<?php
session_start();
include("global.php");
include("form_object.php");
include("overtime.php");
include(getTemplate("words.inc"));
include("menu.php");
//inisialisasi
$strTitle = "ASKA - HRD - Calendar";
$isNew = true;
$strError = "";
$strRows = "";
//$strError = getPermission("activity_calendar.php");
$bolCanAccess = getUserPermission("activity_calendar.php", $bolCanEdit, $bolCanDelete, $strError);
$tplFile = getTemplate("activityCalendar.html");
(isset($_SESSION['sessionUserName'])) ? $strUserName = $_SESSION['sessionUserName'] : $strUserName = "";
//inisialisasi
// data perbulan disimpan di array
$arrCalendar = [
    1 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    2 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    3 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    4 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    5 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    6 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    7 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    8 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    9 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    10 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    11 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    12 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    13 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    14 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    15 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    16 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    17 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    18 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    19 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    20 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    21 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    22 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    23 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    24 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    25 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    26 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    27 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    28 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    29 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    30 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""],
    31 => ["holiday" => false, "remarks" => "", "type" => "", "leave" => false, "overtime" => ""]
];
//fungsi untuk membuat kalender
function calendar($dtSelection, $bolPrint)
{
  global $arrCalendar;
  global $words;
  global $db;
  if ($dtSelection == null) {
    $dtSelection = getDate(); //default hari ini
  }
  $dtTempNow = getDate();
  $strHasil = "";
  $day = $dtSelection["mday"];
  $month = $dtSelection["mon"];
  $strMonthName = $dtSelection["month"];
  $year = $dtSelection["year"];
  $dtThisMonth = getDate(mktime(0, 0, 0, $month, 1, $year));
  $dtNextMonth = getDate(mktime(0, 0, 0, $month + 1, 1, $year));
  $intFirstWeekDay = $dtThisMonth["wday"];
  $intDaysThisMonth = round(($dtNextMonth[0] - $dtThisMonth[0]) / (60 * 60 * 24));
  //- Ambil Data Hari Libur --//
  //cari tahu apakah hari sabtu dinyatakan libur
  $bolSabtuLibur = (getSetting("holidaysaturday") == "t");
  //cari data libur dan keterangan
  $strSQL = "SELECT *,EXTRACT(day FROM holiday) AS tgl ";
  $strSQL .= "FROM hrd_calendar ";
  $strSQL .= "WHERE EXTRACT(month FROM holiday) = '$month' ";
  $strSQL .= "AND EXTRACT(year FROM holiday) = '$year' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrCalendar[$rowDb['tgl']]["remarks"] = $rowDb['remarks'];
    $arrCalendar[$rowDb['tgl']]["holiday"] = ($rowDb['status'] == 't');
    //$arrCalendar[$rowDb['tgl']]["id"] = ($rowDb['id'] == 't');
    $arrCalendar[$rowDb['tgl']]["leave"] = ($rowDb['isLeave'] == 't');
    $arrCalendar[$rowDb['tgl']]["type"] = $rowDb['holidayType'];
    $arrCalendar[$rowDb['tgl']]["overtime"] = $rowDb['overtime'];
    $arrCalendar[$rowDb['tgl']]["status"] = $rowDb['status'];
  }
  //-- Mulai membuat kalender --//
  //mengisi tgl yang kosong sebelum awal bulang
  $strHasil .= "<tr valign=top height=50>";
  for ($intWeekDay = 0; $intWeekDay < $intFirstWeekDay; $intWeekDay++) {
    if ($intWeekDay == 0) {
      $strHasil .= "<td class='bgHoliday'>&nbsp;</td>";
    } else {
      $strHasil .= "<td>&nbsp;</td>";
    }
  }
  $intWeekDay = $intFirstWeekDay;
  for ($intDayCounter = 1; $intDayCounter <= $intDaysThisMonth; $intDayCounter++) {
    $intWeekDay %= 7;
    $strClass = "";
    //cek apakah hari ini atau bukan
    if ($intDayCounter != 0 && $intDayCounter == $dtTempNow['mday'] && $month == $dtTempNow['mon'] && $dtTempNow['year'] == $year) {
      $strClass = "class='bgToday'";
    }
    if ($intWeekDay == 0) { //hari minggu
      $strHasil .= "</tr><tr valign=top height=50>";
      //$strClass = "class='holiday'";
      $arrCalendar[$intDayCounter]["holiday"] = true;
    } else if ($intWeekDay == 6) { //hari sabtu
      $arrCalendar[$intDayCounter]["holiday"] = $bolSabtuLibur;
    }
    if ($arrCalendar[$intDayCounter]["holiday"]) {
      if ($arrCalendar[$intDayCounter]['status'] == 'f') {
        $strClass = "class = 'bgNotHoliday'";
      } else if ($arrCalendar[$intDayCounter]['leave']) {
        $strClass = "class='bgLeaveHoliday'";
      } else {
        if ($arrCalendar[$intDayCounter]['type'] == 'national') {
          $strClass = "class='bgNationalHoliday'";
        } else if ($arrCalendar[$intDayCounter]['type'] == 'company') {
          $strClass = "class='bgCompanyHoliday'";
        } else if ($arrCalendar[$intDayCounter]['type'] == 'special') {
          $strClass = "class='bgSpecialHoliday'";
        } else {
          $strClass = "class='bgHoliday'";
        }
      }
    } else {
      $strClass = "";
    }
    $strRemarks = $arrCalendar[$intDayCounter]["remarks"];
    ($arrCalendar[$intDayCounter]["leave"]) ? $strLeave = 't' : $strLeave = 'f';
    $strType = $arrCalendar[$intDayCounter]["type"];
    $strOvertime = $arrCalendar[$intDayCounter]["overtime"];
    $strStatus = $arrCalendar[$intDayCounter]["status"];
    $strHasil .= "<td $strClass>\n";// .$intDayCounter;
    $strHasil .= "<table cellpadding=2 cellspacing=0 border=0 width=100%>\n";
    $strHasil .= "  <tr valign=top>";
    $strHasil .= "    <td width=20% valign=top $strClass><strong>$intDayCounter</strong>\n";
    $strHasil .= "      <input type=hidden name='dataLeave$intDayCounter' value=\"$strLeave\">\n";
    $strHasil .= "      <input type=hidden name='dataOvertime$intDayCounter' value=\"$strOvertime\">\n";
    $strHasil .= "      <input type=hidden name='dataType$intDayCounter' value=\"$strType\">\n";
    $strHasil .= "      <input type=hidden name='dataRemarks$intDayCounter' value=\"$strRemarks\"></td>\n";
    $strHasil .= "      <input type=hidden name='dataStatus$intDayCounter' value=\"$strStatus\"></td>\n";
    if (!$bolPrint) {
      $strHasil .= "    <td width=80% valign=top align=right>[<a href='javascript:void(0)' onClick=\"editEvent($intDayCounter);\">" . $words['edit'] . "</a>]";
      $strHasil .= "      &nbsp;[<a href=# onClick='deleteEvent($intDayCounter)';>" . $words['delete'] . "</a>]</td>";
    }
    $strHasil .= "  </tr>\n";
    $strHasil .= "  <tr valign=top align=center>";
    $strHasil .= "    <td valign=top colspan=2>$strRemarks";
    if ($strOvertime != '' && $strRemarks != '') {
      $strHasil .= " [$strOvertime] ";
    }
    $strHasil .= "  </td></tr>\n";
    $strHasil .= "</table>\n";
    $strHasil .= "</td>\n";
    $intWeekDay++;
  }
  //mengisi tgl yang kosong setelah akhir bulan
  for ($intWeekDay; $intWeekDay < 7; $intWeekDay++) {
    if ($intWeekDay == 6 && $bolSabtuLibur) {
      $strHasil .= "<td class='bgHoliday'>&nbsp;</td>";
    } else {
      $strHasil .= "<td>&nbsp;</td>";
    }
  }
  $strHasil .= "</tr>";
  return ($strHasil);
}

