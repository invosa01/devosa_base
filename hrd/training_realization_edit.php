<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
include_once('cls_employee.php');
// include_once('../global/employee_function.php');
//include_once(getTemplate("words.inc"));
$dataPrivilege = getDataPrivileges(
    "training_realization_list.php",
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
$strWordsInputTrainingRequest = getWords("input training request");
$strWordsTrainingRequestList = getWords("training request list");
$strWordsApprovedTraining = getWords("approved training");
$strWordsTrainingReport = getWords("training report");
$strWordsDepartment = getWords("department");
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
$strWordsExpense = getWords("training expense");
$strWordsDate = getWords("request date");
$strWordsCost = getWords("training cost");
$strWordsOtherCost = getWords("other cost");
$strWordsPaidBy = getWords("paid by");
$strWordsSave = getWords("save");
$strWordsClearForm = getWords("clear form");
$strWordsBack = getWords("back");
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
$strShowTrainer = "";
$strInputExpense = "";
$arrData = [
    "dataDate"             => $strNow,
    "dataTrainingDate"     => "",
    "dataTrainingDateThru" => "",
    "dataDepartment"       => "",
    "dataEmployeeID"       => "", // NIK Partisipan
    "dataEmployeeName"     => "", // Nama Partisipan
    "dataNumber"           => "",
    "dataDuration"         => "0",
    "dataDurationMin"      => "0", // durasi, dalam menit
    "dataFinish"           => "00:00",
    "dataStart"            => "00:00",
    "dataTopic"            => "", // diabaikan
    "dataTopicID"          => "",
    "dataNote"             => "",
    "dataTrainer"          => "",
    "dataInstitution"      => "", // diabaikan
    "dataInstitutionID"    => "",
    "dataType"             => "", // jenis
    "dataParticipant"      => "", // daftar
    "dataTrainerMore"      => "", // daftar
    "dataExpense"          => "", // daftar
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
];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataRequestID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db)
{
  global $words;
  global $arrData;
  global $strDataRequestID;
  global $_REQUEST;
  if ($strDataRequestID != "") {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "ID=$strDataRequestID", 0);
    $strSQL = "
        SELECT t1.*, t2.topic AS training_topic,
           t3.name_vendor
        FROM hrd_training_request AS t1 
        LEFT JOIN hrd_training_topic AS t2 ON t1.id_topic = t2.id
        LEFT JOIN hrd_training_vendor AS t3 ON t1.id_institution = t3.id
        WHERE t1.id = '$strDataRequestID'
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
      $arrData['dataTopic'] = $rowDb['training_topic'];
      $arrData['dataTopicID'] = $rowDb['id_topic'];
      $arrData['dataDepartment'] = $rowDb['department_code'];
      $arrData['dataNumber'] = $rowDb['request_number'];
      $arrData['dataTrainer'] = $rowDb['trainer'];
      $arrData['dataInstitution'] = $rowDb['name_vendor'];
      $arrData['dataInstitutionID'] = $rowDb['id_institution'];
      $arrData['dataID'] = $strDataRequestID;
      $arrData['dataAddress'] = $rowDb['address'];
      $arrData['dataType'] = $rowDb['training_type'];
      $arrData['dataPaidBy'] = $rowDb['paid_by'];
      $arrData['dataResult'] = $rowDb['result'];
      $arrData['dataDuration'] = $rowDb['total_duration'];
      $arrData['dataDurationMin'] = $rowDb['total_hour'];
      //$arrData['dataCostOther'] = $rowDb['cost_other'];
      //$arrData['dataCost'] = $rowDb['cost'];
    }
  } else {
    return false;
  }
  return true;
} // showData
// fungsi untuk menampilkan combobox daftar mata uang, sementara hardcode dulu
function getCurrencyList($strName, $strDefault = "IDR")
{
  $strResult = "";
  // daftar mata uang, hardcode
  $arrCurrCode = [
      "AUD" => "AUD",
      "CNY" => "CNY",
      "DM"  => "DM",
      "EUR" => "EUR",
      "HKD" => "HKD",
      "GBP" => "GBP",
      "IDR" => "IDR",
      "JPY" => "JPY",
      "KRW" => "KRW",
      "MYR" => "MYR",
      "NLG" => "NLG",
      "RMB" => "RMB",
      "SGD" => "SGD",
      "THB" => "THB",
      "TWD" => "TWD",
      "USD" => "USD",
  ];
  if ($strDefault == "") {
    $strDefault = "IDR";
  }
  $bolOK = false;
  $strResult .= "<select name='$strName' id='$strName'>\n";
  foreach ($arrCurrCode AS $str => $code) {
    if ($str == $strDefault) {
      $strSel = "selected";
      $bolOK = true;
    } else {
      $strSel = "";
    }
    $strResult .= "<option value='$str' " . (($str == $strDefault) ? "selected" : "") . ">$str</option>\n";
  }
  if (!$bolOK && $strDefault != "") {
    $strResult .= "<option value='$strDefault' selected>$strDefault</option>";
  }
  $strResult .= "</select>";
  return $strResult;
}

