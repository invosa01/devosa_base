<?php
/*
  Author : Gesner Tampubolon
  Date 	 : 23/11/2011
  Desc	 : Halaman ini digunakan untuk permintaan pembelian dari karyawan ke GA (form usulan investasi -
  FUI)
  File	 : Data_purcahse_request.php
  */
//====== Include=====================================
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/ga_request_order.php');
include_once('../classes/ga/data_category_item.php');
include_once('../classes/ga/ga_purchase_request.php');
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
$strWordsEntryRO = getWords("entry Request Order");
$strWordsROList = getWords("Request Order list");
// Variabel get ID item yang akan di request order
$strDataIdCategory = getRequestValue('dataIdCategory');
if ($strDataIdCategory != "") {
  $arrData = getDataItemByID($strDataIdCategory);
}
$strDataPurchaseNo = getRequestValue('dataPurchaseNo');
if ($strDataPurchaseNo != "") {
  $arrData = getDataPurchaseRequestByID($strDataPurchaseNo);
}
if ($strDataID != "") {
  $arrData = getDataByID($strDataID);
}
$db = new CdbClass;
$dataItem = new cGaRequestOrder;
$strNow = Date('Y-m-d');
if ($db->connect()) {
  getUserEmployeeInfo();
  $strDataID = getRequestValue('dataID');
  $isNew = ($strDataID == "");
  if ($strDataID != "") {
    $arrData = getDataByID($strDataID);
  } else {
    "";
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
    $f->addHidden("dataReqNoMatch", $arrData['dataRequestNo']);
    $f->addInput(
        getWords("Request Order No"),
        "dataRequestNo",
        $arrData['dataRequestNo'],
        ["style" => "width:$strDateWidth"],
        "string",
        true,
        true,
        true
    );
    if ($isNew) {
      $f->addSelect(
          getWords("purchase request"),
          "dataPurchaseNo",
          getDataListPurchaseNo(
              $arrData['dataPurchaseNo'],
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
          getWords("purchase request"),
          "dataPurchaseNo",
          getDataListPurchaseNo(
              $arrData['dataPurchaseNo'],
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
    $f->addSelect(
        getWords("item category"),
        "dataIdCategory",
        getDataListCategoryItem($arrData['dataIdCategory']),
        "style='width:250px' ",
        "string",
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
    $f->addTextArea(
        getWords("remark"),
        "dataRemark",
        $arrData['dataRemark'],
        ["cols" => 42, "rows" => 4, "maxlength" => 255],
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
  $tbl = new cGaRequestOrder();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrResult['dataRequestNo'] = $dataEdit[$strDataID]['request_order_no'];
  $arrResult['dataPurchaseNo'] = $dataEdit[$strDataID]['id_purchase_request'];
  $arrResult['dataIdCategory'] = $dataEdit[$strDataID]['id_asset_category'];
  $arrResult['dataRequestDate'] = $dataEdit[$strDataID]['request_date'];
  $arrResult['dataRemark'] = $dataEdit[$strDataID]['remark'];
  return $arrResult;
}

//====================== END FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
///***************** FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
function getDataPurchaseRequestByID($strDataID)
{
  global $db;
  $tbl = new cGaPurchaseRequest();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrResult['dataEmployee'] = $dataEdit[$strDataID]['employee_id'];
  $arrResult['dataPurchaseNo'] = $dataEdit[$strDataID]['id'];
  $arrResult['dataIdCategory'] = $dataEdit[$strDataID]['id_asset_category'];
  $arrResult['dataRequestDate'] = $dataEdit[$strDataID]['request_date'];
  $arrResult['dataRemark'] = $dataEdit[$strDataID]['remark'];
  return $arrResult;
}

//====================== END FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
///***************** FUNGSI Bila assigment DIKRIM DARI PARAMETER*******************************
function getDataItemByID($strDataID)
{
  global $db;
  $tbl = new cGaItemCategory();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrResult['dataIdCategory'] = $dataEdit[$strDataID]['id'];
  $arrResult['dataRemark'] = $dataEdit[$strDataID]['remark'];
  return $arrResult;
}

/************BEGIN FUNGSI CEK NOMER REQUEST*************/
function checkExistNo($No)
{
  global $db;
  $result = [];
  if ($db->connect()) {
    $strSQL = "SELECT COUNT(*) AS total FROM ga_request_order where request_order_no='" . $No . "' ";
    $res = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($res)) {
      $count = $rowDb['total'];
    }
  }
  return $count;
}

/************END FUNGSI CEK NOMER REQUEST***************/
//******************************** fungsi untuk menyimpan data*****************************************
function saveData()
{
  global $f;
  global $db;
  global $isNew;
  if ($db->connect()) {
    /// Cek apakah ada nomer reuest ada yang sama
    $noReq = $f->getValue('dataRequestNo');
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
      $tblSave = new cGaRequestOrder();
      //$dataGaPurchaseRequest = new cGaPurchaseRequest;
      $data = [
          "id_purchase_request" => $f->getValue('dataPurchaseNo'),
          "id_asset_category"   => $f->getValue('dataIdCategory'),
          "request_date"        => $f->getValue('dataRequestDate'),
          "request_order_no"    => $f->getValue('dataRequestNo'),
          "remark"              => $f->getValue('dataRemark')
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