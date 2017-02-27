<?php
// Fungsi-fungsi untuk menghitung gaji
// Author: Yudi K.
// daftar link untk perhitungan gaji
$arrLink = [
    0 => "evaluation_operational.php",
    1 => "evaluation_general.php",
    2 => "evaluation_absence.php",
    3 => "evaluation_result.php",
    4 => "evaluation_feedback.php",
    5 => "evaluation_list.php",
];
$arrLinkProbation = [
    0 => "evaluation_probation.php",
    1 => "evaluation_feedback.php",
    2 => "evaluation_list_probation.php",
];
function getDataListDateFrom($default = null, $isHasEmpty = false, $emptyData = null)
{
  $tbl = new cModel("hrd_evaluation_criteria", getWords("evaluation criteria"));
  $arrData = $tbl->generateList(null, null, null, "date_from", "date_from", $isHasEmpty, $emptyData, "DISTINCT");
  if ($default != null || $default != "") {
    while (list($key, $val) = each($arrData)) {
      $temp = &$arrData[$key];
      if ($val['value'] == $default) {
        $temp['selected'] = true;
        $bolFound = true;
      } else {
        $temp['selected'] = false;
      }
    }
  }
  return $arrData;
}

function getDataListDateThru($default = null, $isHasEmpty = false, $emptyData = null)
{
  $tbl = new cModel("hrd_evaluation_criteria", getWords("evaluation criteria"));
  $arrData = $tbl->generateList(null, null, null, "date_thru", "date_thru", $isHasEmpty, $emptyData, "DISTINCT");
  if ($default != null || $default != "") {
    while (list($key, $val) = each($arrData)) {
      $temp = &$arrData[$key];
      if ($val['value'] == $default) {
        $temp['selected'] = true;
      } else {
        $temp['selected'] = false;
      }
    }
  }
  return $arrData;
}

