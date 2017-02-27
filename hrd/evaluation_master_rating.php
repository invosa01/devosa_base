<?php
// created date 03 dec 2015
// for project WAL
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_evaluation_rating.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
//$bolCanView=$bolCanEdit=true;
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$db = new CdbClass;
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
$myDataGrid->caption = strtoupper($strWordsLISTOF . " " . getWords($dataPrivilege['menu_name']));
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "code", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no."), "", ['width' => '30'], ['nowrap' => '']));
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
      getWords("delete"),
      "onClick=\"javascript:return myClient.confirmDelete();\"",
      "deleteData()"
  );
}
$myDataGrid->addButtonExportExcel(
    getWords("export excel"),
    $dataPrivilege['menu_name'] . ".xls",
    getWords($dataPrivilege['menu_name'])
);
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_evaluation_rating ";
$strSQL = "SELECT * FROM hrd_evaluation_rating ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
//$strPageTitle = "ddd";
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("Evaluation rating");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
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
  $dataHrdRating = new cHrdRating();
  $data = [
      "code" => $f->getValue('dataCode'),
      "name" => $f->getValue('dataName'),
      "note" => $f->getValue('dataNote')
  ];
  // simpan data -----------------------
  if ($isNew) {
    // data baru
    $bolSuccess = $dataHrdRating->insert($data);
  } else {
    $bolSuccess = $dataHrdRating->update(/*pk*/
        "code='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  if ($bolSuccess) {
    $f->setValue('dataID', $data['code']);
  }
  $f->message = $dataHrdRating->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['code'][] = $strValue;
  }
  $dataHrdRating = new cHrdRating();
  $dataHrdRating->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdRating->strMessage;
} //deleteData
?>