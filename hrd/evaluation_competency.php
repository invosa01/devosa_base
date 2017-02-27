<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('evaluation_func.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  header("location:login.php?dataPage=evaluation_competency.php");
  exit();
}
$bolCanView = getUserPermission("evaluation_competency.php", $bolCanEdit, $bolCanDelete, $strError, true);
$bolPrint = (isset($_REQUEST['btnPrint']));
if ($bolPrint) {
  $strMainTemplate = getTemplate("evaluation_competency_print.html");
} else {
  $strTemplateFile = getTemplate("evaluation_competency.html");
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strMessages = "";
$strMsgClass = "";
$bolError = false;
$intTotalData = 0;
$strPeriode = "";
$strEmployee = "";
$strInputLevel = "";
$strEvaluationMenu = "";
$strButtons = "&nbsp;";
$strCategoryA = "";
$strCategoryB = "";
$strCategoryC = "";
$strCategoryD = "";
$strCategoryE = "";
$fltWorkTarget = 0; // nilai work target
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "", $bolReadonly = true)
{
  global $words;
  global $strDataID;
  global $fltWorkTarget;
  $intDefaultWidth = 30;
  $intRows = 0;
  $strResult = "";
  $strReadonly = ($bolReadonly) ? "readonly" : "";
  $strTargetCriteria = getSetting("target_criteria"); // ambil kriteria yang nilainya diambil dari form1 (work target)
  // ambil data evalauasion behavior dulu dari, tampung dalam array
  $arrData = [];
  $strSQL = "SELECT * FROM hrd_employee_evaluation_competency WHERE id_evaluation = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrData[$rowDb['criteria']] = (float)$rowDb['point'];
  }
  $strSQL = "SELECT * FROM hrd_evaluation_criteria ";
  $strSQL .= "WHERE type=1 $strKriteria ORDER BY $strOrder id ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strIsReadonly = "";
    $intPoint = (isset($arrData[$rowDb['criteria']])) ? $arrData[$rowDb['criteria']] : 0;
    if ($rowDb['criteria'] == $strTargetCriteria) {
      $strIsReadonly = "readonly";
      $intPoint = $fltWorkTarget;
    }
    if (!is_numeric($intPoint)) {
      $intPoint = 0;
    }
    $strResult .= "<tr valign=top id=\"detailRows$intRows\">\n";
    $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>";
    $strResult .= "  <td><input type=hidden name='detailID$intRows' value=\"" . $rowDb['id'] . "\">\n";
    $strResult .= "  <input type=hidden name=detailCriteria$intRows value=\"" . $rowDb['criteria'] . "\">";
    $strResult .= "  <b>" . $rowDb['criteria'] . "</b><br><small>" . $rowDb['note'] . "</small></td>";
    $strResult .= "  <td align=right><input type=text size=15 maxlength=10 name=detailPoint$intRows value=\"$intPoint\" class='numeric' onChange=\"getTotal()\" $strReadonly></td>";
    $strResult .= "</tr>\n";
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  // tambahkan hidden data
  $strResult .= "<input type=hidden name=maxDetail value=$intRows>";
  return $strResult;
} // showData
// fungsi untuk menampilkan data saat di print
function getDataPrint($db, &$intRows, $strKriteria = "", $strOrder = "", $bolReadonly = true)
{
  global $words;
  global $strDataID;
  global $strTotal;
  global $strTotalPoint;
  $strTotal = 0;
  $strTotalPoint = 0;
  $strResult = "";
  // ambil data evalauasion behavior dulu dari, tampung dalam array
  $arrData = [];
  $strSQL = "SELECT * FROM hrd_employee_evaluation_competency WHERE id_evaluation = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrData[$rowDb['criteria']] = (float)$rowDb['point'];
  }
  $intRows = 0;
  $strSQL = "SELECT * FROM hrd_evaluation_criteria ";
  $strSQL .= "WHERE type=1 $strKriteria ORDER BY $strOrder id ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $intPoint = (isset($arrData[$rowDb['criteria']])) ? $arrData[$rowDb['criteria']] : 0;
    if (!is_numeric($intPoint)) {
      $intPoint = 0;
    }
    $strTotalPoint += $intPoint;
    $strResult .= "<tr valign=top id=\"detailRows$intRows\">\n";
    $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>";
    $strResult .= "  <td><b>" . $rowDb['criteria'] . "</b><br><small>" . $rowDb['note'] . "</small></td>";
    $strResult .= "  <td align=right>$intPoint&nbsp;</td>";
    $strResult .= "</tr>\n";
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  $strTotal = ($intRows == 0) ? 0 : ($strTotalPoint / $intRows);
  return $strResult;
} // getDataPrint
// fungsi untuk menyimpan data
function saveData($db, $strDataID, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $error;
  $strError = "";
  $strmodified_byID = $_SESSION['sessionUserID'];
  // ambil data evalauasion competency dulu dari, tampung dalam array simpan idnya saja
  $arrData = [];
  $strSQL = "SELECT * FROM hrd_employee_evaluation_competency WHERE id_evaluation = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrData[$rowDb['criteria']] = (float)$rowDb['id'];
  }
  (isset($_REQUEST['maxDetail'])) ? $intMax = $_REQUEST['maxDetail'] : $intMax = 0;
  for ($i = 1; $i <= $intMax; $i++) {
    $strCriteria = (isset($_REQUEST['detailCriteria' . $i])) ? $_REQUEST['detailCriteria' . $i] : "";
    $strPoint = (isset($_REQUEST['detailPoint' . $i])) ? $_REQUEST['detailPoint' . $i] : 0;
    if (!is_numeric($strPoint)) {
      $strPoint = 0;
    }
    if ($strCriteria != "") {
      if ($strPoint == 0) { // hapus data
        $strSQL = "DELETE FROM hrd_employee_evaluation_competency ";
        $strSQL .= "WHERE id_evaluation = '$strDataID' AND criteria = '$strCriteria' ";
        $resDb = $db->execute($strSQL);
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$strDataID", 0);
      } else {
        if (isset($arrData[$strCriteria])) { // update data
          $strSQL = "UPDATE hrd_employee_evaluation_competency SET point = '$strPoint' ";
          $strSQL .= "WHERE id_evaluation = '$strDataID' AND criteria = '$strCriteria' ";
          $resDb = $db->execute($strSQL);
          writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataID", 0);
        } else { // insert data baru
          $strSQL = "INSERT INTO hrd_employee_evaluation_competency (created,modified_by, created_by, ";
          $strSQL .= "id_evaluation, criteria, point, flag) ";
          $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', ";
          $strSQL .= "'$strDataID', '$strCriteria', '$strPoint', 0) ";
          $resDb = $db->execute($strSQL);
          writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataID", 0);
        }
      }
    }
  }
  // cari informasi total
  $fltTotal = 0;
  $strSQL = "SELECT AVG(point) AS rata FROM hrd_employee_evaluation_competency ";
  $strSQL .= "WHERE flag=0 AND id_evaluation = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $fltTotal = ($rowDb['rata'] == "") ? 0 : $rowDb['rata'];
  }
  // update status untuk data siapa yang melakukan revisi
  $strTs = date("r");
  $strSQL = "UPDATE hrd_employee_evaluation SET competency_point = '$fltTotal' ";
  $strSQL .= "WHERE id = '$strDataID' ";
  $resExec = $db->execute($strSQL);
  $strError = getWords("data_saved") . " &raquo; " . date("r");
  return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  $strCategoryA = getSetting("category_a");
  $strCategoryB = getSetting("category_b");
  $strCategoryC = getSetting("category_c");
  $strCategoryD = getSetting("category_d");
  $strCategoryE = getSetting("category_e");
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  $bolReadonly = true; // readonly atau bukan
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  if ($bolCanEdit && $strDataID != "") {
    if (isset($_REQUEST['btnSave']) && !$bolIsEmployee) {
      if (!saveData($db, $strDataID, $strError)) {
        $bolError = true;
      }
      if ($strError != "") {
        $strMessages = $strError;
        $strMsgClass = ($bolError) ? "class=bgError" : "class=bgOK";
      }
    }
  }
  if ($strDataID == "") {
    header("location:evaluation_list.php");
    exit();
  } else {
    ($strDataID == "") ? $strKriteria = " AND 1=2 " : $strKriteria = " AND id_evaluation = '$strDataID' ";
    $strKriteria = "";
    if ($bolPrint) {
      // panggil perintah untuk print secara keseluruhan
      printEvaluationResult($db, $strDataID);
      exit();
    }
    // cari info karyawan
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.department_code, t2.section_code ";
    $strSQL .= "FROM hrd_employee_evaluation AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($arrUserInfo['id_employee'] == $rowDb['id_manager'] || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        $bolReadonly = false;
      } else if ($arrUserInfo['isDeptHead'] && $arrUserInfo['department_code'] == $rowDb['department_code']) {
        $bolReadonly = false;
      } else if ($arrUserInfo['isGroupHead'] && $arrUserInfo['section_code'] == $rowDb['section_code']) {
        $bolReadonly = false;
      }
      if (!$bolReadonly) {
        $strButtons = "<input type=submit name=btnSave id=btnSave value=\"" . getWords("save") . "\">";
      }
      $strEmployee = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
      $strPeriode = getBulanSingkat($rowDb['month_from']) . " " . $rowDb['year'];
      $strNextYear = ($rowDb['month_thru'] < $rowDb['month_from']) ? $rowDb['year'] + 1 : $rowDb['year']; // jka bulan lebih kecil, berarti tahun berikutnya
      $strPeriode .= " - " . getBulanSingkat($rowDb['month_thru']) . " " . $strNextYear;
      $fltWorkTarget = $rowDb['target_point'];
    } else {
      header("location:evaluation_list.php");
      exit();
    }
    if ($bolCanView) {
      $strDataDetail = ($bolPrint) ? getDataPrint($db, $intTotalData, $strKriteria, "", $bolReadonly) : getData(
          $db,
          $intTotalData,
          $strKriteria,
          "",
          $bolReadonly
      );
      // tampilkan keterangan tentang status hasil, untuk pritn aja
      if ($bolPrint) {
        if ($strTotal > 90) {
          $strNoteKriteria = $strCategoryA;
        } else if ($strTotal > 80) {
          $strNoteKriteria = $strCategoryB;
        } else if ($strTotal > 70) {
          $strNoteKriteria = $strCategoryC;
        } else if ($strTotal > 59) {
          $strNoteKriteria = $strCategoryD;
        } else {
          $strNoteKriteria = $strCategoryE;
        }
        $strTotal = standardFormat($strTotal);
      }
      // tambah button untuk print
      $strButtons .= "&nbsp;<input type=button name='btnPrint' value=\"" . getWords(
              "print"
          ) . "\" onClick=\"window.open('evaluation_competency.php?btnPrint=Print&dataID=$strDataID')\"> ";
    } else {
      showError("view_denied");
      $strDataDetail = "";
    }
    $strEvaluationMenu = getEvaluationMenu($strDataID, 1, 1);
  }
}
$strInitAction .= "    getTotal();

  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>