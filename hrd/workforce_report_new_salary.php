<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
$dataPrivilege = getDataPrivileges("workforce_report_new.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
    die(accessDenied($_SERVER['HTTP_REFERER']));
}
$strTemplateFile = getTemplate("employee_report.html");
//---- INISIALISASI ----------------------------------------------------
$strWordsCompany = getWords("company");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("sub section");
$strDataDetail = "";
$strHidden = "";
$strResult = "";
$strNow = date("Y-m-d");
$strWordsDate = getWords("salary date");
$strWordsInterval = getWords("interval");
$strChartNewJs = "";
$strChartNew = "";
$strSelectType0 = "";
$strSelectType1 = "";
$strSelectType2 = "";
$strSelectType3 = "";
$strSelectType4 = "";
$strSelectType5 = "";
$strSelectType6 = "";
if(empty($_GET['datefrom'])){
	$dateFromVal = "";
}else{
	$dateFromVal = $_GET['datefrom'];
}
if(empty($_GET['dateend'])){
	$dateEndVal = "";
}else{
	$dateEndVal = $_GET['dateend'];
}
// $dateFromVal = $_GET['datefrom'];
// $dateEndVal = $_GET['dateend'];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    // ------ AMBIL DATA KRITERIA -------------------------
    (isset($_REQUEST['dataType'])) ? $strDataType = $_REQUEST['dataType'] : $strDataType = "";
    (isset($_REQUEST['dataSalaryDate'])) ? $strDate = $_REQUEST['dataSalaryDate'] : $strDate = "";
    (isset($_REQUEST['dataDivision'])) ? $strDataDivision = $_REQUEST['dataDivision'] : $strDataDivision = "";
    (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
    (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
    (isset($_REQUEST['dataSubsection'])) ? $strDataSubSection = $_REQUEST['dataSubsection'] : $strDataSubSection = "";
    (isset($_REQUEST['dataInterval']) && $_REQUEST['dataInterval'] > 0) ? $strDataInterval = $_REQUEST['dataInterval'] : $strDataInterval = 10;
    $strDataEployee = "";
    scopeData(
        $strDataEmployee,
        $strDataSubSection,
        $strDataSection,
        $strDataDepartment,
        $strDataDivision,
        $_SESSION['sessionUserRole'],
        $arrUserInfo
    );
    $strKriteria = "1=1 ";
    if ($strDataDivision != "") {
        $strSQL = "SELECT division_name FROM hrd_division WHERE division_code = '$strDataDivision' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strDivisionName = $rowDb['division_name'];
        }
        $strKriteria .= "AND t1.division_code = '$strDataDivision' ";
    }
    if ($strDataDepartment != "") {
        $strSQL = "SELECT department_name FROM hrd_department WHERE department_code = '$strDataDepartment' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strDepartmentName = $rowDb['department_name'];
        }
        $strKriteria .= "AND t1.department_code = '$strDataDepartment' ";
    }
    if ($strDataSection != "") {
        $strSQL = "SELECT section_name FROM hrd_section WHERE section_code = '$strDataSection' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strSectionName = $rowDb['section_name'];
        }
        $strKriteria .= "AND t1.section_code = '$strDataSection' ";
    }
    if ($strDataSubSection != "") {
        $strSQL = "SELECT sub_section_name FROM hrd_sub_section WHERE sub_section_code = '$strDataSubSection' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strSubSectionName = $rowDb['sub_section_name'];
        }
        $strKriteria .= "AND t1.sub_section_code = '$strDataSubSection' ";
    }
    //$strDate untuk join HRD SALARY DETAIL DGN HRD SALARY MASTER
    //$strKriteria .= "AND id_salary_master = '$strDate' ";
    //$strKriteria .= "AND join_date BETWEEN '$strDate' AND '$strDateThru'";
    $strKriteriaCompany = str_replace("id", "t1.id", $strKriteriaCompany);
    $strKriteria .= $strKriteriaCompany;
    //---- Generate chart
    // Gender
    if ($strDataType == "" or $strDataType == "0") {
        include('workforce_report_salary_pie.php');
        $strWordsChardTitle = 'Employee Gender';
        $strSelectType0 = " selected";
    }
    if ($strDataType == "4") {
        include('workforce_report_salary_pie.php');
        $strWordsChardTitle = 'Employee Salary';
        $strSelectType4 = " selected";
    }
     //-------- end chart
    getUserEmployeeInfo();
    $intDefaultWidthPx = 200;
    $strDataDivision = (isset($_REQUEST['dataDivision'])) ? $_REQUEST['dataDivision'] : "";
    $strDataDepartment = (isset($_REQUEST['dataDepartment'])) ? $_REQUEST['dataDepartment'] : "";
    $strDataSection = (isset($_REQUEST['dataSection'])) ? $_REQUEST['dataSection'] : "";
    $strDataSubSection = (isset($_REQUEST['dataSubSection'])) ? $_REQUEST['dataSubSection'] : "";
    $strDataEmployee = "";
    $strDataSalaryDate = (isset($_REQUEST['dataSalaryDate'])) ? $_REQUEST['dataSalaryDate'] : "";
    scopeData(
        $strDataEmployee,
        $strDataSubSection,
        $strDataSection,
        $strDataDepartment,
        $strDataDivision,
        $_SESSION['sessionUserRole'],
        $arrUserInfo
    );
    $strInputCompany = getCompanyList(
        $db,
        "dataCompany",
        $strDataCompany,
        $strEmptyOption2,
        $strKriteria2,
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputDate = getSalaryDateList(
        $db,
        "dataSalaryDate",
        $strDataSalaryDate,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
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
    $strInputCompany = getCompanyList(
        $db,
        "dataCompany",
        $strDataCompany,
        $strEmptyOption2,
        $strKriteria2,
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputInterval = "<input type=text name='dataInterval' id='dataInterval' value='$strDataInterval'>";
    if (!$bolCanView) {
        showError("view_denied");
        $strDataDetail = "";
    }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/Pie Chart.png";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = getWords("salary statistical analysis page");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strWordsStaticticalReport = getWords("statistical analisys");
$pageSubMenu = workreportSubmenu($strWordsStaticticalReport);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
