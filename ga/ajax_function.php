<?php
if (!session_id()) {
  session_start();
}
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  echo "Session time out, please re-login!";
  exit();
}
include_once('global.php');
if ((!isset($_GET['ajax'])) || ($_GET['ajax'] != 1)) {
  echo "Sorry, must be called from AJAX request!";
  exit();
}
$db = new cDbClass;
$db->connect();
$action = getGetValue("action");
switch ($action) {
  case "getRecruitmentPlan" :
    $strPlanID = getGetValue('idPlan');
    echo getRecruitmentPlan($strPlanID);
    break;
  case "getWarningHistory" :
    $strEmployeeID = getGetValue('employeeID');
    echo getWarningHistory($strEmployeeID);
    break;
  case "getOvertimeInfo" :
    $strEmployeeID = getGetValue('employeeID');
    $strDate = getGetValue('date');
    echo getOvertimeInfo($strEmployeeID, $strDate);
    break;
  case "getAnnualLeaveQuota" :
    $strEmployeeID = getGetValue('employee_id');
    echo getAnnualLeaveQuota($strEmployeeID);
    break;
  case "getAbsenceDuration" :
    $strEmployeeID = getGetValue('employee_id');
    $strStartDate = getGetValue('start_date');
    $strFinishDate = getGetValue('finish_date');
    echo getAbsenceDuration($strEmployeeID, $strStartDate, $strFinishDate);
    break;
  case "getLeaveTolerance" :
    $strAbsenceCode = getGetValue('absence_type_code');
    echo getLeaveTolerance($strAbsenceCode);
    break;
  case "getAttendanceInfo" :
    $strEmployeeID = getGetValue('employee_id');
    $strStartDate = getGetValue("date");
    echo getAttendanceInfo($strEmployeeID, $strStartDate);
    break;
  default :
    echo "You must set any action first!";
    break;
}
exit();
function getWarningHistory($strEmployeeID)
{
  global $db;
  $strDataID = getGetValue('dataID');
  $strResult = "";
  $strResult .= "<span style=\"font-size: 11pt; font-weight: bold\">List of Warning History</span>";
  $strResult .= "<table class=\"gridTable\" border=0 cellpadding=1 cellspacing=0>\n";
  $strResult .= "<tr>\n";
  $strResult .= "  <th>" . getWords("warning date") . "</th>\n";
  $strResult .= "  <th>" . getWords("warning type") . "</th>\n";
  $strResult .= "  <th>" . getWords("duration (days)") . "</th>\n";
  $strResult .= "  <th>" . getWords("due date") . "</th>\n";
  $strResult .= "  <th>" . getWords("note") . "</th>\n";
  $strResult .= "</tr>\n";
  $counter = 0;
  if ($strEmployeeID != "") {
    $strSQL = "
        SELECT t1.*, t2.employee_id
          FROM hrd_employee_warning AS t1 
                INNER JOIN hrd_employee AS t2 
                  ON t1.id_employee = t2.id 
          WHERE t2.employee_id = '$strEmployeeID' 
          ORDER BY t1.warning_date DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $counter++;
      if ($rowDb['id'] == $strDataID) {
        $strCSSClass = " class=\"bgCheckedData\"";
      } else {
        $strCSSClass = "";
      }
      $strResult .= "<tr $strCSSClass>\n";
      $strResult .= "  <td nowrap>" . pgDateFormat($rowDb['warning_date'], "d-M-y") . "</td>\n";
      $strResult .= "  <td nowrap>" . $rowDb['warning_code'] . "</td>\n";
      $strResult .= "  <td align=right nowrap>" . $rowDb['duration'] . "</td>\n";
      $strResult .= "  <td nowrap>" . pgDateFormat($rowDb['due_date'], "d-M-y") . "</td>\n";
      $strResult .= "  <td>" . $rowDb['note'] . "</td>\n";
      $strResult .= "</tr>\n";
    }
  }
  if ($counter == 0) {
    $strResult .= "<tr><td colspan=5>There are no warning data</td></tr>\n";
  }
  $strResult .= "</table>\n";
  return $strResult;
}

