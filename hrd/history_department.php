<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=history_department.php");
    exit();
}
$bolCanView = getUserPermission("history_department.php", $bolCanEdit, $bolCanDelete, $strError);
$strTemplateFile = getTemplate("history_department.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
$strInitCalendar = "";
$strDataEmployee = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataID, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $intDefaultWidth;
    global $strInitCalendar;
    global $strEmptyOption;
    $intDefaultWidthPx = 100;
    $intRows = 0;
    $intShown = 0;
    $intAdd = 10; // maksimum tambahan
    $strResult = "";
    $strNow = date("Y-m-d");
    if ($strDataID != "") {
        $strSQL = "SELECT * FROM hrd_employee_department_history ";
        $strSQL .= "WHERE id_employee = '$strDataID' ";
        $strSQL .= "ORDER BY $strOrder active_date ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $intRows++;
            $intShown++;
            $strResult .= "<tr valign=top id=\"detailRows$intRows\">\n";
            $strResult .= "  <td nowrap><input type=hidden name='detailID$intRows' value=\"" . $rowDb['id'] . "\">\n";
            $strResult .= "  <input type=text size=15 maxlength=10 name=detailDate$intRows id=\"detailDate$intRows\" value=\"" . $rowDb['activeDate'] . "\">&nbsp;";
            $strResult .= "<input type=button id=\"target_$intRows\" value='..'></td>";
            $strResult .= "  <td nowrap>" . getDivisionList(
                    $db,
                    "detailDivision$intRows",
                    $rowDb['division_code'],
                    $strEmptyOption,
                    "",
                    " style=\"width:$intDefaultWidthPx\" "
                ) . "</td>";
            $strResult .= "  <td nowrap>" . getDepartmentList(
                    $db,
                    "detailDepartment$intRows",
                    $rowDb['department_code'],
                    $strEmptyOption,
                    "",
                    " style=\"width:$intDefaultWidthPx\" "
                ) . "</td>";
            $strResult .= "  <td nowrap>" . getSectionList(
                    $db,
                    "detailSection$intRows",
                    $rowDb['section_code'],
                    $strEmptyOption,
                    "",
                    " style=\"width:$intDefaultWidthPx\" "
                ) . "</td>";
            $strResult .= "  <td nowrap>" . getSubSectionList(
                    $db,
                    "detailSubSection$intRows",
                    $rowDb['sub_section_code'],
                    $strEmptyOption,
                    "",
                    " style=\"width:$intDefaultWidthPx\" "
                ) . "</td>";
            $strResult .= "  <td nowrap><input type=text size=20 maxlength=50 name=detailNote$intRows value=\"" . $rowDb['note'] . "\"></td>";
            $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
            $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\" $strAction></td>\n";
            $strResult .= "</tr>\n";
            $strInitCalendar .= "Calendar.setup({ inputField:\"detailDate$intRows\", button:\"target_$intRows\" });\n";
        }
        if ($intRows > 0) {
            writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$strDataID", 0);
        }
        // tambahkan dengan data kosong
        for ($i = 1; $i <= $intAdd; $i++) {
            $intRows++;
            if ($intRows == 1) {
                $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
                $intShown++;
                $strDisabled = "";
            } else {
                $strResult .= "<tr valign=top  id=\"detailRows$intRows\" style=\"display:none\">\n";
                $strDisabled = "disabled";
            }
            $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailDate$intRows id=\"detailDate$intRows\" value=$strNow>&nbsp;";
            $strResult .= "<input type=button id=\"target_$intRows\" value='..'></td>";
            $strResult .= "  <td nowrap>" . getDivisionList(
                    $db,
                    "detailDivision$intRows",
                    "",
                    $strEmptyOption,
                    "",
                    " style=\"width:$intDefaultWidthPx\" $strDisabled "
                ) . "</td>";
            $strResult .= "  <td nowrap>" . getDepartmentList(
                    $db,
                    "detailDepartment$intRows",
                    "",
                    $strEmptyOption,
                    "",
                    " style=\"width:$intDefaultWidthPx\" $strDisabled "
                ) . "</td>";
            $strResult .= "  <td nowrap>" . getSectionList(
                    $db,
                    "detailSection$intRows",
                    "",
                    $strEmptyOption,
                    "",
                    " style=\"width:$intDefaultWidthPx\"  $strDisabled"
                ) . "</td>";
            $strResult .= "  <td nowrap>" . getSubSectionList(
                    $db,
                    "detailSubSection$intRows",
                    "",
                    $strEmptyOption,
                    "",
                    " style=\"width:$intDefaultWidthPx\" $strDisabled "
                ) . "</td>";
            $strResult .= "  <td nowrap><input type=text size=20 maxlength=50 name=detailNote$intRows  $strDisabled></td>";
            $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
            $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\" $strAction></td>\n";
            $strResult .= "</tr>\n";
            $strInitCalendar .= "Calendar.setup({ inputField:\"detailDate$intRows\", button:\"target_$intRows\" });\n";
        }
    }
    // tambahkan hidden data
    $strResult .= "<input type=hidden name=maxDetail value=$intRows>";
    $strResult .= "<input type=hidden name=numShow value=$intShown>";
    return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, $strDataID, &$strError)
{
    global $_REQUEST;
    global $_SESSION;
    global $error;
    $strError = "";
    if ($strDataID == "") {
        return false;
    }
    (isset($_REQUEST['maxDetail'])) ? $intMax = $_REQUEST['maxDetail'] : $intMax = 0;
    for ($i = 1; $i <= $intMax; $i++) {
        (isset($_REQUEST['detailID' . $i])) ? $strID = $_REQUEST['detailID' . $i] : $strID = "";
        (isset($_REQUEST['detailDate' . $i])) ? $strDate = "'" . $_REQUEST['detailDate' . $i] . "'" : $strDate = "";
        (isset($_REQUEST['detailDivision' . $i])) ? $strDivision = $_REQUEST['detailDivision' . $i] : $strDivision = "";
        (isset($_REQUEST['detailDepartment' . $i])) ? $strDepartment = $_REQUEST['detailDepartment' . $i] : $strDepartment = "";
        (isset($_REQUEST['detailSection' . $i])) ? $strSection = $_REQUEST['detailSection' . $i] : $strSection = "";
        (isset($_REQUEST['detailSubSection' . $i])) ? $strSubSection = $_REQUEST['detailSubSection' . $i] : $strSubSection = "";
        (isset($_REQUEST['detailNote' . $i])) ? $strNote = $_REQUEST['detailNote' . $i] : $strNote = "";
        $bolEmpty = true;
        $bolEmpty = ($bolEmpty && ($strDivision == ""));
        $bolEmpty = ($bolEmpty && ($strDepartment == ""));
        $bolEmpty = ($bolEmpty && ($strSection == ""));
        $bolEmpty = ($bolEmpty && ($strSubSection == ""));
        if ($strDate == "" || $strDate == "''") {
            $strDate = "NULL";
        }
        if ($strID == "") {
            if (!$bolEmpty) { // insert new data
                $strSQL = "INSERT INTO hrd_employee_department_history (created,modified_by, created_by, ";
                $strSQL .= "id_employee, division_code, department_code, ";
                $strSQL .= "section_code, sub_section_code, note, active_date) ";
                $strSQL .= "VALUES(now(), '" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
                $strSQL .= "'$strDataID', '$strDivision', '$strDepartment',  ";
                $strSQL .= "'$strSection', '$strSubSection', '$strNote', $strDate) ";
                $resDb = $db->execute($strSQL);
            }
        } else {
            if ($bolEmpty) {
                // delete data
                $strSQL = "DELETE FROM hrd_employee_department_history WHERE id = '$strID' ";
                $resDb = $db->execute($strSQL);
            } else {
                // update data
                $strSQL = "UPDATE hrd_employee_department_history SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
                $strSQL .= "created = now(), division_code = '$strDivision',  ";
                $strSQL .= "department_code = '$strDepartment', active_date = $strDate,  ";
                $strSQL .= "sub_section_code = '$strSubSection', section_code = '$strSection', ";
                $strSQL .= "note = '$strNote' WHERE id = '$strID' ";
                $resDb = $db->execute($strSQL);
            }
        }
    }
    // cari data department terbaru, update data
    $strSQL = "SELECT * FROM hrd_employee_department_history ";
    $strSQL .= "WHERE id_employee = '$strDataID' ORDER BY active_date DESC ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        $strDivision = $rowDb['division_code'];
        $strDepartment = $rowDb['department_code'];
        $strSection = $rowDb['section_code'];
        $strSubSection = $rowDb['sub_section_code'];
    } else {
        $strDivision = "";
        $strDepartment = "";
        $strSection = "";
        $strSubSection = "";
    }
    // update data employee
    $strSQL = "UPDATE hrd_employee SET division_code = '$strDivision', ";
    $strSQL .= "department_code = '$strDepartment', section_code = '$strSection', ";
    $strSQL .= "sub_section_code = '$strSubSection' WHERE id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataID", 0);
    return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
    if (isset($_REQUEST['btnSave'])) {
        if ($bolCanEdit && $strDataID != "") {
            if (!saveData($db, $strDataID, $strError)) {
                echo "<script>alert(\"$strError\")</script>";
            }
        }
    }
    // cari dta perusahaan dulu
    if ($strDataID != "" || $strDataEmployee != "") {
        $strSQL = "SELECT * FROM hrd_employee WHERE 1=1 ";
        if ($strDataID != "") {
            $strSQL .= "AND id = '$strDataID' ";
        } else if ($strDataEmployee != "") {
            $strSQL .= "AND employee_id = '$strDataEmployee' AND flag = 0 ";
        }
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strDataEmployee = $rowDb['employee_id'];
            $strDataID = $rowDb['id'];
        } else {
            $strDataID = "";
        }
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $strDataID, $intTotalData);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
}
$strInitAction .= $strInitCalendar . "
		init();
		document.formFilter.dataEmployee.focus();
		onCodeBlur();
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>