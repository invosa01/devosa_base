<?php
/* Fungsi-fungsi khusus untuk masalah kehadiran
  By, Yudi K.
  2008-07-18
*/
include_once("cls_shift.php");

/* clsAttendanceClass : kelas untuk mengelola data kehadiran karyawan, per hari tertentu
*/

class clsAttendanceClass
{

  var $arrAttendance; // tanggal kehadiran, format YYYY-MM-DD

  var $arrBarcode; // kelas database, sudah terkoneksi

  // atribut sebagai filter, jika ada

var $arrBreakTime; // nik, sebagai filter

var $arrCompany;

var $arrEmployee;

var $arrGroup;

var $arrOT;

var $arrOutOffice;

var $arrSchedule;

  // atribut sebagai data pendukung, dalam array

var $arrSection;       // daftar jadwal shift

  var $arrShift;    // daftar jadwal shift

  var $arrSubSection;  // daftar kehadiran

  var $bolPublicHoliday;    // daftar info karyawan

  var $bolSaturday;     // daftar kode barcode karyawan, untuk nyari ID karyawan

  var $db;     // daftar branch, terkait dengan data kehadiran (berupa text, bisa lebih dari satu)

  var $intWeekDay;     // daftar section, terkait dengan data kehadiran

  var $objAttendance;  // daftar subsection, terkait dengan data kehadiran

  var $strAttendanceDate;       // daftar grup, terkait dengan data kehadiran

    var $strCompany;   // daftar jam istirahat

    var $strDepartmentCode;   // daftar karyawan yang terjadwal tidak hadir (absen, cuti, trip, dsb)

    var $strDivisionCode;          // daftar SPL, jadwal OT

  // atribut pendukung proses

var $strEmployeeID;// apakah public holiday atau tidak (karena minggu belum tentu libur)

    var $strGroup;     // apakah tanggal itu, sabtu dianggap libur atau tidak -- general setting

  var $strNormalFinish;      // kode tanggal 0-6

  var $strNormalStart;  // jam normal masuk,  standard perusahaan : hh:mm:ss

    var $strSectionCode; // jam normal pulang, standard perusahaan : hh:mm:ss

    var $strSubSectionCode;   // untuk menyimpan data kehadiran karyawan tertentu di tanggal tersebut

  // konstuktor

  function clsAttendanceClass($db)
  {
    $this->db = $db;
    $this->resetAttendance();
  }

  /* resetAttendance : reset data kehadiran dan data-data lain, untuk inisialisasi
  */

  function getAttendanceData()
  {
    $strSQL = "
        SELECT * FROM hrd_attendance WHERE attendance_date = '" . $this->strAttendanceDate . "'
      ";
    $resDb = $this->db->execute($strSQL);
    while ($rowDb = $this->db->fetchrow($resDb)) {
      $this->arrAttendance[$rowDb['id_employee']] = $rowDb;
    }
  }

  /* setAttendanceDate : mengisi atribut data tanggal kehadiran
      bolGetData: perintah untuk mengambil informasi penting di tanggal tersebut, untuk disimpan di array dulu
  */

  function getBreakTime()
  {
    $strSQL = "
        SELECT bt.*
        FROM hrd_break_time AS bt
      ";
    $resDb = $this->db->execute($strSQL);
    while ($rowDb = $this->db->fetchrow($resDb)) {
      if ($rowDb['start_time'] != "" && $rowDb['duration'] > 0) {
        $tmp = substr($rowDb['start_time'], 0, 5);
        $this->arrBreakTime[$rowDb['type']] = getNextMinute($rowDb['start_time'], $rowDb['duration']);
      }
    }
  }

  /* getShiftSchedule : fungsi untuk mengambil data jadwal shift di
      default sesuai tanggal yang ada, simpan dalam array
  */

  function getDefaultNormalTime()
  {
    // ambil data dari general setting
    if (($this->strNormalStart = substr(getSetting("start_time"), 0, 5)) == "") {
      $this->strNormalStart = "08:00";
    }
    if (($this->strNormalFinish = substr(getSetting("finish_time"), 0, 5)) == "") {
      $this->strNormalFinish = "17:00";
    }
  }

