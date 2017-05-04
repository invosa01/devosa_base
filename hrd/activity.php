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
  $strSQL .= "AND ((t1.date_from, t1.date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strDateFrom', DATE '$strDateThru') ";
  $strSQL .= "    OR (date_from = DATE '$strDateFrom') ";
  $strSQL .= "    OR (date_from = DATE '$strDateThru') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateFrom') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateThru')) ";
  $strSQL .= "AND t1.status <> '" . REQUEST_STATUS_DENIED . "' ";
  if ($strIDException != "") {
    $strSQL .= "AND t1.id <> '$strIDException' ";
  }
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $arrResult['status'] = true;
    $arrResult['from'] = $rowDb['date_from'];
    $arrResult['thru'] = $rowDb['date_thru'];
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
  // ambil data shift
  $arrShift = getShiftSchedule($db, $strDateFrom, $strDateThru);
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
  $strUpdaterID = (isset($_SESSION['sessionUserID'])) ? $_SESSION['sessionUserID'] : 0;
  $i = 0;
  $strSQL = "";
  while ($strCurrent <= $strDateThru) {
    // cek setiap employee
    foreach ($arrEmp AS $strID => $strData) {
      if (!isEmployeeHoliday($strCurrent, $strID, $arrShift)) {
        //tidak ada hadir dan tidak dinas luar, tidak ada data absen
        if (!isset($arrAtt[$strID][$strCurrent]) && !isset($arrOut[$strCurrent][$strID])) {
          // tambahkan data absensi dengan jenis absen
          $strSQL .= "INSERT INTO hrd_absence (created, modified_by, created_by, request_date, ";
          $strSQL .= "date_from, date_thru, absence_type_code, ";
          $strSQL .= "id_employee, note, status, leave_year) ";
          $strSQL .= "VALUES (now(), '$strUpdaterID', '$strUpdaterID', '$strCurrent', ";
          $strSQL .= "'$strCurrent', '$strCurrent', NULL, '$strID', 'generated by system'," . REQUEST_STATUS_NEW . ", NULL); ";
          // tambahkan data absensi, yang siap potong cuti :-<
          $strSQL .= "INSERT INTO hrd_absence_detail (created, modified_by, created_by, absence_date, ";
          $strSQL .= "absence_type, id_employee) ";
          $strSQL .= "VALUES (now(), '$strUpdaterID', '$strUpdaterID', '$strCurrent', ";
          $strSQL .= "NULL, '$strID'); ";
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
  checkInvalidAttendance($db, $strDateFrom, $strDateThru);
  syncShiftAttendance($db, $strDateFrom, $strDateThru);
  //syncOvertimeApplication($db, $strDateFrom, $strDateThru);
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
/*
function updateEmployeeLeave($db, $strIDEmployee, $intLeave = 0) {
  include_once("../global/employee_function.php");

  if ($strIDEmployee === "") return "";
  if (!is_numeric($intLeave)) return "";
  if ($intLeave == 0) return ""; // gak perlu proses

  $strYear = "";
  $objLeave = new clsAnnualLeave($db);

  $intDuration = $intLeave;
  if ($intDuration > 0 ) // nambah pemakaian cuti, ngurangi jatah cuti
  {
    $arrCuti = $objLeave->getEmployeeLeaveInfo($strIDEmployee);

    $intRemain = $intDuration;

    if ($intRemain > 0) { // jika masih ada lebih, tambahkan ke jatah tahun ini
      if ($arrCuti['curr']['year'] != "") {
        $strSQL  = "UPDATE hrd_leave_history SET used = used + '$intRemain' ";
        $strSQL .= "WHERE id_employee = '$strIDEmployee' AND \"year\" = '" .$arrCuti['curr']['year']."' ";
        $resExec = $db->execute($strSQL);
        $strYear = $arrCuti['curr']['year'];
      }
    }
    echo $strSQL;
  }
  else
  {
    $intDuration = (0 - $intLeave); // dibulatkan
    $arrCuti = getEmployeeLeaveQuota($db, $strIDEmployee);

    $intRemain = $intDuration;

    if ($arrCuti['currTaken'] > 0) { // cek yang sudah terpakai
      if ($arrCuti['curr']['year'] != "") {
        if ($arrCuti['currTaken'] < $intDuration) {
          $intDuration = $arrCuti['currTaken'];
        }

        $strSQL  = "UPDATE hrd_leave_history SET used = used - '$intDuration' ";
        $strSQL .= "WHERE id_employee = '$strIDEmployee' AND \"year\" = '" .$arrCuti['curr']['year']."'; ";
        echo $strSQL;
        $strYear = $arrCuti['curr']['year'];
        $resExec = $db->execute($strSQL);
      }
    }
  }

  return $strYear;
} // updateEmployeeLeave

*/
//by Brian - ditambah last generated grand leave & perubahan cara pemotongan leave
//perubahan cara pemotongan leave akan mengikuti cls_annual_leave::saveLeaveHistory
function updateEmployeeLeave($db, $strIDEmployee, $intLeave = 0, $fltLeaveWeight = 1)
{
  include_once("../global/employee_function.php");
  include_once('cls_annual_leave.php');
  $strYear = "";
  $objLeave = new clsAnnualLeave($db);
  $objLeave->generateEmployeeAnnualLeave($strIDEmployee);
  $intDuration = $intLeave;
  if ($intDuration > 0) // nambah pemakaian cuti, ngurangi jatah cuti
  {
    $arrCuti = $objLeave->getEmployeeLeaveInfo($strIDEmployee);
    //untuk sortir $arrCuti
    $arrCuti_sorted = []; //untuk disortir
    $bolGrand = $bolPrev = true;
    if ($arrCuti['prev']['year'] == "") {
      $bolPrev = false;
    }
    if ($arrCuti['grand']['year'] == "") {
      $bolGrand = false;
    }
    if ($bolPrev) {
      $arrCuti_sorted[] = $arrCuti['prev'];
    }
    if ($bolGrand) {
      $arrCuti_sorted[] = $arrCuti['grand'];
    }
    $arrCuti_sorted[] = $arrCuti['curr'];
    if (count($arrCuti_sorted) == 0) {
      echo "Leave data doesn't exist";
      return $strYear;
    }
    //perbandingan string, namun ttp lancar karena dibandingkan per character. Akan SALAH bila format tidak yyyy-mm-dd
    for ($intTempI = 0; $intTempI < count($arrCuti_sorted) - 1; $intTempI++) {
      for ($intTempJ = $intTempI + 1; $intTempJ > 0; $intTempJ--) {
        while ($arrCuti_sorted[$intTempJ]['exp'] < $arrCuti_sorted[$intTempJ - 1]['exp']) {
          $arrTempSwap = $arrCuti_sorted[$intTempJ];
          $arrCuti_sorted[$intTempJ] = $arrCuti_sorted[$intTempJ - 1];
          $arrCuti_sorted[$intTempJ - 1] = $arrTempSwap;
        }
      }
    }
    $intTempI = 0;
    $intRemain = $intDuration;
    //      print_r($arrCuti_sorted[$intTempI]['overdue']);
    //      die();
    //      //while($intRemain > 0){
    for ($i = 0; $i <= $intRemain; $i++) {
      $intDuration = $intRemain;
      if ($arrCuti_sorted[$intTempI]['remain'] > 0 && $arrCuti_sorted[$intTempI]['overdue'] == "f") {
        if ($intRemain > $arrCuti_sorted[$intTempI]['remain']) {
          $intRemain = $intRemain - $arrCuti_sorted[$intTempI]['remain'];
          $intDuration = $arrCuti_sorted[$intTempI]['remain'];
        } else {
          $intRemain = 0;
        }
        $strSQL = "UPDATE \"hrd_leave_history\" SET used = used + ($intDuration * $fltLeaveWeight)";
        $strSQL .= "WHERE \"id_employee\" = '$strIDEmployee' AND \"year\" = '" . $arrCuti_sorted[$intTempI]['year'] . "' ";
        $resExec = $db->execute($strSQL);
        $strYear = $arrCuti_sorted[$intTempI]['year'];
      }
    }
  } /*
      $intRemain = $intDuration;
      if ($arrCuti['prev']['remain'] > 0 && $arrCuti['prev']['overdue'] == false)
      {
        // tambahkan data terpakai di tahun ini
        if ($arrCuti['prev']['year'] != "") {
          if ($intDuration > $arrCuti['prev']['remain']) {
            $intRemain = $intDuration - $arrCuti['prev']['remain'];
            $intDuration = $arrCuti['prev']['remain'];
          } else {
            $intRemain = 0;
          }

          $strSQL  = "UPDATE \"hrd_leave_history\" SET used = used + ($intDuration * $fltLeaveWeight)";
          $strSQL .= "WHERE \"id_employee\" = '$strIDEmployee' AND \"year\" = '" .$arrCuti['prev']['year']."' ";
          $resExec = $db->execute($strSQL);

          $strYear = $arrCuti['prev']['year'];
        }
      } // tambahkan di daftar cuti terpakai, untuk tahun sebelumnya

      if ($intRemain > 0)
      { // jika masih ada lebih, tambahkan ke jatah tahun ini
        if ($arrCuti['curr']['year'] != "") {
          $strSQL  = "UPDATE \"hrd_leave_history\" SET used = used + ($intRemain * $fltLeaveWeight) ";
          $strSQL .= "WHERE \"id_employee\" = '$strIDEmployee' AND \"year\" = '" .$arrCuti['curr']['year']."' ";
          $resExec = $db->execute($strSQL);
          $strYear = $arrCuti['curr']['year'];

        }
        else 
        {
           echo "Leave data doesn't exist";
        }
      }
    }
    */
  else {
    $intDuration = (0 - $intLeave); // direverse
    $arrCuti = $objLeave->getEmployeeLeaveInfo($strIDEmployee);
    $intRemain = $intDuration;
    if ($arrCuti['curr']['taken'] > 0) { // cek yang sudah terpakai
      if ($arrCuti['curr']['year'] != "") {
        if ($arrCuti['curr']['taken'] < $intDuration) {
          $intRemain = $intDuration - $arrCuti['curr']['taken']; // update sebagian
          $intDuration = $arrCuti['curr']['taken'];
        } else {
          $intRemain = 0;
        }
        $strSQL = "UPDATE \"hrd_leave_history\" SET used = used - ($intDuration * $fltLeaveWeight) ";
        $strSQL .= "WHERE \"id_employee\" = '$strIDEmployee' AND \"year\" = '" . $arrCuti['curr']['year'] . "'; ";
        $strYear = $arrCuti['curr']['year'];
        $resExec = $db->execute($strSQL);
      }
    }
    // cek apakah masih ada sisa, dikurangkan dari cuti sebelumnya
    if ($intRemain > 0) {
      if ($arrCuti['prev']['year'] != "") {
        if ($arrCuti['prev']['taken'] < $intRemain) {
          $intRemain = $arrCuti['prev']['taken'];
        }
        $strSQL = "UPDATE \"hrd_leave_history\" SET used = used - ($intRemain * $fltLeaveWeight) ";
        $strSQL .= "WHERE \"id_employee\" = '$strIDEmployee' AND \"year\" = '" . $arrCuti['prev']['year'] . "'; ";
        $strYear = $arrCuti['prev']['year'];
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
  $strSQL = "SELECT t1.*, t3.status FROM hrd_absence_detail AS t1 ";
  $strSQL .= "LEFT JOIN hrd_absence_type AS t2 ON t1.absence_type = t2.code ";
  $strSQL .= "LEFT JOIN hrd_absence AS t3 ON t1.id_absence = t3.id ";
  $strSQL .= "WHERE (absence_date BETWEEN '$strDateFrom' AND '$strDateThru' OR absence_date = '$strDateFrom') ";
  $strSQL .= "AND t2.is_leave = FALSE AND t3.status >= " . REQUEST_STATUS_APPROVED . " ";
  if ($strIDEmployee != "") {
    $strSQL .= "AND t1.id_employee = '$strIDEmployee' ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['absence_date']][$rowDb['id_employee']] = $rowDb; // absen
    $arrResult[$rowDb['absence_date']][$rowDb['id_employee']]['code'] = $rowDb['absence_type'];
    $arrResult[$rowDb['absence_date']][$rowDb['id_employee']]['type'] = 0;
    $arrResult[$rowDb['absence_date']][$rowDb['id_employee']]['status'] = $rowDb['status'];
  }
  // cari info cuti -- approve aja
  $strSQL = "SELECT t1.*, t3.status FROM hrd_absence_detail AS t1 ";
  $strSQL .= "LEFT JOIN hrd_absence_type AS t2 ON t1.absence_type = t2.code ";
  $strSQL .= "LEFT JOIN hrd_absence AS t3 ON t1.id_absence = t3.id ";
  $strSQL .= "WHERE (absence_date BETWEEN '$strDateFrom' AND '$strDateThru' OR absence_date = '$strDateFrom') ";
  $strSQL .= "AND t2.is_leave = TRUE  AND t3.status >= " . REQUEST_STATUS_APPROVED . " ";
  if ($strIDEmployee != "") {
    $strSQL .= "AND t1.id_employee = '$strIDEmployee' ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['absence_date']][$rowDb['id_employee']]['type'] = 1; // cuti
    $arrResult[$rowDb['absence_date']][$rowDb['id_employee']]['code'] = $rowDb['absence_type'];
    $arrResult[$rowDb['absence_date']][$rowDb['id_employee']]['status'] = $rowDb['status'];
  }
  // cari info training -- yang sudah disetujui
  // $strSQL  = "SELECT t2.*, t1.institution, t1.status as status1, t3.trainingdate ";
  // $strSQL .= "FROM hrd_training_request AS t1, hrd_training_request_participant AS t2, hrd_training_request_detailtime AS t3  ";
  // $strSQL .= "FROM hrd_training_request AS t1, hrd_training_request_participant AS t2, hrd_training_request_detailtime AS t3  ";
  // $strSQL .= "WHERE t1.id = t2.id_request and t1.id = t3.id_request ";
  // $strSQL .= "AND trainingdate BETWEEN DATE '$strDateFrom' AND DATE '$strDateThru' ";
  // $strSQL .= "AND t1.status >= '" .REQUEST_STATUS_APPROVED."' ";
  $strSQL = "SELECT t2.*, t1.status as status1, t3.trainingdate ";
  $strSQL .= "FROM hrd_training_request AS t1 ";
  $strSQL .= "LEFT JOIN hrd_training_request_participant AS t2 ON t1.id=t2.id_request ";
  $strSQL .= "LEFT JOIN hrd_training_request_detailtime AS t3 ON t1.id=t3.id_request ";
  // $strSQL .= "FROM hrd_training_request AS t1, hrd_training_request_participant AS t2, hrd_training_request_detailtime AS t3  ";
  $strSQL .= "WHERE  trainingdate BETWEEN DATE '$strDateFrom' AND DATE '$strDateThru' ";
  $strSQL .= "AND t1.status >= '" . REQUEST_STATUS_APPROVED . "' ";
  if ($strIDEmployee != "") {
    $strSQL .= "AND t2.id_employee = '$strIDEmployee' ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['trainingdate']][$rowDb['id_employee']]['type'] = 2; // training
    $arrResult[$rowDb['trainingdate']][$rowDb['id_employee']]['code'] = "Training"; //
    $arrResult[$rowDb['trainingdate']][$rowDb['id_employee']]['status'] = $rowDb['status1'];
  }
  // cari info perjalanan dinas -- yang sudah disetujui
  $strSQL = "SELECT * FROM hrd_trip WHERE 1=1 ";
  $strSQL .= "AND ((date_from, date_thru) ";
  $strSQL .= "    OVERLAPS (DATE '$strDateFrom', DATE '$strDateThru') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateFrom') ";
  $strSQL .= "    OR (date_thru = DATE '$strDateThru')) ";
  $strSQL .= "AND status >= '" . REQUEST_STATUS_APPROVED . "' ";
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
      $arrResult[$strStart][$rowDb['id_employee']]['code'] = $rowDb['trip_type'];
      $arrResult[$strStart][$rowDb['id_employee']]['status'] = $rowDb['status'];
      $strStart = getNextDate($strStart);
    }
  }
  return $arrResult;
} //
//fungsi untuk mengambil jadwal shift
function getShiftScheduleByDate($db, $strDate, $strSection = "", $strSubSection = "", $strEmployee = "")
{
  $arrResult = [];
  $strKriteria = ""; // kriteria tambahan
  if ($strEmployee != "") {
    $strKriteria .= "AND id_employee = '$strEmployee' ";
  }
  if ($strSection != "" || $strSubSection != "") {
    $strKriteria .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE flag = 0 ";
    if ($strSection != "") {
      $strKriteria .= " AND \"section_code\" = '$strSection' ";
    }
    if ($strSubSection != "") {
      $strKriteria .= " AND \"sub_section_code\" = '$strSubSection' ";
    }
    $strKriteria .= ") ";
  }
  $strSQL = "
      SELECT t1.*, t2.\"shift_off\", t2.id as shift_id
      FROM \"hrd_shift_schedule_employee\" AS t1
      LEFT JOIN \"hrd_shift_type\" AS t2 ON t1.\"shift_code\" = t2.code
      WHERE t1.\"shift_date\" = '$strDate' $strKriteria
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['id_employee']] = $rowDb;
    $arrResult[$rowDb['id_employee']]['is_night'] = ($rowDb['start_time'] > $rowDb['finish_time']);
  }
  return $arrResult;
}

//fungsi untuk mengambil jadwal shift
function getShiftSchedule($db, $strDateFrom, $strDateThru, $strIDEmployee = "")
{
  $arrResult = [];
  $strKriteria = ($strIDEmployee != "") ? "AND id_employee = $strIDEmployee " : "";
  $strSQL = "SELECT t1.*, t2.\"shift_off\", t2.id as shift_id FROM \"hrd_shift_schedule_employee\" AS t1 ";
  $strSQL .= "LEFT JOIN \"hrd_shift_type\" AS t2 ON t1.\"shift_code\" = t2.code ";
  $strSQL .= "WHERE (t1.shift_date BETWEEN '$strDateFrom' AND '$strDateThru') $strKriteria";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['shift_date']][$rowDb['id_employee']] = $rowDb;
    $arrResult[$rowDb['shift_date']][$rowDb['id_employee']]['is_night'] = ($rowDb['start_time'] > $rowDb['finish_time']);
  }
  return $arrResult;
}

function getWorkSchedule($db, $strDate = "", $strIDEmployee = "", $strCriteria = "")
{
  include_once("../global/employee_function.php");
  $arrResult = [];
  global $ARRAY_SCHEDULE_TABLENAME;
  $strScheduleLevel = join(", t0.", $ARRAY_SCHEDULE_TABLENAME);
  //ambil daftar karyawan dengan keterangan organisasinya
  if ($strIDEmployee != "") {
    $tempEmployee = getEmployeeInfoByID($db, $strIDEmployee, "t0.id, " . $strScheduleLevel);
    $arrEmployee[$strIDEmployee] = $tempEmployee;
  } else {
    $arrEmployee = getEmployeeInfoByID($db, "", "t0.id, " . $strScheduleLevel);
  }
  $intDay = -1;
  //ambil workschedule berdasarkan jadwal per susunan organisasi
  $strSQL = "SELECT * FROM hrd_work_schedule WHERE 1=1 $strCriteria ";
  if ($strIDEmployee != "") {
    $strSQL .= "AND table_name = 'Employee' and link_code = '" . $arrEmployee[$strIDEmployee]['employee_id'] . "' ";
  }
  if ($strDate != "") {
    $intDay = getWDay($strDate);
    $strSQL .= "AND (workday = $intDay OR workday = -1)";
  } else {
    $strSQL .= "AND workday = -1";
  }
  $resDb = $db->execute($strSQL);
  $arrSchedule = [];
  while ($rowDb = $db->fetchrow($resDb)) {
    $rowDb['start_time'] = substr($rowDb['start_time'], 0, 5);
    $rowDb['finish_time'] = substr($rowDb['finish_time'], 0, 5);
    $arrSchedule[$rowDb['table_name']][$rowDb['link_code']][$rowDb['workday']] = $rowDb;
  }
  // pasangkan data karyawan dengan data jadwal organisasi yang paling dekat/sesuai
  // prioritas : subsection, section, department, division
  $i = 0;
  $strStartTime = getSetting("start_time");
  if (strlen($strStartTime) > 5) {
    $strStartTime = substr($strStartTime, 0, 5);
  }
  $strFinishTime = getSetting("finish_time");
  if (strlen($strFinishTime) > 5) {
    $strFinishTime = substr($strFinishTime, 0, 5);
  }
  //jumat
  $isDayOff = 'f';
  if ($intDay == 5) {
    $strFridayFinishTime = getSetting("friday_finish_time");
    if (strlen($strFridayFinishTime) >= 5) {
      $strFinishTime = substr($strFridayFinishTime, 0, 5);
    }
  } //sabtu
  else if ($intDay == 6) {
    if (getSetting("saturday") != 'f') {
      $strStartTime = "";
      $strFinishTime = "";
      $isDayOff = 't';
    }
  } //minggu
  else if ($intDay == 0) {
    $isDayOff = 't';
  }
  foreach ($arrEmployee as $strIDEmployee => $arrData) {
    $i++;
    $arrResult[$strIDEmployee] = getScheduleDetail($db, $arrData, $arrSchedule);
    //jika tidak ada working schedule, maka baca dari general setting
    if ($arrResult[$strIDEmployee] == false || count($arrResult[$strIDEmployee]) == 0) {
      $arrResult[$strIDEmployee] = [
          "start_time"  => $strStartTime,
          "finish_time" => $strFinishTime,
          "day_off"     => $isDayOff
      ];
    }
  }
  //print_r($arrResult);
  return $arrResult;
}

function getScheduleDetail($db, $arrData, $arrSchedule, $intLevel = 0)
{
  global $ARRAY_SCHEDULE_LEVEL;
  global $ARRAY_SCHEDULE_TABLENAME;
  if ($intLevel >= count($ARRAY_SCHEDULE_LEVEL) || count($arrSchedule) == 0) {
    return false;
  }
  if (isset($arrData[$ARRAY_SCHEDULE_TABLENAME[$ARRAY_SCHEDULE_LEVEL[$intLevel]]]) && isset($arrSchedule[$ARRAY_SCHEDULE_LEVEL[$intLevel]][$arrData[$ARRAY_SCHEDULE_TABLENAME[$ARRAY_SCHEDULE_LEVEL[$intLevel]]]])) {
    $arrTemp = $arrSchedule[$ARRAY_SCHEDULE_LEVEL[$intLevel]][$arrData[$ARRAY_SCHEDULE_TABLENAME[$ARRAY_SCHEDULE_LEVEL[$intLevel]]]];
    foreach ($arrTemp as $intWorkday => $arrDetail) {
      if ($intWorkday != -1 || !isset($arrResult)) {
        $arrResult = $arrDetail;
      }
    }
    return $arrResult;
  } else {
    $intLevel++;
    return getScheduleDetail($db, $arrData, $arrSchedule, $intLevel);
  }
}

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
  $strSQL = "SELECT code FROM hrd_shift_type ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrShiftType[] = $rowDb['code'];
  }
  // hitung total dulu semua
  $strSQL = "SELECT COUNT(t1.id) AS total, t1.id_employee, ";
  $strSQL .= "SUM(CASE WHEN (holiday = 1) THEN 1 ELSE 0 END) AS total_holiday, "; // total pulang cepat, dalam menit
  $strSQL .= "SUM(CASE WHEN (late_duration > " . LATE_TOLERANCE . " AND not_late = 'f' AND holiday = 0) THEN 1 ELSE 0 END) AS \"late\", "; // cari total keterlambatan, dalam hari
  $strSQL .= "SUM(CASE WHEN (early_duration > " . LATE_TOLERANCE . " AND holiday = 0) THEN 1 ELSE 0 END) AS early, "; // cari total keterlambatan, dalam hari
  $strSQL .= "SUM(CASE WHEN (not_late = 't' AND holiday = 1) THEN 0 ELSE t1.late_duration END) AS total_late, "; // total telat, dalam menit
  $strSQL .= "SUM(CASE WHEN (holiday = 1) THEN 0 ELSE t1.early_duration END) AS total_early, "; // total pulang cepat, dalam menit
  if (isset($arrShiftType)) {
    foreach ($arrShiftType as $strCode) {
      $strSQL .= "SUM(CASE WHEN (code_shift_type = '$strCode') THEN 1 ELSE 0 END) AS \"shift_" . $strCode . "\", "; // shift
    }
  }
  $strSQL .= "SUM(CASE WHEN (holiday = 1) THEN 0 ELSE t1.early_duration END) AS total_early "; // total pulang cepat, dalam menit
  // cari data shift siang dan malam
  //$strSQL .= "SUM(CASE WHEN (shift_type = 1) THEN 1 ELSE 0 END) AS day_shift, ";
  //$strSQL .= "SUM(CASE WHEN (shift_type = 2) THEN 1 ELSE 0 END) AS night_shift ";
  //$strSQL .= "SUM(t1.transport) AS total_transport, SUM(t1.monthly) AS total_monthly ";
  $strSQL .= "FROM hrd_attendance AS t1 ";
  $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
  $strSQL .= "WHERE t1.attendance_date BETWEEN '$strFrom' AND '$strThru' $strHoliday ";
  // cek apakah attendanceStart+finish tidak kosong
  $strSQL .= "AND (t1.attendance_start is not null OR t1.attendance_finish is not null) ";
  // $strSQL .= "AND (t1.attendance_start <> t1.attendance_finish) ";
  // cek yang isAbsence-nya false
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "GROUP BY t1.id_employee ";
  // echo $strSQL;
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['id_employee']]['total'] = $rowDb['total']; // total kehadiran
    $arrResult[$rowDb['id_employee']]['totalHoliday'] = $rowDb['total_holiday']; // total kehadiran di hari libur
    $arrResult[$rowDb['id_employee']]['totalLate'] = $rowDb['total_late'];
    $arrResult[$rowDb['id_employee']]['late'] = $rowDb['late'];
    $arrResult[$rowDb['id_employee']]['totalEarly'] = $rowDb['total_early'];
    $arrResult[$rowDb['id_employee']]['early'] = $rowDb['early'];
    foreach ($arrShiftType as $strCode) {
      $arrResult[$rowDb['id_employee']][$strCode] = $rowDb['shift_' . $strCode];
    }
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
function getEmployeeAbsence2($db, $strFrom, $strThru, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  // langsung dipisah sesuai jenis absennya
  $strSQL = "SELECT t1.* FROM hrd_absence AS t1, hrd_employee AS t2 ";
  $strSQL .= "WHERE t1.id_employee = t2.id ";
  $strSQL .= "AND (status >= '" . REQUEST_STATUS_APPROVED . "' OR status = '" . REQUEST_STATUS_DENIED . "') ";
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
// fungsi untuuk mengambil informasi ketidakhadiran
// aoutput berupa array
function getEmployeeAbsence($db, $strFrom, $strThru, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  // langsung dipisah sesuai jenis absennya
  $strSQL = "SELECT code FROM hrd_absence_type";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrAbsType[] = $rowDb['code'];
  }
  $strSQL = "SELECT t1.id_employee, SUM( CASE WHEN is_leave = FALSE THEN 1 ELSE 0 END) AS total, ";
  foreach ($arrAbsType AS $strCode) {
    $strSQL .= "SUM (CASE WHEN absence_type = '$strCode' THEN 1 ELSE 0 END) AS \"abs_$strCode\", ";
  }
  $strSQL .= "SUM (CASE WHEN is_leave          = TRUE AND is_leave = FALSE THEN 1 ELSE 0 END) AS leave ";
  //$strSQL .= "SUM (CASE WHEN deduct_meal       = TRUE AND is_leave = FALSE THEN 1 ELSE 0 END) AS deduct_meal, ";
  //$strSQL .= "SUM (CASE WHEN deduct_transport  = TRUE AND is_leave = FALSE THEN 1 ELSE 0 END) AS deduct_transport, ";
  //$strSQL .= "SUM (CASE WHEN deduct_shift      = TRUE AND is_leave = FALSE THEN 1 ELSE 0 END) AS deduct_shift, ";
  //$strSQL .= "SUM (CASE WHEN deduct_attendance = TRUE AND is_leave = FALSE THEN 1 ELSE 0 END) AS deduct_attendance, ";
  //$strSQL .= "SUM (CASE WHEN deduct_salary     = TRUE AND is_leave = FALSE THEN 1 ELSE 0 END) AS deduct_salary, ";
  //$strSQL .= "SUM (CASE WHEN deduct_salary     = FALSE AND is_leave = FALSE THEN 1 ELSE 0 END) AS undeduct_salary ";
  $strSQL .= "FROM hrd_absence_detail AS t1 ";
  $strSQL .= "INNER JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
  $strSQL .= "INNER JOIN hrd_absence_type  AS t3 ON t1.absence_type = t3.code ";
  $strSQL .= "INNER JOIN hrd_absence  AS t4 ON t1.id_absence = t4.id ";
  $strSQL .= "WHERE 1=1 AND t4.status >= '" . REQUEST_STATUS_APPROVED . "' ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "AND (absence_date BETWEEN '$strFrom' AND '$strThru') ";
  $strSQL .= "GROUP BY t1.id_employee";
  // echo $strSQL;
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $strDateStart = $strFrom;
    $strDateFinish = $strThru;
    foreach ($arrAbsType AS $strCode) {
      $arrResult[$rowDb['id_employee']][$strCode] = $rowDb['abs_' . $strCode];
    }
    $arrResult[$rowDb['id_employee']]['leave'] = $rowDb['leave'];
    //$arrResult[$rowDb['id_employee']]['deduct_meal']       = $rowDb['deduct_meal'];
    //$arrResult[$rowDb['id_employee']]['deduct_transport']  = $rowDb['deduct_transport'];
    //$arrResult[$rowDb['id_employee']]['deduct_shift']      = $rowDb['deduct_shift'];
    //$arrResult[$rowDb['id_employee']]['deduct_attendance'] = $rowDb['deduct_attendance'];
    //$arrResult[$rowDb['id_employee']]['undeduct_salary']   = $rowDb['undeduct_salary'];
    //$arrResult[$rowDb['id_employee']]['deduct_salary']     = $rowDb['deduct_salary'];
    $arrResult[$rowDb['id_employee']]['total'] = $rowDb['total'];
  }
  return $arrResult;
} // getEmployeeAbsence
// fungsi untuuk mengambil informasi cuti KESELURUHAN
// output berupa array
function getEmployeeLeave($db, $strFrom, $strThru, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  // langsung dipisah sesuai jenis absennya
  $strSQL = "SELECT t1.* FROM hrd_absence_detail AS t1, hrd_employee AS t2 ";
  $strSQL .= "WHERE t1.id_employee = t2.id  ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' AND is_leave = 't' ";
  }
  $strSQL .= "AND (absence_date BETWEEN '$strFrom' and '$strThru') ";
  $strSQL .= "    OR absence_date = DATE '$strFrom' ";
  $strSQL .= "    OR absence_Date = DATE '$strThru' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if (!isset($arrResult[$rowDb['id_employee']][$rowDb['absence_type']])) {
      $arrResult[$rowDb['id_employee']][$rowDb['absence_type']] = 0;
    }
    if (!isset($arrResult[$rowDb['id_employee']]['total'])) {
      $arrResult[$rowDb['id_employee']]['total'] = 0;
    }
    $arrResult[$rowDb['id_employee']][$rowDb['absence_type']] += 1;
    $arrResult[$rowDb['id_employee']]['total'] += 1;
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
  $strSQL .= "AND leave_type_code = 2 AND status >= '" . REQUEST_STATUS_APPROVED . "' ";
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
  $strSQL .= "AND leave_type_code = 1 AND status >= '" . REQUEST_STATUS_APPROVED . "' ";
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
  $strSQL .= "WHERE t1.id_employee = t2.id AND t1.leave_type_code = 0 AND status >= '" . REQUEST_STATUS_APPROVED . "' ";
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
function getEmployeeOvertimeApplication(
    $db,
    $strFrom,
    $strThru,
    $intTipe = 0,
    $strOutdated = "FALSE",
    $strIDEmployee = "",
    $strKriteria = ""
) {
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
  // cari data lembur per menit
  $strSQL .= "SUM(t1.l1) AS total_l1, ";
  $strSQL .= "SUM(t1.l2) AS total_l2, ";
  $strSQL .= "SUM(t1.l3) AS total_l3, ";
  $strSQL .= "SUM(t1.l4) AS total_l4, ";
  $strSQL .= "SUM(CASE WHEN t1.holiday_ot = FALSE THEN t1.l1 ELSE 0 END) AS total_l1_normal, ";
  $strSQL .= "SUM(CASE WHEN t1.holiday_ot = FALSE THEN t1.l2 ELSE 0 END) AS total_l2_normal, ";
  $strSQL .= "SUM(CASE WHEN t1.holiday_ot = FALSE THEN t1.l3 ELSE 0 END) AS total_l3_normal, ";
  $strSQL .= "SUM(CASE WHEN t1.holiday_ot = FALSE THEN t1.l4 ELSE 0 END) AS total_l4_normal, ";
  $strSQL .= "SUM(CASE WHEN t1.holiday_ot = FALSE THEN t1.l1 ELSE 0 END) AS total_l1_holiday, ";
  $strSQL .= "SUM(CASE WHEN t1.holiday_ot = TRUE THEN t1.l2 ELSE 0 END) AS total_l2_holiday, ";
  $strSQL .= "SUM(CASE WHEN t1.holiday_ot = TRUE THEN t1.l3 ELSE 0 END) AS total_l3_holiday, ";
  $strSQL .= "SUM(CASE WHEN t1.holiday_ot = TRUE THEN t1.l4 ELSE 0 END) AS total_l4_holiday ";
  $strSQL .= "FROM hrd_overtime_application_employee AS t1 ";
  $strSQL .= "LEFT JOIN hrd_overtime_application AS t2 ON t2.id = t1.id_application ";
  //$strSQL .= "LEFT JOIN hrd_employee AS t4 ON t1.id_employee = t4.id ";
  if ($strFrom != $strThru) {
    $strSQL .= "WHERE (t1.overtime_date BETWEEN '$strFrom' AND '$strThru') $strHoliday $strKriteria ";
  } else {
    $strSQL .= "WHERE t1.overtime_date ='$strFrom' $strHoliday $strKriteria ";
  }
  if (strtoupper($strOutdated) == "TRUE") {
    $strSQL .= "AND t2.is_outdated = $strOutdated ";
  }
  if ($strIDEmployee != "") {
    $strSQL .= "AND t1.id_employee = '$strIDEmployee' ";
  }
  $strSQL .= "GROUP BY t1.id_employee ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['id_employee']]['total'] = $rowDb['total']; // total kehadiran
    $arrResult[$rowDb['id_employee']]['total1'] = $rowDb['total_l1'];
    $arrResult[$rowDb['id_employee']]['total2'] = $rowDb['total_l2'];
    $arrResult[$rowDb['id_employee']]['total3'] = $rowDb['total_l3'];
    $arrResult[$rowDb['id_employee']]['total4'] = $rowDb['total_l4'];
    $arrResult[$rowDb['id_employee']]['totalL1Normal'] = $rowDb['total_l1_normal'];
    $arrResult[$rowDb['id_employee']]['totalL2Normal'] = $rowDb['total_l2_normal'];
    $arrResult[$rowDb['id_employee']]['totalL3Normal'] = $rowDb['total_l3_normal'];
    $arrResult[$rowDb['id_employee']]['totalL4Normal'] = $rowDb['total_l4_normal'];
    $arrResult[$rowDb['id_employee']]['totalL1Holiday'] = $rowDb['total_l1_holiday'];
    $arrResult[$rowDb['id_employee']]['totalL2Holiday'] = $rowDb['total_l2_holiday'];
    $arrResult[$rowDb['id_employee']]['totalL3Holiday'] = $rowDb['total_l3_holiday'];
    $arrResult[$rowDb['id_employee']]['totalL4Holiday'] = $rowDb['total_l4_holiday'];
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
  $strSQL .= "WHERE t1.status >= '" . REQUEST_STATUS_APPROVED . "' ";
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
// fungsi untuk ambil data overtime, dari tabel overtime (bukan dari overtime application)
// fungsi untuk ambil data overtime dari tabel overtime application)
function getEmployeeOvertimeApplicationDetail($db, $strFrom, $strThru, $strIDEmployee = "")
{
  $arrResult = [];
  // hitung total dulu semua
  $strSQL = "SELECT  t1.* ";
  $strSQL .= "FROM hrd_overtime_application_employee AS t1 LEFT JOIN hrd_overtime_application AS t2 ";
  $strSQL .= "ON t1.id_application = t2.id WHERE ";
  $strSQL .= "(t1.overtime_date BETWEEN '$strFrom' AND '$strThru' OR t1.overtime_date = '$strFrom') ";
  if ($strIDEmployee != "") {
    $strSQL .= "AND t1.id_employee = '$strIDEmployee' ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['overtime_date']][$rowDb['id_employee']] = $rowDb; // total ot
  }
  return $arrResult;
} // getEmployeeOvertimeApplication
// fungsi untuk ambil data overtime, dari tabel attendance
function getEmployeeOvertimeFromAttendance($db, $strFrom, $strThru, $strIDEmployee = "", $strKriteria = "")
{
  $arrResult = [];
  // hitung total dulu semua
  $strSQL = "SELECT  t0.id_employee, ";
  $strSQL .= "SUM(CASE WHEN (is_overtime = 't') THEN overtime ELSE 0 END) AS total_ot, ";
  $strSQL .= "SUM(CASE WHEN (is_overtime = 't') THEN overtime_calculated ELSE 0 END) AS total_otx, ";
  $strSQL .= "SUM(CASE WHEN (is_overtime = 't') THEN l1 ELSE 0 END) AS total1, ";
  $strSQL .= "SUM(CASE WHEN (is_overtime = 't') THEN l2 ELSE 0 END) AS total2, ";
  $strSQL .= "SUM(CASE WHEN (is_overtime = 't') THEN l3 ELSE 0 END) AS total3, ";
  $strSQL .= "SUM(CASE WHEN (is_overtime = 't') THEN l4 ELSE 0 END) AS total4 ";
  $strSQL .= "FROM hrd_attendance AS t0 ";
  $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
  $strSQL .= "WHERE attendance_date BETWEEN '$strFrom' AND '$strThru' $strKriteria ";
  if ($strIDEmployee != "") {
    $strSQL .= "AND id_employee = '$strIDEmployee' ";
  }
  $strSQL .= "GROUP BY t0.id_employee";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['id_employee']] = $rowDb; // total ot
  }
  return $arrResult;
} // getEmployeeOvertime
// fungsi untuk ambil data overtime, dari tabel overtime (bukan dari overtime application)
function getAutoShiftOT($db, $strFrom, $strThru, $strSection = "", $strEmployee = "")
{
  $arrResult = [];
  // hitung total dulu semua
  $strSQL = "SELECT  t1.id_employee, ";
  $strSQL .= "SUM(t1.l1) AS total1, SUM(t1.l2) AS total2, ";
  $strSQL .= "SUM(t1.l3) AS total3, SUM(t1.l4) AS total4 ";
  $strSQL .= "FROM hrd_attendance AS t1 ";
  $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
  $strSQL .= "LEFT JOIN hrd_overtime AS t3 ON t1.id_employee = t3.id_employee AND t1.attendance_date = t1.overtime_date";
  $strSQL .= "WHERE t3.status >= '" . REQUEST_STATUS_APPROVED . "' ";
  $strSQL .= "AND t1.is_overtime = 't' ";
  $strSQL .= "AND t1.attendance_date BETWEEN '$strFrom' AND '$strThru' ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "AND t1.flag = 0 GROUP BY t1.id_employee ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['id_employee']]['total1'] = $rowDb['total1']; // total ot
    $arrResult[$rowDb['id_employee']]['total2'] = $rowDb['total2']; // total ot
    $arrResult[$rowDb['id_employee']]['total3'] = $rowDb['total3']; // total ot
    $arrResult[$rowDb['id_employee']]['total4'] = $rowDb['total4']; // total ot
  }
  return $arrResult;
} // getEmployeeOvertime
function getEmployeeAutoOvertime($db, $strDateFrom, $strDateThru, $strKriteria = "")
{
  $arrOTMax = [1 => 1, 2 => 7, 3 => 1, 4 => 15];
  $arrOTFactor = getOvertimeTypeValue($db);
  //ambil karyawan shift (lihat dari jadwal shiftnya)
  $strSQL = "SELECT id_employee, count(id_employee) AS total FROM hrd_attendance AS t1 ";
  $strSQL .= "LEFT JOIN  hrd_shift_type AS t2 ON t1.code_shift_type = t2.code ";
  $strSQL .= "WHERE attendance_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
  $strSQL .= "AND is_shift = 't' $strKriteria";
  $strSQL .= "GROUP BY id_employee";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['id_employee']]['total'] = $rowDb['total'];
  }
  //dari jumlah ketidak hadiran, assign auto OT yang berhak diperoleh
  foreach ($arrResult as $strIDEmployee => $arrDetail) {
    $intTotal = $arrDetail['total'];
    if ($intTotal == 0) {
      $intAutoOT = 0;
    } else {
      $intAutoOT = floor(($intTotal - 1) / 6) + 1;
    }
    $arrResult[$strIDEmployee]['auto_ot'] = $intAutoOT;
    $intCalculatedAutoOT = 0;
    // assign auto OT ke L1, L2, L3 dan L4
    foreach ($arrOTMax AS $index => $intMax) {
      if ($intAutoOT > $intMax) {
        $arrResult[$strIDEmployee]['total' . $index] = $intMax;
        $intCalculatedAutoOT += ($intMax * $arrOTFactor[$index]);
      } else {
        $arrResult[$strIDEmployee]['total' . $index] = ($intAutoOT > 0) ? $intAutoOT : 0;
        $intCalculatedAutoOT += ($intAutoOT * $arrOTFactor[$index]);
      }
      $intAutoOT -= $arrResult[$strIDEmployee]['total' . $index];
    }
    $arrResult[$strIDEmployee]['calculated_auto_ot'] = $intCalculatedAutoOT;
  }
  return $arrResult;
}

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
  } else {
    // cari cuti
    $strSQL = "SELECT absence_type_code FROM hrd_absence ";
    $strSQL .= "WHERE id_employee = '$strID' AND status >= '" . REQUEST_STATUS_APPROVED . "' ";
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
  $strSQL .= "WHERE t1.id_employee = t2.id AND status >= '" . REQUEST_STATUS_APPROVED . "' ";
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
  $strSQL .= "WHERE t3.id_employee = t2.id AND t1.status >= '" . REQUEST_STATUS_APPROVED . "' ";
  $strSQL .= "AND t1.id = t3.id_request ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection' OR t2.sub_section_code = '$strSection') ";
  }
  if ($strEmployee != "") {
    $strSQL .= "AND t2.employee_id = '$strEmployee' ";
  }
  $strSQL .= "AND ((t1.training_date, t1.training_date) ";
  $strSQL .= "    OVERLAPS (DATE '$strFrom', DATE '$strThru') ";
  $strSQL .= "    OR (t1.training_date = DATE '$strFrom') ";
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
// fungsi untuk syncronisasi data shift dan data attendance (khususnya normal time nya),
function syncShiftAttendance($db, $strDateFrom, $strDateThru, $strKriteria)
{
  $strDefaultStart = substr(getSetting("start_time"), 0, 5);
  $strDefaultFinish = substr(getSetting("finish_time"), 0, 5);
  $strFridayFinish = substr(getSetting("friday_finish_time"), 0, 5);
  // cari info Shift
  $arrOvertime = [];
  $strSQL = "SELECT id_employee, shift_date, t0.start_time, t0.finish_time, shift_code, shift_off ";
  $strSQL .= "FROM hrd_shift_schedule_employee AS t0 ";
  $strSQL .= "LEFT JOIN hrd_shift_type AS t2 ON t0.shift_code = t2.code ";
  $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
  $strSQL .= "WHERE shift_date BETWEEN '$strDateFrom' AND '$strDateThru'  $strKriteria";
  $resS = $db->execute($strSQL);
  while ($rowS = $db->fetchrow($resS)) {
    $arrShift[$rowS['shift_date']][$rowS['id_employee']] = $rowS;
  }
  // cari info Attendance
  $arrAttendance = [];
  $strSQL = "SELECT id_employee, attendance_date, attendance_finish, attendance_start ";
  $strSQL .= "FROM hrd_attendance AS t0 ";
  $strSQL .= "LEFT JOIN hrd_employee AS t1 ON t0.id_employee = t1.id ";
  $strSQL .= "WHERE attendance_date BETWEEN '$strDateFrom' AND '$strDateThru' $strKriteria";
  $resS = $db->execute($strSQL);
  while ($rowS = $db->fetchrow($resS)) {
    $arrAttendance[$rowS['attendance_date']][$rowS['id_employee']] = $rowS;
  }
  //print_r($arrOvertime);
  //print_r($arrAttendance);
  $strDate = $strDateFrom;
  $strSQL = "";
  if (count($arrAttendance) > 0) {
    foreach ($arrAttendance as $strDate => $arrAttDate) {
      $arrSchedule = getWorkSchedule($db, $strDate);
      $bolFriday = (getWDay($strDate) == 5);
      $bolHoliday = isHoliday($strDate);
      $arrNormal = [];
      foreach ($arrAttDate as $strID => $arrAttRow) {
        $arrAttRow = $arrAttendance[$strDate][$strID];
        $strAttendanceStart = substr($arrAttRow['attendance_start'], 0, 5);
        $strAttendanceFinish = substr($arrAttRow['attendance_finish'], 0, 5);
        if (isset($arrShift[$strDate][$strID])) {
          $strShiftCode = $arrShift[$strDate][$strID]['shift_code'];
          $strNormalStart = substr($arrShift[$strDate][$strID]['start_time'], 0, 5);
          $strNormalFinish = substr($arrShift[$strDate][$strID]['finish_time'], 0, 5);
          $intHoliday = ($arrShift[$strDate][$strID]['shift_off'] == "t") ? 1 : 0;
        }/*
              else if($arrSchedule != false && isset($arrSchedule[$strID]))
              {
                 $strShiftCode        = "";
                 $strNormalStart      = substr($arrSchedule[$strID]['start_time'],0,5);
                 $strNormalFinish     = substr($arrSchedule[$strID]['finish_time'],0,5);
                 $intHoliday          = ($arrSchedule[$strID]['day_off'] == "t") ? 1 : 0;
              }*/
        else {
          $strShiftCode = "";
          if (isFlexyTimeActivity($db, $strDate)) {
            $normalStartFinish = getNormalStartNormalFinishByFlexyActivity($db, $strDate, $strAttendanceStart);
            $strNormalStart = $normalStartFinish['strNormalStart'];
            $strNormalFinish = $normalStartFinish['strNormalFinish'];
          } else {
            $strNormalStart = $strDefaultStart;
            $strNormalFinish = ($bolFriday) ? $strFridayFinish : $strDefaultFinish;
          }
          $intHoliday = ($bolHoliday) ? 1 : 0;
        }
        if ($intHoliday) {
          $strNormalStart = $strAttendanceStart;
          $strNormalFinish = $strAttendanceStart;
        }
        if ($strNormalStart == "") {
          continue;
        }
        $arrOT = calculateOvertime(
            $db,
            $strDate,
            $strNormalStart,
            $strNormalFinish,
            $strAttendanceStart,
            $strAttendanceFinish
        );
        $strNotLate = ($arrOT['late'] > 0) ? "f" : "t";
        $intShiftType = (timeCompare($strNormalStart, $strNormalFinish) <= 0) ? 1 : 2;
        //Update Attendance
        if (!empty($strShiftCode)) {
          $strSQL .= "UPDATE hrd_attendance SET code_shift_type = '" . $strShiftCode . "', ";
          $strSQL .= "normal_finish = '$strNormalFinish', normal_start = '$strNormalStart', ";
          $strSQL .= "late_duration = '" . $arrOT['late'] . "', early_duration = '" . $arrOT['early'] . "', ";
          $strSQL .= "not_late = '" . $strNotLate . "', shift_type = '" . $intShiftType . "', ";
          $strSQL .= "holiday= '" . $intHoliday . "' ";
          $strSQL .= "WHERE id_employee = '$strID' AND  attendance_date = '$strDate'; ";
        }
      }
    }
  }
  str_replace("''", "null", $strSQL);
  $resExec = $db->execute($strSQL);
}//syncShiftAttendance
//Cek data kehadiran yang kurang dari 4 jam => dianggap tidak hadir
function checkInvalidAttendance($db, $strDateFrom, $strDateThru, $strKriteria = "")
{
  $strSQL = "select id_employee, attendance_date, normal_start, normal_finish, attendance_finish ";
  $strSQL .= "FROM hrd_attendance AS t1 LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
  $strSQL .= "WHERE attendance_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
  if ($strKriteria != "") {
    $strSQL .= "$strKriteria";
  }
  $resDb = $db->execute($strSQL);
  $strSQL = "";
  while ($rowDb = $db->fetchrow($resDb)) {
    $strDate = $rowDb['attendance_date'];
    $strIDEmployee = $rowDb['id_employee'];
    $strNormalStart = substr($rowDb['normal_start'], 0, 5);
    $strNormalFinish = substr($rowDb['normal_finish'], 0, 5);
    $strAttendanceFinish = substr($rowDb['attendance_finish'], 0, 5);
    if (timeCompare($strNormalStart, $rowDb['attendance_finish']) > 0) {
      $strAttendanceFinish = getNextMinute($strAttendanceFinish, 1440);
    }
    if (getIntervalHour(
            $strNormalStart,
            $strAttendanceFinish
        ) < 240 && $strNormalStart != $strAttendanceFinish && getIntervalHour($strNormalStart, $strNormalFinish) > 240
    ) {
      $strSQL .= "UPDATE hrd_attendance SET flag = 1 WHERE attendance_date = '$strDate' ";
      $strSQL .= "AND id_employee = '$strIDEmployee'; ";
      $strSQL .= "DELETE FROM hrd_absence where date_from = '$strDate' AND date_thru = '$strDate' ";
      $strSQL .= "AND id_employee = '$strIDEmployee'; ";
      $strSQL .= "DELETE FROM hrd_absence_detail WHERE absence_date = '$strDate' ";
      $strSQL .= "AND id_employee = '$strIDEmployee'; ";
      $strSQL .= "INSERT INTO hrd_absence (id, id_employee, date_from, date_thru, absence_type_code, duration) ";
      $strSQL .= "VALUES (nextval('\"hrd_absence_id_seq\"'), '$strIDEmployee', '$strDate', '$strDate', 'A', 1); ";
      $strSQL .= "INSERT INTO hrd_absence_detail (id_absence, id_employee, absence_date, absence_type) ";
      $strSQL .= "VALUES (nextval('\"hrd_absence_id_seq\"'), '$strIDEmployee', '$strDate', 'A'); ";
    } else {
      $strSQL .= "UPDATE hrd_attendance SET flag = 0 WHERE attendance_date = '$strDate' ";
      $strSQL .= "AND id_employee = '$strIDEmployee'; ";
      $strSQL .= "DELETE FROM hrd_absence where date_from = '$strDate' AND date_thru = '$strDate' ";
      $strSQL .= "AND id_employee = '$strIDEmployee'; ";
      $strSQL .= "DELETE FROM hrd_absence_detail WHERE absence_date = '$strDate' ";
      $strSQL .= "AND id_employee = '$strIDEmployee'; ";
    }
  }
  $resDb = $db->execute($strSQL);
}

