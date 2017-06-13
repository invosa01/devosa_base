<?php
require_once 'global.php';
loadStandardCore();
doIncludes(
    [
        'includes/form2/form2.php',
        'includes/datagrid2/datagrid.php',
        'src/Helper/FormHelper.php',
        'src/Helper/GridHelper.php'
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
        'strPageDesc'     => getWords("extra off management"),
        'pageHeader'      => '',
        'strTemplateFile' => getTemplate(str_replace(".php", ".html", $calledFile)),
        'formObject'      => null,
        'formInput'       => '',
        'gridContents'    => null,
        'gridList'        => '',
        'gridTitle'       => getWords('LIST EXTRA OFF QUOTA')
    ];
    extractToGlobal($globalVariables);
    # Important to given access to our global variables.
    foreach (array_keys($GLOBALS) as $varName) {
        global $$varName;
    }
    $pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
    # Get form model contents.
    $formOptions = [
        'column'     => 2,
        'caption'    => strtoupper($strWordsFILTERDATA),
        'references' => ['dataId']
    ];
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
        'dataId'       => ['hidden', '', getPostValue('dataId')],
        'dataEmployee' => ['input', 'employee', null, ['size' => 30, 'maxlength' => 31]],
        'dataDateEo'   => ['input', 'date extra off', null, $dateFieldAttr, 'date'],
        'dataType'     => ['select', 'type', ['hrd_shift_type', 'id', 'code'], $selectAttr],
        'dataActive'   => ['checkbox', 'active', null, $selectAttr],
        'btnShow'      => ['submit', 'show', 'getGridModelData()'],
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

function getExtraOffQuotaListQuery(array $wheres = [])
{
    $strSql = 'SELECT
                    eoa.employee_id,
                    emp.employee_name,
                    sht.code,
                    eoa.date_eo,
                    eoa.date_expired,
                    eoc.eo_level_code,
                    eoa.note,
                    eoa.active
                FROM
                    "public".hrd_eo_quota AS eoa
                INNER JOIN "public".hrd_employee AS emp ON eoa.employee_id = emp."id"
                INNER JOIN "public".hrd_eo_conf AS eoc ON eoa."type" = eoc."id"
                INNER JOIN "public".hrd_shift_type AS sht ON eoc.shift_type_id = sht."id"';
    return getQuery($strSql, $wheres);
}

function getGridModelData()
{
    $wheres = [];
    return pgFetchRows(getExtraOffQuotaListQuery($wheres));
}

function getGridObject(array $gridOptions = [])
{
    $defaultColHeadAttr = ['width' => '400'];
    $defaultColContentAttr = ['nowrap' => ''];
    $gridButtons = [];
    $defaultGridOptions = [
        'formName'          => 'frmExtraOffQuotaGrid',
        'gridName'          => 'extraOffQuotaGrid',
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
        'id'            => ['ID', ['width' => '10']],
        'no'            => ['No.', ['width' => '10']],
        'employee_name' => ['Employee Name', $defaultColHeadAttr],
        'date_eo'       => ['Date Extra Off', $defaultColHeadAttr],
        'date_eo'       => ['Date Extra Off', $defaultColHeadAttr],
        'code'          => ['Type', $defaultColHeadAttr],
        'eo_level_code' => ['Extra Off Level Code', $defaultColHeadAttr],
    ];
    $columnContent = [
        'id'            => ['id', $defaultColContentAttr, 'checkbox'],
        'no'            => ['no', ['nowrap' => ''], 'auto'],
        'employee_name' => ['employee_name', $defaultColContentAttr],
        'date_eo'       => ['date_eo', $defaultColContentAttr],
        'code'          => ['code', $defaultColContentAttr],
        'eo_level_code' => ['eo_level_code', $defaultColContentAttr],
    ];
    $columnSet = ['head' => $columnHeader, 'content' => $columnContent];
    return getBuildDataGrid($modelData, $columnSet, getMergedArrayRecursively($defaultGridOptions, $gridOptions));
}