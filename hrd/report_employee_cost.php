<?php
session_start();
include_once('global.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  header("location:login.php?dataPage=report_employee_cost.php");
  exit();
}
$bolCanView = getUserPermission("report_employee_cost.php", $bolCanEdit, $bolCanDelete, $strError);
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintAll']));
if ($bolPrint) {
  $strMainTemplate = getTemplate("reportEmployeeCostPrint.html");
} else {
  $strTemplateFile = getTemplate("report_employee_cost.html");
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strPaging = "";
$strHidden = "";
$strBtnPrint = "";
$strBtnSave = "";
$intTotalData = 0;
$bolLimit = true; // default, tampilan dibatasi (paging)
$strYear = date("Y");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getData(
    $db,
    $strDateFrom,
    $strDateThru,
    &$intRows,
    $strKriteria = "",
    $intPage = 1,
    $bolLimit = true,
    $strOrder = ""
) {
  global $words;
  global $bolPrint;
  global $ARRAY_EMPLOYEE_STATUS;
  global $strPaging;
  global $intTotalData;
  global $intRowsLimit;
  global $strDataYear;
  $intRowsLimit = getSetting("rows_per_page");
  if (!is_numeric($intRowsLimit)) {
    $intRowsLimit = 50;
  }
  $intRows = 0;
  $strResult = "";
  // cari total data
  $intTotal = 0;
  $strSQL = "SELECT count(id) AS total FROM hrd_employee ";
  $strSQL .= "WHERE active = 1 AND flag = 0 $strKriteria "; // hanya ambil yang statusnya permanent
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    if (is_numeric($rowDb['total'])) {
      $intTotal = $rowDb['total'];
    }
  }
  $strPaging = getPaging($intPage, $intTotal, "javascript:goPage('[PAGE]')");
  if ($strPaging == "") {
    $strPaging = "1&nbsp;";
  }
  $intStart = (($intPage - 1) * $intRowsLimit);
  $strSQL = "SELECT * FROM hrd_employee WHERE active = 1 AND flag = 0 ";
  $strSQL .= " $strKriteria ORDER BY $strOrder employee_id ";
  if ($bolLimit) {
    $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
    // inisialisasi
    $fltSalary = 0;
    $fltConjuncture = 0;
    $fltTiras = 0;
    $fltTrip = 0;
    $fltOT = 0; // uang makan lembur
    $fltTraining = 0;
    $fltMedical = 0;
    $fltTotal = 0;
    // cari data gaji karyawan
    $strSQL = "SELECT SUM(t1.total_gross_round) AS gaji, SUM(t1.conjuncture) AS conj FROM hrd_salary_detail AS t1, ";
    $strSQL .= "hrd_salary_master AS t2 WHERE t1.id_salary_master = t2.id ";
    $strSQL .= "AND t1.id_employee = '" . $rowDb['id'] . "' ";
    $strSQL .= "AND t2.date_thru BETWEEN '$strDateFrom' AND '$strDateThru' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $fltSalary = $rowTmp['gaji'];
      $fltConjuncture = $rowTmp['conj'];
    }
    // cari data tiras
    $strSQL = "SELECT SUM(t1.\"approved_cost\") AS total FROM hrd_tiras AS t1, ";
    $strSQL .= "hrd_tiras_master AS t2 WHERE t1.id_master = t2.id  AND t2.status = " . REQUEST_STATUS_APPROVED . " ";
    $strSQL .= "AND t2.id_employee = '" . $rowDb['id'] . "' ";
    $strSQL .= "AND t2.payment_date  BETWEEN '$strDateFrom' AND '$strDateThru' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $fltTiras = $rowTmp['total'];
    }
    // cari data medis
    $strSQL = "SELECT SUM(amount) AS total FROM hrd_cash_request  AS t1, ";
    $strSQL .= "hrd_medical_claim_master AS t2 WHERE t1.source_id = t2.id AND t1.\"type\" = 1 ";
    $strSQL .= "AND t2.id_employee = '" . $rowDb['id'] . "' ";
    $strSQL .= "AND t1.request_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $fltMedical = $rowTmp['total'];
    }
    // cari data OT
    $strSQL = "SELECT SUM(t1.amount) AS total FROM hrd_cash_request AS t1, ";
    $strSQL .= "hrd_overtime AS t2 WHERE t1.source_id = t2.id AND t1.\"type\" = 2 ";
    $strSQL .= "AND t2.id_employee = '" . $rowDb['id'] . "' ";
    $strSQL .= "AND t1.request_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $fltOT = $rowTmp['total'];
    }
    // cari data business trip
    $strSQL = "SELECT SUM(t1.\"totalAmount\") AS total FROM hrd_trip_payment AS t1 ";
    $strSQL .= "WHERE t1.id_employee = '" . $rowDb['id'] . "' ";
    $strSQL .= "AND t1.payment_date  BETWEEN '$strDateFrom' AND '$strDateThru' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $fltTrip = $rowTmp['total'];
    }
    // cari data training
    $strSQL = "SELECT SUM(t1.cost) AS total FROM hrd_training_request_participant AS t1, ";
    $strSQL .= "hrd_training_request AS t2 WHERE t1.id_request = t2.id  AND t2.status = " . REQUEST_STATUS_APPROVED . " ";
    $strSQL .= "AND t1.id_employee = '" . $rowDb['id'] . "' ";
    $strSQL .= "AND t2.training_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $fltTraining = $rowTmp['total'];
    }
    $fltTotal = $fltSalary + $fltConjuncture + $fltTiras + $fltTrip + $fltOT + $fltTraining + $fltMedical;
    $strResult .= "<tr valign=top title=\"$strEmployeeInfo\">\n";
    if (!$bolPrint) {
      $strResult .= "  <td><input type=checkbox name=chkID$intRows value=\"" . $rowDb['id'] . "\"></td>";
    }
    $strResult .= "  <td nowrap>" . $rowDb['employee_id'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap>" . $rowDb['employee_name'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap>" . $rowDb['department_code'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap>" . $rowDb['section_code'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap>" . $rowDb['function'] . "&nbsp;</td>";
    $strResult .= "  <td align=right>" . standardFormat($fltSalary) . "</td>";
    $strResult .= "  <td align=right>" . standardFormat($fltConjuncture) . "</td>";
    $strResult .= "  <td align=right>" . standardFormat($fltTiras) . "</td>";
    $strResult .= "  <td align=right>" . standardFormat($fltMedical) . "</td>";
    $strResult .= "  <td align=right>" . standardFormat($fltOT) . "</td>";
    $strResult .= "  <td align=right>" . standardFormat($fltTrip) . "</td>";
    $strResult .= "  <td align=right>" . standardFormat($fltTraining) . "</td>";
    $strResult .= "  <td align=right>" . standardFormat($fltTotal) . "</td>";
    $strResult .= "</tr>\n";
  }
  $intTotalData = $intRows;
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  return $strResult;
} // showData
// menampilkan data, tapi langsung di format excel
function getDataExcel(
    $db,
    $strDateFrom,
    $strDateThru,
    &$intRows,
    $strKriteria = "",
    $intPage = 1,
    $bolLimit = true,
    $strOrder = ""
) {
  include_once("../global/class.excelExport.php");
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $strDataYear;
  $arrHeader = [];
  $arrData = [];
  // buat dulu table headernya
  $col = 0;
  $arrHeader[$col++] = ["text" => strtoupper(getWords("no")), "type" => "numeric", "width" => 5];
  $arrHeader[$col++] = ["text" => strtoupper(getWords("employee id")), "type" => "", "width" => 12];
  $arrHeader[$col++] = ["text" => strtoupper(getWords("employee name")), "type" => "", "width" => 17];
  $arrHeader[$col++] = ["text" => strtoupper(getWords("department")), "type" => "", "width" => 8];
  $arrHeader[$col++] = ["text" => strtoupper(getWords("section")), "type" => "", "width" => 8];
  $arrHeader[$col++] = ["text" => strtoupper(getWords("functional")), "type" => "", "width" => 8];
  $arrHeader[$col++] = ["text" => strtoupper(getWords("salary")), "type" => "numeric", "width" => 10];
  $arrHeader[$col++] = ["text" => strtoupper(getWords("conjuncture")), "type" => "numeric", "width" => 10];
  $arrHeader[$col++] = ["text" => strtoupper(getWords("tiras")), "type" => "numeric", "width" => 10];
  $arrHeader[$col++] = ["text" => strtoupper(getWords("medical")), "type" => "numeric", "width" => 10];
  $arrHeader[$col++] = ["text" => strtoupper(getWords("ot claim")), "type" => "numeric", "width" => 10];
  $arrHeader[$col++] = ["text" => strtoupper(getWords("business trip")), "type" => "numeric", "width" => 10];
  $arrHeader[$col++] = ["text" => strtoupper(getWords("training")), "type" => "numeric", "width" => 10];
  $arrHeader[$col++] = ["text" => strtoupper(getWords("total")), "type" => "numeric", "width" => 10];
  $strSQL = "SELECT * FROM hrd_employee WHERE active = 1 AND flag = 0 ";
  $strSQL .= " $strKriteria ORDER BY $strOrder employee_id ";
  $resDb = $db->execute($strSQL);
  $intRows = 0;
  while ($rowDb = $db->fetchrow($resDb)) {
    $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
    // inisialisasi
    $fltSalary = 0;
    $fltConjuncture = 0;
    $fltTiras = 0;
    $fltTrip = 0;
    $fltOT = 0; // uang makan lembur
    $fltTraining = 0;
    $fltMedical = 0;
    $fltTotal = 0;
    // cari data gaji karyawan
    $strSQL = "SELECT SUM(t1.total_gross_round) AS gaji, SUM(t1.conjuncture) AS conj FROM hrd_salary_detail AS t1, ";
    $strSQL .= "hrd_salary_master AS t2 WHERE t1.id_salary_master = t2.id ";
    $strSQL .= "AND t1.id_employee = '" . $rowDb['id'] . "' ";
    $strSQL .= "AND t2.date_thru BETWEEN '$strDateFrom' AND '$strDateThru' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $fltSalary = $rowTmp['gaji'];
      $fltConjuncture = $rowTmp['conj'];
    }
    // cari data tiras
    $strSQL = "SELECT SUM(t1.\"approved_cost\") AS total FROM hrd_tiras AS t1, ";
    $strSQL .= "hrd_tiras_master AS t2 WHERE t1.id_master = t2.id  AND t2.status = " . REQUEST_STATUS_APPROVED . " ";
    $strSQL .= "AND t2.id_employee = '" . $rowDb['id'] . "' ";
    $strSQL .= "AND t2.payment_date  BETWEEN '$strDateFrom' AND '$strDateThru' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $fltTiras = $rowTmp['total'];
    }
    // cari data medis
    $strSQL = "SELECT SUM(amount) AS total FROM hrd_cash_request  AS t1, ";
    $strSQL .= "hrd_medical_claim_master AS t2 WHERE t1.source_id = t2.id AND t1.\"type\" = 1 ";
    $strSQL .= "AND t2.id_employee = '" . $rowDb['id'] . "' ";
    $strSQL .= "AND t1.request_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $fltMedical = $rowTmp['total'];
    }
    // cari data OT
    $strSQL = "SELECT SUM(t1.amount) AS total FROM hrd_cash_request AS t1, ";
    $strSQL .= "hrd_overtime AS t2 WHERE t1.source_id = t2.id AND t1.\"type\" = 2 ";
    $strSQL .= "AND t2.id_employee = '" . $rowDb['id'] . "' ";
    $strSQL .= "AND t1.request_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $fltOT = $rowTmp['total'];
    }
    // cari data business trip
    $strSQL = "SELECT SUM(t1.\"totalAmount\") AS total FROM hrd_trip_payment AS t1 ";
    $strSQL .= "WHERE t1.id_employee = '" . $rowDb['id'] . "' ";
    $strSQL .= "AND t1.payment_date  BETWEEN '$strDateFrom' AND '$strDateThru' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $fltTrip = $rowTmp['total'];
    }
    // cari data training
    $strSQL = "SELECT SUM(t1.cost) AS total FROM hrd_training_request_participant AS t1, ";
    $strSQL .= "hrd_training_request AS t2 WHERE t1.id_request = t2.id  AND t2.status = " . REQUEST_STATUS_APPROVED . " ";
    $strSQL .= "AND t1.id_employee = '" . $rowDb['id'] . "' ";
    $strSQL .= "AND t2.training_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $fltTraining = $rowTmp['total'];
    }
    $fltTotal = $fltSalary + $fltConjuncture + $fltTiras + $fltTrip + $fltOT + $fltTraining + $fltMedical;
    $col = 0;
    $arrData[$intRows][$col++] = ($intRows + 1);
    $arrData[$intRows][$col++] = $rowDb['employee_id'];
    $arrData[$intRows][$col++] = $rowDb['employee_name'];
    $arrData[$intRows][$col++] = $rowDb['department_code'];
    $arrData[$intRows][$col++] = $rowDb['section_code'];
    $arrData[$intRows][$col++] = $rowDb['function'];
    $arrData[$intRows][$col++] = $fltSalary;
    $arrData[$intRows][$col++] = $fltConjuncture;
    $arrData[$intRows][$col++] = $fltTiras;
    $arrData[$intRows][$col++] = $fltMedical;
    $arrData[$intRows][$col++] = $fltOT;
    $arrData[$intRows][$col++] = $fltTrip;
    $arrData[$intRows][$col++] = $fltTraining;
    $arrData[$intRows][$col++] = $fltTotal;
    $intRows++;
  }
  $intTotalData = $intRows;
  // tampilkan file excel
  global $strInfo;
  $objExl = new CxlsExport("employee.xls");
  $objExl->setHeaders("EMPLOYEE COST", $strInfo, "");
  $objExl->setData($arrHeader, $arrData);
  $objExl->showExcel();
  if ($intRows > 0) {
    writeLog(ACTIVITY_EXPORT, MODULE_PAYROLL, "", 0);
  }
  return "";
} // showDataExcel
// fungsi mengambil periode tanggal, berdasarkan periode bulan yagn diberikan
// output berupa array
function getDatePeriode($strMonthFrom, $strYearFrom, $strMonthThru, $strYearThru)
{
  $arrResult['date_from'] = null;
  $arrResult['date_thru'] = null;
  $arrResult['date_from'] = "$strYearFrom-$strMonthFrom-01";
  if ($strMonthThru == 12) {
    $strTmp = ($strYearThru + 1) . "-01-01";
  } else {
    $strTmp = "$strYearThru-$strMonthThru-01";
  }
  $arrResult['date_thru'] = getNextDate($strTmp, -1);
  return $arrResult;
} //getDatePeriode
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  $dtNow = getdate();
  // ------ AMBIL DATA KRITERIA -------------------------
  $strDataemployee_id = (isset($_REQUEST['dataemployee_id'])) ? trim($_REQUEST['dataemployee_id']) : "";
  $strDataDepartment = (isset($_REQUEST['dataDepartment'])) ? $_REQUEST['dataDepartment'] : "";
  $strDataSection = (isset($_REQUEST['dataSection'])) ? $_REQUEST['dataSection'] : "";
  $intCurrPage = (isset($_REQUEST['dataPage'])) ? $_REQUEST['dataPage'] : 1;
  $intMonthFrom = (isset($_REQUEST['dataMonthFrom'])) ? $_REQUEST['dataMonthFrom'] : $dtNow['mon'];
  $intMonthThru = (isset($_REQUEST['dataMonthThru'])) ? $_REQUEST['dataMonthThru'] : $dtNow['mon'];
  $intYearFrom = (isset($_REQUEST['dataYearFrom'])) ? $_REQUEST['dataYearFrom'] : $dtNow['year'];
  $intYearThru = (isset($_REQUEST['dataYearThru'])) ? $_REQUEST['dataYearThru'] : $dtNow['year'];
  $arrPeriode = getDatePeriode($intMonthFrom, $intYearFrom, $intMonthThru, $intYearThru);
  if (!is_numeric($intCurrPage)) {
    $intCurrPage = 1;
  }
  $strBtnPrint = "<input type=button name='btnPrint' value=\"" . $words['print'] . "\" onClick=\"printData($intCurrPage);\">";
  $strBtnSave = "<input type=submit name='btnSave' value=\"" . $words['save'] . "\">";
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if (isset($_REQUEST['btnShowAll']) || isset($_REQUEST['btnPrintAll'])) {
    $strKriteria = "";
    $bolLimit = false;
  } else if (isset($_REQUEST['btnShow']) || isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel'])) {
    $strInfoKriteria = "";
    if ($strDataemployee_id != "") {
      $strKriteria .= "AND upper(employee_id) like '%" . strtoupper($strDataemployee_id) . "%' ";
    }
    if ($strDataDepartment != "") {
      $strKriteria .= "AND department_code = '$strDataDepartment' ";
    }
    if ($strDataSection != "") {
      $strKriteria .= "AND section_code = '$strDataSection' ";
    }
  } else { // jngan tampilkan data
    $strKriteria .= "AND 1=2 ";
    $strBtnPrint = ""; // tidak perlu tampil
    $strBtnSave = ""; // tidak perlu tampil
  }
  if ($intMonthFrom == $intMonthThru && $intYearFrom == $intYearThru) {
    $strInfo = getBulan($intMonthFrom) . " " . $intYearFrom;
  } else {
    $strInfo = getBulan($intMonthFrom) . " " . $intYearFrom . " - ";
    $strInfo .= getBulan($intMonthThru) . " " . $intYearThru;
  }
  if ($bolCanView) {
    if (isset($_REQUEST['btnExcel'])) {
      $strDataDetail = getDataExcel(
          $db,
          $arrPeriode['date_from'],
          $arrPeriode['date_thru'],
          $intTotalData,
          $strKriteria,
          $intCurrPage,
          $bolLimit
      );
    } else {
      $strDataDetail = getData(
          $db,
          $arrPeriode['date_from'],
          $arrPeriode['date_thru'],
          $intTotalData,
          $strKriteria,
          $intCurrPage,
          $bolLimit
      );
    }
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  // generate data hidden input dan element form input
  $intDefaultWidth = 30;
  $intDefaultWidthPx = 200;
  $intDefaultHeight = 3;
  $strInputEmployee = "<input type=text name=dataemployee_id id=dataemployee_id size=$intDefaultWidth value=\"$strDataemployee_id\" style=\"width:$intDefaultWidthPx\"width:$intDefaultWidthPx\">";
  $strInputDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $strDataDepartment,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\""
  );
  $strInputSection = getSectionList(
      $db,
      "dataSection",
      $strDataSection,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\""
  );
  $strInputFrom = getMonthList("dataMonthFrom", $intMonthFrom);
  $strInputFrom .= getYearList("dataYearFrom", $intYearFrom);
  $strInputThru = getMonthList("dataMonthThru", $intMonthThru);
  $strInputThru .= getYearList("dataYearThru", $intYearThru);
  $strHidden .= "<input type=hidden name=dataemployee_id value=\"$strDataemployee_id\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
  $strHidden .= "<input type=hidden name=dataMonthFrom value=\"$intMonthFrom\">";
  $strHidden .= "<input type=hidden name=dataMonthThru value=\"$intMonthThru\">";
  $strHidden .= "<input type=hidden name=dataYearFrom value=\"$intYearFrom\">";
  $strHidden .= "<input type=hidden name=dataYearThru value=\"$intYearThru\">";
  $strHidden .= "<input type=hidden name=dataPage value=\"$intCurrPage\">";
  $strHidden .= "<input type=hidden name=dataTotal value=\"$intTotalData\">";
}
$strInitAction .= " init();
    onCodeBlur();
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>