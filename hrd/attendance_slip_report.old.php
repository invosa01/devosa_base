<?php
date_default_timezone_set('Asia/Jakarta');
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/model/model.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('form_object.php');
include_once("cls_employee.php");
$dataPrivilege = getDataPrivileges(
    "attendance_slip_report.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$db = new CDbClass();
$db->connect();
getUserEmployeeInfo();
scopeData(
    $strDataEmployee,
    $strDataSubSection,
    $strDataSection,
    $strDataDepartment,
    $strDataDivision,
    $_SESSION['sessionUserRole'],
    $arrUserInfo
);
$f = new clsForm(
    "form1", /*2 column view*/
    1, "100%"
);
$f->disableFormTag();
$f->showCaption = false;
$f->showMinimizeButton = false;
$f->showCloseButton = false;
$f->addHidden("isShow", 1);
$f->addSelect(
    getWords("company"),
    "dataCompany",
    getDataListCompany($strDataCompany),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    true
);
$f->addInput(
    getWords("date from"),
    "dataDateFrom",
    ($strDateFrom = getInitialValue("DateFrom", date("m") . "/01/" . date("Y"))),
    ["style" => "width:$strDateWidth"],
    "date",
    false,
    true,
    true
);
$f->addInput(
    getWords("date thru"),
    "dataDateThru",
    ($strDateThru = getInitialValue("DateThru", date($_SESSION['sessionDateSetting']['php_format']))),
    ["style" => "width:$strDateWidth"],
    "date",
    false,
    true,
    true
);
$f->addSelect(
    getWords("employee status"),
    "employeeStatus",
    getDataListEmployeeStatus(
        getInitialValue("EmployeeStatus"),
        true,
        ["value" => "", "text" => "", "selected" => true]
    ),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false
);
$f->addSelect(
    getWords("active"),
    "dataActive",
    getDataListEmployeeActive(
        getInitialValue("Active"),
        true,
        ["value" => "", "text" => "", "selected" => true]
    ),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false
);
$autoCompleteValue = getInitialValue("Employee", null, $_SESSION['sessionEmployeeID']);
$employeeName = '';
if (!empty($autoCompleteValue)) {
  $employeeData = getEmployeNameByID($db, $autoCompleteValue);
  $employeeName = $employeeData['employee_name'];
}
$f->addInputAutoComplete(
    getWords("employee id"),
    "employeeName",
    getDataEmployee(getInitialValue("Employee", null, $_SESSION['sessionEmployeeID'])),
    "style=width:$strDefaultWidthPx",
    "string",
    false,
    true,
    true,
    "",
    "",
    true,
    null,
    "../global/hrd_ajax_source.php?action=getemployee",
    $autoCompleteValue
);
$f->addLabelAutoComplete("", "employeeName", $employeeName);
//  //this save button will hide after save <toggle>
$f->addSubmit("btnShow", "Show Slip", ["onClick" => "return validInput();"], true, true, "", "", "");
// $f->addSubmit("btnExportXLS", "Export Excel", array("onClick" => "return validInput();"), true, true, "", "", "");
$formInput = $f->render();
$showReport = (isset($_POST['btnShow']) || isset($_POST['btnExportXLS']) || isset($_POST['isShow']));
$totalData = 0;
$dataGrid = "";
$strInitAction = "";
$strStatus = $f->getValue('employeeStatus');
$strName = $f->getValue('employeeName');
$strCompany = $f->getValue('dataCompany');
$strActive = $f->getValue('dataActive');
if ($showReport) {
  $strDateFrom = $f->getValue('dataDateFrom');
  $strDateThru = $f->getValue('dataDateThru');
  $strKriteria = "";
  if ($strStatus != "") {
    $strKriteria .= " AND t0.\"employee_status\" = $strStatus";
  }
  if ($strName != "") {
    $strKriteria .= " AND t0.\"employee_id\" = '$strName' ";
  }
  if ($strCompany != "") {
    $strKriteria .= " AND t0.id_company = '$strCompany' ";
  }
  if ($strActive != "") {
    $strKriteria .= " AND t0.active = '$strActive' ";
  }
  $strErrorMessage = "";
  $myDataGrid = new cDataGrid("form1", "DataGrid1", "100%", "100%", true, false, false);
  $myDataGrid->disableFormTag();
  $intPageLimit = $myDataGrid->getPageLimit();
  $intPageNumber = $myDataGrid->getPageNumber();
  $arrSlip = getSlipReport($db, $strDateFrom, $strDateThru, $strKriteria);
  $myDataGrid->setCaption("Attendance Slip - $strDateFrom - $strDateThru");
  $myDataGrid->pageSortBy = "";
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "id", ['width' => 30, 'align' => 'center'], ['nowrap' => ''])
  );
  $myDataGrid->addColumnNumbering(
      new DataGrid_Column(
          "No",
          "",
          ['width' => 30],
          ['nowrap' => ''],
          false,
          false,
          "",
          "",
          "numeric",
          true,
          4,
          true,
          "nomor"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column("NIK", "nik", ['width' => 120], ['nowrap' => ''], true, true, "", "", "string", true, 30)
  );
  $myDataGrid->addColumn(
      new DataGrid_Column("Name", "employee_name", [], ['nowrap' => ''], true, true, "", "", "string", true, 30)
  );
  $myDataGrid->addColumn(
      new DataGrid_Column("Position", "position", [], ['nowrap' => ''], true, true, "", "", "string", true, 30)
  );
  $myDataGrid->addColumn(
      new DataGrid_Column("Functional", "functional", [], ['nowrap' => ''], true, true, "", "", "string", true, 30)
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          "Employee Status",
          "employee_status",
          ['width' => 120],
          ['nowrap' => ''],
          true,
          true,
          "",
          "printEmployeeStatus()",
          "string",
          true,
          30
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          "Join Date",
          "join_date",
          ['width' => 120],
          ['align' => 'center'],
          false,
          false,
          "",
          "",
          "string",
          true,
          12
      )
  );
  $myDataGrid->addSpecialButton(
      "btnSlip",
      "btnSlip",
      "submit",
      getWords("get slip"),
      "onClick=\"document.target = '_blank'\"",
      "getSlip()"
  );
  $myDataGrid->getRequest();
  $strCriteria = "";
  $myDataGrid->totalData = $totalData;
  $myDataGrid->bind($arrSlip);
  $dataGrid = $myDataGrid->render();
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('salary report slip page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
if ($bolPrint) {
  $strMainTemplate = getTemplate("employee_search_print.html");
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//end of main program
// format numeric
function printNumeric($params)
{
  extract($params);
  return number_format($value);
}

function getSlipReport($db, $strDateFromOri, $strDateThruOri, $strKriteria = "")
{
  global $_POST;
  $arrResult = [];
  global $intStart;
  global $intPageLimit;
  global $intPageNumber;
  global $totalData;
  global $strDataID;
  $intPage = $intPageNumber;
  $splitDateFrom = explode('/', $strDateFromOri);
  $strDateFrom = $splitDateFrom[2] . '-' . $splitDateFrom[0] . '-' . $splitDateFrom[1];
  $splitDateThru = explode('/', $strDateThruOri);
  $strDateThru = $splitDateThru[2] . '-' . $splitDateThru[0] . '-' . $splitDateThru[1];
  $intStart = (($intPage - 1) * $intPageLimit);
  $strSQL2 = "SELECT t0.*, \"join_date\", t2.\"position_name\", t3.functional_name
                FROM \"hrd_employee\" AS t0
                    LEFT JOIN \"hrd_position\" AS t2 ON t2.\"position_code\" = t0.\"position_code\"
                    LEFT JOIN \"hrd_functional\" AS t3 ON t3.\"functional_code\" = t0.\"functional_code\"
                WHERE  1 = 1 $strKriteria ORDER BY t0.\"employee_name\"";
  $res3 = $db->execute($strSQL2);
  while ($row3 = $db->fetchrow($res3)) {
    $totalData += 1;
  }
  if (!isset($_POST['btnExportXLS'])) {
    $strSQL2 .= "LIMIT $intPageLimit OFFSET $intStart";
  }
  $res4 = $db->execute($strSQL2);
  if ($db->fetchrow($res4) < 1) {
    $intPageNumber = 1;
    $intStart = 0;
  }
  $res2 = $db->execute($strSQL2);
  while ($row2 = $db->fetchrow($res2)) {
    $arrResult[$row2['id']]['id'] = $row2['id'];
    $arrResult[$row2['id']]['nik'] = $row2['employee_id'];
    $arrResult[$row2['id']]['position'] = $row2['position_name'];
    $arrResult[$row2['id']]['functional'] = $row2['functional_name'];
    $arrResult[$row2['id']]['branch_code'] = $row2['branch_code'];
    $arrResult[$row2['id']]['join_date'] = $row2['join_date'];
    $arrResult[$row2['id']]['employee_name'] = $row2['employee_name'];
    $arrResult[$row2['id']]['employee_status'] = $row2['employee_status'];
  }
  return $arrResult;
}

function formatNumerica($params)
{
  extract($params);
  //	 echo $value; die();
  return standardFormat($value);
  //    return standardFormat($value);
}

//fungsi untuk mengubah format jam ke hitungan menit
function getMinutes($hour_minutes)
{
  $hour = substr($hour_minutes, 0, 2) * 1 * 60;
  $minutes = substr($hour_minutes, 3, 2) * 1;
  return $hour + $minutes;
}

//fungsi untuk mengubah format menit ke format jam
function toHour($minutes)
{
  $hour = floor($minutes / 60);
  if (strlen($hour) == 1) {
    $hour = "0" . $hour;
  }
  $minutes = $minutes % 60;
  if (strlen($minutes) == 1) {
    $minutes = "0" . $minutes;
  }
  $hour_minutes = $hour . ":" . $minutes . ":00";
  return $hour_minutes;
}

// fungsi untuk melakukan proses slip gaji
function getSlip()
{
  global $myDataGrid;
  global $db;
  global $strDateFrom;
  global $strDateThru;
  $splitDateFrom = explode('/', $strDateFrom);
  $strDateFrom = $splitDateFrom[2] . '-' . $splitDateFrom[0] . '-' . $splitDateFrom[1];
  $splitDateThru = explode('/', $strDateThru);
  $strDateThru = $splitDateThru[2] . '-' . $splitDateThru[0] . '-' . $splitDateThru[1];
  include_once("../global/cls_date.php");
  $objDate = new clsCommonDate();
  $objEmp = new clsEmployees($db);
  $objEmp->loadData(
      "id, employee_id, employee_name, id_company, join_date, division_code, functional_code, branch_code"
  );
  // tampilkan header HTML dulu
  echo "
<html>
<head>
<title>Slip</title>
<meta http-equiv='Content-Type' content='application/vnd.ms-word; charset=iso-8859-1'>
<meta http-equiv='Content-Disposition' content='attachment; charset=iso-8859-1'>
<link href='../css/invosa.css' rel='stylesheet' type='text/css'>
</head>
<body marginheight=0 marginwidth=0 leftmargin=10 rightmargin=0 topmargin=0>
<table cellspacing=0 cellpadding=0 border=0 width='100%'>
    ";
  // inisialisasi
  $strThisPage = "
                      <span>&nbsp;";
  $strNewPage = "
                    <span style=\"page-break-before:always;\"></span>";
  $GLOBALS['strDate'] = date("d F Y");
  $GLOBALS['strPeriod'] = $objDate->getDateFormat($strDateFrom, "Ym") . " - From " . $objDate->getDateFormat(
          $strDateFrom,
          "d F Y"
      ) . " to " . $objDate->getDateFormat($strDateThru, "d F Y");
  $bolEven = true; // apakah genap
  $i = 0;
  // print_r($myDataGrid->checkboxes);die();
  foreach ($myDataGrid->checkboxes as $strValue) {
    $bolEven = !$bolEven;
    $i++;
    // inisialisasi detail
    $GLOBALS['strCompany'] = "";
    $GLOBALS['strEmployeeName'] = "";
    $GLOBALS['strEmployeeID'] = "";
    $GLOBALS['strDivision'] = "";
    $GLOBALS['strBranch'] = "";
    $GLOBALS['strPosition'] = "";
    $GLOBALS['strFunctional'] = "";
    $GLOBALS['strPage'] = "";
    $GLOBALS['strResult'] = "";
    $arrResult = [];
    $strSQL = "";
    // ambil ID employee
    $strIDEmployee = $strValue;
    $intCompany = $objEmp->getInfoByID($strIDEmployee, "id_company");
    $strDiv = $objEmp->getInfoByID($strIDEmployee, "division_code");
    $strBrch = $objEmp->getInfoByID($strIDEmployee, "branch_code");
    $strPos = $objEmp->getInfoByID($strIDEmployee, "position_code");
    $strFunc = $objEmp->getInfoByID($strIDEmployee, "functional_code");
    $GLOBALS['strCompany'] = printCompanyName($intCompany);
    $GLOBALS['strBranch'] = getBranchName($strBrch);
    $GLOBALS['strDivision'] = getDivisionName($strDiv);
    $GLOBALS['strPosition'] = getPositionName($strPos);
    $GLOBALS['strFunctional'] = getFunctionalNameChen($strFunc);
    $GLOBALS['strEmployeeID'] = $objEmp->getInfoByID($strIDEmployee, "employee_id");
    $GLOBALS['strEmployeeName'] = $objEmp->getInfoByID($strIDEmployee, "employee_name");
    $strSQL = "SELECT * FROM hrd_attendance WHERE id_employee = $strIDEmployee AND attendance_date BETWEEN '$strDateFrom' AND '$strDateThru'";
    $res = $db->execute($strSQL);
    while ($row = $db->fetchrow($res)) {
      $arrResult[$row['attendance_date']]['attendance_date'] = date("d F Y", strtotime($row['attendance_date']));
      $arrResult[$row['attendance_date']]['attendance_start'] = $row['attendance_start'];
      $arrResult[$row['attendance_date']]['attendance_finish'] = $row['attendance_finish'];
      $arrResult[$row['attendance_date']]['late_duration'] = $row['late_duration'];
      $arrResult[$row['attendance_date']]['early_duration'] = $row['early_duration'];
      $arrResult[$row['attendance_date']]['attendance_code'] = 'H';
    }
    $strSQL = "SELECT * FROM hrd_absence_detail WHERE id_employee = $strIDEmployee AND absence_date BETWEEN '$strDateFrom' AND '$strDateThru'";
    $res = $db->execute($strSQL);
    while ($row = $db->fetchrow($res)) {
      $arrResult[$row['absence_date']]['attendance_date'] = date("d F Y", strtotime($row['absence_date']));
      $arrResult[$row['absence_date']]['attendance_code'] = $row['absence_type'];
    }
    ksort($arrResult);
    // print_r($arrResult);die();
    foreach ($arrResult as $strDate => $strInfo) {
      // echo 'a';die();
      $GLOBALS['strResult'] .= "<tr>";
      $GLOBALS['strResult'] .= "<td>" . $strInfo['attendance_date'] . "</td>";
      $GLOBALS['strResult'] .= "<td>" . $strInfo['attendance_start'] . "</td>";
      $GLOBALS['strResult'] .= "<td>" . $strInfo['attendance_finish'] . "</td>";
      $GLOBALS['strResult'] .= "<td>" . $strInfo['attendance_code'] . "</td>";
      $GLOBALS['strResult'] .= "<td>" . $strInfo['late_duration'] . "</td>";
      $GLOBALS['strResult'] .= "<td>" . $strInfo['early_duration'] . "</td>";
      $GLOBALS['strResult'] .= "</tr>";
    }
    if ($bolEven) // genap
    {
      echo "<br><br><br><br><br><br><br>";
      echo "<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><table><tr><td height=60>&nbsp;</td></tr></table><span>";
      echo $strNewPage;
    } else if ($i == 1) {
      echo $strNewPage;
    } else // ganjil, page berikutnya
    {
      echo "<br><br><br><br><br><br><br>";
      echo "<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><table><tr><td height=60>&nbsp;</td></tr></table><span>";
      echo $strNewPage;
    }
    $tbsPage = new clsTinyButStrong;
    $tbsPage->LoadTemplate("templates/attendance_slip_template.html");
    $tbsPage->Show(TBS_OUTPUT);
  }
  // tampilkan footer HTML
  echo "

<table>
</body>
</html>

    ";
  unset($objEmp);
  exit();
}

?>
