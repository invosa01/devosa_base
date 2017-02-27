<?php
define("SQL_TRUE", 't');
define("SQL_FALSE", 'f');
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/ga_consumable_purchase.php');
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
$strWordsINPUTDATA = getWords("Form Input Data");
$strWordsEntryConsumablePurchase = getWords("entry Consumable Purchase");
$strWordsConsumablePurchaseList = getWords("Consumable Purchase list");
// Get tanggal hari ini
$strNow = date("Y-m-d");
$DataGrid = "";
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
//DAFTAR FUNGSI--------------------------------------------------------------------------------------------------------------
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
  if ($arrData['dataIdItem'] != "") {
    $strKriteria .= "AND cp.id_item = '" . $arrData['dataIdItem'] . "'";
  }
  if ($arrData['dataRequestDate'] != "") {
    $strKriteria .= "AND cp.request_date = '" . $arrData['dataRequestDate'] . "'";
  }
  if ($arrData['dataItemAmount'] != "") {
    $strKriteria .= "AND cp.item_amount = '" . $arrData['dataItemAmount'] . "'";
  }
  if ($arrData['dataStatus'] != "") {
    $strKriteria .= "AND cp.status = '" . $arrData['dataStatus'] . "'";
  }
  if ($arrData['dataRemark'] != "") {
    $strKriteria .= "AND cp.remark = '" . $arrData['dataRemark'] . "'";
  }
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
    //-------------------------------------- BEGIN Data Grid---------------------------------------------------------------------------------//
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("Consumable Purchase No"),
            "consumable_purchase_no",
            ['width' => '100'],
            ['nowrap' => '']
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("Consumable Request No"),
            "consumable_request_no",
            ['width' => '100'],
            ['nowrap' => '']
        )
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("Item"), "item_name", ['width' => '100'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("request Date"), "request_date", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("item amount"), "item_amount", ['width' => '150'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("remark"), "remark", ['width' => '150'], ['nowrap' => '']));
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
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_consumable_purchase as cp
	  				   LEFT JOIN ga_consumable_request AS cr ON cp.id_consumable_request=cr.id
					   LEFT JOIN hrd_employee AS e ON cr.id_employee=e.id";
    $strSQL = "SELECT i.item_name AS item_name,
	  				   cr.consumable_req_no AS consumable_request_no,
	  				   e.id AS id_employee,
	  				   cp.*
	  				   FROM ga_consumable_purchase AS cp
					   LEFT JOIN ga_item as i ON cp.id_item=i.id
					   LEFT JOIN ga_consumable_request AS cr ON cp.id_consumable_request=cr.id
					   LEFT JOIN hrd_employee AS e ON cr.id_employee=e.id";
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

//*********************************** FUNGSI TOMBOL EDIT******************************************
function printEditLink($params)
{
  extract($params);
  return "<a href=\"consumable_purchase_edit.php?dataID=" . $record['id'] . "\">" . getWords('edit') . "</a>";
}

//******************************* END FUNGSI TOMBOL EDIT *******************************************
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $tblDelete = new cGaConsumablePurchase();
  $tblDelete->deleteMultiple($arrKeys);
  $myDataGrid->message = $tblDelete->strMessage;
}

//************************************************END deleteData **************************************
//================================================== BEGIN MAIN PROGRAM =============================================================================
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
  //generate form untuk select trip type
  $f = new clsForm("formFilter", 1, "100%", "");
  $f->caption = strtoupper($strWordsFILTERDATA);
  $f->addHidden("dataID", $strDataID);
  $f->addSelect(
      getWords("item name"),
      "dataIdItem",
      getDataListItemCriteria(
          $db,
          $arrData['dataIdItem'],
          true,
          [
              "value" => "",
              "text" => "",
              "selected" => true
          ],
          "Consumable"
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
      false,
      true,
      true
  );
  $f->addInput(
      getWords("item amount"),
      "dataItemAmount",
      $arrData['dataItemAmount'],
      ["style" => "width:$strDateWidth"],
      "numeric",
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
  $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
  $formInput = $f->render();
  getData($db);
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
//============================================= END MAIN PROGRAM ==========================================================================================
?>