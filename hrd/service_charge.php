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
        'src/Helper/GridHelper.php',
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
    if ($privileges['bolView'] !== true) {
        die(accessDenied($_SERVER['HTTP_REFERER']));
    }
    # Initialize all global variables.
    $globalVariables = [
        'privileges'                 => $privileges,
        'strConfirmStartCalculation' => getWords("do you want to start calculation?"),
        'strPageTitle'               => getWords($privileges['menu_name']),
        'pageIcon'                   => "../images/icons/" . $privileges['icon_file'],
        'strPageDesc'                => getWords("Service Charge management"),
        'pageHeader'                 => '',
        'strTemplateFile'            => getTemplate(str_replace(".php", ".html", $calledFile)),
        'formObject'                 => null,
        'formInput'                  => '',
        'gridContents'               => null,
        'gridList'                   => '',
    ];
    extractToGlobal($globalVariables);
    # Important to given access to our global variables.
    foreach (array_keys($GLOBALS) as $varName) {
        global $$varName;
    }
    $pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
    # Get form model contents.
    $formOptions = [
        'column'     => 1,
        'caption'    => strtoupper($strWordsINPUTDATA),
        'references' => ['dataId']
    ];
    $formObject = getFormObject($formOptions);
    $formInput = $formObject->render();
    # Get grid list contents.
    $gridOptions = ['caption' => strtoupper($strWordsLISTOF . " " . getWords($privileges['menu_name']))];
    $gridContents = getGridListContents($gridOptions);
    $gridList = $gridContents->render();
    # Start to render using tiny but strong class.
    $tbsPage = new clsTinyButStrong;
    $tbsPage->LoadTemplate($strMainTemplate);
    $tbsPage->Show();
}

function getFormObject(array $formOptions = [])
{
    global $strDateWidth;
    $dateFieldAttr = ["style" => "width:$strDateWidth"];
    $btnSaveAttr = ["onClick" => "javascript:myClient.confirmStartCalculation();"];
    $formModel = [
        'dataId'              => ['hidden', '', getPostValue('dataId')],
        'dataDateFrom'        => ['input', 'date from', null, $dateFieldAttr, 'date'],
        'dataDateThru'        => ['input', 'date thru', null, $dateFieldAttr, 'date'],
        'dataCalculationDate' => ['input', 'calculation date', null, array_merge($dateFieldAttr, ['disabled']), 'date'],
        'dataRevenue'         => ['input', 'revenue', null, ['size' => 30, 'maxlength' => 31, 'required']],
        'btnSave'             => ['submit', 'start calculation', 'getSaveData()', $btnSaveAttr]
    ];
    return getBuildForm($formModel, $formOptions);
}

function getDataGrid()
{
    $strSql = 'SELECT
                    sch."id",
                    sch.date_calculation,
                    sch.date_from,
                    sch.date_thru,
                    sch.amount
                FROM
                    "public".hrd_service_charge AS sch';
    $strSqlCount = 'SELECT
                        "count" (*)
                    FROM
                        "public".hrd_service_charge AS sch';
    $strSql = pgFetchRows($strSql);
    return [
        'strSql'      => $strSql,
        'strSqlCount' => $strSqlCount
    ];
}

function getGridListContents(array $gridOptions = [])
{
    $strTitleAttrWidth = ['width' => '400'];
    $strAttrWidth = ['nowrap' => ''];
    $strAttrExport = array_merge($strAttrWidth, ['showInExcel']);
    $btnSaveAttr = '"onClick" => "javascript:myClient.confirmStartCalculation();"';
    $gridDataBinding = getDataGrid();
    $gridModel = [
        'no'               => ['no', 'No.', '', ['width' => ''], $strAttrWidth],
        'date_calculation' => ['data', 'Calculation Date', 'date_calculation', $strTitleAttrWidth, $strAttrWidth],
        'date_from'        => ['data', 'Date From', 'date_from', $strTitleAttrWidth, $strAttrWidth],
        'date_thru'        => ['data', 'Date Thru', 'date_thru', $strTitleAttrWidth, $strAttrWidth],
        'amount'           => ['data', 'Amount', 'amount', ['width' => ''], $strAttrWidth],
        'id'               => ['data', '', 'id', ['width' => ''], $strAttrExport, '', 'getEditData()'],
    ];
    return getBuildGrid($gridModel, $gridOptions, $gridDataBinding);
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

function getEditData()
{
    return "<a href=''>" . getWords('edit') . "</a>";
}

function getSaveData()
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
    $calculationDate = $formObject->getValue('dataCalculationDate');
    $calculationDate = \DateTime::createFromFormat('d-m-Y', $calculationDate)->format('Y-m-d');
    $totalRevenue = (float)$formObject->getValue('dataRevenue');
    $model = [
        'date_from'        => $startDate,
        'date_thru'        => $endDate,
        'date_calculation' => $calculationDate,
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
        if (($existDate = $validationDate['existDate']) === true) {
            if (($result = $dataHrdServiceCharge->insert($model)) === true) {
                # Insert master data for service charge
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