<?php
/* DAFTAR FUNGSI-FUNGSI YANG TERKAIT DENGAN TANGGAL (DAN JAM)
   AUTHOR: YUDI K.

*/
// array untuk menyimpan jumlah tanggal maksimal tiap bulan
// khusus FEBRUARI, dipakai 28 aja :D
$arrDaysOfWeek = [
    1  => 31,
    2  => 28,
    3  => 31,
    4  => 30,
    5  => 31,
    6  => 30,
    7  => 31,
    8  => 31,
    9  => 30,
    10 => 31,
    11 => 30,
    12 => 31,
];
//fungsi untuk mengirimkan format tanggal sesuai format yang diinginkan, standard PHP, dari format PgSQL
//input: dateData sebagai tanggal dari pgSQL, strFormat adalah format yang diinginkan, standard PHP >> YYYY-MM-DD
//output: string berupa tanggal dengan format yang diinginkan
//proses: buat sendiri
function pgDateFormat($dateData, $strFormat)
{
  $strHasil = "";
  if ($dateData != "") {
    list($intTahun, $intBulan, $intTanggal) = explode("-", $dateData);
    $intTahun = (int)$intTahun;
    $intTanggal = (int)$intTanggal;
    $intBulan = (int)$intBulan;
    // loop per karakter
    $intLength = strlen($strFormat);
    for ($i = 0; $i < $intLength; $i++) {
      if (strpos("/-: ,.", $strFormat[$i]) != false) { // delimiter
        $strHasil .= $strFormat[$i];
      } else if ($strFormat[$i] == 'd') { // tanggal 2 digit
        $strHasil .= ($intTanggal < 10) ? "0" . $intTanggal : $intTanggal;
      } else if ($strFormat[$i] == 'j') { // tanggal tidak harus 2 digit
        $strHasil .= (int)$intTanggal;
      } else if ($strFormat[$i] == 'm') { // bulan, angka 2 digit
        $strHasil .= ($intBulan < 10) ? "0" . $intBulan : $intBulan;
      } else if ($strFormat[$i] == 'M') { // bulan, 3 huruf
        $strHasil .= getBulanSingkat((int)$intBulan);
      } else if ($strFormat[$i] == 'n') { // bulan, angka tidak harus 2 digit
        $strHasil .= (int)$intBulan;
      } else if ($strFormat[$i] == 'F') { // bulan, lengkap
        $strHasil .= getBulan((int)$intBulan);
      } else if ($strFormat[$i] == 'y') { // tahun 2 digit
        $strHasil .= substr($intTahun, 2, 2);
      } else if ($strFormat[$i] == 'Y') { // tahun, angka 4 digit
        $strHasil .= $intTahun;
      }
    }
  }
  return $strHasil;
} //end of pgDateFormat
// fungsi untuk mengubah tanggal dari format MM/DD/YYYY ke format YYYY-DD-MM
function standardDateToSQLDate($strDate)
{
  $strResult = $strDate;
  if ($strDate != "") {
    $arrDate = explode("/", $strDate);
    if (count($arrDate) >= 3) {
      $strResult = $arrDate[2] . "-" . $arrDate[0] . "-" . $arrDate[1];
    }
  }
  return $strResult;
}//standardDateToSQLDate
// fungsi untuk memeriksa apakah data sesuai format standard
// format standard adalah YYYY-MM-DD
// input berupa string
function validStandardDate($strDate)
{
  $bolResult = false;
  if ($strDate != "") {
    list($year, $month, $day) = explode("-", $strDate);
    $year = (int)$year;
    $month = (int)$month;
    $day = (int)$day;
    $bolResult = checkdate($month, $day, $year);
  }
  return $bolResult;
} //validStandardDate
// fungsi untuk membandingkan jam dengan di javascript
// format input: YYYY-MM-DD, asumsi data tanggal sudah valid
// output: -1 jika dt1 < dt2, 0 jika sama, 1 jika dt1 > dt2
function dateCompare($dt1 = "", $dt2 = "")
{
  // data valid
  $result = 0; //default
  if ($dt1 == "" || $dt2 == "") {
    return 0;
  }
  // sekedar chek apakah ada data jam dibelakangnya
  $arr1 = explode(" ", $dt1);
  $arr2 = explode(" ", $dt2);
  $dt1 = trim($arr1[0]);
  $dt2 = trim($arr2[0]);
  // split data
  $arr1 = explode("-", $dt1);
  $arr2 = explode("-", $dt2);
  $strThn1 = (int)($arr1[0]);
  $strThn2 = (int)($arr2[0]);
  $strBln1 = (int)($arr1[1]);
  $strBln2 = (int)($arr2[1]);
  $strTgl1 = (int)($arr1[2]);
  $strTgl2 = (int)($arr2[2]);
  if ($strThn1 == $strThn2) // cek tahun
  {
    if ($strBln1 == $strBln2) // cek bulan
    {
      if ($strTgl1 == $strTgl2) // cek tanggal
      {
        $result = 0;
      } else {
        $result = ($strTgl1 < $strTgl2) ? -1 : 1;
      }
    } else {
      $result = ($strBln1 < $strBln2) ? -1 : 1;
    }
  } else {
    $result = ($strThn1 < $strThn2) ? -1 : 1;
  }
  return $result;
}//dateCompare
// fungsi untuk mengambil nama hari dari format tanggal YYYY-MM-DD
// $strDate = tanggal (YYYY-MM-DD), $bolShort = formatnya pendek
function getDayName($strDate, $bolShort = false)
{
  $strResult = "";
  if ($strDate != "") {
    list($year, $month, $day) = explode("-", $strDate);
    $year = (int)$year;
    $month = (int)$month;
    $day = (int)$day;
    if (checkdate($month, $day, $year)) {
      $tsDate = mktime(0, 0, 0, $month, $day, $year);
      $dtDate = getdate($tsDate);
      if ($bolShort) {
        $strResult = getNamaHariSingkat($dtDate['wday'] + 1);
      } else {
        $strResult = getNamaHari($dtDate['wday'] + 1);
      }
    }
  }
  return $strResult;
}//generateDayName
// fungsi yang mengirimkan data tanggal berikutnya (sebanyak durasi yang diinginan, default  1
// INPUT: $startDate, tanggal awal format YYYY-MM-DD, $durasi (optional), intenger dalam skala hari
// OUTPUT: $startDate + $duration, format YYYY-MM-DD
function getNextDate($startDate, $duration = 1)
{
  $lastDate = $startDate; //default
  $intDuration = 1;
  if ($startDate != "") {
    if ($duration != "") {
      $intDuration = $duration;
    }
    list($tahun, $bulan, $tanggal) = explode("-", $startDate);
    $tsTanggal = mktime(10, 0, 0, $bulan, $tanggal, $tahun);
    $tsTanggal += ($intDuration * 86400);
    $lastDate = date("Y-m-d", $tsTanggal);
  }
  return $lastDate;
} // getNextDate
// fungsi mencari tanggal 1 (atau N) bulan berikutnya
function getNextDateNextMonth($startDate, $duration = 1)
{
  global $arrDaysOfWeek;
  $lastDate = $startDate; //default
  $intDuration = 1;
  $bolMinus = ($duration < 0);
  if (validStandardDate($startDate)) {
    if (is_numeric($duration)) {
      $intDuration = $duration;
    } else {
      $intDuration = 0;
    }
    $intYear = floor($intDuration / 12); // nyari total tahun-nya
    $intMonth = ($intDuration % 12); // sisa bulan
    list($tahun, $bulan, $tanggal) = explode("-", $startDate);
    $tahun = (int)$tahun;
    $bulan = (int)$bulan;
    $tanggal = (int)$tanggal;
    if ($bolMinus) {
      $intYear++;
    }
    $tahun += $intYear;
    $bulan += $intMonth;
    if ($bulan > 12) {
      $tahun++;
      $bulan = $bulan - 12;
    }
    // sesuaikan dgn tanggal akhir dari bulan
    if ($bulan == 0) {
      $bulan = 12;
    }
    if ($tanggal > $arrDaysOfWeek[$bulan]) {
      $tanggal = $arrDaysOfWeek[$bulan];
    }
    $lastDate = pgDateFormat("$tahun-$bulan-$tanggal", "Y-m-d");
  }
  return $lastDate;
} // getNextDate
// fungsi untuk mengubah nilai tanggal dari excel (tipe integer) ke format tanggal untuk SQL (agar valid  untuk input ke DB)
function convertExcelDateToSQL($dtTanggal)
{
  $strResult = "NULL";
  $intExcelDate2000 = 36526; // nilai integer dari tanggal 01-01-2000,
  // untuk konversi tanggal dari excel (integer)
  // -- UNTUK SEMENTARA, ASUMSI DB YANG DIPAKAI ADALAH POSTGRE
  if ($dtTanggal == "") {
    $strResult = "NULL";
  } else {
    //$strWeddingdate = "'" .date("Y-m-d",$strWeddingdate). "'";
    $intSelisih = $dtTanggal - $intExcelDate2000;
    $strResult = "(date '2000-01-01' + interval '$intSelisih days')";
  }
  return $strResult;
}//convertExcelDateToSQL
// mengambil data weekday (0->6), dari input tanggal dengan format YYYY-MM-DD
function getWDay($strDate)
{
  $strResult = "";
  if ($strDate != "") {
    list($year, $month, $day) = explode("-", $strDate);
    $tsDate = mktime(0, 0, 0, (int)$month, $day, $year);
    $dtDate = getdate($tsDate);
    $strResult = $dtDate['wday'];
  }
  return $strResult;
}

