<?php
include_once("configuration.php");
//Task:
// - Handle all session object and variable
// - Save to global variable usually the variables are begin with $global.....bla...bla...bla
if (!session_id()) {
    session_start();
}
/* FIX: Kalau file ada di basepath, akan meload configuration.php di Base Folder.
 * Maka di cek untuk selalu load yang ada di folder global
 * OLD CODE: if (!DEFINED('CONFIGURATION_LOADED')) include_once("configuration.php");
 */
$curPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = getLocalSessionValue('sessionBasePath');
if ($curPath == $basePath) {
    if (!DEFINED('CONFIGURATION_LOADED')) {
        include_once("global/configuration.php");
    } else if (!DEFINED('CONFIGURATION_LOADED')) {
        include_once("configuration.php");
    }
}
DEFINE("VALID_APPLICATION", 1);
//get current folder of running page
$currentPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = getLocalSessionValue('sessionBasePath');
if ($currentPath == $basePath) {
    if (!isset($_SESSION['sessionUserID']) || !isset($_SESSION['sessionIdGroup'])) {
        //header("location:".LIVE_SITE."index.php?dataPage=".$_SERVER['PHP_SELF']);
        //header('location:http://192.168.1.24');
        //header('location:'.$_SERVER['PHP_SELF']);
        header('location:' . LIVE_SITE);
        exit();
    } else {
        $globalRelativeFolder = "./";
    }
} else {
    if ((!isset($_SESSION['sessionUserID']) || !isset($_SESSION['sessionIdGroup'])))// && !preg_match("/\bcandidate_edit.php\b/",$_SERVER['SCRIPT_NAME']))
    {
        //header("location:".LIVE_SITE."index.php?dataPage=".$_SERVER['PHP_SELF']);
        //header('location:http://192.168.1.24');
        //header('location:'.$_SERVER['PHP_SELF']);
        header('location:' . LIVE_SITE);
        exit();
    } else {
        if (($basePath == "/") || ($basePath == "\\")) {
            $deepLevel = substr_count($currentPath, "/");
        } else {
            $deepLevel = substr_count($currentPath, "/") - substr_count($basePath, "/");
        }
        //if (preg_match("/\bcandidate_edit.php\b/",$_SERVER['SCRIPT_NAME']) && (!isset($_SESSION['sessionUserID']) || !isset($_SESSION['sessionIdGroup']))) $deepLevel-=2;
        if ($deepLevel > 0) {
            $globalRelativeFolder = str_repeat("../", $deepLevel);
        } else {
            $globalRelativeFolder = "./";
        }
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
//returning the value of SESSION key
function getLocalSessionValue($key)
{
    if (isset($_SESSION[$key])) {
        return $_SESSION[$key];
    } else {
        return "";
    }
}

?>
