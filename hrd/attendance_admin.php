<?php
session_start();
/*
  PROGRAM INI UNTUK MEMERIKSA APAKAH DAFTAR KEHADIRAN YANG DUPLICATE
*/
include_once('global.php');
include_once('form_object.php');
include_once('overtime_func.php');
include_once('activity.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  header("location:login.php?dataPage=attendance_list.php");
  exit();
}
$bolCanView = getUserPermission("attendance_list.php", $bolCanEdit, $bolCanDelete, $strError, true);
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
if ($bolPrint) {
  $strMainTemplate = getTemplate("attendance_list_print.html");
} else {
  $strTemplateFile = getTemplate("attendance_list.html");
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strButtons = "";
$strButtonsTop = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $bolPrint;
  global $arrUserList;
  global $_SESSION;
  $intRows = 0;
  $strResult = "";
  $objTime = new CexecutionTime();
  //ambil setting apakah sabtu libur
  $bolSaturday = (getSetting("saturday") == 't');
  // ambil data hari libur
  $arrHoliday = [];
  $strSQL = "SELECT * FROM hrd_calendar WHERE status = 't' ";
  $strSQL .= "AND \"holiday\" BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if ($rowDb['holiday'] != "") {
      list($year, $month, $day) = explode("-", $rowDb['holiday']);
      $year = (int)$year;
      $month = (int)$month;
      $day = (int)$day;
      $tsTmp = mktime(0, 0, 0, $month, $day, $year);
      $arrHoliday[$tsTmp] = $rowDb['status'];
    }
  }
  // ambil dulu data employee, kumpulkan dalam array
  $arrEmployee = [];
  $i = 0;
  $strSQL = "SELECT * FROM hrd_employee ";
  $strSQL .= "WHERE flag=0 -- $strKriteria ORDER BY $strOrder employee_id ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrEmployee[$rowDb['id']] = $rowDb;
    $i++;
  }
  $strEmployeeSQL = str_replace("*", "id", $strSQL);
  // ambil data kehadiran, kumpulkan di array
  $arrHadir = [];
  $strSQL = "
      SELECT * FROM hrd_attendance WHERE attendance_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru'
      ORDER BY attendance_date,id_employee
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrHadir[$rowDb['attendance_date']][$rowDb['id_employee']][] = $rowDb;
  }
  // lakukan loop berdasar employee
  $intRows = 0;
  foreach ($arrHadir AS $strDate => $arrHadir1) {
    $strTanggal = pgDateFormat($strDate, "d-M-y");
    foreach ($arrHadir1 AS $strIDEmp => $arrTmp) {
      $tmp = count($arrTmp);
      $strStyle1 = ($tmp > 1) ? " style=\"background-color:yellow\" " : "";
      foreach ($arrTmp AS $i => $rowDb) {
        $intRows++;
        $arrData = $arrEmployee[$rowDb['id_employee']];
        // cari data kehadiran pada tanggal tersebut
        // -- inisialisasi dulu
        $strDataAttendanceStart = "";
        $strDataAttendanceFinish = "";
        $strDataNormalStart = "";
        $strDataNormalFinish = "";
        $strDataChangeStart = "";
        $strDataChangeFinish = "";
        $strDataLate = "";
        $strDataTransport = "";
        $strDataNote = "";
        $strDataAttendanceID = "";
        $strAttendanceID = "";
        $strClass = "";
        $intStatus = -1;
        $strShift = "&nbsp;";
        $intMorningOT = 0;
        $intLate = 0;
        $intEarly = 0;
        $intOT1 = 0;
        $intOT2 = 0;
        $intOT3 = 0;
        $intOT4 = 0;
        $intOT = 0;
        $arrRecord = [
            "created" => "",
            "verifiedTime" => "",
            "checkedTime" => "",
            "approvedTime" => "",
            "deniedTime" => "",
            "modified_by" => "",
            "verifiedBy" => "",
            "checked_by" => "",
            "approvedBy" => "",
            "deniedBy" => "",
        ];
        $strLibur = "";
        $strDisableChk = "disabled";
        $strLateStyle = "";
        $strIsAbs = "";
        $strDataAttendanceStart = substr($rowDb['attendance_start'], 0, 5);
        $strDataAttendanceFinish = substr($rowDb['attendance_finish'], 0, 5);
        $strDataNormalStart = substr($rowDb['normal_start'], 0, 5);
        $strDataNormalFinish = substr($rowDb['normal_finish'], 0, 5);
        $strDataChangeStart = substr($rowDb['change_start'], 0, 5);
        $strDataChangeFinish = substr($rowDb['change_finish'], 0, 5);
        // periksa apakah checkbox aproval disable atau gak
        if ($rowDb['notLate'] == 'f' && ($strDataNormalStart < $strDataAttendanceStart)) {
          $strDataLate = "&radic;";
          //$strClass = "class =\"bgLate\"";
          $strLateStyle = "style=\"background-color:red;color:yellow\"";
        }
        $strDataNote = $rowDb['note'];
        if ($rowDb['transport'] == '1') {
          $strDataTransport = "&radic;";
        }
        $strDataAttendanceID = "<input type=hidden name=detailAttendanceID value=\"" . $rowDb['id'] . "\">";
        $strAttendanceID = $rowDb['id'];
        $intStatus = $rowDb['status'];
        $intMorningOT = $rowDb['morningOvertime'];// /60;
        $intLate = $rowDb['lateDuration'];// /60;
        $intEarly = $rowDb['earlyDuration'];// /60;
        $intOT1 = $rowDb['l1'];// /60;
        $intOT2 = $rowDb['l2'];// /60;
        $intOT3 = $rowDb['l3'];// /60;
        $intOT4 = $rowDb['l4'];// /60;
        $intOT = $rowDb['overtime'];// /60;
        if ($rowDb['holiday'] == 1) {
          $strLibur = " class='bgHoliday' ";
          $strLateStyle = ""; // gak ditandai sebagai telat
        }
        //$arrRecord = $arrAtt[$arrData['id']];
        $strIsAbs = ($rowDb['isAbsence'] == 't') ? "&radic;" : "";
        // cari info total telat, pulang awal dsb
        // hitung manual, gak kehitung soalnya
        if ($intLate <= 0) {
          $intMorningOT = getIntervalHour($rowDb['attendance_start'], $rowDb['normal_start']);
        }
        if ($intEarly <= 0) {
          $intOT = getIntervalHour($rowDb['normal_finish'], $rowDb['attendance_finish']);
        }
        // cari info absensi/ketidakhadiran
        $strAbs = "";
        $strAbsTitle = "";
        $strAbsClass = "";
        $strClass = "class='" . getCssClass($intStatus) . "'";
        $strClass1 = ""; // kelas buat nandain periode dah berakhir belum
        if ($intStatus == REQUEST_STATUS_NEW) {
          $intDur = totalWorkDay($db, $strDate, date("Y-m-d"));
          if ($intDur > INT_LIMIT_APPROVAL) {
            $strClass1 = "class='bgBlocked'";
            if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
              $strClass = "class='bgBlocked'";
            }
          }
        }
        $strResult .= "<tr valign=top $strClass>";
        $strResult .= "  <td align='center'>&nbsp;</td>";
        if ($i == 0) {
          $strResult .= " <td $strClass1>$strTanggal&nbsp;$strDataAttendanceID</td>";
          $strResult .= "  <td nowrap $strStyle1><input type=hidden name=detailID$intRows value=\"" . $arrData['id'] . "\" disabled>" . $arrData['employee_id'] . "&nbsp;</td>";
          $strResult .= "  <td nowrap $strStyle1>" . $arrData['employee_name'] . "&nbsp;</td>";
        } else {
          $strResult .= " <td $strClass1>&nbsp;$strDataAttendanceID</td>";
          $strResult .= "  <td>&nbsp;</td>";
          $strResult .= "  <td>&nbsp;</td>";
        }
        $strCF = (($i + 1) == $tmp) ? "" : "checked";
        $strResult .= "  <td $strStyle1>&nbsp;</td>";
        $strResult .= "  <td $strStyle1>&nbsp;</td>";
        $strResult .= "  <td nowrap $strStyle1><input type=checkbox name=chkID$intRows value=\"$strAttendanceID\" $strCF ></td>";
        $strResult .= "  <td $strLateStyle align=center>" . $strDataAttendanceStart . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $strDataAttendanceFinish . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $strDataNormalStart . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $strDataNormalFinish . "&nbsp;</td>";
        if (!$bolPrint) {
          $strResult .= "  <td align=center>" . $strDataChangeStart . "&nbsp;</td>";
          $strResult .= "  <td align=center>" . $strDataChangeFinish . "&nbsp;</td>";
        }
        $strResult .= "  <td align=center>" . $strDataLate . "&nbsp;</td>";
        $strResult .= "  <td>" . $strDataNote . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . minuteToTime($intMorningOT) . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . minuteToTime($intLate) . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . minuteToTime($intEarly) . "&nbsp;</td>";
        $strResult .= "  <td align=center $strLibur>" . minuteToTime($intOT) . "&nbsp;</td>";
        // info record
        if (!$bolPrint) {
          $strDiv = "<div id='detailRecord$intRows' style=\"display:none\">\n";
          $strDiv .= "<strong>" . $arrData['employee_id'] . "-" . $arrData['employee_name'] . "</strong><br>\n";
          $strDiv .= getWords("last modified") . ": " . substr($arrRecord['created'], 0, 19) . " ";
          $strDiv .= (isset($arrUserList[$arrRecord['modified_by']])) ? $arrUserList[$arrRecord['modified_by']]['name'] . "<br>" : "<br>";
          $strDiv .= getWords("verified") . ": " . substr($arrRecord['verified_time'], 0, 19) . " ";
          $strDiv .= (isset($arrUserList[$arrRecord['verified_by']])) ? $arrUserList[$arrRecord['verified_by']]['name'] . "<br>" : "<br>";
          $strDiv .= getWords("checked") . ": " . substr($arrRecord['checked_time'], 0, 19) . " ";
          $strDiv .= (isset($arrUserList[$arrRecord['checked_by']])) ? $arrUserList[$arrRecord['checked_by']]['name'] . "<br>" : "<br>";
          $strDiv .= getWords("approved") . ": " . substr($arrRecord['approved_time'], 0, 19) . " ";
          $strDiv .= (isset($arrUserList[$arrRecord['approved_by']])) ? $arrUserList[$arrRecord['approved_by']]['name'] . "<br>" : "<br>";
          $strDiv .= getWords("denied") . ": " . substr($arrRecord['denied_time'], 0, 19) . " ";
          if (isset($arrUserList[$arrRecord['denied_by']])) {
            $strDiv .= $arrUserList[$arrRecord['denied_by']]['name'] . "<br>";
          } else {
            $strDiv .= ($arrRecord['denied_by'] == -1) ? "[system]<br>" : "<br>";
          }
          $strDiv .= "</div>\n";
          $strResult .= "  <td nowrap align=center><a href=\"javascript:openWindowById('detailRecord$intRows')\" title=\"" . getWords(
                  "show record info"
              ) . "\">" . getWords("show") . "$strDiv</a></td>\n";
          $strResult .= "  <td nowrap align=center>&nbsp;$strIsAbs&nbsp;</td>\n";
        }
        $strResult .= "</tr>\n";
      }
    }
  }
  return $strResult;
} // showData
// fungsi untuk menghapus data yang dipilih
function deleteData($db)
{
  global $_REQUEST;
  $strList = ""; // daftar ID yang dihapus
  $intTotal = (isset($_REQUEST['dataTotalData'])) ? $_REQUEST['dataTotalData'] : 0;
  for ($i = 0; $i <= $intTotal; $i++) {
    if (isset($_REQUEST['chkID' . $i])) {
      if ($strList != "") {
        $strList .= ", ";
      }
      $strList .= "'" . $_REQUEST['chkID' . $i] . "'";
    }
  }
  if ($strList != "") {
    $strSQL = "DELETE FROM hrd_attendance WHERE id IN ($strList) ";
    $resExec = $db->execute($strSQL);
  }
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$intDefaultStart = "08:00";
$intDefaultFinish = "17:00";
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  $arrUserList = getAllUserInfo($db);
  if (isset($_POST['btnDelete'])) {
    deleteData($db);
  }
  // ------ AMBIL DATA KRITERIA -------------------------
  $strDataDateFrom = (isset($_SESSION['sessionFilterDateFrom'])) ? $_SESSION['sessionFilterDateFrom'] : date("Y-m-d");
  $strDataDateThru = (isset($_SESSION['sessionFilterDateThru'])) ? $_SESSION['sessionFilterDateThru'] : date("Y-m-d");
  $strDataDepartment = (isset($_SESSION['sessionFilterDepartment'])) ? $_SESSION['sessionFilterDepartment'] : "";
  $strDataSection = (isset($_SESSION['sessionFilterSection'])) ? $_SESSION['sessionFilterSection'] : "";
  $strDataEmployee = (isset($_SESSION['sessionFilterEmployee'])) ? $_SESSION['sessionFilterEmployee'] : "";
  $strDataEmployeeStatus = (isset($_SESSION['sessionFilterEmployeeStatus'])) ? $_SESSION['sessionFilterEmployeeStatus'] : "";
  if (isset($_REQUEST['dataDateFrom'])) {
    $strDataDateFrom = $_REQUEST['dataDateFrom'];
  }
  if (isset($_REQUEST['dataDateThru'])) {
    $strDataDateThru = $_REQUEST['dataDateThru'];
  }
  if (isset($_REQUEST['dataDepartment'])) {
    $strDataDepartment = $_REQUEST['dataDepartment'];
  }
  if (isset($_REQUEST['dataSection'])) {
    $strDataSection = $_REQUEST['dataSection'];
  }
  if (isset($_REQUEST['dataEmployee'])) {
    $strDataEmployee = $_REQUEST['dataEmployee'];
  }
  if (isset($_REQUEST['dataEmployeeStatus'])) {
    $strDataEmployeeStatus = $_REQUEST['dataEmployeeStatus'];
  }
  // simpan dalam session
  $_SESSION['sessionFilterDepartment'] = $strDataDepartment;
  $_SESSION['sessionFilterSection'] = $strDataSection;
  $_SESSION['sessionFilterEmployee'] = $strDataEmployee;
  $_SESSION['sessionFilterEmployeeStatus'] = $strDataEmployeeStatus;
  if ($bolIsEmployee) {
    $strDataEmployee = $arrUserInfo['employee_id'];
  } else if ($arrUserInfo['isGroupHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strDataSection = $arrUserInfo['section_code'];
  } else if ($arrUserInfo['isDeptHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strDataDepartment = $arrUserInfo['department_code'];
  }
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if ($strDataDepartment != "") {
    $strKriteria .= "AND department_code = '$strDataDepartment' ";
  }
  if ($strDataSection != "") {
    $strKriteria .= "AND section_code = '$strDataSection' ";
  }
  if ($strDataEmployee != "") {
    $strKriteria .= "AND employee_id = '$strDataEmployee' ";
  }
  if (is_numeric($strDataEmployeeStatus) && $strDataEmployeeStatus > 0) {
    $strKriteria .= ($strDataEmployeeStatus == 2) ? "AND employee_status = '" . STATUS_OUTSOURCE . "'" : "AND employee_status <> '" . STATUS_OUTSOURCE . "' ";
  }
  if (isset($_REQUEST['btnShowAlert'])) {
    $status = (isset($_REQUEST['dataStatus'])) ? $_REQUEST['dataStatus'] : "";
    if (is_numeric($status)) {
      $strKriteria .= "AND t1.status = $status ";
    } else {
      $strKriteria .= "AND t1.status < " . REQUEST_STATUS_APPROVED . " AND t1.status <> " . REQUEST_STATUS_DENIED . " ";
    }
  }
  if ($bolCanView) {
    if (validStandardDate($strDataDateFrom) && validStandardDate(
            $strDataDateThru
        ) && (isset($_REQUEST['btnShow']) || isset($_REQUEST['btnPrint']))
    ) {
      // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
      $strDataDetail = getData($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
      $strHidden .= "<input type=hidden name=btnShow value=show>";
    } else if (isset($_REQUEST['btnShowAlert'])) {
      $strDataDetail = getDataAlert($db, $intTotalData, $strKriteria);
      $strHidden .= "<input type=hidden name=btnShowAlert value=show>";
      $strHidden .= "<input type=hidden name=dataStatus value=" . $_REQUEST['dataStatus'] . ">";
    } else {
      $strDataDetail = "";
    }
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  // generate data hidden input dan element form input
  $strReadonly = ($bolIsEmployee) ? "readonly" : "";
  $strDisabled = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) ? "disabled" : "";
  $intDefaultWidthPx = 200;
  $strInputDateFrom = "<input type=text name=dataDateFrom id=dataDateFrom size=15 maxlength=10 value=\"$strDataDateFrom\">";
  $strInputDateThru = "<input type=text name=dataDateThru id=dataDateThru size=15 maxlength=10 value=\"$strDataDateThru\">";
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"$strDataEmployee\" $strReadonly>";
  //     $strInputDivision = getDivisionList($db,"dataDivision",$strDataDivision, $strEmptyOption, "", "style=\"width:$intDefaultWidthPx\" $strDisabled");
  $strTmpKriteria = "WHERE 1 =1 ";
  if ($arrUserInfo['isDeptHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strTmpKriteria .= "AND department_code = '" . $arrUserInfo['department_code'] . "' ";
    $strDisabled = "";
  }
  $strInputDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $strDataDepartment,
      $strEmptyOption,
      $strTmpKriteria,
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  if ($arrUserInfo['isGroupHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strTmpKriteria .= "AND section_code = '" . $arrUserInfo['section_code'] . "' ";
    $strDisabled = "";
  }
  $strInputSection = getSectionList(
      $db,
      "dataSection",
      $strDataSection,
      $strEmptyOption,
      $strTmpKriteria,
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  $arrTmp = ["", "employee", "outsource"];
  $strInputEmployeeStatus = getComboFromArray($arrTmp, "dataEmployeeStatus", $strDataEmployeeStatus);
  //     $strInputSubsection = getSubSectionList($db,"dataSubsection",$strDataSubsection, $strEmptyOption, "", "style=\"width:$intDefaultWidthPx\" $strDisabled");
  //     $strInputGroup = getGroupList($db,"dataGroup",$strDataGroup, $strEmptyOption, "", "style=\"width:$intDefaultWidthPx\" $strDisabled");
  // informasi tanggal kehadiran
  if ($strDataDateFrom == $strDataDateThru) {
    $strInfo .= "<br>" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
  } else {
    $strInfo .= "<br>" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
    $strInfo .= " >> " . strtoupper(pgDateFormat($strDataDateThru, "d-M-Y"));
  }
  $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDataDateFrom\">";
  $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDataDateThru\">";
  //     $strHidden .= "<input type=hidden name=dataDivision value=\"$strDataDivision\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
  //     $strHidden .= "<input type=hidden name=dataSubsection value=\"$strDataSubsection\">";
  //     $strHidden .= "<input type=hidden name=dataGroup value=\"$strDataGroup\">";
  $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
  $strHidden .= "<input type=hidden name=dataEmployeeStatus value=\"$strDataEmployeeStatus\">";
  $strHidden .= "<input type=hidden name=dataTotalData value=\"$intTotalData\">";
  $strButtons .= "&nbsp;<input type=submit name=btnDelete value=\"" . $words['delete'] . "\" onClick=\"return confirm('Are you sure want to delete selected data?')\">";
}
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>