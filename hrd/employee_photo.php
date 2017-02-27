<?php
include_once('global.php');
include_once('../global/class.Thumbnail.php');
$strDummyFile = "photos/dummy.gif";
$intMaxWidth = 250;
$strFile = $strDummyFile;
$strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
// connect ke db, cari file
$db = new CdbClass();
if ($db->connect() && $strDataID != "") {
    // cari info di database
    $strSQL = "SELECT photo,link_id FROM hrd_employee WHERE id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        $strPhotos = $rowDb['photo'];
        if ($strPhotos == "" && $rowDb['link_id'] != "") { // gak ada, coba cari dari data yang belum diapprove
            $strSQL = "SELECT photo FROM hrd_employee WHERE id = '" . $rowDb['link_id'] . "' ";
            $resDb = $db->execute($strSQL);
            if ($rowDb = $db->fetchrow($resDb)) {
                $strPhotos = $rowDb['photo'];
            }
        }
        if ($strPhotos == "") { // gak ada
            $strFile = $strDummyFile;
        } else {
            $strFile = "photos/$strPhotos";
            if (!file_exists($strFile)) {
                $strFile = $strDummyFile;
            }
        }
    }
} else {
    $strFile = $strDummyFile;
}
// tampilkan file
$imgO = new Thumbnail($strFile, $intMaxWidth, 0, 0);
$imgO->show();
?>