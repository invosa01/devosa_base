<?php
include_once('../global/session.php');
include_once('../global.php');
include_once("../global/common_function.php");
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
    die(getWords("view denied"));
}
$do = getGetValue('do', false);
if ($do == 'vacuum') {
    $db = new CDbClass;
    if ($db->connect()) {
        $strSQL = "VACUUM FULL ANALYZE;";
        $db->execute($strSQL);
        die(getWords("Vacuuming database completed.|Re-indexing database in progress..."));
    }
    die(getWords("Failed to vacuum database.|Re-indexing database in progress..."));
} else if ($do == 'reindex') {
    $db = new CDbClass;
    if ($db->connect()) {
        $strSQL = "REINDEX DATABASE \"" . DB_NAME . "\";";
        $db->execute($strSQL);
        die(getWords("Re-indexing database completed."));
    }
    die(getWords("Failed to re-index database."));
} else {
    $strMessage1 = getWords("Please wait, database was optimized");
    $strMessage2 = getWords("This may take several minutes...");
    $tbsPage = new clsTinyButStrong;
    //write this variable in every page
    $strPageTitle = getWords($dataPrivilege['menu_name']);
    if (!$dataPrivilege['icon_file']) {
        $dataPrivilege['icon_file'] = 'blank.png';
    }
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
    $strPageDesc = getWords("database optimizer");
    $pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
    $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
    //------------------------------------------------
    //Load Master Template
    $tbsPage->LoadTemplate("../templates/master.html");
    $tbsPage->Show();
}
?>