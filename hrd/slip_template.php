<?php
include_once('global.php');
//include_once(getTemplate("words.inc"));
$strTemplateFile = getTemplate("slip_template1.html");
$strNow = date("d F Y");
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strTemplateFile);
$tbsPage->Show();
?>