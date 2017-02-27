<?php
if (!DEFINED("CONFIGURATION_LOADED")) {
    include_once("configuration.php");
}
(isset($_SESSION['sessionLanguage'])) ? $dataLanguage = $_SESSION['sessionLanguage'] : $dataLanguage = DEFAULT_LANGUAGE;
switch ($dataLanguage) {
    case "id" :
        include_once("lang/id.words.inc");
        break;
    default:
        include_once("lang/en.words.inc");
}
function getWords($word)
{
    if (isset($GLOBALS['words'][$word])) {
        return $GLOBALS['words'][$word];
    } else if (isset($GLOBALS['messages'][$word])) {
        return $GLOBALS['messages'][$word];
    }
    if (isset($GLOBALS['error'][$word])) {
        return $GLOBALS['error'][$word];
    } else {
        return ucwords(strtolower($word));
    }
}

?>