// membuat submenu perhitungan gaji
// perlu input ID dari salary master
// intCurrent = halaman yang sedang dibuka, intStatus = status calculation
// intMenuType 0 utk menu karyawan tetap, 1 utk karyawan probation
function getEvaluationMenu($strDataID, $intCurrent = 1, $intStatus = 0, $intMenuType = 0)
{
  global $ARRAY_EVALUATION_PROCESS_PROBATION;
  global $ARRAY_EVALUATION_PROCESS;
  global $words;
  global $arrLink;
  global $arrLinkProbation;
  $strResult = "";
  $arrMenuType = ($intMenuType == 1) ? $ARRAY_EVALUATION_PROCESS_PROBATION : $ARRAY_EVALUATION_PROCESS;
  $arrLinkType = ($intMenuType == 1) ? $arrLinkProbation : $arrLink;
  $intTotal = count($arrMenuType);
  if ($intTotal > 1) { // finish tidak dianggap
    for ($i = 0; $i < ($intTotal); $i++) {
      if ($intCurrent == $i) { // saat ini, jdi tidak ada link
        $strResult .= " <b>[" . ($i + 1) . "] " . getWords($arrMenuType[$i]) . "</b>  |";
      } else {
        // untuk saat inni tiap employee boleh akses semua info
        //if ($i <= ($intStatus+1)) { // berikan link
        $strResult .= " <b><a href=\"" . $arrLinkType[$i] . "?dataID=$strDataID\">[" . ($i + 1) . "] " . getWords(
                $arrMenuType[$i]
            ) . "</a></b>  |";
        //} else {
        //  $strResult .= " [" .($i + 1)."] ".$words[$ARRAY_EVALUATION_PROCESS[$i]]. "  |";
        //}
      }
    }
    // hilangkan satu karakter terakhir
    if ($strResult != "") {
      $strResult = substr($strResult, 0, strlen($strResult) - 1);
    }
  }
  return $strResult;
}//getCalculationMenu
// membuat daftar link informasi proses
// perlu input ID dari salary master
// intCurrent = halaman yang sedang dibuka, intStatus = status calculation
// intMenuType 0 utk menu karyawan tetap, 1 utk karyawan probation
function getEvaluationProccess($strDataID, $intStatus = 0, $intMenuType = 0)
{
  global $ARRAY_EVALUATION_PROCESS_PROBATION;
  global $ARRAY_EVALUATION_PROCESS;
  global $words;
  global $arrLink;
  global $arrLinkProbation;
  $strResult = "";
  $strResult = "";
  $arrMenuType = ($intMenuType == 1) ? $ARRAY_EVALUATION_PROCESS_PROBATION : $ARRAY_EVALUATION_PROCESS;
  $arrLinkType = ($intMenuType == 1) ? $arrLinkProbation : $arrLink;
  $intTotal = count($arrMenuType);
  if ($intTotal > 0) { //
    for ($i = 0; $i < $intTotal; $i++) {
      // untuk saat ini, tiap employee boleh ngelihat semua data :D
      //if ($i < $intTotal) {
      $strResult .= " <a href=\"" . $arrLinkType[$i] . "?dataID=$strDataID\" title=\"" . $words[$arrMenuType[$i]] . "\">[" . ($i + 1) . "]</a>  |";
      //}
    }
    // hilangkan satu karakter terakhir
    if ($strResult != "") {
      $strResult = substr($strResult, 0, strlen($strResult) - 1);
    }
  }
  return $strResult;
}//getCalculationMenu
// fungsi untuk nge-PRINT- hasil secara keseluruhan
function printEvaluationResult($db, $strDataID)
{
  if ($strDataID === "") {
    return false;
  }
  $cTbs = new clsTinyButStrong();
  $strTemplate = getTemplate("evaluation_result_print.html", false);
  $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.department_code, t2.position_code, ";
  $strSQL .= "t3.position_name, t4.department_name, t5.section_name ";
  $strSQL .= "FROM hrd_employee_evaluation AS t1 ";
  $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
  $strSQL .= "LEFT JOIN hrd_position AS t3 ON t2.position_code = t3.position_code ";
  $strSQL .= "LEFT JOIN hrd_department AS t4 ON t2.department_code = t4.department_code ";
  $strSQL .= "LEFT JOIN hrd_section AS t5 ON t2.section_code = t5.section_code AND t2.department_code = t5.department_code ";
  $strSQL .= "WHERE t1.id = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strPeriode = getBulanSingkat($rowDb['month_from']) . " " . $rowDb['year'];
    $strNextYear = ($rowDb['month_thru'] < $rowDb['month_from']) ? $rowDb['year'] + 1 : $rowDb['year']; // jka bulan lebih kecil, berarti tahun berikutnya
    $strPeriode .= " - " . getBulanSingkat($rowDb['month_thru']) . " " . $strNextYear;
    $GLOBALS['strPeriode'] = $strPeriode;
    $GLOBALS['strEmployee'] = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
    $GLOBALS['strPosition'] = $rowDb['position_name'];
    $GLOBALS['strDepartment'] = $rowDb['department_name'] . " / " . $rowDb['section_name'];
    // cari info atasannya
    if ($rowDb['id_manager'] == "") {
      $GLOBALS['strManager'] = "";
    } else {
      $strSQL = "SELECT employee_name FROM hrd_employee WHERE id = '" . $rowDb['id_manager'] . "' ";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $GLOBALS['strManager'] = $rowTmp['employee_name'];
      }
    }
    // cari hasilevaluasi
    $fltTotalOperational = 0;
    $fltTotalGeneral = 0;
    $fltTotalAbsence = 0;
    // cari detail target kerja
    // cari penilaian umum
    $arrData = [];
    $strSQL = "SELECT * FROM hrd_employee_evaluation_operational WHERE id_evaluation = '" . $rowDb['id'] . "' ";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      $arrData[$rowTmp['criteria']]['point'] = (float)$rowTmp['point'];
      $arrData[$rowTmp['criteria']]['weight'] = (float)$rowTmp['weight'];
    }
    $intRows = 0;
    $strResult = "";
    $fltPoint = 0;
    $fltWeight = 0;
    $strSQL = "SELECT * FROM hrd_evaluation_criteria ";
    $strSQL .= "WHERE type=2 ";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      $intRows++;
      $intPoint = (isset($arrData[$rowTmp['criteria']]['point'])) ? $arrData[$rowTmp['criteria']]['point'] : 0;
      if (!is_numeric($intPoint)) {
        $intPoint = 0;
      }
      $intWeight = (isset($arrData[$rowTmp['criteria']]['weight'])) ? $arrData[$rowTmp['criteria']]['weight'] : $rowTmp['weight'];
      if (!is_numeric($intWeight)) {
        $intWeight = 0;
      }
      $intTmp = ($intPoint * $intWeight) / 100;
      $fltPoint += $intPoint;
      $fltWeight += $intWeight;
      $fltTotalOperational += $intTmp;
      $strResult .= "<tr valign=top>\n";
      $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>";
      $strResult .= "  <td>&nbsp;<b>" . $rowTmp['criteria'] . "</b></td>\n";
      $strResult .= "  <td>&nbsp;<b>" . $rowTmp['note'] . "</b></td>\n";
      $strResult .= "  <td align=right>" . standardFormat($intWeight) . "&nbsp;</td>";
      $strResult .= "  <td align=right>" . standardFormat($intPoint) . "&nbsp;</td>";
      $strResult .= "  <td align=right>" . standardFormat($intTmp) . "&nbsp;</td>";
      $strResult .= "</tr>\n";
      writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    $GLOBALS['strDetailOperational'] = $strResult;
    $GLOBALS['strTotalPoint1'] = standardFormat($fltPoint);
    $GLOBALS['strTotalWeight1'] = standardFormat($fltWeight);
    $GLOBALS['strTotal1'] = standardFormat($fltTotalOperational);
    // cari penilaian umum
    $arrData = [];
    $strSQL = "SELECT * FROM hrd_employee_evaluation_general WHERE id_evaluation = '" . $rowDb['id'] . "' ";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      $arrData[$rowTmp['criteria']]['point'] = (float)$rowTmp['point'];
      $arrData[$rowTmp['criteria']]['weight'] = (float)$rowTmp['weight'];
    }
    $intRows = 0;
    $strResult = "";
    $fltPoint = 0;
    $fltWeight = 0;
    $strSQL = "SELECT * FROM hrd_evaluation_criteria ";
    $strSQL .= "WHERE type=2 ";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      $intRows++;
      $intPoint = (isset($arrData[$rowTmp['criteria']]['point'])) ? $arrData[$rowTmp['criteria']]['point'] : 0;
      if (!is_numeric($intPoint)) {
        $intPoint = 0;
      }
      $intWeight = (isset($arrData[$rowTmp['criteria']]['weight'])) ? $arrData[$rowTmp['criteria']]['weight'] : $rowTmp['weight'];
      if (!is_numeric($intWeight)) {
        $intWeight = 0;
      }
      $intTmp = ($intPoint * $intWeight) / 100;
      $fltPoint += $intPoint;
      $fltWeight += $intWeight;
      $fltTotalGeneral += $intTmp;
      $strResult .= "<tr valign=top>\n";
      $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>";
      $strResult .= "  <td>&nbsp;<b>" . $rowTmp['criteria'] . "</b></td>\n";
      $strResult .= "  <td>&nbsp;<b>" . $rowTmp['note'] . "</b></td>\n";
      $strResult .= "  <td align=right>" . standardFormat($intWeight) . "&nbsp;</td>";
      $strResult .= "  <td align=right>" . standardFormat($intPoint) . "&nbsp;</td>";
      $strResult .= "  <td align=right>" . standardFormat($intTmp) . "&nbsp;</td>";
      $strResult .= "</tr>\n";
      writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    $GLOBALS['strDetailGeneral'] = $strResult;
    $GLOBALS['strTotalPoint2'] = standardFormat($fltPoint);
    $GLOBALS['strTotalWeight2'] = standardFormat($fltWeight);
    $GLOBALS['strTotal2'] = standardFormat($fltTotalGeneral);
    // ambil penilaian absence
    $arrData = [];
    $strSQL = "SELECT * FROM hrd_employee_evaluation_absence WHERE id_evaluation = '" . $rowDb['id'] . "' ";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      $arrData[$rowTmp['criteria']]['point'] = (float)$rowTmp['point'];
      $arrData[$rowTmp['criteria']]['weight'] = (float)$rowTmp['weight'];
    }
    $intRows = 0;
    $strResult = "";
    $fltPoint = 0;
    $fltWeight = 0;
    $strSQL = "SELECT * FROM hrd_evaluation_criteria ";
    $strSQL .= "WHERE type=2 ";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      $intRows++;
      $intPoint = (isset($arrData[$rowTmp['criteria']]['point'])) ? $arrData[$rowTmp['criteria']]['point'] : 0;
      if (!is_numeric($intPoint)) {
        $intPoint = 0;
      }
      $intWeight = (isset($arrData[$rowTmp['criteria']]['weight'])) ? $arrData[$rowTmp['criteria']]['weight'] : $rowTmp['weight'];
      if (!is_numeric($intWeight)) {
        $intWeight = 0;
      }
      $intTmp = ($intPoint * $intWeight) / 100;
      $fltPoint += $intPoint;
      $fltWeight += $intWeight;
      $fltTotalAbsence += $intTmp;
      $strResult .= "<tr valign=top>\n";
      $strResult .= "  <td nowrap align=right>$intRows.&nbsp;</td>";
      $strResult .= "  <td>&nbsp;<b>" . $rowTmp['criteria'] . "</b></td>\n";
      $strResult .= "  <td>&nbsp;<b>" . $rowTmp['note'] . "</b></td>\n";
      $strResult .= "  <td align=right>" . standardFormat($intWeight) . "&nbsp;</td>";
      $strResult .= "  <td align=right>" . standardFormat($intPoint) . "&nbsp;</td>";
      $strResult .= "  <td align=right>" . standardFormat($intTmp) . "&nbsp;</td>";
      $strResult .= "</tr>\n";
      writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    $GLOBALS['strDetailAbsence'] = $strResult;
    $GLOBALS['strTotalPoint3'] = standardFormat($fltPoint);
    $GLOBALS['strTotalWeight3'] = standardFormat($fltWeight);
    $GLOBALS['strTotal3'] = standardFormat($fltTotalAbsence);
    // cari hasil akhir
    $arrW['weight1'] = (float)getSetting("weight_operational");
    $arrW['weight2'] = (float)getSetting("weight_general");
    $arrW['weight3'] = (float)getSetting("weight_absence");
    if (!is_numeric($arrW['weight1'])) {
      $arrW['weight1'] = 0;
    }
    if (!is_numeric($arrW['weight2'])) {
      $arrW['weight2'] = 0;
    }
    if (!is_numeric($arrW['weight3'])) {
      $arrW['weight3'] = 0;
    }
    $fltTotal1 = ($fltTotalOperational * $arrW['weight1'] / 100);
    $fltTotal2 = ($fltTotalGeneral * $arrW['weight2'] / 100);
    $fltTotal3 = ($fltTotalAbsence * $arrW['weight3'] / 100);
    $GLOBALS['strWeight1'] = standardFormat($arrW['weight1']);
    $GLOBALS['strWeight2'] = standardFormat($arrW['weight2']);
    $GLOBALS['strWeight3'] = standardFormat($arrW['weight3']);
    $GLOBALS['strDataTotal1'] = standardFormat($fltTotal1);
    $GLOBALS['strDataTotal2'] = standardFormat($fltTotal2);
    $GLOBALS['strDataTotal3'] = standardFormat($fltTotal3);
    $GLOBALS['strTotalPoint'] = standardFormat($fltTotalOperational + $fltTotalGeneral + $fltTotalAbsence);
    $GLOBALS['strTotalWeight'] = standardFormat($arrW['weight1'] + $arrW['weight2'] + $arrW['weight3']);
    $GLOBALS['strTotal'] = standardFormat($fltTotal1 + $fltTotal2 + $fltTotal3);
    $GLOBALS['strNoteCriteria1'] = getEvaluationCriteria($fltTotalOperational);
    $GLOBALS['strNoteCriteria2'] = getEvaluationCriteria($fltTotalGeneral);
    $GLOBALS['strNoteCriteria3'] = getEvaluationCriteria($fltTotalAbsence);
    $GLOBALS['strNoteCriteria'] = getEvaluationCriteria($fltTotal1 + $fltTotal2 + $fltTotal3);
    // ambil data hasil tahun lalu
    //cari data total dari tahun sebelumnya
    $arrData = [];
    $strSQL = "SELECT * FROM hrd_employee_evaluation WHERE id_employee = '" . $rowDb['id_employee'] . "' ";
    $strSQL .= "AND (year = '" . ($rowDb['year'] - 2) . "' OR year = '" . ($rowDb['year'] - 1) . "' )";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      if ($rowTmp['year'] == $rowDb['year'] - 1) {
        $arrData['prev1_' . $rowTmp['semester']] = (float)(($rowTmp['operational_point'] * $arrData['weight1']) / 100);
        $arrData['prev1_' . $rowTmp['semester']] += (float)(($rowTmp['general_point'] * $arrData['weight2']) / 100);
        $arrData['prev1_' . $rowTmp['semester']] += (float)(($rowTmp['absence_point'] * $arrData['weight3']) / 100);
      } else if ($rowTmp['year'] == $rowDb['year'] - 2) {
        $arrData['prev2_' . $rowTmp['semester']] = (float)(($rowTmp['operational_point'] * $arrData['weight1']) / 100);
        $arrData['prev2_' . $rowTmp['semester']] += (float)(($rowTmp['general_point'] * $arrData['weight2']) / 100);
        $arrData['prev2_' . $rowTmp['semester']] += (float)(($rowTmp['absence_point'] * $arrData['weight3']) / 100);
      }
    }
    $GLOBALS['strPrevYear1'] = $rowDb['year'] - 1;
    $GLOBALS['strPrevYear2'] = $rowDb['year'] - 2;
    $GLOBALS['strPrevResult1_1'] = (isset($arrData['prev1_1'])) ? standardFormat($arrData['prev1_1']) : 0;
    $GLOBALS['strPrevResult1_2'] = (isset($arrData['prev1_2'])) ? standardFormat($arrData['prev1_2']) : 0;
    $GLOBALS['strPrevResult2_1'] = (isset($arrData['prev2_1'])) ? standardFormat($arrData['prev2_1']) : 0;
    $GLOBALS['strPrevResult2_2'] = (isset($arrData['prev2_2'])) ? standardFormat($arrData['prev2_2']) : 0;
    // ambil data feedback
    $GLOBALS['strInputEmployeeNote'] = $rowDb['employee_note'];
    $GLOBALS['strInputManagerNote'] = $rowDb['manager_note'];
    $GLOBALS['strInputEmployeeStrong'] = $rowDb['employee_strong'];
    $GLOBALS['strInputImprovement'] = $rowDb['employee_improvement'];
    // cari usulan training
    $strResult = "";
    $intRows = 0;
    $strSQL = "SELECT * FROM hrd_employee_evaluation_training ";
    $strSQL .= "WHERE id_evaluation = '" . $rowDb['id'] . "' ";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      $intRows++;
      if ($strResult != "") {
        $strResult .= "<br>";
      }
      $strResult .= $intRows . ".&nbsp;" . $rowTmp['note'] . "&nbsp; - " . $rowTmp['institution'] . "\n";
    }
    $GLOBALS['strInputTraining'] = $strResult;
    // cari usulan mutasi
    $strResult = "";
    $intRows = 0;
    $strSQL = "SELECT * FROM hrd_employee_evaluation_mutation ";
    $strSQL .= "WHERE id_evaluation = '" . $rowDb['id'] . "' ";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      $intRows++;
      if ($strResult != "") {
        $strResult .= "<br>";
      }
      $strResult .= $intRows . ".&nbsp;" . $rowTmp['note'] . "&nbsp; - " . $rowTmp['position'] . "\n";
    }
    $GLOBALS['strInputMutation'] = $strResult;
    // cari usulan lain2
    $strResult = "";
    $intRows = 0;
    $strSQL = "SELECT * FROM hrd_employee_evaluation_other ";
    $strSQL .= "WHERE id_evaluation = '" . $rowDb['id'] . "' ";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      $intRows++;
      if ($strResult != "") {
        $strResult .= "<br>";
      }
      $strResult .= $intRows . ".&nbsp;" . $rowTmp['note'] . "&nbsp; \n";
    }
    $GLOBALS['strInputOther'] = $strResult;
  }
  $cTbs->LoadTemplate($strTemplate);
  $cTbs->show();
  exit();
} //printEvaluationResult
// fungsi untuk mengambil keterangan akan total nilai yang diterima
function getEvaluationCriteria($fltPoint = 0)
{
  global $db;
  if (!is_numeric($fltPoint)) {
    return "";
  }
  if ($fltPoint < 60) {
    return getSetting("category_e");
  } else if ($fltPoint <= 70) {
    return getSetting("category_d");
  } else if ($fltPoint <= 80) {
    return getSetting("category_c");
  } else if ($fltPoint <= 90) {
    return getSetting("category_b");
  } else {
    return getSetting("category_a");
  }
}

