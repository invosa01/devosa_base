<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/employee_function.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_employee.php');
include_once('../classes/hrd/hrd_medical_additional_quota.php');
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
$db = new CdbClass;
$strWordsTreatmentTypeSetting = getWords("treatment type setting");
$strWordsQuotaSetting = getWords("quota setting");
$strWordsExtendedQuota = getWords("extended quota");
//----MAIN PROGRAM -----------------------------------------------------
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  $strYear = (getPostValue('dataYear') == "") ? date("Y") : getPostValue('dataYear');
  $strDataCompany = (getPostValue('dataCompany') == "") ? 1 : getPostValue('dataCompany');
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
  $f = new clsForm("formFilter", 3, "100%", "");
  $f->caption = strtoupper("filter data");
  $f->addSelect(
      getWords("year"),
      "dataYear",
      getDataListYear($strYear),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
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
      getWords("department"),
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
  getData($db, $strDataCompany, $strYear);
}
function getData($db, $strDataCompany, $strYear)
{
  global $dataPrivilege, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge;
  global $f;
  global $myDataGrid;
  global $DataGrid;
  global $strKriteriaCompany;
  $formFilter = $f->render();
  $strKriteria = "";
  // GENERATE CRITERIA
  $arrData = $f->getObjectValues();
  if ($arrData['dataEmployeeID'] != "") {
    $strKriteria .= "AND employee_id = '" . $arrData['dataEmployeeID'] . "' ";
  }
  if ($arrData['dataPosition'] != "") {
    $strKriteria .= "AND t1.position_code = '" . $arrData['dataPosition'] . "' ";
  }
  if ($arrData['dataBranch'] != "") {
    $strKriteria .= "AND t1.branch_code = '" . $arrData['dataBranch'] . "' ";
  }
  if ($arrData['dataGrade'] != "") {
    $strKriteria .= "AND t1.grade_code = '" . $arrData['dataGrade'] . "' ";
  }
  if ($arrData['dataEmployeeStatus'] != "") {
    $strKriteria .= "AND t1.employee_status = '" . $arrData['dataEmployeeStatus'] . "' ";
  }
  if ($arrData['dataActive'] != "") {
    $strKriteria .= "AND t1.active = '" . $arrData['dataActive'] . "' ";
  }
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND t1.division_code = '" . $arrData['dataDivision'] . "' ";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND t1.department_code = '" . $arrData['dataDepartment'] . "' ";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND t1.section_code = '" . $arrData['dataSection'] . "' ";
  }
  if ($arrData['dataSubSection'] != "") {
    $strKriteria .= "AND t1.sub_section_code = '" . $arrData['dataSubSection'] . "' ";
  }
  $strKriteria .= $strKriteriaCompany;
  $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, false, false);
  $myDataGrid->caption = getWords(strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name']))));
  $myDataGrid->pageSortBy = "employee_name";
  //$myDataGrid->setCriteria($strKriteria);
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  $myDataGrid->setPageLimit("all");
  $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("employee id"), "employee_id", ['width' => '50'], ['nowrap' => ''])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("employee name"), "employee_name", ['width' => '150'], ['nowrap' => ''])
  );
  $myDataGrid->addColumn(new DataGrid_Column(getWords("grade"), "grade_code", ['width' => '25'], ['nowrap' => '']));
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("family status"), "medical_quota_status", ['width' => '25'], ['nowrap' => ''])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("department"),
          "department_code",
          ['width' => '75'],
          ['align' => 'center'],
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
          getWords("main quota"),
          "amount",
          ['width' => '50'],
          ['align' => 'center'],
          true,
          true,
          "",
          (isset($_POST['btnExportXLS']) ? "" : "formatNumber()"),
          "",
          true
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("additional (extension)"),
          "amount1",
          ['width' => '50'],
          ['align' => 'center'],
          false,
          false,
          "",
          ($bolCanEdit) ? "printAddQuota1()" : "",
          "",
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("additional (extension)"),
          "amount1",
          ['width' => '50', 'style' => 'display:none'],
          ['align' => 'center', 'style' => 'display:none'],
          false,
          false,
          "",
          "",
          "",
          true
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("additional (family status changes)"),
          "amount2",
          ['width' => '50'],
          ['align' => 'center'],
          false,
          false,
          "",
          ($bolCanEdit) ? "printAddQuota2()" : "",
          "",
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("additional (family status changes)"),
          "amount2",
          ['width' => '50', 'style' => 'display:none'],
          ['align' => 'center', 'style' => 'display:none'],
          false,
          false,
          "",
          "",
          "",
          true
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("note"),
          "note",
          [],
          ['align' => 'center'],
          false,
          false,
          "",
          ($bolCanEdit) ? "printNote()" : "",
          "",
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("note"),
          "note",
          ['style' => 'display:none'],
          ['align' => 'center', 'style' => 'display:none'],
          false,
          false,
          "",
          "",
          "",
          true
      )
  );
  foreach ($arrData AS $key => $value) {
    $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
  }
  $myDataGrid->strAdditionalHtml .= generateHidden("dataYear", $strYear, "");
  if ($bolCanEdit) {
    $myDataGrid->addButton(
        "btnSave",
        "btnSave",
        "submit",
        getWords("save"),
        "onClick=\"javascript:return myClient.confirmSave();\"",
        "saveData()"
    );
  }
  $myDataGrid->addButtonExportExcel(
      getWords("export excel"),
      $dataPrivilege['menu_name'] . ".xls",
      getWords($dataPrivilege['menu_name'])
  );
  $myDataGrid->getRequest();
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  $strSQL = "SELECT t1.id, t1.employee_id, t1.id_company, t1.employee_name, t1.department_code, t1.gender, t1.inspouse, t1.join_date,
                t1.resign_date, t1.due_date, t1.active, t1.division_code, t1.grade_code, t1.employee_status, 
                t1.medical_quota_status, t4.amount, t4.amount1, t4.amount2, t4.note
                FROM hrd_employee AS t1 
                LEFT JOIN (SELECT * FROM hrd_medical_quota_primary WHERE quota_year = $strYear) AS t4 ON t1.id = t4.id_employee
                WHERE id_company = $strDataCompany  $strKriteria 
                ";
  $strSQLCOUNT = "SELECT COUNT(*) AS total  FROM ($strSQL) as t0 WHERE 1=1 ";
  $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT, false);
  $dataset = $myDataGrid->getData($db, $strSQL);
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
}

