<?php
if (!session_id()) {
    session_start();
}
if (defined('CONFIGURATION_LOADED') === false) {
    include_once("configuration.php");
}
function getLocalSessionValue($key)
{
    if (isset($_SESSION[$key])) {
        return $_SESSION[$key];
    } else {
        return "";
    }
}

$curPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = getLocalSessionValue('sessionBasePath');
$loginIndexScriptPagePath = BASE_URL . '/' . INDEX_PAGE;
DEFINE("VALID_APPLICATION", 1);
//get current folder of running page
$currentPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = getLocalSessionValue('sessionBasePath');
if ((!isset($_SESSION['sessionUserID']) || !isset($_SESSION['sessionIdGroup'])) and $_SERVER['SCRIPT_NAME'] !== $loginIndexScriptPagePath) {
    header('location: ' . LIVE_SITE);
}
if ((isset($_SESSION['sessionUserID']) || isset($_SESSION['sessionIdGroup'])) and $_SERVER['SCRIPT_NAME'] === $loginIndexScriptPagePath) {
    header('location: ' . LIVE_SITE . MAIN_PAGE);
}
if ($currentPath == $basePath) {
    $globalRelativeFolder = "./";
} else {
    if (($basePath == "/") || ($basePath == "\\")) {
        $deepLevel = substr_count($currentPath, "/");
    } else {
        $deepLevel = substr_count($currentPath, "/") - substr_count($basePath, "/");
    }
    if ($deepLevel > 0) {
        $globalRelativeFolder = str_repeat("../", $deepLevel);
    } else {
        $globalRelativeFolder = "./";
    }
}
$globalIdGroup = getLocalSessionValue('sessionIdGroup');
$globalUserName = getLocalSessionValue('sessionUserName');
$globalUserID = getLocalSessionValue('sessionUserID');
//GET LAST URL, and Save to Global Variable named URL_REFERER
if (isset($_REQUEST['URL_REFERER'])) {
    $GLOBALS['URL_REFERER'] = $_REQUEST['URL_REFERER'];
} else if (isset($_SERVER['HTTP_REFERER'])) {
    $GLOBALS['URL_REFERER'] = $_SERVER['HTTP_REFERER'];
} else {
    $GLOBALS['URL_REFERER'] = $_SERVER['PHP_SELF'];
}
if (isset($_GET['changeLanguageTo'])) {
    //refresh language
    //to refresh menu from database, $_SESSION['sessionPrivileges'] must be unset
    unset($_SESSION['sessionPrivileges']);
    $_SESSION['sessionLanguage'] = $_GET['changeLanguageTo'];
}
(isset($_SESSION['sessionLanguage'])) ? $globalLanguage = $_SESSION['sessionLanguage'] : $globalLanguage = DEFAULT_LANGUAGE;
(isset($_SESSION['sessionModuleID'])) ? $globalIsModuleLoaded = true : $globalIsModuleLoaded = false;
if (isset($_GET['moduleID'])) {
    $_SESSION['sessionModuleID'] = $_GET['moduleID'];
}
(isset($_SESSION['sessionPrivileges'])) ? $globalIsPrivilegesLoaded = true : $globalIsPrivilegesLoaded = false;
return $_SESSION;
?>
