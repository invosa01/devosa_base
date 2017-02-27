<?php
/*
  KUMPULAN KELAS YANG TERKAIT DENGAN LEMBUR
  Update : 2009-01-23 (Yudi)
*/
include_once("../global/cls_date.php");
include_once("cls_worktime.php");
include_once("cls_shift.php");

/*
  KELAS UNTUK MENGAMBIL DATA LAPORAN LEMBUR
*/

class clsOvertimeReport
{

    var $arrData = []; // kelas database

        var $arrOvertimeType = []; // tanggal awal

    var $data; // tanggal akhir

    var $intTotalDay;   // total durasi hari antara tanggal awal dan akhir, bukan hanya hari kerja

    var $intTotalWorkDay;// total durasi hari kerja antara tanggal awal dan akhir, secara standard, bukan per karyawan

    var $objShift; // id dari karyawan, jika ada atau khusus untuk 1 karyawan saja

    var $objWork; // khusus untuk filter pengambilan data karyawan

    // format query: "AND xxxx"

var $strEmployeeFilter; // data disimpan dalam array saja

    // index adalah id_employee

var $strFinishDate;

    var $strIDEmployee;   // objek untuk fasilitas perhitungan waktu kerja - cls_worktime.php

    var $strStartDate;  // objek untuk ambil info terkait shift - cls_shift.php

    // konstruktor

