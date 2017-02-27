<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('evaluation_func.php');
//include_once(getTemplate("words.inc"));
$dataPrivilege = getDataPrivileges("evaluation_list.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
    die(accessDenied($_SERVER['HTTP_REFERER']));
}
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$intTotalData = 0;
$strMessages = "";
$strMsgClass = "";
$bolError = false;
$strPeriode = "";
$strEmployee = "";
$strEvaluationMenu = "";
// data simpan ke array
$dtNow = getdate();
$arrData['weight1'] = 0;
$arrData['weight2'] = 0;
$arrData['weight3'] = 0;
$arrData['point1'] = 0;
$arrData['point2'] = 0;
$arrData['point3'] = 0;
$arrData['prevYear1'] = $dtNow['year'] - 2;
$arrData['prevYear2'] = $dtNow['year'] - 1;
$arrData['prev1_1'] = 0;
$arrData['prev1_2'] = 0;
$arrData['prev2_1'] = 0;
$arrData['prev2_2'] = 0;
$strCategoryA = "";
$strCategoryB = "";
$strCategoryC = "";
$strCategoryD = "";
$strCategoryE = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, sesuai id = $strDataID
function getData($db, $strDataID)
{
    global $words;
    global $arrData;
    $strResult = "";
    if ($strDataID == "") {
        return "";
    }
    // cari data total saat ini
    $strSQL = "SELECT operational_point, general_point, absence_point FROM hrd_employee_evaluation ";
    $strSQL .= "WHERE id = '$strDataID' AND flag = 0";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        $arrData['point1'] = (is_numeric($rowDb['operational_point'])) ? (float)$rowDb['operational_point'] : 0;
        $arrData['point2'] = (is_numeric($rowDb['general_point'])) ? (float)$rowDb['general_point'] : 0;
        $arrData['point3'] = (is_numeric($rowDb['absence_point'])) ? (float)$rowDb['absence_point'] : 0;
    }
    // update data di tabel masternya , untuk total masing-masing
    $strSQL = "UPDATE hrd_employee_evaluation SET operational_point = '" . $arrData['point1'] . "', ";
    $strSQL .= "general_point = '" . $arrData['point2'] . "', ";
    $strSQL .= "absence_point = '" . $arrData['point3'] . "' ";
    $strSQL .= "WHERE id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    return $strResult;
} // showData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    $arrData['weight1'] = (float)getSetting("weight_operational");
    $arrData['weight2'] = (float)getSetting("weight_general");
    $arrData['weight3'] = (float)getSetting("weight_absence");
    getUserEmployeeInfo();
    $bolIsEmployee = isUserEmployee();
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    if ($strDataID == "") {
        header("location:evaluation_list.php");
        exit();
    } else {
        if ($bolPrint) {
            // panggil perintah untuk print secara keseluruhan
            printEvaluationResult($db, $strDataID);
            exit();
        }
        // cari info karyawan
        $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name FROM hrd_employee_evaluation AS t1 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
        $strSQL .= "WHERE t1.id = '$strDataID'";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strEmployee = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
            $strPeriode = getBulanSingkat($rowDb['month_from']) . " " . $rowDb['year'];
            $strNextYear = ($rowDb['month_thru'] < $rowDb['month_from']) ? $rowDb['year'] + 1 : $rowDb['year']; // jka bulan lebih kecil, berarti tahun berikutnya
            $strPeriode .= " - " . getBulanSingkat($rowDb['month_thru']) . " " . $strNextYear;
            //cari data total dari tahun sebelumnya
            $strSQL = "SELECT * FROM hrd_employee_evaluation WHERE id_employee = '" . $rowDb['id_employee'] . "' ";
            $strSQL .= "AND year = '" . ($rowDb['year'] - 2) . "' AND semester = 1 ";
            $resTmp = $db->execute($strSQL);
            if ($rowTmp = $db->fetchrow($resTmp)) {
                $arrData['prev1_1'] = (float)(($rowTmp['operational_point'] * $arrData['weight1']) / 100);
                $arrData['prev1_1'] += (float)(($rowTmp['general_point'] * $arrData['weight2']) / 100);
                $arrData['prev1_1'] += (float)(($rowTmp['absence_point'] * $arrData['weight3']) / 100);
            }
            $strSQL = "SELECT * FROM hrd_employee_evaluation WHERE id_employee = '" . $rowDb['id_employee'] . "' ";
            $strSQL .= "AND year = '" . ($rowDb['year'] - 2) . "' AND semester = 2 ";
            $resTmp = $db->execute($strSQL);
            if ($rowTmp = $db->fetchrow($resTmp)) {
                $arrData['prev1_2'] = (float)(($rowTmp['operational_point'] * $arrData['weight1']) / 100);
                $arrData['prev1_2'] += (float)(($rowTmp['general_point'] * $arrData['weight2']) / 100);
                $arrData['prev1_2'] += (float)(($rowTmp['absence_point'] * $arrData['weight3']) / 100);
            }
            $strSQL = "SELECT * FROM hrd_employee_evaluation WHERE id_employee = '" . $rowDb['id_employee'] . "' ";
            $strSQL .= "AND year = '" . ($rowDb['year'] - 1) . "' AND semester = 1 ";
            $resTmp = $db->execute($strSQL);
            if ($rowTmp = $db->fetchrow($resTmp)) {
                $arrData['prev2_1'] = (float)(($rowTmp['operational_point'] * $arrData['weight1']) / 100);
                $arrData['prev2_1'] += (float)(($rowTmp['general_point'] * $arrData['weight2']) / 100);
                $arrData['prev2_1'] += (float)(($rowTmp['absence_point'] * $arrData['weight3']) / 100);
            }
            $strSQL = "SELECT * FROM hrd_employee_evaluation WHERE id_employee = '" . $rowDb['id_employee'] . "' ";
            $strSQL .= "AND year = '" . ($rowDb['year'] - 1) . "' AND semester = 2 ";
            $resTmp = $db->execute($strSQL);
            if ($rowTmp = $db->fetchrow($resTmp)) {
                $arrData['prev2_2'] = (float)(($rowTmp['operational_point'] * $arrData['weight1']) / 100);
                $arrData['prev2_2'] += (float)(($rowTmp['general_point'] * $arrData['weight2']) / 100);
                $arrData['prev2_2'] += (float)(($rowTmp['absence_point'] * $arrData['weight3']) / 100);
            }
        } else {
            header("location:evaluation_list.php");
            exit();
        }
        if ($bolCanView) {
            getData($db, $strDataID);
            // tampilkan dta
            $strDataWeight1 = $arrData['weight1'];
            $strDataWeight2 = $arrData['weight2'];
            $strDataWeight3 = $arrData['weight3'];
            $strDataPoint1 = standardFormat($arrData['point1']);
            $strDataPoint2 = standardFormat($arrData['point2']);
            $strDataPoint3 = standardFormat($arrData['point3']);
            // tampilkan totalnya
            $intDataTotal1 = ($arrData['point1'] * $arrData['weight1'] / 100);
            $intDataTotal2 = ($arrData['point2'] * $arrData['weight2'] / 100);
            $intDataTotal3 = ($arrData['point3'] * $arrData['weight3'] / 100);
            $strDataTotal1 = standardFormat($intDataTotal1);
            $strDataTotal2 = standardFormat($intDataTotal2);
            $strDataTotal3 = standardFormat($intDataTotal3);
            $strTotalWeight = $arrData['weight1'] + $arrData['weight2'] + $arrData['weight3'];
            $strTotalPoint = standardFormat($arrData['point1'] + $arrData['point2'] + $arrData['point3']);
            $intTotal = $intDataTotal1 + $intDataTotal2 + $intDataTotal3;
            $strTotal = standardFormat($intTotal);
            $strPrevYear1 = $arrData['prevYear1'];
            $strPrevYear2 = $arrData['prevYear2'];
            $strPrevResult1_1 = $arrData['prev1_1'];
            $strPrevResult1_2 = $arrData['prev1_2'];
            $strPrevResult2_1 = $arrData['prev2_1'];
            $strPrevResult2_2 = $arrData['prev2_2'];
            $strNoteKriteria = "";
        } else {
            showError("view_denied");
            $strDataDetail = "";
        }
        $strEvaluationMenu = getEvaluationMenu($strDataID, 3, 3);
    }
}
$bolPrint = (isset($_REQUEST['btnPrint']));
if ($bolPrint) {
    $strMainTemplate = getTemplate("evaluation_result_print.html");
} else {
    $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>