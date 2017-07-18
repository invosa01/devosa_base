<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('salary_func.php');
include_once('activity.php');
include_once("../global/cls_date.php");
include_once('../includes/model/model.php');
include_once('../includes/datagrid2/datagrid.php');
include_once("cls_salary_calculation.php");
include_once("cls_employee.php");
include_once('../includes/krumo/class.krumo.php');
include_once('../global/tcpdf_include.php');
$dataPrivilege = getDataPrivileges(
    "salary_calculation.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView || (!$bolCanEdit && $_SERVER['QUERY_STRING'] != "")) {
    die(accessDenied($_SERVER['HTTP_REFERER']));
}
$strDisplay = ($bolCanEdit) ? "table-row" : "none";
$bolPrint = (isset($_POST['btnPrint']));
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strColumnAllowance = "";
$strColumnDeduction = "";
$strHidden = "";
$strButtons = "";
$intTotalData = 0;
$strPaging = "";
$strDataID = "";
$strCalculationMenu = "";
$strReportType = "";
$strDataDateFrom = "";
$strDataDateThru = "";
$strKriteria = "";
$strSpan1 = 0;
$strSpan2 = 0;
$strSpan3 = 1;
$strSpan4 = 8;
$strWidth = "\"70px\"";
$arrData = [];
$bolNewData = true;
$strWordsSalarySumary = getWords("salary summary");
$strWordsSalaryCalculation = getWords("salary calculation");
$strWordsCurrency = getWords("currency");
$strWordsOutlet = getWords("outlet");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSubDepartment = getWords("sub department");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("subsection");
$strWordsEmployeeID = getWords("employee id");
$strWordsReport = getWords("report");
$strWordsBankTransferS = getWords("bank transfer - salary");
$strWordsBankTransferL = getWords("bank transfer - loan");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menyimpan data
//global $flag="print";
function saveData()
{
    global $objSalary;
    $objSalary->saveData();
}

// fungsi untuk meng-approve data
function approveData($db)
{
    global $objSalary;
    global $intStatus;
    if ($_SESSION['sessionUserRole'] != ROLE_ADMIN) {
        return false;
    }
    $objSalary->setApproved();
    $intStatus = $objSalary->arrData['status'];
}// approveData
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
    global $ARRAY_EMPLOYEE_STATUS;
    include_once('cls_annual_leave.php');
    $objLeave = new clsAnnualLeave($db);
    foreach ($objSalary->arrMA AS $strCode => $arrInfo) {        //echo $strCode."|".$arrInfo['show']."<br>";
        if ($arrInfo['is_default'] == "t") {
            $strVar = "bolShow_" . $strCode;
            $strVar = (getSetting($strCode . "_show") == "t");
        }
    }
    // die();
    $objDate = new clsCommonDate();
    $objEmp = new clsEmployees($db);
    $objEmp->loadData(
        "id, employee_id, employee_name, id_company, join_date, grade_code, branch_code, functional_code"
    );
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
    $GLOBALS['strPeriod'] = $objDate->getDateFormat($objSalary->arrData['salary_date'], "M Y");
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
        $GLOBALS['intNoAbsence'] = 1;
        // ambil ID employee
        $strIDEmployee = $objSalary->getIDEmployeeFromDetailID($strValue);
        $objLeave->generateEmployeeAnnualLeave($strIDEmployee);
        $arrCuti = $objLeave->getEmployeeLeaveInfo($strIDEmployee);
        $intCompany = $objEmp->getInfoByID($strIDEmployee, "id_company");
        $arrCompany = [];
        $arrCompany = arrCompanyInfo($db, $intCompany);
        //krumo($arrCompany);
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
        $GLOBALS['strDivision'] = getDivisionName($strDiv);
        $strBrch = $objEmp->getInfoByID($strIDEmployee, "branch_code");
        $GLOBALS['strBranch'] = getBranchName($strBrch);
        $GLOBALS['strEmployeeID'] = $objEmp->getInfoByID($strIDEmployee, "employee_id");
        $GLOBALS['strEmployeeName'] = $objEmp->getInfoByID($strIDEmployee, "employee_name");
        $GLOBALS['strEmployeeFunctional'] = $objEmp->getInfoByID($strIDEmployee, "functional_code");
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
                    "tax_allowance"
                ) + $objSalary->getEmployeeSalaryDetail($strIDEmployee, "irregular_tax_allowance")
            ),
            true /*isNumeric*/
        );
        // $GLOBALS['strIncome'] .= wrapRow("Tax Allowance", "Rp", standardFormat($objSalary->getEmployeeSalaryDetail($strIDEmployee, "tax_allowance")), true /*isNumeric*/); //form_function.php
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
            "Tax (PPh 21)",
            "Rp",
            standardFormat(
                $objSalary->getEmployeeSalaryDetail(
                    $strIDEmployee,
                    "tax"
                ) + $objSalary->getEmployeeSalaryDetail($strIDEmployee, "irregular_tax")
            ),
            true /*isNumeric*/
        ); //form_function.php
        if ($objSalary->getEmployeeSalaryDetail($strIDEmployee, "tax_pesangon") > 0) {
            $GLOBALS['strDeduction'] .= wrapRow(
                "Tax Pesangon",
                "Rp",
                standardFormat(
                    $objSalary->getEmployeeSalaryDetail($strIDEmployee, "tax_pesangon")
                ),
                true/*isNumeric*/
            );
        }
        $fltTotalDeduction += $objSalary->getEmployeeSalaryDetail($strIDEmployee, "tax");
        $fltTotalDeduction += $objSalary->getEmployeeSalaryDetail($strIDEmployee, "irregular_tax");
        $fltTotalDeduction += $objSalary->getEmployeeSalaryDetail($strIDEmployee, "tax_pesangon");
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
            $pdf = new TCPDF("L", PDF_UNIT, "A5", true, "UTF-8", false);
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
            $strContent = $tbsPage->Source;
        }
    }
    // tampilkan footer HTML
    echo "
	</table>
	</body>
	</html>
		";
    unset($objEmp);
    exit();
}

