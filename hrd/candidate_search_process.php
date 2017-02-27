<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=candidate_search_process.php");
    exit();
}
$bolCanView = getUserPermission("candidate_search_process.php", $bolCanEdit, $bolCanDelete, $strError);
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintAll']));
$bolFull = (isset($_REQUEST['filterFull']));
if ($bolFull) {
    if ($bolPrint) {
        $strMainTemplate = getTemplate("candidateSearchProcessFullPrint.html");
    } else {
        $strTemplateFile = getTemplate("candidateSearchProcessFull.html");
    }
} else {
    if ($bolPrint) {
        $strMainTemplate = getTemplate("candidateSearchProcessPrint.html");
    } else {
        $strTemplateFile = getTemplate("candidate_search_process.html");
    }
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strPaging = "&nbsp;";
$strHidden = "";
$strBtnPrint = "";
$intTotalData = 0;
$bolLimit = true; // default, tampilan dibatasi (paging)
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getData($db, &$intRows, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
{
    global $words;
    global $bolPrint;
    global $ARRAY_EMPLOYEE_STATUS;
    global $strPaging;
    global $intTotalData;
    global $intRowsLimit;
    $intRowsLimit = getSetting("rows_per_page");
    if (!is_numeric($intRowsLimit)) {
        $intRowsLimit = 50;
    }
    $intRows = 0;
    $strResult = "";
    // cari total data
    $intTotal = 0;
    $strSQL = "SELECT count(id) AS total FROM hrd_candidate ";
    $strSQL .= "WHERE 1=1 $strKriteria ";
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
    $strSQL = "SELECT *,(EXTRACT(YEAR FROM AGE(birthday))) AS umur FROM hrd_candidate ";
    $strSQL .= "WHERE 1=1 $strKriteria ORDER BY $strOrder candidate_name ";
    if ($bolLimit) {
        $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
    }
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strJoinDate = pgDateFormat($rowDb['join_date'], "d-M-y");
        if ($rowDb['candidateStatus'] == 0 || $rowDb['candidateStatus'] == 1) {
            $strDueDate = pgDateFormat($rowDb['due_date'], "d-M-y");
            $strPermanentDate = "";
        } else {
            $strDueDate = pgDateFormat($rowDb['due_date'], "d-M-y");
            $strPermanentDate = pgDateFormat($rowDb['permanent_date'], "d-M-y");
        }
        if ($rowDb['active'] == 1) {
            $strResignDate = "";
            $strActive = $words['active'];
        } else {
            $strResignDate = pgDateFormat($rowDb['resign_date'], "d-M-y");
            $strActive = $words['not active'];
        }
        $strGender = ($rowDb['gender'] == 0) ? "F" : "M";
        $strResult .= "<tr valign=top>\n";
        if (!$bolPrint) {
            $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
        }
        $strResult .= "  <td nowrap><a href='candidateResume.php?dataID=" . $rowDb['id'] . "'>" . $rowDb['candidateID'] . "</a>&nbsp;</td>";
        $strResult .= "  <td nowrap>" . $rowDb['candidate_name'] . "&nbsp;</td>";
        $strResult .= "  <td nowrap>" . $rowDb['nickname'] . "&nbsp;</td>";
        $strResult .= "  <td nowrap>" . $rowDb['alias'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>$strGender&nbsp;</td>";
        $strResult .= "  <td algin=center>" . $rowDb['umur'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $words[$ARRAY_EMPLOYEE_STATUS[$rowDb['candidateStatus']]] . "&nbsp;</td>";
        $strResult .= "  <td align=center>$strJoinDate&nbsp;</td>";
        $strResult .= "  <td align=center>$strDueDate&nbsp;</td>";
        $strResult .= "  <td align=center>$strPermanentDate&nbsp;</td>";
        $strResult .= "  <td align=center>$strActive&nbsp;</td>";
        $strResult .= "  <td align=center>$strResignDate&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['department_code'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['section_code'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['sub_section_code'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['position_code'] . "&nbsp;</td>";
        //$strResult .= "  <td>" .$rowDb['grade_code']. "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['family_status_code'] . "&nbsp;</td>";
        $strResult .= "  <td>" . nl2br($rowDb['primary_address']) . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['primary_phone'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['email'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
        if (!$bolPrint) {
            $strResult .= "  <td nowrap align=center><a href='candidate_edit.php?dataID=" . $rowDb['id'] . "'>" . $words['edit'] . "</a>&nbsp;</td>";
        }
        $strResult .= "</tr>\n";
    }
    $intTotalData = $intRows;
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$intRows data", 0);
    }
    return $strResult;
} // showData
// fungsi untuk menampilkan data, versi yang lengkap
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getDataFull($db, &$intRows, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
{
    global $words;
    global $bolPrint;
    global $ARRAY_EMPLOYEE_STATUS;
    global $strPaging;
    global $intTotalData;
    global $intRowsLimit;
    global $_SESSION;
    $intRowsLimit = getSetting("rows_per_page");
    if (!is_numeric($intRowsLimit)) {
        $intRowsLimit = 50;
    }
    $intRows = 0;
    $strResult = "";
    // cari total data
    $intTotal = 0;
    $strSQL = "SELECT count(id) AS total FROM hrd_candidate ";
    $strSQL .= "WHERE 1=1 $strKriteria ";
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
    $strSQL = "SELECT *,(EXTRACT(YEAR FROM AGE(birthday))) AS umur FROM hrd_candidate ";
    $strSQL .= "WHERE 1=1 $strKriteria ORDER BY $strOrder candidate_name ";
    if ($bolLimit) {
        $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
    }
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strJoinDate = pgDateFormat($rowDb['join_date'], "d-M-y");
        if ($rowDb['candidateStatus'] == 0 || $rowDb['candidateStatus'] == 1) {
            $strDueDate = pgDateFormat($rowDb['due_date'], "d-M-y");
            $strPermanentDate = "";
        } else {
            $strDueDate = pgDateFormat($rowDb['due_date'], "d-M-y");
            $strPermanentDate = pgDateFormat($rowDb['permanent_date'], "d-M-y");
        }
        if ($rowDb['active'] == 1) {
            $strResignDate = "";
            $strActive = $words['active'];
        } else {
            $strResignDate = pgDateFormat($rowDb['resign_date'], "d-M-y");
            $strActive = $words['not active'];
        }
        $strGender = ($rowDb['gender'] == 0) ? "F" : "M";
        $strActive = ($rowDb['active'] == 0) ? "" : "&radic;";
        $strResult .= "<tr valign=top>\n";
        if (!$bolPrint) {
            $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
        }
        $strResult .= "  <td nowrap><a href='candidateResume.php?dataID=" . $rowDb['id'] . "'>" . $rowDb['candidateID'] . "</a>&nbsp;</td>";
        //$strResult .= "  <td nowrap>" .$rowDb['oldCandidateID']. "&nbsp;</td>";
        $strResult .= "  <td nowrap>" . $rowDb['candidate_name'] . "&nbsp;</td>";
        $strResult .= "  <td nowrap>" . $rowDb['nickname'] . "&nbsp;</td>";
        $strResult .= "  <td nowrap>" . $rowDb['alias'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>$strGender&nbsp;</td>";
        $strResult .= "  <td>" . nl2br($rowDb['primary_address']) . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['primary_city'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['primary_zip'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['primary_phone'] . "&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>"; // address 2
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['houseStatus'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['email'] . "&nbsp;</td>";
        $strResult .= "  <td>" . nl2br($rowDb['emergency_contact']) . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['emergency_relation'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['emergency_address'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['emergency_phone'] . "&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>"; // address 2
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['birthplace'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['birthday'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['umur'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['weight'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['height'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['blood_type'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['id_card'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['driver_license_a'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['driver_license_c'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['driver_license_b'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['nationality'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['passport'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['religion_code'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['education_level_code'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['family_status_code'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['wedding_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td>" . $words[$ARRAY_EMPLOYEE_STATUS[$rowDb['candidateStatus']]] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['groupCode'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['sub_section_code'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['section_code'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['department_code'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['position_code'] . "&nbsp;</td>";
        if ($_SESSION['sessionUserRole'] == 2) {
            $strResult .= "  <td>" . $rowDb['grade_code'] . "&nbsp;</td>";
        } else {
            $strResult .= "  <td>&nbsp;</td>";
        }
        $strResult .= "  <td>" . $rowDb['transportCode'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['join_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['due_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['permanent_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td align=center>$strActive&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['resign_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['jamsostek_no'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['koperasiNo'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['pukfspmiNo'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['insuranceNo'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['bank'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['bank_account'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['npwp'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
        if (!$bolPrint) {
            $strResult .= "  <td nowrap align=center><a href='candidate_edit.php?dataID=" . $rowDb['id'] . "'>" . $words['edit'] . "</a>&nbsp;</td>";
        }
        $strResult .= "</tr>\n";
    }
    $intTotalData = $intRows;
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$intRows data", 0);
    }
    return $strResult;
} // showDataFull
// fungsi untuk menghapus data
function deleteData($db)
{
    global $_REQUEST;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            // hapus file foto --- BELUM
            $strSQL = "";
            $strSQL .= "DELETE FROM \"hrdCandidateAddress\" WHERE id_candidate = '$strValue'; ";
            $strSQL .= "DELETE FROM \"hrdCandidateContact\" WHERE id_candidate = '$strValue'; ";
            $strSQL .= "DELETE FROM \"hrdCandidatePhone\" WHERE id_candidate = '$strValue'; ";
            $strSQL .= "DELETE FROM \"hrdCandidateFamily\" WHERE id_candidate = '$strValue'; ";
            $strSQL .= "DELETE FROM \"hrdCandidateEducation\" WHERE id_candidate = '$strValue'; ";
            $strSQL .= "DELETE FROM \"hrdCandidateTraining\" WHERE id_candidate = '$strValue'; ";
            $strSQL .= "DELETE FROM \"hrdCandidateWork\" WHERE id_candidate = '$strValue'; ";
            $strSQL .= "DELETE FROM \"hrdCandidateDepartmentHistory\" WHERE id_candidate = '$strValue'; ";
            $strSQL .= "DELETE FROM \"hrdCandidatePositionHistory\" WHERE id_candidate = '$strValue'; ";
            $strSQL .= "DELETE FROM \"hrdCandidateGradeHistory\" WHERE id_candidate = '$strValue'; ";
            $strSQL .= "DELETE FROM \"hrdCandidateStatusHistory\" WHERE id_candidate = '$strValue'; ";
            $strSQL .= "DELETE FROM hrd_candidate WHERE id = '$strValue'; ";
            $resExec = $db->execute($strSQL);
        }
    }
} //deleteData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    if ($bolCanDelete) {
        deleteData($db);
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    (isset($_REQUEST['filterCandidateID'])) ? $strFilterCandidateID = trim(
        $_REQUEST['filterCandidateID']
    ) : $strFilterCandidateID = "";
    (isset($_REQUEST['filterName'])) ? $strFilterName = trim($_REQUEST['filterName']) : $strFilterName = "";
    (isset($_REQUEST['filterPosition'])) ? $strFilterPosition = trim(
        $_REQUEST['filterPosition']
    ) : $strFilterPosition = "";
    (isset($_REQUEST['filterStatus'])) ? $strFilterStatus = $_REQUEST['filterStatus'] : $strFilterStatus = "";
    (isset($_REQUEST['dataPage'])) ? $intCurrPage = $_REQUEST['dataPage'] : $intCurrPage = 1;
    (isset($_REQUEST['dataSort'])) ? $strSortBy = $_REQUEST['dataSort'] : $strSortBy = "";
    $strInputSortBy = $strSortBy;
    if (!is_numeric($intCurrPage)) {
        $intCurrPage = 1;
    }
    if ($strSortBy != "") {
        $strSortBy = "\"$strSortBy\", ";
    }
    $strBtnPrint = "<input type=button name='btnPrint' value=\"" . $words['print'] . "\" onClick=\"printData($intCurrPage);\">";
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    $strKriteria = "";
    if (isset($_REQUEST['btnShowAll']) || isset($_REQUEST['btnPrintAll'])) {
        $strKriteria = "";
        $bolLimit = false;
    } else if (isset($_REQUEST['btnSearch']) || isset($_REQUEST['btnPrint'])) {
        $strInfoKriteria = "";
        if ($strFilterActive != "") {
            $strKriteria .= "AND active = '$strFilterActive' ";
        }
        if ($strFilterStatus != "") {
            $strKriteria .= "AND \"candidateStatus\" = '$strFilterStatus' ";
        }
        if ($strFilterCandidateID != "") {
            $strKriteria .= "AND upper(\"candidateID\") like '%" . strtoupper($strFilterCandidateID) . "%' ";
        }
        if ($strFilterName != "") {
            $strKriteria .= "AND (upper(candidate_name) like '%" . strtoupper($strFilterName) . "%' ";
            $strKriteria .= "OR upper(\"nickname\") like '%" . strtoupper($strFilterName) . "%' ";
            $strKriteria .= "OR upper(\"alias\") like '%" . strtoupper($strFilterName) . "%') ";
        }
        if ($strFilterPosition != "") {
            $strKriteria .= "AND position_code = '$strFilterPosition' ";
        }
        if ($strFilterDepartment != "") {
            $strKriteria .= "AND department_code = '$strFilterDepartment' ";
        }
        if ($strFilterSection != "") {
            $strKriteria .= "AND section_code = '$strFilterSection' ";
        }
        if ($strFilterSubsection != "") {
            $strKriteria .= "AND sub_section_code = '$strFilterSubsection' ";
        }
        if ($strFilterGrade != "") {
            $strKriteria .= "AND grade_code = '$strFilterGrade' ";
        }
        if ($strFilterTransport != "") {
            $strKriteria .= "AND transport_code = '$strFilterTransport' ";
        }
        if ($strFilterGroup != "") {
            $strKriteria .= "AND \"groupCode\" = '$strFilterGroup' ";
        }
    } else { // jngan tampilkan data
        $strKriteria .= "AND 1=2 ";
        $strBtnPrint = ""; // tidak perlu tampil
    }
    $strDataDetail = "";
    if ($bolCanView) {
        if ($bolFull) {
            //$strDataDetail = getDataFull($db,$intTotalData, $strKriteria, $intCurrPage, $bolLimit, $strSortBy);
        } else {
            //$strDataDetail = getData($db,$intTotalData, $strKriteria, $intCurrPage, $bolLimit, $strSortBy);
        }
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
    // generate data hidden input dan element form input
    $intDefaultWidth = 30;
    $intDefaultWidthPx = 200;
    $intDefaultHeight = 3;
    $strInputFilterCandidateID = "<input type=text name=filterCandidateID size=$intDefaultWidth value=\"$strFilterCandidateID\" style=\"width:$intDefaultWidthPx\"width:$intDefaultWidthPx\">";
    $strInputFilterName = "<input type=text name=filterName size=$intDefaultWidth value=\"$strFilterName\" style=\"width:$intDefaultWidthPx\">";
    $strInputFilterPosition = getPositionList(
        $db,
        "filterPosition",
        $strFilterPosition,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\""
    );
    //$strInputFilterStatus = getCandidateStatusList("filterStatus", $strFilterStatus, $strEmptyOption," style=\"width:$intDefaultWidthPx\"");
    $strInputFilterStatus = "";
    if ($bolFull) {
        $strHidden .= "<input type=hidden name='filterFull' value=\"$strFilterCandidateID\">";
        $strInputViewType = "<input type=checkbox name='filterFull' value=\"$strFilterCandidateID\" checked>";
    } else {
        $strInputViewType = "<input type=checkbox name='filterFull' value=\"$strFilterCandidateID\">";
    }
    $strHidden .= "<input type=hidden name=filterCandidateID value=\"$strFilterCandidateID\">";
    $strHidden .= "<input type=hidden name=filterName value=\"$strFilterName\">";
    $strHidden .= "<input type=hidden name=filterPosition value=\"$strFilterPosition\">";
    $strHidden .= "<input type=hidden name=filterStatus value=\"$strFilterStatus\">";
}
$strInitAction .= "
    document.formInput.filterCandidateID.focus();
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>