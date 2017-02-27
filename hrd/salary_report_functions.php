<?php
// FUNCTION RELATED TO SALARY REPORT
define("CHR_CHECK", "X"); // untuk laporan pajak, simbol untuk menandai pilihan
$arrFixAllowances = [
    "transport"   => "Transport Allowance",
    "housing"     => "Housing Allowance",
    "position"    => "Position Allowance",
    "leave"       => "Leave Allowance",
    "lunch"       => "Meal Allowance",
    "loyality"    => "Pengabdian",
    "overtime"    => "Overtime",
    "conjuncture" => "Conjuncture",
];
$arrFixDeductions = [
    "loan" => "Loan"
];
// fungsi untuk mengambil daftar tunjangan dan potongan gaji
function getSalaryComponent($db, $strDefault)
{
  global $arrFixAllowances;
  global $arrFixDeductions;
  $strResult = "<select name='filterComponent' id='filterComponent' >
    ";
  $strResult .= "<option value=''>All</option> ";
  $strResult .= "<option value='basicSalary' " . (($strDefault == "basicSalary") ? "selected" : "") . ">Basic Salary</option> ";
  $strResult .= "<optgroup label='Allowance'>";
  foreach ($arrFixAllowances AS $strCode => $strName) {
    $strResult .= "<option value='1_$strCode' " . (($strDefault == "1_" . $strCode) ? "selected" : "") . ">$strName</option>";
  }
  $strResult .= "</optgroup>";
  $strResult .= "<optgroup label='Deduction'>";
  foreach ($arrFixDeductions AS $strCode => $strName) {
    $strResult .= "<option value='2_$strCode' " . (($strDefault == "2_" . $strCode) ? "selected" : "") . ">$strName</option>";
  }
  $strResult .= "</optgroup>";
  $strResult .= "</select>";
  return $strResult;
}

// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getDataReport($db, &$intRows, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
{
  global $words;
  global $bolPrint;
  global $strPaging;
  global $intTotalData;
  global $intRowsLimit;
  global $strYear;
  global $strComponent; // komponen gaji
  global $arrFixAllowances, $arrFixDeductions;
  $intRowsLimit = getSetting($db, "rows_per_page");
  if (!is_numeric($intRowsLimit)) {
    $intRowsLimit = 50;
  }
  $intRows = 0;
  $strResult = "";
  $arrResult = [];
  $bolLimit = false;
  $strFields = "totalNet";
  $intComponentType = 0; // 0 : dari data salary detail, 1 : allowance , 2 : deduction
  if ($strComponent == "basicSalary") {
    $strFields = "basicSalary";
  } else if ($strComponent != "") {
    $arrTmp = explode("_", $strComponent);
    if (count($arrTmp) > 1) {
      if (isset($arrFixAllowances[$arrTmp[1]])) {
        // detail
      } else if (isset($arrFixDeductions[$arrTmp[1]])) {
        // detail
      } else if ($arrTmp[0] == 1) {
        $intComponentType = 1;
      } // allowance
      else if ($arrTmp[0] == 2) {
        $intComponentType = 2;
      } // deduction
      $strFields = $arrTmp[1];
    } else {
    }
  }
  // cari data gaji, kumpulkan di array dulu
  $arrGaji = [];
  $strSQL = "
        SELECT SUM(\"$strFields\") AS gaji, EXTRACT(MONTH FROM \"salaryDate\") AS bulan, \"idEmployee\" 
        FROM \"hrdSalaryMaster\" AS t1, \"hrdSalaryDetail\" AS t2 
        WHERE t1.id = t2.\"idSalaryMaster\" 
          AND EXTRACT(YEAR FROM t1.\"salaryDate\") = '$strYear' 
        GROUP BY \"salaryDate\", \"idEmployee\" 
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if (isset($arrGaji[$rowDb['idEmployee']]['bulan'])) {
      $arrGaji[$rowDb['idEmployee']][$rowDb['bulan']] += $rowDb['gaji'];
    } else {
      $arrGaji[$rowDb['idEmployee']][$rowDb['bulan']] = $rowDb['gaji'];
    }
  }
  // cari total data karyawan
  $intTotal = 0;
  $strSQL = "SELECT count(id) AS total FROM \"hrdEmployee\" ";
  $strSQL .= "WHERE \"joinDate\" is not null AND active = 1 $strKriteria "; // hanya ambil yang statusnya permanent
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    if (is_numeric($rowDb['total'])) {
      $intTotal = $rowDb['total'];
    }
  }
  if ($bolLimit) {
    $strPaging = getPaging($intPage, $intTotal, "javascript:goPage('[PAGE]')");
    if ($strPaging == "") {
      $strPaging = "1&nbsp;";
    }
  }
  $intStart = (($intPage - 1) * $intRowsLimit);
  $strSQL = "SELECT id, \"employeeID\", \"employeeName\", gender, \"departmentCode\", \"positionCode\", \"sectionCode\" ";
  $strSQL .= "FROM \"hrdEmployee\" WHERE 1 = 1 ";
  $strSQL .= "AND (active = 1 OR EXTRACT(YEAR FROM \"joinDate\") = '$strYear' ";
  $strSQL .= "OR EXTRACT(YEAR FROM \"resignDate\") = '$strYear') ";
  $strSQL .= " $strKriteria ORDER BY $strOrder \"employeeID\" ";
  if ($bolLimit) {
    $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strGender = ($rowDb['gender'] == 0) ? "F" : "M";
    $fltTotal = 0;
    $arrResult[$intRows]['id'] = $rowDb['id'];
    $arrResult[$intRows]['employeeID'] = $rowDb['employeeID'];
    $arrResult[$intRows]['employeeName'] = $rowDb['employeeName'];
    $arrResult[$intRows]['departmentCode'] = $rowDb['departmentCode'];
    $arrResult[$intRows]['sectionCode'] = $rowDb['sectionCode'];
    $arrResult[$intRows]['gender'] = $strGender;
    $strHiddenID = "";//"<input type=hidden name='chkID$intRows' value=\"" .$rowDb['id']."\">";
    /*
    $strResult .= "<tr valign=top>\n";
    $strResult .= "  <td nowrap>" .$rowDb['employeeID']. "&nbsp;</td>";
    $strResult .= "  <td nowrap>" .$rowDb['employeeName']. "&nbsp;</td>";
    $strResult .= "  <td align=center>$strGender&nbsp;</td>";
    $strResult .= "  <td>" .$rowDb['departmentCode']. "&nbsp;</td>";
    $strResult .= "  <td>" .$rowDb['positionCode']. "&nbsp;</td>";
    */
    for ($i = 1; $i <= 12; $i++) {
      $fltGaji = (isset($arrGaji[$rowDb['id']][$i])) ? $arrGaji[$rowDb['id']][$i] : 0;
      $fltTotal += $fltGaji;
      //$strGaji = ($fltGaji == 0) ? "" : standardFormat($fltGaji);
      //$strResult .= "  <td align=right>" .$strGaji. "&nbsp;</td>";
      $arrResult[$intRows]['salary_' . $i] = $fltGaji;
    }
    //$strTotal = ($fltTotal == 0) ? "" : standardFormat($fltTotal);
    //$strResult .= "  <td align=right>" .$strTotal. "&nbsp;</td>";
    //$strResult .= "</tr>\n";
    $arrResult[$intRows]['total'] = $fltTotal;
  }
  $intTotalData = $intRows;
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  //return $strResult;
  return $arrResult;
} // showData
// fungsi buat nampilin data per baris doank
function showRowsReport($strNo, $rowData, $strClass = "")
{
  $strResult = "";
  $strResult .= "<tr valign=top class=\"$strClass\">\n";
  $strResult .= "  <td nowrap>" . $rowData['employeeID'] . "&nbsp;</td>";
  $strResult .= "  <td nowrap>" . $rowData['employeeName'] . "&nbsp;</td>";
  $strResult .= "  <td align=center>" . $rowData['gender'] . "&nbsp;</td>";
  for ($i = 1; $i <= 12; $i++) {
    $fltGaji = $rowData['salary_' . $i];
    $strGaji = ($fltGaji == 0) ? "" : standardFormat($fltGaji);
    $strResult .= "  <td align=right>" . $strGaji . "&nbsp;</td>";
  }
  $strTotal = ($rowData['total'] == 0) ? "" : standardFormat($rowData['total']);
  $strResult .= "  <td align=right>" . $strTotal . "&nbsp;</td>";
  $strResult .= "</tr>\n";
  return $strResult;
} //showRows
// fungsi untuk nampilin data per employee
// input: dbclass, nomor urut, data
function showDataReport($arrData)
{
  global $words;
  $intRows = 0;
  $strResult = "";
  //     $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
  // bikin header table
  $strDefaultWidth = "width=40";
  //     $strResult .= showHeader();
  foreach ($arrData AS $x => $rowDb) {
    $intRows++;
    $strResult .= showRowsReport($intRows, $rowDb);
  }
  //     $strResult .= "</table>\n";
  return $strResult;
} // showData
// menampilkan data, digroup berdasar departemen
function showDataDepartmentReport($db, $arrData)
{
  global $words;
  global $_SESSION;
  global $strFilterDepartment;
  global $strFilterSection;
  global $strFilterEmployeeID;
  $intRows = 0;
  $strResult = "";
  $strKriteriaDept = "";
  $strKriteriaSect = "";
  $bolShowTotal = $bolShowTotalDept = $bolShowTotalSect = true;
  // cek jika cuma 1 employee yg dicari
  if ($strFilterEmployeeID != "" && isset($arrData[1])) {
    $strKriteriaDept .= "AND \"departmentCode\" = '" . $arrData[1]['departmentCode'] . "' ";
    $strKriteriaSect .= "AND \"departmentCode\" = '" . $arrData[1]['departmentCode'] . "' ";
    $strKriteriaSect .= "AND \"sectionCode\" = '" . $arrData[1]['sectionCode'] . "' ";
    $bolShowTotal = $bolShowTotalDept = $bolShowTotalSect = false;
  }
  // cari data section
  $arrSect = [];
  if ($strFilterSection != "") {
    $strKriteriaSect .= "AND \"sectionCode\" =  '$strFilterSection' ";
    $bolShowTotal = $bolShowTotalDept = false;
  }
  $strSQL = "SELECT * FROM \"hrdSection\" WHERE 1=1 $strKriteriaSect ";
  $strSQL .= "ORDER BY \"sectionCode\" ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrSect[$rowDb['departmentCode']][$rowDb['sectionCode']] = $rowDb['sectionName'];
    if ($strFilterSection != "") {
      $strKriteriaDept = "AND \"departmentCode\" = '" . $rowDb['departmentCode'] . "' ";
    }
  }
  // cari data Department
  if ($strFilterDepartment != "") {
    $strKriteriaDept .= "AND \"departmentCode\" = '$strFilterDepartment' ";
    $bolShowTotal = false;
  }
  $arrDept = [];
  $strSQL = "SELECT * FROM \"hrdDepartment\" WHERE 1=1 $strKriteriaDept ";
  $strSQL .= "ORDER BY \"departmentCode\" ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDept[$rowDb['departmentCode']] = $rowDb['departmentName'];
  }
  // tentukan keanggotaan department/section
  $arrDeptEmployee = []; // daftar anggota sebuah departement, tapi gak punya section
  $arrSectEmployee = []; // daftar anggota sebuah section
  foreach ($arrData AS $x => $rowDb) {
    if ($rowDb['departmentCode'] != "" && $rowDb['sectionCode'] != "") {
      // masuk ke dalam section
      if (isset($arrSectEmployee[$rowDb['sectionCode']])) {
        $arrSectEmployee[$rowDb['sectionCode']][] = $x;
      } else {
        $arrSectEmployee[$rowDb['sectionCode']][0] = $x;
      }
    } else if ($rowDb['departmentCode'] != "") { // cuma ada departemen aja
      // masukkan ke dalam department, tapi gak di section tertentu
      if (isset($arrDeptEmployee[$rowDb['departmentCode']])) {
        $arrDeptEmployee[$rowDb['departmentCode']][] = $x;
      } else {
        $arrDeptEmployee[$rowDb['departmentCode']][0] = $x;
      }
    }
  }
  // array temporer untuk reset data
  $arrEmptyData = [
      "id"           => "",
      "total"        => 0,
      "gender"       => "",
      "employeeID"   => "",
      "employeeName" => "",
  ];
  for ($i = 1; $i < 13; $i++) {
    $arrEmptyData['salary_' . $i] = 0;
  }
  $arrTotal = $arrEmptyData;
  $arrTotal['employeeName'] = "<strong>" . strtoupper(getWords("grand total")) . "</strong>";
  // tampilkan data
  //     $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
  // bikin header table
  $strDefaultWidth = "width=40";
  //     $strResult .= showHeader();
  $intColspan = 4 + 12;
  foreach ($arrDept AS $strDeptCode => $strDeptName) {
    if ($bolShowTotalDept) {
      $strResult .= " <tr valign=top>\n";
      $strResult .= "  <td nowrap colspan=$intColspan>&nbsp;<strong>[$strDeptCode] $strDeptName</strong></td>\n";
      $strResult .= " </tr>\n";
    }
    $arrTotalDept = $arrEmptyData;
    $arrTotalDept['employeeName'] = "<strong>" . strtoupper(getWords("total") . " " . $strDeptCode) . "</strong>";
    // tampilkan data karyawan anggota departemen
    $arrTmp = (isset($arrDeptEmployee[$strDeptCode])) ? $arrDeptEmployee[$strDeptCode] : [];
    foreach ($arrTmp AS $x => $strX) {
      $rowDb = $arrData[$strX];
      $arrTotal['total'] += $rowDb['total'];
      $arrTotalDept['total'] += $rowDb['total'];
      for ($i = 1; $i < 13; $i++) {
        $arrTotal['salary_' . $i] += $rowDb['salary_' . $i];
        $arrTotalDept['salary_' . $i] += $rowDb['salary_' . $i];
      }
      $strResult .= showRowsReport("", $rowDb);
    }
    $arrTmp = (isset($arrSect[$strDeptCode])) ? $arrSect[$strDeptCode] : [];
    foreach ($arrTmp AS $strSectCode => $strSectName) {
      if ($bolShowTotalSect) {
        $strResult .= " <tr valign=top>\n";
        $strResult .= "  <td nowrap colspan=$intColspan>&nbsp;<strong>[$strSectCode] $strSectName</strong></td>\n";
        $strResult .= " </tr>\n";
      }
      $arrTotalSect = $arrEmptyData;
      $arrTotalSect['employeeName'] = "<strong>" . strtoupper(getWords("total") . " " . $strSectCode) . "</strong>";
      // cari karyawan dalam section ini
      $arrTmp1 = (isset($arrSectEmployee[$strSectCode])) ? $arrSectEmployee[$strSectCode] : [];
      foreach ($arrTmp1 AS $x => $strX) {
        $rowDb = $arrData[$strX];
        // hitung total dulu
        $arrTotal['total'] += $rowDb['total'];
        $arrTotalDept['total'] += $rowDb['total'];
        $arrTotalSect['total'] += $rowDb['total'];
        for ($i = 1; $i < 13; $i++) {
          $arrTotal['salary_' . $i] += $rowDb['salary_' . $i];
          $arrTotalDept['salary_' . $i] += $rowDb['salary_' . $i];
          $arrTotalSect['salary_' . $i] += $rowDb['salary_' . $i];
        }
        $strResult .= showRowsReport("", $rowDb);
      }
      // tampilkan total per section
      if ($bolShowTotalSect) {
        $strResult .= showRowsReport("", $arrTotalSect, "bgNewRevised");
      }
    }
    if ($bolShowTotalDept) {
      $strResult .= showRowsReport("", $arrTotalDept, "bgNewRevised");
    }
  }
  if ($bolShowTotal) {
    $strResult .= showRowsReport("", $arrTotal, "tableHeader");
  }
  //     $strResult .= "</table>\n";
  return $strResult;
} // showDataDepartment
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getDataTax($db, &$intRows, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
{
  global $words;
  global $bolPrint;
  global $strPaging;
  global $intTotalData;
  global $intRowsLimit;
  global $strYear;
  $intRowsLimit = getSetting($db, "rows_per_page");
  if (!is_numeric($intRowsLimit)) {
    $intRowsLimit = 50;
  }
  $intRows = 0;
  $strResult = "";
  $arrResult = [];
  $bolLimit = false;
  // cari data gaji, kumpulkan di array dulu
  $arrGaji = [];
  $strSQL = "SELECT SUM(\"tax\") AS pajak, EXTRACT(MONTH FROM \"salaryDate\") AS bulan, \"idEmployee\" ";
  $strSQL .= "FROM \"hrdSalaryMaster\" AS t1, \"hrdSalaryDetail\" AS t2 ";
  $strSQL .= "WHERE t1.id = t2.\"idSalaryMaster\" ";
  $strSQL .= "AND EXTRACT(YEAR FROM t1.\"salaryDate\") = '$strYear' ";
  $strSQL .= "GROUP BY \"salaryDate\", \"idEmployee\" ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if (isset($arrGaji[$rowDb['idEmployee']]['bulan'])) {
      $arrGaji[$rowDb['idEmployee']][$rowDb['bulan']] += $rowDb['pajak'];
    } else {
      $arrGaji[$rowDb['idEmployee']][$rowDb['bulan']] = $rowDb['pajak'];
    }
  }
  // cari total data karyawan
  $intTotal = 0;
  $strSQL = "SELECT count(id) AS total FROM \"hrdEmployee\" ";
  $strSQL .= "WHERE \"joinDate\" is not null AND active = 1 $strKriteria "; // hanya ambil yang statusnya permanent
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    if (is_numeric($rowDb['total'])) {
      $intTotal = $rowDb['total'];
    }
  }
  if ($bolLimit) {
    $strPaging = getPaging($intPage, $intTotal, "javascript:goPage('[PAGE]')");
    if ($strPaging == "") {
      $strPaging = "1&nbsp;";
    }
  }
  $intStart = (($intPage - 1) * $intRowsLimit);
  $strSQL = "SELECT id, \"employeeID\", \"employeeName\", gender, \"departmentCode\", \"positionCode\", \"sectionCode\" ";
  $strSQL .= "FROM \"hrdEmployee\" WHERE 1 = 1 ";
  $strSQL .= "AND (active = 1 OR EXTRACT(YEAR FROM \"joinDate\") = '$strYear' ";
  $strSQL .= "OR EXTRACT(YEAR FROM \"resignDate\") = '$strYear') ";
  $strSQL .= " $strKriteria ORDER BY $strOrder \"employeeID\" ";
  if ($bolLimit) {
    $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strGender = ($rowDb['gender'] == 0) ? "F" : "M";
    $fltTotal = 0;
    $arrResult[$intRows]['id'] = $rowDb['id'];
    $arrResult[$intRows]['employeeID'] = $rowDb['employeeID'];
    $arrResult[$intRows]['employeeName'] = $rowDb['employeeName'];
    $arrResult[$intRows]['departmentCode'] = $rowDb['departmentCode'];
    $arrResult[$intRows]['sectionCode'] = $rowDb['sectionCode'];
    $arrResult[$intRows]['gender'] = $strGender;
    for ($i = 1; $i <= 12; $i++) {
      $fltGaji = (isset($arrGaji[$rowDb['id']][$i])) ? $arrGaji[$rowDb['id']][$i] : 0;
      $fltTotal += $fltGaji;
      //$strGaji = ($fltGaji == 0) ? "" : standardFormat($fltGaji);
      //$strResult .= "  <td align=right>" .$strGaji. "&nbsp;</td>";
      $arrResult[$intRows]['salary_' . $i] = $fltGaji;
    }
    $arrResult[$intRows]['total'] = $fltTotal;
  }
  $intTotalData = $intRows;
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  //return $strResult;
  return $arrResult;
} // getData
// fungsi buat nampilin data per baris doank
function showRowsTax($strNo, $rowData, $strClass = "")
{
  global $bolPrint;
  $strResult = "";
  $strResult .= "<tr valign=top class=\"$strClass\">\n";
  $strResult .= "  <td nowrap>" . $rowData['employeeID'] . "&nbsp;</td>";
  $strResult .= "  <td nowrap>" . $rowData['employeeName'] . "&nbsp;</td>";
  $strResult .= "  <td align=center>" . $rowData['gender'] . "&nbsp;</td>";
  for ($i = 1; $i <= 12; $i++) {
    $fltGaji = $rowData['salary_' . $i];
    $strGaji = ($fltGaji == 0) ? "" : standardFormat($fltGaji);
    $strResult .= "  <td align=right>" . $strGaji . "&nbsp;</td>";
  }
  $strTotal = ($rowData['total'] == 0) ? "" : standardFormat($rowData['total']);
  $strResult .= "  <td align=right>" . $strTotal . "&nbsp;</td>";
  if ($bolPrint) {
    $strResult .= "  <td align=right>&nbsp;</td>";
  } else {
    $strResult .= "  <td align=center><button type='button' onclick=\"goTaxForm('" . $rowData['employeeID'] . "')\">1721-A1</button></td>";
  }
  $strResult .= "</tr>\n";
  return $strResult;
} //showRows
// fungsi untuk nampilin data per employee
// input: dbclass, nomor urut, data
function showDataTax($arrData)
{
  global $words;
  $intRows = 0;
  $strResult = "";
  //     $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
  // bikin header table
  $strDefaultWidth = "width=40";
  //     $strResult .= showHeader();
  foreach ($arrData AS $x => $rowDb) {
    $intRows++;
    $strResult .= showRowsTax($intRows, $rowDb);
  }
  //     $strResult .= "</table>\n";
  return $strResult;
} // showData
// menampilkan data, digroup berdasar departemen
function showDataDepartmentTax($db, $arrData)
{
  global $words;
  global $_SESSION;
  global $strFilterDepartment;
  global $strFilterSection;
  global $strFilterEmployeeID;
  $intRows = 0;
  $strResult = "";
  $strKriteriaDept = "";
  $strKriteriaSect = "";
  $bolShowTotal = $bolShowTotalDept = $bolShowTotalSect = true;
  // cek jika cuma 1 employee yg dicari
  if ($strFilterEmployeeID != "" && isset($arrData[1])) {
    $strKriteriaDept .= "AND \"departmentCode\" = '" . $arrData[1]['departmentCode'] . "' ";
    $strKriteriaSect .= "AND \"departmentCode\" = '" . $arrData[1]['departmentCode'] . "' ";
    $strKriteriaSect .= "AND \"sectionCode\" = '" . $arrData[1]['sectionCode'] . "' ";
    $bolShowTotal = $bolShowTotalDept = $bolShowTotalSect = false;
  }
  // cari data section
  $arrSect = [];
  if ($strFilterSection != "") {
    $strKriteriaSect .= "AND \"sectionCode\" =  '$strFilterSection' ";
    $bolShowTotal = $bolShowTotalDept = false;
  }
  $strSQL = "SELECT * FROM \"hrdSection\" WHERE 1=1 $strKriteriaSect ";
  $strSQL .= "ORDER BY \"sectionCode\" ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrSect[$rowDb['departmentCode']][$rowDb['sectionCode']] = $rowDb['sectionName'];
    if ($strFilterSection != "") {
      $strKriteriaDept = "AND \"departmentCode\" = '" . $rowDb['departmentCode'] . "' ";
    }
  }
  // cari data Department
  if ($strFilterDepartment != "") {
    $strKriteriaDept .= "AND \"departmentCode\" = '$strFilterDepartment' ";
    $bolShowTotal = false;
  }
  $arrDept = [];
  $strSQL = "SELECT * FROM \"hrdDepartment\" WHERE 1=1 $strKriteriaDept ";
  $strSQL .= "ORDER BY \"departmentCode\" ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDept[$rowDb['departmentCode']] = $rowDb['departmentName'];
  }
  // tentukan keanggotaan department/section
  $arrDeptEmployee = []; // daftar anggota sebuah departement, tapi gak punya section
  $arrSectEmployee = []; // daftar anggota sebuah section
  foreach ($arrData AS $x => $rowDb) {
    if ($rowDb['departmentCode'] != "" && $rowDb['sectionCode'] != "") {
      // masuk ke dalam section
      if (isset($arrSectEmployee[$rowDb['sectionCode']])) {
        $arrSectEmployee[$rowDb['sectionCode']][] = $x;
      } else {
        $arrSectEmployee[$rowDb['sectionCode']][0] = $x;
      }
    } else if ($rowDb['departmentCode'] != "") { // cuma ada departemen aja
      // masukkan ke dalam department, tapi gak di section tertentu
      if (isset($arrDeptEmployee[$rowDb['departmentCode']])) {
        $arrDeptEmployee[$rowDb['departmentCode']][] = $x;
      } else {
        $arrDeptEmployee[$rowDb['departmentCode']][0] = $x;
      }
    }
  }
  // array temporer untuk reset data
  $arrEmptyData = [
      "id"           => "",
      "total"        => 0,
      "gender"       => "",
      "employeeID"   => "",
      "employeeName" => "",
  ];
  for ($i = 1; $i < 13; $i++) {
    $arrEmptyData['salary_' . $i] = 0;
  }
  $arrTotal = $arrEmptyData;
  $arrTotal['employeeName'] = "<strong>" . strtoupper(getWords("grand total")) . "</strong>";
  // tampilkan data
  //     $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
  // bikin header table
  $strDefaultWidth = "width=40";
  //     $strResult .= showHeader();
  $intColspan = 5 + 12;
  foreach ($arrDept AS $strDeptCode => $strDeptName) {
    if ($bolShowTotalDept) {
      $strResult .= " <tr valign=top>\n";
      $strResult .= "  <td nowrap colspan=$intColspan>&nbsp;<strong>[$strDeptCode] $strDeptName</strong></td>\n";
      $strResult .= " </tr>\n";
    }
    $arrTotalDept = $arrEmptyData;
    $arrTotalDept['employeeName'] = "<strong>" . strtoupper(getWords("total") . " " . $strDeptCode) . "</strong>";
    // tampilkan data karyawan anggota departemen
    $arrTmp = (isset($arrDeptEmployee[$strDeptCode])) ? $arrDeptEmployee[$strDeptCode] : [];
    foreach ($arrTmp AS $x => $strX) {
      $rowDb = $arrData[$strX];
      $arrTotal['total'] += $rowDb['total'];
      $arrTotalDept['total'] += $rowDb['total'];
      for ($i = 1; $i < 13; $i++) {
        $arrTotal['salary_' . $i] += $rowDb['salary_' . $i];
        $arrTotalDept['salary_' . $i] += $rowDb['salary_' . $i];
      }
      $strResult .= showRowsTax("", $rowDb);
    }
    $arrTmp = (isset($arrSect[$strDeptCode])) ? $arrSect[$strDeptCode] : [];
    foreach ($arrTmp AS $strSectCode => $strSectName) {
      if ($bolShowTotalSect) {
        $strResult .= " <tr valign=top>\n";
        $strResult .= "  <td nowrap colspan=$intColspan>&nbsp;<strong>[$strSectCode] $strSectName</strong></td>\n";
        $strResult .= " </tr>\n";
      }
      $arrTotalSect = $arrEmptyData;
      $arrTotalSect['employeeName'] = "<strong>" . strtoupper(getWords("total") . " " . $strSectCode) . "</strong>";
      // cari karyawan dalam section ini
      $arrTmp1 = (isset($arrSectEmployee[$strSectCode])) ? $arrSectEmployee[$strSectCode] : [];
      foreach ($arrTmp1 AS $x => $strX) {
        $rowDb = $arrData[$strX];
        // hitung total dulu
        $arrTotal['total'] += $rowDb['total'];
        $arrTotalDept['total'] += $rowDb['total'];
        $arrTotalSect['total'] += $rowDb['total'];
        for ($i = 1; $i < 13; $i++) {
          $arrTotal['salary_' . $i] += $rowDb['salary_' . $i];
          $arrTotalDept['salary_' . $i] += $rowDb['salary_' . $i];
          $arrTotalSect['salary_' . $i] += $rowDb['salary_' . $i];
        }
        $strResult .= showRowsTax("", $rowDb);
      }
      // tampilkan total per section
      if ($bolShowTotalSect) {
        $strResult .= showRowsTax("", $arrTotalSect, "bgNewRevised");
      }
    }
    if ($bolShowTotalDept) {
      $strResult .= showRowsTax("", $arrTotalDept, "bgNewRevised");
    }
  }
  if ($bolShowTotal) {
    $strResult .= showRowsTax("", $arrTotal, "tableHeader");
  }
  //     $strResult .= "</table>\n";
  return $strResult;
} // showDataDepartment
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getDataJamsostek($db, &$intRows, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
{
  global $words;
  global $bolPrint;
  global $strPaging;
  global $intTotalData;
  global $intRowsLimit;
  global $strYear;
  $intRowsLimit = getSetting($db, "rows_per_page");
  if (!is_numeric($intRowsLimit)) {
    $intRowsLimit = 50;
  }
  $intRows = 0;
  $strResult = "";
  $arrResult = [];
  $bolLimit = false;
  // cari data gaji, kumpulkan di array dulu
  $arrGaji = [];
  $strSQL = "SELECT SUM(\"jamsostekDeduction\" + \"jamsostekAllowance\") AS jamsostek, ";
  $strSQL .= "EXTRACT(MONTH FROM \"salaryDate\") AS bulan, \"idEmployee\" ";
  $strSQL .= "FROM \"hrdSalaryMaster\" AS t1, \"hrdSalaryDetail\" AS t2 ";
  $strSQL .= "WHERE t1.id = t2.\"idSalaryMaster\" ";
  $strSQL .= "AND EXTRACT(YEAR FROM t1.\"salaryDate\") = '$strYear' ";
  $strSQL .= "GROUP BY \"salaryDate\", \"idEmployee\" ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if (isset($arrGaji[$rowDb['idEmployee']]['bulan'])) {
      $arrGaji[$rowDb['idEmployee']][$rowDb['bulan']] += $rowDb['jamsostek'];
    } else {
      $arrGaji[$rowDb['idEmployee']][$rowDb['bulan']] = $rowDb['jamsostek'];
    }
  }
  // cari total data karyawan
  $intTotal = 0;
  $strSQL = "SELECT count(id) AS total FROM \"hrdEmployee\" ";
  $strSQL .= "WHERE \"joinDate\" is not null AND active = 1 $strKriteria "; // hanya ambil yang statusnya permanent
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    if (is_numeric($rowDb['total'])) {
      $intTotal = $rowDb['total'];
    }
  }
  if ($bolLimit) {
    $strPaging = getPaging($intPage, $intTotal, "javascript:goPage('[PAGE]')");
    if ($strPaging == "") {
      $strPaging = "1&nbsp;";
    }
  }
  $intStart = (($intPage - 1) * $intRowsLimit);
  $strSQL = "SELECT id, \"employeeID\", \"employeeName\", gender, \"departmentCode\", \"positionCode\", \"sectionCode\" ";
  $strSQL .= "FROM \"hrdEmployee\" WHERE 1 = 1 ";
  $strSQL .= "AND (active = 1 OR EXTRACT(YEAR FROM \"joinDate\") = '$strYear' ";
  $strSQL .= "OR EXTRACT(YEAR FROM \"resignDate\") = '$strYear') ";
  $strSQL .= " $strKriteria ORDER BY $strOrder \"employeeID\" ";
  if ($bolLimit) {
    $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strGender = ($rowDb['gender'] == 0) ? "F" : "M";
    $fltTotal = 0;
    $arrResult[$intRows]['id'] = $rowDb['id'];
    $arrResult[$intRows]['employeeID'] = $rowDb['employeeID'];
    $arrResult[$intRows]['employeeName'] = $rowDb['employeeName'];
    $arrResult[$intRows]['departmentCode'] = $rowDb['departmentCode'];
    $arrResult[$intRows]['sectionCode'] = $rowDb['sectionCode'];
    $arrResult[$intRows]['gender'] = $strGender;
    /*
          $strHiddenID = "<input type=hidden name='chkID$intRows' value=\"" .$rowDb['id']."\">";
          $strResult .= "<tr valign=top>\n";
          $strResult .= "  <td nowrap>$strHiddenID" .$rowDb['employeeID']. "&nbsp;</td>";
          $strResult .= "  <td nowrap>" .$rowDb['employeeName']. "&nbsp;</td>";
          $strResult .= "  <td align=center>$strGender&nbsp;</td>";
          $strResult .= "  <td>" .$rowDb['departmentCode']. "&nbsp;</td>";
          $strResult .= "  <td>" .$rowDb['positionCode']. "&nbsp;</td>";*/
    for ($i = 1; $i <= 12; $i++) {
      $fltGaji = (isset($arrGaji[$rowDb['id']][$i])) ? $arrGaji[$rowDb['id']][$i] : 0;
      $fltTotal += $fltGaji;
      //$strGaji = ($fltGaji == 0) ? "" : standardFormat($fltGaji);
      //$strResult .= "  <td align=right>" .$strGaji. "&nbsp;</td>";
      $arrResult[$intRows]['salary_' . $i] = $fltGaji;
    }
    //$strTotal = ($fltTotal == 0) ? "" : standardFormat($fltTotal);
    //$strResult .= "  <td align=right>" .$strTotal. "&nbsp;</td>";
    $strResult .= "</tr>\n";
    $arrResult[$intRows]['total'] = $fltTotal;
  }
  $intTotalData = $intRows;
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  //return $strResult;
  return $arrResult;
} // getData
// fungsi buat nampilin data per baris doank
function showRowsJamsostek($strNo, $rowData, $strClass = "")
{
  $strResult = "";
  $strResult .= "<tr valign=top class=\"$strClass\">\n";
  $strResult .= "  <td nowrap>" . $rowData['employeeID'] . "&nbsp;</td>";
  $strResult .= "  <td nowrap>" . $rowData['employeeName'] . "&nbsp;</td>";
  $strResult .= "  <td align=center>" . $rowData['gender'] . "&nbsp;</td>";
  for ($i = 1; $i <= 12; $i++) {
    $fltGaji = $rowData['salary_' . $i];
    $strGaji = ($fltGaji == 0) ? "" : standardFormat($fltGaji);
    $strResult .= "  <td align=right>" . $strGaji . "&nbsp;</td>";
  }
  $strTotal = ($rowData['total'] == 0) ? "" : standardFormat($rowData['total']);
  $strResult .= "  <td align=right>" . $strTotal . "&nbsp;</td>";
  $strResult .= "</tr>\n";
  return $strResult;
} //showRows
// fungsi untuk nampilin data per employee
// input: dbclass, nomor urut, data
function showDataJamsostek($arrData)
{
  global $words;
  $intRows = 0;
  $strResult = "";
  //     $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
  // bikin header table
  $strDefaultWidth = "width=40";
  //     $strResult .= showHeader();
  foreach ($arrData AS $x => $rowDb) {
    $intRows++;
    $strResult .= showRowsJamsostek($intRows, $rowDb);
  }
  //     $strResult .= "</table>\n";
  return $strResult;
} // showData
// menampilkan data, digroup berdasar departemen
function showDataDepartmentJamsostek($db, $arrData)
{
  global $words;
  global $_SESSION;
  global $strFilterDepartment;
  global $strFilterSection;
  global $strFilterEmployeeID;
  $intRows = 0;
  $strResult = "";
  $strKriteriaDept = "";
  $strKriteriaSect = "";
  $bolShowTotal = $bolShowTotalDept = $bolShowTotalSect = true;
  // cek jika cuma 1 employee yg dicari
  if ($strFilterEmployeeID != "" && isset($arrData[1])) {
    $strKriteriaDept .= "AND \"departmentCode\" = '" . $arrData[1]['departmentCode'] . "' ";
    $strKriteriaSect .= "AND \"departmentCode\" = '" . $arrData[1]['departmentCode'] . "' ";
    $strKriteriaSect .= "AND \"sectionCode\" = '" . $arrData[1]['sectionCode'] . "' ";
    $bolShowTotal = $bolShowTotalDept = $bolShowTotalSect = false;
  }
  // cari data section
  $arrSect = [];
  if ($strFilterSection != "") {
    $strKriteriaSect .= "AND \"sectionCode\" =  '$strFilterSection' ";
    $bolShowTotal = $bolShowTotalDept = false;
  }
  $strSQL = "SELECT * FROM \"hrdSection\" WHERE 1=1 $strKriteriaSect ";
  $strSQL .= "ORDER BY \"sectionCode\" ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrSect[$rowDb['departmentCode']][$rowDb['sectionCode']] = $rowDb['sectionName'];
    if ($strFilterSection != "") {
      $strKriteriaDept = "AND \"departmentCode\" = '" . $rowDb['departmentCode'] . "' ";
    }
  }
  // cari data Department
  if ($strFilterDepartment != "") {
    $strKriteriaDept .= "AND \"departmentCode\" = '$strFilterDepartment' ";
  }
  $arrDept = [];
  $strSQL = "SELECT * FROM \"hrdDepartment\" WHERE 1=1 $strKriteriaDept ";
  $strSQL .= "ORDER BY \"departmentCode\" ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDept[$rowDb['departmentCode']] = $rowDb['departmentName'];
  }
  // tentukan keanggotaan department/section
  $arrDeptEmployee = []; // daftar anggota sebuah departement, tapi gak punya section
  $arrSectEmployee = []; // daftar anggota sebuah section
  foreach ($arrData AS $x => $rowDb) {
    if ($rowDb['departmentCode'] != "" && $rowDb['sectionCode'] != "") {
      // masuk ke dalam section
      if (isset($arrSectEmployee[$rowDb['sectionCode']])) {
        $arrSectEmployee[$rowDb['sectionCode']][] = $x;
      } else {
        $arrSectEmployee[$rowDb['sectionCode']][0] = $x;
      }
    } else if ($rowDb['departmentCode'] != "") { // cuma ada departemen aja
      // masukkan ke dalam department, tapi gak di section tertentu
      if (isset($arrDeptEmployee[$rowDb['departmentCode']])) {
        $arrDeptEmployee[$rowDb['departmentCode']][] = $x;
      } else {
        $arrDeptEmployee[$rowDb['departmentCode']][0] = $x;
      }
    }
  }
  // array temporer untuk reset data
  $arrEmptyData = [
      "id"           => "",
      "total"        => 0,
      "gender"       => "",
      "employeeID"   => "",
      "employeeName" => "",
  ];
  for ($i = 1; $i < 13; $i++) {
    $arrEmptyData['salary_' . $i] = 0;
  }
  $arrTotal = $arrEmptyData;
  $arrTotal['employeeName'] = "<strong>" . strtoupper(getWords("grand total")) . "</strong>";
  // tampilkan data
  //     $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
  // bikin header table
  $strDefaultWidth = "width=40";
  //     $strResult .= showHeader();
  $intColspan = 4 + 12;
  foreach ($arrDept AS $strDeptCode => $strDeptName) {
    if ($bolShowTotalDept) {
      $strResult .= " <tr valign=top>\n";
      $strResult .= "  <td nowrap colspan=$intColspan>&nbsp;<strong>[$strDeptCode] $strDeptName</strong></td>\n";
      $strResult .= " </tr>\n";
    }
    $arrTotalDept = $arrEmptyData;
    $arrTotalDept['employeeName'] = "<strong>" . strtoupper(getWords("total") . " " . $strDeptCode) . "</strong>";
    // tampilkan data karyawan anggota departemen
    $arrTmp = (isset($arrDeptEmployee[$strDeptCode])) ? $arrDeptEmployee[$strDeptCode] : [];
    foreach ($arrTmp AS $x => $strX) {
      $rowDb = $arrData[$strX];
      $arrTotal['total'] += $rowDb['total'];
      $arrTotalDept['total'] += $rowDb['total'];
      for ($i = 1; $i < 13; $i++) {
        $arrTotal['salary_' . $i] += $rowDb['salary_' . $i];
        $arrTotalDept['salary_' . $i] += $rowDb['salary_' . $i];
      }
      $strResult .= showRowsJamsostek("", $rowDb);
    }
    $arrTmp = (isset($arrSect[$strDeptCode])) ? $arrSect[$strDeptCode] : [];
    foreach ($arrTmp AS $strSectCode => $strSectName) {
      if ($bolShowTotalSect) {
        $strResult .= " <tr valign=top>\n";
        $strResult .= "  <td nowrap colspan=$intColspan>&nbsp;<strong>[$strSectCode] $strSectName</strong></td>\n";
        $strResult .= " </tr>\n";
      }
      $arrTotalSect = $arrEmptyData;
      $arrTotalSect['employeeName'] = "<strong>" . strtoupper(getWords("total") . " " . $strSectCode) . "</strong>";
      // cari karyawan dalam section ini
      $arrTmp1 = (isset($arrSectEmployee[$strSectCode])) ? $arrSectEmployee[$strSectCode] : [];
      foreach ($arrTmp1 AS $x => $strX) {
        $rowDb = $arrData[$strX];
        // hitung total dulu
        $arrTotal['total'] += $rowDb['total'];
        $arrTotalDept['total'] += $rowDb['total'];
        $arrTotalSect['total'] += $rowDb['total'];
        for ($i = 1; $i < 13; $i++) {
          $arrTotal['salary_' . $i] += $rowDb['salary_' . $i];
          $arrTotalDept['salary_' . $i] += $rowDb['salary_' . $i];
          $arrTotalSect['salary_' . $i] += $rowDb['salary_' . $i];
        }
        $strResult .= showRowsJamsostek("", $rowDb);
      }
      // tampilkan total per section
      if ($bolShowTotalSect) {
        $strResult .= showRowsJamsostek("", $arrTotalSect, "bgNewRevised");
      }
    }
    if ($bolShowTotalDept) {
      $strResult .= showRowsJamsostek("", $arrTotalDept, "bgNewRevised");
    }
  }
  if ($bolShowTotal) {
    $strResult .= showRowsJamsostek("", $arrTotal, "tableHeader");
  }
  //     $strResult .= "</table>\n";
  return $strResult;
} // showDataDepartment
// function to initiate values in form 1721A1
function initForm1721A1()
{
  for ($i = 2; $i <= 3; $i++) {
    $GLOBALS['sTahun' . $i] = '';
  }
  for ($i = 0; $i <= 6; $i++) {
    $GLOBALS['sNo' . $i] = '';
  }
  for ($i = 0; $i <= 14; $i++) {
    $GLOBALS['sNPWP_No' . $i] = '';
  }
  for ($i = 0; $i <= 22; $i++) {
    $GLOBALS['sNPWP_Nama' . $i] = '';
  }
  for ($i = 0; $i <= 22; $i++) {
    $GLOBALS['sNama' . $i] = '';
  }
  for ($i = 0; $i <= 14; $i++) {
    $GLOBALS['sNPWP_Emp' . $i] = '';
  }
  for ($i = 0; $i <= 45; $i++) {
    $GLOBALS['strAlamat' . $i] = '';
  }
  for ($i = 1; $i <= 4; $i++) {
    $GLOBALS['sBulan' . $i] = '';
  }
  for ($i = 1; $i <= 24; $i++) {
    $GLOBALS['sGaji' . $i] = 0;
  }
  for ($i = 0; $i <= 1; $i++) {
    $GLOBALS['sBulan_24a_' . $i] = '';
  }
  for ($i = 0; $i <= 3; $i++) {
    $GLOBALS['sTahun_24a_' . $i] = '';
  }
  for ($i = 0; $i <= 1; $i++) {
    $GLOBALS['sBulan_24b_' . $i] = '';
  }
  for ($i = 0; $i <= 3; $i++) {
    $GLOBALS['sTahun_24b_' . $i] = '';
  }
  for ($i = 0; $i <= 1; $i++) {
    $GLOBALS['sTgl' . $i] = '';
  }
  for ($i = 0; $i <= 1; $i++) {
    $GLOBALS['sBln' . $i] = '';
  }
  for ($i = 0; $i <= 3; $i++) {
    $GLOBALS['sThn' . $i] = '';
  }
  for ($i = 0; $i <= 19; $i++) {
    $GLOBALS['sPerusahaan' . $i] = '';
  }
  for ($i = 0; $i <= 14; $i++) {
    $GLOBALS['sNPWP_Co' . $i] = '';
  }
  $GLOBALS['sMarried'] = '';
  $GLOBALS['sSingle'] = '';
  $GLOBALS['sMale'] = '';
  $GLOBALS['sFemale'] = '';
  $GLOBALS['sExpat'] = '';
  $GLOBALS['sStatusK'] = '';
  $GLOBALS['sStatusTK'] = '';
  $GLOBALS['sStatusHB'] = '';
  $GLOBALS['sPosition'] = '';
  $GLOBALS['sGaji22a'] = '';
  $GLOBALS['sGaji22b'] = '';
  $GLOBALS['sGaji23a'] = '';
  $GLOBALS['sGaji23b'] = '';
  $GLOBALS['sGaji24a'] = '';
  $GLOBALS['sGaji24b'] = '';
  $GLOBALS['sKuasa'] = '';
  $GLOBALS['sTempat'] = '';
  // set company name
  global $strCompanyName, $strCompanyNPWP;
  $GLOBALS['sPemotong'] = CHR_CHECK;
  $intLen = strlen($strCompanyName);
  for ($i = 0; $i < $intLen; $i++) {
    $GLOBALS['sNPWP_Nama' . $i] = strtoupper($strCompanyName[$i]);
    $GLOBALS['sPerusahaan' . $i] = strtoupper($strCompanyName[$i]);
  }
  $intLen = strlen($strCompanyNPWP);
  for ($i = 0, $j = 0; $i < $intLen; $i++) {
    if (is_numeric($strCompanyNPWP[$i])) {
      $GLOBALS['sNPWP_No' . $j] = strtoupper($strCompanyNPWP[$i]);
      $GLOBALS['sNPWP_Co' . $j] = strtoupper($strCompanyNPWP[$i]);
      $j++;
    }
  }
  // tempat dan tanggal, default hari ini
  $GLOBALS['sTempat'] = "JAKARTA";
  list($strThn, $strBln, $strTgl) = explode("-", date("Y-m-d"));
  for ($i = 0; $i <= 1; $i++) {
    $GLOBALS['sTgl' . $i] = $strTgl[$i];
  }
  for ($i = 0; $i <= 1; $i++) {
    $GLOBALS['sBln' . $i] = $strBln[$i];
  }
  for ($i = 0; $i <= 3; $i++) {
    $GLOBALS['sThn' . $i] = $strThn[$i];
  }
}

