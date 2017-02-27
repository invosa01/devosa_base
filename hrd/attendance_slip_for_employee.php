<?php
include_once('../global/session.php');
include_once('global.php');
//include_once(getTemplate("words.inc"));
header('Pragma: no-cache');
header('Content-Type: application/vnd.ms-word');
header('Content-Disposition: attachment; filename="attendance_slip_for_employee.doc"');
echo "<style>@page Section1 {margin:.5in .5in .5in .5in;}";
echo "body,td,th {font-family: Verdana, Arial, Helvetica, sans-serif;font-size: 10px;}";
echo "\n";
echo "div.Section1 {page:Section1}</style>";
(isset($_REQUEST['dataDateFrom'])) ? $strDateFrom = $_REQUEST['dataDateFrom'] : $strDateFrom = date("Y-m-d");
(isset($_REQUEST['dataDateThru'])) ? $strDateThru = $_REQUEST['dataDateThru'] : $strDateThru = date("Y-m-d");
$intDur = getIntervalDate($strDateFrom, $strDateThru) + 1;
$intHalf = ceil($intDur / 2);
$strStyleTable = "style=\"border-right: 1px solid;border-bottom: 1px solid\"";
$strStyleTd = "style=\"border-left: 1px solid;border-top: 1px solid\"";
$strStyleTh = "style=\"border-left: 1px solid;border-top: 1px solid;font-weight:bold\" align=\"center\"";
$db = new CdbClass;
if ($db->connect()) {
    $intPage = 0;
    $strValueSet = "";
    foreach ($_REQUEST AS $kode => $strValue) {
        if (substr($kode, 0, 5) == 'chkID') {
            $strValueSet .= "'" . $strValue . "', ";
        }
    }
    $strValueSet = substr($strValueSet, 0, -2);
    $strNaN = "<td $strStyleTd>&nbsp;</td>";
    $arrEmp = [];
    $arrAtt = [];
    $arrAbs = [];
    $strSQL = "SELECT t1.id, t1.employee_name, t1.employee_id, t2.department_name ";
    $strSQL .= "FROM hrd_employee AS t1 ";
    $strSQL .= "LEFT JOIN hrd_department AS t2 ON t1.department_code = t2.department_code ";
    $strSQL .= "WHERE t1.id IN ($strValueSet)";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrEmp[$rowDb['id']] = $rowDb;
    }
    $strSQL = "SELECT id_employee, attendance_date, substr(attendance_start,0,6) as time_in, ";
    $strSQL .= "substr(attendance_finish,0,6) as time_out, holiday, ";
    $strSQL .= "late_duration, early_duration, l1, l2, l3, l4, morning_overtime, overtime, code_shift_type ";
    $strSQL .= "FROM hrd_attendance WHERE id_employee IN ($strValueSet)";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrAtt[$rowDb['id_employee']][$rowDb['attendance_date']] = $rowDb;
    }
    $strSQL = "SELECT id_employee, absence_date, absence_type ";
    $strSQL .= "FROM hrd_absence_detail ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrAbs[$rowDb['id_employee']][$rowDb['absence_date']] = $rowDb['absence_type'];
    }
    foreach ($arrEmp as $strIDEmployee => $arrEmployee) {
        $strTempDate = $strDateFrom;
        $intLate = $intEarly = $intOTEarly = $intOTAfternoon = $intOTTotal = 0;
        $strDetail = "";
        $strFrom = pgDateFormat($strDateFrom, "d-m-Y");
        $strThru = pgDateFormat($strDateThru, "d-m-Y");
        $strEmpID = $arrEmployee['employee_id'];
        $strName = $arrEmployee['employee_name'];
        $strDepartment = $arrEmployee['department_name'];
        $strDetail = "";
        $strDetail2 = "";
        $i = 0;
        while (dateCompare($strTempDate, $strDateThru) <= 0) {
            $i++;
            $strColumn = ($i <= $intHalf) ? "strDetail" : "strDetail2";
            $$strColumn .= "<tr>";
            if (isset($arrAtt[$strIDEmployee][$strTempDate])) {
                $arrDetail = $arrAtt[$strIDEmployee][$strTempDate];
                $strShift = ($arrDetail['code_shift_type'] == "") ? "NS" : $arrDetail['code_shift_type'];
                $intLate += $arrDetail['late_duration'];
                $intEarly += $arrDetail['early_duration'];
                $$strColumn .= "<td $strStyleTd nowrap>&nbsp;" . $strTempDate . "</td>";
                $$strColumn .= "<td $strStyleTd>&nbsp;Present</td>";
                $$strColumn .= "<td $strStyleTd>&nbsp;" . $strShift . "</td>";
                $$strColumn .= "<td $strStyleTd>&nbsp;" . $arrDetail['time_in'] . "</td>";
                $$strColumn .= "<td $strStyleTd>&nbsp;" . $arrDetail['time_out'] . "</td>";
                $$strColumn .= "<td $strStyleTd>&nbsp;" . $arrDetail['late_duration'] . "</td>";
                $$strColumn .= "<td $strStyleTd>&nbsp;" . $arrDetail['early_duration'] . "</td>";
            } else {
                $strRemark = (isset($arrAbs[$strIDEmployee][$strTempDate])) ? $arrAbs[$strIDEmployee][$strTempDate] : "";
                $$strColumn .= "<td $strStyleTd>&nbsp;" . $strTempDate . "</td>";
                $$strColumn .= "<td $strStyleTd>&nbsp;" . $strRemark . "</td>";
                $$strColumn .= $strNaN . $strNaN . $strNaN . $strNaN . $strNaN;
            }
            $$strColumn .= "</tr>";
            $strTempDate = getNextDate($strTempDate);
        }
        $strDetail = str_replace("&nbsp;0</td>", "&nbsp;</td>", $strDetail);
        $strDetail2 = str_replace("&nbsp;0</td>", "&nbsp;</td>", $strDetail2);
        $intPage++;
        echo "<p>";
        $tbsPage = new clsTinyButStrong;
        $tbsPage->LoadTemplate(getTemplate("attendance_slip_for_employee.html"));
        $tbsPage->noErr = false;
        $tbsPage->Show(TBS_OUTPUT);
        echo "</p><br>\n";
        if (($intPage % 2) == 0) {
            echo "<span><br clear=all style='mso-special-character:line-break;page-break-before:always'> </span>";
        }
    }
    echo "<body> <div class=Section1>";
    echo "</div></body> ";
}
?>
