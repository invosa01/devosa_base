<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  header("location:login.php?dataPage=recruitment_list.php");
  exit();
}
$bolCanView = getUserPermission("recruitment_list.php", $bolCanEdit, $bolCanDelete, $strError, true);
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
if ($bolPrint) {
  $strMainTemplate = getTemplate("recruitment_plan_print.html");
} else {
  $strTemplateFile = getTemplate("recruitment_plan_list.html");
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strButtons = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataYear, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $ARRAY_REQUEST_STATUS;
  global $ARRAY_MARITAL_STATUS;
  $intRows = 0;
  $strResult = "";
  // cari data request yang terkait dengan plan, untuk tahu plan mana yang dah ada requestnya
  $arrRequest = [];
  $strSQL = "SELECT id, id_plan FROM hrd_recruitment_need ";
  $strSQL .= "WHERE id_plan is not null ";
  $strSQL .= "AND EXTRACT(year FROM due_date) = '$strDataYear' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrRequest[$rowDb['id_plan']] = $rowDb['id'];
  }
  // ambil dulu data employee, kumpulkan dalam array
  $i = 0;
  $strSQL = "SELECT t1.*, t2.department_name FROM hrd_recruitment_plan AS t1 ";
  $strSQL .= "LEFT JOIN hrd_department AS t2 ON t1.department_code = t2.department_code ";
  $strSQL .= "WHERE 1=1 $strKriteria AND \"year\" = '$strDataYear' ";
  $strSQL .= "ORDER BY $strOrder t1.due_date, t1.department_code ";
  $resDb = $db->execute($strSQL);
  $strDateOld = "";
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    if ($rowDb['gender'] == 0) {
      $strGender = $words['female'];
    } else if ($rowDb['gender'] == 1) {
      $strGender = $words['male'];
    } else {
      $strGender = "";
    }
    /*
    if ($rowDb['status'] == '0') {
      $strClass = "bgNewRevised";
    } else if ($rowDb['status'] == '3') {
      $strClass = "bgDenied";
    } else {
      $strClass = "";
    }
    */
    $strClass = getCssClass($rowDb['status']);
    // cari daftar candidate
    $strCandidateList = "";
    $strSQL = "SELECT * FROM hrd_candidate WHERE position = '" . $rowDb['position'] . "' ";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      if ($strCandidateList != "") {
        $strCandidateList .= "<BR>\n";
      }
      $strCandidateList .= "<a href=\"candidate_edit.php?dataID=" . $rowTmp['id'] . "\">";
      $strCandidateList .= $rowTmp['candidate_name'] . "</a>";
      if ($rowTmp['status'] == 4) {
        $strCandidateList .= " [&radic;]";
      }
    }
    $strMaritalStatus = ($rowDb['marital_status'] == 1 || $rowDb['marital_status'] == 0) ? $words[$ARRAY_MARITAL_STATUS[$rowDb['marital_status']]] : "";
    //$strEmployeeStatus = (in_array($rowDb['employee_status'], $ARRAY_EMPLOYEE_STATUS)) ? $words[$ARRAY_EMPLOYEE_STATUS[$rowDb['employee_status']]] : "";
    $strEmployeeStatus = getWords($ARRAY_EMPLOYEE_STATUS[$rowDb['employee_status']]);
    $strEmployeeInfo = $rowDb['department_code'] . " - " . $rowDb['department_name'];
    $strCreateRequest = ($rowDb['status'] == REQUEST_STATUS_DENIED) ? "&nbsp;" : "<button type=button nama=btnCreate$intRows onClick=\"location.href = 'recruitment_edit.php?btnCreate=Create&idPlan=" . $rowDb['id'] . "'\">" . getWords(
            "create request"
        ) . "</button>";
    $strIsRequest = (isset($arrRequest[$rowDb['id']])) ? " (*) " : "";
    $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
    $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
    $strResult .= "  <td align=center>" . $rowDb['year'] . "&nbsp;</td>\n";
    $strResult .= "  <td>" . $rowDb['department_code'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap><a href=\"recruitment_plan_edit.php?dataID=" . $rowDb['id'] . "\">" . $rowDb['position'] . "</a>$strIsRequest&nbsp;</td>";
    $strResult .= "  <td align=center>" . $strEmployeeStatus . "&nbsp;</td>";
    $strResult .= "  <td align=right>" . $rowDb['number'] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . pgDateFormat($rowDb['due_date'], "d-M-y") . "&nbsp;</td>\n";
    $strResult .= "  <td>" . $rowDb['description'] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $rowDb['min_age'] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $rowDb['max_age'] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $strGender . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $strMaritalStatus . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $rowDb['education_level'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['education'] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $rowDb['work_experience'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['qualification'] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "&nbsp;</td>";
    $strResult .= "  <td nowrap>$strCandidateList&nbsp;</td>"; // dadftar kandidate
    $strResult .= "  <td align=center>$strCreateRequest</td>";
    $strResult .= "  <td align=center><a href=\"recruitment_plan_edit.php?dataID=" . $rowDb['id'] . "\">" . $words['edit'] . "</a>&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  return $strResult;
} // showData
// fungsi untuk menghapus data
function deleteData($db)
{
  global $_REQUEST;
  $i = 0;
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 5) == 'chkID') {
      $i++;
      $strSQL = "DELETE FROM hrd_recruitment_plan WHERE id = '$strValue' ";
      $resExec = $db->execute($strSQL);
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
  }
} //deleteData
// fungsi untuk menghapus data
function changeStatus($db, $intStatus)
{
  global $_REQUEST;
  global $_SESSION;
  if (!is_numeric($intStatus)) {
    return false;
  }
  if ($_SESSION['sessionUserRole'] != ROLE_SUPERVISOR && $_SESSION['sessionUserRole'] != ROLE_ADMIN) {
    return false;
  }
  // tambahan info
  if ($intStatus == REQUEST_STATUS_CHECKED) {
    $strUpdate = "checked_by = '" . $_SESSION['sessionUserID'] . "', checked_time = now(), ";
  } else if ($intStatus == REQUEST_STATUS_APPROVED) {
    $strUpdate = "approved_by = '" . $_SESSION['sessionUserID'] . "', approved_time = now(), ";
  } else if ($intStatus == REQUEST_STATUS_DENIED) {
    $strUpdate = "denied_by = '" . $_SESSION['sessionUserID'] . "', denied_time = now(), ";
  }
  $i = 0;
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 5) == 'chkID') {
      $i++;
      $strSQL = "UPDATE hrd_recruitment_plan SET $strUpdate status = '$intStatus'  ";
      $strSQL .= "WHERE id = '$strValue' ";
      $resExec = $db->execute($strSQL);
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data", 0);
  }
} //changeStatus
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  // cek permission -- khusus employee
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    if (!$arrUserInfo['isDeptHead'] && !$arrUserInfo['isGroupHead']) {
      $bolCanDelete = $bolCanEdit = $bolCanView = false;
    }
  }
  // hapus data jika ada perintah
  if (isset($_POST['btnDelete'])) {
    if ($bolCanDelete) {
      deleteData($db);
    }
  } else if (isset($_POST['btnChecked'])) {
    if ($bolCanEdit) {
      changeStatus($db, REQUEST_STATUS_CHECKED);
    }
  } else if (isset($_POST['btnApproved'])) {
    if ($bolCanEdit) {
      changeStatus($db, REQUEST_STATUS_APPROVED);
    }
  } else if (isset($_POST['btnDenied'])) {
    if ($bolCanEdit) {
      changeStatus($db, REQUEST_STATUS_DENIED);
    }
  }
  // ------ AMBIL DATA KRITERIA -------------------------
  //getDefaultSalaryPeriode($strDefaultFrom,$strDefaultThru);
  $strCurrYear = date("Y");
  (isset($_REQUEST['dataYear'])) ? $strDataYear = $_REQUEST['dataYear'] : $strDataYear = $strCurrYear;
  (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
  //(isset($_REQUEST['dataStatus'])) ? $strDataStatus = $_REQUEST['dataStatus'] : $strDataStatus = "";
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strDataDepartment = $arrUserInfo['department_code'];
  }
  if ($strDataDepartment != "") {
    $strKriteria .= "AND t1.department_code = '$strDataDepartment' ";
  }
  if ($bolCanView) {
    $strDataDetail = getData($db, $strDataYear, $intTotalData, $strKriteria);
    if (isset($_REQUEST['btnExcel'])) {
      // ambil data CSS-nya
      if (file_exists("bw.css")) {
        $strStyle = "bw.css";
      }
      $strPrintCss = "";
      $strPrintInit = "";
      headeringExcel("recruitmentPlan.xls");
    }
  } else {
    showError("view_denied");
  }
  $strDeptKriteria = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) ? "WHERE department_code = '" . $arrUserInfo['department_code'] . "' " : "";
  $intDefaultWidthPx = 200;
  $strFilterYear = getYearList("dataYear", $strDataYear);
  $strFilterDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $strDataDepartment,
      $strEmptyOption,
      "$strDeptKriteria",
      "style=\"width:$intDefaultWidthPx\" "
  );
  //$strFilterStatus = getRecruitmentNeedStatusList("dataStatus",$strDataStatus,$strEmptyOption,"style=\"width:$intDefaultWidthPx\"");
  // tampilkan tombol sesuai hak akses
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
    $strButtons .= "&nbsp;<input type=submit name=btnChecked value=\"" . $words['checked'] . "\" onClick=\"return confirmStatusChanges(false)\">";
    $strButtons .= "&nbsp;<input type=submit name=btnDenied value=\"" . $words['denied'] . "\" onClick=\"return confirmStatusChanges(true)\">";
    $strButtons .= "&nbsp;<input type=submit name=btnDelete value=\"" . $words['delete'] . "\" onClick=\"return confirmDelete()\">";
  } else if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    $strButtons .= "&nbsp;<input type=submit name=btnChecked value=\"" . $words['checked'] . "\" onClick=\"return confirmStatusChanges(false)\">";
    $strButtons .= "&nbsp;<input type=submit name=btnApproved value=\"" . $words['approved'] . "\" onClick=\"return confirmStatusChanges(false)\">";
    $strButtons .= "&nbsp;<input type=submit name=btnDenied value=\"" . $words['denied'] . "\" onClick=\"return confirmStatusChanges(true)\">";
    $strButtons .= "&nbsp;<input type=submit name=btnDelete value=\"" . $words['delete'] . "\" onClick=\"return confirmDelete()\">";
  } else if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
    //$strButtons .= "&nbsp;<input type=submit name=btnVerified value=\"" .$words['verified']. "\" onClick=\"return confirmStatusChanges(false)\">";
    //$strButtons .= "&nbsp;<input type=submit name=btnDenied value=\"" .$words['denied']. "\" onClick=\"return confirmStatusChanges(true)\">";
    $strButtons .= "&nbsp;<input type=submit name=btnDelete value=\"" . $words['delete'] . "\" onClick=\"return confirmDelete()\">";
  }
  // informasi tanggal kehadiran
  $strInfo .= $strDataYear;
  $strHidden .= "<input type=hidden name=dataYear value=\"$strDataYear\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  //$strHidden .= "<input type=hidden name=dataStatus value=\"$strDataStatus\">";
}
$strInitAction .= "    document.formInput.dataYear.focus();   ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>