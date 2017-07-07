<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('form_object.php');
$dataPrivilege = getDataPrivileges(
    "employee_search.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintAll']) || isset($_REQUEST['btnExcel']));
$bolFull = (isset($_REQUEST['filterFull']));
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strPaging = "";
$strHidden = "";
$strBtnPrint = "";
$intTotalData = 0;
$bolLimit = true; // default, tampilan dibatasi (paging)
$strButtons = "";
$strStyle = "";
$strWordsFunctional = getWords("functional");
$strWordsCompany = getWords("company");
$strWordsSubSection = getWords("subsect.");
$strWordsSection = getWords("section");
$strWordsDepartment = getWords("department");
$strWordsDivision = getWords("division");
$strWordsLevel = getWords("level");
$strWordsEmployeeStatus = getWords("employee status");
$strWordsEmployeeID = getWords("employee id");
$strWordsSearchEmployee = getWords("search employee");
$strWordsSimpleResume = getWords("simple resume");
$strWordsReport = getWords("report");
$strWordsShow = getWords("show");
$strWordsExcel = getWords("excel");
$strWordsListOfEmployee = getWords("list of employee");
$strWordsEmployeeData = getWords("employee data");
$strWordsTrainingData = getWords("training data");
$strWordsEducationData = getWords("education data");
$strWordsWorkExperience = getWords("work experience");
$strWordsNo = getWords("no");
$strWordsEmployeeId = getWords("empl.id");
$strWordsEmployeeName = getWords("empl.name");
$strWordsDept = getWords("dept.");
$strWordsSect = getWords("sect.");
$strWordsLevel = getWords("level");
$strWordsFunctional = getWords("functional");
$strWordsSubject = getWords("subject");
$strWordsInstitution = getWords("institution");
$strWordsLocation = getWords("location");
$strWordsTrainer = getWords("trainer");
$strWordsPeriode = getWords("periode");
$strWordsCost = getWords("cost");
$strWordsFaculty = getWords("faculty");
$strWordsPosition = getWords("position");
// $strWordsFunctional = getWords("functional");
// $strWordsFunctional = getWords("functional");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getData($db, $strKriteria)
{
  $employeeData = null;
  if ($db->connect()) {
    $strSQL = "SELECT emp.id,emp.employee_id,emp.employee_name,emp.division_code,emp.department_code,
			emp.section_code,emp.sub_section_code,emp.position_code, dept.department_name FROM hrd_employee AS emp 
			LEFT JOIN hrd_department AS dept ON emp.department_code = dept.department_code ";
    $strSQL .= "WHERE 1=1 $strKriteria ";
    $resDb = $db->execute($strSQL);
    $employeeData = [];
    $i = 0;
    while ($rowDb = $db->fetchrow($resDb)) {
      $employeeData[$i] = $rowDb;
      $strSQL2 = "SELECT subject,institution,location,day_from,month_from,year_from,day_thru,
	      month_thru,year_thru,trainer,cost FROM hrd_employee_training ";
      $strSQL2 .= "WHERE id_employee = '" . $rowDb['id'] . "' ORDER BY year_from, month_from, day_from ";
      $resTmp = $db->execute($strSQL2);
      $variables = [];
      $j = 0;
      while ($rowTmp = $db->fetchrow($resTmp)) {
        if ($j == 0) {
          $variables['id'] = 'acc-training-' . $rowDb['id'];
        }
        $trainingData = [];
        $trainingData['title'] = $rowTmp['subject'] . ' [ ' . $rowTmp['institution'] . ',' . $rowTmp['trainer'] . ' ]';
        $listData = [];
        $listData['element'][] = ['title' => 'Subject', 'description' => $rowTmp['subject']];
        $listData['element'][] = ['title' => 'Institution', 'description' => $rowTmp['institution']];
        $listData['element'][] = ['title' => 'Trainer', 'description' => $rowTmp['trainer']];
        $listData['element'][] = ['title' => 'Location', 'description' => $rowTmp['location']];
        $from = $rowTmp['day_from'] . '-' . $rowTmp['month_from'] . '-' . $rowTmp['year_from'];
        $thru = $rowTmp['day_thru'] . '-' . $rowTmp['month_thru'] . '-' . $rowTmp['year_thru'];
        $listData['element'][] = ['title' => 'From', 'description' => $from];
        $listData['element'][] = ['title' => 'Thru', 'description' => $thru];
        $trainingData['description'] = createHorizontalList($listData);
        $variables['element'][] = $trainingData;
        $j++;
      }
      if ($j > 0) {
        $employeeData[$i]['training'] = createAccordion($variables);
      } else {
        $employeeData[$i]['training'] = '-';
      }
      $strSQL2 = "SELECT education_level_code,institution,day_from,month_from,year_from,day_thru,month_thru,
				year_thru,faculty,location,certificate_no FROM hrd_employee_education ";
      $strSQL2 .= "WHERE id_employee = '" . $rowDb['id'] . "' ORDER BY year_from, month_from, day_from ";
      $resTmp = $db->execute($strSQL2);
      $j = 0;
      $variables = [];
      while ($rowTmp = $db->fetchrow($resTmp)) {
        if ($j == 0) {
          $variables['id'] = 'acc-education-' . $rowDb['id'];
        }
        $educationData = [];
        $educationData['title'] = $rowTmp['education_level_code'] . ' [ ' . $rowTmp['faculty'] . ', ' . $rowTmp['institution'] . ' ]';
        $listData = [];
        $listData['element'][] = ['title' => 'Education', 'description' => $rowTmp['education_level_code']];
        $listData['element'][] = ['title' => 'Institution', 'description' => $rowTmp['institution']];
        $listData['element'][] = ['title' => 'Faculty', 'description' => $rowTmp['faculty']];
        $listData['element'][] = ['title' => 'Location', 'description' => $rowTmp['location']];
        $listData['element'][] = ['title' => 'Certificate', 'description' => $rowTmp['certificate_no']];
        $from = $rowTmp['day_from'] . '-' . $rowTmp['month_from'] . '-' . $rowTmp['year_from'];
        $thru = $rowTmp['day_thru'] . '-' . $rowTmp['month_thru'] . '-' . $rowTmp['year_thru'];
        $listData['element'][] = ['title' => 'From', 'description' => $from];
        $listData['element'][] = ['title' => 'Thru', 'description' => $thru];
        $educationData['description'] = createHorizontalList($listData);
        $variables['element'][] = $educationData;
        $j++;
      }
      if ($j > 0) {
        $employeeData[$i]['education'] = createAccordion($variables);
      } else {
        $employeeData[$i]['education'] = '-';
      }
      $strSQL2 = "SELECT institution,location,position,day_from,month_from,year_from,day_thru,month_thru,
	      year_thru,note FROM hrd_employee_work ";
      $strSQL2 .= "WHERE id_employee = '" . $rowDb['id'] . "' ORDER BY year_from, month_from, day_from ";
      $resTmp = $db->execute($strSQL2);
      $j = 0;
      $variables = [];
      while ($rowTmp = $db->fetchrow($resTmp)) {
        if ($j == 0) {
          $variables['id'] = 'acc-work-' . $rowDb['id'];
        }
        $workData = [];
        $workData['title'] = $rowTmp['institution'] . ' [ ' . $rowTmp['position'] . ', ' . $rowTmp['location'] . ' ]';
        $listData = [];
        $listData['element'][] = ['title' => 'Institution', 'description' => $rowTmp['institution']];
        $listData['element'][] = ['title' => 'Position', 'description' => $rowTmp['position']];
        $listData['element'][] = ['title' => 'Location', 'description' => $rowTmp['location']];
        $note = empty($rowTmp['note']) ? '-' : $rowTmp['note'];
        $listData['element'][] = ['title' => 'Note', 'description' => $note];
        $from = $rowTmp['day_from'] . '-' . $rowTmp['month_from'] . '-' . $rowTmp['year_from'];
        $thru = $rowTmp['day_thru'] . '-' . $rowTmp['month_thru'] . '-' . $rowTmp['year_thru'];
        $listData['element'][] = ['title' => 'From', 'description' => $from];
        $listData['element'][] = ['title' => 'Thru', 'description' => $thru];
        $workData['description'] = createHorizontalList($listData);
        $variables['element'][] = $workData;
        $j++;
      }
      if ($j > 0) {
        $employeeData[$i]['work'] = createAccordion($variables);
      } else {
        $employeeData[$i]['work'] = '-';
      }
      $i++;
    }
  }
  return $employeeData;
}

// fungsi untuk menampilkan data, dalam excel
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getExcelData($db, &$intRows, $strKriteria = "", $strOrder = "", $bolFull = false)
{
  //include("../global/class.excelExport.php");
  global $words;
  global $bolPrint;
  global $ARRAY_EMPLOYEE_STATUS;
  global $intTotalData;
  global $bolIsEmployee;
  //-----------------
  $arrHeader = [];
  $arrData = [];
  // bikin dulu header, apa aja yang mau ditampilkan
  $intCols = 0;
  $arrHeader[$intCols++] = ["text" => "NO", "type" => "numeric", "width" => 5];
  $arrHeader[$intCols++] = ["text" => "EMPL. ID", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "EMPL. NAME", "type" => "", "width" => 17];
  $arrHeader[$intCols++] = ["text" => "ASSIGNMENT NOTE", "type" => "", "width" => 6];
  $arrHeader[$intCols++] = ["text" => "NICK", "type" => "", "width" => 8];
  $arrHeader[$intCols++] = ["text" => "AGE", "type" => "numeric", "width" => 5];
  $arrHeader[$intCols++] = ["text" => "EMP. STATUS", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "JOIN DATE", "type" => "date", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "DUE DATE", "type" => "date", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "PERM. DATE", "type" => "date", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "RESIGN DATE", "type" => "date", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "STATUS", "type" => "", "width" => 7];
  $arrHeader[$intCols++] = ["text" => "DEPT", "type" => "", "width" => 6];
  $arrHeader[$intCols++] = ["text" => "SECT", "type" => "", "width" => 6];
  $arrHeader[$intCols++] = ["text" => "FUNCTION", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "LEVEL", "type" => "", "width" => 7];
  $arrHeader[$intCols++] = ["text" => "JOB GRADE", "type" => "", "width" => 7];
  $arrHeader[$intCols++] = ["text" => "FAM. ST", "type" => "", "width" => 6];
  $arrHeader[$intCols++] = ["text" => "ADDRESS", "type" => "", "width" => 20];
  $arrHeader[$intCols++] = ["text" => "CITY", "type" => "", "width" => 7];
  $arrHeader[$intCols++] = ["text" => "ZIP", "type" => "", "width" => 7];
  $arrHeader[$intCols++] = ["text" => "PHONE", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "EMAIL", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "BIRTHPLACE", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "BIRTHDATE", "type" => "date", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "WEIGHT", "type" => "", "width" => 8];
  $arrHeader[$intCols++] = ["text" => "HEIGHT", "type" => "", "width" => 8];
  $arrHeader[$intCols++] = ["text" => "BLOOD", "type" => "", "width" => 7];
  $arrHeader[$intCols++] = ["text" => "ID CARD", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "DRIVE A", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "DRIVE B", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "DRIVE C", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "PASSPORT", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "NPWP", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "BANK 1", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "BRANCH 1", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "ACCOUNT 1", "type" => "", "width" => 15];
  $arrHeader[$intCols++] = ["text" => "ACCOUNT NAME 1", "type" => "", "width" => 20];
  $arrHeader[$intCols++] = ["text" => "BANK 2", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "BRANCH 2", "type" => "", "width" => 10];
  $arrHeader[$intCols++] = ["text" => "ACCOUNT 2", "type" => "", "width" => 15];
  $arrHeader[$intCols++] = ["text" => "ACCOUNT NAME 2", "type" => "", "width" => 20];
  $intRows = 0;
  $strSQL = "SELECT *,(EXTRACT(YEAR FROM AGE(birthday))) AS umur FROM hrd_employee ";
  $strSQL .= "WHERE flag=0 $strKriteria ";
  $strSQL .= "ORDER BY $strOrder employee_name ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intCols = 0;
    $arrData[$intRows][$intCols++] = ($intRows + 1);
    $arrData[$intRows][$intCols++] = "" . $rowDb['employee_id'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['employee_name'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['letter_code'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['nickname'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['umur'];
    $arrData[$intRows][$intCols++] = $words[$ARRAY_EMPLOYEE_STATUS[$rowDb['employee_status']]];
    $arrData[$intRows][$intCols++] = pgDateFormat($rowDb['join_date'], "d-M-y");
    $arrData[$intRows][$intCols++] = pgDateFormat($rowDb['due_date'], "d-M-y");
    $arrData[$intRows][$intCols++] = pgDateFormat($rowDb['permanent_date'], "d-M-y");
    $arrData[$intRows][$intCols++] = pgDateFormat($rowDb['resign_date'], "d-M-y");
    $arrData[$intRows][$intCols++] = ($rowDb['active'] == 1) ? $words['active'] : $words['not active'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['department_code'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['section_code'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['function'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['position_code'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['grade_code'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['family_status_code'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['primary_address'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['primary_city'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['primary_zip'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['primary_phone'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['email'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['birthplace'];
    $arrData[$intRows][$intCols++] = pgDateFormat($rowDb['birthday'], "d-M-y");
    $arrData[$intRows][$intCols++] = "" . $rowDb['weight'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['height'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['blood_type'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['id_card'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['driver_license_a'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['driver_license_b'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['driver_license_c'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['passport'];
    $arrData[$intRows][$intCols++] = "" . $rowDb['npwp'];
    $intRows++;
  }
  $objExl = new CxlsExport("employee.xls");
  $objExl->setHeaders("LIST OF EMPLOYEE", "", "");
  $objExl->setData($arrHeader, $arrData);
  $objExl->showExcel();
  if ($intRows > 0) {
    writeLog(ACTIVITY_EXPORT, MODULE_PAYROLL, "$intRows data", 0);
  }
  return $strResult;
} // showData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  // ------ AMBIL DATA KRITERIA -------------------------
  (isset($_REQUEST['filterEmployeeID'])) ? $strFilterEmployeeID = trim(
      $_REQUEST['filterEmployeeID']
  ) : $strFilterEmployeeID = "";
  (isset($_REQUEST['filterPosition'])) ? $strFilterPosition = trim(
      $_REQUEST['filterPosition']
  ) : $strFilterPosition = "";
  (isset($_REQUEST['filterStatus'])) ? $strFilterStatus = $_REQUEST['filterStatus'] : $strFilterStatus = "";
  (isset($_REQUEST['filterDivision'])) ? $strFilterDivision = $_REQUEST['filterDivision'] : $strFilterDivision = "";
  (isset($_REQUEST['filterDepartment'])) ? $strFilterDepartment = $_REQUEST['filterDepartment'] : $strFilterDepartment = "";
  (isset($_REQUEST['filterSection'])) ? $strFilterSection = $_REQUEST['filterSection'] : $strFilterSection = "";
  (isset($_REQUEST['filterSubSection'])) ? $strfilterSubSection = $_REQUEST['filterSubSection'] : $strfilterSubSection = "";
  (isset($_REQUEST['filterGrade'])) ? $strFilterGrade = $_REQUEST['filterGrade'] : $strFilterGrade = "";
  (isset($_REQUEST['filterFunction'])) ? $strFilterFunction = $_REQUEST['filterFunction'] : $strFilterFunction = "";
  (isset($_REQUEST['dataPage'])) ? $intCurrPage = $_REQUEST['dataPage'] : $intCurrPage = 1;
  (isset($_REQUEST['dataSort'])) ? $strSortBy = $_REQUEST['dataSort'] : $strSortBy = "";
  $strInputSortBy = $strSortBy;
  $strDataSubDepartment = '';
  if (!is_numeric($intCurrPage)) {
    $intCurrPage = 1;
  }
  if ($strSortBy != "") {
    $strSortBy = "\"$strSortBy\", ";
  }
  scopeData(
      $strFilterEmployeeID,
      $strfilterSubSection,
      $strFilterSection,
      $strDataSubDepartment,
      $strFilterDepartment,
      $strFilterDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  $strDisabled = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) ? 'disabled' : 'enabled';
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  $strInfoKriteria = "";
  $strEmployeeName = "";
  if ($strFilterEmployeeID != "") {
    $strKriteria .= "AND emp.employee_id = '$strFilterEmployeeID' ";
    $employeeData = getEmployeNameByID($db, $strFilterEmployeeID);
    $strEmployeeName = $employeeData['employee_name'];
  }
  if ($strFilterStatus != "") {
    $strKriteria .= "AND emp.employee_status = '$strFilterStatus' ";
  }
  if ($strFilterEmployeeID != "") {
    $strKriteria .= "AND upper(emp.employee_id) = '" . strtoupper($strFilterEmployeeID) . "' ";
  }
  if ($strFilterPosition != "") {
    $strKriteria .= "AND emp.position_code = '$strFilterPosition' ";
  }
  if ($strFilterDivision != "") {
    $strKriteria .= "AND emp.division_code = '$strFilterDivision' ";
  }
  if ($strFilterDepartment != "") {
    $strKriteria .= "AND emp.department_code = '$strFilterDepartment' ";
  }
  if ($strFilterSection != "") {
    $strKriteria .= "AND emp.section_code = '$strFilterSection' ";
  }
  if ($strfilterSubSection != "") {
    $strKriteria .= "AND emp.sub_section_code = '$strfilterSubSection' ";
  }
  if ($strFilterFunction != "") {
    $strKriteria .= "AND \"function\" = '$strFilterFunction' ";
  }
  $strKriteria .= $strKriteriaCompany;
  if ($bolCanView) {
    $strBtnShow = '';
    if (isset($_REQUEST['btnExcel'])) {
      //getExcelData($db,$intTotalData, $strKriteria, $strSortBy, $bolFull);
      //$strDataDetail = getData($db,$intTotalData, $strKriteria, $intCurrPage, $bolLimit, $strSortBy);
      // ambil data CSS-nya
      if (file_exists("bw.css")) {
        $strStyle = "bw.css";
      }
      $strPrintCss = "";
      $strPrintInit = "";
      headeringExcel("employee_resume.xls");
    } else if (isset($_REQUEST['btnShow']) || isset($_REQUEST['btnPrint'])) {
      $strBtnShow = '<input type="hidden" name="btnShow">';
      $newDataset = getData($db, $strKriteria);
      $intTotalData = count($newDataset);
      $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%");
      $myDataGrid->caption = getWords("list of employee");
      $myDataGrid->addColumnNumbering(
          new DataGrid_Column(getWords("No"), "", ['width' => 30], ['nowrap' => '', 'valign' => 'top'])
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              strtoupper(getWords("empl.id")),
              "employee_id",
              ['width' => 70],
              ["nowrap" => "nowrap", 'valign' => 'top'],
              true,
              true,
              "",
              "",
              "string",
              true,
              12,
              false
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("employee name"),
              "employee_name",
              [],
              ["nowrap" => "nowrap", 'valign' => 'top'],
              true,
              true,
              "",
              "",
              "string",
              true,
              35
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("dept."),
              "department_name",
              ["width" => 50],
              ["nowrap" => "nowrap", 'valign' => 'top'],
              true,
              true,
              "",
              "",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("sect."),
              "section_code",
              ["width" => 50],
              ["nowrap" => "nowrap", 'valign' => 'top'],
              true,
              true,
              "",
              "",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("position"),
              "position_code",
              ["width" => 70],
              ["nowrap" => "nowrap", 'valign' => 'top'],
              true,
              true,
              "",
              "",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("training"),
              "training",
              ["width" => 70],
              ["nowrap" => "nowrap", 'valign' => 'top'],
              false,
              false,
              "",
              "",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("education"),
              "education",
              ["width" => 70],
              ["nowrap" => "nowrap", 'valign' => 'top'],
              false,
              false,
              "",
              "",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("work experience"),
              "work",
              ["width" => 70],
              ["nowrap" => "nowrap", 'valign' => 'top'],
              false,
              false,
              "",
              "",
              "string",
              true,
              12
          )
      );
      $myDataGrid->getRequest();
      $myDataGrid->totalData = count($newDataset);
      $myDataGrid->bind($newDataset);
      $strDataDetail = $myDataGrid->render();
    }
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  // generate data hidden input dan element form input
  $intDefaultWidth = 30;
  $intDefaultWidthPx = 200;
  $intDefaultHeight = 3;
  $strInputFilterEmployeeID = "<input type=\"text\" class=\"form-control\" name=\"filterEmployeeID\" id=\"filterEmployeeID\" size=$intDefaultWidth value=\"$strFilterEmployeeID\" style=\"width:$intDefaultWidthPx\" $strEmpReadonly>";
  $strInputFilterPosition = getPositionList(
      $db,
      "filterPosition",
      $strFilterPosition,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\""
  );
  $strInputFilterStatus = getEmployeeStatusList(
      "filterStatus",
      $strFilterStatus,
      $strEmptyOption,
      " style=\"width:$intDefaultWidthPx\""
  );
  $strInputFilterDivision = getDivisionList(
      $db,
      "filterDivision",
      $strFilterDivision,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['division']
  );
  $strInputFilterDepartment = getDepartmentList(
      $db,
      "filterDepartment",
      $strFilterDepartment,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['department']
  );
  $strInputFilterSection = getSectionList(
      $db,
      "filterSection",
      $strFilterSection,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['section']
  );
  $strInputFilterSubSection = getSubSectionList(
      $db,
      "filterSubSection",
      $strfilterSubSection,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['sub_section']
  );
  $strInputFilterFunction = getFunctionalPositionList(
      $db,
      "filterFunction",
      $strFilterFunction,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\""
  );
  $strInputCompany = getCompanyList(
      $db,
      "dataCompany",
      $strDataCompany,
      $strEmptyOption2,
      $strKriteria2,
      "style=\"width:$intDefaultWidthPx\" "
  );
  $strHidden .= "<input type=hidden name=filterEmployeeID value=\"$strFilterEmployeeID\">";
  $strHidden .= "<input type=hidden name=filterPosition value=\"$strFilterPosition\">";
  $strHidden .= "<input type=hidden name=filterStatus value=\"$strFilterStatus\">";
  $strHidden .= "<input type=hidden name=filterDivision value=\"$strFilterDivision\">";
  $strHidden .= "<input type=hidden name=filterDepartment value=\"$strFilterDepartment\">";
  $strHidden .= "<input type=hidden name=filterSection value=\"$strFilterSection\">";
  $strHidden .= "<input type=hidden name=filterSubSection value=\"$strfilterSubSection\">";
  $strHidden .= "<input type=hidden name=filterFunction value=\"$strFilterFunction\">";
}
//$strInitAction .= "    document.formInput.filterEmployeeID.focus();   ";
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$strPageDesc = 'Employee simple resume';
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = employeeDataSubmenu($strWordsSimpleResume);
if ($bolPrint) {
  $strTemplateFile = getTemplate("employee_resume_all_print.html");
  $tbsPage->LoadTemplate($strTemplateFile);
} else {
  $strTemplateFile = getTemplate("employee_resume_all.html");
  $tbsPage->LoadTemplate($strMainTemplate);
}
//------------------------------------------------
//Load Master Template
$tbsPage->Show();
?>