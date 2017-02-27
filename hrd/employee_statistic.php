<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
// Data Privilage followed from parent (employee_edit.php)
$dataPrivilege = getDataPrivileges(
    basename("employee_edit.php"),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView && $_POST['dataID'] == "") {
  die(getWords('view denied'));
}
$bolPrint = (isset($_REQUEST['btnPrint']));
if ($bolPrint) {
  header('Pragma: no-cache');
  header('Content-Type: application/vnd.ms-word');
  header('Content-Disposition: download; filename="salary_slip.doc"');
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intDefaultWidth = 30;
$intDefaultWidthPx = 210;
$intDefaultHeight = 3;
$strInputFiles = "";
$strWordsEmployeeData = getWords("employee data");
$strWordsPrimaryInformation = getWords("primary information ");
// Param inisial untuk grafik
$strChartNew = "";
$strChartNewJs = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function get_data_salary_yearly($db, $strParam)
{
  global $strDataID;
  $arrData = [];
  $strSQL = "SELECT AVG(\"total_net\") AS gaji ";
  $strSQL .= "FROM \"hrd_salary_master\" AS t1, \"hrd_salary_detail\" AS t2 ";
  $strSQL .= "WHERE t1.id = t2.\"id_salary_master\" ";
  $strSQL .= "AND EXTRACT(YEAR FROM t1.\"salary_date\") = '" . $strParam["Y"] . "' ";
  //$strSQL .= "AND EXTRACT(MONTH FROM t1.\"salary_date\") = '".$strParam["M"]."' ";
  $resDb = $db->execute($strSQL);
  $value = 0;
  while ($rowDb = $db->fetchrow($resDb)) {
    $value += $rowDb['gaji'];
  }
  $arrData = ["jum" => $value];
  return $arrData;
}

function get_data_salary_monthly($db, $strParam)
{
  global $strDataID;
  $arrData = [];
  $strSQL = "SELECT AVG(\"total_net\") AS gaji ";
  $strSQL .= "FROM \"hrd_salary_master\" AS t1, \"hrd_salary_detail\" AS t2 ";
  $strSQL .= "WHERE t1.id = t2.\"id_salary_master\" ";
  $strSQL .= "AND EXTRACT(YEAR FROM t1.\"salary_date\") = '" . $strParam["Y"] . "' ";
  $strSQL .= "AND EXTRACT(MONTH FROM t1.\"salary_date\") = '" . $strParam["M"] . "' ";
  $resDb = $db->execute($strSQL);
  $value = 0;
  while ($rowDb = $db->fetchrow($resDb)) {
    $value += $rowDb['gaji'];
  }
  $arrData = ["jum" => $value];
  return $arrData;
}

function getData($db, &$arrData, $strDataID = "")
{
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $_SESSION;
  global $arrUserInfo;
  global $bolIsEmployee;
  global $strChartNew;
  global $strChartNewJs;
  $bolNewData = true;
  $strChartNew .= '<div id="chartContainer" style="height: 400px; width: 800px;"></div>';
  $strChartNew .= '<div id="chartContainer2" style="height: 400px; width: 800px;"></div>';
  $strChartNewJs .= '<script type="text/javascript">
    window.onload = function () {';
  $strPingData = "";
  $maxAxis = 11;
  $i = 0;
  $strIndex = ["Y" => date("Y"), "M" => date("m")];
  while ($i <= $maxAxis) {
    $strIndex = ["Y" => $strIndex["Y"], "M" => date("m") - $i];
    if ($strIndex["M"] == 0) {
      $strIndex = ["Y" => date("Y") - 1, "M" => 12];
    }
    $strData = 0;
    $returnvalue = get_data_salary_monthly($db, $strIndex);
    $strData = $returnvalue["jum"];
    if ($strData == "") {
      $strData = 0;
    }
    $arrPingData[$i] = $strData;
    $i++;
  }
  $i = 0;
  $strIndex = ["Y" => date("Y"), "M" => date("m")];
  while ($i <= $maxAxis) {
    //$strIndex=$strIndex-1;
    $strIndex = ["Y" => $strIndex["Y"], "M" => date("m") - $i];
    if ($strIndex["M"] == 0) {
      $strIndex = ["Y" => date("Y") - 1, "M" => 12];
    }
    if ($i != 0) {
      $strPingData .= ",";
    }
    $strPingData .= "{ label:\"" . $strIndex["Y"] . "-" . $strIndex["M"] . "\",y:" . $arrPingData[$i] . ",indexLabel:\"" . number_format(
            $arrPingData[$i]
        ) . "\"}";
    $i++;
  }
  $strChartNewJs .= loadJs(
      [
          'chartname'     => "chart1",
          'chartId'       => "chartContainer",
          'strPingData'   => $strPingData,
          'intervalaxisY' => 500000,
          'type'          => "line",
          'name'          => "Month",
          'title'         => "Monthly Salary",
          'titleAxisY'    => "Salary (Rp)",
          'tooltip'       => "Salary : {y}"
      ]
  );
  $strPingData = "";
  $maxAxis = 11;
  $i = 0;
  $strIndex = ["Y" => date("Y"), "M" => date("m")];
  while ($i <= $maxAxis) {
    $strIndex = ["Y" => date("Y") - $i, "M" => date("m")];
    $strData = 0;
    $returnvalue = get_data_salary_yearly($db, $strIndex);
    $strData = $returnvalue["jum"];
    if ($strData == "") {
      $strData = 0;
    }
    $arrPingData[$i] = $strData;
    $i++;
  }
  $i = 0;
  $strIndex = ["Y" => date("Y"), "M" => date("m")];
  while ($i <= $maxAxis) {
    //$strIndex=$strIndex-1;
    $strIndex = ["Y" => date("Y") - $i, "M" => date("m")];
    if ($i != 0) {
      $strPingData .= ",";
    }
    $strPingData .= "{ label:\"" . $strIndex["Y"] . "\",y:" . $arrPingData[$i] . ",indexLabel:\"" . number_format(
            $arrPingData[$i]
        ) . "\"}";
    $i++;
  }
  $strChartNewJs .= loadJs(
      [
          'chartname'     => "chart2",
          'chartId'       => "chartContainer2",
          'strPingData'   => $strPingData,
          'intervalaxisY' => 500000,
          'type'          => "line",
          'name'          => "Year",
          'title'         => "Yearly(average) Salary",
          'titleAxisY'    => "Salary (Rp)",
          'tooltip'       => "Salary : {y}"
      ]
  );
  $strChartNewJs .= '}
      </script>
      <script type="text/javascript" src="../canvasjs/canvasjs.min.js"></script>';
  return true;
} // showData
function loadJs($arrParam)
{
  $nextSeries = "";
  if (count(@$arrParam["nextSeries"]) > 0) {
    foreach ($arrParam["nextSeries"] as $paramItem) {
      $nextSeries .= ',{
            /*** Change type "column" to "bar", "area", "line" or "pie"***/
            type: "' . @$paramItem["type"] . '",
            toolTipContent:"' . @$paramItem["tooltip"] . '",
            showInLegend: true,
            name: "' . @$paramItem["name"] . '",
            markerSize:8,
                                    indexLabelFontColor: "darkSlateGray",
                                    color: "' . @$paramItem["color"] . '",
            dataPoints: [' . @$paramItem["strPingData"] . '
            ]
          }';
    }
  }
  return '
      var ' . $arrParam["chartname"] . ' = new CanvasJS.Chart("' . $arrParam["chartId"] . '", {
        theme:"theme3",animationEnabled: true,
        title:{
          text: "' . @$arrParam["title"] . '"
        },
                animationEnabled: true,
                    axisX:{
                                  interval: 1,
                                  intervalType: "year",
                                  labelAngle: -50,
                                  labelFontColor: "rgb(0,75,141)",
                    },
                    axisY: {
                                  title: "' . $arrParam["titleAxisY"] . '",
                                  interval: ' . $arrParam["intervalaxisY"] . '
                    },


        data: [
          { 
            /*** Change type "column" to "bar", "area", "line" or "pie"***/
            type: "' . $arrParam["type"] . '",
            toolTipContent:"' . $arrParam["tooltip"] . '",
            showInLegend: true,
            name: "' . @$arrParam["name"] . '",
            markerSize:8,
                                    indexLabelFontColor: "darkSlateGray",
                                    color: "rgba(0,255,0,0.7)",
            dataPoints: [' . $arrParam["strPingData"] . '
            ]
          }' . $nextSeries . '
          ]
        });
        ' . $arrParam["chartname"] . '.render();

      ';
}

//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  //getUserEmployeeInfo();
  (isset($_POST['dataID'])) ? $strDataID = $_POST['dataID'] : $strDataID = "";
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      saveData($db, $strDataID, $strError);
      if ($strError != "") {
        echo "<script>alert(\"$strError\")</script>";
      }
    }
  }
  if ($strDataID != "") {
    getData($db, $arrData, $strDataID);
  }
  //----- TAMPILKAN DATA ---------
  if ($bolIsEmployee && !isMe($strDataID)) {
    redirectPage("employee_search.php");
  }
  if (thisUserIs(ROLE_SUPERVISOR)) {
    if ($arrUserInfo['sub_section_code'] != "" && $arrUserInfo['sub_section_code'] != $arrData['dataSubSectionCode']) {
      redirectPage("employee_search.php");
    } else if ($arrUserInfo['section_code'] != "" && $arrUserInfo['section_code'] != $arrData['dataSectionCode']) {
      redirectPage("employee_search.php");
    } else if ($arrUserInfo['department_code'] != "" && $arrUserInfo['department_code'] != $arrData['dataDepartmentCode']) {
      redirectPage("employee_search.php");
    } else if ($arrUserInfo['division_code'] != "" && $arrUserInfo['division_code'] != $arrData['dataDivisionCode']) {
      redirectPage("employee_search.php");
    }
  }
  $strDataPhoto = "";
  $today = getdate();
  $strToday = $today['weekday'] . ", " . $today['mday'] . " " . $today['month'] . " " . $today['year'];
  //$strInputTaxMaritalStatus = getTaxMaritalStatus($db, $arrData['dataEmployeeID']);
  //tampilkan foto
  if ($arrData['dataPhoto'] == "") {
    $strDataPhoto = "<img src='photos/dummy.gif'>";
  } else {
    if (file_exists("photos/" . $arrData['dataPhoto'])) {
      //$strDataPhoto = "<img src='photos/" .$arrData['dataPhoto']. "'>";
      $strDataPhoto = "<img src=\"employee_photo.php?dataID=$strDataID\">";
    } else {
      $strDataPhoto = "<img src='photos/dummy.gif'>";
    }
  }
}
$strCompanyName = getSetting("company_name");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords("employee data");
$strPageDesc = getWords("Statistic");;
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strWordsStatisticData = getWords("statistik");
$pageSubMenu = employeeEditSubmenu($strWordsStatisticData);
if ($bolPrint) {
  $strTemplateFile = getTemplate("employee_statistic_print.html");
  $tbsPage->LoadTemplate($strTemplateFile);
} else {
  $strTemplateFile = getTemplate("employee_statistic.html");
  $tbsPage->LoadTemplate($strMainTemplate);
}
//$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->Show();
?>