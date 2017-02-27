<?php
include_once('../global/session.php');
include_once('global.php');
include_once('activity.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
include_once('cls_annual_leave.php');
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
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$bolPrint = (isset($_REQUEST['btnPrint']));
//---- INISIALISASI ----------------------------------------------------
$strWordsRequestDate = getWords("jamsostek request date");
$strWordsJamsostekClaimEntry = getWords("jamsostek KK2 claim entry");
$strWordsJamsostekClaimList = getWords("jamsostek KK2 claim list");
$strWordsEmployeeName = getWords("employee name");
$strWordsJamsostekCardID = getWords("jamsostek card id");
$strWordsEmployeeAddress = getWords("address");
$strWordsZipCode = getWords("zip");
$strWordsPhone = getWords("phone");
$strWordsBirthdate = getWords("birthday");
$strWordsBirthplace = getWords("birth place");
$strWordsGender = getWords("gender");
$strWordsPosition = getWords("position");
$strWordsDepartment = getWords("department");
$strWordsAccidentPlace = getWords("accident place");
$strWordsAccidentDate = getWords("accident date");
$strWordsAccidentTime = getWords("accident time");
$strWordsAccidentAftermath = getWords("accident aftermath");
$strWordsDamagedBodyParts = getWords("damaged body parts");
$strWordsMedicalDoctorName = getWords("doctor name");
$strWordsMedicalDoctorAddress = getWords("doctor address");
$strWordsPatientStatus = getWords("patient status");
$strWordsAccidentID = getWords("accident id");
$strWordsExpectedLosses = getWords("expected losses");
$strWordsExpectedLosses_Time = getWords("time");
$strWordsExpectedLosses_Stamp = getWords("stamp") . " (IDR)";
$strWordsExpectedLosses_WH = getWords("work hour");
$strWordsLaborWage = getWords("labor wage");
$strWordsMoneyRewards = getWords("money rewards") . " (" . getWords("basic salary") . " " . getWords(
        "and"
    ) . " " . getWords("allowance") . ")";
$strWordsIrregularIncome = getWords("irregular income");
$strWordsTotal_ab = getWords("total");
$strWordsAccidentType = getWords("accident type");
$strWordsAccidentCause = getWords("accident cause");
$strWordsAccidentFirstQ = "<span id=wordsAccidentFQ name=wordsAccidentFQ></span>"; //tergantung type, masukin ke JS
$strWordsEmployeeID = getWords("employee id");
$strWordsNote = getWords("note");
$strWordsSave = getWords("save");
$strWordsAddNew = getWords("add new");
$strDataDetail = "";
$strButtons = "";
$strMsgClass = "";
$strMessages = "";
$intDefaultWidth = 50;
$intDefaultHeight = 3;
$strNow = date("Y-m-d");
// inisialisasi untuk data array
// $arrData['dataSection'] = "";
$strUserRole = "";
$arrData = [
    "dataDate"                 => $strNow,
    "dataDateAccident"         => $strNow,
    "dataEmployee"             => "",
    "dataEmployeeName"         => "",
    "dataEmployeeAddress"      => "",
    "dataEmployeeZip"          => "",
    "dataEmployeePhone"        => "",
    "dataEmployeeGender"       => "",
    "dataEmployeeBirthdate"    => "",
    "dataEmployeeBirthplace"   => "",
    "dataEmployeePosition"     => "",
    "dataEmployeeDepartment"   => "",
    "dataEmployeeJamsostekID"  => "",
    "dataLaborWage"            => "",
    "dataMoneyRewards"         => "",
    "dataIrregularIncome"      => "",
    "dataAccPlace"             => "",
    "dataAccTime"              => "",
    "dataAccType"              => "0",
    "dataQuestion1"            => "",
    "dataCause"                => "",
    "dataAftermath"            => "",
    "dataDamagedBodyParts"     => "",
    "dataMedicalDoctorName"    => "",
    "dataMedicalDoctorAddress" => "",
    "dataPatientStatus"        => "",
    "dataAccID"                => "",
    "dataExpectedLossTime"     => "",
    "dataExpectedLossStamp"    => "",
    "dataExpectedLossWH"       => "",
    "dataNote"                 => "",
    "dataID"                   => "",
];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db, $strDataID = "")
{
  global $words;
  global $arrData;
  if ($strDataID != "") {
    $strSQL = "
                  SELECT t1.*, 
                  t2.employee_name, t2.employee_id, t2.primary_address, t2.primary_phone, t2.birthplace, t2.birthday, t2.jamsostek_no, t2.primary_zip, t2.gender,
                  t3.position_name,
                  t4.department_name
                  FROM hrd_jamsostek_claim_kk2 AS t1
                  LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
                  LEFT JOIN hrd_position AS t3 ON t3.position_code = t2.position_code
                  LEFT JOIN hrd_department AS t4 ON t4.department_code = t2.department_code
                  WHERE t1.id = $strDataID
                  ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataEmployee'] = (isset($rowDb['employee_id']) && $rowDb["employee_id"] != "") ? $rowDb['employee_id'] : "";
      $arrData['dataEmployeeName'] = (isset($rowDb['employee_name']) && $rowDb["employee_name"] != "") ? $rowDb['employee_name'] : "";
      $arrData['dataEmployeeAddress'] = (isset($rowDb['primary_address']) && $rowDb["primary_address"] != "") ? $rowDb['primary_address'] : "";
      $arrData['dataEmployeeZip'] = (isset($rowDb['primary_zip']) && $rowDb["primary_zip"] != "") ? $rowDb['primary_zip'] : "";
      $arrData['dataEmployeePhone'] = (isset($rowDb['primary_phone']) && $rowDb["primary_phone"] != "") ? $rowDb['primary_phone'] : "";
      $arrData['dataEmployeeGender'] = (isset($rowDb['gender']) && $rowDb["gender"] != "") ? $rowDb['gender'] : "";
      $arrData['dataEmployeeBirthdate'] = (isset($rowDb['birthday']) && $rowDb["birthday"] != "") ? $rowDb['birthday'] : "";
      $arrData['dataEmployeeBirthplace'] = (isset($rowDb['birthplace']) && $rowDb["birthplace"] != "") ? $rowDb['birthplace'] : "";
      $arrData['dataEmployeeJamsostekID'] = (isset($rowDb['jamsostek_no']) && $rowDb["jamsostek_no"] != "") ? $rowDb['jamsostek_no'] : "";
      $arrData['dataEmployeePosition'] = (isset($rowDb['position_name']) && $rowDb["position_name"] != "") ? $rowDb['position_name'] : "";
      $arrData['dataEmployeeDepartment'] = (isset($rowDb['department_name']) && $rowDb["department_name"] != "") ? $rowDb['department_name'] : "";
      $arrData['dataLaborWage'] = (isset($rowDb['labor_wage']) && $rowDb['labor_wage'] != "") ? $rowDb['labor_wage'] : "";
      $arrData['dataMoneyRewards'] = (isset($rowDb['money_rewards']) && $rowDb['money_rewards'] != "") ? $rowDb['money_rewards'] : "";
      $arrData['dataIrregularIncome'] = (isset($rowDb['irregular']) && $rowDb['irregular'] != "") ? $rowDb['irregular'] : "";
      $arrData['dataAccPlace'] = (isset($rowDb['acc_place']) && $rowDb['acc_place'] != "") ? $rowDb['acc_place'] : "";
      $arrData['dataDateAccident'] = (isset($rowDb['acc_date']) && $rowDb['acc_date'] != "") ? $rowDb['acc_date'] : "";
      $arrData['dataAccTime'] = (isset($rowDb['acc_time']) && $rowDb['acc_time'] != "") ? $rowDb['acc_time'] : "";
      $arrData['dataAccType'] = (isset($rowDb['acc_type']) && $rowDb['acc_type'] != "") ? $rowDb['acc_type'] : "";
      $arrData['dataQuestion1'] = (isset($rowDb['acc_question1_answer']) && $rowDb['acc_question1_answer'] != "") ? $rowDb['acc_question1_answer'] : "";
      $arrData['dataCause'] = (isset($rowDb['acc_question2_answer']) && $rowDb['acc_question2_answer'] != "") ? $rowDb['acc_question2_answer'] : "";
      $arrData['dataAftermath'] = (isset($rowDb['acc_aftermath']) && $rowDb['acc_aftermath'] != "") ? $rowDb['acc_aftermath'] : "";
      $arrData['dataDamagedBodyParts'] = (isset($rowDb['damaged_body_parts']) && $rowDb['damaged_body_parts'] != "") ? $rowDb['damaged_body_parts'] : "";
      $arrData['dataMedicalDoctorName'] = (isset($rowDb['doctor_name']) && $rowDb['doctor_name'] != "") ? $rowDb['doctor_name'] : "";
      $arrData['dataMedicalDoctorAddress'] = (isset($rowDb['doctor_address']) && $rowDb['doctor_address'] != "") ? $rowDb['doctor_address'] : "";
      $arrData['dataPatientStatus'] = (isset($rowDb['patient_status']) && $rowDb['patient_status'] != "") ? $rowDb['patient_status'] : "";
      $arrData['dataAccID'] = (isset($rowDb['acc_id']) && $rowDb['acc_id'] != "") ? $rowDb['acc_id'] : "";
      $arrData['dataExpectedLossTime'] = (isset($rowDb['expected_time']) && $rowDb['expected_time'] != "") ? $rowDb['expected_time'] : "";
      $arrData['dataExpectedLossStamp'] = (isset($rowDb['expected_stamp']) && $rowDb['expected_stamp'] != "") ? $rowDb['expected_stamp'] : "";
      $arrData['dataExpectedLossWH'] = (isset($rowDb['expected_workhour']) && $rowDb['expected_workhour'] != "") ? $rowDb['expected_workhour'] : "";
      $arrData['dataID'] = (isset($rowDb['id']) && $rowDb['id'] != "") ? $rowDb['id'] : "";
      $arrData['dataDate'] = (isset($rowDb['date_request']) && $rowDb['date_request'] != "") ? $rowDb['date_request'] : "";
      $arrData['dataNote'] = (isset($rowDb['note']) && $rowDb['note'] != "") ? $rowDb['note'] : "";
    }
  }
  return true;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strDataID, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $error;
  global $messages;
  global $arrData;
  global $arrUserInfo;
  //_debug_array($_REQUEST);
  $strError = "";
  $bolOK = true;
  $strToday = date("Y-m-d");
  $strDataDate = (isset($_REQUEST["dataDate"])) ? $_REQUEST["dataDate"] : $strToday;
  $strDataEmployee = (isset($_REQUEST["dataEmployee"])) ? $_REQUEST['dataEmployee'] : "";
  $strDataLaborWage = (isset($_REQUEST["inputDataLaborWage"]) && $_REQUEST["inputDataLaborWage"] != "") ? $_REQUEST['inputDataLaborWage'] : "NULL";
  $strDataMoneyRewards = (isset($_REQUEST["inputDataMoneyRewards"]) && $_REQUEST["inputDataMoneyRewards"] != "") ? $_REQUEST['inputDataMoneyRewards'] : "NULL";
  $strDataIrregularIncome = (isset($_REQUEST["inputDataIrregularIncome"]) && $_REQUEST["inputDataIrregularIncome"] != "") ? $_REQUEST['inputDataIrregularIncome'] : "NULL";
  $strDataAccPlace = (isset($_REQUEST["inputDataAccPlace"]) && $_REQUEST["inputDataAccPlace"] != "") ? $_REQUEST['inputDataAccPlace'] : "";
  $strDataDateAccident = (isset($_REQUEST["dataDateAccident"]) && $_REQUEST["dataDateAccident"] != "") ? $_REQUEST['dataDateAccident'] : "";
  $strDataAccTime = (isset($_REQUEST["inputDataAccTime"]) && $_REQUEST["inputDataAccTime"] != "") ? $_REQUEST['inputDataAccTime'] : "";
  $strDataAccType = (isset($_REQUEST["inputDataAccType"]) && $_REQUEST["inputDataAccType"] != "") ? $_REQUEST['inputDataAccType'] : "NULL";
  $strDataAccFirstQ = (isset($_REQUEST["inputDataAccFirstQ"]) && $_REQUEST["inputDataAccFirstQ"] != "") ? $_REQUEST['inputDataAccFirstQ'] : "";
  $strDataAccCause = (isset($_REQUEST["inputDataAccCause"]) && $_REQUEST["inputDataAccCause"] != "") ? $_REQUEST['inputDataAccCause'] : "";
  $strDataAftermath = (isset($_REQUEST["inputDataAftermath"]) && $_REQUEST["inputDataAftermath"] != "") ? $_REQUEST['inputDataAftermath'] : "0";
  $strDataDamagedBodyParts = (isset($_REQUEST["inputDataDamagedBodyParts"]) && $_REQUEST["inputDataDamagedBodyParts"] != "") ? $_REQUEST['inputDataDamagedBodyParts'] : "";
  $strDataMedicalDoctorName = (isset($_REQUEST["inputDataMedicalDoctorName"]) && $_REQUEST["inputDataMedicalDoctorName"] != "") ? $_REQUEST['inputDataMedicalDoctorName'] : "";
  $strDataMedicalDoctorAddress = (isset($_REQUEST["inputDataMedicalDoctorAddress"]) && $_REQUEST["inputDataMedicalDoctorAddress"] != "") ? $_REQUEST['inputDataMedicalDoctorAddress'] : "";
  $strDataPatientStatus = (isset($_REQUEST["inputDataPatientStatus"]) && $_REQUEST["inputDataPatientStatus"] != "") ? $_REQUEST['inputDataPatientStatus'] : "NULL";
  $strDataAccID = (isset($_REQUEST["inputDataAccID"]) && $_REQUEST["inputDataAccID"] != "") ? $_REQUEST['inputDataAccID'] : "";
  $strDataExpectedLossTime = (isset($_REQUEST["inputDataExpectedLossTime"]) && $_REQUEST["inputDataExpectedLossTime"] != "") ? $_REQUEST['inputDataExpectedLossTime'] : "0";
  $strDataExpectedLossStamp = (isset($_REQUEST["inputDataExpectedLossStamp"]) && $_REQUEST["inputDataExpectedLossStamp"] != "") ? $_REQUEST['inputDataExpectedLossStamp'] : "NULL";
  $strDataExpectedLossWH = (isset($_REQUEST["inputDataExpectedLossWH"]) && $_REQUEST["inputDataExpectedLossWH"] != "") ? $_REQUEST['inputDataExpectedLossWH'] : "0";
  $strDataNote = (isset($_REQUEST["dataNote"]) && $_REQUEST["dataNote"] != "") ? $_REQUEST['dataNote'] : "";
  if ($strDataEmployee == "") {
    $strError = $error['empty_code'];
    $bolOK = false;
  } else if (!validStandardDate($strDataDate)) {
    $strError = $error['invalid_date'];
    $bolOK = false;
  } else if (!validStandardDate($strDataDateAccident)) {
    $strError = $error['invalid_date'];
    $bolOK = false;
  } else if (substr_count($strDataNote, "'")) {
    $strError = $error['invalid_text'];
    $bolOK = false;
  }
  if (!$bolOK) {
    return $bolOK;
  }
  $strDataIDEmployee = getEmployeeInfoByCode($db, $strDataEmployee, "id");
  $strDataIDEmployee = $strDataIDEmployee['id'];
  //data lama: update
  if ($strDataID != "") {
    $strSQL = "
      UPDATE hrd_jamsostek_claim_kk2 SET
      id_employee = $strDataIDEmployee,
      irregular = $strDataIrregularIncome,
      money_rewards = $strDataMoneyRewards,
      acc_place = '$strDataAccPlace',
      acc_date = '$strDataDateAccident',
      acc_time = '$strDataAccTime',
      acc_type = $strDataAccType,
      acc_question1_answer = '$strDataAccFirstQ',
      acc_question2_answer = '$strDataAccCause',
      acc_aftermath = $strDataAftermath,
      damaged_body_parts = '$strDataDamagedBodyParts',
      doctor_name = '$strDataMedicalDoctorName',
      doctor_address = '$strDataMedicalDoctorAddress',
      patient_status = $strDataPatientStatus,
      acc_id = '$strDataAccID',
      expected_time = $strDataExpectedLossTime,
      expected_stamp = $strDataExpectedLossStamp,
      expected_workhour = $strDataExpectedLossWH,
      note = '$strDataNote',
      date_request = '$strDataDate',
      labor_wage = $strDataLaborWage
      WHERE id = $strDataID";
    $strSQL = str_replace("''", "NULL", $strSQL);
    $res = $db->execute($strSQL);
  } else { //data baru: insert
    $strSQL = "INSERT INTO hrd_jamsostek_claim_kk2
      (id_employee, irregular, money_rewards, acc_place, acc_date, acc_time, acc_type,
      acc_question1_answer, acc_question2_answer, acc_aftermath, damaged_body_parts,
      doctor_name, doctor_address, patient_status, acc_id, expected_time, expected_stamp,
      expected_workhour, note, date_request, labor_wage)
      
      VALUES
      ($strDataIDEmployee, $strDataIrregularIncome, $strDataMoneyRewards, '$strDataAccPlace', '$strDataDateAccident', '$strDataAccTime', $strDataAccType,
      '$strDataAccFirstQ', '$strDataAccCause', $strDataAftermath, '$strDataDamagedBodyParts',
      '$strDataMedicalDoctorName', '$strDataMedicalDoctorAddress', $strDataPatientStatus, '$strDataAccID', '$strDataExpectedLossTime', $strDataExpectedLossStamp,
      $strDataExpectedLossWH, '$strDataNote', '$strDataDate', $strDataLaborWage)
      
      RETURNING id";
    $strSQL = str_replace("''", "NULL", $strSQL);
    $res = $db->execute($strSQL);
    $rowDb = $db->fetchrow($res);
    $strDataID = $rowDb['id'];
  }
  $strError = $messages['data_saved'];
  return true;
} // saveData
// fungsi untuk mengambil data karyawan
function getInfo($db, $strEmployeeID = "")
{
  global $words;
  global $arrData;
  if ($strEmployeeID != "") {
    $strSQL = "
                  SELECT 
                  t2.id, t2.employee_name, t2.employee_id, t2.primary_address, t2.primary_phone, t2.birthplace, t2.birthday, t2.jamsostek_no, t2.primary_zip, t2.gender,
                  t3.position_name,
                  t4.department_name
                  FROM hrd_employee AS t2 
                  LEFT JOIN hrd_position AS t3 ON t3.position_code = t2.position_code
                  LEFT JOIN hrd_department AS t4 ON t4.department_code = t2.department_code
                  WHERE t2.employee_id = '$strEmployeeID'
                  ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataEmployee'] = (isset($rowDb['employee_id']) && $rowDb["employee_id"] != "") ? $rowDb['employee_id'] : "";
      $arrData['dataEmployeeName'] = (isset($rowDb['employee_name']) && $rowDb["employee_name"] != "") ? $rowDb['employee_name'] : "";
      $arrData['dataEmployeeAddress'] = (isset($rowDb['primary_address']) && $rowDb["primary_address"] != "") ? $rowDb['primary_address'] : "";
      $arrData['dataEmployeeZip'] = (isset($rowDb['primary_zip']) && $rowDb["primary_zip"] != "") ? $rowDb['primary_zip'] : "";
      $arrData['dataEmployeePhone'] = (isset($rowDb['primary_phone']) && $rowDb["primary_phone"] != "") ? $rowDb['primary_phone'] : "";
      $arrData['dataEmployeeGender'] = (isset($rowDb['gender']) && $rowDb["gender"] != "") ? $rowDb['gender'] : "";
      $arrData['dataEmployeeBirthdate'] = (isset($rowDb['birthday']) && $rowDb["birthday"] != "") ? $rowDb['birthday'] : "";
      $arrData['dataEmployeeBirthplace'] = (isset($rowDb['birthplace']) && $rowDb["birthplace"] != "") ? $rowDb['birthplace'] : "";
      $arrData['dataEmployeeJamsostekID'] = (isset($rowDb['jamsostek_no']) && $rowDb["jamsostek_no"] != "") ? $rowDb['jamsostek_no'] : "";
      $arrData['dataEmployeePosition'] = (isset($rowDb['position_name']) && $rowDb["position_name"] != "") ? $rowDb['position_name'] : "";
      $arrData['dataEmployeeDepartment'] = (isset($rowDb['department_name']) && $rowDb["department_name"] != "") ? $rowDb['department_name'] : "";
    }
  }
  return true;
} // getInfo
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $strUserRole = $_SESSION['sessionUserRole'];
  if (isset($_REQUEST['dataID'])) {
    $bolIsNew = false;
    $strDataID = $_REQUEST['dataID'];
  } else {
    $strDataID = "";
    $bolIsNew = true;
  }
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      $bolOK = saveData($db, $strDataID, $strError);
      $strMessages = $strError;
      $strMsgClass = ($bolOK) ? "class = bgOK" : "class = bgError";
    }
  }
  $dtNow = getdate();
  $arrData['dataMonth'] = getRomans($dtNow['mon']);
  $arrData['dataYear'] = $dtNow['year'];
  //$strInputLastNo = getLastFormNumber($db, "hrd_absence", "no", $arrData['dataMonth'], $arrData['dataYear']);
  //$intLastNo = ($strInputLastNo == "") ? 0 : (int)$strInputLastNo;
  //$arrData['dataNo'] = addPrevZero($intLastNo + 1,$intFormNumberDigit);
  if (isset($_REQUEST['btnGetInfo'])) {
    getInfo($db, $_REQUEST['dataEmployee']);
    //_debug_array($_REQUEST);
  }
  getData($db, $strDataID);
  $strIDEmployee = getIDEmployee($db, $arrData['dataEmployee']);
  //----- TAMPILKAN DATA ---------
  //see common_function.php
  $strReadonly = (scopeGeneralDataEntry(
      $arrData['dataEmployee'],
      $_SESSION['sessionUserRole'],
      $arrUserInfo,
      $bolIsNew
  )) ? "readonly" : "";
  $strSyncScript = "";
  $strSyncScript .= "<script type=text/javascript>";
  $strInputEmployeeName = "";//<input type=text name=inputDataEmployeeName id=inputDataEmployeeName value=".$arrData['dataEmployeeName']."></input>";
  $strInputJamsostekCardID = $arrData['dataEmployeeJamsostekID'];
  $strInputEmployeeAddress = $arrData['dataEmployeeAddress'];
  $strInputZipCode = $arrData['dataEmployeeZip'];
  $strInputPhone = $arrData['dataEmployeePhone'];
  $strInputBirthdate = $arrData['dataEmployeeBirthdate'];
  $strInputBirthplace = $arrData['dataEmployeeBirthplace'];
  $strInputGender = $arrData['dataEmployeeGender'];
  $strInputPosition = $arrData['dataEmployeePosition'];
  $strInputDepartment = $arrData['dataEmployeeDepartment'];
  $strInputAccidentPlace = "<input type=text name=inputDataAccPlace id=inputDataAccPlace value=" . $arrData['dataAccPlace'] . "></input>";
  $strInputAccidentTime = "<input type=text name=inputDataAccTime id=inputDataAccTime value=" . $arrData['dataAccTime'] . "></input>";
  $strInputAccidentAftermath = "<input type=radio name=inputDataAftermath id=inputDataAftermath0 value=0>" . getWords(
          "death"
      ) . "</input><br>
                                    <input type=radio name=inputDataAftermath id=inputDataAftermath1 value=1>" . getWords(
          "sick"
      ) . "</input><br>
                                    <input type=radio name=inputDataAftermath id=inputDataAftermath2 value=2>" . getWords(
          "wounded"
      ) . "</input><br>";
  $tempAftermath = $arrData['dataAftermath'];
  if (empty($tempAftermath)) {
    $tempAftermath = 0;
  }
  $strSyncScript .= "function syncDataAftermath(){
                      var aftermath = " . $tempAftermath . ";
                      if(aftermath == 1) document.getElementById('inputDataAftermath1').checked = true;
                      else if(aftermath == 2) document.getElementById('inputDataAftermath2').checked = true;
                      else document.getElementById('inputDataAftermath0').checked = true;
                      };
                      syncDataAftermath();";
  $strInputDamagedBodyParts = "<textarea name=inputDataDamagedBodyParts cols=30 rows=3 wrap='virtual'>" . $arrData['dataDamagedBodyParts'] . "</textarea>";
  $strInputMedicalDoctorName = "<input type=text name=inputDataMedicalDoctorName id=inputDataMedicalDoctorName value=" . $arrData['dataMedicalDoctorName'] . "></input>";
  $strInputMedicalDoctorAddress = "<textarea name=inputDataMedicalDoctorAddress cols=30 rows=3 wrap='virtual'>" . $arrData['dataMedicalDoctorAddress'] . "</textarea>";
  $strInputPatientStatus = "<input type=radio name=inputDataPatientStatus id=inputDataPatientStatus0 value=0>" . getWords(
          "outpatient"
      ) . " : " . getWords("work") . "</input><br>
                                    <input type=radio name=inputDataPatientStatus id=inputDataPatientStatus1 value=1>" . getWords(
          "outpatient"
      ) . " : " . getWords("does not work") . "</input><br>
                                    <input type=radio name=inputDataPatientStatus id=inputDataPatientStatus2 value=2>" . getWords(
          "inpatient"
      ) . " : " . getWords("hospital") . "</input><br>
                                    <input type=radio name=inputDataPatientStatus id=inputDataPatientStatus3 value=3>" . getWords(
          "inpatient"
      ) . " : " . getWords("puskesmas") . "</input><br>
                                    <input type=radio name=inputDataPatientStatus id=inputDataPatientStatus4 value=4>" . getWords(
          "inpatient"
      ) . " : " . getWords("poliklinik") . "</input><br>";
  $tempPS = $arrData['dataPatientStatus'];
  if (empty($tempPS)) {
    $tempPS = 0;
  }
  $strSyncScript .= "function syncPatientStatus(){
                        var patientstatus = " . $tempPS . ";
                        if(patientstatus == 1) document.getElementById('inputDataPatientStatus1').checked = true;
                        else if(patientstatus == 2) document.getElementById('inputDataPatientStatus2').checked = true;
                        else if(patientstatus == 3) document.getElementById('inputDataPatientStatus3').checked = true;
                        else if(patientstatus == 4) document.getElementById('inputDataPatientStatus4').checked = true;
                        else document.getElementById('inputDataPatientStatus0').checked = true;
                      };
                      syncPatientStatus();";
  $strInputAccidentID = "<input type=text name=inputDataAccID id=inputDataAccID value=" . $arrData['dataAccID'] . "></input>";
  $strInputExpectedLosses_Time = "<input onchange=syncTime(); type=text name=inputDataExpectedLossTime id=inputDataExpectedLossTime value=" . $arrData['dataExpectedLossTime'] . "></input>";
  $strSyncScript .= "function syncTime(){
                        var paid = document.getElementById('inputDataExpectedLossTime');
                        
                        paid_value = parseInt(paid.value);
                        if(isNaN(paid_value))
                        {
                          paid_value = 0;
                          paid.value = paid_value;
                        }
                      };syncTime();";
  $strInputExpectedLosses_Stamp = "<input onchange=syncStamp(); type=text name=inputDataExpectedLossStamp id=inputDataExpectedLossStamp value=" . $arrData['dataExpectedLossStamp'] . "></input>";
  $strSyncScript .= "function syncStamp(){
                        var paid = document.getElementById('inputDataExpectedLossStamp');
                        
                        paid_value = parseInt(paid.value);
                        if(isNaN(paid_value))
                        {
                          paid_value = 0;
                          paid.value = paid_value;
                        }
                      };syncStamp();";
  $strInputExpectedLosses_WH = "<input onchange=syncWH(); type=text name=inputDataExpectedLossWH id=inputDataExpectedLossWH value=" . $arrData['dataExpectedLossWH'] . "></input>";
  $strSyncScript .= "function syncWH(){
                        var paid = document.getElementById('inputDataExpectedLossWH');
                        
                        paid_value = parseInt(paid.value);
                        if(isNaN(paid_value))
                        {
                          paid_value = 0;
                          paid.value = paid_value;
                        }
                      };syncWH();";
  $strInputLaborWage = "<input type=radio name=inputDataLaborWage id=inputDataLaborWage0 value=0>" . getWords(
          "one day"
      ) . "</input>
                                    <input type=radio name=inputDataLaborWage id=inputDataLaborWage1 value=1>" . getWords(
          "one month"
      ) . "</input>
                                    <input type=radio name=inputDataLaborWage id=inputDataLaborWage2 value=2>" . getWords(
          "contract"
      ) . "</input>";
  $tempLW = $arrData['dataLaborWage'];
  if (empty($tempLW)) {
    $tempLW = 0;
  }
  $strSyncScript .= "function syncLaborWage(){
                        var laborwage = " . $tempLW . ";
                        if(laborwage == 1) document.getElementById('inputDataLaborWage1').checked = true;
                        else if(laborwage == 2) document.getElementById('inputDataLaborWage2').checked = true;
                        else document.getElementById('inputDataLaborWage0').checked = true;
                      };
                      syncLaborWage();";
  $strInputMoneyRewards = "<input type=text name=inputDataMoneyRewards id=inputDataMoneyRewards value='" . $arrData['dataMoneyRewards'] . "' onchange='calc_total()'></input>";
  $strInputIrregularIncome = "<input type=text name=inputDataIrregularIncome id=inputDataIrregularIncome value='" . $arrData['dataIrregularIncome'] . "' onchange='calc_total()'></input>";
  $strSyncScript .= "function calc_total(){
                        var mr_src = document.getElementById('inputDataMoneyRewards');
                        var ii_src = document.getElementById('inputDataIrregularIncome');
                        mr = parseInt(mr_src.value);
                        ii = parseInt(ii_src.value);
                        if(isNaN(mr))
                        {
                          mr = 0;
                          mr_src.value = mr;
                        }
                        if(isNaN(ii))
                        {
                          ii = 0;
                          ii_src.value = ii;
                        }
                        
                        setTotalValue(mr + ii);
                      };";
  $strInputTotal_ab = "<span id=inputDataTotal name=inputDataTotal></span>";
  $strSyncScript .= "function setTotalValue(value){
                        var span = document.getElementById('inputDataTotal');

                        while( span.firstChild ) {
                          span.removeChild( span.firstChild );
                        }
                        span.appendChild( document.createTextNode(value) );
                      };";
  $strInputAccidentType = "<input type=radio onchange='setFirstQuestion(0)' name=inputDataAccType id=inputDataAccType0 value=0>" . getWords(
          "accident"
      ) . "</input><br>
                                    <input type=radio onchange='setFirstQuestion(1)' name=inputDataAccType id=inputDataAccType1 value=1>" . getWords(
          "illness caused from workplace"
      ) . "</input><br>";
  $tempAccType = $arrData['dataAccType'];
  if (empty($tempAccType)) {
    $tempAccType = 0;
  }
  $strSyncScript .= "function syncDataAccType(){
                      var acctype = " . $tempAccType . ";
                      if(acctype == 1) document.getElementById('inputDataAccType1').checked = true;
                      else document.getElementById('inputDataAccType0').checked = true;
                      setFirstQuestion(acctype);
                      };
                      syncDataAccType();";
  $strSyncScript .= "function setFirstQuestion(value){
                        var span = document.getElementById('wordsAccidentFQ');

                        while( span.firstChild ) {
                          span.removeChild( span.firstChild );
                        }
                        
                        if(value == 0)
                        {
                          value = '" . getWords("how the accident happen") . "';
                        }else{
                          value = '" . getWords("mention any illness caused from workplace") . "';
                        }
                        
                        span.appendChild( document.createTextNode(value) );
                      };";
  $strInputAccidentFirstQ = "<textarea name=inputDataAccFirstQ cols=30 rows=3 wrap='virtual' style=\"width:$strDefaultWidthPx\">" . $arrData['dataQuestion1'] . "</textarea>";
  $strInputAccidentCause = "<textarea name=inputDataAccCause cols=30 rows=3 wrap='virtual' style=\"width:$strDefaultWidthPx\">" . $arrData['dataCause'] . "</textarea>";
  $strDisabled = (isset($_REQUEST['dataID']) && $_REQUEST['dataID'] != "") ? "disabled" : "";
  $strBtnInputGetInfo = "<input type=submit id='btnGetInfo' name='btnGetInfo' value='Get Info' $strDisabled>";
  $strInputDate = "<input type=hidden size=15 maxlength=10 name=dataDate id=dataDate value=\"" . $arrData['dataDate'] . "\" >" . $arrData['dataDate'];
  $strInputAccidentDate = "<input type=text size=15 maxlength=10 name=dataDateAccident id=dataDateAccident value=\"" . $arrData['dataDateAccident'] . "\">";
  $strReadonly = (isset($_REQUEST['dataID']) && $_REQUEST['dataID'] != "") ? "readonly" : "";
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=10 maxlength=30 value=\"" . $arrData['dataEmployee'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly >";
  $strInputNote = "<textarea name=dataNote cols=30 rows=3 wrap='virtual' style=\"width:$strDefaultWidthPx\">" . $arrData['dataNote'] . "</textarea>";
  $strSyncScript .= "</script>";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
if ($bolPrint) {
  $strMainTemplate = getTemplate("absence_edit_print.html");
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>