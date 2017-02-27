<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=leave_list.php");
    exit();
}
$bolCanView = getUserPermission("leave_list.php", $bolCanEdit, $bolCanDelete, $strError, true);
$strTemplateFile = getTemplate("leave_denied.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strButtonList = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db)
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $ARRAY_LEAVE_TYPE;
    global $ARRAY_REQUEST_STATUS;
    global $intTotalData;
    $intRows = 0;
    $strResult = "";
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $strSQL = "SELECT t1.*, t2.id AS idemployee, t2.employee_id, t2.employee_name,  ";
            //$strSQL .= "t2.position_code, t2.gender, t2.department_code, t2.section_code, ";
            //$strSQL .= "t2.employee_status, ";
            $strSQL .= "(t1.date_thru - t1.date_from) AS durasi ";
            $strSQL .= "FROM hrd_leave AS t1, hrd_employee AS t2 ";
            $strSQL .= "WHERE t1.id_employee = t2.id AND t1.id = '$strValue' AND status < '" . REQUEST_STATUS_APPROVED . "' "; // yang udah disetujui gak boleh di tolak
            $resDb = $db->execute($strSQL);
            if ($rowDb = $db->fetchrow($resDb)) {
                $intRows++;
                $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
                $strNomor = $rowDb['no'] . "/" . $rowDb['code'] . "/" . $rowDb['month_code'] . "/" . $rowDb['year_code'];
                /*if ($rowDb['status'] == 0) { //new
                  $strClass = "class=bgNewRevised";
                } else if ($rowDb['status'] == 4) {
                  $strClass = "class=bgDenied";
                } else {
                  $strClass = "";
                }*/
                $strClass = getCssClass($rowDb['status']);
                $strClass = " class='$strClass' ";
                // cari selisih hari
                $intDuration = totalWorkDay($db, $rowDb['date_from'], $rowDb['date_thru']);
                $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" $strClass>\n";
                $strResult .= "  <td align=center><input type=hidden name='chkID$intRows' value=\"" . $rowDb['id'] . "\">$intRows.&nbsp;</td>\n";
                $strResult .= "  <td>" . pgDateFormat($rowDb['request_date'], "d-M-y") . "&nbsp;</td>\n";
                $strResult .= "  <td nowrap>" . $strNomor . "&nbsp;</td>";
                $strResult .= "  <td>" . pgDateFormat($rowDb['date_from'], "d-M-y") . "&nbsp;</td>\n";
                $strResult .= "  <td>" . pgDateFormat($rowDb['date_thru'], "d-M-y") . "&nbsp;</td>\n";
                $strResult .= "  <td>" . $rowDb['employee_id'] . "&nbsp;</td>";
                $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . $words[$ARRAY_LEAVE_TYPE[$rowDb['leaveTypeCode']]] . "&nbsp;</td>";
                $strResult .= "  <td align=right>" . $intDuration . "&nbsp;</td>";
                $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
                $strResult .= "  <td>" . $words[$ARRAY_REQUEST_STATUS[$rowDb['status']]] . "&nbsp;</td>";
                $strResult .= "</tr>\n";
                // tambahkan info alasan penolakan
                $strResult .= "<tr valign=top>\n";
                $strResult .= "  <td >&nbsp;</td>\n";
                $strResult .= "  <td colspan=10><strong>" . $words['reason'] . "&nbsp; : </strong><input type=text size=100 maxlength=240 name=detailNote$intRows value=\"" . $rowDb['note_denied'] . "\" class='string'></td>\n";
                $strResult .= "</tr>\n";
            }
        }
    }
    if ($intRows > 0) {
        $intTotalData = $intRows;
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$intRows data", 0);
    }
    return $strResult;
} // showData
// fungsi untuk mengubah status data
function changeStatusData($db)
{
    global $_REQUEST;
    global $_SESSION;
    $strmodified_by = $_SESSION['sessionUserID'];
    $intTotal = (isset($_REQUEST['totalData'])) ? $_REQUEST['totalData'] : 0;
    $intStatus = REQUEST_STATUS_DENIED;
    for ($i = 1; $i <= $intTotal; $i++) {
        if (isset($_REQUEST['chkID' . $i])) {
            $id = $_REQUEST['chkID' . $i];
            $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
            $strSQL = "UPDATE hrd_leave SET status = $intStatus, note_denied = '$strNote', ";
            $strSQL .= "denied_by = '$strmodified_by', denied_time = now() ";
            $strSQL .= "WHERE id = '$id' ";
            //$strSQL .= "AND status < $intStatus "; // hanya yang lebih bawah yang bisa diubah statusnya
            $resExec = $db->execute($strSQL);
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data", 0);
    }
    header("location:leave_list.php");
    exit();
} //changeStatusData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $bolIsEmployee = isUserEmployee();
    if ($bolCanEdit) {
        if (isset($_REQUEST['btnSave'])) {
            changeStatusData($db);
        }
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    $strKriteria = "";
    $strReadonly = "";
    $strDisabled = "";
    if ($bolIsEmployee) {
        header("location:leave_list.php");
        exit();
    }
    // tambahkan button sesuai peran
    if ($bolCanView) {
        $strDataDetail = getData($db);
    } else {
        header("location:leave_list.php");
        exit();
    }
    // informasi tanggal kehadiran
    /*
    if ($strDataDateFrom == $strDataDateThru) {
      $strInfo .= "<br>".strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
    } else {
      $strInfo .= "<br>".strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
      $strInfo .= " >> ".strtoupper(pgDateFormat($strDataDateThru, "d-M-Y"));
    }
    */
}
$strInitAction .= "
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>