<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanClose
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strPaging = "";
$strHidden = "";
$strBtnPrint = "";
$intTotalData = 0;
$bolLimit = true; // default, tampilan dibatasi (paging)
$strButtons = "";
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
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getData($db, &$intRows, $strFilterYear, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $ARRAY_REQUEST_STATUS;
  $intRows = 0;
  $strResult = "";
  $i = 0;
  $fltTotal = 0;
  $intTotalParticipant = 0;
  $fltGrandTotal = 0;
  // cari dulu partisipannya
  $arrParticipant = [];
  $strSQL = "SELECT t3.id_request, t4.employee_name FROM hrd_training_request_participant AS t3 ";
  $strSQL .= "LEFT JOIN hrd_training_request AS t1 ON t1.id = t3.id_request ";
  $strSQL .= "LEFT JOIN hrd_department AS t2 ON t1.department_code = t2.department_code ";
  $strSQL .= "LEFT JOIN hrd_employee AS t4 ON t3.id_employee = t4.id ";
  $strSQL .= "WHERE t1.status=" . REQUEST_STATUS_APPROVED . " AND t3.status = 0 $strKriteria ";
  $strSQL .= "AND EXTRACT(year FROM t1.date_from) = '$strFilterYear' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if (isset($arrParticipant[$rowDb['id_request']])) {
      $arrParticipant[$rowDb['id_request']][] = $rowDb['employee_name'];
    } else {
      $arrParticipant[$rowDb['id_request']][0] = $rowDb['employee_name'];
    }
  }
  $strSQL = "SELECT t1.*, t2.department_name FROM hrd_training_request AS t1 ";
  $strSQL .= "LEFT JOIN hrd_department AS t2 ON t1.department_code = t2.department_code ";
  $strSQL .= "WHERE t1.status=" . REQUEST_STATUS_APPROVED . " AND t1.training_status  = 0 $strKriteria ";
  $strSQL .= "AND EXTRACT(year FROM t1.date_from) = '$strFilterYear' ";
  $strSQL .= "ORDER BY $strOrder t1.date_from, t2.department_name ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strRowspan = "";
    $strParticipant = "";
    $intParticipant = 0;
    if (isset($arrParticipant[$rowDb['id']])) {
      $intParticipant = count($arrParticipant[$rowDb['id']]);
      foreach ($arrParticipant[$rowDb['id']] AS $id => $strName) {
        if ($strParticipant != "") {
          $strParticipant .= "<br>";
        }
        $strParticipant .= $strName;
      }
    }
    $strResult .= "<tr valign=top title=\"" . $rowDb['topic'] . "\">\n";
    $strResult .= "  <td align=right>$intRows.&nbsp;</td>\n";
    $strResult .= "  <td>" . $rowDb['department_code'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['topic'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $strParticipant . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $intParticipant . "&nbsp;</td>";
    $strResult .= "  <td>" . nl2br($rowDb['purpose']) . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['trainer'] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . pgDateFormat($rowDb['date_from'], "d-M-y") . "&nbsp;</td>\n";
    $strResult .= "  <td align=right>" . cekStandardFormat($rowDb['cost']) . "</td>";
    $strResult .= "</tr>\n";
    $fltTotal += $rowDb['cost'];
    $intTotalParticipant += $intParticipant;
    $fltGrandTotal += ($rowDb['cost'] * $intParticipant);
  }
  if ($intRows > 0) {
    // tampilkan total
    $strResult .= " <tr valign=top>\n";
    $strResult .= "  <td colspan=4 align=right><strong>" . $words['total'] . "</strong>&nbsp;</td>\n";
    $strResult .= "  <td align=center>$intTotalParticipant&nbsp;</td>\n";
    $strResult .= "  <td colspan=3 align=right>&nbsp;</td>\n";
    $strResult .= "  <td align=right><strong>" . cekStandardFormat($fltTotal) . "</strong></td>\n";
    $strResult .= " </tr>\n";
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  return $strResult;
} // showData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  // yang boleh cuma group head/dept head jika employee biasa
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    if (!$arrUserInfo['isDeptHead'] && !$arrUserInfo['isGroupHead']) {
      $bolCanView = $bolCanEdit = $bolCanDelete = false;
    }
  }
  // ------ AMBIL DATA KRITERIA -------------------------
  (isset($_REQUEST['filterDepartment'])) ? $strFilterDepartment = $_REQUEST['filterDepartment'] : $strFilterDepartment = "";
  (isset($_REQUEST['filterSection'])) ? $strFilterSection = $_REQUEST['filterSection'] : $strFilterSection = "";
  (isset($_REQUEST['filterYear'])) ? $strFilterYear = $_REQUEST['filterYear'] : $strFilterYear = date("Y");
  (isset($_REQUEST['dataPage'])) ? $intCurrPage = $_REQUEST['dataPage'] : $intCurrPage = 1;
  (isset($_REQUEST['dataSort'])) ? $strSortBy = $_REQUEST['dataSort'] : $strSortBy = "";
  $strInputSortBy = $strSortBy;
  if (!is_numeric($intCurrPage)) {
    $intCurrPage = 1;
  }
  if ($strSortBy != "") {
    $strSortBy = "\"$strSortBy\", ";
  }
  $strBtnPrint = "<input type=button name='btnPrint' value=\"" . $words['print'] . "\" onClick=\"printData($intCurrPage);\">";
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if ($arrUserInfo['isDeptHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strFilterDepartment = $arrUserInfo['department_code'];
  } else if ($arrUserInfo['isGroupHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strFilterDepartment = $arrUserInfo['department_code'];
  }
  //if ($bolIsEmployee) $strKriteria .= "AND t1.employee_id = '" .$arrUserInfo['employee_id']."' ";
  if (isset($_REQUEST['btnSearch']) || isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel'])) {
    $strInfoKriteria = "";
    if ($strFilterDepartment != "") {
      $strKriteria .= "AND t1.department_code = '$strFilterDepartment' ";
    }
    if ($strFilterSection != "") {
      //$strKriteria .= "AND t1.section_code = '$strFilterSection' ";
    }
  } else { // jngan tampilkan data, kecuali jika yang login adalah meployee itu sendiri
    $strKriteria .= " AND 1 = 2 "; // pasti salah
  }
  if ($bolCanView) {
    $strDataDetail = getData($db, $intTotalData, $strFilterYear, $strKriteria, $strSortBy);
    if (isset($_REQUEST['btnExcel'])) {
      // ambil data CSS-nya
      if (file_exists("../css/excel.css")) {
        $strStyle = "../css/excel.css";
      }
      $strPrintCss = "";
      $strPrintInit = "";
      headeringExcel("trainingList.xls");
    }
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  // generate data hidden input dan element form input
  $intDefaultWidth = 30;
  $intDefaultWidthPx = 200;
  $intDefaultHeight = 3;
  $strDisabled = ($bolIsEmployee) ? "disabled" : "";
  $strInputFilterDepartment = getDepartmentList(
      $db,
      "filterDepartment",
      $strFilterDepartment,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  $strInputFilterSection = getSectionList(
      $db,
      "filterSection",
      $strFilterSection,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  $strInputFilterYear = getYearList("filterYear", $strFilterYear, "");
  $strHidden .= "<input type=hidden name=filterDepartment value=\"$strFilterDepartment\">";
  $strHidden .= "<input type=hidden name=filterSection value=\"$strFilterSection\">";
  $strHidden .= "<input type=hidden name=filterYear value=\"$strFilterYear\">";
}
if ($bolPrint) {
  $strMainTemplate = getTemplate("training_list_print.html");
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