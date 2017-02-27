<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_institution.php');
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
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
if ($bolCanEdit) {
  $f = new clsForm("formInput", 1, "100%", "");
  $f->caption = strtoupper($strWordsINPUTDATA);
  $f->addHidden("dataID", $strDataID);
  $f->addInput(
      getWords("institution code"),
      "dataCode",
      "",
      ["size" => 30, "maxlength" => 31],
      "string",
      true,
      true,
      true
  );
  $f->addInput(
      getWords("institution name"),
      "dataName",
      "",
      ["size" => 30, "maxlength" => 63],
      "string",
      true,
      true,
      true
  );
  $f->addInput(getWords("address"), "dataAddress", "", ["size" => 30, "maxlength" => 127], "string", true, true, true);
  $f->addInput(getWords("phone"), "dataPhone", "", ["size" => 30, "maxlength" => 31], "string", true, true, true);
  $f->addInput(
      getWords("contact person"),
      "dataPIC",
      "",
      ["size" => 30, "maxlength" => 31],
      "string",
      true,
      true,
      true
  );
  $f->addInput(getWords("subject"), "subject", "", ["size" => 30, "maxlength" => 31], "string", true, true, true);
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
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("institution code"), "institution_code", ['width' => '150'], ['nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("institution name"), "institution_name", ""));
$myDataGrid->addColumn(new DataGrid_Column(getWords("address"), "address", ""));
$myDataGrid->addColumn(new DataGrid_Column(getWords("phone"), "phone", ""));
$myDataGrid->addColumn(new DataGrid_Column(getWords("contact person"), "pic", ""));
$myDataGrid->addColumn(new DataGrid_Column(getWords("subject"), "subject", ""));
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
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_institution ";
$strSQL = "SELECT * FROM hrd_institution ";
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
      <input type=hidden name='detailCode$counter' id='detailCode$counter' value='" . $record['institution_code'] . "' />
      <input type=hidden name='detailName$counter' id='detailName$counter' value='" . $record['institution_name'] . "' />
      <input type=hidden name='detailAddress$counter' id='detailAddress$counter' value='" . $record['address'] . "' />
      <input type=hidden name='detailPhone$counter' id='detailPhone$counter' value='" . $record['phone'] . "' />
      <input type=hidden name='detailPIC$counter' id='detailPIC$counter' value='" . $record['pic'] . "' />
	  <input type=hidden name='detailSubject$counter' id='detailSubject$counter' value='" . $record['subject'] . "' />
      <a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

function printFormat($params)
{
  extract($params);
  return number_format($record['institution']);
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $isNew;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $dataHrdInstitution = new cHrdInstitution();
  $data = [
      "institution_code" => $f->getValue('dataCode'),
      "institution_name" => $f->getValue('dataName'),
      "address"          => $f->getValue('dataAddress'),
      "phone"            => $f->getValue('dataPhone'),
      "pic"              => $f->getValue('dataPIC'),
      "subject"          => $f->getValue('dataSubject')
  ];
  // simpan data -----------------------
  $bolSuccess = false;
  if ($isNew) {
    // data baru
    $bolSuccess = $dataHrdInstitution->insert($data);
  } else {
    $bolSuccess = $dataHrdInstitution->update(/*pk*/
        "id='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  if ($bolSuccess) {
    if ($isNew) {
      $f->setValue('dataID', $dataHrdInstitution->getLastInsertId());
    } else {
      $f->setValue('dataID', $f->getValue('dataID'));
    }
  }
  $f->message = $dataHrdInstitution->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $dataHrdInstitution = new cHrdInstitution();
  $dataHrdInstitution->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdInstitution->strMessage;
} //deleteData
?>