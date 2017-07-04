<?php
include_once("../global.php");
include_once("../global/common_function.php");
//include_once("../includes/krumo/class.krumo.php");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords('System Administration');
$pageIcon = "../images/icons/home.png";
$strPageDesc = getWords("System administration page");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = "templates/main.html";
if (!$GLOBALS['globalIsModuleLoaded']) {
    //jika daftar module belum ke load, maka load dahulu dari database
    //get Default Module, that is the first occurence module, order by sequence_no of table adm_module
    if ($GLOBALS['globalIdGroup'] != "") {
        $_SESSION['sessionModuleList'] = getDataModuleFromDatabase($GLOBALS['globalIdGroup']);
        if (count($_SESSION['sessionModuleList']) > 0) {
            $_SESSION['sessionModuleID'] = $_SESSION['sessionModuleList'][0]['id_adm_module'];
            $_SESSION['sessionModuleName'] = $_SESSION['sessionModuleList'][0]['name'];
        }
    }
}
if (!$GLOBALS['globalIsPrivilegesLoaded'])
    //jika data privileges user belum ke load, maka load dahulu dari database
    //get data privileges from database
{
    $_SESSION['sessionPrivileges'] = getDataPrivilegesFromDatabase($GLOBALS['globalIdGroup']);
}
$strFavLinkNew = generateFavLinkNew();
//------------------------------------------------
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
//recursively re-order the menu
function reorderMenu($arrMenu, $id_menu = "", $menu_level, &$arrResult)
{
    $next_menu_level = $menu_level + 1;
    foreach ($arrMenu as $key => $value) {
        if ($value['menu_level'] == $menu_level && $value['parent_id_adm_menu'] == $id_menu) {
            $arrResult[] = $value;
            reorderMenu($arrMenu, $value['id_adm_menu'], $next_menu_level, $arrResult);
        }
    }
    return $arrResult;
}

function generateFavLinkNew()
{
    $strResult = '<div class="col-md-12">';
    if (is_array($_SESSION['sessionPrivileges'])) {
        reorderMenu($_SESSION['sessionPrivileges'], "", 0, $dataset);
        foreach ($dataset as $data) {
            if ($_SESSION['sessionModuleID'] == $data['id_adm_module']) {
                if ($data['menu_level'] == 0) {
                    $strResult .= '</div>';
                    $strResult .= '</div>';
                    $strResult .= '<div class="col-md-4">';
                    $strResult .= '<h3><img src="../images/icons/' . $data['icon_file'] . '" width="32" height="32" border="0" />&nbsp;' . getWords(
                            $data['menu_name']
                        ) . '</h3>';
                    $strResult .= '<div class="list-group">';
                } else {
                    $strResult .= '<a href="' . $data['php_file'] . '" class="list-group-item">';
                    $strResult .= '<h5 class="list-group-item-heading"><img src="../images/icons/' . $data['icon_file'] . '" width="20" height="20" border="0" />&nbsp;' . getWords(
                            $data['menu_name']
                        ) . '</h5>';
                    $strResult .= '<p class="list-group-item-text">' . $data['note'] . '</p>';
                    $strResult .= '</a>';
                }
            }
        }
    }
    $strResult .= '</div>';
    return $strResult;
}

?>