<?php
/* Fungsi-fungsi khusus untuk perhitungan lembur
  By, Yudi K.
  */
// Variabel umum:
// $db -> variabel untuk kelas CDbClass, koneksi database
// $strIDEmployee --> ID dari employee (long)
// mengetahui apakah karyawan tertentu sedang shift atau tidak,pada tanggal tertentu
// mengembalikan start dan finish shift, jika ditemukan
function isShift($db, $strIDEmployee, $strDate, &$strStartTime = "", &$strFinishTime = "")
{
    $bolResult = false;
    if ($strIDEmployee == "" || $strDate == "") {
        return false;
    }
    //tambahan untuk melihat tipe groupnya
    //dw
    $intGroupType = getSetting("grouping");
    // cari di schedule shift untuk perorangan
    $strSQL = "SELECT * FROM hrd_shift_schedule_employee  ";
    $strSQL .= "WHERE id_employee = '$strIDEmployee' AND shift_date = '$strDate' AND group_type = '" . $intGroupType . "'";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        // ada jadwal shift perorangan
        $strStartTime = $rowDb['start_time'];
        $strFinishTime = $rowDb['finish_time'];
        return true;
    }
    return $bolResult;
}//isShift
/* Fungsi untuk mengambil informasi shift type, berguna untuk mengecek ada
   additional_ot atau tidak, input sama dengan pengecekan isShift function
*/
function getShiftTypeData($db, $strIDEmployee, $strDate)
{
    if ($strIDEmployee == "" || $strDate == "") {
        return null;
    }
    //tambahan untuk melihat tipe groupnya
    //dw
    $intGroupType = getSetting("grouping");
    // cari di schedule shift untuk perorangan
    $strSQL = "SELECT b.start_time, b.finish_time, b.additional_ot FROM hrd_shift_schedule_employee AS a, hrd_shift_type AS b  ";
    $strSQL .= "WHERE a.shift_code = b.code AND a.id_employee = '$strIDEmployee' AND a.shift_date = '$strDate' AND a.group_type = '" . $intGroupType . "'";
    $resDb = $db->execute($strSQL);
    $shiftype = null;
    if ($rowDb = $db->fetchrow($resDb)) {
        $shiftype = $rowDb;
    } else {
        // cari jadwal shift per group
        $strSQL = "SELECT t1.*,t4.start_time, t4.finish_time, t4.additional_ot FROM hrd_shift_schedule_group AS t1, ";
        $strSQL .= "hrd_shift_group AS t2, hrd_shift_group_member AS t3, hrd_shift_type AS t4";
        $strSQL .= "WHERE t1.shift_code = t4.code AND t1.id_group = t2.id AND t3.id_group = t2.id ";
        $strSQL .= "AND t3.id_employee = '$strIDEmployee' AND t1.shift_date = '$strDate' AND t1.group_type = '0'";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $shiftype = $rowDb;
        }
    }
    return $shiftype;
}//isShift
// fungsi untuk menghitung total jam kerja, dari jam awal sampai jam akhir, dalam menit, setelah dkurangi waktu istirahat
// input: kelas DB,  tanggal, jam awal dan jam akhir
// $intTipe = 0 = hari biasa, 1=jumat, 2 = libur
function getTotalWorkingHour(
    $db,
    $strDate,
    $strStart,
    $strFinish,
    $intBreakFlag = "",
    $intBreakLinkID = "",
    $intTipe = 0
) {
    $intResult = 0;
    if (substr($strFinish, 0, 5) == "00:00") { // untuk finish = 00, ada perlakukan khusus
        $intResult += getIntervalHour($strStart, "24:00:00");
        // cari waktu istirahat malam
        $currFinish = "23:59:59";
        $strSQL = "SELECT * FROM hrd_break_time WHERE start_time >= '$strStart' ";
        $strSQL .= "AND start_time < '$currFinish' AND type = $intTipe ";
        if ($intBreakFlag != "" && $intBreakLinkID != "") {
            $strSQL .= "AND flag = $intBreakFlag AND link_id = $intBreakLinkID ";
        }
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $currTime = getNextMinute($rowDb['start_time'], $rowDb['duration']);
            if ($currTime > $currFinish) {
                $intResult -= getIntervalHour($rowDb['start_time'], $currFinish); //dikurangi selisihnya
            } else {
                $intResult -= $rowDb['duration'];
            }
        }
    } else if ($strStart > $strFinish) { // melewati tengah malam
        $intResult += getIntervalHour($strStart, "24:00:00");
        $intResult += getIntervalHour("00:00:00", $strFinish);
        // cari waktu istirahat malam
        $currFinish = "23:59:59";
        $strSQL = "SELECT * FROM hrd_break_time WHERE start_time >= '$strStart' ";
        $strSQL .= "AND start_time < '$currFinish' AND type = $intTipe ";
        if ($intBreakFlag != "" && $intBreakLinkID != "") {
            $strSQL .= "AND flag = $intBreakFlag AND link_id = $intBreakLinkID ";
        }
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            //$intResult += getIntervalHour($currTime,$rowDb['startTime']);
            $currTime = getNextMinute($rowDb['start_time'], $rowDb['duration']);
            if ($currTime > $currFinish) {
                $intResult -= getIntervalHour($rowDb['start_time'], $currFinish); //dikurangi selisihnya
            } else {
                $intResult -= $rowDb['duration'];
            }
        }
        // cari waktu istirahat pagi
        $currStart = "00:00:00";
        $strSQL = "SELECT * FROM hrd_break_time WHERE start_time >= '$currStart' ";
        $strSQL .= "AND start_time < '$strFinish' AND type = $intTipe ";
        if ($intBreakFlag != "" && $intBreakLinkID != "") {
            $strSQL .= "AND flag = $intBreakFlag AND link_id = $intBreakLinkID ";
        }
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $currTime = getNextMinute($rowDb['start_time'], $rowDb['duration']);
            if ($currTime > $strFinish) {
                $intResult -= getIntervalHour($rowDb['start_time'], $strFinish); //dikurangi selisihnya
            } else {
                $intResult -= $rowDb['duration'];
            }
        }
    } else {
        // proses biasa
        // -- Cari Total Menit Dulu
        $intResult = getIntervalHour($strStart, $strFinish);
        // cari waktu istirahat
        $strSQL = "SELECT * FROM hrd_break_time WHERE start_time >= '$strStart' ";
        $strSQL .= "AND start_time < '$strFinish' AND type = $intTipe ";
        if ($intBreakFlag != "" && $intBreakLinkID != "") {
            $strSQL .= "AND flag = $intBreakFlag AND link_id = $intBreakLinkID ";
        }
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $currTime = getNextMinute($rowDb['start_time'], $rowDb['duration']);
            if ($currTime > $strFinish) {
                $intResult -= getIntervalHour($rowDb['start_time'], $strFinish); //dikurangi selisihnya
            } else {
                $intResult -= $rowDb['duration'];
            }
        }
    }
    return $intResult;
}//getTotalWorkingHour
// fungsi untuk mengupdate data lembur yang ada -- aplikasi lembur
// input: db, idEmployee, tanggal, jam masuk, jam keluar
function updateOvertime($db, $strIDEmployee, $strDate, $strStart, $strFinish, $bolHoliday = false)
{
    // cari apakah ada OVERTIME APPLICATION yang sudah approved
    // jika ada, simpan data lembur
    $strSQL = "SELECT t1.id, t1.start_plan, t1.finish_plan FROM hrd_overtime_application_employee AS t1, ";
    $strSQL .= "hrd_overtime_application AS t2 WHERE t1.id_application = t2.id ";
    $strSQL .= "AND t1.id_employee = '$strIDEmployee' AND t2.overtime_date = '$strDate' AND t1.status=2";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
        // cek dulu , hari libur atau bukan
        if ($bolHoliday) {
            $arrLemburApp = calculateOvertime(
                $db,
                $strDate,
                $rowTmp['start_plan'],
                $rowTmp['finish_plan'],
                $strStart,
                $strFinish
            );
            $strTmpStart = $strStart;
            $strTmpFinish = $strFinish;
        } else {
            $arrLemburApp = calculateOvertime($db, $strDate, $strStart, $rowTmp['start_plan'], $strStart, $strFinish);
            $strTmpStart = $rowTmp['start_plan'];
            $strTmpFinish = $strFinish;
        }
        // update data di aplikasi lemburnya
        ($strTmpStart == "") ? $strTmpStart = "NULL" : $strTmpStart = "'$strTmpStart'";
        ($strTmpFinish == "") ? $strTmpFinish = "NULL" : $strTmpFinish = "'$strTmpFinish'";
        $strSQL = "UPDATE hrd_overtime_application_employee ";
        $strSQL .= "SET start_actual = $strTmpStart, finish_actual = $strTmpFinish,  ";
        $strSQL .= "l1 = '" . $arrLemburApp['l1'] . "', l2 = '" . $arrLemburApp['l2'] . "', ";
        $strSQL .= "l3 = '" . $arrLemburApp['l3'] . "', l4 = '" . $arrLemburApp['l4'] . "', ";
        $strSQL .= "total_time = '" . $arrLemburApp['total'] . "' ";
        $strSQL .= "WHERE id = " . $rowTmp['id'];
        $resExec = $db->execute($strSQL);
    }
    return 0;
}// updateOvertime
function getOvertimeFinish(
    $strNormalStart,
    $strNormalFinish,
    $strAttendanceStart,
    $strAttendanceFinish,
    $strOvertimeStart,
    $strOvertimeFinish
) {
    $bolOT = false;
    if ($strAttendanceFinish == $strOvertimeStart) {
        // nothing
        $bolOT = false;
    } else if ($strAttendanceFinish < $strOvertimeStart) {
        // lebih awal
        if ($strNormalStart <= $strOvertimeStart) {
            // masuk siang
            if ($strAttendanceFinish > $strNormalStart) {
                // pulang awal
                $bolOT = false;
            } else {
                $bolOT = true;
                // bisa dianggap lembur, lewat tengah malam
                if (timeCompare($strOvertimeFinish, $strAttendanceFinish) > 0) {
                    $strOvertimeFinish = $strAttendanceFinish;
                }
            }
        } else {
            // masuk malam
            $bolOT = false; // pulang awal
        }
    } else if ($strAttendanceFinish > $strOvertimeStart) {
        // lebih lama
        if ($strNormalStart <= $strOvertimeStart) {
            // masuk siang
            $bolOT = true;
            if (timeCompare($strAttendanceFinish, $strOvertimeFinish) < 0) {
                $strOvertimeFinish = $strAttendanceFinish;
            }
        } else {
            // masuk malam
            if ($strAttendanceFinish < $strNormalStart) {
                // dianggap lembur
                $bolOT = true;
                if (timeCompare($strAttendanceFinish, $strOvertimeFinish) < 0) {
                    $strOvertimeFinish = $strAttendanceFinish;
                }
            } else {
                // dianggap pulang awal, karena pulang sebelum tengah malam
                $bolOT = false;
            }
        }
    }
    if (!$bolOT) {
        $strOvertimeFinish = "";
    }
    return $strOvertimeFinish;
}

