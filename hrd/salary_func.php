<?php
include_once('global.php');
// Fungsi-fungsi untuk menghitung gaji
// Author: Yudi K.
/*  PERHITUNGAN GAJI SECARA UMUM DILAKUKAN DI CLS_SALARY_CALCULATION
*/
// daftar link untk perhitungan gaji
$arrLink = [
    1 => "salary_calculation_basic.php",
    2 => "salary_calculation_attendance.php",
    3 => "salary_calculation_overtime.php",
    //4 => "salaryCalculationAllowance.php",
    4 => "salary_calculation_deduction.php",
    5 => "salary_calculation_result.php",
];
// membuat submenu perhitungan gaji
// perlu input ID dari salary master
// intCurrent = halaman yang sedang dibuka, intStatus = status calculation
function getCalculationMenu($strDataID, $intCurrent = 1, $intStatus = 0)
{
  global $ARRAY_SALARY_CALCULATION;
  global $words;
  global $arrLink;
  $strResult = "";
  $intTotal = count($ARRAY_SALARY_CALCULATION);
  if ($intTotal > 2) { // start dan finish tidak dianggap
    for ($i = 1; $i < ($intTotal - 1); $i++) {
      if ($intCurrent == $i) { // saat ini, jdi tidak ada link
        $strResult .= " <b>" . $words[$ARRAY_SALARY_CALCULATION[$i]] . "</b>  |";
      } else {
        if ($i <= ($intStatus + 1)) { // berikan link
          $strResult .= " <b><a href=\"" . $arrLink[$i] . "?dataID=$strDataID\">" . $words[$ARRAY_SALARY_CALCULATION[$i]] . "</a></b>  |";
        } else {
          $strResult .= " " . $words[$ARRAY_SALARY_CALCULATION[$i]] . "  |";
        }
      }
    }
    // hilangkan satu karakter terakhir
    if ($strResult != "") {
      $strResult = substr($strResult, 0, strlen($strResult) - 1);
    }
  }
  return $strResult;
}//getCalculationMenu
// membuat daftar link informasi proses
// perlu input ID dari salary master
// intCurrent = halaman yang sedang dibuka, intStatus = status calculation
function getSalaryProccess($strDataID, $intStatus = 0)
{
  global $ARRAY_SALARY_CALCULATION;
  global $words;
  global $arrLink;
  $strResult = "";
  $intTotal = count($ARRAY_SALARY_CALCULATION);
  $intTotalLink = count($arrLink);
  if ($intTotal > 2) { // start dan finish tidak dianggap
    for ($i = 1; $i <= $intStatus; $i++) {
      if ($i < $intTotalLink) {
        $strResult .= " <a href=\"" . $arrLink[$i] . "?dataID=$strDataID\" title=\"" . $words[$ARRAY_SALARY_CALCULATION[$i]] . "\">[$i]</a>  |";
      } else if ($i < $intTotal) {
        $strResult .= " <a href=\"javascript:void(0)\" title=\"" . $words[$ARRAY_SALARY_CALCULATION[$i]] . "\">[$i]</a>  |";
      }
    }
    // hilangkan satu karakter terakhir
    if ($strResult != "") {
      $strResult = substr($strResult, 0, strlen($strResult) - 1);
    }
  }
  return $strResult;
}//getCalculationMenu
function getFixAllowance($db, $strMultival = "", $strDaily = "", $strAllowance = "allowance")
{
  $arrResult = [];
  if ($strDaily != "") {
    $strDaily = "AND value = '$strDaily'";
  }
  // cari settting salary yang kira-kira terkait dengan overtime
  $arrSetting = [];
  $strSQL = "SELECT * FROM all_setting WHERE code like '%" . $strAllowance . "_daily' ";
  $strSQL .= "$strDaily ORDER BY value DESC";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $strCode = str_replace("_daily", "", $rowDb['code']);
    if (getSetting($strCode . "_active") == 't') {
      if ($strMultival != "") {
        if (getSetting($strCode . "_multival") == $strMultival) {
          $arrResult[$strCode] = str_replace(" ", "", getSetting($strCode . "_name"));
        }
      } else {
        $arrResult[$strCode] = str_replace(" ", "", getSetting($strCode . "_name"));
      }
    }
  }
  return $arrResult;
}

?>