function getRecruitmentPlan($strPlanID)
{
  global $db;
  $arrData = [];
  if ($strPlanID != "") {
    $strSQL = "SELECT t1.* FROM \"hrdRecruitmentPlan\" AS t1 ";
    $strSQL .= "WHERE t1.id = '$strPlanID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      //$arrData['dataDate'] = $rowDb['recruitmentDate'];
      $arrData['dataDepartment'] = $rowDb['departmentCode'];
      $arrData['dataPosition'] = $rowDb['position'];
      $arrData['dataEmployeeStatus'] = $rowDb['employeeStatus'];
      $arrData['dataNumber'] = $rowDb['number'];
      $arrData['dataDueDate'] = $rowDb['dueDate'];
      $arrData['dataDescription'] = $rowDb['description'];
      $arrData['dataMinAge'] = $rowDb['minAge'];
      $arrData['dataMaxAge'] = $rowDb['maxAge'];
      $arrData['dataGender'] = $rowDb['gender'];
      $arrData['dataMarital'] = $rowDb['maritalStatus'];
      $arrData['dataEducationLevel'] = $rowDb['educationLevel'];
      $arrData['dataEducation'] = $rowDb['education'];
      $arrData['dataWork'] = $rowDb['workExperience'];
      $arrData['dataQualification'] = $rowDb['qualification'];
      //$arrData['dataStatus'] = $rowDb['status'];
      $arrData['dataCost'] = $rowDb['cost'];
      $arrData['dataPIC'] = $rowDb['PIC'];
      $arrData['dataPlan'] = $rowDb['id'];
    }
  }
  return implode("|||", $arrData);
}

function getOvertimeInfo($strID, $strDate)
{
  global $db;
  include_once('functionEmployee.php');
  if ($strID == "") {
    return "";
  }
  $strID = getIDEmployee($db, $strID);
  if (!is_numeric($strID)) {
    return "";
  }
  $arrResult = [];
  //get department head
  /*$strSQL = "SELECT \"departmentCode\" FROM \"hrdEmployee\" WHERE id = '$strID'";
  $res = $db->execute($strSQL);
  if ($row = $db->fetchrow($res))
  {
    $strSQL = "SELECT \"id\", \"employeeName\", \"positionCode\" FROM \"hrdEmployee\" WHERE \"departmentCode\" = '".$row['departmentCode']."' AND \"salaryGradeCode\" = 'B'";
    $res = $db->execute($strSQL);

    if ($row = $db->fetchrow($res))
    {
      $arrResult[] = $row['id']."_".$row['employeeName']."_ (".$row['positionCode'].")";
      while ($row = $db->fetchrow($res))
        $arrResult[] = $row['id']."_".$row['employeeName']."_ (".$row['positionCode'].")";
    }
    else
    {
      $strSQL = "SELECT \"id\", \"employeeName\", \"positionCode\" FROM \"hrdEmployee\" WHERE \"salaryGradeCode\" = 'B'";
      $res = $db->execute($strSQL);
      while ($row = $db->fetchrow($res))
        $arrResult[] = $row['id']."_".$row['employeeName']."_ (".$row['positionCode'].")";
    }
  }

  $strResult = join($arrResult, "|");*/
  $strResult = "";
  // get Shift Data
  $strSQL = "SELECT \"shiftCode\" FROM \"hrdShiftScheduleEmployee\" WHERE \"shiftDate\" = '$strDate' AND \"idEmployee\" = '$strID'";
  $resS = $db->execute($strSQL);
  if ($rowS = $db->fetchrow($resS)) {
    $strResult .= $rowS['shiftCode'];
    //ambil jam selesai kerja berdasarkan shift
    $strResult .= "|||" . getEmployeeWorkingTime($rowS['shiftCode'], $strDate, "|||");
  } else {
    //ambil jam selesai kerja normal
    $strResult .= "|||" . getEmployeeWorkingTime(null, $strDate, "|||");
  }
  // get Attendance Data
  $strSQL = "SELECT \"attendanceStart\", \"attendanceFinish\" FROM \"hrdAttendance\" WHERE \"attendanceDate\" = '$strDate' AND \"idEmployee\" = '$strID'";
  $resS = $db->execute($strSQL);
  if ($rowS = $db->fetchrow($resS)) {
    //$strResult .= "[".substr($rowS['attendanceStart'],0,5) ."] until [". substr($rowS['attendanceFinish'],0,5)."]";
    //$strResult .= "|||";
    $strResult .= "|||" . substr($rowS['attendanceStart'], 0, 5);
    $strResult .= "|||" . substr($rowS['attendanceFinish'], 0, 5);
  } else {
    $strResult .= "||||||";
  }
  unset($db);
  return $strResult;
}

