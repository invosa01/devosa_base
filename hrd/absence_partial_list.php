<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../global/form_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_absence_partial.php');
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
$strWordsEntryAbsence = getWords("entry absence");
$strWordsAbsenceList = getWords("absence list");
$strWordsEntryPartialAbsence = getWords("entry partial absence");
$strWordsPartialAbsenceList = getWords("partial absence list");
$strWordsAnnualLeave = getWords("annual Leave");
//strWordsAbsenceSlip	   = getWords ("absence slip");
$strSlipContent = "";
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", true, true, true);
$DataGrid = "";
//DAFTAR FUNGSI--------------------------------------------------------------------------------------------------------------
function getData($db)
{
  global $ARRAY_PARTIAL_ABSENCE_TYPE;
  global $dataPrivilege, $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge;
  global $f;
  global $myDataGrid;
  global $DataGrid;
  global $strKriteriaCompany;
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  $arrDate = explode("/", $arrData['dataDateFrom']);
  $arrData['dataDateFrom'] = $arrDate[2] . "-" . $arrDate[0] . "-" . $arrDate[1];
  $arrDate = explode("/", $arrData['dataDateThru']);
  $arrData['dataDateThru'] = $arrDate[2] . "-" . $arrDate[0] . "-" . $arrDate[1];
  // GENERATE CRITERIA
  if (validStandardDate($arrData['dataDateFrom']) && validStandardDate($arrData['dataDateThru'])) {
    $strKriteria .= "AND t1.partial_absence_date BETWEEN '" . $arrData['dataDateFrom'] . "' AND '" . $arrData['dataDateThru'] . "' ";
  }
  if ($arrData['dataType'] != "") {
    $strKriteria .= "AND partial_absence_type = '" . $arrData['dataType'] . "' ";
  }
  if ($arrData['dataEmployeeID'] != "") {
    $strKriteria .= "AND employee_id = '" . $arrData['dataEmployeeID'] . "' ";
  }
  if ($arrData['dataPosition'] != "") {
    $strKriteria .= "AND position_code = '" . $arrData['dataPosition'] . "' ";
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
  if ($arrData['dataRequestStatus'] != "") {
    $strKriteria .= "AND t1.status = '" . $arrData['dataRequestStatus'] . "' ";
  }
  if ($arrData['dataBranch'] != "") {
    $strKriteria .= "AND t2.branch_code = '" . $arrData['dataBranch'] . "' ";
  }
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND t2.division_code = '" . $arrData['dataDivision'] . "' ";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND t2.department_code = '" . $arrData['dataDepartment'] . "' ";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND section_code = '" . $arrData['dataSection'] . "' ";
  }
  if ($arrData['dataSubSection'] != "") {
    $strKriteria .= "AND sub_section_code = '" . $arrData['dataSubSection'] . "' ";
  }
  $strKriteria .= $strKriteriaCompany;
  if ($db->connect()) {
    $myDataGrid->caption = getWords($dataPrivilege['menu_name']);
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->pageSortBy = "created desc";
    $myDataGrid->setCriteria($strKriteria);
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => '']),
        true /*bolDisableSelfStatusChange*/
    );
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("id"), "employee_id", null, ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("employee name"), "employee_name", ["width" => 150], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("department"), "department_name", null, ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date"), "partial_absence_date_", null, ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("type"), "partial_absence_type", ["width" => 70], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("start"), "start_time", null, ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("finish"), "finish_time", null, ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("duration"), "duration", null, ['nowrap' => ''], false, false, "", "formatTime()")
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("approved"),
            "approved_duration",
            null,
            ['nowrap' => ''],
            false,
            false,
            "",
            "formatTime()"
        )
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", null, ""));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("status"), "status", null, "", false, false, "", "printRequestStatus()")
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("checked by"), "checked_by", null, "", false, false, "", "printUserName()")
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("checked time"), "checked_time", null, "", false, false, "", "cutApprovedTime()")
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("approved by"), "approved_by", null, "", false, false, "", "printUserName()")
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("approved time"), "approved_time", null, "", false, false, "", "cutApprovedTime()")
    );
    if ($dataPrivilege['edit'] == 't') {
      $myDataGrid->addColumn(
          new DataGrid_Column(
              "",
              "",
              null,
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
    //$myDataGrid->addSpecialButton("btnSlip", "btnSlip", "submit", getWords("get slip"), "onClick=\"document.formData.target = '_blank'\"", "getSlip()");
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
        $dataPrivilege['menu_name']
    );
    $myDataGrid->getRequest();
    if ($myDataGrid->sortName == "division_name") {
      $myDataGrid->sortName = "division_name,department_name";
    }
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQL = "SELECT t1.*, employee_id, employee_name, division_name, department_name, branch_name
                          ,to_char(t1.partial_absence_date,'MM/DD/YYYY') As partial_absence_date_
                       FROM hrd_absence_partial AS t1
                       LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id
                       LEFT JOIN hrd_branch  AS t6 ON t2.branch_code = t6.branch_code
                       LEFT JOIN hrd_division  AS t4 ON t2.division_code = t4.division_code
                       LEFT JOIN hrd_department  AS t5 ON t2.department_code = t5.department_code";
    $strSQLCOUNT = "SELECT count(*) FROM hrd_absence_partial as t1
                       LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id";
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
    $dataset = $myDataGrid->getData($db, $strSQL);
    foreach ($dataset AS $strKey => $arrDetail) {
      $dataset[$strKey]['partial_absence_type'] = (isset($ARRAY_PARTIAL_ABSENCE_TYPE[$dataset[$strKey]['partial_absence_type']])) ? $ARRAY_PARTIAL_ABSENCE_TYPE[$dataset[$strKey]['partial_absence_type']] : "";
    }
    //bind Datagrid with array dataset and branchCode
    $myDataGrid->bind($dataset);
    $DataGrid = $myDataGrid->render();
  } else {
    $DataGrid = "";
  }
  return $DataGrid;
}

