<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/form2/form2.php');
include_once('../global/employee_function.php');

$dataPrivilege = getDataPrivileges(basename("employee_edit.php"), $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
(isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = '';
$db = new CdbClass();
$db->connect();
# Get saved data if exist.
if ($bolCanView || $strDataID !== '') {
    $arrResult = getData($db);
    foreach ($arrResult as $idCompany => $intPercentage) {
        $arrCompany[] = $idCompany;
        $arrCostPercentage[] = $intPercentage;
    }
}
# Get number of company listed in master data company.
$strSQL = 'SELECT COUNT(id) AS id FROM hrd_company';
$res = $db->execute($strSQL);
if ($row = $db->fetchrow($res)) {
    $intCompanyCount = $row['id'];
}
# Create input form.
$f = new clsForm('formInput', 1, '100%', '');
for ($i = 1; $i <= $intCompanyCount; $i++) {
    $f->addSelect(getWords('company'.$i), 'dataCompany['.$i.']', getDataListCompany($arrCompany[$i-1], true, ['value' => '', 'text' => '-']), ['style' => 'width:400px'], 'numeric', false, true, true);
    $f->addInput(getWords('cost percentage'.$i.'(%)'), 'dataPercentage['.$i.']', (isset($arrCostPercentage[$i-1]) ? $arrCostPercentage[$i-1] : 0), ['style' => 'width:100px'], 'numeric', false, true, true);
}
if ($bolCanEdit) {
    $f->addSubmit('btnSave', getWords('save'), '', true, true, '', '', 'saveData()');
}
$f->addHidden('dataID', $strDataID);
$f->addHidden('dataEmployeeID', getEmployeeCode($db, $strDataID));
$f->addHidden('dataMax', $intCompanyCount);
$formInput = $f->render();

$tbsPage = new clsTinyButStrong;
$strPageTitle = getWords("employee cost sharing");
$strPageDesc = 'Edit employee cost sharing';
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = employeeEditSubmenu(getWords('cost sharing'));
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
/**
 * Function to save data inputted in form to hrd_employee_cost_sharing table.
 */
function saveData() {
    global $db;
    # Id employee of employee.
    $strIDEmployee = getRequestValue('dataID');
    # The number of existing company in master data company.
    $intCompanyCount = getRequestValue('dataMax');
    # Array containing company id selected in form.
    $arrCompany = getRequestValue('dataCompany');
    # Array containing cost percentage filled in form.
    $arrCostPercentage = getRequestValue('dataPercentage');
    $arrCostSharing = [];
    $arrExistingCompany = [];
    $strSQL = '';
    $strSQLCheck = '';
    # Create array containing id company and percentage.
    for ($i = 1; $i <= $intCompanyCount; $i++) {
        if (isset($arrCostPercentage[$i]) && $arrCostPercentage[$i] !== '' && $arrCostPercentage[$i] !== 0 &&
            isset($arrCompany[$i]) && $arrCompany[$i] !== '-') {
            if (empty($arrCostSharing)) {
                $arrCostSharing = [
                    $arrCompany[$i] => $arrCostPercentage[$i]
                ];
            }
            else {
                $arrCostSharing = $arrCostSharing + [$arrCompany[$i] => $arrCostPercentage[$i]];
            }
        }
    }
    # Create array containing existing company for validation purpose.
    $strSQLCheck .= 'SELECT t1.id_company FROM hrd_employee_cost_sharing AS t1
                     LEFT JOIN hrd_company AS t2 ON t1.id_company = t2.id
                     WHERE t1.id_employee = '.$strIDEmployee.';';
    $res = $db->execute($strSQLCheck);
    while ($row = $db->fetchrow($res)) {
        $arrExistingCompany[] = $row['id_company'];
    }
    # Create insert or update query.
    foreach ($arrCostSharing as $idCompany => $intPercentage) {
        # Check if company id already exist or not. Update if exist, otherwise insert new record.
        if (in_array($idCompany, $arrExistingCompany)) {
            $strSQL .= 'UPDATE hrd_employee_cost_sharing SET cost_percentage = '.$intPercentage.'
                        WHERE id_company = '.$idCompany.' AND id_employee = '.$strIDEmployee.';';
        }
        else {
            $strSQL .= 'INSERT INTO hrd_employee_cost_sharing (id_company, cost_percentage, id_employee)
                        VALUES ('.$idCompany.', '.$intPercentage.', '.$strIDEmployee.');';
        }
    }
    $res = $db->execute($strSQL);
}
/**
 * Function to fetch data from hrd_employee_cost_sharing if exist.
 *
 * @params $db
 *
 * return array
 */
function getData($db) {
    $arrResult = [];
    $strIDEmployee = getRequestValue('dataID');
    $strSQL = 'SELECT t1.id_company, t1.cost_percentage, t1.id_employee FROM hrd_employee_cost_sharing AS t1
                     LEFT JOIN hrd_company AS t2 ON t1.id_company = t2.id
                     WHERE t1.id_employee = '.$strIDEmployee.'
                     ORDER BY t1.id;';
    $res = $db->execute($strSQL);
    while ($row = $db->fetchrow($res)) {
        $arrResult[$row['id_company']] = isset($row['cost_percentage']) ? $row['cost_percentage'] : 0;
    }
    return $arrResult;
}