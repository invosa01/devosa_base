<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
$dataPrivilege = getDataPrivileges(
    "resign_edit.php",
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
$dataPrivilege = getDataPrivileges(
    basename("resign_edit.php"),
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
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strButtons = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
// fungsi untuk menampilkan data, tapi hanya perubahan jabatan saja
function getData($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $ARRAY_REQUEST_STATUS;
    global $bolPrint;
    $intRows = 0;
    $strResult = "";
    // ambil dulu data employee, kumpulkan dalam array
    $i = 0;
    $strSQL = "SELECT t3.*, t2.employee_id, t2.employee_name, t2.gender, t2.join_date,  ";
    $strSQL .= "t1.\"note\", t1.proposal_date, t1.\"status\", t1.id as idm ";
    $strSQL .= "FROM hrd_employee_mutation_resign AS t3 ";
    $strSQL .= "LEFT JOIN hrd_employee_mutation AS t1 ON t3.id_mutation = t1.id ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.type=1 $strKriteria ";
    $strSQL .= "AND proposal_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
    $strSQL .= "ORDER BY $strOrder t1.proposal_date, t2.employee_id ";
    $resDb = $db->execute($strSQL);
    $strDateOld = "";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        ($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
        $strClass = getCssClass($rowDb['status']);
        $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
        $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
        if (!$bolPrint) {
            $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['idm'] . "\"></td>\n";
        }
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['proposal_date'], "d-M-Y") . "&nbsp;</td>\n";
        $strResult .= "  <td>" . $rowDb['employee_id'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $strGender . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['join_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['resign_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . standardFormat($rowDb['basic_salary'], true) . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['leave_remain'] . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . standardFormat($rowDb['salary'], true) . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . standardFormat($rowDb['meal'], true) . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . standardFormat($rowDb['conjuncture'], true) . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . standardFormat($rowDb['leave_allowance1'], true) . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . standardFormat($rowDb['leave_allowance2'], true) . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . standardFormat($rowDb['pesangon'], true) . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . standardFormat($rowDb['other_right'], true) . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['right_note'] . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . standardFormat($rowDb['loan'], true) . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . standardFormat($rowDb['other_loan'], true) . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . standardFormat($rowDb['other_obligation'], true) . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['obligation_note'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "&nbsp;</td>";
        if (!$bolPrint) {
            $strResult .= "  <td align=center><a href=\"resign_edit.php?dataID=" . $rowDb['idm'] . "\">" . $words['edit'] . "</a>&nbsp;</td>";
        }
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // getDataPosition
// fungsi untuk menghapus data
function deleteData($db)
{
    global $_REQUEST;
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $i++;
            // cari data id Employee
            $strIDEmployee = "";
            $strSQL = "SELECT id_employee, status FROM hrd_employee_mutation WHERE id = '$strValue' ";
            $resTmp = $db->execute($strSQL);
            if ($rowTmp = $db->fetchrow($resTmp)) {
                if ($_SESSION['sessionUserRole'] == ROLE_ADMIN || $rowTmp['status'] < REQUEST_STATUS_APPROVED) {
                    $strIDEmployee = $rowTmp['id_employee'];
                    $strSQL = "DELETE FROM hrd_employee_mutation_status WHERE id_mutation = '$strValue'; ";
                    $strSQL .= "DELETE FROM hrd_employee_mutation_resign WHERE id_mutation = '$strValue'; ";
                    $strSQL .= "DELETE FROM hrd_employee_mutation_department WHERE id_mutation = '$strValue'; ";
                    $strSQL .= "DELETE FROM hrd_employee_mutation_position WHERE id_mutation = '$strValue'; ";
                    $strSQL .= "DELETE FROM hrd_employee_mutation_salary WHERE id_mutation = '$strValue'; ";
                    $strSQL .= "DELETE FROM hrd_employee_mutation WHERE id = '$strValue'; ";
                    $resExec = $db->execute($strSQL);
                    updateEmployeeCareerData($db, $strIDEmployee);
                }
            }
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
    $strUpdate = "";
    $strmodified_byID = $_SESSION['sessionUserID'];
    $strUpdate = getStatusUpdateString($intStatus);
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $i++;
            // jika approve, update data karyawan juga
            if (isProcessable($rowDb['status'], $intStatus)) {
                $strSQL = "SELECT t2.id, t2.id_employee, t1.resign_date ";
                $strSQL .= "FROM hrd_employee_mutation_resign AS t1, hrd_employee_mutation AS t2 ";
                $strSQL .= "WHERE t1.id_mutation = t2.id AND t2.id = '$strValue' ";
                $resDb = $db->execute($strSQL);
                if ($rowDb = $db->fetchrow($resDb)) {
                    // update data karyawan
                    $strSQL = "UPDATE hrd_employee SET active = '0', resign_date = '" . $rowDb['resign_date'] . "' ";
                    $strSQL .= "WHERE id = '" . $rowDb['id_employee'] . "' ";
                    $resExec = $db->execute($strSQL);
                }
            }
            // update status
            $strSQL = "UPDATE hrd_employee_mutation SET $strUpdate status = '$intStatus'  ";
            //$strSQL .= "verification_date = now(), approval_date = NULL ";
            $strSQL .= "WHERE id = '$strValue' AND status <>  " . REQUEST_STATUS_APPROVED; // yang udah apprve gak boleh diedit
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
    }
    callChangeStatus();
    // ------ AMBIL DATA KRITERIA -------------------------
    getDefaultSalaryPeriode($strDefaultFrom, $strDefaultThru);
    (isset($_REQUEST['dataDateFrom'])) ? $strDataDateFrom = $_REQUEST['dataDateFrom'] : $strDataDateFrom = $strDefaultFrom;
    (isset($_REQUEST['dataDateThru'])) ? $strDataDateThru = $_REQUEST['dataDateThru'] : $strDataDateThru = $strDefaultThru;
    (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
    (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
    (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
    (isset($_REQUEST['dataOption'])) ? $strDataOption = $_REQUEST['dataOption'] : $strDataOption = "";
    $strDeptKriteria = "";
    $strSectKriteria = "";
    if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
        $strDataDepartment = $arrUserInfo['department_code'];
        $strDeptKriteria = "WHERE department_code = '$strDataDepartment' ";
        if ($arrUserInfo['isGroupHead']) {
            $strDataSection = $arrUserInfo['section_code'];
            $strSectKriteria = "WHERE department_code = '$strDataDepartment' AND section_code = '$strDataSection' ";
        } else if ($arrUserInfo['isDeptHead']) {
            $strSectKriteria = "WHERE department_code = '$strDataDepartment' ";
        } else {
            $bolCanView = $bolCanDelete = $bolCanEdit = false;
        }
    }
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    $strKriteria = "";
    if ($strDataDepartment != "") {
        $strKriteria .= "AND department_code = '$strDataDepartment' ";
    }
    if ($strDataSection != "") {
        $strKriteria .= "AND section_code = '$strDataSection' ";
    }
    if ($strDataEmployee != "") {
        $strKriteria .= "AND employee_id = '$strDataEmployee' ";
    }
    $strKriteria .= $strKriteriaCompany;
    if ($bolCanView) {
        if (validStandardDate($strDataDateFrom) && validStandardDate($strDataDateThru)) {
            // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
            $strDataDetail = getData($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
            if (isset($_REQUEST['btnExcel'])) {
                // ambil data CSS-nya
                if (file_exists("bw.css")) {
                    $strStyle = "bw.css";
                }
                $strPrintCss = "";
                $strPrintInit = "";
                headeringExcel("resignList.xls");
            }
        } else {
            $strDataDetail = "";
        }
    } else {
        showError("view_denied");
    }
    $intDefaultWidthPx = 200;
    $strInputDateFrom = "<input type=text name=dataDateFrom id=dataDateFrom size=15 maxlength=10 value=\"$strDataDateFrom\">";
    $strInputDateThru = "<input type=text name=dataDateThru id=dataDateThru size=15 maxlength=10 value=\"$strDataDateThru\">";
    $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"$strDataEmployee\">";
    $strInputDepartment = getDepartmentList(
        $db,
        "dataDepartment",
        $strDataDepartment,
        $strEmptyOption,
        "$strDeptKriteria",
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputSection = getSectionList(
        $db,
        "dataSection",
        $strDataSection,
        $strEmptyOption,
        "$strSectKriteria",
        "style=\"width:$intDefaultWidthPx\""
    );
    //handle user company-access-right
    $strInputCompany = getCompanyList(
        $db,
        "dataCompany",
        $strDataCompany,
        $strEmptyOption2,
        $strKriteria2,
        "style=\"width:$intDefaultWidthPx\" "
    );
    $strInputOption = "";
    // informasi tanggal kehadiran
    if ($strDataDateFrom == $strDataDateThru) {
        $strInfo .= "<br>" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
    } else {
        $strInfo .= "<br>" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
        $strInfo .= " >> " . strtoupper(pgDateFormat($strDataDateThru, "d-M-Y"));
    }
    if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
        $strButtons .= "&nbsp;<input type=submit name=btnChecked value=\"" . $words['checked'] . "\" onClick=\"return confirmStatusChanges(false)\">";
        $strButtons .= "&nbsp;<input type=submit name=btnDenied value=\"" . $words['denied'] . "\" onClick=\"return confirmStatusChanges(true)\">";
        $strButtons .= "&nbsp;<input type=submit name=btnDelete value=\"" . $words['delete'] . "\" onClick=\"return confirmDelete()\">";
    } else if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        $strButtons .= "&nbsp;<input type=submit name=btnChecked value=\"" . $words['checked'] . "\" onClick=\"return confirmStatusChanges(false)\">";
        $strButtons .= "&nbsp;<input type=submit name=btnApproved value=\"" . $words['approved'] . "\" onClick=\"return confirmStatusChanges(false)\">";
        $strButtons .= "&nbsp;<input type=submit name=btnDenied value=\"" . $words['denied'] . "\" onClick=\"return confirmStatusChanges(true)\">";
        $strButtons .= "&nbsp;<input type=submit name=btnDelete value=\"" . $words['delete'] . "\" onClick=\"return confirmDelete()\">";
    } else if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
        //$strButtons .= "&nbsp;<input type=submit name=btnVerified value=\"" .$words['verified']. "\" onClick=\"return confirmStatusChanges(false)\">";
        //$strButtons .= "&nbsp;<input type=submit name=btnDenied value=\"" .$words['denied']. "\" onClick=\"return confirmStatusChanges(true)\">";
        $strButtons .= "&nbsp;<input type=submit name=btnDelete value=\"" . $words['delete'] . "\" onClick=\"return confirmDelete()\">";
    }
    $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDataDateFrom\">";
    $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDataDateThru\">";
    $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
    $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
    $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
    $strHidden .= "<input type=hidden name=dataOption value=\"$strDataOption\">";
}
$tbsPage = new clsTinyButStrong;
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
//------------------------------------------
if ($bolPrint) {
    $strTemplateFile = getTemplate("resign_list_print.html");
    $tbsPage->LoadTemplate($strTemplateFile);
} else {
    $strTemplateFile = getTemplate("resign_list.html");
    $tbsPage->LoadTemplate($strMainTemplate);
}
$tbsPage->Show();
?>