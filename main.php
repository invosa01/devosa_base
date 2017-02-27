<?php
if (!DEFINED('CONFIGURATION_LOADED')) {
    include_once("global/configuration.php");
}
include_once('global/session.php');
include_once("global.php");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = APPLICATION_NAME;
$pageIcon = "images/icons/home.png";
$htmlContentFile = "templates/main.html";
if (!$GLOBALS['globalIsModuleLoaded']) {
    //jika daftar module belum ke load, maka load dahulu dari database
    //get Default Module, that is the first occurence module, order by sequence_no of table adm_module
    if ($GLOBALS['globalIdGroup'] != "") {
        $_SESSION['sessionModuleList'] = getDataModuleFromDatabase($GLOBALS['globalIdGroup']);
        if (count($_SESSION['sessionModuleList']) > 0) {
            if (isset($_SESSION['sessionDefaultModuleID']) && isset($_SESSION['sessionModuleList'][$_SESSION['sessionDefaultModuleID']])) {
                $_SESSION['sessionModuleID'] = $_SESSION['sessionModuleList'][$_SESSION['sessionDefaultModuleID']]['id_adm_module'];
                $_SESSION['sessionModuleName'] = $_SESSION['sessionModuleList'][$_SESSION['sessionDefaultModuleID']]['name'];
            } else {
                $_SESSION['sessionModuleID'] = $_SESSION['sessionModuleList'][1]['id_adm_module'];
                $_SESSION['sessionModuleName'] = $_SESSION['sessionModuleList'][1]['name'];
            }
        }
    }
}
if (!$GLOBALS['globalIsPrivilegesLoaded'])
    //jika data privileges user belum ke load, maka load dahulu dari database
    //get data privileges from database
{
    $_SESSION['sessionPrivileges'] = getDataPrivilegesFromDatabase($GLOBALS['globalIdGroup']);
}
$strFolder = ".";
foreach ($_SESSION['sessionPrivileges'] as $val) {
    if ($val['id_adm_module'] == $_SESSION['sessionModuleID']) {
        $strFolder = $val['folder'];
        break;
    }
}
$strFolder = ".";
foreach ($_SESSION['sessionPrivileges'] as $val) {
    if ($val['id_adm_module'] == $_SESSION['sessionModuleID']) {
        $strFolder = $val['folder'];
        break;
    }
}
$strPageLink = $strFolder . "/main.php";
header("location: $strPageLink");
exit();
?>