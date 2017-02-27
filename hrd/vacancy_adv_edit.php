<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
include_once('../global/employee_function.php');
//include_once("../includes/krumo/class.krumo.php");
//include_once(getTemplate("words.inc"));
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
  die(getWords('view denied'));
}
$strTemplateFile = getTemplate("vacancy_adv_edit.html");
//---- INISIALISASI ----------------------------------------------------
$strWordsDataEntry = getWords("data entry");
$strWordsAdvertisementList = getWords("advertisement list");
$strWordsAdvertisementReport = getWords("advertisement report");
$strWordsDate = getWords("advertisement date");
$strWordsDueDate = getWords("due date");
$strWordsReference = getWords("media");
$strWordsPosition = getWords("position");
$strWordsClearForm = getWords("clear form");
$intDefaultWidth = 30;
$intDefaultHeight = 3;
$strNow = date($_SESSION['sessionDateSetting']['php_format']);
$strMessages = "";
$strMsgClass = "";
$strDataPosition = "";
$bolError = false;
$strRefDelim = "||"; // delimiter untuk memisahkan info reference
//$strEmptyOption = "<option value=''> </option>";
$arrData = [
    "dataDate"      => $strNow,
    "dataDueDate"   => "",
    "dataReference" => "",
    "dataID"        => "",
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
  global $strRefDelim;
  if ($strDataID != "") {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "ID=$strDataID", 0);
    $strSQL = "
        SELECT t1.* 
        FROM hrd_job_advertisement AS t1 
        WHERE t1.id = '$strDataID' 
      ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $advDateExplode = explode("-",$rowDb['advertisement_date']);
      $dueDateExplode = explode("-",$rowDb['due_date']);
      $arrData['dataDate'] = date($_SESSION['sessionDateSetting']['php_format'], mktime(0,0,0,$advDateExplode[1],$advDateExplode[2],$advDateExplode[0]));
      $arrData['dataDueDate'] = date($_SESSION['sessionDateSetting']['php_format'], mktime(0,0,0,$dueDateExplode[1],$dueDateExplode[2],$dueDateExplode[0]));
      $arrData['dataReference'] = $rowDb['ref_type'] . $strRefDelim . $rowDb['reference'];
      $arrData['dataID'] = $strDataID;
    }
  }
  return true;
} // showData
// fungsi untuk ambil data daftar MFR, dalam bentuk array
function getArrayRecruitmentID($db, $strDataID = "")
{
  $arrResult = [];
  $strSQL = "
      SELECT * FROM hrd_recruitment_need
      WHERE status >= '" . REQUEST_STATUS_APPROVED . "'
        AND extract(year from recruitment_date) > " . (date("Y") - 5) . "
      ORDER BY request_number DESC
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $strInfo = $row['request_number'] . " - " . $row['position'];
    $arrResult[$row['id']] = $strInfo;
  }
  return $arrResult;
}