  /* getOutOfOffice : fungsi untuk mengambil info data ketidak hadiran
  */

  function getEmployeeAttendance($strID)
  {
    return ((isset($this->arrAttendance[$strID])) ? $this->arrAttendance[$strID] : []);
  }

  /* getDefaultNormalTime : fungsi untuk mengambil info data jam masuk keluar standard
      mengambil data dari general setting
      mengambil data dari cabang, simpan di arrBranch
      mengambil data dari section dan subsection
  */

  function getEmployeeInfo()
  {
    $strSQL = "
        SELECT e.*, po.get_overtime
        FROM hrd_employee AS e
        LEFT JOIN hrd_position AS po ON e.position_code = po.position_code
        WHERE e.flag=0
      ";
    $resDb = $this->db->execute($strSQL);
    while ($rowDb = $this->db->fetchrow($resDb)) {
      $this->arrEmployee[$rowDb['id']]['employee_id'] = $rowDb['employee_id'];
      $this->arrEmployee[$rowDb['id']]['is_overtime'] = ($rowDb['get_overtime'] == 0) ? "f" : "t";
      $this->arrEmployee[$rowDb['id']]['division_code'] = $rowDb['division_code'];
      $this->arrEmployee[$rowDb['id']]['department_code'] = $rowDb['department_code'];
      $this->arrEmployee[$rowDb['id']]['section_code'] = $rowDb['section_code'];
      $this->arrEmployee[$rowDb['id']]['sub_section_code'] = $rowDb['sub_section_code'];
      $this->arrEmployee[$rowDb['id']]['group_code'] = $rowDb['group_code'];
      $this->arrEmployee[$rowDb['id']]['id_company'] = $rowDb['id_company'];
      $this->arrEmployee[$rowDb['id']]['gender'] = $rowDb['gender'];
      $this->arrEmployee[$rowDb['id']]['barcode'] = $rowDb['barcode'];
      $this->arrBarcode[$rowDb['barcode']] = $rowDb['id'];
    }
  }

  /* isPublicHoliday : fungsi untuk memeriksa apakah hari ini public holiday atau tidak
      data disimpan di atribut (private)
  */

  function getOutOfOffice()
  {
    $this->arrOutOffice = getOutOfficeInfo(
        $this->db,
        $this->strAttendanceDate,
        $this->strAttendanceDate
    ); // activity.php
  }

  /* getEmployeeInfo : fungsi untuk mengambil informasi karyawan, simpan ke arrEmployee
  */

  function getOvertimeData()
  {
    $strSQL = "
        SELECT t2.*, t1.overtime_date FROM hrd_overtime_application AS t1
        INNER JOIN hrd_overtime_application_employee AS t2 ON t1.id = t2.id_application
        WHERE t1.overtime_date = '" . $this->strAttendanceDate . "'
          AND t1.status >= '" . REQUEST_STATUS_APPROVED . "'
      ";
    $resDb = $this->db->execute($strSQL);
    while ($rowDb = $this->db->fetchrow($resDb)) {
      $this->arrOT[$rowDb['id_employee']] = $rowDb;
    }
  }

  /* getBreakTime : fungsi untuk mengambil daftar jam istirahat (private)
      dikelompokkan per cabang (termasuk default), per jenis hari
  */

  function getShiftSchedule()
  {
    $objS = new clsCommonShift($this->db);
    $this->arrShift = $objS->getShiftScheduleByDate($this->strAttendanceDate);
    unset($objS);
    // activity.php
  }

  /* getAttendanceData : fungsi untuk mengambil data kehadiran seluruh karyawan yang pernah disimpan, di tanggal tersebut
      disimpan di array, index adalah idEmployee
  */

