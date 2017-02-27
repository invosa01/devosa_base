<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_loan_type.php');
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
$strWordsLoanType = getWords("loan type");
$strWordsLoanPurpose = getWords("loan purpose");
$db = new CdbClass;
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
if ($bolCanEdit) {
  $f = new clsForm("formInput", 3, "100%", "");
  $f->caption = strtoupper($strWordsINPUTDATA);
  $f->addHidden("dataID", $strDataID);
  $f->addInput(getWords("loan type"), "dataType", "", ["size" => 100, "maxlength" => 127], "string", true, true, true);
  $f->addCheckBox(getWords("paid to external party"), "dataExternalTransfer", false);
  $f->addInput(
      getWords("account no."),
      "dataExternalAccount",
      "",
      ["size" => 100, "maxlength" => 127],
      "string",
      false
  );
  $f->addInput(
      getWords("account name"),
      "dataExternalAccountName",
      "",
      ["size" => 100, "maxlength" => 127],
      "string",
      false
  );
  $f->addSelect(getWords("bank"), "dataExternalBankCode", getDataListBank("", true, ""), "", "", false);
  $f->addInput(
      getWords("weight"),
      "weight",
      "0",
      ["size" => 10, "maxlength" => 10],
      "numeric",
      false,
      true,
      true
  );
  $f->addTextArea(getWords("note"), "dataNote", "", ["cols" => 50, "rows" => 1], "string", false, true, true);
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
$myDataGrid->pageSortBy = "type";
if (!isset($_REQUEST['btnExportXLS'])) {
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "id", ["rowspan" => 2, 'width' => '30'], ['align' => 'center', 'nowrap' => ''])
  );
}
$myDataGrid->addColumnNumbering(
    new DataGrid_Column(getWords("no."), "", ["rowspan" => 2, 'width' => '30'], ['nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("loan type"), "type", ["rowspan" => 2, 'width' => '150'], ['nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("paid to external party"),
        "external_transfer",
        ["rowspan" => 2],
        ['width' => '15', 'valign' => 'top', 'align' => 'center', 'nowrap' => ''],
        false,
        false,
        "",
        "printActiveSymbol()"
    )
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("transfered to:"), "", ["colspan" => 3]));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("account no."), "external_account", ['width' => '150'], ['nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("account name"), "external_account_name", ['width' => '150'], ['nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("bank"), "bank_name", ['width' => '150'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("weight"), "weight", ["rowspan" => 2, 'width' => '70'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", ["rowspan" => 2]));
if (!isset($_REQUEST['btnExportXLS'])) {
  if ($bolCanEdit) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "",
            "",
            ['width' => '60', "rowspan" => 2],
            ['align' => 'center', 'nowrap' => ''],
            false,
            false,
            "",
            "printEditLink()",
            "",
            false
        )
    );
  } //show in excel
}
// generateRoleButtons($dataPrivilege['edit'], $dataPrivilege['delete'], $dataPrivilege['check'], $dataPrivilege['approve'], true, true, $myDataGrid);
generateRoleButtons($dataPrivilege['edit'], $dataPrivilege['delete'], false, false, false, true, $myDataGrid);
$myDataGrid->addButtonExportExcel(
    getWords("export excel"),
    $dataPrivilege['menu_name'] . ".xls",
    getWords($dataPrivilege['menu_name'])
);
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_loan_type";
$strSQL = "SELECT t1.*, t2.bank_name FROM hrd_loan_type AS t1 LEFT JOIN hrd_bank AS t2 ON t1.external_bank_code = t2.bank_code ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('loan type management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = loanSubMenu($strWordsLoanType);
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
      <input type=hidden name='detailType$counter' id='detailType$counter' value='" . $record['type'] . "' />
      <input type=hidden name='detailExternalTransfer$counter' id='detailExternalTransfer$counter' value='" . $record['external_transfer'] . "' />
      <input type=hidden name='detailExternalAccount$counter' id='detailExternalAccount$counter' value='" . $record['external_account'] . "' />
      <input type=hidden name='detailExternalAccountNam$counter' id='detailExternalAccountName$counter' value='" . $record['external_account_name'] . "' />
      <input type=hidden name='detailExternalBankCode$counter' id='detailExternalBankCode$counter' value='" . $record['external_bank_code'] . "' />
      <input type=hidden name='detailNote$counter' id='detailNote$counter' value='" . $record['note'] . "' />
      <input type=hidden name='detailWeight$counter' id='detailWeight$counter' value='" . $record['weight'] . "' />
      <a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $error;
  $isNew = ($f->getValue('dataID') == "");
  $dataLoanType = new cHrdLoanType();
  $strModifiedByID = $_SESSION['sessionUserID'];
  // cek validasi -----------------------
  $strKriteria = ($isNew) ? "" : "AND id <> '" . $f->getValue('dataID') . "' ";
  $data = [
      "type"                  => $f->getValue('dataType'),
      "external_transfer"     => ($f->getValue('dataExternalTransfer') == "") ? "f" : "t",
      "external_account"      => $f->getValue('dataExternalAccount'),
      "external_account_name" => $f->getValue('dataExternalAccountName'),
      "external_bank_code"    => $f->getValue('dataExternalBankCode'),
      "note"                  => $f->getValue('dataNote'),
      "weight"                => $f->getValue('weight')
  ];
  // simpan data -----------------------
  $bolSuccess = false;
  if ($isNew) {
    // data baru
    $bolSuccess = $dataLoanType->insert($data);
  } else {
    $bolSuccess = $dataLoanType->update(/*pk*/
        "id='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  if ($bolSuccess) {
    if ($isNew) {
      $f->setValue('dataID', $dataLoanType->getLastInsertId());
    } else {
      $f->setValue('dataID', $f->getValue('dataID'));
    }
  }
  $f->message = $dataLoanType->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $dataLoanType = new cHrdLoanType();
  $dataLoanType->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataLoanType->strMessage;
} //deleteData
?>