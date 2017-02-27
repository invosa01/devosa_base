<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('import_func.php');
include_once('overtime_func.php');
include_once('activity.php');
include_once('../classes/hrd/hrd_company.php');
include_once('../global/excelReader/excel_reader.php');
include_once('../global/employee_function.php');
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
//---- INISIALISASI ----------------------------------------------------
$strWordsAttendanceData = getWords("attendance data");
$strWordsEntryAttendance = getWords("entry attendance");
$strWordsImportAttendance = getWords("import attendance");
$strWordsAttendanceList = getWords("attendance list");
$strWordsAttendanceReport = getWords("attendance report");
$strWordsAttendanceFile = getWords("attendance file");
$strWordsAttendanceDate = getWords("attendance date");
$strWordsCompany = getWords("company");
$strWordsImport = getWords("import");
$strWordsRESULT = getWords("result");
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strAttendanceDate = "";
$strInputDate = "";
$bolShowResult = false;
$strResultInfo = "";
$strWordsImportData = getWords("import data");
$strDownloadFiles = "<a href='/hrd/templatates_salary.xls'>Download</a>"; // lokasi donwnload untuk file contoh
$strFilePath = "";
$strParameterName = 'fileImport'; // Nama parameter yang untuk input type file
$data = new Spreadsheet_Excel_Reader($strFilePath);  // Class excel reader
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
//fungsi untuk memeriksa data yang diimport mengandung tanggal berapa saja
function checkDateImport()
{
  global $HTTP_POST_FILES;
  global $bolShowResult;
  $strResult = "";
  $arrDate = []; // menampung data daftar tanggal yang ada
  if (is_uploaded_file($HTTP_POST_FILES["fileAttendance"]['tmp_name'])) {
    $dbf = dbase_open($HTTP_POST_FILES["fileAttendance"]['tmp_name'], 0);
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
function importProcess($db)
{
  global $data, $strFilePath, $strFileType, $intNumberOfField, $strNormalStartTime, $strNormalFinishTime;
  $data = new Spreadsheet_Excel_Reader($strFilePath);  // Class excel reader
  // membaca jumlah baris dari data excel yang diupload
  $baris = $data->rowcount($sheet_index = 0);
  // variabel awal counter untuk jumlah data yang sukses dan yang gagal diimport
  $dataPost = $_POST;
  $dateFrom = $dataPost['dataDateFrom'];
  $dateThru = $dataPost['dataDateThru'];
  // import data excel mulai baris ke-2 (karena baris pertama adalah nama kolom)
  for ($i = 2; $i <= $baris; $i++) {
    // Variabelisasi ambil dari template
    $strFingerID = $data->val($i, 1); // Finger ID
    $strNIK = $data->val($i, 1); // NIK
    $strDate = $data->val($i, 3); // Date
    $strIN = $data->val($i, 7); // Time IN
    $strOUT = $data->val($i, 8); // Time Out
    //------------------------------------
    $strIDEmployee = getIDEmployee($db, $strNIK); // Ambil informasi ID dengan Select dari NIK atau finger id
    //$strIDEmployee  = getIDEmployeeByBarcode($db, $strFingerID); // Ambil informasi ID dengan Select dari NIK atau finger id
    // Jika ID employee kosong tidak perlu di proses
    if (($strIDEmployee != 0) OR ($strIDEmployee != "")) {
      $strDateConvert = timeStamp2SingleDate($strDate);  // Covert menjadi format yang dikenali postgresql (Format baru)
      //$strDateConvert = timestampDate2Date($strDate);  // Covert menjadi format yang dikenali postgresql
      $arrDataAttendance[] = [
          'finger_id' => $strNIK,
          'id_employee' => $strIDEmployee,
          'date' => $strDateConvert,
          'in' => $strIN,
          'out' => $strOUT
      ]; // format baru
      //$arrDataAttendance[]=array('finger_id'=>$strFingerID,'id_employee'=>$strIDEmployee,'date'=>$strDateConvert,'in'=>$strIN,'out'=>$strOUT);
    }
  }
  print_r($arrDataAttendance);
  $strMessage = processAttendance($db, $dateFrom, $dateThru, $arrDataAttendance);
  return $strMessage;
}

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
      $strResult = processAttendance($db, $strFileName, false);
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
  global $strDataCompany;
  global $strDateFrom, $strDateThru;
  $strResult = processAttendance($db, $prb, $strDateFrom, $strDateThru, false, $strDataCompany);
  return $strResult;
}

/// Fungsi get ID employee By barcode
function getIDEmployeeByBarcode($db, $code)
{
  $strResult = "";
  if ($code != "") {
    $strSQL = "SELECT id FROM hrd_employee WHERE barcode= '$code' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strResult = $rowDb['id'];
    }
  }
  return $strResult;
}

//----------------------------------------------------------------------
function getFingerIdByEmployeeId($strDataEmployee)
{
  global $db;
  $strSQL = "SELECT barcode FROM \"hrdEmployee\" WHERE \"employeeID\" = '$strDataEmployee' ";
  $res = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($res)) {
    $strFingerID = $rowDb['pin'];
  } else {
    $strFingerID = "";
  }
  return $strFingerID;
}

//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  if (!$bolCanView) {
    echo "<script>alert(\"" . getWords("view_denied") . "\")</script>";
  }
  $strDateFrom = (isset($_REQUEST['dataDateFrom'])) ? $_REQUEST['dataDateFrom'] : getNextDate(date("Y-m-d"), -7);
  $strDateThru = (isset($_REQUEST['dataDateThru'])) ? $_REQUEST['dataDateThru'] : date("Y-m-d");
  $dtAwal = getdate();
  if (isset($_POST['btnImport']) && $bolCanEdit) {
    //Get Information Of Files and Parameter
    //$strFilePath    = $_FILES[$strParameterName]['tmp_name'];
    //$tipeFile       = $_FILES[$strParameterName]['type'];
    $strMessage = importData($db, $intTotal);
    syncOvertimeApplication($db, $strDateFrom, $strDateThru, "", " AND id_company = $strDataCompany");
    $dtAkhir = getdate();
    $selisih = $dtAkhir[0] - $dtAwal[0];
    $jam = floor($selisih / 3600);
    $mnt = floor(($selisih - ($jam * 3600)) / 60);
    $dtk = ($selisih % 60);
    $strResultInfo .= "<br><span style=\"color:red\">Imported : $jam hour $mnt minutes $dtk seconds</span><br>";
    $strResultInfo .= $strMessage;
  }
}
//$strInputFile  = "<input type='file' name='".$strParameterName."' id='".$strParameterName."' size='50'> ";
$strInputDate = "<input type=text name='dataDateFrom' id='dataDateFrom' value='$strDateFrom' size=13 class=\"form-control datepicker\" data-date-format=\"yyyy-mm-dd\">";
//$strInputDate .= "<input type=button name='btnDateFrom' id='btnDateFrom' value='..'>&nbsp;".getWords("until")."&nbsp;";
$strInputDate2 = "<input type=text name='dataDateThru' id='dataDateThru' value='$strDateThru' size=13 class=\"form-control datepicker\" data-date-format=\"yyyy-mm-dd\">";
//$strInputDate .= "<input type=button name='btnDateThru' id='btnDateThru' value='..'>&nbsp;";
$strInputCompany = getCompanyList(
    $db,
    "dataCompany",
    $strDataCompany,
    $strEmptyOption2,
    $strKriteria2,
    "style=\"width:258\" "
);
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = getWords('employee attendance entry');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = attendanceSubMenu($strWordsImportAttendance);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>