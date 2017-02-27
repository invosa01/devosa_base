<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
// Data Privilage followed from parent (employee_edit.php)
$dataPrivilege = getDataPrivileges(
    basename("employee_edit.php"),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView && $_POST['dataID'] == "") {
    die(getWords('view denied'));
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
$strInitCalendar = "";
$strEmployeeID = "";
$stremployee_name = "";
$strMessages = "";
$strMsgClass = "";
$bolError = false;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_FAMILY_RELATION;
    global $intDefaultWidth;
    global $strInitCalendar;
    global $strEmptyOption;
    //$intDefaultWidth = 15;
    $intDefaultWidth = 35;
    $intRows = 0;
    $intShown = 0;
    $intAdd = 10; // maksimum tambahan
    $strResult = "";
    $strDateSparator = $_SESSION['sessionDateSetting']['date_sparator'];
    //$strDatePosYear = $_SESSION['sessionDateSetting']['pos_year'];
    //$strDatePosMonth = $_SESSION['sessionDateSetting']['pos_month'];
    //$strDatePosDay = $_SESSION['sessionDateSetting']['pos_day'];
    $strDatePHPFormat = $_SESSION['sessionDateSetting']['php_format'];
    $strDateHTMLFormat = $_SESSION['sessionDateSetting']['html_format'];
    $strDateFormat = $_SESSION['sessionDateFormat'];
    $strNow = date($strDatePHPFormat);
    $strSQL = "SELECT *, EXTRACT(YEAR FROM AGE(birthday)) AS umur FROM hrd_employee_family ";
    $strSQL .= "WHERE 1=1 $strKriteria ORDER BY $strOrder relation, birthday ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        //$birthDay = explode('-', $rowDb['birthday']);
        //$rowDb['birthday'] = $birthDay[1] . '/' . $birthDay[2] . '/' . $birthDay[0];
        $birthDay = sqlToStandarDateNew($rowDb['birthday'], $strDateSparator, $strDateFormat);
        $intRows++;
        $intShown++;
        $strResult .= "<tr valign=top id=\"detailRows$intRows\">\n";
        $strResult .= "  <td nowrap><input type=hidden name='detailID$intRows' value=\"" . $rowDb['id'] . "\">\n";
        $strResult .= "  <input type=text class=\"form-control\" maxlength=127 name=detailName$intRows value=\"" . $rowDb['name'] . "\"></td>";
        $strResult .= "  <td nowrap>" . getGenderList("detailGender$intRows", $rowDb['gender']) . "</td>";
        $strResult .= "  <td nowrap>" . getFamilyRelationList("detailRelation$intRows", $rowDb['relation']) . "</td>";
        $strResult .= "  <td nowrap><div class=\"input-group\"><input type=text class=\"form-control datepicker\" maxlength=10 name=detailBirthday$intRows id=detailBirthday$intRows value=\"$birthDay\" data-date-format=\"$strDateHTMLFormat\" >";
        $strResult .= "<span class=\"input-group-addon\"><i class=\"fa fa-calendar\"></i></span></div></td>";
        $strResult .= "  <td nowrap align=right>" . $rowDb['umur'] . "&nbsp;</td>";
        $strResult .= "  <td nowrap>" . getEducationList(
                $db,
                "detailEducation$intRows",
                $rowDb['education_code'],
                $strEmptyOption,
                ""
            ) . "</td>";
        $strResult .= "  <td nowrap><input type=text class=\"form-control\" maxlength=31 name=detailJob$intRows value=\"" . $rowDb['job'] . "\"></td>";
        $strResult .= "  <td nowrap><input type=text class=\"form-control\" maxlength=127 name=detailCompany$intRows value=\"" . $rowDb['company'] . "\"></td>";
        $strResult .= "  <td nowrap><input type=text class=\"form-control\" maxlength=31 name=detailPosition$intRows value=\"" . $rowDb['position'] . "\"></td>";
        $strResult .= "  <td nowrap><input type=text class=\"form-control\" maxlength=255 name=detailNote$intRows value=\"" . $rowDb['note'] . "\"></td>";
        $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
        $strResult .= "  <td align=center><div class=\"checkbox\"><label><input type=checkbox name='chkID$intRows' class=\"checkbox-inline\" value=\"" . $rowDb['id'] . "\" $strAction></label></div></td>\n";
        $strResult .= "</tr>\n";
        //$strInitCalendar .= "Calendar.setup({ inputField:\"detailBirthday$intRows\", button:\"target_$intRows\" });\n";
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
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
        $strResult .= "  <td nowrap><input type=text class=\"form-control\" maxlength=50 name=detailName$intRows $strDisabled></td>";
        $strResult .= "  <td nowrap>" . getGenderList("detailGender$intRows") . "</td>";
        $strResult .= "  <td nowrap>" . getFamilyRelationList("detailRelation$intRows", "", "", $strDisabled) . "</td>";
        $strResult .= "  <td nowrap><div class=\"input-group\"><input type=text class=\"form-control datepicker\" maxlength=10 name=detailBirthday$intRows id=\"detailBirthday$intRows\" value=$strNow data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\">&nbsp;";
        $strResult .= "<span class=\"input-group-addon\"><i class=\"fa fa-calendar\"></i></span></div></td>";
        $strResult .= "  <td nowrap>&nbsp;</td>";
        $strResult .= "  <td nowrap>" . getEducationList(
                $db,
                "detailEducation$intRows",
                "",
                $strEmptyOption,
                ""
            ) . "</td>";
        $strResult .= "  <td nowrap><input type=text class=\"form-control\" maxlength=50 name=detailJob$intRows></td>";
        $strResult .= "  <td nowrap><input type=text class=\"form-control\" maxlength=50 name=detailCompany$intRows></td>";
        $strResult .= "  <td nowrap><input type=text class=\"form-control\" maxlength=50 name=detailPosition$intRows></td>";
        $strResult .= "  <td nowrap><input type=text class=\"form-control\" maxlength=50 name=detailNote$intRows  $strDisabled></td>";
        $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
        $strResult .= "  <td align=center><div class=\"checkbox\"><label><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\" $strAction class=\"checkbox-inline\"></label></div></td>\n";
        $strResult .= "</tr>\n";
        //$strInitCalendar .= "Calendar.setup({ inputField:\"detailBirthday$intRows\", button:\"target_$intRows\" });\n";
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
    global $messages;
    $strError = "";
    $strDateSparator = $_SESSION['sessionDateSetting']['date_sparator'];
    $strDatePosYear = $_SESSION['sessionDateSetting']['pos_year'];
    $strDatePosMonth = $_SESSION['sessionDateSetting']['pos_month'];
    $strDatePosDay = $_SESSION['sessionDateSetting']['pos_day'];
    //$strDatePHPFormat = $_SESSION['sessionDateSetting']['php_format'];
    //$strDateHTMLFormat = $_SESSION['sessionDateSetting']['html_format'];
    //$strDateFormat = $_SESSION['sessionDateFormat'];
    (isset($_REQUEST['maxDetail'])) ? $intMax = $_REQUEST['maxDetail'] : $intMax = 0;
    for ($i = 1; $i <= $intMax; $i++) {
        (isset($_REQUEST['detailID' . $i])) ? $strID = $_REQUEST['detailID' . $i] : $strID = "";
        (isset($_REQUEST['detailName' . $i])) ? $strName = $_REQUEST['detailName' . $i] : $strName = "";
        (isset($_REQUEST['detailRelation' . $i])) ? $strRelation = $_REQUEST['detailRelation' . $i] : $strRelation = "5";
        (isset($_REQUEST['detailBirthday' . $i])) ? $strBirthday = $_REQUEST['detailBirthday' . $i] : $strBirthday = "";
        (isset($_REQUEST['detailGender' . $i])) ? $strGender = $_REQUEST['detailGender' . $i] : $strGender = "0";
        (isset($_REQUEST['detailEducation' . $i])) ? $strEducation = $_REQUEST['detailEducation' . $i] : $strEducation = "";
        (isset($_REQUEST['detailJob' . $i])) ? $strJob = $_REQUEST['detailJob' . $i] : $strJob = "";
        (isset($_REQUEST['detailCompany' . $i])) ? $strCompany = $_REQUEST['detailCompany' . $i] : $strCompany = "";
        (isset($_REQUEST['detailPosition' . $i])) ? $strPosition = $_REQUEST['detailPosition' . $i] : $strPosition = "";
        (isset($_REQUEST['detailNote' . $i])) ? $strNote = $_REQUEST['detailNote' . $i] : $strNote = "";
        if ($strBirthday == "" || $strBirthday == "''") {
            $strBirthday = "NULL";
        } else {
            //$strBirthday = explode('/', $strBirthday);
            $strBirthday = "'" . standardDateToSQLDateNew(
                    $strBirthday,
                    $strDateSparator,
                    $strDatePosYear,
                    $strDatePosMonth,
                    $strDatePosDay
                ) . "'";
        }
        if ($strID == "") {
            if ($strName != "") { // insert new data
                $strSQL = "INSERT INTO hrd_employee_family (created,modified, created_by, modified_by,";
                $strSQL .= "id_employee, name, relation, birthday, education_code, ";
                $strSQL .= "job,company, position, note, gender) ";
                $strSQL .= "VALUES(now(), now(), '" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
                $strSQL .= "'$strDataID', '$strName', '$strRelation', $strBirthday, '$strEducation', ";
                $strSQL .= "'$strJob', '$strCompany', '$strPosition', '$strNote', '$strGender') ";
                $resDb = $db->execute($strSQL);
                writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataID", 0);
            }
        } else {
            if ($strName == "") {
                // delete data
                $strSQL = "DELETE FROM hrd_employee_family WHERE id = '$strID' ";
                $resDb = $db->execute($strSQL);
                writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$strDataID", 0);
            } else {
                // update data
                $strSQL = "UPDATE hrd_employee_family SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
                $strSQL .= "modified = now(), name = '$strName', relation = '$strRelation', ";
                $strSQL .= "education_code = '$strEducation', job = '$strJob', gender = '$strGender', ";
                $strSQL .= "company = '$strCompany', position = '$strPosition',  ";
                $strSQL .= "birthday = $strBirthday, note = '$strNote' WHERE id = '$strID' ";
                $resDb = $db->execute($strSQL);
                writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataID", 0);
            }
        }
    }
    $strError = $messages['data_saved'] . " >> " . date("d-M-Y H:i:s");
    return true;
} // saveData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $bolEmployee = ($_SESSION['sessionUserRole'] < ROLE_ADMIN);
    (isset($_POST['dataID'])) ? $strDataID = $_POST['dataID'] : $strDataID = "";
    if ($bolCanEdit && $strDataID != "") {
        if (isset($_POST['btnSave'])) {
            if ($bolEmployee || !saveData($db, $strDataID, $strError)) {
                //echo "<script>alert(\"$strError\")</script>";
                $bolError = true;
                if ($bolEmployee) {
                    $strError = getWords("sorry, you can not edit this page");
                }
            }
            if ($strError != "") {
                $strMessages = $strError;
                $strMsgClass = ($bolError) ? "class=bgError" : "class=bgOK";
            }
        }
    }
    if ($strDataID == "") {
        redirectPage("employee_search.php");
        exit();
    } else {
        // cari info karyawan
        $strSQL = "SELECT employee_id, employee_name, flag, link_id FROM hrd_employee WHERE id = '$strDataID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['flag'] != 0 AND $rowDb['link_id'] != "") { // folder temporer
                $strDataID = $rowDb['link_id'];
            }
            $strEmployeeID = $rowDb['employee_id'];
            $strEmployeeName = strtoupper($rowDb['employee_name']);
            if ($bolEmployee && ($strEmployeeID != $arrUserInfo['employee_id'])) {
                redirectPage("employee_search.php");
                exit();
            }
        } else {
            redirectPage("employee_search.php");
            exit();
        }
        ($strDataID == "") ? $strKriteria = " AND 1=2 " : $strKriteria = " AND id_employee = '$strDataID' ";
        if ($strDataID != "") {
            $strDataDetail = getData($db, $intTotalData, $strKriteria);
        } else {
            showError("view_denied");
            $strDataDetail = "";
        }
    }
}
$strInitAction = $strInitCalendar;
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords("employee data");
$strPageDesc = getWords("employee family information");
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strWordsFamilyData = getWords("family data");
$pageSubMenu = employeeEditSubmenu($strWordsFamilyData);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>