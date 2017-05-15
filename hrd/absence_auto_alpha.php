<?php

include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../classes/hrd/hrd_company.php');
include_once('../global/excelReader/excel_reader.php');
include_once('overtime_func.php');
include_once('activity.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
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

$db = new CdbClass();
if ($db->connect()) {
    $strMsgUnpaidLeave = '';
    $strMsgAutoAlpha = '';
    $strDateAutoAlphaFrom = isset($_REQUEST['dataDateAutoAlphaFrom']) ? $_REQUEST['dataDateAutoAlphaFrom'] : date('Y-m-d');
    $strDateAutoAlphaThru = isset($_REQUEST['dataDateAutoAlphaThru']) ? $_REQUEST['dataDateAutoAlphaThru'] : date('Y-m-d');
    $strDateUnpaidLeaveFrom = isset($_REQUEST['dataDateUnpaidLeaveFrom']) ? $_REQUEST['dataDateUnpaidLeaveFrom'] : date('Y-m-d');
    $strDateUnpaidLeaveThru = isset($_REQUEST['dataDateUnpaidLeaveThru']) ? $_REQUEST['dataDateUnpaidLeaveThru'] : date('Y-m-d');
    $strDataAbsenceTypeUnpaidLeave = isset($_REQUEST['dataTypeUnpaidLeave']) ? $_REQUEST['dataTypeUnpaidLeave'] : '';
    $strDataAbsenceTypeAutoAlpha = isset($_REQUEST['dataTypeAutoAlpha']) ? $_REQUEST['dataTypeAutoAlpha'] : '';

    $successIcon = '<i class="fa fa-exclamation-circle"></i>';
    if (isset($_POST['btnAutoAlpha']) && $bolCanApprove) {
        $strMsg = setAutoAlpha($db, $strDateAutoAlphaFrom, $strDateAutoAlphaThru, $strDataAbsenceTypeAutoAlpha);
        $strMsgAutoAlpha = '<div class="alert alert-info">' . $successIcon . $strMsg . '</div>';
    }

    if (isset($_POST['btnUnpaidLeave']) && $bolCanApprove) {
        $strMsg = setUnpaidLeave($db, $strDateUnpaidLeaveFrom, $strDateUnpaidLeaveThru, $strDataAbsenceTypeUnpaidLeave);
        $strMsgUnpaidLeave = '<div class="alert alert-info">' . $successIcon . $strMsg . '</div>';
    }
}

$strInputTypeAutoAlpha = getAbsenceTypeList($db,"dataTypeAutoAlpha",$arrData['dataType'],"$strSpecial",""," style=\"width:$strDefaultWidthPx\" onChange=\"onAbsenceTypeChange()\"");
$strInputDateAutoAlphaFrom = "<input class='form-control datepicker' type=text name='dataDateAutoAlphaFrom' id='dataDateAutoAlphaFrom' value='$strDateAutoAlphaFrom' data-date-format='yyyy-mm-dd'>";
$strInputDateAutoAlphaThru = "<input class='form-control datepicker' type=text name='dataDateAutoAlphaThru' id='dataDateAutoAlphaThru' value='$strDateAutoAlphaThru' data-date-format='yyyy-mm-dd'>";
$strBtnAutoAlpha = "<input class='btn btn-small btn-primary' name='btnAutoAlpha' id='btnAutoAlpha' value='Auto Alpha' type='submit'>";

$strInputTypeUnpaidLeave = getAbsenceTypeList($db,"dataTypeUnpaidLeave",$arrData['dataType'],"$strSpecial",""," style=\"width:$strDefaultWidthPx\" onChange=\"onAbsenceTypeChange()\"");
$strInputDateUnpaidLeaveFrom = "<input class='form-control datepicker' type=text name='dataDateUnpaidLeaveFrom' id='dataDateUnpaidLeaveFrom' value='$strDateUnpaidLeaveFrom' data-date-format='yyyy-mm-dd'>";
$strInputDateUnpaidLeaveThru = "<input class='form-control datepicker' type=text name='dataDateUnpaidLeaveThru' id='dataDateUnpaidLeaveThru' value='$strDateUnpaidLeaveThru' data-date-format='yyyy-mm-dd'>";
$strBtnUnpaidLeave = "<input class='btn btn-small btn-primary' name='btnUnpaidLeave' id='btnUnpaidLeave' value='Unpaid Leave' type='submit'>";

$tbsPage = new clsTinyButStrong;
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = getWords('auto alpha');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();

/**
 * Function to set auto alpha for active employees with no attendance on a certain period.
 *
 * @param $db
 * @param $strDateFrom
 * @param $strDateThru
 *
 * @return string
 */
function setAutoAlpha($db, $strDateFrom, $strDateThru, $strDataAbsenceType)
{
    $date = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
    $strDateNow = date('Y-m-d', $date);
    $strCurDate = $strDateFrom;
    $intCount = 0;
    while (dateCompare($strCurDate, $strDateThru) <= 0) {
        $strSQL = "SELECT emp.id,shf.shift_code,emp.employee_name, emp.employee_id FROM hrd_employee AS emp
                   LEFT JOIN (select id_employee,shift_code FROM hrd_shift_schedule_employee where shift_date = '$strCurDate') AS shf ON emp.id=shf.id_employee
                   WHERE emp.active=1 AND emp.join_date <= '$strCurDate' AND (emp.resign_date is null or emp.resign_date >= '$strCurDate') AND (emp.is_immune_auto_alpha = 0 OR emp.is_immune_auto_alpha IS NULL) AND
                      emp.id NOT IN (SELECT id_employee FROM hrd_attendance WHERE attendance_date = '$strCurDate')
                      AND emp.id NOT IN (SELECT id_employee FROM hrd_shift_schedule_employee AS t1 LEFT JOIN hrd_shift_type AS t2 ON t1.shift_code = t2.code WHERE t2.shift_off = TRUE AND t1.shift_date = '$strCurDate')
                      AND emp.id NOT IN (SELECT id_employee FROM hrd_trip WHERE date_from <= '$strCurDate' AND date_thru >= '$strCurDate')
                      AND emp.id NOT IN (SELECT id_employee FROM hrd_absence_detail WHERE absence_date = '$strCurDate')";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $intLastID = 0;
            $strIDEmployee = $rowDb['id'];
            $bolHoliday = true;
            if ($rowDb['shift_code'] == "") {
                $bolHoliday = isHoliday($strCurDate);
            } else {
                $bolHoliday = false;
            }
            if ($bolHoliday == false) {
                $strSQL2 = "INSERT INTO hrd_absence (id_employee, date_from, date_thru, absence_type_code, note, status) VALUES
                             ($strIDEmployee, '$strCurDate', '$strCurDate', '$strDataAbsenceType', 'Absence generated by system  on $strDateNow ', 2)";
                $resDb2 = $db->execute($strSQL2);
                $strSQL3 = "SELECT max(id) as last_id FROM hrd_absence WHERE id_employee = " . $rowDb['id'] . " AND absence_type_code = '$strDataAbsenceType' ";
                $resDb3 = $db->execute($strSQL3);
                while ($rowDb3 = $db->fetchrow($resDb3)) {
                    $intLastID = $rowDb3['last_id'];
                }
                $strSQL4 = "INSERT INTO hrd_absence_detail (id_absence, id_employee, absence_date, absence_type) VALUES
                              ($intLastID, $strIDEmployee, '$strCurDate', '$strDataAbsenceType')";
                $resDb4 = $db->execute($strSQL4);
                $intCount++;
            }
        }
        $strCurDate = getNextDate($strCurDate);
    }
    $strMsg = $intCount.' absence entry is generated as '.$strDataAbsenceType.' because there is no attendance on '.$strDateFrom.' through '.$strDateThru;
    return $strMsg;
}

