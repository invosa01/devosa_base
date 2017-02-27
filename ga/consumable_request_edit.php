<?php
/*
Author : Dily Same Alie
Date 	 : 27/11/2011
Desc	 : mencatat permintaan barang yang bersifat consumable dari
         karyawan ke GA
File	 : consumable_request_edit.php
*/
//---- BEGIN INCLUDE ---------------------------//
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/item_edit.php');
include_once('../classes/ga/consumable_request.php');
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
// END hak aksses ===========================================================================================================
$db = new CdbClass;
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
    // FORM INPUT ===============================================================================================================================
    $f = new clsForm("formInput", 1, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    $f->addHidden("dataConsReqNoMatch", $arrData['dataConsReqNo']);
    $f->addInput(
        getWords("Request No"),
        "dataConsReqNo",
        $arrData['dataConsReqNo'],
        ["size" => 40,],
        "string",
        true,
        true,
        true
    );
    $f->addSelect(
        getWords("Item"),
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
        ["style" => "width:200", "size" => 10],
        "",
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
        true
    );
    $f->addLabelAutoComplete("", "dataEmployee", "");
    // Jika Str ReadOnly kosong-------------------------------------------------------------------------------------------------------------
    if ($strReadonly == "") {
      $f->addSelect(
          getWords("departement"),
          "dataDepartment",
          getDataListDepartment(
              $arrData['dataDepartment'],
              true,
              [
                  "value"    => "",
                  "text"     => "",
                  "selected" => true
              ]
          ),
          ["style" => "width:200"],
          "",
          true,
          true,
          true
      );
    } else {
      $f->addSelect(
          getWords("department"),
          "dataDepartment",
          getDataListDepartment(getInitialValue("Department", "", $strDataDepartment), true),
          ["style" => "width:$strDefaultWidthPx"],
          "",
          false,
          ($ARRAY_DISABLE_GROUP['department'] == "")
      );
    }
    //--------------------------------------------------------------------------------------------------------------------------------------
    $f->addInput(
        getWords("Amount"),
        "dataAmount",
        $arrData['dataAmount'],
        ["size" => 20,],
        "numeric",
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
  $tbl = new cGaConsumableRequest();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrTemp = getEmployeeInfoByID($db, $dataEdit[$strDataID]['id_employee'], "employee_id");
  $arrResult['dataEmployee'] = $arrTemp['employee_id'];
  $arrResult['dataIdItem'] = $dataEdit[$strDataID]['id_item'];
  $arrResult['dataAmount'] = $dataEdit[$strDataID]['item_amount'];
  $arrResult['dataRequestDate'] = $dataEdit[$strDataID]['request_date'];
  $arrResult['dataRemark'] = $dataEdit[$strDataID]['remark'];
  $arrResult['dataConsReqNo'] = $dataEdit[$strDataID]['consumable_req_no'];;
  $arrResult['dataDepartment'] = $dataEdit[$strDataID]['department_code'];
  return $arrResult;
}

//******************* END FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
/************BEGIN FUNGSI CEK NOMER REQUEST*************/
function checkExistConsNo($consNo)
{
  global $db;
  $result = [];
  if ($db->connect()) {
    $strSQL = "SELECT COUNT(*) AS total FROM ga_consumable_request where consumable_req_no='" . $consNo . "' ";
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
    $noCons = $f->getValue('dataConsReqNo');
    $noConsMatch = $f->getValue('dataConsReqNoMatch');
    $strMessage = "";
    // Cek data apakah ada nomer request tersebut
    if ($isNew) {
      $found = checkExistConsNo($noCons);
    } elseif ($noCons != $noConsMatch) {
      $found = checkExistConsNo($noCons);
    }
    //// Jika ditemukan nomer requestnya
    if ($found > 0) {
      $strMessage = "Your Number Request Already Exist";
    } else {
      $strIDEmployee = getIDEmployee($db, $f->getValue('dataEmployee'));
      //------------ CLASS GA  asset asiigment -------------//
      $tblSave = new cGaConsumableRequest;
      $data = [
          "id_item"           => $f->getValue('dataIdItem'),
          "id_employee"       => ($strIDEmployee),
          "item_amount"       => $f->getValue('dataAmount'),
          "department_code"   => $f->getValue('dataDepartment'),
          "request_date"      => $f->getValue('dataRequestDate'),
          "consumable_req_no" => $f->getValue('dataConsReqNo'),
          "remark"            => $f->getValue('dataRemark')
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