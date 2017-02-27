<?php
include_once('../global/session.php');
include_once('../global/employee_function.php');
include_once('global.php');
include_once('form_object.php');
include_once('../includes/tbsclass/plugin_excel/tbs_plugin_excel.php');
// periksa apakah sudah login atau belum, jika belum, harus login lagi
$dataPrivilege = getDataPrivileges(
    "attendance_edit_by_employee.php",
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
function getData($db, $strDataDateFrom, $strDataDateThru, $strDataEmployee = "")
{
  global $words;
  global $arrSummary;
  global $strPeriode;
  global $bolAll;
  $arrSummary = [];
  $strPeriode = pgDateFormat($strDataDateFrom, "M d, y") . " TO " . pgDateFormat($strDataDateThru, "M d, y");
  $strSQL = "SELECT id, employee_id, employee_name ";
  $strSQL .= "FROM hrd_employee  WHERE 1=1 ";
  if ($strDataEmployee != "") {
    $strSQL .= "AND employee_id = '$strDataEmployee' ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrEmployee[$rowDb['id']] = $rowDb;
  }
  $strSQL = "SELECT id_employee, attendance_start, attendance_finish, attendance_date, ";
  $strSQL .= "normal_start, normal_finish, employee_id, employee_name, code_shift_type, ";
  $strSQL .= "overtime_start, overtime_finish, overtime, overtime_calculated ";
  $strSQL .= "FROM hrd_attendance AS t0 LEFT JOIN hrd_employee AS t1 ";
  $strSQL .= "ON t0.id_employee = t1.id ";
  $strSQL .= "WHERE attendance_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
  if ($strDataEmployee != "" && $strDataEmployee != "all") {
    $strSQL .= "AND employee_id = '$strDataEmployee' ";
  }
  $strSQL .= "ORDER BY employee_id, attendance_date ";
  $arrData = [];
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrData[$rowDb['id_employee']][$rowDb['attendance_date']] = $rowDb;
  }
  foreach ($arrData as $strIDEmployee => $arrDetail) {
    $strDataDate = $strDataDateFrom;
    while (dateCompare($strDataDate, $strDataDateThru) <= 0) {
      if (isset($arrDetail[$strDataDate])) {
        $arrSummary[] = $arrDetail[$strDataDate];
      } else {
        $arrSummary[] = [
            "attendance_date"     => "$strDataDate",
            "employee_id"         => $arrEmployee[$strIDEmployee]['employee_id'],
            "employee_name"       => $arrEmployee[$strIDEmployee]['employee_name'],
            "attendance_start"    => "",
            "attendance_finish"   => "",
            "normal_start"        => "",
            "normal_finish"       => "",
            "overtime_start"      => "",
            "overtime_finish"     => "",
            "overtime"            => "",
            "overtime_calculated" => "",
            "code_shift_type"     => ""
        ];
      }
      $strDataDate = getNextDate($strDataDate);
    }
    $arrSummary[] = [
        "attendance_date"     => "",
        "employee_id"         => "",
        "employee_name"       => "",
        "attendance_start"    => "",
        "attendance_finish"   => "",
        "normal_start"        => "",
        "overtime_start"      => "",
        "overtime_finish"     => "",
        "overtime"            => "",
        "overtime_calculated" => "",
        "normal_finish"       => "",
        "code_shift_type"     => ""
    ];
  }
} // showData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
  // ------ AMBIL DATA KRITERIA -------------------------
  $strDataEmployee = (isset($_REQUEST['dataEmployee'])) ? $_REQUEST['dataEmployee'] : "";
  $strDataDateFrom = (isset($_REQUEST['dataDateFrom'])) ? $_REQUEST['dataDateFrom'] : "";
  $strDataDateThru = (isset($_REQUEST['dataDateThru'])) ? $_REQUEST['dataDateThru'] : "";
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  if ($bolCanView) {
    if ($strDataEmployee == "all") {
      getData($db, $strDataDateFrom, $strDataDateThru);
    } else {
      getData($db, $strDataDateFrom, $strDataDateThru, $strDataEmployee);
    }
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
}
//print_r($arrSummary); die();
$tbsPage = new clsTinyButStrong;
$tbsPage->PlugIn(TBS_INSTALL, TBS_EXCEL);
$tbsPage->LoadTemplate(getTemplate("attendance_report_by_employee.xml"));
$tbsPage->noErr = true;
$tbsPage->MergeBlock('data', $arrSummary);
// $tbsPage->MergeBlock('field',$arrFields);
//$tbsPage->MergeBlock('sfield2',$arrFields);
$tbsPage->PlugIn(TBS_EXCEL, TBS_EXCEL_FILENAME, $strPeriode . '.xls');
$tbsPage->Show();
?>
