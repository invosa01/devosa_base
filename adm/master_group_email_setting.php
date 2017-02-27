<?php
include_once('../global/session.php');
include_once("../global.php");
//include_once('adminFunc.php');
include_once('../global/handledata.php');
$dataPrivilege = getDataPrivileges("master_group.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$strDataID = getRequestValue("dataID");
if ($strDataID == "") {
  header("location:master_group.php");
}
function getGroupName($db)
{
  global $strDataID;
  $strSQL = "SELECT name FROM adm_group WHERE id_adm_group = $strDataID";
  $resExec = $db->execute($strSQL);
  $result = $db->fetchrow($resExec);
  return $result['name'];
}

function getData($db)
{
  global $strDataID;
  global $arrManagement;
  global $arrDivision;
  global $arrDepartment;
  global $arrSection;
  global $arrSubSection;
  global $arrBranch;
  global $strInputManagement;
  global $strInputDivision;
  global $strInputDepartment;
  global $strInputSection;
  global $strInputSubSection;
  global $strInputBranch;
  global $strInputNewOT;
  global $strInputCheckOT;
  global $strInputApproveOT;
  global $strInputNewAbsence;
  global $strInputCheckAbsence;
  global $strInputApproveAbsence;
  global $strInputNewEmployee;
  global $strInputCheckEmployee;
  global $strInputApproveEmployee;
  global $strInputNewRecruitment;
  global $strInputCheckRecruitment;
  global $strInputApproveRecruitment;
  $arrDataManagement = [];
  $strSQL = "SELECT management FROM adm_email_setting WHERE id_adm_group = $strDataID";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDataManagement = unserialize($rowTmp['management']);
  }
  $arrDataDivision = [];
  $strSQL = "SELECT division FROM adm_email_setting WHERE id_adm_group = $strDataID";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDataDivision = unserialize($rowTmp['division']);
  }
  $arrDataDepartment = [];
  $strSQL = "SELECT department FROM adm_email_setting WHERE id_adm_group = $strDataID";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDataDepartment = unserialize($rowTmp['department']);
  }
  $arrDataSection = [];
  $strSQL = "SELECT section FROM adm_email_setting WHERE id_adm_group = $strDataID";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDataSection = unserialize($rowTmp['section']);
  }
  $arrDataSubSection = [];
  $strSQL = "SELECT sub_section FROM adm_email_setting WHERE id_adm_group = $strDataID";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDataSubSection = unserialize($rowTmp['sub_section']);
  }
  $arrDataBranch = [];
  $strSQL = "SELECT branch FROM adm_email_setting WHERE id_adm_group = $strDataID";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDataBranch = unserialize($rowTmp['branch']);
  }
  $strInputManagement = "<select id=\"dataManagement\" name=\"dataManagement[]\" multiple=multiple style=\"margin: 20px;width:300px;\">";
  foreach ($arrManagement as $keys => $index) {
    if (in_array($keys, $arrDataManagement)) {
      $selected = "selected";
    } else {
      $selected = "";
    }
    $strInputManagement .= "<option value = \"$keys\" $selected>" . $index['name'] . "</option>";
  }
  $strInputManagement .= "</select>";
  //
  $strInputDivision = "<select id=\"dataDivision\" name=\"dataDivision[]\" multiple=multiple style=\"margin: 20px;width:300px;\">";
  foreach ($arrDivision as $keys => $index) {
    if (in_array($keys, $arrDataDivision)) {
      $selected = "selected";
    } else {
      $selected = "";
    }
    $strInputDivision .= "<option value = \"$keys\" $selected>" . $index['name'] . "</option>";
  }
  $strInputDivision .= "</select>";
  //
  $strInputDepartment = "<select id=\"dataDepartment\" name=\"dataDepartment[]\" multiple=multiple style=\"margin: 20px;width:300px;\">";
  foreach ($arrDepartment as $keys => $index) {
    if (in_array($keys, $arrDataDepartment)) {
      $selected = "selected";
    } else {
      $selected = "";
    }
    $strInputDepartment .= "<option value = \"$keys\" $selected>" . $index['name'] . "</option>";
  }
  $strInputDepartment .= "</select>";
  //
  $strInputSection = "<select id=\"dataSection\" name=\"dataSection[]\" multiple=multiple style=\"margin: 20px;width:300px;\">";
  foreach ($arrSection as $keys => $index) {
    if (in_array($keys, $arrDataSection)) {
      $selected = "selected";
    } else {
      $selected = "";
    }
    $strInputSection .= "<option value = \"$keys\" $selected>" . $index['name'] . "</option>";
  }
  $strInputSection .= "</select>";
  //
  $strInputSubSection = "<select id=\"dataSubSection\" name=\"dataSubSection[]\" multiple=multiple style=\"margin: 20px;width:300px;\">";
  foreach ($arrSubSection as $keys => $index) {
    if (in_array($keys, $arrDataSubSection)) {
      $selected = "selected";
    } else {
      $selected = "";
    }
    $strInputSubSection .= "<option value = \"$keys\" $selected>" . $index['name'] . "</option>";
  }
  $strInputSubSection .= "</select>";
  //
  $strInputBranch = "<select id=\"dataBranch\" name=\"dataBranch[]\" multiple=multiple style=\"margin: 20px;width:300px;\">";
  foreach ($arrBranch as $keys => $index) {
    if (in_array($keys, $arrDataBranch)) {
      $selected = "selected";
    } else {
      $selected = "";
    }
    $strInputBranch .= "<option value = \"$keys\" $selected>" . $index['name'] . "</option>";
  }
  $strInputBranch .= "</select>";
  $strSQL = "SELECT new_ot, new_absence, new_employee, new_recruitment, check_ot, check_absence, check_employee, check_recruitment, approve_ot, approve_absence, approve_employee, approve_recruitment FROM adm_email_setting WHERE id_adm_group = $strDataID";
  $resExec = $db->execute($strSQL);
  $result = $db->fetchrow($resExec);
  $strChecked = ($result['new_ot'] == 't') ? 'checked' : '';
  $strInputNewOT = "<input class=\"checkbox-inline\" type=checkbox name=dataNewOT $strChecked>";
  $strChecked = ($result['new_absence'] == 't') ? 'checked' : '';
  $strInputNewAbsence = "<input class=\"checkbox-inline\" type=checkbox name=dataNewAbsence $strChecked>";
  $strChecked = ($result['new_employee'] == 't') ? 'checked' : '';
  $strInputNewEmployee = "<input class=\"checkbox-inline\" type=checkbox name=dataNewEmployee $strChecked>";
  $strChecked = ($result['new_recruitment'] == 't') ? 'checked' : '';
  $strInputNewRecruitment = "<input class=\"checkbox-inline\" type=checkbox name=dataNewRecruitment $strChecked>";
  $strChecked = ($result['check_ot'] == 't') ? 'checked' : '';
  $strInputCheckOT = "<input class=\"checkbox-inline\" type=checkbox name=dataCheckOT $strChecked>";
  $strChecked = ($result['check_absence'] == 't') ? 'checked' : '';
  $strInputCheckAbsence = "<input class=\"checkbox-inline\" type=checkbox name=dataCheckAbsence $strChecked>";
  $strChecked = ($result['check_employee'] == 't') ? 'checked' : '';
  $strInputCheckEmployee = "<input class=\"checkbox-inline\" type=checkbox name=dataCheckEmployee $strChecked>";
  $strChecked = ($result['check_recruitment'] == 't') ? 'checked' : '';
  $strInputCheckRecruitment = "<input class=\"checkbox-inline\" type=checkbox name=dataCheckRecruitment $strChecked>";
  $strChecked = ($result['approve_ot'] == 't') ? 'checked' : '';
  $strInputApproveOT = "<input class=\"checkbox-inline\" type=checkbox name=dataApproveOT $strChecked>";
  $strChecked = ($result['approve_absence'] == 't') ? 'checked' : '';
  $strInputApproveAbsence = "<input class=\"checkbox-inline\" type=checkbox name=dataApproveAbsence $strChecked>";
  $strChecked = ($result['approve_employee'] == 't') ? 'checked' : '';
  $strInputApproveEmployee = "<input class=\"checkbox-inline\" type=checkbox name=dataApproveEmployee $strChecked>";
  $strChecked = ($result['approve_recruitment'] == 't') ? 'checked' : '';
  $strInputApproveRecruitment = "<input class=\"checkbox-inline\" type=checkbox name=dataApproveRecruitment $strChecked>";
}//function getData
function saveData($db)
{
  global $strDataID;
  //attribute type 1 = management, 2 = division, 3 = department, 4 = section, 5 = subsection, 6 = branch
  $strDataManagement = ($_REQUEST['dataManagement']) ? serialize($_REQUEST['dataManagement']) : "empty";
  $strDataDivision = ($_REQUEST['dataDivision']) ? serialize($_REQUEST['dataDivision']) : "empty";
  $strDataDepartment = ($_REQUEST['dataDepartment']) ? serialize($_REQUEST['dataDepartment']) : "empty";
  $strDataSection = ($_REQUEST['dataSection']) ? serialize($_REQUEST['dataSection']) : "empty";
  $strDataSubSection = ($_REQUEST['dataSubSection']) ? serialize($_REQUEST['dataSubSection']) : "empty";
  $strDataBranch = ($_REQUEST['dataBranch']) ? serialize($_REQUEST['dataBranch']) : "empty";
  $strDataNewOT = ($_REQUEST['dataNewOT'] == true) ? 't' : 'f';
  $strDataNewAbsence = ($_REQUEST['dataNewAbsence'] == true) ? 't' : 'f';
  $strDataNewEmployee = ($_REQUEST['dataNewEmployee'] == true) ? 't' : 'f';
  $strDataNewRecruitment = ($_REQUEST['dataNewRecruitment'] == true) ? 't' : 'f';
  $strDataCheckOT = ($_REQUEST['dataCheckOT'] == true) ? 't' : 'f';
  $strDataCheckAbsence = ($_REQUEST['dataCheckAbsence'] == true) ? 't' : 'f';
  $strDataCheckEmployee = ($_REQUEST['dataCheckEmployee'] == true) ? 't' : 'f';
  $strDataCheckRecruitment = ($_REQUEST['dataCheckRecruitment'] == true) ? 't' : 'f';
  $strDataApproveOT = ($_REQUEST['dataApproveOT'] == true) ? 't' : 'f';
  $strDataApproveAbsence = ($_REQUEST['dataApproveAbsence'] == true) ? 't' : 'f';
  $strDataApproveEmployee = ($_REQUEST['dataApproveEmployee'] == true) ? 't' : 'f';
  $strDataApproveRecruitment = ($_REQUEST['dataApproveRecruitment'] == true) ? 't' : 'f';
  $strSQL = "SELECT id_adm_group FROM adm_email_setting WHERE id_adm_group = $strDataID";
  $resExec = $db->execute($strSQL);
  $result = $db->fetchrow($resExec);
  $strSQL = "";
  if (!$result['id_adm_group']) {
    $strSQL = "INSERT INTO adm_email_setting(id_adm_group) VALUES($strDataID)";
    $db->execute($strSQL);
  }
  $strSQL = "UPDATE adm_email_setting SET management = '$strDataManagement' WHERE id_adm_group = $strDataID;";
  $strSQL .= "UPDATE adm_email_setting SET division = '$strDataDivision' WHERE id_adm_group = $strDataID;";
  $strSQL .= "UPDATE adm_email_setting SET department = '$strDataDepartment' WHERE id_adm_group = $strDataID;";
  $strSQL .= "UPDATE adm_email_setting SET section = '$strDataSection' WHERE id_adm_group = $strDataID;";
  $strSQL .= "UPDATE adm_email_setting SET sub_section = '$strDataSubSection' WHERE id_adm_group = $strDataID;";
  $strSQL .= "UPDATE adm_email_setting SET branch = '$strDataBranch' WHERE id_adm_group = $strDataID;";
  $strSQL .= "UPDATE adm_email_setting SET new_ot = '$strDataNewOT', new_absence = '$strDataNewAbsence', new_employee = '$strDataNewEmployee', new_recruitment = '$strDataNewRecruitment' WHERE id_adm_group = $strDataID;";
  $strSQL .= "UPDATE adm_email_setting SET check_ot = '$strDataCheckOT', check_absence = '$strDataCheckAbsence', check_employee = '$strDataCheckEmployee', check_recruitment = '$strDataCheckRecruitment' WHERE id_adm_group = $strDataID;";
  $strSQL .= "UPDATE adm_email_setting SET approve_ot = '$strDataApproveOT', approve_absence = '$strDataApproveAbsence', approve_employee = '$strDataApproveEmployee', approve_recruitment = '$strDataApproveRecruitment' WHERE id_adm_group = $strDataID;";
  $db->execute($strSQL);
  header("Refresh:0");
}//function saveData
$db = new CdbClass;
if ($db->connect()) {
  //start
  $arrSubSection = [];
  $strSQL = "SELECT sub_section_code, sub_section_name, section_code FROM hrd_sub_section ";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrSubSection[$rowTmp['sub_section_code']]['name'] = $rowTmp['sub_section_name'];
  }
  $arrSection = [];
  $strSQL = "SELECT section_code, section_name, department_code FROM hrd_section ";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrSection[$rowTmp['section_code']]['name'] = $rowTmp['section_name'];
  }
  $arrDepartment = [];
  $strSQL = "SELECT department_code, department_name, division_code FROM hrd_department";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDepartment[$rowTmp['department_code']]['name'] = $rowTmp['department_name'];
  }
  $arrDivision = [];
  $strSQL = "SELECT division_code, division_name, management_code FROM hrd_division";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDivision[$rowTmp['division_code']]['name'] = $rowTmp['division_name'];
  }
  $arrManagement = [];
  $strSQL = "SELECT management_code, management_name FROM hrd_management ";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrManagement[$rowTmp['management_code']]['name'] = $rowTmp['management_name'];
  }
  $arrBranch = [];
  $strSQL = "SELECT branch_code, branch_name FROM hrd_branch";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrBranch[$rowTmp['branch_code']]['name'] = $rowTmp['branch_name'];
  }
  //getData
  getData($db);
  $strWordsGroup = getGroupName($db);
  if ($_REQUEST['btnSave']) {
    saveData($db);
  }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
//--------------------------------------------------------------------------------
?>
