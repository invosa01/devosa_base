<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_salary_grade.php');
include_once('../classes/hrd/hrd_trip_type.php');
include_once('../classes/hrd/hrd_trip_type_cost_setting.php');
include_once('../classes/hrd/hrd_trip_cost_type.php');
include_once('../classes/hrd/hrd_trip_cost_platform.php');
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
$strWordsTripAllowanceQuota = getWords("trip allowance quota");
$strWordsDataEntry = getWords("data entry");
$strWordsBusinessTripList = getWords("business trip list");
$strWordsBusinessTripReport = getWords("business trip report");
//INISIALISASI----------------------------------------------------------------------------------------------
//ambil semua jenis trip
$tblTripType = new cHrdTripType();
$arrTripType = $tblTripType->findAll("", "id, trip_type_code, trip_type_name", "", null, 1, "id");
//ambil semua jenis trip cost
$tblTripCostType = new cHrdTripCostType();
$arrTripCostType = $tblTripCostType->findAll(
    "",
    "id, trip_cost_type_code, trip_cost_type_name, currency",
    "trip_cost_type_name",
    null,
    1,
    "id"
);
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
  $arrTripCost[$strTripID] = [];
  foreach ($arrTripCostType AS $strCostID => $arrCostDetail) {
    if (isset($arrTripCostSetting[$strCostID]) && $arrTripCostSetting[$strCostID]['include'] == 't') {
      $arrTripCost[$strTripID][] = $strCostID;
    }
  }
}
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
$strDataTripTypeID = getPostValue('dataTripType');
if ($strDataTripTypeID == "") {
  $arrID = $tblTripType->find("", "id", "", null, 1, "id");
  $strDataTripTypeID = $arrID['id'];
}
//generate form untuk select trip type
//trip type harus dipilih dulu supaya jenis2 trip allowance dapat ditentukan
$fFilter = new clsForm("formFilter", 4, "100%", "");
$fFilter->caption = strtoupper("trip type");
$fFilter->addSelect("", "dataTripType", getDataListTripType($strDataTripTypeID), "style='width:250px'", "", false);
$fFilter->addLiteral(
    "",
    "buttonShow",
    generateButton("btnShow", getWords("show"), "", "onclick = \"document.formFilter.submit()\"")
);
$fFilter->hasButton = false;
$formFilter = $fFilter->render();
// ------------------------------------------------------------------------------------------------------------------------------
if ($strDataTripTypeID != "") {
  $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, false, false);
  $myDataGrid->caption = getWords(strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name']))));
  $myDataGrid->pageSortBy = "grade_code";
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  $myDataGrid->setPageLimit("all");
  $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("grade code"), "grade_code", ['width' => '150'], ['nowrap' => ''])
  );
  foreach ($arrTripCost[$strDataTripTypeID] AS $strCostID) {
    //tampilan untuk datagrid di browser
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords(
                $arrTripCostType[$strCostID]['trip_cost_type_name']
            ) . " (" . $arrTripCostType[$strCostID]['currency'] . ")",
            "trip_cost_" . $strCostID,
            ['width' => '75'],
            ['align' => 'center'],
            false,
            false,
            "",
            ($bolCanEdit) ? "printQuota()" : "",
            "",
            false
        )
    );
    //tampilan untuk datagrid di excel
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords(
                $arrTripCostType[$strCostID]['trip_cost_type_name']
            ) . " (" . $arrTripCostType[$strCostID]['currency'] . ")",
            "trip_cost_" . $strCostID,
            ['width' => '75', 'style' => 'display:none'],
            ['align' => 'center', 'style' => 'display:none'],
            false,
            false,
            "",
            "",
            "",
            true
        )
    );
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
  $myDataGrid->strAdditionalHtml = generateHidden("dataTripType", $strDataTripTypeID, "");
  $myDataGrid->addButtonExportExcel(
      "Export Excel",
      $dataPrivilege['menu_name'] . ".xls",
      getWords($dataPrivilege['menu_name'])
  );
  $myDataGrid->getRequest();
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  $strSQLCOUNT = "SELECT COUNT(*) AS total  FROM hrd_salary_grade";
  $strSQL = "SELECT grade_code FROM hrd_salary_grade ";
  $tblTripCostPlatform = new cHrdTripCostPlatform();
  $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
  $dataset = $myDataGrid->getData($db, $strSQL);
  foreach ($dataset AS $strKey => $arrDetail) {
    $arrTripCostPlatform = $tblTripCostPlatform->findAll(
        "id_trip_type = " . $strDataTripTypeID . " AND grade_code = '" . $arrDetail['grade_code'] . "'",
        "id_trip_cost_type, amount",
        "",
        null,
        1,
        "id_trip_cost_type"
    );
    foreach ($arrTripCostType AS $strCostID => $arrCostDetail) {
      $dataset[$strKey]['trip_cost_' . $strCostID] = (isset($arrTripCostPlatform[$strCostID])) ? $arrTripCostPlatform[$strCostID]['amount'] : 0;
    }
  }
  //bind Datagrid with array dataset
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
} else {
  $DataGrid = "";
}
$strConfirmSave = getWords("do you want to save this entry?");
function printQuota($params)
{
  global $arrTripCostType;
  extract($params);
  $strCostID = substr($field, 10);
  return generateInput("detailQuota_" . $record['grade_code'] . "_" . $strCostID, $value);
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
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
    $arrKeys2['id_trip_type'][] = $strValue;
  }
  $tblHrdTripType = new cHrdTripType();
  $tblHrdTripTypeCostSetting = new cHrdTripTypeCostSetting();
  $tblHrdTripType->deleteMultiple($arrKeys);
  $tblHrdTripTypeCostSetting->deleteMultiple($arrKeys2);
  $myDataGrid->message = $tblHrdTripType->strMessage;
} //deleteData
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