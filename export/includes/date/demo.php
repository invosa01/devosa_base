<?php
include_once("date.php");
$dt1 = new clsDate(); //current date and time
$dt2 = new clsDate(2006, 1, 1);
$dt3 = new clsDate(2006, 3, 3, 11, 30, 0); //also can set time 
//kembali ke 3 bulan sebelumnya $dt1
$dt1->addMonths(-3);
//tambahkan 1 bulan kedepan $dt2
$dt2->addMonths(1);
//tambahkan 2 tahun kedepan $dt2
$dt2->addYears(2);
//tambahkan 2 jam pada $dt3
$dt3->addHours(2);
echo "Hasil \$dt1 : " . $dt1->format() . "<br />";
echo "Jumlah hari dalam bulan " . $dt1->format("F") . " adalah " . $dt1->daysInMonth() . "<br />";
echo "Hasil \$dt2 : " . $dt2->format() . "<br />";
echo "Jumlah hari dalam bulan " . $dt2->format("F-Y") . " adalah " . $dt2->daysInMonth() . "<br />";
if ($dt2->isLeapYear()) {
    echo "Tahun " . $dt2->format("Y") . " adalah tahun kabisat" . "<br />";
} else {
    echo "Tahun " . $dt2->format("Y") . " bukan merupakan tahun kabisat" . "<br />";
}
echo "Hasil \$dt3 (with time format) : " . $dt3->format("Y-m-d H:i:s") . "<br />";
if ($dt1->equals($dt3)) {
    echo "\$dt1 sama dengan \$dt3" . "<br />";
} else {
    echo "\$dt1 tidak sama dengan \$dt3" . "<br />";
}
echo "-----------------------------------------------------<br />";
echo "\$dt1 (with time format) : " . $dt1->format("Y-m-d H:i:s") . "<br />";
echo "\$dt2 (with time format) : " . $dt2->format("Y-m-d H:i:s") . "<br />";
echo "-----------------------------------------------------<br />";
echo "Selisih antara \$dt1 dan \$dt2 dalam interval tahun adalah: " . $dt1->dateDiff($dt2, "year") . " tahun<br />";
echo "Selisih antara \$dt1 dan \$dt2 dalam interval bulan adalah: " . $dt1->dateDiff($dt2, "month") . " bulan<br />";
echo "Selisih antara \$dt1 dan \$dt2 dalam interval minggu adalah: " . $dt1->dateDiff($dt2, "week") . " minggu<br />";
echo "Selisih antara \$dt1 dan \$dt2 dalam interval hari adalah: " . $dt1->dateDiff($dt2) . " hari<br />";
echo "Selisih antara \$dt1 dan \$dt2 dalam interval jam adalah: " . $dt1->dateDiff($dt2, "hour") . " jam<br />";
echo "Selisih antara \$dt1 dan \$dt2 dalam interval menit adalah: " . $dt1->dateDiff($dt2, "minute") . " menit<br />";
echo "Selisih antara \$dt1 dan \$dt2 dalam interval detik adalah: " . $dt1->dateDiff($dt2, "second") . " detik<br />";
?>