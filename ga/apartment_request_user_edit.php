<?php
/*
  Author : Dily Same Alie
  Date 	 : 27/11/2011
  Desc	 : daftar orang (bisa karyawan atau tamu) yang akan menggunakan apartement

  File	 : apartment_request_user_edit.php
  */
//---- BEGIN INCLUDE ---------------------------//
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/item_edit.php');
include_once('../classes/ga/apartment_request_user.php');
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
    //Jika data baru
    if ($isNew) {
      $f->addSelect(
          getWords("Apartment Request"),
          "dataIdApartmentRequest",
          getDataLisApartmentRequest(
              $arrData['dataIdApartmentRequest'],
              true,
              ["value" => "", "text" => "", "selected" => true],
              "AND active='YES'"
          ),
          ["size" => 10],
          "",
          true,
          true,
          true
      );
    } else {
      $f->addSelect(
          getWords("Apartment Request"),
          "dataIdApartmentRequest",
          getDataLisApartmentRequest(
              $arrData['dataIdApartmentRequest'],
              true,
              ["value" => "", "text" => "", "selected" => true],
              ""
          ),
          ["size" => ""],
          "",
          true,
          false,
          true
      );
    }
    //---
    $f->addInputAutoComplete(
        getWords("employee ID"),
        "dataEmployee",
        getDataEmployee($arrData['dataEmployee']),
        "style='width:250px' " . $strReadonly,
        "string",
        false
    );
    $f->addLabelAutoComplete("", "dataEmployee", "");
    $f->addInput(
        getWords("User Name"),
        "dataUserName",
        $arrData['dataUserName'],
        ["size" => 45],
        "string",
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
//****************************** BEGIN Get Datar reuquest ****************************************//
function getDataApartmentRequest()
{
  global $db;
  $result = [];
  if ($db->connect()) {
    $strSQL = "SELECT a.*,a.id AS id_ap,r.room_name AS room_name
	              FROM ga_apartment_request AS a LEFT JOIN ga_room AS r ON a.id_room=r.id  ORDER BY request_date";
    $res = $db->execute($strSQL);
    if ($default != null || $default != "") {
      while ($rowDb = $db->fetchrow($res)) {
        $result[] = [
            "value" => $rowDb['id_ap'],
            "text" => $rowDb['request_date'] . " / " . $rowDb['room_name'],
            "selected" => false
        ];
      }
    }
  }
  return $result;
}

//****************************** END Get Data Room ***********************************************//
///***************** FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
function getDataByID($strDataID)
{
  global $db;
  $tbl = new cGaApartmentRequestUser();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrTemp = getEmployeeInfoByID($db, $dataEdit[$strDataID]['id_employee'], "employee_id");
  $arrResult['dataEmployee'] = $arrTemp['employee_id'];
  $arrResult['dataIdApartmentRequest'] = $dataEdit[$strDataID]['id_ga_apartment_request'];
  $arrResult['dataUserName'] = $dataEdit[$strDataID]['user_name'];
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
    $strIDEmployee = getIDEmployee($db, $f->getValue('dataEmployee'));
    //------------ CLASS GA  asset maintenance -------------//
    $tblSave = new cGaApartmentRequestUser;
    $data = [
        "id_ga_apartment_request" => $f->getValue('dataIdApartmentRequest'),
        "id_employee"             => ($strIDEmployee),
        "user_name"               => $f->getValue('dataUserName'),
        "remark"                  => $f->getValue('dataRemark')
    ];
    //------------END GA  asset maintenance---------------//
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