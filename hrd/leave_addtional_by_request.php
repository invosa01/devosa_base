<?php
//by Farhan (21 Agustus 2009)
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_leave_additional_by_request.php');
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
  //$f->addInput(getWords("employee"), "id_employee", "", array(), "number", true, true, true);
  //=================Auto Complete===
  $autoCompleteValue = getInitialValue("Employee", null, $strDataEmployee);
  $employeeName = '';
  if (!empty($autoCompleteValue)) {
    $employeeData = getEmployeNameByID($db, $autoCompleteValue);
    $employeeName = $employeeData['employee_name'];
  }
  $f->addInputAutoComplete(
      getWords("employee"),
      "id_employee",
      getDataEmployee(getInitialValue("Employee", null, $strDataEmployee)),
      "style=width:$strDefaultWidthPx " . $strEmpReadonly,
      "string",
      false,
      true,
      true,
      "",
      "",
      true,
      null,
      "../global/hrd_ajax_source.php?action=getemployee",
      $autoCompleteValue
  );
  $f->addLabelAutoComplete("", "id_employee", $employeeName);
  //=================Auto Complete===
  $f->addInput(getWords("Valid Additional Leave"), "expired_date", date("Y-m-d"), ["style" => "width:80"], "date");
  //$f->addSelect(getWords("company"), "id_employee", getDataListCompany($strDataCompany, $bolCompanyEmptyOption, $arrCompanyEmptyData, $strKriteria2), array("style" => "width:200"), "", false);
  $f->addTextArea(
      getWords("note"),
      "note",
      "",
      ["cols" => 97, "rows" => 2, "maxlength" => 255],
      "string",
      false,
      true,
      true
  );
  $f->addInput(getWords("Quota"), "add_quota", "", [], "number", true, true, true);
  //$f->addCheckBox(getWords("active"),"active", null,  array("onChange" => "javascript:myClient.setActive(this.checked)"), null, false, true, true);
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
$myDataGrid->addColumn(new DataGrid_Column(getWords("Name"), "employee_name", null, ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("quota"), "add_quota", null, ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("date valid"), "expired_date", ['width' => '100'], ['nowrap' => ''])
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
      "Delete",
      "onClick=\"javascript:return myClient.confirmDelete();\"",
      "deleteData()"
  );
}
$myDataGrid->addButtonExportExcel(
    getWords("export excel"),
    $dataPrivilege['menu_name'] . ".xls",
    getWords($dataPrivilege['menu_name'])
);
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_leave_additional_request ";
$strSQL = "SELECT t0.*, employee_name FROM hrd_leave_additional_request AS t0 LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id
                   WHERE t1.active =1 ";
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
$strPageDesc = getWords("manage additional leave by request");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
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
      <input type=hidden name='detailYear$counter' id='detailYear$counter' value='" . $record['year'] . "' />
      <input type=hidden name='detailEmployee$counter' id='detailEmployee$counter' value='" . $record['id_employee'] . "' />
      <input type=hidden name='detailValidDate$counter' id='detailValidDate$counter' value='" . $record['expired_date'] . "' />
      <input type=hidden name='detailNote$counter' id='detailNote$counter' value='" . $record['note'] . "' />
      <input type=hidden name='detailQuota$counter' id='detailQuota$counter' value='" . $record['add_quota'] . "' />
      <a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $isNew;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strSQL2 = "SELECT id FROM hrd_employee WHERE employee_id ='" . $f->getValue('id_employee') . "'";
  $result = pg_query($strSQL2);
  $myrow = pg_fetch_assoc($result);
  //die($myrow['id']);
  $dataHrdAddRequest = new cHrdAddRequest();
  $data = [
      "year"         => date('Y'),
      "id_employee"  => $myrow['id'],
      "expired_date" => $f->getValue('expired_date'),
      "note"         => $f->getValue('note'),
      "add_quota"    => $f->getValue('add_quota')
  ];
  // simpan data -----------------------
  $bolSuccess = false;
  if ($isNew) {
    // data baru
    $bolSuccess = $dataHrdAddRequest->insert($data);
  } else {
    $bolSuccess = $dataHrdAddRequest->update(/*pk*/
        "id='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  if ($bolSuccess) {
    if ($isNew) {
      $f->setValue('dataID', $dataHrdAddRequest->getLastInsertId());
    } else {
      $f->setValue('dataID', $f->getValue('dataID'));
    }
  }
  $f->message = $dataHrdAddRequest->strMessage;
} // saveData
//funngsi untuk menampilkan tanda ceklist bila statusnya true (farhan)
function printIsActive($params)
{
  extract($params);
  if ($value == 't') {
    return "&radic;";
  } else {
    return "-";
  }
}

// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $dataHrdAddRequest = new cHrdAddRequest();
  $dataHrdAddRequest->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdAddRequest->strMessage;
} //deleteData
?>