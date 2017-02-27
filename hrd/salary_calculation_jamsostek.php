<?php
$need_special_tbs = "390";
date_default_timezone_set('Asia/Jakarta');
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('salary_func.php');
include_once("cls_salary_calculation.php");
include_once("../global/cls_date.php");
include_once('../includes/tbsclass/plugins/tbs_plugin_opentbs.php');
$dataPrivilege = getDataPrivileges(
    "salary_calculation_hotel.php",
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
//---- INISIALISASI ----------------------------------------------------
$arrDataJamsostekNow = [];
$arrDataJamsostekJoin = [];
$arrDataJamsostekResign = [];
$arrJamsostekPerubahan = [];
$arrJamsostekJoin = [];
$arrJamsostekResign = [];
$arrJamsostekAll = [];
$arrJamsostekRecap = [];
$arrDataSalaryThisMonth = [];
$arrDataSalaryPrevMonth = [];
$arrDataSalaryDetailThisMonth = [];
$arrDataSalaryDetailPrevMonth = [];
$arrDataEmployee = [];
$arrDataCompany = [];
$strCompanyCode = getSetting("company_code");
$strCompanyAccount = getSetting("company_account");
$strReportPeriod = "";
//---- INISIALISASI ----------------------------------------------------
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  (isset($_REQUEST['sheet'])) ? $intSheetNumber = $_REQUEST['sheet'] : $intSheetNumber = "";
  if ($strDataID == "") {
    header("location:salary_calculation_hotel.php");
    exit();
  }
  // -----------------  AMBIL DATA ----------------------
  // ----------------- GET SALARY ID FOR PREV MONTH ------------------
  $strSQL = "SELECT * FROM hrd_salary_master WHERE id = $strDataID";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDataSalaryThisMonth = $rowDb;
  }
  $strReportPeriod = date("F Y", strtotime($arrDataSalaryThisMonth['salary_date']));
  $arrDataSalaryPrevMonth['salary_date'] = date(
      "Y-m-d",
      strtotime("-1 month", strtotime($arrDataSalaryThisMonth['salary_date']))
  );
  $arrDataSalaryPrevMonth['salary_date'] = explode("-", $arrDataSalaryPrevMonth['salary_date']);
  $strSQL = "SELECT * FROM hrd_salary_master
    WHERE EXTRACT(YEAR FROM salary_date) = '" . $arrDataSalaryPrevMonth['salary_date'][0] . "' AND EXTRACT(MONTH FROM salary_date) = '" . $arrDataSalaryPrevMonth['salary_date'][1] . "'
    AND status=" . REQUEST_STATUS_APPROVED . " and id_company=" . $arrDataSalaryThisMonth['id_company'];
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDataSalaryPrevMonth = $rowDb;
  }
  // ----------------- END GET SALARY DATA PREV MONTH -------------------
  // ----------------- GET COMPANY INFO -------------
  $strSQL = "SELECT * FROM hrd_company WHERE id = " . $arrDataSalaryThisMonth['id_company'];
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDataCompany = $rowDb;
  }
  // ----------------- END GET COMPANY INFO ------------
  // ----------------- GET SALARY DATA DETAIL & EMPLOYEE DATA DETAIL FOR THIS MONTH & PREV MONTH --------------------
  $strSQL = "SELECT * FROM hrd_salary_detail WHERE id_salary_master = " . $arrDataSalaryThisMonth['id'];
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDataSalaryDetailThisMonth[$rowDb['id_employee']] = $rowDb;
  }
  $arrJamsostekRecap['total_base_jamsostek_prev'] = 0;
  $arrJamsostekRecap['total_employee_prev'] = 0;
  $strSQL = "SELECT * FROM hrd_salary_detail WHERE id_salary_master = " . $arrDataSalaryPrevMonth['id'];
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDataSalaryDetailPrevMonth[$rowDb['id_employee']] = $rowDb;
    $arrJamsostekRecap['total_base_jamsostek_prev'] += $rowDb['base_jamsostek'];
    $arrJamsostekRecap['total_employee_prev'] += 1;
  }
  $strSQL = "SELECT * FROM hrd_employee";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDataEmployee[$rowDb['id']] = $rowDb;
  }
  // ----------------- END GET SALARY DATA DETAIL & EMPLOYEE DATA DETAIL FOR THIS MONTH & PREV MONTH --------------------
  // ----------------- PROCESS CALCULATION FOR JAMSOSTEK REPORT --------------------------------
  foreach ($arrDataEmployee as $strEmployeeID => $arrEmployeeInfo) {
    if (isset($arrDataSalaryDetailPrevMonth[$strEmployeeID]) && isset($arrDataSalaryDetailThisMonth[$strEmployeeID])) {
      $arrDataJamsostekNow[$strEmployeeID]['salary_detail_prev'] = $arrDataSalaryDetailPrevMonth[$strEmployeeID];
      $arrDataJamsostekNow[$strEmployeeID]['salary_detail_now'] = $arrDataSalaryDetailThisMonth[$strEmployeeID];
      $arrDataJamsostekNow[$strEmployeeID]['employee_detail'] = $arrDataEmployee[$strEmployeeID];
    } else if (isset($arrDataSalaryDetailPrevMonth[$strEmployeeID]) && !isset($arrDataSalaryDetailThisMonth[$strEmployeeID])) {
      $arrDataJamsostekResign[$strEmployeeID]['salary_detail_prev'] = $arrDataSalaryDetailPrevMonth[$strEmployeeID];
      $arrDataJamsostekResign[$strEmployeeID]['employee_detail'] = $arrDataEmployee[$strEmployeeID];
    } else if (!isset($arrDataSalaryDetailPrevMonth[$strEmployeeID]) && isset($arrDataSalaryDetailThisMonth[$strEmployeeID])) {
      $arrDataJamsostekJoin[$strEmployeeID]['salary_detail_now'] = $arrDataSalaryDetailThisMonth[$strEmployeeID];
      $arrDataJamsostekJoin[$strEmployeeID]['employee_detail'] = $arrDataEmployee[$strEmployeeID];
    }
  }
  unset($arrDataSalaryThisMonth);
  unset($arrDataSalaryPrevMonth);
  unset($arrDataSalaryDetailPrevMonth);
  // ---------------- CARI DATA PERUBAHAN SALARY --------------
  $arrJamsostekRecap['total_base_jamsostek_diff'] = 0;
  $arrJamsostekRecap['total_employee_diff'] = 0;
  foreach ($arrDataJamsostekNow as $strEmployeeID => $arrEmployeeInfo) {
    $intSalaryChange = $arrEmployeeInfo['salary_detail_now']['base_jamsostek'] - $arrEmployeeInfo['salary_detail_prev']['base_jamsostek'];
    if ($intSalaryChange != 0) {
      $arrJamsostekPerubahan[$strEmployeeID]['base_jamsostek_prev'] = $arrEmployeeInfo['salary_detail_prev']['base_jamsostek'];
      $arrJamsostekPerubahan[$strEmployeeID]['base_jamsostek_now'] = $arrEmployeeInfo['salary_detail_now']['base_jamsostek'];
      $arrJamsostekPerubahan[$strEmployeeID]['gender'] = ($arrEmployeeInfo['employee_detail']['gender'] == 1) ? 'L' : 'P';
      $arrJamsostekPerubahan[$strEmployeeID]['jamsostek_no'] = $arrEmployeeInfo['employee_detail']['jamsostek_no'];
      $arrJamsostekPerubahan[$strEmployeeID]['id_card'] = $arrEmployeeInfo['employee_detail']['id_card'];
      $arrJamsostekPerubahan[$strEmployeeID]['name'] = $arrEmployeeInfo['employee_detail']['employee_name'];
      $arrJamsostekPerubahan[$strEmployeeID]['employee_id'] = $arrEmployeeInfo['employee_detail']['employee_id'];
      $arrJamsostekPerubahan[$strEmployeeID]['birthdate'] = ($arrEmployeeInfo['employee_detail']['birthdate'] != null) ? date(
          "m-d-Y",
          strtotime($arrEmployeeInfo['employee_detail']['birthdate'])
      ) : "00-00-0000";
      $arrJamsostekRecap['total_base_jamsostek_diff'] += $intSalaryChange;
      $arrJamsostekRecap['total_employee_diff'] += 1;
    }
  }
  unset($arrDataJamsostekNow);
  // ---------------- END CARI DATA PERUBAHAN SALARY --------------
  // ---------------- CARI DATA JOIN --------------
  $arrJamsostekRecap['total_base_jamsostek_join'] = 0;
  $arrJamsostekRecap['total_employee_join'] = 0;
  foreach ($arrDataJamsostekJoin as $strEmployeeID => $arrEmployeeInfo) {
    $arrJamsostekJoin[$strEmployeeID]['base_jamsostek_now'] = $arrEmployeeInfo['salary_detail_now']['base_jamsostek'];
    $arrJamsostekJoin[$strEmployeeID]['gender'] = ($arrEmployeeInfo['employee_detail']['gender'] == 1) ? 'L' : 'P';
    $arrJamsostekJoin[$strEmployeeID]['marital_status'] = ($arrEmployeeInfo['employee_detail']['marital_status'] == 1) ? 'KAWIN' : 'LAJANG';
    $arrJamsostekJoin[$strEmployeeID]['jamsostek_no'] = $arrEmployeeInfo['employee_detail']['jamsostek_no'];
    $arrJamsostekJoin[$strEmployeeID]['id_card'] = $arrEmployeeInfo['employee_detail']['id_card'];
    $arrJamsostekJoin[$strEmployeeID]['name'] = $arrEmployeeInfo['employee_detail']['employee_name'];
    $arrJamsostekJoin[$strEmployeeID]['employee_id'] = $arrEmployeeInfo['employee_detail']['employee_id'];
    $arrJamsostekJoin[$strEmployeeID]['birthdate'] = ($arrEmployeeInfo['employee_detail']['birthdate'] != null) ? date(
        "m-d-Y",
        strtotime($arrEmployeeInfo['employee_detail']['birthdate'])
    ) : "00-00-0000";
    $arrJamsostekJoin[$strEmployeeID]['birthplace'] = $arrEmployeeInfo['employee_detail']['birthplace'];
    $arrJamsostekJoin[$strEmployeeID]['primary_address'] = $arrEmployeeInfo['employee_detail']['primary_address'];
    $arrJamsostekJoin[$strEmployeeID]['maiden_mother_name'] = $arrEmployeeInfo['employee_detail']['maiden_mother_name'];
    $arrJamsostekRecap['total_base_jamsostek_join'] += $arrJamsostekJoin[$strEmployeeID]['base_jamsostek_now'];
    $arrJamsostekRecap['total_employee_join'] += 1;
  }
  unset($arrDataJamsostekJoin);
  // ---------------- END CARI DATA JOIN --------------
  // ---------------- CARI DATA RESIGN --------------
  $arrJamsostekRecap['total_base_jamsostek_resign'] = 0;
  $arrJamsostekRecap['total_employee_resign'] = 0;
  foreach ($arrDataJamsostekResign as $strEmployeeID => $arrEmployeeInfo) {
    $arrJamsostekResign[$strEmployeeID]['base_jamsostek_now'] = $arrEmployeeInfo['salary_detail_now']['base_jamsostek'];
    $arrJamsostekResign[$strEmployeeID]['gender'] = ($arrEmployeeInfo['employee_detail']['gender'] == 1) ? 'L' : 'P';
    $arrJamsostekResign[$strEmployeeID]['marital_status'] = ($arrEmployeeInfo['employee_detail']['marital_status'] == 1) ? 'KAWIN' : 'LAJANG';
    $arrJamsostekResign[$strEmployeeID]['jamsostek_no'] = $arrEmployeeInfo['employee_detail']['jamsostek_no'];
    $arrJamsostekResign[$strEmployeeID]['id_card'] = $arrEmployeeInfo['employee_detail']['id_card'];
    $arrJamsostekResign[$strEmployeeID]['name'] = $arrEmployeeInfo['employee_detail']['employee_name'];
    $arrJamsostekResign[$strEmployeeID]['employee_id'] = $arrEmployeeInfo['employee_detail']['employee_id'];
    $arrJamsostekResign[$strEmployeeID]['birthdate'] = ($arrEmployeeInfo['employee_detail']['birthdate'] != null) ? date(
        "m-d-Y",
        strtotime($arrEmployeeInfo['employee_detail']['birthdate'])
    ) : "00-00-0000";
    $arrJamsostekResign[$strEmployeeID]['birthplace'] = $arrEmployeeInfo['employee_detail']['birthplace'];
    $arrJamsostekResign[$strEmployeeID]['primary_address'] = $arrEmployeeInfo['employee_detail']['primary_address'];
    $arrJamsostekResign[$strEmployeeID]['maiden_mother_name'] = $arrEmployeeInfo['employee_detail']['maiden_mother_name'];
    $arrJamsostekRecap['total_base_jamsostek_resign'] += $arrJamsostekJoin[$strEmployeeID]['base_jamsostek_now'];
    $arrJamsostekRecap['total_employee_resign'] += 1;
  }
  unset($arrDataJamsostekResign);
  // ---------------- END CARI DATA RESIGN --------------
  // ---------------- CARI DETAIL PAID THIS MONTH --------------
  foreach ($arrDataSalaryDetailThisMonth as $strEmployeeID => $arrSalaryInfo) {
    $arrJamsostekAll[$strEmployeeID]['base_jamsostek_now'] = $arrSalaryInfo['base_jamsostek'];
    $arrJamsostekAll[$strEmployeeID]['jamsostek_allowance'] = $arrSalaryInfo['jamsostek_allowance'];
    $arrJamsostekAll[$strEmployeeID]['jkk_allowance'] = $arrSalaryInfo['jkk_allowance'];
    $arrJamsostekAll[$strEmployeeID]['jkm_allowance'] = $arrSalaryInfo['jkm_allowance'];
    $arrJamsostekAll[$strEmployeeID]['jamsostek_deduction'] = $arrSalaryInfo['jamsostek_deduction'];
    $arrJamsostekAll[$strEmployeeID]['jamsostek_no'] = $arrDataEmployee[$strEmployeeID]['jamsostek_no'];
    $arrJamsostekAll[$strEmployeeID]['id_card'] = $arrDataEmployee[$strEmployeeID]['id_card'];
    $arrJamsostekAll[$strEmployeeID]['name'] = $arrDataEmployee[$strEmployeeID]['employee_name'];
    $arrJamsostekAll[$strEmployeeID]['employee_id'] = $arrDataEmployee[$strEmployeeID]['employee_id'];
    $arrJamsostekAll[$strEmployeeID]['birthdate'] = ($arrDataEmployee[$strEmployeeID]['birthdate'] != null) ? date(
        "m-d-Y",
        strtotime($arrDataEmployee[$strEmployeeID]['birthdate'])
    ) : "00-00-0000";
  }
  unset($arrDataJamsostekResign);
  unset($arrDataSalaryDetailThisMonth);
  unset($arrDataEmployee);
  // ---------------- CARI DETAIL PAID THIS MONTH --------------
  // ----------------- END PROCESS CALCULATION FOR JAMSOSTEK REPORT -----------------------------
  if ($bolCanView) {
    //      print_r($arrJamsostekAll);die();
    $tbsPage = new clsTinyButStrong;
    $tbsPage->PlugIn(TBS_INSTALL, OPENTBS_PLUGIN);
    $tbsPage->LoadTemplate(getTemplate("jamsostek_" . $intSheetNumber . ".xlsx"));
    $tbsPage->MergeBlock('all', $arrJamsostekAll);
    $tbsPage->MergeBlock('join', $arrJamsostekJoin);
    $tbsPage->MergeBlock('resign', $arrJamsostekResign);
    $tbsPage->MergeBlock('perubahan', $arrJamsostekPerubahan);
    $output_file_name = 'bpjs_ketenagakerjaan_' . $strCompanyCode . '_' . $strReportPeriod . '-' . $intSheetNumber . '.xlsx';
    $tbsPage->Show(OPENTBS_DOWNLOAD, $output_file_name);
  } else {
    showError("view_denied");
  }
}
?>