<?php
/*
Author : Dily Same Alie
Date 	 : 22/11/2011
Desc	 : daftar barang, termasuk dokumen dan ATK. Untuk yang bersifat
         item, berarti per 1 barang, untuk consumable, berarti 1 macam barang
         (misal kertas A4, gula, kopi dsb).
File	 : item_list.php
*/
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/item_edit.php');
//================ END INCLUDE=====================================
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
//INISIALISASI---------------------------------------------------------------------------------------------------------------
$strWordsDataEntry = getWords("data entry");
$DataGrid = "";
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
// *************************** BEGIN Fungsi ISI DATA GRID  ********************************************************************
function getData($db)
{
  global $dataPrivilege, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck;
  global $f;
  global $DataGrid;
  global $myDataGrid;
  global $strKriteriaCompany;
  //global $arrUserInfo
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if ($arrData['dataIdCategory'] != "") {
    $strKriteria .= "AND id_category = '" . $arrData['dataIdCategory'] . "'";
  }
  if (validStandardDate($arrData['dataEntryDateFrom']) && validStandardDate($arrData['dataEntryDateThru'])) {
    $strKriteria .= "AND (i.item_entry_date::date BETWEEN '" . $arrData['dataEntryDateFrom'] . "' AND '" . $arrData['dataEntryDateThru'] . "')  ";
  }
  if ($arrData['dataEmployeeID'] != "") {
    $strKriteria .= "AND e.employee_id = '" . $arrData['dataEmployeeID'] . "'";
  }
  if ($arrData['dataItemCode'] != "") {
    $strKriteria .= "AND i.item_code = '" . $arrData['dataItemCode'] . "'";
  }
  if ($arrData['dataItemName'] != "") {
    $strKriteria .= "AND i.item_name = '" . $arrData['dataItemName'] . "'";
  }
  if ($arrData['dataItemPrice'] != "") {
    $strKriteria .= "AND i.item_price = '" . $arrData['dataItemPrice'] . "'";
  }
  if ($arrData['dataSerialNumber'] != "") {
    $strKriteria .= "AND i.serial_number = '" . $arrData['dataSerialNumber'] . "'";
  }
  if ($arrData['dataIdRoom'] != "") {
    $strKriteria .= "AND i.id_room = '" . $arrData['dataIdRoom'] . "'";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND i.department_code = '" . $arrData['dataDepartment'] . "'";
  }
  if ($arrData['dataActive'] != "") {
    $strKriteria .= "AND i.active = '" . $arrData['dataActive'] . "'";
  }
  $strKriteria .= "AND i.active='YES'";
  $strKriteria .= $strKriteriaCompany;
  if ($db->connect()) {
    $myDataGrid = new cDataGrid("formData", "DataGrid1");
    $myDataGrid->caption = getWords(
        strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
    );
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->setCriteria($strKriteria);
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => '']),
        true /*bolDisableSelfStatusChange*/
    );
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Category"), "category_name", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("Code"), "item_code", ['width' => '100'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("Name"), "item_name", ['width' => '150'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("unit"), "item_unit", ['width' => '50'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("price"), "item_price", ['width' => '100'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Entry Date"), "item_entry_date", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Due Date"), "item_due_date", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("stock"), "item_stock", ['width' => '40'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column("SN", "serial_number", ['width' => '170'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Employee "), "employee_name", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("Rooms"), "room_name", ['width' => '100'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Code Department"), "department_code", ['width' => '100'], ['nowrap' => ''])
    );
    if (!isset($_POST['btnExportXLS']) && $bolCanEdit) {
      $myDataGrid->addColumn(
          new DataGrid_Column(
              "",
              "",
              ["width" => "60"],
              ['align' => 'center', 'nowrap' => ''],
              false,
              false,
              "",
              "printEditLink()",
              "",
              false /*show in excel*/
          )
      );
    }
    foreach ($arrData AS $key => $value) {
      $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
    }
    //-----------------BEGIN Jika Punya Hak Akses Hapus-----------------------------//
    if ($bolCanDelete) {
      $myDataGrid->addSpecialButton(
          "btnDelete",
          "btnDelete",
          "submit",
          "Delete",
          "onClick=\"javascript:return myClient.confirmDelete();\"",
          "deleteData()"
      );
    }
    //---------------- END Jika Punya Hak Akses Hapus-------------------------//
    //----------------BEGIN JIKA Punya Hak Edit---------------------------------//
    if ($bolCanEdit) {
      $myDataGrid->addSpecialButton(
          "btnAssigment",
          "btnAssigment",
          "submit",
          "Assignment",
          "onClick=\"javascript:return confirm('Do you want Assignmet this item?');\"",
          "AssignmentButton()"
      );
    }
    $myDataGrid->addSpecialButton(
        "btnDisposal",
        "btnDisposal",
        "submit",
        "Disposal",
        "onClick=\"javascript:return confirm('Do you want Disposal this item?');\"",
        "DisposalButton()"
    );
    $myDataGrid->addSpecialButton(
        "btnMaintenance",
        "btnMaintenance",
        "submit",
        "Maintenance",
        "onClick=\"javascript:return confirm('Do you want Maintenance this item?');\"",
        "MaintenanceButton()"
    );
    //---------------- END Jika Punya Hak Akses Tambah-------------------------//
    $myDataGrid->addButtonExportExcel(
        "Export Excel",
        $dataPrivilege['menu_name'] . ".xls",
        getWords($dataPrivilege['menu_name'])
    );
    $myDataGrid->getRequest();
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_item AS i LEFT JOIN hrd_employee AS e ON i.id_employee = e.id";
    $strSQL = "SELECT c.category_name AS category_name, r.room_name AS room_name,
	                   e.employee_name AS employee_name ,
	                   e.employee_id AS employee_id ,
					   i.* 
	  				   FROM ga_item AS i LEFT JOIN ga_item_category as c ON i.id_category=c.id
	                   LEFT JOIN ga_room AS r ON i.id_room=r.id
					   LEFT JOIN hrd_employee AS e ON i.id_employee=e.id";
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
    $dataset = $myDataGrid->getData($db, $strSQL);
    //bind Datagrid with array dataset and branchCode
    $myDataGrid->bind($dataset);
    $DataGrid = $myDataGrid->render();
  } else {
    $DataGrid = "";
  }
  return $DataGrid;
}

