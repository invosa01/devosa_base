<?php
session_start();
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
include_once('cls_employee.php');
include_once('../global/employee_function.php');
// include_once('../global/session.php');
// include_once('global.php');
// include_once('form_object.php');
//include_once(getTemplate("words.inc"));
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
//template
$strTemplateFile = getTemplate("training_plan_edit.html");
//---- INISIALISASI ----------------------------------------------------
$strWordsInputTrainingPlan = getWords("Input Training Plan");
$strWordsTrainingPlanList = getWords("Training Plan List");
//$strWordsInputTrainingRequest = getWords("input training request");
$strWordsDomain = getWords("domain");
$strWordsCategory = getWords("competency");
$strWordsTrainingRequestList = getWords("training request list");
$strWordsApprovedTraining = getWords("approved training");
$strWordsTrainingReport = getWords("training report");
$strWordsDepartment = getWords("department");
$strWordsEmployee = getWords("requested by") . " (" . getWords("employee") . ") ";
$strWordsTrainingPlan = getWords("training plan");
$strWordsTrainingProfile = getWords("training profile");
$strWordsTrainingTopic = getWords("training topic");
$strWordsTrainingCategory = getWords("training category");
//$strWordsTrainingType   	   = getWords("training type");
$strWordsTrainingStatus = getWords("training status");
$strWordsType = getWords("sub domain");
// $strWordHidden = getWords("");
$strWordsCreator = getWords("creator");
$strWordsYear = getWords("years");
$strWordsTrainingType = getWords("training Type");
$strWordsDuration = getWords("Duration");
$strWordsDays = getWords("Days");
$strWordsEstimatedCost = getWords("Estimated Cost");
$strWordsNote = getWords("note");
$strWordsStatus = getWords("Status");
$strWordsDivision = getWords("division");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("subsection");
$strWordsPurpose = getWords("purpose");
$strWordsLocation = getWords("location");
$strWordsAddress = getWords("address");
$strWordsInstitution = getWords("institution");
$strWordsInstructor = getWords("instructor");
$strWordsTrainer = getWords("trainer");
$strWordsInstructor = getWords("instructor");
$strWordsExpectedResult = getWords("expected result");
$strWordsExpectedDate = getWords("expected date");
// $strWordsParticipant= getWords("participant");
$strWordsTimeDetail = getWords("training time");
//
$strWordsParticipant = strtoupper(getWords("participant"));
$strWordsTrainingScope = getWords("Training Scope");
$strWordsRequestStatus = getWords("request status");
$strWordsRequestNumber = getWords("request no.");
$strWordsDate = getWords("request date");
$strWordsCost = getWords("training cost");
$strWordsOtherCost = getWords("other cost");
$strWordsPaidBy = getWords("paid by");
$strWordsSave = getWords("save");
$strWordsClearForm = getWords("clear form");
$strWordsDataID = getWords("Hidden");
$strWordsTrainingSyllabus = getWords("syllabus");
$strFileOption = "";
$strWordsDeleteFile = getWords("delete file");
//
$strInitAction = "";
$strDataDetail = "";
$intDefaultWidth = 30;
$intDefaultHeight = 3;
$strNow = date("Y-m-d");
$strMessages = "";
$strMsgClass = "";
$bolError = false;
$dtNow = getdate();
$arrData = [
    "dataYear"        => $dtNow['year'],
    "dataMonth"       => $dtNow['mon'],
    "dataDepartment"  => "",
    //kamal
    "datadivision"    => "",
    "dataTopic"       => "",
    "dataPurpose"     => "",
    "dataDuration"    => "1",
    "dataCost"        => "0",
    "dataNote"        => "",
    "dataParticipant" => "1",
    "dataDate"        => "",
    "dataType"        => "",
    "dataInstitution" => "",
    "dataCreator"     => "",
    "dataStatus"      => "0",
    "dataID"          => "",
];
$strInputType = "";
//print_r($strInpputType);
$strInputParticipant = "";
$strTargetElements = "";
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data\"\
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db)
{
  global $words;
  global $arrData;
  global $strDataID;
  global $bolCanEdit;
  if (isset($_REQUEST['fileID']) && !isset($_REQUEST['btnSave'])) {
    if ($bolCanEdit) {
      deleteFile($db, $_REQUEST['fileID']);
    } else {
      $strMessages = getWords('delete_denied');
      $strMsgClass = "class=bgError";
    }
  }
  if ($strDataID != "") {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "ID=$strDataID", 0);
    $strSQL = "SELECT * FROM hrd_training_plan AS t1 ";
    $strSQL .= "WHERE id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataYear'] = $rowDb['year'];
      $arrData['dataMonth'] = $rowDb['month'];
      $arrData['dataStatus'] = $rowDb['status'];
      $arrData['dataPurpose'] = $rowDb['purpose'];
      $arrData['dataTopic'] = $rowDb['topic'];
      $arrData['dataCreator'] = $rowDb['id_creator'];
      $arrData['dataDepartment'] = $rowDb['department_code'];
      $arrData['dataDivision'] = $rowDb['division_code'];
      $arrData['dataSection'] = $rowDb['section_code'];
      $arrData['dataSubSection'] = $rowDb['sub_section_code'];
      $arrData['dataDivision'] = $rowDb['division_code'];
      $arrData['dataDuration'] = $rowDb['duration'];
      $arrData['dataParticipant'] = $rowDb['participant'];
      $arrData['dataInstitution'] = $rowDb['id_training_vendor'];
      $arrData['dataInstructor'] = $rowDb['id_instructor'];
      $arrData['dataType'] = $rowDb['training_type'];
      $arrData['dataDate'] = $rowDb['expected_date'];
      $arrData['dataCost'] = $rowDb['cost'];
      $arrData['dataNote'] = $rowDb['note'];
      $arrData['doc'] = $rowDb['doc'];
      $arrData['dataID'] = $strDataID;
      $arrData['competency'] = $rowDb['competency'];
      $arrData['domain'] = $rowDb['domain'];
    }
    //UNTUK MENAMPILKAN DOCUMENT
    global $strFileOption;
    global $strWordsDeleteFile;
    if (empty($arrData['doc'])) {
      $strDataDoc = "";
    } else {
      if (file_exists("trainingdoc/" . $arrData['doc'])) {
        $strDataDoc = "<a href=\"trainingdoc/" . $arrData['doc'] . "\" target=\"_blank\" > <img  src='trainingdoc/" . $arrData['doc'] . "' alt=\"" . $arrData['doc'] . "\"></a>&nbsp;&nbsp;";
      } else {
        $strDataDoc = "";
      }
    }
    $strFileOption = "<td>&nbsp;</td><td>&nbsp;</td><td><span id=\"doc\">&nbsp;" . $strDataDoc . "</span>";
    if ($strDataDoc != "" && $bolCanEdit) {
      $strFileOption .= "<input name=\"btnDeleteDoc\" type=\"button\" id=\"btnDelete\" value=\"$strWordsDeleteFile\" onClick=\"deleteFile($strDataID);\"></td>";
    }
    $strFileOption .= "<input type=hidden id='syllabusDoc' name='syllabusDoc' value=$arrData[doc]>";
    //SEDIKIT DESPERATE, selesai
  }
  return true;
} // showData
// ambil daftar jennis training
function getTrainingType($db, $strDataID = "")
{
  $strResult = "";
  $intTotal = 0;
  // cari dulu apakah ada ID dan data yang sudah adda dalam request berdasar ID
  $arrData = [];
  if ($strDataID) {
    $strSQL = "SELECT * FROM hrd_training_plan_type WHERE id_plan = '$strDataID' ";
    $strSQL .= "ORDER BY \"training_type\" ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrData[$rowDb['type']] = false; // sekedar menandai apakah tipe ini masih terdaftar atau gak
    }
  }
  $strResult .= " <table border=0 cellpadding=1>\n";
  $x = 0;
  // cari daftar tipe training yang ada di mmaster data
  $strSQL = "SELECT * FROM hrd_training_type ORDER BY \"training_type\" ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intTotal++;
    if (isset($arrData[$rowDb['training_type']])) {
      $strCheck = "checked";
      $arrData[$rowDb['training_type']] = true;
    } else {
      $strCheck = "";
    }
    if ($x == 0) { // di kolom kanan
      $strResult .= "  <tr valign=top>\n";
    }
    $strResult .= "   <td nowrap><input type=hidden name=dataType$intTotal value=\"" . $rowDb['training_type'] . "\"> ";
    //aslinya ini//  $strResult .= "<label><input type=checkbox name=dataRequestType$intTotal value=\"" .$rowDb['type']."\" $strCheck>".$rowDb['type']. "&nbsp;</label></td>\n";
    //$strResult .= "<label><input type=radio checked=checked name=dataRequestType value=\"" .$rowDb['type']."\" $intTotal>".$rowDb['type']. "&nbsp;</label></td>\n";
    $strResult .= "<label><input type=radio checked=checked" . $rowDb['training_type'] . " name=dataRequestType value=\"" . $rowDb['training_type'] . "\" $strCheck>" . $rowDb['training_type'] . "&nbsp;</label></td>\n";
    if ($x == 2) { // penutup
      $strResult .= " </tr>\n";
      $x = 0;
    } else {
      $x++;
    }
  }
  // handle jika ada data dari request, yang sudah terhapus dari masternya
  foreach ($arrData AS $strKode => $bolStatus) {
    if (!$bolStatus) {
      $intTotal++;
      if ($x == 0) { // di kolom kanan
        $strResult .= "  <tr valign=top>\n";
      }
      $strResult .= "<td nowrap><input type=hidden name=dataType$intTotal value=\"" . $strKode . "\"> ";
      $strResult .= "<input type=checkbox name=dataRequestType$intTotal value=\"" . $strKode . "\">" . $strKode . "&nbsp;</td>\n";
      if ($x == 2) {
        $strResult .= " </tr>\n";
        $x = 0;
      } else {
        $x++;
      }
    }
  }
  if ($x != 0) {
    while ($x < 3) {
      $strResult .= "  <td>&nbsp;</td>\n";
      $x++;
    }
    $strResult .= "  </tr>\n";
  }
  $strResult .= " </table>\n";
  // tambahkan hidden
  $strResult .= "<input type=hidden name=dataTotalType value=\"$intTotal\">\n";
  return $strResult;
}//getTrainingType
// fungsi mengambil daftar participant yang dimohonkan
function getTrainingParticipant($db, $strDataID = "")
{
  global $words;
  global $strTargetElements;
  $intMaxShow = 4; // tambahan yang perlu dimunculkan
  $intAdd = 10; // maksimum tambahan
  $intRows = 0;
  $intShown = 0;
  $strResult = "";
  $arrStatus = ["ok", "cancel"];
  $strResult .= " <table border=0 class=gridTable cellpadding=0 cellspacing=0 width=100%>\n";
  $strResult .= "  <tr valign=top class=tableHeader>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('no') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('employee id') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('name') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('status') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('note') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('delete') . "</td>\n";
  $strResult .= "  </tr>\n";
  if ($strDataID != "") {
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name FROM hrd_training_plan_participant AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t2.id = t1.id_employee ";
    $strSQL .= "WHERE t1.id_plan = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $intRows++;
      $intShown++;
      $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
      $strResult .= "  <td align=right nowrap><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">$intRows&nbsp;</td>";
      $strAction = "onFocus = \"AC_kode = 'detailemployee_id$intRows';AC_nama='detailName$intRows';\" ";
      //print_r("detailemployee_id");
      //adnan
      //$strAction = "onFocus = \"onParticipantFocus(1,1);\"";
      //$strAction = "onfocus = \"alert('test');\"";
      $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailemployee_id$intRows id=detailemployee_id$intRows value=\"" . $rowDb['employee_id'] . "\" $strAction></td>";
      $strResult .= "  <td nowrap>&nbsp;<strong id=\"detailName$intRows\">" . $rowDb['employee_name'] . "</strong></td>";
      $strResult .= "  <td>" . getComboFromArray($arrStatus, "detailStatus$intRows", $rowDb['status']) . "</td>";
      $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailNote$intRows id=detailNote$intRows value=\"" . $rowDb['note'] . "\"></td>";
      $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
      $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' $strAction></td>\n";
      $strResult .= "</tr>\n";
      if ($strTargetElements != "") {
        $strTargetElements .= ", ";
      }
      $strTargetElements .= "\"detailemployee_id$intRows\"";
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
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
    $strAction = "onFocus = \"AC_kode = 'detailemployee_id$intRows';AC_nama='detailName$intRows';\"";
    //adnan
    // $strAction = "onfocus = \"onParticipantFocus('detailemployee_id$intRows','detailName$intRows');\"";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailemployee_id$intRows id=detailemployee_id$intRows $strDisabled $strAction></td>";
    //$strResult .=  "<td><input type=text size=20 maxlength=50 name=detailemployee_id$intRows id=detailemployee_id$intRows $strDisabled $strAction value=".($strDataEmployee = getInitialValue("Employee", null, $strDataEmployee))." $strEmpReadonly></td>";
    $strResult .= "  <td nowrap>&nbsp;<strong id=\"detailName$intRows\"></strong></td>";
    $strResult .= "  <td>" . getComboFromArray($arrStatus, "detailStatus$intRows") . "</td>";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailNote$intRows id=detailNote$intRows></td>";
    $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' $strAction></td>\n";
    $strResult .= "</tr>\n";
    $strTargetElements .= ",\"detailemployee_id$intRows\"";
  }
  $strResult .= " <tr valign=top><td>&nbsp;</td>\n";
  $strResult .= "  <td colspan=5>&nbsp;<a href=\"javascript:showMoreInput();\">" . $words['more'] . "</a></td></tr>\n";
  $strResult .= "  </table>\n";
  // tambahkan hidden data
  $strResult .= "<input type=hidden name=maxDetail value=$intRows>";
  $strResult .= "<input type=hidden name=numShow value=$intShown>";
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
  global $dtNow;
  global $strDataID;
  global $bolCanEdit;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strError = "";
  $bolOK = true;
  $strSQL = "SELECT id FROM hrd_employee WHERE employee_id='$_REQUEST[dataEmployee]'";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strEmployeeID = $rowDb['id'];
  }
  $strDataYear = (isset($_REQUEST['dataYear'])) ? $_REQUEST['dataYear'] : $dtNow['year'];
  $strDataMonth = (isset($_REQUEST['dataMonth'])) ? $_REQUEST['dataMonth'] : $dtNow['mon'];
  $strDataDate = (isset($_REQUEST['dataDate'])) ? $_REQUEST['dataDate'] : "";
  $strDataDepartment = (isset($_REQUEST['dataDepartment'])) ? trim($_REQUEST['dataDepartment']) : "";
  //kamal
  $strDataDivision = (isset($_REQUEST['dataDivision'])) ? $_REQUEST['dataDivision'] : "";
  $strDataSection = (isset($_REQUEST['dataSection'])) ? $_REQUEST['dataSection'] : "";
  $strDataSubSection = (isset($_REQUEST['dataSubSection'])) ? $_REQUEST['dataSubSection'] : "";
  $strDataDomain = $_REQUEST['dataDomain'];
  $strDataType = $_REQUEST['dataType'];
  $strDataCompetency = $_REQUEST['dataCompetency'];
  $strDataDuration = (isset($_REQUEST['dataDuration'])) ? $_REQUEST['dataDuration'] : 1;
  $strDataTopic = (isset($_REQUEST['dataTopic'])) ? $_REQUEST['dataTopic'] : "";
  $strDataPurpose = (isset($_REQUEST['dataPurpose'])) ? $_REQUEST['dataPurpose'] : "";
  $strDataCost = (isset($_REQUEST['dataCost'])) ? $_REQUEST['dataCost'] : 0;
  $strDataParticipant = (isset($_REQUEST['dataParticipant'])) ? $_REQUEST['dataParticipant'] : 1;
  $strDataInstitution = (isset($_REQUEST['dataInstitution'])) ? $_REQUEST['dataInstitution'] : "";
  $strDataInstructor = (isset($_REQUEST['dataInstructor'])) ? $_REQUEST['dataInstructor'] : "";
  $strDataNote = (isset($_REQUEST['dataNote'])) ? $_REQUEST['dataNote'] : "";
  // cek validasi -----------------------
  if ($strDataDepartment == "") {
    $strError = $error['empty_code'];
    $bolOK = false;
  } else if ($strDataTopic == "") {
    $strError = $error['empty_data'];
    $bolOK = false;
  }
  if (!is_numeric($strDataCost)) {
    $strDataCost = 0;
  }
  if (!is_numeric($strDataParticipant)) {
    $strDataParticipant = 1;
  }
  if (!is_numeric($strDataDuration)) {
    $strDataDuration = 1;
  }
  // simpan data -----------------------
  if ($bolOK) { // input OK, tinggal disimpan
    $strDataDate = ($strDataDate == "") ? "NULL" : "'$strDataDate'";
    if ($strDataID == "") {
      // data baru
      $strSQL = "INSERT INTO hrd_training_plan (created,created_by,modified_by, ";
      $strSQL .= "department_code,division_code,section_code,sub_section_code, \"year\", \"month\", expected_date, ";
      $strSQL .= "duration, cost, participant, id_training_vendor, ";
      $strSQL .= "training_type, domain, competency, ";
      $strSQL .= "topic, purpose, note, status, id_creator, id_instructor) ";
      $strSQL .= "VALUES(now(),'$strmodified_byID','$strmodified_byID', ";
      $strSQL .= "'$strDataDepartment', '$strDataDivision', '$strDataSection', '$strDataSubSection','$strDataYear', '$strDataMonth', $strDataDate, ";
      $strSQL .= "'$strDataDuration', '$strDataCost', '$strDataParticipant', ";
      $strSQL .= "'$strDataInstitution', '$strDataType', '$strDataDomain', '$strDataCompetency',";
      $strSQL .= "'$strDataTopic', '$strDataPurpose', '$strDataNote', " . REQUEST_STATUS_CHECKED . ", '$strEmployeeID', '$strDataInstructor') ";
      $resExec = $db->execute($strSQL);
      // cari ID
      $strSQL = "SELECT id FROM hrd_training_plan ";
      $strSQL .= "WHERE \"year\" = '$strDataYear' AND \"month\" = '$strDataMonth' ";
      $strSQL .= "AND department_code = '$strDataDepartment'  ";
      $strSQL .= "AND topic = '$strDataTopic'  ORDER BY id DESC ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $strDataID = $rowDb['id'];
      }
      //SIMPAN DATA SYLLABUS, BEGIN
      $strID = $strDataID;
      if (is_uploaded_file($_FILES["dataTrainingSyllabus"]['tmp_name'])) {
        $arrNamaFile = explode(".", $_FILES["dataTrainingSyllabus"]['name']);
        $strNamaFile = $strID . "_" . strtolower(
                substr($_FILES["dataTrainingSyllabus"]['name'], 0, -(strlen($arrNamaFile[count($arrNamaFile) - 1]) + 1))
            );
        if (strlen($strNamaFile) > 40) {
          $strNamaFile = substr($strNamaFile, 0, 40);
        }
        if (count($arrNamaFile) > 0) {
          $strNamaFile .= "." . $arrNamaFile[count($arrNamaFile) - 1];
        }
        clearstatcache();
        if (!is_dir("trainingdoc")) {
          mkdir("trainingdoc", 0755);
        }
        $strNamaFileLengkap = "trainingdoc/" . $strNamaFile;
        if (file_exists($strNamaFileLengkap)) {
          unlink($strNamaFileLengkap);
        }
        if (move_uploaded_file($_FILES["dataTrainingSyllabus"]['tmp_name'], $strNamaFileLengkap)) {
          // update data
          $strSQL = "UPDATE hrd_training_plan SET doc = '$strNamaFile' WHERE id = '$strID' ";
          $resExec = $db->execute($strSQL);
        }
      }
      //SIMPAN DATA SYLLABUS, END
      writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "id=$strDataID", 0);
    } else {
      // cek status, jika sudah approved, gak boleh diedit lagi
      if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
        $strSQL = "SELECT status FROM hrd_training_plan WHERE id = '$strDataID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
          if ($rowDb['status'] != REQUEST_STATUS_CHECKED) {
            $strError = $error['edit_denied'];
            return false;
          }
        }
      }
      $strSQL = "UPDATE hrd_training_plan ";
      $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
      $strSQL .= "\"year\" = '$strDataYear', \"month\" = '$strDataMonth', ";
      $strSQL .= "department_code = '$strDataDepartment', topic = '$strDataTopic', ";
      $strSQL .= "purpose = '$strDataPurpose', note = '$strDataNote', ";
      $strSQL .= "id_training_vendor = '$strDataInstitution', expected_date = $strDataDate, ";
      $strSQL .= "duration = '$strDataDuration', cost = '$strDataCost', participant = '$strDataParticipant', ";
      $strSQL .= "id_creator = '$strEmployeeID', id_instructor = '$strDataInstructor' ";
      $strSQL .= "WHERE id = '$strDataID' ";
      $resExec = $db->execute($strSQL);
      //SIMPAN DATA SYLLABUS, BEGIN
      $strID = $strDataID;
      if (is_uploaded_file($_FILES["dataTrainingSyllabus"]['tmp_name'])) {
        $arrNamaFile = explode(".", $_FILES["dataTrainingSyllabus"]['name']);
        $strNamaFile = $strID . "_" . strtolower(
                substr($_FILES["dataTrainingSyllabus"]['name'], 0, -(strlen($arrNamaFile[count($arrNamaFile) - 1]) + 1))
            );
        if (strlen($strNamaFile) > 40) {
          $strNamaFile = substr($strNamaFile, 0, 40);
        }
        if (count($arrNamaFile) > 0) {
          $strNamaFile .= "." . $arrNamaFile[count($arrNamaFile) - 1];
        }
        clearstatcache();
        if (!is_dir("trainingdoc")) {
          mkdir("trainingdoc", 0755);
        }
        $strNamaFileLengkap = "trainingdoc/" . $strNamaFile;
        if (file_exists($strNamaFileLengkap)) {
          unlink($strNamaFileLengkap);
        }
        if (move_uploaded_file($_FILES["dataTrainingSyllabus"]['tmp_name'], $strNamaFileLengkap)) {
          // update data
          $strSQL = "UPDATE hrd_training_plan SET doc = '$strNamaFile' WHERE id = '$strID' ";
          $resExec = $db->execute($strSQL);
        }
      }
      //SIMPAN DATA SYLLABUS, END
      writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "id=$strDataID", 0);
    }
    if ($strDataID != "") {
      // simpan data employee participant
      // hapus dulu aja, biar simple
      $strSQL = "DELETE FROM hrd_training_plan_participant WHERE id_plan = '$strDataID' ";
      $resExec = $db->execute($strSQL);
      $intTotal = (isset($_REQUEST['maxDetail'])) ? $_REQUEST['maxDetail'] : 0; // udah dicari di depan, jadi gak perlu lagi
      for ($i = 1; $i <= $intTotal; $i++) {
        if (isset($_REQUEST['detailemployee_id' . $i])) {
          $strEmpID = $_REQUEST['detailemployee_id' . $i];
          $strStatus = (isset($_REQUEST['detailStatus' . $i])) ? $_REQUEST['detailStatus' . $i] : 0;
          $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
          // cari IDnya, kalau ada
          if ($strEmpID != "") {
            $strSQL = "SELECT id FROM hrd_employee WHERE employee_id = '$strEmpID' ";
            $resT = $db->execute($strSQL);
            if ($rowT = $db->fetchrow($resT)) {
              if ($rowT['id'] != "") {
                // simpan
                $strSQL = "INSERT INTO hrd_training_plan_participant (created, modified_by, ";
                $strSQL .= "created_by, id_plan, id_employee, status, note) ";
                $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', ";
                //$strSQL .= "'$strDataID', '" .$rowT['id']."') ";
                $strSQL .= "'$strDataID', '" . $rowT['id'] . "', '$strStatus', '$strNote') ";
                $resExec = $db->execute($strSQL);
              }
            }
          }
        }
      }
      // simpan dta tipe yang diikutkan
      // hapus dulu aja, biar simple
      $strSQL = "DELETE FROM hrd_training_plan_type WHERE id_plan = '$strDataID' ";
      $resExec = $db->execute($strSQL);
      $intTotal = (isset($_REQUEST['dataTotalType'])) ? $_REQUEST['dataTotalType'] : 0;
      for ($i = 1; $i <= $intTotal; $i++) {
        if (isset($_REQUEST['dataRequestType' . $i])) {
          $strKode = $_REQUEST['dataRequestType' . $i];
          // cari IDnya, kalau ada
          if ($strKode != "") {
            // simpan
            $strSQL = "INSERT INTO hrd_training_plan_type (created, modified_by, ";
            $strSQL .= "created_by, id_plan, \"type\") ";
            $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', ";
            $strSQL .= "'$strDataID', '$strKode') ";
            $resExec = $db->execute($strSQL);
          }
        }
      }
      $strError = $messages['data_saved'];
    } else {
      $strError = "Save Failed!";
      $bolOK = false;
    }
  } else { // ---- data SALAH
    // gunakan data yang diisikan tadi
    $arrData['dataDepartment'] = $strDataDepartment;
    $arrData['dataDivision'] = $strDataDivision;
    $arrData['dataSection'] = $strDataSection;
    $arrData['dataSubSection'] = $strDataSubSection;
    $arrData['dataYear'] = $strDataYear;
    $arrData['dataMonth'] = $strDataMonth;
    $arrData['dataDuration'] = $strDataDuration;
    $arrData['dataCost'] = $strDataCost;
    $arrData['dataParticipant'] = $strDataParticipant;
    $arrData['dataTopic'] = $strDataTopic;
    $arrData['dataInstitution'] = $strDataInstitution;
    $arrData['dataType'] = $strDataType;
    $arrData['dataDomain'] = $strDataDomain;
    $arrData['dataCompetency'] = $strDataCompetency;
    $arrData['dataPurpose'] = $strDataPurpose;
    $arrData['dataInstructor'] = $strDataInstructor;
    $arrData['dataNote'] = $strDataNote;
    $arrData['dataDate'] = $strDataDate;
    $arrData['dataID'] = $strDataID;
  }
  return $bolOK;
} // saveData
//DELETE FILE - untuk syllabus
function deleteFile($db, $strDetailID = "")
{
  global $words;
  global $bolCanEdit;
  $bolNewData = true;
  if ($strDetailID != "") {
    $strSQL = "SELECT * FROM hrd_training_plan ";
    $strSQL .= "WHERE id = '$strDetailID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strFile = $rowDb['doc'];
      if ($strFile != "") {
        if (file_exists("trainingdoc/" . $strFile)) {
          unlink("trainingdoc/" . $strFile);
        }
        $strSQL = "UPDATE hrd_training_plan SET doc = '' WHERE id = '$strDetailID' ";
        $resExec = $db->execute($strSQL);
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "file $strDetailID", 0);
      }
    }
  }
  return true;
} // delete syllabus
//----------------------------------------------------------------------
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  (isset($_REQUEST['dataDivision'])) ? $strDataDivision = $_REQUEST['dataDivision'] : $strDataDivision = "";
  (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
  (isset($_REQUEST['dataSubSection'])) ? $strDataSubSection = $_REQUEST['dataSubSection'] : $strDataSubSection = "";
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  // $strInputEmployee =   "<input type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=".($strDataEmployee = getInitialValue("Employee", null, $strDataEmployee))." $strEmpReadonly>";
  $dtNow = getdate();
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      $bolOK = saveData($db, $strError);
      if ($strError != "") {
        //echo "<script>alert(\"$strError\");/script>";
        $strMessages = $strError;
        $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
      }
    }
  }
  if ($bolCanView) {
    getData($db);
    $strInputType = getTrainingType($db, $strDataID);
    $strInputParticipant = getTrainingParticipant($db, $strDataID);
  } else {
    showError("view_denied");
  }
  //----- TAMPILKAN DATA ---------
  // beri default department, sesuai user yang login
  if ($strDataID == "" && $arrData['dataDepartment'] == "") {
    $arrData['dataDepartment'] = $arrUserInfo['department_code'];
  }
  $strDefaultWidthPx1 = 100;
  if ($arrData['dataCreator']) {
    $employeeCode = getEmployeeCode($db, $arrData['dataCreator']);
    $employeeName = getEmployeeName($db, $arrData['dataCreator']);
  }
  $strInputEmployee = "<input id=dataEmployee class=t type=text style=width:200 name=dataEmployee autocomplete=off value= " . $employeeCode . " >";
  $strLabelEmployee = "<strong id=employee_name>" . $employeeName . "</strong>";
  $bolIsEmployee = isUserEmployee();
  $strReadonly = ($arrData['dataStatus'] == 0) ? "" : "readonly"; // kalau dah approve, jadi readonly
  $strInputDuration = "<input type=text name=dataDuration id=dataDuration size=50 maxlength=20 value=\"" . $arrData['dataDuration'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly class='numeric'>";
  //$strInputParticipant = "<input type=text name=dataParticipant id=dataParticipant size=50 maxlength=20 value=\"" .$arrData['dataParticipant']. "\" style=\"width:$strDefaultWidthPx\" $strReadonly>";
  $strInputCost = "<input type=text name=dataCost id=dataCost size=50 maxlength=30 value=\"" . $arrData['dataCost'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly class='numeric'>";
  $strInputTopic = "<input type=text name=dataTopic id=dataTopic size=50 maxlength=80 value=\"" . $arrData['dataTopic'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly>";
  // $strInputInstitution = "<input type=text name=dataInstitution id=dataInstitution size=50 maxlength=80 value=\"" .$arrData['dataInstitution']. "\" style=\"width:$strDefaultWidthPx\" $strReadonly>";
  $strInputInstitution = getTrainingInstitutionList(
      $db,
      "dataInstitution",
      $arrData['dataInstitution'],
      "",
      "",
      "style=\"width:$strDefaultWidthPx\""
  );
  $strInputInstructor = getTrainingInstructorList(
      $db,
      "dataInstructor",
      $arrData['dataInstructor'],
      "",
      "",
      "style=\"width:$strDefaultWidthPx\" "
  );
  $strInputDate = "<input type=text name=dataDate id=dataDate size=15 maxlength=10 value=\"" . $arrData['dataDate'] . "\" $strReadonly>";
  $strInputPurpose = "<textarea name=dataPurpose cols=30 rows=3 wrap='virtual' style=\"width:$strDefaultWidthPx\" $strReadonly>" . $arrData['dataPurpose'] . "</textarea>";
  $strInputNote = "<textarea name=dataNote cols=30 rows=3 wrap='virtual' style=\"width:$strDefaultWidthPx\" $strReadonly>" . $arrData['dataNote'] . "</textarea>";
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strDeptKriteria = "WHERE department_code = '" . $arrUserInfo['department_code'] . "' ";
  } else {
    $strDeptKriteria = "";
  }
  $strInputTrainingSyllabus = "<input name=\"dataTrainingSyllabus\" type=\"file\" id=\"dataTrainingSyllabus\" size=\"42\" >";
  $strInputDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $arrData['dataDepartment'],
      "",
      "$strDeptKriteria",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputDivision = getDivisionList(
      $db,
      "dataDivision",
      $arrData['dataDivision'],
      $strEmptyOption,
      "",
      "style=\"width:$strDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['division']
  );
  $strInputSection = getSectionList(
      $db,
      "dataSection",
      $arrData['dataSection'],
      $strEmptyOption,
      "",
      "style=\"width:$strDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['section']
  );
  $strInputSubSection = getSubSectionList(
      $db,
      "dataSubSection",
      $arrData['dataSubSection'],
      $strEmptyOption,
      "",
      "style=\"width:$strDefaultWidthPx\" " . $ARRAY_DISABLE_GROUP['sub_section']
  );
  $strInputCompetencyTraining = getCompetencyTrainingList(
      $db,
      "dataCompetency",
      $arrData['competency'],
      "",
      "",
      "style=\"width:$strDefaultWidthPx\" id=dataCompetency"
  );
  $strInputDomainTraining = getDomainTrainingList(
      $db,
      "dataDomain",
      $arrData['domain'],
      "",
      "",
      "style=\"width:$strDefaultWidthPx\" id=dataDomain"
  );
  $strInputTypeTraining = getTypeTrainingList(
      $db,
      "dataType",
      $arrData['dataType'],
      "",
      "",
      "style=\"width:$strDefaultWidthPx\" id=dataType"
  );
  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  //by irfan
  //get AJAX
  if (isset($_POST['competency'])) {
    // global $db;
    $strSQL = "SELECT domain FROM hrd_training_type WHERE competency='$_POST[competency]'";
    $resDb = $db->execute($strSQL);
    $domain[] = "";
    while ($rowDb = $db->fetchrow($resDb)) {
      $domain[] = $rowDb['domain'];
    }
    $data = implode(",", $domain);
    echo $data;
    die();
  }
  if (isset($_POST['domain'])) {
    // global $db;
    $strSQL = "SELECT training_type FROM hrd_training_type WHERE domain='$_POST[domain]' GROUP BY training_type";
    $resDb = $db->execute($strSQL);
    $training_type[] = "";
    while ($rowDb = $db->fetchrow($resDb)) {
      $training_type[] = $rowDb['training_type'];
    }
    $data = implode(",", $training_type);
    echo $data;
    die();
  }
  // $strDomain = array();
  // $strDomain = getDomainArray($db);
  // echo "<script language ='JavaScript'>\n";
  // echo "var domainArray = new Array;\n";
  // for ($i = 0; $i < count($strDomain); $i++){
  // echo "domainArray.push(\"$strDomain[$i]\");";
  // }
  // echo "</script>";
  // $strCategory = array();
  // $strCategory = getCategoryArray($db);
  // echo "<script language ='JavaScript'>\n";
  // echo "var categoryArray = new Array;\n";
  // for ($i = 0; $i < count($strCategory); $i++){
  // echo "categoryArray.push(\"$strCategory[$i]\");";
  // }
  // echo "</script>";
  // $strType = array();
  // $strType = getTypeArray($db);
  // echo "<script language ='JavaScript'>\n";
  // echo "var typeArray = new Array;\n";
  // for ($i = 0; $i < count($strType); $i++){
  // echo "typeArray.push(\"$strType[$i]\");";
  // }
  // echo "</script>";
  // $strCategoryType = array();
  // $strCategoryType = getCategoryTypeArray($db);
  // echo "<script language ='JavaScript'>\n";
  // echo "var categoryTypeArray = new Array;\n";
  // for ($i = 0; $i < count($strCategoryType); $i++){
  // echo "categoryTypeArray[$i] = new Array;";
  // for ($j = 0; $j < count($strCategoryType[$i]); $j++){
  // echo "categoryTypeArray[$i].push('";
  // echo $strCategoryType[$i][$j];
  // echo "');\n";
  // }
  // }
  // echo "</script>";
  // $strCategoryDomain = array();
  // $strCategoryDomain = getCategoryDomainArray($db);
  // echo "<script language ='JavaScript'>\n";
  // echo "var categoryDomainArray = new Array;\n";
  // for ($i = 0; $i < count($strCategoryDomain); $i++){
  // echo "categoryDomainArray[$i] = new Array;";
  // for ($j = 0; $j < count($strCategoryDomain[$i]); $j++){
  // echo "categoryDomainArray[$i].push('";
  // echo $strCategoryDomain[$i][$j];
  // echo "');\n";
  // }
  // }
  // echo "</script>";
  // $strCategoryDomainType = array();
  // $strCategoryDomainType = getCategoryDomainTypeArray($db);
  // echo "<script language ='JavaScript'>\n";
  // echo "var categoryDomainTypeArray = new Array;\n";
  // for ($i = 0; $i < count($strCategoryDomainType); $i++){
  // echo "categoryDomainTypeArray[$i] = new Array;";
  // for ($j = 0; $j < count($strCategoryDomainType[$i]); $j++){
  // echo "categoryDomainTypeArray[$i][$j] = new Array;";
  // for ($k = 0; $k < count($strCategoryDomainType[$i][$j]); $k++){
  // echo "categoryDomainTypeArray[$i][$j].push('";
  // echo $strCategoryDomainType[$i][$j][$k];
  // echo "');\n";
  // }
  // }
  // }
  // echo "</script>";
  // $strDomainType = array();
  // $strDomainType = getDomainTypeArray($db);
  // echo "<script language ='JavaScript'>\n";
  // echo "var domainTypeArray = new Array;\n";
  // for ($i = 0; $i < count($strDomainType); $i++){
  // echo "domainTypeArray[$i] = new Array;";
  // for ($j = 0; $j < count($strDomainType[$i]); $j++){
  // echo "domainTypeArray[$i].push('";
  // echo $strDomainType[$i][$j];
  // echo "');\n";
  // }
  // }
  // echo "</script>";
  // $strDomainCategory = array();
  // $strDomainCategory = getDomainCategoryArray($db);
  // echo "<script language ='JavaScript'>\n";
  // echo "var domainCategoryArray = new Array;\n";
  // for ($i = 0; $i < count($strDomainCategory); $i++){
  // echo "domainCategoryArray.push(\"$strDomainCategory[$i]\");";
  // }
  // echo "</script>";
  // $strTypeDomain = array();
  // $strTypeDomain = getTypeDomainArray($db);
  // echo "<script language ='JavaScript'>\n";
  // echo "var typeDomainArray = new Array;\n";
  // for ($i = 0; $i < count($strTypeDomain); $i++){
  // echo "typeDomainArray.push(\"$strTypeDomain[$i]\");";
  // }
  // echo "</script>";
  // $strTypeCategory = array();
  // $strTypeCategory = getTypeCategoryArray($db);
  // echo "<script language ='JavaScript'>\n";
  // echo "var typeCategoryArray = new Array;\n";
  // for ($i = 0; $i < count($strTypeCategory); $i++){
  // echo "typeCategoryArray.push(\"$strTypeCategory[$i]\");";
  // }
  // echo "</script>";
  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  $strInputYear = getYearList(
      "dataYear",
      $arrData['dataYear'],
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputMonth = getMonthList(
      "dataMonth",
      $arrData['dataMonth'],
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputStatus = $words[$ARRAY_REQUEST_STATUS[$arrData['dataStatus']]];
}
$strInitAction .= "  document.formInput.dataDepartment.focus();
    Calendar.setup({ inputField:\"dataDate\", button:\"btnDate\" });
    init();
    onCodeBlur();
  ";
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>