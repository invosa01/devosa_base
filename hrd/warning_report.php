<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strWordsDataEntry = getWords("data entry");
$strWordsWarningList = getWords("warning list");
$strWordsWarningReport = getWords("warning report");
$strWordsDateFrom = getWords("date from");
$strWordsDateThru = getWords("date to");
$strWordsEmployeeID = getWords("employee id");
$strWordsCompany = getWords("company");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsBand = getWords("band");
$strWordsMonth = getWords("month");
$strWordsFinishDate = getWords("finish date");
$strWordsEmployeeStatus = getWords("employee status");
$strWordsDelete = getWords("delete");
$strWordsStatus = getWords("status");
$strWordsShowData = getWords("show data");
$strWordsPrint = getWords("print");
$strWordsReportType = getWords("report type");
$strReportName = strtoupper(getWords("warning report"));
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strStyle = "";
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
// menampilkan data, digroup berdasar departemen
function showDataDetail($db)
{
  global $strDataMonth, $strDataYear;
  global $strDataDepartment, $strDataCompany;
  global $strDataSection;
  global $strDataGrade;
  $intRows = 0;
  $strResult = "";
  $strKriteriaDept = "";
  $bolShowTotal = $bolShowTotalDept = $bolShowTotalSect = true;
  // cari data Department
  if ($strDataDepartment != "") {
    $strKriteriaDept .= "AND emp.department_code = '$strDataDepartment' ";
    $bolShowTotal = false;
  }
  if ($strDataGrade != "") {
    $strKriteriaDept .= "AND emp.salary_grade_code = '$strDataGrade' ";
  }
  if ($strDataCompany != "") {
    $strKriteriaDept .= "AND emp.id_company = '$strDataCompany' ";
  }
  $arrDept = [];
  $arrEmp = [];
  $strSQL = "
      SELECT warn.*, emp.employee_status, emp.employee_id, employee_name, 
        emp.salary_grade_code, emp.position_code,  emp.department_code,
        dept.department_name, pos.position_name
      FROM hrd_employee_warning AS warn
      INNER JOIN hrd_employee AS emp ON warn.id_employee = emp.id
      LEFT JOIN hrd_department AS dept ON emp.department_code = dept.department_code
      LEFT JOIN hrd_position AS pos ON emp.position_code = pos.position_code
      WHERE EXTRACT(month FROM warning_date) = '$strDataMonth'
        AND EXTRACT(year FROM warning_date) = '$strDataYear'
        AND warn.status = '" . REQUEST_STATUS_APPROVED . "'
        $strKriteriaDept
      ORDER BY dept.department_name, emp.employee_id
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDept[$rowDb['department_code']] = $rowDb['department_name'];
    if (isset($arrEmp[$rowDb['department_code']][$rowDb['id_employee']])) {
      if ($rowDb['letter_no'] != "") {
        if ($arrEmp[$rowDb['department_code']][$rowDb['id_employee']]['letter_no'] != "") {
          $arrEmp[$rowDb['department_code']][$rowDb['id_employee']]['letter_no'] .= ", ";
        }
        $arrEmp[$rowDb['department_code']][$rowDb['id']]['letter_no'] .= $rowDb['letter_no'];
      }
      $arrEmp[$rowDb['department_code']][$rowDb['id']][$rowDb['warning_code']] = 1;
    } else {
      $arrEmp[$rowDb['department_code']][$rowDb['id']] = $rowDb;
    }
    $arrEmp[$rowDb['department_code']][$rowDb['id']][$rowDb['warning_code']] = 1;
  }
  // ambil data kode warning
  $arrType = [];
  $arrTotalDefault = [];
  $strSQL = "SELECT * FROM hrd_warning_type ORDER BY flag ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrType[] = $rowDb['code'];
    $arrTotalDefault[$rowDb['code']] = 0;
  }
  // tampilkan data
  $strDefaultWidth = "width=40";
  $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
  // bikin header table
  $strResult .= "  <tr align=center class='tableHeader'>\n";
  $strResult .= "    <th rowspan=2>" . getWords("no.") . "</th>\n";
  $strResult .= "    <th rowspan=2>" . getWords("employee id") . "</th>\n";
  $strResult .= "    <th rowspan=2>" . getWords("name") . "</th>\n";
  $strResult .= "    <th rowspan=2>" . getWords("band") . "</th>\n";
  $strResult .= "    <th rowspan=2>" . getWords("status") . "</th>\n";
  $strResult .= "    <th rowspan=2>" . getWords("position") . "</th>\n";
  $strResult .= "    <th rowspan=2>" . getWords("date") . "</th>\n";
  $strResult .= "    <th colspan=" . (count($arrType) + 1) . ">SP</th>\n";
  $strResult .= "    <th rowspan=2>" . getWords("note") . "</th>\n";
  $strResult .= "  </tr>\n";
  $strResult .= "  <tr align=center class='tableHeader'>\n";
  foreach ($arrType AS $i => $strType) {
    $strResult .= "    <th>" . $strType . "</th>\n";
  }
  $strResult .= "    <th>" . getWords("letter no.") . "</th>\n";
  $strResult .= "  </tr>\n";
  $arrTotal = $arrTotalDefault;
  foreach ($arrDept AS $code => $name) {
    $strResult .= "  <tr><td colspan=" . (count(
                $arrType
            ) + 9) . "><b>" . (($name == "") ? $code : $name) . "</b></td></tr>\n"; // nama department
    $arrTotalDept = $arrTotalDefault;
    if (isset($arrEmp[$code])) {
      $i = 0;
      foreach ($arrEmp[$code] AS $id => $rowDb) {
        $i++;
        $strResult .= "  <tr>\n";
        $strResult .= "    <td width=25 align=right>$i.&nbsp;</td>\n";
        $strResult .= "    <td >" . $rowDb['employee_id'] . "&nbsp;</td>\n";
        $strResult .= "    <td >" . $rowDb['employee_name'] . "&nbsp;</td>\n";
        $strResult .= "    <td >" . $rowDb['salary_grade_code'] . "&nbsp;</td>\n";
        $strResult .= "    <td >" . $rowDb['employee_status'] . "&nbsp;</td>\n";
        $strResult .= "    <td >" . $rowDb['position_name'] . "&nbsp;</td>\n";
        $strResult .= "    <td >" . pgDateFormat($rowDb['warning_date'], "d-M-y") . "&nbsp;</td>\n";
        foreach ($arrType AS $i => $strType) {
          $strTmp = "";
          if (isset($rowDb[$strType])) {
            $strTmp = $rowDb[$strType];
            $arrTotalDept[$strType] += 1;
            $arrTotal[$strType] += 1;
          }
          $strResult .= "    <td align='center'>" . $strTmp . "&nbsp;</td>\n";
        }
        $strResult .= "    <td >" . $rowDb['letter_no'] . "&nbsp;</td>\n";
        $strResult .= "    <td >" . $rowDb['note'] . "&nbsp;</td>\n";
        $strResult .= "  </tr>\n";
      }
      // subtotal
      $strResult .= "  <tr class='bgNewRevised'>\n";
      $strResult .= "    <td colspan=7><b>" . getWords("subtotal") . " " . $name . "</b>&nbsp;</td>\n";
      foreach ($arrType AS $i => $strType) {
        $strResult .= "    <td align='center'><b>" . $arrTotalDept[$strType] . "</b>&nbsp;</td>\n";
      }
      $strResult .= "    <td >&nbsp;</td>\n";
      $strResult .= "    <td >&nbsp;</td>\n";
      $strResult .= "  </tr>\n";
    }
  }
  // footer
  $strResult .= "  <tr class='tableHeader'>\n";
  $strResult .= "    <td colspan=7><b>" . strtoupper(getWords("grand total")) . "</b>&nbsp;</td>\n";
  foreach ($arrType AS $i => $strType) {
    $strResult .= "    <td align='center'><b>" . $arrTotal[$strType] . "</b>&nbsp;</td>\n";
  }
  $strResult .= "    <td >&nbsp;</td>\n";
  $strResult .= "    <td >&nbsp;</td>\n";
  $strResult .= "  </tr>\n";
  $strResult .= "</table>\n";
  return $strResult;
} //
// menampilkan data rekap per department
function showDataDepartment($db)
{
  global $strDataMonth, $strDataYear;
  global $strDataDepartment, $strDataCompany;
  global $strDataSection;
  global $strDataGrade;
  $intRows = 0;
  $strResult = "";
  $strKriteriaDept = "";
  $strKriteria = "";
  if ($strDataDepartment != "") {
    $strKriteriaDept .= "AND department_code = '$strDataDepartment' ";
  }
  $strKriteria .= $strKriteriaDept;
  if ($strDataCompany != "") {
    $strKriteria .= "AND id_company = '$strDataCompany' ";
  }
  if ($strDataGrade != "") {
    $strKriteria .= "AND salary_grade_code = '$strDataGrade' ";
  }
  $arrData = [];
  $strSQL = "
      SELECT emp.department_code, warn.warning_code, COUNT(warn.*) AS total_warning
      FROM hrd_employee_warning AS warn
      INNER JOIN (
        SELECT * FROM hrd_employee WHERE 1=1 $strKriteria
      ) AS emp ON warn.id_employee = emp.id
      WHERE EXTRACT(month FROM warning_date) = '$strDataMonth'
        AND EXTRACT(year FROM warning_date) = '$strDataYear'
        AND warn.status = '" . REQUEST_STATUS_APPROVED . "'
      GROUP BY emp.department_code, warn.warning_code
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrData[$rowDb['department_code']][$rowDb['warning_code']] = $rowDb['total_warning'];
  }
  // ambil data kode warning
  $arrType = [];
  $arrTotalDefault = [];
  $arrTotalDefault['total'] = 0;
  $strSQL = "SELECT * FROM hrd_warning_type ORDER BY flag ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrType[] = $rowDb['code'];
    $arrTotalDefault[$rowDb['code']] = 0;
  }
  // tampilkan data
  $strDefaultWidth = "width=40";
  $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
  // bikin header table
  $strResult .= "  <tr align=center class='tableHeader'>\n";
  $strResult .= "    <th rowspan=2>" . getWords("no.") . "</th>\n";
  $strResult .= "    <th rowspan=2>" . getWords("code") . "</th>\n";
  $strResult .= "    <th rowspan=2>" . getWords("name") . "</th>\n";
  $strResult .= "    <th colspan=" . (count($arrType) + 1) . ">SP</th>\n";
  $strResult .= "  </tr>\n";
  $strResult .= "  <tr align=center class='tableHeader'>\n";
  foreach ($arrType AS $i => $strType) {
    $strResult .= "    <th>" . $strType . "</th>\n";
  }
  $strResult .= "    <th>" . getWords("total") . "</th>\n";
  $strResult .= "  </tr>\n";
  $arrTotal = $arrTotalDefault;
  // ambil data department
  $i = 0;
  $strSQL = "SELECT * FROM hrd_department WHERE 1=1 $strKriteriaDept ORDER BY department_name ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $i++;
    $strResult .= "  <tr>\n";
    $strResult .= "    <td width=25 align=right>$i.&nbsp;</td>\n";
    $strResult .= "    <td >" . $row['department_code'] . "&nbsp;</td>\n";
    $strResult .= "    <td >" . $row['department_name'] . "&nbsp;</td>\n";
    $intTotal = 0;
    foreach ($arrType AS $j => $strType) {
      $strTmp = 0;
      if (isset($arrData[$row['department_code']][$strType])) {
        $strTmp = $arrData[$row['department_code']][$strType];
        $intTotal += $strTmp;
        $arrTotal[$strType] += $strTmp;
      }
      $strResult .= "    <td align='center'>" . $strTmp . "&nbsp;</td>\n";
    }
    $strResult .= "    <td align='center'>" . $intTotal . "&nbsp;</td>\n";
    $strResult .= "  </tr>\n";
    $arrTotal['total'] += $intTotal;
  }
  // footer
  $strResult .= "  <tr class='tableHeader'>\n";
  $strResult .= "    <td>&nbsp;</td>\n";
  $strResult .= "    <td colspan=2><b>" . strtoupper(getWords("grand total")) . "</b>&nbsp;</td>\n";
  foreach ($arrType AS $j => $strType) {
    $strResult .= "    <td align='center'><b>" . $arrTotal[$strType] . "</b>&nbsp;</td>\n";
  }
  $strResult .= "    <td align='center'><b>" . $arrTotal['total'] . "</b>&nbsp;</td>\n";
  $strResult .= "  </tr>\n";
  $strResult .= "</table>\n";
  return $strResult;
} //
// menampilkan data rekap per band
function showDataBand($db)
{
  global $strDataMonth, $strDataYear;
  global $strDataDepartment, $strDataCompany;
  global $strDataSection;
  global $strDataGrade;
  $intRows = 0;
  $strResult = "";
  $strKriteriaBand = "";
  $strKriteria = "";
  if ($strDataGrade != "") {
    $strKriteriaBand .= "AND salary_grade_code = '$strDataGrade' ";
  }
  $strKriteria .= $strKriteriaBand;
  if ($strDataCompany != "") {
    $strKriteria .= "AND id_company = '$strDataCompany' ";
  }
  if ($strDataDepartment != "") {
    $strKriteria .= "AND department_code = '$strDataDepartment' ";
  }
  $arrData = [];
  $strSQL = "
      SELECT emp.salary_grade_code, warn.warning_code, COUNT(warn.*) AS total_warning
      FROM hrd_employee_warning AS warn
      INNER JOIN (
        SELECT * FROM hrd_employee WHERE 1=1 $strKriteria
      ) AS emp ON warn.id_employee = emp.id
      WHERE EXTRACT(month FROM warning_date) = '$strDataMonth'
        AND EXTRACT(year FROM warning_date) = '$strDataYear'
        AND warn.status = '" . REQUEST_STATUS_APPROVED . "'
      GROUP BY emp.salary_grade_code, warn.warning_code
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrData[$rowDb['salary_grade_code']][$rowDb['warning_code']] = $rowDb['total_warning'];
  }
  // ambil data kode warning
  $arrType = [];
  $arrTotalDefault = [];
  $arrTotalDefault['total'] = 0;
  $strSQL = "SELECT * FROM hrd_warning_type ORDER BY flag ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrType[] = $rowDb['code'];
    $arrTotalDefault[$rowDb['code']] = 0;
  }
  // tampilkan data
  $strDefaultWidth = "width=40";
  $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
  // bikin header table
  $strResult .= "  <tr align=center class='tableHeader'>\n";
  $strResult .= "    <th rowspan=2>" . getWords("no.") . "</th>\n";
  $strResult .= "    <th rowspan=2>" . getWords("band") . "</th>\n";
  $strResult .= "    <th colspan=" . (count($arrType) + 1) . ">SP</th>\n";
  $strResult .= "  </tr>\n";
  $strResult .= "  <tr align=center class='tableHeader'>\n";
  foreach ($arrType AS $i => $strType) {
    $strResult .= "    <th>" . $strType . "</th>\n";
  }
  $strResult .= "    <th>" . getWords("total") . "</th>\n";
  $strResult .= "  </tr>\n";
  $arrTotal = $arrTotalDefault;
  // ambil data department
  $i = 0;
  $strSQL = "SELECT * FROM hrd_salary_grade WHERE 1=1 $strKriteriaBand ORDER BY grade_code ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $i++;
    $strResult .= "  <tr>\n";
    $strResult .= "    <td width=25 align=right>$i.&nbsp;</td>\n";
    $strResult .= "    <td >" . $row['grade_code'] . "&nbsp;</td>\n";
    $intTotal = 0;
    foreach ($arrType AS $j => $strType) {
      $strTmp = 0;
      if (isset($arrData[$row['grade_code']][$strType])) {
        $strTmp = $arrData[$row['grade_code']][$strType];
        $intTotal += $strTmp;
        $arrTotal[$strType] += $strTmp;
      }
      $strResult .= "    <td align='center'>" . $strTmp . "&nbsp;</td>\n";
    }
    $strResult .= "    <td align='center'>" . $intTotal . "&nbsp;</td>\n";
    $strResult .= "  </tr>\n";
    $arrTotal['total'] += $intTotal;
  }
  // footer
  $strResult .= "  <tr class='tableHeader'>\n";
  $strResult .= "    <td>&nbsp;</td>\n";
  $strResult .= "    <td><b>" . strtoupper(getWords("grand total")) . "</b>&nbsp;</td>\n";
  foreach ($arrType AS $j => $strType) {
    $strResult .= "    <td align='center'><b>" . $arrTotal[$strType] . "</b>&nbsp;</td>\n";
  }
  $strResult .= "    <td align='center'><b>" . $arrTotal['total'] . "</b>&nbsp;</td>\n";
  $strResult .= "  </tr>\n";
  $strResult .= "</table>\n";
  return $strResult;
} //
// menampilkan data rekap berdasar alasan pemberian SP
function showDataReason($db)
{
  global $strDataMonth, $strDataYear;
  global $strDataDepartment, $strDataCompany;
  global $strDataSection;
  global $strDataGrade;
  $intRows = 0;
  $strResult = "";
  $strKriteriaBand = "";
  $strKriteria = "";
  if ($strDataGrade != "") {
    $strKriteriaBand .= "AND salary_grade_code = '$strDataGrade' ";
  }
  $strKriteria .= $strKriteriaBand;
  if ($strDataCompany != "") {
    $strKriteria .= "AND id_company = '$strDataCompany' ";
  }
  if ($strDataDepartment != "") {
    $strKriteria .= "AND department_code = '$strDataDepartment' ";
  }
  $arrData = [];
  $intTotal = 0;
  $strSQL = "
      SELECT warn.reason, COUNT(warn.*) AS total_warning
      FROM hrd_employee_warning AS warn
      INNER JOIN (
        SELECT * FROM hrd_employee WHERE 1=1 $strKriteria
      ) AS emp ON warn.id_employee = emp.id
      WHERE EXTRACT(month FROM warning_date) = '$strDataMonth'
        AND EXTRACT(year FROM warning_date) = '$strDataYear'
        AND warn.status = '" . REQUEST_STATUS_APPROVED . "'
      GROUP BY warn.reason
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrData[$rowDb['reason']] = $rowDb['total_warning'];
    $intTotal += $rowDb['total_warning'];
  }
  // tampilkan data
  $strDefaultWidth = "width=40";
  $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
  // bikin header table
  $strResult .= "  <tr align=center class='tableHeader'>\n";
  $strResult .= "    <th >" . getWords("no.") . "</th>\n";
  $strResult .= "    <th >" . getWords("reason") . "</th>\n";
  $strResult .= "    <th >" . getWords("total") . "</th>\n";
  $strResult .= "    <th >" . getWords("percentage") . " (%)</th>\n";
  $strResult .= "  </tr>\n";
  // ambil data department
  $i = 0;
  $strSQL = "SELECT * FROM hrd_warning_reason ORDER BY reason ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $i++;
    $intWarning = (isset($arrData[$row['reason']])) ? $arrData[$row['reason']] : 0;
    if ($intTotal == 0) {
      $fltPercent = 0;
    } else {
      $fltPercent = (($intWarning / $intTotal) * 100);
    }
    $strResult .= "  <tr>\n";
    $strResult .= "    <td width=25 align=right>$i.&nbsp;</td>\n";
    $strResult .= "    <td >" . $row['reason'] . "&nbsp;</td>\n";
    $strResult .= "    <td align='center'>" . $intWarning . "&nbsp;</td>\n";
    $strResult .= "    <td align='center'>" . standardFormat($fltPercent) . "&nbsp;</td>\n";
    $strResult .= "  </tr>\n";
  }
  // footer
  $strResult .= "  <tr class='tableHeader'>\n";
  $strResult .= "    <td>&nbsp;</td>\n";
  $strResult .= "    <td><b>" . strtoupper(getWords("total")) . "</b>&nbsp;</td>\n";
  $strResult .= "    <td align='center'><b>" . $intTotal . "</b>&nbsp;</td>\n";
  $strResult .= "    <td align='center'><b>" . (($intTotal == 0) ? standardFormat(0) : standardFormat(
          100
      )) . "</b>&nbsp;</td>\n";
  $strResult .= "  </tr>\n";
  $strResult .= "</table>\n";
  return $strResult;
} //
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  // ------ AMBIL DATA KRITERIA -------------------------
  (isset($_REQUEST['dataMonth'])) ? $strDataMonth = $_REQUEST['dataMonth'] : $strDataMonth = date("n");
  (isset($_REQUEST['dataYear'])) ? $strDataYear = $_REQUEST['dataYear'] : $strDataYear = date("Y");
  (isset($_REQUEST['dataDivision'])) ? $strDataDivision = $_REQUEST['dataDivision'] : $strDataDivision = "";
  (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
  (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
  (isset($_REQUEST['dataGrade'])) ? $strDataGrade = $_REQUEST['dataGrade'] : $strDataGrade = "";
  //(isset($_REQUEST['dataGroup'])) ? $strDataGroup = $_REQUEST['dataGroup'] : $strDataGroup = "";
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
  (isset($_REQUEST['dataReportType'])) ? $strReportType = $_REQUEST['dataReportType'] : $strReportType = 0;
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    if ($arrUserInfo['isDeptHead']) {
      $strDataDepartment = $arrUserInfo['department_code'];
    } else if ($arrUserInfo['isGroupHead']) {
      $strDataSection = $arrUserInfo['section_code'];
    } else {
      $strDataEmployee = $arrUserInfo['employee_id'];
    }
  }
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if ($strDataDivision != "") {
    $strKriteria .= "AND division_code = '$strDataDivision' ";
  }
  if ($strDataDepartment != "") {
    $strKriteria .= "AND department_code = '$strDataDepartment' ";
  }
  if ($strDataGrade != "") {
    $strKriteria .= "AND salary_grade_code = '$strDataSection' ";
  }
  $strKriteria .= $strKriteriaCompany;
  $strKriteria .= $objUP->genFilterEmployee();
  if ($bolCanView) {
    $bolShow = (isset($_REQUEST['btnShow']) || $bolPrint);
    if ($bolShow) {
      // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
      if ($strReportType == 0) {
        $strDataDetail = showDataDetail($db);
        $strInfo .= " - " . getWords("detail");
      } else if ($strReportType == 1) {
        $strDataDetail = showDataDepartment($db);
        $strInfo .= " - " . getWords("summary by department");
      } else if ($strReportType == 2) {
        $strDataDetail = showDataBand($db);
        $strInfo .= " - " . getWords("summary by band");
      } else if ($strReportType == 3) {
        $strDataDetail = showDataReason($db);
        $strInfo .= " - " . getWords("summary by reason");
      }
      $strHidden .= "<input type=hidden name=btnShow value=show>";
      if (isset($_REQUEST['btnExcel'])) {
        // ambil data CSS-nya
        if (file_exists("../css/excel.css")) {
          $strStyle = "../css/excel.css";
        }
        $strPrintCss = "";
        $strPrintInit = "";
        headeringExcel("attendance.xls");
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
  $strDisabled = ""; //($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) ? "disabled" : "";
  $strTmpKriteria = "WHERE 1=1 ";
  $intDefaultWidthPx = 200;
  $strInputMonth = getMonthList("dataMonth", $strDataMonth, "", "", " $strDisabled");
  $strInputMonth .= " " . getYearList("dataYear", $strDataYear, "", "", " $strDisabled");
  $strTmpKriteria .= $objUP->genFilterDivision();
  $strInputDivision = getDivisionList(
      $db,
      "dataDivision",
      $strDataDivision,
      $strEmptyOption,
      $strTmpKriteria,
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  /*
  if ($arrUserInfo['isDeptHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strTmpKriteria .= "AND department_code = '" .$arrUserInfo['department_code']."' ";
    $strDisabled = "";
  }
  */
  $strTmpKriteria .= $objUP->genFilterDepartment();
  //handle user company-access-right
  $strInputCompany = getCompanyList(
      $db,
      "dataCompany",
      $strDataCompany,
      $strEmptyOption2,
      $strKriteria2,
      "style=\"width:$intDefaultWidthPx\" "
  );
  $strInputDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $strDataDepartment,
      $strEmptyOption,
      $strTmpKriteria,
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  $arrTmp = [
      getWords("detail"),
      getWords("summary by department"),
      getWords("summary by band"),
      getWords("summary by reason")
  ];
  $strInputReportType = getComboFromArray($arrTmp, "dataReportType", $strReportType);
  if ($arrUserInfo['isGroupHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strTmpKriteria .= "AND section_code = '" . $arrUserInfo['section_code'] . "' ";
    $strDisabled = "";
  }
  $strInputGrade = getSalaryGradeList(
      $db,
      "dataGrade",
      $strDataGrade,
      $strEmptyOption,
      "",
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  // informasi tanggal kehadiran
  $strInfo .= ", " . getBulan($strDataMonth) . " " . $strDataYear;
  $strHidden .= "<input type=hidden name=dataMonth value=\"$strDataMonth\">";
  $strHidden .= "<input type=hidden name=dataYear value=\"$strDataYear\">";
  $strHidden .= "<input type=hidden name=dataCompany value=\"$strDataCompany\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  $strHidden .= "<input type=hidden name=dataGrade value=\"$strDataGrade\">";
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
  $strMainTemplate = getTemplate("report_print.html");
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>