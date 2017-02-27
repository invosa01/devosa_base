<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
$dataPrivilege = getDataPrivileges(
    "overtime_edit.php",
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
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintApproved']) || isset($_REQUEST['btnExcel']));
$bolPrintReport = (isset($_REQUEST['btnPrintReport']));
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strButtonList = "";
$strStyle = "";
//----------------------------------------------------------------------
// fungsi buat nentuin apakah standard format atau gak
function cekStandardFormat($strText)
{
  global $_REQUEST;
  if (isset($_REQUEST['btnExcel'])) // untuk tampil di excel
  {
    $strResult = $strText;
  } else {
    $strResult = standardFormat($strText, true) . "&nbsp;";
  }
  return $strResult;
}

//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $ARRAY_LEAVE_TYPE;
  global $ARRAY_OT_STATUS;
  global $arrUserInfo;
  global $_SESSION;
  global $_REQUEST;
  global $bolPrint;
  global $arrUserList;
  $intRows = 0;
  $strResult = "";
  $arrStatusDenied = [2, 8]; // daftar status yang sifatnya ditolak :D
  $arrStatusEmplEditable = array_merge(
      $arrStatusDenied,
      [0, 5, 7]
  ); // daftar status yang sifatnya bisa diedit oleh employee
  //ambil overtime reason
  $arrReason = [];
  $strSQL = "SELECT * FROM hrd_overtime_reason";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $arrReason[$row['id']] = $row['code'];
  }
  // ambil dulu data employee, kumpulkan dalam array
  $arrEmployee = [];
  $i = 0;
  $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.department_code, t2.section_code, t2.active ";
  $strSQL .= "FROM hrd_overtime AS t1, hrd_employee AS t2 ";
  $strSQL .= "WHERE t1.id_employee = t2.id $strKriteria ";
  if (!isset($_REQUEST['btnShowAlert'])) {
    $strSQL .= "AND t1.overtime_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
    //$strSQL .= "AND t1.application_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
  }
  $strSQL .= "ORDER BY $strOrder t1.overtime_date DESC, t2.employee_name ";
  $resDb = $db->execute($strSQL);
  $strDateOld = "";
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $bolEditable = true;
    $strDisabled = "disabled"; // untuk  mengatur apakah data bisa diubah statusnya atau tidak
    $strBgClass = "";
    $strBgClass1 = ""; // kelas buat yang kedaluwarsa
    $strHiddenNote = ""; // buat info jika kedaluwarsa
    $strNomor = $rowDb['no'] . "/" . $rowDb['code'] . "/" . $rowDb['month_code'] . "/" . $rowDb['year_code'];
    $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
    // cek apakah boleh edit atau gak
    if (isMe($rowDb['id_employee'])) {
      $strDisabled = "";
    } else if (isMe($rowDb['manager_id'])) {
      $strDisabled = "";
    } else {
      $bolEditable = false; // gak boleh edit
      if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE && ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead'])) {
        // anggap sebagai manager/atasannya
        if ($rowDb['status'] < 6) {
          // bisa diupdate statusnya
          $strDisabled = "";
        }
      } else if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
        $bolEditable = true;
        $strDisabled = "";
        /*
        if (($rowDb['status'] > 2 && $rowDb['status'] < 5) || $rowDb['status'] == 1) {
          // bisa diupdate statusnya, karena sudah dicek/approved oleh atasannya
          $strDisabled = "";
        }
        if ($rowDb['status'] > 9 && $rowDb['status'] < 11) {
          // bisa diupdate statusnya, karena sudah dicek/approved oleh atasannya
          $strDisabled = "";
        }
        */
      } else if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        $bolEditable = true;
        $strDisabled = "";
        /*
        if (($rowDb['status'] > 2 && $rowDb['status'] < 7) || $rowDb['status'] == 1) {
          // bisa diupdate statusnya, karena sudah dicek/approved oleh atasannya
          $strDisabled = "";
        }
        if ($rowDb['status'] > 9) {
          // bisa diupdate statusnya, karena sudah dicek/approved oleh atasannya
          $strDisabled = "";
        }
        */
      }
    }
    // cek status
    if ($rowDb['status'] < 3) {
      $strStatus1 = getWords($ARRAY_OT_STATUS[$rowDb['status']]);
      $strStatus2 = "";
      /*if ($rowDb['status'] == 0) $strStatus1 = getWords("new");
      else if ($rowDb['status'] == 1) $strStatus1 = getWords("verified");
      else if ($rowDb['status'] == 3) $strStatus1 = getWords("checked");
      else if ($rowDb['status'] == 5) $strStatus1 = getWords("approved");
      */
    } else {
      $strStatus1 = getWords("approved");//$words[$ARRAY_OT_STATUS[5]];
      $strStatus2 = getWords($ARRAY_OT_STATUS[$rowDb['status']]);
      /*
      if ($rowDb['status'] == 7) $strStatus2 = getWords("new");
      else if ($rowDb['status'] == 8) $strStatus2 = getWords("verified");
      else if ($rowDb['status'] == 10) $strStatus2 = getWords("checked");
      else if ($rowDb['status'] == 12) $strStatus2 = getWords("approved");
      */
    }
    // cek status, untuk tampilan warnanya, mengatur class
    $bolDenied = false;
    if ($rowDb['status'] == 0 || $rowDb['status'] == 3) { // baru
      $strBgClass = "bgNewData";
    } else if ($rowDb['status'] == 1 || $rowDb['status'] == 4 || $rowDb['status'] == 5) { // approve atasan
      $strBgClass = "bgVerifiedData";
    } else if ($rowDb['status'] == 6) { // cek admin
      $strBgClass = "bgCheckedData";
    } else if ($rowDb['status'] == 2 || $rowDb['status'] == 8) {
      $strBgClass = "bgDenied";
      $bolDenied = true;
    }
    // tampilkan waktu verifikasi oleh atasan (terhadap realisasi)
    $strVerifiedTime = substr($rowDb['verified_time1'], 0, 10);
    // cek apakah permohonan sudah kedaluwarsa
    if ($rowDb['status'] == 0) {
      if (isOutDated($rowDb['overtime_date'])) {
        $strBgClass1 = "class=bgBlocked";
        $strHiddenNote = "<input type=hidden name=dataBlocked$intRows value='yes'>";
        $strHiddenNote .= "<input type=hidden name=dataNote$intRows  disabled>"; // untuk note saat verified
        $strHiddenNote .= "<input type=hidden name=detailName$intRows  disabled value=\"" . $rowDb['employee_name'] . "\">";
        $strHiddenNote .= "<input type=hidden name=detailDate$intRows  disabled value=\"" . pgDateFormat(
                $rowDb['application_date'],
                "d M Y"
            ) . "\">";
        if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
          $strBgClass = "bgBlocked";
        }
      }
    }
    $strAttendanceFinish = "";// cari data aktual kehadiran sesuai mesin
    $strSQL = "SELECT attendance_finish FROM hrd_attendance ";
    $strSQL .= "WHERE id_employee = '" . $rowDb['id_employee'] . "' ";
    $strSQL .= "AND attendance_date = '" . $rowDb['overtime_date'] . "' ";
    $resA = $db->execute($strSQL);
    if ($rowA = $db->fetchrow($resA)) {
      $strAttendanceFinish = $rowA['attendance_finish'];
    }
    $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strBgClass>\n";
    if (!$bolPrint) {
      $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\" $strDisabled></td>\n";
    } else {
      $strResult .= "  <td>&nbsp;</td>\n";
    }
    $strResult .= "  <td $strBgClass1>" . pgDateFormat(
            $rowDb['overtime_date'],
            "d-M-y"
        ) . "&nbsp;$strHiddenNote</td>\n";
    $strResult .= "  <td>" . $rowDb['employee_id'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['department_code'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['section_code'] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . printAct($rowDb['active']) . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . substr($rowDb['time_start'], 0, 5) . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . substr($rowDb['time_finish'], 0, 5) . "&nbsp;</td>";
    $strResult .= "  <td align=right>" . cekStandardFormat($rowDb['duration'] / 60) . "</td>";
    //$strResult .= "  <td align=center>$strStatus1&nbsp;</td>";
    if ($rowDb['status'] < 3) { // baru usul
      $strResult .= "  <td align=center>&nbsp;</td>";
      $strResult .= "  <td align=center>&nbsp;</td>";
      $strResult .= "  <td align=right>&nbsp;</td>";
      $strResult .= "  <td align=right>&nbsp;</td>";
      $strResult .= "  <td align=right>&nbsp;</td>";
      $strResult .= "  <td align=right>&nbsp;</td>";
      $strResult .= "  <td align=right>&nbsp;</td>";
    } else { // sudah tahap actual
      $strResult .= "  <td align=center>" . substr($rowDb['actual_start'], 0, 5) . "&nbsp;</td>";
      $strResult .= "  <td align=center>" . substr($rowDb['actual_finish'], 0, 5) . "&nbsp;</td>";
      $strResult .= "  <td align=right>" . cekStandardFormat($rowDb['actual_duration'] / 60) . "</td>";
      $strResult .= "  <td align=right>" . cekStandardFormat($rowDb['ot1'] / 60) . "</td>";
      $strResult .= "  <td align=right>" . cekStandardFormat($rowDb['ot2'] / 60) . "</td>";
      $strResult .= "  <td align=right>" . cekStandardFormat($rowDb['ot3'] / 60) . "</td>";
      $strResult .= "  <td align=right>" . cekStandardFormat($rowDb['ot4'] / 60) . "</td>";
    }
    $strResult .= "  <td align=center>$strStatus2&nbsp;</td>";
    $strResult .= "  <td align=center>" . substr($strAttendanceFinish, 0, 5) . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['shift_code'] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $arrReason[$rowDb['id_overtime_reason']] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
    //$strResult .= "  <td align=right>" .$fltCash. "&nbsp;</td>";
    $strResult .= "  <td align=right>" . cekStandardFormat($rowDb['meal_compensation']) . "</td>";
    //$strResult .= "  <td align=right>" .cekStandardFormat($rowDb['transport_compensation']). "</td>";
    if ($bolEditable && !$bolPrint) {
      $strResult .= "  <td align=center><a href=\"overtime_edit.php?dataID=" . $rowDb['id'] . "\">" . $words['edit'] . "</a>&nbsp;</td>";
    } else {
      if (!$bolPrint) {
        $strResult .= "  <td align=center>&nbsp;</td>";
      }
    }
    if (!$bolPrint) {
      // tambahkan info record info
      $strDiv = "<div id='detailRecord$intRows' style=\"display:none\">\n";
      $strDiv .= "<strong>" . $rowDb['employee_id'] . "-" . $rowDb['employee_name'] . "</strong><br><br>\n";
      $strDiv .= getWords("last modified") . ": " . substr($rowDb['created'], 0, 19) . " ";
      $strDiv .= (isset($arrUserList[$rowDb['modified_by']])) ? $arrUserList[$rowDb['modified_by']]['name'] . "<br>" : "<br>";
      $strDiv .= "<strong>" . getWords("ot plan") . "</strong><br>\n";
      $strDiv .= getWords("verified") . ": " . substr($rowDb['verified_time'], 0, 19) . " ";
      $strDiv .= (isset($arrUserList[$rowDb['verified_by']])) ? $arrUserList[$rowDb['verified_by']]['name'] . "<br>" : "<br>";
      /*
              $strDiv .= getWords("checked"). ": ".substr($rowDb['checked_time'], 0,19) ." ";
              $strDiv .= (isset($arrUserList[$rowDb['checked_by']])) ? $arrUserList[$rowDb['checked_by']]['name']."<br>" : "<br>";

              $strDiv .= getWords("approved"). ": ".substr($rowDb['approved_time'], 0,19) ." ";
              $strDiv .= (isset($arrUserList[$rowDb['approved_by']])) ? $arrUserList[$rowDb['approved_by']]['name']."<br>" : "<br>";
      */
      $strDiv .= getWords("denied") . ": " . substr($rowDb['denied_time'], 0, 19) . " ";
      $strDiv .= (isset($arrUserList[$rowDb['denied_by']])) ? $arrUserList[$rowDb['denied_by']]['name'] . "<br>" : "<br>";
      $strDiv .= "<strong>" . getWords("ot actual") . "</strong><br>\n";
      $strDiv .= getWords("verified") . ": " . substr($rowDb['verified_time1'], 0, 19) . " ";
      $strDiv .= (isset($arrUserList[$rowDb['verified_by1']])) ? $arrUserList[$rowDb['verified_by1']]['name'] . "<br>" : "<br>";
      $strDiv .= getWords("checked") . ": " . substr($rowDb['checked_time1'], 0, 19) . " ";
      $strDiv .= (isset($arrUserList[$rowDb['checked_by1']])) ? $arrUserList[$rowDb['checked_by1']]['name'] . "<br>" : "<br>";
      $strDiv .= getWords("approved") . ": " . substr($rowDb['approved_time1'], 0, 19) . " ";
      $strDiv .= (isset($arrUserList[$rowDb['approved_by1']])) ? $arrUserList[$rowDb['approved_by1']]['name'] . "<br>" : "<br>";
      $strDiv .= getWords("denied") . ": " . substr($rowDb['denied_time1'], 0, 19) . " ";
      $strDiv .= (isset($arrUserList[$rowDb['denied_by1']])) ? $arrUserList[$rowDb['denied_by1']]['name'] . "<br>" : "<br>";
      $strDiv .= "</div>\n";
      $strResult .= "  <td nowrap align=center><a href=\"javascript:openWindowById('detailRecord$intRows')\" title=\"" . getWords(
              "show record info"
          ) . "\">" . getWords("show") . "$strDiv</a></td>\n";
    }
    $strResult .= "</tr>\n";
    // tampilkan alasan jika statusnya ditolak
    if ($bolDenied || $rowDb['note_denied'] != "") {
      $strResult .= "<tr valign=top class=$strBgClass>\n";
      $strResult .= "  <td>&nbsp;</td>\n";
      $strResult .= "  <td colspan=27>&nbsp;<strong>" . $words['reason'] . " : " . $rowDb['note_denied'] . "&nbsp;</strong></td>\n";
      $strResult .= "</tr>\n";
    }
  }
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  return $strResult;
} // showData
// ambil data, khsusu untuk rekap per karyawan
function getDataReport($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $ARRAY_LEAVE_TYPE;
  global $ARRAY_OT_STATUS;
  global $arrUserInfo;
  global $_SESSION;
  global $_REQUEST;
  global $bolPrint;
  $intRows = 0;
  $strResult = "";
  $arrStatusDenied = [2, 8]; // daftar status yang sifatnya ditolak :D
  $arrStatusEmplEditable = array_merge(
      $arrStatusDenied,
      [0, 5, 7]
  ); // daftar status yang sifatnya bisa diedit oleh employee
  // ambil dulu data employee, kumpulkan dalam array
  $arrEmployee = [];
  $i = 0;
  $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.department_code, t2.section_code ";
  $strSQL .= "FROM hrd_overtime AS t1, hrd_employee AS t2 ";
  $strSQL .= "WHERE t1.id_employee = t2.id AND status >= " . REQUEST_STATUS_APPROVED . " $strKriteria ";
  $strSQL .= "ORDER BY $strOrder t1.overtime_date, t2.employee_name ";
  $resDb = $db->execute($strSQL);
  $strDateOld = "";
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $bolEditable = true;
    $strBgClass = "";
    $intWDay = getWDay($rowDb['overtime_date']);
    $strWDay = getNamaHari($intWDay);
    $strResult .= "<tr valign=top>\n";
    $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>";
    $strResult .= "  <td nowrap align=center>$strWDay&nbsp;</td>";
    $strResult .= "  <td nowrap align=center>" . pgDateFormat($rowDb['overtime_date'], "d-M-y") . "&nbsp;</td>\n";
    $strResult .= "  <td align=center>" . substr($rowDb['actual_start'], 0, 5) . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . substr($rowDb['actual_finish'], 0, 5) . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['shift_code'] . "&nbsp;</td>";
    $strResult .= "  <td align=right>" . cekStandardFormat($rowDb['duration'] / 60) . "&nbsp;</td>";
    $strResult .= "  <td align=right>" . cekStandardFormat($rowDb['meal_compensation']) . "&nbsp;</td>";
    //$strResult .= "  <td align=right>" .cekStandardFormat($rowDb['transport_compensation']). "&nbsp;</td>";
    //$strResult .= "  <td align=right>" .cekStandardFormat($rowDb['meal_compensation'] + $rowDb['transport_compensation']). "&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  return $strResult;
} // getDataReport
// fungsi untuk melakukan approval terhadap data
function approvedData($db)
{
  global $_REQUEST;
  global $_SESSION;
  global $arrUserInfo;
  global $arrUserList;
  $strmodified_by = $_SESSION['sessionUserID'];
  $i = 0;
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 5) == 'chkID') {
      $id = substr($strIndex, 5, strlen($strIndex) - 5);
      if (isset($_REQUEST['dataBlocked' . $id]) && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
        // diproses dengan catatan, karena sudah kedaluwarsa
        $strNote = (isset($_REQUEST['dataNote' . $id])) ? $_REQUEST['dataNote' . $id] : "";
        if ($strNote != "") {
          // simpan data
          $strSQL = "UPDATE hrd_overtime SET status = 1, note_denied = '$strNote', ";
          $strSQL .= "verified_by = '$strmodified_by', verified_time = now() ";
          $strSQL .= "WHERE id = '$strValue' AND status = 0 "; // hanya yang baru, yagn boleh
          $resExec = $db->execute($strSQL);
          $i++;
        }
      } else {
        // lihat dulu statusnya
        $strSQL = "SELECT status FROM hrd_overtime WHERE id = '$strValue' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
          $intNextStatus = "";
          $strUpdate = "";
          // tentukan siapa yang approved
          if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) { // atasan
            //$intNextStatus = ($rowDb['status'] < 7) ? 1 : 8;
            if ($rowDb['status'] < 3) {
              $intNextStatus = 1;
              $strUpdate = "verified_time = now(), verified_by = '$strmodified_by', ";
            } else {
              if ($arrUserInfo['isDeptHead']) {
                $intNextStatus = 5;
              } else {
                $intNextStatus = 4;
              }
              $strUpdate = "verified_time1 = now(), verified_by1 = '$strmodified_by', ";
            }
          } else if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) { // admmin hrd
            //$intNextStatus = ($rowDb['status'] < 7) ? 3 : 10;
            if ($rowDb['status'] < 3) {
              $intNextStatus = 1;
              $strUpdate = "verified_time = now(), verified_by = '$strmodified_by', ";
            } else {
              $intNextStatus = 6;
              $strUpdate = "checked_time1 = now(), checked_by1 = '$strmodified_by', ";
            }
          } else if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) { // manager hrd
            //$intNextStatus = ($rowDb['status'] < 7) ? 5 : 12;
            if ($rowDb['status'] < 3) {
              $intNextStatus = 1;
              $strUpdate = "verified_time = now(), verified_by = '$strmodified_by', ";
            } else {
              $intNextStatus = REQUEST_STATUS_APPROVED;
              $strUpdate = "approved_time1 = now(), approved_by1 = '$strmodified_by', ";
            }
          }
          if ($intNextStatus != "") {
            $strSQL = "UPDATE hrd_overtime SET $strUpdate status = '$intNextStatus', note_denied = '' WHERE id = '$strValue' ";
            //  jika group head, gak boleh approve diri sendiri
            if ($arrUserInfo['isGroupHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
              $strSQL .= "AND id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
            }
            $resExec = $db->execute($strSQL);
            $i++;
          }
        }
      }
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data", 0);
  }
} //approvedData
function printAct($a)
{
  if ($a == 1) {
    return "&radic;";
  } else {
    return "";
  }
}

