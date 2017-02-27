<?php
include_once('../global/session.php');
include_once('global.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$bolPrint = (isset($_REQUEST['btnPrint']));
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
$strWordsDepartmentData = getWords("organizational structure");
$strWordsInputData = getWords("input data");
$strWordsDepartment = getWords("chart / tree");
$strWordsManagementCode = getWords("management code");
$strWordsDivisionCode = getWords("division code");
$strWordsDepartmentCode = getWords("department code");
$strWordsSubDepartmentCode = getWords("sub department code");
$strWordsSectionCode = getWords("section code");
$strWordsSubSectionCode = getWords("sub section code");
$strWordsName = getWords("name");
$strWordsSave = getWords("save");
$strWordsClearForm = getWords("clear form");
$strWordsLISTOFDIVISIONDEPARTMENTANDSECTION = getWords("list of division, departmen, sub department and section");
$strWordsMANAGEMENT = getWords("management");
$strWordsDIVISION = getWords("division");
$strWordsCODE = getWords("code");
$strWordsNAME = getWords("name");
$strWordsHOLIDAY = getWords("holiday");
$strWordsSTARTTIME = getWords("start time");
$strWordsFINISHTIME = getWords("finish time");
$strWordsDEPARTMENT = getWords("department");
$strWordsSUBDEPARTMENT = getWords("sub department");
$strWordsSECTION = getWords("section");
$strWordsSUBSECTION = getWords("sub section");
$strWordsDelete = getWords("delete");
$strWordsGroup = getWords("group");
$strButtonList = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $words;
  $intRows = 0;
  $strResult = "";
  $strSQL = "SELECT * FROM hrd_management ";
  $strSQL .= "WHERE management_code LIKE '" . getCompanyCode() . "%' $strKriteria ORDER BY $strOrder management_code ";
  $resMan = $db->execute($strSQL);
  $strWordEdit = "<img src=\"../images/b_edit.png\" border=0 alt='Edit' />";
  while ($rowMan = $db->fetchrow($resMan)) {
    $intRows++;
    $strResult .= "<tr valign=top>\n";
    $strResult .= "  <td><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" type=\"checkbox\" name='chkManagementID$intRows' value=\"" . $rowMan['id'] . "\"></label></div></td>\n";
    $strResult .= "  <td nowrap><input type=hidden name=detailManagementCode$intRows value=\"" . $rowMan['management_code'] . "\" disabled>" . $rowMan['management_code'] . "&nbsp;</td>\n";
    $strResult .= "  <td><input type=hidden name=detailManagementName$intRows value=\"" . $rowMan['management_name'] . "\" disabled>" . $rowMan['management_name'] . "&nbsp;</td>\n";
    $strResult .= "  <td nowrap align=center><a href='javascript:editDataManagement($intRows)'>" . $strWordEdit . "</a>&nbsp;</td>\n";
    $strResult .= "  <td nowrap align=center><a href='javascript:addDataDivision($intRows)'><img src=\"../images/add.png\" border=0 alt='" . getWords(
            "add"
        ) . "' title='" . $words['add department'] . "' /></a>&nbsp;</td>\n";
    $intDiv = 0;
    $strSQL = "SELECT * FROM hrd_division ";
    $strSQL .= "WHERE management_code = '" . $rowMan['management_code'] . "' ";
    $resDiv = $db->execute($strSQL);
    //$bolSatHol = ($rowDiv['saturday_holiday'] == 't') ? "&radic;" : "";
    $strWordEdit = "<img src=\"../images/b_edit.png\" border=0 alt='Edit' />";
    while ($rowDiv = $db->fetchrow($resDiv)) {
      $intDiv++;
      if ($intDiv > 1) { //baris baru
        $intRows++;
        $strResult .= "<tr valign=top>\n";
        $strHidden = "<input type=hidden name='chkManagementID$intRows' value=\"" . $rowMan['id'] . "\" disabled>";
        $strHidden .= "<input type=hidden name='detailManagementCode$intRows' value=\"" . $rowMan['management_code'] . "\" disabled>";
        $strHidden .= "<input type=hidden name='detailManagementName$intRows' value=\"" . $rowMan['management_name'] . "\" disabled>";
        $strResult .= "<td colspan=5>$strHidden&nbsp;</td>\n";
      }
      $strResult .= "  <td><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" type=\"checkbox\" name='chkDivisionID$intRows' value=\"" . $rowDiv['id'] . "\"></label></div></td>\n";
      $strResult .= "  <td nowrap><input type=hidden name=detailDivisionCode$intRows value=\"" . $rowDiv['division_code'] . "\" disabled>" . $rowDiv['division_code'] . "&nbsp;</td>\n";
      $strResult .= "  <td><input type=hidden name=detailDivisionName$intRows value=\"" . $rowDiv['division_name'] . "\" disabled>" . $rowDiv['division_name'] . "&nbsp;</td>\n";
      $strResult .= "  <td nowrap align=center><a href='javascript:editDataDivision($intRows)'>" . $strWordEdit . "</a>&nbsp;</td>\n";
      $strResult .= "  <td nowrap align=center><a href='javascript:addData($intRows)'><img src=\"../images/add.png\" border=0 alt='" . getWords(
              "add"
          ) . "' title='" . $words['add department'] . "' /></a>&nbsp;</td>\n";
      $intDept = 0;
      $strSQL = "SELECT * FROM hrd_department ";
      $strSQL .= "WHERE division_code = '" . $rowDiv['division_code'] . "' ";
      $strSQL .= "ORDER BY $strOrder department_code ";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) {
        $intDept++;
        if ($intDept > 1) { //baris baru
          $intRows++;
          $strResult .= "<tr valign=top>\n";
          $strHidden = "<input type=hidden name='chkDivisionID$intRows' value=\"" . $rowDiv['id'] . "\" disabled>";
          $strHidden .= "<input type=hidden name='detailManagementCode$intRows' value=\"" . $rowMan['management_code'] . "\" disabled>";
          $strHidden .= "<input type=hidden name='detailManagementName$intRows' value=\"" . $rowMan['management_name'] . "\" disabled>";
          $strHidden .= "<input type=hidden name='detailDivisionCode$intRows' value=\"" . $rowDiv['division_code'] . "\" disabled>";
          $strHidden .= "<input type=hidden name='detailDivisionName$intRows' value=\"" . $rowDiv['division_name'] . "\" disabled>";
          $strResult .= "<td colspan=5>$strHidden&nbsp;</td>\n";
          $strResult .= "<td colspan=5>&nbsp;</td>\n";
        }
        $strResult .= "  <td><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" type=\"checkbox\" name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></label></div></td>\n";
        $strResult .= "  <td nowrap><input type=hidden name=detailCode$intRows value=\"" . $rowDb['department_code'] . "\" disabled>" . $rowDb['department_code'] . "&nbsp;</td>\n";
        $strResult .= "  <td><input type=hidden name=detailName$intRows value=\"" . $rowDb['department_name'] . "\" disabled>" . $rowDb['department_name'] . "&nbsp;</td>\n";
        $strResult .= "  <td nowrap align=center><a href='javascript:editData($intRows)'>" . $strWordEdit . "</a>&nbsp;</td>\n";
        //=========== sub department =====//
        $strResult .= "  <td nowrap align=center><a href='javascript:addDataSubDepartment($intRows)'><img src=\"../images/add.png\" border=0 alt='" . getWords(
                "add"
            ) . "' title='" . $words['add sub department'] . "' /></a>&nbsp;</td>\n";
        $intSubDepartment = 0;
        // cari data section
        $strSQL = "SELECT * FROM hrd_sub_department WHERE department_code = '" . $rowDb['department_code'] . "' ORDER BY sub_department_code ";
        $resSubDept = $db->execute($strSQL);
        while ($rowSubDept = $db->fetchrow($resSubDept)) {
          $intSubDepartment++;
          if ($intSubDepartment > 1) { //baris baru
            $intRows++;
            $strResult .= "<tr valign=top>\n";
            $strHidden = "<input type=hidden name='chkID$intRows' value=\"" . $rowDb['id'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailManagementCode$intRows' value=\"" . $rowMan['management_code'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailManagementName$intRows' value=\"" . $rowMan['management_name'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailDivisionCode$intRows' value=\"" . $rowDiv['division_code'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailDivisionName$intRows' value=\"" . $rowDiv['division_name'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailCode$intRows' value=\"" . $rowDb['department_code'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailName$intRows' value=\"" . $rowDb['department_name'] . "\" disabled>";
            $strResult .= "  <td colspan=5>$strHidden&nbsp;</td>\n";
            $strResult .= "  <td colspan=5>&nbsp;</td>\n";
            $strResult .= "  <td colspan=5>&nbsp;</td>\n";
          }
          //tampilkan data sub department
          $strResult .= "  <td><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" type=\"checkbox\" name='chkSubDepartmentID$intRows' value=\"" . $rowSubDept['id'] . "\"></label></div></td>\n";
          $strResult .= "  <td nowrap><input type=hidden name=detailSubDepartmentCode$intRows value=\"" . $rowSubDept['sub_department_code'] . "\" disabled>" . $rowSubDept['sub_department_code'] . "&nbsp;</td>\n";
          $strResult .= "  <td><input type=hidden name=detailSubDepartmentName$intRows value=\"" . $rowSubDept['sub_department_name'] . "\" disabled>" . $rowSubDept['sub_department_name'] . "&nbsp;</td>\n";
          $strResult .= "  <td nowrap align=center><a href='javascript:editDataSubDepartment($intRows)'>" . $strWordEdit . "</a>&nbsp;</td>\n";

          //======= end data sub department=========//
        $strResult .= "  <td nowrap align=center><a href='javascript:addDataSection($intRows)'><img src=\"../images/add.png\" border=0 alt='" . getWords(
                "add"
            ) . "' title='" . $words['add section'] . "' /></a>&nbsp;</td>\n";
        $intSection = 0;
        // cari data section
        //$strSQL = "SELECT * FROM hrd_section WHERE sub_department_code = '" . $rowSubDept['sub_department_code'] . "' ORDER BY section_code ";
        $strSQL = "SELECT * FROM hrd_section WHERE department_code = '" . $rowDb['department_code'] . "' ";
          $strSQL .= "AND sub_department_code = '" . $rowSubDept['sub_department_code'] . "' ORDER BY section_code ";
        $resSec = $db->execute($strSQL);
        while ($rowSec = $db->fetchrow($resSec)) {
          $intSection++;
          if ($intSection > 1) { //baris baru
            $intRows++;
            $strResult .= "<tr valign=top>\n";
            $strHidden = "<input type=hidden name='chkID$intRows' value=\"" . $rowDb['id'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailManagementCode$intRows' value=\"" . $rowMan['management_code'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailManagementName$intRows' value=\"" . $rowMan['management_name'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailDivisionCode$intRows' value=\"" . $rowDiv['division_code'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailDivisionName$intRows' value=\"" . $rowDiv['division_name'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailCode$intRows' value=\"" . $rowDb['department_code'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailName$intRows' value=\"" . $rowDb['department_name'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailSubDepartmentCode$intRows' value=\"" . $rowSubDept['sub_department_code'] . "\" disabled>";
            $strHidden .= "<input type=hidden name='detailSubDepartmentName$intRows' value=\"" . $rowSubDept['sub_department_name'] . "\" disabled>";
            $strResult .= "  <td colspan=5>$strHidden&nbsp;</td>\n";
            $strResult .= "  <td colspan=5>&nbsp;</td>\n";
            $strResult .= "  <td colspan=5>&nbsp;</td>\n";
          }
          //tampilkan dta section
          $strResult .= "  <td><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" type=\"checkbox\" name='chkSectionID$intRows' value=\"" . $rowSec['id'] . "\"></label></div></td>\n";
          $strResult .= "  <td nowrap><input type=hidden name=detailSectionCode$intRows value=\"" . $rowSec['section_code'] . "\" disabled>" . $rowSec['section_code'] . "&nbsp;</td>\n";
          $strResult .= "  <td><input type=hidden name=detailSectionName$intRows value=\"" . $rowSec['section_name'] . "\" disabled>" . $rowSec['section_name'] . "&nbsp;</td>\n";
          $strResult .= "  <td nowrap align=center><a href='javascript:editDataSection($intRows)'>" . $strWordEdit . "</a>&nbsp;</td>\n";
          $strResult .= "  <td nowrap align=center><a href='javascript:addDataSubSection($intRows)'><img src=\"../images/add.png\" border=0 alt='" . getWords(
                  "add"
              ) . "' title='" . $words['add subsection'] . "' /></a>&nbsp;</td>\n";
          //cari dta subsection
          $intSubSection = 0;
          $strSQL = "SELECT * FROM hrd_sub_section WHERE department_code = '" . $rowDb['department_code'] . "' ";
          $strSQL .= "AND sub_department_code = '" . $rowSubDept['sub_department_code'] . "' ";
          $strSQL .= "AND section_code = '" . $rowSec['section_code'] . "' ORDER BY sub_section_code ";
          $resSub = $db->execute($strSQL);
          while ($rowSub = $db->fetchrow($resSub)) {
            $intSubSection++;
            if ($intSubSection > 1) { //baris baru
              $intRows++;
              $strResult .= "<tr valign=top>\n";
              $strHidden = "<input type=hidden name='chkID$intRows' value=\"" . $rowDb['id'] . "\" disabled>";
              $strHidden .= "<input type=hidden name='detailManagementCode$intRows' value=\"" . $rowMan['management_code'] . "\" disabled>";
              $strHidden .= "<input type=hidden name='detailManagementName$intRows' value=\"" . $rowMan['management_name'] . "\" disabled>";
              $strHidden .= "<input type=hidden name='detailDivisionCode$intRows' value=\"" . $rowDiv['division_code'] . "\" disabled>";
              $strHidden .= "<input type=hidden name='detailDivisionName$intRows' value=\"" . $rowDiv['division_name'] . "\" disabled>";
              $strHidden .= "<input type=hidden name='detailCode$intRows' value=\"" . $rowDb['department_code'] . "\" disabled>";
              $strHidden .= "<input type=hidden name='detailName$intRows' value=\"" . $rowDb['department_name'] . "\" disabled>";
              $strHidden .= "<input type=hidden name='detailSubDepartmentCode$intRows' value=\"" . $rowSubDept['sub_department_code'] . "\" disabled>";
              $strHidden .= "<input type=hidden name='detailSubDepartment$intRows' value=\"" . $rowSubDept['sub_department_name'] . "\" disabled>";
              $strHidden .= "<input type=hidden name='detailSectionCode$intRows' value=\"" . $rowSec['section_code'] . "\" disabled>";
              $strHidden .= "<input type=hidden name='detailSectionName$intRows' value=\"" . $rowSec['section_name'] . "\" disabled>";
              $strResult .= "  <td colspan=5>$strHidden&nbsp;</td>\n";
              $strResult .= "  <td colspan=5>&nbsp;</td>\n";
              $strResult .= "  <td colspan=5>&nbsp;</td>\n";
              $strResult .= "  <td colspan=10>&nbsp;</td>\n";
            }
            // tampilkan data section
            $strResult .= "  <td><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" type=\"checkbox\" name='chkSubSectionID$intRows' value=\"" . $rowSub['id'] . "\"></label></div></td>\n";
            $strResult .= "  <td nowrap><input type=hidden name=detailSubSectionCode$intRows value=\"" . $rowSub['sub_section_code'] . "\" disabled>" . $rowSub['sub_section_code'] . "&nbsp;</td>\n";
            $strResult .= "  <td><input type=hidden name=detailSubSectionName$intRows value=\"" . $rowSub['sub_section_name'] . "\" disabled>" . $rowSub['sub_section_name'] . "&nbsp;</td>\n";
            $strResult .= "  <td nowrap align=center><a href='javascript:editDataSubSection($intRows)'>" . $strWordEdit . "</a>&nbsp;</td>\n";
            $strResult .= "</tr>\n";
          } // end cari subsection
          if ($intSubSection == 0) { //jika tidak ada subsection
            $strResult .= " <td colspan=5>&nbsp;</td>\n";
            $strResult .= "</tr>\n";
          }
        } // end cari data section
        if ($intSection == 0) { //jika tidak ada section
          $strResult .= " <td colspan=10>&nbsp;</td>\n";
          $strResult .= "</tr>\n";
        }
      }
        if ($intSubDepartment == 0) { //jika tidak ada sub department
          $strResult .= " <td colspan=15>&nbsp;</td>\n";
          $strResult .= "</tr>\n";
        }
      }
      if ($intDept == 0) { //jika tidak ada dept
        $strResult .= " <td colspan=20>&nbsp;</td>\n";
        $strResult .= "</tr>\n";
      }
    }
    if ($intDiv == 0) { //jika tidak ada dept
      $strResult .= " <td colspan=25>&nbsp;</td>\n";
      $strResult .= "</tr>\n";
    }
  }
  writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
  global $error;
  $strError = "";
  (isset($_REQUEST['dataType'])) ? $strDataType = $_REQUEST['dataType'] : $strDataType = "department";
  (isset($_REQUEST['dataCode'])) ? $strDataCode = trim($_REQUEST['dataCode']) : $strDataCode = "";
  (isset($_REQUEST['dataOldCode'])) ? $strDataOldCode = $_REQUEST['dataOldCode'] : $strDataOldCode = "";
  (isset($_REQUEST['dataName'])) ? $strDataName = $_REQUEST['dataName'] : $strDataName = "";
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  // cek validasi -----------------------
  if (strtolower($strDataType) == "management") {
    // --- EDIT DATA MANAGEMENT
    if ($strDataCode == "") {
      $strError = $error['empty_code'];
      return false;
    } else if ($strDataName == "") {
      $strError = $error['empty_name'];
      return false;
    } else {
      ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
      if (isDataExists($db, "hrd_management", "management_code", $strDataCode, $strKriteria)) {
        $strError = $error['duplicate_code'] . "  Management -> $strDataCode";
        return false;
      }
    }
    // simpan data -----------------------
    $data = [
        "management_code" => check_plain($strDataCode),
        "management_name" => check_plain($strDataName),
    ];
    $tbl = new cModel("hrd_management", "management");
    if ($strDataID == "") {
      $tbl->insert($data);
      // data baru
      writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataCode", 0);
    } else {
      $tbl->update(["id" => $strDataID], $data);
      writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataCode", 0);
      // update data2 dibawahnya, jika ada perubahan kode
      $strDataCode = check_plain($strDataCode);
      $strDataOldCode = check_plain($strDataOldCode);
      if ($strDataOldCode != $strDataCode) {
        $tbl->query(
            "
              UPDATE hrd_division SET management_code = '$strDataCode' 
              WHERE management_code = '$strDataOldCode'; 
              UPDATE hrd_department SET management_code = '$strDataCode' 
              WHERE management_code = '$strDataOldCode';
              UPDATE hrd_sub_department SET division_code = '$strDataCode'
              WHERE management_code = '$strDataOldCode';
              UPDATE hrd_section SET division_code = '$strDataCode' 
              WHERE management_code = '$strDataOldCode';
              UPDATE hrd_sub_section SET division_code = '$strDataCode' 
              WHERE management_code = '$strDataOldCode';
              UPDATE hrd_employee SET management_code = '$strDataCode' 
              WHERE management_code = '$strDataOldCode'"
        );
      }
    }
  } else if (strtolower($strDataType) == "division") {
    (isset($_REQUEST['dataManCode'])) ? $strDataManCode = $_REQUEST['dataManCode'] : $strDataManCode = "";
    // --- EDIT DATA DIVISION
    if ($strDataCode == "") {
      $strError = $error['empty_code'];
      return false;
    } else if ($strDataName == "") {
      $strError = $error['empty_name'];
      return false;
    } else {
      ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
      if (isDataExists($db, "hrd_division", "division_code", $strDataCode, $strKriteria)) {
        $strError = $error['duplicate_code'] . "  Division -> $strDataCode";
        return false;
      }
    }
    // simpan data -----------------------
    $data = [
        "division_code"   => $strDataCode,
        "division_name"   => $strDataName,
        "management_code" => $strDataManCode,
    ];
    $tbl = new cModel("hrd_division", "division");
    if ($strDataID == "") {
      $tbl->insert($data);
      // data baru
      writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataCode", 0);
    } else {
      $tbl->update(["id" => $strDataID], $data);
      writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataCode", 0);
      // update data2 dibawahnya, jika ada perubahan kode
      if ($strDataOldCode != $strDataCode) {
        $tbl->query(
            "
              UPDATE hrd_department SET division_code = '$strDataCode' 
              WHERE division_code = '$strDataOldCode';
              UPDATE hrd_sub_department SET division_code = '$strDataCode'
              WHERE division_code = '$strDataOldCode';
              UPDATE hrd_section SET division_code = '$strDataCode' 
              WHERE division_code = '$strDataOldCode';
              UPDATE hrd_sub_section SET division_code = '$strDataCode' 
              WHERE division_code = '$strDataOldCode';
              UPDATE hrd_employee SET division_code = '$strDataCode' 
              WHERE division_code = '$strDataOldCode'"
        );
      }
    }
  } else if (strtolower($strDataType) == "department") {
    // --- EDIT DATA DEPARTMENT
    (isset($_REQUEST['dataManCode'])) ? $strDataManCode = $_REQUEST['dataManCode'] : $strDataManCode = "";
    (isset($_REQUEST['dataDivCode'])) ? $strDataDivCode = $_REQUEST['dataDivCode'] : $strDataDivCode = "";
    if ($strDataCode == "") {
      $strError = $error['empty_code'];
      return false;
    } else if ($strDataName == "") {
      $strError = $error['empty_name'];
      return false;
    } else {
      ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
      if (isDataExists($db, "hrd_department", "department_code", $strDataCode, $strKriteria)) {
        $strError = $error['duplicate_code'] . "  Department -> $strDataCode";
        return false;
      }
    }
    $data = [
        "department_code" => $strDataCode,
        "department_name" => $strDataName,
        "division_code"   => $strDataDivCode,
        "management_code" => $strDataManCode,
    ];
    $tbl = new cModel("hrd_department", "department");
    // simpan data -----------------------
    if ($strDataID == "") {
      // data baru
      $tbl->insert($data);
      writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataCode", 0);
    } else {
      $tbl->update(["id" => $strDataID], $data);
      writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataCode", 0);
      // update data2 dibawahnya, jika ada perubahan kode
      if ($strDataOldCode != $strDataCode) {
        $tbl->query(
            "
              UPDATE hrd_sub_department SET department_code = '$strDataCode'
              WHERE department_code = '$strDataOldCode';
              UPDATE hrd_section SET department_code = '$strDataCode'
              WHERE department_code = '$strDataOldCode';
              UPDATE hrd_sub_section SET department_code = '$strDataCode' 
              WHERE department_code = '$strDataOldCode';
              UPDATE hrd_employee SET department_code = '$strDataCode' 
              WHERE department_code = '$strDataOldCode'"
        );
      }
    }
  } else if (strtolower($strDataType) == "subdepartment") { // --- EDIT DATA SECTION
    (isset($_REQUEST['dataManCode'])) ? $strDataManCode = $_REQUEST['dataManCode'] : $strDataManCode = "";
    (isset($_REQUEST['dataDivCode'])) ? $strDataDivCode = $_REQUEST['dataDivCode'] : $strDataDivCode = "";
    (isset($_REQUEST['dataDeptCode'])) ? $strDataDeptCode = $_REQUEST['dataDeptCode'] : $strDataDeptCode = "";
    if ($strDataCode == "") {
      $strError = $error['empty_code'];
      return false;
    } else if ($strDataName == "") {
      $strError = $error['empty_name'];
      return false;
    } else {
      ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
      if (isDataExists($db, "hrd_sub_department", "sub_department_code", $strDataCode, $strKriteria)) {
        $strError = $error['duplicate_code'] . "  Sub Department -> $strDataCode";
        return false;
      }
    }
    $data = [
        "sub_department_code"    => $strDataCode,
        "sub_department_name"    => $strDataName,
        "department_code" => $strDataDeptCode,
        "division_code"   => $strDataDivCode,
        "management_code" => $strDataManCode,
    ];
    $tbl = new cModel("hrd_sub_department", "subdepartment");
    // simpan data -----------------------
    if ($strDataID == "") {
      // data baru
      $tbl->insert($data);
      writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataCode", 0);
    } else {
      $tbl->update(["id" => $strDataID], $data);
      writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataCode", 0);
      // update data2 dibawahnya, jika ada perubahan kode
      if ($strDataOldCode != $strDataCode) {
        $tbl->query(
            "
            UPDATE hrd_section SET section_code = '$strDataCode'
              WHERE section_code = '$strDataOldCode';
            UPDATE hrd_sub_section SET section_code = '$strDataCode'
              WHERE section_code = '$strDataOldCode';
            UPDATE hrd_employee SET section_code = '$strDataCode' 
              WHERE section_code = '$strDataOldCode'"
        );
      }
    }
  } else if (strtolower($strDataType) == "section") { // --- EDIT DATA SECTION
    (isset($_REQUEST['dataManCode'])) ? $strDataManCode = $_REQUEST['dataManCode'] : $strDataManCode = "";
    (isset($_REQUEST['dataDivCode'])) ? $strDataDivCode = $_REQUEST['dataDivCode'] : $strDataDivCode = "";
    (isset($_REQUEST['dataDeptCode'])) ? $strDataDeptCode = $_REQUEST['dataDeptCode'] : $strDataDeptCode = "";
    (isset($_REQUEST['dataSubDeptCode'])) ? $strDataSubDeptCode = $_REQUEST['dataSubDeptCode'] : $strDataSubDeptCode = "";
    if ($strDataCode == "") {
      $strError = $error['empty_code'];
      return false;
    } else if ($strDataName == "") {
      $strError = $error['empty_name'];
      return false;
    } else {
      ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
      if (isDataExists($db, "hrd_section", "section_code", $strDataCode, $strKriteria)) {
        $strError = $error['duplicate_code'] . "  Section -> $strDataCode";
        return false;
      }
    }
    $data = [
        "section_code"    => $strDataCode,
        "section_name"    => $strDataName,
        "sub_department_code" => $strDataSubDeptCode,
        "department_code" => $strDataDeptCode,
        "division_code"   => $strDataDivCode,
        "management_code" => $strDataManCode,
    ];
    $tbl = new cModel("hrd_section", "section");
    // simpan data -----------------------
    if ($strDataID == "") {
      // data baru
      $tbl->insert($data);
      writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataCode", 0);
    } else {
      $tbl->update(["id" => $strDataID], $data);
      writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataCode", 0);
      // update data2 dibawahnya, jika ada perubahan kode
      if ($strDataOldCode != $strDataCode) {
        $tbl->query(
            "
            UPDATE hrd_sub_section SET section_code = '$strDataCode'
              WHERE section_code = '$strDataOldCode';
            UPDATE hrd_employee SET section_code = '$strDataCode'
              WHERE section_code = '$strDataOldCode'"
        );
      }
    }
  } else if (strtolower($strDataType) == "subsection") { // --- EDIT DATA SUBSECTION
    (isset($_REQUEST['dataManCode'])) ? $strDataManCode = $_REQUEST['dataManCode'] : $strDataManCode = "";
    (isset($_REQUEST['dataDivCode'])) ? $strDataDivCode = $_REQUEST['dataDivCode'] : $strDataDivCode = "";
    (isset($_REQUEST['dataDeptCode'])) ? $strDataDeptCode = $_REQUEST['dataDeptCode'] : $strDataDeptCode = "";
    (isset($_REQUEST['dataSectCode'])) ? $strDataSectCode = $_REQUEST['dataSectCode'] : $strDataSectCode = "";
    (isset($_REQUEST['dataSubDeptCode'])) ? $strDataSubDeptCode = $_REQUEST['dataSubDeptCode'] : $strDataSubDeptCode = "";
    //(isset($_REQUEST['dataOvertime'])) ? $strDataOvertime = $_REQUEST['dataOvertime'] : $strDataOvertime = "f";
    if ($strDataCode == "") {
      $strError = $error['empty_code'];
      return false;
    } else if ($strDataName == "") {
      $strError = $error['empty_name'];
      return false;
    } else {
      ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
      if (isDataExists($db, "hrd_sub_section", "sub_section_code", $strDataCode, $strKriteria)) {
        $strError = $error['duplicate_code'] . " Sub Section -> $strDataCode";
        return false;
      }
    }
    $data = [
        "sub_section_code" => $strDataCode,
        "sub_section_name" => $strDataName,
        "section_code"     => $strDataSectCode,
        "sub_department_code"  => $strDataSubDeptCode,
        "department_code"  => $strDataDeptCode,
        "division_code"    => $strDataDivCode,
        "management_code"  => $strDataManCode,
    ];
    $tbl = new cModel("hrd_sub_section", "sub section");
    // simpan data -----------------------
    if ($strDataID == "") {
      // data baru
      $tbl->insert($data);
      writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataCode", 0);
    } else {
      $tbl->update(["id" => $strDataID], $data);
      writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataCode", 0);
    }
  }
  return true;
} // saveData
// fungsi untuk menghapus data
function deleteData($db)
{
  global $_REQUEST;
  $i = 0;
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 15) == 'chkManagementID') {
      // cari dulu data Department code
      $strSQL = "SELECT * FROM hrd_management WHERE id = '$strValue' ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['management_code'];
        $strSQL = "DELETE FROM hrd_division WHERE management_code = '$strCode' ";
        $resExec = $db->execute($strSQL);
        $strSQL = "DELETE FROM hrd_department WHERE management_code = '$strCode' ";
        $resExec = $db->execute($strSQL);
        $strSQL = "DELETE FROM hrd_section WHERE management_code = '$strCode' ";
        $resExec = $db->execute($strSQL);
        $strSQL = "DELETE FROM hrd_sub_section WHERE management_code = '$strCode' ";
        $resExec = $db->execute($strSQL);
      }
      $strSQL = "DELETE FROM hrd_management WHERE id = '$strValue' ";
      $resExec = $db->execute($strSQL);
      $i++;
    }
    if (substr($strIndex, 0, 13) == 'chkDivisionID') {
      // cari dulu data Department code
      $strSQL = "SELECT * FROM hrd_division WHERE id = '$strValue' ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['division_code'];
        $strSQL = "DELETE FROM hrd_department WHERE division_code = '$strCode' ";
        $resExec = $db->execute($strSQL);
        $strSQL = "DELETE FROM hrd_section WHERE division_code = '$strCode' ";
        $resExec = $db->execute($strSQL);
        $strSQL = "DELETE FROM hrd_sub_section WHERE division_code = '$strCode' ";
        $resExec = $db->execute($strSQL);
      }
      $strSQL = "DELETE FROM hrd_division WHERE id = '$strValue' ";
      $resExec = $db->execute($strSQL);
      $i++;
    }
    if (substr($strIndex, 0, 5) == 'chkID') {
      // cari dulu data Department code
      $strSQL = "SELECT * FROM hrd_department WHERE id = '$strValue' ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['department_code'];
        $strSQL = "DELETE FROM hrd_section WHERE department_code = '$strCode' ";
        $resExec = $db->execute($strSQL);
        $strSQL = "DELETE FROM hrd_sub_section WHERE department_code = '$strCode' ";
        $resExec = $db->execute($strSQL);
      }
      $strSQL = "DELETE FROM hrd_department WHERE id = '$strValue' ";
      $resExec = $db->execute($strSQL);
      $i++;
    } else if (substr($strIndex, 0, 12) == 'chkSectionID') { // hapus section
      // cari dulu data section code
      $strSQL = "SELECT * FROM hrd_section WHERE id = '$strValue' ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['section_code'];
        $strCode1 = $rowDb['department_code'];
        $strSQL = "DELETE FROM hrd_sub_section WHERE section_code = '$strCode' ";
        $strSQL .= "AND department_code = '$strCode1' ";
        $resExec = $db->execute($strSQL);
      }
      $strSQL = "DELETE FROM hrd_section WHERE id = '$strValue' ";
      $resExec = $db->execute($strSQL);
      $i++;
    } else if (substr($strIndex, 0, 15) == 'chkSubSectionID') { // hapus sub section
      $strSQL = "DELETE FROM hrd_sub_section WHERE id = '$strValue' ";
      $resExec = $db->execute($strSQL);
      $i++;
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
  }
} //deleteData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  if (isset($_REQUEST['btnSave'])) {
    if ($bolCanEdit) {
      if (!saveData($db, $strError)) {
        echo "<script>alert(\"$strError\")</script>";
      }
    } else {
      echo "<script>alert(\"Sorry, you do not have authority to modify data in this page\")</script>";
    }
  } else if (isset($_REQUEST['btnDelete'])) {
    if ($bolCanDelete) {
      deleteData($db);
    }
  }
  if ($bolCanView) {
    $strDataDetail = getData($db, $intTotalData);
  } else {
    showError("view_denied");
  }
}
$strButtonList = generateRoleButtons($bolCanEdit, $bolCanDelete, $bolCanCheck, $bolCanApprove);
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords(strtolower($dataPrivilege['menu_name']));
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('departement management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = organizationChartSubmenu($strWordsInputData);
//------------------------------------------------
//Load Master Template
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>