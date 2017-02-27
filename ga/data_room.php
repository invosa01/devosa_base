<?php
/*
Author : Dily Same Alie
Date 	 : 21/11/2011
Desc	 : Halaman ini digunakan untuk mengelola GA rooms
File	 : Data_room.php
*/
//---- BEGIN INCLUDE -----------------------------//
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/data_room.php');
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
  $f->addSelect(getWords("Location Name"), "id_location", getDataLocation());
  $f->addInput(getWords("Room Code"), "room_code", "", ["size" => 10], "string", true, true, true);
  $f->addInput(getWords("Room Name"), "room_name", "", ["size" => 40], "string", true, true, true);
  $f->addInput(getWords("Room Type"), "room_type", "", ["size" => 35], "string", true, true, true);
  $f->addInput(getWords("Capacity"), "capacity", "", ["size" => 7], "string", true, true, true, "", "Person");
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
    new DataGrid_Column(getWords("Location Name"), "location_name", ['width' => '100'], ['nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("Room Code"), "room_code", ['width' => '40'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("Room Name"), "room_name", ['width' => '160'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("Room Type"), "room_type", ['width' => '60'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("Capacity"), "capacity", ['width' => '45'], ['nowrap' => '']));
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
$myDataGrid->addButtonExportExcel(
    "Export Excel",
    $dataPrivilege['menu_name'] . ".xls",
    getWords($dataPrivilege['menu_name'])
);
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_room ";
$strSQL = "SELECT l.location_name,r.*,r.id AS id FROM ga_room as r LEFT JOIN ga_location AS l ON r.id_location=l.id ";
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
//-------------------------BEGIN Get Data Location of GA -------------------------------------------//
function getDataLocation()
{
  global $db;
  $result = [];
  if ($db->connect()) {
    $strSQL = "SELECT * FROM ga_location ORDER BY location_name";
    $res = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($res)) {
      $result[] = ["value" => $rowDb['id'], "text" => $rowDb['location_name'], "selected" => false];
    }
  }
  return $result;
}

//-------------------------END Get Data Location -------------------------------------------//
function printEditLink($params)
{
  extract($params);
  return "
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['id'] . "' />
      <input type=hidden name='detailId_location$counter' id='detailId_location$counter' value='" . $record['id_location'] . "' />
      <input type=hidden name='detailRoom_code$counter' id='detailRoom_code$counter' value='" . $record['room_code'] . "' />
      <input type=hidden name='detailRoom_name$counter' id='detailRoom_name$counter' value='" . $record['room_name'] . "' />
      <input type=hidden name='detailRoom_type$counter' id='detailRoom_type$counter' value='" . $record['room_type'] . "' />
      <input type=hidden name='detailCapacity$counter' id='detailCapacity$counter' value='" . $record['capacity'] . "' />
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
  $dataGaLocation = new cGaRooms;
  $data = [
      "id_location" => $f->getValue('id_location'),
      "room_code"   => $f->getValue('room_code'),
      "room_type"   => $f->getValue('room_type'),
      "capacity"    => $f->getValue('capacity'),
      "room_name"   => $f->getValue('room_name')
  ];
  //------------END GA Roms----------------//
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
  $dataGaRooms = new cGaRooms();
  $dataGaRooms->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataRooms->strMessage;
}

/*********************************END fungsi untuk menghapus data ****************************/
?>