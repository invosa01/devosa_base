<?php
include_once('../global/session.php');
include_once('global.php');
$dataPrivilege = getDataPrivileges("training_setting.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
    die(getWords('view denied'));
}
$bolPrint = (isset($_REQUEST['btnPrint']));
if ($bolPrint) {
    $strTemplateFile = getTemplate("data_training_setting_print.html");
} else {
    $strTemplateFile = getTemplate("data_training_setting.html");
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
$strWordsTrainingQuestionEmployee = getWords("setting for training evaluation by employee");
$strWordsTrainingQuestionManager = getWords("setting for training evaluation by manager");
$strWordsTrainingEvaluationQuestion = getWords("training evaluation question");
$strWordsInputTrainingQuestionCategory = getWords("input category question");
$strDisableApprove = ($_SESSION['sessionUserRole'] == ROLE_MANAGER) ? "" : "disabled";
$strWordsTrainingTypeData = getWords("question category data");
$strWordsINPUTDATATRAININGTYPE = getWords("question category data");
$strWordsTrainingSetting = getWords("question category data");
$strWordsNote = getWords("note");
$strWordsTRAININGSETTING = getWords("question category");
$strWordsNOTE = getWords("note");
$strWordsDelete = getWords("delete");
$strWordsSave = getWords("save");
$strWordsPrint = getWords("print");
$strWordsAddNew = getWords("add new");
$strWordsLISTOFTRAININGTYPE = getWords("list of question category");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data per barisnya aja
function getDataRows($rowDb, $intRows)
{
    global $words;
    global $bolPrint;
    $strResult = "";
    $strResult .= "<tr valign=top >\n";
    if ($bolPrint) {
        $strResult .= "  <td nowrap>" . $intRows . "</td>\n";
    } else {
        $strResult .= "  <td nowrap><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
    }
    $strResult .= "  <td><input type=hidden name='detailID$intRows' value=\"" . $rowDb['id'] . "\"><input type=hidden name=detailName$intRows value=\"" . $rowDb['category'] . "\" disabled>" . $rowDb['category'] . "&nbsp;</td>";
    $strResult .= "  <td><input type=hidden name=detailNote$intRows value=\"" . $rowDb['note'] . "\" disabled>" . $rowDb['note'] . "&nbsp;</td>";
    if (!$bolPrint) {
        $strResult .= "  <td nowrap align=center><a href='javascript:editData($intRows)'>" . $words['edit'] . "</a>&nbsp;</td>";
    }
    $strResult .= "</tr>\n";
    return $strResult;
}

// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $bolPrint;
    global $ARRAY_LEADER_LIST;
    $intRows = 0;
    $strResult = "";
    // cari dulu data temporer yang link IDnya ada
    $strSQL = "SELECT * FROM hrd_training_setting";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= getDataRows($rowDb, $intRows);
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $error;
    $strError = "";
    $strFields = "created, modified_by, created_by,  note";
    // cek ijinnya
    $bolIsManager = ($_SESSION['sessionUserRole'] == ROLE_MANAGER);
    (isset($_REQUEST['dataName'])) ? $strDataName = $_REQUEST['dataName'] : $strDataName = "";
    (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    // cek validasi -----------------------
    if ($strDataName == "") {
        $strError = $error['empty_code'];
        return false;
    } else {
        ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
        if (isDataExists("hrd_training_setting", "category", $strDataName, $strKriteria)) {
            $strError = $error['duplicate_code'] . "  -> $strDataName";
            return false;
        }
    }
    // simpan data -----------------------
    if ($strDataID == "") {
        // data baru
        $strSQL = "INSERT INTO hrd_training_setting (created,created_by,modified_by, ";
        $strSQL .= "category, note) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "'$strDataName','$strDataNote') ";
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataName", 0);
    } else {
        /*if (!$bolIsManager) {
          if ($strDataFlag == 0) { // master, bikin temporernnya
            $strDataID = getTempData($db, "hrdTrainingType", $strFields, $strDataID,2);
          }
        }*/
        $strSQL = "UPDATE hrd_training_setting ";
        $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "category = '$strDataName',  note = '$strDataNote' ";
        $strSQL .= "WHERE id = '$strDataID' ";
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataName", 0);
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
            $strSQL = "DELETE FROM hrd_training_setting WHERE id = '$strValue'; ";
            $resExec = $db->execute($strSQL);
            $i++;
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
    }
} //deleteData
// fungsi untuk approve data oleh manager
function approveData($db)
{
    global $_REQUEST;
    if ($_SESSION['sessionUserRole'] != ROLE_MANAGER) {
        return 0;
    }
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            approveTempData($db, "hrd_training_setting", $strValue);
            $i++;
        }
    }
} //approveData
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
    } else if (isset($_REQUEST['btnApprove']) && $_SESSION['sessionUserRole'] == ROLE_MANAGER) {
        approveData($db);
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $intTotalData);
        $strData = $strDataDetail; // print
    } else {
        showError("view_denied");
    }
    $strInfo = "";
    $strPeriod = "";
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
    $strMainTemplate = getTemplate("data_training_setting_print.html");//;"../templates/master_print.html";
}
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
