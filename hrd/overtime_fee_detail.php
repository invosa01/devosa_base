<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_employee.php');
include_once('../classes/hrd/hrd_overtime_fee_master.php');
include_once('../classes/hrd/hrd_overtime_fee_detail.php');
$dataPrivilege = getDataPrivileges(
    "overtime_fee_summary.php",
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
$strWordsBackToOvertimeFeeCalculation = getWords("back to overtime fee calculation");
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, false);
$DataGrid = "";
//DAFTAR FUNGSI--------------------------------------------------------------------------------------------------------------
function getData($db, $strDataID)
{
  global $dataPrivilege;
  global $f;
  global $myDataGrid;
  global $DataGrid;
  global $strKriteriaCompany;
  //global $arrUserInfo;
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
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
    $strKriteria .= "AND grade_code = '" . $arrData['dataGrade'] . "'";
  }
  if ($arrData['dataEmployeeStatus'] != "") {
    $strKriteria .= "AND employee_status = '" . $arrData['dataEmployeeStatus'] . "'";
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
  $strKriteria .= (str_replace("id_company", "t2.id_company", $strKriteriaCompany));
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
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("employee id"), "employee_id", ['width' => '150'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("employee name"), "employee_name", ['width' => '250'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("grade"), "grade_code", ['width' => '50'], [], true, true, "", "", "", true)
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("division"),
            "division_code",
            ['width' => '150'],
            [],
            true,
            true,
            "",
            "getDivisionName()",
            "",
            true
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("department"),
            "department_code",
            ['width' => '150'],
            [],
            true,
            true,
            "",
            "getDepartmentName()",
            "",
            true
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("section"),
            "section_code",
            ['width' => '75'],
            ['align' => 'center'],
            true,
            true,
            "",
            "getSectionName()",
            "",
            true
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("branch"), "branch", ['width' => '75'], [], true, true, "", "", "", true)
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("bank"), "bank2_code", ['width' => '75'], [], true, true, "", "", "", true)
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("bank account"),
            "bank2_account",
            ['width' => '100'],
            [],
            true,
            true,
            "",
            "",
            "string",
            true
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("bank account name"),
            "bank2_account_name",
            ['width' => '175'],
            [],
            true,
            true,
            "",
            "",
            "",
            true
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("transport allowance"),
            "transport_allowance",
            ['width' => '75'],
            ['align' => 'center'],
            false,
            false,
            "",
            "formatNumeric()",
            "numeric",
            true
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("meal allowance"),
            "meal_allowance",
            ['width' => '75'],
            ['align' => 'center'],
            false,
            false,
            "",
            "formatNumeric()",
            "numeric",
            true
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("breakfast allowance"),
            "breakfast_allowance",
            ['width' => '75'],
            ['align' => 'center'],
            false,
            false,
            "",
            "formatNumeric()",
            "numeric",
            true
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("total"),
            "total_allowance",
            ['width' => '75'],
            ['align' => 'center'],
            false,
            false,
            "",
            "formatNumeric()",
            "numeric",
            true
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("status"), "status", "", "", false, false, "", "printRequestStatus()")
    );
    if ($dataPrivilege['edit'] == 't') {
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
    $myDataGrid->strAdditionalHtml .=
        generateHidden("dataIDMaster", $strDataID, "");
    //tampilkan buttons sesuai dengan otoritas, common_function.php
    generateRoleButtons(
        $dataPrivilege['edit'],
        $dataPrivilege['delete'],
        $dataPrivilege['check'],
        $dataPrivilege['approve'],
        true,
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
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_overtime_fee_detail AS t1
                       LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id 
                       WHERE id_master = '$strDataID'";
    $strSQL = "SELECT t1.*, (t1.transport_allowance + t1.breakfast_allowance + t1.meal_allowance) as total_allowance, t1.created AS
                       calculated, employee_id, employee_name, grade_code, division_code, department_code, bank2_account, 
                       bank2_code, bank2_account_name, t2.branch_code || ' - ' || branch_name as branch
                       FROM hrd_overtime_fee_detail AS t1
                       LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id
                       LEFT JOIN hrd_branch  AS t3 ON t2.branch_code = t3.branch_code
                       LEFT JOIN hrd_overtime_fee_master AS t4 ON t1.id_master = t4.id
                       WHERE id_master = '$strDataID'
                       ";
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
    $myDataGrid->pageSortBy = "employee_name";
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
      $strSQLx = "SELECT status, employee_name, date_from, date_thru
                    FROM hrd_overtime_fee_detail AS t1 
                    LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
                    LEFT JOIN hrd_overtime_fee_master AS t3 ON t1.id_master = t3.id
                    WHERE t1.id = '$strValue' ";
      $resDb = $db->execute($strSQLx);
      if ($rowDb = $db->fetchrow($resDb)) {
        //the status should be increasing
        if (isProcessable($rowDb['status'], $intStatus)) {
          $strSQL .= "UPDATE hrd_overtime_fee_detail SET $strUpdate status = '$intStatus'  ";
          $strSQL .= "WHERE id = '$strValue'; ";
          writeLog(
              ACTIVITY_EDIT,
              MODULE_PAYROLL,
              $rowDb['employee_name'] . " - " . $rowDb['date_from'] . " - " . $rowDb['date_thru'],
              $intStatus
          );
        }
      }
    }
    $resExec = $db->execute($strSQL);
  }
} //changeStatus
// fungsi untuk menghapus data ==> tidak bisa hapus data dari page detail, harus hapus masternya langsung
function deleteData()
{
} //deleteData
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  (isset($_REQUEST['dataIDMaster'])) ? $strDataID = $_REQUEST['dataIDMaster'] : $strDataID = "";
  if ($strDataID == "") {
    header("location:overtime_fee_summary.php");
    exit();
  }
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
  $f->addHidden("dataIDMaster", $strDataID);
  $f->addInputAutoComplete(
      getWords("employee ID"),
      "dataEmployeeID",
      getDataEmployee($strDataEmployee),
      "style=width:$strDefaultWidthPx " . $strReadonly,
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployeeID", "");
  $f->addLiteral("", "", "");
  $f->addSelect(
      getWords("request status"),
      "dataRequestStatus",
      getDataListRequestStatus("", true, ["value" => "", "text" => "", "selected" => true]),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addLiteral("", "", "");
  $f->addSelect(
      getWords("branch"),
      "dataBranch",
      getDataListBranch("", true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("level"),
      "dataPosition",
      getDataListPosition("", true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("grade"),
      "dataGrade",
      getDataListSalaryGrade("", true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("status"),
      "dataEmployeeStatus",
      getDataListEmployeeStatus("", true, ["value" => "", "text" => "", "selected" => true]),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("active"),
      "dataActive",
      getDataListEmployeeActive("", true, ["value" => "", "text" => "", "selected" => true]),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
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
      getDataListDivision($strDataDivision, true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['division'] == "")
  );
  $f->addSelect(
      getWords("department "),
      "dataDepartment",
      getDataListDepartment($strDataDepartment, true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['department'] == "")
  );
  $f->addSelect(
      getWords("section"),
      "dataSection",
      getDataListSection($strDataSection, true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['section'] == "")
  );
  $f->addSelect(
      getWords("sub section"),
      "dataSubSection",
      getDataListSubSection($strDataSubSection, true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['sub_section'] == "")
  );
  $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
  $formFilter = $f->render();
  getData($db, $strDataID);
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