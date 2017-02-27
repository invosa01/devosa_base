<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_eotm.php');
include_once('../includes/datagrid2/datagrid.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
// if (!$bolCanView) die(accessDenied($_SERVER['HTTP_REFERER']));
$db = new CdbClass;
// ------------------------------------------------------------------------------------------------------------------------------
$strInputNo = "<input name=\"no_regulation\" type=\"text\" id=\"no_regulation\"></td></tr>";
$strInputDesc = "<textarea rows=\"4\" id=\"desc_regulation\"  name=\"desc_regulation\" cols=\"50\"></textarea></td></tr>";
$strInputDoc = "<input name=\"file\" type=\"file\" id=\"file\" value=\"file\"></td></tr>";
$tbsPage = new clsTinyButStrong;
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", true, true);
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column("Regulation No", "no_reg", ['width' => '150'], ['align' => 'left']));
$myDataGrid->addColumn(new DataGrid_Column("Description", "description", ['width' => '150'], ['align' => 'left']));
$myDataGrid->addColumn(new DataGrid_Column("File Name", "file_name", ['width' => '150'], ['align' => 'left']));
$myDataGrid->addButton(
    "btnDelete",
    "btnDelete",
    "submit",
    "Delete",
    "onClick=\"javascript:if (!confirm('Delete Regulation?')) return false\"",
    "DeleteChecked()"
);
$myDataGrid->getRequest();
$strSQL = "select * from regulation_file where 1=1";
$strSQLCOUNT = "
      SELECT COUNT(*) AS total
        FROM 
        ( 
          " . $strSQL . "
        ) AS xx
        WHERE 1=1 ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
//write this variable in every page
//$strPageTitle = $dataPrivilege['menu_name'];nanti dipake
$strPageTitle = "Upload Company Regulation";
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
function DeleteChecked($params)
{
    global $myDataGrid;
    global $db;
    extract($params);
    $arrKeys = [];
    foreach ($myDataGrid->checkboxes as $strValue) {
        $arrKeys['id'][] = $strValue;
    }
    $tbl = new cModel("regulation_file", "list regulation");
    $tbl->deleteMultiple($arrKeys);
    $myDataGrid->message = $tbl->strMessage;
    return true;
}

function getDataByID($strDataID)
{
    global $db;
    $tblEOTM = new cHrdEotm();
    $dataOETM = $global->find("id = $strDataID", "id", "id", null, 1, "id");
    $arrTemp = getEmployeeCode($db, $dataDonation['id_employee']);
    $arrResult['dataEmployee'] = $arrTemp['employee_id'];
    $arrResult['dataCreated'] = $dataOETM['created'];
    $arrResult['dataMonth'] = $dataOETM['form_code'];
    $arrResult['dataYear'] = $dataOETM['form_code'];
    $arrResult['dataCompany'] = $dataOETM['id_company'];
    $arrResult['dataNote'] = $dataOETM['note'];
    return $arrResult;
}

// fungsi untuk menyimpan data
?>