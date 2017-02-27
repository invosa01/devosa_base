<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../includes/dbclass/dbclass.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../global/libchart/classes/libchart.php');
$dataPrivilege = getDataPrivileges("workforce_report.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
    die(accessDenied($_SERVER['HTTP_REFERER']));
}
//$tblEmployee = new cModel("hrd_employee", getWords("employee"));
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintAll']) || isset($_REQUEST['btnExportXLS']));
$bolLimit = false;//(getRequestValue('dataLimit', 0) == 1);
//---- INISIALISASI ----------------------------------------------------
$strWordsListOfNewEmployee = getWords("list of new employee");
$strWordsCompany = getWords("company");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("sub section");
$strWordsDate = getWords("date");
$strDataDetail = "";
$strDataDate = "";
$strDivisionName = "";
$strDepartmentName = "";
$strSectionName = "";
$strSubSectionName = "";
$strStyle = "";
$strHidden = "";
$intTotalData = 0; // default, tampilan dibatasi (paging)
$strSearchDisplay = "display:none";
$strWordsNoOfPeople = getWords("no of people");
$strWordsTitle = getWords("age");
$strWordsTotal = getWords("total");
$strDataMale = 0;
$strDataFemale = 0;
$strDataTotal = 0;
$strChartPath = "";
$strChartNewJs = "";
$strChartNew = "";
$strDirPath = "chartimg";
$strFilePath = "$strWordsTitle.png";
$strWordsDate = getWords("salary date");
$strDataInterval = 10;
$strResultInTable = "";
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database,
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getData($strKriteria)
{
    global $tblEmployee;
    global $strDataTotal;
    global $strChartPath;
    global $strChartNewJs;
    global $strChartNew;
    global $strPageTitle;
    global $strDirPath;
    global $strFilePath;
    global $strDate;
    global $strPageTitle;
    global $strDataInterval;
    global $strResultInTable;
    global $db;
    //getCurrYear
    //$strSQL = "SELECT EXTRACT(YEAR FROM salary_date) AS year FROM hrd_salary_master WHERE id = $strDate";
    //ganti query salary date terakhir
    $strSQL = "SELECT EXTRACT(YEAR FROM salary_date) AS year FROM hrd_salary_master Order by salary_date desc limit 1";
    $currYear = $db->execute($strSQL);
    $currYear = $db->fetchrow($currYear);
    $currYear = $currYear["year"];
    //getMaxAge
    $strSQL = "SELECT MAX($currYear - EXTRACT(YEAR FROM birthday)) AS umur FROM hrd_employee WHERE ($currYear - EXTRACT(YEAR FROM birthday))>0";
    $maxAge = $db->execute($strSQL);
    $maxAge = $db->fetchrow($maxAge);
    $maxAge = $maxAge["umur"];
    $chart = new VerticalBarChart();
    $dataSet = new XYDataSet();
    for ($i = 0; $i < ($maxAge + 1) / $strDataInterval; $i++) {
        $strSQL = "SELECT COUNT(*) FROM hrd_employee AS t1
		LEFT JOIN hrd_salary_detail AS t2 ON t1.id = t2.id_employee ";
        $minRange = $i * $strDataInterval;
        $maxRange = $minRange + $strDataInterval - 1;
        $strExecuteKriteria = " AND ($currYear - EXTRACT(YEAR FROM birthday)) BETWEEN $minRange AND $maxRange ";
        $strExecuteSQL = $strSQL . $strKriteria . $strExecuteKriteria;
        $numOfEmployee = $db->execute($strExecuteSQL);
        $numOfEmployee = $db->fetchrow($numOfEmployee);
        $numOfEmployee = $numOfEmployee["count"];
        if ($numOfEmployee == 0) {
            continue;
        }
        $dataSet->addPoint(new Point("$minRange - $maxRange", $numOfEmployee));
        $strResultInTable .= "
		<tr>
			<td>$minRange - $maxRange</td>
			<td align=right>$numOfEmployee</td>
		</tr>
		";
        $strDataTotal += $numOfEmployee;
    }
    /*
        $chart->setDataSet($dataSet);
        $chart->setTitle($strPageTitle);
        $strChartPath = $strDirPath. "/" .$strFilePath;
        if(!file_exists($strChartPath)){
            if(!file_exists($strDirPath)) mkdir($strDirPath);
            $temp = fopen($strChartPath,"w");
            fwrite($temp,"");
            fclose($temp);
        }
        $chart->render($strChartPath);
      */
    $strChartNew = 'Report Baru:<br/>
  <div id="chartContainer" style="height: 400px; width: 700px;">
  </div>';
    $strChartNewJs = '<script type="text/javascript">
  window.onload = function () {
    var chart = new CanvasJS.Chart("chartContainer", {
      theme:"theme3",animationEnabled: true,
      title:{
        text: "Employee Age"
      },

      data: [  //array of dataSeries
        { //dataSeries - first quarter
          /*** Change type "column" to "bar", "area", "line" or "pie"***/
          type: "column",
          name: "First Quarter",
          dataPoints: [
            { label: "20-25", y: 18 },
            { label: "orange", y: 29 },
            { label: "apple", y: 40 },
            { label: "mango", y: 34 },
            { label: "grape", y: 24 },
            { label: "12", y: 24 },
            { label: "13", y: 24 },
            { label: "14", y: 24 },
            { label: "15", y: 24 }
            ]
          },
          { //dataSeries - second quarter

            type: "column",
            name: "Second Quarter",
            dataPoints: [
              { label: "25-30", y: 23 },
              { label: "orange", y: 33 },
              { label: "apple", y: 48 },
              { label: "mango", y: 37 },
              { label: "grape", y: 20 },
              { label: "33", y: 22 },
              { label: "34", y: 30 },
              { label: "35", y: 19 },
              { label: "36", y: 20 }
              ]
            }
            ]
          });

          chart.render();
        }
        </script>
        <script type="text/javascript" src="../canvasjs/canvasjs.min.js"></script>';
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    // ------ AMBIL DATA KRITERIA -------------------------
    (isset($_REQUEST['dataSalaryDate'])) ? $strDate = $_REQUEST['dataSalaryDate'] : $strDate = "";
    (isset($_REQUEST['dataDivision'])) ? $strDataDivision = $_REQUEST['dataDivision'] : $strDataDivision = "";
    (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
    (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
    (isset($_REQUEST['dataSubsection'])) ? $strDataSubSection = $_REQUEST['dataSubsection'] : $strDataSubSection = "";
    (isset($_REQUEST['dataInterval']) && $_REQUEST['dataInterval'] > 0) ? $strDataInterval = $_REQUEST['dataInterval'] : $strDataInterval = 10;
    $strHidden = "<input type=\"hidden\" name=\"dataSalaryDate\"  value=\"$strDate\">";
    //$strHidden .= "<input type=\"hidden\" name=\"dataDateThru\"  value=\"$strDateThru\">";
    $strHidden .= "<input type=\"hidden\" name=\"dataDivision\" value=\"$strDataDivision\">";
    $strHidden .= "<input type=\"hidden\" name=\"dataDepartment\" value=\"$strDataDepartment\">";
    $strHidden .= "<input type=\"hidden\" name=\"dataSection\"   value=\"$strDataSection\">";
    $strHidden .= "<input type=\"hidden\" name=\"dataSubSection\"   value=\"$strDataSubSection\">";
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    $strDataEployee = "";
    scopeData(
        $strDataEmployee,
        $strDataSubSection,
        $strDataSection,
        $strDataDepartment,
        $strDataDivision,
        $_SESSION['sessionUserRole'],
        $arrUserInfo
    );
    $strKriteria = "WHERE 1=1 ";
    if ($strDataDivision != "") {
        $strSQL = "SELECT division_name FROM hrd_division WHERE division_code = '$strDataDivision' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strDivisionName = $rowDb['division_name'];
        }
        $strKriteria .= "AND t1.division_code = '$strDataDivision' ";
    }
    if ($strDataDepartment != "") {
        $strSQL = "SELECT department_name FROM hrd_department WHERE department_code = '$strDataDepartment' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strDepartmentName = $rowDb['department_name'];
        }
        $strKriteria .= "AND t1.department_code = '$strDataDepartment' ";
    }
    if ($strDataSection != "") {
        $strSQL = "SELECT section_name FROM hrd_section WHERE section_code = '$strDataSection' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strSectionName = $rowDb['section_name'];
        }
        $strKriteria .= "AND t1.section_code = '$strDataSection' ";
    }
    if ($strDataSubSection != "") {
        $strSQL = "SELECT sub_section_name FROM hrd_sub_section WHERE sub_section_code = '$strDataSubSection' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strSubSectionName = $rowDb['sub_section_name'];
        }
        $strKriteria .= "AND t1.sub_section_code = '$strDataSubSection' ";
    }
    //$strDate untuk join HRD SALARY DETAIL DGN HRD SALARY MASTER
    $strKriteria .= "AND id_salary_master = '$strDate' ";
    //$strKriteria .= "AND join_date BETWEEN '$strDate' AND '$strDateThru'";
    $strKriteriaCompany = str_replace("id", "t1.id", $strKriteriaCompany);
    $strKriteria .= $strKriteriaCompany;
    if ($bolCanView) {
        getData($strKriteria);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
    $strSQL = "SELECT salary_date FROM hrd_salary_master WHERE id = $strDate";
    $strDataDate = $db->execute($strSQL);
    $strDataDate = $db->fetchrow($strDataDate);
    $strDataDate = $strDataDate["salary_date"];
    $strDataDate = pgDateFormat($strDataDate, "d F Y");// ." -". pgDateFormat($strDateThru, "d F Y");
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