  function initAttendanceInfo()
  {
    if ((isset($this->arrEmployee[$this->objAttendance->strIDEmployee]))) {
      $strTmpSubSection = $this->arrEmployee[$this->objAttendance->strIDEmployee]['sub_section_code'];
      $strTmpSection = $this->arrEmployee[$this->objAttendance->strIDEmployee]['section_code'];
      if ($this->arrEmployee[$this->objAttendance->strIDEmployee]['is_overtime'] == 't') {
        $this->objAttendance->bolGetOT = true;
      } // dapat OT
    } else {
      $strTmpSection = "";
      $strTmpSubSection = "";
    }
    // cek absen atau tidak
    if (isset($this->arrOutOffice[$this->objAttendance->strIDEmployee])) {
      $this->objAttendance->bolAbsence = true;
      $this->objAttendance->intAbsenceType = $this->arrOutOffice[$this->objAttendance->strIDEmployee]['type'];
      $this->objAttendance->strAbsenceCode = $this->arrOutOffice[$this->objAttendance->strIDEmployee]['code'];
    }
    // cek apakah pernah ada data kehadiran, jika ada, ambil dari data tersebut
    $arrTmp = $this->getEmployeeAttendance($this->objAttendance->strIDEmployee);
    if (count($arrTmp) > 0) {
      $this->objAttendance->strAttendanceID = $arrTmp['id'];
      $this->objAttendance->strNormalStart = substr($arrTmp['normal_start'], 0, 5);
      $this->objAttendance->strNormalFinish = substr($arrTmp['normal_finish'], 0, 5);
      $this->objAttendance->strAttendanceStart = substr($arrTmp['attendance_start'], 0, 5);
      $this->objAttendance->strAttendanceFinish = substr($arrTmp['attendance_finish'], 0, 5);
      $this->objAttendance->bolLate = ($arrTmp['not_late'] != 't');
      $this->objAttendance->strNote = $arrTmp['note'];
      $this->objAttendance->fltTotalOT = $arrTmp['overtime'];
      $this->objAttendance->fltOT1 = $arrTmp['l1'];
      $this->objAttendance->fltOT2 = $arrTmp['l2'];
      $this->objAttendance->fltOT3 = $arrTmp['l3'];
      $this->objAttendance->fltOT4 = $arrTmp['l4'];
      $this->objAttendance->strShiftCode = $arrTmp['code_shift_type'];
      $this->objAttendance->strShiftCode2 = $arrTmp['code_shift_type2'];
      $this->objAttendance->bolAbsence = ($arrTmp['is_absence'] == 't');
      $this->objAttendance->bolHoliday = ($arrTmp['holiday'] == 't');
      $this->objAttendance->intLate = $arrTmp['late_duration'];
      if (isset($arrTmp['late_deduction'])) {
        $this->objAttendance->intLateDeduction = $arrTmp['late_deduction'];
      }
      $this->objAttendance->intEarly = $arrTmp['early_duration'];
      /*
      if (isset($arrTmp['break_late_deduction']))
         $this->objAttendance->intBreakLate = $arrTmp['break_late_duration'];
*/
      // jika ga dapat OT, kosongkan
      if (!$this->objAttendance->bolGetOT) {
        $this->objAttendance->fltOT1 = 0;
        $this->objAttendance->fltOT2 = 0;
        $this->objAttendance->fltOT3 = 0;
        $this->objAttendance->fltOT4 = 0;
        $this->objAttendance->fltTotalOT = 0;
      }
    } else {
      $this->objAttendance->strNormalBreak = '12:00';
      // jika masih kosong juga, gunakan dari general setting
      //ambil data overtime
      if (isset($this->arrOT[$this->objAttendance->strIDEmployee])) {
        $arrTemp = $this->arrOT[$this->objAttendance->strIDEmployee];
        $this->objAttendance->fltOT1 = $arrTemp['l1'];
        $this->objAttendance->fltOT2 = $arrTemp['l2'];
        $this->objAttendance->fltOT3 = $arrTemp['l3'];
        $this->objAttendance->fltOT4 = $arrTemp['l4'];
        $this->objAttendance->strShiftCode = $arrTemp['shift_code'];
        $this->objAttendance->strShiftCode2 = $arrTemp['shift_code2'];
      }
      // 1. cek dari shift
      if (isset($this->arrShift[$this->objAttendance->strIDEmployee])) {
        $arrTemp = $this->arrShift[$this->objAttendance->strIDEmployee];
        $this->objAttendance->strShiftCode = $arrTemp['shift_code'];
        if ($this->isShiftOFF($this->objAttendance->strIDEmployee)) {
          $this->objAttendance->strNormalStart = "";
          $this->objAttendance->strNormalFinish = "";
          $this->objAttendance->bolHoliday = true;
          $this->objAttendance->bolShiftOff = true;
        } else {
          $this->objAttendance->strNormalStart = substr($arrTemp['start_time'], 0, 5);
          $this->objAttendance->strNormalFinish = substr($arrTemp['finish_time'], 0, 5);
          $this->objAttendance->bolHoliday = false;
          $this->objAttendance->bolShiftOff = false;
        }
      }
      // 2. cek dari work schedule
      /*
      else if (isset($this->arrSchedule[$this->objAttendance->strIDEmployee]))
      {
         $arrTemp = $this->arrSchedule[$this->objAttendance->strIDEmployee];
         if ($arrTemp['day_off'] != "t")
         {
            $this->objAttendance->strNormalStart  = "";
            $this->objAttendance->strNormalFinish = "";
            $this->objAttendance->bolHoliday = true;
         }
         else
         {
            $this->objAttendance->strNormalStart  = $arrTemp['start_time'];
            $this->objAttendance->strNormalFinish = $arrTemp['finish_time'];
            $this->objAttendance->bolHoliday = false;
         }
      }
      */
      else {
        // cek hari libur
        $this->objAttendance->bolHoliday = $this->bolPublicHoliday;
        if (!$this->bolPublicHoliday) {
          if ($this->intWeekDay == 0) // minggu, anggap karyawan non shift libur
          {
            $this->objAttendance->bolHoliday = true;
          } else if ($this->intWeekDay == 6) // sabtu
          {
            $this->objAttendance->bolHoliday = $this->bolSaturday;
          }
        }
        if (!$this->objAttendance->bolHoliday) {
          // 3. baru ambil default
          if ($this->objAttendance->strNormalStart == "") {
            $this->objAttendance->strNormalStart = $this->strNormalStart;
          }
          if ($this->objAttendance->strNormalFinish == "") {
            $this->objAttendance->strNormalFinish = $this->strNormalFinish;
          }
        }
      }
    }
  }

