<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
include_once('cls_annual_leave.php');
include_once('../global/employee_function.php');
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
  die(getWords('view denied'));
}
// ubah tgl 2-11-2012
$strWordsEntryAbsence = getWords("absence entry");
$strWordsAbsenceList = getWords("absence list");
$strWordsEntryPartialAbsence = getWords("partial absence entry");
$strWordsPartialAbsenceList = getWords("partial absence list");
$strWordsAnnualLeave = getWords("annual leave");
$strWdShow = getWords("show");
$strWdPrint = getWords("print");
$strWdPrintAll = getWords("print all");
$strWdExcel = getWords("excel");
$strWdReset = getWords("reset");
$strListTop = getWords("list of employee annual leave");
$strEmpID = getWords("employee id");
$strEmpName = getWords("employee name");
$strStatus = getWords("status");
$strJoinDate = getWords("join date");
$strNoS = getWords("# of Service");
$strPrevYear = getWords("prev year");
$strThisYear = getWords("this year");
$strTotal = getWords("total");
$strRemain = getWords("remain");
$strYears = getWords("year");
$strQuota = getWords("quota");
$strAddQuota = getWords("add quota");
$strHoliday = getWords("holiday");
$strTaken = getWords("taken");
$strAddTaken = getWords("add taken");
// end ubah tgl 2-11-2012
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintAll']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strStyle = "";
$strPaging = "";
$strHidden = "";
$strBtnPrint = "";
$strBtnSave = "";
$strBtnSave1 = "";
$intTotalData = 0;
$bolLimit = true; // default, tampilan dibatasi (paging)
$strYear = date("Y") - 1;
//$strPrevYear1 = $strYear - 2;
//$strPrevYear2 = $strYear - 1;
$strWordsCompany = getWords("company");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("sub section");
$strWordsEmployeeID = getWords("employee id");
$strWordsAnnualLeave = getWords("annual leave");
$strWordsEntryAbsence = getWords("entry absence");
$strWordsAbsenceList = getWords("absence list");
$strWordsShow = getWords("show");
$strWordsPrint = getWords("print");
$strWordsPrintAll = getWords("print all");
$strWordsEmployeeId = getWords("employee id");
$strMessage = "";
$strMsgClass = "";
$emplId = getWords("empl. id");
$emplName = getWords("employee name");
$status = getWords("status");
$joinDate = getWords("join date");
$PrevYear = getWords("prev. year");
$thisYear = getWords("this year");
$total = getWords("total");
$remain = getWords("remain");
$year = getWords("year");
$quota = getWords("quota");
$holiday = getWords("holiday");
$taken = getWords("taken");
$strShowImport = " style = \"display:none\" ";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getData($db, &$intRows, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
{
  global $words;
  global $dataPrivilege;
  global $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge;
  global $bolPrint;
  global $ARRAY_EMPLOYEE_STATUS;
  global $strPaging;
  global $intTotalData;
  global $intRowsLimit;
  global $strFilterYear;
  $intRowsLimit = getSetting("rows_per_page");
  if (!is_numeric($intRowsLimit)) {
    $intRowsLimit = 50;
  }
  $intRows = 0;
  $strResult = "";
  $strDateFrom = "";
  $strDateThru = "";
  // cari total data
  $intTotal = 0;
  $strSQL = "SELECT count(he.id) AS total FROM hrd_employee AS he ";
  $strSQL .= "WHERE he.join_date is not null AND he.active = 1 $strKriteria "; // hanya ambil yang statusnya permanent
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
  $objLeave = new clsAnnualLeave($db);
  $strSQL = "SELECT he.id, he.employee_id, he.employee_name, he.gender, he.section_code, hs.section_name, he.employee_status, ";
  $strSQL .= "EXTRACT(YEAR FROM AGE(join_date)) AS durasi, ";
  $strSQL .= "EXTRACT(MONTH FROM join_date) AS bulan, ";
  $strSQL .= "EXTRACT(YEAR FROM join_date) AS tahun, ";
  $strSQL .= "join_date, resign_date, (EXTRACT(YEAR FROM AGE(birthday))) AS umur ";
  $strSQL .= "FROM hrd_employee AS he LEFT JOIN hrd_section AS hs ON hs.section_code = he.section_code ";
  $strSQL .= "WHERE join_date is not null AND active = 1 ";
  $strSQL .= " $strKriteria ORDER BY $strOrder employee_id ";
  if ($bolLimit) {
    $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $dataValidAdditional = ""; //tgl valid until untuk additional cuti
    $intRows++;
    $strGender = ($rowDb['gender'] == 0) ? "F" : "M";
    $strInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
    $tempInfo = $objLeave->arrHistory;
    $objLeave->generateEmployeeAnnualLeave($rowDb['id']);
    $arrCuti = $objLeave->getEmployeeLeaveInfo($rowDb['id']);
    $strPrevYear = trim($arrCuti['prev']['year']);
    $strCurrYear = trim($arrCuti['curr']['year']);
    //remark by uddin 20150109
    //$strPrevYearText = ($strPrevYear == "") ? "" : $strPrevYear."/".($strPrevYear + 1); // periode kerja yang menimbulkan cuti
    //$strCurrYearText = ($strCurrYear == "") ? "" : $strCurrYear."/".($strCurrYear + 1);
    //$strCurrLeaveText = ($strCurrYear == "") ? "&nbsp;" : ($strCurrYear)."/".($strCurrYear + 1);
    //$strPrevLeaveText = ($strPrevYear == "") ? "&nbsp;" : ($strPrevYear)."/".($strPrevYear + 1); // periode cuti
    // uddin 20150109
    $strPrevYearText = ($strPrevYear == "") ? "" : $strPrevYear; // periode kerja yang menimbulkan cuti
    $strCurrYearText = ($strCurrYear == "") ? "" : $strCurrYear;
    $strCurrLeaveText = ($strCurrYear == "") ? "&nbsp;" : $strCurrYear;
    $strPrevLeaveText = ($strPrevYear == "") ? "&nbsp;" : $strPrevYear; // periode cuti
    //end
    $intLeaveRemain = 0;
    $intLeaveQuotaPrev = $arrCuti['prev']['quota'];
    $intLeaveQuotaCurr = $arrCuti['curr']['quota'];
    $intLeaveRemainCurr = $intLeaveRemainPrev = 0;
    $intLeaveRemainAddCurr = $intLeaveRemainAddPrev = 0;
    $intLeaveAdditionalQuotaCurr = $intLeaveAdditionalQuotaPrev = 0;
    $intLeaveAdditionalCurr = $intLeaveAdditionalPrev = 0;
    $dataPrevYear = $strPrevYear;
    $strPrevYear = substr($strPrevYear, 0, 4);
    $dataCurrYear = $strCurrYear;
    $strCurrYear = substr($strCurrYear, 0, 4);
    if (isset($tempInfo[$rowDb['id']][$strPrevYear]['additional_quota']) && $tempInfo[$rowDb['id']][$strPrevYear]['additional_quota'] != "") {
      $intLeaveAdditionalQuotaPrev = $tempInfo[$rowDb['id']][$strPrevYear]['additional_quota'];
    }
    if (isset($tempInfo[$rowDb['id']][$strCurrYear]['additional_quota']) && $tempInfo[$rowDb['id']][$strCurrYear]['additional_quota'] != "") {
      $intLeaveAdditionalQuotaCurr = $tempInfo[$rowDb['id']][$strCurrYear]['additional_quota'];
    }
    if (isset($tempInfo[$rowDb['id']][$strPrevYear]['additional']) && $tempInfo[$rowDb['id']][$strPrevYear]['additional'] != "") {
      $intLeaveAdditionalPrev = $tempInfo[$rowDb['id']][$strPrevYear]['additional'];
    }
    if (isset($tempInfo[$rowDb['id']][$strCurrYear]['additional']) && $tempInfo[$rowDb['id']][$strCurrYear]['additional'] != "") {
      $intLeaveAdditionalCurr = $tempInfo[$rowDb['id']][$strCurrYear]['additional'];
    }
    $strPrevYear = $dataPrevYear;
    $strCurrYear = $dataCurrYear;
    if ($intLeaveQuotaPrev == 0) {
      $intLeaveTakenPrev = 0; // anggap aja gak ada
      $intLeaveHolidayPrev = 0; //
      $intLeaveRemainPrev = 0;
      $intLeaveRemainAddPrev = 0;
      //tetap hrs hitung additional
      //$arrCuti['prev']['remain'] -= $intLeaveAdditionalPrev;
      //$arrCuti['prev']['remain'] += $intLeaveAdditionalQuotaPrev;
      $intLeaveHolidayPrev = $arrCuti['prev']['holiday'];
      $intLeaveRemainPrev = $arrCuti['prev']['remain'];
      $intLeaveRemainAddPrev = $arrCuti['prev']['remain_add'];
      $intLeaveRemain += $arrCuti['prev']['remain'];
    } else {
      if ($arrCuti['prev']['overdue'] == 't') {
        $arrCuti['prev']['remain'] -= $intLeaveAdditionalPrev;
        $arrCuti['prev']['remain'] += $intLeaveAdditionalQuotaPrev;
        $arrCuti['prev']['remain_add'] = $intLeaveAdditionalQuotaPrev - $intLeaveAdditionalPrev;
        $intLeaveTakenPrev = $arrCuti['prev']['taken'];
        $intLeaveHolidayPrev = "<strike>" . $arrCuti['prev']['holiday'] . "</strike>";
        $intLeaveRemainPrev = "<strike>" . $arrCuti['prev']['remain'] . "</strike>";
        $intLeaveRemainAddPrev = "<strike>" . $arrCuti['prev']['remain_add'] . "</strike>";
      } else {
        $arrCuti['prev']['remain'] -= $intLeaveAdditionalPrev;
        $arrCuti['prev']['remain'] += $intLeaveAdditionalQuotaPrev;
        $arrCuti['prev']['remain_add'] = $intLeaveAdditionalQuotaPrev - $intLeaveAdditionalPrev;
        $intLeaveTakenPrev = $arrCuti['prev']['taken'];
        $intLeaveHolidayPrev = $arrCuti['prev']['holiday'];
        $intLeaveRemainPrev = $arrCuti['prev']['remain'];
        $intLeaveRemainAddPrev = $arrCuti['prev']['remain_add'];
        $intLeaveRemain += $arrCuti['prev']['remain'];
      }
    }
    if ($intLeaveQuotaCurr == 0) {
      //$intLeaveTakenCurr = 0; // anggap aja gak ada
      $intLeaveTakenCurr = $arrCuti['curr']['taken'];
      $intLeaveHolidayCurr = 0; //
      $intLeaveRemainCurr = 0;
      $intLeaveRemainAddCurr = 0;
      //ttp harus hitung additional
      $arrCuti['curr']['remain'] -= $intLeaveAdditionalCurr;
      $arrCuti['curr']['remain'] += $intLeaveAdditionalQuotaCurr;
      $arrCuti['curr']['remain_add'] = $intLeaveAdditionalQuotaCurr - $intLeaveAdditionalCurr;
      $intLeaveHolidayCurr = $arrCuti['curr']['holiday'] . "";
      $intLeaveRemainCurr = $arrCuti['curr']['remain'] . "";
      $intLeaveRemainAddCurr = $arrCuti['curr']['remain_add'] . "";
      $intLeaveRemain += $arrCuti['curr']['remain'];
      $dataValidAdditional = $arrCuti['curr']['valid_until'];
    } else {
      $intLeaveTakenCurr = $arrCuti['curr']['taken'] . "";
      $intLeaveHolidayCurr = $arrCuti['curr']['holiday'] . "";
      $arrCuti['curr']['remain'] -= $intLeaveAdditionalCurr;
      $arrCuti['curr']['remain'] += $intLeaveAdditionalQuotaCurr;
      $arrCuti['curr']['remain_add'] = $intLeaveAdditionalQuotaCurr - $intLeaveAdditionalCurr;
      $intLeaveRemainCurr = $arrCuti['curr']['remain'] . "";
      $intLeaveRemainAddCurr = $arrCuti['curr']['remain_add'] . "";
      $intLeaveRemain += $arrCuti['curr']['remain'];
      $dataValidAdditional = $arrCuti['curr']['valid_until'];
    }
    //$intLeaveRemain = /*$intLeaveRemainPrev +*/ $intLeaveRemainCurr;
    if ($arrCuti['prev']['quota'] == 0 && $arrCuti['curr']['quota'] == 0 && !$bolPrint) {
      $strClass = "class=bgDenied";
    } else {
      $strClass = "";
    }
    $strResult .= "<tr valign=top title=\"$strInfo\" $strClass>\n";
    if (!$bolPrint) {
      //$strResult .= "  <td><input type=checkbox name=chkID$intRows value=\"" .$rowDb['id']."\"></td>";
    }
    $strDisabledPrev = ($rowDb['employee_status'] == STATUS_OUTSOURCE) ? "disabled" : "";
    $strReadonlyPrev = ($arrCuti['prev']['overdue'] == 't') ? "readonly" : "";
    $strEmployeeStatus = getWords($ARRAY_EMPLOYEE_STATUS[$rowDb['employee_status']]);
    $strLeaveQuotaPrev = ($bolPrint || !$bolCanEdit) ? "$intLeaveQuotaPrev &nbsp;" : "<input type=text size=5 maxlength=10 class='numeric form-control' name=detailQuotaPrev$intRows value=\"$intLeaveQuotaPrev\" $strDisabledPrev $strReadonlyPrev>";
    $strLeaveQuotaCurr = ($bolPrint || !$bolCanEdit) ? "$intLeaveQuotaCurr &nbsp;" : "<input type=text size=5 maxlength=10 class='numeric form-control' name=detailQuotaCurr$intRows value=\"$intLeaveQuotaCurr\">";
    $strLeaveTakenPrev = ($bolPrint || !$bolCanEdit) ? "$intLeaveTakenPrev &nbsp;" : "<input type=text size=5 maxlength=10 class='numeric form-control' name=detailTakenPrev$intRows value=\"$intLeaveTakenPrev\" $strDisabledPrev $strReadonlyPrev>";
    $strLeaveTakenCurr = ($bolPrint || !$bolCanEdit) ? "$intLeaveTakenCurr &nbsp;" : "<input type=text size=5 maxlength=10 class='numeric form-control' name=detailTakenCurr$intRows value=\"$intLeaveTakenCurr\">";
    $strPrevYear = ($bolPrint || !$bolCanEdit) ? "&nbsp;$strPrevLeaveText" : "&nbsp;$strPrevLeaveText<input type=hidden name=detailYearPrev$intRows value='$strPrevYear'>";
    $strCurrYear = ($bolPrint || !$bolCanEdit) ? "&nbsp;$strCurrLeaveText" : "&nbsp;$strCurrLeaveText<input type=hidden name=detailYearCurr$intRows value='$strCurrYear'>";
    $strLeaveAdditionalQuotaPrev = ($bolPrint || !$bolCanEdit) ? "$intLeaveAdditionalQuotaPrev &nbsp;" : "<input type=text size=5 maxlength=10 class='numeric form-control' name=detailAdditionalQuotaPrev$intRows value=\"$intLeaveAdditionalQuotaPrev\">";
    $strLeaveAdditionalQuotaCurr = ($bolPrint || !$bolCanEdit) ? "$intLeaveAdditionalQuotaCurr &nbsp;" : "<input type=text size=5 maxlength=10 class='numeric form-control' name=detailAdditionalQuotaCurr$intRows value=\"$intLeaveAdditionalQuotaCurr\">";
    $strLeaveAdditionalPrev = ($bolPrint || !$bolCanEdit) ? "$intLeaveAdditionalPrev &nbsp;" : "<input type=text size=5 maxlength=10 class='numeric form-control' name=detailAdditionalPrev$intRows value=\"$intLeaveAdditionalPrev\">";
    $strLeaveAdditionalCurr = ($bolPrint || !$bolCanEdit) ? "$intLeaveAdditionalCurr &nbsp;" : "<input type=text size=5 maxlength=10 class='numeric form-control' name=detailAdditionalCurr$intRows value=\"$intLeaveAdditionalCurr\">";
    $strHiddenID = ($bolPrint || !$bolCanEdit) ? "" : "<input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">";
    $strResult .= "  <td nowrap>$strHiddenID" . $rowDb['employee_id'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap>" . $rowDb['employee_name'] . "&nbsp;</td>";
    //$strResult .= "  <td align=center>$strGender&nbsp;</td>";
    $strResult .= "  <td align=center>$strEmployeeStatus&nbsp;</td>";
    $strResult .= "  <td align=right>" . pgDateFormat($rowDb['join_date'], "d-M-Y") . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $rowDb['durasi'] . "</td>";
    //$strResult .= "  <td>" .$rowDb['section_name']. "&nbsp;</td>";
    /*$strResult .= "  <td align=center>$strPrevYear</td>";
    $strResult .= "  <td align=right>$intLeaveQuotaPrev</td>";
    $strResult .= "  <td align=right>$intLeaveHolidayPrev</td>";
    $strResult .= "  <td align=right>$strLeaveTakenPrev&nbsp;</td>";
    $strResult .= "  <td align=right>$intLeaveRemainPrev&nbsp;</td>";*/
    //$strResult .= "  <td align=center title='" .$arrCuti['prev']['finish']."'>$strPrevYear</td>";
    $strResult .= "  <td align=center title='" . $arrCuti['prev']['finish'] . "'>$strPrevYear </td>";
    $strResult .= "  <td align=right>$strLeaveQuotaPrev</td>";
    $strResult .= "  <td align=right>$strLeaveAdditionalQuotaPrev</td>";
    $strResult .= "  <td align=right>$intLeaveHolidayPrev</td>";
    $strResult .= "  <td align=right>$strLeaveTakenPrev</td>";
    $strResult .= "  <td align=right>$strLeaveAdditionalPrev</td>";
    //$strResult .= "  <td align=right>$intLeaveAdditionalPrev&nbsp;</td>";
    $strResult .= "  <td align=right>$intLeaveRemainPrev</td>";
    //$strResult .= "  <td align=right>$intLeaveRemainAddPrev</td>";
    //$strResult .= "  <td align=center title='" .$arrCuti['curr']['finish']."'>$strCurrYear</td>";
    $strResult .= "  <td align=center title='" . $arrCuti['curr']['finish'] . "'>$strCurrYear</td>";
    $strResult .= "  <td align=right>$strLeaveQuotaCurr</td>";
    $strResult .= "  <td align=right>$strLeaveAdditionalQuotaCurr</td>";
    $strResult .= "  <td align=right>$intLeaveHolidayCurr</td>";
    $strResult .= "  <td align=right>$strLeaveTakenCurr</td>";
    $strResult .= "  <td align=right>$strLeaveAdditionalCurr</td>";
    //$strResult .= "  <td align=right>$intLeaveAdditionalCurr&nbsp;</td>";
    $strResult .= "  <td align=right>$intLeaveRemainCurr</td>";
    //$strResult .= "  <td align=right>$intLeaveRemainAddCurr</td>";
    $strResult .= "  <td align=right>$dataValidAdditional</td>";
    //  di remark karena tidak dibutuhkan
    //  $strResult .= "  <td align=right><strong>$intLeaveRemain</strong>&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  $intTotalData = $intRows;
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  return $strResult;
} // showData
//----------------------------------------------------------------------
// fungsi untuk menyimpan data, dalam kasus ini adalha data jatah/quota cuti per tahun
function saveData($db, $type = 0)
{
  global $_SESSION;
  $strUpdaterID = $_SESSION['sessionUserID'];
  //$intYear = (isset($_REQUEST['dataYear'])) ? $_REQUEST['dataYear'] : "";
  //$intYearPrev = $intYear - 1;
  $intTotal = (isset($_REQUEST['dataTotal'])) ? $_REQUEST['dataTotal'] : 0;
  for ($i = 1; $i <= $intTotal; $i++) {
    $strID = (isset($_REQUEST['detailID' . $i])) ? $_REQUEST['detailID' . $i] : "";
    $strCurrYear = (isset($_REQUEST['detailYearCurr' . $i])) ? trim($_REQUEST['detailYearCurr' . $i]) : "";
    $strPrevYear = (isset($_REQUEST['detailYearPrev' . $i])) ? trim($_REQUEST['detailYearPrev' . $i]) : "";
    $intCurr = (isset($_REQUEST['detailTakenCurr' . $i])) ? $_REQUEST['detailTakenCurr' . $i] : 0;
    $intPrev = (isset($_REQUEST['detailTakenPrev' . $i])) ? $_REQUEST['detailTakenPrev' . $i] : 0;
    $intQuotaCurr = (isset($_REQUEST['detailQuotaCurr' . $i])) ? $_REQUEST['detailQuotaCurr' . $i] : 0;
    $intQuotaPrev = (isset($_REQUEST['detailQuotaPrev' . $i])) ? $_REQUEST['detailQuotaPrev' . $i] : 0;
    $intLeaveAdditionalQuotaPrev = (isset($_REQUEST['detailAdditionalQuotaPrev' . $i])) ? $_REQUEST['detailAdditionalQuotaPrev' . $i] : 0;
    $intLeaveAdditionalQuotaCurr = (isset($_REQUEST['detailAdditionalQuotaCurr' . $i])) ? $_REQUEST['detailAdditionalQuotaCurr' . $i] : 0;
    $intLeaveAdditionalPrev = (isset($_REQUEST['detailAdditionalPrev' . $i])) ? $_REQUEST['detailAdditionalPrev' . $i] : 0;
    $intLeaveAdditionalCurr = (isset($_REQUEST['detailAdditionalCurr' . $i])) ? $_REQUEST['detailAdditionalCurr' . $i] : 0;
    // simpan data
    $strSQL = "";
    // simpan data jika angkanya benar
    $strSQL = "";
    // simpan data jika angkanya benar
    if (is_numeric($intPrev) && $type == 0 && $strPrevYear != "") { //update yang prev
      $strSQL = "UPDATE \"hrd_leave_history\" SET \"used\" = '$intPrev', \"total\" = '$intQuotaPrev'  ";
      $strSQL .= "WHERE \"year\" = $strPrevYear AND \"id_employee\" = '$strID' ";
      $resExec = $db->execute($strSQL);
    } else if (is_numeric($intCurr) && $type == 1 && $strCurrYear != "") {
      $strSQL = "UPDATE \"hrd_leave_history\" SET \"used\" = '$intCurr', \"total\" = '$intQuotaCurr'  ";
      $strSQL .= "WHERE \"year\" = $strCurrYear AND \"id_employee\" = '$strID' ";
      $resExec = $db->execute($strSQL);
    }
    if (is_numeric($intLeaveAdditionalPrev) && is_numeric($intLeaveAdditionalQuotaPrev) && $strPrevYear != "") {
      $strSQL = "SELECT * FROM \"hrd_leave_additional\" WHERE \"year\" = $strPrevYear AND \"id_employee\" = '$strID'";
      $resExec = $db->execute($strSQL);
      if ($db->numrows($resExec) == 1) {
        $strSQL = "DELETE FROM \"hrd_leave_additional\" ";
        $strSQL .= "WHERE \"year\" = $strPrevYear AND \"id_employee\" = '$strID' ";
        $resExec = $db->execute($strSQL);
      }
      $strSQL = "INSERT INTO \"hrd_leave_additional\"(id_employee,year,additional,additional_quota) VALUES ('$strID', $strPrevYear, '$intLeaveAdditionalPrev', '$intLeaveAdditionalQuotaPrev')";
      $resExec = $db->execute($strSQL);
    }
    if (is_numeric($intLeaveAdditionalCurr) && is_numeric($intLeaveAdditionalQuotaCurr) && $strCurrYear != "") {
      $strSQL = "SELECT * FROM \"hrd_leave_additional\" WHERE \"year\" = $strCurrYear AND \"id_employee\" = '$strID'";
      $resExec = $db->execute($strSQL);
      if ($db->numrows($resExec) == 1) {
        $strSQL = "DELETE FROM \"hrd_leave_additional\" ";
        $strSQL .= "WHERE \"year\" = $strCurrYear AND \"id_employee\" = '$strID' ";
        $resExec = $db->execute($strSQL);
      }
      $strSQL = "INSERT INTO \"hrd_leave_additional\"(id_employee,year,additional,additional_quota) VALUES ('$strID', $strCurrYear, '$intLeaveAdditionalCurr', '$intLeaveAdditionalQuotaCurr')";
      $resExec = $db->execute($strSQL);
    }
  }
  writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "", 0);
  return true;
}

function resetData($db)
{
  global $strKriteria;
  $objLeave = new clsAnnualLeave($db);
  $strSQL = "SELECT he.id, he.employee_id, he.employee_name, he.gender, he.section_code, hs.section_name, he.employee_status, ";
  $strSQL .= "EXTRACT(YEAR FROM join_date) AS tahun, ";
  $strSQL .= "join_date, resign_date, (EXTRACT(YEAR FROM AGE(birthday))) AS umur ";
  $strSQL .= "FROM hrd_employee AS he LEFT JOIN hrd_section AS hs ON hs.section_code = he.section_code ";
  $strSQL .= "WHERE join_date is not null AND active = 1 ";
  $strSQL .= " $strKriteria ORDER BY employee_id ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $strGender = ($rowDb['gender'] == 0) ? "F" : "M";
    $strID = $rowDb['id'];
    $strThisYear = date("Y");
    $strStartCurr = $objLeave->getStartPeriod(
        $strThisYear,
        $rowDb['join_date']
    ); // ambil periode awal untuk tahun sekarang
    // bandingkan, apakah sekarang sudah melewati masa join date
    if ($strStartCurr > $objLeave->strDate) {
      $strThisYear--;
    }
    $objLeave->saveLeaveHistory($strID, $strThisYear - 1);
    $objLeave->saveLeaveHistory($strID, $strThisYear);
    $objLeave->saveLeaveHistory($strID, $strThisYear + 1);
    //die ("cek dulu");
  }
}

// proses import data
function importData($db)
{
  include_once('../global/excelReader/reader.php');
  global $_FILES;
  global $_REQUEST;
  global $_SESSION;
  global $strResult;
  global $messages;
  $strError = "";
  $strUpdaterID = $_SESSION['sessionUserID'];
  $intExcelDate2000 = 36526; // nilai integer dari tanggal 01-01-2000,
  // untuk konversi tanggal dari excel (integer)
  $intTotal = 0;
  $strTotalResultForm = 0;
  $strTotalResultDetail = 0;
  if (is_uploaded_file($_FILES["fileData"]['tmp_name'])) {
    //-- baca file Excel
    $data = new Spreadsheet_Excel_Reader();
    $data->setOutputEncoding('CP1251');
    $data->read($_FILES["fileData"]['tmp_name']);
    //--- MULAI BACA DATA DARI FILE EXCEL -------
    $intCols = 56; // default ada segini
    $intRows = $data->sheets[0]['numRows']; // total baris
    $ok = 0;
    $arrEmp = []; // menampung daftar karyawan yagn sudah ada
    if ($intRows > 1) {
      // baca dulu daftar karyawan
      $strSQL = "SELECT id, employee_id FROM hrd_employee ";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) {
        $arrEmp[$rowDb['employee_id']] = $rowDb['id'];
      }
    }
    $arrEmpUnknown = []; // mencatat daftar employee ID yang gak teradftar
    $arrForm = []; // mencatat daftar form yang sudah ada, indexnya adalah form number
    // UPDATE DULU SISA CUTI YG ADA
    //$intRows = 15;
    for ($i = 7; $i <= $intRows; $i++) {
      // tampung di variabel, biar pendek codingnya :D
      $arrData = (isset($data->sheets[0]['cells'][$i])) ? $data->sheets[0]['cells'][$i] : [];
      // baca data satu persatu
      $strNo = (isset($arrData[1])) ? trim($arrData[1]) : "";
      $stremployee_id = (isset($arrData[2])) ? trim($arrData[2]) : "";
      $strName = (isset($arrData[3])) ? addslashes(trim($arrData[3])) : "";
      $strRemain = (isset($arrData[24])) ? (trim($arrData[24])) : "";
      // validasi dan handle data
      if ($stremployee_id != "" && is_numeric($strRemain)) {
        if (isset($arrEmp[$stremployee_id])) {
          $strIDEmployee = $arrEmp[$stremployee_id];
          $arrCuti = getEmployeeLeaveQuota($db, $strIDEmployee);
          // cek total penggunaan ini
          //$intPrevTaken = 0;
          $intCurrTaken = 0;
          /*
          if (($arrCuti['prevQuota'] - $arrCuti['prevHoliday']) >= $strRemain) {
            // tahun lalu sudah habis, tahun ini sudah ada yang kepakai
            $intPrevTaken = $arrCuti['prevQuota'] - $arrCuti['prevHoliday'];

            $intCurrTaken = $arrCuti['currQuota'] - $arrCuti['currHoliday'] - $strRemain;
            echo "awal";
          } else {
            echo "else";
            // tahun ini masih utuh/lebih
            $intCurrTaken = 0;
            $strPrevRemain = $strRemain - ($arrCuti['currQuota'] - $arrCuti['currHoliday']);
            $intPrevTaken = $arrCuti['prevQuota'] - $arrCuti['prevHoliday'] - $strPrevRemain;
          }
          */
          // cek jatah tahun ini, jika sisa > jatah, berarti gak ada pemakaian
          if (($arrCuti['currQuota'] - $arrCuti['currHoliday']) <= $strRemain) {
            // gak ada pemakaian bulan ini
            $intCurrTaken = 0;
            //$intPrevRemain = ($strRemain - ($arrCuti['currQuota'] - $arrCuti['currHoliday']));
            //$intPrevTaken = $arrCuti['prevQuota'] - $arrCuti['prevHoliday'] - $intPrevRemain;
          } else {
            // ada pemakaian bulan ini
            $intCurrTaken = ($arrCuti['currQuota'] - $arrCuti['currHoliday']) - $strRemain;
            //$intPrevTaken = $arrCuti['fprevQuota'] - $arrCuti['prevHoliday'];
          }
          // simpan hasil
          $strSQL = "";
          /*if ($arrCuti['prevYear'] != "") {
            $strSQL .= "UPDATE hrd_leave_history SET used = '$intPrevTaken' ";
            $strSQL .= "WHERE id_employee = '$strIDEmployee' ";
            $strSQL .= "AND \"year\" = '" .$arrCuti['prevYear']."'; ";
          }*/
          if ($arrCuti['currYear'] != "") {
            $strSQL .= "UPDATE hrd_leave_history SET used = '$intCurrTaken' ";
            $strSQL .= "WHERE id_employee = '$strIDEmployee' ";
            $strSQL .= "AND \"year\" = '" . $arrCuti['currYear'] . "'; ";
          }
          if ($strSQL != "") {
            $resExec = $db->execute($strSQL);
          }
          $strTotalResultDetail++;
        } else {
          if (!in_array($stremployee_id, $arrEmpUnknown)) {
            $arrEmpUnknown[] = $stremployee_id;
          }
        }
      }
    }
    if ($ok > 0) {
      writeLog(ACTIVITY_IMPORT, MODULE_PAYROLL, "$strTotalResultDetail data", 0);
    }
    //$strResult = $messages['data_saved'] ." ". $ok. "/".$i;
    //$strResult .= " <br>".$strError;
    // MULAI UPDATE DATA CUTI DAN ABSEN POTONG CUTI YG SUDAH ADA
    $strSQL = "SELECT * FROM hrd_leave WHERE status >= '" . REQUEST_STATUS_APPROVED . "' ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $intTotal = totalWorkDay($db, $rowDb['date_from'], $rowDb['date_thru']);
      $strYear = updateEmployeeLeave($db, $rowDb['id_employee'], $intTotal);
    }
    // ambil dulu jenis absen
    $arrAbsType = [];
    $strSQL = "SELECT * FROM hrd_absence_type ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrAbsType[$rowDb['code']] = $rowDb;
    }
    // UPdATE CUTI DARI ABSEN YG APPROVED DAN POTONG CUTI
    $strSQL = "SELECT * FROM hrd_absence WHERE status >= '" . REQUEST_STATUS_APPROVED . "' ";
    $strSQL .= "AND leave_year <> '' AND leave_year is not null ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $intTotal = 0;
      if (isset($arrAbsType[$rowDb['absence_type_code']])) {
        if ($arrAbsType[$rowDb['absence_type_code']]['isleave'] == 't') {
          // potong cuti
          $intTotal = totalWorkDay($db, $rowDb['date_from'], $rowDb['date_thru']);
        }
      } else {
        $intTotal = $rowDb['leaveDuration'];
      }
      $strYear = updateEmployeeLeave($db, $rowDb['id_employee'], $intTotal);
    }
    // update dari absen yg DENIED
    // UPdATE CUTI DARI ABSEN YG APPROVED DAN POTONG CUTI
    $strSQL = "SELECT * FROM hrd_absence WHERE status = '" . REQUEST_STATUS_DENIED . "' ";
    $strSQL .= "AND leave_year <> '' AND leave_year is not null ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $intTotal = 0;
      if (isset($arrAbsType[$rowDb['absence_type_code']])) {
        //if ($arrAbsType[$rowDb['absence_type_code']]['isleave'] == 't')
        // potong cuti semua
        $intTotal = totalWorkDay($db, $rowDb['date_from'], $rowDb['date_thru']);
      } else {
        $intTotal = $rowDb['leaveDuration'];
      }
      $strYear = updateEmployeeLeave($db, $rowDb['id_employee'], $intTotal);
    }
    // tampilkan employee ID yang error
    $strErrorID = implode(", ", $arrEmpUnknown);
    $strResultStyle = "";
  }
  //fclose($handle);
  // tampilkan alert
  /*
  showMessage("Finish ...");
  print_r($arrEmpUnknown);
  echo "<script language='Javascript'>alert('Process Done! $strTotalResultForm | $strTotalResultDetail data updated!')</script>";
  */
} //importData
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  $strButtonPrintAllDisplay = 'hidden';
  $strButtonResetDisplay = 'hidden';
  if ($arrUserInfo['display_print_all']) {
    $strButtonPrintAllDisplay = '';
  }
  if ($arrUserInfo['display_reset']) {
    $strButtonResetDisplay = '';
  }
  $strFilterYear = (isset($_REQUEST['filterYear'])) ? $_REQUEST['filterYear'] : date("Y");
  // ------ AMBIL DATA KRITERIA -------------------------
  $strFilteremployee_id = (isset($_REQUEST['filteremployee_id'])) ? trim($_REQUEST['filteremployee_id']) : "";
  $strFilterDivision = (isset($_REQUEST['filterDivision'])) ? $_REQUEST['filterDivision'] : "";
  $strFilterDepartment = (isset($_REQUEST['filterDepartment'])) ? $_REQUEST['filterDepartment'] : "";
  $strFilterSection = (isset($_REQUEST['filterSection'])) ? $_REQUEST['filterSection'] : "";
  $strFilterSubsection = (isset($_REQUEST['filterSubsection'])) ? $_REQUEST['filterSubsection'] : "";
  $intCurrPage = (isset($_REQUEST['dataPage'])) ? $_REQUEST['dataPage'] : 1;
  scopeData(
      $strDataEmployee,
      $strFilterSubsection,
      $strFilterSection,
      $strFilterDepartment,
      $strFilterDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  if (!is_numeric($intCurrPage)) {
    $intCurrPage = 1;
  }
  $strBtnPrint = "<input type=button name='btnPrint' value=\"" . $words['print'] . "\" onClick=\"printData($intCurrPage);\">";
  //$_REQUEST['btnShow'] = "Show"; // langsung tampil
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if (isset($_REQUEST['btnShowAll']) || isset($_REQUEST['btnPrintAll'])) {
    $strKriteria = "";
    $bolLimit = false;
  } else if (isset($_REQUEST['btnReset']) || isset($_REQUEST['btnShow']) || $bolPrint) {
    $strInfoKriteria = "";
    if ($strFilteremployee_id != "") {
      $strKriteria .= "AND employee_id = '$strFilteremployee_id' ";
    }
    if ($strFilterDivision != "") {
      $strKriteria .= "AND he.division_code = '$strFilterDivision' ";
    }
    if ($strFilterDepartment != "") {
      $strKriteria .= "AND he.department_code = '$strFilterDepartment' ";
    }
    if ($strFilterSection != "") {
      $strKriteria .= "AND he.section_code = '$strFilterSection' ";
    }
    if ($strFilterSubsection != "") {
      $strKriteria .= "AND he.sub_section_code = '$strFilterSubsection' ";
    }
    $strKriteria .= $strKriteriaCompany;
    //uddin: tambah kriteria jika employee maka yg muncul employee yg functional dia dan dibawahnya
    $strDataUserRole = $_SESSION['sessionUserRole'];
    if ($strDataUserRole == ROLE_EMPLOYEE or $strDataUserRole == ROLE_SUPERVISOR) {
      if ($arrUserInfo["functional_code"] != "") {
        //$strSQL="SELECT * FROM hrd_functional WHERE head_code='".$arrUserInfo["functional_code"]."'";
        $strSQL = "SELECT t1.*,t2.employee_id FROM hrd_functional as t1
                    LEFT JOIN  (select * from hrd_employee " . $strKriteriaDiv . ") as t2 ON t1.functional_code=t2.functional_code
                    WHERE t1.head_code='" . $arrUserInfo["functional_code"] . "'";
        $resDb = $db->execute($strSQL);
        // $strFunctionalcode="('".$arrUserInfo["functional_code"]."'"; // inisial masukkan kode functional diri sendiri
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
        //$strKriteria .= " AND functional_code in ".$strFunctionalcode." ";
        $strKriteria .= " AND (he.functional_code in " . $strFunctionalcode . " or he.employee_id='" . $arrUserInfo["employee_id"] . "') ";
      }
    }
    // end tambah kriteria functional code
  } else { // jngan tampilkan data
    $strKriteria .= "AND 1=2 ";
    $strBtnPrint = ""; // tidak perlu tampil
    $strBtnSave = ""; // tidak perlu tampil
  }
  /*    if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
        $strDisabled = "disabled";
        if ($arrUserInfo['isDeptHead']) {
          $strFilterDepartment = $arrUserInfo['department_code'];
          $strKriteria .= "AND department_code = '$strFilterDepartment' ";
        } else if ($arrUserInfo['isGroupHead']) {
          $strFilterSection = $arrUserInfo['section_code'];
          $strKriteria .= "AND section_code = '$strFilterSection' ";
        } else {
          $strFilteremployee_id = $arrUserInfo['employee_id'];
          $strKriteria .= "AND upper(employee_id) = '" .strtoupper($strFilteremployee_id). "' ";

        }
      }*/
  if ($bolCanEdit) {
    $strShowImport = "";
    $strBtnSave = "<input type=submit name='btnSave' value=\"" . $words['save'] . "\" onClick=\"return confirmSave()\">";
    $strBtnSave1 = "<input type=submit name='btnSave1' value=\"" . $words['save'] . "\" onClick=\"return confirmSave()\">";
    if (isset($_POST['btnSave'])) {
      $bolOK = saveData($db, 0);
      if ($bolOK) {
        $strMsgClass = "bgOK";
        $strMessage = $messages['data_saved'];
      }
      $_REQUEST['btnShow'] = "Show";
    } else if (isset($_POST['btnSave1'])) {
      $bolOK = saveData($db, 1);
      if ($bolOK) {
        $strMsgClass = "bgOK";
        $strMessage = $messages['data_saved'];
      }
      $_REQUEST['btnShow'] = "Show";
    } else if (isset($_POST['btnImport'])) {
      $bolOK = importData($db);
      if ($bolOK) {
        $strMsgClass = "bgOK";
        //$strMessage = $messages['data_saved'];
      }
      $_REQUEST['btnShow'] = "Show";
    } else if (isset($_POST['btnReset'])) {
      $bolOK = resetData($db);
      if ($bolOK) {
        $strMsgClass = "bgOK";
        //$strMessage = $messages['data_saved'];
      }
      $_REQUEST['btnShow'] = "Show";
    }
  }
  if ($bolCanView) {
    if (isset($_REQUEST['btnExcel'])) {
      $bolLimit = false;
    }
    $strDataDetail = getData($db, $intTotalData, $strKriteria, $intCurrPage, $bolLimit);
    if (isset($_REQUEST['btnExcel'])) {
      // ambil data CSS-nya
      if (file_exists("bw.css")) {
        $strStyle = "bw.css";
      }
      $strPrintCss = "";
      $strPrintInit = "";
      headeringExcel("leave.xls");
    }
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  // generate data hidden input dan element form input
  $intDefaultWidth = 30;
  $intDefaultWidthPx = 200;
  $intDefaultHeight = 3;
  $strTmpKriteria = "WHERE 1=1 ";
  //uddin: untuk WAL semua employee supervisor
  //$strDataUserRole=$_SESSION['sessionUserRole'];
  // if ($strDataUserRole == ROLE_SUPERVISOR) {
  //  $bolIsEmployee = true;
  // }
  if ($bolIsEmployee) {
    $strFilteremployee_id = $arrUserInfo['employee_id'];
    $strEmployeeName = $arrUserInfo['employee_name'];
    $strInputFilteremployee_id = "<input class='form-control' type=text name=filteremployee_id id=filteremployee_id size=$intDefaultWidth value=\"$strFilteremployee_id\" readonly='readonly'>";
  } else {
    $strEmployeeName = ""; //isset($arrUserInfo['employee_name']) ? $arrUserInfo['employee_name'] : '';
    $strInputFilteremployee_id = "<input class='form-control' type=text name=filteremployee_id id=filteremployee_id size=$intDefaultWidth value=\"$strFilteremployee_id\">";
  }
  $strInputFilterYear = getYearList("filterYear", $strFilterYear);
  $strInputFilterDivision = getDivisionList(
      $db,
      "filterDivision",
      $strFilterDivision,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['division']
  );
  $strInputFilterDepartment = getDepartmentList(
      $db,
      "filterDepartment",
      $strFilterDepartment,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['department']
  );
  $strInputFilterSection = getSectionList(
      $db,
      "filterSection",
      $strFilterSection,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['section']
  );
  $strInputFilterSubsection = getSubSectionList(
      $db,
      "filterSubsection",
      $strFilterSubsection,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['sub_section']
  );
  //handle user company-access-right
  $strInputFilterCompany = getCompanyList(
      $db,
      "dataCompany",
      $strDataCompany,
      $strEmptyOption2,
      $strKriteria2,
      "style=\"width:$intDefaultWidthPx\" "
  );
  $strYear = $strFilterYear;
  $strYear .= "<input type=hidden name='dataYear' value='$strYear'>";
  //$strPrevYear  = $strYear - 1;
  //$strPrevYear .= "<input type=hidden name='dataYearPrev' value='" .($strYear - 1). "'>";
  $strHidden .= "<input type=hidden name=filteremployee_id value=\"$strFilteremployee_id\">";
  $strHidden .= "<input type=hidden name=filterDivision value=\"$strFilterDivision\">";
  $strHidden .= "<input type=hidden name=filterDepartment value=\"$strFilterDepartment\">";
  $strHidden .= "<input type=hidden name=filterSection value=\"$strFilterSection\">";
  $strHidden .= "<input type=hidden name=filterSubsection value=\"$strFilterSubsection\">";
  $strHidden .= "<input type=hidden name=filterYear value=\"$strFilterYear\">";
  $strHidden .= "<input type=hidden name=dataPage value=\"$intCurrPage\">";
  $strHidden .= "<input type=hidden name=dataTotal value=\"$intTotalData\">";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords(strtolower($dataPrivilege['menu_name']));
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = getWords('absence partial entry form');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = dataAbsenceSubmenu($strWordsAnnualLeave);
if ($bolPrint) {
  $strMainTemplate = getTemplate("leave_annual_print.html");
  $getTemplateFooter = file_get_contents(getTemplate("report_footer.html"));
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
