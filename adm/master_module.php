<?php
include_once('../global/session.php');
include_once('../global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/adm/adm_module.php');
include_once("../global/common_function.php");
$dataPrivilege = getDataPrivileges("master_module.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$strMessage = "";
$db = new CdbClass();
$strAction = getRequestValue('act');
$intDataID = getRequestValue('dataID');
if ($strAction == "desc") {
  goSortOrder($intDataID, false);
} else if ($strAction == "asc") {
  goSortOrder($intDataID, true);
}
if ($bolCanEdit) {
  $f = new clsForm("formInput", 1, "100%", "");
  $f->caption = strtoupper(vsprintf(getWords("input data %s"), getWords("module")));
  //$f->addHelp(getWords("help for")." ".$dataPrivilege['menu_name'], getHelps("master module"), 8, 167, 400, 300);
  $f->addHidden("dataID");
  $f->addInput(getWords("module name"), "dataName", "", ["size" => 50], "string", true, true, true);
  $f->addInput(
      getWords("folder name"),
      "dataFolder",
      "",
      ["size" => 50],
      "string",
      true,
      true,
      true,
      "",
      "&nbsp;" . getWords("Note: Relative to application folder")
  );
  $f->addCheckBox(getWords("visible"), "dataVisible", false);
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
  $f->addButton("btnAdd", getWords("add new user"), ["onClick" => "javascript:myClient.editData(0);"]);
  $formInput = $f->render();
} else {
  $formInput = "";
}
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", true, true, false);
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
if ($bolCanDelete) {
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "id_adm_module", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
  );
}
//$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", array('width'=>'30'), array('nowrap'=>'')));
$myDataGrid->addColumn(new DataGrid_Column(getWords("name"), "name", null, ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("folder"), "folder", ['width' => '150'], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("status"), "visible", ['width' => '100'], ['align' => 'center'], true, false)
);
if ($bolCanEdit) {
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("order"), "order", ['width' => '40'], ['align' => 'center'], false, false)
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
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM adm_module ";
$strSQL = "SELECT * FROM adm_module ORDER BY sequence_no ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
$counter = 0;
foreach ($dataset as &$rowDb) {
  $counter++;
  if ($bolCanEdit) {
    //add hidden when able to EDIT
    $rowDb['name'] = "
        <input type=\"hidden\" name=\"detailID$counter\" id=\"detailID$counter\" value=\"" . $rowDb['id_adm_module'] . "\">
        <input type=\"hidden\" name=\"detailName$counter\" id=\"detailName$counter\" value=\"" . $rowDb['name'] . "\">
        <input type=\"hidden\" name=\"detailFolder$counter\" id=\"detailFolder$counter\" value=\"" . $rowDb['folder'] . "\">
        <input type=\"hidden\" name=\"detailVisible$counter\" id=\"detailVisible$counter\" value=\"" . $rowDb['visible'] . "\" disabled>
        <a href='javascript:myClient.editData($counter)'>" . $rowDb['name'] . "</a>";
    $rowDb['order'] = "<a href=\"master_module.php?dataID=" . $rowDb['id_adm_module'] . "&act=asc\"><img src=../images/asc.gif width=11 height=11 border=0></a><a href=\"master_module.php?dataID=" . $rowDb['id_adm_module'] . "&act=desc\"><img src=../images/desc.gif width=11 height=11 border=0></a>";
  }
  if ($rowDb['visible'] == 't') {
    $rowDb['visible'] = strtolower(getWords("visible"));
  } else {
    $rowDb['visible'] = strtolower(getWords("no show"));
  }
}
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$tbsPage = new clsTinyButStrong;
$strWordModuleList = strtoupper(vsprintf(getWords("list of %s"), getWords("module")));
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("module management");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
//--------------------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  $strDataVisible = ($f->value('dataVisible')) ? 't' : 'f';
  $dataModule = new cAdmModule();
  // simpan data -----------------------
  $data = [
      "name" => strtoupper($f->getValue('dataName')),
      "visible" => $strDataVisible,
      "folder" => $f->getValue('dataFolder'),
  ];
  if ($f->getValue('dataID') == "") {
    // data baru
    $data["sequence_no"] = intval($dataModule->getMaxSequenceNo() + 1);
    $dataModule->insert($data);
  } else {
    $dataModule->update(["id_adm_module" => $f->getValue('dataID')], $data);
  }
  $f->message = $dataModule->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id_adm_module'][] = $strValue;
  }
  $dataModule = new cAdmModule();
  $dataModule->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataModule->strMessage;
} //deleteData
function goSortOrder($intDataID, $modeAsc)
{
  $dataModule = new cAdmModule();
  $arrData = $dataModule->findAll(null, null, "ORDER BY sequence_no");
  $prevData = null;
  $currData = null;
  if ($modeAsc) {
    //ascending
    foreach ($arrData as $data) {
      if ($data['id_adm_module'] == $intDataID) {
        $currData = $data;
        break;
      }
      $prevData = $data;
    }
  } else {
    $isFound = false;
    foreach ($arrData as $data) {
      $prevData = $data;
      if ($isFound) {
        break;
      }
      if ($data['id_adm_module'] == $intDataID) {
        $currData = $data;
        $isFound = true;
      }
    }
  }
  if ($prevData != null && $currData != null) {
    $dataModule->begin();
    if ($dataModule->update(
        ["id_adm_module" => $prevData['id_adm_module']],
        ["sequence_no" => $currData['sequence_no']]
    )
    ) {
      if ($dataModule->update(
          ["id_adm_module" => $currData['id_adm_module']],
          ["sequence_no" => $prevData['sequence_no']]
      )
      ) {
        $dataModule->commit();
      } else {
        $dataModule->rollback();
      }
    } else {
      $dataModule->rollback();
    }
  }
}

?>