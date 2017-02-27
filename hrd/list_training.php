<?php
//---- INISIALISASI ----------------------------------------------------
$strWordsInputTrainingRequest = getWords("input training request");
$strWordsTrainingRequestList = getWords("training request list");
$strWordsApprovedTraining = getWords("approved training");
$strWordsTrainingReport = getWords("training report");
$strWordsDepartment = getWords("department");
$strWordsUnit = getWords("unit");
$strWordsEmployee = getWords("requested by") . " (" . getWords("employee") . ") ";
$strWordsTrainingPlan = getWords("training plan");
$strWordsTrainingProfile = getWords("training profile");
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
$strWordsParticipant = getWords("participant");
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
$strDataDetail = "";
$strPaging = "";
$strHidden = "";
$strBtnPrint = "";
$intTotalData = 0;
$bolLimit = true; // default, tampilan dibatasi (paging)
$strButtons = "";
//----------------------------------------------------------------------
// fungsi buat nentuin apakah standard format atau gak
function cekStandardFormat($strText, $bolDec = true, $intDec = 2)
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

//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getData($db, &$intRows, $strFilterYear, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $ARRAY_REQUEST_STATUS;
  $intRows = 0;
  $strResult = "";
  $i = 0;
  $fltTotal = 0;
  $intTotalParticipant = 0;
  $fltGrandTotal = 0;
  // cari dulu partisipannya
  $arrParticipant = [];
  $strSQL = "SELECT t3.id_request, t4.employee_name FROM hrd_training_request_participant AS t3 ";
  $strSQL .= "LEFT JOIN hrd_training_request AS t1 ON t1.id = t3.id_request ";
  $strSQL .= "LEFT JOIN hrd_department AS t2 ON t1.department_code = t2.department_code ";
  $strSQL .= "LEFT JOIN hrd_employee AS t4 ON t3.id_employee = t4.id ";
  $strSQL .= "INNER JOIN hrd_training_request_detailtime as trd on t1.id = trd.id_request ";
  $strSQL .= "WHERE t1.status=" . REQUEST_STATUS_APPROVED . " AND t3.status = 0 $strKriteria ";
  $strSQL .= "AND EXTRACT(year FROM trd.trainingdate) = '$strFilterYear' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if (isset($arrParticipant[$rowDb['id_request']])) {
      $arrParticipant[$rowDb['id_request']][] = $rowDb['employee_name'];
    } else {
      $arrParticipant[$rowDb['id_request']][0] = $rowDb['employee_name'];
    }
  }
  $strSQL = "SELECT t1.*, t2.department_name FROM hrd_training_request AS t1 ";
  $strSQL .= "LEFT JOIN hrd_department AS t2 ON t1.department_code = t2.department_code ";
  $strSQL .= "INNER JOIN hrd_training_request_detailtime as trd on t1.id = trd.id_request ";
  $strSQL .= "WHERE t1.status=" . REQUEST_STATUS_APPROVED . " AND t1.training_status  = 0 $strKriteria ";
  $strSQL .= "AND EXTRACT(year FROM trd.trainingdate) = '$strFilterYear' ";
  $strSQL .= "ORDER BY $strOrder trd.trainingdate, t2.department_name ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strRowspan = "";
    $strParticipant = "";
    $intParticipant = 0;
    if (isset($arrParticipant[$rowDb['id']])) {
      $intParticipant = count($arrParticipant[$rowDb['id']]);
      foreach ($arrParticipant[$rowDb['id']] AS $id => $strName) {
        if ($strParticipant != "") {
          $strParticipant .= "<br>";
        }
        $strParticipant .= $strName;
      }
    }
    $strResult .= "<tr valign=top title=\"" . $rowDb['topic'] . "\">\n";
    $strResult .= "  <td align=right>$intRows.&nbsp;</td>\n";
    $strResult .= "  <td>" . $rowDb['department_code'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['topic'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $strParticipant . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $intParticipant . "&nbsp;</td>";
    $strResult .= "  <td>" . nl2br($rowDb['purpose']) . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['trainer'] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . pgDateFormat($rowDb['training_date'], "d-M-y") . "&nbsp;</td>\n";
    $strResult .= "  <td align=right>" . cekStandardFormat($rowDb['cost']) . "</td>";
    $strResult .= "</tr>\n";
    $fltTotal += $rowDb['cost'];
    $intTotalParticipant += $intParticipant;
    $fltGrandTotal += ($rowDb['cost'] * $intParticipant);
  }
  if ($intRows > 0) {
    // tampilkan total
    $strResult .= " <tr valign=top>\n";
    $strResult .= "  <td colspan=4 align=right><strong>" . $words['total'] . "</strong>&nbsp;</td>\n";
    $strResult .= "  <td align=center>$intTotalParticipant&nbsp;</td>\n";
    $strResult .= "  <td colspan=3 align=right>&nbsp;</td>\n";
    $strResult .= "  <td align=right><strong>" . cekStandardFormat($fltTotal) . "</strong></td>\n";
    $strResult .= " </tr>\n";
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  return $strResult;
} // showData
?>
