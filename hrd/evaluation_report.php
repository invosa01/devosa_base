<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('evaluation_func.php');
include_once('../global/employee_function.php');
$dataPrivilege = getDataPrivileges("evaluation_list.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$strPaging = "";
$strInfo = "";
$intTotalData = 0;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
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

// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
// uotput berupa array
function getData($db, &$intRows, $strKriteria = "", $strOrder = "", $intPage = 1, $bolLimit = true)
{
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $strPaging;
  $intRows = 0;
  //     $strResult = "";
  $arrResult = [];
  $bolLimit = false;
  $intRowsLimit = getSetting("rows_per_page");
  if (!is_numeric($intRowsLimit)) {
    $intRowsLimit = 50;
  }
  $fltWeightOperational = (float)getSetting("weight_operational");
  $fltWeightGeneral = (float)getSetting("weight_general");
  $fltWeightAbsence = (float)getSetting("weight_absence");
  if (!is_numeric($fltWeightOperational)) {
    $fltWeightOperational = 0;
  }
  if (!is_numeric($fltWeightAbsence)) {
    $fltWeightAbsence = 0;
  }
  if (!is_numeric($fltWeightGeneral)) {
    $fltWeightGeneral = 0;
  }
  // cari total data
  $intTotal = 0;
  if ($bolLimit) {
    $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_employee_evaluation AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE 1=1 $strKriteria ";
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
  }
  $intStart = (($intPage - 1) * $intRowsLimit);
  // ambil dulu data employee, kumpulkan dalam array
  $i = 0;
  $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.department_code, t2.section_code  ";
  $strSQL .= "FROM hrd_employee_evaluation AS t1 ";
  $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
  $strSQL .= "WHERE 1=1 $strKriteria ";
  $strSQL .= "ORDER BY $strOrder \"year\" DESC, \"semester\", department_code, employee_name ";
  if ($bolLimit) {
    $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    /*
          // cari data manager
          $strManager = "";
          if ($rowDb['id_manager'] != "") {
            $strSQL = "SELECT employee_name FROM hrd_employee WHERE id = '" .$rowDb['id_manager']."' ";
            $resTmp = $db->execute($strSQL);
            if ($rowTmp = $db->fetchrow($resTmp)) {
              $strManager = $rowTmp['employee_name'];
            }
          }
    */
    $fltResult = ($rowDb['operational_point'] * $fltWeightOperational);
    $fltResult += ($rowDb['general_point'] * $fltWeightGeneral);
    $fltResult += ($rowDb['absence_point'] * $fltWeightAbsence);
    $arrResult[$intRows]['employee_id'] = $rowDb['employee_id'];
    $arrResult[$intRows]['employee_name'] = $rowDb['employee_name'];
    $arrResult[$intRows]['department_code'] = $rowDb['department_code'];
    $arrResult[$intRows]['section_code'] = $rowDb['section_code'];
    $arrResult[$intRows]['operational'] = $rowDb['operational_point'];
    $arrResult[$intRows]['general'] = $rowDb['general_point'];
    $arrResult[$intRows]['absence'] = $rowDb['absence_point'];
    $arrResult[$intRows]['result'] = $fltResult / 100;
  }
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  return $arrResult;
} // getData
// fungsi menampilkan data per baris
// input : no urut, data, kelas CSS
function showRows($strNo, $arrData, $strClass = "")
{
  $strResult = "";
  $strResult .= "<tr valign=top class=\"$strClass\">\n";
  $strResult .= "  <td align=right nowrap>" . $strNo . "&nbsp;</td>\n";
  $strResult .= "  <td>&nbsp;" . $arrData['employee_id'] . "&nbsp;</td>\n";
  $strResult .= "  <td>&nbsp;" . $arrData['employee_name'] . "&nbsp;</td>\n";
  $strResult .= "  <td align=right>" . cekStandardFormat($arrData['operational']) . "</td>\n";
  $strResult .= "  <td align=right>" . cekStandardFormat($arrData['general']) . "</td>\n";
  $strResult .= "  <td align=right>" . cekStandardFormat($arrData['absence']) . "</td>\n";
  $strResult .= "  <td align=right>" . cekStandardFormat($arrData['result']) . "</td>\n";
  $strResult .= "</tr>\n";
  return $strResult;
} //showRows
// fungsi untuk menampilkan data, per employee
function showData($arrData)
{
  $strResult = "";
  foreach ($arrData AS $x => $rowDb) {
    $strResult .= showRows($x, $rowDb);
  }
  return $strResult;
} //showData
// fungsi untuk menampilkan data per department
// menampilkan data, digroup berdasar departemen
function showDataDepartment($db, $arrData)
{
  global $strFilterDepartment;
  global $strFilterSection;
  global $strFilterEmployee;
  $intRows = 0;
  $strResult = "";
  $strKriteriaDept = "";
  $strKriteriaSect = "";
  $bolShowTotal = $bolShowTotalDept = $bolShowTotalSect = true;
  // cek jika cuma 1 employee yg dicari
  if ($strFilterEmployee != "" && isset($arrData[1])) {
    $strKriteriaDept .= "AND department_code = '" . $arrData[1]['department_code'] . "' ";
    $strKriteriaSect .= "AND department_code = '" . $arrData[1]['department_code'] . "' ";
    $strKriteriaSect .= "AND section_code = '" . $arrData[1]['section_code'] . "' ";
    $bolShowTotal = $bolShowTotalDept = $bolShowTotalSect = false;
  }
  // cari data section
  $arrSect = [];
  if ($strFilterSection != "") {
    $strKriteriaSect .= "AND section_code =  '$strFilterSection' ";
    $bolShowTotal = $bolShowTotalDept = false;
  }
  $strSQL = "SELECT * FROM hrd_section WHERE 1=1 $strKriteriaSect ";
  $strSQL .= "ORDER BY section_code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrSect[$rowDb['department_code']][$rowDb['section_code']] = $rowDb['section_name'];
    if ($strFilterSection != "") {
      $strKriteriaDept = "AND department_code = '" . $rowDb['department_code'] . "' ";
    }
  }
  // cari data Department
  if ($strFilterDepartment != "") {
    $strKriteriaDept .= "AND department_code = '$strFilterDepartment' ";
    $bolShowTotal = false;
  }
  $arrDept = [];
  $strSQL = "SELECT * FROM hrd_department WHERE 1=1 $strKriteriaDept ";
  $strSQL .= "ORDER BY department_code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDept[$rowDb['department_code']] = $rowDb['department_name'];
  }
  // tentukan keanggotaan department/section
  $arrDeptEmployee = []; // daftar anggota sebuah departement, tapi gak punya section
  $arrSectEmployee = []; // daftar anggota sebuah section
  foreach ($arrData AS $x => $rowDb) {
    if ($rowDb['department_code'] != "" && $rowDb['section_code'] != "") {
      // masuk ke dalam section
      if (isset($arrSectEmployee[$rowDb['section_code']])) {
        $arrSectEmployee[$rowDb['section_code']][] = $x;
      } else {
        $arrSectEmployee[$rowDb['section_code']][0] = $x;
      }
    } else if ($rowDb['department_code'] != "") { // cuma ada departemen aja
      // masukkan ke dalam department, tapi gak di section tertentu
      if (isset($arrDeptEmployee[$rowDb['department_code']])) {
        $arrDeptEmployee[$rowDb['department_code']][] = $x;
      } else {
        $arrDeptEmployee[$rowDb['department_code']][0] = $x;
      }
    }
  }
  // array temporer untuk reset data
  $arrEmptyData = [
      "id"            => "",
      "operational"   => 0,
      "absence"       => 0,
      "general"       => 0,
      "result"        => 0,
      "employee_id"   => "",
      "employee_name" => "",
  ];
  $arrTotal = $arrEmptyData;
  $arrTotal['employee_name'] = "<strong>" . strtoupper(getWords("grand total")) . "</strong>";
  // tampilkan data
  //     $strResult .= "<table cellspacing=0 cellpadding=1 border=0 class=gridTable>\n";
  // bikin header table
  $intColspan = 7;
  foreach ($arrDept AS $strDeptCode => $strDeptName) {
    if ($bolShowTotalDept) {
      $strResult .= " <tr valign=top>\n";
      $strResult .= "  <td nowrap colspan=$intColspan>&nbsp;<strong>[$strDeptCode] $strDeptName</strong></td>\n";
      $strResult .= " </tr>\n";
    }
    $arrTotalDept = $arrEmptyData;
    $arrTotalDept['employee_name'] = "<strong>" . strtoupper(getWords("total") . " " . $strDeptCode) . "</strong>";
    // tampilkan data karyawan anggota departemen
    $arrTmp = (isset($arrDeptEmployee[$strDeptCode])) ? $arrDeptEmployee[$strDeptCode] : [];
    foreach ($arrTmp AS $x => $strX) {
      $rowDb = $arrData[$strX];
      $arrTotal['result'] += $rowDb['result'];
      $arrTotal['general'] += $rowDb['general'];
      $arrTotal['operational'] += $rowDb['operational'];
      $arrTotal['absence'] += $rowDb['absence'];
      $arrTotalDept['result'] += $rowDb['result'];
      $arrTotalDept['general'] += $rowDb['general'];
      $arrTotalDept['operational'] += $rowDb['operational'];
      $arrTotalDept['absence'] += $rowDb['absence'];
      $strResult .= showRows("", $rowDb);
    }
    $arrTmp = (isset($arrSect[$strDeptCode])) ? $arrSect[$strDeptCode] : [];
    foreach ($arrTmp AS $strSectCode => $strSectName) {
      if ($bolShowTotalSect) {
        $strResult .= " <tr valign=top>\n";
        $strResult .= "  <td nowrap colspan=$intColspan>&nbsp;<strong>[$strSectCode] $strSectName</strong></td>\n";
        $strResult .= " </tr>\n";
      }
      $arrTotalSect = $arrEmptyData;
      $arrTotalSect['employee_name'] = "<strong>" . strtoupper(getWords("total") . " " . $strSectCode) . "</strong>";
      // cari karyawan dalam section ini
      $arrTmp1 = (isset($arrSectEmployee[$strSectCode])) ? $arrSectEmployee[$strSectCode] : [];
      foreach ($arrTmp1 AS $x => $strX) {
        $rowDb = $arrData[$strX];
        // hitung total dulu
        $arrTotal['result'] += $rowDb['result'];
        $arrTotal['general'] += $rowDb['general'];
        $arrTotal['operational'] += $rowDb['operational'];
        $arrTotal['absence'] += $rowDb['absence'];
        $arrTotalDept['result'] += $rowDb['result'];
        $arrTotalDept['general'] += $rowDb['general'];
        $arrTotalDept['operational'] += $rowDb['operational'];
        $arrTotalDept['absence'] += $rowDb['absence'];
        $arrTotalSect['result'] += $rowDb['result'];
        $arrTotalSect['general'] += $rowDb['general'];
        $arrTotalSect['operational'] += $rowDb['operational'];
        $arrTotalSect['absence'] += $rowDb['absence'];
        $strResult .= showRows("", $rowDb);
      }
      // tampilkan total per section
      if ($bolShowTotalSect) {
        $strResult .= showRows("", $arrTotalSect, "bgNewRevised");
      }
    }
    if ($bolShowTotalDept) {
      $strResult .= showRows("", $arrTotalDept, "bgNewRevised");
    }
  }
  if ($bolShowTotal) {
    $strResult .= showRows("", $arrTotal, "tableHeader");
  }
  //     $strResult .= "</table>\n";
  return $strResult;
} // showDataDepartment
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  // ------ AMBIL DATA KRITERIA -------------------------
  $strFilterYear = (isset($_SESSION['sessionFilterYear'])) ? $_SESSION['sessionFilterYear'] : date("Y");
  $strFilterDepartment = (isset($_SESSION['sessionFilterDepartment'])) ? $_SESSION['sessionFilterDepartment'] : "";
  $strFilterSemester = (isset($_SESSION['sessionFilterSemester'])) ? $_SESSION['sessionFilterSemester'] : "1";
  if (isset($_REQUEST['filterYear'])) {
    $strFilterYear = $_REQUEST['filterYear'];
  }
  if (isset($_REQUEST['filterDepartment'])) {
    $strFilterDepartment = $_REQUEST['filterDepartment'];
  }
  if (isset($_REQUEST['filterSemester'])) {
    $strFilterSemester = $_REQUEST['filterSemester'];
  }
  $_SESSION['sessionFilterYear'] = $strFilterYear;
  $_SESSION['sessionFilterDepartment'] = $strFilterDepartment;
  $_SESSION['sessionFilterSemester'] = $strFilterSemester;
  $strDataEmployee = $arrUserInfo['employee_id'];
  $strFilterEmployee = "";
  $strPage = (isset($_REQUEST['dataPage'])) ? $_REQUEST['dataPage'] : 1;
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if ($strFilterYear != "") {
    $strKriteria = "AND year = '$strFilterYear' ";
  }
  if ($strFilterSemester != "") {
    $strKriteria = "AND semester = '$strFilterSemester' ";
  }
  if ($strFilterDepartment != "") {
    $strKriteria = "AND department_code = '$strFilterDepartment' ";
  }
  if ($arrUserInfo['idEmployee'] != "" && $bolIsEmployee) {
    $strFilterEmployee = $arrUserInfo['employee_id'];
    $strKriteria = "AND id_employee = '" . $arrUserInfo['id_employee'] . "' ";
  }
  if ($bolCanView) {
    $arrDataDetail = getData($db, $intTotalData, $strKriteria, "", $strPage);
    //$strDataDetail = showData($arrDataDetail);
    $strDataDetail = showDataDepartment($db, $arrDataDetail);
    if (isset($_REQUEST['btnExcel'])) {
      // ambil data CSS-nya
      if (file_exists("../css/excel.css")) {
        $strStyle = "../css/excel.css";
      }
      $strPrintCss = "";
      $strPrintInit = "";
      headeringExcel("evaluationReport.xls");
    }
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  // generate data hidden input dan element form input
  $strInfo .= "<br>$strFilterYear [$strFilterSemester]";
  $intDefaultWidthPx = 100;
  $strInputYear = getYearList("dataYear", date("Y"), "", "style=\"width:$intDefaultWidthPx\"");
  $strInputSemester = "<select name=dataSemester style=\"width:$intDefaultWidthPx\">\n";
  $strInputSemester .= "  <option value=1>1</option>\n";
  $strInputSemester .= "  <option value=2>2</option>\n";
  $strInputSemester .= "</select>\n";
  $strReadonly = ($bolIsEmployee) ? "readonly" : "";
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee value=\"$strDataEmployee\" size=20 maxlength=20 style=\"width:$intDefaultWidthPx\" $strReadonly>";
  $strFilterDepartment = getDepartmentList($db, "filterDepartment", $strFilterDepartment, $strEmptyOption);
  $strFilterYear = getYearList("filterYear", $strFilterYear);
  $strFilterSem = "<select name=filterSemester>\n";
  if ($strFilterSemester == 2) {
    $strFilterSem .= "  <option value=1>1</option>\n";
    $strFilterSem .= "  <option value=2 selected>2</option>\n";
  } else {
    $strFilterSem .= "  <option value=1 selected>1</option>\n";
    $strFilterSem .= "  <option value=2>2</option>\n";
  }
  $strFilterSem .= "</select>\n";
  $strFilterSemester = $strFilterSem;
}
if ($bolPrint) {
  $strMainTemplate = getTemplate("evaluation_report_print.html");
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>