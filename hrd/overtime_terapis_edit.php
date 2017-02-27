<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
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
if ($_SESSION['sessionUserRole'] < ROLE_ADMIN) {
    redirectPage("overtime_terapi_list.php");
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intDefaultWidth = 10;
$intDefaultHeight = 3;
$strNow = date("Y-m-d");
$dtNow = getdate();
$strMessage = "";
$strMsgClass = "";
$strWordsDataEntry = getWords("data entry");
$strWordsDataList = getWords("data list");
$strWordsEmpID = getWords("employee ID");
$strWordsOtTerapisDate = getWords("overtime date");
$strWordsOtTerapisType = getWords("type");
$strWordsQty = getWords("qty");
$strWordsNote = getWords("note");
$strWordsSave = getWords("save");
$strWordsClear = getWords("clear");
$arrData = [
    "dataEmployee"    => "",
    "dataTerapisCode" => "",
    "dataDate"        => "",
    "dataQty"         => "1",
    "dataNote"        => "",
];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db, $strDataID = "")
{
    global $words;
    global $arrData;
    if ($strDataID != "") {
        $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t3.ot_type, t3.amount ";
        $strSQL .= "FROM hrd_overtime_terapis_employee AS t1 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
        $strSQL .= "LEFT JOIN hrd_overtime_terapis AS t3 ON t1.ot_code = t3.ot_code ";
        $strSQL .= "WHERE t1.id = '$strDataID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $arrData['dataEmployee'] = $rowDb['employee_id'];
            $arrData['dataTerapisCode'] = $rowDb['ot_code'];
            $arrData['dataTerapisType'] = $rowDb['ot_type'];
            $arrData['dataAmount'] = $rowDb['amount'];
            $arrData['dataTotAmount'] = $rowDb['tot_amount'];
            $arrData['dataID'] = $rowDb['id'];
            $arrData['dataQty'] = $rowDb['qty'];
            $arrData['dataDate'] = $rowDb['overtime_date'];
            $arrData['dataNote'] = $rowDb['note'];
            $arrData['dataStatus'] = $rowDb['status'];
            writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$strDataID ->Emp: " . $rowDb['employee_id'], 0);
        }
    }
    return true;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strDataID, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $error;
    global $messages;
    global $arrData;
    $strError = "";
    $bolOK = true;
    $strToday = date("Y-m-d");
    (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
    (isset($_REQUEST['dataTerapisCode'])) ? $strDataTerapisCode = $_REQUEST['dataTerapisCode'] : $strDataTerapisCode = "";
    (isset($_REQUEST['dataDate'])) ? $strDataDate = $_REQUEST['dataDate'] : $strDataDate = "";
    (isset($_REQUEST['dataQty'])) ? $strDataQty = $_REQUEST['dataQty'] : $strDataQty = "1";
    (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
    // cek validasi -----------------------
    if ($strDataEmployee == "") {
        $strError = $error['empty_code'];
        $bolOK = false;
    } else if (!validStandardDate($strDataDate)) {
        $strError = $error['invalid_date'];
        $bolOK = false;
    } else if (!is_numeric($strDataQty)) {
        $strError = $error['invalid_number'];
        $bolOK = false;
    }
    // cari dta Employee ID, apakah ada atau tidak
    $strIDEmployee = getIDEmployee($db, $strDataEmployee);
    if ($strIDEmployee == "") {
        $strError = $error['data_not_found'];
        $bolOK = false;
    }
    // simpan data -----------------------
    if ($bolOK) {
        if ($strDataID == "") {
            // data baru
            $strSQL = "INSERT INTO hrd_overtime_terapis_employee (created,created_by,modified_by, ";
            $strSQL .= "id_employee,overtime_date, qty, ot_code, note, status) ";
            $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
            $strSQL .= "'$strIDEmployee','$strDataDate', '$strDataQty', '$strDataTerapisCode', ";
            $strSQL .= "'$strDataNote', 0 )";
            $resExec = $db->execute($strSQL);
            // ambil data ID-nya
            $strSQL = "SELECT id FROM hrd_overtime_terapis_employee WHERE id_employee = '$strIDEmployee' ";
            $strSQL .= "AND overtime_date = '$strDataDate' AND qty ='$strDataQty' AND ot_code = '$strDataTerapisCode' ";
            $strSQL .= "ORDER BY id DESC";
            $resDb = $db->execute($strSQL);
            if ($rowDb = $db->fetchrow($resDb)) {
                $strDataID = $rowDb['id'];
            }
            writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strIDEmployee", 0);
        } else {
            $strSQL = "UPDATE hrd_overtime_terapis_employee SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
            $strSQL .= "id_employee = '$strIDEmployee', overtime_date = '$strDataDate', ";
            $strSQL .= "ot_code = '$strDataTerapisCode', qty = '$strDataQty', ";
            $strSQL .= "note = '$strDataNote' ";
            $strSQL .= "WHERE id = '$strDataID' ";
            $resExec = $db->execute($strSQL);
            writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$strIDEmployee", 0);
        }
        $strError = $messages['data_saved'];
    } else { // ---- data SALAH
        // gunakan data yang diisikan tadi
        $arrData['dataEmployee'] = $strDataEmployee;
        $arrData['dataDate'] = $strDataDate;
        $arrData['dataQty'] = $strDataQty;
        $arrData['dataNote'] = $strDataNote;
        $arrData['dataID'] = $strDataID;
        $arrData['dataTerapisCode'] = $strDataTerapisCode;
    }
    return $bolOK;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    if (isset($_REQUEST['btnSave'])) {
        if ($bolCanEdit) {
            $bolOK = saveData($db, $strDataID, $strError);
            if ($strError != "") {
                $strMessage = $strError;
                $strMsgClass = ($bolOK) ? "bgOK" : "bgCancel";
            }
            //$strDataID = ""; // biar tidak mengambil dta, melainkan pakai data baru atau data yang dikirim (jika error)
        }
    }
    if ($bolCanView) {
        getData($db, $strDataID);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
    $strReadonly = (scopeCBDataEntry(
        $arrData['dataEmployee'],
        $_SESSION['sessionUserRole'],
        $arrUserInfo
    )) ? "readonly" : "";
    //----- TAMPILKAN DATA ---------
    $strInputDate = "<input class=\"form-control datepicker\" data-date-format=\"yyyy-mm-dd\" type=text size=15 maxlength=10 name=dataDate id=dataDate value=\"" . $arrData['dataDate'] . "\">";
    $strInputEmployee = "<input class=\"form-control\" type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"" . $arrData['dataEmployee'] . "\" $strReadonly>";
    $strInputQty = "<input class=\"form-control\" type=text name=dataQty id=dataQty size=30 maxlength=10 value=\"" . $arrData['dataQty'] . "\">";
    $strInputNote = "<textarea class=\"form-control\" name=dataNote cols=30 rows=2 wrap='virtual' >" . $arrData['dataNote'] . "</textarea>";
    $strInputOtType = getOtTerapisList(
        $db,
        "dataTerapisCode",
        $arrData['dataTerapisCode'],
        $strEmptyOption,
        "",
        "style=\"width:$strDefaultWidthPx\""
    );
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('overtime terapis entry form');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = otTerapisSubMenu($strWordsDataEntry);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>