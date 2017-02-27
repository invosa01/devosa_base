<?php
/* Daftar Fungsi untuk mengolah data yang terkait dengan aktivitas kepegawaian
 Misal,  lembur, absen, cuti dan lain-lain

 */
// fungsi untuk mengetahui apakah ada aktivitas cuti antara tanggal tertentu
// untuk karyawan tertentu.
// input: data, idEmployee, tanggal awal+akhir, idException (id yg jadi perkecualian)
// output: array(status: ada/tidak, id aktifitas, tgl aktifitas)
function isLeaveExists($db, $strID, $strDateFrom, $strDateThru, $strIDException = "")
{
  $arrResult = [
      "status" => false,
      "id"     => "",
      "from"   => "",
      "thru"   => ""
  ];
  if ($strID == "" || $strDateFrom == "" || $strDateThru == "") {
    return $arrResult;
  }
  $strSQL = "SELECT * FROM hrd_leave WHERE id_employee = '$strID' ";
  $strSQL .= "AND ((date_from, date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strDateFrom', DATE '$strDateThru') ";
  $strSQL .= "    OR (date_from = DATE '$strDateFrom') ";
  $strSQL .= "    OR (date_from = DATE '$strDateThru') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateFrom') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateThru')) ";
  $strSQL .= "AND status <> '" . REQUEST_STATUS_DENIED . "' ";
  if ($strIDException != "") {
    $strSQL .= "AND id <> '$strIDException' ";
  }
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $arrResult['status'] = true;
    $arrResult['from'] = $rowDb['date_from'];
    $arrResult['thru'] = $rowDb['date_thru'];
    $arrResult['id'] = $rowDb['id'];
  }
  return $arrResult;
} // isLeaveExists
// fungsi untuk mengetahui apakah ada aktivitas perjalan dinas antara tanggal tertentu
// untuk karyawan tertentu.
// input: data, idEmployee, tanggal awal+akhir, idException (id yg jadi perkecualian)
// output: array(status: ada/tidak, id aktifitas, tgl aktifitas)
function isTripExists($db, $strID, $strDateFrom, $strDateThru, $strIDException = "")
{
  $arrResult = [
      "status" => false,
      "id"     => "",
      "from"   => "",
      "thru"   => ""
  ];
  if ($strID == "" || $strDateFrom == "" || $strDateThru == "") {
    return $arrResult;
  }
  $strSQL = "SELECT * FROM hrd_trip WHERE id_employee = '$strID' ";
  $strSQL .= "AND ((date_from, date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strDateFrom', DATE '$strDateThru') ";
  $strSQL .= "    OR (date_from = DATE '$strDateFrom') ";
  $strSQL .= "    OR (date_from = DATE '$strDateThru') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateFrom') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateThru')) ";
  $strSQL .= "AND status <> '" . REQUEST_STATUS_DENIED . "' ";
  if ($strIDException != "") {
    $strSQL .= "AND id <> '$strIDException' ";
  }
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $arrResult['status'] = true;
    $arrResult['from'] = $rowDb['date_from'];
    $arrResult['thru'] = $rowDb['date_thru'];
    $arrResult['id'] = $rowDb['id'];
  }
  return $arrResult;
} // isTripExists
// fungsi untuk mengetahui apakah ada aktivitas training antara tanggal tertentu
// untuk karyawan tertentu.
// input: data, idEmployee, tanggal awal+akhir, idException (id yg jadi perkecualian)
// output: array(status: ada/tidak, id aktifitas, tgl aktifitas)
function isTrainingExists($db, $strID, $strDateFrom, $strDateThru, $strIDException = "")
{
  $arrResult = [
      "status" => false,
      "id"     => "",
      "from"   => "",
      "thru"   => ""
  ];
  if ($strID == "" || $strDateFrom == "" || $strDateThru == "") {
    return $arrResult;
  }
  $strSQL = "SELECT t1.* FROM hrd_training_request AS t1, hrd_training_request_participant AS t2 ";
  $strSQL .= "WHERE t1.id = t2.id_request AND t2.id_employee = '$strID' ";
  $strSQL .= "AND ((t1.training_date, t1.training_date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strDateFrom', DATE '$strDateThru') ";
  $strSQL .= "    OR (training_date = DATE '$strDateFrom') ";
  $strSQL .= "    OR (training_date = DATE '$strDateThru') ";
  $strSQL .= "    OR (training_date_thru = DATE '$strDateFrom') ";
  $strSQL .= "    OR (training_date_thru = DATE '$strDateThru')) ";
  $strSQL .= "AND t1.status <> '" . REQUEST_STATUS_DENIED . "' ";
  if ($strIDException != "") {
    $strSQL .= "AND t1.id <> '$strIDException' ";
  }
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $arrResult['status'] = true;
    $arrResult['from'] = $rowDb['training_date'];
    $arrResult['thru'] = $rowDb['training_date_thru'];
    $arrResult['id'] = $rowDb['id'];
  }
  return $arrResult;
} // isTrainingExists
// fungsi untuk memeriksa data kehadiran karyawan, crosscek dengan data ketidakhadiran-nya
// jika data kehadiran tidak ada dan gak ada info apa-apa, otomatis "MEMBUAT" data absence, yang potong gaji.
// dari periode tanggal tertentu
function recheckAttendanceData($db, $strDateFrom, $strDateThru)
{
  global $_SESSION;
  $bolResult = true;
  if (!validStandardDate($strDateFrom) || !validStandardDate($strDateThru)) {
    return false;
  }
  $cExec = new CexecutionTime();
  //  ambil dulu data kehadiran yang ada
  $arrAtt = [];
  $strSQL = "SELECT  id, attendance_date, id_employee FROM hrd_attendance ";
  $strSQL .= "WHERE  attendance_date BETWEEN '$strDateFrom' AND '$strDateThru'  ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $strTgl = pgDateFormat($rowDb['attendance_date'], "Y-m-d");
    $arrAtt[$rowDb['id_employee']][$strTgl] = $rowDb['id'];
  }
  // ambil data ketidakhadiran
  $arrOut = getOutOfficeInfo($db, $strDateFrom, $strDateThru);
  // cari data karyawan
  $arrEmp = [];
  $strSQL = "SELECT id, employee_id FROM hrd_employee WHERE active=1 AND flag=0 AND onsite = 'f' "; // yang di kantor aja
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrEmp[$rowDb['id']] = $rowDb['employee_id'];
  }
  // mulai looping perhari
  $strDateFrom = pgDateFormat($strDateFrom, "Y-m-d");
  $strDateThru = pgDateFormat($strDateThru, "Y-m-d");
  $strCurrent = $strDateFrom;
  $strmodified_byID = (isset($_SESSION['sessionUserID'])) ? $_SESSION['sessionUserID'] : 0;
  $i = 0;
  $strSQL = "";
  while ($strCurrent <= $strDateThru) {
    $bolHoliday = isHoliday($strCurrent);
    if (!$bolHoliday) {
      // cek setiap employee
      foreach ($arrEmp AS $strID => $strData) {
        if (!isset($arrAtt[$strID][$strCurrent]) && !isset($arrOut[$strCurrent][$strID])) {
          // tambahkan data absensi, yang siap potong cuti :-<
          $strSQL .= "INSERT INTO hrd_absence (created, modified_by, created_by, request_date, ";
          $strSQL .= "date_from, date_thru, absence_type_code, ";
          $strSQL .= "id_employee, note, status, leave_year) ";
          $strSQL .= "VALUES (now(), '$strmodified_byID', '$strmodified_byID', '$strCurrent', ";
          $strSQL .= "'$strCurrent', '$strCurrent', NULL, '$strID', 'generated by system'," . REQUEST_STATUS_CHECKED . ",NULL); ";
          $i++;
          if ($i > 50) { // proses per 50
            $resExec = $db->execute($strSQL);
            $strSQL = "";
            $i = 0;
          }
        } else if (isset($arrAtt[$strID][$strCurrent]) && isset($arrOut[$strCurrent][$strID])) {
          // ada dua-duanya, update data kehadiran jadi isAbsence
          $strSQL = "UPDATE hrd_attendance SET is_absence = 't' ";
          $strSQL .= "WHERE attendance_date = '$strCurrent' ";
          $strSQL .= "AND id_employee = '$strID'; ";
          $i++;
          if ($i > 50) { // proses per 50
            $resExec = $db->execute($strSQL);
            $strSQL = "";
            $i = 0;
          }
        }
      }
    }
    $strCurrent = getNextDate($strCurrent);
  }
  if ($strSQL != "") {
    $resExec = $db->execute($strSQL);
  }
  $strDur = $cExec->getDuration();
  writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "recheck in $strDur ", 0);
  return $bolResult;
}//recheckAttendanceData
// fungsi untuk mengecek apakah ada request yang kedaluwarsa
function recheckRequestData($db, $strDateFrom)
{
  if ($strDateFrom == "") {
    return false;
  }
  if (!validStandardDate($strDateFrom)) {
    return false;
  }
  $cExec = new CexecutionTime();
  $intLimit = 2 * INT_LIMIT_APPROVAL;
  // --- CEK IJIN ATTENDANCE ---
  $strNow = date("Y-m-d");
  $strIDList = "";
  $strSQL = "SELECT id, attendance_date, status FROM hrd_attendance WHERE attendance_date <= '$strDateFrom' ";
  $strSQL .= "AND attendance_date < CURRENT_DATE AND status = 0  ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cek apakah melebihi
    $intDur = totalWorkDay($db, $rowDb['attendance_date'], $strNow);
    if ($intDur >= $intLimit) {
      if ($strIDList != "") {
        $strIDList .= ", ";
      }
      $strIDList .= $rowDb['id'];
    }
  }
  if ($strIDList != "") {
    // update data, otomatis tolak
    $strSQL = "UPDATE hrd_attendance SET status = '" . REQUEST_STATUS_DENIED . "', ";
    $strSQL .= "denied_time = now(), denied_by = -1 ";
    $strSQL .= "WHERE id IN ($strIDList) ";
    $resExec = $db->execute($strSQL);
  }
  // --- CEK PERMOHONAN LEMBUR ------
  //$intLimitOT = INT_LIMIT_APPROVAL_OT;
  $strIDList = "";
  $strSQL = "SELECT id, application_date, overtime_date, status FROM hrd_overtime WHERE application_date <= '$strDateFrom' ";
  $strSQL .= "AND application_date < CURRENT_DATE AND status = 0  ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cek apakah melebihi
    $intDur = totalWorkDay($db, $rowDb['application_date'], $strNow);
    if ($intDur > INT_LIMIT_APPROVAL_OT) {
      if ($strIDList != "") {
        $strIDList .= ", ";
      }
      $strIDList .= $rowDb['id'];
    }
  }
  if ($strIDList != "") {
    // update data, otomatis tolak
    $strSQL = "UPDATE hrd_overtime SET status = '2', ";
    $strSQL .= "denied_time = now(), denied_by = -1, note_denied = 'by system' ";
    $strSQL .= "WHERE id IN ($strIDList) ";
    $resExec = $db->execute($strSQL);
  }
  // cek realisasi lembur, yang belum diapprove juga
  $strIDList = "";
  $intLimitOT = 2 * INT_LIMIT_APPROVAL_OT;
  $strSQL = "SELECT id, application_date, overtime_date, status FROM hrd_overtime WHERE application_date <= '$strDateFrom' ";
  $strSQL .= "AND application_date < CURRENT_DATE AND (status = 3 OR status = 4)  ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cek apakah melebihi
    $intDur = totalWorkDay($db, $rowDb['application_date'], $strNow);
    if ($intDur > $intLimitOT) {
      if ($strIDList != "") {
        $strIDList .= ", ";
      }
      $strIDList .= $rowDb['id'];
    }
  }
  if ($strIDList != "") {
    // update data, otomatis tolak
    $strSQL = "UPDATE hrd_overtime SET status = '8', ";
    $strSQL .= "\"deniedTime1\" = now(), \"deniedBy1\" = -1, note_denied = 'by system' ";
    $strSQL .= "WHERE id IN ($strIDList) ";
    $resExec = $db->execute($strSQL);
  }
  // --- CEK PERMOHONAN CUTI ------
  $strIDList = "";
  $strSQL = "SELECT id, request_date, status FROM hrd_leave WHERE request_date <= '$strDateFrom' ";
  $strSQL .= "AND request_date < CURRENT_DATE AND status = 0  ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cek apakah melebihi
    $intDur = totalWorkDay($db, $rowDb['request_date'], $strNow);
    if ($intDur >= $intLimit) {
      if ($strIDList != "") {
        $strIDList .= ", ";
      }
      $strIDList .= $rowDb['id'];
    }
  }
  if ($strIDList != "") {
    // update data, otomatis tolak
    $strSQL = "UPDATE hrd_leave SET status = '" . REQUEST_STATUS_DENIED . "', ";
    $strSQL .= "denied_time = now(), denied_by = -1, note_denied = 'by system' ";
    $strSQL .= "WHERE id IN ($strIDList) ";
    $resExec = $db->execute($strSQL);
  }
  // --- CEK PERMOHONAN ABSEN ------
  $strIDList = "";
  $strSQL = "SELECT id, request_date, status, date_from, date_thru, id_employee ";
  $strSQL .= "FROM hrd_absence WHERE request_date <= '$strDateFrom' ";
  $strSQL .= "AND request_date < CURRENT_DATE AND status = 0  ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cek apakah melebihi
    $intDur = totalWorkDay($db, $rowDb['request_date'], $strNow);
    if ($intDur >= $intLimit) {
      if ($strIDList != "") {
        $strIDList .= ", ";
      }
      $strIDList .= $rowDb['id'];
      // potong cuti
      /*
      $intAbsDur = totalWorkDay($db, $rowDb['date_from'], $rowDb['date_thru']);
      $strYear = updateEmployeeLeave($db, $rowDb['id_employee'], $intAbsDur);

      $strSQLUpdate = "";
      if ($strYear != "") {
        // update data absence
        $strSQLUpdate  = "leave_year = '$strYear', ";
      }
      */
      $strSQL = "UPDATE hrd_absence SET status = '" . REQUEST_STATUS_DENIED . "', ";
      $strSQL .= "denied_time = now(), denied_by = -1, note_denied = 'by system' ";
      $strSQL .= "WHERE id = " . $rowDb['id'] . " ";
      $resExec = $db->execute($strSQL);
    }
  }
  $strDur = $cExec->getDuration();
  writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "check request data in $strDur ", 0);
  return true;
}//recheckRequestData
// fungsi untuk menghapus data absensi yagn digenerate oleh system
function deleteSystemGeneratedAbsence($db, $strIDEmployee, $strDate)
{
  if ($strIDEmployee == "" || $strDate == "") {
    return false;
  }
  if (!validStandardDate($strDate)) {
    return false;
  }
  $strSQL = "DELETE FROM hrd_absence WHERE id_employee = '$strIDEmployee' ";
  $strSQL .= "AND date_from = '$strDate' ";
  $strSQL .= "AND date_thru = '$strDate' ";
  $strSQL .= "AND (absence_type_code is null OR absence_type_code = '') ";
  $strSQL .= "AND status < 3 "; // yang belum diapprove
  $resExec = $db->execute($strSQL);
  return true;
} //deleteSystemGeneratedAbsence
// fungsi untuk menambahkan pemakaian cuti karyawan, sehinggu meng-update sisa cutinya
// input: database, idEmployee, jumlah cuti yang ditambahkan
// jika jumlah cuti > 0, jatah cuti berkurang, jika < 0, berarti jatah cuti bertambah
// output : mengambil data tahun lembur yang dipotongkan --
function updateEmployeeLeave($db, $strIDEmployee, $intLeave = 0)
{
  include_once("../global/employee_function.php");
  if ($strIDEmployee === "") {
    return "";
  }
  if (!is_numeric($intLeave)) {
    return "";
  }
  if ($intLeave == 0) {
    return "";
  } // gak perlu proses
  $strYear = "";
  $intDuration = $intLeave;
  if ($intDuration > 0) // nambah pemakaian cuti, ngurangi jatah cuti
  {
    $arrCuti = getEmployeeLeaveQuota($db, $strIDEmployee);
    $intRemain = $intDuration;
    if ($arrCuti['prevRemain'] > 0) {
      // tambahkan data terpakai di tahun ini
      if ($arrCuti['prevYear'] != "") {
        if ($intDuration > $arrCuti['prevRemain']) {
          $intRemain = $intDuration - $arrCuti['prevRemain'];
          $intDuration = $arrCuti['prevRemain'];
        } else {
          $intRemain = 0;
        }
        $strSQL = "UPDATE hrd_leave_history SET used = used + '$intDuration' ";
        $strSQL .= "WHERE id_employee = '$strIDEmployee' AND \"year\" = '" . $arrCuti['prevYear'] . "' ";
        $resExec = $db->execute($strSQL);
        $strYear = $arrCuti['prevYear'];
      }
    } // tambahkan di daftar cuti terpakai, untuk tahun sebelumnya
    if ($intRemain > 0) { // jika masih ada lebih, tambahkan ke jatah tahun ini
      if ($arrCuti['currYear'] != "") {
        $strSQL = "UPDATE hrd_leave_history SET used = used + '$intRemain' ";
        $strSQL .= "WHERE id_employee = '$strIDEmployee' AND \"year\" = '" . $arrCuti['currYear'] . "' ";
        $resExec = $db->execute($strSQL);
        $strYear = $arrCuti['currYear'];
      } else {
        // tambahkan
        // --- BELUM HANDLE ---
      }
    }
  } else {
    $intDuration = (0 - $intLeave); // dibulatkan
    $arrCuti = getEmployeeLeaveQuota($db, $strIDEmployee);
    $intRemain = $intDuration;
    if ($arrCuti['currTaken'] > 0) { // cek yang sudah terpakai
      if ($arrCuti['currYear'] != "") {
        if ($arrCuti['currTaken'] < $intDuration) {
          $intRemain = $intDuration - $arrCuti['currTaken']; // update sebagian
          $intDuration = $arrCuti['currTaken'];
        } else {
          $intRemain = 0;
        }
        $strSQL = "UPDATE hrd_leave_history SET used = used - '$intDuration' ";
        $strSQL .= "WHERE id_employee = '$strIDEmployee' AND \"year\" = '" . $arrCuti['currYear'] . "'; ";
        $strYear = $arrCuti['currYear'];
        $resExec = $db->execute($strSQL);
      }
    }
    // cek apakah masih ada sisa, dikurangkan dari cuti sebelumnya
    if ($intRemain > 0) {
      if ($arrCuti['prevYear'] != "") {
        $strSQL = "UPDATE hrd_leave_history SET used = used - '$intRemain' ";
        $strSQL .= "WHERE id_employee = '$strIDEmployee' AND \"year\" = '" . $arrCuti['prevYear'] . "'; ";
        $strYear = $arrCuti['prevYear'];
        $resExec = $db->execute($strSQL);
      }
    }
  }
  return $strYear;
} // updateEmployeeLeave
// fungsi untuk memeriksa, apakah karyawan dianggap TIDAK berada di kantor
// misal dalam kondisi absen, cuti, training, trip
// input: database, tanggal (asumsi valid), idEmployee (option)
// hasil disimpan dalama array, id employee sebagai index
// format output: $arrResult[$tanggal][$idEmployee] = info
// bolAbsOnly = true, jika hanya mencari yang absen (untuk kasus training atau perjalanan dinas, bisa overlap)
function getOutOfficeInfo($db, $strDateFrom, $strDateThru, $strIDEmployee = "", $bolAbsOnly = false)
{
  $arrResult = [];
  //if ($strDateFrom == "" || $strDateThru == "") return $arrResult;
  if (!validStandardDate($strDateFrom) || !validStandardDate($strDateThru)) {
    return $arrResult;
  }
  // cari info absen
  $strSQL = "SELECT * FROM hrd_absence WHERE 1=1 ";
  $strSQL .= "AND ((date_from, date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strDateFrom', DATE '$strDateThru') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateFrom') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateThru')) ";
  //$strSQL .= "AND status = '" .REQUEST_STATUS_APPROVED."' ";
  if ($strIDEmployee != "") {
    $strSQL .= "AND id_employee = '$strIDEmployee' ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cari awal dan akhirnya
    $strStart = (pgDateFormat($strDateFrom, "Ymd") < pgDateFormat($rowDb['date_from'], "Ymd")) ? pgDateFormat(
        $rowDb['date_from'],
        "Y-m-d"
    ) : pgDateFormat($strDateFrom, "Y-m-d");
    $strFinish = (pgDateFormat($strDateThru, "Ymd") < pgDateFormat($rowDb['date_thru'], "Ymd")) ? pgDateFormat(
        $strDateThru,
        "Y-m-d"
    ) : pgDateFormat($rowDb['date_thru'], "Y-m-d");
    while ($strStart <= $strFinish) {
      $arrResult[$strStart][$rowDb['id_employee']]['type'] = 0; // absen
      $arrResult[$strStart][$rowDb['id_employee']]['code'] = $rowDb['absence_type_code']; // absen
      $arrResult[$strStart][$rowDb['id_employee']]['status'] = $rowDb['status'];
      // jika code NULL, dianggap false (karena belum pasti -- atau digenerate dari system)
      $strStart = getNextDate($strStart);
    }
  }
  // cari info cuti -- approve aja
  $strSQL = "SELECT * FROM hrd_leave WHERE 1=1 ";
  $strSQL .= "AND ((date_from, date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strDateFrom', DATE '$strDateThru') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateFrom') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateThru')) ";
  $strSQL .= "AND status = '" . REQUEST_STATUS_APPROVED . "' ";
  if ($strIDEmployee != "") {
    $strSQL .= "AND id_employee = '$strIDEmployee' ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cari awal dan akhirnya
    $strStart = (pgDateFormat($strDateFrom, "Ymd") < pgDateFormat($rowDb['date_from'], "Ymd")) ? pgDateFormat(
        $rowDb['date_from'],
        "Y-m-d"
    ) : pgDateFormat($strDateFrom, "Y-m-d");
    $strFinish = (pgDateFormat($strDateThru, "Ymd") < pgDateFormat($rowDb['date_thru'], "Ymd")) ? pgDateFormat(
        $strDateThru,
        "Y-m-d"
    ) : pgDateFormat($rowDb['date_thru'], "Y-m-d");
    while ($strStart <= $strFinish) {
      $arrResult[$strStart][$rowDb['id_employee']]['type'] = 1; // cuti
      $arrResult[$strStart][$rowDb['id_employee']]['code'] = $rowDb['leave_type_code']; // absen
      $arrResult[$strStart][$rowDb['id_employee']]['status'] = $rowDb['status'];
      $strStart = getNextDate($strStart);
    }
  }
  // cari info training -- yang sudah disetujui
  $strSQL = "SELECT t2.*, t1.institution, t1.status as status1, t1.training_date, t1.training_date_thru ";
  $strSQL .= "FROM hrd_training_request AS t1, hrd_training_request_participant AS t2 ";
  $strSQL .= "WHERE t1.id = t2.id_request ";
  $strSQL .= "AND ((t1.training_date, t1.training_date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strDateFrom', DATE '$strDateThru') ";
  $strSQL .= "    OR (t1.training_date_thru = DATE '$strDateFrom') ";
  $strSQL .= "    OR (t1.training_date_thru = DATE '$strDateThru')) ";
  $strSQL .= "AND t1.status = '" . REQUEST_STATUS_APPROVED . "' ";
  if ($strIDEmployee != "") {
    $strSQL .= "AND t2.id_employee = '$strIDEmployee' ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cari awal dan akhirnya
    $strStart = (pgDateFormat($strDateFrom, "Ymd") < pgDateFormat($rowDb['training_date'], "Ymd")) ? pgDateFormat(
        $rowDb['training_date'],
        "Y-m-d"
    ) : pgDateFormat($strDateFrom, "Y-m-d");
    $strFinish = (pgDateFormat($strDateThru, "Ymd") < pgDateFormat($rowDb['training_date_thru'], "Ymd")) ? pgDateFormat(
        $strDateThru,
        "Y-m-d"
    ) : pgDateFormat($rowDb['training_date_thru'], "Y-m-d");
    while ($strStart <= $strFinish) {
      $arrResult[$strStart][$rowDb['id_employee']]['type'] = 2; // training
      $arrResult[$strStart][$rowDb['id_employee']]['code'] = $rowDb['institution']; //
      $arrResult[$strStart][$rowDb['id_employee']]['status'] = $rowDb['status1'];
      $strStart = getNextDate($strStart);
    }
  }
  // cari info perjalanan dinas -- yang sudah disetujui
  $strSQL = "SELECT * FROM hrd_trip WHERE 1=1 ";
  $strSQL .= "AND ((date_from, date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strDateFrom', DATE '$strDateThru') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateFrom') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateThru')) ";
  $strSQL .= "AND status = '" . REQUEST_STATUS_APPROVED . "' ";
  if ($strIDEmployee != "") {
    $strSQL .= "AND id_employee = '$strIDEmployee' ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cari awal dan akhirnya
    $strStart = (pgDateFormat($strDateFrom, "Ymd") < pgDateFormat($rowDb['date_from'], "Ymd")) ? pgDateFormat(
        $rowDb['date_from'],
        "Y-m-d"
    ) : pgDateFormat($strDateFrom, "Y-m-d");
    $strFinish = (pgDateFormat($strDateThru, "Ymd") < pgDateFormat($rowDb['date_thru'], "Ymd")) ? pgDateFormat(
        $strDateThru,
        "Y-m-d"
    ) : pgDateFormat($rowDb['date_thru'], "Y-m-d");
    while ($strStart <= $strFinish) {
      $arrResult[$strStart][$rowDb['id_employee']]['type'] = 3; // dinas
      $arrResult[$strStart][$rowDb['id_employee']]['code'] = $rowDb['location'];
      $arrResult[$strStart][$rowDb['id_employee']]['status'] = $rowDb['status'];
      $strStart = getNextDate($strStart);
    }
  }
  return $arrResult;
} //
// fungsi untuk mengambil informsi total kehadiran karyawan
// output berupa array, berisi total kehadiran,, datang telat dan pulang cepat
// input: tgl Awal dan Akhir, intTipe = 0(semua), 1=biasa, 2=hari libur
//, strSection bisa diisi kode section atau subsection, sedn strEmployee bisa diisi employee_id
function getEmployeeAttendance($db, $strFrom, $strThru, $intTipe = 0, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  if ($intTipe == 1) {
    $strHoliday = "AND holiday = 0";
  } else if ($intTipe == 2) { // libur
    $strHoliday = "AND holiday = 1";
  } else {
    $strHoliday = "";
  }
  // hitung total dulu semua
  $strSQL = "SELECT COUNT(t1.id) AS total, t1.id_employee, ";
  $strSQL .= "SUM(CASE WHEN (holiday = 1) THEN 1 ELSE 0 END) AS total_holiday, "; // total pulang cepat, dalam menit
  $strSQL .= "SUM(CASE WHEN (late_duration > " . LATE_TOLERANCE . " AND not_late = 'f' AND holiday = 0) THEN 1 ELSE 0 END) AS \"late\", "; // cari total keterlambatan, dalam hari
  $strSQL .= "SUM(CASE WHEN (early_duration > " . LATE_TOLERANCE . " AND holiday = 0) THEN 1 ELSE 0 END) AS early, "; // cari total keterlambatan, dalam hari
  $strSQL .= "SUM(CASE WHEN (not_late = 't' AND holiday = 1) THEN 0 ELSE t1.late_duration END) AS total_late, "; // total telat, dalam menit
  $strSQL .= "SUM(CASE WHEN (holiday = 1) THEN 0 ELSE t1.early_duration END) AS total_early, "; // total pulang cepat, dalam menit
  // cari data shift siang dan malam
  $strSQL .= "SUM(CASE WHEN (shift_type = 1) THEN 1 ELSE 0 END) AS day_shift, ";
  $strSQL .= "SUM(CASE WHEN (shift_type = 2) THEN 1 ELSE 0 END) AS night_shift, ";
  $strSQL .= "SUM(t1.transport) AS total_transport, SUM(t1.monthly) AS total_monthly ";
  $strSQL .= "FROM hrd_attendance AS t1 LEFT JOIN hrd_employee AS t2 ";
  $strSQL .= "ON t1.id_employee = t2.id ";
  $strSQL .= "WHERE t1.attendance_date BETWEEN '$strFrom' AND '$strThru' $strHoliday ";
  // cek apakah attendanceStart+finish tidak kosong
  $strSQL .= "AND (t1.attendance_start is not null OR t1.attendance_finish is not null) ";
  // cek yang isAbsence-nya false
  $strSQL .= "AND (t1.is_absence != 't') ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "GROUP BY t1.id_employee ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['id_employee']]['total'] = $rowDb['total']; // total kehadiran
    $arrResult[$rowDb['id_employee']]['totalHoliday'] = $rowDb['total_holiday']; // total kehadiran di hari libur
    $arrResult[$rowDb['id_employee']]['totalLate'] = $rowDb['total_late'];
    $arrResult[$rowDb['id_employee']]['late'] = $rowDb['late'];
    $arrResult[$rowDb['id_employee']]['totalEarly'] = $rowDb['total_early'];
    $arrResult[$rowDb['id_employee']]['early'] = $rowDb['early'];
    $arrResult[$rowDb['id_employee']]['totalTransport'] = $rowDb['total_transport'];
    $arrResult[$rowDb['id_employee']]['dayShift'] = $rowDb['day_shift'];
    $arrResult[$rowDb['id_employee']]['nightShift'] = $rowDb['night_shift'];
    $arrResult[$rowDb['id_employee']]['totalMonthly'] = $rowDb['total_monthly'];
  }
  return $arrResult;
} // getEmployeeAttendance
// fungsi untuk mengambil informsi total kehadiran karyawan
// khusus untuk karyawan di luar kantor -- yang datanya di RECAP (dari table recap)
// output berupa array, berisi total kehadiran,, datang telat dan pulang cepat, dan lembur
// input: tgl Awal dan Akhir
//, strSection bisa diisi kode section atau subsection, sedn strEmployee bisa diisi employee_id
function getEmployeeAttendanceRecap($db, $strFrom, $strThru, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  // hitung total dulu semua
  $strSQL = "SELECT t1.id_employee, SUM(t1.attendance) AS total_attendance, ";
  $strSQL .= "SUM(t1.late) AS total_late, SUM(t1.early) AS total_early, ";
  $strSQL .= "SUM(t1.l1) AS total_L1, SUM(t1.l2) AS total_L2, ";
  $strSQL .= "SUM(t1.l3) AS total_L3, SUM(t1.l4) AS total_L4 ";
  $strSQL .= "FROM hrd_attendance_recap AS t1 LEFT JOIN hrd_employee AS t2 ";
  $strSQL .= "ON t1.id_employee = t2.id ";
  $strSQL .= "WHERE t1.date_from >= '$strFrom' ";
  $strSQL .= "AND t1.date_thru <= '$strThru' ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "GROUP BY t1.id_employee ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['id_employee']]['totalAttendance'] = $rowDb['total_attendance']; // total kehadiran
    $arrResult[$rowDb['id_employee']]['totalLate'] = $rowDb['total_late'];
    $arrResult[$rowDb['id_employee']]['totalEarly'] = $rowDb['total_early'];
    $arrResult[$rowDb['id_employee']]['totalL1'] = $rowDb['total_L1'];
    $arrResult[$rowDb['id_employee']]['totalL2'] = $rowDb['total_L2'];
    $arrResult[$rowDb['id_employee']]['totalL3'] = $rowDb['total_L3'];
    $arrResult[$rowDb['id_employee']]['totalL4'] = $rowDb['total_L4'];
    $arrResult[$rowDb['id_employee']]['totalOT'] = $rowDb['total_L1'] + $rowDb['total_L2'] + $rowDb['total_L3'] + $rowDb['total_L4'];
  }
  return $arrResult;
} // getEmployeeAttendanceRecap
// fungsi untuuk mengambil informasi ketidakhadiran
// aoutput berupa array
function getEmployeeAbsence($db, $strFrom, $strThru, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  // langsung dipisah sesuai jenis absennya
  $strSQL = "SELECT t1.* FROM hrd_absence AS t1, hrd_employee AS t2 ";
  $strSQL .= "WHERE t1.id_employee = t2.id ";
  $strSQL .= "AND (status = '" . REQUEST_STATUS_APPROVED . "' OR status = '" . REQUEST_STATUS_DENIED . "') ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "AND ((date_from, date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strFrom', DATE '$strThru') ";
  $strSQL .= "    OR (date_thru = DATE '$strFrom') ";
  $strSQL .= "    OR (date_thru = DATE '$strThru')) ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cari total waktu
    (pgDateFormat($strFrom, "Ymd") < pgDateFormat(
            $rowDb['date_from'],
            "Ymd"
        )) ? $strDateStart = $rowDb['date_from'] : $strDateStart = $strFrom;
    (pgDateFormat($strThru, "Ymd") < pgDateFormat(
            $rowDb['date_thru'],
            "Ymd"
        )) ? $strDateFinish = $strThru : $strDateFinish = $rowDb['date_thru'];
    $intTotal = totalWorkDay($db, $strDateStart, $strDateFinish);
    if (isset($arrResult[$rowDb['id_employee']][$rowDb['absence_type_code']])) {
      $arrResult[$rowDb['id_employee']][$rowDb['absence_type_code']] += $intTotal;
    } else {
      $arrResult[$rowDb['id_employee']][$rowDb['absence_type_code']] = $intTotal;
    }
    if (isset($arrResult[$rowDb['id_employee']]['total'])) {
      $arrResult[$rowDb['id_employee']]['total'] += $intTotal;
    } else {
      $arrResult[$rowDb['id_employee']]['total'] = $intTotal;
    }
  }
  return $arrResult;
} // getEmployeeAbsence
// fungsi untuuk mengambil informasi cuti KESELURUHAN
// output berupa array
function getEmployeeLeave($db, $strFrom, $strThru, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  // langsung dipisah sesuai jenis absennya
  $strSQL = "SELECT t1.* FROM hrd_leave AS t1, hrd_employee AS t2 ";
  $strSQL .= "WHERE t1.id_employee = t2.id  AND status = '" . REQUEST_STATUS_APPROVED . "' ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "AND ((date_from, date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strFrom', DATE '$strThru') ";
  $strSQL .= "    OR (date_thru = DATE '$strFrom') ";
  $strSQL .= "    OR (date_thru = DATE '$strThru')) ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cari total waktu
    (pgDateFormat($strFrom, "Ymd") < pgDateFormat(
            $rowDb['date_from'],
            "Ymd"
        )) ? $strDateStart = $rowDb['date_from'] : $strDateStart = $strFrom;
    (pgDateFormat($strThru, "Ymd") < pgDateFormat(
            $rowDb['date_thru'],
            "Ymd"
        )) ? $strDateFinish = $strThru : $strDateFinish = $rowDb['date_thru'];
    $intTotal = totalWorkDay($db, $strDateStart, $strDateFinish);
    if (isset($arrResult[$rowDb['id_employee']][$rowDb['leave_type_code']])) {
      $arrResult[$rowDb['id_employee']][$rowDb['leave_type_code']] += $intTotal;
    } else {
      $arrResult[$rowDb['id_employee']][$rowDb['leave_type_code']] = $intTotal;
    }
    if (isset($arrResult[$rowDb['id_employee']]['total'])) {
      $arrResult[$rowDb['id_employee']]['total'] += $intTotal;
    } else {
      $arrResult[$rowDb['id_employee']]['total'] = $intTotal;
    }
  }
  return $arrResult;
} // getEmployeeLeave
// fungsi untuuk mengambil informasi cuti BULANANN (HAID), khusus untuk wanita
// output berupa array
function getEmployeeLeaveMonthly($db, $strFrom, $strThru, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  // langsung dipisah sesuai jenis absennya
  $strSQL = "SELECT t1.* FROM hrd_leave AS t1, hrd_employee AS t2 ";
  $strSQL .= "WHERE t1.id_employee = t2.id AND t2.gender = 0 ";
  $strSQL .= "AND leave_type_code = 2 AND status = '" . REQUEST_STATUS_APPROVED . "' ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "AND ((date_from, date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strFrom', DATE '$strThru') ";
  $strSQL .= "    OR (date_thru = DATE '$strFrom') ";
  $strSQL .= "    OR (date_thru = DATE '$strThru')) ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cari total waktu
    (pgDateFormat($strFrom, "Ymd") < pgDateFormat(
            $rowDb['date_from'],
            "Ymd"
        )) ? $strDateStart = $rowDb['date_from'] : $strDateStart = $strFrom;
    (pgDateFormat($strThru, "Ymd") < pgDateFormat(
            $rowDb['date_thru'],
            "Ymd"
        )) ? $strDateFinish = $strThru : $strDateFinish = $rowDb['date_thru'];
    $intTotal = totalWorkDay($db, $strDateStart, $strDateFinish);
    if (isset($arrResult[$rowDb['id_employee']])) {
      $arrResult[$rowDb['id_employee']] += $intTotal;
    } else {
      $arrResult[$rowDb['id_employee']] = $intTotal;
    }
  }
  return $arrResult;
} // getEmployeeLeave
// fungsi untuuk mengambil informasi cuti MELAHIRKAN, khusus untuk wanita
// output berupa array
function getEmployeeLeaveMaternity($db, $strFrom, $strThru, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  // langsung dipisah sesuai jenis absennya
  $strSQL = "SELECT t1.* FROM hrd_leave AS t1, hrd_employee AS t2 ";
  $strSQL .= "WHERE t1.id_employee = t2.id AND t2.gender = 0 ";
  $strSQL .= "AND leave_type_code = 1 AND status = '" . REQUEST_STATUS_APPROVED . "' ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "AND ((date_from, date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strFrom', DATE '$strThru') ";
  $strSQL .= "    OR (date_thru = DATE '$strFrom') ";
  $strSQL .= "    OR (date_thru = DATE '$strThru')) ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cari total waktu
    (pgDateFormat($strFrom, "Ymd") < pgDateFormat(
            $rowDb['date_from'],
            "Ymd"
        )) ? $strDateStart = $rowDb['date_from'] : $strDateStart = $strFrom;
    (pgDateFormat($strThru, "Ymd") < pgDateFormat(
            $rowDb['date_thru'],
            "Ymd"
        )) ? $strDateFinish = $strThru : $strDateFinish = $rowDb['date_thru'];
    $intTotal = totalWorkDay($db, $strDateStart, $strDateFinish);
    if (isset($arrResult[$rowDb['id_employee']])) {
      $arrResult[$rowDb['id_employee']] += $intTotal;
    } else {
      $arrResult[$rowDb['id_employee']] = $intTotal;
    }
  }
  return $arrResult;
} // getEmployeeMaternnity
// fungsi untuuk mengambil informasi cuti TAHUNAN
// output berupa array
function getEmployeeLeaveAnnual($db, $strFrom, $strThru, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  // langsung dipisah sesuai jenis absennya
  $strSQL = "SELECT t1.* FROM hrd_leave AS t1, hrd_employee AS t2 ";
  $strSQL .= "WHERE t1.id_employee = t2.id AND t1.leave_type_code = 0 AND status = '" . REQUEST_STATUS_APPROVED . "' ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "AND ((date_from, date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strFrom', DATE '$strThru') ";
  $strSQL .= "    OR (date_thru = DATE '$strFrom') ";
  $strSQL .= "    OR (date_thru = DATE '$strThru')) ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cari total waktu
    (pgDateFormat($strFrom, "Ymd") < pgDateFormat(
            $rowDb['date_from'],
            "Ymd"
        )) ? $strDateStart = $rowDb['date_from'] : $strDateStart = $strFrom;
    (pgDateFormat($strThru, "Ymd") < pgDateFormat(
            $rowDb['date_thru'],
            "Ymd"
        )) ? $strDateFinish = $strThru : $strDateFinish = $rowDb['date_thru'];
    $intTotal = totalWorkDay($db, $strDateStart, $strDateFinish);
    if (isset($arrResult[$rowDb['id_employee']])) {
      $arrResult[$rowDb['id_employee']] += $intTotal;
    } else {
      $arrResult[$rowDb['id_employee']] = $intTotal;
    }
  }
  return $arrResult;
} // getEmployeeAnnual
// fungsi untuk mengambil daftar overtime, berdasarkan aplikasi overtime per employee
// output berupa array, rekap total per employee
// input, tglAwal, tglAkhir, intTipe(0=semua,1=biasa,2=libur), section/subsectin dan employee_id
function getEmployeeOvertimeApplication($db, $strFrom, $strThru, $intTipe = 0, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  if ($intTipe == 1) {
    $strHoliday = "AND holiday = 0";
  } else if ($intTipe == 2) { // libur
    $strHoliday = "AND holiday = 1";
  } else {
    $strHoliday = "";
  }
  $arrOTType = getOvertimeTypeValue($db);
  // cek validasi
  for ($i = 1; $i <= 4; $i++) {
    if (!is_numeric($arrOTType[$i])) {
      $arrOTType[$i] = 1;
    } // default
  }
  // hitung total dulu semua
  $strSQL = "SELECT COUNT(t1.id) AS total, t1.id_employee, ";
  // cari data lembur per menit
  $strSQL .= "SUM(CASE WHEN t3.holiday = 0 THEN t3.l1 ELSE 0 END) AS total_l1_normal, ";
  $strSQL .= "SUM(CASE WHEN t3.holiday = 0 THEN t3.l2 ELSE 0 END) AS total_l2_normal, ";
  $strSQL .= "SUM(CASE WHEN t3.holiday = 0 THEN t3.l3 ELSE 0 END) AS total_l3_normal, ";
  $strSQL .= "SUM(CASE WHEN t3.holiday = 0 THEN t3.l4 ELSE 0 END) AS total_l4_normal, ";
  $strSQL .= "SUM(CASE WHEN t3.holiday = 1 THEN t3.l1 ELSE 0 END) AS total_l1_holiday, ";
  $strSQL .= "SUM(CASE WHEN t3.holiday = 1 THEN t3.l2 ELSE 0 END) AS total_l2_holiday, ";
  $strSQL .= "SUM(CASE WHEN t3.holiday = 1 THEN t3.l3 ELSE 0 END) AS total_l3_holiday, ";
  $strSQL .= "SUM(CASE WHEN t3.holiday = 1 THEN t3.l4 ELSE 0 END) AS total_l4_holiday, ";
  $strTmpKriteria = "((t3.l1 + t3.l2) > 150)"; // kriteria dihiting lembur, untuk bahan hitung jumlah hari kerja
  // yang diambil hanya yang lembur diatas 2.5 jam (entah, benar atau gak, ini aja dipakai)
  $strSQL .= "SUM(CASE WHEN ($strTmpKriteria AND t3.holiday = 0) THEN 1 ELSE 0 END) AS total_overtime_normal, "; // total hari lembur di hari biasa
  $strSQL .= "SUM(CASE WHEN ($strTmpKriteria AND t3.holiday = 0 AND t3.transport = 0) THEN 1 ELSE 0 END) AS total_overtime_normal_no_bus, "; // total hari lembur di hari biasa, tanpa transport
  $strSQL .= "SUM(CASE WHEN ($strTmpKriteria AND t3.holiday = 1 AND t3.transport = 0) THEN 1 ELSE 0 END) AS total_overtime_holiday_no_bus, "; // total hari lembur di hari libur, tanpa transport, jika pulang lebih awal
  $strSQL .= "SUM(CASE WHEN ($strTmpKriteria AND t3.holiday = 1 AND t3.transport = 1) THEN 1 ELSE 0 END) AS total_overtime_holiday_bus "; // total hari lembur di hari libur, dengan transport
  $strSQL .= "FROM hrd_overtime_application_employee AS t1, ";
  $strSQL .= "hrd_overtime_application AS t2, hrd_attendance AS t3, hrd_employee AS t4 ";
  $strSQL .= "WHERE t1.id_employee = t4.id AND t1.id_application = t2.id ";
  $strSQL .= "AND t2.overtime_date = t3.attendance_date AND t3.id_employee = t4.id ";
  $strSQL .= "AND t2.overtime_date BETWEEN '$strFrom' AND '$strThru' $strHoliday ";
  $strSQL .= "AND t2.status = 2 "; // hanya yang sudah approve
  if ($strSection != "") {
    $strSQL .= "AND (t4.section_code = '$strSection' OR t4.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t4.employee_id = '$strEmployee' ";
  }
  $strSQL .= "GROUP BY t1.id_employee ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['id_employee']]['total'] = $rowDb['total']; // total kehadiran
    $arrResult[$rowDb['id_employee']]['totalL1Normal'] = $rowDb['total_l1_normal'];
    $arrResult[$rowDb['id_employee']]['totalL2Normal'] = $rowDb['total_l2_normal'];
    $arrResult[$rowDb['id_employee']]['totalL3Normal'] = $rowDb['total_l3_normal'];
    $arrResult[$rowDb['id_employee']]['totalL4Normal'] = $rowDb['total_l4_normal'];
    $arrResult[$rowDb['id_employee']]['totalL1Holiday'] = $rowDb['total_l1_holiday'];
    $arrResult[$rowDb['id_employee']]['totalL2Holiday'] = $rowDb['total_l2_holiday'];
    $arrResult[$rowDb['id_employee']]['totalL3Holiday'] = $rowDb['total_l3_holiday'];
    $arrResult[$rowDb['id_employee']]['totalL4Holiday'] = $rowDb['total_l4_holiday'];
    $arrResult[$rowDb['id_employee']]['totalOvertimeNormal'] = $rowDb['total_overtime_normal'];
    $arrResult[$rowDb['id_employee']]['totalOvertimeNormalNoBus'] = $rowDb['total_overtime_normal_no_bus'];
    $arrResult[$rowDb['id_employee']]['totalOvertimeHolidayNoBus'] = $rowDb['total_overtime_holiday_no_bus'];
    $arrResult[$rowDb['id_employee']]['totalOvertimeHolidayBus'] = $rowDb['total_overtime_holiday_bus'];
    //$arrResult[$rowDb['id_employee']]['totalTransport'] = $rowDb['totalTransport'];
  }
  return $arrResult;
} // getEmployeeApplicationOvertime
// fungsi untuk ambil data overtime, dari tabel overtime (bukan dari overtime application)
function getEmployeeOvertime($db, $strFrom, $strThru, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  // hitung total dulu semua
  $strSQL = "SELECT  t1.id_employee, ";
  $strSQL .= "SUM(t1.ot1) AS total1, SUM(t1.ot2) AS total2, ";
  $strSQL .= "SUM(t1.ot3) AS total3, SUM(t1.ot4) AS total4 ";
  $strSQL .= "FROM hrd_overtime AS t1 LEFT JOIN hrd_employee AS t2 ";
  $strSQL .= "ON t1.id_employee = t2.id ";
  $strSQL .= "WHERE t1.status = '" . OVERTIME_STATUS_APPROVED . "' ";
  $strSQL .= "AND t1.overtime_date BETWEEN '$strFrom' AND '$strThru' ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "GROUP BY t1.id_employee ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['id_employee']]['total1'] = $rowDb['total1']; // total ot
    $arrResult[$rowDb['id_employee']]['total2'] = $rowDb['total2']; // total ot
    $arrResult[$rowDb['id_employee']]['total3'] = $rowDb['total3']; // total ot
    $arrResult[$rowDb['id_employee']]['total4'] = $rowDb['total4']; // total ot
  }
  return $arrResult;
} // getEmployeeOvertime
// fungsi untuk mengambil data absen/cuti karyawan pada hari tertentu
// output berupa array
function getAbsenceInfoByEmployee($db, $strDate, $strID)
{
  $arrResult = ["tipe" => "", "code" => ""]; // tipe = 0=abs, 1=leave
  $strSQL = "SELECT absence_type_code FROM hrd_absence ";
  $strSQL .= "WHERE id_employee = '$strID' ";
  $strSQL .= "AND '$strDate' BETWEEN date_from AND date_thru ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $arrResult['tipe'] = "absence";
    $arrResult['code'] = $rowDb['absence_type_code'];
    echo $rowDb['absence_type_code'] . " > $strDate <BR>";
  } else {
    // cari cuti
    $strSQL = "SELECT absence_type_code FROM hrd_absence ";
    $strSQL .= "WHERE id_employee = '$strID' AND status = '" . REQUEST_STATUS_APPROVED . "' ";
    $strSQL .= "AND '$strDate' BETWEEN date_from AND date_thru ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrResult['tipe'] = "leave";
      $arrResult['code'] = ""; //kosongin aja
    } else {
      // cari kemungkinan baru join atau resign
      $strSQL = "SELECT id, join_date, resign_date FROM hrd_employee ";
      $strSQL .= "WHERE id = '$strID' ";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        if ($rowDb['resign_date'] < $strDate) { // resign
          $arrResult['tipe'] = "out";
          $arrResult['code'] = "";
        } else if ($rowDb['join_date'] > $strDate) { // belum masuk
          $arrResult['tipe'] = "out";
          $arrResult['code'] = "";
        }
      }
    }
  }
  return $arrResult;
}//getAbsenceInfoByEmployee
// mengambil total business trip yang dilakukan
// aoutput berupa array
function getEmployeeTrip($db, $strFrom, $strThru, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  // langsung dipisah sesuai jenis absennya
  $strSQL = "SELECT t1.* FROM hrd_trip AS t1, hrd_employee AS t2 ";
  $strSQL .= "WHERE t1.id_employee = t2.id AND status = '" . REQUEST_STATUS_APPROVED . "' ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "AND ((date_from, date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strFrom', DATE '$strThru') ";
  $strSQL .= "    OR (date_thru = DATE '$strFrom') ";
  $strSQL .= "    OR (date_thru = DATE '$strThru')) ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cari total waktu
    (pgDateFormat($strFrom, "Ymd") < pgDateFormat(
            $rowDb['date_from'],
            "Ymd"
        )) ? $strDateStart = $rowDb['date_from'] : $strDateStart = $strFrom;
    (pgDateFormat($strThru, "Ymd") < pgDateFormat(
            $rowDb['date_thru'],
            "Ymd"
        )) ? $strDateFinish = $strThru : $strDateFinish = $rowDb['date_thru'];
    $intTotal = totalWorkDay($db, $strDateStart, $strDateFinish);
    if (isset($arrResult[$rowDb['id_employee']]['total'])) {
      $arrResult[$rowDb['id_employee']]['total'] += $intTotal;
    } else {
      $arrResult[$rowDb['id_employee']]['total'] = $intTotal;
    }
  }
  return $arrResult;
} // getEmployeeTrip
// ambil data training karyawan
// aoutput berupa array
function getEmployeeTraining($db, $strFrom, $strThru, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  // langsung dipisah sesuai jenis absennya
  $strSQL = "SELECT t1.* FROM hrd_training_request AS t1, hrd_employee AS t2, hrd_training_request_participant AS t3 ";
  $strSQL .= "WHERE t3.id_employee = t2.id AND t1.status = '" . REQUEST_STATUS_APPROVED . "' ";
  $strSQL .= "AND t1.id = t3.id_request ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "AND ((t1.training_date, t1.training_date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strFrom', DATE '$strThru') ";
  $strSQL .= "    OR (t1.training_date_thru = DATE '$strFrom') ";
  $strSQL .= "    OR (t1.training_date_thru = DATE '$strThru')) ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cari total waktu
    (pgDateFormat($strFrom, "Ymd") < pgDateFormat(
            $rowDb['training_date'],
            "Ymd"
        )) ? $strDateStart = $rowDb['training_date'] : $strDateStart = $strFrom;
    (pgDateFormat($strThru, "Ymd") < pgDateFormat(
            $rowDb['training_date_thru'],
            "Ymd"
        )) ? $strDateFinish = $strThru : $strDateFinish = $rowDb['training_date_thru'];
    $intTotal = totalWorkDay($db, $strDateStart, $strDateFinish);
    if (isset($arrResult[$rowDb['id_employee']]['total'])) {
      $arrResult[$rowDb['id_employee']]['total'] += $intTotal;
    } else {
      $arrResult[$rowDb['id_employee']]['total'] = $intTotal;
    }
  }
  return $arrResult;
} // getEmployeeTraining
?>