function searchEvaluator($strEmployee, $bolEmployeeID = true)
{
  if ($bolEmployeeID) {
    return getEmployeeInfoByID($db, getEmployeeManagerID("", $strEmployee), "employee_id");
  } else {
    return getEmployeeInfoByID($db, getEmployeeManagerID($strEmployee, ""), "employee_id");
  }
}

function getEvaluationPointList($db, $varName, $strDefault, $type)
{
  // cari usulan lain2
  $strResult = "";
  $strSQL = "SELECT * FROM hrd_evaluation_point ";
  $strSQL .= "WHERE type = $type ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrPoint[$rowDb['id']] = $rowDb['note'];
  }
  return getComboFromArray($arrPoint, $varName, $strDefault);
}

// aoutput berupa array
function getEmployeeConfirmedAbsence($db, $strDateFrom, $strDateThru, $strIDEmployee = "")
{
  $arrResult = [];
  // langsung dipisah sesuai jenis absennya
  $strSQL = "SELECT id_employee, ";
  $strSQL .= "SUM(CASE WHEN late_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
  $strSQL .= "THEN 1 ELSE 0 END) AS late , ";
  $strSQL .= "SUM(CASE WHEN early_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
  $strSQL .= "THEN 1 ELSE 0 END) AS early , ";
  $strSQL .= "SUM(CASE WHEN attendance_date BETWEEN '$strDateFrom' AND '$strDateThru' ";
  $strSQL .= "THEN 1 ELSE 0 END) AS forget ";
  $strSQL .= "FROM hrd_absence_confirm  ";
  if ($strIDEmployee != "") {
    $strSQL .= "WHERE id_employee = '$strIDEmployee' ";
  }
  $strSQL .= "GROUP BY id_employee";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['id_employee']] = $rowDb;
  }
  return $arrResult;
} // getEmployeeAbsenceConfirm
//MAIN
if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != "") {
  if (function_exists($_REQUEST['ajax'])) {
    echo $_REQUEST['ajax']($_REQUEST['ajax']);
  }
}
?>