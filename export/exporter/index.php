<?php
//include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('import_func.php');
include_once('overtime_func.php');
include_once('activity.php');
//---- INISIALISASI ----------------------------------------------------
$strWordsAttendanceData = getWords("attendance data");
$strWordsEntryAttendance = getWords("entry attendance");
$strWordsImportAttendance = getWords("import attendance");
$strWordsAttendanceList = getWords("attendance list");
$strWordsAttendanceReport = getWords("attendance report");
$strWordsAttendanceFile = getWords("attendance file");
$strWordsImport = getWords("import");
$strWordsSync = getWords("sync");
$strWordsRESULT = getWords("result");
$strDate = getWords("attendance date");
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strAttendanceDate = "";
$strInputDate = "";
$bolShowResult = false;
$strResultInfo = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi mengubah tanggal dari format YYYYMMDD ke YYYY-MM-DD
function timestampDate2Date($strTs)
{
  if (strlen($strTs) != 8) {
    return $strTs; // error, langsug dibalikin
  } else {
    $strResult = substr($strTs, 0, 4) . "-" . substr($strTs, 4, 2) . "-" . substr($strTs, 6);
    return $strResult;
  }
}//timestampDate2Date
// fungsi mengubah format time dari HHMM ke HH:MM:00
function timestampTime2Time($strTs)
{
  if (strlen($strTs) != 4) {
    return $strTs;
  } else {
    $strResult = substr($strTs, 0, 2) . ":" . substr($strTs, 2) . ":00";
    return $strResult;
  }
}//timestampTime2Time
//fungsi untuk memeriksa data yang diimport mengandung tanggal berapa saja
function checkDateImport()
{
  global $HTTP_POST_FILES;
  global $bolShowResult;
  $strResult = "";
  $arrDate = []; // menampung data daftar tanggal yang ada
  if (is_uploaded_file($HTTP_POST_FILES["fileData"]['tmp_name'])) {
    $dbf = dbase_open($HTTP_POST_FILES["fileData"]['tmp_name'], 0);
    if ($dbf) {
      $intLen = dbase_numrecords($dbf);
      for ($i = 1; $i <= $intLen; $i++) {
        $arrTmp = dbase_get_record_with_names($dbf, $i);
        $strStatus = trim($arrTmp['FCSTATUS']);
        $strAttendanceDate = timestampDate2Date($arrTmp['FDDATE']);
        $strTime = timestampTime2Time($arrTmp['FCTIME']);
        list($tahun, $bulan, $tanggal) = explode("-", $strAttendanceDate);
        $tsTmp = mktime(0, 0, 0, (int)$bulan, (int)$tanggal, $tahun);
        $dtTmp = getdate($tsTmp);
        $strAttendanceDate = $dtTmp['year'] . "-" . $dtTmp['mon'] . "-" . $dtTmp['mday']; // biar seragam
        $arrDate[$strAttendanceDate] = 1;
      }
      foreach ($arrDate AS $strDate => $tmp) {
        $strResult .= "<a href=\"javascript:insertDate('$strDate')\">" . pgDateFormat($strDate, "d M Y") . "</a><br>";
      }
    }
    dbase_close($dbf);
  }
  return $strResult;
} //checkDateImport
// fungsi untuk menghitung overtime dari data attendance per tanggal tertentu
function calculateOT($db, $strDataDate)
{
  global $_SESSION;
  $strmodified_byID = $_SESSION['sessionUserID'];
  if ($strDataDate == "") {
    return "";
  }
  $strSQL = "SELECT id,id_employee FROM hrd_attendance WHERE attendance_date = '$strDataDate' ";
  $resDb = $db->execute($strSQL);
  $i = 0;
  while ($rowDb = $db->fetchrow($resDb)) {
    reCalculateOvertimeData($db, $strDataDate, $rowDb['id_employee']);
    $i++;
  }
  return "$i data";
}

;// calculateOT
//fungsi untuk memproses import data
function importData_Asli($db, &$intTotal)
{
  global $HTTP_POST_FILES;
  global $_FILES;
  global $_REQUEST;
  global $_SESSION;
  global $words;
  $strmodified_by = $_SESSION['sessionUserID'];
  $strResult = "";
  foreach ($_FILES AS $kode => $value) {
    if (is_uploaded_file($_FILES[$kode]['tmp_name'])) {
      $strFileName = $_FILES[$kode]['tmp_name'];
      print $strFileName;
      //$strResult = processAttendance($db, $strFileName, false);
    }
  }
  /*
  if (is_uploaded_file($HTTP_POST_FILES["fileData"]['tmp_name'])) {
    $strFileName = $HTTP_POST_FILES["fileData"]['tmp_name'];
    $strResult = processAttendance($db, $strFileName, false);

  }
  */
  return $strResult;
} //importData
function importData($db, &$intTotal)
{
  global $strDateFrom, $strDateTo;
  $strResult = processAttendance($db, $strDateFrom, $strDateTo, false);
  return $strResult;
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strTemplateFile = getTemplate("attendance_import.html");
$db = new CdbClass;
if ($db->connect()) {
  // ------ AMBIL DATA KRITERIA -------------------------
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  $strDataDate = "";
  //if (!$bolCanView) echo "<script>alert(\"".getWords("view_denied")."\")</script>";
  $strDateFrom = (isset($_REQUEST['dateFrom'])) ? $_REQUEST['dateFrom'] : date("Y-m-d");
  $strDateTo = (isset($_REQUEST['dateTo'])) ? $_REQUEST['dateTo'] : date("Y-m-d");
  $dtAwal = getdate();
  //print "test : $intTotal";
  if (isset($_POST['btnImport'])) {
    $strHasil = importData($db, $intTotal);
    //getAttendanceData($db, false);
    syncOvertimeApplication($db, $strDateFrom, $strDateTo);
    syncShiftAttendance($db, $strDateFrom, $strDateTo);
    //checkInvalidAttendance($db, $strDateFrom, $strDateTo);
    $dtAkhir = getdate();
    $selisih = $dtAkhir[0] - $dtAwal[0];
    $jam = floor($selisih / 3600);
    $mnt = floor(($selisih - ($jam * 3600)) / 60);
    $dtk = ($selisih % 60);
    $strResultInfo .= "<br><span style=\"color:red\">Finish in : $jam hour $mnt minutes $dtk seconds</span><br>";
    $strResultInfo .= $strHasil;
  }
  if (isset($_POST['btnSync']) && $bolCanEdit) {
    $remotefile = 'HTTP://192.168.1.19/DATATKS.mdb';
    $localfile = "..\DATATKS.MDB";
    copy($remotefile, $localfile);
  }
}
$strInputDate = "<input type=text name='dateFrom' id='dateFrom' value='$strDateFrom' size=13>&nbsp;";
$strInputDate .= "<input type=button name='btnDateFrom' id='btnDateFrom' value='..'>&nbsp;" . getWords(
        "until"
    ) . "&nbsp;";
$strInputDate .= "<input type=text name='dateTo' id='dateTo' value='$strDateTo' size=13>&nbsp;";
$strInputDate .= "<input type=button name='btnDateTo' id='btnDateTo' value='..'>&nbsp;";
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = "Export Attendance";
//$pageIcon = "../images/icons/blank.gif";
//else $pageIcon = "../images/icons/".$dataPrivilege['icon_file'];
$strTemplateFile = "attendance_export.html";
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strTemplateFile);
$tbsPage->Show();
?>