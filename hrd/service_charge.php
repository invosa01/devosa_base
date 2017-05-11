<?php
require_once 'global.php';
loadStandardCore();
doIncludes(
    [
        'includes/form2/form2.php',
        'includes/datagrid2/datagrid.php',
        'classes/hrd/hrd_service_charge.php',
        'classes/hrd/hrd_service_charge_detail.php',
        'src/Helper/FormHelper.php',
        'src/System/Postfix.php'
    ]
);
setActiveDbConnection('dbConnection', getPgConnection(DB_NAME, DB_USER, DB_PWD, DB_SERVER, DB_PORT));
renderPage();
function getServiceChargeConfig()
{
    $serviceCharge = 0.1;
    $lossAndBreakage = 0.05;
    $socialWelfare = 0.02;
    return [
        'SCCostFormula'   => $serviceCharge . '*REV',
        'totalSCFormula'  => 'SC-(' . $lossAndBreakage . '*SC+' . $socialWelfare . '*SC)',
        'SCPerEmpFormula' => '(TOTSC/TOTWD)*WD'
    ];
}

function getServiceChargeData($startDate, $endDate)
{
    $totalWorkDays = 0;
    $strSql = 'SELECT
                    atd.id_employee,
                    COUNT (
                        DISTINCT (atd.attendance_date)
                    ) AS "workDay"
                FROM
                    "public".hrd_attendance AS atd
                WHERE
                    atd.attendance_date >= ' . pgEscape($startDate) . ' AND atd.attendance_date <= ' . pgEscape(
            $endDate
        ) . '
                GROUP BY
                    atd.id_employee';
    $workDaysPerEmp = pgFetchRows($strSql);
    foreach ($workDaysPerEmp as $row) {
        $totalWorkDays += $row['workDay'];
    }
    return [
        'workDaysPerEmp' => $workDaysPerEmp,
        'totalWorkDays'  => $totalWorkDays
    ];
}

function extractToGlobal(array $globalVars = [])
{
    foreach ($globalVars as $var => $value) {
        $GLOBALS[$var] = $value;
    }
}

function renderPage()
{
    # Setting up and process the privileges.
    $calledFile = basename($_SERVER['PHP_SELF']);
    $privileges = getDataPrivileges($calledFile);
    if ($privileges['bolView'] === false) {
        die(accessDenied($_SERVER['HTTP_REFERER']));
    }
    # Initialize all global variables.
    $globalVariables = [
        'privileges'         => $privileges,
        'strWordsDateFrom'   => getWords("date from"),
        'strWordsDateThru'   => getWords("date thru"),
        'strWordsEmployeeID' => getWords("employee id"),
        'strWordsShow'       => getWords("show"),
        'strConfirmSave'     => getWords("save"),
        'strConfirmDelete'   => getWords("delete"),
        'strDataDetail'      => "",
        'strHidden'          => "",
        'intTotalData'       => 0,
        'strButtons'         => "",
        'strButtonsTop'      => "",
        'strConfirmSave'     => getWords("do you want to save this entry?"),
        'strPageTitle'       => getWords($privileges['menu_name']),
        'pageIcon'           => "../images/icons/" . $privileges['icon_file'],
        'strPageDesc'        => getWords("Service Charge management"),
        'pageHeader'         => '',
        'strTemplateFile'    => getTemplate(str_replace(".php", ".html", $calledFile)),
        'formObject'         => null,
        'formInput'          => '',
        'DataGrid'           => ''
    ];
    extractToGlobal($globalVariables);
    # Important to given access to our global variables.
    foreach (array_keys($GLOBALS) as $varName) {
        global $$varName;
    }
    $pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
    # Get form model contents.
    $formOptions = ['column' => 1, 'caption' => strtoupper($strWordsINPUTDATA), 'references' => ['dataId']];
    $formObject = getFormObject($formOptions);
    $formInput = $formObject->render();
    # Get grid list contents.
    $gridOptions = [];
    $DataGrid = getGridListContents($gridOptions);
    # Start to render using tiny but strong class.
    $tbsPage = new clsTinyButStrong;
    $tbsPage->LoadTemplate($strMainTemplate);
    $tbsPage->Show();
}

function getFormObject(array $formOptions = [])
{
    global $strDateWidth;
    $dateFieldAttr = ["style" => "width:$strDateWidth"];
    $btnSubmitAttr = ["onClick" => "javascript:myClient.confirmSave();"];
    $formModel = [
        'dataId'              => ['hidden', '', getPostValue('dataId')],
        'dataDateFrom'        => ['input', 'date from', null, $dateFieldAttr, 'date'],
        'dataDateThru'        => ['input', 'date thru', null, $dateFieldAttr, 'date'],
        'dataCalculationDate' => ['input', 'calculation date', null, array_merge($dateFieldAttr, ['disabled']), 'date'],
        'dataRevenue'         => ['input', 'revenue', null, ['size' => 30, 'maxlength' => 31, 'required']],
        'btnSave'             => ['submit', 'start calculation', 'saveData()', $btnSubmitAttr]
    ];
    return getBuildForm($formModel, $formOptions);
}

