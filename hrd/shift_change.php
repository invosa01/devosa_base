<?php
require_once 'global.php';
loadStandardCore();
doIncludes(
    [
        'includes/form2/form2.php',
        'includes/datagrid2/datagrid.php',
        'classes/hrd/hrd_shift_change.php',
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
        'strPageDesc'     => getWords("shift change management"),
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
    $formOptions = [
        'column'     => 2,
        'caption'    => strtoupper($strWordsINPUTDATA),
        'references' => ['dataId']
    ];
    $formObject = getFormObject($formOptions);
    $formInput = $formObject->render();
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
        'dataShiftDate'     => ['input', 'shift date', null, array_merge($dateFieldAttr, ['required']), 'date'],
        'dataCurrentShift'  => ['select', 'current shift', ['hrd_shift_type', 'id', 'code'], $selectAttr],
        'dataProposedShift' => ['select', 'proposed shift', ['hrd_shift_type', 'id', 'code'], $selectAttr],
        'dataNote'          => ['textarea', 'note', null, ["cols" => 97, "rows" => 2]],
        'btnSave'           => ['submit', 'save', 'getSaveData()', $btnSaveAttr],
        'btnAdd'            => ['submit', 'add new', '', $btnAddNewAttr]
    ];
    return getBuildForm($formModel, $formOptions);
}

function getSaveData()
{
    /**
     * @var \clsForm $formObject
     */
    global $formObject;
    $result = true;
    $dataHrdShiftChange = new cHrdShiftChange();
    $shiftDate = $formObject->getValue('dataShiftDate');
    $shiftDate = \DateTime::createFromFormat('d-m-Y', $shiftDate)->format('Y-m-d');
    $status = REQUEST_STATUS_NEW;
    $model = [
        'shift_date'     => $shiftDate,
        'current_shift'  => $formObject->getValue('dataCurrentShift'),
        'proposed_shift' => $formObject->getValue('dataProposedShift'),
        'status'         => $status,
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