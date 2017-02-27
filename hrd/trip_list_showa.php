<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_salary_grade.php');
include_once('../classes/hrd/hrd_trip_type.php');
include_once('../classes/hrd/hrd_trip_cost_type.php');
include_once('../classes/hrd/hrd_trip_type_cost_setting.php');
include_once('../classes/hrd/hrd_trip_cost_platform.php');
include_once('../classes/hrd/hrd_trip.php');
include_once('../classes/hrd/hrd_trip_detail.php');
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
$strWordsTripAllowanceQuota = getWords("trip allowance quota");
$strWordsDataEntry = getWords("data entry");
$strWordsBusinessTripList = getWords("business trip list");
$strWordsBusinessTripReport = getWords("business trip report");
$strWordsDispositionForm = getWords("disposition form");
$strSlipContent = "";
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", true, true, true);
$DataGrid = "";
//INISIALISASI------------------------------------------------------------------------------------------------------------------
//ambil semua jenis trip
$tblTripType = new cHrdTripType();
$arrTripType = $tblTripType->findAll("", "id, trip_type_code, trip_type_name", "", null, 1, "id");
//ambil semua jenis trip cost untuk setiap currency
$tblTripCostType = new cHrdTripCostType();
foreach ($ARRAY_CURRENCY as $strCurrencyNo => $strCurrencyCode) {
  $arrTripCostType[$strCurrencyCode] = $tblTripCostType->findAll(
      "currency = '$strCurrencyCode'",
      "id, trip_cost_type_name, currency",
      "trip_cost_type_name",
      null,
      1,
      "id"
  );
}
//ambil setting cost untuk trip sesuai dengan trip type yang dipilih
$tblTripTypeCostSetting = new cHrdTripTypeCostSetting();
foreach ($arrTripType AS $strTripID => $arrTripDetail) {
  $arrTripCostSetting = $tblTripTypeCostSetting->findAll(
      "id_trip_type = $strTripID",
      "id_trip_cost_type, include",
      "",
      null,
      1,
      "id_trip_cost_type"
  );
  foreach ($ARRAY_CURRENCY AS $strCurrencyNo => $strCurrencyCode) {
    $arrTripCost[$strTripID][$strCurrencyCode] = [];
    foreach ($arrTripCostType[$strCurrencyCode] AS $strCostID => $arrCostDetail) {
      if (isset($arrTripCostSetting[$strCostID]) && $arrTripCostSetting[$strCostID]['include'] == 't') {
        $arrTripCost[$strTripID][$strCurrencyCode][] = $strCostID;
      }
    }
  }
}
//DAFTAR FUNGSI--------------------------------------------------------------------------------------------------------------
function getData($db)
{
  global $dataPrivilege;
  global $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge;
  global $f;
  global $arrTripCost;
  global $arrTripCostType;
  global $ARRAY_CURRENCY;
  global $myDataGrid;
  global $DataGrid;
  global $strKriteriaCompany;
  //global $arrUserInfo;
  $strDataTripTypeID = $f->getValue('dataTripType');
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if (validStandardDate($arrData['dataDateFrom']) && validStandardDate($arrData['dataDateThru'])) {
    $strKriteria .= "AND t1.created::date BETWEEN '" . $arrData['dataDateFrom'] . "' AND '" . $arrData['dataDateThru'] . "' ";
  }
  if ($strDataTripTypeID != "") {
    $strKriteria .= "AND id_trip_type = '" . $strDataTripTypeID . "' ";
  }
  if ($arrData['dataEmployee'] != "") {
    $strKriteria .= "AND employee_id = '" . $arrData['dataEmployee'] . "' ";
  }
  if ($arrData['dataPosition'] != "") {
    $strKriteria .= "AND position_code = '" . $arrData['dataPosition'] . "' ";
  }
  if ($arrData['dataBranch'] != "") {
    $strKriteria .= "AND t2.branch_code = '" . $arrData['dataBranch'] . "' ";
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
  if ($arrData['dataDestination'] != "") {
    $strKriteria .= "AND destination = '" . $arrData['dataDestination'] . "' ";
  }
  $strKriteria .= $strKriteriaCompany;
  if ($db->connect() && $strDataTripTypeID != "") {
    $myDataGrid->caption = getWords($dataPrivilege['menu_name']);
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->pageSortBy = "created desc";
    $myDataGrid->setCriteria($strKriteria);
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID", "id", ["rowspan" => 2, 'width' => '30'], ['align' => 'center', 'nowrap' => '']),
        true/*bolDisableSelfStatusChange*/
    );
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ["rowspan" => 2, 'width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("form code"), "form_code", ["rowspan" => 2], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("created"), "created", ["rowspan" => 2], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date from"), "date_from", ["rowspan" => 2], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date thru"), "date_thru", ["rowspan" => 2], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("employee id"), "employee_id", ["rowspan" => 2], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("employee name"), "employee_name", ["rowspan" => 2], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("division"), "division_name", ["rowspan" => 2], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("department"), "department_name", ["rowspan" => 2], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("location"), "branch", ["rowspan" => 2, 'width' => '200'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("destination"), "destination", ["rowspan" => 2], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("purpose"), "purpose", ["rowspan" => 2], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("task detail"), "task", ["rowspan" => 2, 'width' => '250'], "")
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", ["rowspan" => 2], ""));
    // tampilkan cost dalam setiap currency jika ada
    foreach ($ARRAY_CURRENCY as $strCurrencyNo => $strCurrencyCode) {
      if (count($arrTripCost[$strDataTripTypeID][$strCurrencyCode]) > 0) {
        //$myDataGrid->addColumn(new DataGrid_Column(getWords("Trip Cost ").$strCurrencyCode, "", array("colspan" => count($arrTripCost[$strDataTripTypeID][$strCurrencyCode])),  array("nowrap" => "nowrap"), true, true, "", "", "string", true, count($arrTripCost[$strDataTripTypeID][$strCurrencyCode])));
        $myDataGrid->addSpannedColumn(
            getWords("Trip Cost ") . $strCurrencyCode,
            count($arrTripCost[$strDataTripTypeID][$strCurrencyCode])
        );
        foreach ($arrTripCost[$strDataTripTypeID][$strCurrencyCode] AS $strCostID) {
          $myDataGrid->addColumn(
              new DataGrid_Column(
                  getWords(
                      $arrTripCostType[$strCurrencyCode][$strCostID]['trip_cost_type_name']
                  ) . " " . $strCurrencyCode,
                  "trip_cost_" . $strCostID,
                  ['width' => '75'],
                  ['align' => 'right'],
                  false,
                  false,
                  "",
                  "formatNumber()",
                  "numeric",
                  true,
                  15
              )
          );
        }
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("total"),
                "total_cost_" . $strCurrencyCode,
                ["rowspan" => 2, 'width' => '75'],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumber()",
                "numeric",
                true,
                15
            )
        );
      }
    }
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("status"), "status", ["rowspan" => 2], "", false, false, "", "printRequestStatus()"
        )
    );
    if (!isset($_POST['btnExportXLS']) && $bolCanEdit) {
      $myDataGrid->addColumn(
          new DataGrid_Column(
              "",
              "",
              ["rowspan" => 2, "width" => "60"],
              ['align' => 'center', 'nowrap' => ''],
              false,
              false,
              "",
              "printGlobalEditLink()",
              "",
              true /*show in excel*/
          )
      );
      //$myDataGrid->addColumn(new DataGrid_Column("", "", array("rowspan" => 2, "width" => "60"), array('align' => 'center', 'nowrap' => ''), false, false, "", "printAddAllowanceLink()", "", true/*show in excel*/));
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
    $myDataGrid->addSpecialButton(
        "btnClosed",
        "btnClosed",
        "submit",
        getWords('closed'),
        "onClick=\"javascript:return myClient.confirmChangeStatus();\"",
        "callChangeStatus()"
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
    $strSQL = "SELECT t1.*, employee_id, employee_name, division_name, department_name, t2.branch_code || ' - ' || branch_name as branch
                       FROM hrd_trip AS t1
                       LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id
                       LEFT JOIN hrd_branch  AS t3 ON t2.branch_code = t3.branch_code
                       LEFT JOIN hrd_division  AS t4 ON t2.division_code = t4.division_code
                       LEFT JOIN hrd_department  AS t5 ON t2.department_code = t5.department_code";
    $strSQLCOUNT = "SELECT count(*) FROM hrd_trip as t1
                       LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id";
    $tblTripDetail = new cHrdTripDetail();
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
    $dataset = $myDataGrid->getData($db, $strSQL);
    foreach ($dataset AS $strKey => $arrDetail) {
      $arrTripDetail = $tblTripDetail->findAll(
          "id_trip = " . $arrDetail['id'],
          "id_trip_cost_type, amount",
          "",
          null,
          1,
          "id_trip_cost_type"
      );
      foreach ($ARRAY_CURRENCY AS $strCurrencyNo => $strCurrencyCode) {
        $intTotal = 0;
        foreach ($arrTripCostType[$strCurrencyCode] AS $strCostID => $arrCostDetail) {
          $intAmount = (isset($arrTripDetail[$strCostID])) ? $arrTripDetail[$strCostID]['amount'] : 0;
          $intTotal += $intAmount;
          $dataset[$strKey]['trip_cost_' . $strCostID] = $intAmount;
        }
        $dataset[$strKey]['total_cost_' . $strCurrencyCode] = $intTotal;
      }
    }
    //bind Datagrid with array dataset and branchCode
    $myDataGrid->bind($dataset);
    $DataGrid = $myDataGrid->render();
  } else {
    $DataGrid = "";
  }
  return $DataGrid;
}

