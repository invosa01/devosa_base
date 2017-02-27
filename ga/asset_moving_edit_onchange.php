<?php
/*
Author : Dily Same Alie
Date 	 : 23/11/2011
Desc	 : skrip ini adalah skrip tambahan yang di includkan ke java skrip
            skrip ini berfungi untuk menghandel perintah onChange dan menmpilkan data yang dichange
Relasi : asset_moving_edit.php
File	 : asset_moving_edit_onchange.php
*/
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../classes/ga/asset_moving.php');
//---------------------------------------------
$db = new CdbClass;
$tbl = new cGaAssetMoving;
$arrData = [];
$strSQL = "SELECT e.employee_name AS employee_name,i.* FROM ga_item AS i
             LEFT JOIN hrd_employee AS e ON i.id_employee=e.id ";
$arrData = $tbl->query($strSQL);
echo "arrDat = new Array();";
foreach ($arrData AS $i => $row) {
  echo "
      arrDat['" . $row['id'] . "'] = new Array(\"" . $row['id_employee'] . "\", \"" . $row['id_room'] . "\", \"" . $row['department_code'] . "\"
	  ,\"" . $row['employee_name'] . "\");
    ";
}
?>
// fungsi javascript untuk mengambil data dengan kriteria tertentu
function getInfo(no)
{
arrTmp = new Array("", 0);
if (no != "")
{
if (typeof arrDat[no] != 'undefined') arrTmp = arrDat[no];
}
return arrTmp;
}