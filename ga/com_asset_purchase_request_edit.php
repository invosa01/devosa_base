<?php
//====== Include=====================================
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/ga_purchase_request.php');
include_once('../classes/ga/data_category_item.php');
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
$strWordsEntryPR = getWords("entry Purchase Request");
$strWordsPRList = getWords("Purchase Request list");
$targetFile = 'C:\xampp\htdocs\innogene\ga\prdoc';
// Variabel get ID item yang akan di asigmnent
$strDataIdCategory = getRequestValue('dataIdCategory');
if ($strDataIdCategory != "") {
  $arrData = getDataItemByID($strDataIdCategory);
}
if ($strDataID != "") {
  $arrData = getDataByID($strDataID);
}
$strNow = date('Y-m-d');
$db = new CdbClass;
$dataItem = new cGaPurchaseRequest;
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
    $f->addHidden("dataReqNoMatch", $arrData['dataPurchaseNo']);
    $f->addInput(
        getWords("purchase request number"),
        "dataPurchaseNo",
        $arrData['dataPurchaseNo'],
        "style='width:250px' ",
        "string",
        true,
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
    $f->addSelect(
        getWords("item category"),
        "dataIdCategory",
        getDataListCategoryItem(
            $arrData['dataIdCategory'],
            true,
            [
                "value" => "",
                "text" => "",
                "selected" => true
            ]
        ),
        "style='width:250px' ",
        "",
        false,
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
        ["cols" => 40, "rows" => 4, "maxlength" => 255],
        "string",
        true,
        true,
        true
    );
    $f->addFile(
        getWords("attachment file"),
        "dataAttachmentFile",
        $arrData['dataAttachmentFile'],
        ["size" => 30],
        "string",
        true,
        true,
        true,
        "",
        "",
        true,
        null,
        $targetFile
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
  $tbl = new cGaPurchaseRequest();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrTemp = getEmployeeInfoByID($db, $dataEdit[$strDataID]['id_employee'], "employee_id");
  $arrResult['dataEmployee'] = $arrTemp['employee_id'];
  $arrResult['dataPurchaseNo'] = $dataEdit[$strDataID]['purchase_no'];
  $arrResult['dataIdCategory'] = $dataEdit[$strDataID]['id_asset_category'];
  $arrResult['dataRequestDate'] = $dataEdit[$strDataID]['request_date'];
  $arrResult['dataRemark'] = $dataEdit[$strDataID]['remark'];
  $arrResult['dataAttachmentFile'] = $dataEdit[$strDataID]['attachment_file'];
  //$arrResult['dataAktifasi']        = $dataEdit[$strDataID]['aktifasi'];
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
    $strSQL = "SELECT COUNT(*) AS total FROM ga_purchase_request where purchase_no='" . $reqNo . "' ";
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
  $tbl = new cGaItemCategory();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrResult['dataIdCategory'] = $dataEdit[$strDataID]['id'];
  $arrResult['dataRemark'] = $dataEdit[$strDataID]['remark'];
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
    $noReq = $f->getValue('dataPurchaseNo');
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
      $strmodified_byID = $_SESSION['sessionUserID'];
      $strIDEmployee = getIDEmployee($db, $f->getValue('dataEmployee'));
      $tblSave = new cGaPurchaseRequest();
      //$dataGaPurchaseRequest = new cGaPurchaseRequest;
      $data = [
          "id_asset_category" => $f->getValue('dataIdCategory'),
          "id_employee"       => $strIDEmployee,
          "request_date"      => $f->getValue('dataRequestDate'),
          "remark"            => $f->getValue('dataRemark'),
          "attachment_file"   => $f->getValue('dataAttachmentFile'),
          "purchase_no"       => $f->getValue('dataPurchaseNo'),
      ];
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