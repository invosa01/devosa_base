<?php
session_start();
include_once('global.php');
include_once('form_object.php');
include_once('import_func.php');
//include_once(getTemplate("words.inc"));
//--- FILE PHP UNTUK MENJALANKAN PERINTAH PENGAMBILAN KEHADIRAN SECARA OTOMATIS
//--- DIPANGGIL OLEH CRON, MEMBACA SESUAI SETTING
//---- INISIALISASI ----------------------------------------------------
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    // ------ AMBIL DATA KRITERIA -------------------------
    getAttendanceData($db);
}
?>