//Cek data kehadiran yang kurang dari 4 jam => dianggap tidak hadir
function checkBlankAttendance($db, $strDateFrom, $strDateThru, $strKriteria = "")
{
  $strCurDate = $strDateFrom;
  include_once("../classes/hrd/hrd_absence.php");
  include_once("../classes/hrd/hrd_absence_detail.php");
  $tblAbsence = new cHrdAbsence();
  $tblAbsenceDetail = new cHrdAbsenceDetail();
  $strSQL = "SELECT id_employee, absence_date FROM hrd_absence_detail WHERE absence_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrAbsence[$rowDb['absence_date']][] = $rowDb['id_employee'];
  }
  while (dateCompare($strCurDate, $strDateThru) <= 0) {
    $strSQL = "SELECT id FROM hrd_employee WHERE active=1 AND
                    id NOT IN (SELECT id_employee FROM hrd_attendance WHERE attendance_date = '$strCurDate') $strKriteria";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      if (!isset($arrAbsence[$strCurDate][$rowDb['id']])) {
        $tblAbsence->insert(
            [
                "id_employee"       => $rowDb['id'],
                "date_from"         => $strCurDate,
                "date_thru"         => $strCurDate,
                "absence_type_code" => "Absent",
                "note"              => "generated by system",
                "status"            => REQUEST_STATUS_NEW
            ]
        );
        $tblAbsenceDetail->insert(
            [
                "id_absence"   => $tblAbsence->getLastInsertId(),
                "id_employee"  => $rowDb['id'],
                "absence_date" => $strCurDate,
                "absence_type" => "Absent"
            ]
        );
      }
    }
    $strCurDate = getNextDate($strCurDate);
  }
}