  /* getOvertimeData : fungsi untuk mengambil data SPL seluruh karyawan yang pernah disimpan, di tanggal tersebut
      disimpan di array, index adalah idEmployee
  */

  function isPublicHoliday()
  {
    $this->bolPublicHoliday = false;
    $strSQL = "
        SELECT * FROM hrd_calendar WHERE holiday = '" . $this->strAttendanceDate . "' AND status ='t'
      ";
    $resDb = $this->db->execute($strSQL);
    if ($rowDb = $this->db->fetchrow($resDb)) {
      if ($rowDb['holiday'] != "") {
        $this->bolPublicHoliday = true;
      }
    }
  }

  /* getEmployeeAttendance : fungsi mengambil data karyawan dengan idEmployee, jika ada
      disimpan dalam array, jika tidak ada, dikirim array kosong
  */

  function isShift($strID)
  {
    return (isset($this->arrShift[$strID]));
  }

  /* isShiftOFF : fungsi mengambil info apakah jadwal cuti sedang OFF, untuk karyawan tertentu - idEmployee (private)
  */

  function isShiftOFF($strID)
  {
    $bolResult = false;
    if (isset($this->arrShift[$strID])) {
      $bolResult = ($this->arrShift[$strID]['shift_off'] == 't');
    }
    return $bolResult;
  }

  /* isShift : fungsi untuk mengambil apakah ada jadwal shift untuk karyawan tersebut (private)
  */