//----------------------------------------------------------------------
function getDataGrid($db, $strCriteria, $bolLimit = true, $isFullView = false, $isExport = false)
{
    global $bolPrint;
    global $bolCanDelete;
    global $bolCanEdit;
    global $intTotalData;
    global $strDataID;
    global $objSalary;
    global $myDataGrid;
    global $bolIrregular;
    global $bolHideBlank;
    global $arrUserInfo;
    //global $strKriteriaCompany;
    if (isset($_POST['btnExportXLS']) || isset($_POST['btnExcelAll'])) {
        $isExport = true;
    } else {
        $isExport = false;
    }
    //class initialization
    $DEFAULTPAGELIMIT = getSetting("rows_per_page");
    if (!is_numeric($DEFAULTPAGELIMIT)) {
        $DEFAULTPAGELIMIT = 50;
    }
    if ($bolPrint) {
        $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, false, false);
    } else {
        $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", $bolLimit, false, true);
        $myDataGrid->caption = getWords("list of salary");
    }
    $myDataGrid->disableFormTag();
    //$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->pageSortBy = "nama";
    //end of class initialization
    /*
        $strSQL  = "SELECT include_irregular FROM hrd_salary_master WHERE id = $strDataID";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb))
        {
          $bolIncludeIrregular = ($rowDb['include_irregular'] == 't');
        }
    */
    // kumpulkan jenis tunjangan lain-lain dan potongan lain-lain
    $arrOtherAllowance = [];
    $arrOtherDeduction = [];
    $arrIrrAllowance = [];
    $arrIrrFixAllowance = [];
    $intOtherAllowance = 0; // total jenis tunjangan lain-lain
    $intOtherDeduction = 0; // total jenis potongan lain-lain
    $intIrrAllowance = 0; // total jenis irregular income lain-lain
    $intIrrFixAllowance = 0; // total jenis irregular income lain-lain
    $strOtherAllowance = ""; // fields-fields tambahan untuk tunjangan lain-lain
    $strOtherDeduction = ""; // fields-fields tambahan untuk potongan lain-lain
    $strIrrAllowance = ""; // fields-fields tambahan untuk tunjangan lain-lain
    $strIrrFixAllowance = ""; // fields-fields tambahan untuk tunjangan lain-lain
    foreach ($objSalary->arrMA AS $strCode => $arrTmp) // looping data tunjangan lain-lain
    {
        if ($arrTmp['is_default'] == 't') {
            if ($arrTmp['irregular'] == 't') {
                $strIrrFixName = ($arrTmp['name'] == "") ? $arrTmp['allowance_code'] : $arrTmp['name'];
                $arrIrrFixAllowance[$strCode] = $strIrrFixName;
                $strIrrFixAllowance .= ", 0 AS alw_" . $strCode;
                if ($arrTmp['active'] == 't') {
                    $intIrrFixAllowance++;
                }
            }
        } else {
            if ($arrTmp['irregular'] == 't') {
                $strIrrName = ($arrTmp['name'] == "") ? $arrTmp['allowance_code'] : $arrTmp['name'];
                $arrIrrAllowance[$strCode] = $strIrrName;
                $strIrrAllowance .= ", 0 AS alw_" . $strCode;
                if ($arrTmp['active'] == 't') {
                    $intIrrAllowance++;
                }
            } else {
                $strName = ($arrTmp['name'] == "") ? $arrTmp['allowance_code'] : $arrTmp['name'];
                $arrOtherAllowance[$strCode] = $strName;
                $strOtherAllowance .= ", 0 AS alw_" . $strCode;
                $intOtherAllowance++;
            }
        }
    };
    foreach ($objSalary->arrMD AS $strCode => $arrTmp) // looping data deduction lainnya
    {
        if ($arrTmp['is_default'] == 'f') {
            $strName = ($arrTmp['name'] == "") ? $arrTmp['deduction_code'] : $arrTmp['name'];
            $arrOtherDeduction[$strCode] = $strName;
            $strOtherDeduction .= ", 0 AS ded_" . $strCode;
            $intOtherDeduction++;
        }
    }
    //ambil list jenis-jenis iuran / loan
    $intLoanType = 0;
    $strSQL = "SELECT id, type FROM hrd_loan_type";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intLoanType++;
        $arrLoanType[$rowDb['id']] = $rowDb['type'];
    }
    $strSQL = "SELECT t1.*, t2.id AS id_type FROM hrd_loan as t1
                LEFT JOIN hrd_loan_type AS t2 ON t1.type = t2.type WHERE status = 0
                AND payment_from < '" . $objSalary->arrData['salary_finish_date'] . "'
                AND (payment_thru + interval '1 months') > '" . $objSalary->arrData['salary_finish_date'] . "'  ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        if ($rowDb['periode'] == 0) {
            $fltLoan = 0;
        } else {
            $fltLoan = round((((100 + $rowDb['interest']) / 100) * $rowDb['amount']) / $rowDb['periode']);
        }
        if ($rowDb['resign_date'] != "" || $rowDb['resign_date'] != null) {
            if ($rowDb['resign_date'] >= $objSalary->arrData['date_from_salary'] && $rowDb['resign_date'] <= $objSalary->arrData['date_thru_salary']) {
                $intPaymentThruMonth = date("n", strtotime($rowDb['payment_thru']));
                $intPaymentThruYear = date("Y", strtotime($rowDb['payment_thru']));
                $intResignDateMonth = date("n", strtotime($rowDb['resign_date']));
                $intResignDateYear = date("Y", strtotime($rowDb['resign_date']));
                $intMultiplier = ($intPaymentThruYear - $intResignDateYear) * 12 + $intPaymentThruMonth - $intResignDateMonth + 1;
                $fltLoan = $intMultiplier * $fltLoan;
            }
        }
        if (isset($arrEmployeeLoan[$rowDb['id_type']][$rowDb['id_employee']]['amount'])) {
            $arrEmployeeLoan[$rowDb['id_type']][$rowDb['id_employee']]['amount'] += $fltLoan;
        } else {
            $arrEmployeeLoan[$rowDb['id_type']][$rowDb['id_employee']]['amount'] = $fltLoan;
        }
    }
    $newArrayAllowance = newArrayAllowance($objSalary->arrMA);
    $newArrayDeduction = newArrayDeduction($objSalary->arrMD);
    if (!$bolPrint && !$isExport) {
        $myDataGrid->addColumnCheckbox(
            new DataGrid_Column("chkID", "id", ['rowspan' => 2, 'width' => 30], ['align' => 'center', 'nowrap' => ''])
        );
    }
    $myDataGrid->addColumnNumbering(
        new DataGrid_Column(
            "No",
            "",
            ['rowspan' => 2, 'width' => 30],
            ['nowrap' => ''],
            false,
            false,
            "",
            "",
            "numeric",
            true,
            6,
            false,
            "Sub Total ",
            true
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("employee name"),
            "nama",
            ["rowspan" => 2],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            35,
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("employee id"),
            "employee_id",
            ['rowspan' => 2, 'width' => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("division"),
            "division_code",
            ["rowspan" => 2, "width" => 30],
            null,
            true,
            true,
            "",
            "getDivisionName()",
            "string",
            true,
            12,
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("position"),
            "position_code",
            ["rowspan" => 2, "width" => 80],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            6,
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("grade"),
            "grade_code",
            ["rowspan" => 2, "width" => 30],
            null,
            true,
            true,
            "",
            "",
            "string",
            true,
            12,
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("id card"),
            "id_card",
            ["rowspan" => 2, "width" => 30],
            null,
            true,
            true,
            "",
            "",
            "string",
            true,
            12,
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("npwp"),
            "npwp",
            ["rowspan" => 2, "width" => 30],
            null,
            true,
            true,
            "",
            "",
            "string",
            true,
            12,
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("sex"),
            "gender",
            ["rowspan" => 2, "width" => 30],
            ["align" => "center"],
            true,
            true,
            "",
            "printGender()",
            "string",
            true,
            6,
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("family status (tax)"),
            "tax_status_code",
            ["rowspan" => 2, "width" => 30],
            null,
            true,
            true,
            "",
            "",
            "string",
            true,
            12,
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("join date"),
            "join_date",
            ["rowspan" => 2, "width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "formatDate()",
            "string",
            true,
            12,
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("resign date"),
            "resign_date",
            ["rowspan" => 2, "width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "formatDate()",
            "string",
            true,
            12,
            false
        )
    );
    // $myDataGrid->addColumn(new DataGrid_Column(getWords("department"), "department_code", array("rowspan" => 2, "width" => 30), null, true, true, "", "getDepartmentName()", "string", true, 12, false));
    // $myDataGrid->addColumn(new DataGrid_Column(getWords("sub department"), "sub_department_code", array("rowspan" => 2, "width" => 30), null, true, true, "", "getSubDepartmentName()", "string", true, 12, false));
    // $myDataGrid->addColumn(new DataGrid_Column(getWords("section"), "section_code", array("rowspan" => 2, "width" => 30), null, true, true, "", "getSectionName()", "string", true, 12, false));
    // $myDataGrid->addColumn(new DataGrid_Column(getWords("position"), "position_code", array("rowspan" => 2, "width" => 80),  array("nowrap" => "nowrap"), true, true, "", "", "string", true, 6, false));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("status"),
            "employee_status",
            ["rowspan" => 2, "width" => 50],
            ["align" => "center", "nowrap" => "nowrap"],
            true,
            true,
            "",
            "printStatus()",
            "string",
            true,
            12
        )
    );
    //ALLOWANCE & DEDUCTION REGULER
    if (!$bolIrregular) {
        $totalAllowanceNonBenefitNonTax = count($newArrayAllowance['first_view_allowance']);
        $myDataGrid->addSpannedColumn(getWords("income"), $totalAllowanceNonBenefitNonTax + 2);
        for ($i = 0; $i < count($newArrayAllowance['other_allowance']); $i++) {
            $allowanceData = $newArrayAllowance['other_allowance'][$i];
            $myDataGrid->addColumn(
                new DataGrid_Column(
                    getWords($allowanceData['name']),
                    "alw_" . $allowanceData['allowance_code'],
                    ["rowspan" => 1, "width" => 270],
                    ["align" => "right"],
                    false,
                    true,
                    "",
                    "formatNumeric()",
                    "numeric",
                    true,
                    15,
                    true,
                    "alw_" . $allowanceData['allowance_code']
                )
            );
        }
        for ($i = 0; $i < count($newArrayAllowance['default_allowance']); $i++) {
            $allowanceData = $newArrayAllowance['default_allowance'][$i];
            $myDataGrid->addColumn(
                new DataGrid_Column(
                    getWords($allowanceData['name']),
                    $allowanceData['allowance_code'],
                    ["rowspan" => 1, "width" => 270],
                    ["align" => "right"],
                    false,
                    true,
                    "",
                    "formatNumeric()",
                    "numeric",
                    true,
                    15,
                    true,
                    $allowanceData['allowance_code']
                )
            );
        }
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("tax allowance"),
                "tax_allowance",
                ["rowspan" => 1, "width" => 70],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "formatNumeric()",
                "numeric",
                true,
                15,
                true,
                "tax_allowance"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("irregular tax allowance"),
                "irregular_tax_allowance",
                ["rowspan" => 1, "width" => 70],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "formatNumeric()",
                "numeric",
                true,
                15,
                true,
                "irregular_tax_allowance"
            )
        );
        $totalBenefitTax = count($newArrayAllowance['benefit_tax']);
        $myDataGrid->addSpannedColumn(getWords("benefit tax"), $totalBenefitTax);
        for ($i = 0; $i < $totalBenefitTax; $i++) {
            $allowanceData = $newArrayAllowance['benefit_tax'][$i];
            if ($allowanceData['is_default'] == 't') {
                $myDataGrid->addColumn(
                    new DataGrid_Column(
                        getWords($allowanceData['name']),
                        $allowanceData['allowance_code'],
                        ["rowspan" => 1, "width" => 270],
                        ["align" => "right"],
                        false,
                        true,
                        "",
                        "formatNumeric()",
                        "numeric",
                        true,
                        15,
                        true,
                        $allowanceData['allowance_code']
                    )
                );
            } else {
                $myDataGrid->addColumn(
                    new DataGrid_Column(
                        getWords($allowanceData['name']),
                        'alw_' . $allowanceData['allowance_code'],
                        ["rowspan" => 1, "width" => 270],
                        ["align" => "right"],
                        false,
                        true,
                        "",
                        "formatNumeric()",
                        "numeric",
                        true,
                        15,
                        true,
                        $allowanceData['allowance_code']
                    )
                );
            }
        }
        $totalBenefitNonTax = count($newArrayAllowance['benefit_non_tax']);
        $myDataGrid->addSpannedColumn(getWords("benefit non tax"), $totalBenefitNonTax);
        for ($i = 0; $i < $totalBenefitNonTax; $i++) {
            $allowanceData = $newArrayAllowance['benefit_non_tax'][$i];
            if ($allowanceData['is_default'] == 't') {
                $myDataGrid->addColumn(
                    new DataGrid_Column(
                        getWords($allowanceData['name']),
                        $allowanceData['allowance_code'],
                        ["rowspan" => 1, "width" => 270],
                        ["align" => "right"],
                        false,
                        true,
                        "",
                        "formatNumeric()",
                        "numeric",
                        true,
                        15,
                        true,
                        $allowanceData['allowance_code']
                    )
                );
            } else {
                $myDataGrid->addColumn(
                    new DataGrid_Column(
                        getWords($allowanceData['name']),
                        'alw_' . $allowanceData['allowance_code'],
                        ["rowspan" => 1, "width" => 270],
                        ["align" => "right"],
                        false,
                        true,
                        "",
                        "formatNumeric()",
                        "numeric",
                        true,
                        15,
                        true,
                        $allowanceData['allowance_code']
                    )
                );
            }
        }
        $totalOtherDeduction = count($newArrayDeduction['other_deduction']);
        $totalDefaultDeduction = count($newArrayDeduction['default_deduction']);
        $totalDeduction = $totalOtherDeduction + $totalDefaultDeduction;
        $myDataGrid->addSpannedColumn(getWords("deduction"), $totalDeduction);
        for ($i = 0; $i < $totalOtherDeduction; $i++) {
            $deductionData = $newArrayDeduction['other_deduction'][$i];
            $myDataGrid->addColumn(
                new DataGrid_Column(
                    getWords($deductionData['name']),
                    "ded_" . $deductionData['deduction_code'],
                    ["rowspan" => 1, "width" => 270],
                    ["align" => "right"],
                    false,
                    true,
                    "",
                    "formatNumeric()",
                    "numeric",
                    true,
                    15,
                    true,
                    "ded_" . $deductionData['deduction_code']
                )
            );
        }
        for ($i = ($totalDefaultDeduction - 1); $i >= 0; $i--) {
            $deductionData = $newArrayDeduction['default_deduction'][$i];
            if ($deductionData['deduction_code'] != 'loan_deduction') {
                $myDataGrid->addColumn(
                    new DataGrid_Column(
                        getWords($deductionData['name']),
                        $deductionData['deduction_code'],
                        ["rowspan" => 1, "width" => 270],
                        ["align" => "right"],
                        false,
                        true,
                        "",
                        "formatNumeric()",
                        "numeric",
                        true,
                        15,
                        true,
                        $deductionData['deduction_code']
                    )
                );
            }
        }
        /*$myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("calculated tax"),
                "calculated_tax",
                ["rowspan" => 2, "width" => 70],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "formatNumeric()",
                "numeric",
                true,
                15,
                true,
                "calculated_tax"
            )
        );*/
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("tax"),
                "deduction_tax",
                ["rowspan" => 2, "width" => 70],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "formatNumeric()",
                "numeric",
                true,
                15,
                true,
                "deduction_tax"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("irregular tax"),
                "deduction_tax_irregular",
                ["rowspan" => 2, "width" => 70],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "formatNumeric()",
                "numeric",
                true,
                15,
                true,
                "deduction_tax_irregular"
            )
        );
    }
    if ($bolIrregular) {
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("THR Allowance"),
                "thr_allowance",
                ["width" => 70],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "formatNumeric()",
                "numeric",
                true,
                15,
                true,
                "thr_allowance"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("tax allowance"),
                "irregular_tax",
                ["rowspan" => 1, "width" => 70],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "formatNumeric()",
                "numeric",
                true,
                15,
                true,
                "irregular_tax"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("tax deduction"),
                "irregular_tax_allowance",
                ["rowspan" => 1, "width" => 70],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "formatNumeric()",
                "numeric",
                true,
                15,
                true,
                "irregular_tax_allowance"
            )
        );
    } else {
        if ($intLoanType > 1) {
            $myDataGrid->addSpannedColumn(getWords("loan and payment"), $intLoanType);
            foreach ($arrLoanType AS $strCode => $strName) // looping data tunjangan lain-lain
            {
                $myDataGrid->addColumn(
                    new DataGrid_Column(
                        $strName,
                        "loan_" . $strCode,
                        ["rowspan" => 1, "width" => 70],
                        ["nowrap" => "nowrap", "align" => "right"],
                        false,
                        true,
                        "",
                        "formatNumeric()",
                        "numeric",
                        true,
                        15,
                        true,
                        "loan_" . $strCode
                    )
                );
            }
        } else {
            foreach ($arrLoanType AS $strCode => $strName) // looping data tunjangan lain-lain
            {
                $myDataGrid->addColumn(
                    new DataGrid_Column(
                        $strName,
                        "loan_" . $strCode,
                        ["rowspan" => 2, "width" => 70],
                        ["nowrap" => "nowrap", "align" => "right"],
                        false,
                        true,
                        "",
                        "formatNumeric()",
                        "numeric",
                        true,
                        15,
                        true,
                        "loan_" . $strCode
                    )
                );
            }
        }
        //$myDataGrid->addColumn(new DataGrid_Column(getWords("Cash"), "cash_income", array("rowspan" => 2, "width" => 70),  array("align" => "right"), false, true, "", "formatNumeric()", "numeric", true, 6, true, "cash_income"));
        // $myDataGrid->addColumn(new DataGrid_Column(getWords("Transfer"), "transfer_income", array("rowspan" => 2, "width" => 70),  array("align" => "right"), false, true, "", "formatNumeric()", "numeric", true, 6, true, "transfer_income"));
    }
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("total"),
            "total_gross",
            ["rowspan" => 2, "width" => 70],
            ["align" => "right"],
            false,
            true,
            "",
            "formatNumeric()",
            "numeric",
            true,
            6,
            true,
            "total_gross"
        )
    );
    if ($isExport) {
        $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
        $myDataGrid->strFileNameXLS = "salary.xls";
        $myDataGrid->strTitle1 = getWords("salary");
        $myDataGrid->groupBy("id_salary_master");
        $myDataGrid->hasGrandTotal = true;
    }
    $myDataGrid->addRepeaterFunction("printDeniedNote()");
    if (!$bolPrint) {
        /*
        if($_SESSION['sessionUserRole'] == ROLE_SUPER)
        {
           $myDataGrid->addSpecialButton("btnFinish", "btnFinish", "submit", getWords("check"), "onClick=\"return confirmCheck();\"", "finishData()");
           myDataGrid->addSpecialButton("btnApprove", "btnApprove", "submit", getWords("approve"), "onClick=\"return confirmCheck();\"", "approveData()");
        }
        */
        #untuk submit export pdf di form dialog, karena button btnpdf hanya untuk menampilkan modal dialog saja.
        $myDataGrid->addSpecialButton(
            "hidepdf",
            "hidepdf",
            "submit",
            getWords(""),
            "onClick=\"document.formData.target = '_blank'\"",
            "getPDF()"
        );
        $myDataGrid->addSpecialButton(
            "btnSlip",
            "btnSlip",
            "submit",
            getWords("get slip"),
            "onClick=\"document.formData.target = '_blank'\"",
            "getSlip()"
        );
        $myDataGrid->addSpecialButton(
            "btnpdf",
            "btnpdf",
            "button",
            getWords("export pdf"),
            "onClick=\"document.formData.target = '_blank'\"",
            "getPDF()"
        );
        if ($bolCanEdit) {
            //$myDataGrid->addButton("btnPrint", "btnPrint", "submit", getWords("print"), "onClick=\"document.formData.target = '_blank';\"");
            //$myDataGrid->addButton("btnCalculate", "btnCalculate", "submit", getWords("recalculate"), "onClick=\"return confirm('Are you sure want to recalculate this salary calculation?');\"", "saveData()");
            $myDataGrid->addButtonExportExcel(getWords("export excel"), "salary.xls", getWords("list of salary"));
        }
    }
    $myDataGrid->getRequest();
    //--------------------------------
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strCriteriaFlag = "";//$myDataGrid->getCriteria()." AND (flag = 0 OR (flag=0 AND (\"link_id\" IS NULL))) ".$strCriteria;
    $strOrderBy = $myDataGrid->getSortBy();
    if ($bolLimit) {
        $strPageLimit = $myDataGrid->getPageLimit();
        $intPageNumber = $myDataGrid->getPageNumber();
    } else {
        $strPageLimit = null;
        $intPageNumber = null;
    }
    // cari total
    $strSQL = "
      SELECT COUNT(t1.id) AS total
      FROM (
        SELECT *
        FROM hrd_salary_detail WHERE id_salary_master = '$strDataID'
      ) AS t1
      LEFT JOIN (
        SELECT id, employee_name, join_date, id_company, gender, salary_currency
        FROM hrd_employee WHERE 1=1 $strCriteria
      ) AS t2 ON t1.id_employee = t2.id
      WHERE 1=1 $strCriteria

    ";
    if ($bolHideBlank) {
        if ($bolIrregular) {
            $strSQL .= "AND total_net_irregular > 0 AND total_gross_irregular > 0";
        } else {
            $strSQL .= "AND total_net > 0 AND total_gross > 0";
        }
    }
    $res = $db->execute($strSQL);
    if ($row = $db->fetchrow($res)) {
        $myDataGrid->totalData = ($row['total'] == "") ? 0 : $row['total'];
    }
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQL = "
      SELECT t1.*, t2.employee_name as nama, t2.join_date, t2.gender, t2.salary_currency, t2.resign_date, t2.id_card,
        t3.position_group, 0 as absence, t4.weight AS grade_weight
        $strOtherAllowance $strOtherDeduction
      FROM (
        SELECT *
        FROM hrd_salary_detail WHERE id_salary_master = '$strDataID'
      ) AS t1
      LEFT JOIN (
        SELECT id, employee_name, join_date, resign_date, id_card, id_company, gender, salary_currency
        FROM hrd_employee WHERE 1=1 $strCriteria
      ) AS t2 ON t1.id_employee = t2.id
      LEFT JOIN (
        SELECT position_code, position_group
        FROM hrd_position
      ) AS t3 ON t1.position_code= t3.position_code
      LEFT JOIN (
        SELECT grade_code, weight
        FROM hrd_salary_grade
      ) AS t4 ON t1.grade_code= t4.grade_code
      WHERE 1=1 $strCriteria
    ";
    if ($bolHideBlank) {
        if ($bolIrregular) {
            $strSQL .= "AND total_net_irregular > 0 AND total_gross_irregular > 0";
        } else {
            $strSQL .= "AND total_net > 0 AND total_gross > 0";
        }
    }
    //handle sort
    if ($isExport) {
        $strSQL .= " ORDER BY t2.employee_name";
    } else if ($myDataGrid->isShowSort) {
        if ($myDataGrid->pageSortBy != "") {
            $strSQL .= " ORDER BY " . $myDataGrid->sortName . " " . $myDataGrid->sortOrder;
        }
    }
    //handle page limit
    if ($myDataGrid->isShowPageLimit && !$isExport) {
        if (is_numeric($myDataGrid->pageLimit) && $myDataGrid->pageLimit > 0) {
            $strSQL .= " LIMIT $myDataGrid->pageLimit OFFSET " . $myDataGrid->getOffsetStart();
        }
    }
    /*$strSQLtemp= "SELECT id FROM hrd_employee WHERE 1=1";
    $resDbtemp = $db->execute($strSQLtemp);
    while ($rowDbtemp = $db->fetchrow($resDbtemp)){
        $objSalary->arrDD['potongan_insentif'][$rowDbtemp['id']]['amount']=0;
    }
        $strSQLtemp= "SELECT * from hrd_absence where date_from BETWEEN '".$objSalary->arrData['date_from']."' AND '".$objSalary->arrData['date_thru']."' AND (absence_type_code='A')";
        $resDbtemp = $db->execute($strSQLtemp);
        while ($rowDbtemp = $db->fetchrow($resDbtemp)){
            $total=0;
            $dur=getIntervalDate($rowDbtemp['date_from'],$objSalary->arrData['date_thru'])+1;
            if ($dur<$rowDbtemp['duration']){
                $total += $dur;
            } else $total += $rowDbtemp['duration'];
            if ($objSalary->arrDD['potongan_insentif'][$rowDbtemp['id_employee']]['amount'] < 1){
                $objSalary->arrDD['potongan_insentif'][$rowDbtemp['id_employee']]['amount'] += 3;
            }
        }

        $strSQLtemp= "SELECT * from hrd_absence where date_from BETWEEN '".$objSalary->arrData['date_from']."' AND '".$objSalary->arrData['date_thru']."' AND (absence_type_code='I')";
        $resDbtemp = $db->execute($strSQLtemp);
        while ($rowDbtemp = $db->fetchrow($resDbtemp)){
            $total=0;
            $dur=getIntervalDate($rowDbtemp['date_from'],$objSalary->arrData['date_thru'])+1;
            if ($dur<$rowDbtemp['duration']){
                $total += $dur;
            } else $total += $rowDbtemp['duration'];
            if ($objSalary->arrDD['potongan_insentif'][$rowDbtemp['id_employee']]['amount'] < 3){
              if ($total>1){
                $objSalary->arrDD['potongan_insentif'][$rowDbtemp['id_employee']]['amount'] += 1;
              } else if (($total==1) && ($objSalary->arrDD['potongan_insentif'][$rowDbtemp['id_employee']]['amount'] < 2))
                $objSalary->arrDD['potongan_insentif'][$rowDbtemp['id_employee']]['amount'] += 2;
            }
        }



        $strSQLtemp= "SELECT * from hrd_absence where date_from BETWEEN '".$objSalary->arrData['date_from']."' AND '".$objSalary->arrData['date_thru']."' AND (absence_type_code='SD' OR absence_type_code='STD')";
        $resDbtemp = $db->execute($strSQLtemp);
        while ($rowDbtemp = $db->fetchrow($resDbtemp)){
            $total=0;
            $dur=getIntervalDate($rowDbtemp['date_from'],$objSalary->arrData['date_thru'])+1;
            if ($dur<$rowDbtemp['duration']){
                $total += $dur;
            } else $total += $rowDbtemp['duration'];
            if ($objSalary->arrDD['potongan_insentif'][$rowDbtemp['id_employee']]['amount'] < 3){
                $objSalary->arrDD['potongan_insentif'][$rowDbtemp['id_employee']]['amount'] += 1;
            }
        }


        $strSQLtemp= "SELECT * from hrd_attendance where attendance_date BETWEEN '".$objSalary->arrData['date_from']."' AND '".$objSalary->arrData['date_thru']."' AND late_duration>0";
        $resDbtemp = $db->execute($strSQLtemp);
        while ($rowDbtemp = $db->fetchrow($resDbtemp)){
            if ($objSalary->arrDD['potongan_insentif'][$rowDbtemp['id_employee']]['amount'] < 3){
                $objSalary->arrDD['potongan_insentif'][$rowDbtemp['id_employee']]['amount'] += 1;
            }
        }

        $strSQLtemp= "SELECT * from hrd_attendance where attendance_date BETWEEN '".$objSalary->arrData['date_from']."' AND '".$objSalary->arrData['date_thru']."' AND early_duration>0";
        $resDbtemp = $db->execute($strSQLtemp);
        while ($rowDbtemp = $db->fetchrow($resDbtemp)){
            if ($objSalary->arrDD['potongan_insentif'][$rowDbtemp['id_employee']]['amount'] < 3){
                $objSalary->arrDD['potongan_insentif'][$rowDbtemp['id_employee']]['amount'] += 1;
            }
        }*/
    //get query
    $dataset = [];
    $resDb = $db->execute($strSQL);
    //put result to array dataset
    while ($rowDb = $db->fetchrow($resDb)) {
        //$rowDb['total_ot_min'] = (1.5 * $rowDb['ot1_min']) * (2 * $rowDb['ot2_min']) * (3 * $rowDb['ot3_min']) * (4 * $rowDb['ot4_min']); // hardcode
        //$rowDb['allowance_tax'] = $rowDb['tax_allowance'] + $rowDb['irregular_tax_allowance'];
        //$rowDb['allowance_tax'] = $rowDb['tax_allowance'];
        //$rowDb['deduction_tax'] = $rowDb['tax'] + $rowDb['irregular_tax'];
        $rowDb['deduction_tax'] = $rowDb['tax'];
        $rowDb['deduction_tax_irregular'] = $rowDb['irregular_tax'];
        $rowDb['shift_hour'] = $rowDb['shift_hour'] / 60;
        $rowDb['l_e'] = standardFormat($rowDb['late_round'] + $rowDb['early_round'], true, 2);
        $rowDb['ot1_min'] = ($rowDb['ot1_min']) / 60;
        $rowDb['ot2_min'] = ($rowDb['ot2_min'] + $rowDb['ot2b_min']) / 60;
        $rowDb['ot3_min'] = $rowDb['ot3_min'] / 60;
        $rowDb['ot4_min'] = $rowDb['ot4_min'] / 60;
        //$rowDb['ot3_min']=0;
        //$rowDb['ot4_min']=0;
        $rowDb['total_ot_min'] = $rowDb['total_ot_min'] / 60;
        //$rowDb['otx_min'] = $rowDb['otx_min'] / 60;
        $rowDb['otx_min'] = $rowDb['ot1_min'] * 1.5 + $rowDb['ot2_min'] * 2 + $rowDb['ot3_min'] * 3 + $rowDb['ot4_min'] * 4;
        //$rowDb['jamsostek_deduction'] += $rowDb['jamsostek_allowance']; // sudah ditambah dengan tunjangannya
        foreach ($arrOtherAllowance AS $strCode => $strName) {
            if (isset($objSalary->arrDA[$strCode][$rowDb['id_employee']])) {
                $rowDb['alw_' . $strCode] = $objSalary->arrDA[$strCode][$rowDb['id_employee']]['amount'];
            }
        }
        foreach ($arrIrrAllowance AS $strCode => $strName) {
            if (isset($objSalary->arrDA[$strCode][$rowDb['id_employee']])) {
                $rowDb['alw_' . $strCode] = $objSalary->arrDA[$strCode][$rowDb['id_employee']]['amount'];
            }
        }
        foreach ($arrOtherDeduction AS $strCode => $strName) {
            if (isset($objSalary->arrDD[$strCode][$rowDb['id_employee']])) {
                $rowDb['ded_' . $strCode] = $objSalary->arrDD[$strCode][$rowDb['id_employee']]['amount'];
            }
        }
        foreach ($arrLoanType AS $strCode => $strName) {
            if (isset($arrEmployeeLoan[$strCode][$rowDb['id_employee']])) {
                $rowDb['loan_' . $strCode] = $arrEmployeeLoan[$strCode][$rowDb['id_employee']]['amount'];
            } else {
                $rowDb['loan_' . $strCode] = 0;
            }
        }
        /*$intRound = (isset($objSalary->arrConf['salary_round']) && is_numeric(
                $objSalary->arrConf['salary_round']
            )) ? $objSalary->arrConf['salary_round'] : 1;*/
        //Hardcode, never use this lines on standard package
        //----------------------
        $rowDb['total_gross'] = $objSalary->arrDetail[$rowDb['id_employee']]['total_gross'];// -$objSalary->arrDetail[$rowDb['id_employee']]['tax'] - $objSalary->arrDD['BPJS'][$rowDb['id_employee']]['amount'] - $objSalary->arrDetail[$rowDb['id_employee']]['jamsostek_deduction'] - $objSalary->arrDD['COS'][$rowDb['id_employee']]['amount'] - $objSalary->arrDA['tunjangan_jk'][$rowDb['id_employee']]['amount'];
        //$rowDb['total_gross'] = roundMoney($rowDb['total_gross'], $intRound);
        //$rowDb['cash_income'] = $rowDb['alw_tunjangan_kehadiran'] - $rowDb['ded_potongan_kehadiran'] - $rowDb['ded_potongan_cuti'];
        //$rowDb['transfer_income'] = $rowDb['total_gross'] - $rowDb['cash_income'];
        $rowDb['absence'] = $objSalary->arrDetail[$intID]['paid_absence_day'] + $objSalary->arrDetail[$intID]['unpaid_absence_day']; //----------------------
        $rowDb['calculated_tax'] = is_null(
            $rowDb['calculated_tax']
        ) ? 0 : ($rowDb['calculated_tax'] + $rowDb['irregular_tax']);
        if ($rowDb['grade_weight'] > $arrUserInfo['grade_weight'] || empty($arrUserInfo['grade_weight'])) {
            $dataset[] = $rowDb;
        }
    }
    $intTotalData = count($dataset);
    $myDataGrid->bind($dataset);
    return $myDataGrid->render();
}

