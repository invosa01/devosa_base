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
// // $f->addSelect(getWords("employee level"), "dataLevel", getDataLevel(), array("style" => "width:$strDefaultWidthPx"), "", false);
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
// $strLevel = $f->getValue('dataLevel');
$strCompany = $f->getValue('dataCompany');
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
    // if($strLevel != "")
    // {
    //   if($strLevel == 1)
    //     $strKriteria .= " AND t2.position_group::INTEGER >= 2 ";
    //   elseif ($strLevel == 2)
    //     $strKriteria .= " AND t2.position_group::INTEGER < 2 ";
    //   else $strKriteria .= " AND t2.position_group::INTEGER >= 0 ";
    // }
    $dataMasterSalary = getMasterSalarybyYearAndMonth($intYear, $intMonth, $strCompany);
    if ($dataMasterSalary == 0) {
        $strErrorMessage = "Sorry, payroll calculation has not been done!";
        $strInitAction .= "alert('" . $strErrorMessage . "');";
    } else {
        $strErrorMessage = "";
        $myDataGrid = new cDataGrid("form1", "DataGrid1", "", "", true, false, false);
        $myDataGrid->disableFormTag();
        $intPageLimit = $myDataGrid->getPageLimit();
        $intPageNumber = $myDataGrid->getPageNumber();
        $arrSalary = getSalaryCumulativeReport($db, $intYear, $intMonth, $strKriteria);
        $myDataGrid->setCaption("Salary Report Cumulative- $intYear - $intMonth");
        $myDataGrid->pageSortBy = "code";
        $myDataGrid->addColumnNumbering(
            new DataGrid_Column(
                "No",
                "",
                ['width' => 30],
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
                "NIK", "nik", ['width' => 120], ['nowrap' => ''], true, true, "", "", "string", true, 30
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Employee Name",
                "employee_name",
                ['width' => 120],
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
                "Division Code",
                "code",
                ['width' => 120],
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
                "Division Name",
                "name",
                ['width' => 120],
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
                "Basic Salary",
                "basic_salary",
                ['width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "formatNumber()",
                "numeric",
                true,
                12,
                true,
                "basic_salary"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Other Taxable Allowance",
                "other_tax_allowance",
                ['width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "formatNumber()",
                "numeric",
                true,
                12,
                true,
                "other_tax_allowance"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Tax Allowance",
                "tax_allowance",
                ['width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "formatNumber()",
                "numeric",
                true,
                12,
                true,
                "tax_allowance"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Other Non Taxable Allowance",
                "other_non_tax_allowance",
                ['width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "formatNumber()",
                "numeric",
                true,
                12,
                true,
                "other_non_tax_allowance"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Overtime Allowance",
                "overtime_allowance",
                ['width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "formatNumber()",
                "numeric",
                true,
                12,
                true,
                "overtime_allowance"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Jamsostek Deduction",
                "jamsostek_deduction",
                ['width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "formatNumber()",
                "numeric",
                true,
                12,
                true,
                "jamsostek_deduction"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "BPJS Deduction",
                "bpjs_deduction",
                ['width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "formatNumber()",
                "numeric",
                true,
                12,
                true,
                "bpjs_deduction"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Pension Deduction",
                "pension_deduction",
                ['width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "formatNumber()",
                "numeric",
                true,
                12,
                true,
                "pension_deduction"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Other Deduction",
                "deduction",
                ['width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "formatNumber()",
                "numeric",
                true,
                12,
                true,
                "deduction"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Tax",
                "tax",
                ['width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "formatNumber()",
                "numeric",
                true,
                12,
                true,
                "tax"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Total",
                "total",
                ['width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "formatNumber()",
                "numeric",
                true,
                12,
                true,
                "total"
            )
        );
        if (isset($_POST['btnExportXLS'])) {
            $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
            $myDataGrid->strFileNameXLS = "Salary Report Cumulative.xls";
            $myDataGrid->strTitle1 = getWords("Salary Report Cumulative- $intYear - $intMonth");
            $myDataGrid->groupBy("code");
            $myDataGrid->hasGrandTotal = true;
        }
        $myDataGrid->getRequest();
        $strCriteria = "";
        $myDataGrid->totalData = $totalData;
        $myDataGrid->bind($arrSalary);
        $dataGrid = $myDataGrid->render();
    }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = "Salary Report Cumulative";
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('salary report cumulative page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strWordsSalaryReportCumulative = getWords("salary report cumulative");
$pageSubMenu = salaryReportSubMenu($strWordsSalaryReportCumulative);
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
function getSalaryCumulativeReport($db, $intYear, $intMonth, $strKriteria = "")
{
    global $_POST;
    $arrResult = [];
    if ($intYear == "") {
        return $arrResult;
    }
    $strSQL = "SELECT id FROM \"hrd_salary_master\" WHERE EXTRACT(YEAR FROM \"salary_date\") = '$intYear' AND EXTRACT(MONTH FROM \"salary_date\") <= '$intMonth' AND status=" . REQUEST_STATUS_APPROVED;
    $res = $db->execute($strSQL);
    while ($row = $db->fetchrow($res)) {
        $salaryMasterID = $row['id'];
        $strSQL2 = "SELECT t0.*, t1.employee_name, t1.employee_id as nik, t1.division_code as code
                    FROM \"hrd_salary_detail\" AS t0
                        LEFT JOIN \"hrd_employee\" AS t1 ON t0.\"id_employee\" = t1.id
                        WHERE \"id_salary_master\" ='$salaryMasterID' $strKriteria  AND total_gross > 0 ORDER BY code";
        $res2 = $db->execute($strSQL2);
        while ($row2 = $db->fetchrow($res2)) {
            $arrResult[$row2['id_employee']]['id'] = $row2['id_employee'];
            $arrResult[$row2['id_employee']]['nik'] = $row2['nik'];
            $arrResult[$row2['id_employee']]['employee_name'] = $row2['employee_name'];
            $arrResult[$row2['id_employee']]['code'] = $row2['code'];
            $arrResult[$row2['id_employee']]['name'] = getDivisionName($row2['code']);
            // $arrResult[$row2['id_employee']]['basic_salary'] += toHour($row2['total_ot_min']);
            $arrResult[$row2['id_employee']]['tax_allowance'] += $row2['tax_allowance'];
            $arrResult[$row2['id_employee']]['tax_allowance'] += $row2['irregular_tax_allowance'];
            $arrResult[$row2['id_employee']]['overtime_allowance'] += $row2['overtime_allowance'];
            $arrResult[$row2['id_employee']]['jamsostek_allowance'] += $row2['jamsostek_allowance'];
            $arrResult[$row2['id_employee']]['jkk_allowance'] += $row2['jkk_allowance'];
            $arrResult[$row2['id_employee']]['jkm_allowance'] += $row2['jkm_allowance'];
            $arrResult[$row2['id_employee']]['bpjs_allowance'] += $row2['bpjs_allowance'];
            $arrResult[$row2['id_employee']]['pension_allowance'] += $row2['pension_allowance'];
            $arrResult[$row2['id_employee']]['jamsostek_deduction'] += $row2['jamsostek_deduction'];
            $arrResult[$row2['id_employee']]['bpjs_deduction'] += $row2['bpjs_deduction'];
            $arrResult[$row2['id_employee']]['pension_deduction'] += $row2['pension_deduction'];
            $arrResult[$row2['id_employee']]['deduction'] += $row2['loan_deduction'];
            $arrResult[$row2['id_employee']]['deduction'] += $row2['absence_deduction'];
            $arrResult[$row2['id_employee']]['deduction'] += $row2['late_deduction'];
            $arrResult[$row2['id_employee']]['tax'] += $row2['tax'];
            $arrResult[$row2['id_employee']]['tax'] += $row2['irregular_tax'];
            $arrResult[$row2['id_employee']]['total'] += $row2['total_gross'];
        }
        $strSQL5 = "SELECT t0.*, t1.division_code
                   FROM \"hrd_salary_allowance\" AS t0
                       LEFT JOIN \"hrd_employee\" AS t1 ON t0.\"id_employee\" = t1.id
                   WHERE \"id_salary_master\" ='$salaryMasterID' $strKriteria ";
        $res5 = $db->execute($strSQL5);
        while ($row5 = $db->fetchrow($res5)) {
            if ($row5['allowance_code'] == 'basic_salary') {
                $arrResult[$row5['id_employee']]['basic_salary'] += $row5['amount'];
            }
            //uddin20160213 ubah jamsostek_allowance menjadi jamsostek_allowance_manual
            if ($row5['allowance_code'] == 'other_tax_allowance'
                || $row5['allowance_code'] == 'jabatan_allowance'
                || $row5['allowance_code'] == 'transport_allowance'
                || $row5['allowance_code'] == 'jamsostek_allowance_manual'
                || $row5['allowance_code'] == 'other_allowance'
            ) {
                $arrResult[$row5['id_employee']]['other_tax_allowance'] += $row5['amount'];
            }
            if ($row5['allowance_code'] == 'other_non_tax_allowance'
                || $row5['allowance_code'] == 'severance_allowance'
                || $row5['allowance_code'] == 'suka_duka_allowance'
                || $row5['allowance_code'] == 'vehicle_allowance'
            ) {
                $arrResult[$row5['id_employee']]['other_non_tax_allowance'] += $row5['amount'];
            }
            if ($row5['allowance_code'] == 'overtime_allowance') {
                $arrResult[$row5['id_employee']]['overtime_allowance'] += $row5['amount'];
            }
        }
        $strSQL6 = "SELECT t0.*, t1.division_code
                  FROM \"hrd_salary_deduction\" AS t0
                      LEFT JOIN \"hrd_employee\" AS t1 ON t0.\"id_employee\" = t1.id
                  WHERE \"id_salary_master\" ='$salaryMasterID' $strKriteria ";
        $res6 = $db->execute($strSQL6);
        while ($row6 = $db->fetchrow($res6)) {
            $arrResult[$row6['id_employee']]['deduction'] += $row6['amount'];
        }
        // print_r($arrResult);die();
    }
    usort($arrResult, "compareByDivision");
    return $arrResult;
}

function compareByDivision($a, $b)
{
    // print_r($a);die();
    return strcmp($a['code'], $b['code']);
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
