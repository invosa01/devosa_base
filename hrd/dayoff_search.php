<?php
include_once('../global/session.php');
include_once('../global/cls_date.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
//$dataPrivilege = getDataPrivileges("overtime_application_edit.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintApproved']) || isset($_REQUEST['btnExcel']));
$bolPrintReport = (isset($_REQUEST['btnPrintReport']));
//---- INISIALISASI ----------------------------------------------------
$strWordsOvertimeApplication = getWords("overtime application");
$strWordsApplicationList = getWords("overtime application list");
$strWordsOvertimeReport = getWords("overtime report");
$strWordsDataEntry = getWords("data entry");
$strWordsDayOffList = getWords("list of dayoff");
$strWordsEmployeeDayOff = getWords("employee dayoff");
$strWordsOvertimeDate = getWords("overtime date");
$strWordsDateFrom = getWords("date from");
$strWordsDateTo = getWords("date thru");
$strWordsStatus = getWords("status");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("subsection");
$strWordsGroup = getWords("group");
$strWordsEmployee = getWords("employee");
$strWordsEmployeeID = getWords("employee id");
$strWordsEmployeeName = getWords("employee name");
//$strWordsCompany      = getWords("company");
//$strWordsDepartment   = getWords("department");
//$strWordsArea         = getWords("area");
$strWordsApprove = getWords("approve");
$strWordsAttendance = getWords("attendance");
$strWordsNote = getWords("note");
$strWordsDelete = getWords("delete");
$strWordsShow = getWords("show data");
$strWordsType = getWords("type");
$strWordsStart = getWords("start");
$strWordsFinish = getWords("finish");
$strWordsl1 = getWords("l1");
$strWordsl2 = getWords("l2");
$strWordsl3 = getWords("l3");
$strWordsl4 = getWords("l4");
$strWordsTotal = getWords("total");
$strWordsID = strtoupper("id");
$strWordsDate = strtoupper("date");
$strWordsDept = getWords("dept.");
$strWordsSect = getWords("sect.");
$strWordsBand = getWords("band");
$strWordsEarlyOT = getWords("early") . " " . strtoupper("ot");
$strWordsAfternoonOT = getWords("afternoon") . " " . strtoupper("ot");
$strWordsOvertime = getWords("overtime");
$strWordsRealization = getWords("realization");
$strWordsPlan = getWords("plan");
$strWordsBreak = getWords("break");
$strWordsMeal = getWords("meal");
$strWordsEarly = getWords("early");
$strDataDetail = "";
$strHidden = "";
$strInputStatus = "";
$intTotalData = 0;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data -- per detail employee
// $db = kelas database, $intRows = jumlah baris (return)
function getDataEmployee($db, $strDataDateFrom, $strDataDateThru, &$intRows)
{
  global $words;
  global $ARRAY_APPLICATION_STATUS;
  global $ARRAY_REQUEST_STATUS;
  global $bolPrint;
  global $arrUserInfo;
  global $arrUserList;
  global $objUP;
  $intRows = 0;
  $strResult = "";
  $strKriteria1 = ""; // kriteria reset ulang, master
  $strKriteria2 = ""; // kriteria reset ulang, detail
  $objDt = new clsCommonDate();
  $bolIsEmployee = $objUP->isUserEmployee();
  $strUserIDEmp = $objUP->getIDEmployee();
  $strDataStatus = getRequestValue("dataStatus");
  $strDataType = getRequestValue("dataType");
  //$strDataCompany = getRequestValue("dataCompany");
  //$strDataWilayah = getRequestValue("dataWilayah");
  // $strDataDepartment = getRequestValue("dataDepartment");
  $strDataEmployee = getRequestValue("dataEmployee");
  $strKriteria1 .= "AND overtime_type = '1' ";
  if ($strDataStatus != "-1" && $strDataStatus !== "") {
    $strKriteria1 .= " AND status = '$strDataStatus' ";
  }
  //if ($strDataType != "-1" && $strDataType !== "")
  //  $strKriteria1 .= "AND overtime_type = '$strDataType' ";
  //if ($strDataCompany != "")
  //$strKriteria2 .= "AND id_company = '$strDataCompany' ";
  //if ($strDataDepartment != "")
  //$strKriteria2 .= "AND department = '$strDataDepartment' ";
  if ($strDataEmployee != "") {
    $strKriteria2 .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE employee_id = '$strDataEmployee') ";
  }
  $strKriteriaEmployee = $objUP->genFilterEmployee();
  //if ($strDataWilayah != "")
  //$strKriteriaEmployee .= "AND id_wilayah = '$strDataWilayah' ";
  // header tabel
  $strResult .= "
      <table cellspacing=0 cellpadding=1 border=0 class='gridTable' width='100%'>
        <tr>
          <th rowspan=2 width='30px'>&nbsp;</th>
          <th rowspan=2>" . getWords("date") . "&nbsp;</th>
          <th rowspan=2 nowrap>" . getWords("employee id") . "&nbsp;</th>
          <th rowspan=2>" . getWords("name") . "&nbsp;</th>
          <th colspan=2 nowrap>" . getWords("early") . "&nbsp; (" . getWords("plan") . ")</th>
          <th colspan=2 nowrap>" . getWords("early") . "&nbsp; (" . getWords("actual") . ")</th>
          <th colspan=2 nowrap>" . getWords("afternoon") . "&nbsp; (" . getWords("actual") . ")</th>
          <th colspan=2 nowrap>" . getWords("afternoon") . "&nbsp; (" . getWords("plan") . ")</th>
          <th colspan=2 nowrap>" . getWords("attendance") . "&nbsp;</th>
          <th rowspan=2>" . getWords("Transport") . "&nbsp;</th>
          <th rowspan=2 nowrap>" . getWords("Transport") . "&nbsp; (" . getWords("Fee") . ")</th>
          <th rowspan=2 >&nbsp;</th>
          <th rowspan=2 >&nbsp;</th>
          <th rowspan=2 >&nbsp;</th>
          <th rowspan=2 >&nbsp;</th>
          <th rowspan=2 >&nbsp;</th>
        </tr>
        <tr>
          <th>" . getWords("start") . "&nbsp;</th>
          <th>" . getWords("finish") . "&nbsp;</th>
          <th>" . getWords("start") . "&nbsp;</th>
          <th>" . getWords("finish") . "&nbsp;</th>
          <th>" . getWords("start") . "&nbsp;</th>
          <th>" . getWords("finish") . "&nbsp;</th>
          <th>" . getWords("start") . "&nbsp;</th>
          <th>" . getWords("finish") . "&nbsp;</th>
          <th>" . getWords("start") . "&nbsp;</th>
          <th>" . getWords("finish") . "&nbsp;</th>
        </tr>
    ";
  // cari jumlah employee
  $strSQL = "
        SELECT t1.*, t2.employee_id,  t2.employee_name,
          t4.attendance_date, t4.attendance_start, 
          t4.attendance_finish, tm.overtime_type, tm.status, 
          tm.checked_by, tm.approved_by
        FROM (
          SELECT tbl1.*
          FROM hrd_overtime_application_employee AS tbl1
          WHERE overtime_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru'
            $strKriteria2
        )  AS t1 
        INNER JOIN (
          SELECT * FROM hrd_overtime_application
          WHERE id IN (
            SELECT DISTINCT id_application FROM hrd_overtime_application_employee
            WHERE overtime_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru'
              $strKriteria2
          ) $strKriteria1          
        ) AS tm ON t1.id_application = tm.id
        INNER JOIN (
          SELECT * FROM hrd_employee
          WHERE active IN (0,1) $strKriteriaEmployee
        ) AS t2 ON t1.id_employee = t2.id 
        LEFT JOIN (
          SELECT * FROM hrd_attendance
          WHERE attendance_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru'
        ) AS t4 ON t1.id_employee = t4.id_employee AND t1.overtime_date = t4.attendance_date
        ORDER BY t1.overtime_date, t2.employee_name
    ";
  $resTmp = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resTmp)) {
    $intRows++;
    $strShowInfo = "";
    if (!$bolPrint) {
      if ($rowDb['status'] != 0) {
        $strDiv = "<div id='detailRecord$intRows' style=\"display:none\">\n";
        $strDiv .= "<strong>" . $rowDb['employee_id'] . "-" . $rowDb['employee_name'] . "</strong><br>\n";
        $strDiv .= getWords("last modified") . ": " . substr($rowDb['modified'], 0, 19) . " ";
        $strDiv .= (isset($arrUserList[$rowDb['modified_by']])) ? $arrUserList[$rowDb['modified_by']]['name'] . "<br>" : "<br>";
        //$strDiv .= getWords("acknowledged"). ": ".substr($rowDb['acknowledge_time'], 0,19) ." ";
        //$strDiv .= (isset($arrUserList[$rowDb['acknowledge_by']])) ? $arrUserList[$rowDb['acknowledge_by']]['name']."<br>" : "<br>";
        $strDiv .= getWords("checked") . ": " . substr($rowDb['checked_time'], 0, 19) . " ";
        $strDiv .= (isset($arrUserList[$rowDb['checked_by']])) ? $arrUserList[$rowDb['checked_by']]['name'] . "<br>" : "<br>";
        $strDiv .= getWords("approved") . ": " . substr($rowDb['approved_time'], 0, 19) . " ";
        $strDiv .= (isset($arrUserList[$rowDb['approved_by']])) ? $arrUserList[$rowDb['approved_by']]['name'] . "<br>" : "<br>";
        $strDiv .= getWords("denied") . ": " . substr($rowDb['denied_time'], 0, 19) . " ";
        $strDiv .= (isset($arrUserList[$rowDb['denied_by']])) ? $arrUserList[$rowDb['denied_by']]['name'] . "<br>" : "<br>";
        $strDiv .= "</div>\n";
        $strShowInfo .= "  <td nowrap align=center><a href=\"javascript:openWindowById('detailRecord$intRows')\" title=\"" . getWords(
                "show record info"
            ) . "\">" . getWords("show") . "$strDiv</a></td>\n";
      } else {
        // tambahkan info record info
        $strDiv = "<div id='detailRecord$intRows' style=\"display:none\">\n";
        $strDiv .= "<strong>" . $rowDb['employee_id'] . "-" . $rowDb['employee_name'] . "</strong><br>\n";
        $strDiv .= getWords("last modified") . ": " . substr($rowDb['modified'], 0, 19) . " ";
        $strDiv .= (isset($arrUserList[$rowDb['modified_by']])) ? $arrUserList[$rowDb['modified_by']]['name'] . "<br>" : "<br>";
        //$strDiv .= getWords("acknowledged"). ": ".substr($rowDb['acknowledge_time'], 0,19) ." ";
        //$strDiv .= (isset($arrUserList[$rowDb['acknowledge_by']])) ? $arrUserList[$rowDb['acknowledge_by']]['name']."<br>" : "<br>";
        $strDiv .= getWords("checked") . ": " . substr($rowDb['checked_time'], 0, 19) . " ";
        $strDiv .= (isset($arrUserList[$rowDb['checked_by']])) ? $arrUserList[$rowDb['checked_by']]['name'] . "<br>" : "<br>";
        $strDiv .= getWords("approved") . ": " . substr($rowDb['approved_time'], 0, 19) . " ";
        $strDiv .= (isset($arrUserList[$rowDb['approved_by']])) ? $arrUserList[$rowDb['approved_by']]['name'] . "<br>" : "<br>";
        $strDiv .= getWords("denied") . ": " . substr($rowDb['denied_time'], 0, 19) . " ";
        $strDiv .= (isset($arrUserList[$rowDb['denied_by']])) ? $arrUserList[$rowDb['denied_by']]['name'] . "<br>" : "<br>";
        $strDiv .= "</div>\n";
        $strShowInfo .= "  <td nowrap align=center><a href=\"javascript:openWindowById('detailRecord$intRows')\" title=\"" . getWords(
                "show record info"
            ) . "\">" . getWords("show") . "$strDiv</a></td>\n";
      }
    }
    $strChkDis = ""; // disabled checkbox atau tidak
    if ($bolIsEmployee && $strUserIDEmp == $rowDb['id_employee']) {
      $strChkDis = " disabled ";
    }
    $strResult .= "
          <tr class=\"" . getApprovalClassStyle($rowDb['status']) . "\">
            <td><input type=checkbox name='chkID$intRows' id='chkID$intRows' value=" . $rowDb['id_application'] . " $strChkDis ></td>
            <td nowrap>" . $objDt->getDateFormat($rowDb['overtime_date'], "d-M-Y") . "</td>
            <td>" . $rowDb['employee_id'] . "</td>
            <td nowrap>" . $rowDb['employee_name'] . "</td>
            <td>" . $objDt->formatStandardTime($rowDb['start_early_plan']) . "</td>
            <td>" . $objDt->formatStandardTime($rowDb['finish_early_plan']) . "</td>
            <td>" . $objDt->formatStandardTime($rowDb['start_plan']) . "</td>
            <td>" . $objDt->formatStandardTime($rowDb['finish_plan']) . "</td>
            <td>" . $objDt->formatStandardTime($rowDb['start_early_actual']) . "</td>
            <td>" . $objDt->formatStandardTime($rowDb['finish_early_actual']) . "</td>
            <td>" . $objDt->formatStandardTime($rowDb['start_actual']) . "</td>
            <td>" . $objDt->formatStandardTime($rowDb['finish_actual']) . "</td>
            <td>" . $objDt->formatStandardTime($rowDb['attendance_start']) . "</td>
            <td>" . $objDt->formatStandardTime($rowDb['attendance_finish']) . "</td>
            <td align=center>&nbsp;" . getWords($rowDb['transport']) . "</td>
            <td align=center>&nbsp;" . getWords($rowDb['transport_fee']) . "</td>
            <td align=center>&nbsp;" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "</td>
            <td align=center>&nbsp;<a href=overtime_application_edit.php?btnPrint=Print&dataID=" . $rowDb['id_application'] . " target='_blank'>" . getWords(
            'print'
        ) . "</a></td>
            <td align=center>&nbsp;<a href=overtime_application_edit.php?dataID=" . $rowDb['id_application'] . ">" . getWords(
            'edit'
        ) . "</a></td>
            <td align=center>&nbsp;" . (($rowDb['overtime_type'] == 1) ? getWords("dayoff") : getWords("overtime")) . "</td>
            $strShowInfo
          </tr>
        ";
  }
  global $objUP;
  global $bolCanDelete;
  $strButtonList = $objUP->generateApprovalButtons();
  if ($bolCanDelete) {
    $strButtonList .= "&nbsp;<input type=submit name=btnDelete value=\"" . getWords(
            'delete'
        ) . "\" onClick=\"return confirmDelete()\">";
  }
  // footer
  $strResult .= "
        <tr>
          <td><input name='chkAll' type='checkbox' id='chkAll' value='" . getWords("all") . "' onClick=\"checkAll();\"> </td>
            <td colspan=22>
              $strButtonList
              <input name='dataStatus' type='hidden' id='dataStatus1' value='" . $strDataStatus . "'>
              <input name='dataType' type='hidden' id='dataType1' value='" . $strDataType . "'>
              <input name='dataDateFrom' type='hidden' id='dataDateFrom1' value='" . $strDataDateFrom . "'>
              <input name='dataDateThru' type='hidden' id='dataDateThru1' value='" . $strDataDateThru . "'>
              <input name='totalData' type='hidden' id='totalData' value='" . $intRows . "'>
              &nbsp; 
            </td>
        </tr>
      </table>
    ";
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$intRows data", 0);
  }
  return $strResult;
} // showData
// fungsi untuk mengambil jenis kelas CSS berdasar status approval
// input  : status
// output : nama kelas CSS sesuai status
function getApprovalClassStyle($intStatus)
{
  $strResult = "";
  if ($intStatus == REQUEST_STATUS_NEW) {
    $strResult = "bgNewData";
  } else if ($intStatus == REQUEST_STATUS_VERIFIED) {
    $strResult = "bgVerifiedData";
  } else if ($intStatus == REQUEST_STATUS_CHECKED) {
    $strResult = "bgCheckedData";
  }
  return $strResult;
}

