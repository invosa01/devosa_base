<?php
require_once 'global.php';
loadStandardCore();
doIncludes(
    [
        'includes/form2/form2.php',
        'includes/datagrid2/datagrid.php',
        'classes/hrd/hrd_global_configuration.php',
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
        'strPageDesc'     => getWords("global configuration managemnet"),
        'pageHeader'      => '',
        'strTemplateFile' => getTemplate(str_replace(".php", ".html", $calledFile)),
        'formObject'      => null,
        'formInput'       => '',
        'gridContents'    => null,
        'gridList'        => '',
        'gridTitle'       => getWords('LIST GLOBAL CONFIGURATION')
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
        'dataGcfCode'  => ['input', 'Code', null, ['size' => 30, 'maxlength' => 10, 'required']],
        'dataGcfName'  => ['input', 'Name', null, ['size' => 30, 'maxlength' => 31, 'required']],
        'dataGcfValue' => ['input', 'Value', null, ['size' => 30, 'maxlength' => 31, 'required']],
        'btnSave'      => ['submit', 'save', 'getSaveData()', $btnSaveAttr],
        'btnAdd'       => ['submit', 'add new', '', $btnAddNewAttr]
    ];
    return getBuildForm($formModel, $formOptions);
}

function getGlobalConfigurationListQuery(array $wheres = [])
{
    $strSql = 'SELECT
                    gcf."id",
                    gcf.code,
                    gcf."name",
                    gcf."value",
                    gcf.active
                FROM
                    "public".hrd_global_configuration AS gcf';
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
    $wheres[] = 'gcf.active = ' . pgEscape($active);
    return pgFetchRows(getGlobalConfigurationListQuery($wheres));
}

function getGridObject(array $gridOptions = [])
{
    $defaultColHeadAttr = ['width' => '400'];
    $defaultColContentAttr = ['nowrap' => ''];
    $gridButtons = [
        'btnDelete' => [
            'delete',
            'Delete',
            'onClick="javascript:return myClient.confirmDelete();"',
            'deleteData()'
        ]
    ];
    $defaultGridOptions = [
        'formName'          => 'frmGlobalConfigurationGrid',
        'gridName'          => 'globalConfigurationGrid',
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
        'id'    => ['ID', ['width' => '10']],
        'no'    => ['No.', ['width' => '10']],
        'code'  => ['Code', $defaultColHeadAttr],
        'name'  => ['Name', $defaultColHeadAttr],
        'value' => ['Value', $defaultColHeadAttr]
    ];
    $columnContent = [
        'id'    => ['id', $defaultColContentAttr, 'checkbox'],
        'no'    => ['no', ['nowrap' => ''], 'auto'],
        'code'  => ['code', $defaultColContentAttr],
        'name'  => ['name', $defaultColContentAttr],
        'value' => ['value', $defaultColContentAttr]
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
    $dataHrdGlobalConfiguration = new cHrdGlobalConfiguration();
    $gcfCode = $formObject->getValue('dataGcfCode');
    $model = [
        'code'    => $gcfCode,
        'name'    => $formObject->getValue('dataGcfName'),
        'value'   => $formObject->getValue('dataGcfValue'),
        'created' => now()
    ];
    # Start to process updating database.
    if ($formObject->isInsertMode() === true) {
        # Insert master data for extra off type
        $whereGcfCode [] = 'gcf.code = ' . pgEscape($gcfCode);
        $valEoLvlCodeExist = pgFetchRow(getGlobalConfigurationListQuery($whereGcfCode));
        if (($valEoLvlCodeExist > 0) === false) {
            if (($result = $dataHrdGlobalConfiguration->insert($model)) === true) {
                $formObject->message = $dataHrdGlobalConfiguration->strMessage;
            }
        } else {
            $formObject->message = 'Global Configuration Code : ' . $gcfCode . ' Exist';
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
    //$gridObject = unserialize(getFlashMessage('ExtraOffTypeGrid'), true);
    $arrId = [];
    $dataHrdGlobalConfiguration = new cHrdGlobalConfiguration();
    foreach ($dataGridObj->checkboxes as $value) {
        $arrId['id'] = $value;
    }
    $disable = [
        'active'   => 'f'
    ];
    $dataHrdGlobalConfiguration->update($arrId, $disable);
    $dataGridObj->message = 'Data Deleted';
    //setFlashMessage($gridName, serialize($dataGridObj));
}