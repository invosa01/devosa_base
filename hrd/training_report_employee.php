<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
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
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintAll']) || isset($_REQUEST['btnExcel']));
$bolFull = (isset($_REQUEST['filterFull']));
$DataGrid = "";
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strPaging = "";
$strHidden = "";
$strBtnPrint = "";
$intTotalData = 0;
$strWordsByTopic = getWords("by topic");
$strWordsByEmployee = getWords("by employee");
$bolLimit = true; // default, tampilan dibatasi (paging)
$strLastID = "";
$strLastName = "";
$strButtons = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI-----------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getData($db)
{
  global $dataPrivilege, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge;
  global $f;
  global $DataGrid;
  global $myDataGrid;
  global $strKriteriaCompany;
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if ($arrData['dataTrainingType'] != "") {
    $strKriteria .= "AND t1.training_type = '" . $arrData['dataTrainingType'] . "'";
  }
  if (validStandardDate($arrData['dataDateFrom']) && validStandardDate($arrData['dataDateThru'])) {
    $strKriteria .= "AND (t1.training_date BETWEEN '" . $arrData['dataDateFrom'] . "' AND '" . $arrData['dataDateThru'] . "')  ";
  }
  if ($arrData['dataEmployee'] != "") {
    $strKriteria .= "AND t4.employee_id = '" . $arrData['dataEmployee'] . "'";
  }
  if ($arrData['dataPosition'] != "") {
    $strKriteria .= "AND t4.position_code = '" . $arrData['dataPosition'] . "'";
  }
  if ($arrData['dataGrade'] != "") {
    $strKriteria .= "AND t4.grade_code= '" . $arrData['dataGrade'] . "'";
  }
  if ($arrData['dataActive'] != "") {
    $strKriteria .= "AND t4.active= '" . $arrData['dataActive'] . "'";
  }
  if ($arrData['dataEmployeeStatus'] != "") {
    $strKriteria .= "AND t4.employee_status = '" . $arrData['dataEmployeeStatus'] . "'";
  }
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND t4.division_code = '" . $arrData['dataDivision'] . "'";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND t4.department_code = '" . $arrData['dataDepartment'] . "'";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND t4.section_code = '" . $arrData['dataSection'] . "'";
  }
  if ($arrData['dataSubSection'] != "") {
    $strKriteria .= "AND t4.sub_section_code = '" . $arrData['dataSubSection'] . "'";
  }
  $strKriteria .= $strKriteriaCompany;
  if ($db->connect()) {
    $myDataGrid->caption = getWords(
        strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
    );
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->pageSortBy = "employee_name";
    $myDataGrid->setCriteria($strKriteria);
    $myDataGrid->groupBy("employee_id");
    $myDataGrid->hasGrandTotal = true;
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("employee ID"),
            "employee_id",
            "",
            ['nowrap' => ''],
            null,
            null,
            null,
            "printNewID()"
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Name"), "employee_name", "", ['nowrap' => ''], null, null, null, "printNewName()")
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("division"), "division_code", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("department"), "department_code", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("section"), "section_code", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("sub section"), "sub_section_code", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("type"), "training_type", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("topic"), "topic", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("training date"), "training_date", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("expected result"), "result", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("institution"), "name_vendor", "", ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("total cost"),
            "total_cost",
            null,
            ['align' => 'right'],
            false,
            false,
            "",
            "formatNumber()",
            "numeric",
            true,
            15,
            true,
            "total_cost"
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("duration"),
            "total_hour",
            null,
            ['align' => 'right'],
            false,
            false,
            "",
            "",
            "numeric",
            true,
            15,
            true,
            "total_hour"
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("status"), "status", "", "", false, false, "", "printRequestStatus()")
    );
    foreach ($arrData AS $key => $value) {
      $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
    }
    //tampilkan buttons sesuai dengan otoritas, common_function.php
    $myDataGrid->addButtonExportExcel(
        "Export Excel",
        $dataPrivilege['menu_name'] . ".xls",
        getWords($dataPrivilege['menu_name'])
    );
    $myDataGrid->getRequest();
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQL = "
        SELECT t1.*, t2.cost + t2.other_cost as total_cost, t5.topic, t5.training_type, t3.name_vendor, t4.division_code, t4.department_code, t4.section_code,  t4.id_company, t4.employee_id, t4.employee_name
        FROM hrd_training_request AS t1 
        LEFT JOIN hrd_training_request_participant AS t2 ON t1.id = t2.id_request
		LEFT JOIN hrd_training_plan AS t5 ON t1.id_plan= t5.id
        LEFT JOIN hrd_training_vendor AS t3 ON t5.id_training_vendor = t3.id
        LEFT JOIN hrd_employee AS t4 ON t2.id_employee = t4.id
        WHERE t1.status=" . REQUEST_STATUS_APPROVED . " ";
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_training_request AS t1
       LEFT JOIN hrd_training_request_participant AS t2 ON t1.id = t2.id_request
       LEFT JOIN hrd_employee AS t4 ON t2.id_employee = t4.id WHERE 1=1";
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

//----------------------------------------------------------------------
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
      getWords("training type"),
      "dataTrainingType",
      getDataListTrainingType("", true, ["value" => "", "text" => "", "selected" => true]),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addInputAutoComplete(
      getWords("employee"),
      "dataEmployee",
      getDataEmployee(getInitialValue("Employee", null, $strDataEmployee)),
      "style=width:$strDefaultWidthPx " . $strEmpReadonly,
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployee", "");
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
function printNewID($params)
{
  global $strLastID;
  extract($params);
  $bolNew = ($value != $strLastID);
  $strLastID = $value;
  return ($bolNew) ? $strLastID : "";
}

function printNewName($params)
{
  global $strLastName;
  extract($params);
  $bolNew = ($value != $strLastName);
  $strLastName = $value;
  return ($bolNew) ? $strLastName : "";
}

$tbsPage = new clsTinyButStrong;
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
if ($bolPrint) {
  $strMainTemplate = getTemplate(str_replace(".php", "_print.html", basename($_SERVER['PHP_SELF'])));
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
