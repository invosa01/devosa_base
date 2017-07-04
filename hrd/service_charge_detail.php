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
    if ($privileges['bolView'] !== true) {
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
        'gridList'        => '',
        'gridTitle'       => getWords('LIST SERVICE CHARGE DETAIL')
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
    $gridContents = getGridObject($gridOptions);
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
    $selectAttr = ["cols" => 97, "rows" => 2];
    $formModel = [
        'dataId'         => ['hidden', '', getPostValue('dataId')],
        'dataDateFrom'   => ['input', 'date from', null, $dateFieldAttr, 'date'],
        'dataDateThru'   => ['input', 'date thru', null, $dateFieldAttr, 'date'],
        'dataEmployee'   => ['input', 'employee id', null, ['size' => 30, 'maxlength' => 31]],
        'dataDivision'   => ['select', 'division', ['hrd_division', 'division_code', 'division_name'], $selectAttr],
        'dataDepartment' => ['select', 'department', ['hrd_division', 'division_code', 'division_name'], $selectAttr],
        'btnShow'        => ['submit', 'show', 'getRenderGrid()']
    ];
    return getBuildForm($formModel, $formOptions);
}

function getQuery($strSQL, array $wheres = [])
{
    if (count($wheres) > 0) {
        $strSQL .= ' WHERE ' . implodeArray($wheres, ' AND ');
    }
    return $strSQL;
}

function getExtraOffListQuery(array $wheres = [])
{
    $strSql = 'SELECT
                    emp.employee_id,
                    emp.employee_name,
                    emp.position_code,
                    emp.division_code,
                    emp.department_code,
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
    return getQuery($strSql, $wheres);
}

function getGridModelData()
{
    $wheres = [];
    return pgFetchRows(getExtraOffListQuery($wheres));
}

function getGridObject(array $gridOptions = [])
{
    $defaultColHeadAttr = ['width' => '400'];
    $defaultColContentAttr = ['nowrap' => ''];
    $gridButtons = [];
    $defaultGridOptions = [
        'formName'          => 'frmServiceChargeGrid',
        'gridName'          => 'serviceChargeGrid',
        'gridWidth'         => '100%',
        'gridHeight'        => '100%',
        'showPageLimit'     => true,
        'showSearch'        => true,
        'showSort'          => true,
        'showPageNumbering' => true,
        'path'              => null,
        'buttons'           => $gridButtons,
        'calledFile'        => basename($_SERVER['PHP_SELF'])
    ];
    $modelData = getGridModelData();
    $columnHeader = [
        'id'               => ['ID', ['width' => '10']],
        'no'               => ['No.', ['width' => '10']],
        'employee_id'      => ['Employee Id', $defaultColHeadAttr],
        'employee_name'    => ['Date From', $defaultColHeadAttr],
        'position_code'    => ['Date Thru', $defaultColHeadAttr],
        'join_date'        => ['Join Date', $defaultColHeadAttr],
        'workday_employee' => ['Work Employee', $defaultColHeadAttr],
        'cost_employee'    => ['Cost Employee', $defaultColHeadAttr]
    ];
    $columnContent = [
        'id'               => ['id', $defaultColContentAttr, 'checkbox'],
        'no'               => ['no', ['nowrap' => ''], 'auto'],
        'employee_id'      => ['employee_id', $defaultColContentAttr],
        'employee_name'    => ['employee_name', $defaultColContentAttr],
        'position_code'    => ['position_code', $defaultColContentAttr],
        'join_date'        => ['join_date', $defaultColContentAttr],
        'workday_employee' => ['workday_employee', $defaultColContentAttr],
        'cost_employee'    => ['cost_employee', $defaultColContentAttr]
    ];
    $columnSet = ['head' => $columnHeader, 'content' => $columnContent];
    return getBuildDataGrid($modelData, $columnSet, getMergedArrayRecursively($defaultGridOptions, $gridOptions));
}