// format tampilan gender
function printGender($params)
{
    extract($params);
    return ($value == 0) ? "F" : "M";
}    // format tampilan gender
function printCurrency($params)
{
    global $ARRAY_CURRENCY;
    extract($params);
    return $ARRAY_CURRENCY[$value];
}

// format tampilan gender
function printColumn($item, $key, &$myDataGrid)
{
    if ($item['is_default'] == 't') {
        $myDataGrid->addColumn(
            new DataGrid_Column(
                $item['name'],
                $item['allowance_code'],
                ["width" => 70],
                ["nowrap" => "nowrap", "align" => "right"],
                false,
                true,
                "",
                "formatNumeric()",
                "numeric",
                true,
                15,
                true,
                $item['allowance_code']
            )
        );
    }
}

// format tampilan employee status
function printStatus($params)
{
    extract($params);
    global $ARRAY_EMPLOYEE_STATUS;
    return getWords($ARRAY_EMPLOYEE_STATUS[$value]);
}

// format tampilan staff/nonstaff
function printStaff($params)
{
    global $POSITION_STAFF;
    extract($params);
    $str = ($value == $POSITION_STAFF) ? getWords("staff") : getWords("non staff");
    return $str;
}

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends TCPDF
{

    //Page header
    public function Header()
    {
        // get the current page break margin
        $bMargin = $this->getBreakMargin();
        // get current auto-page-break mode
        $auto_page_break = $this->AutoPageBreak;
        // disable auto-page-break
        $this->SetAutoPageBreak(false, 0);
        // set bacground image
        $img_file = '../images/logo_back_slip.png';
        //$this->Image($img_file, 0, 45, '', '', '', '', 'M', false, 300, 'C', false, false, 0);
        $this->Image($img_file, 10, 20, -300);
        // restore auto-page-break status
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        $this->setPageMark();
    }
}

