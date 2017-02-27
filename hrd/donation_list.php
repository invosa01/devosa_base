<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_salary_grade.php');
include_once('../classes/hrd/hrd_donation_type.php');
include_once('../classes/hrd/hrd_donation_platform.php');
include_once('../classes/hrd/hrd_donation.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
//INISIALISASI---------------------------------------------------------------------------------------------------------------
$strWordsDataEntry = getWords("data entry");
$strWordsDonationList = getWords("donation list");
$strWordsDonationReport = getWords("donation report");
$strWordsDispositionForm = getWords("disposition form");
$DataGrid = "";
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
//DAFTAR FUNGSI--------------------------------------------------------------------------------------------------------------
function getData($db)
{
  global $dataPrivilege, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge;
  global $f;
  global $DataGrid;
  global $myDataGrid;
  global $strKriteriaCompany;
  //global $arrUserInfo;
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if ($arrData['dataDonationType'] != "") {
    $strKriteria .= "AND donation_code = '" . $arrData['dataDonationType'] . "'";
  }
  if (validStandardDate($arrData['dataDateFrom']) && validStandardDate($arrData['dataDateThru'])) {
    $strKriteria .= "AND (t1.created::date BETWEEN '" . $arrData['dataDateFrom'] . "' AND '" . $arrData['dataDateThru'] . "')  ";
  }
  if ($arrData['dataEmployeeID'] != "") {
    $strKriteria .= "AND employee_id = '" . $arrData['dataEmployeeID'] . "'";
  }
  if ($arrData['dataPosition'] != "") {
    $strKriteria .= "AND position_code = '" . $arrData['dataPosition'] . "'";
  }
  if ($arrData['dataBranch'] != "") {
    $strKriteria .= "AND t2.branch_code = '" . $arrData['dataBranch'] . "'";
  }
  if ($arrData['dataGrade'] != "") {
    $strKriteria .= "AND t1.grade_code = '" . $arrData['dataGrade'] . "'";
  }
  if ($arrData['dataEmployeeStatus'] != "") {
    $strKriteria .= "AND t2.employee_status = '" . $arrData['dataEmployeeStatus'] . "'";
  }
  if ($arrData['dataActive'] != "") {
    $strKriteria .= "AND active = '" . $arrData['dataActive'] . "'";
  }
  if ($arrData['dataRequestStatus'] != "") {
    $strKriteria .= "AND t1.status = '" . $arrData['dataRequestStatus'] . "'";
  }
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND t2.division_code = '" . $arrData['dataDivision'] . "'";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND t2.department_code = '" . $arrData['dataDepartment'] . "'";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND section_code = '" . $arrData['dataSection'] . "'";
  }
  if ($arrData['dataSubSection'] != "") {
    $strKriteria .= "AND sub_section_code = '" . $arrData['dataSubSection'] . "'";
  }
  $strKriteria .= $strKriteriaCompany;
  if ($db->connect()) {
    $myDataGrid->caption = getWords(
        strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
    );
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->setCriteria($strKriteria);
    if (!isset($_REQUEST['btnExportXLS'])) {
      $myDataGrid->addColumnCheckbox(
          new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => '']),
          true /*bolDisableSelfStatusChange*/
      );
    }
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("form code"), "form_code", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("created"), "created", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date from"), "event_date_from", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date thru"), "event_date_thru", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("employee id"), "employee_id", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("employee name"), "employee_name", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("donation type"), "name", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("name"), "relation_name", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("relation"), "relation_type", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", "", ""));
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("amount"), "amount", "", array('align' => 'right'), false, false));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("amount"),
            "amount",
            ['nowrap' => ''],
            ["align" => "right"],
            false,
            false,
            "",
            "formatNumber()",
            "numeric",
            true,
            15
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("status"), "status", "", "", false, false, "", "printRequestStatus()")
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
              "printGlobalEditLink()",
              "",
              false /*show in excel*/
          )
      );
    }
    foreach ($arrData AS $key => $value) {
      $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
    }
    //tampilkan buttons sesuai dengan otoritas, common_function.php
    generateRoleButtons(
        $bolCanEdit,
        $bolCanDelete,
        $bolCanCheck,
        $bolCanApprove,
        $bolCanAcknowledge,
        true,
        $myDataGrid
    );
    $myDataGrid->addButtonExportExcel(
        "Export Excel",
        $dataPrivilege['menu_name'] . ".xls",
        getWords($dataPrivilege['menu_name'])
    );
    $myDataGrid->getRequest();
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_donation AS t1 LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id";
    $strSQL = "SELECT t1.*, employee_id, employee_name, t2.department_code , t2.section_code , t2.branch_code || ' - ' || branch_name as                        branch, t4.name
                       FROM hrd_donation AS t1
                       LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id
                       LEFT JOIN hrd_branch  AS t3 ON t2.branch_code = t3.branch_code
                       LEFT JOIN hrd_donation_type  AS t4 ON t1.donation_code= t4.code
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
  $strUpdate = getStatusUpdateString($intStatus);
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 15) == 'DataGrid1_chkID') {
      $strSQLx = "SELECT status, employee_name, t1.created, donation_code
                    FROM hrd_donation AS t1 
                    LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
                    WHERE t1.id = '$strValue' ";
      $resDb = $db->execute($strSQLx);
      if ($rowDb = $db->fetchrow($resDb)) {
        //the status should be increasing
        if (isProcessable($rowDb['status'], $intStatus)) {
          $strSQL .= "UPDATE hrd_donation SET $strUpdate status = '$intStatus'  ";
          $strSQL .= "WHERE id = '$strValue'; ";
          writeLog(
              ACTIVITY_EDIT,
              MODULE_PAYROLL,
              $rowDb['employee_name'] . " - " . $rowDb['created'] . " - " . $rowDb['donation_code'],
              $intStatus
          );
        }
      }
    }
    $resExec = $db->execute($strSQL);
  }
} //changeStatus
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $tblDonation = new cHrdDonation();
  $tblDonation->deleteMultiple($arrKeys);
  $myDataGrid->message = $tblDonation->strMessage;
} //deleteData
//----MAIN PROGRAM -----------------------------------------------------
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
  //trip type harus dipilih dulu supaya jenis2 trip allowance dapat ditentukan
  $f = new clsForm("formFilter", 3, "100%", "");
  $f->caption = strtoupper($strWordsFILTERDATA);
  $f->addInput(
      getWords("date from"),
      "dataDateFrom",
      getInitialValue("DateFrom", date("Y-m-") . "01"),
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      getWords("date thru"),
      "dataDateThru",
      getInitialValue("DateThru", date("Y-m-d")),
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addSelect(
      getWords("donation type"),
      "dataDonationType",
      getDataListDonationType("", true, ["value" => "", "text" => "", "selected" => true]),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
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
      getWords("request status"),
      "dataRequestStatus",
      getDataListRequestStatus(
          getInitialValue("RequestStatus"),
          true,
          ["value" => "", "text" => "", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("branch"),
      "dataBranch",
      getDataListBranch(getInitialValue("Branch"), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("level"),
      "dataPosition",
      getDataListPosition(getInitialValue("Position"), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("grade"),
      "dataGrade",
      getDataListSalaryGrade(getInitialValue("Grade"), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("status"),
      "dataEmployeeStatus",
      getDataListEmployeeStatus(
          getInitialValue("EmployeeStatus"),
          true,
          ["value" => "", "text" => "", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("active"),
      "dataActive",
      getDataListEmployeeActive(
          getInitialValue("Active"),
          true,
          ["value" => "", "text" => "", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addLiteral("", "", "");
  $f->addSelect(
      getWords("company"),
      "dataCompany",
      getDataListCompany($strDataCompany, $bolCompanyEmptyOption, $arrCompanyEmptyData, $strKriteria2),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("division"),
      "dataDivision",
      getDataListDivision(getInitialValue("Division", "", $strDataDivision), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['division'] == "")
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
  $f->addSelect(
      getWords("section"),
      "dataSection",
      getDataListSection(getInitialValue("Section", "", $strDataSection), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['section'] == "")
  );
  $f->addSelect(
      getWords("sub section"),
      "dataSubSection",
      getDataListSubSection(getInitialValue("SubSection", "", $strDataSubSection), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['sub_section'] == "")
  );
  $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
  $formFilter = $f->render();
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
//--------------------------------------------------------------------------------
?>