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
$strWordsEntryAbsence = getWords("entry absence");
$strWordsAbsenceList = getWords("absence list");
$strWordsEntryPartialAbsence = getWords("partial absence entry");
$strWordsPartialAbsenceList = getWords("partial absence list");
$strWordsAnnualLeave = getWords("annual leave");
$strConfirmSave = getWords("save");
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
  global $arrUserInfo;
  //global $arrUserInfo;
  $initFilterStatus = getInitialValue("RequestStatus", $reqStatus, $reqStatus);
  $arrData = $f->getObjectValues();
  $splitDateFrom = explode('/', $arrData['dataDateFrom']);
  $strDateFrom = $splitDateFrom[2] . '-' . $splitDateFrom[0] . '-' . $splitDateFrom[1];
  $splitDateThru = explode('/', $arrData['dataDateThru']);
  $strDateThru = $splitDateThru[2] . '-' . $splitDateThru[0] . '-' . $splitDateThru[1];
  $strKriteria = "";
  // GENERATE CRITERIA
  if ($arrData['dataAbsenceType'] != "") {
    $strKriteria .= "AND absence_type_code = '" . $arrData['dataAbsenceType'] . "'";
  }
  //if (validStandardDate($strDateFrom = $arrData['dataDateFrom']) && validStandardDate($strDateThru = $arrData['dataDateThru'])) {
  if (validStandardDate($strDateFrom) && validStandardDate($strDateThru)) {
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
  if ($arrData['dataStatus'] != "") {
    $strKriteria .= "AND employee_status = '" . $arrData['dataStatus'] . "'";
  }
  if ($arrData['dataActive'] != "") {
    $strKriteria .= "AND active = '" . $arrData['dataActive'] . "'";
  }
  if ($arrData['dataRequestStatus'] != "") {
    $strKriteria .= "AND status = '" . $arrData['dataRequestStatus'] . "'";
  } elseif ($initFilterStatus != "") {
    # code...
    $strKriteria .= "AND status = '" . $initFilterStatus . "'";
  }
  $strKriteriaDiv = "";
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= " AND division_code = '" . $arrData['dataDivision'] . "'";
    $strKriteriaDiv = " where division_code= '" . $arrData['dataDivision'] . "' ";
  }
  /*
  if ($arrData['dataDepartment']!= "") {
    $strKriteria .= "AND department_code = '".$arrData['dataDepartment']."'";
  }
  if ($arrData['dataSection']!= "") {
    $strKriteria .= "AND section_code = '".$arrData['dataSection']."'";
  }
  if ($arrData['dataSubSection']!= "") {
    $strKriteria .= "AND sub_section_code = '".$arrData['dataSubSection']."'";
  }
  */
  //uddin: tambah kriteria jika employee maka yg muncul employee yg functional dia dan dibawahnya
  $strDataUserRole = $_SESSION['sessionUserRole'];
  if ($strDataUserRole == ROLE_EMPLOYEE or $strDataUserRole == ROLE_SUPERVISOR) {
    if ($arrUserInfo["functional_code"] != "") {
      //$strSQL="SELECT * FROM hrd_functional WHERE head_code='".$arrUserInfo["functional_code"]."'";
      $strSQL = "SELECT t1.*,t2.employee_id FROM hrd_functional as t1
                    LEFT JOIN  (select * from hrd_employee " . $strKriteriaDiv . ") as t2 ON t1.functional_code=t2.functional_code
                    WHERE t1.head_code='" . $arrUserInfo["functional_code"] . "'";
      $resDb = $db->execute($strSQL);
      // $strFunctionalcode="('".$arrUserInfo["functional_code"]."'"; // inisial masukkan kode functional diri sendiri
      $strFunctionalcode = "('DUMMYINVOSAFUNCT'"; // inisial masukkan kode functional diri sendiri
      while ($rowDb = $db->fetchrow($resDb)) {
        //$strFunctionalcode.=",'".$rowDb['functional_code']."'";
        $tempRecursif = getfunctionalrecursif(
            $db,
            $rowDb['functional_code'],
            $rowDb['employee_id'],
            $strKriteriaDiv,
            0
        );
        $strFunctionalcode .= ",'" . $rowDb['functional_code'] . "'" . $tempRecursif;
      }
      $strFunctionalcode .= ")";
      //$strKriteria .= " AND functional_code in ".$strFunctionalcode." ";
      $strKriteria .= " AND (functional_code in " . $strFunctionalcode . " or employee_id='" . $arrUserInfo["employee_id"] . "') ";
    }
  }
  // end tambah kriteria functional code
  //echo $strKriteria;
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
    $myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no."), "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("created"), "created_date_", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date from"), "date_from_", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date thru"), "date_thru_", "", ['nowrap' => '']));
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
    // 201601211052 Rubah Absen Type menjadi Keterangan
    $myDataGrid->addColumn(new DataGrid_Column(getWords("absence type"), "absence_type_note", "", ['nowrap' => '']));
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("absence type"), "absence_type_code", "", array('nowrap' => '')));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("duration"), "duration", "", ['nowrap' => ''], false, false, "", "")
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("status"), "status", "", ['nowrap' => ''], false, false, "", "printRequestStatus()"
        )
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", "", ['nowrap' => ''], false, false, "", ""));
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
      $myDataGrid->addColumn(
          new DataGrid_Column(
              "",
              "",
              ['rowspan' => 2, 'width' => '60'],
              ['align' => 'center', 'nowrap' => ''],
              false,
              false,
              "",
              "printView()",
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
        false,
        $dataPrivilege['approve'],
        false,
        true,
        $myDataGrid
    );
    $myDataGrid->addButtonExportExcel(
        getWords("export excel"),
        $dataPrivilege['menu_name'] . ".xls",
        getWords($dataPrivilege['menu_name'])
    );
    $myDataGrid->getRequest();
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_absence AS t1 LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id";
    $strSQL = "select *,to_char(created_date,'MM/DD/YYYY') As created_date_ ,to_char(date_from,'MM/DD/YYYY') As date_from_,to_char(date_thru,'MM/DD/YYYY') As date_thru_
              from (SELECT t1.*, t1.created::date as created_date, t3.deduct_leave, t3.leave_weight, t2.id AS idemployee, t2.employee_id, t2.employee_name, t2.id_company, t2.active, t2.employee_status, t2.grade_code, t2.branch_code, t3.note as absence_type_note,";
    $strSQL .= "t2.functional_code,t2.position_code, t2.division_code, t2.department_code, t2.section_code, t2.sub_section_code ";
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

function printEditLink($params)
{
  extract($params);
  if ($record['status'] == 0) {
    return "
      <a href=\"absence_edit.php?dataID=" . $record['id'] . "\">" . getWords('edit') . "</a>";
  } else {
    return "";
  }
}

function printView($params)
{
  global $datasetMaster;
  extract($params);
  //var_dump($record);
  return "<a href=\"absence_view.php?dataID=" . $record['id'] . "\">View</a>";
}

//  function callChangeStatus() {
//
//    global $_REQUEST;
////    print_r($_REQUEST);
//    global $db;
//    if (isset($_REQUEST['btnVerified'])) $intStatus = REQUEST_STATUS_VERIFIED;
//    else if (isset($_REQUEST['btnChecked'])) $intStatus = REQUEST_STATUS_CHECKED;
//    else if (isset($_REQUEST['btnApproved'])) $intStatus = REQUEST_STATUS_APPROVED;
//    else if (isset($_REQUEST['btnDenied'])) $intStatus = REQUEST_STATUS_DENIED;
//    else if (isset($_REQUEST['btnPaid'])) $intStatus = REQUEST_STATUS_PAID;
//    changeStatus($db, $intStatus);
//  }
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
  if ($intStatus == REQUEST_STATUS_VERIFIED) {
    $strUpdate = "verified_by = '" . $_SESSION['sessionUserID'] . "', verified_time = now(), ";
    $statusAbsence = 'VERIFIED';
  } else if ($intStatus == REQUEST_STATUS_CHECKED) {
    $strUpdate = "checked_by = '" . $_SESSION['sessionUserID'] . "', checked_time = now(), ";
    $statusAbsence = 'CHECKED';
  } else if ($intStatus == REQUEST_STATUS_APPROVED) {
    $strUpdate = "approved_by = '" . $_SESSION['sessionUserID'] . "', approved_time = now(), ";
    $statusAbsence = 'APPROVED';
  } else if ($intStatus == REQUEST_STATUS_DENIED) {
    $strUpdate = "denied_by = '" . $_SESSION['sessionUserID'] . "', denied_time = now(), ";
    $statusAbsence = 'DENIED';
  } else if ($intStatus == REQUEST_STATUS_PAID) {
    $strUpdate = "paid_by = '" . $_SESSION['sessionUserID'] . "', paid_time = now(), ";
    $statusAbsence = 'PAID';
  }
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 15) == 'DataGrid1_chkID') {
      $strSQLx = "SELECT id_employee, t2.email, employee_name, t1.created, absence_type_code, t3.note,
        						date_from, date_thru
                    FROM hrd_absence AS t1
                    LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
                    LEFT JOIN hrd_absence_type t3 ON t1.absence_type_code = t3.code
                    WHERE t1.id = '$strValue' ";
      $resDb = $db->execute($strSQLx);
      if ($rowDb = $db->fetchrow($resDb)) {
        //the status should be increasing
        //if ($rowDb['status'] < $intStatus && $rowDb['status'] != REQUEST_STATUS_DENIED )
        if (isProcessable($rowDb['status'], $intStatus)) {
          $strSQL .= "UPDATE hrd_absence SET $strUpdate status = '$intStatus'  ";
          $strSQL .= "WHERE id = '$strValue'; ";
          writeLog(
              ACTIVITY_EDIT,
              MODULE_PAYROLL,
              $rowDb['employee_name'] . " - " . $rowDb['created'] . " - " . $rowDb['absence_type_code'],
              $intStatus
          );
          if ($intStatus == REQUEST_STATUS_APPROVED || $intStatus == REQUEST_STATUS_DENIED) {
            $strBody = "ESS Notification - Email notifikasi untuk employee berikut:" . "\n" . "\n";
            $strBody .= "____________________________________________________________________________________" . "\n";
            $strBody .= "Nama: " . $rowDb['employee_name'] . "\n";
            $strBody .= "Tipe Absen: " . $rowDb['absence_type_code'] . " - ( " . $rowDb['note'] . " )\n";
            //$strBody .= "Absence Type: ".$strDataType."\n";
            $strBody .= "Tanggal: " . $rowDb['date_from'] . " Until " . $rowDb['date_thru'] . "\n";
            $strBody .= "____________________________________________________________________________________" . "\n";
            $strBody .= "Status Pengajuan Absen Anda melalui Sistem ESS ( http://hr.wanaarthalife.com/devosa/ ) : " . $statusAbsence . ". " . "\n";
            $strBody = getBodyEmail(0, 'Absence', $strBody, $_SESSION['sessionUserID']);
            $strSubject = 'ABSENCE STATUS ' . $statusAbsence;
            $headData = getHeadEmployeeData($db, $rowDb['id_employee']);
            if (!empty($rowDb['email']) && !empty($headData['email'])) {
              sendMail($rowDb['email'], $strSubject, $strBody, $headData['email']);
            }
          }
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
  if (count($arrKeys) > 0) {
    $tblAbsence->deleteMultiple($arrKeys);
    $tblAbsenceDetail->deleteMultiple($arrKeys2);
  }
  writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, implode(",", $arrKeys2['id_absence']));
  $myDataGrid->message = $tblAbsence->strMessage;
} //deleteData
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  if (isset($_POST['btnShowAlert']) && $_POST['btnShowAlert'] == 1) {
    //$dtFrom = getNextYear(date("Y-m-d"), -1);
    //inisial value jika dari alert
    $dtFrom = getInitialValueDateRange($db, "hrd_absence", "date_from", "asc", "status=0", date($_SESSION['sessionDateSetting']['php_format']));
    $dtThru = getInitialValueDateRange($db, "hrd_absence", "date_thru", "desc", "status=0", date($_SESSION['sessionDateSetting']['php_format']));
    //$arrDate=explode("-",$dtFrom);
    //$dtFrom=$arrDate[1]."/".$arrDate[2]."/".$arrDate[0];
    $reqStatus = null;
    $_SESSION["sessiondataEmployee"] = "";
    $_SESSION["sessiondataPosition"] = "";
    $_SESSION["sessiondataSalaryGrade"] = "";
    $_SESSION["sessiondataEmployeeStatus"] = "";
    //$_REQUEST["sessiondataEmployeeStatus"] = "";
    //echo       $_SESSION["sessiondataEmployeeStatus"];
  } else {
    //$dtFrom = date("Y-m-")."01";
    $dtFrom = date("m") . "/01/" . date("Y");
    $dtThru = date($_SESSION['sessionDateSetting']['php_format']);
    $reqStatus = null;
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
      getInitialValue("DateFrom", $dtFrom, $dtFrom),
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  //$f->addInput(getWords("date thru"), "dataDateThru", getInitialValue("DateThru", date("m/d/Y")), array("style" => "width:$strDateWidth"), "date", false, true, true);
  $f->addInput(
      getWords("date thru"),
      "dataDateThru",
      getInitialValue("DateThru", $dtThru, $dtThru),
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addSelect(
      getWords("absence type"),
      "dataAbsenceType",
      getDataListabsenceType("", true, ["value" => "", "text" => "", "selected" => true]),
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
      "dataEmployee",
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
  $f->addLabelAutoComplete("", "dataEmployee", $employeeName);
  //$f->addInputAutoComplete(getWords("employee"), "dataEmployee", getDataEmployee(getInitialValue("Employee", null, $strDataEmployee)), "style=width:$strDefaultWidthPx ".$strEmpReadonly, "string", false);
  //$f->addLabelAutoComplete("", "dataEmployee", "");
  $f->addSelect(
      getWords("request status"),
      "dataRequestStatus",
      getDataListRequestStatus(
          getInitialValue("RequestStatus", $reqStatus, $reqStatus),
          true,
          ["value" => "", "text" => "-", "selected" => true]
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
      "dataStatus",
      getDataListEmployeeStatus(
          getInitialValue("EmployeeStatus"),
          true,
          ["value" => "", "text" => "-", "selected" => true]
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
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('absence data management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = dataAbsenceSubmenu($strWordsAbsenceList);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
?>
