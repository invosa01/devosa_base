<?php
include_once('../global/session.php');
include_once('global.php');
$dataPrivilege = getDataPrivileges(
    "shift_schedule.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView) {
    die(accessDenied($_SERVER['HTTP_REFERER']));
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
//----------------------------------------------------------------------
//--- DAFTAR FUNGSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    $intRows = 0;
    $strResult = "";
    $strSQL = "SELECT t1.*, count(t2.id) FROM hrd_shift_group AS t1 LEFT JOIN hrd_shift_group_member AS t2 ";
    $strSQL .= "ON t1.group_name = t2.group_name ";
    $strSQL .= "WHERE 1=1 $strKriteria GROUP BY t1.group_name ORDER BY $strOrder group_name ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        // cari jumlah member dari group shift
        $intMember = 0;
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_shift_group_member ";
        $strSQL .= "WHERE \"id_group\" = " . $rowDb['id'];
        $resTmp = $db->execute($strSQL);
        if ($rowTmp = $db->fetchrow($resTmp)) {
            if (is_numeric($rowTmp['total'])) {
                $intMember = $rowTmp['total'];
            }
        }
        $strResult .= "<tr valign=top>\n";
        $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
        $strResult .= "  <td><input type=hidden name=detailName$intRows value=\"" . $rowDb['group_name'] . "\" disabled>" . $rowDb['group_name'] . "&nbsp;</td>";
        $strResult .= "  <td><input type=hidden name=detailNote$intRows value=\"" . $rowDb['note'] . "\" disabled>" . $rowDb['note'] . "&nbsp;</td>";
        $strResult .= "  <td nowrap align=right>" . $intMember . "</a>&nbsp;</td>";
        $strResult .= "  <td nowrap align=center id=togleText>&nbsp;<a href=\"javascript:showDetail($intRows);\">" . $words['view member'] . "</a>&nbsp;</td>";
        $strResult .= "  <td nowrap align=center><a href=\"shift_group_member.php?dataID=" . $rowDb['id'] . "\">" . $words['edit member'] . "</a>&nbsp;</td>";
        $strResult .= "  <td nowrap align=center><a href='javascript:editData($intRows)'>" . $words['edit'] . "</a>&nbsp;</td>";
        $strResult .= "</tr>\n";
        // tampilkan row untuk detailnya
        $strResult .= "<tr valign=top style=\"display:none\" id=\"detail$intRows\">\n";
        $strResult .= " <td colspan=7 bgColor=#FFFFCC><div id=\"detailData$intRows\"></div></td>";
        $strResult .= "</tr>\n";
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $error;
    $strError = "";
    (isset($_REQUEST['dataName'])) ? $strdataName = $_REQUEST['dataName'] : $strdataName = "";
    (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    // cek validasi -----------------------
    if ($strdataName == "") {
        $strError = $error['empty_code'];
        return false;
    } else {
        ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
        if (isDataExists("hrd_shift_group", "group_name", $strdataName, $strKriteria)) {
            $strError = $error['duplicate_code'] . "  -> $strdataName";
            return false;
        }
    }
    // simpan data -----------------------
    if ($strDataID == "") {
        // data baru
        $strSQL = "INSERT INTO hrd_shift_group (created,created_by,modified_by, ";
        $strSQL .= "\"group_name\", note) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "'$strdataName','$strDataNote') ";
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strdataName", 0);
    } else {
        $strSQL = "UPDATE hrd_shift_group ";
        $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "\"group_name\" = '$strdataName', note = '$strDataNote' ";
        $strSQL .= "WHERE id = '$strDataID' ";
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strdataName", 0);
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
            // hapus juga membernya
            $strSQL = "DELETE FROM hrd_shift_group_member WHERE \"id_group\" = '$strValue'";
            $resExec = $db->execute($strSQL);
            $strSQL = "DELETE FROM hrd_shift_group WHERE id = '$strValue' ";
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
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>