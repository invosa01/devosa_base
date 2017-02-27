<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
include_once('salary_func.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
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
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strReportName = strtoupper(getWords("overtime report"));
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strStyle = "";
$strWordsIncludeDepartment = getWords("show department");
$strWordsIncludeSection = getWords("show section");
$strWordsIncludeEmployee = getWords("show employee");
$strWordGetOvertimeSlip = getWords("get overtime slip");
$strWordsDataEntry = getWords("data entry");
$strWordsOvertimeListEmployee = getWords("overtime list employee");
$strWordsOvertimeListGroup = getWords("overtime list by group");
$strWordsOvertimeReport = getWords("overtime report");
//----------------------------------------------------------------------
// fungsi buat nentuin apakah standard format atau gak
function cekStandardFormat($strText, $bolDec = true, $intDec = 2)
{
  global $_REQUEST;
  if (isset($_REQUEST['btnExcel'])) // untuk tampil di excel
  {
    $strResult = $strText;
  } else {
    $strResult = standardFormat($strText, $bolDec, $intDec) . "&nbsp;";
  }
  return $strResult;
}

//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $ARRAY_OT_STATUS;
  global $bolDataCost;
  $intRows = 0;
  $strResult = "";
  $arrResult = [];
  $fltHourPerMonth = getSetting("hour_per_month");
  if (!is_numeric($fltHourPerMonth)) {
    $fltHourPerMonth = 0;
  }
  $arrOTType = getOvertimeTypeValue($db);
  // cek validasi
  for ($i = 1; $i <= 4; $i++) {
    if (!is_numeric($arrOTType[$i])) {
      $arrOTType[$i] = 1;
    } // default
  }
  $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable >\n";
  // bikin header table
  $strDefaultWidth = "width=40";
  $strColSpan = ($bolDataCost) ? "colspan=2" : "";
  /*
      $strResult .= " <tr align=center class=tableHeader>\n";
      $strResult .= "  <td nowrap>&nbsp;" .strtoupper(getWords("no"))."</td>\n";
      $strResult .= "  <td nowrap>&nbsp;" .strtoupper(getWords("employee id"))."</td>\n";
      $strResult .= "  <td nowrap>&nbsp;" .strtoupper(getWords("employee name"))."</td>\n";
      if ($bolDataCost) {
        $strResult .= "  <td nowrap>&nbsp;" .strtoupper(getWords("basic salary"))."</td>\n";
      }
      $strResult .= "  <td nowrap $strDefaultWidth $strColSpan>&nbsp;" .strtoupper(getWords("ot 1"))."</td>\n";
      $strResult .= "  <td nowrap $strDefaultWidth $strColSpan>&nbsp;" .strtoupper(getWords("ot 2"))."</td>\n";
      $strResult .= "  <td nowrap $strDefaultWidth $strColSpan>&nbsp;" .strtoupper(getWords("ot 3"))."</td>\n";
      $strResult .= "  <td nowrap $strDefaultWidth $strColSpan>&nbsp;" .strtoupper(getWords("ot 4"))."</td>\n";
      $strResult .= "  <td nowrap $strDefaultWidth $strColSpan>&nbsp;" .strtoupper(getWords("total"))."</td>\n";
      $strResult .= " </tr>\n";
  */
  // ambil data OT ==> activity.php ------------------------------------------------------------------------
  //dari hrd_overtime
  //$arrEmployeeOT = getEmployeeOvertime($db,$strDataDateFrom, $strDataDateThru);
  //dari hrd_overtime_application
  $arrEmployeeOT = getEmployeeOvertimeApplication($db, $strDataDateFrom, $strDataDateThru);
  //$arrEmployeeOTRecap = getEmployeeAttendanceRecap($db,$strDataDateFrom,$strDataDateThru); // yang recap
  //--------------------------------------------------------------------------------------------------------
  // ambil dulu data employee
  $arrEmployee = [];
  $i = 0;
  $strSQL = "SELECT t1.id, t1.employee_id, t1.employee_name, t2.basic_salary, ";
  $strSQL .= "t1.onsite, t1.department_code, t1.section_code FROM hrd_employee AS t1 ";
  $strSQL .= "LEFT JOIN hrd_employee_basic_salary AS t2 ON t1.id = t2.id_employee ";
  $strSQL .= "WHERE t1.active=1 AND t1.flag=0  $strKriteria ORDER BY $strOrder employee_id ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strID = $rowDb['id'];
    $arrResult[$intRows]['id'] = $rowDb['id'];
    $arrResult[$intRows]['employee_id'] = $rowDb['employee_id'];
    $arrResult[$intRows]['employee_name'] = $rowDb['employee_name'];
    $arrResult[$intRows]['department_code'] = $rowDb['department_code'];
    $arrResult[$intRows]['section_code'] = $rowDb['section_code'];
    //    $arrResult[$intRows]['position_code'] = $rowDb['position_code'];
    $arrResult[$intRows]['basic_salary'] = $rowDb['basic_salary'];
    $fltBasic = ($fltHourPerMonth == 0 || $rowDb['basic_salary'] == "") ? 0 : ($rowDb['basic_salary'] / $fltHourPerMonth);
    if ($rowDb['onsite'] == 't') {
      $fltOT1 = (isset($arrEmployeeOTRecap[$strID]['totalL1'])) ? $arrEmployeeOTRecap[$strID]['totalL1'] : 0;
      $fltOT2 = (isset($arrEmployeeOTRecap[$strID]['totalL2'])) ? $arrEmployeeOTRecap[$strID]['totalL2'] : 0;
      $fltOT3 = (isset($arrEmployeeOTRecap[$strID]['totalL3'])) ? $arrEmployeeOTRecap[$strID]['totalL3'] : 0;
      $fltOT4 = (isset($arrEmployeeOTRecap[$strID]['totalL4'])) ? $arrEmployeeOTRecap[$strID]['totalL4'] : 0;
    } else {
      $fltOT1 = (isset($arrEmployeeOT[$strID]['total1'])) ? $arrEmployeeOT[$strID]['total1'] : 0;
      $fltOT2 = (isset($arrEmployeeOT[$strID]['total2'])) ? $arrEmployeeOT[$strID]['total2'] : 0;
      $fltOT3 = (isset($arrEmployeeOT[$strID]['total3'])) ? $arrEmployeeOT[$strID]['total3'] : 0;
      $fltOT4 = (isset($arrEmployeeOT[$strID]['total4'])) ? $arrEmployeeOT[$strID]['total4'] : 0;
    }
    $fltTotal = $fltOT1 + $fltOT2 + $fltOT3 + $fltOT4;
    $arrResult[$intRows]['ot1'] = $fltOT1;
    $arrResult[$intRows]['ot2'] = $fltOT2;
    $arrResult[$intRows]['ot3'] = $fltOT3;
    $arrResult[$intRows]['ot4'] = $fltOT4;
    $arrResult[$intRows]['total'] = $fltTotal;
    if ($bolDataCost) {
      // hitung jumlah biaya
      $fltAmount1 = ($fltOT1 / 60) * $arrOTType[1] * $fltBasic;
      $fltAmount2 = ($fltOT2 / 60) * $arrOTType[2] * $fltBasic;
      $fltAmount3 = ($fltOT3 / 60) * $arrOTType[3] * $fltBasic;
      $fltAmount4 = ($fltOT4 / 60) * $arrOTType[4] * $fltBasic;
      $fltAmountTotal = $fltAmount1 + $fltAmount2 + $fltAmount3 + $fltAmount4;
      $arrResult[$intRows]['amount1'] = $fltAmount1;
      $arrResult[$intRows]['amount2'] = $fltAmount2;
      $arrResult[$intRows]['amount3'] = $fltAmount3;
      $arrResult[$intRows]['amount4'] = $fltAmount4;
      $arrResult[$intRows]['amountTotal'] = $fltAmountTotal;
    }
    /*
          $strResult .= " <tr valign=top>\n";
          $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>\n";
          $strResult .= "  <td nowrap >&nbsp;" .$rowDb['employee_id']."</td>\n";
          $strResult .= "  <td nowrap >&nbsp;" .$rowDb['employee_name']."</td>\n";
          if ($bolDataCost) {
            $strResult .= "  <td nowrap align=right $strDefaultWidth>" .standardFormat($rowDb['basic_salary'],true,2)."&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right $strDefaultWidth>" .standardFormat($fltOT1/60,true)."&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right $strDefaultWidth>" .standardFormat($fltAmount1,true,0)."&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right $strDefaultWidth>" .standardFormat($fltOT2/60,true)."&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right $strDefaultWidth>" .standardFormat($fltAmount2,true,0)."&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right $strDefaultWidth>" .standardFormat($fltOT3/60,true)."&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right $strDefaultWidth>" .standardFormat($fltAmount3,true,0)."&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right $strDefaultWidth>" .standardFormat($fltOT4/60,true)."&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right $strDefaultWidth>" .standardFormat($fltAmount4,true,0)."&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right $strDefaultWidth><strong>" .standardFormat($fltTotal/60,true)."</strong>&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right $strDefaultWidth><strong>" .standardFormat($fltAmountTotal,true,0)."</strong>&nbsp;</td>\n";
          } else {
            $strResult .= "  <td nowrap align=right>" .standardFormat($fltOT1/60,true)."&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right>" .standardFormat($fltOT2/60,true)."&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right>" .standardFormat($fltOT3/60,true)."&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right>" .standardFormat($fltOT4/60,true)."&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=right><strong>" .standardFormat($fltTotal/60,true)."</strong>&nbsp;</td>\n";
          }

          $strResult .= " </tr>\n";
    */
  }
  writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  $strResult .= "</table>\n";
  return $arrResult;
} // getData
// fungsi buat nampilih header dari table hasil
function showHeader()
{
  global $bolDataCost;
  $strResult = "";
  // bikin header table
  $strDefaultWidth = "width=40";
  $strColSpan = ($bolDataCost) ? "colspan=2" : "";
  $strResult .= " <tr align=center class=tableHeader>\n";
  $strResult .= "  <td nowrap><input name=\"chkAll\" type=\"checkbox\" id=\"chkAll\" value=\"All\" onClick=\"checkAll(this.checked);\"></td>\n";
  $strResult .= "  <td nowrap>&nbsp;</td>\n";
  $strResult .= "  <td nowrap>&nbsp;</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . strtoupper(getWords("employee id")) . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . strtoupper(getWords("employee name")) . "</td>\n";
  if ($bolDataCost) {
    $strResult .= "  <td nowrap>&nbsp;" . strtoupper(getWords("basic salary")) . "</td>\n";
  }
  $strResult .= "  <td nowrap $strDefaultWidth $strColSpan>&nbsp;" . strtoupper(getWords("ot 1")) . "</td>\n";
  $strResult .= "  <td nowrap $strDefaultWidth $strColSpan>&nbsp;" . strtoupper(getWords("ot 2")) . "</td>\n";
  $strResult .= "  <td nowrap $strDefaultWidth $strColSpan>&nbsp;" . strtoupper(getWords("ot 3")) . "</td>\n";
  $strResult .= "  <td nowrap $strDefaultWidth $strColSpan>&nbsp;" . strtoupper(getWords("ot 4")) . "</td>\n";
  $strResult .= "  <td nowrap $strDefaultWidth $strColSpan>&nbsp;" . strtoupper(getWords("total")) . "</td>\n";
  $strResult .= " </tr>\n";
  return $strResult;
} //showHeader
// fungsi menampilkan data per baris
// input : no urut, data, kelas CSS
function showRows($strNo, $arrData, $strClass = "", $intType = 0)
{
  global $bolDataCost;
  global $bolPrint;
  $strResult = "";
  $strDefaultWidth = "width=40";
  $strResult .= " <tr valign=top class=\"$strClass\">\n";
  if ($intType == 0) {
    if (!$bolPrint && $strClass == "") {
      $strResult .= "  <td><input type=checkbox name=chkID$strNo value=\"" . $arrData['id'] . "\"></td>";
    }
    $strResult .= "  <td nowrap align=right>$strNo&nbsp;</td>";
    $strResult .= "  <td nowrap align=right>&nbsp;</td>\n";
    $strResult .= "  <td nowrap >&nbsp;" . $arrData['employee_id'] . "</td>\n";
    $strResult .= "  <td nowrap >&nbsp;" . $arrData['employee_name'] . "</td>\n";
  } else if ($intType == 1) {
    $strResult .= "  <td nowrap align=right>&nbsp;</td>";
    $strResult .= "  <td nowrap width='5px'>&nbsp;</td>\n";
    $strResult .= "  <td nowrap colspan='3'>" . $arrData['employee_name'] . "</td>\n";
  } else if ($intType == 2) {
    $strResult .= "  <td nowrap align=right>&nbsp;</td>";
    $strResult .= "  <td nowrap colspan='4'>" . $arrData['employee_name'] . "</td>\n";
  } else if ($intType == 3) {
    $strResult .= "  <td nowrap align=right><input name=\"chkAll2\" type=\"checkbox\" id=\"chkAll2\" value=\"All\" onClick=\"checkAll(this.checked);\"></td>";
    $strResult .= "  <td nowrap colspan='4'>" . $arrData['employee_name'] . "</td>\n";
  }
  if ($bolDataCost) {
    $strResult .= "  <td nowrap align=right $strDefaultWidth>" . cekStandardFormat(
            $arrData['basic_salary'],
            true,
            2
        ) . "</td>\n";
    $strResult .= "  <td nowrap align=right $strDefaultWidth>" . cekStandardFormat(
            $arrData['ot1'] / 60,
            true
        ) . "</td>\n";
    $strResult .= "  <td nowrap align=right $strDefaultWidth>" . cekStandardFormat(
            $arrData['amount1'],
            true,
            0
        ) . "</td>\n";
    $strResult .= "  <td nowrap align=right $strDefaultWidth>" . cekStandardFormat(
            $arrData['ot2'] / 60,
            true
        ) . "</td>\n";
    $strResult .= "  <td nowrap align=right $strDefaultWidth>" . cekStandardFormat(
            $arrData['amount2'],
            true,
            0
        ) . "</td>\n";
    $strResult .= "  <td nowrap align=right $strDefaultWidth>" . cekStandardFormat(
            $arrData['ot3'] / 60,
            true
        ) . "</td>\n";
    $strResult .= "  <td nowrap align=right $strDefaultWidth>" . cekStandardFormat(
            $arrData['amount3'],
            true,
            0
        ) . "</td>\n";
    $strResult .= "  <td nowrap align=right $strDefaultWidth>" . cekStandardFormat(
            $arrData['ot4'] / 60,
            true
        ) . "</td>\n";
    $strResult .= "  <td nowrap align=right $strDefaultWidth>" . cekStandardFormat(
            $arrData['amount4'],
            true,
            0
        ) . "</td>\n";
    $strResult .= "  <td nowrap align=right $strDefaultWidth><strong>" . cekStandardFormat(
            $arrData['total'] / 60,
            true
        ) . "</strong></td>\n";
    $strResult .= "  <td nowrap align=right $strDefaultWidth><strong>" . cekStandardFormat(
            $arrData['amountTotal'],
            true,
            0
        ) . "</strong></td>\n";
  } else {
    $strResult .= "  <td nowrap align=right>" . cekStandardFormat($arrData['ot1'] / 60, true) . "</td>\n";
    $strResult .= "  <td nowrap align=right>" . cekStandardFormat($arrData['ot2'] / 60, true) . "</td>\n";
    $strResult .= "  <td nowrap align=right>" . cekStandardFormat($arrData['ot3'] / 60, true) . "</td>\n";
    $strResult .= "  <td nowrap align=right>" . cekStandardFormat($arrData['ot4'] / 60, true) . "</td>\n";
    $strResult .= "  <td nowrap align=right><strong>" . cekStandardFormat(
            $arrData['total'] / 60,
            true
        ) . "</strong></td>\n";
  }
  $strResult .= " </tr>\n";
  return $strResult;
} //showRows
// fungsi untuk nampilin data per employee
// input: dbclass, nomor urut, data
function showData($arrData)
{
  global $words;
  $intRows = 0;
  $strResult = "";
  $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable width=\"100%\">\n";
  // bikin header table
  $strDefaultWidth = "width=40";
  $strResult .= showHeader();
  foreach ($arrData AS $x => $rowDb) {
    $intRows++;
    $strResult .= showRows($intRows, $rowDb);
  }
  $strResult .= "</table>\n";
  return $strResult;
} // showData
//----------------------------------------------------------------------
// menampilkan data, digroup berdasar departemen
function showDataDepartment($db, $arrData, &$intRows)
{
  global $strDataDepartment;
  global $strDataSection;
  global $strDataEmployee;
  global $bolDataCost;
  global $chkDept;
  global $chkSect;
  global $chkEmp;
  $intRows = 0;
  $strResult = "";
  $strKriteriaDept = "";
  $strKriteriaSect = "";
  $bolShowTotal = true;
  $bolShowTotalDept = ($chkDept != "");
  $bolShowTotalSect = ($chkSect != "");
  $bolShowEmp = ($chkEmp != "");
  // cek jika cuma 1 employee yg dicari
  if ($strDataEmployee != "" && isset($arrData[1])) {
    $strKriteriaDept .= "AND department_code = '" . $arrData[1]['department_code'] . "' ";
    $strKriteriaSect .= "AND department_code = '" . $arrData[1]['department_code'] . "' ";
    $strKriteriaSect .= "AND section_code = '" . $arrData[1]['section_code'] . "' ";
    $bolShowTotal = $bolShowTotalDept = $bolShowTotalSect = false;
  }
  // cari data section
  $arrSect = [];
  if ($strDataSection != "") {
    $strKriteriaSect .= "AND section_code =  '$strDataSection' ";
    $bolShowTotal = $bolShowTotalDept = false;
  }
  $strSQL = "SELECT * FROM hrd_section WHERE 1=1 $strKriteriaSect ";
  $strSQL .= "ORDER BY section_code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrSect[$rowDb['department_code']][$rowDb['section_code']] = $rowDb['section_name'];
    if ($strDataSection != "") {
      $strKriteriaDept = "AND department_code = '" . $rowDb['department_code'] . "' ";
    }
  }
  // cari data Department
  if ($strDataDepartment != "") {
    $strKriteriaDept .= "AND department_code = '$strDataDepartment' ";
    $bolShowTotal = false;
  }
  $arrDept = [];
  $strSQL = "SELECT * FROM hrd_department WHERE 1=1 $strKriteriaDept ";
  $strSQL .= "ORDER BY department_code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDept[$rowDb['department_code']] = $rowDb['department_name'];
  }
  // tentukan keanggotaan department/section
  $arrDeptEmployee = []; // daftar anggota sebuah departement, tapi gak punya section
  $arrSectEmployee = []; // daftar anggota sebuah section
  foreach ($arrData AS $x => $rowDb) {
    if ($rowDb['department_code'] != "" && $rowDb['section_code'] != "") {
      // masuk ke dalam section
      if (isset($arrSectEmployee[$rowDb['section_code']])) {
        $arrSectEmployee[$rowDb['section_code']][] = $x;
      } else {
        $arrSectEmployee[$rowDb['section_code']][0] = $x;
      }
    } else if ($rowDb['department_code'] != "") { // cuma ada departemen aja
      // masukkan ke dalam department, tapi gak di section tertentu
      if (isset($arrDeptEmployee[$rowDb['department_code']])) {
        $arrDeptEmployee[$rowDb['department_code']][] = $x;
      } else {
        $arrDeptEmployee[$rowDb['department_code']][0] = $x;
      }
    }
  }
  // array temporer untuk reset data
  $arrEmptyData = [
      "id"            => "",
      "ot1"           => 0,
      "ot2"           => 0,
      "ot3"           => 0,
      "ot4"           => 0,
      "total"         => 0,
      "amount1"       => 0,
      "amount2"       => 0,
      "amount3"       => 0,
      "amount4"       => 0,
      "amountTotal"   => 0,
      "employee_id"   => "",
      "employee_name" => "",
      "basicSalary"   => 0,
  ];
  $arrTotal = $arrEmptyData;
  $arrTotal['employee_name'] = "<strong>" . strtoupper(getWords("grand total")) . "</strong>";
  // tampilkan data
  $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
  // bikin header table
  $strDefaultWidth = "width=40";
  $strResult .= showHeader();
  $intColspan = ($bolDataCost) ? 14 : 9;
  foreach ($arrDept AS $strDeptCode => $strDeptName) {
    if ($bolShowTotalDept) {
      $strResult .= " <tr valign=top class=\"bgNewData\">\n";
      $strResult .= "  <td>&nbsp;</td><td nowrap colspan=$intColspan>[$strDeptCode] $strDeptName</td>\n";
      $strResult .= " </tr>\n";
    }
    $arrTotalDept = $arrEmptyData;
    $arrTotalDept['employee_name'] = "<font color=\"darkblue\">" . strtoupper("total") . "</font>";
    // tampilkan data karyawan anggota departemen
    $arrTmp = (isset($arrDeptEmployee[$strDeptCode])) ? $arrDeptEmployee[$strDeptCode] : [];
    foreach ($arrTmp AS $x => $strX) {
      $intRows++;
      $rowDb = $arrData[$strX];
      $arrTotal['ot1'] += $rowDb['ot1'];
      $arrTotal['ot2'] += $rowDb['ot2'];
      $arrTotal['ot3'] += $rowDb['ot3'];
      $arrTotal['ot4'] += $rowDb['ot4'];
      $arrTotal['total'] += $rowDb['total'];
      $arrTotalDept['ot1'] += $rowDb['ot1'];
      $arrTotalDept['ot2'] += $rowDb['ot2'];
      $arrTotalDept['ot3'] += $rowDb['ot3'];
      $arrTotalDept['ot4'] += $rowDb['ot4'];
      $arrTotalDept['total'] += $rowDb['total'];
      if ($bolDataCost) {
        $arrTotal['amount1'] += $rowDb['amount1'];
        $arrTotal['amount2'] += $rowDb['amount2'];
        $arrTotal['amount3'] += $rowDb['amount3'];
        $arrTotal['amount4'] += $rowDb['amount4'];
        $arrTotal['amountTotal'] += $rowDb['amountTotal'];
        $arrTotalDept['amount1'] += $rowDb['amount1'];
        $arrTotalDept['amount2'] += $rowDb['amount2'];
        $arrTotalDept['amount3'] += $rowDb['amount3'];
        $arrTotalDept['amount4'] += $rowDb['amount4'];
        $arrTotalDept['amountTotal'] += $rowDb['amountTotal'];
      }
      if ($bolShowEmp) {
        $strResult .= showRows($intRows, $rowDb);
      }
    }
    $arrTmp = (isset($arrSect[$strDeptCode])) ? $arrSect[$strDeptCode] : [];
    foreach ($arrTmp AS $strSectCode => $strSectName) {
      if ($bolShowTotalSect) {
        $strResult .= " <tr valign=top >\n";
        $strResult .= "  <td>&nbsp;</td><td>&nbsp;</td><td nowrap colspan=" . ($intColspan - 1) . " class=\"bgNewRevised\">[$strSectCode] $strSectName</td>\n";
        $strResult .= " </tr>\n";
      }
      $arrTotalSect = $arrEmptyData;
      $arrTotalSect['employee_name'] = "<font color=\"orange\">" . strtoupper("total") . "</font>";
      // cari karyawan dalam section ini
      $arrTmp1 = (isset($arrSectEmployee[$strSectCode])) ? $arrSectEmployee[$strSectCode] : [];
      foreach ($arrTmp1 AS $x => $strX) {
        $intRows++;
        $rowDb = $arrData[$strX];
        // hitung total dulu
        $arrTotal['ot1'] += $rowDb['ot1'];
        $arrTotal['ot2'] += $rowDb['ot2'];
        $arrTotal['ot3'] += $rowDb['ot3'];
        $arrTotal['ot4'] += $rowDb['ot4'];
        $arrTotal['total'] += $rowDb['total'];
        $arrTotalDept['ot1'] += $rowDb['ot1'];
        $arrTotalDept['ot2'] += $rowDb['ot2'];
        $arrTotalDept['ot3'] += $rowDb['ot3'];
        $arrTotalDept['ot4'] += $rowDb['ot4'];
        $arrTotalDept['total'] += $rowDb['total'];
        $arrTotalSect['ot1'] += $rowDb['ot1'];
        $arrTotalSect['ot2'] += $rowDb['ot2'];
        $arrTotalSect['ot3'] += $rowDb['ot3'];
        $arrTotalSect['ot4'] += $rowDb['ot4'];
        $arrTotalSect['total'] += $rowDb['total'];
        if ($bolDataCost) {
          $arrTotal['amount1'] += $rowDb['amount1'];
          $arrTotal['amount2'] += $rowDb['amount2'];
          $arrTotal['amount3'] += $rowDb['amount3'];
          $arrTotal['amount4'] += $rowDb['amount4'];
          $arrTotal['amountTotal'] += $rowDb['amountTotal'];
          $arrTotalDept['amount1'] += $rowDb['amount1'];
          $arrTotalDept['amount2'] += $rowDb['amount2'];
          $arrTotalDept['amount3'] += $rowDb['amount3'];
          $arrTotalDept['amount4'] += $rowDb['amount4'];
          $arrTotalDept['amountTotal'] += $rowDb['amountTotal'];
          $arrTotalSect['amount1'] += $rowDb['amount1'];
          $arrTotalSect['amount2'] += $rowDb['amount2'];
          $arrTotalSect['amount3'] += $rowDb['amount3'];
          $arrTotalSect['amount4'] += $rowDb['amount4'];
          $arrTotalSect['amountTotal'] += $rowDb['amountTotal'];
        }
        if ($bolShowEmp) {
          $strResult .= showRows($intRows, $rowDb);
        }
      }
      // tampilkan total per section
      // parameter terakhir : jenis data
      // 1 = total section
      // 2 = total department
      // 3 = total grand total
      if ($bolShowTotalSect) {
        $strResult .= showRows("", $arrTotalSect, "cOrange", 1);
      }
    }
    if ($bolShowTotalDept) {
      $strResult .= showRows("", $arrTotalDept, "cBlue", 2);
    }
  }
  if ($bolShowTotal) {
    $strResult .= showRows("", $arrTotal, "tableHeader", 3);
  }
  $strResult .= "</table>\n";
  return $strResult;
} // showDataDepartment
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  // ------ AMBIL DATA KRITERIA -------------------------
  $chkDept = (isset($_REQUEST['chkDept'])) ? "checked" : "";
  $chkSect = (isset($_REQUEST['chkSect'])) ? "checked" : "";
  if (!isset($_REQUEST['dataShowEmp'])) {
    $chkEmp = "checked";
  } else {
    $chkEmp = (isset($_REQUEST['chkEmp'])) ? "checked" : "";
  }
  $strDataDateFrom = (isset($_SESSION['sessionFilterDateFrom'])) ? $_SESSION['sessionFilterDateFrom'] : date("Y-m-d");
  $strDataDateThru = (isset($_SESSION['sessionFilterDateThru'])) ? $_SESSION['sessionFilterDateThru'] : date("Y-m-d");
  $strDataEmployeeStatus = (isset($_SESSION['sessionFilterEmployeeStatus'])) ? $_SESSION['sessionFilterEmployeeStatus'] : "";
  if (isset($_REQUEST['dataDateFrom'])) {
    $strDataDateFrom = $_REQUEST['dataDateFrom'];
  }
  if (isset($_REQUEST['dataDateThru'])) {
    $strDataDateThru = $_REQUEST['dataDateThru'];
  }
  if (isset($_REQUEST['dataEmployeeStatus'])) {
    $strDataEmployeeStatus = $_REQUEST['dataEmployeeStatus'];
  }
  (isset($_REQUEST['dataDivision'])) ? $strDataDivision = $_REQUEST['dataDivision'] : $strDataDivision = "";
  (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
  (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
  (isset($_REQUEST['dataSubsection'])) ? $strDataSubsection = $_REQUEST['dataSubsection'] : $strDataSubsection = "";
  //(isset($_REQUEST['dataGroup'])) ? $strDataGroup = $_REQUEST['dataGroup'] : $strDataGroup = "";
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
  $bolDataCost = (isset($_REQUEST['dataCost']));
  $_SESSION['sessionFilterEmployeeStatus'] = $strDataEmployeeStatus;
  $_SESSION['sessionFilterDateFrom'] = $strDataDateFrom;
  $_SESSION['sessionFilterDateThru'] = $strDataDateThru;
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    if ($arrUserInfo['isDeptHead']) {
      $strDataDepartment = $arrUserInfo['department_code'];
    } else if ($arrUserInfo['isGroupHead']) {
      $strDataSection = $arrUserInfo['section_code'];
    } else {
      $strDataEmployee = $arrUserInfo['employee_id'];
    }
  }
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if ($strDataDivision != "") {
    $strKriteria .= "AND division_code = '$strDataDivision' ";
  }
  if ($strDataDepartment != "") {
    $strKriteria .= "AND department_code = '$strDataDepartment' ";
  }
  if ($strDataSection != "") {
    $strKriteria .= "AND section_code = '$strDataSection' ";
  }
  if ($strDataSubsection != "") {
    $strKriteria .= "AND sub_section_code = '$strDataSubsection' ";
  }
  if ($strDataEmployee != "") {
    $strKriteria .= "AND employee_id = '$strDataEmployee' ";
  }
  if ($strDataEmployeeStatus > 0) {
    $strKriteria .= ($strDataEmployeeStatus == 2) ? "AND employee_status = '" . STATUS_OUTSOURCE . "'" : "AND employee_status <> '" . STATUS_OUTSOURCE . "' ";
  }
  $strKriteria .= $strKriteriaCompany;
  if ($bolCanView) {
    $bolShow = (isset($_REQUEST['btnShow']) || $bolPrint);
    if (validStandardDate($strDataDateFrom) && validStandardDate($strDataDateThru) && $bolShow) {
      // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
      $arrDataDetail = getData($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
      //         $strDataDetail = showData($arrDataDetail);
      $strDataDetail = showDataDepartment($db, $arrDataDetail, $intTotalData);
      $strHidden .= "<input type=hidden name=btnShow value=show>";
      if (isset($_REQUEST['btnExcel'])) {
        // ambil data CSS-nya
        if (file_exists("bw.css")) {
          $strStyle = "bw.css";
        }
        $strPrintCss = "";
        $strPrintInit = "";
        headeringExcel("attendance.xls");
      }
    } else {
      $strDataDetail = "";
    }
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  // generate data hidden input dan element form input
  $strReadonly = ($bolIsEmployee) ? "readonly" : "";
  $strDisabled = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) ? "disabled" : "";
  $strTmpKriteria = "WHERE 1=1 ";
  $intDefaultWidthPx = 200;
  $strInputDateFrom = "<input type=text name=dataDateFrom id=dataDateFrom size=15 maxlength=10 value=\"$strDataDateFrom\">";
  $strInputDateThru = "<input type=text name=dataDateThru id=dataDateThru size=15 maxlength=10 value=\"$strDataDateThru\">";
  $strInputDateFrom2 = "<input type=hidden name=dataDateFrom id=dataDateFrom value=\"$strDataDateFrom\"";
  $strInputDateThru2 = "<input type=hidden name=dataDateThru id=dataDateThru value=\"$strDataDateThru\"";
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"$strDataEmployee\" $strReadonly>";
  $strInputDivision = getDivisionList(
      $db,
      "dataDivision",
      $strDataDivision,
      $strEmptyOption,
      "",
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  if ($arrUserInfo['isDeptHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strTmpKriteria .= "AND department_code = '" . $arrUserInfo['department_code'] . "' ";
    $strDisabled = "";
  }
  //handle user company-access-right
  $strInputCompany = getCompanyList(
      $db,
      "dataCompany",
      $strDataCompany,
      $strEmptyOption2,
      $strKriteria2,
      "style=\"width:$intDefaultWidthPx\" "
  );
  $strInputDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $strDataDepartment,
      $strEmptyOption,
      $strTmpKriteria,
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  if ($arrUserInfo['isGroupHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strTmpKriteria .= "AND section_code = '" . $arrUserInfo['section_code'] . "' ";
    $strDisabled = "";
  }
  $strInputSection = getSectionList(
      $db,
      "dataSection",
      $strDataSection,
      $strEmptyOption,
      $strTmpKriteria,
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  $strInputSubsection = getSubSectionList(
      $db,
      "dataSubsection",
      $strDataSubsection,
      $strEmptyOption,
      "",
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  //$strInputGroup = getGroupList($db,"dataGroup",$strDataGroup, $strEmptyOption, "", "style=\"width:$intDefaultWidthPx\" $strDisabled");
  $arrTmp = ["", "employee", "outsource"];
  $strInputEmployeeStatus = getComboFromArray($arrTmp, "dataEmployeeStatus", $strDataEmployeeStatus);
  $strCheck = ($bolDataCost) ? "checked" : "";
  $strInputCost = "<input type=checkbox name=dataCost value=1 $strCheck>";
  $strChkDepartment = "<input type=\"checkbox\" name=\"chkDept\" $chkDept>";
  $strChkSection = "<input type=\"checkbox\" name=\"chkSect\" $chkSect>";
  $strChkEmployee = "<input type=\"checkbox\" name=\"chkEmp\" $chkEmp onClick=\"setEmpHidden(this.checked);\">";
  // informasi tanggal kehadiran
  if ($strDataDateFrom == $strDataDateThru) {
    $strInfo .= "<br>" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
  } else {
    $strInfo .= "<br>" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
    $strInfo .= " &raquo; " . strtoupper(pgDateFormat($strDataDateThru, "d-M-Y"));
  }
  $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDataDateFrom\">";
  $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDataDateThru\">";
  $strHidden .= "<input type=hidden name=dataDivision value=\"$strDataDivision\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
  $strHidden .= "<input type=hidden name=dataSubsection value=\"$strDataSubsection\">";
  //$strHidden .= "<input type=hidden name=dataGroup value=\"$strDataGroup\">";
  $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
  $strHidden .= "<input type=hidden name=dataEmployeeStatus value=\"$strDataEmployeeStatus\">";
  $strHidden .= "<input type=hidden name=dataShowEmp value=\"$chkEmp\">";
  $strHidden .= "<input type=hidden name=totalData value=\"$intTotalData\">";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
if ($bolPrint) {
  $strMainTemplate = getTemplate(str_replace(".php", "_print.html", basename($_SERVER['PHP_SELF'])));
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>