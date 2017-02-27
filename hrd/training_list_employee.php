<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
$dataPrivilege = getDataPrivileges("training_list.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
    die(accessDenied($_SERVER['HTTP_REFERER']));
}
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strPaging = "";
$strHidden = "";
$strBtnPrint = "";
$intTotalData = 0;
$bolLimit = true; // default, tampilan dibatasi (paging)
$strButtons = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
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

// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getDataLama($db, &$intRows, $strFilterYear, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
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
    $intTotal = 0;
    $strSQL = "SELECT count(t1.id) AS total FROM hrd_training_employee AS t1, ";
    $strSQL .= "hrd_employee AS t2 WHERE t1.id_employee = t2.id ";
    $strSQL .= "AND t2.flag=0 AND t1.status = 0 $strKriteria ";
    $strSQL .= "AND EXTRACT(year FROM t1.date_from) = '$strFilterYear' ";
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
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.department_code, ";
    $strSQL .= "t2.section_code FROM hrd_training_employee AS t1, hrd_employee AS t2 ";
    $strSQL .= "WHERE t1.id_employee = t2.id AND t2.flag=0 AND t1.status = 0 $strKriteria ";
    $strSQL .= "AND EXTRACT(year FROM t1.date_from) = '$strFilterYear' ";
    $strSQL .= "ORDER BY $strOrder t2.employee_name, t2.id, t1.date_from DESC ";
    if ($bolLimit) {
        $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
    }
    $fltTotal = 0;
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strTgl = pgDateFormat($rowDb['date_from'], "d-M-y");
        if ($rowDb['date_thru'] != "") {
            $strTgl .= " -- <br>" . pgDateFormat($rowDb['date_thru'], "d-M-y");
        }
        $strResult .= " <tr valign=top>";
        if (!$bolPrint) {
            $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
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
        $strResult .= "  <td>" . $rowDb['subject'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['institution'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['trainer'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $strTgl . "&nbsp;</td>";
        $strResult .= "  <td align=right>" . cekStandardFormat($rowDb['cost']) . "</td>";
        $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
        if (!$bolPrint) {
            $strResult .= "  <td nowrap align=center><a href='training_evaluation_edit.php?dataID=" . $rowDb['id'] . "'>" . getWords(
                    'evaluation'
                ) . "</a>&nbsp;</td>";
        }
        $fltTotal += $rowDb['cost'];
        //$strResult .= getDataPerRow($rowDb,$intRows);
        $strResult .= " </tr>\n";
    }
    // tambahkan nnilai total
    $intTotalData = $intRows;
    if ($intRows > 0) {
        $strResult .= " <tr valign=top>\n";
        $strResult .= "  <td>&nbsp;</td>\n";
        $strResult .= "  <td colspan=4>&nbsp;</td>\n";
        $strResult .= "  <td colspan=4 align=right><strong>" . $words['total'] . "</strong>&nbsp;</td>\n";
        $strResult .= "  <td align=right><strong>" . cekStandardFormat($fltTotal) . "</strong></td>\n";
        $strResult .= "  <td align=right colspan=2>&nbsp;</td>\n";
        $strResult .= " </tr>\n";
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$intRows data", 0);
    }
    return $strResult;
} // showData
function getData($db, &$intRows, $strFilterYear, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
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
    $strSQL = "SELECT count(t1.id) AS total FROM hrd_training_request_participant AS t1 ";
    $strSQL .= "LEFT JOIN hrd_training_request AS t3 ON t1.id_request = t3.id ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t3.status = '" . REQUEST_STATUS_APPROVED . "' AND t3.training_status  = 0 ";
    $strSQL .= "AND t1.status = 0 AND t2.flag=0 $strKriteria ";
    $strSQL .= "AND EXTRACT(year FROM t3.training_date) = '$strFilterYear' ";
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
    $strSQL = "SELECT t3.*, t1.id_employee, t1.id as id_participant, t1.cost as cost_participant, ";
    $strSQL .= "t1.note, t1.evaluation, t1.evaluation2, ";
    $strSQL .= "t2.employee_id, t2.employee_name, t2.department_code, ";
    $strSQL .= "t2.section_code FROM hrd_training_request_participant AS t1 ";
    $strSQL .= "LEFT JOIN hrd_training_request AS t3 ON t1.id_request = t3.id ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t3.status = '" . REQUEST_STATUS_APPROVED . "' AND t3.training_status  = 0 ";
    $strSQL .= "AND t1.status = 0 AND t2.flag=0 $strKriteria ";
    $strSQL .= "AND EXTRACT(year FROM t3.training_date) = '$strFilterYear' ";
    $strSQL .= "ORDER BY $strOrder t2.employee_name, t2.id, t3.training_date DESC ";
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
            $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
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
        $strResult .= "  <td align=right>" . cekStandardFormat($rowDb['cost_participant']) . "</td>";
        //$strResult .= "  <td>" .$rowDb['note']. "&nbsp;</td>";
        $strResult .= "  <td align=right nowrap>" . cekStandardFormat($rowDb['evaluation'], true) . "</td>";
        if (!$bolPrint) {
            $strResult .= "  <td nowrap align=center><a href='training_evaluation_edit.php?dataID=" . $rowDb['id_participant'] . "'>" . getWords(
                    'view'
                ) . "</a>&nbsp;</td>";
        }
        $strResult .= "  <td align=right nowrap>" . cekStandardFormat($rowDb['evaluation2'], true) . "</td>";
        if (!$bolPrint) {
            $strResult .= "  <td nowrap align=center><a href='training_evaluation_edit2.php?dataID=" . $rowDb['id_participant'] . "'>" . getWords(
                    'view'
                ) . "</a>&nbsp;</td>";
            $strCreateSharingSession = "<a href=\"training_sharing_session_edit.php?dataID=" . $rowDb['id_participant'] . "\">" . $words['create'] . "</a>";
            $strResult .= "  <td nowrap align=center>$strCreateSharingSession&nbsp;</td>\n";
        }
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
        $strResult .= "  <td align=right><strong>" . cekStandardFormat($fltTotal) . "</strong></td>\n";
        $strResult .= "  <td align=right colspan=$intSpan2>&nbsp;</td>\n";
        $strResult .= " </tr>\n";
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$intRows data", 0);
    }
    return $strResult;
} // getData
// fungsi untuk menghapus data
function deleteData($db)
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
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $bolIsEmployee = isUserEmployee();
    if ($bolCanDelete && isset($_POST['btnDelete'])) {
        if ($_SESSION['sessionUserRole'] == 3 || $_SESSION['sessionUserRole'] == 4) {
            deleteData($db);
        }
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    (isset($_REQUEST['filterEmployeeID'])) ? $strFilterEmployeeID = trim(
        $_REQUEST['filterEmployeeID']
    ) : $strFilterEmployeeID = "";
    (isset($_REQUEST['filterDepartment'])) ? $strFilterDepartment = $_REQUEST['filterDepartment'] : $strFilterDepartment = "";
    (isset($_REQUEST['filterSection'])) ? $strFilterSection = $_REQUEST['filterSection'] : $strFilterSection = "";
    (isset($_REQUEST['filterYear'])) ? $strFilterYear = $_REQUEST['filterYear'] : $strFilterYear = date("Y");
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
    if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
        if ($arrUserInfo['isDeptHead']) {
            $strFilterDepartment = $arrUserInfo['department_code'];
        } else if ($arrUserInfo['isGroupHead']) {
            $strFilterSection = $arrUserInfo['section_code'];
        } else {
            $strFilterEmployeeID = $arrUserInfo['employee_id'];
        }
    }
    if (isset($_REQUEST['btnSearch']) || isset($_REQUEST['btnPrint'])) {
        $strInfoKriteria = "";
        if ($strFilterEmployeeID != "") {
            $strKriteria .= "AND upper(t2.employee_id) = '" . strtoupper($strFilterEmployeeID) . "' ";
        }
        if ($strFilterDepartment != "") {
            $strKriteria .= "AND t2.department_code = '$strFilterDepartment' ";
        }
        if ($strFilterSection != "") {
            $strKriteria .= "AND t2.section_code = '$strFilterSection' ";
        }
    } else { // jngan tampilkan data, kecuali jika yang login adalah meployee itu sendiri
        /*
         if ($arrUserInfo['employee_id'] == "") {
            $strKriteria .= " AND 1 = 2 "; // pasti salah
            $strBtnPrint = ""; // tidak perlu tampil
          } else {
            $strKriteria .= "AND employee_id = '". $arrUserInfo['employee_id']. "' ";
          }
        */
        $strKriteria .= " AND 1 = 2 "; // pasti salah
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $intTotalData, $strFilterYear, $strKriteria, $intCurrPage, $bolLimit, $strSortBy);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
    // generate data hidden input dan element form input
    $intDefaultWidth = 30;
    $intDefaultWidthPx = 200;
    $intDefaultHeight = 3;
    $strDisabled = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) ? "disabled" : "";
    $strInputFilterEmployeeID = "<input type=text id=filterEmployeeID name=filterEmployeeID size=$intDefaultWidth value=\"$strFilterEmployeeID\" style=\"width:$intDefaultWidthPx\"width:$intDefaultWidthPx\" >";
    $strInputFilterDepartment = getDepartmentList(
        $db,
        "filterDepartment",
        $strFilterDepartment,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\" $strDisabled"
    );
    $strInputFilterSection = getSectionList(
        $db,
        "filterSection",
        $strFilterSection,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\" $strDisabled"
    );
    $strInputFilterYear = getYearList("filterYear", $strFilterYear, "");
    /*
    // tombol untuk check/approve
    if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
      $strButtons = "<input name=btnCheck value=\"" .$words['check']."\" onclick=\"return confirmCheck();\" type=\"submit\">";
    } else if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
      $strButtons = "<input name=btnApprove value=\"" .$words['approve']."\" onclick=\"return confirmCheck();\" type=\"submit\">";
    }
    */
    $strHidden .= "<input type=hidden name=filterEmployeeID value=\"$strFilterEmployeeID\">";
    $strHidden .= "<input type=hidden name=filterDepartment value=\"$strFilterDepartment\">";
    $strHidden .= "<input type=hidden name=filterSection value=\"$strFilterSection\">";
    $strHidden .= "<input type=hidden name=filterYear value=\"$strFilterYear\">";
}
if ($bolPrint) {
    $strMainTemplate = getTemplate(str_replace(".php", "_print.html", basename($_SERVER['PHP_SELF'])));
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