// fungsi mengambil daftar biaya selama training
function getTrainingExpense($db, $strDataRequestID = "", $strDataEmployeeID = "")
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
  $strResult .= " <table border=0 class=gridTable cellpadding=0 cellspacing=0 >\n";
  $strResult .= "  <tr valign=top class=tableHeader>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('no') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('curr.') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('note') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('description') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('amount') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('delete') . "</td>\n";
  $strResult .= "  </tr>\n";
  $arrExpense = [];
  if ($strDataRequestID != "" && $strDataEmployeeID != "") {
    $strSQL = "
          SELECT *
          FROM hrd_training_realization_expense
          WHERE id_request = '$strDataRequestID'
            AND id_employee = '$strDataEmployeeID'
      ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrExpense[$rowDb['id']] = $rowDb;
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  foreach ($arrExpense AS $id => $rowDb) {
    $intRows++;
    $intShown++;
    $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
    $strResult .= "  <td align=right nowrap><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">$intRows&nbsp;</td>";
    $strResult .= "  <td>" . getCurrencyList("detailCurr$intRows", $rowDb['currency_code']) . "</td>";
    $strResult .= "  <td><input type=text size=20 name=detailNote$intRows id=detailNote$intRows value=\"" . $rowDb['note'] . "\"></td>";
    $strResult .= "  <td><input type=text size=20 name=detailDesc$intRows id=detailDesc$intRows value=\"" . $rowDb['description'] . "\"></td>";
    $strResult .= "  <td><input type=text size=10 name=detailAmount$intRows id=detailAmount$intRows value=\"" . $rowDb['amount'] . "\" class='numberformat numeric'></td>";
    $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center ><input type=checkbox id='chkID$intRows' name='chkID$intRows' $strAction></td>\n";
    $strResult .= "</tr>\n";
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
    $strResult .= "  <td>" . getCurrencyList("detailCurr$intRows") . "</td>";
    $strResult .= "  <td><input type=text size=20 name=detailNote$intRows id=detailNote$intRows $strDisabled></td>";
    $strResult .= "  <td><input type=text size=20 name=detailDesc$intRows id=detailDesc$intRows ></td>";
    $strResult .= "  <td><input type=text size=10 name=detailAmount$intRows id=detailAmount$intRows value=0 class='numberformat numeric'></td>";
    $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center><input type=checkbox id='chkID$intRows' name='chkID$intRows' $strAction></td>\n";
    $strResult .= "</tr>\n";
    $strTargetElements .= ",\"detailEmployeeID$intRows\"";
  }
  $strResult .= " <tr valign=top><td>&nbsp;</td>\n";
  $strResult .= "  <td colspan=4>&nbsp;<a href=\"javascript:showMoreInput();\">" . getWords(
          'more'
      ) . "</a></td></tr>\n";
  $strResult .= "  </table>\n";
  // tambahkan hidden data
  $strResult .= "<input type=hidden id='maxDetail' name='maxDetail' value=$intRows>";
  $strResult .= "<input type=hidden id='numShow' name='numShow' value=$intShown>";
  return $strResult;
} // getTrainingExpense
// fungsi mengambil daftar trainer jika berasal dari karyawan
function getTrainingTrainer($db, $strDataRequestID = "")
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
  $strResult .= "  </tr>\n";
  $arrParticipant = [];
  if ($strDataRequestID != "") {
    $strSQL = "
          SELECT t1.id, t1.status, t1.cost, t2.employee_id, t2.employee_name 
          FROM hrd_training_request_trainer AS t1 
          LEFT JOIN hrd_employee AS t2 ON t2.id = t1.id_employee 
          WHERE t1.id_request = '$strDataRequestID'
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
    $strResult .= "<tr valign=top  id=\"detailTrainerRows$intRows\">\n";
    $strResult .= "  <td align=right nowrap><input type=hidden name=detailTrainerID$intRows value=\"" . $rowDb['id'] . "\">$intRows&nbsp;</td>";
    $strResult .= "  <td>&nbsp;" . $rowDb['employee_id'] . "</td>";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['employee_name'] . "</td>";
    $strResult .= "</tr>\n";
  }
  $strResult .= "  </table>\n";
  return $strResult;
} // getTrainingTrainer
// menampilkan bagian detail waktu pelaksanaan training
function getTimeDetail($db, $strDataRequestID = "", $strDataEmployeeID = "", $arrData = [], $strInputDuration = "")
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
  if ($strDataRequestID != "" && $strDataEmployeeID != "") {
    $strSQL = "
          SELECT *
          FROM hrd_training_realization
          WHERE id_request = '$strDataRequestID'
            AND id_employee = '$strDataEmployeeID'
      ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrtime[$rowDb['id']] = $rowDb;
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  // ambil default jika tidak ada data
  $bolFound = (count($arrtime) > 0);
  $arrTimeTraining = [];
  if (!$bolFound) {
    $strSQL = "
          SELECT id, timestart, timefinish, duration, trainingdate 
          FROM hrd_training_request_detailtime
          WHERE id_request = '$strDataRequestID'
      ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrTimeTraining[$rowDb['id']] = $rowDb;
    }
  }
  $strResult .= " <table border=0 class=gridTable cellpadding=0 cellspacing=0 >\n";
  $strResult .= "  <tr valign=top class=tableHeader>\n";
  $strResult .= "  <td nowrap >&nbsp;" . getWords("no.") . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords("date") . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords("start time") . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords("finish time") . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords("duration") . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords("note") . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords("delete") . "</td>\n";
  $strResult .= "  </tr>\n";
  $arrTime = ($bolFound) ? $arrtime : $arrTimeTraining;
  foreach ($arrTime AS $id => $rowDb) {
    $intRows++;
    $intShown++;
    $dataDate = $rowDb['trainingdate'];
    $duration = $rowDb['duration'];
    $strDetailID = ($bolFound) ? $id : "";
    $strDetailNote = ($bolFound) ? $rowDb['note'] : "";
    $strResult .= "<tr valign=top  id=\"detailRowsA$intRows\">\n";
    $strResult .= "<td align=right nowrap><input type=hidden name=detailIDA$intRows id=detailIDA$intRows  value=\"" . $strDetailID . "\" >$intRows&nbsp;</td>";
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
    $strResult .= "  <td align=center><input type=text id='dataNote$intRows' name='dataNote$intRows' size='50' value=\"" . $strDetailNote . "\"></td>\n";
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
    $strResult .= "  <td align=center><input type=text id='dataNote$intRows' name='dataNote$intRows' size=50></td>\n";
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
  global $strDataRequestID;
  global $strDataEmployeeID;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strError = "";
  $bolOK = true;
  $strToday = date("Y-m-d");
  // cek validasi -----------------------
  // simpan data -----------------------
  if ($bolOK) {
    if ($strDataRequestID != "") {
      // SIMPAN DATA PARTISIPAN
      $intExpense = 0; // jumlah participant
      // simpan data employee participant
      // hapus dulu aja, biar simple
      $strSQL = "
          DELETE FROM hrd_training_realization_expense
          WHERE id_request = '$strDataRequestID'
            AND id_employee = '$strDataEmployeeID'
        ";
      $resExec = $db->execute($strSQL);
      $intTotal = (isset($_REQUEST['maxDetail'])) ? $_REQUEST['maxDetail'] : 0;
      $fltTotalCost = 0;
      for ($i = 1; $i <= $intTotal; $i++) {
        if (isset($_REQUEST['detailNote' . $i])) {
          $fltAmount = (isset($_REQUEST['detailAmount' . $i])) ? str_replace(
              ",",
              "",
              $_REQUEST['detailAmount' . $i]
          ) : 0;
          $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
          $strDesc = (isset($_REQUEST['detailDesc' . $i])) ? $_REQUEST['detailDesc' . $i] : "";
          $strCurr = (isset($_REQUEST['detailCurr' . $i])) ? $_REQUEST['detailCurr' . $i] : "";
          // cari IDnya, kalau ada
          if ($strNote != "" && is_numeric($fltAmount)) {
            $fltTotalCost += $fltAmount;
            // simpan
            $strSQL = "
                INSERT INTO hrd_training_realization_expense (
                  updater, id_request, id_employee, currency_code, note,
                  description, amount)
                VALUES('$strmodified_byID',
                  '$strDataRequestID', '" . $strDataEmployeeID . "', '$strCurr',
                  '$strNote', '$strDesc', '$fltAmount')
              ";
            $resExec = $db->execute($strSQL);
          }
        }
      }
      // update cost per employee, jika data ada
      /*
      if (!is_numeric($fltTotalCost)) $fltTotalCost = 0;
      $strSQL  = "
          UPDATE hrd_training_request_participant SET cost = '$fltTotalCost'
          WHERE id_request = '$strDataRequestID' AND id_employee = '$strDataEmployeeID'
      ";
      $resExec = $db->execute($strSQL);
      */
      $strSQL = "
          DELETE FROM hrd_training_realization
          WHERE id_request = '$strDataRequestID'
            AND id_employee = '$strDataEmployeeID'
        ";
      $resExec = $db->execute($strSQL);
      $intTotal1 = ($_REQUEST['numShow1']) ? $_REQUEST['numShow1'] : 0; // udah dicari di depan, jadi gak perlu lagi
      $total = 0;
      $fltTotalMin = 0;
      $arrDateDetail = [];
      for ($i = 1; $i <= $intTotal1; $i++) {
        $strDataDate1 = (isset($_REQUEST['dataDateTraining' . $i])) ? $_REQUEST['dataDateTraining' . $i] : "";
        if ($strDataDate1 == "") {
          continue;
        }
        $strDataStart = (isset($_REQUEST['dataStart' . $i])) ? $_REQUEST['dataStart' . $i] : "";
        $strDataFinish = (isset($_REQUEST['dataFinish' . $i])) ? $_REQUEST['dataFinish' . $i] : "";
        $strDataNote = (isset($_REQUEST['dataNote' . $i])) ? $_REQUEST['dataNote' . $i] : "";
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
                INSERT INTO hrd_training_realization (currdate, updater, creator,
                  id_request, id_employee, timestart, timefinish, duration,
                  trainingdate, note)
                VALUES(now(), '$strmodified_byID', '$strmodified_byID', 
                  '$strDataRequestID', '$strDataEmployeeID', '$strDataStart', '$strDataFinish',
                  '$strDuration', '$strDataDate1', '$strDataNote')
            ";
          $resExec = $db->execute($strSQL);
        }
      }
      $fltDuration = count($arrDateDetail);
      // update data durasi
      /*
      $strSQL = "
        UPDATE hrd_training_request SET total_duration = '$fltDuration',
          total_hour = '$fltTotalMin'
        WHERE id = '$strDataRequestID';
      ";
      $resExec = $db->execute($strSQL);
      */
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
    $arrData['dataTopicID'] = $strDataTopicID;
    $arrData['dataNote'] = $strDataNote;
    $arrData['dataTrainer'] = $strDataTrainer;
    $arrData['dataInstitution'] = "";
    $arrData['dataResult'] = $strDataResult;
    $arrData['dataInstitutionID'] = "";
    $arrData['dataCost'] = $strDataCost;
    $arrData['dataPaidBy'] = $strDataPaidBy;
    $arrData['dataType'] = $strDataType;
    $arrData['dataID'] = $strDataRequestID;
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "ABSENCE DATA", 0);
  }
  return $bolOK;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  $dtNow = getdate();
  $strUserRole = $_SESSION['sessionUserRole'];
  if ($arrUserInfo['isGroupHead'] || $arrUserInfo['isDeptHead']) {
    $bolCanView = $bolCanDelete = $bolCanEdit = true;
  }
  $strDataRequestID = (isset($_REQUEST['dataRequestID'])) ? $_REQUEST['dataRequestID'] : "";
  $strDataEmployeeID = (isset($_REQUEST['dataEmployeeID'])) ? $_REQUEST['dataEmployeeID'] : "";
  if ($strDataRequestID == "" || $strDataEmployeeID == "") {
    echo "<script>location.href='training_realization_list.php'</script>";
    exit();
  }
  if ($bolCanEdit) {
    if (isset($_POST['btnSave'])) {
      $bolOK = saveData($db, $strError);
      if ($strError != "") {
        //echo "<script>alert(\"$strError\");/script>";
        $strMessages = $strError;
        $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
      }
      //$strDataRequestID = ""; // biar tidak mengambil dta, melainkan pakai data baru atau data yang dikirim (jika error)
    }
  }
  $objEmpl = new clsEmployee($db);
  $objEmpl->loadDataById($strDataEmployeeID);
  $arrData['dataEmployeeID'] = $objEmpl->getInfo("employee_id");
  $arrData['dataEmployeeName'] = $objEmpl->getInfo("employee_name");
  if ($bolCanView) {
    getData($db);
    //$strInputType = getTrainingType($db, $strDataRequestID);
    $strInputExpense = getTrainingExpense($db, $strDataRequestID, $strDataEmployeeID);
    $strInputTrainerMore = getTrainingTrainer($db, $strDataRequestID);
    //$strInputLastNumber .= getLastTrainingNumber($db);
    if ($strDataRequestID == "") {
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
  $strReadonly = ($arrData['dataStatus'] == 0 || $_SESSION['sessionUserRole'] == ROLE_ADMIN) ? "" : "readonly"; // kalau dah approve, jadi readonly
  $strEmptyOptions = "<option value=''> </option>";
  $strReadonlyRequest = "";
  $strReadonlyEmployee = "";
  $strDisabled = "";
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strReadonlyRequest = "readonly";
    $strReadonlyEmployee = "readonly";
    $strDisabled = "disabled";
  }; // employee gak bisa ubah tanggal request
  $strInputParticipant = $arrData['dataEmployeeID'] . " - " . $arrData['dataEmployeeName'];
  $strInputDate = $arrData['dataDate'] . "";
  $strInputTrainingDate = pgDateFormat($arrData['dataTrainingDate'], "d M Y");
  $strInputDuration = $arrData['dataDuration'];
  $strInputTrainer = $arrData['dataTrainer'] . "";
  $strInputTimeDetail = getTimeDetail($db, $strDataRequestID, $strDataEmployeeID, $arrData, $strInputDuration);
  $strInputNumber = $arrData['dataNumber'] . "";
  $strInputTopic = $arrData['dataTopic'] . "";
  $strInputInstitution = $arrData['dataInstitution'] . "";
  $strInputType = $arrData['dataType'] . "";
  $strInputAddress = nl2br($arrData['dataAddress']) . "";
  $strInputCost = standardFormat($arrData['dataCost']) . "";
  $strInputOtherCost = standardFormat($arrData['dataCostOther']) . "";
  $strInputNote = nl2br($arrData['dataNote']) . "";
  $strInputResult = nl2br($arrData['dataResult']) . "";
  $arrTmp = ["public", "inhouse", "internal", "sharing session"];
  $strInputCategory = (isset($arrTmp[$arrData['dataCategory']])) ? getWords($arrTmp[$arrData['dataCategory']]) : "";
  $arrTmp = ["domestic", "foreign"];
  $strInputPlace = (isset($arrTmp[$arrData['dataPlace']])) ? getWords($arrTmp[$arrData['dataPlace']]) : "";
  $strShowTrainer = ($arrData['dataCategory'] == 0 || $arrData['dataCategory'] == 1) ? " style=\"display:none\" " : "";
  // $arrTmp = array("ok", "pending", "cancel");
  // $strInputTrainingStatus = getComboFromArray($arrTmp, "dataTrainingStatus", $arrData['dataTrainingStatus'], "", " style=\"width:$strDefaultWidthPx \" ");
  $arrTmp = ["company", "employee"];
  $strInputPaidBy = (isset($arrTmp[$arrData['dataPaidBy']])) ? getWords($arrTmp[$arrData['dataPaidBy']]) : "";
  $strInputStatus = getWords($ARRAY_REQUEST_STATUS[$arrData['dataStatus']]);
  if (!$bolCanEdit) {
    $strDisabledSave = "disabled ";
  } else if ($objUP->isUserEmployee()) {
    if ($arrData['dataStatus'] >= REQUEST_STATUS_APPROVED) {
      $strDisabledSave = "disabled";
    }
  }
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