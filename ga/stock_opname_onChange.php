<?php
/*
Author : Dily Same Alie
Date 	 : 23/11/2011
Desc	 : skrip ini adalah skrip tambahan yang di includkan ke java skrip
            skrip ini berfungi untuk menghandel perintah onChange dan menmpilkan data yang dichange
Relasi : stock_opname_edit.php
File	 : stock_opname_edit_onchange.php
*/
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../classes/ga/asset_moving.php');
//---------------------------------------------
$db = new CdbClass;
$tbl = new cGaAssetMoving;
$arrData = [];
$strSQL = "SELECT i.item_stock AS item_stock, i.id AS id FROM ga_item AS i
	         LEFT JOIN ga_item_category AS ic ON i.id_category=ic.id WHERE 1=1 AND ic.category_type='Consumable'";
$arrData = $tbl->query($strSQL);
echo "arrDat = new Array();";
foreach ($arrData AS $i => $row) {
  echo "
      arrDat['" . $row['id'] . "'] = new Array(\"" . $row['item_stock'] . "\");
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