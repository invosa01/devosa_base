<?php
require_once 'global.php';
loadStandardCore();
doIncludes(
    [
        'includes/form2/form2.php',
        'includes/datagrid2/datagrid.php',
        'classes/hrd/hrd_extra_off.php',
        'classes/hrd/hrd_extra_off_quota.php',
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
        'strPageDesc'        => getWords("extra off management"),
        'pageHeader'         => '',
        'strTemplateFile'    => getTemplate(str_replace(".php", ".html", $calledFile)),
        'formObject'         => null,
        'formInput'          => '',
        'gridContents'       => null,
        'gridList'           => '',
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
        'dataId'       => ['hidden', '', getPostValue('dataId')],
        'dataEmployee' => ['input', 'employee', null, ['size' => 30, 'maxlength' => 31, 'required']],
        'dataDateEo'   => ['input', 'date extra off', null, array_merge($dateFieldAttr, ['required']), 'date'],
        'dataType'     => ['select', 'type', [], $selectAttr],
        'dataNoteEo'   => ['textarea', 'note', null, ["cols" => 97, "rows" => 2, 'required']],
        'btnSave'      => ['submit', 'save', 'getSaveData()', $btnSaveAttr],
        'btnAdd'       => ['submit', 'add new', '', $btnAddNewAttr]
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
                    eoa."id",
                    eoa.employee_id,
                    eoa.date_eo,
                    eoa.status,
                    eoa.active,
                    eoa."type",
                    eoa.note,
                    emp.employee_name,
                    bst."name" AS name_status,
                    stp.code
                FROM
                    "public".hrd_eo_application AS eoa
                INNER JOIN "public".hrd_employee AS emp ON eoa.employee_id = emp."id"
                INNER JOIN "public".base_status AS bst ON eoa.status = bst."id"
                INNER JOIN "public".hrd_eo_conf AS eoc ON eoa."type" = eoc."id"
                INNER JOIN "public".hrd_shift_type AS stp ON eoc.shift_type_id = stp."id"';
    return getQuery($strSql, $wheres);
}

function getGridModelData()
{
    $wheres = [];
    $active = 't';
    $wheres[] = 'eoa.active = ' . pgEscape($active);
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
        'date_eo'       => ['Date Extra Off', $defaultColHeadAttr],
        'status'        => ['Status', $defaultColHeadAttr],
        'type'          => ['Type', $defaultColHeadAttr],
        'note'          => ['Note', $defaultColHeadAttr],
    ];
    $columnContent = [
        'id'            => ['id', $defaultColContentAttr, 'checkbox'],
        'no'            => ['no', ['nowrap' => ''], 'auto'],
        'employee_name' => ['employee_name', $defaultColContentAttr],
        'date_eo'       => ['date_eo', $defaultColContentAttr],
        'status'        => ['name_status', $defaultColContentAttr],
        'type'          => ['code', $defaultColContentAttr],
        'note'          => ['note', $defaultColContentAttr],
    ];
    $columnSet = ['head' => $columnHeader, 'content' => $columnContent];
    return getBuildDataGrid($modelData, $columnSet, getMergedArrayRecursively($defaultGridOptions, $gridOptions));
}

function getValidationInputDate($dataDateEo, $empId)
{
    $existDate = true;
    $strSql = 'SELECT
                   "count" (*)
                FROM
                    "public".hrd_eo_application AS eoa
                WHERE
                   eoa.date_eo = ' . pgEscape($dataDateEo) . '
                AND  eoa.employee_id = ' . pgEscape($empId) . '
                GROUP BY eoa."id"';
    $validationDate = pgFetchRow($strSql);
    if (($validationDate > 0) === true) {
        $existDate = false;
    }
    return [
        'existDate' => $existDate
    ];
}

