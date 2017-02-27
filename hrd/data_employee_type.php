<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_employee_type.php');
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
  $f->caption = strtoupper(vsprintf(getWords("input data %s"), getWords("employee type")));
  $f->addHidden("dataID", $strDataID);
  $f->addInput(getWords("employee type code"), "dataCode", "", ["size" => 50], "string", true, true, true);
  $f->addInput(getWords("employee type name"), "dataName", "", ["size" => 50], "string", true, true, true);
  $f->addCheckBox(getWords("is employee?"), "dataIsEmployee", false, []);
  $f->addCheckBox(getWords("has leave?"), "dataIsLeave", false, []);
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
$myDataGrid->caption = getWords(strtoupper(vsprintf(getWords("list of %s"), getWords("employee type"))));
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "code", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("employee type code"), "code", ['width' => '120'], ['nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("employee type name"), "name", ['width' => ''], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("is employee"),
        "is_employee",
        ['width' => '80'],
        ['align' => 'center'],
        true,
        false,
        "",
        "printIsEmployee()"
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("has leave"),
        "is_leave",
        ['width' => '80'],
        ['align' => 'center'],
        true,
        false,
        "",
        "printHasLeave()"
    )
);
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
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_employee_type ";
$strSQL = "SELECT * FROM hrd_employee_type ";
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
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['code'] . "' />
      <input type=hidden name='detailCode$counter' id='detailCode$counter' value='" . $record['code'] . "' />
      <input type=hidden name='detailName$counter' id='detailName$counter' value='" . $record['name'] . "' />
      <input type=hidden name='detailIsEmployee$counter' id='detailIsEmployee$counter' value='" . $record['is_employee'] . "' />
      <input type=hidden name='detailIsLeave$counter' id='detailIsLeave$counter' value='" . $record['is_leave'] . "' />
      <a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

function printIsEmployee($params)
{
  extract($params);
  if ($record['is_employee'] == 't') {
    return "&radic;";
  } else {
    return "-";
  }
}

function printHasLeave($params)
{
  extract($params);
  if ($record['is_leave'] == 't') {
    return "&radic;";
  } else {
    return "-";
  }
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $isNew;
  $dataHrdEmployeeType = new cHrdEmployeeType();
  $strIsEmployee = ($f->getValue('dataIsEmployee')) ? 't' : 'f';
  $strHasLeave = ($f->getValue('dataIsLeave')) ? 't' : 'f';
  $data = [
      "code" => $f->getValue('dataCode'),
      "name" => $f->getValue('dataName'),
      "is_employee" => $strIsEmployee,
      "is_leave" => $strHasLeave
  ];
  // simpan data -----------------------
  if ($isNew) {
    // data baru
    $bolSuccess = $dataHrdEmployeeType->insert($data);
  } else {
    $bolSuccess = $dataHrdEmployeeType->update(/*pk*/
        "code='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  if ($bolSuccess) {
    $f->setValue('dataID', $f->getValue('dataID'));
  }
  $f->message = $dataHrdEmployeeType->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['code'][] = $strValue;
  }
  $dataHrdEmployeeType = new cHrdEmployeeType();
  $dataHrdEmployeeType->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdEmployeeType->strMessage;
} //deleteData
?>