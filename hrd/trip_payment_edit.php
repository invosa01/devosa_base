<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  header("location:login.php?dataPage=trip_payment_edit.php");
  exit();
}
$bolCanView = getUserPermission("trip_payment_edit.php", $bolCanEdit, $bolCanDelete, $strError);
$bolPrint = (isset($_REQUEST['btnPrint']));
($bolPrint) ? $strMainTemplate = getTemplate("trip_payment_print.html", false) : $strTemplateFile = getTemplate(
    "trip_payment_edit.html"
);
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strButtons = "";
$intTotalData = 0;
$strInitCalendar = "";
$strMessages = "";
$strMsgClass = "";
$bolError = false;
$arrData = [
    "dataNo"            => "",       // komponen kode
    "dataCode"          => "USAPD-HRD", // default
    "dataYear"          => "",
    "dataMonth"         => "",
    "dataEmployee"      => "",
    "dataemployee_name" => "",
    "dataMethod"        => "",
    "dataAccount"       => "",
    "dataBudget"        => "",
    "dataDate"          => date("Y-m-d"),
    "dataPayment"       => date("Y-m-d"),
    //"dataPaymentStatus" => "0",
    "dataStatus"        => "0",
    "dataLocation"   => "",
    "dataPurpose"    => "",
    "dataTask"       => "",
    "dataBudget"     => "",
    "dataDateFrom"   => "",
    "dataDateThru"   => "",
    "dataDuration"   => "0",
    "dataAllowance"  => "0",
    "dataTotalOther" => "0", // total biaya lain-lain
    "dataTotal"      => "0", // total uang saku dan lain-lain
    "dataCurrType"   => "0", // jenis currency, 0:IDR, 1:USD
];
$strInputPaymentDate = "";
$strInputNo = "";
$strInputEmployee = "";
$strInputMethod = "";
$strInputAccount = "";
$strInputPaymentStatus = "";
$strInputStatus = "";
$strInputLastNo = "";
$strInputLocation = "";
$strInputPurpose = "";
$strInputTripDate = "";
$strInputDuration = "";
$strInputAllowance = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strDataID = "")
{
  global $words;
  global $intDefaultWidth;
  global $strEmptyOption;
  global $arrData;
  global $bolIsEmployee;
  global $bolPrint;
  $intMaxShow = 4; // tambahan yang perlu dimunculkan
  $intAdd = 10; // maksimum tambahan
  $intRows = 0;
  $intShown = 0;
  $strResult = "";
  $dtNow = getdate();
  $strReadonly = ($bolIsEmployee) ? "readonly" : "";
  if ($strDataID != "") {
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name FROM hrd_trip_payment AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t2.id = t1.id_employee ";
    $strSQL .= "WHERE t1.id = '$strDataID' ";
    $resM = $db->execute($strSQL);
    if ($rowM = $db->fetchrow($resM)) {
      $arrData['dataEmployee'] = $rowM['employee_id'];
      $arrData['dataemployee_name'] = $rowM['employee_name'];
      $arrData['dataNo'] = $rowM['no'];
      $arrData['dataCode'] = $rowM['code'];
      $arrData['dataMonth'] = $rowM['month_code'];
      $arrData['dataYear'] = $rowM['year_code'];
      $arrData['dataMethod'] = $rowM['method'];
      $arrData['dataAccount'] = $rowM['accountNo'];
      $arrData['dataBudget'] = $rowM['budgetCode'];
      $arrData['dataPayment'] = $rowM['paymentDate'];
      $arrData['dataDate'] = $rowM['request_date'];
      $arrData['dataStatus'] = $rowM['status'];
      $strSQL = "SELECT * FROM hrd_trip_payment_other WHERE id_trip_payment  = '" . $rowM['id'] . "' ";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $intShown++;
        if ($intRows == 1) // cek pilihan currency
        {
          $arrData['dataCurrType'] = ($rowDb['amountOther'] != 0) ? 1 : 0;
        }
        $fltAmount = ($arrData['dataCurrType'] == 0) ? $rowDb['amount'] : $rowDb['amountOther'];
        $arrData['dataTotalOther'] += $fltAmount;
        if ($bolPrint) {
          $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
          $strResult .= "  <td align=right>$intRows&nbsp;</td>";
          $strResult .= "  <td>&nbsp;" . $rowDb['note'] . "</td>";
          $strResult .= "  <td nowrap align=right>" . standardFormat($fltAmount) . "&nbsp;</td>";
          $strResult .= "</tr>\n";
        } else {
          $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
          $strResult .= "  <td align=right><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">$intRows&nbsp;</td>";
          $strResult .= "  <td><input type=text size=50 maxlength=90 name=detailNote$intRows value=\"" . $rowDb['note'] . "\"></td>";
          $strResult .= "  <td nowrap><input type=text size=15 maxlength=20 name=detailCost$intRows value=\"" . (float)$rowDb['amount'] . "\" class=numeric onChange=\"onCostChanged($intRows);\" class='numeric'></td>";
          $strResult .= "  <td nowrap><input type=text size=15 maxlength=20 name=detailCostOther$intRows value=\"" . (float)$rowDb['amountOther'] . "\" class=numeric onChange=\"onCostChanged($intRows, 'Other');\" class='numeric'></td>";
          $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
          $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' $strAction></td>\n";
          $strResult .= "</tr>\n";
        }
      }
      writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
  }
  $arrData['dataTotal'] += $arrData['dataTotalOther'];
  // tambahkan dengan data kosong
  if (!$bolPrint) {
    for ($i = 1; $i <= $intAdd; $i++) {
      $intRows++;
      if ($intRows <= $intMaxShow) {
        $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
        $intShown++;
        $strDisabled = "";
      } else {
        $strResult .= "<tr valign=top  id=\"detailRows$intRows\" style=\"display:none\">\n";
        $strDisabled = "disabled";
      }
      $strResult .= "  <td align=right>$intRows&nbsp;</td>";
      $strResult .= "  <td><input type=text size=50 maxlength=90 name=detailNote$intRows value=''></td>";
      $strResult .= "  <td nowrap align=right><input type=text size=15 maxlength=20 name=detailCost$intRows $strDisabled value=0 class=numeric onChange=\"onCostChanged($intRows);\" class='numeric'></td>";
      $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
      $strResult .= "  <td nowrap align=right><input type=text size=15 maxlength=20 name=detailCostOther$intRows $strDisabled value=0 class=numeric onChange=\"onCostChanged($intRows, 'Other');\" class='numeric'></td>";
      $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
      $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' $strAction></td>\n";
      $strResult .= "</tr>\n";
    }
  }
  // tambahkan hidden data
  $strResult .= "<input type=hidden name=maxDetail value=$intRows>";
  $strResult .= "<input type=hidden name=numShow value=$intShown>";
  return $strResult;
} // showData
// fungsi untuk mengambil data trip berdasarkan ID
// sekaligus ngecek apakah data payment form dah ada, return data ID dari payment form
function getTripData($db, $strTripID = "")
{
  global $arrData;
  $strResult = "";
  if ($strTripID == "") {
    return "";
  }
  $strSQL = "SELECT t1.*, (date_thru - date_from) AS durasi, t2.employee_id FROM hrd_trip AS t1 ";
  $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
  $strSQL .= "WHERE t1.id = '$strTripID' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $arrData['dataLocation'] = $rowDb['location'];
    $arrData['dataPurpose'] = $rowDb['purpose'];
    $arrData['dataTask'] = $rowDb['task'];
    $arrData['dataEmployee'] = $rowDb['employee_id'];
    $arrData['dataDateFrom'] = $rowDb['date_from'];
    $arrData['dataDateThru'] = $rowDb['date_thru'];
    $arrData['dataAllowance'] = $rowDb['totalAllowance'];
    $arrData['dataDuration'] = $rowDb['durasi'] + 1;
    //$arrData['dataDuration'] = totalWorkDay($db,$rowDb['date_from'],$rowDb['date_thru']);
  }
  // cari apakah ada payment
  $strSQL = "SELECT id FROM hrd_trip_payment WHERE id_trip = '$strTripID' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strResult = $rowDb['id'];
  }
  $arrData['dataTotal'] += $arrData['dataAllowance'];
  return $strResult;
}// getTripData
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $messages;
  global $error;
  global $strDataID;
  global $intFormNumberDigit;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strError = "";
  $bolOK = true;
  $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
  $strDataTripID = (isset($_REQUEST['dataTripID'])) ? $_REQUEST['dataTripID'] : "";
  $strDataCode = (isset($_REQUEST['dataCode'])) ? trim($_REQUEST['dataCode']) : "";
  $strDataNo = (isset($_REQUEST['dataNo'])) ? trim($_REQUEST['dataNo']) : "";
  $strDataMonth = (isset($_REQUEST['dataMonth'])) ? trim($_REQUEST['dataMonth']) : "";
  $strDataYear = (isset($_REQUEST['dataYear'])) ? trim($_REQUEST['dataYear']) : "";
  $strDataEmployee = (isset($_REQUEST['dataEmployee'])) ? trim($_REQUEST['dataEmployee']) : "";
  $strDataMethod = (isset($_REQUEST['dataMethod'])) ? $_REQUEST['dataMethod'] : "0";
  $strDataAccount = (isset($_REQUEST['dataAccount'])) ? $_REQUEST['dataAccount'] : "";
  $strDataBudget = (isset($_REQUEST['dataBudget'])) ? $_REQUEST['dataBudget'] : "";
  $strDataDate = (isset($_REQUEST['dataDate'])) ? $_REQUEST['dataDate'] : date("Y-m-d");
  $strDataPaymentDate = (isset($_REQUEST['dataPaymentDate'])) ? $_REQUEST['dataPaymentDate'] : date("Y-m-d");
  $strDataStatus = (isset($_REQUEST['dataStatus'])) ? $_REQUEST['dataStatus'] : "0";
  $strDataTotalAmount = (isset($_REQUEST['totalCost'])) ? $_REQUEST['totalCost'] : "0";
  $strDataTotalAmountOther = (isset($_REQUEST['totalCostOther'])) ? $_REQUEST['totalCostOther'] : "0";
  //$strDataPaymentStatus = (isset($_REQUEST['dataPaymentStatus'])) ? $_REQUEST['dataPaymentStatus'] : "0";
  // validasi data
  if (!validStandardDate($strDataDate)) {
    $strDataDate = date("Y-m-d");
  } else if (!validStandardDate($strDataPaymentDate)) {
    $strDataPaymentDate = date("Y-m-d");
  }
  // cek masalah kode/nomor form
  if ($strDataNo == "") {
    $strError = $error['empty_code'];
    $bolOK = false;
  } else {
    ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
    $strNumber = "$strDataCode-$strDataNo/$strDataMonth/$strDataYear";
    //if (isDataExists("hrdTripPayment","claimNumber",$strDataNo,$strKriteria)) {
    //  $strError = $error['duplicate_code']. "  -> $strDataNo";
    //  $bolOK = false;
    //}
  }
  // cek apakah ada data emmployee
  if ($strDataEmployee == "") {
    $bolOK = false;
    $strError = $error['empty_data'];
  } else {
    $strSQL = "SELECT id FROM hrd_employee WHERE employee_id = '$strDataEmployee' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strIDEmployee = $rowDb['id'];
    } else {
      $bolOK = false;
      $strError = $error['data_not_found'];
    }
  }
  if ($bolOK && $strIDEmployee != "") {
    if ($strDataID == "") { // buat baru
      // cek dulu, apakah data sudah yang terakhir
      $strTmp = getLastFormNumber($db, "hrdTripPayment", "no", $strDataMonth, $strDataYear);
      $intTmp = ($strTmp == "") ? 0 : (int)$strTmp;
      $intKode = (int)$strDataNo;
      if ($intKode <= $intTmp) { // sudah ada yang lebih besar atau sama, ganti
        $intKode = $intTmp + 1;
        $strDataNo = addPrevZero($intKode, $intFormNumberDigit);
      }
      $strSQL = "INSERT INTO hrd_trip_payment (created, modified_by, created_by, id_employee, ";
      $strSQL .= "code, no, month_code, year_code, method, account_no, ";
      $strSQL .= "budget_code, request_date, payment_date , status, id_trip, \"totalAmount\", total_amount_other) ";
      $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '$strIDEmployee', ";
      $strSQL .= "'$strDataCode', '$strDataNo', '$strDataMonth', '$strDataYear', ";
      $strSQL .= "'$strDataMethod', '$strDataAccount', '$strDataBudget', '$strDataDate', ";
      $strSQL .= "'$strDataPaymentDate', '$strDataStatus', '$strDataTripID', '$strDataTotalAmount', '$strDataTotalAmountOther') ";
      $resExec = $db->execute($strSQL);
      // cari IDnya
      $strSQL = "SELECT id FROM hrd_trip_payment WHERE code = '$strDataCode' ";
      $strSQL .= "AND no = '$strDataNo' AND month_code = '$strDataMonth' AND year_code = '$strDataYear' ";
      $strSQL .= "AND id_employee = '$strIDEmployee' AND status = '$strDataStatus' ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $strDataID = $rowDb['id'];
      }
    } else {
      $strSQL = "UPDATE hrd_trip_payment SET modified_by = '$strmodified_byID', ";
      $strSQL .= "id_employee = '$strIDEmployee', ";
      $strSQL .= "code = '$strDataCode', no = '$strDataNo', ";
      $strSQL .= "month_code = '$strDataMonth', year_code = '$strDataYear', ";
      $strSQL .= "method = '$strDataMethod', account_no = '$strDataAccount', ";
      $strSQL .= "request_date = '$strDataDate', payment_date  = '$strDataPaymentDate', ";
      $strSQL .= "budget_code = '$strDataBudget', status = '$strDataStatus', ";
      $strSQL .= "\"totalAmount\" = '$strDataTotalAmount', total_amount_other = '$strDataTotalAmountOther' ";
      $strSQL .= "WHERE id = '$strDataID' ";
      $resExec = $db->execute($strSQL);
    }
    if ($strDataID != "") { // simpan detail claim yang dilakukan
      (isset($_REQUEST['maxDetail'])) ? $intMax = $_REQUEST['maxDetail'] : $intMax = 0;
      for ($i = 1; $i <= $intMax; $i++) {
        (isset($_REQUEST['detailID' . $i])) ? $strID = $_REQUEST['detailID' . $i] : $strID = "";
        $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
        (isset($_REQUEST['detailCost' . $i])) ? $strCost = $_REQUEST['detailCost' . $i] : $strCost = "0";
        (isset($_REQUEST['detailCostOther' . $i])) ? $strCostOther = $_REQUEST['detailCostOther' . $i] : $strCostOther = "0";
        if (!is_numeric($strCost)) {
          $strCost = 0;
        }
        if (!is_numeric($strCostOther)) {
          $strCostOther = 0;
        }
        if ($strID == "") {
          if ($strNote != "") { // insert new data
            $strSQL = "INSERT INTO hrd_trip_payment_other (created,modified_by, created_by, ";
            $strSQL .= "id_trip_payment, note, amount, amount_other) ";
            $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '$strDataID', ";
            $strSQL .= "'$strNote', '$strCost', '$strCostOther') ";
            $resDb = $db->execute($strSQL);
            writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataID", 0);
          }
        } else {
          if ($strNote == "") {
            // delete data
            $strSQL = "DELETE FROM hrd_trip_payment_other WHERE id = '$strID' ";
            $resDb = $db->execute($strSQL);
            writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$strDataID", 0);
          } else {
            // update data
            $strSQL = "UPDATE hrd_trip_payment_other SET modified_by = '$strmodified_byID', ";
            $strSQL .= "note = '$strNote', amount = '$strCost', amount_other = '$strCostOther' ";
            $strSQL .= "WHERE id = '$strID' ";
            $resDb = $db->execute($strSQL);
            writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataID", 0);
          }
        } // if
      } // for
    } //if
    $strError = $messages['data_saved'] . " >> " . date("d-M-Y H:i:s");
    return true;
  } else {
    return false;
  }
  return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
  $strDataTripID = (isset($_REQUEST['dataTripID'])) ? $_REQUEST['dataTripID'] : "";
  //if ($_SESSION['sessionUserRole'] != ROLE_SUPERVISOR && $_SESSION['sessionUserRole'] != ROLE_ADMIN) {
  //  $bolCanView = $bolCanEdit = false;
  //}
  // baca dulu default buat nomor baru
  $dtNow = getdate(); // default berdasar hari ini
  $arrData['dataMonth'] = getRomans($dtNow['mon']);
  $arrData['dataYear'] = $dtNow['year'];
  $strInputLastNo = getLastFormNumber($db, "hrdTripPayment", "no", $arrData['dataMonth'], $arrData['dataYear']);
  $intLastNo = ($strInputLastNo == "") ? 0 : (int)$strInputLastNo;
  $arrData['dataNo'] = addPrevZero($intLastNo + 1, $intFormNumberDigit);
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      if (!saveData($db, $strError)) {
        //echo "<script>alert(\"$strError\")/script>";
        $bolError = true;
      }
      if ($strError != "") {
        $strMessages = $strError;
        $strMsgClass = ($bolError) ? "class=bgError" : "class=bgOK";
      }
    }
  }
  if ($bolCanView) {
    $strDataID = getTripData($db, $strDataTripID); // mengambil data trip, sekaligus ID dari payment jika sudah ada
    $strDataDetail = getData($db, $intTotalData, $strDataID);
    //$strInputLastNo = getLastMedicalNumber($db);
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  $strReadonly = "";
  if ($bolIsEmployee) {
    $arrData['dataEmployee'] = $arrUserInfo['employee_id'];
    $strReadonly = "readonly";
  }
  $strInputNo = "<input type=text name=dataNo id=dataNo size=$intFormNumberDigit maxlength=10 value=\"" . $arrData['dataNo'] . "\" style=\"width:50\" readonly>";
  $strInputNo .= "/<input type=text name=dataCode id=dataCode size=3 maxlength=10 value=\"" . $arrData['dataCode'] . "\" style=\"width:70\" >";
  $strInputNo .= "/<input type=text name=dataMonth id=dataMonth size=5 maxlength=5 value=\"" . $arrData['dataMonth'] . "\" style=\"width:30\" readonly>";
  $strInputNo .= "/<input type=text name=dataYear id=dataYear size=4 maxlength=4 value=\"" . $arrData['dataYear'] . "\" style=\"width:40\" readonly>";
  $strInputPaymentDate = "<input type=text size=15 maxlength=10 name=dataPaymentDate id=dataPaymentDate value=\"" . $arrData['dataPayment'] . "\" $strReadonly class='date-empty'>";
  $strInputDate = "<input type=text size=15 maxlength=10 name=dataDate id=dataDate value=\"" . $arrData['dataDate'] . "\" $strReadonly class='date'>";
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"" . $arrData['dataEmployee'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly class='string'>";
  $strInputAccount = "<input type=text name=dataAccount id=dataAccount size=30 maxlength=30 value=\"" . $arrData['dataAccount'] . "\" style=\"width:$strDefaultWidthPx\">";
  $strInputBudget = "<input type=text name=dataBudget size=30 maxlength=30 value=\"" . $arrData['dataBudget'] . "\" style=\"width:$strDefaultWidthPx\">";
  $strInputMethod = getPaymentMethodList(
      "dataMethod",
      $arrData['dataMethod'],
      "",
      "style=\"width:$strDefaultWidthPx\""
  );
  // status pembayaran
  if ($bolIsEmployee) {
    //$strInputPaymentStatus = ($arrData['dataPaymentStatus'] == 1) ? $words['paid'] : $words['unpaid'];
    $strInputStatus = $words[$ARRAY_REQUEST_STATUS[$arrData['dataStatus']]];
  } else {
    /*
    $strInputPaymentStatus  = "<select name=dataStatus style=\"width:$strDefaultWidthPx\">";
    if ($arrData['dataPaymentStatus'] == 0) {
      $strInputPaymentStatus .= "  <option value=0 selected>" .$words['unpaid']."</option>";
      $strInputPaymentStatus .= "  <option value=1>" .$words['paid']."</option>";
    } else {
      $strInputPaymentStatus .= "  <option value=0>" .$words['unpaid']."</option>";
      $strInputPaymentStatus .= "  <option value=1 selected>" .$words['paid']."</option>";
    }
    $strInputPaymentStatus .= "</select>\n";
    */
    //$strInputStatus = getComboFromArray($ARRAY_REQUEST_STATUS, "dataStatus", $arrData['dataStatus']);
    $strInputStatus = $words[$ARRAY_REQUEST_STATUS[$arrData['dataStatus']]];
  }
  // data-data trip
  $strInputLocation = "" . $arrData['dataLocation'];
  $strInputPurpose = "" . $arrData['dataPurpose'];
  $strInputTask = "" . nl2br($arrData['dataTask']);
  $strInputDuration = "" . $arrData['dataDuration'];
  $strInputAllowance = "<input type=hidden name=dataAllowance value='" . $arrData['dataAllowance'] . "'>" . standardFormat(
          $arrData['dataAllowance']
      );
  if ($arrData['dataDateFrom'] == $arrData['dataDateThru']) {
    $strInputTripDate = pgDateFormat($arrData['dataDateFrom'], 'd M y');
  } else {
    $strInputTripDate = pgDateFormat($arrData['dataDateFrom'], 'd M y');
    $strInputTripDate .= " - " . pgDateFormat($arrData['dataDateThru'], 'd M y');
  }
  // tampilan untuk data print
  if ($bolPrint) {
    include_once("../global/numtoword/numtoword.php");
    $strDataNo = $arrData['dataNo'] . "/" . $arrData['dataCode'] . "/" . $arrData['dataMonth'] . "/" . $arrData['dataYear'];
    $strDataPayment = pgDateFormat($arrData['dataPayment'], "d   M  Y");
    $strDataDate = pgDateFormat($arrData['dataDate'], "d M Y");
    $strDataEmployee = $arrData['dataemployee_name'];
    $strDataAccount = $arrData['dataAccount'];
    $strDataBudget = $arrData['dataBudget'];
    $strDataTotal = standardFormat($arrData['dataTotal']);
    $strDataTotalTerbilang = Terbilang($arrData['dataTotal']);
    $strDataLocation = $arrData['dataLocation'];
    $strDataPurpose = nl2br($arrData['dataTask']); // task-nya yang tampil
    $strDataAllowance = ($arrData['dataDuration'] == 0) ? 0 : standardFormat(
        $arrData['dataAllowance'] / $arrData['dataDuration']
    );
    $strDataTotalAllowance = standardFormat($arrData['dataAllowance']);
    $strDataDuration = $arrData['dataDuration'];
    $strDataTotalOther = standardFormat($arrData['dataTotalOther']);
    //$strDataTotalOtherTerbilang = Terbilang($arrData['dataTotalOther']);
    // untuk terbilan
    $strSayLang = ($arrData['dataCurrType'] == 0) ? "id" : "en";
    $cSay = new cNumToWord($arrData['dataTotalOther'], true, $strSayLang);
    $strDataTotalOtherTerbilang = $cSay->result;
    $strDataFrom = pgDateFormat($arrData['dataDateFrom'], "d M Y");
    $strDataThru = pgDateFormat($arrData['dataDateThru'], "d M Y");
  }
  // tampilkan button
  $strDisablePrint = ($strDataTripID != "") ? "" : "disabled";
  $strButtons .= "<input type=button name=btnPrint onClick=\"window.open('trip_payment_edit.php?btnPrint=Print&dataTripID=$strDataTripID');\" value=\"" . getWords(
          "print"
      ) . "\" $strDisablePrint>";
}
$strInitAction .= " document.formInput.dataDate.focus();
    Calendar.setup({ inputField:\"dataDate\", button:\"btnDate\" });
    Calendar.setup({ inputField:\"dataPaymentDate\", button:\"btnPayment\" });
    init();
    onCodeBlur();
    getTotalCost();
    getTotalCost('Other');
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>