function getValidationInputAttend($dataDateEo, $empId)
{
    $existAttend = false;
    $strSql = 'SELECT
                    "count" (*)
                FROM
                    "public".hrd_attendance AS atd
                WHERE
                    atd.id_employee = ' . pgEscape($empId) . '
                AND atd.attendance_date = ' . pgEscape($dataDateEo) . '
                GROUP BY atd."id"';
    $validationAttend = pgFetchRow($strSql);
    if (($validationAttend > 0) === true) {
        $existAttend = true;
    }
    return [
        'existAttend' => $existAttend
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
    $dataHrdExtraOffApplication = new cHrdExtraOffApplication();
    $dataDateEo = $formObject->getValue('dataDateEo');
    $dataDateEo = \DateTime::createFromFormat('d-m-Y', $dataDateEo)->format('Y-m-d');
    $new = checkStatus('NEW');
    # Load extra off model.
    $model = [
        'employee_id' => $formObject->getValue('dataEmployee'),
        'date_eo'     => $dataDateEo,
        'status'      => $new,
        'active'      => 't',
        'type'        => $formObject->getValue('dataType'),
        'note'        => $formObject->getValue('dataNoteEo'),
    ];
    $validationDate = getValidationInputDate($model['date_eo'], $model['employee_id']);
    $validationAttend = getValidationInputAttend($model['date_eo'], $model['employee_id']);
    # Start to process updating database.
    if ($formObject->isInsertMode() === true) {
        # Insert master data for extra off quota.
        if (($existDate = $validationDate['existDate']) === true) {
            if (($existAttend = $validationAttend['existAttend']) === true) {
                if (($result = $dataHrdExtraOffApplication->insert($model)) === true) {
                    $formObject->message = $dataHrdExtraOffApplication->strMessage;
                }
            } else {
                $formObject->message = 'Employee : '
                    . $model['employee_id']
                    . ' And Date  : '
                    . $model['date_eo']
                    . ' Attendance Not Exist';
                $formObject->msgClass = "bgError";
            }
        } else {
            $formObject->message = 'Employee : '
                . $model['employee_id']
                . ' And Date  : '
                . $model['date_eo']
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
    $dataHrdExtraOffApplication = new cHrdExtraOffApplication();
    foreach ($dataGridObj->checkboxes as $value) {
        $arrId['id'][] = $value;
    }
    $new = checkStatus('NEW');
    $disable = ['active' => 'f',];
    $wheres[] = 'eoa."id" = ' . pgEscape($value) . 'AND eoa.status = ' . pgEscape($new);
    $approvedExist = pgFetchRows(getExtraOffListQuery($wheres));
    if (($result = count($approvedExist) > 0) === true) {
        $dataHrdExtraOffApplication->deleteMultiple($arrId);
    } else {
        $arr2Id = ['id' => $value];
        $dataHrdExtraOffApplication->update($arr2Id, $disable);
    }
    $dataGridObj->message = 'Data Deleted';
    //setFlashMessage($gridName, serialize($dataGridObj));
}

function getDataExtraOff($eoaId)
{
    $strSql = 'SELECT
                    eoa."id",
                    eoa.employee_id,
                    eoa.date_eo,
                    eoa.active,
                    eoa."type",
                    eoa.note
                FROM
                    "public".hrd_eo_application AS eoa
                WHERE eoa."id" = ' . pgEscape($eoaId) . '
                GROUP BY eoa."id"';
    $setDataExtraOff = pgFetchRow($strSql);
    return $setDataExtraOff;
}

function getConfExtraOff($type, array $wheres = [])
{
    $wheres = [];
    $strSql = 'SELECT
                    eoc.eo_level_code,
                    eoc.shift_type_id,
                    eoc.duration,
                    eoc.expaired_day
                FROM
                    "public".hrd_eo_conf AS eoc
                INNER JOIN "public".hrd_eo_application AS eoa ON eoc."id" = eoa."type"
                WHERE eoa."type" = ' . pgEscape($type) . '
                GROUP BY eoc."id"';
    $setConfExtraOff = pgFetchRow(getQuery($strSql, $wheres));
    return $setConfExtraOff;
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
    $dataHrdExtraOffApplication = new cHrdExtraOffApplication();
    $dataHrdExtraOffQuota = new cHrdExtraOffQuota();
    foreach ($dataGridObj->checkboxes as $value) {
        $arrId = ['id' => $value];
    }
    $approved = ['status' => checkStatus('APPROVED')];
    $new = checkStatus('NEW');
    $eoIdAndNew[] = 'eoa."id" = ' . pgEscape($value) . 'AND eoa.status = ' . pgEscape($new);
    $approvedExist = pgFetchRows(getExtraOffListQuery($eoIdAndNew));
    $setModel = getDataExtraOff($arrId['id']);
    $date_eo = $setModel['date_eo'];
    $type = $setModel['type'];
    $eoConfType[] = 'eoa."type" = ' . pgEscape($type);
    $setModelConfEo = getConfExtraOff($type, $eoConfType);
    $expaired_day = $setModelConfEo['expaired_day'];
    $expaired = date('Y-m-d', strtotime('+' . $expaired_day . 'days', strtotime($date_eo)));
    $active = 't';
    # model data quota extra off
    $modelEoQuota = [
        'employee_id'       => $setModel['employee_id'],
        'eo_application_id' => $setModel['id'],
        'date_eo'           => $date_eo,
        'date_expaired'     => $expaired,
        'active'            => $active,
        'type'              => $setModel['type'],
        'note'              => $setModel['note']
    ];
    if (($result = count($approvedExist) > 0) === true) {
        #duration conf extra off
        $duration = $setModelConfEo['duration'];
        $dataHrdExtraOffQuota->insert($modelEoQuota);
        $dataHrdExtraOffApplication->update($arrId, $approved);;
        $dataGridObj->message = $dataHrdExtraOffApplication->strMessage;
    } else {
        $dataGridObj->message = 'Employee : '
            . $modelEoQuota['employee_id'] . ' And Date  : '
            . $modelEoQuota['date_eo'] . ' Is Approved';
    }
}