<?php
if (!DEFINED('CONFIGURATION_LOADED')) {
    include_once("global/configuration.php");
}
include_once("global/common_variable.php");
include_once("includes/dbclass/dbclass.php");
include_once('includes/tbsclass/tbs_class.php');
include_once('global/words.php');
include_once('global/helps.php');
include_once("includes/model/model.php");
function setDataToCookie($key, $value)
{
    setcookie($key, $value, time() + +3600000);
}

function getDataFromCookie($key)
{
    (isset($_COOKIE[$key])) ? $currentCookieData = $_COOKIE[$key] : $currentCookieData = null;
    return $currentCookieData;
}

//function ini untuk proses go back to previous page, dengan menyimpan URL REFERRER sebelumnya, dimasukkan di COOKIE
function pushUrlReferrer($force = false)
{
    //hanya menyimpan yang url referer
    //sehingga kalo page di refresh tidak disimpan
    if (isset($_SERVER["HTTP_REFERER"])) {
        if (strpos($_SERVER["HTTP_REFERER"], $_SERVER["PHP_SELF"]) === false) {
            setDataToCookie("COOKIE_URL_REFERRER", $_SERVER["HTTP_REFERER"]);
        }
    }
}

function popUrlReferrer()
{
    return getDataFromCookie("COOKIE_URL_REFERRER");
}

//returning the value of REQUEST key
//$defaultValue = value returned if no variable found
function getRequestValue($key, $defaultValue = "")
{
    return (isset($_REQUEST[$key])) ? $_REQUEST[$key] : $defaultValue;
}

//returning the value of POST key
//$defaultValue = value returned if no variable found
function getPostValue($key, $defaultValue = "")
{
    return (isset($_POST[$key])) ? $_POST[$key] : $defaultValue;
}

//returning the value of GET key
//$defaultValue = value returned if no variable found
function getGetValue($key, $defaultValue = "")
{
    return (isset($_GET[$key])) ? $_GET[$key] : $defaultValue;
}

//returning the value of SESSION key
//$defaultValue = value returned if no variable found
function getSessionValue($key, $defaultValue = "")
{
    return (isset($_SESSION[$key])) ? $_SESSION[$key] : $defaultValue;
}

// fungsi untuk mengambil template
// input: template dasar, bolLang jika true artinya tergantung bahasa, jika false, artinya tidak
// output: template lengkap, plus identitas bahasa sesuai session
function getTemplate($page)
{
    $strTpl = "./templates/";
    $strTpl .= $page;
    return $strTpl;
} //end of getTemplate
?>
