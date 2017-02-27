<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
//include_once("../includes/krumo/class.krumo.php");
$dataPrivilege = getDataPrivileges(
    "mutation_edit.php",
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
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintStatus']) || isset($_REQUEST['btnPrintDepartment']) || isset($_REQUEST['btnPrintPosition']) || isset($_REQUEST['btnPrintSalary']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strEmployeeName = "";
$strWordsDateFrom = getWords("date from");
$strWordsDateThru = getWords("until");
$strWordsEmployeeID = getWords("employee id");
$strWordsCompany = getWords("company");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("sub section");
$strWordsListOf = getWords("list of");
$strWordsProposalDate = getWords("proposal date");
$strWordsLetterCode = getWords("letter code");
$strWordsName = getWords("name");
$strWordsGender = getWords("sex");
$strWordsStatusConfirmation = getWords("status confirmation");
$strWordsStatusChanges = getWords("status changes");
$strWordsGradeChanges = getWords("grade changes");
$strWordsDepartmentChanges = getWords("department changes");
$strWordsNote = getWords("note");
$strWordsStatus = getWords("status");
$strWordsOldLevel = getWords("old level");
$strWordsOldGrade = getWords("old grade");
$strWordsStartDate = getWords("start date");
$strWordsNewLevel = getWords("new level");
$strWordsNewGrade = getWords("new grade");
$strWordsOldDepartment = getWords("old department");
$strWordsNewDepartment = getWords("new department");
$strWordsNewBasicSalary = getWords("new basic salary");
$strWordsOldBasicSalary = getWords("old basic salary");
$strWordsNewPositionAllow = getWords("new position allowance");
$strWordsOldPositionAlow = getWords("old position allowance");
$strWordsNewMealAllow = getWords("new meal allowance");
$strWordsOldMealAllow = getWords("old meal allowance");
$strWordsNewTransportAllow = getWords("new transport allowance");
$strWordsOldTransportAllow = getWords("old transport allowance");
$strWordsNewVehicleAllow = getWords("new vehicle allowance");
$strWordsOldVehicleAllow = getWords("old vehicle allowance");
$strWordsSalaryChanges = getWords("salary changes");
$strWordsRequestStatus = getWords("request status");
$strWordsProposalEntry = getWords("proposal entry");
$strWordsProposalList = getWords("proposal list");
$strWordsShowData = getWords("show data");
$strWordsExcel = getWords("export excel");
$strWordsPrint = getWords("print");
$strWordsPrintStatusChanges = getWords("print status changes");
$strWordsPrintGradeChanges = getWords("print grade changes");
$strWordsPrintDepartmentChanges = getWords("print department changes");
$strHeader1 = "";
$strHeader2 = "";
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strButtons = "";
$strStyle = "";
$strHeaderEdit = "";
if ($bolPrint) {
    $strDisplayAll = $strDisplayStatus = $strDisplayPosition = $strDisplayDepartment = $strDisplaySalary = "style=\"display:none\" ";
}
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getHeader(&$strHeader1, &$strHeader2)
{
    global $strShowStatus;
    global $strShowPosition;
    global $strShowDepartment;
    global $strShowSalary;
    global $strWordsStatusChanges;
    global $strWordsStatus;
    global $strWordsDateFrom;
    global $strWordsDateThru;
    global $strWordsGradeChanges;
    global $strWordsOldLevel;
    global $strWordsOldGrade;
    global $strWordsNewLevel;
    global $strWordsNewGrade;
    global $strWordsDepartmentChanges;
    global $strWordsOldDepartment;
    global $strWordsNewDepartment;
    global $strWordsStartDate;
    global $strWordsSalaryChanges;
    global $strWordsOldBasicSalary, $strWordsNewBasicSalary;
    global $strWordsOldPositionAlow, $strWordsNewPositionAllow;
    global $strWordsOldMealAllow, $strWordsNewMealAllow;
    global $strWordsOldTransportAllow, $strWordsNewTransportAllow;
    global $strWordsOldVehicleAllow, $strWordsNewVehicleAllow;
    $strHeader1 = "";
    $strHeader2 = "<tr>";
    if ($strShowStatus == "t") {
        $strHeader1 .= "<th colspan=3 class=tableHeader>$strWordsStatusChanges</th>";
        $strHeader2 .= "
            <th nowrap class=\"tableHeader\">$strWordsStatus</th>
            <th nowrap class=\"tableHeader\">$strWordsDateFrom</th>
            <th nowrap class=\"tableHeader\">$strWordsDateThru</th>
            ";
    }
    if ($strShowPosition == "t") {
        $strHeader1 .= "<th colspan=6 class=tableHeader>$strWordsGradeChanges</th>";
        $strHeader2 .= "
            <th nowrap class=\"tableHeader\">$strWordsOldLevel</th>
            <th nowrap class=\"tableHeader\">$strWordsOldGrade</th>
            <th nowrap class=\"tableHeader\">$strWordsStartDate</th>
            <th nowrap class=\"tableHeader\">$strWordsNewLevel</th>
            <th nowrap class=\"tableHeader\">$strWordsNewGrade</th>
            <th nowrap class=\"tableHeader\">$strWordsStartDate</th>
            ";
    }
    if ($strShowDepartment == "t") {
        $strHeader1 .= "<th colspan=3 class=tableHeader>$strWordsDepartmentChanges</th>";
        $strHeader2 .= "
            <th nowrap class=\"tableHeader\">$strWordsOldDepartment</th>
            <th nowrap class=\"tableHeader\">$strWordsNewDepartment</th>
            <th nowrap class=\"tableHeader\">$strWordsStartDate</th>
            ";
    }
    if (false)//$strShowSalary == "t")
    {
        $strHeader1 .= "<th colspan=11 class=tableHeader>$strWordsSalaryChanges</th>";
        $strHeader2 .= "
            <th nowrap class=\"tableHeader\">$strWordsOldBasicSalary</th>
            <th nowrap class=\"tableHeader\">$strWordsNewBasicSalary</th>
            <th nowrap class=\"tableHeader\">$strWordsOldPositionAlow</th>
            <th nowrap class=\"tableHeader\">$strWordsNewPositionAllow</th>
            <th nowrap class=\"tableHeader\">$strWordsOldMealAllow</th>
            <th nowrap class=\"tableHeader\">$strWordsNewMealAllow</th>
            <th nowrap class=\"tableHeader\">$strWordsOldTransportAllow</th>
            <th nowrap class=\"tableHeader\">$strWordsNewTransportAllow</th>
            <th nowrap class=\"tableHeader\">$strWordsOldVehicleAllow</th>
            <th nowrap class=\"tableHeader\">$strWordsNewVehicleAllow</th>
            <th nowrap class=\"tableHeader\">$strWordsStartDate</th>";
    }
    $strHeader2 .= "</tr>";
}

function getData($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $ARRAY_REQUEST_STATUS;
    global $bolPrint;
    global $strShowStatus;
    global $strShowPosition;
    global $strShowDepartment;
    global $strShowSalary;
    global $strHeaderEdit;
    $intRows = 0;
    $strResult = "";
    // ambil dulu data employee, kumpulkan dalam array
    $i = 0;
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.gender ";
    $strSQL .= "FROM hrd_employee_mutation AS t1 LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE ";
    $strSQL .= "1=1 AND t1.type != 1 $strKriteria";
    //$strSQL .="proposal_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru'";
    $strSQL .= "ORDER BY $strOrder t1.proposal_date, t2.employee_id ";
    $resDb = $db->execute($strSQL);
    $strDateOld = "";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        /*
        if ($rowDb['status'] == '0') {
          $strClass = "bgNewRevised";
        } else if ($rowDb['status'] == '3') {
          $strClass = "bgDenied";
        } else {
          $strClass = "";
        }
        */
        $strClass = getCssClass($rowDb['status']);
        $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
        $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
        if (!$bolPrint) {
            $strResult .= "  <td><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" type=checkbox name='chkID$intRows' id='chkID$intRows' value=\"" . $rowDb['id'] . "\"></label></div></td>\n";
        }
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['proposal_date'], "d-M-Y") . "&nbsp;</td>\n";
        $strResult .= "  <td align=center>" . $rowDb['letter_code'] . "&nbsp;</td>";
        $strResult .= "  <td align=center><input type=hidden name='empID$intRows' value=\"" . $rowDb['id_employee'] . "\">" . $rowDb['employee_id'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
        //$strResult .= "  <td align=center>" .$strGender. "&nbsp;</td>";
        if ($strShowStatus == "t") {
            // status confirmation
            $strSQL = "SELECT * FROM hrd_employee_mutation_status WHERE id_mutation = '" . $rowDb['id'] . "' ";
            $resTmp = $db->execute($strSQL);
            if ($rowTmp = $db->fetchrow($resTmp)) {
                $strStatus = ($rowTmp['status_new'] != "99") ? $words[$ARRAY_EMPLOYEE_STATUS[$rowTmp['status_new']]] : getWords(
                    "resigned"
                );
                $strResult .= "  <td>" . $strStatus . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . pgDateFormat(
                        $rowTmp['status_date_from'],
                        "d-M-y"
                    ) . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . pgDateFormat(
                        $rowTmp['status_date_thru'],
                        "d-M-y"
                    ) . "&nbsp;</td>";
            } else {
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
            }
        }
        /*
        // resignment
        $strSQL  = "SELECT * FROM hrd_employee_mutation_resign WHERE id_mutation = '" .$rowDb['id']."' ";
        $resTmp = $db->execute($strSQL);
        if ($rowTmp = $db->fetchrow($resTmp)) {
          $strResult .= "  <td align=center>" .pgDateFormat($rowTmp['resign_date'], "d-M-y"). "&nbsp;</td>";
        } else {
          $strResult .= "  <td>&nbsp;</td>";
        }
  */
        if ($strShowPosition == "t") {
            // position changes
            $strSQL = "SELECT * FROM hrd_employee_mutation_position WHERE id_mutation = '" . $rowDb['id'] . "' ";
            $resTmp = $db->execute($strSQL);
            if ($rowTmp = $db->fetchrow($resTmp)) {
                $strResult .= "  <td align=center>" . $rowTmp['position_old'] . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . $rowTmp['grade_old'] . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . pgDateFormat(
                        $rowTmp['position_old_date'],
                        "d-M-y"
                    ) . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . $rowTmp['position_new'] . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . $rowTmp['grade_new'] . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . pgDateFormat(
                        $rowTmp['position_new_date'],
                        "d-M-y"
                    ) . "&nbsp;</td>";
            } else {
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
            }
        }
        if ($strShowDepartment == "t") {
            // department mutation
            $strSQL = "SELECT * FROM hrd_employee_mutation_department WHERE id_mutation = '" . $rowDb['id'] . "' ";
            $resTmp = $db->execute($strSQL);
            if ($rowTmp = $db->fetchrow($resTmp)) {
                $strResult .= "  <td>" . getDepartmentName($rowTmp['department_old']) . "&nbsp;</td>";
                $strResult .= "  <td>" . getDepartmentName($rowTmp['department_new']) . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . pgDateFormat(
                        $rowTmp['department_new_date'],
                        "d-M-y"
                    ) . "&nbsp;</td>";
            } else {
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
            }
        }
        if (false) //($strShowSalary == "t")
        {
            // salary increase
            $strSQL = "SELECT * FROM hrd_employee_mutation_salary WHERE id_mutation = '" . $rowDb['id'] . "' ";
            $resTmp = $db->execute($strSQL);
            if ($rowTmp = $db->fetchrow($resTmp)) {
                $strResult .= "  <td align=right>" . $rowTmp['basic_salary_old'] . "&nbsp;</td>";
                $strResult .= "  <td align=right>" . $rowTmp['basic_salary_new'] . "&nbsp;</td>";
                $strResult .= "  <td align=right>" . $rowTmp['position_allow_old'] . "&nbsp;</td>";
                $strResult .= "  <td align=right>" . $rowTmp['position_allow_new'] . "&nbsp;</td>";
                $strResult .= "  <td align=right>" . $rowTmp['meal_allow_old'] . "&nbsp;</td>";
                $strResult .= "  <td align=right>" . $rowTmp['meal_allow_new'] . "&nbsp;</td>";
                $strResult .= "  <td align=right>" . $rowTmp['transport_allow_old'] . "&nbsp;</td>";
                $strResult .= "  <td align=right>" . $rowTmp['transport_allow_new'] . "&nbsp;</td>";
                $strResult .= "  <td align=right>" . $rowTmp['vehicle_allow_old'] . "&nbsp;</td>";
                $strResult .= "  <td align=right>" . $rowTmp['vehicle_allow_new'] . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . pgDateFormat($rowTmp['salary_new_date'], "d-M-y") . "&nbsp;</td>";
                //$strResult .= "  <td>" .$rowTmp['salaryNote']. "&nbsp;</td>";
            } else {
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
            }
        }
        $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "&nbsp;</td>";
        if (!$bolPrint && ($rowDb['status'] == REQUEST_STATUS_NEW || $_SESSION['sessionUserRole'] >= ROLE_ADMIN)) {
            $strHeaderEdit = "<td rowspan=2 nowrap class=tableHeader>&nbsp;</td>";
            $strResult .= "  <td align=center><a style=\"text-decoration: none;\" class=\"btn btn-primary btn-xs\" href=\"mutation_edit.php?dataID=" . $rowDb['id'] . "\">" . $words['edit'] . "</a>&nbsp;</td>";
        } else if (!$bolPrint) {
            $strHeaderEdit = "<td rowspan=2 nowrap class=tableHeader>&nbsp;</td>";
            $strResult .= "  <td align=center><a style=\"text-decoration: none;\" class=\"btn btn-primary btn-xs\" href=\"mutation_denied.php?dataID=" . $rowDb['id'] . "\">" . $words['edit'] . "</a>&nbsp;</td>";
        } else {
            //$strResult .= "  <td>&nbsp;</td>";
            $strHeaderEdit = "";
        }
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // getData
// fungsi untuk menampilkan data, tapi hanya perubahan status aja
function getDataStatus($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $ARRAY_REQUEST_STATUS;
    global $bolPrint;
    $intRows = 0;
    $strResult = "";
    // ambil dulu data employee, kumpulkan dalam array
    $i = 0;
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.gender, ";
    $strSQL .= "t3.status_new, t3.status_date_from, t3.status_date_thru ";
    $strSQL .= "FROM hrd_employee_mutation_status AS t3 ";
    $strSQL .= "LEFT JOIN hrd_employee_mutation AS t1 ON t3.id_mutation = t1.id ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.type=0 $strKriteria ";
    //$strSQL .= "AND proposal_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
    $strSQL .= "ORDER BY $strOrder t1.proposal_date, t2.employee_id ";
    $resDb = $db->execute($strSQL);
    $strDateOld = "";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        //$strGender = ($rowDb['gender'] == 0) ? $words['female'] : $words['male'];
        $strStatus = ($rowDb['status_new'] != "99") ? $words[$ARRAY_EMPLOYEE_STATUS[$rowDb['status_new']]] : getWords(
            "resigned"
        );
        $strClass = getCssClass($rowDb['status']);
        $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
        $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['proposal_date'], "d-M-Y") . "&nbsp;</td>\n";
        $strResult .= "  <td align=center>" . $rowDb['letter_code'] . "&nbsp;</td>\n";
        $strResult .= "  <td align=center>" . $rowDb['employee_id'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
        //$strResult .= "  <td align=center>" .$strGender. "&nbsp;</td>";
        $strResult .= "  <td>" . $strStatus . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['status_date_from'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['status_date_thru'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // getDataStatus
// fungsi untuk menampilkan data, tapi hanya perubahan jabatan saja
function getDataPosition($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $ARRAY_REQUEST_STATUS;
    global $bolPrint;
    $intRows = 0;
    $strResult = "";
    // ambil dulu data employee, kumpulkan dalam array
    $i = 0;
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.gender, ";
    $strSQL .= "t3.position_old, t3.position_old_date, t3.grade_old, ";
    $strSQL .= "t3.position_new, t3.position_new_date, t3.grade_new ";
    $strSQL .= "FROM hrd_employee_mutation_position AS t3 ";
    $strSQL .= "LEFT JOIN hrd_employee_mutation AS t1 ON t3.id_mutation = t1.id ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.type=0 $strKriteria ";
    //$strSQL .= "AND proposal_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
    $strSQL .= "ORDER BY $strOrder t1.proposal_date, t2.employee_id ";
    $resDb = $db->execute($strSQL);
    $strDateOld = "";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        //($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
        $strClass = getCssClass($rowDb['status']);
        $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
        $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['proposal_date'], "d-M-Y") . "&nbsp;</td>\n";
        $strResult .= "  <td align=center>" . $rowDb['letter_code'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['employee_id'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
        //$strResult .= "  <td align=center>" .$strGender. "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['position_old'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['grade_old'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['position_old_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['position_new'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['grade_new'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['position_new_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // getDataPosition
// fungsi untuk menampilkan data, tapi hanya perubahan department aja
function getDataDepartmentSpecial($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $ARRAY_REQUEST_STATUS;
    global $bolPrint;
    $intRows = 0;
    $strResult = "";
    // ambil dulu data employee, kumpulkan dalam array
    $i = 0;
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.gender, ";
    $strSQL .= "t3.department_old, t3.department_new, t3.department_new_date ";
    $strSQL .= "FROM hrd_employee_mutation_department AS t3 ";
    $strSQL .= "LEFT JOIN hrd_employee_mutation AS t1 ON t3.id_mutation = t1.id ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.type=0 $strKriteria ";
    //$strSQL .= "AND proposal_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
    $strSQL .= "ORDER BY $strOrder t1.proposal_date, t2.employee_id ";
    $resDb = $db->execute($strSQL);
    $strDateOld = "";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        //($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
        $strClass = getCssClass($rowDb['status']);
        $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
        $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['proposal_date'], "d-M-Y") . "&nbsp;</td>\n";
        $strResult .= "  <td>" . $rowDb['letter_code'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_id'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
        //$strResult .= "  <td align=center>" .$strGender. "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['department_old'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['department_new'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['department_new_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // getDataDepartment
function getDataSalary($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $ARRAY_REQUEST_STATUS;
    global $bolPrint;
    $intRows = 0;
    $strResult = "";
    // ambil dulu data employee, kumpulkan dalam array
    $i = 0;
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.gender, ";
    $strSQL .= "t3.basic_salary_old, t3.basic_salary_new, t3.position_allow_old, position_allow_new, ";
    $strSQL .= "meal_allow_old, meal_allow_new, transport_allow_old, transport_allow_new, vehicle_allow_old, vehicle_allow_new, salary_new_date ";
    $strSQL .= "FROM hrd_employee_mutation_salary AS t3 ";
    $strSQL .= "LEFT JOIN hrd_employee_mutation AS t1 ON t3.id_mutation = t1.id ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.type=0 $strKriteria ";
    //$strSQL .= "AND proposal_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
    $strSQL .= "ORDER BY $strOrder t1.proposal_date, t2.employee_id ";
    $resDb = $db->execute($strSQL);
    $strDateOld = "";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        //($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
        $strClass = getCssClass($rowDb['status']);
        $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
        $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['proposal_date'], "d-M-Y") . "&nbsp;</td>\n";
        $strResult .= "  <td>" . $rowDb['letter_code'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_id'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
        //$strResult .= "  <td align=center>" .$strGender. "&nbsp;</td>";
        $strResult .= "  <td align=right>" . $rowDb['basic_salary_old'] . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . $rowDb['basic_salary_new'] . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . $rowDb['position_allow_old'] . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . $rowDb['position_allow_new'] . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . $rowDb['meal_allow_old'] . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . $rowDb['meal_allow_new'] . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . $rowDb['transport_allow_old'] . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . $rowDb['transport_allow_new'] . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . $rowDb['vehicle_allow_old'] . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . $rowDb['vehicle_allow_new'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['salary_new_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // getDataSalary
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
                if ($_SESSION['sessionUserRole'] >= ROLE_ADMIN || $rowTmp['status'] < REQUEST_STATUS_APPROVED) {
                    $strIDEmployee = $rowTmp['id_employee'];
                    $strSQL = "DELETE FROM hrd_employee_mutation_status WHERE id_mutation = '$strValue'; ";
                    $strSQL .= "DELETE FROM hrd_employee_mutation_resign WHERE id_mutation = '$strValue'; ";
                    $strSQL .= "DELETE FROM hrd_employee_mutation_department WHERE id_mutation = '$strValue'; ";
                    $strSQL .= "DELETE FROM hrd_employee_mutation_position WHERE id_mutation = '$strValue'; ";
                    //$strSQL .= "DELETE FROM hrd_employee_mutation_salary WHERE id_mutation = '$strValue'; ";
                    $strSQL .= "DELETE FROM hrd_employee_mutation WHERE id = '$strValue'; ";
                    $resExec = $db->execute($strSQL);
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
    global $ARRAY_EMPLOYEE_STATUS;
    $arrTempStatus = $ARRAY_EMPLOYEE_STATUS;
    $arrTempStatus[99] = "Resigned";
    if (!is_numeric($intStatus)) {
        return false;
    }
    $i = 0;
    $strUpdate = "";
    $strmodified_byID = $_SESSION['sessionUserID'];
    $strUpdate = getStatusUpdateString($intStatus);
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $strIDEmployee = $_REQUEST['empID' . substr($strIndex, 5)];
            $i++;
            $strSQLx = "SELECT status_old, status_new, grade_old, grade_new, division_old, department_old, section_old, division_new,
                    department_new, section_new, employee_name, t0.approved_time, t0.status 
                    FROM hrd_employee_mutation AS t0 
                    LEFT JOIN hrd_employee_mutation_status AS t1 ON t0.id = t1.id_mutation
                    LEFT JOIN hrd_employee_mutation_position AS t2 ON t0.id = t2.id_mutation
                    LEFT JOIN hrd_employee_mutation_department  AS t3 ON t0.id = t3.id_mutation
                    LEFT JOIN hrd_employee AS t4 ON t0.id_employee = t4.id
                    WHERE t0.id = '$strValue' ";
            $resDb = $db->execute($strSQLx);
            if ($rowDb = $db->fetchrow($resDb)) {
                //only new entries can be edited and updated
                if (isProcessable($rowDb['status'], $intStatus)) {
                    $strSQL = "UPDATE hrd_employee_mutation SET $strUpdate status = '$intStatus' WHERE id = $strValue ";
                    //$strSQL .= "verification_date = now(), approval_date = NULL ";
                    //$strSQL .= "WHERE id = '$strValue' AND status <>  ".REQUEST_STATUS_APPROVED; // yang udah apprve gak boleh diedit
                    $resExec = $db->execute($strSQL);
                    updateEmployeeCareerData($db, $strValue, $strIDEmployee);
                    $strLog = "";
                    if ($rowDb['status_old'] != $rowDb['status_new']) {
                        $strLog .= "status " . $arrTempStatus[$rowDb['status_old']] . " to " . $arrTempStatus[$rowDb['status_new']];
                    }
                    if ($rowDb['grade_old'] != $rowDb['grade_new']) {
                        $strLog .= "grade " . $rowDb['grade_old'] . " to " . $rowDb['grade_new'];
                    }
                    if ($rowDb['section_old'] != $rowDb['section_new']) {
                        $strLog .= "section " . $rowDb['section_old'] . " to " . $rowDb['section_new'];
                    } else if ($rowDb['department_old'] != $rowDb['department_new']) {
                        $strLog .= "department " . $rowDb['department_old'] . " to " . $rowDb['department_new'];
                    } else if ($rowDb['division_old'] != $rowDb['division_new']) {
                        $strLog .= "division " . $rowDb['division_old'] . " to " . $rowDb['division_new'];
                    }
                    writeLog(
                        ACTIVITY_EDIT,
                        MODULE_EMPLOYEE,
                        $rowDb['employee_name'] . " - " . $rowDb['approved_time'] . ". \n" .
                        $strLog,
                        $intStatus
                    );
                }
            }
        }
    }
    if ($i > 0) {
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
    } else {
        callChangeStatus();
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    getDefaultSalaryPeriode($strDefaultFrom, $strDefaultThru);
    $strDataDateFrom = (isset($_SESSION['sessionFilterDateFrom'])) ? $_SESSION['sessionFilterDateFrom'] : date("Y-m-d");
    $strDataDateThru = (isset($_SESSION['sessionFilterDateThru'])) ? $_SESSION['sessionFilterDateThru'] : date("Y-m-d");
    $strDataDivision = (isset($_SESSION['sessionFilterDivision'])) ? $_SESSION['sessionFilterDivision'] : "";
    $strDataDepartment = (isset($_SESSION['sessionFilterDepartment'])) ? $_SESSION['sessionFilterDepartment'] : "";
    $strDataSection = (isset($_SESSION['sessionFilterSection'])) ? $_SESSION['sessionFilterSection'] : "";
    $strDataSubSection = (isset($_SESSION['sessionFilterSubSection'])) ? $_SESSION['sessionFilterSubSection'] : "";
    $strDataEmployee = (isset($_SESSION['sessionFilterEmployee'])) ? $_SESSION['sessionFilterEmployee'] : "";
    $strDataRequestStatus = (isset($_SESSION['sessionFilterRequestStatus'])) ? $_SESSION['sessionFilterRequestStatus'] : "";
    $strDataEmployeeStatus = (isset($_SESSION['sessionFilterEmployeeStatus'])) ? $_SESSION['sessionFilterEmployeeStatus'] : "";
    if (isset($_REQUEST['dataDateFrom'])) {
        $strDataDateFrom = $_REQUEST['dataDateFrom'];
    }
    if (isset($_REQUEST['dataDateThru'])) {
        $strDataDateThru = $_REQUEST['dataDateThru'];
    }
    if (isset($_REQUEST['dataDivision'])) {
        $strDataDivision = $_REQUEST['dataDivision'];
    }
    if (isset($_REQUEST['dataDepartment'])) {
        $strDataDepartment = $_REQUEST['dataDepartment'];
    }
    if (isset($_REQUEST['dataSection'])) {
        $strDataSection = $_REQUEST['dataSection'];
    }
    if (isset($_REQUEST['dataSubSection'])) {
        $strDataSubSection = $_REQUEST['dataSubSection'];
    }
    if (isset($_REQUEST['dataEmployee'])) {
        $strDataEmployee = $_REQUEST['dataEmployee'];
    }
    if (isset($_REQUEST['dataRequestStatus'])) {
        $strDataRequestStatus = $_REQUEST['dataRequestStatus'];
    }
    if (isset($_REQUEST['dataEmployeeStatus'])) {
        $strDataEmployeeStatus = $_REQUEST['dataEmployeeStatus'];
    }
    $strShowStatus = (isset($_REQUEST['chkStatus']) && $_REQUEST['chkStatus'] == "t") ? "t" : "f";
    $strShowPosition = (isset($_REQUEST['chkPosition']) && $_REQUEST['chkPosition'] == "t") ? "t" : "f";
    $strShowDepartment = (isset($_REQUEST['chkDepartment']) && $_REQUEST['chkDepartment'] == "t") ? "t" : "f";
    $strShowSalary = ""; //(isset($_REQUEST['chkSalary']) && $_REQUEST['chkSalary'] =="t")         ? "t" : "f";
    $_SESSION['sessionFilterDateFrom'] = $strDataDateFrom;
    $_SESSION['sessionFilterDateThru'] = $strDataDateThru;
    $_SESSION['sessionFilterDivision'] = $strDataDivision;
    $_SESSION['sessionFilterDepartment'] = $strDataDepartment;
    $_SESSION['sessionFilterSection'] = $strDataSection;
    $_SESSION['sessionFilterSubSection'] = $strDataSubSection;
    $_SESSION['sessionFilterEmployee'] = $strDataEmployee;
    $_SESSION['sessionFilterRequestStatus'] = $strDataRequestStatus;
    $_SESSION['sessionFilterEmployeeStatus'] = $strDataEmployeeStatus;
    scopeData(
        $strDataEmployee,
        $strDataSubSection,
        $strDataSection,
        $strDataDepartment,
        $strDataDivision,
        $_SESSION['sessionUserRole'],
        $arrUserInfo
    );
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    $strKriteria = "";
    if ($strDataDateFrom != "" && $strDataDateThru != "") {
        $strKriteria .= "AND proposal_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
    }
    if ($strDataDivision != "") {
        $strKriteria .= "AND division_code = '$strDataDivision' ";
    }
    if ($strDataDepartment != "") {
        $strKriteria .= "AND department_code = '$strDataDepartment' ";
    }
    if ($strDataSection != "") {
        $strKriteria .= "AND section_code = '$strDataSection' ";
    }
    if ($strDataSubSection != "") {
        $strKriteria .= "AND sub_section_code = '$strDataSubSection' ";
    }
    if ($strDataEmployee != "") {
        $strKriteria .= "AND employee_id = '$strDataEmployee' ";
    }
    if ($strDataRequestStatus != "") {
        $strKriteria .= "AND status = '$strDataRequestStatus' ";
    }
    $strKriteria .= $strKriteriaCompany;
    if ($bolCanView) {
        if (validStandardDate($strDataDateFrom) && validStandardDate($strDataDateThru)) {
            // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
            getHeader($strHeader1, $strHeader2);
            $strDataDetail = getData($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
            if ($bolPrint) {
                // perintah printing, cek jenis yg diprint
                if (isset($_REQUEST['btnPrintStatus'])) {
                    $strDisplayStatus = ""; // biar tampilkan
                    $strDataDetail = getDataStatus(
                        $db,
                        $strDataDateFrom,
                        $strDataDateThru,
                        $intTotalData,
                        $strKriteria
                    );
                } else if (isset($_REQUEST['btnPrintPosition'])) {
                    $strDisplayPosition = "";
                    $strDataDetail = getDataPosition(
                        $db,
                        $strDataDateFrom,
                        $strDataDateThru,
                        $intTotalData,
                        $strKriteria
                    );
                } else if (isset($_REQUEST['btnPrintDepartment'])) {
                    $strDisplayDepartment = "";
                    $strDataDetail = getDataDepartmentSpecial(
                        $db,
                        $strDataDateFrom,
                        $strDataDateThru,
                        $intTotalData,
                        $strKriteria
                    );
                } else if (isset($_REQUEST['btnPrintSalary'])) {
                    $strDisplaySalary = "";
                    $strDataDetail = getDataSalary(
                        $db,
                        $strDataDateFrom,
                        $strDataDateThru,
                        $intTotalData,
                        $strKriteria
                    );
                } else if (isset($_REQUEST['btnPrint'])) {
                    $strShowStatus = true;
                    $strShowPosition = true;
                    $strShowDepartment = true;
                    $strShowSalary = true;
                    $strDisplayAll = "";
                    $strDataDetail = getData($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
                } else {
                    $strDisplayAll = "";
                }
                if (isset($_REQUEST['btnExcel'])) {
                    // ambil data CSS-nya
                    $strShowStatus = true;
                    $strShowPosition = true;
                    $strShowDepartment = true;
                    $strShowSalary = false;//true;
                    $strDisplayAll = "";
                    $strDataDetail = getData($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
                    if (file_exists("../css/default_bw.css")) {
                        $strStyle = "../css/default_bw.css";
                    }
                    $strPrintCss = "";
                    $strPrintInit = "";
                    headeringExcel("mutationList.xls");
                }
            }
        } else {
            $strDataDetail = "";
        }
    } else {
        showError("view_denied");
    }
    $intDefaultWidthPx = 200;
    $strInputDateFrom = "<input class=\"form-control datepicker\" type=text name=dataDateFrom id=dataDateFrom maxlength=10 value=\"2012-01-01\" data-date-format=\"yyyy-mm-dd\" >";
    $strInputDateThru = "<input class=\"form-control datepicker\" type=text name=dataDateThru id=dataDateThru maxlength=10 value=\"$strDataDateThru\" data-date-format=\"yyyy-mm-dd\" >";
    $strInputEmployee = "<input class=\"form-control\" type=text name=dataEmployee id=dataEmployee maxlength=30 value=\"$strDataEmployee\" $strEmpReadonly>";
    $strInputDivision = getDivisionList(
        $db,
        "dataDivision",
        $strDataDivision,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['division']
    );
    $strInputDepartment = getDepartmentList(
        $db,
        "dataDepartment",
        $strDataDepartment,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['department']
    );
    $strInputSection = getSectionList(
        $db,
        "dataSection",
        $strDataSection,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['section']
    );
    $strInputSubSection = getSubSectionList(
        $db,
        "dataSubSection",
        $strDataSubSection,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['sub_section']
    );
    $strInputRequestStatus = getComboFromArray(
        $ARRAY_REQUEST_STATUS,
        "dataRequestStatus",
        $strDataRequestStatus,
        $strEmptyOption,
        "style=\"width:125\""
    );
    //handle user company-access-right
    $strInputCompany = getCompanyList(
        $db,
        "dataCompany",
        $strDataCompany,
        $strEmptyOption2,
        $strKriteria2,
        "style=\"width:125\" "
    );
    $strInputShowStatus = generateCheckBox(
        "chkStatus",
        $strShowStatus,
        "",
        "onClick=\"setHiddenChk(this, this.name, this.value)\"",
        $strWordsStatusChanges
    );
    $strInputShowPosition = generateCheckBox(
        "chkPosition",
        $strShowPosition,
        "",
        "onClick=\"setHiddenChk(this, this.name, this.value)\""
    );
    $strInputShowDepartment = generateCheckBox(
        "chkDepartment",
        $strShowDepartment,
        "",
        "onClick=\"setHiddenChk(this, this.name, this.value)\"",
        $strWordsDepartmentChanges
    );
    $strInputShowSalary = ""; //generateCheckBox("chkSalary", $strShowSalary, "", "onClick=\"setHiddenChk(this, this.name, this.value)\"");
    // informasi tanggal kehadiran
    if ($strDataDateFrom == $strDataDateThru) {
        $strInfo .= strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
    } else {
        $strInfo .= strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
        $strInfo .= " >> " . strtoupper(pgDateFormat($strDataDateThru, "d-M-Y"));
    }
    $strButtons = generateRoleButtons($bolCanEdit, $bolCanDelete, $bolCanCheck, $bolCanApprove, $bolCanAcknowledge);
    $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDataDateFrom\">";
    $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDataDateThru\">";
    $strHidden .= "<input type=hidden name=dataDivision value=\"$strDataDivision\">";
    $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
    $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
    $strHidden .= "<input type=hidden name=dataSubSection value=\"$strDataSubSection\">";
    $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
    $strHidden .= "<input type=hidden name=dataRequestStatus value=\"$strDataRequestStatus\">";
    $strHidden .= "<input type=hidden name=chkStatus value=\"$strShowStatus\">";
    $strHidden .= "<input type=hidden name=chkPosition value=\"$strShowPosition\">";
    $strHidden .= "<input type=hidden name=chkDepartment value=\"$strShowDepartment\">";
    $strHidden .= "<input type=hidden name=chkDepartment value=\"$strShowSalary\">";
}
$tbsPage = new clsTinyButStrong;
if (!empty($strDataEmployee)) {
    $employeeData = getEmployeeInfoByCode($db, $strDataEmployee, 'employee_name');
    $strEmployeeName = $employeeData['employee_name'];
}
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('employee mutation request list');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = employeeMutationSubmenu($strWordsProposalList);
$strWordsRequestList = getWords('request list');
//------------------------------------------------
if ($bolPrint) {
    $strTemplateFile = getTemplate("mutation_list_print.html");
    $tbsPage->LoadTemplate($strTemplateFile);
} else {
    $strTemplateFile = getTemplate("mutation_list.html");
    $tbsPage->LoadTemplate($strMainTemplate);
}
$tbsPage->Show();
?>