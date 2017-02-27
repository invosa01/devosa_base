<?php
if (!session_id()) {
    session_start();
}
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
//header ('Pragma: no-cache');
//header ('Content-Type: application/vnd.ms-word');
//header ('Content-Disposition: attachment; filename="salary_slip.doc"');
$db = new CdbClass;
if ($db->connect()) {
    $i = 0;
    foreach ($_REQUEST AS $kode => $value) {
        if (substr($kode, 0, 5) == 'chkID') {
            $strSQL = "SELECT t1.*, t2.*, t3.position_name, t4.section_name, ";
            $strSQL .= "t2.\"bank\", t2.bank_account, t6.salary_date, ";
            $strSQL .= "t5.department_name, t2.function, t6.date_thru ";
            $strSQL .= "FROM hrd_salary_detail AS t1 ";
            $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
            $strSQL .= "LEFT JOIN hrd_position AS t3 ON t2.position_code = t3.position_code ";
            $strSQL .= "LEFT JOIN hrd_section AS t4 ON t2.section_code = t4.section_code ";
            $strSQL .= "LEFT JOIN hrd_department AS t5 ON t2.department_code = t5.department_code ";
            $strSQL .= "LEFT JOIN hrd_salary_master AS t6 ON t1.id_salary_master = t6.id ";
            $strSQL .= "WHERE t1.id = '$value' AND t2.employee_status <> " . STATUS_OUTSOURCE . " ";
            $resDb = $db->execute($strSQL);
            if ($rowDb = $db->fetchrow($resDb)) {
                $i++;
                $stremployee_name = "" . $rowDb['employee_name'];
                $stremployee_id = "" . $rowDb['employee_id'];
                $strDepartmentName = "" . $rowDb['department_name'];
                $strBarcode = $rowDb['barcode'];
                $arr = str_split($strBarcode);
                if ($strBarcode !== "") {
                    for ($c = 0; $c < count($arr); $c++) {
                        if ($c == 0) {
                            $strBar = "<table border=1 cellspacing=0 cellpadding=3 align='center'><td align='center'>";
                        }
                        $strBar .= $arr[$c] . "<br>";
                    }
                    $strBar .= "</td></table>";
                }
                $strDataID = "" . $rowDb['id'];
                $arrData['dataPhoto'] = $rowDb['photo'];
                //tampilkan foto
                if ($arrData['dataPhoto'] == "") {
                    $strDataPhoto = "<img src='photos/dummy.gif'>";
                } else {
                    if (file_exists("photos/" . $arrData['dataPhoto'])) {
                        $strDataPhoto = "<img src=\"employee_photo.php?dataID=$strDataID\">";
                    } else {
                        $strDataPhoto = "<img src='photos/dummy.gif'>";
                    }
                }
                echo "<p><table border=0 class=\"slip\"><tr><td>";
                $tbsPage = new clsTinyButStrong;
                $tbsPage->LoadTemplate(getTemplate("employee_name_tag.html"));
                $tbsPage->noErr = false;
                $tbsPage->Show(TBS_OUTPUT);
                echo "</td></tr></table></p><br>\n \n";
                echo "<span><br clear=all style='mso-special-character:line-break;page-break-before:always'> </span>";
            }
        }
    }
}
?>
