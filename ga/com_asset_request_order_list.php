<?php
define("SQL_TRUE", 't');
define("SQL_FALSE", 'f');
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/ga_request_order.php');
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
$strWordsEntryRO = getWords("entry Request Order");
$strWordsROList = getWords("Request Order list");
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
  if ($arrData['dataPurchaseRequest'] != "") {
    $strKriteria .= "AND ro.id_purchase_request = '" . $arrData['dataPurchaseRequest'] . "'";
  }
  if ($arrData['dataEmployee'] != "") {
    $strKriteria .= "AND ro.employee_id = '" . $arrData['dataEmployee'] . "'";
  }
  if ($arrData['dataIdCategory'] != "") {
    $strKriteria .= "AND ro.id_asset_category = '" . $arrData['dataIdCategory'] . "'";
  }
  if ($arrData['dataRequestDate'] != "") {
    $strKriteria .= "AND ro.request_date = '" . $arrData['dataRequestDate'] . "'";
  }
  if ($arrData['dataAktifasi'] != "") {
    $strKriteria .= "AND cp.active = '" . $arrData['dataAktifasi'] . "'";
  }
  if ($arrData['dataRemark'] != "") {
    $strKriteria .= "AND ro.remark = '" . $arrData['dataRemark'] . "'";
  }
  if ($db->connect()) {
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
        new DataGrid_Column(getWords("Request Order number"), "request_order_no", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("purchase request number"), "purchase_no", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("requester"), "employee_name", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Item category"), "category_name", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("request Date"), "request_date", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("remark"), "remark", ['width' => '150'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("status"), "status", "", "", true, true, "", "printRequestStatus()")
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
    //tampilkan buttons sesuai dengan otoritas, common_function.php
    generateRoleButtons($bolCanEdit, $bolCanDelete, $bolCanCheck, $bolCanApprove, true, true, $myDataGrid);
    $myDataGrid->addButtonExportExcel(
        "Export Excel",
        $dataPrivilege['menu_name'] . ".xls",
        getWords($dataPrivilege['menu_name'])
    );
    //-----------------BEGIN Jika Punya Hak Akses Hapus-----------------------------//
    $myDataGrid->getRequest();
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_request_order AS ro
	  LEFT JOIN ga_purchase_request AS pr ON ro.id_purchase_request = pr.id 
	  LEFT JOIN hrd_employee AS e ON pr.id_employee = e.id";
    $strSQL = "SELECT i.category_name,
	  				   e.employee_name AS employee_name,
	   				   e.id AS id_employee,
					   pr.purchase_no,
					   ro.* 
					   FROM ga_request_order as ro
					   LEFT JOIN ga_item_category AS i ON ro.id_asset_category=i.id  
	  				   LEFT JOIN ga_purchase_request AS pr ON ro.id_purchase_request =pr.id  
					   LEFT JOIN hrd_employee AS e ON pr.id_employee = e.id";
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
  return "<a href=\"com_asset_request_order_edit.php?dataID=" . $record['id'] . "\">" . getWords('edit') . "</a>";
}

//******************************* END FUNGSI TOMBOL EDIT *******************************************
//fungsi untuk generate link untuk masing2 PR
/*function printViewLinkPR($params)
{
  extract($params);
  return "<a href=\"data_purchase_request_list.php?view=1&dataID=".$record['purchase_no']."\">".$value."</a>";
}*/
function callChangeStatus()
{
  global $_REQUEST;
  //    print_r($_REQUEST);
  global $db;
  if (isset($_REQUEST['btnVerified'])) {
    $intStatus = REQUEST_STATUS_VERIFIED;
  } else if (isset($_REQUEST['btnChecked'])) {
    $intStatus = REQUEST_STATUS_CHECKED;
  } else if (isset($_REQUEST['btnApproved'])) {
    $intStatus = REQUEST_STATUS_APPROVED;
  } else if (isset($_REQUEST['btnDenied'])) {
    $intStatus = REQUEST_STATUS_DENIED;
  } else if (isset($_REQUEST['btnPaid'])) {
    $intStatus = REQUEST_STATUS_PAID;
  }
  changeStatus($db, $intStatus);
}

// fungsi untuk verify, check, deny, atau approve
function changeStatus($db, $intStatus)
{
  global $_REQUEST;
  global $_SESSION;
  if (!is_numeric($intStatus)) {
    return false;
  }
  $strUpdate = "";
  $strSQL = "";
  $strmodified_byID = $_SESSION['sessionUserID'];
  ///-- Buat perintah sql untuk status
  if ($intStatus == REQUEST_STATUS_VERIFIED) {
    $strUpdate = "verified_by = '" . $_SESSION['sessionUserID'] . "', verified_time = now(), ";
  } else if ($intStatus == REQUEST_STATUS_CHECKED) {
    $strUpdate = "checked_by = '" . $_SESSION['sessionUserID'] . "', checked_time = now(), ";
  } else if ($intStatus == REQUEST_STATUS_APPROVED) {
    $strUpdate = "approved_by = '" . $_SESSION['sessionUserID'] . "', approved_time = now(), ";
  } else if ($intStatus == REQUEST_STATUS_DENIED) {
    $strUpdate = "denied_by = '" . $_SESSION['sessionUserID'] . "', denied_time = now(), ";
  } else if ($intStatus == REQUEST_STATUS_PAID) {
    $strUpdate = "paid_by = '" . $_SESSION['sessionUserID'] . "', paid_time = now(), ";
  }
  //-- END perintah sql
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 15) == 'DataGrid1_chkID') {
      $strSQLx = "SELECT t1.status, pr.id_employee, t2.employee_name
                    FROM ga_request_order AS t1 
                    LEFT JOIN ga_purchase_request AS pr ON t1.id_purchase_request=pr.id
                    LEFT JOIN hrd_employee AS t2 ON pr.id_employee = t2.id
                    WHERE t1.id = '$strValue' ";
      $resDb = $db->execute($strSQLx);
      if ($rowDb = $db->fetchrow($resDb)) {
        //the status should be increasing
        if ($rowDb['status'] < $intStatus && $rowDb['status'] != REQUEST_STATUS_DENIED) {
          $strSQL .= "UPDATE ga_request_order SET $strUpdate status = '$intStatus'  ";
          $strSQL .= "WHERE id = '$strValue'; ";
          writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, $rowDb['employee_id'] . " - " . $rowDb['employee_name'], $intStatus);
        }
      }
    }
    $resExec = $db->execute($strSQL);
  }
} //changeStatus
//====================== END FUNGSI UNTUK EDIT DATA YANG DIKRIM DARI PARAMETER*******************************
function getDataPurchaseRequest()
{
  global $db;
  $result = [];
  if ($db->connect()) {
    $strSQL = "SELECT * FROM ga_purchase_request ORDER BY purchase_no";
    $res = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($res)) {
      $result[] = ["value" => $rowDb['id'], "text" => $rowDb['purchase_no'], "selected" => false];
    }
  }
  return $result;
}

//-------------------------END Get Data Item Category -------------------------------------------//
//fungsi untuk generate simbol radic
function printInt($params)
{
  extract($params);
  if ($value == SQL_TRUE) {
    return "&radic;";
  } else {
    return "";
  }
}

//END of FUNCTION
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $tblDelete = new cGaRequestOrder();
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
      getWords("purchase request"),
      "dataPurchaseRequest",
      getDataPurchaseRequest($arrData['dataPurchaseRequest']),
      "style='width:250px' ",
      "string",
      false,
      true,
      true
  );
  $f->addSelect(
      getWords("item category"),
      "dataIdCategory",
      getDataListCategoryItem($arrData['dataIdCategory']),
      "style='width:250px' ",
      "string",
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