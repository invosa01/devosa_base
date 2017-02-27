<?php
/*
  KUMPULAN KELAS YANG TERKAIT DENGAN ABSENSI DAN KEHADIRAN
  Update : 2009-01-23 (Yudi)
*/
include_once("../global/cls_date.php");
include_once("cls_worktime.php");
include_once("cls_shift.php");
include_once('../classes/hrd/hrd_absence_partial.php');

/*
  KELAS UNTUK MENGAMBIL DATA LAPORAN ABSENSI DAN KEHADIRAN
*/

class clsAbsenceReport
{

    var $arrAbsenceType = []; // kelas database

  var $arrData = []; // tanggal awal

  var $arrShiftEmployee = []; // tanggal akhir

    var $arrShiftType = [];   // total durasi hari antara tanggal awal dan akhir, bukan hanya hari kerja

  var $data;// total durasi hari kerja antara tanggal awal dan akhir, secara standard, bukan per karyawan

  var $intTotalDay; // id dari karyawan, jika ada atau khusus untuk 1 karyawan saja

  var $intTotalWorkDay; // khusus untuk filter pengambilan data karyawan

  // format query: "AND xxxx"

var $objShift; // data disimpan dalam array saja

  // index adalah id_employee

var $objWork;

var $strEmployeeFilter;

  var $strFinishDate; // daftar karyawan yang memiliki jadwal shift

  var $strIDEmployee;   // objek untuk fasilitas perhitungan waktu kerja - cls_worktime.php

  var $strStartDate;  // objek untuk ambil info terkait shift - cls_shift.php

  // konstruktor

  function clsAbsenceReport($db, $strStartDate, $strFinishDate, $strIDEmployee = "", $strEmployeeFilter = "")
  {
    $this->data = $db;
    $this->objWork = new clsWorkTime($db);
    $this->objShift = new clsCommonShift($db);
    $this->strStartDate = $strStartDate;
    $this->strFinishDate = $strFinishDate;
    $this->strIDEmployee = $strIDEmployee;
    $this->strEmployeeFilter = $strEmployeeFilter;
    if ($this->strIDEmployee != "") {
      $this->strEmployeeFilter .= "AND id = '" . $this->strIDEmployee . "' ";
    }
    $this->arrData = [];
    $objDt = new clsCommonDate();
    $this->getAbsenceType();
    $this->arrShiftType = $this->objShift->getShiftType(); // abaikan shift Off
    $this->arrShiftEmployee = $this->objShift->getShiftEmployees($strStartDate, $strFinishDate);
    $this->intTotalDay = $objDt->getIntervalDate($this->strStartDate, $this->strFinishDate) + 1;
    $this->intTotalWorkDay = $this->objWork->getTotalWorkDay($strStartDate, $strFinishDate);
    $this->arrTotalWorkDayShift = $this->objShift->getTotalWorkingShift($this->strStartDate, $this->strFinishDate);
    unset($objDt);
    //echo $this->strStartDate;
  }

  /* getAbsenceType : fungsi untuk mengambil daftar jenis cuti (private)
  */

