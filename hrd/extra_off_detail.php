<?php
require_once 'global.php';
loadStandardCore();
doIncludes(
    [
        'includes/form2/form2.php',
        'includes/datagrid2/datagrid.php',
        'classes/hrd/hrd_extra_off_detail.php',
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
    if ($privileges['bolView'] === false) {
        die(accessDenied($_SERVER['HTTP_REFERER']));
    }
    # Initialize all global variables.
    $globalVariables = [
        'privileges'      => $privileges,
        'intTotalData'    => 0,
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
    ];
    extractToGlobal($globalVariables);
    # Important to given access to our global variables.
    foreach (array_keys($GLOBALS) as $varName) {
        global $$varName;
    }
    $pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
    # Get form model contents.
    $formOptions = ['column' => 1, 'caption' => strtoupper($strWordsINPUTDATA), 'references' => ['dataId']];
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
    global $strDateWidth, $strDefaultWidthPx;
    $dateFieldAttr = ["style" => "width:$strDateWidth"];
    $dateFieldAttrSelect = ["style" => "width:$strDefaultWidthPx"];
    $btnSaveAttr = ["onClick" => "javascript:myClient.confirmSave();"];
    $btnAddNewAttr = ["onClick" => "javascript:myClient.editData(0);"];
    $formModel = [
        'dataId'       => ['hidden', '', getPostValue('dataId')],
        'dataEmployee' => ['input', 'employee', null, ['size' => 30, 'maxlength' => 31, 'required']],
        'dataDateUse'  => ['input', 'date use extra off', null, $dateFieldAttr, 'date'],
        'dataExtraOff' => ['select', 'extra Off', null, $dateFieldAttrSelect],
        'btnSave'      => ['submit', 'save', 'saveData()', $btnSaveAttr],
        'btnAdd'       => ['submit', 'add new', '', $btnAddNewAttr]
    ];
    return getBuildForm($formModel, $formOptions);
}

function getDataGrid()
{
    $strSql = 'SELECT
                    emp."id",
                    emp.employee_id,
                    emp.employee_name AS employee_name,
                    qeo."id",
                    qeo.date_extra_off AS eof,
                    qeo.date_expaired AS exp,
                    qeo.note AS nt,
                    eod."id",
                    eod.employee_id,
                    eod.quota_extra_id,
                    eod.date_use
                FROM
                    "public".hrd_extra_off_detail AS eod
                LEFT JOIN "public".hrd_quota_extra_off AS qeo ON eod.quota_extra_id = qeo."id"
                LEFT JOIN "public".hrd_employee AS emp ON eod.employee_id = emp."id"';
    $strSqlCount = 'SELECT
                        "count" (*)
                    FROM
                        "public".hrd_extra_off_detail AS eod';
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
        'no'            => ['no', 'No.', '', ['width' => '10'], ['nowrap' => '']],
        'employee_name' => ['data', 'Employee Name', 'employee_name', $strTitleAttrWidth, $strAttrWidth],
        'date_use'      => ['data', 'Date Use Extra Off', 'date_use', $strTitleAttrWidth, $strAttrWidth],
        'eof'           => ['data', 'Date Extra Off', 'eof', $strTitleAttrWidth, $strAttrWidth],
        'nt'            => ['data', 'Note', 'nt', $strTitleAttrWidth, $strAttrWidth],
        'exp'           => ['data', 'Date Expared', 'exp', $strTitleAttrWidth, $strAttrWidth]
    ];
    return getBuildGrid($gridModel, $gridOptions, $gridDataBinding);
}

function getValidationInputDate($qeoId, $dataDateUseEo)
{
    $existDate = false;
    $existEmp = true;
    $strSqlEmp = 'SELECT
                        "count" (*)
                    FROM
                        "public".hrd_extra_off_detail AS eod
                    WHERE
                         eod.quota_extra_id = ' . pgEscape($qeoId) . '
                     AND eod.date_use = ' . pgEscape($dataDateUseEo) . '
                    GROUP BY
                        eod."id"';
    $validationDate = pgFetchRow($strSqlEmp);
    if (($validationDate > 0) === true) {
        $existEmp = false;
    }
    $strSql = 'SELECT
                    "count" (*)
                FROM
                    "public".hrd_quota_extra_off AS qeo
                WHERE
                    qeo.date_extra_off <= ' . pgEscape($dataDateUseEo) . '
                AND qeo.date_expaired >= ' . pgEscape($dataDateUseEo) . '
                AND qeo."id" = ' . pgEscape($qeoId) . '
                GROUP BY
                    qeo."id"';
    $validationDate = pgFetchRow($strSql);
    if (($validationDate > 0) === true) {
        $existDate = true;
    }
    return [
        'existDate' => $existDate,
        'existEmp'  => $existEmp
    ];
}

function saveData()
{
    /**
     * @var \clsForm $formObject
     */
    global $formObject;
    $result = true;
    $dataHrdExtraOffDetail = new cHrdExtraOffDetail();
    $dataHrdShiftScheduleEmp = new cHrdShiftScheduleEmployee();
    $dataDateUseEo = $formObject->getValue('dataDateUse');
    $dataDateUseEo = \DateTime::createFromFormat('d-m-Y', $dataDateUseEo)->format('Y-m-d');
    $model = [
        'employee_id'    => $formObject->getValue('dataEmployee'),
        'quota_extra_id' => $formObject->getValue('dataExtraOff'),
        'date_use'       => $dataDateUseEo
    ];
    # Load service charge model.
    $validationDate = getValidationInputDate($model['quota_extra_id'], $model['date_use']);
    # Start to process updating database.
    if ($formObject->isInsertMode() === true) {
        # Insert master data for Extra Off Detail
        if (($existDate = $validationDate['existDate']) === true) {
            if (($existEmp = $validationDate['existEmp']) === true) {
                if (($result = $dataHrdExtraOffDetail->insert($model)) === true) {
                    $dataEmpId = $formObject->getValue('dataEmployee');
                    $detailSsEmp = [
                        'id_employee' => $dataEmpId,
                        'shift_date'  => $dataDateUseEo
                    ];
                    $detailModel = [
                        'shift_code'  => 'E0',
                        'start_time'  => '00:00:00',
                        'finish_time' => '00:00:00'
                    ];
                    # Update into detail Shift Schedule Employee
                    if ($dataHrdShiftScheduleEmp->update($detailSsEmp, $detailModel) === false) {
                        debug($dataHrdShiftScheduleEmp, true);
                        $result = false;
                    }
                }
            }
        }
        $formObject->message = $dataHrdExtraOffDetail->strMessage;
    }
}