// hitung total overtime dalam hari tertentu, berdasar data kehadiran, dalam menit
// UNTUK PERHITUNGAN NORMAL, UNTUK DATA KEHADIRAN AJA
// output berupa array: (l1,l2,l3,l4,total), dalam menit, juga jumlah keterlambatan dan pulang awal
// input: kelas DB,  tanggal, jam awal normal, jam akhir normal, jam awal aktual, dan jam akhir aktual
// input $bolBreak, jika TRUE, artinya ada jam istirahat, jika FALSE, berarti tanpa istirahat
function calculateOvertime(
    $db,
    $strDate,
    $strNormalStart,
    $strNormalFinish,
    $strAttendanceStart,
    $strAttendanceFinish,
    $strOvertimeStart = "",
    $strOvertimeFinish = "",
    $bolHoliday = false,
    $bolBreak = true,
    $breakFlag = 0,
    $breakLinkID = ""
) {
    $arrResult = [
        "total" => 0,
        "l1" => 0,
        "l2" => 0,
        "l3" => 0,
        "l4" => 0,
        "l5" => 0,
        "morning" => 0,
        "late" => 0,
        "early" => 0
    ];
    $intWDay = getWDay($strDate);
    if ($bolHoliday) {
        $strNormalStart = $strNormalFinish = $strAttendanceStart;
    }
    if ($strOvertimeStart == "") {
        $strOvertimeStart = $strNormalFinish;
    }
    if ($strOvertimeFinish == "") {
        $strOvertimeFinish = $strAttendanceFinish;
    }
    // jika jumat, tipenya 1, selain itu 0 = hari biasa
    $intTipe = ($intWDay == 5) ? 1 : 0;
    $intTipe = ($bolHoliday) ? 2 : $intTipe;
    // CARI INFO TERLAMBAT ATAU LEMBUR PAGI ----
    if (validTime($strAttendanceStart)) {
        if ($strNormalStart == $strAttendanceStart) {
            // nothing
        } else if ($strNormalStart > $strAttendanceStart) { // lebih awal
            if ($strNormalStart <= $strNormalFinish) { // masuk siang
                // anggap lembur pagi
                $arrResult['morning'] = getTotalWorkingHour(
                    $db,
                    $strDate,
                    $strAttendanceStart,
                    $strNormalStart,
                    $breakFlag,
                    $breakLinkID,
                    $intTipe
                );
            } else { // masuk malam
                if ($strAttendanceStart < $strNormalFinish) { // terlambat
                    $arrResult['late'] = getTotalWorkingHour(
                        $db,
                        $strDate,
                        $strNormalStart,
                        $strAttendanceStart,
                        $breakFlag,
                        $breakLinkID,
                        $intTipe
                    );
                } else { // awal
                    $arrResult['morning'] = getTotalWorkingHour(
                        $db,
                        $strDate,
                        $strAttendanceStart,
                        $strNormalStart,
                        $breakFlag,
                        $breakLinkID,
                        $intTipe
                    );
                }
            }
        } else { // lebih lama
            if ($strNormalStart <= $strNormalFinish) { // masuk siang
                if ($strAttendanceStart < $strNormalFinish) { // telat
                    $arrResult['late'] = getTotalWorkingHour(
                        $db,
                        $strDate,
                        $strNormalStart,
                        $strAttendanceStart,
                        $breakFlag,
                        $breakLinkID,
                        $intTipe
                    );
                } else { // awal, asumsi hadir di hari sebelumnya
                    if ($bolBreak) {
                        $arrResult['morning'] = getTotalWorkingHour(
                            $db,
                            $strDate,
                            $strAttendanceStart,
                            $strNormalStart,
                            $breakFlag,
                            $breakLinkID,
                            $intTipe
                        );
                    } else {
                        $arrResult['morning'] = getTotalHour($strAttendanceStart, $strNormalStart);
                    }
                }
            } else { // masuk malam
                $arrResult['late'] = getTotalWorkingHour(
                    $db,
                    $strDate,
                    $strNormalStart,
                    $strAttendanceStart,
                    $breakFlag,
                    $breakLinkID,
                    $intTipe
                );
            }
        }
    }
    // -- CARI INFO APAKAH PULANG AWAL ATAU LEMBUR
    if (validTime($strAttendanceFinish)) {
        if ($strAttendanceFinish < $strNormalFinish) {
            // lebih awal
            if ($strNormalStart <= $strNormalFinish) {
                // masuk siang
                if ($strAttendanceFinish > $strNormalStart) {
                    // pulang awal
                    $arrResult['early'] = getTotalWorkingHour(
                        $db,
                        $strDate,
                        $strAttendanceFinish,
                        $strNormalFinish,
                        $breakFlag,
                        $breakLinkID,
                        $intTipe
                    );
                }
            } else {
                // masuk malam
                $arrResult['early'] = getTotalWorkingHour(
                    $db,
                    $strDate,
                    $strAttendanceFinish,
                    $strNormalFinish,
                    $breakFlag,
                    $breakLinkID,
                    $intTipe
                );
            }
        } else if ($strAttendanceFinish > $strNormalFinish) {
            // lebih lama
            if ($strNormalStart > $strNormalFinish) {
                // masuk malam
                if ($strAttendanceFinish >= $strNormalStart) {
                    // dianggap pulang awal, karena pulang sebelum tengah malam
                    $arrResult['early'] = getTotalWorkingHour(
                        $db,
                        $strDate,
                        $strAttendanceFinish,
                        $strNormalFinish,
                        $breakFlag,
                        $breakLinkID,
                        $intTipe
                    );
                }
            }
        }
        if ($bolBreak) {
            $intTotal = getTotalWorkingHour(
                $db,
                $strDate,
                $strOvertimeStart,
                $strOvertimeFinish,
                $breakFlag,
                $breakLinkID,
                $intTipe
            );
        } else {
            $intTotal = getTotalHour($strOvertimeStart, $strOvertimeFinish);
        }
        //Aturan Pembulatan
        $arrResult['total'] = $intTotal;
        if ($bolHoliday) {
            // cek L2
            $intMax = (60 * 8); // maksimal L2 adalah 8
            if ($intTotal > $intMax) {
                $arrResult['l2'] = $intMax;
                $intTotal -= $intMax;
                //cari L3
                $intMax = 60;
                if ($intTotal > $intMax) {
                    $arrResult['l3'] = $intMax;
                    $arrResult['l4'] = $intTotal - $intMax;
                } else {
                    $arrResult['l3'] = $intTotal;
                }
            } else {
                $arrResult['l2'] = $intTotal;
            }
        } else {
            $bolLembur = false;
            if ($strAttendanceFinish == $strOvertimeStart) {
                // nothing
                $bolLembur = false;
            } else if ($strAttendanceFinish < $strOvertimeStart) { // lebih awal
                if ($strNormalStart <= $strOvertimeStart) { // masuk siang
                    if ($strAttendanceFinish > $strNormalStart) { // pulang awal
                        $bolLembur = false;
                    } else { // bisa dianggap lembur, lewat tengah malam
                        $bolLembur = true;
                    }
                } else { // masuk malam
                    $bolLembur = false; // pulang awal
                }
            } else if ($strAttendanceFinish > $strOvertimeStart) { // lebih lama
                if ($strNormalStart <= $strOvertimeStart) { // masuk siang
                    $bolLembur = true;
                } else { // masuk malam
                    if ($strAttendanceFinish < $strNormalStart) { // dianggap lembur
                        $bolLembur = true;
                    } else { // dianggap pulang awal, karena pulang sebelum tengah malam
                        $bolLembur = false;
                    }
                }
            }
            // bukan hari libur
            if ($bolLembur) {
                $arrResult['total'] = $intTotal;
                // cek L1
                $intMax = 60;
                if ($intTotal > $intMax) {
                    $arrResult['l1'] = $intMax;
                    $intTotal -= $intMax;
                    // cek L2
                    $arrResult['l2'] = $intTotal;
                } else {
                    $arrResult['l1'] = $intTotal;
                }
            }
        }
    }
    $arrResult['l1x'] = $arrResult['l1'] * 1.5;
    $arrResult['l2x'] = $arrResult['l2'] * 2;
    $arrResult['l3x'] = $arrResult['l3'] * 3;
    $arrResult['l4x'] = $arrResult['l4'] * 4;
    $arrResult['totalx'] = $arrResult['l1x'] + $arrResult['l2x'] + $arrResult['l3x'] + $arrResult['l4x'];
    return $arrResult;
}//calculateOvertime
function calculateOvertimeByOTSchedule(
    $db,
    $strDate,
    $strOvertimeStart,
    $strOvertimeFinish,
    $intEarlyOvertime = 0,
    $strOvertimeType = "",
    $intTipe = "",
    $bolBreak = true,
    $breakFlag = 0,
    $breakLinkID = ""
) {
    $arrResult = ["total" => 0, "l1" => 0, "l2" => 0, "l3" => 0, "l4" => 0, "l5" => 0];
    $bol5WorkDay = false;
    $intWDay = getWDay($strDate);
    if ($strOvertimeType == 1) {
        $bolHoliday = true;
    } else {
        $bolHoliday = false;
    }
    // jika jumat, tipenya 1, selain itu 0 = hari biasa
    if (!is_numeric($intTipe)) {
        $intTipe = ($intWDay == 5) ? 1 : 0;
        $intTipe = ($bolHoliday) ? 2 : $intTipe;
    }
    $intTotal = getTotalWorkingHour(
        $db,
        $strDate,
        $strOvertimeStart,
        $strOvertimeFinish,
        $breakFlag,
        $breakLinkID,
        $intTipe
    );
    //jumlahkan dengan total early overtime
    $intTotal += $intEarlyOvertime;
    $arrResult['total'] = $intTotal;
    if ($bolHoliday) {
        // cek L2
        $intTotal = $arrResult['total'];
        $intMax = (60 * 8); // maksimal L2 adalah 8
        if ($intTotal > $intMax) {
            $arrResult['l2'] = $intMax;
            $intTotal -= $intMax;
            //cari L3
            $intMax = 60;
            if ($intTotal > $intMax) {
                $arrResult['l3'] = $intMax;
                $arrResult['l4'] = $intTotal - $intMax;
            } else {
                $arrResult['l3'] = $intTotal;
            }
        } else {
            $arrResult['l2'] = $intTotal;
        }
    } else  // not holiday
    {
        // cek L1
        $intMax = 60;
        if ($intTotal > $intMax) {
            $arrResult['l1'] = $intMax;
            $intTotal -= $intMax;
            // cek L2
            $arrResult['l2'] = $intTotal;
        } else {
            $arrResult['l1'] = $intTotal;
        }
    }
    return $arrResult;
}//calculateOvertimeByOTSchedule
// fungsi untuk menghitung lembur dari data kehadiran yang sudah ada, untuk employee  tertentu
// input, Tanggal kehadiran, idEmployee
// proses, cari data kehadiran berdasar kriteria yang ada pada hari tertentu saja
function reCalculateOvertimeData($db, $strDate, $strIDEmployee = "")
{
    $arrResult = [];
    if ($strDate == "" || $strDate == "NULL") {
        return $arrResult;
    }
    if ($strIDEmployee == "") {
        return $arrResult;
    }
    $strSQL = "SELECT * FROM hrd_attendance ";
    $strSQL .= "WHERE id_employee = '$strIDEmployee' AND attendance_date = '$strDate' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        $bolOK = ($rowDb['normal_start'] != "");
        $bolOK = ($bolOK && ($rowDb['normal_finish'] != ""));
        $bolOK = ($bolOK && ($rowDb['attendance_start'] != ""));
        $bolOK = ($bolOK && ($rowDb['attendance_finish'] != ""));
        if ($bolOK) {
            $arrLembur = calculateOvertime(
                $db,
                $strDate,
                $rowDb['normal_start'],
                $rowDb['normal_finish'],
                $rowDb['attendance_start'],
                $rowDb['attendance_finish']
            );
            if ($rowDb['attendance_start'] != "" && $rowDb['attendance_finish'] != "") {
                $intTotalHour = getTotalHour($rowDb['attendance_start'], $rowDb['attendance_finish']);
            } else {
                $intTotalHour = 0;
            }
            // update data sesuai data lembur yang berhasil dihitung
            $strSQL = "UPDATE hrd_attendance SET created=now(), ";
            $strSQL .= "total_duration = '$intTotalHour', ";
            $strSQL .= "morning_overtime = '" . $arrLembur['morning'] . "', ";
            $strSQL .= "late_duration = '" . $arrLembur['late'] . "', ";
            $strSQL .= "early_duration = '" . $arrLembur['early'] . "', ";
            $strSQL .= "l1 = '" . $arrLembur['l1'] . "', l2 = '" . $arrLembur['l2'] . "',  ";
            $strSQL .= "l3 = '" . $arrLembur['l3'] . "', l4 = '" . $arrLembur['l4'] . "',  ";
            $strSQL .= "overtime = '" . $arrLembur['total'] . "'  ";
            $strSQL .= "WHERE id = '" . $rowDb['id'] . "' ";
            $resExec = $db->execute($strSQL);
        }
    }
} // reCalculateOvertimeData
// fungsi untuk menghitung total kompensasi makan dan transport yang didapat
// semua parameter harus diisi.
// classDB, tanggal, idEmployee, jam awal, jam akhir, durasi (biar gak ngtung lagi)
function calculateOTCompensation($db, $strDate, $strIDEmployee, $strStart, $strFinish, $intDuration = 0)
{
    $arrResult = ["meal" => 0, "transport" => 0];
    if ($strDate == "" || $strIDEmployee == "" || $strStart == "" || $strFinish == "") {
        return $arrResult;
    }
    $bolHoliday = isHoliday($strDate);
    $strFinish = substr($strFinish, 0, 5);
    $intDuration = (int)($intDuration / 60);
    // ambil info karyawan
    $strSQL = "SELECT * FROM hrd_employee WHERE flag = 0 AND id = '$strIDEmployee' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        $fltMeal = 0;
        $fltTransport = 0;
        // ambil platform berdasarkan status karyawan
        $strLimitTransport = getSetting("ot_transport_normal_limit");
        $strLimitTransport = ($strLimitTransport == "") ? "21:00" : substr($strLimitTransport, 0, 5);
        if ($rowDb['employee_status'] == STATUS_OUTSOURCE) {
            if ($bolHoliday) {
                $fltMeal = getSetting("ot_meal_holiday_outsource");
                $fltTransport = getSetting("ot_transport_holiday_outsource");
            } else {
                $fltMeal = getSetting("ot_meal_normal_outsource");
                $fltTransport = getSetting("ot_transport_normal_outsource");
            }
        } else { // permanent dan kontrak
            if ($bolHoliday) {
                $fltMeal = getSetting("ot_meal_holiday_permanent");
                $fltTransport = getSetting("ot_transport_holiday_permanent");
            } else {
                $fltMeal = getSetting("ot_meal_normal_permanent");
                $fltTransport = getSetting("ot_transport_normal_permanent");
            }
        }
        // ambil batas jam untuk uang makan
        $intLimitMeal1 = getSetting("ot_meal_limit1");
        $intLimitMeal2 = getSetting("ot_meal_limit2");
        $intLimitMeal3 = getSetting("ot_meal_limit3");
        // cek validasi
        if (!is_numeric($fltMeal)) {
            $fltMeal = 0;
        }
        if (!is_numeric($fltTransport)) {
            $fltTransport = 0;
        }
        if (!is_numeric($intLimitMeal1)) {
            $intLimitMeal1 = 0;
        }
        if (!is_numeric($intLimitMeal2)) {
            $intLimitMeal2 = 0;
        }
        if (!is_numeric($intLimitMeal3)) {
            $intLimitMeal3 = 0;
        }
        // hitung jatah transport
        if ($bolHoliday) {
            $arrResult['transport'] = $fltTransport;
        } else {
            // hari normal, harus lebih atas
            if ($strFinish >= $strLimitTransport) {
                $arrResult['transport'] = $fltTransport;
            }
        }
        //whether get meal or not ==> based on organization chart
        //--------------------------------------------------------------------------
        $strSQL = "SELECT t1.get_ot_meal AS division_meal, t2.get_ot_meal AS department_meal, ";
        $strSQL .= "t3.get_ot_meal AS section_meal, t4.get_ot_meal AS subsection_meal ";
        $strSQL .= "FROM hrd_employee AS t0 ";
        $strSQL .= "LEFT JOIN hrd_division   AS t1 ON t0.division_code   = t1.division_code ";
        $strSQL .= "LEFT JOIN hrd_department AS t2 ON t0.department_code = t2.department_code ";
        $strSQL .= "LEFT JOIN hrd_section    AS t3 ON t0.section_code    = t3.section_code ";
        $strSQL .= "LEFT JOIN hrd_sub_section AS t4 ON t0.sub_section_code = t4.sub_section_code ";
        $strSQL .= "WHERE t0.id = '$strIDEmployee'";
        $resS = $db->execute($strSQL);
        if ($rowS = $db->fetchrow($resS)) {
            if ($rowS['subsection_meal'] == 'f') {
                $bolGetMeal = false;
            } else if ($rowS['subsection_meal'] == 't') {
                $bolGetMeal = true;
            } else if ($rowS['section_meal'] == 'f') {
                $bolGetMeal = false;
            } else if ($rowS['section_meal'] == 't') {
                $bolGetMeal = true;
            } else if ($rowS['department_meal'] == 'f') {
                $bolGetMeal = false;
            } else if ($rowS['department_meal'] == 't') {
                $bolGetMeal = true;
            } else if ($rowS['division_meal'] == 'f') {
                $bolGetMeal = false;
            } else if ($rowS['division_meal'] == 't') {
                $bolGetMeal = true;
            } else {
                $bolGetMeal = false;
            }
        } else {
            $bolGetMeal = false;
        }
        //--------------------------------------------------------------------------
        // hitung jatah makan
        if ($bolGetMeal) {
            if ($intDuration >= ($intLimitMeal1 + $intLimitMeal2 + $intLimitMeal3)) {
                $arrResult['meal'] = $fltMeal * 3;
            } else if ($intDuration >= ($intLimitMeal1 + $intLimitMeal2)) {
                $arrResult['meal'] = $fltMeal * 2;
            } else if ($intDuration >= $intLimitMeal1) {
                $arrResult['meal'] = $fltMeal;
            }
        } else {
            $arrResult['meal'] = "test";
        }
    }
    return $arrResult;
}//calculateOTCompensation
function extractAutoOT($intAutoOT)
{
    $arrOTType = [1 => 1, 2 => 7, 3 => 1, 4 => 15];
    // assign auto OT ke L1, L2, L3 dan L4
    foreach ($arrOTType AS $index => $intMax) {
        if ($intAutoOT > $intMax) {
            $arrResult[$index] = $intMax;
        } else {
            $arrResult[$index] = ($intAutoOT > 0) ? $intAutoOT : 0;
        }
        $intAutoOT -= $arrResult[$index];
    }
    return $arrResult;
}

