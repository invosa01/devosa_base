<?php
require_once 'global.php';
loadStandardCore();
doIncludes(
    [
        'includes/form2/form2.php',
        'includes/datagrid2/datagrid.php',
        'classes/hrd/hrd_shift_change.php',
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
        'strPageDesc'     => getWords("shift change list management"),
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
    $formOptions = [
        'column'     => 2,
        'caption'    => strtoupper($strWordsFILTERDATA),
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
    $selectAttr = ["cols" => 97, "rows" => 2];
    $formModel = [
        'dataId'         => ['hidden', '', getPostValue('dataId')],
        'dataDateFrom'   => ['input', 'date from', null, $dateFieldAttr, 'date'],
        'dataDivision'   => ['select', 'division', ['hrd_division', 'division_code', 'division_name'], $selectAttr],
        'dataDepartment' => ['select', 'department', ['hrd_division', 'division_code', 'division_name'], $selectAttr],
        'btnShow'        => ['submit', 'show', 'getRenderGrid()']
    ];
    return getBuildForm($formModel, $formOptions);
}

function getRenderGrid()
{
    global $formObject;
    $model = [
    ];
    return $model;
}

function getQuery($strSQL, array $wheres = [])
{
    if (count($wheres) > 0) {
        $strSQL .= ' WHERE ' . implodeArray($wheres, ' AND ');
    }
    return $strSQL;
}

function getDataGrid()
{
    $model = [];
    $wheres = [];
    $strSql = 'SELECT
                    shc."id",
                    shc.shift_date,
                    sht.code AS code1,
                    sht1.code AS code2,
                    shc.status,
                    shc.note
                FROM
                    "public".hrd_shift_change AS shc
                LEFT JOIN "public".hrd_shift_type AS sht ON shc.current_shift = sht."id"
                LEFT JOIN "public".hrd_shift_type AS sht1 ON shc.proposed_shift = sht1."id"';
    $strSqlCount = 'SELECT
                        COUNT(shc."id") AS total
                    FROM
                        "public".hrd_shift_change AS shc';
    $strSql = pgFetchRows(getQuery($strSql, $wheres));
    $strSqlCount = getQuery($strSqlCount, $wheres);
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
        'id'            => ['checked', 'id', 'id', ['width' => '10'], ['nowrap' => '']],
        'no'            => ['no', 'No.', '', ['width' => '10'], ['nowrap' => '']],
        'shift_date'    => ['data', 'Shift Date', 'shift_date', $strTitleAttrWidth, $strAttrWidth],
        'code1'         => ['data', 'Current Shift', 'code1', $strTitleAttrWidth, $strAttrWidth],
        'code2'         => ['data', 'Proposed Shift', 'code2', $strTitleAttrWidth, $strAttrWidth],
        'status'        => ['data', 'Status', 'status', $strTitleAttrWidth, $strAttrWidth],
        'note'          => ['data', 'Note', 'note', $strTitleAttrWidth, $strAttrWidth],
        'role'          => ['role', 'Button', '', ['edit', 'delete', false, 'approve', false]],
        'ServiceCharge' => ['exportExl', 'Export Excel']
    ];
    return getBuildGrid($gridModel, $gridOptions, $gridDataBinding);
}

function deleteData()
{
    /**
     * @var \cDataGrid $gridContents
     */
    global $gridContents;
    $arrId = [];
    foreach ($gridContents->checkboxes as $value) {
        $arrId['id'][] = $value;
    }
}