function syncLateEarly($db, $strDateFrom, $strDateThru, $strIDEmployee = "", $strKriteria = "")
{
  // cari info Attendance
  $arrAttendance = [];
  $strSQL = "SELECT t1.*, t2.shift_code ";
  $strSQL .= "FROM hrd_attendance AS t1 ";
  $strSQL .= "LEFT JOIN hrd_employee AS t3 ON t1.id_employee = t3.id ";
  $strSQL .= "LEFT JOIN hrd_shift_schedule_employee AS t2 ON t1.id_employee = t2.id_employee AND t1.attendance_date = t2.shift_date ";
  $strSQL .= "WHERE attendance_date BETWEEN '$strDateFrom' AND '$strDateThru' $strKriteria";
  $temp = explode("AND employee_id = ", $strKriteria);
  $temp = explode("'", $temp[1]);
  $ID = "";
  $ID = $temp[1];
  if ($strIDEmployee != "") {
    $strSQL .= " AND id_employee ='$strIDEmployee' ";
  }
  $resS = $db->execute($strSQL);
  while ($rowS = $db->fetchrow($resS)) {
    //if(($rowS['attendance_finish']>=$rowS['normal_finish'])||(($rowS['attendance_finish']>$rowS['normal_finish'])&&($rowS['attendance_finish']<$rowS['normal_start']))){
    //  -- CHECK EARLY --
    if ($rowS['normal_start'] > $rowS['normal_finish']) { //overnight
      if ((getMinutes($rowS['attendance_finish']) >= getMinutes($rowS['normal_finish'])) && (getMinutes(
                  $rowS['attendance_finish']
              ) < getMinutes($rowS['normal_start'])) && (getMinutes($rowS['attendance_finish']) > getMinutes(
                  $rowS['attendance_start']
              ))
      ) {
        $strSQL = "UPDATE hrd_attendance SET early_duration = 0 ";
        $strSQL .= "WHERE attendance_date = '" . $rowS['attendance_date'] . "' ";
        $strSQL .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE id='" . $rowS['id_employee'] . "') ";
        $res = $db->execute($strSQL);
      } else if (getMinutes($rowS['attendance_finish']) < getMinutes($rowS['normal_finish'])) {
        $strSQL = "UPDATE hrd_attendance SET early_duration = Extract(EPOCH FROM(normal_finish-attendance_finish)::INTERVAL)/60";
        $strSQL .= " WHERE attendance_date = '" . $rowS['attendance_date'] . "' ";
        $strSQL .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE id='" . $rowS['id_employee'] . "') ";
        $res = $db->execute($strSQL);
      } else if ((getMinutes($rowS['attendance_finish']) < getMinutes('24:00:00')) && (getMinutes(
                  $rowS['attendance_finish']
              ) >= getMinutes($rowS['attendance_start'])) && (getMinutes($rowS['attendance_finish']) != getMinutes(
                  $rowS['normal_finish']
              ))
      ) {
        if (getMinutes($rowS['attendance_finish']) < (getMinutes($rowS['normal_finish']) + 1440)) {
          $strSQL = "UPDATE hrd_attendance SET early_duration = Extract(EPOCH FROM(normal_finish-attendance_finish+'86400')::INTERVAL)/60";
          $strSQL .= " WHERE attendance_date = '" . $rowS['attendance_date'] . "' ";
          $strSQL .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE id='" . $rowS['id_employee'] . "') ";
          $res = $db->execute($strSQL);
        } else {
          $strSQL = "UPDATE hrd_attendance SET early_duration = 0";
          $strSQL .= " WHERE attendance_date = '" . $rowS['attendance_date'] . "' ";
          $strSQL .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE id='" . $rowS['id_employee'] . "') ";
          $res = $db->execute($strSQL);
        }
      } else if ((getMinutes($rowS['attendance_finish']) >= getMinutes($rowS['normal_finish'])) || (strtotime(
                  $rowS['attendance_finish']
              ) < strtotime($rowS['normal_start']))
      ) {
        $strSQL = "UPDATE hrd_attendance SET early_duration = 0 ";
        $strSQL .= "WHERE attendance_date = '" . $rowS['attendance_date'] . "' ";
        $strSQL .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE id='" . $rowS['id_employee'] . "') ";
        $res = $db->execute($strSQL);
      }
    } else {
      if ((strtotime($rowS['attendance_finish']) >= strtotime($rowS['normal_finish']))) {
        $strSQL = "UPDATE hrd_attendance SET early_duration = 0 ";
        $strSQL .= "WHERE attendance_date = '" . $rowS['attendance_date'] . "' ";
        $strSQL .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE id='" . $rowS['id_employee'] . "') ";
        $res = $db->execute($strSQL);
      } else if (strtotime($rowS['attendance_finish']) < strtotime($rowS['normal_finish']) && getMinutes(
              $rowS['attendance_finish']
          ) >= getMinutes($rowS['attendance_start'])
      ) {
        $strSQL = "UPDATE hrd_attendance SET early_duration = Extract(EPOCH FROM(normal_finish-attendance_finish)::INTERVAL)/60";
        $strSQL .= " WHERE attendance_date = '" . $rowS['attendance_date'] . "' ";
        $strSQL .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE id='" . $rowS['id_employee'] . "') ";
        $res = $db->execute($strSQL);
      }
    }
    //     --END EARLY--
    //     --CHECK LATE--
    if ($rowS['normal_start'] > $rowS['normal_finish']) { //overnight
      if (getMinutes($rowS['attendance_start']) <= getMinutes($rowS['normal_start']) && getMinutes(
              $rowS['attendance_start']
          ) > getMinutes($rowS['normal_finish'])
      ) {
        $strSQL = "UPDATE hrd_attendance SET late_duration = 0 ";
        $strSQL .= "WHERE attendance_date = '" . $rowS['attendance_date'] . "' ";
        $strSQL .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE id='" . $rowS['id_employee'] . "') ";
        $res = $db->execute($strSQL);
      } else if (getMinutes($rowS['attendance_start']) <= getMinutes($rowS['normal_finish'])) {
        if ((getMinutes($rowS['attendance_start']) + 1440) > getMinutes($rowS['normal_start'])) {
          $strSQL = "UPDATE hrd_attendance SET late_duration = Extract(EPOCH FROM('86400' - (normal_start-attendance_start))::INTERVAL)/60";
          $strSQL .= " WHERE attendance_date = '" . $rowS['attendance_date'] . "' ";
          $strSQL .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE id='" . $rowS['id_employee'] . "') ";
          $res = $db->execute($strSQL);
        } else {
          $strSQL = "UPDATE hrd_attendance SET late_duration = 0";
          $strSQL .= " WHERE attendance_date = '" . $rowS['attendance_date'] . "' ";
          $strSQL .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE id='" . $rowS['id_employee'] . "') ";
          $res = $db->execute($strSQL);
        }
      }
    } else {
      if (strtotime($rowS['attendance_start']) <= strtotime($rowS['normal_start'])) {
        $strSQL = "UPDATE hrd_attendance SET late_duration = 0 ";
        $strSQL .= "WHERE attendance_date = '" . $rowS['attendance_date'] . "' ";
        $strSQL .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE id='" . $rowS['id_employee'] . "') ";
        $res = $db->execute($strSQL);
      } else if (strtotime($rowS['attendance_start']) > strtotime($rowS['normal_start'])) {
        $strSQL = "UPDATE hrd_attendance SET late_duration = Extract(EPOCH FROM(attendance_start-normal_start)::INTERVAL)/60 ";
        $strSQL .= "WHERE attendance_date = '" . $rowS['attendance_date'] . "' ";
        $strSQL .= "AND id_employee IN (SELECT id FROM hrd_employee WHERE id='" . $rowS['id_employee'] . "') ";
        $res = $db->execute($strSQL);
      }
    }
    //     --END LATE--
  }
}//syncLateEarly
//fungsi untuk mengubah format jam ke hitungan menit
function getMinutes($hour_minutes)
{
  $hour = substr($hour_minutes, 0, 2) * 1 * 60;
  $minutes = substr($hour_minutes, 3, 2) * 1;
  return $hour + $minutes;
}

