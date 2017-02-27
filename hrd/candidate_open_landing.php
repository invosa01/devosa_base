<?php
include_once("../global.php");
include_once("../global/common_function.php");
include_once("../global/common_data.php");
//inisialisasi
$strPageTitle = "PatraSK - Recruitment";
$strCopyright = COPYRIGHT;
$dataMRF = "";
$linkOpenRec = "candidate_edit_published.php";
//daftar fungsi
//main program
$arrayMRF = getDataListMRF();
$dataMRF .= "<tr>";
$dataMRF .= "<th align=center colspan=2>Welcome to PatraSK!";
if (count($arrayMRF)) {
    $dataMRF .= "<br>We are currently opening recruitment for positions listed below:</th>";
    $dataMRF .= "</tr>";
    $dataMRF .= "<table border=1>";
    $dataMRF .= "<tr>";
    $dataMRF .= "<td align=center>Position</td><td align=center>MRF No.</td>";
    $dataMRF .= "</tr>";
    $dataMRF .= "<tr>";
    foreach ($arrayMRF as $indivMRF) {
        $tempId = $indivMRF['value'];
        $dataMRF .= "<td align=center width='50%'>";
        $db = new CdbClass;
        $db->connect() or die("DB ERROR");
        $strSQL = "SELECT position_code, department_code FROM hrd_recruitment_need WHERE id = $tempId";
        $resDb = $db->execute($strSQL);
        $rowDb = $db->fetchrow($resDb);
        $tempDept = $rowDb['department_code'];
        $tempPos = $rowDb['position_code'];
        $strSQL = "SELECT position_name FROM hrd_position WHERE position_code = '$tempPos'";
        $resDb = $db->execute($strSQL);
        $rowDb = $db->fetchrow($resDb);
        $tempStr = $rowDb['position_name'];
        $tempStr .= " - ";
        $strSQL = "SELECT department_name FROM hrd_department WHERE department_code = '$tempDept'";
        $resDb = $db->execute($strSQL);
        $rowDb = $db->fetchrow($resDb);
        $tempStr .= $rowDb['department_name'];
        $tempStr .= "<br>";
        $tempLoc = $tempPos . " - " . $tempDept;
        $tempStr .= $tempLoc;
        $dataMRF .= "<a href=\"$linkOpenRec?MrfId=$tempId&MrfPos=$tempLoc\">" . $tempStr . "</a>";
        $dataMRF .= "</td>";
        $dataMRF .= "<td align=center width='50%'>";
        $dataMRF .= "<a href=\"$linkOpenRec?MrfId=$tempId&MrfPos=$tempLoc\">" . $indivMRF['text'] . "</a>";
        $dataMRF .= "</td>";
    }
    $dataMRF .= "</table>";
    $dataMRF .= "</tr>";
} else {
    $dataMRF .= "<br>We are terribly sorry, but currently we don't have any open position. You can still drop your CV here, though!</th>";
    $dataMRF .= "</tr>";
}
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate("templates/candidate_open_landing.html");
$tbsPage->Show();
?>
