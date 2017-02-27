<?php
/*
Author : Dily Same Alie
Date 	 : 23/11/2011
Desc	 : mencatat permintaan penggunaan kendaraan oleh karyawan
File	 : vehicle_request_edit.php
*/
//---- BEGIN INCLUDE ---------------------------//
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/vehicle_request.php');
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
    $f->addHidden("dataReqNoMatch", $arrData['dataReqNo']);
    $f->addInput(
        getWords("Request No"),
        "dataReqNo",
        $arrData['dataReqNo'],
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
            "Vehicle"
        ),
        ["style" => "width:200", "size" => 10],
        "",
        true,
        true,
        true
    );
    $f->addSelect(
        getWords("Driver"),
        "dataIdDriver",
        getDataListDriver(
            $arrData['dataIdDriver'],
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
    $f->addInputAutoComplete(
        getWords("Request By"),
        "dataEmployee",
        getDataEmployee($arrData['dataEmployee']),
        "style='width:250px' " . $strReadonly,
        "string",
        true
    );
    $f->addLabelAutoComplete("", "dataEmployee", "");
    $f->addInput(
        getWords("Request date"),
        "dataRequestDate",
        $arrData['dataRequestDate'],
        ["style" => "width:$strDateWidth"],
        "date",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("date from"),
        "dataDateFrom",
        $arrData['dataDateFrom'],
        ["style" => "width:$strDateWidth"],
        "date",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("date to"),
        "dataDateTo",
        $arrData['dataDateTo'],
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
/************BEGIN FUNGSI CEK NOMER REQUEST*************/
function checkExistNo($consNo)
{
  global $db;
  $result = [];
  if ($db->connect()) {
    $strSQL = "SELECT COUNT(*) AS total FROM ga_vehicle_request where vehicle_req_no='" . $consNo . "' ";
    $res = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($res)) {
      $count = $rowDb['total'];
    }
  }
  return $count;
}

/************END FUNGSI CEK NOMER REQUEST***************/
///***************** FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
function getDataByID($strDataID)
{
  global $db;
  $tbl = new cGaVehicleRequest();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrTemp = getEmployeeInfoByID($db, $dataEdit[$strDataID]['id_employee'], "employee_id");
  $arrResult['dataEmployee'] = $arrTemp['employee_id'];
  $arrResult['dataIdItem'] = $dataEdit[$strDataID]['id_item'];
  $arrResult['dataIdDriver'] = $dataEdit[$strDataID]['id_driver'];
  $arrResult['dataRequestDate'] = $dataEdit[$strDataID]['request_date'];
  $arrResult['dataDateFrom'] = $dataEdit[$strDataID]['date_from'];
  $arrResult['dataDateTo'] = $dataEdit[$strDataID]['date_to'];
  $arrResult['dataRemark'] = $dataEdit[$strDataID]['remark'];
  $arrResult['dataReqNo'] = $dataEdit[$strDataID]['vehicle_req_no'];
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
    $noReq = $f->getValue('dataReqNo');
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
      $strIDEmployee = getIDEmployee($db, $f->getValue('dataEmployee'));
      //------------ CLASS GA  asset asiigment -------------//
      $tblSave = new cGaVehicleRequest;
      $data = [
          "id_item"        => $f->getValue('dataIdItem'),
          "id_employee"    => ($strIDEmployee),
          "id_driver"      => $f->getValue('dataIdDriver'),
          "request_date"   => $f->getValue('dataRequestDate'),
          "date_from"      => $f->getValue('dataDateFrom'),
          "date_to"        => $f->getValue('dataDateTo'),
          "vehicle_req_no" => $f->getValue('dataReqNo'),
          "remark"         => $f->getValue('dataRemark')
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