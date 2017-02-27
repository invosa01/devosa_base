<?php
include_once('../global/session.php');
include_once('global.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
    die(accessDenied($_SERVER['HTTP_REFERER']));
}
//---- INISIALISASI ----------------------------------------------------
$strWordsShiftTypeData = getWords("shift type data");
$strWordsINPUTDATA = getWords("input data");
$strWordsShiftCode = getWords("shift code");
$strWordsStartTime = getWords("start time");
$strWordsFinishTime = getWords("finish time");
$strWordsCODE = getWords("code");
$strWordsSTARTTIME = getWords("start time");
$strWordsFINISHTIME = getWords("finish time");
/* Add Additional Overtime */
$strWordsAddOvertime = getWords("additional OT");
/* End Add Additional Overtime */
$strWordsLISTOFSHIFTTYPE = getWords("list of schedule type");
$strWordsLISTOFSHIFTPATTERN = getWords("list of roster");
$strWordsRoster = getWords("roster");
$strWordsOff = getWords("off");
$strWordsShift = getWords("shift");
$strWordsNote = getWords("note");
$strWordsOverNight = getWords("over night");
$strWordsShiftAllowance = getWords("shift allowance");
$strWordsSave = getWords("save");
$strWordsAddNew = getWords("add new");
$strWordsDelete = getWords("delete");
$strWordsDays = "# " . getWords("days");
$strWordscheckbox = getWords("checkbox");
$strWordsNo = getWords("no.");
$strWordsScheduleForm = getWords("roster setting");
$strDataDetail = "";
$strDataDetail2 = "";
$strInputAllowanceGroup = "";
$strAllowanceGroup = "";
$strAllowance = "";
$intTotalData = 0;
$strDisableApprove = ($_SESSION['sessionUserRole'] == ROLE_ADMIN) ? "" : "disabled";
$strWordsAllowance = getWords("shift allowance");
$strWordsScheduleType = getWords("schedule type");
$strWordsWorkSchedule = getWords("work schedule");
$arrPositionGroup = [];
$strWordsInMinutes = getWords("in minutes");;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data per barisnya aja
function getDataRows($rowDb, $intRows)
{
    global $words;
    global $arrPositionGroup;
    global $strAllowance;
    $strResult = "";
    //if ($rowDb['flag'] == 0) {
    //  $strClass = $strAddChar = "";
    //} else {
    //  $strClass = "class=bgCheckedData";
    //  $strAddChar = ($rowDb['link_id'] == "") ? "" : "&nbsp;&nbsp;";
    //}
    $strAddChar = "";
    $strClass = "";
    $bolOff = ($rowDb['shift_off'] == 't') ? "&radic;" : "";
    $bolShift = ($rowDb['is_shift'] == 't') ? "&radic;" : "";
    $bolOverNight = ($rowDb['over_night'] != 0) ? "&radic;" : "";
    $strResult .= "<tr valign=top $strClass>\n";
    $strResult .= "  <td class=\"center\" nowrap>$strAddChar<div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" type=checkbox id='chkID$intRows' name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></label></div>\n";
    //  $strResult .= "  <input type=hidden disabled name='detailFlag$intRows' value=\"" .$rowDb['flag']. "\"></td>\n";
    $strResult .= "  <td nowrap><input type=hidden name=detailCode$intRows value=\"" . $rowDb['code'] . "\" disabled>" . $rowDb['code'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap align=right><input type=hidden name=detailStart$intRows value=\"" . substr(
            $rowDb['start_time'],
            0,
            5
        ) . "\" disabled>" . substr($rowDb['start_time'], 0, 5) . "&nbsp;</td>";
    $strResult .= "  <td nowrap align=right><input type=hidden name=detailFinish$intRows value=\"" . substr(
            $rowDb['finish_time'],
            0,
            5
        ) . "\" disabled>" . substr($rowDb['finish_time'], 0, 5) . "&nbsp;</td>";
    $strResult .= "  <td align=right><input type=hidden name=detailAdditionalOT$intRows value=\"" . $rowDb['additional_ot'] . "\" disabled>" . $rowDb['additional_ot'] . "&nbsp;</td>";
    $strResult .= "  <td align=right><input type=hidden name=detailIsShift$intRows value=\"" . $rowDb['is_shift'] . "\" disabled>" . $bolShift . "&nbsp;</td>";
    $strResult .= "  <td align=right><input type=hidden name=detailShiftOff$intRows value=\"" . $rowDb['shift_off'] . "\">" . $bolOff . "&nbsp;</td>";
    $strResult .= "  <td align=right><input type=hidden name=detailShiftOverNight$intRows value=\"" . $rowDb['over_night'] . "\">" . $bolOverNight . "&nbsp;</td>";
    $strResult .= "  <td><input type=hidden name=detailNote$intRows value=\"" . $rowDb['note'] . "\" disabled>" . $rowDb['note'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap align=right><input type=hidden name=detailShiftAllowance$intRows value=\"" . $rowDb['shift_allowance'] . "\" disabled>" . $rowDb['shift_allowance'] . "&nbsp;</td>";
    //Shift Allowance untuk masing2 position Group dan Outsource
    /*
    if ($rowDb['shift_allowance'] == "")
    {
      foreach($arrPositionGroup as $strCode)
      {
         $strResult .= "<td align=\"right\"><input type=hidden name=\"detailAllowance$intRows\" value=\"".$rowDb['shift_allowance']."\" disabled>0&nbsp;</td>";
      }
    }
    else
    {
      $arrPositionGroup = split(",",$rowDb['shift_allowance']);
      foreach($arrPositionGroup as $strCode)
      {
         $$strCode = substr($strCode, strpos($strCode, "-") + 1);
         $strResult .= "<td align=\"right\"><input type=hidden name=\"detailAllowance$intRows\" value=\"".$rowDb['shift_allowance']."\" disabled>".number_format($$strCode)."&nbsp;</td>";
      }
    }*/
    $strResult .= "  <td nowrap align=center><a class=\"btn btn-primary btn-xs\" href='javascript:editData($intRows)'>" . $words['edit'] . "</a>&nbsp;</td>";
    $strResult .= "</tr>\n";
    return $strResult;
}

//untuk daftar roster
function getDataRows2($rowDb, $intRows)
{
    global $words;
    $strResult = "";
    $arrDays = split(",", $rowDb['pattern']);
    $strResult .= "<tr valign=top>\n";
    $strResult .= "  <td class=\"center\" nowrap><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" type=checkbox name='chkID2$intRows' id='chkID2$intRows' value=\"" . $rowDb['id'] . "\"></label></div>\n";
    $strResult .= "  <input type=hidden name=detailDays$intRows value=\"" . count($arrDays) . "\" disabled>";
    $strResult .= "  <td class=\"left\" nowrap><input type=hidden name=detailPattern$intRows value=\"" . $rowDb['pattern'] . "\" disabled>" . $rowDb['pattern'] . "&nbsp;</td>";
    $strResult .= "</tr>\n";
    return $strResult;
}

// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    $intRows = 0;
    $strResult = "";
    // cari dulu data temporer yang link IDnya ada
    //
    //$strSQL  = "SELECT * FROM hrd_shift_type WHERE flag <> 0 AND link_id is not null ";
    //$resDb = $db->execute($strSQL);
    //while ($rowDb = $db->fetchrow($resDb)) {
    //  $arrTmp[$rowDb['link_id']] = $rowDb;
    //}
    $strSQL = "SELECT * FROM hrd_shift_type ";
    $strSQL .= "WHERE 1 = 1 $strKriteria ORDER BY $strOrder \"code\" ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= getDataRows($rowDb, $intRows);
        if (isset($arrTmp[$rowDb['id']])) {
            $intRows++;
            $strResult .= getDataRows($arrTmp[$rowDb['id']], $intRows);
        }
    }
    // cari dulu data temporer yang link IDnya ada
    //$strSQL  = "SELECT * FROM hrd_shift_type WHERE flag <> 0 AND link_id is null ";
    //$resDb = $db->execute($strSQL);
    //while ($rowDb = $db->fetchrow($resDb)) {
    //  $intRows++;
    //  $strResult .= getDataRows($rowDb, $intRows);
    //}
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    return $strResult;
} // showData
function getData2($db, &$intRows)
{
    global $words;
    $intRows = 0;
    $strResult = "";
    $strSQL = "SELECT * FROM \"hrd_shift_roster\" ORDER BY pattern";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= getDataRows2($rowDb, $intRows);
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $arrPositionGroup;
    global $error;
    $strError = "";
    // cek ijinnya
    $bolIsManager = ($_SESSION['sessionUserRole'] == ROLE_ADMIN);
    (isset($_REQUEST['dataCode'])) ? $strDataCode = $_REQUEST['dataCode'] : $strDataCode = "";
    (isset($_REQUEST['dataStart'])) ? $strDataStart = $_REQUEST['dataStart'] : $strDataStart = "00:00";
    (isset($_REQUEST['dataFinish'])) ? $strDataFinish = $_REQUEST['dataFinish'] : $strDataFinish = "00:00";
    (isset($_REQUEST['dataBreak'])) ? $strDataBreak = $_REQUEST['dataBreak'] : $strDataBreak = "0";
    (isset($_REQUEST['dataShiftOff'])) ? $strDataShiftOff = "t" : $strDataShiftOff = "f";
    (isset($_REQUEST['dataShiftOverNight'])) ? $strDataShiftOverNight = "1" : $strDataShiftOverNight = "0";
    (isset($_REQUEST['dataIsShift'])) ? $strDataIsShift = "t" : $strDataIsShift = "f";
    (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
    (isset($_REQUEST['dataShiftAllowance'])) ? $strDataShiftAllowance = $_REQUEST['dataShiftAllowance'] : $strDataShiftAllowance = "";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    (isset($_REQUEST['dataFlag'])) ? $strDataFlag = $_REQUEST['dataFlag'] : $strDataFlag = "2";
    (isset($_REQUEST['dataAddOT'])) ? $strDataAddOT = $_REQUEST['dataAddOT'] : $strDataAddOT = "0";
    // cek validasi -----------------------
    if ($strDataCode == "") {
        $strError = $error['empty_code'];
        return false;
    } else {
        ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
        if (isDataExists($db, "hrd_shift_type", "code", $strDataCode, $strKriteria)) {
            $strError = $error['duplicate_code'] . "  -> $strDataCode";
            return false;
        }
    }
    /*
    $strDataAllowance = "";
    foreach($arrPositionGroup as $strCode)
    {
       if (isset($_REQUEST['dataAllowance'.strtoupper($strCode)]))
          $strDataAllowance .= ",".$strCode."-".$_REQUEST['dataAllowance'.strtoupper($strCode)];
       else
          $strDataAllowance .= ",".$strCode."-0";
    }
    $strDataAllowance = substr($strDataAllowance, 1);
    */
    // simpan data -----------------------
    if ($strDataID == "") {
        // data baru
        $strFlag = 0;//($bolIsManager) ? 0 : 2;
        $strSQL = "INSERT INTO hrd_shift_type (created,created_by,modified_by, ";
        $strSQL .= "\"code\",\"start_time\", finish_time, shift_off, is_shift, \"break_duration\", note, shift_allowance, additional_ot, over_night) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "'$strDataCode', '$strDataStart','$strDataFinish', '$strDataShiftOff', '$strDataIsShift', '$strDataBreak', '$strDataNote', $strDataShiftAllowance, $strDataAddOT, $strDataShiftOverNight); ";
        $strSQL .= "INSERT INTO \"hrd_shift_roster\" (currdate,creator,updater, pattern) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "'$strDataCode'); ";
        //echo $strSQL;
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataCode", 0);
    } else {
        /*if (!$bolIsManager) {
          if ($strDataFlag == 0) { // master, bikin temporernnya
            $strDataID = getTempData($db, "hrdShiftType", $strFields, $strDataID,2);
          }
        }
        */
        $strSQL = "UPDATE hrd_shift_type SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "\"code\" = '$strDataCode', shift_allowance = $strDataShiftAllowance, ";
        $strSQL .= "start_time = '$strDataStart', finish_time = '$strDataFinish',  note = '$strDataNote', ";
        $strSQL .= "shift_off = '$strDataShiftOff', is_shift = '$strDataIsShift', ";
        $strSQL .= "over_night = '$strDataShiftOverNight' ";
        $strSQL .= "WHERE id = '$strDataID' ";
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataCode", 0);
    }
    $resExec = $db->execute($strSQL);
    return true;
} // saveData
// fungsi untuk menyimpan data
function saveData2($db, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $error;
    $strError = "";
    $arrShift = [];
    $arrPattern = [];
    $strFields = "currdate, updater, creator, pattern";
    // cek ijinnya
    $bolIsManager = ($_SESSION['sessionUserRole'] == ROLE_ADMIN);
    (isset($_REQUEST['dataDays'])) ? $intDataDays = $_REQUEST['dataDays'] : $intDataDays = "0";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    $strSQL = "SELECT \"code\" FROM \"hrd_shift_type\"";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrShift[] = $rowDb['code'];
    }
    for ($i = 1; $i <= $intDataDays; $i++) {
        if (in_array($_REQUEST['shift_' . $i], $arrShift)) {
            $arrPattern[] = $_REQUEST['shift_' . $i];
        }
    }
    if (count($arrPattern) == 0) {
        $strError = $error['empty_code'];
        return false;
    } else {
        $strPattern = join($arrPattern, ",");
        ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
        if (isDataExists($db, "hrd_shift_roster", "pattern", $strPattern, $strKriteria)) {
            $strError = $error['duplicate_code'] . "  -> $strPattern";
            return false;
        }
    }
    // simpan data -----------------------
    if ($strDataID == "") {
        // data baru
        $strSQL = "INSERT INTO \"hrd_shift_roster\" (currdate,creator,updater, pattern) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "'$strPattern') ";
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strPattern", 0);
    } else {
        $strSQL = "UPDATE \"hrd_shift_roster\" ";
        $strSQL .= "SET updater = '" . $_SESSION['sessionUserID'] . "', \"pattern\" = '$strPattern'"; // jika
        $strSQL .= "WHERE id = '$strDataID' ";
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataCode", 0);
    }
    $resExec = $db->execute($strSQL);
    return true;
} // saveData
// fungsi untuk menghapus data
function deleteData($db)
{
    global $_REQUEST;
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            //$strSQL  = "DELETE FROM hrd_shift_type WHERE link_id = '$strValue'; ";
            $strSQL = "DELETE FROM hrd_shift_type WHERE id = '$strValue'; ";
            $strSQL .= "DELETE FROM \"hrd_shift_roster\" WHERE pattern = '$strValue'; ";
            $resExec = $db->execute($strSQL);
            $i++;
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
    }
} //deleteData
function deleteData2($db)
{
    global $_REQUEST;
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 6) == 'chkID2') {
            $strSQL = "DELETE FROM \"hrd_shift_roster\" WHERE id = '$strValue'; ";
            $resExec = $db->execute($strSQL);
            $i++;
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
    }
} //deleteData
// fungsi untuk approve data oleh manager
function approveData($db)
{
    global $_REQUEST;
    if ($_SESSION['sessionUserRole'] != ROLE_ADMIN) {
        return 0;
    }
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            approveTempData($db, "hrd_shift_type", $strValue);
            $i++;
        }
    }
} //approveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    $i = count($ARRAY_POSITION_GROUP);
    $strSQL = "SELECT company_name FROM hrd_company WHERE is_outsource = true";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrPositionGroup[$i] = $rowDb['company_name'];
        $i++;
    }
    $arrPositionGroup = array_merge($ARRAY_POSITION_GROUP, $arrPositionGroup);
    foreach ($arrPositionGroup AS $strCode) {
        $strInputAllowanceGroup .= "
               <tr>
                  <td nowrap> - &nbsp;$strCode</td>
                  <td>:</td>
                  <td><input name=\"dataAllowance" . strtoupper(
                $strCode
            ) . "\" type=\"text\" id=\"dataAllowance" . strtoupper($strCode) . "\" size=\"20\" maxlength=\"10\" value=\"0\" ></td>
                </tr>";
        $strAllowanceGroup .= "<td>" . getWords($strCode) . "</td>";
    }
    if (isset($_REQUEST['btnSave'])) {
        if ($bolCanEdit) {
            if (!saveData($db, $strError)) {
                echo "<script>alert(\"$strError\")</script>";
            }
        }
    } else if (isset($_REQUEST['btnDelete'])) {
        if ($bolCanDelete) {
            deleteData($db);
        }
    } else if (isset($_REQUEST['btnSave2'])) {
        if ($bolCanEdit) {
            if (!saveData2($db, $strError)) {
                echo "<script>alert(\"$strError\")</script>";
            }
        }
    } else if (isset($_REQUEST['btnDelete2'])) {
        if ($bolCanDelete) {
            deleteData2($db);
        }
    } else if (isset($_REQUEST['btnApprove']) && $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        approveData($db);
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $intTotalData);
        $strDataDetail2 = getData2($db, $intTotalData2);
    } else {
        showError("view_denied");
    }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = getWords('shift type management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = dataShiftTypeSubmenu($strWordsScheduleType);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>