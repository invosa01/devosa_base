<?php
include_once('../global/session.php');
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
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$strTemplateFile = getTemplate("evaluation_setting.html");
//---- INISIALISASI ----------------------------------------------------
$arrSetting = [
    "strStartEvaluation" => [
        "code"         => "start_evaluation",
        "value"        => "1",
        "note"         => "start month for evaluation periode",
        "default"      => "1",
        "oldparameter" => "strStartEvaluation"
    ],
    "strOperational" => [
        "code"         => "weight_operational",
        "value"        => "0",
        "note"         => "weight for operational performance",
        "default"      => "0",
        "oldparameter" => "strOperational"
    ],
    "strGeneral"     => [
        "code"         => "weight_general",
        "value"        => "0",
        "note"         => "weight for employee general evaluation",
        "default"      => "0",
        "oldparameter" => "strGeneral"
    ],
    "strAbsence"     => [
        "code"         => "weight_absence",
        "value"        => "0",
        "note"         => "weight for employee absence",
        "default"      => "0",
        "oldparameter" => "strAbsence"
    ],
    "strLate"  => [
        "code"         => "weight_deduction_dl",
        "value"        => "0",
        "note"         => "datang terlambat",
        "default"      => "0",
        "oldparameter" => "strLate"
    ],
    "strEarly" => [
        "code"         => "weight_deduction_pc",
        "value"        => "0",
        "note"         => "pulang cepat",
        "default"      => "0",
        "oldparameter" => "strGeneral"
    ],
    "strAlpa"  => [
        "code"         => "weight_deduction_m",
        "value"        => "0",
        "note"         => "alpa",
        "default"      => "0",
        "oldparameter" => "strAlpa"
    ],
];
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database
// return berubah $arrOvertime (parameter)
function getData($db)
{
  global $strModule;
  global $arrSetting;
  global $_REQUEST;
  $intIDmodified_by = $_SESSION['sessionUserID'];
  foreach ($arrSetting AS $kode => $arrData) {
    if ($arrData['code'] != "") {
      $arrSetting[$kode]["value"] = getSetting($arrData['code']);
      if ($arrSetting[$kode]["value"] == "" || $arrSetting[$kode]["value"] == 0) {
        if (saveSetting($arrData['code'], $arrData['default'], $arrData['note'], $strModule)) {
          $arrSetting[$kode]["value"] = $arrData['default'];
        }
      }
    }
  }
  writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  return true;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $arrSetting;
  global $arrBreakTime;
  global $messages;
  $strmodified_byID = $_SESSION['sessionUserID'];
  foreach ($arrSetting AS $kode => $arrData) {
    if (isset($_REQUEST[$kode])) {
      $strValue = $_REQUEST[$kode];
      $strSQL = "UPDATE all_setting SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
      $strSQL .= "created = now(), value = '$strValue' ";
      $strSQL .= "WHERE code = '" . $arrData['code'] . "'; ";
      $resExec = $db->execute($strSQL);
      $strError = $messages['data_saved'];
    }
  }
  writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "", 0);
  return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  if ($bolCanView) {
    if ($bolCanEdit) {
      if (isset($_REQUEST['btnSave'])) {
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
$strStartEvaluation = $arrSetting['strStartEvaluation']['value'];
$strStartEvaluation = getMonthList("strStartEvaluation", $strStartEvaluation);
$strOperational = $arrSetting['strOperational']['value'];
$strGeneral = $arrSetting['strGeneral']['value'];
$strAbsence = $arrSetting['strAbsence']['value'];
$strLate = $arrSetting['strLate']['value'];
$strEarly = $arrSetting['strEarly']['value'];
$strAlpa = $arrSetting['strAlpa']['value'];
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>