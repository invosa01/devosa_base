<?php
// khusus mengambil info kehadiran dari request ajax
include_once("global.php");
include_once("import_func.php");
$db = new CdbClass();
if ($db->connect()) {
    $dtNow = getdate();
    // jika ada session, lakukan pengecekan kapan terakhir
    if (isset($_SESSION['sessionUserID'])) {
        if (isset($_SESSION['sessionLastAttendanceUpdate'])) {
            $selisih = $dtNow[0] - $_SESSION['sessionLastAttendanceUpdate'];
            if ($selisih > 960) { // jika lewat dari 15 menit, baru diambil
                getAttendanceData($db);
                $_SESSION['sessionLastAttendanceUpdate'] = $dtNow[0];
            }
        } else {
            getAttendanceData($db);
            $_SESSION['sessionLastAttendanceUpdate'] = $dtNow[0];
        }
    } else {
        getAttendanceData($db);
    } // langsung ambil
    // cek data kemarin, untuk memastikan apakah ada data attendance kemarin lengkap atau tidak
    // cek apakah ada yang kemarin tidak ada data attendance, dan tidak ada informasi absensi/cuti
    $strYesterday = getNextDate(date("Y-m-d"), -4);
    $strYesterday = pgDateFormat($strYesterday, "Y-m-d");
    // cek dulu di setting, kapan terakhir pengecekan untuk hari itu.
    $strLast = getSetting("last_check_attendance");
    if (!validStandardDate($strLast)) {
        // insert data
        $strSQL = "DELETE FROM all_setting WHERE code = 'last_check_attendance'; ";
        $strSQL .= "INSERT INTO all_setting (code, value) VALUES('last_check_attendance', '$strYesterday') ";
        $resExec = $db->execute($strSQL);
        // panggil proses -- activity.php
        recheckAttendanceData($db, $strYesterday, $strYesterday);
    } else {
        if ($strLast < $strYesterday) { // kalau kemarin belum diproses
            $strLast = getNextDate($strLast);
            recheckAttendanceData($db, $strLast, $strYesterday);
            saveSetting("last_check_attendance", $strYesterday);
        }
    }
    // cek apakah ada request yang gak diverified oleh ATASAN, dan melebihi batas
    // otomatis denied oleh system
    $strYesterday = getNextDate(date("Y-m-d"), -((2 * INT_LIMIT_APPROVAL) + 4)); // pertimbangan mungkin ada hari libur
    $strYesterday = pgDateFormat($strYesterday, "Y-m-d");
    // cek dulu di setting, kapan terakhir pengecekan untuk hari itu.
    $strLast = getSetting("last_check_request");
    if (!validStandardDate($strLast)) {
        // insert data
        $strSQL = "DELETE FROM all_setting WHERE code = 'last_check_request'; ";
        $strSQL .= "INSERT INTO all_setting (code, value) VALUES('last_check_request', '$strYesterday') ";
        $resExec = $db->execute($strSQL);
        // panggil proses -- activity.php
        recheckRequestData($db, $strYesterday, $strYesterday);
    } else {
        if ($strLast < $strYesterday) { // kalau kemarin belum diproses
            $strLast = getNextDate($strLast);
            saveSetting("last_check_request", $strYesterday);
            recheckRequestData($db, $strLast, $strYesterday);
        }
    }
    //recheckRequestData($db, $strLast, $strYesterday); // UNTUK SEMENTARA, HARUS DIHAPUS
    if (isset($_REQUEST['show'])) {
        echo "Last Update: " . date("d-M-y H:i:s");
    }
}
?>