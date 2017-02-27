<?php
if (!session_id()) {
  session_start();
}
include_once('global.php');
//include_once(getTemplate("words.inc"));
header('Pragma: no-cache');
header('Content-Type: application/vnd.ms-word');
header('Content-Disposition: attachment; filename="salary_slip.doc"');
//$strTemplateFile = getTemplate("salary_slip.html", false);
$db = new CdbClass;
if ($db->connect()) {
  $strNow = date("d F Y");
  $fltHourPerMonth = getSetting("hour_per_month");
  if (!is_numeric($fltHourPerMonth)) {
    $fltHourPerMonth = "173";
  } // default
  // ambil semua setting dulu
  $arrSetting = [];
  $strSQL = "SELECT * FROM all_setting ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrSetting[$rowDb['code']] = $rowDb['value'];
  }
  // daftar komponen gaji yang fix, termasu kode field di tabel salary
  // dibikin array, biar lebih simple
  $arrIncome = [
    //"salary" => array("name" => "Gaji", "code" => "basicSalary"),
    "transport"   => ["name" => "Transportasi", "code" => "transport"],
    "housing"     => ["name" => "Perumahan", "code" => "housing"],
    "conjuncture" => ["name" => "Konjungtur", "code" => "conjuncture"],
    "leave"       => ["name" => "Cuti", "code" => "leave"],
    "meal"        => ["name" => "Uang Makan", "code" => "lunch"],
  ];
  $fltZakatPercent = (isset($arrSetting["zakat_deducdeduc"])) ? $arrSetting["zakat_deduc"] : "0";
  if (!is_numeric($fltZakatPercent)) {
    $fltZakatPercent = 0;
  }
  $strBasicSalaryName = (isset($arrSetting["basicsalary_name"])) ? $arrSetting["basicsalary_name"] : "Gaji";
  if (isset($arrSetting["transport_name"])) {
    $arrIncome["transport"]['name'] = $arrSetting["transport_name"];
  }
  if (isset($arrSetting["housing_name"])) {
    $arrIncome["housing"]['name'] = $arrSetting["housing_name"];
  }
  if (isset($arrSetting["conjuncture_name"])) {
    $arrIncome["conjuncture"]['name'] = $arrSetting["conjuncture_name"];
  }
  if (isset($arrSetting["leave_name"])) {
    $arrIncome["leave"]['name'] = $arrSetting["leave_name"];
  }
  if (isset($arrSetting["meal_name"])) {
    $arrIncome["meal"]['name'] = $arrSetting["meal_name"];
  }
  $strOTName = (isset($arrSettting['overtime_name'])) ? $arrSetting['overtime_name'] : "Uang Lembur";
  $strJamsostekName = (isset($arrSettting['jamsostek_deduc_name'])) ? $arrSetting['jamsostek_deduc_name'] : "Jamsostek";
  $strLoanName = (isset($arrSettting['loan_name'])) ? $arrSetting['loan_name'] : getWords("loan");
  $strLoanShow = (isset($arrSettting['loan_show'])) ? ($arrSetting['loan_show'] == 't') : "f";
  // CARI DATA type allowance dan deduction
  $arrAllowType = [];
  $arrDeducType = [];
  if (isset($_REQUEST['dataID'])) {
    $strDataID = $_REQUEST['dataID'];
    $strSQL = "SELECT t1.*, t2.name FROM hrd_salary_master_allowance AS t1 ";
    $strSQL .= "LEFT JOIN hrd_allowance_type AS t2 ON t1.allowance_code = t2.code ";
    $strSQL .= "WHERE id_salary_master = '$strDataID' AND is_default = 'f' ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrAllowType[$rowDb['allowanceCode']] = $rowDb['name'];
    }
    $strDataID = $_REQUEST['dataID'];
    $strSQL = "SELECT t1.*, t2.name FROM hrd_salary_master_deduction AS t1 ";
    $strSQL .= "LEFT JOIN hrd_deduction_type AS t2 ON t1.deduction_code = t2.code ";
    $strSQL .= "WHERE id_salary_master = '$strDataID' AND is_default = 'f'  ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrDeducType[$rowDb['deductionCode']] = $rowDb['name'];
    }
  }
  // cari daftar info zakat
  $arrZakat = [];
  $strSQL = "SELECT zakat, id_employee FROM hrd_employee_basic_salary ";
  $resZ = $db->execute($strSQL);
  while ($rowZ = $db->fetchrow($resZ)) {
    if ($rowZ['zakat'] == 't') {
      $arrZakat[$rowZ['id_employee']] = $rowZ['zakat'];
    }
  }
  $bolGanjil = true;
  $i = 0;
  foreach ($_REQUEST AS $kode => $value) {
    if (substr($kode, 0, 5) == 'chkID') {
      $strSQL = "SELECT t1.*, t2.employee_name, t3.position_name, t4.section_name, ";
      $strSQL .= "t2.\"bank\", t2.bank_account, t6.salary_date, ";
      $strSQL .= "t5.department_name, t2.function, t6.date_thru ";
      $strSQL .= "FROM hrd_salary_detail AS t1 ";
      $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
      $strSQL .= "LEFT JOIN hrd_position AS t3 ON t2.position_code = t3.position_code ";
      $strSQL .= "LEFT JOIN hrd_section AS t4 ON t2.section_code = t4.section_code ";
      $strSQL .= "LEFT JOIN hrd_department AS t5 ON t2.department_code = t5.department_code ";
      $strSQL .= "LEFT JOIN hrd_salary_master AS t6 ON t1.id_salary_master = t6.id ";
      $strSQL .= "WHERE t1.id = '$value' AND t2.employee_status <> " . STATUS_OUTSOURCE . " ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $i++;
        $strPeriode = "" . pgDateFormat($rowDb['date_thru'], " F Y");
        $stremployee_name = "" . $rowDb['employee_name'];
        $stremployee_id = "" . $rowDb['employee_id'];
        $strDepartmentName = "" . $rowDb['department_name'];
        $strSectionName = "" . $rowDb['section_name'];
        $strPositionName = "" . $rowDb['position_name'];
        $strGradeCode = "" . $rowDb['grade_code'];
        $strFunctional = "" . $rowDb['function'];
        $strPeriodeIndo = "" . pgDateFormat($rowDb['salary_date'], "d F Y");
        $strBank = "" . $rowDb['bank'];
        $strBankAccount = "" . $rowDb['bank_account'];
        $strShowZakat = (isset($arrZakat[$rowDb['id_employee']])) ? "" : "display:none";
        $strDetailDeduction = "";
        $strDetailIncome = "";
        $fltTotalIncome = 0;
        $fltTotalDeduction = 0;
        //--- CARI INCOME DULU
        // cari basic
        //$strDetailIncome .= "  <tr valign=top><td nowrap>$strBasicSalaryName&nbsp;</td>\n";
        //$strDetailIncome .= "   <td nowrap>:</td>\n<td nowrap>Rp</td>\n";
        //$strDetailIncome .= "   <td nowrap align=right>" .standardFormat($rowDb['basic_salary'] - $rowDb['attendance_deduction'],true,0)."&nbsp;</td>\n</tr>\n";
        $strBasicSalary = $rowDb['basic_salary'];
        $strDetailIncome = standardFormat($rowDb['basic_salary'] - $rowDb['attendance_deduction'], true, 0);
        $fltTotalIncome += $rowDb['basic_salary'] - $rowDb['attendance_deduction'];
        // cari income lain2
        foreach ($arrIncome AS $index => $arrInfo) {
          $strName = $arrInfo['name'];
          $strField = $arrInfo['code'];
          //$strDetailIncome .= "  <tr valign=top><td nowrap>$strName&nbsp;</td>\n";
          //$strDetailIncome .= "   <td nowrap>:</td>\n<td nowrap>Rp</td>\n";
          //$strDetailIncome .= "   <td nowrap align=right>" .standardFormat($rowDb[$strField],true,0)."&nbsp;</td>\n</tr>\n";
          $strDetailIncome = standardFormat($rowDb[$strField], true, 0);
          $fltTotalIncome += $rowDb[$strField];
        }
        $fltOT = $rowDb['ot1'] + $rowDb['ot2'] + $rowDb['ot3'] + $rowDb['ot4'];
        //$strDetailIncome .= "  <tr valign=top><td nowrap>$strOTName&nbsp;</td>\n";
        //$strDetailIncome .= "   <td nowrap>:</td>\n<td nowrap>Rp</td>\n";
        //$strDetailIncome .= "   <td nowrap align=right>" .standardFormat($fltOT,true,0)."&nbsp;</td>\n</tr>\n";
        $strDetailIncome = standardFormat($fltOT, true, 0);
        $fltTotalIncome += $fltOT;
        // cari potongan lain-lain
        foreach ($arrAllowType AS $strCode => $strName) {
          $fltAmount = 0;
          $strSQL = "SELECT * FROM hrd_salary_allowance WHERE allowance_code = '$strCode' ";
          $strSQL .= "AND id_employee = '" . $rowDb['id_employee'] . "' ";
          $strSQL .= "AND id_salary_master = '" . $rowDb['idSalaryMaster'] . "' ";
          $resA = $db->execute($strSQL);
          if ($rowA = $db->fetchrow($resA)) {
            $fltAmount = $rowA['amount'];
          }
          //$strDetailIncome .= "  <tr valign=top><td nowrap>$strName&nbsp;</td>\n";
          //$strDetailIncome .= "   <td nowrap>:</td>\n<td nowrap>Rp</td>\n";
          //$strDetailIncome .= "   <td nowrap align=right>" .standardFormat($fltAmount,true,0)."&nbsp;</td>\n</tr>\n";
          $strDetailIncome = standardFormat($fltAmount, true, 0);
          $fltTotalIncome += $fltAmount;
        }
        // -- CARI POTONGAN
        //$strDetailDeduction .= "  <tr valign=top><td nowrap>$strJamsostekName&nbsp;</td>\n";
        //$strDetailDeduction .= "   <td nowrap>:</td>\n<td nowrap>Rp</td>\n";
        //$strDetailDeduction .= "   <td nowrap align=right>" .standardFormat($rowDb['jamsostek_deduction'],true,0)."&nbsp;</td>\n</tr>\n";
        $strDetailDeduction = standardFormat($rowDb['jamsostek_deduction'], true, 0);
        $strJamsostek = $strDetailDeduction;
        $fltTotalDeduction += $rowDb['jamsostek_deduction'];
        // LOAN
        if ($strLoanShow) {
          //$strDetailDeduction .= "  <tr valign=top><td nowrap>$strLoanName&nbsp;</td>\n";
          //$strDetailDeduction .= "   <td nowrap>:</td>\n<td nowrap>Rp</td>\n";
          //$strDetailDeduction .= "   <td nowrap align=right>" .standardFormat($rowDb['loan'],true,0)."&nbsp;</td>\n</tr>\n";
          $strDetailDeduction = standardFormat($rowDb['loan'], true, 0);
        }
        $fltTotalDeduction += $rowDb['loan'];
        $strLoan = $fltTotalDeduction;
        // cari potongan lain-lain
        foreach ($arrDeducType AS $strCode => $strName) {
          $fltAmount = 0;
          $strSQL = "SELECT * FROM hrd_salary_deduction WHERE deduction_code = '$strCode' ";
          $strSQL .= "AND id_employee = '" . $rowDb['id_employee'] . "' ";
          $strSQL .= "AND id_salary_master = '" . $rowDb['idSalaryMaster'] . "' ";
          $resA = $db->execute($strSQL);
          if ($rowA = $db->fetchrow($resA)) {
            $fltAmount = $rowA['amount'];
          }
          //$strDetailDeduction .= "  <tr valign=top><td nowrap>$strName&nbsp;</td>\n";
          //$strDetailDeduction .= "   <td nowrap>:</td>\n<td nowrap>Rp</td>\n";
          //$strDetailDeduction .= "   <td nowrap align=right>" .standardFormat($fltAmount,true,0)."&nbsp;</td>\n</tr>\n";
          $strDetailDeduction = standardFormat($fltAmount, true, 0);
          $fltTotalDeduction += $fltAmount;
        }
        $strTotalIncome = standardFormat($fltTotalIncome, true, 0);
        $strTotalDeduction = standardFormat($fltTotalDeduction, true, 0);
        $strTotalNet = standardFormat($rowDb['total_net'], true, 0);
        $fltZakat = ($fltZakatPercent / 100) * $fltTotalIncome;
        $strZakat = standardFormat($fltZakat, true, 0);
        echo "<p><table border=0 class=\"slip\"><tr><td>";
        $tbsPage = new clsTinyButStrong;
        $tbsPage->LoadTemplate(getTemplate("salary_slip.html"));
        $tbsPage->noErr = false;
        $tbsPage->Show(TBS_OUTPUT);
        echo "</td></tr></table></p><br>\n \n";
        echo "<span><br clear=all style='mso-special-character:line-break;page-break-before:always'> </span>";
      }
    }
  }
}
?>