    function clsOvertimeReport($db, $strStartDate, $strFinishDate, $strIDEmployee = "", $strEmployeeFilter = "")
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
        $this->getOvertimeType();
        $this->intTotalDay = $objDt->getIntervalDate($this->strStartDate, $this->strFinishDate) + 1;
        $this->intTotalWorkDay = $this->objWork->getTotalWorkDay($strStartDate, $strFinishDate);
        unset($objDt);
    }

    /* getAbsenceType : fungsi untuk mengambil daftar jenis cuti (private)
    */

    function generateOvertimeReport($fltShiftHour = 0)
    {
        // hitung total dulu semua
        $strSQL = "
          SELECT COUNT(t1.id) AS total_ot_day, t1.id_employee,
            SUM(l1) AS total_ot_1, SUM(l2) AS total_ot_2, SUM(l2b) AS total_ot_2b,
            SUM(l3) AS total_ot_3, SUM(l4) AS total_ot_4,
            SUM(CASE WHEN
              overtime_finish_early_auto - overtime_start_early_auto >= interval '1 hour' AND
              overtime_start_early <= overtime_start_early_auto AND
              overtime_finish_early >= overtime_finish_early_auto AND
              overtime_start_early_auto IS NOT NULL AND
              overtime_finish_early_auto IS NOT NULL AND
              overtime_start_early IS NOT NULL AND
              overtime_finish_early IS NOT NULL
            THEN 1 ELSE 0 END) AS early_auto_day
      ";
        // cari data shift siang dan malam
        $strSQL .= "FROM (
          SELECT * FROM hrd_attendance
          WHERE attendance_date BETWEEN '" . $this->strStartDate . "'
              AND '" . $this->strFinishDate . "'
            AND (attendance_start is not null OR attendance_finish is not null)
            AND (is_absence != 't')
        ) AS t1 ";
        if ($this->strEmployeeFilter != "") {
            $strSQL .= " WHERE id_employee IN (
           SELECT id FROM hrd_employee WHERE 1=1 " . $this->strEmployeeFilter . ") ";
        }
        $strSQL .= "GROUP BY t1.id_employee ";
        $resDb = $this->data->execute($strSQL);
        while ($rowDb = $this->data->fetchrow($resDb)) {
            $strID = $rowDb['id_employee'];
            if (!isset($this->arrData[$strID])) {
                $this->initData($strID);
            }
            foreach ($this->arrData[$strID] as $strCode => $strValue) {
                if ($strCode != "total_ot_all" && $strCode != "total_ot_min") {
                    $this->arrData[$strID][$strCode] = $rowDb[$strCode];
                }
            }
            $fltTotal = $fltTotalOTAll = 0;
            for ($i = 1; $i <= 4; $i++) {
                if (isset($this->arrOvertimeType['L' . $i])) {
                    $fltTotalOTAll += $this->arrData[$strID]['total_ot_' . $i];
                    $fltTotal += ($this->arrOvertimeType['L' . $i] * $this->arrData[$strID]['total_ot_' . $i]);
                }
            }
            $fltTotalOTAll += $this->arrData[$strID]['total_ot_2b'];
            $fltTotal += ($this->arrOvertimeType['L2b'] * $this->arrData[$strID]['total_ot_2b']);
            $this->arrData[$strID]['total_ot_all'] = $fltTotalOTAll;
            $this->arrData[$strID]['total_ot_min'] = $fltTotal;
        }
        // echo $strSQL;
        // die();
    }

    /* initData : fungsi untuk melakukan inisialisasi data per karyawan, data diisi dengan default 0
    */

    function generateOvertimeSalaryReport($strSalaryDate)
    {
        $strSalaryDate = (validStandardDate($strSalaryDate)) ? $strSalaryDate : $this->strFinishDate;
        // hitung total dulu semua
        $strSQL = "
          SELECT COUNT(is_overtime) AS total_ot_day, t1.id_employee,
            SUM(l1) AS total_ot_1, SUM(l2) AS total_ot_2, SUM(l2b) AS total_ot_2b,
            SUM(l3) AS total_ot_3, SUM(l4) AS total_ot_4,
            SUM(CASE WHEN holiday = 1 AND (overtime_finish - overtime_start >= interval '4 hours' OR (overtime_finish - overtime_start < interval '0 hours' AND overtime_finish - overtime_start + interval '24 hours' >= interval '4 hours')) THEN 1 ELSE 0 END) AS ot_hl_gt4h,
            SUM(CASE WHEN (overtime_finish - overtime_start >= interval '3 hours' OR (overtime_finish - overtime_start < interval '0 hours' AND overtime_finish - overtime_start + interval '24 hours' >= interval '3 hours')) THEN 1 ELSE 0 END) AS ot_meal_counter,

            SUM(CASE WHEN auto_overtime = 't' THEN 1 ELSE 0 END) AS early_auto_day
      ";
        // cari data shift siang dan malam
        $strSQL .= "FROM
      (
          SELECT ta.* FROM hrd_attendance AS ta LEFT JOIN
          (hrd_overtime_application_employee AS toe LEFT JOIN hrd_overtime_application as toa ON toa.id = toe.id_application)
          ON ta.attendance_date = toe.overtime_date AND ta.id_employee = toe.id_employee
          WHERE
          (
            ((attendance_date BETWEEN '" . $this->strStartDate . "' AND '" . $this->strFinishDate . "') AND (toa.is_outdated = 'f' OR toa.is_outdated IS NULL))
            OR
            (toa.is_outdated = 't' AND salary_month = EXTRACT(MONTH FROM DATE '" . $strSalaryDate . "') AND salary_year = EXTRACT(YEAR FROM DATE '" . $strSalaryDate . "'))
          )
          AND (toe.status >= " . REQUEST_STATUS_APPROVED . ")
          AND (attendance_start is not null OR attendance_finish is not null)
          AND (is_overtime = 't')
        UNION
          SELECT ta2.* FROM hrd_attendance AS ta2
          LEFT JOIN (hrd_absence_detail AS tad2 LEFT JOIN hrd_absence_type AS tat2 ON tad2.absence_type =  tat2.code)
          ON ta2.attendance_date = tad2.absence_date AND ta2.id_employee = tad2.id_employee
          WHERE
          (attendance_date BETWEEN '" . $this->strStartDate . "' AND '" . $this->strFinishDate . "')
          AND attendance_date NOT IN
            (SELECT overtime_date FROM hrd_overtime_application_employee AS toe2 WHERE toe2.id_employee = ta2.id_employee)
          AND (attendance_start is not null OR attendance_finish is not null)
          AND (is_overtime = 't')
          AND (auto_overtime = 't')
        ) AS t1
        ";
        if ($this->strEmployeeFilter != "") {
            $strSQL .= " WHERE id_employee IN (
           SELECT id FROM hrd_employee WHERE 1=1 " . $this->strEmployeeFilter . ") ";
        }
        $strSQL .= "GROUP BY t1.id_employee";
        // die($strSQL);
        $resDb = $this->data->execute($strSQL);
        while ($rowDb = $this->data->fetchrow($resDb)) {
            $strID = $rowDb['id_employee'];
            if (!isset($this->arrData[$strID])) {
                $this->initData($strID);
            }
            foreach ($this->arrData[$strID] as $strCode => $strValue) {
                if ($strCode != "total_ot_all" && $strCode != "total_ot_min") {
                    $this->arrData[$strID][$strCode] = $rowDb[$strCode];
                }
            }
            $fltTotal = $fltTotalOTAll = 0;
            for ($i = 1; $i <= 4; $i++) {
                if (isset($this->arrOvertimeType['L' . $i])) {
                    $fltTotalOTAll += $this->arrData[$strID]['total_ot_' . $i];
                    $fltTotal += ($this->arrOvertimeType['L' . $i] * $this->arrData[$strID]['total_ot_' . $i]);
                }
            }
            $fltTotalOTAll += $this->arrData[$strID]['total_ot_2b'];
            $fltTotal += ($this->arrOvertimeType['L2b'] * $this->arrData[$strID]['total_ot_2b']);
            $this->arrData[$strID]['total_ot_all'] = $fltTotalOTAll;
            $this->arrData[$strID]['total_ot_min'] = $fltTotal;
        }
    }

    // Menambahkan total durasi excess monthly working hour

    function getData($strIDEmployee, $strField = "")
    {
        $strResult = 0;
        if ($strField == "") {
            if (isset($this->arrData[$strIDEmployee])) {
                $arrResult = $this->arrData[$strIDEmployee];
            }
            return $arrResult;
        } else {
            if (isset($this->arrData[$strIDEmployee][$strField])) {
                $strResult = $this->arrData[$strIDEmployee][$strField];
            }
            return $strResult;
        }
    }

    /* generateOvertimeReport : fungsi untuk mengambil informasi/laporan lembur
        ambil dari data kehadiran
    */

    function getDataAllowance($strIDEmployee, $intLevel, $fltSalary, $arrFilteredOT = "")
    {
        $fltResult = 0;
        if ($arrFilteredOT == "") {
            if (isset($this->arrData[$strIDEmployee]['total_ot_' . $intLevel]) && isset($this->arrOvertimeType['L' . $intLevel])) {
                $fltResult = ($this->arrData[$strIDEmployee]['total_ot_' . $intLevel] / 60) * $this->arrOvertimeType['L' . $intLevel] * $fltSalary;
            }
        } else if (isset($arrFilteredOT['total_ot_' . $intLevel]) && isset($this->arrOvertimeType['L' . $intLevel])) {
            $fltResult = ($arrFilteredOT['total_ot_' . $intLevel] / 60) * $this->arrOvertimeType['L' . $intLevel] * $fltSalary;
        }
        return $fltResult;
    }

    /*
      getData : fungsi untuk mengambil data dari karyawan tertentu, berdasar data yang ada di arrData
      input : id karyawan, field (kode index). untuk absen atau shift dengan kode tertentu, gunakan fungsi lain
      output: nilai yang ada dalam arrData sesuai id dan field
    */

    function getOvertimeType()
    {
        $strSQL = "
        SELECT code, scale
        FROM hrd_overtime_type
      ";
        $res = $this->data->execute($strSQL);
        while ($row = $this->data->fetchrow($res)) {
            $this->arrOvertimeType[$row['code']] = $row['scale'];
        }
        if (count($this->arrOvertimeType) == 0) {
            //$this->arrOvertimeType = array("L1" => 1.5, "L2" => 2,"L2b" => 2.5, "L3" => 3, "L4" => 4);
            $this->arrOvertimeType = ["L1" => 1.5, "L2" => 2, "L2b" => 2, "L3" => 1.5, "L4" => 2];
        };
    }

    /*
      getDataAllowance : fungsi untuk mengambil data tunjangan lembur, sesuai jumlah lembur dan gaji pokok per jam
      input : id karyawan, level (1, 2, 3, 4 -> untuk OT1, OT2, OT3, OT4), gaji pokok per jam
      output: nilai yang ada dalam arrData sesuai id dan field
    */

    function initData($strIDEmployee)
    {
        if (isset($this->arrData[$strIDEmployee])) {
            unset($this->arrData[$strIDEmployee]);
        }
        $this->arrData[$strIDEmployee] = [
            "total_ot_day"    => 0, // total overtime, dalam hari
            "total_ot_1"      => 0, // total ot, L1 dalam menit
            "total_ot_2"      => 0, // total ot, L2 dalam menit
            "total_ot_2b"     => 0, // total ot, L2b dalam menit
            "total_ot_3"      => 0, // total ot, L3 dalam menit
            "total_ot_4"      => 0, // total ot, L4 dalam menit
            "total_ot_all"    => 0, // total ot, dalam menit, sebelum dikonversikan
            "total_ot_min"    => 0, // total ot, dalam menit, setelah dikonversikan dengan faktor pengali masing-masing
            "early_auto_day"  => 0, // total early auto ot, dalam hari.
            "ot_hl_gt4h"      => 0, // total early auto ot, dalam hari.
            "ot_meal_counter" => 0, // total early auto ot, dalam hari.
        ];
    }

    function limitOvertime($strIDEmployee, $fltOTLimit)
    {
        $fltInitialOT = 0;
        $arrOTType = $this->arrOvertimeType;
        $strDateFrom = $this->strStartDate;
        $strDateThru = $this->strFinishDate;
        //jika total OT min lebih dari 30 jam, loop untuk cari 30 jam pertama
        $fltTotalOT = $fltInitialOT;
        $fltTotalOT1 = $fltInitialOT;
        $fltTotalOT2 = $fltInitialOT;
        $fltTotalOT2b = $fltInitialOT;
        $fltTotalOT3 = $fltInitialOT;
        $fltTotalOT4 = $fltInitialOT;
        $fltTotalOTUntilYesterday = $fltInitialOT;
        $fltTotalOT1UntilYesterday = $fltInitialOT;
        $fltTotalOT2UntilYesterday = $fltInitialOT;
        $fltTotalOT2bUntilYesterday = $fltInitialOT;
        $fltTotalOT3UntilYesterday = $fltInitialOT;
        $fltTotalOT4UntilYesterday = $fltInitialOT;
        $strDateTemp = $strDateFrom;
        $arrOTGroup1 = []; // array untuk OT yang dihitung normal
        $arrOTGroup2 = []; // array untuk OT selebihnya
        while (dateCompare($strDateTemp, $strDateThru) <= 0) {
            //assign OT untuk date temp
            $objTodayOT = new clsOvertimeReport(
                $this->data, $strDateTemp, $strDateTemp, $strIDEmployee
            ); // cls_ambil data summary ot utk 1 hari
            $objTodayOT->generateOvertimeReport();
            if (!isset($objTodayOT->arrData[$strIDEmployee])) {
                continue;
            }
            $arrTodayOT[$strIDEmployee] = $objTodayOT->arrData[$strIDEmployee];
            $fltTodayL1Min = $arrTodayOT[$strIDEmployee]['total_ot_1'];
            $fltTodayL2Min = $arrTodayOT[$strIDEmployee]['total_ot_2'];
            $fltTodayL2bMin = $arrTodayOT[$strIDEmployee]['total_ot_2b'];
            $fltTodayL3Min = $arrTodayOT[$strIDEmployee]['total_ot_3'];
            $fltTodayL4Min = $arrTodayOT[$strIDEmployee]['total_ot_4'];
            $fltTodayOTMin = $fltTodayL1Min + $fltTodayL2Min + $fltTodayL2bMin + $fltTodayL3Min + $fltTodayL4Min;
            $fltTotalOTUntilYesterday = $fltTotalOT;
            $fltTotalOT1UntilYesterday = $fltTotalOT1;
            $fltTotalOT2UntilYesterday = $fltTotalOT2;
            $fltTotalOT2bUntilYesterday = $fltTotalOT2b;
            $fltTotalOT3UntilYesterday = $fltTotalOT3;
            $fltTotalOT4UntilYesterday = $fltTotalOT4;
            $fltTotalOT += $fltTodayOTMin;
            $fltTotalOT1 += $fltTodayL1Min;
            $fltTotalOT2 += $fltTodayL2Min;
            $fltTotalOT2b += $fltTodayL2bMin;
            $fltTotalOT3 += $fltTodayL3Min;
            $fltTotalOT4 += $fltTodayL4Min;
            if ($fltTotalOT < $fltOTLimit) {
                //belum sampai batas overtime maksimal
                //masukkan nilai ot hari ini ke group1
                //null ke group2
                $arrOTGroup1[$strDateTemp] = $arrTodayOT;
            } else if ($fltTotalOTUntilYesterday >= $fltOTLimit) {
                //sudah lebih dari batas overtime maksimal jam
                //masukkan nilai ot hari ini ke group2
                //null ke group1
                $arrOTGroup2[$strDateTemp] = $arrTodayOT;
            } else {
                //perpotongan jam pada batas overtime maksimal
                //masukkan bagian batas bawah ke group1
                //selebihnya ke group2
                $fltDifference = $fltOTLimit - $fltTotalOTUntilYesterday;
                //cek letak batas durasi ot reguler dan sisanya di L1, L2, L3, atau L4
                if ($fltTodayL1Min <= $fltDifference) {
                    //durasi l1 masih termasuk kelompok ot batas bawah
                    //assign semua ke group 1 L1
                    //assign 0 ke group 2 L1
                    $arrOTGroup1[$strDateTemp][$strIDEmployee]['total_ot_1'] = $arrTodayOT[$strIDEmployee]['total_ot_1'];
                    $arrOTGroup2[$strDateTemp][$strIDEmployee]['total_ot_1'] = 0;
                    if ($fltTodayL2Min <= ($fltDifference - $fltTodayL1Min)) {
                        //durasi l2 masih termasuk kelompok batas bawah
                        //assign semua ke group 1 L2
                        //assign 0 ke group 2 L2
                        $arrOTGroup1[$strDateTemp][$strIDEmployee]['total_ot_2'] = $arrTodayOT[$strIDEmployee]['total_ot_2'];
                        $arrOTGroup2[$strDateTemp][$strIDEmployee]['total_ot_2'] = 0;
                        if ($fltTodayL3Min <= ($fltDifference - $fltTodayL1Min - $fltTodayL2Min)) {
                            //durasi l3 masih termasuk kelompok batas bawah
                            //assign semua ke group 1 L3
                            //assign 0 ke group 2 L3
                            $arrOTGroup1[$strDateTemp][$strIDEmployee]['total_ot_3'] = $arrTodayOT[$strIDEmployee]['total_ot_3'];
                            $arrOTGroup2[$strDateTemp][$strIDEmployee]['total_ot_3'] = 0;
                            if ($fltTodayL4Min <= ($fltDifference - $fltTodayL1Min - $fltTodayL2Min - $fltTodayL3Min)) {
                                //durasi l4 masih termasuk kelompok batas bawah
                                //assign semua ke group 1 L4
                                //assign 0 ke group 2 L4
                                $arrOTGroup1[$strDateTemp][$strIDEmployee]['total_ot_4'] = $arrTodayOT[$strIDEmployee]['total_ot_4'];
                                $arrOTGroup2[$strDateTemp][$strIDEmployee]['total_ot_4'] = 0;
                            } else {
                                //assign sebesar selisih ke group 1 L4
                                //assign sisanya ke group 2 L4
                                $arrOTGroup1[$strDateTemp][$strIDEmployee]['total_ot_4'] = ($fltDifference - $fltTodayL1Min - $fltTodayL2Min - $fltTodayL3Min);
                                $arrOTGroup2[$strDateTemp][$strIDEmployee]['total_ot_4'] = $arrTodayOT[$strIDEmployee]['total_ot_4'] - ($fltDifference - $fltTodayL1Min - $fltTodayL2Min - $fltTodayL3Min);
                            }
                        } else {
                            //assign sebesar selisih ke group 1 L3
                            //assign sisanya ke group 2 L3
                            $arrOTGroup1[$strDateTemp][$strIDEmployee]['total_ot_3'] = ($fltDifference - $fltTodayL1Min - $fltTodayL2Min);
                            $arrOTGroup2[$strDateTemp][$strIDEmployee]['total_ot_3'] = $arrTodayOT[$strIDEmployee]['total_ot_3'] - ($fltDifference - $fltTodayL1Min - $fltTodayL2Min);
                        }
                    } else {
                        //assign sebesar selisih ke group 1 L2
                        //assign sisanya ke group 2 L2
                        $arrOTGroup1[$strDateTemp][$strIDEmployee]['total_ot_2'] = ($fltDifference - $fltTodayL1Min);
                        $arrOTGroup2[$strDateTemp][$strIDEmployee]['total_ot_2'] = $arrTodayOT[$strIDEmployee]['total_ot_2'] - ($fltDifference - $fltTodayL1Min);
                    }
                } else {
                    //assign sebesar selisih ke group 1 L1
                    //assign sisanya ke group 2 L1
                    $arrOTGroup1[$strDateTemp][$strIDEmployee]['total_ot_1'] = $fltDifference;
                    $arrOTGroup2[$strDateTemp][$strIDEmployee]['total_ot_1'] = $arrTodayOT[$strIDEmployee]['total_ot_1'] - $fltDifference;
                    $arrOTGroup2[$strDateTemp][$strIDEmployee]['total_ot_2'] = $arrTodayOT[$strIDEmployee]['total_ot_2'];
                    $arrOTGroup2[$strDateTemp][$strIDEmployee]['total_ot_2b'] = $arrTodayOT[$strIDEmployee]['total_ot_2b'];
                    $arrOTGroup2[$strDateTemp][$strIDEmployee]['total_ot_3'] = $arrTodayOT[$strIDEmployee]['total_ot_3'];
                    $arrOTGroup2[$strDateTemp][$strIDEmployee]['total_ot_4'] = $arrTodayOT[$strIDEmployee]['total_ot_4'];
                }
            }
            $strDateTemp = getNextDate($strDateTemp);
        }
        //hitung group1 : kelompok batas bawah -------------------------------------------------------------
        //$fltL1Min = $fltL2Min = $fltL3Min = $fltL4Min = 0;
        if (isset($arrOTGroup1)) {
            $this->arrData[$strIDEmployee]['total_ot_1'] =
            $this->arrData[$strIDEmployee]['total_ot_2'] =
            $this->arrData[$strIDEmployee]['total_ot_2b'] =
            $this->arrData[$strIDEmployee]['total_ot_3'] =
            $this->arrData[$strIDEmployee]['total_ot_4'] =
            $this->arrData[$strIDEmployee]['total_ot_all'] =
            $this->arrData[$strIDEmployee]['total_ot_min'] = 0;
            foreach ($arrOTGroup1 as $strDate => $arrDailyOT) {
                $fltTotal = $fltTotalOTAll = 0;
                for ($i = 1; $i <= 4; $i++) {
                    if (isset($this->arrOvertimeType['L' . $i])) {
                        $this->arrData[$strIDEmployee]['total_ot_' . $i] += isset($arrDailyOT[$strIDEmployee]['total_ot_' . $i]) ? $arrDailyOT[$strIDEmployee]['total_ot_' . $i] : 0;
                        $fltTotalOTAll += $this->arrData[$strIDEmployee]['total_ot_' . $i];
                        $fltTotal += ($this->arrOvertimeType['L' . $i] * $this->arrData[$strIDEmployee]['total_ot_' . $i]);
                    }
                }
                $this->arrData[$strIDEmployee]['total_ot_2b'] += isset($arrDailyOT[$strIDEmployee]['total_ot_2b']) ? $arrDailyOT[$strIDEmployee]['total_ot_2b'] : 0;
                $fltTotalOTAll += $this->arrData[$strIDEmployee]['total_ot_2b'];
                $fltTotal += ($this->arrOvertimeType['L2b'] * $this->arrData[$strIDEmployee]['total_ot_2b']);
                $this->arrData[$strIDEmployee]['total_ot_all'] = $fltTotalOTAll;
                $this->arrData[$strIDEmployee]['total_ot_min'] = $fltTotal;
            }
        }
        //print_r ($this->arrData[$strIDEmployee]);
        //-----------------------------------------------------------------------------------------------
        //hitung group2 : selebihnya---------------------------------------------------------------------
        //total durasi spv ot dalam menit
        //di takaful tidak digunakan, pending dulu
        $fltOTMinSpv = 0;
        if (isset($arrOTGroup2)) {
            foreach ($arrOTGroup2 as $strDate => $arrDailyOT) {
                $fltOTMinSpv += isset($arrDailyOT[$strIDEmployee]['total1']) ? $arrDailyOT[$strIDEmployee]['total1'] : 0;
                $fltOTMinSpv += isset($arrDailyOT[$strIDEmployee]['total2']) ? $arrDailyOT[$strIDEmployee]['total2'] : 0;
                $fltOTMinSpv += isset($arrDailyOT[$strIDEmployee]['total2b']) ? $arrDailyOT[$strIDEmployee]['total2b'] : 0;
                $fltOTMinSpv += isset($arrDailyOT[$strIDEmployee]['total3']) ? $arrDailyOT[$strIDEmployee]['total3'] : 0;
                $fltOTMinSpv += isset($arrDailyOT[$strIDEmployee]['total4']) ? $arrDailyOT[$strIDEmployee]['total4'] : 0;
            }
            //besar tunjangan lembur spv
            $fltOvertimeSpv = $fltOTMinSpv / 60 * $fltHalfOTRate;
        } else {
            $fltOTMinSpv = $fltOvertimeSpv = 0;
        }
        //----------------------------------------------------------------------------------------------
        $arrResult['fltOTMin'] = $fltOTMin;
        $arrResult['fltOTXMin'] = $fltOTXMin;
        $arrResult['fltOvertime'] = $fltOvertime;
        $arrResult['fltOTMinSpv'] = $fltOTMinSpv;
        $arrResult['fltOvertimeSpv'] = $fltOvertimeSpv;
        return $arrResult;
    }

    /* generateOvertimeReport : fungsi untuk mengambil informasi/laporan lembur untuk salary calculation
        ambil dari data kehadiran
        - ambil data overtime sesuai cut off periode overtime dan sudah di approve
        - ambil data outdated overtime (overtime diluar cut off peride overtime tapi termasuk dalam perhitungan karena baru dibuat spl nya)
    */

    function setExcessOT($strIDEmployee, $fltExcessOT)
    {
        $fltExcessOT2 = $fltExcessOT - HOURTOMIN;
        if ($fltExcessOT2 > 0) {
            $this->arrData[$strIDEmployee]['total_ot_1'] += HOURTOMIN;
            $this->arrData[$strIDEmployee]['total_ot_2'] += $fltExcessOT2;
            $this->arrData[$strIDEmployee]['total_ot_min'] += (($this->arrOvertimeType['L1'] * HOURTOMIN) + ($this->arrOvertimeType['L2'] * $fltExcessOT2));
        } else {
            $this->arrData[$strIDEmployee]['total_ot_1'] += $fltExcessOT;
            $this->arrData[$strIDEmployee]['total_ot_min'] += ($this->arrOvertimeType['L1'] * $fltExcessOT);
        }
        $this->arrData[$strIDEmployee]['total_ot_all'] += $fltExcessOT;
    }
}

?>