function printQuota($params)
{
  global $arrTripCostType;
  extract($params);
  $strCostID = substr($field, 10);
  return generateInput("detailQuota_" . $record['grade_code'] . "_" . $strCostID, $value);
}

function printAddAllowanceLink($params)
{
  extract($params);
  return "<a href=\"trip_edit.php?dataID=" . $record['id'] . "&cash=t\">" . getWords('edit') . "</a>";
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
      $strSQLx = "SELECT status, employee_name, t1.created, form_code
                    FROM hrd_trip AS t1 
                    LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
                    WHERE t1.id = '$strValue' ";
      $resDb = $db->execute($strSQLx);
      if ($rowDb = $db->fetchrow($resDb)) {
        //the status should be increasing
        if (isProcessable($rowDb['status'], $intStatus)) {
          $strSQL .= "UPDATE hrd_trip SET $strUpdate status = '$intStatus'  ";
          $strSQL .= "WHERE id = '$strValue'; ";
          writeLog(
              ACTIVITY_EDIT,
              MODULE_PAYROLL,
              $rowDb['employee_name'] . " - " . $rowDb['created'] . " - " . $rowDb['form_code'],
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
    $arrKeys2['id_trip'][] = $strValue;
  }
  $tblHrdTrip = new cHrdTrip();
  $tblHrdTripDetail = new cHrdTripDetail();
  $tblHrdTrip->deleteMultiple($arrKeys);
  $tblHrdTripDetail->deleteMultiple($arrKeys2);
  $myDataGrid->message = $tblHrdTrip->strMessage;
} //deleteData
// fungsi untuk melakukan proses slip gaji
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  $_getInitialValue = (isset($_POST['btnShowAlert']) && $_POST['btnShowAlert'] == 1) ? "getInitialValueAlert" : "getInitialValue";
  $strDataID = getPostValue('dataID');
  $isNew = ($strDataID == "");
  $strDataTripTypeID = getPostValue('dataTripType');
  if ($strDataTripTypeID == "") {
    $arrID = $tblTripType->find("", "id", "id", null, 1, "id");
    $strDataTripTypeID = $arrID['id'];
  }
  $strPageTitle = $dataPrivilege['menu_name'];
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
  $f->caption = strtoupper($strWordsFILTERDATA);
  $f->addInput(
      getWords("date from"),
      "dataDateFrom",
      $_getInitialValue("DateFrom"),
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
  $f->addSelect(
      getWords("trip type"),
      "dataTripType",
      getDataListTripType($strDataTripTypeID),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addInputAutoComplete(
      getWords("employee"),
      "dataEmployee",
      getDataEmployee($_getInitialValue("Employee", null, $strDataEmployee)),
      "style=width:$strDefaultWidthPx " . $strEmpReadonly,
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployee", "");
  $f->addSelect(
      getWords("request status"),
      "dataRequestStatus",
      getDataListRequestStatus($_getInitialValue("RequestStatus"), true, $arrEmpty),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
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
      getWords("destination"),
      "dataDestination",
      getDataListDestination("", true, ["value" => "", "text" => "", "selected" => true]),
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
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
?>