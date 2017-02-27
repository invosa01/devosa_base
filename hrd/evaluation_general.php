<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
include_once('evaluation_func.php');
//include_once(getTemplate("words.inc"));
$dataPrivilege = getDataPrivileges("evaluation_list.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
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
$strWarningInfo = 0;
$strAbsenceInfo = 0;
$strLateInfo = 0;
$strEarlyInfo = 0;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "", $bolReadonly = true)
{
  global $words;
  global $strDataID;
  global $bolSelfEvaluation;
  $intDefaultWidth = 30;
  $intRows = 0;
  $strResult = "";
  // ambil data evaluasi kinerja general
  $arrData = [];
  $strSQL = "SELECT * FROM hrd_employee_evaluation_general WHERE id_evaluation = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrData[$rowDb['criteria']]['point'] = (float)$rowDb['point'];
    $arrData[$rowDb['criteria']]['weight'] = (float)$rowDb['weight'];
    $arrData[$rowDb['criteria']]['note'] = $rowDb['note'];
  }
  $strReadonly = ($bolReadonly) ? "readonly" : "";
  $strSQL = "SELECT * FROM hrd_evaluation_criteria ";
  $strSQL .= "WHERE type = 1 $strKriteria ORDER BY $strOrder id ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $intPoint = (isset($arrData[$rowDb['criteria']]['point'])) ? $arrData[$rowDb['criteria']]['point'] : 0;
    if (!$bolSelfEvaluation) {
      if (!is_numeric($intPoint)) {
        $intPoint = 0;
      }
    }
    $intWeight = (isset($arrData[$rowDb['criteria']]['weight'])) ? $arrData[$rowDb['criteria']]['weight'] : $rowDb['weight'];
    $strNote = (isset($arrData[$rowDb['criteria']]['note'])) ? $arrData[$rowDb['criteria']]['note'] : "";
    if (!is_numeric($intWeight)) {
      $intWeight = 0;
    }
    $strResult .= "<tr valign=top id=\"detailRows$intRows\">\n";
    $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>";
    $strResult .= "  <td><input type=hidden name='detailID$intRows' value=\"" . $rowDb['id'] . "\">\n";
    $strResult .= "  <input type=hidden name=detailCriteria$intRows value=\"" . $rowDb['criteria'] . "\"> ";
    $strResult .= "<b>" . $rowDb['criteria'] . "</b><br><small>" . $rowDb['note'] . "</small></td>\n";
    if ($bolSelfEvaluation) {
      $strResult .= "<td>" . getEvaluationPointList($db, "detailPoint", $intPoint, 1) . "</td>";
    } else {
      $strResult .= "  <td align=right><input type=text size=15 maxlength=10 name=detailWeight$intRows value=\"" . $intWeight . "\" class='numeric' onChange=\"getTotalPoint($intRows)\" readonly></td>";
      $strResult .= "  <td align=right><input type=text size=15 maxlength=10 name=detailPoint$intRows value=\"" . $intPoint . "\" class='numeric' onChange=\"getTotalPoint($intRows)\" $strReadonly></td>";
      $strResult .= "  <td align=right><input type=text size=15 maxlength=10 name=detailTotal$intRows value=\"" . (($intPoint * $intWeight) / 100) . "\" class='numeric' readonly></td>";
      $strResult .= "  <td align=right><input type=text size=50 maxlength=10 name=detailNote$intRows value=\"$strNote\"></td>";
    }
    $strResult .= "</tr>\n";
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  // tambahkan hidden data
  $strResult .= "<input type=hidden name=maxDetail value=$intRows>";
  return $strResult;
} // showData
// ambil data, untuk tujuan print
function getDataPrint($db, &$intRows, $strKriteria = "", $strOrder = "", $bolReadonly = true)
{
  global $words;
  global $strDataID;
  global $strTotal;
  global $strTotalWeight;
  global $strTotalPoint;
  $strResult = "";
  $strTotal = 0;
  $strTotalPoint = 0;
  $strTotalWeight = 0;
  // ambil data evaluasion kinerja operasional
  $arrData = [];
  $strSQL = "SELECT * FROM hrd_employee_evaluation_general WHERE id_evaluation = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrData[$rowDb['criteria']]['point'] = (float)$rowDb['point'];
    $arrData[$rowDb['criteria']]['weight'] = (float)$rowDb['weight'];
    $arrData[$rowDb['criteria']]['note'] = (float)$rowDb['note'];
  }
  $strSQL = "SELECT * FROM hrd_evaluation_criteria ";
  $strSQL .= "WHERE type = 1 $strKriteria ORDER BY $strOrder id ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $intPoint = (isset($arrData[$rowDb['criteria']]['point'])) ? $arrData[$rowDb['criteria']]['point'] : 0;
    if (!is_numeric($intPoint)) {
      $intPoint = 0;
    }
    $intWeight = (isset($arrData[$rowDb['criteria']]['weight'])) ? $arrData[$rowDb['criteria']]['weight'] : $rowDb['weight'];
    $strNote = (isset($arrData[$rowDb['criteria']]['weight'])) ? $arrData[$rowDb['criteria']]['weight'] : "";
    if (!is_numeric($intWeight)) {
      $intWeight = 0;
    }
    $intTmp = ($intPoint * $intWeight) / 100;
    $strTotalPoint += $intPoint;
    $strTotalWeight += $intWeight;
    $strTotal += $intTmp;
    $strResult .= "<tr valign=top id=\"detailRows$intRows\">\n";
    $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>";
    $strResult .= "  <td>&nbsp;<b>" . $rowDb['criteria'] . "</b><br><small>" . $rowDb['note'] . "</small></td>\n";
    if ($bolSelfEvaluation) {
      $strResult .= "  <td align=right>" . $rowDb['point'] . "&nbsp;</td>";
    } else {
      $strResult .= "  <td align=right>" . $intWeight . "&nbsp;</td>";
      $strResult .= "  <td align=right>" . $intPoint . "&nbsp;</td>";
      $strResult .= "  <td align=right>" . standardFormat($intTmp) . "&nbsp;</td>";
      $strResult .= "  <td align=right>" . $strNote . "&nbsp;</td>";
    }
    $strResult .= "</tr>\n";
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  return $strResult;
} // getDataPrint
// fungsi untuk menyimpan data
function saveData($db, $strDataID, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $error;
  global $bolSelfEvaluation;
  $strError = "";
  $strmodified_byID = $_SESSION['sessionUserID'];
  // ambil data evaluasion kinerja operasional
  $arrData = [];
  $strSQL = "SELECT * FROM hrd_employee_evaluation_general WHERE id_evaluation = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrData[$rowDb['criteria']] = (float)$rowDb['id'];
  }
  (isset($_REQUEST['maxDetail'])) ? $intMax = $_REQUEST['maxDetail'] : $intMax = 0;
  for ($i = 1; $i <= $intMax; $i++) {
    $strCriteria = (isset($_REQUEST['detailCriteria' . $i])) ? $_REQUEST['detailCriteria' . $i] : "";
    $strPoint = (isset($_REQUEST['detailPoint' . $i])) ? $_REQUEST['detailPoint' . $i] : "";
    $strWeight = (isset($_REQUEST['detailWeight' . $i])) ? $_REQUEST['detailWeight' . $i] : 0;
    $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
    $strTotal = "";
    if (!is_numeric($strWeight)) {
      $strWeight = 0;
    }
    if (!$bolSelfEvaluation) {
      if (!is_numeric($strPoint)) {
        $strPoint = 0;
      }
      $strTotal = $strWeight * $strPoint;
    }
    if ($strCriteria != "") {
      if (!isset($arrData[$strCriteria])) {
        //insert new data
        $strSQL = "INSERT INTO hrd_employee_evaluation_general (created,modified_by, created_by, ";
        $strSQL .= "id_evaluation, criteria, weight, point, total, note) ";
        $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '$strDataID',  ";
        $strSQL .= "'$strCriteria', '$strWeight', '$strPoint', '$strTotal', '$strNote') ";
        $resDb = $db->execute($strSQL);
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataID", 0);
      } else {
        // update data
        $strSQL = "UPDATE hrd_employee_evaluation_general SET modified_by = '$strmodified_byID', ";
        $strSQL .= "point = '$strPoint', weight = '$strWeight',  note = '$strNote',total = '" . (($strPoint * $strWeight)) . "' ";
        $strSQL .= "WHERE id_evaluation = '$strDataID' AND criteria = '$strCriteria'  ";
        $resDb = $db->execute($strSQL);
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataID", 0);
      }
    }
  }
  // cari informasi total
  $fltTotal = 0;
  $strSQL = "SELECT SUM(total/100) AS rata FROM hrd_employee_evaluation_general ";
  $strSQL .= "WHERE id_evaluation = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $fltTotal = ($rowDb['rata'] == "") ? 0 : $rowDb['rata'];
  }
  // update status untuk data siapa yang melakukan revisi
  $strTs = date("r");
  $strSQL = "UPDATE hrd_employee_evaluation SET general_point = '$fltTotal' ";
  $strSQL .= "WHERE id = '$strDataID' ";
  $resExec = $db->execute($strSQL);
  $strError = getWords("data_saved") . " &raquo; " . date("r");
  return true;
} // saveData
// fungsi untuk menghapus data evaluasi bersangkutan
function deleteData($db, $strDataID)
{
  $strSQL = "DELETE FROM hrd_employee_evaluation_general WHERE id_evaluation = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  // update status untuk data siapa yang melakukan revisi
  $strSQL = "UPDATE hrd_employee_evaluation SET general_point = 0 ";
  $strSQL .= "WHERE id = '$strDataID' ";
  $resExec = $db->execute($strSQL);
  $strError = getWords("data_deleted") . " &raquo; " . date("r");
  return true;
} // deleteData
//fungsi mengambil informasi karyawan yang terkait dengan evaluasi
function getEmployeeInfo($db, $strIDEmployee = "", $strDateFrom = "", $strDateThru = "", $stremployee_id = "")
{
  global $strWarningInfo;
  global $strAbsenceInfo;
  global $strLateInfo;
  global $strEarlyInfo;
  if ($strIDEmployee == "") {
    return false;
  }
  $strSQL = "SELECT COUNT(id) AS total FROM hrd_employee_warning  ";
  $strSQL .= "WHERE id_employee = '$strIDEmployee' ";
  $strSQL .= "AND warning_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    if ($rowDb['total'] != "") {
      $strWarningInfo = $rowDb['total'];
    }
  }
  $arrAbs = getEmployeeAbsence($db, $strDateFrom, $strDateThru, "", $stremployee_id);
  $arrAtt = getEmployeeAttendance($db, $strDateFrom, $strDateThru, 0, "", $stremployee_id);
  if (isset($arrAbs[$strIDEmployee]['total'])) {
    $strAbsenceInfo = $arrAbs[$strIDEmployee]['total'];
  }
  if (isset($arrAtt[$strIDEmployee]['late'])) {
    $strLateInfo = $arrAtt[$strIDEmployee]['late'];
  }
  if (isset($arrAtt[$strIDEmployee]['early'])) {
    $strEarlyInfo = $arrAtt[$strIDEmployee]['early'];
  }
  return true;
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  $bolReadonly = true; // boleh diedit atau gak
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
    } else if (isset($_REQUEST['btnDelete']) && !$bolIsEmployee) {
      if (!deleteData($db, $strDataID)) {
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
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.section_code, t2.department_code ";
    $strSQL .= "FROM hrd_employee_evaluation AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      // cek apakah berhak mengedit data
      if ($arrUserInfo['idEmployee'] == $rowDb['id_manager'] || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        $bolReadonly = false;
      } else if ($arrUserInfo['isDeptHead'] && $arrUserInfo['department_code'] == $rowDb['department_code']) {
        $bolReadonly = false;
      } else if ($arrUserInfo['isGroupHead'] && $arrUserInfo['section_code'] == $rowDb['section_code']) {
        $bolReadonly = false;
      }
      if (!$bolReadonly) {
        $strButtons = "<input type=submit name=btnSave id=btnSave value=\"" . getWords("save") . "\">";
        $strButtons .= "&nbsp;<input type=submit name=btnDelete id=btnSave value=\"" . getWords("delete") . "\">";
      }
      $bolSelfEvaluation = ($rowDb['id_employee'] == $rowDb['id_evaluator']);
      $strEmployee = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
      $strPeriode = getBulanSingkat($rowDb['month_from']) . " " . $rowDb['year'];
      $strNextYear = ($rowDb['month_thru'] < $rowDb['month_from']) ? $rowDb['year'] + 1 : $rowDb['year']; // jka bulan lebih kecil, berarti tahun berikutnya
      $strPeriode .= " - " . getBulanSingkat($rowDb['month_thru']) . " " . $strNextYear;
      $intYearThru = ($rowDb['month_from'] <= $rowDb['month_thru']) ? $rowDb['year'] : $rowDb['year'] + 1;
      // cari periodenya sesuai tanggal
      $strDateFrom = $rowDb['year'] . "-" . $rowDb['month_from'] . "-1";
      if ($rowDb['month_thru'] == 12) {
        $strDateThru = ($intYearThru + 1) . "-1-1";
      } else {
        $strDateThru = $intYearThru . "-" . ($rowDb['month_thru'] + 1) . "-1";
      }
      $strDateThru = getNextDate($strDateThru, -1);
      getEmployeeInfo($db, $rowDb['id_employee'], $strDateFrom, $strDateThru, $rowDb['employee_id']);
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
      if (!$bolSelfEvaluation) {
        $strDataDetail .= "
             <tr>
               <td>&nbsp;</td>
               <td align=\"right\"><strong>Total</strong></td>
               <td align=\"right\"><input name=\"dataTotalWeight\" type=\"text\" value=\"0\" size=\"10\" maxlength=\"10\" class=\"numeric\" readonly></td>
               <td align=\"right\"><input name=\"dataTotalPoint\" type=\"text\" value=\"0\" size=\"10\" maxlength=\"10\" class=\"numeric\" readonly></td>
               <td align=\"right\"><input name=\"dataTotal\" type=\"text\" value=\"0\" size=\"10\" maxlength=\"10\" class=\"numeric\" readonly></td>
               <td align=\"right\">&nbsp;</td>
             </tr>";
        $strDataDetail = "
                <tr align=\"center\" class=\"tableHeader\">
                  <td width=\"15px\" nowrap>No.</td>
                  <td width=\"70%\" nowrap>Description</td>
                  <td nowrap>Weight</td>
                  <td nowrap>Point</td>
                  <td nowrap>Total</td>
                  <td nowrap>Note</td>
                </tr>" . $strDataDetail;
      } else {
        $strDataDetail = "
                <tr align=\"center\" class=\"tableHeader\">
                  <td width=\"15px\" nowrap>No.</td>
                  <td width=\"70%\" nowrap>Description</td>
                  <td nowrap>Point</td>
                </tr>" . $strDataDetail;
      }
      // tampilkan keterangan tentang status hasil, untuk pritn aja
      if ($bolPrint) {
        $strNoteKriteria = "";
        $strTotal = standardFormat($strTotal);
      }
      // tambah button untuk print
      //$strButtons .= "&nbsp;<input type=button name='btnPrint' value=\"" .getWords("print")."\" onClick=\"window.open('evaluation_general.php?btnPrint=Print&dataID=$strDataID')\"> ";
    } else {
      showError("view_denied");
      $strDataDetail = "";
    }
    $strEvaluationMenu = getEvaluationMenu($strDataID, 1);
  }
}
$bolPrint = (isset($_REQUEST['btnPrint']));
if ($bolPrint) {
  $strMainTemplate = getTemplate(str_replace(".php", "_print.html", basename($_SERVER['PHP_SELF'])));
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>