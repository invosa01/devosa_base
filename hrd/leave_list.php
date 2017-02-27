<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_absence_type.php');
include_once('../classes/hrd/hrd_absence.php');
include_once('../classes/hrd/hrd_absence_detail.php');
$dataPrivilege = getDataPrivileges(
    "absence_list.php",
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
$strWordsEntryAbsence = getWords("absence entry");
$strWordsAbsenceList = getWords("absence list");
$strWordsEntryPartialAbsence = getWords("partial absence entry");
$strWordsPartialAbsenceList = getWords("partial absence list");
$strWordsAnnualLeave = getWords("annual leave");
$DataGrid = "";
$formFilter = "";
//DAFTAR FUNGSI--------------------------------------------------------------------------------------------------------------
function getData($db)
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
  if ($arrData['dataAbsenceType'] != "") {
    $strKriteria .= "AND absence_type_code = '" . $arrData['dataAbsenceType'] . "'";
  }
  if (validStandardDate($strDateFrom = $arrData['dataDateFrom']) && validStandardDate(
          $strDateThru = $arrData['dataDateThru']
      )
  ) {
    $strKriteria .= "AND ((date_from, date_thru) ";
    $strKriteria .= "    OVERLAPS (DATE '$strDateFrom', DATE '$strDateThru') ";
    $strKriteria .= "    OR (date_thru = DATE '$strDateFrom') ";
    $strKriteria .= "    OR (date_thru = DATE '$strDateThru')) ";
  }
  if ($arrData['dataEmployee'] != "") {
    $strKriteria .= "AND employee_id = '" . $arrData['dataEmployee'] . "'";
  }
  if ($arrData['dataPosition'] != "") {
    $strKriteria .= "AND position_code = '" . $arrData['dataPosition'] . "'";
  }
  if ($arrData['dataBranch'] != "") {
    $strKriteria .= "AND branch_code = '" . $arrData['dataBranch'] . "'";
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
    $strKriteria .= "AND status = '" . $arrData['dataRequestStatus'] . "'";
  }
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND division_code = '" . $arrData['dataDivision'] . "'";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND department_code = '" . $arrData['dataDepartment'] . "'";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND section_code = '" . $arrData['dataSection'] . "'";
  }
  if ($arrData['dataSubSection'] != "") {
    $strKriteria .= "AND sub_section_code = '" . $arrData['dataSubSection'] . "'";
  }
  $strKriteria .= "AND deduct_leave = true";
  $strKriteria .= $strKriteriaCompany;
  if ($db->connect()) {
    $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
    $myDataGrid->caption = getWords(
        strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
    );
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->setCriteria($strKriteria);
    $myDataGrid->pageSortBy = "date_from,employee_name";
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => '']),
        true /*bolDisableSelfStatusChange*/
    );
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("created"), "created_date", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date from"), "date_from", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date thru"), "date_thru", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("employee id"), "employee_id", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("employee name"), "employee_name", "", ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("department"),
            "department_code",
            "",
            ['nowrap' => ''],
            false,
            false,
            "",
            "getDepartmentName()"
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("is leave"),
            "deduct_leave",
            "",
            ['nowrap' => ''],
            false,
            false,
            "",
            "printActiveSymbol()"
        )
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("absence type"), "absence_type_code", "", ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("duration"), "duration", "", ['nowrap' => ''], false, false, "", "")
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("status"), "status", "", ['nowrap' => ''], false, false, "", "printRequestStatus()"
        )
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
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_absence AS t1 LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id ";
    $strSQLCOUNT .= "LEFT JOIN hrd_absence_type AS t3 ON t1.absence_type_code = t3.code  ";
    $strSQL = "select * from (SELECT t1.*, t1.created::date as created_date, t3.deduct_leave, t3.leave_weight, t2.id AS idemployee, t2.employee_id, t2.employee_name, t2.id_company, t2.active, t2.employee_status, t2.grade_code, t2.branch_code, ";
    $strSQL .= "t2.position_code, t2.division_code, t2.department_code, t2.section_code, t2.sub_section_code ";
    $strSQL .= "FROM hrd_absence AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "LEFT JOIN hrd_absence_type AS t3 ON t1.absence_type_code = t3.code) as t  ";
    $strSQL .= "WHERE 1=1 $strKriteria";
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
      $strSQLx = "SELECT status, employee_name, t1.created, absence_type_code
                    FROM hrd_absence AS t1 
                    LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
                    WHERE t1.id = '$strValue' ";
      $resDb = $db->execute($strSQLx);
      if ($rowDb = $db->fetchrow($resDb)) {
        //the status should be increasing
        if (isProcessable($rowDb['status'], $intStatus)) {
          $strSQL .= "UPDATE hrd_absence SET $strUpdate status = '$intStatus'  ";
          $strSQL .= "WHERE id = '$strValue'; ";
          writeLog(
              ACTIVITY_EDIT,
              MODULE_PAYROLL,
              $rowDb['employee_name'] . " - " . $rowDb['created'] . " - " . $rowDb['absence_type_code'],
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
    $arrKeys2['id_absence'][] = $strValue;
  }
  $tblAbsence = new cHrdAbsence();
  $tblAbsenceDetail = new cHrdAbsenceDetail();
  $tblAbsence->deleteMultiple($arrKeys);
  $tblAbsenceDetail->deleteMultiple($arrKeys2);
  writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, implode(",", $arrKeys2['id_absence']));
  $myDataGrid->message = $tblAbsence->strMessage;
} //deleteData
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  if (isset($_POST['btnShowAlert']) && $_POST['btnShowAlert'] == 1) {
    $reqStatus = 0;
    $_getInitialValue = "getInitialValueAlert";
  } else {
    $reqStatus = "";
    $_getInitialValue = "getInitialValue";
  }
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
  $f = new clsForm("formFilter", 3, "100%", "");
  $f->caption = strtoupper($strWordsFILTERDATA);
  $f->addInput(
      getWords("date from"),
      "dataDateFrom",
      $_getInitialValue("DateFrom", date("Y-m-") . "01"),
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      getWords("date thru"),
      "dataDateThru",
      $_getInitialValue("DateThru", date("Y-m-d")),
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInputAutoComplete(
      getWords("employee"),
      "dataEmployee",
      getDataEmployee($_getInitialValue("Employee", null, $strDataEmployee)),
      "style=width:$strDefaultWidthPx " . $strEmpReadonly,
      "string",
      false
  );
  $f->addSelect(
      getWords("absence type"),
      "dataAbsenceType",
      getDataListabsenceType("", true, ["value" => "", "text" => "", "selected" => true]),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployee", "");
  $f->addSelect(
      getWords("request status"),
      "dataRequestStatus",
      getDataListRequestStatus(
          $_getInitialValue("RequestStatus", null, $reqStatus),
          true,
          ["value" => "", "text" => "", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addLiteral("", "", "");
  $f->addSelect(
      getWords("branch"),
      "dataBranch",
      getDataListBranch($_getInitialValue("Branch"), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("level"),
      "dataPosition",
      getDataListPosition($_getInitialValue("Position"), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("grade"),
      "dataGrade",
      getDataListSalaryGrade($_getInitialValue("Grade"), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("status"),
      "dataEmployeeStatus",
      getDataListEmployeeStatus(
          $_getInitialValue("EmployeeStatus", "", ""),
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
          $_getInitialValue("Active"),
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
      getDataListDivision($_getInitialValue("Division", "", $strDataDivision), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['division'] == "")
  );
  $f->addSelect(
      getWords("department "),
      "dataDepartment",
      getDataListDepartment($_getInitialValue("Department", "", $strDataDepartment), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['department'] == "")
  );
  $f->addSelect(
      getWords("section"),
      "dataSection",
      getDataListSection($_getInitialValue("Section", "", $strDataSection), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['section'] == "")
  );
  $f->addSelect(
      getWords("sub section"),
      "dataSubSection",
      getDataListSubSection($_getInitialValue("SubSection", "", $strDataSubSection), true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['sub_section'] == "")
  );
  $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
  if ($bolCanApprove) {
    $f->addSubmit("btnSync", getWords("sync"), "", true, true, "", "", "");
  }
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