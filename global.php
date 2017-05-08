<?php
define('GLOBAL_MODULE_LOADED', true);
if (!DEFINED('CONFIGURATION_LOADED')) {
    include_once("global/configuration.php");
}
include_once('global/session.php');
include_once("global/common_variable.php");
include_once("includes/dbclass/dbclass.php");
include_once('includes/tbsclass/tbs_class.php');
include_once('global/words.php');
include_once('global/helps.php');
include_once("includes/model/model.php");
function getDataPrivilegesFromDatabase($idGroup)
{
    //get from database
    (isset($_SESSION['sessionLanguage'])) ? $dataLanguage = $_SESSION['sessionLanguage'] : $dataLanguage = DEFAULT_LANGUAGE;
    $db = new CdbClass;
    if ($db->connect()) {
        if ($idGroup != "") {
            $strSQL = "
        SELECT c.view, c.edit, c.delete , c.approve, c.acknowledge, c.check, c.id_adm_menu, 
               c.page_name, c.menu_level, c.php_file, c.icon_file, c.parent_id_adm_menu, c.note,
               f.sequence_no_module, f.sequence_no_menu, f.menu_name, f.id_adm_module, f.folder
        FROM (SELECT gm.view, gm.edit, gm.delete , gm.approve, gm.acknowledge, gm.check, m.id_adm_menu, m.page_name, m.menu_level, 
                     m.php_file, m.icon_file, m.parent_id_adm_menu, m.note
             FROM adm_group_menu AS gm INNER JOIN adm_menu AS m
                ON gm.id_adm_menu = m.id_adm_menu AND m.visible = 't'
             WHERE gm.id_adm_group = $idGroup) AS c
          INNER JOIN 
          (SELECT mn.id_adm_menu, d.sequence_no AS sequence_no_module, mn.sequence_no AS sequence_no_menu,
                  CASE WHEN w.translation IS NULL THEN mn.name ELSE w.translation END AS menu_name, 
                  d.id_adm_module, d.folder
            FROM adm_menu AS mn INNER JOIN adm_module AS d 
                  ON d.id_adm_module = mn.id_adm_module
                LEFT JOIN adm_words AS w
                  ON w.word = mn.name AND w.language = '$dataLanguage'
            WHERE d.visible = 't' AND mn.visible = 't') AS f 
          ON f.id_adm_menu = c.id_adm_menu 
          ORDER BY f.sequence_no_module, c.menu_level, f.sequence_no_menu";
            $res = $db->execute($strSQL);
            while ($rowDb = $db->fetchrow($res, "ASSOC")) {
                $menuPrivileges[] = $rowDb;
            }
        }
    }
    $db->close();
    return $menuPrivileges;
}

function getDataModuleFromDatabase($idGroup)
{
    //get from database
    $db = new CdbClass;
    $arrModule = [];
    if ($db->connect()) {
        $strSQL = "
        SELECT a.id_adm_module, a.name
          FROM adm_module AS a
            INNER JOIN 
            (SELECT id_adm_module FROM adm_menu 
              WHERE id_adm_menu IN (SELECT id_adm_menu FROM adm_group_menu WHERE id_adm_group = " . $idGroup . ")
              GROUP BY id_adm_module) AS b
                ON a.id_adm_module = b.id_adm_module
          ORDER BY a.sequence_no";
        $res = $db->execute($strSQL);
        while ($row = $db->fetchrow($res)) {
            $arrModule[$row['id_adm_module']] = $row;
        }
    }
    $db->close();
    return $arrModule;
}

function getDataPrivileges(
    $strPage,
    &$bolView = false,
    &$bolEdit = false,
    &$bolDelete = false,
    &$bolApprove = false,
    &$bolCheck = false,
    &$bolAcknowledge = false
) {
    $strPage = strtolower($strPage);
    $value = [];
    if (!isset($_SESSION['sessionPrivileges'])) {
        //jika session untuk menyimpan data privileges hilang, maka query lagi ke server database
        (isset($_SESSION['sessionIdGroup'])) ? $idGroup = $_SESSION['sessionIdGroup'] : $idGroup = "";
        $_SESSION['sessionPrivileges'] = getDataPrivilegesFromDatabase($idGroup);
    }
    $result = null;
    foreach ($_SESSION['sessionPrivileges'] as $value) {
        if (strtolower($value["php_file"]) == $strPage) {
            $result = $value;
            $result['bolView'] = $bolView = $value["view"] == 't';
            $result['bolEdit'] = $bolEdit = $value["edit"] == 't';
            $result['bolDelete'] = $bolDelete = $value["delete"] == 't';
            $result['bolApprove'] = $bolApprove = $value["approve"] == 't';
            $result['bolAcknowledge'] = $bolAcknowledge = $value["acknowledge"] == 't';
            $result['bolCheck'] = $bolCheck = $value["check"] == 't';
            break;
        }
    }
    return $result;
} //end of getUserPermission
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

function dump($vars)
{
    echo '<pre>';
    var_dump($vars);
    echo '</pre>';
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
function getDateSettingFromDatabase()
{
    //get from database
    $db = new CdbClass;
    $arrDateSetting = [];
    if ($db->connect()) {
        $strSQL = "
        SELECT id, date_format, date_sparator, php_format, javascript_format, html_format, pos_year, pos_month, pos_day
         FROM date_setting WHERE active = TRUE ORDER BY id DESC LIMIT 1";
        $res = $db->execute($strSQL);
        while ($row = $db->fetchrow($res)) {
            $arrDateSetting = $row;
        }
    }
    $db->close();
    return $arrDateSetting;
}

if (function_exists('loadStandardCore') === false) {
    /**
     * Load standard core library.
     *
     * @return void
     */
    function loadStandardCore()
    {
        # Lazy load.
        require_once __DIR__ . '/src/System/Standards.php';
        doIncludes(
            [
                'src/System/Sessions.php',
                'src/System/Database.php',
            ]
        );
    }
}
?>
