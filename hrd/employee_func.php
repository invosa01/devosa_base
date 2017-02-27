<?php
/*
 Fungsi - fungsi terkait dengan employee

 Author: Yudi K
 Versi 1:
 Update: 2006-02-03
*/
//include("global.php");
// fungsi untuk menghitung jatah cuti karyawan
// hasil berupa array
function getEmployeeLeaveData(
    $db,
    $strYear,
    $stremployee_id = "",
    $strIDEmployee = "",
    $strSection = "",
    $strDepartment = ""
) {
} // getEmployeeLeaveDepartment
// mengambil data id dari dept head
function getDeptHeadID($db, $strDepartment)
{
  $intResult = -1;
  $strDeptHead = getSetting("department_head");
  if ($strDeptHead != "" && $strDepartment != "") {
    $strSQL = "SELECT id FROM hrd_employee WHERE department_code = '$strDepartment' ";
    $strSQL .= "AND position_code = '$strDeptHead' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $intResult = $rowDb['id'];
    }
  }
  return $intResult;
}// getDeptHeadID
// fungsi untuk menghitung jatah rawat inap karyawan
// hasil berupa array
function getEmployeeMedicalPlatformData(
    $db,
    $strYear,
    $stremployee_id = "",
    $strIDEmployee = "",
    $strSection = "",
    $strDepartment = ""
) {
  $arrResult = [];
  $arrPlatform = [];
  // ambil data platform
  $strSQL = "SELECT * FROM hrd_medical_platform ";
  $strSQL .= "WHERE flag = 0 ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrPlatform[$rowDb['grade_code']][$rowDb['family_status_code']] = $rowDb['amount'];
  }
  $fltPlatformOS = getSetting("outsource_medical"); // platform untuk outsource
  if (!is_numeric($fltPlatformOS)) {
    $fltPlatformOS = 0;
  }
  $strSQL = "SELECT * FROM hrd_employee WHERE flag = 0 AND active = 1 ";
  if ($stremployee_id != "") {
    $strSQL .= "AND employee_id = '$stremployee_id' ";
  }
  if ($strIDEmployee != "") {
    $strSQL .= "AND id = '$strIDEmployee' ";
  }
  if ($strSection != "") {
    $strSQL .= "AND section_code = '$strSection' ";
  }
  if ($strDepartment != "") {
    $strSQL .= "AND department_code = '$strDepartment' ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->execute($strSQL)) {
    if ($rowDb['employee_status'] == 2) { // outsource
      $fltPlatform = $fltPlatformOS;
    } else {
      $fltPlatform = (isset($arrPlatform[$rowDb['grade_code']][$rowDb['family_status_code']])) ? $arrPlatform[$rowDb['grade_code']][$rowDb['family_status_code']] : 0;
    }
    $fltClaim = 0;
    // cari data medis yang dipakai
  }
  return $arrResult;
} // getEmployeeLeaveDepartment
?>