function cutApprovedTime($params)
{
  extract($params);
  return substr($value, 0, 16);
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
      $strSQLx = "SELECT status, employee_name, partial_absence_date, partial_absence_type
                    FROM hrd_absence_partial AS t1 
                    LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
                    WHERE t1.id = '$strValue' ";
      $resDb = $db->execute($strSQLx);
      if ($rowDb = $db->fetchrow($resDb)) {
        //the status should be increasing
        if (isProcessable($rowDb['status'], $intStatus)) {
          $strSQL .= "UPDATE hrd_absence_partial SET $strUpdate status = '$intStatus'  ";
          $strSQL .= "WHERE id = '$strValue'; ";
          writeLog(
              ACTIVITY_EDIT,
              MODULE_PAYROLL,
              $rowDb['employee_name'] . " - " . $rowDb['partial_absence_date'] . " - " . $rowDb['partial_absence_type'],
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
  $dataAbsencePartial = new cHrdAbsencePartial();
  $dataAbsencePartial->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataAbsencePartial->strMessage;
} //deleteData
// fungsi untuk melakukan proses slip gaji
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  $strDataID = getPostValue('dataID');
  $isNew = ($strDataID == "");
  $strPageTitle = $dataPrivilege['menu_name'];
  $strDataDateFrom = (isset($_SESSION['sessionFilterDateFrom'])) ? $_SESSION['sessionFilterDateFrom'] : date(
          "m"
      ) . "/01/" . date("Y");
  $strDataDateThru = (isset($_SESSION['sessionFilterDateThru'])) ? $_SESSION['sessionFilterDateThru'] : date($_SESSION['sessionDateSetting']['php_format']);
  $strDataDivision = (isset($_SESSION['sessionFilterDivision'])) ? $_SESSION['sessionFilterDivision'] : "";
  $strDataDepartment = (isset($_SESSION['sessionFilterDepartment'])) ? $_SESSION['sessionFilterDepartment'] : "";
  $strDataSection = (isset($_SESSION['sessionFilterSection'])) ? $_SESSION['sessionFilterSection'] : "";
  $strDataSubSection = (isset($_SESSION['sessionFilterSubSection'])) ? $_SESSION['sessionFilterSubSection'] : "";
  $strDataEmployee = (isset($_SESSION['sessionFilterEmployee'])) ? $_SESSION['sessionFilterEmployee'] : "";
  $strDataEmployeeStatus = (isset($_SESSION['sessionFilterEmployeeStatus'])) ? $_SESSION['sessionFilterEmployeeStatus'] : "";
  $strDataActive = (isset($_SESSION['sessionFilterActive'])) ? $_SESSION['sessionFilterActive'] : "";
  if (isset($_REQUEST['dataDateFrom'])) {
    $strDataDateFrom = $_REQUEST['dataDateFrom'];
  }
  if (isset($_REQUEST['dataDateThru'])) {
    $strDataDateThru = $_REQUEST['dataDateThru'];
  }
  if (isset($_REQUEST['dataDivision'])) {
    $strDataDivision = $_REQUEST['dataDivision'];
  }
  if (isset($_REQUEST['dataDepartment'])) {
    $strDataDepartment = $_REQUEST['dataDepartment'];
  }
  if (isset($_REQUEST['dataSection'])) {
    $strDataSection = $_REQUEST['dataSection'];
  }
  if (isset($_REQUEST['dataSubSection'])) {
    $strDataSubSection = $_REQUEST['dataSubSection'];
  }
  if (isset($_REQUEST['dataEmployee'])) {
    $strDataEmployee = $_REQUEST['dataEmployee'];
  }
  if (isset($_REQUEST['dataEmployeeStatus'])) {
    $strDataEmployeeStatus = $_REQUEST['dataEmployeeStatus'];
  }
  if (isset($_REQUEST['dataActive'])) {
    $strDataActive = $_REQUEST['dataActive'];
  }
  // default selalu ambil yang aktif saja
  //if($strDataActive == "") $strDataActive = 1;
  // simpan dalam session
  $_SESSION['sessionFilterDateFrom'] = $strDataDateFrom;
  $_SESSION['sessionFilterDateThru'] = $strDataDateThru;
  $_SESSION['sessionFilterDivision'] = $strDataDivision;
  $_SESSION['sessionFilterDepartment'] = $strDataDepartment;
  $_SESSION['sessionFilterSection'] = $strDataSection;
  $_SESSION['sessionFilterSubSection'] = $strDataSubSection;
  $_SESSION['sessionFilterEmployee'] = $strDataEmployee;
  $_SESSION['sessionFilterEmployeeStatus'] = $strDataEmployeeStatus;
  $_SESSION['sessionFilterActive'] = $strDataActive;
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  //generate form untuk select trip type
  //trip type harus dipilih dulu supaya jenis2 trip allowance dapat ditentukan
  $f = new clsForm("formFilter", 3, "100%", "");
  $f->caption = strtoupper("type");
  $strDateFrom = getInitialValue("DateFrom", date($_SESSION['sessionDateSetting']['php_format']));
  $explodeDateFrom = explode('-', $strDateFrom);
  if (count($explodeDateFrom) == 3) {
    $strDateFrom = $explodeDateFrom[1] . '/' . $explodeDateFrom[2] . '/' . $explodeDateFrom[0];
  }
  $strDateThru = getInitialValue("DateThru", date($_SESSION['sessionDateSetting']['php_format']));
  $explodeDateThru = explode('-', $strDateThru);
  if (count($explodeDateThru) == 3) {
    $strDateThru = $explodeDateThru[1] . '/' . $explodeDateThru[2] . '/' . $explodeDateThru[0];
  }
  $f->addInput(
      getWords("date from"),
      "dataDateFrom",
      $strDateFrom,
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      getWords("date thru"),
      "dataDateThru",
      $strDateThru,
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addSelect(
      getWords("type"),
      "dataType",
      getDataListPartialAbsenceType("", true, ["value" => "", "text" => "", "selected" => true]),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $autoCompleteValue = getInitialValue("Employee", null, $strDataEmployee);
  $employeeName = '';
  if (!empty($autoCompleteValue)) {
    $employeeData = getEmployeNameByID($db, $autoCompleteValue);
    $employeeName = $employeeData['employee_name'];
  }
  $f->addInputAutoComplete(
      getWords("employee"),
      "dataEmployeeID",
      getDataEmployee(getInitialValue("Employee", null, $strDataEmployee)),
      "style=width:$strDefaultWidthPx " . $strEmpReadonly,
      "string",
      false,
      true,
      true,
      "",
      "",
      true,
      null,
      "../global/hrd_ajax_source.php?action=getemployee",
      $autoCompleteValue
  );
  $f->addLabelAutoComplete("", "dataEmployeeID", $employeeName);
  //$f->addInputAutoComplete(getWords("employee ID"), "dataEmployeeID", getDataEmployee(getInitialValue("Employee", null, $strDataEmployee)), "style=width:$strDefaultWidthPx ".$strEmpReadonly, "string", false, true, true, "", "", true, null, "../global/hrd_ajax_source.php?action=getemployee");
  //$f->addLabelAutoComplete("", "dataEmployeeID", "");
  $f->addSelect(
      getWords("request status"),
      "dataRequestStatus",
      getDataListRequestStatus(
          getInitialValue("RequestStatus"),
          true,
          ["value" => "", "text" => " ", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("branch"),
      "dataBranch",
      getDataListBranch(getInitialValue("Branch"), true, ["value" => "", "text" => " ", "selected" => true]),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("level"),
      "dataPosition",
      getDataListPosition(
          getInitialValue("Position"),
          true,
          ["value" => "", "text" => " ", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("grade"),
      "dataGrade",
      getDataListSalaryGrade(
          getInitialValue("Grade"),
          true,
          ["value" => "", "text" => " ", "selected" => true]
      ),
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
          ["value" => "", "text" => " ", "selected" => true]
      ),
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
  $f->addLiteral("", "", "");
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
      getDataListDivision(
          getInitialValue("Division", "", $strDataDivision),
          true,
          ["value" => "", "text" => " ", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['division'] == "")
  );
  $f->addSelect(
      getWords("department "),
      "dataDepartment",
      getDataListDepartment(
          getInitialValue("Department", "", $strDataDepartment),
          true,
          ["value" => "", "text" => " ", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['department'] == "")
  );
  $f->addSelect(
      getWords("section"),
      "dataSection",
      getDataListSection(
          getInitialValue("Section", "", $strDataSection),
          true,
          ["value" => "", "text" => " ", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['section'] == "")
  );
  $f->addSelect(
      getWords("sub section"),
      "dataSubSection",
      getDataListSubSection(
          getInitialValue("SubSection", "", $strDataSubSection),
          true,
          ["value" => "", "text" => " ", "selected" => true]
      ),
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
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('absence partial entry form');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = dataAbsenceSubmenu($strWordsPartialAbsenceList);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
?>