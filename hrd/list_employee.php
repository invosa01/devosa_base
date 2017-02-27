<?php
//---- INISIALISASI ----------------------------------------------------
$strWordsInputTrainingRequest = getWords("input training request");
$strWordsTrainingRequestList = getWords("training request list");
$strWordsApprovedTraining = getWords("approved training");
$strWordsTrainingReport = getWords("training report");
$strWordsDepartment = getWords("department");
$strWordsUnit = getWords("unit");
$strWordsEmployeeID = getWords("employee id");
$strWordsName = getWords("name");
$strWordsTrainingPlan = getWords("training plan");
$strWordsTrainingTopic = getWords("training topic");
$strWordsTrainingCategory = getWords("training category");
$strWordsTrainingType = getWords("training type");
$strWordsTrainingStatus = getWords("training status");
$strWordsPurpose = getWords("purpose");
$strWordsLocation = getWords("location");
$strWordsInstitution = getWords("institution");
$strWordsTrainer = getWords("trainer");
$strWordsInstructor = getWords("instructor");
$strWordsExpectedResult = getWords("expected result");
$strWordsExpectedDate = getWords("expected date");
$strWordsParticipants = getWords("participant");
$strWordsRequestStatus = getWords("request status");
$strWordsRequestNumber = getWords("request no.");
$strWordsTrainingDate = getWords("training date");
$strWordsDate = getWords("request date");
$strWordsShowData = getWords("show data");
$strWordsPrint = getWords("print");
$strWordsNO = getWords("no.");
$strWordsNumber = getWords("number");
$strWordsCost = getWords("cost");
$strWordsYear = getWords("year");
$strWordsReport = getWords("training report");
$strDataDetail = "";
$strPaging = "";
$strHidden = "";
$strBtnPrint = "";
$intTotalData = 0;
$bolLimit = true; // default, tampilan dibatasi (paging)
$strButtons = "";
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi buat nentuin apakah standard format atau gak
function cekStandardFormat1($strText, $bolDec = true, $intDec = 2)
{
    global $_REQUEST;
    if (isset($_REQUEST['btnExcel'])) // untuk tampil di excel
    {
        $strResult = $strText;
    } else {
        $strResult = standardFormat($strText, $bolDec, $intDec) . "&nbsp;";
    }
    return $strResult;
}

// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getData1($db, &$intRows, $strFilterYear, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
{
    global $words;
    global $bolPrint;
    global $strPaging;
    global $intTotalData;
    global $intRowsLimit;
    global $bolIsEmployee;
    $intRowsLimit = getSetting("rows_per_page");
    if (!is_numeric($intRowsLimit)) {
        $intRowsLimit = 50;
    }
    if (!is_numeric($strFilterYear)) {
        $strFilterYear = date("Y");
    }
    $intRows = 0;
    $strResult = "";
    // cari total data
    // diambil dari training participant
    $intTotal = 0;
    $strSQL = "
        SELECT count(t1.id) AS total 
        FROM hrd_training_request_participant AS t1 
        LEFT JOIN hrd_training_request AS t3 ON t1.id_request = t3.id 
        LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id 
        INNER JOIN hrd_training_request_detailtime as trd on t3.id = trd.id_request 
        WHERE t3.status = '" . REQUEST_STATUS_APPROVED . "' AND t3.training_status  = 0
          AND t1.status = 0 AND t2.flag=0 $strKriteria 
          AND EXTRACT(year FROM trd.trainingdate) = '$strFilterYear' 
    ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        if (is_numeric($rowDb['total'])) {
            $intTotal = $rowDb['total'];
        }
    }
    $strPaging = getPaging($intPage, $intTotal, "javascript:goPage('[PAGE]')");
    if ($strPaging == "") {
        $strPaging = "1&nbsp;";
    }
    $intStart = (($intPage - 1) * $intRowsLimit);
    //-----------------
    $tmpIDEmployee = "";
    $strSQL = "
        SELECT t3.*, t1.id_employee, t1.id as id_participant, t1.cost as cost_participant, 
          t1.note, t1.evaluation, t1.evaluation2, 
          t2.employee_id, t2.employee_name, t2.department_code, 
          t2.section_code FROM hrd_training_request_participant AS t1 
        LEFT JOIN hrd_training_request AS t3 ON t1.id_request = t3.id 
        LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id 
        INNER JOIN hrd_training_request_detailtime as trd on t3.id = trd.id_request 
        WHERE t3.status = '" . REQUEST_STATUS_APPROVED . "' AND t3.training_status  = 0
          AND t1.status = 0 AND t2.flag=0 $strKriteria 
          AND EXTRACT(year FROM trd.trainingdate) = '$strFilterYear' 
        ORDER BY $strOrder t2.employee_name, t2.id, t3.training_date DESC 
    ";
    if ($bolLimit) {
        $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
    }
    $fltTotal = 0;
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strTgl = pgDateFormat($rowDb['training_date'], "d-M-y");
        if ($rowDb['training_date_thru'] != "") {
            $strTgl .= " -- " . pgDateFormat($rowDb['training_date_thru'], "d-M-y");
        }
        $strResult .= " <tr valign=top>";
        if (!$bolPrint) {
            //$strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" .$rowDb['id']. "\"></td>\n";
            $strResult .= "  <td align=right>$intRows.&nbsp;</td>\n";
        }
        if ($tmpIDEmployee != $rowDb['id_employee']) {
            $strResult .= "  <td>" . $rowDb['employee_id'] . "&nbsp;</td>\n";
            $strResult .= "  <td nowrap>" . $rowDb['employee_name'] . "&nbsp;</td>\n";
            $strResult .= "  <td>" . $rowDb['department_code'] . "&nbsp;</td>\n";
            $strResult .= "  <td>" . $rowDb['section_code'] . "&nbsp;</td>\n";
            $tmpIDEmployee = $rowDb['id_employee'];
        } else { // kosong aja
            $strResult .= "  <td colspan=4>&nbsp;</td>\n";
        }
        $strResult .= "  <td>" . $rowDb['topic'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['institution'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['trainer'] . "&nbsp;</td>";
        $strResult .= "  <td nowrap>" . $strTgl . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . cekStandardFormat1($rowDb['cost_participant']) . "</td>";
        //$strResult .= "  <td>" .$rowDb['note']. "&nbsp;</td>";
        /*
        $strResult .= "  <td align=right nowrap>" .cekStandardFormat1($rowDb['evaluation'], true). "</td>";
        if (!$bolPrint) {
          $strResult .= "  <td nowrap align=center><a href='training_evaluation_edit.php?dataID=" .$rowDb['id_participant']. "'>" .getWords('view'). "</a>&nbsp;</td>";
        }
        $strResult .= "  <td align=right nowrap>" .cekStandardFormat1($rowDb['evaluation2'], true). "</td>";
        if (!$bolPrint) {
          $strResult .= "  <td nowrap align=center><a href='training_evaluation_edit2.php?dataID=" .$rowDb['id_participant']. "'>" .getWords('view'). "</a>&nbsp;</td>";
          $strCreateSharingSession = "<a href=\"training_sharing_session_edit.php?dataID=" .$rowDb['id_participant']."\">" .$words['create']."</a>";
        $strResult .= "  <td nowrap align=center>$strCreateSharingSession&nbsp;</td>\n";
        }
        */
        $fltTotal += $rowDb['cost'];
        //$strResult .= getDataPerRow($rowDb,$intRows);
        $strResult .= " </tr>\n";
    }
    // tambahkan nnilai total
    $intTotalData = $intRows;
    if ($intRows > 0) {
        $intSpan1 = ($bolPrint) ? 3 : 4;
        $intSpan2 = ($bolPrint) ? 3 : 6;
        $strResult .= " <tr valign=top>\n";
        $strResult .= "  <td colspan=5>&nbsp;</td>\n";
        $strResult .= "  <td colspan=$intSpan1 align=right><strong>" . $words['total'] . "</strong>&nbsp;</td>\n";
        $strResult .= "  <td align=right><strong>" . cekStandardFormat1($fltTotal) . "</strong></td>\n";
        //$strResult .= "  <td align=right colspan=$intSpan2>&nbsp;</td>\n";
        $strResult .= " </tr>\n";
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$intRows data", 0);
    }
    return $strResult;
} // getData
// fungsi untuk menghapus data
function deleteData1($db)
{
    global $_REQUEST;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $strSQL = "";
            $strSQL .= "DELETE FROM hrd_training_employee WHERE id = '$strValue'; ";
            $resExec = $db->execute($strSQL);
        }
    }
} //deleteData
?>