  function resetAttendance()
  {
    // inisialisasi
    $this->strAttendanceDate = "";
    $this->strEmployeeID = "";
    $this->strDivisionCode = "";
    $this->strDepartmentCode = "";
    $this->strSectionCode = "";
    $this->strSubSectionCode = "";
    $this->strGroup = "";
    $this->strCompany = "";
    $this->arrShift = [];
    $this->arrAttendance = [];
    $this->arrEmployee = [];
    $this->arrBarcode = [];
    $this->arrCompany = [];
    $this->arrSection = [];
    $this->arrSubSection = [];
    $this->arrBreakTime = [];
    $this->arrOutOffice = [];
    $this->arrOT = [];
    $this->objAttendance = new clsAttendanceInfo(); // objek untuk pengolahan data kehadiran karyawan tertentu
  }

  /* initAttendanceInfo : melakukan inisialisasi data objAttendance, yang merupakan data awal untuk karyawan dengan idEmployee tertentu
      mengisi informasi di dalam objek tersebut dengan nilai default, khususnya jika belum ada data di database
      untuk karyawan dan tanggal tertentu
  */

  function saveCurrentAttendance()
  {
    $objAtt = &$this->objAttendance; // biar gak kepanjangan -- pointer
    // verifikasi data dulu
    $strNormalStart = ($objAtt->strNormalStart == "") ? "NULL" : "'" . $objAtt->strNormalStart . "'";
    $strNormalFinish = ($objAtt->strNormalFinish == "") ? "NULL" : "'" . $objAtt->strNormalFinish . "'";
    $strAttendanceStart = ($objAtt->strAttendanceStart == "") ? "NULL" : "'" . $objAtt->strAttendanceStart . "'";
    $strAttendanceFinish = ($objAtt->strAttendanceFinish == "") ? "NULL" : "'" . $objAtt->strAttendanceFinish . "'";
    $strNormalBreak = ($objAtt->strNormalBreak == "") ? "NULL" : "'" . $objAtt->strNormalBreak . "'";
    $strActualBreak = ($objAtt->strActualBreak == "") ? "NULL" : "'" . $objAtt->strActualBreak . "'";
    $strNotLate = ($objAtt->bolLate) ? "'f'" : "'t'";
    $strHoliday = ($objAtt->bolHoliday) ? "'1'" : "'0'";
    $strShiftType = ($objAtt->bolShiftNight) ? 1 : 0;
    $strShiftCode = $objAtt->strShiftCode;
    $strShiftCode2 = $objAtt->strShiftCode2;
    $strIsAbsence = ($objAtt->bolAbsence) ? "'t'" : "'f'";
    $strStatus = 0; // sementara langsung OK
    if ($objAtt->strAttendanceStart == "" && $objAtt->strAttendanceFinish == "") // anggap data gak ada, hapus
    {
      $strSQL = "
          DELETE FROM hrd_attendance WHERE id_employee = '" . $objAtt->strIDEmployee . "'
            AND attendance_date = '" . $this->strAttendanceDate . "';
          UPDATE hrd_overtime_application_employee SET start_actual = null, finish_actual = null, l1 = 0, l2 = 0, l3 = 0, l4 = 0, total_time = 0 WHERE id_employee = '" . $objAtt->strIDEmployee . "'
            AND overtime_date = '" . $this->strAttendanceDate . "';
        ";
    } else if ($objAtt->strAttendanceID == "") // baru
    {
      // hapus dulu, menghindari duplikasi
      $strSQL = "
          DELETE FROM hrd_attendance WHERE id_employee = '" . $objAtt->strIDEmployee . "'
            AND attendance_date = '" . $this->strAttendanceDate . "';
          UPDATE hrd_overtime_application_employee SET start_actual = null, finish_actual = null WHERE id_employee = '" . $objAtt->strIDEmployee . "'
            AND overtime_date = '" . $this->strAttendanceDate . "';
        ";
      $strSQL .= "
          INSERT INTO hrd_attendance (
              id_employee, attendance_date,
              attendance_start, attendance_finish, normal_start, normal_finish,
              not_late, note, total_duration, morning_overtime, late_duration,
              early_duration, l1, l2, l3, l4, overtime, overtime_start, overtime_finish,
              shift_type, code_shift_type, code_shift_type2, status, is_absence, holiday
            )
            VALUES(
              '" . $objAtt->strIDEmployee . "', '" . $this->strAttendanceDate . "', $strAttendanceStart, $strAttendanceFinish,
              $strNormalStart, $strNormalFinish, $strNotLate, '" . $objAtt->strNote . "', '" . $objAtt->intTotalDuration . "',
              0, '" . $objAtt->intLate . "', '" . $objAtt->intEarly . "',
              '" . $objAtt->fltOT1 . "', '" . $objAtt->fltOT2 . "', '" . $objAtt->fltOT3 . "', '" . $objAtt->fltOT4 . "',
              '" . $objAtt->fltTotalOT . "', NULL, NULL,  '$strShiftType', '$strShiftCode', '$strShiftCode2',
              $strStatus, $strIsAbsence, $strHoliday
            );
        ";
    } else // anggap update
    {
      // hapus dulu, cegah duplikasi
      $strSQL = "
          DELETE FROM hrd_attendance WHERE id_employee = '" . $objAtt->strIDEmployee . "'
            AND attendance_date = '" . $this->strAttendanceDate . "'
            AND id <> '" . $objAtt->strAttendanceID . "';
        ";
      $strSQL .= "
          UPDATE hrd_attendance SET created=now(),
            modified_by = '" . $_SESSION['sessionUserID'] . "',
            attendance_start = $strAttendanceStart, attendance_finish = $strAttendanceFinish,
            normal_start = $strNormalStart, normal_finish = $strNormalFinish,
            not_late = $strNotLate, note = '" . $objAtt->strNote . "', total_duration = '" . $objAtt->intTotalDuration . "',
            late_duration = '" . $objAtt->intLate . "', early_duration = '" . $objAtt->intEarly . "',
            l1 = '" . $objAtt->fltOT1 . "', l2 = '" . $objAtt->fltOT2 . "',
            l3 = '" . $objAtt->fltOT3 . "', l4 = '" . $objAtt->fltOT4 . "',
            overtime = '" . $objAtt->fltTotalOT . "', shift_type = '$strShiftType',
            status = '$strStatus', is_absence = $strIsAbsence, holiday = $strHoliday
          WHERE id_employee = '" . $objAtt->strIDEmployee . "'
            AND attendance_date = '" . $this->strAttendanceDate . "';
        ";
    }
    $resExec = $this->db->execute($strSQL);
    return ($resExec != false);
  }

