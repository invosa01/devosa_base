<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
//--------------MAIN-----------------------------
$db = new CdbClass;
$db->connect();
if ($_POST['dataID'] == "") {
    $strJoinDate = $_POST['join'];
    $strBirthday = $_POST['birthday'];
    $companyId = $_POST['company_id'];
    if ($companyId == "") {
        $companyId = 23; // Default ICW
    }
    $arrDateJoin = explode($_SESSION['sessionDateSetting']['date_sparator'], $strJoinDate);
    $strJoinYear = $arrDateJoin[$_SESSION['sessionDateSetting']['pos_year']];
    $strJoinMonth = $arrDateJoin[$_SESSION['sessionDateSetting']['pos_month']];

    $arrDateBirth = explode($_SESSION['sessionDateSetting']['date_sparator'], $strBirthday);
    $strBirthYear = $arrDateBirth[$_SESSION['sessionDateSetting']['pos_year']];
    echo getNextId($db, $strJoinMonth, $strJoinYear, $strBirthYear, $companyId);
    die();
}
//------------FUNCTIONS----------------------------
/*
 * FUNTION GENERTATE NIK UNTUK INDOCIPTA WISSESA
 * Sanusi, A. 28 Oktober 2016
 * ICW FORMAT : JJBBXXXX ==> JJ : 2 Digit Akhir Tahun Join, BB : 2 Digit Akhir Tahun Lahir, XXXX : No Urut 0001,dst.
 * BAJ FORMAT : JJJJjjXXX ==> JJJJ : 4 Digit Tahun Join, jj: 2 Digit Bulan Join, XXX : Nomor Urut 001, dst.
 */
function getNextId($db, $monthJoin, $yearJoin, $yearBirth, $companyId)
{
    $newid = "";
    $awalan = "";
    $curid = 0;
    $strIDYearJoin = substr($yearJoin, -2);
    $strIDYearBirth = substr($yearBirth, -2);
    //$companyCode = getCompanyCode($db, $companyId);
    $digit = 0;
    $awalan = "";
    If ($companyId == 24) {
        $digit = 7; //Mulai Karakter ke 7 Adalah Nomor Urut
        $counter_length = 3;
        $awalan = $yearJoin . $monthJoin;
    } else {
        $digit = 5; //( ICW : 3 karakter akhir = no urut)
        $counter_length = 4;
        $awalan = $strIDYearJoin . $strIDYearBirth;
    }
    $strSQL = "SELECT MAX (seq) AS last_id FROM (
SELECT employee_id, id_company, substr(employee_id, $digit) AS  seq
FROM hrd_employee AS t1) AS t2 WHERE id_company = $companyId ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $curid = intval($rowDb["last_id"]);
        $curid = $curid + 1;
        $curid_length = strlen($curid);
        //Jika Melebihi Batas Counter maka ditambah 1 karakter digit lagi. Ex. 201606.999 Next New Employee 201710.1000
        If ($curid_length > $counter_length) {
            $counter_length = $counter_length + 1;
        }
        $newid = $awalan . str_pad($curid, $counter_length, "0", STR_PAD_LEFT);
    }
    return $newid;
}
?>