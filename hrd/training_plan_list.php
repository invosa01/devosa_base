<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../global/employee_function.php');
include_once('../classes/hrd/hrd_training_plan.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck
);
$strWordsInputTrainingPlan = getWords("input training plan");
$strWordsTrainingPlanList = getWords("training plan list");
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
  //global $arrUserInfo;
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if ($arrData['year'] != "") {
    $strKriteria .= "AND t1.year = '" . $arrData['year'] . "'";
  }
  if (validStandardDate($strDateFrom = $arrData['dataDateFrom']) && validStandardDate(
          $strDateThru = $arrData['dataDateThru']
      )
  ) {
    $strKriteria .= "AND t1.expected_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
  }
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND t1.division_code = '" . $arrData['dataDivision'] . "'";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND t1.department_code = '" . $arrData['dataDepartment'] . "'";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND t1.section_code = '" . $arrData['dataSection'] . "'";
  }
  if ($arrData['dataSubSection'] != "") {
    $strKriteria .= "AND t1.sub_section_code = '" . $arrData['dataSubSection'] . "'";
  }
  // $strKriteria .= $strKriteriaCompany;
  if ($db->connect()) {
    $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
    $myDataGrid->caption = getWords(
        strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
    );
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->setCriteria($strKriteria);
    $myDataGrid->pageSortBy = "expected_date";
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID1", "id_plan", ['rowspan' => 2, 'width' => 30], ['align' => 'center', 'nowrap' => ''])
    );
    $myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no."), "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("topic"), "topic", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("creator"), "creator", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("div."), "division_code", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("dept."), "department_code", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("section"), "section_code", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("sub sect."), "sub_section_code", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("type"), "training_type", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("purpose"), "purpose", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("duration"), "duration", "", ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("expected date"),
            "expected_date",
            "",
            ['nowrap' => ''],
            true,
            true,
            "",
            "formatDate()",
            "string",
            true,
            12,
            false
        )
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("institution"), "name_vendor", "", ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("cost"),
            "cost",
            "",
            ["nowrap" => "nowrap", "align" => "right"],
            false,
            true,
            "",
            "formatNumeric(ignoreZero=false,decimal=2)",
            "numeric",
            true,
            15,
            true,
            "cost"
        )
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", "", ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("status"), "status", "", ['nowrap' => ''], false, false, "", "printRequestStatus()"
        )
    );
    $myDataGrid->addSpannedColumn(getWords("action"), 2);
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
            ["width" => "60"],
            ['align' => 'center', 'nowrap' => ''],
            false,
            false,
            "",
            "printCreatedLink()",
            "",
            false /*show in excel*/
        )
    );
    foreach ($arrData AS $key => $value) {
      $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
    }
    //tampilkan buttons sesuai dengan otoritas, common_function.php
    generateRoleButtons(
        $dataPrivilege['edit'],
        $dataPrivilege['delete'],
        "",
        $dataPrivilege['approve'],
        "",
        true,
        $myDataGrid
    );
    $myDataGrid->addButtonExportExcel(
        getWords("export excel"),
        $dataPrivilege['menu_name'] . ".xls",
        getWords($dataPrivilege['menu_name'])
    );
    $myDataGrid->getRequest();
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_training_plan AS t1";
    $strSQL = "SELECT t1.*,t1.id as id_plan, t2.department_name, t3.employee_name as creator,t4.name_vendor FROM hrd_training_plan AS t1 ";
    $strSQL .= "LEFT JOIN hrd_department AS t2 ON t1.department_code = t2.department_code ";
    $strSQL .= "LEFT JOIN hrd_employee AS t3 ON t1.id_creator = t3.id ";
    $strSQL .= "LEFT JOIN hrd_training_vendor AS t4 ON t1.id_training_vendor = t4.id ";
    $strSQL .= "WHERE 1=1 $strKriteria ";
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
  if ($record['status'] == 2) {
    return "<font color=lightgrey><strike>" . getWords('edit') . "</strike></font>";
  } else {
    return "<a href=\"training_plan_edit.php?dataID=" . $record['id'] . "\">" . getWords('edit') . "</a>";
  }
}