// fungsi untuk melakukan pennolakan terhadap data
function deniedData($db)
{
  global $_REQUEST;
  global $_SESSION;
  global $arrUserInfo;
  global $arrUserList;
  $strmodified_by = $_SESSION['sessionUserID'];
  $i = 0;
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 5) == 'chkID') {
      $i++;
      $id = substr($strIndex, 5, strlen($strIndex) - 5);
      // lihat dulu statusnya
      $strSQL = "SELECT status FROM hrd_overtime WHERE id = '$strValue' ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $intNextStatus = "";
        // tentukan siapa yang approved
        /*
        if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) { // atasan
          $intNextStatus = ($rowDb['status'] < 3) ? 2 : 9;
        } else if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) { // admmin hrd
          $intNextStatus = ($rowDb['status'] < 3) ? 2 : 11;
        } else if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) { // manager hrd
          $intNextStatus = ($rowDb['status'] < 3) ? 2 : 8;
        }*/
        $intNextStatus = ($rowDb['status'] < 3) ? 2 : 8;
        if ($rowDb['status'] < 3) {
          $strUpdate = "denied_time = now(), denied_by = '$strmodified_by', ";
        } else {
          $strUpdate = "denied_time1 = now(), denied_by1 = '$strmodified_by', ";
        }
        if ($intNextStatus != "") {
          $strSQL = "UPDATE hrd_overtime SET $strUpdate status = '$intNextStatus' WHERE id = '$strValue' ";
          $resExec = $db->execute($strSQL);
        }
      }
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data", 0);
  }
} //deniedData
// fungsi untuk menghapus data
function deleteData($db)
{
  global $_REQUEST;
  global $_SESSION;
  global $arrUserInfo;
  $i = 0;
  if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    $strKriteria = "";
  } // bisa hapus semua
  else {
    $strKriteria = "AND id_employee = '" . $arrUserInfo['id_employee'] . "' "; // hanya punya sendiri
    $strKriteria .= ($arrUserInfo['isDeptHead']) ? "AND status = 0 " : "AND status = 0 ";
  }
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 5) == 'chkID') {
      $i++;
      $id = substr($strIndex, 5, strlen($strIndex) - 5);
      $strSQL = "DELETE FROM hrd_overtime WHERE id = '$strValue' $strKriteria ";
      $resExec = $db->execute($strSQL);
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
  }
} //deleteData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$strUserRole = "";
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  $arrUserList = getAllUserInfo($db);
  $strUserRole = $_SESSION['sessionUserRole'];
  // hapus data jika ada perintah
  if (isset($_POST['btnDelete'])) {
    if ($bolCanDelete) {
      deleteData($db);
    }
  } else if (isset($_POST['btnApproved'])) {
    if ($bolCanEdit) {
      approvedData($db);
    }
  } else if (isset($_POST['btnDenied'])) {
    if ($bolCanEdit) {
      deniedData($db);
    }
  }
  // ------ AMBIL DATA KRITERIA -------------------------
  $strDataDateFrom = (isset($_SESSION['sessionFilterDateFrom'])) ? $_SESSION['sessionFilterDateFrom'] : date("Y-m-d");
  $strDataDateThru = (isset($_SESSION['sessionFilterDateThru'])) ? $_SESSION['sessionFilterDateThru'] : date("Y-m-d");
  $strDataDepartment = (isset($_SESSION['sessionFilterDepartment'])) ? $_SESSION['sessionFilterDepartment'] : "";
  $strDataSection = (isset($_SESSION['sessionFilterSection'])) ? $_SESSION['sessionFilterSection'] : "";
  $strDataEmployee = (isset($_SESSION['sessionFilterEmployee'])) ? $_SESSION['sessionFilterEmployee'] : "";
  $strDataActive = (isset($_SESSION['sessionFilterActive'])) ? $_SESSION['sessionFilterActive'] : "";
  $strDataEmployeeStatus = (isset($_SESSION['sessionFilterEmployeeStatus'])) ? $_SESSION['sessionFilterEmployeeStatus'] : "";
  if (isset($_REQUEST['dataDateFrom'])) {
    $strDataDateFrom = $_REQUEST['dataDateFrom'];
  }
  if (isset($_REQUEST['dataDateThru'])) {
    $strDataDateThru = $_REQUEST['dataDateThru'];
  }
  if (isset($_REQUEST['dataDepartment'])) {
    $strDataDepartment = $_REQUEST['dataDepartment'];
  }
  if (isset($_REQUEST['dataSection'])) {
    $strDataSection = $_REQUEST['dataSection'];
  }
  if (isset($_REQUEST['dataEmployee'])) {
    $strDataEmployee = $_REQUEST['dataEmployee'];
  }
  if (isset($_REQUEST['dataActive'])) {
    $strDataActive = $_REQUEST['dataActive'];
  }
  if (isset($_REQUEST['dataEmployeeStatus'])) {
    $strDataEmployeeStatus = $_REQUEST['dataEmployeeStatus'];
  }
  // default selalu ambil yang aktif saja
  if ($strDataActive == "") {
    $strDataActive = 1;
  }
  // simpan dalam session
  $_SESSION['sessionFilterDateFrom'] = $strDataDateFrom;
  $_SESSION['sessionFilterDateThru'] = $strDataDateThru;
  $_SESSION['sessionFilterDepartment'] = $strDataDepartment;
  $_SESSION['sessionFilterSection'] = $strDataSection;
  $_SESSION['sessionFilterEmployee'] = $strDataEmployee;
  $_SESSION['sessionFilterActive'] = $strDataActive;
  $_SESSION['sessionFilterEmployeeStatus'] = $strDataEmployeeStatus;
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if ($bolIsEmployee) {
    if ($arrUserInfo['employee_id'] == "") {
      $strKriteria .= "AND 1=2 ";
    } else {
      $strDataEmployee = $arrUserInfo['employee_id'];
      $strKriteria .= "AND employee_id = '$strDataEmployee' AND t1.flag = 0 ";
    }
  } else {
    // batasi untuk dept-head/group head
    if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE && $arrUserInfo['isDeptHead']) {
      $strDataDepartment = $arrUserInfo['department_code'];
    } else if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE && $arrUserInfo['isGroupHead']) {
      $strDataSection = $arrUserInfo['section_code'];
    }
    // buat filtering
    if ($strDataDepartment != "") {
      $strKriteria .= "AND (department_code = '$strDataDepartment' ";
      if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE && $arrUserInfo['isDeptHead']) {
        $strKriteria .= "OR t1.manager_id = '" . $arrUserInfo['id_employee'] . "') "; // cek jika ada yang jadi bagian dia
      } else {
        $strKriteria .= ") ";
      }
    }
    if ($strDataSection != "") {
      $strKriteria .= "AND (section_code = '$strDataSection' ";
      if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE && $arrUserInfo['isGroupHead']) {
        $strKriteria .= "OR t1.manager_id = '" . $arrUserInfo['id_employee'] . "') "; // cek jika ada yang jadi bagian dia
      } else {
        $strKriteria .= ") ";
      }
    }
    if ($strDataActive != "") {
      $strKriteria .= "AND active = '$strDataActive' AND t1.flag = 0 ";
    }
    if ($strDataEmployee != "") {
      $strKriteria .= "AND employee_id = '$strDataEmployee' AND t1.flag = 0 ";
    }
    if ($strDataEmployeeStatus != "") {
      $strKriteria .= "AND employee_status = '$strDataEmployeeStatus' ";
    }
  }
  if (isset($_REQUEST['btnPrintApproved'])) {
    $strKriteria .= "AND status >= " . REQUEST_STATUS_APPROVED . " ";
  }
  if (isset($_REQUEST['btnShowAlert'])) { // request dari alert
    $status = (isset($_REQUEST['dataStatus'])) ? $_REQUEST['dataStatus'] : "";
    if (is_numeric($status)) {
      $strKriteria .= "AND status = $status";
    } else {
      $strKriteria .= "AND status IN (0,1,3,7,8,10) ";
    }
  }
  if ($bolCanView) {
    if (validStandardDate($strDataDateFrom) && validStandardDate($strDataDateThru)) {
      // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
      if ($bolPrintReport) {
        // cari info employee
        $strSQL = "SELECT t1.employee_id, t1.employee_name, t1.department_code, t1.active,";
        $strSQL .= "t1.section_code, t2.department_name FROM hrd_employee AS t1 ";
        $strSQL .= "LEFT JOIN hrd_department AS t2 ON t1.department_code = t2.department_code ";
        $strSQL .= "WHERE t1.flag=0 AND t1.employee_id = '$strDataEmployee' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
          $stremployee_id = $rowDb['employee_id'];
          $stremployee_name = $rowDb['employee_name'];
          $strDepartment = $rowDb['department_code'] . " - " . $rowDb['department_name'];
          //$stremployee_name = $rowDb['employee_name'];
        }
        $strDataDetail = getDataReport($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
      } else {
        $strDataDetail = getData($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
      }
    } else {
      $strDataDetail = "";
    }
  } else {
    showError("view_denied");
  }
  if ($bolCanView) {
    //if (isset($_REQUEST['btnExcel'])) $bolLimit = false;
    //$strDataDetail = getData($db,$intTotalData, $strKriteria, $intCurrPage, $bolLimit);
    if (isset($_REQUEST['btnExcel'])) {
      $strDataDetail = getData($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
      // ambil data CSS-nya
      if (file_exists("bw.css")) {
        $strStyle = "bw.css";
      }
      $strPrintCss = "";
      $strPrintInit = "";
      headeringExcel("overtime.xls");
    }
  }
  // generate data hidden input dan element form input
  $strReadonly = ($bolIsEmployee) ? "readonly" : "";
  $strDisabled = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) ? "disabled" : "";
  $intDefaultWidthPx = 200;
  $strInputDateFrom = "<input type=text name=dataDateFrom id=dataDateFrom size=15 maxlength=10 value=\"$strDataDateFrom\">";
  $strInputDateThru = "<input type=text name=dataDateThru id=dataDateThru size=15 maxlength=10 value=\"$strDataDateThru\">";
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"$strDataEmployee\" $strReadonly>";
  $strTmpKriteria = "WHERE 1 =1 ";
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE && $arrUserInfo['isDeptHead']) {
    $strTmpKriteria .= "AND department_code = '" . $arrUserInfo['department_code'] . "' ";
    $strDisabled = "";
  }
  $strInputDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $strDataDepartment,
      $strEmptyOption,
      $strTmpKriteria,
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE && $arrUserInfo['isGroupHead']) {
    $strTmpKriteria .= "AND section_code = '" . $arrUserInfo['section_code'] . "' ";
    $strDisabled = "";
  }
  $strInputSection = getSectionList(
      $db,
      "dataSection",
      $strDataSection,
      $strEmptyOption,
      $strTmpKriteria,
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  $strInputEmployeeStatus = getEmployeeStatusList(
      "dataEmployeeStatus",
      $strDataEmployeeStatus,
      $strEmptyOption2,
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  $strInputActive = getEmployeeActiveList(
      "dataActive",
      $strDataActive,
      $strEmptyOption2,
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  //     $strInputSubsection = getSubSectionList($db,"dataSubsection",$strDataSubsection, $strEmptyOption, "", "style=\"width:$intDefaultWidthPx\" $strDisabled");
  // bikin daftar button yang mungkin ada
  if ($bolIsEmployee) {
    // bisa hapus doank, itupun punya sendiri
    //$strButtonList .= "&nbsp;<input type=submit name=btnDelete value=\"" .$words['delete']. "\" onClick=\"return confirmDelete();\">";
  } else {
    if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
      //$strButtonList .= "&nbsp;<input type=submit name=btnDelete value=\"" .$words['delete']. "\" onClick=\"return confirmDelete();\">";
      $strButtonList .= "&nbsp;<input type=submit name=btnApproved value=\"" . $words['checked'] . "\" onClick=\"return confirmChange(false);\">";
    } else if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
      //         $strButtonList .= "&nbsp;<input type=submit name=btnDelete value=\"" .$words['delete']. "\" onClick=\"return confirmDelete();\">";
      $strButtonList .= "&nbsp;<input type=submit name=btnApproved value=\"" . $words['approved'] . "\" onClick=\"return confirmChange(false);\">";
    } else if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
      $strButtonList .= "&nbsp;<input type=submit name=btnApproved value=\"" . $words['approved'] . "\" onClick=\"return confirmChange(false);\">";
    }
    $strButtonList .= "&nbsp;<input type=submit name=btnDenied value=\"" . $words['denied'] . "\" onClick=\"return confirmChange(true);\">";
  }
  $strButtonList .= "&nbsp;<input type=submit name=btnDelete value=\"" . $words['delete'] . "\" onClick=\"return confirmDelete();\">";
  // informasi tanggal kehadiran
  if ($strDataDateFrom == $strDataDateThru) {
    $strInfo .= "<br>" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-y"));
  } else {
    $strInfo .= "<br>" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-y"));
    $strInfo .= " >> " . strtoupper(pgDateFormat($strDataDateThru, "d-M-y"));
  }
  $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDataDateFrom\">";
  $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDataDateThru\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
  //     $strHidden .= "<input type=hidden name=dataSubsection value=\"$strDataSubsection\">";
  $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
  $strHidden .= "<input type=hidden name=dataActive value=\"$strDataActive\">";
  $strHidden .= "<input type=hidden name=dataEmployeeStatus value=\"$strDataEmployeeStatus\">";
}
if ($bolPrint) {
  $strMainTemplate = getTemplate("overtime_list_print.html");
} else if ($bolPrintReport) {
  $strMainTemplate = getTemplate("overtime_list_report_print.html");
} else {
  $strTemplateFile = getTemplate("overtime_list.html");
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>