//fungsi untuk mengubah format menit ke format jam
function toHour($minutes)
{
  $hour = floor($minutes / 60);
  if (strlen($hour) == 1) {
    $hour = "0" . $hour;
  }
  $minutes = $minutes % 60;
  if (strlen($minutes) == 1) {
    $minutes = "0" . $minutes;
  }
  $hour_minutes = $hour . ":" . $minutes . ":00";
  return $hour_minutes;
}

/* Flexi Time Setting */
function isFlexyTimeActivity($db, $date)
{
  if ($db->connect()) {
    $strSQL = "SELECT break_duration,work_duration,start_date,
      end_date,start_time_1,start_time_2,finish_time_1,finish_time_2 
      FROM all_flexy_time_setting WHERE start_date <= '$date'";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      return true;
    }
  }
  return false;
}//check if flexy time
function getFlexyTimesettingByDateActivity($db, $dateCheck)
{
  $flexyTimeSetting = [];
  if ($db->connect()) {
    $strSQL = "SELECT break_duration,work_duration,start_date,
      end_date,start_time_1,start_time_2,finish_time_1,finish_time_2 
      FROM all_flexy_time_setting WHERE start_date <= '$dateCheck' 
      ORDER BY start_date DESC LIMIT 1";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $flexyTimeSetting[$dateCheck] = $rowDb;
    }
  }
  return $flexyTimeSetting;
}//getflexytimesetting by date
//get normal start normal finish
function getNormalStartNormalFinishByFlexyActivity($db, $strDataDate, $strAttendanceStart)
{
  $normalStartFinish = [];
  $flexyTimeSetting = getFlexyTimesettingByDateActivity($db, $strDataDate);
  //get flexy time start and finish
  $flexyStartTimeFrom = $flexyTimeSetting[$strDataDate]['start_time_1'];
  $flexyStartTimeTo = $flexyTimeSetting[$strDataDate]['start_time_2'];
  $flexyFinishTimeFrom = $flexyTimeSetting[$strDataDate]['finish_time_1'];
  $flexyFinishTimeTo = $flexyTimeSetting[$strDataDate]['finish_time_2'];
  $flexyWorkDuration = $flexyTimeSetting[$strDataDate]['work_duration'];
  $flexyBreakDuration = $flexyTimeSetting[$strDataDate]['break_duration'];
  //get flexy time minutes
  $flexyStartTimeFrominMinutes = getMinutes($flexyStartTimeFrom);
  $flexyStartTimeToinMinutes = getMinutes($flexyStartTimeTo);
  $flexyFinishTimeFrominMinutes = getMinutes($flexyFinishTimeFrom);
  $flexyFinishTimeToinMinutes = getMinutes($flexyFinishTimeTo);
  //get attendance info in minutes
  $strAttendanceStartinMinutes = getMinutes($strAttendanceStart);
  //1. if attendance start is in flexy time, normal start = attendance start, normal finish = attendance start + work duration
  if ($strAttendanceStartinMinutes >= $flexyStartTimeFrominMinutes && $strAttendanceStartinMinutes <= $flexyStartTimeToinMinutes) {
    $normalStartFinish['strNormalStart'] = $strAttendanceStart;
    $normalStartFinish['strNormalFinish'] = substr(
        toHour($strAttendanceStartinMinutes + $flexyWorkDuration + $flexyBreakDuration),
        0,
        5
    );
  } //2. if attendance start is before flexy time, normal start = flexy start time from, normal finish = flexy finish time from
  else if ($strAttendanceStartinMinutes < $flexyStartTimeFrominMinutes) {
    $normalStartFinish['strNormalStart'] = $flexyStartTimeFrom;
    $normalStartFinish['strNormalFinish'] = $flexyFinishTimeFrom;
  } //3. if attendance start is after flexy time, normal start = flexy start time to, normal finish = finish time to
  else if ($strAttendanceStartinMinutes > $flexyStartTimeToinMinutes) {
    $normalStartFinish['strNormalStart'] = $flexyStartTimeTo;
    $normalStartFinish['strNormalFinish'] = $flexyFinishTimeTo;
  }
  return $normalStartFinish;
}

//end get normal start normal finish
/* End Flexi Time Setting*/
?>