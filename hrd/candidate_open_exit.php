<?php
include_once("../global.php");
include_once("../global/common_function.php");
include_once("../global/common_data.php");
//inisialisasi
$strPageTitle = "PatraSK - Recruitment";
$strCopyright = COPYRIGHT;
$dataGoodBye = "";
$linkOpenRec = "http://hr.patra-sk.com/hrd/candidate_edit_published.php";
//daftar fungsi
//main program
$dataGoodBye .= "<tr>
	<th>Thank you for applying in PatraSK!</th>
  </tr><tr><th>&nbsp;</th></tr>";
$arrayMRF = getDataListMRF();
$dataGoodBye .= "<table border=0>";
foreach ($arrayMRF as $temp) {
    $dataGoodBye .= "<tr><td>&nbsp;</td></tr>";
}
$dataGoodBye .= "</table>";
session_start();
session_destroy();
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate("templates/candidate_open_exit.html");
$tbsPage->Show();
?>
