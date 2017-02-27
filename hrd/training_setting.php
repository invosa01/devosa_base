<?php
include_once('../global/session.php');
//  include_once('../global.php');
include_once('global.php');
include_once('form_object.php');
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
//---- INISIALISASI ----------------------------------------------------
$strWordsTrainingEvaluationQuestion = getWords("training evaluation question");
$strWordsPointForEvaluationAnswer = getWords("point for evaluation answer");
$strWordsTrainingEvaluationQuestion = getWords("questions for training evaluation");
$strWordsTrainingEvaluationPoint = getWords("points for training evaluation");
$strWordsInputTrainingQuestionCategory = getWords("input category question");
$strWordsMore = getWords("more");
$strWordsSave = getWords("save");
$strDataQuestion = "";
$intTotalQuestion = 0;
$intMaxQuestion = 0;
$strDataPoint = "";
$intTotalPoint = 0;
$intMaxPoint = 0;
$strDataInstructor = "";
$intTotalInstructor = 0;
$intMaxInstructor = 0;
$arrData = [
    "question"        => "",
    "instructor"      => "",
    "point"           => "",
    "totalQuestion"   => "0",
    "totalInstructor" => "0",
    "totalPoint"      => "0",
    "maxQuestion"     => "0",
    "maxInstructor"   => "0",
    "maxPoint"        => "0",
];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database
// return berubah $arrOvertime (parameter)
function getData($db)
{
  global $arrData;
  $intAdd = 10;
  // INFORMASI POINT
  $strTmpText = " <tr id=\"[#TRNAME]\" [#STYLE]>\n";
  $strTmpText .= "  <td align=right>[#ID]&nbsp;</td>\n";
  $strTmpText .= "  <td nowrap><input type='text' name='[#NAME]' id='[#NAME]' value=\"[#VALUE]\" size=40><input type='text' name='[#NAME1]' id='[#NAME1]' value=\"[#VALUE1]\" size=15 class=numeric>[#TYPE]</td>\n";
  $strTmpText .= "  <td>&nbsp;</td>\n";
  $strTmpText .= " </tr>\n";
  $arrKey = ["[#TRNAME]", "[#STYLE]", "[#ID]", "[#NAME]", "[#VALUE]", "[#NAME1]", "[#VALUE1]", "[#NAME2]", "[#TYPE]"];
  $arrData['point'] .= " <tr class=tableHeader>\n";
  $arrData['point'] .= "  <td>&nbsp;</td>\n";
  $arrData['point'] .= "  <td nowrap><input type='text' name='[#NAME]' id='[#NAME]' value=\"" . strtoupper(
          getWords("answer")
      ) . "\" size=40 disabled style=\"color:black\" >";
  $arrData['point'] .= "<input type='text' name='[#NAME1]' id='[#NAME1]' value=\"" . strtoupper(
          getWords("weight")
      ) . "\" size=15 disabled style=\"color:black\" ><input type='text' name='[#NAME2]' id='[#NAME2]' value=\"" . strtoupper(
          getWords("question type")
      ) . "\" size=20 disabled style=\"color:black\" ></td>\n";
  $arrData['point'] .= "  <td>&nbsp;</td>\n";
  $arrData['point'] .= " </tr>\n";
  // ambil data point untuk jawaban pertanyaan
  $int1 = 0;
  $strSQL = "SELECT * FROM hrd_training_evaluation_point";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $int1++;
    $strTRName = "detailDataPoint$int1";
    $strID = "$int1.<input type=hidden name='detailPointID$int1' value='" . $rowDb['id'] . "'>";
    $strName = "detailPoint$int1";
    $strName1 = "detailPointWeight$int1";
    $strName2 = "detailPointType$int1";
    $strValue = "" . $rowDb['note'];
    $strValue1 = "" . $rowDb['weight'];
    $strType = getQuestionTypeList($db, $strName2, $rowDb['type']);
    $arrValue = [$strTRName, "", $strID, $strName, $strValue, $strName1, $strValue1, $strName2, $strType];
    $arrData['point'] .= str_replace($arrKey, $arrValue, $strTmpText);
  }
  // jika, 0, tambahkan 1
  if ($int1 == 0) {
    $int1++;
    $strID = "$int1.";
    $strTRName = "detailDataPoint$int1";
    $strName = "detailPoint$int1";
    $strValue = "";
    $strName1 = "detailPointWeight$int1";
    $strValue1 = "0";
    $strName2 = "detailPointType$int1";
    $strType = getQuestionTypeList($db, $strName2, $rowDb['type']);
    $arrValue = [$strTRName, "", $strID, $strName, $strValue, $strName1, $strValue1, $strName2, $strType];
    $arrData['point'] .= str_replace($arrKey, $arrValue, $strTmpText);
  }
  $arrData['totalPoint'] = $int1;
  // tambahkan field kosong -- hidden
  for ($i = 0; $i < $intAdd; $i++) {
    $int1++;
    $strID = "$int1.";
    $strTRName = "detailDataPoint$int1";
    $strName = "detailPoint$int1";
    $strValue = "";
    $strName1 = "detailPointWeight$int1";
    $strValue1 = "0";
    $strName2 = "detailPointType$int1";
    $strType = getQuestionTypeList($db, $strName2, $rowDb['type']);
    $arrValue = [
        $strTRName,
        "style=\"display:none\"",
        $strID,
        $strName,
        $strValue,
        $strName1,
        $strValue1,
        $strName2,
        $strType
    ];
    $arrData['point'] .= str_replace($arrKey, $arrValue, $strTmpText);
  }
  $arrData['maxPoint'] = $int1;
  writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  return true;
} // showData
// fungsi untuk mengambil data question type
function getDataQuestion($db, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $bolPrint;
  global $ARRAY_LEADER_LIST;
  $intRows = 0;
  $strResult = "";
  $strSQL = "SELECT * FROM \"hrd_training_question_type\"";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strResult .= getDataRows($rowDb, $intRows, $db);
  }
  $strTotalData = $intRows;
  writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  return $strResult;
}//showDataQuestion
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
  global $_POST;
  global $_SESSION;
  $intIDmodified_by = $_SESSION['sessionUserID'];
  // simpan point
  $intTotalPoint = (isset($_POST['totalPoint'])) ? $_POST['totalPoint'] : 0;
  for ($i = 1; $i <= $intTotalPoint; $i++) {
    $strID = (isset($_POST['detailPointID' . $i])) ? $_POST['detailPointID' . $i] : "";
    $strTxt = (isset($_POST['detailPoint' . $i])) ? $_POST['detailPoint' . $i] : "";
    $strWeight = (isset($_POST['detailPointWeight' . $i])) ? $_POST['detailPointWeight' . $i] : "0";
    $strType = (isset($_POST['detailPointType' . $i])) ? $_POST['detailPointType' . $i] : "0";
    if (!is_numeric($strWeight)) {
      $strWeight = 0;
    }
    if ($strTxt != "") {
      if ($strID === "") {
        // insert into
        $strSQL = "INSERT INTO hrd_training_evaluation_point (created, modified_by, created_by, note, weight, type) ";
        $strSQL .= "VALUES (now(), '$intIDmodified_by', '$intIDmodified_by', '$strTxt', '$strWeight', '$strType')";
        $resExec = $db->execute($strSQL);
      } else {
        // update data
        $strSQL = "UPDATE hrd_training_evaluation_point SET modified_by = '$intIDmodified_by', ";
        $strSQL .= "note = '$strTxt', weight = '$strWeight', type = '$strType' WHERE id = '$strID' ";
        $resExec = $db->execute($strSQL);
      }
    } else if ($strID != "") { // hapus data
      $strSQL = "DELETE FROM hrd_training_evaluation_point WHERE id = '$strID' ";
      $resExec = $db->execute($strSQL);
    }
  }
  $strError = getWords("data_saved");
  writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "", 0);
  return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  if ($bolCanView) {
    if ($bolCanEdit) {
      if (isset($_POST['btnSave'])) {
        saveData($db, $strError);
        if ($strError != "") {
          echo "<script>alert(\"$strError\")</script>";
        }
      }
    }
    getData($db);
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
}
// tampilkan data
$strDataQuestion = "" . $arrData['question'];
$intTotalQuestion = "" . $arrData['totalQuestion'];
$intMaxQuestion = "" . $arrData['maxQuestion'];
$strDataPoint = "" . $arrData['point'];
$intTotalPoint = "" . $arrData['totalPoint'];
$intMaxPoint = "" . $arrData['maxPoint'];
$strDataInstructor = "" . $arrData['instructor'];
$intTotalInstructor = "" . $arrData['totalInstructor'];
$intMaxInstructor = "" . $arrData['maxInstructor'];
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
