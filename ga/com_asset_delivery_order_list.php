<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/delivery_order.php');
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
  if ($arrData['dataDONumber'] != "") {
    $strKriteria .= "AND d.delivery_order_no = '" . $arrData['dataDONumber'] . "'";
  }
  if (validStandardDate($arrData['dataDeliveryDate']) && validStandardDate($arrData['dataDueDate'])) {
    $strKriteria .= "AND (d.delivery_date::date BETWEEN '" . $arrData['dataDeliveryDate'] . "' AND '" . $arrData['dataDueDate'] . "')  ";
  }
  if ($arrData['dataEmployeeID'] != "") {
    $strKriteria .= "AND e.employee_id = '" . $arrData['dataEmployeeID'] . "'";
  }
  if ($arrData['dataIdRequestOrder'] != "") {
    $strKriteria .= "AND d.id_request_order = '" . $arrData['dataIdRequestOrder'] . "'";
  }
  if ($arrData['dataIdPurchaseRequest'] != "") {
    $strKriteria .= "AND d.id_purchase_request = '" . $arrData['dataIdPurchaseRequest'] . "'";
  }
  if ($arrData['dataItemCode'] != "") {
    $strKriteria .= "AND d.item_code = '" . $arrData['dataItemCode'] . "'";
  }
  if ($arrData['dataItemName'] != "") {
    $strKriteria .= "AND d.item_name = '" . $arrData['dataItemName'] . "'";
  }
  if ($arrData['dataItemPrice'] != "") {
    $strKriteria .= "AND d.item_price = '" . $arrData['dataItemPrice'] . "'";
  }
  if ($arrData['dataSerialNumber'] != "") {
    $strKriteria .= "AND d.serial_number = '" . $arrData['dataSerialNumber'] . "'";
  }
  if ($arrData['dataVendor'] != "") {
    $strKriteria .= "AND v.id = '" . $arrData['dataVendor'] . "'";
  }
  if ($db->connect()) {
    $myDataGrid->caption = getWords(
        strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
    );
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->setCriteria($strKriteria);
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => '']),
        false /*bolDisableSelfStatusChange*/
    );
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("Delivery Order Number"), "delivery_order_no", ['width' => '100'], ['nowrap' => '']
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Request Order"), "request_order_no", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Purchase Request"), "purchase_no", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Category"), "category_name", ['width' => '100'], ['nowrap' => ''])
    );
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("Item ID"), "id_item", array('width' => '100'),array('nowrap' => '')));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("Code"), "item_code", ['width' => '100'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("Name"), "item_name", ['width' => '150'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("price"), "item_price", ['width' => '100'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Delivery Date"), "delivery_date", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Due Date"), "item_due_date", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column("SN", "serial_number", ['width' => '170'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Vendor"), "vendor_name", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("remark"), "remark", ['width' => '100'], ['nowrap' => '']));
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
    $myDataGrid->addButtonExportExcel(
        "Export Excel",
        $dataPrivilege['menu_name'] . ".xls",
        getWords($dataPrivilege['menu_name'])
    );
    $myDataGrid->getRequest();
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_delivery_order AS d LEFT JOIN ga_vendor AS v ON d.id_vendor=v.id";
    $strSQL = "SELECT c.category_name AS category_name, pr.purchase_no,
	                  v.vendor_name, ro.request_order_no,
					   d.* 
	  				   FROM ga_delivery_order AS d LEFT JOIN ga_item_category as c ON d.id_asset_category=c.id
	                   LEFT JOIN ga_purchase_request AS pr ON d.id_purchase_request=pr.id
	                   LEFT JOIN ga_request_order AS ro ON d.id_request_order=ro.id
					   LEFT JOIN ga_vendor AS v ON d.id_vendor=v.id";
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
  return "<a href=\"com_asset_delivery_order_edit.php?dataIDItem=" . $record['id_item'] . "&dataID=" . $record['id'] . "\">" . getWords(
      'edit'
  ) . "</a>";
}

//******************************* END FUNGSI TOMBOL EDIT *******************************************
//********************************** BEGIN Fungsi untuk menghapus data****************************************
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $tblDelete = new cGaDeliveryOrder();
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
  $f->addInput(getWords("Delivery order number#"), "dataDONumber", "", ["size" => 50], "string", false, true, true);
  $f->addSelect(
      getWords("Request Order ID"),
      "dataIdRequestOrder",
      getDataRequestOrder(""),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("Purchase Request ID"),
      "dataIdPurchaseRequest",
      getDataListPurchaseNo(""),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("Asset Category Item"),
      "dataIdCategory",
      getDataListCategoryItem(""),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("vendor"),
      "dataVendor",
      getDataVendor(""),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  //$f->addInput(getWords("Item ID"), "dataItemId", "", array("size" => 50), "string", false, true, true);
  $f->addInput(getWords("Item Code"), "dataItemCode", "", ["size" => 50], "string", false, true, true);
  $f->addInput(getWords("item name"), "dataItemName", "", ["size" => 50], "string", false, true, true);
  $f->addInput(getWords("item price"), "dataItemPrice", "", ["size" => 50], "numeric", false, true, true);
  $f->addInput(
      getWords("delivery date"),
      "dataDeliveryDate",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(getWords("due date"), "dataDueDate", "", ["style" => "width:$strDateWidth"], "date", false, true, true);
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