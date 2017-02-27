<?php
session_start();
include_once('global.php');
include_once('form_object.php');
include_once('overtime_func.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  header("location:login.php?dataPage=attendance_edit_recap.php");
  exit();
}
$bolCanView = getUserPermission("attendance_edit.php", $bolCanEdit, $bolCanDelete, $strError);
$bolPrint = (isset($_REQUEST['btnPrint']));
if ($bolPrint) {
  $strMainTemplate = getTemplate("attendance_edit_recap_print.html");
} else {
  $strTemplateFile = getTemplate("attendance_edit_recap.html");
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strPaging = "";
$strMessages = "";
$strMsgClass = "";
$strButtons = "";
$bolError = false;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData(
    $db,
    $strDateFrom,
    $strDateThru,
    &$intRows,
    $strKriteria = "",
    $strOptionShow = "",
    $intPage = 1,
    $bolLimit = true,
    $strOrder = ""
) {
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $strDefaultStart;
  global $strDefaultFinish;
  global $strPaging;
  global $intTotalData;
  global $intRowsLimit;
  global $bolPrint;
  $intRowsLimit = getSetting("rows_per_page");
  if (!is_numeric($intRowsLimit)) {
    $intRowsLimit = 50;
  }
  $intTipe = 0; // default hari normal
  // cari total data
  $intTotal = 0;
  $strSQL = "SELECT count(id) AS total FROM hrd_employee ";
  $strSQL .= "WHERE active=1 AND flag=0  AND onsite = 't' $strKriteria ";
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
  list($tahun, $bulan, $tanggal) = explode("-", $strDateFrom);
  $dtTmp = getdate(mktime(0, 0, 0, (int)$bulan, (int)$tanggal, $tahun));
  if ($dtTmp['wday'] == 5) { //hari jumat
    // hari jumat
    $intTipe = 1;
    if (($strDefaultFinish = substr(getSetting("friday_finish_time"), 0, 5)) == "") {
      $strDefaultFinish = "18:30";
    }
  } //
  // cari data attendance, jika ada
  $arrData = [];
  $strSQL = "SELECT * FROM hrd_attendance_recap ";
  $strSQL .= "WHERE date_from = '$strDateFrom' AND date_thru = '$strDateThru' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrData[$rowDb['id_employee']] = $rowDb;
  }
  $strSQL = "SELECT * FROM hrd_employee ";
  $strSQL .= "WHERE active=1  AND flag=0  AND onsite = 't' $strKriteria ORDER BY $strOrder employee_id ";
  if ($bolLimit) {
    //$strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    ($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
    ($rowDb['gender'] == "1") ? $strDisabledMonthly = "disabled" : $strDisabledMonthly = "";
    $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
    //---- CARI DATA KEHADIRAN --------------------------
    if (isset($arrData[$rowDb['id']])) {
      $intAtt = $arrData[$rowDb['id']]['attendance'];
      $intLate = $arrData[$rowDb['id']]['late'];
      $intEarly = $arrData[$rowDb['id']]['early'];
      $intOT1 = ($arrData[$rowDb['id']]['l1'] / 60);
      $intOT2 = ($arrData[$rowDb['id']]['l2'] / 60);
      $intOT3 = ($arrData[$rowDb['id']]['l3'] / 60);
      $intOT4 = ($arrData[$rowDb['id']]['l4'] / 60);
      $intTotalOT = $intOT1 + $intOT2 + $intOT3 + $intOT4;
    } else {
      $intAtt = 0;
      $intLate = 0;
      $intEarly = 0;
      $intOT1 = 0;
      $intOT2 = 0;
      $intOT3 = 0;
      $intOT4 = 0;
      $intTotalOT = 0;
    }
    // ----- TAMPILKAN DATA ---------------------------------------
    $strClass = "";
    $strSize = 5;
    $strMax = 10;
    if ($bolPrint) {
      $strResult .= "<tr valign=top>\n";
      $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>";
      $strResult .= "  <td nowrap>" . $rowDb['employee_id'] . "&nbsp;</td>";
      $strResult .= "  <td nowrap>" . $rowDb['employee_name'] . "&nbsp;</td>";
      $strResult .= "  <td>" . $rowDb['department_code'] . "&nbsp;</td>";
      $strResult .= "  <td>" . $rowDb['section_code'] . "&nbsp;</td>";
      $strResult .= "  <td align=right>$intAtt&nbsp;</td>";
      $strResult .= "  <td align=right>$intLate&nbsp;</td>";
      $strResult .= "  <td align=right>$intEarly&nbsp;</td>";
      $strResult .= "  <td align=right>$intOT1&nbsp;</td>";
      $strResult .= "  <td align=right>$intOT2&nbsp;</td>";
      $strResult .= "  <td align=right>$intOT3&nbsp;</td>";
      $strResult .= "  <td align=right>$intOT4&nbsp;</td>";
      $strResult .= "  <td align=right>$intTotalOT&nbsp;</td>";
      $strResult .= "</tr>\n";
    } else {
      $strResult .= "<tr valign=top id=detailData$intRows title=\"$strEmployeeInfo\" $strClass>\n";
      $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>";
      $strResult .= "  <td nowrap><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">" . $rowDb['employee_id'] . "&nbsp;</td>";
      $strResult .= "  <td nowrap>" . $rowDb['employee_name'] . "&nbsp;</td>";
      $strResult .= "  <td>" . $rowDb['department_code'] . "&nbsp;</td>";
      $strResult .= "  <td>" . $rowDb['section_code'] . "&nbsp;</td>";
      $strResult .= "  <td><input type=text name='detailAttendance$intRows' value='$intAtt' size=$strSize maxlength=$strMax class='numeric'></td>";
      $strResult .= "  <td><input type=text name='detailLate$intRows' value='$intLate' size=$strSize maxlength=$strMax class='numeric'></td>";
      $strResult .= "  <td><input type=text name='detailEarly$intRows' value='$intEarly' size=$strSize maxlength=$strMax class='numeric'></td>";
      $strAction = "onChange=\"getTotalOT($intRows)\"";
      $strResult .= "  <td><input type=text name='detailOT1$intRows' value='$intOT1' size=$strSize maxlength=$strMax class='numeric' $strAction></td>";
      $strResult .= "  <td><input type=text name='detailOT2$intRows' value='$intOT2' size=$strSize maxlength=$strMax class='numeric' $strAction></td>";
      $strResult .= "  <td><input type=text name='detailOT3$intRows' value='$intOT3' size=$strSize maxlength=$strMax class='numeric' $strAction></td>";
      $strResult .= "  <td><input type=text name='detailOT4$intRows' value='$intOT4' size=$strSize maxlength=$strMax class='numeric' $strAction></td>";
      $strResult .= "  <td><input type=text name='detailTotalOT$intRows' value='$intTotalOT' size=$strSize maxlength=$strMax disabled class='numeric'></td>";
      $strResult .= "</tr>\n";
    }
  }
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "Date=$strDateFrom:$strDateThru", 0);
  }
  return $strResult;
} // showData
// fungsi untuk menyimpan data yang dikirim
// $db = kelas database, $strError, pesan kesalahan atau pemberitahuan sukses
function saveData($db, &$strError)
{
  include_once('activity.php');
  global $words;
  global $messages;
  global $_SESSION;
  global $_REQUEST;
  $strError = "";
  (isset($_REQUEST['totalData'])) ? $intTotal = $_REQUEST['totalData'] : $intTotal = 0;
  (isset($_REQUEST['dataDateFrom'])) ? $strDataDateFrom = $_REQUEST['dataDateFrom'] : $strDataDateFrom = "";
  (isset($_REQUEST['dataDateThru'])) ? $strDataDateThru = $_REQUEST['dataDateThru'] : $strDataDateThru = "";
  // ---- VALIDASI ----
  if ($strDataDateFrom == "" || !validStandardDate($strDataDateFrom)) {
    $strError = "Error date";
    return false;
  } else if ($strDataDateThru == "" || !validStandardDate($strDataDateThru)) {
    $strError = "Error date";
    return false;
  }
  // cari data attendance, jika ada
  $arrData = [];
  $strSQL = "SELECT id, id_employee FROM hrd_attendance_recap ";
  $strSQL .= "WHERE date_from = '$strDataDateFrom' AND date_thru = '$strDataDateThru' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrData[$rowDb['id_employee']] = $rowDb['id'];
  }
  // ---------------- !!!!!!!!! -----------------------------------
  for ($i = 1; $i <= $intTotal; $i++) {
    $stremployee_id = isset($_REQUEST['detailID' . $i]) ? $_REQUEST['detailID' . $i] : "";
    if ($stremployee_id != "") {
      $intAtt = (isset($_REQUEST['detailAttendance' . $i])) ? $_REQUEST['detailAttendance' . $i] : 0;
      $intLate = (isset($_REQUEST['detailLate' . $i])) ? $_REQUEST['detailLate' . $i] : 0;
      $intEarly = (isset($_REQUEST['detailEarly' . $i])) ? $_REQUEST['detailEarly' . $i] : 0;
      $intOT1 = (isset($_REQUEST['detailOT1' . $i])) ? ($_REQUEST['detailOT1' . $i] * 60) : 0;
      $intOT2 = (isset($_REQUEST['detailOT2' . $i])) ? ($_REQUEST['detailOT2' . $i] * 60) : 0;
      $intOT3 = (isset($_REQUEST['detailOT3' . $i])) ? ($_REQUEST['detailOT3' . $i] * 60) : 0;
      $intOT4 = (isset($_REQUEST['detailOT4' . $i])) ? ($_REQUEST['detailOT4' . $i] * 60) : 0;
      $bolOK = true;
      if (!is_numeric($intAtt)) {
        $bolOK = false;
      } else if (!is_numeric($intLate)) {
        $bolOK = false;
      } else if (!is_numeric($intEarly)) {
        $bolOK = false;
      } else if (!is_numeric($intOT1)) {
        $bolOK = false;
      } else if (!is_numeric($intOT2)) {
        $bolOK = false;
      } else if (!is_numeric($intOT3)) {
        $bolOK = false;
      } else if (!is_numeric($intOT4)) {
        $bolOK = false;
      }
      if ($bolOK) {
        if (isset($arrData[$stremployee_id]) && $arrData[$stremployee_id] !== "") // ada
        {
          $strSQL = "UPDATE hrd_attendance_recap SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
          $strSQL .= "attendance = '$intAtt', late = '$intLate', early = '$intEarly', ";
          $strSQL .= "l1 = '$intOT1', l2 = '$intOT2', l3 = '$intOT3', l4 = '$intOT4' ";
          //$strSQL .= "WHERE id = '" .$arrData[$stremployee_id]['id']."' ";
          $strSQL .= "WHERE id_employee = '$stremployee_id' ";
          $strSQL .= "AND date_from = '$strDataDateFrom' AND date_thru = '$strDataDateThru' ";
          $resExec = $db->execute($strSQL);
        } else {
          $strSQL = "INSERT INTO hrd_attendance_recap (created, modified_by, ";
          $strSQL .= "id_employee, date_from, date_thru, ";
          $strSQL .= "attendance, late, early, l1, l2, l3, l4) ";
          $strSQL .= "VALUES (now(), '" . $_SESSION['sessionUserID'] . "', ";
          $strSQL .= "'$stremployee_id', '$strDataDateFrom', '$strDataDateThru', ";
          $strSQL .= "'$intAtt', '$intLate', '$intEarly', ";
          $strSQL .= "'$intOT1', '$intOT2', '$intOT3', '$intOT4') ";
          $resExec = $db->execute($strSQL);
        }
      }
    }
  }//for
  writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "Date=$strDataDateFrom - $strDataDateThru", 0);
  $strError = getWords('data_saved') . " >> " . date("r");
  return true;
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
//$intDefaultStart = "07:30";
//$intDefaultFinish = "16:30";
$db = new CdbClass;
if ($db->connect()) {
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      $bolError = !saveData($db, $strError);
      if ($strError != "") {
        //echo "<script>alert(\"$strError\");</script>";
        $strMessages = $strError;
        $strMsgClass = ($bolError) ? "bgError" : "bgOK";
      }
    }
  }
  getDefaultSalaryPeriode($strDefaultFrom, $strDefaultThru);
  // ------ AMBIL DATA KRITERIA -------------------------
  (isset($_REQUEST['dataDateFrom'])) ? $strDataDateFrom = $_REQUEST['dataDateFrom'] : $strDataDateFrom = $strDefaultFrom;
  (isset($_REQUEST['dataDateThru'])) ? $strDataDateThru = $_REQUEST['dataDateThru'] : $strDataDateThru = $strDefaultThru;
  $strDataDateFrom = pgDateFormat($strDataDateFrom, "Y-m-d");
  $strDataDateThru = pgDateFormat($strDataDateThru, "Y-m-d");
  (isset($_REQUEST['dataDivision'])) ? $strDataDivision = $_REQUEST['dataDivision'] : $strDataDivision = "";
  (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
  (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
  (isset($_REQUEST['dataSubsection'])) ? $strDataSubsection = $_REQUEST['dataSubsection'] : $strDataSubsection = "";
  (isset($_REQUEST['dataGroup'])) ? $strDataGroup = $_REQUEST['dataGroup'] : $strDataGroup = "";
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
  (isset($_REQUEST['dataEmployeeStatus'])) ? $strDataEmployeeStatus = $_REQUEST['dataEmployeeStatus'] : $strDataEmployeeStatus = "";
  (isset($_REQUEST['dataView'])) ? $strDataView = $_REQUEST['dataView'] : $strDataView = "";
  (isset($_REQUEST['dataPage'])) ? $intCurrPage = $_REQUEST['dataPage'] : $intCurrPage = 1;
  if (!is_numeric($intCurrPage)) {
    $intCurrPage = 1;
  }
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
  if ($strDataSubsection != "") {
    $strKriteria .= "AND sub_section_code = '$strDataSubsection' ";
  }
  if ($strDataGroup != "") {
    $strKriteria .= "AND \"groupCode\" = '$strDataGroup' ";
  }
  if ($strDataEmployee != "") {
    $strKriteria .= "AND employee_id = '$strDataEmployee' ";
  }
  if (is_numeric($strDataEmployeeStatus) && $strDataEmployeeStatus > 0) {
    $strKriteria .= ($strDataEmployeeStatus == 2) ? "AND employee_status = '" . STATUS_OUTSOURCE . "'" : "AND employee_status <> '" . STATUS_OUTSOURCE . "' ";
  }
  $bolShow = (isset($_REQUEST['btnShow']) || isset($_REQUEST['btnSave']) || $bolPrint);
  if ($bolCanView && ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN)) {
    if (validStandardDate($strDataDateFrom) && validStandardDate($strDataDateThru) && $bolShow) {
      // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
      $strDataDetail = getData(
          $db,
          $strDataDateFrom,
          $strDataDateThru,
          $intTotalData,
          $strKriteria,
          $strDataView,
          $intCurrPage
      );
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
  $strInputDateFrom = "<input type=text name=dataDateFrom id=dataDateFrom size=15 maxlength=10 value=\"$strDataDateFrom\">";
  $strInputDateThru = "<input type=text name=dataDateThru id=dataDateThru size=15 maxlength=10 value=\"$strDataDateThru\">";
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
  $strInputGroup = getGroupList(
      $db,
      "dataGroup",
      $strDataGroup,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\""
  );
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=20 maxlength=30 value=\"$strDataEmployee\" >";
  $arrTmp = ["", "employee", "outsource"];
  $strInputEmployeeStatus = getComboFromArray($arrTmp, "dataEmployeeStatus", $strDataEmployeeStatus);
  $arrViewOption = ["", "overtime", "late", "early leave"];
  $strInputView = "<select name=dataView style=\"width:$intDefaultWidthPx\">\n";
  foreach ($arrViewOption AS $idx => $str) {
    if ($idx == 0) {
      $strInputView .= "  <option value=''> </option>\n";
    } else {
      $strSelect = ($idx == $strDataView) ? "selected" : "";
      $strInputView .= "  <option value='$idx' $strSelect>" . $words[$str] . "</option>\n";
    }
  }
  $strButtons .= "<button name='btnSave' type='submit'>" . getWords("save") . "</button>";
  $strButtons .= " <button name='btnWork' type=button onClick =\"useWorkingDays()\">" . getWords(
          "use working days"
      ) . "</button>";
  $strInputView .= "</select>\n";
  // informasi tanggal kehadiran
  //$strHari = strtoupper(getDayName($strDataDateFrom));
  $strInfo .= "<br>" . strtoupper(pgDateFormat($strDataDateFrom, "d M Y"));
  if ($strDataDateFrom != $strDataDateThru) {
    $strInfo .= " - " . strtoupper(pgDateFormat($strDataDateThru, "d M Y"));
  }
  $intWorkDay = totalWorkDay($db, $strDataDateFrom, $strDataDateThru);
  $strInfo .= " [$intWorkDay day(s) ]";
  $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDataDateFrom\">";
  $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDataDateThru\">";
  $strHidden .= "<input type=hidden name=dataDivision value=\"$strDataDivision\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
  $strHidden .= "<input type=hidden name=dataSubsection value=\"$strDataSubsection\">";
  $strHidden .= "<input type=hidden name=dataGroup value=\"$strDataGroup\">";
  $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
  $strHidden .= "<input type=hidden name=dataEmployeeStatus value=\"$strDataEmployeeStatus\">";
  $strHidden .= "<input type=hidden name=dataView value=\"$strDataView\">";
  $strHidden .= "<input type=hidden name=dataWorkingDays value=\"$intWorkDay\">";
}
$strInitAction .= "
      document.formInput.dataDateFrom.focus();
      Calendar.setup({ inputField:\"dataDateFrom\", button:\"btnDateFrom\" });
      Calendar.setup({ inputField:\"dataDateThru\", button:\"btnDateThru\" });
      init();
      onCodeBlur();
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>