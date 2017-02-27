<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
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
$strDisplayDetail = "";
$strBtnPrint = "";
$strWordsEmployeeResignProposal = getWords("severance employee");
$strWordsResignProposalEntry = getWords("severance employee entry");
$strWordsResignProposalList = getWords("severance employee list");
$strWordsProposalDate = getWords("proposal date");
$strWordsProposalType = getWords("proposal type");
$strWordsEmployeeID = getWords("employee id");
$strWordsResignDate = getWords("resign date");
$strWordsGetInfo = getWords("get info");
$strWordsEmployeeInformation = getWords("employee information");
$strWordsJoinDate = getWords("join date");
$strWordsEmployeeStatus = getWords("employee status");
$strWordsSalary = getWords("salary");
$strWordsLeaveRemain = getWords("leave remain");
$strWordsAttendance = getWords("attendance");
$strWordsRightNote = getWords("right note");
$strWordsSalary = getWords("salary");
$strWordsMealAllowance = getWords("meal allowance");
$strWordsConjuncture = getWords("conjuncture");
$strWordsLeaveAllowance = getWords("leave allowance");
$strWordsSeparationPay = getWords("separation pay");
$strWordsOthers = getWords("others");
$strWordsTotalRight = getWords("total");
$strWordsSave = getWords("save");
$strWordsClearForm = getWords("clear form");
$strWordsOutstandingLoan = getWords("outstanding loan");
$strWordsOtherLoan = getWords("other loan");
$strWordsOthers = getWords("other");
$strWordsTotalObligation = getWords("total");
$strWordsNote = getWords("note");
$strWordsNoteReason = getWords("note/reason");
$strWordsStatus = getWords("status");
$strWordsEmployeeObligation = getWords("employee's obligation");
$strWordsEmployeeRights = getWords("employee's right");
if ($strDataID == "") {
  $arrData = [
      "dataDate"           => $strNow,
      "dataEmployee"       => "",
      "dataType"           => "",
      "dataResignDate"     => $strNow,
      "dataJoinDate"       => "",
      "dataEmployeeStatus" => 0,
      "dataBasicSalary"    => 0,
      "dataEmployeeLeave"  => 0,
      "dataWorkingDays" => 0,// jumlah hari kerja
      "dataAttendance"  => 0, // jumlah kehadiran
      "dataLeaveMonth"  => 0, //bulan berjalan untuk uang cuti
      "dataSalary"         => 0,
      "dataConjuncture"    => 0,
      "dataMeal"           => 0,
      "dataLeaveRemain"    => 0,
      "dataLeaveAllowance" => 0,
      "dataPesangon"       => 0,
      "dataOtherRight"     => 0,
      "dataRightNote"      => "",
      "dataLoan"            => 0,
      "dataOtherLoan"       => 0,
      "dataOtherObligation" => 0,
      "dataObligationNote"  => "",
      "dataStatus" => "0", //status dari proposal (baru, verifikasi, setuju, tolak)
      "dataNote"   => "",
      "dataID"     => "",
  ];
}
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
  if ($_REQUEST['dataID'] != "") {
    $strDataID = $_REQUEST['dataID'];
  }
  if ($strDataID != "") {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "ID=$strDataID", 0);
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.join_date,  ";
    $strSQL .= "t2.department_code, t2.grade_code, t2.employee_status, t2.position_code,t3.* ";
    $strSQL .= "FROM hrd_employee_mutation AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "LEFT JOIN hrd_employee_mutation_resign as t3 ON t1.id = t3.id_mutation ";
    $strSQL .= "WHERE t3.id = '$strDataID' ";
    // writeLog(ACTIVITY_VIEW, MODULE_PAYROLL,"ID=$strDataID",0);
    // $strSQL  = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.join_date,  ";
    // $strSQL .= "t2.department_code, t2.grade_code, t2.employee_status, t2.position_code ";
    // $strSQL .= "FROM hrd_employee_mutation AS t1 ";
    // $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    // $strSQL .= "WHERE t1.id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataEmployee'] = $rowDb['employee_id'];
      $arrData['dataID'] = $rowDb['id'];
      $arrData['dataDate'] = $rowDb['proposal_date'];
      $arrData['dataJoinDate'] = $rowDb['join_date'];
      $arrData['dataStatus'] = $rowDb['status'];
      $arrData['dataNote'] = $rowDb['note'];
      $arrData['dataemployee_name'] = $rowDb['employee_name'];
      $arrData['dataPosition'] = $rowDb['position_code'];
      $arrData['dataGrade'] = $rowDb['grade_code'];
      $arrData['dataEmployeeStatus'] = $rowDb['employee_status'];
      $arrData['dataJoinDate'] = $rowDb['join_date'];
      $arrData['dataDepartment'] = $rowDb['department_code'];
      // cari salary
      $strSQL = "SELECT * FROM hrd_employee_mutation_resign WHERE id_mutation = '$strDataID' ";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $arrData['dataResignDate'] = $rowTmp['resign_date'];
        $arrData['dataWorkingDays'] = $rowTmp['working_days'];
        $arrData['dataAttendance'] = $rowTmp['attendance'];
        $arrData['dataEmployeeLeave'] = $rowTmp['leave_remain'];
        $arrData['dataLeaveMonth'] = $rowTmp['leave_remain1'];
        $arrData['dataBasicSalary'] = (float)$rowTmp['basic_salary'];
        $arrData['dataSalary'] = (float)$rowTmp['salary'];
        $arrData['dataMeal'] = (float)$rowTmp['meal'];
        $arrData['dataConjuncture'] = (float)$rowTmp['conjuncture'];
        $arrData['dataLeaveRemain'] = (float)$rowTmp['leave_allowance1'];
        $arrData['dataLeaveAllowance'] = (float)$rowTmp['leave_allowance2'];
        $arrData['dataOtherRight'] = (float)$rowTmp['other_right'];
        $arrData['dataLoan'] = (float)$rowTmp['loan'];
        $arrData['dataOtherLoan'] = (float)$rowTmp['other_loan'];
        $arrData['dataPesangon'] = (float)$rowTmp['pesangon'];
        $arrData['dataOtherObligation'] = (float)$rowTmp['other_obligation'];
        $arrData['dataRightNote'] = $rowTmp['right_note'];
        $arrData['dataObligationNote'] = $rowTmp['obligation_note'];
      }
    }
  }
  return true;
} // showData
// fungsi untuk menyimpan data absen
function saveData($db, &$strError)
{
  global $_REQUEST;
  global $error;
  global $_SESSION;
  global $arrData;
  global $strDataID;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strError = "";
  $bolOK = true;
  $strToday = date("Y-m-d");
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = trim($_REQUEST['dataEmployee']) : $strDataEmployee = "";
  (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = "";
  (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
  (isset($_REQUEST['dataType'])) ? $strDataType = $_REQUEST['dataType'] : $strDataType = "";
  $strResignDate = (isset($_REQUEST['dataResignDate'])) ? $_REQUEST['dataResignDate'] : "";
  $strWorkingDays = (isset($_REQUEST['dataWorkingDays'])) ? $_REQUEST['dataWorkingDays'] : 0;
  $strAttendance = (isset($_REQUEST['dataAttendance'])) ? $_REQUEST['dataAttendance'] : 0;
  $strLeaveRemain1 = (isset($_REQUEST['dataEmployeeLeave'])) ? $_REQUEST['dataEmployeeLeave'] : 0;
  $strLeaveRemain2 = (isset($_REQUEST['dataLeaveMonth'])) ? $_REQUEST['dataLeaveMonth'] : 0;
  $strBasicSalary = (isset($_REQUEST['dataBasicSalary'])) ? $_REQUEST['dataBasicSalary'] : 0;
  $strSalary = (isset($_REQUEST['dataSalary'])) ? $_REQUEST['dataSalary'] : 0;
  $strMeal = (isset($_REQUEST['dataMeal'])) ? $_REQUEST['dataMeal'] : 0;
  $strConjuncture = (isset($_REQUEST['dataConjuncture'])) ? $_REQUEST['dataConjuncture'] : 0;
  $strLeave1 = (isset($_REQUEST['dataLeaveRemain'])) ? $_REQUEST['dataLeaveRemain'] : 0; // sisa cuti (hari, diuangkan)
  $strLeave2 = (isset($_REQUEST['dataLeaveAllowance'])) ? $_REQUEST['dataLeaveAllowance'] : 0; // uang cuti (bulan berjalan)
  $strPesangon = (isset($_REQUEST['dataPesangon'])) ? $_REQUEST['dataPesangon'] : 0;
  $strOtherRight = (isset($_REQUEST['dataOtherRight'])) ? $_REQUEST['dataOtherRight'] : 0;
  $strLoan = (isset($_REQUEST['dataLoan'])) ? $_REQUEST['dataLoan'] : 0;
  $strOtherLoan = (isset($_REQUEST['dataOtherLoan'])) ? $_REQUEST['dataOtherLoan'] : 0;
  $strObligation = (isset($_REQUEST['dataOtherObligation'])) ? $_REQUEST['dataOtherObligation'] : 0;
  $strNote = (isset($_REQUEST['dataNote'])) ? $_REQUEST['dataNote'] : "";
  $strNoteRight = (isset($_REQUEST['dataRightNote'])) ? $_REQUEST['dataRightNote'] : "";
  $strNoteObligation = (isset($_REQUEST['dataObligationNote'])) ? $_REQUEST['dataObligationNote'] : "";
  if (!is_numeric($strBasicSalary)) {
    $strBasicSalary = 0;
  }
  if (!is_numeric($strSalary)) {
    $strSalary = 0;
  }
  // cek validasi -----------------------
  if ($strDataEmployee == "") {
    $strError = getWords('empty_code');
    $bolOK = false;
  } else if (!validStandardDate($strDataDate)) {
    $strError = getWords('invalid_date');
    $bolOK = false;
  } else if (!validStandardDate($strResignDate)) {
    $strError = getWords('invalid_date');
    $bolOK = false;
  }
  // cari dta Employee ID, apakah ada atau tidak
  $strSQL = "SELECT id FROM hrd_employee WHERE employee_id = '$strDataEmployee' AND active = 1 ";
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
  // simpan data -----------------------
  if ($bolOK) { // input OK, tinggal disimpan
    if ($strDataID == "") {
      // data baru
      $strSQL = "INSERT INTO hrd_employee_mutation (created,created_by,modified_by, ";
      $strSQL .= "id_employee,proposal_date, note, status, type) ";
      $strSQL .= "VALUES(now(),'$strmodified_byID','$strmodified_byID', ";
      //$strSQL .= "'$strIDEmployee','$strDataDate', '$strDataNote', " .REQUEST_STATUS_VERIFIED.", 1)  ";
      $strSQL .= "'$strIDEmployee','$strDataDate', '$strDataNote', 0, $strDataType)";
      echo $strSQL;
      $resExec = $db->execute($strSQL);
      // cari ID
      $strSQL = "SELECT id FROM hrd_employee_mutation ";
      $strSQL .= "WHERE id_employee = '$strIDEmployee' AND proposal_date = '$strDataDate' ";
      // $strSQL .= "AND type = 1 AND status = ".REQUEST_STATUS_VERIFIED;
      $strSQL .= "AND type ='$strDataType' AND status = 0";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $strDataID = $rowDb['id'];
      }
    } else {
      // cek status, jika sudah approved, gak boleh diedit lagi
      $strSQL = "SELECT status FROM hrd_employee_mutation WHERE id = '$strDataID' ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        if ($rowDb['status'] >= REQUEST_STATUS_APPROVED) {
          $strError = getWords('edit_denied');
          return false;
        }
      }
      $strSQL = "UPDATE hrd_employee_mutation ";
      $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
      $strSQL .= "id_employee = '$strIDEmployee', proposal_date = '$strDataDate', ";
      $strSQL .= "note = '$strDataNote'  WHERE id = '$strDataID' ";
      $resExec = $db->execute($strSQL);
    }
    // simpan data detilnya, jika ada
    if ($strDataID != "") {
      //hapus dulu semua data
      $strSQL = "DELETE FROM hrd_employee_mutation_resign WHERE id_mutation = '$strDataID'; ";
      $resDb = $db->execute($strSQL);
      // simpan data salary
      $strSQL = "INSERT INTO hrd_employee_mutation_resign (created, modified_by, created_by, ";
      $strSQL .= "id_mutation, resign_date, working_days, ";
      $strSQL .= "\"attendance\", leave_remain, \"leave_remain1\", ";
      $strSQL .= "basic_salary, \"salary\", \"meal\", \"conjuncture\", ";
      $strSQL .= "leave_allowance1, leave_allowance2,";
      $strSQL .= "\"pesangon\", other_right, right_note, ";
      $strSQL .= "\"loan\", other_loan, other_obligation, obligation_note) ";
      $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '$strDataID', ";
      $strSQL .= "'$strResignDate', '$strWorkingDays', '$strAttendance', ";
      $strSQL .= "'$strLeaveRemain1', '$strLeaveRemain2', '$strBasicSalary', ";
      $strSQL .= "'$strSalary', '$strMeal', '$strConjuncture', '$strLeave1', ";
      $strSQL .= "'$strLeave2', '$strPesangon', '$strOtherRight', '$strNoteRight', ";
      $strSQL .= "'$strLoan', '$strOtherLoan', '$strObligation', '$strNoteObligation')";
      $resExec = $db->execute($strSQL);
    }
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "RESIGN DATA", 0);
    $strError = getWords('data_saved');
  } else { // ---- data SALAH
    // gunakan data yang diisikan tadi
    $arrData['dataEmployee'] = $strDataEmployee;
    $arrData['dataDate'] = $strDataDate;
    $arrData['dataNote'] = $strDataNote;
    $arrData['dataResignDate'] = $strResignDate;
    $arrData['dataAttendance'] = $strAttendance;
    $arrData['dataWorkingDays'] = $strWorkingDays;
    $arrData['dataEmployeeLeave'] = $strLeaveRemain1;
    $arrData['dataLeaveMonth'] = $strLeaveRemain2;
    $arrData['dataBasicSalary'] = $strBasicSalary;
    $arrData['dataSalary'] = $strSalary;
    $arrData['dataMeal'] = $strMeal;
    $arrData['dataConjuncture'] = $strConjuncture;
    $arrData['dataLeaveRemain'] = $strLeave1;
    $arrData['dataLeaveAllowance'] = $strLeave2;
    $arrData['dataPesangon'] = $strPesangon;
    $arrData['dataOtherRight'] = $strOtherRight;
    $arrData['dataRightNote'] = $strNoteRight;
    $arrData['dataLoan'] = $strLoan;
    $arrData['dataOtherLoan'] = $strOtherLoan;
    $arrData['dataOtherObligation'] = $strObligation;
    $arrData['dataObligationNote'] = $strNoteObligation;
    $arrData['dataSalaryNote'] = $strNote;
    $arrData['dataID'] = $strDataID;
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "RESIGN DATA", 1);
  }
  return $bolOK;
} // saveData
// fungsi untuk mengambil data employee yang terakhir
// bolStart : mencari dari awal termasuk perhitungan hak dan kewajiban, jika false, hanya info pegawai aja
function getInfoEmployee($db, $bolStart = true)
{
  include_once('../global/employee_function.php');
  include_once('salary_func.php');
  include_once('activity.php');
  global $_REQUEST;
  global $_SESSION;
  global $arrData;
  $strDataType = (isset($_REQUEST['dataType'])) ? $_REQUEST['dataType'] : "";
  $strID = (isset($_REQUEST['dataEmployee'])) ? $_REQUEST['dataEmployee'] : "";
  if ($strID != "") {
    $arrData['dataType'] = $strDataType;
    $arrData['dataEmployee'] = $strID;
    $strResignDate = (validStandardDate($_REQUEST['dataResignDate'])) ? $_REQUEST['dataResignDate'] : date("Y-m-d");
    $arrData['dataResignDate'] = $strResignDate;
    $strResignDate = getNextDate($strResignDate, -1); // dikurangi 1 hari, sebagai hari terakhir dia kerja
    // ambil start dan finish date untuk uang makan
    $strMealDateFrom = date("Y-m-d");
    $strMealDateThru = date("Y-m-d");
    getDefaultSalaryPeriode($strMealDateFrom, $strMealDateThru, $strResignDate, true);
    $strWorkingDaysMeal = totalWorkDay($db, $strMealDateFrom, $strMealDateThru);
    // ambil start dan finish date untuk gaji
    // dianggap start tgl 1 dan akhir tgl lain
    $intSalaryDay = getSetting("salary_date");
    if (!is_numeric($intSalaryDay)) {
      $intSalaryDay = 25;
    } //default
    list($thn, $bln, $tgl) = explode("-", $strResignDate);
    $dtResign['year'] = (int)$thn;
    $dtResign['mon'] = (int)$bln;
    $dtResign['mday'] = (int)$tgl;
    $strSalaryDateThru = date("$thn-$bln-$intSalaryDay");
    if ($intSalaryDay <= (int)$tgl) // sudah lewat
    {
      $strSalaryDateFrom = getNextDate($strSalaryDateThru);
      $strSalaryDateThru = getNextDateNextMonth($strSalaryDateThru);
    } else {
      $strSalaryDateFrom = getNextDateNextMonth($strSalaryDateThru, -1);
      $strSalaryDateFrom = getNextDate($strSalaryDateFrom);
    }
    $strWorkingDaysSalary = totalWorkDay($db, $strSalaryDateFrom, $strSalaryDateThru);
    $arrData['dataWorkingDays'] = $strWorkingDaysMeal; // pakai hari kerja untuk makan
    $strSQL = "SELECT id, join_date, employee_status, ";
    $strSQL .= "AGE('" . $_REQUEST['dataResignDate'] . "', join_date) AS umur, ";
    $strSQL .= "EXTRACT(year FROM AGE('" . $_REQUEST['dataResignDate'] . "', join_date)) AS lama, ";
    $strSQL .= "EXTRACT(month FROM AGE('" . $_REQUEST['dataResignDate'] . "', join_date)) AS lamabulan ";
    $strSQL .= "FROM hrd_employee WHERE employee_id = '$strID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataJoinDate'] = $rowDb['join_date'];
      $arrData['dataEmployeeStatus'] = $rowDb['employee_status'];
      $arrData['dataEmployeeLeave'] = getEmployeeLeaveRemain($db, $rowDb['id']);
      // cari data salary
      $strSQL = "SELECT grade1_allowance, grade2_allowance FROM hrd_employee_basic_salary ";
      $strSQL .= "WHERE id_employee = '" . $rowDb['id'] . "' ";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $fltBasic = $rowTmp['grade1_allowance'] + $rowTmp['grade2_allowance'];
        if (is_numeric($fltTransport = getSetting("transport_allow"))) {
          $fltBasic += ($rowTmp['grade1_allowance'] + $rowTmp['grade2_allowance']) * ($fltTransport / 100);
        }
        if (is_numeric($fltHousing = getSetting("housing_allow"))) {
          $fltBasic += ($rowTmp['grade1_allowance'] + $rowTmp['grade2_allowance']) * ($fltHousing / 100);
        }
        $arrData['dataBasicSalary'] = (float)$fltBasic;
      }
      // echo $rowDb['lamabulan'];
      // echo $rowDb['lama'];
      if ($bolStart) {
        $arrData['dataLeaveRemain'] = $arrData['dataBasicSalary'] * ($arrData['dataEmployeeLeave'] / 12);
        if ($_REQUEST['dataType'] == 1) {
          // cari jatah uang pisah
          if ($rowDb['lama'] < 3) {
            $arrData['dataPesangon'] = 0;
          } else if ($rowDb['lama'] < 6) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 2);
          } else if ($rowDb['lama'] < 9) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 3);
          } else if ($rowDb['lama'] < 12) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 4);
          } else if ($rowDb['lama'] < 15) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 5);
          } else if ($rowDb['lama'] < 18) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 6);
          } else if ($rowDb['lama'] < 21) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 7);
          } else if ($rowDb['lama'] < 24) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 8);
          } // else if ($rowDb['lama'] < 18) $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 6);
          else {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 10);
          }
        } elseif ($_REQUEST['dataType'] == 2) {
          if ($rowDb['lama'] < 1 && $rowDb['lamabulan'] < 12) {
            $arrData['dataPesangon'] = $arrData['dataBasicSalary'] * 1;
          } else if ($rowDb['lama'] < 2 && $rowDb['lamabulan'] < 12) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 2);
          } else if ($rowDb['lama'] < 3 && $rowDb['lamabulan'] < 12) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 3);
          } else if ($rowDb['lama'] < 4 && $rowDb['lamabulan'] < 12) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 4);
          } else if ($rowDb['lama'] < 5 && $rowDb['lamabulan'] < 12) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 5);
          } else if ($rowDb['lama'] < 6 && $rowDb['lamabulan'] < 12) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 6);
          } else if ($rowDb['lama'] < 7 && $rowDb['lamabulan'] < 12) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 7);
          } else if ($rowDb['lama'] < 8 && $rowDb['lamabulan'] < 12) {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 8);
          } else {
            $arrData['dataPesangon'] = ($arrData['dataBasicSalary'] * 9);
          }
        }
        // cari data pinjaman
        $arrData['dataLoan'] = 0;
        $strSQL = "SELECT *, EXTRACT(month FROM AGE(payment_from)) AS paid  ";
        $strSQL .= "FROM hrd_loan WHERE status = 0 ";
        $strSQL .= "AND payment_from < '$strResignDate' ";
        $strSQL .= "AND (payment_thru + interval '1 months') > '$strResignDate' "; // karena tanggal dibuat 01
        $strSQL .= "AND id_employee = '" . $rowDb['id'] . "' ";
        $resTmp = $db->execute($strSQL);
        while ($rowTmp = $db->fetchrow($resTmp)) {
          // cari total pembayaran
          $intTotalPayment = $rowTmp['paid'];
          // jika lebih dari tgl 15, ditambah 1
          if ($dtResign['mday'] > $intSalaryDay) {
            $intTotalPayment++;
          }
          if ($intTotalPayment > $rowTmp['periode']) {
            $intTotalPayment = $rowTmp['periode'];
          }
          // hitung cicilan
          if ($rowTmp['periode'] == 0) {
            $fltMonthlyPayment = 0;
          } else {
            $fltMonthlyPayment = ((((100 + $rowTmp['interest']) / 100) * $rowTmp['amount']) / $rowTmp['periode']);
          }
          $arrData['dataLoan'] += ($rowTmp['periode'] - $intTotalPayment) * $fltMonthlyPayment;
        }
        // cari jatah uang makan, berdasar info kehadiran
        $arrAtt = getEmployeeAttendance($db, $strMealDateFrom, $strResignDate, 0, "", $strID);
        $fltMeal = getSetting("meal_allow");
        if (!is_numeric($fltMeal)) {
          $fltMeal = 0;
        }
        $arrData['dataAttendance'] = (isset($arrAtt[$rowDb['id']]['total'])) ? $arrAtt[$rowDb['id']]['total'] : 0;
        $arrData['dataMeal'] = $arrData['dataAttendance'] * $fltMeal;
        // cari data gaji, proposional harian
        $intWork = totalWorkDay($db, $strSalaryDateFrom, $strResignDate);
        $intTotalWorkDay = totalWorkDay($db, $strSalaryDateFrom, $strSalaryDateThru);
        if ($intTotalWorkDay == 0) {
          $arrData['dataSalary'] = 0;
        } else {
          $arrData['dataSalary'] = $intWork * ($arrData['dataBasicSalary'] / $intTotalWorkDay);
        }
        $arrData['dataSalary'] = round($arrData['dataBasicSalary']);
        $fltConjuncture = getSetting("conjuncture_allow");
        if (!is_numeric($fltConjuncture)) {
          $fltConjuncture = 0;
        }
        $arrData['dataConjuncture'] = ($fltConjuncture / 100) * ($arrData['dataSalary'] + $arrData['dataMeal']);
        $arrData['dataConjuncture'] = round($arrData['dataConjuncture']);
        if ($rowDb['lama'] > 0) {
          // hitung uang cuti, jika lebih dari 1
          $arrData['dataLeaveMonth'] = $rowDb['lamabulan'];
          $arrData['dataLeaveAllowance'] = ($arrData['dataLeaveMonth'] / 12) * $arrData['dataBasicSalary'];
          $arrData['dataLeaveAllowance'] = round($arrData['dataLeaveAllowance']);
        }
      }
    }
  }
}