  function generateAbsenceReport()
  {
    // diambil dari absence detail
    $strSQL = "
        SELECT t1.id_employee, t1.absence_type, COUNT(t1.id) AS total
        FROM (
          SELECT * FROM hrd_absence_detail
          WHERE absence_date BETWEEN '" . $this->strStartDate . "' AND '" . $this->strFinishDate . "'
        ) AS t1
        INNER JOIN (
          SELECT * FROM hrd_absence
          WHERE status >= '" . REQUEST_STATUS_APPROVED . "'
        ) AS t2 ON t1.id_absence = t2.id
        INNER JOIN (
          SELECT id, employee_id FROM hrd_employee
          WHERE 1 = 1 " . $this->strEmployeeFilter . "
        ) AS t3 ON t1.id_employee = t3.id
        GROUP BY t1.id_employee, t1.absence_type
      ";
    $res = $this->data->execute($strSQL);
    while ($row = $this->data->fetchrow($res)) {
      $strID = $row['id_employee'];
      $this->arrData[$strID]['absence_detail'][$row['absence_type']] = $row['total'];
      $this->arrData[$strID]['total_absence'] = array_sum($this->arrData[$strID]['absence_detail']);
      /*if ($row['absence_type'] == 'DL' || $row['absence_type'] == 'TL') {
        $this->arrData[$strID]['total_absence_dl'] += $this->arrData[$strID]['absence_detail']['DL'] + $this->arrData[$strID]['absence_detail']['TL'];
      }*/
    }
    // ambil data khusus untuk cuti tahunan
    // diambil dari absence detail
    $strSQL = "
        SELECT t2.id_employee, SUM(t2.leave_duration) AS total
        FROM (
          SELECT * FROM hrd_absence
          WHERE status >= '" . REQUEST_STATUS_APPROVED . "'
        ) AS t2
        INNER JOIN (
          SELECT id, employee_id FROM hrd_employee
          WHERE 1 = 1 " . $this->strEmployeeFilter . "
        ) AS t3 ON t2.id_employee = t3.id
        GROUP BY t2.id_employee
      ";
    $res = $this->data->execute($strSQL);
    while ($row = $this->data->fetchrow($res)) {
      $strID = $row['id_employee'];
      $this->arrData[$strID]['total_leave'] = $row['total'];
    }
  }

  /* initData : fungsi untuk melakukan inisialisasi data per karyawan, data diisi dengan default 0
  */

