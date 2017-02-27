<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_employee.php');
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
$tblEmployee = new cModel("hrd_employee", getWords("employee"));
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintAll']));
//---- INISIALISASI ----------------------------------------------------
$strHidden = "";
$intTotalData = 0; // default, tampilan dibatasi (paging)
$strWordsTreatmentTypeSetting = getWords("treatment type setting");
$strWordsQuotaSetting = getWords("quota setting");
$strWordsEmployeeQuotaList = getWords("employee quota list");
$strWordsInputMedicalClaim = getWords("input claim");
$strWordsMedicalClaimList = getWords("claim list");
$strWordsEmployeeMedicalReport = getWords(
    "employee medical report"
);  //----------------------------------------------------------------------
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $strKriteria = "";
  $arrData = [];
  $arrUserList = getAllUserInfo($db);//ambil semua info user]
  $arrData['dataDateFrom'] = (getPostValue('dataDateFrom') == "") ? date("Y-m") . "-01" : getPostValue('dataDateFrom');
  $arrData['dataDateThru'] = (getPostValue('dataDateThru') == "") ? date("Y-m-d") : getPostValue('dataDateThru');
  $arrData['dataClaimFrom'] = getPostValue('dataClaimFrom');
  $arrData['dataClaimThru'] = getPostValue('dataClaimThru');
  $intCurrPage = getPostValue('dataPage');
  $arrData['dataBank'] = getPostValue('dataBank');
  $arrData['dataRequestStatus'] = getPostValue('dataRequestStatus');
  // ------ AMBIL DATA KRITERIA -------------------------
  $arrData['dataEmployeeID'] = trim(getSessionValue('sessiondataEmployeeID'));
  $arrData['dataBranch'] = getSessionValue('sessiondataBranch');
  $arrData['dataPosition'] = getSessionValue('sessiondataPosition');
  $arrData['dataGrade'] = getSessionValue('sessiondataGrade');
  $arrData['dataStatus'] = getSessionValue('sessiondataEmployeeStatus');
  $arrData['dataActive'] = getSessionValue('sessiondataActive');
  $arrData['dataManagement'] = getSessionValue('sessiondataManagement');
  $arrData['dataDivision'] = getSessionValue('sessiondataDivision');
  $arrData['dataDepartment'] = getSessionValue('sessiondataDepartment');
  $arrData['dataSection'] = getSessionValue('sessiondataSection');
  $arrData['dataSubsection'] = getSessionValue('sessiondataSubsection');
  $arrData['dataEmployeeID'] = getPostValue('dataEmployeeID');
  $arrData['dataBranch'] = getPostValue('dataBranch');
  $arrData['dataPosition'] = getPostValue('dataPosition');
  $arrData['dataGrade'] = getPostValue('dataGrade');
  $arrData['dataStatus'] = getPostValue('sessiondataEmployeeStatus');
  $arrData['dataActive'] = getPostValue('dataActive');
  $arrData['dataManagement'] = getPostValue('dataManagement');
  $arrData['dataDivision'] = getPostValue('dataDivision');
  $arrData['dataDepartment'] = getPostValue('dataDepartment');
  $arrData['dataSection'] = getPostValue('dataSection');
  $arrData['dataSubsection'] = getPostValue('dataSubsection');
  // default selalu ambil yang aktif saja
  // simpan di session
  $_SESSION['sessiondataEmployeeID'] = $arrData['dataEmployeeID'];
  $_SESSION['sessiondataBranch'] = $arrData['dataBranch'];
  $_SESSION['sessiondataPosition'] = $arrData['dataPosition'];
  $_SESSION['sessiondataGrade'] = $arrData['dataGrade'];
  $_SESSION['sessiondataEmployeeStatus'] = $arrData['dataStatus'];
  $_SESSION['sessiondataActive'] = $arrData['dataActive'];
  $_SESSION['sessiondataManagement'] = $arrData['dataManagement'];
  $_SESSION['sessiondataDivision'] = $arrData['dataDivision'];
  $_SESSION['sessiondataDepartment'] = $arrData['dataDepartment'];
  $_SESSION['sessiondataSection'] = $arrData['dataSection'];
  $_SESSION['sessiondataSubsection'] = $arrData['dataSubsection'];
  if (!is_numeric($intCurrPage)) {
    $intCurrPage = 1;
  }
  scopeData(
      $arrData['dataEmployeeID'],
      $arrData['dataSubSection'],
      $arrData['dataSection'],
      $arrData['dataDepartment'],
      $arrData['dataDivision'],
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  $strReadonly = (scopeCBDataEntry(
      $arrData['dataEmployeeID'],
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  )) ? "readonly" : "";
  if ($arrData['dataRequestStatus'] != "") {
    $strKriteria .= "AND \"status\" = '" . $arrData['dataRequestStatus'] . "' ";
  }
  if ($arrData['dataBank'] != "") {
    $strKriteria .= "AND \"bank2_code\" = '" . $arrData['dataBank'] . "' ";
  }
  if ($arrData['dataBranch'] != "") {
    $strKriteria .= "AND \"branch_code\" = '" . $arrData['dataBranch'] . "' ";
  }
  if ($arrData['dataPosition'] != "") {
    $strKriteria .= "AND \"position_code\" = '" . $arrData['dataPosition'] . "' ";
  }
  if ($arrData['dataStatus'] != "") {
    $strKriteria .= "AND \"employee_status\" = '" . $arrData['dataStatus'] . "' ";
  }
  if ($arrData['dataActive'] != "") {
    $strKriteria .= "AND active = '" . $arrData['dataActive'] . "' ";
  }
  if ($arrData['dataEmployeeID'] != "") {
    $strKriteria .= "AND upper(\"employee_id\") like '%" . $arrData['dataEmployeeID'] . "%' ";
  }
  if ($arrData['dataManagement'] != "") {
    $strKriteria .= "AND \"management_code\" = '" . $arrData['dataManagement'] . "' ";
  }
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND \"division_code\" = '" . $arrData['dataDivision'] . "' ";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND \"department_code\" = '" . $arrData['dataDepartment'] . "' ";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND \"section_code\" = '" . $arrData['dataSection'] . "' ";
  }
  if ($arrData['dataSubsection'] != "") {
    $strKriteria .= "AND \"sub_section_code\" = '" . $arrData['dataSubsection'] . "' ";
  }
  if ($arrData['dataGrade'] != "") {
    $strKriteria .= "AND \"grade_code\" = '" . $arrData['dataGrade'] . "' ";
  }
  $strKriteria .= $strKriteriaCompany;
  // generate data hidden input dan element form input
  $intDefaultWidth = 30;
  $fFilter = new clsForm("formFilter", 3, "100%", "");
  $fFilter->caption = strtoupper("filter data");
  $fFilter->addInput(
      getWords("date from"),
      "dataDateFrom",
      $arrData['dataDateFrom'],
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $fFilter->addInput(
      getWords("date thru"),
      "dataDateThru",
      $arrData['dataDateThru'],
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $fFilter->addInputAutoComplete(
      getWords("employee ID"),
      "dataEmployeeID",
      getDataEmployee($arrData['dataEmployeeID']),
      "style=width:$strDefaultWidthPx " . $strReadonly,
      "string",
      false
  );
  $fFilter->addLabelAutoComplete("", "dataEmployeeID", "");
  $fFilter->addInput(
      getWords("minimum cost filter"),
      "dataClaimFrom",
      $arrData['dataClaimFrom'],
      "",
      "numeric",
      false,
      true,
      true
  );
  $fFilter->addInput(
      getWords("maximum cost filter"),
      "dataClaimThru",
      $arrData['dataClaimThru'],
      "",
      "numeric",
      false,
      true,
      true
  );
  $fFilter->addSelect(
      getWords("request status"),
      "dataRequestStatus",
      getDataListRequestStatus(
          $arrData['dataRequestStatus'],
          true,
          ["value" => "", "text" => "", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  //$fFilter->addInputAutoComplete(getWords("employee ID"), "dataEmployeeID", getDataEmployee($arrData['dataEmployeeID']), array("size" => 25), "string", false);
  $fFilter->addSelect(
      getWords("branch"),
      "dataBranch",
      getDataListBranch($arrData['dataBranch'], true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $fFilter->addSelect(
      getWords("level"),
      "dataPosition",
      getDataListPosition($arrData['dataPosition'], true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $fFilter->addSelect(
      getWords("grade"),
      "dataGrade",
      getDataListSalaryGrade($arrData['dataGrade'], true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $fFilter->addSelect(
      getWords("status"),
      "dataEmployeeStatus",
      getDataListEmployeeStatus(
          $arrData['dataStatus'],
          true,
          ["value" => "", "text" => "", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $fFilter->addSelect(
      getWords("active"),
      "dataActive",
      getDataListEmployeeActive(
          $arrData['dataActive'],
          true,
          ["value" => "", "text" => "", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $fFilter->addSelect(
      getWords("bank"),
      "dataBank",
      getDataListBank($arrData['dataBank'], true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $fFilter->addLiteral("", "", "");
  $fFilter->addSelect(
      getWords("company"),
      "dataCompany",
      getDataListCompany($strDataCompany, $bolCompanyEmptyOption, $arrCompanyEmptyData, $strKriteria2),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $fFilter->addSelect(
      getWords("division"),
      "dataDivision",
      getDataListDivision($arrData['dataDivision'], true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['division'] == "")
  );
  $fFilter->addSelect(
      getWords("department "),
      "dataDepartment",
      getDataListDepartment($arrData['dataDepartment'], true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['department'] == "")
  );
  $fFilter->addSelect(
      getWords("section"),
      "dataSection",
      getDataListSection($arrData['dataSection'], true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['section'] == "")
  );
  $fFilter->addSelect(
      getWords("subsection"),
      "dataSubsection",
      getDataListSubSection($arrData['dataSubsection'], true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['sub_section'] == "")
  );
  $fFilter->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "doNothing()");
  $formFilter = $fFilter->render();
  if ($bolCanView) {
    $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%");
    $myDataGrid->caption = getWords($dataPrivilege['menu_name']);
    $DataGrid = showData(
        $strKriteria,
        $arrData['dataDateFrom'],
        $arrData['dataDateThru'],
        $arrData['dataClaimFrom'],
        $arrData['dataClaimThru']
    );
  } else {
    showError("view_denied");
  }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
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
//end of main program
//--------------------------
function showData($strKriteria, $strDataDateFrom, $strDataDateThru, $strDataClaimFrom, $strDataClaimThru)
{
  global $tblEmployee;
  global $bolPrint;
  global $bolCanDelete;
  global $bolCanEdit;
  global $intTotalData;
  global $dataPrivilege;
  global $myDataGrid;
  global $arrData;
  $db = new CdbClass;
  $db->connect();
  $myDataGrid->strAdditionalHtml = "";
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column(
          "chkID",
          "id",
          ['width' => 30],
          ['align' => 'center', 'nowrap' => ''],
          false,
          false,
          "",
          "",
          "string",
          false
      )
  );
  $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => 30], ['nowrap' => '']));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          "Employee ID",
          "employee_id",
          ['width' => 100],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          true,
          15
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("employee name"),
          "employee_name",
          "",
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          true,
          35
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("bank account"),
          "bank2_account",
          ["width" => 150],
          ["align" => "center"],
          true,
          true,
          "",
          "",
          "string",
          true,
          25
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("bank"),
          "bank_name",
          ["width" => 150],
          ["align" => "center"],
          true,
          true,
          "",
          "",
          "string",
          true,
          25
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("bank account name"),
          "bank2_account_name",
          ["width" => 150],
          ["align" => "center"],
          true,
          true,
          "",
          "",
          "string",
          true,
          35
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("total claim"),
          "total_claim",
          ["width" => 150],
          ["align" => "right"],
          true,
          true,
          "",
          "formatNumber()",
          "numeric",
          true,
          15
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("branch code"),
          "branch_code",
          ["width" => 100],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          true,
          10
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("branch"),
          "branch_name",
          ["width" => 100],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          true,
          10
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("division code"),
          "division_code",
          ["width" => 100],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("department code"),
          "department_code",
          ["width" => 150],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("section code"),
          "section_code",
          ["width" => 100],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          false
      )
  );
  $myDataGrid->addButtonExportExcel("Export Excel", "medical_report.xls", getWords($dataPrivilege['menu_name']));
  foreach ($arrData AS $key => $value) {
    $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
  }
  $myDataGrid->getRequest();
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  $strSQL = "
      SELECT t1.id, employee_id, employee_name, bank_name, division_code, 
      department_code, section_code, t1.branch_code, branch_name, bank2_account, bank2_account_name, SUM(approved_cost) AS total_claim 
      FROM hrd_employee AS t1 LEFT JOIN 
      (hrd_medical_claim AS t2 LEFT JOIN hrd_medical_claim_master AS t3 
      ON t2.id_master = t3.id AND claim_date between '$strDataDateFrom' AND '$strDataDateThru')
      ON t1.id = t3.id_employee
      LEFT JOIN hrd_bank AS t4 ON t1.bank2_code = t4.bank_code
      LEFT JOIN hrd_branch AS t5 ON t1.branch_code = t5.branch_code
      WHERE 1=1 
      ";
  if ($strKriteria != "") {
    $strSQL .= "$strKriteria ";
  }
  $strSQL .= " GROUP BY t1.id, employee_id, employee_name, bank2_account, bank2_account_name, bank_name, division_code , department_code , section_code , t1.branch_code , branch_name ";
  $strSQLCOUNT = "SELECT COUNT(*) AS total FROM ($strSQL) AS t0 WHERE 1=1 ";
  $strSQL = "SELECT * FROM ($strSQL) AS t0 WHERE 1=1 ";
  if ($strDataClaimFrom != "") {
    $strSQLCOUNT .= "AND total_claim >= $strDataClaimFrom ";
    $strSQL .= "AND total_claim >= $strDataClaimFrom ";
  }
  if ($strDataClaimThru != "") {
    $strSQL .= "AND total_claim <= $strDataClaimThru ";
  }
  $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
  $dataset = $myDataGrid->getData($db, $strSQL);
  $myDataGrid->bind($dataset);
  return $myDataGrid->render();
}

function printStatus($params)
{
  extract($params);
  if ($value == 1) {
    return getWords('active');
  } else {
    return getWords('not active');
  }
}

function getCSSClassName($flag, $bolOrphan = false)
{
  if ($bolOrphan) {
    $strClass = "class=\"bgDenied\"";
    $strDisabled = "";
  } else {
    switch ($flag) {
      case 0 :
        $strClass = "";
        break;
      case 1 :
        $strClass = "class=\"bgNewData\"";
        break;
      case 2 :
        $strClass = "class=\"bgCheckedData\"";
        break;
      case 3 : // ditolak
        $strClass = "class=\"bgDenied\"";
        break;
      default :
        $strClass = "";
        break;
    }
  }
  return $strClass;
}

function doNothing()
{
}

?>
