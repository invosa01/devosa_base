<?php
include_once('../global/session.php');
include_once('../global/employee_function.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
include_once('../classes/hrd/hrd_company.php');
include_once('../classes/hrd/hrd_absence_partial.php');
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
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strWordsATTENDANCEDATA = getWords("attendance data");
$strWordsEntryAttendance = getWords("entry attendance");
$strWordsImportAttendance = getWords("import attendance");
$strWordsAttendanceList = getWords("attendance list");
$strWordsAttendanceReport = getWords("attendance report");
$strWordsDateFrom = getWords("date from");
$strWordsDateThru = getWords("date thru");
$strWordsEmployeeID = getWords("employee id");
$strWordsShowData = getWords("show");
$strWordsPrint = getWords("print");
$strWordsExcel = getWords("export excel");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSection = getWords("section");
$strWordsEmployeeStatus = getWords("employee status");
$strWordsATTENDANCEREPORT = getWords("attendance report");
$strWordGetAttendanceSlip = getWords("get attendance slip");
$strWordGetAttendanceSlip2 = getWords("get attendance slip (employee)");
$strWordsIncludeManagement = getWords("show management");
$strWordsIncludeDivision = getWords("show division");
$strWordsIncludeDepartment = getWords("show department");
$strWordsIncludeSection = getWords("show section");
$strWordsIncludeEmployee = getWords("show employee");
$strWordsCompany = getWords("company");
$strReportName = getWords("attendance report");
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strStyle = "";
$intRow = 0;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData(
    $db,
    $strDataDateFrom,
    $strDataDateThru,
    $strDataEmployee,
    &$intRows,
    $strKriteria = "",
    $strOrder = ""
)
{
    global $words;
    global $ARRAY_LEAVE_TYPE;
    global $chkEmp;
    $intRows = 0;
    $strResult = "";
    $arrResult = [];
    // ambil dulu jenis absen
    $arrAbsType = [];
    $strSQL = "SELECT * FROM hrd_absence_type ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrAbsType[$rowDb['code']] = $rowDb['note'];
    }
    // uddin untuk membalik tanggal menjadi format asli
    /*$arrDate = explode("-", $strDataDateFrom);
    $strDataDateFrom = $arrDate[2] . "-" . $arrDate[1] . "-" . $arrDate[0];*/
    //die($_SESSION['sessionDateSetting']['date_sparator']);
    /*$strDataDateFrom = standardDateToSQLDateNew(
        $strDataDateFrom,
        $_SESSION['sessionDateSetting']['date_sparator'],
        $_SESSION['sessionDateSetting']['pos_year'],
        $_SESSION['sessionDateSetting']['pos_month'],
        $_SESSION['sessionDateSetting']['pos_day']
    );*/
    /* $strDataDateThru = standardDateToSQLDateNew(
         $strDataDateThru,
         $_SESSION['sessionDateSetting']['date_sparator'],
         $_SESSION['sessionDateSetting']['pos_year'],
         $_SESSION['sessionDateSetting']['pos_month'],
         $_SESSION['sessionDateSetting']['pos_day']
     );*/
    /*$arrDate = explode("-", $strDataDateThru);
    $strDataDateThru = $arrDate[2] . "-" . $arrDate[1] . "-" . $arrDate[0];*/
    // ambil data informasi kehadiran dan absen
    $arrEmpAtt = getEmployeeAttendance($db, $strDataDateFrom, $strDataDateThru);
    //$arrEmpAttRecap = getEmployeeAttendanceRecap($db,$strDataDateFrom,$strDataDateThru); // yang recap
    $arrEmpAbs = getEmployeeAbsence($db, $strDataDateFrom, $strDataDateThru);
    //$arrEmpLv   = getEmployeeLeave($db,$strDataDateFrom,$strDataDateThru);
    //$arrEmpTrip  = getEmployeeTrip($db,$strDataDateFrom,$strDataDateThru);
    $arrEmpTrn = getEmployeeTraining($db, $strDataDateFrom, $strDataDateThru);
    //get approved late or early
    $tblAbsPart = new cHrdAbsencePartial();
    $strCriteria = "partial_absence_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' AND status >= " . REQUEST_STATUS_APPROVED . " ";
    if ($strDataEmployee != "") {
        $strCriteria .= "AND id_employee = '" . getIDEmployee($db, $strDataEmployee) . "' ";
    }
    $dataAbsPart = $tblAbsPart->findAll($strCriteria, "", "", null, 1, "id");
    foreach ($dataAbsPart as $strID => $detailAbsPart) {
        if ($detailAbsPart['partial_absence_type'] == PARTIAL_ABSENCE_LATE) {
            if (!isset($arrAbsPart[$detailAbsPart['id_employee']]['approved_late'])) {
                $arrAbsPart[$detailAbsPart['id_employee']]['approved_late'] = $detailAbsPart['approved_duration'];
                $arrAbsPart[$detailAbsPart['id_employee']]['approved_late_day'] = 0;
            } else {
                $arrAbsPart[$detailAbsPart['id_employee']]['approved_late'] += $detailAbsPart['approved_duration'];
            }
            if ($detailAbsPart['approved_duration'] >= $detailAbsPart['duration'] && $detailAbsPart['approved_duration'] > 0) {
                $arrAbsPart[$detailAbsPart['id_employee']]['approved_late_day'] += 1;
            }
        } elseif ($detailAbsPart['partial_absence_type'] == PARTIAL_ABSENCE_EARLY) {
            if (!isset($arrAbsPart[$detailAbsPart['id_employee']]['approved_early'])) {
                $arrAbsPart[$detailAbsPart['id_employee']]['approved_early'] = $detailAbsPart['approved_duration'];
                $arrAbsPart[$detailAbsPart['id_employee']]['approved_early_day'] = 0;
            } else {
                $arrAbsPart[$detailAbsPart['id_employee']]['approved_early'] += $detailAbsPart['approved_duration'];
            }
            if ($detailAbsPart['approved_duration'] >= $detailAbsPart['duration'] && $detailAbsPart['approved_duration'] > 0) {
                $arrAbsPart[$detailAbsPart['id_employee']]['approved_early_day'] += 1;
            }
        }
    }
    //get absence which cancels late/early
    $strSQL = "SELECT t1.id_employee, SUM(late_duration) as cancel_late_duration, SUM(early_duration) as cancel_early_duration, ";
    $strSQL .= "SUM(CASE WHEN late_duration > 0 THEN 1 ELSE 0 END) AS cancel_late_day, ";
    $strSQL .= "SUM(CASE WHEN early_duration > 0 THEN 1 ELSE 0 END) AS cancel_early_day ";
    $strSQL .= "FROM hrd_absence_detail AS t1 ";
    $strSQL .= "LEFT JOIN hrd_absence_type AS t2 ON t1.absence_type = t2.code ";
    $strSQL .= "LEFT JOIN hrd_absence AS t3 ON t1.id_absence = t3.id ";
    $strSQL .= "LEFT JOIN hrd_attendance AS t4 ON t1.id_employee = t4.id_employee AND t1.absence_date = t4.attendance_date ";
    $strSQL .= "WHERE absence_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' AND cancel_partial_absence = TRUE AND t3.status >= " . REQUEST_STATUS_APPROVED . " GROUP BY t1.id_employee";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrCancelLate[$rowDb['id_employee']] = $rowDb;
    }
    if ($chkEmp != "") {
        // ambil dulu data employee
        $arrEmployee = [];
        $i = 0;
        $strSQL = "SELECT * FROM hrd_employee ";
        $strSQL .= "WHERE active=1  $strKriteria ORDER BY $strOrder employee_id ";
        // echo $strSQL;
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $intRows++;
            $strID = $rowDb['id'];
            $intAttendance = (isset($arrEmpAtt[$strID]['total'])) ? $arrEmpAtt[$strID]['total'] : 0;
            $intHoliday = (isset($arrEmpAtt[$strID]['totalHoliday'])) ? $arrEmpAtt[$strID]['totalHoliday'] : 0;
            $intLate = (isset($arrEmpAtt[$strID]['late'])) ? $arrEmpAtt[$strID]['late'] : 0;
            $intEarly = (isset($arrEmpAtt[$strID]['early'])) ? $arrEmpAtt[$strID]['early'] : 0;
            $intLateDuration = (isset($arrEmpAtt[$strID]['totalLate'])) ? $arrEmpAtt[$strID]['totalLate'] : 0;
            $intEarlyDuration = (isset($arrEmpAtt[$strID]['totalEarly'])) ? $arrEmpAtt[$strID]['totalEarly'] : 0;
            //kurangi dengan late atau early yang sudah diapprove
            $intLate = (isset($arrAbsPart[$strID]['approved_late_day'])) ? $intLate - $arrAbsPart[$strID]['approved_late_day'] : $intLate;
            $intEarly = (isset($arrAbsPart[$strID]['approved_early_day'])) ? $intEarly - $arrAbsPart[$strID]['approved_early_day'] : $intEarly;
            $intLateDuration = (isset($arrAbsPart[$strID]['approved_late'])) ? $intLateDuration - $arrAbsPart[$strID]['approved_late'] : $intLateDuration;
            $intEarlyDuration = (isset($arrAbsPart[$strID]['approved_early'])) ? $intEarlyDuration - $arrAbsPart[$strID]['approved_early'] : $intEarlyDuration;
            //kurangi dengan cuti yang punya sifat meng-cancel late dan early (contohnya cuti setengah hari)
            $intLate = (isset($arrCancelLate[$strID])) ? $intLate - $arrCancelLate[$strID]['cancel_late_day'] : $intLate;
            $intEarly = (isset($arrCancelLate[$strID])) ? $intEarly - $arrCancelLate[$strID]['cancel_early_day'] : $intEarly;
            $intLateDuration = (isset($arrCancelLate[$strID])) ? $intLateDuration - $arrCancelLate[$strID]['cancel_late_duration'] : $intLateDuration;
            $intEarlyDuration = (isset($arrCancelLate[$strID])) ? $intEarlyDuration - $arrCancelLate[$strID]['cancel_early_duration'] : $intEarlyDuration;
            //$intAbsLv      = (isset($arrEmpAbs[$strID][''])) ? $arrEmpAbs[$strID][''] : 0;// absen potong cuti (ALPHA)
            //$intSpecialAbs = (isset($arrEmpAbs[$strID][SPECIAL_ABSENCE_CODE])) ? $arrEmpAbs[$strID][SPECIAL_ABSENCE_CODE] : 0; // absen khusus
            //$intTrip = (isset($arrEmpTrip[$strID])) ? $arrEmpTrip[$strID]['total'] : 0;
            $intTraining = (isset($arrEmpTrn[$strID])) ? $arrEmpTrn[$strID]['total'] : 0;
            $intAbs = 0;
            $intLv = 0; // total
            foreach ($arrAbsType AS $kode => $nama) {
                $intTmp = (isset($arrEmpAbs[$strID][$kode])) ? $arrEmpAbs[$strID][$kode] : 0;
                $intAbs += $intTmp;
                if ($intTmp == 0) {
                    $intTmp = "";
                }
                $arrResult[$intRows]['absence_' . $kode] = $intTmp;
            }
            /*foreach ($ARRAY_LEAVE_TYPE AS $kode => $nama) {
              $intTmp = (isset($arrEmpLv[$strID][$kode])) ? $arrEmpLv[$strID][$kode] : 0;
              $intLv += $intTmp;
              if ($intTmp == 0) $intTmp = "";
              $arrResult[$intRows]['leave_'.$kode] = $intTmp;
            }*/
            // jika nilainya 0, kosongkan
            if ($intLate == 0) {
                $intLate = "";
            }
            if ($intEarly == 0) {
                $intEarly = "";
            }
            if ($intLateDuration == 0) {
                $intLateDuration = "";
            }
            if ($intEarlyDuration == 0) {
                $intEarlyDuration = "";
            }
            if ($intAbs == 0) {
                $intAbs = "";
            }
            //if ($intAbsLv == 0) $intAbsLv = "";
            //       if ($intLeave == 0) $intLeave = "";
            if ($intLv == 0) {
                $intLv = "";
            }
            //if ($intSpecialAbs == 0) $intSpecialAbs = "";
            //if ($intTrip == 0) $intTrip = "";
            if ($intTraining == 0) {
                $intTraining = "";
            }
            $arrResult[$intRows]['id'] = $rowDb['id'];
            $arrResult[$intRows]['employee_id'] = $rowDb['employee_id'];
            $arrResult[$intRows]['employee_name'] = $rowDb['employee_name'];
            $arrResult[$intRows]['id_company'] = $rowDb['id_company'];
            $arrResult[$intRows]['management_code'] = $rowDb['management_code'];
            $arrResult[$intRows]['division_code'] = $rowDb['division_code'];
            $arrResult[$intRows]['department_code'] = $rowDb['department_code'];
            $arrResult[$intRows]['section_code'] = $rowDb['section_code'];
            $arrResult[$intRows]['position_code'] = $rowDb['position_code'];
            $arrResult[$intRows]['employee_status'] = $rowDb['employee_status'];
            $arrResult[$intRows]['attendance'] = $intAttendance;
            $arrResult[$intRows]['holiday'] = $intHoliday;
            $arrResult[$intRows]['late'] = $intLate;
            $arrResult[$intRows]['totalLate'] = $intLateDuration;
            $arrResult[$intRows]['early'] = $intEarly;
            $arrResult[$intRows]['totalEarly'] = $intEarlyDuration;
            //$arrResult[$intRows]['absenceleave'] = $intAbsLv;
            $arrResult[$intRows]['absence'] = $intAbs;
            //$arrResult[$intRows]['special'] = $intSpecialAbs;
            //$arrResult[$intRows]['leave'] = $intLv;
            //$arrResult[$intRows]['trip'] = $intTrip;
            $arrResult[$intRows]['training'] = $intTraining;
        }
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    return $arrResult;
} // showData
// fungsi buat nampilih header dari table hasil
function getAbsenceType($db)
{
    global $arrAbsType;
    // ambil dulu jenis absen
    $arrAbsType = [];
    $strSQL = "SELECT * FROM hrd_absence_type ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrAbsType[$rowDb['code']] = $rowDb['note'];
    }
} //getAbsenceType
// fungsi buat nampilih header dari table hasil
function showHeader()
{
    //global $ARRAY_LEAVE_TYPE;
    global $arrAbsType;
    global $bolPrint;
    $strResult = "";
    if ($bolPrint) {
        $strResult .= "<table align='left'>";
        $strResult .= "  <tr><td><h2>Legend</h2></td></tr> ";
        foreach ($arrAbsType AS $kode => $nama) {
            $strResult .= "  <tr><td nowrap >&nbsp;<font size=0.2 pt>" . strtoupper(
                    $kode
                ) . "</font></td><td nowrap rowspan=2 >&nbsp;<font size=0.2 pt>" . strtoupper(
                    $nama
                ) . "</font></td><tr>\n";
        }
        $strResult .= "  <tr><td>&nbsp;</td></tr> ";
        $strResult .= "</table>";
    }
    // bikin header table
    $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=\"table table-striped table-hover gridTable\">\n";
    // bikin header table
    $strDefaultWidth = "width=40";
    $strDefaultWidth = "width=40";
    $intNumAbsType = count($arrAbsType);
    //$intNumLvType = count($ARRAY_LEAVE_TYPE);
    $strResult .= " <tr align=center class=tableHeader>\n";
    $strResult .= "  <td nowrap rowspan=2 align=right>&nbsp;</td>";
    $strResult .= "  <td class=\"center\" nowrap rowspan=2>&nbsp;" . strtoupper(getWords("no")) . "</td>\n";
    $strResult .= "  <td class=\"center\" nowrap rowspan=2>&nbsp;" . strtoupper(getWords("employee id")) . "</td>\n";
    $strResult .= "  <td class=\"center\" nowrap rowspan=2>&nbsp;" . strtoupper(getWords("employee name")) . "</td>\n";
    $strResult .= "  <td class=\"center\" nowrap rowspan=2 $strDefaultWidth>&nbsp;" . strtoupper(
            getWords("attendance")
        ) . "</td>\n";
    $strResult .= "  <td class=\"center\" nowrap rowspan=2 $strDefaultWidth>&nbsp;" . strtoupper(
            getWords("late")
        ) . "</td>\n";
    $strResult .= "  <td class=\"center\" nowrap rowspan=2 $strDefaultWidth>&nbsp;" . strtoupper(
            getWords("late (min)")
        ) . "</td>\n";
    $strResult .= "  <td class=\"center\" nowrap rowspan=2 $strDefaultWidth>&nbsp;" . strtoupper(
            getWords("early")
        ) . "</td>\n";
    $strResult .= "  <td class=\"center\" nowrap rowspan=2 $strDefaultWidth>&nbsp;" . strtoupper(
            getWords("early (min)")
        ) . "</td>\n";
    $strResult .= "  <td class=\"center\" nowrap rowspan=2 $strDefaultWidth>&nbsp;" . strtoupper(
            getWords("holiday")
        ) . "</td>\n";
    $strResult .= "  <td class=\"center\" nowrap rowspan=2 $strDefaultWidth>&nbsp;" . strtoupper(
            getWords("shift allowance")
        ) . "</td>\n";
    $strResult .= "  <td class=\"center\" nowrap colspan=" . ($intNumAbsType + 3) . ">&nbsp;" . strtoupper(
            getWords("absence")
        ) . "</td>\n";
    //$strResult .= "  <td nowrap colspan=" .($intNumLvType + 1).">&nbsp;" .strtoupper(getWords("leave"))."</td>\n";
    //$strResult .= "  <td nowrap rowspan=2 $strDefaultWidth>&nbsp;" .strtoupper(getWords("business trip"))."</td>\n";
    //$strResult .= "  <td nowrap rowspan=2 $strDefaultWidth>&nbsp;" .strtoupper(getWords("training"))."</td>\n";
    $strResult .= " </tr>\n";
    $strResult .= " <tr align=center class=tableHeader>\n";
    foreach ($arrAbsType AS $kode => $nama) {
        $strResult .= "  <td class=\"center\" nowrap title=\"$nama\" $strDefaultWidth>&nbsp;" . strtoupper(
                $kode
            ) . "</td>\n";
    }
    //$strResult .= "  <td nowrap $strDefaultWidth>&nbsp;A</td>\n"; // absen lain-lain
    //$strResult .= "  <td nowrap $strDefaultWidth title=\"special absence\">&nbsp;K</td>\n"; // ijin khusus
    $strResult .= "  <td class=\"center\" nowrap $strDefaultWidth>&nbsp;" . strtoupper(getWords("total")) . "</td>\n";
    /*foreach($ARRAY_LEAVE_TYPE AS $kode => $nama) {
      $strResult .= "  <td nowrap $strDefaultWidth>&nbsp;" .strtoupper(getWords($nama))."</td>\n";
    }*/
    //$strResult .= "  <td nowrap $strDefaultWidth>&nbsp;" .strtoupper(getWords("total"))."</td>\n";
    $strResult .= " </tr>\n";
    return $strResult;
} //showHeader
// fungsi buat nampilin data per baris doank
function showRows($strNo, $rowData, $strClass = "")
{
    //global $ARRAY_LEAVE_TYPE;
    global $arrAbsType;
    global $bolPrint;
    global $intRows;
    $strDateSparator = $_SESSION['sessionDateSetting']['date_sparator'];
    $strDatePosYear = $_SESSION['sessionDateSetting']['pos_year'];
    $strDatePosMonth = $_SESSION['sessionDateSetting']['pos_month'];
    $strDatePosDay = $_SESSION['sessionDateSetting']['pos_day'];
    $strDataDateFrom = standardDateToSQLDateNew(
        $_REQUEST['dataDateFrom'],
        $strDateSparator,
        $strDatePosYear,
        $strDatePosMonth,
        $strDatePosDay
    );
    $strDataDateThru = standardDateToSQLDateNew(
        $_REQUEST['dataDateThru'],
        $strDateSparator,
        $strDatePosYear,
        $strDatePosMonth,
        $strDatePosDay
    );
    $intRows++;
    $strResult = "";
    $strResult .= " <tr valign=top class=\"$strClass\">\n";
    if ($bolPrint || $strClass != "") {
        $strResult .= "  <td nowrap align=right>&nbsp;</td>";
    } else {
        $strResult .= "  <td><input type=checkbox name=chkID$intRows value=\"" . $rowData['id'] . "\" checked></td>";
    }
    global $db;
    $strSQL = "SELECT COUNT(*) AS total from hrd_attendance AS t1
				INNER JOIN (
					SELECT * FROM hrd_shift_type 
				) AS t2 ON t1.code_shift_type=t2.code AND t2.shift_allowance>0
				INNER JOIN (
					SELECT * FROM hrd_employee
				) AS t3 ON t1.id_employee=t3.id
				WHERE attendance_date BETWEEN '" . $strDataDateFrom . "' AND '" . $strDataDateThru . "'";
    if ($rowData['attendance'] < 100) {
        $strSQL .= " AND t3.employee_id='" . $rowData['employee_id'] . "'";
    }
    // echo $strSQL;
    $resDb = $db->execute($strSQL);
    $total = $db->fetchrow($resDb);
    $strResult .= "  <td nowrap align=right>$strNo&nbsp;</td>\n";
    $strResult .= "  <td nowrap >&nbsp;" . $rowData['employee_id'] . "</td>\n";
    $strResult .= "  <td nowrap >&nbsp;" . $rowData['employee_name'] . "</td>\n";
    $strResult .= "  <td nowrap class=\"center\">&nbsp;" . $rowData['attendance'] . "</td>\n";
    $strResult .= "  <td nowrap class=\"center\">&nbsp;" . $rowData['late'] . "</td>\n";
    $strResult .= "  <td nowrap class=\"center\">&nbsp;" . minuteToTime($rowData['totalLate']) . " ( " . $rowData['totalLate'] . " ) </td>\n";
    $strResult .= "  <td nowrap class=\"center\">&nbsp;" . $rowData['early'] . "</td>\n";
    $strResult .= "  <td nowrap class=\"center\">&nbsp;" . minuteToTime($rowData['totalEarly']) . " ( " . $rowData['totalEarly'] . " ) </td>\n";
    $strResult .= "  <td nowrap class=\"center\">&nbsp;" . $rowData['holiday'] . "</td>\n";
    $strResult .= "  <td nowrap class=\"center\">&nbsp;" . $total['total'] . "</td>\n";
    // hitung absen
    foreach ($arrAbsType AS $kode => $nama) {
        $strResult .= "  <td nowrap class=\"center\">&nbsp;" . $rowData['absence_' . $kode] . "</td>\n";
    }
    //$strResult .= "  <td nowrap align=center>&nbsp;" .$rowData['absenceleave']."</td>\n";
    //$strResult .= "  <td nowrap align=center>&nbsp;" .$rowData['special']."</td>\n";
    $strResult .= "  <td nowrap class=\"center\">&nbsp;" . $rowData['absence'] . "</td>\n";
    // hitung cuti
    /*foreach ($ARRAY_LEAVE_TYPE AS $kode => $nama) {
      $strResult .= "  <td nowrap align=center>&nbsp;" .$rowData['leave_'.$kode]."</td>\n";
    }*/
    //$strResult .= "  <td nowrap align=center>&nbsp;" .$rowData['leave']."</td>\n";
    //$strResult .= "  <td nowrap align=center>&nbsp;" .$rowData['trip']."</td>\n";
    //$strResult .= "  <td nowrap align=center>&nbsp;" .$rowData['training']."</td>\n";
    $strResult .= " </tr>\n";
    return $strResult;
} //showRows
// fungsi untuk nampilin data per employee
// input: dbclass, nomor urut, data
function showData($db, $arrData)
{
    global $words;
    //global $ARRAY_LEAVE_TYPE;
    global $arrAbsType;
    $intRows = 0;
    $strResult = "";
    //$strResult .= "<table>";
    //$strResult .= "</table>";
    // $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
    // bikin header table
    $strDefaultWidth = "width=40";
    $intNumAbsType = count($arrAbsType);
    //$intNumLvType = count($ARRAY_LEAVE_TYPE);
    $strResult .= showHeader();
    // print_r($arrData);
    foreach ($arrData AS $x => $rowDb) {
        $intRows++;
        $strResult .= showRows($intRows, $rowDb);
    }
    $strResult .= "</table>\n";
    return $strResult;
} // showData
// menampilkan data, digroup berdasar departemen
function showDataDepartment($db, $arrData)
{
    global $words;
    //global $ARRAY_LEAVE_TYPE;
    global $arrAbsType;
    global $_SESSION;
    global $strDataCompany;
    global $strDataManagement;
    global $strDataDivision;
    global $strDataDepartment;
    global $strDataSection;
    global $strDataEmployee;
    global $chkDept;
    global $chkSect;
    global $chkEmp;
    $bolShowTotalDept = ($chkDept != "");
    $bolShowTotalSect = ($chkSect != "");
    $bolShowEmp = ($chkEmp != "");
    $intRows = 0;
    $strResult = "";
    $strKriteriaMan = "";
    $strKriteriaDiv = "";
    $strKriteriaDept = "";
    $strKriteriaSect = "";
    if ($strDataCompany != "") {
        $strTempKriteria = "AND management_code LIKE '" . getCompanyCode() . "%' ";
        $strKriteriaDiv .= $strTempKriteria;
        $strKriteriaDept .= $strTempKriteria;
        $strKriteriaSect .= $strTempKriteria;
    }
    $bolShowTotal = true;
    // cek jika cuma 1 employee yg dicari
    if ($strDataEmployee != "" && isset($arrData[1])) {
        $strTempKriteriaMan = "AND management_code = '" . $arrData[1]['management_code'] . "' ";
        $strTempKriteriaDiv = "AND division_code = '" . $arrData[1]['division_code'] . "' ";
        $strTempKriteriaDept = "AND department_code = '" . $arrData[1]['department_code'] . "' ";
        $strTempKriteriaSect = "AND section_code = '" . $arrData[1]['section_code'] . "' ";
        $strKriteriaMan .= $strTempKriteriaMan;
        $strKriteriaDiv .= $strTempKriteriaMan . $strTempKriteriaDiv;
        $strKriteriaDept .= $strTempKriteriaMan . $strTempKriteriaDiv . $strTempKriteriaDept;
        $strKriteriaSect .= $strTempKriteriaMan . $strTempKriteriaDiv . $strTempKriteriaDept . $strTempKriteriaSect;
        $bolShowTotal = $bolShowTotalDept = $bolShowTotalSect = false;
    }
    // cari data section
    $arrSect = [];
    if ($strDataSection != "") {
        $strKriteriaSect .= "AND section_code =  '$strDataSection' ";
        $bolShowTotal = $bolShowTotalDept = false;
    }
    $strSQL = "SELECT * FROM hrd_section WHERE 1=1 $strKriteriaSect ";
    $strSQL .= "ORDER BY section_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrSect[$rowDb['management_code']][$rowDb['division_code']][$rowDb['department_code']][$rowDb['section_code']] = $rowDb['section_name'];
        if ($strDataSection != "") {
            $strKriteriaDept = "AND department_code = '" . $rowDb['department_code'] . "' ";
            $strKriteriaDiv = "AND division_code = '" . $rowDb['division_code'] . "' ";
            $strKriteriaMan = "AND management_code = '" . $rowDb['management_code'] . "' ";
        }
    }
    // cari data Department
    if ($strDataDepartment != "") {
        $strKriteriaDept .= "AND department_code = '$strDataDepartment' ";
        $bolShowTotal = false;
    }
    $arrDept = [];
    $strSQL = "SELECT * FROM hrd_department WHERE 1=1 $strKriteriaDept ";
    $strSQL .= "ORDER BY department_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrDept[$rowDb['management_code']][$rowDb['division_code']][$rowDb['department_code']] = $rowDb['department_name'];
        if ($strDataDepartment != "") {
            $strKriteriaDiv = "AND division_code = '" . $rowDb['division_code'] . "' ";
            $strKriteriaMan = "AND management_code = '" . $rowDb['management_code'] . "' ";
        }
    }
    // cari data Division
    if ($strDataDivision != "") {
        $strKriteriaDiv .= "AND division_code = '$strDataDivision' ";
        //$bolShowTotal = false;
    }
    $arrDiv = [];
    $strSQL = "SELECT * FROM hrd_division WHERE 1=1 $strKriteriaDiv ";
    $strSQL .= "ORDER BY division_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrDiv[$rowDb['management_code']][$rowDb['division_code']] = $rowDb['division_name'];
        if ($strKriteriaDiv != "") {
            $strKriteriaMan = "AND management_code = '" . $rowDb['management_code'] . "' ";
        }
    }
    // cari data Management
    if ($strDataManagement != "") {
        $strKriteriaMan .= "AND management_code = '$strDataManagement' ";
        //$bolShowTotal = false;
    }
    $arrMan = [];
    $strSQL = "SELECT * FROM hrd_management WHERE 1=1 $strKriteriaMan ";
    $strSQL .= "ORDER BY management_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrMan[$rowDb['management_code']] = $rowDb['management_name'];
    }
    // tentukan keanggotaan department/section
    $arrManEmployee = []; // daftar anggota sebuah management, tapi gak punya division
    $arrDivEmployee = []; // daftar anggota sebuah division, tapi gak punya department
    $arrDeptEmployee = []; // daftar anggota sebuah departement, tapi gak punya section
    $arrSectEmployee = []; // daftar anggota sebuah section
    foreach ($arrData AS $x => $rowDb) {
        if ($rowDb['management_code'] != "" && $rowDb['division_code'] != "" && $rowDb['department_code'] != "" && $rowDb['section_code'] != "") {
            // masuk ke dalam section
            if (isset($arrSectEmployee[$rowDb['section_code']])) {
                $arrSectEmployee[$rowDb['section_code']][] = $x;
            } else {
                $arrSectEmployee[$rowDb['section_code']][0] = $x;
            }
        } else if ($rowDb['management_code'] != "" && $rowDb['division_code'] != "" && $rowDb['department_code'] != "") {
            // cuma ada departement aja
            // masukkan ke dalam department, tapi gak di section tertentu
            if (isset($arrDeptEmployee[$rowDb['department_code']])) {
                $arrDeptEmployee[$rowDb['department_code']][] = $x;
            } else {
                $arrDeptEmployee[$rowDb['department_code']][0] = $x;
            }
        } else if ($rowDb['management_code'] != "" && $rowDb['division_code'] != "") { // cuma ada division aja
            // masukkan ke dalam division, tapi gak di department tertentu
            if (isset($arrDivEmployee[$rowDb['division_code']])) {
                $arrDivEmployee[$rowDb['division_code']][] = $x;
            } else {
                $arrDivEmployee[$rowDb['division_code']][0] = $x;
            }
        } else if ($rowDb['management_code'] != "") { // cuma ada management aja
            // masukkan ke dalam management, tapi gak di division tertentu
            if (isset($arrManEmployee[$rowDb['management_code']])) {
                $arrManEmployee[$rowDb['management_code']][] = $x;
            } else {
                $arrManEmployee[$rowDb['management_code']][0] = $x;
            }
        }
    }
    // array temporer untuk reset data
    $arrEmptyData = [
        "id" => "",
        "late" => 0,
        "early" => 0,
        "totalLate" => 0,
        "totalEarly" => 0,
        "absence" => 0,
        "absenceleave" => 0,
        "leave" => 0, /*"trip" => 0,*/
        "training" => 0,
        "attendance" => 0,
        "employee_id" => "",
        "employee_name" => "",
        "holiday" => "0",
        "shift" => "0"
    ];
    foreach ($arrAbsType AS $kode => $nama) {
        $arrEmptyData['absence_' . $kode] = 0;
    }
    /*foreach ($ARRAY_LEAVE_TYPE AS $kode => $nama) {
      $arrEmptyData['leave_'.$kode] = 0;
    }*/
    $arrTotal = $arrEmptyData;
    $arrTotal['employee_id'] = "<strong>" . strtoupper(getWords("grand total")) . "</strong>";
    // tampilkan data
    $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
    // bikin header table
    $strDefaultWidth = "width=40";
    $intNumAbsType = count($arrAbsType);
    //$intNumLvType = count($ARRAY_LEAVE_TYPE);
    $strResult .= showHeader();
    $intColspan = 14 + count($arrAbsType);
    foreach ($arrMan AS $strManCode => $strManName) {
        //tidak ada pilihan untuk menampilkan total per divisi
        if (false /*$bolShowTotalMan && ($bolShowEmp || $bolShowTotalSect || $bolShowTotalDept|| $bolShowTotalDiv)*/) {
            $strResult .= " <tr valign=top>\n";
            $strResult .= "  <td nowrap colspan=$intColspan>&nbsp;<strong>[$strManCode] $strManName</strong></td>\n";
            $strResult .= " </tr>\n";
        }
        $arrTotalMan = $arrEmptyData;
        $arrTotalMan['employee_name'] = "<strong>" . strtoupper(getWords("total") . " " . $strManCode) . "</strong>";
        // tampilkan data karyawan anggota management
        $arrTmpEmployeeMan = (isset($arrManEmployee[$strManCode])) ? $arrManEmployee[$strManCode] : [];
        foreach ($arrTmpEmployeeMan AS $x => $strX) {
            $rowDb = $arrData[$strX];
            $arrTotal['attendance'] += $rowDb['attendance'];
            $arrTotal['late'] += $rowDb['late'];
            $arrTotal['totalLate'] += $rowDb['totalLate'];
            $arrTotal['early'] += $rowDb['early'];
            $arrTotal['totalEarly'] += $rowDb['totalEarly'];
            $arrTotal['holiday'] += $rowDb['holiday'];
            $arrTotal['absence'] += $rowDb['absence'];
            //$arrTotal['leave'] += $rowDb['leave'];
            //$arrTotal['special'] += $rowDb['special'];
            // $arrTotal['absenceleave'] += $rowDb['absenceleave'];
            $arrTotalMan['late'] += $rowDb['late'];
            $arrTotalMan['totalLate'] += $rowDb['totalLate'];
            $arrTotalMan['attendance'] += $rowDb['attendance'];
            $arrTotalMan['totalEarly'] += $rowDb['totalEarly'];
            $arrTotalMan['holiday'] += $rowDb['holiday'];
            $arrTotalMan['absence'] += $rowDb['absence'];
            //$arrTotalMan['leave'] += $rowDb['leave'];
            //$arrTotalMan['special'] += $rowDb['special'];
            //$arrTotalMan['absenceleave'] += $rowDb['absenceleave'];
            foreach ($arrAbsType AS $kode => $nama) {
                $arrTotalMan['absence_' . $kode] += $rowDb['absence_' . $kode];
                $arrTotal['absence_' . $kode] += $rowDb['absence_' . $kode];
            }
            /*foreach ($ARRAY_LEAVE_TYPE AS $kode => $nama) {
              $arrTotalMan['leave_'.$kode] += $rowDb['leave_'.$kode];
              $arrTotal['leave_'.$kode] += $rowDb['leave_'.$kode];
            }*/
            $strResult .= showRows("", $rowDb);
        }
        $arrTmpDiv = (isset($arrDiv[$strManCode])) ? $arrDiv[$strManCode] : [];
        foreach ($arrTmpDiv AS $strDivCode => $strDivName) {
            if (false /*$bolShowTotalDiv && ($bolShowEmp || $bolShowTotalSect || $bolShowTotalDept)*/) {
                $strResult .= " <tr valign=top>\n";
                $strResult .= "  <td class=\"left\" nowrap colspan=$intColspan>&nbsp;<strong>[$strDivCode] $strDivName</strong></td>\n";
                $strResult .= " </tr>\n";
            }
            $arrTotalDiv = $arrEmptyData;
            $arrTotalDiv['employee_name'] = "<strong>" . strtoupper(
                    getWords("total") . " " . $strDivCode
                ) . "</strong>";
            // tampilkan data karyawan anggota division
            $arrTmpEmployeeDiv = (isset($arrDivEmployee[$strDivCode])) ? $arrDivEmployee[$strDivCode] : [];
            foreach ($arrTmpEmployeeDiv AS $x => $strX) {
                $rowDb = $arrData[$strX];
                $arrTotal['attendance'] += $rowDb['attendance'];
                $arrTotal['late'] += $rowDb['late'];
                $arrTotal['totalLate'] += $rowDb['totalLate'];
                $arrTotal['early'] += $rowDb['early'];
                $arrTotal['totalEarly'] += $rowDb['totalEarly'];
                $arrTotal['holiday'] += $rowDb['holiday'];
                $arrTotal['absence'] += $rowDb['absence'];
                //$arrTotal['leave'] += $rowDb['leave'];
                //$arrTotal['special'] += $rowDb['special'];
                // $arrTotal['absenceleave'] += $rowDb['absenceleave'];
                $arrTotalDiv['late'] += $rowDb['late'];
                $arrTotalDiv['totalLate'] += $rowDb['totalLate'];
                $arrTotalDiv['attendance'] += $rowDb['attendance'];
                $arrTotalDiv['totalEarly'] += $rowDb['totalEarly'];
                $arrTotalDiv['holiday'] += $rowDb['holiday'];
                $arrTotalDiv['absence'] += $rowDb['absence'];
                //$arrTotalDiv['leave'] += $rowDb['leave'];
                //$arrTotalDiv['special'] += $rowDb['special'];
                //$arrTotalDiv['absenceleave'] += $rowDb['absenceleave'];
                foreach ($arrAbsType AS $kode => $nama) {
                    $arrTotalDiv['absence_' . $kode] += $rowDb['absence_' . $kode];
                    $arrTotal['absence_' . $kode] += $rowDb['absence_' . $kode];
                }
                /*foreach ($ARRAY_LEAVE_TYPE AS $kode => $nama) {
                  $arrTotalDiv['leave_'.$kode] += $rowDb['leave_'.$kode];
                  $arrTotal['leave_'.$kode] += $rowDb['leave_'.$kode];
                }*/
                $strResult .= showRows("", $rowDb);
            }
            $arrTmpDept = (isset($arrDept[$strManCode][$strDivCode])) ? $arrDept[$strManCode][$strDivCode] : [];
            foreach ($arrTmpDept AS $strDeptCode => $strDeptName) {
                if ($bolShowTotalDept && ($bolShowEmp || $bolShowTotalSect)) {
                    $strResult .= " <tr valign=top>\n";
                    $strResult .= "  <td class=\"left\" nowrap colspan=$intColspan>&nbsp;<strong>[$strDeptCode] $strDeptName</strong></td>\n";
                    $strResult .= " </tr>\n";
                }
                $arrTotalDept = $arrEmptyData;
                $arrTotalDept['employee_name'] = "<strong>" . strtoupper(
                        getWords("total") . " " . $strDeptCode
                    ) . "</strong>";
                // tampilkan data karyawan anggota departemen
                $arrTmpEmployeeDept = (isset($arrDeptEmployee[$strDeptCode])) ? $arrDeptEmployee[$strDeptCode] : [];
                foreach ($arrTmpEmployeeDept AS $x => $strX) {
                    $rowDb = $arrData[$strX];
                    $arrTotal['attendance'] += $rowDb['attendance'];
                    $arrTotal['late'] += $rowDb['late'];
                    $arrTotal['totalLate'] += $rowDb['totalLate'];
                    $arrTotal['early'] += $rowDb['early'];
                    $arrTotal['totalEarly'] += $rowDb['totalEarly'];
                    $arrTotal['holiday'] += $rowDb['holiday'];
                    $arrTotal['absence'] += $rowDb['absence'];
                    //$arrTotal['leave'] += $rowDb['leave'];
                    //$arrTotal['special'] += $rowDb['special'];
                    // $arrTotal['absenceleave'] += $rowDb['absenceleave'];
                    $arrTotalDept['late'] += $rowDb['late'];
                    $arrTotalDept['totalLate'] += $rowDb['totalLate'];
                    $arrTotalDept['attendance'] += $rowDb['attendance'];
                    $arrTotalDept['totalEarly'] += $rowDb['totalEarly'];
                    $arrTotalDept['holiday'] += $rowDb['holiday'];
                    $arrTotalDept['absence'] += $rowDb['absence'];
                    //$arrTotalDept['leave'] += $rowDb['leave'];
                    //$arrTotalDept['special'] += $rowDb['special'];
                    //$arrTotalDept['absenceleave'] += $rowDb['absenceleave'];
                    foreach ($arrAbsType AS $kode => $nama) {
                        $arrTotalDept['absence_' . $kode] += $rowDb['absence_' . $kode];
                        $arrTotal['absence_' . $kode] += $rowDb['absence_' . $kode];
                    }
                    /*foreach ($ARRAY_LEAVE_TYPE AS $kode => $nama) {
                      $arrTotalDept['leave_'.$kode] += $rowDb['leave_'.$kode];
                      $arrTotal['leave_'.$kode] += $rowDb['leave_'.$kode];
                    }*/
                    $strResult .= showRows("", $rowDb);
                }
                $arrTmpSect = (isset($arrSect[$strManCode][$strDivCode][$strDeptCode])) ? $arrSect[$strManCode][$strDivCode][$strDeptCode] : [];
                foreach ($arrTmpSect AS $strSectCode => $strSectName) {
                    if ($bolShowTotalSect && $bolShowEmp) {
                        $strResult .= " <tr valign=top>\n";
                        $strResult .= "  <td class=\"left\" nowrap colspan=$intColspan>&nbsp;<strong>[$strSectCode] $strSectName</strong></td>\n";
                        $strResult .= " </tr>\n";
                    }
                    $arrTotalSect = $arrEmptyData;
                    $arrTotalSect['employee_name'] = "<strong>" . strtoupper(
                            getWords("total") . " " . $strSectCode
                        ) . "</strong>";
                    // cari karyawan dalam section ini
                    $arrTmpEmployeeSect = (isset($arrSectEmployee[$strSectCode])) ? $arrSectEmployee[$strSectCode] : [];
                    foreach ($arrTmpEmployeeSect AS $x => $strX) {
                        $rowDb = $arrData[$strX];
                        // hitung total dulu
                        $arrTotal['attendance'] += $rowDb['attendance'];
                        $arrTotal['late'] += $rowDb['late'];
                        $arrTotal['totalLate'] += $rowDb['totalLate'];
                        $arrTotal['early'] += $rowDb['early'];
                        $arrTotal['totalEarly'] += $rowDb['totalEarly'];
                        $arrTotal['holiday'] += $rowDb['holiday'];
                        $arrTotal['absence'] += $rowDb['absence'];
                        //$arrTotal['leave'] += $rowDb['leave'];
                        //$arrTotal['special'] += $rowDb['special'];
                        //$arrTotal['absenceleave'] += $rowDb['absenceleave'];
                        $arrTotalDept['attendance'] += $rowDb['attendance'];
                        $arrTotalDept['late'] += $rowDb['late'];
                        $arrTotalDept['totalLate'] += $rowDb['totalLate'];
                        $arrTotalDept['early'] += $rowDb['early'];
                        $arrTotalDept['totalEarly'] += $rowDb['totalEarly'];
                        $arrTotalDept['holiday'] += $rowDb['holiday'];
                        $arrTotalDept['absence'] += $rowDb['absence'];
                        //$arrTotalDept['leave'] += $rowDb['leave'];
                        //$arrTotalDept['special'] += $rowDb['special'];
                        //$arrTotalDept['absenceleave'] += $rowDb['absenceleave'];
                        $arrTotalSect['attendance'] += $rowDb['attendance'];
                        $arrTotalSect['late'] += $rowDb['late'];
                        $arrTotalSect['totalLate'] += $rowDb['totalLate'];
                        $arrTotalSect['early'] += $rowDb['early'];
                        $arrTotalSect['totalEarly'] += $rowDb['totalEarly'];
                        $arrTotalSect['holiday'] += $rowDb['holiday'];
                        $arrTotalSect['absence'] += $rowDb['absence'];
                        //$arrTotalSect['leave'] += $rowDb['leave'];
                        //$arrTotalSect['special'] += $rowDb['special'];
                        //$arrTotalSect['absenceleave'] += $rowDb['absenceleave'];
                        foreach ($arrAbsType AS $kode => $nama) {
                            $arrTotalDept['absence_' . $kode] += $rowDb['absence_' . $kode];
                            $arrTotalSect['absence_' . $kode] += $rowDb['absence_' . $kode];
                            $arrTotal['absence_' . $kode] += $rowDb['absence_' . $kode];
                        }
                        /*foreach ($ARRAY_LEAVE_TYPE AS $kode => $nama) {
                          $arrTotalDept['leave_'.$kode] += $rowDb['leave_'.$kode];
                          $arrTotalSect['leave_'.$kode] += $rowDb['leave_'.$kode];
                          $arrTotal['leave_'.$kode] += $rowDb['leave_'.$kode];
                        }*/
                        if ($bolShowEmp) {
                            $strResult .= showRows("", $rowDb);
                        }
                    }
                    // tampilkan total per section
                    if ($bolShowTotalSect) {
                        $strResult .= showRows("", $arrTotalSect, "bgNewRevised");
                    }
                }
                if ($bolShowTotalDept) {
                    $strResult .= showRows("", $arrTotalDept, "bgNewRevised");
                }
            }
            if (false /*$bolShowTotalDiv*/) {
                $strResult .= showRows("", $arrTotalDiv, "bgNewRevised");
            }
        }
        if (false /*$bolShowTotalMan*/) {
            $strResult .= showRows("", $arrTotalMan, "bgNewRevised");
        }
    }
    if ($bolShowTotal) {
        $strResult .= showRows("", $arrTotal, "tableHeader");
    }
    $strResult .= "</table>\n";
    return $strResult;
} // showDataDepartment
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    // ------ AMBIL DATA KRITERIA -------------------------
    $chkDept = (isset($_REQUEST['chkDept'])) ? "checked" : "";
    $chkSect = (isset($_REQUEST['chkSect'])) ? "checked" : "";
    $chkEmp = (isset($_REQUEST['chkEmp'])) ? "checked" : "";
    //global $strDataDateFrom;
    //global $strDataDateThru;
    //$strDataDateFrom    = (isset($_SESSION['sessionFilterDateFrom']))   ? $_SESSION['sessionFilterDateFrom']    : date($_SESSION['sessionDateSetting']['php_format']);
    //$strDataDateThru    = (isset($_SESSION['sessionFilterDateThru']))   ? $_SESSION['sessionFilterDateThru']    : date($_SESSION['sessionDateSetting']['php_format']);
    $strDataDateFrom = (isset($_SESSION['sessionFilterDateFrom'])) ? $_SESSION['sessionFilterDateFrom'] : date(
        $_SESSION['sessionDateSetting']['php_format']
    );
    $strDataDateThru = (isset($_SESSION['sessionFilterDateThru'])) ? $_SESSION['sessionFilterDateThru'] : date(
        $_SESSION['sessionDateSetting']['php_format']
    );
    $strDataDivision = (isset($_SESSION['sessionFilterDivision'])) ? $_SESSION['sessionFilterDivision'] : "";
    $strDataDepartment = (isset($_SESSION['sessionFilterDepartment'])) ? $_SESSION['sessionFilterDepartment'] : "";
    $strDataSection = (isset($_SESSION['sessionFilterSection'])) ? $_SESSION['sessionFilterSection'] : "";
    $strDataSubSection = (isset($_SESSION['sessionFilterSubSection'])) ? $_SESSION['sessionFilterSubSection'] : "";
    $strDataEmployee = (isset($_SESSION['sessionFilterEmployee'])) ? $_SESSION['sessionFilterEmployee'] : "";
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
    if (isset($_REQUEST['dataEmployeeStatus'])) {
        $strDataEmployeeStatus = $_REQUEST['dataEmployeeStatus'];
    }
    $_SESSION['sessionFilterDateFrom'] = $strDataDateFrom;
    $_SESSION['sessionFilterDateThru'] = $strDataDateThru;
    $_SESSION['sessionFilterDivision'] = $strDataDivision;
    $_SESSION['sessionFilterDepartment'] = $strDataDepartment;
    $_SESSION['sessionFilterSection'] = $strDataSection;
    $_SESSION['sessionFilterSubSection'] = $strDataSubSection;
    $_SESSION['sessionFilterEmployee'] = $strDataEmployee;
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
    if ($strDataEmployeeStatus != "") {
        $strKriteria .= "AND employee_status = '$strDataEmployeeStatus' ";
    }
    $strKriteria .= $strKriteriaCompany;
    if ($bolCanView) {
        getAbsenceType($db);
        $bolShow = (isset($_REQUEST['btnShow']) || $bolPrint);
        $strDataDateFromStandar = $strDataDateFrom;
        $strDataDateThruStandar = $strDataDateFrom;
        $strDataDateFrom = standardDateToSQLDateNew(
            $strDataDateFrom,
            $_SESSION['sessionDateSetting']['date_sparator'],
            $_SESSION['sessionDateSetting']['pos_year'],
            $_SESSION['sessionDateSetting']['pos_month'],
            $_SESSION['sessionDateSetting']['pos_day']
        );
        $strDataDateThru = standardDateToSQLDateNew(
            $strDataDateThru,
            $_SESSION['sessionDateSetting']['date_sparator'],
            $_SESSION['sessionDateSetting']['pos_year'],
            $_SESSION['sessionDateSetting']['pos_month'],
            $_SESSION['sessionDateSetting']['pos_day']
        );
        if (validStandardDate($strDataDateFrom) && validStandardDate($strDataDateThru) && $bolShow) {
            // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
            $arrDataDetail = getData(
                $db,
                $strDataDateFrom,
                $strDataDateThru,
                $strDataEmployee,
                $intTotalData,
                $strKriteria
            );
            // print_r($arrDataDetail);
            $strDataDetail = showData($db, $arrDataDetail);
            $strDataDetail = showDataDepartment($db, $arrDataDetail);
            $strHidden .= "<input type=hidden name=btnShow value=show>";
            if (isset($_REQUEST['btnExcel'])) {
                // ambil data CSS-nya
                if (file_exists("bw.css")) {
                    $strStyle = "bw.css";
                }
                $strPrintCss = "";
                $strPrintInit = "";
                headeringExcel("attendance.xls");
            }
        } else {
            $strDataDetail = "";
        }
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
    // generate data hidden input dan element form input
    $intDefaultWidthPx = 200;
    $strInputDateFrom = "<input class=\"form-control datepicker\" type=text name=dataDateFrom id=dataDateFrom maxlength=10 value=\"$strDataDateFromStandar\" onChange=\"document.getElementById('dataDateThru').value=this.value\" data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\">";
    $strInputDateThru = "<input class=\"form-control datepicker\" type=text name=dataDateThru id=dataDateThru maxlength=10 value=\"$strDataDateThruStandar\" data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\">";
    $strInputDateFrom2 = "<input type=hidden name=dataDateFrom id=dataDateFrom value=\"$strDataDateFrom\"";
    $strInputDateThru2 = "<input type=hidden name=dataDateThru id=dataDateThru value=\"$strDataDateThru\"";
    $strInputEmployee = "<input class=form-control type=text name=dataEmployee id=dataEmployee maxlength=30 value=\"$strDataEmployee\" $strEmpReadonly>";
    $strInputDivision = getDivisionList(
        $db,
        "dataDivision",
        $strDataDivision,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['division']
    );
    $strInputDepartment = getDepartmentList(
        $db,
        "dataDepartment",
        $strDataDepartment,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['department']
    );
    $strInputSection = getSectionList(
        $db,
        "dataSection",
        $strDataSection,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['section']
    );
    $strInputSubSection = getSubSectionList(
        $db,
        "dataSubSection",
        $strDataSubSection,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['sub_section']
    );
    $strInputCompany = getCompanyList(
        $db,
        "dataCompany",
        $strDataCompany,
        $strEmptyOption2,
        $strKriteria2,
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputEmployeeStatus = getEmployeeStatusList(
        "dataEmployeeStatus",
        $strDataEmployeeStatus,
        $strEmptyOption,
        "style=\"width:$intDefaultWidthPx\""
    );
    $strChkDepartment = "<div class=\"checkbox\"><label><input class=\"checkbox-inline\" type=\"checkbox\" name=\"chkDept\" $chkDept>&nbsp;$strWordsIncludeDepartment</label></div>";
    $strChkSection = "<div class=\"checkbox\"><label><input class=\"checkbox-inline\" type=\"checkbox\" name=\"chkSect\" $chkSect>&nbsp;$strWordsIncludeSection</label></div>";
    $strChkEmployee = "<div class=\"checkbox\"><label><input class=\"checkbox-inline\" type=\"checkbox\" name=\"chkEmp\" $chkEmp>&nbsp;$strWordsIncludeEmployee</label></div>";
    // informasi tanggal kehadiran
    if ($strDataDateFrom == $strDataDateThru) {
        $strInfo .= strtoupper(pgDateFormat($strDataDateFrom, "M/d/Y"));
    } else {
        $strInfo .= strtoupper(pgDateFormat($strDataDateFrom, "M/d/Y"));
        $strInfo .= " &raquo; " . strtoupper(pgDateFormat($strDataDateThru, "M/d/Y"));
    }
    $strInfo2 = "<table class=\"table table-striped table-hover\">";
    $i = 1;
    foreach ($arrAbsType as $strCode => $strValue) {
        if ($i % 2 == 1) {
            $strInfo2 .= "<tr><td>" . $strCode . "</td><td>" . $strValue . "</td>";
        } else {
            $strInfo2 .= "<td>" . $strCode . "</td><td>" . $strValue . "</td></tr>";
        }
        $i++;
    }
    if (!count($arrAbsType) % 2) {
        $strInfo2 .= "<td>&nbsp;</td><td>&nbsp;</td></tr>";
    }
    $strInfo2 .= "</table>";
    $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDataDateFrom\">";
    $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDataDateThru\">";
    $strHidden .= "<input type=hidden name=dataDivision value=\"$strDataDivision\">";
    $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
    $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
    $strHidden .= "<input type=hidden name=dataSubSection value=\"$strDataSubSection\">";
    $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
    $strHidden .= "<input type=hidden name=dataEmployeeStatus value=\"$strDataEmployeeStatus\">";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = getWords('employee attendance report');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = attendanceSubMenu($strWordsAttendanceReport);
if ($bolPrint) {
    $strMainTemplate = getTemplate("report_print.html");
} else {
    $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>