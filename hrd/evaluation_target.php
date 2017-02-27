<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('evaluation_func.php');
include_once('../global/approval_func.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  header("location:login.php?dataPage=evaluation_target.php");
  exit();
}
$bolCanView = getUserPermission("evaluation_target.php", $bolCanEdit, $bolCanDelete, $strError, true);
$bolPrint = (isset($_REQUEST['btnPrint']));
if ($bolPrint) {
  $strMainTemplate = getTemplate("evaluation_target_print.html");
} else {
  $strTemplateFile = getTemplate("evaluation_target.html");
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
$strInputNote = "";
$strButton = "";
$strTargetA = "";
$strTargetB = "";
$strTargetC = "";
$strTargetD = "";
$strTargetE = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
// readnly untuk menentukan apakah data readonly atau gak =-> readonly jika bukan yang buat
function getData($db, &$intRows, $strKriteria = "", $strOrder = "", $bolReadonly = false)
{
  global $words;
  global $intDefaultWidth;
  global $strEmptyOption;
  global $arrUserInfo;
  $intDefaultWidth = 30;
  $intDefaultHeight = 3;
  $intRows = 0;
  $intShown = 0;
  $intAdd = 20; // maksimum tambahan
  $strResult = "";
  $strNow = date("Y-m-d");
  $strReadonly = ($bolReadonly) ? "readonly" : "";
  // cari info data revisi
  $arrRev = [];
  $strSQL = "SELECT * FROM hrd_employee_evaluation_target ";
  $strSQL .= "WHERE flag>0 $strKriteria ORDER BY $strOrder id ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrRev[$rowDb['link_id']] = $rowDb;
  }
  $strSQL = "SELECT * FROM hrd_employee_evaluation_target ";
  $strSQL .= "WHERE flag=0 $strKriteria ORDER BY $strOrder id ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $intShown++;
    $strTmp = (isset($arrRev[$rowDb['id']])) ? "<a href=\"javascript:viewObj('detailRev$intRows')\">[+]</a>" : "";
    $strResult .= "<tr valign=top id=\"detailRows$intRows\">\n";
    $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>";
    $strResult .= "  <td nowrap align=center title=\"" . getWords("show revision") . "\">$strTmp&nbsp;</td>";
    $strResult .= "  <td nowrap><input type=hidden name='detailID$intRows' value=\"" . $rowDb['id'] . "\">\n";
    $strResult .= "  <textarea cols=$intDefaultWidth rows=$intDefaultHeight name=detailGoal$intRows $strReadonly>" . $rowDb['goal'] . "</textarea></td>";
    $strResult .= "  <td nowrap><textarea cols=$intDefaultWidth rows=$intDefaultHeight name=detailTarget$intRows $strReadonly>" . $rowDb['target'] . "</textarea></td>";
    $strResult .= "  <td nowrap><input type=text size=5 maxlength=10 name=detailWeight$intRows value=\"" . $rowDb['weight'] . "\" class='numeric' onChange=\"getTotalPoint($intRows)\" $strReadonly></td>";
    $strResult .= "  <td nowrap><textarea cols=$intDefaultWidth rows=$intDefaultHeight name=detailAchievement$intRows $strReadonly>" . $rowDb['achievement'] . "</textarea></td>";
    $strResult .= "  <td nowrap><input type=text size=5 maxlength=10 name=detailPoint$intRows value=\"" . $rowDb['point'] . "\" class='numeric' onChange=\"getTotalPoint($intRows)\" $strReadonly></td>";
    $strResult .= "  <td nowrap><input type=text size=5 maxlength=10 name=detailTotal$intRows value=\"" . (($rowDb['point'] * $rowDb['weight']) / 100) . "\" class='numeric' disabled></td>";
    $strResult .= "  <td nowrap><textarea cols=$intDefaultWidth rows=$intDefaultHeight name=detailNote$intRows $strReadonly>" . $rowDb['note'] . "</textarea></td>";
    $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
    if ($bolReadonly) {
      $strResult .= "  <td>&nbsp;</td>";
    } else {
      $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\" $strAction></td>\n";
    }
    $strResult .= "</tr>\n";
    // tampilkan data revisi
    if (isset($arrRev[$rowDb['id']])) {
      $arrRow = $arrRev[$rowDb['id']];
      $strResult .= "<tr valign=top id=\"detailRev$intRows\" class=bgNewRevised style=\"display:none\">\n";
      $strResult .= "  <td nowrap align=right>&nbsp;</td>";
      $strResult .= "  <td nowrap align=center>&nbsp;</td>";
      $strResult .= "  <td>&nbsp;" . nl2br($arrRow['goal']) . "</td>";
      $strResult .= "  <td>&nbsp;" . nl2br($arrRow['target']) . "</td>";
      $strResult .= "  <td align=right>" . $arrRow['weight'] . "&nbsp;</td>";
      $strResult .= "  <td>&nbsp;" . nl2br($arrRow['achievement']) . "</td>";
      $strResult .= "  <td align=right>" . $arrRow['point'] . "&nbsp;</td>";
      $strResult .= "  <td align=right>" . (($arrRow['point'] * $arrRow['weight']) / 100) . "&nbsp;</td>";
      $strResult .= "  <td>&nbsp;" . nl2br($arrRow['note']) . "</td>";
      $strResult .= "  <td>&nbsp;</td>";
      $strResult .= "</tr>\n";
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  // tambahkan dengan data kosong
  for ($i = 1; $i <= $intAdd; $i++) {
    $intRows++;
    if ($intRows == 1) {
      $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
      $intShown++;
      $strDisabled = "";
    } else {
      $strResult .= "<tr valign=top  id=\"detailRows$intRows\" style=\"display:none\">\n";
      $strDisabled = "disabled";
    }
    $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>";
    $strResult .= "  <td nowrap align=right>&nbsp;</td>";
    $strResult .= "  <td nowrap><textarea cols=$intDefaultWidth rows=$intDefaultHeight name=detailGoal$intRows $strReadonly></textarea></td>";
    $strResult .= "  <td nowrap><textarea cols=$intDefaultWidth rows=$intDefaultHeight name=detailTarget$intRows $strReadonly></textarea></td>";
    $strResult .= "  <td nowrap><input type=text size=5 maxlength=10 name=detailWeight$intRows $strDisabled class='numeric' value=0 onChange=\"getTotalPoint($intRows)\" $strReadonly></td>";
    $strResult .= "  <td nowrap><textarea cols=$intDefaultWidth rows=$intDefaultHeight name=detailAchievement$intRows $strReadonly></textarea></td>";
    $strResult .= "  <td nowrap><input type=text size=5 maxlength=10 name=detailPoint$intRows $strDisabled class='numeric' value=0 onChange=\"getTotalPoint($intRows)\" $strReadonly></td>";
    $strResult .= "  <td nowrap><input type=text size=5 maxlength=10 name=detailTotal$intRows disabled class='numeric' value=0 $strReadonly></td>";
    $strResult .= "  <td nowrap><textarea cols=$intDefaultWidth rows=$intDefaultHeight name=detailNote$intRows $strReadonly></textarea></td>";
    $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
    if ($bolReadonly) {
      $strResult .= "  <td>&nbsp;</td>\n";
    } else {
      $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\" $strAction></td>\n";
    }
    $strResult .= "</tr>\n";
  }
  // tambahkan hidden data
  $strResult .= "<input type=hidden name=maxDetail value=$intRows>";
  $strResult .= "<input type=hidden name=numShow value=$intShown>";
  return $strResult;
} // showData
// ambil data untuk print
function getDataPrint($db, &$intRows, $strKriteria = "", $strOrder = "", $bolReadonly = false)
{
  global $words;
  global $intDefaultWidth;
  global $strEmptyOption;
  global $arrUserInfo;
  global $strTotalWeight;
  global $strTotalPoint;
  global $strTotal;
  $strResult = "";
  $strNow = date("Y-m-d");
  $strTotal = 0;
  $strTotalWeight = 0;
  $strTotalPoint = 0;
  $strSQL = "SELECT * FROM hrd_employee_evaluation_target ";
  $strSQL .= "WHERE flag=0 $strKriteria ORDER BY $strOrder id ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strTotalWeight += $rowDb['weight'];
    $strTotalPoint += $rowDb['point'];
    $intTmp = ($rowDb['point'] * $rowDb['weight']) / 100;
    $strTotal += $intTmp;
    $strResult .= "<tr valign=top id=\"detailRows$intRows\">\n";
    $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>";
    $strResult .= "  <td>&nbsp;" . nl2br($rowDb['goal']) . "</td>";
    $strResult .= "  <td>&nbsp;" . nl2br($rowDb['target']) . "</td>";
    $strResult .= "  <td nowrap align=right>" . $rowDb['weight'] . "&nbsp;</td>";
    $strResult .= "  <td>&nbsp;" . nl2br($rowDb['achievement']) . "</td>";
    $strResult .= "  <td nowrap align=right>" . $rowDb['point'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap align=right>" . standardFormat($intTmp) . "&nbsp;</td>";
    $strResult .= "  <td>&nbsp;" . nl2br($rowDb['note']) . "</td>";
    $strResult .= "</tr>\n";
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  return $strResult;
} // getDataPrint
// fungsi untuk menyimpan data
// last status = status terakhir : 0 (diedit oleh employee), 1 (diedit oleh depthead)
function saveData($db, $strDataID, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $arrUserInfo;
  global $error;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strError = "";
  // ambil dulu data evaluasi
  $intStatus = 0;
  $intIDEmployee = -1;
  $intIDManager = -1;
  $strSQL = "SELECT * FROM hrd_employee_evaluation WHERE id = $strDataID ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $intStatus = $rowDb['status'];
    $intIDEmployee = $rowDb['id_employee'];
    $intIDManager = $rowDb['id_manager'];
  } else {
    $strError = getWords("data_not_found");
    return false;
  }
  // ambil data evaluasi target yang terakhir
  // format : flag = 0
  $arrData = []; // daftar evaluatin target yang terakhir
  $strSQL = "SELECT * FROM hrd_employee_evaluation_target ";
  $strSQL .= "WHERE flag = 0 AND id_evaluation = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrData[$rowDb['id']] = $rowDb;
  }
  // tentukan, apakah data tinggal update, atau buat revisinya
  $bolMakeNew = false; // menentukan apakah perlu dibuat data baru
  if ($intStatus == 0) { // terakhir update oleh employee
    $bolMakeNew = ($arrUserInfo['id_employee'] == $intIDManager); // diedit oleh manager
  } else if ($intStatus == 1) {
    $bolMakeNew = ($arrUserInfo['id_employee'] == $intIDEmployee);
  }
  (isset($_REQUEST['maxDetail'])) ? $intMax = $_REQUEST['maxDetail'] : $intMax = 0;
  for ($i = 1; $i <= $intMax; $i++) {
    (isset($_REQUEST['detailID' . $i])) ? $strID = $_REQUEST['detailID' . $i] : $strID = "";
    $strGoal = (isset($_REQUEST['detailGoal' . $i])) ? $_REQUEST['detailGoal' . $i] : "";
    $strTarget = (isset($_REQUEST['detailTarget' . $i])) ? $_REQUEST['detailTarget' . $i] : "";
    $strAchievement = (isset($_REQUEST['detailAchievement' . $i])) ? $_REQUEST['detailAchievement' . $i] : "";
    $strWeight = (isset($_REQUEST['detailWeight' . $i])) ? $_REQUEST['detailWeight' . $i] : "0";
    $strPoint = (isset($_REQUEST['detailPoint' . $i])) ? $_REQUEST['detailPoint' . $i] : "0";
    $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
    $strTotal = $strWeight * $strPoint;
    if ($strID == "") {
      if ($strGoal != "") { // insert new data
        $strSQL = "INSERT INTO hrd_employee_evaluation_target (created,modified_by, created_by, ";
        $strSQL .= "id_evaluation, goal, target, achievement, note, weight, ";
        $strSQL .= "point, total, flag) ";
        $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', ";
        $strSQL .= "'$strDataID', '$strGoal', '$strTarget', '$strAchievement', '$strNote', ";
        $strSQL .= "'$strWeight', '$strPoint', '$strTotal', 0) ";
        $resDb = $db->execute($strSQL);
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataID", 0);
      }
    } else {
      if ($strGoal == "") {
        // delete data
        $strSQL = "DELETE FROM hrd_employee_evaluation_target WHERE id = '$strID' ";
        $strSQL .= "DELETE FROM hrd_employee_evaluation_target WHERE link_id = '$strID' "; // hapus juga historynya
        $resDb = $db->execute($strSQL);
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$strDataID", 0);
      } else {
        // cek apakah ada perubahan atau tidak
        if (isset($arrData[$strID]) && $bolMakeNew) {
          // cek satu-satu
          $bolSame = ($strGoal == $arrData[$strID]['goal']);
          if ($bolSame) {
            $bolSame = ($strTarget == $arrData[$strID]['target']);
          }
          if ($bolSame) {
            $bolSame = ($strNote == $arrData[$strID]['note']);
          }
          if ($bolSame) {
            $bolSame = ($strWeight == $arrData[$strID]['weight']);
          }
          if ($bolSame) {
            $bolSame = ($strPoint == $arrData[$strID]['point']);
          }
          if ($bolSame) {
            $bolSame = ($strAchievement == $arrData[$strID]['achievement']);
          }
          if (!$bolSame) {
            // bikin revisi
            // hapus dulu data yang lama
            $strSQL = "DELETE FROM hrd_employee_evaluation_target WHERE link_id = '$strID' ";
            $resExec = $db->execute($strSQL);
            $strFields = "created, modified_by, created_by, id_evaluation, ";
            $strFields .= "goal, target, note, weight, point, achievement, total";
            $intTmp = getTempData($db, "hrd_employeeEvaluationTarget", $strFields, $strID, 1);
          }
        }
        // update data
        $strSQL = "UPDATE hrd_employee_evaluation_target SET modified_by = '$strmodified_byID', ";
        $strSQL .= "goal = '$strGoal', target = '$strTarget', ";
        $strSQL .= "note = '$strNote', weight = '$strWeight', point = '$strPoint', ";
        $strSQL .= "achievement = '$strAchievement', total = '$strTotal' WHERE id = '$strID' ";
        $resDb = $db->execute($strSQL);
      }
    }
  }
  $fltTotal = 0;
  // hitung total point untuk bagian target
  $strSQL = "SELECT SUM(total/100) AS rata FROM hrd_employee_evaluation_target ";
  $strSQL .= "WHERE flag=0 AND id_evaluation = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $fltTotal = ($rowDb['rata'] == "") ? 0 : $rowDb['rata'];
  }
  // update status untuk data siapa yang melakukan revisi
  $strTs = date("r");
  $intStatus = ($arrUserInfo['id_employee'] == $intIDEmployee) ? 0 : 1;
  $strSQL = "UPDATE hrd_employee_evaluation SET status = $intStatus, ";
  $strSQL .= "note = '$strTs', target_point = '$fltTotal', modified_by = '$strmodified_byID' ";
  $strSQL .= "WHERE id = '$strDataID' ";
  $resExec = $db->execute($strSQL);
  writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataID", 0);
  $strError = getWords("data_saved") . " &raquo; $strTs";
  return true;
} // saveData
// fungsi untuk menyimpan approval
//----------------------------------------------------------------------
// level = 0(edit employee), 1 (revision), 2(finnish)
function approveData($db, $strDataID)
{
  global $_SESSION;
  if ($strDataID != "") {
    $strTmp = date("r");
    $strSQL = "UPDATE hrd_employee_evaluation SET status = 2, ";
    $strSQL .= "note = '$strTmp', approved_by = '" . $_SESSION['sessionUserID'] . "', approved_time = now() ";
    $strSQL .= "WHERE id = '$strDataID' ";
    $resExec = $db->execute($strSQL);
  }
  return true;
}//approveData
// perintah membatalkan approval
function cancelApproveData($db, $strDataID)
{
  global $_SESSION;
  if ($_SESSION['sessionUserRole'] != ROLE_ADMIN) {
    return false;
  }
  if ($strDataID != "") {
    $strTmp = date("r");
    $strSQL = "UPDATE hrd_employee_evaluation SET status = 0 ";
    $strSQL .= "WHERE id = '$strDataID' ";
    $resExec = $db->execute($strSQL);
  }
  return true;
}//approveData
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  $strTargetA = getSetting("target_a");
  $strTargetB = getSetting("target_b");
  $strTargetC = getSetting("target_c");
  $strTargetD = getSetting("target_d");
  $strTargetE = getSetting("target_e");
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  $bolIsEmployee = isUserEmployee();
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  if ($bolCanEdit && $strDataID != "") {
    if (isset($_POST['btnSave'])) {
      if (!saveData($db, $strDataID, $strError)) {
        //           echo "<script>alert(\"$strError\")</script>";
        $bolError = true;
      }
      if ($strError != "") {
        $strMessages = $strError;
        $strMsgClass = ($bolError) ? "class=bgError" : "class=bgOK";
      }
    } else if (isset($_POST['btnApprove'])) {
      approveData($db, $strDataID);
    } else if (isset($_POST['btnCancelApprove'])) {
      cancelApproveData($db, $strDataID);
    }
  }
  if ($strDataID == "") {
    header("location:evaluation_list.php");
    exit();
  } else {
    ($strDataID == "") ? $strKriteria = " AND 1=2 " : $strKriteria = " AND id_evaluation = '$strDataID' ";
    if ($bolPrint) {
      printEvaluationResult($db, $strDataID);
      exit();
    }
    // cari info karyawan
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.section_code, t2.department_code FROM hrd_employee_evaluation AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['status'] > 1) {
        $bolDataReadonly = true;
      } else {
        $bolDataReadonly = ($arrUserInfo['id_employee'] != $rowDb['id_employee'] && $arrUserInfo['id_employee'] != $rowDb['id_manager']);
        // group head atau dept head boleh ngasih revisi
        if ($arrUserInfo['isDeptHead'] && $arrUserInfo['department_code'] == $rowDb['department_code']) {
          $bolDataReadonly = false;
        } else if ($arrUserInfo['isGroupHead'] && $arrUserInfo['section_code'] == $rowDb['section_code']) {
          $bolDataReadonly = false;
        }
      }
      $strEmployee = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
      $strThisYear = ($rowDb['month_thru'] < $rowDb['month_from']) ? $rowDb['year'] - 1 : $rowDb['year']; // jka bulan lebih kecil, berarti tahun sebelumnya
      $strPeriode = getBulanSingkat($rowDb['month_from']) . " " . $strThisYear;
      //$strNextYear = ($rowDb['month_thru'] < $rowDb['month_from']) ? $rowDb['year'] : $rowDb['year'] + 1; // jka bulan lebih kecil, berarti tahun berikutnya
      $strNextYear = $rowDb['year'];
      $strPeriode .= " - " . getBulanSingkat($rowDb['month_thru']) . " " . $strNextYear;
      $strInputNote = $rowDb['note'] . "";
      $strApproveConfirm = "onClick = \"return confirmApproval();\"";
      if ($rowDb['status'] < 2) { //belum diapprove
        if ($arrUserInfo['id_employee'] == $rowDb['id_employee']) { // yang login karyawan
          $strButton .= "<input type=submit name=btnSave value=\"" . getWords('save') . "\">";
        } else if ($arrUserInfo['id_employee'] == $rowDb['id_manager']) { // manager
          $strButton .= "<input type=submit name=btnSave value=\"" . getWords('save revision') . "\">";
          $strButton .= " &nbsp; <input type=submit name=btnApprove value=\"" . getWords(
                  'approve'
              ) . "\" $strApproveConfirm>";
        } else if ($arrUserInfo['isDeptHead'] && $arrUserInfo['department_code'] == $rowDb['department_code']) {
          $strButton .= "<input type=submit name=btnSave value=\"" . getWords('save revision') . "\">";
          $strButton .= " &nbsp; <input type=submit name=btnApprove value=\"" . getWords(
                  'approve'
              ) . "\" $strApproveConfirm>";
        } else if ($arrUserInfo['isGroupHead'] && $arrUserInfo['section_code'] == $rowDb['section_code']) {
          $strButton .= "<input type=submit name=btnSave value=\"" . getWords('save revision') . "\">";
          $strButton .= " &nbsp; <input type=submit name=btnApprove value=\"" . getWords(
                  'approve'
              ) . "\" $strApproveConfirm>";
        }
        $strInputNote = ($rowDb['status'] == 1) ? getWords('revision by dept. head') : getWords("revision by employee");
        $strInputNote .= " --- " . $rowDb['note'];
        $strTmp = trim($rowDb['modified_by']);
        if (isset($arrUserList[$rowDb['modified_by']])) {
          $strInputNote .= " [" . $arrUserList[$rowDb['modified_by']]['name'] . "]";
        }
      } else {
        $strInputNote = getWords('approved');
        $strInputNote .= " --- " . $rowDb['note'];
        if (isset($arrUserList[$rowDb['approved_by']])) {
          $strInputNote .= " [" . $arrUserList[$rowDb['approved_by']]['name'] . "]";
        }
      }
      if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        // bisa membatalkan approve, agar masih bisa direvisi
        $strButton .= " &nbsp; <input type=submit name=btnCancelApprove value=\"" . getWords(
                'cancel approval'
            ) . "\" onClick = \"return confirmCancel();\">";
      }
      $strEvaluationMenu = getEvaluationMenu($strDataID, 0, 0);
      if ($bolCanView) {
        $strDataDetail = ($bolPrint) ? getDataPrint($db, $intTotalData, $strKriteria, "", $bolDataReadonly) : getData(
            $db,
            $intTotalData,
            $strKriteria,
            "",
            $bolDataReadonly
        ); // ambil listnya
        // tambah button untuk print
        $strButton .= "&nbsp;<input type=button name='btnPrint' value=\"" . getWords(
                "print"
            ) . "\" onClick=\"window.open('evaluation_target.php?btnPrint=Print&dataID=$strDataID')\"> ";
        // tampilkan keterangan tentang status hasil, untuk pritn aja
        if ($bolPrint) {
          if ($strTotal > 90) {
            $strNoteKriteria = $strTargetA;
          } else if ($strTotal > 80) {
            $strNoteKriteria = $strTargetB;
          } else if ($strTotal > 70) {
            $strNoteKriteria = $strTargetC;
          } else if ($strTotal > 59) {
            $strNoteKriteria = $strTargetD;
          } else {
            $strNoteKriteria = $strTargetE;
          }
          $strTotal = standardFormat($strTotal);
        }
      } else {
        showError("view_denied");
        $strDataDetail = "";
      }
    } else {
      header("location:evaluation_list.php");
      exit();
    }
  }
}
$strInitAction .= "    getTotal();   ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>