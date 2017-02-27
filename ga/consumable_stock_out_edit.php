<?php
//====== Include=====================================
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/consumable_stock_out.php');
include_once('../classes/ga/item_edit.php');
//===== END include=================================
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
$strWordsINPUTDATA = getWords("Form Input Data");
$strWordsEntryConsumableStockOut = getWords("entry consumable stock out");
$strWordsConsumableStockOutList = getWords("consumable stock out list");
$db = new CdbClass;
$dataItem = new cGaConsumableStockOut;
if ($db->connect()) {
  getUserEmployeeInfo();
  $strDataID = getRequestValue('dataID');
  $isNew = ($strDataID == "");
  if ($strDataID != "") {
    $arrData = getDataByID($strDataID);
  }
  //-------------------------------------------------------------------------------------------------------------------------------
  $strReadonly = (scopeGeneralDataEntry(
      $arrData['dataEmployee'],
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  )) ? "readonly" : "";
  // --------------------------------------------------------------------------------------------------------------------------------
  if ($bolCanEdit) {
    $f = new clsForm("formInput", 2, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    //$f->addSelect(getWords("Category Item"), "dataIdCategory", getDataCategoryItem());
    $f->addSelect(
        getWords("item"),
        "dataIdItem",
        getDataListItem($arrData['dataIdItem'], true, ["value" => "", "text" => "", "selected" => true]),
        "style='width:250px' ",
        "",
        true,
        true,
        true
    );
    $f->addSelect(
        getWords("departement code"),
        "dataDepartmentCode",
        getDataListDepartment(
            $arrData['dataDepartmentCode'],
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
        "style='width:250px' ",
        "",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("transaction date"),
        "dataTransactionDate",
        $arrData['dataTransactionDate'],
        ["style" => "width:$strDateWidth"],
        "date",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("item amount"),
        "dataItemAmount",
        $arrData['dataItemAmount'],
        ["style" => "width:$strDateWidth"],
        "numeric",
        true,
        true,
        true
    );
    $f->addTextArea(
        getWords("remark"),
        "dataRemark",
        $arrData['dataRemark'],
        ["cols" => 40, "rows" => 4, "maxlength" => 255],
        "string",
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
    $f->addButton(
        "btnAdd",
        getWords("add new"),
        ["onClick" => "location.href='" . basename($_SERVER['PHP_SELF'] . "';")]
    );
    $formInput = $f->render();
  } else {
    $formInput = "";
  }
}
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
///****************** FUNGSI UNTUK MENDAPATKAN JUMLAH STOCK ITEM TERAKHIR********************************//
function getLastStockItem($idItem, $isNew)
{
  global $db;
  $strSQL = "SELECT item_stock,current_stock FROM ga_item WHERE id=" . $idItem . "";
  $res = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($res)) {
    if ($isNew) {
      /// Jika data baru ambil dari field item_stock
      $result = $rowDb['item_stock'];
    } else {
      /// Jika edit data ambil dari data current_stock sebelum diedit
      $result = $rowDb['current_stock'];
    }
  }
  return $result;
}

///****************** END FUNGSI UNTUK MENDAPATKAN JUMLAH STOCK ITEM TERAKHIR****************************//
///***************** FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
function getDataByID($strDataID)
{
  global $db;
  $tbl = new cGaConsumableStockOut();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrTemp = getEmployeeInfoByID($db, $dataEdit[$strDataID]['id_employee'], "employee_id");
  $arrResult['dataIdItem'] = $dataEdit[$strDataID]['id_item'];
  $arrResult['dataDepartmentCode'] = $dataEdit[$strDataID]['department_code'];
  $arrResult['dataTransactionDate'] = $dataEdit[$strDataID]['transaction_date'];
  $arrResult['dataItemAmount'] = $dataEdit[$strDataID]['item_amount'];
  $arrResult['dataRemark'] = $dataEdit[$strDataID]['remark'];
  //foreach($arrTripCost[$dataDonation ['trip_type']
  return $arrResult;
}

//====================== END FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
//******************************* FUNGSI VALIDASI STOCK TIDAK 0******************************************
function cekStockNoZero($idItem)
{
  global $db;
  $result = [];
  if ($db->connect()) {
    $strSQL = "SELECT item_stock FROM ga_item where id='" . $idItem . "' ";
    $res = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($res)) {
      $count = $rowDb['item_stock'];
    }
  }
  return $count;
}

//******************************* END FUNGSI ************************************************************
//******************************** fungsi untuk menyimpan data*****************************************
function saveData()
{
  global $f;
  global $db;
  global $isNew;
  if ($db->connect()) {
    /// Cek apakah ada  stock lebih kecil kosong
    $idItem = $f->getValue('dataIdItem');
    $stockOut = $f->getValue('dataItemAmount');
    $lastStock = cekStockNoZero($idItem);
    $strMessage = "";
    if (($stockOut > $lastStock) && ($isNew)) {
      $strMessage = "Take stock not enough ";
    } else {
      $tblSave = new cGaConsumableStockOut();
      /// Hitung stock yang akan disimpan(di update) kedatabase Item
      $lastStock = getLastStockItem($f->getValue('dataIdItem'), $isNew);
      $iditem = $f->getValue('dataIdItem');
      $amountStock = $lastStock - ($f->getValue('dataItemAmount'));
      // Pannggil araay untuk perintah Update
      $dataUpdateItem = new cGaItem;
      $dataToUpdate = ["item_stock" => $amountStock, "current_stock" => $lastStock];
      $data = [
          "id_item"          => $f->getValue('dataIdItem'),
          "department_code"  => $f->getValue('dataDepartmentCode'),
          "transaction_date" => $f->getValue('dataTransactionDate'),
          "item_amount"      => $f->getValue('dataItemAmount'),
          "remark"           => $f->getValue('dataRemark')
      ];
      // simpan data
      $bolSuccess = false;
      if ($isNew) {
        // data baru
        $bolSuccess = $tblSave->insert($data);
        $bolSuccess = $dataUpdateItem->update(/*pk*/
            "id='" . $f->getValue('dataIdItem') . "'", /*data to update*/
            $dataToUpdate
        );
      } else {
        $bolSuccess = $tblSave->update("id='" . $f->getValue('dataID') . "'", $data);
        //UPdate tabel item
        $bolSuccess = $dataUpdateItem->update(/*pk*/
            "id='" . $f->getValue('dataIdItem') . "'", /*data to update*/
            $dataToUpdate
        );
      }
      if ($bolSuccess) {
        if ($isNew) {
          $f->setValue('dataID', $tblSave->getLastInsertId());
        } else {
          $f->setValue('dataID', $f->getValue('dataID'));
        }
      }
      $strMessage = $tblSave->strMessage;
    }
  } else {
    $f->message = "no connection";
    $f->msgClass = "bgError";
  }
  $f->message = $strMessage;
}

//*********************************************************END  saveData ***************************************
?>