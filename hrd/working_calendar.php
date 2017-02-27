<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('calendar.php');
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
$strMonthNavigation = "";
$strMonthList = "";
$strYearList = "";
$strCalendar = "";
$strLegend = "";
$strNow = date($_SESSION['sessionDateSetting']['php_format']);
$strHTMLDateFormat = $_SESSION['sessionDateSetting']['html_format'];
$strDateSparator = $_SESSION['sessionDateSetting']['date_sparator'];
$strDatePosYMD = $_SESSION['sessionDateSetting']['pos_year'] . $_SESSION['sessionDateSetting']['pos_month'] . $_SESSION['sessionDateSetting']['pos_day'];
$strHolidayCategory = "";
$strWordsEventList = getWords("event list");
$strWordsSelectMonth = getWords("select month");
$strWordsDate = getWords("date");
$strWordsEvent = getWords("event");
$strWordsCategory = getWords("category");
$strWordsHoliday = getWords("holiday");
$strWordsYes = getWords("yes");
$strWordsNo = getWords("no");
$strWordsLeave = getWords("leave");
$strWordsYes = getWords("yes");
$strWordsNo = getWords("no");
$strWordsShow = getWords("show");
$strWordsSave = getWords("save");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database
// return berubah $arrOvertime (parameter)
function getData($db, $strMonth, $strYear)
{
    global $words;
    $strResult = getMonthlyCalendar($db, $strMonth, $strYear); //calendar.php
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$strMonth - $strYear", 0);
    return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $messages;
    global $error;
    (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = "";
    (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
    (isset($_REQUEST['dataCategory'])) ? $strDataCategory = $_REQUEST['dataCategory'] : $strDataCategory = "";
    (isset($_REQUEST['dataHoliday'])) ? $strDataStatus = $_REQUEST['dataHoliday'] : $strDataStatus = "t";
    (isset($_REQUEST['dataLeave'])) ? $strDataLeave = $_REQUEST['dataLeave'] : $strDataLeave = "t";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    $strDataDate = standardDateToSQLDateNew(
        $_REQUEST['dataDate'],
        $_SESSION['sessionDateSetting']['date_sparator'],
        $_SESSION['sessionDateSetting']['pos_year'],
        $_SESSION['sessionDateSetting']['pos_month'],
        $_SESSION['sessionDateSetting']['pos_day']
    );
    /*    print_r($_REQUEST);
        die();*/
    // ---- cek validasi -----
    $bolOK = true;
    if ($strDataDate == "") {
        $strError = $error['invalid_date'];
        $bolOK = false;
    } else if (!validStandardDate($strDataDate)) {
        $strError = $error['invalid_date'];
        $bolOK = false;
    } else if ($strDataNote == "") {
        $strError = $error['empty_data'];
        $bolOK = false;
    }
    if ($bolOK) {
        // cari apakah udah ada data atau belum
        if ($strDataID != "") {
            $strSQL = "UPDATE hrd_calendar SET created = now(), ";
            $strSQL .= "modified_by = '" . $_SESSION['sessionUserID'] . "', ";
            $strSQL .= "category = '$strDataCategory', note = '$strDataNote', ";
            $strSQL .= "leave = '$strDataLeave', status = '$strDataStatus' ";
            $strSQL .= "WHERE id = $strDataID ";
            $resDb = $db->execute($strSQL);
            writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataDate", 0);
        } else {
            $strSQL = "INSERT INTO hrd_calendar (created, modified_by, created_by, ";
            $strSQL .= "holiday, category, note, leave, status) ";
            $strSQL .= "VALUES(now(), '" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
            $strSQL .= "'$strDataDate', '$strDataCategory', '$strDataNote', '$strDataLeave', '$strDataStatus') ";
            $resDb = $db->execute($strSQL);
            writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataDate", 0);
        }
    }
    return $bolOK;
} // saveData
// menghapus data
function deleteData($db)
{
    global $_REQUEST;
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    (isset($_REQUEST['dataDay'])) ? $strDataDay = $_REQUEST['dataDay'] : $strDataDay = "";
    (isset($_REQUEST['dataMonth'])) ? $strDataMonth = $_REQUEST['dataMonth'] : $strDataMonth = "";
    (isset($_REQUEST['dataYear'])) ? $strDataYear = $_REQUEST['dataYear'] : $strDataYear = "";
    $strDataDate = $strDataYear . "-" . $strDataMonth . "-" . $strDataDay;
    if ($strDataID != "") {
        $strSQL = "DELETE FROM hrd_calendar WHERE id = $strDataID ";
        $resExec = $db->execute($strSQL);
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$strDataDate", 0);
        // handle jika ada kaitannya dengan lembur, dsb -------
    }
    return true;
}//deleteData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
//--- Ambil Data Yang Dikirim ----
$dtNow = getdate();
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
    getUserEmployeeInfo();
    $bolIsEmployee = isUserEmployee();
    if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        $strShowEdit = "";
    } else {
        $strShowEdit = "style=\"display:none\"";
    }
    if (!$bolIsEmployee) {// selain employee biasa
        if (isset($_REQUEST['btnSave'])) {
            if ($bolCanEdit) {
                saveData($db, $strError);
                if ($strError != "") {
                    echo "<script>alert(\"$strError\")</script>";
                }
            }
        } else if (isset($_REQUEST['btnDelete'])) {
            if ($bolCanDelete) {
                deleteData($db);
            }
        }
    }
    if ($bolCanView) {
        $strCalendar = getData($db, $strDataMonth, $strDataYear);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
    // buat legenda
    $strLegend .= "<table cellspacing=0 border=0 width=100% class='table tabled-bordered gridTable'>\n";
    $strLegend .= "  <tr><td class='bgNationalHoliday' width=20>&nbsp;</td>";
    $strLegend .= "  <td class='left' nowrap>" . getWords('national event') . "&nbsp;</td></tr>";
    $strLegend .= "  <tr><td class='bgCompanyHoliday' width=20>&nbsp;</td>";
    $strLegend .= "  <td class='left' nowrap>" . getWords('company event') . "&nbsp;</td></tr>";
    $strLegend .= "  <tr><td class='bgSpecialHoliday' width=20>&nbsp;</td>";
    $strLegend .= "  <td class='left' nowrap>" . getWords('special event') . "&nbsp;</td></tr>";
    $strLegend .= "  <tr><td class='bgLeaveHoliday' width=20>&nbsp;</td>";
    $strLegend .= "  <td class='left' nowrap>" . getWords('leave') . "&nbsp;</td></tr>";
    $strLegend .= "</table>\n";
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
$strMonthNavigation .= "<strong><a class=\"btn btn-primary btn-small\" href=working_calendar.php?dataMonth=$intPrevMonth&dataYear=$intPrevYear>" . getBulanSingkat(
        (int)$intPrevMonth
    ) . " " . $intPrevYear . "</a> ";
$strMonthNavigation .= "<button class=\"btn btn-primary btn-small\" disabled>" . getBulan(
        (int)$strDataMonth
    ) . " " . $strDataYear . "</button> ";
$strMonthNavigation .= "<a class=\"btn btn-primary btn-small\" href=working_calendar.php?dataMonth=$intNextMonth&dataYear=$intNextYear>" . getBulanSingkat(
        (int)$intNextMonth
    ) . " " . $intNextYear . "</a></strong> ";
$strHolidayType = getHolidayTypeList("dataCategory");
$strMonthList = getMonthList("dataMonth", $strDataMonth);
$strYearList = getYearList("dataYear", $strDataYear);
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("manage working calendar");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>