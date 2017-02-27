<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/model/model.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('form_object.php');
$dataPrivilege = getDataPrivileges("salary_report.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintAll']) || isset($_REQUEST['btnExcel']));
$strOutsource = "";
if ($bolPrint) {
  $strTemplateFile = getTemplate("salaryReportTaxPrint.html");
} else {
  $strTemplateFile = getTemplate("salary_report_tax.html");
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strPaging = "";
$strHidden = "";
$strBtnPrint = "";
$intTotalData = 0;
$bolLimit = true; // default, tampilan dibatasi (paging)
$strYear = date("Y");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  // ------ AMBIL DATA KRITERIA -------------------------
  $strFilterYear = (isset($_REQUEST['filterYear'])) ? trim($_REQUEST['filterYear']) : $strYear;
  if (!is_numeric($strFilterYear)) {
    $strFilterYear = $strYear;
  }
  $strFilterEmployeeID = (isset($_REQUEST['filterEmployeeID'])) ? trim($_REQUEST['filterEmployeeID']) : "";
  $strFilterDepartment = (isset($_REQUEST['filterDepartment'])) ? $_REQUEST['filterDepartment'] : "";
  $strFilterSection = (isset($_REQUEST['filterSection'])) ? $_REQUEST['filterSection'] : "";
  $intCurrPage = (isset($_REQUEST['dataPage'])) ? $_REQUEST['dataPage'] : 1;
  $intType = (isset($_REQUEST['filterType'])) ? $_REQUEST['filterType'] : 0;
  if (!is_numeric($intCurrPage)) {
    $intCurrPage = 1;
  }
  $strBtnPrint = "<input type=button name='btnPrint' value=\"" . $words['print'] . "\" onClick=\"printData($intCurrPage);\">";
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $bolShow = false;
  $strKriteria = "";
  if (!$bolViewOutsource) {
    $strKriteria .= " AND \"employeeStatus\" IN (0,1) ";
  }
  if (isset($_REQUEST['btnShowAll']) || isset($_REQUEST['btnPrintAll'])) {
    $strKriteria = "";
    $bolLimit = false;
    $bolShow = true;
  } else if (isset($_REQUEST['btnShow']) || isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel'])) {
    $strInfoKriteria = "";
    if ($strFilterEmployeeID != "") {
      $strKriteria .= "AND upper(\"employeeID\") like '%" . strtoupper($strFilterEmployeeID) . "%' ";
    }
    if ($strFilterDepartment != "") {
      $strKriteria .= "AND \"departmentCode\" = '$strFilterDepartment' ";
    }
    if ($strFilterSection != "") {
      $strKriteria .= "AND \"sectionCode\" = '$strFilterSection' ";
    }
    if ($intType == 1) {
      $strKriteria .= "AND \"employeeStatus\" <> '" . STATUS_OUTSOURCE . "' ";
    } else if ($intType == 2) {
      $strKriteria .= "AND \"employeeStatus\" = '" . STATUS_OUTSOURCE . "' ";
    }
    $bolShow = true;
  } else { // jngan tampilkan data
    $strKriteria .= "AND 1=2 ";
    $strBtnPrint = ""; // tidak perlu tampil
  }
  if ($bolCanView) {
    $strYear = $strFilterYear;
    if (isset($_REQUEST['btnTaxForm'])) {
      $strCompanyName = getSetting($db, "company_name");
      $strCompanyNPWP = getSetting($db, "company_npwp");
      generateForm1721A1($db, $strYear, $strFilterEmployeeID); // salaryReportFunctions.php
      exit();
    } else if ($bolShow) {
      $arrDataDetail = getDataTax($db, $intTotalData, $strKriteria, $intCurrPage, $bolLimit);
      $strDataDetail = showDataDepartmentTax($db, $arrDataDetail);
    }
    if (isset($_REQUEST['btnExcel'])) {
      // ambil data CSS-nya
      if (file_exists("bw.css")) {
        $strStyle = "bw.css";
      }
      $strPrintCss = "";
      $strPrintInit = "";
      headeringExcel("salaryReportTax.xls");
    }
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  // generate data hidden input dan element form input
  $intDefaultWidth = 30;
  $intDefaultWidthPx = 200;
  $intDefaultHeight = 3;
  //$strInputFilterYear = "<input type=input name=filterYear size=4 maxlength=4 value=\"$strFilterYear\">";
  $strInputFilterYear = getYearList("filterYear", $strFilterYear);
  $strInputFilterEmployeeID = "<input type=input name=filterEmployeeID id=filterEmployeeID size=$intDefaultWidth value=\"$strFilterEmployeeID\" style=\"width:$intDefaultWidthPx\"width:$intDefaultWidthPx\">";
  $strInputFilterDepartment = getDepartmentList(
      $db,
      "filterDepartment",
      $strFilterDepartment,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\""
  );
  $strInputFilterSection = getSectionList(
      $db,
      "filterSection",
      $strFilterSection,
      $strEmptyOption,
      "",
      " style=\"width:$intDefaultWidthPx\""
  );
  $arrStatusOpt = ["all", "employee"];
  if ($bolViewOutsource) {
    $arrStatusOpt[] = "outsource";
  }
  $strInputFilterType = getComboFromArray($arrStatusOpt, "filterType", $intType);
  $strHidden .= "<input type=hidden name=filterEmployeeID value=\"$strFilterEmployeeID\">";
  $strHidden .= "<input type=hidden name=filterDepartment value=\"$strFilterDepartment\">";
  $strHidden .= "<input type=hidden name=filterSection value=\"$strFilterSection\">";
  $strHidden .= "<input type=hidden name=filterYear value=\"$strFilterYear\">";
  $strHidden .= "<input type=hidden name=filterType value=\"$intType\">";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
if ($bolPrint) {
  $strMainTemplate = getTemplate("employee_search_print.html");
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>