  function generateAttendanceReport()
  {
    // hitung total dulu semua
    $strSQL = "SELECT COUNT(t1.id) AS total, t1.id_employee, ";
    $strSQL .= "SUM(CASE WHEN (holiday = 1) THEN 1 ELSE 0 END) AS total_holiday, "; // total masuk di hari libur
    $strSQL .= "SUM(CASE WHEN (late_duration > " . LATE_TOLERANCE . " AND not_late = 'f' AND holiday = 0) THEN 1 ELSE 0 END) AS total_late, "; // cari total keterlambatan, dalam hari
    $strSQL .= "SUM(CASE WHEN (early_duration > " . LATE_TOLERANCE . " AND holiday = 0) THEN 1 ELSE 0 END) AS total_early, "; // cari total pulang cepat, dalam hari
    $strSQL .= "SUM(CASE WHEN (early_duration > " . LATE_TOLERANCE . " AND holiday = 0) OR (late_duration > " . LATE_TOLERANCE . " AND holiday = 0) THEN 1 ELSE 0 END) AS total_late_early, "; // cari total pulang cepat, dalam hari
    $strSQL .= "SUM(CASE WHEN (not_late = 't' AND holiday = 1) THEN 0 ELSE t1.late_duration END) AS total_late_min, "; // total telat, dalam menit
    foreach ($this->arrShiftType as $strCode => $arrTmp) {
      $strSQL .= "SUM(CASE WHEN (code_shift_type = '$strCode') THEN 1 ELSE 0 END) AS \"shift_" . $strCode . "\", "; // shift
      //$strSQL .= "SUM(CASE WHEN (code_shift_type2 = '$strCode') THEN 1 ELSE 0 END) AS \"shift2_". $strCode ."\", "; // shift
    }
    $strSQL .= "SUM(CASE WHEN (holiday = 1) THEN 0 ELSE t1.early_duration END) AS total_early_min "; // total pulang cepat, dalam menit
    // cari data shift siang dan malam
    $strSQL .= "FROM (
          SELECT * FROM hrd_attendance
          WHERE attendance_date BETWEEN '" . $this->strStartDate . "'
              AND '" . $this->strFinishDate . "'
            AND (attendance_start is not null OR attendance_finish is not null)
            AND (is_absence != 't')
        ) AS t1 ";
    $strSQL .= "LEFT JOIN (
          SELECT id, employee_id, employee_name
          FROM hrd_employee WHERE 1 = 1 " . $this->strEmployeeFilter . "
        ) AS t2 ON t1.id_employee = t2.id
      ";
    $strSQL .= "GROUP BY t1.id_employee ";
    $resDb = $this->data->execute($strSQL);
    while ($rowDb = $this->data->fetchrow($resDb)) {
      $strID = $rowDb['id_employee'];
      if (!isset($this->arrData[$strID])) {
        $this->initData($strID);
      }
      $this->arrData[$strID]['total_attendance'] = $rowDb['total']; // total kehadiran
      $this->arrData[$strID]['total_holiday'] = $rowDb['total_holiday']; // total kehadiran di hari libur
      $this->arrData[$strID]['total_late'] = 0;
      $this->arrData[$strID]['total_late_min'] = $rowDb['total_late_min'];
      $this->arrData[$strID]['total_early'] = 0;
      $this->arrData[$strID]['total_early_min'] = $rowDb['total_early_min'];
      $this->arrData[$strID]['total_late_early'] = 0;
      foreach ($this->arrShiftType as $strCode => $arrTmp) {
        $this->arrData[$strID]['shift_detail'][$strCode] = $rowDb['shift_' . $strCode] /*+ $rowDb['shift2_'.$strCode]*/
        ;
      }
      if (isset($this->arrData[$strID]['shift_detail'])) {
        $this->arrData[$strID]['total_shift'] = array_sum($this->arrData[$strID]['shift_detail']);
      }
    }
  }

  /* generateAttendanceReport : fungsi untuk mengambil informasi/laporan terkait dengan kehadiran karyawan
      ambil data total kehadiran, keterlambatan, pulang awal, dan shift
  */

  function generatePartialAbsenceReport()
  {
    $tblAbsencePartial = new cHrdAbsencePartial();
    $strCriteria = "partial_absence_date BETWEEN '" . $this->strStartDate . "' AND '" . $this->strFinishDate . "' AND status = " . REQUEST_STATUS_APPROVED . " ";
    //if ($arrData['dataEmployee'] != "") $strCriteria .= "AND id_employee = '".getIDEmployee($db, $arrData['dataEmployee'])."' ";
    $dataAbsencePartial = $tblAbsencePartial->findAll($strCriteria, "", "", null, 1, "id");
    foreach ($dataAbsencePartial as $strID => $detailAbsencePartial) {
      $arrAbsencePartial[$detailAbsencePartial['partial_absence_date']][$detailAbsencePartial['id_employee']][$detailAbsencePartial['partial_absence_type']] = $detailAbsencePartial;
    }
    // hitung total dulu semua
    $strSQL = "SELECT t1.late_duration AS late, t1.early_duration AS early, t1.id_employee, attendance_date ";
    $strSQL .= "FROM (
          SELECT * FROM hrd_attendance
          WHERE attendance_date BETWEEN '" . $this->strStartDate . "'
              AND '" . $this->strFinishDate . "'
            AND (attendance_start is not null OR attendance_finish is not null)
        ) AS t1 ";
    $strSQL .= "LEFT JOIN (
          SELECT id, employee_id, employee_name
          FROM hrd_employee WHERE active = 1 " . $this->strEmployeeFilter . "
        ) AS t2 ON t1.id_employee = t2.id
      ";
    //$strSQL .= "GROUP BY t1.id_employee ";
    $resDb = $this->data->execute($strSQL);
    //echo $strSQL;
    while ($rowDb = $this->data->fetchrow($resDb)) {
      $strID = $rowDb['id_employee'];
      if (!isset($this->arrData[$strID])) {
        $this->initData($strID);
      }
      //pembulatan untuk datang terlambat
      if ($rowDb['late'] > 0) {
        $intLate[$strID] = ($rowDb['late'] < LATE_TOLERANCE) ? 0 : round(
                ceil($rowDb['late'] / 30) * 30
            ) / 60;//$rowDb['late'] / 60;
        if (isset($arrAbsencePartial[$rowDb['attendance_date']][$strID][PARTIAL_ABSENCE_LATE]) && is_numeric(
                $arrAbsencePartial[$rowDb['attendance_date']][$strID][PARTIAL_ABSENCE_LATE]['approved_duration']
            )
        ) {
          $intLate[$strID] -= ($rowDb['late'] > $arrAbsencePartial[$rowDb['attendance_date']][$strID][PARTIAL_ABSENCE_LATE]['approved_duration']) ? $arrAbsencePartial[$rowDb['attendance_date']][$strID][PARTIAL_ABSENCE_LATE]['approved_duration'] / 60 : $intLate[$strID];
        }
        if ($intLate > 0) {
          $this->arrData[$strID]['total_late']++;
          $this->arrData[$strID]['total_late_early']++;
        }
      } else {
        $intLate[$strID] = 0;
      }
      $this->arrData[$strID]['total_late_round'] += $intLate[$strID];
      if ($rowDb['early'] > 0) {
        $intEarly[$strID] = ($rowDb['early'] < LATE_TOLERANCE) ? 0 : round(
                ceil($rowDb['early'] / 30) * 30
            ) / 60;//$rowDb['early'] / 60 ;
        if (isset($arrAbsencePartial[$rowDb['attendance_date']][$strID][PARTIAL_ABSENCE_EARLY]) && is_numeric(
                $arrAbsencePartial[$rowDb['attendance_date']][$strID][PARTIAL_ABSENCE_EARLY]['approved_duration']
            )
        ) {
          $intEarly[$strID] -= ($rowDb['early'] > $arrAbsencePartial[$rowDb['attendance_date']][$strID][PARTIAL_ABSENCE_EARLY]['approved_duration']) ? $arrAbsencePartial[$rowDb['attendance_date']][$strID][PARTIAL_ABSENCE_EARLY]['approved_duration'] / 60 : $intEarly[$strID];
        }
        if ($intEarly > 0) {
          $this->arrData[$strID]['total_early']++;
          if ($intLate == 0) {
            $this->arrData[$strID]['total_late_early']++;
          }
        }
      } else {
        $intEarly[$strID] = 0;
      }
      //total pembulatan pulang cepat
      $this->arrData[$strID]['total_early_round'] += $intEarly[$strID];
    }
    foreach ($this->arrData as $strID => $arrValue) {
      $this->arrData[$strID]['absence_detail'][SPECIAL_ABSENCE] = $arrValue['total_late_early'];
    }
  }

  /* generateAbsenceReport : fungsi untuk mengambil informasi terkait jumlah absen karyawan
      ambil data total absen per kode absen
  */

  function getAbsenceType()
  {
    $strSQL = "
        SELECT code, is_leave, note
        FROM hrd_absence_type
      ";
    $res = $this->data->execute($strSQL);
    while ($row = $this->data->fetchrow($res)) {
      $this->arrAbsenceType[$row['code']] = $row;
    }
  }

  /* generateAttendanceReport : fungsi untuk mengambil informasi/laporan terkait dengan kehadiran karyawan khusus keterlambatan
     pembulatan Total keterlambatan dihitung perhari
  */

  function getData($strIDEmployee, $strField)
  {
    $strResult = 0;
    if (isset($this->arrData[$strIDEmployee][$strField])) {
      $strResult = $this->arrData[$strIDEmployee][$strField];
    }
    return $strResult;
  }

  /*
    getData : fungsi untuk mengambil data dari karyawan tertentu, berdasar data yang ada di arrData
    input : id karyawan, field (kode index). untuk absen atau shift dengan kode tertentu, gunakan fungsi lain
    output: nilai yang ada dalam arrData sesuai id dan field
  */

  function getDataAbsence($strIDEmployee, $strCode)
  {
    $strResult = 0;
    //print_r($this->arrData);
    if (isset($this->arrData[$strIDEmployee]['absence_detail'][$strCode])) {
      $strResult = $this->arrData[$strIDEmployee]['absence_detail'][$strCode];
    }
    return $strResult;
  }

  /* isEmployeeExist : memeriksa akah ID employee tertentu ada dalam data karyawan di laporan yang dibuat
      input   : id karyawan
      output  : true jika id karyawan ada di arrData
  */

  function getDataShift($strIDEmployee, $strCode)
  {
    $strResult = 0;
    if (isset($this->arrData[$strIDEmployee]['shift_detail'][$strCode])) {
      $strResult = $this->arrData[$strIDEmployee]['shift_detail'][$strCode];
    }
    return $strResult;
  }

  /*
    getDataAbsence : fungsi untuk mengambil data total absen dari karyawan tertentu, untuk jenis absen tertentu
    input : id karyawan, kode absensi
    output: nilai yang ada dalam arrData sesuai id dan kode absen
  */

  function getDataShiftAllowance($strIDEmployee)
  {
    $fltResult = 0;
    if (isset($this->arrData[$strIDEmployee]['shift_detail'])) {
      foreach ($this->arrData[$strIDEmployee]['shift_detail'] AS $strCode => $intTotal) {
        $fltAllowance = (isset($this->arrShiftType[$strCode]['shift_allowance'])) ? $this->arrShiftType[$strCode]['shift_allowance'] : 0;
        $fltResult += $intTotal * $fltAllowance;
      }
    }
    return $fltResult;
  }

  /*
    getDataShift : fungsi untuk mengambil data total shift yang dijalani oleh karyawan tertentu, untuk jenis absen tertentu
    input : id karyawan, kode shift
    output: nilai yang ada dalam arrData sesuai id dan kode shift
  */

  function getDataShiftHour($strIDEmployee)
  {
    $fltResult = 0;
    if (isset($this->arrData[$strIDEmployee]['shift_detail'])) {
      foreach ($this->arrData[$strIDEmployee]['shift_detail'] AS $strCode => $intTotal) {
        $fltHour = getIntervalHour(
            $this->arrShiftType[$strCode]['start_time'],
            $this->arrShiftType[$strCode]['finish_time']
        );
        $fltResult += ($intTotal * $fltHour);
      }
    }
    return $fltResult;
  }

  /*
    getDataShiftAllowance : fungsi untuk mengambil data total tunjangan shift yang dijalani oleh karyawan tertentu
    input : id karyawan
    output: nilai tunjangan shift karyawan tertentu
  */

  function initData($strIDEmployee)
  {
    if (isset($this->arrData[$strIDEmployee])) {
      unset($this->arrData[$strIDEmployee]);
    }
    $this->arrData[$strIDEmployee] = [
        "total_day"         => $this->intTotalDay, // total interval hari, bukan hanya hari kerja
        "total_workday"     => 0, // total interval hari kerja
        "total_attendance"  => 0, // total kehadiran (termasuk di hari libur)
        "total_late"        => 0, // total hari keterlambatan
        "total_late_min"    => 0, // total jam keterlambatan
        "total_late_round"  => 0, // total jam keterlambatan
        "total_early"       => 0, // total hari pulang cepat
        "total_early_min"   => 0, // total jam pulang cepat
        "total_early_round" => 0, // total jam pulang cepat
        "total_holiday"     => 0, // total kehadiran di hari libur
        "total_absence"     => 0, // total absensi, tidak dikelompokkan dalam jenis absen
        "total_leave"       => 0, // total pengambilan cuti tahunan, termasuk absen yang potong cuti
        "total_trip"        => 0, // total perjalanan dinas
        "total_shift"       => 0, // total kehadiran shift
        "absence_detail"    => [], // total absence dikelompokkan berdasar jenis absensi
        "shift_detail"      => [], // total shift dikelompokkan berdasar jenis absensi
    ];
    if (isset($this->arrShiftEmployee[$strIDEmployee])) {
      $this->arrData[$strIDEmployee]['total_workday'] = (isset($this->arrTotalWorkDayShift[$strIDEmployee])) ? $this->arrTotalWorkDayShift[$strIDEmployee] : 0;
      //$this->arrData[$strIDEmployee]['total_workday'] = $this->intTotalWorkDay;
    } else {
      $this->arrData[$strIDEmployee]['total_workday'] = $this->intTotalWorkDay;
    }
    foreach ($this->arrAbsenceType AS $strKode => $arrAbs) {
      $this->arrData[$strIDEmployee]['absence_detail'][$strKode] = 0;
    }
    foreach ($this->arrShiftType AS $strKode => $arrShit) {
      $this->arrData[$strIDEmployee]['shift_detail'][$strKode] = 0;
    }
    //print_r($this->arrData[14174]['absence_detail']);
  }

  function isEmployeeExist($strIDEmployee)
  {
    return (isset($this->arrData[$strIDEmployee]));
  }
}

?>