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
main();
function extractToGlobal(array $globalVars = [])
{
    foreach ($globalVars as $var => $value) {
        $GLOBALS[$var] = $value;
    }
}

function main()
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
        'formContents'    => '',
        'gridObject'      => null,
        'gridContents'    => '',
        'gridTitle'       => getWords('LIST SHIFT CHANGE')
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
    $formContents = $formObject->render();
    # Get grid object contents.
    $gridOptions = ['caption' => strtoupper($strWordsLISTOF . " " . getWords($privileges['menu_name']))];
    $gridObject = getGridObject($gridOptions);
    $gridContents = $gridObject->render();
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

function getShiftChangeListQuery(array $wheres = [])
{
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
    return getQuery($strSql, $wheres);
}

function getQuery($strSQL, array $wheres = [])
{
    if (count($wheres) > 0) {
        $strSQL .= ' WHERE ' . implodeArray($wheres, ' AND ');
    }
    return $strSQL;
}

function getGridModelData()
{
    $wheres = [];
    return pgFetchRows(getShiftChangeListQuery(), $wheres);
}

function getGridObject(array $gridOptions = [])
{
    $defaultColHeadAttr = ['width' => '400'];
    $defaultColContentAttr = ['nowrap' => ''];
    $gridButtons = [
        'btnDelete'   => ['delete', 'Delete', 'onClick="javascript:return myClient.confirmDelete();"', 'deleteData()'],
        'btnApproved' => ['approve', 'Approve', 'onClick="javascript:return myClient.confirmAppoved();"', 'changeStatus()']
    ];
    $defaultGridOptions = [
        'formName'          => 'frmShiftChangeGrid',
        'gridName'          => 'shiftChangeGrid',
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
        'id'         => ['ID', ['width' => '10']],
        'no'         => ['No.', ['width' => '10']],
        'shift_date' => ['Shift Date', $defaultColHeadAttr],
        'code1'      => ['Current Shift', $defaultColHeadAttr],
        'code2'      => ['Proposed Shift', $defaultColHeadAttr],
        'status'     => ['Status', $defaultColHeadAttr],
        'note'       => ['Note', $defaultColHeadAttr],
    ];
    $columnContent = [
        'id'         => ['id', $defaultColContentAttr, 'checkbox'],
        'no'         => ['no', ['nowrap' => ''], 'auto'],
        'shift_date' => ['shift_date', $defaultColContentAttr],
        'code1'      => ['code1', $defaultColContentAttr],
        'code2'      => ['code2', $defaultColContentAttr],
        'status'     => ['status', $defaultColContentAttr],
        'note'       => ['note', $defaultColContentAttr],
    ];
    $columnSet = ['head' => $columnHeader, 'content' => $columnContent];
    return getBuildDataGrid($modelData, $columnSet, getMergedArrayRecursively($defaultGridOptions, $gridOptions));
}

function deleteData()
{
    /**
     * @var \cDataGrid $dataGridObj
     */
    global $dataGridObj;
    //$gridObject = unserialize(getFlashMessage('shiftChangeGrid'), true);
    $arrId = [];
    foreach ($dataGridObj->checkboxes as $value) {
        $arrId['id'][] = $value;
    }
    $dataHrdShiftChange = new cHrdShiftChange();
    $dataHrdShiftChange->deleteMultiple($arrId);
    $dataGridObj->message = $dataHrdShiftChange->strMessage;
    //setFlashMessage($gridName, serialize($dataGridObj));
}

function changeStatus()
{
    /**
     * @var \cDataGrid $dataGridObj
     */
    //$gridObject = unserialize(getFlashMessage('shiftChangeGrid'), true);
    global $dataGridObj;
    $schId = [];
    foreach ($dataGridObj->checkboxes as $value) {
        $schId = ['id' => $value];
    }
    $varDataStatus = ['status' => REQUEST_STATUS_APPROVED];
    $dataHrdShiftChange = new cHrdShiftChange();
    $dataHrdShiftChange->update($schId, $varDataStatus);
    $dataGridObj->message = $dataHrdShiftChange->strMessage;
}
