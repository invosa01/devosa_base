<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=shift_group.php");
    exit();
}
$bolCanView = getUserPermission("employee_search.php", $bolCanEdit, $bolCanDelete, $strError);
$strTemplateFile = getTemplate("employee_onsite.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strDataDetailSearch = "";
$strHidden = "";
$strGroupName = "";
$intTotalData = 0;
$intTotalDataSearch = 0;
$strFilterEmployee = "";
$strFilterSection = "";
$strKriteria = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data member dari Group
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $strDataID;
    $intRows = 0;
    $strResult = "";
    $strSQL = "SELECT id, employee_id, employee_name FROM hrd_employee ";
    $strSQL .= "WHERE active = '1' AND flag=0 AND onsite = 't' ORDER BY $strOrder employee_name ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= "<tr valign=top>\n";
        $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
        $strResult .= "  <td nowrap>" . $rowDb['employee_id'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // showData
// menampilkan daftar employee hasil pencarian
function getDataSearch($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $strDataID;
    $intRows = 0;
    $strResult = "";
    if ($strKriteria == "") {
        $strKriteria = "AND 1=2"; // biar kosong, gak boleh tampil semua
    }
    $strSQL = "SELECT id,employee_id, employee_name FROM hrd_employee ";
    $strSQL .= "WHERE active=1 AND flag=0 AND onsite = 'f' $strKriteria ORDER BY $strOrder employee_name ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= "<tr valign=top>\n";
        $strResult .= "  <td><input type=checkbox name='chkIDSearch$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
        $strResult .= "  <td nowrap>" . $rowDb['employee_id'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    return $strResult;
} // showDataSearch
// fungsi untuk menyimpan data, untuk satu data saja
function addData($db, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $error;
    $strError = "";
    (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
    // cek validasi -----------------------
    if ($strDataEmployee == "") {
        $strError = $error['empty_code'];
        return false;
    }
    // simpan data -----------------------
    // cari data employee dulu, apakah ada atau tidak
    $strSQL = "UPDATE hrd_employee SET onsite = 't' WHERE employee_id = '$strDataEmployee' ";
    $resExec = $db->execute($strSQL);
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "add $strDataEmployee ", 0);
    return true;
} // addData
// fungsi untuk menambahkan beberapa data sekaligus
function addDataMore($db)
{
    global $_REQUEST;
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 11) == 'chkIDSearch') {
            $strSQL = "UPDATE hrd_employee SET onsite = 't' WHERE id = '$strValue' ";
            $resExec = $db->execute($strSQL);
            $i++;
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "add $i data", 0);
    }
} //addDataMore
// fungsi untuk menghapus data
function deleteData($db)
{
    global $_REQUEST;
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $strSQL = "UPDATE hrd_employee SET onsite = 'f' WHERE id = '$strValue' ";
            $resExec = $db->execute($strSQL);
            $i++;
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "remove $i data", 0);
    }
} //deleteData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    // ambil kriteria
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    (isset($_REQUEST['filterSection'])) ? $strDataSection = $_REQUEST['filterSection'] : $strDataSection = "";
    (isset($_REQUEST['filterEmployee'])) ? $strDataEmployee = $_REQUEST['filterEmployee'] : $strDataEmployee = "";
    // proses action, jika ada form submission
    if (isset($_REQUEST['btnAdd'])) { // tambahkan satu employee
        if ($bolCanEdit) {
            if (addData($db, $strError) == false) {
                if ($strError != "") {
                    echo "<script>alert('$strError')</script>";
                }
            }
        }
    } else if (isset($_REQUEST['btnDelete'])) { // hapus kenaggotaan member dari group
        if ($bolCanDelete) {
            deleteData($db);
        }
    } else if (isset($_REQUEST['btnSearch'])) { // search employee data
        if ($strDataEmployee != "") {
            $strKriteria .= "AND (employee_id like '%$strDataEmployee%' ";
            $strKriteria .= "OR UPPER(employee_name) like '%" . strtoupper($strDataEmployee) . "%' ) ";
        }
        if ($strDataSection != "") {
            $strKriteria .= "AND section_code = '$strDataSection' ";
        }
        $strDataDetailSearch = getDataSearch($db, $intTotalDataSearch, $strKriteria);
    } else if (isset($_REQUEST['btnAddMore'])) { // tambahkan anggota group, banyak
        if ($bolCanEdit) {
            addDataMore($db);
        }
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $intTotalData);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
    // tampilkan input untuk search employee
    $intWidthPx = 150;
    $strFilterEmployee = "<input type=text name=\"filterEmployee\" id=\"filterEmployee\" size=20 style=\"width:$intWidthPx\" value=\"$strDataEmployee\">";
    $strFilterSection = getSectionList(
        $db,
        "filterSection",
        $strDataSection,
        $strEmptyOption,
        "",
        "style=\"width:$intWidthPx\""
    );
    // hidden value
    $strHidden .= "<input type=hidden name='filterEmployee' value='$strDataEmployee'>";
    $strHidden .= "<input type=hidden name='filterSection' value='$strDataSection'>";
}
$strInitAction .= " document.formInput.dataEmployee.focus();
    init();
    onCodeBlur();
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>