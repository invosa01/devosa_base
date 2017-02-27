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
$strWordsJamsostekClaimEntry = getWords("jamsostek kk3 claim entry");
$strWordsJamsostekClaimList = getWords("jamsostek kk3 claim list");
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
$strWordsKK2SubmitDate = "KK2 " . getWords("submit date");
$strWordsCostTransport = getWords("transport to hospital / employee's house cost") . " (IDR)";
$strWordsCostTreatment = getWords("treatment for employee cost") . " (IDR)";
$strWordsCostProthese = getWords("prothese / orthese cost") . " (IDR)";
$strWordsCostFuneral = getWords("funeral cost") . " (IDR)";
$strWordsCostTotal = getWords("total") . " (IDR)";
$strWordsSTMB = "STMB " . getWords("compensation") . " (IDR)";
$strWordsRecvName = getWords("receiver name");
$strWordsRecvAddress = getWords("receiver address");
$strWordsPatientStatus = getWords("employee status") . "<br>&nbsp;" . getWords("based on medical document");
$strWordsDatePatientStatusDetermined = getWords("date determined");
$strWordsDoc = getWords("upload medical document");
$strWordsPaid = getWords("company have paid") . " (IDR)";
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
    "dataDate"                => $strNow,
    "dataDateAccident"        => $strNow,
    "dataDateKK2Submit"       => $strNow,
    "dataDateDetermined"      => $strNow,
    "dataEmployee"            => "",
    "dataEmployeeName"        => "",
    "dataEmployeeAddress"     => "",
    "dataEmployeeZip"         => "",
    "dataEmployeePhone"       => "",
    "dataEmployeeGender"      => "",
    "dataEmployeeBirthdate"   => "",
    "dataEmployeeBirthplace"  => "",
    "dataEmployeePosition"    => "",
    "dataEmployeeDepartment"  => "",
    "dataEmployeeJamsostekID" => "",
    "dataCostTransport"       => "0",
    "dataCostTreatment"       => "0",
    "dataCostProthese"        => "0",
    "dataCostFuneral"         => "0",
    "dataSTMB_a"              => "0",
    "dataSTMB_b"              => "0",
    "dataSTMB_c"              => "0",
    "dataAccTime"             => "",
    "dataAccPlace"            => "",
    "dataRecvName"            => "",
    "dataRecvAddress"         => "",
    "dataPatientStatus"       => "0",
    "dataDoc"                 => "",
    "dataPaid"                => "",
    "dataNote"                => "",
    "dataID"                  => "",
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
                  FROM hrd_jamsostek_claim_kk3 AS t1
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
      $arrData['dataAccPlace'] = (isset($rowDb['acc_place']) && $rowDb['acc_place'] != "") ? $rowDb['acc_place'] : "";
      $arrData['dataDateAccident'] = (isset($rowDb['acc_date']) && $rowDb['acc_date'] != "") ? $rowDb['acc_date'] : "";
      $arrData['dataAccTime'] = (isset($rowDb['acc_time']) && $rowDb['acc_time'] != "") ? $rowDb['acc_time'] : "";
      $arrData['dataDateKK2Submit'] = (isset($rowDb['kk2_submit_date']) && $rowDb['kk2_submit_date'] != "") ? $rowDb['kk2_submit_date'] : "";
      $arrData['dataDateDetermined'] = (isset($rowDb['date_determined']) && $rowDb['date_determined'] != "") ? $rowDb['date_determined'] : "";
      $arrData['dataCostTransport'] = (isset($rowDb['cost_transport']) && $rowDb['cost_transport'] != "") ? $rowDb['cost_transport'] : "0";
      $arrData['dataCostTreatment'] = (isset($rowDb['cost_treatment']) && $rowDb['cost_treatment'] != "") ? $rowDb['cost_treatment'] : "0";
      $arrData['dataCostProthese'] = (isset($rowDb['cost_prothese']) && $rowDb['cost_prothese'] != "") ? $rowDb['cost_prothese'] : "0";
      $arrData['dataCostFuneral'] = (isset($rowDb['cost_funeral']) && $rowDb['cost_funeral'] != "") ? $rowDb['cost_funeral'] : "0";
      $arrData['dataSTMB_a'] = (isset($rowDb['stmb_a']) && $rowDb['stmb_a'] != "") ? $rowDb['stmb_a'] : "";
      $arrData['dataSTMB_b'] = (isset($rowDb['stmb_b']) && $rowDb['stmb_b'] != "") ? $rowDb['stmb_b'] : "";
      $arrData['dataSTMB_c'] = (isset($rowDb['stmb_c']) && $rowDb['stmb_c'] != "") ? $rowDb['stmb_c'] : "";
      $arrData['dataRecvName'] = (isset($rowDb['recv_name']) && $rowDb['recv_name'] != "") ? $rowDb['recv_name'] : "";
      $arrData['dataRecvAddress'] = (isset($rowDb['recv_address']) && $rowDb['recv_address'] != "") ? $rowDb['recv_address'] : "";
      $arrData['dataPatientStatus'] = (isset($rowDb['patient_status']) && $rowDb['patient_status'] != "") ? $rowDb['patient_status'] : "";
      $arrData['dataDoc'] = (isset($rowDb['doc_kk4']) && $rowDb['doc_kk4'] != "") ? $rowDb['doc_kk4'] : "";
      //UNTUK MENAMPILKAN DOCUMENT
      global $strDataDoc;
      //global $strWordsDeleteFile;
      if ($arrData['dataDoc'] == "") {
        $strDataDoc = "";
      } else {
        if (file_exists("jamsostek_kk3/" . $arrData['dataDoc'])) {
          $strDataDoc = "<a href=\"jamsostek_kk3/" . $arrData['dataDoc'] . "\" target=\"_blank\" > <img  src='jamsostek_kk3/" . $arrData['dataDoc'] . "' alt=\"" . $arrData['dataDoc'] . "\"></a>&nbsp;&nbsp;";
        } else {
          $strDataDoc = "";
        }
      }
      //$strFileOption = "<td>&nbsp;</td><td>&nbsp;</td><td><span id=\"doc\">&nbsp;".$strDataDoc."</span>";
      //if($strDataDoc != "") $strFileOption .= "<input name=\"btnDeleteDoc\" type=\"button\" id=\"btnDelete\" value=\"$strWordsDeleteFile\" onClick=\"deleteFile($strDataID);\"></td>";
      //$strFileOption .= "<input type=hidden id='syllabusDoc' name='syllabusDoc' value=$row[doc]>";
      //SEDIKIT DESPERATE, selesai
      $arrData['dataPaid'] = (isset($rowDb['paid']) && $rowDb['paid'] != "") ? $rowDb['paid'] : "";
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
  global $_FILES;
  global $_SESSION;
  global $error;
  global $messages;
  global $arrData;
  global $arrUserInfo;
  //_debug_array($_REQUEST);
  $strError = "";
  $bolOK = true;
  $strToday = date("Y-m-d");
  $strDataDate = (isset($_REQUEST["dataDate"]) && $_REQUEST["dataDate"] != "") ? $_REQUEST["dataDate"] : "";
  $strDataEmployee = (isset($_REQUEST["dataEmployee"]) && $_REQUEST["dataEmployee"] != "") ? $_REQUEST["dataEmployee"] : "";
  $strDataAccPlace = (isset($_REQUEST["inputAccPlace"]) && $_REQUEST["inputAccPlace"] != "") ? $_REQUEST["inputAccPlace"] : "";
  $strDataDateAcc = (isset($_REQUEST["dataDateAccident"]) && $_REQUEST["dataDateAccident"] != "") ? $_REQUEST["dataDateAccident"] : "";
  $strDataAccTime = (isset($_REQUEST["inputAccTime"]) && $_REQUEST["inputAccTime"] != "") ? $_REQUEST["inputAccTime"] : "NULL";
  $strDataDateKK2 = (isset($_REQUEST["inputDateKK2Submit"]) && $_REQUEST["inputDateKK2Submit"] != "") ? $_REQUEST["inputDateKK2Submit"] : "";
  $strDataCostTrans = (isset($_REQUEST["inputCostTransport"]) && $_REQUEST["inputCostTransport"] != "") ? $_REQUEST["inputCostTransport"] : "0";
  $strDataCostTreat = (isset($_REQUEST["inputCostTreatment"]) && $_REQUEST["inputCostTreatment"] != "") ? $_REQUEST["inputCostTreatment"] : "0";
  $strDataCostProth = (isset($_REQUEST["inputCostProthese"]) && $_REQUEST["inputCostProthese"] != "") ? $_REQUEST["inputCostProthese"] : "0";
  $strDataCostFuner = (isset($_REQUEST["inputCostFuneral"]) && $_REQUEST["inputCostFuneral"] != "") ? $_REQUEST["inputCostFuneral"] : "0";
  $strDataSTMB_a = (isset($_REQUEST["inputSTMB_a"]) && $_REQUEST["inputSTMB_a"] != "") ? $_REQUEST["inputSTMB_a"] : "0";
  $strDataSTMB_b = (isset($_REQUEST["inputSTMB_b"]) && $_REQUEST["inputSTMB_b"] != "") ? $_REQUEST["inputSTMB_b"] : "0";
  $strDataSTMB_c = (isset($_REQUEST["inputSTMB_c"]) && $_REQUEST["inputSTMB_c"] != "") ? $_REQUEST["inputSTMB_c"] : "0";
  $strDataRecvName = (isset($_REQUEST["inputRecvName"]) && $_REQUEST["inputRecvName"] != "") ? $_REQUEST["inputRecvName"] : "";
  $strDataRecvAddr = (isset($_REQUEST["inputRecvAddress"]) && $_REQUEST["inputRecvAddress"] != "") ? $_REQUEST["inputRecvAddress"] : "";
  $strDataPatientStats = (isset($_REQUEST["inputPatientStatus"]) && $_REQUEST["inputPatientStatus"] != "") ? $_REQUEST["inputPatientStatus"] : "0";
  $strDataDateDetermined = (isset($_REQUEST["inputDatePatientStatusDetermined"]) && $_REQUEST["inputDatePatientStatusDetermined"] != "") ? $_REQUEST["inputDatePatientStatusDetermined"] : "NULL";
  $strDataPaid = (isset($_REQUEST["inputPaid"]) && $_REQUEST["inputPaid"] != "") ? $_REQUEST["inputPaid"] : "";
  $strDataNote = (isset($_REQUEST["dataNote"]) && $_REQUEST["dataNote"] != "") ? $_REQUEST["dataNote"] : "";
  if ($strDataEmployee == "") {
    $strError = $error['empty_code'];
    $bolOK = false;
  } else if (!validStandardDate($strDataDate)) {
    $strError = $error['invalid_date'];
    $bolOK = false;
  } else if (!validStandardDate($strDataDateAcc)) {
    $strError = $error['invalid_date'];
    $bolOK = false;
  } else if (!validStandardDate($strDataDateDetermined)) {
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
      UPDATE hrd_jamsostek_claim_kk3 SET
      id_employee = $strDataIDEmployee,
      acc_place = '$strDataAccPlace',
      acc_date = '$strDataDateAcc',
      acc_time = $strDataAccTime,
      kk2_submit_date = '$strDataDateKK2',
      cost_transport = $strDataCostTrans,
      cost_treatment = $strDataCostTreat,
      cost_prothese = $strDataCostProth,
      cost_funeral = $strDataCostFuner,
      stmb_a = $strDataSTMB_a,
      stmb_b = $strDataSTMB_b,
      stmb_c = $strDataSTMB_c,
      recv_name = '$strDataRecvName',
      recv_address = '$strDataRecvAddr',
      patient_status = $strDataPatientStats,
      date_determined = '$strDataDateDetermined',
      paid = $strDataPaid,
      date_request = '$strDataDate',
      note = '$strDataNote'
      WHERE id = $strDataID";
    $strSQL = str_replace("''", "NULL", $strSQL);
    $res = $db->execute($strSQL);
  } else { //data baru: insert
    $strSQL = "INSERT INTO hrd_jamsostek_claim_kk3
      (id_employee, acc_place, acc_date, acc_time, 
      kk2_submit_date, cost_transport, cost_treatment, cost_prothese, cost_funeral, 
      stmb_a, stmb_b, stmb_c, recv_name, recv_address,
      patient_status, date_determined, doc_kk4, paid, date_request, note)
      
      VALUES
      ($strDataIDEmployee, '$strDataAccPlace', '$strDataDateAcc', $strDataAccTime,
      '$strDataDateKK2', $strDataCostTrans, $strDataCostTreat, $strDataCostProth, $strDataCostFuner, 
      $strDataSTMB_a, $strDataSTMB_b, $strDataSTMB_c, '$strDataRecvName', '$strDataRecvAddr',
      $strDataPatientStats, '$strDataDateDetermined', 'NULL', $strDataPaid, '$strDataDate', '$strDataNote')
      
      RETURNING id";
    $strSQL = str_replace("''", "NULL", $strSQL);
    $res = $db->execute($strSQL);
    $rowDb = $db->fetchrow($res);
    $strDataID = $rowDb['id'];
  }
  $strError = $messages['data_saved'];
  // simpan data doc, jika ada
  if ($strDataID != "") {
    //cek jika file kosong
    if ($_FILES["detailDoc"]['name'] != "") {
      if (is_uploaded_file($_FILES["detailDoc"]['tmp_name'])) {
        $arrNamaFile = explode(".", $_FILES["detailDoc"]['name']);
        $strNamaFile = $strDataID . "_" . $_FILES["detailDoc"]['name'];
        if (strlen($strNamaFile) > 40) {
          $strNamaFile = substr($strNamaFile, 0, 40);
        }
        $strNamaFile .= "";
        clearstatcache();
        if (!is_dir("jamsostek_kk3")) {
          mkdir("jamsostek_kk3", 0777);
        }
        $strNamaFileLengkap = "jamsostek_kk3/" . $strNamaFile;
        if (file_exists($strNamaFileLengkap)) {
          unlink($strNamaFileLengkap);
        }
        // move_uploaded_file($_FILES["detailDoc"]["tmp_name"], "absencedoc/" . $_FILES["detailDoc"]["name"]);
        move_uploaded_file($_FILES["detailDoc"]['tmp_name'], $strNamaFileLengkap);
        // update data
        $strSQL = "UPDATE hrd_jamsostek_claim_kk3 SET doc_kk4 = '$strNamaFile' WHERE id = '$strDataID' ";
        $resExec = $db->execute($strSQL);
      }
    }
  }
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
  $strInputAccidentPlace = "<input type=text name=inputAccPlace id=inputAccPlace value=" . $arrData['dataAccPlace'] . "></input>";
  $strInputAccidentTime = "<input type=text name=inputAccTime id=inputAccTime value=" . $arrData['dataAccTime'] . "></input>";
  $strInputKK2SubmitDate = "<input type=text name=inputDateKK2Submit id=inputDateKK2Submit value=" . $arrData['dataDateKK2Submit'] . "></input>";
  $strInputRecvName = "<input type=text name=inputRecvName id=inputRecvName value=" . $arrData['dataRecvName'] . "></input>";
  $strInputRecvAddress = "<textarea name=inputRecvAddress cols=30 rows=3 wrap='virtual'>" . $arrData['dataRecvAddress'] . "</textarea>";
  $strInputPatientStatus = "<input type=radio name=inputPatientStatus id=inputPatientStatus0 value=0>" . getWords(
          "cannot work until"
      ) . " 'Date Determined'</input><br>
                                    <input type=radio name=inputPatientStatus id=inputPatientStatus1 value=1>" . getWords(
          "permanently disabled"
      ) . "</input><br>
                                    <input type=radio name=inputPatientStatus id=inputPatientStatus2 value=2>" . getWords(
          "permanently disabled, physically or mentally"
      ) . "</input><br>
                                    <input type=radio name=inputPatientStatus id=inputPatientStatus3 value=3>" . getWords(
          "died"
      ) . "</input><br>";
  $tempPS = $arrData['dataPatientStatus'];
  if (empty($tempPS)) {
    $tempPS = 0;
  }
  $strSyncScript .= "function syncPatientStatus(){
                        var patientstatus = " . $tempPS . ";
                        if(patientstatus == 1) document.getElementById('inputPatientStatus1').checked = true;
                        else if(patientstatus == 2) document.getElementById('inputPatientStatus2').checked = true;
                        else if(patientstatus == 3) document.getElementById('inputPatientStatus3').checked = true;
                        else document.getElementById('inputPatientStatus0').checked = true;
                      };
                      syncPatientStatus();";
  $strInputDatePatientStatusDetermined = "<input type=text name=inputDatePatientStatusDetermined id=inputDatePatientStatusDetermined value=" . $arrData['dataDateDetermined'] . "></input>";
  $strInputCostTransport = "<input type=text name=inputCostTransport id=inputCostTransport value='" . $arrData['dataCostTransport'] . "' onchange='calc_total()'></input>";
  $strInputCostTreatment = "<input type=text name=inputCostTreatment id=inputCostTreatment value='" . $arrData['dataCostTreatment'] . "' onchange='calc_total()'></input>";
  $strInputCostProthese = "<input type=text name=inputCostProthese id=inputCostProthese value='" . $arrData['dataCostProthese'] . "' onchange='calc_total()'></input>";
  $strInputCostFuneral = "<input type=text name=inputCostFuneral id=inputCostFuneral value='" . $arrData['dataCostFuneral'] . "' onchange='calc_total()'></input>";
  $strSyncScript .= "function calc_total(){
                        var cost_trans = document.getElementById('inputCostTransport');
                        var cost_treat = document.getElementById('inputCostTreatment');
                        var cost_proth = document.getElementById('inputCostProthese');
                        var cost_funer = document.getElementById('inputCostFuneral');
                        
                        trans = parseInt(cost_trans.value);
                        if(isNaN(trans))
                        {
                          trans = 0;
                          cost_trans.value = trans;
                        }
                        
                        treat = parseInt(cost_treat.value);
                        if(isNaN(treat))
                        {
                          treat = 0;
                          cost_treat.value = treat;
                        }
                        
                        proth = parseInt(cost_proth.value);
                        if(isNaN(proth))
                        {
                          proth = 0;
                          cost_proth.value = proth;
                        }
                        
                        funer = parseInt(cost_funer.value);
                        if(isNaN(funer))
                        {
                          funer = 0;
                          cost_funer.value = funer;
                        }
                        
                        setTotalValue(trans + treat + proth + funer);
                      };";
  $strInputCostTotal = "<span id=inputDataTotal name=inputDataTotal></span>";
  $strSyncScript .= "function setTotalValue(value){
                        var span = document.getElementById('inputDataTotal');

                        while( span.firstChild ) {
                          span.removeChild( span.firstChild );
                        }
                        span.appendChild( document.createTextNode(value) );
                      };";
  $strInputSTMB_a = "<input type=text name=inputSTMB_a id=inputSTMB_a value='" . $arrData['dataSTMB_a'] . "' onchange='syncSTMB()'></input>";
  $strInputSTMB_b = "<input type=text name=inputSTMB_b id=inputSTMB_b value='" . $arrData['dataSTMB_b'] . "' onchange='syncSTMB()'></input>";
  $strInputSTMB_c = "<input type=text name=inputSTMB_c id=inputSTMB_c value='" . $arrData['dataSTMB_c'] . "' onchange='syncSTMB()'></input>";
  $strSyncScript .= "function syncSTMB(){
                        var a = document.getElementById('inputSTMB_a');
                        var b = document.getElementById('inputSTMB_b');
                        var c = document.getElementById('inputSTMB_c');
                        
                        a_value = parseInt(a.value);
                        if(isNaN(a_value))
                        {
                          a_value = 0;
                          a.value = a_value;
                        }
                        
                        b_value = parseInt(b.value);
                        if(isNaN(a_value))
                        {
                          b_value = 0;
                          b.value = b_value;
                        }
                        
                        c_value = parseInt(c.value);
                        if(isNaN(c_value))
                        {
                          c_value = 0;
                          c.value = c_value;
                        }
                      };syncSTMB();";
  $strInputPaid = "<input type=text name=inputPaid id=inputPaid value='" . $arrData['dataPaid'] . "' onchange='syncPaid()'></input>";
  $strSyncScript .= "function syncPaid(){
                        var paid = document.getElementById('inputPaid');
                        
                        paid_value = parseInt(paid.value);
                        if(isNaN(paid_value))
                        {
                          paid_value = 0;
                          paid.value = paid_value;
                        }
                      };syncPaid();";
  $strDisabled = (isset($_REQUEST['dataID']) && $_REQUEST['dataID'] != "") ? "disabled" : "";
  $strBtnInputGetInfo = "<input type=submit id='btnGetInfo' name='btnGetInfo' value='Get Info' $strDisabled>";
  $strInputDate = "<input type=hidden size=15 maxlength=10 name=dataDate id=dataDate value=\"" . $arrData['dataDate'] . "\" >" . $arrData['dataDate'];
  $strInputAccidentDate = "<input type=text size=15 maxlength=10 name=dataDateAccident id=dataDateAccident value=\"" . $arrData['dataDateAccident'] . "\">";
  $strReadonly = (isset($_REQUEST['dataID']) && $_REQUEST['dataID'] != "") ? "readonly" : "";
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=10 maxlength=30 value=\"" . $arrData['dataEmployee'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly >";
  $strInputNote = "<textarea name=dataNote cols=30 rows=3 wrap='virtual' style=\"width:$strDefaultWidthPx\">" . $arrData['dataNote'] . "</textarea>";
  $strInputDoc = "<input name=\"detailDoc\" type=\"file\" id=\"detailDoc\"></td></tr>";
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