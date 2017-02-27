<?php
/*
 Generate data untuk diolah oleh javascript
 Author: Yudi K
 Versi 1:
 Update: 2005-02-03
*/
session_start();
include_once("global.php");
// array untuk daftar conversi character
$strL = "abcdefghijklmnopqrstuvwxyz"; // daftar karakter lower case
$strU = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"; // daftar karakter upper case
// generate array daftar konversi
for ($i = 0; $i < 26; $i++) {
    if ($i < 13) {
        $arrEncoder[$strL[$i]] = $strL[$i + 13];
        $arrEncoder[$strU[$i]] = $strU[$i + 13];
    } else {
        $arrEncoder[$strL[$i]] = $strL[$i - 13];
        $arrEncoder[$strU[$i]] = $strU[$i - 13];
    }
}
$db = new CdbClass;
if ($db->connect()) {
    $strAllCanons = "";
    $strNickTokens = "";
    $i = 0;
    $strSQL = "SELECT id, employee_id, employee_name FROM hrd_employee WHERE 1=1 $strKriteriaCompany $strKriteriaOrganizational ORDER BY employee_id ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strAllCanons .= "\"" . $rowDb["employee_id"] . "\", ";
        $strAllCanons .= "\"" . $rowDb["employee_name"] . "\", ";
        $strNickTokens .= "[\"" . $rowDb["employee_id"] . "," . ($i * 2) . "\"], ";
        $i++;
    }
    $strAllCanons = "[" . $strAllCanons . "];\n";
    $strNickTokens = "[" . $strNickTokens . "];\n";
}
/*
$strNickTokens = nl2br($strNickTokens);
$strAllCanons  = nl2br($strAllCanons);
*/
echo "var AC_listStr = \"List\";\n";
echo "var AC_nickNameStr = \"Kode\";\n";
echo "var AC_nickTokens = $strNickTokens ";
echo "var AC_allCanons = $strAllCanons";
?>
