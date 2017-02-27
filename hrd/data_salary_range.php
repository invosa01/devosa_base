<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView) {
    die(accessDenied($_SERVER['HTTP_REFERER']));
}
/*
  session_start();

  include_once('global.php');
  include_once('../global/excelReader/reader.php');
  //include_once(getTemplate("words.inc"));

  // periksa apakah sudah login atau belum, jika belum, harus login lagi
  if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=data_salary_grade.php");
    exit();
  }

  $bolCanView = getUserPermission("data_salary_grade.php", $bolCanEdit, $bolCanDelete, $strError);
*/
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
if ($bolPrint) {
    $strMainTemplate = getTemplate("data_salary_grade_print.html");
} else {
    $strTemplateFile = getTemplate("data_salary_grade.html");
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strInputFacilities = "";
$intTotalData = 0;
$intTotalFacilities = 0;
$strPageTitle = getwords("job grade");
$strWordsJobGradeData = getwords("job grade data");
$strWordsGrade = getwords("grade");
$strWordsNote = getwords("note");
$strWordsGradeAllowance = getwords("grade allowance");
$strWordsTransportAllowance = getwords("transport allowance");
$strWordsMealAllowance = getwords("meal allowance");
$strWordsVehicleAllowance = getwords("vehicle allowance");
$strWordsDomesticTripAllowance = getwords("domestic trip allowance");
$strWordsSave = getwords("save");
$strWordsClearForm = getwords("clear form");
$strWordsFacilities = getwords("facilities");
$strWordsLISTOFJOBGRADE = getwords("LIST OF JOB GRADE");
$strWordsGrade = getwords("grade");
$strWordsNote = getwords("note");
//$strWordsDOMESTICTRIP = getwords("DOMESTIC TRIP");
$strWordsOT = getwords("OT");
$strWordsDelete = getwords("delete");
$strWordsExcel = getwords("excel");
$strWordsPrint = getwords("print");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $bolPrint;
    $intRows = 0;
    $strResult = "";
    // ambil data fasilitas yang dimiliki
    $strSQL = "SELECT * FROM hrd_facility ORDER BY name ";
    $resDb = $db->execute($strSQL);
    $i = 0;
    while ($rowDb = $db->fetchrow($resDb)) {
        $i++;
        $arrFacility[$i] = $rowDb['name'];
    }
    // ambil data fasilitas group
    $strSQL = "SELECT * FROM hrd_grade_facility ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrGradeFacility[$rowDb['grade_code']][$rowDb['facility']] = array_search($rowDb['facility'], $arrFacility);
    }
    $strSQL = "SELECT * FROM hrd_salary_grade ";
    $strSQL .= "WHERE 1=1 $strKriteria ORDER BY $strOrder grade_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strFacility = "";
        if (isset($arrGradeFacility[$rowDb['grade_code']])) {
            foreach ($arrGradeFacility[$rowDb['grade_code']] AS $strFasilitas => $intIndex) {
                if ($strFacility != "") {
                    $strFacility .= ", ";
                }
                $strFacility .= "<input type=hidden name=detailFacility$intRows" . "_$intIndex disabled>$strFasilitas";
            }
        }
        //apakah lembur atau tidak
        $strResult .= "<tr valign=top>\n";
        if (!$bolPrint) {
            $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
        }
        $strResult .= "  <td nowrap><input type=hidden name=detailCode$intRows value=\"" . $rowDb['grade_code'] . "\" disabled>" . $rowDb['grade_code'] . "&nbsp;</td>";
        //$strResult .= "  <td align=right><input type=hidden name=detailBasic$intRows value=\"" .(float)$rowDb['basic_salary']. "\" disabled>" .standardFormat($rowDb['basic_salary']). "&nbsp;</td>";
        $strResult .= "  <td align=right><input type=hidden name=detailGrade$intRows value=\"" . (float)$rowDb['grade_allowance'] . "\" disabled>" . standardFormat(
                $rowDb['grade_allowance']
            ) . "&nbsp;</td>";
        $strResult .= "  <td align=right><input type=hidden name=detailTransport$intRows value=\"" . (float)$rowDb['transport_allowance'] . "\" disabled>" . standardFormat(
                $rowDb['transport_allowance']
            ) . "&nbsp;</td>";
        $strResult .= "  <td align=right><input type=hidden name=detailMeal$intRows value=\"" . (float)$rowDb['meal_allowance'] . "\" disabled>" . standardFormat(
                $rowDb['meal_allowance']
            ) . "&nbsp;</td>";
        $strResult .= "  <td align=right><input type=hidden name=detailVehicle$intRows value=\"" . (float)$rowDb['vehicle_allowance'] . "\" disabled>" . standardFormat(
                $rowDb['vehicle_allowance']
            ) . "&nbsp;</td>";
        $strResult .= "  <td >$strFacility&nbsp;</td>";
        $strResult .= "  <td><input type=hidden name=detailNote$intRows value=\"" . $rowDb['note'] . "\" disabled>" . $rowDb['note'] . "&nbsp;</td>";
        if (!$bolPrint) {
            $strResult .= "  <td nowrap align=center><a href='javascript:editData($intRows)'>" . $words['edit'] . "</a>&nbsp;</td>";
        }
        $strResult .= "</tr>\n";
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $error;
    $strError = "";
    (isset($_REQUEST['dataCode'])) ? $strDataCode = trim($_REQUEST['dataCode']) : $strDataCode = "";
    //(isset($_REQUEST['dataBasic']) && is_numeric($_REQUEST['dataBasic'])) ? $strDataBasic = $_REQUEST['dataBasic'] : $strDataBasic = "0";
    (isset($_REQUEST['dataGrade']) && is_numeric(
            $_REQUEST['dataGrade']
        )) ? $strDataGrade = $_REQUEST['dataGrade'] : $strDataGrade = "0";
    (isset($_REQUEST['dataTransport']) && is_numeric(
            $_REQUEST['dataTransport']
        )) ? $strDataTransport = $_REQUEST['dataTransport'] : $strDataTransport = "0";
    (isset($_REQUEST['dataMeal']) && is_numeric(
            $_REQUEST['dataMeal']
        )) ? $strDataMeal = $_REQUEST['dataMeal'] : $strDataMeal = "0";
    (isset($_REQUEST['dataVehicle']) && is_numeric(
            $_REQUEST['dataVehicle']
        )) ? $strDataVehicle = $_REQUEST['dataVehicle'] : $strDataVehicle = "0";
    (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    // cek validasi -----------------------
    if ($strDataCode == "") {
        $strError = $error['empty_code'];
        return false;
    } else {
        ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
        if (isDataExists("hrd_salary_grade", "grade_code", $strDataCode, $strKriteria)) {
            $strError = $error['duplicate_code'] . "  -> $strDataCode";
            return false;
        }
    }
    // simpan data -----------------------
    if ($strDataID == "") {
        // data baru
        $strSQL = "INSERT INTO hrd_salary_grade (created,created_by,modified_by, ";
        $strSQL .= "grade_code,note, grade_allowance, transport_allowance, meal_allowance, vehicle_allowance) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "'$strDataCode','$strDataNote', '$strDataGrade', '$strDataTransport', '$strDataMeal', '$strDataVehicle') ";
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataCode", 0);
        $strTmpCode = $strDataCode;
    } else {
        //ambil grade code yang lama
        $strSQL = "SELECT grade_code FROM hrd_grade_facility WHERE id = '$strDataID' ";
        $resTmp = $db->execute($strSQL);
        $strTmpCode = ($rowTmp = $db->fetchrow($resTmp)) ? $rowTmp['grade_code'] : $strDataCode;
        $strSQL = "UPDATE hrd_salary_grade ";
        $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "grade_code = '$strDataCode', note = '$strDataNote', ";
        $strSQL .= "grade_allowance= '$strDataGrade', ";
        $strSQL .= "transport_allowance= '$strDataTransport', ";
        $strSQL .= "meal_allowance= '$strDataMeal', ";
        $strSQL .= "vehicle_allowance= '$strDataGrade' ";
        $strSQL .= "WHERE id = '$strDataID' ";
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataCode", 0);
    }
    $resExec = $db->execute($strSQL);
    // update data fasilitas dari grade
    if ($strTmpCode != "") {
        //hpus dulu yang lama
        $strSQL = "DELETE FROM hrd_grade_facility WHERE grade_code = '$strTmpCode' ";
        $resExec = $db->execute($strSQL);
    }
    foreach ($_REQUEST AS $strIndex => $strValue) {
        if (substr($strIndex, 0, 12) == "dataFacility") {
            $strSQL = "INSERT INTO hrd_grade_facility (created_by, modified_by, grade_code, facility) ";
            $strSQL .= "VALUES('" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
            $strSQL .= "'$strDataCode', '$strValue') ";
            $resExec = $db->execute($strSQL);
        }
    }
    return true;
} // saveData
// fungsi untuk menghapus data
function deleteData($db)
{
    global $_REQUEST;
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $strSQL = "SELECT grade_code FROM hrd_salary_grade WHERE id = '$strValue' ";
            $resDb = $db->execute($strSQL);
            if ($rowDb = $db->fetchrow($resDb)) {
                $strSQL = "DELETE FROM hrd_grade_facility WHERE grade_code = '" . $rowDb['grade_code'] . "' ";
                $resExec = $db->execute($strSQL);
            }
            $strSQL = "DELETE FROM hrd_salary_grade WHERE id = '$strValue' ";
            $resExec = $db->execute($strSQL);
            $i++;
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
    }
} //deleteData
// fungsi untuk mengambil data fasilitas
function getFacilities($db)
{
    global $intTotalFacilities;
    $strResult = "";
    $strSQL = "SELECT * FROM hrd_facility ORDER BY name ";
    $resDb = $db->execute($strSQL);
    $bolGanjil = false;
    $intRows = 0;
    while ($rowDb = $db->fetchrow($resDb)) {
        $intTotalFacilities++;
        if (!$bolGanjil) { // baris kiri
            $strResult .= "<tr valign=top>\n";
            $strResult .= "  <td nowrap title=\"" . $rowDb['note'] . "\">";
            $strResult .= "<input type=checkbox name=dataFacility$intTotalFacilities value=\"" . $rowDb['name'] . "\"> ";
            $strResult .= "<span onClick=\"check($intTotalFacilities)\" >" . $rowDb['name'] . "</span>&nbsp;</td>\n";
        } else {
            $strResult .= "  <td nowrap title=\"" . $rowDb['note'] . "\">";
            $strResult .= "<input type=checkbox name=dataFacility$intTotalFacilities value=\"" . $rowDb['name'] . "\"> ";
            $strResult .= "<span onClick=\"check($intTotalFacilities)\" >" . $rowDb['name'] . "</span>&nbsp;</td>\n";
            $strResult .= "</tr>\n";
        }
        $bolGanjil = !$bolGanjil;
    }
    if ($bolGanjil) {
        $strResult .= "<td>&nbsp;</td></tr>\n";
    } // ditutup
    return $strResult;
}//getFacilities
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    if (isset($_REQUEST['btnSave'])) {
        if ($bolCanEdit) {
            if (!saveData($db, $strError)) {
                echo "<script>alert(\"$strError\")</script>";
            }
        }
    } else if (isset($_REQUEST['btnDelete'])) {
        if ($bolCanDelete) {
            deleteData($db);
        }
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $intTotalData);
        $strInputFacilities = getFacilities($db);
        if (isset($_REQUEST['btnExcel'])) {
            $strDataDetail = getData($db, $intTotalData);
            // ambil data CSS-nya
            if (file_exists("bw.css")) {
                $strStyle = "bw.css";
            }
            $strPrintCss = "";
            $strPrintInit = "";
            headeringExcel("salaryGrade.xls");
        }
    } else {
        showError("view_denied");
    }
}
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>