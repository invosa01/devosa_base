<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/model/model.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('form_object.php');
//include_once('../includes/krumo/class.krumo.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$tblEmployee = new cModel("hrd_employee", getWords("employee"));
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintAll']) || isset($_REQUEST['btnExportXLS']) || isset($_REQUEST['btnExcelAll']));
//$bolFull = (isset($_REQUEST['dataFull'])) ? true : false;
$bolLimit = true;//(getRequestValue('dataLimit', 0) == 1);
//---- INISIALISASI ----------------------------------------------------
$strWordsEmployeeID = getWords("employee id");
$strWordsName = getWords("name");
$strWordsNick = getWords("nick");
$strWordsEmployeeStatus = getWords("employee status");
$strWordsLevel = getWords("level");
$strWordsBranch = getWords("branch");
$strWordsActive = getWords("active");
$strWordsCompany = getWords("company");
$strWordsManagement = getWords("management");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSubDepartment = getWords("sub department");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("sub section");
$strWordsGrade = getWords("grade");
$strWordsCurrency = getWords("salary currency");
$strWordsSex = getWords("sex");
$strWordsAge = getWords("age");
$strWordsPosition = getWords("position");
$strWordsFunctional = getWords("functional");
$strWordsCity = getWords("city");
$strWordsMajor = getWords("major");
$strWordsFullView = getWords("full view");
$strWordsSearchEmployee = getWords("search employee");
$strWordsSimpleResume = getWords("simple resume");
$strWordsReport = getWords("report");
$strWordsSearch = getWords("search");
$strWordsShowAll = getWords("show all");
$strWordsExportExcelAll = getWords("export excel all");
$strWordsExportExcel = getWords("export excel");
$pageSubMenu = employeeDataSubmenu($strWordsSearchEmployee);
$strHidden = "";
$intTotalData = 0; // default, tampilan dibatasi (paging)
//----------------------------------------------------------------------
//class inheritance from cDataGrid
class cDataGrid2 extends cDataGrid
{

