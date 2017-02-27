<?php
/*
  KELAS-KELAS YANG TERKAIT DENGAN SHIFT
  BISA BERISI FUNGSI-FUNGSI STANDARD TERKAIT DENGAN SHIFT
  UPDATE 2009-01-24 By, Yudi K.
*/
include_once("../global/cls_date.php");

/*
  clsCommonShift()
  Kelas yang berisi fungsi-fungsi umum yang terkait dengan data shift karyawan
*/

class clsCommonShift
{

  var $data; // objek database

  var $objDate; // objek tanggal

  // konstruktor
  function clsCommonShift($db)
  {
    $this->data = $db;
    $this->objDate = new clsCommonDate();
  }

  /*  getOffShiftType : fungsi untuk mengambil kode shift yang termasuk OFF
      output: kode shift yang dianggap sebagai hari libur kerja (OFF)
  */

  function checkEmployeeShiftOff($strIDEmployee, $strDateFrom, $strDateTo)
  {
    $bolResult = false;
    $i = 0;
    $intSelisih = $this->objDate->getIntervalDate($strDateFrom, $strDateTo) + 1;
    $strSQL = "
        SELECT t1.id_employee, t1.id, t1.shift_code, t2.shift_off
        FROM hrd_shift_schedule_employee AS t1
        LEFT JOIN hrd_shift_type AS t2 ON t1.shift_code = t2.code
        WHERE t1.id_employee = '$strIDEmployee'
          AND t1.shift_date BETWEEN '$strDateFrom' AND '$strDateTo'
      ";
    $resDb = $this->data->execute($strSQL);
    while ($rowDb = $this->data->fetchrow($resDb)) {
      if ($rowDb['shift_off'] == 't') {
        $i++;
      }
    }
    $bolResult = ($i == $intSelisih);
    return $bolResult;
  }

  /*  getShiftType : fungsi untuk mengambil seluruh daftar jenis shift
      input : bolIncludeOff (optional), jika true, maka jenis shift yang OFF juga ikut diambil
      output: array, daftar jenis shift dengan index kode shift
  */

  function getOffShiftType()
  {
    $strResult = "";
    $strSQL = "SELECT * FROM hrd_shift_type WHERE shift_off = 't' LIMIT 1 ";
    $resDb = $this->data->execute($strSQL);
    if ($rowDb = $this->data->fetchrow($resDb)) {
      $strResult = $rowDb['code'];
    }
    return $strResult;
  }

  /*  getShiftScheduleByDate : fungsi untuk mengambil daftar jadwal shift di tanggal tertentu
      input: db, tanggal (YYYY-MM-DD), section (optional), subsection (optional), idEmployee (optional)
      output: array, index:idEmployee
  */

  function getPrevSchedule($strIDEmployee, $strDateFrom, $strDateTo)
  {
    if ($strDateFrom <= $strDateTo) {
      return false;
    }
    $arrTemp = $this->getShiftScheduleByDate($strDateFrom, $strIDEmployee);
    if (isset($arrTemp[$strIDEmployee])) {
      if ($arrTemp[$strIDEmployee]['shift_off'] == 't') {
        $arrTemp = $this->getPrevSchedule($strIDEmployee, $this->objDate->getNextDate($strDateFrom, -1), $strDateTo);
      } else {
        return $arrTemp;
      }
    } else {
      $arrTemp = $this->getPrevSchedule(
          $this->data,
          $strIDEmployee,
          $this->objDate->getNextDate($strDateFrom, -1),
          $strDateTo
      );
    }
  }

  /* getShiftEmployees : fungsi untuk mengambil informasi karyawan mana saja yang memiliki jadwal shift
      pada periode waktu tertentu
      input: tanggal awal dan tanggal akhir (format SQL - YYYY-MM-DD)
      output : array, berisi id karyawan yang dalam periode waktu tertentu memiliki jadwal shift meski cuma 1
  */

  function getShiftEmployees($strDateFrom, $strDateTo)
  {
    $arrResult = [];
    if ($strDateFrom != "" && $strDateTo != "") {
      $strSQL = "
          SELECT DISTINCT id_employee 
          FROM hrd_shift_schedule_employee
          WHERE shift_date BETWEEN '$strDateFrom' AND '$strDateTo'
        ";
      $res = $this->data->execute($strSQL);
      while ($row = $this->data->fetchrow($res)) {
        $arrResult[$row['id_employee']] = true;
      }
    }
    return $arrResult;
  }

  /* checkEmployeeShiftOff : fungsi untuk memeriksa apakah seorang karyawan terdaftar cuti dan sedang OFF pada hari tertentu
      input: db, idEmployee, tanggal awal, tanggal akhir (format YYYY-MM-DD)
  */

