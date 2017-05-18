<?php
require_once 'global.php';
loadStandardCore();
doIncludes(
    [
        'includes/form2/form2.php',
        'includes/datagrid2/datagrid.php',
        'classes/hrd/hrd_extra_off.php',
        'classes/hrd/hrd_absence.php',
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
    if ($privileges['bolView'] === false) {
        die(accessDenied($_SERVER['HTTP_REFERER']));
    }
    # Initialize all global variables.
    $globalVariables = [
        'privileges'      => $privileges,
        'strConfirmSave'  => getWords("do you want to save this entry?"),
        'strPageTitle'    => getWords($privileges['menu_name']),
        'pageIcon'        => "../images/icons/" . $privileges['icon_file'],
        'strPageDesc'     => getWords("extra off management"),
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
        'column'     => 1,
        'caption'    => strtoupper($strWordsINPUTDATA),
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
    $btnSaveAttr = ["onClick" => "javascript:myClient.confirmSave();"];
    $btnAddNewAttr = ["onClick" => "javascript:myClient.editData(0);"];
    $formModel = [
        'dataId'       => ['hidden', '', getPostValue('dataId')],
        'dataEmployee' => ['input', 'employee', null, ['size' => 30, 'maxlength' => 31, 'required']],
        'dataDateEo'   => ['input', 'date extra off', null, $dateFieldAttr, 'date'],
        'dataNoteEo'   => ['textarea', 'note', null, ["cols" => 97, "rows" => 2]],
        'btnSave'      => ['submit', 'save', 'getSaveData()', $btnSaveAttr],
        'btnAdd'       => ['submit', 'add new', '', $btnAddNewAttr]
    ];
    return getBuildForm($formModel, $formOptions);
}

function getDataGrid()
{
    $strSql = 'SELECT
                    exo."id",
                    exo.employee_id,
                    exo.date_extra_off,
                    exo.note,
                    emp.employee_id AS code,
                    emp.employee_name
                FROM
                    "public".hrd_extra_off AS exo
                INNER JOIN "public".hrd_employee AS emp ON exo.employee_id = emp."id"';
    $strSqlCount = 'SELECT
                        "count" (*)
                    FROM
                        "public".hrd_extra_off AS exo';
    return [
        'strSql'      => $strSql,
        'strSqlCount' => $strSqlCount
    ];
}

function getGridListContents(array $gridOptions = [])
{
    $strTitleAttrWidth = ['width' => '150'];
    $strAttrWidth = ['nowrap' => ''];
    $gridDataBinding = getDataGrid();
    $gridModel = [
        'no'             => ['no', 'No.', '', ['width' => '10'], ['nowrap' => '']],
        'employee_name'  => ['data', 'Employee Name', 'employee_name', $strTitleAttrWidth, $strAttrWidth],
        'date_extra_off' => ['data', 'Date Extra Off', 'date_extra_off', $strTitleAttrWidth, $strAttrWidth],
        'note'           => ['data', 'Note', 'note', $strTitleAttrWidth, $strAttrWidth],
    ];
    return getBuildGrid($gridModel, $gridOptions, $gridDataBinding);
}

function getValidationInputDate($dataDateEo, $empId)
{
    $existDate = true;
    $strSql = 'SELECT
                    "count"(*)
                FROM
                    "public".hrd_extra_off AS exo
                WHERE exo.date_extra_off = ' . pgEscape($dataDateEo) . '
                AND 
                    exo.employee_id = ' . pgEscape($empId) . '
                GROUP BY 
                    exo.id';
    $validationDate = pgFetchRow($strSql);
    if (($validationDate > 0) === true) {
        $existDate = false;
    }
    return [
        'existDate' => $existDate
    ];
}

function getSaveData()
{
    /**
     * @var \clsForm $formObject
     */
    global $formObject;
    $result = true;
    $dataHrdExtraOff = new cHrdExtraOff();
    $dataHrdAbsence = new cHrdAbsence();
    $dataDateEo = $formObject->getValue('dataDateEo');
    $dataDateEo = \DateTime::createFromFormat('d-m-Y', $dataDateEo)->format('Y-m-d');
    $status = REQUEST_STATUS_NEW;
    $model = [
        'employee_id'    => $formObject->getValue('dataEmployee'),
        'date_extra_off' => $dataDateEo,
        'note'           => $formObject->getValue('dataNoteEo'),
        'status'         => $status,
    ];
    # Load service charge model.
    $validationDate = getValidationInputDate($model['date_extra_off'], $model['employee_id']);
    # Start to process updating database.
    if ($formObject->isInsertMode() === true) {
        # Insert master data for service charge
        if (($existDate = $validationDate['existDate']) === true) {
            if (($result = $dataHrdExtraOff->insert($model)) === true) {
                $exoId = $dataHrdExtraOff->getLastInsertId();
                $absenceTypeCode = 'EO';
                $detailModel = [
                    'extra_off_id'      => $exoId,
                    'id_employee'       => $formObject->getValue('dataEmployee'),
                    'date_from'         => $dataDateEo,
                    'absence_type_code' => $absenceTypeCode,
                    'note'              => $formObject->getValue('dataNoteEo'),
                    'status'            => $status
                ];
                # Insert into detail service charge.
                if ($dataHrdAbsence->insert($detailModel) === false) {
                    $result = false;
                }
            }
        }
        $formObject->message = $dataHrdExtraOff->strMessage;
    }
}