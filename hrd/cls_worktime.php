<?php
/*
  UNTUK MENAMPUNG KELAS-KELAS YANG TERKAIT DENGAN WAKTU KERJA - TIME MANAGEMENT
  MISAL : TOTAL HARI KERJA PER KARYAWAN, TOTAL OFF, TOTAL HARI LIBUR DSB
  UPDATE : 2009-01-23 (Yudi)
*/
include_once("../global/cls_date.php");
include_once("cls_shift.php");

// clsWorkTime : kelas untuk mengelola fungsi-fungsi yang terkait dengan periode kerja
class clsWorkTime
{

    var $data;    // objek database

    var $objDate; // objek tanggal

    // konstruktor
    function clsWorkTime($db)
    {
        $this->data = $db;
        $this->objDate = new clsCommonDate();
    }

    /* 
      getTotalWorkDay : fungsi untuk menghitung total workday, standard
      input : tanggal awal dan tanggal akhir, format SQL (YYYY-MM-DD)
      output : jumlah total hari kerja diantara tanggal awal dan akhir itu
    */
    function getTotalWorkDay($db, $strFrom, $strThru)
    {
        global $arrWorkDay; // untuk menampung total workday, datefrom-datethru, agar menghemat pencarian, jika sudah pernah ada
        $this->data = $db;
        if (isset($arrWorkDay["$strFrom.$strThru"])) {
            return $arrWorkDay["$strFrom.$strThru"];
        } // langsung dibalikin
        if ($strFrom == "" || $strThru == "") {
            return 0;
        }
        $intResult = 0;
        $strFrom = $this->objDate->getDateFormat($strFrom, "Y-m-d");
        $strThru = $this->objDate->getDateFormat($strThru, "Y-m-d");
        if ($strFrom > $strThru) {
            $intResult = 0;
        } else if ($strFrom == $strThru) {
            $intResult = 1;
        } else {
            // ubah format dalam timestamp
            list($intYear, $intMonth, $intDay) = explode("-", $strFrom);
            $tsFrom = mktime(0, 0, 0, (int)$intMonth, (int)$intDay, (int)$intYear);
            $dtFrom = getdate($tsFrom);
            list($intYear, $intMonth, $intDay) = explode("-", $strThru);
            $tsThru = mktime(0, 0, 0, (int)$intMonth, (int)$intDay, (int)$intYear);
            $dtThru = getdate($tsThru);
            $intSelisih = ($tsThru - $tsFrom) / 86400; // selisih dalam hari
            $intSelisihMinggu = floor($intSelisih / 7); // selisih dalam minggu
            $intResult = $intSelisih + 1; // karena hari awal dihitung juga
            $intModMinggu = ($intSelisih % 7);
            // dikurangi dengan hari minggu
            $intResult -= $intSelisihMinggu;
            if ($intModMinggu > 0) {
                if ($dtFrom['wday'] == 0 || $dtThru['wday'] == 0) {
                    $intResult--;
                } else if ($dtFrom['wday'] > $dtThru['wday']) {
                    $intResult--;
                }
            }
            // dikurangi hari sabtu jika, sabtu libur
            $bolSaturday = (getSetting("saturday") == 't');
            if ($bolSaturday) {
                $intResult -= $intSelisihMinggu;
                if ($intModMinggu > 0) {
                    if ($dtFrom['wday'] == 6 || $dtThru['wday'] == 6) {
                        $intResult--;
                    } else if ($dtFrom['wday'] > $dtThru['wday']) {
                        $intResult--;
                    }
                }
            }
            // cari data hari libur
            // selain hari minggu
            $strSQL = "SELECT COUNT(id) AS total FROM hrd_calendar ";
            $strSQL .= "WHERE holiday BETWEEN '$strFrom' AND '$strThru' ";
            $strSQL .= "AND EXTRACT(dow FROM holiday) <> 0 ";
            if ($bolSaturday) {
                $strSQL .= "AND EXTRACT(dow FROM holiday) <> 6 ";
            }
            $strSQL .= "AND status = 't' "; // cari yang libur saja
            $resDb = $this->data->execute($strSQL);
            if ($rowDb = $this->data->fetchrow($resDb)) {
                if (is_numeric($rowDb['total'])) {
                    $intResult -= $rowDb['total'];
                }
            }
            // cari data pengganti libur
            $strSQL = "SELECT COUNT(id) AS total FROM hrd_calendar ";
            $strSQL .= "WHERE holiday BETWEEN '$strFrom' AND '$strThru' ";
            $strSQL .= "AND status = 'f' "; // cari yang libur saja
            $resDb = $this->data->execute($strSQL);
            if ($rowDb = $this->data->fetchrow($resDb)) {
                if (is_numeric($rowDb['total'])) {
                    $intResult += $rowDb['total'];
                }
            }
        }
        // simpan ke var global
        $arrWorkDay["$strFrom.$strThru"] = round($intResult);
        return round($intResult);
    }

