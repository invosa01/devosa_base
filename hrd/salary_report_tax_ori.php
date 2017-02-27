<?php
//if ( !session_id() ) session_start();
ini_set("display_errors", 0);
date_default_timezone_set('Asia/Jakarta');
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/model/model.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('form_object.php');
include_once('../includes/tbsclass/plugins/tbs_plugin_opentbs.php');
include_once("cls_tax_calculation.php");
$dataPrivilege = getDataPrivileges("salary_report.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
$dataPrivilegeManagerial = getDataPrivileges(
    "salary_calculation_managerial.php",
    $bolCanViewManagerial,
    $bolCanEditManagerial,
    $bolCanDeleteManagerial,
    $bolCanApproveManagerial
);
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
    getDataListCompany($strDataCompany, false, $arrCompanyEmptyData, $strKriteria2),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    true
);
$f->addSelect("Year", "dataYear", getDataYear(), ["style" => "width:$strDefaultWidthPx"], "", true);
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
// $f->addSelect(getWords("employee level"), "dataLevel", getDataLevel(), array("style" => "width:$strDefaultWidthPx"), "", false);
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
//$f->addSubmit("btnExportCSV", "Export E-SPT", array(), true, true, "", "", "");
$formInput = $f->render();
//  $strInputEmployee = "<input name=\"btnShow\" type=\"submit\" id=\"btnShow\" value=\"Show Report\">";
//  $strButtonShow = "<input name=\"btnShow\" type=\"submit\" id=\"btnShow\" value=\"Show Report\">";
//  $strButtonExcel = "<input name=\"btnExportXLS\" type=\"submit\" id=\"btnExportXLS\" value=\"Export Excel\">";
$showReport = (isset($_POST['btnShow']) || isset($_POST['btnExportXLS']) || isset($_POST['isShow']));
$totalData = 0;
$dataGrid = "";
$strInitAction = "";
$strStatus = $f->getValue('employeeStatus');
$strName = $f->getValue('employeeName');
// $strLevel = $f->getValue('dataLevel');
$strCompany = $f->getValue('dataCompany');
if ($showReport) {
    $intYear = intval($f->getValue('dataYear'));
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
    // if($strLevel != "")
    // {
    //   if($strLevel == 1)
    //     $strKriteria .= " AND t3.position_group::INTEGER >= 2 ";
    //   elseif ($strLevel == 2)
    //     $strKriteria .= " AND t3.position_group::INTEGER < 2 ";
    //   else $strKriteria .= " AND t3.position_group::INTEGER >= 0 ";
    // }
    //    $strKriteria .= $strKriteriaCompany;
    $dataMasterSalary = getMasterSalarybyYear($intYear, $strCompany);
    //echo $dataMasterSalary;
    if ($dataMasterSalary == 0) {
        $strErrorMessage = "Sorry, payroll calculation of " . $intYear . " has not been done!";
        $strInitAction .= "alert('" . $strErrorMessage . "');";
    } else {
        $strErrorMessage = "";
        $myDataGrid = new cDataGrid("form1", "DataGrid1", "", "", true, false, false);
        $myDataGrid->disableFormTag();
        $intPageLimit = $myDataGrid->getPageLimit();
        $intPageNumber = $myDataGrid->getPageNumber();
        //      echo $myDataGrid->getPageLimit();
        //      echo $myDataGrid->getPageNumber();
        //      echo "<br>";
        $arrAnnualTax = getAnnualTax($db, $intYear, $strKriteria);
        $myDataGrid->setCaption("Report Tax - $intYear");
        $myDataGrid->pageSortBy = "h.\"employeeID\"";
        $myDataGrid->addColumnNumbering(
            new DataGrid_Column(
                "No",
                "",
                ["rowspan" => 1, 'width' => '30'],
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
                "Name",
                "employeeName",
                ["rowspan" => 1],
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
                "NPWP",
                "npwp",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "",
                "string",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Family Status for Pph21",
                "familyStatusCodePph21",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "",
                "string",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Join Date",
                "joinDate",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "",
                "string",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Resign Date",
                "resignDate",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "",
                "string",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Total Regular Income",
                "baseTax",
                ["rowspan" => 1, 'width' => 150],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Total Irregular Income",
                "baseTaxIrregular",
                ["rowspan" => 1, 'width' => 150],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Functional Cost",
                "jabatanAnnual",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Annual Tax",
                "totalAnnualTax",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "January",
                "01",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "February",
                "02",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "March",
                "03",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "April",
                "04",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "May",
                "05",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "June",
                "06",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "July",
                "07",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "August",
                "08",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "September",
                "09",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "October",
                "010",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "November",
                "011",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "December",
                "012",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        // $myDataGrid->addSpannedColumn(getWords("January"),2);
        //   $myDataGrid->addColumn(new DataGrid_Column("Regular", "01", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        //   $myDataGrid->addColumn(new DataGrid_Column("Irregular", "11", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        // $myDataGrid->addSpannedColumn(getWords("February"),2);
        //   $myDataGrid->addColumn(new DataGrid_Column("Regular", "02", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        //   $myDataGrid->addColumn(new DataGrid_Column("Irregular", "12", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        // $myDataGrid->addSpannedColumn(getWords("March"),2);
        //   $myDataGrid->addColumn(new DataGrid_Column("Regular", "03", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        //   $myDataGrid->addColumn(new DataGrid_Column("Irregular", "13", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        // $myDataGrid->addSpannedColumn(getWords("April"),2);
        //   $myDataGrid->addColumn(new DataGrid_Column("Regular", "04", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        //   $myDataGrid->addColumn(new DataGrid_Column("Irregular", "14", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        // $myDataGrid->addSpannedColumn(getWords("May"),2);
        //   $myDataGrid->addColumn(new DataGrid_Column("Regular", "05", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        //   $myDataGrid->addColumn(new DataGrid_Column("Irregular", "15", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        // $myDataGrid->addSpannedColumn(getWords("June"),2);
        //   $myDataGrid->addColumn(new DataGrid_Column("Regular", "06", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        //   $myDataGrid->addColumn(new DataGrid_Column("Irregular", "16", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        // $myDataGrid->addSpannedColumn(getWords("July"),2);
        //   $myDataGrid->addColumn(new DataGrid_Column("Regular", "07", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        //   $myDataGrid->addColumn(new DataGrid_Column("Irregular", "17", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        // $myDataGrid->addSpannedColumn(getWords("August"),2);
        //   $myDataGrid->addColumn(new DataGrid_Column("Regular", "08", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        //   $myDataGrid->addColumn(new DataGrid_Column("Irregular", "18", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        // $myDataGrid->addSpannedColumn(getWords("September"),2);
        //   $myDataGrid->addColumn(new DataGrid_Column("Regular", "09", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        //   $myDataGrid->addColumn(new DataGrid_Column("Irregular", "19", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        // $myDataGrid->addSpannedColumn(getWords("October"),2);
        //   $myDataGrid->addColumn(new DataGrid_Column("Regular", "010", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        //   $myDataGrid->addColumn(new DataGrid_Column("Irregular", "110", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        // $myDataGrid->addSpannedColumn(getWords("November"),2);
        //   $myDataGrid->addColumn(new DataGrid_Column("Regular", "011", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        //   $myDataGrid->addColumn(new DataGrid_Column("Irregular", "111", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        // $myDataGrid->addSpannedColumn(getWords("December"),2);
        //   $myDataGrid->addColumn(new DataGrid_Column("Regular", "012", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        //   $myDataGrid->addColumn(new DataGrid_Column("Irregular", "112", array('width' => 120), array('align' => 'right'), false, false, "","formatNumerica()", "numeric", true, 12));
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Total Monthly",
                "totalMonthly",
                ["rowspan" => 1, 'width' => 120],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        if (!isset($_POST['btnExportXLS'])) {
            $myDataGrid->addColumn(
                new DataGrid_Column(
                    "",
                    "get",
                    ['width' => 80, 'rowspan' => '2'],
                    ['align' => 'right'],
                    true,
                    false,
                    "",
                    "",
                    "string",
                    true,
                    15
                )
            );
        }
        if (isset($_POST['btnExportXLS'])) {
            $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
            $myDataGrid->strFileNameXLS = "Tax Report.xls";
            $myDataGrid->strTitle1 = getWords("Report Tax - $intYear");
            $myDataGrid->hasGrandTotal = true;
        }
        // Report e-SPT
        // if (isset($_POST['btnExportCSV'])){
        //   echo "export E-spt";
        //   //
        //   //exit();
        // }
        //$myDataGrid->addButtonExportExcel(getWords("export excel"), "Tax Report.xls", getWords("Report Tax - $intYea"));
        //if you page can provide permission to view, edit, or delete, then you must set this to control datagrid permission
        //$myDataGrid->setPermission(/*view*/true, /*delete*/true, /*edit*/true);
        $myDataGrid->getRequest();
        $strCriteria = "";
        $myDataGrid->totalData = $totalData;
        $myDataGrid->bind($arrAnnualTax);
        $dataGrid = $myDataGrid->render();
    }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('salary report tax page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strWordsTaxReport = getWords("tax report");
$pageSubMenu = salaryReportSubMenu($strWordsTaxReport);
if ($bolPrint) {
    $strMainTemplate = getTemplate("employee_search_print.html");
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
function getAnnualTax($db, $intYear, $strKriteria = "")
{
    global $_POST;
    $arrResult = [];
    if ($intYear == "") {
        return $arrResult;
    }
    global $intStart;
    global $intPageLimit;
    global $intPageNumber;
    //    echo $intPageLimit;
    global $totalData;
    $intPage = $intPageNumber;
    //    echo $intPage;
    $strSQL = "SELECT id, EXTRACT(MONTH FROM \"salary_date\") as mon FROM \"hrd_salary_master\" WHERE EXTRACT(YEAR FROM \"salary_date\") = '$intYear' AND status=" . REQUEST_STATUS_APPROVED;
    $res = $db->execute($strSQL);
    $intStart = (($intPage - 1) * $intPageLimit);
    while ($row = $db->fetchrow($res)) {
        $salaryMasterID = $row['id'];
        $salaryMasterMonth = $row['mon'];
        $strSQL2 = "SELECT t0.\"id_employee\", t0.\"employee_id\", \"base_tax\", \"base_irregular_tax\", tax, \"irregular_tax\" , jkk_allowance, \"basic_salary\", t0.npwp,
                        t1.\"tax_status_code\", \"join_date\", \"resign_date\", t1.\"employee_name\", \"primary_address\",t1.\"primary_city\",t1.\"primary_zip\", t1.gender, t2.\"tax_reduction\", t3.\"position_name\", t2.children, t2.\"marital_status\", t0.jamsostek_deduction, t0.pension_deduction
                    FROM \"hrd_salary_detail\" AS t0
                        LEFT JOIN \"hrd_employee\" AS t1 ON t0.\"id_employee\" = t1.id
                        LEFT JOIN \"hrd_family_status\" AS t2 ON t2.family_status_code = t1.\"family_status_code\"
                        LEFT JOIN \"hrd_position\" AS t3 ON t3.\"position_code\" = t0.\"position_code\"
                    WHERE \"id_salary_master\" ='$salaryMasterID' $strKriteria ORDER BY t1.\"employee_name\"";
        $res3 = $db->execute($strSQL2);
        while ($row3 = $db->fetchrow($res3)) {
            $totalData += 1;
        }
        if (!isset($_POST['btnExportXLS'])) {
            $strSQL2 .= "LIMIT $intPageLimit OFFSET $intStart";
        }
        $res4 = $db->execute($strSQL2);
        if ($db->fetchrow($res4) < 1) {
            $intPageNumber = 1;
            $intStart = 0;
        }
        $res2 = $db->execute($strSQL2);
        while ($row2 = $db->fetchrow($res2)) {
            $arrResult[$row2['id_employee']]['id'] = $row2['id_employee'];
            $arrResult[$row2['id_employee']]['nik'] = $row2['employee_id'];
            $arrResult[$row2['id_employee']]['position'] = $row2['position_name'];
            $arrResult[$row2['id_employee']]['marital'] = $row2['marital_status'];
            $arrResult[$row2['id_employee']]['children'] = $row2['children'];
            $arrResult[$row2['id_employee']]['baseTax'] += ($row2['base_tax'] > 0) ? $row2['base_tax'] : 0;
            $arrResult[$row2['id_employee']]['baseTaxIrregular'] += ($row2['base_irregular_tax'] > 0) ? $row2['base_irregular_tax'] : 0;
            $arrResult[$row2['id_employee']]['tax'] += ($row2['tax'] > 0) ? $row2['tax'] : 0;
            $arrResult[$row2['id_employee']]['taxIrregular'] += ($row2['irregular_tax'] > 0) ? $row2['irregular_tax'] : 0;
            $arrResult[$row2['id_employee']]['npwp'] = $row2['npwp'];
            $arrResult[$row2['id_employee']]['familyStatusCodePph21'] = $row2['tax_status_code'];
            $arrResult[$row2['id_employee']]['joinDate'] = $row2['join_date'];
            $arrResult[$row2['id_employee']]['resignDate'] = $row2['resign_date'];
            $arrResult[$row2['id_employee']]['jkjkk'] += ($row2['jkk_allowance'] > 0) ? $row2['jkk_allowance'] : 0;
            $arrResult[$row2['id_employee']]['jamsostek_deduction'] += ($row2['jamsostek_deduction'] > 0) ? $row2['jamsostek_deduction'] : 0;
            $arrResult[$row2['id_employee']]['pension_deduction'] += ($row2['pension_deduction'] > 0) ? $row2['pension_deduction'] : 0;
            $arrResult[$row2['id_employee']]['basicSalary'] += $row2['basic_salary'];
            $arrResult[$row2['id_employee']]['allowance'] += $row2['base_tax'] - $row2['basic_salary'] - $row2['jkk_allowance'] - $row2['tax'] - $row2['irregular_tax'];
            $arrResult[$row2['id_employee']]['employeeName'] = $row2['employee_name'];
            $arrResult[$row2['id_employee']]['primaryAddress'] = $row2['primary_address'];
            $arrResult[$row2['id_employee']]['primaryCity'] = $row2['primary_city'];
            $arrResult[$row2['id_employee']]['primaryZip'] = $row2['primary_zip'];
            $arrResult[$row2['id_employee']]['gender'] = $row2['gender'];
            $arrResult[$row2['id_employee']]['01'] += ($salaryMasterMonth == 1) ? ($row2['tax'] + $row2['irregular_tax']) : 0;
            $arrResult[$row2['id_employee']]['02'] += ($salaryMasterMonth == 2) ? ($row2['tax'] + $row2['irregular_tax']) : 0;
            $arrResult[$row2['id_employee']]['03'] += ($salaryMasterMonth == 3) ? ($row2['tax'] + $row2['irregular_tax']) : 0;
            $arrResult[$row2['id_employee']]['04'] += ($salaryMasterMonth == 4) ? ($row2['tax'] + $row2['irregular_tax']) : 0;
            $arrResult[$row2['id_employee']]['05'] += ($salaryMasterMonth == 5) ? ($row2['tax'] + $row2['irregular_tax']) : 0;
            $arrResult[$row2['id_employee']]['06'] += ($salaryMasterMonth == 6) ? ($row2['tax'] + $row2['irregular_tax']) : 0;
            $arrResult[$row2['id_employee']]['07'] += ($salaryMasterMonth == 7) ? ($row2['tax'] + $row2['irregular_tax']) : 0;
            $arrResult[$row2['id_employee']]['08'] += ($salaryMasterMonth == 8) ? ($row2['tax'] + $row2['irregular_tax']) : 0;
            $arrResult[$row2['id_employee']]['09'] += ($salaryMasterMonth == 9) ? ($row2['tax'] + $row2['irregular_tax']) : 0;
            $arrResult[$row2['id_employee']]['010'] += ($salaryMasterMonth == 10) ? ($row2['tax'] + $row2['irregular_tax']) : 0;
            $arrResult[$row2['id_employee']]['011'] += ($salaryMasterMonth == 11) ? ($row2['tax'] + $row2['irregular_tax']) : 0;
            $arrResult[$row2['id_employee']]['012'] += ($salaryMasterMonth == 12) ? ($row2['tax'] + $row2['irregular_tax']) : 0;
            // $arrResult[$row2['id_employee']]['11'] += ($salaryMasterMonth == 1) ? $row2['irregular_tax'] : 0;
            // $arrResult[$row2['id_employee']]['12'] += ($salaryMasterMonth == 2) ? $row2['irregular_tax'] : 0;
            // $arrResult[$row2['id_employee']]['13'] += ($salaryMasterMonth == 3) ? $row2['irregular_tax'] : 0;
            // $arrResult[$row2['id_employee']]['14'] += ($salaryMasterMonth == 4) ? $row2['irregular_tax'] : 0;
            // $arrResult[$row2['id_employee']]['15'] += ($salaryMasterMonth == 5) ? $row2['irregular_tax'] : 0;
            // $arrResult[$row2['id_employee']]['16'] += ($salaryMasterMonth == 6) ? $row2['irregular_tax'] : 0;
            // $arrResult[$row2['id_employee']]['17'] += ($salaryMasterMonth == 7) ? $row2['irregular_tax'] : 0;
            // $arrResult[$row2['id_employee']]['18'] += ($salaryMasterMonth == 8) ? $row2['irregular_tax'] : 0;
            // $arrResult[$row2['id_employee']]['19'] += ($salaryMasterMonth == 9) ? $row2['irregular_tax'] : 0;
            // $arrResult[$row2['id_employee']]['110'] += ($salaryMasterMonth == 10) ? $row2['irregular_tax'] : 0;
            // $arrResult[$row2['id_employee']]['111'] += ($salaryMasterMonth == 11) ? $row2['irregular_tax'] : 0;
            // $arrResult[$row2['id_employee']]['112'] += ($salaryMasterMonth == 12) ? $row2['irregular_tax'] : 0;
            $arrResult[$row2['id_employee']]['taxAnnual'] = 0;
            $arrResult[$row2['id_employee']]['taxIrregularAnnual'] = 0;
            $arrResult[$row2['id_employee']]['jabatanAnnual'] = 0;
            $arrResult[$row2['id_employee']]['totalMonthly'] += $row2['tax'] + $row2['irregular_tax'];
            $arrResult[$row2['id_employee']]['totalAnnualTax'] = 0;
            $arrResult[$row2['id_employee']]['get'] = "";
            $arrResult[$row2['id_employee']]['year'] = $intYear;
            $arrResult[$row2['id_employee']]['ptkp'] = $row2['tax_reduction'];
        }
    }
    $i = 0;
    foreach ($arrResult as $arrEmployee) {
        $i++;
        $objTax = new clsTaxCalculation($db);
        $bolNPWP = (trim($arrEmployee['npwp']) != "");
        $strFamilyStatusPph21 = $arrEmployee['familyStatusCodePph21'];
        $fltBasic = $arrEmployee['baseTax'];
        $fltBasicIrregular = $arrEmployee['baseTaxIrregular'];
        $fltJamsostekDeduction = $arrEmployee['jamsostek_deduction'];
        $fltPensionDeduction = $arrEmployee['pension_deduction'];
        $taxMethod = 1;
        $strIDEmployee = $arrEmployee['id'];
        $strJoinDate = $arrEmployee['joinDate'];
        $strResignDate = $arrEmployee['resignDate'];
        $objTax->setDataIncludeIrregular(
            $fltBasic,
            $fltBasicIrregular,
            $strFamilyStatusPph21,
            $bolNPWP,
            $fltJamsostekDeduction,
            $fltPensionDeduction,
            $strIDEmployee,
            1,
            null,
            12,
            $intYear,
            $strJoinDate,
            $strResignDate,
            false
        );
        $fltTax = $objTax->getTaxAnnual(true);
        $fltIrregularTax = $objTax->getTaxAnnual(false);
        $fltTax = ($fltTax < 0) ? 0 : $fltTax;
        $fltIrregularTax = ($fltIrregularTax < 0) ? 0 : $fltIrregularTax;
        $arrResult[$strIDEmployee]['taxAnnual'] += $fltTax;
        $arrResult[$strIDEmployee]['taxIrregularAnnual'] += $fltIrregularTax;
        $intJabatan = 0.05 * ($fltBasic + $fltBasicIrregular);
        $intJabatan = ($intJabatan <= 6000000) ? $intJabatan : 6000000;
        $arrResult[$strIDEmployee]['jabatanAnnual'] += $intJabatan;
        unset($objTax);
        //$arrResult[$strIDEmployee]['totalAnnualTax']=0;
        $arrResult[$strIDEmployee]['totalAnnualTax'] = $fltTax + $fltIrregularTax;
        $sResult = serialize($arrResult[$strIDEmployee]);
        $sResult = str_replace('"', '$%^', $sResult);
        //        die($sResult);
        $arrResult[$strIDEmployee]['get'] = "<form action=\"form_pph.php\" method=\"POST\" target=\"_blank\">
                                            <input type=\"hidden\" name=\"result\" value=\"$sResult\">
                                            <input type=\"hidden\" name=\"row\" value=\"$i\">
                                            <input type=\"submit\" value=\"Form 1721-A1\">
                                            </form>";
        //Total Per Kolom
        $arrResult['total']['employeeName'] = "TOTAL";
        $arrResult['total']['baseTax'] += $arrResult[$strIDEmployee]['baseTax'];
        $arrResult['total']['baseTaxIrregular'] += $arrResult[$strIDEmployee]['baseTaxIrregular'];
        $arrResult['total']['jabatanAnnual'] += $arrResult[$strIDEmployee]['jabatanAnnual'];
        $arrResult['total']['taxAnnual'] += $arrResult[$strIDEmployee]['taxAnnual'];
        $arrResult['total']['taxIrregularAnnual'] += $arrResult[$strIDEmployee]['taxIrregularAnnual'];
        $arrResult['total']['totalAnnualTax'] += $arrResult[$strIDEmployee]['totalAnnualTax'];
        $arrResult['total']['01'] += $arrResult[$strIDEmployee]['01'];
        $arrResult['total']['02'] += $arrResult[$strIDEmployee]['02'];
        $arrResult['total']['03'] += $arrResult[$strIDEmployee]['03'];
        $arrResult['total']['04'] += $arrResult[$strIDEmployee]['04'];
        $arrResult['total']['05'] += $arrResult[$strIDEmployee]['05'];
        $arrResult['total']['06'] += $arrResult[$strIDEmployee]['06'];
        $arrResult['total']['07'] += $arrResult[$strIDEmployee]['07'];
        $arrResult['total']['08'] += $arrResult[$strIDEmployee]['08'];
        $arrResult['total']['09'] += $arrResult[$strIDEmployee]['09'];
        $arrResult['total']['010'] += $arrResult[$strIDEmployee]['010'];
        $arrResult['total']['011'] += $arrResult[$strIDEmployee]['011'];
        $arrResult['total']['012'] += $arrResult[$strIDEmployee]['012'];
        $arrResult['total']['11'] += $arrResult[$strIDEmployee]['11'];
        $arrResult['total']['12'] += $arrResult[$strIDEmployee]['12'];
        $arrResult['total']['13'] += $arrResult[$strIDEmployee]['13'];
        $arrResult['total']['14'] += $arrResult[$strIDEmployee]['14'];
        $arrResult['total']['15'] += $arrResult[$strIDEmployee]['15'];
        $arrResult['total']['16'] += $arrResult[$strIDEmployee]['16'];
        $arrResult['total']['17'] += $arrResult[$strIDEmployee]['17'];
        $arrResult['total']['18'] += $arrResult[$strIDEmployee]['18'];
        $arrResult['total']['19'] += $arrResult[$strIDEmployee]['19'];
        $arrResult['total']['110'] += $arrResult[$strIDEmployee]['110'];
        $arrResult['total']['111'] += $arrResult[$strIDEmployee]['111'];
        $arrResult['total']['112'] += $arrResult[$strIDEmployee]['112'];
        $arrResult['total']['totalMonthly'] += $arrResult[$strIDEmployee]['totalMonthly'];
    }
    //print_r($arrResult);die();
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

function getMasterSalaryByMonthAndYear($intMonth, $intYear)
{
    global $db;
    $bolExist = 0;
    $strSQL = "SELECT id FROM \"hrd_salary_master\" WHERE EXTRACT(MONTH FROM \"salary_date\") = $intMonth AND EXTRACT (YEAR FROM \"salary_date\") = $intYear";
    $res = $db->execute($strSQL);
    $rowDb = $db->fetchrow($res);
    if (pg_num_rows($res) > 1) {
        $bolExist = 1;
    } else {
        $bolExist = 0;
    }
    return $bolExist;
}

function getMasterSalaryByYear($intYear, $intCompany)
{
    global $db;
    $bolExist = 0;
    $strSQL = "SELECT id FROM \"hrd_salary_master\" WHERE EXTRACT (YEAR FROM \"salary_date\") = $intYear AND status = " . REQUEST_STATUS_APPROVED . " AND id_company = $intCompany";
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

?>
