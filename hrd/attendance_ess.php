<?php
include_once('../global/session.php');
include_once('global.php');
include_once('attendance_functions.php');
include_once('activity.php');
include_once('overtime_func.php');
//($bolEss) ? ($bolEss = false)  : echo "D";
//if (basename($_SERVER['PHP_SELF']) != basename($_SERVER['HTTP_REFERER'])) redirectPage("attendance_ess.php");
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
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
//---- INISIALISASI ----------------------------------------------------
$strWordsAttendanceEntry = getWords("attendance entry");
$strWordsAttendanceList = getWords("attendance list");
$strWordsOverEntry = getWords("over entry");
$strWordsOverList = getWords("over list");
$strWordsIn = getWords("in");
$strWordsOut = getWords("out");
$strWordsNote = getWords("Note");
$strWordsSave = getWords("save");
$strWordsNo = getWords("no.");
$strDataDetail = "";
$strDataAttendanceStart = "";
$strDataAttendanceFinish = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strIDEmployee, $strDataDate)
{
    global $strDataAttendanceStart;
    global $strDataAttendanceFinish;
    $arrResult = [];
    $objAttendanceClass = new clsAttendanceClass($db);
    $objAttendanceClass->resetAttendance();
    $objAttendanceClass->setFilter(getNextDate($strDataDate, -1), $strDataDate, $strIDEmployee);
    $objAttendanceClass->getAttendanceResource();
    $objToday = new clsAttendanceInfo($db);
    $objToday->newInfo($strIDEmployee, $strDataDate);
    $objToday->initAttendanceInfo($objAttendanceClass);
    $arrResult['attendance_id'] = $objToday->strAttendanceID;
    $arrResult['note'] = $objToday->strNote;
    if ($objToday->strAttendanceStart != "") {
        $arrResult['check_in'] = "checked";
        $arrResult['disable_in'] = "disabled";
        $arrResult['attendance_start'] = $strDataAttendanceStart = $objToday->strAttendanceStart;
    } else {
        $arrResult['check_in'] = "";
        $arrResult['disable_in'] = "";
        $arrResult['attendance_start'] = "";
        $arrResult['disable_out'] = "disabled";
    }
    if ($objToday->strAttendanceFinish != "") {
        $arrResult['check_out'] = "checked";
        $arrResult['disable_out'] = "disabled";
        $arrResult['attendance_finish'] = $strDataAttendanceFinish = $objToday->strAttendanceFinish;
    } else {
        $arrResult['check_out'] = "";
        $arrResult['disable_out'] = "";
        $arrResult['attendance_finish'] = "";
    }
    $arrResult['early'] = $objToday->intEarly;
    if ($arrResult['early'] == 0) {
        $arrResult['selected0'] = "selected";
        $arrResult['selected1'] = "";
    } else {
        $arrResult['selected1'] = "selected";
        $arrResult['selected0'] = "";
    }
    return $arrResult;
} // getData
// fungsi untuk menyimpan data
function saveData($db, &$strError, $strDataDate, $strIDEmployee)
{
    global $_REQUEST;
    global $_SESSION;
    $objAttendanceClass = new clsAttendanceClass($db);
    $objAttendanceClass->resetAttendance();
    $objAttendanceClass->setFilter(getNextDate($strDataDate, -1), $strDataDate, $strIDEmployee);
    $objAttendanceClass->getAttendanceResource();
    $objToday = new clsAttendanceInfo($db);
    $objToday->newInfo($strIDEmployee, $strDataDate);
    $objToday->initAttendanceInfo($objAttendanceClass);
    $strDataAttendanceID = (isset($_REQUEST['dataAttendanceID'])) ? $_REQUEST['dataAttendanceID'] : "";
    $strDataAttendanceStart = (isset($_REQUEST['dataAttendanceStart'])) ? $_REQUEST['dataAttendanceStart'] : "";
    $strDataAttendanceFinish = (isset($_REQUEST['dataAttendanceFinish'])) ? $_REQUEST['dataAttendanceFinish'] : "";
    $strDataNote = (isset($_REQUEST['dataNote2'])) ? $_REQUEST['dataNote2'] : "";
    $bolDataIn = (isset($_REQUEST['dataIn']));
    $bolDataOut = (isset($_REQUEST['dataOut']));
    if ($bolDataIn && $objToday->strAttendanceStart == "") {
        $objToday->strAttendanceStart = getNextMinute(date("H:i"), getTimeDiff($db, $strIDEmployee));
        writeLog(
            ACTIVITY_ADD,
            MODULE_PAYROLL,
            $objAttendanceClass->arrEmployee[$strIDEmployee]['employee_id'] . "-" . $objToday->strAttendanceDate . "-" . $objToday->strAttendanceStart
        );
    }
    if ($bolDataOut && $objToday->strAttendanceFinish == "") {
        $objToday->strAttendanceFinish = getNextMinute(date("H:i"), getTimeDiff($db, $strIDEmployee));
        $objToday->strNote = $strDataNote;
        $objToday->bolYesterday = true;
        writeLog(
            ACTIVITY_ADD,
            MODULE_PAYROLL,
            $objAttendanceClass->arrEmployee[$strIDEmployee]['employee_id'] . "-" . (($objToday->bolYesterday) ? getNextDate(
                $objToday->strAttendanceDate,
                -1
            ) : $objToday->strAttendanceDate) . "-" . $objToday->strAttendanceStart
        );
    }
    $objToday->calculateDuration();
    $objToday->calculateLate();
    $objToday->calculateOvertime();
    $objToday->saveCurrentAttendance($objAttendanceClass, "ess");
    return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
error_reporting(0);
if ($db->connect()) {
    $strEmployeeInfo = "";
    getUserEmployeeInfo();
    if ((!isset($arrUserInfo['employee_id'])) || $arrUserInfo['employee_id'] == 0 || $arrUserInfo['employee_id'] == "") {
        $bolCanView = false;
    } else {
        $strIDEmployee = $arrUserInfo['id_employee'];
        $strDataDate = date("Y-m-d");
        $strEmployeeInfo .= $arrUserInfo['employee_id'] . "&nbsp; - " . strtoupper($arrUserInfo['employee_name']);
    }
    if (isset($_REQUEST['btnSave'])) {
        if ($bolCanEdit) {
            if (!saveData($db, $strError, $strDataDate, $strIDEmployee)) {
                echo "<script>alert(\"$strError\")</script>";
            } else {
                writeLog(ACTIVITY_EDIT, MODULE_EMPLOYEE, $arrUserInfo['employee_name'], 0);
            }
        }
    }
    if ($bolCanView) {
        $arrData = getData($db, $strIDEmployee, $strDataDate);
    } else {
        echo("Your ID is not valid, please relogin");
        die();
    }
    $strTime = getNextMinute(date("H:i:s"), getTimeDiff($db, $strIDEmployee));
    $strInputIn = "<input type='checkbox' name='dataIn' " . $arrData['check_in'] . " " . $arrData['disable_in'] . " >&nbsp;";
    $strInputOut = "<input type='checkbox' name='dataOut'  onClick='setNote(this.checked)' ";
    $strInputOut .= $arrData['check_out'] . " " . $arrData['disable_out'] . " >&nbsp;";
    $strInputNote1 = "<select name='dataNote1' disabled>";
    $strInputNote1 .= "<option value=0 " . $arrData['selected0'] . " >Pulang</option>";
    $strInputNote1 .= "<option value=1 " . $arrData['selected1'] . " >Izin dengan alasan</option></select>";
    $strInputNote2 = "<textarea name='dataNote2' cols='37'>" . $arrData['note'] . "</textarea>";
    $strButton = "<input type='submit' name='btnSave' value='$strWordsSave'>";
    $strHidden = "<input type='hidden' name='dataAttendanceID' value='" . $arrData['attendance_id'] . "'>";
    $strHidden .= "<input type='hidden' name='dataAttendanceStart' value='" . $arrData['attendance_start'] . "'>";
    $strHidden .= "<input type='hidden' name='dataAttendanceFinish' value='" . $arrData['attendance_finish'] . "'>";
    $strHidden .= "<input type='hidden' name='dataEarly' value='" . $arrData['early'] . "'>";
}
error_reporting(E_ALL);
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