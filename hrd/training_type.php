<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_training_type.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
//---- INISIALISASI ----------------------------------------------------
$strWordsTrainingCategory = getWords("training category");
$strWordsTrainingType = getWords("training type");
$strWordsTrainingTemplate = getWords("training template");
$strWordsTrainingNeedCriteria = getWords("training need criteria");
$db = new CdbClass;
$db->connect();
$strFilterIDCategory = getPostValue('filterIDCategory');
$strDataID = getPostValue('dataID');
$dataHrdTrainingType = new cHrdTrainingType();
$isNew = ($strDataID == "");
if ($bolCanEdit) {
  $f = new clsForm("formInput", 1, "100%", "");
  $f->caption = strtoupper("input data " . $dataPrivilege['menu_name']);
  $f->addHidden("dataID", $strDataID);
  $f->addSelect(
      getWords("training category"),
      "dataIDCategory",
      getDataListTrainingCategory($strFilterIDCategory, false),
      ["style" => "width:270"]
  );
  $f->addInput(getWords("code"), "dataCode", "", ["size" => 50], "string", true, true, true);
  $f->addInput(getWords("name"), "dataName", "", ["size" => 50], "string", true, true, true);
  $f->addTextArea(getWords("note"), "dataNote", "", ["cols" => 50, "rows" => 3], "string", false, true, true);
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
  $f->addButton("btnAdd", getWords("add new"), ["onClick" => "document.formFilter.submit();"]);
  $formInput = $f->render();
} else {
  $formInput = "";
}
//$strDataIDCategory = (getPostValue('dataIDCategory'));
$fFilter = new clsForm("formFilter", 8, "100%", "");
$fFilter->caption = strtoupper("filter data " . $strWordsTrainingType);
$fFilter->addSelect(
    getWords("category"),
    "filterIDCategory",
    getDataListTrainingCategory($strFilterIDCategory, true),
    "",
    "",
    false
);
$fFilter->addLiteral(
    "",
    "buttonShow",
    generateButton("btnShow", "Show", "", "onclick = \"document.formFilter.submit()\"")
);
//$fFilter->showCaption = false;
$fFilter->hasButton = false;
$formFilter = $fFilter->render();
$myDataGrid = new cDataGrid("formData", "DataGrid");
$myDataGrid->caption = strtoupper(getWords($dataPrivilege['menu_name']));
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", "", ['width' => '15', 'valign' => 'top', 'align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(
    new DataGrid_Column("No", "", "", ['width' => '30', 'valign' => 'top', 'nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("category"),
        "training_category",
        "",
        ['width' => '200', 'valign' => 'top', 'nowrap' => '']
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("code"), "code", "", ['width' => '50', 'valign' => 'top', 'nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("name"), "name", "", ['width' => '200', 'valign' => 'top', 'nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", "", ['valign' => 'top', 'nowrap' => '']));
if ($bolCanEdit) {
  $myDataGrid->addColumn(
      new DataGrid_Column(
          "",
          "",
          "",
          ['width' => '60', 'valign' => 'top', 'align' => 'center', 'nowrap' => ''],
          false,
          false,
          "",
          "printEditLink()"
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
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_training_type ";
//$strSQL       =  "SELECT t1.*, t2.training_category FROM hrd_training_type AS t1 LEFT JOIN hrd_training_category AS t2 ON t1.id_category = t2.id ";
// perubahan
$strSQL = "SELECT t1.*, t2.training_category FROM hrd_training_type AS t1 LEFT JOIN hrd_training_category AS t2 ON t1.id = t2.id ";
if ($strFilterIDCategory != "") {
  $strSQL .= " WHERE id_category = '$strFilterIDCategory'";
  $strSQLCOUNT .= " WHERE id_category = '$strFilterIDCategory'";
}
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$strConfirmSave = getWords("do you want to save this entry?");
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
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['id'] . "' />
      <input type=hidden name='detailIDCategory$counter' id='detailIDCategory$counter' value='" . $record['id_category'] . "' />
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
  $db = new CdbClass;
  $bolSave = true;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $dataHrdTrainingType = new cHrdTrainingType();
  if ($bolSave) {
    for ($i = 1; $i <= 5; $i++) {
      $arrScore['dataScore' . $i] = getPostValue('dataScore' . $i);
      $arrScore['dataScore' . $i . 'Note'] = getPostValue('dataScore' . $i . 'Note');
    }
    $data = [
        "id_category" => $f->getValue('dataIDCategory'),
        "code"        => $f->getValue('dataCode'),
        "name"        => $f->getValue('dataName'),
        "note"        => $f->getValue('dataNote')
    ];
    // simpan data -----------------------
    $bolSuccess = false;
    if ($isNew) {
      // data baru
      $bolSuccess = $dataHrdTrainingType->insert($data);
      if ($bolSuccess) {
        $f->setValue('dataID', $dataHrdTrainingType->getLastInsertId());
      }
    } else {
      $bolSuccess = $dataHrdTrainingType->update("id = '" . $f->getValue('dataID') . "'", $data);
    }
  }
  $f->message = $dataHrdTrainingType->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $dataHrdTrainingType = new cHrdTrainingType();
  $dataHrdTrainingType->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdTrainingType->strMessage;
} //deleteData
?>