<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('evaluation_func.php');
include_once('activity.php');
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
$arrData = [
    "dataID"                  => "",
    "dataEmployeeNote"        => "",
    "dataManagerNote"         => "",
    "dataEmployeeStrong"      => "",
    "dataEmployeeImprovement" => "",
];
$strInputTraining = "";
$strInputOther = "";
$strInputMutation = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getDataTraining($db, $strDataID = "", $bolReadonly = false)
{
  global $words;
  global $strTargetElements;
  global $bolPrint;
  $intMaxShow = 2; // tambahan yang perlu dimunculkan
  $intAdd = 10; // maksimum tambahan
  $intRows = 0;
  $intShown = 0;
  $strResult = "";
  $strResult .= " <table border=0  cellpadding=1 cellspacing=0 class='noGridTable' width=100%>\n";
  $strResult .= "<tr valign=top align=center class=tableHeader>\n";
  $strResult .= "  <td nowrap width=10>" . getWords("no") . "&nbsp;</td>";
  $strResult .= "  <td nowrap>" . getWords("type") . "&nbsp;</td>";
  $strResult .= "  <td nowrap>" . getWords("institution") . "&nbsp;</td>";
  $strResult .= "</tr>\n";
  $strReadonly = "";
  //$strReadonly = ($bolReadonly) ? "readonly" : "";
  if ($strDataID != "") {
    $strSQL = "SELECT * FROM hrd_employee_evaluation_training ";
    $strSQL .= "WHERE id_evaluation = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $intRows++;
      $intShown++;
      if ($bolPrint) {
        $strResult .= "<tr valign=top id=\"detailTrainingRows$intRows\">\n";
        $strResult .= "  <td align=right nowrap>$intRows.&nbsp;</td>";
        $strResult .= "  <td nowrap>&nbsp;" . $rowDb['note'] . "</td>";
        $strResult .= "  <td nowrap>&nbsp;" . $rowDb['institution'] . "</td>";
        $strResult .= "</tr>\n";
      } else {
        $strResult .= "<tr valign=top id=\"detailTrainingRows$intRows\">\n";
        $strResult .= "  <td align=right nowrap><input type=hidden name=detailTrainingID$intRows value=\"" . $rowDb['id'] . "\">$intRows.&nbsp;</td>";
        $strResult .= "  <td nowrap><input type=text name=\"detailTraining$intRows\" value=\"" . $rowDb['note'] . "\" size=20 maxlength=200 $strReadonly></td>";
        $strResult .= "  <td nowrap><input type=text name=\"detailInstitution$intRows\" value=\"" . $rowDb['institution'] . "\" size=40 maxlength=200 $strReadonly></td>";
        $strResult .= "</tr>\n";
      }
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  // tambahkan dengan data kosong
  if (!$bolPrint) {
    for ($i = 1; $i <= $intAdd; $i++) {
      $intRows++;
      if ($intRows <= $intMaxShow) {
        $strResult .= "<tr valign=top  id=\"detailTrainingRows$intRows\">\n";
        $intShown++;
        $strDisabled = "";
      } else {
        $strResult .= "<tr valign=top  id=\"detailTrainingRows$intRows\" style=\"display:none\">\n";
        $strDisabled = "disabled";
      }
      $strResult .= "  <td align=right nowrap>$intRows.&nbsp;</td>";
      $strResult .= "  <td nowrap><input type=text name=\"detailTraining$intRows\" value=\"\" size=20 maxlength=200  $strReadonly $strDisabled></td>";
      $strResult .= "  <td nowrap><input type=text name=\"detailInstitution$intRows\" value=\"\" size=40 maxlength=200  $strReadonly></td>";
      $strResult .= "</tr>\n";
    }
    if (!$bolReadonly) {
      $strResult .= " <tr valign=top><td>&nbsp;</td>\n";
      $strResult .= "  <td colspan=2>&nbsp;<a href=\"javascript:showMoreInput('Training');\">" . $words['more'] . "</a></td></tr>\n";
    }
  }
  $strResult .= "  </table>\n";
  // tambahkan hidden data
  $strResult .= "<input type=hidden name=maxTrainingDetail value=$intRows>";
  $strResult .= "<input type=hidden name=numTrainingShow value=$intShown>";
  return $strResult;
} // getDataTraining
function getDataMutation($db, $strDataID = "", $bolReadonly = false)
{
  global $words;
  global $strTargetElements;
  global $bolPrint;
  $intMaxShow = 2; // tambahan yang perlu dimunculkan
  $intAdd = 10; // maksimum tambahan
  $intRows = 0;
  $intShown = 0;
  $strResult = "";
  $strResult .= " <table border=0 class='noGridTable' cellpadding=1 cellspacing=0 width=100%>\n";
  $strResult .= "<tr valign=top align=center class=tableHeader>\n";
  $strResult .= "  <td nowrap width=10>" . getWords("no") . "&nbsp;</td>";
  $strResult .= "  <td nowrap>" . getWords("type") . "&nbsp;</td>";
  $strResult .= "  <td nowrap>" . getWords("level") . "&nbsp;</td>";
  $strResult .= "</tr>\n";
  $strReadonly = "";
  //$strReadonly = ($bolReadonly) ? "readonly" : "";
  if ($strDataID != "") {
    $strSQL = "SELECT * FROM hrd_employee_evaluation_mutation ";
    $strSQL .= "WHERE id_evaluation = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $intRows++;
      $intShown++;
      if ($bolPrint) {
        $strResult .= "<tr valign=top  id=\"detailMutationRows$intRows\">\n";
        $strResult .= "  <td align=right nowrap>$intRows.&nbsp;</td>";
        $strResult .= "  <td nowrap>&nbsp;" . $rowDb['note'] . "</td>";
        $strResult .= "  <td nowrap>&nbsp;" . $rowDb['position'] . "</td>";
        $strResult .= "</tr>\n";
      } else {
        $strResult .= "<tr valign=top  id=\"detailMutationRows$intRows\">\n";
        $strResult .= "  <td align=right nowrap><input type=hidden name=detailMutationID$intRows value=\"" . $rowDb['id'] . "\">$intRows.&nbsp;</td>";
        $strResult .= "  <td nowrap><input type=text name=\"detailMutation$intRows\" value=\"" . $rowDb['note'] . "\" size=20 maxlength=200 $strReadonly></td>";
        $strResult .= "  <td nowrap><input type=text name=\"detailPosition$intRows\" value=\"" . $rowDb['position'] . "\" size=40 maxlength=200 $strReadonly></td>";
        $strResult .= "</tr>\n";
      }
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  // tambahkan dengan data kosong
  if (!$bolPrint) {
    for ($i = 1; $i <= $intAdd; $i++) {
      $intRows++;
      if ($intRows <= $intMaxShow) {
        $strResult .= "<tr valign=top  id=\"detailMutationRows$intRows\">\n";
        $intShown++;
        $strDisabled = "";
      } else {
        $strResult .= "<tr valign=top  id=\"detailMutationRows$intRows\" style=\"display:none\">\n";
        $strDisabled = "disabled";
      }
      $strResult .= "  <td align=right nowrap>$intRows.&nbsp;</td>";
      $strResult .= "  <td nowrap><input type=text name=\"detailMutation$intRows\" value=\"\" size=20 maxlength=200 $strReadonly $strDisabled></td>";
      $strResult .= "  <td nowrap><input type=text name=\"detailPosition$intRows\" value=\"\" size=40 maxlength=200 $strReadonly></td>";
      $strResult .= "</tr>\n";
    }
    if (!$bolReadonly) {
      $strResult .= " <tr valign=top><td>&nbsp;</td>\n";
      $strResult .= "  <td colspan=2>&nbsp;<a href=\"javascript:showMoreInput('Mutation');\">" . $words['more'] . "</a></td></tr>\n";
    }
  }
  $strResult .= "  </table>\n";
  // tambahkan hidden data
  $strResult .= "<input type=hidden name=maxMutationDetail value=$intRows>";
  $strResult .= "<input type=hidden name=numMutationShow value=$intShown>";
  return $strResult;
} // getDataMutation
function getDataOther($db, $strDataID = "", $bolReadonly = false)
{
  global $words;
  global $strTargetElements;
  global $bolPrint;
  $intMaxShow = 2; // tambahan yang perlu dimunculkan
  $intAdd = 10; // maksimum tambahan
  $intRows = 0;
  $intShown = 0;
  $strResult = "";
  $strResult .= " <table border=0 class='noGridTable' cellpadding=1 cellspacing=0 width=100%>\n";
  $strReadonly = "";
  //$strReadonly = ($bolReadonly) ? "readonly" : "";
  if ($strDataID != "") {
    $strSQL = "SELECT * FROM hrd_employee_evaluation_other ";
    $strSQL .= "WHERE id_evaluation = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $intRows++;
      $intShown++;
      if ($bolPrint) {
        $strResult .= "<tr valign=top  id=\"detailOtherRows$intRows\">\n";
        $strResult .= "  <td align=right nowrap width=10>$intRows.&nbsp;</td>";
        $strResult .= "  <td nowrap>&nbsp;" . $rowDb['note'] . "</td>";
        $strResult .= "</tr>\n";
      } else {
        $strResult .= "<tr valign=top  id=\"detailOtherRows$intRows\">\n";
        $strResult .= "  <td align=right nowrap><input type=hidden name=detailOtherID$intRows value=\"" . $rowDb['id'] . "\">$intRows.&nbsp;</td>";
        $strResult .= "  <td nowrap><input type=text name=\"detailOther$intRows\" value=\"" . $rowDb['note'] . "\" size=65 maxlength=200 $strReadonly></td>";
        $strResult .= "</tr>\n";
      }
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  // tambahkan dengan data kosong
  if (!$bolPrint) {
    for ($i = 1; $i <= $intAdd; $i++) {
      $intRows++;
      if ($intRows <= $intMaxShow) {
        $strResult .= "<tr valign=top  id=\"detailOtherRows$intRows\">\n";
        $intShown++;
        $strDisabled = "";
      } else {
        $strResult .= "<tr valign=top  id=\"detailOtherRows$intRows\" style=\"display:none\">\n";
        $strDisabled = "disabled";
      }
      $strResult .= "  <td align=right nowrap>$intRows.&nbsp;</td>";
      $strResult .= "  <td nowrap><input type=text name=\"detailOther$intRows\" value=\"\" size=60 maxlength=200 $strReadonly $strDisabled></td>";
      $strResult .= "</tr>\n";
    }
    if (!$bolReadonly) {
      $strResult .= " <tr valign=top><td>&nbsp;</td>\n";
      $strResult .= "  <td colspan=2>&nbsp;<a href=\"javascript:showMoreInput('Other');\">" . $words['more'] . "</a></td></tr>\n";
    }
  }
  $strResult .= "  </table>\n";
  // tambahkan hidden data
  $strResult .= "<input type=hidden name=maxOtherDetail value=$intRows>";
  $strResult .= "<input type=hidden name=numOtherShow value=$intShown>";
  return $strResult;
} // getDataOther
// fungsi untuk menyimpan data
function saveData($db, $strDataID, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $error;
  global $arrUserInfo;
  $strError = "";
  $strmodified_byID = $_SESSION['sessionUserID'];
  $bolEmployee = false;
  // ambil dulu informasi evaluasi yang disimpan
  if ($strDataID == "") {
    return false;
  }
  $strSQL = "SELECT * FROM hrd_employee_evaluation WHERE id = $strDataID ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    if ($rowDb['id_employee'] == $arrUserInfo['id_employee']) // atasan
    {
      $bolEmployee = true;
    }
  } else {
    $strError = getWords("data_not_found");
    return false;
  }
  // -- UDPATE data feedback dulu --
  $strEmployeeNote = (isset($_REQUEST['dataEmployeeNote'])) ? $_REQUEST['dataEmployeeNote'] : "";
  $strManagerNote = (isset($_REQUEST['dataManagerNote'])) ? $_REQUEST['dataManagerNote'] : "";
  $strEmployeeStrong = (isset($_REQUEST['dataEmployeeStrong'])) ? $_REQUEST['dataEmployeeStrong'] : "";
  $strEmployeeImprovement = (isset($_REQUEST['dataEmployeeImprovement'])) ? $_REQUEST['dataEmployeeImprovement'] : "";
  $strSQL = "UPDATE hrd_employee_evaluation SET employee_note = '$strEmployeeNote', ";
  if (!$bolEmployee) { // khusus managernya
    $strSQL .= "manager_note = '$strManagerNote', ";
    $strSQL .= "employee_strong = '$strEmployeeStrong', employee_improvement = '$strEmployeeImprovement', ";
  }
  $strSQL .= "modified_by = '$strmodified_byID' WHERE id = '$strDataID' ";
  $resExec = $db->execute($strSQL);
  // -- simpan data development , khusus manager
  if (!$bolEmployee) {
    // simpan feedback training
    $intTotal = (isset($_REQUEST['numTrainingShow'])) ? $_REQUEST['numTrainingShow'] : 0;
    for ($i = 1; $i <= $intTotal; $i++) {
      $strID = (isset($_REQUEST['detailTrainingID' . $i])) ? $_REQUEST['detailTrainingID' . $i] : "";
      $strNote = (isset($_REQUEST['detailTraining' . $i])) ? $_REQUEST['detailTraining' . $i] : "";
      $strInstitution = (isset($_REQUEST['detailInstitution' . $i])) ? $_REQUEST['detailInstitution' . $i] : "";
      if ($strID === "") {
        if ($strNote != "") {
          $strSQL = "INSERT INTO hrd_employee_evaluation_training (created, ";
          $strSQL .= "modified_by, created_by, id_evaluation, note, institution) ";
          $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', ";
          $strSQL .= "'$strDataID', '$strNote', '$strInstitution') ";
          $resExec = $db->execute($strSQL);
        }
      } else {
        if ($strNote == "") { // hapus
          $strSQL = "DELETE FROM hrd_employee_evaluation_training WHERE id = '$strID' ";
          $resExec = $db->execute($strSQL);
        } else { // update
          $strSQL = "UPDATE hrd_employee_evaluation_training SET note = '$strNote', ";
          $strSQL .= "institution = '$strInstitution' WHERE id = '$strID' ";
          $resExec = $db->execute($strSQL);
        }
      }
    }
    // simpan feedback mutasi
    $intTotal = (isset($_REQUEST['numMutationShow'])) ? $_REQUEST['numMutationShow'] : 0;
    for ($i = 1; $i <= $intTotal; $i++) {
      $strID = (isset($_REQUEST['detailMutationID' . $i])) ? $_REQUEST['detailMutationID' . $i] : "";
      $strNote = (isset($_REQUEST['detailMutation' . $i])) ? $_REQUEST['detailMutation' . $i] : "";
      $strPosition = (isset($_REQUEST['detailPosition' . $i])) ? $_REQUEST['detailPosition' . $i] : "";
      if ($strID === "") {
        if ($strNote != "") {
          $strSQL = "INSERT INTO hrd_employee_evaluation_mutation (created, ";
          $strSQL .= "modified_by, created_by, id_evaluation, note, position) ";
          $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', ";
          $strSQL .= "'$strDataID', '$strNote', '$strPosition') ";
          $resExec = $db->execute($strSQL);
        }
      } else {
        if ($strNote == "") { // hapus
          $strSQL = "DELETE FROM hrd_employee_evaluation_mutation WHERE id = '$strID' ";
          $resExec = $db->execute($strSQL);
        } else { // update
          $strSQL = "UPDATE hrd_employee_evaluation_mutation SET note = '$strNote', ";
          $strSQL .= "position = '$strPosition' WHERE id = '$strID' ";
          $resExec = $db->execute($strSQL);
        }
      }
    }
    // simpan feedback other
    $intTotal = (isset($_REQUEST['numOtherShow'])) ? $_REQUEST['numOtherShow'] : 0;
    for ($i = 1; $i <= $intTotal; $i++) {
      $strID = (isset($_REQUEST['detailOtherID' . $i])) ? $_REQUEST['detailOtherID' . $i] : "";
      $strNote = (isset($_REQUEST['detailOther' . $i])) ? $_REQUEST['detailOther' . $i] : "";
      if ($strID === "") {
        if ($strNote != "") {
          $strSQL = "INSERT INTO hrd_employee_evaluation_other (created, ";
          $strSQL .= "modified_by, created_by, id_evaluation, note) ";
          $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', ";
          $strSQL .= "'$strDataID', '$strNote') ";
          $resExec = $db->execute($strSQL);
        }
      } else {
        if ($strNote == "") { // hapus
          $strSQL = "DELETE FROM hrd_employee_evaluation_other WHERE id = '$strID' ";
          $resExec = $db->execute($strSQL);
        } else { // update
          $strSQL = "UPDATE hrd_employee_evaluation_other ";
          $strSQL .= "SET note = '$strNote' WHERE id = '$strID' ";
          $resExec = $db->execute($strSQL);
        }
      }
    }
  }
  $strError = getWords("data_saved") . " &raquo; " . date("r");
  return true;
} // saveData
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
  $arrUserList =
  $bolIsEmployee = isUserEmployee();
  $bolReadonly = false; // boleh diedit atau gak
  $bolEmployee = false; // apakah employee ybs
  $bolManager = false;  // apakah manager ybs
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  if ($bolCanEdit && $strDataID != "") {
    if (isset($_POST['btnSave'])) {
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
      // cek apakah berhak mengedit data
      $bolManager = ($rowDb['id_manager'] == $arrUserInfo['idEmployee']);
      $bolEmployee = ($rowDb['id_employee'] == $arrUserInfo['idEmployee']);
      if ($bolManager || $bolEmployee || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        $bolReadonly = false;
      } else if ($arrUserInfo['isDeptHead'] && $arrUserInfo['department_code'] == $rowDb['department_code']) {
        $bolReadonly = false;
        $bolManager = true;
      } else if ($arrUserInfo['isGroupHead'] && $arrUserInfo['section_code'] == $rowDb['section_code']) {
        $bolReadonly = false;
        $bolManager = true;
      }
      if (!$bolReadonly) {
        $strButtons = "<input type=submit name=btnSave id=btnSave value=\"" . getWords("save") . "\">";
      }
      $intMenuType = $rowDb['flag'];
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
      // ambil data informasi feedback
      $arrData['dataEmployeeNote'] = $rowDb['employee_note'];
      $arrData['dataManagerNote'] = $rowDb['manager_note'];
      $arrData['dataEmployeeStrong'] = $rowDb['employee_strong'];
      $arrData['dataEmployeeImprovement'] = $rowDb['employee_improvement'];
    } else {
      header("location:evaluation_list.php");
      exit();
    }
    // ambil data lain-lainnya
    if ($bolCanView) {
      //$strDataDetail = getData($db,$intTotalData, $strKriteria,"", $bolReadonly);
    } else {
      showError("view_denied");
      $strDataDetail = "";
    }
    //if (isset($_REQUEST['flag']) && $_REQUEST['flag'] == "1")
    $strEvaluationMenu = getEvaluationMenu($strDataID, 4, 4, $intMenuType);
    //else
    //   $strEvaluationMenu = getEvaluationMenu($strDataID,4,4,$intMenuType);
    // tampilkan data
    $intCol = 30;
    $intRow = 3;
    $intColPx = 300;
    $strReadonly = "";
    //$strReadonly =  "" : "readonly"; // khusus untuk edit note employee
    $strTxtStyle = "width:$intColPx";
    if ($bolPrint) {
      $strTxtStyle .= ";border-style:none;border-size:0px";
    }
    $strInputEmployeeNote = "<textarea name=dataEmployeeNote cols=$intCol rows=$intRow style=\"$strTxtStyle\" $strReadonly>" . $arrData['dataEmployeeNote'] . "</textarea>\n";
    //$strReadonly = ($bolManager) ? "" : "readonly"; // khusus untuk edit note manager
    $strInputManagerNote = "<textarea name=dataManagerNote cols=$intCol rows=$intRow style=\"$strTxtStyle\" $strReadonly>" . $arrData['dataManagerNote'] . "</textarea>\n";
    $strInputEmployeeStrong = "<textarea name=dataEmployeeStrong cols=$intCol rows=$intRow style=\"$strTxtStyle\" $strReadonly>" . $arrData['dataEmployeeStrong'] . "</textarea>\n";
    $strInputImprovement = "<textarea name=dataEmployeeImprovement cols=$intCol rows=$intRow style=\"$strTxtStyle\" $strReadonly>" . $arrData['dataEmployeeImprovement'] . "</textarea>\n";
    $strInputTraining = getDataTraining($db, $strDataID, !$bolManager);
    $strInputMutation = getDataMutation($db, $strDataID, !$bolManager);
    $strInputOther = getDataOther($db, $strDataID, !$bolManager);
    // tambah button untuk print
    //$strButtons .= "&nbsp;<input type=button name='btnPrint' value=\"" .getWords("print")."\" onClick=\"window.open('evaluation_feedback.php?btnPrint=Print&dataID=$strDataID')\"> ";
  }
}
$bolPrint = (isset($_REQUEST['btnPrint']));
if ($bolPrint) {
  $strMainTemplate = getTemplate("evaluation_feedback_print.html");
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