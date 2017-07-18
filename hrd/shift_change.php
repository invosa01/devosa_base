<?php
require_once 'global.php';
loadStandardCore();
doIncludes(
    [
        'includes/form2/form2.php',
        'includes/datagrid2/datagrid.php',
        'classes/hrd/hrd_shift_change.php',
        'classes/hrd/hrd_shift_schedule_employee.php',
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
        'privileges'         => $privileges,
        'strConfirmSave'     => getWords("do you want to save this entry?"),
        'strConfirmApproved' => getWords("do you want to approved this entry?"),
        'strPageTitle'       => getWords($privileges['menu_name']),
        'pageIcon'           => "../images/icons/" . $privileges['icon_file'],
        'strPageDesc'        => getWords("shift change management"),
        'pageHeader'         => '',
        'strTemplateFile'    => getTemplate(str_replace(".php", ".html", $calledFile)),
        'formObject'         => null,
        'formInput'          => '',
        'gridContents'       => null,
        'gridList'           => '',
        'gridTitle'          => getWords('LIST SHIFT CHANGE')
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
    $selectAttr = ["cols" => 97, "rows" => 2, "required" => true];
    $btnSaveAttr = ["onClick" => "javascript:myClient.confirmSave();"];
    $btnAddNewAttr = ["onClick" => "javascript:myClient.editData(0);"];
    $formModel = [
        'dataId'            => ['hidden', '', getPostValue('dataId')],
        'dataEmployee'      => ['input', 'employee', null, ['size' => 30, 'maxlength' => 31, 'required']],
        'dataCurrentShift'  => ['select', 'current shift', [], $selectAttr],
        'dataProposedShift' => ['select', 'proposed shift', ['hrd_shift_type', 'id', 'code'], $selectAttr],
        'dataNote'          => ['textarea', 'note', null, ["cols" => 97, "rows" => 2]],
        'btnSave'           => ['submit', 'save', 'getSaveData()', $btnSaveAttr],
        'btnAdd'            => ['submit', 'add new', '', $btnAddNewAttr]
    ];
    return getBuildForm($formModel, $formOptions);
}

function getShiftChangeListQuery(array $wheres = [])
{
    $strSql = 'SELECT
                    sch."id",
                    sse."id" AS "sseId",
                    sch.employee_id,
                    emp.employee_name,
                    sse.shift_date,
                    sse.shift_code,
                    sht.code,
                    bss."name",
                    sch.note,
                    sch.status,
                    sht.start_time,
                    sht.finish_time,
                    sch.active
                FROM
                    "public".hrd_shift_change AS sch
                INNER JOIN "public".hrd_employee AS emp ON sch.employee_id = emp."id"
                INNER JOIN "public".hrd_shift_type AS sht ON sch.proposed_shift = sht."id"
                INNER JOIN "public".hrd_shift_schedule_employee AS sse ON sch.current_shift = sse."id"
                INNER JOIN "public".base_status AS bss ON sch.status = bss."id"';
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
    $active = 't';
    $wheres[] = 'sch.active = ' . pgEscape($active);
    return pgFetchRows(getShiftChangeListQuery($wheres));
}

function getGridObject(array $gridOptions = [])
{
    $defaultColHeadAttr = ['width' => '400'];
    $defaultColContentAttr = ['nowrap' => ''];
    $gridButtons = [
        'btnDelete'   => [
            'delete',
            'Delete',
            'onClick="javascript:return myClient.confirmDelete();"',
            'deleteData()'
        ],
        'btnApproved' => [
            'approve',
            'Approve',
            'onClick="javascript:return myClient.confirmChangeStatus();"',
            'changeStatus()'
        ]
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
        'id'            => ['ID', ['width' => '10']],
        'no'            => ['No.', ['width' => '10']],
        'employee_name' => ['Employee Name', $defaultColHeadAttr],
        'shift_code'    => ['Current Shift', $defaultColHeadAttr],
        'code'          => ['Proposed Shift', $defaultColHeadAttr],
        'name'          => ['Status', $defaultColHeadAttr],
        'note'          => ['Note', $defaultColHeadAttr],
    ];
    $columnContent = [
        'id'            => ['id', $defaultColContentAttr, 'checkbox'],
        'no'            => ['no', ['nowrap' => ''], 'auto'],
        'employee_name' => ['employee_name', $defaultColContentAttr],
        'shift_code'    => ['shift_code', $defaultColContentAttr],
        'code'          => ['code', $defaultColContentAttr],
        'name'          => ['name', $defaultColContentAttr],
        'note'          => ['note', $defaultColContentAttr],
    ];
    $columnSet = ['head' => $columnHeader, 'content' => $columnContent];
    return getBuildDataGrid($modelData, $columnSet, getMergedArrayRecursively($defaultGridOptions, $gridOptions));
}

function checkStatus($mStt)
{
    $strSql = 'SELECT
                    bss."id",
                    bss."code",
                    bss."name"
                FROM
                    "public".base_status AS bss
                WHERE bss."name" = ' . pgEscape($mStt) . '
                GROUP BY bss."id"';
    $status = pgFetchRow($strSql);
    if (array_key_exists('id', $status) === true) {
        $status = $status['id'];
    }
    return $status;
}

function getSaveData()
{
    /**
     * @var \clsForm $formObject
     */
    global $formObject;
    $result = true;
    $dataHrdShiftChange = new cHrdShiftChange();
    $new = checkStatus('NEW');
    $model = [
        'employee_id'    => $formObject->getValue('dataEmployee'),
        'current_shift'  => $formObject->getValue('dataCurrentShift'),
        'proposed_shift' => $formObject->getValue('dataProposedShift'),
        'status'         => $new,
        'note'           => $formObject->getValue('dataNote')
    ];
    # Start to process updating database.
    if ($formObject->isInsertMode() === true) {
        # Insert master data for shift charge
        if (($result = $dataHrdShiftChange->insert($model)) === true) {
            $formObject->message = $dataHrdShiftChange->strMessage;
        } else {
            $result = false;
        }
    }
}

function deleteData()
{
    /**
     * @var \cDataGrid $dataGridObj
     */
    global $dataGridObj;
    //$gridObject = unserialize(getFlashMessage('shiftChangeGrid'), true);
    $arrId = [];
    $dataHrdShiftChange = new cHrdShiftChange();
    foreach ($dataGridObj->checkboxes as $value) {
        $arrId['id'] = $value;
        $disable = ['active' => 'f'];
        $dataHrdShiftChange->update($arrId, $disable);
    }
    $dataGridObj->message = 'Data Deleted';
    redirectPage($_SERVER['PHP_SELF']);
    //setFlashMessage($gridName, serialize($dataGridObj));
}

function changeStatus()
{
    /**
     * @var \cDataGrid $dataGridObj
     */
    //$gridObject = unserialize(getFlashMessage('shiftChangeGrid'), true);
    global $dataGridObj;
    $arrId = [];
    $dataHrdShiftChange = new cHrdShiftChange();
    $dataHrdShiftScheduleEmp = new cHrdShiftScheduleEmployee();
    foreach ($dataGridObj->checkboxes as $value) {
        $arrId = ['id' => $value];
        $approved = ['status' => checkStatus('APPROVED')];
        $new = checkStatus('NEW');
        $wheres[] = 'sch."id" = ' . pgEscape($value) . 'AND sch.status = ' . pgEscape($new);
        $approvedExist = pgFetchRow(getShiftChangeListQuery($wheres));
        foreach ($approvedExist as $value => $item) {
            #model data shift schedule employee
            $modelShiftSchEmp = [
                'id' => $item['sseId']
            ];
            $modelShiftType = [
                'shift_code'  => $item['code'],
                'start_time'  => $item['start_time'],
                'finish_time' => $item['finish_time']
            ];
        }
        if (($result = count($approvedExist) > 0) === true) {
            $dataHrdShiftChange->update($arrId, $approved);
            $dataHrdShiftScheduleEmp->update($modelShiftSchEmp, $modelShiftType);
            $dataGridObj->message = $dataHrdShiftChange->strMessage;
        } else {
            $dataGridObj->message = 'Data Is Approved';
        }
    }
    redirectPage($_SERVER['PHP_SELF']);
}
