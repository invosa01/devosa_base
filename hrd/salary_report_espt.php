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
$f->addSelect(
    "Month",
    "dataMonth",
    getDataMonth(),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    true,
    true,
    true,
    "",
    "Mandatory for E-SPT Masa"
);
$f->addSelect(
    getWords("branch"),
    "dataBranch",
    getDataListBranch(getInitialValue("Branch"), true, ['value' => '', 'text' => '', 'selected' => true]),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false
);
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
$f->addSelect(
    getWords("e s p t report type"),
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
$strBranch = $f->getValue('dataBranch');
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
        $strKriteria .= " AND t1.id_company = $strCompany ";
    }
    if ($strBranch !== '') {
        $strKriteria .= " AND t1.branch_code = $strBranch ";
    }
    //if tahunan
    if ($strType != 1) {
        $dataMasterSalary = getMasterSalarybyYearAndMonth($intYear, $intMonth, $strCompany);
    } else {
        $dataMasterSalary = getMasterSalarybyYear($intYear, $strCompany);
    }
    if ($dataMasterSalary == 0) {
        $strErrorMessage = "Sorry, payroll calculation has not been done!";
        $strInitAction .= "alert('" . $strErrorMessage . "');";
    } else {
        if ($strType != 1) {
            $strErrorMessage = "";
            $myDataGrid = new cDataGrid("form1", "DataGrid1", "100%", "100%", true, false, false);
            $myDataGrid->disableFormTag();
            $intPageLimit = $myDataGrid->getPageLimit();
            $intPageNumber = $myDataGrid->getPageNumber();
            $arrESPTMasa = getESPTMasa($db, $intYear, $intMonth, $strKriteria);
            $myDataGrid->setCaption("Report ESPT Masa - $intYear - $intMonth");
            $myDataGrid->pageSortBy = "";
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
                    "masa pajak",
                    "masa_pajak",
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
                    "tahun pajak",
                    "tahun_pajak",
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
                    "pembetulan",
                    "pembetulan",
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
                    "npwp",
                    "npwp",
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
                    "nama",
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
                    "kode pajak",
                    "kode_pajak",
                    ['width' => 120],
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
                    "jumlah bruto",
                    "total_base_tax",
                    ['width' => 120],
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
                    "jumlah pph",
                    "total_tax",
                    ['width' => 120],
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
                    "kode negara",
                    "kode_negara",
                    ['width' => 120],
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
            if (isset($_POST['btnExportXLS'])) {
                $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
                $myDataGrid->strFileNameXLS = "ESPT Masa Report.xls";
                $myDataGrid->strTitle1 = getWords("Report ESPT Masa - $intYear - $intMonth");
                $myDataGrid->hasGrandTotal = true;
            }
            $myDataGrid->getRequest();
            $strCriteria = "";
            $myDataGrid->totalData = $totalData;
            $myDataGrid->bind($arrESPTMasa);
            $dataGrid = $myDataGrid->render();
        } else {
            $strErrorMessage = "";
            $myDataGrid = new cDataGrid("form1", "DataGrid1", "100%", "100%", true, false, false);
            $myDataGrid->disableFormTag();
            $intPageLimit = $myDataGrid->getPageLimit();
            $intPageNumber = $myDataGrid->getPageNumber();
            $arrESPTTahunan = getESPTTahunan($db, $intYear, $strKriteria);
            $myDataGrid->setCaption("Report ESPT Tahunan - $intYear");
            $myDataGrid->pageSortBy = "";
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
                    getWords("masa pajak"),
                    "masa_pajak",
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
                    getWords("tahun pajak"),
                    "tahun_pajak",
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
                    getWords("pembetulan"),
                    "pembetulan",
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
                    getWords("no bukti potong"),
                    "no_bukti_potong",
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
                    getWords("masa awal"),
                    "masa_awal",
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
                    getWords("masa akhir"),
                    "masa_akhir",
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
                    getWords("npwp"),
                    "npwp",
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
                    getWords("n i k"),
                    "nik",
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
                    getWords("nama"),
                    "nama",
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
                    getWords("alamat"),
                    "alamat",
                    ['width' => 220],
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
                    "jenis_kelamin",
                    ['width' => 220],
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
                    getWords("status p t k p"),
                    "status_ptkp",
                    ['width' => 220],
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
                    getWords("jumlah tanggungan"),
                    "jumlah_tanggungan",
                    ['width' => 220],
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
                    getWords("nama jabatan"),
                    "nama_jabatan",
                    ['width' => 220],
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
                    getWords("w p luar negeri"),
                    "wp_luar_negeri",
                    ['width' => 220],
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
                    getWords("kode negara"),
                    "kode_negara",
                    ['width' => 120],
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
                    getWords("kode pajak"),
                    "kode_pajak",
                    ['width' => 120],
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
                    getWords("jumlah 1"),
                    "jumlah_1",
                    ['width' => 120],
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
                    getWords("jumlah 2"),
                    "jumlah_2",
                    ['width' => 120],
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
                    getWords("jumlah 3"),
                    "jumlah_3",
                    ['width' => 120],
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
                    getWords("jumlah 4"),
                    "jumlah_4",
                    ['width' => 120],
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
                    getWords("jumlah 5"),
                    "jumlah_5",
                    ['width' => 120],
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
                    getWords("jumlah 6"),
                    "jumlah_6",
                    ['width' => 120],
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
                    getWords("jumlah 7"),
                    "jumlah_7",
                    ['width' => 120],
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
                    getWords("jumlah 8"),
                    "jumlah_8",
                    ['width' => 120],
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
                    getWords("jumlah 9"),
                    "jumlah_9",
                    ['width' => 120],
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
                    getWords("jumlah 10"),
                    "jumlah_10",
                    ['width' => 120],
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
                    getWords("jumlah 11"),
                    "jumlah_11",
                    ['width' => 120],
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
                    getWords("jumlah 12"),
                    "jumlah_12",
                    ['width' => 120],
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
                    getWords("jumlah 13"),
                    "jumlah_13",
                    ['width' => 120],
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
                    getWords("jumlah 14"),
                    "jumlah_14",
                    ['width' => 120],
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
                    getWords("jumlah 15"),
                    "jumlah_15",
                    ['width' => 120],
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
                    getWords("jumlah 16"),
                    "jumlah_16",
                    ['width' => 120],
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
                    getWords("jumlah 17"),
                    "jumlah_17",
                    ['width' => 120],
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
                    getWords("jumlah 18"),
                    "jumlah_18",
                    ['width' => 120],
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
                    getWords("jumlah 19"),
                    "jumlah_19",
                    ['width' => 120],
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
                    getWords("jumlah 20"),
                    "jumlah_20",
                    ['width' => 120],
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
                    getWords("status pindah"),
                    "status_pindah",
                    ['width' => 120],
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
                    getWords("npwp pemotong"),
                    "npwp_pemotong",
                    ['width' => 150],
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
                    getWords("nama pemotong"),
                    "nama_pemotong",
                    ['width' => 160],
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
                    getWords("tanggal bukti potong"),
                    "tanggal_bukti_potong",
                    ['width' => 120],
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
            if (isset($_POST['btnExportXLS'])) {
                $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
                $myDataGrid->strFileNameXLS = "ESPT Tahunan Report.xls";
                $myDataGrid->strTitle1 = getWords("Report ESPT Tahunan - $intYear");
                $myDataGrid->hasGrandTotal = true;
            }
            $myDataGrid->getRequest();
            $strCriteria = "";
            $myDataGrid->totalData = $totalData;
            $myDataGrid->bind($arrESPTTahunan);
            $dataGrid = $myDataGrid->render();
        }
    }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('salary report e-spt page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strWordsESPTReport = getWords("E-SPT report");
$pageSubMenu = salaryReportSubMenu($strWordsESPTReport);
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
function getESPTMasa($db, $intYear, $intMonth, $strKriteria = "")
{
    global $_POST;
    $arrResult = [];
    if ($intYear == "") {
        return $arrResult;
    }
    global $intStart;
    global $intPageLimit;
    global $intPageNumber;
    global $totalData;
    $intPage = $intPageNumber;
    $strSQL = "SELECT id FROM \"hrd_salary_master\" WHERE EXTRACT(YEAR FROM \"salary_date\") = '$intYear' AND EXTRACT(MONTH FROM \"salary_date\") = '$intMonth' AND status=" . REQUEST_STATUS_APPROVED;
    $res = $db->execute($strSQL);
    $intStart = (($intPage - 1) * $intPageLimit);
    while ($row = $db->fetchrow($res)) {
        $salaryMasterID = $row['id'];
        $strSQL2 = "SELECT $intMonth as masa_pajak, $intYear as tahun_pajak, 0 as pembetulan, t1.npwp, '21-100-01' as kode_pajak, base_tax, base_irregular_tax, tax, irregular_tax, t1.\"employee_name\", t0.id_employee
                    FROM \"hrd_salary_detail\" AS t0
                        LEFT JOIN \"hrd_employee\" AS t1 ON t0.\"id_employee\" = t1.id
                        LEFT JOIN \"hrd_position\" AS t2 ON t2.\"position_code\" = t0.\"position_code\"
                    WHERE t0.total_net > 0 AND t0.total_gross > 0 AND \"id_salary_master\" ='$salaryMasterID' $strKriteria ORDER BY t1.\"employee_name\"";
        // die($strSQL2);
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
            $arrResult[$row2['id_employee']]['masa_pajak'] = $row2['masa_pajak'];
            $arrResult[$row2['id_employee']]['tahun_pajak'] = $row2['tahun_pajak'];
            $arrResult[$row2['id_employee']]['pembetulan'] = $row2['pembetulan'];
            $arrResult[$row2['id_employee']]['npwp'] = ($row2['npwp'] == "" || $row2['npwp'] == 0) ? '000000000000000' : $row2['npwp'];
            $arrResult[$row2['id_employee']]['employee_name'] = $row2['employee_name'];
            $arrResult[$row2['id_employee']]['kode_pajak'] = $row2['kode_pajak'];
            $arrResult[$row2['id_employee']]['total_base_tax'] = $row2['base_tax'] + $row2['base_irregular_tax'];
            $arrResult[$row2['id_employee']]['total_tax'] = $row2['tax'] + $row2['irregular_tax'];
            $arrResult[$row2['id_employee']]['kode_negara'] = '';
        }
    }
    return $arrResult;
}

