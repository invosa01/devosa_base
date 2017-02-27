<?php
/*
  Fungsi-fungsi untuk calendar
*/
//fungsi untuk membuat kalender
// $db = class database
// $strMonth, $stryear = bulan dan tahun yang diinginkan
// $bolPrint = apakah di print atau tidak
function getMonthlyCalendar($db, $strMonth = "", $strYear = "", $intDefaultHeight = 60, $bolPrint = false)
{
  global $words;
  global $_SESSION;
  // ---- INISIALISASI PARAMETER ---
  //     for($i = 1; $i < 32; $i++) {
  //       $arrCalendar[$i]["holiday"] = false;
  //       $arrCalendar[$i]["note"] = "";
  //       $arrCalendar[$i]["leave"] = false;
  //       $arrCalendar[$i]["type"] = "";
  //       $arrCalendar[$i]["status"] = "";
  //     }
  $dtTempNow = getDate();
  $strHasil = "";
  if ($strMonth == "") {
    $strMonth = (int)$dtTempNow['mon'];
  }
  if ($strYear == "") {
    $strYear = (int)$dtTempNow['year'];
  }
  //$day = $dtSelection["mday"];
  $month = $strMonth;
  $year = $strYear;
  $dtThisMonth = getDate(mktime(0, 0, 0, $month, 1, $year));
  $dtNextMonth = getDate(mktime(0, 0, 0, $month + 1, 1, $year));
  $intFirstWeekDay = $dtThisMonth["wday"];
  $intDaysThisMonth = round(($dtNextMonth[0] - $dtThisMonth[0]) / (60 * 60 * 24));
  //- ------ CARI DATA HARI LIBUR DI DATABASE ---------------------
  // cari tahu apakah hari sabtu dinyatakan libur
  $bolSabtuLibur = (getSetting("saturday") == "t");
  $bolMingguLibur = true;
  //cari data libur dan keterangan
  $strSQL = "SELECT *,EXTRACT(day FROM holiday) AS tgl ";
  $strSQL .= "FROM hrd_calendar ";
  $strSQL .= "WHERE EXTRACT(month FROM holiday) = '$month' ";
  $strSQL .= "AND EXTRACT(year FROM holiday) = '$year' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrCalendar[$rowDb['tgl']][] = [
        "note" => $rowDb['note'],
        "id" => $rowDb['id'],
        "holiday" => ($rowDb['status'] == 't'),
        "leave" => ($rowDb['leave'] == 't'),
        "type" => $rowDb['category'],
        "status" => $rowDb['status']
    ];
  }
  //-- Mulai membuat kalender --//
  //mengisi tgl yang kosong sebelum awal bulang
  $strHasil .= "<table class=\"table table-striped\" cellspacing=0 cellpadding=0 border=1 width=100%>\n";
  // buat headernya
  $strHasil .= "<thead><tr valign=middle height=20>\n";
  for ($i = 0; $i < 7; $i++) {
    $strHasil .= "  <th nowrap align=center  class='tableHeader' width=14%>" . getNamaHariSingkat($i) . "&nbsp;</th>\n";
  }
  $strHasil .= "</tr></thead>\n";
  $strHasil .= "<tbody>";
  $strHasil .= "<tr valign=top height=$intDefaultHeight>\n";
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
    if ($intWeekDay == 0) {
      //hari minggu
      $strHasil .= "</tr><tr valign=top height=$intDefaultHeight>";
      //$strClass = "class='holiday'";
      if (isset($arrCalendar[$intDayCounter])) {
        while (list($key, $val) = each($arrCalendar[$intDayCounter])) {
          $row = &$arrCalendar[$intDayCounter][$key];
          $row["holiday"] = $bolMingguLibur;
          $strClass = ($bolMingguLibur) ? "class='bgHoliday'" : "";
        }
      }
    } else if ($intWeekDay == 6) { //hari sabtu
      if (isset($arrCalendar[$intDayCounter])) {
        while (list($key, $val) = each($arrCalendar[$intDayCounter])) {
          $row = &$arrCalendar[$intDayCounter][$key];
          $row["holiday"] = $bolSabtuLibur;
          $strClass = ($bolSabtuLibur) ? "class='bgHoliday'" : "";
        }
      }
      //$arrCalendar[$intDayCounter]["holiday"] = $bolSabtuLibur;
    }
    $str1stClass = "";
    if (isset($arrCalendar[$intDayCounter])) {
      if ($arrCalendar[$intDayCounter][0]['leave'] == "f") {
        $str1stClass = "class='bgLeaveHoliday'";
      } else {
        switch ($arrCalendar[$intDayCounter][0]['type']) {
          case "0" :
            $str1stClass = "class='bgNationalHoliday'";
            break;
          case "1" :
            $str1stClass = "class='bgCompanyHoliday'";
            break;
          case "2" :
            $str1stClass = "class='bgSpecialHoliday'";
            break;
          default:
            $str1stClass = "";
            break;
        }
      }
    }
    $strRemarks = "";
    if (isset($arrCalendar[$intDayCounter])) {
      if (count($arrCalendar[$intDayCounter]) > 0) {
        $strRemarks = "<table width=\"100%\" height=\"100%\" border=0 cellpadding=2 cellspacing=0>";
        foreach ($arrCalendar[$intDayCounter] as $cal) {
          if ($arrCalendar[$intDayCounter][0]['leave'] == "f") {
            $str1stClass = "class='bgLeaveHoliday'";
          } else {
            switch ($cal['type']) {
              case "0" :
                $strClass = "class='bgNationalHoliday'";
                break;
              case "1" :
                $strClass = "class='bgCompanyHoliday'";
                break;
              case "2" :
                $strClass = "class='bgSpecialHoliday'";
                break;
              default:
                $strClass = "";
                break;
            }
          }
          $strRemarks .= "<tr><td $strClass align=center valign=middle>";
          if (!$bolPrint && $_SESSION['sessionUserRole'] != ROLE_EMPLOYEE) {
            $strRemarks .= "<a class=nounderline href=\"javascript:editEvent(" . $cal['id'] . ", " . $intDayCounter . ");\">" . strtoupper(
                    $cal["note"]
                ) . "</a>";
          } else {
            $strRemarks .= strtoupper($cal["note"]);
          }
          if ($cal["note"] != "" && !$bolPrint && $_SESSION['sessionUserRole'] != ROLE_EMPLOYEE) {
            $strRemarks .= " &nbsp;<br>[<a class=nounderline href=\"javascript:deleteEvent(" . $cal['id'] . ", $intDayCounter)\">" . getWords(
                    'delete'
                ) . "</a>]";
          }
          ($cal["leave"]) ? $strLeave = 't' : $strLeave = 'f';
          $strRemarks .= "<input type=hidden id='dataLeave" . $cal["id"] . "' name='dataLeave" . $cal["id"] . "' value=\"$strLeave\">\n";
          $strRemarks .= "      <input type=hidden id='dataCategory" . $cal["id"] . "' name='dataCategory" . $cal["id"] . "' value=\"" . $cal["type"] . "\">\n";
          $strRemarks .= "      <input type=hidden id='dataNote" . $cal["id"] . "' name='dataNote" . $cal["id"] . "' value=\"" . $cal["note"] . "\">\n";
          $strRemarks .= "      <input type=hidden id='dataStatus" . $cal["id"] . "' name='dataStatus" . $cal["id"] . "' value=\"" . $cal["status"] . "\">\n";
          $strRemarks .= "</td></tr>";
        }
        $strRemarks .= "</table>";
      }
    }
    //       if ($strRemarks != "" && !$bolPrint && $_SESSION['sessionUserRole'] != ROLE_EMPLOYEE) {
    //         $strRemarks = "<table width=\"100%\" height=\"100%\" border=0 cellpadding=2 cellspacing=0>";
    //         $strRemarks = "<a class=nounderline href=\"javascript:editEvent(".$arrCalendar[$intDayCounter]["id"].", $intDayCounter);\">". $strRemarks ."</a>";
    //         $strRemarks .= " &nbsp;<br>[<a href=\"javascript:deleteEvent(".$arrCalendar[$intDayCounter]["id"].")\">" .$words['delete']. "</a>]</td>";
    //
    //
    //       ($arrCalendar[$intDayCounter]["leave"]) ? $strLeave = 't' : $strLeave = 'f';
    //
    //       $strRemarks  .= "<input type=hidden id='dataLeave".$arrCalendar[$intDayCounter]["id"]."' name='dataLeave".$arrCalendar[$intDayCounter]["id"]."' value=\"$strLeave\">\n";
    //       $strRemarks .= "      <input type=hidden id='dataCategory".$arrCalendar[$intDayCounter]["id"]."' name='dataCategory".$arrCalendar[$intDayCounter]["id"]."' value=\"" .$arrCalendar[$intDayCounter]["type"]. "\">\n";
    //       $strRemarks .= "      <input type=hidden id='dataNote".$arrCalendar[$intDayCounter]["id"]."' name='dataNote".$arrCalendar[$intDayCounter]["id"]."' value=\"" .$arrCalendar[$intDayCounter]["note"]. "\">\n";
    //       $strRemarks .= "      <input type=hidden id='dataStatus".$arrCalendar[$intDayCounter]["id"]."' name='dataStatus".$arrCalendar[$intDayCounter]["id"]."' value=\"" .$arrCalendar[$intDayCounter]["status"]. "\">\n";
    //       }
    $strHasil .= "<td $str1stClass>\n";// .$intDayCounter;
    $strHasil .= "  <table class=\"calendarDate\" cellpadding=0 cellspacing=0 border=0 width=100%>\n";
    $strHasil .= "    <tr valign=top>";
    $strHasil .= "      <td style=\"padding: 2px\" align=left><strong>$intDayCounter</strong></td>\n";
    $colspan = "";
    if (!$bolPrint && $_SESSION['sessionUserRole'] != ROLE_EMPLOYEE) {
      $colspan = "colspan=2";
      $strHasil .= "    <td style=\"padding: 2px\" align=right width=20><a class=nounderline href=\"javascript:editEvent(0, " . $intDayCounter . ");\">[+]</a></td>\n";
    }
    $strHasil .= "    </tr>\n";
    $strHasil .= "    <tr valign=top>\n";
    $strHasil .= "      <td align=center $colspan>" . $strRemarks . "</td>\n";
    $strHasil .= "    </tr>\n";
    $strHasil .= "  </table>\n";
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
  $strHasil .= "</tbody>";
  $strHasil .= "</table>\n";
  return ($strHasil);
}// getMonthlyCalendar
//fungsi untuk membuat kalender jadwal shift bulanan, per Group
// $db = class database
// $strMonth, $stryear = bulan dan tahun yang diinginkan
// $bolPrint = apakah di print atau tidak
function getMonthlyEmployeeShiftCalendar(
    $db,
    $strMonth = "",
    $strYear = "",
    $strEmployee = "",
    $intDefaultHeight = 60,
    $bolPrint = false
) {
  global $words;
  // ---- INISIALISASI PARAMETER ---
  for ($i = 1; $i < 32; $i++) {
    $arrCalendar[$i]["holiday"] = false; // libur atau bukan
    $arrCalendar[$i]["content"] = ""; // daftar jadwal shift
    $arrCalendar[$i]["note"] = ""; // keterangan libur
    $arrCalendar[$i]["leave"] = false; // libur cuti atau bukan
    $arrCalendar[$i]["type"] = ""; // jenis libur
  }
  $dtTempNow = getDate();
  $strHasil = "";
  if ($strMonth == "") {
    $strMonth = (int)$dtTempNow['mon'];
  }
  if ($strYear == "") {
    $strYear = (int)$dtTempNow['year'];
  }
  //$day = $dtSelection["mday"];
  $month = $strMonth;
  $year = $strYear;
  $dtThisMonth = getDate(mktime(0, 0, 0, $month, 1, $year));
  $dtNextMonth = getDate(mktime(0, 0, 0, $month + 1, 1, $year));
  $intFirstWeekDay = $dtThisMonth["wday"];
  $intDaysThisMonth = round(($dtNextMonth[0] - $dtThisMonth[0]) / (60 * 60 * 24));
  //- ------ CARI DATA HARI LIBUR DI DATABASE ---------------------
  // cari tahu apakah hari sabtu dinyatakan libur
  $bolSabtuLibur = (getSetting("saturday") == "t");
  //cari data libur dan keterangan
  $strSQL = "SELECT *,EXTRACT(day FROM holiday) AS tgl ";
  $strSQL .= "FROM hrd_calendar ";
  $strSQL .= "WHERE EXTRACT(month FROM holiday) = '$month' ";
  $strSQL .= "AND EXTRACT(year FROM holiday) = '$year' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrCalendar[$rowDb['tgl']]["note"] = $rowDb['note'];
    $arrCalendar[$rowDb['tgl']]["holiday"] = ($rowDb['status'] == 't');
    //$arrCalendar[$rowDb['tgl']]["id"] = ($rowDb['id'] == 't');
    $arrCalendar[$rowDb['tgl']]["leave"] = ($rowDb['leave'] == 't');
    $arrCalendar[$rowDb['tgl']]["type"] = $rowDb['category'];
  }
  // --- CARI DAFTAR JADWAL SHIFT -----
  // cari data ID pegawai
  $stremployee_id = "";
  if ($strEmployee != "") {
    $strSQL = "SELECT id FROM hrd_employee WHERE employee_id = '$strEmployee' AND flag = 0 ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $stremployee_id = $rowDb['id'];
    }
  }
  if ($stremployee_id != "") {
    $j = 0;
    $strSQL = "SELECT t1.*,EXTRACT(day FROM shift_date) AS tgl ";
    $strSQL .= "FROM hrd_shift_schedule_employee AS t1 ";
    $strSQL .= "WHERE EXTRACT(month FROM shift_date) = '$month' ";
    $strSQL .= "AND EXTRACT(year FROM shift_date) = '$year' ";
    $strSQL .= "AND id_employee = '$stremployee_id' ";
    $strSQL .= "ORDER BY \"startTime\"";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $j++;
      $strHidden = "<input type=hidden name='dataShiftCode" . $rowDb['tgl'] . "' value=\"" . $rowDb['shiftCode'] . "\" disabled>\n";
      $strHidden .= "<input type=hidden name='dataShiftID" . $rowDb['tgl'] . "' value=\"" . $rowDb['id'] . "\" disabled>\n";
      $strHidden .= "<input type=hidden name='dataStart" . $rowDb['tgl'] . "' value=\"" . $rowDb['startTime'] . "\" disabled>\n";
      $strHidden .= "<input type=hidden name='dataFinish" . $rowDb['tgl'] . "' value=\"" . $rowDb['finishTime'] . "\" disabled>\n";
      $strHidden .= "<input type=hidden name='dataNote" . $rowDb['tgl'] . "' value=\"" . $rowDb['note'] . "\" disabled>\n";
      $arrCalendar[$rowDb['tgl']]["content"] .= "<tr valign=top>\n";
      $arrCalendar[$rowDb['tgl']]["content"] .= "  <td>* </td>\n";
      $arrCalendar[$rowDb['tgl']]["content"] .= "  <td title=\"" . $rowDb['note'] . "\">" . $rowDb['shiftCode'] . "</td>\n";
      $arrCalendar[$rowDb['tgl']]["content"] .= "  <td>$strHidden<a href =  \"javascript:deleteData(" . $rowDb['tgl'] . ")\">[x]</a></td>\n";
      $arrCalendar[$rowDb['tgl']]["content"] .= "  <td><a href=\"javascript:editData(" . $rowDb['tgl'] . ")\">" . $words['edit'] . "</a></td>\n";
      $arrCalendar[$rowDb['tgl']]["content"] .= "</tr>\n";
    }
  }
  // -- merapikan content ---
  /*
  $strTableOpen = "<table cellpadding=0 cellspacing=0 border=0>\n";
  $strTableClose = "</table>\n";
  for($i = 1; $i < 32; $i++) {
    if ($arrCalendar[$i]["content"] == "") {
      $arrCalendar[$i]['content'] = "&nbsp;";
    } else {
      $arrCalendar[$i]['content'] = $strTableOpen . $arrCalendar[$i]['content'] .$strTableClose;
    }
  }
  */
  //-- Mulai membuat kalender --//
  //mengisi tgl yang kosong sebelum awal bulang
  $strHasil .= "<table cellspacing=0 cellpadding=0 border=1 width=100%>\n";
  // buat headernya
  $strHasil .= "<tr valign=middle height=20>\n";
  for ($i = 0; $i < 7; $i++) {
    $strHasil .= "  <td nowrap align=center  class='tableHeader' width=14%>" . getNamaHariSingkat(
            $i + 1
        ) . "&nbsp;</td>\n";
  }
  $strHasil .= "</tr>\n";
  $strHasil .= "<tr valign=top height=$intDefaultHeight>\n";
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
      $strHasil .= "</tr><tr valign=top height=$intDefaultHeight>";
      //$strClass = "class='holiday'";
      $arrCalendar[$intDayCounter]["holiday"] = true;
    } else if ($intWeekDay == 6) { //hari sabtu
      $arrCalendar[$intDayCounter]["holiday"] = $bolSabtuLibur;
    }
    if ($arrCalendar[$intDayCounter]["holiday"]) {
      if ($arrCalendar[$intDayCounter]['leave']) {
        $strClass = "class='bgLeaveHoliday'";
      } else {
        if ($arrCalendar[$intDayCounter]['type'] == 0) {
          $strClass = "class='bgNationalHoliday'";
        } else if ($arrCalendar[$intDayCounter]['type'] == 1) {
          $strClass = "class='bgCompanyHoliday'";
        } else if ($arrCalendar[$intDayCounter]['type'] == 2) {
          $strClass = "class='bgSpecialHoliday'";
        } else {
          $strClass = "class='bgHoliday'";
        }
      }
    } else {
      //$strClass = "";
    }
    $strRemarks = $arrCalendar[$intDayCounter]["note"];
    if ($strRemarks != "" && !$bolPrint) {
      $strRemarks .= " &nbsp; </td>";
    }
    ($arrCalendar[$intDayCounter]["leave"]) ? $strLeave = 't' : $strLeave = 'f';
    /*
    $strHidden  = "<input type=hidden name='dataLeave$intDayCounter' value=\"$strLeave\">\n";
    $strHidden .= "      <input type=hidden name='dataCategory$intDayCounter' value=\"" .$arrCalendar[$intDayCounter]["type"]. "\">\n";
    $strHidden .= "      <input type=hidden name='dataNote$intDayCounter' value=\"" .$arrCalendar[$intDayCounter]["note"]. "\">\n";
    */
    $strHasil .= "<td $strClass>\n";// .$intDayCounter;
    $strHasil .= "<table cellpadding=2 cellspacing=0 border=0 width=100%>\n";
    $strHasil .= "  <tr valign=top >";
    //$strHasil .= "    <td $strClass><strong>$intDayCounter</strong>$strHidden</td>\n";
    $strHasil .= "    <td colspan=2 $strClass><strong>$intDayCounter</strong></td>\n";
    $strHasil .= "    <td colspan=2 align=right $strClass><a href=\"javascript:addData($intDayCounter);\">[" . $words['add'] . "]</a></td>\n";
    $strHasil .= "  </tr>\n";
    $strHasil .= $arrCalendar[$intDayCounter]['content'];
    $strHasil .= "  <tr valign=top align=center>";
    $strHasil .= "    <td>$strRemarks";
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
  $strHasil .= "</table>\n";
  return ($strHasil);
}// getMonthlyEmployeeShiftCalendar
//fungsi untuk membuat kalender jadwal shift bulanan, per Group
// $db = class database
// $strMonth, $stryear = bulan dan tahun yang diinginkan
// $bolPrint = apakah di print atau tidak
function getMonthlyGroupShiftCalendar($db, $strMonth = "", $strYear = "", $intDefaultHeight = 60, $bolPrint = false)
{
  global $words;
  // ---- INISIALISASI PARAMETER ---
  for ($i = 1; $i < 32; $i++) {
    $arrCalendar[$i]["holiday"] = false; // libur atau bukan
    $arrCalendar[$i]["content"] = ""; // daftar jadwal shift
    $arrCalendar[$i]["note"] = ""; // keterangan libur
    $arrCalendar[$i]["leave"] = false; // libur cuti atau bukan
    $arrCalendar[$i]["type"] = ""; // jenis libur
  }
  $dtTempNow = getDate();
  $strHasil = "";
  if ($strMonth == "") {
    $strMonth = (int)$dtTempNow['mon'];
  }
  if ($strYear == "") {
    $strYear = (int)$dtTempNow['year'];
  }
  //$day = $dtSelection["mday"];
  $month = $strMonth;
  $year = $strYear;
  $dtThisMonth = getDate(mktime(0, 0, 0, $month, 1, $year));
  $dtNextMonth = getDate(mktime(0, 0, 0, $month + 1, 1, $year));
  $intFirstWeekDay = $dtThisMonth["wday"];
  $intDaysThisMonth = round(($dtNextMonth[0] - $dtThisMonth[0]) / (60 * 60 * 24));
  //- ------ CARI DATA HARI LIBUR DI DATABASE ---------------------
  // cari tahu apakah hari sabtu dinyatakan libur
  $bolSabtuLibur = (getSetting("saturday") == "t");
  //cari data libur dan keterangan
  $strSQL = "SELECT *,EXTRACT(day FROM holiday) AS tgl ";
  $strSQL .= "FROM hrd_calendar ";
  $strSQL .= "WHERE EXTRACT(month FROM holiday) = '$month' ";
  $strSQL .= "AND EXTRACT(year FROM holiday) = '$year' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrCalendar[$rowDb['tgl']]["note"] = $rowDb['note'];
    $arrCalendar[$rowDb['tgl']]["holiday"] = ($rowDb['status'] == 't');
    //$arrCalendar[$rowDb['tgl']]["id"] = ($rowDb['id'] == 't');
    $arrCalendar[$rowDb['tgl']]["leave"] = ($rowDb['leave'] == 't');
    $arrCalendar[$rowDb['tgl']]["type"] = $rowDb['category'];
  }
  // --- CARI DAFTAR JADWAL SHIFT -----
  $j = 0;
  $strSQL = "SELECT t1.*,EXTRACT(day FROM shift_date) AS tgl, t2.\"groupName\" ";
  $strSQL .= "FROM hrd_shift_schedule_group AS t1 ";
  $strSQL .= "LEFT JOIN hrd_shift_group AS t2 ON t1.\"idGroup\" = t2.id ";
  $strSQL .= "WHERE EXTRACT(month FROM shift_date) = '$month' ";
  $strSQL .= "AND EXTRACT(year FROM shift_date) = '$year' ";
  $strSQL .= "ORDER BY \"startTime\"";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $j++;
    $strHidden = "<input type=hidden name='dataShiftCode$j' value=\"" . $rowDb['shiftCode'] . "\" disabled>\n";
    $strHidden .= "<input type=hidden name='dataShiftID$j' value=\"" . $rowDb['id'] . "\" disabled>\n";
    $strHidden .= "<input type=hidden name='dataStart$j' value=\"" . $rowDb['startTime'] . "\" disabled>\n";
    $strHidden .= "<input type=hidden name='dataFinish$j' value=\"" . $rowDb['finishTime'] . "\" disabled>\n";
    $strHidden .= "<input type=hidden name='dataNote$j' value=\"" . $rowDb['note'] . "\" disabled>\n";
    $strHidden .= "<input type=hidden name='dataGroup$j' value=\"" . $rowDb['idGroup'] . "\" disabled>\n";
    $arrCalendar[$rowDb['tgl']]["content"] .= "<tr valign=top>\n";
    $arrCalendar[$rowDb['tgl']]["content"] .= "  <td>* </td>\n";
    $arrCalendar[$rowDb['tgl']]["content"] .= "  <td>" . $rowDb['shiftCode'] . "</td>\n";
    $arrCalendar[$rowDb['tgl']]["content"] .= "  <td>" . $rowDb['groupName'] . "</td>\n";
    $arrCalendar[$rowDb['tgl']]["content"] .= "  <td>$strHidden<a href =  \"javascript:deleteData(" . $rowDb['id'] . ")\">[x]</a></td>\n";
    //$arrCalendar[$rowDb['tgl']]["content"] .= "  <td><a href=\"javascript:editData($intDayCounter)\">" .$words['edit']. "</a></td>\n";
    $arrCalendar[$rowDb['tgl']]["content"] .= "</tr>\n";
  }
  // -- merapikan content ---
  /*
  $strTableOpen = "<table cellpadding=0 cellspacing=0 border=0>\n";
  $strTableClose = "</table>\n";
  for($i = 1; $i < 32; $i++) {
    if ($arrCalendar[$i]["content"] == "") {
      $arrCalendar[$i]['content'] = "&nbsp;";
    } else {
      $arrCalendar[$i]['content'] = $strTableOpen . $arrCalendar[$i]['content'] .$strTableClose;
    }
  }
  */
  //-- Mulai membuat kalender --//
  //mengisi tgl yang kosong sebelum awal bulang
  $strHasil .= "<table cellspacing=0 cellpadding=0 border=1 width=100%>\n";
  // buat headernya
  $strHasil .= "<tr valign=middle height=20>\n";
  for ($i = 0; $i < 7; $i++) {
    $strHasil .= "  <td nowrap align=center  class='tableHeader' width=14%>" . getNamaHariSingkat(
            $i + 1
        ) . "&nbsp;</td>\n";
  }
  $strHasil .= "</tr>\n";
  $strHasil .= "<tr valign=top height=$intDefaultHeight>\n";
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
      $strHasil .= "</tr><tr valign=top height=$intDefaultHeight>";
      //$strClass = "class='holiday'";
      $arrCalendar[$intDayCounter]["holiday"] = true;
    } else if ($intWeekDay == 6) { //hari sabtu
      $arrCalendar[$intDayCounter]["holiday"] = $bolSabtuLibur;
    }
    if ($arrCalendar[$intDayCounter]["holiday"]) {
      if ($arrCalendar[$intDayCounter]['leave']) {
        $strClass = "class='bgLeaveHoliday'";
      } else {
        if ($arrCalendar[$intDayCounter]['type'] == 0) {
          $strClass = "class='bgNationalHoliday'";
        } else if ($arrCalendar[$intDayCounter]['type'] == 1) {
          $strClass = "class='bgCompanyHoliday'";
        } else if ($arrCalendar[$intDayCounter]['type'] == 2) {
          $strClass = "class='bgSpecialHoliday'";
        } else {
          $strClass = "class='bgHoliday'";
        }
      }
    } else {
      //$strClass = "";
    }
    $strRemarks = $arrCalendar[$intDayCounter]["note"];
    if ($strRemarks != "" && !$bolPrint) {
      $strRemarks .= " &nbsp; </td>";
    }
    ($arrCalendar[$intDayCounter]["leave"]) ? $strLeave = 't' : $strLeave = 'f';
    /*
    $strHidden  = "<input type=hidden name='dataLeave$intDayCounter' value=\"$strLeave\">\n";
    $strHidden .= "      <input type=hidden name='dataCategory$intDayCounter' value=\"" .$arrCalendar[$intDayCounter]["type"]. "\">\n";
    $strHidden .= "      <input type=hidden name='dataNote$intDayCounter' value=\"" .$arrCalendar[$intDayCounter]["note"]. "\">\n";
    */
    $strHasil .= "<td $strClass>\n";// .$intDayCounter;
    $strHasil .= "<table cellpadding=2 cellspacing=0 border=0 width=100%>\n";
    $strHasil .= "  <tr valign=top >";
    //$strHasil .= "    <td $strClass><strong>$intDayCounter</strong>$strHidden</td>\n";
    $strHasil .= "    <td colspan=2 $strClass><strong>$intDayCounter</strong></td>\n";
    $strHasil .= "    <td colspan=2 align=right $strClass><a href=\"javascript:addData($intDayCounter);\">[" . $words['add'] . "]</a></td>\n";
    $strHasil .= "  </tr>\n";
    $strHasil .= $arrCalendar[$intDayCounter]['content'];
    $strHasil .= "  <tr valign=top align=center>";
    $strHasil .= "    <td>$strRemarks";
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
  $strHasil .= "</table>\n";
  return ($strHasil);
}// getMonthlyGroupShiftCalendar
//-- end function calendar
?>