// format tampilan tanggal
// format tampilan angka
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo($db);
    $bolIsEmployee = ($_SESSION['sessionUserRole'] != ROLE_ADMIN);
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    if ($strDataID == "") {
        header("location:salary_calculation.php");
        exit();
    }
    $objSalary = new clsSalaryCalculation($db, $strDataID); // cls_salary_calculation.php
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
    } else {
        // gak ada, keluar
        header("location:salary_calculation.php");
        exit();
    }
    // hitung ulang data jika ada perintah
    if (isset($_POST['btnFinish'])) {
        $strSQL = "UPDATE hrd_salary_master SET status = " . SALARY_CALCULATION_FINISH . " ";
        $strSQL .= "WHERE id = '$strDataID' ";
        $resExec = $db->execute($strSQL);
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "Finish : $strDataID", 0);
    } else if (isset($_POST['btnApprove'])) {
        if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
            approveData($db);
        }
    }
    $strCalculationMenu = "<b><a class=\"btn btn-primary btn-small\" href='salary_calculation.php'>" . getWords(
            "salary calculation list"
        ) . "</a></b>";//getCalculationMenu($strDataID, 5, $intStatus);
    // ------ AMBIL DATA KRITERIA -------------------------
    (isset($_POST['dataEmployee'])) ? $strDataEmployee = $_POST['dataEmployee'] : $strDataEmployee = "";
    (isset($_POST['dataSalaryCurrency'])) ? $strDataSalaryCurrency = $_POST['dataSalaryCurrency'] : $strDataSalaryCurrency = "";
    (isset($_POST['dataCompany'])) ? $strDataCompany = $_POST['dataCompany'] : $strDataCompany = "";
    (isset($_POST['dataBranch'])) ? $strDataBranch = $_POST['dataBranch'] : $strDataBranch = "";
    (isset($_POST['dataDivision'])) ? $strDataDivision = $_POST['dataDivision'] : $strDataDivision = "";
    (isset($_POST['dataDepartment'])) ? $strDataDepartment = $_POST['dataDepartment'] : $strDataDepartment = "";
    (isset($_POST['dataSubDepartment'])) ? $strDataSubDepartment = $_POST['dataSubDepartment'] : $strDataSubDepartment = "";
    (isset($_POST['dataSection'])) ? $strDataSection = $_POST['dataSection'] : $strDataSection = "";
    (isset($_POST['dataSubSection'])) ? $strDataSubSection = $_POST['dataSubSection'] : $strDataSubSection = "";
    (isset($_POST['dataEmployeeType'])) ? $strDataEmployeeType = $_POST['dataEmployeeType'] : $strDataEmployeeType = "";
    (isset($_POST['dataPage'])) ? $intCurrPage = $_POST['dataPage'] : $intCurrPage = 1;
    if (!is_numeric($intCurrPage)) {
        $intCurrPage = 1;
    }
    scopeCBDataEntry($strDataEmployee, $_SESSION['sessionUserRole'], $arrUserInfo);
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    //$strKriteria = "";
    if ($strDataEmployee != "") {
        $strKriteria .= "AND employee_id = '$strDataEmployee' ";
    }
    if ($strDataBranch != "") {
        $strKriteria .= "AND branch_code = '$strDataBranch' ";
    }
    if ($strDataDivision != "") {
        $strKriteria .= "AND division_code = '$strDataDivision' ";
    }
    if ($strDataDepartment != "") {
        $strKriteria .= "AND department_code = '$strDataDepartment' ";
    }
    if ($strDataSubDepartment != "") {
        $strKriteria .= "AND sub_department_code = '$strDataSubDepartment' ";
    }
    if ($strDataSection != "") {
        $strKriteria .= "AND section_code = '$strDataSection' ";
    }
    if ($strDataSubSection != "") {
        $strKriteria .= "AND sub_section_code = '$strDataSubSection' ";
    }
    if ($strDataSalaryCurrency != "") {
        $strKriteria .= "AND salary_currency = '$strDataSalaryCurrency' ";
    }
    if ($strDataEmployeeType == "INA") {
        $strKriteria .= "AND substring(employee_id from 1 for 1) = 'I' ";
    } else if ($strDataEmployeeType == "JPN") {
        $strKriteria .= "AND substring(employee_id from 1 for 1) = 'J' ";
    } else if ($strDataEmployeeType == "") {
        $strKriteria .= "";
    }
    //$strKriteria .= $strKriteriaCompany;
    if ($bolCanView) {
        //$strDataDetail = getData($db, $strDataID, $intTotalData, $strKriteria,$intCurrPage);
        $strDataDetail = getDataGrid($db, $strKriteria, $intCurrPage);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
    // generate data hidden input dan element form input
    $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee class=\"form-control\" maxlength=30 value=\"" . $strDataEmployee . "\" >";
    $strInputDataCurrency = getComboFromArray(
        $ARRAY_CURRENCY,
        "dataSalaryCurrency",
        $strDataSalaryCurrency,
        $strEmptyOption,
        " style=\"width:$strDefaultWidthPx\""
    );
    $strInputBranch = getBranchList(
        $db,
        "dataBranch",
        $strDataBranch,
        $strEmptyOption,
        "",
        "style=\"width:$strDefaultWidthPx\""
    );
    $strInputDivision = getDivisionList(
        $db,
        "dataDivision",
        $strDataDivision,
        $strEmptyOption,
        "",
        "style=\"width:$strDefaultWidthPx\""
    );
    $strInputDepartment = getDepartmentList(
        $db,
        "dataDepartment",
        $strDataDepartment,
        $strEmptyOption,
        "",
        "style=\"width:$strDefaultWidthPx\""
    );
    $strInputSubDepartment = getSubDepartmentList(
        $db,
        "dataSubDepartment",
        $strDataSubDepartment,
        $strEmptyOption,
        "",
        "style=\"width:$strDefaultWidthPx\""
    );
    $strInputSection = getSectionList(
        $db,
        "dataSection",
        $strDataSection,
        $strEmptyOption,
        "",
        "style=\"width:$strDefaultWidthPx\""
    );
    $strInputSubSection = getSubSectionList(
        $db,
        "dataSubSection",
        $strDataSubSection,
        $strEmptyOption,
        "",
        "style=\"width:$strDefaultWidthPx\""
    );
    $strInputEmployeeType = "<select class=\"form-control select2\" name=dataEmployeeType id=dataEmployeeType ><option value=\"\"></option><option value=\"INA\">Karyawan Indonesia</option><option value=\"JPN\">Karyawan Jepang</option></select>";
    if ($strDataEmployeeType == "INA") {
        $strInputEmployeeType = "<select class=\"form-control select2\" name=dataEmployeeType id=dataEmployeeType ><option value=\"\"></option><option value=\"INA\" selected>Karyawan Indonesia</option><option value=\"JPN\">Karyawan Jepang</option></select>";
    } else if ($strDataEmployeeType == "JPN") {
        $strInputEmployeeType = "<select class=\"form-control select2\" name=dataEmployeeType id=dataEmployeeType ><option value=\"\"></option><option value=\"INA\">Karyawan Indonesia</option><option value=\"JPN\" selected>Karyawan Jepang</option></select>";
    }
    //untuk filter ketika meng-export dengan filter bank (by Farhan)
    //global $intDefaultWidthPx;
    $strSQL = "SELECT bank_code FROM hrd_employee ";
    $strSQL .= "WHERE id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        $bolNewData = false;
        $arrData['dataBankCode'] = "" . $rowDb['bank_code'];
    }
    if ($bolNewData) {
        $arrData['dataBankCode'] = "";
    }
    $strReportType = getBankList(
        $db,
        "dataBankCode",
        $arrData['dataBankCode'],
        $strEmptyOption,
        "",
        " style=\"width:250\""
    );
    //---------------------------------------------------------------------------------------------------
    $strHidden .= "<input type=hidden name=dataCompany value=\"$strDataCompany\">";
    $strHidden .= "<input type=hidden name=dataBranch value=\"$strDataBranch\">";
    //$strHidden .= "<input type=hidden name=dataGrade value=\"$strDataGrade\">";
    $strHidden .= "<input type=hidden name=dataSalaryCurrency value=\"$strDataSalaryCurrency\">";
    $strHidden .= "<input type=hidden name=dataDivision value=\"$strDataDivision\">";
    $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
    $strHidden .= "<input type=hidden name=dataSubDepartment value=\"$strDataSubDepartment\">";
    $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
    $strHidden .= "<input type=hidden name=dataSubSection value=\"$strDataSubSection\">";
    $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
    $strHidden .= "<input type=hidden name=dataPage value=\"$intCurrPage\">";
    $strHidden .= "<input type=hidden name=dataID value=\"$strDataID\">";
    $strHidden .= "<input type=hidden name=dataEmployeeType value=\"$strDataEmployeeType\">";
}
if ($bolPrint) {
    $strMainTemplate = getTemplate("salary_calculation_result_print.html");
} else {
    $strTemplateFile = getTemplate("salary_calculation_result.html");
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("salary calculation result table");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
