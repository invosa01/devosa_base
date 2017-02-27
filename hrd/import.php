<?php
session_start();
include_once('global.php');
include_once('form_object.php');
include_once('import_func.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  header("location:login.php?dataPage=import.php");
  exit();
}
$bolCanView = getUserPermission("attendance_edit.php", $bolCanEdit, $bolCanDelete, $strError);
$strTemplateFile = "import.html";// getTemplate("import.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$strResult = "";
$intTotalData = 0;
$strTotalResultForm = ""; // jumlah form yang ditemukan
$strTotalResultDetail = ""; // jumlah detail data yang diproses
$strErrorID = ""; // daftar ID employee yang gak ketemu
$strResultStyle = "style = \"display:none\" ";
$strFormatFile = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNGSI ------------------------------------------------------
// menampilkan pesan ke HTML
function showMessage($strMsg)
{
  echo $strMsg . "<br>";
}

// proses import data -- main-main aja
function importData($db, &$intTotal)
{
  global $_FILES;
  global $_REQUEST;
  global $_SESSION;
  global $strResult;
  global $messages;
  global $strTotalResultDetail;
  global $strTotalResultForm;
  global $strErrorID;
  global $strResultStyle;
  $strError = "";
  $strmodified_byID = $_SESSION['sessionUserID'];
  $cTime = new CexecutionTime();
  $arrEmployee = [];
  // cari data id karyawan
  $strSQL = "SELECT employee_id, id FROM hrd_employee WHERE flag = 0 ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrEmployee[$rowDb['employee_id']] = $rowDb['id'];
  }
  $intTotal = 0;
  $i = 0;
  $ok = 0;
  $intTmp = 0;
  $arrTmp = [
      "absen manual"    => 0,
      "cuti"            => 0,
      "izin"            => 0,
      "absen manual"    => 0,
      "sakit"           => 0,
      "training"        => 0,
      "tugas luar kota" => 0,
      "null"            => 0,
  ];
  $arrNone = [];
  if (is_uploaded_file($_FILES["fileData"]['tmp_name'])) {
    // cari total dulu
    $handle = fopen($_FILES["fileData"]['tmp_name'], "r");
    while (($data = fgets($handle, 2000)) !== false) {
      $intTotal++;
    }
    fclose($handle);
    $handle = fopen($_FILES["fileData"]['tmp_name'], "r");
    while (($data = fgetcsv($handle, 2000, ",")) !== false) {
      $i++;
      $intTmp++;
      $stremployee_id = trim($data[0]);
      $strDate = trim($data[1]);
      $strStart = trim($data[2]);
      $strFinish = trim($data[3]);
      $strType = strtolower(trim($data[4]));
      $strNote = trim($data[5]);
      if ($strNote == "null" || $strNote == "NULL") {
        $strNote = "";
      }
      $strID = (isset($arrEmployee[$stremployee_id])) ? $arrEmployee[$stremployee_id] : "";
      if ($strID != "") {
        $strDate = substr($strDate, 0, 10);
        list($tgl, $jam) = explode(" ", $strStart);
        $strStart = substr($jam, 0, 5);
        list($tgl, $jam) = explode(" ", $strFinish);
        $strFinish = substr($jam, 0, 5);
        if ($strType == "absen manual") {
          insertAttendanceData($db, $strID, $strDate, $strStart, $strFinish, $strNote);
        } else if ($strType == "cuti") {
          // proses pencatatan cuti, tapi gak ngurangin jatah cuti
          insertLeaveData($db, $strID, $strDate, $strNote);
        } else if ($strType == "izin") {
          insertAbsenceData($db, $strID, $strDate, "I", $strNote);
        } else if ($strType == "sakit") {
          insertAbsenceData($db, $strID, $strDate, "S", $strNote);
        } else if ($strType == "training") {
          insertTrainingData($db, $strID, $strDate, $strNote);
        } else if ($strType == "tugas luar kota") {
          insertTripData($db, $strID, $strDate, $strNote);
        } else if ($strType == "null") // dianggap hadir normal
        {
          // normal, jika lebih kecil dari september, maka disimpan, selain itu gak perlu
          $x = (int)substr($strDate, 5, 2);
          if ($x < 10) {
            insertAttendanceData($db, $strID, $strDate, $strStart, $strFinish, $strNote);
          }
        }
      } else {
        //if (!in_array($stremployee_id, $arrNone))
        //  $arrNone[] = $stremployee_id;
        // yang gak ketemu employeenya, biarin saja
      }
      if ($intTmp >= 50) {
        $str = "Processing ... $i of $intTotal<br>\n";
        //flush();
        $intTmp = 0;
      }
    }
    fclose($handle);
    if ($ok > 0) {
      writeLog(ACTIVITY_IMPORT, MODULE_PAYROLL, "$strTotalResultDetail data", 0);
    }
    //$strResult = $messages['data_saved'] ." ". $ok. "/".$i;
    //$strResult .= " <br>".$strError;
    // tampilkan employee ID yang error
    //$strErrorID = implode(", ", $arrEmpUnknown);
    $strDur = $cTime->getDuration();
    echo "<script language='Javascript'>alert('Process Done! Time = $strDur ')</script>";
    //$strResultStyle = "";
  }
  //fclose($handle);
  // tampilkan alert
  //showMessage("Finish ...");
  //print_r($arrEmpUnknown);
  //echo "<script language='Javascript'>alert('Process Done! $strDur seconds')</script>";
} //importData
// fungsi menambahkan data cuti
function insertLeaveData($db, $strID, $strDate, $strNote)
{
  global $_SESSION;
  $strmodified_byID = $_SESSION['sessionUserID'];
  if ($strID == "" || $strDate == "") {
    return false;
  }
  $strDataNo = "0000";
  $strDataCode = "SYSTEMS"; // khusus yg digenerate oleh system
  $strDataYear = substr($strDate, 0, 4);
  $strDataMonth = (int)substr($strDate, 5, 2);
  $strDataMonth = getRomans($strDataMonth);
  $strDataStatus = REQUEST_STATUS_APPROVED;
  // hapus dulu data yang digenerate sys
  $strSQL = "DELETE FROM hrd_leave WHERE id_employee = '$strID' ";
  $strSQL .= "AND request_date = '$strDate' ";
  //$strSQL .= "AND no = '$strDataNo' AND code = '$strDataCode' ";
  $resExec = $db->execute($strSQL);
  $strSQL = "INSERT INTO hrd_leave (created,created_by,modified_by, ";
  $strSQL .= "id_employee,request_date, date_from, date_thru, leave_type_code, ";
  $strSQL .= "duration, note, no, code, month_code, year_code, status) ";
  $strSQL .= "VALUES(now(),'$strmodified_byID','" . $_SESSION['sessionUserID'] . "', ";
  $strSQL .= "'$strID','$strDate', '$strDate', '$strDate', 0, '1', '$strNote', ";
  $strSQL .= "'$strDataNo', '$strDataCode', '$strDataMonth', '$strDataYear', '$strDataStatus')  ";
  $resExec = $db->execute($strSQL);
} //insertLeaveData
// fungsi menambahkan data absence
function insertAbsenceData($db, $strID, $strDate, $strType, $strNote)
{
  global $_SESSION;
  $strmodified_byID = $_SESSION['sessionUserID'];
  if ($strID == "" || $strDate == "") {
    return false;
  }
  $strDataNo = "0000";
  $strDataCode = "SYSTEMS"; // khusus yg digenerate oleh system
  $strDataYear = substr($strDate, 0, 4);
  $strDataMonth = (int)substr($strDate, 5, 2);
  $strDataMonth = getRomans($strDataMonth);
  $strDataStatus = REQUEST_STATUS_APPROVED;
  // hapus dulu data yang digenerate sys
  $strSQL = "DELETE FROM hrd_absence WHERE id_employee = '$strID' ";
  $strSQL .= "AND request_date = '$strDate' ";
  //$strSQL .= " AND no = '$strDataNo' AND code = '$strDataCode' AND absence_type_code = '$strType' ";
  $resExec = $db->execute($strSQL);
  $strSQL = "INSERT INTO hrd_absence (created, modified_by, created_by, request_date, ";
  $strSQL .= "date_from, date_thru, absence_type_code, ";
  $strSQL .= "id_employee, note, status) ";
  $strSQL .= "VALUES (now(), '$strmodified_byID', '$strmodified_byID', '$strDate', ";
  $strSQL .= "'$strDate', '$strDate', '$strType', '$strID', '$strNote'," . REQUEST_STATUS_APPROVED . "); ";
  $resExec = $db->execute($strSQL);
} //insertAbsenceData
// fungsi menambahkan data absence
function insertTripData($db, $strID, $strDate, $strNote)
{
  global $_SESSION;
  $strmodified_byID = $_SESSION['sessionUserID'];
  if ($strID == "" || $strDate == "") {
    return false;
  }
  $strDataNo = "0000";
  $strDataCode = "SYSTEMS"; // khusus yg digenerate oleh system
  $strDataYear = substr($strDate, 0, 4);
  $strDataMonth = (int)substr($strDate, 5, 2);
  $strDataMonth = getRomans($strDataMonth);
  $strDataStatus = REQUEST_STATUS_APPROVED;
  // hapus dulu data yang digenerate sys
  $strSQL = "DELETE FROM hrd_trip WHERE id_employee = '$strID' ";
  $strSQL .= "AND proposal_date = '$strDate' ";
  $strSQL .= "AND no = '$strDataNo' AND code = '$strDataCode' ";
  $resExec = $db->execute($strSQL);
  $strSQL = "INSERT INTO hrd_trip (created,created_by,modified_by, ";
  $strSQL .= "id_employee,proposal_date, date_from, date_thru, location, ";
  $strSQL .= "purpose, task, allowance, no, code, month_code, year_code, ";
  $strSQL .= "total_allowance, status)";
  $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
  $strSQL .= "'$strID','$strDate', '$strDate', '$strDate',  ";
  $strSQL .= "'', '','$strNote', '0',   ";
  $strSQL .= "'$strDataNo', '$strDataCode', '$strDataMonth', '$strDataYear',  ";
  $strSQL .= "0, '$strDataStatus' )";
  $resExec = $db->execute($strSQL);
} //insertTripData
// fungsi menambahkan data absence
function insertTrainingData($db, $strID, $strDate, $strNote)
{
  global $_SESSION;
  $strmodified_byID = $_SESSION['sessionUserID'];
  if ($strID == "" || $strDate == "") {
    return false;
  }
  $strDataNo = "0000";
  $strDataCode = "SYSTEMS"; // khusus yg digenerate oleh system
  $strDataYear = substr($strDate, 0, 4);
  $strDataMonth = (int)substr($strDate, 5, 2);
  $strDataMonth = getRomans($strDataMonth);
  $strDataStatus = REQUEST_STATUS_APPROVED;
  // hapus dulu data yang digenerate sys
  $strSQL = "SELECT id FROM hrd_training_request ";
  $strSQL .= "WHERE request_date = '$strDate' ";
  //$strSQL .= "AND request_number = '$strDataNo".".$strDataCode' ";
  $strSQL .= "AND id_employee = '$strID' ";
  $strSQL .= "ORDER BY id DESC ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $strDataID = $rowDb['id'];
    $strSQL = "DELETE FROM hrd_training_request_participant WHERE id_request = '$strDataID'; ";
    $strSQL .= "DELETE FROM hrd_training_request WHERE id = '$strDataID'; ";
    $resExec = $db->execute($strSQL);
  }
  // simpan
  $strSQL = "INSERT INTO hrd_training_request (created,created_by,modified_by, ";
  $strSQL .= "id_employee, request_date, request_number, topic,  ";
  $strSQL .= "training_date, \"trainingDateThru\", status, training_status ) ";
  $strSQL .= "VALUES(now(),'$strmodified_byID','$strmodified_byID', ";
  $strSQL .= "'$strID', '$strDate', '$strDataNo" . "-$strDataCode',  ";
  $strSQL .= "'$strNote','$strDate', '$strDate', '$strDataStatus', 0) ";
  $resExec = $db->execute($strSQL);
  // cari ID
  $strSQL = "SELECT id FROM hrd_training_request ";
  $strSQL .= "WHERE request_date = '$strDate' AND request_number = '$strDataNo" . ".$strDataCode' ";
  $strSQL .= "AND id_employee = '$strID' ";
  $strSQL .= "ORDER BY id DESC ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strDataID = $rowDb['id'];
    // simpan partisipannya
    $strSQL = "INSERT INTO hrd_training_request_participant (created, modified_by, ";
    $strSQL .= "created_by, id_request, id_employee) ";
    $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', ";
    //$strSQL .= "'$strDataID', '" .$rowT['id']."') ";
    $strSQL .= "'$strDataID', '$strID') ";
    $resExec = $db->execute($strSQL);
  }
} //insertTrainingData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  // ------ AMBIL DATA KRITERIA -------------------------
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if (isset($_POST['btnImport'])) {
    importData($db, &$intTotalData);
  }
}
$strResult = "";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strTemplateFile);
$tbsPage->Show();
?>