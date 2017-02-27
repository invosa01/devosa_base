<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once("../includes/krumo/class.krumo.php");
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
$strDataDetail = "";
$intDefaultWidth = 30;
$intDefaultHeight = 3;
$strNow = date("Y-m-d");
$strMessages = "";
$strMsgClass = "";
$bolError = false;
$strBtnPrint = "";
$strWordsProposalDate = getWords("proposal date");
$strWordsEmployeeID = getWords("employee id");
$strWordsLetterCode = getWords("letter code");
$strWordsEmployeeStatusConfirmation = getWords("employee status confirmation");
$strWordsEmployeeStatus = getWords("employee status");
$strWordsDateFrom = getWords("date from");
$strWordsStartDate = getWords("start date");
$strWordsStartDate = getWords("start date");
$strWordsEmployeeDpartmentChanges = getWords("employee department changes");
$strWordsEmployeeSalaryChanges = getWords("employee salary changes");
$strWordsPositionAllow = getwords("position allowance");
$strWordsMealAllow = getwords("meal allowance");
$strWordsTransportAllow = getwords("transport allowance");
$strWordsVehicleAllow = getwords("vehicle allowance");
$strWordsBasicSalary = getwords("basic salary");
$strWordsRecentDepartment = getWords("recent department");
$strWordsNewDepartment = getWords("new department");
$strWordsNote = getWords("note");
$strWordsStatus = getWords("status");
$strWordsManagement = getWords("management");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("sub section");
$strWordsRecent = getWords("recent");
$strWordsNew = getWords("new");
$strWordsPromotion = getWords("promotion");
$strWordsProposalEntry = getWords("proposal entry");
$strWordsProposalList = getWords("proposal list");
$strWordsGetInfo = getWords("get info");
$strWordsUntil = getWords("until");
$strWordsSave = getWords("save");
$strWordsClearForm = getWords("clear form");
$arrData = [
    "dataDate"         => $strNow,
    "dataEmployee"     => "",
    "dataLetterCode"   => "",
    "dataIsStatus"     => false, // aapkah ada perubahan status
    "dataIsResign"     => false, // apakah ada pemberhentian
    "dataIsPosition"   => false, // apakah ada perubahan jabatan
    "dataIsDepartment" => false, // apakah ada perubahan department
    "dataIsSalary"     => false, // apakah ada perubahan gaji
    "dataStatusNew"      => "", // status karyawan yang lama
    "dataStatusDateFrom" => "",//$strNow,
    "dataStatusDateThru" => "",//$strNow,
    /*
	"dataPositionOld" => "",
    "dataPositionNew" => "",
    "dataGradeOld" => "",
    "dataGradeNew" => "",
    "dataPositionOldDate" => "",//$strNow,
    "dataPositionNewDate" => date("Y-m-d"),//$strNow,
    */
    "dataManagementOld"     => "",
    "dataManagementNew"     => "",
    "dataDivisionOld"       => "",
    "dataDivisionNew"       => "",
    "dataDepartmentOld"     => "",
    "dataDepartmentNew"     => "",
    "dataSectionOld"        => "",
    "dataSectionNew"        => "",
    "dataSubSectionOld"     => "",
    "dataSubSectionNew"     => "",
    "dataDepartmentNewDate" => "",
    "dataBasicSalaryOld"   => "",
    "dataBasicSalaryNew"   => "",
    "dataPositionAllowOld" => "",
    "dataPositionAllowNew" => "",
    "dataMealOld"          => "",
    "dataMealNew"          => "",
    "dataTransportOld"     => "",
    "dataTransportNew"     => "",
    "dataVehicleOld"       => "",
    "dataVehicleNew"       => "",
    "dataStartOldDate"     => "",
    "dataStartNewDate"     => "",
    "dataStatus" => "0", //status dari proposal (baru, verifikasi, setuju, tolak)
    "dataNote"   => "",
    "dataID"     => "",
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
  //print_r ($strSQL);
  if ($strDataID != "") {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "ID=$strDataID", 0);
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.join_date,  ";
    $strSQL .= "t2.management_code, t2.division_code, t2.department_code, t2.section_code, t2.sub_section_code, ";
    $strSQL .= "t2.grade_code, t2.employee_status, t2.position_code ";
    $strSQL .= "FROM hrd_employee_mutation AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataEmployee'] = $rowDb['employee_id'];
      $arrData['dataID'] = $rowDb['id'];
      $arrData['dataDate'] = $rowDb['proposal_date'];
      $arrData['dataLetterCode'] = $rowDb['letter_code'];
      $arrData['dataStatus'] = $rowDb['status'];
      $arrData['dataNote'] = $rowDb['note'];
      $strStatus = $rowDb['status'];
      $arrData['dataEmployeeName'] = $rowDb['employee_name'];
      $arrData['dataPosition'] = $rowDb['position_code'];
      //$arrData['dataGrade'] = $rowDb['grade_code'];
      $arrData['dataEmployeeStatus'] = $rowDb['employee_status'];
      $arrData['dataJoinDate'] = $rowDb['join_date'];
      $arrData['dataManagement'] = $rowDb['management_code'];
      $arrData['dataDivision'] = $rowDb['division_code'];
      $arrData['dataDepartment'] = $rowDb['department_code'];
      $arrData['dataSection'] = $rowDb['section_code'];
      $arrData['dataSubSection'] = $rowDb['sub_section_code'];
      // cari status
      $strSQL = "SELECT * FROM hrd_employee_mutation_status WHERE id_mutation = '$strDataID' ";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $arrData['dataIsStatus'] = true;
        $arrData['dataStatusNew'] = $rowTmp['status_new'];
        $arrData['dataStatusDateFrom'] = $rowTmp['status_date_from'];
        $arrData['dataStatusDateThru'] = $rowTmp['status_date_thru'];
      }
      //print_r($strSQL);
      /*
              // cari resign
              $strSQL  = "SELECT * FROM hrd_employee_mutation_resign WHERE id_mutation = '$strDataID' ";
              $resTmp = $db->execute($strSQL);
              if ($rowTmp = $db->fetchrow($resTmp)) {
                $arrData['dataIsResign'] = true;
                $arrData['dataResignDate'] = $rowTmp['resign_date'];
              }
      */
      // cari salary
      $strSQL = "SELECT * FROM hrd_employee_mutation_salary WHERE id_mutation = '$strDataID' ";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $arrData['dataIsSalary'] = true;
        $arrData['dataBasicSalaryOld'] = (float)$rowTmp['basic_salary_old'];
        $arrData['dataBasicSalaryNew'] = (float)$rowTmp['basic_salary_new'];
        $arrData['dataPositionAllowOld'] = (float)$rowTmp['position_allow_old'];
        $arrData['dataPositionAllowNew'] = (float)$rowTmp['position_allow_new'];
        $arrData['dataMealNew'] = (float)$rowTmp['meal_allow_new'];
        $arrData['dataMealOld'] = (float)$rowTmp['meal_allow_old'];
        $arrData['dataTransportOld'] = (float)$rowTmp['transport_allow_old'];
        $arrData['dataTransportNew'] = (float)$rowTmp['transport_allow_new'];
        $arrData['dataVehicleOld'] = (float)$rowTmp['vehicle_allow_old'];
        $arrData['dataVehicleNew'] = (float)$rowTmp['vehicle_allow_new'];
        $arrData['dataStartNewDate'] = $rowTmp['salary_new_date'];
        $arrData['dataStartOldDate'] = $rowTmp['salary_old_date'];
      }
      // cari posiition
      /*
      $strSQL  = "SELECT * FROM hrd_employee_mutation_position WHERE id_mutation = '$strDataID' ";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $arrData['dataIsPosition'] = true;
        $arrData['dataPositionOld'] = $rowTmp['position_old'];
        $arrData['dataPositionNew'] = $rowTmp['position_new'];
        $arrData['dataGradeOld'] = $rowTmp['grade_old'];
        $arrData['dataGradeNew'] = $rowTmp['grade_new'];
        $arrData['dataPositionOldDate'] = $rowTmp['position_old_date'];
        $arrData['dataPositionNewDate'] = $rowTmp['position_new_date'];
      }*/
      // cari department
      $strSQL = "SELECT * FROM hrd_employee_mutation_department WHERE id_mutation = '$strDataID' ";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $arrData['dataIsDepartment'] = true;
        $arrData['dataManagementOld'] = $rowTmp['management_old'];
        $arrData['dataManagementNew'] = $rowTmp['management_new'];
        $arrData['dataDivisionOld'] = $rowTmp['division_old'];
        $arrData['dataDivisionNew'] = $rowTmp['division_new'];
        $arrData['dataDepartmentOld'] = $rowTmp['department_old'];
        $arrData['dataDepartmentNew'] = $rowTmp['department_new'];
        $arrData['dataSectionOld'] = $rowTmp['section_old'];
        $arrData['dataSectionNew'] = $rowTmp['section_new'];
        $arrData['dataSubSectionOld'] = $rowTmp['sub_section_old'];
        $arrData['dataSubSectionNew'] = $rowTmp['sub_section_new'];
        $arrData['dataDepartmentNewDate'] = $rowTmp['department_new_date'];
      }
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
  global $strStatus;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strError = "";
  $bolOK = true;
  $strToday = date("Y-m-d");
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = trim($_REQUEST['dataEmployee']) : $strDataEmployee = "";
  (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = "";
  (isset($_REQUEST['dataLetterCode'])) ? $strDataLetterCode = $_REQUEST['dataLetterCode'] : $strDataLetterCode = "";
  (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
  $strDateDepartment = (isset($_REQUEST['dataDepartmentNewDate'])) ? $_REQUEST['dataDepartmentNewDate'] : "";
  $strDateFrom = (isset($_REQUEST['dataStatusDateFrom'])) ? $_REQUEST['dataStatusDateFrom'] : "";
  $strDateThru = (isset($_REQUEST['dataStatusDateThru'])) ? $_REQUEST['dataStatusDateThru'] : "";
  $strSalaryOldDate = (isset($_REQUEST['dataStartOldDate'])) ? $_REQUEST['dataStartOldDate'] : "";
  $strSalaryNewDate = (isset($_REQUEST['dataStartNewDate'])) ? $_REQUEST['dataStartNewDate'] : "";
  // cek validasi -----------------------
  if ($strDataEmployee == "") {
    $strError = $error['empty_code'];
    $bolOK = false;
  } else if (!validStandardDate($strDataDate)) {
    $strError = $error['invalid_date'];
    $bolOK = false;
  } else if (!validStandardDate($strDateFrom) && isset($_REQUEST['dataIsStatus'])) {
    $strError = $error['invalid_date'];
    $bolOK = false;
  }
  if (!validStandardDate($strDateDepartment) && isset($_REQUEST['dataIsDepartment'])) {
    $strError = $error['invalid_date'];
    $bolOK = false;
  }
  if ((!validStandardDate($strSalaryOldDate) || (!validStandardDate(
              $strSalaryNewDate
          ))) && isset($_REQUEST['dataIsSalary'])
  ) {
    $strError = $error['invalid_date'];
    $bolOK = false;
  }
  // cari dta Employee ID, apakah ada atau tidak
  // $strSQL  = "SELECT id FROM hrd_employee WHERE employee_id = '$strDataEmployee' AND flag = 0 ";
  $strSQL = "SELECT id FROM hrd_employee WHERE employee_id = '$strDataEmployee' ";
  // FIX: Kalau di set flag = 0 tidak bisa karena banyak data yang flag = null. Mungkin pada saat pertama entry data flag tidak masuk
  //echo($strSQL);
  //$strSQL  = "SELECT id FROM hrd_employee WHERE employee_id = '$strDataEmployee'";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strIDEmployee = $rowDb['id'];
  } else {
    $strIDEmployee = "";
  }
  if ($strIDEmployee == "") {
    $strError = $error['data_not_found'];
    $bolOK = false;
  }
  if ($strDataID != "") {
    $strSQL = "SELECT id, status FROM hrd_employee_mutation ";
    $strSQL .= "WHERE id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strStatus = $rowDb['status'];
    } else {
      $strStatus = 0;
    }
  }
  if ($strStatus == 3) {
    $bolOK = false;
    $strSQL = "UPDATE hrd_employee_mutation ";
    $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
    $strSQL .= "note = '$strDataNote', letter_code = '$strDataLetterCode'  WHERE id = '$strDataID' ";
    $resExec = $db->execute($strSQL);
  }
  // simpan data -----------------------
  if ($bolOK) { // input OK, tinggal disimpan
    if ($strDataID == "") {
      // data baru
      $strSQL = "INSERT INTO hrd_employee_mutation (created,created_by,modified_by, ";
      $strSQL .= "id_employee,proposal_date, note, letter_code, status, type) ";
      $strSQL .= "VALUES(now(),'$strmodified_byID','$strmodified_byID', ";
      $strSQL .= "'$strIDEmployee','$strDataDate', '$strDataNote', '$strDataLetterCode', " . REQUEST_STATUS_NEW . ", 0)  ";
      $resExec = $db->execute($strSQL);
      // cari ID
      $strSQL = "SELECT id FROM hrd_employee_mutation ";
      $strSQL .= "WHERE id_employee = '$strIDEmployee' AND proposal_date = '$strDataDate' ";
      $strSQL .= "AND type = 0 AND status = " . REQUEST_STATUS_NEW;
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $strDataID = $rowDb['id'];
      }
    } else {
      $strSQL = "UPDATE hrd_employee_mutation ";
      $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
      $strSQL .= "id_employee = '$strIDEmployee', proposal_date = '$strDataDate', ";
      $strSQL .= "note = '$strDataNote', letter_code = '$strDataLetterCode'  WHERE id = '$strDataID' ";
      $resExec = $db->execute($strSQL);
    }
    // simpan data detilnya, jika ada
    if ($strDataID != "") {
      //hapus dulu semua data
      $strSQL = "DELETE FROM hrd_employee_mutation_status WHERE id_mutation = '$strDataID'; ";
      $strSQL .= "DELETE FROM hrd_employee_mutation_department WHERE id_mutation = '$strDataID'; ";
      $strSQL .= "DELETE FROM hrd_employee_mutation_position WHERE id_mutation = '$strDataID'; ";
      //$strSQL  = "DELETE FROM hrd_employee_mutation_resign WHERE id_mutation = '$strDataID'; ";
      $strSQL .= "DELETE FROM hrd_employee_mutation_salary WHERE id_mutation = '$strDataID'; ";
      $resDb = $db->execute($strSQL);
      if (isset($_REQUEST['dataIsStatus'])) {
        // simpan data status
        $strEmployeeStatus = (isset($_REQUEST['dataStatusNew'])) ? $_REQUEST['dataStatusNew'] : 0;
        $strDateFrom = ($strDateFrom == "") ? "NULL" : "'$strDateFrom'";
        $strDateThru = ($strDateThru == "") ? "NULL" : "'$strDateThru'";
        $strSQL = "INSERT INTO hrd_employee_mutation_status (created, modified_by, created_by, ";
        $strSQL .= "id_mutation, status_new, status_date_from, status_date_thru) ";
        $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '$strDataID', ";
        $strSQL .= "'$strEmployeeStatus', $strDateFrom, $strDateThru) ";
        $resExec = $db->execute($strSQL);
      }
      /*
      if (isset($_REQUEST['dataIsResign'])) {
        // simpan data resign
        $strDate = (isset($_REQUEST['dataResignDate'])) ? $_REQUEST['dataResignDate'] : "";

        $strDate = ($strDate == "") ? "NULL" : "'$strDate'";

        $strSQL  = "INSERT INTO hrd_employee_mutation_resign (created, modified_by, created_by, ";
        $strSQL .= "id_mutation, resign_date) ";
        $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '$strDataID', $strDate) ";
        $resExec = $db->execute($strSQL);

      }
      */
      if (isset($_REQUEST['dataIsSalary'])) {
        // simpan data salary
        $strBasicSalaryOld = (isset($_REQUEST['dataBasicSalaryOld'])) ? $_REQUEST['dataBasicSalaryOld'] : 0;
        $strBasicSalaryNew = (isset($_REQUEST['dataBasicSalaryNew'])) ? $_REQUEST['dataBasicSalaryNew'] : 0;
        $strPositionAllowOld = (isset($_REQUEST['dataPositionAllowOld'])) ? $_REQUEST['dataPositionAllowOld'] : 0;
        $strPositionAllowNew = (isset($_REQUEST['dataPositionAllowNew'])) ? $_REQUEST['dataPositionAllowNew'] : 0;
        $strMealOld = (isset($_REQUEST['dataMealOld'])) ? $_REQUEST['dataMealOld'] : 0;
        $strMealNew = (isset($_REQUEST['dataMealNew'])) ? $_REQUEST['dataMealNew'] : 0;
        $strTransportOld = (isset($_REQUEST['dataTransportOld'])) ? $_REQUEST['dataTransportOld'] : 0;
        $strTransportNew = (isset($_REQUEST['dataTransportNew'])) ? $_REQUEST['dataTransportNew'] : 0;
        $strVehicleOld = (isset($_REQUEST['dataVehicleOld'])) ? $_REQUEST['dataVehicleOld'] : 0;
        $strVehicleNew = (isset($_REQUEST['dataVehicleNew'])) ? $_REQUEST['dataVehicleNew'] : 0;
        if (!is_numeric($strBasicSalaryNew)) {
          $strBasicSalaryNew = 0;
        }
        if (!is_numeric($strPositionAllowNew)) {
          $strPositionAllowNew = 0;
        }
        if (!is_numeric($strMealNew)) {
          $strMealNew = 0;
        }
        if (!is_numeric($strTransportNew)) {
          $strTransportNew = 0;
        }
        if (!is_numeric($strVehicleNew)) {
          $strVehicleNew = 0;
        }
        $strSQL = "INSERT INTO hrd_employee_mutation_salary (created, modified_by, created_by, ";
        $strSQL .= "id_mutation, salary_old_date, salary_new_date, basic_salary_old, basic_salary_new, position_allow_old, ";
        $strSQL .= "position_allow_new, meal_allow_old, meal_allow_new, transport_allow_old, ";
        $strSQL .= "transport_allow_new, vehicle_allow_new, vehicle_allow_old ) ";
        $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '$strDataID', ";
        $strSQL .= "'$strSalaryOldDate', '$strSalaryNewDate', '$strBasicSalaryOld', '$strBasicSalaryNew', ";
        $strSQL .= "'$strPositionAllowOld', '$strPositionAllowNew', '$strMealOld', '$strMealNew', '$strTransportOld', ";
        $strSQL .= "'$strTransportNew', '$strVehicleNew', '$strVehicleOld')";
        //echo $strSQL;
        $resExec = $db->execute($strSQL);
      }
      /*
      if (isset($_REQUEST['dataIsPosition'])) {
        // simpan data status
        $strPositionOld = (isset($_REQUEST['dataPositionOld'])) ? $_REQUEST['dataPositionOld'] : "";
        $strPositionNew = (isset($_REQUEST['dataPositionNew'])) ? $_REQUEST['dataPositionNew'] : "";
        //$strGradeOld = (isset($_REQUEST['dataGradeOld'])) ? $_REQUEST['dataGradeOld'] : "";
        //$strGradeNew = (isset($_REQUEST['dataGradeNew'])) ? $_REQUEST['dataGradeNew'] : "";
        $strDateOld = (isset($_REQUEST['dataPositionOldDate'])) ? $_REQUEST['dataPositionOldDate'] : "";
        $strDateNew = (isset($_REQUEST['dataPositionNewDate'])) ? $_REQUEST['dataPositionNewDate'] : "";

        $strDateOld = ($strDateOld == "") ? "NULL" : "'$strDateOld'";
        $strDateNew = ($strDateNew == "") ? "NULL" : "'$strDateNew'";

        $strSQL  = "INSERT INTO hrd_employee_mutation_position (created, modified_by, created_by, ";
        $strSQL .= "id_mutation, position_old, position_new, grade_old, ";
        $strSQL .= "grade_new, \"position_old_date\", \"position_new_date\") ";
        $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '$strDataID', ";
        $strSQL .= "'$strPositionOld', '$strPositionNew', '$strGradeOld', '$strGradeNew', ";
        $strSQL .= "$strDateOld, $strDateNew) ";
        $resExec = $db->execute($strSQL);

      }*/
      if (isset($_REQUEST['dataIsDepartment'])) {
        // simpan data status
        $strManagementOld = (isset($_REQUEST['dataManagementOld'])) ? $_REQUEST['dataManagementOld'] : "";
        $strManagementNew = (isset($_REQUEST['dataManagementNew'])) ? $_REQUEST['dataManagementNew'] : "";
        $strDivisionOld = (isset($_REQUEST['dataDivisionOld'])) ? $_REQUEST['dataDivisionOld'] : "";
        $strDivisionNew = (isset($_REQUEST['dataDivisionNew'])) ? $_REQUEST['dataDivisionNew'] : "";
        $strDepartmentOld = (isset($_REQUEST['dataDepartmentOld'])) ? $_REQUEST['dataDepartmentOld'] : "";
        $strDepartmentNew = (isset($_REQUEST['dataDepartmentNew'])) ? $_REQUEST['dataDepartmentNew'] : "";
        $strSectionOld = (isset($_REQUEST['dataSectionOld'])) ? $_REQUEST['dataSectionOld'] : "";
        $strSectionNew = (isset($_REQUEST['dataSectionNew'])) ? $_REQUEST['dataSectionNew'] : "";
        $strSubSectionOld = (isset($_REQUEST['dataSubSectionOld'])) ? $_REQUEST['dataSubSectionOld'] : "";
        $strSubSectionNew = (isset($_REQUEST['dataSubSectionNew'])) ? $_REQUEST['dataSubSectionNew'] : "";
        $strDepartmentNewDate = (isset($_REQUEST['dataDepartmentNewDate'])) ? $_REQUEST['dataDepartmentNewDate'] : date(
            "Y-m-d"
        );
        $strSQL = "INSERT INTO hrd_employee_mutation_department (created, modified_by, created_by, ";
        $strSQL .= "id_mutation, ";
        $strSQL .= "management_old, management_new, ";
        $strSQL .= "division_old, division_new, ";
        $strSQL .= "department_old, department_new, ";
        $strSQL .= "section_old, section_new, ";
        $strSQL .= "sub_section_old, sub_section_new, department_new_date)";
        $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '$strDataID', ";
        $strSQL .= "'$strManagementOld','$strManagementNew', ";
        $strSQL .= "'$strDivisionOld','$strDivisionNew', ";
        $strSQL .= "'$strDepartmentOld','$strDepartmentNew', ";
        $strSQL .= "'$strSectionOld','$strSectionNew', ";
        $strSQL .= "'$strSubSectionOld','$strSubSectionNew','$strDepartmentNewDate')";
        $resExec = $db->execute($strSQL);
      }
    }
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "MUTATION DATA", 0);
    $strError = $messages['data_saved'];
  } else { // ---- data SALAH
    // gunakan data yang diisikan tadi
    $arrData['dataEmployee'] = $strDataEmployee;
    $arrData['dataDate'] = $strDataDate;
    $arrData['dataLetterCode'] = $strDataLetterCode;
    $arrData['dataNote'] = $strDataNote;
    $arrData['dataID'] = $strDataID;
    getInfoEmployee($db);
    $arrData['dataIsDepartment'] = (isset($_REQUEST['dataIsDepartment'])) ? "true" : "";
    $arrData['dataIsPosition'] = (isset($_REQUEST['dataIsPosition'])) ? "true" : "";
    $arrData['dataIsSalary'] = (isset($_REQUEST['dataIsSalary'])) ? "true" : "";
    $arrData['dataIsStatus'] = (isset($_REQUEST['dataIsStatus'])) ? "true" : "";
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "ABSENCE DATA", 0);
  }
  return $bolOK;
} // saveData
// fungsi untuk mengambil data employee yang terakhir
function getInfoEmployee($db)
{
  global $_REQUEST;
  global $_SESSION;
  global $arrData;
  $strID = (isset($_REQUEST['dataEmployee'])) ? $_REQUEST['dataEmployee'] : "";
  //(isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = trim($_REQUEST['dataEmployee']) : $strDataEmployee = "";
  (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = "";
  (isset($_REQUEST['dataLetterCode'])) ? $strDataLetterCode = $_REQUEST['dataLetterCode'] : $strDataLetterCode = "";
  (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
  if ($strID != "") {
    $arrData['dataEmployee'] = $strID;
    $arrData['dataDate'] = $strDataDate;
    $arrData['dataLetterCode'] = $strDataLetterCode;
    $arrData['dataNote'] = $strDataNote;
    $strSQL = "SELECT * FROM hrd_employee WHERE employee_id = '$strID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['employeeName'] = $rowDb['employee_name'];
      $arrData['dataStatusNew'] = $rowDb['employee_status'];
      if ($rowDb['employee_status'] == 1) { // permanent
        $arrData['dataStatusDateFrom'] = $rowDb['permanent_date'];
        $arrData['dataStatusDateThru'] = "";
      } else {
        $arrData['dataStatusDateFrom'] = $rowDb['join_date'];
        $arrData['dataStatusDateThru'] = $rowDb['due_date'];
      }
      //$arrData['dataPositionOld']    = $rowDb['position_code'];
      //$arrData['dataGradeOld']       = $rowDb['grade_code'];
      $arrData['dataManagementOld'] = $rowDb['management_code'];
      $arrData['dataDivisionOld'] = $rowDb['division_code'];
      $arrData['dataDepartmentOld'] = $rowDb['department_code'];
      $arrData['dataSectionOld'] = $rowDb['section_code'];
      $arrData['dataSubSectionOld'] = $rowDb['sub_section_code'];
      // cari data history masing-masing
      $strSQL = "SELECT t2.approved_time::date AS approved_date FROM hrd_employee_mutation_position AS t1,  ";
      $strSQL .= "hrd_employee_mutation AS t2 WHERE t1.id_mutation = t2.id ";
      $strSQL .= "AND t2.id_employee = '" . $rowDb['id'] . "' AND t2.status = " . MUTATION_STATUS_APPROVED;
      $strSQL .= " ORDER BY t2.proposal_date DESC LIMIT 1 ";
      $resTmp = $db->execute($strSQL);
      //echo $strSQL;
      /*
      if ($rowTmp = $db->fetchrow($resTmp))
      {
        $arrData['dataPositionOldDate'] = $rowTmp['approved_date'];
      }
      else
      {
        $arrData['dataPositionOldDate'] = $rowDb['join_date'];
      }*/
      $idSalarySet = "";
      // cari data salary
      $strSQL = "SELECT id, start_date FROM hrd_basic_salary_set ";
      $strSQL .= "WHERE id_company = '" . $rowDb['id_company'] . "' ";
      $resTmp1 = $db->execute($strSQL);
      while ($rowTmp = $db->fetchrow($resTmp1)) {
        $strSQL = "SELECT basic_salary, position_allowance, meal_allowance, transport_allowance, vehicle_allowance, start_date FROM hrd_employee_basic_salary AS t1 ";
        $strSQL .= "LEFT JOIN hrd_basic_salary_set AS t2 ON t1.id_salary_set = t2.id ";
        $strSQL .= "WHERE id_salary_set = '$rowTmp[id]' ";
        $strSQL .= "AND id_employee = '" . $rowDb['id'] . "' ";
        //$strSQL .= "ORDER BY t1.salary_new_date DESC LIMIT 1 ";
        $resTmp = $db->execute($strSQL);
        if ($rowTmp = $db->fetchrow($resTmp)) {
          $arrData['dataBasicSalaryOld'] = (float)$rowTmp['basic_salary'];
          $arrData['dataPositionAllowOld'] = $rowTmp['position_allowance'];
          $arrData['dataMealOld'] = $rowTmp['meal_allowance'];
          $arrData['dataTransportOld'] = $rowTmp['transport_allowance'];
          $arrData['dataVehicleOld'] = $rowTmp['vehicle_allowance'];
          $arrData['dataStartOldDate'] = $rowTmp['start_date'];
        }
      }
    }
  }
}

// fungsi untuk mengambil info sstatus karyawan, untuk perpanjangan
function getEmployeeStatusInfo($db)
{
  global $_REQUEST;
  global $arrData;
  $strID = (isset($_REQUEST['dataIDEmployee'])) ? $_REQUEST['dataIDEmployee'] : "";
  if ($strID === "") {
    return false;
  }
  $strSQL = "SELECT * FROM hrd_employee WHERE id = '$strID' AND flag = 0 ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $arrData['dataEmployee'] = $rowDb['employee_id'];
    $arrData['employeeName'] = $rowDb['employee_name'];
    $arrData['dataStatusNew'] = $rowDb['employee_status'];
    $arrData['dataStatusDateFrom'] = getNextDate($rowDb['due_date']);
    $arrData['dataStatusDateThru'] = $arrData['dataStatusDateFrom'];
    $arrData['dataIsStatus'] = true;
  }
  return false;
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    if (!$arrUserInfo['isDeptHead'] && !$arrUserInfo['isGroupHead']) {
      $bolCanDelete = $bolCanEdit = $bolCanView = false;
    }
  }
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      $bolOK = saveData($db, $strError);
      if ($strError != "") {
        $strMessages = $strError;
        $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
      }
      //$strDataID = ""; // biar tidak mengambil dta, melainkan pakai data baru atau data yang dikirim (jika error)
    }
  }
  if (isset($_REQUEST['btnGet'])) {
    // ambil info tentang employee
    getInfoEmployee($db);
  } else if (isset($_REQUEST['btnRenew'])) {
    getEmployeeStatusInfo($db);
  }
  //$arrData['dataEmployee'] = $arrUserInfo['employee_id']; // beri default user
  if ($bolCanView) {
    getData($db);
  } else {
    showError("view_denied");
  }
  //----- TAMPILKAN DATA ---------
  $strReadonly = ($arrData['dataStatus'] == 3) ? "readonly" : ""; // kalau dah approve, jadi readonly
  $strStatus = $arrData['dataStatus'];
  //echo $strStatus;
  $strInputDate = "<input class=\"form-control datepicker\" type=text maxlength=10 name=dataDate id=dataDate value=\"" . $arrData['dataDate'] . "\" $strReadonly data-date-format=\"yyyy-mm-dd\">";
  $strInputStatusDateFrom = "<input class=\"form-control datepicker\" type=text maxlength=10 name=dataStatusDateFrom id=dataStatusDateFrom value=\"" . $arrData['dataStatusDateFrom'] . "\" data-date-format=\"yyyy-mm-dd\" $strReadonly>";
  $strInputStatusDateThru = "<input class=\"form-control datepicker\" type=text maxlength=10 name=dataStatusDateThru id=dataStatusDateThru value=\"" . $arrData['dataStatusDateThru'] . "\" data-date-format=\"yyyy-mm-dd\" $strReadonly>";
  $strInputDepartmentNewDate = "<input class=\"form-control datepicker\" type=text maxlength=10 name=dataDepartmentNewDate id=dataDepartmentNewDate value=\"" . $arrData['dataDepartmentNewDate'] . "\" data-date-format=\"yyyy-mm-dd\" $strReadonly>";
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee maxlength=30 value=\"" . $arrData['dataEmployee'] . "\" class=\"form-control\" $strReadonly>";
  $strInputLetterCode = "<input type=\"text\" name=dataLetterCode maxlength=63 value=\"" . $arrData['dataLetterCode'] . "\" class=\"form-control\" >";
  $strInputNote = "<textarea name=dataNote cols=30 rows=3 wrap='virtual' class=\"form-control\" >" . $arrData['dataNote'] . "</textarea>";
  $strInputBasicSalaryOld = "<input type=text name=dataBasicSalaryOld id=dataBasicSalaryOld size=20  value=\"" . $arrData['dataBasicSalaryOld'] . "\"  $strReadonly>";
  $strInputBasicSalaryNew = "<input type=text name=dataBasicSalaryNew id=dataBasicSalaryNew size=20 value=\"" . $arrData['dataBasicSalaryNew'] . "\" $strReadonly>";
  $strInputPositionAllowOld = "<input type=text name=dataPositionAllowOld id=dataPositionAllowOld size=20  value=\"" . $arrData['dataPositionAllowOld'] . "\" $strReadonly>";
  $strInputPositionAllowNew = "<input type=text name=dataPositionAllowNew id=dataPositionAllowNew size=20 value=\"" . $arrData['dataPositionAllowNew'] . "\"  $strReadonly>";
  $strInputMealOld = "<input type=text name=dataMealOld id=dataMealOld size=20  value=\"" . $arrData['dataMealOld'] . "\"  $strReadonly>";
  $strInputMealNew = "<input type=text name=dataMealNew id=dataMealNew size=20  value=\"" . $arrData['dataMealNew'] . "\" $strReadonly>";
  $strInputTransportOld = "<input type=text name=dataTransportOld id=dataTransportOld size=20  value=\"" . $arrData['dataTransportOld'] . "\"  $strReadonly>";
  $strInputTransportNew = "<input type=text name=dataTransportNew id=dataTransportNew size=20  value=\"" . $arrData['dataTransportNew'] . "\"  $strReadonly>";
  $strInputVehicleOld = "<input type=text name=dataVehicleOld id=dataVehicleOld size=20 maxlength=30 value=\"" . $arrData['dataVehicleOld'] . "\"  $strReadonly>";
  $strInputVehicleNew = "<input type=text name=dataVehicleNew id=dataVehicleNew size=20 maxlength=30 value=\"" . $arrData['dataVehicleNew'] . "\"  $strReadonly>";
  $strInputStartOldDate = "<input type=text name=dataStartOldDate id=dataStartOldDate size=20 maxlength=30 value=\"" . $arrData['dataStartOldDate'] . "\"  $strReadonly>";
  $strInputStartNewDate = "<input type=text name=dataStartNewDate id=dataStartNewDate size=20 maxlength=30 value=\"" . $arrData['dataStartNewDate'] . "\"  $strReadonly>";
  //$strInputPositionOld = getPositionList($db,"dataPositionOld",$arrData['dataPositionOld'], "$strEmptyOption", "", " style=\"width:$strDefaultWidthPx \" $strReadonly");
  //$strInputPositionNew = getPositionList($db,"dataPositionNew",$arrData['dataPositionNew'], "$strEmptyOption", "", " style=\"width:$strDefaultWidthPx \" $strReadonly");
  //$strInputGradeOld = getSalaryGradeList($db,"dataGradeOld",$arrData['dataGradeOld'], "$strEmptyOption", "", " style=\"width:$strDefaultWidthPx \" $strReadonly");
  //$strInputGradeNew = getSalaryGradeList($db,"dataGradeNew",$arrData['dataGradeNew'], "$strEmptyOption", "", " style=\"width:$strDefaultWidthPx \" $strReadonly");
  $strInputManagementOld = getManagementList(
      $db,
      "dataManagementOld",
      $arrData['dataManagementOld'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputManagementNew = getManagementList(
      $db,
      "dataManagementNew",
      $arrData['dataManagementNew'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputDivisionOld = getDivisionList(
      $db,
      "dataDivisionOld",
      $arrData['dataDivisionOld'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputDivisionNew = getDivisionList(
      $db,
      "dataDivisionNew",
      $arrData['dataDivisionNew'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" onChange=\"checkDivision()\" $strReadonly"
  );
  $strInputDepartmentOld = getDepartmentList(
      $db,
      "dataDepartmentOld",
      $arrData['dataDepartmentOld'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputDepartmentNew = getDepartmentList(
      $db,
      "dataDepartmentNew",
      $arrData['dataDepartmentNew'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" onChange=\"checkDepartment()\" $strReadonly"
  );
  $strInputSectionOld = getSectionList(
      $db,
      "dataSectionOld",
      $arrData['dataSectionOld'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputSectionNew = getSectionList(
      $db,
      "dataSectionNew",
      $arrData['dataSectionNew'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" onChange=\"checkSection()\" $strReadonly"
  );
  $strInputSubSectionOld = getSubSectionList(
      $db,
      "dataSubSectionOld",
      $arrData['dataSubSectionOld'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" $strReadonly"
  );
  $strInputSubSectionNew = getSubSectionList(
      $db,
      "dataSubSectionNew",
      $arrData['dataSubSectionNew'],
      "$strEmptyOption",
      "",
      " style=\"width:$strDefaultWidthPx \" onChange=\"checkSubSection()\" $strReadonly"
  );
  $strEmptyOption = "<option value=''>&nbsp; </option>\n";
  //12/18/2012
  $strInputStatusNew = getEmployeeStatusList("dataStatusNew", $arrData['dataStatusNew'], "", " $strReadonly", "");
  //<option value='99'>Resigned</option>\n <option value='8'>Promotion</option>
  $strInputStatus = '<div style="padding-top: 7px;"><strong>' . $words[$ARRAY_REQUEST_STATUS[$arrData['dataStatus']]] . '</strong></div>';
  $strChecked = ($arrData['dataIsStatus']) ? "checked" : "";
  $strClick = " onClick = \"checkStatus()\" ";
  $strInputIsStatus = "<div class=\"checkbox\" style=\"padding-top: 0 !important;\"><label><input class=\"checkbox-inline\" type=checkbox name=dataIsStatus value=0 $strChecked $strClick $strReadonly>$strWordsEmployeeStatusConfirmation</label></div>";
  $strChecked = ($arrData['dataIsPosition']) ? "checked" : "";
  //$strClick = " onClick = \"checkPosition()\" ";
  //$strInputIsPosition = "<input type=checkbox name=dataIsPosition value=0 $strChecked $strClick $strReadonly>";
  $strChecked = ($arrData['dataIsDepartment']) ? "checked" : "";
  $strClick = " onClick = \"checkOrganization()\" ";
  $strInputIsDepartment = "<div class=\"checkbox\" style=\"padding-top: 0 !important;\"><label><input class=\"checkbox-inline\" type=checkbox name=dataIsDepartment value=0 $strChecked $strClick $strReadonly>$strWordsEmployeeDpartmentChanges</label></div>";
  $strChecked = ($arrData['dataIsSalary']) ? "checked" : "";
  $strClick = " onClick = \"checkSalary()\" ";
  $strInputIsSalary = "<div class=\"checkbox\" style=\"padding-top: 0 !important;\"><label><input class=\"checkbox-inline\" type=checkbox name=dataIsSalary value=0 $strChecked $strClick $strReadonly></label></div>";
  if ($bolPrint) {
    $strInputEmployee = $arrData['dataEmployee'] . " / " . $arrData['dataEmployeeName'];
    $strInputDepartment = $arrData['dataDepartment'];
    $strInputPosition = $arrData['dataPosition'];
    //$strInputGrade = $arrData['dataGrade'];
    $strInputJoinDate = pgDateFormat($arrData['dataJoinDate'], "d M Y");
    $strInputEmployeeStatus = getWords($ARRAY_EMPLOYEE_STATUS[$arrData['dataEmployeeStatus']]);
    $strInputStatusNew = ($arrData['dataStatusNew'] === "") ? "" : getWords(
        $ARRAY_EMPLOYEE_STATUS[$arrData['dataStatusNew']]
    );
    $strInputStatusDateFrom = pgDateFormat($arrData['dataStatusDateFrom'], "d M Y");
    $strInputStatusDateThru = pgDateFormat($arrData['dataStatusDateThru'], "d M Y");
    $strInputManagementOld = $arrData['dataManagementOld'];
    $strInputManagementNew = $arrData['dataManagementNew'];
    $strInputDivisionOld = $arrData['dataDivisionOld'];
    $strInputDivisionNew = $arrData['dataDivisionNew'];
    $strInputDepartmentOld = $arrData['dataDepartmentOld'];
    $strInputDepartmentNew = $arrData['dataDepartmentNew'];
    $strInputSectionOld = $arrData['dataSectionOld'];
    $strInputSectionNew = $arrData['dataSectionNew'];
    $strInputSuSectionOld = $arrData['dataSubSectionOld'];
    $strInputSubSectionNew = $arrData['dataSubSectionNew'];
    $strInputDepartmentNewDate = pgDateFormat($arrData['dataDepartmentNewDate'], "d M Y");
    $strInputBasicSalaryOld = $arrData['dataBasicSalaryOld'];
    $strInputBasicSalaryNew = $arrData['dataBasicSalaryNew'];
    $strInputPositionAllowOld = $arrData['dataPositionAllowOld'];
    $strInputPositionAllowNew = $arrData['dataPositionAllowNew'];
    $strInputMealAllowOld = $arrData['dataMealOld'];
    $strInputMealAllowNew = $arrData['dataMealNew'];
    $strInputTransportAllowOld = $arrData['dataTransportOld'];
    $strInputTransportAllowNew = $arrData['dataTransportNew'];
    $strInputVehicleAllowOld = $arrData['dataVehicleOld'];
    $strInputVehicleAllowNew = $arrData['dataVehicleNew'];
    $strInputStartNewDate = pgDateFormat($arrData['dataStartNewDate'], "d M Y");
    $strInputNote = nl2br($arrData['dataNote']);
  }
  // tambahan tombol
  $strDisabledPrint = ($strDataID != "") ? "" : "disabled";
  $strBtnPrint .= "<input type=button name=btnPrint onClick=\"window.open('mutation_edit.php?btnPrint=Print&dataID=$strDataID');\" value=\"" . getWords(
          "print"
      ) . "\" $strDisabledPrint>";
}
($bolPrint) ? $strMainTemplate = getTemplate("mutation_edit_print.html", false) : $strTemplateFile = getTemplate(
    "mutation_edit.html"
);
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$strPageDesc = getWords('employee mutation request');
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = employeeMutationSubmenu($strWordsProposalEntry);
$strEmpName = '';
if (isset($arrData['employeeName']) && !empty($arrData['employeeName'])) {
  $strEmpName = $arrData['employeeName'];
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>