// fungsi untuk cek SPL dan data attendance, hanya yang ada di SPL saja yang dianggap lembur
// utk overtime_application
function syncOvertimeApplication($db, $strDateFrom, $strDateThru, $strIDEmployee = "", $strKriteria = "")
{
    // cari info SPL
    // uddin : tambah status=2 kalkulasi SPL yg sudah di approved
    $arrOvertime = [];
    $strSQL = "SELECT id_application, id_employee, t1.overtime_date, include_early_ot, ";
    $strSQL .= "start_plan, finish_plan, start_actual, finish_actual, holiday_ot, ";
    $strSQL .= "start_early_plan, finish_early_plan, start_early_actual, finish_early_actual, ";
    $strSQL .= "start_early_auto, finish_early_auto, start_auto, finish_auto ";
    $strSQL .= "FROM hrd_overtime_application_employee AS t1 ";
    $strSQL .= "LEFT JOIN hrd_overtime_application AS t2 ON t1.id_application = t2.id ";
    $strSQL .= "LEFT JOIN hrd_employee AS t3 ON t1.id_employee = t3.id ";
    $strSQL .= "WHERE t1.status=2 and t1.overtime_date BETWEEN '$strDateFrom' AND '$strDateThru' $strKriteria";
    if ($strIDEmployee != "") {
        $strSQL .= "AND id_employee ='$strIDEmployee' ";
    }
    //echo $strSQL;
    $resS = $db->execute($strSQL);
    while ($rowS = $db->fetchrow($resS)) {
        $arrOvertime[$rowS['overtime_date']][$rowS['id_employee']] = $rowS;
    }
    // cari info Attendance
    $arrAttendance = [];
    $strSQL = "SELECT id_employee, attendance_date, attendance_finish, attendance_start, ";
    $strSQL .= "normal_finish, normal_start, holiday FROM hrd_attendance AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t3 ON t1.id_employee = t3.id ";
    $strSQL .= "WHERE attendance_date BETWEEN '$strDateFrom' AND '$strDateThru' $strKriteria";
    if ($strIDEmployee != "") {
        $strSQL .= "AND id_employee ='$strIDEmployee' ";
    }
    $resS = $db->execute($strSQL);
    while ($rowS = $db->fetchrow($resS)) {
        if ($rowS['attendance_start'] != "" && $rowS['attendance_finish'] != "" && ($rowS['normal_start'] == "" || $rowS['normal_finish'] == "")) {
            $rowS['normal_start'] = $rowS['normal_finish'] = $rowS['attendance_start'];
        }
        $arrAttendance[$rowS['attendance_date']][$rowS['id_employee']] = $rowS;
    }
    // cari info Employee auto ot
    $arrAutoOT = [];
    $strSQL = "SELECT t1.id, get_auto_ot FROM hrd_employee AS t1 LEFT JOIN hrd_position AS t2 ";
    $strSQL .= "ON t1.position_code = t2.position_code ";
    if ($strIDEmployee != "") {
        $strSQL .= "AND id ='$strIDEmployee' ";
    }
    $resS = $db->execute($strSQL);
    while ($rowS = $db->fetchrow($resS)) {
        $arrAutoOT[$rowS['id']] = ($rowS['get_auto_ot'] == 't');
    }
    $strSQL = "";
    //jika strIDEmployee tidak di definisikan,
    //cek ulang auto ot yang valid simpan datanya,
    //untuk data selebihnya (yang tidak dapat auto ot)
    //  cek setiap kemungkinan data attendance yang tidak didukung SPL dan hapus keterangan OT di tabel hrd_attendance
    foreach ($arrAttendance as $strCurDate => $arrAttEmp) {
        if ($strIDEmployee == "") //jika id employee tidak didefinisikan, loop
        {
            foreach ($arrAttEmp as $strID => $arrDetail) {
                if (!isset($arrOvertime[$strCurDate][$strID])) {
                    $strSQL .= generateNonOTAttendanceSQL($strID, $strCurDate, $arrDetail, $arrAutoOT[$strID]);
                } else {
                    $strSQL .= generateOTAttendanceSQL(
                        $strID,
                        $strCurDate,
                        $arrDetail,
                        $arrOvertime[$strCurDate][$strID]
                    );
                }
            }
        } else //jika didefinisikan tidak usah loop
        {
            if (!isset($arrOvertime[$strCurDate][$strIDEmployee])) {
                $strSQL .= generateNonOTAttendanceSQL(
                    $strIDEmployee,
                    $strCurDate,
                    $arrAttEmp[$strIDEmployee],
                    $arrAutoOT[$strIDEmployee]
                );
            } else {
                $strSQL .= generateOTAttendanceSQL(
                    $strIDEmployee,
                    $strCurDate,
                    $arrAttEmp[$strIDEmployee],
                    $arrOvertime[$strCurDate][$strIDEmployee]
                );
            }
        }
    }
    $strSQL = str_replace("''", "null", $strSQL);
    $resExec = $db->execute($strSQL);
}//syncOvertimeApplicaton
function generateOTAttendanceSQL($strIDEmployee, $strDate, $arrAttRow, $arrOTRow, $bolAutoOT = false)
{
    //cek finish actual utk regular overtime
    //jika ambil yang lebih awal (antara attendance finish dengan finish plan)
    global $db;
    //tambahkan keterangan auto OT jika fungsi ini dipanggil dari generateAutoOTAttendanceSQL (hanya terjadi jika hari libur)
    //artinya waktu lembur mengacu pada jam kehadiran aktual karyawan, tanpa perlu SPL
    if ($bolAutoOT) {
        $strOvertimeStartAuto = $arrOTRow['start_auto'];
        $strOvertimeFinishAuto = $arrOTRow['finish_auto'];
        $strOvertimeStartEarlyAuto = $arrOTRow['start_early_auto'];
        $strOvertimeFinishEarlyAuto = $arrOTRow['finish_early_auto'];
        $strAutoOT = "t";
    } else {
        $strOvertimeStartAuto = $strOvertimeFinishAuto = $strOvertimeStartEarlyAuto = $strOvertimeFinishEarlyAuto = "";
        $strAutoOT = "f";
    }
    /* Penentuan ot_start pada lembur libur tergantung pada jadwal dan kehadiran karyawan, sbb:

    Jika start_ot < finish_ot
      if attendance_start < ot_start, ot_start
      else if attendance_start < ot_finish, attendance_start
      else, cut
    Else
      jika attendance start <= ot_start && attendance start > ot_finish
        if attendance finish <= ot_start && attendance finish > ot_finish, cut
        else, ot_start
      else, attendance_start
    */
    if ($arrOTRow['holiday_ot'] == "f") {
        $strOvertimeStart = $arrOTRow['start_plan'];
        //echo "bukan libur<br/><br/>";
    } else {
        //echo "<br/><br/>libur2".$arrAttRow['attendance_start'].$arrOTRow['start_plan'].$arrOTRow['finish_plan']."--jj--";
        if (timeCompare($arrOTRow['start_plan'], $arrOTRow['finish_plan']) < 0) {
            if (timeCompare($arrAttRow['attendance_start'], $arrOTRow['start_plan']) < 0) {
                $strOvertimeStart = $arrOTRow['start_plan'];
            } else if (timeCompare($arrAttRow['attendance_start'], $arrOTRow['finish_plan']) < 0) {
                $strOvertimeStart = roundOvertimeInOut($arrAttRow['attendance_start'], 1);
            } else {
                $strOvertimeStart = $strOvertimeFinish = "";
            }
        } else {
            if (timeCompare($arrAttRow['attendance_start'], $arrOTRow['start_plan']) <= 0 && timeCompare(
                    $arrAttRow['attendance_start'],
                    $arrOTRow['finish_plan']
                ) > 0
            ) {
                if (timeCompare($arrAttRow['attendance_finish'], $arrOTRow['start_plan']) <= 0 && timeCompare(
                        $arrAttRow['attendance_finish'],
                        $arrOTRow['finish_plan']
                    ) > 0 && timeCompare($arrAttRow['attendance_start'], $arrAttRow['attendance_finish']) <= 0
                ) {
                    $strOvertimeStart = $strOvertimeFinish = "";
                } else {
                    $strOvertimeStart = $arrOTRow['start_plan'];
                }
            } else {
                $strOvertimeStart = roundOvertimeInOut($arrAttRow['attendance_start'], 1);
            }
        }
    }
    /* Penentuan ot_finish tergantung pada jadwal dan kehadiran karyawan, sbb:

      if start_ot < finish_ot
        if overnight (masuk malam, pulang pagi)
          jika attendance_finish > normal start || attendance_finish < ot_start, cut
          else if attendance_finish < ot_finish, get attendance_finish
          else, get ot_finish
        else (standard, masuk pagi pulang sore)
          if attendance_finish < normal_start || attendance_finish > ot_finish, get ot_finish
          else if attendance_finish > ot_start, get attendance_finish
          else, cut
      else (standard jika tanpa ot, ada kemungkinan overnight jika ot)
        if attendance_finish > ot_start || attendance_finish < ot_finish, get attendance_finish
        else if attendance_finish > normal_start, cut
        else, get ot_finish
    */
    if ($arrOTRow['finish_plan'] != "") {
        if (timeCompare($arrOTRow['start_plan'], $arrOTRow['finish_plan']) <= 0) {
            if (timeCompare($arrAttRow['normal_start'], $arrAttRow['normal_finish']) > 0) {
                if (timeCompare($arrAttRow['attendance_finish'], $arrAttRow['normal_start']) > 0 || timeCompare(
                        $arrAttRow['attendance_finish'],
                        $arrOTRow['start_plan']
                    ) < 0
                ) {
                    $strOvertimeStart = $strOvertimeFinish = "";
                } else if (timeCompare($arrAttRow['attendance_finish'], $arrOTRow['finish_plan']) < 0) {
                    $strOvertimeFinish = roundOvertimeInOut($arrAttRow['attendance_finish'], 0);
                } else {
                    $strOvertimeFinish = $arrOTRow['finish_plan'];
                }
            } else {
                if (timeCompare($arrAttRow['attendance_finish'], $arrAttRow['normal_start']) < 0 || timeCompare(
                        $arrAttRow['attendance_finish'],
                        $arrOTRow['finish_plan']
                    ) > 0
                ) {
                    $strOvertimeFinish = $arrOTRow['finish_plan'];
                } else if (timeCompare($arrAttRow['attendance_finish'], $arrOTRow['start_plan']) > 0) {
                    $strOvertimeFinish = roundOvertimeInOut($arrAttRow['attendance_finish'], 0);//
                } else {
                    $strOvertimeStart = $strOvertimeFinish = "";
                }
            }
        } else {
            if (timeCompare($arrAttRow['attendance_finish'], $arrOTRow['start_plan']) > 0 || timeCompare(
                    $arrAttRow['attendance_finish'],
                    $arrOTRow['finish_plan']
                ) < 0
            ) {
                $strOvertimeFinish = roundOvertimeInOut($arrAttRow['attendance_finish'], 0);
            } else if (timeCompare($arrAttRow['attendance_finish'], $arrAttRow['normal_start']) > 0) {
                $strOvertimeStart = $strOvertimeFinish = "";
            } else {
                $strOvertimeFinish = $arrOTRow['finish_plan'];
            }
        }
    } else {
        $strOvertimeStart = $strOvertimeFinish = "";
    }
    //cek start_early_actual utk early overtime
    //jika ambil yang lebih besar (antara attendance start dengan start_ealy_plan
    if ($arrOTRow['include_early_ot'] == "t") {
        $strOvertimeFinishEarly = $arrOTRow['finish_early_plan'];
        /*
        Jika start_ot < finish_ot
          jika overnight
            jika attendance_start > normal-start || attendance_start < normal_finish  cut
            else if attendance_start < ot_start, get ot_start
            else, get attendance_start
          else
            if attendance_start < ot_start || attendance_start > normal_finish  get ot_start
            else if attendance_start < normal_start  get attendance_start
            else, cut
        Else
          jika attendance_start < ot_finish || attendance_start > ot_start  get attendance_start
          else if attendance_start > normal_finish, cut
          else, get ot_start
        */
        if (timeCompare($arrOTRow['start_early_plan'], $arrOTRow['finish_early_plan']) < 0) {
            if (timeCompare($arrAttRow['normal_start'], $arrAttRow['normal_finish']) > 0) {
                if (timeCompare($arrAttRow['attendance_start'], $arrAttRow['normal_start']) > 0 || timeCompare(
                        $arrAttRow['attendance_start'],
                        $arrAttRow['normal_finish']
                    ) < 0
                ) {
                    $strOvertimeStartEarly = $strOvertimeFinishEarly = "";
                } else if (timeCompare($arrAttRow['attendance_start'], $arrOTRow['start_early_plan']) > 0) {
                    $strOvertimeStartEarly = roundOvertimeInOut($arrAttRow['attendance_start'], 1);
                } else {
                    $strOvertimeStartEarly = $arrOTRow['start_early_plan'];
                }
            } else {
                if (timeCompare($arrAttRow['attendance_start'], $arrAttRow['normal_finish']) > 0 || timeCompare(
                        $arrAttRow['attendance_start'],
                        $arrOTRow['start_early_plan']
                    ) < 0
                ) {
                    $strOvertimeStartEarly = $arrOTRow['start_early_plan'];
                } else if (timeCompare($arrAttRow['attendance_start'], $arrAttRow['normal_start']) < 0) {
                    $strOvertimeStartEarly = roundOvertimeInOut($arrAttRow['attendance_start'], 1);
                } else {
                    $strOvertimeStartEarly = $strOvertimeFinishEarly = "";
                }
            }
        } else {
            if (timeCompare($arrAttRow['attendance_start'], $arrOTRow['start_early_plan']) > 0 || timeCompare(
                    $arrAttRow['attendance_start'],
                    $arrOTRow['finish_early_plan']
                ) < 0
            ) {
                $strOvertimeStartEarly = roundOvertimeInOut($arrAttRow['attendance_start'], 1);
            } else if (timeCompare($arrAttRow['attendance_start'], $arrAttRow['normal_finish']) > 0) {
                $strOvertimeStartEarly = $strOvertimeFinishEarly = "";
            } else {
                $strOvertimeStartEarly = $arrOTRow['start_early_plan'];
            }
        }
        if ($strOvertimeStartEarly == "" || $strOvertimeFinishEarly == "") {
            $intEarlyDuration = 0;
        } else {
            $intEarlyDuration = getTotalHour($strOvertimeStartEarly, $strOvertimeFinishEarly);
        }
    } else {
        $strOvertimeStartEarly = $strOvertimeFinishEarly /*= $strOvertimeStartEarlyAuto = $strOvertimeFinishEarlyAuto*/ = "";
        $intEarlyDuration = 0;
    }
    $bolHoliday = ($arrOTRow['holiday_ot'] == "t");
    if ($strOvertimeStart != "" && $strOvertimeFinish != "") {
        $arrBreak = (isset($arrOTRow['id_application'])) ? getOTBreak(
            $arrOTRow['id_application']
        ) : ['intBreakFlag' => 0, 'intBreakType' => "", 'intBreakLinkID' => ""];
        foreach ($arrBreak as $strKey => $strVal) {
            $$strKey = $strVal;
        }
        $arrOT = calculateOvertimeByOTSchedule(
            $db,
            $strDate,
            $strOvertimeStart,
            $strOvertimeFinish,
            $intEarlyDuration,
            $bolHoliday,
            $intBreakType,
            true,
            $intBreakFlag,
            $intBreakLinkID
        );
        $strIsOvertime = 't';
    } else if ($arrOTRow['include_early_ot'] == "t") {
        $arrOT = calculateOvertimeByOTSchedule(
            $db,
            $strDate,
            "01:00:00",
            "01:00:00",
            $intEarlyDuration,
            $bolHoliday,
            false
        );
        $strIsOvertime = 't';
    } else {
        $arrOT = ['l1' => 0, 'l2' => 0, 'l3' => 0, 'l4' => 0, 'l5' => 0, 'total' => 0, 'is_overtime' => 'f'];
        $strOvertimeStart = $strOvertimeFinish = $strOvertimeStartAuto = $strOvertimeFinishAuto = "";
        $strIsOvertime = 'f';
    }
    /* Pengecekan apakah ada additional_overtime jika merupakan shift yang shift type
       nya mengandung nilai additional overtime masukkan ke dalam nilai l5
    */
    if ($bolHoliday) {
        $bolIsShift = isShift($db, $strIDEmployee, $strDate);
        if ($bolIsShift) {
            $shiftTypeData = getShiftTypeData($db, $strIDEmployee, $strDate);
            /* Pengecekan jika kehadiran sesuai dengan data shift type, jika tidak
               additional_ot bernilai 0
            */
            if (strtotime($arrAttRow['attendance_start']) <= strtotime($shiftTypeData['start_time']) && strtotime(
                    $arrAttRow['attendance_finish']
                ) >= strtotime($shiftTypeData['finish_time'])
            ) {
                $arrOT['l5'] = $shiftTypeData['additional_ot'];
            } else {
                $arrOT['l5'] = 0;
            }
        } else {
            /* Jika bukan Shift additional_ot bernilai 0 */
            $arrOT['l5'] = 0;
        }
        /* Nilai total ditambah dengan nilai additional_ot */
        $arrOT['total'] = $arrOT['total'] + $arrOT['l5'];
    }
    //Update Overtime
    $strSQL = "UPDATE hrd_overtime_application_employee ";
    $strSQL .= "SET start_actual = '$strOvertimeStart', ";
    $strSQL .= "finish_actual = '$strOvertimeFinish', ";
    $strSQL .= "l1 = '" . $arrOT['l1'] . "', ";
    $strSQL .= "l2 = '" . $arrOT['l2'] . "', ";
    $strSQL .= "l3 = '" . $arrOT['l3'] . "', ";
    $strSQL .= "l4 = '" . $arrOT['l4'] . "', ";
    $strSQL .= "l5 = '" . $arrOT['l5'] . "', ";
    $strSQL .= "total_time = '" . $arrOT['total'] . "' ";
    $strSQL .= "WHERE id_employee = '$strIDEmployee' AND  overtime_date = '$strDate'; ";
    //Update Attendance
    $strSQL .= "UPDATE hrd_attendance SET ";
    $strSQL .= "overtime_start = '$strOvertimeStart', overtime_finish = '$strOvertimeFinish', ";
    $strSQL .= "overtime_start_early = '$strOvertimeStartEarly', overtime_finish_early = '$strOvertimeFinishEarly', ";
    $strSQL .= "overtime_start_auto = '" . $strOvertimeStartAuto . "', overtime_finish_auto = '" . $strOvertimeFinishAuto . "', ";
    $strSQL .= "overtime_start_early_auto = '" . $strOvertimeStartEarlyAuto . "', overtime_finish_early_auto = '" . $strOvertimeFinishEarlyAuto . "', ";
    $strSQL .= "l1 = '" . $arrOT['l1'] . "', l2 = '" . $arrOT['l2'] . "', ";
    $strSQL .= "l3 = '" . $arrOT['l3'] . "', l4 = '" . $arrOT['l4'] . "', l5 = '" . $arrOT['l5'] . "', ";
    $strSQL .= "overtime= '" . $arrOT['total'] . "', early_overtime = '" . $intEarlyDuration . "', ";
    $strSQL .= "is_overtime = '" . $strIsOvertime . "', auto_overtime = '" . $strAutoOT . "'  ";
    $strSQL .= "WHERE id_employee = '$strIDEmployee' AND  attendance_date = '$strDate'; ";
    return $strSQL;
}//generateOTAttendanceSQL
//fungsi untuk generate data overtime di table attendance jika tidak ada spl untuk hari bersangkutan
//hanya perlu cek apakah auto OT atau tidak
//waktu auto OT mengikuti data Attendance, minimum limit, dan maksimum limit
function generateNonOTAttendanceSQL($strIDEmployee, $strDataDate, $arrAttendance, $bolAutoOT = true)
{
    $intTotalOTMin = $intEarlyOTMin = 0;
    $strOvertimeStartEarlyAuto = $strOvertimeFinishEarlyAuto = $strOvertimeStartEarly = $strOvertimeFinishEarly = "";
    $strOvertimeStartAuto = $strOvertimeFinishAuto = $strOvertimeStart = $strOvertimeFinish = "";
    if ($bolAutoOT) {
        if ($arrAttendance['holiday'] == 1) {
            $strOvertimeStart = $strOvertimeStartAuto = roundOvertimeInOut($arrAttendance['attendance_start'], 1);
            $strOvertimeFinish = $strOvertimeFinishAuto = roundOvertimeInOut($arrAttendance['attendance_finish'], 0);
            $intEarlyOTMin = 0;
        } else {
            if (timeCompare(
                    getNextMinute($arrAttendance['attendance_start'], AUTO_OT_MINIMUM_DURATION),
                    $arrAttendance['normal_start']
                ) <= 0
            ) {
                if (getTotalHour(
                        $arrAttendance['attendance_start'],
                        $arrAttendance['normal_start']
                    ) > AUTO_OT_MAXIMUM_DURATION
                ) {
                    $strOvertimeStartEarlyAuto =
                    $strOvertimeStartEarly = getNextMinute(
                        $arrAttendance['normal_start'],
                        (AUTO_OT_MAXIMUM_DURATION * -1)
                    );
                    $intEarlyOTMin += AUTO_OT_MAXIMUM_DURATION;
                    $intTotalOTMin += AUTO_OT_MAXIMUM_DURATION;
                } else {
                    $strOvertimeStartEarlyAuto = $strOvertimeStartEarly = roundOvertimeInOut($arrAttendance['attendance_start'], 1);
                    $intEarlyOTMin += getTotalHour($arrAttendance['attendance_start'], $arrAttendance['normal_start']);
                    $intTotalOTMin += getTotalHour($arrAttendance['attendance_start'], $arrAttendance['normal_start']);
                }
                $strOvertimeFinishEarlyAuto = $strOvertimeFinishEarly = $arrAttendance['normal_start'];
            }
            if (timeCompare(
                    $arrAttendance['attendance_finish'],
                    getNextMinute($arrAttendance['normal_finish'], AUTO_OT_MINIMUM_DURATION)
                ) >= 0
            ) {
                $strOvertimeStartAuto = $strOvertimeStart = $arrAttendance['normal_finish'];
                if (getTotalHour(
                        $arrAttendance['normal_finish'],
                        $arrAttendance['attendance_finish']
                    ) > AUTO_OT_MAXIMUM_DURATION
                ) {
                    $strOvertimeFinishAuto = $strOvertimeFinish = getNextMinute(
                        $arrAttendance['normal_finish'],
                        AUTO_OT_MAXIMUM_DURATION
                    );
                    $intTotalOTMin += AUTO_OT_MAXIMUM_DURATION;
                } else {
                    $strOvertimeFinishAuto = $strOvertimeFinish = roundOvertimeInOut($arrAttendance['attendance_finish'], 0);
                    $intTotalOTMin += getIntervalHour(
                        $arrAttendance['normal_finish'],
                        $arrAttendance['attendance_finish']
                    );
                }
            }
        }
    }
    if ($intTotalOTMin != 0 || $arrAttendance['holiday'] == 1) {
        $arrOT = [];
        $arrOT['start_early_plan'] = $strOvertimeStartEarly;
        $arrOT['finish_early_plan'] = $strOvertimeFinishEarly;
        $arrOT['start_early_auto'] = $strOvertimeStartEarlyAuto;
        $arrOT['finish_early_auto'] = $strOvertimeFinishEarlyAuto;
        $arrOT['start_plan'] = $strOvertimeStart;
        $arrOT['finish_plan'] = $strOvertimeFinish;
        $arrOT['start_auto'] = $strOvertimeStartAuto;
        $arrOT['finish_auto'] = $strOvertimeFinishAuto;
        $arrOT['include_early_ot'] = ($intEarlyOTMin > 0) ? "t" : "f";
        $arrOT['holiday_ot'] = ($arrAttendance['holiday'] == 1) ? "t" : "f";
        return generateOTAttendanceSQL($strIDEmployee, $strDataDate, $arrAttendance, $arrOT, $bolAutoOT);
    } else {
        //Update Attendance
        return
            " UPDATE hrd_attendance SET
          overtime_start_early = null, overtime_finish_early = null,
          overtime_start = null, overtime_finish = null,
          overtime_start_early_auto = null, overtime_finish_early_auto = null,
          overtime_start_auto = null, overtime_finish_auto = null,
          l1 = 0, l2 = 0, l3 = 0, l4 = 0, l5 = 0, overtime = 0, early_overtime = 0, is_overtime = 'f'
          WHERE id_employee = '$strIDEmployee' AND  attendance_date = '$strDataDate'; ";
    }
}//generateOTAttendanceSQLString
function getOTBreak($strIDOvertimeApplication)
{
    global $db;
    // ambil data Break khusus overtime jika ada
    $strSQL1 = "SELECT type, flag, link_id ";
    $strSQL1 .= "FROM hrd_break_time WHERE flag = '2' AND type = '3' AND ";
    $strSQL1 .= "link_id = '" . $strIDOvertimeApplication . "'";
    $resS = $db->execute($strSQL1);
    if ($rowS = $db->fetchrow($resS)) {
        $arrResult['intBreakFlag'] = 2;
        $arrResult['intBreakType'] = 3;
        $arrResult['intBreakLinkID'] = $strIDOvertimeApplication;
    } else {
        $arrResult['intBreakFlag'] = 0;
        $arrResult['intBreakType'] = "";
        $arrResult['intBreakLinkID'] = "";
    }
    return $arrResult;
}

