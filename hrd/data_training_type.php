<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
$bolCanView = $bolCanEdit = true;
if (!$bolCanView) {
    die(getWords('view denied'));
}
$bolPrint = (isset($_REQUEST['btnPrint']));
if ($bolPrint) {
    $strTemplateFile = getTemplate("data_training_type_print.html");
} else {
    $strTemplateFile = getTemplate("data_training_type.html");
}
//---- INISIALISASI ----------------------------------------------------
$db = new CdbClass;
$strDataDetail = "";
$intTotalData = 0;
$strWordDomain = getWords("domain");
$strWordsTrainingTypeData = getWords("training type data");
$strWordsINPUTDATATRAININGTYPE = getWords("training type data");
$strWordsTrainingType = getWords("sub domain");
$strWordsNote = getWords("note");
$strWordsTRAININGTYPE = getWords("sub domain");
$strWordsDomain = getWords("domain");
$strWordsCompetency = getWords("competency");
$strWordsNOTE = getWords("note");
$strAction = "onFocus = \"AC_kode = 'dataDomain';\" ";
$strInputDomain = "  <input type=text size=50 maxlength=50 name=dataDomain $readonly2 value=\"$strDataDomain\"  $strAction>";
$strAction = "onFocus = \"AC_kode = 'dataCompetency';\" ";
$strInputCompetency = "  <input type=text size=50 maxlength=50 name=dataCompetency id=dataCompetency $readonly2 value=\"$strDataCompetency\"  $strAction>";
$strWordsDelete = getWords("delete");
$strWordsSave = getWords("save");
$strWordsPrint = getWords("print");
$strWordsAddNew = getWords("add new");
$strWordsLISTOFTRAININGTYPE = getWords("list of training type");
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
    $strResult .= "  <td><input type=hidden name='detailID$intRows' value=\"" . $rowDb['id'] . "\"><input type=hidden name=detailName$intRows value=\"" . $rowDb['training_type'] . "\" disabled>" . $rowDb['training_type'] . "&nbsp;</td>";
    $strResult .= "  <td><input type=hidden name=detailNote$intRows value=\"" . $rowDb['note'] . "\" disabled>" . $rowDb['note'] . "&nbsp;</td>";
    $strResult .= "  <td><input type=hidden name=detailDomain$intRows value=\"" . $rowDb['domain'] . "\" disabled>" . $rowDb['domain'] . "&nbsp;</td>";
    $strResult .= "  <td><input type=hidden name=detailCompetency$intRows value=\"" . $rowDb['competency'] . "\" disabled>" . $rowDb['competency'] . "&nbsp;</td>";
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
    $strSQL = "SELECT * FROM hrd_training_type ORDER BY training_type";
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
    $strFields = "created, modified_by, created_by, training_type, note, domain, competency";
    (isset($_REQUEST['dataName'])) ? $strDataName = $_REQUEST['dataName'] : $strDataName = "";
    (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
    (isset($_REQUEST['dataDomain'])) ? $strDataDomain = $_REQUEST['dataDomain'] : $strDataDomain = "";
    (isset($_REQUEST['dataCompetency'])) ? $strDataCompetency = $_REQUEST['dataCompetency'] : $strDataCompetency = "";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    // cek validasi -----------------------
    if ($strDataName == "") {
        $strError = $error['empty_code'];
        return false;
    } else {
        ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
        if (isDataExists($db, "hrd_training_type", "training_type", $strDataName, $strKriteria)) {
            $strError = $error['duplicate_code'] . "  -> $strDataName";
            return false;
        }
    }
    // simpan data -----------------------
    if ($strDataID == "") {
        // data baru
        $strSQL = "INSERT INTO hrd_training_type (created,created_by,modified_by, ";
        $strSQL .= "training_type, note, domain, competency) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "'$strDataName','$strDataNote', '$strDataDomain', '$strDataCompetency') ";
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataName", 0);
    } else {
        /*if (!$bolIsManager) {
          if ($strDataFlag == 0) { // master, bikin temporernnya
            $strDataID = getTempData($db, "hrdTrainingType", $strFields, $strDataID,2);
          }
        }*/
        $strSQL = "UPDATE hrd_training_type ";
        $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "training_type = '$strDataName',  note = '$strDataNote', domain = '$strDataDomain', competency = '$strDataCompetency'";
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
            $strSQL = "DELETE FROM hrd_training_type WHERE id = '$strValue'; ";
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
            approveTempData($db, "hrd_training_type", $strValue);
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
$strPageTitle = $dataPrivilege['menu_name'];
$strPageDesc = getWords("Training Type");;
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
?>