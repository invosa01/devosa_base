<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
$dataPrivilege = getDataPrivileges(
    "training_request_edit.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$bolPrint = (isset($_REQUEST['btnPrint']));
($bolPrint) ? $strMainTemplate = getTemplate("training_evaluation_edit_print.html") : $strTemplateFile = getTemplate(
    "training_evaluation_edit.html"
);
//---- INISIALISASI ----------------------------------------------------
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
$strWordTrainingEvaluation = getWords("training evaluation");
$strWordTopic = getWords("topic");
$strWordTrainingDate = getWords("training date");
$strWordTrainer = getWords("trainer");
$strWordInstitution = getWords("institution");
$strWordEmployee = getWords("employee");
$arrData = [
    "dataEmployee"      => "",
    "dataTopic"         => "",
    "dataInstitution"   => "",
    "dataTrainer"       => "",
    "dataDate"          => "",
    "dataDetail"        => "",
    "dataTotalQuestion" => "",
    "dataFeedback"      => "",
];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db, $strDataID, $strRequestID, $strEmployeeID, $bolReadonly = true)
{
  global $words;
  global $arrData;
  if ($strDataID === "" || $strEmployeeID === "" || $strRequestID === "") {
    return false;
  }
  $arrData['dataDetail'] .= "<table border=0 cellspacing=0 cellpadding=1 class='gridTable' width=100%>\n";
  // ambil daftar point untuk penilaian terhadap training olehj employee
  $arrPoint = [];
  $strSQL = "SELECT id, note, weight, \"type\" FROM hrd_training_evaluation_point";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrPoint[] = $rowDb;
  }
  // cari apakah sudah ada / sudah pernah mengisi evaluasi
  $intRows = 0;
  $int1 = 0; // urutan pertanyaan umum
  $strSQL = "SELECT * FROM hrd_training_evaluation ";
  $strSQL .= "WHERE id_request = '$strRequestID' ";
  $strSQL .= "AND id_employee = '$strEmployeeID' ";
  //	  echo $strSQL; die();
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $int1++;
    $strAnswer = ($bolReadonly) ? $rowDb['answer'] . "&nbsp;" : getPointList(
        $arrPoint,
        "detailAnswer$int1",
        $rowDb['answer'],
        $rowDb['question_type']
    );
    $arrData['dataDetail'] .= "<tr>\n";
    $arrData['dataDetail'] .= " <td nowrap>&nbsp;$int1. " . $rowDb['question'];
    $arrData['dataDetail'] .= "<input type=hidden value=\"" . $rowDb['id'] . "\" name=detailAnswerID$int1>";
    $arrData['dataDetail'] .= "<input type=hidden name=detailQuestion$int1 value=\"" . $rowDb['question'] . "\">&nbsp;</td>\n";
    $arrData['dataDetail'] .= " <td>" . $strAnswer . "<input type=hidden name=detailQuestionType$int1 value=\"" . $rowDb['question_type'] . "\"></td>\n";
    // $arrData['dataDetail'] .= " <td>" .$strAnswer."</td>\n";
    $arrData['dataDetail'] .= "</tr>\n";
  }
  // jika data belum ada, ambil pertanyaan dari setting untuk evaluasi terhadap training oleh employee
  if ($intRows == 0) {
    $strSQL = "SELECT * FROM hrd_training_evaluation_question";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $intRows++;
      $int1++;
      $strAnswer = ($bolReadonly) ? "&nbsp;" : getPointList(
          $arrPoint,
          "detailAnswer$int1",
          null,
          $rowDb['question_type']
      );
      $arrData['dataDetail'] .= "<tr>\n";
      $arrData['dataDetail'] .= " <td nowrap>&nbsp;$int1. " . $rowDb['question'];
      $arrData['dataDetail'] .= "<input type=hidden name=detailQuestion$int1 value=\"" . $rowDb['question'] . "\">&nbsp;</td>\n";
      $arrData['dataDetail'] .= " <td>" . $strAnswer . "<input type=hidden name=detailQuestionType$int1 value=\"" . $rowDb['question_type'] . "\"></td>\n";
      $arrData['dataDetail'] .= "</tr>\n";
    }
  } else {
    $GLOBALS['strBtnPrint'] .= "<button type=submit name=btnPrint onClick=\"document.formInput.target='_blank'\">" . getWords(
            "print"
        ) . "</button>";
  }
  $arrData['dataTotalQuestion'] = $int1;
  $arrData['dataDetail'] .= "</table>";
  // ambil data feedback
  $strSQL = "SELECT feedback FROM hrd_training_request_participant ";
  $strSQL .= "WHERE id_request = '$strRequestID' ";
  $strSQL .= "AND id_employee = '$strEmployeeID' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $arrData['dataFeedback'] = $rowDb['feedback'];
  }
  return true;
} // showData
// menampilkan pilihan tombol
function getPointList($arr, $strName, $strDefault = "", $intQT)
{
  $strResult = "<select name='$strName' style=\"width:$strDateWidthpx\">\n";
  $bolSelect = false;
  foreach ($arr AS $id => $arrValue) {
    if ($strDefault == $arrValue['note']) {
      $strSelected = "selected";
      $bolSelect = true;
    } else {
      $strSelected = "";
    }
    if ($intQT == $arrValue['type']) {
      $strResult .= "<option value='" . $arrValue['note'] . "' $strSelected>" . $arrValue['note'] . "</option>\n";
    }
  }
  // jika ada default tapi belum dipilih, tambahkan
  if ($strDefault != "" && !$bolSelect) {
    $strResult .= "  <option value='$strDefault'>$strDefault</option>\n";
  }
  $strResult .= "</select>\n";
  // echo $strResult;
  return $strResult;
}

