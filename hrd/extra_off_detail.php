<?php
require_once 'global.php';
loadStandardCore();
doIncludes(
    [
        'includes/form2/form2.php',
        'includes/datagrid2/datagrid.php',
        'classes/hrd/hrd_extra_off_detail.php',
        'classes/hrd/hrd_extra_off_quota.php',
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
        'privileges'      => $privileges,
        'strConfirmSave'  => getWords("do you want to save this entry?"),
        'strPageTitle'    => getWords($privileges['menu_name']),
        'pageIcon'        => "../images/icons/" . $privileges['icon_file'],
        'strPageDesc'     => getWords("use extra off management"),
        'pageHeader'      => '',
        'strTemplateFile' => getTemplate(str_replace(".php", ".html", $calledFile)),
        'formObject'      => null,
        'formInput'       => '',
        'gridContents'    => null,
        'gridList'        => '',
        'gridTitle'       => getWords('LIST EXTRA OFF DETAIL')
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
    global $strDateWidth, $strDefaultWidthPx;
    $dateFieldAttr = ["style" => "width:$strDateWidth"];
    $selectAttr = ["cols" => 97, "rows" => 2, "required" => true];
    $btnSaveAttr = ["onClick" => "javascript:myClient.confirmSave();"];
    $btnAddNewAttr = ["onClick" => "javascript:myClient.editData(0);"];
    $formModel = [
        'dataId'            => ['hidden', '', getPostValue('dataId')],
        'dataEmployee'      => ['input', 'employee', null, ['size' => 30, 'maxlength' => 31, 'required']],
        'dataDateUse'       => ['input', 'date Use extra off', null, array_merge($dateFieldAttr, ['required']), 'date'],
        'dataQuotaExtraOff' => ['select', 'extra Off', [], $selectAttr],
        'btnSave'           => ['submit', 'save', 'getSaveData()', $btnSaveAttr],
        'btnAdd'            => ['submit', 'add new', '', $btnAddNewAttr]
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
                    eou."id",
                    eou.employee_id,
                    eou.eo_quota_id,
                    eou.date_use,
                    eou.status,
                    eou.active,
                    stp.code,
                    bss."name" AS name_status,
                    eoq.date_expaired,
                    eoq.date_eo,
                    eoq.note,
                    emp.employee_name,
                    stp.start_time,
	                stp.finish_time
                FROM
                    "public".hrd_eo_use AS eou
                INNER JOIN "public".hrd_eo_quota AS eoq ON eou.eo_quota_id = eoq."id"
                INNER JOIN "public".hrd_eo_conf AS eoc ON eoq."type" = eoc."id"
                INNER JOIN "public".hrd_shift_type AS stp ON eoc.shift_type_id = stp."id"
                INNER JOIN "public".base_status AS bss ON eou.status = bss."id"
                INNER JOIN "public".hrd_employee AS emp ON eou.employee_id = emp."id"
                AND eoq.employee_id = emp."id"';
    return getQuery($strSql, $wheres);
}

function getGridModelData()
{
    $wheres = [];
    $active = 't';
    $wheres[] = 'eou.active = ' . pgEscape($active);
    return pgFetchRows(getExtraOffListQuery($wheres));
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
        'formName'          => 'frmExtraOffGrid',
        'gridName'          => 'extraOffGrid',
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
        'date_use'      => ['Date Use Extra Off', $defaultColHeadAttr],
        'date_expaired' => ['Date Expaired Extra Off', $defaultColHeadAttr],
        'date_eo'       => ['Date Extra Off', $defaultColHeadAttr],
        'status'        => ['Status', $defaultColHeadAttr],
        'type'          => ['Type', $defaultColHeadAttr],
        'note'          => ['Note', $defaultColHeadAttr],
    ];
    $columnContent = [
        'id'            => ['id', $defaultColContentAttr, 'checkbox'],
        'no'            => ['no', ['nowrap' => ''], 'auto'],
        'employee_name' => ['employee_name', $defaultColContentAttr],
        'date_use'      => ['date_use', $defaultColContentAttr],
        'date_expaired' => ['date_expaired', $defaultColContentAttr],
        'date_eo'       => ['date_eo', $defaultColContentAttr],
        'status'        => ['name_status', $defaultColContentAttr],
        'type'          => ['code', $defaultColContentAttr],
        'note'          => ['note', $defaultColContentAttr],
    ];
    $columnSet = ['head' => $columnHeader, 'content' => $columnContent];
    return getBuildDataGrid($modelData, $columnSet, getMergedArrayRecursively($defaultGridOptions, $gridOptions));
}

function getValidationInputDate($dataDateUse, $empId)
{
    $existDate = true;
    $strSql = 'SELECT
                    "count" (*)
                FROM
                    "public".hrd_eo_use AS eou
                WHERE
                    eou.employee_id = ' . pgEscape($empId) . '
                AND eou.date_use = ' . pgEscape($dataDateUse) . '
                GROUP BY
                    eou."id"';
    $validationDate = pgFetchRow($strSql);
    if (($validationDate > 0) === true) {
        $existDate = false;
    }
    return [
        'existDate' => $existDate
    ];
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
    $dataExtraOffDetail = new cHrdExtraOffDetail();
    $dataDateUse = $formObject->getValue('dataDateUse');
    $dataDateUse = \DateTime::createFromFormat('d-m-Y', $dataDateUse)->format('Y-m-d');
    $new = checkStatus('NEW');
    $active = 't';
    # Load extra off model.
    $model = [
        'employee_id' => $formObject->getValue('dataEmployee'),
        'date_use'    => $dataDateUse,
        'eo_quota_id' => $formObject->getValue('dataQuotaExtraOff'),
        'status'      => $new,
        'active'      => $active
    ];
    $validationDate = getValidationInputDate($model['date_use'], $model['employee_id']);
    # Start to process updating database.
    if ($formObject->isInsertMode() === true) {
        # Insert master data for extra off quota.
        if (($existDate = $validationDate['existDate']) === true) {
            if (($result = $dataExtraOffDetail->insert($model)) === true) {
                $formObject->message = $dataExtraOffDetail->strMessage;
            }
        } else {
            $formObject->message = 'Employee : '
                . $model['employee_id']
                . ' And Date  : '
                . $model['date_use']
                . ' Exist';
            $formObject->msgClass = "bgError";
        }
    }
}

function deleteData()
{
    /**
     * @var \cDataGrid $dataGridObj
     */
    //$gridObject = unserialize(getFlashMessage('shiftChangeGrid'), true);
    global $dataGridObj;
    $result = false;
    $arrId = [];
    $dataExtraOffDetail = new cHrdExtraOffDetail();
    foreach ($dataGridObj->checkboxes as $value) {
        $arrId['id'][] = $value;
    }
    $new = checkStatus('NEW');
    $disable = ['active' => 'f'];
    $wheres[] = 'eou."id" = ' . pgEscape($value) . 'AND eou.status = ' . pgEscape($new);
    $approvedExist = pgFetchRows(getExtraOffListQuery($wheres));
    if (($result = count($approvedExist) > 0) === true) {
        $dataExtraOffDetail->deleteMultiple($arrId);
    } else {
        $arr2Id = ['id' => $value];
        $dataExtraOffDetail->update($arr2Id, $disable);
    }
    $dataGridObj->message = 'Data Deleted';
    //setFlashMessage($gridName, serialize($dataGridObj));
}

function getShiftSchEmpExist($empId, $dateUse)
{
    $ExistshiftSchEmp = true;
    $strSql = 'SELECT
                    "count" (*)
                FROM
                    "public".hrd_shift_schedule_employee AS she
                WHERE 
                    she.id_employee = ' . pgEscape($empId) . '
                AND she.shift_date = ' . pgEscape($dateUse) . '
                GROUP BY she."id"';
    $validationshiftSchEmpExist = pgFetchRow($strSql);
    if (($validationshiftSchEmpExist > 0) === true) {
        $ExistshiftSchEmp = false;
    }
    return [
        'ExistshiftSchEmp' => $ExistshiftSchEmp
    ];
}

function changeStatus()
{
    /**
     * @var \cDataGrid $dataGridObj
     */
    //$gridObject = unserialize(getFlashMessage('shiftChangeGrid'), true);
    global $dataGridObj;
    $result = false;
    $arrId = [];
    $dataExtraOffDetail = new cHrdExtraOffDetail();
    $dataHrdExtraOffQuota = new cHrdExtraOffQuota();
    $dataHrdShiftScheduleEmp = new cHrdShiftScheduleEmployee();
    foreach ($dataGridObj->checkboxes as $value) {
        $arrId = ['id' => $value];
    }
    $approved = ['status' => checkStatus('APPROVED')];
    $new = checkStatus('NEW');
    $wheres[] = 'eou."id" = ' . pgEscape($value) . 'AND eou.status = ' . pgEscape($new);
    $approvedExist = pgFetchRows(getExtraOffListQuery($wheres));
    foreach ($approvedExist as $value => $item) {
        # Update extra off quota
        $varQuotaActive = [
            'active' => 'f'
        ];
        $varQuotaId = [
            'id' => $item['eo_quota_id']
        ];
        # model data shift schedulle employee
        $modelShiftEmp = [
            'id_employee' => $item['employee_id'],
            'shift_date'  => $item['date_use']
        ];
        $modelShiftType = [
            'shift_code'  => $item['code'],
            'start_time'  => $item['start_time'],
            'finish_time' => $item['finish_time']
        ];
    }
    if (($result = count($approvedExist) > 0) === true) {
        # Update approved extra off detail.
        $dataExtraOffDetail->update($arrId, $approved);
        # Update extra off quota.
        $dataHrdExtraOffQuota->update($varQuotaId, $varQuotaActive);
        # Insert into detail Shift Schedule Employee.
        $validationshiftSchEmpExist = getShiftSchEmpExist($modelShiftEmp['id_employee'], $modelShiftEmp['shift_date']);
        if (($ExistshiftSchEmp = $validationshiftSchEmpExist['ExistshiftSchEmp']) === true) {
            $dataHrdShiftScheduleEmp->insert(array_merge($modelShiftEmp, $modelShiftType));
        } else {
            $dataHrdShiftScheduleEmp->update($modelShiftEmp, $modelShiftType);
        }
        $dataGridObj->message = $dataExtraOffDetail->strMessage;
    } else {
        $dataGridObj->message = 'Data Is Approved';
    }
}