function getAnnualLeaveQuota($strEmployeeID)
{
  // include_once("../global/employee_function.php");
  include_once("cls_annual_leave.php");
  global $db;
  $intRows = 0;
  $strResult = "";
  $strResult .= "<span style=\"font-size: 11pt; font-weight: bold\">Annual Leave Quota</span>";
  $strResult .= "<table class=\"gridTable\" border=0 cellpadding=1 cellspacing=0>\n";
  $strResult .= "<tr>\n";
  $strResult .= "  <th colspan=6>" . getWords("previous year") . "</th>\n";
  $strResult .= "  <th colspan=6>" . getWords("current year") . "</th>\n";
  $strResult .= "  <th rowspan=2>" . getWords("total remaining") . "</th>\n";
  $strResult .= "</tr>\n";
  $strResult .= "<tr>\n";
  $strResult .= "  <th>" . getWords("year") . "</th>\n";
  $strResult .= "  <th>" . getWords("quota") . "</th>\n";
  $strResult .= "  <th>" . getWords("holiday") . "</th>\n";
  $strResult .= "  <th>" . getWords("prev. over") . "</th>\n";
  $strResult .= "  <th>" . getWords("taken") . "</th>\n";
  $strResult .= "  <th>" . getWords("remain") . "</th>\n";
  $strResult .= "  <th>" . getWords("year") . "</th>\n";
  $strResult .= "  <th>" . getWords("quota") . "</th>\n";
  $strResult .= "  <th>" . getWords("holiday") . "</th>\n";
  $strResult .= "  <th>" . getWords("prev. over") . "</th>\n";
  $strResult .= "  <th>" . getWords("taken") . "</th>\n";
  $strResult .= "  <th>" . getWords("remain") . "</th>\n";
  $strResult .= "</tr>\n";
  $counter = 0;
  $strDateFrom = "";
  $strDateThru = "";
  $strKriteria = "AND employee_id = '" . $strEmployeeID . "' AND active = 1 AND flag = 0 ";
  $objLeave = new clsAnnualLeaveTakaful($db, $strKriteria);
  $objLeave->generateAnnualLeave();
  $strSQL = "
        SELECT id, employee_id, employee_name, gender, section_code, employee_status, 
          EXTRACT(YEAR FROM AGE(join_date)) AS durasi, 
          EXTRACT(MONTH FROM join_date) AS bulan, 
          EXTRACT(YEAR FROM join_date) AS tahun, 
          join_date, resign_date
        FROM hrd_employee 
        WHERE 1=1 $strKriteria
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strGender = ($rowDb['gender'] == FEMALE) ? "F" : "M";
    $strInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
    //$arrCuti = getEmployeeLeaveQuota($db, $rowDb['id']);
    $arrCuti = $objLeave->getEmployeeLeaveInfo($rowDb['id']);
    // cek nilai sisa cuti
    $strPrevYear = $arrCuti['prev']['year'];
    $strPrevPeriod = $arrCuti['prev']['start'] . " | " . $arrCuti['prev']['finish'];
    $intLeaveQuotaPrev = $arrCuti['prev']['quota'];
    $intLeaveHolidayPrev = $arrCuti['prev']['holiday'];
    $intLeaveTakenPrev = $arrCuti['prev']['taken'];
    $intLeaveRemainPrev = $arrCuti['prev']['remain'];
    $strCurrYear = $arrCuti['curr']['year'];
    $strCurrPeriod = $arrCuti['curr']['start'] . " | " . $arrCuti['prev']['finish'];
    $intLeaveQuotaCurr = $arrCuti['curr']['quota'];
    $intLeaveHolidayCurr = $arrCuti['curr']['holiday'];
    $intLeaveTakenCurr = $arrCuti['curr']['taken'];
    $intLeaveRemainCurr = $arrCuti['curr']['remain'];
    $intOverPrev = $arrCuti['curr']['prev_taken'];
    $intOverCurr = $arrCuti['curr']['prev_taken'];
    $strPrevClass = "";
    if ($arrCuti['prev']['overdue']) {
      $strPrevClass = "style=\"background-color:darkred;color:white\" ";
    }
    /*
    else
    {
      $strPrevClass = "";
      $intLeaveRemain += $intLeaveRemainPrev;
    }
    */
    //$intLeaveRemain = $intLeaveRemainCurr;
    $intLeaveRemain = $objLeave->getEmployeeLeaveRemain($rowDb['id']);
    $strClass = "";
    /*
    if ($rowDb['employee_status'] == STATUS_CONTRACT_1)
      $strClass = "class=bgConsidered";
    else  if ($rowDb['employee_status'] == STATUS_CONTRACT_2)
      $strClass = "class=bgConsidered";
    */
    if ($intLeaveQuotaCurr == 0 && $intLeaveQuotaPrev == 0) {
      $strClass = "class=bgDenied";
    }
    $strResult .= "<tr valign=top title=\"$strInfo\" $strClass>\n";
    $strResult .= "  <td align=center>" . $strPrevYear . "</td>";
    $strResult .= "  <td align=right>" . $intLeaveQuotaPrev . "</td>";
    $strResult .= "  <td align=right>" . $intLeaveHolidayPrev . "</td>";
    $strResult .= "  <td align=right>" . $intOverPrev . "</td>";
    $strResult .= "  <td align=right>" . $intLeaveTakenPrev . "</td>";
    $strResult .= "  <td align=right $strPrevClass>" . $intLeaveRemainPrev . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $strCurrYear . "</td>";
    $strResult .= "  <td align=right>" . $intLeaveQuotaCurr . "</td>";
    $strResult .= "  <td align=right>" . $intLeaveHolidayCurr . "&nbsp;</td>";
    $strResult .= "  <td align=right>" . $intOverCurr . "</td>";
    $strResult .= "  <td align=right>" . $intLeaveTakenCurr . "</td>";
    $strResult .= "  <td align=right>" . $intLeaveRemainCurr . "&nbsp;</td>";
    $strResult .= "  <td align=right><strong>" . $intLeaveRemain . "</strong>&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  $strResult .= "</table>\n";
  if ($intRows == 0) {
    $strResult = "ERROR: Cannot find employee ID: " . $strEmployeeID . " in the database!";
  }
  return $strResult;
}