  function getShiftScheduleByDate($strDate, $strSection = "", $strSubSection = "", $strEmployee = "")
  {
    $arrResult = [];
    $strKriteria = ""; // kriteria tambahan
    if ($strEmployee != "") {
      $strKriteria .= "AND \"id_employee\" = '$strEmployee' ";
    }
    if ($strSection != "" || $strSubSection != "") {
      $strKriteria .= "
          AND \"id_employee\" IN (
            SELECT id FROM hrd_employee
            WHERE flag = 0
        ";
      if ($strSection != "") {
        $strKriteria .= " AND \"section_code\" = '$strSection' ";
      }
      if ($strSubSection != "") {
        $strKriteria .= " AND \"sub_section_code\" = '$strSubSection' ";
      }
      $strKriteria .= ") ";
    }
    $strSQL = "
        SELECT t1.*, t2.shift_off, t2.id as shift_id
        FROM hrd_shift_schedule_employee AS t1
        LEFT JOIN hrd_shift_type AS t2 ON t1.shift_code = t2.code
        WHERE t1.shift_date = '$strDate' $strKriteria
      ";
    $resDb = $this->data->execute($strSQL);
    while ($rowDb = $this->data->fetchrow($resDb)) {
      $arrResult[$rowDb['id_employee']] = $rowDb;
    }
    return $arrResult;
  }

  /*  getPrevSchedule : fungsi untuk mengambil jadwal sebelumnya yg tidak off
      input: db, idEmployee, tanggal awal, tanggal akhir (format YYYY-MM-DD)
  */

  function getShiftType($bolIncludeOff = true)
  {
    $arrResult = [];
    $strKriteria = ($bolIncludeOff) ? "" : "AND shift_off <> 't' ";
    $strSQL = "SELECT * FROM hrd_shift_type WHERE 1=1 $strKriteria ";
    $resDb = $this->data->execute($strSQL);
    while ($rowDb = $this->data->fetchrow($resDb)) {
      $arrResult[$rowDb['code']] = $rowDb;
    }
    return $arrResult;
  }

  /*  isEmployeeShift : fungsi untuk mengetahui apakah karyawan memiliki jadwal shift dalam periode tanggal tertentu
      input: db, idEmployee, tanggal awal, tanggal akhir (format YYYY-MM-DD)
  */

  function getTotalWorkingShift($strDateFrom, $strDateTo, $strIDEmployee = "")
  {
    $arrResult = [];
    $intInterval = $this->objDate->getIntervalDate($strDateFrom, $strDateTo) + 1;
    if ($strIDEmployee != "") {
      $arrResult[$strIDEmployee] = $intInterval;
    }
    $strKriteria = ($strIDEmployee == "") ? "" : " AND id_employee = '$strIDEmployee' ";
    // cari total hari yang jadwal shiftnya OFF
    $strSQL = "
          SELECT t1.id_employee, COUNT(t1.*) AS total
          FROM hrd_shift_schedule_employee AS t1
          INNER JOIN hrd_shift_type AS t2 ON t1.shift_code = t2.code
          WHERE t2.shift_off = 't' $strKriteria
            AND t1.shift_date BETWEEN '$strDateFrom' AND '$strDateTo'
          GROUP BY t1.id_employee
      ";
    $resDb = $this->data->execute($strSQL);
    while ($rowDb = $this->data->fetchrow($resDb)) {
      if ($rowDb['total'] != "") {
        $arrResult[$rowDb['id_employee']] = $intInterval - $rowDb['total'];
      } else {
        $arrResult[$rowDb['id_employee']] = $intInterval;
      }
    }
    return $arrResult;
  }

  /*  getTotalWorkingShift : fungsi untuk menghitung total durasi hari kerja yang semestinya, disesuaikan dengan jadwal shift
        mencari selisih hari, kemudian dikurangi dengan jadwal OFF
      input: db, tanggal awal, tanggal akhir, idEmployee (optional)
      ouput: array : idEmployee -> durasi
  */

  function isEmployeeShift($strIDEmployee, $strDateFrom, $strDateTo)
  {
    $bolResult = false;
    $strSQL = "
        SELECT * FROM hrd_shift_schedule_employee
        WHERE id_employee = '$strIDEmployee'
          AND shift_date BETWEEN '$strDateFrom' AND '$strDateTo'
      ";
    $resDb = $this->data->execute($strSQL);
    if ($rowDb = $this->data->fetchrow($resDb)) {
      $bolResult = true;
    }
    return $bolResult;
  }
}

?>