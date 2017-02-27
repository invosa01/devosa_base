<?php
include_once('../global/session.php');
include_once('../global/employee_function.php');
include_once('global.php');
include_once('form_object.php');
include_once('overtime_func.php');
include_once('activity.php');
$dataPrivilege = getDataPrivileges(
    basename("attendance_edit_by_employee.php"),
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
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strWordsATTENDANCEDATA = getWords("attendance data");
$strWordsEntryAttendanceByEmployee = getWords("entry attendance by employee");
$strWordsEntryAttendanceByDate = getWords("entry attendance by date");
$strWordsImportAttendance = getWords("import attendance");
$strWordsAttendanceList = getWords("attendance list");
$strWordsAttendanceReport = getWords("attendance report");
$strWordsAttendanceDate = getWords("attendance date");
$strWordsEmployeeID = getWords("employee id");
$strWordsShowData = getWords("show data");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSection = getWords("section");
$strWordsViewOption = getWords("view option");
$strWordsLISTOFEMPLOYEEATTENDANCE = getWords("list of employee attendance");
$strWordsEMPLOYEE = strtoupper(getWords("employee"));
$strWordsATTENDANCE = strtoupper(getWords("attendance"));
$strWordsNORMAL = strtoupper(getWords("normal"));
$strWordsOvertime = strtoupper(getWords("overtime"));
$strWordsOff = strtoupper(getWords("off"));
$strWordsNOTLATE = strtoupper(getWords("not late"));
$strWordsLEAVE = strtoupper(getWords("leave"));
$strWordsNOTE = strtoupper(getWords("note"));
$strWordsNO = strtoupper(getWords("no"));
$strWordsID = strtoupper(getWords("id"));
$strWordsNAME = strtoupper(getWords("employee name"));
$strWordsDEPT = strtoupper(getWords("dept"));
$strWordsSECT = strtoupper(getWords("sect"));
$strWordsSTART = strtoupper(getWords("start"));
$strWordsFINISH = strtoupper(getWords("finish"));
$strWordsSHIFT = strtoupper(getWords("shift"));
$strWordsValid = strtoupper(getWords("valid"));
$strWordsSaveData = getWords("save data");
$strWordsUseDefault = getWords("use default");
$strWordsUseNormal = getWords("use normal");
$strWordsClearData = getWords("clear data");
$strWordsUndoChanges = getWords("undo changes");
$intTotalData = 0;
$bolError = false;
$strMsgClass = $strButtons = $strMessages = $strHidden = $strPaging = $strDataDetail = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData(
    $db,
    $strDataDate,
    &$intRows,
    $strKriteria = "",
    $strOptionShow = "",
    $intPage = 1,
    $bolLimit = true,
    $strOrder = ""
) {
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $strDefaultStart;
  global $strDefaultFinish;
  global $strPaging;
  global $intTotalData;
  global $intRowsLimit;
  global $arrWorkSchedule;
  // PAGING-------------------------------------------------------------------------------------------
  $intRowsLimit = getSetting("rows_per_page");
  if (!is_numeric($intRowsLimit)) {
    $intRowsLimit = 50;
  }
  $intTipe = 0; // default hari normal
  // cari total data
  $intTotal = 0;
  if ($strOptionShow > 0) {
    $strSQL = "SELECT count(t1.id) AS total FROM hrd_attendance AS t1, ";
    $strSQL .= "hrd_employee AS t2 WHERE t1.id_employee = t2.id ";
    $strSQL .= "AND t1.attendance_date = '$strDataDate' $strKriteria ";
    if ($strOptionShow == 1) { // Overtime
      $strSQL .= "AND (t1.l1 + t1.l2) > 0 ";
    } else if ($strOptionShow == 2) { // late
      $strSQL .= "AND t1.late_duration > 0 ";
    } else if ($strOptionShow == 3) { // early
      $strSQL .= "AND t1.early_duration > 0 ";
    }
  } else {
    $strSQL = "SELECT count(id) AS total FROM hrd_employee ";
    $strSQL .= "WHERE 1=1 $strKriteria ";
  }
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
  // ----------------------------------------------------------------------------------------------------
  // cari info apakah ada shift atau gak
  $arrShift = getShiftScheduleByDate($db, $strDataDate);
  $arrWorkSchedule = getWorkSchedule($db, $strDataDate);
  // cek jenis hari
  $intWDay = getWDay($strDataDate);
  $intTipe = ($intWDay == 5) ? 1 : 0; // jumat atau bukan
  $strDataDate = pgDateFormat($strDataDate, "Y-m-d"); // format distandardkan
  $intRows = 0;
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
  if ($strOptionShow > 0) {
    // tampil data dengan kondisi tertentu
    $strSQL = "SELECT t2.* FROM hrd_attendance AS t1, hrd_employee AS t2 ";
    $strSQL .= "WHERE t1.id_employee = t2.id  $strKriteria ";
    $strSQL .= "AND t1.attendance_date = '$strDataDate' ";
    if ($strOptionShow == 1) { // Overtime
      $strSQL .= "AND (t1.l1 + t1.l2) > 0 ";
    } else if ($strOptionShow == 2) { // late
      $strSQL .= "AND t1.late_duration > 0 ";
    } else if ($strOptionShow == 3) { // early
      $strSQL .= "AND t1.early_duration > 0 ";
    }
  } else { // tampil normail
    $strSQL = "SELECT * FROM hrd_employee ";
    $strSQL .= "WHERE 1=1 $strKriteria ORDER BY $strOrder employee_id ";
  }
  if ($bolLimit) {
    $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    ($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
    $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
    $strIDEmployee = $rowDb['id'];
    //---- CARI DATA KEHADIRAN --------------------------
    //-- INISIALISASI KEHADIRAN -----------------------------------
    $strDataAttendanceStart = "";
    $strDataAttendanceFinish = "";
    $strDataNormalStart = "";
    $strDataNormalFinish = "";
    $strDataOvertimeStart = "";
    $strDataOvertimeFinish = "";
    $strDataOff = "f";
    $strDataNotLate = "t";
    $strShiftType = "";
    $strDataNote = "";
    $strDataAttendanceID = "";
    $strShift = "&nbsp;";
    $intStatus = -1;
    $strDataIsOvertime = "";
    $strAttClass = "";
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
      $strDataOvertimeStart = substr($rowAtt['overtime_start'], 0, 5);
      $strDataOvertimeFinish = substr($rowAtt['overtime_finish'], 0, 5);
      $strDataIsOvertime = ($rowAtt['is_overtime'] == 't' && $rowAtt['overtime'] > 0) ? "&radic;" : "";
      $strDataOff = $rowAtt['holiday'];
      $strDataNotLate = $rowAtt['not_late'];
      $strShiftType = $rowAtt['code_shift_type'];
      $strDataNote = $rowAtt['note'];
      $strDataAttendanceID = $rowAtt['id'];
      $intStatus = $rowAtt['status'];
      $strAttClass = ($rowAtt['flag'] == 1) ? "style=\"background-color:red\"" : "";
    } // 1. cek dari shift
    else if (isset($arrShift[$strIDEmployee])) {
      $arrTemp = $arrShift[$strIDEmployee];
      $strShiftType = $arrTemp['shift_code'];
      if ($arrTemp['shift_off'] != "t") {
        $strDataNormalStart = substr($arrTemp['start_time'], 0, 5);
        $strDataNormalFinish = substr($arrTemp['finish_time'], 0, 5);
        $bolHoliday = false;
      } else {
        $strDataNormalStart = "";
        $strDataNormalFinish = "";
        $bolHoliday = true;
      }
    } // 2. cek dari work schedule
    else if (isset($arrWorkSchedule[$strIDEmployee])) {
      $arrTemp = $arrWorkSchedule[$strIDEmployee];
      if ($arrTemp['day_off'] != "t") {
        $strDataNormalStart = $arrTemp['start_time'];
        $strDataNormalFinish = $arrTemp['finish_time'];
        // cek hari libur
        $bolHoliday = isHoliday($strDataDate);
      } else {
        $strDataNormalStart = "";
        $strDataNormalFinish = "";
        $bolHoliday = true;
      }
    } else {
      $bolHoliday = isHoliday($strDataDate);
      if (!$bolHoliday) {
        $strDataNormalStart = $strDefaultStart;
        $strDataNormalFinish = $strDefaultFinish;
      }
    }
    // buat input data
    $strDataAttendanceStart = "<input type=text size=5 maxsize=5 name=detailAttendanceStart$intRows id=detailAttendanceStart$intRows value=\"$strDataAttendanceStart\" onChange=\"checkLate($intRows);\"  >";
    $strDataAttendanceFinish = "<input type=text size=5 maxsize=5 name=detailAttendanceFinish$intRows id=detailAttendanceFinish$intRows value=\"$strDataAttendanceFinish\"  >";
    $strDataNormalStart = "<input type=text size=5 maxsize=5 name=detailNormalStart$intRows id=detailNormalStart$intRows value=\"$strDataNormalStart\" onChange=\"checkLate($intRows);\"  >";
    $strDataNormalFinish = "<input type=text size=5 maxsize=5 name=detailNormalFinish$intRows id=detailNormalFinish$intRows value=\"$strDataNormalFinish\"  >";
    $strDisableChk = "";
    $strChecked = ($bolHoliday) ? "checked" : "";
    $strDataOff = "<input type=checkbox name=detailOff$intRows id=detailOff$intRows value=\"t\" $strChecked>";
    $strChecked = ($strDataNotLate == 't') ? "checked" : "";
    $strDataNotLate = "<input type=checkbox name=detailNotLate$intRows id=detailNotLate$intRows value=\"t\" $strChecked>";
    $strDataNote = "<input type=text size=30 maxsize=90 name=detailNote$intRows id=detailNote$intRows value=\"$strDataNote\">";
    $strDataChkStatus = "<input type=checkbox name='chkID$intRows' value=\"$strDataAttendanceID\">";
    $strDataAttendanceID = "<input type=hidden name=detailAttendanceID$intRows id=detailAttendanceID$intRows value=\"$strDataAttendanceID\">";
    // cek status
    /*
    $strIsNew = "checked";
    $strIsCheck = ($intStatus > 0) ? "checked" : "";
    $strIsApproved = ($intStatus == 2) ? "checked" : "";
    $strIsCancel = ($intStatus == 3) ? "checked" : "";

    $strClass = getCssClass($intStatus);
    */
    $strClass = "";
    $strOTClass = "";
    if ($strDataIsOvertime != "") {
      $strOTClass = "class=\"bgHoliday\"";
    }
    // ----- TAMPILKAN DATA ---------------------------------------
    $strResult .= "<tr valign=top id=detailData$intRows title=\"$strEmployeeInfo\" $strClass>\n";
    $strResult .= "  <td nowrap align=right>$intRows.&nbsp;$strDataAttendanceID</td>";
    $strResult .= "  <td nowrap><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">" . $rowDb['employee_id'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap>" . $rowDb['employee_name'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['department_code'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['section_code'] . "&nbsp;</td>";
    $strResult .= "  <td align=\"center\" $strAttClass>" . $strDataAttendanceStart . "</td>";
    $strResult .= "  <td align=\"center\" $strAttClass>" . $strDataAttendanceFinish . "</td>";
    $strResult .= "  <td align=\"center\">" . $strDataNormalStart . "</td>";
    $strResult .= "  <td align=\"center\">" . $strDataNormalFinish . "</td>";
    $strResult .= "  <td align=\"center\" $strOTClass>" . $strDataOvertimeStart . "</td>";
    $strResult .= "  <td align=\"center\" $strOTClass>" . $strDataOvertimeFinish . "</td>";
    $strResult .= "  <td align=\"center\" $strOTClass>" . $strDataIsOvertime . "</td>";
    $strResult .= "  <td>" . $strDataOff . "</td>";
    $strResult .= "  <td>" . $strDataNotLate . "</td>";
    $strResult .= "  <td>" . $strShiftType . "</td>";
    $strResult .= "  <td>" . $strDataNote . "</td>";
    $strResult .= "</tr>\n";
  }
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "Date=$strDataDate", 0);
  }
  return $strResult;
} // showData
// fungsi untuk menyimpan data yang dikirim
// $db = kelas database, $strError, pesan kesalahan atau pemberitahuan sukses
function saveData($db, &$strError)
{
  include_once('activity.php');
  global $words;
  global $messages;
  global $_SESSION;
  global $_REQUEST;
  global $strKriteria;
  $strError = "";
  (isset($_REQUEST['totalData'])) ? $intTotal = $_REQUEST['totalData'] : $intTotal = 0;
  (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = "";
  // ---- VALIDASI ----
  if ($strDataDate == "" || !validStandardDate($strDataDate)) {
    $strError = "Error date";
    return false;
  }
  // cek jenis hari
  $intWDay = getWDay($strDataDate);
  $bolHoliday = isHoliday($strDataDate);
  $intTipe = ($intWDay == 5) ? 1 : 0; // jumat atau bukan
  $strDataDate = pgDateFormat($strDataDate, "Y-m-d"); // format distandardkan
  // cek data ke-TIDAKHADIRAN--
  $arrOut = getOutOfficeInfo($db, $strDataDate, $strDataDate); //-activity.php
  // cari info apakah ada shift atau gak
  $arrShift = [];
  $intShiftType = 0;
  $strSQL = "SELECT *, t2.shift_off FROM hrd_shift_schedule_employee AS t1 ";
  $strSQL .= "LEFT JOIN hrd_shift_type AS t2 ON t1.shift_code = t2.code ";
  $strSQL .= "WHERE shift_date = '$strDataDate' ";
  $resS = $db->execute($strSQL);
  $strCodeShiftType = [];
  while ($rowS = $db->fetchrow($resS)) {
    $arrShift[$rowS['id_employee']] = $rowS;
  }
  // ---------------- !!!!!!!!! -----------------------------------
  for ($i = 1; $i <= $intTotal; $i++) {
    (isset($_REQUEST['detailID' . $i])) ? $strID = $_REQUEST['detailID' . $i] : $strID = "";
    if ($strID != "") {
      $strAttendanceStart = (isset($_REQUEST['detailAttendanceStart' . $i])) ? $_REQUEST['detailAttendanceStart' . $i] : "";
      $strAttendanceFinish = (isset($_REQUEST['detailAttendanceFinish' . $i])) ? $_REQUEST['detailAttendanceFinish' . $i] : "";
      $strNormalStart = (isset($_REQUEST['detailNormalStart' . $i])) ? $_REQUEST['detailNormalStart' . $i] : "";
      $strNormalFinish = (isset($_REQUEST['detailNormalFinish' . $i])) ? $_REQUEST['detailNormalFinish' . $i] : "";
      $strOvertimeStart = (isset($_REQUEST['detailOvertimeStart' . $i])) ? $_REQUEST['detailOvertimeStart' . $i] : "";
      $strOvertimeFinish = (isset($_REQUEST['detailOvertimeFinish' . $i])) ? $_REQUEST['detailOvertimeFinish' . $i] : "";
      $strHoliday = (isset($_REQUEST['detailOff' . $i])) ? 't' : 'f';
      $intHoliday = (isset($_REQUEST['detailOff' . $i])) ? 1 : 0;
      $bolNotLate = (isset($_REQUEST['detailNotLate' . $i])) ? 't' : 'f';
      $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
      $strShift = (isset($_REQUEST['detailShift' . $i])) ? $_REQUEST['detailShift' . $i] : "";
      $strAttendanceID = (isset($_REQUEST['detailAttendanceID' . $i])) ? $_REQUEST['detailAttendanceID' . $i] : "";
      $strCodeShiftType = (isset($arrShift[$strID]['shift_code'])) ? $arrShift[$strID]['shift_code'] : "";
      // data status
      $intStatus = REQUEST_STATUS_NEW;//($_SESSION['sessionUserRole'] == 4) ? 2 : 1;
      if ($strAttendanceStart != "" && $strAttendanceFinish != "") {
        $intTotalHour = getTotalHour($strAttendanceStart, $strAttendanceFinish);
      } else {
        $intTotalHour = 0;
      }
      //handle overtime hari libur
      if ($intHoliday == 1 && $strAttendanceStart != "" && $strAttendanceFinish != "" && $strAttendanceStart != "null" && $strAttendanceFinish != "null") {
        if ($strNormalStart == "" && $strNormalFinish == "") {
          $strNormalStart = $strNormalFinish = $strAttendanceStart;
        }
      }
      $arrLembur = calculateOvertime(
          $db,
          $strDataDate,
          $strNormalStart,
          $strNormalFinish,
          $strAttendanceStart,
          $strAttendanceFinish,
          "",
          "",
          $bolHoliday,
          true
      );
      //--- tanganni data yang kosong
      ($strAttendanceStart == "") ? $strAttendanceStart = "NULL" : $strAttendanceStart = "'$strAttendanceStart'";
      ($strAttendanceFinish == "") ? $strAttendanceFinish = "NULL" : $strAttendanceFinish = "'$strAttendanceFinish'";
      ($strNormalStart == "") ? $strNormalStart = $strAttendanceStart : $strNormalStart = "'$strNormalStart'";
      ($strNormalFinish == "") ? $strNormalFinish = $strAttendanceFinish : $strNormalFinish = "'$strNormalFinish'";
      if ($strAttendanceStart == "NULL" && $strAttendanceFinish == "NULL") { // hapus data
        $strSQL = "DELETE FROM hrd_attendance ";
        $strSQL .= "WHERE id_employee = '$strID' AND attendance_date = '$strDataDate' ";
        $resExec = $db->execute($strSQL);
      } else {
        // ---- simpan data
        $intShiftType = 0;
        if (isset($arrShift[$strID])) {
          $intShiftType = ($arrShift[$strID]['start_time'] < $arrShift[$strID]['finish_time']) ? 1 : 2;
        }
        // cek status isAbsence
        if (isset($arrOut[$strDataDate][$strID])) {
          if ($arrOut[$strDataDate][$strID]['type'] == 0 && $arrOut[$strDataDate][$strID]['code'] == "") {
            $strIsAbsence = 'f';
          } else {
            $strIsAbsence = 't';
          }
        } else {
          $strIsAbsence = "f";
        }
        if ($strAttendanceID == "") { // data baru
          $strSQL = "DELETE FROM hrd_attendance ";
          $strSQL .= "WHERE id_employee = '$strID' AND attendance_date = '$strDataDate'; "; // hapus dulu, biar gak ada duplikasi data untuk tgl yg sama
          $strSQL .= "INSERT INTO hrd_attendance ";
          $strSQL .= "(created, created_by, modified_by, id_employee, attendance_date, ";
          $strSQL .= "attendance_start, attendance_finish, normal_start, normal_finish, ";
          //$strSQL .= "change_start, change_finish, ";
          $strSQL .= "holiday, not_late, note, total_duration, ";
          $strSQL .= "morning_overtime, late_duration, early_duration, ";
          $strSQL .= "l1, l2, l3, l4, overtime, shift_type, status, is_absence, code_shift_type) ";
          $strSQL .= "VALUES(now(), '" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
          $strSQL .= "'$strID', '$strDataDate', $strAttendanceStart, $strAttendanceFinish, ";
          $strSQL .= "$strNormalStart, $strNormalFinish, ";
          //$strSQL .= "$strAttendanceStart, $strAttendanceFinish, ";
          $strSQL .= "'$intHoliday', '$bolNotLate', '$strNote', '$intTotalHour', ";
          $strSQL .= "'0', '" . $arrLembur['late'] . "', '" . $arrLembur['early'] . "', ";
          $strSQL .= "'0', '0', '0', '0', '0', '$intShiftType', ";
          $strSQL .= "$intStatus, '$strIsAbsence', '" . $strCodeShiftType . "') ";
          //echo $strSQL;
          $resExec = $db->execute($strSQL);
        } else { // data lama
          $strSQL = "UPDATE hrd_attendance SET created=now(), ";
          $strSQL .= "modified_by = '" . $_SESSION['sessionUserID'] . "', ";
          $strSQL .= "id_employee = '$strID', attendance_date = '$strDataDate', ";
          $strSQL .= "attendance_start = $strAttendanceStart, attendance_finish = $strAttendanceFinish, ";
          $strSQL .= "normal_start = $strNormalStart, normal_finish = $strNormalFinish, ";
          $strSQL .= "holiday = '$intHoliday', not_late = '$bolNotLate', ";
          $strSQL .= "note = '$strNote', total_duration = '$intTotalHour', ";
          $strSQL .= "morning_overtime = '" . $arrLembur['morning'] . "', ";
          $strSQL .= "late_duration = '" . $arrLembur['late'] . "', ";
          $strSQL .= "early_duration = '" . $arrLembur['early'] . "', ";
          $strSQL .= "shift_type = '$intShiftType',  is_absence = '$strIsAbsence',";
          $strSQL .= "status = $intStatus, ";
          $strSQL .= "code_shift_type = '$strCodeShiftType' WHERE id = '$strAttendanceID' ";
          $resExec = $db->execute($strSQL);
          //echo $strSQL;
        }
        // hapus data absensi yagn digenerate system
        deleteSystemGeneratedAbsence($db, $strID, $strDataDate);
      }
    }//if
  }//for
  //adjusting attendance and overtime plan ==> overtime_func.php
  syncOvertimeApplication($db, $strDataDate, $strDataDate, $strIDEmployee);
  checkInvalidAttendance($db, $strDataDate, $strDataDate, $strKriteria);
  writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "Date=$strDataDate", 0);
  $strError = $messages['data_saved'] . " >> " . date("r");
  return true;
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
//$intDefaultStart = "07:30";
//$intDefaultFinish = "16:30";
$db = new CdbClass;
if ($db->connect()) {
  // ambil setting default start dan finish kerja
  if (($strDefaultStart = substr(getSetting("start_time"), 0, 5)) == "") {
    $strDefaultStart = "07:30";
  }
  if (($strDefaultFinish = substr(getSetting("finish_time"), 0, 5)) == "") {
    $strDefaultFinish = "18:30";
  }
  $strDataEmployee = (isset($_SESSION['sessionFilterEmployee'])) ? $_SESSION['sessionFilterEmployee'] : "";
  $strDataActive = (isset($_SESSION['sessionFilterActive'])) ? $_SESSION['sessionFilterActive'] : "";
  // ------ AMBIL DATA KRITERIA -------------------------
  $strDataDate = (isset($_REQUEST['dataDate'])) ? $_REQUEST['dataDate'] : date("Y-m-d");
  $strDataDivision = (isset($_REQUEST['dataDivision'])) ? $_REQUEST['dataDivision'] : "";
  $strDataDepartment = (isset($_REQUEST['dataDepartment'])) ? $_REQUEST['dataDepartment'] : "";
  $strDataSection = (isset($_REQUEST['dataSection'])) ? $_REQUEST['dataSection'] : "";
  $strDataSubsection = (isset($_REQUEST['dataSubsection'])) ? $_REQUEST['dataSubsection'] : "";
  $strDataActive = (isset($_REQUEST['dataActive'])) ? $_REQUEST['dataActive'] : "";
  $strDataView = (isset($_REQUEST['dataView'])) ? $_REQUEST['dataView'] : "";
  $intCurrPage = (isset($_REQUEST['dataPage'])) ? $_REQUEST['dataPage'] : 1;
  if (isset($_REQUEST['dataActive'])) {
    $strDataActive = $_REQUEST['dataActive'];
  }
  if (isset($_REQUEST['dataEmployee'])) {
    $strDataEmployee = $_REQUEST['dataEmployee'];
  }
  $_SESSION['sessionFilterActive'] = $strDataActive;
  $_SESSION['sessionFilterEmployee'] = $strDataEmployee;
  if (!is_numeric($intCurrPage)) {
    $intCurrPage = 1;
  }
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if ($strDataDivision != "") {
    $strKriteria .= "AND division_code = '$strDataDivision' ";
  }
  if ($strDataDepartment != "") {
    $strKriteria .= "AND department_code = '$strDataDepartment' ";
  }
  if ($strDataSection != "") {
    $strKriteria .= "AND section_code = '$strDataSection' ";
  }
  if ($strDataSubsection != "") {
    $strKriteria .= "AND sub_section_code = '$strDataSubsection' ";
  }
  if ($strDataActive != "") {
    $strKriteria .= "AND active = '$strDataActive' ";
  }
  if ($strDataEmployee != "") {
    $strKriteria .= "AND employee_id = '$strDataEmployee' ";
  }
  $strKriteria .= $strKriteriaCompany;
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      $bolError = !saveData($db, $strError);
      if ($strError != "") {
        $strMessages = $strError;
        $strMsgClass = ($bolError) ? "class=bgError" : "class=bgOK";
      }
    }
  }
  if ($bolCanView) {
    if (validStandardDate($strDataDate) && isset($_REQUEST['btnShow'])) {
      // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
      $strDataDetail = getData($db, $strDataDate, $intTotalData, $strKriteria, $strDataView, $intCurrPage, false);
      $strHidden .= "<input type=hidden name=btnShow value=show>";
    } else {
      $strDataDetail = "";
    }
  } else {
    $strDataDetail = "";
    showError("view_denied");
  }
  //--- TAMPILKAN INPUT DATA -------------------------
  // generate data hidden input dan element form input
  $intDefaultWidthPx = 200;
  $strInputDate = "<input type=text name=dataDate id=dataDate size=15 maxlength=10 value=\"$strDataDate\">";
  $strInputDivision = getDivisionList(
      $db,
      "dataDivision",
      $strDataDivision,
      $strEmptyOption,
      "",
      "style=\"width:$intDefaultWidthPx\""
  );
  $strInputDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $strDataDepartment,
      $strEmptyOption,
      "",
      "style=\"width:$intDefaultWidthPx\""
  );
  $strInputSection = getSectionList(
      $db,
      "dataSection",
      $strDataSection,
      $strEmptyOption,
      "",
      "style=\"width:$intDefaultWidthPx\""
  );
  $strInputSubsection = getSubSectionList(
      $db,
      "dataSubsection",
      $strDataSubsection,
      $strEmptyOption,
      "",
      "style=\"width:$intDefaultWidthPx\""
  );
  //$strInputGroup = getGroupList($db,"dataGroup",$strDataGroup, $strEmptyOption,""," style=\"width:$intDefaultWidthPx\"");
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=20 maxlength=30 value=\"$strDataEmployee\" style=\"width:$intDefaultWidthPx\">";
  $strInputActive = getEmployeeActiveList(
      "dataActive",
      $strDataActive,
      $strEmptyOption,
      " style=\"width:$intDefaultWidthPx\""
  );
  //handle user company-access-right
  $strInputCompany = getCompanyList(
      $db,
      "dataCompany",
      $strDataCompany,
      $strEmptyOption2,
      $strKriteria2,
      "style=\"width:$intDefaultWidthPx\" "
  );
  $arrViewOption = ["", "overtime", "late", "early leave"];
  $strInputView = "<select name=dataView style=\"width:$intDefaultWidthPx\">\n";
  foreach ($arrViewOption AS $idx => $str) {
    if ($idx == 0) {
      $strInputView .= "  <option value=''> </option>\n";
    } else {
      $strSelect = ($idx == $strDataView) ? "selected" : "";
      $strInputView .= "  <option value='$idx' $strSelect>" . $words[$str] . "</option>\n";
    }
  }
  $strInputView .= "</select>\n";
  // informasi tanggal kehadiran
  $strHari = strtoupper(getDayName($strDataDate));
  $strInfo .= "<br>$strHari, " . strtoupper(pgDateFormat($strDataDate, "d-M-Y"));
  $strHidden .= "<input type=hidden name=dataDate value=\"$strDataDate\">";
  $strHidden .= "<input type=hidden name=dataDivision value=\"$strDataDivision\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
  $strHidden .= "<input type=hidden name=dataActive value=\"$strDataActive\">";
  $strHidden .= "<input type=hidden name=dataSubsection value=\"$strDataSubsection\">";
  //$strHidden .= "<input type=hidden name=dataGroup value=\"$strDataGroup\">";
  $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
  $strHidden .= "<input type=hidden name=dataView value=\"$strDataView\">";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
if ($bolPrint) {
  $strMainTemplate = getTemplate(str_replace(".php", "_print.html", basename($_SERVER['PHP_SELF'])));
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>