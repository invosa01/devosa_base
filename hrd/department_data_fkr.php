<?php
/*
 Generate data departent, section dsb untuk diolah oleh javascript

Author: Yudi K
Versi 1:
Update: 2005-02-03
*/
include("global.php");
$db = new CdbClass;
if ($db->connect()) {
    // cari data department
    $strDepartment = "var arrDepartment = new Array();\n";
    $strSQL = "SELECT * FROM hrd_department ORDER BY department_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strDepartment .= "arrDepartment['" . $rowDb['department_code'] . "'] = \"" . $rowDb['division_code'] . "\";\n";
    }
    // cari data section
    $strSection = "var arrSection = new Array();\n";
    $strSQL = "SELECT * FROM hrd_section ORDER BY section_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strSection .= "arrSection['" . $rowDb['section_code'] . "'] = new Array(\"" . $rowDb['division_code'] . "\",\"" . $rowDb['department_code'] . "\");\n";
    }
    // cari data subsection
    $strSubSection = "var arrSubSection = new Array();\n";
    $strSQL = "SELECT * FROM hrd_sub_section ORDER BY sub_section_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strSubSection .= "arrSubSection['" . $rowDb['sub_section_code'] . "'] = new Array(\"" . $rowDb['division_code'] . "\",\"" . $rowDb['department_code'] . "\", \"" . $rowDb['section_code'] . "\");\n";
    }
}
echo "$strDepartment\n";
echo "$strSection\n";
echo "$strSubSection\n";
?>
