<?php
include_once('../global/session.php');
include_once('global.php');
include_once('activity.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
include_once('cls_annual_leave.php');
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
$bolPrint = (isset($_REQUEST['btnPrint']));
//---- INISIALISASI ----------------------------------------------------
$strWordsAbsenceData = getWords("absence data");
$strWordsEntryAbsence = getWords("entry absence");
$strWordsAbsenceList = getWords("absence list");
$strWordsAbsenceSlip = getWords("Absence slip");
$strWordsEntryPartialAbsence = getWords("entry partial absence");
$strWordsPartialAbsenceList = getWords("partial absence list");
$strWordsAnnualLeave = getWords("annual leave");
$strWordsAbsenceRequestDate = getWords("absence request date");
$strWordsAbsenceDateFrom = getWords("absence date from");
$strWordsAbsenceDateThru = getWords("absence date thru");
$strWordsAbsenceType = getWords("absence type");
$strWordsEmployeeID = getWords("employee id");
$strWordsNote = getWords("note");
$strWordsStatus = getWords("status");
$strWordsDuration = getWords("duration");
$strWordsLeaveDuration = getWords("leave ");
$strWordsSave = getWords("save");
$strWordsAddNew = getWords("add new");
$strWordsDocument = getWords("document");
//$strSpecialAbsenceCode       = SPECIAL_ABSENCE_CODE;
$strDataDetail = "";
$strButtons = "";
$strMsgClass = "";
$strMessages = "";
$intDefaultWidth = 50;
$intDefaultHeight = 3;
$strNow = date("Y-m-d");
// inisialisasi untuk data array
// $arrData['dataSection'] = "";
$strUserRole = "";
$arrData = [
    "dataDate"          => $strNow,
    "dataDate_"         => date($_SESSION['sessionDateSetting']['php_format']),
    "dataDateFrom"      => date($_SESSION['sessionDateSetting']['php_format']),
    "dataDateThru"      => date($_SESSION['sessionDateSetting']['php_format']),
    "dataEmployee"      => "",
    "dataEmployeeName"  => "",
    "dataSection"       => "",
    "dataType"          => "",
    "dataSpecial"       => "",
    "dataDuration"      => "1",
    "dataLeaveDuration" => "0",
    "dataNote"          => "",
    "dataDoc"           => "",
    //"dataCode" => "ABSEN-HRD",
    //"dataNo" => "",
    //"dataMonth" => "",
    //"dataYear" => "",
    "dataStatus"        => 0,
    "dataID"            => "",
    // untuk keperluan print aja
    "dataDateCreated"   => "",
    "dataDateVerified"  => "",
    "dataDateApproved"  => "",
];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db, $strDataID = "")
{
    global $words;
    global $arrData;
    if ($strDataID != "") {
        $strSQL = "SELECT t1.*, t2.employee_id, t2.id as id_employee, t2.employee_name, ";
        $strSQL .= "t3.section_name FROM hrd_absence AS t1 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
        $strSQL .= "LEFT JOIN hrd_section AS t3 ON t2.section_code = t3.section_code ";
        $strSQL .= "WHERE t1.id = '$strDataID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $arrData['dataEmployee'] = $rowDb['employee_id'];
            $arrData['dataEmployeeName'] = $rowDb['employee_name'];
            $arrData['dataSection'] = $rowDb['section_name'];
            $arrData['dataID'] = $rowDb['id'];
            $arrData['dataType'] = $rowDb['absence_type_code'];
            $arrData['dataSpecial'] = $rowDb['special_type_code'];
            $arrData['dataDuration'] = $rowDb['duration'];
            $arrData['dataLeaveDuration'] = $rowDb['leave_duration'];
            $arrData['dataDate'] = $rowDb['request_date'];
            $arrData['dataDateFromOri'] = sqlToStandarDateNew(
                $rowDb['date_from'],
                $_SESSION['sessionDateSetting']['date_sparator'],
                $_SESSION['sessionDateFormat']
            );
            $arrData['dataDateThruOri'] = sqlToStandarDateNew(
                $rowDb['date_thru'],
                $_SESSION['sessionDateSetting']['date_sparator'],
                $_SESSION['sessionDateFormat']
            );
            $arrData['dataNote'] = $rowDb['note'];
            $arrData['dataDoc'] = $rowDb['doc'];
            $arrData['dataStatus'] = $rowDb['status'];
            //$arrData['dataNo'] = $rowDb['no'];
            //$arrData['dataCode'] = $rowDb['code'];
            //$arrData['dataMonth'] = $rowDb['month_code'];
            //$arrData['dataYear'] = $rowDb['year_code'];
            $arrData['dataDateCreated'] = substr($rowDb['request_date'], 0, 10);
            $arrData['dataDateVerified'] = substr($rowDb['verified_time'], 0, 10);
            $arrData['dataDateApproved'] = substr($rowDb['approved_time'], 0, 10);
        }
    }
    return true;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strDataID, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $error;
    global $messages;
    global $arrData;
    global $arrUserInfo;
    $strError = "";
    $bolOK = true;
    $strToday = date("Y-m-d");
    (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
    (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = "";
    (isset($_REQUEST['dataType'])) ? $strDataType = $_REQUEST['dataType'] : $strDataType = "";
    (isset($_REQUEST['dataSpecial'])) ? $strDataSpecial = $_REQUEST['dataSpecial'] : $strDataSpecial = "";
    (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
    (isset($_REQUEST['dataStatus'])) ? $strDataStatus = $_REQUEST['dataStatus'] : $strDataStatus = "0";
    (isset($_REQUEST['detailDoc'])) ? $detailDoc = $_REQUEST['detailDoc'] : $detailDoc = "";
    //(isset($_REQUEST['dataDateFrom'])) ? $strDataDateFrom = $_REQUEST['dataDateFrom'] : $strDataDateFrom = "";
    //(isset($_REQUEST['dataDateThru'])) ? $strDataDateThru = $_REQUEST['dataDateThru'] : $strDataDateThru = "";
    //uddoin balik format sesuai postgre agar bisa input ke table
    if (isset($_REQUEST['dataDateFrom'])) {
        //$arrDate = explode("-", $_REQUEST['dataDateFrom']);
        //$strDataDateFrom = $arrDate[2] . "-" . $arrDate[1] . "-" . $arrDate[0];
        $strDataDateFrom = standardDateToSQLDateNew(
            $_REQUEST['dataDateFrom'],
            $_SESSION['sessionDateSetting']['date_sparator'],
            $_SESSION['sessionDateSetting']['pos_year'],
            $_SESSION['sessionDateSetting']['pos_month'],
            $_SESSION['sessionDateSetting']['pos_day']
        );
    } else {
        $strDataDateFrom = "";
    }
    if (isset($_REQUEST['dataDateThru'])) {
        //$arrDate = explode("-", $_REQUEST['dataDateThru']);
        //$strDataDateThru = $arrDate[2] . "-" . $arrDate[1] . "-" . $arrDate[0];
        $strDataDateThru = standardDateToSQLDateNew(
            $_REQUEST['dataDateThru'],
            $_SESSION['sessionDateSetting']['date_sparator'],
            $_SESSION['sessionDateSetting']['pos_year'],
            $_SESSION['sessionDateSetting']['pos_month'],
            $_SESSION['sessionDateSetting']['pos_day']
        );
    } else {
        $strDataDateThru = "";
    }
    //die ($strDataDateFrom."--".$strDataDateThru);
    // cek validasi -----------------------
    if ($strDataEmployee == "") {
        $strError = $error['empty_code'];
        $bolOK = false;
    } else if (!validStandardDate($strDataDateFrom)) {
        $strError = $error['invalid_date'];
        $bolOK = false;
    } else if (!validStandardDate($strDataDateThru)) {
        $strError = $error['invalid_date'];
        $bolOK = false;
    } else if (substr_count($strDataNote, "'")) {
        $strError = $error['invalid_text'];
        $bolOK = false;
    }
    // cari dta Employee ID, apakah ada atau tidak
    $arrEmployee = getEmployeeInfoByCode($db, $strDataEmployee, "id, employee_name");
    if (count($arrEmployee) == 0) {
        $strError = $error['employee_data_not_found'];
        $bolOK = false;
    }
    $strIDEmployee = $arrEmployee['id'];
    $strDataDuration = totalWorkDayEmployee($db, $strIDEmployee, $strDataDateFrom, $strDataDateThru);
    $strDataLeaveDuration = 0;
    if (!is_numeric($strDataDuration)) {
        $strError = $error['invalid_number'];
        $bolOK = false;
    }
    // 20151127 uddin
    // validasi dulu karena ada aturan tambahan
    // jika durasi lebih dari 2 hari dan sakit maka cek attachment
    // 20151221 chen
    //ubah menjadi 1 hari
    if ($strDataDuration >= 1 and $strDataType == "SD" and $_FILES["detailDoc"]['name'] == "") {
        $bolOK = false;
        $strError = "Sick with doctor's letter must have attachment";
    }
    if ($strDataDuration >= 1 and ($strDataType == "AKM"
            or $strDataType == "HJI"
            or $strDataType == "BA"
            or $strDataType == "IM"
            or $strDataType == "KA"
            or $strDataType == "KG"
            or $strDataType == "KKM"
            or $strDataType == "LH"
            or $strDataType == "MK"
            or $strDataType == "UMR"
            or $strDataType == "ZI") and $_FILES["detailDoc"]['name'] == ""
    ) {
        $bolOK = false;
        $strError = "Special leave must have attachment";
    }
    if ($strDataDuration > 1 and $strDataType == "TD") {
        $bolOK = false;
        $strError = "Sick without doctor's letter cannot be more than 1 days";
    }
    $strSQL = "SELECT * FROM hrd_absence_partial AS t1 ";
    $strSQL .= "WHERE partial_absence_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' AND id_employee = '$strIDEmployee'";
    $resS = $db->execute($strSQL);
    if ($rowS = $db->fetchrow($resS)) {
        $strError = "The selected date is used for partial absence";
        $bolOK = false;
    }
    $strSQL = "SELECT leave_weight FROM hrd_absence_type ";
    $strSQL .= "WHERE code = '$strDataType'";
    $resS = $db->execute($strSQL);
    if ($rowL = $db->fetchrow($resS)) {
        if ($rowL['leave_weight'] >= 1 || $rowL['leave_weight'] == "") {
            $strSQL = "SELECT * FROM hrd_attendance AS t1 ";
            $strSQL .= "WHERE attendance_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' AND id_employee = '$strIDEmployee'";
            $resS = $db->execute($strSQL);
            if ($rowS = $db->fetchrow($resS)) {
                $strError = "The selected date is not valid, please check attendance data";
                $bolOK = false;
            }
        }
    }
    // jika bukan data baru, cukup update data note
    // revisi : update data from,thru, dan duration juga
    // revisi tambahan : cek overlapping date
    // cek data yang duplikat
    $strSQL = "SELECT id FROM hrd_absence WHERE id_employee = '$strIDEmployee' ";
    $strSQL .= "AND ((date_from, date_thru) ";
    $strSQL .= "    OVERLAPS (DATE '$strDataDateFrom', DATE '$strDataDateThru') ";
    $strSQL .= "    OR (date_thru = DATE '$strDataDateFrom') ";
    $strSQL .= "    OR (date_thru = DATE '$strDataDateThru')) ";
    $strSQL .= " AND STATUS <> " . REQUEST_STATUS_DENIED;
    $resS = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resS)) {
        $strError = $error['overlaping_date_entry'];
        $bolOK = false;
    }
    if ($strDataID != "" && $bolOK) {
        //Update data DateFrom, DateThru, dan Duration
        $strUpdatedDuration = getIntervalDate($strDataDateFrom, $strDataDateThru) + 1;
        $strSQL = "UPDATE hrd_absence ";
        $strSQL .= "SET modified_by = '$_SESSION[sessionUserID]', ";
        $strSQL .= "date_from = '$strDataDateFrom' , ";
        $strSQL .= "date_thru = '$strDataDateThru' , ";
        $strSQL .= "duration = $strUpdatedDuration ";
        $strSQL .= "WHERE id = '$strDataID' ";
        $resExec = $db->execute($strSQL);
        //Selesai Update
        updateNote(
            $db,
            "hrd_absence",
            $strDataID,
            $_SESSION['sessionUserID'],
            $arrEmployee['employee_name'] . " - " . $strDataType . " - " . $strDataDateFrom . " - ",
            $strDataNote,
            $strDataStatus,
            ACTIVITY_EDIT,
            MODULE_EMPLOYEE
        );
        $strError = $messages['data_saved'];
        $bolOK = true;
    }
    $arrShift = [];
    $strSQL = "SELECT *, t2.shift_off FROM hrd_shift_schedule_employee AS t1 ";
    $strSQL .= "LEFT JOIN hrd_shift_type AS t2 ON t1.shift_code = t2.code ";
    $strSQL .= "WHERE shift_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru'";
    $resS = $db->execute($strSQL);
    if ($rowS = $db->fetchrow($resS)) {
        $arrShift[$rowS['id_employee']]['shift_date'] = $rowS;
    }
    $strSQL = "SELECT code, deduct_leave FROM hrd_absence_type ";
    $strSQL .= "WHERE deduct_leave = TRUE AND code = '$strDataType'";
    $resS = $db->execute($strSQL);
    $arrLeave = [];
    if ($rowS = $db->fetchrow($resS)) {
        $intHCM = getSetting("hcm"); //hutang cuti maksimal
        $intJCI = getSetting("jci"); //jatah cuti inisial
        $strDataLeaveDuration = $strDataDuration;
        $objLeave = new clsAnnualLeave($db);
        $tempInfo = $objLeave->arrHistory;
        $objLeave->generateEmployeeAnnualLeave($strIDEmployee);
        $arrCuti = $objLeave->getEmployeeLeaveInfo($strIDEmployee);
        $arrCuti["prev"]["add_taken"] = (isset($tempInfo[$strIDEmployee][$arrCuti["prev"]["year"]]["additional"])) ? $tempInfo[$strIDEmployee][$arrCuti["prev"]["year"]]["additional"] : 0;
        $arrCuti["curr"]["add_taken"] = (isset($tempInfo[$strIDEmployee][$arrCuti["curr"]["year"]]["additional"])) ? $tempInfo[$strIDEmployee][$arrCuti["curr"]["year"]]["additional"] : 0;
        $arrCuti["prev"]["add_quota"] = (isset($tempInfo[$strIDEmployee][$arrCuti["prev"]["year"]]["additional_quota"])) ? $tempInfo[$strIDEmployee][$arrCuti["prev"]["year"]]["additional_quota"] : 0;
        $arrCuti["curr"]["add_quota"] = (isset($tempInfo[$strIDEmployee][$arrCuti["curr"]["year"]]["additional_quota"])) ? $tempInfo[$strIDEmployee][$arrCuti["curr"]["year"]]["additional_quota"] : 0;
        if (dateCompare($strDataDateFrom, $strDataDateThru) > 0 || dateCompare(
                $strDataDateFrom,
                $arrCuti['next']['finish']
            ) > 0 || dateCompare($strDataDateThru, $arrCuti['next']['finish']) > 0 || (dateCompare(
                    $strDataDateFrom,
                    $arrCuti['curr']['finish']
                ) <= 0 && dateCompare($strDataDateThru, $arrCuti['curr']['finish']) > 0)
        ) {
            $strError = $error['invalid_date'];
            $bolOK = false;
        } else if (dateCompare($strDataDateThru, $arrCuti['curr']['finish']) <= 0) {
            if ($arrCuti["prev"]["overdue"] == 't') {
                if ($strDataDuration > ($arrCuti['curr']['remain'] - $arrCuti['curr']['add_taken'] + $arrCuti['curr']['add_quota'])) {
                    $strError = $error['leave_overquota'];
                    $bolOK = false;
                }
            } else {
                if ($strDataDuration > ($arrCuti['curr']['remain'] - $arrCuti['curr']['add_taken'] + $arrCuti['curr']['add_quota']) + ($arrCuti['prev']['remain'] - $arrCuti['prev']['add_taken'] + $arrCuti['prev']['add_quota'])) {
                    $strError = $error['leave_overquota'];
                    $bolOK = false;
                }
            }
        } else if ((dateCompare(
                    $strDataDateThru,
                    $arrCuti['curr']['finish']
                ) <= 0 && $strDataDuration > ($arrCuti['curr']['remain'] - $arrCuti['curr']['add_taken'] + $arrCuti['curr']['add_quota'] + $intHCM)) || (dateCompare(
                    $strDataDateThru,
                    $arrCuti['curr']['finish']
                ) > 0 && $strDataDuration > ($arrCuti['next']['remain'] + $intHCM))
        ) {
            $strError = $error['leave_overquota'];
            $bolOK = false;
        }
    }
    // simpan data -----------------------
    if ($bolOK) { // input OK, tinggal disimpan
        if (!is_numeric($strDataLeaveDuration)) {
            $strDataLeaveDuration = 0;
        }
        if ($strDataLeaveDuration > $strDataDuration) {
            $strDataLeaveDuration = $strDataDuration;
        }
        if ($strDataID == "") {
            // cek data yang duplikat
            $strSQL = "SELECT id FROM hrd_absence WHERE id_employee = '$strIDEmployee' ";
            $strSQL .= "AND ((date_from, date_thru) ";
            $strSQL .= "    OVERLAPS (DATE '$strDataDateFrom', DATE '$strDataDateThru') ";
            $strSQL .= "    OR (date_thru = DATE '$strDataDateFrom') ";
            $strSQL .= "    OR (date_thru = DATE '$strDataDateThru')) ";
            $strSQL .= "AND STATUS <> " . REQUEST_STATUS_DENIED;
            $resS = $db->execute($strSQL);
            if ($rowDb = $db->fetchrow($resS)) {
                $strError = $error['overlaping_date_entry'];
                $bolOK = false;
            }
            if ($bolOK) {
                $strSQL = "INSERT INTO hrd_absence (created, created_by, modified_by, ";
                $strSQL .= "id_employee, request_date, date_from, date_thru, ";
                $strSQL .= "absence_type_code, special_type_code, ";
                $strSQL .= "duration, leave_duration, note,  status) ";
                $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
                $strSQL .= "'$strIDEmployee','$strDataDate', '$strDataDateFrom', '$strDataDateThru', ";
                $strSQL .= "'$strDataType', '$strDataSpecial', ";
                $strSQL .= "'$strDataDuration', '$strDataLeaveDuration', '$strDataNote', ";
                $strSQL .= "$strDataStatus)  ";
                $resExec = $db->execute($strSQL);
                // cari ID
                $strSQL = "SELECT id FROM hrd_absence ";
                $strSQL .= "WHERE id_employee = '$strIDEmployee' AND request_date = '$strDataDate' ";
                $strSQL .= "AND date_from = '$strDataDateFrom' ";
                $strSQL .= "AND date_thru = '$strDataDateThru' ";
                $strSQL .= "ORDER BY id DESC";
                $resDb = $db->execute($strSQL);
                if ($rowDb = $db->fetchrow($resDb)) {
                    $strDataID = $rowDb['id'];
                }
                // simpan data doc, jika ada
                if ($strDataID != "") {
                    //cek jika file kosong
                    if ($_FILES["detailDoc"]['name'] != "") {
                        if (is_uploaded_file($_FILES["detailDoc"]['tmp_name'])) {
                            $arrNamaFile = explode(".", $_FILES["detailDoc"]['name']);
                            $strNamaFile = $strDataID . "_" . $_FILES["detailDoc"]['name'];
                            if (strlen($strNamaFile) > 40) {
                                $strNamaFile = substr($strNamaFile, 0, 40);
                            }
                            $strNamaFile .= "";
                            clearstatcache();
                            if (!is_dir("absencedoc")) {
                                mkdir("absencedoc", 0777);
                            }
                            $strNamaFileLengkap = "absencedoc/" . $strNamaFile;
                            if (file_exists($strNamaFileLengkap)) {
                                unlink($strNamaFileLengkap);
                            }
                            move_uploaded_file($_FILES["detailDoc"]['tmp_name'], $strNamaFileLengkap);
                            // update data
                            $strSQL = "UPDATE hrd_absence SET doc = '$strNamaFile' WHERE id = '$strDataID' ";
                            $resExec = $db->execute($strSQL);
                            // move_uploaded_file($_FILES["detailDoc"]["tmp_name"], "absencedoc/" . $_FILES["detailDoc"]["name"]);
                        }
                    }
                }
                writeLog(
                    ACTIVITY_ADD,
                    MODULE_EMPLOYEE,
                    $arrEmployee['employee_name'] . " - " . $strDataType . " - " . $strDataDateFrom . " - " . $strDataDuration . " days",
                    0
                );
            }
        } else {
            $strSQL = "UPDATE hrd_absence ";
            $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
            $strSQL .= "id_employee = '$strIDEmployee', absence_type_code = '$strDataType', ";
            $strSQL .= "special_type_code = '$strDataSpecial', ";
            $strSQL .= "request_date = '$strDataDate', ";
            $strSQL .= "date_from = '$strDataDateFrom', date_thru = '$strDataDateThru', ";
            $strSQL .= "leave_duration = '$strDataLeaveDuration', ";
            $strSQL .= "note = '$strDataNote', duration = '$strDataDuration' ";
            $strSQL .= "WHERE id = '$strDataID' ";
            $resExec = $db->execute($strSQL);
            writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "ABSENCE DATA", 0);
        }
        // hapus data kehadiran pada saat itu
        $strSQL = "UPDATE hrd_attendance SET is_absence = 't' WHERE id_employee = '$strIDEmployee' ";
        $strSQL .= "AND attendance_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
        $resExec = $db->execute($strSQL);
        if ($bolOK) {
            $strCurrDate = $strDataDateFrom;
            $strSQL = "DELETE FROM hrd_absence_detail WHERE id_absence = '$strDataID' ; ";
            $res = $db->execute($strSQL);
            while (dateCompare($strCurrDate, $strDataDateThru) <= 0) {
                $arrShift = getShiftScheduleByDate($db, $strCurrDate, "", "", $strIDEmployee);
                $arrWorkSchedule = getWorkSchedule($db, $strCurrDate, $strIDEmployee);
                // 1. cek dari shift schedule
                if (isset($arrShift[$strIDEmployee])) {
                    $bolHoliday = ($arrShift[$strIDEmployee]['shift_off'] == "t") ? true : false;
                } // 2. cek dari work schedule
                else if (isset($arrWorkSchedule[$strIDEmployee])) {
                    $bolHoliday = ($arrWorkSchedule[$strIDEmployee]['day_off'] == "t") ? true : isHoliday($strCurrDate);
                } // 2. cek general setting
                else {
                    $bolHoliday = isHoliday($strCurrDate);
                }
                if (!$bolHoliday) //jika bukan hari libur, masukkan datanya
                {
                    $strSQL .= "INSERT INTO hrd_absence_detail (created,modified_by,created_by, id_absence, id_employee, absence_date, absence_type) ";
                    $strSQL .= "VALUES ( now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', '$strDataID', '$strIDEmployee', '$strCurrDate','$strDataType'); ";
                }
                $res = $db->execute($strSQL);
                $strCurrDate = getNextDate($strCurrDate);
            }
            $strError = $messages['data_saved'];
        }
    } else { // ---- data SALAH
        // gunakan data yang diisikan tadi
        $arrData['dataEmployee'] = $strDataEmployee;
        $arrData['dataDate'] = $strDataDate;
        $arrData['dataDateFrom'] = $strDataDateFrom;
        $arrData['dataDateThru'] = $strDataDateThru;
        $arrData['dataType'] = $strDataType;
        $arrData['dataDuration'] = $strDataDuration;
        $arrData['dataNote'] = $strDataNote;
        $arrData['dataDoc'] = $detailDoc;
        $arrData['dataID'] = $strDataID;
        //writeLog(ACTIVITY_EDIT, MODULE_EMPLOYEE, "data not saved - error: ".$strError, 0);
    }
    //einsert
    return $bolOK;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $strUserRole = $_SESSION['sessionUserRole'];
    if (isset($_REQUEST['dataID'])) {
        $bolIsNew = false;
        $strDataID = $_REQUEST['dataID'];
    } else {
        $strDataID = "";
        $bolIsNew = true;
    }
    if ($bolCanEdit) {
        if (isset($_REQUEST['btnSave'])) {
            $bolOK = saveData($db, $strDataID, $strError);
            $closeButton = '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
            if ($bolOK) {
                $successIcon = '<i class="fa fa-exclamation-circle"></i>';
                $strMessages = '<div class="alert alert-info">' . $closeButton . $successIcon . $strError . '</div>';
            } else {
                $errorIcon = '<i class="fa fa-times-circle"></i>';
                $strMessages = '<div class="alert alert-danger">' . $closeButton . $errorIcon . $strError . '</div>';
            }
            $strMsgClass = ($bolOK) ? "class = bgOK" : "class = bgError";
        }
    }
    $dtNow = getdate();
    $arrData['dataMonth'] = getRomans($dtNow['mon']);
    $arrData['dataYear'] = $dtNow['year'];
    $arrData['dataDateFromNow'] = isset($arrData['dataDateFrom']) ? date(
        $_SESSION['sessionDateSetting']['php_format'],
        $arrData['dataDateFrom']
    ) : date($_SESSION['sessionDateSetting']['php_format']);
    $arrData['dataDateThruNow'] = isset($arrData['dataDateThru']) ? date(
        $_SESSION['sessionDateSetting']['php_format'],
        $arrData['dataDateThru']
    ) : date($_SESSION['sessionDateSetting']['php_format']);
    //$strInputLastNo = getLastFormNumber($db, "hrd_absence", "no", $arrData['dataMonth'], $arrData['dataYear']);
    //$intLastNo = ($strInputLastNo == "") ? 0 : (int)$strInputLastNo;
    //$arrData['dataNo'] = addPrevZero($intLastNo + 1,$intFormNumberDigit);
    getData($db, $strDataID);
    //see common_function.php
    $strReadonly = (scopeGeneralDataEntry(
        $arrData['dataEmployee'],
        $_SESSION['sessionUserRole'],
        $arrUserInfo,
        $bolIsNew
    )) ? "readonly" : "";
    // echo "empdata:".$arrData['dataEmployee'];
    $strIDEmployee = getIDEmployee($db, $arrData['dataEmployee']);
    $strEmployeeNIK = $arrData['dataEmployee'];
    if ($strIDEmployee != "") {
        $objLeave = new clsAnnualLeave($db);
        $tempInfo = $objLeave->arrHistory;
        $objLeave->generateEmployeeAnnualLeave($strIDEmployee);
        $arrCuti = $objLeave->getEmployeeLeaveInfo($strIDEmployee);
        $intJCI = getSetting("jci"); //hutang cuti maksimal
        $intHCM = ($arrCuti['curr']['quota'] == $intJCI) ? 0 : getSetting("hcm"); //hutang cuti maksimal
        $arrCuti["prev"]["add_taken"] = (isset($tempInfo[$strIDEmployee][$arrCuti["prev"]["year"]]["additional"])) ? $tempInfo[$strIDEmployee][$arrCuti["prev"]["year"]]["additional"] : 0;
        $arrCuti["curr"]["add_taken"] = (isset($tempInfo[$strIDEmployee][$arrCuti["curr"]["year"]]["additional"])) ? $tempInfo[$strIDEmployee][$arrCuti["curr"]["year"]]["additional"] : 0;
        $arrCuti["prev"]["add_quota"] = (isset($tempInfo[$strIDEmployee][$arrCuti["prev"]["year"]]["additional_quota"])) ? $tempInfo[$strIDEmployee][$arrCuti["prev"]["year"]]["additional_quota"] : 0;
        $arrCuti["curr"]["add_quota"] = (isset($tempInfo[$strIDEmployee][$arrCuti["curr"]["year"]]["additional"])) ? $tempInfo[$strIDEmployee][$arrCuti["curr"]["year"]]["additional_quota"] : 0;
        //$strLeaveDetail = "&nbsp;<br><strong>Leave Detail</strong><br><table border=1><tr><th>Year</th><th>Quota</th><th>Add. Quota</th><th>Adv. Quota</th><th>Holiday</th><th>Taken</th><th>Add. Taken</th><th>Remaining</th></tr>";
        //$strLeaveDetail .= "<tr><td>".$arrCuti['prev']['year']."</td><td>".$arrCuti['prev']['quota']."</td><td>".$arrCuti['prev']['add_quota']."</td><td>$intHCM</td><td>".$arrCuti['prev']['holiday']."</td><td>".$arrCuti['prev']['taken']."</td><td>".$arrCuti['prev']['add_taken']."</td><td>".($arrCuti['prev']['remain']-$arrCuti['prev']['add_taken']+$arrCuti['prev']['add_quota'])."</td></tr>";
        //$strLeaveDetail .= "<tr><td>".$arrCuti['curr']['year']."</td><td>".$arrCuti['curr']['quota']."</td><td>".$arrCuti['curr']['add_quota']."</td><td>$intHCM</td><td>".$arrCuti['curr']['holiday']."</td><td>".$arrCuti['curr']['taken']."</td><td>".$arrCuti['curr']['add_taken']."</td><td>".($arrCuti['curr']['remain']-$arrCuti['curr']['add_taken']+$arrCuti['curr']['add_quota'])."</td></tr></table>";    }
        // uddin additional di tutup
        $strLeaveDetail = "&nbsp;<br><strong>Leave Detail</strong><br><table class=\"table\" ><tr><th>Year</th><th>Quota</th><th>Holiday</th><th>Taken</th><th>Remaining</th></tr>";
        $strLeaveDetail .= "<tr><td>" . $arrCuti['prev']['year'] . "</td><td align=\"center\">" . $arrCuti['prev']['quota'] . "</td><td align=\"center\">" . $arrCuti['prev']['holiday'] . "</td><td align=\"center\">" . $arrCuti['prev']['taken'] . "</td><td align=\"center\">" . ($arrCuti['prev']['remain'] - $arrCuti['prev']['add_taken'] + $arrCuti['prev']['add_quota']) . "</td></tr>";
        $strLeaveDetail .= "<tr><td>" . $arrCuti['curr']['year'] . "</td><td align=\"center\">" . $arrCuti['curr']['quota'] . "</td><td align=\"center\">" . $arrCuti['curr']['holiday'] . "</td><td align=\"center\">" . $arrCuti['curr']['taken'] . "</td><td align=\"center\">" . ($arrCuti['curr']['remain'] - $arrCuti['curr']['add_taken'] + $arrCuti['curr']['add_quota']) . "</td></tr></table>";
    } else {
        $strLeaveDetail = "";
    }
    //----- TAMPILKAN DATA ---------
    //echo "tt".$arrData['dataEmployee'];
    $strInputDate = "<input type=hidden size=15 maxlength=10 name=dataDate id=dataDate value=\"" . $arrData['dataDate'] . "\" >" . $arrData['dataDate_'];
    $strInputDateFrom = "<input class=\"form-control datepicker\" type=text size=15 maxlength=10 name=dataDateFrom id=dataDateFrom value=\"" . $arrData['dataDateFrom'] . "\" data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\">";
    $strInputDateThru = "<input class=\"form-control datepicker\" type=text size=15 maxlength=10 name=dataDateThru id=dataDateThru value=\"" . $arrData['dataDateThru'] . "\" data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\">";
    $strInputEmployee = "<input class=\"form-control\" type=text name=dataEmployee id=dataEmployee size=10 maxlength=30 value=\"" . $strEmployeeNIK . "\" $strReadonly >";
    $strInputDuration = "<input class=\"form-control\" type=text name=dataDuration id=dataDuration size=30 maxlength=10 value=\"" . $arrData['dataDuration'] . "\" readonly class='numeric' >";
    $strInputNote = "<textarea class=\"form-control\" name=dataNote cols=30 rows=3 wrap='virtual' >" . $arrData['dataNote'] . "</textarea>";
    $strSpecial = "";
    $strInputType = getAbsenceTypeList(
        $db,
        "dataType",
        $arrData['dataType'],
        "$strSpecial",
        "",
        " style=\"width:$strDefaultWidthPx\" onChange=\"onAbsenceTypeChange()\""
    );
    $strInputLeaveDuration = "<input class=\"form-control\"  type=text name=dataLeaveDuration id=dataLeaveDuration size=30 maxlength=10 value=\"" . $arrData['dataLeaveDuration'] . "\" disabled class='numeric'>";
    $strInputLeaveDuration .= "<div class=\"checkbox\"><label><input class=\"checkbox-inline\" type='checkbox' name='chkEditLeave' onClick=\"editLeaveDuration()\" title=\"Click here to edit leave duration\"></label></div>";
    $strInputDoc = "<input name=\"detailDoc\" type=\"file\" id=\"detailDoc\" value=\"" . $arrData['dataDoc'] . "\">";
    $strInputStatus = $words[$ARRAY_REQUEST_STATUS[$arrData['dataStatus']]] . generateHidden(
            "dataStatus",
            $arrData['dataStatus']
        );
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = getWords('form entry absence');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = dataAbsenceSubmenu($strWordsEntryAbsence);
if ($bolPrint) {
    $strMainTemplate = getTemplate("absence_edit_print.html");
} else {
    $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
