<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid/datagrid.php');
include_once('../includes/form2/form2.php');
// include_once('../includes/model/model.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$strWordsRecruitmentProcessType = getWords("recruitment process type");
$strWordsInterviewQuestion = getWords("interview question");
$strWordsDriverQuestion = getWords("driver question");
$arrPrintType = [ // sekedar menentukan apakah perlu di print form atau gak, di datanet, khusus interview
                  0 => "",
                  1 => "Interview Form",
                  2 => "Driver Form"
];
$db = new CdbClass;
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
$tbl = new cModel("hrd_recruitment_process_type", "recruitment process type");
$formInput = "";
if ($bolCanEdit) {
  $f = new clsForm("formInput", 1, "100%", "");
  $f->caption = strtoupper(vsprintf(getWords("input data %s"), getWords("recruitment process type")));
  $f->addHidden("dataID", $strDataID);
  $f->addInput(getWords("name"), "name", "", ["size" => 50], "string", true, true, true);
  $f->addSelect(getWords("step"), "step", getDataListRecruitmentProcessTypeStep(null, true), array(), "string");
  $f->addSelect(getWords("form"), "letter_type", $arrPrintType, array(), "string");
  $f->addTextArea(getWords("note"), "note", "", ["cols" => 48, "rows" => 2], "string", false, true, true);
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
  // $f->addButton("btnAdd", getWords("add new"), array("onClick" => "javascript:myClient.editData(0);"));
  $f->addButton(
      "btnAdd",
      getWords("add new"),
      ["onClick" => "javascript:window.location.href='data_recruitment_process_type.php'"]
  );
  $formInput = $f->render();
}
$myDataGrid = new cDataGrid("formData", "DataGrid1");
$myDataGrid->caption = getWords(strtoupper(vsprintf(getWords("list of %s"), getWords("recruitment process type"))));
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", ['width' => 30], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(new DataGrid_Column("No.", "", ['width' => 30], ['nowrap' => 'nowrap']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("step"), "step", ['width' => 70], ['nowrap' => 'nowrap']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("name"), "name", ['width' => 200], ['nowrap' => 'nowrap'], true, true, "", "")
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("form"), "letter_type", null, ['nowrap' => ''], false, false, "", "printLetter()")
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", null, ['nowrap' => '']));
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
$DataGrid = $myDataGrid->render();
$strConfirmDelete = getWords("are you sure to delete this selected data?");
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('recruitment process type management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
// handle tampilan jenis print form
function printLetter($params)
{
  extract($params);
  if ($value == "1") {
    $form = "Interview";
  } elseif ($value == "2") {
    $form = "Driver";
  } else {
    $form = "";
  }
  return $form;
}

function printEditLink($params)
{
  extract($params);
  return
      generateHidden("detailID$counter", $record['id'], "disabled") .
      generateHidden("name$counter", $record['name'], "disabled") .
      generateHidden("step$counter", $record['step'], "disabled") .
      generateHidden("letter_type$counter", $record['letter_type'], "disabled") .
      generateHidden("note$counter", $record['note'], "disabled") . "
      <a id=\"editprocesstype-".$counter."\" class=\"edit-data\" href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $isNew;
  global $strDataID;
  global $tbl;
  global $db;
  $data = $_REQUEST;
  // print_r($data);
  // die();
  // simpan data -----------------------
  $bolSuccess = false;
  ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
  if (isDataExists($db, "hrd_recruitment_process_type", "name", $data['name'], $strKriteria)) {
    $strError = getWords('duplicate_code') . "  -> {$data['name']}";
    return false;
  }
  if (!is_numeric($data['step'])) {
    // cari apakah step terakhir
    $strDataStep = 0;
    $strSQL = "SELECT MAX(step) AS maks FROM hrd_recruitment_process_type ";
    $arrResult = $tbl->query($strSQL);
    $data['step'] = 1;
    if (count($arrResult) > 0) {
      $data['step'] = intval($arrResult[0]['maks']) + 1;
    }
  }
  if ($isNew) {
    $strSQL1 = "SELECT id, step FROM hrd_recruitment_process_type WHERE step >= " . $data['step'] . " order by step ASC";
    $arrResult1 = $tbl->query($strSQL1);
    $dataAwal = $arrResult1[0]['step'];
    foreach ($arrResult1 as $loop) {
      if ($loop['step'] == $dataAwal) {
        $strSQL = "UPDATE hrd_recruitment_process_type SET step = " . ($loop["step"] + 1) . " WHERE id = " . $loop["id"] . " ";
        $tbl->execute($strSQL);
        //echo ($loop["step"]+1)." | ";
      }
      $dataAwal++;
    }
    if ($bolSuccess = $tbl->insert($data)) {
      $f->setValue('dataID', $tbl->getLastInsertID());
      $dataName = $data['name'];
    }
  } else {
    if ($rowDb = $tbl->findById($strDataID, "step")) {
      $intCurrStep = $rowDb['step'];
      if ($intCurrStep < $data['step']) { // step jadi lebih banyak
        $strSQL = "UPDATE hrd_recruitment_process_type SET step = step - 1 ";
        $strSQL .= "WHERE step > '$intCurrStep' AND step <= '" . $data['step'] . "' ";
        $tbl->execute($strSQL);
      } else if ($intCurrStep > $data['step']) { // step jadi lebih sedikit
        $strSQL = "UPDATE hrd_recruitment_process_type SET step = step + 1 ";
        $strSQL .= "WHERE step < '$intCurrStep' AND step >= '" . $data['step'] . "' ";
        $tbl->execute($strSQL);
      }
    }
    if ($bolSuccess = $tbl->update(/*pk*/
        "id='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    )
    ) {
      $dataName = $data['name'];
    }
  }
  if ($bolSuccess) {
    $f->message = $tbl->strMessage;
    //$f->setValues('step', getDataListRecruitmentProcessTypeStep(null, true));
    writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "{$dataName}", 0);
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
    $f->setValues('step', getDataListRecruitmentProcessTypeStep(null, true));
  } else {
    $myDataGrid->errorMessage = "Failed to delete data " . $tbl->strEntityName;
  }
} //deleteData
?>