// fungsi untuk menghapus data
function deleteData($db)
{
  global $_REQUEST;
  $arrProcess = []; // untuk menampung id yang sudah diproses
  $i = 0;
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 5) == 'chkID') {
      if (isset($arrProcess[$strValue])) {
        continue;
      } else {
        $arrProcess[$strValue] = $strValue;
      }
      $strSQL = "DELETE FROM hrd_overtime_application_employee WHERE id_application = '$strValue' ";
      $resExec = $db->execute($strSQL);
      $strSQL = "DELETE FROM hrd_overtime_application WHERE id = '$strValue' ";
      $resExec = $db->execute($strSQL);
      $i++;
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
  }
} //deleteData
//----------------------------------------------------------------------
// fungsi untuk melakukan approval terhadap data
function approvedData($db)
{
  global $_REQUEST;
  global $_SESSION;
  global $arrUserInfo;
  global $arrUserList;
  global $objUP;
  global $bolCanApprove;
  $strUpdater = $_SESSION['sessionUserID'];
  $arrProcess = []; // untuk menyimpan id yang sudah diproses
  if ($objUP->isUserEmployee() || !$bolCanApprove) {
    return false;
  }
  $i = 0;
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 5) == 'chkID') {
      $id = substr($strIndex, 5, strlen($strIndex) - 5);
      if (isset($arrProcess[$id])) {
        continue;
      } else {
        $arrProcess[$id] = $id;
      }
      // lihat dulu statusnya
      $strSQL = "SELECT * FROM hrd_overtime_application WHERE id = '$strValue' ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $intNextStatus = "";
        $strUpdate = "";
        // tentukan siapa yang approved
        /*
        if ($_SESSION['sessionUserRole'] == ROLE_ADMIN)
        { // admmin hrd
          $strUpdate .= " checked_time = now(), ";
          $strUpdate .= " checked_by = '$strUpdater', ";
          $intNextStatus = REQUEST_STATUS_CHECKED;
        } else if ($_SESSION['sessionUserRole'] == ROLE_MANAGER) { // manager hrd
          $strUpdate .= " approved_time = now(), ";
          $strUpdate .= " approved_by = '$strUpdater', ";
          $intNextStatus = REQUEST_STATUS_APPROVED;
        }
        */
        // sudah pasti approve
        $strUpdate .= " approved_time = now(), ";
        $strUpdate .= " approved_by = '$strUpdater', ";
        $intNextStatus = REQUEST_STATUS_APPROVED;
        if ($intNextStatus != "") {
          $strSQL = "UPDATE hrd_overtime_application SET $strUpdate status = '$intNextStatus' WHERE id = '$strValue' ";
          $resExec = $db->execute($strSQL);
          $i++;
        }
        // cek, terkait dengan day off
        if ($rowDb['overtime_type'] == 1 && $rowDb['status'] < REQUEST_STATUS_APPROVED) {
          $strSQL = "UPDATE hrd_overtime_application_employee SET dayoff = 1 WHERE id_application = '$strValue' ";
          $resExec = $db->execute($strSQL);
        }
      }
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data", 0);
  }
} //approvedData
// fungsi untuk mengubah status data, kecuali appoval
function changeStatusData($db, $intStatus)
{
  global $_REQUEST;
  global $_SESSION;
  global $arrUserInfo;
  global $objUP;
  $arrProcess = []; // untuk menampung ID yang sudah diproses
  $strmodified_by = $_SESSION['sessionUserID'];
  $strUpdate = "";
  if ($intStatus == REQUEST_STATUS_VERIFIED) {
    $strUpdate = "acknowledge_time = now(), acknowledge_by = '$strmodified_by', ";
  } else if ($intStatus == REQUEST_STATUS_CHECKED) {
    $strUpdate = "checked_time = now(), checked_by = '$strmodified_by', ";
  } else if ($intStatus == REQUEST_STATUS_DENIED) {
    $strUpdate = "denied_time = now(), denied_by = '$strmodified_by', ";
  }
  $i = 0;
  $bolOK = true;
  $db->execute("begin");
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 5) == 'chkID') {
      if (isset($arrProcess[$strValue])) {
        continue;
      } else {
        $arrProcess[$strValue] = $strValue;
      }
      $intRow = substr($strIndex, 5, strlen($strIndex) - 5);
      if (isset($_REQUEST['dataBlocked' . $intRow]) && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
        // diproses dengan catatan, karena sudah kedaluwarsa
        $strNote = (isset($_REQUEST['dataNote' . $intRow])) ? $_REQUEST['dataNote' . $intRow] : "";
        if ($strNote != "") {
          // simpan data
          $strSQL = "UPDATE hrd_overtime_application SET $strUpdate status = $intStatus, note_denied = '$strNote' ";
          $strSQL .= "WHERE id = '$strValue' AND status <> " . REQUEST_STATUS_DENIED . " "; // yang denied gak boleh
          $resExec = $db->execute($strSQL);
          if ($resExec == false) {
            $bolOK = false;
          }
          $i++;
        }
      } else {
        $strSQL = "UPDATE hrd_overtime_application SET $strUpdate status = $intStatus WHERE id = '$strValue' ";
        if (!$objUP->isManagerHR()) {
          $strSQL .= "AND status < $intStatus "; // hanya yang lebih bawah yang bisa diubah statusnya
        }
        $resExec = $db->execute($strSQL);
        if ($resExec == false) {
          $bolOK = false;
        }
        $i++;
      }
    }
  }
  if ($bolOK) {
    $db->execute("commit");
  } else {
    $db->execute("rollback");
  }
  if ($i > 0) {
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data - change status($intStatus)", 0);
  }
} //changeStatusData
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$strButtonList = "";
$db = new CdbClass;
if ($db->connect()) {
  $arrUserList = getAllUserInfo($db);
  $arrUserInfo = getUserEmployeeInfo($db);
  // hapus data jika ada perintah
  if (isset($_REQUEST['btnDelete'])) {
    if ($bolCanDelete) {
      deleteData($db);
    }
  } else if (isset($_POST['btnApproved'])) {
    if ($bolCanApprove) {
      approvedData($db);
    }
  } else if (isset($_POST['btnVerified'])) {
    changeStatusData($db, REQUEST_STATUS_VERIFIED);
  } else if (isset($_POST['btnChecked'])) {
    changeStatusData($db, REQUEST_STATUS_CHECKED);
  }
  // ------ AMBIL DATA KRITERIA -------------------------
  $strDataDateFrom = date("Y-m-d");
  $strDataDateThru = date("Y-m-d");
  if (isset($_SESSION['sessionDataDateFrom'])) {
    $strDataDateFrom = $_SESSION['sessionDataDateFrom'];
  }
  if (isset($_SESSION['sessionDataDateThru'])) {
    $strDataDateThru = $_SESSION['sessionDataDateThru'];
  }
  if (isset($_REQUEST['dataDateFrom'])) {
    $strDataDateFrom = $_REQUEST['dataDateFrom'];
  }
  if (isset($_REQUEST['dataDateThru'])) {
    $strDataDateThru = $_REQUEST['dataDateThru'];
  }
  (isset($_REQUEST['dataStatus'])) ? $strDataStatus = $_REQUEST['dataStatus'] : $strDataStatus = "";
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
  $_SESSION['sessionDataDateFrom'] = $strDataDateFrom;
  $_SESSION['sessionDataDateThru'] = $strDataDateThru;
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  $strReadonly = "";
  if ($objUP->isUserEmployee() && !$objUP->isRoleSupervisor()) {
    $strDataEmployee = $objUP->getEmployeeID();
    $strReadonly = " readonly ";
  }
  // cek jenis user, jika ternyata bukan admin/manager, hanya bisa lihat section-nya sendri
  $strExtraSection = $strEmptyOption;
  $strTmpKriteria = "WHERE 1=1 ";
  $strKriteria .= "AND t1.overtime_type = '0' ";
  if ($strDataStatus != "") {
    $strKriteria .= "AND t1.status = '$strDataStatus' ";
  }
  if ($bolCanView) {
    if (validStandardDate($strDataDateFrom) && validStandardDate($strDataDateThru)) {
      // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
      $strDataDetail = getDataEmployee($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
    } else {
      $strDataDetail = "";
    }
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  // generate data hidden input dan element form input
  $intDefaultWidthPx = 200;
  $strStartKriteria = " WHERE 1=1 ";
  $strInputDateFrom = "<input type=text name=dataDateFrom id=dataDateFrom size=12 maxlength=10 value=$strDataDateFrom>";
  $strInputDateThru = "<input type=text name=dataDateThru id=dataDateThru size=12 maxlength=10 value=$strDataDateThru>";
  //$strInputCompany = getCompanyList($db, "dataCompany",$strDataCompany, $strEmptyOption2, $strKriteria2, "style=\"width:$intDefaultWidthPx\" ");
  //$strInputArea = getWilayahList($db, "dataWilayah",$strDataWilayah, $strEmptyOption, "", "style=\"width:$intDefaultWidthPx\" ");
  //$strStartKriteria .= $objUP->genFilterDivision();
  //$strStartKriteria .= $objUP->genFilterDepartment();
  //$strInputDepartment = getDepartmentList($db, "dataDepartment", $strDataDepartment, $strEmptyOption, $strStartKriteria, "style=\"width:$intDefaultWidthPx\" ");
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"$strDataEmployee\" $strReadonly >";
  //$strInputStatus = getOvertimeApplicationStatusList("dataStatus",$strDataStatus, $strEmptyOption, "style=width:$intDefaultWidthPx");
  //$arrType = array('-1' => "", '0' => getWords('overtime'), '1' => getWords('day off'));
  //$strInputType = getComboFromArray($arrType, "dataType", $strDataType);
  $arr = [
      "-1"                    => "",
      REQUEST_STATUS_NEW      => getWords($ARRAY_REQUEST_STATUS[REQUEST_STATUS_NEW]),
      REQUEST_STATUS_CHECKED  => getWords($ARRAY_REQUEST_STATUS[REQUEST_STATUS_CHECKED]),
      REQUEST_STATUS_APPROVED => getWords($ARRAY_REQUEST_STATUS[REQUEST_STATUS_APPROVED])
  ];
  $strInputStatus = getComboFromArray($arr, "dataStatus", $strDataStatus, "", "style=\"width:$intDefaultWidthPx\" ");
  // informasi tanggal kehadiran
  if ($strDataDateFrom == $strDataDateThru) {
    $strInfo .= "<br>" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
  } else {
    $strInfo .= "<br>" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
    $strInfo .= " >> " . strtoupper(pgDateFormat($strDataDateThru, "d-M-Y"));
  }
  $strHidden .= "<input type=hidden name=dataDateFrom value=$strDataDateFrom>";
  $strHidden .= "<input type=hidden name=dataDateThru value=$strDataDateThru>";
  $strHidden .= "<input type=hidden name=dataStatus value=$strDataStatus>";
  $strHidden .= "<input type=hidden name=dataEmployee value=$strDataEmployee>";
  $strButtonList .= $objUP->generateApprovalButtons();
  if ($bolCanDelete) {
    $strButtonList .= "&nbsp;<input type=submit name=btnDelete value=\"" . getWords(
            'delete'
        ) . "\" onClick=\"return confirmDelete()\">";
  }
  /*
  if ($_SESSION['sessionUserRole'] == ROLE_ADMIN)
  {
    $strButtonList .= "&nbsp;<input type=submit name='btnApprove' value=" .getWords('checked'). " onClick=return confirmApprove();>";
  } else if ($_SESSION['sessionUserRole'] == ROLE_MANAGER) {
    $strButtonList .= "&nbsp;<input type=submit name='btnApprove' value=" .getWords('approved'). " onClick=return confirmApprove();>";
  }
  */
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
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