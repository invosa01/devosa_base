<?php
include_once('../global/session.php');
include_once('global.php');
$dataPrivilege = getDataPrivileges(
    "employee_edit.php",
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
//---- INISIALISASI ----------------------------------------------------
$strWordsOVERTIMESETTING = getWords("overtime setting");
$strWordsCODE = getWords("code");
$strWordsOT1 = getWords("o.t 1");
$strWordsOT2 = getWords("o.t 2");
$strWordsOT3 = getWords("o.t 3");
$strWordsOT4 = getWords("o.t 4");
$strWordsSCALE = getWords("scale");
$strWordsNOTE = getWords("note");
$strWordsOVERTIMECOMPENSATIONSETTING = getWords("overtime compensation setting");
$strWordsSTATUS = getWords("status");
$strWordsMEAL = getWords("meal");
$strWordsNORMALDAY = getWords("normal day");
$strWordsStandartAlasanLembur = getWords("standart alasan lembur");
$strWordsCODE = getWords("code");
$strWordsKETERANGAN = getWords("keterangan");
$strWordsHOLIDAY = getWords("holiday");
$strWordsTRANSPORT = getWords("transport");
$strWordsPermanentContract = getWords("permanent contract");
$strWordsOutsource = getWords("outsource");
$strWordsCODE = getWords("code");
$strWordsKETERANGAN = getWords("keterangan");
$strWordsDelete = getWords("delete");
$strWordsTimeLimitforCompensation = getWords("time limit for compensation");
$strWordsNormalDayTranspor = getWords("normal day transport");
$strWords1rstMeal = getWords("1st meal");
$strWords2ndMeal = getWords("2nd meal");
$strWords3rdMeal = getWords("3rd meal");
$strWordsSave = getWords("save");
$strModule = "PAYROLL";
$strDataDetail = "";
$strMessages = "";
$strMsgClass = "";
$intTotalData = 0;
$arrOvertime = [
    1 => ["scale" => 0, "note" => "",],
    2 => ["scale" => 0, "note" => "",],
    3 => ["scale" => 0, "note" => "",],
    4 => ["scale" => 0, "note" => "",],
];
$arrSetting = [
    "dataMealNormalPermanent"       => [
        "code"    => "ot_meal_normal_permanent",
        "value"   => "0",
        "note"    => "meal compensation for ot normal day of permanent",
        "default" => "0"
    ],
    "dataMealNormalOutsource"       => [
        "code"    => "ot_meal_normal_outsource",
        "value"   => "0",
        "note"    => "meal compensation for ot normal day of outsource",
        "default" => "0"
    ],
    "dataMealHolidayPermanent"      => [
        "code"    => "ot_meal_holiday_permanent",
        "value"   => "0",
        "note"    => "meal compensation for ot holiday of permanent",
        "default" => "0"
    ],
    "dataMealHolidayOutsource"      => [
        "code"    => "ot_meal_holiday_outsource",
        "value"   => "0",
        "note"    => "meal compensation for ot holiday of outsource",
        "default" => "0"
    ],
    "dataTransportNormalPermanent"  => [
        "code"    => "ot_transport_normal_permanent",
        "value"   => "0",
        "note"    => "transport compensation for ot normal day of permanent",
        "default" => "0"
    ],
    "dataTransportNormalOutsource"  => [
        "code"    => "ot_transport_normal_outsource",
        "value"   => "0",
        "note"    => "transport compensation for ot normal day of outsource",
        "default" => "0"
    ],
    "dataTransportHolidayPermanent" => [
        "code"    => "ot_transport_holiday_permanent",
        "value"   => "0",
        "note"    => "transport compensation for ot holiday of permanent",
        "default" => "0"
    ],
    "dataTransportHolidayOutsource" => [
        "code"    => "ot_transport_holiday_outsource",
        "value"   => "0",
        "note"    => "transport compensation for ot holiday of outsource",
        "default" => "0"
    ],
    "dataTransportLimitNormal"      => [
        "code"    => "ot_transport_normal_limit",
        "value"   => "21:00:00",
        "note"    => "time limit for transport compensation normalday",
        "default" => "21:00:00"
    ],
    "dataMealLimit1"                => [
        "code"    => "ot_meal_limit1",
        "value"   => "4",
        "note"    => "duration limit for meal compensation 1rst",
        "default" => "4"
    ],
    "dataMealLimit2"                => [
        "code"    => "ot_meal_limit2",
        "value"   => "8",
        "note"    => "duration limit for meal compensation 2nd",
        "default" => "8"
    ],
    "dataMealLimit3"                => [
        "code"    => "ot_meal_limit3",
        "value"   => "8",
        "note"    => "duration limit for meal compensation 3rd",
        "default" => "8"
    ],
];
//----------------------------------------------------------------------
//--- DAFTAR FUNGSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database
// return berubah $arrOvertime (parameter)
function getData($db)
{
  global $words;
  global $_SESSION;
  global $arrOvertime;
  global $arrSetting;
  global $strModule;
  $intIDmodified_by = $_SESSION['sessionUserID'];
  $tblOvertimeType = new cModel("hrd_overtime_type");
  foreach ($arrOvertime AS $i => $arrLembur) {
    if ($rowDb = $tblOvertimeType->findByCode("L$i")) {
      $arrOvertime[$i]['scale'] = $rowDb['scale'];
      $arrOvertime[$i]['note'] = $rowDb['note'];
    } else { // create
      $data = [
          "scale" => 0,
          "note"  => '',
          "code"  => "L$i"
      ];
      $tblOvertimeType->insert($data);
    }
  }
  $tblOvertimeType = new cModel("hrd_overtime_type");
  foreach ($arrOvertime AS $i => $arrLembur) {
    $strSQL = "SELECT * FROM hrd_overtime_type WHERE code = 'L$i' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrOvertime[$i]['scale'] = $rowDb['scale'];
      $arrOvertime[$i]['note'] = $rowDb['note'];
    } else { // create
      $strSQL = "INSERT INTO hrd_overtime_type (created, code, scale, note) ";
      $strSQL .= "VALUES(now(), 'L$i', 0, '') ";
      $resExec = $db->execute($strSQL);
    }
  }
  $tblSetting = new cModel("all_setting");
  foreach ($arrSetting AS $kode => $arrData) {
    if ($arrData['code'] != "") {
      if ($arrHasil = $tblSetting->findByCode($arrData['code'])) {
        $arrSetting[$kode]["value"] = $arrHasil['value'];
      } else {
        $data = [
            "code"   => $arrData['code'],
            "value"  => $arrData['default'],
            "note"   => $arrData['note'],
            "module" => $strModule
        ];
        $tblSetting->insert($data);
      }
    }
  }
  return true;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $arrOvertime;
  global $arrSetting;
  global $messages;
  $tblOvertimeType = new cModel("hrd_overtime_type");
  foreach ($arrOvertime AS $i => $arrLembur) {
    (isset($_REQUEST['dataAmount' . $i])) ? $intAmount = $_REQUEST['dataAmount' . $i] : $intAmount = 0;
    (isset($_REQUEST['dataNote' . $i])) ? $strNote = $_REQUEST['dataNote' . $i] : $strNote = "";
    $data = [
        "scale" => $intAmount,
        "note"  => $strNote,
        "code"  => "L$i"
    ];
    if ($rowDb = $tblOvertimeType->findByCode('L$i')) {
      $tblOvertimeType->update(["code" => "L$i"], $data);
    } else { // create
      $tblOvertimeType->insert($data);
    }
  }
  $strmodified_byID = $_SESSION['sessionUserID'];
  $tblSetting = new cModel("all_setting");
  foreach ($arrSetting AS $kode => $arrData) {
    if (isset($_REQUEST[$kode])) {
      $strValue = $_REQUEST[$kode];
      $tblSetting->update(["code" => $arrData['code']], ["value" => $strValue]);
    }
  }
  writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "", 0);
  $strError = $messages['data_saved'] . " " . date("d-M-y H:i:s");
  return true;
} // saveData
function getDataKeterangan($db)
{
  global $words;
  $strResult = "";
  $query = "SELECT code, reason FROM hrd_overtime_reason ORDER BY code";
  $res = $db->execute($query);
  $i = 0;
  while ($row = $db->fetchrow($res)) {
    $i++;
    $strResult .= "<tr>";
    $strResult .= "	<td><input name='chkID$i' type='checkbox' value=\"" . $row['code'] . "\"/> </td>";
    $strResult .= "	<td><input type='hidden' name='hidCode$i' value=\"" . $row['code'] . "\" />" . $row['code'] . "</td> ";
    $strResult .= "	<td><input type='hidden' name='hidReason$i' value=\"" . $row['reason'] . "\" />" . $row['reason'] . "</td>";
    $strResult .= "	<td align='center'><a href='javascript: editData($i)' >" . $words['edit'] . "</a>&nbsp;</td>";
    $strResult .= "</tr>";
  }
  $strResult .= "<input type='hidden' name='totalData' id='totalData' value='$i' />";
  return $strResult;
}

