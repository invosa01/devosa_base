<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=data_structural.php");
    exit();
}
$bolCanView = getUserPermission("data_structural.php", $bolCanEdit, $bolCanDelete, $strError);
$strTemplateFile = getTemplate("data_structural.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
$strInitCalendar = "";
$strDataEmployee = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $intDefaultWidth;
    global $strEmptyOption;
    $intRows = 0;
    $intShown = 0;
    $intAdd = 20; // maksimum tambahan
    $strResult = "";
    $strNow = date("Y-m-d");
    $strSQL = "SELECT * FROM hrd_structural ";
    $strSQL .= "ORDER BY $strOrder number ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $intShown++;
        $strResult .= "<tr valign=top id=\"detailRows$intRows\">\n";
        $strResult .= "  <td nowrap><input type=hidden name='detailID$intRows' value=\"" . $rowDb['id'] . "\">\n";
        $strResult .= "  $intRows.</td>";
        $strResult .= "  <td nowrap>" . getPositionList(
                $db,
                "detailPosition$intRows",
                $rowDb['position'],
                $strEmptyOption
            ) . "</td>";
        $strResult .= "  <td nowrap><input type=text size=20 maxlength=50 name=detailNote$intRows value=\"" . $rowDb['note'] . "\"></td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    // tambahkan dengan data kosong
    for ($i = 1; $i <= $intAdd; $i++) {
        $intRows++;
        if ($intRows == 1) {
            $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
            $intShown++;
            $strDisabled = "";
        } else {
            $strResult .= "<tr valign=top  id=\"detailRows$intRows\" style=\"display:none\">\n";
            $strDisabled = "disabled";
        }
        $strResult .= "  <td nowrap>$intRows.</td>";
        $strResult .= "  <td nowrap>" . getPositionList(
                $db,
                "detailPosition$intRows",
                "",
                $strEmptyOption,
                "",
                $strDisabled
            ) . "</td>";
        $strResult .= "  <td nowrap><input type=text size=20 maxlength=50 name=detailNote$intRows  $strDisabled></td>";
        $strResult .= "</tr>\n";
    }
    // tambahkan hidden data
    $strResult .= "<input type=hidden name=maxDetail value=$intRows>";
    $strResult .= "<input type=hidden name=numShow value=$intShown>";
    return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $error;
    $strError = "";
    $intNumber = 0;
    //hapus dulu semua data
    $strSQL = "DELETE FROM hrd_structural ";
    $resDb = $db->execute($strSQL);
    (isset($_REQUEST['maxDetail'])) ? $intMax = $_REQUEST['maxDetail'] : $intMax = 0;
    for ($i = 1; $i <= $intMax; $i++) {
        (isset($_REQUEST['detailID' . $i])) ? $strID = $_REQUEST['detailID' . $i] : $strID = "";
        (isset($_REQUEST['detailPosition' . $i])) ? $strPosition = $_REQUEST['detailPosition' . $i] : $strPosition = "";
        (isset($_REQUEST['detailNote' . $i])) ? $strNote = $_REQUEST['detailNote' . $i] : $strNote = "";
        $bolEmpty = true;
        $bolEmpty = ($bolEmpty && ($strPosition == ""));
        if (!$bolEmpty) { // insert new data
            $intNumber++;
            $strSQL = "INSERT INTO hrd_structural (created,modified_by, created_by, ";
            $strSQL .= "number, position,  note) ";
            $strSQL .= "VALUES(now(), '" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
            $strSQL .= "'$intNumber', '$strPosition', '$strNote') ";
            $resDb = $db->execute($strSQL);
        }
    }
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "", 0);
    return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    if ($bolCanEdit) {
        if (isset($_REQUEST['btnSave'])) {
            if (!saveData($db, $strDataID, $strError)) {
                echo "<script>alert(\"$strError\")</script>";
            }
        }
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $intTotalData);
    } else {
        showError("view_denied");
    }
}
$strInitAction .= "";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>