function printAddQuota1($params)
{
  extract($params);
  return generateInput("dataAddQuota_" . $record['id'], $value, "size = 10");
}

function printAddQuota2($params)
{
  extract($params);
  return generateInput("dataAddQuota2_" . $record['id'], $value, "size = 10");
}

function printNote($params)
{
  extract($params);
  return generateInput("dataNote_" . $record['id'], $value, "size = 150");
}

// fungsi untuk menyimpan data
function saveData()
{
  global $myDataGrid;
  global $error;
  $strError = "";
  $bolSuccess = true;
  $strModifiedByID = $_SESSION['sessionUserID'];
  $arrData = $myDataGrid->checkboxes;
  if (!is_numeric($strYear = $arrData['dataYear'])) {
    $myDataGrid->errorMessage = $strError;
    return false;
  }
  $tblHrdEmployee = new cHrdEmployee();
  $arrEmployee = $tblHrdEmployee->findAll("", "id", "", null, 1, "id");
  $tblMedicalAdditionalQuota = new cHrdMedicalAdditionalQuota();
  foreach ($arrEmployee AS $strIDEmployee => $arrDetailEmployee) {
    if (isset($arrData['dataAddQuota_' . $strIDEmployee])) {
      $data['id_employee'] = $strIDEmployee;
      $data['quota_year'] = $strYear;
      $data['amount1'] = $arrData['dataAddQuota_' . $strIDEmployee];
      $data['amount2'] = $arrData['dataAddQuota2_' . $strIDEmployee];
      $data['note'] = $arrData['dataNote_' . $strIDEmployee];
      $tblMedicalAdditionalQuota->delete(["id_employee" => $strIDEmployee, "quota_year" => $strYear]);
      $tblMedicalAdditionalQuota->insert($data);
    }
  }
  if ($bolSuccess) {
    $myDataGrid->message = $tblMedicalAdditionalQuota->strMessage;
  } else {
    $myDataGrid->errorMessage = $strError;
  }
} // saveData
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