<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
include_once('cls_employee.php');
include_once('../global/employee_function.php');
//include_once(getTemplate("words.inc"));
$dataPrivilege = getDataPrivileges(
    "training_request_edit.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$strTemplateFile = getTemplate("training_request_edit.html");
//---- INISIALISASI ----------------------------------------------------
$strWordsInputTrainingRequest = getWords("input training request");
$strWordsTrainingRequestList = getWords("training request list");
$strWordsApprovedTraining = getWords("approved training");
$strWordsTrainingReport = getWords("training report");
$strWordsDepartment = getWords("department");
$strWordsDivision = getWords("division");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("subsection");
$strWordsEmployee = getWords("requested by") . " (" . getWords("employee") . ") ";
$strWordsTrainingPlan = getWords("training plan");
$strWordsTrainingProfile = getWords("training profile");
$strWordsTrainingTopic = getWords("training topic");
$strWordsTrainingCategory = getWords("training category");
$strWordsTrainingType = getWords("training type");
$strWordsTrainingStatus = getWords("training status");
$strWordsPurpose = getWords("purpose");
$strWordsLocation = getWords("location");
$strWordsAddress = getWords("address");
$strWordsInstitution = getWords("institution");
$strWordsTrainer = getWords("trainer");
$strWordsInstructor = getWords("instructor");
$strWordsExpectedResult = getWords("expected result");
$strWordsExpectedDate = getWords("expected date");
$strWordsParticipant = getWords("participant");
$strWordsTimeDetail = getWords("training time");
$strWordsRequestStatus = getWords("request status");
$strWordsRequestNumber = getWords("request no.");
$strWordsDate = getWords("request date");
$strWordsCost = getWords("training cost");
$strWordsOtherCost = getWords("other cost");
$strWordsPaidBy = getWords("paid by");
$strWordsSave = getWords("save");
$strWordsClearForm = getWords("clear form");
$strWordsDeleteFile = getWords("delete file");
$strWordsTrainingSyllabus = getWords("syllabus");
$strFileOption = "";
$strButtons = "";
$strDataDetail = "";
$intDefaultWidth = 30;
$intDefaultHeight = 3;
$strNow = date("Y-m-d");
$strMessages = "";
$strMsgClass = "";
$strInputLastNumber = "";
$strInputType = "";
$strInputParticipant = "";
$strInputTrainerMore = "";
$strInputTimeDetail = "";
$intFormNumberDigit = "";
$strTargetElements = "";
$bolError = false;
$strUserRole = "";
$strInitActions = "";
$strDisabledSave = ""; // apakah tidak boleh edit
$strEmptyOptions = "<option value=''> </option>";
$arrData = [
    "dataDate"             => $strNow,
    "dataTrainingDate"     => "",
    "dataTrainingDateThru" => "",
    "dataDepartment"       => "",
    "dataEmployee"         => "", // beri default
    "dataNumber"           => "",
    "dataDuration"         => "0",
    "dataDurationMin"      => "0", // durasi, dalam menit
    "dataFinish"           => "00:00",
    "dataStart"            => "00:00",
    "dataTopic"            => "", // diabaikan
    "dataNote"             => "",
    "dataTrainer"          => "",
    "dataInstitution"      => "", // diabaikan
    "dataInstitutionID"    => "",
    "dataTrainingType"     => "", // jenis
    "dataParticipant"      => "", // daftar
    "dataTrainerMore"      => "", // daftar
    "dataCategory"         => "0",
    "dataPlace"            => "0",
    "dataAddress"          => "",
    "dataPlan"             => "",
    "dataCost"             => 0,
    "dataCostOther"        => 0, // biaya tambahan
    "dataTrainingStatus"   => "0",
    "dataStatus"           => "0",
    "dataPaidBy"           => "0",
    "dataResult"           => "",
    "dataID"               => "",
    "doc"                  => "",
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
  global $_REQUEST;
  global $bolCanEdit;
  if (isset($_REQUEST['fileID']) && !isset($_REQUEST['btnSave'])) {
    if ($bolCanEdit) {
      deleteFile($db, $_REQUEST['fileID']);
    } else {
      $strMessages = getWords('delete_denied');
      $strMsgClass = "class=bgError";
    }
  }
  //UNTUK MENAMPILKAN DOCUMENT
  global $strFileOption;
  //global $strWordsDeleteFile;
  $strSQL = "SELECT id,doc FROM hrd_training_plan";
  $resDb = $db->execute($strSQL);
  while ($row = $db->fetchrow($resDb)) {
    if ($row['doc'] == "") {
      $strDataDoc = "";
    } else {
      if (file_exists("trainingdoc/" . $row['doc'])) {
        $strDataDoc = "<a href=\"trainingdoc/" . $row['doc'] . "\" target=\"_blank\" > <img  src='trainingdoc/" . $row['doc'] . "' alt=\"" . $row['doc'] . "\"></a>&nbsp;&nbsp;";
      } else {
        $strDataDoc = "";
      }
    }
    //$strFileOption = "<td>&nbsp;</td><td>&nbsp;</td><td><span id=\"doc\">&nbsp;".$strDataDoc."</span>";
    if ($strDataDoc != "") {
      $tempId = $row['id'];
      $strFileOption .= "<td id='syllabus$tempId' style=display:none><span id=\"doc\">&nbsp;" . $strDataDoc . "</span></td>";
    }
    //if($strDataDoc != "") $strFileOption .= "<input name=\"btnDeleteDoc\" type=\"button\" id=\"btnDelete\" value=\"$strWordsDeleteFile\" onClick=\"deleteFile($strDataID);\"></td>";
    //$strFileOption .= "<input type=hidden id='syllabusDoc' name='syllabusDoc' value=$row[doc]>";
  }
  //SEDIKIT DESPERATE, selesai
  // echo   "a1:  ".$strDataID;
  if ($strDataID != "") {
    $strSQL = "
        SELECT t1.*, t2.employee_id, t3.* 
        FROM hrd_training_request AS t1 
        LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id 
		LEFT JOIN hrd_training_plan AS t3 ON t1.id_plan = t3.id 
        WHERE t1.id = '$strDataID' 
      ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataDate'] = $rowDb['request_date'];
      $arrData['dataTrainingDate'] = $rowDb['training_date'];
      $arrData['dataTrainingDateThru'] = $rowDb['training_date_thru'];
      $arrData['dataStatus'] = $rowDb['status'];
      $arrData['dataTrainingStatus'] = $rowDb['training_status'];
      $arrData['dataPlace'] = $rowDb['place'];
      $arrData['dataCategory'] = $rowDb['category'];
      $arrData['dataPlan'] = $rowDb['id_plan'];
      $arrData['dataTopic'] = $rowDb['topic'];
      $arrData['dataDepartment'] = $rowDb['department_code'];
      $arrData['dataEmployee'] = $rowDb['employee_id'];
      $arrData['dataNumber'] = $rowDb['request_number'];
      $arrData['dataTrainer'] = $rowDb['trainer'];
      $arrData['dataInstitution'] = $rowDb['institution'];
      $arrData['dataInstructorID'] = $rowDb['id_instructor'];
      $arrData['dataID'] = $strDataID;
      $arrData['dataAddress'] = $rowDb['address'];
      $arrData['dataCost'] = $rowDb['cost'];
      $arrData['dataTrainingType'] = $rowDb['training_type'];
      $arrData['dataPaidBy'] = $rowDb['paid_by'];
      $arrData['dataResult'] = $rowDb['result'];
      $arrData['dataDuration'] = $rowDb['total_duration'];
      $arrData['dataDurationMin'] = $rowDb['total_hour'];
      $arrData['dataCostOther'] = $rowDb['cost_other'];
      $arrData['doc'] = $rowDb['doc'];
      $arrData['dataDivision'] = $rowDb['division_code'];
      $arrData['dataSection'] = $rowDb['section_code'];
      $arrData['dataSubSection'] = $rowDb['sub_section_code'];
      $arrData['dataNote'] = $rowDb['note'];
      $arrData['dataInstitutionID'] = $rowDb['id_training_vendor'];
      $arrData['dataTrainingCompetency'] = $rowDb['competency'];
      $arrData['dataTrainingDomain'] = $rowDb['domain'];
      $arrData['dataTrainingDate'] = $rowDb['expected_date'];
    }
    // print_r($arrData);
  } else {
    // cek jika ada request dari training plan
    if (isset($_REQUEST['btnCreate'])) {
      $strPlanID = (isset($_REQUEST['id_plan'])) ? $_REQUEST['id_plan'] : "";
      if ($strPlanID != "") {
        $arrData['dataPlan'] = $strPlanID;
        $strSQL = "SELECT * FROM hrd_training_plan WHERE id = '$strPlanID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
          $arrData['dataDivision'] = $rowDb['division_code'];
          $arrData['dataDepartment'] = $rowDb['department_code'];
          $arrData['dataSection'] = $rowDb['section_code'];
          $arrData['dataSubSection'] = $rowDb['sub_section_code'];
          $arrData['dataTopic'] = $rowDb['topic'];
          $arrData['dataNote'] = $rowDb['note'];
          $arrData['dataInstitutionID'] = $rowDb['id_training_vendor'];
          $arrData['dataInstructorID'] = $rowDb['id_instructor'];
          $arrData['dataTrainingCompetency'] = $rowDb['competency'];
          $arrData['dataTrainingDomain'] = $rowDb['domain'];
          $arrData['dataTrainingType'] = $rowDb['training_type'];
          $arrData['dataTrainingDate'] = $rowDb['expected_date'];
          if ($rowDb['duration'] > 1) {
            if ($rowDb['expected_date'] != "") {
              $arrData['dataTrainingDateThru'] = getNextDate($rowDb['expected_date'], $rowDb['duration'] - 1);
            }
          } else {
            $arrData['dataTrainingDateThru'] = $arrData['dataTrainingDate'];
          }
        }
      }
    }
    // cek jika ada request untuk sharing session dari "search training request"
    if (isset($_REQUEST['btnCreateSharing'])) {
      $strPlanID = (isset($_REQUEST['id_plan'])) ? $_REQUEST['id_plan'] : "";
      if ($strPlanID != "") {
        $arrData['dataPlan'] = $strPlanID;
        $strSQL = "SELECT * FROM hrd_training_request WHERE id = '$strPlanID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
          $arrData['dataDepartment'] = $rowDb['department_code'];
          //$arrData['dataNote'] = $rowDb['note'];
          $arrData['dataInstitutionID'] = $rowDb['id_institution'];
          $arrData['dataTrainingType'] = $rowDb['training_type'];
          $arrData['dataCategory'] = '3';
          $arrData['dataResult'] = $rowDb['result'];
          $arrData['dataResult'] = $rowDb['result'];
          $arrData['dataPlan'] = $rowDb['id_plan'];
          $arrData['dataTrainer'] = $rowDb['trainer'];
          $arrData['dataLocation'] = $rowDb['place'];
          $arrData['dataAddress'] = $rowDb['address'];
          $arrData['dataPaid'] = $rowDb['paid_by'];
          //$arrData['dataTrainingDate'] = $rowDb['expected_date'];
          // if ($rowDb['duration'] > 1)
          // {
          // if ($rowDb['expected_date'] != "" ) $arrData['dataTrainingDateThru'] = getNextDate($rowDb['expected_date'], $rowDb['duration']-1);
          // }
          // else
          // $arrData['dataTrainingDateThru'] = $arrData['dataTrainingDate'];
        }
      }
    }
  }
  return true;
} // showData
// fungsi mengambil daftar participant yang dimohonkan
function getTrainingParticipant($db, $strDataID = "")
{
  global $words;
  global $strTargetElements;
  global $_REQUEST;
  global $strEmptyOptions;
  global $bolIsEmployee;
  $intMaxShow = 4; // tambahan yang perlu dimunculkan
  if ($bolIsEmployee) {
    $intMaxShow = 1;
  }
  $intAdd = 50; // maksimum tambahan
  $intRows = 0;
  $intShown = 0;
  $strResult = "";
  $strButtons = "";
  $arrStatus = ["ok", "cancel"];
  $strResult .= " <table border=0 class=gridTable cellpadding=0 cellspacing=0 >\n";
  $strResult .= "  <tr valign=top class=tableHeader>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('no') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('employee id') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('name') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('training cost') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('other cost') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('status') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('note') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('delete') . "</td>\n";
  // $strResult .= "  <td nowrap>&nbsp;" .getWords('form')."</td>\n";
  // $strResult .= "  <td nowrap>&nbsp;" .getWords('certificate')."</td>\n";
  $strResult .= "  </tr>\n";
  $arrParticipant = [];
  if ($strDataID != "") {
    $strSQL = "
          SELECT t1.id, t1.note, t1.status, t1.cost, t1.other_cost, 
            t2.employee_id, t2.employee_name
          FROM hrd_training_request_participant AS t1 
          LEFT JOIN hrd_employee AS t2 ON t2.id = t1.id_employee 
          WHERE t1.id_request = '$strDataID' 
      ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrParticipant[$rowDb['id']] = $rowDb;
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  } else if (isset($_REQUEST['btnCreate'])) {
    $strPlanID = (isset($_REQUEST['id_plan'])) ? $_REQUEST['id_plan'] : "";
    if ($strPlanID != "") {
      /*
      $strSQL  = "
          SELECT t1.id, t1.note, t1.status, t2.employee_id, t2.employee_name, 0 as cost, 0 as other_cost
          FROM hrd_training_plan_participant AS t1
          LEFT JOIN hrd_employee AS t2 ON t2.id = t1.id_employee
          WHERE t1.id_plan = '$strPlanID' AND t1.status = 0
      ";
      */
      //Custom: hilangkan participant pada plan => mengacu participant pada request
      //EDIT : Harusnya kosong, ignore all
      /*
      $strSQL = "SELECT t1.id, t1.note, t1.status, t2.employee_id, t2.employee_name, t1.cost, t1.other_cost
          FROM hrd_training_request_participant AS t1
          LEFT JOIN hrd_employee AS t2 ON t2.id = t1.id_employee
          LEFT JOIN hrd_training_request AS t3 ON t3.id = t1.id_request
          WHERE t3.id_plan = '$strPlanID'";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) {
        $arrParticipant[$rowDb['id']] = $rowDb;
      }
      */
    }
  }
  $bolCurrentEmpIsParticipant = false;
  foreach ($arrParticipant AS $id => $rowDb) {
    $intRows++;
    $intShown++;
    //CUSTOM: bila employee - hide if not self
    $intRowsVisible = 1;
    $strCustomHide = "";
    global $arrUserInfo;
    if ($bolIsEmployee && $rowDb['employee_id'] != $arrUserInfo['employee_id']) {
      $strCustomHide = " style=display:none ";
      $intShown--;
      $intRowsVisible = $intRows;
    } else {
      $bolCurrentEmpIsParticipant = true;
    }
    $strResult .= "<tr valign=top $strCustomHide id=\"detailRows$intRows\">\n";
    //--------CUSTOM SELESAI-------------------
    $strResult .= "  <td align=right nowrap><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">$intRowsVisible&nbsp;</td>";
    $strAction = "onFocus = \"AC_kode = 'detailEmployeeID$intRows';AC_nama='detailName$intRows';\" ";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailEmployeeID$intRows id=detailEmployeeID$intRows value=\"" . $rowDb['employee_id'] . "\" $strAction></td>";
    $strResult .= "  <td nowrap>&nbsp;<strong id=\"detailName$intRows\">" . $rowDb['employee_name'] . "</strong></td>";
    $strResult .= "  <td><input type=text size=15 maxlength=20 name=detailAmount$intRows id=detailAmount$intRows value=\"" . $rowDb['cost'] . "\" class='numberformat numeric'></td>";
    $strResult .= "  <td><input type=text size=15 maxlength=20 name=detailAmountOther$intRows id=detailAmountOther$intRows value=\"" . $rowDb['other_cost'] . "\" class='numberformat numeric'></td>";
    $strResult .= "  <td>" . getComboFromArray($arrStatus, "detailStatus$intRows", $rowDb['status']) . "</td>";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailNote$intRows id=detailNote$intRows value=\"" . $rowDb['note'] . "\"></td>";
    $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center title='cost " . standardFormat(
            $rowDb['cost']
        ) . "'>g<input type=checkbox id='chkID$intRows' name='chkID$intRows' $strAction></td>\n";
    // $strActionForm = " onChange = \"chkPrintForm($intRows);\" ";
    // $strResult .= "  <td align=center title='id " .$rowDb['employee_id']."'>f<input type=checkbox id='chkIDF$intRows' name='chkIDF$intRows' $strActionForm value='" .$rowDb['id']. "'></td>\n";
    // $strActionCertificate = " onChange = \"chkPrintCertificate($intRows);\" ";
    // $strResult .= "  <td align=center title='id " .$rowDb['employee_id']."'>f<input type=checkbox id='chkIDC$intRows' name='chkIDC$intRows' $strActionCertificate></td>\n";
    $strResult .= "</tr>\n";
    $strTargetElements .= ",\"detailEmployeeID$intRows\"";
  }
  // tambahkan dengan data kosong
  // Custom: bila employee - tampilkan max 1 data kosong; biar gampang langsung cek dia employee bukan
  if ($bolIsEmployee && !$bolCurrentEmpIsParticipant) {
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
    //custom sedikit di sini
    global $arrUserInfo;
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailEmployeeID$intRows id=detailEmployeeID$intRows $strDisabled $strAction value = '" . $arrUserInfo['employee_id'] . "' readonly></td>";
    $strResult .= "  <td nowrap>&nbsp;<strong id=\"detailName$intRows\">" . $arrUserInfo['employee_name'] . "</strong></td>";
    $strResult .= "  <td><input type=text size=15 maxlength=20 name=detailAmount$intRows id=detailAmount$intRows value=0 class='numberformat numeric'></td>";
    $strResult .= "  <td><input type=text size=15 maxlength=20 name=detailAmountOther$intRows id=detailAmountOther$intRows value=0 class='numberformat numeric'></td>";
    $strResult .= "  <td>" . getComboFromArray($arrStatus, "detailStatus$intRows") . "</td>";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailNote$intRows id=detailNote$intRows></td>";
    $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center><input type=checkbox id='chkID$intRows' name='chkID$intRows' $strAction></td>\n";
    // $strActionForm = " onChange = \"chkPrintForm($intRows);\" ";
    // $strResult .= "  <td align=center><input type=checkbox id='chkIDF$intRows' name='chkIDF$intRows' $strActionForm></td>\n";
    // $strActionCertificate = " onChange = \"chkPrintCertificate($intRows);\" ";
    // $strResult .= "  <td align=center><input type=checkbox id='chkIDC$intRows' name='chkIDC$intRows' $strActionCertificate></td>\n";
    $strResult .= "</tr>\n";
    $strTargetElements .= ",\"detailEmployeeID$intRows\"";
  } else {
    //-------------CUSTOM SELESAI--------------------------
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
      $strResult .= "  <td><input type=text size=15 maxlength=20 name=detailAmount$intRows id=detailAmount$intRows value=0 class='numberformat numeric'></td>";
      $strResult .= "  <td><input type=text size=15 maxlength=20 name=detailAmountOther$intRows id=detailAmountOther$intRows value=0 class='numberformat numeric'></td>";
      $strResult .= "  <td>" . getComboFromArray($arrStatus, "detailStatus$intRows") . "</td>";
      $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailNote$intRows id=detailNote$intRows></td>";
      $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
      $strResult .= "  <td align=center><input type=checkbox id='chkID$intRows' name='chkID$intRows' $strAction></td>\n";
      // $strActionForm = " onChange = \"chkPrintForm($intRows);\" ";
      // $strResult .= "  <td align=center><input type=checkbox id='chkIDF$intRows' name='chkIDF$intRows' $strActionForm></td>\n";
      // $strActionCertificate = " onChange = \"chkPrintCertificate($intRows);\" ";
      // $strResult .= "  <td align=center><input type=checkbox id='chkIDC$intRows' name='chkIDC$intRows' $strActionCertificate></td>\n";
      $strResult .= "</tr>\n";
      $strTargetElements .= ",\"detailEmployeeID$intRows\"";
    }
  }
  $strButtons = "";
  //Custom: Employee cuma boleh lihat = 1 - dia or empty
  if (!$bolIsEmployee) {
    $strResult .= " <tr valign=top><td>&nbsp;</td>\n";
    $strResult .= "  <td colspan=10>&nbsp;<a href=\"javascript:showMoreInput();\">" . getWords(
            'more'
        ) . "</a></td></tr>\n";
  }
  $strResult .= " <tr valign=top><td>&nbsp;</td>\n";
  // $strResult .= " <td colspan=10><input name=\"btnPrintForm\" type=submit id=\"btnPrintForm\" value=\"" .getWords('print form')."\" onClick=\"goPrintForm();\">";
  // $strResult .= " &nbsp;<input name=\"btnPrintCertificate\" type=submit id=\"btnPrintCertificate\" value=\"" .getWords('print certificate')."\" onClick=\"goPrintCertificate();\"></td></tr>";
  $strResult .= "  </table>\n";
  // tambahkan hidden data
  $strResult .= "<input type=hidden id='maxDetail' name='maxDetail' value=$intRows>";
  $strResult .= "<input type=hidden id='numShow' name='numShow' value=$intShown>";
  return $strResult;
} // getTrainingParticipant
// fungsi mengambil daftar trainer jika berasal dari karyawan
function getTrainingTrainer($db, $strDataID = "")
{
  global $words;
  global $strTargetElements;
  global $_REQUEST;
  $intMaxShow = 2; // tambahan yang perlu dimunculkan
  $intAdd = 50; // maksimum tambahan
  $intRows = 0;
  $intShown = 0;
  $strResult = "";
  $arrStatus = ["ok", "cancel"];
  $strResult .= " <table border=0 class=gridTable cellpadding=0 cellspacing=0 >\n";
  $strResult .= "  <tr valign=top class=tableHeader>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('no') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('employee id') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('name') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('status') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('delete') . "</td>\n";
  $strResult .= "  </tr>\n";
  $arrParticipant = [];
  if ($strDataID != "") {
    $strSQL = "
          SELECT t1.id, t1.status, t1.cost, t2.employee_id, t2.employee_name 
          FROM hrd_training_request_trainer AS t1 
          LEFT JOIN hrd_employee AS t2 ON t2.id = t1.id_employee 
          WHERE t1.id_request = '$strDataID' 
      ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrParticipant[$rowDb['id']] = $rowDb;
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  } else if (isset($_REQUEST['btnCreateSharing'])) {
    $strPlanID = (isset($_REQUEST['id_plan'])) ? $_REQUEST['id_plan'] : "";
    if ($strPlanID != "") {
      $arrData['dataPlan'] = $strPlanID;
      $strSQL = "
          SELECT t1.id, t1.status, t1.cost, t2.employee_id, t2.employee_name 
          FROM hrd_training_request_participant AS t1 
          LEFT JOIN hrd_employee AS t2 ON t2.id = t1.id_employee 
          WHERE t1.id_request = '$strPlanID' 
       ";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) {
        $arrParticipant[$rowDb['id']] = $rowDb;
      }
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  foreach ($arrParticipant AS $id => $rowDb) {
    $intRows++;
    $intShown++;
    $strResult .= "<tr valign=top  id=\"detailTrainerRows$intRows\">\n";
    $strResult .= "  <td align=right nowrap><input type=hidden name=detailTrainerID$intRows value=\"" . $rowDb['id'] . "\">$intRows&nbsp;</td>";
    $strAction = "onFocus = \"AC_kode = 'detailTrainerEmployeeID$intRows';AC_nama='detailTrainerName$intRows';\" ";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailTrainerEmployeeID$intRows id=detailTrainerEmployeeID$intRows value=\"" . $rowDb['employee_id'] . "\" $strAction></td>";
    $strResult .= "  <td nowrap>&nbsp;<strong id=\"detailTrainerName$intRows\">" . $rowDb['employee_name'] . "</strong></td>";
    $strResult .= "  <td>" . getComboFromArray($arrStatus, "detailTrainerStatus$intRows", $rowDb['status']) . "</td>";
    $strAction = " onChange = \"chkTrainerDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center title='cost " . standardFormat(
            $rowDb['cost']
        ) . "'><input type=checkbox id='chkTrainerID$intRows' name='chkTrainerID$intRows' $strAction></td>\n";
    $strResult .= "</tr>\n";
    $strTargetElements .= ",\"detailTrainerEmployeeID$intRows\"";
  }
  // tambahkan dengan data kosong
  for ($i = 1; $i <= $intAdd; $i++) {
    $intRows++;
    if ($intRows <= $intMaxShow) {
      $strResult .= "<tr valign=top  id=\"detailTrainerRows$intRows\">\n";
      $intShown++;
      $strDisabled = "";
    } else {
      $strResult .= "<tr valign=top  id=\"detailTrainerRows$intRows\" style=\"display:none\">\n";
      $strDisabled = "disabled";
    }
    $strResult .= "  <td align=right nowrap>$intRows&nbsp;</td>";
    $strAction = "onFocus = \"AC_kode = 'detailTrainerEmployeeID$intRows';AC_nama='detailTrainerName$intRows';\" ";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailTrainerEmployeeID$intRows id=detailTrainerEmployeeID$intRows $strDisabled $strAction></td>";
    $strResult .= "  <td nowrap>&nbsp;<strong id=\"detailTrainerName$intRows\"></strong></td>";
    $strResult .= "  <td>" . getComboFromArray($arrStatus, "detailTrainerStatus$intRows") . "</td>";
    $strAction = " onChange = \"chkTrainerDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center><input type=checkbox id='chkTrainerID$intRows' name='chkTrainerID$intRows' $strAction></td>\n";
    $strResult .= "</tr>\n";
    $strTargetElements .= ",\"detailTrainerEmployeeID$intRows\"";
  }
  $strResult .= " <tr valign=top><td>&nbsp;</td>\n";
  $strResult .= "  <td colspan=3>&nbsp;<a href=\"javascript:showMoreTrainerInput();\">" . getWords(
          'more'
      ) . "</a></td></tr>\n";
  $strResult .= "  </table>\n";
  // tambahkan hidden data
  $strResult .= "<input type=hidden id='maxTrainerDetail' name='maxTrainerDetail' value=$intRows>";
  $strResult .= "<input type=hidden id='numTrainerShow' name='numTrainerShow' value=$intShown>";
  return $strResult;
} // getTrainingTrainer
// menampilkan bagian detail waktu pelaksanaan training
function getTimeDetail($db, $strDataID = "", $arrData, $strInputDuration)
{
  global $words;
  global $_REQUEST;
  global $strInitActions;
  $intMaxShow = 1; // tambahan yang perlu dimunculkan
  $intAdd = 30; // maksimum tambahan
  $intRows = 0;
  $intShown = 0;
  $strResult = "";
  $date = date('Y-m-d');
  $arrtime = [];
  if ($strDataID != "") {
    $strSQL = "
          SELECT id, timestart, timefinish, duration, trainingdate 
          FROM hrd_training_request_detailtime 
          WHERE id_request = '$strDataID' 
      ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrtime[$rowDb['id']] = $rowDb;
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  $strResult .= " <table border=0 class=gridTable cellpadding=0 cellspacing=0 >\n";
  $strResult .= "  <tr valign=top class=tableHeader>\n";
  $strResult .= "  <td nowrap >&nbsp;" . getWords("no.") . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords("date") . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords("start time") . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords("finish time") . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords("duration") . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords("delete") . "</td>\n";
  $strResult .= "  </tr>\n";
  foreach ($arrtime AS $id => $rowDb) {
    $intRows++;
    $intShown++;
    $dataDate = $rowDb['trainingdate'];
    $duration = $rowDb['duration'];
    $strResult .= "<tr valign=top  id=\"detailRowsA$intRows\">\n";
    $strResult .= "<td align=right nowrap><input type=hidden name=detailIDA$intRows id=detailIDA$intRows  value=\"" . $rowDb['id'] . "\" >$intRows&nbsp;</td>";
    $strResult .= "<td><input type=text size=10 maxlength=10 name=dataDateTraining$intRows id=dataDateTraining$intRows value=\"" . substr(
            $rowDb['trainingdate'],
            0,
            10
        ) . "\" class='date-empty'> ";
    $strResult .= " <input name=\"btnDate$intRows\" type=button id=\"btnDate$intRows\" value='..'></td>";
    $strResult .= "  <td align=center><input type=text size=5 name=dataStart$intRows id=dataStart$intRows value=\"" . substr(
            $rowDb['timestart'],
            0,
            5
        ) . "\" onBlur=\"getDuration('$intRows')\" class='input_mask mask_shorttime'></td>";
    $strResult .= "  <td align=center><input type=text size=5 name=dataFinish$intRows id=dataFinish$intRows value=\"" . substr(
            $rowDb['timefinish'],
            0,
            5
        ) . "\" onBlur=\"getDuration('$intRows')\" class='input_mask mask_shorttime'></td>";
    $strResult .= "<td><strong id=dataDuration$intRows>$duration</td>";
    $strAction = " onClick = \"chkDeleteChangedtime($intRows);\" ";
    $strResult .= "  <td align=center><input type=checkbox id='chkIDA$intRows' name='chkIDA$intRows' $strAction></td>\n";
    $strResult .= "</tr>\n";
    $strInitActions .= "Calendar.setup({ inputField:\"dataDateTraining$intRows\", button:\"btnDate$intRows\" }) \n";
  }
  // tambahkan dengan data kosong
  for ($i = 0; $i <= $intAdd; $i++) {
    $intRows++;
    if ($intRows <= $intMaxShow) {
      $strResult .= "<tr valign=top  id=\"detailRowsA$intRows\">\n";
      $intShown++;
      $strDisabled = "";
    } else {
      $strResult .= "<tr valign=top  id=\"detailRowsA$intRows\" style=\"display:none\">\n";
      $strDisabled = "disabled";
    }
    $date = ""; //date('Y-m-d', strtotime ("$i Day"));
    $strResult .= "  <td align=right nowrap><input type=hidden name=detailIDA$intRows id=detailIDA$intRows  $strDisabled>$intRows&nbsp;</td>";
    $strResult .= "<td><input type=text size=10 maxlength=10 name=dataDateTraining$intRows id=dataDateTraining$intRows value=\"$date\"   class='date-empty'> ";
    $strResult .= " <input name=\"btnDate$intRows\" type=button id=\"btnDate$intRows\" value='..' ></td>";
    $strResult .= "  <td align=center><input type=text size=5 name=dataStart$intRows id=dataStart$intRows value=\"" . "" . "\" onBlur=\"getDuration('$intRows')\"  class='input_mask mask_shorttime'></td>";
    $strResult .= "  <td align=center><input type=text size=5 name=dataFinish$intRows id=dataFinish$intRows value=\"" . "" . "\" onBlur=\"getDuration('$intRows')\" class='input_mask mask_shorttime'></td>";
    $strResult .= "  <td><strong id=dataDuration$intRows>$strInputDuration</td>";
    $strAction = " onClick = \"chkDeleteChangedtime($intRows);\" ";
    $strResult .= "  <td align=center><input type=checkbox id='chkIDA$intRows' name='chkIDA$intRows' $strAction></td>\n";
    $strResult .= "</tr>\n";
    $strInitActions .= "Calendar.setup({ inputField:\"dataDateTraining$intRows\", button:\"btnDate$intRows\" }) \n";
  }
  // sekedar info tambahan saja
  $intHour = floor($arrData['dataDurationMin'] / 60);
  $intMin = ($arrData['dataDurationMin'] % 60);
  $strInfo = " Duration " . $arrData['dataDuration'] . " (days), $intHour : $intMin (hours) ";
  // footer
  $strResult .= "  <tr ><td colspan=6 title='$strInfo'>&nbsp;<a href=\"javascript:showMoreInputTime();\">" . getWords(
          'more'
      ) . "</a></td></tr>\n";
  $strResult .= "  </table>\n";
  // tambahkan hidden data
  $strResult .= "<input type=hidden id='maxDetail1' name='maxDetail1' value=$intRows>";
  $strResult .= "<input type=hidden id='numShow1' name='numShow1' value=$intShown>";
  return $strResult;
} // getTimeDetail
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
  // print_r($_REQUEST);
  $strDataDate = (isset($_REQUEST['dataDate'])) ? $_REQUEST['dataDate'] : "";
  $strDataTrainingDate = (isset($_REQUEST['dataTrainingDate'])) ? $_REQUEST['dataTrainingDate'] : "";
  $strDataTrainingDateThru = (isset($_REQUEST['dataTrainingDateThru'])) ? $_REQUEST['dataTrainingDateThru'] : "";
  $strDataDepartment = (isset($_REQUEST['dataDepartment'])) ? trim($_REQUEST['dataDepartment']) : "";
  $strDataNumber = (isset($_REQUEST['dataNumber'])) ? $_REQUEST['dataNumber'] : "";
  $strDataEmployee = (isset($_REQUEST['dataEmployee'])) ? $_REQUEST['dataEmployee'] : "";
  $strDataTopic = (isset($_REQUEST['dataTopic'])) ? $_REQUEST['dataTopic'] : "";
  $strDataType = (isset($_REQUEST['dataTrainingType'])) ? $_REQUEST['dataTrainingType'] : "";
  $strDataNote = (isset($_REQUEST['dataNote'])) ? $_REQUEST['dataNote'] : "";
  $strDataTrainer = (isset($_REQUEST['dataTrainer'])) ? $_REQUEST['dataTrainer'] : "";
  $strDataInstitutionID = (isset($_REQUEST['dataInstitutionID'])) ? $_REQUEST['dataInstitutionID'] : "";
  $strDataCategory = (isset($_REQUEST['dataCategory'])) ? $_REQUEST['dataCategory'] : 0;
  $strDataPlace = (isset($_REQUEST['dataPlace'])) ? $_REQUEST['dataPlace'] : 0;
  $strDataAddress = (isset($_REQUEST['dataAddress'])) ? $_REQUEST['dataAddress'] : "";
  $strDataResult = (isset($_REQUEST['dataResult'])) ? $_REQUEST['dataResult'] : "";
  $strDataTrainingStatus = (isset($_REQUEST['dataTrainingStatus'])) ? $_REQUEST['dataTrainingStatus'] : 0;
  $strDataCost = (isset($_REQUEST['dataCost'])) ? $_REQUEST['dataCost'] : 0;
  $strDataCost = str_replace(",", "", $strDataCost);
  if (!is_numeric($strDataCost)) {
    $strDataCost = 0;
  }
  $strDataOtherCost = (isset($_REQUEST['dataCostOther'])) ? $_REQUEST['dataCostOther'] : 0;
  $strDataOtherCost = str_replace(",", "", $strDataOtherCost);
  if (!is_numeric($strDataOtherCost)) {
    $strDataOtherCost = 0;
  }
  $strDataPaidBy = (isset($_REQUEST['dataPaidBy'])) ? $_REQUEST['dataPaidBy'] : 0;
  if (!is_numeric($strDataPaidBy)) {
    $strDataPaidBy = 0;
  }
  $strDataPlan = (isset($_REQUEST['dataPlan'])) ? $_REQUEST['dataPlan'] : "";
  $fltTotalCost = $strDataCost + $strDataOtherCost;
  if (!is_numeric($strDataPlan)) {
    $strDataPlan = "NULL";
  }
  $intTotal1 = ($_REQUEST['numShow1']) ? $_REQUEST['numShow1'] : 0; // udah dicari di depan, jadi gak perlu lagi
  $total = 0;
  // cari ID dari employee
  $objEmp = new clsEmployees($db);
  $objEmp->loadData();
  $strIDEmployee = $objEmp->getInfoByCode($strDataEmployee, "id");
  // cek validasi -----------------------
  if ($strIDEmployee == "") {
    $strIDEmployee = -1;
    $bolOK = false;
  } else if ($strDataNumber == "") {
    $strError = $error['empty_code'];
    $bolOK = false;
  } else if (!validStandardDate($strDataDate)) {
    $strError = $error['invalid_date'];
    $bolOK = false;
  } else if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    // cek apakah tanggal terjadi sebelum hari ini/sebelum tanggal request.
    $tmp = dateCompare($strDataDate, $strDataTrainingDate);
    if ($tmp == 1) { // lebih besar hari ini (requestdate)
      $strError = getWords("cannot_entry_date_before_today");
      $bolOK = false;
    }
  }
  // cek apakah ada conflik atau gak dari pesertanya
  if ($bolOK) {
    $intTotal = (isset($_REQUEST['maxDetail'])) ? $_REQUEST['maxDetail'] : 0;
    $arrEmployee = []; // nampung ID dari employee peserta
    for ($i = 1; $i <= $intTotal; $i++) {
      $strEmpID = (isset($_REQUEST['detailEmployeeID' . $i])) ? $_REQUEST['detailEmployeeID' . $i] : "";
      if ($strEmpID != "") {
        $strID = $objEmp->getInfoByCode($strEmpID, "id");;
        $arrEmployee[$strEmpID] = $strID;
        // cek permohonan cuti
        if ($bolOK) {
          $arrTmp = isLeaveExists(
              $db,
              $strID,
              $strDataTrainingDate,
              $strDataTrainingDateThru,
              $strDataID
          ); // activity.php
          if ($arrTmp['status']) { // ada data conflic
            $bolOK = false;
            $strError = getWords("activity_conflict") . " {Leave - $strEmpID} ";
            $strError .= "<a href=\"leave_edit.php?dataID=" . $arrTmp['id'] . "\">";
            $strError .= pgDateFormat($arrTmp['from'], "d-M-y");
            if ($arrTmp['from'] != $arrTmp['thru']) {
              $strError .= " - " . pgDateFormat($arrTmp['thru'], "d-M-y");
            }
            $strError .= "</a>";
            break;
          }
        }
        // cek training
        if ($bolOK) {
          $arrTmp = isTrainingExists(
              $db,
              $strID,
              $strDataTrainingDate,
              $strDataTrainingDateThru,
              $strDataID
          ); // activity.php
          if ($arrTmp['status']) { // ada data conflic
            $bolOK = false;
            $strError = getWords("activity_conflict") . " {Training - $strEmpID} ";
            $strError .= "<a href=\"training_request_edit.php?dataID=" . $arrTmp['id'] . "\">";
            $strError .= pgDateFormat($arrTmp['from'], "d-M-y");
            if ($arrTmp['from'] != $arrTmp['thru']) {
              $strError .= " - " . pgDateFormat($arrTmp['thru'], "d-M-y");
            }
            $strError .= "</a>";
            break;
          }
        }
      }
    }
  }
  $strDataTrainingDate = validStandardDate($strDataTrainingDate) ? "'$strDataTrainingDate'" : "NULL";
  $strDataTrainingDateThru = validStandardDate($strDataTrainingDateThru) ? "'$strDataTrainingDateThru'" : "NULL";
  $strDataDate = validStandardDate($strDataDate) ? "'$strDataDate'" : "NULL";
  //$strDataTopicID = ($strDataTopicID == "") ? "NULL" : "'$strDataTopicID'";
  $strDataInstitutionID = ($strDataInstitutionID == "") ? "NULL" : "'$strDataInstitutionID'";
  // simpan data -----------------------
  if ($bolOK) { // input OK, tinggal disimpan
    // echo "aa";
    if ($strDataID == "") {
      // data baru
      $strDataID = $db->getNextID("hrd_training_request_id_seq");
      $strSQL = "
          INSERT INTO hrd_training_request (id, created,created_by,modified_by,
            id_employee, request_date, request_number,
            status, category, place, training_status , id_plan, address, 
            cost, paid_by, result, cost_other)
          VALUES('$strDataID', now(),'$strmodified_byID','$strmodified_byID',
            '$strIDEmployee', $strDataDate, '$strDataNumber',
            " . REQUEST_STATUS_NEW . ", '$strDataCategory', '$strDataPlace',
            '$strDataTrainingStatus','$strDataPlan', '$strDataAddress',
            '$strDataCost', '$strDataPaidBy', '$strDataResult', '$strDataOtherCost')
        ";
      // echo $strSQL.",".$strDataPlan;
      $resExec = $db->execute($strSQL);
    } else {
      // cek status, jika sudah approved, gak boleh diedit lagi
      // kecuali jika admin
      if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
        $strSQL = "SELECT status FROM hrd_training_request WHERE id = '$strDataID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
          if ($rowDb['status'] != REQUEST_STATUS_CHECKED) {
            $strError = $error['edit_data_denied'];
            return false;
          }
        }
      }
      $strSQL = "
          UPDATE hrd_training_request
          SET modified_by = '" . $_SESSION['sessionUserID'] . "',
            request_date = $strDataDate, id_employee = '$strIDEmployee',
            request_number = '$strDataNumber',
            category = '$strDataCategory', place = '$strDataPlace',
            training_status  = '$strDataTrainingStatus', id_plan = $strDataPlan,
            address ='$strDataAddress', cost = '$strDataCost',
            paid_by = '$strDataPaidBy', result = '$strDataResult',
            cost_other = '$strDataOtherCost'
          WHERE id = '$strDataID'
        ";
      $resExec = $db->execute($strSQL);
    }
    if ($strDataID != "") {
      // SIMPAN DATA PARTISIPAN
      $intParticipant = 0; // jumlah participant
      // simpan data employee participant
      // hapus dulu aja, biar simple
      $strSQL = "DELETE FROM hrd_training_request_participant WHERE id_request = '$strDataID' ";
      $resExec = $db->execute($strSQL);
      //$intTotal = (isset($_REQUEST['maxDetail'])) ? $_REQUEST['maxDetail'] : 0; // udah dicari di depan, jadi gak perlu lagi
      $strDataCost = 0;
      $strDataOtherCost = 0; // reset
      for ($i = 1; $i <= $intTotal; $i++) {
        if (isset($_REQUEST['detailEmployeeID' . $i])) {
          $strEmpID = $_REQUEST['detailEmployeeID' . $i];
          $strStatus = (isset($_REQUEST['detailStatus' . $i])) ? $_REQUEST['detailStatus' . $i] : 0;
          $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
          $fltCost = (isset($_REQUEST['detailAmount' . $i])) ? str_replace(",", "", $_REQUEST['detailAmount' . $i]) : 0;
          $fltCostOther = (isset($_REQUEST['detailAmountOther' . $i])) ? str_replace(
              ",",
              "",
              $_REQUEST['detailAmountOther' . $i]
          ) : 0;
          $strDataCost += $fltCost;
          $strDataOtherCost += $fltCostOther;
          // cari IDnya, kalau ada
          if ($strEmpID != "") {
            if (isset($arrEmployee[$strEmpID])) {
              if ($arrEmployee[$strEmpID] != "") {
                // simpan
                $strSQL = "
                    INSERT INTO hrd_training_request_participant (created, modified_by,
                      created_by, id_request, id_employee, status, note, cost,
                      other_cost)
                    VALUES(now(), '$strmodified_byID', '$strmodified_byID',
                      '$strDataID', '" . $arrEmployee[$strEmpID] . "', '$strStatus',
                      '$strNote', '$fltCost', '$fltCostOther')
                  ";
                $resExec = $db->execute($strSQL);
                if ($strStatus == 0) {
                  $intParticipant++;
                }
              }
            }
          }
        }
      }
      /*
      // update cost per employee, jika data ada
      if ($intParticipant > 0)
      {
        $fltAmount = $fltTotalCost / $intParticipant;
        $strSQL  = "
            UPDATE hrd_training_request_participant SET cost = '$fltAmount'
            WHERE id_request = '$strDataID' AND status = 0
        ";
        $resExec = $db->execute($strSQL);
      }
      */
      // SIMPAN DATA TRAINER, JIKA ADA
      $intTrainer = 0; // jumlah participant
      // hapus dulu aja, biar simple
      $strSQL = "DELETE FROM hrd_training_request_trainer WHERE id_request = '$strDataID' ";
      $resExec = $db->execute($strSQL);
      if ($strDataCategory == 0 || $strDataCategory == 1) {
        $intTotalTrainer = 0;
      } else {
        $intTotalTrainer = (isset($_REQUEST['numTrainerShow'])) ? $_REQUEST['numTrainerShow'] : 0;
      }
      for ($i = 1; $i <= $intTotalTrainer; $i++) {
        if (isset($_REQUEST['detailTrainerEmployeeID' . $i])) {
          $strEmpID = $_REQUEST['detailTrainerEmployeeID' . $i];
          $strStatus = (isset($_REQUEST['detailTrainerStatus' . $i])) ? $_REQUEST['detailStatus' . $i] : 0;
          // cari IDnya, kalau ada
          if ($strEmpID != "") {
            $intEmpID = $objEmp->getInfoByCode($strEmpID, "id");
            if ($intEmpID != "") {
              // simpan
              $strSQL = "
                  INSERT INTO hrd_training_request_trainer (created, modified_by,
                    created_by, id_request, id_employee, status)
                  VALUES(now(), '$strmodified_byID', '$strmodified_byID',
                    '$strDataID', '" . $intEmpID . "', '$strStatus')
                ";
              $resExec = $db->execute($strSQL);
              if ($strStatus == 0) {
                $intTrainer++;
              }
            }
          }
        }
      }
      // update cost per employee, jika data ada
      if ($intTrainer > 0) {
        $fltAmount = $fltTotalCost / $intTrainer;
        $strSQL = "
              UPDATE hrd_training_request_trainer SET cost = '$fltAmount' 
              WHERE id_request = '$strDataID' AND status = 0 
          ";
        $resExec = $db->execute($strSQL);
      }
      $strSQL = "DELETE FROM hrd_training_request_detailtime WHERE id_request = '$strDataID' ";
      $resExec = $db->execute($strSQL);
      $intTotal1 = ($_REQUEST['numShow1']) ? $_REQUEST['numShow1'] : 0; // udah dicari di depan, jadi gak perlu lagi
      $total = 0;
      $fltTotalMin = 0;
      $arrDateDetail = [];
      for ($i = 1; $i <= $intTotal1; $i++) {
        $strDataDate1 = (isset($_REQUEST['dataDateTraining' . $i])) ? $_REQUEST['dataDateTraining' . $i] : "";
        if ($strDataDate == "") {
          continue;
        }
        $strDataStart = (isset($_REQUEST['dataStart' . $i])) ? $_REQUEST['dataStart' . $i] : "";
        $strDataFinish = (isset($_REQUEST['dataFinish' . $i])) ? $_REQUEST['dataFinish' . $i] : "";
        list($firstMinute, $firstSecond) = explode(':', $strDataStart);
        list($secondMinute, $secondSecond) = explode(':', $strDataFinish);
        // hitung durasi
        $firstSecond += ($firstMinute * 60);
        $secondSecond += ($secondMinute * 60);
        $duration = $secondSecond - $firstSecond;
        $total = $duration + $total;
        $intHour = $duration / 60;
        if ($intHour < 1) {
          $intHour = 0;
        }
        $intHour = intval($intHour);
        $intMin = $duration % 60;
        $strDuration = $intHour . " : " . $intMin;
        $fltDuration = ($intHour * 60) + $intMin;
        if (!isset($arrDateDetail[$strDataDate1])) {
          $arrDateDetail[$strDataDate1] = $fltDuration;
          $fltTotalMin += $fltDuration;
        }
        // save detail time
        if ($strDataDate1 != "") {
          // simpan
          $strSQL = "
                INSERT INTO hrd_training_request_detailtime (currdate, updater, 
                  creator, id_request, timestart, timefinish, duration, trainingdate) 
                VALUES(now(), '$strmodified_byID', '$strmodified_byID', 
                  '$strDataID','$strDataStart', '$strDataFinish', '$strDuration', '$strDataDate1') 
            ";
          //$strSQL .= "'$strDataID', '" .$rowT['id']."') ";
          $resExec = $db->execute($strSQL);
        } else {
          $strSQL = "
                INSERT INTO hrd_training_request_detailtime (currdate, updater, 
                  creator, id_request, timestart, timefinish, duration, trainingdate) 
                VALUES(null,null,null, 
                  null,null, null, null,null) 
            ";
          //$strSQL .= "'$strDataID', '" .$rowT['id']."') ";
          $resExec = $db->execute($strSQL);
        }
      }
      $fltDuration = count($arrDateDetail);
      // update data durasi dan biaya
      $strSQL = "
          UPDATE hrd_training_request SET total_duration = '$fltDuration',
            total_hour = '$fltTotalMin', cost = '$strDataCost',
            cost_other = '$strDataOtherCost'
          WHERE id = '$strDataID';
        ";
      $resExec = $db->execute($strSQL);
    }
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "MUTATION DATA", 0);
    $strError = $messages['data_saved'];
  } else { // ---- data SALAH
    // gunakan data yang diisikan tadi
    $arrData['dataDepartment'] = $strDataDepartment;
    $arrData['dataDate'] = $strDataDate;
    $arrData['dataTrainingDate'] = $strDataTrainingDate;
    $arrData['dataTrainingDateThru'] = $strDataTrainingDateThru;
    $arrData['dataNumber'] = $strDataNumber;
    $arrData['dataEmployee'] = $strDataEmployee;
    $arrData['dataTopic'] = $strDataTopic;
    $arrData['dataNote'] = $strDataNote;
    $arrData['dataTrainer'] = $strDataTrainer;
    $arrData['dataInstitution'] = "";
    $arrData['dataResult'] = $strDataResult;
    $arrData['dataInstitutionID'] = "";
    $arrData['dataCost'] = $strDataCost;
    $arrData['dataPaidBy'] = $strDataPaidBy;
    $arrData['dataTrainingType'] = $strDataType;
    $arrData['dataID'] = $strDataID;
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "ABSENCE DATA", 0);
  }
  return $bolOK;
} // saveData
// fungsi untuk ambil nomor terakhir
function getLastTrainingNumber($db, $strYear)
{
  $strResult = "";
  $strSQL = "SELECT MAX(request_number) AS no FROM hrd_training_request ";
  $strSQL .= "WHERE EXTRACT(year FROM request_date) = '$strYear' ";
  $strSQL .= "AND request_number ~ '^[0-9]+$' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strResult = $rowDb['no'];
  }
  return $strResult;
}//getLastTrainingNumber
// untuk menampilkan link untuk print MRF
function printLink($params)
{
  extract($params);
  $strResult = "<a href=\"javascript:openWindowDialog('templates/training_request_print.html?dataID=" . $record['id'] . "');\">" . getWords(
          "print"
      ) . "</a>";
  return $strResult;
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  if (isset($_POST['getPlanID'])) {
    //$db = new CdbClass;
    $strSQL = "SELECT t1.competency,t1.domain,t1.training_type, t1.id as id_plan, t2.name_vendor,t3.name_instructor,t4.division_code,t4.division_name,
					t5.department_code,t5.department_name,t6.section_code,t6.section_name,t7.sub_section_code,t7.sub_section_name
					FROM hrd_training_plan AS t1 
					LEFT JOIN hrd_training_vendor AS t2 ON t1.id_training_vendor = t2.id 
					LEFT JOIN hrd_training_instructor AS t3 ON t1.id_instructor = t3.id 
					LEFT JOIN hrd_division AS t4 ON t1.division_code = t4.division_code
					LEFT JOIN hrd_department AS t5 ON t1.department_code = t5.department_code
					LEFT JOIN hrd_section AS t6 ON t1.section_code = t6.section_code
					LEFT JOIN hrd_sub_section AS t7 ON t1.sub_section_code = t7.sub_section_code
					WHERE t1.id = " . $_POST["getPlanID"] . " ";
    $resDb = $db->execute($strSQL);
    $rowDb = $db->fetchrow($resDb);
    $dataPlanID = $rowDb['id_plan'];
    $dataTrainingType = "[" . $rowDb['competency'] . "][" . $rowDb['domain'] . "][" . $rowDb['training_type'] . "]";
    $dataTrainingDivision = $rowDb['division_code'];
    $dataTrainingDepartment = $rowDb['department_code'];
    $dataTrainingSection = $rowDb['section_code'];
    $dataTrainingSubSection = $rowDb['sub_section_code'];
    $dataTrainingInstitution = $rowDb['name_vendor'];
    $dataTrainingInstructor = $rowDb['name_instructor'];
    $data = $dataPlanID . "/" . $dataTrainingType . "/" . $dataTrainingDivision . "/" . $dataTrainingDepartment . "/" . $dataTrainingSection . "/" . $dataTrainingSubSection . "/" . $dataTrainingInstitution . "/" . $dataTrainingInstructor;
    echo $data;
    die();
  }
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  $dtNow = getdate();
  $strUserRole = $_SESSION['sessionUserRole'];
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
    //$strInputType = getTrainingType($db, $strDataID);
    //Edit: hilangkan participant
    $strInputParticipant = getTrainingParticipant($db, $strDataID);
    //$strInputParticipant = "";
    $strInputTrainerMore = "";//getTrainingTrainer($db, $strDataID);
    //$strInputLastNumber .= getLastTrainingNumber($db);
    if ($strDataID == "") {
      $strInputLastNumber .= getLastTrainingNumber($db, pgDateFormat($arrData['dataDate'], "Y"));
      if (!is_numeric($strInputLastNumber)) {
        $strInputLastNumber = 0;
      } else {
        $strInputLastNumber = (int)$strInputLastNumber;
      }
      $strInputLastNumber++;
      $arrData['dataNumber'] = addPrevZero($strInputLastNumber, 5);
    }
  } else {
    showError("view_denied");
  }
  //----- TAMPILKAN DATA ---------
  $strDefaultWidthPx = 250;
  $strDefaultWidthPx1 = 100;
  $strDisabled = "";
  $strInputDate = "<input type=text size=15 maxlength=10 name=dataDate id=dataDate value=\"" . $arrData['dataDate'] . "\" readonly class='date-empty'>";
  $strInputDate .= " <input name=\"btnDate\" type=button id=\"btnDate\" value='..' $strDisabled>";
  $strInitActions .= "Calendar.setup({ inputField:\"dataDate\", button:\"btnDate\" }) \n";
  $strInputTrainingDate = "<input type=text size=15 maxlength=10 name=dataTrainingDate id=dataTrainingDate value=\"" . $arrData['dataTrainingDate'] . "\" class='date'>";
  $strInputTrainingDateThru = "<input type=text size=15 maxlength=10 name=dataTrainingDateThru id=dataTrainingDateThru value=\"" . $arrData['dataTrainingDateThru'] . "\"  class='date'>";
  $strInputDuration = $arrData['dataDuration'];
  $strInputTimeDetail = getTimeDetail($db, $strDataID, $arrData, $strInputDuration);
  $strInputNumber = "<input type=text name=dataNumber size=50 maxlength=50 value=\"" . $arrData['dataNumber'] . "\" style=\"width:$strDefaultWidthPx\"  class='string'>";
  $strAction = "onFocus = \"AC_kode = 'dataEmployee';AC_nama = 'employee_name'; \";";
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=50 maxlength=20 value=\"" . $arrData['dataEmployee'] . "\" style=\"width:$strDefaultWidthPx\" $strAction>";
  // print_r($arrData);
  if ($arrData['dataPlan'] != "") {
    $strInputTopic = "<input type=text name='dataTopic' size=50 maxlength=50 value=\"" . $arrData['dataTopic'] . "\" style=\"width:$strDefaultWidthPx\" disabled>";
    // $strInputTopic .= "<input type=hidden name='dataTopic' maxlength=50 value=\"" .$arrData['dataTopic']. "\">";
    $strInputDataPlan = "<input type=hidden name='dataPlan' maxlength=50 value=\"" . $arrData['dataPlan'] . "\">";
    $strInputInstitution = getDataTrainingInstitution(
        $db,
        "dataInstitutionID",
        $arrData['dataInstitutionID'],
        "text",
        "",
        " style=\"width:$strDefaultWidthPx \" disabled"
    );
    // if (!isset($strInputInstitution)) $strInputInstitution .= "<input type=hidden name='dataInstitutionID' maxlength=50 value=\"" .$arrData['dataInstitutionID']. "\">"; else $strInputInstitution .= "";
    $strInputTrainer = getDataTrainingInstructor(
        $db,
        "dataInstructorID",
        $arrData['dataInstructorID'],
        "text",
        "",
        " style=\"width:$strDefaultWidthPx \" disabled"
    );
    // if (!isset($strInputTrainer)) $strInputTrainer .= getDataTrainingInstructor($db,"dataTrainer",$arrData['dataInstructorID'], "hidden", "", " style=\"width:$strDefaultWidthPx \""); else $strInputTrainer .= "";
    $strInputType = "<input type=text name=dataTrainingType value='[" . $arrData['dataTrainingCompetency'] . "][" . $arrData['dataTrainingDomain'] . "][" . $arrData['dataTrainingType'] . "]' style=\"width:$strDefaultWidthPx \" disabled><input type=hidden name=dataTrainingType value='[" . $arrData['dataTrainingCompetency'] . "][" . $arrData['dataTrainingDomain'] . "][" . $arrData['dataTrainingType'] . "]' style=\"width:$strDefaultWidthPx \">";
    $strInputDivision = getDataDivision(
        $db,
        "dataDivision",
        $arrData['dataDivision'],
        "text",
        "",
        " style=\"width:$strDefaultWidthPx \" disabled"
    );
    // if (!isset($strInputDivision)) $strInputDivision .= getDataDivision($db,"dataDivision",$arrData['dataDivision'], "hidden", "", " style=\"width:$strDefaultWidthPx \""); else $strInputDivision .= "";
    $strInputDepartment = getDataDepartment(
        $db,
        "dataDepartment",
        $arrData['dataDepartment'],
        "text",
        "",
        " style=\"width:$strDefaultWidthPx \" disabled"
    );
    // if (!isset($strInputDepartment)) $strInputDepartment .= "<input type=hidden name='dataDepartment' maxlength=50 value=\"" .$arrData['dataDepartment']. "\">"; else $strInputDepartment .= "";
    $strInputSection = getDataSection(
        $db,
        "dataSection",
        $arrData['dataSection'],
        "text",
        "",
        " style=\"width:$strDefaultWidthPx \" disabled"
    );
    // if (!isset($strInputSection)) $strInputSection .= getDataSection($db,"dataSection",$arrData['dataSection'], "hidden", "", " style=\"width:$strDefaultWidthPx \""); else $strInputSection .= "";
    $strInputSubSection = getDataSubSection(
        $db,
        "dataSubSection",
        $arrData['dataSubSection'],
        "text",
        "",
        " style=\"width:$strDefaultWidthPx \" disabled"
    );
    // if (!isset($strInputSubSection)) $strInputSubSection .= getDataSubSection($db,"dataSubSection",$arrData['dataSubSection'], "hidden", "", " style=\"width:$strDefaultWidthPx \""); else $strInputSubSection .= "";
  } else {
    $strInputTopic = getTrainingTopicList(
        $db,
        "dataPlanID",
        $arrData['dataPlan'],
        "text",
        "WHERE status = 2",
        " style=\"width:$strDefaultWidthPx \" $strReadonly"
    );
    $strInputDataPlan = "<input type=hidden id='dataPlan' maxlength=50  name='dataPlan'>";
    $strInputInstitution = "<input type=text id='dataInstitution'  style=\"width:$strDefaultWidthPx \" disabled>";
    $strInputTrainer = "<input type=text id='dataTrainer'  style=\"width:$strDefaultWidthPx \" disabled>";
    $strInputType = "<input type=text id='dataType'  style=\"width:$strDefaultWidthPx \" disabled>";
    $strInputDivision = "<input type=text id='dataDivision'  style=\"width:$strDefaultWidthPx \" disabled>";
    $strInputDepartment = "<input type=text id='dataDepartment'  style=\"width:$strDefaultWidthPx \" disabled>";
    $strInputSection = "<input type=text id='dataSection'  style=\"width:$strDefaultWidthPx \" disabled>";
    $strInputSubSection = "<input type=text id='dataSubSection'  style=\"width:$strDefaultWidthPx \" disabled>";
  }
  $strInputAddress = "<input type=text name=dataAddress id=dataAddress size=50 value=\"" . $arrData['dataAddress'] . "\" style=\"width:$strDefaultWidthPx\">";
  $strInputCost = "<input type=text name='dataCost' id='dataCost' size=50 maxlength=50 value=\"" . $arrData['dataCost'] . "\" style=\"width:$strDefaultWidthPx\" class='numberformat numeric'>";
  $strInputOtherCost = "<input type=text name='dataCostOther' id='dataCostOther' size=50 maxlength=50 value=\"" . $arrData['dataCostOther'] . "\" style=\"width:$strDefaultWidthPx\" class='numberformat numeric'>";
  $strInputNote = "<textarea name=dataNote id=dataNote cols=30 rows=3 wrap='virtual' style=\"width:$strDefaultWidthPx\">" . $arrData['dataNote'] . "</textarea>";
  $strInputResult = "<textarea id='dataResult' name='dataResult' cols=30 rows=3 wrap='virtual' style=\"width:$strDefaultWidthPx\">" . $arrData['dataResult'] . "</textarea>";
  $strInputTrainingSyllabus = "<input name=\"dataTrainingSyllabus\" type=\"file\" id=\"dataTrainingSyllabus\" size=\"42\" >";
  $strPlanKriteria = "year = '" . date("Y") . "' ";
  if ($arrUserInfo['department_code'] != "" && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strPlanKriteria .= "AND department_code = '" . $arrUserInfo['department_code'] . "' ";
  }
  if ($arrData['dataPlan'] != "") {
    $strPlanKriteria = "WHERE 1=1 AND (id = '" . $arrData['dataPlan'] . "' OR ($strPlanKriteria)) ";
  } else {
    $strPlanKriteria = "WHERE 1=1 AND $strPlanKriteria ";
  }
  $arrTmp = ["Public", "External", "Internal", "Sharing Session"];
  $strInputCategory = getComboFromArray(
      $arrTmp,
      "dataCategory",
      $arrData['dataCategory'],
      "",
      " style=\"width:$strDefaultWidthPx \" "
  );
  $arrTmp = ["Domestic", "Foreign"];
  $strInputPlace = getComboFromArray(
      $arrTmp,
      "dataPlace",
      $arrData['dataPlace'],
      "",
      " style=\"width:$strDefaultWidthPx \" "
  );
  $arrTmp = ["OK", "Pending", "Cancel"];
  $strInputTrainingStatus = getComboFromArray(
      $arrTmp,
      "dataTrainingStatus",
      $arrData['dataTrainingStatus'],
      "",
      " style=\"width:$strDefaultWidthPx \" "
  );
  $arrTmp = ["Employee", "Company", "Free"];
  $strInputPaidBy = getComboFromArray(
      $arrTmp,
      "dataPaidBy",
      $arrData['dataPaidBy'],
      "",
      " style=\"width:$strDefaultWidthPx \" "
  );
  $strInputStatus = getWords($ARRAY_REQUEST_STATUS[$arrData['dataStatus']]);
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
//DELETE FILE - untuk syllabus
function deleteFile($db, $strDetailID = "")
{
  global $words;
  $bolNewData = true;
  if ($strDetailID != "") {
    $strSQL = "SELECT * FROM hrd_training_request ";
    $strSQL .= "WHERE id = '$strDetailID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strFile = $rowDb['doc'];
      if ($strFile != "") {
        if (file_exists("trainingdoc/" . $strFile)) {
          unlink("trainingdoc/" . $strFile);
        }
        $strSQL = "UPDATE hrd_training_request SET doc = '' WHERE id = '$strDetailID' ";
        $resExec = $db->execute($strSQL);
        //writeLog(ACTIVITY_DELETE, MODULE_PAYROLL,"file $strDetailID",0);
      }
    }
  }
  return true;
} // delete syllabus
//----------------------------------------------------------------------
?>