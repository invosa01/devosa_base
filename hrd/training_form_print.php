<?php
if (!session_id()) {
    include_once('../global/session.php');
}
include_once('global.php');
/*
  header ('Pragma: no-cache');
  header ('Content-Type: application/vnd.ms-word');
  header ('Content-Disposition: attachment; filename="training_form.doc"');  
  */
$db = new CdbClass;
if ($db->connect()) {
    global $_REQUEST;
    // header
    echo "
      <html>
        <body>
    ";
    $i = 0;
    foreach ($_REQUEST AS $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $strSQL = "
          select t2.*, t1.id, t3.employee_name, t3.position_code, td.division_name,
            tp.topic as topic_name, te.employee_name as manager_name,
            t1.training_date, t1.training_date_thru, t1.request_date,
            t1.result
          from hrd_training_request as t1 
            inner join (
              SELECT * FROM hrd_training_request_participant
              WHERE id = '$strValue'
            ) as t2 on t1.id = t2.id_request 
            inner join hrd_employee as t3 on t2.id_employee = t3.id
            left join hrd_training_topic as tp ON t1.id_topic = tp.id 
            left join hrd_division as td ON t3.division_code = td.division_code 
            left join hrd_employee as te ON t1.id_employee = te.id 
          ";
            $resDb = $db->execute($strSQL);
            if ($rowDb = $db->fetchrow($resDb)) {
                $i++;
                $strDate = pgDateFormat($rowDb['request_date'], "d M Y");
                $strEmployeeName = "" . $rowDb['employee_name'];
                $strJabatan = $rowDb['position_code'] . "";
                $strDivisi = $rowDb['division_name'] . "";
                $strJudulKursus = $rowDb['topic_name'] . "";
                $strTanggalKursus = pgDateFormat($rowDb['training_date'], "d M Y");
                if ($rowDb['training_date'] != $rowDb['training_date_thru']) {
                    $strTanggalKursus .= "  Sampai " . pgDateFormat($rowDb['training_date_thru'], "d M Y");
                }
                $strNomorKursus = "";
                $strBiayaKursus = standardFormat($rowDb['cost']);
                $strBiayaLain = standardFormat($rowDb['other_cost']);
                $strManagerName = "";// $rowDb['manager_name']. "";
                $strTrainingNote = nl2br($rowDb['result']) . "";
                if ($i > 1) {
                    echo "<span><br clear=all style='mso-special-character:line-break;page-break-before:always'> </span>";
                }
                echo "<p><table border=0 class=\"slip\"><tr><td>";
                $tbsPage = new clsTinyButStrong;
                $tbsPage->LoadTemplate(getTemplate("training_form_print.html"));
                $tbsPage->noErr = false;
                $tbsPage->Show(TBS_OUTPUT);
                echo "</td></tr></table></p><br>\n \n";
            }
        }
    }
    echo "
        </body>
      </html>
    ";
}
?>