<?php
//====== Include=====================================
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/stock_opname.php');
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
$strWordsEntryStockOpname = getWords("entry stock opname");
$strWordsStockOpnameList = getWords("stock opname list");
$db = new CdbClass;
$dataItem = new cGAStockOpname;
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
    //$f->addSelect(getWords("item"), "dataIdItem", getDataListItem($arrData['dataIdItem']),array("onChange" => "onItemChanged();"));
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
                "selected" => true,
            ],
            "Consumable"
        ),
        ["style" => "width:200", "size" => 10, "onChange" => "onItemChanged();"],
        "",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("stock opname date"),
        "dataStockOpnameDate",
        $arrData['dataStockOpnameDate'],
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
    $f->addInput(
        getWords("item old amount"),
        "dataItemOldAmount",
        $arrData['dataItemOldAmount'],
        ["readonly" => true],
        "string",
        false,
        true,
        true
    );
    $f->addTextArea(
        getWords("remark"),
        "dataRemark",
        $arrData['dataRemark'],
        ["cols" => 40, "rows" => 4, "maxlength" => 255],
        "string",
        false,
        true,
        true
    );
    //$f->addInput(getWords("approval"), "dataApproval", "", array("size" => 30), "", false, true, true);
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
  $tbl = new cGaStockOpname();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrTemp = getEmployeeInfoByID($db, $dataEdit[$strDataID]['id_employee'], "employee_id");
  $arrResult['dataEmployee'] = $arrTemp['employee_id'];
  $arrResult['dataIdItem'] = $dataEdit[$strDataID]['id_item'];
  $arrResult['dataStockOpnameDate'] = $dataEdit[$strDataID]['stock_opname_date'];
  $arrResult['dataItemAmount'] = $dataEdit[$strDataID]['item_amount'];
  $arrResult['dataItemOldAmount'] = $dataEdit[$strDataID]['item_old_amount'];
  $arrResult['dataRemark'] = $dataEdit[$strDataID]['remark'];
  //foreach($arrTripCost[$dataDonation ['trip_type']
  return $arrResult;
}

//====================== END FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
/*
  function getDataTransactionStock($idItem){
    global $db;
    $idItem;
	 $strSQL="select * from (
			  select i.item_amount AS amount, '+' AS Status from ga_consumable_stock_in AS i where id_item=3
			  UNION
			  select o.item_amount AS amount, '-' AS Status from ga_consumable_stock_out AS o where id_item=3
			  )AS ga_result";
	 $res = $db->execute($strSQL);
	 $totalStock="";
	 
     while ($rowDb = $db->fetchrow($res)){
	   	  /// Jika data baru ambil dari field item_stock
          $count = $rowDb['status'];
		  $lastStock = $rowDb['amount'];
		  $totalStock = $totalStock .$count .$lastStock ;
		  
        }
    	return $totalStock;
	
}*/
//******************************** fungsi untuk menyimpan data*****************************************
function saveData()
{
  global $f;
  global $db;
  global $isNew;
  if ($db->connect()) {
    $strmodified_byID = $_SESSION['sessionUserID'];
    $tblSave = new cGaStockOpname();
    $data = [
        "id_item"           => $f->getValue('dataIdItem'),
        "stock_opname_date" => $f->getValue('dataStockOpnameDate'),
        "item_amount"       => $f->getValue('dataItemAmount'),
        "item_old_amount"   => $f->getValue('dataItemOldAmount'),
        "remark"            => $f->getValue('dataRemark')
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
  } else {
    $f->message = "no connection";
    $f->msgClass = "bgError";
  }
  $f->message = $tblSave->strMessage;
}

//*********************************************************END  saveData ***************************************
?>