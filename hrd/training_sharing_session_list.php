<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strWordsInputSharingSession = getWords("input sharing session");
$strWordsSharingSessionList = getWords("sharing session list");
$strWordsInputSharingSessionFromTraining = getWords("input sharing session from training");
$strWordsTrainingType = getWords("training type");
$strWordsTrainingDate = getWords("training date");
$strWordsSharingDate = getWords("sharing session date");
$strWordsTrainingStatus = getWords("training status");
$strWordsLocation = getWords("location");
$strWordsInstitution = getWords("institution");
$strWordsInstructor = getWords("instructor");
$strWordsParticipant = getWords("participant");
$strWordsTraining = getWords("training");
$strWordsSharingSession = getWords("sharing session");
$strWordsSharingSession = getWords("sharing session");
$strWordsDepartment = getWords("department");
$strWordsShowData = getWords("show data");
$strWordsDateFrom = getWords("date from");
$strWordsDateTo = getWords("date to");
$strWordsPurpose = getWords("purpose");
$strWordsTrainer = getWords("trainer");
$strWordsTopic = getWords("topic");
$strWordsPlace = getWords("location");
$strDataDetail = "";
$strHidden = "";
$strButtonList = "";
$intTotalData = 0;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDateFrom, $strDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $ARRAY_REQUEST_STATUS;
  global $_REQUEST;
  $intRows = 0;
  $strResult = "";
  $strSQLT = "
      SELECT t1.*, t2.department_code, t2.training_date, t2.training_date_thru,
        t2.institution, t2.trainer, t2.topic AS training_topic, t3.department_name
      FROM hrd_training_sharing AS t1
      LEFT JOIN hrd_training_request AS t2 ON t1.id_training_request = t2.id
      LEFT JOIN hrd_department AS t3 ON t2.department_code = t3.department_code
      WHERE 1=1
        AND ( 
          t1.date_from BETWEEN '$strDateFrom' AND '$strDateThru' OR
          t1.date_to BETWEEN '$strDateFrom' AND '$strDateThru'
        ) 
        $strKriteria
    ";
  // ambil dulu data partisipan training
  $arrPart1 = [];
  $strSQL = "
      SELECT tp.id_request, tp.id_employee,tp.note, 
        te.employee_id, te.employee_name
      FROM hrd_training_request_participant AS tp
      LEFT JOIN hrd_employee AS te ON te.id = tp.id_employee
      WHERE tp.id_request IN (
        SELECT id_training_request FROM (
          $strSQLT
        ) AS tmp
      )
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrPart1[$rowDb['id_request']][] = $rowDb;
  }
  // ambil dulu data partisipan sharing session
  $arrPart2 = [];
  $strSQL = "
      SELECT tp.id_training_sharing, tp.id_employee,tp.note, 
        te.employee_id, te.employee_name
      FROM hrd_training_sharing_participant AS tp
      LEFT JOIN hrd_employee AS te ON te.id = tp.id_employee
      WHERE tp.id_training_sharing IN (
        SELECT id FROM (
          $strSQLT
        ) AS tmp
      )
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrPart2[$rowDb['id_training_sharing']][] = $rowDb;
  }
  // ambil dulu data trainer sharing session
  $arrTrainer = [];
  $strSQL = "
      SELECT tp.id_training_sharing, tp.id_employee,
        te.employee_id, te.employee_name
      FROM hrd_training_sharing_trainer AS tp
      LEFT JOIN hrd_employee AS te ON te.id = tp.id_employee
      WHERE tp.id_training_sharing IN (
        SELECT id FROM (
          $strSQLT
        ) AS tmp
      )
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrTrainer[$rowDb['id_training_sharing']][] = $rowDb;
  }
  $i = 0;
  $strSQL = "
      SELECT * FROM ( $strSQLT ) AS tmp
      ORDER BY $strOrder date_from 
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strTrainer = $strParticipant = $strTrainingParticipant = "";
    if (isset($arrPart1[$rowDb['id_training_request']])) {
      foreach ($arrPart1[$rowDb['id_training_request']] AS $i => $row) {
        if ($strTrainingParticipant != "") {
          $strTrainingParticipant .= "<br> \n";
        }
        $strTrainingParticipant .= $row['employee_id'] . " - " . $row['employee_name'];
      }
    }
    if (isset($arrPart2[$rowDb['id']])) {
      foreach ($arrPart2[$rowDb['id']] AS $i => $row) {
        if ($strParticipant != "") {
          $strParticipant .= "<br> \n";
        }
        $strParticipant .= $row['employee_id'] . " - " . $row['employee_name'];
      }
    }
    if (isset($arrTrainer[$rowDb['id']])) {
      foreach ($arrTrainer[$rowDb['id']] AS $i => $row) {
        if ($strTrainer != "") {
          $strTrainer .= "<br> \n";
        }
        $strTrainer .= $row['employee_id'] . " - " . $row['employee_name'];
      }
    }
    $strEmployeeInfo = $rowDb['department_code'] . " - " . $rowDb['department_name'];
    $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" >\n";
    $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
    $strResult .= "  <td align=center>" . pgDateFormat($rowDb['date_from'], "d-M-y") . "&nbsp;</td>\n";
    $strResult .= "  <td align=center>" . pgDateFormat($rowDb['date_to'], "d-M-y") . "&nbsp;</td>\n";
    $strResult .= "  <td nowrap>" . $rowDb['place'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap>" . nl2br($rowDb['topic']) . "&nbsp;</td>";
    $strResult .= "  <td nowrap>" . $strTrainer . "&nbsp;</td>";
    $strResult .= "  <td nowrap>" . $strParticipant . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['department_name'] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . pgDateFormat($rowDb['training_date'], "d-M-y") . "&nbsp;</td>\n";
    $strResult .= "  <td align=center>" . pgDateFormat($rowDb['training_date_thru'], "d-M-y") . "&nbsp;</td>\n";
    $strResult .= "  <td>" . $rowDb['training_topic'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['institution'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap>$strTrainingParticipant&nbsp;</td>\n";
    $strResult .= "  <td align=center><a href=\"training_sharing_session_edit.php?dataID=" . $rowDb['id'] . "\">" . getWords(
            'edit'
        ) . "</a>&nbsp;</td>";
    $strResult .= "  <td align=center>&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  return $strResult;
} // showData
// fungsi untuk menghapus data
function deleteData($db)
{
  global $_REQUEST;
  $i = 0;
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 5) == 'chkID') {
      $i++;
      $strSQL = "DELETE FROM hrd_training_sharing_participant WHERE id_training_sharing = '$strValue'; ";
      $strSQL .= "DELETE FROM hrd_training_sharing_trainer WHERE id_training_sharing = '$strValue'; ";
      $strSQL .= "DELETE FROM hrd_training_sharing WHERE id = '$strValue'; ";
      $resExec = $db->execute($strSQL);
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
  }
} //deleteData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    if (!$arrUserInfo['isDeptHead'] && !$arrUserInfo['isGroupHead']) {
      $bolCanView = $bolCanDelete = $bolCanEdit = false;
    }
  }
  // hapus data jika ada perintah
  if (isset($_POST['btnDelete'])) {
    if ($bolCanDelete) {
      deleteData($db);
    }
  }
  // ------ AMBIL DATA KRITERIA -------------------------
  //getDefaultSalaryPeriode($strDefaultFrom,$strDefaultThru);
  $strNow = date("Y-m-d");
  $strDateFrom = (isset($_SESSION['sessionFilterDateFrom'])) ? $_SESSION['sessionFilterDateFrom'] : date("Y-m-d");
  $strDateThru = (isset($_SESSION['sessionFilterDateThru'])) ? $_SESSION['sessionFilterDateThru'] : date("Y-m-d");
  $strDataDepartment = (isset($_SESSION['sessionFilterDepartment'])) ? $_SESSION['sessionFilterDepartment'] : "";
  $strDataSection = (isset($_SESSION['sessionFilterSection'])) ? $_SESSION['sessionFilterSection'] : "";
  if (isset($_REQUEST['dataDateFrom'])) {
    $strDateFrom = $_REQUEST['dataDateFrom'];
  }
  if (isset($_REQUEST['dataDateThru'])) {
    $strDateThru = $_REQUEST['dataDateThru'];
  }
  if (isset($_REQUEST['dataDepartment'])) {
    $strDataDepartment = $_REQUEST['dataDepartment'];
  }
  if (isset($_REQUEST['dataSection'])) {
    $strDataSection = $_REQUEST['dataSection'];
  }
  // simpan dalam session
  $_SESSION['sessionFilterDateFrom'] = $strDateFrom;
  $_SESSION['sessionFilterDateThru'] = $strDateThru;
  $_SESSION['sessionFilterDepartment'] = $strDataDepartment;
  $_SESSION['sessionFilterSection'] = $strDataSection;
  (isset($_REQUEST['dataStatus'])) ? $strDataStatus = $_REQUEST['dataStatus'] : $strDataStatus = "";
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  /*
  if ($arrUserInfo['isDeptHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) $strDataDepartment = $arrUserInfo['department_code'];
  else if ($arrUserInfo['isGroupHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) $strDataSection = $arrUserInfo['section_code'];
  */
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strDataDepartment = $arrUserInfo['department_code'];
  }
  if ($strDataDepartment != "") {
    $strKriteria .= "AND t2.department_code = '$strDataDepartment' ";
  }
  if ($bolCanView) {
    $strDataDetail = getData($db, $strDateFrom, $strDateThru, $intTotalData, $strKriteria);
  } else {
    showError("view_denied");
  }
  if ($bolCanView) {
    //if (isset($_REQUEST['btnExcel'])) $bolLimit = false;
    //$strDataDetail = getData($db,$intTotalData, $strKriteria, $intCurrPage, $bolLimit);
    if (isset($_REQUEST['btnExcel'])) {
      $strDataDetail = getData($db, $strDateFrom, $strDateThru, $intTotalData, $strKriteria);
      // ambil data CSS-nya
      if (file_exists("../css/excel.css")) {
        $strStyle = "../css/excel.css";
      }
      $strPrintCss = "";
      $strPrintInit = "";
      headeringExcel("trainingRequest.xls");
    }
  }
  $intDefaultWidthPx = 200;
  $strFilterDateFrom = "<input type=text size=15 maxlength=10 name=dataDateFrom id=dataDateFrom value=\"$strDateFrom\">";
  $strFilterDateThru = "<input type=text size=15 maxlength=10 name=dataDateThru id=dataDateThru value=\"$strDateThru\">";
  $strFilterDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $strDataDepartment,
      $strEmptyOption,
      "",
      "style=\"width:$intDefaultWidthPx\" "
  );
  $strFilterSection = getSectionList(
      $db,
      "dataSection",
      $strDataSection,
      $strEmptyOption,
      "",
      "style=\"width:$intDefaultWidthPx\" "
  );
  /*
  if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    $strButtonList .= "&nbsp;<input type=submit name=btnChecked value=\"" .getWords('checked'). "\" onClick=\"return confirmStatusChanges(false)\">";
    $strButtonList .= "&nbsp;<input type=submit name=btnDenied value=\"" .getWords('denied'). "\" onClick=\"return confirmStatusChanges(true)\">";
  } else if ($_SESSION['sessionUserRole'] == ROLE_MANAGER) {
    $strButtonList .= "&nbsp;<input type=submit name=btnChecked value=\"" .getWords('checked'). "\" onClick=\"return confirmStatusChanges(false)\">";
    $strButtonList .= "&nbsp;<input type=submit name=btnApproved value=\"" .getWords('approved'). "\" onClick=\"return confirmStatusChanges(false)\">";
    $strButtonList .= "&nbsp;<input type=submit name=btnDenied value=\"" .getWords('denied'). "\" onClick=\"return confirmStatusChanges(true)\">";
    $strButtonList .= "&nbsp;<input type=submit name=btnDelete value=\"" .getWords('delete'). "\" onClick=\"return confirmDelete()\">";
  } else if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
    $strButtonList .= "&nbsp;<input type=submit name=btnVerified value=\"" .getWords('verified'). "\" onClick=\"return confirmStatusChanges(false)\">";
    //$strButtonList .= "&nbsp;<input type=submit name=btnDenied value=\"" .getWords('denied'). "\" onClick=\"return confirmStatusChanges(true)\">";
  }
  */
  if ($bolCanDelete) {
    $strButtonList .= "&nbsp;<input type=submit name=btnDelete value=\"" . getWords(
            'delete'
        ) . "\" onClick=\"return confirmDelete()\">";
  }
  // informasi tanggal kehadiran
  if ($strDateFrom == $strDateThru) {
    $strInfo .= "<br>" . strtoupper(pgDateFormat($strDateFrom, "d-M-Y"));
  } else {
    $strInfo .= "<br>" . strtoupper(pgDateFormat($strDateFrom, "d-M-Y"));
    $strInfo .= " >> " . strtoupper(pgDateFormat($strDateThru, "d-M-Y"));
  }
  $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDateFrom\">";
  $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDateThru\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
  $strHidden .= "<input type=hidden name=dataStatus value=\"$strDataStatus\">";
}
if ($bolPrint) {
  $strMainTemplate = getTemplate(str_replace(".php", "_print.html", basename($_SERVER['PHP_SELF'])));
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>