// fungsi mengambil daftar jabatan yang dimohonkan
function getPositionAdv($db, $strDataID = "")
{
  global $words;
  global $_REQUEST;
  global $strEmptyOption;
  $intMaxShow = 4; // tambahan yang perlu dimunculkan
  $intAdd = 10; // maksimum tambahan
  $intRows = 0;
  $intShown = 0;
  $strResult = "";
  $arrRecruitmentID = getArrayRecruitmentID($db, $strDataID);
  $strResult .= " <table border=0 class='table table-striped table-hover' cellpadding=0 cellspacing=0 >\n";
  $strResult .= "  <thead><tr valign=top class=tableHeader>\n";
  $strResult .= "  <th nowrap>&nbsp;" . getWords('no') . "</th>\n";
  $strResult .= "  <th nowrap>&nbsp;" . "MRF" . "</th>\n";
  $strResult .= "  <th nowrap>&nbsp;" . getWords('position') . "</th>\n";
  $strResult .= "  <th nowrap>&nbsp;" . getWords('note') . "</th>\n";
  $strResult .= "  <th nowrap>&nbsp;" . getWords('delete') . "</th>\n";
  $strResult .= "  </tr></thead>\n";
  $arrDetail = [];
  if ($strDataID != "") {
    $strSQL = "
        SELECT t1.* 
        FROM hrd_job_advertisement_detail AS t1 
        WHERE t1.id_advertisement = '$strDataID' 
      ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrDetail[$rowDb['id']] = $rowDb;
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  $strResult .= "<tbody>";
  foreach ($arrDetail AS $id => $rowDb) {
    $intRows++;
    $intShown++;
    $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
    $strResult .= "  <td align=right nowrap><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">$intRows&nbsp;</td>";
    $strAction = "onChange= \"onRecruitmentChanged($intRows);\" ";
    $strResult .= "  <td>" . getComboFromArray(
            $arrRecruitmentID,
            "detailRecruitment$intRows",
            $rowDb['id_recruitment_need'],
            $strEmptyOption,
            " $strAction "
        ) . "</td>";
    $strResult .= "  <td><input class='form-control'  type=text size=30 maxlength=100 name=detailPosition$intRows id=detailPosition$intRows value=\"" . $rowDb['position_name'] . "\" ></td>";
    $strResult .= "  <td><input class='form-control'  type=text size=20 maxlength=100 name=detailNote$intRows id=detailNote$intRows value=\"" . $rowDb['note'] . "\"></td>";
    $strAction = " onClick = \"chkDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center><div class=\"checkbox\"><label><input class=\"checkbox-inline\" type=checkbox name='chkID$intRows' $strAction></label></div></td>\n";
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
    $strAction = "onChange = \"onRecruitmentChanged($intRows);\" ";
    $strResult .= "  <td>" . getComboFromArray(
            $arrRecruitmentID,
            "detailRecruitment$intRows",
            "",
            $strEmptyOption,
            " $strAction "
        ) . "</td>";
    $strResult .= "  <td><input class='form-control' type=text size=30 maxlength=100 name=detailPosition$intRows id=detailPosition$intRows value=\"\" $strDisabled></td>";
    $strResult .= "  <td><input class='form-control' type=text size=20 maxlength=100 name=detailNote$intRows id=detailNote$intRows value=\"\" $strDisabled></td>";
    $strAction = " onClick = \"chkDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center><div class=\"checkbox\"><label><input class=\"checkbox-inline\" type=checkbox name='chkID$intRows' $strAction></label></div></td>\n";
    $strResult .= "</tr>\n";
  }
  $strResult .= " <tr valign=top><td>&nbsp;</td>\n";
  $strResult .= "  <td colspan=4>&nbsp;<a class='btn btn-info btn-xs' href=\"javascript:showMoreInput();\">" . getWords(
          'more'
      ) . "</a></td></tr>\n";
  $strResult .= "</tbody>";
  $strResult .= "  </table>\n";
  // tambahkan hidden data
  $strResult .= "<input type=hidden name=maxDetail value=$intRows>";
  $strResult .= "<input type=hidden name=numShow value=$intShown>";
  return $strResult;
} // getPositionAdv
// fungsi untuk menyimpan data absen
function saveData($db, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $error;
  global $messages;
  global $arrData;
  global $strDataID;
  global $strRefDelim;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strError = "";
  $bolOK = true;
  $strToday = date("Y-m-d");
  $strDataDate = (isset($_REQUEST['dataDate'])) ? standardDateToSQLDate($_REQUEST['dataDate']) : "";
  $strDataDueDate = (isset($_REQUEST['dataDueDate'])) ? standardDateToSQLDate($_REQUEST['dataDueDate']) : "";
  $strDataReference = (isset($_REQUEST['dataReference'])) ? trim($_REQUEST['dataReference']) : "";
  // cek validasi -----------------------
  if (!validStandardDate($strDataDate)) {
    $strError = getWords('invalid_date');
    $bolOK = false;
  } else if ($strDataReference == "") {
    $strError = getWords('empty_code') . " - " . getWords("media");
    $bolOK = false;
  }
  $strDataDueDate = validStandardDate($strDataDueDate) ? "'$strDataDueDate'" : "NULL";
  $arrTmp = explode($strRefDelim, $strDataReference);
  $strRefType = (isset($arrTmp[0])) ? $arrTmp[0] : "";
  $strReference = (isset($arrTmp[1])) ? $arrTmp[1] : "";
  $strRefType = (is_numeric($strRefType)) ? "'$strRefType'" : "NULL";
  // simpan data -----------------------
  if ($bolOK) { // input OK, tinggal disimpan
    if ($strDataID == "") {
      // data baru
      $strDataID = $db->getNextID("hrd_job_advertisement_id_seq");
      $strSQL = "
          INSERT INTO hrd_job_advertisement (id, created,created_by,modified_by, 
            advertisement_date, due_date, ref_type, reference) 
          VALUES('$strDataID', now(),'$strmodified_byID','$strmodified_byID', 
            '$strDataDate', $strDataDueDate, $strRefType, '$strReference') 
        ";
      $resExec = $db->execute($strSQL);
    } else {
      $strSQL = "
          UPDATE hrd_job_advertisement 
          SET modified_by = '" . $_SESSION['sessionUserID'] . "',
            advertisement_date = '$strDataDate', due_date = $strDataDueDate, 
            ref_type = $strRefType, reference = '$strReference'
          WHERE id = '$strDataID' 
        ";
      $resExec = $db->execute($strSQL);
    }
    if ($strDataID != "") {
      // simpan data employee participant
      // hapus dulu aja, biar simple
      $strSQL = "DELETE FROM hrd_job_advertisement_detail WHERE id_advertisement = '$strDataID' ";
      $resExec = $db->execute($strSQL);
      $intTotal = (isset($_REQUEST['numShow'])) ? $_REQUEST['numShow'] : 0;
      for ($i = 1; $i <= $intTotal; $i++) {
        if (isset($_REQUEST['detailPosition' . $i])) {
          $strPosition = $_REQUEST['detailPosition' . $i];
          $strRecruitment = (isset($_REQUEST['detailRecruitment' . $i])) ? $_REQUEST['detailRecruitment' . $i] : 0;
          $strRecruitment = (is_numeric($strRecruitment)) ? "'$strRecruitment'" : "NULL";
          $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
          if ($strPosition != "") {
            $strSQL = "
                INSERT INTO hrd_job_advertisement_detail (created, modified_by, 
                  created_by, id_advertisement, id_recruitment_need, position_name, note) 
                VALUES(now(), '$strmodified_byID', '$strmodified_byID', 
                  '$strDataID', $strRecruitment, '$strPosition', '$strNote') 
              ";
            $resExec = $db->execute($strSQL);
          }
        }
      }
    }
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "MUTATION DATA", 0);
    $strError = $messages['data_saved'];
  } else { // ---- data SALAH
    $arrData['dataID'] = $strDataID;
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "ABSENCE DATA", 0);
  }
  return $bolOK;
} // saveData
// fungsi untuk mencari daftar jenis media massa
function getReferenceList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
  global $strRefDelim;
  $strResult = "";
  $strHidden = "";
  if (!$listonly) {
    $strResult .= "<select name=\"$varname\" id=\"$varname\" $action class=\"form-control select2\">\n";
  }
  $strResult .= $extra;
  $arrRef = [];
  $strSQL = "SELECT * FROM hrd_candidate_reference ";
  $strSQL .= $criteria;
  $strSQL .= " ORDER BY name, reference ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrRef[$rowDb['type']]['info'] = $rowDb['name'];
    $arrRef[$rowDb['type']]['data'][] = $rowDb['reference'];
  }
  foreach ($arrRef AS $strType => $arrType) {
    $strResult .= "<optgroup label='" . $arrType['info'] . "'>";
    foreach ($arrType['data'] AS $i => $strRef) {
      $value = $strType . $strRefDelim . $strRef;
      ($value == $default) ? $strSelect = "selected" : $strSelect = "";
      $strInfo = $strRef;
      $strResult .= "<option value=\"" . $value . "\" $strSelect>" . $strInfo . "</option>\n";
    }
    $strResult .= "</optgroup>\n";
  }
  while ($rowDb = $db->fetchrow($resDb)) {
  }
  if (!$listonly) {
    $strResult .= "</select>\n";
  }
  return $strResult;
}//
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  $dtNow = getdate();
  $strUserRole = $_SESSION['sessionUserRole'];
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  if ($bolCanEdit) {
    if (isset($_POST['btnSave'])) {
      $bolOK = saveData($db, $strError);
      if ($strError != "") {
        $strMessages = $strError;
        $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
      }
    }
  }
  if ($bolCanView) {
    getData($db);
    $strInputPosition = getPositionAdv($db, $strDataID);
  } else {
    showError("view_denied");
  }
  //----- TAMPILKAN DATA ---------
  $strDefaultWidthPx = 250;
  $strDefaultWidthPx1 = 100;
  $strReadonlyRequest = "";
  $strReadonlyEmployee = "";
  $strDisabled = "";
  $strInputDate = "<input type=text size=15 maxlength=10 name=dataDate id=dataDate value=\"" . $arrData['dataDate'] . "\" $strReadonlyRequest title=\"format:mm/dd/yyyy\" class=\"date-empty form-control datepicker\" data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\">";
  //$strInputDate .= " <input name=\"btnDate\" type=button id=\"btnDate\" value='..' $strDisabled>";
  $strInputDueDate = "<input type=text size=15 maxlength=10 name=dataDueDate id=dataDueDate value=\"" . $arrData['dataDueDate'] . "\" title=\"format:mm/dd/yyyy\" class=\"date-empty form-control datepicker\" data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\">";
  //$strInputDueDate .= " <input name=\"btnDueDate\" type=button id=\"btnDueDate\" value='..' $strDisabled>";
  $strInputReference = getReferenceList(
      $db,
      "dataReference",
      $arrData['dataReference'],
      "",
      "",
      ""
  );
  //$strInputStatus = $words[$ARRAY_REQUEST_STATUS[$arrData['dataStatus']]];
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = vacancyAdvSubmenu($strWordsDataEntry);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>