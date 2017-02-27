<?php
// Author : Yudi K
// daftar fungsi-fungsi untuk modul admin
// beberapa istilah yang dipakai dalam parameter fungsi:
// $db --> objek CDbClass, sebagai objek database, biar lebih efektif
// $varname --> nama element/data yang dibuat
// $default --> default value yang akan ditampilkan, jika ada
// $extra --> baris/option tambahan, jika ada
// $criteria --> kriteria/query pemilihan dari database
// $action -> action tambahan yang menyertai element tersebut, misal onClick, dsb
// $listonly --> apakah hanya menampilkan daftar option atau tidak
// mengenerate combo list untuk daftar jenis shift group
function getUserGroupList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select name=\"$varname\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM \"allUserGroup\" $criteria ORDER BY module, \"groupName\" ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['groupCode'] . "\" $strSelect>[" . $rowDb['module'] . "] " . $rowDb['groupName'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getUserGroupList
// fungsi untuk generate data jenis user
function getUserTypeList($varname, $default = 0, $extra = "", $action = "")
{
    global $ARRAY_USER_TYPE;
    global $words;
    $strResult = "";
    $strResult .= "<select name=\"$varname\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_USER_TYPE);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i && $default != "") ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . $words[$ARRAY_USER_TYPE[$i]] . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getUserTypeList
// fungsi untuk generate data jenis role user
function getUserRoleList($varname, $default = 0, $extra = "", $action = "")
{
    global $ARRAY_USER_ROLE;
    global $words;
    $strResult = "";
    $strResult .= "<select name=\"$varname\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_USER_ROLE);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i && $default != "") ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . $words[$ARRAY_USER_ROLE[$i]] . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getUserRoleList
// fungsi untuk generate daftar modul2 yang ada
function getModuleList($varname, $default = 0, $extra = "", $action = "")
{
    global $ARRAY_MODULE_LIST;
    global $words;
    $strResult = "";
    $strResult .= "<select name=\"$varname\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_MODULE_LIST);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i && $default != "") ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . strtoupper($ARRAY_MODULE_LIST[$i]) . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getModuleList
?>