// fungsi untuk mengekstract data tanggal
// input: format : YYYY-MM-DD HH:NN:SS
function extractDate($strDate)
{
  $arrResult = [
      "year"    => 0,
      "month"   => 1,
      "day"     => 1,
      "hour"    => 0,
      "minute"  => 0,
      "second"  => 0,
      "integer" => 0, //timestamp unix
  ];
  if ($strDate == "") {
    return false;
  }
  $arrTmp = explode(" ", $strDate);
  $arrTgl = explode("-", $arrTmp[0]);
  $arrResult['year'] = (int)$arrTgl[0];
  if (isset($arrTgl[1])) {
    $arrResult['month'] = (int)$arrTgl[1];
  }
  if (isset($arrTgl[2])) {
    $arrResult['day'] = (int)$arrTgl[2];
  }
  if (isset($arrTmp[1])) {
    // extrat jam
    $arrWkt = explode(":", $arrTmp[1]);
    $arrResult['hour'] = (int)$arrWkt[0];
    if (isset($arrWkt[1])) {
      $arrResult['minute'] = (int)$arrWkt[1];
    }
    if (isset($arrWkt[2])) {
      $arrResult['second'] = (int)$arrWkt[2];
    }
  }
  $arrResult['integer'] = @mktime(
      $arrResult['hour'],
      $arrResult['minute'],
      $arrResult['second'],
      $arrResult['month'],
      $arrResult['day'],
      $arrResult['year']
  );
  return $arrResult;
} //extractDate
// ----------------------------------- BAGIAN WAKTU --------------------
// membandingkan waktu HH:NN, hanya sampai hitungan mennit
// 0 jika sama, 1 jika lebih besar, -1 jika lebih kecil
function timeCompare($time1, $time2)
{
  $intResult = 0;
  $arr1 = explode(":", $time1);
  $arr2 = explode(":", $time2);
  $jam1 = (isset($arr1[0])) ? (int)trim($arr1[0]) : 0;
  $jam2 = (isset($arr2[0])) ? (int)trim($arr2[0]) : 0;
  $menit1 = (isset($arr1[1])) ? (int)trim($arr1[1]) : 0;
  $menit2 = (isset($arr2[1])) ? (int)trim($arr2[1]) : 0;
  if ($jam1 == $jam2) {
    if ($menit1 == $menit2) {
      $intResult = 0;
    } else if ($menit1 < $menit2) {
      $intResult = -1;
    } else {
      $intResult = 1;
    }
  } else if ($jam1 < $jam2) {
    $intResult = -1;
  } else {
    $intResult = 1;
  }
  return $intResult;
}//timeCompare
function getDateInterval($dateFrom, $dateThru)
{
  $arrDur = [
      "year"  => 0,
      "month" => 0,
      "day"   => 0,
  ];
  if (validStandardDate($dateFrom) && validStandardDate($dateThru)) {
    $arr1 = extractDate($dateFrom);
    $arr2 = extractDate($dateThru);
    if ($arr1['integer'] > $arr2['integer']) {
      // tanggal 1 lebih besar, ditukar saja
      $arr3 = $arr1;
      $arr1 = $arr2;
      $arr2 = $arr3;
    }
    $arrDur['year'] = ($arr2['year'] - $arr1['year']);
    if ($arr1['month'] == $arr2['month']) // sama, cek tanggalnya
    {
      if (($arr1['day'] > $arr2['day']) && ($arrDur['year'] > 0)) {
        $arrDur['year']--;
      } // belum genap setahun
    } else if ($arr1['month'] > $arr2['month']) {
      if ($arrDur['year'] > 0) {
        $arrDur['year']--;
      }
      $arrDur['month'] = ($arr2['month'] - $arr1['month']) + 12;
    } else {
      $arrDur['month'] = ($arr2['month'] - $arr1['month']);
    }
    if ($arr1['day'] > $arr2['day']) {
      $intPrevMon = ($arr2['month'] == 1) ? 12 : $arr2['month'] - 1;
      $intTmpYear = ($intPrevMon == 1) ? $arr2['year'] - 1 : $arr2['year'];
      $intMax = lastDay($intPrevMon, $intTmpYear);
      $arrDur['day'] = $intMax + ($arr2['day'] - $arr1['day']);
      if ($arrDur['month'] > 0) {
        $arrDur['month']--;
      }
    } else {
      $arrDur['day'] = $arr2['day'] - $arr1['day'];
    }
  }
  return $arrDur;
}

