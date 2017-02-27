<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_employee.php');
include_once('../classes/hrd/hrd_leave_allowance_base.php');
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
$strWordsCutOff = getWords("cut off");
$strWordsLeaveAllowanceList = getWords("leave allowance list");
//----MAIN PROGRAM -----------------------------------------------------
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  $f = new clsForm("formFilter", 3, "100%", "");
  $f->caption = strtoupper("trip type");
  $f->addInputAutoComplete(
      getWords("employee ID"),
      "dataEmployeeID",
      getDataEmployee($strDataEmployee),
      "style=width:$strDefaultWidthPx " . $strEmpReadonly,
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployeeID", "");
  $f->addLiteral("", "", "");
  $f->addSelect(
      getWords("join month"),
      "dataJoinMonth",
      getDataListMonth(intval(date('m')), true),
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
  getData($db);
}
function getData($db)
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
    $strKriteria .= "AND position_code = '" . $arrData['dataPosition'] . "' ";
  }
  if ($arrData['dataBranch'] != "") {
    $strKriteria .= "AND branch_code = '" . $arrData['dataBranch'] . "' ";
  }
  if ($arrData['dataJoinMonth'] != "") {
    $strKriteria .= "AND EXTRACT(MONTH FROM join_date) = '" . $arrData['dataJoinMonth'] . "' ";
  }
  if ($arrData['dataGrade'] != "") {
    $strKriteria .= "AND grade_code = '" . $arrData['dataGrade'] . "' ";
  }
  if ($arrData['dataEmployeeStatus'] != "") {
    $strKriteria .= "AND employee_status = '" . $arrData['dataEmployeeStatus'] . "' ";
  }
  if ($arrData['dataActive'] != "") {
    $strKriteria .= "AND active = '" . $arrData['dataActive'] . "' ";
  }
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND division_code = '" . $arrData['dataDivision'] . "' ";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND department_code = '" . $arrData['dataDepartment'] . "' ";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND section_code = '" . $arrData['dataSection'] . "' ";
  }
  if ($arrData['dataSubSection'] != "") {
    $strKriteria .= "AND sub_section_code = '" . $arrData['dataSubSection'] . "' ";
  }
  $strKriteria .= $strKriteriaCompany;
  $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, true, false);
  $myDataGrid->caption = getWords(strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name']))));
  $myDataGrid->pageSortBy = "employee_name";
  $myDataGrid->setCriteria($strKriteria);
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  $myDataGrid->setPageLimit("all");
  $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("employee id"), "employee_id", ['width' => '75'], ['nowrap' => ''])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("employee name"), "employee_name", ['width' => '150'], ['nowrap' => ''])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("division"), "division_code", [], [], true, true, "", "getDivisionName()", "", true)
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("department"),
          "department_code",
          [],
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
      new DataGrid_Column(getWords("section"), "section_code", [], [], true, true, "", "getSectionName()", "", true)
  );
  //untuk di html, pd html: header di-hide, content di-show
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("cut off counter"),
          "cut_off_counter",
          ['width' => '75', 'style' => 'display:none'],
          ['align' => 'center'],
          false,
          false,
          "",
          ($bolCanEdit) ? "printCounter()" : "",
          "",
          false
      )
  );
  //untuk di excel, pd html: header di-show, content di hide
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("cut off counter"),
          "cut_off_counter",
          ['width' => '75'],
          ['align' => 'center', 'style' => 'display:none'],
          true,
          false,
          "",
          "",
          "",
          true
      )
  );
  //untuk di html, pd html: header di-hide, content di-show
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("cut off date"),
          "cut_off_date",
          ['style' => 'display:none'],
          ['align' => 'center', 'nowrap' => ''],
          false,
          false,
          "",
          ($bolCanEdit) ? "printDate()" : "",
          "",
          false
      )
  );
  //untuk di excel, pd html: header di-show, content di hide
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("cut off date"),
          "cut_off_date",
          [],
          ['align' => 'center', 'style' => 'display:none'],
          true,
          false,
          "",
          "",
          "",
          true
      )
  );
  $myDataGrid->addColumn(new DataGrid_Column(getWords("join date"), "join_date", ['width' => '75'], ['nowrap' => '']));
  foreach ($arrData AS $key => $value) {
    $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
  }
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
      "Export Excel",
      $dataPrivilege['menu_name'] . ".xls",
      getWords($dataPrivilege['menu_name'])
  );
  $myDataGrid->getRequest();
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  $strSQLCOUNT = "SELECT COUNT(*) AS total  FROM hrd_employee AS t1  ";
  $strSQLCOUNT .= "LEFT JOIN hrd_leave_allowance_base AS t2 ON t1.id = t2.id_employee";
  $strSQL = "SELECT t1.id as id_employee, employee_id, employee_name, join_date, division_code, department_code, section_code, cut_off_counter, cut_off_date FROM hrd_employee AS t1  ";
  $strSQL .= "LEFT JOIN hrd_leave_allowance_base AS t2 ON t1.id = t2.id_employee";
  $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
  $dataset = $myDataGrid->getData($db, $strSQL);
  //bind Datagrid with array dataset
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
}

function printCounter($params)
{
  extract($params);
  return generateInput("detailCutOffCounter_" . $record['id_employee'], $value);
}

function printDate($params)
{
  global $strInitAction;
  extract($params);
  $strInitAction .= "Calendar.setup({ inputField:\"detailCutOffDate_" . $record['id_employee'] . "\", button:\"btnCutOffDate_" . $record['id_employee'] . "\" });\n";
  return generateInput("detailCutOffDate_" . $record['id_employee'], $value) . " " . generateButton(
      "btnCutOffDate_" . $record['id_employee'],
      ".."
  );
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
  $tblHrdEmployee = new cHrdEmployee();
  $arrEmployee = $tblHrdEmployee->findAll("", "id", "", null, 1, "id");
  $tblHrdLeaveAllowanceBase = new cHrdLeaveAllowanceBase();
  foreach ($arrEmployee AS $strIDEmployee => $arrDetailEmployee) {
    if (isset($arrData['detailCutOffCounter_' . $strIDEmployee])) {
      $data['id_employee'] = $strIDEmployee;
      $data['cut_off_counter'] = $arrData['detailCutOffCounter_' . $strIDEmployee];
      $data['cut_off_date'] = $arrData['detailCutOffDate_' . $strIDEmployee];
      $tblHrdLeaveAllowanceBase->delete(["id_employee" => $strIDEmployee]);
      $tblHrdLeaveAllowanceBase->insert($data);
    }
  }
  if ($bolSuccess) {
    $myDataGrid->message = $tblHrdLeaveAllowanceBase->strMessage;
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