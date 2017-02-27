<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_training_need_criteria.php');
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
$strDataID = getPostValue('dataID');
$strFilterActive = getPostValue('filterActive');
$isNew = ($strDataID == "");
if ($bolCanEdit) {
  $f = new clsForm("formInput", 2, "100%", "");
  $f->caption = strtoupper($strWordsINPUTDATA);
  $f->addHidden("dataID", $strDataID);
  $f->addTextArea(getWords("question"), "dataQuestion", "", ["cols" => 90, "rows" => 1], "string", true, true, true);
  $f->addTextArea(
      getWords("answer options"),
      "dataOptions",
      "",
      ["cols" => 90, "rows" => 1],
      "string",
      false,
      true,
      true,
      "",
      "<br>" . getWords("use pipe '|' symbol as delimiter")
  );
  $f->addCheckBox(getWords("active"), "dataActive", true);
  $f->addInput(getWords("sequence"), "dataSequence", "", ["size" => 5], "integer", true, true, true);
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
$fFilter = new clsForm("formFilter", 1, "100%", "");
$fFilter->caption = strtoupper($strWordsFILTERDATA);
$fFilter->addSelect(
    getWords("active"),
    "filterActive",
    getDataListActive(),
    ["onChange" => "document.formFilter.submit()"],
    "",
    false
);
$fFilter->hasButton = false;
$formFilter = $fFilter->render();
$myDataGrid = new cDataGrid("formData", "DataGrid");
$myDataGrid->caption = strtoupper(getWords($dataPrivilege['menu_name']));
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", "", ['width' => '15', 'valign' => 'top', 'align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", "", ['width' => '30', 'nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("sequence"), "sequence", ['width' => '100'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("question"), "question", ""));
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("answer options"),
        "options",
        "",
        ['width' => '300'],
        false,
        false,
        "",
        "printAnswerOption()"
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("active"),
        "active",
        "",
        ['width' => '30', 'valign' => 'top', 'align' => 'center', 'nowrap' => ''],
        false,
        false,
        "",
        "printActiveSymbol()"
    )
);
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
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_training_need_criteria ";
$strSQL = "SELECT * FROM hrd_training_need_criteria ";
if ($strFilterActive != "") {
  $strFilterActive = ($strFilterActive) ? "t" : "f";
  $strSQL .= " WHERE active = '$strFilterActive'";
  $strSQLCOUNT .= " WHERE active = '$strFilterActive'";
}
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$intNextSequence = $myDataGrid->totalData + 1;
echo $intNextSequence;
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
function printAnswerOption($params)
{
  extract($params);
  return str_replace("|", "<br>- ", str_replace("| ", "<br>- ", "- " . $value));
}

function printEditLink($params)
{
  extract($params);
  return
      generateHidden("detailID$counter", $record['id']) .
      generateHidden("detailQuestion$counter", $record['question']) .
      generateHidden("detailOptions$counter", $record['options']) .
      generateHidden("detailSequence$counter", $record['sequence']) .
      generateHidden("detailActive$counter", $record['active']) .
      "<a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $isNew;
  $arrScore = [];
  $db = new CdbClass;
  $bolSave = true;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $tblHrdTrainingNeedCriteria = new cHrdTrainingNeedCriteria();
  if ($bolSave) {
    $data = [
        "question" => $f->getValue('dataQuestion'),
        "options"  => $f->getValue('dataOptions'),
        "sequence" => $f->getValue('dataSequence'),
        "active"   => ($f->getValue('dataActive') == 't') ? "t" : "f"
    ];
    // simpan data -----------------------
    $bolSuccess = false;
    if ($isNew) {
      // data baru
      $bolSuccess = $tblHrdTrainingNeedCriteria->insert($data);
      if ($bolSuccess) {
        $f->setValue('dataID', $tblHrdTrainingNeedCriteria->getLastInsertId());
      }
    } else {
      $bolSuccess = $tblHrdTrainingNeedCriteria->update(/*pk*/
          "id='" . $f->getValue('dataID') . "'", /*data to update*/
          $data
      );
    }
  }
  $f->message = $tblHrdTrainingNeedCriteria->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $tblHrdTrainingNeedCriteria = new cHrdTrainingNeedCriteria();
  $tblHrdTrainingNeedCriteria->deleteMultiple($arrKeys);
  $myDataGrid->message = $tblHrdTrainingNeedCriteria->strMessage;
} //deleteData
?>