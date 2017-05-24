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
        $myDataGrid = new cDataGrid("form1", "DataGrid1", "100%", "100%", true, false, false);
        $myDataGrid->disableFormTag();
        $intPageLimit = $myDataGrid->getPageLimit();
        $intPageNumber = $myDataGrid->getPageNumber();
        $arrOvertime = getOvertimeReport($db, $intYear, $intMonth, $strKriteria);
        $myDataGrid->setCaption("Report Overtime - $intYear - $intMonth");
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
                "NIK", "nik", ['width' => 120], ['nowrap' => ''], true, true, "", "", "string", true, 30
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Name",
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
                "Position",
                "position",
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
                "Functional",
                "functional",
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
                "Employee Status",
                "employee_status",
                ['width' => 120],
                ['nowrap' => ''],
                true,
                true,
                "",
                "printEmployeeStatus()",
                "string",
                true,
                30
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Join Date",
                "join_date",
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
                "Resign Date",
                "resign_date",
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
                "Total OT Day",
                "total_ot_day",
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
                getWords("ot1"),
                "ot1_min",
                ["width" => 30],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "",
                "numeric",
                true,
                15
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("ot2"),
                "ot2_min",
                ["width" => 30],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "",
                "numeric",
                true,
                15
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("ot3"),
                "ot3_min",
                ["width" => 30],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "",
                "numeric",
                true,
                15
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("ot4"),
                "ot4_min",
                ["width" => 30],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "",
                "numeric",
                true,
                15
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("total"),
                "total_ot_min",
                ["width" => 30],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "formatNumeric()",
                "numeric",
                true,
                15,
                false
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("converted"),
                "otx_min",
                ["width" => 30],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "",
                "numeric",
                true,
                15,
                false
            )
        );
        // $myDataGrid->addColumn(new DataGrid_Column("Total OT Hours", "total_ot_hour", array('width'=> 120), array('align' => 'center'), false, false, "","", "string", true, 12));
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
                12
            )
        );
        if (isset($_POST['btnExportXLS'])) {
            $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
            $myDataGrid->strFileNameXLS = "Overtime Report.xls";
            $myDataGrid->strTitle1 = getWords("Report Overtime - $intYear - $intMonth");
            $myDataGrid->hasGrandTotal = true;
        }
        $myDataGrid->getRequest();
        $strCriteria = "";
        $myDataGrid->totalData = $totalData;
        $myDataGrid->bind($arrOvertime);
        $dataGrid = $myDataGrid->render();
    }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('salary report overtime page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strWordsOvertimeReport = getWords("overtime report");
$pageSubMenu = salaryReportSubMenu($strWordsOvertimeReport);
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
function getOvertimeReport($db, $intYear, $intMonth, $strKriteria = "")
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
        $strSQL2 = "SELECT t0.*, t0.\"join_date\", t0.\"resign_date\", t1.\"employee_name\", t2.\"position_name\", t3.functional_name, t1.employee_status
                    FROM \"hrd_salary_detail\" AS t0
                        LEFT JOIN \"hrd_employee\" AS t1 ON t0.\"id_employee\" = t1.id
                        LEFT JOIN \"hrd_position\" AS t2 ON t2.\"position_code\" = t0.\"position_code\"
                        LEFT JOIN \"hrd_functional\" AS t3 ON t3.\"functional_code\" = t1.\"functional_code\"
                    WHERE total_net > 0 AND total_gross > 0 AND \"id_salary_master\" ='$salaryMasterID' $strKriteria AND total_ot_min > 0 ORDER BY t1.\"employee_name\"";
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
            $arrResult[$row2['id_employee']]['functional'] = $row2['functional_name'];
            $arrResult[$row2['id_employee']]['join_date'] = $row2['join_date'];
            $arrResult[$row2['id_employee']]['resign_date'] = $row2['resign_date'];
            $arrResult[$row2['id_employee']]['employee_name'] = $row2['employee_name'];
            $arrResult[$row2['id_employee']]['employee_status'] = $row2['employee_status'];
            $arrResult[$row2['id_employee']]['total_ot_hour'] = toHour($row2['total_ot_min']);
            $arrResult[$row2['id_employee']]['total_ot_day'] = $row2['ot_day'];
            $arrResult[$row2['id_employee']]['overtime_allowance'] = $row2['overtime_allowance'];
        }
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
