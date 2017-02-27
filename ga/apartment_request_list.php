<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/apartment_request.php');
//================ END INCLUDE==========================================
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
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if ($arrData['dataIdRoom'] != "") {
    $strKriteria .= "AND a.id_room = '" . $arrData['dataIdRoom'] . "'";
  }
  if (validStandardDate($arrData['dataRequestDateFrom']) && validStandardDate($arrData['dataRequestDateThru'])) {
    $strKriteria .= "AND (a.request_date::date BETWEEN '" . $arrData['dataRequestDateFrom'] . "' AND '" . $arrData['dataRequestDateThru'] . "')  ";
  }
  if (validStandardDate($arrData['dataDateFromFrom']) && validStandardDate($arrData['dataDateFromThru'])) {
    $strKriteria .= "AND (a.date_from::date BETWEEN '" . $arrData['dataDateFromFrom'] . "' AND '" . $arrData['dataDateFromThru'] . "')  ";
  }
  if (validStandardDate($arrData['dataDateToFrom']) && validStandardDate($arrData['dataDateToThru'])) {
    $strKriteria .= "AND (a.date_to::date BETWEEN '" . $arrData['dataDateToFrom'] . "' AND '" . $arrData['dataDateToThru'] . "')  ";
  }
  if ($arrData['dataEmployeeID'] != "") {
    $strKriteria .= "AND e.employee_id = '" . $arrData['dataEmployeeID'] . "'";
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
        true
    );
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Req.No"), "apartment_req_no", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("room"), "room_name", ['width' => '100'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Employee"), "employee_name", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Request date"), "request_date", ['width' => '150'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Date From"), "date_from", ['width' => '150'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("Date To"), "date_to", ['width' => '150'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("remark"), "remark", ['width' => '100'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("status"), "status", "", "", true, true, "", "printRequestStatus()")
    );
    // Jika punya hal akses edit
    if (!isset($_POST['btnExportXLS']) && $bolCanEdit) {
      $myDataGrid->addColumn(
          new DataGrid_Column(
              "", "", ["width" => "60"], ['align' => 'center', 'nowrap' => ''], false, false, "",
              "printEditLink()", "", false /*show in excel*/
          )
      );
    }
    foreach ($arrData AS $key => $value) {
      $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
    }
    //tampilkan buttons sesuai dengan otoritas, common_function.php
    generateRoleButtons($bolCanEdit, $bolCanDelete, $bolCanCheck, $bolCanApprove, false, true, $myDataGrid);
    $myDataGrid->addButtonExportExcel(
        "Export Excel",
        $dataPrivilege['menu_name'] . ".xls",
        getWords($dataPrivilege['menu_name'])
    );
    $myDataGrid->getRequest();
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_apartment_request AS a LEFT JOIN hrd_employee AS e ON a.request_by = e.id";
    $strSQL = "SELECT r.room_name AS room_name,
	  				e.employee_name AS employee_name,
				    a.request_by AS id_employee,
					a.* 
                    FROM ga_apartment_request AS a LEFT JOIN ga_room AS r ON a.id_room=r.id
				    LEFT JOIN hrd_employee AS e ON a.request_by=e.id";
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
  return "<a href=\"apartment_request_edit.php?dataID=" . $record['id'] . "\">" . getWords('edit') . "</a>";
}

//******************************* END FUNGSI TOMBOL EDIT *******************************************
//*************************** FUNGSI GENERATE BUTTON ***********************************//
//********************************** BEGIN FUNGSI PERBARUHI STATUS ******************************************************
function callChangeStatus()
{
  global $_REQUEST;
  //print_r($_REQUEST);
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

//************************************ END FUNGSI PERBARUHI STATUS **************************************************/
//************************************ BEGIN FUNGSI VERIVY, CHECK, DENY, atau APROVE ********************************/
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
      $strSQLx = "SELECT status, employee_name,request_by,t1.request_date
                    FROM ga_apartment_request AS t1 
                    LEFT JOIN hrd_employee AS t2 ON t1.request_by = t2.id
                    WHERE t1.id = '$strValue' ";
      $resDb = $db->execute($strSQLx);
      if ($rowDb = $db->fetchrow($resDb)) {
        //the status should be increasing
        if ($rowDb['status'] < $intStatus && $rowDb['status'] != REQUEST_STATUS_DENIED) {
          $strSQL .= "UPDATE ga_apartment_request SET $strUpdate status = '$intStatus'  ";
          $strSQL .= "WHERE id = '$strValue'; ";
          writeLog(
              ACTIVITY_EDIT,
              MODULE_PAYROLL,
              $rowDb['employee_name'] . " - " . $rowDb['request_by'] . " - " . $rowDb['request_date'],
              $intStatus
          );
        }
      }
    }
    $resExec = $db->execute($strSQL);
  }
}

//************************************ BEGIN FUNGSI VERIVY, CHECK, DENY, atau APROVE ********************************/
/*********************************BEGIN  fungsi untuk menghapus data ***************************/
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $dataItem = new cGaApartmentRequest();
  $dataItem->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataItem->strMessage;
}

/*********************************END fungsi untuk menghapus data ****************************/
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
  $f->addInputAutoComplete(
      getWords("request by"),
      "dataEmployeeID",
      getDataEmployee(getInitialValue("Employee", null, $strDataEmployee)),
      "style=width:$strDefaultWidthPx " . $strReadonly,
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployeeID", "");
  $f->addSelect(
      getWords("Locatoin Room"),
      "dataIdRoom",
      getDataListRoom(
          $arrData['dataIdRoom'],
          true,
          [
              "value" => "",
              "text" => "",
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
      getWords("Request date From"),
      "dataRequestDateFrom",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      getWords("Request date to"),
      "dataRequestDateThru",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      "(Date From) from",
      "dataDateFromFrom",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      "(Date From) thru",
      "dataDateFromThru",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput("(Date To) From", "dataDateToFrom", "", ["style" => "width:$strDateWidth"], "date", false, true, true);
  $f->addInput("(Date To) Thru", "dataDateToThru", "", ["style" => "width:$strDateWidth"], "date", false, true, true);
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