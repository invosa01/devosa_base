<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
$dataPrivilege = getDataPrivileges(
    "absence_edit.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
$bolCanView = ($bolCanApprove || $bolCanCheck || $bolCanAcknowledge);
if (!$bolCanView) {
    die(getWords('action denied'));
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strButtonList = "";
$strWordsDataEntry = getWords("data entry");
$strWordsAbsenceList = getWords("absence list");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
function getData($db, $strDataID)
{
    global $ARRAY_REQUEST_STATUS;
    $strResult = "";
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name
                FROM hrd_absence AS t1 
                LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id 
                WHERE t1.id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        $strResult .= "
        <tr>\n
          <td>&nbsp;" . getWords('employee') . "</td>
          <td>&nbsp;:<input type=hidden name=dataEmployeeName value=" . $rowDb['employee_name'] . "></td>
          <td>&nbsp;" . $rowDb['employee_id'] . " - " . $rowDb['employee_name'] . "</td>
        </tr>\n
        <tr>\n
          <td>&nbsp;" . getWords('absence date') . "</td>
          <td>&nbsp;:<input type=hidden name=dataDateFrom value=" . $rowDb['date_from'] . "><input type=hidden name=dataDateThru value=" . $rowDb['date_thru'] . "></td>
          <td>&nbsp;" . $rowDb['date_from'] . " - " . $rowDb['date_thru'] . "</td>
        </tr>\n
        <tr>\n
          <td>&nbsp;" . getWords('absence type') . "</td>
          <td>&nbsp;:<input type=hidden name=dataAbsenceTypeCode value=" . $rowDb['absence_type_code'] . "></td>
          <td>&nbsp;" . $rowDb['absence_type_code'] . "</td>
        </tr>\n
        <tr>\n
          <td>&nbsp;" . getWords('status') . "</td>
          <td>&nbsp;:<input type=hidden name=dataStatus value=" . $rowDb['status'] . "></td>
          <td>&nbsp;" . $ARRAY_REQUEST_STATUS[$rowDb['status']] . "</td>
        </tr>\n
        <tr valign=top>\n
          <td>&nbsp;" . getWords('note') . "</td>
          <td>&nbsp;:</td>
          <td>
          <input type=hidden name=dataID value=$strDataID>
          <textarea cols=100 rows=3 name=dataNote>" . $rowDb['note'] . "</textarea><br>&nbsp;<br>
          <input type=submit name=\"btnSave\" value=\"" . getWords('save') . "\"></td>
        </tr>\n";
    }
    return $strResult;
} //getData
// fungsi untuk menyimpan data note
function saveData($db)
{
    global $_REQUEST;
    global $_SESSION;
    $strModifiedBy = $_SESSION['sessionUserID'];
    $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
    $strDataNote = (isset($_REQUEST['dataNote'])) ? $_REQUEST['dataNote'] : "";
    $strDataEmployeeName = (isset($_REQUEST['dataEmployeeName'])) ? $_REQUEST['dataEmployeeName'] : "";
    $strDataDateFrom = (isset($_REQUEST['dataDateFrom'])) ? $_REQUEST['dataDateFrom'] : "";
    $strDataDateThru = (isset($_REQUEST['dataDateThru'])) ? $_REQUEST['dataDateThru'] : "";
    $intStatus = (isset($_REQUEST['dataStatus'])) ? $_REQUEST['dataStatus'] : 0;
    $strDataAbsenceTypeCode = (isset($_REQUEST['dataAbsenceTypeCode'])) ? $_REQUEST['dataAbsenceTypeCode'] : "";
    $strSQL = "UPDATE hrd_absence SET note = '$strDataNote', ";
    $strSQL .= "modified_by = '$strModifiedBy' ";
    $strSQL .= "WHERE id = '$strDataID' ";
    $resExec = $db->execute($strSQL);
    writeLog(
        ACTIVITY_EDIT,
        MODULE_EMPLOYEE,
        $strDataEmployeeName . " - " . $strDataDateFrom . " - " . $strDataAbsenceTypeCode . " - " . $strDataNote,
        $intStatus
    );
    //header("location:overtime_application_list_employee.php");
} //saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $bolIsEmployee = isUserEmployee();
    $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
    if ($bolCanView) {
        if (isset($_REQUEST['btnSave'])) {
            saveData($db);
        }
        $strDataDetail = getData($db, $strDataID);
    } else {
        showError("view_denied");
    }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = "note";
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