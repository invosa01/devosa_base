<?php
if (!DEFINED("CONFIGURATION_LOADED")) {
    include_once("configuration.php");
}
(isset($_SESSION['sessionLanguage'])) ? $dataLanguage = $_SESSION['sessionLanguage'] : $dataLanguage = DEFAULT_LANGUAGE;
switch ($dataLanguage) {
    case "id" :
        include_once("lang/id.helps.inc");
        break;
    default:
        include_once("lang/en.helps.inc");
}
function getHelps($help)
{
    if (isset($GLOBALS['helpDictionary'][$help])) {
        return $GLOBALS['helpDictionary'][$help];
    } else {
        return $GLOBALS['helpDictionary']["no help"];
    }
}

?>