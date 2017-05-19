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
        'privileges'      => $privileges,
        'strPageTitle'    => getWords($privileges['menu_name']),
        'pageIcon'        => "../images/icons/" . $privileges['icon_file'],
        'strPageDesc'     => getWords("Service Charge List management"),
        'pageHeader'      => '',
        'strTemplateFile' => getTemplate(str_replace(".php", ".html", $calledFile)),
        'formObject'      => null,
        'formInput'       => '',
        'gridContents'    => null,
        'gridList'        => ''
    ];
    extractToGlobal($globalVariables);
    # Important to given access to our global variables.
    foreach (array_keys($GLOBALS) as $varName) {
        global $$varName;
    }
    $pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
    # Get form model contents.
    $formOptions = ['column' => 2, 'caption' => strtoupper($strWordsFILTERDATA), 'references' => ['dataId']];
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
    $formModel = [
        'dataId'         => ['hidden', '', getPostValue('dataId')],
        'dataDateFrom'   => ['input', 'date from', null, $dateFieldAttr, 'date'],
        'dataDateThru'   => ['input', 'date thru', null, $dateFieldAttr, 'date'],
        'dataEmployee'   => ['input', 'employee id', null, ['size' => 30, 'maxlength' => 31]],
        'dataDivision'   => ['select', 'division', null, ["cols" => 97, "rows" => 2]],
        'dataDepartment' => ['select', 'department', null, ["cols" => 97, "rows" => 2]],
        'btnShow'        => ['submit', 'show', 'getRenderGrid()']
    ];
    return getBuildForm($formModel, $formOptions);
}

function getRenderGrid()
{
    global $formObject;
    $startDate = $formObject->getValue('dataDateFrom');
    $startDate = \DateTime::createFromFormat('d-m-Y', $startDate)->format('Y-m-d');
    $endDate = $formObject->getValue('dataDateThru');
    $endDate = \DateTime::createFromFormat('d-m-Y', $endDate)->format('Y-m-d');
    $model = [
        'date_from'       => $startDate,
        'date_thru'       => $endDate,
        'employee_id'     => $formObject->getValue('dataEmployee'),
        'division_code'   => $formObject->getValue('dataDivision'),
        'department_code' => $formObject->getValue('dataDepartment'),
    ];
    return $model;
}

function getQuery($strSql, array $wheres = [])
{
    if (count($wheres) > 0) {
        $strSql .= ' WHERE ' . implodeArray($wheres, ' AND ');
    }
    return $strSql;
}

function getDataGrid()
{
    $model = [];
    $wheres = [];
    $employeeId = null;
    $divisionCode = null;
    $dateFrom = null;
    $dateThru = null;
    $renderGrid = getRenderGrid($model);
    if (array_key_exists('date_from', $renderGrid) === true) {
        $dateFrom = $renderGrid['date_from'];
    }
    if (array_key_exists('date_thru', $renderGrid) === true) {
        $dateThru = $renderGrid['date_thru'];
    }
    if (array_key_exists('employee_id', $renderGrid) === true) {
        $employeeId = $renderGrid['employee_id'];
    }
    if (array_key_exists('division_code', $renderGrid) === true) {
        $divisionCode = $renderGrid['division_code'];
    }
    $strSql = 'SELECT
                    emp.employee_id,
                    emp.employee_name,
                    emp.position_code,
                    emp.division_code,
                    emp.join_date,
                    sce.date_from,
	                sce.date_thru,
                    scd."id",
                    scd.workday_employee,
                    scd.cost_employee,
                    cpy.company_name
                FROM
                    "public".hrd_service_charge_detail AS scd
                INNER JOIN "public".hrd_service_charge AS sce ON scd.service_charge_id = sce."id"
                INNER JOIN "public".hrd_employee AS emp ON scd.employee_id = emp."id"
                INNER JOIN "public".hrd_company AS cpy ON emp.id_company = cpy."id"';
    if ($dateFrom !== '' and $dateThru !== '') {
        $wheres[] = 'sce.date_from BETWEEN ' . pgEscape($dateFrom) . ' AND ' . pgEscape($dateThru);
    }
    if ($employeeId !== '') {
        $wheres[] = 'emp.employee_id = ' . pgEscape($employeeId);
    }
    if ($divisionCode !== '') {
        $wheres[] = 'emp.division_code = ' . pgEscape($divisionCode);
    }
    $strSql = getQuery($strSql, $wheres);
    $strSqlCount = 'SELECT
                        "count" (*)
                    FROM
                         "public".hrd_service_charge_detail AS scd';
    return [
        'strSql'      => $strSql,
        'strSqlCount' => $strSqlCount
    ];
}

function getGridListContents(array $gridOptions = [])
{
    $strTitleAttrWidth = ['width' => '400'];
    $strAttrWidth = ['nowrap' => ''];
    $gridDataBinding = getDataGrid();
    $gridModel = [
        'no'               => ['no', 'No.', '', ['width' => '10'], ['nowrap' => '']],
        'employee_id'      => ['data', 'Employee Id', 'employee_id', $strTitleAttrWidth, $strAttrWidth],
        'employee_name'    => ['data', 'Employee Name', 'employee_name', $strTitleAttrWidth, $strAttrWidth],
        'position_code'    => ['data', 'Position', 'position_code', $strTitleAttrWidth, $strAttrWidth],
        'join_date'        => ['data', 'Join Date', 'join_date', $strTitleAttrWidth, $strAttrWidth],
        'workday_employee' => ['data', 'Days', 'workday_employee', $strTitleAttrWidth, $strAttrWidth],
        'cost_employee'    => ['data', 'Nett / Employee', 'cost_employee', $strTitleAttrWidth, $strAttrWidth],
        'ServiceCharge'    => ['exportExl', 'Export Excel']
    ];
    return getBuildGrid($gridModel, $gridOptions, $gridDataBinding);
}