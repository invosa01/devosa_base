<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
include_once('cls_absence.php');
$dataPrivilege = getDataPrivileges("dayoff_search.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strReportName = strtoupper(getWords("overtime report"));
$strWordsDataEntry = getWords("data entry");
$strWordsApplicationList = getWords("overtime application list");
$strWordsOvertimeReport = getWords("overtime report");
$strWordsDayOffList = getWords("list of dayoff");
$strWordsEmployeeDayOff = getWords("employee dayoff");
$strWordsDateFrom = getWords("date from");
$strWordsDateThru = getWords("date to");
$strWordsDepartment = getWords("department");
$strWordsArea = getWords("area");
$strWordsEmployeeID = getWords("employee id");
$strWordsType = getWords("type");
$strWordsLISTOFDAYOFF = getWords("list of day off");
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strStyle = "";
$strInitAction = "";
//----------------------------------------------------------------------
// fungsi buat nentuin apakah standard format atau gak
function cekStandardFormat($strText, $bolDec = true, $intDec = 2)
{
  global $_REQUEST;
  if (isset($_REQUEST['btnExcel'])) // untuk tampil di excel
  {
    $strResult = $strText;
  } else {
    $strResult = standardFormat($strText, $bolDec, $intDec) . "&nbsp;";
  }
  return $strResult;
}

//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data, summary berapa nilai hak dayoff yang dimiliki oleh karyawan, sampai dengan batas akhir
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getDataSummary($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $objUP;
  // ambil dulu
  $objDO = new clsDayOff($db, "", $strDataDateThru); // cls_absence.php
  $arrDO = $objDO->getAllTotalDayOff();
  // header
  $strResult = "
      <table class='gridTable' cellspacing=0 cellpadding=1 border-0>
        <tr>
          <td class='tableHeader'>" . getWords("employee id") . "</td>
          <td class='tableHeader'>" . getWords("name") . "</td>
          <td class='tableHeader'>" . getWords("department") . "</td>
          <td class='tableHeader'>" . getWords("section") . "</td>
          <td class='tableHeader' nowrap>" . getWords("day off") . "</td>
          <td class='tableHeader'>&nbsp;</td>
        </tr>
    ";
  // detail
  $intRow = 0;
  $strActiveCriteria = " AND ((active = 1) OR (active = 0 AND resign_date > CURRENT_DATE) )";
  $strKriteria .= $objUP->genFilterEmployee();
  $strSQL = "SELECT * FROM hrd_employee WHERE 1=1 $strActiveCriteria $strKriteria ORDER BY employee_id ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $intRow++;
    $intDO = (isset($arrDO[$row['id']])) ? $arrDO[$row['id']] : 0;
    $strResult .= "
        <tr>
          <td nowrap>" . $row['employee_id'] . "&nbsp;</td>
          <td nowrap>" . $row['employee_name'] . "&nbsp;</td>
          <td>" . $row['department_code'] . "&nbsp;</td>
          <td>" . $row['section_code'] . "&nbsp;</td>
          <td align=center>" . $intDO . "&nbsp;</td>
          <td align=center nowrap><input type=button name='btnShowDetail$intRow' id='btnShowDetail$intRow' value='" . getWords(
            'show detail'
        ) . "' onclick=\"goViewDetail('" . $row['employee_id'] . "')\"></td>
        </tr>
      ";
  }
  // footer
  $strResult .= "
        
      </table>
    ";
  unset($objDO);
  writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  $strResult .= "</table>\n";
  return $strResult;
} // getData
// fungsi untuk menampilkan data, detal per dayoff yang dilakukan karyawan, dalam batas waktu tanggal tertentu
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getDataDetail($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $strDataEmployee;
  global $strInitAction;
  global $objUP;
  if ($strDataEmployee == "") {
    return "";
  }
  // ambil dulu
  $objDO = new clsDayOff($db, "", $strDataDateThru);
  $arrDO = $objDO->getAllTotalDayOff();
  $bolIsEmployee = $objUP->isUserEmployee();
  // header
  $strResult = "
      <table class='gridTable' cellspacing=0 cellpadding=1 border-0>
        <tr>
          <td class='tableHeader'>" . getWords("employee id") . "</td>
          <td class='tableHeader'>" . getWords("name") . "</td>
          <td class='tableHeader'>" . getWords("department") . "</td>
          <td class='tableHeader'>" . getWords("section") . "</td>
          <td class='tableHeader'>" . getWords("date") . "</td>
          <td class='tableHeader'>" . getWords("due date") . "</td>
          <td class='tableHeader'>" . getWords("taken") . "</td>
          <td class='tableHeader'>&nbsp;</td>
        </tr>
    ";
  // ambil data pengambilan day off
  $arrTaken = [];
  $strSQL = "
      SELECT t1.*
      FROM (
        SELECT * FROM hrd_absence_confirm_detail
        WHERE info_type = '" . ABS_INFO_DAYOFF . "'
          AND date_to BETWEEN '$strDataDateFrom' AND '$strDataDateThru'
      ) AS t1
      INNER JOIN (
        SELECT * FROM hrd_absence_confirm
        WHERE status = '" . REQUEST_STATUS_APPROVED . "'
      ) AS t2 ON t1.id_absence_confirm = t2.id
      INNER JOIN (
        SELECT * FROM hrd_employee WHERE employee_id = '$strDataEmployee' 
      ) AS t3 ON t1.id_employee = t3.id 
      
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $arrTaken[$row['date_to']] = $row['date_from'];
  }
  // detail
  $intRow = 0;
  $strSQL = "
      SELECT t1.*, t2.overtime_date,
        t3.employee_id, t3.employee_name, t3.department_code, t3.section_code
      FROM (
        SELECT * FROM hrd_overtime_application_employee
        WHERE overtime_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru'
      ) AS t1
      INNER JOIN (
        SELECT * FROM hrd_overtime_application 
        WHERE overtime_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru'
        AND overtime_type = 1
        AND status = " . REQUEST_STATUS_APPROVED . "
      ) AS t2 ON t1.id_application = t2.id
      INNER JOIN (
        SELECT * FROM hrd_employee WHERE employee_id = '$strDataEmployee'
          AND active IN (0,1) " . $objUP->genFilterEmployee() . "
      ) AS t3 ON t1.id_employee = t3.id 
      ORDER BY t2.overtime_date
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $intRow++;
    $strDueDate = genTextDate(
        "DueDate" . $intRow,
        $row['dayoff_duedate'],
        (($bolIsEmployee) ? "readonly" : ""),
        (($bolIsEmployee) ? "disabled" : "")
    );//form_object.php
    $strBtn = ($row['dayoff'] == 1 && !$bolIsEmployee) ? "<input type=button name='btnShowDetail$intRow' id='btnShowDetail$intRow' value='" . getWords(
            'change due date'
        ) . "' onclick=\"goChangeDue('" . $intRow . "')\">" : "&nbsp;";
    $strBtn .= "<input type=hidden name='dataID$intRow' id='dataID$intRow' value='" . $row['id'] . "'>";
    $strTakenCls = ($row['dayoff'] == 1) ? " style=\"background-color:lightgreen\" " : "";
    $strTakenDate = $row['dayoff_taken'];
    if ($strTakenDate == "" && isset($arrTaken[$row['overtime_date']])) {
      $strTakenDate = $arrTaken[$row['overtime_date']];
    }
    $strTakenStatus = ($row['dayoff'] == 1) ? getWords("no") : getWords("yes");
    if ($strTakenDate != "" && $row['dayoff'] == 0) {
      $strTakenStatus .= " (" . $strTakenDate . ") ";
    }
    $strResult .= "
        <tr>
          <td nowrap>" . $row['employee_id'] . "&nbsp;</td>
          <td nowrap>" . $row['employee_name'] . "&nbsp;</td>
          <td>" . $row['department_code'] . "&nbsp;</td>
          <td>" . $row['section_code'] . "&nbsp;</td>
          <td align=center>" . pgDateFormat($row['overtime_date'], "d-M-y") . "&nbsp;</td>
          <td align=center nowrap>" . $strDueDate . "&nbsp;</td>
          <td align=center $strTakenCls title=\"$strTakenDate\">" . $strTakenStatus . "&nbsp;</td>
          <td align=center nowrap>$strBtn</td>
        </tr>
      ";
    $strInitAction .= "
        Calendar.setup({ inputField:\"dataDueDate$intRow\", button:\"btnDueDate$intRow\" });";
  }
  // footer
  $strResult .= "
        
      </table>
    ";
  unset($objDO);
  writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  $strResult .= "</table>\n";
  return $strResult;
} // getData
// fungsi untuk melakukan update data due date dayoff, untuk kasus perpanjangan due date
function updateDueDate($db)
{
  $strUserID = $_SESSION['sessionUserID'];
  $strDueDate = (isset($_POST['dataDueDate'])) ? $_POST['dataDueDate'] : "";
  $strID = (isset($_POST['dataDayOffID'])) ? $_POST['dataDayOffID'] : "";
  if ($strID != "" && validStandardDate($strDueDate)) {
    $strSQL = "
        UPDATE hrd_overtime_application_employee
        SET dayoff_duedate = '$strDueDate', modified_by = '$strUserID',
          modified = now()
        WHERE id = '$strID';
      ";
    $resExec = $db->execute($strSQL);
  }
}

