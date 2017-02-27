<?php
include_once('../global/session.php');
include_once('global.php');
$dataPrivilege = getDataPrivileges(
    "loan_purpose.php",
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
$strDataDetail = "";
$intTotalData = 0;
$strWordsDataEntry = getWords("data entry");
$strWordsLoanList = getWords("loan list");
$strWordsLoanType = getWords("loan type");
$strWordsLoanPurpose = getWords("loan purpose");
$strWordsListOfLoanPurpose = getWords("list of loan purpose");
$strWordsNote = getWords("note");
$strWordsSave = getWords("save");
$strWordsClear = getWords("clear");
$strWordsInputData = getWords("input data");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $bolCanEdit;
    $intRows = 0;
    $strResult = "";
    $strDataID = getPostValue('dataID');
    $isNew = ($strDataID == "");
    $strSQL = "SELECT * FROM hrd_loan_purpose ";
    $strSQL .= "WHERE 1=1 $strKriteria ORDER BY $strOrder \"purpose\" ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= "<tr valign=top>\n";
        if ($bolCanEdit) {
            $strResult .= "  <td><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></label></div></td>\n";
        } else {
            $strResult .= "  <td>&nbsp;</td>\n";
        }
        $strResult .= "  <td nowrap><input type=hidden name=detailPurpose$intRows value=\"" . $rowDb['purpose'] . "\">" . $rowDb['purpose'] . "&nbsp;</td>";
        $strResult .= "  <td nowrap><input type=hidden name=detailNote$intRows value=\"" . $rowDb['note'] . "\">" . $rowDb['note'] . "&nbsp;</td>";
        if ($bolCanEdit) {
            $strResult .= "  <td nowrap align=center><a href='javascript:editData($intRows)'>" . $words['edit'] . "</a>&nbsp;</td>";
        } else {
            $strResult .= "  <td nowrap>&nbsp;</td>";
        }
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // showData
//   fungsi untuk menyimpan data
function saveData($db, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $error;
    $strError = "";
    (isset($_REQUEST['dataPurpose'])) ? $strDataPurpose = $_REQUEST['dataPurpose'] : $strDataPurpose = "";
    (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    // cek validasi -----------------------
    if ($strDataPurpose == "") {
        $strError = $error['empty_code'];
        return false;
    } else {
        ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
        if (isDataExists($db, "hrd_loan_purpose", "purpose", $strDataPurpose, $strKriteria)) {
            $strError = $error['duplicate_code'] . "  -> $strDataPurpose";
            return false;
        }
    }
    // simpan data -----------------------
    if ($strDataID == "") {
        // data baru
        $strSQL = "INSERT INTO hrd_loan_purpose (created,created_by,modified_by, purpose, note) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "',";
        $strSQL .= "'" . $_SESSION['sessionUserID'] . "', '$strDataPurpose', '$strDataNote') ";
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataPurpose", 0);
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataNote", 0);
    } else {
        $strSQL = "UPDATE hrd_loan_purpose ";
        $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', purpose = '$strDataPurpose', note = '$strDataNote' ";
        $strSQL .= "WHERE id = '$strDataID' ";
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataPurpose", 0);
    }
    $resExec = $db->execute($strSQL);
    return true;
} // saveData
// fungsi untuk menghapus data
function deleteData($db)
{
    global $_REQUEST;
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $strSQL = "DELETE FROM hrd_loan_purpose WHERE id = '$strValue' ";
            $resExec = $db->execute($strSQL);
            $i++;
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
    }
} //deleteData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    if (isset($_REQUEST['btnSave'])) {
        if ($bolCanEdit) {
            if (!saveData($db, $strError)) {
                echo "<script>alert(\"$strError\")</script>";
            }
        }
    } else if (isset($_REQUEST['btnDelete'])) {
        if ($bolCanDelete) {
            deleteData($db);
        }
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $intTotalData);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
}
//$strButtonList = generateRoleButtons($bolCanEdit, $bolCanDelete, $bolCanCheck, $bolCanApprove, $bolCanAcknowledge);
$strButtonList = "<input type=\"submit\" name=\"btnDelete\" id=\"btnDelete\" onClick=\"return confirmDelete()\" value=\"" . $words['delete'] . "\" />";
//generateSubmit("btnDelete", $words['delete'], "", " onClick=\"return confirmDelete()\"");
$strDisplay = ($bolCanEdit) ? "block" : "none";
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('loan purpose entry');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = loanSubMenu($strWordsLoanPurpose);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
?>