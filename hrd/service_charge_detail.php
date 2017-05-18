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
        'gridList'        => '',
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
        'dataId'           => ['hidden', '', getPostValue('dataId')],
        'dataDateFrom'     => ['input', 'date from', null, $dateFieldAttr, 'date'],
        'dataDateThru'     => ['input', 'date thru', null, $dateFieldAttr, 'date'],
        'dataEmployee'     => ['input', 'employee id', null, ['size' => 30, 'maxlength' => 31]],
        'datacompany'      => ['select', 'company', null, ["cols" => 97, "rows" => 2]],
        'dataDivision'     => ['select', 'division', null, ["cols" => 97, "rows" => 2]],
        'dataDepartment'   => ['select', 'department', null, ["cols" => 97, "rows" => 2]],
        'dataSubDeparment' => ['select', 'subdeparment', null, ["cols" => 97, "rows" => 2]],
        'dataSection'      => ['select', 'section', 'weee', ["cols" => 97, "rows" => 2]],
        'dataSubSection'   => ['select', 'sub section', null, ["cols" => 97, "rows" => 2]],
        'btnShow'          => ['submit', 'show', 'showData()']
    ];
    return getBuildForm($formModel, $formOptions);
}

function getDataGrid()
{
    $strSql = 'SELECT
                    emp.employee_name,
                    scd."id",
                    scd.workday_employee,
                    scd.cost_employee,
                    cpy.company_name
                FROM
                    "public".hrd_service_charge_detail AS scd
                INNER JOIN "public".hrd_employee AS emp ON scd.employee_id = emp."id"
                INNER JOIN "public".hrd_company AS cpy ON emp.id_company = cpy."id"';
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
    $strAttrWidthChkID = ['align' => 'center', 'nowrap' => ''];
    $strAttrWidth = ['nowrap' => ''];
    $gridDataBinding = getDataGrid();
    $gridModel = [
        'id'               => ['checked', 'id', ['width' => '10'], $strAttrWidthChkID],
        'no'               => ['no', 'No.', '', ['width' => '10'], ['nowrap' => '']],
        'employee_name'    => ['data', 'Employee Name', 'employee_name', $strTitleAttrWidth, $strAttrWidth],
        'workday_employee' => ['data', 'Work Day', 'workday_employee', $strTitleAttrWidth, $strAttrWidth],
        'cost_employee'    => ['data', 'Cost Employee', 'cost_employee', $strTitleAttrWidth, $strAttrWidth],
        'company_name'     => ['data', 'Company', 'company_name', $strTitleAttrWidth, $strAttrWidth],
        'btnDelete'        => ['submit', 'Delete', 'submit', 'getDeleteData()'],
        'btnApproved'      => ['submit', 'Approved', 'submit', 'getDeleteData()'],
        'ServiceCharge'    => ['exportExl', 'Export Excel']
    ];
    return getBuildGrid($gridModel, $gridOptions, $gridDataBinding);
}

function showData()
{
}