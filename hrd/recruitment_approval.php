<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=recruitment_approval.php");
    exit();
}
$bolCanView = getUserPermission("recruitment_approval.php", $bolCanEdit, $bolCanDelete, $strError);
$strTemplateFile = getTemplate("recruitment_approval.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataYear, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $ARRAY_RECRUITMENT_NEED_STATUS;
    global $ARRAY_MARITAL_STATUS;
    $intRows = 0;
    $strResult = "";
    // ambil dulu data employee, kumpulkan dalam array
    $i = 0;
    $strSQL = "SELECT t1.*, t2.department_name FROM hrd_recruitment_need AS t1 ";
    $strSQL .= "LEFT JOIN hrd_department AS t2 ON t1.department_code = t2.department_code ";
    $strSQL .= "WHERE 1=1 AND status = 1 $strKriteria "; // cuma tampil yang sudah diapprove HRD
    $strSQL .= "AND ( EXTRACT (year FROM recruitment_date) = '$strDataYear' ";
    $strSQL .= "OR EXTRACT (year FROM due_date) = '$strDataYear') ";
    $strSQL .= "ORDER BY $strOrder t1.recruitment_date, t2.department_name ";
    $resDb = $db->execute($strSQL);
    $strDateOld = "";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        if ($rowDb['gender'] == 0) {
            $strGender = $words['female'];
        } else if ($rowDb['gender'] == 1) {
            $strGender = $words['male'];
        } else {
            $strGender = "";
        }
        if ($rowDb['status'] == '0') {
            $strClass = "bgNewRevised";
        } else if ($rowDb['status'] == '3') {
            $strClass = "bgDenied";
        } else {
            $strClass = "";
        }
        $strMaritalStatus = ($rowDb['marital_status'] == 1 || $rowDb['marital_status'] == 0) ? $words[$ARRAY_MARITAL_STATUS[$rowDb['marital_status']]] : "";
        $strEmployeeStatus = (in_array(
            $rowDb['employee_status'],
            $ARRAY_EMPLOYEE_STATUS
        )) ? $words[$ARRAY_EMPLOYEE_STATUS[$rowDb['employee_status']]] : "";
        $strEmployeeInfo = $rowDb['department_code'] . " - " . $rowDb['department_name'];
        $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
        $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['recruitmentDate'], "d-M-y") . "&nbsp;</td>\n";
        $strResult .= "  <td>" . $rowDb['department_name'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['position'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $strEmployeeStatus . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . $rowDb['number'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['due_date'], "d-M-y") . "&nbsp;</td>\n";
        $strResult .= "  <td>" . $rowDb['description'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['min_age'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['max_age'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $strGender . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $strMaritalStatus . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['education_level'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['education'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['work_experience'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['qualification'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $words[$ARRAY_RECRUITMENT_NEED_STATUS[$rowDb['status']]] . "&nbsp;</td>";
        $strResult .= "  <td align=center>&nbsp;</td>"; // dadftar kandidate
        $strResult .= "  <td align=center>&nbsp;</td>";
        //$strResult .= "  <td align=center><a href=\"recruitment_edit.php?dataID=" .$rowDb['id']. "\">" .$words['edit']. "</a>&nbsp;</td>";
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
            $strSQL = "DELETE FROM hrd_recruitment_need WHERE id = '$strValue' ";
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
    if (!is_numeric($intStatus)) {
        return false;
    }
    if ($_SESSION['sessionUserRole'] != ROLE_SUPERVISOR && $_SESSION['sessionUserRole'] != ROLE_SUPERVISOR && $_SESSION['sessionUserRole'] != ROLE_ADMIN) {
        return false;
    }
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $i++;
            $strSQL = "UPDATE hrd_recruitment_need SET status = '$intStatus',  ";
            if ($intStatus == 1) {
                $strSQL .= "approval_date = NULL ";
            } else {
                $strSQL .= "approval_date = now() ";
            }
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
            changeStatus($db, 2);
        }
    } else if (isset($_REQUEST['btnNotApproved'])) {
        if ($bolCanEdit) {
            changeStatus($db, 1);
        }
    } else if (isset($_REQUEST['btnDenied'])) {
        if ($bolCanEdit) {
            changeStatus($db, 3);
        }
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    //getDefaultSalaryPeriode($strDefaultFrom,$strDefaultThru);
    $strCurrYear = date("Y");
    (isset($_REQUEST['dataYear'])) ? $strDataYear = $_REQUEST['dataYear'] : $strDataYear = $strCurrYear;
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
        $strDataDetail = getData($db, $strDataYear, $intTotalData, $strKriteria);
    } else {
        showError("view_denied");
    }
    $intDefaultWidthPx = 200;
    $strFilterYear = getYearList("dataYear", $strDataYear);
    $strFilterDepartment = getDepartmentList(
        $db,
        "dataDepartment",
        $strDataDepartment,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\" "
    );
    $strFilterStatus = getRecruitmentNeedStatusList(
        "dataStatus",
        $strDataStatus,
        $strEmptyOption,
        "style=\"width:$intDefaultWidthPx\""
    );
    // informasi tanggal kehadiran
    $strInfo .= $strDataYear;
    $strHidden .= "<input type=hidden name=dataYear value=\"$strDataYear\">";
    $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
    $strHidden .= "<input type=hidden name=dataStatus value=\"$strDataStatus\">";
}
$strInitAction .= "		document.formInput.dataYear.focus();   ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>