  /* saveCurrentAttendance : fungsi untuk menyimpan data attendance dari objAttendance yang sedang diproses
      berarti data yang disimpan adalah data 1 karyawan 1 hari
  */

  function setAttendanceDate($strDate, $bolGetData = false)
  {
    $abc = $this->db;
    $this->strAttendanceDate = $strDate;
    $this->intWeekDay = getWDay($strDate);
    $this->isPublicHoliday();
    $this->bolSaturday = (trim(getSetting("saturday")) == 't');
    if ($bolGetData) {
      $this->getDefaultNormalTime();
      $this->getBreakTime();
      $this->getShiftSchedule();
      //$this->getWorkSchedule();
      $this->getOutOfOffice();
      $this->getEmployeeInfo();
      $this->getAttendanceData();
      $this->getOvertimeData();
    }
  }
} // class
/* clsAttendanceInfo : kelas khusus untuk data kehadiran karyawan tertentu, sekedar menyimpan datanya saja
    tanggal kehadiran sesuai dengan tanggal yang ada di clsAttendanceClass
*/

class clsAttendanceInfo
{

var $bolAbsence;

var $bolGetOT;

  var $bolHoliday;     // id dari data kehadiran, jika sudah ada

  var $bolLate;      // jam masuk normal, hh:mm:ss

  var $bolNeedCalculateLate;     // jam pulang normal, hh:mm:ss

  var $bolShiftNight;  // jam hadir aktual

  var $bolShiftOff; // jam pulang aktual

    var $fltOT1;      // jam selesai istirahat (siang) yang normal

