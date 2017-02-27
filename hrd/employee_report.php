<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
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
$strTemplateFile = getTemplate("employee_report.html");
//---- INISIALISASI ----------------------------------------------------
$strWordsCompany = getWords("company");
$strWordsManagement = getWords("management");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("sub section");
$strWordsGrade = getWords("grade");
$strWordsLevel = getWords("level");
$strWordsPosition = getWords("position");
$strWordsSearchEmployee = getWords("search employee");
$strWordsSimpleResume = getWords("simple resume");
$strWordsReport = getWords("report");
$strWordsShow = getWords("show");
$strWordsExcel = getWords("excel");
$strDataDetail = "";
$strHidden = "";
$strResult = "";
$strNow = date("Y-m-d");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $intDefaultWidthPx = 200;
    $strDataCompany = (isset($_REQUEST['dataCompany'])) ? $_REQUEST['dataCompany'] : "";
    $strDataManagement = (isset($_REQUEST['dataManagement'])) ? $_REQUEST['dataManagement'] : "";
    $strDataDivision = (isset($_REQUEST['dataDivision'])) ? $_REQUEST['dataDivision'] : "";
    $strDataDepartment = (isset($_REQUEST['dataDepartment'])) ? $_REQUEST['dataDepartment'] : "";
    $strDataSection = (isset($_REQUEST['dataSection'])) ? $_REQUEST['dataSection'] : "";
    $strDataSubSection = (isset($_REQUEST['dataSubSection'])) ? $_REQUEST['dataSubSection'] : "";
    $strDataGrade = (isset($_REQUEST['dataGrade'])) ? $_REQUEST['dataGrade'] : "";
    $strDataPosition = (isset($_REQUEST['dataPosition'])) ? $_REQUEST['dataPosition'] : "";
    $strDataEmployee = "";
    scopeData(
        $strDataEmployee,
        $strDataSubSection,
        $strDataSection,
        $strDataDepartment,
        $strDataManagement,
        $strDataDivision,
        $_SESSION['sessionUserRole'],
        $arrUserInfo
    );
    $strInputCompany = getCompanyList(
        $db,
        "dataCompany",
        15,
        $strEmptyOption2,
        $strKriteria2,
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInpuManagement = getManagementList(
        $db,
        "dataManagement",
        21,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['division']
    );
    $strInputDivision = getDivisionList(
        $db,
        "dataDivision",
        $strDataDivision,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['division']
    );
    $strInputDepartment = getDepartmentList(
        $db,
        "dataDepartment",
        $strDataDepartment,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['department']
    );
    $strInputSection = getSectionList(
        $db,
        "dataSection",
        $strDataSection,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['section']
    );
    $strInputSubSection = getSubSectionList(
        $db,
        "dataSubsection",
        $strDataSubSection,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['sub_section']
    );
    // $strInputCompany    = getCompanyList($db, "dataCompany",$strDataCompany, $strEmptyOption2, $strKriteria2, "style=\"width:$intDefaultWidthPx\"");
    $strInputGrade = getSalaryGradeList(
        $db,
        "dataGrade",
        $strDataGrade,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputPosition = getPositionList(
        $db,
        "dataPosition",
        $strDataPosition,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
    $strReportType = getWords("report type");
    $strInputReportType = '<select name="dataType" id="dataType" onChange="configureCriteria();" class="form-control select2">
                        <option value="2">Employee Report</option>
                        <option value="1">List of Resign/End Contract Employee</option>
                        <option value="0">List of New Employee</option>
                      </select>';
    $strDateFrom = getWords('date from');
    $strInputDateFrom = '<input class="form-control datepicker" name="dataDateFrom" type="text" id="dataDateFrom" value="' . date(
            $_SESSION['sessionDateSetting']['php_format']
        ) . '" maxlength="10" data-date-format="' . $_SESSION['sessionDateSetting']['html_format'] .'">';
    $strDateThru = getWords('date thru');
    $strInputDateThru = '<input class="form-control datepicker" name="dataDateThru" type="text" id="dataDateThru" value="' . date(
            $_SESSION['sessionDateSetting']['php_format']
        ) . '" maxlength="10" data-date-format="' . $_SESSION['sessionDateSetting']['html_format'] .'">';
    if (!$bolCanView) {
        showError("view_denied");
        $strDataDetail = "";
    }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$strPageDesc = 'Employee Report';
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = employeeDataSubmenu($strWordsReport);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>