    /* isHoliday : fungsi untuk mencari info apakah suatu hari dianggap sebagai hari libur untuk karyawan tertentu (atau umum)
       input  : tanggal (YYYY-MM-DD), id employee (jika kosong, berarti dianggap info untuk perusahaan secara standard)
       output : true jika hari tersebut merupakan libur untuk karyawan terkait
    */
    function isHoliday($strDate, $strID = "")
    {
        $bolResult = false;
        if ($this->objDate->validDate($strDate)) {
            $bolFound = false;
            // cek dulu jika mencari data karyawan tertentu
            if ($strID != "") {
                $objS = new clsCommonShift($this->data);
                // cek apakah dia ada jadwal shift atau tidak
                //$strDate1 = $this->objDate->convertToSQL($strDate);
                if ($objS->isEmployeeShift($strID, $strDate, $strDate)) {
                    if ($objS->checkEmployeeShiftOff($strID, $strDate, $strDate)) {
                        $bolResult = true;
                    }
                    $bolFound = true;
                }
                unset($objS);
            }
            if (!$bolFound) // anggap tidak ada id employee, atau jika ada, karyawan tidak punya jadwal shift
            {
                $bolResult = $this->isPublicHoliday($strDate);
            }
        }
        return $bolResult;
    }

    /* isPublicHoliday : fungsi untuk mencari apakah hari tertentu merupakan hari libur secara umum, tanpa melihat jadwal shift
         public holiday terjadi jika: hari libur nasional,hari minggu, atau hari sabtu (jika di general setting sabtu dianggap libur)
       input  : tanggal (YYYY-MM-DD)
       output : true jika merupakan libur umum
    */
    function isPublicHoliday($strDate)
    {
        global $db;
        $bolResult = false;
        if (!$this->objDate->validDate($strDate)) {
            return false;
        }
        // cari hari dan tanggalnya
        $arrDt = $this->objDate->extractDate($strDate);
        //list($tahun,$bulan,$tanggal) = explode("-",$strDate);
        //$tsTanggal = mktime(0,0,0,$bulan,$tanggal,$tahun);
        $dtTanggal = getdate($arrDt['integer']);//getdate($tsTanggal);
        // cari di calendar
        $tbl = new cModel("hrd_calendar");
        if ($rowDb = $tbl->findByHoliday($strDate, "id, status")) {
            $bolResult = ($rowDb['status'] == 't'); // bisa saja hari libur, atau hari libur tapi dianggap masuk (pengganti)
        } else {
            // tidak ada catatann hari libur
            if ($dtTanggal['wday'] == 0) { // hari minggu, libur
                $bolResult = true;
            } else if ($dtTanggal['wday'] == 6) { // hari sabtu
                if (getSetting("saturday") == "t") {
                    $bolResult = true;
                }
            }
        }
        return $bolResult;
    }//isHoliday
}

?>