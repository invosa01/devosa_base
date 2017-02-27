<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_cost_center.php');
$dataPrivilege = getDataPrivileges(
    basename('data_cost_center.php'),
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
global $strDataID;
global $db;
$strDataID = $_REQUEST['dataID'];
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
  $arrDataManagement = [];
  $strSQL = "SELECT attribute_value FROM hrd_cost_center_member WHERE attribute_type = 1 AND id_cost_center = $strDataID";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDataManagement = unserialize($rowTmp['attribute_value']);
  }
  $arrDataDivision = [];
  $strSQL = "SELECT attribute_value FROM hrd_cost_center_member WHERE attribute_type = 2 AND id_cost_center = $strDataID";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDataDivision = unserialize($rowTmp['attribute_value']);
  }
  $arrDataDepartment = [];
  $strSQL = "SELECT attribute_value FROM hrd_cost_center_member WHERE attribute_type = 3 AND id_cost_center = $strDataID";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDataDepartment = unserialize($rowTmp['attribute_value']);
  }
  $arrDataSection = [];
  $strSQL = "SELECT attribute_value FROM hrd_cost_center_member WHERE attribute_type = 4 AND id_cost_center = $strDataID";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDataSection = unserialize($rowTmp['attribute_value']);
  }
  $arrDataSubSection = [];
  $strSQL = "SELECT attribute_value FROM hrd_cost_center_member WHERE attribute_type = 5 AND id_cost_center = $strDataID";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDataSubSection = unserialize($rowTmp['attribute_value']);
  }
  $arrDataBranch = [];
  $strSQL = "SELECT attribute_value FROM hrd_cost_center_member WHERE attribute_type = 6 AND id_cost_center = $strDataID";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDataBranch = unserialize($rowTmp['attribute_value']);
  }
  $strInputManagement = "<select class=\"form-control select2\" id=\"dataManagement\" name=\"dataManagement[]\" multiple=multiple>";
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
  $strInputDivision = "<select class=\"form-control select2\" id=\"dataDivision\" name=\"dataDivision[]\" multiple=\"multiple\">";
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
  $strInputDepartment = "<select class=\"form-control select2\" id=\"dataDepartment\" name=\"dataDepartment[]\" multiple=multiple >";
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
  $strInputSection = "<select class=\"form-control select2\" id=\"dataSection\" name=\"dataSection[]\" multiple=multiple >";
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
  $strInputSubSection = "<select class=\"form-control select2\" id=\"dataSubSection\" name=\"dataSubSection[]\" multiple=multiple>";
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
  $strInputBranch = "<select class=\"form-control select2\" id=\"dataBranch\" name=\"dataBranch[]\" multiple=multiple >";
  foreach ($arrBranch as $keys => $index) {
    if (in_array($keys, $arrDataBranch)) {
      $selected = "selected";
    } else {
      $selected = "";
    }
    $strInputBranch .= "<option value = \"$keys\" $selected>" . $index['name'] . "</option>";
  }
  $strInputBranch .= "</select>";
  //
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
  $strSQL = "";
  if ($strDataManagement != "empty") {
    $strSQL .= "DELETE FROM hrd_cost_center_member WHERE id_cost_center = $strDataID AND attribute_type = 1;";
    $strSQL .= "INSERT INTO hrd_cost_center_member (id_cost_center, attribute_type, attribute_value) VALUES ($strDataID, 1, '$strDataManagement');";
  }
  if ($strDataDivision != "empty") {
    $strSQL .= "DELETE FROM hrd_cost_center_member WHERE id_cost_center = $strDataID AND attribute_type = 2;";
    $strSQL .= "INSERT INTO hrd_cost_center_member (id_cost_center, attribute_type, attribute_value) VALUES ($strDataID, 2, '$strDataDivision');";
  }
  if ($strDataDepartment != "empty") {
    $strSQL .= "DELETE FROM hrd_cost_center_member WHERE id_cost_center = $strDataID AND attribute_type = 3;";
    $strSQL .= "INSERT INTO hrd_cost_center_member (id_cost_center, attribute_type, attribute_value) VALUES ($strDataID, 3, '$strDataDepartment');";
  }
  if ($strDataSection != "empty") {
    $strSQL .= "DELETE FROM hrd_cost_center_member WHERE id_cost_center = $strDataID AND attribute_type = 4;";
    $strSQL .= "INSERT INTO hrd_cost_center_member (id_cost_center, attribute_type, attribute_value) VALUES ($strDataID, 4, '$strDataSection');";
  }
  if ($strDataSubSection != "empty") {
    $strSQL .= "DELETE FROM hrd_cost_center_member WHERE id_cost_center = $strDataID AND attribute_type = 5;";
    $strSQL .= "INSERT INTO hrd_cost_center_member (id_cost_center, attribute_type, attribute_value) VALUES ($strDataID, 5, '$strDataSubSection');";
  }
  if ($strDataBranch != "empty") {
    $strSQL .= "DELETE FROM hrd_cost_center_member WHERE id_cost_center = $strDataID AND attribute_type = 6;";
    $strSQL .= "INSERT INTO hrd_cost_center_member (id_cost_center, attribute_type, attribute_value) VALUES ($strDataID, 6, '$strDataBranch');";
  }
  $db->execute($strSQL);
  header("Refresh:0");
}//function saveData
//-------MAIN---------------------------//
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
  if ($_REQUEST['btnSave']) {
    saveData($db);
  }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('data cost center member page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
//--------------------------------------------------------------------------------
?>