function getGridListContents(array $datagridOptions = [])
{
    global $privileges, $strWordsLISTOF;
    $db = new CdbClass;
    $myDataGrid = new cDataGrid("formData", "DataGrid1");
    $myDataGrid->caption = strtoupper($strWordsLISTOF . " " . getWords($privileges['menu_name']));
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no."), "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Calculation Date"), "date_calculation", ['width' => '150'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Date From"), "date_from", ['width' => '200'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Date Thru"), "date_thru", ['width' => '200'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("Amount"), "amount", null, ['nowrap' => '']));
    $myDataGrid->getRequest();
    $strSql = 'SELECT
                    sch.date_calculation,
                    sch.date_from,
                    sch.date_thru,
                    sch.amount
                FROM
                    "public".hrd_service_charge AS sch';
    $dataset = $myDataGrid->getData($db, $strSql);
    $strSqlCount = 'SELECT
                        "count" (*)
                    FROM
                        "public".hrd_service_charge AS sch';
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSqlCount);
    $myDataGrid->bind($dataset);
    return $myDataGrid->render();
}

function getValidationInputDate($startDate, $endDate)
{
    $existDate = true;
    $strSql = 'SELECT
                    "count" (*)
                FROM
                    "public".hrd_service_charge AS sch
                WHERE
                   sch.date_from BETWEEN   ' . pgEscape($startDate) . ' AND  ' . pgEscape($endDate) . '
                GROUP BY 
                    sch.id';
    $validationDate = pgFetchRow($strSql);
    if (($validationDate > 0) === true) {
        $existDate = false;
    }
    return [
        'existDate' => $existDate
    ];
}

function saveData()
{
    /**
     * @var \clsForm $formObject
     */
    global $formObject;
    $result = true;
    $dataHrdServiceCharge = new cHrdServiceCharge();
    $dataHrdServiceChargeDetail = new cHrdServiceChargeDetail();
    $startDate = $formObject->getValue('dataDateFrom');
    $startDate = \DateTime::createFromFormat('d-m-Y', $startDate)->format('Y-m-d');
    $endDate = $formObject->getValue('dataDateThru');
    $endDate = \DateTime::createFromFormat('d-m-Y', $endDate)->format('Y-m-d');
    $totalRevenue = (float)$formObject->getValue('dataRevenue');
    $model = [
        'date_from'        => $startDate,
        'date_thru'        => $endDate,
        'date_calculation' => $formObject->getValue('dataCalculationDate'),
        'amount'           => $totalRevenue,
    ];
    # Load service charge model.
    $validationDate = getValidationInputDate($model['date_from'], $model['date_thru']);
    $scData = getServiceChargeData($model['date_from'], $model['date_thru']);
    # Load service charge configuration.
    $scConfig = getServiceChargeConfig();
    # Start to calculate the service charge cost per employee.
    $empScData = $scData['workDaysPerEmp'];
    $totalWorkDays = $scData['totalWorkDays'];
    $postfixModel['REV'] = $totalRevenue;
    $scCost = getEvaluatedMathExpressionValue($scConfig['SCCostFormula'], $postfixModel);
    $postfixModel['SC'] = $scCost;
    $totalSCCost = getEvaluatedMathExpressionValue($scConfig['totalSCFormula'], $postfixModel);
    $postfixModel['TOTSC'] = $totalSCCost;
    $postfixModel['TOTWD'] = $totalWorkDays;
    # Start to process updating database.
    if ($formObject->isInsertMode() === true) {
        # Insert master data for service charge
        if (($existDate = $validationDate['existDate']) === true) {
            if (($result = $dataHrdServiceCharge->insert($model)) === true) {
                $scId = $dataHrdServiceCharge->getLastInsertId();
                foreach ($empScData as $row) {
                    $postfixModel['WD'] = $row['workDay'];
                    $scCostPerEmpoyee = getEvaluatedMathExpressionValue($scConfig['SCPerEmpFormula'], $postfixModel);
                    $detailModel = [
                        'service_charge_id' => $scId,
                        'employee_id'       => $row['id_employee'],
                        'workday_employee'  => $row['workDay'],
                        'cost_employee'     => $scCostPerEmpoyee
                    ];
                    # Insert into detail service charge.
                    if ($dataHrdServiceChargeDetail->insert($detailModel) === false) {
                        $result = false;
                    }
                }
            }
        }
        $formObject->message = $dataHrdServiceCharge->strMessage;
    }
}