<?php
session_start();
include_once('global.php');
include_once('import_func.php');
include_once('../global/employee_function.php');
////include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  header("location:login.php?dataPage=frontpage.php");
  exit();
}
$bolCanView = getUserPermission("frontpage.php", $bolCanEdit, $bolCanDelete, $strError, true);
/*
if ($_SESSION['sessionUserModule'] == MODULE_PAYROLL) {
  $bolCanView = true;
  $bolCanEdit = true;
  $bolCanDelete = true;
} else {
  $bolCanView = false;
  $bolCanEdit = false;
  $bolCanDelete = false;
}
*/
$strTemplateFile = getTemplate("welcome.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strDetailNews = "";
$strDetailContract = "";
$strDetailAlert = "";
$strDetailBirthday = "";
$strNow = date("Y-m-d");
$stremployee_id = "";
$stremployee_name = "";
$strEmployeeStatus = "";
$strDepartment = "";
$strPosition = "";
$strLeave = "";
$strMedical = "";
$arrDataEmployee = [];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi mengambil daftar karyawan yang hampir habis masa kontrak
function getDataContract($db)
{
  global $words;
  global $_SESSION;
  global $strDataSection;
  global $ARRAY_EMPLOYEE_STATUS;
  global $bolIsEmployee;
  global $arrUserInfo;
  $intRows = 0;
  $strResult = "";
  // cari info perubahan karyawan yang belum disetujui, untuk status renewalnya
  $arrMutation = [];
  $strSQL = "SELECT t2.id, t2.id_employee FROM hrd_employee_mutation_status AS t1, hrd_employee_mutation AS t2 ";
  $strSQL .= "WHERE t1.id_mutation = t2.id AND t2.status < " . REQUEST_STATUS_APPROVED;
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrMutation[$rowDb['id_employee']] = $rowDb['id'];
  }
  // kriteria contract yang ditampilkan adalah, yang duedatenya sudah lewat 1 minggu, dan hampmir habis 1 bulan lagi
  $strSQL = "SELECT *, ((due_date) - CURRENT_DATE) AS selisih, ";
  $strSQL .= "CASE WHEN (due_date = CURRENT_DATE) THEN 1 ELSE 0 END AS sekarang ";
  $strSQL .= "FROM hrd_employee ";
  $strSQL .= "WHERE ((due_date > CURRENT_DATE AND (due_date - interval '2 months') < CURRENT_DATE) OR (permanent_date is null AND due_date < CURRENT_DATE)) ";
  //$strSQL .= "WHERE ((due_date BETWEEN date(now() - interval '7 days') AND date(now() + interval '1 months')) ";
  //$strSQL .= "OR due_date > CURRENT_DATE) AND resign_date is null AND permanent_date is null ";
  $strSQL .= "AND employee_status <> " . STATUS_PERMANENT . " AND active = 1 AND flag = 0 ";
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    // jika employee, haknya dibatasi
    if ($arrUserInfo['isDeptHead']) {
      $strSQL .= "AND department_code = '" . $arrUserInfo['department_code'] . "' ";
    } else if ($arrUserInfo['isGroupHead']) {
      $strSQL .= "AND section_code = '" . $arrUserInfo['section_code'] . "' ";
    } else {
      $strSQL .= "AND 1=2 ";
    } // kosongkan isi
  }
  $strSQL .= "ORDER BY due_date DESC, employee_name ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strID = $rowDb['id'];
    $strJoinDate = pgDateFormat($rowDb['join_date'], "d-M-y");
    $strDueDate = pgDateFormat($rowDb['due_date'], "d-M-y");
    //$stremployee_id = ($bolIsEmployee) ? $rowDb['employee_id'] : "<a href='employee_resume.php?dataID=$strID'>" .$rowDb['employee_id']."</a>";
    $stremployee_id = $rowDb['employee_id'];
    $strName = $rowDb['employee_name'];
    //$strSection = ($rowDb['sub_section_code'] == "") ? $rowDb['section_code'] : $rowDb['sub_section_code'];
    //$strGender = ($rowDb['gender'] == 0) ? "F" : "M";
    $strSelisih = $rowDb['selisih'];
    $strClass = ($rowDb['sekarang'] == 1) ? "class = 'bgLate'" : "";
    if (isset($arrMutation[$rowDb['id']])) {
      $strLinkPar = "dataID=" . $arrMutation[$rowDb['id']];
      $strConfirm = " [&radic;]";
      $strClass = 'class="bgNewRevised"';
    } else {
      $strLinkPar = "dataIDEmployee=" . $rowDb['id'];
      $strConfirm = "";
    }
    $strResult .= "<tr valign=top $strClass>\n";
    $strResult .= "  <td nowrap>$stremployee_id&nbsp;</td>";
    $strResult .= "  <td nowrap>$strName&nbsp;</td>";
    $strResult .= "  <td align=center>$strJoinDate&nbsp;</td>";
    $strResult .= "  <td align=center>$strDueDate&nbsp;</td>";
    $strResult .= "  <td>$strSelisih&nbsp;</td>";
    $strResult .= "  <td align=center nowrap><a href=\"mutation_edit.php?btnRenew=Renew&$strLinkPar\">" . getWords(
            "renew"
        ) . " $strConfirm</a>&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  $intTotalData = $intRows;
  return $strResult;
} // getDataContract
function getDataBirthday($db)
{
  global $words;
  global $_SESSION;
  global $bolIsEmployee;
  $intRows = 0;
  $strResult = "";
  $dtNow = getdate();
  $tsNow = mktime(0, 0, 0, $dtNow['mon'], $dtNow['mday'], $dtNow['year']);
  $tsAwal = $tsNow - (86400 * 2);
  $tsAkhir = $tsNow + (86400 * 4);
  $strAwal = date("m-d", $tsAwal);
  $strAkhir = date("m-d", $tsAkhir);
  // kriteria contract yang ditampilkan adalah, yang duedatenya sudah lewat 1 minggu, dan hampmir habis 1 bulan lagi
  $strSQL = "SELECT *, EXTRACT(YEAR FROM AGE(\"birthday\")) AS umur, ";
  $strSQL .= "CASE WHEN (SUBSTRING(CAST (birthday as text ) FROM 6 FOR 5) = SUBSTRING(CAST (CURRENT_DATE as text )  FROM 6 FOR 5)) THEN 1 ELSE 0 END AS sekarang ";
  $strSQL .= "FROM hrd_employee ";
  $strSQL .= "WHERE SUBSTRING(CAST (birthday as text ) FROM 6 FOR 5) BETWEEN '$strAwal' AND '$strAkhir'  ";
  $strSQL .= "AND flag = 0 AND active = 1 ";
  $strSQL .= "ORDER BY SUBSTRING(CAST (birthday as text )  FROM 6 FOR 5) DESC, employee_name ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strID = $rowDb['id'];
    $strBirthday = pgDateFormat($rowDb['birthday'], "d M");
    //$stremployee_id = ($bolIsEmployee) ? $rowDb['employee_id'] : "<a href='employee_resume.php?dataID=$strID'>" .$rowDb['employee_id']."</a>";
    $stremployee_id = $rowDb['employee_id'];
    $strName = $rowDb['employee_name'];
    $strGender = ($rowDb['gender'] == 0) ? "F" : "M";
    $strUmur = "";//$rowDb['umur'];
    $strClass = ($rowDb['sekarang'] == 1) ? "class = 'bgLate'" : "";
    $strResult .= "<tr valign=top $strClass>\n";
    /*
    if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
      $strResult .= "  <td nowrap>$stremployee_id&nbsp;</td>";
    } else {
      $strResult .= "  <td nowrap>$stremployee_id&nbsp;</td>";
    }
    */
    $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>";
    $strResult .= "  <td>$strName&nbsp;</td>";
    $strResult .= "  <td align=center>$strGender&nbsp;</td>";
    $strResult .= "  <td align=center>$strBirthday&nbsp;</td>";
    $strResult .= "  <td align=center>$strUmur&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  $intTotalData = $intRows;
  return $strResult;
} // getDataBirthday
// fungsi mengambil data informasi karyawan, jika ada user
function getEmployeeInfo($db)
{
  global $arrUserInfo;
  global $arrDataEmployee;
  if ($arrUserInfo['employee_id'] != "") {
    $arrDataEmployee['employee_id'] = $arrUserInfo['employee_id'];
    $arrDataEmployee['employee_name'] = $arrUserInfo['employee_name'];
    $strSQL = "SELECT employee_status FROM hrd_employee ";
    $strSQL .= "WHERE employee_id = '" . $arrUserInfo['employee_id'] . "' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $arrDataEmployee['employee_status'] = $rowTmp['employee_status'];
    }
    /*
          $arrCuti = getEmployeeLeaveQuota($db, $arrUserInfo['id_employee']);
          $intLeaveQuotaPrev = $arrCuti['prevQuota'];
          $intLeaveQuotaCurr = $arrCuti['currQuota'];
          if ($intLeaveQuotaPrev == 0) {
            $intLeaveTakenPrev = 0; // anggap aja gak ada
            $intLeaveHolidayPrev = 0; //
          } else {
            $intLeaveTakenPrev = $arrCuti['prevTaken'];
            $intLeaveHolidayPrev = $arrCuti['prevHoliday'];
          }
          if ($intLeaveQuotaCurr == 0) {
            $intLeaveTakenCurr = 0; // anggap aja gak ada
            $intLeaveHolidayCurr = 0; //
          } else {
            $intLeaveTakenCurr = $arrCuti['currTaken'];
            $intLeaveHolidayCurr = $arrCuti['currHoliday'];
          }
          $intLeaveRemain = $intLeaveQuotaPrev + $intLeaveQuotaCurr;
          $intLeaveRemain -= ($intLeaveTakenCurr + $intLeaveTakenPrev);
          $intLeaveRemain -= ($intLeaveHolidayCurr + $intLeaveHolidayPrev);
          */
    $arrDataEmployee['leaveQuota'] = getEmployeeLeaveRemain($db, $arrUserInfo['id_employee']);
    $arrMedical = getEmployeeMedicalQuota($db, $arrUserInfo['id_employee']);
    $arrDataEmployee['medicalQuota'] = $arrMedical['outpatient'];
  }
}

