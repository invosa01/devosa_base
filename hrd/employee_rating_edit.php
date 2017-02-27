<?php
include_once('../global/session.php');
include_once('global.php');
include_once('activity.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
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
$bolPrint = (isset($_REQUEST['btnPrint']));
//---- INISIALISASI ----------------------------------------------------
$strWordsAbsenceData = getWords("absence data");
$strWordsEntryRating = getWords("entry rating");
$strWordsRatingDate = getWords("date");
$strWordsRatingPeriod = getWords("Period");
$strWordsAbsenceDateThru = getWords("absence date thru");
$strWordsRatingCode = getWords("value");
$strWordsEmployeeID = getWords("employee id");
$strWordsNote = getWords("note");
$strWordsSave = getWords("save");
$strWordsAddNew = getWords("add new");
$strWordsDocument = getWords("document");
//$strSpecialAbsenceCode       = SPECIAL_ABSENCE_CODE;
$strDataDetail = "";
$strButtons = "";
$strMsgClass = "";
$strMessages = "";
$intDefaultWidth = 50;
$intDefaultHeight = 3;
$strNow = date("Y-m-d");
// inisialisasi untuk data array
// $arrData['dataSection'] = "";
$strUserRole = "";
$arrData = [
    "dataDate"          => $strNow,
    "dataDateFrom"      => $strNow,
    "dataDateThru"      => $strNow,
    "dataEmployee"      => "",
    "dataEmployeeName"  => "",
    "dataSection"       => "",
    "dataType"          => "",
    "dataSpecial"       => "",
    "dataDuration"      => "1",
    "dataLeaveDuration" => "0",
    "dataNote"          => "",
    "dataDoc"           => "",
    //"dataCode" => "ABSEN-HRD",
    //"dataNo" => "",
    //"dataMonth" => "",
    //"dataYear" => "",
    "dataStatus"        => 0,
    "dataID"            => "",
    // untuk keperluan print aja
    "dataDateCreated"   => "",
    "dataDateVerified"  => "",
    "dataDateApproved"  => "",
    "dataPeriod"        => date("Y")
];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db, $strDataID = "")
{
  global $words;
  global $arrData;
  if ($strDataID != "") {
    $strSQL = "SELECT t1.*, t2.employee_id, t2.id as id_employee, t2.employee_name, ";
    $strSQL .= "t3.section_name FROM hrd_employee_rating AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "LEFT JOIN hrd_section AS t3 ON t2.section_code = t3.section_code ";
    $strSQL .= "WHERE t1.id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataEmployee'] = $rowDb['employee_id'];
      $arrData['dataEmployeeName'] = $rowDb['employee_name'];
      $arrData['dataSection'] = $rowDb['section_name'];
      $arrData['dataID'] = $rowDb['id'];
      $arrData['dataCode'] = $rowDb['rating_code'];
      $arrData['dataPeriod'] = $rowDb['period'];
      $arrData['dataDate'] = $rowDb['rating_date'];
      $arrData['dataNote'] = $rowDb['note'];
    }
  }
  return true;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strDataID, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $error;
  global $messages;
  global $arrData;
  global $arrUserInfo;
  $strError = "";
  $bolOK = true;
  $strToday = date("Y-m-d");
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
  (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = "";
  (isset($_REQUEST['dataCode'])) ? $strDataCode = $_REQUEST['dataCode'] : $strDataCode = "";
  (isset($_REQUEST['dataPeriod'])) ? $strDataPeriod = $_REQUEST['dataPeriod'] : $strDataPeriod = "";
  (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
  // cek validasi -----------------------
  if ($strDataEmployee == "") {
    $strError = $error['empty_employee'];
    $bolOK = false;
  } else if ($strDataCode == "") {
    $strError = $error['empty_code'];
    $bolOK = false;
  } else if ($strDataPeriod == "") {
    $strError = $error['invalid_date'];
    $bolOK = false;
  } else if (substr_count($strDataNote, "'")) {
    $strError = $error['invalid_text'];
    $bolOK = false;
  }
  // cari dta Employee ID, apakah ada atau tidak
  $arrEmployee = getEmployeeInfoByCode($db, $strDataEmployee, "id, employee_name");
  if (count($arrEmployee) == 0) {
    $strError = $error['employee_data_not_found'];
    $bolOK = false;
  } else {
    $strIDEmployee = $arrEmployee["id"];
  }
  // simpan data -----------------------
  if ($bolOK) { // input OK, tinggal disimpan
    if ($strDataID == "") {
      // cek data yang duplikat 
      $strSQL = "SELECT id FROM hrd_employee_rating WHERE id_employee = '$strIDEmployee' ";
      $strSQL .= "AND    period = $strDataPeriod ";
      $resS = $db->execute($strSQL);
      echo $strSQL;
      if ($rowDb = $db->fetchrow($resS)) {
        //echo "Sini juga";
        //var_dump($rowDb);
        $strError = $error['overlaping_date_entry'];
        $bolOK = false;
      }
      if ($bolOK) {
        $strSQL = "INSERT INTO hrd_employee_rating (created, created_by, modified_by, ";
        $strSQL .= "id_employee, rating_code, rating_date, period, ";
        $strSQL .= "note) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "'$strIDEmployee','$strDataCode','$strDataDate', $strDataPeriod, ";
        $strSQL .= "'$strDataNote')  ";
        $resExec = $db->execute($strSQL);
        // cari ID
        //echo $strSQl;
        writeLog(
            'EMPLOYEE RATING ADD',
            MODULE_EMPLOYEE,
            $arrEmployee['employee_name'] . " - " . $strDataCode . " - " . $strDataDate . " - " . $strDataPeriod . " ",
            0
        );
        $strError = $messages['data_saved'];
      }
    } else {
      $strSQL = "UPDATE hrd_absence ";
      $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
      $strSQL .= "id_employee = '$strIDEmployee',";
      $strSQL .= "rating_code = '$strDataCode', ";
      $strSQL .= "rating_date = '$strDataDate', ";
      $strSQL .= "period = $strDataPeriod, ";
      $strSQL .= "note = '$strDataNote', duration = '$strDataDuration' ";
      $strSQL .= "WHERE id = '$strDataID' ";
      $resExec = $db->execute($strSQL);
      writeLog('EMPLOYEE RATING UPDATE', MODULE_EMPLOYEE, "Employee rating", 0);
      $strError = $messages['data_saved'];
    }
  } else { // ---- data SALAHs
    // gunakan data yang diisikan tadi
    $arrData['dataEmployee'] = $strDataEmployee;
    $arrData['dataDate'] = $strDataDate;
    $arrData['dataCode'] = $strDataCode;
    $arrData['dataPeriod'] = $strDataPeriod;
    $arrData['dataNote'] = $strDataNote;
    $arrData['dataID'] = $strDataID;
    //writeLog(ACTIVITY_EDIT, MODULE_EMPLOYEE, "data not saved - error: ".$strError, 0);
  }
  //einsert
  return $bolOK;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $strUserRole = $_SESSION['sessionUserRole'];
  if (isset($_REQUEST['dataID'])) {
    $bolIsNew = false;
    $strDataID = $_REQUEST['dataID'];
  } else {
    $strDataID = "";
    $bolIsNew = true;
  }
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      $bolOK = saveData($db, $strDataID, $strError);
      $closeButton = '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
      if ($bolOK) {
        $successIcon = '<i class="fa fa-exclamation-circle"></i>';
        $strMessages = '<div class="alert alert-info">' . $closeButton . $successIcon . $strError . '</div>';
      } else {
        $errorIcon = '<i class="fa fa-times-circle"></i>';
        $strMessages = '<div class="alert alert-danger">' . $closeButton . $errorIcon . $strError . '</div>';
      }
      $strMsgClass = ($bolOK) ? "class = bgOK" : "class = bgError";
    }
  }
  $dtNow = getdate();
  $arrData['dataMonth'] = getRomans($dtNow['mon']);
  $arrData['dataYear'] = $dtNow['year'];
  //$strInputLastNo = getLastFormNumber($db, "hrd_absence", "no", $arrData['dataMonth'], $arrData['dataYear']);
  //$intLastNo = ($strInputLastNo == "") ? 0 : (int)$strInputLastNo;
  //$arrData['dataNo'] = addPrevZero($intLastNo + 1,$intFormNumberDigit);
  getData($db, $strDataID);
  //see common_function.php
  $strReadonly = (scopeGeneralDataEntry(
      $arrData['dataEmployee'],
      $_SESSION['sessionUserRole'],
      $arrUserInfo,
      $bolIsNew
  )) ? "readonly" : "";
  // echo "empdata:".$arrData['dataEmployee'];
  $strIDEmployee = getIDEmployee($db, $arrData['dataEmployee']);
  //----- TAMPILKAN DATA ---------
  //echo "tt".$arrData['dataEmployee'];
  $strInputDate = "<input type=hidden size=15 maxlength=10 name=dataDate id=dataDate value=\"" . $arrData['dataDate'] . "\" >" . $arrData['dataDate'];
  $strInputEmployee = "<input class=\"form-control\" type=text name=dataEmployee id=dataEmployee size=10 maxlength=30 value=\"" . $arrData['dataEmployee'] . "\" $strReadonly >";
  $strInputPeriod = "<input class=\"form-control\" type=text name=dataPeriod id=dataPeriod size=10 maxlength=10 value=\"" . $arrData['dataPeriod'] . "\" class='numeric' >";
  $strInputNote = "<textarea class=\"form-control\" name=dataNote cols=30 rows=3 wrap='virtual' >" . $arrData['dataNote'] . "</textarea>";
  $strSpecial = "";
  $strInputCode = getRatingValueList(
      $db,
      "dataCode",
      $arrData['dataCode'],
      "$strSpecial",
      "",
      " style=\"width:$strDefaultWidthPx\" "
  );
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = getWords('form entry employee rating');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = dataRatingSubmenu($strWordsEntryRating);
if ($bolPrint) {
  $strMainTemplate = getTemplate("employee_rating_edit_print.html");
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>