// fungsi yang mengirimkan data selisih tanggal
// INPUT: tanggal awal  dan tanggal akhir format YYYY-MM-DD, $durasi (optional)
// OUTPUT: selisih hari
function getIntervalDate($dateFrom, $dateThru)
{
  $intDuration = 0;
  if (validStandardDate($dateFrom) && validStandardDate($dateThru)) {
    list($tahun1, $bulan1, $tanggal1) = explode("-", $dateFrom);
    list($tahun2, $bulan2, $tanggal2) = explode("-", $dateThru);
    $tsTanggal1 = mktime(0, 0, 0, $bulan1, $tanggal1, $tahun1);
    $tsTanggal2 = mktime(10, 0, 0, $bulan2, $tanggal2, $tahun2);
    $intSelisih = $tsTanggal2 - $tsTanggal1;
    $intDuration = ($intSelisih / 86400);
  }
  return round($intDuration);
}

//cari selisih waktu (dalam menit), antara 2 jam
//input:$tsAwal, $tsAkhir, format: [hh:mm:ss]
//output: selisih (akhir-awal), dibulatkan dalam menit
function getIntervalHour($tsAwal, $tsAkhir)
{
  $intHasil = 0;
  if ($tsAkhir == "" || $tsAwal == "") {
    return 0;
  }
  //cek apakah awal lebih besar dari akhir, dianggap melewati tengah malam
  if (substr($tsAwal, 2, 1) == ":") {
    list($jam, $menit) = explode(":", $tsAwal);
  } else {
    $jam = substr($tsAwal, 0, 2);
    $menit = substr($tsAwal, 2, 2);
  }
  $dtAwal = mktime((int)$jam, (int)$menit, "0", "1", "1", "1");
  if (substr($tsAkhir, 2, 1) == ":") {
    list($jam, $menit) = explode(":", $tsAkhir);
  } else {
    $jam = substr($tsAkhir, 0, 2);
    $menit = substr($tsAkhir, 2, 2);
  }
  $dtAkhir = mktime((int)$jam, (int)$menit, "0", "1", "1", "1");
  if ($tsAwal > $tsAkhir) { // kasus lewat tengah malam
    $intHasil = ($dtAwal - $dtAkhir) / 60;
    $intHasil = (24 * 60) - (int)$intHasil;
  } else {
    $intHasil = ($dtAkhir - $dtAwal) / 60;
  }
  return (int)$intHasil;
} //getInterval
// fungsi menghitung menit berikutnya dari sebuah jam HH:NN
function getNextMinute($startTime, $duration = 1)
{
  $lastTime = "";
  $intDuration = 1;
  if ($startTime != "") {
    if ($duration != "") {
      $intDuration = $duration;
    }
    list($jam, $menit) = explode(":", $startTime);
    $tsTanggal = mktime($jam, $menit, 0, 1, 1, 2000);
    $tsTanggal += ($intDuration * 60);
    $lastTime = date("H:i", $tsTanggal);
  }
  return $lastTime;
} // getNextDate
function getNextYear($startDate, $duration = 1)
{
  if (!is_numeric($duration) || ($duration == 0)) {
    return $startDate;
  }
  $lastDate = $startDate; //default
  $arrTmp = extractDate($startDate);
  $arrTmp['year'] = $arrTmp['year'] + $duration; // tambahkan
  $intLastDay = lastDay($arrTmp['month'], $arrTmp['year']);
  if ($arrTmp['day'] > $intLastDay) {
    $arrTmp['day'] = $intLastDay;
  }
  $lastDate = $arrTmp['year'] . "-" . (addPrevZero($arrTmp['month'], 2)) . "-" . addPrevZero($arrTmp['day'], 2);
  // mundurkan satu hari
  //$lastDate = getNextDate($lastDate, -1);
  return $lastDate;
} // getNextYear
// fungsi untuk ngecek tanggal terakhir dalam sebuah bulan tertentu
// memeriksa apakah suatu jam valid atau tidak
// jam dinyatakan valid jika formatnya HH:NN atau HH:NN:SS
function validTime($strTime)
{
  $bolResult = true;
  $arrTmp = explode(":", $strTime);
  if (count($arrTmp) > 1) { // untuk sementara tidak ngecek apakah
    $bolResult = (is_numeric($arrTmp[0]) && is_numeric($arrTmp[1]));
    if (count($arrTmp) > 2) {
      $bolResult = ($bolResult && (is_numeric($arrTmp[2])));
    }
  } else {
    $bolResult = false;
  }
  return $bolResult;
}

