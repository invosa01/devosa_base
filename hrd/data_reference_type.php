<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
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
  die(getWords('view denied'));
}
$arrPrintType = [ // sekedar menentukan apakah perlu di print form atau gak, di datanet, khusus interview
                  0 => "",
                  1 => "Interview Form",
];
$db = new CdbClass;
$arrCategory = [
    1 => "Media Cetak",
    2 => "Media Elektronik",
    3 => "Out Source",
    4 => "Rekomendasi"
];
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
$tbl = new cModel("hrd_candidate_reference", "reference type");
$formInput = "";
if ($bolCanEdit) {
  $f = new clsForm("formInput", 1, "100%", "");
  $f->caption = strtoupper(vsprintf(getWords("input data %s"), getWords("reference type")));
  $f->addHidden("dataID", $strDataID);
  $f->addSelect(getWords("type"), "type", $arrCategory, [], "string", true, true, true);
  $f->addInput(getWords("reference"), "reference", "", ["size" => 50], "string", true, true, true);
  $f->addSubmit(
      "btnSave",
      getWords("save"),
      ["onClick" => "return confirm('" . getWords('do you want to save this entry?') . "');"],
      true,
      true,
      "",
      "",
      "saveData()"
  );
  $f->addButton("btnAdd", getWords("add new"), ["onClick" => "javascript:myClient.editData(0);"]);
  $f->_getRequest();
}
$myDataGrid = new cDataGrid("formData", "DataGrid1");
$myDataGrid->caption = getWords(strtoupper(vsprintf(getWords("list of %s"), getWords("reference type"))));
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", ['width' => 30], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(new DataGrid_Column("No.", "", ['width' => 30], ['nowrap' => 'nowrap']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("type"), "type", null, ['nowrap' => ''], true, false, "", "printCategory()")
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("reference"), "reference", [], ['nowrap' => 'nowrap'], true, true, "", "")
);
if ($bolCanEdit) {
  $myDataGrid->addColumn(
      new DataGrid_Column(
          "",
          "",
          ['width' => 60],
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
      getWords("delete"),
      "onClick=\"javascript:return myClient.confirmDelete();\"",
      "deleteData()"
  );
}
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strCriteriaFlag = $myDataGrid->getCriteria();
$myDataGrid->totalData = $tbl->findCount($strCriteriaFlag);
$dataset = $tbl->findAll(
    $strCriteriaFlag,
    null /* all fields */,
    $myDataGrid->getSortBy(),
    $myDataGrid->getPageLimit(),
    $myDataGrid->getPageNumber()
);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$formInput = $f->render();
$DataGrid = $myDataGrid->render();
$strConfirmDelete = getWords("are you sure to delete this selected data?");
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('reference type management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
// handle tampilan jenis print form
function printCategory($params)
{
  global $arrCategory;
  extract($params);
  return (isset($arrCategory[$value])) ? ($arrCategory[$value]) : "";
}

function printEditLink($params)
{
  extract($params);
  return
      generateHidden("detailID$counter", $record['id'], "disabled") .
      generateHidden("reference$counter", $record['reference'], "disabled") .
      generateHidden("type$counter", $record['type'], "disabled") . "
      <a id=\"edittype-$counter\" class=\"edit-data\" href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $isNew;
  global $strDataID;
  global $tbl;
  global $arrCategory;
  $data = $_POST;
  // simpan data -----------------------
  $bolSuccess = false;
  ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";/*
    if (isDataExists("hrd_candidate_reference", "name", $data['name'], $strKriteria)) 
    {
      $strError = getWords('duplicate_code'). "  -> {$data['name']}";
      return false;
    }
     */
  $data['name'] = (isset($arrCategory[$data['type']])) ? $arrCategory[$data['type']] : "";
  if ($isNew) {
    $bolSuccess = $tbl->insert($data);
  } else {
    $bolSuccess = $tbl->update(/*pk*/
        "id='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  //---------- END Jika Data Baru---------------------------------------------//
  //---------------BEGIN Jika data yang berhasil disimpan-----------------//
  if ($bolSuccess) {
    if ($isNew) {
      $f->setValue('dataID', $tbl->getLastInsertId());
    } else {
      $f->setValue('dataID', $f->getValue('dataID'));
    }
  }
  //---------------END Jika data yang berhasil disimpan--------------------//
  if ($bolSuccess) {
    $f->message = $tbl->strMessage;
    //$f->setValues('step', getDataListRecruitmentProcessTypeStep(null, true));
  } else {
    $f->errorMessage = $tbl->strMessage;
  }
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  global $f;
  global $tbl;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  if ($tbl->deleteMultiple($arrKeys)) {
    $myDataGrid->message = $tbl->strMessage;
  } else {
    $myDataGrid->errorMessage = "Failed to delete data " . $tbl->strEntityName;
  }
} //deleteData
?>