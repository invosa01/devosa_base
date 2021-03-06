<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('form_object.php');
include_once('../classes/hrd/hrd_tax_paid_before.php');
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
if ($db->connect()) {
  $strDataID = getPostValue('dataID');
  $isNew = ($strDataID == "");
  // $strDataApprover = (isset($_REQUEST['approver_id'])) ?  $_REQUEST['approver_id'] : $strDataApprover = "";
  // $strSet = "position";
  if ($bolCanEdit) {
    $f = new clsForm("formInput", 3, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    //    $dataOrganizationStructure = $dataHrdOrganization->generateList(null, "levelling", null, "id", "name");
    $f->addHidden("dataID", $strDataID);
    $f->addInputAutoComplete(
        getwords("employee id"),
        "employee_id",
        getDataEmployee($strDataEmployee),
        "style=width:$strDefaultWidthPx " . $strReadonly,
        "string",
        false
    );
    $f->addLabelAutoComplete("", "employee_name", "");
    $f->addSelect("Year", "year", getDataYear(), ["style" => "width:$strDefaultWidthPx"], "", true);
    $f->addInput(
        getWords("Taxable Regular Income"),
        "taxable_regular_income",
        "0",
        ["size" => 30, "maxlength" => 20],
        "numeric",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("Regular Tax Paid Before"),
        "tax_regular_paid",
        "0",
        ["size" => 30, "maxlength" => 20],
        "numeric",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("Taxable Irregular Income"),
        "taxable_irregular_income",
        "0",
        ["size" => 30, "maxlength" => 20],
        "numeric",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("Irregular Tax Paid Before"),
        "tax_irregular_paid",
        "0",
        ["size" => 30, "maxlength" => 20],
        "numeric",
        false,
        true,
        true
    );
    $f->addTextArea(getWords("note"), "note", "", ["cols" => 97, "rows" => 2], "string", false, true, true);
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
    $f->addButton("btnAdd", getWords("add new"), ["onClick" => "javascript:myClient.editData('0|position|3');"]);
    $formInput = $f->render();
  } else {
    $formInput = "";
  }
  $myDataGrid = new cDataGrid("formData", "DataGrid1");
  $myDataGrid->caption = strtoupper($strWordsLISTOF . " " . getWords($dataPrivilege['menu_name']));
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
  );
  $myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no."), "", ['width' => '30'], ['nowrap' => '']));
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("Employee Name"), "employee_id", ['width' => '200'], ['nowrap' => ''])
  );
  $myDataGrid->addColumn(new DataGrid_Column(getWords("Year"), "year", ['width' => '200'], ['nowrap' => '']));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("Taxable Regular Income"),
          "taxable_regular_income",
          ['width' => '200'],
          ['nowrap' => '']
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("Regular Tax Paid Before"), "tax_regular_paid", ['width' => '200'], ['nowrap' => ''])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("Taxable Irregular Income"),
          "taxable_irregular_income",
          ['width' => '200'],
          ['nowrap' => '']
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("Irregular Tax Paid Before"),
          "tax_irregular_paid",
          ['width' => '200'],
          ['nowrap' => '']
      )
  );
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
  //tampilkan buttons sesuai dengan otoritas, common_function.php
  generateRoleButtons($bolCanEdit, $bolCanDelete, $bolCanCheck, $bolCanApprove, $bolCanAcknowledge, false, $myDataGrid);
  $myDataGrid->addButtonExportExcel(
      getWords("export excel"),
      $dataPrivilege['menu_name'] . ".xls",
      getWords($dataPrivilege['menu_name'])
  );
  $myDataGrid->getRequest();
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_tax_paid_before ";
  $strSQL = "SELECT * FROM hrd_tax_paid_before";// as t0 LEFT JOIN hrd_employee as t1 where t0.employee_id = t1.employee_id ";
  $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
  $dataset = $myDataGrid->getData($db, $strSQL);
  //bind Datagrid with array dataset
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
  $strConfirmSave = getWords("do you want to save this entry?");
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('New Employee Tax Before');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
//--------------------------------------------------------------------------------
function printEditLink($params)
{
  global $strSet;
  $strResult = "";
  extract($params);
  return "
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['id'] . "' />
      <input type=hidden name='detailIDEmployee$counter' id='detailIDEmployee$counter' value='" . $record['employee_id'] . "' />
      <input type=hidden name='detailYear$counter' id='detailYear$counter' value='" . $record['year'] . "' />
      <input type=hidden name='detailTaxableRegularIncome$counter' id='detailTaxableRegularIncome$counter' value='" . $record['taxable_regular_income'] . "' />
      <input type=hidden name='detailTaxableIrregularIncome$counter' id='detailTaxableIrregularIncome$counter' value='" . $record['taxable_irregular_income'] . "' />
      <input type=hidden name='detailRegularTaxPaid$counter' id='detailRegularTaxPaid$counter' value='" . $record['tax_regular_paid'] . "' />
      <input type=hidden name='detailIrregularTaxPaid$counter' id='detailIrregularTaxPaid$counter' value='" . $record['tax_irregular_paid'] . "' />
      <input type=hidden name='detailNote$counter' id='detailNote$counter' value='" . $record['note'] . "' />
      <a href=\"javascript:myClient.editData('$counter" . "|$strSet|" . MAX_ALLOWANCE_SET . "')\">" . getWords(
      'edit'
  ) . "</a>" . $strResult;
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $db;
  global $error;
  global $isNew;
  global $strSet;
  // $strDataCode = $f->getValue('id');
  $dataHrdTaxPaidBefore = new cHrdTaxPaidBefore();
  $data = [
      "employee_id"              => $f->getValue('employee_id'),
      "year"                     => $f->getValue('year'),
      "taxable_regular_income"   => $f->getValue('taxable_regular_income'),
      "taxable_irregular_income" => $f->getValue('taxable_irregular_income'),
      "tax_regular_paid"         => $f->getValue('tax_regular_paid'),
      "tax_irregular_paid"       => $f->getValue('tax_irregular_paid'),
      "note"                     => $f->getValue('note')
  ];
  // simpan data -----------------------
  $bolSuccess = false;
  // data baru
  if ($isNew) {
    $bolSuccess = $dataHrdTaxPaidBefore->insert($data);
  } else {
    $bolSuccess = $dataHrdTaxPaidBefore->update(/*pk*/
        "id='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  if ($bolSuccess) {
    $f->setValue('dataID', $data['id']);
    $f->message = $dataHrdTaxPaidBefore->strMessage;
  }
  $f->msgClass = ($bolSuccess) ? "bgOK" : "bgError";
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $dataHrdTaxPaidBefore = new cHrdTaxPaidBefore();
  $dataHrdTaxPaidBefore->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdTaxPaidBefore->strMessage;
} //deleteData
function getDataYear()
{
  $currYear = intval(date("Y"));
  $arrResult = [];
  for ($i = $currYear; $i > $currYear - 10; $i--) {
    if ($i == $currYear) {
      $arrResult[] = ["value" => $i, "text" => $i, "selected" => true];
    } else {
      $arrResult[] = ["value" => $i, "text" => $i, "selected" => false];
    }
  }
  return $arrResult;
}

?>
