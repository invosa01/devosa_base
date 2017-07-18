<?php
ini_set("display_errors", 1);
date_default_timezone_set('Asia/Jakarta');
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/model/model.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('form_object.php');
include_once('cls_salary_calculation.php');
include_once("cls_employee.php");
include_once('../includes/krumo/class.krumo.php');
include_once('../global/tcpdf_include.php');
//krumo($_SESSION);die();
$dataPrivilege = getDataPrivileges("salary_report_slip.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
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
getUserEmployeeInfo();
scopeData(
    $strDataEmployee,
    $strDataSubSection,
    $strDataSection,
    $strDataDepartment,
    $strDataDivision,
    $_SESSION['sessionUserRole'],
    $arrUserInfo
);
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
$f->addCheckBox(getWords("irregular(THR/Bonus)"), "dataIrregular", false, [], "string", false, true, true, "", "");
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
//$autoCompleteValue = getInitialValue("Employee", null, $_SESSION['sessionEmployeeID']);
$autoCompleteValue = $_SESSION['sessionEmployeeID']; //getInitialValue("Employee", null, $_SESSION['sessionEmployeeID']);
$employeeName = '';
if (!empty($autoCompleteValue)) {
    $employeeData = getEmployeNameByID($db, $autoCompleteValue);
    $employeeName = $employeeData['employee_name'];
    $employeeID = $employeeData['employee_id'];
}
$f->addInput(
    getWords("employee id"),
    "employeeName",
    $employeeID,
    "style=width:$strDefaultWidthPx readonly",
    "string",
    false,
    true,
    true,
    "",
    ""
);
$f->addLabelAutoComplete("", "employeeName", $employeeName);
//  $f->addInputAutoComplete(getWords("employee id"), "employeeName", getDataEmployee(getInitialValue("Employee", null, $strDataEmployee)), "style=width:$strDefaultWidthPx ".$strEmpReadonly, "string", true, true, true, "", "", true, null, "../global/hrd_ajax_source.php?action=getemployee");
//  $f->addLabelAutoComplete("", "employeeName", "");
//  $f->addLabelAutoComplete("", "dataEmployee", "");
//  //this save button will hide after save <toggle>
$f->addSubmit("btnShow", "Show Slip", ["onClick" => "return validInput();"], true, true, "", "", "");
// $f->addSubmit("btnExportXLS", "Export Excel", array("onClick" => "return validInput();"), true, true, "", "", "");
$formInput = $f->render();
$showReport = (isset($_POST['btnShow']) || isset($_POST['btnExportXLS']) || isset($_POST['isShow']));
$totalData = 0;
$dataGrid = "";
$strInitAction = "";
$strStatus = $f->getValue('employeeStatus');
$strName = $f->getValue('employeeName');
$bolIrregular = ($f->getValue('dataIrregular')) ? 't' : 'f';
$strCompany = $f->getValue('dataCompany');
if ($showReport) {
    $intYear = intval($f->getValue('dataYear'));
    $intMonth = intval($f->getValue('dataMonth'));
    $strKriteria = "";
    if ($strStatus != "") {
        $strKriteria .= " AND t1.\"employee_status\" = $strStatus";
    }
    // if($strName != "")
    // {
    $strKriteria .= " AND t1.\"employee_id\" = '" . $_SESSION['sessionEmployeeID'] . "' ";
    // }
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
        $arrSlip = getSlipReport($db, $intYear, $intMonth, $strCompany, $strKriteria);
        $myDataGrid->setCaption("Slip - $intYear - $intMonth");
        $myDataGrid->pageSortBy = "";
        $myDataGrid->addColumnCheckbox(
            new DataGrid_Column("chkID", "id", ['width' => 30, 'align' => 'center'], ['nowrap' => ''])
        );
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
            new DataGrid_Column("Name", "employee_name", [], ['nowrap' => ''], true, true, "", "", "string", true, 30)
        );
        $myDataGrid->addColumn(
            new DataGrid_Column("Position", "position", [], ['nowrap' => ''], true, true, "", "", "string", true, 30)
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "Functional", "functional", [], ['nowrap' => ''], true, true, "", "", "string", true, 30
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
                "Total Salary",
                "total_gross",
                ['width' => 120],
                ['align' => 'center'],
                false,
                false,
                "",
                "formatNumerica()",
                "numeric",
                true,
                12
            )
        );
        //$myDataGrid->addSpecialButton("btnSlip", "btnSlip", "submit", getWords("get slip"), "onClick=\"document.target = 'blank'\"", "getSlip()");
        #untuk submit export pdf di form dialog, karena button btnpdf hanya untuk menampilkan modal dialog saja
        $myDataGrid->addSpecialButton(
            "hidepdf",
            "hidepdf",
            "submit",
            getWords(""),
            "formtarget = \"_blank\"",
            "getPDF()"
        );
        $myDataGrid->addSpecialButton(
            "btnSlip",
            "btnSlip",
            "submit",
            getWords("get slip"),
            "formtarget = \"_blank\"",
            "getSlip()"
        );
        $myDataGrid->addSpecialButton(
            "btnpdf",
            "btnpdf",
            "button",
            getWords("export pdf"),
            "formtarget = \"_blank\"",
            "getPDF()"
        );
        $myDataGrid->getRequest();
        $strCriteria = "";
        $myDataGrid->totalData = $totalData;
        $myDataGrid->bind($arrSlip);
        $dataGrid = $myDataGrid->render();
    }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('salary report slip page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
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
function getSlipReport($db, $intYear, $intMonth, $strCompany, $strKriteria = "")
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
    global $strDataID;
    $intPage = $intPageNumber;
    $strSQL = "SELECT id FROM \"hrd_salary_master\" WHERE EXTRACT(YEAR FROM \"salary_date\") = '$intYear' AND EXTRACT(MONTH FROM \"salary_date\") = '$intMonth'
AND status=" . REQUEST_STATUS_APPROVED . " AND id_company = $strCompany ";
    $res = $db->execute($strSQL);
    $intStart = (($intPage - 1) * $intPageLimit);
    while ($row = $db->fetchrow($res)) {
        $salaryMasterID = $row['id'];
        $strSQL2 = "SELECT t0.*, t0.\"join_date\", t0.\"resign_date\", t1.\"employee_name\", t2.\"position_name\", t3.functional_name, t1.employee_status, t1.get_bpjs, t1.branch_code
                    FROM \"hrd_salary_detail\" AS t0
                        LEFT JOIN \"hrd_employee\" AS t1 ON t0.\"id_employee\" = t1.id
                        LEFT JOIN \"hrd_position\" AS t2 ON t2.\"position_code\" = t0.\"position_code\"
                        LEFT JOIN \"hrd_functional\" AS t3 ON t3.\"functional_code\" = t1.\"functional_code\"
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
            $strDataID = $row2['id_salary_master'];
            $arrResult[$row2['id_employee']]['id'] = $row2['id_employee'];
            $arrResult[$row2['id_employee']]['nik'] = $row2['employee_id'];
            $arrResult[$row2['id_employee']]['position'] = $row2['position_name'];
            $arrResult[$row2['id_employee']]['functional'] = $row2['functional_name'];
            $arrResult[$row2['id_employee']]['branch_code'] = $row2['branch_code'];
            $arrResult[$row2['id_employee']]['join_date'] = $row2['join_date'];
            $arrResult[$row2['id_employee']]['employee_name'] = $row2['employee_name'];
            $arrResult[$row2['id_employee']]['employee_status'] = $row2['employee_status'];
            $arrResult[$row2['id_employee']]['total_gross'] = $row2['total_gross'];
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
    global $bolIrregular;
    $bolExist = 0;
    $strSQL = "SELECT id FROM \"hrd_salary_master\" WHERE EXTRACT (YEAR FROM \"salary_date\") = $intYear AND EXTRACT (MONTH FROM \"salary_date\") = $intMonth
      AND status = " . REQUEST_STATUS_APPROVED . " AND id_company = $intCompany";
    if ($bolIrregular == 't') {
        $strSQL .= " AND irregular is true";
    } else {
        $strSQL .= " AND irregular is false";
    }
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

// fungsi untuk export slip ke pdf
function getPDF()
{
    $pwdpdf = $_POST['txtpdf'];
    getSlip("exportpdf", $pwdpdf);
}

// fungsi untuk melakukan proses slip gaji
function getSlip($flag = "", $pwd = "")
{
    global $bolIrregular;
    global $bolHideBlank;
    global $myDataGrid;
    global $db;
    global $objSalary;
    global $strDataID;
    global $ARRAY_EMPLOYEE_STATUS;
    global $arrUserInfo;
    // $bolIrregular = false;
    // $bolHideBlank = false;
    include_once('cls_annual_leave.php');
    $objLeave = new clsAnnualLeave($db);
    $objSalary = new clsSalaryCalculation($db, $strDataID);
    if ($objSalary->strDataID != "") {
        $strDateFrom = $objSalary->arrData['date_from'];
        $strDateThru = $objSalary->arrData['date_thru'];
        $strCompany = $objSalary->arrData['id_company'];
        $strDataDateFrom = pgDateFormat($objSalary->arrData['date_from'], "d M Y");
        $strDataDateThru = pgDateFormat($objSalary->arrData['date_thru'], "d M Y");
        $strDataDate = pgDateFormat($objSalary->arrData['salary_date'], "d M Y");
        $intStatus = $objSalary->arrData['status'];
        $bolIrregular = ($objSalary->irregular == "t");
        $bolHideBlank = ($objSalary->arrData['hide_blank'] == "t");
    }
    foreach ($objSalary->arrMA AS $strCode => $arrInfo) {        //echo $strCode."|".$arrInfo['show']."<br>";
        if ($arrInfo['is_default'] == "t") {
            $strVar = "bolShow_" . $strCode;
            $strVar = (getSetting($strCode . "_show") == "t");
        }
    }
    // die();
    $objDate = new clsCommonDate();
    $objEmp = new clsEmployees($db);
    $objEmp->loadData("id, employee_id, employee_name, id_company, join_date, grade_code, branch_code");
    // tampilkan header HTML dulu
    echo "
<html>
<head>
<title>Slip</title>
<meta http-equiv='Content-Type' content='application/vnd.ms-word; charset=iso-8859-1'>
<meta http-equiv='Content-Disposition' content='attachment; charset=iso-8859-1'>
<link href='../css/invosa.css' rel='stylesheet' type='text/css'>
</head>";
    if ($flag == "exportpdf") {
        echo "<body marginheight=0 marginwidth=0 leftmargin=10 rightmargin=0 topmargin=0>
		<table cellspacing=0 cellpadding=0 border=0 width='100%'>
		";
    } else {
        echo "<body onLoad=\"window.print();\" marginheight=0 marginwidth=0 leftmargin=10 rightmargin=0 topmargin=0>
		<table cellspacing=0 cellpadding=0 border=0 width='100%'>
		";
    }
    // inisialisasi
    $strThisPage = "
                      <span>&nbsp;";
    $strNewPage = "
                    <span style=\"page-break-before:always;\"></span>";
    $GLOBALS['strPeriod'] = $objDate->getDateFormat($objSalary->arrData['salary_date'], "F Y");
    $GLOBALS['strUserName'] = $_SESSION['sessionUserName'];
    $bolEven = true; // apakah genap
    $i = 0;
    foreach ($myDataGrid->checkboxes as $strValue) {
        $bolEven = !$bolEven;
        $i++;
        // inisialisasi detail
        $GLOBALS['strCompany'] = "";
        $GLOBALS['strCompanyAddress'] = "";
        $GLOBALS['strCompanyCity'] = "";
        $GLOBALS['strCompanyPhone'] = "";
        $GLOBALS['strCompanyFax'] = "";
        $GLOBALS['strCompanyEmail'] = "";
        $GLOBALS['strCompanyLogo'] = "";
        $GLOBALS['strEmployeeName'] = "";
        $GLOBALS['strEmployeeID'] = "";
        $GLOBALS['strDivision'] = "";
        $GLOBALS['strJoinDate'] = "";
        $GLOBALS['strWorkingDay'] = 0;
        $GLOBALS['strOvertimeHour'] = 0;
        $GLOBALS['strOvertimeBasic'] = 0;
        $GLOBALS['strIncome'] = "";
        $GLOBALS['strDeduction'] = "";
        $GLOBALS['strIncomeBlankSpace'] = "";
        $GLOBALS['strDeductionBlankSpace'] = "";
        $GLOBALS['strTotalIncome'] = "";
        $GLOBALS['strTotalDeduction'] = "";
        $GLOBALS['strTotalSalary'] = "";
        $GLOBALS['strEmployeeFunctional'] = "";
        $GLOBALS['intNoAbsence'] = 1;
        // ambil ID employee
        $strDataUserRole = $_SESSION['sessionUserRole'];
        //untuk supervisor dan employee hanya bisa lihat slip sendiri
        //var_dump($arrUserInfo);
        if ($strDataUserRole == ROLE_EMPLOYEE or $strDataUserRole == ROLE_SUPERVISOR) {
            $strIDEmployee = $arrUserInfo['id_employee'];
            //$strIDEmployee = $strValue;
        } else {
            $strIDEmployee = $strValue;
        }
        $objLeave->generateEmployeeAnnualLeave($strIDEmployee);
        $arrCuti = $objLeave->getEmployeeLeaveInfo($strIDEmployee);
        $intCompany = $objEmp->getInfoByID($strIDEmployee, "id_company");
        $arrCompany = [];
        $arrCompany = arrCompanyInfo($db, $intCompany);
        $GLOBALS['strCompany'] = $arrCompany['company_name'];
        $GLOBALS['strCompanyAdress'] = $arrCompany['address'];
        $GLOBALS['strCompanyCity'] = $arrCompany['city'];
        if ($GLOBALS['strCompanyCity'] === null) {
            $GLOBALS['strCompanyCity'] = $arrCompany['address'];
        }
        $GLOBALS['strCompanyPhone'] = $arrCompany['phone'];
        $GLOBALS['strCompanyFax'] = $arrCompany['fax'];
        $GLOBALS['strCompanyEmail'] = $arrCompany['email'];
        if ($GLOBALS['strCompanyLogo'] != null) {
            $GLOBALS['strCompanyLogo'] = $arrCompany['logo'];
        } else {
            $GLOBALS['strCompanyLogo'] = "logo_back_slip.png";
        }
        $strDiv = $objSalary->getEmployeeSalaryDetail($strIDEmployee, "division_code");
        $strBrch = $objEmp->getInfoByID($strIDEmployee, "branch_code");
        $GLOBALS['strBranch'] = getBranchName($strBrch);
        $GLOBALS['strDivision'] = getDivisionName($strDiv);
        $GLOBALS['strEmployeeID'] = $objEmp->getInfoByID($strIDEmployee, "employee_id");
        $GLOBALS['strEmployeeName'] = $objEmp->getInfoByID($strIDEmployee, "employee_name");
        //$GLOBALS['strEmployeeGrade']  = $objEmp->getInfoByID($strIDEmployee, "grade_code");
        $GLOBALS['strJoinDate'] = $objDate->getDateFormat($objEmp->getInfoByID($strIDEmployee, "join_date"), "d-M-y");
        $GLOBALS['strWorkingDay'] = $objSalary->getEmployeeSalaryDetail($strIDEmployee, "attendance_day");
        $GLOBALS['strSisaCuti'] = $arrCuti['curr']['remain'];
        $ot1 = $objSalary->getEmployeeSalaryDetail($strIDEmployee, "ot1_min") * 1.5;
        $ot2 = $objSalary->getEmployeeSalaryDetail($strIDEmployee, "ot2_min") * 2;
        $ot2b = $objSalary->getEmployeeSalaryDetail($strIDEmployee, "ot2b_min") * 2;
        //$ot2b = $objSalary->getEmployeeSalaryDetail($strIDEmployee, "ot2b_min") * 2.5;
        $ot3 = $objSalary->getEmployeeSalaryDetail($strIDEmployee, "ot3_min") * 3;
        $ot4 = $objSalary->getEmployeeSalaryDetail($strIDEmployee, "ot4_min") * 4;
        $GLOBALS['intNoAbsence'] = $objSalary->arrDetail[$strIDEmployee]['paid_absence_day'] + $objSalary->arrDetail[$strIDEmployee]['unpaid_absence_day'];;
        if (($ot1 + $ot2 + $ot2b + $ot3 + $ot4) <> 0) {
            $GLOBALS['strOvertimeHour'] = ($ot1 + $ot2 + $ot2b + $ot3 + $ot4) / 60;
        }
        // tampilkan income
        $fltTotalIncome = $fltTotalDeduction = $fltSalary = 0;
        if (!$bolIrregular) {
            //$GLOBALS['strIncome'] .= wrapRow("Basic Salary", "Rp", standardFormat($objSalary->getEmployeeSalaryDetail($strIDEmployee, "basic_salary")), true /*isNumeric*/); //form_function.php
            //$fltTotalIncome += $objSalary->getEmployeeSalaryDetail($strIDEmployee, "basic_salary");
            //$GLOBALS['strIncome'] .= wrapRow("Overtime", "Rp", $objSalary->getEmployeeSalaryDetail($strIDEmployee, "overtime_allowance"));
        }
        foreach ($objSalary->arrMA AS $strCode => $arrInfo) {
            $fltAmount = $objSalary->getEmployeeSalaryDetail($strIDEmployee, $strCode);
            if ($arrInfo['is_default'] == 't' && $arrInfo['show'] == 't') {
                if ((!$bolIrregular && $arrInfo['irregular'] == 'f') || $arrInfo['irregular'] == 't') {
                    //Jika hide if zero, dan nilainya zero tambahkan 1 baris blank space (berhubung printernya continues paper)
                    if ($arrInfo['hidezero'] == 'f' || $fltAmount != 0) {
                        $GLOBALS['strIncome'] .= wrapRow(
                            $arrInfo['name'],
                            "Rp",
                            standardFormat($fltAmount),
                            true /*isNumeric*/
                        );
                        $fltTotalIncome += $fltAmount;
                        if ($arrInfo['benefit'] == 't') {
                            $GLOBALS['strDeduction'] .= wrapRow(
                                $arrInfo['name'],
                                "Rp",
                                standardFormat($fltAmount),
                                true/*isNumeric*/
                            );
                            $fltTotalDeduction += $fltAmount;
                        }
                    } else {
                        $GLOBALS['strIncomeBlankSpace'] .= wrapRow("", "", "", false /*isNumeric*/);
                        if ($arrInfo['benefit'] == 't') {
                            $GLOBALS['strDeductionBlankSpace'] .= wrapRow("", "", "", false /*isNumeric*/);
                        }
                    }
                } else {
                    $GLOBALS['strIncomeBlankSpace'] .= wrapRow("", "", "", false /*isNumeric*/);
                    if ($arrInfo['benefit'] == 't') {
                        $GLOBALS['strDeductionBlankSpace'] .= wrapRow("", "", "", false /*isNumeric*/);
                    }
                }
            }
        }
        foreach ($objSalary->arrMA AS $strCode => $arrInfo) {
            $fltAmount = $objSalary->getEmployeeAllowanceDetail($strIDEmployee, $strCode);
            if ($arrInfo['is_default'] == 'f' && $arrInfo['show'] == 't') {
                if ((!$bolIrregular && $arrInfo['irregular'] == 'f') || $arrInfo['irregular'] == 't') {
                    if ($arrInfo['hidezero'] == 'f' || $fltAmount != 0) {
                        $GLOBALS['strIncome'] .= wrapRow(
                            $arrInfo['name'],
                            "Rp",
                            standardFormat($fltAmount),
                            true/*isNumeric*/
                        );
                        $fltTotalIncome += $fltAmount;
                        if ($arrInfo['benefit'] == 't') {
                            $GLOBALS['strDeduction'] .= wrapRow(
                                $arrInfo['name'],
                                "Rp",
                                standardFormat($fltAmount),
                                true/*isNumeric*/
                            );
                            $fltTotalDeduction += $fltAmount;
                        }
                    } else {
                        $GLOBALS['strIncomeBlankSpace'] .= wrapRow("", "", "", false/*isNumeric*/);
                        if ($arrInfo['benefit'] == 't') {
                            $GLOBALS['strDeductionBlankSpace'] .= wrapRow("", "", "", false/*isNumeric*/);
                        }
                    }
                } else {
                    $GLOBALS['strIncomeBlankSpace'] .= wrapRow("", "", "", false/*isNumeric*/);
                    if ($arrInfo['benefit'] == 't') {
                        $GLOBALS['strDeductionBlankSpace'] .= wrapRow("", "", "", false/*isNumeric*/);
                    }
                }
            }
        }
        $GLOBALS['strIncome'] .= wrapRow(
            "Tax Allowance",
            "Rp",
            standardFormat(
                $objSalary->getEmployeeSalaryDetail(
                    $strIDEmployee,
                    "tax"
                ) + $objSalary->getEmployeeSalaryDetail($strIDEmployee, "irregular_tax")
            ),
            true /*isNumeric*/
        ); //form_function.php
        $fltTotalIncome += $objSalary->getEmployeeSalaryDetail($strIDEmployee, "tax");
        $fltTotalIncome += $objSalary->getEmployeeSalaryDetail($strIDEmployee, "irregular_tax");
        // tampilkan potongan
        if (!$bolIrregular) {
            foreach ($objSalary->arrMD AS $strCode => $arrInfo) {
                $fltAmount = $objSalary->getEmployeeSalaryDetail($strIDEmployee, $strCode);
                if ($arrInfo['is_default'] == 't' && $arrInfo['show'] == 't') {
                    if ($arrInfo['hidezero'] == 'f' || $fltAmount != 0) {
                        $GLOBALS['strDeduction'] .= wrapRow(
                            $arrInfo['name'],
                            "Rp",
                            standardFormat($fltAmount),
                            true/*isNumeric*/
                        );
                        $fltTotalDeduction += $fltAmount;
                    } else {
                        $GLOBALS['strDeductionBlankSpace'] .= wrapRow("", "", "", false/*isNumeric*/);
                    }
                }
            }
            foreach ($objSalary->arrMD AS $strCode => $arrInfo) {
                $fltAmount = $objSalary->getEmployeeDeductionDetail($strIDEmployee, $strCode);
                if ($arrInfo['is_default'] == 'f' && $arrInfo['show'] == 't') {
                    if ($arrInfo['hidezero'] == 'f' || $fltAmount != 0) {
                        $GLOBALS['strDeduction'] .= wrapRow(
                            $arrInfo['name'],
                            "Rp",
                            standardFormat($fltAmount),
                            true/*isNumeric*/
                        );
                        $fltTotalDeduction += $fltAmount;
                    } else {
                        $GLOBALS['strDeductionBlankSpace'] .= wrapRow("", "", "", false/*isNumeric*/);
                    }
                }
            }
            //$GLOBALS['strDeduction'] .= wrapRow("Potongan L/E", "Rp", standardFormat($objSalary->getEmployeeSalaryDetail($strIDEmployee, "absence_deduction")), true /*isNumeric*/); //form_function.php
        }
        // else
        //tampilkan zakat utk irregular income
        //$GLOBALS['strDeduction'] .= wrapRow("Zakat", "Rp", standardFormat($objSalary->getEmployeeSalaryDetail($strIDEmployee, "zakat_deduction_irregular")), true /*isNumeric*/); //form_function.php
        //tampilkan tax (tax reguler + irreguler)
        $GLOBALS['strDeduction'] .= wrapRow(
            "Tax",
            "Rp",
            standardFormat(
                $objSalary->getEmployeeSalaryDetail(
                    $strIDEmployee,
                    "tax"
                ) + $objSalary->getEmployeeSalaryDetail($strIDEmployee, "irregular_tax")
            ),
            true /*isNumeric*/
        ); //form_function.php
        $fltTotalDeduction += $objSalary->getEmployeeSalaryDetail($strIDEmployee, "tax");
        $fltTotalDeduction += $objSalary->getEmployeeSalaryDetail($strIDEmployee, "irregular_tax");
        $GLOBALS['strTotalIncome'] = standardFormat($fltTotalIncome);
        $GLOBALS['strTotalDeduction'] = standardFormat($fltTotalDeduction);
        $GLOBALS['strTotalSalary'] = standardFormat(round($fltTotalIncome, 2) - round($fltTotalDeduction, 2));
        if ($bolEven) // genap
        {
            echo "<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><table><tr><td height=60>&nbsp;</td></tr></table><span>";
        } else if ($i == 1) {
            echo $strThisPage;
        } else // ganjil, page berikutnya
        {
            echo $strNewPage;
        }
        if ($flag == "exportpdf") {
            // create new PDF document
            $pdf = new TCPDF('L', PDF_UNIT, 'A5', true, 'UTF-8', false);
            // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor("Devosa");
            $pdf->SetTitle("Devosa Payslip");
            $pdf->SetSubject("Employee Salary Slip");
            $pdf->SetKeywords("devosa, Employee");
            // remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            // set margins
            $pdf->SetMargins(10, 10, 5);
            // set auto page breaks
            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            // set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            // set some language-dependent strings (optional)
            if (@file_exists(dirname(__FILE__) . "/lang/eng.php")) {
                require_once(dirname(__FILE__) . "/lang/eng.php");
                //$pdf->setLanguageArray($l);
            }
            // set font
            $pdf->SetFont("helvetica", "", 8);
            // set password pdf
            $pdf->SetProtection(["copy"], $pwd, null, 0, null);
            // Add a page
            $pdf->AddPage();
            //tambahkan logo gambar
            $img_file = "../images/" . $GLOBALS["strCompanyLogo"];
            $pdf->Image($img_file, 10, 15, -200);
            $tbsPage = new clsTinyButStrong;
            $tbsPage->LoadTemplate("templates/slip_template3.html");
            $tbsPage->Render = TBS_NOTHING;
            $tbsPage->Show();
            $strContent = $tbsPage->Source;
            $pdf->writeHTML($strContent, true, false, true, false, '');
            //FI untuk langsung ditampilkan di browser, D jika langsung download ke file
            $pdf->Output("salary.pdf", "FI");
            ob_clean();
        } else {
            $tbsPage = new clsTinyButStrong;
            $tbsPage->LoadTemplate("templates/slip_template2.html");
            $tbsPage->Show(TBS_OUTPUT);
        }
    }
    // tampilkan footer HTML
    echo "
<table>
</body>
</html>
    ";
    unset($objEmp);
    exit();
}

?>
