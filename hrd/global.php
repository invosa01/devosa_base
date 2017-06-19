<?php
/*
  Daftar variabel dan fungsi global
    Author: Yudi K.
*/
include("../global.php");
include_once("../global/common_variable.php");
include_once("../global/common_data.php");
include_once("../global/common_function.php");
include_once("../global/date_function.php");
include_once("../global/approval_function.php");
include_once("../global/form_function.php");
include_once("../global/words.php");
$strPrintCss = "../css/bw.css"; // file CSS untuk print
$strPrintInit = "window.print();";
$strCopyright = COPYRIGHT;
$strCompanyName = getSetting("company_name");
$strWordsINPUTDATA = getWords("input data");
$strWordsLISTOF = getWords("list of");
$strWordsFILTERDATA = getWords("filter data");
$strConfirmSave = $messages['confirm_save'];
$strConfirmApprove = $messages['confirm_approve'];
$strConfirmDelete = $messages['confirm_delete'];
$strConfirmChangeStatus = $messages['confirm_change_status'];
$permissionGroup = $_SESSION['sessionPermissionGroup'];
if (isset($_REQUEST['dataCompany'])) {
    $intIdCompany = $_REQUEST['dataCompany'];
} else if (isset($_REQUEST['filterCompany'])) {
    $intIdCompany = $_REQUEST['filterCompany'];
} else if (isset($_SESSION['sessionIdCompany'])) {
    $intIdCompany = $_SESSION['sessionIdCompany'];
} else {
    $intIdCompany = -1;
}
$bolIdCompany = ($intIdCompany == -1 || $intIdCompany == "");
if (!$bolIdCompany) {
    $strKriteriaCompany = " AND id_company = '$intIdCompany' ";
    $strDataCompany = $intIdCompany;
    $strFilterCompany = $intIdCompany;
    if (isset($_SESSION['sessionIdCompany']) && $_SESSION['sessionIdCompany'] != -1 && $_SESSION['sessionIdCompany'] != "") {
        $strKriteria2 = "WHERE id = " . $_SESSION['sessionIdCompany'] . " ";
        $strKriteria3 = "WHERE company_id = " . $_SESSION['sessionIdCompany'] . " ";
        $strEmptyOption2 = "";
        $bolCompanyEmptyOption = false;
        $arrCompanyEmptyData = null;
    } else {
        $strKriteria2 = "";
        $strKriteria3 = "";
        $strEmptyOption2 = $strEmptyOption;
        $bolCompanyEmptyOption = true;
        $arrCompanyEmptyData = ["value" => "", "text" => "", "selected" => true];
    }
} else {
    $strKriteriaCompany = "";
    $strDataCompany = "";
    $strFilterCompany = "";
    $strKriteria2 = "";
    $strKriteria3 = "";
    $strEmptyOption2 = $strEmptyOption;
    $bolCompanyEmptyOption = true;
    $arrCompanyEmptyData = ["value" => "", "text" => "", "selected" => true];
}
$strKriteriaOrganizational = "";
$strKriteriaOrganizational .= (!isset($_SESSION['sessionUserDivision']) || $_SESSION['sessionUserDivision'] == "") ? "" : "AND division_code = '" . $_SESSION['sessionUserDivision'] . "' ";
$strKriteriaOrganizational .= (!isset($_SESSION['sessionUserDepartment']) || $_SESSION['sessionUserDepartment'] == "") ? "" : "AND department_code  = '" . $_SESSION['sessionUserDepartment'] . "' ";
$strKriteriaOrganizational .= (!isset($_SESSION['sessionUserSection']) || $_SESSION['sessionUserSection'] == "") ? "" : "AND section_code = '" . $_SESSION['sessionUserSection'] . "' ";
$strKriteriaOrganizational .= (!isset($_SESSION['sessionUserSubsection']) || $_SESSION['sessionUserSubsection'] == "") ? "" : "AND sub_section_code = '" . $_SESSION['sessionUserSubsection'] . "' ";
$intPageLimit = 10; // jumlah link page maksimal yang ditampilkan
$intRowsLimit = 50; // jumlah baris yang ditampilkan satu page
if ($permissionGroup == 0 || $permissionGroup == null) {
    $strCriteriaPosition = ""; // Bisa lihat semua level position
    $intPermissionGroup = 0;
} else {
    $strCriteriaPosition = "WHERE position_group >= '$permissionGroup' ";
    $intPermissionGroup = intval($permissionGroup);
}
function now()
{
    $dateTime = date_create('now')->format('Y-m-d H:i:s');
    return $dateTime;
}

?>
