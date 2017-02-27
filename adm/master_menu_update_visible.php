<?php
include_once('../global/session.php');
include_once('../global.php');
$dataPrivilege = getDataPrivileges("master_menu.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanEdit) {
    exit();
}
(isset($_GET['kode'])) ? $dataKode = $_GET['kode'] : $dataKode = "";
if ($dataKode == "") {
    exit();
}
$strData = "";
$db = new CdbClass();
if ($db->connect()) {
    $strSQL = "UPDATE adm_menu SET visible = not visible WHERE id_adm_menu = '$dataKode'";
    if ($db->execute($strSQL)) {
        $strSQL = "SELECT visible FROM adm_menu WHERE id_adm_menu = '$dataKode'";
        $db->execute($strSQL);
        if ($rowDb = $db->fetchrow()) {
            if ($rowDb['visible'] == 't') {
                $strData = "<a href=\"javascript:myClient.updateVisibleStatus(" . $dataKode . ")\"><img src=\"../images/publish.png\" border=\"0\" width=\"12\" height=\"12\" /></a>";
            } else {
                $strData = "<a href=\"javascript:myClient.updateVisibleStatus(" . $dataKode . ")\"><img src=\"../images/cross.png\" border=\"0\" width=\"12\" height=\"12\" /></a>";
            }
        }
    }
}
echo $strData;
?>