<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  header("location:login.php?dataPage=recruitment_edit.php");
  exit();
}
$bolCanView = getUserPermission("recruitment_edit.php", $bolCanEdit, $bolCanDelete, $strError, true);
$strTemplateFile = getTemplate("recruitment_plan_edit.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intDefaultWidth = 30;
$intDefaultHeight = 3;
$strNow = date("Y-m-d");
$strMessages = "";
$strMsgClass = "";
$bolError = false;
$arrData = [
    "dataDate"           => $strNow,
    "dataYear"           => date("Y"),
    "dataDueDate"        => "",
    "dataDepartment"     => "",
    "dataPosition"       => "",
    "dataEmployeeStatus" => "",
    "dataNumber"         => "1",
    "dataPIC"            => "",
    "dataDescription"    => "",
    "dataQualification"  => "",
    "dataMinAge"         => "",
    "dataMaxAge"         => "",
    "dataMarital"        => "",
    "dataEducationLevel" => "",
    "dataEducation"      => "",
    "dataWork"           => "",
    "dataGender"         => "",
    "dataMarital"        => "",
    "dataCost"           => "0",
    "dataStatus"         => "0",
    "dataID"             => "",
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
  if ($strDataID != "") {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "ID=$strDataID", 0);
    $strSQL = "SELECT t1.* FROM hrd_recruitment_plan AS t1 ";
    $strSQL .= "WHERE t1.id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      //$arrData['dataDate'] = $rowDb['recruitmentDate'];
      $arrData['dataYear'] = $rowDb['year'];
      $arrData['dataDueDate'] = $rowDb['due_date'];
      $arrData['dataStatus'] = $rowDb['status'];
      $arrData['dataDescription'] = $rowDb['description'];
      $arrData['dataQualification'] = $rowDb['qualification'];
      $arrData['dataDepartment'] = $rowDb['department_code'];
      $arrData['dataPosition'] = $rowDb['position'];
      $arrData['dataEmployeeStatus'] = $rowDb['employee_status'];
      $arrData['dataNumber'] = $rowDb['number'];
      $arrData['dataCost'] = $rowDb['cost'];
      $arrData['dataPIC'] = $rowDb['pic'];
      $arrData['dataMinAge'] = $rowDb['min_age'];
      $arrData['dataMaxAge'] = $rowDb['max_age'];
      $arrData['dataMarital'] = $rowDb['marital_status'];
      $arrData['dataEducationLevel'] = $rowDb['education_level'];
      $arrData['dataEducation'] = $rowDb['education'];
      $arrData['dataWork'] = $rowDb['work_experience'];
      $arrData['dataGender'] = $rowDb['gender'];
    }
  }
  return true;
} // showData
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
  //$strDataDate = (isset($_REQUEST['dataDate'])) ? $_REQUEST['dataDate'] : "";
  $strDataYear = (isset($_REQUEST['dataYear'])) ? $_REQUEST['dataYear'] : date("Y");
  $strDataDueDate = (isset($_REQUEST['dataDueDate'])) ? $_REQUEST['dataDueDate'] : "";
  $strDataDepartment = (isset($_REQUEST['dataDepartment'])) ? trim($_REQUEST['dataDepartment']) : "";
  $strDataPosition = (isset($_REQUEST['dataPosition'])) ? trim($_REQUEST['dataPosition']) : "";
  $strDataEmployeeStatus = (isset($_REQUEST['dataEmployeeStatus'])) ? trim($_REQUEST['dataEmployeeStatus']) : "";
  $strDataDescription = (isset($_REQUEST['dataDescription'])) ? trim($_REQUEST['dataDescription']) : "";
  $strDataQualification = (isset($_REQUEST['dataQualification'])) ? trim($_REQUEST['dataQualification']) : "";
  $strDataNumber = (isset($_REQUEST['dataNumber'])) ? $_REQUEST['dataNumber'] : "0";
  $strDataWork = (isset($_REQUEST['dataWork'])) ? $_REQUEST['dataWork'] : "0";
  $strDataMinAge = (isset($_REQUEST['dataMinAge'])) ? $_REQUEST['dataMinAge'] : "";
  $strDataMaxAge = (isset($_REQUEST['dataMaxAge'])) ? $_REQUEST['dataMaxAge'] : "";
  $strDataEducation = (isset($_REQUEST['dataEducation'])) ? $_REQUEST['dataEducation'] : "";
  $strDataEducationLevel = (isset($_REQUEST['dataEducationLevel'])) ? $_REQUEST['dataEducationLevel'] : "";
  $strDataGender = (isset($_REQUEST['dataGender'])) ? $_REQUEST['dataGender'] : "";
  $strDataMarital = (isset($_REQUEST['dataMarital'])) ? $_REQUEST['dataMarital'] : "";
  // cek validasi -----------------------
  if ($strDataDepartment == "") {
    $strError = $error['empty_code'];
    $bolOK = false;
  } else if ($strDataPosition == "") {
    $strError = $error['empty_code'];
    $bolOK = false;
  } /*else if (!validStandardDate($strDataDate)) {
      $strError = $error['invalid_date'];
      $bolOK =  false;
    }*/
  $strDataDueDate = validStandardDate($strDataDueDate) ? "'$strDataDueDate'" : "NULL";
  //$strDataDate = validStandardDate($strDataDate) ? "'$strDataDate'" : "NULL";
  if (!is_numeric($strDataGender)) {
    $strDataGender = 2;
  } // bebas
  if (!is_numeric($strDataMarital)) {
    $strDataMarital = 2;
  } // bebas
  if (!is_numeric($strDataNumber)) {
    $strDataNumber = 1;
  }
  // simpan data -----------------------
  if ($bolOK) { // input OK, tinggal disimpan
    if ($strDataID == "") {
      // data baru
      $strSQL = "INSERT INTO hrd_recruitment_plan (created,created_by,modified_by, ";
      $strSQL .= "department_code,\"year\", due_date, position, ";
      $strSQL .= "employee_status, number, description, min_age, max_age, ";
      $strSQL .= "gender, marital_status, education_level, education, ";
      $strSQL .= "work_experience, qualification, cost, status) ";
      $strSQL .= "VALUES(now(),'$strmodified_byID','$strmodified_byID', ";
      $strSQL .= "'$strDataDepartment','$strDataYear', $strDataDueDate, '$strDataPosition',  ";
      $strSQL .= "'$strDataEmployeeStatus', '$strDataNumber', '$strDataDescription', ";
      $strSQL .= "'$strDataMinAge', '$strDataMaxAge', '$strDataGender', '$strDataMarital', ";
      $strSQL .= "'$strDataEducationLevel', '$strDataEducation', '$strDataWork', ";
      $strSQL .= "'$strDataQualification', 0," . REQUEST_STATUS_VERIFIED . ") ";
      $resExec = $db->execute($strSQL);
      // cari ID
      $strSQL = "SELECT id FROM hrd_recruitment_plan ";
      $strSQL .= "WHERE \"year\" = '$strDataYear' AND department_code = '$strDataDepartment' ";
      $strSQL .= "AND position = '$strDataPosition' AND status = " . REQUEST_STATUS_VERIFIED . " ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $strDataID = $rowDb['id'];
      } else {
        $strError = "save fail";
        return false;
      }
    } else {
      // cek status, jika sudah approved, gak boleh diedit lagi
      $strSQL = "SELECT status FROM hrd_recruitment_plan WHERE id = '$strDataID' ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        if ($rowDb['status'] != REQUEST_STATUS_VERIFIED) {
          $strError = $error['edit_denied'];
          return false;
        }
      }
      $strSQL = "UPDATE hrd_recruitment_plan ";
      $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
      $strSQL .= "\"year\" = '$strDataYear', due_date = $strDataDueDate, ";
      $strSQL .= "department_code = '$strDataDepartment', position = '$strDataPosition', ";
      $strSQL .= "employee_status = '$strDataEmployeeStatus', number = '$strDataNumber', ";
      $strSQL .= "description = '$strDataDescription', qualification = '$strDataQualification', ";
      $strSQL .= "min_age = '$strDataMinAge', max_age = '$strDataMaxAge', ";
      $strSQL .= "marital_status = '$strDataMarital', gender = '$strDataGender', ";
      $strSQL .= "education_level = '$strDataEducationLevel', education = '$strDataEducation', ";
      $strSQL .= "work_experience = '$strDataWork' ";
      $strSQL .= "WHERE id = '$strDataID' ";
      $resExec = $db->execute($strSQL);
    }
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "MUTATION DATA", 0);
    $strError = $messages['data_saved'];
  } else { // ---- data SALAH
    // gunakan data yang diisikan tadi
    $arrData['dataDepartment'] = $strDataDepartment;
    //$arrData['dataDate'] = $strDataDate;
    $arrData['dataYear'] = $strDataYear;
    $arrData['dataPosition'] = $strDataPosition;
    $arrData['dataEmployeeStatus'] = $strDataEmployeeStatus;
    $arrData['dataNumber'] = $strDataNumber;
    $arrData['dataDescription'] = $strDataDescription;
    $arrData['dataQualification'] = $strDataQualification;
    $arrData['dataMinAge'] = $strDataMinAge;
    $arrData['dataMaxAge'] = $strDataMaxAge;
    $arrData['dataWork'] = $strDataWork;
    $arrData['dataEducationLevel'] = $strDataEducationLevel;
    $arrData['dataEducation'] = $strDataEducation;
    $arrData['dataID'] = $strDataID;
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
  // cek permission -- khusus employee
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    if (!$arrUserInfo['isDeptHead'] && !$arrUserInfo['isGroupHead']) {
      $bolCanDelete = $bolCanEdit = $bolCanView = false;
    }
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
  } else {
    showError("view_denied");
  }
  //----- TAMPILKAN DATA ---------
  $strDefaultWidthPx = 200;
  $strDefaultWidthPx1 = 100;
  $strReadonly = ($arrData['dataStatus'] <= REQUEST_STATUS_VERIFIED) ? "" : "readonly"; // kalau dah approve, jadi readonly
  // jika baru, beri default department sesuai employee
  if ($arrData['dataDepartment'] == "" && $strDataID == "") {
    $arrData['dataDepartment'] = $arrUserInfo['department_code'];
  }
  //$strInputDate = "<input type=text size=15 maxlength=10 name=dataDate id=dataDate value=\"" .$arrData['dataDate']. "\" $strReadonly>";
  $strInputYear = getYearList("dataYear", $arrData['dataYear']);
  $strInputDueDate = "<input type=text size=15 maxlength=10 name=dataDueDate id=dataDueDate value=\"" . $arrData['dataDueDate'] . "\" $strReadonly class='date-empty'>";
  $strInputPosition = "<input type=text name=dataPosition id=dataPosition size=15 maxlength=30 value=\"" . $arrData['dataPosition'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly>";
  $strInputEducation = "<input type=text name=dataEducation id=dataEducation size=15 maxlength=30 value=\"" . $arrData['dataEducation'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly>";
  $strInputNumber = "<input type=text name=dataNumber size=20 maxlength=10 value=\"" . $arrData['dataNumber'] . "\" style=\"width:$strDefaultWidthPx\"  $strReadonly class=numeric>";
  $strInputMinAge = "<input type=text name=dataMinAge size=20 maxlength=10 value=\"" . $arrData['dataMinAge'] . "\" style=\"width:$strDefaultWidthPx1\" $strReadonly class='numeric-empty'>";
  $strInputMaxAge = "<input type=text name=dataMaxAge size=20 maxlength=10 value=\"" . $arrData['dataMaxAge'] . "\" style=\"width:$strDefaultWidthPx1\" $strReadonly class='numeric-empty'>";
  $strInputWork = "<input type=text name=dataWork size=20 maxlength=3 value=\"" . $arrData['dataWork'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly>";
  $strInputDescription = "<textarea name=dataDescription cols=30 rows=3 wrap='virtual' style=\"width:$strDefaultWidthPx\" $strReadonly>" . $arrData['dataDescription'] . "</textarea>";
  $strInputQualification = "<textarea name=dataQualification cols=30 rows=3 wrap='virtual' style=\"width:$strDefaultWidthPx\" $strReadonly>" . $arrData['dataQualification'] . "</textarea>";
  $strDeptKriteria = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) ? "WHERE department_code = '" . $arrUserInfo['department_code'] . "' " : "";
  $strInputDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $arrData['dataDepartment'],
      "",
      "$strDeptKriteria",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputEducationLevel = getEducationList(
      $db,
      "dataEducationLevel",
      $arrData['dataEducationLevel'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputEmployeeStatus = getEmployeeStatusList(
      "dataEmployeeStatus",
      $arrData['dataEmployeeStatus'],
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputGender = getGenderList(
      "dataGender",
      $arrData['dataGender'],
      "$strEmptyOption",
      " style=\"width:$strDefaultWidthPx1 \" $strReadonly"
  );
  $strInputMarital = getMaritalList(
      "dataMarital",
      $arrData['dataMarital'],
      "$strEmptyOption",
      " style=\"width:$strDefaultWidthPx1 \" $strReadonly"
  );
  //$strInputStatus = $words[$ARRAY_MUTATION_STATUS[$arrData['dataStatus']]];
  $strInputStatus = getWords($ARRAY_REQUEST_STATUS[$arrData['dataStatus']]);
}
$strInitAction .= "document.formInput.dataDepartment.focus();
    Calendar.setup({ inputField:\"dataDueDate\", button:\"btnDueDate\" });
 
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>