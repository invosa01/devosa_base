<?php
require_once 'global.php';
loadStandardCore();
doIncludes(
    [
        'includes/form2/form2.php',
        'includes/datagrid2/datagrid.php',
        'classes/hrd/hrd_extra_off_type.php',
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
        'gridTitle'          => getWords('LIST EXTRA OFF TYPE')
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
        'dataId'          => ['hidden', '', getPostValue('dataId')],
        'dataEoLvlCode'   => ['input', 'level code', null, ['size' => 30, 'maxlength' => 31, 'required']],
        'dataShiftType'   => ['select', 'shift type', ['hrd_shift_type', 'id', 'code'], $selectAttr],
        'dataDuration'    => ['input', 'duration days', null, ['size' => 30, 'maxlength' => 31, 'required']],
        'dataExpairedDay' => ['input', 'expaired days', null, ['size' => 30, 'maxlength' => 31, 'required']],
        'btnSave'         => ['submit', 'save', 'getSaveData()', $btnSaveAttr],
        'btnAdd'          => ['submit', 'add new', '', $btnAddNewAttr]
    ];
    return getBuildForm($formModel, $formOptions);
}

function getShiftChangeListQuery(array $wheres = [])
{
    $strSql = 'SELECT
                    eof."id",
                    eof.eo_level_code,
                    eof.shift_type_id,
                    eof.duration,
                    eof.expaired_day,
                    sht.code
                FROM
                    "public".hrd_eo_conf AS eof
                INNER JOIN "public".hrd_shift_type AS sht ON eof.shift_type_id = sht."id"';
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
    $wheres[] = 'eof.active = ' . pgEscape($active);
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
        'formName'          => 'frmExtraOffTypeGrid',
        'gridName'          => 'extraOffTypeGrid',
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
        'eo_level_code' => ['Eo Level Code', $defaultColHeadAttr],
        'code'          => ['Shift Type', $defaultColHeadAttr],
        'duration'      => ['Duration', $defaultColHeadAttr],
        'expaired_day'  => ['Expaired Day', $defaultColHeadAttr]
    ];
    $columnContent = [
        'id'            => ['id', $defaultColContentAttr, 'checkbox'],
        'no'            => ['no', ['nowrap' => ''], 'auto'],
        'eo_level_code' => ['eo_level_code', $defaultColContentAttr],
        'code'          => ['code', $defaultColContentAttr],
        'duration'      => ['duration', $defaultColContentAttr],
        'expaired_day'  => ['expaired_day', $defaultColContentAttr]
    ];
    $columnSet = ['head' => $columnHeader, 'content' => $columnContent];
    return getBuildDataGrid($modelData, $columnSet, getMergedArrayRecursively($defaultGridOptions, $gridOptions));
}

function getSaveData()
{
    /**
     * @var \clsForm $formObject
     */
    global $formObject;
    $result = true;
    $dataHrdExtraOffType = new cHrdExtraOffType();
    $eoLvlCode = $formObject->getValue('dataEoLvlCode');
    $model = [
        'eo_level_code' => $eoLvlCode,
        'shift_type_id' => $formObject->getValue('dataShiftType'),
        'duration'      => $formObject->getValue('dataDuration'),
        'expaired_day'  => $formObject->getValue('dataExpairedDay')
    ];
    # Start to process updating database.
    if ($formObject->isInsertMode() === true) {
        # Insert master data for extra off type
        $whereEoLvlCode [] = 'eof.eo_level_code = ' . pgEscape($eoLvlCode);
        $valEoLvlCodeExist = pgFetchRow(getShiftChangeListQuery($whereEoLvlCode));
        if (($valEoLvlCodeExist > 0) === false) {
            if (($result = $dataHrdExtraOffType->insert($model)) === true) {
                $formObject->message = $dataHrdExtraOffType->strMessage;
            }
        } else {
            $formObject->message = 'Extra Off Level Code : ' . $eoLvlCode . ' Exist';
            $formObject->msgClass = "bgError";
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
    $dataHrdExtraOffType = new cHrdExtraOffType();
    foreach ($dataGridObj->checkboxes as $value) {
        $arrId['id'] = $value;
    }
    $disable = ['active' => 'f'];
    $dataHrdExtraOffType->update($arrId, $disable);
    $dataGridObj->message = 'Data Deleted';
    //setFlashMessage($gridName, serialize($dataGridObj));
}

