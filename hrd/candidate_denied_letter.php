<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=recruitment_process_edit.php");
    exit();
}
$bolCanView = getUserPermission("recruitment_process_edit.php", $bolCanEdit, $bolCanDelete, $strError);
$strTemplateFile = getTemplate("candidate_denied_letter.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strPageTitle = "";
$strNo = "";
$strDate = date("d F Y");
$strName = "";
$strAddress = "";
$strCity = "";
$strZip = "";
$strDeptHead = "ROSYADI";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
    if ($strDataID != "") {
        // cari data recruitment
        $strSQL = "SELECT * FROM hrd_candidate WHERE id = '$strDataID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strName = "" . $rowDb['candidate_name'];
            $strAddress = "" . $rowDb['address'];
            $strCity = "" . $rowDb['city'];
            $strZip = "" . $rowDb['zip'];
        }
    }
}
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strTemplateFile);
$tbsPage->Show();
?>