//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  // ------ AMBIL DATA KRITERIA -------------------------
  (isset($_REQUEST['dataDateFrom'])) ? $strDataDateFrom = $_REQUEST['dataDateFrom'] : $strDataDateFrom = date("Y-m-d");
  (isset($_REQUEST['dataDateThru'])) ? $strDataDateThru = $_REQUEST['dataDateThru'] : $strDataDateThru = getNextDate(
      getNextDateNextMonth(date("Y-m-d"), 0)
  );
  (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
  (isset($_REQUEST['dataWilayah'])) ? $strDataWilayah = $_REQUEST['dataWilayah'] : $strDataWilayah = "";
  (isset($_REQUEST['dataType'])) ? $strDataType = $_REQUEST['dataType'] : $strDataType = 0;
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    if ($arrUserInfo['isDeptHead']) {
      $strDataDepartment = $arrUserInfo['department_code'];
    } else if ($arrUserInfo['isGroupHead']) {
      $strDataSection = $arrUserInfo['section_code'];
    } else {
      $strDataEmployee = $arrUserInfo['employee_id'];
    }
  }
  // cek action, jika ada
  if (isset($_POST['dataUpdate']) && $_POST['dataUpdate'] == 1) {
    if ($bolCanEdit) {
      updateDueDate($db);
    }
  }
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  /*if ($strDataDepartment != "") {
    $strKriteria .= "AND department_code = '$strDataDepartment' ";
  }
  if ($strDataWilayah != "") {
    $strKriteria .= "AND id_wilayah = '$strDataWilayah' ";
  }*/
  if ($strDataEmployee != "") {
    $strKriteria .= "AND employee_id = '$strDataEmployee' ";
  }
  //$strKriteria .= $strKriteriaCompany;
  if ($bolCanView) {
    $bolShow = (isset($_REQUEST['btnShow']) || $bolPrint);
    if (validStandardDate($strDataDateFrom) && validStandardDate($strDataDateThru) && $bolShow) {
      // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
      if ($strDataType == 1) {
        $strDataDetail = getDataDetail($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
      } else {
        $strDataDetail = getDataSummary($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
      }
      //         $strDataDetail = showData($arrDataDetail);
      //        $strDataDetail = showDataDepartment($db, $arrDataDetail);
      $strHidden .= "<input type=hidden name=btnShow value=show>";
      if (isset($_REQUEST['btnExcel'])) {
        // ambil data CSS-nya
        if (file_exists("../css/excel.css")) {
          $strStyle = "../css/excel.css";
        }
        $strPrintCss = "";
        $strPrintInit = "";
        headeringExcel("overtime_report.xls");
      }
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
  $strTmpKriteria = "WHERE 1=1 ";
  $strTmpKriteria .= $objUP->genFilterDepartment();
  $intDefaultWidthPx = 200;
  $strInputDateFrom = "<input type=text name=dataDateFrom id=dataDateFrom size=15 maxlength=10 value=\"$strDataDateFrom\">";
  $strInputDateThru = "<input type=text name=dataDateThru id=dataDateThru size=15 maxlength=10 value=\"$strDataDateThru\">";
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"$strDataEmployee\" $strReadonly>";
  // $strInputDepartment = getDepartmentList($db,"dataDepartment", $strDataDepartment, $strEmptyOption, $strTmpKriteria, "style=\"width:$intDefaultWidthPx\" $strDisabled");
  //$strInputArea = getWilayahList($db,"dataWilayah", $strDataWilayah, $strEmptyOption, "", "style=\"width:$intDefaultWidthPx\" ");
  if ($arrUserInfo['isGroupHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strTmpKriteria .= "AND section_code = '" . $arrUserInfo['section_code'] . "' ";
    $strDisabled = "";
  }
  //$strInputGroup = getGroupList($db,"dataGroup",$strDataGroup, $strEmptyOption, "", "style=\"width:$intDefaultWidthPx\" $strDisabled");
  $arrTmp = ["summary", "detail"];
  $strInputType = getComboFromArray($arrTmp, "dataType", $strDataType);
  // informasi tanggal kehadiran
  if ($strDataDateFrom == $strDataDateThru) {
    $strInfo .= strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
  } else {
    $strInfo .= strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
    $strInfo .= " &raquo; " . strtoupper(pgDateFormat($strDataDateThru, "d-M-Y"));
  }
  $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDataDateFrom\">";
  $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDataDateThru\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  $strHidden .= "<input type=hidden name=dataWilayah value=\"$strDataWilayah\">";
  $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
  $strHidden .= "<input type=hidden name=dataType value=\"$strDataType\">";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
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