<?php
/*
  Daftar fungsi-fungsi (super) global, yang terkait dengan proses2 formulir
  termasuk pengambilan nomor
    Author: Yudi K.
*/
function formatDate($params)
{
    if (!is_numeric($params) && !is_string($params) && $params != "") {
        extract($params);
    } else {
        $value = $params;
    }
    return pgDateFormat($value, "d-M-y");
}

function formatTime($params)
{
    if (!is_numeric($params) && !is_string($params) && $params != "") {
        extract($params);
    } else {
        $value = $params;
    }
    return minuteToTime($value, true);
}

?>