// fungsi untuk mengambil informasi alert
function getAlert($db)
{
  global $arrUserInfo;
  global $_SESSION;
  $strClass = "bgNewRevised";
  $strResult = "<table width=100 border=0 cellspacing=0 cellpadding=1>\n";
  // cek apakah ada perubahan data PEGAWAI ---
  $strLink = "javascript:goAlert('employee_search.php')";
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) { //. cek apakah ada yang flagnya 1/3
    $strLink = "javascript:goAlert('employee_search.php',1)";
    $strSQL = "SELECT count(id) AS total FROM hrd_employee WHERE flag=1 ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;modified employee data : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
    $strLink = "javascript:goAlert('employee_search.php',3)";
    $strSQL = "SELECT count(id) AS total FROM hrd_employee WHERE flag=3 ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;DENIED employee data : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) { //. cek apakah ada yang flagnya 1
    $strLink = "javascript:goAlert('employee_search.php',2)";
    $strSQL = "SELECT count(id) AS total FROM hrd_employee WHERE flag=2 ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Employee data need approval : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  //------ END of Employee
  // ---- cek informasi data ATTENDANCE -------
  $strLink = "javascript:goAlert('attendance_list.php')";
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah baru
    $strLink = "javascript:goAlert('attendance_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_attendance WHERE status = " . REQUEST_STATUS_NEW . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Attendance Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
    // cek yang statusnya udah verified
    $strLink = "javascript:goAlert('attendance_list.php'," . REQUEST_STATUS_VERIFIED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_attendance WHERE status = " . REQUEST_STATUS_VERIFIED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Verified Attendance Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah checked
    $strLink = "javascript:goAlert('attendance_list.php'," . REQUEST_STATUS_CHECKED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_attendance WHERE status = " . REQUEST_STATUS_CHECKED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Attendance Request Need Approval : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  } else if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
    // cari request baru yang ada di bawah departmentnnya
    // cek yang statusnya udah new
    $strLink = "javascript:goAlert('attendance_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_attendance AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.status = " . REQUEST_STATUS_NEW . " ";
    $strSQL .= "AND t2.department_code = '" . $arrUserInfo['department_code'] . "' ";
    if ($arrUserInfo['isGroupHead']) {
      $strSQL .= "AND t2.section_code = '" . $arrUserInfo['section_code'] . "' ";
    }
    $strSQL .= "AND t1.id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Attendance Request (Need Approval) : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  // ---- END of Attendance
  // ---- cek informasi data ABSENCE -------
  $strLink = "javascript:goAlert('absence_list.php')";
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah baru
    $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_absence WHERE status = " . REQUEST_STATUS_NEW . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Absence Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
    // cek yang statusnya udah verified
    $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_VERIFIED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_absence WHERE status = " . REQUEST_STATUS_VERIFIED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Verified Absence Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah checked
    $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_CHECKED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_absence WHERE status = " . REQUEST_STATUS_CHECKED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Absence Request Need Approval : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  } else if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
    // cari request baru yang ada di bawah departmentnnya
    // cek yang statusnya udah new
    $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_absence AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.status = " . REQUEST_STATUS_NEW . " ";
    $strSQL .= "AND t2.department_code = '" . $arrUserInfo['department_code'] . "' ";
    if ($arrUserInfo['isGroupHead']) {
      $strSQL .= "AND t2.section_code = '" . $arrUserInfo['section_code'] . "' ";
    }
    $strSQL .= "AND t1.id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Absence Request (Need Approval) : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  // ---- END of Absence
  // ---- cek informasi data LEAVE
  $strLink = "javascript:goAlert('leave_list.php')";
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah baru
    $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_absence_detail AS t0 ";
    $strSQL = "INNER JOIN hrd_absence AS t1 ON t0.id_absence = t1.id ";
    $strSQL = "WHERE status = " . REQUEST_STATUS_NEW . " AND t1.is_leave = TRUE";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Leave Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
    // cek yang statusnya udah verified
    $strLink = "javascript:goAlert('leave_list.php'," . REQUEST_STATUS_VERIFIED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_absence_detail AS t0 ";
    $strSQL = "INNER JOIN hrd_absence AS t1 ON t0.id_absence = t1.id ";
    $strSQL = "WHERE status = " . REQUEST_STATUS_VERIFIED . " AND t1.is_leave = TRUE";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Verified Leave Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah checked
    $strLink = "javascript:goAlert('leave_list.php'," . REQUEST_STATUS_CHECKED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_absence_detail AS t0 ";
    $strSQL = "INNER JOIN hrd_absence AS t1 ON t0.id_absence = t1.id ";
    $strSQL = "WHERE status = " . REQUEST_STATUS_CHECKED . " AND t1.is_leave = TRUE";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Leave Request Need Approval : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  } else if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
    // cari request baru yang ada di bawah departmentnnya
    // cek yang statusnya udah new
    $strLink = "javascript:goAlert('leave_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_absence_detail AS t0 ";
    $strSQL = "INNER JOIN hrd_absence AS t1 ON t0.id_absence = t1.id ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t0.id_employee = t2.id ";
    $strSQL .= "WHERE t0.status = " . REQUEST_STATUS_NEW . " AND t1.is_leave = TRUE";
    $strSQL .= "AND t2.department_code = '" . $arrUserInfo['department_code'] . "' ";
    if ($arrUserInfo['isGroupHead']) {
      $strSQL .= "AND t2.section_code = '" . $arrUserInfo['section_code'] . "' ";
    }
    $strSQL .= "AND t1.id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Leave Request (Need Approval) : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  // ---- END of Leave
  // ----- cek pengajuan Lembur
  $strLink = "javascript:goAlert('overtime_list.php')";
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah baru
    $strLink = "javascript:goAlert('overtime_list.php',0)";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_overtime WHERE status = 0 ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Overtime Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
    /*
          // cek yang statusnya udah verified
          $strLink = "javascript:goAlert('overtime_list.php',1)";
          $strSQL  = "SELECT COUNT(id) AS total FROM hrd_overtime WHERE status = 1 ";
          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
              $strResult .= " <tr valign=top class=$strClass>\n";
              $strResult .= "  <td align=left nowrap>&nbsp;Verified Overtime Request : </td>\n";
              $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
              $strResult .= " </tr>\n";
            }
          }
          */
  } else if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah checked
    /*
        $strLink = "javascript:goAlert('overtime_list.php',3)";
        $strSQL  = "SELECT COUNT(id) AS total FROM hrd_overtime WHERE status = 3 ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
          if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
            $strResult .= " <tr valign=top class=$strClass>\n";
            $strResult .= "  <td align=left nowrap>&nbsp;Overtime Request Need Approval : </td>\n";
            $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
            $strResult .= " </tr>\n";
          }
        }
        */
  } else if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
    // cari request baru yang ada di bawah departmentnnya
    // cek yang statusnya udah new
    $strLink = "javascript:goAlert('overtime_list.php',0)";
    $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_overtime AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.status = 0 ";
    if ($arrUserInfo['isGroupHead'] && $arrUserInfo['section_code'] != '') {
      $strSQL .= "AND (t1.\"managerID\" = '" . $arrUserInfo['id_employee'] . "' ";
      $strSQL .= "OR t2.section_code = '" . $arrUserInfo['section_code'] . "' )";
    } else if ($arrUserInfo['isDeptHead'] && $arrUserInfo['department_code'] != '') {
      $strSQL .= "AND (t1.\"managerID\" = '" . $arrUserInfo['id_employee'] . "' ";
      $strSQL .= "OR t2.department_code = '" . $arrUserInfo['department_code'] . "' )";
    } else {
      $strSQL .= "AND t1.\"managerID\" = '" . $arrUserInfo['id_employee'] . "' ";
    }
    $strSQL .= "AND t1.id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
    //$strSQL .= "AND t1.\"managerID\" = '" .$arrUserInfo['id_employee']."' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Overtime Request (Need Approval) : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  //-- aktualisasi lembur
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah baru
    $strLink = "javascript:goAlert('overtime_list.php',3)";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_overtime WHERE status = 3 ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Overtime Actual Report : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
    // cek yang statusnya udah verified
    $strLink = "javascript:goAlert('overtime_list.php',5)";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_overtime WHERE status = 5 ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Verified Overtime Actual Report : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah checked
    $strLink = "javascript:goAlert('overtime_list.php',6)";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_overtime WHERE status = 6 ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Overtime Actual Report Need Approval : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  } else if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
    // cari request baru yang ada di bawah departmentnnya
    // cek yang statusnya udah new
    $strLink = "javascript:goAlert('overtime_list.php',3)";
    $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_overtime AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.status = 3 ";
    $strSQL .= "AND t1.\"managerID\" = '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND t1.id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Overtime Actual Report (Need Approval) : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  if ($arrUserInfo['isDeptHead']) {
    // cari request baru yang ada di bawah departmentnnya
    // cek yang statusnya udah new
    $strLink = "javascript:goAlert('overtime_list.php',4)";
    $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_overtime AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.status = 4 ";
    $strSQL .= "AND t1.\"managerID\" = '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND t1.id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Verified Overtime Actual Report (Need Approval) : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  // ---- end of Lembur
  // ---- cek pengajuan Medis
  $strLink = "javascript:goAlert('medical_list.php')";
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah baru
    $strLink = "javascript:goAlert('medical_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_medical_claim_master WHERE status = " . REQUEST_STATUS_NEW . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Medical Claim Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
    // cek yang statusnya udah verified
    $strLink = "javascript:goAlert('medical_list.php'," . REQUEST_STATUS_VERIFIED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_medical_claim_master WHERE status = " . REQUEST_STATUS_VERIFIED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Verified Medical Claim Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah checked
    $strLink = "javascript:goAlert('medical_list.php'," . REQUEST_STATUS_CHECKED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_medical_claim_master WHERE status = " . REQUEST_STATUS_CHECKED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Medical Claim Request Need Approval : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  /* -- ATASAN gak perlu
      if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
        // cari request baru yang ada di bawah departmentnnya
        // cek yang statusnya udah new
        $strLink = "javascript:goAlert('medical_list.php',".REQUEST_STATUS_NEW.")";
        $strSQL  = "SELECT COUNT(t1.id) AS total FROM hrd_medical_claim_master AS t1 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
        $strSQL .= "WHERE t1.status = " .REQUEST_STATUS_NEW. " ";
        $strSQL .= "AND t2.department_code = '" .$arrUserInfo['department_code']."' ";
        if ($arrUserInfo['isGroupHead']) {
          $strSQL .= "AND t2.section_code = '" .$arrUserInfo['section_code']."' ";
        }
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
          if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
            $strResult .= " <tr valign=top class=$strClass>\n";
            $strResult .= "  <td align=left nowrap>&nbsp;New Medical Claim Request (Need Approval) : </td>\n";
            $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
            $strResult .= " </tr>\n";
          }
        }
      }
  */
  // ---- END of pengajuan medis
  // ----- cek informasi data TIRAS
  $strLink = "javascript:goAlert('tiras_list.php')";
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah baru
    $strLink = "javascript:goAlert('tiras_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_tiras_master WHERE status = " . REQUEST_STATUS_NEW . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New \"Tiras\" Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
    // cek yang statusnya udah verified
    $strLink = "javascript:goAlert('tiras_list.php'," . REQUEST_STATUS_VERIFIED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_tiras_master WHERE status = " . REQUEST_STATUS_VERIFIED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Verified \"Tiras\" Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah checked
    $strLink = "javascript:goAlert('tiras_list.php'," . REQUEST_STATUS_CHECKED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_tiras_master WHERE status = " . REQUEST_STATUS_CHECKED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;\"Tiras\" Request Need Approval : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  /* atasan gak perlu
      if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
        $strLink = "javascript:goAlert('tiras_list.php',".REQUEST_STATUS_NEW.")";
        // cari request baru yang ada di bawah departmentnnya
        // cek yang statusnya udah new
        $strSQL  = "SELECT COUNT(t1.id) AS total FROM hrd_tiras_master AS t1 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
        $strSQL .= "WHERE t1.status = " .REQUEST_STATUS_NEW. " ";
        $strSQL .= "AND t2.department_code = '" .$arrUserInfo['department_code']."' ";
        if ($arrUserInfo['isGroupHead']) {
          $strSQL .= "AND t2.section_code = '" .$arrUserInfo['section_code']."' ";
        }
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
          if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
            $strResult .= " <tr valign=top class=$strClass>\n";
            $strResult .= "  <td align=left nowrap>&nbsp;New \"Tiras\" Request (Need Approval) : </td>\n";
            $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
            $strResult .= " </tr>\n";
          }
        }
      }
  */
  // ---- end of TIRAS
  // --- cek pengajuan Perjalanan Dinas
  $strLink = "javascript:goAlert('trip_list.php')";
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    $strLink = "javascript:goAlert('trip_list.php'," . REQUEST_STATUS_NEW . ")";
    // cek yang statusnya udah baru
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_trip WHERE status = " . REQUEST_STATUS_NEW . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Business Trip Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
    // cek yang statusnya udah verified
    $strLink = "javascript:goAlert('trip_list.php'," . REQUEST_STATUS_VERIFIED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_trip WHERE status = " . REQUEST_STATUS_VERIFIED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Verified Business Trip Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah checked
    $strLink = "javascript:goAlert('trip_list.php'," . REQUEST_STATUS_CHECKED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_trip WHERE status = " . REQUEST_STATUS_CHECKED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Business Trip Request Need Approval : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  } else if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
    $strLink = "javascript:goAlert('trip_list.php'," . REQUEST_STATUS_NEW . ")";
    // cari request baru yang ada di bawah departmentnnya
    // cek yang statusnya udah new
    $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_trip AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.status = " . REQUEST_STATUS_NEW . " ";
    $strSQL .= "AND t2.department_code = '" . $arrUserInfo['department_code'] . "' ";
    if ($arrUserInfo['isGroupHead']) {
      $strSQL .= "AND t2.section_code = '" . $arrUserInfo['section_code'] . "' ";
    }
    $strSQL .= "AND t1.id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Business Trip Request (Need Approval) : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  // --- end of Perjalanan Dinas
  // -- cek Permintaan Karyawan baru
  $strLink = "javascript:goAlert('recruitment_list.php')";
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah baru
    $strLink = "javascript:goAlert('recruitment_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_recruitment_need WHERE status = " . REQUEST_STATUS_NEW . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Recruitment Need Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\"><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
    // cek yang statusnya udah verified
    $strLink = "javascript:goAlert('recruitment_list.php'," . REQUEST_STATUS_VERIFIED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_recruitment_need WHERE status = " . REQUEST_STATUS_VERIFIED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Verified Recruitment Need Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah checked
    $strLink = "javascript:goAlert('recruitment_list.php'," . REQUEST_STATUS_CHECKED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_recruitment_need WHERE status = " . REQUEST_STATUS_CHECKED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Recruitment Need Request Need Approval : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  /*
  if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
    // cari request baru yang ada di bawah departmentnnya
    // cek yang statusnya udah new
    $strSQL  = "SELECT COUNT(t1.id) AS total FROM hrd_recruitment_need AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.status = " .REQUEST_STATUS_NEW. " ";
    $strSQL .= "AND t2.department_code = '" .$arrUserInfo['department_code']."' ";
    if ($arrUserInfo['isGroupHead']) {
      $strSQL .= "AND t2.section_code = '" .$arrUserInfo['section_code']."' ";
    }
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Recruitment Need Request (Need Approval) : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  */
  // --- end of Permintaan Karyawan
  // cek TRAINING PLAN yang belum dibuat request-nya
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN || $arrUserInfo['isDeptHead']) {
    $strLink = "javascript:goAlert('training_plan_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(id1) AS total FROM  ";
    $strSQL .= "(SELECT id AS id1 FROM hrd_training_plan ";
    $strSQL .= "WHERE  ((expected_date > CURRENT_DATE AND (expected_date - interval '1 months') < CURRENT_DATE) OR (expected_date < CURRENT_DATE)) ";
    if ($arrUserInfo['isDeptHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
      $strSQL .= "AND department_code = '" . $arrUserInfo['department_code'] . "' ";
    }
    $strSQL .= "EXCEPT ";
    $strSQL .= "SELECT DISTINCT id_plan AS id1 FROM hrd_training_request ";
    $strSQL .= "WHERE EXTRACT(year FROM request_date) = '" . date("Y") . "' ";
    if ($arrUserInfo['isDeptHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
      $strSQL .= "AND department_code = '" . $arrUserInfo['department_code'] . "' ";
    }
    $strSQL .= ") AS tbl ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Unrequested Training Plan : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  // --- Permintaan Training ---
  $strLink = "javascript:goAlert('training_request_list.php')";
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah baru
    $strLink = "javascript:goAlert('training_request_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_training_request WHERE status = " . REQUEST_STATUS_NEW . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Training Need Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
    // cek yang statusnya udah verified
    $strLink = "javascript:goAlert('training_request_list.php'," . REQUEST_STATUS_VERIFIED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_training_request WHERE status = " . REQUEST_STATUS_VERIFIED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Verified Training Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    // cek yang statusnya udah checked
    $strLink = "javascript:goAlert('training_request_list.php'," . REQUEST_STATUS_CHECKED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_training_request WHERE status = " . REQUEST_STATUS_CHECKED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Training Request Need Approval : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  } else if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
    // cari request baru yang ada di bawah departmentnnya
    // cek yang statusnya udah new
    $strLink = "javascript:goAlert('training_request_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_training_request AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.status = " . REQUEST_STATUS_NEW . " ";
    $strSQL .= "AND t2.department_code = '" . $arrUserInfo['department_code'] . "' ";
    if ($arrUserInfo['isGroupHead']) {
      $strSQL .= "AND t2.section_code = '" . $arrUserInfo['section_code'] . "' ";
    }
    $strSQL .= "AND t1.id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Training Request (Need Approval) : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  // --  end of Permintaan Training
  // --- Evaluasi
  // --- end Of Evaluasi
  // PERMITNAAAN KENDARAAN
  $strLink = "../ga/transportList.php";
  if ($arrUserInfo['isGroupHead'] || $arrUserInfo['isDeptHead']) {
    // cek yang statusnya udah baru
    $strSQL = "SELECT COUNT(t1.id) AS total, t1.status FROM ga_transport_request AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.status = 0 AND t1.id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND t2.department_code = '" . $arrUserInfo['department_code'] . "' ";
    if ($arrUserInfo['isGroupHead']) {
      $strSQL .= "AND t2.section_code = '" . $arrUserInfo['section_code'] . "' ";
    }
    $strSQL .= "GROUP BY t1.status ORDER BY t1.status ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strLink = "../ga/transportList.php?btnShowAlert=Show&dataStatus=" . $rowDb['status'];
        if ($rowDb['status'] == REQUEST_STATUS_NEW) {
          $strText = "New Transport Request";
        }
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;$strText : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  // -- end kendaraan ----
  /*

      // cek apakah ada request terkait denga REQUEST ORDER, permintaan barang
      $strSisfo = getSetting("sisfo_code");
      if ($arrUserInfo['isGroupHead'] && $strSisfo == $arrUserInfo['section_code']) {
        // cek yang statusnya yg baru atau yg dah dicek, yang terkait IT
        $strSQL  = "SELECT COUNT(t1.id) AS total FROM \"gaRequestOrderDetail\" AS t1, ";
        $strSQL .= "\"gaRequestOrder\" AS t2  WHERE t1.id_request = t2.id ";
        $strSQL .= "AND t1.status = " .GA_REQUEST_STATUS_CHECKED." ";
        $strSQL .= "AND t2.it = 't' ";
        //$strSQL .= "GROUP BY status ORDER BY status ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
          if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
            $strLink = "../ga/roList.php?btnShowAlert=Show&dataStatus=".GA_REQUEST_STATUS_CHECKED;
             $strText = "Request Order Need Verification";

            $strResult .= " <tr valign=top class=$strClass>\n";
            $strResult .= "  <td align=left nowrap>&nbsp;$strText : </td>\n";
            $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
            $strResult .= " </tr>\n";
          }
        }
      }
  */
  $strResult .= "</table>\n";
  return $strResult;
}

// fungsi untuk mengambil informasi alert
function getAlertActivity($db)
{
  global $arrUserInfo;
  global $_SESSION;
  $strClass = "bgNewRevised";
  $strResult = "";
  $strResult = "<table width=100 border=0 cellspacing=0 cellpadding=1>\n";
  // --- Evaluasi
  // --- end Of Evaluasi
  // ---  AMBIL DATA PENGAJUAN DARI KARYAWAN YANG LOGIN, UNTUK TAHU STATUSNYA
  // --- YANG DIAMBIL ADALAH PENGAJUAN 14 HARI KE BELAKANG DAN YANG BELUM
  $intMaxDays = 14; // batas data yang diambil
  global $ARRAY_REQUEST_STATUS;
  global $ARRAY_OT_STATUS;
  $strResult .= " <tr valign=top>\n";
  $strResult .= "  <td align=left nowrap>&nbsp;</td>\n";
  $strResult .= " </tr>\n";
  $strResult .= " <tr valign=top>\n";
  $strResult .= "  <td align=left nowrap style=\"text-decoration:underline; \">&nbsp;<strong>Employee Activity &nbsp;</strong></td>\n";
  $strResult .= " </tr>\n";
  if ($arrUserInfo['id_employee'] != "") {
    // ambil info apakah perubahan employee-nya ada yang ditolak
    $strSQL = "SELECT id FROM hrd_employee WHERE flag=3 ";
    $strSQL .= "AND link_id = " . $arrUserInfo['id_employee'] . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strLink = "employee_edit.php?dataID=" . $rowDb['id'];
      $strResult .= " <tr valign=top class=$strClass>\n";
      $strResult .= "  <td align=left nowrap>&nbsp;DENIED employee data changes </td>\n";
      $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">&nbsp;" . getWords(
              "view"
          ) . "</a></strong></td>\n";
      $strResult .= " </tr>\n";
    }
    // -- ambil data pengubahan data kehadiran
    $strSQL = "SELECT * FROM hrd_attendance WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND ( ((CURRENT_DATE - attendance_date) < $intMaxDays) ";
    $strSQL .= "OR (CURRENT_DATE <= attendance_date)) ";
    $strSQL .= "AND (change_start is not null OR change_finish is not null) ";
    $strSQL .= "ORDER BY attendance_date DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $strClass = getCssClass($rowDb['status']);
      $strTeks = "Attendance Request: <a href=\"attendance_edit_employee.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
              $rowDb['attendance_date'],
              "d-M-y"
          ) . "</a> &nbsp; &raquo; <strong>";
      $strTeks .= getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]);
      $strTeks .= "</strong>";
      $strResult .= " <tr valign=top class=$strClass>\n";
      $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
      $strResult .= " </tr>\n";
    }
    // -- ambil data ijin absen
    $strSQL = "SELECT * FROM hrd_absence WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND ( ((CURRENT_DATE - request_date) < $intMaxDays) ";
    $strSQL .= "OR (CURRENT_DATE <= request_date)) ";
    $strSQL .= "ORDER BY request_date DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $strClass = getCssClass($rowDb['status']);
      $strTeks = "Absence Request: <a href=\"absence_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
              $rowDb['request_date'],
              "d-M-y"
          ) . "</a> &nbsp; &raquo; <strong>";
      $strTeks .= getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]);
      if ($rowDb['leave_year'] != "") {
        $strTeks .= " (as LEAVE) ";
      }
      $strTeks .= "</strong>";
      $strResult .= " <tr valign=top class=$strClass>\n";
      $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
      $strResult .= " </tr>\n";
    }
    // -- ambil data ijin cuti
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_absence_detail AS t0 ";
    $strSQL = "INNER JOIN hrd_absence AS t1 ON t0.id_absence = t1.id ";
    $strSQL = "WHERE t0.id_employee = '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND ( ((CURRENT_DATE - absence_date) < $intMaxDays) ";
    $strSQL .= "OR (CURRENT_DATE <= absence_date)) ";
    $strSQL .= "ORDER BY absence_date DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $strClass = getCssClass($rowDb['status']);
      $strTeks = "Leave Request: <a href=\"leave_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
              $rowDb['request_date'],
              "d-M-y"
          ) . "</a> &raquo; <strong>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "</strong>";
      $strResult .= " <tr valign=top class=$strClass>\n";
      $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
      $strResult .= " </tr>\n";
    }
    // -- ambil data permohonan lembur
    $strSQL = "SELECT * FROM hrd_overtime WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND ( ((CURRENT_DATE - overtime_date) < $intMaxDays) ";
    $strSQL .= "OR (CURRENT_DATE <= overtime_date)) ";
    $strSQL .= "AND status < 3 "; // masih request
    $strSQL .= "ORDER BY overtime_date DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      //$strClass = getCssClass($rowDb['status']);
      $strClass = "";
      switch ($rowDb['status']) {
        case 0 :
          $strClass = "bgNewData";
          $intStatus = 0;
          break;
        case 1 :
          $strClass = "bgVerifiedData";
          $intStatus = 1;
          break;
        case 2 :
          $strClass = "bgDeniedData";
          $intStatus = 2;
          break;
        default :
          break;
      }
      $strTeks = "Overtime Plan: <a href=\"overtime_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
              $rowDb['overtime_date], "d-M-y"). "</a> &raquo; <strong>". getWords($ARRAY_OT_STATUS[$intStatus])."</strong>";
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
        $strResult .= " </tr>\n";

      }

      // -- ambil data realisasi lembur
      $strSQL  = "SELECT * FROM hrd_overtime WHERE id_employee = '" .$arrUserInfo['id_employee']."' ";
      $strSQL .= "AND ( ((CURRENT_DATE - overtime_date) < $intMaxDays) ";
      $strSQL .= "OR (CURRENT_DATE <= overtime_date)) ";
      $strSQL .= "AND status > 2 "; // masih request
      $strSQL .= "ORDER BY overtime_date DESC ";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) {
        //$strClass = getCssClass($rowDb['status']);
        $strClass = "";
        switch ($rowDb['status']) {
          case 3 : $strClass = "bgNewData";break;
          case 4 : $strClass = "bgVerifiedData";break;
          case 5 : $strClass = "bgVerifiedData";break;
          case 6 : $strClass = "bgCheckedData";break;
          case 7 : $strClass = "bgApprovedData";break;
          case 8 : $strClass = "bgDeniedData";break;
          default : break;
        }
        $intStatus = $rowDb['status'];

        $strTeks = "Overtime Realisation: <a href=\"overtime_edit.php?dataID=" .$rowDb['id']."\">". pgDateFormat($rowDb['overtime_date], "d-M-y"). "</a> &raquo; <strong>" . getWords(
          $ARRAY_OT_STATUS[$intStatus]
      ) . "</strong>";
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
        $strResult .= " </tr>\n";

      }
    // -- ambil data pengajuan medis
    $strSQL = "SELECT t2.id, t1.claim_date, t2.status FROM hrd_medical_claim AS t1, hrd_medical_claim_master AS t2  ";
    $strSQL .= "WHERE t1.id_master = t2.id ";
    $strSQL .= "AND t2.id_employee = '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND ( ((CURRENT_DATE - t1.claim_date) < $intMaxDays) ";
    $strSQL .= "OR (CURRENT_DATE <= t1.claim_date)) ";
    $strSQL .= "--GROUP BY t1.claim_date, t2.status  ";
    $strSQL .= "ORDER BY t1.claim_date DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $strClass = getCssClass($rowDb['status']);
      $strTeks = "Medical Request: <a href=\"medical_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
              $rowDb['claim_date'],
              "d-M-y"
          ) . "</a> &raquo; <strong>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "</strong>";
      $strResult .= " <tr valign=top class=$strClass>\n";
      $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
      $strResult .= " </tr>\n";
    }
    // -- ambil data pengajuan tiras
    $strSQL = "SELECT * FROM hrd_tiras_master WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND ( ((CURRENT_DATE - request_date) < $intMaxDays) ";
    $strSQL .= "OR (CURRENT_DATE <= request_date)) ";
    $strSQL .= "ORDER BY request_date DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $strClass = getCssClass($rowDb['status']);
      $strTeks = "'Tiras' Request: <a href=\"tiras_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
              $rowDb['request_date'],
              "d-M-y"
          ) . "</a> &raquo; <strong>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "</strong>";
      $strResult .= " <tr valign=top class=$strClass>\n";
      $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
      $strResult .= " </tr>\n";
    }
    // -- ambil data perjalanan dinas
    $strSQL = "SELECT * FROM hrd_trip WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND ( ((CURRENT_DATE - proposal_date) < $intMaxDays) ";
    $strSQL .= "OR (CURRENT_DATE <= proposal_date)) ";
    $strSQL .= "ORDER BY proposal_date DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $strClass = getCssClass($rowDb['status']);
      $strTeks = "Business Trip Request: <a href=\"trip_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
              $rowDb['proposal_date'],
              "d-M-y"
          ) . "</a> &raquo; <strong>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "</strong>";
      $strResult .= " <tr valign=top class=$strClass>\n";
      $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
      $strResult .= " </tr>\n";
    }
    // -- ambil data permohonan training
    $strSQL = "SELECT * FROM hrd_training_request WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND ( ((CURRENT_DATE - request_date) < $intMaxDays) ";
    $strSQL .= "OR (CURRENT_DATE <= request_date)) ";
    $strSQL .= "ORDER BY request_date DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $strClass = getCssClass($rowDb['status']);
      $strTeks = "Training Request: <a href=\"training_request_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
              $rowDb['request_date'],
              "d-M-y"
          ) . "</a> &raquo; <strong>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "</strong>";
      $strResult .= " <tr valign=top class=$strClass>\n";
      $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
      $strResult .= " </tr>\n";
    }
    // -- ambil data keikutsertaan training, yang udah approve
    // -- yang belum terjadi
    $strSQL = "SELECT t2.training_date FROM hrd_training_request_participant AS t1, hrd_training_request AS t2  ";
    $strSQL .= "WHERE t1.id_request = t2.id AND t2.status >= '" . REQUEST_STATUS_APPROVED . "' ";
    $strSQL .= "AND t1.id_employee = '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND t2.training_status  = 0 ";
    $strSQL .= "AND CURRENT_DATE <= t2.training_date ";
    $strSQL .= "ORDER BY t2.training_date DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $strTeks = "Training Participation : " . pgDateFormat(
              $rowDb['training_date'],
              "d-M-y"
          ) . " &raquo; ";//<strong>". getWords($ARRAY_REQUEST_STATUS[$rowDb['status']])."</strong>";
      $strResult .= " <tr valign=top >\n";
      $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
      $strResult .= " </tr>\n";
    }
    // cari training yang belum sempat dibuat evaluasinya
    $strSQL = "
          SELECT t2.id as idx, t2.training_date, t1.id
          FROM hrd_training_request_participant AS t1, hrd_training_request AS t2
          WHERE t1.id_request = t2.id AND t2.status >= '" . REQUEST_STATUS_APPROVED . "'
            AND t1.id_employee = '" . $arrUserInfo['id_employee'] . "'
            AND t2.training_status  = 0
            AND CURRENT_DATE > t2.training_date
            AND EXTRACT(year FROM t2.training_date) = '" . date("Y") . "'
          EXCEPT
          SELECT DISTINCT t1.id_request AS idx, t2.training_date, t3.id
          FROM hrd_training_evaluation AS t1, hrd_training_request AS t2, hrd_training_request_participant AS t3
          WHERE t1.id_request = t2.id AND t2.id = t3.id_request
            AND t1.id_employee = " . $arrUserInfo['id_employee'] . "
            AND EXTRACT(year FROM t2.training_date) = '" . date("Y") . "'
      ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $strTeks = "Training Need Evaluation : <a href=\"training_evaluation_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
              $rowDb['training_date'],
              "d-M-y"
          ) . "</a> &raquo; ";//<strong>". getWords($ARRAY_REQUEST_STATUS[$rowDb['status']])."</strong>";
      $strResult .= " <tr valign=top >\n";
      $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
      $strResult .= " </tr>\n";
    }
    // -- ambil data permintaan kendaraan
    $strSQL = "SELECT * FROM ga_transport_request WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND ( ((CURRENT_DATE - request_date) < $intMaxDays) ";
    $strSQL .= "OR (CURRENT_DATE <= request_date)) ";
    $strSQL .= "ORDER BY request_date DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $strClass = getCssClass($rowDb['status']);
      $strTeks = "Transport Request: " . pgDateFormat($rowDb['request_date'], "d-M-y") . " &raquo; <strong>";
      $strTeks .= getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]);
      if ($rowDb['vehicleStatus'] == 1) {
        $strTeks .= " -- " . getWords("available");
      } else if ($rowDb['vehicleStatus'] == 2) {
        $strTeks .= " -- " . getWords("not available");
      }
      $strTeks .= "</strong>";
      $strResult .= " <tr valign=top class=$strClass>\n";
      $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
      $strResult .= " </tr>\n";
    }
    // -- ambil data permintaan ruang rapat
    $strSQL = "SELECT * FROM ga_meeting_room_request WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' ";
    $strSQL .= "AND ( ((CURRENT_DATE - request_date) < $intMaxDays) ";
    $strSQL .= "OR (CURRENT_DATE <= request_date)) ";
    $strSQL .= "ORDER BY request_date DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $strClass = getCssClass($rowDb['status']);
      $strTeks = "Meeting Room Request: " . pgDateFormat($rowDb['request_date'], "d-M-y") . " &raquo; <strong>";
      $strTeks .= getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]);
      $strTeks .= "</strong>";
      $strResult .= " <tr valign=top class=$strClass>\n";
      $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
      $strResult .= " </tr>\n";
    }
  }
  // --- END, pengambilan data pengajuan apa aja oleh karyawan
  $strResult .= "</table>\n";
  return $strResult;
} //getAlertActivity
// fungsi untuk menentukan kelas CSS yagn akan digunakan, sesuai status request
/*function getCssClass($intStatus = 0) {
  $strResult = "";
  switch ($intStatus) {
    case 0: $strResult = "bgNewData";break;
    case 1: $strResult = "bgVerifiedData";break;
    case 2: $strResult = "bgCheckedData";break;
    case 3: $strResult = "bgApprovedData";break;
    case 4: $strResult = "bgDeniedData";break;
    default : $strResult = "";break;
  }
  return $strResult;
}*///getCssClass
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
//--- Ambil Data Yang Dikirim ----
$dtNow = getdate();
(isset($_REQUEST['dataYear'])) ? $strDataYear = $_REQUEST['dataYear'] : $strDataYear = $dtNow['year'];
if (!is_numeric($strDataYear)) {
  $strDataYear = $dtNow['year'];
}
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  // jika sudah diambil, tunggu sampai 15 menit berikutnya
  /*
  if (isset($_SESSION['sessionLastAttendanceUpdate'])) {
    $selisih = $dtNow[0] - $_SESSION['sessionLastAttendanceUpdate'];
    if ($selisih > 960) { // jika lewat dari 15 menit, baru diambil
      getAttendanceData($db);
      $_SESSION['sessionLastAttendanceUpdate'] = $dtNow[0];
    }
  } else  {
    getAttendanceData($db);
    $_SESSION['sessionLastAttendanceUpdate'] = $dtNow[0];
  }
  */
  if ($bolCanView) {
    $strDepartment = $arrUserInfo['department_code'] . " - " . $arrUserInfo['section_code'];
    $strPosition = $arrUserInfo['position_code'] . "";
    $strDetailAlert = "";
    if ($_SESSION['sessionUserRole'] != ROLE_EMPLOYEE) {
      $strDetailAlert .= getAlert($db);
    } else {
      // asumsi employee
      if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
        $strDetailAlert .= getAlert($db);
      }
    }
    $strDetailAlert .= "<br>" . getAlertActivity($db);
    $strDetailBirthday = getDataBirthday($db);
    /*
    if ($_SESSION['sessionUserRole'] == ROLE_ADMIN || $_SESSION['sessionUserRole'] == ROLE_SUPERVISOR)
      $strDetailContract = getDataContract($db);
    else $strDetailContract = ""; */
    $strDetailContract = getDataContract($db);
    getEmployeeInfo($db);
    if (isset($arrDataEmployee['employee_id'])) {
      $stremployee_id = $arrDataEmployee['employee_id'];
      $stremployee_name = $arrDataEmployee['employee_name'];
      $strEmployeeStatus = getWords($ARRAY_EMPLOYEE_STATUS[$arrDataEmployee['employee_status']]);
      $strLeave = $arrDataEmployee['leaveQuota'];
      $strMedical = standardFormat($arrDataEmployee['medicalQuota']);
    }
    //writeLog(ACTIVITY_VIEW, MODULE_PAYROLL,"",0);
  } else {
    showError("view_denied");
  }
}
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>