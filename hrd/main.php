<?php
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_education_level.php');
include_once('../global/employee_function.php');
include_once('calendar2.php');
//include_once("../includes/krumo/class.krumo.php");
//echo $_SESSION['sessionUserRole'];
/* $email = $_SESSION['sessionEmail'];
echo $email;
echo "<br>";
echo $_SESSION['sessionIdGroup'];
echo "<br>";
echo $_SESSION['sessionUserRole'];
echo "<br>";
echo $_SESSION['sessionGroupRole'];
echo "<br>";
echo $_SESSION['sessionDefaultModuleID']; */
$strPageTitle = getWords("application human resource") . " | DeVosa";
$strWordsAlert = getWords("alert");
$strWordsShortcut = getWords("shortcut");
$strWordsBirthday = getWords("birthday");
$strWordsTripcuti = getWords("employee information");
$strWordsEotm = getWords("employee of the month");
$strWordsNews = getWords("news");
$strWordsPeraturan = getWords("peraturan");
$strWordsEmployeeContract = getWords("employee contract");
$strWordsEmployeeActivity = getWords("employee activity");
$strWordsTimeTable = getWords("time table");
$strWordsEmployeeKRA = getWords("employee KRA");
$strWordsAttendance = getWords("attendance");
$strWordsEmployeeInfo = getWords("employee info");
$strWordsSubEmployeeInfo = getWords("view and edit employee information");
$strWordsSubAttendance = getWords("view attendance information");
$strWordsAbsence = getWords("absence");
$strWordsSubAbsence = getWords("view and input absence proposal");
$strWordsLeave = getWords("leave");
$strWordsSubLeave = getWords("view employee leave quota");
$strWordsSalarySlip = getWords("salary slip");
$strWordsSubSalarySlip = getWords("view employee salary slip");
$strWordsNo = getWords("no.");
$strWordsName = getWords("name");
$strWordsFm = getWords("f/m");
$strWordsBirthday = getWords("birthday");
$strWordsStartDate = getWords("start date");
$strWordsEndDate = getWords("end date");
$strWordsDescription = getWords("description");
$strWordsEmplId = getWords("empl. id");
$strWordsFinishDate = getWords("finish date");
$strWordsTimeInOut = strtoupper(getWords("TIME IN/OUT"));
$strWordsDescription = getWords("description");
$bolCanView = true;
$bolCanEdit = true;
$bolCanDelete = true;
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strDetailNews = "";
$strDetailContract = "";
$strDetailAlert = "";
$strDetailBirthday = "";
$strDetailBirthday = "";
$strNow = date("Y-m-d");
$strEmployeeID = "";
$strEmployeeName = "";
$strEmployeeStatus = "";
$strDepartment = "";
$strPosition = "";
$strLeave = "";
$strMedical = "";
$arrDataEmployee = [];
$strHidden = "";
$strDate = "";
$strNews = "";
$strKriteria = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi mengambil daftar karyawan yang hampir habis masa kontrak
function getDataContract($db)
{
    global $words;
    global $_SESSION;
    global $strDataDivision, $strDataDepartment, $strDataSection, $strDataSubSection;
    global $ARRAY_EMPLOYEE_STATUS;
    global $bolIsEmployee;
    global $arrUserInfo;
    global $strKriteriaCompany, $strKriteria;
    $intRows = 0;
    $strResult = "";
    $employeeId = $arrUserInfo['employee_id'];
    // cari info perubahan karyawan yang belum disetujui, untuk status renewalnya
    $arrMutation = [];
    $strSQL = "SELECT t2.id, t2.id_employee FROM hrd_employee_mutation_status AS t1, hrd_employee_mutation AS t2 ";
    $strSQL .= "WHERE t1.id_mutation = t2.id AND t2.status < " . REQUEST_STATUS_APPROVED;
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrMutation[$rowDb['id_employee']] = $rowDb['id'];
    }
    // kriteria contract yang ditampilkan adalah, yang duedatenya sudah lewat 1 minggu, dan hampmir habis 1 bulan lagi
    $strSQL = "SELECT *, ((due_date) - CURRENT_DATE) AS selisih, ";
    $strSQL .= "CASE WHEN (due_date = CURRENT_DATE) THEN 1 ELSE 0 END AS sekarang ";
    $strSQL .= "FROM hrd_employee ";
    $strSQL .= "WHERE ((due_date > CURRENT_DATE AND (due_date - interval '2 months') < CURRENT_DATE) OR (permanent_date is null AND due_date < CURRENT_DATE)) ";
    //$strSQL .= "WHERE ((due_date BETWEEN date(now() - interval '7 days') AND date(now() + interval '1 months')) ";
    //$strSQL .= "OR due_date > CURRENT_DATE) AND resign_date is null AND permanent_date is null ";
    $strSQL .= "AND employee_status <> " . STATUS_PERMANENT . " AND active = 1 $strKriteriaCompany ";
    if ($strDataDivision != "") {
        $strSQL .= "AND division_code = '$strDataDivision' ";
    }
    if ($strDataDepartment != "") {
        $strSQL .= "AND department_code = '$strDataDepartment' ";
    }
    if ($strDataSection != "") {
        $strSQL .= "AND section_code = '$strDataSection' ";
    }
    if ($strDataSubSection != "") {
        $strSQL .= "AND sub_section_code = '$strDataSubSection' ";
    }
    if (SET_FIlTERING_CONTRACT_EMPLOYEE_INFO === true){
        $strSQL .= "AND employee_id = '$employeeId'";
    }
    $strSQL .= "ORDER BY due_date DESC, employee_name ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strID = $rowDb['id'];
        $strJoinDate = pgDateFormat($rowDb['join_date'], "d-M-y");
        $strDueDate = pgDateFormat($rowDb['due_date'], "d-M-y");
        //$strEmployeeID = ($bolIsEmployee) ? $rowDb['employee_id'] : "<a href='employee_resume.php?dataID=$strID'>" .$rowDb['employee_id']."</a>";
        $strEmployeeID = $rowDb['employee_id'];
        $strName = $rowDb['employee_name'];
        //$strSection = ($rowDb['sub_section_code'] == "") ? $rowDb['section_code'] : $rowDb['sub_section_code'];
        //$strGender = ($rowDb['gender'] == 0) ? "F" : "M";
        $strSelisih = $rowDb['selisih'];
        $strClass = ($rowDb['sekarang'] == 1) ? "class = 'bgLate'" : "";
        if (isset($arrMutation[$rowDb['id']])) {
            $strLinkPar = "dataID=" . $arrMutation[$rowDb['id']];
            $strConfirm = " [&radic;]";
            $strClass = 'class="bgNewRevised"';
        } else {
            $strLinkPar = "dataIDEmployee=" . $rowDb['id'];
            $strConfirm = "";
        }
        $strResult .= "<tr valign=top $strClass>\n";
        $strResult .= "  <td nowrap>$strEmployeeID&nbsp;</td>";
        $strResult .= "  <td nowrap>$strName&nbsp;</td>";
        $strResult .= "  <td align=center>$strJoinDate&nbsp;</td>";
        $strResult .= "  <td align=center>$strDueDate&nbsp;</td>";
        $strResult .= "  <td>$strSelisih&nbsp;</td>";
        $strResult .= "  <td align=center nowrap><a href=\"mutation_edit.php?btnRenew=Renew&$strLinkPar\">" . getWords(
                "renew"
            ) . " $strConfirm</a>&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    $intTotalData = $intRows;
    return $strResult;
} // getDataContract
function getEmployeeKRA($db)
{
    global $words;
    global $_SESSION;
    global $strDataSection;
    global $ARRAY_EMPLOYEE_STATUS;
    global $bolIsEmployee;
    global $arrUserInfo;
    $intRows = 0;
    $strResult = "";
    if ($arrUserInfo['employee_id'] == "") {
        return "";
    }
    $strSQL = "SELECT * FROM hrd_evaluation_criteria_employee ";
    $strSQL .= "WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' and is_last_updated = true";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strID = $rowDb['id'];
        $strKriteria = $rowDb['criteria'];
        $strTargetDate = $rowDb['target_date'];
        $strResult .= "<tr valign=top >\n";
        $strResult .= "  <td align=center>$intRows&nbsp;</td>";
        $strResult .= "  <td align=center>$strKriteria&nbsp;</td>";
        $strResult .= "  <td align=center>$strTargetDate&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    $intTotalData = $intRows;
    return $strResult;
} // getEmployeeKRA
function getDataBirthday($db)
{
    global $words;
    global $_SESSION;
    global $bolIsEmployee;
    global $strKriteriaCompany;
    global $arrUserInfo;
    $intRows = 0;
    $strResult = "";
    $dtNow = getdate();
    $tsNow = mktime(0, 0, 0, $dtNow['mon'], $dtNow['mday'], $dtNow['year']);
    $tsAwal = $tsNow - (86400 * 1);
    $tsAkhir = $tsNow + (86400 * 7);
    $strAwal = date("m-d", $tsAwal);
    $strAkhir = date("m-d", $tsAkhir);
    // kriteria contract yang ditampilkan adalah, yang duedatenya sudah lewat 1 minggu, dan hampmir habis 1 bulan lagi
    $strSQL = "select a.employee_name,  a.employee_ID, gender, to_char(birthday,'DD Mon') as sekarang
from hrd_employee AS a
where a.active = 1
and to_char(current_date,'Mon') = to_char(birthday,'Mon') $strKriteriaCompany ";
    /*
    $strSQL .= "CASE WHEN (SUBSTRING(CAST(birthday as text) FROM 6 FOR 5) = SUBSTRING(CAST(CURRENT_DATE as text ) FROM 6 FOR 5)) THEN 1 ELSE 0 END AS sekarang ";
    $strSQL .= "FROM hrd_employee ";
    $strSQL .= "WHERE SUBSTRING(CAST(birthday as text ) FROM 6 FOR 5) BETWEEN '$strAwal' AND '$strAkhir'  ";
    $strSQL .= "AND active = 1 $strKriteriaCompany ";

    if ($strDataDivision != "") $strSQL .= "AND division_code = '$strDataDivision' ";
    if ($strDataDepartment != "") $strSQL .= "AND department_code = '$strDataDepartment' ";
    if ($strDataSection != "") $strSQL .= "AND section_code = '$strDataSection' ";
    if ($strDataSubSection != "") $strSQL .= "AND sub_section_code = '$strDataSubSection' ";
    */
    $strSQL .= "ORDER BY SUBSTRING(CAST (birthday as text ) FROM 6 FOR 5) ASC, sekarang ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        //$strID = $rowDb['id'];
        $strBirthday = $rowDb['sekarang'];
        //$strEmployeeID = ($bolIsEmployee) ? $rowDb['employee_id'] : "<a href='employee_resume.php?dataID=$strID'>" .$rowDb['employee_id']."</a>";
        $strEmployeeID = $rowDb['employee_id'];
        $strName = $rowDb['employee_name'];
        $strGender = ($rowDb['gender'] == 0) ? "F" : "M";
        //$strUmur = "";//$rowDb['umur'];
        $strClass = ($rowDb['sekarang'] == 1) ? "class = 'bgLate'" : "";
        $strResult .= "<tr valign=top $strClass>\n";
        /*
        if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
          $strResult .= "  <td nowrap>$strEmployeeID&nbsp;</td>";
        } else {
          $strResult .= "  <td nowrap>$strEmployeeID&nbsp;</td>";
        }
        */
        $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>";
        $strResult .= "  <td>$strName&nbsp;</td>";
        $strResult .= "  <td align=center>$strGender&nbsp;</td>";
        $strResult .= "  <td align=center>$strBirthday&nbsp;</td>";
        //$strResult .= "  <td align=center>$strUmur&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    $intTotalData = $intRows;
    return $strResult;
} // getDataBirthday
///////////////////////////////////////////////////////// trip cuti /////////////////////////////////////
function getDataTripcuti($db)
{
    global $words;
    global $_SESSION;
    global $bolIsEmployee;
    global $strKriteriaCompany;
    global $arrUserInfo;
    $intRows = 0;
    $strResult = "";
    $dtNow = getdate();
    $tsNow = mktime(0, 0, 0, $dtNow['mon'], $dtNow['mday'], $dtNow['year']);
    $tsAwal = $tsNow - (86400 * 1);
    $tsAkhir = $tsNow + (86400 * 7);
    $strAwal = date("m-d", $tsAwal);
    $strAkhir = date("m-d", $tsAkhir);
    // kriteria contract yang ditampilkan adalah, yang duedatenya sudah lewat 1 minggu, dan hampmir habis 1 bulan lagi
    /*$strSQL = "select c.employee_name AS Nama, a.date_from AS Tgl_Mulai, a.date_thru AS Tgl_Selesai,
                b.note AS note, 'Cuti' as description
                from hrd_absence AS a, hrd_absence_type AS b, hrd_employee AS c
                where a.status = 2
                and a.absence_type_code = b.code
                and b.deduct_leave = 'TRUE'
                and a.id_employee = c.id
                and current_date between a.date_from and a.date_thru
                union all
                select c.employee_name AS Nama, a.date_from AS Tgl_Mulai, a.date_thru AS Tgl_Selesai,
                a.trip_type as note, 'Business Trip' as description
                from hrd_trip as a, hrd_employee as c
                where a.status = 2
                and a.id_employee = c.id
                and current_date between a.date_from and a.date_thru
                and current_date between a.date_from and a.date_thru
                order by description";*/
    $strSQL = "select c.employee_name AS Nama, a.date_from AS Tgl_Mulai, a.date_thru AS Tgl_Selesai,
                b.note AS note, 'Cuti' as description
                from hrd_absence AS a, hrd_absence_type AS b, hrd_employee AS c
                where a.status in(2,3) $strKriteriaCompany
                and a.absence_type_code = b.code
                and b.deduct_leave = 'TRUE'
                and a.id_employee = c.id
                and current_date between a.date_from and a.date_thru
                union all
                select c.employee_name AS Nama, a.date_from AS Tgl_Mulai, a.date_thru AS Tgl_Selesai,
                a.trip_type as note, 'Business Trip' as description
                from hrd_trip as a, hrd_employee as c
                where a.status in(2,3) $strKriteriaCompany
                and a.id_employee = c.id
      and current_date between a.date_from and a.date_thru
                order by description";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        /* $strID = $rowDb['id'];
        $strBirthday = pgDateFormat($rowDb['birthday'], "d M");
        //$strEmployeeID = ($bolIsEmployee) ? $rowDb['employee_id'] : "<a href='employee_resume.php?dataID=$strID'>" .$rowDb['employee_id']."</a>";
        $strEmployeeID = $rowDb['employee_id'];
        $strName = $rowDb['employee_name'];
        $strGender = ($rowDb['gender'] == 0) ? "F" : "M";
        $strUmur = "";//$rowDb['umur'];
        $strClass = ($rowDb['sekarang'] == 1) ? "class = 'bgLate'" : ""; */
        $strNama = $rowDb['nama'];
        $strTgl_mulai = $rowDb['tgl_mulai'];
        $strTgl_selesai = $rowDb['tgl_selesai'];
        //      $strNote = $rowDb['note'];
        $strDescription = $rowDb['description'];
        $strResult .= "<tr valign=top $strClass>\n";
        /*
        if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
          $strResult .= "  <td nowrap>$strEmployeeID&nbsp;</td>";
        } else {
          $strResult .= "  <td nowrap>$strEmployeeID&nbsp;</td>";
        }
        */
        $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>";
        $strResult .= "  <td>$strNama&nbsp;</td>";
        $strResult .= "  <td align=center>$strTgl_mulai&nbsp;</td>";
        $strResult .= "  <td align=center>$strTgl_selesai&nbsp;</td>";
        // $strResult .= "  <td align=center>$strNote&nbsp;</td>";
        $strResult .= "  <td align=center>$strDescription&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    $intTotalData = $intRows;
    return $strResult;
} // getDatatripcuti
///////////////////////////////////////////////////////// trip cuti /////////////////////////////////////
////////////////////////////// start View Overtime by yuda ////////////////////////////
function getDataTampilovertime($db)
{
    $strResult = "

        <div class=\"col-md-3 col-sm-3 col-xs-6 text-center stat\">
        <span><a href=\"overtime_application_list.php\">Overtime</a></span><br/>
        <em>View and entry overtime proposal and actual.</em>
        </div>
            ";
    return $strResult;
}

