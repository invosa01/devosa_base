<?php
/*
Author : Dily Same Alie
Date 	 : 23/11/2011
Desc	 : mencatat informasi barang yang dibuang atau sudah tidak
         dipakai.�informasi ini mempengaruhi lokasi asset serta penanggung
         jawabnya. juga bisa mempengaruhi info data asset yang dipegang karyawan
         tertentu.
File	 : asset_disposal_edit.php
*/
//---- BEGIN INCLUDE ---------------------------//
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/asset_disposal.php');
//---- END INCLUDE -----------------------------//
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
  $isNew = ($strDataID == "");
  // Variabel get ID item yang akan di disposal
  $strDataIDItem = getRequestValue('dataIDItem');
  if ($strDataIDItem != "") {
    $arrData['dataIdItem'] = $strDataIDItem;
  }
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
    // FORM INPUT ===============================================================================================================================
    $f = new clsForm("formInput", 1, "100%", "");
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
        ["style" => "width:200"],
        "",
        false,
        false,
        true
    );
    $f->addInputAutoComplete(
        getWords("employee ID"),
        "dataEmployee",
        getDataEmployee($arrData['dataEmployee']),
        "style='width:250px' " . $strReadonly,
        "string",
        true
    );
    $f->addLabelAutoComplete("", "dataEmployee", "");
    $f->addInput(
        getWords("due date"),
        "dataDisposalDate",
        $arrData['dataDisposalDate'],
        ["style" => "width:$strDateWidth"],
        "date",
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
    $f->addButton("btnAdd", getWords("Back"), ["onClick" => "self.history.back();"]);
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
  $tbl = new cGaAssetDisposal();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrTemp = getEmployeeInfoByID($db, $dataEdit[$strDataID]['id_employee'], "employee_id");
  $arrResult['dataEmployee'] = $arrTemp['employee_id'];
  $arrResult['dataIdItem'] = $dataEdit[$strDataID]['id_item'];
  $arrResult['dataDisposalDate'] = $dataEdit[$strDataID]['disposal_date'];
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
    $strRelationType = "";
    $strIDEmployee = getIDEmployee($db, $f->getValue('dataEmployee'));
    //------------ CLASS GA  asset asiigment -------------//
    $tblSave = new cGaAssetDisposal;
    $data = [
        "id_item"       => $f->getValue('dataIdItem'),
        "id_employee"   => ($strIDEmployee),
        "disposal_date" => $f->getValue('dataDisposalDate'),
        "remark"        => $f->getValue('dataRemark')
    ];
    //------------END GA  asset asiigment---------------//
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