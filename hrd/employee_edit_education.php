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
if (!$bolCanView && $_REQUEST['dataID'] == "") {
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
    $strWordsDocument = getWords("document");
    $strWordsDeleteFile = getWords("delete file");
    $intRows = 0;
    $intShown = 0;
    $intAdd = 6; // maksimum tambahan
    $strResult = "";
    $strNow = date("Y-m-d");
    $strSQL = "SELECT * FROM hrd_employee_education ";
    $strSQL .= "WHERE education_level_code!='DOC' $strKriteria ORDER BY $strOrder year_from, id";
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
        $strResult .= "	<div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['education level'] . "</label>";
        $strResult .= " <div class=\"col-sm-9\"><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">";
        $strResult .= getEducationList(
                $db,
                "detailCode$intRows",
                $rowDb['education_level_code'],
                $strEmptyOption
            ) . "</div>";
        $strResult .= "	</div>\n";
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['institution'] . "</label>";
        $strResult .= " <div class=\"col-sm-9\"><input type=text class=\"form-control\" maxlength=63 name=detailInstitution$intRows value=\"" . $rowDb['institution'] . "\"></div>";
        $strResult .= "	</div>\n";
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">IPK</label>";
        $strResult .= " <div class=\"col-sm-9\"><input class=\"form-control\" type=text maxlength=63 name=detailIpk$intRows value=\"" . $rowDb['ipk'] . "\"></div>";
        $strResult .= " </div>\n";
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['location'] . "</label>";
        $strResult .= " <div class=\"col-sm-9\"><input class=\"form-control\" type=text maxlength=31 name=detailLocation$intRows value=\"" . $rowDb['location'] . "\"></div>";
        $strResult .= " </div>\n";
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['faculty'] . "</label>";
        $strResult .= " <div class=\"col-sm-9\"><input class=\"form-control\"  type=text maxlength=63 name=detailFaculty$intRows value=\"" . $rowDb['faculty'] . "\"></div>";
        $strResult .= " </div>\n";
        $strResult .= "</div>\n";
        $strResult .= "<div class=\"col-md-4\">\n";
        /*$strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">".$words['registration no.']."</label>";
     $strResult .= " <div class=\"col-sm-9\"><input class=\"form-control\" type=text maxlength=31 name=detailRegistrationNo$intRows value=\"" .$rowDb['registration_no']. "\"></div>";
     $strResult .= "	</div>\n";
     $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">".$words['certificate no.']."</label>";
     $strResult .= " <div class=\"col-sm-9\"><input class=\"form-control\" type=text maxlength=31 name=detailCertificateNo$intRows value=\"" .$rowDb['certificate_no']. "\"></div>";
     $strResult .= " </div>\n";*/
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['date from'] . "</label>";
        $strTmp = getDayList("detailDayFrom$intRows", $rowDb['day_from'], $strEmptyOption);
        $strTmp .= getMonthList("detailMonthFrom$intRows", $rowDb['month_from'], $strEmptyOption);
        $strTmp .= getYearList("detailYearFrom$intRows", $rowDb['year_from'], $strEmptyOption);
        $strResult .= " <div class=\"col-sm-9\">" . $strTmp . "</div>";
        $strResult .= " </div>\n";
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . getWords(
                "date thru"
            ) . "</label>";
        $strTmp = getDayList("detailDayThru$intRows", $rowDb['day_thru'], $strEmptyOption);
        $strTmp .= getMonthList("detailMonthThru$intRows", $rowDb['month_thru'], $strEmptyOption);
        $strTmp .= getYearList("detailYearThru$intRows", $rowDb['year_thru'], $strEmptyOption);
        $strResult .= " <div class=\"col-sm-9\">" . $strTmp . "</div>";
        $strResult .= " </div>\n";
        //tampilkan foto
        if ($rowDb['doc'] == "") {
            $strDataDoc = "";
        } else {
            if (file_exists("edudoc/" . $rowDb['doc'])) {
                $strDataDoc = "<a href=\"edudoc/" . $rowDb['doc'] . "\" target=\"_blank\" >" . $rowDb['doc'] . "</a>";
            } else {
                $strDataDoc = "";
            }
        }
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $strWordsDocument . "</label>";
        $strResult .= " <div class=\"col-sm-9\"><div class=\"fileinput fileinput-new\" data-provides=\"fileinput\">";
        $strResult .= "  <span class=\"btn btn-primary btn-file\"><span class=\"fileinput-new\">Select file</span><span class=\"fileinput-exists\">Change</span>";
        $strResult .= "  <input name=\"detailDoc$intRows\" type=\"file\" id=\"detailDoc$intRows\">";
        $strResult .= "  <span class=\"fileinput-filename\"></span>";
        $strResult .= "  <i class=\"fa fa-times fileinput-exists\" data-dismiss=\"fileinput\"></i></div>";
        $strResult .= "  <span id=\"doc\">&nbsp;" . $strDataDoc . "</span>";
        if (!empty($strDataDoc)) {
            $strResult .= "  <input class=\"btn btn-small btn-danger\" name=\"btnDeleteDoc$intRows\" type=\"button\" id=\"btnDelete$intRows\" value=\"$strWordsDeleteFile\" onClick=\"deleteFile($intRows);\">";
        }
        $strResult .= " </div></div>";
        $strResult .= "</div>\n";
        $strResult .= "<div class=\"col-md-4\">\n";
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['note'] . "</label>";
        $strResult .= " <div class=\"col-sm-9\"><textarea class=\"form-control\" cols=$intDefaultWidth rows=2 name=detailNote$intRows style=\"width:$intDefaultWidthPx\">" . $rowDb['note'] . "</textarea></div>";
        $strResult .= " </div>\n";
        $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
        $strResult .= "  <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['delete'] . "</label>";
        $strResult .= "  <div class=\"col-sm-9\"><div class=\"checkbox\"><label><input class=\"checkbox-inline\" type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\" $strAction></label></div></div>\n";
        $strResult .= "  </div>\n";
        $strResult .= " </div>";
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
        $strResult .= "	<div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['education level'] . "</label>";
        $strResult .= " <div class=\"col-sm-9\"><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">";
        $strResult .= getEducationList(
                $db,
                "detailCode$intRows",
                $rowDb['education_level_code'],
                $strEmptyOption
            ) . "</div>";
        $strResult .= "	</div>\n";
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['institution'] . "</label>";
        $strResult .= " <div class=\"col-sm-9\"><input type=text class=\"form-control\" maxlength=63 name=detailInstitution$intRows value=\"" . $rowDb['institution'] . "\"></div>";
        $strResult .= "	</div>\n";
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">IPK</label>";
        $strResult .= " <div class=\"col-sm-9\"><input class=\"form-control\" type=text maxlength=63 name=detailIpk$intRows value=\"" . $rowDb['ipk'] . "\"></div>";
        $strResult .= " </div>\n";
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['location'] . "</label>";
        $strResult .= " <div class=\"col-sm-9\"><input class=\"form-control\" type=text maxlength=31 name=detailLocation$intRows value=\"" . $rowDb['location'] . "\"></div>";
        $strResult .= " </div>\n";
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['faculty'] . "</label>";
        $strResult .= " <div class=\"col-sm-9\"><input class=\"form-control\"  type=text maxlength=63 name=detailFaculty$intRows value=\"" . $rowDb['faculty'] . "\"></div>";
        $strResult .= " </div>\n";
        $strResult .= "</div>\n";
        $strResult .= "<div class=\"col-md-4\">\n";
        /*$strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">".$words['registration no.']."</label>";
        $strResult .= " <div class=\"col-sm-9\"><input class=\"form-control\" type=text maxlength=31 name=detailRegistrationNo$intRows value=\"" .$rowDb['registration_no']. "\"></div>";
        $strResult .= "	</div>\n";
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">".$words['certificate no.']."</label>";
        $strResult .= " <div class=\"col-sm-9\"><input class=\"form-control\" type=text maxlength=31 name=detailCertificateNo$intRows value=\"" .$rowDb['certificate_no']. "\"></div>";
        $strResult .= " </div>\n";*/
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['date from'] . "</label>";
        $strTmp = getDayList("detailDayFrom$intRows", $rowDb['day_from'], $strEmptyOption);
        $strTmp .= getMonthList("detailMonthFrom$intRows", $rowDb['month_from'], $strEmptyOption);
        $strTmp .= getYearList("detailYearFrom$intRows", $rowDb['year_from'], $strEmptyOption);
        $strResult .= " <div class=\"col-sm-9\">" . $strTmp . "</div>";
        $strResult .= " </div>\n";
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . getWords(
                "date thru"
            ) . "</label>";
        $strTmp = getDayList("detailDayThru$intRows", $rowDb['day_thru'], $strEmptyOption);
        $strTmp .= getMonthList("detailMonthThru$intRows", $rowDb['month_thru'], $strEmptyOption);
        $strTmp .= getYearList("detailYearThru$intRows", $rowDb['year_thru'], $strEmptyOption);
        $strResult .= " <div class=\"col-sm-9\">" . $strTmp . "</div>";
        $strResult .= " </div>\n";
        //tampilkan foto
        if ($rowDb['doc'] == "") {
            $strDataDoc = "";
        } else {
            if (file_exists("edudoc/" . $rowDb['doc'])) {
                $strDataDoc = "<a href=\"edudoc/" . $rowDb['doc'] . "\" target=\"_blank\" > <img  src='edudoc/" . $rowDb['doc'] . "' alt=\"" . $rowDb['doc'] . "\"></a>&nbsp;&nbsp;";
            } else {
                $strDataDoc = "";
            }
        }
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $strWordsDocument . "</label>";
        $strResult .= " <div class=\"col-sm-9\"><div class=\"fileinput fileinput-new\" data-provides=\"fileinput\">";
        $strResult .= "  <span class=\"btn btn-primary btn-file\"><span class=\"fileinput-new\">Select file</span><span class=\"fileinput-exists\">Change</span>";
        $strResult .= "  <input name=\"detailDoc$intRows\" type=\"file\" id=\"detailDoc$intRows\">";
        $strResult .= "  <span class=\"fileinput-filename\"></span>";
        $strResult .= "  <i class=\"fa fa-times fileinput-exists\" data-dismiss=\"fileinput\"></i></div>";
        $strResult .= "  <span id=\"doc\">&nbsp;" . $strDataDoc . "</span>";
        if (!empty($strDataDoc)) {
            $strResult .= "  <input class=\"btn btn-small btn-danger\" name=\"btnDeleteDoc$intRows\" type=\"button\" id=\"btnDelete$intRows\" value=\"$strWordsDeleteFile\" onClick=\"deleteFile($intRows);\">";
        }
        $strResult .= "  </div>";
        $strResult .= " </div>";
        $strResult .= "</div>\n";
        $strResult .= "<div class=\"col-md-4\">\n";
        $strResult .= " <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['note'] . "</label>";
        $strResult .= " <div class=\"col-sm-9\"><textarea class=\"form-control\" cols=$intDefaultWidth rows=2 name=detailNote$intRows style=\"width:$intDefaultWidthPx\">" . $rowDb['note'] . "</textarea></div>";
        $strResult .= " </div>\n";
        $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
        $strResult .= "  <div class=\"form-group\"><label class=\"col-sm-3 control-label\" for=\"dataType\">" . $words['delete'] . "</label>";
        $strResult .= "  <div class=\"col-sm-9\"><div class=\"checkbox\"><label><input class=\"checkbox-inline\" type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\" $strAction></label></div></div>\n";
        $strResult .= "  </div>\n";
        $strResult .= " </div>";
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
    global $_FILES;
    global $messages;
    $strError = "";
    (isset($_REQUEST['maxDetail'])) ? $intMax = $_REQUEST['maxDetail'] : $intMax = 0;
    for ($i = 1; $i <= $intMax; $i++) {
        (isset($_REQUEST['detailID' . $i])) ? $strID = $_REQUEST['detailID' . $i] : $strID = "";
        (isset($_REQUEST['detailCode' . $i])) ? $strCode = $_REQUEST['detailCode' . $i] : $strCode = "";
        (isset($_REQUEST['detailInstitution' . $i])) ? $strInstitution = $_REQUEST['detailInstitution' . $i] : $strInstitution = "";
        (isset($_REQUEST['detailIpk' . $i])) ? $strIpk = $_REQUEST['detailIpk' . $i] : $strIpk = "";
        (isset($_REQUEST['detailLocation' . $i])) ? $strLocation = $_REQUEST['detailLocation' . $i] : $strLocation = "";
        (isset($_REQUEST['detailFaculty' . $i])) ? $strFaculty = $_REQUEST['detailFaculty' . $i] : $strFaculty = "";
        (isset($_REQUEST['detailRegistrationNo' . $i])) ? $strRegistrationNo = $_REQUEST['detailRegistrationNo' . $i] : $strReqistrationNo = "";
        (isset($_REQUEST['detailCertificateNo' . $i])) ? $strCertificateNo = $_REQUEST['detailCertificateNo' . $i] : $strCertificateNo = "";
        (isset($_REQUEST['detailDayFrom' . $i])) ? $strDayFrom = $_REQUEST['detailDayFrom' . $i] : $strDayFrom = "";
        (isset($_REQUEST['detailMonthFrom' . $i])) ? $strMonthFrom = $_REQUEST['detailMonthFrom' . $i] : $strMonthFrom = "";
        (isset($_REQUEST['detailYearFrom' . $i])) ? $strYearFrom = $_REQUEST['detailYearFrom' . $i] : $strYearFrom = "";
        (isset($_REQUEST['detailDayThru' . $i])) ? $strDayThru = $_REQUEST['detailDayThru' . $i] : $strDayThru = "";
        (isset($_REQUEST['detailMonthThru' . $i])) ? $strMonthThru = $_REQUEST['detailMonthThru' . $i] : $strMonthThru = "";
        (isset($_REQUEST['detailYearThru' . $i])) ? $strYearThru = $_REQUEST['detailYearThru' . $i] : $strYearThru = "";
        (isset($_REQUEST['detailNote' . $i])) ? $strNote = $_REQUEST['detailNote' . $i] : $strNote = "";
        if ($strID == "") {
            if ($strCode != "") { // insert new data
                $strSQL = "INSERT INTO hrd_employee_education (created,modified, created_by, modified_by,";
                $strSQL .= "id_employee, education_level_code, institution, ipk, note, ";
                $strSQL .= "location, faculty, registration_no, certificate_no,  ";
                $strSQL .= "day_from, month_from, year_from, ";
                $strSQL .= "day_thru, month_thru, year_thru) ";
                $strSQL .= "VALUES(now(), now(), '" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
                $strSQL .= "'$strDataID', '$strCode', '$strInstitution', '$strIpk', '$strNote', ";
                $strSQL .= "'$strLocation', '$strFaculty', '$strRegistrationNo', '$strCertificateNo', ";
                $strSQL .= "'$strDayFrom', '$strMonthFrom', '$strYearFrom', ";
                $strSQL .= "'$strDayThru', '$strMonthThru', '$strYearThru') ";
                $resDb = $db->execute($strSQL);
                writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataID", 0);
            }
            // ambil data IDnya
            $strSQL = "SELECT id FROM hrd_employee_education WHERE id_employee = '$strDataID' AND education_level_code = '$strCode' ";
            $resDb = $db->execute($strSQL);
            if ($rowDb = $db->fetchrow($resDb)) {
                $strID = $rowDb['id'];
            }
        } else {
            if ($strCode == "") {
                // delete data
                $strSQL = "DELETE FROM hrd_employee_education WHERE id = '$strID' ";
                $resDb = $db->execute($strSQL);
                writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$strDataID", 0);
            } else {
                // update data
                $strSQL = "UPDATE hrd_employee_education SET modified_by = '" . $_SESSION['sessionUserID'] . "', ";
                $strSQL .= "modified = now(), education_level_code = '$strCode', ";
                $strSQL .= "institution = '$strInstitution', ipk = '$strIpk', note = '$strNote', ";
                $strSQL .= "location = '$strLocation', faculty = '$strFaculty', ";
                $strSQL .= "registration_no = '$strRegistrationNo', certificate_no = '$strCertificateNo', ";
                $strSQL .= "day_from = '$strDayFrom', month_from = '$strMonthFrom', year_from = '$strYearFrom', ";
                $strSQL .= "day_thru = '$strDayThru', month_thru = '$strMonthThru', year_thru = '$strYearThru' ";
                $strSQL .= "WHERE id = '$strID' ";
                $resDb = $db->execute($strSQL);
                writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataID", 0);
            }
        }
        // simpan data gambar, jika ada
        if ($strID != "") {
            if (is_uploaded_file($_FILES["detailDoc$i"]['tmp_name'])) {
                $arrNamaFile = explode(".", $_FILES["detailDoc$i"]['name']);
                $strNamaFile = $strID . "_" . strtolower(
                        substr($_FILES["detailDoc$i"]['name'], 0, -(strlen($arrNamaFile[count($arrNamaFile) - 1]) + 1))
                    );
                if (strlen($strNamaFile) > 40) {
                    $strNamaFile = substr($strNamaFile, 0, 40);
                }
                if (count($arrNamaFile) > 0) {
                    $strNamaFile .= "." . $arrNamaFile[count($arrNamaFile) - 1];
                }
                clearstatcache();
                if (!is_dir("edudoc")) {
                    mkdir("edudoc", 0755);
                }
                $strNamaFileLengkap = "edudoc/" . $strNamaFile;
                if (file_exists($strNamaFileLengkap)) {
                    unlink($strNamaFileLengkap);
                }
                if (move_uploaded_file($_FILES["detailDoc$i"]['tmp_name'], $strNamaFileLengkap)) {
                    // update data
                    $strSQL = "UPDATE hrd_employee_education SET doc = '$strNamaFile' WHERE id = '$strID' ";
                    $resExec = $db->execute($strSQL);
                }
            }
        }
    }
    $strError = $messages['data_saved'] . " >> " . date("d-M-Y H:i:s");
    return true;
} // saveData
// fungsi untuk menghapus gambar employee
function deleteFile($db, $strDetailID = "")
{
    global $words;
    $bolNewData = true;
    if ($strDetailID != "") {
        $strSQL = "SELECT * FROM hrd_employee_education ";
        $strSQL .= "WHERE id = '$strDetailID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strFile = $rowDb['doc'];
            if ($strFile != "") {
                if (file_exists("edudoc/" . $strFile)) {
                    unlink("edudoc/" . $strFile);
                }
                $strSQL = "UPDATE hrd_employee_education SET doc = '' WHERE id = '$strDetailID' ";
                $resExec = $db->execute($strSQL);
                writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "file $strDetailID", 0);
            }
        }
    }
    return true;
} // deletePicture
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
$strDataPhoto = "";
if ($db->connect()) {
    getUserEmployeeInfo();
    $bolEmployee = ($_SESSION['sessionUserRole'] < ROLE_ADMIN);
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
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
        } else if (isset($_REQUEST['fileID'])) {
            if ($bolCanEdit && $_SESSION['sessionUserRole'] != ROLE_EMPLOYEE) {
                deleteFile($db, $_REQUEST['fileID']);
            } else {
                $strMessages = '<div class="alert alert-danger">' . $closeIcon . getWords('delete_denied') . '</div>';
                $strMsgClass = "class=bgError";
            }
        }
    }
    if ($strDataID == "") {
        redirectPage("employee_search.php");
        exit();
    } else {
        ($strDataID == "") ? $strKriteria = " AND 1=2 " : $strKriteria = " AND id_employee = '$strDataID' ";
        // cari info karyawan
        $strSQL = "SELECT employee_id, employee_name, flag,link_id FROM hrd_employee WHERE id = '$strDataID' ";
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
$strPageDesc = getWords("employee education information");;
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strWordsEducationData = getWords("education data");
$pageSubMenu = employeeEditSubmenu($strWordsEducationData);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>