/**
 * Function to set unapproved absence on a certain period to unpaid leave.
 *
 * @param $db
 * @param $strDateFrom
 * @param $strDateThru
 *
 * @return string
 */
function setUnpaidLeave($db, $strDateFrom, $strDateThru, $strDataAbsenceType) {
    $strCurDate = $strDateFrom;
    $strSQLUpdate = '';
    $intCount = 0;
    while (dateCompare($strCurDate, $strDateThru) <= 0) {
        $strSQL = "SELECT id_absence FROM hrd_absence_detail AS t1
                   LEFT JOIN hrd_absence AS t2 ON t1.id_absence = t2.id
                   WHERE t2.status < ".REQUEST_STATUS_APPROVED." AND t1.absence_date = '$strCurDate' ";
        if (isset($strDataAbsenceType) && $strDataAbsenceType != '') {
            $strSQL .= " AND t1.absence_type = '$strDataAbsenceType' ";
        }
        $res = $db->execute($strSQL);
        while ($row = $db->fetchrow($res)) {
            $strIDAbsence = $row['id_absence'];
            $strSQLUpdate .= "UPDATE hrd_absence SET absence_type_code = 'UL', status = 2, modified = now(), approved_time = now() WHERE id = '$strIDAbsence';
                              UPDATE hrd_absence_detail SET absence_type = 'UL', modified = now() WHERE id_absence = '$strIDAbsence';";
            if (isset($strIDAbsence) && $strIDAbsence !== '') {
                $intCount++;
            }
        }
        $strCurDate = getNextDate($strCurDate);
    }
    $res = $db->execute($strSQLUpdate);
    $strMsg = $intCount.' absence entry has been treated as unpaid leave on '.$strDateFrom.' through '.$strDateThru;
    return $strMsg;
}