<?php
/*
  DAFTAR KELAS TERKAIT DENGAN TANGGAL DAN JAM
  CREATED : 2009-01-16 (Yudi)
  UPDATED :
*/

/*
  KELAS TANGGAL/JAM YANG BERSIFAT UMUM, BERISI METHOD-METHOD TERKAIT DENGAN TANGGAL DAN JAM

*/

class clsCommonDate
{

  var $bolSQLFormat = true; // pemisah antara tanggal, default -, untuk format standard tampilan ke user entry

  var $intDayPos = 2; // pemisah antara jam, default :

  /*
    Mengatur posisi bagian-bagian tanggal, khusus untuk mengubah ke format tampilan entry user saja
    Misal: 2008-09-02 berarti yearPos = 0, monthPos = 1, dayPos = 2 (default format SQL)
           02/12/2009 berarti yearPos = 2, monthPos = 1, dayPos = 0 (default format indonesia)
    Untuk format yang digunakan dalam perhitungan, tetap menggunakan format standard query (YYYY-MM-DD)
  */

var $intMonthPos = 1; // posisi tahun

  var $intYearPos = 0; // posisi tahun

  var $strDateDelimiter = "-"; // posisi tahun

  var $strTimeDelimiter = ":"; // apakah format tanggal yang digunakan adalah standard seperti format SQL (YYYY-MM-DD)

  // konstruktor

  function clsCommonDate()
  {
  }

  /*  getTotalDayOfMonth : method untuk mencari tanggal terakhir dari sebuah bulan
      input: bulan, tahun (optional)
          input tahun diperlukan untuk menentukan tahun kabisat atau bukan, khusus untuk februari
  */

  function convertExcelDate($intSerial)
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

  /*  isKabisat : fungsi untuk menetukan apakah sebuah tahun termasuk tahun kabisat atau bukan
      input : tahun
      output: true jika tahun tersebut adalah kabisat
  */

  function convertSQLToTimestamp($strDate)
  {
    $arrDt = explode("-", $strDate);
    $intResult = @mktime(0, 0, 0, $arrDt[1], $arrDt[2], $arrDt[0]);
    return $intResult;
  }

  /* getMonthName : fungsi untuk mengambil kode bulan (nama bulan, dalam bahasa inggris) */

  function convertStandardToTimestamp($strDate)
  {
    $arrDt = $this->extractDate($strDate);
    return $arrDt['integer'];
  }

  /* getDayName : fungsi untuk mengambil kode hari (nama hari, dalam bahasa inggris) */

  function convertToSQL($strDate)
  {
    if ($this->bolSQLFormat) {
      return $strDate;
    } // tidak perlu diubah, sudah sama
    else {
      $arrDt = $this->extractDate($strDate);
      return $arrDt['year'] . "-" . $arrDt['month'] . "-" . $arrDt['day'];
    }
  }

  /*
    validStandardDate : fungsi untuk memeriksa apakah tanggal tertentu nilainya valid, dalam format standard
    input: tanggal
    output : true jika tanggal valid
  */

  function convertToStandard($strDate)
  {
    if ($this->bolSQLFormat) {
      return $strDate;
    } // tidak perlu diubah, sudah sama
    else {
      $arrDt = explode("-", $strDate);
      $arrTmp[$this->intYearPos] = $arrDt[0];
      $arrTmp[$this->intMonthPos] = $arrDt[1];
      $arrTmp[$this->intDayPos] = $arrDt[2];
      $strResult = $arrTmp[0] . $this->strDateDelimiter . $arrTmp[1] . $this->strDateDelimiter . $arrTmp[2];
      return $strResult;
    }
  }

  /*
    validDate : fungsi untuk memeriksa apakah tanggal tertentu nilainya valid, dalam format Query (SQL)
    input: tanggal
    output : true jika tanggal valid
  */

  function excelSerialTimeToString($intTime)
  {
    $tmp = $intTime * 1440;
    $tmp2 = floor($tmp / 60);
    $tmp3 = $tmp - $tmp2 * 60;
    $tmp3 = round($tmp3);
    return $tmp2 . ":" . $tmp3;
  }