//************** END FUNGSI ISI DATA GRID ****************************************************************************************
//*********************************** FUNGSI TOMBOL EDIT******************************************
function printEditLink($params)
{
  extract($params);
  return "<a href=\"item_edit.php?dataID=" . $record['id'] . "\">" . getWords('edit') . "</a>";
}

//******************************* END FUNGSI TOMBOL EDIT *******************************************
//********************************** BEGIN Fungsi assignmet button****************************************
function AssignmentButton()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  header("location:asset_moving_edit.php?dataIDItem=" . $strValue . "");
}

//************************************************END assignmet **************************************
//********************************** BEGIN Fungsi disposal button****************************************
function DisposalButton()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  header("location:asset_disposal_edit.php?dataIDItem=" . $strValue . "");
}

//************************************************END disposal **************************************
//********************************** BEGIN Fungsi maintenance button****************************************
function MaintenanceButton()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  header("location:asset_maintenance_edit.php?dataIDItem=" . $strValue . "");
}

//************************************************END maintenace **************************************
//********************************** BEGIN Fungsi untuk menghapus data****************************************
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $tblDelete = new cGaItem();
  $tblDelete->deleteMultiple($arrKeys);
  $myDataGrid->message = $tblDelete->strMessage;
}

//************************************************END deleteData **************************************
//============================================================ MAIN PROGRAM ==========================================================
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  $strDataID = getPostValue('dataID');
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  $strReadonly = (scopeCBDataEntry($strDataEmployee, $_SESSION['sessionUserRole'], $arrUserInfo)) ? "readonly" : "";
  /// Form ==================================================================================
  $f = new clsForm("formFilter", 2, "100%", "");
  $f->caption = strtoupper($strWordsFILTERDATA);
  $f->addSelect(
      getWords("Category Item"),
      "dataIdCategory",
      getDataListCategoryItem(""),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addInput(getWords("Item Code"), "dataItemCode", "", ["size" => 50], "string", false, true, true);
  $f->addInput(getWords("item name"), "dataItemName", "", ["size" => 50], "string", false, true, true);
  $f->addInput(getWords("item price"), "dataItemPrice", "", ["size" => 50], "string", false, true, true);
  $f->addInput(
      getWords("Entry date From"),
      "dataEntryDateFrom",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      getWords("ENTRY date Thru"),
      "dataEntryDateThru",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(getWords("serial number"), "dataSerialNumber", "", ["size" => 50], "string", false, true, true);
  $f->addInputAutoComplete(
      getWords("employee ID"),
      "dataEmployeeID",
      getDataEmployee(getInitialValue("Employee", null, $strDataEmployee)),
      "style=width:$strDefaultWidthPx " . $strReadonly,
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployeeID", "");
  $f->addSelect(
      getWords("Lacation Room"),
      "dataIdRoom",
      getDataListRoom(""),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("department "),
      "dataDepartment",
      getDataListDepartment(getInitialValue("Department", "", $strDataDepartment), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['department'] == "")
  );
  //$f->addSelect(getWords(" Active Item"), "dataActive", getStatusAktivItem(),array("style" => "width:$strDefaultWidthPx"), "", false);
  $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
  $f->addButton("btnAdd", getWords("Clear"), ["onClick" => "location.href='" . basename($_SERVER['PHP_SELF'] . "';")]);
  $formFilter = $f->render();
  getData($db);
  // END FORM====================================================================================
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
?>