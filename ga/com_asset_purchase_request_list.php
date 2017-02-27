<?php
define("SQL_TRUE", 't');
define("SQL_FALSE", 'f');
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/ga_purchase_request.php');
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
$strWordsEntryPR = getWords("entry Purchase Request");
$strWordsPRList = getWords("Purchase Request list");
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
  if ($arrData['dataPurchaseNo'] != "") {
    $strKriteria .= "AND pr.id = '" . $arrData['dataPurchaseNo'] . "'";
  }
  if ($arrData['dataEmployee'] != "") {
    $strKriteria .= "AND e.employee_id = '" . $arrData['dataEmployee'] . "'";
  }
  if ($arrData['dataIdCategory'] != "") {
    $strKriteria .= "AND pr.id_asset_category = '" . $arrData['dataIdCategory'] . "'";
  }
  if ($arrData['dataRequestDate'] != "") {
    $strKriteria .= "AND pr.request_date = '" . $arrData['dataRequestDate'] . "'";
  }
  if ($arrData['dataRemark'] != "") {
    $strKriteria .= "AND pr.remark = '" . $arrData['dataRemark'] . "'";
  }
  if ($arrData['dataAttachmentFile'] != "") {
    $strKriteria .= "AND pr.attachment_file = '" . $arrData['dataAttachmentFile'] . "'";
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
        new DataGrid_Column(getWords("purchase request number"), "purchase_no", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("employee name"), "employee_name", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("request Date"), "request_date", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Item category"), "category_name", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("remark"), "remark", ['width' => '150'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("attachment file"),
            "attachment_file",
            ['width' => '60'],
            ['align' => 'center', 'nowrap' => ''],
            false,
            false,
            "",
            "printDownloadLink()"
        )
    );
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
    //----------------BEGIN JIKA Punya Hak Edit---------------------------------//
    if ($bolCanEdit) {
      $myDataGrid->addSpecialButton(
          "btnOrder",
          "btnOrder",
          "submit",
          "Order Now",
          "onClick=\"javascript:return confirm('Do you want Order?');\"",
          "requestOrder()"
      );
    }
    //---------------- END Jika Punya Hak Akses Tambah-------------------------//
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
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_purchase_request AS pr
	  				  LEFT JOIN hrd_employee AS e ON pr.id_employee=e.id";
    $strSQL = "SELECT i.category_name,
	  				  e.employee_name AS employee_name,
	  				  pr.*
	  				  FROM ga_purchase_request AS pr
	  				  LEFT JOIN ga_item_category AS i ON pr.id_asset_category=i.id
	  				  LEFT JOIN hrd_employee AS e ON pr.id_employee=e.id
					 ";
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
  return "<a href=\"com_asset_purchase_request_edit.php?dataID=" . $record['id'] . "\">" . getWords('edit') . "</a>";
}

//******************************* END FUNGSI TOMBOL EDIT *******************************************
/*********************** BEGIN Fungsi Download ****************************************************/
function printDownloadLink($params)
{
  extract($params);
  return "
      
      <a href=\"/innogene/ga/prdoc/" . $record['attachment_file'] . "\">" . getWords(
      $record['attachment_file']
  ) . "</a>";
  //<a href=\"javascript:openWindowDialog('devosa/ga/prdoc/view=1&dataIDIns=".$record['id']."')\">".$value."</a>";
}

/*********************** END Fungsi Download ****************************************************/
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
      $strSQLx = "SELECT status, t1.id_employee, t2.employee_name, t1.request_date AS req
                    FROM ga_purchase_request AS t1 
                    LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
                    WHERE t1.id = '$strValue' ";
      $resDb = $db->execute($strSQLx);
      if ($rowDb = $db->fetchrow($resDb)) {
        //the status should be increasing
        if ($rowDb['status'] < $intStatus && $rowDb['status'] != REQUEST_STATUS_DENIED) {
          $strSQL .= "UPDATE ga_purchase_request SET $strUpdate status = '$intStatus'  ";
          $strSQL .= "WHERE id = '$strValue'; ";
          writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, $rowDb['employee_id'] . " - " . $rowDb['created_by'], $intStatus);
        }
      }
    }
    $resExec = $db->execute($strSQL);
  }
} //changeStatus
//Fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $tblDelete = new cGaPurchaseRequest();
  $tblDelete->deleteMultiple($arrKeys);
  $myDataGrid->message = $tblDelete->strMessage;
}

//************************************************END deleteData **************************************
//Fungsi untuk Request Order
function requestOrder()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  //$tblDelete = new cGaPurchaseRequest();
  //$tblDelete->deleteMultiple($arrKeys);
  header("location:com_asset_request_order_edit.php?dataPurchaseNo=" . $strValue . "");
}

//************************************************END Request Order **************************************
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
      getWords("Purchase Request No#"),
      "dataPurchaseNo",
      getDataListPurchaseNo(""),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addInputAutoComplete(
      getWords("employee ID"),
      "dataEmployee",
      getDataEmployee(getInitialValue("Employee", null, $strDataEmployee)),
      "style=width:$strDefaultWidthPx " . $strReadonly,
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployee", "");
  $f->addSelect(
      getWords("item category"),
      "dataIdCategory",
      getDataListCategoryItem(""),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addInput(
      getWords("request date"),
      "dataRequestDate",
      null,
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  //$f->addCheckBox(getWords("aktif/ tidak aktif"),"dataAktifasi", false, "");
  $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
  $f->addButton("btnAdd", getWords("Clear"), ["onClick" => "location.href='" . basename($_SERVER['PHP_SELF'] . "';")]);
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