//----------------------------------------------------------------------
function getTypeName($varname, $default = "")
{
  $arrType = [1 => "Resign", 2 => "Fired"];
  $strResult = "";
  $strResult .= "<select class=\"form-control select2\" name=\"$varname\" $action>\n";
  foreach ($arrType as $key => $value) {
    ($key == $default) ? $strSelect = "selected" : $strSelect = "";
    $strResult .= "<option value=\"" . $key . "\" $strSelect>" . $value . "</option>\n";
  }
  $strResult .= "</select>";
  return $strResult;
}

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
        //echo "<script>alert(\"$strError\");/script>";
        $strMessages = $strError;
        $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
      }
      //$strDataID = ""; // biar tidak mengambil dta, melainkan pakai data baru atau data yang dikirim (jika error)
    }
  }
  if (isset($_REQUEST['btnGet'])) {
    // ambil info tentang employee
    getInfoEmployee($db);
  }
  //$arrData['dataEmployee'] = $arrUserInfo['employee_id']; // beri default user
  if ($bolCanView) {
    getData($db);
  } else {
    showError("view_denied");
  }
  //----- TAMPILKAN DATA ---------
  $strDefaultWidthPx1 = 100;
  $strReadonly = ($arrData['dataStatus'] == 2) ? "readonly" : ""; // kalau dah approve, jadi readonly
  $strInputDate = "<input class=\"form-control datepicker\" type=text maxlength=10 name=dataDate id=dataDate value=\"" . $arrData['dataDate'] . "\" data-date-format=\"yyyy-mm-dd\" $strReadonly>";
  $strInputEmployee = "<input class=\"form-control\" type=text name=dataEmployee id=dataEmployee maxlength=30 value=\"" . $arrData['dataEmployee'] . "\" $strReadonly>";
  $strInputType = getTypeName("dataType", $arrData['dataType']);
  $strInputResignDate = "<input class=\"form-control datepicker\" type=text maxlength=10 name=dataResignDate id=dataResignDate value=\"" . $arrData['dataResignDate'] . "\" data-date-format=\"yyyy-mm-dd\" $strReadonly>";
  $strInputEmployeeStatus = getWords($ARRAY_EMPLOYEE_STATUS[$arrData['dataEmployeeStatus']]) . "&nbsp;";
  $strInputEmployeeLeave = "<input type=hidden name=dataEmployeeLeave value=\"" . $arrData['dataEmployeeLeave'] . "\">" . $arrData['dataEmployeeLeave'] . "&nbsp;";
  $strInputEmployeeLeave .= " (" . $arrData['dataLeaveMonth'] . ")";
  $strInputEmployeeJoin = pgDateFormat($arrData['dataJoinDate'], "d M Y") . "&nbsp;";
  $strInputEmployeeSalary = standardFormat($arrData['dataBasicSalary']) . "&nbsp;";
  $strInputEmployeeSalary .= "<input type=hidden name=dataBasicSalary value=\"" . $arrData['dataBasicSalary'] . "\"";
  $strInputAttendance = $arrData['dataAttendance'];
  $strInputAttendance .= "<input type=hidden name=dataAttendance value=\"" . $arrData['dataAttendance'] . "\">";
  $strInputWorkingDays = $arrData['dataWorkingDays'];
  $strInputWorkingDays .= "<input type=hidden name=dataWorkingDays value=\"" . $arrData['dataWorkingDays'] . "\">";
  $strAction = "onChange=\"getTotalRight()\"";
  $strInputSalary = "<input type=text name=dataSalary id=dataSalary size=15 maxlength=20 value=\"" . $arrData['dataSalary'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly class='numeric' $strAction>";
  $strInputConjuncture = "<input type=text name=dataConjuncture id=dataConjuncture size=15 maxlength=20 value=\"" . $arrData['dataConjuncture'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly class='numeric' $strAction>";
  $strInputMeal = "<input type=text name=dataMeal id=dataMeal size=15 maxlength=20 value=\"" . $arrData['dataMeal'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly class='numeric' $strAction>";
  $strInputLeaveRemain = "<input type=text name=dataLeaveRemain id=dataLeaveRemain size=15 maxlength=20 value=\"" . $arrData['dataLeaveRemain'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly class='numeric' $strAction>";
  $strInputLeave = "<input type=text name=dataLeaveAllowance id=dataLeaveAllowance size=15 maxlength=20 value=\"" . $arrData['dataLeaveAllowance'] . "\" style=\"width:$strDefaultWidthPx\" $strReadonly class='numeric' $strAction>";
  $strInputLeave .= "<input type=hidden name=dataLeaveMonth value=\"" . $arrData['dataLeaveMonth'] . "\">";
  $strInputOtherRight = "<input class=\"form-control\" type=text name=dataOtherRight id=dataOtherRight maxlength=20 value=\"" . $arrData['dataOtherRight'] . "\" $strReadonly class='numeric' $strAction>";
  $strInputPesangon = "<input class=\"form-control\" type=text name=dataPesangon id=dataPesangon maxlength=20 value=\"" . $arrData['dataPesangon'] . "\" $strReadonly class='numeric' $strAction>";
  $strInputRightNote = "<input class=\"form-control\" type=text name=dataRightNote id=dataRightNote maxlength=100 value=\"" . $arrData['dataRightNote'] . "\" $strReadonly>";
  $strAction = "onChange=\"getTotalObligation()\"";
  $strInputLoan = "<input class=\"form-control\" type=text name=dataLoan id=dataLoan maxlength=20 value=\"" . $arrData['dataLoan'] . "\" $strReadonly class='numeric' $strAction>";
  $strInputOtherLoan = "<input class=\"form-control\" type=text name=dataOtherLoan id=dataOtherLoan maxlength=20 value=\"" . $arrData['dataOtherLoan'] . "\" $strReadonly class='numeric' $strAction>";
  $strInputOtherObligation = "<input class=\"form-control\" type=text name=dataOtherObligation id=dataOtherObligation maxlength=20 value=\"" . $arrData['dataOtherObligation'] . "\" $strReadonly class='numeric' $strAction>";
  $strInputObligationNote = "<input class=\"form-control\" type=text name=dataObligationNote id=dataObligationNote maxlength=100 value=\"" . $arrData['dataObligationNote'] . "\" $strReadonly>";
  $strInputNote = "<textarea class=\"form-control\" name=dataNote cols=30 rows=3 wrap='virtual' $strReadonly>" . $arrData['dataNote'] . "</textarea>";
  $strInputStatus = getWords($ARRAY_REQUEST_STATUS[$arrData['dataStatus']]);
  // hitung total hak
  // $strInputTotalRight = $arrData['dataSalary'] + $arrData['dataMeal'] + $arrData['dataConjuncture'];
  // $strInputTotalRight += $arrData['dataLeaveRemain'] + $arrData['dataLeaveAllowance'];
  // $strInputTotalRight += $arrData['dataPesangon'] + $arrData['dataOtherRight'];
  $strInputTotalRight = $arrData['dataPesangon'] + $arrData['dataOtherRight'];
  $strInputTotalObligation = $arrData['dataLoan'] + $arrData['dataOtherLoan'] + $arrData['dataOtherObligation'];
  if ($bolPrint) {
    $strInputEmployee = $arrData['dataEmployee'] . " / " . $arrData['dataemployee_name'];
    $strInputDepartment = $arrData['dataDepartment'];
    $strInputPosition = $arrData['dataPosition'];
    $strInputGrade = $arrData['dataGrade'];
    $strInputJoinDate = pgDateFormat($arrData['dataJoinDate'], "d M Y");
    $strInputResignDate = pgDateFormat($arrData['dataResignDate'], "d M Y");
    $strInputEmployeeStatus = getWords($ARRAY_EMPLOYEE_STATUS[$arrData['dataEmployeeStatus']]);
    $strInputAttendance = $arrData['dataAttendance'];
    $strInputWorkingDays = $arrData['dataWorkingDays'];
    $strInputEmployeeLeave = $arrData['dataEmployeeLeave'];
    $strInputEmployeeSalary = standardFormat($arrData['dataBasicSalary']);
    $strInputSalary = standardFormat($arrData['dataSalary']);
    $strInputMeal = standardFormat($arrData['dataMeal']);
    $strInputConjuncture = standardFormat($arrData['dataConjuncture']);
    $strInputLeave1 = standardFormat($arrData['dataLeaveRemain']);
    $strInputLeave2 = standardFormat($arrData['dataLeaveAllowance']);
    $strInputPesangon = standardFormat($arrData['dataPesangon']);
    $strInputOtherRight = standardFormat($arrData['dataOtherRight']);
    $strInputRightNote = $arrData['dataRightNote'];
    $strInputLoan = standardFormat($arrData['dataLoan']);
    $strInputOtherLoan = standardFormat($arrData['dataOtherLoan']);
    $strInputOtherObligation = standardFormat($arrData['dataOtherObligation']);
    $strInputObligationNote = $arrData['dataObligationNote'];
    $strInputTotalObligation = standardFormat($strInputTotalObligation);
    $strInputTotalRight = standardFormat($strInputTotalRight);
    $strInputNote = nl2br($arrData['dataNote']);
  }
  // jika baru atau data employee belum ada, sembunyikan data detail
  if ($arrData['dataEmployee'] == "") {
    $strDisplayDetail = " style=\"display:none\" ";
  }
  // tambahan tombol
  $strDisabledPrint = ($strDataID != "") ? "" : "disabled";
  $strBtnPrint .= "<input class=\"btn btn-primary\" type=button name=btnPrint onClick=\"window.open('resign_edit.php?btnPrint=Print&dataID=$strDataID');\" value=\"" . getWords(
          "print"
      ) . "\" $strDisabledPrint>";
}
($bolPrint) ? $strMainTemplate = getTemplate("resign_edit_print.html", false) : $strTemplateFile = getTemplate(
    "resign_edit.html"
);
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('employee resign request entry');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = employeeResignSubmenu($strWordsResignProposalEntry);
$strEmpName = "";
if (isset($_REQUEST['btnGet']) && isset($_REQUEST['dataEmployee']) && !empty($_REQUEST['dataEmployee'])) {
  $mployeeData = getEmployeeInfoByCode($db, $_REQUEST['dataEmployee'], 'employee_name');
  $strEmpName = $mployeeData['employee_name'];
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>