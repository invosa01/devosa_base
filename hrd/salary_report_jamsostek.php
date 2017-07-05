<?php
ini_set("display_errors", 1);
date_default_timezone_set('Asia/Jakarta');
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/model/model.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('form_object.php');
$dataPrivilege = getDataPrivileges("salary_report.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$db = new CDbClass();
$db->connect();
$f = new clsForm(
    "form1", /*2 column view*/
    1, "100%"
);
$f->disableFormTag();
$f->showCaption = false;
$f->showMinimizeButton = false;
$f->showCloseButton = false;
$f->addHidden("isShow", 1);
$f->addSelect(
    getWords("company"),
    "dataCompany",
    getDataListCompany($strDataCompany),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    true
);
$f->addSelect("Year", "dataYear", getDataYear(), ["style" => "width:$strDefaultWidthPx"], "", true);
$f->addSelect("Month", "dataMonth", getDataMonth(), ["style" => "width:$strDefaultWidthPx"], "", true);
$f->addSelect(
    getWords("employee status"),
    "employeeStatus",
    getDataListEmployeeStatus(
        getInitialValue("EmployeeStatus"),
        true,
        ["value" => "", "text" => "", "selected" => true]
    ),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false
);
$f->addSelect(
    getWords("report type"),
    "dataType",
    getDataType(),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    true,
    true,
    true,
    "",
    ""
);
$f->addInputAutoComplete(
    getwords("employee id"),
    "employeeName",
    getDataEmployee($strDataEmployee),
    "style=width:$strDefaultWidthPx " . $strReadonly,
    "string",
    false
);
$f->addLabelAutoComplete("", "employeeName", "");
//  //this save button will hide after save <toggle>
$f->addSubmit("btnShow", "Show Report", ["onClick" => "return validInput();"], true, true, "", "", "");
$f->addSubmit("btnExportXLS", "Export Excel", ["onClick" => "return validInput();"], true, true, "", "", "");
$formInput = $f->render();
$showReport = (isset($_POST['btnShow']) || isset($_POST['btnExportXLS']) || isset($_POST['isShow']));
$totalData = 0;
$dataGrid = "";
$strInitAction = "";
$strStatus = $f->getValue('employeeStatus');
$strName = $f->getValue('employeeName');
$strCompany = $f->getValue('dataCompany');
$strType = $f->getValue('dataType');
if ($showReport) {
  $intYear = intval($f->getValue('dataYear'));
  $intMonth = intval($f->getValue('dataMonth'));
  $strKriteria = "";
  if ($strStatus != "") {
    $strKriteria .= " AND t1.\"employee_status\" = $strStatus";
  }
  if ($strName != "") {
    $strKriteria .= " AND t1.\"employee_id\" = '$strName' ";
  }
  if ($strCompany != "") {
    $strKriteria .= " AND t1.id_company = '$strCompany' ";
  }
  $dataMasterSalary = getMasterSalarybyYearAndMonth($intYear, $intMonth, $strCompany);
  if ($dataMasterSalary == 0) {
    $strErrorMessage = "Sorry, payroll calculation has not been done!";
    $strInitAction .= "alert('" . $strErrorMessage . "');";
  } else {
    $strErrorMessage = "";
    $myDataGrid = new cDataGrid("form1", "DataGrid1", "100%", "100%", true, false, false);
    $myDataGrid->disableFormTag();
    $intPageLimit = $myDataGrid->getPageLimit();
    $intPageNumber = $myDataGrid->getPageNumber();
    $arrJamsostek = getJamsostekReport($db, $intYear, $intMonth, $strKriteria, $strType);
    $myDataGrid->setCaption("Report BPJS Ketenagakerjaan - $intYear - $intMonth");
    $myDataGrid->pageSortBy = "";
    if ($strType == 0) {
      $myDataGrid->addSpannedColumn(getWords("rekap tenaga kerja"), 4);
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("bulan lalu"),
              "total_employee_prev",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("penambahan tenaga kerja"),
              "total_employee_join",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("pengurangan tenaga kerja"),
              "total_employee_resign",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("perubahan upah"),
              "total_employee_diff",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addSpannedColumn(getWords("rekap upah"), 4);
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("bulan lalu"),
              "total_base_jamsostek_prev",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "formatNumber()",
              "numeric",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("penambahan tenaga kerja"),
              "total_base_jamsostek_join",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "formatNumber()",
              "numeric",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("pengurangan tenaga kerja"),
              "total_base_jamsostek_resign",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "formatNumber()",
              "numeric",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("perubahan upah"),
              "total_base_jamsostek_diff",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "formatNumber()",
              "numeric",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("total upah"),
              "total_base_jamsostek_all",
              ["rowspan" => 2, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "formatNumber()",
              "numeric",
              true,
              30
          )
      );
      $myDataGrid->addSpannedColumn(getWords("perhitungan iuran"), 6);
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("j h t by company"),
              "total_jamsostek_allowance",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "formatNumber()",
              "numeric",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("j h t by employee"),
              "total_jamsostek_deduction",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "formatNumber()",
              "numeric",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("j p n by company"),
              "total_pension_allowance",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "formatNumber()",
              "numeric",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("j p n by employee"),
              "total_pension_deduction",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "formatNumber()",
              "numeric",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("j k k by company"),
              "total_jkk_allowance",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "formatNumber()",
              "numeric",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("j k m by company"),
              "total_jkm_allowance",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "formatNumber()",
              "numeric",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("total iuran"),
              "total_jamsostek",
              ["rowspan" => 2, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "formatNumber()",
              "numeric",
              true,
              30
          )
      );
    } else if ($strType == 1) {
      $myDataGrid->addColumnNumbering(
          new DataGrid_Column(
              getWords("No"),
              "",
              ["rowspan" => 2, 'width' => 30],
              ['nowrap' => ''],
              false,
              false,
              "",
              "",
              "numeric",
              true,
              4,
              true,
              "nomor"
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("no k p j"),
              "jamsostek_no",
              ["rowspan" => 2, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nomor induk karyawan"),
              "employee_id",
              ["rowspan" => 2, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nomor k t p"),
              "id_card",
              ["rowspan" => 2, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nama lengkap tenaga kerja sesuai k t p / identitas"),
              "name",
              ["rowspan" => 2, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              130
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("tanggal lahir dd-mm-yyyy"),
              "birthdate",
              ["rowspan" => 2, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("upah bulan ini"),
              "base_jamsostek_now",
              ["rowspan" => 2, 'width' => 120],
              ['align' => 'center'],
              false,
              false,
              "",
              "formatNumber()",
              "numeric",
              true,
              12
          )
      );
      $myDataGrid->addSpannedColumn(getWords("rincian"), 6);
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("j k k"),
              "jkk_allowance",
              ["rowspan" => 1, 'width' => 120],
              ['align' => 'center'],
              false,
              false,
              "",
              "formatNumber()",
              "numeric",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("j k m"),
              "jkm_allowance",
              ["rowspan" => 1, 'width' => 120],
              ['align' => 'center'],
              false,
              false,
              "",
              "formatNumber()",
              "numeric",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("j h t tk"),
              "jamsostek_deduction",
              ["rowspan" => 1, 'width' => 120],
              ['align' => 'center'],
              false,
              false,
              "",
              "formatNumber()",
              "numeric",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("j h t prshn"),
              "jamsostek_allowance",
              ["rowspan" => 1, 'width' => 120],
              ['align' => 'center'],
              false,
              false,
              "",
              "formatNumber()",
              "numeric",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("j p n tk"),
              "pension_deduction",
              ["rowspan" => 1, 'width' => 120],
              ['align' => 'center'],
              false,
              false,
              "",
              "formatNumber()",
              "numeric",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("j p n prshn"),
              "pension_allowance",
              ["rowspan" => 1, 'width' => 120],
              ['align' => 'center'],
              false,
              false,
              "",
              "formatNumber()",
              "numeric",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("total iuran"),
              "total",
              ["rowspan" => 2, 'width' => 120],
              ['align' => 'center'],
              false,
              false,
              "",
              "formatNumber()",
              "numeric",
              true,
              12
          )
      );
    } elseif ($strType == 2) {
      $myDataGrid->addColumnNumbering(
          new DataGrid_Column(
              getWords("No"),
              "",
              ["rowspan" => 1, 'width' => 30],
              ['nowrap' => ''],
              false,
              false,
              "",
              "",
              "numeric",
              true,
              4,
              true,
              "nomor"
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("no k p j"),
              "jamsostek_no",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nomor induk karyawan"),
              "employee_id",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nomor k t p"),
              "id_card",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nama lengkap tenaga kerja sesuai k t p / identitas"),
              "name",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              130
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("tempat lahir"),
              "birthplace",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("tanggal lahir dd-mm-yyyy"),
              "birthdate",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("status"),
              "marital_status",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("jenis kelamin"),
              "gender",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("upah"),
              "base_jamsostek_now",
              ["rowspan" => 1, 'width' => 120],
              ['align' => 'center'],
              false,
              false,
              "",
              "formatNumber()",
              "numeric",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nama ibu kandung"),
              "maiden_mother_name",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              130
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("alamat"),
              "primary_address",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              230
          )
      );
    } elseif ($strType == 3) {
      $myDataGrid->addColumnNumbering(
          new DataGrid_Column(
              getWords("No"),
              "",
              ["rowspan" => 1, 'width' => 30],
              ['nowrap' => ''],
              false,
              false,
              "",
              "",
              "numeric",
              true,
              4,
              true,
              "nomor"
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("no k p j"),
              "jamsostek_no",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nomor induk karyawan"),
              "employee_id",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nomor k t p"),
              "id_card",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nama lengkap tenaga kerja sesuai k t p / identitas"),
              "name",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              130
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("tanggal lahir dd-mm-yyyy"),
              "birthdate",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("jenis kelamin"),
              "gender",
              ["rowspan" => 1, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("upah terakhir"),
              "base_jamsostek_prev",
              ["rowspan" => 1, 'width' => 120],
              ['align' => 'center'],
              false,
              false,
              "",
              "formatNumber()",
              "numeric",
              true,
              12
          )
      );
    } elseif ($strType == 4) {
      $myDataGrid->addColumnNumbering(
          new DataGrid_Column(
              getWords("No"),
              "",
              ["rowspan" => 2, 'width' => 30],
              ['nowrap' => ''],
              false,
              false,
              "",
              "",
              "numeric",
              true,
              4,
              true,
              "nomor"
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("no k p j"),
              "jamsostek_no",
              ["rowspan" => 2, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nomor induk karyawan"),
              "employee_id",
              ["rowspan" => 2, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nomor k t p"),
              "id_card",
              ["rowspan" => 2, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nama lengkap tenaga kerja sesuai k t p / identitas"),
              "name",
              ["rowspan" => 2, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              130
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("tanggal lahir dd-mm-yyyy"),
              "birthdate",
              ["rowspan" => 2, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("jenis kelamin"),
              "gender",
              ["rowspan" => 2, 'width' => 120],
              ['nowrap' => ''],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addSpannedColumn(getWords("updah tenaga kerja"), 2);
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("bulan lalu"),
              "base_jamsostek_prev",
              ["rowspan" => 1, 'width' => 120],
              ['align' => 'center'],
              false,
              false,
              "",
              "formatNumber()",
              "numeric",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("bulan ini"),
              "base_jamsostek_now",
              ["rowspan" => 1, 'width' => 120],
              ['align' => 'center'],
              false,
              false,
              "",
              "formatNumber()",
              "numeric",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("selisih"),
              "base_jamsostek_selisih",
              ["rowspan" => 2, 'width' => 120],
              ['align' => 'center'],
              false,
              false,
              "",
              "formatNumber()",
              "numeric",
              true,
              12
          )
      );
    }
    if (isset($_POST['btnExportXLS'])) {
      $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
      $myDataGrid->strFileNameXLS = "jamsostek_report.xls";
      $myDataGrid->strTitle1 = getWords("Report Jamsostek - $intYear - $intMonth");
      $myDataGrid->hasGrandTotal = true;
    }
    $myDataGrid->getRequest();
    $strCriteria = "";
    $myDataGrid->totalData = $totalData;
    $myDataGrid->bind($arrJamsostek);
    $dataGrid = $myDataGrid->render();
  }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('salary report bpjs ketenagakerjaan');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strWordsBPJSKT = getWords("BPJS ketenagakerjaan report");
$pageSubMenu = salaryReportSubMenu($strWordsBPJSKT);
if ($bolPrint) {
  $strMainTemplate = getTemplate("salary_report_jamsostek_print.html");
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//end of main program
// format numeric
function printNumeric($params)
{
  extract($params);
  return number_format($value);
}

// fungsi untuk mengambil data total pajak tahunan karyawan, jika ada
//  jika tidak ada, maka akan dilakukan perhitungan
// output berupa array
function getJamsostekReport($db, $intYear, $intMonth, $strKriteria = "", $strType = 0)
{
  global $_POST;
  $arrResult = [];
  if ($intYear == "") {
    return $arrResult;
  }
  $arrDataJamsostekNow = [];
  $arrDataJamsostekJoin = [];
  $arrDataJamsostekResign = [];
  $arrJamsostekPerubahan = [];
  $arrJamsostekJoin = [];
  $arrJamsostekResign = [];
  $arrJamsostekAll = [];
  $arrJamsostekRecap = [];
  $arrDataSalaryThisMonth = [];
  $arrDataSalaryPrevMonth = [];
  $arrDataSalaryDetailThisMonth = [];
  $arrDataSalaryDetailPrevMonth = [];
  $arrDataEmployee = [];
  $arrDataCompany = [];
  $strCompanyCode = getSetting("company_code");
  $strCompanyAccount = getSetting("company_account");
  $strReportPeriod = "";
  $strSQL = "SELECT id FROM \"hrd_salary_master\" WHERE EXTRACT(YEAR FROM \"salary_date\") = '$intYear' AND EXTRACT(MONTH FROM \"salary_date\") = '$intMonth' AND status=" . REQUEST_STATUS_APPROVED;
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $strSQL = "SELECT * FROM hrd_salary_master WHERE id = " . $row['id'];
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrDataSalaryThisMonth = $rowDb;
      $strSQL2 = "SELECT * FROM hrd_salary_detail WHERE total_net > 0 AND total_gross > 0 AND id_salary_master = " . $rowDb['id'] . " AND jamsostek_deduction > 0";
      $resDb2 = $db->execute($strSQL2);
      while ($rowDb2 = $db->fetchrow($resDb2)) {
        $arrDataSalaryDetailThisMonth[$rowDb2['id_employee']] = $rowDb2;//array_merge($arrDataSalaryDetailThisMonth[$rowDb2['id_employee']], $rowDb2);
      }
    }
  }
  $strReportPeriod = date("F Y", strtotime($arrDataSalaryThisMonth['salary_date']));
  $arrDataSalaryPrevMonth['salary_date'] = date(
      "Y-m-d",
      strtotime("-1 month", strtotime($arrDataSalaryThisMonth['salary_date']))
  );
  $arrDataSalaryPrevMonth['salary_date'] = explode("-", $arrDataSalaryPrevMonth['salary_date']);
  $arrJamsostekRecap[0]['total_base_jamsostek_prev'] = 0;
  $arrJamsostekRecap[0]['total_employee_prev'] = 0;
  $strSQL = "SELECT * FROM hrd_salary_master
    WHERE EXTRACT(YEAR FROM salary_date) = '" . $arrDataSalaryPrevMonth['salary_date'][0] . "' AND EXTRACT(MONTH FROM salary_date) = '" . $arrDataSalaryPrevMonth['salary_date'][1] . "'
    AND status=" . REQUEST_STATUS_APPROVED . " and id_company=" . $arrDataSalaryThisMonth['id_company'];
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDataSalaryPrevMonth = $rowDb;
    $strSQL2 = "SELECT * FROM hrd_salary_detail WHERE total_net > 0 AND total_gross > 0 AND id_salary_master = " . $rowDb['id'] . " AND jamsostek_deduction > 0";
    $resDb2 = $db->execute($strSQL2);
    while ($rowDb2 = $db->fetchrow($resDb2)) {
      $arrDataSalaryDetailPrevMonth[$rowDb2['id_employee']] = $rowDb2;//array_merge($arrDataSalaryDetailPrevMonth[$rowDb2['id_employee']], $rowDb2);
      $arrJamsostekRecap[0]['total_base_jamsostek_prev'] += $rowDb2['base_jamsostek'];
      $arrJamsostekRecap[0]['total_employee_prev'] += 1;
    }
  }
  $strSQL = "SELECT * FROM hrd_company WHERE id = " . $arrDataSalaryThisMonth['id_company'];
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDataCompany = $rowDb;
  }
  $strSQL = "SELECT t0.* FROM hrd_employee as t0 LEFT JOIN hrd_family_status as t1 ON t0.family_status_code = t1.family_status_code WHERE get_jamsostek = 1;";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDataEmployee[$rowDb['id']] = $rowDb;
  }
  foreach ($arrDataEmployee as $strEmployeeID => $arrEmployeeInfo) {
    if (isset($arrDataSalaryDetailPrevMonth[$strEmployeeID]) && isset($arrDataSalaryDetailThisMonth[$strEmployeeID])) {
      $arrDataJamsostekNow[$strEmployeeID]['salary_detail_prev'] = $arrDataSalaryDetailPrevMonth[$strEmployeeID];
      $arrDataJamsostekNow[$strEmployeeID]['salary_detail_now'] = $arrDataSalaryDetailThisMonth[$strEmployeeID];
      $arrDataJamsostekNow[$strEmployeeID]['employee_detail'] = $arrDataEmployee[$strEmployeeID];
    } else if (isset($arrDataSalaryDetailPrevMonth[$strEmployeeID]) && !isset($arrDataSalaryDetailThisMonth[$strEmployeeID])) {
      $arrDataJamsostekResign[$strEmployeeID]['salary_detail_prev'] = $arrDataSalaryDetailPrevMonth[$strEmployeeID];
      $arrDataJamsostekResign[$strEmployeeID]['employee_detail'] = $arrDataEmployee[$strEmployeeID];
    } else if (!isset($arrDataSalaryDetailPrevMonth[$strEmployeeID]) && isset($arrDataSalaryDetailThisMonth[$strEmployeeID])) {
      $arrDataJamsostekJoin[$strEmployeeID]['salary_detail_now'] = $arrDataSalaryDetailThisMonth[$strEmployeeID];
      $arrDataJamsostekJoin[$strEmployeeID]['employee_detail'] = $arrDataEmployee[$strEmployeeID];
    }
  }
  unset($arrDataSalaryThisMonth);
  unset($arrDataSalaryPrevMonth);
  unset($arrDataSalaryDetailPrevMonth);
  // ---------------- CARI DATA PERUBAHAN SALARY --------------
  $arrJamsostekRecap[0]['total_base_jamsostek_diff'] = 0;
  $arrJamsostekRecap[0]['total_employee_diff'] = 0;
  foreach ($arrDataJamsostekNow as $strEmployeeID => $arrEmployeeInfo) {
    $intSalaryChange = $arrEmployeeInfo['salary_detail_now']['base_jamsostek'] - $arrEmployeeInfo['salary_detail_prev']['base_jamsostek'];
    if ($intSalaryChange != 0) {
      $arrJamsostekPerubahan[$strEmployeeID]['base_jamsostek_prev'] = $arrEmployeeInfo['salary_detail_prev']['base_jamsostek'];
      $arrJamsostekPerubahan[$strEmployeeID]['base_jamsostek_now'] = $arrEmployeeInfo['salary_detail_now']['base_jamsostek'];
      $arrJamsostekPerubahan[$strEmployeeID]['base_jamsostek_selisih'] = $arrJamsostekPerubahan[$strEmployeeID]['base_jamsostek_now'] - $arrJamsostekPerubahan[$strEmployeeID]['base_jamsostek_prev'];
      $arrJamsostekPerubahan[$strEmployeeID]['gender'] = ($arrEmployeeInfo['employee_detail']['gender'] == 1) ? 'L' : 'P';
      $arrJamsostekPerubahan[$strEmployeeID]['jamsostek_no'] = $arrEmployeeInfo['employee_detail']['jamsostek_no'];
      $arrJamsostekPerubahan[$strEmployeeID]['id_card'] = $arrEmployeeInfo['employee_detail']['id_card'];
      $arrJamsostekPerubahan[$strEmployeeID]['name'] = $arrEmployeeInfo['employee_detail']['employee_name'];
      $arrJamsostekPerubahan[$strEmployeeID]['employee_id'] = $arrEmployeeInfo['employee_detail']['employee_id'];
      $arrJamsostekPerubahan[$strEmployeeID]['birthdate'] = ($arrEmployeeInfo['employee_detail']['birthday'] != null) ? date(
          "m-d-Y",
          strtotime($arrEmployeeInfo['employee_detail']['birthday'])
      ) : "00-00-0000";
      $arrJamsostekRecap[0]['total_base_jamsostek_diff'] += $intSalaryChange;
      $arrJamsostekRecap[0]['total_employee_diff'] += 1;
    }
  }
  unset($arrDataJamsostekNow);
  // ---------------- END CARI DATA PERUBAHAN SALARY --------------
  // ---------------- CARI DATA JOIN --------------
  $arrJamsostekRecap[0]['total_base_jamsostek_join'] = 0;
  $arrJamsostekRecap[0]['total_employee_join'] = 0;
  foreach ($arrDataJamsostekJoin as $strEmployeeID => $arrEmployeeInfo) {
    $arrJamsostekJoin[$strEmployeeID]['base_jamsostek_now'] = $arrEmployeeInfo['salary_detail_now']['base_jamsostek'];
    $arrJamsostekJoin[$strEmployeeID]['gender'] = ($arrEmployeeInfo['employee_detail']['gender'] == 1) ? 'L' : 'P';
    $arrJamsostekJoin[$strEmployeeID]['marital_status'] = ($arrEmployeeInfo['employee_detail']['is_married'] == 1) ? 'KAWIN' : 'LAJANG';
    $arrJamsostekJoin[$strEmployeeID]['jamsostek_no'] = $arrEmployeeInfo['employee_detail']['jamsostek_no'];
    $arrJamsostekJoin[$strEmployeeID]['id_card'] = $arrEmployeeInfo['employee_detail']['id_card'];
    $arrJamsostekJoin[$strEmployeeID]['name'] = $arrEmployeeInfo['employee_detail']['employee_name'];
    $arrJamsostekJoin[$strEmployeeID]['employee_id'] = $arrEmployeeInfo['employee_detail']['employee_id'];
    $arrJamsostekJoin[$strEmployeeID]['birthdate'] = ($arrEmployeeInfo['employee_detail']['birthday'] != null) ? date(
        "m-d-Y",
        strtotime($arrEmployeeInfo['employee_detail']['birthday'])
    ) : "00-00-0000";
    $arrJamsostekJoin[$strEmployeeID]['birthplace'] = $arrEmployeeInfo['employee_detail']['birthplace'];
    $arrJamsostekJoin[$strEmployeeID]['primary_address'] = $arrEmployeeInfo['employee_detail']['primary_address'];
    $arrJamsostekJoin[$strEmployeeID]['maiden_mother_name'] = $arrEmployeeInfo['employee_detail']['maiden_mother_name'];
    $arrJamsostekRecap[0]['total_base_jamsostek_join'] += $arrJamsostekJoin[$strEmployeeID]['base_jamsostek_now'];
    $arrJamsostekRecap[0]['total_employee_join'] += 1;
  }
  unset($arrDataJamsostekJoin);
  // ---------------- END CARI DATA JOIN --------------
  // ---------------- CARI DATA RESIGN --------------
  $arrJamsostekRecap[0]['total_base_jamsostek_resign'] = 0;
  $arrJamsostekRecap[0]['total_employee_resign'] = 0;
  foreach ($arrDataJamsostekResign as $strEmployeeID => $arrEmployeeInfo) {
    $arrJamsostekResign[$strEmployeeID]['base_jamsostek_prev'] = $arrEmployeeInfo['salary_detail_prev']['base_jamsostek'];
    $arrJamsostekResign[$strEmployeeID]['gender'] = ($arrEmployeeInfo['employee_detail']['gender'] == 1) ? 'L' : 'P';
    $arrJamsostekResign[$strEmployeeID]['marital_status'] = ($arrEmployeeInfo['employee_detail']['is_married'] == 1) ? 'KAWIN' : 'LAJANG';
    $arrJamsostekResign[$strEmployeeID]['jamsostek_no'] = $arrEmployeeInfo['employee_detail']['jamsostek_no'];
    $arrJamsostekResign[$strEmployeeID]['id_card'] = $arrEmployeeInfo['employee_detail']['id_card'];
    $arrJamsostekResign[$strEmployeeID]['name'] = $arrEmployeeInfo['employee_detail']['employee_name'];
    $arrJamsostekResign[$strEmployeeID]['employee_id'] = $arrEmployeeInfo['employee_detail']['employee_id'];
    $arrJamsostekResign[$strEmployeeID]['birthdate'] = ($arrEmployeeInfo['employee_detail']['birthday'] != null) ? date(
        "m-d-Y",
        strtotime($arrEmployeeInfo['employee_detail']['birthday'])
    ) : "00-00-0000";
    $arrJamsostekResign[$strEmployeeID]['birthplace'] = $arrEmployeeInfo['employee_detail']['birthplace'];
    $arrJamsostekResign[$strEmployeeID]['primary_address'] = $arrEmployeeInfo['employee_detail']['primary_address'];
    $arrJamsostekResign[$strEmployeeID]['maiden_mother_name'] = $arrEmployeeInfo['employee_detail']['maiden_mother_name'];
    $arrJamsostekRecap[0]['total_base_jamsostek_resign'] += $arrJamsostekResign[$strEmployeeID]['base_jamsostek_prev'];
    $arrJamsostekRecap[0]['total_employee_resign'] += 1;
  }
  unset($arrDataJamsostekResign);
  // ---------------- END CARI DATA RESIGN --------------
  // ---------------- CARI DETAIL PAID THIS MONTH --------------
  foreach ($arrDataSalaryDetailThisMonth as $strEmployeeID => $arrSalaryInfo) {
    $arrJamsostekAll[$strEmployeeID]['base_jamsostek_now'] = $arrSalaryInfo['base_jamsostek'];
    $arrJamsostekAll[$strEmployeeID]['jamsostek_allowance'] = $arrSalaryInfo['jamsostek_allowance'];
    $arrJamsostekAll[$strEmployeeID]['jkk_allowance'] = $arrSalaryInfo['jkk_allowance'];
    $arrJamsostekAll[$strEmployeeID]['jkm_allowance'] = $arrSalaryInfo['jkm_allowance'];
    $arrJamsostekAll[$strEmployeeID]['jamsostek_deduction'] = $arrSalaryInfo['jamsostek_deduction'];
    $arrJamsostekAll[$strEmployeeID]['pension_deduction'] = $arrSalaryInfo['pension_deduction'];
    $arrJamsostekAll[$strEmployeeID]['pension_allowance'] = $arrSalaryInfo['pension_allowance'];
    $arrJamsostekAll[$strEmployeeID]['total'] = $arrJamsostekAll[$strEmployeeID]['jkk_allowance'] + $arrJamsostekAll[$strEmployeeID]['jkm_allowance']
        + $arrJamsostekAll[$strEmployeeID]['jamsostek_deduction'] + $arrJamsostekAll[$strEmployeeID]['jamsostek_allowance'] + $arrJamsostekAll[$strEmployeeID]['pension_allowance']
        + $arrJamsostekAll[$strEmployeeID]['pension_deduction'];
    $arrJamsostekAll[$strEmployeeID]['jamsostek_no'] = $arrDataEmployee[$strEmployeeID]['jamsostek_no'];
    $arrJamsostekAll[$strEmployeeID]['id_card'] = $arrDataEmployee[$strEmployeeID]['id_card'];
    $arrJamsostekAll[$strEmployeeID]['name'] = $arrDataEmployee[$strEmployeeID]['employee_name'];
    $arrJamsostekAll[$strEmployeeID]['employee_id'] = $arrDataEmployee[$strEmployeeID]['employee_id'];
    $arrJamsostekAll[$strEmployeeID]['birthdate'] = ($arrDataEmployee[$strEmployeeID]['birthday'] != null) ? date(
        "m-d-Y",
        strtotime($arrDataEmployee[$strEmployeeID]['birthday'])
    ) : "00-00-0000";
    $arrJamsostekRecap[0]['total_jamsostek_allowance'] += $arrSalaryInfo['jamsostek_allowance'];
    $arrJamsostekRecap[0]['total_jkk_allowance'] += $arrSalaryInfo['jkk_allowance'];
    $arrJamsostekRecap[0]['total_jkm_allowance'] += $arrSalaryInfo['jkm_allowance'];
    $arrJamsostekRecap[0]['total_jamsostek_deduction'] += $arrSalaryInfo['jamsostek_deduction'];
    $arrJamsostekRecap[0]['total_pension_deduction'] += $arrSalaryInfo['pension_deduction'];
    $arrJamsostekRecap[0]['total_pension_allowance'] += $arrSalaryInfo['pension_allowance'];
  }
  unset($arrDataJamsostekResign);
  unset($arrDataSalaryDetailThisMonth);
  unset($arrDataEmployee);
  // ---------------- CARI DETAIL PAID THIS MONTH --------------
  $arrJamsostekRecap[0]['total_base_jamsostek_all'] = $arrJamsostekRecap[0]['total_base_jamsostek_prev'] + $arrJamsostekRecap[0]['total_base_jamsostek_join']
      + $arrJamsostekRecap[0]['total_base_jamsostek_diff'] - $arrJamsostekRecap[0]['total_base_jamsostek_resign'];
  $arrJamsostekRecap[0]['total_jamsostek'] = $arrJamsostekRecap[0]['total_jamsostek_allowance'] + $arrJamsostekRecap[0]['total_jkk_allowance'] +
      $arrJamsostekRecap[0]['total_jkm_allowance'] + $arrJamsostekRecap[0]['total_jamsostek_deduction'] + $arrJamsostekRecap[0]['total_pension_deduction'] + $arrJamsostekRecap[0]['total_pension_allowance'];
  if ($strType == 0) {
    $arrResult = $arrJamsostekRecap;
  } elseif ($strType == 1) {
    $arrResult = $arrJamsostekAll;
  } elseif ($strType == 2) {
    $arrResult = $arrJamsostekJoin;
  } elseif ($strType == 3) {
    $arrResult = $arrJamsostekResign;
  } else {
    $arrResult = $arrJamsostekPerubahan;
  }
  return $arrResult;
}

function getDataMonth()
{
  global $ARRAY_MONTH;
  $arrResult = [];
  foreach ($ARRAY_MONTH as $key => $val) {
    if ($key == intval(date("m"))) {
      $arrResult[] = ["value" => $key, "text" => $val, "selected" => true];
    } else {
      $arrResult[] = ["value" => $key, "text" => $val, "selected" => false];
    }
  }
  return $arrResult;
}

function getDataYear()
{
  $currYear = intval(date("Y"));
  $arrResult = [];
  for ($i = $currYear; $i > $currYear - 10; $i--) {
    if ($i == $currYear) {
      $arrResult[] = ["value" => $i, "text" => $i, "selected" => true];
    } else {
      $arrResult[] = ["value" => $i, "text" => $i, "selected" => false];
    }
  }
  return $arrResult;
}

function getDataLevel()
{
  global $bolCanViewManagerial;
  $arrResult = [];
  if (!$bolCanViewManagerial) {
    $arrResult[] = ["value" => 1, "text" => "Staff Only", "selected" => true];
  } else {
    $arrResult[] = ["value" => 0, "text" => "All Employee"];
    $arrResult[] = ["value" => 1, "text" => "Staff Only"];
    $arrResult[] = ["value" => 2, "text" => "Managerial Only"];
  }
  return $arrResult;
}

function getDataType()
{
  $arrResult[] = ["value" => 0, "text" => "Recap"];
  $arrResult[] = ["value" => 1, "text" => "Detail (Form 2A)"];
  $arrResult[] = ["value" => 2, "text" => "Tenaga Kerja Masuk (Form 1A)"];
  $arrResult[] = ["value" => 3, "text" => "Tenaga Kerja Keluar (Form 1B)"];
  $arrResult[] = ["value" => 4, "text" => "Perubahan"];
  return $arrResult;
}

function getMasterSalarybyYearAndMonth($intYear, $intMonth, $intCompany)
{
  global $db;
  $bolExist = 0;
  $strSQL = "SELECT id FROM \"hrd_salary_master\" WHERE EXTRACT (YEAR FROM \"salary_date\") = $intYear AND EXTRACT (MONTH FROM \"salary_date\") = $intMonth
      AND status = " . REQUEST_STATUS_APPROVED . " AND id_company = $intCompany";
  // die($strSQL);
  $res = $db->execute($strSQL);
  if (pg_num_rows($res) >= 1) {
    $bolExist = 1;
  } else {
    $bolExist = 0;
  }
  return $bolExist;
}

function formatNumerica($params)
{
  extract($params);
  //	 echo $value; die();
  return standardFormat($value);
  //    return standardFormat($value);
}

//fungsi untuk mengubah format jam ke hitungan menit
function getMinutes($hour_minutes)
{
  $hour = substr($hour_minutes, 0, 2) * 1 * 60;
  $minutes = substr($hour_minutes, 3, 2) * 1;
  return $hour + $minutes;
}

//fungsi untuk mengubah format menit ke format jam
function toHour($minutes)
{
  $hour = floor($minutes / 60);
  if (strlen($hour) == 1) {
    $hour = "0" . $hour;
  }
  $minutes = $minutes % 60;
  if (strlen($minutes) == 1) {
    $minutes = "0" . $minutes;
  }
  $hour_minutes = $hour . ":" . $minutes . ":00";
  return $hour_minutes;
}

?>
