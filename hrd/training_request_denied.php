<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=training_request_list.php");
    exit();
}
$bolCanView = getUserPermission("training_request_list.php", $bolCanEdit, $bolCanDelete, $strError, true);
$strTemplateFile = getTemplate("training_request_denied.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$strButtonList = "";
$intTotalData = 0;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows)
{
    global $words;
    global $ARRAY_REQUEST_STATUS;
    $intRows = 0;
    $strResult = "";
    // ambil dulu data employee, kumpulkan dalam array
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $strSQL = "SELECT t1.*, t2.department_name, t3.employee_name FROM hrd_training_request AS t1 ";
            $strSQL .= "LEFT JOIN hrd_department AS t2 ON t1.department_code = t2.department_code ";
            $strSQL .= "LEFT JOIN hrd_employee AS t3 ON t1.id_employee = t3.id ";
            $strSQL .= "WHERE t1.id  = '$strValue' ";
            $resDb = $db->execute($strSQL);
            $strDateOld = "";
            while ($rowDb = $db->fetchrow($resDb)) {
                $intRows++;
                $bolDenied = false;
                if ($rowDb['status'] == '0') {
                    $strClass = "bgNewRevised";
                } else if ($rowDb['status'] == '4') {
                    $strClass = "bgDenied";
                    $bolDenied = true;
                } else {
                    $strClass = "";
                }
                // cari daftar candidate
                $strTypeList = "";
                $strSQL = "SELECT * FROM hrd_training_request_type WHERE id_request = '" . $rowDb['id'] . "' ";
                $resTmp = $db->execute($strSQL);
                while ($rowTmp = $db->fetchrow($resTmp)) {
                    if ($strTypeList != "") {
                        $strTypeList .= ", ";
                    }
                    $strTypeList .= $rowTmp['type'];
                }
                $strRowspan = "";
                $strParticipant1 = "";
                $strParticipant2 = "";
                $intParticipant = 0;
                $strSQL = "SELECT t1.id_employee, t1.status, t2.employee_id, t2.employee_name FROM hrd_training_request_participant AS t1 ";
                $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
                $strSQL .= "WHERE t1.id_request = '" . $rowDb['id'] . "' ";
                $strSQL .= "ORDER BY t2.employee_name ";
                $resTmp = $db->execute($strSQL);
                while ($rowTmp = $db->fetchrow($resTmp)) {
                    $intParticipant++;
                    if ($rowTmp['status'] == 0) {
                        $strStatus = "";
                    } else if ($rowTmp['status'] == 1) {
                        $strStatus = "&radic;";
                    } else {
                        $strStatus = "x";
                    }
                    if ($intParticipant == 1) {
                        $strParticipant1 .= "  <td nowrap>" . $rowTmp['employee_name'] . "&nbsp;</td>\n";
                        $strParticipant1 .= "  <td nowrap align=center>" . $strStatus . "&nbsp;</td>\n";
                        $strParticipant1 .= "  <td nowrap><a href=\"training_edit.php?dataRequestID=" . $rowDb['id'] . "&dataemployee_id=" . $rowTmp['id_employee'] . "\">" . $words['report'] . "</a>&nbsp;</td>\n";
                    } else {
                        $strParticipant2 .= " <tr valign=top class=$strClass>\n";
                        $strParticipant2 .= "  <td nowrap>" . $rowTmp['employee_name'] . "&nbsp;</td>\n";
                        $strParticipant2 .= "  <td nowrap align=center>" . $strStatus . "&nbsp;</td>\n";
                        $strParticipant2 .= "  <td nowrap><a href=\"training_edit.php?dataRequestID=" . $rowDb['id'] . "&dataemployee_id=" . $rowTmp['id_employee'] . "\">" . $words['report'] . "</a>&nbsp;</td>\n";
                        $strParticipant2 .= "  </tr>\n";
                    }
                }
                if ($intParticipant == 0) { // gak ada partisipan
                    $strParticipant1 .= "  <td nowrap>&nbsp;</td>\n";
                    $strParticipant1 .= "  <td nowrap>&nbsp;</td>\n";
                    $strParticipant1 .= "  <td nowrap>&nbsp;</td>\n";
                } else {
                    $strRowspan = " rowspan=$intParticipant ";
                }
                $strEmployeeInfo = $rowDb['department_code'] . " - " . $rowDb['department_name'];
                $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
                $strResult .= "  <td $strRowspan align=center>$intRows.&nbsp;<input type=hidden name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
                $strResult .= "  <td $strRowspan align=center>" . pgDateFormat(
                        $rowDb['request_date'],
                        "d-M-y"
                    ) . "&nbsp;</td>\n";
                $strResult .= "  <td $strRowspan nowrap>" . $rowDb['department_code'] . "&nbsp;</td>";
                $strResult .= "  <td $strRowspan align=center><a href=\"training_request_edit.php?dataID=" . $rowDb['id'] . "\">" . $rowDb['requestNumber'] . "</a>&nbsp;</td>";
                $strResult .= "  <td $strRowspan nowrap>" . $rowDb['employee_name'] . "&nbsp;</td>";
                $strResult .= "  <td $strRowspan>" . $strTypeList . "&nbsp;</td>";
                $strResult .= "  <td $strRowspan>" . $rowDb['topic'] . "&nbsp;</td>";
                $strResult .= "  <td $strRowspan>" . nl2br($rowDb['purpose']) . "&nbsp;</td>";
                $strResult .= "  <td $strRowspan>" . nl2br($rowDb['result']) . "&nbsp;</td>";
                $strResult .= "  <td $strRowspan>" . $rowDb['trainer'] . "&nbsp;</td>";
                $strResult .= "  <td $strRowspan align=center>" . pgDateFormat(
                        $rowDb['training_date'],
                        "d-M-y"
                    ) . "&nbsp;</td>\n";
                $strResult .= "  <td $strRowspan align=center>" . $words[$ARRAY_REQUEST_STATUS[$rowDb['status']]] . "&nbsp;</td>";
                $strResult .= $strParticipant1;
                $strResult .= "</tr>\n";
                $strResult .= $strParticipant2;
                // tambahkan info alasan penolakan
                $strResult .= "<tr valign=top>\n";
                $strResult .= "  <td >&nbsp;</td>\n";
                $strResult .= "  <td colspan=15><strong>" . $words['reason'] . "&nbsp; : <input type=text name=detailNote$intRows value=\"" . $rowDb['note_denied'] . "\" size=100 maxlength=240 class=string> </strong></td>\n";
                $strResult .= "</tr>\n";
            }
        }
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // showData
// fungsi untuk mengubah status data
function changeStatusData($db)
{
    global $_REQUEST;
    global $_SESSION;
    $strmodified_by = $_SESSION['sessionUserID'];
    $intTotal = (isset($_REQUEST['totalData'])) ? $_REQUEST['totalData'] : 0;
    $intStatus = REQUEST_STATUS_DENIED;
    for ($i = 1; $i <= $intTotal; $i++) {
        if (isset($_REQUEST['chkID' . $i])) {
            $id = $_REQUEST['chkID' . $i];
            $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
            $strSQL = "UPDATE hrd_training_request SET status = $intStatus, note_denied = '$strNote', ";
            $strSQL .= "denied_by = '$strmodified_by', denied_time = now() ";
            $strSQL .= "WHERE id = '$id' ";
            $resExec = $db->execute($strSQL);
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data", 0);
    }
    header("location:training_request_list.php");
    exit();
} //changeStatusData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $bolIsEmployee = isUserEmployee();
    if ($bolCanEdit) {
        if (isset($_REQUEST['btnSave'])) {
            changeStatusData($db);
        }
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    if ($bolIsEmployee) {
        header("location:training_request_list.php");
        exit();
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $intTotalData);
    } else {
        header("location:training_request_list.php");
        exit();
    }
    /*
    // informasi tanggal kehadiran
    if ($strDateFrom == $strDateThru) {
      $strInfo .= "<br>".strtoupper(pgDateFormat($strDateFrom, "d-M-Y"));
    } else {
      $strInfo .= "<br>".strtoupper(pgDateFormat($strDateFrom, "d-M-Y"));
      $strInfo .= " >> ".strtoupper(pgDateFormat($strDateThru, "d-M-Y"));
    }
    */
}
$strInitAction .= "
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>