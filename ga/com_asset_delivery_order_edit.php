<?php
//====== Include=====================================
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/delivery_order.php');
include_once('../classes/ga/item_edit.php');
//===== END include=================================
// BEGIN hak akses =========================================================================================================
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
// END hak akses ===========================================================================================================
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $strDataID = getRequestValue('dataID');
  $strDataIDItem = getRequestValue('dataIDItem');
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
  echo $dataIdItem;
  if ($bolCanEdit) {
    // FORM INPUT ===============================================================================================================================
    $f = new clsForm("formInput", 2, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    $f->addHidden("dataIdItemToUpdate", $arrData['dataIdItemToUpdate']);
    $f->addInput(
        getWords("Delivery order number#"),
        "dataDONumber",
        $arrData['dataDONumber'],
        ["size" => 50],
        "string",
        true,
        true,
        true
    );
    if ($isNew) {
      $f->addSelect(
          getWords("Request Order ID"),
          "dataIdRequestOrder",
          getDataRequestOrder(
              $arrData['dataIdRequestOrder'],
              true,
              [
                  "value"    => "",
                  "text"     => "",
                  "selected" => true
              ],
              "AND active='YES'"
          ),
          ["style" => "width:200"],
          "",
          false,
          true,
          true
      );
      $f->addSelect(
          getWords("Purchase Request ID"),
          "dataIdPurchaseRequest",
          getDataListPurchaseNo(
              $arrData['dataIdPurchaseRequest'],
              true,
              [
                  "value"    => "",
                  "text"     => "",
                  "selected" => true
              ],
              "AND active_do='YES'"
          ),
          ["style" => "width:200"],
          "",
          false,
          true,
          true
      );
    } else {
      $f->addSelect(
          getWords("Request Order ID"),
          "dataIdRequestOrder",
          getDataRequestOrder(
              $arrData['dataIdRequestOrder'],
              true,
              [
                  "value"    => "",
                  "text"     => "",
                  "selected" => true
              ]
          ),
          ["style" => "width:200"],
          "",
          false,
          false,
          true
      );
      $f->addSelect(
          getWords("Purchase Request ID"),
          "dataIdPurchaseRequest",
          getDataListPurchaseNo(
              $arrData['dataIdPurchaseRequest'],
              true,
              [
                  "value"    => "",
                  "text"     => "",
                  "selected" => true
              ]
          ),
          ["style" => "width:200"],
          "",
          false,
          false,
          true
      );
    }
    $f->addSelect(
        getWords("Asset Category Item"),
        "dataIdCategory",
        getDataListCategoryItem(
            $arrData['dataIdCategory'],
            true,
            [
                "value"    => "",
                "text"     => "",
                "selected" => true
            ]
        ),
        ["style" => "width:200"],
        "",
        false,
        true,
        true
    );
    //$f->addInput(getWords("Item ID"), "dataItemId",	$arrData['dataItemId'], array("size" => 50), "string", true, true, true);
    $f->addInput(
        getWords("Item Code"),
        "dataItemCode",
        $arrData['dataItemCode'],
        ["size" => 50],
        "string",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("item name"),
        "dataItemName",
        $arrData['dataItemName'],
        ["size" => 50],
        "string",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("item price"),
        "dataItemPrice",
        $arrData['dataItemPrice'],
        ["size" => 50],
        "numeric",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("delivery date"),
        "dataDeliveryDate",
        $arrData['dataDeliveryDate'],
        ["style" => "width:$strDateWidth"],
        "date",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("due date"),
        "dataDueDate",
        $arrData['dataDueDate'],
        ["style" => "width:$strDateWidth"],
        "date",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("serial number"),
        "dataSerialNumber",
        $arrData['dataSerialNumber'],
        ["size" => 50],
        "string",
        false,
        true,
        true
    );
    $f->addSelect(
        getWords("vendor"),
        "dataVendor",
        getDataVendor(
            $arrData['dataVendor'],
            true,
            [
                "value"    => "",
                "text"     => "",
                "selected" => true
            ]
        ),
        ["style" => "width:200"],
        "",
        false,
        true,
        true
    );
    $f->addTextArea(
        getWords("Remarks"),
        "dataRemark",
        $arrData['dataRemark'],
        ["cols" => 60, "rows" => 4, "maxlength" => 255],
        "string",
        false,
        true,
        true
    );
    $f->addInputAutoComplete(
        getWords("employee ID"),
        "dataEmployee",
        getDataEmployee($arrData['dataEmployee']),
        "style='width:250px' " . $strReadonly,
        "string",
        false
    );
    $f->addLabelAutoComplete("", "dataEmployee", "");
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
    // END FORM INPUT ============================================================================================================================
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
///***************** FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
function getDataByID($strDataID)
{
  global $db;
  global $isNew;
  global $strDataIDItem;
  $tbl = new cGaDeliveryOrder();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrResult['dataDONumber'] = $dataEdit[$strDataID]['delivery_order_no'];
  $arrResult['dataIdRequestOrder'] = $dataEdit[$strDataID]['id_request_order'];
  $arrResult['dataIdPurchaseRequest'] = $dataEdit[$strDataID]['id_purchase_request'];
  $arrResult['dataIdCategory'] = $dataEdit[$strDataID]['id_asset_category'];
  $arrResult['dataItemCode'] = $dataEdit[$strDataID]['item_code'];
  $arrResult['dataItemName'] = $dataEdit[$strDataID]['item_name'];
  $arrResult['dataItemPrice'] = $dataEdit[$strDataID]['item_price'];
  $arrResult['dataDeliveryDate'] = $dataEdit[$strDataID]['delivery_date'];
  $arrResult['dataDueDate'] = $dataEdit[$strDataID]['item_due_date'];
  $arrResult['dataItemStock'] = $dataEdit[$strDataID]['item_stock'];
  $arrResult['dataSerialNumber'] = $dataEdit[$strDataID]['serial_number'];
  $arrResult['dataRemark'] = $dataEdit[$strDataID]['remark'];
  $arrResult['dataVendor'] = $dataEdit[$strDataID]['id_vendor'];
  $tblItem = new cGaItem();
  $dataEditItem = $tblItem->findAll("id = $strDataIDItem", "", "", null, 1, "id");
  $arrTemp = getEmployeeInfoByID($db, $dataEditItem[$strDataIDItem]['id_employee'], "employee_id");
  $arrResult['dataEmployee'] = $arrTemp['employee_id'];
  if (!$isNew) {
    $arrResult['dataIdItemToUpdate'] = $dataEdit[$strDataID]['id_item'];
  }
  return $arrResult;
}

//******************* END FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
//******************************** fungsi untuk menyimpan data*****************************************
function saveData()
{
  global $f;
  global $db;
  global $isNew;
  if ($db->connect()) {
    $strIDEmployee = getIDEmployee($db, $f->getValue('dataEmployee'));
    $tblSaveItem = new cGaItem();
    $dataItem = [
        "item_code"       => $f->getValue('dataItemCode'),
        "item_name"       => $f->getValue('dataItemName'),
        "item_price"      => $f->getValue('dataItemPrice'),
        "item_entry_date" => $f->getValue('dataDeliveryDate'),
        "item_due_date"   => $f->getValue('dataDueDate'),
        "id_category"     => $f->getValue('dataIdCategory'),
        "serial_number"   => $f->getValue('dataSerialNumber'),
        "id_employee"     => ($strIDEmployee)
    ];
    // simpan data
    $bolSuccessItem = false;
    $bolSuccesDo = false;
    if ($isNew) {
      // data baru
      $bolSuccessItem = $tblSaveItem->insert($dataItem);
      $f->setValue('dataID', $tblSaveItem->getLastInsertId());
      $dataIdItem = $f->getValue('dataID', $tblSaveItem->getLastInsertId());
      $tblSaveDo = new cGaDeliveryOrder();
      $dataDo = [
          "delivery_order_no"   => $f->getValue('dataDONumber'),
          "id_request_order"    => $f->getValue('dataIdRequestOrder'),
          "id_purchase_request" => $f->getValue('dataIdPurchaseRequest'),
          "id_asset_category"   => $f->getValue('dataIdCategory'),
          "id_item"             => $dataIdItem,
          "item_code"           => $f->getValue('dataItemCode'),
          "item_name"           => $f->getValue('dataItemName'),
          "item_price"          => $f->getValue('dataItemPrice'),
          "delivery_date"       => $f->getValue('dataDeliveryDate'),
          "item_due_date"       => $f->getValue('dataDueDate'),
          "serial_number"       => $f->getValue('dataSerialNumber'),
          "id_employee"         => ($strIDEmployee),
          "id_vendor"           => $f->getValue('dataVendor'),
          "remark"              => $f->getValue('dataRemark')
      ];
      $bolSuccessDo = $tblSaveDo->insert($dataDo);
      $dataIdDo = $f->setValue('dataID', $tblSaveDo->getLastInsertId());
    } else {
      $tblSaveItem = new cGaItem();
      $dataItem = [
          "item_code"       => $f->getValue('dataItemCode'),
          "item_name"       => $f->getValue('dataItemName'),
          "item_price"      => $f->getValue('dataItemPrice'),
          "item_entry_date" => $f->getValue('dataDeliveryDate'),
          "item_due_date"   => $f->getValue('dataDueDate'),
          "id_category"     => $f->getValue('dataIdCategory'),
          "serial_number"   => $f->getValue('dataSerialNumber'),
          "id_employee"     => ($strIDEmployee)
      ];
      $tblSaveDo = new cGaDeliveryOrder();
      $dataDo = [
          "delivery_order_no"   => $f->getValue('dataDONumber'),
          "id_request_order"    => $f->getValue('dataIdRequestOrder'),
          "id_purchase_request" => $f->getValue('dataIdPurchaseRequest'),
          "id_asset_category"   => $f->getValue('dataIdCategory'),
          "id_item"             => $f->getValue('dataIdItemToUpdate'),
          "item_code"           => $f->getValue('dataItemCode'),
          "item_name"           => $f->getValue('dataItemName'),
          "item_price"          => $f->getValue('dataItemPrice'),
          "delivery_date"       => $f->getValue('dataDeliveryDate'),
          "item_due_date"       => $f->getValue('dataDueDate'),
          "serial_number"       => $f->getValue('dataSerialNumber'),
          "id_employee"         => ($strIDEmployee),
          "id_vendor"           => $f->getValue('dataVendor'),
          "remark"              => $f->getValue('dataRemark')
      ];
      $bolSuccessItem = $tblSaveItem->update("id='" . $f->getValue('dataIdItemToUpdate') . "'", $dataItem);
      $bolSuccessDo = $tblSaveDo->update("id='" . $f->getValue('dataID') . "'", $dataDo);
    }
    if ($bolSuccess) {
      if ($isNew) {
        $f->setValue('dataID', $tblSaveItem->getLastInsertId());
        $f->setValue('dataID', $tblSaveDo->getLastInsertId());
      } else {
        $f->setValue('dataID', $f->getValue('dataID'));
      }
    }
  } else {
    $f->message = "no connection";
    $f->msgClass = "bgError";
  }
  $f->message = $tblSaveDo->strMessage;
}

//*********************************************************END  saveData ***************************************
?>