<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
$dataPrivilege = getDataPrivileges(
    "shift_schedule.php",
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
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strDataDetailSearch = "";
$strHidden = "";
$strGroupName = "";
$intTotalData = 0;
$intTotalDataSearch = 0;
$strDataID = "";
$strFilterEmployee = "";
$strFilterDepartment = "";
$strFilterSection = "";
$strKriteria = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data member dari Group
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $strDataID;
  $intRows = 0;
  $strResult = "";
  $strSQL = "SELECT t1.id, t2.employee_id, t2.employee_name FROM hrd_shift_group_member AS t1 ";
  $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
  $strSQL .= "WHERE \"id_group\" = '$strDataID' $strKriteria ORDER BY $strOrder employee_name ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strResult .= "<tr valign=top>\n";
    $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
    $strResult .= "  <td nowrap>" . $rowDb['employee_id'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$strDataID", 0);
  return $strResult;
} // showData
// menampilkan daftar employee hasil pencarian
function getDataSearch($db, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $strDataID;
  $intRows = 0;
  $strResult = "";
  if ($strKriteria == "") {
    $strKriteria = "AND 1=2"; // biar kosong, gak boleh tampil semua
  }
  $strSQL = "SELECT id,employee_id, employee_name FROM hrd_employee ";
  $strSQL .= "WHERE active=1 AND flag=0 $strKriteria ORDER BY $strOrder employee_name ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strResult .= "<tr valign=top>\n";
    $strResult .= "  <td><input type=checkbox name='chkIDSearch$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
    $strResult .= "  <td nowrap>" . $rowDb['employee_id'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  return $strResult;
} // showDataSearch
// fungsi untuk menyimpan data, untuk satu data saja
function addData($db, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $error;
  global $strDataID;
  $strError = "";
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
  // cek validasi -----------------------
  if ($strDataEmployee == "") {
    $strError = $error['empty_code'];
    return false;
  }
  // simpan data -----------------------
  if ($strDataID != "") {
    // cari data employee dulu, apakah ada atau tidak
    $strSQL = "SELECT id FROM hrd_employee WHERE employee_id = '$strDataEmployee' AND flag = 0 ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $stremployee_id = $rowDb['id'];
    } else {
      $strError = $error['data_not_found'];
      return false;
    }
    // hapus dulu jika sudah pernah terdaftar
    $strSQL = "DELETE FROM hrd_shift_group_member WHERE id_employee = '$stremployee_id' ";
    $resExec = $db->execute($strSQL);
    // tambahkan data
    $strSQL = "INSERT INTO hrd_shift_group_member (created,created_by,modified_by, ";
    $strSQL .= "id_employee, \"id_group\") ";
    $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
    $strSQL .= "'$stremployee_id','$strDataID') ";
    $resExec = $db->execute($strSQL);
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataID", 0);
  }
  return true;
} // addData
// fungsi untuk menambahkan beberapa data sekaligus
function addDataMore($db)
{
  global $_REQUEST;
  global $strDataID;
  $i = 0;
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 11) == 'chkIDSearch') {
      $strSQL = "DELETE FROM hrd_shift_group_member WHERE id_employee = '$strValue' ";
      $strSQL .= "AND \"id_group\" = '$strDataID' ";
      $resExec = $db->execute($strSQL);
      $strSQL = "INSERT INTO hrd_shift_group_member (created,created_by,modified_by, ";
      $strSQL .= "id_employee, \"id_group\") ";
      $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
      $strSQL .= "'$strValue','$strDataID') ";
      $resExec = $db->execute($strSQL);
      $i++;
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataID + $i data", 0);
  }
} //addDataMore
// fungsi untuk menghapus data
function deleteData($db)
{
  global $_REQUEST;
  $i = 0;
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 5) == 'chkID') {
      $strSQL = "DELETE FROM hrd_shift_group_member WHERE id = '$strValue' ";
      $resExec = $db->execute($strSQL);
      $i++;
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$strDataID - $i data", 0);
  }
} //deleteData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  // ambil kriteria
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  (isset($_REQUEST['filterDepartment'])) ? $strDataDepartment = $_REQUEST['filterDepartment'] : $strDataDepartment = "";
  (isset($_REQUEST['filterSection'])) ? $strDataSection = $_REQUEST['filterSection'] : $strDataSection = "";
  (isset($_REQUEST['filterEmployee'])) ? $strDataEmployee = $_REQUEST['filterEmployee'] : $strDataEmployee = "";
  if ($strDataID == "") {
    header("location:shift_group.php");
    exit();
  }
  // proses action, jika ada form submission
  if (isset($_REQUEST['btnAdd'])) { // tambahkan satu employee
    if ($bolCanEdit) {
      if (addData($db, $strError) == false) {
        if ($strError != "") {
          echo "<script>alert('$strError')</script>";
        }
      }
    }
  } else if (isset($_REQUEST['btnDelete'])) { // hapus kenaggotaan member dari group
    if ($bolCanDelete) {
      deleteData($db);
    }
  } else if (isset($_REQUEST['btnSearch'])) { // search employee data
    if ($strDataEmployee != "") {
      $strKriteria .= "AND (employee_id like '%$strDataEmployee%' ";
      $strKriteria .= "OR UPPER(employee_name) like '%" . strtoupper($strDataEmployee) . "%' ) ";
    }
    if ($strDataDepartment != "") {
      $strKriteria .= "AND department_code = '$strDataDepartment' ";
    }
    if ($strDataSection != "") {
      $strKriteria .= "AND section_code = '$strDataSection' ";
    }
    $strDataDetailSearch = getDataSearch($db, $intTotalDataSearch, $strKriteria);
  } else if (isset($_REQUEST['btnAddMore'])) { // tambahkan anggota group, banyak
    if ($bolCanEdit) {
      addDataMore($db);
    }
  }
  // cari data Group, apakah ada
  $strSQL = "SELECT * FROM hrd_shift_group WHERE id = $strDataID ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strGroupName = $rowDb['group_name'];
  } else {
    header("location:shift_group.php");
    exit();
  }
  if ($bolCanView) {
    $strDataDetail = getData($db, $intTotalData);
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  // tampilkan input untuk search employee
  $intWidthPx = 150;
  $strFilterEmployee = "<input type=text name=\"filterEmployee\" id=\"filterEmployee\" size=20 style=\"width:$intWidthPx\" value=\"$strDataEmployee\">";
  $strFilterDepartment = getDepartmentList(
      $db,
      "filterDepartment",
      $strDataDepartment,
      $strEmptyOption,
      "",
      "style=\"width:$intWidthPx\""
  );
  $strFilterSection = getSectionList(
      $db,
      "filterSection",
      $strDataSection,
      $strEmptyOption,
      "",
      "style=\"width:$intWidthPx\""
  );
  // hidden value
  $strHidden .= "<input type=hidden name='filterEmployee' value='$strDataEmployee'>";
  $strHidden .= "<input type=hidden name='filterDepartment' value='$strDataDepartment'>";
  $strHidden .= "<input type=hidden name='filterSection' value='$strDataSection'>";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
if (isset($_REQUEST['btnPrint'])) {
  $strInfo = getBulan($strDataMonth) . " - $strDataYear";
  $strSQL = "SELECT code, note FROM hrd_shift_type ORDER BY code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrShiftType[] = ["code" => $rowDb['code'], "name" => $rowDb['note']];
  }
  $tbsPage->PlugIn(TBS_INSTALL, TBS_EXCEL);
  $tbsPage->LoadTemplate($strTemplateFile);
  $tbsPage->MergeBlock('emp', $arrData);
  $tbsPage->MergeBlock('shift', $arrShiftType);
  $tbsPage->PlugIn(TBS_EXCEL, TBS_EXCEL_FILENAME, 'shift_schedule.xls');
} else {
  $tbsPage->LoadTemplate($strMainTemplate);
}
$tbsPage->Show();
?>