// fungsi untuk menyimpan data absen
function saveData($db, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $strDataID;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strError = "";
  $strRequestID = (isset($_REQUEST['dataRequestID'])) ? $_REQUEST['dataRequestID'] : "";
  $strEmployeeID = (isset($_REQUEST['dataEmployeeID'])) ? $_REQUEST['dataEmployeeID'] : "";
  if ($strRequestID === "" || $strEmployeeID === "") {
    return false;
  }
  // ambil daftar point untuk penilaian terhadap training oleh employee (type = 0)
  $arrPoint = [];
  $strSQL = "SELECT id, note, weight, type FROM hrd_training_evaluation_point";
  $resDb = $db->execute($strSQL);
  //	  echo $strSQL;
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrPoint[$rowDb['note']] = $rowDb;
  }
  // simpan data -----------------------
  $intQuestion = (isset($_REQUEST['totalQuestion'])) ? $_REQUEST['totalQuestion'] : "0";
  //die();
  // hapus dulu data jawaban yang lama
  $strSQL = "DELETE FROM hrd_training_evaluation ";
  $strSQL .= "WHERE id_employee = '$strEmployeeID' AND id_request = '$strRequestID' ";
  $resExec = $db->execute($strSQL);
  $fltPoint = 0;
  for ($i = 1; $i <= $intQuestion; $i++) {
    $strID = (isset($_REQUEST['detailQuestionID' . $i])) ? $_REQUEST['detailQuestionID' . $i] : "";
    $strQ = (isset($_REQUEST['detailQuestion' . $i])) ? $_REQUEST['detailQuestion' . $i] : "";
    $strA = (isset($_REQUEST['detailAnswer' . $i])) ? $_REQUEST['detailAnswer' . $i] : "";
    $strP = (isset($arrPoint[$strA]['weight'])) ? $arrPoint[$strA]['weight'] : 0;
    $strQT = (isset($_REQUEST['detailQuestionType' . $i])) ? $_REQUEST['detailQuestionType' . $i] : "";
    //      print_r($strP);die();
    $strSQL = "INSERT INTO hrd_training_evaluation (created, modified_by, created_by, ";
    $strSQL .= "id_request, id_employee, question, answer, point, question_type) ";
    $strSQL .= "VALUES(now(), $strmodified_byID, $strmodified_byID, '$strRequestID', ";
    $strSQL .= "'$strEmployeeID', '$strQ', '$strA', '$strP', '$strQT') ";
    $resExec = $db->execute($strSQL);
    //		echo $strSQL;die();
    $fltPoint += $strP;
  }
  if ($intQuestion > 0) {
    $fltPoint = ($fltPoint / $intQuestion);
  }
  $strFeedback = (isset($_REQUEST['dataFeedback'])) ? $_REQUEST['dataFeedback'] : "";
  // update data feedback
  $strSQL = "UPDATE hrd_training_request_participant SET feedback = '$strFeedback', ";
  $strSQL .= "evaluation = '$fltPoint' ";
  $strSQL .= "WHERE id = '$strDataID' ";
  $resExec = $db->execute($strSQL);
  writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "TRAINING DATA", 0);
  $strError = getWords('data_saved');
  return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
  if ($bolCanEdit) {
    if (isset($_POST['btnSave'])) {
      $bolOK = saveData($db, $strError);
      if ($strError != "") {
        //echo "<script>alert(\"$strError\");</script>";
        $strMessages = $strError;
        $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
      }
      //$strDataID = ""; // biar tidak mengambil dta, melainkan pakai data baru atau data yang dikirim (jika error)
    }
  }
  // cek apakah ada atau tidak datanya
  $strEmployeeID = "";
  $strRequestID = "";
  if ($strDataID == "") {
  } else {
    // cari info participant, untuk mendapatkan id employee dan id requestnya
    $strSQL = "SELECT t1.id_employee AS participant, t1.id_request,t2.*,name_instructor,name_vendor,t4.*, t3.employee_id, t3.employee_name FROM hrd_training_request_participant AS t1 LEFT JOIN hrd_training_request AS t2 ON t1.id_request = t2.id LEFT JOIN hrd_employee AS t3 ON t1.id_employee = t3.id LEFT JOIN hrd_training_plan as t4 ON t2.id_plan = t4.id LEFT JOIN hrd_training_instructor as t5 ON t4.id_instructor = t5.id LEFT JOIN hrd_training_vendor as t6 ON t6.id = t4.id_training_vendor WHERE t1.id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strEmployeeID = $rowDb['participant'];
      $strRequestID = $rowDb['id_request'];
      $arrData['dataEmployee'] = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
      $arrData['dataDate'] = pgDateFormat($rowDb['training_date'], "d-M-y") . "  -- " . pgDateFormat(
              $rowDb['training_date_thru'],
              "d-M-y"
          );
      $arrData['dataIdEmployee'] = $rowDb['id_employee'];
      $arrData['dataTopic'] = $rowDb['topic'];
      $arrData['dataTrainer'] = $rowDb['name_instructor'];
      $arrData['dataInstitution'] = $rowDb['name_vendor'];
      if (isMe($rowDb['participant'])) {
        $bolReadonly = false;
      }
    }
    if ($strEmployeeID === "" || $strRequestID === "") {
      //redirectPage("training_list_employee.php");
      echo "kecebong";
      exit();
    }
  }
  if ($bolCanView) {
    if (($_SESSION['sessionUserRole'] == ROLE_SUPER || $_SESSION['sessionUserRole'] == ROLE_ADMIN) && $bolPrint) {
      $bolReadonly = false;
    }
    getData($db, $strDataID, $strRequestID, $arrData['dataIdEmployee'], $bolReadonly);
    if (!$bolReadonly) {
      $strButtons .= "<button type='submit' name='btnSave' onClick=\"document.formInput.target = ''\">" . getWords(
              "save"
          ) . "</button> ";
    }
    $strButtons .= $strBtnPrint;
  } else {
    showError("view_denied");
  }
  //----- TAMPILKAN DATA ---------
  $strDefaultWidthPx1 = 100;
  $strReadonly = ""; //($arrData['dataStatus'] == 0) ? "" : "readonly"; // kalau dah approve, jadi readonly
  $strInputDate = $arrData['dataDate'];
  $strInputTopic = $arrData['dataTopic'];
  $strInputTrainer = $arrData['dataTrainer'];
  $strInputInstitution = $arrData['dataInstitution'];
  $strInputEmployee = $arrData['dataEmployee'];
  $strInputIdEmployee = "<input type=hidden name=dataEmployeeID value=$arrData[dataIdEmployee]>";
  $strDataFeedback = (isset($arrData['dataFeedback'])) ? $arrData['dataFeedback'] : "";
  $strDataDetail = $arrData['dataDetail'];
  $strDataQuestion = $arrData['dataDetail'];
  $strDataTotalQuestion = $arrData['dataTotalQuestion'];
  //	  print_r($arrData);die();
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