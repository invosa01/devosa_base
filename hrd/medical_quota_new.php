<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_salary_grade.php');
include_once('../classes/hrd/hrd_medical_quota.php');
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
$strWordsDataEntry = getWords("data entry");
$strWordsBusinessTripList = getWords("business trip list");
$strWordsBusinessTripReport = getWords("business trip report");
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
$DataGrid = "";
//INISIALISASI------------------------------------------------------------------------------------------------------------------
//ambil semua jenis trip
//DAFTAR FUNGSI--------------------------------------------------------------------------------------------------------------
function getData($db)
{
  global $dataPrivilege;
  global $f;
  global $arrTripCost;
  global $arrTripCostType;
  global $myDataGrid;
  global $DataGrid;
  global $strKriteriaCompany;
  //global $arrUserInfo;
  $strDataTripTypeID = $f->getValue('dataTripType');
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if (validStandardDate($arrData['dataDateFrom']) && validStandardDate($arrData['dataDateThru'])) {
    $strKriteria .= "AND proposal_date BETWEEN '" . $arrData['dataDateFrom'] . "' AND '" . $arrData['dataDateThru'] . "'";
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
  $strKriteria .= $strKriteriaCompany;
  if ($db->connect() && $strDataTripTypeID != "") {
    $myDataGrid->caption = getWords(
        strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
    );
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->setPageLimit("all");
    $myDataGrid->setCriteria($strKriteria);
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID", "id", ["rowspan" => 2, 'width' => '30'], ['align' => 'center', 'nowrap' => '']),
        true /*bolDisableSelfStatusChange*/
    );
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ["rowspan" => 2, 'width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("id"), "employee_id", ["rowspan" => 2, 'width' => '150'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("employee name"),
            "employee_name",
            ["rowspan" => 2, 'width' => '150'],
            ['nowrap' => '']
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("sex"), "gender", ["rowspan" => 2, 'width' => '150'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("grade"), "grade_code", ["rowspan" => 2, 'width' => '150'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("medical status"),
            "medical_quota_status",
            ["rowspan" => 2, 'width' => '150'],
            ['nowrap' => '']
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("outpatient"), "", ["colspan" => 4], null, false, false, "", "", "string", true, 40
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("quota"), "quota", ["rowspan" => 2, 'width' => '200'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("additional quota"),
            "additional_quota",
            ["rowspan" => 2, 'width' => '150'],
            ['nowrap' => '']
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("claim"), "claim", ["rowspan" => 2, 'width' => '150'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("remain"), "remain", ["rowspan" => 2, 'width' => '250'], ""));
    foreach ($arrMedicalType[$strMedicalType] AS $arrMedicalCode) {
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords($arrTripCostType[$strCostID]['trip_cost_type_name']),
              "trip_cost_" . $strCostID,
              ['width' => '75'],
              ['align' => 'center'],
              false,
              false
          )
      );
    }
    foreach ($arrMedicalType AS $strType => $arrMedicalCode) {
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords($strType),
              "",
              ["colspan" => count($arrMedicalCode)],
              null,
              false,
              false,
              "",
              "",
              "string",
              true,
              40
          )
      );
      foreach ($arrMedicalCode AS $strCode => $arrDetail) {
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords($strCode),
                "amount_" . $strType . "_" . $strCode,
                ['width' => '75'],
                ['align' => 'center'],
                false,
                false
            )
        );
        $strResult .= "  <td align=right>" . standardFormat($intPlatform, true, 0) . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . standardFormat($intClaim, true, 0) . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . standardFormat($intRemain, true, 0) . "&nbsp;</td>";
      }
    }
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("status"), "status", ["rowspan" => 2], "", false, false, "", "printRequestStatus()"
        )
    );
    if ($dataPrivilege['edit'] == 't') {
      $myDataGrid->addColumn(
          new DataGrid_Column(
              "",
              "",
              ["rowspan" => 2, "width" => "60"],
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
    if ($dataPrivilege['delete'] == 't') {
      $myDataGrid->addSpecialButton(
          "btnDelete",
          "btnDelete",
          "submit",
          getWords("delete"),
          "onClick=\"javascript:return myClient.confirmDelete();\"",
          "deleteData()"
      );
    }
    if ($dataPrivilege['check'] == 't') {
      $myDataGrid->addSpecialButton(
          "btnChecked",
          "btnChecked",
          "submit",
          getWords("checked"),
          "onClick=\"javascript:return myClient.confirmChangeStatus();\"",
          "callChangeStatus()"
      );
    }
    if ($dataPrivilege['approve'] == 't') {
      $myDataGrid->addSpecialButton(
          "btnApproved",
          "btnApproved",
          "submit",
          getWords("approved"),
          "onClick=\"javascript:return myClient.confirmChangeStatus();\"",
          "callChangeStatus()"
      );
      $myDataGrid->addSpecialButton(
          "btnPaid",
          "btnPaid",
          "submit",
          getWords("paid"),
          "onClick=\"javascript:return myClient.confirmChangeStatus();\"",
          "callChangeStatus()"
      );
      $myDataGrid->addSpecialButton(
          "btnDenied",
          "btnDenied",
          "submit",
          getWords("denied"),
          "onClick=\"javascript:return myClient.confirmChangeStatus();\"",
          "callChangeStatus()"
      );
    }
    $myDataGrid->addButtonExportExcel(
        "Export Excel",
        $dataPrivilege['menu_name'] . ".xls",
        getWords($dataPrivilege['menu_name'])
    );
    $myDataGrid->getRequest();
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_trip AS t1 LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id";
    $strSQL = "SELECT t1.*, employee_id, employee_name, division_code, t2.branch_code || ' - ' || branch_name as branch
                       FROM hrd_trip AS t1
                       LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id
                       LEFT JOIN hrd_branch  AS t3 ON t2.branch_code = t3.branch_code";
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
      foreach ($arrTripCostType AS $strCostID => $arrCostDetail) {
        $dataset[$strKey]['trip_cost_' . $strCostID] = (isset($arrTripDetail[$strCostID])) ? $arrTripDetail[$strCostID]['amount'] : 0;
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

function printEditLink($params)
{
  extract($params);
  return "
      <a href=\"trip_edit.php?dataID=" . $record['id'] . "\">" . getWords('edit') . "</a>";
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
  $strTripTypeID = $arrData['dataTripType'];
  $tblHrdSalaryGrade = new cHrdSalaryGrade();
  $arrGrade = $tblHrdSalaryGrade->findAll("", "grade_code", "", null, 1, "grade_code");
  $tblTripTypeCostSetting = new cHrdTripTypeCostSetting();
  $arrTripCostSetting = $tblTripTypeCostSetting->findAll(
      "id_trip_type = $strTripTypeID AND include = 't'",
      "id_trip_cost_type",
      "",
      null,
      1,
      "id_trip_cost_type"
  );
  $tblHrdTripCostPlatform = new cHrdTripCostPlatform();
  $data = ["id_trip_type" => $strTripTypeID];
  foreach ($arrGrade AS $strGradeCode => $arrGradeDetail) {
    $data['grade_code'] = $strGradeCode;
    foreach ($arrTripCostSetting AS $strCostID => $arrSettingDetail) {
      $data['id_trip_cost_type'] = $strCostID;
      if (isset($arrData['detailQuota_' . $strGradeCode . '_' . $strCostID])) {
        if (!is_numeric($arrData['detailQuota_' . $strGradeCode . '_' . $strCostID])) {
          $bolSuccess = false;
          $strError = $error['invalid_number'];
          continue;
        }
        $data['amount'] = $arrData['detailQuota_' . $strGradeCode . '_' . $strCostID];
        $tblHrdTripCostPlatform->delete(
            ["id_trip_type" => $strTripTypeID, "id_trip_cost_type" => $strCostID, "grade_code" => $strGradeCode]
        );
        $tblHrdTripCostPlatform->insert($data);
      }
    }
  }
  if ($bolSuccess) {
    $myDataGrid->message = $tblHrdTripCostPlatform->strMessage;
  } else {
    $myDataGrid->errorMessage = $strError;
  }
} // saveData
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
      $strSQLx = "SELECT status, employee_name, proposal_date, t1.created
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
              $rowDb['employee_name'] . " - " . $rowDb['created'] . " - " . $rowDb['proposal_date'],
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
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  $strDataID = getPostValue('dataID');
  $isNew = ($strDataID == "");
  $strDataTripTypeID = getPostValue('dataTripType');
  if ($strDataTripTypeID == "") {
    $arrID = $tblTripType->find("", "id", "", null, 1, "id");
    $strDataTripTypeID = $arrID['id'];
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
  //generate form untuk select trip type
  //trip type harus dipilih dulu supaya jenis2 trip allowance dapat ditentukan
  $f = new clsForm("formFilter", 3, "100%", "");
  $f->caption = strtoupper("trip type");
  $f->addSelect(
      getWords("trip type"),
      "dataTripType",
      getDataListTripType($strDataTripTypeID),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addInput(
      getWords("date from"),
      "dataDateFrom",
      date("Y-m") . "-01",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      getWords("date thru"),
      "dataDateThru",
      date("Y-m-d"),
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInputAutoComplete(
      getWords("employee ID"),
      "dataEmployeeID",
      getDataEmployee($strDataEmployee),
      "style=width:$strDefaultWidthPx " . $strEmpReadonly,
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployeeID", "");
  $f->addSelect(
      getWords("request status"),
      "dataRequestStatus",
      getDataListRequestStatus("", true, ["value" => "", "text" => "", "selected" => true]),
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
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
?>