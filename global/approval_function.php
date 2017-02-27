<?php
/*
  Daftar fungsi-fungsi (super) global, yang terkait dengan proses approval
    Author: Yudi K.
*/
// fungsi untuk meng-approve sebuah data
function approveTempData($db, $strTable, $strDataID)
{
    if ($strDataID != "" && $strTable != "") {
        // cek dulu flagnya, jika 0, gak perlu diapprove
        $strSQL = "SELECT * FROM \"$strTable\" WHERE id = '$strDataID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['flag'] == 0) { // nothing
            } else {
                if ($rowDb['linkID'] == "") { // baru, bukan update
                    $strSQL = "UPDATE \"$strTable\" SET flag = 0, \"linkID\" = NULL ";
                    $strSQL .= "WHERE id = '$strDataID' ";
                    $resExec = $db->execute($strSQL);
                } else { // update data nih
                    $strSQL = "DELETE FROM \"$strTable\" WHERE id = '" . $rowDb['linkID'] . "'; \n";
                    $strSQL .= "UPDATE \"$strTable\" SET flag = 0, id = \"linkID\", \"linkID\" = NULL ";
                    $strSQL .= "WHERE id = '$strDataID' ";
                    $resExec = $db->execute($strSQL);
                }
            }
        }
    }
} // ID dari data yang akan diapprove
// fungsi untuk mengambil ID dari data temporer
// cek dulu, jika ada apakah adatemporernya, jika ada langsung baca ID
// jika belum ada, buat dulu tabel temporernya
// mengembalikan id dari duplikatnya, flag adalah nilai flag yang diinginkan
// strFieds adalah daftar fieds, strDataID adalah ID dari data yang diduplikat
function getTempData($db, $strTable, $strFields, $strDataID, $strFlag = 2)
{
    $intResult = "";
    // cari dulu apakah dah ada temporernya
    $strSQL = "SELECT id FROM \"$strTable\" WHERE \"linkID\" = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        $intResult = $rowDb['id'];
    }
    if ($intResult == "") {
        $strSQL = "INSERT INTO \"$strTable\"  ($strFields, flag, \"linkID\") ";
        $strSQL .= "SELECT $strFields, $strFlag, id FROM \"$strTable\" ";
        $strSQL .= "WHERE id = '$strDataID' ";
        $resExec = $db->execute($strSQL);
        // car IDnya
        $strSQL = "SELECT id FROM \"$strTable\" WHERE \"linkID\" = '$strDataID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $intResult = $rowDb['id'];
        }
    }
    return $intResult;
}//getTempData
?>
