<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
// Data Privilage followed from parent (employee_edit.php)
$dataPrivilege = getDataPrivileges(
    basename("employee_edit.php"),
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
$bolPrint = (isset($_REQUEST['btnPrint']));
//---- INISIALISASI ----------------------------------------------------
$strDataStatus = "";
$strDataPosition = "";
$strDataDepartment = "";
$strDataSalary = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk mengambil daftar history employee status
function getDataStatus($db, $strDataID = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    $strResult = "";
    if ($strDataID != "") {
        $strSQL = "SELECT t1.*, t2.letter_code, EXTRACT(month FROM AGE(status_date_thru, status_date_from)) AS bulan, ";
        $strSQL .= "EXTRACT(year FROM AGE(status_date_thru, status_date_from)) AS tahun ";
        $strSQL .= "FROM hrd_employee_mutation_status AS t1, ";
        $strSQL .= "hrd_employee_mutation AS t2 WHERE t1.id_mutation = t2.id ";
        $strSQL .= "AND t2.id_employee = '$strDataID' AND t2.status >= " . REQUEST_STATUS_APPROVED . " ";
        $strSQL .= "ORDER BY t1.status_date_from DESC ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $intDur = ($rowDb['tahun'] * 12) + $rowDb['bulan'];
            $strResult .= "<tr>\n";
            $strResult .= "  <td>" . $words[$ARRAY_EMPLOYEE_STATUS[$rowDb['status_new']]] . "&nbsp;</td>\n";
            $strResult .= "  <td align=center>" . pgDateFormat($rowDb['status_date_from'], "d-M-Y") . "&nbsp;</td>\n";
            $strResult .= "  <td align=center>" . pgDateFormat($rowDb['status_date_thru'], "d-M-Y") . "&nbsp;</td>\n";
            if ($rowDb['status_new'] == 1) {
                $strResult .= "  <td>&nbsp;</td>";
            } else {
                $strResult .= "  <td align=center>" . $intDur . "&nbsp;</td>";
            }
            $strResult .= "  <td>" . $rowDb['letter_code'] . "&nbsp;</td>\n";
            $strResult .= "</tr>\n";
        }
    }
    return $strResult;
} //getDataStatus
// fungsi untuk mengambil history jabatan
function getDataPosition($db, $strDataID = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    $strResult = "";
    if ($strDataID != "") {
        $strSQL = "SELECT t1.*, t2.letter_code FROM hrd_employee_mutation_position AS t1, ";
        $strSQL .= "hrd_employee_mutation AS t2 WHERE t1.id_mutation = t2.id ";
        $strSQL .= "AND t2.id_employee = '$strDataID' AND t2.status >= " . REQUEST_STATUS_APPROVED . " ";
        $strSQL .= "ORDER BY t1.position_new_date DESC ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $strResult .= "<tr>\n";
            $strResult .= "  <td>" . $rowDb['position_new'] . "&nbsp;</td>\n";
            $strResult .= "  <td>" . $rowDb['grade_new'] . "&nbsp;</td>\n";
            $strResult .= "  <td>" . $rowDb['letter_code'] . "&nbsp;</td>\n";
            $strResult .= "  <td align=center>" . pgDateFormat($rowDb['position_new_date'], "d-M-Y") . "&nbsp;</td>\n";
            $strResult .= "</tr>\n";
        }
    }
    return $strResult;
} //getDataPosition
// fungsi untuk mengambil history department
function getDataDepartment($db, $strDataID = "")
{
    global $words;
    $strResult = "";
    // cari data phone tambahan, jika ada
    if ($strDataID != "") {
        $strSQL = "SELECT t1.*, t2.letter_code FROM hrd_employee_mutation_department AS t1, ";
        $strSQL .= "hrd_employee_mutation AS t2 WHERE t1.id_mutation = t2.id ";
        $strSQL .= "AND t2.id_employee = '$strDataID' AND t2.status >= " . REQUEST_STATUS_APPROVED . " ";
        $strSQL .= "ORDER BY t1.department_date DESC ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $strResult .= "<tr>\n";
            $strResult .= "  <td>" . $rowDb['department_new'] . "&nbsp;</td>\n";
            $strResult .= "  <td align=center>" . pgDateFormat($rowDb['department_date'], "d-M-Y") . "&nbsp;</td>\n";
            $strResult .= "  <td>" . $rowDb['letter_code'] . "&nbsp;</td>\n";
            $strResult .= "</tr>\n";
        }
    }
    return $strResult;
} //getDataDepartment
// fungsi untuk mengambil history gaji
function getDataSalary($db, $strDataID = "")
{
    global $words;
    $strResult = "";
    // cari data phone tambahan, jika ada
    if ($strDataID != "") {
        $strSQL = "SELECT t1.*, t2.letter_code FROM hrd_employee_mutation_salary AS t1, ";
        $strSQL .= "hrd_employee_mutation AS t2 WHERE t1.id_mutation = t2.id ";
        $strSQL .= "AND t2.id_employee = '$strDataID' AND t2.status >= " . REQUEST_STATUS_APPROVED . " ";
        $strSQL .= "ORDER BY t1.salary_new_date DESC ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $strResult .= "<tr>\n";
            $strResult .= "  <td align=center>" . pgDateFormat($rowDb['salary_new_date'], "d-M-Y") . "&nbsp;</td>\n";
            $strResult .= "  <td align=right>" . standardFormat($rowDb['salary_new'], true) . "&nbsp;</td>\n";
            if ($rowDb['salary_old'] == 0) {
                $fltPersen = 100;
            } else {
                $fltPersen = (($rowDb['salary_new'] - $rowDb['salary_old']) / $rowDb['salary_old']) * 100;
            }
            $strResult .= "  <td align=right>" . standardFormat($fltPersen) . "&nbsp;</td>\n";
            $strResult .= "  <td align=right>" . $rowDb['letter_code'] . "&nbsp;</td>\n";
            $strResult .= "</tr>\n";
        }
    }
    return $strResult;
} //getDataSalary
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $stremployee_id = "";
    $stremployee_name = "";
    $bolEmployee = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE);
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    if ($strDataID == "") {
        redirectPage("employee_search.php");
        exit();
    }
    if ($bolCanView) {
        // ambil dur
        // cari info karyawan
        $strSQL = "SELECT employee_id, employee_name, flag,link_id FROM hrd_employee WHERE id = '$strDataID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['flag'] != 0 AND $rowDb['link_id'] != "") { // folder temporer
                $strDataID = $rowDb['link_id'];
            }
            $strEmployeeID = $rowDb['employee_id'];
            $strEmployeeName = strtoupper($rowDb['employee_name']);
            if ($bolEmployee && ($stremployee_id != $arrUserInfo['employee_id'])) {
                $bolCanView = false;
                redirectPage("employee_search.php");
                exit();
            }
        } else {
            redirectPage("employee_search.php");
            exit();
        }
        $strDataStatus = getDataStatus($db, $strDataID);
        $strDataPosition = getDataPosition($db, $strDataID);
        $strDataDepartment = getDataDepartment($db, $strDataID);
        if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
            $strDataSalary = getDataSalary($db, $strDataID);
        }
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
}
$strInitAction = "
  ";
//write this variable in every page
$tbsPage = new clsTinyButStrong;
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
if ($bolPrint) {
    $strMainTemplate = getTemplate("employeeHistoryPrint.html");
    $tbsPage->LoadTemplate("templates/employeeHistoryPrint.html");
} else {
    $strTemplateFile = getTemplate("employee_history.html");
    $tbsPage->LoadTemplate($strMainTemplate);
}
//$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
//  $tbsPage->LoadTemplate($strMainTemplate) ;
$tbsPage->Show();
?>