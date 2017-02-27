<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('cls_employee.php');
include_once('../global/cls_date.php');
$dataPrivilege = getDataPrivileges(
    "training_request_list.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$bolPrint = (isset($_REQUEST['btnPrint']));
($bolPrint) ? $strMainTemplate = getTemplate("training_evaluation_manager_print.html") : $strTemplateFile = getTemplate(
    "training_evaluation_manager.html"
);
//---- INISIALISASI ----------------------------------------------------
$strWordTopic = getWords("training topic");
$strWordTrainingDate = getWords("training date");
$strWordTrainer = getWords("trainer");
$strWordManager = getWords("supervisor");
$strWordInstitution = getWords("institution");
$strWordEmployee = getWords("employee") . " / " . getWords("participant");
$strWordFromParticipant = getWords("from participants");
$strWordFromEmployee = getWords("from employee");
$strWordFromManager = getWords("from manager");
$strWordTrainingEvaluation = strtoupper(getWords("training evaluation"));
$strDataDetail = "";
$intDefaultWidth = 30;
$intDefaultHeight = 3;
$strNow = date("Y-m-d");
$strMessages = "";
$strMsgClass = "";
$bolError = false;
$strInputEmployee = "";
$strInputDate = "";
$strInputTopic = "";
$strInputInstitution = "";
$strInputTrainer = "";
$strInputInstructor = "";
$strDataTotalQuestion = 0;
$strDataTotalInstructor = 0;
$strBtnPrint = "";
$strButtons = "";
$arrData = [
    "dataEmployee"      => "",
    "dataTopic"         => "",
    "dataInstitution"   => "",
    "dataTrainer"       => "",
    "dataManager"       => "",
    "dataEmployee"      => "",
    "dataDate"          => "",
    "dataDetail"        => "",
    "dataTotalQuestion" => "",
    "dataFeedback"      => "",
];
$objDt = new clsCommonDate();
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db, $strRequestID, $strEmployeeID, $strManagerID = "", $bolReadonly = true)
{
  global $words;
  global $arrData;
  global $objEmp;
  global $objDt;
  if ($strRequestID === "") {
    return false;
  }
  // AMBIL DATA MASTER TRAINING REQUEST
  $strSQL = "
      SELECT t1.*, t2.topic AS topic_name, t3.name_vendor
      FROM hrd_training_request AS t1 
      LEFT JOIN hrd_training_topic AS t2 ON t1.id_topic = t2.id
      LEFT JOIN hrd_training_vendor AS t3 ON t1.id_institution = t3.id
      WHERE t1.id = '$strRequestID'
    ";
  $res = $db->execute($strSQL);
  if ($row = $db->fetchrow($res)) {
    $arrData['dataTrainer'] = $row['trainer'];
    $arrData['dataInstitution'] = $row['name_vendor'];
    $arrData['dataTopic'] = $row['topic_name'];
    $arrData['dataDate'] = $objDt->getDateFormat($row['training_date'], "d M Y");
    if ($row['training_date'] != $row['training_date_thru']) {
      $arrData['dataDate'] .= " " . getWords("until") . " " . $objDt->getDateFormat(
              $row['training_date_thru'],
              "d M Y"
          );
    }
  }
  // AMBIL DATA ATASAN
  if (!isset($_REQUEST['btnPrint'])) {
    $arrData['dataManager'] = "<select name='dataManagerID' id='dataManagerID' >";
    $strTmp = ($strManagerID != "") ? " OR id = '$strManagerID' " : "";
    $strActiveCriteria = " AND ((active = 1) OR (active = 0 AND resign_date > CURRENT_DATE) $strTmp )";
    $strSQL = "
        SELECT employee_id, employee_name, id
        FROM hrd_employee 
        WHERE 1=1  $strActiveCriteria AND position_code IN (
          SELECT position_code FROM hrd_position
          WHERE level_no IN ('1', '2', '3', '4')
        )
        ORDER BY employee_name
      ";
    $res = $db->execute($strSQL);
    while ($row = $db->fetchrow($res)) {
      $sel = ($strManagerID == $row['id']) ? "selected " : "";
      $arrData['dataManager'] .= " <option value='" . $row['id'] . "' $sel>" . $row['employee_id'] . " - " . $row['employee_name'] . "</option> ";
    }
    $arrData['dataManager'] .= "</select> ";
  } else {
    //jika ditekan tombol print maka akan menampilkan data final saja
    $strTmp = ($strManagerID != "") ? " id = '$strManagerID' " : "";
    $strSQL = "
        SELECT employee_id, employee_name, id
        FROM hrd_employee 
        Where $strTmp
      ";
    $res = $db->execute($strSQL);
    $namaManager = "";
    while ($row = $db->fetchrow($res)) {
      $namaManager = $row['employee_name'];
    }
    $arrData['dataManager'] .= $namaManager;
  }
  //end of data manager
  // AMBIL DATA PARTISIPAN
  if (!isset($_REQUEST['btnPrint'])) {
    $arrData['dataEmployee'] = "<select name='dataEmployeeID' id='dataEmployeeID' >";
    $strSQL = "
        SELECT t1.*, t2.employee_name, t2.employee_id 
        FROM (
          SELECT * FROM hrd_training_request_participant 
          WHERE id_request = '$strRequestID'
            AND status <> '1'
        ) AS t1
        INNER JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
        ORDER BY t2.employee_name
      ";
    $res = $db->execute($strSQL);
    while ($row = $db->fetchrow($res)) {
      $sel = ($strEmployeeID == $row['id_employee']) ? "selected " : "";
      $arrData['dataEmployee'] .= " <option value='" . $row['id_employee'] . "' $sel>" . $row['employee_id'] . " - " . $row['employee_name'] . "</option> ";
    }
    $arrData['dataEmployee'] .= "</select> ";
    $arrData['dataEmployee'] .= "<input type='submit' name='btnGetInfo' id='btnGetInfo' value=\"" . getWords(
            "show evaluation"
        ) . "\"> "; // untuk refresh data setelah memilih karyawan
  } else {
    $strSQL = "
        SELECT t1.*, t2.employee_name, t2.employee_id 
        FROM (
          SELECT * FROM hrd_training_request_participant 
          WHERE id_employee = '$strEmployeeID'
            AND status <> '1'
        ) AS t1
        INNER JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
        ORDER BY t2.employee_name
      ";
    $res = $db->execute($strSQL);
    while ($row = $db->fetchrow($res)) {
      $employeeName = $row['employee_name'];
    }
    $arrData['dataEmployee'] .= $employeeName;
  }
  // end of data participant
  // AMBIL DATA DETAIL EVALUASI
  // ambil daftar point untuk penilaian terhadap training olehj employee (type = 0)
  $arrPoint = [];
  $strSQL = "SELECT id, note, weight FROM hrd_training_evaluation_point WHERE type = '0' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrPoint[] = $rowDb;
  }
  // cari apakah sudah ada / sudah pernah mengisi evaluasi
  $intRows = 0;
  $arrEval = [];
  if ($strEmployeeID != "" && $strManagerID != "") {
    $strSQL = "
          SELECT * FROM hrd_training_evaluation as te 
          WHERE evaluation_type = '2'
            AND id_request = '$strRequestID' 
            AND id_employee = '$strEmployeeID' 
            AND id_manager = '$strManagerID' 
      ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrEval[$rowDb['id_question']] = $rowDb;
    }
  }
  $arrCategory = [];
  $arrInfo = [];
  $strSQL = "
      SELECT * FROM hrd_training_evaluation_question WHERE question_type = '2' 
      ORDER BY category, question
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrCategory[$rowDb['category']] = strtoupper($rowDb['category']);
    $arrInfo[$rowDb['category']][] = $rowDb;
  }
  $intCols = 2;
  $intRows = 0;
  // tampilkan jika id employee dipilih
  if ($strEmployeeID != "" && $strManagerID != "") {
    $arrData['dataDetail'] .= "<table border=0 cellspacing=0 cellpadding=1 class='gridTable' width=100%>\n";
    foreach ($arrInfo AS $strCategory => $data) {
      $strCategoryName = (isset($arrCategory[$strCategory])) ? $arrCategory[$strCategory] : $strCategory;
      $arrData['dataDetail'] .= "
          <tr>
            <td colspan='$intCols' style='font-weight:bold;color:white;background-color:darkgray'>$strCategoryName&nbsp;</td>
          </tr>";
      $int1 = 0; // urutan pertanyaan umum
      foreach ($data As $i => $rowDb) {
        $intRows++;
        $int1++;
        $intPoint = (isset($arrEval[$rowDb['id']]['point'])) ? $arrEval[$rowDb['id']]['point'] : "";
        $strAnswer = getPointList($arrPoint, "detailAnswer$intRows", $intPoint);
        $arrData['dataDetail'] .= "<tr>\n";
        $arrData['dataDetail'] .= " <td nowrap>&nbsp;" . $rowDb['question'];
        $arrData['dataDetail'] .= "<input type=hidden name=detailQuestion$intRows value=\"" . $rowDb['id'] . "\">&nbsp;</td>\n";
        $arrData['dataDetail'] .= " <td>" . $strAnswer . "</td>\n";
        $arrData['dataDetail'] .= "</tr>\n";
      }
    }
    $GLOBALS['strBtnPrint'] .= "<button type=submit name=btnPrint onClick=\"document.formInput.target='_blank'\">" . getWords(
            "print"
        ) . "</button>";
    if (!isset($_REQUEST['btnPrint'])) {
      $intRows++;
      $strFeedback = (isset($arrEval['-1']['answer'])) ? $arrEval['-1']['answer'] : "";
      $arrData['dataDetail'] .= "
          <tr>
            <td colspan='$intCols' style='font-weight:bold;color:white;background-color:darkgray'>" . strtoupper(
              getWords("feedback")
          ) . "&nbsp;</td>
          </tr>
          <tr>
            <td nowrap colspan=2>
              <textarea id='detailAnswer$intRows' name='detailAnswer$intRows' style=\"width:100%\">$strFeedback</textarea>
              &nbsp;<input type=hidden name=detailQuestion$intRows id=detailQuestion$intRows value=\"-1\">&nbsp;
            </td>
          </tr>
        ";
      $arrData['dataDetail'] .= "</table>";
    } else {
      $strFeedback = (isset($arrEval['-1']['answer'])) ? $arrEval['-1']['answer'] : "";
      $arrData['dataDetail'] .= "
          <tr>
            <td colspan='$intCols' style='font-weight:bold;color:white;background-color:darkgray'>" . strtoupper(
              getWords("feedback")
          ) . "&nbsp;</td>
          </tr>
          <tr>
            <td nowrap colspan=2>
             $strFeedback
            </td>
          </tr>
        ";
      $arrData['dataDetail'] .= "</table>";
    }
  }
  $arrData['dataTotalQuestion'] = $intRows;
  return true;
} // showData
// menampilkan pilihan tombol
function getPointList($arr, $strName, $strDefault = "")
{
  if (!isset($_REQUEST['btnPrint'])) {
    $strResult = "<select name='$strName' id='$strName' style=\"width:100px\">\n";
    $bolSelect = false;
    $strResult .= "  <option value='0' > </option>\n";
    foreach ($arr AS $id => $arrValue) {
      if ($strDefault == $arrValue['weight']) {
        $strSelected = "selected";
        $bolSelect = true;
      } else {
        $strSelected = "";
      }
      $strResult .= "  <option value='" . $arrValue['weight'] . "' $strSelected>" . $arrValue['weight'] . " - " . $arrValue['note'] . "</option>\n";
    }
    // jika ada default tapi belum dipilih, tambahkan
    // if ($strDefault != "" && !$bolSelect)
    //  $strResult .= "  <option value='$strDefault' selected>$strDefault</option>\n";
    $strResult .= "</select>\n";
  } else {
    $strResult = "";
    foreach ($arr AS $id => $arrValue) {
      if ($strDefault == $arrValue['weight']) {
        $strResult .= $arrValue['note'];
      }
    }
  }
  return $strResult;
}

