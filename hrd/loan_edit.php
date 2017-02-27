<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
//include_once('../includes/krumo/class.krumo.php');
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
if ($_SESSION['sessionUserRole'] < ROLE_ADMIN) {
  redirectPage("loan_list.php");
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intDefaultWidth = 10;
$intDefaultHeight = 3;
$strNow = date($_SESSION['sessionDateSetting']['php_format']);
$dtNow = getdate();
$strMessage = "";
$strMsgClass = "";
$strWordsDataEntry = getWords("data entry");
$strWordsLoanList = getWords("loan list");
$strWordsLoanType = getWords("loan type");
$strWordsLoanPurpose = getWords("loan purpose");
$strWordsEmpID = getWords("employee ID");
$strWordsLoanNo = getWords("loan no");
$strWordsLoanDate = getWords("loan date");
$strWordsLoanType = getWords("loan type");
$strWordsPurpose = getWords("purpose");
$strWordsAmount = getWords("amount");
$strWordsPeriode = getWords("periode");
$strWordsMargin = getWords("margin");
$strWordsMonthlyPayment = getWords("monthly payment");
$strWordsstartPayment = getWords("start payment");
$strWordsLastPayment = getWords("last payment");
$strWordsNote = getWords("note");
$strWordsSave = getWords("save");
$strWordsClear = getWords("clear");
$strWordsMonth = getWords("month");
$arrData = [
    "dataDate"       => $strNow,
    "dataTransfer"   => $strNow,
    "dataPODate"     => $strNow,
    "dataEmployee"   => "",
    "dataNo"         => "1",
    "dataType"       => "",
    "dataPurpose"    => "",
    "dataAmount"     => "0",
    "dataInterest"   => "0",
    "dataPeriode"    => "1",
    "dataPhone"      => "",
    "dataMonthStart" => $dtNow['mon'],
    "dataYearStart"  => $dtNow['year'],
    "dataNote"       => "",
    "dataPO"         => "",
    "dataID"         => "",
];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db, $strDataID = "")
{
  global $words;
  global $arrData;
  if ($strDataID != "") {
    $strSQL = "SELECT t1.*, t2.employee_id, EXTRACT(month FROM payment_from) AS bulan, ";
    $strSQL .= "EXTRACT(year FROM payment_from) AS tahun ";
    $strSQL .= "FROM hrd_loan AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataEmployee'] = $rowDb['employee_id'];
      $arrData['dataNo'] = $rowDb['no'];
      $arrData['dataType'] = $rowDb['loan_type_id'];
      $arrData['dataPurpose'] = $rowDb['purpose'];
      $arrData['dataID'] = $rowDb['id'];
      $arrData['dataAmount'] = $rowDb['amount'];
      $arrData['dataPeriode'] = $rowDb['periode'];
      $arrData['dataInterest'] = $rowDb['interest'];
      $arrData['dataDate'] = $rowDb['loan_date'];
      $arrData['dataTransfer'] = $rowDb['transfer_date'];
      $arrData['dataNote'] = $rowDb['note'];
      $arrData['dataMonthStart'] = $rowDb['bulan'];
      $arrData['dataYearStart'] = $rowDb['tahun'];
      writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$strDataID ->Emp: " . $rowDb['employee_id'], 0);
    }
  }
  return true;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strDataID, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $error;
  global $messages;
  global $arrData;
  $strError = "";
  $bolOK = true;
  $strToday = date("Y-m-d");
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
  (isset($_REQUEST['dataNo'])) ? $strDataNo = $_REQUEST['dataNo'] : $strDataNo = "";
  (isset($_REQUEST['dataType'])) ? $strDataType = $_REQUEST['dataType'] : $strDataType = "";
  (isset($_REQUEST['dataPurpose'])) ? $strDataPurpose = $_REQUEST['dataPurpose'] : $strDataPurpose = "";
  (isset($_REQUEST['dataDate'])) ? $strDataDate = standardDateToSQLDateNew($_REQUEST['dataDate'],$_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']) : $strDataDate = "";
  (isset($_REQUEST['dataAmount'])) ? $strDataAmount = $_REQUEST['dataAmount'] : $strDataAmount = "0";
  (isset($_REQUEST['dataPeriode'])) ? $strDataPeriode = $_REQUEST['dataPeriode'] : $strDataPeriode = "0";
  (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
  (isset($_REQUEST['dataMonthStart'])) ? $strDataMonthStart = $_REQUEST['dataMonthStart'] : $strDataMonthStart = "";
  (isset($_REQUEST['dataYearStart'])) ? $strDataYearStart = $_REQUEST['dataYearStart'] : $strDataYearStart = "";
  (isset($_REQUEST['dataTransfer'])) ? $strDataTransfer = standardDateToSQLDateNew($_REQUEST['dataTransfer'],$_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']) : $strDataTransfer = "";
  (isset($_REQUEST['dataInterest'])) ? $strDataInterest = $_REQUEST['dataInterest'] : $strDataInterest = "0";
  (isset($_REQUEST['dataPODate'])) ? $strDataPODate = standardDateToSQLDateNew($_REQUEST['dataPODate'],$_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']) : $strDataPODate = $strToday;
  // cek validasi -----------------------
  if ($strDataEmployee == "") {
    $strError = $error['empty_code'];
    $bolOK = false;
  } else if (!validStandardDate($strDataDate)) {
    $strError = $error['invalid_date'];
    $bolOK = false;
  } else if (!is_numeric($strDataAmount)) {
    $strError = $error['invalid_number'];
    $bolOK = false;
  } else if (!is_numeric($strDataInterest)) {
    $strError = $error['invalid_number'];
    $bolOK = false;
  } else if (!is_numeric($strDataPeriode)) {
    $strError = $error['invalid_number'];
    $bolOK = false;
  }
  $arrLoanType = createLoanType();
  $strLoanType = $arrLoanType[$strDataType]['type'];
  // cari dta Employee ID, apakah ada atau tidak
  $strIDEmployee = getIDEmployee($db, $strDataEmployee);
  if ($strIDEmployee == "") {
    $strError = $error['data_not_found'];
    $bolOK = false;
  }
  // cari range pembayaran
  /*
  list($thn,$bln,$tgl) = explode("-",$strDataDate);
  if ((int)$tgl < 15) { // mulai bayar bulan ini
    $strDataFrom = "$thn-$bln-01";
  } else { // mulai bayar bulan depan
    if ($bln == 12) {
      $bln = 1;
      $thn++;
    } else {
      $bln++;
    }
    $strDataFrom = "$thn-$bln-01";
  }
  */
  $strDateStart = "$strDataYearStart-$strDataMonthStart-01";
  // simpan data -----------------------
  if ($bolOK) { // input OK, tinggal disimpan
    // handle transfer dan PO date, biar bisa kosong
    $strDataTransfer = ($strDataTransfer == "") ? "NULL" : "'$strDataTransfer'";
    $strDataPODate = ($strDataPODate == "") ? "NULL" : "'$strDataPODate'";
    if ($strDataID == "") {
      // data baru
      $strSQL = "INSERT INTO hrd_loan (created,created_by,modified_by, ";
      $strSQL .= "id_employee,loan_date, amount, periode, interest, note,status, ";
      $strSQL .= "no, type, purpose, ";
      $strSQL .= "transfer_date,";
      $strSQL .= "payment_from, payment_thru), loan_type_id ";
      $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
      $strSQL .= "'$strIDEmployee','$strDataDate', '$strDataAmount', '$strDataPeriode', ";
      $strSQL .= "'$strDataInterest', '$strDataNote', 0, '$strDataNo', ";
      $strSQL .= "'$strLoanType', '$strDataPurpose', ";
      $strSQL .= "$strDataTransfer, '$strDateStart',";
      $strSQL .= "(date '$strDateStart' + interval '" . ($strDataPeriode - 1) . "  month'), $strDataType)";
      $resExec = $db->execute($strSQL);
      // ambil data ID-nya
      $strSQL = "SELECT id FROM hrd_loan WHERE id_employee = '$strIDEmployee' ";
      $strSQL .= "AND loan_date = '$strDataDate' AND no = '$strDataNo' ";
      $strSQL .= "ORDER BY id DESC";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $strDataID = $rowDb['id'];
      }
      writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strIDEmployee", 0);
    } else {
      $strSQL = "UPDATE hrd_loan SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
      $strSQL .= "id_employee = '$strIDEmployee', loan_date = '$strDataDate', ";
      $strSQL .= "no = '$strDataNo', type = '$strLoanType', purpose = '$strDataPurpose', ";
      $strSQL .= "amount = '$strDataAmount', periode = '$strDataPeriode', interest = '$strDataInterest', ";
      $strSQL .= "note = '$strDataNote', payment_from = '$strDateStart', ";
      $strSQL .= "transfer_date = $strDataTransfer, ";
      $strSQL .= "payment_thru = (date '$strDateStart' + interval '" . ($strDataPeriode - 1) . " month'), loan_type_id = '$strDataType' ";
      $strSQL .= "WHERE id = '$strDataID' ";
      $resExec = $db->execute($strSQL);
      writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$strIDEmployee", 0);
    }
    $strError = $messages['data_saved'];
  } else { // ---- data SALAH
    // gunakan data yang diisikan tadi
    $arrData['dataEmployee'] = $strDataEmployee;
    $arrData['dataDate'] = $strDataDate;
    $arrData['dataPeriode'] = $strDataPeriode;
    $arrData['dataAmount'] = $strDataAmount;
    $arrData['dataInterest'] = $strDataInterest;
    $arrData['dataNote'] = $strDataNote;
    $arrData['dataID'] = $strDataID;
    $arrData['dataNo'] = $strDataNo;
    $arrData['dataType'] = $strDataType;
    $arrData['dataPurpose'] = $strDataPurpose;
    //      $arrData['dataProductType'] = $strDataProductType;
    //      $arrData['dataProductCode'] = $strDataProductCode;
    $arrData['dataTransfer'] = $strDataTransfer;
    //$arrData['dataAddress'] = $strDataAddress;
    //$arrData['dataPhone'] = $strDataPhone;
    $arrData['dataMonthStart'] = $strDataMonthStart;
    $arrData['dataYearStart'] = $strDataYearStart;
    //$arrData['dataPO'] = $strDataPO;
    $arrData['dataPODate'] = $strDataPODate;
  }
  return $bolOK;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  if (isset($_REQUEST['btnSave'])) {
    if ($bolCanEdit) {
      $bolOK = saveData($db, $strDataID, $strError);
      if ($strError != "") {
        $strMessage = $strError;
        $strMsgClass = ($bolOK) ? "bgOK" : "bgCancel";
      }
      //$strDataID = ""; // biar tidak mengambil dta, melainkan pakai data baru atau data yang dikirim (jika error)
    }
  }
  if ($bolCanView) {
    getData($db, $strDataID);
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  $strReadonly = (scopeCBDataEntry(
      $arrData['dataEmployee'],
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  )) ? "readonly" : "";
  //----- TAMPILKAN DATA ---------
  $strInputDate = "<input class=\"form-control datepicker\" data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\" type=text size=15 maxlength=10 name=dataDate id=dataDate value=\"" . $arrData['dataDate']. "\">";
  $strInputTransfer = "<input class=\"form-control\" type=text size=15 maxlength=10 name=dataTransfer id=dataTransfer value=\"" . $arrData['dataTransfer'] . "\">";
  $strInputPODate = "<input class=\"form-control datepicker\" data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\" type=text size=15 maxlength=10 name=dataPODate id=dataPODate value=\"" . $arrData['dataPODate']. "\">";
  $strInputEmployee = "<input class=\"form-control\" type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"" . $arrData['dataEmployee'] . "\" $strReadonly>";
  $strInputNo = "<input class=\"form-control\" type=text name=dataNo id=dataNo size=15 maxlength=30 value=\"" . $arrData['dataNo'] . "\" >";
  $strInputPO = "<input class=\"form-control\" type=text name=dataPO id=dataPO size=15 maxlength=30 value=\"" . $arrData['dataPO'] . "\" >";
  $strInputPeriode = "<input class=\"form-control\" type=text name=dataPeriode id=dataPeriode size=30 maxlength=10 value=\"" . $arrData['dataPeriode'] . "\" onChange=\"getMonthlyPayment();getLastMonth();\">";
  $strInputAmount = "<input class=\"form-control\" type=text name=dataAmount id=dataAmount size=30 maxlength=10 value=\"" . $arrData['dataAmount'] . "\" onChange=\"getMonthlyPayment()\">";
  $strInputInterest = "<input class=\"form-control\" type=text name=dataInterest id=dataInterest size=30 maxlength=10 value=\"" . $arrData['dataInterest'] . "\" onChange=\"getMonthlyPayment()\">";
  $strInputNote = "<textarea class=\"form-control\" name=dataNote cols=30 rows=2 wrap='virtual' >" . $arrData['dataNote'] . "</textarea>";
  $strInputType = getLoanTypeList2(
      $db,
      "dataType",
      $arrData['dataType'],
      $strEmptyOption,
      "",
      "style=\"width:$strDefaultWidthPx\""
  );
  $strInputPurpose = getLoanPurposeList(
      $db,
      "dataPurpose",
      $arrData['dataPurpose'],
      $strEmptyOption,
      "",
      "style=\"width:$strDefaultWidthPx\""
  );
  //    $strInputProductType = getLoanProductTypeList($db, "dataProductType", $arrData['dataProductType'], $strEmptyOption,"", "style=\"width:$strDefaultWidthPx\"");
  $strInputStart = getMonthList("dataMonthStart", $arrData['dataMonthStart'], "", "onChange = \"getLastMonth();\";");
  $strInputFinish = getMonthList("dataMonthFinish");
  $strInputStart .= getYearList("dataYearStart", $arrData['dataYearStart'], "", "onChange = \"getLastMonth();\";");
  $strInputFinish .= getYearList("dataYearFinish");
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('loan entry form');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = loanSubMenu($strWordsDataEntry);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>