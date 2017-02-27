<?php
session_start();
include_once('global.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=loan_product.php");
    exit();
}
$bolCanView = getUserPermission("loan_product.php", $bolCanEdit, $bolCanDelete, $strError);
$strTemplateFile = getTemplate("loan_product.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    $intRows = 0;
    $strResult = "";
    $strSQL = "SELECT * FROM hrd_loan_product ";
    $strSQL .= "WHERE 1=1 $strKriteria ORDER BY $strOrder product_type ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= "<tr valign=top>\n";
        $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
        $strResult .= "  <td nowrap><input type=hidden name=detailProduct$intRows value=\"" . $rowDb['productType'] . "\">" . $rowDb['productType'] . "&nbsp;</td>";
        $strResult .= "  <td nowrap align=right><input type=hidden name=detailPrice$intRows value=\"" . (float)$rowDb['price'] . "\">" . standardFormat(
                $rowDb['price']
            ) . "&nbsp;</td>";
        $strResult .= "  <td nowrap align=center><a href='javascript:editData($intRows)'>" . $words['edit'] . "</a>&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $error;
    $strError = "";
    (isset($_REQUEST['dataProduct'])) ? $strDataProduct = $_REQUEST['dataProduct'] : $strDataProduct = "";
    (isset($_REQUEST['dataPrice'])) ? $strDataPrice = $_REQUEST['dataPrice'] : $strDataPrice = "0";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    // cek validasi -----------------------
    if ($strDataProduct == "") {
        $strError = $error['empty_code'];
        return false;
    } else {
        ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
        if (isDataExists("hrdLoanProduct", product_type, $strDataProduct, $strKriteria)) {
            $strError = $error['duplicate_code'] . "  -> $strDataProduct";
            return false;
        }
    }
    if (!is_numeric($strDataPrice)) {
        $strDataPrice = 0;
    }
    // simpan data -----------------------
    if ($strDataID == "") {
        // data baru
        $strSQL = "INSERT INTO hrd_loan_product (created,created_by,modified_by, product_type, price) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "',";
        $strSQL .= "'" . $_SESSION['sessionUserID'] . "', '$strDataProduct', '$strDataPrice') ";
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataProduct", 0);
    } else {
        $strSQL = "UPDATE hrd_loan_product ";
        $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "product_type = '$strDataProduct', price = '$strDataPrice' ";
        $strSQL .= "WHERE id = '$strDataID' ";
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataProduct", 0);
    }
    $resExec = $db->execute($strSQL);
    return true;
} // saveData
// fungsi untuk menghapus data
function deleteData($db)
{
    global $_REQUEST;
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $strSQL = "DELETE FROM hrd_loan_product WHERE id = '$strValue' ";
            $resExec = $db->execute($strSQL);
            $i++;
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
    }
} //deleteData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    if (isset($_REQUEST['btnSave'])) {
        if ($bolCanEdit) {
            if (!saveData($db, $strError)) {
                echo "<script>alert(\"$strError\")</script>";
            }
        }
    } else if (isset($_REQUEST['btnDelete'])) {
        if ($bolCanDelete) {
            deleteData($db);
        }
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $intTotalData);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
}
$strInitAction .= "    document.formInput.dataProduct.focus();   ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>