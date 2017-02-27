<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
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
//---- INISIALISASI ----------------------------------------------------
$strModule = "payroll";
$strMessages = "";
$strMsgClass = "";
$arrSetting = [
    "strCompanyName"          => [
        "code"         => "company_name",
        "value"        => "",
        "note"         => "company_name",
        "default"      => "",
        "oldparameter" => "oldCompanyName"
    ],
    "strCompanyCode"          => [
        "code"         => "company_code",
        "value"        => "",
        "note"         => "company code",
        "default"      => "",
        "oldparameter" => "oldCompanyCode"
    ],
    "strCompanyBankAccountNo" => [
        "code"         => "company_account",
        "value"        => "",
        "note"         => "company bank account no",
        "default"      => "",
        "oldparameter" => "oldCompanyBankAccountNo"
    ],
    "strStartTime"            => [
        "code"         => "start_time",
        "value"        => "",
        "note"         => "normal start time to work",
        "default"      => "07:30:00",
        "oldparameter" => "oldStartTime"
    ],
    "strFinishTime"           => [
        "code"         => "finish_time",
        "value"        => "",
        "note"         => "normal finish time to work",
        "default"      => "16:30:00",
        "oldparameter" => "oldFinishTime"
    ],
    "strFridayFinishTime"     => [
        "code"         => "friday_finish_time",
        "value"        => "",
        "note"         => "finish time to work at friday",
        "default"      => "16:45:00",
        "oldparameter" => "oldFridayFinishTime"
    ],
    "strSaturday"             => [
        "code"         => "saturday",
        "value"        => "",
        "note"         => "saturday is holiday or not",
        "default"      => "t",
        "oldparameter" => "oldSaturday"
    ],
    /*"strDeptHead" => array("code" => "department_head", "value" => "", "note" => "code for department head", "default" => "", "oldparameter" => "oldDeptHead"),
    "strGroupHead" => array("code" => "group_head", "value" => "", "note" => "code for group head", "default" => "", "oldparameter" => "oldGroupHead"),*/
    "strSignature"            => [
        "code"         => "signature",
        "value"        => "",
        "note"         => "signature replacement in printout form",
        "default"      => "",
        "oldparameter" => "oldSignature"
    ],
    "rdGrouping"              => [
        "code"         => "grouping",
        "value"        => "",
        "note"         => "shift group selection",
        "default"      => "0",
        "oldparameter" => "oldgrouping"
    ],
    "strMaxOTMember"          => [
        "code"         => "max_ot_member",
        "value"        => "",
        "note"         => "maximum member on an SPL",
        "default"      => "20",
        "oldparameter" => "oldMaxOTMember"
    ],
    /*new by chen*/
    "strMinAutoOT"            => [
        "code"         => "min_auto_ot",
        "value"        => "",
        "note"         => "minimum auto OT",
        "default"      => "3",
        "oldparameter" => "oldMinAutoOT"
    ],
    "strMaxAutoOT"            => [
        "code"         => "max_auto_ot",
        "value"        => "",
        "note"         => "maximum auto OT",
        "default"      => "3",
        "oldparameter" => "oldMaxAutoOT"
    ],
    /*end*/
    "strAttendanceFilePath"   => [
        "code"         => "attendance_file_path",
        "value"        => "",
        "note"         => "path for importing hand key attendance record ",
        "default"      => "",
        "oldparameter" => "oldAttendanceFilePath"
    ],
    "strAttendanceFileType"   => [
        "code"         => "attendance_file_type",
        "value"        => "",
        "note"         => "file type of hand key attendance record",
        "default"      => "",
        "oldparameter" => "oldAttendanceFileType"
    ],
    "strLeaveMethod"          => [
        "code"         => "leave_method",
        "value"        => "0",
        "note"         => "Skema Cuti 0:JoinDate 1:Prorate 2:JoinDate+Cutoff",
        "default"      => "0",
        "oldparameter" => "oldLeaveMethod"
    ],
    "strMBCN"                 => [
        "code"         => "mbcn",
        "value"        => "13",
        "note"         => "Masa Berlaku Cuti Normal",
        "default"      => "13",
        "oldparameter" => "oldMBCN"
    ],
    "strMBCB"                 => [
        "code"         => "mbcb",
        "value"        => "48",
        "note"         => "Masa Berlaku Cuti Besar",
        "default"      => "48",
        "oldparameter" => "oldMBCB"
    ],
    "strHCM"                  => [
        "code"         => "hcm",
        "value"        => "3",
        "note"         => "Hutang Cuti Maksimal",
        "default"      => "3",
        "oldparameter" => "oldHCM"
    ],
    "strJCI"                  => [
        "code"         => "jci",
        "value"        => "4",
        "note"         => "Jatah Cuti Initial",
        "default"      => "4",
        "oldparameter" => "oldJCI"
    ],
    "strPCB"                  => [
        "code"         => "pcb",
        "value"        => "5",
        "note"         => "Periode Cuti Besar",
        "default"      => "5",
        "oldparameter" => "oldPCB"
    ],
    "strJCB"                  => [
        "code"         => "jcb",
        "value"        => "44",
        "note"         => "Jatah Cuti Besar",
        "default"      => "44",
        "oldparameter" => "oldJCB"
    ],
    "strJCN"                  => [
        "code"         => "jcn",
        "value"        => "12",
        "note"         => "Jatah Cuti Normal",
        "default"      => "12",
        "oldparameter" => "oldJCn"
    ],
    'strTaxCalculation' => [
        'code' => 'tax_calculation',
        'value' => '0',
        'note' => '0: cumulative, 1: flat',
        'default' => '0',
        'oldparameter' => 'oldTaxCalculation'
    ]
];
// untuk breakTime, dipisiahkan, karena tabelnya beda
$strBreakNormal = "";
$strBreakFriday = "";
$strBreakHoliday = "";
$strNormalID0 = "";
$strFridayID0 = "";
$strHolidayID0 = "";
$strNormalBreak0 = "";
$strFridayBreak0 = "";
$strHolidayBreak0 = "";
$strNormalDuration0 = "";
$strFridayDuration0 = "";
$strHolidayDuration0 = "";
$strNormalNote0 = "";
$strFridayNote0 = "";
$strHolidayNote0 = "";
$strNormalFinish0 = "";
$strHolidayFinish0 = "";
$strFridayFinish0 = "";
$arrFirstData = [];
$strWordsDays = getWords("days");
$strWordsMonths = getWords("months");
$strWordsYears = getWords("years");
$strWordsCompanyIdentity = getWords("company identity");
$strWordsCompanyName = getWords("company name");
$strWordsCompanyCode = getWords("company code");
$strWordsCompanyBankAccountNo = getWords("company bank account no");
$strWordsDailySetting = getWords("daily setting");
$strWordsStartTime = getWords("start time");
$strWordsFinishTime = getWords("finish time");
$strWordsFridayFinishTime = getWords("friday finish time");
$strWordsSaturdayOff = getWords("saturday off (holiday)");
$strWordsBreakTime = getWords("break time");
$strWordsDayType = getWords("day type");
$strWordsNote = getWords("note");
$strWordsDur = getWords("dur. (min)");
$strWordsNormalDay = getWords("normal day");
$strWordsFriday = getWords("friday");
$strWordsHoliday = getWords("holiday");
$strWordsLeaveAndOvertime = getWords("leave and overtime");
$strWordsInitialLeaveQuota = getWords("initial leave quota");
$strWordsMaximumAdvanceLeave = getWords("maximum advance leave");
$strWordsGrandLeavePeriod = getWords("grand leave period");
$strWordsGrandLeaveQuota = getWords("grand leave quota");
$strWordsLeaveQuota = getWords("leave quota");
$strWordsLeaveAreValidFor = getWords("leave are valid for");
$strWordsGrandLeaveAreValidFor = getWords("grand leave are valid for");
$strWordsMaximumMemberOnSPL = getWords("maximum member on spl");
$strWordsAttendanceImportSetting = getWords("attendance import setting");
$strWordsDateSetting = getWords("date format setting");
$strWordDateFormat = getWords("date format");
$strWordDateNote = getWords("note");
$strWordsLeaveMethod = getWords("leave method");
$strWordsJoinDate = getWords("join date");
$strWordsProrate = getWords("prorate");
$strWordsJoinDateCutoff = getWords("join date with december cutoff");
$strWordsSave = getWords("save");
$strWordsMinimumAutoOT = getWords("minimum auto overtime");
$strWordsMaximumAutoOT = getWords("maximum auto overtime");
$strWordsFilePath = getWords("file path");
$strWordsFileType = getWords("file type");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database
// return berubah $arrOvertime (parameter)
function getData($db)
{
    global $words;
    global $_SESSION;
    global $strModule;
    global $arrSetting;
    $intIDmodified_by = $_SESSION['sessionUserID'];
    $tblSetting = new cModel("all_setting");
    foreach ($arrSetting AS $kode => $arrData) {
        if ($arrData['code'] != "") {
            if ($arrHasil = $tblSetting->findByCode($arrData['code'])) {
                $arrSetting[$kode]["value"] = $arrHasil['value'];
            } else {
                $data = [
                    "code"   => $arrData['code'],
                    "value"  => $arrData['default'],
                    "note"   => $arrData['note'],
                    "module" => $strModule
                ];
                $tblSetting->insert($data);
            }
        }
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    return true;
} // showData
// fungsi untuk mengambil data tambahan jam istirahat
// tipe = 0,1,2
function getDataBreak($db, $tipe)
{
    global $arrFirstData;
    $strDefaultBreak = "00:00";
    $intMaxDetail = 20;
    $strResult = "";
    //inisialisasi
    $arrFirstData[$tipe]['id'] = "";
    $arrFirstData[$tipe]['break'] = $strDefaultBreak;
    $arrFirstData[$tipe]['note'] = "";
    $arrFirstData[$tipe]['finish'] = $strDefaultBreak;
    $arrFirstData[$tipe]['duration'] = 0;
    $strSQL = "SELECT * FROM hrd_break_time WHERE type = '$tipe' ORDER BY start_time ";
    $resDb = $db->execute($strSQL);
    $i = 0;
    while ($rowDb = $db->fetchrow($resDb)) {
        if ($i == 0) {
            $arrFirstData[$tipe]['id'] = $rowDb['id'];
            $arrFirstData[$tipe]['break'] = substr($rowDb['start_time'], 0, 5);
            $arrFirstData[$tipe]['duration'] = $rowDb['duration'];
            $arrFirstData[$tipe]['finish'] = getNextMinute($rowDb['start_time'], $rowDb['duration']);
            $arrFirstData[$tipe]['note'] = $rowDb['note'];
        } else {
            $strResult .= "<tr valign=top id='detailData$tipe" . "_$i'>\n";
            $strResult .= "  <td>&nbsp;</td>\n";
            $strResult .= "  <td>:<input type=hidden name=dataID$tipe" . "_$i value=\"" . $rowDb['id'] . "\"></td>\n";
            $strResult .= "  <td><input class=\"form-control\" type=text name=dataBreak$tipe" . "_$i size=15 maxlength=10 value=\"" . substr(
                    $rowDb['start_time'],
                    0,
                    5
                ) . "\"></td>\n";
            $strResult .= "  <td><input class=\"form-control\" type=text name=dataDuration$tipe" . "_$i size=15 maxlength=10 value=\"" . $rowDb['duration'] . "\"></td>\n";
            $strResult .= "  <td nowrap>&nbsp;" . getNextMinute($rowDb['start_time'], $rowDb['duration']) . "</td>\n";
            $strResult .= "  <td><input class=\"form-control\" type=text name=dataNote$tipe" . "_$i size=30 maxlength=30 value=\"" . $rowDb['note'] . "\"></td>\n";
            $strResult .= "</tr>\n";
        }
        $i++;
    }
    if ($i == 0) {
        $intNumShow = 1;
        $i = 1;
    } else {
        $intNumShow = $i + 1;
    }
    // tambahkan detail tambahan
    while ($i <= $intMaxDetail) {
        $strResult .= "<tr valign=top id='detailData$tipe" . "_$i' style=\"display:none\">\n";
        $strResult .= "  <td>&nbsp;</td>\n";
        $strResult .= "  <td>:</td>\n";
        $strResult .= "  <td><input class=\"form-control\" type=text name=dataBreak$tipe" . "_$i size=15 maxlength=10 value=\"$strDefaultBreak\" disabled></td>\n";
        $strResult .= "  <td><input class=\"form-control\" type=text name=dataDuration$tipe" . "_$i size=15 maxlength=10 value=\"0\" disabled></td>\n";
        $strResult .= "  <td>&nbsp;</td>\n";
        $strResult .= "  <td><input class=\"form-control\" type=text name=dataNote$tipe" . "_$i size=30 maxlength=30 value=\"\" disabled></td>\n";
        $strResult .= "</tr>\n";
        $i++;
    }
    // tambahkan hidden value
    $strResult .= "<input type=hidden name='numShow$tipe' value=$intNumShow>";
    $strResult .= "<input type=hidden name='maxDetail$tipe' value=$intMaxDetail>";
    return $strResult;
}//getDataBreak
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $arrSetting;
    global $arrBreakTime;
    global $messages;
    $strmodified_byID = $_SESSION['sessionUserID'];
    foreach ($arrSetting AS $kode => $arrData) {
        if (isset($_REQUEST[$kode])) {
            $strValue = $_REQUEST[$kode];
            $strSQL = "UPDATE all_setting SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
            $strSQL .= "created = now(), value = '$strValue' ";
            $strSQL .= "WHERE code = '" . $arrData['code'] . "' ";
            $resExec = $db->execute($strSQL);
        }
    }

    /*
    * Eksekusi perubahan date format ke database
    */
    $strIDDateSetting = (isset($_REQUEST['dateSettingFormat'])) ? $_REQUEST['dateSettingFormat'] : $_SESSION['sessionDateSetting']['id'];
    $strSQL2 = "UPDATE date_setting SET active = FALSE ";
    $resExec = $db->execute($strSQL2);
    $strSQL3 = "UPDATE date_setting SET active = TRUE WHERE id = '$strIDDateSetting' ";
    $resExec = $db->execute($strSQL3);

    // simpan dta libur hari sabtu
    $strKode = (isset($_REQUEST['strSaturday'])) ? "t" : "f";
    $strSQL = "UPDATE all_setting SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
    $strSQL .= "created = now(), value = '$strKode' ";
    $strSQL .= "WHERE code = 'saturday' ";
    $resExec = $db->execute($strSQL);
    // simpan jam istirahat
    for ($tipe = 0; $tipe <= 2; $tipe++) {
        $intMax = 20;
        for ($i = 0; $i <= $intMax; $i++) {
            $strID = (isset($_REQUEST['dataID' . $tipe . '_' . $i])) ? $_REQUEST['dataID' . $tipe . '_' . $i] : "";
            $strBreak = (isset($_REQUEST['dataBreak' . $tipe . '_' . $i])) ? $_REQUEST['dataBreak' . $tipe . '_' . $i] : "";
            $strDuration = (isset($_REQUEST['dataDuration' . $tipe . '_' . $i])) ? $_REQUEST['dataDuration' . $tipe . '_' . $i] : "";
            $strNote = (isset($_REQUEST['dataNote' . $tipe . '_' . $i])) ? $_REQUEST['dataNote' . $tipe . '_' . $i] : "";
            if (!is_numeric($strDuration)) {
                $strDuration = 0;
            }
            if ($strBreak == "") { // ada kemungkinan ndihapus
                if ($strID != "") {
                    //hapus data
                    $strSQL = "DELETE FROM hrd_break_time WHERE id = '$strID' ";
                    $resExec = $db->execute($strSQL);
                }
            } else {
                if ($strID == "") { // insert new
                    $strSQL = "INSERT INTO hrd_break_time (created,modified_by,created_by, ";
                    $strSQL .= "\"start_time\",duration, note, type) ";
                    $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', ";
                    $strSQL .= "'$strBreak', '$strDuration', '$strNote', '$tipe') ";
                    $resExec = $db->execute($strSQL);
                } else {//update
                    $strSQL = "UPDATE hrd_break_time SET modified_by = '$strmodified_byID', ";
                    $strSQL .= "\"start_time\" = '$strBreak', duration = '$strDuration', note = '$strNote' ";
                    $strSQL .= "WHERE id = '$strID' ";
                    $resExec = $db->execute($strSQL);
                }
            }
            $strSQL = "DELETE FROM  hrd_break_time WHERE duration = 0";
            $resExec = $db->execute($strSQL);
        }
    }
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "", 0);
    $strError = $messages['data_saved'] . " " . date("d-M-y H:i:s");
    return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    if ($bolCanView) {
        if ($bolCanEdit) {
            if (isset($_REQUEST['btnSave'])) {
                $bolOK = saveData($db, $strError);
                if ($strError != "") {
                    //echo "<script>alert(\"$strError\")</script>";
                    $strMessages = $strError;
                    $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
                }
            }
        }
        getData($db);
        $strBreakNormal = getDataBreak($db, 0);
        $strBreakFriday = getDataBreak($db, 1);
        $strBreakHoliday = getDataBreak($db, 2);
        //tampilkan default baris pertama 0
        $strNormalID0 = $arrFirstData[0]['id'];
        $strFridayID0 = $arrFirstData[1]['id'];
        $strHolidayID0 = $arrFirstData[2]['id'];
        $strNormalBreak0 = $arrFirstData[0]['break'];
        $strFridayBreak0 = $arrFirstData[1]['break'];
        $strHolidayBreak0 = $arrFirstData[2]['break'];
        $strNormalDuration0 = $arrFirstData[0]['duration'];
        $strFridayDuration0 = $arrFirstData[1]['duration'];
        $strHolidayDuration0 = $arrFirstData[2]['duration'];
        $strNormalNote0 = $arrFirstData[0]['note'];
        $strFridayNote0 = $arrFirstData[1]['note'];
        $strHolidayNote0 = $arrFirstData[2]['note'];
        $strNormalFinish0 = $arrFirstData[0]['finish'];
        $strFridayFinish0 = $arrFirstData[1]['finish'];
        $strHolidayFinish0 = $arrFirstData[2]['finish'];
        //$strInputSalaryFrom = getDayList("strSalaryDateFrom",$arrSetting['strSalaryDateFrom']['value']);
        //$strInputSalaryThru = getDayList("strSalaryDateThru",$arrSetting['strSalaryDateThru']['value']);
        //$strInputDeptHead = getPositionList($db, "strDeptHead", $arrSetting['strDeptHead']['value'], $strEmptyOption);
        //$strInputGroupHead = getPositionList($db, "strGroupHead", $arrSetting['strGroupHead']['value'], $strEmptyOption);
        if (isset($arrSetting['rdGrouping']['value'])) {
            $strChecked0 = "";
            $strChecked1 = "";
            if ($arrSetting['rdGrouping']['value'] == 0) {
                $strChecked0 = "checked";
            } else {
                $strChecked1 = "checked";
            }
        }
        $strInputGroupingShift = "<input type='radio' name='rdGrouping' value='0' $strChecked0>Group</input>
                                <br>
                                <input type='radio' name='rdGrouping' value='1' $strChecked1>Section</input>";
        //print_r ($arrSetting['rdGrouping']['value']);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
}
// tampilkan data
$strCompanyName = $arrSetting['strCompanyName']['value'];
$oldCompanyName = $arrSetting['strCompanyName']['value'];
$strCompanyCode = $arrSetting['strCompanyCode']['value'];
$oldCompanyCode = $arrSetting['strCompanyCode']['value'];
$strCompanyBankAccountNo = $arrSetting['strCompanyBankAccountNo']['value'];
$strCompanyBankAccountNo = $arrSetting['strCompanyBankAccountNo']['value'];
$strStartTime = $arrSetting['strStartTime']['value'];
$oldStartTime = $arrSetting['strStartTime']['value'];
$strFinishTime = $arrSetting['strFinishTime']['value'];
$oldFinishTime = $arrSetting['strFinishTime']['value'];
$strFridayFinishTime = $arrSetting['strFridayFinishTime']['value'];
$oldFridayFinishTime = $arrSetting['strFridayFinishTime']['value'];
$strMaxOTMember = $arrSetting['strMaxOTMember']['value'];
$oldMaxOTMember = $arrSetting['strMaxOTMember']['value'];
$strLeaveMethod = $arrSetting['strLeaveMethod']['value'];
$oldLeaveMethod = $arrSetting['strLeaveMethod']['value'];
$strHCM = $arrSetting['strHCM']['value'];
$oldHCM = $arrSetting['strHCM']['value'];
$strJCI = $arrSetting['strJCI']['value'];
$oldJCI = $arrSetting['strJCI']['value'];
$strPCB = $arrSetting['strPCB']['value'];
$oldPCB = $arrSetting['strPCB']['value'];
$strJCB = $arrSetting['strJCB']['value'];
$oldJCB = $arrSetting['strJCB']['value'];
$strJCN = $arrSetting['strJCN']['value'];
$oldJCN = $arrSetting['strJCN']['value'];
$strMBCN = $arrSetting['strMBCN']['value'];
$oldMBCN = $arrSetting['strMBCN']['value'];
$strMBCB = $arrSetting['strMBCB']['value'];
$oldMBCB = $arrSetting['strMBCB']['value'];
$strAttendanceFilePath = $arrSetting['strAttendanceFilePath']['value'];
$oldAttendanceFilePath = $arrSetting['strAttendanceFilePath']['value'];
$strAttendanceFileType = $arrSetting['strAttendanceFileType']['value'];
$oldAttendanceFileType = $arrSetting['strAttendanceFileType']['value'];
$strMinAutoOT = $arrSetting['strMinAutoOT']['value'];
$oldMinAutoOT = $arrSetting['strMinAutoOT']['value'];
$strMaxAutoOT = $arrSetting['strMaxAutoOT']['value'];
$oldMaxAutoOT = $arrSetting['strMaxAutoOT']['value'];
if ($arrSetting['strSaturday']['value'] == 't') {
    $strSaturday = "checked";
    $oldSaturday = "t";
} else {
    $strSaturday = "";
    $oldSaturday = "f";
}
$strSignature = $arrSetting['strSignature']['value'];
$strTaxCalculation = $arrSetting['strTaxCalculation']['value'];
$oldTaxCalculation = $arrSetting['strTaxCalculation']['value'];

$inputDateSettingList = getDateSettingList($db,"dateSettingFormat",$_SESSION['sessionDateSetting']['id'],"","");
//var_dump($inputDateSettingList);
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("manage general setting data");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>