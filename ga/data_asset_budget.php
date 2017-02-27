<?php
/*
Author : Gesner Tampubolon
Date 	 : 22/11/2011
Desc	 : Halaman ini digunakan untuk budget tahunan per jenis asset
File	 : Data_asset_budget.php
*/
//---- BEGIN INCLUDE -----------------------------//
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/ga_asset_budget.php');
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
  $f->addInput(getWords("budget code"), "dataBudgetCode", "", ["size" => 30], "string", true, true, true);
  $f->addSelect(getWords("item category"), "dataIdCategory", getDataItemCategory());
  $f->addInput(getWords("budget year"), "dataBudgetYear", "", ["size" => 30], "numeric", true, true, true);
  $f->addInput(
      getWords("budget amount (Harga Barang)"),
      "dataBudgetAmount",
      "",
      ["size" => 30],
      "numeric",
      true,
      true,
      true
  );
  $f->addInput(
      getWords("budget count (Jumlah Barang)"),
      "dataBudgetCount",
      "",
      ["size" => 30],
      "numeric",
      true,
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
  $f->addButton("btnAdd", getWords("add new"), ["onClick" => "javascript:myClient.editData(0);"]);
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
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("Budget code"), "budget_code", ['width' => '100'], ['nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("Item category"), "category_name", ['width' => '100'], ['nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("Budget Year"), "budget_year", ['width' => '40'], ['nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("Budget Amount"), "budget_amount", ['width' => '100'], ['nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("Budget Count"), "budget_count", ['width' => '40'], ['nowrap' => ''])
);
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
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_asset_budget ";
$strSQL = "SELECT i.category_name,a.*,a.id FROM ga_asset_budget as a LEFT JOIN ga_item_category AS i ON a.id=i.id  ";
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
//-------------------------BEGIN Get Data Item Category -------------------------------------------//
function getDataItemCategory()
{
  global $db;
  $result = [];
  if ($db->connect()) {
    $strSQL = "SELECT * FROM ga_item_category ORDER BY category_name";
    $res = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($res)) {
      $result[] = ["value" => $rowDb['id'], "text" => $rowDb['category_name'], "selected" => true];
    }
  }
  return $result;
}

//-------------------------END Get Data Item Category -------------------------------------------//
/*********************** BEGIN Fungsi Edit ****************************************************/
function printEditLink($params)
{
  extract($params);
  return "
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['id'] . "' />
      <input type=hidden name='detailBudgetCode$counter' id='detailBudgetCode$counter' value='" . $record['budget_code'] . "' />
      <input type=hidden name='detailIdCategory$counter' id='detailIdCategory$counter' value='" . $record['id'] . "' />
      <input type=hidden name='detailBudgetYear$counter' id='detailBudgetYear$counter' value='" . $record['budget_year'] . "' />
      <input type=hidden name='detailBudgetAmount$counter' id='detailBudgetAmount$counter' value='" . $record['budget_amount'] . "' />
      <input type=hidden name='detailBudgetCount$counter' id='detailBudgetCount$counter' value='" . $record['budget_count'] . "' />
      <a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

/*********************** END Fungsi Edit ****************************************************/
/* function printFormat($params)
 {
   extract($params);
   return number_format($record['bank']);
 }*/
/***************************** BEGIN fungsi untuk menyimpan data **********************************/
function saveData()
{
  //--------- BEGIN VARIABEL DEKLARASI-----------//
  global $f;
  global $isNew;
  //--------- END VARIABEL DEKLARASI-----------//
  //------------ CLASS GA Rooms-------------//
  $dataGaAssetBudget = new cGaAssetBudget;
  $data = [
      "budget_code" => $f->getValue('dataBudgetCode'),
      "id" => $f->getValue('dataIdCategory'),
      "budget_year" => $f->getValue('dataBudgetYear'),
      "budget_amount" => $f->getValue('dataBudgetAmount'),
      "budget_count" => $f->getValue('dataBudgetCount')
  ];
  //------------END GA Roms----------------//
  //------------- BEGIN SAVE DATA ------------------------------------------------//
  $bolSuccess = false;
  //---------- BEGIN Jika Data Baru---------------------------------------------//
  if ($isNew) {
    $bolSuccess = $dataGaAssetBudget->insert($data);
  } else {
    $bolSuccess = $dataGaAssetBudget->update(/*pk*/
        "id='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  //---------- END Jika Data Baru---------------------------------------------//
  //---------------BEGIN Jika data yang berhasil disimpan-----------------//
  if ($bolSuccess) {
    if ($isNew) {
      $f->setValue('dataID', $dataGaAssetBudget->getLastInsertId());
    } else {
      $f->setValue('dataID', $f->getValue('dataID'));
    }
  }
  //---------------END Jika data yang berhasil disimpan--------------------//
  $f->message = $dataAssetBudget->strMessage;
  //------------- END SAVE DATA ------------------------------------------------//
}

/***************************** END fungsi untuk menyimpan data **********************************/
/*********************************BEGIN  fungsi untuk menghapus data ***************************/
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $dataGaAssetBudget = new cGaAssetBudget();
  $dataGaAssetBudget->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataAssetBudget->strMessage;
}

/*********************************END fungsi untuk menghapus data ****************************/
?>