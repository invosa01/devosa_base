<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/form2/form2.php');
include_once('../includes/datagrid2/datagrid.php');
# Get page permission.
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
# Check page permission, display notification if user is not privileged.
if (!$bolCanView) {
    die(getWords('view denied'));
}
# Get Input Form.
$formInput = getFormInput();
# Get Datagrid.
$dataGrid = getDataGrid();
# Declare template class.
$tbsPage = new clsTinyButStrong;
# Get page title.
$strPageTitle = getWords($dataPrivilege['menu_name']);
# Get page description.
$strPageDesc = getWords('data leave level');
# Get page header.
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
# Get page template.
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
# Load page template.
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
/**
 * Function to create an input form.
 *
 * @return string
 */
function getFormInput()
{
    global $f;
    # Declare form class.
    $f = new clsForm('form1', 3, '100%');
    $f->addInput(getWords('Leave Level'), 'dataLeaveLevel', '', '', 'string', true, true, true);
    $f->addInput(getWords('Yearly Quota'), 'dataYearlyQuota', '', '', 'numeric', true, true, true);
    $f->addLabel('', '', '');
    $f->addLabel('', '', '');
    $f->addLabel('', '', '');
    $f->addLabel('', '', '');
    $f->addSubmit('btnSave', getWords('Save'), 'onclick = "return validInput();"', true, true, '', '', 'saveData()');
    return $f->render();
}

/**
 * Function to create a list.
 *
 * @return string
 */
function getDataGrid()
{
    global $myDataGrid;
    # Declare db class.
    $db = new CdbClass();
    # Declare datagrid class.
    $myDataGrid = new cDataGrid('formData', 'DataGrid1', '100%', '100%', true, false, false, true);
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column('chkID', 'id', ['align' => 'center', 'width' => '5'], ['align' => 'center'])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords('Level Code'), 'level_code', '', ['align' => 'left']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords('Maximal Leave Quota'), 'max_quota', '', ['align' => 'left']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords('Edit'), '', '', ['align' => 'left']));
    $myDataGrid->addSpecialButton('btnDelete', 'btnDelete', 'submit', getWords('delete'), '', 'deleteData()');
    $myDataGrid->getRequest();
    # Get total data.
    $strSQLCount = "SELECT COUNT(id) FROM hrd_leave_level_quota ";
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCount);
    # Get data list.
    $strSQL = "SELECT id, level_code, max_quota FROM hrd_leave_level_quota ";
    $dataset = $myDataGrid->getData($db, $strSQL);
    $myDataGrid->bind($dataset);
    return $myDataGrid->render();
}

/**
 * Function to save data to table.
 *
 * @return void
 */
function saveData()
{
    global $f;
    # Get input value.
    $arrData = [
        'level_code' => getRequestValue('dataLeaveLevel'),
        'max_quota'  => getRequestValue('dataYearlyQuota')
    ];
    # Create query string.
    $strColumn = '';
    $strValue = '';
    foreach ($arrData as $key => $value) {
        $strColumn .= (isset($strColumn) && $strColumn !== '') ? ", " . $key : $key;
        $strValue .= (isset($strValue) && $strValue !== '') ? ", " . "'$value'" : "'$value'";
    }
    $strSQL = "INSERT INTO hrd_leave_level_quota ($strColumn) VALUES ($strValue);";
    # Declare db class.
    $db = new CdbClass();
    if ($db->connect()) {
        if ($db->execute($strSQL)) {
            # Save to table hrd_level_leave_quota, display success message on success.
            $f->message = 'Data saved successfully';
        } else {
            # Display error message on failed.
            $f->message = 'Failed to save';
        }
    }
}

/**
 * Function to delete data from list.
 *
 * @return void
 */
function deleteData()
{
    global $myDataGrid;
    $db = new CdbClass();
    $strSQL = "";
    foreach ($myDataGrid->checkboxes as $strValue) {
        $strSQL .= "DELETE FROM hrd_leave_level_quota WHERE id = $strValue; ";
    }
    if ($db->connect()) {
        $db->execute($strSQL);
    }
}