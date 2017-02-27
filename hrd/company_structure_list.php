<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_company.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView) {
    die(accessDenied($_SERVER['HTTP_REFERER']));
}
$db = new CdbClass;
/*$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
$strDefaultPath = getSetting("attendance_file_path");
$strDefaultType = getSetting("attendance_file_type");*/
if ($bolCanEdit) {
    /* $f = new clsForm("formInput", 1, "100%", "");
     $f->caption = strtoupper($strWordsINPUTDATA);

     $f->addHidden("dataID", $strDataID);

     $f->addInput(getWords("company code"), "dataCompanyCode", "", array("size" => 31, "maxlength" => 31), "string", true, true, true);
     $f->addInput(getWords("company name"), "dataCompanyName", "", array("size" => 100, "maxlength" => 127), "string", true, true, true);
     $f->addInput(getWords("attendance file path"), "dataAttendanceFilePath", $strDefaultPath, array("size" => 100, "maxlength" => 255), "string", false, true, true);
     $f->addInput(getWords("attendance file type"), "dataAttendanceFileType", $strDefaultType, array("size" => 100, "maxlength" => 7), "string", false, true, true);

     $f->addTextArea(getWords("note"), "dataNote", "", array("cols" => 97, "rows" => 2), "string", false, true, true);


     $f->addSubmit("btnSave", getWords("save"), array("onClick" => "javascript:myClient.confirmSave();"), true, true, "", "", "saveData()");
     $f->addButton("btnAdd", getWords("add new"), array("onClick" => "javascript:myClient.editData(0);"));

     $formInput = $f->render();*/
} else {
    $formInput = "";
}
$myDataGrid = new cDataGrid("formData", "DataGrid1");
$myDataGrid->caption = strtoupper($strWordsLISTOF . " " . getWords($dataPrivilege['menu_name']));
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no."), "", ['width' => '30'], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("company code"), "company_code", ['width' => '150'], ['nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("company name"), "company_name", "", ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(
        "Organization Chart",
        "",
        ['width' => '60'],
        ['align' => 'center', 'nowrap' => ''],
        false,
        false,
        "",
        "printChartLink()",
        "",
        false /*show in excel*/
    )
);
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_company ";
$strSQL = "SELECT * FROM hrd_company ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('company structure organization list');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
function printChartLink($params)
{
    extract($params);
    return "

      <a href=\"data_functional_chart_3.php?company_id=" . $record['id'] . "\">" . getWords('chart') . "</a>";
}

?>