function getESPTTahunan($db, $intYear, $strKriteria = "")
{
    global $_POST;
    $arrResult = [];
    if ($intYear == "") {
        return $arrResult;
    }
    global $intStart;
    global $intPageLimit;
    global $intPageNumber;
    global $totalData;
    $intPage = $intPageNumber;
    $strSQL = "SELECT id FROM \"hrd_salary_master\" WHERE EXTRACT(YEAR FROM \"salary_date\") = '$intYear' AND status=" . REQUEST_STATUS_APPROVED;
    $res = $db->execute($strSQL);
    $intStart = (($intPage - 1) * $intPageLimit);
    while ($row = $db->fetchrow($res)) {
        $salaryMasterID = $row['id'];
        $strSQL2 = "SELECT t1.join_date, t1.resign_date, t1.npwp, t1.id_card, t1.employee_name as nama, t1.primary_address, t1.gender, t3.marital_status, t3.children, t0.*
                    FROM \"hrd_salary_detail\" AS t0
                        LEFT JOIN \"hrd_employee\" AS t1 ON t0.\"id_employee\" = t1.id
                        LEFT JOIN \"hrd_position\" AS t2 ON t2.\"position_code\" = t0.\"position_code\"
                        LEFT JOIN \"hrd_family_status\" AS t3 ON t3.\"family_status_code\" = t1.\"family_status_code\"
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
            $strJoinDate = $row2['join_date'];
            $strResignDate = $row2['resign_date'];
            $intJoinDateDay = date('j', strtotime($strJoinDate));
            $intJoinDateMonth = date('n', strtotime($strJoinDate));
            $intJoinDateYear = date('Y', strtotime($strJoinDate));
            $intResignDateDay = ($strResignDate != '') ? date('j', strtotime($strResignDate)) : 0;
            $intResignDateMonth = ($strResignDate != '') ? date('n', strtotime($strResignDate)) : 0;
            $intResignDateYear = ($strResignDate != '') ? date('Y', strtotime($strResignDate)) : 0;
            if ($intJoinDateYear < $intYear && $strResignDate == '') {
                $row2['masa_awal'] = 1;
                $row2['masa_akhir'] = 12;
            } elseif ($intJoinDateYear < $intYear && $strResignDate != '') {
                $row2['masa_awal'] = 1;
                $row2['masa_akhir'] = $intResignDateMonth;
            } elseif ($intJoinDateYear == $intYear && $strResignDate == '') {
                $row2['masa_awal'] = $intJoinDateMonth;
                $row2['masa_akhir'] = 12;
                //if ($intJoinDateDay >= JOIN_DATE_LIMIT) {
                //    $row2['masa_awal'] -= 1;
                //}
            } elseif ($intJoinDateYear == $intYear && $strResignDate != '') {
                $row2['masa_awal'] = $intJoinDateMonth;
                $row2['masa_akhir'] = $intResignDateMonth;
            }
            $arrResult[$row2['id_employee']]['id'] = $row2['id_employee'];
            $arrResult[$row2['id_employee']]['masa_pajak'] = 12; // untuk sementara hard code
            $arrResult[$row2['id_employee']]['tahun_pajak'] = $intYear;
            $arrResult[$row2['id_employee']]['pembetulan'] = 0; // untuk sementara hard code, system tidak bisa cover
            $arrResult[$row2['id_employee']]['no_bukti_potong'] = '';
            $arrResult[$row2['id_employee']]['masa_awal'] = $row2['masa_awal'];
            $arrResult[$row2['id_employee']]['masa_akhir'] = $row2['masa_akhir'];
            $arrResult[$row2['id_employee']]['npwp'] = ($row2['npwp'] == "" || $row2['npwp'] == 0) ? '000000000000000' : $row2['npwp'];
            $arrResult[$row2['id_employee']]['nik'] = $row2['id_card']; // no ktp
            $arrResult[$row2['id_employee']]['nama'] = $row2['nama'];
            $arrResult[$row2['id_employee']]['alamat'] = $row2['primary_address'];
            $arrResult[$row2['id_employee']]['jenis_kelamin'] = ($row2['gender'] == 0) ? 'F' : 'M';
            $arrResult[$row2['id_employee']]['status_ptkp'] = ($row2['marital_status'] == 0) ? 'TK' : (($row2['marital_status'] == 2) ? 'HB' : 'K');
            $arrResult[$row2['id_employee']]['jumlah_tanggungan'] = $row2['children'];
            $arrResult[$row2['id_employee']]['nama_jabatan'] = 'Pegawai'; // untuk sementara hard code
            $arrResult[$row2['id_employee']]['wp_luar_negeri'] = 'N'; // untuk sementara hard code, system tidak cover
            $arrResult[$row2['id_employee']]['kode_negara'] = ''; // biarkan kosong
            $arrResult[$row2['id_employee']]['kode_pajak'] = '21-100-01'; // untuk sementara di hard code
            $fltBasicSalary = (isset($row2['basic_salary']) && $row2['basic_salary'] > 0) ? $row2['basic_salary'] : getBasicSalary($row2['id_salary_master'], $row2['id_employee']);
            $arrResult[$row2['id_employee']]['jumlah_1'] +=  $fltBasicSalary;// basic salary setahun
            $arrResult[$row2['id_employee']]['jumlah_2'] += $row2['tax_allowance'] + $row2['irregular_tax_allowance']; // tax allowance
            $arrResult[$row2['id_employee']]['jumlah_3'] += $row2['base_tax'] - $fltBasicSalary - $row2['tax_allowance'] + $row2['irregular_tax_allowance']; // tunjangan lain
            $arrResult[$row2['id_employee']]['jumlah_4'] += 0; // pendapatan tambahan setahun
            $arrResult[$row2['id_employee']]['jumlah_5'] += $row2['jkk_allowance'] + $row2['jkm_allowance'] + $row2['bpjs_allowance']; // premi yang dibayar company
            $arrResult[$row2['id_employee']]['jumlah_6'] += 0; // pendapatan tambahan (natura)
            $arrResult[$row2['id_employee']]['jumlah_7'] += $row2['base_irregular_tax']; // pendapatan tambahan (irregular)
            $arrResult[$row2['id_employee']]['jumlah_8'] = $arrResult[$row2['id_employee']]['jumlah_1'] + $arrResult[$row2['id_employee']]['jumlah_2'] +
                $arrResult[$row2['id_employee']]['jumlah_3'] + $arrResult[$row2['id_employee']]['jumlah_4'] /*+
                $arrResult[$row2['id_employee']]['jumlah_5']*/ + $arrResult[$row2['id_employee']]['jumlah_6'] +
                $arrResult[$row2['id_employee']]['jumlah_7']; // total 1-7
            $arrResult[$row2['id_employee']]['jumlah_9'] = (0.05 * $arrResult[$row2['id_employee']]['jumlah_8'] < 500000 * ($arrResult[$row2['id_employee']]['masa_akhir'] - $arrResult[$row2['id_employee']]['masa_awal'] + 1))
                ? 0.05 * $arrResult[$row2['id_employee']]['jumlah_8'] : 500000 * ($arrResult[$row2['id_employee']]['masa_akhir'] - $arrResult[$row2['id_employee']]['masa_awal'] + 1); // biaya jabatan
            $arrResult[$row2['id_employee']]['jumlah_10'] = $row2['jamsostek_deduction']; // jht/pensiun yang dibayar employee setahun
            $arrResult[$row2['id_employee']]['jumlah_11'] = $arrResult[$row2['id_employee']]['jumlah_9'] + $arrResult[$row2['id_employee']]['jumlah_10']; // total 9-10
            $arrResult[$row2['id_employee']]['jumlah_12'] = $arrResult[$row2['id_employee']]['jumlah_8'] - $arrResult[$row2['id_employee']]['jumlah_11']; // jumlah_8 - jumlah_11
            $arrResult[$row2['id_employee']]['jumlah_13'] = 0; // penyambungan pendapatan (untuk sekarang diisi nol dulu, system belum cover)
            $arrResult[$row2['id_employee']]['jumlah_14'] = $arrResult[$row2['id_employee']]['jumlah_12'] + $arrResult[$row2['id_employee']]['jumlah_13']; // jumlah_12 + jumlah_13
            $arrResult[$row2['id_employee']]['jumlah_15'] = $row2['tax_reduction']; // PTKP
            $arrResult[$row2['id_employee']]['jumlah_16'] += ($arrResult[$row2['id_employee']]['jumlah_14'] - $arrResult[$row2['id_employee']]['jumlah_15'] >= 0) ? $arrResult[$row2['id_employee']]['jumlah_14'] - $arrResult[$row2['id_employee']]['jumlah_15'] : 0; // jumlah_14 - jumlah_15
            $arrResult[$row2['id_employee']]['jumlah_17'] += getAnnualTaxESPT($arrResult[$row2['id_employee']]['jumlah_16']); // total pph until december
            $arrResult[$row2['id_employee']]['jumlah_18'] += $row2['tax'] + $row2['irregular_tax']; // pph paid
            $arrResult[$row2['id_employee']]['jumlah_19'] = $arrResult[$row2['id_employee']]['jumlah_17'] - $arrResult[$row2['id_employee']]['jumlah_18']; // jumlah_17 - jumlah_18
            $arrResult[$row2['id_employee']]['jumlah_20'] = $arrResult[$row2['id_employee']]['jumlah_19']; // pph yang harus dibayarkan
            $arrResult[$row2['id_employee']]['status_pindah'] = ''; // dikosongkan saja, system masih belum cover
            $arrResult[$row2['id_employee']]['npwp_pemotong'] = '01.304.772.5-073.000'; // sementara di hard code
            $arrResult[$row2['id_employee']]['nama_pemotong'] = 'PT. ASURANSI JIWA ADISARANA WANAARTHA'; // sementara di hard code
            $arrResult[$row2['id_employee']]['tanggal_bukti_potong'] = date($_SESSION['sessionDateSetting']['php_format']); // tanggal hari ini
        }
    }
    $i = 0;
    foreach ($arrResult as $arrEmployee) {
        $i++;
        $strIDEmployee = $arrEmployee['id'];
        $arrResult[$strIDEmployee]['no_bukti_potong'] = "1." . $arrResult[$strIDEmployee]['masa_awal'] . "-" . $arrResult[$strIDEmployee]['masa_akhir'] . "." . date(
                "y",
                strtotime($intYear)
            ) . "-" . str_pad($i, 6, "0", STR_PAD_LEFT);
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
    $arrResult[] = ["value" => 0, "text" => "E-SPT Masa"];
    $arrResult[] = ["value" => 1, "text" => "E-SPT Tahunan"];
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

function getBasicSalary($strIDSalary, $strIDEmployee)
{
    global $db;
    $strResult = 0;
    $strSQL = "SELECT amount FROM hrd_salary_allowance
                WHERE \"id_salary_master\" = $strIDSalary AND id_employee = $strIDEmployee AND allowance_code = 'basic_salary';";
    $res = $db->execute($strSQL);
    while ($row = $db->fetchrow($res)) {
        $strResult += $row['amount'];
    }
    return $strResult;
}

function getAnnualTaxESPT($fltPKP)
{
    $tax = 0;
    $tarif1 = 0;
    $tarif2 = 0;
    $tarif3 = 0;
    $tarif4 = 0;
    if ($fltPKP > 0) {
        $batas1 = 50000000;
        $tarif1 = ($fltPKP > $batas1) ? $batas1 * 0.05 : $fltPKP * 0.05;
        $sisa1 = $fltPKP - $batas1;
    }
    if ($sisa1 > 0) {
        $batas2 = 200000000;
        $tarif2 = ($sisa1 > $batas2) ? $batas2 * 0.15 : $sisa1 * 0.15;
        $sisa2 = $sisa1 - $batas2;
    }
    if ($sisa2 > 0) {
        $batas3 = 250000000;
        $tarif3 = ($sisa2 > $batas3) ? $batas3 * 0.25 : $sisa2 * 0.25;
        $sisa3 = $sisa2 - $batas3;
    }
    if ($sisa3 > 0) {
        $tarif4 = $sisa3 * 0.30;
    }
    $tax = $tarif1 + $tarif2 + $tarif3 + $tarif4;
    return $tax;
}

?>
