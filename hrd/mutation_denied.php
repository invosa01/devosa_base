<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
$dataPrivilege = getDataPrivileges(
    "mutation_edit.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
$bolCanView = ($bolCanApprove || $bolCanCheck);
if (!$bolCanView) {
    die(getWords('action denied'));
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strButtonList = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
function getData($db, $strDataID)
{
    global $ARRAY_REQUEST_STATUS;
    global $words;
    $strResult = "";
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t3.*, t4.*, t5.* ";
    $strSQL .= "FROM hrd_employee_mutation AS t1 LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "LEFT JOIN hrd_employee_mutation_status AS t3 ON t3.id_mutation = t1.id ";
    $strSQL .= "LEFT JOIN hrd_employee_mutation_position AS t4 ON t4.id_mutation = t1.id ";
    $strSQL .= "LEFT JOIN hrd_employee_mutation_department AS t5 ON t5.id_mutation = t1.id ";
    $strSQL .= "WHERE t1.id = '$strDataID'";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        $strResult .= "
        <tr valign=top>\n
          <td>&nbsp;" . strtoupper($words['employee']) . "
          <input type=hidden name=dataEmployeeName value=" . $rowDb['employee_name'] . ">
          </td>
          <td colspan=3>" . $rowDb['employee_id'] . " - " . $rowDb['employee_name'] . "</td>
        </tr>\n
        <tr valign=top>\n
          <td>&nbsp;</td>
          <td colspan=3>&nbsp;</td>
        </tr>\n
        <tr>\n
          <th>&nbsp;</th>
          <th width='250'>" . getWords('old') . "</th>
          <th width='250'>" . getWords('new') . "</th>
          <th>" . getWords('new date') . "</th>
        </tr>\n
        <tr>\n
          <td><strong>" . strtoupper($words['department']) . "</strong></td>
          <td>" . $rowDb['department_old'] . "</td>
          <td>" . $rowDb['department_new'] . "</td>
          <td>" . $rowDb['department_new_date'] . "</td>
        </tr>\n
        <tr>\n
          <td><strong>" . strtoupper($words['status']) . "</strong></td>
          <td>" . $rowDb['status_old'] . "</td>
          <td>" . $rowDb['status_new'] . "</td>
          <td>&nbsp;</td>
        </tr>\n
        <tr>\n
          <td><strong>" . strtoupper($words['position']) . "</strong></td>
          <td>" . $rowDb['position_old'] . "</td>
          <td>" . $rowDb['position_new'] . "</td>
          <td>" . $rowDb['position_new_date'] . "</td>
        </tr>\n
        <tr>\n
          <td><strong>" . strtoupper($words['job grade']) . "</strong></td>
          <td>" . $rowDb['grade_old'] . "</td>
          <td>" . $rowDb['grade_new'] . "</td>
          <td>" . $rowDb['position_new_date'] . "</td>
        </tr>\n
        <tr>\n
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>\n
        <tr valign=top>\n
          <td>&nbsp;" . strtoupper($words['note']) . "</td>
          <td colspan=3>
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
    $strDataProposalDate = (isset($_REQUEST['dataProposalDate'])) ? $_REQUEST['dataProposalDate'] : "";
    $intStatus = (isset($_REQUEST['dataStatus'])) ? $_REQUEST['dataStatus'] : 0;
    $strSQL = "UPDATE hrd_employee_mutation SET note = '$strDataNote', ";
    $strSQL .= "modified_by = '$strModifiedBy' ";
    $strSQL .= "WHERE id = '$strDataID' ";
    $resExec = $db->execute($strSQL);
    writeLog(
        ACTIVITY_EDIT,
        MODULE_EMPLOYEE,
        $strDataEmployeeName . " - " . $strDataProposalDate . " - " . $strDataNote,
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