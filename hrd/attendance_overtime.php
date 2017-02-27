<?php
session_start();
include_once('global.php');
include_once('form_object.php');
include_once('overtime_func.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=attendance_overtime.php");
    exit();
}
$bolCanView = getUserPermission("attendance_overtime.php", $bolCanEdit, $bolCanDelete, $strError);
$strTemplateFile = getTemplate("attendance_overtime.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strPaging = "&nbsp;";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataDate, &$intRows, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $strDefaultStart;
    global $strDefaultFinish;
    global $strPaging;
    global $intTotalData;
    global $intRowsLimit;
    $intRowsLimit = getSetting("rows_per_page");
    if (!is_numeric($intRowsLimit)) {
        $intRowsLimit = 50;
    }
    $intTipe = 0; // default hari normal
    // cari total data
    $intTotal = 0;
    $strSQL = "SELECT count(t1.id) AS total FROM hrd_employee AS t1,  ";
    $strSQL .= "hrd_overtime_application AS t2, hrd_overtime_application_employee AS t3 ";
    $strSQL .= "WHERE t1.id = t3.id_employee AND t2.id = t3.id_application ";
    $strSQL .= "AND t2.overtime_date = '$strDataDate' ";
    $strSQL .= "$strKriteria ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        if (is_numeric($rowDb['total'])) {
            $intTotal = $rowDb['total'];
        }
    }
    $strPaging = getPaging($intPage, $intTotal, "javascript:goPage('[PAGE]')");
    if ($strPaging == "") {
        $strPaging = "1&nbsp;";
    }
    $intStart = (($intPage - 1) * $intRowsLimit);
    $intRows = 0;
    $strResult = "";
    list($tahun, $bulan, $tanggal) = explode("-", $strDataDate);
    $dtTmp = getdate(mktime(0, 0, 0, (int)$bulan, (int)$tanggal, $tahun));
    if ($dtTmp['wday'] == 5) { //hari jumat
        // hari jumat
        $intTipe = 1;
        if (($strDefaultFinish = substr(getSetting("friday_finish_time"), 0, 5)) == "") {
            $strDefaultFinish = "18:30";
        }
    } //
    $strSQL = "SELECT t1.* FROM hrd_employee  AS t1,  ";
    $strSQL .= "hrd_overtime_application AS t2, hrd_overtime_application_employee AS t3 ";
    $strSQL .= "WHERE t1.active = 1 AND t1.id = t3.id_employee ";
    $strSQL .= "AND t2.id = t3.id_application AND t2.overtime_date = '$strDataDate' ";
    $strSQL .= "$strKriteria ";
    $strSQL .= "ORDER BY $strOrder employee_name ";
    if ($bolLimit) {
        $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
    }
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        ($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
        ($rowDb['transportCode'] == "") ? $strDisabledTransport = "disabled" : $strDisabledTransport = "";
        $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
        //---- CARI DATA KEHADIRAN --------------------------
        //-- INISIALISASI KEHADIRAN -----------------------------------
        $strDataAttendanceStart = "";
        $strDataAttendanceFinish = "";
        $strDataNormalStart = "";
        $strDataNormalFinish = "";
        $strDataNotLate = "t";
        $strDataTransport = "1";
        $strDataNote = "";
        $strDataAttendanceID = "";
        $strShift = "&nbsp;";
        // jika belum ada data, coba cari data kehadiran dari jadwal shift, jika ada
        if (isShift($db, $rowDb['id'], $strDataDate, $strDataNormalStart, $strDataNormalFinish)) {
            $strDataNormalStart = substr($strDataNormalStart, 0, 5);
            $strDataNormalFinish = substr($strDataNormalFinish, 0, 5);
            $strShift = "&radic;";
        }
        // ------------------------------------------------------------
        $strSQL = "SELECT * FROM hrd_attendance ";
        $strSQL .= "WHERE id_employee = '" . $rowDb['id'] . "' ";
        $strSQL .= "AND attendance_date = '$strDataDate' ";
        $resAtt = $db->execute($strSQL);
        if ($rowAtt = $db->fetchrow($resAtt)) {
            $strDataAttendanceStart = substr($rowAtt['attendance_start'], 0, 5);
            $strDataAttendanceFinish = substr($rowAtt['attendance_finish'], 0, 5);
            $strDataNormalStart = substr($rowAtt['normal_start'], 0, 5);
            $strDataNormalFinish = substr($rowAtt['normal_finish'], 0, 5);
            $strDataNotLate = $rowAtt['notLate'];
            $strDataTransport = ($rowAtt['transport'] == "") ? 0 : $rowAtt['transport'];
            $strDataNote = $rowAtt['note'];
            $strDataAttendanceID = $rowAtt['id'];
        }
        //jika data normal dan start kosong, diisi dengan default
        if ($strDataNormalStart == "") {
            $strDataNormalStart = $strDefaultStart;
        }
        if ($strDataNormalFinish == "") {
            $strDataNormalFinish = $strDefaultFinish;
        }
        // buat input data
        $strDataAttendanceStart = "<input type=text size=5 maxsize=5 name=detailAttendanceStart$intRows id=detailAttendanceStart$intRows value=\"$strDataAttendanceStart\" onChange=\"checkLate($intRows);\">";
        $strDataAttendanceFinish = "<input type=text size=5 maxsize=5 name=detailAttendanceFinish$intRows id=detailAttendanceFinish$intRows value=\"$strDataAttendanceFinish\">";
        $strDataNormalStart = "<input type=text size=5 maxsize=5 name=detailNormalStart$intRows id=detailNormalStart$intRows value=\"$strDataNormalStart\" onChange=\"checkLate($intRows);\">";
        $strDataNormalFinish = "<input type=text size=5 maxsize=5 name=detailNormalFinish$intRows id=detailNormalFinish$intRows value=\"$strDataNormalFinish\">";
        if ($strDataNotLate == 't') {
            $strDataNotLate = "<input type=checkbox name=detailNotLate$intRows id=detailNotLate$intRows value=\"t\" checked>";
        } else {
            $strDataNotLate = "<input type=checkbox name=detailNotLate$intRows id=detailNotLate$intRows value=\"t\">";
        }
        ($strDataTransport == "0") ? $strChecked = "" : $strChecked = "checked";
        $strDataTransport = "<input type=checkbox name=detailTransport$intRows id=detailTransport$intRows value=\"1\" $strChecked $strDisabledTransport>";
        $strDataNote = "<input type=text size=30 maxsize=90 name=detailNote$intRows id=detailNote$intRows value=\"$strDataNote\">";
        $strDataAttendanceID = "<input type=hidden name=detailAttendanceID$intRows id=detailAttendanceID$intRows value=\"$strDataAttendanceID\">";
        // ----- TAMPILKAN DATA ---------------------------------------
        $strResult .= "<tr valign=top id=detailData$intRows title=\"$strEmployeeInfo\">\n";
        $strResult .= "  <td nowrap align=right>$intRows.&nbsp;$strDataAttendanceID</td>";
        $strResult .= "  <td nowrap><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">" . $rowDb['employee_id'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['department_code'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['section_code'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['sub_section_code'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $strDataAttendanceStart . "</td>";
        $strResult .= "  <td>" . $strDataAttendanceFinish . "</td>";
        $strResult .= "  <td>" . $strDataNormalStart . "</td>";
        $strResult .= "  <td>" . $strDataNormalFinish . "</td>";
        $strResult .= "  <td>" . $strDataNotLate . "</td>";
        $strResult .= "  <td>" . $strDataTransport . "</td>";
        $strResult .= "  <td align=center>" . $strShift . "</td>";
        $strResult .= "  <td>" . $strDataNote . "</td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "Date=$strDataDate", 0);
    }
    return $strResult;
} // showData
// fungsi untuk menyimpan data yang dikirim
// $db = kelas database, $strError, pesan kesalahan atau pemberitahuan sukses
function saveData($db, &$strError)
{
    global $words;
    global $messages;
    global $_SESSION;
    global $_REQUEST;
    $strError = "";
    (isset($_REQUEST['totalData'])) ? $intTotal = $_REQUEST['totalData'] : $intTotal = 0;
    (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = "";
    // ---- VALIDASI ----
    if ($strDataDate == "") {
        $strError = "Error date";
        return false;
    }
    // cek jenis hari
    $intWDay = getWDay($strDataDate);
    $bolHoliday = isHoliday($strDataDate);
    $intTipe = ($intWDay == 5) ? 1 : 0; // jumat atau bukan
    // ---------------- !!!!!!!!! -----------------------------------
    for ($i = 1; $i <= $intTotal; $i++) {
        (isset($_REQUEST['detailID' . $i])) ? $strID = $_REQUEST['detailID' . $i] : $strID = "";
        if ($strID != "") {
            (isset($_REQUEST['detailAttendanceStart' . $i])) ? $strAttendanceStart = $_REQUEST['detailAttendanceStart' . $i] : $strAttendanceStart = "";
            (isset($_REQUEST['detailAttendanceFinish' . $i])) ? $strAttendanceFinish = $_REQUEST['detailAttendanceFinish' . $i] : $strAttendanceFinish = "";
            (isset($_REQUEST['detailNormalStart' . $i])) ? $strNormalStart = $_REQUEST['detailNormalStart' . $i] : $strNormalStart = $strAttendanceStart;
            (isset($_REQUEST['detailNormalFinish' . $i])) ? $strNormalFinish = $_REQUEST['detailNormalFinish' . $i] : $strNormalFinish = $strAttendanceFinish;
            (isset($_REQUEST['detailNotLate' . $i])) ? $bolNotLate = 't' : $bolNotLate = 'f';
            (isset($_REQUEST['detailTransport' . $i])) ? $bolTransport = '1' : $bolTransport = '0';
            (isset($_REQUEST['detailNote' . $i])) ? $strNote = $_REQUEST['detailNote' . $i] : $strNote = "";
            (isset($_REQUEST['detailAttendanceID' . $i])) ? $strAttendanceID = $_REQUEST['detailAttendanceID' . $i] : $strAttendanceID = "";
            if ($strAttendanceStart != "" && $strAttendanceFinish != "") {
                $intTotalHour = getTotalHour($strAttendanceStart, $strAttendanceFinish);
            } else {
                $intTotalHour = 0;
            }
            $arrLembur = calculateOvertime(
                $db,
                $strDataDate,
                $strNormalStart,
                $strNormalFinish,
                $strAttendanceStart,
                $strAttendanceFinish
            );
            // cari apakah ada OVERTIME APPLICATION yang sudah approved
            // jika ada, simpan data lembur
            $strSQL = "SELECT t1.id, t1.start_plan, t1.finish_plan FROM hrd_overtime_application_employee AS t1, ";
            $strSQL .= "hrd_overtime_application AS t2 WHERE t1.id_application = t2.id ";
            $strSQL .= "AND t1.id_employee = '$strID' AND t2.overtime_date = '$strDataDate' ";
            $resTmp = $db->execute($strSQL);
            if ($rowTmp = $db->fetchrow($resTmp)) {
                // cek dulu , hari libur atau bukan
                if ($bolHoliday) {
                    $arrLemburApp = calculateOvertime(
                        $db,
                        $strDataDate,
                        $rowTmp['startPlan'],
                        $rowTmp['finishPlan'],
                        $strAttendanceStart,
                        $strAttendanceFinish
                    );
                    $strTmpStart = $strAttendanceStart;
                    $strTmpFinish = $strAttendanceFinish;
                } else {
                    $arrLemburApp = calculateOvertime(
                        $db,
                        $strDataDate,
                        $strNormalStart,
                        $rowTmp['startPlan'],
                        $strAttendanceStart,
                        $strAttendanceFinish
                    );
                    $strTmpStart = $rowTmp['startPlan'];
                    $strTmpFinish = $strAttendanceFinish;
                }
                // update data di aplikasi lemburnya
                ($strTmpStart == "") ? $strTmpStart = "NULL" : $strTmpStart = "'$strTmpStart'";
                ($strTmpFinish == "") ? $strTmpFinish = "NULL" : $strTmpFinish = "'$strTmpFinish'";
                $strSQL = "UPDATE hrd_overtime_application_employee ";
                $strSQL .= "SET start_actual = $strTmpStart, finish_actual = $strTmpFinish,  ";
                $strSQL .= "l1 = '" . $arrLemburApp['l1'] . "', l2 = '" . $arrLemburApp['l2'] . "', ";
                $strSQL .= "l3 = '" . $arrLemburApp['l3'] . "', l4 = '" . $arrLemburApp['l4'] . "', ";
                $strSQL .= "total_time = '" . $arrLemburApp['total'] . "' ";
                $strSQL .= "WHERE id = " . $rowTmp['id'];
                $resExec = $db->execute($strSQL);
            }
            //--- end overtime application ---
            //--- tanganni data yang kosong
            ($strAttendanceStart == "") ? $strAttendanceStart = "NULL" : $strAttendanceStart = "'$strAttendanceStart'";
            ($strAttendanceFinish == "") ? $strAttendanceFinish = "NULL" : $strAttendanceFinish = "'$strAttendanceFinish'";
            ($strNormalStart == "") ? $strNormalStart = $strAttendanceStart : $strNormalStart = "'$strNormalStart'";
            ($strNormalFinish == "") ? $strNormalFinish = $strAttendanceFinish : $strNormalFinish = "'$strNormalFinish'";
            if ($strAttendanceStart == "NULL" && $strAttendanceFinish == "NULL") { // hapus data
                $strSQL = "DELETE FROM hrd_attendance ";
                $strSQL .= "WHERE id_employee = '$strID' AND attendance_date = '$strDataDate' ";
                $resExec = $db->execute($strSQL);
            } else {
                // ---- simpan data
                // cari info apakah ada shift atau gak
                $intShiftType = 0;
                $strSQL = "SELECT * FROM hrd_shift_schedule_employee ";
                $strSQL .= "WHERE id_employee = '$strID' AND shift_date = '$strDataDate' ";
                $resS = $db->execute($strSQL);
                if ($rowS = $db->fetchrow($resS)) {
                    $intShiftType = ($rowS['startTime'] < $rowS['finishTime']) ? 1 : 2;
                }
                if ($strAttendanceID == "") { // data baru
                    $strSQL = "INSERT INTO hrd_attendance ";
                    $strSQL .= "(created, created_by, modified_by, id_employee, attendance_date, ";
                    $strSQL .= "attendance_start, attendance_finish, normal_start, normal_finish, ";
                    $strSQL .= "not_late, transport, note, total_duration, ";
                    $strSQL .= "morning_overtime, late_duration, early_duration, ";
                    $strSQL .= "l1, l2, l3, l4, overtime, shift_type) ";
                    $strSQL .= "VALUES(now(), '" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
                    $strSQL .= "'$strID', '$strDataDate', $strAttendanceStart, $strAttendanceFinish, ";
                    $strSQL .= "$strNormalStart, $strNormalFinish, '$bolNotLate', ";
                    $strSQL .= "'$bolTransport', '$strNote', '$intTotalHour', ";
                    $strSQL .= "'" . $arrLembur['morning'] . "', '" . $arrLembur['late'] . "', '" . $arrLembur['early'] . "', ";
                    $strSQL .= "'" . $arrLembur['l1'] . "', '" . $arrLembur['l2'] . "', '" . $arrLembur['l3'] . "', ";
                    $strSQL .= "'" . $arrLembur['l4'] . "', '" . $arrLembur['total'] . "', '$intShiftType') ";
                    $resExec = $db->execute($strSQL);
                } else { // data lama
                    $strSQL = "UPDATE hrd_attendance SET created=now(), ";
                    $strSQL .= "modified_by = '" . $_SESSION['sessionUserID'] . "', ";
                    $strSQL .= "id_employee = '$strID', attendance_date = '$strDataDate', ";
                    $strSQL .= "attendance_start = $strAttendanceStart, attendance_finish = $strAttendanceFinish, ";
                    $strSQL .= "normal_start = $strNormalStart, normal_finish = $strNormalFinish, ";
                    $strSQL .= "not_late = '$bolNotLate', transport = '$bolTransport', ";
                    $strSQL .= "note = '$strNote', total_duration = '$intTotalHour', ";
                    $strSQL .= "morning_overtime = '" . $arrLembur['morning'] . "', ";
                    $strSQL .= "late_duration = '" . $arrLembur['late'] . "', ";
                    $strSQL .= "early_duration = '" . $arrLembur['early'] . "', ";
                    $strSQL .= "l1 = '" . $arrLembur['l1'] . "', l2 = '" . $arrLembur['l2'] . "',  ";
                    $strSQL .= "l3 = '" . $arrLembur['l3'] . "', l4 = '" . $arrLembur['l4'] . "',  ";
                    $strSQL .= "overtime = '" . $arrLembur['total'] . "', shift_type = '$intShiftType'  ";
                    $strSQL .= "WHERE id = '$strAttendanceID' ";
                    $resExec = $db->execute($strSQL);
                }
            }
        }//if
    }//for
    // update data keterangan hari libur atau tidak untuk aplikasi yang dibuat
    ($bolHoliday) ? $intHoliday = 1 : $intHoliday = 0;
    $strSQL = "UPDATE hrd_attendance SET holiday = $intHoliday ";
    $strSQL .= "WHERE attendance_date = '$strDataDate' ";
    $resExec = $db->execute($strSQL);
    $strSQL = "UPDATE hrd_overtime_application SET holiday = $intHoliday ";
    $strSQL .= "WHERE overtime_date = '$strDataDate' ";
    $resExec = $db->execute($strSQL);
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "Date=$strDataDate", 0);
    $strError = $messages['data_saved'] . " >> " . date("r");
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
//$intDefaultStart = "07:30";
//$intDefaultFinish = "16:30";
$db = new CdbClass;
if ($db->connect()) {
    // ambil setting default start dan finish kerja
    if (($strDefaultStart = substr(getSetting("start_time"), 0, 5)) == "") {
        $strDefaultStart = "07:30";
    }
    if (($strDefaultFinish = substr(getSetting("finish_time"), 0, 5)) == "") {
        $strDefaultFinish = "18:30";
    }
    if ($bolCanEdit && (isset($_REQUEST['btnSave']))) {
        saveData($db, $strError);
        if ($strError != "") {
            echo "<script>alert(\"$strError\");</script>";
        }
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = date("Y-m-d");
    (isset($_REQUEST['dataDivision'])) ? $strDataDivision = $_REQUEST['dataDivision'] : $strDataDivision = "";
    (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
    (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
    (isset($_REQUEST['dataSubsection'])) ? $strDataSubsection = $_REQUEST['dataSubsection'] : $strDataSubsection = "";
    (isset($_REQUEST['dataTransport'])) ? $strDataTransport = $_REQUEST['dataTransport'] : $strDataTransport = "";
    (isset($_REQUEST['dataGroup'])) ? $strDataGroup = $_REQUEST['dataGroup'] : $strDataGroup = "";
    (isset($_REQUEST['dataPage'])) ? $intCurrPage = $_REQUEST['dataPage'] : $intCurrPage = 1;
    if (!is_numeric($intCurrPage)) {
        $intCurrPage = 1;
    }
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    $strKriteria = "";
    if ($strDataDivision != "") {
        $strKriteria .= "AND t1.division_code = '$strDataDivision' ";
    }
    if ($strDataDepartment != "") {
        $strKriteria .= "AND t1.department_code = '$strDataDepartment' ";
    }
    if ($strDataSection != "") {
        $strKriteria .= "AND t1.section_code = '$strDataSection' ";
    }
    if ($strDataSubsection != "") {
        $strKriteria .= "AND t1.sub_section_code = '$strDataSubsection' ";
    }
    if ($strDataTransport != "") {
        $strKriteria .= "AND t1.transport_code = '$strDataTransport' ";
    }
    if ($strDataGroup != "") {
        $strKriteria .= "AND t1.\"groupCode\" = '$strDataGroup' ";
    }
    if ($bolCanView) {
        if (validStandardDate($strDataDate) && isset($_REQUEST['btnShow'])) {
            // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
            $strDataDetail = getData($db, $strDataDate, $intTotalData, $strKriteria, $intCurrPage);
            $strHidden .= "<input type=hidden name=btnShow value=show>";
        } else {
            $strDataDetail = "";
        }
    } else {
        $strDataDetail = "";
        showError("view_denied");
    }
    //--- TAMPILKAN INPUT DATA -------------------------
    // generate data hidden input dan element form input
    $intDefaultWidthPx = 200;
    $strInputDate = "<input type=text name=dataDate id=dataDate size=15 maxlength=10 value=\"$strDataDate\">";
    $strInputDivision = getDivisionList(
        $db,
        "dataDivision",
        $strDataDivision,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputDepartment = getDepartmentList(
        $db,
        "dataDepartment",
        $strDataDepartment,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputSection = getSectionList(
        $db,
        "dataSection",
        $strDataSection,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputSubsection = getSubSectionList(
        $db,
        "dataSubsection",
        $strDataSubsection,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputTransport = getTransportList(
        $db,
        "dataTransport",
        $strDataTransport,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\""
    );
    $strInputGroup = getGroupList(
        $db,
        "dataGroup",
        $strDataGroup,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\""
    );
    // informasi tanggal kehadiran
    $strHari = strtoupper(getDayName($strDataDate));
    $strInfo .= "<br>$strHari, " . strtoupper(pgDateFormat($strDataDate, "d-M-Y"));
    $strHidden .= "<input type=hidden name=dataDate value=\"$strDataDate\">";
    $strHidden .= "<input type=hidden name=dataDivision value=\"$strDataDivision\">";
    $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
    $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
    $strHidden .= "<input type=hidden name=dataSubsection value=\"$strDataSubsection\">";
    $strHidden .= "<input type=hidden name=dataTransport value=\"$strDataTransport\">";
    $strHidden .= "<input type=hidden name=dataGroup value=\"$strDataGroup\">";
}
$strInitAction .= "

    document.formInput.dataDate.focus();
    Calendar.setup({ inputField:\"dataDate\", button:\"btnDate\" });
    checkLateAll();
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>