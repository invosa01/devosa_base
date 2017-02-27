<?php
/*

Desc	 : Halaman ini digunakan untuk kategori itema
File	 : Data_item_category.php
*/
//---- BEGIN INCLUDE -----------------------------//
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/data_category_item.php');
//---- END INCLUDE -----------------------------//
//------------- BEGIN Previleges acess-------------------------------------------------------------------------------------//
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
//------------- END Previleges acess-------------------------------------------------------------------------------------//
/* Buka Class Database*/
$db = new CdbClass;
//----- BEGIN Variabel Umum-----------------//
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
//----- END Variabel Umum------------------//
/* Hak Akses Edit dan Tambah */
if ($bolCanEdit) {
  $f = new clsForm("formInput", 1, "100%", "");
  $f->caption = strtoupper($strWordsINPUTDATA);
  $f->addHidden("dataID", $strDataID);
  $f->addInput(getWords("Category name"), "dataCategoryName", "", ["size" => 40], "string", true, true, true);
  $f->addSelect(
      getWords("Category type"),
      "dataCategoryType",
      getDataListCategoryType(),
      ["style" => "width:200"],
      "",
      true,
      true,
      true
  );
  //$f->addInput(getWords("Category Type"), "category_type", "", array("size" => 30), "string", true, true, true);
  $f->addInput(getWords("Maintenance Action"), "dataMaintenanceAction", "", ["size" => 40], "string", true, true, true);
  $f->addTextArea(
      getWords("Remarks"),
      "dataRemark",
      "",
      ["cols" => 60, "rows" => 4, "maxlength" => 255],
      "string",
      false,
      true,
      true
  );
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
  $f->addButton(
      "btnAdd",
      getWords("add new"),
      ["onClick" => "location.href='" . basename($_SERVER['PHP_SELF'] . "';")]
  );
  $formInput = $f->render();
} else //-------------------------------------- BEGIN Data Grid---------------------------------------------------------------------------------//
{
  $formInput = "";
}
$myDataGrid = new cDataGrid("formData", "DataGrid1");
$myDataGrid->caption = strtoupper($strWordsLISTOF . " " . $dataPrivilege['menu_name']);
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("Name"), "category_name", ['width' => '100'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("Type"), "category_type", ['width' => '40'], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("Maintenance Action"), "maintenance_action", ['width' => '160'], ['nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("Remark"), "remark", ['width' => '160'], ['nowrap' => '']));
//------------------BEGIN Jika Punya hak akses Edit------------------------//
if ($bolCanEdit) {
  $myDataGrid->addColumn(
      new DataGrid_Column(
          "", "", ['width' => '60'], ['align' => 'center', 'nowrap' => ''], false, false, "",
          "printEditLink()", "", false /*show in excel*/
      )
  );
}
//------------------END Jika punya hak akses Edit-------------------------//
//----------------BEGIN JIKA Punya Hak Edit---------------------------------//
if ($bolCanEdit) {
  $myDataGrid->addSpecialButton(
      "btnPurchaseRequest",
      "btnPurchaseRequest",
      "submit",
      "Purchase Request",
      "onClick=\"javascript:return confirm('Do you want to purchase request this item?');\"",
      "purchaseRequest()"
  );
}
//---------------- END Jika Punya Hak Akses Tambah-------------------------//
//----------------BEGIN JIKA Punya Hak Edit---------------------------------//
if ($bolCanEdit) {
  $myDataGrid->addSpecialButton(
      "btnRequestOrder",
      "btnRequestOrder",
      "submit",
      "Request Order",
      "onClick=\"javascript:return confirm('Do you want to request order this item?');\"",
      "requestOrder()"
  );
}
//---------------- END Jika Punya Hak Akses Tambah-------------------------//
//-----------------BEGIN Jika Punya Hak Akses Hapus-----------------------------//
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
//---------------- END Jika Punya Hak Akses Hapus-------------------------//
$myDataGrid->addButtonExportExcel(
    "Export Excel",
    $dataPrivilege['menu_name'] . ".xls",
    getWords($dataPrivilege['menu_name'])
);
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_item_category ";
$strSQL = "SELECT * FROM ga_item_category ";
// Hitung jumlah data
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
// Tampilkan isi data
$dataset = $myDataGrid->getData($db, $strSQL);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
//-------------------------------------- END BEGIN Data Grid---------------------------------------------------------------------------------//
//Pesan Ketika tombol simpan
$strConfirmSave = getWords("do you want to save this entry?");
//-------------------------BEGIN Class Paging---------------------------------------------------//
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//-------------------------END Class Paging---------------------------------------------------//
/*********************** BEGIN Fungsi Edit ****************************************************/
function printEditLink($params)
{
  extract($params);
  return "
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['id'] . "' />
      <input type=hidden name='detailCategory_name$counter' id='detailCategory_name$counter' value='" . $record['category_name'] . "' />
      <input type=hidden name='detailCategory_type$counter' id='detailCategory_type$counter' value='" . $record['category_type'] . "' />
      <input type=hidden name='detailMaintenance_action$counter' id='detailMaintenance_action$counter' value='" . $record['maintenance_action'] . "' />
      <input type=hidden name='detailRemark$counter' id='detailRemark$counter' value='" . $record['remark'] . "' />
      <a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

/*********************** END Fungsi Edit ****************************************************/
/***************************** BEGIN fungsi untuk menyimpan data **********************************/
function saveData()
{
  //--------- BEGIN VARIABEL DEKLARASI-----------//
  global $f;
  global $isNew;
  //--------- END VARIABEL DEKLARASI-----------//
  //------------ CLASS GA Rooms-------------//
  $dataGaItemCategory = new cGaItemCategory;
  $data = [
      "category_name" => $f->getValue('dataCategoryName'),
      "category_type" => $f->getValue('dataCategoryType'),
      "maintenance_action" => $f->getValue('dataMaintenanceAction'),
      "remark" => $f->getValue('dataRemark')
  ];
  //------------END GA Roms----------------//
  //------------- BEGIN SAVE DATA ------------------------------------------------//
  $bolSuccess = false;
  //---------- BEGIN Jika Data Baru---------------------------------------------//
  if ($isNew) {
    $bolSuccess = $dataGaItemCategory->insert($data);
  } else {
    $bolSuccess = $dataGaItemCategory->update(/*pk*/
        "id='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  //---------- END Jika Data Baru---------------------------------------------//
  //---------------BEGIN Jika data yang berhasil disimpan-----------------//
  if ($bolSuccess) {
    if ($isNew) {
      $f->setValue('dataID', $dataGaItemCategory->getLastInsertId());
    } else {
      $f->setValue('dataID', $f->getValue('dataID'));
    }
  }
  //---------------END Jika data yang berhasil disimpan--------------------//
  $f->message = $dataGaItemCategory->strMessage;
  //------------- END SAVE DATA ------------------------------------------------//
}

/***************************** END fungsi untuk menyimpan data **********************************/
//Fungsi untuk Purchase Request
function purchaseRequest()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  //$tblDelete = new cGaPurchaseRequest();
  //$tblDelete->deleteMultiple($arrKeys);
  header("location:com_asset_purchase_request_edit.php?dataIdCategory=" . $strValue . "");
}

//************************************************END Purchase Request **************************************
//Fungsi untuk Request Order
function requestOrder()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  //$tblDelete = new cGaPurchaseRequest();
  //$tblDelete->deleteMultiple($arrKeys);
  header("location:com_asset_request_order_edit.php?dataIdCategory=" . $strValue . "");
}

//************************************************END Request Order **************************************
/*********************************BEGIN  fungsi untuk menghapus data ***************************/
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $datacItemCategory = new cGaItemCategory();
  $dataItemCategory->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataItemCategory->strMessage;
}

/*********************************END fungsi untuk menghapus data ****************************/
?>