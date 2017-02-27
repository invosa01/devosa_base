<?php
/*
Author : Dily Same Alie
Date 	 : 21/11/2011
Desc	 : Halaman ini digunakan untuk mengelola GA locatoin.
File	 : ga_location.php
*/
//---- BEGIN INCLUDE -----------------------------//
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/data_location.php');
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
  $f->addInput(
      getWords("Location Name"),
      "location_name",
      "",
      ["size" => 30, "maxlength" => 31],
      "string",
      true,
      true,
      true
  );
  $f->addTextArea(
      getWords("Remarks"),
      "remark",
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
    new DataGrid_Column(getWords("Location Name"), "location_name", ['width' => '150'], ['nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("remark"), "remark", ""));
//------------------BEGIN Jika Punya hak akses Edit---------------------------//
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
//------- Tombol Exxport To Excel--------//
$myDataGrid->addButtonExportExcel(
    "Export Excel",
    $dataPrivilege['menu_name'] . ".xls",
    getWords($dataPrivilege['menu_name'])
);
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_location ";
$strSQL = "SELECT * FROM ga_location ";
// Hitung jumlah data
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
// Tampilkan isi data
$dataset = $myDataGrid->getData($db, $strSQL);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
//-------------------------------------- END Data Grid -----------------------------------------------------------------------------------//
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
      <input type=hidden name='detailLocation$counter' id='detailLocation$counter' value='" . $record['location_name'] . "' />
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
  //------------ CLASS GA LOCATION-------------//
  $dataGaLocation = new cGaLocation();
  $data = [
      "location_name" => $f->getValue('location_name'),
      "remark"        => $f->getValue('remark')
  ];
  //------------END GA LOCATION----------------//
  //------------- BEGIN SAVE DATA ------------------------------------------------//
  $bolSuccess = false;
  //---------- BEGIN Jika Data Baru---------------------------------------------//
  if ($isNew) {
    $bolSuccess = $dataGaLocation->insert($data);
  } else {
    $bolSuccess = $dataGaLocation->update(/*pk*/
        "id='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  //---------- END Jika Data Baru---------------------------------------------//
  //---------------BEGIN Jika data yang berhasil disimpan-----------------//
  if ($bolSuccess) {
    if ($isNew) {
      $f->setValue('dataID', $dataGaLocation->getLastInsertId());
    } else {
      $f->setValue('dataID', $f->getValue('dataID'));
    }
  }
  //---------------END Jika data yang berhasil disimpan--------------------//
  $f->message = $dataHrdBank->strMessage;
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
  $dataGaLocation = new cGaLocation();
  $dataGaLocation->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataGaLocation->strMessage;
}

/*********************************END fungsi untuk menghapus data ****************************/
?>