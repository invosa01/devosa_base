<?php
include_once('../global/session.php');
include_once('../global.php');
include_once('../global/common_function.php');
include_once('../global/cls_permission.php');
include_once('../includes/datagrid/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../global/date_function.php');
include_once('../global/common_data.php');
$dataPrivilege = getDataPrivileges("recruitment_edit.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$isCandidate = ($_SESSION['sessionGroupRole'] == ROLE_CANDIDATE);
$strPrintDate = date("d F Y");
$strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
$isNew = ($strDataID == "");
$checked = "X";
$strNow = date("Y-m-d H:i:s");
$db = new CdbClass();
$db->connect();
$arrUserList = getAllUserInfo($db);//ambil semua info user]
$tblMRF = new cModel("hrd_recruitment_need", getWords("mrf"));
$arrData = getData($strDataID);
//print_r($arrData);
$strDataNo = "" . $arrData['request_number'];
$strDataDate = "" . pgDateFormat($arrData['recruitment_date'], "d-M-Y");
$strDataCompany = "" . $arrData['company_name'];
$strDataGrade = "" . $arrData['grade_code'];
$strDataDepartment = "" . $arrData['department_code'];
$strDataPosition = "" . $arrData['position_code'];
$strDataRequired = "" . $arrData['number'];
$strDataDateRequired = ($arrData['due_date'] == "") ? "ASAP" : "" . pgDateFormat($arrData['due_date'], "d-M-Y");
$strDataTotalEmployee = "" . $arrData['actual_number'];
$strDataReason = "" . nl2br($arrData['reason']);
$strDataJobDescription = "" . nl2br($arrData['description']);
$strDataQualification = "" . nl2br($arrData['qualification']);
$strDataSalary = "" . standardFormat($arrData['monthly_salary']);
$strDataAllowance = "" . standardFormat($arrData['allowance']);
$strCreatedDate = pgDateFormat(substr($arrData['created'], 0, 10), "d-M-Y") . " ";
$strCreatorName = (isset($arrUserList[$arrData['created_by']])) ? $arrUserList[$arrData['created_by']]['name'] . " " : " ";
$strVerifiedDate = pgDateFormat(substr($arrData['verified_time'], 0, 10), "d-M-Y") . " ";
$strVerifiedName = (isset($arrUserList[$arrData['verified_by']])) ? $arrUserList[$arrData['verified_by']]['name'] . " " : " ";
$strApprovedDate = pgDateFormat(substr($arrData['approved_time'], 0, 10), "d-M-Y") . " ";
$strApprovedName = (isset($arrUserList[$arrData['approved_by']])) ? $arrUserList[$arrData['approved_by']]['name'] . " " : " ";
$strDirectorDate = pgDateFormat(substr($arrData['dir_approval_time'], 0, 10), "d-M-Y") . " ";
$strDirectorName = (isset($arrUserList[$arrData['dir_approval_by']])) ? $arrUserList[$arrData['dir_approval_by']]['name'] . " " : " ";
//cek box, mainkan kelasnya, jika true = kotakX, false = kotak
define("CSS_TRUE", "kotakX");
define("CSS_FALSE", "kotak");
if ($arrData['request_type'] == 1) // sub
{
  $strDataTypeAdd = "";
  $strDataTypeSub = $checked;
} else {
  $strDataTypeAdd = $checked;
  $strDataTypeSub = "";
}
if ($arrData['gender'] == 0 || $arrData['gender'] == 0) {
  $strDataGenderM = $strDataGenderF = $checked;
} else {
  $strDataGenderM = ($arrData['gender'] == 1) ? $checked : "";
  $strDataGenderF = ($arrData['gender'] == 0) ? $checked : "";
}
$strDataStatusP = ($arrData['employee_status'] == 1) ? $checked : "";
$strDataStatusC = ($arrData['employee_status'] == 0) ? $checked : "";
$strDataBudgetTrue = ($arrData['budget_type'] == 0) ? $checked : "";
$strDataBudgetFalse = ($arrData['budget_type'] == 1) ? $checked : "";
$tbsPage = new clsTinyButStrong;
$strPageTitle = getWords("print mrf");
$strTemplateFile = getTemplate('recruitment_print.html');
//candidate user
$tbsPage->LoadTemplate($strTemplateFile);
//$tbsPage->Show(TBS_NOTHING) ;
$tbsPage->Show();
//--------------------------------------------------------------------------------
function getData($strDataID)
{
  global $tblMRF;
  $tblRel = new cModel("hrd_company", "company");
  if ($strDataID != "") {
    if ($rowDb = $tblMRF->findById($strDataID)) {
      if ($rowDb['id_company'] != "") {
        $arrTmp = $tblRel->find(["id" => $rowDb['id_company']], "company_name");
        if (isset($arrTmp['company_name'])) {
          $rowDb['company_name'] = $arrTmp['company_name'];
        }
      }
      if (!isset($rowDb['company_name'])) {
        $rowDb['company_name'] = "";
      }
      return $rowDb;
    }
  }
  // kalau gak ketemu
  $arrData = $tblMRF->getEmptyRecord(); // inisialisasi
  $arrData = $tblCandidate->getEmptyRecord();
  $arrData['company_name'] = "";
  return $arrData;
}

?>