////////////////////////////// end View Overtime by yuda ////////////////////////////
////////////////////////////// start EOTM by yuda ////////////////////////////
function getDataEotm($db)
{
    global $words, $strClass;
    global $_SESSION;
    global $bolIsEotm;
    global $strKriteriaCompany;
    global $arrUserInfo;
    $intRows = 0;
    $strResult = "";
    $dtNow = getdate();
    $tsNow = mktime(0, 0, 0, $dtNow['mon'], $dtNow['mday'], $dtNow['year']);
    $bulanSekarang = date("m");
    $tahunSekarang = date("Y");
    /* echo $bulanSekarang;
    echo "<br>";
    echo $tahunSekarang;
    echo "<br>"; */
    if ($bulanSekarang == 01) {
        $blnYgDitampilkan = 12;
        $tahunYgDitampilkan = $tahunSekarang - 1;
    } else {
        $blnYgDitampilkan = $bulanSekarang - 1;
        $tahunYgDitampilkan = $tahunSekarang;
    }
    //echo $blnYgDitampilkan;
    $tsAwal = $tsNow - (86400 * 1);
    $tsAkhir = $tsNow + (86400 * 7);
    $strAwal = date("m-d", $tsAwal);
    $strAkhir = date("m-d", $tsAkhir);
    $strSQL = "SELECT t1.*, employee_id, employee_name, t3.division_name
               FROM hrd_eotm AS t1
               LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id
               LEFT JOIN hrd_division  AS t3 ON t2.division_code = t3.division_code
         WHERE t1.release_month ='" . $blnYgDitampilkan . "' AND t1.release_year='" . $tahunYgDitampilkan . "'
        order by id desc
               ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strName = $rowDb['employee_name'];
        // $strFormCode = $rowDb['form_code'];
        $strDiv = $rowDb['division_name'];
        //$strMonth = $rowDb['dataMonth'];
        $strYear = $rowDb['release_year'];
        //  $strNote = $rowDb['note'];
        $strMonth = getBulan($rowDb['release_month']);
        $strResult .= "<tr valign=top $strClass>\n";
        $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>";
        $strResult .= "  <td>$strName&nbsp;</td>";
        //$strResult .= "  <td align=center>$strFormCode&nbsp;</td>";
        $strResult .= "  <td align=center>$strDiv&nbsp;</td>";
        $strResult .= "  <td align=center>$strMonth&nbsp;</td>";
        $strResult .= "  <td align=center>$strYear&nbsp;</td>";
        //$strResult .= "  <td align=center>$strNote&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    $intTotalData = $intRows;
    return $strResult;
} // getDataEotm
////////////////////////////// end EOTM by yuda ////////////////////////////
//fungsi untuk mengambil data news (by Farhan)
function getDataNews($db)
{
    global $words;
    global $_SESSION;
    global $strKriteriaCompany;
    $intRows = 0;
    $strResult = "<table class=\"table table-hover\">";
    $strSQL = "SELECT t0.*, company_code FROM hrd_news AS t0 LEFT JOIN hrd_company AS t1 ON t0.id_company = t1.id
                   WHERE 1=1 and active is true $strKriteriaCompany ORDER BY date_event";
    $resDb = $db->execute($strSQL);
    $strResult .= "<tr valign=top height=20>\n";
    $strResult .= "  <th nowrap align=center width=\"20px\">" . getWords("no.") . "&nbsp;</td>";
    $strResult .= "  <th align=center>" . getWords("created") . "&nbsp;</td>";
    $strResult .= "  <th align=center>" . getWords("event date") . "&nbsp;</td>";
    $strResult .= "  <th width=\"50px\" align=center>" . getWords("company") . "&nbsp;</td>";
    $strResult .= "  <th align=center width=\"75%\">" . getWords("news") . "&nbsp;</td>";
    $strResult .= "</tr>\n";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= "<tr valign=top height=20>\n";
        $strResult .= "  <td nowrap align=center>$intRows&nbsp;</td>";
        $strResult .= "  <td nowrap align=center>" . pgDateFormat($rowDb['created'], "d M Y") . "&nbsp;</td>";
        $strResult .= "  <td nowrap align=center>" . pgDateFormat($rowDb['date_event'], "d M Y") . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['company_code'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['news'] . "&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    $strResult .= "</table>";
    $intTotalData = $intRows;
    return $strResult;
} // getDataNews
// fungsi mengambil data informasi karyawan, jika ada user
function getEmployeeInfo($db)
{
    global $arrUserInfo;
    global $arrDataEmployee;
    if ($arrUserInfo['employee_id'] != "") {
        $arrDataEmployee['employee_id'] = $arrUserInfo['employee_id'];
        $arrDataEmployee['employee_name'] = $arrUserInfo['employee_name'];
        $strSQL = "SELECT employee_status FROM hrd_employee ";
        $strSQL .= "WHERE employee_id = '" . $arrUserInfo['employee_id'] . "' ";
        $resTmp = $db->execute($strSQL);
        if ($rowTmp = $db->fetchrow($resTmp)) {
            $arrDataEmployee['employee_status'] = $rowTmp['employee_status'];
        }
        /*
              $arrCuti = getEmployeeLeaveQuota($db, $arrUserInfo['id_employee']);
              $intLeaveQuotaPrev = $arrCuti['prevQuota'];
              $intLeaveQuotaCurr = $arrCuti['currQuota'];
              if ($intLeaveQuotaPrev == 0) {
                $intLeaveTakenPrev = 0; // anggap aja gak ada
                $intLeaveHolidayPrev = 0; //
              } else {
                $intLeaveTakenPrev = $arrCuti['prevTaken'];
                $intLeaveHolidayPrev = $arrCuti['prevHoliday'];
              }
              if ($intLeaveQuotaCurr == 0) {
                $intLeaveTakenCurr = 0; // anggap aja gak ada
                $intLeaveHolidayCurr = 0; //
              } else {
                $intLeaveTakenCurr = $arrCuti['currTaken'];
                $intLeaveHolidayCurr = $arrCuti['currHoliday'];
              }
              $intLeaveRemain = $intLeaveQuotaPrev + $intLeaveQuotaCurr;
              $intLeaveRemain -= ($intLeaveTakenCurr + $intLeaveTakenPrev);
              $intLeaveRemain -= ($intLeaveHolidayCurr + $intLeaveHolidayPrev);

              $arrDataEmployee['leaveQuota'] = getEmployeeLeaveRemain($db, $arrUserInfo['id_employee']);
              //$arrMedical = getEmployeeMedicalQuota($db, $arrUserInfo['id_employee']);
              $arrDataEmployee['medicalQuota'] = $arrMedical['outpatient'];*/
    }
}

// fungsi untuk mengambil informasi alert
function getAlert($db)
{
    // Alert ditampilkan berdasarkan privilege dan scope akses data karyawan (getDataPrivileges dan scopeData)
    global $arrUserInfo, $bolIsEmployee;
    global $_SESSION;
    global $strKriteriaCompany;
    global $strKriteria;
    global $strKriteriaOT;
    $strClass = "bgNewRevised";
    $strResult = "<table class=\"table table-hover\">\n";
    // Alert Placement Request. To be checked, to be approved dan to be acknowledged
    $dataPrivilege = getDataPrivileges(
        "mutation_list.php",
        $bolCanView,
        $bolCanEdit,
        $bolCanDelete,
        $bolCanApprove,
        $bolCanCheck,
        $bolCanAcknowledge
    );
    if ($bolCanCheck) {
        $strLink = "javascript:goAlert('mutation_list.php'," . REQUEST_STATUS_NEW . ")";
        $strSQL = "SELECT COUNT(t0.id) AS total FROM hrd_employee_mutation as t0 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
        $strSQL .= "WHERE status = " . REQUEST_STATUS_NEW . " $strKriteria " . " $strKriteriaCompany ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Placement Request - need to be checked: </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    if ($bolCanApprove) {
        // cek yang statusnya akan di Approve
        $strLink = "javascript:goAlert('mutation_list.php'," . REQUEST_STATUS_CHECKED . ")";
        $strSQL = "SELECT COUNT(t0.id) AS total FROM hrd_employee_mutation AS t0 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
        $strSQL .= "WHERE status = " . REQUEST_STATUS_CHECKED . " $strKriteria " . " $strKriteriaCompany ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Placement Request - need to be approved: </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    if ($bolCanAcknowledge) {
        // cek yang statusnya akan di AKnowledge
        $strLink = "javascript:goAlert('mutation_list.php'," . REQUEST_STATUS_APPROVED . ")";
        $strSQL = "SELECT COUNT(t0.id) AS total FROM hrd_employee_mutation AS t0 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
        $strSQL .= "WHERE status = " . REQUEST_STATUS_APPROVED . " $strKriteria " . " $strKriteriaCompany ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Placement Request - need to be acknowledged: </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    //End of Alert Placement Request
    //Alert Overtime
    $dataPrivilege = getDataPrivileges(
        "overtime_application_list.php",
        $bolCanView,
        $bolCanEdit,
        $bolCanDelete,
        $bolCanApprove,
        $bolCanCheck,
        $bolCanAcknowledge
    );
    /*
        if ($bolCanCheck)
        {
          // cek yang statusnya akan di checked
          $strLink = "javascript:goAlert('overtime_application_list.php',".REQUEST_STATUS_NEW.")";
          $strSQL  = "SELECT COUNT(t0.id) AS total FROM hrd_overtime_application_employee AS t0 ";
          $strSQL  .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
          $strSQL  .= "LEFT JOIN hrd_overtime_application AS t2 ON t0.id_application = t2.id ";
          $strSQL  .= "WHERE t2.status = " .REQUEST_STATUS_NEW. " $strKriteriaOT ";
          //echo $strSQL;
          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
              $strResult .= " <tr valign=top class=$strClass>\n";
              $strResult .= "  <td align=left nowrap>&nbsp;Overtime Request - need to be checked: </td>\n";
              $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
              $strResult .= " </tr>\n";
            }
          }
        }
    */
    if ($bolCanApprove) {
        // cek yang statusnya akan di Approve
        //$strLink = "javascript:goAlert('overtime_application_list.php',".REQUEST_STATUS_CHECKED.")";
        $strLink = "javascript:goAlert('overtime_application_list.php'," . REQUEST_STATUS_NEW . ")";
        $strSQL = "SELECT COUNT(t0.id) AS total FROM hrd_overtime_application_employee AS t0 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
        $strSQL .= "LEFT JOIN hrd_overtime_application AS t2 ON t0.id_application = t2.id ";
        $strSQL .= "WHERE (t0.status = " . REQUEST_STATUS_NEW . " or t0.status = " . REQUEST_STATUS_CHECKED . ") $strKriteriaOT " . " $strKriteriaCompany ";
        //echo $strSQL;
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Overtime Request - need to be approved: </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    // echo $strSQL."jhsadjkahs";
    if ($bolCanAcknowledge) {
        $strLink = "javascript:goAlert('overtime_application_list.php'," . REQUEST_STATUS_APPROVED . ")";
        $strSQL = "SELECT COUNT(t0.id) AS total FROM hrd_overtime_application_employee AS t0 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
        $strSQL .= "LEFT JOIN hrd_overtime_application AS t2 ON t0.id_application = t2.id ";
        $strSQL .= "AND t2.status = " . REQUEST_STATUS_APPROVED . " $strKriteriaOT " . " $strKriteriaCompany ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Overtime Request - beed to be acknowledged: </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    // End of Alert Overtime
    //Alert Employee Temporary Data
    //$dataPrivilege = getDataPrivileges("employee_temporary_list.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge);
    $dataPrivilege = getDataPrivileges(
        "employee_temporary_edit.php",
        $bolCanView,
        $bolCanEdit,
        $bolCanDelete,
        $bolCanApprove,
        $bolCanCheck,
        $bolCanAcknowledge
    );
    $strKriteriaTemp = str_replace('employee_id', 't0.employee_id', $strKriteria);
    /*
       if ($bolCanCheck)
       {
         $strLink = "javascript:goAlert('employee_temporary_list.php',0)";
         $strSQL   = "SELECT count(t0.id) AS total FROM hrd_employee_temporary AS t0 ";
         $strSQL  .= "LEFT JOIN hrd_employee AS t1 ON t0.id = t1.id ";
         $strSQL  .= "WHERE status = ".REQUEST_STATUS_NEW." $strKriteriaTemp";

         $resDb = $db->execute($strSQL);
         if ($rowDb = $db->fetchrow($resDb)) {
           if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
             $strResult .= " <tr valign=top class=$strClass>\n";
             //$strResult .= "  <td align=left nowrap>&nbsp;Temporary employee data - need to be checked: </td>\n";
             $strResult .= "  <td align=left nowrap>&nbsp;Temporary Employee Data - need to be approved: </td>\n";
             $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
             $strResult .= " </tr>\n";
           }
         }
       }
     */
    if ($bolCanApprove) {
        //$strLink = "javascript:goAlert('employee_temporary_list.php',1)";
        $strLink = "javascript:goAlert('employee_temporary_list.php',0)";
        $strSQL = "SELECT count(t0.id) AS total FROM hrd_employee_temporary AS t0 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id = t1.id ";
        $strSQL .= "WHERE (status = " . REQUEST_STATUS_NEW . " or status = " . REQUEST_STATUS_CHECKED . ") $strKriteriaTemp " . " $strKriteriaCompany ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Temporary Employee Data - need to be approved: </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    if ($bolCanAcknowledge) {
        $strLink = "javascript:goAlert('employee_temporary_list.php',1)";
        $strSQL = "SELECT count(t0.id) AS total FROM hrd_employee_temporary AS t0 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id = t1.id ";
        $strSQL .= "WHERE status = " . REQUEST_STATUS_APPROVED . " $strKriteriaTemp " . " $strKriteriaCompany ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Temporary Employee Data - need to be acknowledged: </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    //End of Alert Temporary Employee Data
    //Alert Absence
    $dataPrivilege = getDataPrivileges(
        "absence_list.php",
        $bolCanView,
        $bolCanEdit,
        $bolCanDelete,
        $bolCanApprove,
        $bolCanCheck,
        $bolCanAcknowledge
    );
    /*
       if ($bolCanCheck)
       {
         $strLink = "javascript:goAlert('absence_list.php',".REQUEST_STATUS_NEW.")";
         $strSQL  = "SELECT COUNT(t0.id) AS total FROM hrd_absence as t0 ";
         $strSQL  .= "LEFT JOIN hrd_employee as t1 ON t0.id_employee = t1.id ";
         $strSQL  .= "WHERE status = " .REQUEST_STATUS_NEW." ".$strKriteriaCompany." ". $strKriteria;
         $resDb = $db->execute($strSQL);
         if ($rowDb = $db->fetchrow($resDb)) {
           if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
             $strResult .= " <tr valign=top class=$strClass>\n";
             $strResult .= "  <td align=left nowrap>&nbsp;Absence List - need to be checked: </td>\n";
             $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
             $strResult .= " </tr>\n";
           }
         }
       }
   */
    if ($bolCanApprove) {
        //$strLink = "javascript:goAlert('absence_list.php',".REQUEST_STATUS_CHECKED.")";
        $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_NEW . ")";
        $strSQL = "SELECT COUNT(t0.id) AS total FROM hrd_absence as t0 ";
        $strSQL .= "LEFT JOIN hrd_employee as t1 ON t0.id_employee = t1.id ";
        $strSQL .= "WHERE (status = " . REQUEST_STATUS_NEW . " or status = " . REQUEST_STATUS_CHECKED . ") " . $strKriteriaCompany . " " . $strKriteria;
        //echo $strSQL;
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Absence List - need to be approved: </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    if ($bolCanAcknowledge) {
        $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_APPROVED . ")";
        $strSQL = "SELECT COUNT(t0.id) AS total FROM hrd_absence as t0 ";
        $strSQL .= "LEFT JOIN hrd_employee as t1 ON t0.id_employee = t1.id ";
        $strSQL .= "WHERE status = " . REQUEST_STATUS_APPROVED . " " . $strKriteriaCompany . " " . $strKriteria;
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Absence List - need to be acknowledged: </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    //End of Alert Absence
    //Alert Leave
    // $dataPrivilege = getDataPrivileges("absence_list.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge);
    /*
       if ($bolCanCheck)
       {
         $strLink = "javascript:goAlert('absence_list.php',".REQUEST_STATUS_NEW.", 1)";
         $strSQL  = "SELECT COUNT(t0.id) AS total FROM hrd_absence  AS t0 ";
         $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
         $strSQL .= "LEFT JOIN hrd_absence_type AS t2 ON t0.absence_type_code = t2.code ";
         $strSQL .= "WHERE status = " .REQUEST_STATUS_NEW. "  AND t2.is_leave = TRUE $strKriteria";
         $resDb = $db->execute($strSQL);
         if ($rowDb = $db->fetchrow($resDb))
         {
           if ($rowDb['total'] != "" && $rowDb['total'] > 0)
           {
             $strResult .= " <tr valign=top class=$strClass>\n";
             $strResult .= "  <td align=left nowrap>&nbsp;Leave Request - need to be checked: </td>\n";
             $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
             $strResult .= " </tr>\n";
           }
         }
       }
     */
    // if ($bolCanApprove)
    // {
    //   //$strLink = "javascript:goAlert('absence_list.php',".REQUEST_STATUS_CHECKED.", 1)";
    //   $strLink = "javascript:goAlert('absence_list.php',".REQUEST_STATUS_NEW.", 1)";
    //   $strSQL  = "SELECT COUNT(t0.id) AS total FROM hrd_absence  AS t0 ";
    //   $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
    //   $strSQL .= "LEFT JOIN hrd_absence_type AS t2 ON t0.absence_type_code = t2.code ";
    //   $strSQL .= "WHERE (status = " .REQUEST_STATUS_NEW. " or status = " .REQUEST_STATUS_CHECKED. ")  AND t2.is_leave = TRUE $strKriteria";
    //   $resDb = $db->execute($strSQL);
    //   if ($rowDb = $db->fetchrow($resDb))
    //   {
    //     if ($rowDb['total'] != "" && $rowDb['total'] > 0)
    //     {
    //       $strResult .= " <tr valign=top class=$strClass>\n";
    //       $strResult .= "  <td align=left nowrap>&nbsp;Leave Request - need to be approved: </td>\n";
    //       $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
    //       $strResult .= " </tr>\n";
    //     }
    //   }
    // }
    // if ($bolCanAcknowledge)
    // {
    //   $strLink = "javascript:goAlert('absence_list.php',".REQUEST_STATUS_APPROVED.", 1)";
    //   $strSQL  = "SELECT COUNT(t0.id) AS total FROM hrd_absence  AS t0 ";
    //   $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
    //   $strSQL .= "LEFT JOIN hrd_absence_type AS t2 ON t0.absence_type_code = t2.code ";
    //   $strSQL .= "WHERE status = " .REQUEST_STATUS_APPROVED. "  AND t2.is_leave = TRUE $strKriteria";
    //   $resDb = $db->execute($strSQL);
    //   if ($rowDb = $db->fetchrow($resDb))
    //   {
    //     if ($rowDb['total'] != "" && $rowDb['total'] > 0)
    //     {
    //       $strResult .= " <tr valign=top class=$strClass>\n";
    //       $strResult .= "  <td align=left nowrap>&nbsp;Leave Request - need to be acknowledged: </td>\n";
    //       $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
    //       $strResult .= " </tr>\n";
    //     }
    //   }
    // }
    //End of Alert Leave
    /*
        //Alert Business Trip
        $dataPrivilege = getDataPrivileges("trip_list.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge);

        if ($bolCanCheck)
        {
          $strLink = "javascript:goAlert('trip_list.php', ".REQUEST_STATUS_NEW.")";
          $strSQL  = "SELECT COUNT(t0.id) AS total FROM hrd_trip AS t0 ";
          $strSQL  .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
          $strSQL  .= "WHERE status = " .REQUEST_STATUS_NEW. "  $strKriteria";

          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
              $strResult .= " <tr valign=top class=$strClass>\n";
              $strResult .= "  <td align=left nowrap>&nbsp;Business Trip - need to be checked: </td>\n";
              $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
              $strResult .= " </tr>\n";
            }
          }
        }

        if ($bolCanApprove)
        {
          $strLink = "javascript:goAlert('trip_list.php',".REQUEST_STATUS_CHECKED.")";
          $strSQL  = "SELECT COUNT(t0.id) AS total FROM hrd_trip AS t0 ";
          $strSQL  .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
          $strSQL  .= "WHERE status = " .REQUEST_STATUS_CHECKED. "  $strKriteria";
          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
              $strResult .= " <tr valign=top class=$strClass>\n";
              $strResult .= "  <td align=left nowrap>&nbsp;Business Trip - need to be approved: </td>\n";
              $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
              $strResult .= " </tr>\n";
            }
          }
        }
        if ($bolCanAcknowledge)
        {
          $strLink = "javascript:goAlert('trip_list.php',".REQUEST_STATUS_APPROVED.")";
          $strSQL  = "SELECT COUNT(t0.id) AS total FROM hrd_trip AS t0 ";
          $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
          $strSQL .= "WHERE status = " .REQUEST_STATUS_APPROVED. " $strKriteria";
          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
              $strResult .= " <tr valign=top class=$strClass>\n";
              $strResult .= "  <td align=left nowrap>&nbsp;Business Trip - need to be acknowledged: </td>\n";
              $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
              $strResult .= " </tr>\n";
            }
          }
        }
        // End of Alert Business Trip

        //acknowledge



        //Alert Business Trip Manageriakl
        $dataPrivilege = getDataPrivileges("trip_approval_managerial.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge);

        if ($bolCanCheck)
        {
          $strLink = "javascript:goAlert('trip_approval_managerial.php', ".REQUEST_STATUS_NEW.")";
          $strSQL  = "SELECT COUNT(t0.id) AS total FROM hrd_trip AS t0 ";
          $strSQL  .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
          $strSQL  .= "LEFT JOIN hrd_position AS t2 ON t1.position_code = t2.position_code ";
          $strSQL  .= "WHERE status = " .REQUEST_STATUS_NEW. " AND approver_id = '".$arrUserInfo['employee_id']."'  $strKriteria";

          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
              $strResult .= " <tr valign=top class=$strClass>\n";
              $strResult .= "  <td align=left nowrap>&nbsp;Manager Business Trip - need to be checked: </td>\n";
              $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
              $strResult .= " </tr>\n";
            }
          }
        }

        if ($bolCanApprove)
        {
          $strLink = "javascript:goAlert('trip_approval_managerial.php',".REQUEST_STATUS_CHECKED.")";
          $strSQL  = "SELECT COUNT(t0.id) AS total FROM hrd_trip AS t0 ";
          $strSQL  .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
          $strSQL  .= "LEFT JOIN hrd_position AS t2 ON t1.position_code = t2.position_code ";
          $strSQL  .= "WHERE status = " .REQUEST_STATUS_CHECKED. " AND approver_id = '".$arrUserInfo['employee_id']."'  $strKriteria";
          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
              $strResult .= " <tr valign=top class=$strClass>\n";
              $strResult .= "  <td align=left nowrap>&nbsp;Manager Business Trip - need to be approved: </td>\n";
              $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
              $strResult .= " </tr>\n";
            }
          }
        }
        if ($bolCanAcknowledge)
        {
          $strLink = "javascript:goAlert('trip_approval_managerial.php',".REQUEST_STATUS_APPROVED.")";
          $strSQL  = "SELECT COUNT(t0.id) AS total FROM hrd_trip AS t0 ";
          $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
          $strSQL  .= "LEFT JOIN hrd_position AS t2 ON t1.position_code = t2.position_code ";
          $strSQL .= "WHERE status = " .REQUEST_STATUS_APPROVED. " AND approver_id = '".$arrUserInfo['employee_id']."' $strKriteria";
          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
              $strResult .= " <tr valign=top class=$strClass>\n";
              $strResult .= "  <td align=left nowrap>&nbsp;Manager Business Trip - need to be acknowledged: </td>\n";
              $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
              $strResult .= " </tr>\n";
            }
          }
        }
        // End of Alert Business Trip

        //acknowledge
    */
    //Alert Recruitment
    $dataPrivilege = getDataPrivileges(
        "recruitment_list.php",
        $bolCanView,
        $bolCanEdit,
        $bolCanDelete,
        $bolCanApprove,
        $bolCanCheck,
        $bolCanAcknowledge
    );
    if ($bolCanCheck) {
        $strLink = "javascript:goAlert('recruitment_list.php'," . REQUEST_STATUS_NEW . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_recruitment_need WHERE status = " . REQUEST_STATUS_NEW . " $strKriteriaCompany ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Recruitment List - need to be checked: </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\"><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    if ($bolCanApprove) {
        $strLink = "javascript:goAlert('recruitment_list.php'," . REQUEST_STATUS_CHECKED . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_recruitment_need WHERE status = " . REQUEST_STATUS_CHECKED . " $strKriteriaCompany ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Recruitment List - need to be approved: </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\"><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    if ($bolCanAcknowledge) {
        $strLink = "javascript:goAlert('recruitment_list.php'," . REQUEST_STATUS_APPROVED . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_recruitment_need WHERE status = " . REQUEST_STATUS_APPROVED . " $strKriteriaCompany ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Recruitment List - need to be acknowledged: </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\"><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    //End of Alert Recruitment
    //Alert Training
    /*
        if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN)
        {
          $strLink = "javascript:goAlert('training_plan_list.php',".REQUEST_STATUS_NEW.")";
          $strSQL  = "SELECT COUNT(id1) AS total FROM  ";
          $strSQL .= "(SELECT id AS id1 FROM hrd_training_plan ";
          $strSQL .= "WHERE  ((expected_date > CURRENT_DATE AND (expected_date - interval '1 months') < CURRENT_DATE) OR (expected_date < CURRENT_DATE)) ";
          if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR)
            $strSQL .= "AND department_code = '" .$arrUserInfo['department_code']."' ";
          $strSQL .= "EXCEPT ";
            $strSQL .= "SELECT DISTINCT id_plan AS id1 FROM hrd_training_request ";
            $strSQL .= "WHERE EXTRACT(year FROM request_date) = '" .date("Y")."' ";
            if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR)
              $strSQL .= "AND department_code = '" .$arrUserInfo['department_code']."' ";
          $strSQL .= ") AS tbl ";

          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
              $strResult .= " <tr valign=top class=$strClass>\n";
              $strResult .= "  <td align=left nowrap>&nbsp;Unrequested Training Plan : </td>\n";
              $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
              $strResult .= " </tr>\n";
            }
          }
        }

        // --- Permintaan Training ---
        if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
          // cek yang statusnya udah baru
          $strLink = "javascript:goAlert('training_request_list.php',".REQUEST_STATUS_NEW.")";
          $strSQL  = "SELECT COUNT(id) AS total FROM hrd_training_request WHERE status = " .REQUEST_STATUS_NEW. " ";
          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
              $strResult .= " <tr valign=top class=$strClass>\n";
              $strResult .= "  <td align=left nowrap>&nbsp;New Training Need Request : </td>\n";
              $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
              $strResult .= " </tr>\n";
            }
          }

          // cek yang statusnya udah verified
          $strLink = "javascript:goAlert('training_request_list.php',".REQUEST_STATUS_CHECKED.")";
          $strSQL  = "SELECT COUNT(id) AS total FROM hrd_training_request WHERE status = " .REQUEST_STATUS_CHECKED. " ";
          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
              $strResult .= " <tr valign=top class=$strClass>\n";
              $strResult .= "  <td align=left nowrap>&nbsp;Verified Training Request : </td>\n";
              $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
              $strResult .= " </tr>\n";
            }
          }
        }
        if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
          // cek yang statusnya udah checked
          $strLink = "javascript:goAlert('training_request_list.php',".REQUEST_STATUS_CHECKED.")";
          $strSQL  = "SELECT COUNT(id) AS total FROM hrd_training_request WHERE status = " .REQUEST_STATUS_CHECKED. " ";
          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
              $strResult .= " <tr valign=top class=$strClass>\n";
              $strResult .= "  <td align=left nowrap>&nbsp;Training Request Need Approval : </td>\n";
              $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
              $strResult .= " </tr>\n";
            }
          }
        }
        if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
          // cari request baru yang ada di bawah departmentnnya
          // cek yang statusnya udah new
          $strLink = "javascript:goAlert('training_request_list.php',".REQUEST_STATUS_NEW.")";
          $strSQL  = "SELECT COUNT(t1.id) AS total FROM hrd_training_request AS t1 ";
          $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
          $strSQL .= "WHERE t1.status = " .REQUEST_STATUS_NEW. " ";
          $strSQL .= "AND t2.department_code = '" .$arrUserInfo['department_code']."' ";
          $strSQL .= "AND t2.section_code = '" .$arrUserInfo['section_code']."' ";
          $strSQL .= "AND t1.id_employee <> '" .$arrUserInfo['id_employee']."' ";
          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
              $strResult .= " <tr valign=top class=$strClass>\n";
              $strResult .= "  <td align=left nowrap>&nbsp;New Training Request (Need Approval) : </td>\n";
              $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
              $strResult .= " </tr>\n";
            }
          }
        }*/
    $strResult .= "</table>\n";
    return $strResult;
}

function getRegulation()
{
    /*$folder = "regulation/";
  $handle = opendir($folder);

  # Making an array containing the files in the current directory:
  while ($file = readdir($handle)){
      if( $file != ".." && $file != "." ){
          //$key = filemtime($file);
          $files[] = $file ;
      }
  }
  closedir($handle);
  // sort files by mtime:
  sort($files) ;
  $strResult = "<table width=100 border=0 cellspacing=0 cellpadding=1>";
  foreach ($files as $file) {
    $strFile = str_replace("_", "/", $file);
    $FileName = substr($strFile, 0,-4);
    $strResult .= "<tr><td nowrap><a href=\"peraturan.php?file=$file\" target='read'>$FileName</a></td></tr>" ;
    $i++;
    }

    $strResult .= "</table>";
    return  $strResult;*/
    global $db;
    $strResult = "<table width=\"100%\" class=\"gridTable\">";
    $strResult .= "<tr valign=top height=20>\n";
    $strResult .= "  <th nowrap align=center width=\"20px\">" . getWords("no.") . "&nbsp;</td>";
    $strResult .= "  <th align=center>" . getWords("regulation number") . "&nbsp;</td>";
    $strResult .= "  <th align=center width=\"75%\">" . getWords("description") . "&nbsp;</td>";
    $strResult .= "</tr>\n";
    $strSQL = "SELECT * FROM REGULATION_FILE";
    $resDb = $db->execute($strSQL);
    $i = 0;
    while ($rowDb = $db->fetchrow($resDb)) {
        $no_reg = str_replace('/', '_', $rowDb['no_reg']);
        //$no_reg = str_replace(':', ' ', $rowDb['no_reg']);
        $no = $i + 1;
        $strResult .= "<tr><td nowrap>$no</td><td nowrap><a href=\"peraturan.php?file={$rowDb['file_name']}&d={$no_reg}\" target='read'>{$rowDb['no_reg']}</a></td><td nowrap>{$rowDb['description']}</td></tr>";
        $i++;
    }
    $strResult .= "</table>";
    return $strResult;
}

// fungsi untuk mengambil informasi alert
function getAlertActivity($db)
{
    global $arrUserInfo;
    global $_SESSION;
    global $bolIsEmployee;
    $strClass = "bgNewRevised";
    $strResult = "";
    $strResult = "<table width=100 border=0 cellspacing=0 cellpadding=1>\n";
    // --- Evaluasi
    // --- end Of Evaluasi
    // ---  AMBIL DATA PENGAJUAN DARI KARYAWAN YANG LOGIN, UNTUK TAHU STATUSNYA
    // --- YANG DIAMBIL ADALAH PENGAJUAN 14 HARI KE BELAKANG DAN YANG BELUM
    $intMaxDays = 14; // batas data yang diambil
    global $ARRAY_REQUEST_STATUS;
    global $ARRAY_OT_STATUS;
    $strResult .= " <tr valign=top>\n";
    $strResult .= "  <td align=left nowrap>&nbsp;</td>\n";
    $strResult .= " </tr>\n";
    $strResult .= " <tr valign=top>\n";
    $strResult .= "  <td align=left nowrap style=\"text-decoration:underline; \">&nbsp;<strong>Employee Activity &nbsp;</strong></td>\n";
    $strResult .= " </tr>\n";
    if ($arrUserInfo['id_employee'] != "") {
        // ambil info apakah perubahan employee-nya ada yang ditolak
        $strSQL = "SELECT id FROM hrd_employee WHERE flag=3 ";
        $strSQL .= "AND link_id = " . $arrUserInfo['id_employee'] . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strLink = "employee_edit.php?dataID=" . $rowDb['id'];
            $strResult .= " <tr valign=top class=$strClass>\n";
            $strResult .= "  <td align=left nowrap>&nbsp;DENIED employee data changes </td>\n";
            $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">&nbsp;" . getWords(
                    "view"
                ) . "</a></strong></td>\n";
            $strResult .= " </tr>\n";
        }
        // -- ambil data pengubahan data kehadiran
        $strSQL = "SELECT * FROM hrd_attendance WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' ";
        $strSQL .= "AND ( ((CURRENT_DATE - attendance_date) < $intMaxDays) ";
        $strSQL .= "OR (CURRENT_DATE <= attendance_date)) ";
        // $strSQL .= "AND (change_start is not null OR change_finish is not null) ";
        $strSQL .= "ORDER BY attendance_date DESC ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $strClass = getCssClass($rowDb['status']);
            $strTeks = "Attendance Request: <a href=\"attendance_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
                    $rowDb['attendance_date'],
                    "d-M-y"
                ) . "</a> &nbsp; &raquo; <strong>";
            $strTeks .= getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]);
            $strTeks .= "</strong>";
            $strResult .= " <tr valign=top class=$strClass>\n";
            $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
            $strResult .= " </tr>\n";
        }
        // -- ambil data ijin absen
        $strSQL = "SELECT * FROM hrd_absence WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' ";
        $strSQL .= "AND ( ((CURRENT_DATE - request_date) < $intMaxDays) ";
        $strSQL .= "OR (CURRENT_DATE <= request_date)) ";
        $strSQL .= "ORDER BY request_date DESC ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $strClass = getCssClass($rowDb['status']);
            $strTeks = "Absence Request: <a href=\"absence_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
                    $rowDb['request_date'],
                    "d-M-y"
                ) . "</a> &nbsp; &raquo; <strong>";
            $strTeks .= getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]);
            if ($rowDb['leave_year'] != "") {
                $strTeks .= " (as LEAVE) ";
            }
            $strTeks .= "</strong>";
            $strResult .= " <tr valign=top class=$strClass>\n";
            $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
            $strResult .= " </tr>\n";
        }
        // -- ambil data ijin cuti
        $strSQL = "SELECT * FROM hrd_absence_detail  AS t1 ";
        $strSQL .= "LEFT JOIN hrd_absence_type AS t2 ON t1.absence_type = t2.code ";
        $strSQL .= "LEFT JOIN hrd_absence AS t3 ON t1.id_absence = t3.id WHERE t1.id_employee = '" . $arrUserInfo['id_employee'] . "' AND t2.is_leave = TRUE ";
        $strSQL .= "AND t1.created <= t1.absence_date ORDER BY t1.created DESC ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $strClass = getCssClass($rowDb['status']);
            $strTeks = "Leave Request: <a href=\"leave_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
                    $rowDb['request_date'],
                    "d-M-y"
                ) . "</a> &raquo; <strong>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "</strong>";
            $strResult .= " <tr valign=top class=$strClass>\n";
            $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
            $strResult .= " </tr>\n";
        }
        // -- ambil data permohonan lembur
        $strSQL = "SELECT * FROM hrd_overtime_application_employee WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' ";
        $strSQL .= "AND ( ((CURRENT_DATE - overtime_date) < $intMaxDays) ";
        $strSQL .= "OR (CURRENT_DATE <= overtime_date)) ";
        $strSQL .= "AND status < 3 "; // masih request
        $strSQL .= "ORDER BY overtime_date DESC ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            //$strClass = getCssClass($rowDb['status']);
            $strClass = "";
            switch ($rowDb['status']) {
                case 0 :
                    $strClass = "bgNewData";
                    $intStatus = 0;
                    break;
                case 1 :
                    $strClass = "bgVerifiedData";
                    $intStatus = 1;
                    break;
                case 2 :
                    $strClass = "bgDeniedData";
                    $intStatus = 2;
                    break;
                default :
                    break;
            }
            $strTeks = "Overtime Plan: <a href=\"overtime_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
                    $rowDb['overtime_date'],
                    "d-M-y"
                ) . "</a> &raquo; <strong>" . getWords($ARRAY_OT_STATUS[$intStatus]) . "</strong>";
            $strResult .= " <tr valign=top class=$strClass>\n";
            $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
            $strResult .= " </tr>\n";
        }
        // -- ambil data realisasi lembur
        $strSQL = "SELECT * FROM hrd_overtime_application_employee WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' ";
        $strSQL .= "AND ( ((CURRENT_DATE - overtime_date) < $intMaxDays) ";
        $strSQL .= "OR (CURRENT_DATE <= overtime_date)) ";
        $strSQL .= "AND status > 2 "; // masih request
        $strSQL .= "ORDER BY overtime_date DESC ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            //$strClass = getCssClass($rowDb['status']);
            $strClass = "";
            switch ($rowDb['status']) {
                case 3 :
                    $strClass = "bgNewData";
                    break;
                case 4 :
                    $strClass = "bgVerifiedData";
                    break;
                case 5 :
                    $strClass = "bgVerifiedData";
                    break;
                case 6 :
                    $strClass = "bgCheckedData";
                    break;
                case 7 :
                    $strClass = "bgApprovedData";
                    break;
                case 8 :
                    $strClass = "bgDeniedData";
                    break;
                default :
                    break;
            }
            $intStatus = $rowDb['status'];
            $strTeks = "Overtime Realisation: <a href=\"overtime_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
                    $rowDb['overtime_date'],
                    "d-M-y"
                ) . "</a> &raquo; <strong>" . getWords($ARRAY_OT_STATUS[$intStatus]) . "</strong>";
            $strResult .= " <tr valign=top class=$strClass>\n";
            $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
            $strResult .= " </tr>\n";
        }
        // -- ambil data pengajuan medis
        $strSQL = "SELECT t2.id, t1.claim_date, t2.status FROM hrd_medical_claim AS t1, hrd_medical_claim_master AS t2  ";
        $strSQL .= "WHERE t1.id_master = t2.id ";
        $strSQL .= "AND t2.id_employee = '" . $arrUserInfo['id_employee'] . "' ";
        $strSQL .= "AND ( ((CURRENT_DATE - t1.claim_date) < $intMaxDays) ";
        $strSQL .= "OR (CURRENT_DATE <= t1.claim_date)) ";
        $strSQL .= "--GROUP BY t1.claim_date, t2.status  ";
        $strSQL .= "ORDER BY t1.claim_date DESC ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $strClass = getCssClass($rowDb['status']);
            $strTeks = "Medical Request: <a href=\"medical_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
                    $rowDb['claim_date'],
                    "d-M-y"
                ) . "</a> &raquo; <strong>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "</strong>";
            $strResult .= " <tr valign=top class=$strClass>\n";
            $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
            $strResult .= " </tr>\n";
        }
        /*
              // -- ambil data pengajuan tiras
              $strSQL  = "SELECT * FROM hrd_tiras_master WHERE id_employee = '" .$arrUserInfo['id_employee']."' ";
              $strSQL .= "AND ( ((CURRENT_DATE - request_date) < $intMaxDays) ";
              $strSQL .= "OR (CURRENT_DATE <= request_date)) ";
              $strSQL .= "ORDER BY request_date DESC ";
              $resDb = $db->execute($strSQL);
              while ($rowDb = $db->fetchrow($resDb)) {
                $strClass = getCssClass($rowDb['status']);

                $strTeks = "'Tiras' Request: <a href=\"tiras_edit.php?dataID=" .$rowDb['id']."\">". pgDateFormat($rowDb['request_date'], "d-M-y"). "</a> &raquo; <strong>". getWords($ARRAY_REQUEST_STATUS[$rowDb['status']])."</strong>";
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
                $strResult .= " </tr>\n";

              }

              // -- ambil data perjalanan dinas
              $strSQL  = "SELECT * FROM hrd_trip WHERE id_employee = '" .$arrUserInfo['id_employee']."' ";
              $strSQL .= "AND ( ((CURRENT_DATE - proposal_date) < $intMaxDays) ";
              $strSQL .= "OR (CURRENT_DATE <= proposal_date)) ";
              $strSQL .= "ORDER BY proposal_date DESC ";
              $resDb = $db->execute($strSQL);
              while ($rowDb = $db->fetchrow($resDb)) {
                $strClass = getCssClass($rowDb['status']);

                $strTeks = "Business Trip Request: <a href=\"trip_edit.php?dataID=" .$rowDb['id']."\">". pgDateFormat($rowDb['proposal_date'], "d-M-y"). "</a> &raquo; <strong>". getWords($ARRAY_REQUEST_STATUS[$rowDb['status']])."</strong>";
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
                $strResult .= " </tr>\n";

              }
        */
        // -- ambil data permohonan training
        $strSQL = "SELECT * FROM hrd_training_request WHERE id_employee = '" . $arrUserInfo['id_employee'] . "' ";
        $strSQL .= "AND ( ((CURRENT_DATE - request_date) < $intMaxDays) ";
        $strSQL .= "OR (CURRENT_DATE <= request_date)) ";
        $strSQL .= "ORDER BY request_date DESC ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $strClass = getCssClass($rowDb['status']);
            $strTeks = "Training Request: <a href=\"training_request_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
                    $rowDb['request_date'],
                    "d-M-y"
                ) . "</a> &raquo; <strong>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "</strong>";
            $strResult .= " <tr valign=top class=$strClass>\n";
            $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
            $strResult .= " </tr>\n";
        }
        // -- ambil data keikutsertaan training, yang udah approve
        // -- yang belum terjadi
        $strSQL = "SELECT t2.date_from FROM hrd_training_request_participant AS t1, hrd_training_request AS t2  ";
        $strSQL .= "WHERE t1.id_request = t2.id AND t2.status >= '" . REQUEST_STATUS_APPROVED . "' ";
        $strSQL .= "AND t1.id_employee = '" . $arrUserInfo['id_employee'] . "' ";
        $strSQL .= "AND t2.training_status  = 0 ";
        $strSQL .= "AND CURRENT_DATE <= t2.date_from ";
        $strSQL .= "ORDER BY t2.date_from DESC ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $strTeks = "Training Participation : " . pgDateFormat(
                    $rowDb['date_from'],
                    "d-M-y"
                ) . " &raquo; ";//<strong>". getWords($ARRAY_REQUEST_STATUS[$rowDb['status']])."</strong>";
            $strResult .= " <tr valign=top >\n";
            $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
            $strResult .= " </tr>\n";
        }
        // cari training yang belum sempat dibuat evaluasinya
        $strSQL = "
          SELECT t2.id as idx, t2.date_from, t1.id
          FROM hrd_training_request_participant AS t1, hrd_training_request AS t2
          WHERE t1.id_request = t2.id AND t2.status >= '" . REQUEST_STATUS_APPROVED . "'
            AND t1.id_employee = '" . $arrUserInfo['id_employee'] . "'
            AND t2.training_status  = 0
            AND CURRENT_DATE > t2.date_from
            AND EXTRACT(year FROM t2.date_from) = '" . date("Y") . "'
          EXCEPT
          SELECT DISTINCT t1.id_request AS idx, t2.date_from, t3.id
          FROM hrd_training_evaluation AS t1, hrd_training_request AS t2, hrd_training_request_participant AS t3
          WHERE t1.id_request = t2.id AND t2.id = t3.id_request
            AND t1.id_employee = " . $arrUserInfo['id_employee'] . "
            AND EXTRACT(year FROM t2.date_from) = '" . date("Y") . "'
      ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $strTeks = "Training Need Evaluation : <a href=\"training_evaluation_edit.php?dataID=" . $rowDb['id'] . "\">" . pgDateFormat(
                    $rowDb['date_from'],
                    "d-M-y"
                ) . "</a> &raquo; ";//<strong>". getWords($ARRAY_REQUEST_STATUS[$rowDb['status']])."</strong>";
            $strResult .= " <tr valign=top >\n";
            $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
            $strResult .= " </tr>\n";
        }
        /*
              // -- ambil data permintaan kendaraan
              $strSQL  = "SELECT * FROM ga_transport_request WHERE id_employee = '" .$arrUserInfo['id_employee']."' ";
              $strSQL .= "AND ( ((CURRENT_DATE - request_date) < $intMaxDays) ";
              $strSQL .= "OR (CURRENT_DATE <= request_date)) ";
              $strSQL .= "ORDER BY request_date DESC ";
              $resDb = $db->execute($strSQL);
              while ($rowDb = $db->fetchrow($resDb)) {
                $strClass = getCssClass($rowDb['status']);

                $strTeks = "Transport Request: ". pgDateFormat($rowDb['request_date'], "d-M-y"). " &raquo; <strong>";
                $strTeks .= getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]);
                if ($rowDb['vehicleStatus'] == 1)
                  $strTeks .= " -- ".getWords("available");
                else if ($rowDb['vehicleStatus'] == 2)
                  $strTeks .= " -- ".getWords("not available");
                $strTeks .= "</strong>";
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;$strTeks </td>\n";
                $strResult .= " </tr>\n";

              }
        */
    }
    // --- END, pengambilan data pengajuan apa aja oleh karyawan
    $strResult .= "</table>\n";
    return $strResult;
} //getAlertActivity
// fungsi untuk menentukan kelas CSS yagn akan digunakan, sesuai status request
/*function getCssClass($intStatus = 0) {
  $strResult = "";
  switch ($intStatus) {
    case 0: $strResult = "bgNewData";break;
    case 1: $strResult = "bgVerifiedData";break;
    case 2: $strResult = "bgCheckedData";break;
    case 3: $strResult = "bgApprovedData";break;
    case 4: $strResult = "bgDeniedData";break;
    default : $strResult = "";break;
  }
  return $strResult;
}*///getCssClass
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
//--- Ambil Data Yang Dikirim ----
$dtNow = getdate();
(isset($_REQUEST['dataWYear'])) ? $strDataWYear = $_REQUEST['dataWYear'] : $strDataWYear = $dtNow['year'];
(isset($_REQUEST['dataMonth'])) ? $strDataMonth = $_REQUEST['dataMonth'] : $strDataMonth = $dtNow['mon'];
(isset($_REQUEST['dataYear'])) ? $strDataYear = $_REQUEST['dataYear'] : $strDataYear = $dtNow['year'];
if (!is_numeric($strDataYear)) {
    $strDataYear = $dtNow['year'];
}
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $bolIsEmployee = isUserEmployee();
    $arrUserList = getAllUserInfo($db);
    scopeData(
        $strDataEmployee,
        $strDataSubSection,
        $strDataSection,
        $strDataSubDepartment,
        $strDataDepartment,
        $strDataDivision,
        $_SESSION['sessionUserRole'],
        $arrUserInfo
    );
    //if ($strDataSubSection != "")   $strKriteria .= "AND sub_section_code = '$strDataSubSection' ";
    //if ($strDataSection != "")      $strKriteria .= "AND section_code = '$strDataSection' ";
    //if ($strDataDepartment != "")   $strKriteria .= "AND department_code = '$strDataDepartment' ";
    if ($strDataDivision != "") {
        if ($strDataDivision != "C0100") { // khusus direksi tidak melihat divisi
            $strKriteria .= " AND division_code = '$strDataDivision' ";
            $strKriteriaDiv = " where active=1 and division_code= '" . $strDataDivision . "' ";
        } else {
            $strKriteriaDiv = " where active=1 ";
        }
    }
    //if ($strDataSubSection != "")   $strKriteria .= "AND sub_section_code = '$strDataSubSection' ";
    //uddin: tambah kriteria jika employee maka yg muncul employee yg functional dia dan dibawahnya
    $strDataUserRole = $_SESSION['sessionUserRole'];
    if ($strDataUserRole == ROLE_EMPLOYEE or $strDataUserRole == ROLE_SUPERVISOR) {
        if ($arrUserInfo["functional_code"] != "") {
            //$strSQL="SELECT * FROM hrd_functional WHERE head_code='".$arrUserInfo["functional_code"]."'";
            $strSQL = "SELECT t1.*,t2.employee_id FROM hrd_functional as t1
                    LEFT JOIN  (select * from hrd_employee " . $strKriteriaDiv . " AND active = 1) as t2 ON t1.functional_code=t2.functional_code
                    WHERE t1.head_code='" . $arrUserInfo["functional_code"] . "'";
            $resDb = $db->execute($strSQL);
            //$strFunctionalcode="('".$arrUserInfo["functional_code"]."'"; // inisial masukkan kode functional diri sendiri
            $strFunctionalcode = "('DUMMYINVOSAFUNCT'"; // inisial masukkan kode functional diri sendiri
            while ($rowDb = $db->fetchrow($resDb)) {
                //$strFunctionalcode.=",'".$rowDb['functional_code']."'";
                $tempRecursif = getfunctionalrecursif(
                    $db,
                    $rowDb['functional_code'],
                    $rowDb['employee_id'],
                    $strKriteriaDiv,
                    0
                );
                $strFunctionalcode .= ",'" . $rowDb['functional_code'] . "'" . $tempRecursif;
            }
            $strFunctionalcode .= ")";
            $strKriteria .= " AND functional_code in " . $strFunctionalcode . " ";
            //$strKriteria .= "AND (functional_code in ".$strFunctionalcode." or employee_id='".$arrUserInfo["employee_id"]."') ";
        }
    }
    // end tambah kriteria functional code
    //if ($_SESSION['sessionIdGroup'] == 13) $strKriteria .= "AND (salary_grade_code = 'MGR' OR department_code LIKE 'SEC%')";
    $strKriteriaOT = $strKriteria;
    // echo $strKriteriaOT;
    $strKriteria .= "AND employee_id <> '" . $arrUserInfo['employee_id'] . "'";
    if ($bolCanView) {
        $strDepartment = $arrUserInfo['department_code'] . " - " . $arrUserInfo['section_code'];
        $strPosition = $arrUserInfo['position_code'] . "";
        $strDetailAlert = "";
        /*if ($_SESSION['sessionUserRole'] != ROLE_EMPLOYEE)
        {
          $strDetailAlert .= getAlert($db);
          //$strDetailAlert .= "<br>". getAlertActivity($db);
        }*/
        //comment by adnan , add
        $strHideHeader = "";
        //if (($bolIsEmployee && $arrUserInfo["functional_code"]!= "") || ($strDataUserRole == ROLE_ADMIN || $strDataUserRole == ROLE_SUPER)){
        if (($arrUserInfo["functional_code"] != "") || ($strDataUserRole == ROLE_ADMIN || $strDataUserRole == ROLE_SUPER)) {
            $strDetailAlert .= getAlert($db);
        }
        $strDetailEotm = getDataEotm($db);
        $strDetailTampilovertime = getDataTampilovertime($db);
        $strDetailNews = getDataNews($db);
        // hanya admin yg lihat list kontrak
        if ($_SESSION['sessionUserRole'] == ROLE_ADMIN || $_SESSION['sessionUserRole'] == ROLE_SUPER || $_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
            $strDetailBirthday = getDataBirthday($db);
            $strDetailContract = getDataContract($db);
            $strDetailTripcuti = getDataTripcuti($db);
            $strWordsEmployeeInfoLink = "<a href=\"employee_search.php\" class=\"pie-title\">$strWordsEmployeeInfo</a>";
            $strWordsAbsenceLink = "<a href=\"absence_edit.php\" class=\"pie-title\">$strWordsAbsence</a>";
            $strWordsAttendanceLink = "<a href=\"attendance_list.php\" class=\"pie-title\">$strWordsAttendance</a>";
            $strWordsLeaveLink = "<a href=\"leave_annual.php\" class=\"pie-title\">$strWordsLeave</a>";
            $strWordsSalarySlipLink = "";
            $strWordsSubSalarySlip = "";
        } else {
            $strHideHeader = "style=display:none";
            $strDetailContract = "";
            $strDetailBirthday = "";
            $strDetailTripcuti = "";// getDataTripcuti($db);
            $strWordsEmployeeInfoLink = "<a href=\"employee_temporary_edit.php\" class=\"pie-title\">$strWordsEmployeeInfo</a>";
            $strWordsAbsenceLink = "<a href=\"absence_edit.php\" class=\"pie-title\">$strWordsAbsence</a>";
            $strWordsAttendanceLink = "<a href=\"attendance_list.php\" class=\"pie-title\">$strWordsAttendance</a>";
            $strWordsLeaveLink = "<a href=\"leave_annual.php\" class=\"pie-title\">$strWordsLeave</a>";
            $strWordsSalarySlipLink = "<a href=\"salary_report_slip.php\" class=\"pie-title\">$strWordsSalarySlip</a>";
        }
        //$strDetailContract = getDataContract($db);
        getEmployeeInfo($db);
        if (isset($arrDataEmployee['employee_id'])) {
            $strEmployeeID = $arrDataEmployee['employee_id'];
            $strEmployeeName = $arrDataEmployee['employee_name'];
            $strEmployeeStatus = getWords($ARRAY_EMPLOYEE_STATUS[$arrDataEmployee['employee_status']]);
            // $strLeave = $arrDataEmployee['leaveQuota'];
            // $strMedical = standardFormat($arrDataEmployee['medicalQuota']);
        }
        //writeLog(ACTIVITY_VIEW, MODULE_PAYROLL,"",0);
    } else {
        showError("view_denied");
    }
}
$strDisplayShortcut = "display:block"; //($_SESSION['sessionUserRole'] >= ROLE_ADMIN) ? "display:block" : "display:none";
$strDisplayTripcuti = "display:block";
$strDisplayEotm = "display:block";
//$strDisplayTampilovertime = ($_SESSION['sessionUserRole'] >= ROLE_ADMIN) ? "display:block" : "display:none";
// 28,29,30,31,35,36
$strDisplayTampilovertime = ($_SESSION['sessionIdGroup'] === '28' || $_SESSION['sessionIdGroup'] === '29' || $_SESSION['sessionIdGroup'] === '30' || $_SESSION['sessionIdGroup'] === '31' || $_SESSION['sessionIdGroup'] === '35' || $_SESSION['sessionIdGroup'] === '36') ? "display:block" : "display:none";
$strDisplayBirthday = "display:block"; //($_SESSION['sessionUserRole'] >= ROLE_SUPERVISOR) ? "display:block" : "display:none";
$strDisplayAlert = "display:block";//($_SESSION['sessionUserRole'] >= ROLE_SUPERVISOR) ? "display:block" : "display:none";
// $strDisplayContract = ($_SESSION['sessionEmployeeID'] === '120002' || $_SESSION['sessionEmployeeID'] === '111001' || $_SESSION['sessionEmployeeID'] === '211016') ? "display:block" : "display:none";  ////($_SESSION['sessionUserRole'] >= ROLE_SUPERVISOR) ? "display:block" : "display:none";
$strDisplayContract = "display:block";
$strMonthYear = strtoupper(getBulan($strDataMonth)) . " - " . $strDataWYear;
$strCalendar = getMonthlyCalendar($db, $strDataMonth, $strDataWYear, "50", true);
$strEmployeeKRA = getEmployeeKRA($db);
$strRegulation = getRegulation();
$strHidden .= "<input type=\"hidden\" name=\"dataMonth\" value=\"$strDataMonth\">";
$strHidden .= "<input type=\"hidden\" name=\"dataWYear\" value=\"$strDataWYear\">";
$tbsPage = new clsTinyButStrong;
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$tbsPage->LoadTemplate($strMainTemplate);
//$tbsPage->LoadTemplate($sss) ;
$tbsPage->Show();
?>
