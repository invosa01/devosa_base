<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('overtime_func.php');
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
$strWordsGetInfo = getWords("get info");
$strWordsUseNormal = getWords("use normal");
$strWordsATTENDANCEDATA = getWords("attendance data");
$strWordsManualDataEntry = getWords("manual data entry");
$strWordsMultipleDataEntry = getWords("multiple data entry");
$strWordsImportAttendanceData = getWords("import attendance data");
$strWordsAttendanceList = getWords("attendance list");
$strWordsAttendanceReport = getWords("attendance report");
$strWordsAttendanceDate = getWords("attendance date");
$strWordsEmployeeID = getWords("employee id");
$strWordsNormalTime = getWords("normal time");
$strWordsActualTime = getWords("actual time");
$strWordsRevisionTime = getWords("revision time");
$strWordsNote = getWords("note");
$strWordsSave = getWords("save");
$strHidden = "";
$strMessages = "";
$strMsgClass = "";
$bolError = false;
$arrData = [
    "attendanceDate" => date("Y-m-d"),
    "employee_id"    => "",
    "normal_start"   => "",
    "normal_finish"  => "",
    "actual_start"   => "",
    "actual_finish"  => "",
    "change_start"   => "",
    "change_finish"  => "",
    "note"           => "",
    "late"           => false,
];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataDate, $stremployee_id = "")
{
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $strDefaultStart;
  global $strDefaultFinish;
  global $arrData;
  if ($stremployee_id == "") {
    $arrData['normal_start'] = $strDefaultStart;
    $arrData['normal_finish'] = $strDefaultFinish;
    return 0;
  }
  $strResult = "";
  list($tahun, $bulan, $tanggal) = explode("-", $strDataDate);
  $dtTmp = getdate(mktime(0, 0, 0, (int)$bulan, (int)$tanggal, $tahun));
  if ($dtTmp['wday'] == 5) { //hari jumat
    // hari jumat
    $intTipe = 1;
    if (($strDefaultFinish = substr(getSetting("friday_finish_time"), 0, 5)) == "") {
      $strDefaultFinish = "18:30";
    }
  } //
  $strSQL = "SELECT * FROM hrd_employee ";
  $strSQL .= "WHERE employee_id = '$stremployee_id' AND flag = 0 ";
  $resDb = $db->execute($strSQL);
  $intRows = 0;
  if ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    //---- CARI DATA KEHADIRAN --------------------------
    //-- INISIALISASI KEHADIRAN -----------------------------------
    $strDataAttendanceStart = "";
    $strDataAttendanceFinish = "";
    $strDataNormalStart = "";
    $strDataNormalFinish = "";
    $strDataChangeStart = "";
    $strDataChangeFinish = "";
    $strDataNote = "";
    $strShift = "&nbsp;";
    // jika belum ada data, coba cari data kehadiran dari jadwal shift, jika ada
    if (isShift($db, $rowDb['id'], $strDataDate, $strDataNormalStart, $strDataNormalFinish)) {
      $strDataNormalStart = substr($strDataNormalStart, 0, 5);
      $strDataNormalFinish = substr($strDataNormalFinish, 0, 5);
      $strShift = "&radic;";
    }
    // ------------------------------------------------------------
    $strSQL = "SELECT * FROM hrd_attendance ";
    $strSQL .= "WHERE id_employee = '" . $rowDb['id'] . "' ";
    $strSQL .= "AND attendance_date = '$strDataDate' ";
    $resAtt = $db->execute($strSQL);
    if ($rowAtt = $db->fetchrow($resAtt)) {
      $strDataAttendanceStart = substr($rowAtt['attendance_start'], 0, 5);
      $strDataAttendanceFinish = substr($rowAtt['attendance_finish'], 0, 5);
      $strDataNormalStart = substr($rowAtt['normal_start'], 0, 5);
      $strDataNormalFinish = substr($rowAtt['normal_finish'], 0, 5);
      $strDataChangeStart = substr($rowAtt['change_start'], 0, 5);
      $strDataChangeFinish = substr($rowAtt['change_finish'], 0, 5);
      $strDataNote = $rowAtt['note'];
    }
    //jika data normal dan start kosong, diisi dengan default
    if ($strDataNormalStart == "") {
      $strDataNormalStart = $strDefaultStart;
    }
    if ($strDataNormalFinish == "") {
      $strDataNormalFinish = $strDefaultFinish;
    }
    // buat input data
    $arrData['actual_start'] = $strDataAttendanceStart;
    $arrData['actual_finish'] = $strDataAttendanceFinish;
    $arrData['normal_start'] = $strDataNormalStart;
    $arrData['normal_finish'] = $strDataNormalFinish;
    $arrData['change_start'] = $strDataChangeStart;
    $arrData['change_finish'] = $strDataChangeFinish;
    $arrData['note'] = $strDataNote;
  }
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "Date=$strDataDate, employee = $stremployee_id ", 0);
  }
  return 0;
} // showData
// fungsi untuk menyimpan data yang dikirim
// $db = kelas database, $strError, pesan kesalahan atau pemberitahuan sukses
function saveData($db, &$strError)
{
  global $words;
  global $messages;
  global $_SESSION;
  global $_REQUEST;
  global $arrUserInfo;
  $strError = "";
  $strDataDate = (isset($_REQUEST['dataDate'])) ? $_REQUEST['dataDate'] : "";
  $strDataEmployee = (isset($_REQUEST['dataEmployee'])) ? $_REQUEST['dataEmployee'] : "";
  $strNormalStart = (isset($_REQUEST['dataNormalStart'])) ? $_REQUEST['dataNormalStart'] : "";
  $strNormalFinish = (isset($_REQUEST['dataNormalFinish'])) ? $_REQUEST['dataNormalFinish'] : "";
  $strAttendanceStart = (isset($_REQUEST['dataAttendanceStart'])) ? $_REQUEST['dataAttendanceStart'] : "";
  $strAttendanceFinish = (isset($_REQUEST['dataAttendanceFinish'])) ? $_REQUEST['dataAttendanceFinish'] : "";
  $strChangeStart = (isset($_REQUEST['dataChangeStart'])) ? $_REQUEST['dataChangeStart'] : "";
  $strChangeFinish = (isset($_REQUEST['dataChangeFinish'])) ? $_REQUEST['dataChangeFinish'] : "";
  $strNote = (isset($_REQUEST['dataNote'])) ? $_REQUEST['dataNote'] : "";
  // ---- VALIDASI ----
  if ($strDataDate == "" || !validStandardDate($strDataDate)) {
    $strError = "Error date";
    return false;
  } else // cek apakah tanggal yang diajukan melebihi 1 hari
  {
    $intTmp = totalWorkDay($db, $strDataDate, date("Y-m-d"));
    $tmp = dateCompare(date("Y-m-d"), $strDataDate);
    if ($tmp < 0) // lewat hari ini (kemarin dan seterusnya), gak boleh
    {
      $strError = "Cannot entry date more than TODAY!";//getWords('cannot_entry_date_after_today');
      return false;
    } else if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
      if ($intTmp > (INT_EDIT_ATTENDANCE + 1)) { // diatur di commonvar
        $strError = getWords('edit_data_denied');
        return false;
      }
    }
  }
  // cek jenis hari
  $intWDay = getWDay($strDataDate);
  $bolHoliday = isHoliday($strDataDate);
  $intTipe = ($intWDay == 5) ? 1 : 0; // jumat atau bukan
  ($bolHoliday) ? $intHoliday = 1 : $intHoliday = 0;
  // ---------------- !!!!!!!!! -----------------------------------
  // cari dulu ID dari employee
  $strID = "";
  $strSQL = "SELECT id FROM hrd_employee WHERE employee_id = '$strDataEmployee' AND flag = 0 ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strID = $rowDb['id'];
  }
  if ($strID != "" && $strDataDate != "") {
    if ($strAttendanceStart != "" && $strAttendanceFinish != "") {
      $intTotalHour = getTotalHour($strAttendanceStart, $strAttendanceFinish);
    } else {
      $intTotalHour = 0;
    }
    $arrLembur = calculateOvertime(
        $db,
        $strDataDate,
        $strNormalStart,
        $strNormalFinish,
        $strAttendanceStart,
        $strAttendanceFinish
    );
    // cari apakah ada OVERTIME APPLICATION yang sudah approved
    // jika ada, simpan data lembur
    /*
    $strSQL  = "SELECT t1.id, t1.start_plan, t1.finish_plan FROM hrd_overtime_application_employee AS t1, ";
    $strSQL .= "hrd_overtime_application AS t2 WHERE t1.id_application = t2.id ";
    $strSQL .= "AND t1.id_employee = '$strID' AND t2.overtime_date = '$strDataDate' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      // cek dulu , hari libur atau bukan
      if ($bolHoliday) {
        $arrLemburApp = calculateOvertime($db,$strDataDate, $rowTmp['startPlan'],$rowTmp['finishPlan'], $strAttendanceStart, $strAttendanceFinish);
        $strTmpStart = $strAttendanceStart;
        $strTmpFinish = $strAttendanceFinish;
      } else {
        $arrLemburApp = calculateOvertime($db,$strDataDate,$strNormalStart,$rowTmp['startPlan'],$strAttendanceStart,$strAttendanceFinish);
        $strTmpStart = $rowTmp['startPlan'];
        $strTmpFinish = $strAttendanceFinish;
      }

      // update data di aplikasi lemburnya
      ($strTmpStart == "") ? $strTmpStart = "NULL" : $strTmpStart = "'$strTmpStart'";
      ($strTmpFinish == "") ? $strTmpFinish = "NULL" : $strTmpFinish = "'$strTmpFinish'";

      $strSQL  = "UPDATE hrd_overtime_application_employee ";
      $strSQL .= "SET start_actual = $strTmpStart, finish_actual = $strTmpFinish,  ";
      $strSQL .= "l1 = '" .$arrLemburApp['l1']. "', l2 = '" .$arrLemburApp['l2']. "', ";
      $strSQL .= "l3 = '" .$arrLemburApp['l3']. "', l4 = '" .$arrLemburApp['l4']. "', ";
      $strSQL .= "total_time = '" .$arrLemburApp['total']. "' ";
      $strSQL .= "WHERE id = ". $rowTmp['id'];
      $resExec = $db->execute($strSQL);
    }
    //--- end overtime application ---
    */
    //--- tanganni data yang kosong
    ($strAttendanceStart == "") ? $strAttendanceStart = "NULL" : $strAttendanceStart = "'$strAttendanceStart'";
    ($strAttendanceFinish == "") ? $strAttendanceFinish = "NULL" : $strAttendanceFinish = "'$strAttendanceFinish'";
    ($strNormalStart == "") ? $strNormalStart = $strAttendanceStart : $strNormalStart = "'$strNormalStart'";
    ($strNormalFinish == "") ? $strNormalFinish = $strAttendanceFinish : $strNormalFinish = "'$strNormalFinish'";
    ($strChangeStart == "") ? $strChangeStart = "NULL" : $strChangeStart = "'$strChangeStart'";
    ($strChangeFinish == "") ? $strChangeFinish = "NULL" : $strChangeFinish = "'$strChangeFinish'";
    $bolNotLate = ($strAttendanceStart > $strNormalStart) ? "f" : "t";
    $bolTransport = '0';
    $bolMonthly = '0';
    // hapus dulu data yang lama, jadi gak usah ngecek, langsung insert aja :D
    $strSQL = "DELETE FROM hrd_attendance ";
    $strSQL .= "WHERE id_employee = '$strID' AND attendance_date = '$strDataDate' ";
    $resExec = $db->execute($strSQL);
    //if ($strAttendanceStart == "NULL" && $strAttendanceFinish == "NULL") { // hapus data
    // nothing
    //} else {
    // ---- simpan data
    // cari info apakah ada shift atau gak
    $intShiftType = 0;
    $strSQL = "SELECT * FROM hrd_shift_schedule_employee ";
    $strSQL .= "WHERE id_employee = '$strID' AND shift_date = '$strDataDate' ";
    $resS = $db->execute($strSQL);
    if ($rowS = $db->fetchrow($resS)) {
      $intShiftType = ($rowS['startTime'] < $rowS['finishTime']) ? 1 : 2;
    }
    $strStatus = ($arrUserInfo['isDeptHead']) ? REQUEST_STATUS_CHECKED : REQUEST_STATUS_NEW; //jika dept head, langsung checked
    $strSQL = "INSERT INTO hrd_attendance ";
    $strSQL .= "(created, created_by, modified_by, id_employee, attendance_date, ";
    $strSQL .= "attendance_start, attendance_finish, normal_start, normal_finish, ";
    $strSQL .= "not_late, transport, note, total_duration, ";
    $strSQL .= "morning_overtime, late_duration, early_duration, ";
    $strSQL .= "l1, l2, l3, l4, overtime, shift_type, monthly, holiday, ";
    $strSQL .= "change_start, change_finish, status) ";
    $strSQL .= "VALUES(now(), '" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
    $strSQL .= "'$strID', '$strDataDate', $strAttendanceStart, $strAttendanceFinish, ";
    $strSQL .= "$strNormalStart, $strNormalFinish, '$bolNotLate', ";
    $strSQL .= "'$bolTransport', '$strNote', '$intTotalHour', ";
    $strSQL .= "'" . $arrLembur['morning'] . "', '" . $arrLembur['late'] . "', '" . $arrLembur['early'] . "', ";
    $strSQL .= "'" . $arrLembur['l1'] . "', '" . $arrLembur['l2'] . "', '" . $arrLembur['l3'] . "', ";
    $strSQL .= "'" . $arrLembur['l4'] . "', '" . $arrLembur['total'] . "', '$intShiftType', ";
    $strSQL .= "'$bolMonthly', '$intHoliday', $strChangeStart, $strChangeFinish,$strStatus) ";
    $resExec = $db->execute($strSQL);
    //}
  }//if
  // update data keterangan hari libur atau tidak untuk aplikasi yang dibuat
  /*
  ($bolHoliday) ? $intHoliday = 1 : $intHoliday = 0;
  $strSQL  = "UPDATE hrd_attendance SET holiday = $intHoliday ";
  $strSQL .= "WHERE attendance_date = '$strDataDate' ";
  $resExec = $db->execute($strSQL);
  */
  writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "Date=$strDataDate, employee = '$strDataEmployee' ", 0);
  $strError = $messages['data_saved'] . " >> " . date("r");
  return true;
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$strNow = date("Y-m-d");
//$intDefaultStart = "07:30";
//$intDefaultFinish = "16:30";
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  // ambil setting default start dan finish kerja
  if (($strDefaultStart = substr(getSetting("start_time"), 0, 5)) == "") {
    $strDefaultStart = "07:30";
  }
  if (($strDefaultFinish = substr(getSetting("finish_time"), 0, 5)) == "") {
    $strDefaultFinish = "18:30";
  }
  $arrData['normal_start'] = $strDefaultStart;
  $arrData['normal_finish'] = $strDefaultFinish;
  if ($bolCanEdit && (isset($_REQUEST['btnSave']))) {
    $bolError = !saveData($db, $strError);
    if ($strError != "") {
      //echo "<script>alert(\"$strError\");</script>";
      $strMessages = $strError;
      $strMsgClass = ($bolError) ? "class=bgError" : "class=bgOK";
    }
  }
  // ------ AMBIL DATA KRITERIA -------------------------
  $arrData['attendance_date'] = (isset($_REQUEST['dataDate'])) ? $_REQUEST['dataDate'] : date("Y-m-d");
  //$arrData['employee_id'] = (isset($_REQUEST['dataEmployee'])) ? $_REQUEST['dataEmployee'] : "";
  $arrData['employee_id'] = $arrUserInfo['employee_id'];
  if ($bolCanView) {
    if (validStandardDate($arrData['attendance_date']) && $arrData['employee_id'] != "") {
      // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
      getData($db, $arrData['attendance_date'], $arrData['employee_id']);
    }
  } else {
    showError("view_denied");
  }
  //--- TAMPILKAN INPUT DATA -------------------------
  // generate data hidden input dan element form input
  $intDefaultWidthPx = 200;
  if (substr($arrData['normal_start'], 0, 5) < substr($arrData['actual_start'], 0, 5)) {
    $strLateMark = "style=\"background-color:#FFCC99\"";
    $strLateText = " &nbsp;<strong>" . getWords("late") . "</strong>";
  } else {
    $strLateMark = "";
    $strLateText = "";
  }
  $strDisabledRevision = ($arrData['actual_start'] == "" && $arrData['actual_finish'] == "") ? "" : "disabled";
  $strInputDate = "<input type=input name=dataDate id=dataDate size=10 maxlength=10 value=\"" . $arrData['attendanceDate'] . "\" class='date'>";
  $strInputNormalStart = "<input type=input name=dataNormalStart id=dataNormalStart size=5 maxlength=5 value=\"" . substr(
          $arrData['normal_start'],
          0,
          5
      ) . "\" readonly>";
  $strInputNormalFinish = "<input type=input name=dataNormalFinish id=dataNormalFinish size=5 maxlength=5 value=\"" . substr(
          $arrData['normal_finish'],
          0,
          5
      ) . "\" readonly>";
  $strInputActualStart = "<input type=input name=dataAttendanceStart id=dataAttendanceStart size=5 maxlength=5 value=\"" . substr(
          $arrData['actual_start'],
          0,
          5
      ) . "\" readonly $strLateMark>";
  $strInputActualFinish = "<input type=input name=dataAttendanceFinish id=dataAttendanceFinish size=5 maxlength=5 value=\"" . substr(
          $arrData['actual_finish'],
          0,
          5
      ) . "\" readonly> $strLateText";
  $strInputChangeStart = "<input type=input name=dataChangeStart id=dataChangeStart size=5 maxlength=5 value=\"" . substr(
          $arrData['change_start'],
          0,
          5
      ) . "\" class='time-empty' $strDisabledRevision>";
  $strInputChangeFinish = "<input type=input name=dataChangeFinish id=dataChangeFinish size=5 maxlength=5 value=\"" . substr(
          $arrData['change_finish'],
          0,
          5
      ) . "\" class='time-empty' $strDisabledRevision>";
  $strInputEmployee = "<input type=input name=dataEmployee id=dataEmployee size=20 maxlength=30 value=\"" . $arrData['employee_id'] . "\">";
  $strInputNote = "<textarea name=dataNote id=dataNote cols=40 rows=2>" . $arrData['note'] . "</textarea>";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>