function printCreatedLink($params)
{
  extract($params);
  if ($record['status'] == 2) {
    return "
      <a href=\"training_request_edit.php?btnCreate=Create&id_plan=" . $record['id'] . "\">" . getWords(
        'create request'
    ) . "</a>";
  } else {
    return "<font color=lightgrey><strike>" . getWords('create request') . "</strike></font>";
  }
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
  } else if ($intStatus == REQUEST_STATUS_CHECKED) {
    $strUpdate = "checked_by = '" . $_SESSION['sessionUserID'] . "', checked_time = now(), ";
  } else if ($intStatus == REQUEST_STATUS_APPROVED) {
    $strUpdate = "approved_by = '" . $_SESSION['sessionUserID'] . "', approved_time = now(), ";
  } else if ($intStatus == REQUEST_STATUS_DENIED) {
    $strUpdate = "denied_by = '" . $_SESSION['sessionUserID'] . "', denied_time = now(), ";
  } else if ($intStatus == REQUEST_STATUS_PAID) {
    $strUpdate = "paid_by = '" . $_SESSION['sessionUserID'] . "', paid_time = now(), ";
  }
  foreach ($_REQUEST as $strIndex => $strValue) {
    // echo $strIndex." : ".$strValue." ,    " ;
    if (substr($strIndex, 0, 15) == 'DataGrid1_chkID') {
      $strSQLx = "SELECT t1.*, t2.*
                    FROM hrd_training_plan AS t1 
                    LEFT JOIN hrd_employee AS t2 ON t1.id_creator = t2.id
                    WHERE t1.id = '$strValue' ";
      $resDb = $db->execute($strSQLx);
      if ($rowDb = $db->fetchrow($resDb)) {
        //the status should be increasing
        if ($rowDb['status'] < $intStatus && $rowDb['status'] != REQUEST_STATUS_DENIED) {
          $strSQL .= "UPDATE hrd_training_plan SET $strUpdate status = '$intStatus'  ";
          $strSQL .= "WHERE id = '$strValue'; ";
          writeLog(
              ACTIVITY_EDIT,
              MODULE_OTHER,
              $rowDb['id'] . " - " . $rowDb['created'] . " - " . $rowDb['training_type'],
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
    // $arrKeys2['id_absence'][] = $strValue;
  }
  $tblTrainingPlan = new cHrdTrainingPlan();
  echo "G";
  // $tblAbsenceDetail = new cHrdAbsenceDetail();
  $tblTrainingPlan->deleteMultiple($arrKeys);
  // $tblAbsenceDetail->deleteMultiple($arrKeys2);
  writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, implode(",", $arrKeys['id']));
  $myDataGrid->message = $tblAbsence->strMessage;
} //deleteData
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  if (isset($_POST['btnShowAlert']) && $_POST['btnShowAlert'] == 1) {
    $dtFrom = getNextYear(date("Y-m-d"), -1);
    $reqStatus = 0;
    $_SESSION["sessiondataEmployee"] = "";
    $_SESSION["sessiondataPosition"] = "";
    $_SESSION["sessiondataSalaryGrade"] = "";
    $_SESSION["sessiondataEmployeeStatus"] = "";
    $_REQUEST["sessiondataEmployeeStatus"] = "";
    echo $_SESSION["sessiondataEmployeeStatus"];
  } else {
    $dtFrom = date("Y-m-") . "01";
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
  $f->addSelect(
      getWords("Year"),
      "dataYear",
      getDataYear(),
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
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
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
function getDataYear()
{
  $dtNow = getdate();
  $intYear = $dtNow['year'];
  for ($i = 0; $i < 50; $i++) {
    $arrData[$i]['text'] = $intYear - $i;
    $arrData[$i]['value'] = $intYear - $i;
  }
  while (list($key, $val) = each($arrData)) {
    $temp = &$arrData[$key];
    if ($val['value'] == $intYear) {
      $temp['selected'] = true;
    } else {
      $temp['selected'] = false;
    }
  }
  return $arrData;
}

?>