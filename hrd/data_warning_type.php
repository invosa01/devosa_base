<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_warning_type.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
    die(accessDenied($_SERVER['HTTP_REFERER']));
}
$db = new CdbClass;
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
$strDisableApprove = ($_SESSION['sessionUserRole'] == ROLE_ADMIN) ? "" : "disabled";
$strWordsWarningTypeData = getWords("warning type data");
$strWordsINPUTDATA = getWords("input data");
$strWordsCode = getWords("code");
$strWordsDuration = getWords("duration");
$strWordsNote = getWords("note");
$strWordsSave = getWords("save");
$strWordsAddNew = getWords("add new");
$strWordsDelete = getWords("delete");
$strWordsCODE = getWords("code");
$strWordsDURATION = getWords("duration");
$strWordsNOTE = getWords("note");
$strWordsLISTOFWARNINGTYPE = getWords("list of warning type");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data per barisnya aja
function getDataRows($rowDb, $intRows)
{
    global $words;
    $strResult = "";
    $strClass = "class=bgCheckedData";
    $strAddChar = "&nbsp;&nbsp;";
    $strResult .= "<tr valign=top $strClass>\n";
    $strResult .= "  <td nowrap>$strAddChar<input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\">\n";
    $strResult .= "  <td nowrap><input type=hidden name=detailCode$intRows value=\"" . $rowDb['code'] . "\" disabled>" . $rowDb['code'] . "&nbsp;</td>";
    $strResult .= "  <td align=right><input type=hidden name=detailDuration$intRows value=\"" . $rowDb['duration'] . "\" disabled>" . $rowDb['duration'] . "&nbsp;</td>";
    $strResult .= "  <td><input type=hidden name=detailNote$intRows value=\"" . $rowDb['note'] . "\" disabled>" . $rowDb['note'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap align=center><a href='javascript:editData($intRows)'>" . $words['edit'] . "</a>&nbsp;</td>";
    $strResult .= "</tr>\n";
    return $strResult;
}

// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    $intRows = 0;
    $strResult = "";
    // cari dulu data temporer yang link IDnya ada
    $strSQL = "SELECT * FROM hrd_warning_type ";
    $strSQL .= "WHERE 1 = 1 $strKriteria ORDER BY $strOrder \"code\" ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= getDataRows($rowDb, $intRows);
    }
    // cari dulu data temporer yang link IDnya ada
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
    $strFields = "created, modified_by, created_by, code, \"duration\", note";
    // cek ijinnya
    $bolIsManager = ($_SESSION['sessionUserRole'] == ROLE_ADMIN);
    (isset($_REQUEST['dataCode'])) ? $strDataCode = trim($_REQUEST['dataCode']) : $strDataCode = "";
    (isset($_REQUEST['dataDuration'])) ? $strDataDuration = $_REQUEST['dataDuration'] : $strDataDuration = "0";
    (isset($_REQUEST['dataNote'])) ? $strDataNote = $_REQUEST['dataNote'] : $strDataNote = "";
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    (isset($_REQUEST['dataFlag'])) ? $strDataFlag = $_REQUEST['dataFlag'] : $strDataFlag = "2";
    // cek validasi -----------------------
    if ($strDataCode == "") {
        $strError = $error['empty_code'];
        return false;
    } else {
        ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
        if (isDataExists($db, "hrd_warning_type", "code", $strDataCode, $strKriteria)) {
            $strError = $error['duplicate_code'] . "  -> $strDataCode";
            return false;
        }
    }
    // simpan data -----------------------
    if ($strDataID == "") {
        // data baru
        $strFlag = 0;//($bolIsManager) ? 0 : 2;
        $strSQL = "INSERT INTO hrd_warning_type (created,created_by,modified_by, ";
        $strSQL .= "code,duration, note) ";
        $strSQL .= "VALUES(now(),'" . $_SESSION['sessionUserID'] . "','" . $_SESSION['sessionUserID'] . "', ";
        $strSQL .= "'$strDataCode', '$strDataDuration', '$strDataNote') ";
        writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataCode", 0);
    } else {
        /*if (!$bolIsManager) {
          if ($strDataFlag == 0) { // master, bikin temporernnya
            $strDataID = getTempData($db, "hrdWarningType", $strFields, $strDataID,2);
          }
        }*/
        $strSQL = "UPDATE hrd_warning_type ";
        $strSQL .= "SET modified_by = '" . $_SESSION['sessionUserID'] . "', \"code\" = '$strDataCode', ";
        $strSQL .= "\"duration\"= '$strDataDuration', note = '$strDataNote' ";
        $strSQL .= "WHERE id = '$strDataID' ";
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataCode", 0);
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
            //        $strSQL  = "DELETE FROM hrd_warning_type WHERE link_id = '$strValue'; ";
            $strSQL = "DELETE FROM hrd_warning_type WHERE id = '$strValue'; ";
            $resExec = $db->execute($strSQL);
            $i++;
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
    }
} //deleteData
// fungsi untuk approve data oleh manager
function approveData($db)
{
    global $_REQUEST;
    if ($_SESSION['sessionUserRole'] != ROLE_ADMIN) {
        return 0;
    }
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            approveTempData($db, "hrdWarningType", $strValue);
            $i++;
        }
    }
} //approveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
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
    } else if (isset($_REQUEST['btnApprove']) && $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        approveData($db);
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $intTotalData);
    } else {
        showError("view_denied");
    }
}
/*
  $strDataID = getPostValue('dataID');
  $isNew = ($strDataID == "");
   if ($bolCanEdit)
  {
    $f = new clsForm("formInput", 1, "100%", "");
    $f->caption = strtoupper(vsprintf(getWords("input data %s"), getWords("family status")));

    $f->addHidden("dataID", $strDataID);
    $f->addInput(getWords("code"), "dataCode", "", array("size" => 20), "string", true, true, true);  
    $f->addSelect(getWords("marital status"), "dataMaritalStatus", getDataListMaritalStatus(), array(), "string", true, true, true);  
    $f->addInput(getWords("number of children"), "dataChildren", "", array("size" => 3), "integer", true, true, true);  
    $f->addInput(getWords("tax reduction"), "dataTaxReduction", "", array("size" => 10), "numeric", true, true, true);  
    $f->addTextArea(getWords("note"), "dataNote", "", array("cols" => 48, "rows" => 2), "string", false, true, true);  

    $f->addSubmit("btnSave", getWords("save"), array("onClick" => "javascript:myClient.confirmSave();"), true, true, "", "", "saveData()");
    $f->addButton("btnAdd", getWords("add new"), array("onClick" => "javascript:myClient.editData(0);"));
    
    $formInput = $f->render();
  }
  else
    $formInput = "";
  */
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$strPageDesc = getWords("Warning Type");;
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
?>