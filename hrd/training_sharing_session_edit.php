<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
include_once('../global/employee_function.php');
include_once('cls_employee.php');
//include_once(getTemplate("words.inc"));
$dataPrivilege = getDataPrivileges(
    "training_sharing_session_list.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$strTemplateFile = getTemplate("training_request_edit.html");
//---- INISIALISASI ----------------------------------------------------
$strWordsInputSharingSession = getWords("input sharing session");
$strWordsSharingSessionList = getWords("sharing session list");
$strWordsInputSharingSessionFromTraining = getWords("input sharing session from training");
$strWordsTrainingCategory = getWords("training category");
$strWordsTrainingProfile = getWords("training profile");
$strWordsTrainingTopic = getWords("training topic");
$strWordsTrainingType = getWords("training type");
$strWordsTrainingDate = getWords("training date");
$strWordsSharingDate = getWords("sharing session date");
$strWordsTrainingStatus = getWords("training status");
$strWordsLocation = getWords("location");
$strWordsInstitution = getWords("institution");
$strWordsInstructor = getWords("instructor");
$strWordsExpectedResult = getWords("expected result");
$strWordsExpectedDate = getWords("expected date");
$strWordsParticipant = getWords("participant");
$strWordsRequestStatus = getWords("request status");
$strWordsRequestNumber = getWords("request no.");
$strWordsTraining = getWords("training");
$strWordsNewFromTraining = getWords("add new from training");
$strWordsPurpose = getWords("purpose");
$strWordsTrainer = getWords("trainer");
$strWordsDate = getWords("date");
$strWordsUntil = getWords("until");
$strWordsTopic = getWords("topic");
$strWordsSave = getWords("save");
$strWordsCancel = getWords("cancel");
$strWordsNew = getWords("add new");
$strDataDetail = "";
$intDefaultWidth = 30;
$intDefaultHeight = 3;
$strNow = date("Y-m-d");
$strMessages = "";
$strMsgClass = "";
$strInputLastNumber = "";
$strInputType = "";
$strInputParticipant = "";
$intFormNumberDigit = "";
$strTargetElements = "";
$bolError = false;
$strUserRole = "";
$strShowTraining = "";
$strDataTrainingID = (isset($_REQUEST['dataTrainingID'])) ? $_REQUEST['dataTrainingID'] . "" : "";
$strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
// per 28 Okt, sharing session bisa tanpa training
if ($strDataTrainingID == "") {
  //redirectPage("training_request_list.php");
  //exit();
  $strShowTraining = " style=\"display:none\" ";
}
$arrData = [
  // info training
  "dataTrainingDate"        => "",
  "dataTrainingDateTo"      => "",
  "dataTrainingTopic"       => "",
  "dataTrainingPurpose"     => "",
  "dataTrainingResult"      => "",
  "dataTrainingTrainer"     => "",
  "dataTrainingInstitution" => "",
  "dataTrainingType"        => "", // daftar
  "dataTrainingCategory"    => "0",
  "dataTrainingPlace"       => "0",
  "dataTrainingID"          => $strDataTrainingID,
  // data sharing session
  "dataID"                  => "",
  "dataDateFrom"            => "",
  "dataDateTo"              => "",
  "dataPlace"               => "",
  "dataTopic"               => "",
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
  global $strDataTrainingID;
  global $_REQUEST;
  // cek data sharing session
  if ($strDataID != "") {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "ID=$strDataID", 0);
    $strSQL = "
        SELECT *
        FROM hrd_training_sharing
        WHERE id = '$strDataID' 
      ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataDateFrom'] = $rowDb['date_from'];
      $arrData['dataDateTo'] = $rowDb['date_to'];
      $arrData['dataPlace'] = $rowDb['place'];
      $arrData['dataTopic'] = $rowDb['topic'];
      $arrData['dataID'] = $strDataID;
      $arrData['dataTrainingID'] = $rowDb['id_training_request'];
      $strDataTrainingID = $rowDb['id_training_request'] . "";
    }
  }
  // cek training data
  if ($strDataTrainingID != "") {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "trainingID=$strDataTrainingID", 0);
    $strSQL = "
        SELECT t1.*, t2.employee_id 
        FROM hrd_training_request AS t1 
        LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id 
        WHERE t1.id = '$strDataTrainingID' 
      ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataTrainingDate'] = $rowDb['training_date'];
      $arrData['dataTrainingDateTo'] = $rowDb['training_date_thru'];
      $arrData['dataTrainingPlace'] = $rowDb['place'];
      $arrData['dataTrainingCategory'] = $rowDb['category'];
      $arrData['dataTrainingPurpose'] = $rowDb['purpose'];
      $arrData['dataTrainingTopic'] = $rowDb['topic'];
      $arrData['dataTrainingTrainer'] = $rowDb['trainer'];
      $arrData['dataTrainingInstitution'] = $rowDb['institution'];
      $arrData['dataTrainingResult'] = $rowDb['result'];
      $arrData['dataTrainingID'] = $rowDb['id'];
    }
  }
  return true;
} // showData
// fungsi mengambil daftar trainer yang merupakan peserta training sebelumnya
function getTrainingTrainer($db)
{
  global $words;
  global $strTargetElements;
  global $_REQUEST;
  global $arrData;
  $intMaxShow = 1; // tambahan yang perlu dimunculkan
  $intAdd = 20; // maksimum tambahan
  $intRows = 0;
  $intShown = 0;
  $strResult = "";
  $arrStatus = ["ok", "cancel"];
  $strResult .= " <table border=0 class=gridTable cellpadding=0 cellspacing=0 width=100%>\n";
  $strResult .= "  <tr valign=top class=tableHeader>\n";
  $strResult .= "  <td nowrap width=10>&nbsp;" . getWords('no') . "</td>\n";
  $strResult .= "  <td nowrap width=30>&nbsp;" . getWords('employee id') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('name') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('status') . "</td>\n";
  $strResult .= "  </tr>\n";
  $arrParticipant = [];
  $arrTrainer = [];
  // cari dulu trainer yang terdaftar dalam sharing session
  if ($arrData['dataID'] != "") {
    $strSQL = "
        SELECT td.*, te.employee_id, te.employee_name 
        FROM hrd_training_sharing_trainer AS td
        LEFT JOIN (
          SELECT * FROM hrd_employee 
        ) AS te ON td.id_employee = te.id
        WHERE td.id_training_sharing = '" . $arrData['dataID'] . "'
      ";
    $res = $db->execute($strSQL);
    while ($row = $db->fetchrow($res)) {
      $arrTrainer[$row['id_employee']] = $row;
    }
  }
  // cari dulu peserta training, yang akan menjadi trainer
  if ($arrData['dataTrainingID'] != "") {
    $strSQL = "
        SELECT t1.*, t2.employee_id, t2.employee_name 
        FROM hrd_training_request_participant AS t1
        LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
        WHERE t1.id_request = '" . $arrData['dataTrainingID'] . "'
      ";
    $res = $db->execute($strSQL);
    while ($row = $db->fetchrow($res)) {
      $arrParticipant[$row['id_employee']] = $row;
    }
  }
  // tampilkan
  foreach ($arrParticipant AS $id => $rowDb) {
    $intRows++;
    $intShown++;
    $strCheck = (isset($arrTrainer[$id])) ? "checked" : "";
    $strResult .= "<tr valign=top  id=\"detailTrainer$intRows\">\n";
    $strResult .= "  <td align=right nowrap>$intRows&nbsp;</td>";
    $strResult .= "  <td nowrap>" . $rowDb['employee_id'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap>" . $rowDb['employee_name'] . "&nbsp;</td>";
    $strResult .= "  <td align=center><input type=checkbox id='chkTrainer$intRows' name='chkTrainer$intRows' $strCheck value='" . $id . "'></td>\n";
    $strResult .= "</tr>\n";
  }
  if ($arrData['dataTrainingID'] == "") { // tanpa training, jadi ID karyawan bisa diisikan
    // cek dulu data yang sudah ada
    foreach ($arrTrainer AS $id => $rowDb) {
      $intRows++;
      $strResult .= "<tr valign=top  id=\"detailTrainer$intRows\">\n";
      $intShown++;
      $strResult .= "  <td align=right nowrap>$intRows&nbsp;</td>";
      $strAction = "onFocus = \"AC_kode = 'detailTrainerID$intRows';AC_nama='detailTrainerName$intRows';\" ";
      $strResult .= "  <td><input type=text size=20 maxlength=50 name='detailTrainerID$intRows' id='detailTrainerID$intRows' $strAction value=\"" . $rowDb['employee_id'] . "\"></td>";
      $strResult .= "  <td nowrap>&nbsp;<strong id=\"detailTrainerName$intRows\">" . $rowDb['employee_name'] . "</strong></td>";
      $strResult .= "  <td align=center>&nbsp;</td>\n";
      $strResult .= "</tr>\n";
      $strTargetElements .= ",\"detailTrainerID$intRows\"";
    }
    // tambahkan dengan data kosong
    for ($i = 1; $i <= $intAdd; $i++) {
      $intRows++;
      if ($intRows <= $intMaxShow) {
        $strResult .= "<tr valign=top  id=\"detailTrainer$intRows\">\n";
        $intShown++;
        $strDisabled = "";
      } else {
        $strResult .= "<tr valign=top  id=\"detailTrainer$intRows\" style=\"display:none\">\n";
        $strDisabled = "disabled";
      }
      $strResult .= "  <td align=right nowrap>$intRows&nbsp;</td>";
      $strAction = "onFocus = \"AC_kode = 'detailTrainerID$intRows';AC_nama='detailTrainerName$intRows';\" ";
      $strResult .= "  <td><input type=text size=20 maxlength=50 name='detailTrainerID$intRows' id='detailTrainerID$intRows' $strDisabled $strAction></td>";
      $strResult .= "  <td nowrap>&nbsp;<strong id=\"detailTrainerName$intRows\"></strong></td>";
      $strResult .= "  <td align=center>&nbsp;</td>\n";
      $strResult .= "</tr>\n";
      $strTargetElements .= ",\"detailTrainerID$intRows\"";
    }
    $strResult .= " <tr valign=top><td>&nbsp;</td>\n";
    $strResult .= "  <td colspan=3>&nbsp;<a href=\"javascript:showMoreInput(0);\">" . getWords(
            'more'
        ) . "</a></td></tr>\n";
  }
  // tambahkan hidden data
  $strResult .= "<input type=hidden name='maxTrainerDetail' id='maxTrainerDetail' value=$intRows>";
  $strResult .= "<input type=hidden name='numTrainerShow' id='numTrainerShow' value=$intShown>";
  $strResult .= "  </table>\n";
  // tambahkan hidden data
  $strResult .= "<input type=hidden name='totalTrainer' id='totalTrainer' value=$intRows>";
  return $strResult;
} // getTrainingTrainer
// fungsi mengambil daftar participant yang dimohonkan
function getTrainingParticipant($db, $strDataID = "")
{
  global $words;
  global $strTargetElements;
  global $_REQUEST;
  $intMaxShow = 4; // tambahan yang perlu dimunculkan
  $intAdd = 50; // maksimum tambahan
  $intRows = 0;
  $intShown = 0;
  $strResult = "";
  $arrStatus = ["ok", "cancel"];
  $strResult .= " <table border=0 class=gridTable cellpadding=0 cellspacing=0 width=100%>\n";
  $strResult .= "  <tr valign=top class=tableHeader>\n";
  $strResult .= "  <td nowrap width=10>&nbsp;" . getWords('no') . "</td>\n";
  $strResult .= "  <td nowrap width=30>&nbsp;" . getWords('employee id') . "</td>\n";
  $strResult .= "  <td nowrap width='100'>&nbsp;" . getWords('name') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('note') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('delete') . "</td>\n";
  $strResult .= "  </tr>\n";
  $arrParticipant = [];
  if ($strDataID != "") {
    $strSQL = "
        SELECT t1.id, t1.note, t1.status, t2.employee_id, t2.employee_name 
        FROM hrd_training_sharing_participant AS t1 
        LEFT JOIN hrd_employee AS t2 ON t2.id = t1.id_employee 
        WHERE t1.id_training_sharing = '$strDataID' 
      ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrParticipant[$rowDb['id']] = $rowDb;
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  foreach ($arrParticipant AS $id => $rowDb) {
    $intRows++;
    $intShown++;
    $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
    $strResult .= "  <td align=right nowrap><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">$intRows&nbsp;</td>";
    $strAction = "onFocus = \"AC_kode = 'detailEmployeeID$intRows';AC_nama='detailName$intRows';\" ";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailEmployeeID$intRows id=detailEmployeeID$intRows value=\"" . $rowDb['employee_id'] . "\" $strAction></td>";
    $strResult .= "  <td nowrap>&nbsp;<strong id=\"detailName$intRows\">" . $rowDb['employee_name'] . "</strong></td>";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailNote$intRows id=detailNote$intRows value=\"" . $rowDb['note'] . "\"></td>";
    $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' $strAction></td>\n";
    $strResult .= "</tr>\n";
    $strTargetElements .= ",\"detailEmployeeID$intRows\"";
  }
  // tambahkan dengan data kosong
  for ($i = 1; $i <= $intAdd; $i++) {
    $intRows++;
    if ($intRows <= $intMaxShow) {
      $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
      $intShown++;
      $strDisabled = "";
    } else {
      $strResult .= "<tr valign=top  id=\"detailRows$intRows\" style=\"display:none\">\n";
      $strDisabled = "disabled";
    }
    $strResult .= "  <td align=right nowrap>$intRows&nbsp;</td>";
    $strAction = "onFocus = \"AC_kode = 'detailEmployeeID$intRows';AC_nama='detailName$intRows';\" ";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailEmployeeID$intRows id=detailEmployeeID$intRows $strDisabled $strAction></td>";
    $strResult .= "  <td nowrap>&nbsp;<strong id=\"detailName$intRows\"></strong></td>";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailNote$intRows id=detailNote$intRows></td>";
    $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' $strAction></td>\n";
    $strResult .= "</tr>\n";
    $strTargetElements .= ",\"detailEmployeeID$intRows\"";
  }
  $strResult .= " <tr valign=top><td>&nbsp;</td>\n";
  $strResult .= "  <td colspan=4>&nbsp;<a href=\"javascript:showMoreInput(1);\">" . getWords(
          'more'
      ) . "</a></td></tr>\n";
  $strResult .= "  </table>\n";
  // tambahkan hidden data
  $strResult .= "<input type=hidden name='maxDetail' id='maxDetail' value=$intRows>";
  $strResult .= "<input type=hidden name='numShow' id='numShow' value=$intShown>";
  return $strResult;
} // getTrainingParticipant
// fungsi untuk menyimpan data absen
function saveData($db, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $error;
  global $messages;
  global $arrData;
  global $strDataID;
  global $strDataTrainingID;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strError = "";
  $bolOK = true;
  $strToday = date("Y-m-d");
  $strDateFrom = (isset($_REQUEST['dataDateFrom'])) ? $_REQUEST['dataDateFrom'] : "";
  $strDateTo = (isset($_REQUEST['dataDateTo'])) ? $_REQUEST['dataDateTo'] : "";
  $strTopic = (isset($_REQUEST['dataTopic'])) ? $_REQUEST['dataTopic'] : "";
  $strPlace = (isset($_REQUEST['dataPlace'])) ? $_REQUEST['dataPlace'] : "";;
  // cek validasi -----------------------
  if (!validStandardDate($strDateFrom)) {
    $strError = getWords('invalid_date') . " " . getWords("date from");
    $bolOK = false;
  } else if (!validStandardDate($strDateTo)) {
    $strError = getWords('invalid_date') . " " . getWords("date to");
    $bolOK = false;
  }
  /*
  else if ($strDataTrainingID == "")
  {
    $strError = getWords("data not found"). " - " .getWords("training request");
    $bolOK = false;
  }
  */
  // simpan data -----------------------
  $db->execute("begin");
  if ($bolOK) { // input OK, tinggal disimpan
    if ($strDataID == "") {
      // data baru
      $strDataID = $db->getNextID("hrd_training_sharing_id_seq");
      $strSQL = "
          INSERT INTO hrd_training_sharing (created, created_by, modified_by, 
            id_training_request, date_from, date_to, 
            topic, place, id) 
          VALUES(now(),'$strmodified_byID','$strmodified_byID', 
            " . handleNull($strDataTrainingID) . ", '$strDateFrom', '$strDateTo',
            '$strTopic', '$strPlace', '$strDataID') 
        ";
      $resExec = $db->execute($strSQL);
      if ($resExec == false) {
        $bolOK = false;
      }
    } else {
      $strSQL = "
          UPDATE hrd_training_sharing SET modified = now(),
            modified_by = '" . $_SESSION['sessionUserID'] . "',
            date_from = '$strDateFrom', date_to = '$strDateTo', 
            topic = '$strTopic', place = '$strPlace'
          WHERE id = '$strDataID' 
        ";
      $resExec = $db->execute($strSQL);
      if ($resExec == false) {
        $bolOK = false;
      }
    }
    if ($strDataID != "" && $bolOK) {
      $objEmp = new clsEmployees($db);
      $objEmp->loadData("id, employee_id, employee_name, active ", "AND active <> 2 ");
      // update data trainer yang mengikut
      $strSQL = "DELETE FROM hrd_training_sharing_trainer WHERE id_training_sharing = '$strDataID'; ";
      $intTotalTrainer = (isset($_POST['totalTrainer'])) ? $_POST['totalTrainer'] : 0;
      for ($i = 0; $i <= $intTotalTrainer; $i++) {
        $strID = (isset($_POST['chkTrainer' . $i])) ? $_POST['chkTrainer' . $i] : ""; // cek jika data dari training
        if ($strID == "") // mungkin tanpa training
        {
          $strEmpID = (isset($_REQUEST['detailTrainerID' . $i])) ? $_REQUEST['detailTrainerID' . $i] : "";
          if ($strEmpID != "") {
            // cari IDnya, kalau ada
            $strID = $objEmp->getIDByCode($strEmpID);
          }
        }
        if ($strID != "") {
          $strSQL .= "
              INSERT INTO hrd_training_sharing_trainer 
                (id_training_request, id_training_sharing, id_employee)
              VALUES (" . handleNull($strDataTrainingID) . ", '$strDataID', '$strID');
            ";
        }
      }
      $resExec = $db->execute($strSQL);
      if ($resExec == false) {
        $bolOK = false;
      }
      // simpan data peserta
      $intParticipant = 0; // jumlah participant
      // simpan data employee participant
      // hapus dulu aja, biar simple
      $strSQL = "DELETE FROM hrd_training_sharing_participant WHERE id_training_sharing = '$strDataID'; ";
      $intParticipant = (isset($_REQUEST['numShow'])) ? $_REQUEST['numShow'] : 0;
      for ($i = 1; $i <= $intParticipant; $i++) {
        if (isset($_REQUEST['detailEmployeeID' . $i])) {
          $strEmpID = $_REQUEST['detailEmployeeID' . $i];
          $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
          //$strStatus = (isset($_REQUEST['detailStatus'.$i])) ? $_REQUEST['detailStatus'.$i] : 0;
          if ($strEmpID != "") {
            // cari IDnya, kalau ada
            $strID = $objEmp->getIDByCode($strEmpID);
            if ($strID != "") {
              // simpan
              $strSQL .= "
                  INSERT INTO hrd_training_sharing_participant 
                    (id_training_request, id_training_sharing, id_employee, note) 
                  VALUES(" . handleNull($strDataTrainingID) . ", '$strDataID', '$strID', '$strNote');
                ";
              $resExec = $db->execute($strSQL);
              if ($resExec == false) {
                $bolOK = false;
              }
            }
          }
        }
      }
      unset($objEmp);
    }
    if ($bolOK) {
      writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "TRAINING SHARING DATA", 0);
      $db->execute("commit");
      $strError = getWords('data_saved');
    } else {
      $db->execute("rollback");
      $strError = getWords('data not saved');
    }
  } else { // ---- data SALAH
    // gunakan data yang diisikan tadi
    $arrData['dataDateFrom'] = $strDateFrom;
    $arrData['dataDateTo'] = $strDateTo;
    $arrData['dataTopic'] = $strTopic;
    $arrData['dataPlace'] = $strPlace;
    $arrData['dataID'] = $strDataID;
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "TRAINING SHARING DATA", 0);
  }
  return $bolOK;
} // saveData
// handle data kosong. jika kosong diganti menjadi NULL, jika tidak, beri ''
function handleNull($str)
{
  if ($str == "") {
    return "NULL";
  } else {
    return "'$str'";
  }
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  $dtNow = getdate();
  $strUserRole = $_SESSION['sessionUserRole'];
  // kalau dept/group head, boleh akses
  $arrData['dataDepartment'] = $arrUserInfo['department_code'];
  $arrData['dataEmployee'] = $arrUserInfo['employee_id']; // default
  if ($arrUserInfo['isGroupHead'] || $arrUserInfo['isDeptHead']) {
    $bolCanView = $bolCanDelete = $bolCanEdit = true;
  }
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  if ($bolCanEdit) {
    if (isset($_POST['btnSave'])) {
      $bolOK = saveData($db, $strError);
      if ($strError != "") {
        //echo "<script>alert(\"$strError\");/script>";
        $strMessages = $strError;
        $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
      }
      //$strDataID = ""; // biar tidak mengambil dta, melainkan pakai data baru atau data yang dikirim (jika error)
    }
  }
  if ($bolCanView) {
    getData($db);
    // $strInputType = getTrainingType($db, $strDataID);
    $strInputTrainer = getTrainingTrainer($db);
    $strInputParticipant = getTrainingParticipant($db, $strDataID);
  } else {
    showError("view_denied");
  }
  //----- TAMPILKAN DATA ---------
  $strDefaultWidthPx = 250;
  $strDefaultWidthPx1 = 100;
  $strReadonly = ""; //($arrData['dataStatus'] == 0 || $_SESSION['sessionUserRole'] == ROLE_ADMIN) ? "" : "readonly"; // kalau dah approve, jadi readonly
  $strReadonlyRequest = "";
  $strReadonlyEmployee = "";
  $strDisabled = "";
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strReadonlyRequest = "readonly";
    $strReadonlyEmployee = "readonly";
    $strDisabled = "disabled";
  }; // employee gak bisa ubah tanggal request
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strDeptKriteria = "WHERE department_code = '" . $arrUserInfo['department_code'] . "' ";
  } else {
    $strDeptKriteria = "";
  }
  $strInputDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $arrData['dataDepartment'],
      "",
      "$strDeptKriteria",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strPlanKriteria = "WHERE year = '" . date("Y") . "' ";
  if ($arrUserInfo['department_code'] != "" && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strPlanKriteria .= "AND department_code = '" . $arrUserInfo['department_code'] . "' ";
  }
  $strTrainingDateFrom = pgDateFormat($arrData['dataTrainingDate'], "d-M-Y");
  $strTrainingDateTo = pgDateFormat($arrData['dataTrainingDateTo'], "d-M-Y");
  $strTrainingTopic = $arrData['dataTrainingTopic'] . "";
  $strTrainingPurpose = nl2br($arrData['dataTrainingPurpose']) . "";
  $strTrainingResult = nl2br($arrData['dataTrainingResult']) . "";
  $strTrainingInstitution = nl2br($arrData['dataTrainingInstitution']) . "";
  $strTrainingTrainer = nl2br($arrData['dataTrainingTrainer']) . "";
  $arrTmp = ["domestic", "foreign"];
  $arrData['dataTrainingPlace'] = (isset($arrTmp[$arrData['dataTrainingPlace']])) ? getWords(
      $arrTmp[$arrData['dataTrainingPlace']]
  ) : "";
  $strTrainingPlace = $arrData['dataTrainingPlace'] . "";
  $strInputDateFrom = "<input type=text size=15 maxlength=10 name='dataDateFrom' id='dataDateFrom' value=\"" . $arrData['dataDateFrom'] . "\" $strReadonlyRequest class='date'>";
  $strInputDateFrom .= " <input name=\"btnDateFrom\" type=button id=\"btnDateFrom\" value='..' $strDisabled>";
  $strInputDateTo = "<input type=text size=15 maxlength=10 name='dataDateTo' id='dataDateTo' value=\"" . $arrData['dataDateTo'] . "\" $strReadonlyRequest class='date'>";
  $strInputDateTo .= " <input name=\"btnDateTo\" type=button id=\"btnDateTo\" value='..' $strDisabled>";
  $strInputPlace = "<input type=text name='dataPlace' id='dataPlace' size=50 maxlength=80 value=\"" . $arrData['dataPlace'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly>";
  $strInputTopic = "<textarea name='dataTopic' cols=30 rows=3 wrap='virtual' style=\"width:$strDefaultWidthPx\" $strReadonly>" . $arrData['dataTopic'] . "</textarea>";
  // tampilan data permohonan kas
  $strReadonly = "readonly";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>