<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=training_request_approval.php");
    exit();
}
$bolCanView = getUserPermission("training_request_approval.php", $bolCanEdit, $bolCanDelete, $strError);
$strTemplateFile = getTemplate("training_request_approval.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDateFrom, $strDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_REQUEST_STATUS;
    $intRows = 0;
    $strResult = "";
    // ambil dulu data employee, kumpulkan dalam array
    $i = 0;
    $strSQL = "SELECT t1.*, t2.department_name, t3.employee_name FROM hrd_training_request AS t1 ";
    $strSQL .= "LEFT JOIN hrd_department AS t2 ON t1.department_code = t2.department_code ";
    $strSQL .= "LEFT JOIN hrd_employee AS t3 ON t1.id_employee = t3.id ";
    $strSQL .= "WHERE status=1 $strKriteria ";
    $strSQL .= "AND request_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
    $strSQL .= "ORDER BY $strOrder t1.request_date, t2.department_name ";
    $resDb = $db->execute($strSQL);
    $strDateOld = "";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        if ($rowDb['status'] == '0') {
            $strClass = "bgNewRevised";
        } else if ($rowDb['status'] == '4') {
            $strClass = "bgDenied";
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
        $strParticipant = "";
        $strSQL = "SELECT t1.id_employee, t2.employee_id, t2.employee_name FROM hrd_training_request_participant AS t1 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
        $strSQL .= "WHERE t1.id_request = '" . $rowDb['id'] . "' ";
        $resTmp = $db->execute($strSQL);
        while ($rowTmp = $db->fetchrow($resTmp)) {
            if ($strParticipant != "") {
                $strParticipant .= "<br>\n";
            }
            $strParticipant .= "[" . $rowTmp['employee_id'] . "] ";
            $strParticipant .= $rowTmp['employee_name'];
        }
        $strEmployeeInfo = $rowDb['department_code'] . " - " . $rowDb['department_name'];
        $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
        $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['request_date'], "d-M-y") . "&nbsp;</td>\n";
        $strResult .= "  <td nowrap>" . $rowDb['department_code'] . "&nbsp;</td>";
        $strResult .= "  <td nowrap>" . $rowDb['requestNumber'] . "&nbsp;</td>";
        $strResult .= "  <td nowrap>" . $rowDb['employee_name'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $strTypeList . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['topic'] . "&nbsp;</td>";
        $strResult .= "  <td>" . nl2br($rowDb['purpose']) . "&nbsp;</td>";
        $strResult .= "  <td>" . nl2br($rowDb['result']) . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['trainer'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['training_date'], "d-M-y") . "&nbsp;</td>\n";
        $strResult .= "  <td align=center>" . $words[$ARRAY_REQUEST_STATUS[$rowDb['status']]] . "&nbsp;</td>";
        $strResult .= "  <td nowrap>$strParticipant&nbsp;</td>"; // dadftar kandidate
        $strResult .= "  <td align=center><a href=\"training_request_edit.php?dataID=" . $rowDb['id'] . "\">" . $words['edit'] . "</a>&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // showData
// fungsi untuk menghapus data
function deleteData($db)
{
    global $_REQUEST;
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $i++;
            $strSQL = "DELETE FROM hrd_training_request_participant WHERE id_request = '$strValue'; ";
            $strSQL .= "DELETE FROM hrd_training_request_type WHERE id_request = '$strValue'; ";
            $strSQL .= "DELETE FROM hrd_training_request WHERE id = '$strValue'; ";
            $resExec = $db->execute($strSQL);
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
    }
} //deleteData
// fungsi untuk menghapus data
function changeStatus($db, $intStatus)
{
    global $_REQUEST;
    global $_SESSION;
    if (!is_numeric($intStatus)) {
        return false;
    }
    if ($_SESSION['sessionUserRole'] != ROLE_SUPERVISOR && $_SESSION['sessionUserRole'] != ROLE_ADMIN) {
        return false;
    }
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $i++;
            $strSQL = "UPDATE hrd_training_request SET status = '$intStatus'  ";
            //$strSQL .= "verification_date = now(), approval_date = NULL ";
            $strSQL .= "WHERE id = '$strValue' "; // yang udah apprve gak boleh diedit
            $resExec = $db->execute($strSQL);
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data", 0);
    }
} //changeStatus
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    // hapus data jika ada perintah
    if (isset($_REQUEST['btnDelete'])) {
        if ($bolCanDelete) {
            deleteData($db);
        }
    } else if (isset($_REQUEST['btnApproved'])) {
        if ($bolCanEdit) {
            changeStatus($db, 3);
        }
    } else if (isset($_REQUEST['btnNotApproved'])) {
        if ($bolCanEdit) {
            changeStatus($db, 2);
        }
    } else if (isset($_REQUEST['btnDenied'])) {
        if ($bolCanEdit) {
            changeStatus($db, 4);
        }
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    //getDefaultSalaryPeriode($strDefaultFrom,$strDefaultThru);
    $strNow = date("Y-m-d");
    (isset($_REQUEST['dataDateFrom'])) ? $strDateFrom = $_REQUEST['dataDateFrom'] : $strDateFrom = $strNow;
    (isset($_REQUEST['dataDateThru'])) ? $strDateThru = $_REQUEST['dataDateThru'] : $strDateThru = $strNow;
    (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
    (isset($_REQUEST['dataStatus'])) ? $strDataStatus = $_REQUEST['dataStatus'] : $strDataStatus = "";
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    $strKriteria = "";
    if ($strDataDepartment != "") {
        $strKriteria .= "AND t1.department_code = '$strDataDepartment' ";
    }
    if ($strDataStatus != "") {
        $strKriteria .= "AND t1.status = '$strDataStatus' ";
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $strDateFrom, $strDateThru, $intTotalData, $strKriteria);
    } else {
        showError("view_denied");
    }
    $intDefaultWidthPx = 200;
    $strFilterDateFrom = "<input type=text size=15 maxlength=10 name=dataDateFrom id=dataDateFrom value=\"$strDateFrom\">";
    $strFilterDateThru = "<input type=text size=15 maxlength=10 name=dataDateThru id=dataDateThru value=\"$strDateThru\">";
    $strFilterDepartment = getDepartmentList(
        $db,
        "dataDepartment",
        $strDataDepartment,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\" "
    );
    //$strFilterStatus = getRecruitmentNeedStatusList("dataStatus",$strDataStatus,$strEmptyOption,"style=\"width:$intDefaultWidthPx\"");
    // informasi tanggal kehadiran
    if ($strDateFrom == $strDateThru) {
        $strInfo .= "<br>" . strtoupper(pgDateFormat($strDateFrom, "d-M-Y"));
    } else {
        $strInfo .= "<br>" . strtoupper(pgDateFormat($strDateFrom, "d-M-Y"));
        $strInfo .= " >> " . strtoupper(pgDateFormat($strDateThru, "d-M-Y"));
    }
    $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDateFrom\">";
    $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDateThru\">";
    $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
    $strHidden .= "<input type=hidden name=dataStatus value=\"$strDataStatus\">";
}
$strInitAction .= " document.formInput.dataDateFrom.focus();
    Calendar.setup({ inputField:\"dataDateFrom\", button:\"btnDateFrom\" });
    Calendar.setup({ inputField:\"dataDateThru\", button:\"btnDateThru\" });

  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>