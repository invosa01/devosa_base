<?php
/*
Author : Dily Same Alie
Date 	 : 22/11/2011
Desc	 : mencatat perpindahan barang, bisa antar lokasi, bisa juga antar
         karyawan. informasi ini mempengaruhi lokasi asset serta penanggung
         jawabnya. juga bisa mempengaruhi info data asset yang dipegang karyawan
         tertentu
File	 : asset_moving_edit.php
*/
//---- BEGIN INCLUDE ---------------------------//
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/asset_moving.php');
include_once('../classes/ga/item_edit.php');
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
  $strDataNameFrom = getRequestValue('dataNameFrom');
  $isNew = ($strDataID == "");
  // Variabel get ID item yang akan di asigmnent
  $strDataIDItem = getRequestValue('dataIDItem');
  $strDataIDFrom = getRequestValue('dataIDFrom');
  if ($strDataIDItem != "") {
    $arrData = getDataItemByID($strDataIDItem);
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
    /// Menampilakn kode employee form
    if ($strDataIDFrom != "") {
      $arrData['dataEmployeeNameFrom'] = $strDataIDFrom;
    }
    ///--end-------------------------------------
    // FORM INPUT ===============================================================================================================================
    $f = new clsForm("formInput", 1, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    $f->addHidden(
        "dataIdEmployeeFrom",
        $arrData['dataIdEmployeeFrom'],
        ["size" => 20, "readonly" => true],
        "string",
        false,
        true,
        true
    );
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
        ["style" => "width:200",],
        "",
        false,
        false,
        true
    );
    $f->addInput(
        getWords("employee ID  from "),
        "dataEmployeeNameFrom",
        $arrData['dataEmployeeNameFrom'],
        ["size" => 45, "readonly" => true],
        "string",
        false,
        true,
        true
    );
    $f->addSelect(
        getWords("Location Room From"),
        "dataIdRoomFrom",
        getDataListRoom(
            $arrData['dataIdRoomFrom'],
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
        false,
        true
    );
    $f->addSelect(
        getWords("department From"),
        "dataDepartmentFrom",
        getDataListDepartment(
            $arrData['dataDepartmentFrom'],
            true,
            [
                "value"    => "",
                "text"     => "",
                "selected" => true,
            ]
        ),
        ["style" => "width:200"],
        "",
        true,
        false,
        true
    );
    ///------ EMPLOYEE TO
    $f->addLabel("<hr>", "", "<hr>");
    $f->addInputAutoComplete(
        getWords("employee ID TO "),
        "dataEmployee",
        getDataEmployee($arrData['dataEmployee']),
        "style='width:250px' " . $strReadonly,
        "string",
        true
    );
    $f->addLabelAutoComplete("", "dataEmployee", "");
    $f->addSelect(
        getWords("Locatoin Room To "),
        "dataIdRoomTo",
        getDataListRoom(
            $arrData['dataIdRoomTo'],
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
    // Jika St ReadOnly kosong-------------------------------------------------------------------------------------------------------------
    if ($strReadonly == "") {
      $f->addSelect(
          getWords("departement To"),
          "dataDepartmentTo",
          getDataListDepartment(
              $arrData['dataDepartmentTo'],
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
          getWords("department TO"),
          "dataDepartmentTo",
          getDataListDepartment(getInitialValue("Department", "", $strDataDepartment), true),
          ["style" => "width:$strDefaultWidthPx"],
          "",
          false,
          ($ARRAY_DISABLE_GROUP['department'] == "")
      );
    }
    //--------------------------------------------------------------------------------------------------------------------------------------
    $f->addInput(
        getWords("moving date"),
        "dataMovingDate",
        $arrData['dataMovingDate'],
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
///***************** FUNGSI Bila assigment DIKRIM DARI PARAMETER*******************************
function getDataItemByID($strDataID)
{
  global $db;
  $tbl = new cGaItem();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrTemp = getEmployeeInfoByID($db, $dataEdit[$strDataID]['id_employee'], "employee_id");
  $arrResult['dataIdItem'] = $dataEdit[$strDataID]['id'];
  $arrResult['dataIdEmployeeFrom'] = $dataEdit[$strDataID]['id_employee'];
  $arrResult['dataEmployeeNameFrom'] = $arrTemp['employee_id']; // Employee TO
  $arrResult['dataIdRoomFrom'] = $dataEdit[$strDataID]['id_room'];
  $arrResult['dataDepartmentFrom'] = $dataEdit[$strDataID]['department_code'];
  return $arrResult;
}

//******************* END FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
///***************** FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
function getDataByID($strDataID)
{
  global $db;
  $tbl = new cGaAssetMoving();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrTemp = getEmployeeInfoByID($db, $dataEdit[$strDataID]['id_employee_to'], "employee_id");
  $arrResult['dataIdItem'] = $dataEdit[$strDataID]['id_item'];
  $arrResult['dataIdEmployeeFrom'] = $dataEdit[$strDataID]['id_employee_from'];
  $arrResult['dataEmployee'] = $arrTemp['employee_id']; // Employee TO
  $arrResult['dataIdRoomFrom'] = $dataEdit[$strDataID]['id_room_from'];
  $arrResult['dataIdRoomTo'] = $dataEdit[$strDataID]['id_room_to'];
  $arrResult['dataDepartmentFrom'] = $dataEdit[$strDataID]['department_code_from'];
  $arrResult['dataDepartmentTo'] = $dataEdit[$strDataID]['department_code_to'];
  $arrResult['dataMovingDate'] = $dataEdit[$strDataID]['moving_date'];
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
    $strIDEmployeeTo = getIDEmployee($db, $f->getValue('dataEmployee'));
    //------------ CLASS GA  asset asiigment -------------//
    $tblSave = new cGaAssetMoving;
    $data = [
        "id_item"              => $f->getValue('dataIdItem'),
        "moving_date"          => $f->getValue('dataMovingDate'),
        "id_employee_from"     => $f->getValue('dataIdEmployeeFrom'),
        "id_room_from"         => $f->getValue('dataIdRoomFrom'),
        "department_code_from" => $f->getValue('dataDepartmentFrom'),
        "id_employee_to"       => ($strIDEmployeeTo),
        "id_room_to"           => $f->getValue('dataIdRoomTo'),
        "department_code_to"   => $f->getValue('dataDepartmentTo'),
        "remark"               => $f->getValue('dataRemark')
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