    var $fltOT2;      // jam kembali dari istirahat siang, aktual

    var $fltOT3;    // total durasi jam masuk dan pulang, dalam menit (yang dianggap aktual jam kerja)

    var $fltOT4;// total durasi jam masuk dan pulang, full

  var $fltOT5; // apakah perlu hitung keterlambatan

  var $fltTotalOT;       // apakah terlambat

  var $intAbsenceType;       // jumlah menit keterlambatan

  var $intBreakLate;      // jumlah menit pulang awal

  var $intEarly;  // jumlah menit keterlambatan setelah jam istirahat

  var $intLate;    // potongan keterlambatan, yang akan mengurangi lembur

  var $intLateDeduction;      // apakah perlu dihitung OTnya atau diabaikan

var $intTotalDuration;

var $intTotalDurationFull;

var $strAbsenceCode;

var $strActualBreak;

  var $strAttendanceFinish;      // andai ada

  var $strAttendanceID;  // total OT, dalam menit

  var $strAttendanceStart;      // apakah sedang dianggap absen atau tidak

    var $strCompany;      // apakah termasuk hari libur, hari libur nasional

    var $strIDEmployee;     // apakah status shift adalah OFF

  var $strNormalBreak;   // apakah termasuk shift malam

  var $strNormalFinish;  // jenis absen, jika ada

  var $strNormalStart;  // kode  absen, jika ada

    var $strNote;    // kode shift, jika ada -- untuk menentukan kerja siang atau malam

  var $strShiftCode;    // kode shift, jika ada -- untuk menentukan kerja siang atau malam

var $strShiftCode2;

  // konstuktor

  function clsAttendanceInfo()
  {
    // reset
  }

  /* newInfo : fungsi mereset atribut, untuk ID Employee tertentu
  */

  function calculateDuration()
  {
    if ($this->strAttendanceStart != "" && $this->strAttendanceFinish != "") {
      $this->intTotalDurationFull = getTotalHour($this->strAttendanceStart, $this->strAttendanceFinish);
      if ($this->strNormalStart == "") {
        $this->intTotalDuration = $this->intTotalDurationFull;
      } else {
        $this->intTotalDuration = getTotalHour($this->strNormalStart, $this->strAttendanceFinish);
      } // jam kerja dihitung dari normal
    }
  }

  /* calculateDuration : fungsi untuk menghitung total waktu kehadiran
      berdasarkan atribut waktu kehadiran (datang dan pulang)
  */

  function calculateLate()
  {
    // masih ada kemungkinan bugs, jika telat atau pulang cepat terlalu jauh, melewati tengah malam
    if ($this->strAttendanceStart != "" && $this->strNormalStart != "") {
      if ($this->strAttendanceStart > $this->strNormalStart) {
        $this->intLate = getTotalHour($this->strNormalStart, $this->strAttendanceStart);
      }
    }
    if ($this->strAttendanceFinish != "" && $this->strNormalFinish != "") {
      if ($this->strAttendanceFinish < $this->strNormalFinish) {
        $this->intEarly = getTotalHour($this->strAttendanceFinish, $this->strNormalFinish);
      }
    }
    /*
    // hitung kemungkinan telat jam istirahat
    if ($this->strNormalBreak != "" && $this->strActualBreak != "" && ($this->strNormalBreak < $this->strActualBreak) )
    {
      $this->intBreakLate = getTotalHour($this->strNormalBreak, $this->strActualBreak);
    }

    // jika pulang < 12, hitung pengurang gaji
    if ($this->intEarly > 0)
    {
      // lakukan pembulatan pemotongan gaji, dibulatkan ke atas 30menit ke atas
      // aturannya, seharusnya yang dibayar hanya waktu kerja, dibulatkan 30menit ke bawah
      $this->intLateDeduction = ceil($this->intEarly/30) * 30;
    }
    */
  }

  /* calculateLate : fungsi untuk menghitung keterlambatan dan pulang cepat
      berdasarkan atribut waktu kehadiran (datang dan pulang)
  */

