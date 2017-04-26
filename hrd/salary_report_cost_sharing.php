<?php
ini_set("display_errors", 1);
date_default_timezone_set('Asia/Jakarta');
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/model/model.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('form_object.php');
$dataPrivilege = getDataPrivileges(basename($_SERVER['PHP_SELF']), $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
    die(accessDenied($_SERVER['HTTP_REFERER']));
}
$db = new CDbClass();
$db->connect();
$f = new clsForm("form1", 1, "100%");
$f->disableFormTag();
$f->showCaption = false;
$f->showMinimizeButton = false;
$f->showCloseButton = false;

$f->addSelect(getWords("collector company"),
              "dataCollectorCompany",
              getDataListCompany(),
              '',
              "",
              true
);
$f->addSelect(getWords("collectible company"),
              "dataCollectibleCompany",
              getDataListCompany(),
              '',
              "",
              true
);
$f->addSelect(getWords('payroll month'),
              'dataPayrollMonth',
              getDataListSalary(),
              '',
              '',
              true);
$f->addSubmit("btnShow",
              "Show Report",
              ["onClick" => "return validInput();"],
              true,
              true,
              "",
              "",
              "");
$f->addSubmit("btnExportXLS",
              "Export Excel",
              'onClick = return validInput();',
              true,
              true,
              "",
              "",
              "");
$formInput = $f->render();

$totalData = 0;
$dataGrid = "";
$strInitAction = "";
$showReport = (isset($_POST['btnShow']) || isset($_POST['btnExportXLS']));

if (isset($showReport) && $showReport == '1') {
    $bolOK = TRUE;
    $strCompanyCollector = getRequestValue('dataCollectorCompany');
    $strCompanyCollectible = getRequestValue('dataCollectibleCompany');
    $strIDSalaryMaster = getRequestValue('dataPayrollMonth');

    if ($strCompanyCollector === $strCompanyCollectible) {
        $bolOK = FALSE;
    }
    if ($bolOK === TRUE) {
        $dataGrid = getData($db, $strCompanyCollector, $strCompanyCollectible, $strIDSalaryMaster);
    }
}