function getSpvOvertime(
    $db,
    $intInitialOT,
    $strDateFrom,
    $strDateThru,
    $strIDEmployee, /*$strOutdated = "FALSE", */
    $strKriteria = "",
    $fltBasicPerHour
) {
    global $fltHalfOTRate;
    global $fltHalfOTMax;
    $arrOTType = getOvertimeTypeValue($db);
    //jika total OT min lebih dari 30 jam, loop untuk cari 30 jam pertama
    $intTotalOT = $intInitialOT;
    $intTotalOT1 = $intInitialOT;
    $intTotalOT2 = $intInitialOT;
    $intTotalOT3 = $intInitialOT;
    $intTotalOT4 = $intInitialOT;
    $intTotalOTUntilYesterday = $intInitialOT;
    $intTotalOT1UntilYesterday = $intInitialOT;
    $intTotalOT2UntilYesterday = $intInitialOT;
    $intTotalOT3UntilYesterday = $intInitialOT;
    $intTotalOT4UntilYesterday = $intInitialOT;
    $strDateTemp = $strDateFrom;
    $arrOTGroup1 = []; // array untuk OT  30 jam pertama
    $arrOTGroup2 = []; // array untuk OT  selebihnya
    while (dateCompare($strDateTemp, $strDateThru) <= 0) {
        //assign OT untuk date temp
        $arrTodayOT = getEmployeeOvertimeFromAttendance($db, $strDateTemp, $strDateTemp, $strIDEmployee, $strKriteria);
        if (!isset($arrTodayOT[$strIDEmployee])) {
            $arrTodayOT = [$strIDEmployee => ['total1' => 0, 'total2' => 0, 'total3' => 0, 'total4' => 0]];
        }
        $intTodayL1Min = $arrTodayOT[$strIDEmployee]['total1'];
        $intTodayL2Min = $arrTodayOT[$strIDEmployee]['total2'];
        $intTodayL3Min = $arrTodayOT[$strIDEmployee]['total3'];
        $intTodayL4Min = $arrTodayOT[$strIDEmployee]['total4'];
        $intTodayOTMin = $intTodayL1Min + $intTodayL2Min + $intTodayL3Min + $intTodayL4Min;
        $intTotalOTUntilYesterday = $intTotalOT;
        $intTotalOT1UntilYesterday = $intTotalOT1;
        $intTotalOT2UntilYesterday = $intTotalOT2;
        $intTotalOT3UntilYesterday = $intTotalOT3;
        $intTotalOT4UntilYesterday = $intTotalOT4;
        $intTotalOT += $intTodayOTMin;
        $intTotalOT1 += $intTodayL1Min;
        $intTotalOT2 += $intTodayL2Min;
        $intTotalOT3 += $intTodayL3Min;
        $intTotalOT4 += $intTodayL4Min;
        if ($intTotalOT < 1800) {
            //belum sampai 30 jam
            //masukkan nilai ot hari ini ke group1
            //null ke group2
            $arrOTGroup1[$strDateTemp] = $arrTodayOT;
        } else if ($intTotalOTUntilYesterday >= 1800) {
            //sudah lebih dari 30 jam
            //masukkan nilai ot hari ini ke group2
            //null ke group1
            $arrOTGroup2[$strDateTemp] = $arrTodayOT;
        } else {
            //perpotongan jam ke 30
            //masukkan bagian dari 30 jam pertama ke group1
            //selebihnya ke group2
            $intDifference = 1800 - $intTotalOTUntilYesterday;
            //cek letak batas 30 jam pertama dan sisanya di L1, L2, L3, atau L4
            if ($intTodayL1Min <= $intDifference) {
                //durasi l1 masih termasuk 30 jam pertama
                //assign semua ke group 1 L1
                //assign 0 ke group 2 L1
                $arrOTGroup1[$strDateTemp][$strIDEmployee]['total1'] = $arrTodayOT[$strIDEmployee]['total1'];
                $arrOTGroup2[$strDateTemp][$strIDEmployee]['total1'] = 0;
                if ($intTodayL2Min <= ($intDifference - $intTodayL1Min)) {
                    //durasi l2 masih termasuk 30 jam pertama
                    //assign semua ke group 1 L2
                    //assign 0 ke group 2 L2
                    $arrOTGroup1[$strDateTemp][$strIDEmployee]['total2'] = $arrTodayOT[$strIDEmployee]['total2'];
                    $arrOTGroup2[$strDateTemp][$strIDEmployee]['total2'] = 0;
                    if ($intTodayL3Min <= ($intDifference - $intTodayL1Min - $intTodayL2Min)) {
                        //durasi l3 masih termasuk 30 jam pertama
                        //assign semua ke group 1 L3
                        //assign 0 ke group 2 L3
                        $arrOTGroup1[$strDateTemp][$strIDEmployee]['total3'] = $arrTodayOT[$strIDEmployee]['total3'];
                        $arrOTGroup2[$strDateTemp][$strIDEmployee]['total3'] = 0;
                        if ($intTodayL4Min <= ($intDifference - $intTodayL1Min - $intTodayL2Min - $intTodayL3Min)) {
                            //durasi l4 masih termasuk 30 jam pertama
                            //assign semua ke group 1 L4
                            //assign 0 ke group 2 L4
                            $arrOTGroup1[$strDateTemp][$strIDEmployee]['total4'] = $arrTodayOT[$strIDEmployee]['total4'];
                            $arrOTGroup2[$strDateTemp][$strIDEmployee]['total4'] = 0;
                        } else {
                            //assign sebesar selisih ke group 1 L4
                            //assign sisanya ke group 2 L4
                            $arrOTGroup1[$strDateTemp][$strIDEmployee]['total4'] = ($intDifference - $intTodayL1Min - $intTodayL2Min - $intTodayL3Min);
                            $arrOTGroup2[$strDateTemp][$strIDEmployee]['total4'] = $arrTodayOT[$strIDEmployee]['total4'] - ($intDifference - $intTodayL1Min - $intTodayL2Min - $intTodayL3Min);
                        }
                    } else {
                        //assign sebesar selisih ke group 1 L3
                        //assign sisanya ke group 2 L3
                        $arrOTGroup1[$strDateTemp][$strIDEmployee]['total3'] = ($intDifference - $intTodayL1Min - $intTodayL2Min);
                        $arrOTGroup2[$strDateTemp][$strIDEmployee]['total3'] = $arrTodayOT[$strIDEmployee]['total3'] - ($intDifference - $intTodayL1Min - $intTodayL2Min);
                    }
                } else {
                    //assign sebesar selisih ke group 1 L2
                    //assign sisanya ke group 2 L2
                    $arrOTGroup1[$strDateTemp][$strIDEmployee]['total2'] = ($intDifference - $intTodayL1Min);
                    $arrOTGroup2[$strDateTemp][$strIDEmployee]['total2'] = $arrTodayOT[$strIDEmployee]['total2'] - ($intDifference - $intTodayL1Min);
                }
            } else {
                //assign sebesar selisih ke group 1 L1
                //assign sisanya ke group 2 L1
                $arrOTGroup1[$strDateTemp][$strIDEmployee]['total1'] = $intDifference;
                $arrOTGroup2[$strDateTemp][$strIDEmployee]['total1'] = $arrTodayOT[$strIDEmployee]['total1'] - $intDifference;
                $arrOTGroup2[$strDateTemp][$strIDEmployee]['total2'] = $arrTodayOT[$strIDEmployee]['total2'];
                $arrOTGroup2[$strDateTemp][$strIDEmployee]['total3'] = $arrTodayOT[$strIDEmployee]['total3'];
                $arrOTGroup2[$strDateTemp][$strIDEmployee]['total4'] = $arrTodayOT[$strIDEmployee]['total4'];
            }
        }
        $strDateTemp = getNextDate($strDateTemp);
    }
    //hitung group1 : 30 jam pertama-------------------------------------------------------------
    $intOTDay = $intL1Min = $intL2Min = $intL3Min = $intL4Min = 0;
    if (isset($arrOTGroup1)) {
        foreach ($arrOTGroup1 as $strDate => $arrDailyOT) {
            $intOTDay++;
            $intL1Min += isset($arrDailyOT[$strIDEmployee]['total1']) ? $arrDailyOT[$strIDEmployee]['total1'] : 0;
            $intL2Min += isset($arrDailyOT[$strIDEmployee]['total2']) ? $arrDailyOT[$strIDEmployee]['total2'] : 0;
            $intL3Min += isset($arrDailyOT[$strIDEmployee]['total3']) ? $arrDailyOT[$strIDEmployee]['total3'] : 0;
            $intL4Min += isset($arrDailyOT[$strIDEmployee]['total4']) ? $arrDailyOT[$strIDEmployee]['total4'] : 0;
        }
        //total durasi group1 dalam menit
        $fltOTMin = $intL1Min + $intL2Min + $intL3Min + $intL4Min;
    } else {
        $fltOTMin = $intL1Min = $intL2Min = $intL3Min = $intL4Min = 0;
    }
    //total durasi group1 setelah perkalian menurut peraturan pemerintah dalam menit
    $fltOTXMin = $intL1Min * $arrOTType[1];
    $fltOTXMin += $intL2Min * $arrOTType[2];
    $fltOTXMin += $intL3Min * $arrOTType[3];
    $fltOTXMin += $intL4Min * $arrOTType[4];
    //besar tunjangan lembur berdasarkan ot biasa (diluar spv, less, dan auto)
    $fltOvertime = $fltOTXMin / 60 * $fltBasicPerHour;
    //-----------------------------------------------------------------------------------------------
    //hitung group2 : selebihnya---------------------------------------------------------------------
    //total durasi spv ot dalam menit
    $fltOTMinSpv = 0;
    if (isset($arrOTGroup2)) {
        foreach ($arrOTGroup2 as $strDate => $arrDailyOT) {
            $fltOTMinSpv += isset($arrDailyOT[$strIDEmployee]['total1']) ? $arrDailyOT[$strIDEmployee]['total1'] : 0;
            $fltOTMinSpv += isset($arrDailyOT[$strIDEmployee]['total2']) ? $arrDailyOT[$strIDEmployee]['total2'] : 0;
            $fltOTMinSpv += isset($arrDailyOT[$strIDEmployee]['total3']) ? $arrDailyOT[$strIDEmployee]['total3'] : 0;
            $fltOTMinSpv += isset($arrDailyOT[$strIDEmployee]['total4']) ? $arrDailyOT[$strIDEmployee]['total4'] : 0;
        }
        //besar tunjangan lembur spv
        $fltOvertimeSpv = $fltOTMinSpv / 60 * $fltHalfOTRate;
    } else {
        $fltOTMinSpv = $fltOvertimeSpv = 0;
    }
    //----------------------------------------------------------------------------------------------
    $arrResult['total_ot_1'] = $intL1Min;
    $arrResult['total_ot_2'] = $intL2Min;
    $arrResult['total_ot_3'] = $intL3Min;
    $arrResult['total_ot_4'] = $intL4Min;
    $arrResult['total_ot_day'] = $intOTDay;
    return $arrResult;
}