function getAbsenceDuration($strEmployeeID, $strStartDate, $strFinishDate)
{
  include_once('function_employee.php');
  include_once('../global/cls_date.php');
  global $db;
  $strIDEmployee = getIDEmployee($db, $strEmployeeID);
  if ($strIDEmployee == "") {
    $strDataDuration = "ERROR: Cannot find employee ID: " . $strEmployeeID . " in the database!";
  } else if (validStandardDate($strStartDate) == false) {
    $strDataDuration = "ERROR: Invalid Date: " . $strStartDate . " !";
  } else if (validStandardDate($strFinishDate) == false) {
    $strDataDuration = "ERROR: Invalid Date: " . $strFinishDate . " !";
  } else if (dateCompare($strStartDate, $strFinishDate) == 1) {
    $strDataDuration = 0;
  } else {
    $strDataDuration = totalWorkDayEmployee($db, $strIDEmployee, $strStartDate, $strFinishDate);
  } // common_functions.php
  //$strDataDuration = totalWorkDay($db, $strStartDate, $strFinishDate);
  return $strDataDuration;
}

function getLeaveTolerance($strAbsenceCode)
{
  include_once("../includes/model/model.php");
  $tbl = new cModel("hrd_absence_type");
  if ($arrData = $tbl->findByCode($strAbsenceCode)) {
    $strLeaveDeduct = ($arrData['deduct_leave'] == 't' || $arrData['is_leave'] == 't') ? 't' : 'f';
    return (float)($arrData['leave_tolerance']) . "|" . $arrData['unlimited_free_day'] . "|" . $strLeaveDeduct;//$arrData['deduct_leave'];
  }
  return "0|f|t";
}

function getAttendanceInfo($strEmployeeID, $strStartDate)
{
  $tblAttendance = new cModel("hrd_attendance");
  if ($arrAttendanceData = $tblAttendance->find(
      "attendance_date = '" . $strStartDate . "' AND
                                id_employee IN (SELECT id FROM hrd_employee WHERE employee_id = '" . $strEmployeeID . "')",
      null,
      null,
      null,
      null,
      "id_employee"
  )
  ) {
    return $arrAttendanceData['attendance_start'] . "|" . $arrAttendanceData['attendance_finish'];
  }
  return "|";
}

?>