$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('salary report page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strWordsSalaryReport = getWords("salary report");
if ($bolPrint) {
    $strMainTemplate = getTemplate("employee_search_print.html");
} else {
    $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();

/**
 * Function to fetch a list of approved salary calculation.
 */
function getDataListSalary() {
    global $db;
    $intCount = 0;
    $strSQL = "SELECT t1.salary_date, t1.id, t2.company_name FROM hrd_salary_master AS t1
               LEFT JOIN hrd_company AS t2 ON t1.id_company = t2.id WHERE t1.status >= 2;";
    $res = $db->execute($strSQL);
    while($row = $db->fetchrow($res)) {
        $arrData[$intCount]['value'] = $row['id'];
        $arrData[$intCount]['text'] = 'Payroll - '.$row['company_name'].' - '.date('F Y', strtotime($row['salary_date'])).' ('.$row['salary_date'].')';
        $intCount++;
    }
    return $arrData;
}

/**
 * Function to check whether cost sharing calculation has been done or not.
 */
function getData($db, $strCompanyCollector, $strCompanyCollectible, $strIDSalaryMaster) {
    $strSQL = "SELECT COUNT(t1.id) AS id FROM hrd_salary_cost_sharing AS t1
               LEFT JOIN hrd_salary_master AS t2 ON t1.id_company_collectible = t2.id
               WHERE id_company_collector = $strCompanyCollector AND id_company_collectible = $strCompanyCollectible AND id_salary_master = $strIDSalaryMaster;";
    $res = $db->execute($strSQL);
    if ($row = $db->fetchrow($res)) {
        $strCountID = $row['id'];
    }
    $strSQL = "SELECT COUNT(id) AS id FROM hrd_salary_master WHERE id = $strIDSalaryMaster AND id_company = $strCompanyCollector;";
    $res = $db->execute($strSQL);
    if ($row = $db->fetchrow($res)) {
        $strCountIDSalaryMaster = $row['id'];
    }
    # If cost sharing has been done previously then fetch the data.
    if (isset($strCountID) && $strCountID > 0) {
        return getCostSharingData($db, $strCompanyCollector, $strCompanyCollectible, $strIDSalaryMaster);
    }
    # Else calculate it first and then fetch the data.
    else if (($strCountID <= 0 || $strCountID == NULL) && $strCountIDSalaryMaster == '1') {
        calculateCostSharing($db, $strCompanyCollector, $strCompanyCollectible, $strIDSalaryMaster);
        return getCostSharingData($db, $strCompanyCollector, $strCompanyCollectible, $strIDSalaryMaster);
    }
    else {
        return 'Payroll calculation for '.$strCompanyCollector.' has not been done';
    }
}

/**
 * Function to fetch cost sharing data from hrd_salary_cost_sharing.
 */
function getCostSharingData($db, $strCompanyCollector, $strCompanyCollectible, $strIDSalaryMaster) {
    global $dataPrivilege;

    $myDataGrid = new cDataGrid('formData', 'DataGrid1', '100%', '', true, false);

    $myDataGrid->addColumn(new DataGrid_Column(getWords('Employee ID'),
                                               'employee_id'
                                              )
                          );
    $myDataGrid->addColumn(new DataGrid_Column(getWords('Employee Name'),
                                               'employee_name'
                                              )
                          );
    $myDataGrid->addColumn(new DataGrid_Column(getWords('Company Penagih'),
                                               'company_collector'
                                              )
                          );
    $myDataGrid->addColumn(new DataGrid_Column(getWords('Company Tertagih'),
                                               'company_collectible'
                                              )
                          );
    $myDataGrid->addColumn(new DataGrid_Column(getWords('Take Home Pay'),
                                               'take_home_pay'
                                              )
                          );
    $myDataGrid->addColumn(new DataGrid_Column(getWords('Bulan Penggajian'),
                                               'payroll_month'
                                              )
                          );
    if (isset($_POST['btnExportXLS'])) {
        $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
        $myDataGrid->strFileNameXLS = "Salary_Cost_Sharing_Report.xls";
        $myDataGrid->strTitle1 = getWords("Salary Cost Sharing Report");
    }
    $myDataGrid->getRequest();
    $strSQLCOUNT = "SELECT COUNT(t1.id)
                    FROM hrd_salary_cost_sharing AS t1
                    WHERE t1.id_company_collector = $strCompanyCollector AND t1.id_company_collectible = $strCompanyCollectible
                    AND t1.id_salary_master = $strIDSalaryMaster;";
    $strSQL = "SELECT t1.*, t2.employee_name, t2.employee_id, t3.company_name AS company_collector, t4.company_name AS company_collectible, t5.salary_date
               FROM hrd_salary_cost_sharing AS t1
               LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
               LEFT JOIN (SELECT id, company_name FROM hrd_company WHERE id = $strCompanyCollector) AS t3 ON t3.id = t1.id_company_collector
               LEFT JOIN (SELECT id, company_name FROM hrd_company WHERE id = $strCompanyCollectible) AS t4 ON t4.id = t1.id_company_collectible
               LEFT JOIN hrd_salary_master AS t5 ON t5.id = t1.id_salary_master
               WHERE t1.id_company_collector = $strCompanyCollector AND t1.id_company_collectible = $strCompanyCollectible
               AND t1.id_salary_master = $strIDSalaryMaster";
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
    $dataSet = $myDataGrid->getData($db, $strSQL);
    foreach ($dataSet as $index => $array) {
        $dataSet[$index]['payroll_month'] = date('F Y', strtotime($array['salary_date']));
    }
    # Test.
    $myDataGrid->bind($dataSet);
    return $myDataGrid->render();
}
/**
 * Function to calculate cost sharing.
 */
function calculateCostSharing($db, $strCompanyCollector, $strCompanyCollectible, $strIDSalaryMaster) {
    # Get cost sharing percentage.
    $strSQL = "SELECT t1.id_company, t1.id_employee, t1.cost_percentage, t3.total_gross AS take_home_pay FROM hrd_employee_cost_sharing AS t1
               LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
               LEFT JOIN hrd_salary_detail AS t3 ON t3.id_employee = t1.id_employee
               WHERE t2.active = '1' AND t3.id_salary_master = '$strIDSalaryMaster' AND t1.id_company = '$strCompanyCollectible';";
    $res = $db->execute($strSQL);
    while($row = $db->fetchrow($res)) {
        $arrDetail[$row['id_employee']][$row['cost_percentage']] = $row['take_home_pay'];
    }

    # Calculate cost sharing percentage.
    $strSQL = "";
    foreach($arrDetail as $strIDEmployee => $arrCostAndTHP) {
        foreach($arrCostAndTHP as $intCostPercentage => $fltTHP) {
            if ($intCostPercentage > 0) {
                $fltTakeHomePay = ($intCostPercentage * $fltTHP)/100;
                # Insert into hrd_salary_cost_sharing.
                $strSQL .= "INSERT INTO hrd_salary_cost_sharing (id_company_collector, id_company_collectible, id_employee, take_home_pay, id_salary_master)
                            VALUES ($strCompanyCollector, $strCompanyCollectible, $strIDEmployee, $fltTakeHomePay, $strIDSalaryMaster);";
            }
        }
    }
    $res = $db->execute($strSQL);
}
?>
