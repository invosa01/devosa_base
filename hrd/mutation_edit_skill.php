<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
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
$strDataDetail = "";
$intDefaultWidth = 30;
$intDefaultHeight = 3;
$strNow = date("Y-m-d");
$strMessages = "";
$strMsgClass = "";
$bolError = false;
$strBtnPrint = "";
$strWordsProposalDate = getWords("proposal date");
$strWordsEmployeeID = getWords("employee id");
$strWordsLetterCode = getWords("letter code");
$strWordsDateFrom = getWords("date from");
$strWordsEmployeeGrade = getWords("employee grade");
$strWordsRecentLevel = getWords("recent level");
$strWordsGrade = getWords("grade");
$strWordsStartDate = getWords("start date");
$strWordsNewLevel = getWords("new level");
$strWordsStartDate = getWords("start date");
$strWordsNote = getWords("note");
$strWordsStatus = getWords("status");
$arrData = [
    "dataDate"         => $strNow,
    "dataEmployee"     => "",
    "dataLetterCode"   => "",
    "dataIsResign"     => false, // apakah ada pemberhentian
    "dataIsPosition"   => false, // apakah ada perubahan jabatan
    "dataIsDepartment" => false, // apakah ada perubahan department
    "dataIsSalary"     => false, // apakah ada perubahan gaji
    "dataPositionOld"     => "",
    "dataPositionNew"     => "",
    "dataGradeOld"        => "",
    "dataGradeNew"        => "",
    "dataPositionOldDate" => "",//$strNow,
    "dataPositionNewDate" => date("Y-m-d"),//$strNow,
    "dataStatus" => "0", //status dari proposal (baru, verifikasi, setuju, tolak)
    "dataNote"   => "",
    "dataID"     => "",
];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db)
{
  global $words;
  global $arrData;
  global $strDataID;
  if ($strDataID != "") {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "ID=$strDataID", 0);
    // cari posiition
    $strSQL = "SELECT * FROM hrd_employee_mutation_position WHERE id_mutation = '$strDataID' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $arrData['dataIsPosition'] = true;
      $arrData['dataPositionOld'] = $rowTmp['position_old'];
      $arrData['dataPositionNew'] = $rowTmp['position_new'];
      $arrData['dataGradeOld'] = $rowTmp['grade_old'];
      $arrData['dataGradeNew'] = $rowTmp['grade_new'];
      $arrData['dataPositionOldDate'] = $rowTmp['position_old_date'];
      $arrData['dataPositionNewDate'] = $rowTmp['position_new_date'];
    }
  }
  return true;
} // showData
// fungsi untuk menyimpan data absen
function saveData($db, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $error;
  global $messages;
  global $arrData;
  global $strDataID;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strError = "";
  $bolOK = true;
  $strToday = date("Y-m-d");
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = trim($_REQUEST['dataEmployee']) : $strDataEmployee = "";
  (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = "";
  (isset($_REQUEST['dataLetterCode'])) ? $strDataLetterCode = $_REQUEST['dataLetterCode'] : $strDataLetterCode = "";
  (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
  // cek validasi -----------------------
  if ($strDataEmployee == "") {
    $strError = $error['empty_code'];
    $bolOK = false;
  } else if (!validStandardDate($strDataDate)) {
    $strError = $error['invalid_date'];
    $bolOK = false;
  }
  // cari dta Employee ID, apakah ada atau tidak
  $strSQL = "SELECT id FROM hrd_employee WHERE employee_id = '$strDataEmployee' AND flag = 0 ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strIDEmployee = $rowDb['id'];
  } else {
    $strIDEmployee = "";
  }
  if ($strIDEmployee == "") {
    $strError = $error['data_not_found'];
    $bolOK = false;
  }
  // simpan data -----------------------
  if ($bolOK) { // input OK, tinggal disimpan
    if ($strDataID == "") {
      // data baru
      $strSQL = "INSERT INTO hrd_employee_mutation (created,created_by,modified_by, ";
      $strSQL .= "id_employee,proposal_date, note, letter_code, type) ";
      $strSQL .= "VALUES(now(),'$strmodified_byID','$strmodified_byID', ";
      $strSQL .= "'$strIDEmployee','$strDataDate', '$strDataNote', '$strDataLetterCode',  0)  ";
      $resExec = $db->execute($strSQL);
      // cari ID
      $strSQL = "SELECT id FROM hrd_employee_mutation ";
      $strSQL .= "WHERE id_employee = '$strIDEmployee' AND proposal_date = '$strDataDate' ";
      $strSQL .= "AND type = 0 AND status = " . REQUEST_STATUS_NEW;
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $strDataID = $rowDb['id'];
      }
    } else {
      $strSQL = "UPDATE hrd_employee_mutation ";
      $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
      $strSQL .= "id_employee = '$strIDEmployee', proposal_date = '$strDataDate', ";
      $strSQL .= "note = '$strDataNote', letter_code = '$strDataLetterCode'  WHERE id = '$strDataID' ";
      $resExec = $db->execute($strSQL);
    }
    // simpan data detilnya, jika ada
    if ($strDataID != "") {
      //hapus dulu semua data
      $strSQL .= "DELETE FROM hrd_employee_mutation_position WHERE id_mutation = '$strDataID'; ";
      $resDb = $db->execute($strSQL);
      /*
      if (isset($_REQUEST['dataIsResign'])) {
        // simpan data resign
        $strDate = (isset($_REQUEST['dataResignDate'])) ? $_REQUEST['dataResignDate'] : "";

        $strDate = ($strDate == "") ? "NULL" : "'$strDate'";

        $strSQL  = "INSERT INTO hrd_employee_mutation_resign (created, modified_by, created_by, ";
        $strSQL .= "id_mutation, resign_date) ";
        $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '$strDataID', $strDate) ";
        $resExec = $db->execute($strSQL);

      }
      */
    }
    if (isset($_REQUEST['dataIsPosition'])) {
      // simpan data status
      $strPositionOld = (isset($_REQUEST['dataPositionOld'])) ? $_REQUEST['dataPositionOld'] : "";
      $strPositionNew = (isset($_REQUEST['dataPositionNew'])) ? $_REQUEST['dataPositionNew'] : "";
      $strGradeOld = (isset($_REQUEST['dataGradeOld'])) ? $_REQUEST['dataGradeOld'] : "";
      $strGradeNew = (isset($_REQUEST['dataGradeNew'])) ? $_REQUEST['dataGradeNew'] : "";
      $strDateOld = (isset($_REQUEST['dataPositionOldDate'])) ? $_REQUEST['dataPositionOldDate'] : "";
      $strDateNew = (isset($_REQUEST['dataPositionNewDate'])) ? $_REQUEST['dataPositionNewDate'] : "";
      $strDateOld = ($strDateOld == "") ? "NULL" : "'$strDateOld'";
      $strDateNew = ($strDateNew == "") ? "NULL" : "'$strDateNew'";
      $strSQL = "INSERT INTO hrd_employee_mutation_position (created, modified_by, created_by, ";
      $strSQL .= "id_mutation, position_old, position_new, grade_old, ";
      $strSQL .= "grade_new, \"position_old_date\", \"position_new_date\") ";
      $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '$strDataID', ";
      $strSQL .= "'$strPositionOld', '$strPositionNew', '$strGradeOld', '$strGradeNew', ";
      $strSQL .= "$strDateOld, $strDateNew) ";
      $resExec = $db->execute($strSQL);
    }
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "MUTATION DATA", 0);
    $strError = $messages['data_saved'];
  } else { // ---- data SALAH
    // gunakan data yang diisikan tadi
    $arrData['dataEmployee'] = $strDataEmployee;
    $arrData['dataDate'] = $strDataDate;
    $arrData['dataLetterCode'] = $strDataLetterCode;
    $arrData['dataNote'] = $strDataNote;
    $arrData['dataID'] = $strDataID;
    getInfoEmployee($db);
    $arrData['dataIsPosition'] = (isset($_REQUEST['dataIsPosition'])) ? "true" : "";
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "ABSENCE DATA", 0);
  }
  return $bolOK;
} // saveData
// fungsi untuk mengambil data employee yang terakhir
function getInfoEmployee($db)
{
  global $_REQUEST;
  global $_SESSION;
  global $arrData;
  $strID = (isset($_REQUEST['dataEmployee'])) ? $_REQUEST['dataEmployee'] : "";
  //(isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = trim($_REQUEST['dataEmployee']) : $strDataEmployee = "";
  (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = "";
  (isset($_REQUEST['dataLetterCode'])) ? $strDataLetterCode = $_REQUEST['dataLetterCode'] : $strDataLetterCode = "";
  (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
  if ($strID != "") {
    $arrData['dataEmployee'] = $strID;
    $arrData['dataDate'] = $strDataDate;
    $arrData['dataLetterCode'] = $strDataLetterCode;
    $arrData['dataNote'] = $strDataNote;
    $strSQL = "SELECT * FROM hrd_employee WHERE employee_id = '$strID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['employee_status'] == 1) { // permanent
        $arrData['dataStatusDateFrom'] = $rowDb['permanent_date'];
        $arrData['dataStatusDateThru'] = "";
      } else {
        $arrData['dataStatusDateFrom'] = $rowDb['join_date'];
        $arrData['dataStatusDateThru'] = $rowDb['due_date'];
      }
      $arrData['dataPositionOld'] = $rowDb['position_code'];
      $arrData['dataGradeOld'] = $rowDb['grade_code'];
      // cari data history masing-masing
      $strSQL = "SELECT t2.approved_time::date AS approved_date FROM hrd_employee_mutation_position AS t1,  ";
      $strSQL .= "hrd_employee_mutation AS t2 WHERE t1.id_mutation = t2.id ";
      $strSQL .= "AND t2.id_employee = '" . $rowDb['id'] . "' AND t2.status = " . MUTATION_STATUS_APPROVED;
      $strSQL .= "ORDER BY t2.proposal_date DESC LIMIT 1 ";
      $resTmp = $db->execute($strSQL);
      //echo $strSQL;
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $arrData['dataPositionOldDate'] = $rowTmp['approved_date'];
      } else {
        $arrData['dataPositionOldDate'] = $rowDb['join_date'];
      }
      $idSalarySet = "";
      // cari data salary
    }
  }
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    if (!$arrUserInfo['isDeptHead'] && !$arrUserInfo['isGroupHead']) {
      $bolCanDelete = $bolCanEdit = $bolCanView = false;
    }
  }
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      $bolOK = saveData($db, $strError);
      if ($strError != "") {
        $strMessages = $strError;
        $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
      }
      //$strDataID = ""; // biar tidak mengambil dta, melainkan pakai data baru atau data yang dikirim (jika error)
    }
  }
  if (isset($_REQUEST['btnGet'])) {
    // ambil info tentang employee
    getInfoEmployee($db);
  } else if (isset($_REQUEST['btnRenew'])) {
    getEmployeeStatusInfo($db);
  }
  //$arrData['dataEmployee'] = $arrUserInfo['employee_id']; // beri default user
  if ($bolCanView) {
    getData($db);
  } else {
    showError("view_denied");
  }
  //----- TAMPILKAN DATA ---------
  //echo $strStatus;
  $strInputDate = "<input type=text size=15 maxlength=10 name=dataDate id=dataDate value=\"" . $arrData['dataDate'] . "\" $strReadonly>";
  $strInputPositionOldDate = "<input type=text size=15 maxlength=10 name=dataPositionOldDate id=dataPositionOldDate value=\"" . $arrData['dataPositionOldDate'] . "\" $strReadonly>";
  $strInputPositionNewDate = "<input type=text size=15 maxlength=10 name=dataPositionNewDate id=dataPositionNewDate value=\"" . $arrData['dataPositionNewDate'] . "\" $strReadonly>";
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"" . $arrData['dataEmployee'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly>";
  $strInputLetterCode = "<input type=\"text\" name=dataLetterCode size=15 maxlength=63 value=\"" . $arrData['dataLetterCode'] . "\" style=\"width:$strDefaultWidthPx\"  >";
  $strInputNote = "<textarea name=dataNote cols=30 rows=3 wrap='virtual' style=\"width:$strDefaultWidthPx\" >" . $arrData['dataNote'] . "</textarea>";
  $strInputStatusNew = getEmployeeStatusList(
      "dataStatusNew",
      $arrData['dataStatusNew'],
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly",
      "<option value='99'>Resigned</option>\n"
  );
  $strInputStatus = $words[$ARRAY_REQUEST_STATUS[$arrData['dataStatus']]];
  $strInputPositionOld = getPositionList(
      $db,
      "dataPositionOld",
      $arrData['dataPositionOld'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputPositionNew = getPositionList(
      $db,
      "dataPositionNew",
      $arrData['dataPositionNew'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputGradeOld = getSalaryGradeList(
      $db,
      "dataGradeOld",
      $arrData['dataGradeOld'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputGradeNew = getSalaryGradeList(
      $db,
      "dataGradeNew",
      $arrData['dataGradeNew'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strChecked = ($arrData['dataIsPosition']) ? "checked" : "";
  $strClick = " onClick = \"checkPosition()\" ";
  $strInputIsPosition = "<input type=checkbox name=dataIsPosition value=0 $strChecked $strClick $strReadonly>";
  if ($bolPrint) {
    $strInputEmployee = $arrData['dataEmployee'] . " / " . $arrData['dataEmployeeName'];
    $strInputPosition = $arrData['dataPosition'];
    $strInputGrade = $arrData['dataGrade'];
    $strInputJoinDate = pgDateFormat($arrData['dataJoinDate'], "d M Y");
    $strInputStatusNew = ($arrData['dataStatusNew'] === "") ? "" : getWords(
        $ARRAY_EMPLOYEE_STATUS[$arrData['dataStatusNew']]
    );
    $strInputPositionNew = $arrData['dataPositionNew'];
    $strInputPositionOld = $arrData['dataPositionOld'];
    $strInputPositionOldDate = pgDateFormat($arrData['dataPositionOldDate'], "d M Y");
    $strInputPositionNewDate = pgDateFormat($arrData['dataPositionNewDate'], "d M Y");
    $strInputGradeNew = $arrData['dataGradeNew'];
    $strInputGradeOld = $arrData['dataGradeOld'];
    $strInputNote = nl2br($arrData['dataNote']);
  }
  // tambahan tombol
  $strDisabledPrint = ($strDataID != "") ? "" : "disabled";
  $strBtnPrint .= "<input type=button name=btnPrint onClick=\"window.open('mutation_edit_skill.php?btnPrint=Print&dataID=$strDataID');\" value=\"" . getWords(
          "print"
      ) . "\" $strDisabledPrint>";
}
($bolPrint) ? $strMainTemplate = getTemplate("mutation_edit_print.html", false) : $strTemplateFile = getTemplate(
    "mutation_edit_skill.html"
);
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>