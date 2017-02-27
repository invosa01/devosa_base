<?php
/*
Author : Gesner Tampubolon
Date 	 : 21/11/2011
Desc	 : Halaman ini digunakan untuk data vendor
File	 : Data_vendor.php
*/
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/data_vendor.php');
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
$db = new CdbClass;
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
if ($bolCanEdit) {
  $f = new clsForm("formInput", 1, "100%", "");
  $f->caption = strtoupper($strWordsINPUTDATA);
  $f->addHidden("dataID", $strDataID);
  $f->addInput(
      getWords("vendor name"),
      "dataVendorName",
      "",
      ["size" => 30, "maxlength" => 31],
      "string",
      true,
      true,
      true
  );
  $f->addInput(
      getWords("address"),
      "dataVendorAddress",
      "",
      ["size" => 30, "maxlength" => 31],
      "string",
      true,
      true,
      true
  );
  $f->addInput(
      getWords("phone"),
      "dataVendorPhone",
      "",
      ["size" => 30, "maxlength" => 127],
      "string",
      true,
      true,
      true
  );
  $f->addInput(
      getWords("contact person"),
      "dataVendorContactPerson",
      "",
      ["size" => 30, "maxlength" => 31],
      "string",
      true,
      true,
      true
  );
  $f->addInput(
      getWords("email"),
      "dataVendorEmail",
      "",
      ["size" => 30, "maxlength" => 127],
      "string",
      true,
      true,
      true
  );
  $f->addInput(getWords("remark"), "dataRemark", "", ["size" => 30, "maxlength" => 127], "string", true, true, true);
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
    new DataGrid_Column("chkID", "id", ['width' => '50'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '50'], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("vendor name"), "vendor_name", ['width' => '150'], ['nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("address"), "vendor_address", ['width' => '250'], ['nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("phone"), "vendor_phone", ['width' => '150'], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("contact person"), "vendor_contactPerson", ['width' => '150'], ['nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("email"), "vendor_email", ['width' => '150'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("remark"), "remark", ""));
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
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_vendor ";
$strSQL = "SELECT * FROM ga_vendor ";
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
      <input type=hidden name='detailVendorName$counter' id='detailVendorName$counter' value='" . $record['vendor_name'] . "' />
      <input type=hidden name='detailVendorAddress$counter' id='detailVendorAddress$counter' value='" . $record['vendor_address'] . "' />
      <input type=hidden name='detailVendorPhone$counter' id='detailVendorPhone$counter' value='" . $record['vendor_phone'] . "' />
      <input type=hidden name='detailVendorContactPerson$counter' id='detailVendorContactPerson$counter' value='" . $record['vendor_contactPerson'] . "' />
      <input type=hidden name='detailVendorEmail$counter' id='detailVendorEmail$counter' value='" . $record['vendor_email'] . "' />
      <input type=hidden name='detailRemark$counter' id='detailRemark$counter' value='" . $record['remark'] . "' />
      <a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

/*function printFormat($params)
{
  extract($params);
  return number_format($record['vendor']);
}*/
// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $isNew;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $dataGaVendor = new cGaVendor();
  $data = [
      "vendor_name"          => $f->getValue('dataVendorName'),
      "vendor_address"       => $f->getValue('dataVendorAddress'),
      "vendor_phone"         => $f->getValue('dataVendorPhone'),
      "vendor_contactPerson" => $f->getValue('dataVendorContactPerson'),
      "vendor_email"         => $f->getValue('dataVendorEmail'),
      "remark"               => $f->getValue('dataRemark')
  ];
  // simpan data -----------------------
  $bolSuccess = false;
  if ($isNew) {
    // data baru
    $bolSuccess = $dataGaVendor->insert($data);
  } else {
    $bolSuccess = $dataGaVendor->update(/*pk*/
        "id='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  if ($bolSuccess) {
    if ($isNew) {
      $f->setValue('dataID', $dataGaVendor->getLastInsertId());
    } else {
      $f->setValue('dataID', $f->getValue('dataID'));
    }
  }
  $f->message = $dataGaVendor->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  print_r($arrKeys);
  die();
  $dataGaVendor = new cGaVendor();
  $dataGaVendor->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataGaVendor->strMessage;
} //deleteData
?>