// fungsi untuk menyimpan data absen
function saveData($db, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $strRequestID, $strEmployeeID, $strManagerID;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strError = "";
  if ($strRequestID === "" || $strEmployeeID === "") {
    return false;
  }
  // ambil daftar point untuk penilaian terhadap training oleh employee (type = 0)
  $arrPoint = [];
  /*
  $strSQL  = "SELECT id, note, weight FROM hrd_training_evaluation_point WHERE type = 0";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb))
  {
    $arrPoint[$rowDb['note']] = $rowDb['weight'];
  }
  */
  // simpan data -----------------------
  $intQuestion = (isset($_REQUEST['totalQuestion'])) ? $_REQUEST['totalQuestion'] : "0";
  // hapus dulu data jawaban yang lama
  $strSQL = "
        DELETE FROM hrd_training_evaluation 
        WHERE evaluation_type = '2'
          AND id_employee = '$strEmployeeID' 
          AND id_manager = '$strManagerID' 
          AND id_request = '$strRequestID' 
    ";
  $resExec = $db->execute($strSQL);
  $fltPoint = 0;
  for ($i = 1; $i <= $intQuestion; $i++) {
    $strQ = (isset($_REQUEST['detailQuestion' . $i])) ? $_REQUEST['detailQuestion' . $i] : "";
    if ($strQ == '-1') // untuk komentar
    {
      $strA = (isset($_REQUEST['detailAnswer' . $i])) ? $_REQUEST['detailAnswer' . $i] : "";
      $strP = 0;
    } else {
      $strP = (isset($_REQUEST['detailAnswer' . $i])) ? $_REQUEST['detailAnswer' . $i] : 0;
      $strA = "";
      $fltPoint += $strP;
    }
    $strSQL = "
        INSERT INTO hrd_training_evaluation (created, modified_by, created_by, 
          id_request, id_employee, id_manager, id_question, answer, point, evaluation_type) 
        VALUES(now(), $strmodified_byID, $strmodified_byID, '$strRequestID', 
          '$strEmployeeID', '$strManagerID', '$strQ', '$strA', '$strP', '2'); 
      ";
    $resExec = $db->execute($strSQL);
  }
  if ($intQuestion > 0) {
    $fltPoint = ($fltPoint / $intQuestion);
  }
  /* feedback dipindah ke bagian detail
  $strFeedback = (isset($_REQUEST['dataFeedback'])) ? $_REQUEST['dataFeedback'] : "";
  // update data feedback
  $strSQL  = "UPDATE hrd_training_request_participant SET feedback = '$strFeedback', ";
  $strSQL .= "evaluation = '$fltPoint' ";
  $strSQL .= "WHERE id_employee = '$strEmployeeID' AND id_request = '$strRequestID' ";
  $resExec = $db->execute($strSQL);
  */
  writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "EVALUASI TRAINING DATA 0", 0);
  $strError = getWords('data_saved');
  return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  $bolReadonly = true;
  $objEmp = new clsEmployee($db);
  $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : ""; // kayaknya gak perlu ini
  $strRequestID = (isset($_REQUEST['dataRequestID'])) ? $_REQUEST['dataRequestID'] : "";
  $strEmployeeID = (isset($_REQUEST['dataEmployeeID'])) ? $_REQUEST['dataEmployeeID'] : "";
  $strManagerID = (isset($_REQUEST['dataManagerID'])) ? $_REQUEST['dataManagerID'] : "";
  if ($bolCanEdit) {
    if (isset($_POST['btnSave'])) {
      $bolOK = saveData($db, $strError);
      if ($strError != "") {
        //echo "<script>alert(\"$strError\");</script>";
        $strMessages = $strError;
        $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
      }
    }
  }
  // cek apakah ada atau tidak datanya
  if ($strRequestID == "") {
    //redirectPage("training_list_employee.php");
    echo "data not found $strRequestID <a href='training_request_list.php'>[BACK]</a> ";
    exit();
  }
  if ($bolCanView) {
    /*
    if ($_SESSION['sessionUserRole'] == ROLE_ADMIN || $_SESSION['sessionUserRole'] == ROLE_MANAGER) $bolReadonly = false;
    if ($bolPrint) $bolReadonly = true;
    */
    getData($db, $strRequestID, $strEmployeeID, $strManagerID, $bolReadonly);
    $strButtons .= "<button type='submit' name='btnSave' onClick=\"document.formInput.target = ''\">" . getWords(
            "save"
        ) . "</button> ";
    $strButtons .= $strBtnPrint;
  } else {
    showError("view_denied");
  }
  //----- TAMPILKAN DATA ---------
  $strDefaultWidthPx = 250;
  $strDefaultWidthPx1 = 100;
  $strReadonly = ""; //($arrData['dataStatus'] == 0) ? "" : "readonly"; // kalau dah approve, jadi readonly
  $strInputDate = $arrData['dataDate'] . "";
  $strInputTopic = $arrData['dataTopic'] . "";
  $strInputTrainer = $arrData['dataTrainer'] . "";
  $strInputManager = $arrData['dataManager'] . "";
  $strInputInstitution = $arrData['dataInstitution'] . "";
  $strInputEmployee = $arrData['dataEmployee'] . "";
  $strDataFeedback = (isset($arrData['dataFeedback'])) ? $arrData['dataFeedback'] : "";
  $strDataDetail = $arrData['dataDetail'];
  $strDataQuestion = $arrData['dataDetail'];
  $strDataTotalQuestion = $arrData['dataTotalQuestion'];
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords("training evaluation"); //getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