// function to generate single tax form
// input: Year, NIK
function generateForm1721A1($db, $strYear, $strEmployeeID)
{
  echo "gen tax report";
  exit();
  global $strCompanyName, $strCompanyNPWP;
  initForm1721A1();
  if (strlen($strYear) == 4) {
    for ($i = 2; $i <= 3; $i++) {
      $GLOBALS['sTahun' . $i] = $strYear[$i];
    }
  }
  // get employee information
  $strIDEmployee = "";
  $strSQL = "
          SELECT t1.*, t2.npwp, t2.\"primary_address\", t3.\"position_name\", t4.children 
          FROM \"hrd_salary_detail\" AS t1
          LEFT JOIN (
            SELECT * FROM \"hrd_employee\" WHERE \"employee_id\" = '$strEmployeeID'
          ) AS t2 ON t1.\"id_employee\" = t2.id
          LEFT JOIN \"hrd_position\" AS t3 ON t1.\"position_code\" = t3.\"position_code\"
          LEFT JOIN \"hrd_family_status\" AS t4 ON t1.\"family_status_code\" = t4.\"code\"
          WHERE t1.\"employee_id\" = '$strEmployeeID'
            AND (t1.\"id_salary_master\" IN (
              SELECT id FROM \"hrd_salary_master\" WHERE EXTRACT(year FROM \"salary_date\") = '$strYear' AND flag = 0 ORDER BY \"salary_date\" DESC LIMIT 1
            ) OR t1.\"idSalaryMaster\" IN (
              SELECT id FROM \"hrd_salary_master\" WHERE EXTRACT(year FROM \"salary_date\") = '$strYear' AND flag = 1 ORDER BY \"salary_date\" DESC LIMIT 1
            ))
            LIMIT 1
        ";
  $res = $db->execute($strSQL);
  //echo $strSQL;
  while ($row = $db->fetchrow($res)) {
    foreach ($row AS $key => $val) {
      $row[$key] = strtoupper($val);
    }
    $strIDEmployee = $row['idEmployee'];
    // no urut, sementara dianggap nik saja
    $strNo = addPrevZero($row['employeeID'], 7);
    for ($i = 0; $i <= 6; $i++) {
      $GLOBALS['sNo' . $i] = $strNo[$i];
    }
    $intLen = strlen($row['employeeName']);
    for ($i = 0; $i < $intLen; $i++) {
      $GLOBALS['sNama' . $i] = $row['employeeName'][$i];
    }
    $intLen = strlen($row['npwp']);
    for ($i = 0, $j = 0; $i < $intLen; $i++) {
      if (is_numeric($row['npwp'][$i])) {
        $GLOBALS['sNPWP_Emp' . $j++] = $row['npwp'][$i];
      }
    }
    $intLen = strlen($row['primaryAddress']);
    for ($i = 0; $i < $intLen; $i++) {
      $GLOBALS['strAlamat' . $i] = $row['primaryAddress'][$i];
    }
    ($row['gender'] == 1) ? $GLOBALS['sMale'] = CHR_CHECK : $GLOBALS['sFemale'] = CHR_CHECK;
    ($row['marital_status'] == 1) ? $GLOBALS['sMarried'] = CHR_CHECK : $GLOBALS['sSingle'] = CHR_CHECK;
    $GLOBALS['sPosition'] = $row['positionName'];
    if ($row['familyStatusCode'] != "") {
      ($row['familyStatusCode'][0] == 'K') ? $GLOBALS['sStatusK'] = $row['children'] : $GLOBALS['sStatusTK'] = $row['children'];
    }
    // ptkp terakhir
    $GLOBALS['sGaji17'] = $row['taxReduction'];
  }
  // cari data jumlah pajak dan gaji, dari tabel hrd_tax_calculation
  if ($strIDEmployee != "") {
    // cari pajak terakhir, untuk mengambil PTKP
    // cari total pajak
    $strSQL = "
        SELECT SUM(tax) AS total_tax, SUM(income_regular_monthly) AS total_regular, SUM(income_irregular_monthly) AS total_irregular,
          SUM(salary) AS total_salary, SUM(medical) AS total_medical, SUM(tax_reduction) AS total_ptkp,
          SUM(jamsostek) AS total_jamsostek,
          MIN(EXTRACT(month FROM t2.\"salaryDate\")) AS start_month, MAX(EXTRACT(month FROM t2.\"salaryDate\")) AS end_month
        FROM hrd_tax_calculation AS t1
        INNER JOIN (
          SELECT * FROM \"hrdSalaryMaster\" WHERE EXTRACT(year FROM \"salaryDate\") = '$strYear'
        ) AS t2 ON t1.id_salary_master = t2.id
        WHERE t1.id_employee = '$strIDEmployee'
        AND t1.id_salary_master IN (
          SELECT id FROM \"hrdSalaryMaster\" WHERE EXTRACT(year FROM \"salaryDate\") = '$strYear'
        )
        
      ";
    $res = $db->execute($strSQL);
    //echo $strSQL;
    while ($row = $db->fetchrow($res)) {
      $GLOBALS['sGaji1'] = $row['total_salary'];
      $GLOBALS['sGaji2'] = $row['total_tax'];
      $GLOBALS['sGaji3'] = $row['total_regular'] - $row['total_tax'] - $row['total_salary'] - $row['total_jamsostek'];
      $GLOBALS['sGaji5'] = $row['total_jamsostek'];
      //if ($row['total_medical'] != 0) $GLOBALS['sGaji6'] = $row['total_medical'];
      $GLOBALS['sGaji7'] = $row['total_regular'];
      $GLOBALS['sGaji8'] = $row['total_irregular'];
      $GLOBALS['sGaji9'] = $GLOBALS['sGaji7'] + $GLOBALS['sGaji8'];
      // PR untuk Jabatan, pertimbangkan join date apakah sudah setahun atau belum
      $fltMaxPosition = TAX_POS_LIMIT;
      $GLOBALS['sGaji10'] = $GLOBALS['sGaji7'] * TAX_POS_RATE;
      if ($GLOBALS['sGaji10'] > $fltMaxPosition) {
        $GLOBALS['sGaji10'] = $fltMaxPosition;
      } else {
        // hanya hitung jika poin 10 belum mencapai maksimal
        $GLOBALS['sGaji11'] = $GLOBALS['sGaji8'] * TAX_POS_RATE;
        if ($GLOBALS['sGaji11'] > $fltMaxPosition) {
          $GLOBALS['sGaji11'] = $fltMaxPosition;
        }
      }
      $GLOBALS['sGaji13'] = $GLOBALS['sGaji10'] + $GLOBALS['sGaji11'] + $GLOBALS['sGaji12'];
      $GLOBALS['sGaji14'] = $GLOBALS['sGaji9'] - $GLOBALS['sGaji13'];
      // PR, pikirkan poin 15 dan 20, karena sifatnya input manual (pajak di kantor sebelumnya, jika kerja < 1 tahun)
      $GLOBALS['sGaji16'] = $GLOBALS['sGaji14'] + $GLOBALS['sGaji14']; // ini masih bingung
      $GLOBALS['sGaji18'] = $GLOBALS['sGaji16'] - $GLOBALS['sGaji17'];
      $GLOBALS['sGaji19'] = $GLOBALS['sGaji2'];
      $GLOBALS['sGaji21'] = $GLOBALS['sGaji19'];
      $strMinMonth = addPrevZero($row['start_month'], 2);
      $strMaxMonth = addPrevZero($row['end_month'], 2);
      $GLOBALS['sBulan1'] = $strMinMonth[0];
      $GLOBALS['sBulan2'] = $strMinMonth[1];
      $GLOBALS['sBulan3'] = $strMaxMonth[0];
      $GLOBALS['sBulan4'] = $strMaxMonth[1];
    }
    for ($i = 1; $i <= 24; $i++) {
      if ($GLOBALS['sGaji' . $i] != "") {
        $GLOBALS['sGaji' . $i] = standardFormat($GLOBALS['sGaji' . $i]);
      }
    }
  }
  $tbsPage = new clsTinyButStrong;
  $tbsPage->NoErr = true;
  $tbsPage->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);
  /*
  $template = "1721A1.ods";
  $strFileName = "1721A1-".$strYear."-".$strEmployeeID.".ods";
  */
  $tbsPage->PlugIn(OPENTBS_SELECT_SHEET, 1);
  $template = "1721A1.xlsx";
  $strFileName = "1721A1-" . $strYear . "-" . $strEmployeeID . ".xlsx";
  $tbsPage->LoadTemplate($template);
  $tbsPage->Show(OPENTBS_DOWNLOAD, $strFileName);
}

?>