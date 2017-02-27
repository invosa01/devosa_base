<?php
include_once('../includes/datagrid2/datagrid.php');

/*
  SEDIKIT MODIFIKASI DATAGRID PUNYA DEDY
  BIAR GAK PERLU MENGUBAH YANG ASLI
  AUTHOR : Yudi (2009-03-06)
*/

class cDataGridNew extends cDataGrid
{

    // modifikasi tampilan checkbox, agar ada title sebagai tanda dari ID tersebut
    function _printCheckbox($name, $data, $counter)
    {
        if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
            return "<div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" type=\"checkbox\"
          name=\"" . $this->checkboxItemID . $counter . "\" id=\"" . $this->checkboxItemID . $counter . "\"
          value=\"" . $data . "\" title=\"key = " . $data . "\"
          onClick=\"o" . $this->gridName . ".itemListClicked(this)\"></label></div>";
        } else {
            return "";
        }
    }

    /*you can inherit this function to created your own TR class or style*/
    // menambahkan warna berdasarkan statusnya, jika ada

    function getCSSClassName($flag, $bolOrphan = false)
    {
        if ($bolOrphan) {
            $strClass = "class=\"bgDenied\"";
            $strDisabled = "";
        } else {
            switch ($flag) {
                case 0 :
                    $strClass = "class=\"bgNewData\"";
                    break;
                case 1 :
                    $strClass = "class=\"bgVerifiedData\"";
                    break;
                case 2 :
                    $strClass = "class=\"bgCheckedData\"";
                    break;
                case 3 : // disetujui
                    $strClass = "class=\"\" ";
                    break;
                case 4 : // ditolak
                    $strClass = "class=\"bgDenied\"";
                    break;
                default :
                    $strClass = "";
                    break;
            }
        }
        return $strClass;
    }

    // menentukan kelas CSS berdasarkan status approval

    function printOpeningRow($intRows, $rowDb)
    {
        $strResult = "";
        $strClass = "";
        if (isset($rowDb['status'])) {
            $strStatus = strtolower($rowDb['status']);
            if (!is_numeric($strStatus)) // handle untuk yang sudah langsung diconvert ke text
            {
                if ($strStatus == strtolower(getWords("new"))) {
                    $strStatus = 0;
                } else if ($strStatus == strtolower(getWords("verified"))) {
                    $strStatus = 1;
                } else if ($strStatus == strtolower(getWords("checked"))) {
                    $strStatus = 2;
                } else if ($strStatus == strtolower(getWords("approved"))) {
                    $strStatus = 3;
                } else if ($strStatus == strtolower(getWords("denied"))) {
                    $strStatus = 4;
                }
            }
            $strClass = $this->getCSSClassName($strStatus, false); // common
        }
        $strResult .= " <tr $strClass valign=\"top\">";
        return $strResult;
    }
}

?>