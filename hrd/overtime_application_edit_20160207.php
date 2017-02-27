<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
include_once('overtime_func.php');
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
$bolPrint = (isset($_REQUEST['btnPrint']));
$strTemplateFile = getTemplate("overtimeApplicationEdit.html");
//---- INISIALISASI ----------------------------------------------------
$strWordsOvertimeApplication = getWords("overtime application");
$strWordsDataEntry = getWords("data entry");
$strWordsOvertimeList = getWords("overtime list");
$strWordsHolidayOTApproval = getWords("holiday OT approval");
$strWordsWorkdayOTApproval = getWords("workday OT approval");
$strWordsOvertimeReport = getWords("overtime report");
$strWordsOvertimeDate = getWords("overtime date");
$strWordsEntryDate = getWords("entry date");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("subsection");
$strWordsDepartment = getWords("department");
$strWordsDivision = getWords("division");
$strWordsEmployeeID = getWords("employee id");
$strWordsEmployeeName = getWords("employee name");
$strWordsID = strtoupper("id");
$strWordsEarlyOvertime = getWords("early overtime");
$strWordsAfternoonOvertime = getWords("afternoon overtime");
$strWordsEarlyActual = getWords("early actual");
//$strWordsEarlyAuto           = getWords("early auto");
$strWordsEarlyPlan = getWords("early plan");
$strWordsIsOutdated = getWords("is outdated");
$strWordsSalaryMonth = getWords("salary month");
$strWordsAfternoonActual = getWords("afternoon actual");
//$strWordsAfternoonAuto       = getWords("afternoon auto");
$strWordsAfternoonPlan = getWords("afternoon plan");
$strWordsNote = getWords("note");
$strWordsStatus = getWords("status");
$strWordsAddNew = getWords("add new");
$strWordsSave = getWords("save");
$strWordsEmployeeList = getWords("list of employee");
$strWordsSearchEmployee = getWords("search employee");
$strWordsBreakTime = getWords("break time");
$strWordsDayType = getWords("day type");
$strWordsStartTime = getWords("start time");
$strWordsDurMin = getWords("dur. (min)");
$strWordsFinishTime = getWords("finish time");
$strWordsFriday = getWords("friday");
$strWordsMore = getWords("more");
$strWordsCreate = getWords("save");
$strWordsHoliday = getWords("holiday");
$strWordsAddToList = getWords("add to list");
$strWordsName = getWords("name");
$strWordsDuration = getWords("duration");
$strWordsPlan = getWords("plan");
$strWordsActual = getWords("actual");
$strWordsAttendance = getWords("attendance");
$strWordsHolidayOT = getWords("holiday") . "<br>" . strtoupper("ot");
$strWordsTransport = getWords("transport");
$strWordsTransportFee = getWords("transport fee");
$strWordsPurpose = getWords("note");
$strWordsStart = getWords("start");
$strWordsFinish = getWords("finish");
$strDataDetail = "";
$intDefaultWidth = 25;
$intDefaultWidthPx = 200;
$intDefaultHeight = 3;
$intTotalData = 0;
$arrData = [];
$arrData['dataDetail'] = "";
$strBreakNormal = "";
$strEmployeeList = "";
$strSearchHeader = "<thead><tr class='center' valign='middle' class='tableHeader center'><th width='10'>&nbsp;</th> <th>&nbsp;" . $strWordsID . "&nbsp;</th><th class='center' nowrap width='75%'>&nbsp;" . $strWordsEmployeeName . "&nbsp;</th></tr></thead>";
$strSearchFooter = "<tfoot><tr><th><div class='checkbox no-margin'><label><input name='chkSearchAll' type='checkbox' id='chkSearchAll' value='checkbox' onClick='checkSearchAll();'></th><th><input class='btn btn-primary btn-sm' name='btnSave1' type='button' id='btnAddEmployee' value='" . $strWordsAddToList . "' onClick='addEmployee(document.formInput.dataSearchEmployee.value)'></label></div></th><th nowrap>&nbsp; </th></tr></tfoot>";
$strDataDetail = "<tr valign='middle' class='tableHeader center'>";
$strDataDetail .= "<th width='10' rowspan='2'>&nbsp;</th>";
$strDataDetail .= "<th width='10' rowspan='2'>&nbsp;</th>";
$strDataDetail .= "<th class='center' rowspan='2'>&nbsp;" . $strWordsID . "&nbsp;</th> ";
$strDataDetail .= "<th class='center' nowrap rowspan='2'>&nbsp;" . $strWordsEmployeeName . "&nbsp;</th> ";
//Early Hide by Sanusi 2016-02-01 10:19
//$strDataDetail .= "<th class='center' colspan='2'>&nbsp;".$strWordsEarlyPlan."&nbsp; </th>";
//$strDataDetail .= "<th class='center' colspan='2'>&nbsp;<strong>".$strWordsEarlyActual."</strong>&nbsp; </th>";
$strDataDetail .= "<th class='center' colspan='2'>&nbsp;<strong>" . $strWordsAfternoonActual . "</strong>&nbsp; </th>";
$strDataDetail .= "<th class='center' colspan='2'>&nbsp;" . $strWordsAfternoonPlan . "&nbsp; </th> ";
$strDataDetail .= "<th class='center' colspan='2'>&nbsp;" . $strWordsAttendance . "</th> ";
$strDataDetail .= "<th class='center' rowspan='2'>&nbsp;" . $strWordsHolidayOT . "&nbsp;</th> ";
// $strDataDetail .= "<th class='center' rowspan='2' width='30%'>&nbsp;".$strWordsTransport."&nbsp;</th> ";
// $strDataDetail .= "<th class='center' rowspan='2' width='30%'>&nbsp;".$strWordsTransportFee."&nbsp;</th> ";
$strDataDetail .= "<th class='center' rowspan='2' width='30%'>&nbsp;" . $strWordsPurpose . "&nbsp;</th>";
$strDataDetail .= "</tr> ";
$strDataDetail .= "<tr class='tableHeader'>";
//Early Hide by Sanusi 2016-02-01 10:19
//$strDataDetail .= "<th class='center' valign='middle'>".$strWordsStart."</th> ";
//$strDataDetail .= "<th class='center' valign='middle'>".$strWordsFinish."</th> ";
//$strDataDetail .= "<th class='center' valign='middle'>".$strWordsStart."</th> ";
//$strDataDetail .= "<th class='center' valign='middle'>".$strWordsFinish."</th>";
$strDataDetail .= "<th class='center' valign='middle'>" . $strWordsStart . "</th> ";
$strDataDetail .= "<th class='center' valign='middle'>" . $strWordsFinish . "</th>";
$strDataDetail .= "<th class='center' valign='middle'>" . $strWordsStart . "</th> ";
$strDataDetail .= "<th class='center' valign='middle'>" . $strWordsFinish . "</th>";
$strDataDetail .= "<th class='center' valign='middle'>" . $strWordsStart . "</th> ";
$strDataDetail .= "<th class='center' valign='middle'>" . $strWordsFinish . "</th>";
$strDataDetail .= "</tr>";
//$strDetailFooter = "<tr class='tableHeader'><td>&nbsp;</td><td><input name='chkAll' type='checkbox' id='chkAll' value='checkbox' onClick='checkAll();'></td><td colspan=2 nowrap align='left'>&nbsp;</td><td colspan=2><input name='btnDefaultEarly' type='button' id='btnDefaultEarly' value='Use Default' onClick='checkDefaultEarly();'></td><td colspan=2 nowrap align='left'>&nbsp;</td><td colspan=2><input name='btnDefaultAfternoon' type='button' id='btnDefaultAfternoon' value='Use Default' onClick='checkDefaultAfternoon();'></td><td colspan=9>&nbsp;</td> </tr>";
$strDetailFooter = "<tr class='tableHeader'>";
$strDetailFooter .= "<td>&nbsp;</td>";
$strDetailFooter .= "<td colspan><div class='checkbox no-margin'><label><input class='checkbox-inline' name='chkAll' type='checkbox' id='chkAll' value='checkbox' onClick='checkAll();'></label></div>";
$strDetailFooter .= "<td><input class='btn btn-sm btn-danger' name='btnDelete' type='button' id='btnDelete' value='" . getWords(
        "delete"
    ) . "' onClick='DeleteData();'></td>";