  /*
    validTime : fungsi untuk memeriksa apakah jam tertentu nilainya valid
    input: waktu
    output : true jika tanggal valid
  */

function extractDate($strDate)
  {
    $arrResult = [
        "year"    => 0,
        "month"   => 1,
        "day"     => 1,
        "wday"    => 0,
        "hour"    => 0,
        "minute"  => 0,
        "second"  => 0,
        "integer" => 0, //timestamp unix
    ];
    if ($strDate == "") {
      return false;
    }
    $arrTmp = explode(" ", $strDate);
    if ($this->validDate($arrTmp[0])) {
      $arrTgl = explode("-", $arrTmp[0]);
      $arrResult['year'] = (int)$arrTgl[0];
      if (isset($arrTgl[1])) {
        $arrResult['month'] = (int)$arrTgl[1];
      }
      if (isset($arrTgl[2])) {
        $arrResult['day'] = (int)$arrTgl[2];
      }
    }
    if (isset($arrTmp[1]) && $this->validTime($arrTmp[1])) {
      // extrat jam
      $arrWkt = explode($this->strTimeDelimiter, $arrTmp[1]);
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
  }

  /*
    extractDate : fungsi untuk meng-ekstrak tanggal dalam komponen-komponen tanggal terkait
    input: tanggal, dalam format SQL
    output: array berisi komponen tanggal tersebut
  */

function getDateFormat($dateData, $strFormat)
  {
    $strHasil = "";
    if ($dateData != "") {
      //list($intTahun,$intBulan,$intTanggal) = explode($this->strDateDelimiter,$dateData);
      $arrDt = $this->extractDate($dateData);
      $intTahun = (int)$arrDt['year'];
      $intTanggal = (int)$arrDt['day'];
      $intBulan = (int)$arrDt['month'];
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
          $strHasil .= getWords($this->getMonthName((int)$intBulan, false));
        } else if ($strFormat[$i] == 'n') { // bulan, angka tidak harus 2 digit
          $strHasil .= (int)$intBulan;
        } else if ($strFormat[$i] == 'F') { // bulan, lengkap
          $strHasil .= getWords($this->getMonthName((int)$intBulan));
        } else if ($strFormat[$i] == 'y') { // tahun 2 digit
          $strHasil .= substr($intTahun, 2, 2);
        } else if ($strFormat[$i] == 'Y') { // tahun, angka 4 digit
          $strHasil .= $intTahun;
        }
      }
    }
    return $strHasil;
  } //extractDate

  /*
    getWDay : fungsi untuk menentukan hari apa dalam suatu tanggal
    input : tanggal, format query (YYYY-MM-DD)
    output : hari (dalam integer: 0-6)
  */

  function getDayName($intMonth, $bolFull = true)
  {
    $arrFullName = [0 => "sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
    $arrShortName = [0 => "sun", "mon", "tue", "wed", "thu", "fri", "sat"];
    $strResult = "";
    if ($bolFull) {
      $strResult = (isset($arrFullName[$intMonth])) ? $arrFullName[$intMonth] : "";
    } else {
      $strResult = (isset($arrShortName[$intMonth])) ? $arrShortName[$intMonth] : "";
    }
    return $strResult;
  }

  /*
    getDateFormat : fungsi untuk mengirimkan format tanggal sesuai format yang diinginkan, standard PHP, dari format PgSQL
    input: dateData sebagai tanggal dari pgSQL, strFormat adalah format yang diinginkan, standard PHP >> YYYY-MM-DD
    output: string berupa tanggal dengan format yang diinginkan
    proses: buat sendiri
  */

  function getIntervalDate($dateFrom, $dateThru)
  {
    $intDuration = 0;
    if ($this->validDate($dateFrom) && $this->validDate($dateThru)) {
      list($tahun1, $bulan1, $tanggal1) = explode("-", $dateFrom);
      list($tahun2, $bulan2, $tanggal2) = explode("-", $dateThru);
      $arrFrom = $this->extractDate($dateFrom);
      $arrThru = $this->extractDate($dateThru);
      $tsTanggal1 = mktime(0, 0, 0, $arrFrom['month'], $arrFrom['day'], $arrFrom['year']);
      $tsTanggal2 = mktime(10, 0, 0, $arrThru['month'], $arrThru['day'], $arrThru['year']);
      $intSelisih = $tsTanggal2 - $tsTanggal1;
      $intDuration = ($intSelisih / 86400);
    }
    return round($intDuration);
  } //end of pgDateFormat

  /*
    convertToSQL : fungsi untuk mengubah tanggal ke format standard Query : YYYY-MM-DD
    Input: tanggal dengan format standard interface
    Output : tanggal dengan format standard query
  */

  function getMonthName($intMonth, $bolFull = true)
  {
    $arrFullName = [
        1 => "january",
        "february",
        "march",
        "april",
        "may",
        "june",
        "july",
        "august",
        "september",
        "october",
        "november",
        "december"
    ];
    $arrShortName = [
        1 => "jan",
        "feb",
        "mar",
        "apr",
        "may",
        "jun",
        "jul",
        "aug",
        "sep",
        "oct",
        "nov",
        "dec"
    ];
    $strResult = "";
    if ($bolFull) {
      $strResult = (isset($arrFullName[$intMonth])) ? $arrFullName[$intMonth] : "";
    } else {
      $strResult = (isset($arrShortName[$intMonth])) ? $arrShortName[$intMonth] : "";
    }
    return $strResult;
  }

  /*
    convertToStandard : fungsi untuk mengubah tanggal dari format standard Query : YYYY-MM-DD, ke format standard tampilan
    Input: tanggal dengan format query
    Output: tanggal dengan format standard tampilan
  */

function getNextDate($startDate, $duration = 1)
  {
    $lastDate = $startDate; //default
    $intDuration = 1;
    if ($startDate != "") {
      if ($duration != "") {
        $intDuration = $duration;
      }
      //list($tahun,$bulan,$tanggal) = explode("-",$startDate);
      $arr = $this->extractDate($startDate);
      $tsTanggal = $arr['integer'];
      $tsTanggal += ($intDuration * 86400);
      $arrTmp[$this->intYearPos] = "Y";
      $arrTmp[$this->intMonthPos] = "m";
      $arrTmp[$this->intDayPos] = "d";
      //$lastDate = date($arrTmp[0].$this->strDateDelimiter.$arrTmp[1].$this->strDateDelimiter.$arrTmp[2], $tsTanggal);
      $lastDate = date("Y-m-d", $tsTanggal);
    }
    return $lastDate;
  }

  /*
    convertStandardToTimestamp : fungsi untuk mengubah tanggal dari format standard ke nilai UNIX timestamp (integer)
    Input: tanggal dengan format standard
    Output: integer dari UNIX timestamp
  */

  function getPeriodOfMonth($intMonth = "", $intYear = "")
  {
    if ($intYear == "") {
      $intYear = date("Y");
    }
    if ($intMonth == "") {
      $intMonth = date("m");
    }
    $arrResult = ["start" => "", "finish" => ""];
    $strMonth = ($intMonth < 10) ? "0" . $intMonth : $intMonth;
    $strStart = $intYear . "-" . $strMonth . "-01";
    $strFinish = $intYear . "-" . $strMonth . "-" . $this->getTotalDayOfMonth($intMonth, $intYear);
    //$arrResult['start']   = $this->convertToStandard($strStart);
    //$arrResult['finish']  = $this->convertToStandard($strFinish);
    return $arrResult;
  }

  /*
    convertSQLToTimestamp : fungsi untuk mengubah tanggal dari format Query ke nilai UNIX timestamp (integer)
    Input: tanggal dengan format Query (YYYY-MM-DD)
    Output: integer dari UNIX timestamp
  */

  function getTotalDayOfMonth($intMonth, $intYear = "")
  {
    $intResult = 0;
    $intMonth = (int)$intMonth;
    $intYear = (int)$intYear;
    $arrMaxDay = [
        1 => 31,
        28,
        31,
        30,
        31,
        30,
        31,
        31,
        30,
        31,
        30,
        31
    ];
    if ($intMonth == 2) // kasus khusus untuk februari
    {
      if ($intYear == "" || !is_numeric($intYear)) {
        $intYear = date("Y");
      } // default tahun sekarang
      $intResult = ($this->isKabisat($intYear)) ? 29 : 28;
    } else if (isset($arrMaxDay[$intMonth])) {
      $intResult = $arrMaxDay[$intMonth];
    }
    return $intResult;
  }

  /*
    convertExcelDate : fungsi untuk convert nilai tanggal (numeric) dari excel ke format SQL
    input : integer, data tanggal dari file excel
    output : string tanggal, format query (YYYY-MM-DD)
  */

  function getWDay($strDate)
  {
    $strResult = "";
    if ($this->validDate($strDate)) {
      list($year, $month, $day) = explode("-", $strDate);
      $tsDate = mktime(0, 0, 0, (int)$month, $day, $year);
      $dtDate = getdate($tsDate);
      $strResult = $dtDate['wday'];
    }
    return $strResult;
  }

  /*
    excelSerialTimeToString : fungsi untuk convert nilai jam (numeric) dari excel ke format SQL
    input  : integer, nilai jam dari file excel
    output : string, jam HH:MM
  */

  function isKabisat($intYear)
  {
    if (($intYear % 4) != 0) {
      return false;
    } else {
      if (($intYear % 100) == 0) // bisa dibagi seratus
      {
        return (($intYear % 400) == 0);
      } // bisa dibagi 400 = kabisat, kalau gak bisa,bukan
      else {
        return true;
      }
    }
  }

  /* getNextDate : fungsi yang mengirimkan data tanggal berikutnya (sebanyak durasi yang diinginan, default  1
      INPUT: $startDate, tanggal awal format query (SQL), $durasi (optional), intenger dalam skala hari
      OUTPUT: $startDate + $duration, format query (SQL)
  */

  function validDate($strDate)
  {
    $bolResult = false;
    if ($strDate != "" && preg_match(
            '/(\d+)' . $this->strDateDelimiter . '(\d+)' . $this->strDateDelimiter . '(\d+)/',
            $strDate
        )
    ) {
      $arrDt = explode($this->strDateDelimiter, $strDate);
      $year = (int)$arrDt[0];
      $month = (int)$arrDt[1];
      $day = (int)$arrDt[2];
      $bolResult = checkdate($month, $day, $year);
    }
    return $bolResult;
  } // getNextDate

  /*
    getIntervalDate : Fungsi yang mengirimkan data selisih tanggal
    INPUT: tanggal awal  dan tanggal akhir format YYYY-MM-DD
    OUTPUT: selisih hari
  */

  function validStandardDate($strDate)
  {
    $bolResult = false;
    if ($strDate != "" && preg_match(
            '/(\d+)' . $this->strDateDelimiter . '(\d+)' . $this->strDateDelimiter . '(\d+)/',
            $strDate
        )
    ) {
      $arrDt = explode($this->strDateDelimiter, $strDate);
      $year = (int)$arrDt[$this->intYearPos];
      $month = (int)$arrDt[$this->intMonthPos];
      $day = (int)$arrDt[$this->intDayPos];
      $bolResult = checkdate($month, $day, $year);
    }
    return $bolResult;
  }

  /*
    getPeriodOfMonth : fungsi untuk mengambil tanggal awal dan akhir dari sebuah bulan
    input : bulan dan tahun
    output : array ('start', 'finish'), format adalah format query
  */

  function validTime($strTime)
  {
    $bolResult = true;
    $arrTmp = explode($this->strTimeDelimiter, $strTime);
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
} // class
?>