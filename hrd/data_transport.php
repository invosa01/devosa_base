<?php
session_start();
include_once('global.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=data_transport.php");
    exit();
}
$bolCanView = getUserPermission("data_transport.php", $bolCanEdit, $bolCanDelete, $strError);
$bolPrint = (isset($_REQUEST['btnPrint']));
if ($bolPrint) {
    $strMainTemplate = getTemplate("data_transport_print.html");
} else {
    $strTemplateFile = getTemplate("data_transport.html");
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
$intMaxBus = 50;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $bolPrint;
    $intRows = 0;
    $strResult = "";
    $strSQL = "SELECT * FROM \"hrdTransportation\" ";
    $strSQL .= "WHERE 1=1 $strKriteria ORDER BY $strOrder \"code\" ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        // cari dulu daftar bus
        $j = 0;
        $strDetail1 = "";
        $strDetail2 = "";
        $strSQL = "SELECT * FROM \"hrdBus\" WHERE \"idTransportation\" = '" . $rowDb['id'] . "' ";
        $resBus = $db->execute($strSQL);
        while ($rowBus = $db->fetchrow($resBus)) {
            $j++;
            if ($j == 1) {
                $strHiddenBus = "<input type=hidden name='detailBusID$intRows" . "_$j' value=\"" . $rowBus['id'] . "\" disabled>\n";
                $strHiddenBus .= "<input type=hidden name='detailBusCode$intRows" . "_$j' value=\"" . $rowBus['code'] . "\" disabled>\n";
                $strHiddenBus .= "<input type=hidden name='detailBusSeat$intRows" . "_$j' value=\"" . $rowBus['seat'] . "\" disabled>\n";
                $strHiddenBus .= "<input type=hidden name='detailBusNote$intRows" . "_$j' value=\"" . $rowBus['note'] . "\" disabled>\n";
                $strDetail1 .= "<td>$strHiddenBus&nbsp;" . $rowBus['code'] . "</td>\n";
                $strDetail1 .= "<td align=right>&nbsp;" . $rowBus['seat'] . "</td>\n";
                $strDetail1 .= "<td>&nbsp;" . $rowBus['note'] . "</td>\n";
            } else {
                $strHiddenBus = "<input type=hidden name='detailBusID$intRows" . "_$j' value=\"" . $rowBus['id'] . "\" disabled>\n";
                $strHiddenBus .= "<input type=hidden name='detailBusCode$intRows" . "_$j' value=\"" . $rowBus['code'] . "\" disabled>\n";
                $strHiddenBus .= "<input type=hidden name='detailBusSeat$intRows" . "_$j' value=\"" . $rowBus['seat'] . "\" disabled>\n";
                $strHiddenBus .= "<input type=hidden name='detailBusNote$intRows" . "_$j' value=\"" . $rowBus['note'] . "\" disabled>\n";
                if ($bolPrint) {
                    $strDetail2 .= "<tr valign=top><td colspan=4>&nbsp;</td>\n";
                } else {
                    $strDetail2 .= "<tr valign=top><td colspan=5>&nbsp;</td>\n";
                }
                $strDetail2 .= "<td>$strHiddenBus&nbsp;" . $rowBus['code'] . "</td>\n";
                $strDetail2 .= "<td align=right>&nbsp;" . $rowBus['seat'] . "</td>\n";
                $strDetail2 .= "<td>&nbsp;" . $rowBus['note'] . "</td>\n";
                if ($bolPrint) {
                    $strDetail2 .= "<td>&nbsp;</td></tr>\n";
                } else {
                    $strDetail2 .= "<td>&nbsp;</td><td>&nbsp;</td></tr>\n";
                }
            }
        }
        // handle jika tidak ada bus
        if ($j == 0) {
            $strDetail1 .= "<td>&nbsp;</td>\n";
            $strDetail1 .= "<td align=right>&nbsp;</td>\n";
            $strDetail1 .= "<td>&nbsp;</td>\n";
        }
        $strTotalBus = "<input type=hidden name='detailTotalBus$intRows' value=$j disabled>";
        $strResult .= "<tr valign=top>\n";
        if (!$bolPrint) {
            $strResult .= "  <td>$strTotalBus<input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
        }
        $strResult .= "  <td nowrap><input type=hidden name=detailCode$intRows value=\"" . $rowDb['code'] . "\" disabled>" . $rowDb['code'] . "&nbsp;</td>";
        $strResult .= "  <td><input type=hidden name=detailLocation$intRows value=\"" . $rowDb['location'] . "\" disabled>" . $rowDb['location'] . "&nbsp;</td>";
        $strResult .= "  <td><input type=hidden name=detailArea$intRows value=\"" . $rowDb['area'] . "\" disabled>" . $rowDb['area'] . "&nbsp;</td>";
        //$strResult .= "  <td><input type=hidden name=detailBus$intRows value=\"" .$rowDb['busNumber']. "\" disabled>" .$rowDb['busNumber']. "&nbsp;</td>";
        //$strResult .= "  <td align=right><input type=hidden name=detailSeat$intRows value=\"" .$rowDb['seat']. "\" disabled>" .$rowDb['seat']. "&nbsp;</td>";
        $strResult .= "  <td align=right><input type=hidden name=detailAllowance$intRows value=\"" . (float)$rowDb['overtimeAllowance'] . "\" disabled>" . standardFormat(
                $rowDb['overtimeAllowance']
            ) . "&nbsp;</td>";
        $strResult .= $strDetail1;
        $strResult .= "  <td><input type=hidden name=detailNote$intRows value=\"" . $rowDb['note'] . "\" disabled>" . $rowDb['note'] . "&nbsp;</td>";
        if (!$bolPrint) {
            $strResult .= "  <td nowrap align=center><a href='javascript:editData($intRows)'>" . $words['edit'] . "</a>&nbsp;</td>";
        }
        $strResult .= "</tr>\n";
        $strResult .= $strDetail2;
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
    global $intMaxBus;
    $strmodified_byID = $_SESSION['sessionUserID'];
    $strError = "";
    (isset($_REQUEST['dataCode'])) ? $strDataCode = $_REQUEST['dataCode'] : $strDataCode = "";
    (isset($_REQUEST['dataAllowance'])) ? $strDataAllowance = $_REQUEST['dataAllowance'] : $strDataAllowance = "0";
    //(isset($_REQUEST['dataSeat'])) ? $strDataSeat = $_REQUEST['dataSeat'] : $strDataSeat = "0";
    (isset($_REQUEST['dataLocation'])) ? $strDataLocation = $_REQUEST['dataLocation'] : $strDataLocation = "";
    (isset($_REQUEST['dataArea'])) ? $strDataArea = $_REQUEST['dataArea'] : $strDataArea = "";
    //(isset($_REQUEST['dataBus'])) ? $strDataBus = $_REQUEST['dataBus'] : $strDataBus = "";
    (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    // cek validasi -----------------------
    if ($strDataCode == "") {
        $strError = $error['empty_code'];
        return false;
    } else {
        ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
        if (isDataExists("hrdTransportation", "code", $strDataCode, $strKriteria)) {
            $strError = $error['duplicate_code'] . "  -> $strDataCode";
            return false;
        }
    }
    // simpan data -----------------------
    if ($strDataID == "") {
        // data baru
        $strSQL = "INSERT INTO \"hrdTransportation\" (created,created_by,modified_by, ";
        $strSQL .= "code, location, area, \"overtimeAllowance\", note) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "'$strDataCode', '$strDataLocation','$strDataArea', ";
        $strSQL .= "'$strDataAllowance','$strDataNote') ";
        $resExec = $db->execute($strSQL);
        // cari data ID
        $strSQL = "SELECT id FROM \"hrdTransportation\" WHERE code = '$strDataCode' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strDataID = $rowDb['id'];
        }
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataCode", 0);
    } else {
        $strSQL = "UPDATE \"hrdTransportation\" ";
        $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', code = '$strDataCode', ";
        $strSQL .= "location = '$strDataLocation', area = '$strDataArea', ";
        $strSQL .= "\"overtimeAllowance\"= '$strDataAllowance', note = '$strDataNote' ";
        $strSQL .= "WHERE id = '$strDataID' ";
        $resExec = $db->execute($strSQL);
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataCode", 0);
    }
    // simpan data bus
    if ($strDataID != "") {
        $strBusCode = "";
        $intSeat = 0;
        for ($i = 1; $i <= $intMaxBus; $i++) {
            (isset($_REQUEST['dataBusID' . $i])) ? $strID = $_REQUEST['dataBusID' . $i] : $strID = "";
            (isset($_REQUEST['dataBusCode' . $i])) ? $strCode = $_REQUEST['dataBusCode' . $i] : $strCode = "";
            (isset($_REQUEST['dataBusSeat' . $i])) ? $strSeat = $_REQUEST['dataBusSeat' . $i] : $strSeat = "";
            (isset($_REQUEST['dataBusNote' . $i])) ? $strNote = $_REQUEST['dataBusNote' . $i] : $strNote = "";
            if ($strID == "") { //add new
                if ($strCode != "") {
                    if (!is_numeric($strSeat)) {
                        $strSeat = 0;
                    };
                    $strSQL = "INSERT INTO \"hrdBus\" (created,modified_by,created_by, ";
                    $strSQL .= "\"idTransportation\", code, seat, note) ";
                    $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', ";
                    $strSQL .= "'$strDataID', '$strCode', $strSeat, '$strNote') ";
                    $resExec = $db->execute($strSQL);
                }
            } else { //update atau delete
                if ($strCode == "") {
                    $strSQL = "DELETE FROM \"hrdBus\" WHERE id  = '$strID' ";
                    $resExec = $db->execute($strSQL);
                } else {
                    $strSQL = "UPDATE \"hrdBus\" SET modified_by = '$strmodified_byID', ";
                    $strSQL .= "code = '$strCode', seat = '$strSeat', note = '$strNote' ";
                    $strSQL .= "WHERE id = '$strID' ";
                    $resExec = $db->execute($strSQL);
                }
            }
            // simpan untuk ditampung di master data transport
            if ($strCode != "" && $strBusCode == "") {
                $strBusCode = $strCode;
                $intSeat = $strSeat;
            }
        }
        // update data bus
        $strSQL = "UPDATE \"hrdTransportation\" SET \"busNo\" = '$strBusCode', seat = '$intSeat' ";
        $strSQL .= "WHERE id = '$strDataID' ";
        $resExec = $db->execute($strSQL);
    }
    return true;
} // saveData
// fungsi untuk menghapus data
function deleteData($db)
{
    global $_REQUEST;
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $strSQL = "DELETE FROM \"hrdTransportation\" WHERE id = '$strValue' ";
            $resExec = $db->execute($strSQL);
            $i++;
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
    }
} //deleteData
// mengambil daftar detail untuk isian bus
function getDetailInputBus($intMax = 0)
{
    $strResult = "";
    if ($intMax > 0) {
        $i = 1;
        // pertama, tampilkan baris awal, yang langsung VISIBLE
        $strResult .= "<tr id=\"detailInputBus$i\">\n";
        $strResult .= "  <td><input type=text size=20 maxlength=20 name='dataBusCode$i'></td>\n";
        $strResult .= "  <td><input type=text size=5 maxlength=10 name='dataBusSeat$i' value=0></td>\n";
        $strResult .= "  <td><input type=text size=30 maxlength=50 name='dataBusNote$i'></td>\n";
        $strResult .= "  <td><input type=checkbox name='chkBus$i' onClick=\"chkDeleteChanged($i)\"><input type=hidden name='dataBusID$i'></td>\n";
        $strResult .= "</tr>\n";
        while ($i < $intMax) {
            $i++;
            $strResult .= "<tr id=\"detailInputBus$i\" style=\"display:none\">\n";
            $strResult .= "  <td><input type=text size=20 maxlength=20 name='dataBusCode$i' disabled></td>\n";
            $strResult .= "  <td><input type=text size=5 maxlength=10 name='dataBusSeat$i' disabled></td>\n";
            $strResult .= "  <td><input type=text size=30 maxlength=50 name='dataBusNote$i' disabled></td>\n";
            $strResult .= "  <td><input type=checkbox name='chkBus$i' disabled onClick=\"chkDeleteChanged($i)\"><input type=hidden name='dataBusID$i'></td>\n";
            $strResult .= "</tr>\n";
        }
    }
    return $strResult;
}//getDetailInputBus
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
        $strDetailBus = getDetailInputBus($intMaxBus);
    } else {
        showError("view_denied");
        $strDetailBus = "";
    }
}
$strInitAction .= "    document.formInput.dataCode.focus();  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>