$strDetailFooter .= "<td colspan=2 nowrap align='left'>&nbsp;</td>";
//$strDetailFooter .= "<td colspan=2><input class='btn btn-sm btn-primary' name='btnDefaultEarly' type='button' id='btnDefaultEarly' value='".getWords("use default")."' onClick='checkDefaultEarly();'></td>";
$strDetailFooter .= "<td colspan=2></td>";
$strDetailFooter .= "<td colspan=2 nowrap align='left'>&nbsp;</td>";
//$strDetailFooter .= "<td colspan=2><input class='btn btn-sm btn-info' name='btnDefaultAfternoon' type='button' id='btnDefaultAfternoon' value='".getWords("use default")."' onClick='checkDefaultAfternoon();'></td>";
$strDetailFooter .= "<td colspan=2></td>";
$strDetailFooter .= "<td colspan=9>&nbsp;</td>";
$strDetailFooter .= "</tr>";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db, &$arrData, $strDataID = "")
{
    global $bolCanEdit, $bolCanCheck, $bolCanDelete, $bolCanApprove;
    global $words;
    global $intTotalData;
    global $strEmployeeList;
    global $maxOTMember;
    global $strStatusDisable;
    $bolNewData = true;
    $strStartTime = substr(getSetting("start_time"), 0, 5);
    $strFinishTime = substr(getSetting("finish_time"), 0, 5);
    // die('a');
    if ($strDataID != "") {
        $arrMember = []; // daftar anggota
        // cari dulu daftar anggotanya
        $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, ";
        $strSQL .= "t3.attendance_start, t3.attendance_finish ";
        $strSQL .= "FROM hrd_overtime_application_employee AS t1 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
        $strSQL .= "LEFT JOIN hrd_attendance AS t3 ON t1.id_employee = t3.id_employee AND t1.overtime_date = t3.attendance_date  ";
        $strSQL .= "WHERE t1.id_application = '$strDataID' ";
        $strSQL .= ($bolCanApprove) ? "" : "AND t1.status = " . REQUEST_STATUS_NEW;
        // die($strSQL);
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $arrMember[$rowDb['id_employee']] = $rowDb;
            $strEmployeeList .= $rowDb['employee_id'] . "|";
        }
        // ambil data pokok
        $strSQL = "SELECT * FROM hrd_overtime_application ";
        $strSQL .= "WHERE id = '$strDataID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $bolNewData = false;
            $arrData['dataEntryDate'] = getDateFormat($rowDb['entry_date']);
            $arrData['dataDate'] = getDateFormat($rowDb['overtime_date']);
            $arrData['dataNote'] = $rowDb['note'];
            $arrData['dataStatus'] = $rowDb['status'];
            // $arrData['dataEarly']       =  ($rowDb['include_early_ot'] == "t") ? true : false ;
            // $arrData['dataStartEarly']  = (strtotime($rowDb['start_time_early']>0) ? substr($rowDb['start_time_early'],0,5) : 'null';
            // $arrData['dataFinishEarly'] = (strtotime($rowDb['finish_time_early'])>0) ? substr($rowDb['finish_time_early'],0,5) : 'null';
            $arrData['dataStart'] = substr($rowDb['start_time'], 0, 5);
            $arrData['dataFinish'] = substr($rowDb['finish_time'], 0, 5);
            $strDisabledEarly = ($arrData['dataEarly']) ? "" : "disabled";
            if ($rowDb['is_outdated'] == 't') {
                $arrData['dataIsOutdatedTemp'] = "t";
            } else if ($rowDb['is_outdated'] == 'f') {
                $arrData['dataIsOutdatedTemp'] = "f";
            } else {
                $arrData['dataIsOutdatedTemp'] = "null";
            }
            $arrData['dataSalaryMonth'] = $rowDb['salary_month'];
            $arrData['dataSalaryYear'] = $rowDb['salary_year'];
            $arrData['dataDetail'] = "";
            foreach ($arrMember AS $strIDEmployee => $arrTmp) {
                $intTotalData++;
                $strChecked = (isset($arrTmp['holiday_ot']) && $arrTmp['holiday_ot'] == "t") ? "checked" : "";
                $strDisabledAuto = ($arrTmp['start_auto'] == "" || !$arrData['dataEarly']) ? "disabled" : "";
                $strDetailID = "<input type=hidden name=detailID$intTotalData value='" . $arrTmp['id'] . "'>";
                $arrData['dataDetail'] .= " <tr valign='top' id='row$intTotalData'>";
                $arrData['dataDetail'] .= "  <td nowrap>&nbsp;" . $intTotalData . "&nbsp;</td>";
                //tambahan adnan
                //$arrData['dataDetail'] .= "  <td nowrap>&nbsp;&nbsp;</td>";
                //
                $arrData['dataDetail'] .= "  <td><div class='checkbox no-margin'><label><input class='checkbox-inline' type=checkbox name='chkID$intTotalData' value='" . $arrTmp['id_employee'] . "' checked ></label></div>$strDetailID</td>";
                $arrData['dataDetail'] .= "  <td nowrap>&nbsp;" . $arrTmp['employee_id'] . "&nbsp;</td>";
                $arrData['dataDetail'] .= "  <td nowrap>&nbsp;" . $arrTmp['employee_name'] . "&nbsp;<input type='hidden' name='detailIDEmployee$intTotalData' value='$strIDEmployee'></td>";
                $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type=text name='detailStartEarlyPlan$intTotalData' id='detailStartEarlyPlan$intTotalData' value='" . substr(
                        $arrData['dataStartEarly'],
                        0,
                        5
                    ) . "' size=5 maxlength=5 onChange='checkAttendance($intTotalData)' $strDisabledEarly data-mask=\"99:99\"></td>";
                $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type='text' name='detailFinishEarlyPlan$intTotalData' id='detailFinishEarlyPlan$intTotalData' value='" . substr(
                        $arrData['dataFinishEarly'],
                        0,
                        5
                    ) . "' size=5 maxlength=5 onChange='checkAttendance($intTotalData)' $strDisabledEarly data-mask=\"99:99\"></td>";
                //$arrData['dataDetail'] .= "  <td><input type=text name='detailStartEarlyAuto$intTotalData' id='detailStartEarlyAuto$intTotalData' value='" .substr($arrTmp['start_early_auto'],0,5). "' size=5 maxlength=5 onChange='checkAttendance($intTotalData)' $strDisabledAuto></td>";
                //$arrData['dataDetail'] .= "  <td><input type='text' name='detailFinishEarlyAuto$intTotalData' id='detailFinishEarlyAuto$intTotalData' value='" .substr($arrTmp['finish_early_auto'],0,5). "' size=5 maxlength=5 onChange='checkAttendance($intTotalData)' $strDisabledAuto></td>";
                $arrData['dataDetail'] .= "  <td><strong><input class='form-control-sized' type=text name='detailStartEarlyActual$intTotalData' id='detailStartEarlyActual$intTotalData' value='" . substr(
                        $arrTmp['start_early_actual'],
                        0,
                        5
                    ) . "' size=5 maxlength=5 onChange='checkAttendance($intTotalData)' $strDisabledEarly></strong></td>";
                $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type='text' name='detailFinishEarlyActual$intTotalData' id='detailFinishEarlyActual$intTotalData' value='" . substr(
                        $arrTmp['finish_early_actual'],
                        0,
                        5
                    ) . "' size=5 maxlength=5 onChange='checkAttendance($intTotalData)' $strDisabledEarly></td>";
                $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type='text' name='detailStartActual$intTotalData' id='detailStartActual$intTotalData' value='" . substr(
                        $arrTmp['start_actual'],
                        0,
                        5
                    ) . "' size=5 maxlength=5 onChange='checkAttendance($intTotalData)' readonly></td>";
                $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type='text' name='detailFinishActual$intTotalData' id='detailFinishActual$intTotalData' value='" . substr(
                        $arrTmp['finish_actual'],
                        0,
                        5
                    ) . "' size=5 maxlength=5 onChange='checkAttendance($intTotalData)' readonly></td>";
                //$arrData['dataDetail'] .= "  <td><input type='text' name='detailStartAuto$intTotalData' id='detailStartAuto$intTotalData' value='" .substr($arrTmp['start_auto'],0,5). "' size=5 maxlength=5 onChange='checkAttendance($intTotalData)' $strDisabledAuto></td>";
                //$arrData['dataDetail'] .= "  <td><input type='text' name='detailFinishAuto$intTotalData' id='detailFinishAuto$intTotalData' value='" .substr($arrTmp['finish_auto'],0,5). "' size=5 maxlength=5 onChange='checkAttendance($intTotalData)'  $strDisabledAuto></td>";
                $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type='text' name='detailStartPlan$intTotalData' id='detailStartPlan$intTotalData' value='" . substr(
                        $arrData['dataStart'],
                        0,
                        5
                    ) . "' size=5 maxlength=5 onChange='checkAttendance($intTotalData)' readonly data-mask=\"99:99\"></td>";
                $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type='text' name='detailFinishPlan$intTotalData' id='detailFinishPlan$intTotalData' value='" . substr(
                        $arrData['dataFinish'],
                        0,
                        5
                    ) . "' size=5 maxlength=5 onChange='checkAttendance($intTotalData)' readonly data-mask=\"99:99\"></td>";
                $arrData['dataDetail'] .= "<input type=hidden name='detailFinishActualTemp$intTotalData' id='detailFinishActualTemp$intTotalData' size=5 maxlength=5 value='" . substr(
                        $arrTmp['finish_actual'],
                        0,
                        5
                    ) . "' class='time' disabled>";
                //$arrData['dataDetail'] .= "  <td><input type=text name=detailFinishActual$intTotalData id=detailFinishActual$intTotalData value='" .getIntervalHour($arrTmp['start_actual'], $arrTmp['finish_actual']). "'></td>";
                $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type=text name='detailAttendanceStart$intTotalData' id='detailAttendanceStart$intTotalData' value='" . substr(
                        $arrTmp['attendance_start'],
                        0,
                        5
                    ) . "' size=5 readonly></td>";
                $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type=text name='detailAttendanceFinish$intTotalData' id='detailAttendanceFinish$intTotalData' value='" . substr(
                        $arrTmp['attendance_finish'],
                        0,
                        5
                    ) . "' size=5 readonly></td>";
                $arrData['dataDetail'] .= "  <td class='center'><div class='checkbox no-margin'><label><input class='checkbox-inline' type=checkbox name='chkHoliday$intTotalData' id='chkHoliday$intTotalData' $strChecked strStatusDisable></label></div></td>";
                //  $arrData['dataDetail'] .= "  <td><input  class='form-control' type=text name='detailTransport$intTotalData' id='detailTransport$intTotalData' value='".$arrTmp['transport']."' size=10 maxlength=63></td>";
                //  $arrData['dataDetail'] .= "  <td><input class='form-control' type=text name='detailTransportFee$intTotalData' id='detailTransportFee$intTotalData' value='".$arrTmp['transport_fee']."' size=10 maxlength=15></td>";
                $arrData['dataDetail'] .= "  <td><input class='form-control' type=text name='detailPurpose$intTotalData' id='detailPurpose$intTotalData' value='" . $arrTmp['note'] . "' size=60 maxlength=100></td>";
                $arrData['dataDetail'] .= " </tr>";
            }
        }
    }
    $i = $intTotalData;
    $strStyle = "style='display:none'";
    $strDis = "disabled";
    // tambahkan detail tambahan
    while ($i < $maxOTMember) {
        $i++;
        $arrData['dataDetail'] .= "<tr valign='top' id='row$i' $strStyle>";
        $arrData['dataDetail'] .= "  <td nowrap>&nbsp;" . $i . "&nbsp;</td>";
        //tambahan adnan
        //$arrData['dataDetail'] .= "  <td nowrap>&nbsp;</td>";
        //
        $arrData['dataDetail'] .= "  <td><div class='checkbox no-margin'><label><input class='checkbox-inline' type='checkbox' name='chkID$i' checked $strDis></label></div></td>";
        $arrData['dataDetail'] .= "  <td nowrap id='detailEmployeeID$i' >&nbsp;</td>";
        $arrData['dataDetail'] .= "  <td nowrap id='detailEmployeeName$i' >&nbsp;<input type='hidden' name='detailIDEmployee$i'></td>";
        //Early Plan Hide by Sanusi 2016-02-01 10:19
        //$arrData['dataDetail'] .= "  <td><input class='form-control-sized' type=text name='detailStartEarlyPlan$i' id='detailStartEarlyPlan$i' ' size=5 maxlength=5 onChange='checkAttendance($i)' $strDis></td>";
        //$arrData['dataDetail'] .= "  <td><input class='form-control-sized' type=text name='detailFinishEarlyPlan$i' id='detailFinishEarlyPlan$i'  size=5 maxlength=5 onChange='checkAttendance($i)' $strDis></td>";
        //$arrData['dataDetail'] .= "  <td><input type=text name='detailStartEarlyAuto$i' id='detailStartEarlyAuto$i' ' size=5 maxlength=5 onChange='checkAttendance($i)' $strDis></td>";
        //$arrData['dataDetail'] .= "  <td><input type=text name='detailFinishEarlyAuto$i' id='detailFinishEarlyAuto$i'  size=5 maxlength=5 onChange='checkAttendance($i)' $strDis></td>";
        //Early Actual Hide by Sanusi 2016-02-01 10:19
        //$arrData['dataDetail'] .= "  <td><input class='form-control-sized' type=text name='detailStartEarlyActual$i' id='detailStartEarlyActual$i' ' size=5 maxlength=5 onChange='checkAttendance($i)' $strDis></td>";
        //$arrData['dataDetail'] .= "  <td><input class='form-control-sized' type=text name='detailFinishEarlyActual$i' id='detailFinishEarlyActual$i'  size=5 maxlength=5 onChange='checkAttendance($i)' $strDis></td>";
        $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type=text name='detailStartActual$i' id='detailStartActual$i'  size=5 maxlength=5 onChange='checkAttendance($i)' $strDis></td>";
        $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type=text name='detailFinishActual$i' id='detailFinishActual$i' size=5 maxlength=5 onChange='checkAttendance($i)' $strDis></td>";
        //$arrData['dataDetail'] .= "  <td><input type=text name='detailStartAuto$i' id='detailStartAuto$i'  size=5 maxlength=5 onChange='checkAttendance($i)' $strDis></td>";
        //$arrData['dataDetail'] .= "  <td><input type=text name='detailFinishAuto$i' id='detailFinishAuto$i' size=5 maxlength=5 onChange='checkAttendance($i)' $strDis></td>";
        $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type=text name='detailStartPlan$i' id='detailStartPlan$i'  size=5 maxlength=5 onChange='checkAttendance($i)' $strDis></td>";
        $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type=text name='detailFinishPlan$i' id='detailFinishPlan$i' size=5 maxlength=5 onChange='checkAttendance($i)' $strDis></td>";
        $arrData['dataDetail'] .= "<input type=hidden name=detailFinishActualTemp$i id=detailFinishActualTemp$i size=5 maxlength=5 value='$strFinishTime' class='time' disabled>";
        $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type=text name='detailAttendanceStart$i' id='detailAttendanceStart$i' value='' size=5 $strDis readonly></td>";
        $arrData['dataDetail'] .= "  <td><input class='form-control-sized' type=text name='detailAttendanceFinish$i' id='detailAttendanceFinish$i' value='' size=5 $strDis readonly></td>";
        $arrData['dataDetail'] .= "  <td class='center'><div class='checkbox no-margin'><label><input class='checkbox-inline' type=checkBox name='chkHoliday$i' id='chkHoliday$i' $strDis></label></div></td>";
        //  $arrData['dataDetail'] .= "  <td><input class='form-control' type=text name='detailTransport$i' id='detailTransport$i' size=10 maxlength=63 $strDis></td>";
        //  $arrData['dataDetail'] .= "  <td><input class='form-control' type=text name='detailTransportFee$i' id='detailTransportFee$i' size=10 maxlength=15 $strDis></td>";
        $arrData['dataDetail'] .= "  <td><input class='form-control' type=text name='detailPurpose$i' id='detailPurpose$i' size=50 maxlength=150 $strDis></td>";
        $arrData['dataDetail'] .= " </tr>";
    }
    if ($bolNewData) {
        $arrData['dataEntryDate'] = date($_SESSION['sessionDateSetting']['php_format']);
        $arrData['dataDate'] = date($_SESSION['sessionDateSetting']['php_format']);
        $arrData['dataSection'] = "";
        $arrData['dataSubSection'] = "";
        $arrData['dataGroup'] = "";
        $arrData['dataNote'] = "";
        $arrData['dataStatus'] = "";
        $arrData['dataEarly'] = false;
        $arrData['dataIsOutdated'] = false;
        $arrData['dataIsOutdatedTemp'] = "null";
        $arrData['dataSalaryMonth'] = "";
        $arrData['dataSalaryYear'] = "";
        $arrData['dataStartEarly'] = $strStartTime;
        $arrData['dataFinishEarly'] = $strStartTime;
        $arrData['dataStart'] = $strFinishTime;
        $arrData['dataFinish'] = $strFinishTime;
        $arrData['dataStartEarlyPlan'] = "";
        $arrData['dataFinishEarlyPlan'] = "";
        $arrData['dataStartPlan'] = "";
        $arrData['dataFinishPlan'] = "";
        //$arrData['dataStartEarlyAuto'] = "";
        //$arrData['dataFinishEarlyAuto'] = "";
        //$arrData['dataStartAuto'] = "";
        //$arrData['dataFinishAuto'] = "";
    }
    return true;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strDataID, &$strError)
{
    global $bolCanEdit, $bolCanCheck, $bolCanDelete, $bolCanApprove;
    global $_REQUEST;
    global $HTTP_POST_FILES;
    global $_SESSION;
    global $error;
    global $messages;
    global $maxOTMember;
    $strError = "";
    $strToday = date("Y-m-d");
    $arrDate = explode("/", $_REQUEST['dataEntryDate']);
    $tempDateEntry = $arrDate[2] . "-" . $arrDate[0] . "-" . $arrDate[1];
    $arrDate = explode("/", $_REQUEST['dataDate']);
    $tempDataDate = $arrDate[2] . "-" . $arrDate[0] . "-" . $arrDate[1];
    //$strDataEntryDate  = (isset($_REQUEST['dataEntryDate']))   ? $_REQUEST['dataEntryDate'] : $strTodays ;
    //$strDataDate       = (isset($_REQUEST['dataDate']))        ? $_REQUEST['dataDate']      : "" ;
    $strDataEntryDate = (isset($_REQUEST['dataEntryDate'])) ? $tempDateEntry : $strTodays;
    $strDataDate = (isset($_REQUEST['dataDate'])) ? $tempDataDate : "";
    $strDataNote = (isset($_REQUEST['dataNote'])) ? $_REQUEST['dataNote'] : "";
    $strDataEarly = (isset($_REQUEST['chkEarly'])) ? "t" : "f";
    $strDataIsOutdated = (isset($_REQUEST['dataIsOutdated'])) ? "t" : "f";
    $strStartPlan = (isset($_REQUEST['dataStart'])) ? "'" . $_REQUEST['dataStart'] . "'" : "null";
    $strFinishPlan = (isset($_REQUEST['dataFinish'])) ? "'" . $_REQUEST['dataFinish'] . "'" : "null";
    //jika early nya disabled, disable juga early start dan finish
    if ($strDataEarly == "f") {
        $strStartEarlyPlan = "null";
        $strFinishEarlyPlan = "null";
    } else {
        $strStartEarlyPlan = (isset($_REQUEST['dataStartEarly'])) ? "'" . $_REQUEST['dataStartEarly'] . "'" : "null";
        $strFinishEarlyPlan = (isset($_REQUEST['dataFinishEarly'])) ? "'" . $_REQUEST['dataFinishEarly'] . "'" : "null";
    }
    //jika is outdated (data kadaluwarsa yang dimasukkan ke perhitungan gaji bulan ini atau bulan berikut2nya)
    if ($strDataIsOutdated == "f") {
        $strDataSalaryMonth = "null";
        $strDataSalaryYear = "null";
    } else {
        $strDataSalaryMonth = (isset($_REQUEST['dataSalaryMonth'])) ? $_REQUEST['dataSalaryMonth'] : "null";
        $strDataSalaryYear = (isset($_REQUEST['dataSalaryYear'])) ? $_REQUEST['dataSalaryYear'] : "null";
    }
    if ($strDataDate == "") {
        $strDataDate = $strToday;
    }
    if ($strDataEntryDate == "") {
        $strDataEntryDate = $strToday;
    }
    // cek validasi -----------------------
    if ($strDataDate == "" || $strDataEntryDate == "") {
        $strError = $error['invalid_date'];
        return false;
    }
    //uddin 20160122: validasi khusus untuk wal
    $strIDEmployee = (isset($_REQUEST['chkID1'])) ? $_REQUEST['chkID1'] : "";
    if ($strIDEmployee == "") {
        //continue;
        $strError = "Please Click button \"add to list\" first";
        return false;
    }
    $strSQL = "";
    // simpan data -----------------------
    if ($strDataID == "") {
        if (isDataExists_(
            $db,
            "hrd_overtime_application_employee",
            "id_employee",
            $strIDEmployee,
            "AND overtime_date = '$strDataDate'"
        )) {
            //continue;
            $strError = "Duplicate overtime date";
            return false;
        }
        // end validasi ESS WAL
        $strTmpID = $db->getNextID("hrd_overtime_application_id_seq");
        // data baru
        $strSQL .= "INSERT INTO hrd_overtime_application (id, created, created_by, modified_by, ";
        $strSQL .= "overtime_date, entry_date, note, is_outdated, salary_month, salary_year, ";
        // $strSQL .= "start_time, finish_time, include_early_ot, start_time_early, finish_time_early)";
        $strSQL .= "start_time, finish_time)";
        //$strSQL .= "checked_by, approved_by, status) ";
        $strSQL .= "VALUES('$strTmpID',now(),'" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "'" . $_SESSION['sessionUserID'] . "', '$strDataDate', '$strDataEntryDate', ";
        $strSQL .= "'$strDataNote', '$strDataIsOutdated', $strDataSalaryMonth, $strDataSalaryYear, ";
        // $strSQL .= "$strStartPlan, $strFinishPlan, '$strDataEarly', $strStartEarlyPlan, $strFinishEarlyPlan);";
        $strSQL .= "$strStartPlan, $strFinishPlan);";
        //$strSQL .= "'" .$_SESSION['sessionUserID']. "','" .$_SESSION['sessionUserID']. "',0); ";
        $strDataID = $strTmpID;
        $strError = $messages['data_saved'];
        writeLog(ACTIVITY_ADD, MODULE_EMPLOYEE, "$strDataID", 0);
    } else {
        $strSQL .= "UPDATE hrd_overtime_application SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "overtime_date = '$strDataDate', entry_date = '$strDataEntryDate', ";
        $strSQL .= "is_outdated = '$strDataIsOutdated', salary_month = $strDataSalaryMonth, salary_year = $strDataSalaryYear,";
        $strSQL .= "start_time = $strStartPlan, finish_time = $strFinishPlan, note = '$strDataNote' ";
        // $strSQL .= "start_time_early = $strStartEarlyPlan, finish_time_early = $strFinishEarlyPlan ";
        // $strSQL .= ", include_early_ot = '$strDataEarly' WHERE id = '$strDataID'; ";
        // $strSQL .= " WHERE id = '$strDataID'; ";
        $strSQL .= " WHERE id = '$strDataID'; ";
        $strError = $messages['data_saved'];
        writeLog(ACTIVITY_EDIT, MODULE_EMPLOYEE, "$strDataID", 0);
    }
    // update data jam -jam istirahat, jika ada
    // simpan jam istirahat
    $tipe = 3; //tipe break untuk lembur
    $intMax = 10;
    for ($i = 0; $i <= $intMax; $i++) {
        $strID = (isset($_REQUEST['dataID' . $tipe . '_' . $i])) ? $_REQUEST['dataID' . $tipe . '_' . $i] : "";
        $strBreak = (isset($_REQUEST['dataBreak' . $tipe . '_' . $i])) ? $_REQUEST['dataBreak' . $tipe . '_' . $i] : "";
        $strDuration = (isset($_REQUEST['dataDuration' . $tipe . '_' . $i])) ? $_REQUEST['dataDuration' . $tipe . '_' . $i] : "";
        $strNote = (isset($_REQUEST['dataNote' . $tipe . '_' . $i])) ? $_REQUEST['dataNote' . $tipe . '_' . $i] : "";
        if (!is_numeric($strDuration)) {
            $strDuration = 0;
        }
        if ($strBreak == "" || $strDuration == 0) { // ada kemungkinan ndihapus
            if ($strID != "") {
                //hapus data
                $strSQL .= "DELETE FROM hrd_break_time WHERE id = '$strID'; ";
            }
        } else {
            if ($strID == "") { // insert new
                $strSQL .= "INSERT INTO hrd_break_time (created, ";
                $strSQL .= "start_time,duration, note, type, flag, link_id) ";
                $strSQL .= "VALUES(now(), ";
                $strSQL .= "'$strBreak', '$strDuration', '$strNote', '$tipe', 2, '$strDataID'); ";
            } else {//update
                $strSQL .= "UPDATE hrd_break_time SET ";
                $strSQL .= "start_time = '$strBreak', duration = '$strDuration', note = '$strNote' ";
                $strSQL .= "WHERE id = '$strID'; ";
            }
        }
    }
    // get Break Data
    $strSQL1 = "SELECT type, flag, link_id ";
    $strSQL1 .= "FROM hrd_break_time WHERE flag = '2' AND type = '3' AND link_id = '$strDataID'";
    $resS = $db->execute($strSQL1);
    if ($rowS = $db->fetchrow($resS)) {
        $intBreakFlag = 2;
        $intBreakType = 3;
        $intBreakLinkID = $strDataID;
    } else {
        $intBreakFlag = 0;
        $intBreakType = "";
        $intBreakLinkID = "";
    }
    // handle data peserta/employee dari pilihan
    (isset($_REQUEST['totalData'])) ? $intTotal = $_REQUEST['totalData'] : $intTotal = 0;
    $intTotal = ($intTotal < $maxOTMember) ? $intTotal : $maxOTMember;
    // delete semua detail
    $strSQL .= "DELETE FROM hrd_overtime_application_employee WHERE id_application = '$strDataID' ";
    $strSQL .= ($bolCanApprove) ? "" : "AND status = " . REQUEST_STATUS_NEW;
    $resExec = $db->execute($strSQL);
    $strSQL = "";
    for ($i = 1; $i <= $intTotal; $i++) {
        $strIDEmployee = (isset($_REQUEST['chkID' . $i])) ? $_REQUEST['chkID' . $i] : "";
        if ($strIDEmployee == "") {
            continue;
        }
        if (isDataExists_(
            $db,
            "hrd_overtime_application_employee",
            "id_employee",
            $strIDEmployee,
            "AND overtime_date = '$strDataDate'"
        )) {
            //continue;
            $strError = "Duplicate overtime date";
            return false;
        }
        //$strStartAuto     = (isset($_REQUEST['detailStartAuto'.$i]) && $_REQUEST['detailStartAuto'.$i] != "")  ? $_REQUEST['detailStartAuto'.$i] : "";
        //$strFinishAuto    = (isset($_REQUEST['detailFinishAuto'.$i]) && $_REQUEST['detailFinishAuto'.$i] != "") ? $_REQUEST['detailFinishAuto'.$i] :  "";
        $strStartActual = (isset($_REQUEST['detailStartActual' . $i]) && $_REQUEST['detailStartActual' . $i] != "") ? $_REQUEST['detailStartActual' . $i] : "";
        $strFinishActual = (isset($_REQUEST['detailFinishActual' . $i]) && $_REQUEST['detailFinishActual' . $i] != "") ? $_REQUEST['detailFinishActual' . $i] : "";
        if (timeCompare($strStartActual, $strFinishActual) > 0) {
            $strStartActual = $strFinishActual = "";
        }
        if ($strDataEarly == "f") {
            $strStartEarlyActual = "null";
            $strFinishEarlyActual = "null";
            //$strStartEarlyAuto     = "null";
            //$strFinishEarlyAuto    = "null";
            $intDurationEarly = 0;
        } else {
            //$strStartEarlyAuto  = (isset($_REQUEST['detailStartEarlyAuto'.$i]) && $_REQUEST['detailStartEarlyAuto'.$i] != "" ) ? "'".$_REQUEST['detailStartEarlyAuto'.$i]."'"  : "null";
            //$strFinishEarlyAuto = (isset($_REQUEST['detailFinishEarlyAuto'.$i]) && $_REQUEST['detailFinishEarlyAuto'.$i] != "") ? "'".$_REQUEST['detailFinishEarlyAuto'.$i]."'" :  "null";
            $strStartEarlyActual = (isset($_REQUEST['detailStartEarlyActual' . $i]) && $_REQUEST['detailStartEarlyActual' . $i] != "") ? "'" . $_REQUEST['detailStartEarlyActual' . $i] . "'" : "null";
            $strFinishEarlyActual = (isset($_REQUEST['detailFinishEarlyActual' . $i]) && $_REQUEST['detailFinishEarlyActual' . $i] != "") ? "'" . $_REQUEST['detailFinishEarlyActual' . $i] . "'" : "null";
            if ($strStartEarlyActual == "null" || $strFinishEarlyActual == "null") {
                $intDurationEarly = 0;
            } else {
                $intDurationEarly = getIntervalHour(
                    $_REQUEST['detailStartEarlyActual' . $i],
                    $_REQUEST['detailFinishEarlyActual' . $i]
                );
            }
        }
        //$strStartAuto     = ($strStartAuto    != "") ? "'$strStartAuto'"     : "null";
        //$strFinishAuto    = ($strFinishAuto   != "") ? "'$strFinishAuto'"    : "null";
        $strStartActual = ($strStartActual != "") ? "'$strStartActual'" : "null";
        $strFinishActual = ($strFinishActual != "") ? "'$strFinishActual'" : "null";
        $strHoliday = (isset($_REQUEST['chkHoliday' . $i])) ? "t" : "f";
        $strTransport = (isset($_REQUEST['detailTransport' . $i])) ? $_REQUEST['detailTransport' . $i] : "";
        //
        $strTransportFee = (isset($_REQUEST['detailTransportFee' . $i]) && is_numeric(
                $_REQUEST['detailTransportFee' . $i]
            )) ? $_REQUEST['detailTransportFee' . $i] : 0;
        $strPurpose = (isset($_REQUEST['detailPurpose' . $i])) ? $_REQUEST['detailPurpose' . $i] : "";
        $bolHoliday = ($strHoliday == "t");
        $intHoliday = ($strHoliday == "t") ? 1 : 0;
        if ($strStartActual == "null" || $strFinishActual == "null") {
            $arrOT = ["l1" => 0, "l2" => 0, "l3" => 0, "l4" => 0, "l5", "total" => 0];
        } else {
            $arrOT = calculateOvertimeByOTSchedule(
                $db,
                $strDataDate,
                $_REQUEST['detailStartActual' . $i],
                $_REQUEST['detailFinishActual' . $i],
                $intDurationEarly,
                $bolHoliday,
                $intBreakType,
                true,
                $intBreakFlag,
                $intBreakLinkID
            );
        }
        if ($strIDEmployee != "") {
            // insert data baru
            $userID = $_SESSION['sessionUserID'];
            $strSQL = "INSERT INTO hrd_overtime_application_employee ";
            $strSQL .= "(created, modified_by, created_by, id_application, id_employee, note, ";
            //  $strSQL .= "start_early_plan, finish_early_plan, start_early_actual, finish_early_actual, ";
            //$strSQL .= "start_early_auto, finish_early_auto, start_auto, finish_auto, ";
            $strSQL .= "start_plan, finish_plan, start_actual, finish_actual, holiday_ot, ";
            $strSQL .= "l1, l2, l3, l4, early_ot, total_time, overtime_date, entry_date, transport, transport_fee,";
            $strSQL .= "checked_time,approved_time,acknowledged_time,acknowledged_by) ";
            $strSQL .= "VALUES(now(), $userID, $userID, ";
            //$strSQL .= "'$strDataID', '$strIDEmployee', '$strPurpose', ";
            // yang disimpan pada note adalah note yg diisi di header
            $strSQL .= "'$strDataID', '$strIDEmployee', '$strDataNote', ";
            //  $strSQL .= "$strStartEarlyPlan, $strFinishEarlyPlan, $strStartEarlyActual, $strFinishEarlyActual, ";
            //$strSQL .= "$strStartEarlyAuto, $strFinishEarlyAuto, $strStartAuto, $strFinishAuto, ";
            $strSQL .= "$strStartPlan, $strFinishPlan, $strStartActual, $strFinishActual, '$strHoliday', ";
            $strSQL .= "'" . $arrOT['l1'] . "', '" . $arrOT['l2'] . "', '" . $arrOT['l3'] . "', '" . $arrOT['l4'] . "', ";
            $strSQL .= "'" . $intDurationEarly . "', '" . $arrOT['total'] . "', '$strDataDate', '$strDataEntryDate', '$strTransport', $strTransportFee,";
            $strSQL .= "now(), now(), now(), $userID); ";
            /* Email Process */
            $strBody .= "ESS Notification - Email notifikasi untuk employee berikut:" . "\n" . "\n";
            $strBody .= "____________________________________________________________________________________" . "\n";
            $strBody .= "Name: " . getEmployeeName($db, $strIDEmployee) . "\n";
            $strBody .= "Date: " . $strDataDate . "\n";
            $strStartEarlyPlanEmail = ($strStartEarlyPlan == null) ? " " : " ";
            $strFinishEarlyPlanEmail = ($strFinishEarlyPlan == null) ? " " : " ";
            $strBody .= "Afternoon Early: " . $strStartEarlyPlanEmail . " - " . $strFinishEarlyPlanEmail . "\n";
            $strBody .= "Afternoon Plan: " . $strStartPlan . " - " . $strFinishPlan . "\n";
            $strBody .= "Note: " . $strNote . "\n";
            $strBody .= "Total Time: " . $arrOT['total'] . " hour(s) \n";
            $strBody .= "____________________________________________________________________________________" . "\n";
            $strBody .= "Harap segera APPROVE/DENIED Pengajuan OVERTIME Bawahan Anda melalui Sistem ESS ( http://hr.wanaarthalife.com/devosa/ ). " . "\n";
            $strBody = getBodyEmail(0, 'Overtime', $strBody, $_SESSION['sessionUserID']);
            $strSubject = getSubjectEmail(0, 'Overtime', getEmployeeCode($db, $strIDEmployee));
            $headData = getHeadEmployeeData($db, $strIDEmployee);
            if (!empty($headData) && !empty($headData['email'])) {
                sendMail($headData['email'], $strSubject, $strBody, getEmployeeEmail($db, $strIDEmployee));
            }
            /* End Email Proses */
            $strSQL .= "UPDATE hrd_attendance ";
            $strSQL .= "SET ";
            $strSQL .= "overtime_start = $strStartActual, overtime_finish = $strFinishActual, ";
            //  $strSQL .= "overtime_start_early = $strStartEarlyActual, overtime_finish_early = $strFinishEarlyActual, ";
            //$strSQL .= "overtime_start_auto = $strStartAuto, overtime_finish_auto = $strFinishAuto, ";
            //$strSQL .= "overtime_start_early_auto = $strStartEarlyAuto, overtime_finish_early_auto = $strFinishEarlyAuto, ";
            $strSQL .= "is_overtime = 't',  early_overtime = '$intDurationEarly', ";
            $strSQL .= "l1 = '" . $arrOT['l1'] . "', l2 = '" . $arrOT['l2'] . "', ";
            $strSQL .= "l3 = '" . $arrOT['l3'] . "', l4 = '" . $arrOT['l4'] . "', ";
            $strSQL .= "overtime = '" . $arrOT['total'] . "', holiday = $intHoliday ";
            $strSQL .= "WHERE id_employee = '$strIDEmployee' AND attendance_date = '$strDataDate'; ";
        } else {
            $strSQL .= "DELETE FROM hrd_overtime_application_employee ";
            $strSQL .= "WHERE id_employee = '$strIDEmployee' AND overtime_date = '$strDataDate'; ";
        }
        $resExec = $db->execute($strSQL);
        syncOvertimeApplication($db, $strDataDate, $strDataDate, $strIDEmployee);
    }
    if ($strDataID == "") {
        $strSQL = "SELECT MAX(id) as max_id FROM hrd_overtime_application";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strDataID = $rowDb['max_id'];
        }
    }
    return true;
} // saveData
// fungsi menambahkan 1 employee sebagai peserta
function addEmployee($db, $strList, $strOldList, $strDate, $intTotalData)
{
    $arrData = [];
    $arrTemp = [];
    $strResult = "";
    $strNormalStart = substr(getSetting("start_time"), 0, 5);
    $strNormalFinish = substr(getSetting("finish_time"), 0, 5);
    //$strStartEarlyTimeAuto  = getNextMinute($strNormalStart, -60);
    //$strFinishTimeAuto      = getNextMinute($strNormalFinish, 60);
    if ($strList == "") {
        return "$intTotalData~$strOldList~";
    }
    $arrData = explode("|", $strList);
    $arrTemp = explode("|", $strOldList);
    // cek employee yang sudah ada di fieldset list of employee supaya tidak duplikat
    foreach ($arrData as $strKey => $strNew) {
        if ($strNew == "") {
            continue;
        }
        if (in_array($strNew, $arrTemp)) {
            $strList = str_replace($strNew, "", $strList);
        }
    }
    // update list employee yang ada di fieldset list of employee
    $strOldList .= "|" . $strList;
    //$strOldList = str_replace("||", "|", $strOldList);
    if ($strList == "") {
        return "$intTotalData~$strOldList~";
    }
    // ambil data yang diperlukan dari list emlpoyee baru
    $arrEmp = getInfo($db, $strList, $strDate);
    // generate inner html
    foreach ($arrEmp as $strID => $arrData) {
        if (is_numeric($strID)) {
            $intTotalData++;
            $strNormalStart = (isset($arrData['normal_start'])) ? substr(
                $arrData['normal_start'],
                0,
                5
            ) : $strNormalStart;
            $strNormalFinish = (isset($arrData['normal_finish'])) ? substr(
                $arrData['normal_finish'],
                0,
                5
            ) : $strNormalFinish;
            $strAttStart = (isset($arrData['attendance_start'])) ? substr($arrData['attendance_start'], 0, 5) : "";
            $strAttFinish = (isset($arrData['attendance_finish'])) ? substr($arrData['attendance_finish'], 0, 5) : "";
            $strHoliday = (isset($arrData['holiday'])) ? $arrData['holiday'] : "f";
            $strResult .= $arrData['id_employee'] . ">";        //0
            $strResult .= $arrData['employee_id'] . ">";        //1
            $strResult .= $arrData['employee_name'] . ">";     //2
            $strResult .= $arrData['get_auto_ot'] . ">";       //3
            $strResult .= $strNormalStart . ">";               //4
            $strResult .= $strNormalFinish . ">";              //5
            $strResult .= $strAttStart . ">";                  //6
            $strResult .= $strAttFinish . ">";                 //7
            $strResult .= /*$strStartEarlyTimeAuto*/
                " >";     //8
            $strResult .= /*$strFinishTimeAuto*/
                " >";        //9
            $strResult .= $strHoliday . ">";                  //10
            $strResult .= $arrData['transport'] . ">";        //11
            $strResult .= $arrData['transport_fee'] . ">|";   //12
        }
    }
    return "$intTotalData~$strOldList~" . $strResult;
}//addEmployee
function getInfo($db, &$strListID, $strDate)
{
    $arrDate = explode("/", $strDate);
    $strDate = $arrDate[2] . "-" . $arrDate[0] . "-" . $arrDate[1];
    if ($strListID == "") {
        return "";
    }
    $arrEmpID = explode("|", $strListID);
    $strListID = "";
    foreach ($arrEmpID as $strID) {
        if ($strID == "") {
            continue;
        }
        $strListID .= "'" . getIDEmployee($db, $strID) . "',";
    }
    $strListID = substr($strListID, 0, -1);
    $arrResult = [];
    $strResult = "";
    if ($strListID == "") {
        return $arrResult;
    }
    // get Employee Data
    $strSQL = "SELECT id as id_employee, employee_id, employee_name, get_auto_ot, transport , transport_fee ";
    $strSQL .= "FROM hrd_employee as t1 LEFT JOIN hrd_position as t2 ";
    $strSQL .= "ON t1.position_code = t2.position_code WHERE id IN ($strListID)";
    $resS = $db->execute($strSQL);
    while ($rowS = $db->fetchrow($resS)) {
        $arrResult[$rowS['id_employee']] = $rowS;
    }
    // get Attendance Data
    $strSQL = "SELECT id_employee, normal_start, normal_finish,  attendance_start, attendance_finish ";
    $strSQL .= "FROM hrd_attendance WHERE attendance_date = '$strDate' AND id_employee IN ($strListID)";
    $resS = $db->execute($strSQL);
    while ($rowS = $db->fetchrow($resS)) {
        $arrResult[$rowS['id_employee']]['normal_start'] = $rowS['normal_start'];
        $arrResult[$rowS['id_employee']]['normal_finish'] = $rowS['normal_finish'];
        $arrResult[$rowS['id_employee']]['attendance_start'] = $rowS['attendance_start'];
        $arrResult[$rowS['id_employee']]['attendance_finish'] = $rowS['attendance_finish'];
    }
    // get Shift Data
    $strSQL = "SELECT id_employee, shift_code, shift_off FROM hrd_shift_schedule_employee AS t1 ";
    $strSQL .= "LEFT JOIN hrd_shift_type AS t2 ON t1.shift_code = t2.code ";
    $strSQL .= "WHERE shift_date = '$strDate' AND id_employee IN ($strListID)";
    $resS = $db->execute($strSQL);
    while ($rowS = $db->fetchrow($resS)) {
        //  $arrResult[$rowS['id_employee']]['shift_code']  = $rowS['shift_code'];
        //standard, prioritas dari jadwal shift
        //  $arrResult[$rowS['id_employee']]['holiday']     = ($rowS['shift_off'] == "t") ? "t" : "f";
        //kasus takaful, prioritas working calendar
        $arrResult[$rowS['id_employee']]['holiday'] = ($rowS['shift_off'] == "t" || isHoliday($strDate)) ? "t" : "f";
    }
    $arrEmpID = explode(",", str_replace("'", "", $strListID));
    // cek holiday
    foreach ($arrEmpID as $strEmpID) {
        //  if (!isset($arrResult[$strEmpID]['holiday']))
        //  {
        if (isHoliday($strDate)) {
            $arrResult[$strEmpID]['holiday'] = "t";
        } else {
            $arrResult[$strEmpID]['holiday'] = "f";
        }
        //  }
    }
    return $arrResult;
}

