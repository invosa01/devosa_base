<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_training_category.php');
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
$strDataID = getPostValue('dataIDCategory');
$isNew = ($strDataID == "");
if ($bolCanEdit) {
  $f = new clsForm("formInput", 1, "100%", "");
  $f->caption = strtoupper(vsprintf(getWords("input data %s"), $strWordsTrainingCategory));
  $f->addHidden("dataIDCategory", $strDataID);
  $f->addInput(getWords("training category"), "dataCategory", "", ["size" => 49], "string", true, true, true);
  $f->addTextArea(getWords("note"), "dataNote", "", ["cols" => 46, "rows" => 3], "string", false, true, true);
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
$myDataGrid->caption = getWords(strtoupper(vsprintf(getWords("list of %s"), getWords("family status"))));
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", ['width' => '15'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("category"), "training_category", ['width' => '250'], ['nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", "", ['align' => 'center']));
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
$myDataGrid->strAdditionalHtml = generateHidden("dataIDCategory", "", "");
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_training_category ";
$strSQL = "SELECT * FROM hrd_training_category ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$intNextSequence = $myDataGrid->totalData + 1;
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
      <input type=hidden name='detailCategory$counter' id='detailCategory$counter' value='" . $record['training_category'] . "' />
      <input type=hidden name='detailNote$counter' id='detailNote$counter' value='" . $record['note'] . "' />
      <a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $isNew;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $dataHrdTrainingCategory = new cHrdTrainingCategory();
  $data = [
      "training_category" => $f->getValue('dataCategory'),
      "note"              => $f->getValue('dataNote')
  ];
  // simpan data -----------------------
  $bolSuccess = false;
  if ($isNew) {
    // data baru
    $bolSuccess = $dataHrdTrainingCategory->insert($data);
  } else {
    $bolSuccess = $dataHrdTrainingCategory->update(/*pk*/
        "id='" . $f->getValue('dataIDCategory') . "'", /*data to update*/
        $data
    );
  }
  if ($bolSuccess) {
    if (isset($data['id'])) {
      $f->setValue('dataIDCategory', $data['id']);
    } else {
      $f->setValue('dataIDCategory', "");
    }
    if ($isNew) {
      //berikan parent id sesuai dengan id pada data baru. Mempermudah sorting pada datagrid (by parent_id, created)
      $f->setValue('dataIDCategory', $dataHrdTrainingCategory->getLastInsertId());
    }
  }
  $f->message = $dataHrdTrainingCategory->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $dataHrdTrainingCategory = new cHrdTrainingCategory();
  $dataHrdTrainingCategory->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdTrainingCategory->strMessage;
} //deleteData
?>