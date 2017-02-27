<?php
/*
  Fungsi - fungsi terkait dengan employee
  Siapa tahu bisa jadi kelas ..... :D
   Author: Yudi K.
*/
// fungsi untuk mencari ID dari employee dengan kode tertentu (bukan employee_id)
// jika tidak ketemu, kirimkan error
function getIDEmployee($db, $code)
{
    $strResult = "";
    if ($code != "") {
        $strSQL = "SELECT id FROM hrd_employee WHERE employee_id = '$code' AND flag = 0 ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strResult = $rowDb['id'];
        }
    }
    return $strResult;
} // getemployee_idByCode
?>