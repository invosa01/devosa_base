<?php
/*
Author : Dily Same Alie
Date 	 : 22/11/2011
Desc	 : daftar barang, termasuk dokumen dan ATK. Untuk yang bersifat
         item, berarti per 1 barang, untuk consumable, berarti 1 macam barang
         (misal kertas A4, gula, kopi dsb).
File	 : item_edit.php
*/
//====== Include=====================================
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
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
    $f = new clsForm("formInput", 2, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    $f->addSelect(
        getWords("Category Item"),
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
        getWords("item unit"),
        "dataItemUnit",
        $arrData['dataItemUnit'],
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
        "string",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("Entry date"),
        "dataItemEntryDate",
        $arrData['dataItemEntryDate'],
        ["style" => "width:$strDateWidth"],
        "date",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("due date"),
        "dataItemDueDate",
        $arrData['dataItemDueDate'],
        ["style" => "width:$strDateWidth"],
        "date",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("stock"),
        "dataItemStock",
        $arrData['dataItemStock'],
        ["size" => 10],
        "string",
        true,
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
        true
    );
    $f->addLabelAutoComplete("", "dataEmployee", "");
    //$f->addSelect(getWords("Lacation Room"), "dataIdRoom",$arrData['dataIdRoom'], getDataRooms());
    $f->addSelect(
        getWords("Locatoin Room"),
        "dataIdRoom",
        getDataListRoom(
            $arrData['dataIdRoom'],
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
    // Jika St ReadOnly kosong-------------------------------------------------------------------------------------------------------------
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
          false,
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
  $tbl = new cGaItem();
  $dataEdit = $tbl->findAll("id = $strDataID", "", "", null, 1, "id");
  $arrTemp = getEmployeeInfoByID($db, $dataEdit[$strDataID]['id_employee'], "employee_id");
  $arrResult['dataEmployee'] = $arrTemp['employee_id'];
  $arrResult['dataIdCategory'] = $dataEdit[$strDataID]['id_category'];
  $arrResult['dataItemCode'] = $dataEdit[$strDataID]['item_code'];
  $arrResult['dataItemName'] = $dataEdit[$strDataID]['item_name'];
  $arrResult['dataItemUnit'] = $dataEdit[$strDataID]['item_unit'];
  $arrResult['dataItemPrice'] = $dataEdit[$strDataID]['item_price'];
  $arrResult['dataItemEntryDate'] = $dataEdit[$strDataID]['item_entry_date'];
  $arrResult['dataItemDueDate'] = $dataEdit[$strDataID]['item_due_date'];
  $arrResult['dataItemStock'] = $dataEdit[$strDataID]['item_stock'];
  $arrResult['dataSerialNumber'] = $dataEdit[$strDataID]['serial_number'];
  $arrResult['dataRemark'] = $dataEdit[$strDataID]['remark'];
  $arrResult['dataIdRoom'] = $dataEdit[$strDataID]['id_room'];
  $arrResult['dataDepartment'] = $dataEdit[$strDataID]['department_code'];
  //foreach($arrTripCost[$dataDonation ['trip_type']
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
    $strmodified_byID = $_SESSION['sessionUserID'];
    $strIDEmployee = getIDEmployee($db, $f->getValue('dataEmployee'));
    $tblSave = new cGaItem();
    $data = [
        "id_category"     => $f->getValue('dataIdCategory'),
        "item_code"       => $f->getValue('dataItemCode'),
        "item_name"       => $f->getValue('dataItemName'),
        "item_unit"       => $f->getValue('dataItemUnit'),
        "item_price"      => $f->getValue('dataItemPrice'),
        "item_entry_date" => $f->getValue('dataItemEntryDate'),
        "item_due_date"   => $f->getValue('dataItemDueDate'),
        "item_stock"      => $f->getValue('dataItemStock'),
        "serial_number"   => $f->getValue('dataSerialNumber'),
        "id_employee"     => ($strIDEmployee),
        "id_room"         => $f->getValue('dataIdRoom'),
        "department_code" => $f->getValue('dataDepartment'),
        "remark"          => $f->getValue('dataRemark')
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