<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=history_employee_status.php");
    exit();
}
$bolCanView = getUserPermission("history_employee_status.php", $bolCanEdit, $bolCanDelete, $strError);
$strTemplateFile = getTemplate("history_employee_status.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
$strInitCalendar = "";
$strDataEmployee = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataID, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $intDefaultWidth;
    global $strInitCalendar;
    global $strEmptyOption;
    $intRows = 0;
    $intShown = 0;
    $intAdd = 10; // maksimum tambahan
    $strResult = "";
    $strNow = date("Y-m-d");
    if ($strDataID != "") {
        $strSQL = "SELECT * FROM hrd_employee_status_history ";
        $strSQL .= "WHERE id_employee = '$strDataID' ";
        $strSQL .= "ORDER BY $strOrder active_date ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $intRows++;
            $intShown++;
            $strResult .= "<tr valign=top id=\"detailRows$intRows\">\n";
            $strResult .= "  <td nowrap><input type=hidden name='detailID$intRows' value=\"" . $rowDb['id'] . "\">\n";
            $strResult .= "  " . getEmployeeStatusList("detailStatus$intRows", $rowDb['statusCode']) . "</td>";
            $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailDate$intRows id=\"detailDate$intRows\" value=\"" . $rowDb['activeDate'] . "\">&nbsp;";
            $strResult .= "<input type=button id=\"target_$intRows\" value='..'></td>";
            $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailDuration$intRows id=\"detailDuration$intRows\" value=\"" . $rowDb['duration'] . "\"></td>";
            $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailDueDate$intRows id=\"detailDueDate$intRows\" value=\"" . $rowDb['due_date'] . "\" disabled>&nbsp;";
            $strResult .= "<input type=button id=\"target_due_$intRows\" value='..'></td>";
            $strResult .= "  <td nowrap><input type=text size=20 maxlength=50 name=detailNote$intRows value=\"" . $rowDb['note'] . "\"></td>";
            $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
            $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\" $strAction></td>\n";
            $strResult .= "</tr>\n";
            $strInitCalendar .= "Calendar.setup({ inputField:\"detailDate$intRows\", button:\"target_$intRows\" });\n";
            $strInitCalendar .= "Calendar.setup({ inputField:\"detailDueDate$intRows\", button:\"target_due_$intRows\" });\n";
        }
        if ($intRows > 0) {
            writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$strDataID", 0);
        }
        // tambahkan dengan data kosong
        for ($i = 1; $i <= $intAdd; $i++) {
            $intRows++;
            if ($intRows == 1) {
                $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
                $intShown++;
                $strDisabled = "";
            } else {
                $strResult .= "<tr valign=top  id=\"detailRows$intRows\" style=\"display:none\">\n";
                $strDisabled = "disabled";
            }
            $strResult .= "  <td nowrap>" . getEmployeeStatusList(
                    "detailStatus$intRows",
                    "",
                    $strEmptyOption,
                    $strDisabled
                ) . "</td>";
            $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailDate$intRows id=\"detailDate$intRows\" value=$strNow $strDisabled>&nbsp;";
            $strResult .= "<input type=button id=\"target_$intRows\" value='..'></td>";
            $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailDuration$intRows id=\"detailDuration$intRows\" value=0 $strDisabled></td>";
            $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailDueDate$intRows id=\"detailDueDate$intRows\" value=$strNow $strDisabled>&nbsp;";
            $strResult .= "<input type=button id=\"target_due_$intRows\" value='..'></td>";
            $strResult .= "  <td nowrap><input type=text size=20 maxlength=50 name=detailNote$intRows  $strDisabled></td>";
            $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
            $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\" $strAction></td>\n";
            $strResult .= "</tr>\n";
            $strInitCalendar .= "Calendar.setup({ inputField:\"detailDate$intRows\", button:\"target_$intRows\" });\n";
            $strInitCalendar .= "Calendar.setup({ inputField:\"detailDueDate$intRows\", button:\"target_due_$intRows\" });\n";
        }
    }
    // tambahkan hidden data
    $strResult .= "<input type=hidden name=maxDetail value=$intRows>";
    $strResult .= "<input type=hidden name=numShow value=$intShown>";
    return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, $strDataID, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $error;
    $strError = "";
    if ($strDataID == "") {
        return false;
    }
    (isset($_REQUEST['maxDetail'])) ? $intMax = $_REQUEST['maxDetail'] : $intMax = 0;
    for ($i = 1; $i <= $intMax; $i++) {
        (isset($_REQUEST['detailID' . $i])) ? $strID = $_REQUEST['detailID' . $i] : $strID = "";
        (isset($_REQUEST['detailDate' . $i])) ? $strDate = "'" . $_REQUEST['detailDate' . $i] . "'" : $strDate = "";
        //(isset($_REQUEST['detailDueDate'.$i])) ? $strDueDate = "'" .$_REQUEST['detailDueDate'.$i]. "'" : $strDueDate = "";
        (isset($_REQUEST['detailDuration' . $i])) ? $strDuration = $_REQUEST['detailDuration' . $i] : $strDuration = 0;
        (isset($_REQUEST['detailStatus' . $i])) ? $strStatus = $_REQUEST['detailStatus' . $i] : $strStatus = "";
        (isset($_REQUEST['detailNote' . $i])) ? $strNote = $_REQUEST['detailNote' . $i] : $strNote = "";
        $bolEmpty = true;
        $bolEmpty = ($bolEmpty && ($strStatus == ""));
        if (!is_numeric($strDuration)) {
            $strDuration = 0;
        }
        if ($strDate == "" || $strDate == "''") {
            $strDate = "NULL";
            $strDueDate = "NULL";
        } else {
            $strDueDate = " (date ( date $strDate + INTERVAL '$strDuration months') - 1) ";
            if ($strDuration == 0 && $strStatus == 2) {
                $strDueDate = "NULL";
            }
        }
        if ($strID == "") {
            if (!$bolEmpty) { // insert new data
                $strSQL = "INSERT INTO hrd_employee_status_history (created,modified_by, created_by, ";
                $strSQL .= "id_employee, status_code,  note, active_date, due_date, duration) ";
                $strSQL .= "VALUES(now(), '" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
                $strSQL .= "'$strDataID', '$strStatus', '$strNote', $strDate, $strDueDate, '$strDuration') ";
                $resDb = $db->execute($strSQL);
            }
        } else {
            if ($bolEmpty) {
                // delete data
                $strSQL = "DELETE FROM hrd_employee_status_history WHERE id = '$strID' ";
                $resDb = $db->execute($strSQL);
            } else {
                // update data
                $strSQL = "UPDATE hrd_employee_status_history SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
                $strSQL .= "created = now(), status_code = '$strStatus', duration = '$strDuration', ";
                $strSQL .= "active_date = $strDate, due_date = $strDueDate,  ";
                $strSQL .= "note = '$strNote' WHERE id = '$strID' ";
                $resDb = $db->execute($strSQL);
            }
        }
    }
    // cari data department terbaru, update data
    $strSQL = "SELECT * FROM hrd_employee_status_history ";
    $strSQL .= "WHERE id_employee = '$strDataID' ORDER BY active_date DESC ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        $strStatus = $rowDb['statusCode'];
        if ($strStatus == 0) {
            $strJoindate = "'" . $rowDb['activeDate'] . "'";
            $strDuedate = "'" . $rowDb['due_date'] . "'";
        } else { // permanent
            $strJoindate = "'" . $rowDb['activeDate'] . "'";
            $strDuedate = "NULL";
        }
    } else {
        $strStatus = "";
        $strJoindate = "join_date";
        $strDuedate = "due_date";
    }
    // update data employee
    $strSQL = "UPDATE hrd_employee SET employee_status = '$strStatus', ";
    $strSQL .= "join_date = $strJoindate, due_date = $strDuedate ";
    $strSQL .= "WHERE id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataID", 0);
    return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
    if (isset($_REQUEST['btnSave'])) {
        if ($bolCanEdit && $strDataID != "") {
            if (!saveData($db, $strDataID, $strError)) {
                echo "<script>alert(\"$strError\")</script>";
            }
        }
    }
    // cari dta perusahaan dulu
    if ($strDataID != "" || $strDataEmployee != "") {
        $strSQL = "SELECT * FROM hrd_employee WHERE 1=1 ";
        if ($strDataID != "") {
            $strSQL .= "AND id = '$strDataID' ";
        } else if ($strDataEmployee != "") {
            $strSQL .= "AND employee_id = '$strDataEmployee' AND flag = 0 ";
        }
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strDataEmployee = $rowDb['employee_id'];
            $strDataID = $rowDb['id'];
        } else {
            $strDataID = "";
        }
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $strDataID, $intTotalData);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
}
$strInitAction .= $strInitCalendar . "
		init();
		document.formFilter.dataEmployee.focus();
		onCodeBlur();
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>