// fungsi menampilkan daftar employee
function searchEmployee($db, $strCategory, $strKey)
{
    $strResult = "";
    $strSQL = "SELECT id, employee_id, employee_name FROM hrd_employee as A1 LEFT JOIN hrd_position as A2 ON A1.position_code=A2.position_code ";
    $strSQL .= "WHERE " . $strCategory . "_code = '$strKey' AND A2.get_ot!=0 AND active=1";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strResult .= $rowDb['id'] . "," . $rowDb['employee_id'] . "," . $rowDb['employee_name'] . "|";
    }
    return $strResult;
} // searchEmployee
// fungsi untuk mengambil data tambahan jam istirahat
// tipe = 0,1,2
function getDataBreak($db, $tipe, $strDataID)
{
    global $arrFirstData;
    $strDefaultBreak = "00:00";
    $intMaxDetail = 5;
    $strResult = "";
    //inisialisasi
    $arrFirstData[$tipe]['id'] = "";
    $arrFirstData[$tipe]['break'] = $strDefaultBreak;
    $arrFirstData[$tipe]['note'] = "";
    $arrFirstData[$tipe]['finish'] = $strDefaultBreak;
    $arrFirstData[$tipe]['duration'] = 0;
    $strKriteria = ($strDataID == "") ? "AND 1=2 " : "AND link_id = '$strDataID' ";
    $strSQL = "
      SELECT * FROM hrd_break_time
      WHERE type = '$tipe'
        AND (flag = 2) -- jenis overtime
        $strKriteria
      ORDER BY start_time
    ";
    $resDb = $db->execute($strSQL);
    $i = 0;
    while ($rowDb = $db->fetchrow($resDb)) {
        $strResult .= "<tr valign=top id='detailData$tipe" . "_$i'>\n";
        $strResult .= "  <td><input type=hidden name=dataID$tipe" . "_$i value=" . $rowDb['id'] . "><input class='form-control' type=text name=dataBreak$tipe" . "_$i size=12 maxlength=10 value=" . substr(
                $rowDb['start_time'],
                0,
                5
            ) . " data-mask=\"99:99\"></td>\n";
        $strResult .= "  <td><input class='form-control' type=text name=\"dataDuration$tipe" . "_$i\" size=12 maxlength=10 value=" . $rowDb['duration'] . "></td>\n";
        $strResult .= "  <td nowrap>&nbsp;" . getNextMinute($rowDb['start_time'], $rowDb['duration']) . "</td>\n";
        $strResult .= "  <td><input class='form-control-sized' type=text name=dataNote$tipe" . "_$i size=50 maxlength=50 value=\"" . $rowDb['note'] . "\"></td>\n";
        $strResult .= "</tr>\n";
        $i++;
    }
    if ($i == 0 && getSetting("ot_auto_break") == 't') {
        $strStartBreak = "";
        $strDurationBreak = "";
        $strEndBreak = "";
        $NameHari = getDayName($otDay);
        $resDbBreak;
        if ($NameHari == "Friday") {
            $sqlBreak = "SELECT * FROM hrd_break_time WHERE type = 1 ORDER BY start_time";
            $resDbBreak = $db->execute($sqlBreak);
        } else if ($NameHari == "Sunday" || $NameHari == "Saturday") {
            if ($NameHari == "Saturday" && getSetting('saturday') == 'f') {
                $sqlBreak = "SELECT * FROM hrd_break_time WHERE type = 0 ORDER BY start_time";
                $resDbBreak = $db->execute($sqlBreak);
            } else {
                $sqlBreak = "SELECT * FROM hrd_break_time WHERE type = 2 ORDER BY start_time";
                $resDbBreak = $db->execute($sqlBreak);
            }
        } else {
            $sqlBreak = "SELECT * FROM hrd_break_time WHERE type = 0 ORDER BY start_time";
            $resDbBreak = $db->execute($sqlBreak);
        }
        while ($rowDbBreak = $db->fetchrow($resDbBreak)) {
            $strStartBreak = $rowDbBreak['start_time'];
            $strDurationBreak = $rowDbBreak['duration'];
            $strEndBreak = getNextMinute($rowDbBreak['start_time'], $rowDbBreak['duration']);
            break;
        }
        $strResult .= "<tr valign=top id='detailData$tipe" . "_$i'>\n";
        $strResult .= "  <td><input class='form-control' type=text name=dataBreak$tipe" . "_$i size=12 maxlength=10 value=$strStartBreak data-mask=\"99:99\"></td>\n";
        $strResult .= "  <td><input class='form-control' type=text name=dataDuration$tipe" . "_$i size=12 maxlength=10 value=$strDurationBreak></td>\n";
        $strResult .= "  <td><input class='form-control' type=text name=dataBreakFinish$tipe" . "_$i size=12 maxlength=10 value=$strDefaultBreak data-mask=\"99:99\"></td>\n";
        $strResult .= "  <td><input class='form-control-sized' type=\"text\" name=\"dataNote$tipe" . "_$i\" size=\"50\" maxlength=\"50\" value='Default Break Time'></td>\n";
        $strResult .= "</tr>\n";
        $i++;
    }
    if ($i == 0) {
        $intNumShow = 1;
    } else {
        $intNumShow = $i;
    }
    // tambahkan detail tambahan
    while ($i <= $intMaxDetail) {
        $strStyle = ($i == 0) ? "" : "style = display:none ";
        $strDis = ($i == 0) ? "" : "disabled";
        $strResult .= "<tr valign=top id='detailData$tipe" . "_$i' $strStyle>\n";
        $strResult .= "  <td><input class='form-control' type=text name=dataBreak$tipe" . "_$i size=12 maxlength=10 value=$strDefaultBreak $strDis data-mask=\"99:99\"></td>\n";
        $strResult .= "  <td><input class='form-control' type=text name=dataDuration$tipe" . "_$i size=12 maxlength=10 value=0 $strDis></td>\n";
        //$strResult .= "  <td>&nbsp;</td>\n";
        $strResult .= "  <td><input class='form-control' type=text name=dataBreakFinish$tipe" . "_$i size=12 maxlength=10 value=$strDefaultBreak $strDis data-mask=\"99:99\"></td>\n";
        $strResult .= "  <td><input class='form-control-sized' type=\"text\" name=\"dataNote$tipe" . "_$i\" size=\"50\" maxlength=\"50\" value='' $strDis></td>\n";
        $strResult .= "</tr>\n";
        $i++;
    }
    // tambahkan tombol untuk more input
    $strResult .= "
      <tr>
        <td><a class='btn btn-primary btn-xs' href=javascript:moreInput($tipe)>" . getWords("more") . "</a></td>
        <td>&nbsp;<input type=hidden name='numShow$tipe' value=$intNumShow></td>
        <td>&nbsp;<input type=hidden name='maxDetail$tipe' value=$intMaxDetail></td>
        <td>&nbsp;</td>
      </tr>
    ";
    return $strResult;
}//getDataBreak
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $arrUserList = getAllUserInfo($db);
    $maxOTMember = getSetting("max_ot_member");
    if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == "searchEmployee") {
        echo searchEmployee($db, $_REQUEST['strCategory'], $_REQUEST['strKey']);
        exit();
    }
    if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == "addEmployee") {
        echo addEmployee(
            $db,
            $_REQUEST['strList'],
            $_REQUEST['strOldList'],
            $_REQUEST['strDate'],
            $_REQUEST['intTotalData']
        );
        exit();
    }
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    $bolSave = (isset($_REQUEST['btnSave']) || isset($_REQUEST['btnSave1']));
    scopeData(
        $arrUserInfo['employee_id'],
        $strDataSubSection,
        $strDataSection,
        $strDataDepartment,
        $strDataDivision,
        $_SESSION['sessionUserRole'],
        $arrUserInfo
    );
    //cek user role, jika supervisor dan employee hanya bisa entri data dari employee search list (supaya scopeData berlaku)
    //$strIndividuDisable = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE || $_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) ? "disabled" : "";
    $strMessages = "";
    if ($bolSave) {
        if ($bolCanEdit) {
            $bolOK = saveData($db, $strDataID, $strError);
            $closeButton = '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
            if ($bolOK) {
                $successIcon = '<i class="fa fa-exclamation-circle"></i>';
                $strMessages = '<div class="alert alert-info">' . $closeButton . $successIcon . $strError . '</div>';
                $strDataID = "";
            } else {
                $errorIcon = '<i class="fa fa-times-circle"></i>';
                $strMessages = '<div class="alert alert-danger">' . $closeButton . $errorIcon . $strError . '</div>';
            }
            $strMsgClass = ($bolOK) ? "class = bgOK" : "class = bgError";
            /*
            if ($strError != "") {
              echo "<script>alert($strError)</script>";
            }
            if($bolSaved)
            header('Location: overtime_application_edit.php');
              // header('overtime_application_edit.php');
            */
        } else {
            echo "<script>alert(\"Sorry, you are not authorized to modify data in this page\")</script>";
        }
    } else if (isset($_REQUEST['btnAdd'])) {
        if ($bolCanEdit) {
            addEmployee($db, $strDataID, $strError);
            if ($strError != "") {
                echo "<script>alert($strError)</script>";
            }
        }
    }
    if ($bolCanView) {
        $strDataID != "";
        getData($db, $arrData, $strDataID);
        $strBreakNormal = getDataBreak($db, 3, $strDataID);
    } else {
        showError("view_denied");
        $strDataDetail .= "";
    }
    //----- TAMPILKAN DATA ---------
    $strUserRole = $_SESSION['sessionUserRole'];
    $strDisplayNone = "";
    if ($strDisplayNone == ROLE_EMPLOYEE) {
        $strDisplayNone = "style = 'display:none;'";
    }
    $strDataPhoto = "";
    $strButtonSave = "";
    $strButtonPrint = "";
    $strChecked = ($arrData['dataEarly']) ? "checked" : "";
    //ambil data periode gaji
    //$strInputNote = "<input type=text name=dataNote size=$intDefaultWidth maxlength=50 value=" .$arrData['dataNote']. " style=width:$intDefaultWidthPx>";
    $strInputNote = "<textarea class='form-control' name='dataNote' id='dataNote' rows=3 >" . $arrData['dataNote'] . "</textarea>";
    $strInputEarly1 = "<input class='form-control' type=text name=\"dataStartEarly\" size=5 maxlength=5 value=\"" . $arrData['dataStartEarly'] . "\"  onChange=\"setDefault('Early');\" readonly data-mask=\"99:99\">";
    $strInputEarly2 = "<input class='form-control' type=text name=\"dataFinishEarly\" size=5 maxlength=5 value=\"" . $arrData['dataFinishEarly'] . "\"  onChange=\"setDefault('Early');\" readonly data-mask=\"99:99\">";
    $strInputEarly3 = "<div class='checkbox no-margin no-padding'><label><input class='checkbox-inline' type=\"hidden\" name=\"chkEarly\" onChange=\"setEarly()\" $strChecked ></label></div>";
    $strOutdatedChecked = ($arrData['dataIsOutdatedTemp'] == "t") ? "checked" : "";
    // hanya admin yang bisa melakukan back date overtime
    // uddin 20151126
    IF ($_SESSION['sessionUserRole'] >= ROLE_ADMIN) {
        $strInputIsOutdated = "<input type=\"hidden\" name=\"dataIsOutdatedTemp\" value=\"" . $arrData['dataIsOutdatedTemp'] . "\"><div class='checkbox no-margin no-padding'><label><input class='checkbox-inline' type=\"checkbox\" name=\"dataIsOutdated\" onChange=\"setSalaryMonth(this.checked)\" $strOutdatedChecked></label></div>";
        $strInputSalaryMonth = getMonthList("dataSalaryMonth", $arrData['dataSalaryMonth'], $strEmptyOption);
        $strInputSalaryYear = "<input class='form-control-sized' type=\"text\" size=10 name=\"dataSalaryYear\" value=\"" . $arrData['dataSalaryYear'] . "\" >";
    } else {
        $strInputIsOutdated = "";
        $strInputSalaryMonth = "";
        $strInputSalaryYear = "";
        // labelnya juga dihilangkan
        $strWordsIsOutdated = "";
        $strWordsSalaryMonth = "";
    }
    $strInputAfternoon1 = "<input class='form-control' type=text name=dataStart size=5 maxlength=5 value=" . $arrData['dataStart'] . "  onChange=\"setDefault(''); \" data-mask=\"99:99\">";
    $strInputAfternoon2 = "<input class='form-control' type=text name=dataFinish size=5 maxlength=5 value=" . $arrData['dataFinish'] . "  onChange=\"setDefault('');\" data-mask=\"99:99\">";
    IF ($_SESSION['sessionUserRole'] < ROLE_ADMIN) {
        $strInputEmployee1 = "<input readonly value='" . $arrUserInfo['employee_id'] . "' class='form-control' type=text name=dataEmployee id=dataEmployee size=12 maxlength=30  style=width:$intDefaultWidthPx >";
    } else {
        $strInputEmployee1 = "<input class='form-control' type=text name=dataEmployee id=dataEmployee size=12 maxlength=30  style=width:$intDefaultWidthPx >";
    }
    // tambahkan tombol add  pada bagian employee
    $strInputEmployee2 = "<input class='btn btn-primary btn-xs' type=button id=btnAdd name=btnAdd value='" . getWords(
            "add to list"
        ) . "'  onClick='addEmployee(document.formInput.dataEmployee.value)' >";
    // tambahkan tombol SAVE, khusus jika data baru
    if ($strDataID == "") {
        //$strButtonSave .= " &nbsp; <input type=submit name=btnSave1 value=" .$words['save']. ">";
    } else {
        // tammbah tombol print
        //$strButtonPrint .= " &nbsp; <input type=button name=btnPrint value=" .$words['print']. " onClick=window.open('overtimeApplicationPrint.php?dataID=$strDataID')>";
    }
    getDefaultSalaryPeriode($strSalaryStart, $strSalaryFinish, $arrData['dataEntryDate']);
    $strInputEntryDate = "<input class='form-control datepicker' type=text name=dataEntryDate id=dataEntryDate size=12 maxlength=10   value=\"" . $arrData['dataEntryDate'] . "\" readonly data-date-format='mm/dd/yyyy'>";
    $strInputDate = "<input class='form-control datepicker' type=text name=dataDate id=dataDate size=12 maxlength=10 value='" . $arrData['dataDate'] . "' data-date-format='mm/dd/yyyy'>";
    $strInputDivision = getDivisionList(
        $db,
        "dataDivision",
        $strDataDivision,
        $strEmptyOption,
        "",
        " onChange=\"searchEmployee()\" " . $ARRAY_DISABLE_GROUP['division']
    );
    $strInputDepartment = getDepartmentList(
        $db,
        "dataDepartment",
        $strDataDepartment,
        $strEmptyOption,
        "",
        " onChange=\"searchEmployee()\" " . $ARRAY_DISABLE_GROUP['department']
    );
    $strInputSection = getSectionList(
        $db,
        "dataSection",
        $strDataSection,
        $strEmptyOption,
        "",
        " onChange=\"searchEmployee()\" " . $ARRAY_DISABLE_GROUP['section']
    );
    $strInputSubSection = getSubSectionList(
        $db,
        "dataSubSection",
        $strDataSubSection,
        $strEmptyOption,
        "",
        " onChange=\"searchEmployee()\"" . $ARRAY_DISABLE_GROUP['sub_section']
    );
    $strFinishYear = intval(substr($strSalaryFinish, 0, 4));
    $strSalaryFinish = intval(substr($strSalaryFinish, 5, 2));
    $strSalaryMonthInfo = "<input type='hidden' name='dataSalaryStart' value='$strSalaryStart'>";
    $strSalaryMonthInfo .= "<input type='hidden' name='dataSalaryFinish' value='$strSalaryFinish'>";
    $strSalaryMonthInfo .= "<input type='hidden' name='dataFinishYear' value='$strFinishYear'>";
    $strDataDetail .= $arrData['dataDetail'] . "";
    if ($arrData['dataStatus'] == "") {
        $strInputStatus = "";
    } else {
        $strInputStatus = $words[$ARRAY_REQUEST_STATUS[$arrData['dataStatus']]];
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
$strPageDesc = getWords('overtime application entry form');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = overtimeSubmenu($strWordsDataEntry);
if ($bolPrint) {
    $strMainTemplate = getTemplate(str_replace(".php", "_print.html", basename($_SERVER['PHP_SELF'])));
} else {
    $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
