<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('salary_func.php');
include_once('activity.php');
include_once("../global/cls_date.php");
include_once('../includes/model/model.php');
include_once('../includes/datagrid2/datagrid.php');
include_once("cls_salary_calculation_hotel.php");
include_once("cls_employee.php");
$dataPrivilege = getDataPrivileges(
    "salary_calculation_hotel.php",
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
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("subsection");
$strWordsEmployeeID = getWords("employee id");
$strWordsReport = getWords("report");
$strWordsBankTransferS = getWords("bank transfer - salary");
$strWordsBankTransferL = getWords("bank transfer - loan");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menyimpan data
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
// fungsi untuk melakukan proses slip gaji
function getSlip()
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
      $$strVar = (getSetting($strCode . "_show") == 't');
    }
  }
  // die();
  $objDate = new clsCommonDate();
  $objEmp = new clsEmployees($db);
  $objEmp->loadData("id, employee_id, employee_name, id_company, join_date, grade_code");
  // tampilkan header HTML dulu
  echo "
<html>
<head>
<title>Slip</title>
<meta http-equiv='Content-Type' content='application/vnd.ms-word; charset=iso-8859-1'>
<meta http-equiv='Content-Disposition' content='attachment; charset=iso-8859-1'>
<link href='../css/invosa.css' rel='stylesheet' type='text/css'>
</head>
<body onLoad=\"window.print();\" marginheight=0 marginwidth=0 leftmargin=10 rightmargin=0 topmargin=0>
<table cellspacing=0 cellpadding=0 border=0 width='100%'>
    ";
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
    $GLOBALS['strCompany'] = getCompanyCode($intCompany);
    $strDiv = $objSalary->getEmployeeSalaryDetail($strIDEmployee, "division_code");
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
            $GLOBALS['strIncome'] .= wrapRow($arrInfo['name'], "Rp", standardFormat($fltAmount), true /*isNumeric*/);
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
            $GLOBALS['strIncome'] .= wrapRow($arrInfo['name'], "Rp", standardFormat($fltAmount), true/*isNumeric*/);
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
        standardFormat($objSalary->getEmployeeSalaryDetail($strIDEmployee, "tax")),
        true /*isNumeric*/
    ); //form_function.php
    $GLOBALS['strIncome'] .= wrapRow(
        "Tax Irregular Allowance",
        "Rp",
        standardFormat(
            $objSalary->getEmployeeSalaryDetail($strIDEmployee, "irregular_tax")
        ),
        true /*isNumeric*/
    ); //form_function.php
    $GLOBALS['strIncome'] .= wrapRow(
        "JHT 2% Allowance",
        "Rp",
        standardFormat(
            $objSalary->getEmployeeSalaryDetail($strIDEmployee, "jht_2_allowance")
        ),
        true /*isNumeric*/
    ); //form_function.php
    $fltTotalIncome += $objSalary->getEmployeeSalaryDetail($strIDEmployee, "tax");
    $fltTotalIncome += $objSalary->getEmployeeSalaryDetail($strIDEmployee, "irregular_tax");
    $fltTotalIncome += $objSalary->getEmployeeSalaryDetail($strIDEmployee, "jht_2_allowance");
    // tampilkan potongan
    if (!$bolIrregular) {
      foreach ($objSalary->arrMD AS $strCode => $arrInfo) {
        $fltAmount = $objSalary->getEmployeeSalaryDetail($strIDEmployee, $strCode);
        if ($arrInfo['is_default'] == 't' && $arrInfo['show'] == 't') {
          if ($arrInfo['hidezero'] == 'f' || $fltAmount != 0) {
            $GLOBALS['strDeduction'] .= wrapRow($arrInfo['name'], "Rp", standardFormat($fltAmount), true/*isNumeric*/);
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
            $GLOBALS['strDeduction'] .= wrapRow($arrInfo['name'], "Rp", standardFormat($fltAmount), true/*isNumeric*/);
            $fltTotalDeduction += $fltAmount;
          } else {
            $GLOBALS['strDeductionBlankSpace'] .= wrapRow("", "", "", false/*isNumeric*/);
          }
        }
      }
      //$GLOBALS['strDeduction'] .= wrapRow("Potongan L/E", "Rp", standardFormat($objSalary->getEmployeeSalaryDetail($strIDEmployee, "absence_deduction")), true /*isNumeric*/); //form_function.php
    }
    //      else
    //tampilkan zakat utk irregular income
    //$GLOBALS['strDeduction'] .= wrapRow("Zakat", "Rp", standardFormat($objSalary->getEmployeeSalaryDetail($strIDEmployee, "zakat_deduction_irregular")), true /*isNumeric*/); //form_function.php
    //tampilkan tax (tax reguler + irreguler)
    $GLOBALS['strDeduction'] .= wrapRow(
        "Tax",
        "Rp",
        standardFormat($objSalary->getEmployeeSalaryDetail($strIDEmployee, "tax")),
        true /*isNumeric*/
    ); //form_function.php
    $GLOBALS['strDeduction'] .= wrapRow(
        "Tax Irregular",
        "Rp",
        standardFormat(
            $objSalary->getEmployeeSalaryDetail($strIDEmployee, "irregular_tax")
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
    $tbsPage = new clsTinyButStrong;
    $tbsPage->LoadTemplate("templates/slip_template2.html");
    $tbsPage->Show(TBS_OUTPUT);
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
  $myDataGrid->pageSortBy = "\"grouper\",t1.employee_name";
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
  foreach ($objSalary->arrMD AS $strCode => $arrTmp) // looping data tunjangan lain-lain
  {
    if ($arrTmp['is_default'] == 'f') {
      $strName = ($arrTmp['name'] == "") ? $arrTmp['deduction_code'] : $arrTmp['name'];
      $arrOtherDeduction[$strCode] = $strName;
      $strOtherDeduction .= ", 0 AS alw_" . $strCode;
      $intOtherDeduction++;
    }
  }
  //ambil list jenis-jenis iuran / loan
  $intLoanType = 0;
  $strSQL = "SELECT id, type FROM hrd_loan_type ORDER BY note";
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
    if (isset($arrEmployeeLoan[$rowDb['id_type']][$rowDb['id_employee']]['amount'])) {
      $arrEmployeeLoan[$rowDb['id_type']][$rowDb['id_employee']]['amount'] += $fltLoan;
    } else {
      $arrEmployeeLoan[$rowDb['id_type']][$rowDb['id_employee']]['amount'] = $fltLoan;
    }
  }
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
          "employee_name",
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
          getWords("fam."),
          "family_status_code",
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
          getWords("grouper"),
          "grouper",
          ["rowspan" => 2, "width" => 30, "style" => "display:none"],
          ["style" => "display:none"],
          true,
          true,
          "",
          "",
          "string",
          true,
          8,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("currency"),
          "salary_currency",
          ["rowspan" => 2, "width" => 30],
          ["align" => "center"],
          true,
          true,
          "",
          "printCurrency()",
          "string",
          true,
          6,
          false
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
          getWords("department"),
          "department_code",
          ["rowspan" => 2, "width" => 30],
          null,
          true,
          true,
          "",
          "getDepartmentName()",
          "string",
          true,
          12,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("section"),
          "section_code",
          ["rowspan" => 2, "width" => 30],
          null,
          true,
          true,
          "",
          "getSectionName()",
          "string",
          true,
          12,
          false
      )
  );
  // $myDataGrid->addColumn(new DataGrid_Column(getWords("sub"), "sub_section_code", array("rowspan" => 2, "width" => 30), null, true, true, "", "getSubSectionName()", "string", true, 12, false));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("grade"),
          "grade_code",
          ["rowspan" => 2, "width" => 30],
          ["align" => "center", "nowrap" => "nowrap"],
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
          getWords("level"),
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
  //$myDataGrid->addColumn(new DataGrid_Column(getWords("staff"), "position_group", array("rowspan" => 2, "width" => 50),  array("align" => "center", "nowrap" => "nowrap"), true, true, "", "printStaff()", "string", true, 12));
  //	if ($intIrrAllowance + $intIrrFixAllowance > 1)
  //    {
  ////      $myDataGrid->addSpannedColumn(getWords("irregular income"), ($intIrrAllowance + $intIrrFixAllowance ));
  ////      foreach ($arrIrrFixAllowance AS $strCode => $strName) // looping data irregular income
  ////      {
  ////        $myDataGrid->addColumn(new DataGrid_Column($strName, $strCode, array("width" => 70),  array("nowrap" => "nowrap", "align" => "right"), false, true, "", "formatNumeric()", "numeric", true, 15, true, $strCode));
  ////      }
  ////      foreach ($arrIrrAllowance AS $strCode => $strName) // looping data irregular income
  ////      {
  ////        $myDataGrid->addColumn(new DataGrid_Column($strName, "alw_".$strCode, array("width" => 70),  array("nowrap" => "nowrap", "align" => "right"), false, true, "", "formatNumeric()", "numeric", true, 15, true, "alw_".$strCode));
  ////      }
  //      $myDataGrid->addColumn(new DataGrid_Column(getWords("sub total"), "total_net_irregular", array("rowspan" => 2, "width" => 70),  array("nowrap" => "nowrap", "align" => "right"), false, true, "", "formatNumeric()", "numeric", true, 15, true, "total_irregular"));
  //
  //    }
  //    else
  //    {
  ////      foreach ($arrIrrFixAllowance AS $strCode => $strName) // looping data irregular income
  ////      {
  ////        $myDataGrid->addColumn(new DataGrid_Column($strName, $strCode, array("rowspan" => 2, "width" => 70),  array("nowrap" => "nowrap", "align" => "right"), false, true, "", "formatNumeric()", "numeric", true, 15, true, $strCode));
  ////      }
  ////      foreach ($arrIrrAllowance AS $strCode => $strName) // looping data irregular income
  ////      {
  ////        $myDataGrid->addColumn(new DataGrid_Column($strName, "alw_".$strCode, array("rowspan" => 2, "width" => 70),  array("nowrap" => "nowrap", "align" => "right"), false, true, "", "formatNumeric()", "numeric", true, 15, true, "alw_".$strCode));
  ////      }
  //    }
  $myDataGrid->addSpannedColumn(getWords("allowance"), 16);
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("basic salary"),
          "alw_basic_salary",
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
          "alw_basic_salary"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("rapel gaji"),
          "alw_rapel",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "alw_rapel"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("service charge"),
          "fix_service_charge",
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
          "fix_service_charge"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("variable service charge"),
          "alw_upl_var_sc",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "alw_upl_var_sc"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("tax allowance regular"),
          "tax_allowance",
          ["width" => 90],
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
          getWords("tax allowance irregular"),
          "irregular_tax_allowance",
          ["width" => 90],
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
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("other_allowance"),
          "alw_other_allowance",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "alw_other_allowance"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("meal allowance"),
          "alw_upl_meal",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "alw_upl_meal"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("severance allowance"),
          "alw_upl_severance",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "alw_upl_severance"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("jht 2% allowance"),
          "jht_2_allowance",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          0
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("jkm allowance"),
          "jkm_allowance",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "jkm_allowance"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("jkm allowance"),
          "jkk_allowance",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "jkk_allowance"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("jht 3.7% allowance"),
          "jamsostek_allowance",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "jamsostek_allowance"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("bonus"),
          "alw_bonus",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "alw_bonus"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("thr allowance"),
          "thr_allowance",
          ["width" => 90],
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
          getWords("rapel tunjangan cuti"),
          "alw_rapel_tunjangan_cuti",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "alw_rapel_tunjangan_cuti"
      )
  );
  $myDataGrid->addSpannedColumn(getWords("deduction"), 7);
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("tax"),
          "tax",
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
          "tax"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("irregular tax"),
          "irregular_tax",
          ["width" => 70],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric(ignoreZero=true,decimal=2)",
          "numeric",
          true,
          15,
          true,
          "irregular_tax"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("jht 2% deduction"),
          "jamsostek_deduction",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "jamsostek_deduction"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("jkm deduction"),
          "jkm_deduction",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "jkm_deduction"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("jkm deduction"),
          "jkk_deduction",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "jkk_deduction"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("jht 3.7% deduction"),
          "jamsostek_deduction_jht",
          ["width" => 90],
          ["nowrap" => "nowrap", "align" => "right"],
          false,
          true,
          "",
          "formatNumeric()",
          "numeric",
          true,
          15,
          true,
          "jamsostek_deduction_jht"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("other deduction"),
          "ded_other_deduction",
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
          "ded_other_deduction"
      )
  );
  foreach ($arrOtherAllowance AS $strCode => $strName) // looping data tunjangan lain-lain
  {
    //$myDataGrid->addColumn(new DataGrid_Column($strName, "alw_".$strCode, array("width" => 70),  array("nowrap" => "nowrap", "align" => "right"), false, true, "", "formatNumeric()", "numeric", true, 15, true, "alw_".$strCode));
  }
  if ($bolIrregular) {
    //      $myDataGrid->addColumn(new DataGrid_Column(getWords("THR Allowance"), "alw_thr_allowance", array("width" => 70),  array("nowrap" => "nowrap", "align" => "right"), false, true, "", "formatNumeric()", "numeric", true, 15, true, "alw_thr_allowance"));
  } else {
    //loan
    if ($intLoanType > 1) {
      $myDataGrid->addSpannedColumn(getWords("loan and payment"), $intLoanType);
      foreach ($arrLoanType AS $strCode => $strName) // looping data tunjangan lain-lain
      {
        $myDataGrid->addColumn(
            new DataGrid_Column(
                $strName,
                "loan_" . $strCode,
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
  }
  if ($isExport) {
    $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
    $myDataGrid->strFileNameXLS = "salary.xls";
    $myDataGrid->strTitle1 = getWords("salary");
    $myDataGrid->groupBy("grouper");
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
    $myDataGrid->addSpecialButton(
        "btnSlip",
        "btnSlip",
        "submit",
        getWords("get slip"),
        "onClick=\"document.formData.target = '_blank'\"",
        "getSlip()"
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
      SELECT t1.*, t2.employee_name, t2.join_date, t2.gender, t2.salary_currency, t3.position_group, 0 as absence
        $strOtherAllowance $strOtherDeduction
      FROM (
        SELECT *
        FROM hrd_salary_detail WHERE id_salary_master = '$strDataID' 
      ) AS t1
      LEFT JOIN (
        SELECT id, employee_name, join_date, id_company, gender, salary_currency
        FROM hrd_employee WHERE 1=1 $strCriteria
      ) AS t2 ON t1.id_employee = t2.id
      LEFT JOIN (
        SELECT position_code, position_group
        FROM hrd_position 
      ) AS t3 ON t1.position_code= t3.position_code
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
    $strSQL .= " ORDER BY grouper, t2.employee_name";
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
    $rowDb['total_tax'] = $rowDb['tax'] + $rowDb['irregular_tax'];
    //      $rowDb['tax_allowance'] = $rowDb['total_tax'];
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
    $rowDb['jkk_deduction'] = $rowDb['jkk_allowance']; // sudah ditambah dengan tunjangannya
    $rowDb['jkm_deduction'] = $rowDb['jkm_allowance']; // sudah ditambah dengan tunjangannya
    $rowDb['jamsostek_deduction_jht'] = $rowDb['jamsostek_allowance']; // sudah ditambah dengan tunjangannya
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
    $intRound = (isset($objSalary->arrConf['salary_round']) && is_numeric(
            $objSalary->arrConf['salary_round']
        )) ? $objSalary->arrConf['salary_round'] : 1;
    $rowDb['fix_service_charge'] = $rowDb['alw_upl_sc'] + $rowDb['alw_upl_gm_sc']; // sudah ditambah dengan tunjangannya
    //Hardcode, never use this lines on standard package
    //----------------------
    $rowDb['total_gross'] = $objSalary->arrDetail[$rowDb['id_employee']]['total_gross'];// -$objSalary->arrDetail[$rowDb['id_employee']]['tax'] - $objSalary->arrDD['BPJS'][$rowDb['id_employee']]['amount'] - $objSalary->arrDetail[$rowDb['id_employee']]['jamsostek_deduction'] - $objSalary->arrDD['COS'][$rowDb['id_employee']]['amount'] - $objSalary->arrDA['tunjangan_jk'][$rowDb['id_employee']]['amount'];
    $rowDb['total_gross'] = roundMoney($rowDb['total_gross'], $intRound);
    $rowDb['cash_income'] = $rowDb['alw_tunjangan_kehadiran'] - $rowDb['ded_potongan_kehadiran'] - $rowDb['ded_potongan_cuti'];
    //$rowDb['transfer_income'] = $rowDb['total_gross'] - $rowDb['cash_income'];
    $rowDb['absence'] = $objSalary->arrDetail[$intID]['paid_absence_day'] + $objSalary->arrDetail[$intID]['unpaid_absence_day']; //----------------------
    $dataset[] = $rowDb;
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
    header("location:salary_calculation_hotel.php");
    exit();
  }
  $objSalary = new clsSalaryCalculationHotel($db, $strDataID); // cls_salary_calculation.php
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
    header("location:salary_calculation_hotel.php");
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
  $strCalculationMenu = "<b><a href='salary_calculation_hotel.php'>" . getWords(
          "salary calculation list"
      ) . "</a></b>";//getCalculationMenu($strDataID, 5, $intStatus);
  // ------ AMBIL DATA KRITERIA -------------------------
  (isset($_POST['dataEmployee'])) ? $strDataEmployee = $_POST['dataEmployee'] : $strDataEmployee = "";
  (isset($_POST['dataSalaryCurrency'])) ? $strDataSalaryCurrency = $_POST['dataSalaryCurrency'] : $strDataSalaryCurrency = "";
  (isset($_POST['dataCompany'])) ? $strDataCompany = $_POST['dataCompany'] : $strDataCompany = "";
  (isset($_POST['dataBranch'])) ? $strDataBranch = $_POST['dataBranch'] : $strDataBranch = "";
  (isset($_POST['dataDivision'])) ? $strDataDivision = $_POST['dataDivision'] : $strDataDivision = "";
  (isset($_POST['dataDepartment'])) ? $strDataDepartment = $_POST['dataDepartment'] : $strDataDepartment = "";
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
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee style=\"width:$strDefaultWidthPx\" maxlength=30 value=\"" . $strDataEmployee . "\" >";
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
  $strInputEmployeeType = "<select style=\"width:$strDefaultWidthPx\" name=dataEmployeeType id=dataEmployeeType ><option value=\"\"></option><option value=\"INA\">Karyawan Indonesia</option><option value=\"JPN\">Karyawan Jepang</option></select>";
  if ($strDataEmployeeType == "INA") {
    $strInputEmployeeType = "<select style=\"width:$strDefaultWidthPx\" name=dataEmployeeType id=dataEmployeeType ><option value=\"\"></option><option value=\"INA\" selected>Karyawan Indonesia</option><option value=\"JPN\">Karyawan Jepang</option></select>";
  } else if ($strDataEmployeeType == "JPN") {
    $strInputEmployeeType = "<select style=\"width:$strDefaultWidthPx\" name=dataEmployeeType id=dataEmployeeType ><option value=\"\"></option><option value=\"INA\">Karyawan Indonesia</option><option value=\"JPN\" selected>Karyawan Jepang</option></select>";
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
  $strHidden .= "<input type=hidden name=dataSalaryCurrency value=\"$strDataSalaryCurrency\">";
  $strHidden .= "<input type=hidden name=dataDivision value=\"$strDataDivision\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
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
  $strTemplateFile = getTemplate("salary_calculation_result_hotel.html");
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>