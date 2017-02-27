<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_donation_type.php');
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
$strWordsDonationTypeSetup = getWords("donation type setup");
$strWordsDonationQuotaSetup = getWords("donation quota setup");
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
if ($bolCanEdit) {
  $f = new clsForm("formInput", 1, "100%", "");
  $f->caption = strtoupper($strWordsINPUTDATA);
  $f->addHidden("dataID", $strDataID);
  $f->addInput(getWords("code"), "dataCode", "", ["size" => 30, "maxlength" => 31], "string", true, true, true);
  $f->addInput(getWords("name"), "dataName", "", ["size" => 100, "maxlength" => 127], "string", true, true, true);
  $f->addTextArea(getWords("note"), "dataNote", "", ["cols" => 97, "rows" => 2], "string", false, true, true);
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
  $f->addButton("btnAdd", getWords("add new"), ["onClick" => "javascript:myClient.editData(0);"]);
  $formInput = $f->render();
} else {
  $formInput = "";
}
$myDataGrid = new cDataGrid("formData", "DataGrid1");
$myDataGrid->caption = strtoupper($strWordsLISTOF . " " . $dataPrivilege['menu_name']);
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
if (!isset($_REQUEST['btnExportXLS'])) {
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "code", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
  );
}
$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("code"), "code", ['width' => '150'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("name"), "name", ['width' => '200'], ['nowrap' => '']));
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
}
if ($bolCanDelete) {
  $myDataGrid->addSpecialButton(
      "btnDelete",
      "btnDelete",
      "submit",
      "Delete",
      "onClick=\"javascript:return myClient.confirmDelete();\"",
      "deleteData()"
  );
}
$myDataGrid->addButtonExportExcel(
    "Export Excel",
    $dataPrivilege['menu_name'] . ".xls",
    getWords($dataPrivilege['menu_name'])
);
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_donation_type ";
$strSQL = "SELECT * FROM hrd_donation_type ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
function printEditLink($params)
{
  extract($params);
  return "
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['code'] . "' />
      <input type=hidden name='detailCode$counter' id='detailCode$counter' value='" . $record['code'] . "' />
      <input type=hidden name='detailName$counter' id='detailName$counter' value='" . $record['name'] . "' />
      <input type=hidden name='detailNote$counter' id='detailNote$counter' value='" . $record['note'] . "' />
      <a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $isNew;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $dataHrdDonationType = new cHrdDonationType();
  $data = [
      "code" => $f->getValue('dataCode'),
      "name" => $f->getValue('dataName'),
      "note" => $f->getValue('dataNote')
  ];
  // simpan data -----------------------
  if ($isNew) {
    // data baru
    $bolSuccess = $dataHrdDonationType->insert($data);
  } else {
    $bolSuccess = $dataHrdDonationType->update(/*pk*/
        "code='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  if ($bolSuccess) {
    $f->setValue('dataID', $data['code']);
  }
  $f->message = $dataHrdDonationType->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['code'][] = $strValue;
  }
  $dataHrdDonationType = new cHrdDonationType();
  $dataHrdDonationType->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdDonationType->strMessage;
} //deleteData
?>