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
$strEmployeeID = "";
$stremployee_name = "";
$intTotalData = 0;
$intDefaultWidth = 30;
$intDefaultWidthPx = 250;
$strMessages = "";
$strMsgClass = "";
$bolError = false;
$strError = "";
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
    global $intDefaultWidthPx;
    global $strEmptyOption;
    $intRows = 0;
    $intShown = 0;
    $intAdd = 6; // maksimum tambahan
    $strResult = "";
    $strNow = date("Y-m-d");
    $strSQL = "SELECT * FROM hrd_employee_work ";
    $strSQL .= "WHERE 1=1 $strKriteria ORDER BY $strOrder year_from ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $intShown++;
        if ($intRows > 0) {
            $strResult .= "<div class=\"col-md-12\" id=\"detailRows$intRows\" style=\"border-top: 1px solid #DDD;padding-top: 15px;\">";
        } else {
            $strResult .= "<div class=\"col-md-12\" id=\"detailRows$intRows\">";
        }
        $strResult .= "<div class=\"col-md-4\">\n";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['institution'] . "</label>";
        $strResult .= "  	<div class=\"col-sm-9\"><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">";
        $strResult .= "  	<input class=\"form-control\" type=text maxlength=63 name=detailInstitution$intRows value=\"" . $rowDb['institution'] . "\" ></div>";
        $strResult .= "	</div>\n";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['location'] . "</label>";
        $strResult .= "  	<div class=\"col-sm-9\"><input class=\"form-control\" type=text maxlength=31 name=detailLocation$intRows value=\"" . $rowDb['location'] . "\"></div>";
        $strResult .= "	</div>\n";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['position'] . "</label>";
        $strResult .= "  	<div class=\"col-sm-9\"><input class=\"form-control\" maxlength=31 name=detailPosition$intRows value=\"" . $rowDb['position'] . "\"></div>";
        $strResult .= "	</div>\n";
        $strResult .= "</div>\n";
        $strResult .= "<div class=\"col-md-4\">\n";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['date from'] . "</label>";
        $strTmp = getDayList("detailDayFrom$intRows", $rowDb['day_from'], $strEmptyOption);
        $strTmp .= getMonthList("detailMonthFrom$intRows", $rowDb['month_from'], $strEmptyOption);
        $strTmp .= getYearList("detailYearFrom$intRows", $rowDb['year_from'], $strEmptyOption);
        $strResult .= "  	<div class=\"col-sm-9\">" . $strTmp . "</div>";
        $strResult .= "	</div>\n";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['date thru'] . "</label>";
        $strTmp = getDayList("detailDayThru$intRows", $rowDb['day_thru'], $strEmptyOption);
        $strTmp .= getMonthList("detailMonthThru$intRows", $rowDb['month_thru'], $strEmptyOption);
        $strTmp .= getYearList("detailYearThru$intRows", $rowDb['year_thru'], $strEmptyOption);
        $strResult .= "  	<div class=\"col-sm-9\">" . $strTmp . "</div>";
        $strResult .= "	</div>\n";
        $strResult .= "</div>\n";
        $strResult .= "<div class=\"col-md-4\">\n";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['note'] . "</label>";
        $strResult .= "  	<div class=\"col-sm-9\"><textarea class=\"form-control\" cols=$intDefaultWidth rows=2 name=detailNote$intRows >" . $rowDb['note'] . "</textarea></td>";
        $strResult .= "</div>\n";
        $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['delete'] . "</label>";
        $strResult .= "  	<div class=\"col-sm-9\"><div class=\"checkbox\"><label><input class=\"checkbox-inline\" type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\" $strAction></label></div></div>\n";
        $strResult .= " </div>\n";
        $strResult .= "</div>\n";
        $strResult .= "</div>\n";
        $strResult .= "</div>\n";
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    // tambahkan dengan data kosong
    for ($i = 1; $i <= $intAdd; $i++) {
        $intRows++;
        if ($intRows == 1) {
            $strResult .= "<div class=\"col-md-12\" id=\"detailRows$intRows\">\n";
            $intShown++;
            $strDisabled = "";
        } else {
            $strResult .= "<div class=\"col-md-12\" id=\"detailRows$intRows\" style=\"display:none;border-top: 1px solid #DDD;padding-top: 15px;\">\n";
            $strDisabled = "disabled";
        }
        $strResult .= "<div class=\"col-md-4\">\n";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['institution'] . "</label>";
        $strResult .= "  	<div class=\"col-sm-9\"><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">";
        $strResult .= "  	<input class=\"form-control\" type=text maxlength=63 name=detailInstitution$intRows value=\"" . $rowDb['institution'] . "\" ></div>";
        $strResult .= "	</div>\n";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['location'] . "</label>";
        $strResult .= "  	<div class=\"col-sm-9\"><input class=\"form-control\" type=text maxlength=31 name=detailLocation$intRows value=\"" . $rowDb['location'] . "\"></div>";
        $strResult .= "	</div>\n";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['position'] . "</label>";
        $strResult .= "  	<div class=\"col-sm-9\"><input class=\"form-control\" maxlength=31 name=detailPosition$intRows value=\"" . $rowDb['position'] . "\"></div>";
        $strResult .= "	</div>\n";
        $strResult .= "</div>\n";
        $strResult .= "<div class=\"col-md-4\">\n";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['date from'] . "</label>";
        $strTmp = getDayList("detailDayFrom$intRows", $rowDb['day_from'], $strEmptyOption);
        $strTmp .= getMonthList("detailMonthFrom$intRows", $rowDb['month_from'], $strEmptyOption);
        $strTmp .= getYearList("detailYearFrom$intRows", $rowDb['year_from'], $strEmptyOption);
        $strResult .= "  	<div class=\"col-sm-9\">" . $strTmp . "</div>";
        $strResult .= "	</div>\n";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['date thru'] . "</label>";
        $strTmp = getDayList("detailDayThru$intRows", $rowDb['day_thru'], $strEmptyOption);
        $strTmp .= getMonthList("detailMonthThru$intRows", $rowDb['month_thru'], $strEmptyOption);
        $strTmp .= getYearList("detailYearThru$intRows", $rowDb['year_thru'], $strEmptyOption);
        $strResult .= "  	<div class=\"col-sm-9\">" . $strTmp . "</div>";
        $strResult .= "	</div>\n";
        $strResult .= "</div>\n";
        $strResult .= "<div class=\"col-md-4\">\n";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['note'] . "</label>";
        $strResult .= "  	<div class=\"col-sm-9\"><textarea class=\"form-control\" cols=$intDefaultWidth rows=2 name=detailNote$intRows >" . $rowDb['note'] . "</textarea></td>";
        $strResult .= " </div>\n";
        $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
        $strResult .= "	<div class=\"form-group\">";
        $strResult .= "		<label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['delete'] . "</label>";
        $strResult .= "  	<div class=\"col-sm-9\"><div class=\"checkbox\"><label><input class=\"checkbox-inline\" type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\" $strAction></label></div></div>\n";
        $strResult .= " </div>\n";
        $strResult .= " </div>\n";
        $strResult .= "</div>\n";
        $strResult .= "</div>\n";
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
    (isset($_REQUEST['maxDetail'])) ? $intMax = $_REQUEST['maxDetail'] : $intMax = 0;
    for ($i = 1; $i <= $intMax; $i++) {
        (isset($_REQUEST['detailID' . $i])) ? $strID = $_REQUEST['detailID' . $i] : $strID = "";
        (isset($_REQUEST['detailLocation' . $i])) ? $strLocation = $_REQUEST['detailLocation' . $i] : $strLocation = "";
        (isset($_REQUEST['detailPosition' . $i])) ? $strPosition = $_REQUEST['detailPosition' . $i] : $strPosition = "";
        (isset($_REQUEST['detailInstitution' . $i])) ? $strInstitution = $_REQUEST['detailInstitution' . $i] : $strInstitution = "";
        (isset($_REQUEST['detailDayFrom' . $i])) ? $strDayFrom = $_REQUEST['detailDayFrom' . $i] : $strDayFrom = "";
        (isset($_REQUEST['detailMonthFrom' . $i])) ? $strMonthFrom = $_REQUEST['detailMonthFrom' . $i] : $strMonthFrom = "";
        (isset($_REQUEST['detailYearFrom' . $i])) ? $strYearFrom = $_REQUEST['detailYearFrom' . $i] : $strYearFrom = "";
        (isset($_REQUEST['detailDayThru' . $i])) ? $strDayThru = $_REQUEST['detailDayThru' . $i] : $strDayThru = "";
        (isset($_REQUEST['detailMonthThru' . $i])) ? $strMonthThru = $_REQUEST['detailMonthThru' . $i] : $strMonthThru = "";
        (isset($_REQUEST['detailYearThru' . $i])) ? $strYearThru = $_REQUEST['detailYearThru' . $i] : $strYearThru = "";
        (isset($_REQUEST['detailNote' . $i])) ? $strNote = $_REQUEST['detailNote' . $i] : $strNote = "";
        if ($strID == "") {
            if ($strInstitution != "") { // insert new data
                $strSQL = "INSERT INTO hrd_employee_work (created,modified, created_by, modified_by,";
                $strSQL .= "id_employee, institution, location, position, note, ";
                $strSQL .= "day_from, month_from, year_from, ";
                $strSQL .= "day_thru, month_thru, year_thru) ";
                $strSQL .= "VALUES(now(),now(), '" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
                $strSQL .= "'$strDataID', '$strInstitution','$strLocation', '$strPosition', '$strNote', ";
                $strSQL .= "'$strDayFrom', '$strMonthFrom', '$strYearFrom', ";
                $strSQL .= "'$strDayThru', '$strMonthThru', '$strYearThru') ";
                $resDb = $db->execute($strSQL);
                writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataID", 0);
            }
        } else {
            if ($strInstitution == "") {
                // delete data
                $strSQL = "DELETE FROM hrd_employee_work WHERE id = '$strID' ";
                $resDb = $db->execute($strSQL);
                writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$strDataID", 0);
            } else {
                // update data
                $strSQL = "UPDATE hrd_employee_work SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
                $strSQL .= "modified = now(), location = '$strLocation', position = '$strPosition', ";
                $strSQL .= "institution = '$strInstitution', note = '$strNote', ";
                $strSQL .= "day_from = '$strDayFrom', month_from = '$strMonthFrom', year_from = '$strYearFrom', ";
                $strSQL .= "day_thru = '$strDayThru', month_thru = '$strMonthThru', year_thru = '$strYearThru' ";
                $strSQL .= "WHERE id = '$strID' ";
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
        $closeIcon = '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
        if (isset($_POST['btnSave'])) {
            if ($bolEmployee || !saveData($db, $strDataID, $strError)) {
                //echo "<script>alert(\"$strError\")</script>";
                $bolError = true;
                if ($bolEmployee) {
                    $strError = getWords("sorry, you can not edit this page");
                }
            }
            if ($strError != "") {
                $strMessages = ($bolError) ? '<div class="alert alert-danger">' . $closeIcon . $strError . '</div>' : '<div class="alert alert-success">' . $closeIcon . $strError . '</div>';
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
                $bolCanView = false;
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
$strInitAction = "
  ";
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords("employee data");
$strPageDesc = getWords("employee work experiencies information");
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strWordsWorkExperiences = getWords("work experiences");
$pageSubMenu = employeeEditSubmenu($strWordsWorkExperiences);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>