  /*you can inherit this function to created your own TR class or style*/
  function printOpeningRow($intRows, $rowDb)
  {
    $strResult = "";
    $strClass = getCSSClassName($rowDb['flag'], false);
    if (($intRows % 2) == 0) {
      $strResult .= "
            <tr $strClass valign=\"top\">";
    } else {
      $strResult .= "
            <tr $strClass valign=\"top\">";
    }
    return $strResult;
  }
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);//ambil semua info user]
  // ------ AMBIL DATA KRITERIA -------------------------
  $strDataEmployeeID = trim(getSessionValue('sessiondataEmployeeID'));
  $strDataName = trim(getSessionValue('sessiondataName'));
  $strDataQuery = getSessionValue('sessiondataQuery');
  $strDataBranch = trim(getSessionValue('sessiondataBranch'));
  $strDataPosition = trim(getSessionValue('sessiondataPosition'));
  $strDataStatus = getSessionValue('sessiondataEmployeeStatus');
  $strDataActive = getSessionValue('sessiondataActive');
  $strDataFull = getSessionValue('sessiondataFull');
  $strDataManagement = getSessionValue('sessiondataManagement');
  $strDataDivision = getSessionValue('sessiondataDivision');
  $strDataDepartment = getSessionValue('sessiondataDepartment');
  $strDataSubDepartment = getSessionValue('sessiondataSubDepartment');
  $strDataSection = getSessionValue('sessiondataSection');
  $strDataSubSection = getSessionValue('sessiondataSubSection');
  $strDataGrade = getSessionValue('sessiondataGrade');
  $strDataCurrency = getSessionValue('sessiondataCurrency');
  $strDataGender = getSessionValue('sessiondataGender');
  $strDataAgeL = getSessionValue('sessiondataAgeL');
  $strDataAgeU = getSessionValue('sessiondataAgeU');
  $strDataCity = getSessionValue('sessiondataCity');
  $strDataMajor = getSessionValue('sessiondataMajor');
  $intCurrPage = getSessionValue('sessionPageEmployee', 1);
  $strDataCompany = getSessionValue('sessiondataCompany');
  if (isset($_REQUEST['dataEmployeeID'])) {
    $strDataEmployeeID = trim($_REQUEST['dataEmployeeID']);
  }
  if (isset($_REQUEST['dataName'])) {
    $strDataName = trim($_REQUEST['dataName']);
  }
  if (isset($_REQUEST['dataQuery'])) {
    $strDataQuery = $_REQUEST['dataQuery'];
  }
  if (isset($_REQUEST['dataBranch'])) {
    $strDataBranch = trim($_REQUEST['dataBranch']);
  }
  if (isset($_REQUEST['dataPosition'])) {
    $strDataPosition = trim($_REQUEST['dataPosition']);
  }
  if (isset($_REQUEST['dataEmployeeStatus'])) {
    $strDataStatus = $_REQUEST['dataEmployeeStatus'];
  }
  if (isset($_REQUEST['dataActive'])) {
    $strDataActive = $_REQUEST['dataActive'];
  }
  if (isset($_REQUEST['dataManagement'])) {
    $strDataManagement = $_REQUEST['dataManagement'];
  }
  if (isset($_REQUEST['dataDivision'])) {
    $strDataDivision = $_REQUEST['dataDivision'];
  }
  if (isset($_REQUEST['dataDepartment'])) {
    $strDataDepartment = $_REQUEST['dataDepartment'];
  }
  if (isset($_REQUEST['dataSubDepartment'])) {
    $strDataSubDepartment = $_REQUEST['dataSubDepartment'];
  }
  if (isset($_REQUEST['dataSection'])) {
    $strDataSection = $_REQUEST['dataSection'];
  }
  if (isset($_REQUEST['dataSubSection'])) {
    $strDataSubSection = $_REQUEST['dataSubSection'];
  }
  if (isset($_REQUEST['dataCurrency'])) {
    $strDataCurrency = $_REQUEST['dataCurrency'];
  }
  if (isset($_REQUEST['dataGrade'])) {
    $strDataGrade = $_REQUEST['dataGrade'];
  }
  if (isset($_REQUEST['dataGender'])) {
    $strDataGender = $_REQUEST['dataGender'];
  }
  if (isset($_REQUEST['dataAgeL'])) {
    $strDataAgeL = $_REQUEST['dataAgeL'];
  }
  if (isset($_REQUEST['dataAgeU'])) {
    $strDataAgeU = $_REQUEST['dataAgeU'];
  }
  if (isset($_REQUEST['dataCity'])) {
    $strDataCity = $_REQUEST['dataCity'];
  }
  if (isset($_REQUEST['dataMajor'])) {
    $strDataMajor = $_REQUEST['dataMajor'];
  }
  if (isset($_REQUEST['dataPage'])) {
    $intCurrPage = $_REQUEST['dataPage'];
  }
  if (isset($_REQUEST['dataFull'])) {
    $strDataFull = $_REQUEST['dataFull'];
  } else {
    $strDataFull = 0;
  }
  if (isset($_REQUEST['dataCompany'])){
      $strDataCompany = $_REQUEST['dataCompany'];
  }
  /*$employeeBasicSalary = null;
  if ($strDataFull) {
    $employeeBasicSalary = getEmployeeAllowanceBySalarySet($db, 'basic_salary');
  }*/
  $strDataFunctionalPosition = (isset($_REQUEST['dataFunctionalPosition'])) ? $_REQUEST['dataFunctionalPosition'] : "";
  // default selalu ambil yang aktif saja
  // simpan di session
  $_SESSION['sessiondataEmployeeID'] = $strDataEmployeeID;
  $_SESSION['sessiondataName'] = $strDataName;
  $_SESSION['sessiondataQuery'] = $strDataQuery;
  $_SESSION['sessiondataBranch'] = $strDataBranch;
  $_SESSION['sessiondataPosition'] = $strDataPosition;
  $_SESSION['sessiondataEmployeeStatus'] = $strDataStatus;
  $_SESSION['sessiondataFull'] = $strDataFull;
  $_SESSION['sessiondataActive'] = $strDataActive;
  $_SESSION['sessiondataManagement'] = $strDataManagement;
  $_SESSION['sessiondataDivision'] = $strDataDivision;
  $_SESSION['sessiondataDepartment'] = $strDataDepartment;
  $_SESSION['sessiondataSubDepartment'] = $strDataSubDepartment;
  $_SESSION['sessiondataSection'] = $strDataSection;
  $_SESSION['sessiondataSubSection'] = $strDataSubSection;
  $_SESSION['sessiondataCurrency'] = $strDataCurrency;
  $_SESSION['sessiondataGrade'] = $strDataGrade;
  $_SESSION['sessiondataAgeL'] = $strDataAgeL;
  $_SESSION['sessiondataAgeU'] = $strDataAgeU;
  $_SESSION['sessionCityList'] = $strDataCity;
  $_SESSION['sessionMajorList'] = $strDataMajor;
  $_SESSION['sessionPageEmployee'] = $intCurrPage;
  $_SESSION['sessiondataCompany'] = $strDataCompany;
  if (!is_numeric($intCurrPage)) {
    $intCurrPage = 1;
  }
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  //  if (isset($_REQUEST['btnShowAll']) || isset($_REQUEST['btnPrintAll']) || isset($_REQUEST['btnExcelAll']) || //isset($_REQUEST['btnShowAlert']))
  // {
  scopeData(
      $strDataEmployeeID,
      $strDataSubSection,
      $strDataSection,
      $strSubDepartment,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  if ($strDataActive != "") {
    $strKriteria .= "AND active = '$strDataActive' ";
  }
  if ($strDataStatus != "") {
    $strKriteria .= "AND \"employee_status\" = '$strDataStatus' ";
  }
  if ($strDataGender != "") {
    $strKriteria .= "AND \"gender\" = '$strDataGender' ";
  }
  if ($strDataAgeL != "") {
    $strDataAgeL = floor($strDataAgeL);
    $strKriteria .= "AND (EXTRACT(YEAR FROM AGE(birthday))) >= '$strDataAgeL'";
  }
  if ($strDataAgeU != "") {
    $strDataAgeU = floor($strDataAgeU);
    $strKriteria .= "AND (EXTRACT(YEAR FROM AGE(birthday))) <= '$strDataAgeU'";
  }
  if ($strDataEmployeeID != "") {
    $strKriteria .= "AND upper(\"employee_id\") like '%" . strtoupper($strDataEmployeeID) . "%' ";
  }
  if ($strDataName != "") {
    $strKriteria .= "AND (upper(\"employee_name\") like '%" . strtoupper($strDataName) . "%' ";
    $strKriteria .= "OR upper(\"nickname\") like '%" . strtoupper($strDataName) . "%') ";
  }
  if ($strDataQuery != "") {
    $strKriteria .= " $strDataQuery ";
  }

  if ($strDataGrade != "") {
    $strKriteria .= "AND \"grade_code\" = '$strDataGrade' ";
  }
  if ($strDataCurrency != "") {
    $strKriteria .= "AND \"salary_currency\" = '$strDataCurrency' ";
  }
  if ($strDataCity != "") {
    $strKriteria .= "AND \"primary_city\" = '$strDataCity' ";
  }
  if ($strDataMajor != "") {
    $strKriteria .= "AND \"major_code\" = '$strDataMajor' ";
  }
  if ($strDataCompany != ""){
    $strKriteria .= "AND \"id_company\" = '$strDataCompany' ";
  }
  if ($strDataFull != 0) {
    $bolFull = true;
  }
  $strKriteria .= $strKriteriaCompany;
  $strCriteria = $strKriteria;
  if ($strDataBranch != "") {
    $strKriteria .= "AND \"branch_code\" = '$strDataBranch' ";
    $strCriteria .= "AND t0.\"branch_code\" = '$strDataBranch' ";
  }
  if ($strDataPosition != "") {
    $strKriteria .= "AND \"position_code\" = '$strDataPosition' ";
    $strCriteria .= "AND t0.\"position_code\" = '$strDataPosition' ";
  }
  if ($strDataManagement != "") {
    $strKriteria .= "AND \"management_code\" = '$strDataManagement' ";
    $strCriteria .= "AND t0.\"management_code\" = '$strDataManagement' ";
  }
  if ($strDataDivision != "") {
    $strKriteria .= "AND \"division_code\" = '$strDataDivision' ";
    $strCriteria .= "AND t0.\"division_code\" = '$strDataDivision' ";
  }
  if ($strDataDepartment != "") {
    $strKriteria .= "AND \"department_code\" = '$strDataDepartment' ";
    $strCriteria .= "AND t0.\"department_code\" = '$strDataDepartment' ";
  }
  if ($strDataSubDepartment != "") {
    $strKriteria .= "AND \"sub_department_code\" = '$strDataSubDepartment' ";
    $strCriteria .= "AND t0.\"sub_department_code\" = '$strDataSubDepartment' ";
  }
  if ($strDataSection != "") {
    $strKriteria .= "AND \"section_code\" = '$strDataSection' ";
    $strCriteria .= "AND t0.\"section_code\" = '$strDataSection' ";
  }
  if ($strDataSubSection != "") {
    $strKriteria .= "AND \"sub_section_code\" = '$strDataSubSection' ";
    $strCriteria .= "AND t0.\"sub_section_code\" = '$strDataSubSection' ";
  }
  if ($strDataFunctionalPosition != "") {
    $strKriteria .= "AND functional_code = '$strDataFunctionalPosition' ";
    $strCriteria .= "AND t0.functional_code = '$strDataFunctionalPosition' ";
  }
  // }
  if ($bolCanView) {
    if (isset($_REQUEST['btnExportXLS']) || isset($_REQUEST['btnExcelAll'])) {
      $isExport = true;
    } else {
      $isExport = false;
    }
    //class initialization
    $DEFAULTPAGELIMIT = getSetting("rows_per_page");
    if (!is_numeric($DEFAULTPAGELIMIT)) {
      $DEFAULTPAGELIMIT = 50;
    }
    if ($bolPrint) {
      $myDataGrid = new cDataGrid2("formData", "DataGrid1", "100%", "100%", false, false, false, false);
    } else {
      $myDataGrid = new cDataGrid2("formData", "DataGrid1", "100%", "100%", $bolLimit, true, true);
      $myDataGrid->caption = getWords("list of employee");
    }
    //$myDataGrid->disableFormTag();
    //$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->pageSortBy = "employee_name";
    //end of class initialization
    $DataGrid = showData($strCriteria, $strKriteria, $bolLimit, $bolFull, $isExport);
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  // generate data hidden input dan element form input
  $intDefaultHeight = 3;
  //$f = new clsForm("formFilter", 3, "100%", "");
  //$f->addInputAutoComplete(getWords("employee ID"), "dataEmployee", getDataEmployee($strDataEmployee), "style='width:250px' ". $strReadonly, "string", true);
  //$strInputDataEmployeeID = $f->render();
  //added by adnan untuk outcomplete
  if ($_SESSION['sessionUserRole'] >= ROLE_SUPERVISOR) {
    $readonly2 = '';
  } else {
    $readonly2 = 'readonly';
  }
  $strAction = "onFocus = \"AC_kode = 'dataEmployeeID';AC_nama='dataName';\" ";
  $strInputDataEmployeeID = "  <input class=\"form-control\" type=\"text\" maxlength=\"50\" name=\"dataEmployeeID\" id=\"dataEmployeeID\" $readonly2 value=\"$strDataEmployeeID\" placeholder=\"Employee ID\" $strAction>";
  $strInputDataName = "<input type=\"text\" class=\"form-control\" name=\"dataName\" id=\"dataName\" value=\"$strDataName\" style=\"width:$strDefaultWidthPx\" placeholder=\"Employee Name\" />";
  $strInputDataBranch = getBranchList(
      $db,
      "dataBranch",
      $strDataBranch,
      $strEmptyOption,
      "",
      " style=\"width:$strDefaultWidthPx\""
  );
  $strInputDataPosition = getPositionList(
      $db,
      "dataPosition",
      $strDataPosition,
      $strEmptyOption,
      "",
      " style=\"width:$strDefaultWidthPx\""
  );
  /*$strInputDataPosition = getPositionList(
      $db,
      "dataPosition",
      $strDataPosition,
      $strEmptyOption,
      $strCriteriaPosition,
      " style=\"width:$strDefaultWidthPx\""
  );*/
  $strInputDataStatus = getEmployeeStatusList(
      "dataEmployeeStatus",
      $strDataStatus,
      $strEmptyOption,
      " style=\"width:$strDefaultWidthPx\""
  );
  $strInputDataActive = getEmployeeActiveList(
      "dataActive",
      $strDataActive,
      $strEmptyOption,
      " style=\"width:$strDefaultWidthPx\""
  );
  //handle user company-access-right
  $strInputCompany = getCompanyList(
      $db,
      "dataCompany",
      $strDataCompany,
      $strEmptyOption2,
      $strKriteria2,
      "style=\"width:$strDefaultWidthPx\" "
  );
  $strInputDataManagement = getManagementList(
      $db,
      "dataManagement",
      $strDataManagement,
      $strEmptyOption,
      "",
      " style=\"width:$strDefaultWidthPx\"",
      false,
      "dataDepartment",
      true
  );
  $strInputDataDivision = getDivisionList(
      $db,
      "dataDivision",
      $strDataDivision,
      $strEmptyOption,
      "",
      " style=\"width:$strDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['division'],
      false,
      "dataDepartment",
      true
  );
  $strInputDataDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $strDataDepartment,
      $strEmptyOption,
      "",
      " style=\"width:$strDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['department'],
      false,
      "dataSubDepartment",
      true
  );
  $strInputDataSubDepartment = getSubDepartmentList(
      $db,
      "dataSubDepartment",
      $strDataSubDepartment,
      $strEmptyOption,
      "",
      " style=\"width:$strDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['sub_department'],
      false,
      "dataSection",
      true
  );
  $strInputDataSection = getSectionList(
      $db,
      "dataSection",
      $strDataSection,
      $strEmptyOption,
      "",
      " style=\"width:$strDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['section']
  );
  $strInputDataSubSection = getSubSectionList(
      $db,
      "dataSubSection",
      $strDataSubSection,
      $strEmptyOption,
      "",
      " style=\"width:$strDefaultWidthPx\"  " . $ARRAY_DISABLE_GROUP['sub_section']
  );
  $strInputDataGrade = getSalaryGradeList(
      $db,
      "dataGrade",
      $strDataGrade,
      $strEmptyOption,
      "",
      " style=\"width:$strDefaultWidthPx\""
  );
  $strInputDataCurrency = getComboFromArray(
      $ARRAY_CURRENCY,
      "dataCurrency",
      $strDataCurrency,
      $strEmptyOption,
      " style=\"width:$strDefaultWidthPx\""
  );
  $strInputDataGender = getComboFromArray(
      $ARRAY_GENDER,
      "dataGender",
      $strDataGender,
      $strEmptyOption,
      " style=\"width:$strDefaultWidthPx\""
  );
  $strInputdataFunctionalPosition = getFunctionalPositionList(
      $db,
      "dataFunctionalPosition",
      $strDataFunctionalPosition,
      $strEmptyOption,
      "",
      " style=\"width:$strDefaultWidthPx\""
  );
  $strAgeWidthPx = $strDefaultWidthPx / 2 - 6.5;
  $strAgeWidthPx .= "px";
  $strInputDataAgeL = "<input type=\"text\" class=\"form-control pull-left\" name=\"dataAgeL\" id=\"dataAgeL\" value=\"$strDataAgeL\" style=\"width:$strAgeWidthPx\">";
  $strInputDataAgeU = "<input type=\"text\" class=\"form-control pull-left\" name=\"dataAgeU\" id=\"dataAgeU\" value=\"$strDataAgeU\" style=\"width:$strAgeWidthPx\">";
  // $strInputDataPosition = getPositionList($db,"dataPosition", $strDataCity, $strEmptyOption, ""," style=\"width:$strDefaultWidthPx\"");
  // $strInputDataFunctional = getFunctionalPositionList($db,"dataFunctional", $strDataCity, $strEmptyOption, ""," style=\"width:$strDefaultWidthPx\"");
  $strInputDataCity = getCityList(
      $db,
      "dataCity",
      $strDataCity,
      $strEmptyOption,
      "",
      " style=\"width:$strDefaultWidthPx\""
  );
  $strInputDataMajor = getMajorList(
      $db,
      "dataMajor",
      $strDataMajor,
      $strEmptyOption,
      "",
      " style=\"width:$strDefaultWidthPx\""
  );
  //text query
  //    $strInputDataQuery = "<input type=\"textarea\" class=\"form-control\" name=\"dataQuery\" id=\"dataQuery\" value=\"$strDataQuery\" style=\"width:$strDefaultWidthPx\" placeholder=\"\" />";
  $strInputDataQuery = "<textarea name=\"dataQuery\" class=\"form-control\" id=\"dataQuery\" style=\"width:$strDefaultWidthPx\" style=\"width:$strDefaultWidthPx\">$strDataQuery</textarea>";
  if ($bolFull) {
    $strInputViewType = "<div class=\"checkbox\"><label><input class=\"checkbox-inline\" type=\"checkbox\" name=\"dataFull\" id=\"dataFull\" value=\"1\" checked onClick=\"checkFull(this.checked)\"></label></div>";
    $strHidden .= "<input id=\"dataFullHide\" type=hidden name='dataFull' value=\"1\">";
  } else {
    $strInputViewType = "<div class=\"checkbox\"><label><input class=\"checkbox-inline\" type=\"checkbox\" name=\"dataFull\"' id=\"dataFull\" value=\"1\" onClick=\"checkFull(this.checked)\"></label></div>";
    $strHidden .= "<input id=\"dataFullHide\" type=hidden name='dataFull' value=\"0\">";
  }
  $strHidden .= "<input type=hidden name=dataEmployeeID value=\"$strDataEmployeeID\">";
  $strHidden .= "<input type=hidden name=dataName       value=\"$strDataName\">";
  $strHidden .= "<input type=hidden name=dataQuery       value=\"$strDataQuery\">";
  $strHidden .= "<input type=hidden name=dataBranch   value=\"$strDataBranch\">";
  $strHidden .= "<input type=hidden name=dataPosition   value=\"$strDataPosition\">";
  $strHidden .= "<input type=hidden name=dataEmployeeStatus value=\"$strDataStatus\">";
  $strHidden .= "<input type=hidden name=dataActive     value=\"$strDataActive\">";
  $strHidden .= "<input type=hidden name=dataManagement value=\"$strDataManagement\">";
  $strHidden .= "<input type=hidden name=dataDivision value=\"$strDataDivision\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  $strHidden .= "<input type=hidden name=dataCompany    value=\"$strDataCompany\">";
  //$strHidden .= "<input type=hidden name=dataFull   value=\"$strDataFull\">";
  $strHidden .= "<input type=hidden name=dataSection    value=\"$strDataSection\">";
  $strHidden .= "<input type=hidden name=dataSubSection value=\"$strDataSubSection\">";
  $strHidden .= "<input type=hidden name=dataFunctionalPosition   value=\"$strDataFunctionalPosition\">";
  $strHidden .= "<input type=hidden name=dataCurrency      value=\"$strDataCurrency\">";
  $strHidden .= "<input type=hidden name=dataGrade      value=\"$strDataGrade\">";
  $strHidden .= "<input type=hidden name=dataGender     value=\"$strDataGender\">";
  $strHidden .= "<input type=hidden name=dataAgeL     value=\"$strDataAgeL\">";
  $strHidden .= "<input type=hidden name=dataAgeU     value=\"$strDataAgeU\">";
  $strHidden .= "<input type=hidden name=dataCity        value=\"$strDataCity\">";
  $strHidden .= "<input type=hidden name=dataMajor        value=\"$strDataMajor\">";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$strPageDesc = 'Employee Data Management';
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
if ($bolPrint) {
  $strMainTemplate = getTemplate("employee_search_print.html");
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//end of main program
//--------------------------
function showData($strCriteriaMain, $strCriteriaOther, $bolLimit = true, $isFullView = false, $isExport = false)
{
  global $tblEmployee;
  global $bolPrint;
  global $bolCanDelete;
  global $bolCanEdit;
  global $intTotalData;
  global $myDataGrid;
  //global $employeeBasicSalary;
  $db = new CdbClass;
  $db->connect();
  $bolUpdateOnly = (isset($_REQUEST['btnShowAlert']));
  if ($bolUpdateOnly) {
    $bolLimit = false;
  }
  $intdataFlag = (isset($_REQUEST['dataStatus'])) ? $_REQUEST['dataStatus'] : -1;
  if (!$bolPrint && ($bolCanEdit || $bolCanDelete)) {
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID", "id", ['rowspan' => 2, 'width' => 30], ['align' => 'center', 'nowrap' => ''])
    );
  }
  $myDataGrid->addColumnNumbering(
      new DataGrid_Column(getWords("No"), "", ['rowspan' => 2, 'width' => 30], ['nowrap' => ''])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          strtoupper(getWords("empl.id")),
          "employee_id",
          ['rowspan' => 2, 'width' => 70],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "printResumeLink()",
          "string",
          true,
          12,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("employee name"),
          "employee_name",
          ["rowspan" => 2],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "printEmployeeName()",
          "string",
          true,
          35
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          strtoupper("npwp"),
          "npwp",
          ["rowspan" => 2, "width" => 80],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          true,
          12
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("sex"),
          "gender",
          ["rowspan" => 2, "width" => 30],
          ["align" => "center"],
          true,
          true,
          "",
          "printGender()",
          "string",
          true,
          6
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("age"),
          "umur",
          ["rowspan" => 2, "width" => 30],
          ["align" => "right"],
          true,
          true,
          "",
          "",
          "numeric",
          true,
          6
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("fam."),
          "family_status_code",
          ["rowspan" => 2, "width" => 30],
          null,
          true,
          true,
          "",
          "",
          "string",
          true,
          12
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("tax."),
          "tax_status_code",
          ["rowspan" => 2, "width" => 30],
          null,
          true,
          true,
          "",
          "",
          "string",
          true,
          12
      )
  );
  // 15:00 2016-02-04 Request bu soffi jadi Marital Status
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("Marital Status"),
          "ket_marital_status",
          ["rowspan" => 2, "width" => 30],
          null,
          true,
          true,
          "",
          "",
          "string",
          true,
          12
      )
  );
  //$myDataGrid->addColumn(new DataGrid_Column(getWords("liv."), "living_cost_code", array("rowspan" => 2, "width" => 30),  null, true, true, "", "", "string", true, 12));
  //$myDataGrid->addColumn(new DataGrid_Column(getWords("med."), "medical_quota_status", array("rowspan" => 2, "width" => 30),  null, true, true, "", "", "string", true, 12));
  //$myDataGrid->addColumn(new DataGrid_Column(getWords("finger ID"), "barcode", array("rowspan" => 2, "width" => 80),  array("nowrap" => "nowrap"), true, true, "", "", "integer", true, 12));
  if ($isFullView) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("nick name"),
            "nickname",
            ["rowspan" => 2, "width" => 80],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addSpannedColumn(getWords("primary address"), 4);
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("address"),
            "primary_address",
            ["width" => 150],
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
            getWords("city"),
            "primary_city",
            ["width" => 80],
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
            getWords("zip"),
            "primary_zip",
            ["width" => 40],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            8
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("phone"),
            "primary_phone",
            ["width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addSpannedColumn(getWords("emergency contact"), 4);
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("name"),
            "emergency_contact",
            ["width" => 150],
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
            getWords("relation"),
            "emergency_relation",
            ["width" => 80],
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
            getWords("address"),
            "emergency_address",
            ["width" => 120],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            30
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("phone"),
            "emergency_phone",
            ["width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("birthplace"),
            "birthplace",
            ["rowspan" => 2, "width" => 120],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            30
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("birthday"),
            "birthday",
            ["rowspan" => 2, "width" => 80],
            null,
            true,
            true,
            "",
            "formatDate()",
            "string",
            true,
            12
        )
    );
  }
  $myDataGrid->addSpannedColumn(getWords("work information"), 15);
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("employee status"),
          "employee_status",
          ["width" => 100],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "printEmployeeStatus()",
          "string",
          true,
          15
      )
  );
  $myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("company"),
        "company_name",
        ["width" => 50],
        ["nowrap" => "nowrap"],
        true,
        true,
        "",
        "",
        "string",
        true,
        12
    )
  );
  if ($isFullView) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("man."),
            "management_name",
            ["width" => 50],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            12
        )
    );
  }
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("div."),
          "division_name",
          ["width" => 50],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          true,
          12
      )
  );
  //$myDataGrid->addColumn(new DataGrid_Column(getWords("dept."), "department_name", array("width" => 50),  array("nowrap" => "nowrap"), false, true, "", "", "string", true, 12));
  //$myDataGrid->addColumn(new DataGrid_Column(getWords("sect."), "section_code", array("width" => 50),  array("nowrap" => "nowrap"), false, true, "", "", "string", true, 12));
  //$myDataGrid->addColumn(new DataGrid_Column(getWords("sub."), "sub_section_code", array("width" => 50),  array("nowrap" => "nowrap"), false, true, "", "", "string", true, 12));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("branch"),
          "branch_name",
          ["width" => 70],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          true,
          12
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("position"),
          "position_name",
          ["width" => 70],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          true,
          12
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("grade"),
          "grade_code",
          ["width" => 40],
          ["align" => "center"],
          true,
          true,
          "",
          "",
          "string",
          true,
          6
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("job title"),
          "functional_name",
          ["width" => 70],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          true,
          12
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("manager name"),
          "id",
          ["width" => 70],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "printManagerName()",
          "string",
          true,
          12
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("salary currency"),
          "salary_currency",
          ["width" => 40],
          ["align" => "center"],
          true,
          true,
          "",
          "printSalaryCurrency()",
          "string",
          true,
          6
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("join year"),
          "joinyear",
          ["width" => 30],
          ["align" => "right"],
          true,
          true,
          "",
          "",
          "numeric",
          true,
          6
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("join date"),
          "join_date",
          ["width" => 70],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "formatDate()",
          "string",
          true,
          12
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("finish date"),
          "due_date",
          ["width" => 70],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "formatDate()",
          "string",
          true,
          12
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("permanent date"),
          "permanent_date",
          ["width" => 70],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "formatDate()",
          "string",
          true,
          12
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("status"),
          "active",
          ["width" => 50],
          ["align" => "center", "nowrap" => "nowrap"],
          true,
          true,
          "",
          "printStatus()",
          "string",
          true,
          12
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("resign date"),
          "resign_date",
          ["width" => 70],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "formatDate()",
          "string",
          true,
          12
      )
  );
  if ($isFullView) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("weight"),
            "weight",
            ["rowspan" => 2, "width" => 40],
            null,
            true,
            true,
            "",
            "",
            "string",
            true,
            8
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("height"),
            "height",
            ["rowspan" => 2, "width" => 40],
            null,
            true,
            true,
            "",
            "",
            "string",
            true,
            8
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("blood"),
            "blood_type",
            ["rowspan" => 2, "width" => 30],
            null,
            true,
            true,
            "",
            "",
            "string",
            true,
            6
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("id card"),
            "id_card",
            ["rowspan" => 2, "width" => 80],
            null,
            true,
            true,
            "",
            "",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addSpannedColumn(getWords("driving license"), 3);
    $myDataGrid->addColumn(
        new DataGrid_Column("A", "driver_license_a", ["width" => 80], null, true, true, "", "", "string", true, 12)
    );
    $myDataGrid->addColumn(
        new DataGrid_Column("B", "driver_license_b", ["width" => 80], null, true, true, "", "", "string", true, 12)
    );
    $myDataGrid->addColumn(
        new DataGrid_Column("C", "driver_license_c", ["width" => 80], null, true, true, "", "", "string", true, 12)
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("nationality"),
            "nationality",
            ["rowspan" => 2, "width" => 70],
            null,
            true,
            true,
            "",
            "",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("passport no."),
            "passport",
            ["rowspan" => 2, "width" => 50],
            null,
            true,
            true,
            "",
            "",
            "string",
            true,
            8
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("religion"),
            "religion_code",
            ["rowspan" => 2, "width" => 70],
            null,
            true,
            true,
            "",
            "",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addSpannedColumn(getWords("education detail"), 2);
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("education"),
            "education_level_code",
            ["width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("major"),
            "major_code",
            ["width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("wedding date"),
            "wedding_date",
            ["rowspan" => 2, "width" => 80],
            null,
            true,
            true,
            "",
            "formatDate()",
            "string",
            true,
            12
        )
    );
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("spouse"), "inspouse", array("rowspan" => 2, "width" => 30), null, true, false, "", "printIsSpouse()", "string", true, 12));
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("zakat"), "zakat", array("rowspan" => 2, "width" => 30), null, true, false, "", "printIsZakat()", "string", true, 12));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("jamsostek no."),
            "jamsostek_no",
            ["rowspan" => 2, "width" => 80],
            null,
            true,
            true,
            "",
            "",
            "string",
            true,
            15
        )
    );
    $myDataGrid->addSpannedColumn(getWords("primary bank account"), 3);
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("acc no."),
            "bank_account",
            ["width" => 80],
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
            getWords("acc name"),
            "bank_account_name",
            ["width" => 120],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            30
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("bank code"),
            "bank_code",
            ["width" => 80],
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
    $myDataGrid->addSpannedColumn(getWords("secondary bank account"), 3);
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("acc no."),
            "bank2_account",
            ["width" => 80],
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
            getWords("acc name"),
            "bank2_account_name",
            ["width" => 120],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            30
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("bank code"),
            "bank2_code",
            ["width" => 80],
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
  }
  if (!$isFullView) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("phone"),
            "primary_phone",
            ["rowspan" => 2, "width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            12
        )
    );
  }
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("email"),
          "email",
          ["rowspan" => 2, "width" => 70],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          true,
          12
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("note"),
          "note_employee",
          ["rowspan" => 2, "width" => 70],
          ["nowrap" => "nowrap"],
          true,
          true,
          "",
          "",
          "string",
          true,
          12
      )
  );
  if ($isFullView) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("basic salary"),
            "basic_salary",
            ["rowspan" => 2, "width" => 70],
            ["nowrap" => "nowrap", "align" => "right"],
            false,
            true,
            "",
            "formatNumeric()",
            "numeric",
            true,
            15
        )
    );
  }
  if ($isExport) {
    $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
    $myDataGrid->strFileNameXLS = "employee_list.xls";
    $myDataGrid->strTitle1 = getWords("list of employee");
  }
  $myDataGrid->addRepeaterFunction("printDeniedNote()");
  if (!$bolPrint) {
    if ($_SESSION['sessionUserRole'] >= ROLE_ADMIN && $bolCanDelete) {
      $myDataGrid->addSpecialButton(
          "btnDelete",
          "btnDelete",
          "submit",
          getWords("delete"),
          "onClick=\"return confirmDelete();\"",
          "deleteData()"
      );
    }
    //$myDataGrid->addButton("btnPrint", "btnPrint", "submit", getWords("print"), "onClick=\"document.formData.target = '_blank';\"");
    $myDataGrid->addButtonExportExcel(getwords("export excel"), "employee_list.xls", getWords("list of employee"));
  }
  $myDataGrid->getRequest();
  if ($myDataGrid->sortName == "division_name") {
    $myDataGrid->sortName = "division_name,department_name,section_name,sub_section_name";
  } else {
    $myDataGrid->sortName .= ",division_name,department_name,section_name,sub_section_name";
  }
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  //$strCriteriaFlag = $myDataGrid->getCriteria()." AND (flag = 0 OR (flag=0 AND (\"link_id\" IS NULL))) ".$strCriteria;
  $strOrderBy = $myDataGrid->getSortBy();
  if ($bolLimit) {
    $strPageLimit = $myDataGrid->getPageLimit();
    $intPageNumber = $myDataGrid->getPageNumber();
  } else {
    $strPageLimit = null;
    $intPageNumber = null;
  }
  $myDataGrid->totalData = $tblEmployee->findCount($strCriteriaOther);
  $arrTmpData = getEmployeeUpdated($strCriteriaOther);
  $newDataset = [];
  // 11:18 2016-02-05 by Sanusi CASE WHEN t0.marital_status = 0 THEN 'SINGLE' ELSE 'MARRIED' END AS ket_marital_status
  /*$strSQL       = "SELECT t0.*,CASE WHEN t0.marital_status = 0 THEN 'SINGLE' ELSE 'MARRIED' END AS ket_marital_status,(EXTRACT(YEAR FROM AGE(birthday))) AS umur, (EXTRACT(YEAR FROM AGE(\"join_date\"))) AS joinyear,
                   management_name, division_name, department_name, section_name, sub_section_name, branch_name, position_name, functional_name
                   FROM hrd_employee AS t0
                   LEFT JOIN hrd_management AS t1 ON t0.management_code = t1.management_code
                   LEFT JOIN hrd_division AS t2 ON t0.division_code = t2.division_code
                   LEFT JOIN hrd_department AS t3 ON t0.department_code = t3.department_code
                   LEFT JOIN hrd_section AS t4 ON t0.section_code = t4.section_code
                   LEFT JOIN hrd_branch AS t5 ON t0.branch_code = t5.branch_code
                   LEFT JOIN hrd_position AS t7 ON t0.position_code = t7.position_code
                   LEFT JOIN hrd_functional AS t8 ON t0.functional_code = t8.functional_code
                   LEFT JOIN hrd_sub_section AS t6 ON t0.sub_section_code = t6.sub_section_code WHERE 1=1
         $strCriteriaMain
                    ";*/
  //17:15 2016-02-16 by sanusi penambahan Basic salary berdasarkan salaryset terbaru di report employee
  //13:05 2016-04-12 by sanusi marital status input sendiri tetapi cek juga jika wedding date terisi dianggap merried juga walau marital status single,
  $strSQL = "SELECT t0.id,t0.employee_id,t0.employee_name,t0.primary_address,t0.primary_phone,
                    t0.birthplace,t0.nationality,t0.barcode,t0.id_card,t0.bank_account,t0.jamsostek_no,
                    t0.email,t0.photo,t0.active,t0.gender,t0.birthday,t0.is_birthday,t0.family_status_code,
                    t0.religion_code,t0.education_level_code,t0.employee_status,t0.division_code,t0.department_code,
                    t0.section_code,t0.sub_section_code,t0.position_code,t0.grade_code,t0.join_date,
                    t0.due_date,t0.permanent_date,t0.resign_date,t0.note AS note_employee,
                    t0.transport_code,t0.emergency_contact,t0.emergency_address,t0.emergency_phone,
                    t0.emergency_relation,t0.driver_license_a,t0.driver_license_b,t0.driver_license_c,
                    t0.wedding_date,t0.weight,t0.height,t0.blood_type,t0.primary_city,t0.primary_zip,
                    t0.functional_code,t0.zakat,t0.npwp,t0.house_status,t0.letter_code,t0.passport,
                    t0.marital_status,t0.bank_account_name,t0.bank_branch,t0.verified_time,t0.checked_time,
                    t0.approved_time,t0.denied_time,t0.verified_by,t0.checked_by,t0.approved_by,
                    t0.denied_by,t0.inspouse,t0.created,t0.created_by,t0.modified,
                    t0.modified_by,t0.bank_account_type,t0.salary_payment_type,t0.id_company,
                    t0.bank_code,t0.nickname,t0.living_cost_code,t0.flag,t0.link_id,t0.medical_quota_status,
                    t0.management_code,t0.bank2_branch,t0.bank2_account_name,t0.bank2_account,t0.bank2_account_type,
                    t0.bank2_code,t0.branch_code,t0.transport,t0.transport_fee,t0.probation_date,
                    t0.salary_currency,t0.salary_grade_code,t0.major_code,t0.ot_meal,t0.tax_status_code,
                    t0.private_phone,t0.private_email,t0.gross_up,t0.get_jamsostek,t0.get_bpjs,t0.bpjs_no,
                    t0.bpjs_tk_no,t0.mother_name,t0.is_immune_auto_alpha,t0.sub_department_code,
                    CASE WHEN (t0.marital_status= 1 OR t0.wedding_date IS NOT NULL) THEN
                     'MARRIED' ELSE 'SINGLE' 
                    END AS ket_marital_status,(EXTRACT(YEAR FROM AGE(birthday))) AS umur, (EXTRACT(YEAR FROM AGE(\"join_date\"))) AS joinyear,
                     management_name, division_name, department_name, sub_department_name, section_name, sub_section_name, branch_name, position_name, functional_name,
                     t9.company_name
                     FROM hrd_employee AS t0
                     LEFT JOIN hrd_management AS t1 ON t0.management_code = t1.management_code
                     LEFT JOIN hrd_division AS t2 ON t0.division_code = t2.division_code
                     LEFT JOIN hrd_department AS t3 ON t0.department_code = t3.department_code
                     LEFT JOIN hrd_sub_department AS t10 ON t0.sub_department_code = t10.sub_department_code
                     LEFT JOIN hrd_section AS t4 ON t0.section_code = t4.section_code
                     LEFT JOIN hrd_sub_section AS t6 ON t0.sub_section_code = t6.sub_section_code
                     LEFT JOIN hrd_branch AS t5 ON t0.branch_code = t5.branch_code
                     LEFT JOIN hrd_position AS t7 ON t0.position_code = t7.position_code
                     LEFT JOIN hrd_functional AS t8 ON t0.functional_code = t8.functional_code
                     LEFT JOIN hrd_company AS t9 ON t0.id_company = t9.id
                    WHERE 1=1
           $strCriteriaMain
                      ";
  //krumo($strSQL);
  //$myDataGrid->totalData = count($tblEmployee->findAll($strSQL));
  $dataset = $myDataGrid->getData($db, $strSQL);
  //$myDataGrid->bind($dataset);
  foreach ($dataset as $rowDb) {
    /*if ($isFullView) {
      $rowDb['basic_salary'] = isset($employeeBasicSalary[$rowDb['id']]) ? $employeeBasicSalary[$rowDb['id']] : 0;
    }*/
    // cek apakah tampil semua atau yang berubah aja
    if ($bolUpdateOnly) {
      if (isset($arrTmpData[$rowDb['id']])) {
        if ($intdataFlag == -1 || $intdataFlag == $arrTmpData[$rowDb['id']]['flag']) {
          $newDataset[] = $rowDb;
          $newDataset[] = $arrTmpData[$rowDb['id']];
          unset($arrTmpData[$rowDb['id']]);
        } else {
          unset($arrTmpData[$rowDb['id']]);
        }
      }
    } else {
      $newDataset[] = $rowDb;
      if (isset($arrTmpData[$rowDb['id']])) {
        $newDataset[] = $arrTmpData[$rowDb['id']];
        unset($arrTmpData[$rowDb['id']]);
      }
    }
  }
  $intTotalData = count($newDataset);
  $myDataGrid->bind($newDataset);
  return $myDataGrid->render();
}

function printManagerName($params)
{
  global $db;
  extract($params);
  $arrResult = getHeadEmployeeData($db, $value);
  return $arrResult['employee_name'];
}

function printGender($params)
{
  extract($params);
  return ($value == 0) ? "F" : "M";
}

function printIsSpouse($params)
{
  extract($params);
  return ($value == 't') ? "*" : "";
}

function printIsZakat($params)
{
  extract($params);
  return ($value == 't') ? "*" : "";
}

function printResumeLink($params)
{
  extract($params);
  global $bolPrint;
  if ($bolPrint) {
    return stripslashes($value);
  } else //2011-04-20 dessy, ubah get ke post, gunakan form tambahan supaya tidak terlalu banyak data yang disubmit (see templates/employee_search.html)
  {
    return generateHidden("dataID_" . $record['id'], $record['id'], "") . generateButton(
        "btnReferer" . $record['id'],
        $value,
        "style=\"background-color:white;border:none;color:blue;width:50px;text-align:left;\"",
        "onclick=\"document.formReferer.dataID.value = '" . $record['id'] . "';document.formReferer.submit()\""
    );
  }
  //----------------------------------------------------------------------------------------------------------
}

function printEmployeeName($params)
{
  extract($params);
  global $bolPrint;
  if ($bolPrint) {
    return $value;
  } else {
    $strHiddenInfo = "<input type=hidden name='detailName$counter' value='" . stripslashes($value) . "' disabled>";
    $strHiddenInfo .= "<input type=hidden name='detailDenied$counter' value='' disabled>";
    return $value . $strHiddenInfo;
  }
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

function printDeniedNote($params)
{
  extract($params);
  global $bolPrint;
  global $bolFull;
  $strResult = "";
  if ($bolFull) {
    $colspan = "colspan=57";
  } else {
    $colspan = "colspan=20";
  }
  $strClass = getCSSClassName($record['flag']);
  $strEmployeeInfo = $record['employee_id'] . " - " . $record['employee_name'];
  if (!$bolPrint && $record['flag'] == 3) {
    $strResult .= "<tr valign=top $strClass title=\"$strEmployeeInfo\">\n";
    $strResult .= "  <td nowrap colspan=3>" . getWords("denial reason") . ":</td>";
    $strResult .= "  <td nowrap $colspan><strong>" . $record['note_denied'] . "&nbsp;</strong></td>";
    $strResult .= "</tr>\n";
  }
  return $strResult;
}

function getEmployeeUpdated($strCriteria)
{
  global $tblEmployee;
  // CARI DATA RECORD YANG DIUPDATE/BARU, TAPI BELUM DI APPROVE MA MANAGER, SIMPAN DI ARRAY, tapi yang sifatnya UPDATE
  return $tblEmployee->findAll(
      $strCriteria,
      "*, (EXTRACT(YEAR FROM AGE(birthday))) AS umur, (EXTRACT(YEAR FROM AGE(\"join_date\"))) AS joinyear",
      null,
      null,
      null,
      "link_id"
  );
}

// fungsi untuk menghapus data
function deleteData()
{
  global $tblEmployee;
  global $myDataGrid;
  $arrKeys = [];
  //$tblEmployee->begin();
  $isSuccess = false;
  $counter = 0;
  foreach ($myDataGrid->checkboxes as $strValue) {
    $counter++;
    $strSQL = "";
    $strSQL .= "DELETE FROM \"hrd_employee_family\" WHERE \"id_employee\" = '$strValue'; ";
    $strSQL .= "DELETE FROM \"hrd_employee_education\" WHERE \"id_employee\" = '$strValue'; ";
    $strSQL .= "DELETE FROM \"hrd_employee_training\" WHERE \"id_employee\" = '$strValue'; ";
    $strSQL .= "DELETE FROM \"hrd_employee_work\" WHERE \"id_employee\" = '$strValue'; ";
    //$strSQL .= "DELETE FROM \"hrd_employee_department_history\" WHERE \"id_employee\" = '$strValue'; ";
    //$strSQL .= "DELETE FROM \"hrd_employee_position_history\" WHERE \"id_employee\" = '$strValue'; ";
    //$strSQL .= "DELETE FROM \"hrd_employee_grade_history\" WHERE \"id_employee\" = '$strValue'; ";
    //$strSQL .= "DELETE FROM \"hrd_employee_status_history\" WHERE \"id_employee\" = '$strValue'; ";
    $strSQL .= "DELETE FROM \"hrd_employee_facility\" WHERE \"id_employee\" = '$strValue'; ";
    $strSQL .= "DELETE FROM \"hrd_employee\" WHERE \"link_id\" = '$strValue'; ";
    $strSQL .= "DELETE FROM \"hrd_employee\" WHERE id = '$strValue'; ";
    $isSuccess = $tblEmployee->execute($strSQL);
    if (!$isSuccess) {
      break;
    }
  }
  if ($isSuccess) {
    //$tblEmployee->commit();
    $myDataGrid->message = $counter . " record(s) " . getWords("data employee deleted!");
  } else {
    $tblEmployee->rollback();
    $myDataGrid->errorMessage = getWords("failed to delete data employee!");
  }
} //deleteData
// fungsi untuk check data temporer
function checkData()
{
  global $tblEmployee;
  global $myDataGrid;
  $strUpdater = $_SESSION['sessionUserID'];
  $tblEmployee->begin();
  $counter = 0;
  $isSuccess = true;
  foreach ($myDataGrid->checkboxes as $strValue) {
    if ($tblEmployee->findCount("id=" . intval($strValue) . " AND flag<>0") > 0) {
      $counter++;
      $strSQL = "UPDATE \"hrd_employee\" set flag = 2, \"checked_by\" = '$strUpdater', \"checked_time\" = now() ";
      $strSQL .= "WHERE id = '$strValue' AND flag <> 0 "; // cuma yang baru
      $isSuccess = $tblEmployee->execute($strSQL);
      if (!$isSuccess) {
        break;
      }
    }
  }
  if ($isSuccess) {
    $tblEmployee->commit();
    if ($counter == 0) {
      $myDataGrid->message = getWords("no records data employee checked!");
    } else {
      $myDataGrid->message = $counter . " record(s) " . getWords("data employee checked!");
    }
  } else {
    $tblEmployee->rollback();
    $myDataGrid->errorMessage = getWords("failed to check data employee!");
  }
} //checkData
// fungsi untuk check data temporer
function deniedData()
{
  global $tblEmployee;
  global $myDataGrid;
  $strUpdater = $_SESSION['sessionUserID'];
  $tblEmployee->begin();
  $isSuccess = false;
  $counter = 0;
  foreach ($myDataGrid->checkboxes as $strID => $strValue) {
    $i = str_replace("DataGrid1_chkID", "", $strID);
    $counter++;
    $strNote = getPostValue('detailDenied' . $i);
    $strSQL = "UPDATE \"hrd_employee\" set flag = 3, \"note_denied\" = '$strNote', ";
    $strSQL .= "\"denied_time\" = now(), \"denied_by\" = '$strUpdater' ";
    $strSQL .= "WHERE id = '$strValue' AND flag <> 0 "; // cuma yang baru
    $isSuccess = $tblEmployee->execute($strSQL);
    if (!$isSuccess) {
      break;
    }
  }
  if ($isSuccess) {
    $tblEmployee->commit();
    $myDataGrid->message = $counter . " record(s) " . getWords("data employee denied!");
  } else {
    $tblEmployee->rollback();
    $myDataGrid->errorMessage = getWords("failed to denied data employee!");
  }
} //deniedData
// fungsi untuk approve data temporer
function approveData()
{
  global $tblEmployee;
  global $myDataGrid;
  $strUpdater = $_SESSION['sessionUserID'];
  $tblEmployee->begin();
  $counter = 0;
  $isSuccess = true;
  foreach ($myDataGrid->checkboxes as $strID => $strValue) {
    if ($rowDb = $tblEmployee->find(
        "id=" . intval($strValue) . " AND flag<>0",
        "id, flag, \"link_id\""
    )
    ) {
      $counter++;
      $strLinkID = $rowDb['link_id'];
      if ($strLinkID == "") {
        // baru
        $strSQL = "UPDATE \"hrd_employee\" SET flag = 0, \"link_id\" = NULL, ";
        $strSQL .= "\"approved_time\" = now(), \"approved_by\" = '$strUpdater' ";
        $strSQL .= "WHERE id = '$strValue' ";
        $isSuccess = $tblEmployee->execute($strSQL);
      } else {
        // update
        $strSQL = "DELETE FROM \"hrd_employee\" WHERE id = '$strLinkID'; \n";
        $strSQL .= "UPDATE \"hrd_employee\" SET flag = 0, id = \"link_id\", \"link_id\" = NULL, ";
        $strSQL .= "\"approved_time\" = now(), \"approved_by\" = '$strUpdater' ";
        $strSQL .= "WHERE id = '$strValue' ";
        $isSuccess = $tblEmployee->execute($strSQL);
      }
      if (!$isSuccess) {
        break;
      }
    }
  }
  if ($isSuccess) {
    $tblEmployee->commit();
    if ($counter == 0) {
      $myDataGrid->message = getWords("no records data employee approved!");
    } else {
      $myDataGrid->message = $counter . " record(s) " . getWords("data employee approved!");
    }
  } else {
    $tblEmployee->rollback();
    $myDataGrid->errorMessage = getWords("failed to approved data employee!");
  }
} //approveData
function getData($db, &$intRows, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
{
  //global $words;
  //global $bolPrint;
  //global $ARRAY_EMPLOYEE_STATUS;
  global $strPaging;
  global $intTotalData;
  global $intRowsLimit;
  global $bolIsEmployee;
  global $_REQUEST;
  $intRowsLimit = getSetting("rows_per_page");
  if (!is_numeric($intRowsLimit)) {
    $intRowsLimit = 50;
  }
  $intRows = 0;
  $strResult = "";
  $bolUpdateOnly = (isset($_REQUEST['btnShowAlert']));
  $intFilterFlag = (isset($_REQUEST['dataStatus'])) ? $_REQUEST['dataStatus'] : -1;
  // cari total data
  $intTotal = 0;
  $strSQL = "SELECT count(id) AS total FROM hrd_employee ";
  $strSQL .= "WHERE flag=0 $strKriteria ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    if (is_numeric($rowDb['total'])) {
      $intTotal = $rowDb['total'];
    }
  }
  $strPaging = getPaging($intPage, $intTotal, "javascript:goPage('[PAGE]')");
  if ($strPaging == "") {
    $strPaging = "1&nbsp;";
  }
  $intStart = (($intPage - 1) * $intRowsLimit);
  // CARI DATA RECORD YANG BARU, TAPI BELUM DI APPROVE MA MANAGER, SIMPAN DI ARRAY, taruh paling bawah :D
  $strSQL = "SELECT *,(EXTRACT(YEAR FROM AGE(birthday))) AS umur FROM hrd_employee ";
  $strSQL .= "WHERE flag <> 0 AND (link_id is NULL) $strKriteria ";
  $strSQL .= "ORDER BY $strOrder employee_name ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strResult .= getDataPerRow($rowDb, $intRows);
  }
  // CARI DATA RECORD YANG DIUPDATE/BARU, TAPI BELUM DI APPROVE MA MANAGER, SIMPAN DI ARRAY, tapi yang sifatnya UPDATE
  $strSQL = "SELECT *,(EXTRACT(YEAR FROM AGE(birthday))) AS umur FROM hrd_employee ";
  $strSQL .= "WHERE flag <> 0 AND (link_id is not NULL) $strKriteria ";
  $strSQL .= "ORDER BY $strOrder employee_name ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if ($rowDb['link_id'] != "") {
      $arrTmpData[$rowDb['link_id']] = $rowDb;
    }
  }
  //-----------------
  $strSQL = "SELECT *,(EXTRACT(YEAR FROM AGE(birthday))) AS umur FROM hrd_employee ";
  $strSQL .= "WHERE flag=0 $strKriteria ";
  $strSQL .= "ORDER BY $strOrder employee_name ";
  if ($bolLimit) {
    $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cek apakah tampil semua atau yang berubah aja
    if ($bolUpdateOnly) {
      if (isset($arrTmpData[$rowDb['id']])) {
        if ($intFilterFlag == -1 || $intFilterFlag == $arrTmpData[$rowDb['id']]['flag']) {
          $intRows++;
          $strResult .= getDataPerRow($rowDb, $intRows);
          $intRows++;
          $strResult .= getDataPerRow($arrTmpData[$rowDb['id']], $intRows);
          unset($arrTmpData[$rowDb['id']]);
        } else {
          unset($arrTmpData[$rowDb['id']]);
        }
      }
    } else {
      $intRows++;
      $strResult .= getDataPerRow($rowDb, $intRows);
      if (isset($arrTmpData[$rowDb['id']])) {
        $intRows++;
        $strResult .= getDataPerRow($arrTmpData[$rowDb['id']], $intRows);
        unset($arrTmpData[$rowDb['id']]);
      }
    }
  }
  // tampilkan data yang yatim piatu :))
  if (isset($arrTmpData) && !$bolLimit) { // hanya untuk yang tampil semua
    foreach ($arrTmpData AS $id => $arrDataEmp) {
      $intRows++;
      $strResult .= getDataPerRow($arrDataEmp, $intRows, true);
    }
  }
  $intTotalData = $intRows;
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$intRows data", 0);
  }
  if (!$bolLimit) {
    $strPaging = "&nbsp;";
  }
  return $strResult;
} // showData
?>