// mencari jumlah selisih jam, tanpa dikurangi jam istirahat
// output dalam menit
function getTotalHour($strStart, $strFinish)
{
  $intResult = 0;
  if (substr($strFinish, 0, 5) == "00:00") { // untuk finish = 00, ada perlakukan khusus
    $intResult += getIntervalHour($strStart, "24:00:00");
  } else if ($strStart > $strFinish) { // melewati tengah malam
    $intResult += getIntervalHour($strStart, "24:00:00");
    $intResult += getIntervalHour("00:00:00", $strFinish);
  } else {
    // proses biasa
    // -- Cari Total Menit Dulu
    $intResult = getIntervalHour($strStart, $strFinish);
  }
  return $intResult;
}//getTotalHour
// fungsi untu mengubah jumlah menit ke format time
// input integer, output string (hh:mm)
// $bol Empty, jika true, maka jika nilai 0 akan dijadiin blank
function minuteToTime($intMin, $bolEmpty = true)
{
  $strResult = "";
  if (is_numeric($intMin)) {
    if ($bolEmpty && $intMin == 0) {
      $strResult = "";
    } else {
      $intJam = floor($intMin / 60);
      $intMen = ($intMin % 60);
      // format
      $intJam = addPrevZero($intJam, 2);
      $intMen = addPrevZero($intMen, 2);
      $strResult = "$intJam:$intMen";
    }
  }
  return $strResult;
}// minuteToTime
/*
  kelas untuk menghitung execution time
  author: Yudi K.
*/

