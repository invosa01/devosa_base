<?php
include_once('../global/session.php');
include_once('../global/employee_function.php');
include_once('global.php');
include_once('form_object.php');
include_once('attendance_functions.php');
include_once('overtime_func.php');
include_once('activity.php');
// $dataPrivilege = getDataPrivileges(basename($_SERVER['PHP_SELF']), $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge);
$dataPrivilege = getDataPrivileges(
    "attendance_edit_by_employee.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
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
$strWordsDate = strtoupper(getWords("date"));
$strWordsDateFrom = getWords("date from");
$strWordsDateThru = getWords("date thru");
$strWordsEmployeeID = getWords("employee id");
$strWordsEmployeeName = strtoupper(getWords("employee name"));
$strWordsShowData = getWords("show data");
$strWordsExport = getWords("export");
$strWordsExportAll = getWords("export all");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSection = getWords("section");
$strWordsViewOption = getWords("view option");
$strWordsLISTOFEMPLOYEEATTENDANCE = getWords("list of employee attendance");
$strWordsEMPLOYEE = strtoupper(getWords("employee"));
$strWordsAttendance = strtoupper(getWords("attendance"));
$strWordsNormal = strtoupper(getWords("normal"));
$strWordsEarlyOvertime = strtoupper(getWords("early overtime "));
$strWordsOvertime = strtoupper(getWords("overtime"));
$strWordsOvertimePlan = strtoupper(getWords("overtime plan"));
$strWordsTotalOvertime = strtoupper(getWords("total overtime"));
$strWordsOff = strtoupper(getWords("off"));
$strWordsNOTLATE = strtoupper(getWords("not late"));
$strWordsLEAVE = strtoupper(getWords("leave"));
$strWordsNote = strtoupper(getWords("note"));
$strWordsNO = strtoupper(getWords("no"));
$strWordsID = strtoupper(getWords("id"));
$strWordsNAME = strtoupper(getWords("employee name"));
$strWordsDEPT = strtoupper(getWords("dept"));
$strWordsSECT = strtoupper(getWords("sect"));
$strWordsStart = strtoupper(getWords("start"));
$strWordsFinish = strtoupper(getWords("finish"));
$strWordsShift = strtoupper(getWords("shift"));
$strWordsValid = strtoupper(getWords("valid"));
$strWordsEarly = strtoupper(getWords("early"));
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
    $strDataDateFrom,
    $strDataDateThru,
    $strDataEmployee,
    &$intRows,
    $strKriteria = "",
    $intPage = 1,
    $bolLimit = false
) {
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $strDefaultStart;
  global $strDefaultFinish;
  global $strPaging;
  global $intRowsLimit;
  global $arrWorkSchedule;
  $strResult = "";
  $rowDb = getEmployeeInfoByCode($db, $strDataEmployee, "id, employee_name");
  $strIDEmployee = $rowDb['id'];
  // PAGING-------------------------------------------------------------------------------------------
  /*
  $intRowsLimit = getSetting("rows_per_page");
  if (!is_numeric($intRowsLimit)) $intRowsLimit = 50;
  $intTipe = 0; // default hari normal

  // cari total data
  $intTotal = getIntervalDate($strDataDateFrom, $strDataDateThru) + 1;


  $strPaging = getPaging($intPage,$intTotal,"javascript:goPage('[PAGE]')");
  if ($strPaging == "") {
    $strPaging = "1&nbsp;";
  }
  $intStart = (($intPage -1) * $intRowsLimit);
  */
  $strPaging = "";
  // ----------------------------------------------------------------------------------------------------
  $objAttendanceClass = new clsAttendanceClass($db);
  $objAttendanceClass->resetAttendance();
  $objAttendanceClass->setFilter(getNextDate($strDataDateFrom, -1), $strDataDateThru, $strIDEmployee, $strKriteria);
  $objAttendanceClass->getAttendanceResource();
  $objToday = new clsAttendanceInfo($db);
  $objYesterday = new clsAttendanceInfo($db);
  $strDataDate = pgDateFormat($strDataDateFrom, "Y-m-d"); // format distandardkan
  $fltTotalMonthlyOT = 0;
  $intRows = 0;
  $strClass = "class=\"bgReadonly\"";
  while (dateCompare($strDataDate, $strDataDateThru) <= 0) {
    $objToday->newInfo($strIDEmployee, $strDataDate);
    $objYesterday->newInfo($strIDEmployee, getNextDate($strDataDate, -1));
    $objToday->initAttendanceInfo($objAttendanceClass);
    $objYesterday->initAttendanceInfo($objAttendanceClass);
    $arrOT = $objToday->getEmployeeOvertimeApplication($objAttendanceClass);
    $intRows++;
    //---- CARI DATA KEHADIRAN --------------------------
    //-- INISIALISASI KEHADIRAN -----------------------------------
    $strDataAttendanceStart = $objToday->strAttendanceStart;
    $strDataAttendanceFinish = $objToday->strAttendanceFinish;
    $strDataNormalStart = $objToday->strNormalStart;
    $strDataNormalFinish = $objToday->strNormalFinish;
    if (count($arrOT) == 0) {
      $strDataOvertimeStartEarlyPlan =
      $strDataOvertimeFinishEarlyPlan =
      $strDataOvertimeStartPlan =
      $strDataOvertimeFinishPlan = "";
    } else {
      $strDataOvertimeStartEarlyPlan = substr($arrOT['start_early_plan'], 0, 5);
      $strDataOvertimeFinishEarlyPlan = substr($arrOT['finish_early_plan'], 0, 5);
      $strDataOvertimeStartPlan = substr($arrOT['start_plan'], 0, 5);
      $strDataOvertimeFinishPlan = substr($arrOT['finish_plan'], 0, 5);
    }
    $strDataOvertimeStartEarly = $objToday->strOvertimeStartEarly;
    $strDataOvertimeFinishEarly = $objToday->strOvertimeFinishEarly;
    $strDataOvertimeStart = $objToday->strOvertimeStart;
    $strDataOvertimeFinish = $objToday->strOvertimeFinish;
    $fltTotalOT = $objToday->fltTotalOT;
    $fltTotalOTHour = ($fltTotalOT == 0) ? "" : addPrevZero(floor($fltTotalOT / 60), 2);
    $fltTotalOTMin = ($fltTotalOT == 0) ? "" : addPrevZero($fltTotalOT % 60, 2);
    $strIsOvertime = $objToday->strIsOvertime;
    //$strNotOvertime             = $objToday->strNotOvertime;
    $strDataNotLate = ($objToday->bolLate) ? "t" : "f";
    $strDataNote = $objToday->strNote;
    $strDataShiftCode = $objToday->strShiftCode;
    $bolShiftNight = $objToday->bolShiftNight;
    $strDataAttendanceID = $objToday->strAttendanceID;
    //$intStatus                  = $objToday->strStatus;
    $bolHoliday = $objToday->bolHoliday;
    $bolGetAutoOT = ($objToday->strOvertimeStartEarlyAuto != "" || $objToday->strOvertimeStartAuto != "");
    // buat input data
    $strDataAttendanceStart = "<input type=text size=5 maxsize=5 name=detailAttendanceStart$intRows id=detailAttendanceStart$intRows value=\"$strDataAttendanceStart\" onChange=\"checkLate($intRows);\"  >";
    $strDataAttendanceFinish = "<input type=text size=5 maxsize=5 name=detailAttendanceFinish$intRows id=detailAttendanceFinish$intRows value=\"$strDataAttendanceFinish\"  >";
    $strDataNormalStart = "<input type=text size=5 maxsize=5 name=detailNormalStart$intRows id=detailNormalStart$intRows value=\"$strDataNormalStart\" onChange=\"checkLate($intRows);\"  >";
    $strDataNormalFinish = "<input type=text size=5 maxsize=5 name=detailNormalFinish$intRows id=detailNormalFinish$intRows value=\"$strDataNormalFinish\"  >";
    $strDataOvertimeStartEarlyPlan = "<input type=text size=5 maxsize=5 name=detailOvertimeStartEarlyPlan$intRows id=detailOvertimeStartEarlyPlan$intRows value=\"$strDataOvertimeStartEarlyPlan\" readonly >";
    $strDataOvertimeFinishEarlyPlan = "<input type=text size=5 maxsize=5 name=detailOvertimeFinishEarlyPlan$intRows id=detailOvertimeFinishEarlyPlan$intRows value=\"$strDataOvertimeFinishEarlyPlan\" readonly >";
    $strDataOvertimeStartPlan = "<input type=text size=5 maxsize=5 name=detailOvertimeStartPlan$intRows id=detailOvertimeStartPlan$intRows value=\"$strDataOvertimeStartPlan\" readonly >";
    $strDataOvertimeFinishPlan = "<input type=text size=5 maxsize=5 name=detailOvertimeFinishPlan$intRows id=detailOvertimeFinishPlan$intRows value=\"$strDataOvertimeFinishPlan\" readonly >";
    $strDataOvertimeStartEarly = "<input type=text size=5 maxsize=5 name=detailOvertimeStartEarly$intRows id=detailOvertimeStartEarly$intRows value=\"$strDataOvertimeStartEarly\" readonly >";
    $strDataOvertimeFinishEarly = "<input type=text size=5 maxsize=5 name=detailOvertimeFinishEarly$intRows id=detailOvertimeFinishEarly$intRows value=\"$strDataOvertimeFinishEarly\" readonly >";
    $strDataOvertimeStart = "<input type=text size=5 maxsize=5 name=detailOvertimeStart$intRows id=detailOvertimeStart$intRows value=\"$strDataOvertimeStart\" readonly >";
    $strDataOvertimeFinish = "<input type=text size=5 maxsize=5 name=detailOvertimeFinish$intRows id=detailOvertimeFinish$intRows value=\"$strDataOvertimeFinish\" readonly >";
    $strDataShiftCode = "<input type=text size=5 maxsize=5 name=detailShiftCode$intRows id=detailShiftCode$intRows value=\"$strDataShiftCode\" readonly>";
    $strChecked = ($bolHoliday) ? "checked" : "";
    $strDataOff = "<input type=checkbox name=detailOff$intRows id=detailOff$intRows value=\"t\" $strChecked>";
    $strChecked = ($strDataNotLate == 't') ? "checked" : "";
    $strDataNotLate = "<input type=checkbox name=detailNotLate$intRows id=detailNotLate$intRows value=\"t\" $strChecked>";
    $strDataChkStatus = "<input type=checkbox name='chkID$intRows' value=\"$strDataAttendanceID\">";
    $strDataAttendanceID = "<input type=hidden name=detailAttendanceID$intRows id=detailAttendanceID$intRows value=\"$strDataAttendanceID\">";
    $strDataTotalOT = "<input type=text size=3 maxsize=5 name=detailTotalOTHour$intRows id=detailTotalOTHour$intRows value=\"" . $fltTotalOTHour . "\" readonly> : <input type=text size=3 maxsize=5 name=detailTotalOTMin$intRows id=detailTotalOTMin$intRows value=\"" . $fltTotalOTMin . "\" readonly>";
    $fltTotalMonthlyOT += $fltTotalOT;
    $strChecked = ($bolGetAutoOT) ? "checked" : "";
    $strDataGetAutoOT = "<input type=checkbox name=detailGetAutoOT$intRows id=detailGetAutoOT$intRows value=\"t\" $strChecked>";
    $strDataNote = "<input type=text size=5 maxsize=5 name=detailNote$intRows id=detailNote$intRows value=\"$strDataNote\" >";
    // ----- TAMPILKAN DATA ---------------------------------------
    $strResult .= "<tr valign=top id=detailData$intRows>\n";
    $strResult .= "  <td nowrap align=right>$intRows.&nbsp;$strDataAttendanceID</td>";
    $strResult .= "  <td nowrap>" . $rowDb['employee_name'] . "</td>";
    $strResult .= "  <td nowrap><input type=hidden name=detailID$intRows value=\"" . $strDataDate . "\">" . $strDataDate . "&nbsp;</td>";
    $strResult .= "  <td align=\"center\">" . $strDataNormalStart . "</td>";
    $strResult .= "  <td align=\"center\">" . $strDataNormalFinish . "</td>";
    $strResult .= "  <td align=\"center\" $strClass>" . $strDataAttendanceStart . "</td>";
    $strResult .= "  <td align=\"center\" $strClass>" . $strDataAttendanceFinish . "</td>";
    //$strResult .= "  <td>" .$strDataShiftCode."</td>";
    $strResult .= "  <td align=\"center\">" . $strDataOff . "</td>";
    $strResult .= "  <td align=\"center\">" . $strDataGetAutoOT . "</td>";
    $strResult .= "  <td align=\"center\" $strClass>" . $strDataOvertimeStartEarlyPlan . "</td>";
    $strResult .= "  <td align=\"center\" $strClass>" . $strDataOvertimeFinishEarlyPlan . "</td>";
    $strResult .= "  <td align=\"center\" >" . $strDataOvertimeStartPlan . "</td>";
    $strResult .= "  <td align=\"center\" >" . $strDataOvertimeFinishPlan . "</td>";
    $strResult .= "  <td align=\"center\" $strClass>" . $strDataOvertimeStartEarly . "</td>";
    $strResult .= "  <td align=\"center\" $strClass>" . $strDataOvertimeFinishEarly . "</td>";
    $strResult .= "  <td align=\"center\" >" . $strDataOvertimeStart . "</td>";
    $strResult .= "  <td align=\"center\" >" . $strDataOvertimeFinish . "</td>";
    $strResult .= "  <td align=\"center\" $strClass>" . $strDataTotalOT . "</td>";
    $strResult .= "  <td>" . $strDataNote . "</td>";
    //$strResult .= "  <td>" .$strDataNotLate. "</td>";
    $strResult .= "</tr>\n";
    $strDataDate = getNextDate($strDataDate);
  }
  $fltTotalOTMonthlyHour = addPrevZero(floor($fltTotalMonthlyOT / 60), 2);
  $fltTotalOTMonthlyMin = addPrevZero($fltTotalMonthlyOT % 60, 2);
  $strDataTotalOT = "<input type=text size=3 maxsize=5 name=TotalOTHour value=\"" . $fltTotalOTMonthlyHour . "\" readonly> : <input type=text size=3 maxsize=5 name=TotalOTMin value=\"" . $fltTotalOTMonthlyMin . "\" readonly>";
  $strResult .= "<tr><td colspan=17>&nbsp;</td><td align=\"center\"> " . $strDataTotalOT . "</strong></td><td>&nbsp;</td></tr>";
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
  $intTotal = (isset($_REQUEST['totalData'])) ? $_REQUEST['totalData'] : 0;
  $strDataDateFrom = (isset($_REQUEST['dataDateFrom'])) ? $_REQUEST['dataDateFrom'] : "";
  $strDataDateThru = (isset($_REQUEST['dataDateThru'])) ? $_REQUEST['dataDateThru'] : "";
  $strDataEmployee = (isset($_REQUEST['dataEmployee'])) ? $_REQUEST['dataEmployee'] : "";
  // ---- VALIDASI ----
  if ($strDataDateFrom == "" || !validStandardDate($strDataDateFrom)) {
    $strError = "Error date";
    return false;
  }
  if ($strDataDateThru == "" || !validStandardDate($strDataDateThru)) {
    $strError = "Error date";
    return false;
  }
  if ($strDataEmployee == "") {
    return false;
  }
  $strID = getEmployeeInfoByCode($db, $strDataEmployee, "id");
  $strID = $strID['id'];
  // cari info apakah ada shift atau gak
  $arrShift = [];
  $intShiftType = 0;
  $arrShift = getShiftSchedule($db, $strDataDateFrom, $strDataDateThru, $strID);
  // cek data ke-TIDAKHADIRAN-- (absent, cuti, training, trip)
  $arrOut = getOutOfficeInfo($db, $strDataDateFrom, $strDataDateThru); //-activity.php
  $strSQL = "";
  // ---------------- !!!!!!!!! -----------------------------------
  for ($i = 1; $i <= $intTotal; $i++) {
    $strDataDate = (isset($_REQUEST['detailID' . $i])) ? $_REQUEST['detailID' . $i] : "";
    if ($strDataDate != "") {
      // cek jenis hari
      $intWDay = getWDay($strDataDate);
      $bolHoliday = isHoliday($strDataDate);
      $intTipe = ($intWDay == 5) ? 1 : 0; // jumat atau bukan
      $strDataDate = pgDateFormat($strDataDate, "Y-m-d"); // format distandardkan
      $strAttendanceStart = (isset($_REQUEST['detailAttendanceStart' . $i])) ? $_REQUEST['detailAttendanceStart' . $i] : "";
      $strAttendanceFinish = (isset($_REQUEST['detailAttendanceFinish' . $i])) ? $_REQUEST['detailAttendanceFinish' . $i] : "";
      $strNormalStart = (isset($_REQUEST['detailNormalStart' . $i])) ? $_REQUEST['detailNormalStart' . $i] : "";
      $strNormalFinish = (isset($_REQUEST['detailNormalFinish' . $i])) ? $_REQUEST['detailNormalFinish' . $i] : "";
      $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
      $strHoliday = (isset($_REQUEST['detailOff' . $i])) ? 't' : 'f';
      $intHoliday = (isset($_REQUEST['detailOff' . $i])) ? 1 : 0;
      $bolNotLate = (isset($_REQUEST['detailNotLate' . $i])) ? 't' : 'f';
      $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
      $strShift = (isset($_REQUEST['detailShift' . $i])) ? $_REQUEST['detailShift' . $i] : "";
      $strAttendanceID = (isset($_REQUEST['detailAttendanceID' . $i])) ? $_REQUEST['detailAttendanceID' . $i] : "";
      $strCodeShiftType = (isset($arrShift[$strDataDate][$strID]['shift_code'])) ? $arrShift[$strDataDate][$strID]['shift_code'] : "";
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
      $varTemp = "";
      if ($strNormalStart == "24:00" && (int)substr($strNormalStart, 0, 2) > (int)substr(
              $strAttendanceStart,
              0,
              2
          ) && (int)substr($strAttendanceStart, 0, 2) < (int)substr($strNormalFinish, 0, 2)
      ) {
        $strNormalStart = "00:00";
        $varTemp = "true";
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
      $strAttendanceStart = ($strAttendanceStart == "") ? "NULL" : "'$strAttendanceStart'";
      $strAttendanceFinish = ($strAttendanceFinish == "") ? "NULL" : "'$strAttendanceFinish'";
      $strNormalStart = ($strNormalStart == "") ? $strAttendanceStart : "'$strNormalStart'";
      $strNormalFinish = ($strNormalFinish == "") ? $strAttendanceFinish : "'$strNormalFinish'";
      if ($strAttendanceStart == "NULL" && $strAttendanceFinish == "NULL") { // hapus data
        $strSQL1 = "DELETE FROM hrd_attendance ";
        $strSQL1 .= "WHERE id_employee = '$strID' AND attendance_date = '$strDataDate' ";
        $resExec = $db->execute($strSQL1);
      } else {
        // ---- simpan data
        $intShiftType = 0;
        if (isset($arrShift[$strDataDate][$strID])) {
          $intShiftType = ($arrShift[$strDataDate][$strID]['start_time'] < $arrShift[$strDataDate][$strID]['finish_time']) ? 1 : 2;
        }
        // cek status isAbsence
        if (isset($arrOut[$strDataDate][$strID])) {
          $strIsAbsence = ($arrOut[$strDataDate][$strID]['type'] == OUTOFFICE_ABSENT || $arrOut[$strDataDate][$strID]['type'] == OUTOFFICE_LEAVE) ? 't' : 'f';
        } else {
          $strIsAbsence = "f";
        }
        if ($strAttendanceID == "") { // data baru
          $strSQL .= "DELETE FROM hrd_attendance ";
          $strSQL .= "WHERE id_employee = '$strID' AND attendance_date = '$strDataDate'; "; // hapus dulu, biar gak ada duplikasi data untuk tgl yg sama
          $strSQL .= "INSERT INTO hrd_attendance ";
          $strSQL .= "(created, created_by, modified_by, id_employee, attendance_date, ";
          $strSQL .= "attendance_start, attendance_finish, normal_start, normal_finish, ";
          //$strSQL .= "change_start, change_finish, ";
          $strSQL .= "holiday, not_late, note, total_duration, code_shift_type, ";
          $strSQL .= "early_overtime, late_duration, early_duration, ";
          $strSQL .= "l1, l2, l3, l4, overtime, shift_type, status, is_absence) ";
          $strSQL .= "VALUES(now(), '" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
          $strSQL .= "'$strID', '$strDataDate', $strAttendanceStart, $strAttendanceFinish, ";
          if ($varTemp == "true") {
            $strNormalStart = "'24:00'";
          }
          $strSQL .= "$strNormalStart, $strNormalFinish, ";
          //$strSQL .= "$strAttendanceStart, $strAttendanceFinish, ";
          $strSQL .= "'$intHoliday', '$bolNotLate', '$strNote', '$intTotalHour', '$strCodeShiftType', ";
          $strSQL .= "'0', '" . $arrLembur['late'] . "', '" . $arrLembur['early'] . "', ";
          $strSQL .= "'0', '0', '0', '0', '0', '$intShiftType', ";
          $strSQL .= "$intStatus, '$strIsAbsence'); ";
        } else { // data lama
          $strSQL .= "UPDATE hrd_attendance SET created=now(), ";
          $strSQL .= "modified_by = '" . $_SESSION['sessionUserID'] . "', ";
          $strSQL .= "id_employee = '$strID', attendance_date = '$strDataDate', ";
          $strSQL .= "attendance_start = $strAttendanceStart, attendance_finish = $strAttendanceFinish, ";
          if ($varTemp == "true") {
            $strNormalStart = "'24:00'";
          }
          $strSQL .= "normal_start = $strNormalStart, normal_finish = $strNormalFinish, ";
          $strSQL .= "holiday = '$intHoliday', not_late = '$bolNotLate', ";
          $strSQL .= "note = '$strNote', total_duration = '$intTotalHour', ";
          $strSQL .= "early_overtime = '" . $arrLembur['morning'] . "', ";
          $strSQL .= "late_duration = '" . $arrLembur['late'] . "', ";
          $strSQL .= "early_duration = '" . $arrLembur['early'] . "', ";
          $strSQL .= "shift_type = '$intShiftType',  is_absence = '$strIsAbsence',";
          $strSQL .= "status = $intStatus, data_source = 'modified', ";
          $strSQL .= "code_shift_type = '$strCodeShiftType' WHERE id = '$strAttendanceID'; ";
        }
        // hapus data absensi yagn digenerate system
        deleteSystemGeneratedAbsence($db, $strID, $strDataDate);
      }
    }//if
  }//for
  $resExec = $db->execute($strSQL);
  //adjusting attendance and overtime plan ==> overtime_func.php
  syncOvertimeApplication($db, $strDataDateFrom, $strDataDateThru, $strID);
  //checkInvalidAttendance($db, $strDataDateFrom, $strDataDateThru, $strKriteria);
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
  if (($strDefaultFinishFriday = substr(getSetting("friday_finish_time"), 0, 5)) == "") {
    $strDefaultFinishFriday = "18:30";
  }
  $strTempDate = date("Y-m-d");
  $arrDt = explode("-", $strTempDate);
  $strDefaultFrom = $arrDt[0] . "-" . $arrDt[1] . "-" . "01";
  $strDefaultThru = $arrDt[0] . "-" . $arrDt[1] . "-" . lastday($arrDt[1], $arrDt[0]);
  $strDataDateFrom = (isset($_SESSION['sessionFilterDateFrom'])) ? $_SESSION['sessionFilterDateFrom'] : date("Y-m-d");
  $strDataDateThru = (isset($_SESSION['sessionFilterDateThru'])) ? $_SESSION['sessionFilterDateThru'] : date("Y-m-d");
  $strDataEmployee = (isset($_SESSION['sessionFilterEmployee'])) ? $_SESSION['sessionFilterEmployee'] : "";
  // ------ AMBIL DATA KRITERIA -------------------------
  $strDataView = (isset($_REQUEST['dataView'])) ? $_REQUEST['dataView'] : "";
  $intCurrPage = (isset($_REQUEST['dataPage'])) ? $_REQUEST['dataPage'] : 1;
  if (isset($_REQUEST['dataDateFrom'])) {
    $strDataDateFrom = $_REQUEST['dataDateFrom'];
  }
  if (isset($_REQUEST['dataDateThru'])) {
    $strDataDateThru = $_REQUEST['dataDateThru'];
  }
  if (isset($_REQUEST['dataEmployee'])) {
    $strDataEmployee = $_REQUEST['dataEmployee'];
  }
  if (!is_numeric($intCurrPage)) {
    $intCurrPage = 1;
  }
  $_SESSION['sessionFilterDateFrom'] = $strDataDateFrom;
  $_SESSION['sessionFilterDateThru'] = $strDataDateThru;
  $_SESSION['sessionFilterEmployee'] = $strDataEmployee;
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
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
    if (validStandardDate($strDataDateFrom) && validStandardDate(
            $strDataDateThru
        ) && ($strDataEmployee != "") && isset($_REQUEST['btnShow'])
    ) {
      // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
      $strDataDetail = getData(
          $db,
          $strDataDateFrom,
          $strDataDateThru,
          $strDataEmployee,
          $intTotalData,
          $strKriteria,
          $strDataView,
          $intCurrPage
      );
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
  $strInputDateFrom = "<input type=text name=dataDateFrom id=dataDateFrom size=15 maxlength=10 value=\"$strDataDateFrom\">";
  $strInputDateThru = "<input type=text name=dataDateThru id=dataDateThru size=15 maxlength=10 value=\"$strDataDateThru\">";
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=20 maxlength=30 value=\"$strDataEmployee\" style=\"width:$intDefaultWidthPx\">";
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
  //$strHari = strtoupper(getDayName($strDataDate));
  if ($strDataEmployee != "") {
    $rowDb = getEmployeeInfoByCode(
        $db,
        $strDataEmployee,
        "employee_id, employee_name, position_code, division_code, department_code, section_code, sub_section_code"
    );
    $strInfo = "<br>" . $rowDb['employee_id'] . " - " . $rowDb['employee_name'] . "<br>";
    $strInfo .= $rowDb['position_code'] . "<br>";
    $strInfo .= $rowDb['division_code'] . " - " . $rowDb['department_code'] . " - " . $rowDb['section_code'] . " - " . $rowDb['sub_section_code'];
  } else {
    $strInfo = "";
  }
  $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDataDateFrom\">";
  $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDataDateThru\">";
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
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>