/**
 * @param $strTime
 * @param $intFlagInOut
 *
 * @return mixed
 */
function roundOvertimeInOut($strTime, $intFlagInOut) {
    global $db;
    $strResult = $strTime;
    $arrOvertimeSetting = array();
    if ($db->connect()) {
        $strSQL = "SELECT code, value, round_up FROM setting_overtime;";
        $res = $db->execute($strSQL);
        while ($row = $db->fetchrow($res)) {
            $arrOvertimeSetting[$row['code']] = $row;
        }
    }
    if (isset($strTime) && $strTime !== '') {
        # start time
        if ($intFlagInOut === 1) {
            $strInOutCode = 'ot_in_round_up';
        }
        # finish time
        else if ($intFlagInOut === 0) {
            $strInOutCode = 'ot_out_round_up';
        }
        # round_up === true => strtime + value - (minute % value)
        # round_down === false => strtime - (minute % value)
        $strResult = ($arrOvertimeSetting[$strInOutCode]['round_up'] == 't') ?
            date('H:i', strtotime($strTime) + ($arrOvertimeSetting[$strInOutCode]['value']*60) - ((date('i', strtotime($strTime)) % $arrOvertimeSetting[$strInOutCode]['value'])*60)) :
            date('H:i', strtotime($strTime) - ((date('i', strtotime($strTime)) % $arrOvertimeSetting[$strInOutCode]['value'])*60));
    }
    return $strResult;
}
?>
