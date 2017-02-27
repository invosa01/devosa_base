<?php
session_start();
include("global.php");
$strTemplateFile = getTemplate("blank.html");
$strPageTitle = 'SMART-U';
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strTemplateFile);
$tbsPage->Show();
?>