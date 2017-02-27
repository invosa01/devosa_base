<?php
session_start();
include_once('global.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=changepassword.php");
    exit();
}
$bolCanView = getUserPermission("changepassword.php", $bolCanEdit, $bolCanDelete, $strError, true);
$strTemplateFile = getTemplate("changepassword.html");
//---- INISIALISASI ----------------------------------------------------
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $error;
    $strError = "";
    (isset($_REQUEST['dataOld'])) ? $strDataOld = $_REQUEST['dataOld'] : $strDataOld = "";
    (isset($_REQUEST['dataNew'])) ? $strDataNew = $_REQUEST['dataNew'] : $strDataNew = "";
    (isset($_REQUEST['dataNew1'])) ? $strDataNew1 = $_REQUEST['dataNew1'] : $strDataNew1 = "";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    // cek validasi -----------------------
    if ($strDataOld == "") {
        $strError = $error['empty_old_password'];
        return false;
    } else if ($strDataNew == "") {
        $strError = $error['empty_new_password'];
        return false;
    } else if ($strDataNew != $strDataNew1) {
        $strError = $error['new_password_not_match'];
        return false;
    }
    // chek password lama data -----------------------
    $strSQL = "SELECT passwd FROM all_user WHERE id = '" . $_SESSION['sessionUserID'] . "' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        if (md5($strDataOld) == $rowDb['passwd']) {
            // simpan perubahan dta
            $strSQL = "UPDATE all_user SET passwd = '" . md5($strDataNew) . "' ";
            $strSQL .= "WHERE id = '" . $_SESSION['sessionUserID'] . "' ";
            $resDb = $db->execute($strSQL);
            writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "", 0);
        } else {
            $strError = $error['invalid_password'];
            return false;
        }
    } else {
        // error
        $strError = $error['invalid_password'];
        return false;
    }
    return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    if (isset($_REQUEST['btnSave'])) {
        if ($bolCanEdit) {
            if (saveData($db, $strError)) {
                echo "<script>alert(\"" . $messages['password_changed'] . "\")</script>";
            } else {
                echo "<script>alert(\"$strError\")</script>";
            }
        }
    }
}
$strInitAction .= "
    document.formInput.dataOld.focus();
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>