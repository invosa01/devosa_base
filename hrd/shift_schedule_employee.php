<?php
session_start();
include_once('global.php');
include_once('calendar.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=shift_schedule_employee.php");
    exit();
}
$bolCanView = getUserPermission("shift_schedule_employee.php", $bolCanEdit, $bolCanDelete, $strError);
if (!$bolCanView) {
    die($strError);
}
$strTemplateFile = getTemplate("shift_schedule_employee.html");
//---- INISIALISASI ----------------------------------------------------
$strMonthNavigation = "";
$strMonthList = "";
$strYearList = "";
$strCalendar = "";
$strLegend = "";
$strNow = date("Y-m-d");
$strHolidayCategory = "";
$stremployee_id = "";
$stremployee_name = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database
// return berubah $arrOvertime (parameter)
function getData($db, $strMonth, $strYear, $strEmployee = "")
{
    global $words;
    $strResult = getMonthlyEmployeeShiftCalendar($db, $strMonth, $strYear, $strEmployee);
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$strMonth - $strYear", 0);
    return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strError, $stremployee_id = "-1")
{
    global $_REQUEST;
    global $_SESSION;
    global $messages;
    global $error;
    (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = "";
    (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = "";
    (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
    (isset($_REQUEST['dataShiftType'])) ? $strDataShift = $_REQUEST['dataShiftType'] : $strDataShift = "";
    (isset($_REQUEST['dataStart'])) ? $strDataStart = $_REQUEST['dataStart'] : $strDataStart = "";
    (isset($_REQUEST['dataFinish'])) ? $strDataFinish = $_REQUEST['dataFinish'] : $strDataFinish = "";
    // ---- cek validasi -----
    $bolOK = true;
    if ($strDataDate == "") {
        $strError = $error['invalid_date'];
        $bolOK = false;
    } else if (!validStandardDate($strDataDate)) {
        $strError = $error['invalid_date'];
        $bolOK = false;
    } else if ($strDataShift == "") {
        $strError = $error['empty_data'];
        $bolOK = false;
    } else if ($stremployee_id == "") {
        $strError = $error['empty_data'];
        $bolOK = false;
    }
    if ($bolOK) {
        // cari apakah udah ada data atau belum
        $intTipe = ($strDataStart < $strDataFinish) ? 1 : 2;
        $strSQL = "SELECT id FROM hrd_shift_schedule_employee WHERE shift_date = '$strDataDate' ";
        $strSQL .= "AND id_employee = '$stremployee_id' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strSQL = "UPDATE hrd_shift_schedule_employee SET created = now(), ";
            $strSQL .= "modified_by = '" . $_SESSION['sessionUserID'] . "', ";
            $strSQL .= "shift_code = '$strDataShift', note = '$strDataNote', ";
            $strSQL .= "\"startTime\" = '$strDataStart', finish_time = '$strDataFinish' ";
            $strSQL .= "WHERE id = " . $rowDb['id'];
            $resExec = $db->execute($strSQL);
            writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$stremployee_id - $strDataDate", 0);
        } else {
            $strSQL = "INSERT INTO hrd_shift_schedule_employee (created, modified_by, created_by, ";
            $strSQL .= "shift_date, shift_code, id_employee, \"startTime\", finish_time, note) ";
            $strSQL .= "VALUES(now(), '" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
            $strSQL .= "'$strDataDate', '$strDataShift', '$stremployee_id', '$strDataStart','$strDataFinish', '$strDataNote') ";
            $resExec = $db->execute($strSQL);
            writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$stremployee_id - $strDataDate", 0);
        }
        //echo $strSQL;
        // update data kehadiran, jika ada
        $strSQL = "UPDATE hrd_attendance SET shift_type = '$intTipe' ";
        $strSQL .= "WHERE id_employee = '$stremployee_id' ";
        $strSQL .= "AND attendance_date = '$strDataDate' ";
        $resExec = $db->execute($strSQL);
    }
    return $bolOK;
} // saveData
// menghapus data
function deleteData($db, $stremployee_id = -1)
{
    global $_REQUEST;
    (isset($_REQUEST['dataDay'])) ? $strDataDay = $_REQUEST['dataDay'] : $strDataDay = "";
    (isset($_REQUEST['dataMonth'])) ? $strDataMonth = $_REQUEST['dataMonth'] : $strDataMonth = "";
    (isset($_REQUEST['dataYear'])) ? $strDataYear = $_REQUEST['dataYear'] : $strDataYear = "";
    $strDataDate = $strDataYear . "-" . $strDataMonth . "-" . $strDataDay;
    if ($strDataDay != "") {
        $strSQL = "DELETE FROM hrd_shift_schedule_employee WHERE shift_date = '$strDataDate' ";
        $strSQL .= "AND id_employee = '$stremployee_id' ";
        $resExec = $db->execute($strSQL);
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$stremployee_id - $strDataDate", 0);
        // handle jika ada kaitannya dengan lembur, dsb -------
        // update data kehadiran, jika ada
        $strSQL = "UPDATE hrd_attendance SET shift_type = 0 ";
        $strSQL .= "WHERE id_employee = '$stremployee_id' ";
        $strSQL .= "AND attendance_date = '$strDataDate' ";
        $resExec = $db->execute($strSQL);
    }
    return true;
}//deleteData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
//--- Ambil Data Yang Dikirim ----
$dtNow = getdate();
(isset($_REQUEST['dataEmployee'])) ? $stremployee_id = $_REQUEST['dataEmployee'] : $stremployee_id = "";
(isset($_REQUEST['dataMonth'])) ? $strDataMonth = $_REQUEST['dataMonth'] : $strDataMonth = $dtNow['mon'];
(isset($_REQUEST['dataYear'])) ? $strDataYear = $_REQUEST['dataYear'] : $strDataYear = $dtNow['year'];
if (!is_numeric($strDataMonth)) {
    $strDataMonth = $dtNow['mon'];
}
if (!is_numeric($strDataYear)) {
    $strDataYear = $dtNow['year'];
}
$db = new CdbClass;
if ($db->connect()) {
    $arrInfo = getEmployeeInfoByCode($db, $stremployee_id, "id,employee_name");
    if (isset($arrInfo['id'])) {
        $strDataID = $arrInfo['id'];
        $stremployee_name = $arrInfo['employee_name'];
    }
    if (isset($_REQUEST['btnSave'])) {
        if ($bolCanEdit) {
            saveData($db, $strError, $strDataID);
            if ($strError != "") {
                echo "<script>alert(\"$strError\")</script>";
            }
        }
    } else if (isset($_REQUEST['btnDelete'])) {
        if ($bolCanDelete) {
            deleteData($db, $strDataID);
        }
    }
    if ($bolCanView) {
        $strCalendar = getData($db, $strDataMonth, $strDataYear, $stremployee_id);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
    // buat legenda
    $strLegend .= "<table cellspacing=0 border=0 width=100% class='gridTable'>\n";
    $strLegend .= "  <tr><td class='bgNationalHoliday' width=20>&nbsp;</td>";
    $strLegend .= "  <td nowrap>" . $words['national holiday'] . "&nbsp;</td></tr>";
    $strLegend .= "  <tr><td class='bgCompanyHoliday' width=20>&nbsp;</td>";
    $strLegend .= "  <td nowrap>" . $words['company holiday'] . "&nbsp;</td></tr>";
    $strLegend .= "  <tr><td class='bgSpecialHoliday' width=20>&nbsp;</td>";
    $strLegend .= "  <td nowrap>" . $words['special holiday'] . "&nbsp;</td></tr>";
    $strLegend .= "  <tr><td class='bgLeaveHoliday' width=20>&nbsp;</td>";
    $strLegend .= "  <td nowrap>" . $words['leave holiday'] . "&nbsp;</td></tr>";
    $strLegend .= "</table>\n";
    // data shift type
    $strShiftType = getShiftTypeList(
        $db,
        "dataShiftType",
        "",
        $strEmptyOption,
        "",
        " onChange=\"shiftTypeChange();\" "
    );
    //$strShiftGroup = getShiftGroupList($db, "dataGroup", "", $strEmptyOption, "", "");
    // ambil array untuk shift type di javascript
    $strShiftTypeArray = "";
    $strSQL = "SElECT * FROM hrd_shift_type ORDER BY code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        if ($strShiftTypeArray != "") {
            $strShiftTypeArray .= ", ";
        }
        $strShiftTypeArray .= "[";
        $strShiftTypeArray .= "\"" . $rowDb['code'] . "\",";
        $strShiftTypeArray .= "\"" . $rowDb['start_time'] . "\",";
        $strShiftTypeArray .= "\"" . $rowDb['finish_time'] . "\",";
        $strShiftTypeArray .= "]";
    }
    $strShiftTypeArray = "var arrShiftType = [$strShiftTypeArray]";
}
// header
$intPrevYear = $strDataYear;
$intNextYear = $strDataYear;
if ($strDataMonth == 1) {
    $intPrevMonth = 12;
    $intPrevYear = $strDataYear - 1;
    $intNextMonth = $strDataMonth + 1;
} else if ($strDataMonth == 12) {
    $intPrevMonth = $strDataMonth - 1;
    $intNextYear = $strDataYear + 1;
    $intNextMonth = 1;
} else {
    $intPrevMonth = $strDataMonth - 1;
    $intNextMonth = $strDataMonth + 1;
}
/*
$strMonthNavigation .= "<strong><a href=shift_schedule_employee.php?dataMonth=$intPrevMonth&dataYear=$intPrevYear>" .getBulanSingkat((int)$intPrevMonth). " ". $intPrevYear. "</a> ";
$strMonthNavigation .= " | " .getBulan((int)$strDataMonth). " ".$strDataYear. " | ";
$strMonthNavigation .= "<a href=shift_schedule_employee.php?dataMonth=$intNextMonth&dataYear=$intNextYear>" .getBulanSingkat((int)$intNextMonth). " ". $intNextYear. "</a></strong> ";
*/
$strHolidayType = getHolidayTypeList("dataCategory");
$strMonthList = getMonthList("dataMonth", $strDataMonth);
$strYearList = getYearList("dataYear", $strDataYear);
$strInitAction .= "Calendar.setup({ inputField:\"dataDate\", button:\"btnDate\" });
    document.formFilter.dataEmployee.focus();
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show(TBS_OUTPUT);
?>

<script src="scripts/acEmployee.js"></script>
<script src="employee_data.php"></script>
<link href="ac.css" rel="stylesheet" type="text/css">
<script>
    // script-script tambahan, khusus untu loockup employee

    AC_targetElements = ["dataEmployee"];

    // fungsi yang melakukan proses jika kode (dari input box yang yang diinginkan(
    // kehilangan fokus.
    function onCodeBlur() {
        /*var kode = document.formInput.dataEmployee.value;
         var nama = AC_getNameByCode(kode);
         var obj = document.getElementById("employee_name");
         obj.innerHTML = nama;
         */
        return 1;
    }
    init();
</script>