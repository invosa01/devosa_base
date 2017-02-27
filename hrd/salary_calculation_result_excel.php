<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('salary_func.php');
include_once('../includes/tbsclass/plugin_excel/tbs_plugin_excel.php');
// periksa apakah sudah login atau belum, jika belum, harus login lagi
$dataPrivilege = getDataPrivileges(
    "salary_calculation.php",
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
$strDataDetail = "";
$strDataColumn = "";
$strHidden = "";
$strButtons = "";
$intTotalData = 0;
$strPaging = "";
$strDataID = "";
$strCalculationMenu = "";
$strDataDateFrom = "";
$strDataDateThru = "";
$strKriteria = " ";
$strWidth = "75px";
$strSpan1 = 0; // colspan untuk colum allowance
$strSpan2 = 7; // colspan untuk colum paging
//---- INISIALISASI ----------------------------------------------------
$strDataID = "";
$strPeriode = "";
$strKriteria = "";
$arrData = [];
$arrFields = [];
$arrEmp = [];
$arrEmpAllowance = [];
$arrEmpDeduction = [];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataID, &$intRows, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
{
  global $words;
  global $arrData;
  global $arrFields;
  global $arrEmp;
  global $strPeriode;
  global $arrEmpAllowance;
  global $arrEmpDeduction;
  global $strMonth;
  global $strYear;
  $strResult = "";
  if ($strDataID == "") {
    return "";
  } else {
    // cari info data
    $strSQL = "SELECT * FROM \"hrd_salary_master\" WHERE id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strDateFrom = $rowDb['date_from'];
      $strDateThru = $rowDb['date_thru'];
      $strPeriode = pgDateFormat($strDateFrom, "M d, y") . " TO " . pgDateFormat($strDateThru, "M d, y");
    } else {
      return "";
    }
  }
  $strMonth = pgDateFormat($strDateThru, "M");
  $strYear = pgDateFormat($strDateThru, "y");
  //Allowance types
  $strSQL = "SELECT * FROM \"hrd_salary_master_allowance\" WHERE \"id_salary_master\" = '$strDataID' AND is_default = 'f'";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrAllowance[$rowTmp['allowance_code']] = $rowTmp;
  }
  //Other Allowance
  $strSQL = "SELECT * FROM \"hrd_salary_allowance\" WHERE \"id_salary_master\" = '$strDataID' ";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrEmpAllowance[$rowTmp['id_employee']][$rowTmp['allowance_code']] = $rowTmp['amount'];
  }
  //Deduction types
  $strSQL = "SELECT * FROM \"hrd_salary_master_deduction\" WHERE \"id_salary_master\" = '$strDataID' AND is_default = 'f'";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrEmpAllowance[$rowTmp['deduction_code']] = $rowTmp;
  }
  //Other Deduction
  $strSQL = "SELECT * FROM \"hrd_salary_deduction\" WHERE \"id_salary_master\" = '$strDataID' ";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDeduction[$rowTmp['deduction_code']] = $rowTmp;
  }
  $strSQL = "SELECT t1.*, t2.employee_name, t2.join_date, ";
  $strSQL .= "CASE WHEN t2.gender = 0 THEN 'F' ELSE 'M' END AS gender_sign, t2.\"employee_name\" FROM ";
  $strSQL .= "hrd_salary_detail AS t1 LEFT JOIN hrd_employee AS t2 ON t1.\"id_employee\" = t2.id ";
  $strSQL .= "WHERE t1.id_salary_master = '$strDataID' $strKriteria ORDER BY $strOrder t2.employee_name ";
  $resDb = $db->execute($strSQL);
  $i = 0;
  while ($rowDb = $db->fetchrow($resDb)) {
    $i++;
    $rowDb['no'] = $i;
    $rowDb['overtime'] = $rowDb['ot1'] + $rowDb['ot2'] + $rowDb['ot3'] + $rowDb['ot4'];
    foreach ($arrAllowance as $strCode => $arrDetail) {
      $rowDb[$strCode] = (isset($arrEmpAllowance[$rowDb['id_employee']][$strCode])) ? $arrEmpAllowance[$rowDb['id_employee']][$strCode] : 0;
    }
    foreach ($arrDeduction as $strCode => $arrDetail) {
      $rowDb[$strCode] = (isset($arrEmpDeduction[$rowDb['id_employee']][$strCode])) ? $arrEmpDeduction[$rowDb['id_employee']][$strCode] : 0;
    }
    $arrData[] = $rowDb;
  }
} // showData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  if ($strDataID == "") {
    header("location:salary_calculation.php");
    exit();
  }
  /*
  // cari apakah ada master salry dengan ID tersebut
  $strSQL = "SELECT * FROM \"hrd_salary_master\" WHERE id = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strDateFrom = $rowDb['dateFrom'];
    $strDateThru = $rowDb['dateThru'];
    $strDataDateFrom = pgDateFormat($rowDb['dateFrom'],"d M Y");
    $strDataDateThru = pgDateFormat($rowDb['dateThru'],"d M Y");
    $intStatus = $rowDb['status'];
  } else {
    // gak ada, keluar
    header("location:salaryCalculation.php");
    exit();
  }
  */
  // ------ AMBIL DATA KRITERIA -------------------------
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
  (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
  (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
  (isset($_REQUEST['dataType'])) ? $strDataType = $_REQUEST['dataType'] : $strDataType = "0";
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  //$strKriteria = "";
  if ($strDataEmployee != "") {
    $strKriteria .= "AND t1.\"employee_id\" = '$strDataEmployee' ";
  }
  if ($strDataSection != "") {
    $strKriteria .= "AND t1.\"section_code\" = '$strDataSection' ";
  }
  if ($strDataDepartment != "") {
    $strKriteria .= "AND t1.\"department_code\" = '$strDataDepartment' ";
  }
  if ($strDataType == 1) {
    $strKriteria .= "AND t1.\"employee_status\" <> " . STATUS_OUTSOURCE . " ";
  } else if ($strDataType == 2) {
    $strKriteria .= "AND t1.\"employee_status\" = " . STATUS_OUTSOURCE . " ";
  }
  if ($bolCanView) {
    $strDataDetail = getData($db, $strDataID, $intTotalData, $strKriteria);
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
}
//print_r($arrData);die();
$tbsPage = new clsTinyButStrong;
$tbsPage->PlugIn(TBS_INSTALL, TBS_EXCEL);
$tbsPage->LoadTemplate(getTemplate("salary_report.xml"));
//  $tbsPage->MergeBlock('emp',$arrEmp);
$tbsPage->MergeBlock('data', $arrData);
// $tbsPage->MergeBlock('field',$arrFields);
//$tbsPage->MergeBlock('sfield2',$arrFields);
$tbsPage->PlugIn(TBS_EXCEL, TBS_EXCEL_FILENAME, 'salary_report.xls');
$tbsPage->Show();
?>