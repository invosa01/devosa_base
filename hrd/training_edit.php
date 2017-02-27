<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  header("location:login.php?dataPage=training_edit.php");
  exit();
}
$bolCanView = getUserPermission("training_edit.php", $bolCanEdit, $bolCanDelete, $strError);
$strTemplateFile = getTemplate("training_edit.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intDefaultWidth = 30;
$intDefaultHeight = 3;
$strNow = date("Y-m-d");
$strMessages = "";
$strMsgClass = "";
$bolError = false;
$arrData = [
    "dataDateFrom" => $strNow,
    "dataDateThru" => "",
    "dataEmployee" => "",
    "dataStatus" => 0,
    "dataTopic" => "",
    "dataInstitution" => "",
    "dataLocation" => "",
    "dataTrainer" => "",
    "dataCost" => "0",
    "dataRequestID" => "",
    "dataNote" => "",
    "dataID" => "",
];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db)
{
  global $words;
  global $arrData;
  global $strDataID;
  global $stremployee_id;
  global $strRequestID;
  if ($strDataID != "") { // sudah ada data trainingnya
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "ID=$strDataID", 0);
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name FROM hrd_training_employee AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataDateFrom'] = $rowDb['date_from'];
      $arrData['dataDateThru'] = $rowDb['date_thru'];
      $arrData['dataTopic'] = $rowDb['subject'];
      $arrData['dataInstitution'] = $rowDb['institution'];
      $arrData['dataLocation'] = $rowDb['location'];
      $arrData['dataCost'] = (float)$rowDb['cost'];
      $arrData['dataTrainer'] = $rowDb['trainer'];
      $arrData['dataNote'] = $rowDb['note'];
      $stremployee_id = $rowDb['id_employee'];
      $strRequestID = $rowDb['idRequest'];
      $arrData['dataEmployee'] = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
    }
  }
  if ($strRequestID != "" && $stremployee_id != "") { // cari info tentang statusnya
    // cari data status keikutsertaan
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t3.topic,  ";
    $strSQL .= "t3.training_date, t3.trainer, t3.cost ";
    $strSQL .= "FROM hrd_training_request_participant AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "LEFT JOIN hrd_training_request AS t3 ON t1.id_request = t3.id ";
    $strSQL .= "WHERE t1.id_employee = '$stremployee_id' ";
    $strSQL .= "AND t1.id_request = '$strRequestID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataTopic'] = $rowDb['topic'];
      $arrData['dataInstitution'] = $rowDb['trainer'];
      $arrData['dataDateFrom'] = $rowDb['training_date'];
      $arrData['dataStatus'] = $rowDb['status'];
      $arrData['dataNote'] = $rowDb['note'];
      $arrData['dataCost'] = $rowDb['cost'];
      $arrData['dataEmployee'] = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
    }
    if ($strDataID == "") { // cari dulu, sapa tahu dah ada
      // cari dulu apakah ada data training
      $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name FROM hrd_training_employee AS t1 ";
      $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
      $strSQL .= "WHERE t1.id_employee = '$stremployee_id' ";
      $strSQL .= "AND t1.id_request = '$strRequestID' ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $arrData['dataDateFrom'] = $rowDb['date_from'];
        $arrData['dataDateThru'] = $rowDb['date_thru'];
        $arrData['dataTopic'] = $rowDb['subject'];
        $arrData['dataInstitution'] = $rowDb['institution'];
        $arrData['dataLocation'] = $rowDb['location'];
        $arrData['dataCost'] = (float)$rowDb['cost'];
        $arrData['dataTrainer'] = $rowDb['trainer'];
        $arrData['dataNote'] = $rowDb['note'];
        $strDataID = $rowDb['id'];
        $arrData['dataEmployee'] = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
      }
    }
  }
  return true;
} // showData
// fungsi untuk menyimpan data absen
function saveData($db, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $error;
  global $messages;
  global $arrData;
  global $strDataID;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strError = "";
  $bolOK = true;
  $strToday = date("Y-m-d");
  $strDataDateFrom = (isset($_REQUEST['dataDateFrom'])) ? $_REQUEST['dataDateFrom'] : "";
  $strDataDateThru = (isset($_REQUEST['dataDateThru'])) ? $_REQUEST['dataDateThru'] : "";
  $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
  $stremployee_id = (isset($_REQUEST['dataemployee_id'])) ? $_REQUEST['dataemployee_id'] : "";
  $strRequestID = (isset($_REQUEST['dataRequestID'])) ? $_REQUEST['dataRequestID'] : "";
  $strDataStatus = (isset($_REQUEST['dataStatus'])) ? $_REQUEST['dataStatus'] : "0";
  $strDataNote = (isset($_REQUEST['dataNote'])) ? substr($_REQUEST['dataNote'], 0, 250) : "";
  $strDataTopic = (isset($_REQUEST['dataTopic'])) ? $_REQUEST['dataTopic'] : "";
  $strDataInstitution = (isset($_REQUEST['dataInstitution'])) ? $_REQUEST['dataInstitution'] : "";
  $strDataLocation = (isset($_REQUEST['dataLocation'])) ? $_REQUEST['dataLocation'] : "";
  $strDataTrainer = (isset($_REQUEST['dataTrainer'])) ? $_REQUEST['dataTrainer'] : "";
  $strDataCost = (isset($_REQUEST['dataCost'])) ? $_REQUEST['dataCost'] : "";
  // cek validasi -----------------------
  if ($strDataStatus == 1) { // accepted
    if ($strDataTopic == "") {
      $strError = $error['empty_code'];
      $bolOK = false;
    } else if ($stremployee_id == "") {
      $strError = $error['data_not_found'];
      $bolOK = false;
    } else if ($strRequestID == "") {
      $strError = $error['data_not_found'];
      $bolOK = false;
    } else if (!validStandardDate($strDataDateFrom)) {
      $strError = $error['invalid_date'];
      $bolOK = false;
    }
  }
  $strDataDateFrom = validStandardDate($strDataDateFrom) ? "'$strDataDateFrom'" : "NULL";
  $strDataDateThru = validStandardDate($strDataDateThru) ? "'$strDataDateThru'" : "NULL";
  if (!is_numeric($strDataCost)) {
    $strDataCost = 0;
  }
  // simpan data -----------------------
  if ($bolOK) { // input OK, tinggal disimpan
    // simpan dulu data statusnya
    $strSQL = "UPDATE hrd_training_request_participant SET modified_by = '$strmodified_byID', ";
    $strSQL .= "status = '$strDataStatus', note = '$strDataNote' ";
    $strSQL .= "WHERE id_employee = '$stremployee_id' AND id_request = '$strRequestID' ";
    $resExec = $db->execute($strSQL);
    // simpan juga costnya ke master requestnya
    $strSQL = "UPDATE hrd_training_request SET cost = '$strDataCost' ";
    $strSQL .= "WHERE id = '$strRequestID' ";
    $resExec = $db->execute($strSQL);
    if ($strDataStatus != 1) { // belum diterima, jadi data dihapus
      $strSQL = "DELETE FROM hrd_training_employee WHERE id_employee = '$stremployee_id' ";
      $strSQL .= "AND id_request = '$strRequestID' ";
      $resExec = $db->execute($strSQL);
    } else if ($strDataID == "") {
      // data baru
      $strSQL = "INSERT INTO hrd_training_employee (created,created_by,modified_by, ";
      $strSQL .= "id_request, id_employee, date_from, date_thru, ";
      $strSQL .= "subject, institution, location, trainer, note) ";
      $strSQL .= "VALUES(now(),'$strmodified_byID','$strmodified_byID', ";
      $strSQL .= "'$strRequestID', '$stremployee_id', $strDataDateFrom, $strDataDateThru,  ";
      $strSQL .= "'$strDataTopic', '$strDataInstitution', '$strDataLocation', ";
      $strSQL .= "'$strDataTrainer', '$strDataNote') ";
      $resExec = $db->execute($strSQL);
      // cari ID
      $strSQL = "SELECT id FROM hrd_training_employee ";
      $strSQL .= "WHERE id_request = '$strRequestID' AND id_employee = '$stremployee_id' ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $strDataID = $rowDb['id'];
      }
    } else {
      $strSQL = "UPDATE hrd_training_employee ";
      $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
      $strSQL .= "date_from = $strDataDateFrom, date_thru = $strDataDateThru, ";
      $strSQL .= "subject = '$strDataTopic', institution = '$strDataInstitution', ";
      $strSQL .= "location = '$strDataLocation', trainer = '$strDataTrainer', ";
      $strSQL .= "note = '$strDataNote' ";
      $strSQL .= "WHERE id_employee = '$stremployee_id' AND id_request = '$strRequestID' ";
      $resExec = $db->execute($strSQL);
    }
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "TRAINING DATA", 0);
    $strError = $messages['data_saved'];
  } else { // ---- data SALAH
    // gunakan data yang diisikan tadi
    $arrData['dataDateFrom'] = $strDataDateFrom;
    $arrData['dataDateThru'] = $strDataDateThru;
    $arrData['dataCost'] = $strDataCost;
    $arrData['dataTopic'] = $strDataTopic;
    $arrData['dataInstitution'] = $strDataInstitution;
    $arrData['dataLocation'] = $strDataLocation;
    $arrData['dataTrainer'] = $strDataTrainer;
    $arrData['dataNote'] = $strDataNote;
    $arrData['dataID'] = $strDataID;
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "ABSENCE DATA", 0);
  }
  return $bolOK;
} // saveData
// menghapus laporan keikutsertaan training
function deleteData($db)
{
  global $_REQUEST;
  $strRequest = (isset($_REQUEST['dataRequestID'])) ? $_REQUEST['dataRequestID'] : "";
  $strEmployee = (isset($_REQUEST['dataemployee_id'])) ? $_REQUEST['dataemployee_id'] : "";
  if ($strRequest != "" && $strEmployee != "") {
    $strSQL = "DELETE FROM hrd_training_employee ";
    $strSQL .= "WHERE id_request = '$strRequest' AND id_employee = '$strEmployee' ";
    $resExec = $db->execute($strSQL);
    $strSQL = "UPDATE hrd_training_request_participant SET status = 0 ";
    $strSQL .= "WHERE id_request = '$strRequest' AND id_employee = '$strEmployee' ";
    $resExec = $db->execute($strSQL);
  }
  writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "Request=$strRequest, Employee=$strEmployee", 0);
  return true;
}//deleteData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  $strRequestID = (isset($_REQUEST['dataRequestID'])) ? $_REQUEST['dataRequestID'] : "";
  $stremployee_id = (isset($_REQUEST['dataemployee_id'])) ? $_REQUEST['dataemployee_id'] : "";
  $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      $bolOK = saveData($db, $strError);
      if ($strError != "") {
        //echo "<script>alert(\"$strError\");/script>";
        $strMessages = $strError;
        $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
      }
      //$strDataID = ""; // biar tidak mengambil dta, melainkan pakai data baru atau data yang dikirim (jika error)
    } else if (isset($_REQUEST['btnDelete'])) {
      deleteData($db);
      header("location:training_request_list.php");
      exit();
    }
  }
  // cek apakah ada atau tidak datanya
  if (($strRequestID == "" || $stremployee_id == "") && $strDataID == "") {
    header("location:training_request_list.php");
    exit();
  } else if (!isMe($stremployee_id)) {
    // gak berhak, kecuali admin
    if (!thisUserIs(ROLE_SUPERVISOR) && !thisUserIs(ROLE_ADMIN)) {
      header("location:training_request_list.php");
      exit();
    }
  }
  if ($bolCanView) {
    getData($db);
  } else {
    showError("view_denied");
  }
  //----- TAMPILKAN DATA ---------
  $strDefaultWidthPx1 = 100;
  $strReadonly = ""; //($arrData['dataStatus'] == 0) ? "" : "readonly"; // kalau dah approve, jadi readonly
  $strInputDateFrom = "<input type=text size=15 maxlength=10 name=dataDateFrom id=dataDateFrom value=\"" . $arrData['dataDateFrom'] . "\" $strReadonly>";
  $strInputDateFrom .= " <input type=button id=btnDateFrom value='..' >";
  $strInputDateThru = "<input type=text size=15 maxlength=10 name=dataDateThru id=dataDateThru value=\"" . $arrData['dataDateThru'] . "\" $strReadonly>";
  $strInputDateThru .= " <input type=button id=btnDateThru value='..' >";
  $strInputTopic = "<input type=text name=dataTopic id=dataTopic size=50 maxlength=90 value=\"" . $arrData['dataTopic'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly>";
  $strInputTrainer = "<input type=text name=dataTrainer id=dataTrainer size=50 maxlength=90 value=\"" . $arrData['dataTrainer'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly>";
  $strInputInstitution = "<input type=text name=dataInstitution id=dataInstitution size=50 maxlength=90 value=\"" . $arrData['dataInstitution'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly>";
  $strInputLocation = "<input type=text name=dataLocation id=dataLocation size=50 maxlength=90 value=\"" . $arrData['dataLocation'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly>";
  $strInputCost = "<input type=text name=dataCost size=50 maxlength=30 value=\"" . (float)$arrData['dataCost'] . "\" style=\"width:$strDefaultWidthPx\"  $strReadonly class=numeric>";
  $strInputEmployee = "<input type=hidden name=dataemployee_id id=dataemployee_id value=\"" . $stremployee_id . "\"><strong>" . $arrData['dataEmployee'] . "</strong>";
  $strInputNote = "<textarea name=dataNote cols=50 rows=4 style=\"width:$strDefaultWidthPx\">" . $arrData['dataNote'] . "</textarea>\n";
  $strInputStatus = getComboFromArray(
      $ARRAY_TRAINING_PARTICIPATION,
      "dataStatus",
      $arrData['dataStatus'],
      "",
      " style=\"width:$strDefaultWidthPx\" onChange=\"onStatusChange();\" "
  );
}
$strInitAction .= "	document.formInput.dataTopic.focus();
		Calendar.setup({ inputField:\"dataDateFrom\", button:\"btnDateFrom\" });
		Calendar.setup({ inputField:\"dataDateThru\", button:\"btnDateThru\" });
        AC_kode = \"dataEmployee\";
        AC_nama = employee_name;
		onStatusChange();
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>