//-- end function calendar
// get template source
(isset($_REQUEST['btnPrint']) ? $bolPrint = true : $bolPrint = false);
if ($bolPrint) {
  $tplFile = getTemplate("activityCalendarPrint.html");
} else {
  $tplFile = getTemplate("activityCalendar.html");
}
if ($strError == "") {
  $db = new CdbClass;
  if ($db->connect()) {
    $dtNow = getdate();
    //generate navigasi menu bulan
    $intMonth = $dtNow['mon']; //inisialisasi
    $intYear = $dtNow['year'];
    if (isset($_REQUEST['dataMonth']) && is_numeric($_REQUEST['dataMonth'])) {
      $intMonth = $_REQUEST['dataMonth'];
    }
    if (isset($_REQUEST['dataYear']) && is_numeric($_REQUEST['dataYear'])) {
      $intYear = $_REQUEST['dataYear'];
    }
    //-- simpan data jika perlu --//
    if (isset($_REQUEST['btnSave'])) {
      if ($bolCanEdit) {
        (isset($_REQUEST['dataDay']) && is_numeric(
                $_REQUEST['dataDay']
            )) ? $intDay = $_REQUEST['dataDay'] : $intMonth = $dtNow['mday'];
        (isset($_REQUEST['dataEvent'])) ? $strEvent = $_REQUEST['dataEvent'] : $strEvent = "";
        (isset($_REQUEST['dataHoliday'])) ? $strStatus = $_REQUEST['dataHoliday'] : $strStatus = "t";
        (isset($_REQUEST['dataLeave'])) ? $strLeave = $_REQUEST['dataLeave'] : $strLeave = "t";
        (isset($_REQUEST['dataType'])) ? $strType = $_REQUEST['dataType'] : $strType = "";
        (isset($_REQUEST['dataOvertime'])) ? $strOvertime = $_REQUEST['dataOvertime'] : $strOvertime = "2";
        $strTanggal = "$intYear-$intMonth-$intDay";
        $dtTempTanggal = getdate(mktime('', '', '', $intMonth, $intDay, $intYear));
        $strSQL = "SELECT * FROM hrd_calendar ";
        $strSQL .= "WHERE holiday = '$strTanggal' ";
        $resDb = $db->execute($strSQL);
        if ($db->numrows($resDb) == 0) { //new
          $strSQL = "INSERT INTO hrd_calendar ";
          $strSQL .= "(created,modified_by,remarks,holiday,status,\"isLeave\",\"holidayType\",overtime) ";
          $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "', ";
          $strSQL .= "'$strEvent','$strTanggal','$strStatus','$strLeave','$strType','$strOvertime') ";
        } else { //update
          $strSQL = "UPDATE hrd_calendar ";
          $strSQL .= "SET created=now(),modified_by = '" . $_SESSION['sessionUserID'] . "', ";
          $strSQL .= "remarks = '$strEvent', status = '$strStatus', ";
          $strSQL .= "\"isLeave\" = '$strLeave', \"holidayType\" = '$strType', overtime = '$strOvertime' ";
          $strSQL .= "WHERE holiday = '$strTanggal' ";
        }
        if ($db->execute($strSQL) == false) {
          $strError .= $db->dbError . "\\n";
        }
        //update data kehadiran pegawai jika ada
        $strSQL = "SELECT \"employeeCode\" FROM hrd_attendance WHERE attendance_date = '$strTanggal' ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
          //update data kehadiran dan overtime karyawan
          calculateOvertime($db, $rowDb['employee_code'], $dtTempTanggal);
        }
        // update status tabel kehadiran, apakah hari libur atau bukan
        $strSQL = "UPDATE hrd_attendance SET \"isHoliday\" = '$strStatus' ";
        $strSQL .= "WHERE attendance_date = '$strTanggal' ";
        $resTmp = $db->execute($strSQL);
      } else {
        echo "<script>alert('" . $errorPermission['edit denied'] . "');</script>";
      }
    } else if (isset($_REQUEST['btnDelete'])) { //hapus data event
      if ($bolCanDelete) {
        if (isset($_REQUEST['dataDay']) && $_REQUEST['dataDay'] != "") {
          $intDay = $_REQUEST['dataDay'];
          $strTanggal = "$intYear-$intMonth-$intDay";
          $dtTempTanggal = getdate(mktime('', '', '', $intMonth, $intDay, $intYear));
          $strSQL = "DELETE FROM hrd_calendar ";
          $strSQL .= "WHERE holiday = '$strTanggal' ";
          if ($db->execute($strSQL) == false) {
            $strError .= $db->dbError . "\\n";
          }
          //update data kehadiran pegawai jika ada
          $strSQL = "SELECT \"employeeCode\" FROM hrd_attendance WHERE attendance_date = '$strTanggal' ";
          $resDb = $db->execute($strSQL);
          while ($rowDb = $db->fetchrow($resDb)) {
            //update data kehadiran dan overtime karyawan
            calculateOvertime($db, $rowDb['employee_code'], $dtTempTanggal);
          }
          // update di status libur dari attendance
          if (isHoliday($db, $dtTempTanggal, "")) {
            $bolIsHoliday = 't';
          } else {
            $bolIsHoliday = 'f';
          }
          //
          $strSQL = "UPDATE hrd_attendance SET \"isHoliday\" = '$bolIsHoliday' ";
          $strSQL .= "WHERE attendance_date = '$strTanggal' ";
          $resTmp = $db->execute($strSQL);
        }
      } else {
        echo "<script>alert('" . $errorPermission['delete denied'] . "');</script>";
      }
    }
    $dtCurrent = getdate(mktime('', '', '', $intMonth, 1, $intYear));
    $strCalendar = calendar($dtCurrent, $bolPrint);
    $strMonthMenu = "";
    if ($bolPrint) {
      $strMonthMenu .= getBulan($intMonth) . " " . $intYear;
    } else {
      if ($intMonth == 1) {
        $strMonthMenu .= "<a href=# onClick=\"goMonth('12','" . ($intYear - 1) . "')\">" . getBulan(
                12
            ) . " " . ($intYear - 1) . "</a>";
      } else {
        $strMonthMenu .= "<a href=# onClick=\"goMonth('" . ($intMonth - 1) . "','$intYear')\">" . getBulan(
                $intMonth - 1
            ) . " " . $intYear . "</a>";
      }
      $strMonthMenu .= "&nbsp; | &nbsp;<strong>" . getBulan($intMonth) . " " . $intYear . "</strong>&nbsp; | &nbsp;";
      if ($intMonth == 12) {
        $strMonthMenu .= "<a href=# onClick=\"goMonth('1','" . ($intYear + 1) . "')\">" . getBulan(
                1
            ) . " " . ($intYear + 1) . "</a>";
      } else {
        $strMonthMenu .= "<a href=# onClick=\"goMonth('" . ($intMonth + 1) . "','$intYear')\">" . getBulan(
                $intMonth + 1
            ) . " " . $intYear . "</a>";
      }
    }
    $strMonthYear = "<select name='dataMonth'>\n";
    for ($i = 1; $i <= 12; $i++) {
      if ($i == $intMonth) {
        $strMonthYear .= "<option value=$i selected>" . getBulan($i) . "&nbsp;</option>";
      } else {
        $strMonthYear .= "<option value=$i>" . getBulan($i) . "&nbsp;</option>";
      }
    }
    $strMonthYear .= "</select>\n";
    $intYearInterval = 10;
    $strMonthYear .= "<select name='dataYear'>\n";
    for ($i = $intYearInterval; $i > 0; $i--) { //tahun      sebelumnya
      $intTmpYear = $dtNow['year'] - $i;
      if ($intYear == $intTmpYear) {
        $strMonthYear .= "<option value=$intTmpYear selected>$intTmpYear</option>";
      } else {
        $strMonthYear .= "<option value=$intTmpYear>$intTmpYear</option>";
      }
    }
    for ($i = 0; $i < $intYearInterval; $i++) { //tahun    berikutnya
      $intTmpYear = $dtNow['year'] + $i;
      if ($intTmpYear == $intYear) {
        $strMonthYear .= "<option value=$intTmpYear selected>$intTmpYear</option>";
      } else {
        $strMonthYear .= "<option value=$intTmpYear>$intTmpYear</option>";
      }
    }
    $strMonthYear .= "</select>\n ";
    //$strMonthYear .= "&nbsp;<input type=text name='dataYear' value='$intYear' maxlength=4 size=4> &nbsp;";
    $strHiddenData = "<input type=hidden name='dataMonth' value='$intMonth'>";
    $strHiddenData .= "<input type=hidden name='dataYear' value='$intYear'>";
    $strEventDate = getDateData("data", $dtNow['mday'], $intMonth, $intYear);
    $tplPage = new UltraTemplate();
    $tplPage->load($tplFile);
    $tplPage->tag("tagTitle", $strTitle);
    $tplPage->tag("tagCalendar", $strCalendar);
    $tplPage->tag("tagMonthMenu", $strMonthMenu);
    if (!$bolPrint) {
      $tplPage->tag("tagMonthYear", $strMonthYear);
      $tplPage->tag("tagHiddenData", $strHiddenData);
      $tplPage->tag("tagEventDate", $strEventDate);
    }
    $tplPage->display();
  } else {
    $strError = "<script>alert('" . $db->dbError . "');</script>";
    echo $strError;
  }
} else {
  echo $strError;
}
?>
