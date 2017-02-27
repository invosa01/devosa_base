<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_education_level.php');
include_once('../global/employee_function.php');
include_once("activity.php");
include_once("overtime_func.php");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    //$bolIsEmployee = isUserEmployee();
    $arrUserList = getAllUserInfo($db);
    //sync
    if ($_SESSION[sessionUser] == "hendri") {
        $yesterday = date("Y-m-d", strtotime("-20 days"));
        $last3days = date("Y-m-d", strtotime("-25 days"));
        $todayname = date("D");
        $today = date("Y-m-d");
        if ($todayname == "Mon") {
            $strFrom = $last3days;
            $strThru = $today;
        } else {
            $strFrom = $yesterday;
            $strThru = $today;
        }
        syncShiftAttendance($db, $strFrom, $strThru, "");
        syncOvertimeApplication($db, $strFrom, $strThru, "", "");
        syncLateEarly($db, $strFrom, $strThru, "", "");
    }
    header("location:main.php");
}
?>