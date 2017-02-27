<?php
include_once('../global/session.php');
include_once('global.php');
//include_once(getTemplate("words.inc"));
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
    die(getWords('view denied'));
}
$bolPrint = (isset($_REQUEST['btnPrint']));
if ($bolPrint) {
    $strTemplateFile = getTemplate("data_training_instructorPrint.html");
} else {
    $strTemplateFile = getTemplate("data_training_instructor.html");
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
$strWordsTrainingTypeData = getWords("training type data");
$strWordsINPUTDATATRAININGTYPE = getWords("training type data");
$strWordsInstructorName = getWords("instructor name");
$strWordsAddress = getWords("address");
$strWordsTelephone = getWords("telephone");
$strWordsEmail = getWords("email");
$strWordsSubject = getWords("subject");
$strWordsINSTRUCTORNAME = getWords("instructor name");
$strWordsADDRESS = getWords("address");
$strWordsTELEPHONE = getWords("telephone");
$strWordsEMAIL = getWords("email");
$strWordsDelete = getWords("delete");
$strWordsSave = getWords("save");
$strWordsPrint = getWords("print");
$strWordsAddNew = getWords("add new");
$strWordsLISTOFTRAININGINSTRUCTOR = getWords("list of training instructor");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data per barisnya aja
function getDataRows($rowDb, $intRows)
{
    global $words;
    global $bolPrint;
    $strResult = "";
    if ($rowDb['flag'] == 0) {
        $strClass = $strAddChar = "";
    } else {
        $strClass = "class=bgCheckedData";
        $strAddChar = ($rowDb['linkID'] == "") ? "" : "&nbsp;&nbsp;";
    }
    $strResult .= "<tr valign=top $strClass>\n";
    if (!$bolPrint) {
        $strResult .= "  <td nowrap>$strAddChar<input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\">\n";
        $strResult .= "  <input type=hidden disabled name='detailFlag$intRows' value=\"" . $rowDb['flag'] . "\"></td>\n";
    } else {
        $strResult .= "  <td nowrap>" . $intRows . "</td>\n";
    }
    $strResult .= "  <td width='15%'><input type=hidden name=detailName$intRows value=\"" . $rowDb['name_instructor'] . "\" disabled>" . $rowDb['name_instructor'] . "&nbsp;</td>";
    $strResult .= "  <td width='30%'><input type=hidden name=detailAddress$intRows value=\"" . $rowDb['address'] . "\" disabled>" . $rowDb['address'] . "&nbsp;</td>";
    $strResult .= "  <td><input type=hidden name=detailTelephone$intRows value=\"" . $rowDb['telephone'] . "\" disabled>" . $rowDb['telephone'] . "&nbsp;</td>";
    $strResult .= "  <td><input type=hidden name=detailEmail$intRows value=\"" . $rowDb['email'] . "\" disabled>" . $rowDb['email'] . "&nbsp;</td>";
    $strResult .= "  <td><input type=hidden name=detailSubject$intRows value=\"" . $rowDb['subject'] . "\" disabled>" . $rowDb['subject'] . "&nbsp;</td>";
    if (!$bolPrint) {
        $strResult .= "  <td nowrap align=center><a href='javascript:editData($intRows)'>" . $words['edit'] . "</a>&nbsp;</td>";
    }
    $strResult .= "</tr>\n";
    return $strResult;
}

// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $bolPrint;
    global $ARRAY_LEADER_LIST;
    $intRows = 0;
    $strResult = "";
    // cari dulu data temporer yang link IDnya ada
    $strSQL = "SELECT * FROM hrd_training_instructor WHERE flag <> 0 AND \"linkID\" is not null ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrTmp[$rowDb['linkID']] = $rowDb;
    }
    $strSQL = "SELECT * FROM hrd_training_instructor ";
    $strSQL .= "WHERE flag = 0 $strKriteria ORDER BY $strOrder name_instructor ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= getDataRows($rowDb, $intRows);
        if (isset($arrTmp[$rowDb['id']])) {
            $intRows++;
            $strResult .= getDataRows($arrTmp[$rowDb['id']], $intRows);
        }
    }
    // cari dulu data temporer yang link IDnya ada
    $strSQL = "SELECT * FROM hrd_training_instructor WHERE flag <> 0 AND \"linkID\" is null ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= getDataRows($rowDb, $intRows);
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
    $strFields = "currdate, updater, creator, name_instructor, address, ";
    $strFields .= "telephone, email, subject";
    (isset($_REQUEST['dataName'])) ? $strDataName = $_REQUEST['dataName'] : $strDataName = "";
    (isset($_REQUEST['dataAddress'])) ? $strDataAddress = $_REQUEST['dataAddress'] : $strDataAddress = "";
    (isset($_REQUEST['dataTelephone'])) ? $strDataTelephone = $_REQUEST['dataTelephone'] : $strDataTelephone = "";
    (isset($_REQUEST['dataEmail'])) ? $strDataEmail = $_REQUEST['dataEmail'] : $strDataEmail = "";
    (isset($_REQUEST['dataSubject'])) ? $strDataSubject = $_REQUEST['dataSubject'] : $strDataSubject = "";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    (isset($_REQUEST['dataFlag'])) ? $strDataFlag = $_REQUEST['dataFlag'] : $strDataFlag = "2";
    // cek validasi -----------------------
    if ($strDataName == "") {
        $strError = $error['empty_code'];
        return false;
    } else {
        ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
        if (isDataExists($db, "hrd_training_instructor", "name_instructor", $strDataName, $strKriteria)) {
            $strError = $error['duplicate_code'] . "  -> $strDataName";
            return false;
        }
    }
    // simpan data -----------------------
    if ($strDataID == "") {
        // data baru
        $strFlag = 0;//($bolIsManager) ? 0 : 2;
        $strSQL = "INSERT INTO hrd_training_instructor (currdate,creator,updater, ";
        $strSQL .= "name_instructor, address, telephone, email, flag, subject) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "'$strDataName','$strDataAddress','$strDataTelephone',";
        $strSQL .= "'$strDataEmail','$strFlag', '$strDataSubject') ";
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataName", 0);
    } else {
        /*if (!$bolIsManager) {
          if ($strDataFlag == 0) { // master, bikin temporernnya
            $strDataID = getTempData($db, "hrdTrainingType", $strFields, $strDataID,2);
          }
        }*/
        $strSQL = "UPDATE hrd_training_instructor ";
        $strSQL .= "SET updater = '" . $_SESSION['sessionUserID'] . "', ";
        //if ($bolIsManager) {
        $strSQL .= "flag = 0, "; // jika manager, langsung jadi 0
        //}
        $strSQL .= "name_instructor = '$strDataName', ";
        $strSQL .= "address = '$strDataAddress', ";
        $strSQL .= "telephone = '$strDataTelephone',  email = '$strDataEmail' , subject = '$strDataSubject'";
        $strSQL .= "WHERE id = '$strDataID' ";
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataName", 0);
    }
    $resExec = $db->execute($strSQL);
    return true;
} // saveData
// fungsi untuk menghapus data
function deleteData($db)
{
    global $_REQUEST;
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $strSQL = "DELETE FROM hrd_training_instructor WHERE \"linkID\" = '$strValue'; ";
            $strSQL .= "DELETE FROM hrd_training_instructor WHERE id = '$strValue'; ";
            $resExec = $db->execute($strSQL);
            $i++;
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
    }
} //deleteData
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
        $strData = $strDataDetail; // print
    } else {
        showError("view_denied");
    }
    $strInfo = "";
    $strPeriod = "";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
if ($bolPrint) {
    $strMainTemplate = getTemplate("data_training_instructor_print.html");//;"../templates/master_print.html";
}
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
