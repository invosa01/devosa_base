<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
// Data Privilage followed from parent (employee_edit.php)
$dataPrivilege = getDataPrivileges(
    basename("employee_edit.php"),
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
// ambil informasi apakah termasuk user HRD atau bukan - from global.php versi before
$bolHRDUser = false;
if (isset($_SESSION['sessionUserRole'])) {
    $bolHRDUser = ($_SESSION['sessionUserRole'] == 3) || ($_SESSION['sessionUserRole'] == 4);
    // hanya admin atau manager yang boleh
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
$strInitCalendar = "";
$strEmployeeID = "";
$stremployee_name = "";
$strMessages = "";
$strMsgClass = "";
$bolError = false;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_FAMILY_RELATION;
    global $intDefaultWidth;
    global $strInitCalendar;
    global $strEmptyOption;
    global $bolHRDUser;
    $intDefaultWidth = 15;
    $intRows = 0;
    $intShown = 0;
    $intAdd = 10; // maksimum tambahan
    $strResult = "";
    $strNow = date("Y-m-d");
    // ambil dulu daftar fasilitas yang dimiliki employee, simpan di array
    $strSQL = "SELECT * FROM hrd_employee_facility ";
    $strSQL .= "WHERE 1=1 $strKriteria ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrEmployeeFacility[$rowDb['facility']]['note'] = $rowDb['note'];
        $arrEmployeeFacility[$rowDb['facility']]['id'] = $rowDb['id'];
    }
    // ambil daftar fasilitas yang ada
    $strSQL = "SELECT * FROM hrd_facility ";
    $strSQL .= "ORDER BY name ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $intShown++;
        if (isset($arrEmployeeFacility[$rowDb['name']])) {
            $strNote = $arrEmployeeFacility[$rowDb['name']]['note'];
            $strID = $arrEmployeeFacility[$rowDb['name']]['id'];
            $strID = "<input type=hidden name=detailID$intRows value=\"$strID\">";
            $strChecked = "checked";
        } else {
            $strNote = "";
            $strID = "";
            $strChecked = "";
        }
        if ($bolHRDUser) { // bisa edit
            $strResult .= "<tr valign=top>\n";
            $strResult .= "  <td nowrap>$intRows.&nbsp;$strID</td>";
            $strResult .= "  <td nowrap><input type=checkbox name=chkID$intRows value=\"" . $rowDb['name'] . "\" $strChecked></td>";
            $strResult .= "  <td nowrap title=\"" . $rowDb['note'] . "\">&nbsp;" . $rowDb['name'] . "&nbsp;</td>";
            $strResult .= "  <td nowrap><input type=text size=30 maxlength=50 name=detailNote$intRows value=\"" . $strNote . "\"></td>";
            $strResult .= "</tr>\n";
        } else { // gak bisa edit
            $strResult .= "<tr valign=top>\n";
            $strResult .= "  <td nowrap>$intRows.&nbsp;$strID</td>";
            $strResult .= "  <td nowrap><input type=checkbox name=chkID$intRows value=\"" . $rowDb['name'] . "\" $strChecked disabled></td>";
            $strResult .= "  <td nowrap title=\"" . $rowDb['note'] . "\">&nbsp;" . $rowDb['name'] . "&nbsp;</td>";
            $strResult .= "  <td nowrap>&nbsp;" . $strNote . "</td>";
            $strResult .= "</tr>\n";
        }
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    $strResult .= "<input type=hidden name=maxDetail value=$intRows>";
    return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, $strDataID, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $messages;
    $strError = "";
    (isset($_REQUEST['maxDetail'])) ? $intMax = $_REQUEST['maxDetail'] : $intMax = 0;
    for ($i = 1; $i <= $intMax; $i++) {
        (isset($_REQUEST['detailID' . $i])) ? $strID = $_REQUEST['detailID' . $i] : $strID = "";
        (isset($_REQUEST['chkID' . $i])) ? $strFacility = $_REQUEST['chkID' . $i] : $strFacility = "";
        (isset($_REQUEST['detailNote' . $i])) ? $strNote = $_REQUEST['detailNote' . $i] : $strNote = "";
        if ($strID == "") {
            if ($strFacility != "") { // insert new data
                $strSQL = "INSERT INTO hrd_employee_facility (created,modified, created_by, modified_by,";
                $strSQL .= "id_employee, facility, note) ";
                $strSQL .= "VALUES(now(),now(), '" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
                $strSQL .= "'$strDataID', '$strFacility', '$strNote') ";
                $resDb = $db->execute($strSQL);
                writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataID", 0);
            }
        } else {
            if ($strFacility == "") {
                // delete data
                $strSQL = "DELETE FROM hrd_employee_facility WHERE id = '$strID' ";
                $resDb = $db->execute($strSQL);
                writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$strDataID", 0);
            } else {
                // update data
                $strSQL = "UPDATE hrd_employee_facility SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
                $strSQL .= "modified = now(), facility = '$strFacility', note = '$strNote' ";
                $strSQL .= "WHERE id = '$strID' ";
                $resDb = $db->execute($strSQL);
                writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataID", 0);
            }
        }
    }
    $strError = $messages['data_saved'] . " >> " . date("d-M-Y H:i:s");
    return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $bolEmployee = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE);
    $bolSupervisor = ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR);
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    (isset($arrUserInfo['department_code'])) ? $strDataDepartment = $arrUserInfo['department_code'] : $strDataDepartment = "";
    if ($bolCanEdit && $strDataID != "") {
        if (isset($_POST['btnSave'])) {
            // cuma admin atau manager yang boleh edit
            if ($_SESSION['sessionUserRole'] == 3 || $_SESSION['sessionUserRole'] == 4) {
                if ($bolEmployee || $bolSupervisor || !saveData($db, $strDataID, $strError)) {
                    //echo "<script>alert(\"$strError\")</script>";
                    $bolError = true;
                    if ($bolEmployee || $bolSupervisor) {
                        $strError = getWords("sorry, you can not edit this page");
                    }
                }
                if ($strError != "") {
                    $strMessages = $strError;
                    $strMsgClass = ($bolError) ? "class=bgError" : "class=bgOK";
                }
            }
        }
    }
    if ($strDataID == "") {
        redirectPage("employee_search.php");
        exit();
    } else {
        // cari info karyawan
        $strSQL = "SELECT employee_id, employee_name, flag, link_id FROM hrd_employee WHERE id = '$strDataID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['flag'] != 0 AND $rowDb['link_id'] != "") { // folder temporer
                $strDataID = $rowDb['link_id'];
            }
            $strEmployeeID = $rowDb['employee_id'];
            $strEmployeeName = strtoupper($rowDb['employee_name']);
            if ($bolEmployee && ($strEmployeeID != $arrUserInfo['employee_id'])) {
                $bolCanView = false;
                redirectPage("employee_search.php");
                exit();
            }
            if ($bolSupervisor && ($strDataDepartment != $arrUserInfo['department_code'])) {
                $bolCanView = false;
                redirectPage("employee_search.php");
                exit();
            }
        } else {
            redirectPage("employee_search.php");
            exit();
        }
        ($strDataID == "") ? $strKriteria = " AND 1=2 " : $strKriteria = " AND id_employee = '$strDataID' ";
        if ($bolCanView) {
            $strDataDetail = getData($db, $intTotalData, $strKriteria);
        } else {
            showError("view_denied");
            $strDataDetail = "";
        }
    }
}
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