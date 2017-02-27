<?php
//====== Include=====================================
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/ga_consumable_purchase.php');
include_once('../classes/ga/item_edit.php');
include_once('../classes/ga/consumable_request.php');
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
$strWordsEntryConsumablePurchase = getWords("entry Consumable Purchase");
$strWordsConsumablePurchaseList = getWords("Consumable Purchase list");
// Variabel get ID item yang akan di asigmnent
$strDataItem = getRequestValue('dataIdItem');
if ($strDataItem != "") {
  $arrData = getDataItemByID($strDataItem);
}
$strDataConsumableRequest = getRequestValue('dataConsReqNo');
if ($strDataConsumableRequest != "") {
  $arrData = getDataConsumableRequestByID($strDataConsumableRequest);
}
if ($strDataID != "") {
  $arrData = getDataByID($strDataID);
}
$db = new CdbClass;
$dataItem = new cGaConsumablePurchase;
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
    $f = new clsForm("formInput", 1, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    $f->addHidden("dataReqNoMatch", $arrData['dataConsPurchaseNo']);
    $f->addInput(
        getWords("Consumable Purchase No"),
        "dataConsPurchaseNo",
        $arrData['dataConsPurchaseNo'],
        "style='width:250px' ",
        "string",
        true,
        true,
        true
    );
    // Jika Baru maka enable nomer cons req no
    if ($isNew) {
      $f->addSelect(
          getWords("Consumable Request No"),
          "dataConsReqNo",
          getDataListConsumableRequestNo(
              $arrData['dataConsReqNo'],
              true,
              [
                  "value"    => "",
                  "text"     => "",
                  "selected" => true
              ],
              "AND active='YES'"
          ),
          "",
          true,
          true,
          true
      );
    } else {
      $f->addSelect(
          getWords("Consumable Request No"),
          "dataConsReqNo",
          getDataListConsumableRequestNo(
              $arrData['dataConsReqNo'],
              true,
              [
                  "value"    => "",
                  "text"     => "",
                  "selected" => true
              ],
              ""
          ),
          "",
          true,
          true,
          false
      );
    }
    ///------------
    $f->addSelect(
        getWords("item"),
        "dataIdItem",
        getDataListItemCriteria(
            $db,
            $arrData['dataIdItem'],
            true,
            [
                "value"    => "",
                "text"     => "",
                "selected" => true
            ],
            "Consumable"
        ),
        "style='width:250px' ",
        "",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("request date"),
        "dataRequestDate",
        $arrData['dataRequestDate'],
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
///***************** FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
function getDataByID($strDataID)
{
  global $db;
  $tbl = new cGaConsumablePurchase();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrTemp = getEmployeeInfoByID($db, $dataEdit[$strDataID]['id_employee'], "employee_id");
  $arrResult['dataIdItem'] = $dataEdit[$strDataID]['id_item'];
  $arrResult['dataRequestDate'] = $dataEdit[$strDataID]['request_date'];
  $arrResult['dataItemAmount'] = $dataEdit[$strDataID]['item_amount'];
  $arrResult['dataRemark'] = $dataEdit[$strDataID]['remark'];
  $arrResult['dataConsPurchaseNo'] = $dataEdit[$strDataID]['consumable_purchase_no'];
  $arrResult['dataConsReqNo'] = $dataEdit[$strDataID]['id_consumable_request'];
  //foreach($arrTripCost[$dataDonation ['trip_type']
  return $arrResult;
}

//====================== END FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
///***************** FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
function getDataConsumableRequestByID($strDataID)
{
  global $db;
  $tbl = new cGaConsumableRequest();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrResult['dataIdItem'] = $dataEdit[$strDataID]['id_item'];
  $arrResult['dataRequestDate'] = $dataEdit[$strDataID]['request_date'];
  $arrResult['dataItemAmount'] = $dataEdit[$strDataID]['item_amount'];
  $arrResult['dataRemark'] = $dataEdit[$strDataID]['remark'];
  $arrResult['dataConsReqNo'] = $dataEdit[$strDataID]['id'];
  //foreach($arrTripCost[$dataDonation ['trip_type']
  return $arrResult;
}

//====================== END FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
/************BEGIN FUNGSI CEK NOMER REQUEST*************/
function checkExistNo($reqNo)
{
  global $db;
  $result = [];
  if ($db->connect()) {
    $strSQL = "SELECT COUNT(*) AS total FROM ga_consumable_purchase where consumable_purchase_no='" . $reqNo . "' ";
    $res = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($res)) {
      $count = $rowDb['total'];
    }
  }
  return $count;
}

/************END FUNGSI CEK NOMER REQUEST***************/
///***************** FUNGSI Bila assigment DIKRIM DARI PARAMETER*******************************
function getDataItemByID($strDataID)
{
  global $db;
  $tbl = new cGaItem();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrResult['dataIdItem'] = $dataEdit[$strDataID]['id'];
  //$arrResult['dataItemStock']  = $dataEdit[$strDataID]['item_stock'];
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
    /// Cek apakah ada nomer reuest ada yang sama
    $noReq = $f->getValue('dataConsPurchaseNo');
    $noReqMatch = $f->getValue('dataReqNoMatch');
    if ($isNew) {
      $found = checkExistNo($noReq);
    } elseif ($noReq != $noReqMatch) {
      $found = checkExistNo($noReq);
    }
    $strMessage = "";
    if ($found > 0) {
      $strMessage = "Your Number Request Has Exist";
    } else {
      $tblSave = new cGaConsumablePurchase();
      //$dataGaPurchaseRequest = new cGaPurchaseRequest;
      $data = [
          "id_item"                => $f->getValue('dataIdItem'),
          "request_date"           => $f->getValue('dataRequestDate'),
          "item_amount"            => $f->getValue('dataItemAmount'),
          "remark"                 => $f->getValue('dataRemark'),
          "consumable_purchase_no" => $f->getValue('dataConsPurchaseNo'),
          "id_consumable_request"  => $f->getValue('dataConsReqNo')
      ];
      // simpan data donation
      $bolSuccess = false;
      if ($isNew) {
        // data baru
        $bolSuccess = $tblSave->insert($data);
      } else {
        $bolSuccess = $tblSave->update("id='" . $f->getValue('dataID') . "'", $data);
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