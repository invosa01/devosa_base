<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_cost_center.php');
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
if ($db->connect()) {
  $strDataID = getPostValue('dataID');
  $isNew = ($strDataID == "");
  $strDataApprover = (isset($_REQUEST['approver_id'])) ? $_REQUEST['approver_id'] : $strDataApprover = "";
  if ($bolCanEdit) {
    $f = new clsForm("formInput", 1, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    $f->addSelect(getWords("company"), "dataCompany", getDataListCompany(-1, true, ""));
    $f->addInput(
        getWords("cost center name"),
        "dataName",
        "",
        ["size" => 25, "maxlength" => 15],
        "string",
        true,
        true,
        true
    );
    $f->addTextArea(getWords("note"), "dataNote", "", ["cols" => 97, "rows" => 2], "string", false, true, true);
    //      $f->addSelect(getWords("member :: company"), "dataMemberCompany", getDataListCompany(-1, true,""), array("multiple" => "multiple"));
    $f->addSubmit(
        "btnSave",
        getWords("save"),
        ["onClick" => "javascript:myClient.confirmSave();"],
        true,
        true,
        "",
        "",
        "saveData()"
    );
    $f->addButton("btnAdd", getWords("add new"), ["onClick" => "javascript:myClient.editData('0');"]);
    $formInput = $f->render();
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
  $myDataGrid->addColumn(new DataGrid_Column(getWords("company"), "company_name", null, ['nowrap' => '']));
  $myDataGrid->addColumn(new DataGrid_Column(getWords("cost center name"), "name", null, ['nowrap' => '']));
  $myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", null, ['nowrap' => '']));
  if ($bolCanEdit) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "",
            "",
            ['width' => '60'],
            ['align' => 'center', 'nowrap' => ''],
            false,
            false,
            "",
            "printEditLink()",
            "",
            false /*show in excel*/
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "",
            "",
            ['width' => '60'],
            ['align' => 'center', 'nowrap' => ''],
            false,
            false,
            "",
            "printPermissionLink()",
            "",
            false /*show in excel*/
        )
    );
  }
  if ($bolCanDelete) {
    $myDataGrid->addSpecialButton(
        "btnDelete",
        "btnDelete",
        "submit",
        getWords("delete"),
        "onClick=\"javascript:return myClient.confirmDelete();\"",
        "deleteData()"
    );
  }
  //tampilkan buttons sesuai dengan otoritas, common_function.php
  generateRoleButtons($bolCanEdit, $bolCanDelete, $bolCanCheck, $bolCanApprove, $bolCanAcknowledge, false, $myDataGrid);
  $myDataGrid->addButtonExportExcel(
      getWords("export excel"),
      $dataPrivilege['menu_name'] . ".xls",
      getWords($dataPrivilege['menu_name'])
  );
  $myDataGrid->getRequest();
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_cost_center";
  $strSQL = "SELECT a.*,c.company_name as dataID FROM hrd_cost_center as a
                    LEFT JOIN hrd_company AS c on a.id_company::integer = c.id";
  $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
  $dataset = $myDataGrid->getData($db, $strSQL);
  //bind Datagrid with array dataset
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
  $strConfirmSave = getWords("do you want to save this entry?");
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('data cost center page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
//--------------------------------------------------------------------------------
function printEditLink($params)
{
  extract($params);
  return "
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['id'] . "' />
      <input type=hidden name='detailName$counter' id='detailName$counter' value='" . $record['name'] . "' />
      <input type=hidden name='detailNote$counter' id='detailNote$counter' value='" . $record['note'] . "' />
      <input type=hidden name='detailCompany$counter' id='detailCompany$counter' value='" . $record['id_company'] . "' />
      <a href=\"javascript:myClient.editData('$counter')\">" . getWords('edit') . "</a>";
}

function printPermissionLink($params)
{
  extract($params);
  return "<a href=\"data_cost_center_member.php?dataID=" . $record['id'] . " \">" . getWords('set member') . "</a>";
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $db;
  global $error;
  global $isNew;
  $strDataCode = $f->getValue('dataName');
  //print_r($_REQUEST);die();
  $dataHrdCostCenter = new cHrdCostCenter();
  $data = [
      "name" => $f->getValue('dataName'),
      "id_company" => $f->getValue('dataCompany'),
      "note" => $f->getValue('dataNote')
  ];
  // simpan data -----------------------
  if ($isNew) {
    // data baru
    if (isDataExists($db, "hrd_cost_center", "name", $strDataCode)) {
      $f->message = $error['duplicate_code'] . " of Cost Center Name -> $strDataCode";
    }
    $bolSuccess = $dataHrdCostCenter->insert($data);
    $intDataID = $dataHrdCostCenter->getLastInsertId();
  } else {
    $bolSuccess = $dataHrdCostCenter->update(/*pk*/
        "id='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
    $intDataID = $f->getValue('dataID');
  }
  if ($bolSuccess) {
    $f->setValue('dataID', $intDataID);
  }
  $f->message = $dataHrdCostCenter->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $dataHrdCostCenter = new cHrdCostCenter();
  $dataHrdCostCenter->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdCostCenter->strMessage;
} //deleteData
?>