class CexecutionTime
{

  var $intStart;

  function CexecutionTime()
  {
    $tmp = getdate();
    $this->intStart = $tmp[0];
  }

  // fungsi menampilkan durasi, dari saat ini dengan intStart
  // tipe: 0 = output H:M:S, 1= output jumlah detik
  function getDuration($intType = 0)
  {
    $strResult = "";
    $tmp = getdate();
    $intFinish = $tmp[0];
    $intSelisih = $intFinish - $this->intStart;
    $intDetik = ($intSelisih % 60);
    $intMenit = (floor($intSelisih / 60) % 60);
    $intJam = floor($intMenit / 3600);
    if ($intType == 0) {
      $strResult = "$intJam:$intMenit:$intDetik";
    } else {
      $strResult = $intSelisih;
    }
    return $strResult;
  } //getDuration
}

function lastday($month, $year)
{
  if (empty($month)) {
    $month = date('m');
  }
  if (empty($year)) {
    $year = date('Y');
  }
  $result = strtotime("{$year}-{$month}-01");
  $result = strtotime('-1 second', strtotime('+1 month', $result));
  return date("d", $result);
}

function getDefaultSalaryPeriode(&$strStart, &$strFinish, $strCurrent = "", $isForSeverance = false)
{
  global $db;
  if ($strCurrent == "") {
    list($thn, $bln, $tgl) = explode("-", date("Y-m-d"));
  } else {
    // asumsi dah valid
    list($thn, $bln, $tgl) = explode("-", $strCurrent);
  }
  $tsCurrent = mktime(0, 0, 0, (int)$bln, (int)$tgl, (int)$thn);
  $dtNow = getdate($tsCurrent);
  $strStart = (is_numeric(getSetting("salary_date_from"))) ? date(
      "Y-m-d",
      mktime(
          10,
          0,
          0,
          $dtNow['mon'],
          getSetting("salary_date_from"),
          date("Y")
      )
  ) : date("Y") . "-" . date("m") . "-01";
  if ($tgl <= getSetting("salary_date_thru")) {
    $strStart = date("Y-m-d", strtotime('-2 month', strtotime($strStart)));
  } else {
    $strStart = date("Y-m-d", strtotime('-1 month', strtotime($strStart)));
  }
  if ($isForSeverance) {
    $strStart = date("Y-m-d", strtotime('+1 month', strtotime($strStart)));
  }
  $strFinish = date("Y-m-d", strtotime('-1 second', strtotime('+1 month', strtotime($strStart))));
  return 0;
} //getDefaullSalaryPeriode
//Cek apakah tanggal sudah kedaluwarsa, sudah melewati batas gajian bulan sebelumnya
function isOutDated($strDate)
{
  //bandingkan tanggalnya dengan periode perhitungan gaji bulan ini, jika masih valid berarti tidak kadaluwarsa
  $strSalaryStart = "";
  $strSalaryFinish = "";
  $strSalaryBase = date("Y-m-d", strtotime('+1 month', strtotime($strDate)));
  getDefaultSalaryPeriode($strSalaryStart, $strSalaryFinish, $strSalaryBase);
  if (dateCompare($strSalaryStart, $strDate) <= 0 && dateCompare($strDate, $strSalaryFinish) <= 0) {
    return false;
  } else {
    return true;
  }
}

function excelSerialDateToString($intSerial)
{
  $l = $intSerial + 68569 + 2415019;
  $n = intval((4 * $l) / 146097);
  $l = $l - intval((146097 * $n + 3) / 4);
  $i = intval((4000 * ($l + 1)) / 1461001);
  $l = $l - intval((1461 * $i) / 4) + 31;
  $j = intval((80 * $l) / 2447);
  $intDay = $l - intval((2447 * $j) / 80);
  $l = intval($j / 11);
  $intMonth = $j + 2 - (12 * $l);
  $intYear = 100 * ($n - 49) + $i + $l;
  return $intYear . "-" . $intMonth . "-" . $intDay;
}

?>