  function calculateOvertime()
  {
    if ($this->bolGetOT) // hanya yang berhak lembur yang dapat
    {
      // reset dulu
      $this->fltOT1 = $this->fltOT2 = $this->fltOT3 = $this->fltOT4 = $fltTotalOT = 0;
      // hitung
      $intTotal = ($this->intTotalDurationFull > $this->intTotalDuration) ? $this->intTotalDuration : $this->intTotalDurationFull;
      // ambil yang terkecil, dengan asumsi, kalau full lebih kecil, berarti telat
      $this->calculateOvertimeDetail($intTotal);
    }
  }

  /* calculateOvertime : fungsi untuk menghitung total lembur, berdasarkan data durasi jam kerja
  */

  function calculateOvertimeDetail($intTotal)
  {
    // dibulatkan 30 menit ke bawah
    $intTotal = $intTotal - ($intTotal % 30);
    // cari total hari kerja normal
    // perlu cek apakah 5 atau 6 hari kerja
    /*if ($this->bolShortestDay)
    {
      $intWork = FRIDAY_WORK_HOUR * 60;
    }
    else
    {
      $intWork = ($this->strShiftCode == NIGHT_SHIFT_TYPE) ? (FULL_WORK_HOUR * 60) : (FULL_NIGHT_WORK_HOUR * 60);
    }
    */
    $intWork = 7 * 60; // default 7 jam dulu
    if ($this->bolHoliday || $this->bolShiftOff) // sedang OFF atau hari libur
    {
      if ($intTotal <= $intWork) // tidak sampai sepenuh hari, atau pas sehari
      {
        $this->fltOT2 = $intTotal;
      } else {
        $this->fltOT2 = $intWork;
        $intTmp = ($intTotal - $intWork); // ambil sisa
        if ($intTmp <= 60) {
          $this->fltOT3 = $intTmp;
        } else {
          $this->fltOT3 = 60;
          $this->fltOT4 = $intTotal - 60;
        }
      }
    } else // hari biasa
    {
      $intTotalOT = ($intTotal > $intWork) ? ($intTotal - $intWork) : 0; // hitung sisa untuk lembur
      if ($intTotalOT <= 60) {
        $this->fltOT1 = $intTotalOT;
      } else {
        $this->fltOT1 = 60;
        $this->fltOT2 = $intTotalOT - 60;
      }
    }
    $this->fltTotalOT = $this->fltOT1 + $this->fltOT2 + $this->fltOT3 + $this->fltOT4 + $this->fltOT5;
  }

  /* calculateOvertimeDetail : fungsi private, yang secara menghitung lembur lebih detail
      dipecah ke dalam L1, L2, dst
      intTotal adalah total jam kerja yang dianggap kerja aktual
  */

  function newInfo($strIDEmployee)
  {
    $this->strIDEmployee = $strIDEmployee;
    $this->strCompany = "";
    // reset
    $this->strAttendanceID = "";
    $this->strNormalStart = "";
    $this->strNormalFinish = "";
    $this->strAttendanceStart = "";
    $this->strAttendanceFinish = "";
    $this->strNormalBreak = "";
    $this->strActualBreak = "";
    $this->intTotalDuration = 0;
    $this->intTotalDurationFull = 0;
    $this->bolNeedCalculateLate = false;
    $this->bolLate = false;
    $this->intLate = 0;
    $this->intEarly = 0;
    $this->intBreakLate = 0;
    $this->intLateDeduction = 0;
    $this->bolGetOT = false;
    $this->fltOT1 = 0;
    $this->fltOT2 = 0;
    $this->fltOT3 = 0;
    $this->fltOT4 = 0;
    $this->fltOT5 = 0;
    $this->fltTotalOT = 0;
    $this->bolAbsence = false;
    $this->bolHoliday = false;
    $this->bolShiftOff = false;
    $this->bolShiftNight = false;
    $this->intAbsenceType = -1;
    $this->strAbsenceCode = "";
    $this->strShiftCode = "";
    $this->strShiftCode2 = "";
    $this->strNote = "";
  }
}

?>