//getDataKeterangan
function saveDataRequest($db)
{
  global $_REQUEST;
  global $_SESSION;
  $intIDmodified_by = $_SESSION['sessionUserID'];
  $hasil = cek_field($db, "code", "hrd_overtime_reason", $_REQUEST['dataCode'], "");
  $tbl = new cModel("hrd_overtime_reason");
  $data = [
      "code"   => $_REQUEST['dataCode'],
      "reason" => $_REQUEST['dataReason']
  ];
  if ($hasil == 0) {
    $tbl->insert($data);
  } else {
    $tbl->update(["code" => $_REQUEST['dataCode']], $data);
  }
  return true;
}

//saveDataRequest
function deleteData()
{
  global $_REQUEST;
  global $db;
  $i = 0;
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 5) == 'chkID') {
      $strSQL = "DELETE FROM hrd_overtime_reason WHERE code = '" . $strValue . "'";
      $res = $db->execute($strSQL);
      $i++;
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
  }
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      $bolOK = saveData($db, $strError);
      if ($strError != "") {
        $strMessages = $strError;
        $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
      }
    } else if (isset($_REQUEST['btnSaveReason'])) {
      $bolOK = saveDataRequest($db, $strError);
      if (!$bolOK) {
        $strMessages = "Data Sudah Ada!";
        $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
      }
    } else if (isset($_REQUEST['btnDelete'])) {
      deleteData();
    }
  }
  if ($bolCanView) {
    getData($db);
  }
  // -- tampilkan data
  $intAmountL1 = $arrOvertime[1]['scale'];
  $intAmountL2 = $arrOvertime[2]['scale'];
  $intAmountL3 = $arrOvertime[3]['scale'];
  $intAmountL4 = $arrOvertime[4]['scale'];
  $strNoteL1 = $arrOvertime[1]['note'];
  $strNoteL2 = $arrOvertime[2]['note'];
  $strNoteL3 = $arrOvertime[3]['note'];
  $strNoteL4 = $arrOvertime[4]['note'];
  foreach ($arrSetting AS $kode => $arrTmp) {
    $$kode = $arrTmp['value'];
  }
  $strInputData = getDataKeterangan($db);
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>