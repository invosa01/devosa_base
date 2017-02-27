<?php
session_start();
include_once('global.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=data_education.php");
    exit();
}
$bolCanView = getUserPermission("data_education.php", $bolCanEdit, $bolCanDelete, $strError);
$strTemplateFile = getTemplate("data_education.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
$strDisableApprove = ($_SESSION['sessionUserRole'] == ROLE_ADMIN) ? "" : "disabled";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data per barisnya aja
function getDataRows($rowDb, $intRows)
{
    global $words;
    $strResult = "";
    if ($rowDb['flag'] == 0) {
        $strClass = $strAddChar = "";
    } else {
        $strClass = "class=bgCheckedData";
        $strAddChar = ($rowDb['link_id'] == "") ? "" : "&nbsp;&nbsp;";
    }
    $strResult .= "<tr valign=top $strClass>\n";
    $strResult .= "  <td nowrap>$strAddChar<input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\">\n";
    $strResult .= "  <input type=hidden disabled name='detailFlag$intRows' value=\"" . $rowDb['flag'] . "\"></td>\n";
    $strResult .= "  <td nowrap><input type=hidden name=detailCode$intRows value=\"" . $rowDb['code'] . "\">" . $rowDb['code'] . "&nbsp;</td>";
    $strResult .= "  <td><input type=hidden name=detailName$intRows value=\"" . $rowDb['name'] . "\">" . $rowDb['name'] . "&nbsp;</td>";
    $strResult .= "  <td><input type=hidden name=detailNote$intRows value=\"" . $rowDb['note'] . "\">" . $rowDb['note'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap align=center><a href='javascript:editData($intRows)'>" . $words['edit'] . "</a>&nbsp;</td>";
    $strResult .= "</tr>\n";
    return $strResult;
}

// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    $intRows = 0;
    $strResult = "";
    // cari dulu data temporer yang link IDnya ada
    $strSQL = "SELECT * FROM hrd_education_level WHERE flag <> 0 AND link_id is not null ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrTmp[$rowDb['link_id']] = $rowDb;
    }
    $strSQL = "SELECT * FROM hrd_education_level ";
    $strSQL .= "WHERE flag = 0 $strKriteria ORDER BY $strOrder \"code\" ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= getDataRows($rowDb, $intRows);
        if (isset($arrTmp[$rowDb['id']])) {
            $intRows++;
            $strResult .= getDataRows($arrTmp[$rowDb['id']], $intRows);
        }
    }
    // cari dulu data temporer yang link IDnya ada
    $strSQL = "SELECT * FROM hrd_education_level WHERE flag <> 0 AND link_id is null ";
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
    $strFields = "created, modified_by, created_by, code, name, note";
    // cek ijinnya
    $bolIsManager = ($_SESSION['sessionUserRole'] == ROLE_ADMIN);
    (isset($_REQUEST['dataCode'])) ? $strDataCode = trim($_REQUEST['dataCode']) : $strDataCode = "";
    (isset($_REQUEST['dataName'])) ? $strdataName = $_REQUEST['dataName'] : $strdataName = "";
    (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    (isset($_REQUEST['dataFlag'])) ? $strDataFlag = $_REQUEST['dataFlag'] : $strDataFlag = "2";
    // cek validasi -----------------------
    if ($strDataCode == "") {
        $strError = $error['empty_code'];
        return false;
    } else if ($strdataName == "") {
        $strError = $error['empty_name'];
        return false;
    } else {
        ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
        if (isDataExists("hrdEducationLevel", "code", $strDataCode, $strKriteria)) {
            $strError = $error['duplicate_code'] . "  -> $strDataCode";
            return false;
        }
    }
    // simpan data -----------------------
    if ($strDataID == "") {
        // data baru
        $strFlag = 0;//($bolIsManager) ? 0 : 2;
        $strSQL = "INSERT INTO hrd_education_level (created,created_by,modified_by, code,name, note, flag) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "'$strDataCode', '$strdataName','$strDataNote', '$strFlag') ";
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataCode", 0);
    } else {
        $strSQL = "UPDATE hrd_education_level ";
        $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', code = '$strDataCode', ";
        //if ($bolIsManager) {
        $strSQL .= "flag = 0, "; // jika manager, langsung jadi 0
        //}
        $strSQL .= "name= '$strdataName', note = '$strDataNote' ";
        $strSQL .= "WHERE id = '$strDataID' ";
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataCode", 0);
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
            $strSQL = "DELETE FROM hrd_education_level WHERE link_id = '$strValue'; ";
            $strSQL .= "DELETE FROM hrd_education_level WHERE id = '$strValue'; ";
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
    if ($_SESSION['sessionUserRole'] != ROLE_ADMIN) {
        return 0;
    }
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            approveTempData($db, "hrdEducationLevel", $strValue);
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
    } else if (isset($_REQUEST['btnApprove']) && $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        approveData($db);
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $intTotalData);
    } else {
        showError("view_denied");
    }
}
$strInitAction .= "    document.formInput.dataCode.focus();   ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>