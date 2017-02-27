<?php
//====== Include=====================================
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/consumable_stock_in.php');
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
$strWordsEntryConsumableStockIn = getWords("entry consumable stock in");
$strWordsConsumableStockInList = getWords("consumable stock in list");
$db = new CdbClass;
$dataItem = new cGaConsumableStockIn;
if ($db->connect()) {
  getUserEmployeeInfo();
  $strDataID = getRequestValue('dataID');
  $isNew = ($strDataID == "");
  if ($strDataID != "") {
    $arrData = getDataByID($strDataID);
  } else {
    $arrData['dataTempFormCode'] = $arrData['dataFormCode'] = (getPostValue('dataFormCode') != "") ? getPostValue(
        'dataFormCode'
    ) : getFormCode($db, "SDM.DNT-", date(".m.Y."), "hrd_donation");
    $arrData['dataDonationCode'] = getPostValue('dataDonationCode');
    $arrData['dataEmployee'] = getPostValue('dataEmployee');
    $arrData['dataCreated'] = (getPostValue('dataCreated') != "") ? getPostValue('dataCreated') : date("Y-m-d");
    $arrData['dataEventDateFrom'] = (getPostValue('dataEventDateFrom') != "") ? getPostValue(
        'dataEventDateFrom'
    ) : date("Y-m-d");
    $arrData['dataEventDateThru'] = (getPostValue('dataEventDateThru') != "") ? getPostValue(
        'dataEventDateThru'
    ) : date("Y-m-d");
    $arrData['dataAmount'] = getPostValue('dataAmount');
    $arrData['dataRelationName'] = getPostValue('dataRelationName');
    $arrData['dataRelationType'] = getPostValue('dataRelationType');
    $arrData['dataNote'] = getPostValue('dataNote');
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
    $f->addSelect(
        getWords("Item"),
        "dataIdItem",
        getDataListItem(
            $arrData['dataIdItem'],
            true,
            [
                "value"    => "",
                "text"     => "",
                "selected" => true
            ]
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
        ["style" => "width:200px"],
        "numeric",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("document no"),
        "dataDocNo",
        $arrData['dataDocNo'],
        ["style" => "width:200px"],
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
  $tbl = new cGaConsumableStockIn();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrTemp = getEmployeeInfoByID($db, $dataEdit[$strDataID]['id_employee'], "employee_id");
  $arrResult['dataIdItem'] = $dataEdit[$strDataID]['id_item'];
  $arrResult['dataTransactionDate'] = $dataEdit[$strDataID]['transaction_date'];
  $arrResult['dataItemAmount'] = $dataEdit[$strDataID]['item_amount'];
  $arrResult['dataDocNo'] = $dataEdit[$strDataID]['document_no'];
  return $arrResult;
}

//====================== END FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
//******************************** fungsi untuk menyimpan data*****************************************
function saveData()
{
  global $f;
  global $db;
  global $isNew;
  if ($db->connect()) {
    $strRelationType = "";
    $strmodified_byID = $_SESSION['sessionUserID'];
    //$strIDEmployee = getIDEmployee($db, $f->getValue('dataEmployee'));
    $tblSave = new cGaConsumableStockIn();
    /// Hitung stock yang akan disimpan(di update) kedatabase Item
    $lastStock = getLastStockItem($f->getValue('dataIdItem'), $isNew);
    $iditem = $f->getValue('dataIdItem');
    $amountStock = $lastStock + ($f->getValue('dataItemAmount'));
    // Pannggil araay untuk perintah Update
    $dataUpdateItem = new cGaItem;
    $dataToUpdate = ["item_stock" => $amountStock, "current_stock" => $lastStock];
    $data = [
        "id_item"          => $f->getValue('dataIdItem'),
        "transaction_date" => $f->getValue('dataTransactionDate'),
        "item_amount"      => $f->getValue('dataItemAmount'),
        "document_no"      => $f->getValue('dataDocNo')
    ];
    // simpan data donation
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
  } else {
    $f->message = "no connection";
    $f->msgClass = "bgError";
  }
  $f->message = $tblSave->strMessage;
}

//*********************************************************END  saveData ***************************************
?>