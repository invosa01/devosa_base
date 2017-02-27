<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/employee_function.php');
$dataPrivilege = getDataPrivileges(
    "mutation_edit.php",
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
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintStatus']) || isset($_REQUEST['btnPrintDepartment']) || isset($_REQUEST['btnPrintPosition']));
//---- INISIALISASI ----------------------------------------------------
$strWordsDateFrom = getWords("date from");
$strWordsDateThru = getWords("date thru");
$strWordsEmployeeID = getWords("employee id");
$strWordsCompany = getWords("company");
$strWordsDepartment = getWords("department");
$strWordsSection = getWords("section");
$strWordsListOf = getWords("list of");
$strWordsProposalDate = getWords("proposal date");
$strWordsLetterCode = getWords("letter ode");
$strWordsName = getWords("name");
$strWordsGender = getWords("sex");
$strWordsStatusConfirmation = getWords("status confirmation");
$strWordsPositionChanges = getWords("position changes");
$strWordsDepartmentChanges = getWords("department changes");
$strWordsNote = getWords("note");
$strWordsStatus = getWords("status");
$strWordsOldPosition = getWords("old position");
$strWordsOldGrade = getWords("old grade");
$strWordsStartDate = getWords("start date");
$strWordsNewPosition = getWords("new position");
$strWordsNewGrade = getWords("new grade");
$strWordsOldDepartment = getWords("old department");
$strWordsNewDepartment = getWords("new department");
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strButtons = "";
if ($bolPrint) {
    $strDisplayAll = $strDisplayStatus = $strDisplayPosition = $strDisplayDepartment = "style=\"display:none\" ";
}
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $ARRAY_REQUEST_STATUS;
    global $bolPrint;
    $intRows = 0;
    $strResult = "";
    $strNow = date("Y-m-d");
    $i = 0;
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.gender ";
    $strSQL .= "FROM hrd_employee_mutation AS t1 LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.type=0 AND status = " . REQUEST_STATUS_CHECKED . " $strKriteria "; // yang tampil yang checked
    //$strSQL .= "AND ((status = 1) OR (status = 2 AND t1.proposal_date >= '$strNow') ) ";
    $strSQL .= "ORDER BY $strOrder t1.proposal_date, t2.employee_id ";
    $resDb = $db->execute($strSQL);
    $strDateOld = "";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        ($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
        /*
        if ($rowDb['status'] == '1') {
          $strClass = "bgNewRevised";
        } else if ($rowDb['status'] == '3') {
          $strClass = "bgDenied";
        } else {
          $strClass = "";
        }
        */
        $strClass = getCssClass($rowDb['status']);
        $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
        $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
        if (!$bolPrint) {
            $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
        }
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['proposal_date'], "d-M-Y") . "&nbsp;</td>\n";
        $strResult .= "  <td>" . $rowDb['employee_id'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $strGender . "&nbsp;</td>";
        // status confirmation
        $strSQL = "SELECT * FROM hrd_employee_mutation_status WHERE id_mutation = '" . $rowDb['id'] . "' ";
        $resTmp = $db->execute($strSQL);
        if ($rowTmp = $db->fetchrow($resTmp)) {
            $strResult .= "  <td>" . $words[$ARRAY_EMPLOYEE_STATUS[$rowTmp['status_new']]] . "&nbsp;</td>";
            $strResult .= "  <td align=center>" . pgDateFormat($rowTmp['status_date_from'], "d-M-y") . "&nbsp;</td>";
            $strResult .= "  <td align=center>" . pgDateFormat($rowTmp['status_date_thru'], "d-M-y") . "&nbsp;</td>";
        } else {
            $strResult .= "  <td>&nbsp;</td>";
            $strResult .= "  <td>&nbsp;</td>";
            $strResult .= "  <td>&nbsp;</td>";
        }
        /*
              // resignment
              $strSQL  = "SELECT * FROM hrd_employee_mutation_resign WHERE id_mutation = '" .$rowDb['id']."' ";
              $resTmp = $db->execute($strSQL);
              if ($rowTmp = $db->fetchrow($resTmp)) {
                $strResult .= "  <td align=center>" .pgDateFormat($rowTmp['resign_date'], "d-M-y"). "&nbsp;</td>";
              } else {
                $strResult .= "  <td>&nbsp;</td>";
              }
        */
        /*
              // salary increase
              $strSQL  = "SELECT * FROM hrd_employee_mutation_salary WHERE id_mutation = '" .$rowDb['id']."' ";
              $resTmp = $db->execute($strSQL);
              if ($rowTmp = $db->fetchrow($resTmp)) {
                $strResult .= "  <td align=right>" .standardFormat($rowTmp['salaryOld'], true). "&nbsp;</td>";
                $strResult .= "  <td align=center>" .pgDateFormat($rowTmp['salaryOldDate'], "d-M-y"). "&nbsp;</td>";
                $strResult .= "  <td align=right>" .standardFormat($rowTmp['salaryProposed'], true). "&nbsp;</td>";
                $strResult .= "  <td align=center>" .pgDateFormat($rowTmp['salaryProposedDate'], "d-M-y"). "&nbsp;</td>";
                $strResult .= "  <td align=right>" .standardFormat($rowTmp['salaryNew'], true). "&nbsp;</td>";
                $strResult .= "  <td align=center>" .pgDateFormat($rowTmp['salaryNewDate'], "d-M-y"). "&nbsp;</td>";
                $strResult .= "  <td>" .$rowTmp['salaryNote']. "&nbsp;</td>";
              } else {
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
                $strResult .= "  <td>&nbsp;</td>";
              }
        */
        // position changes
        $strSQL = "SELECT * FROM hrd_employee_mutation_position WHERE id_mutation = '" . $rowDb['id'] . "' ";
        $resTmp = $db->execute($strSQL);
        if ($rowTmp = $db->fetchrow($resTmp)) {
            $strResult .= "  <td align=center>" . $rowTmp['position_old'] . "&nbsp;</td>";
            $strResult .= "  <td align=center>" . $rowTmp['grade_old'] . "&nbsp;</td>";
            $strResult .= "  <td align=center>" . pgDateFormat($rowTmp['position_old_date'], "d-M-y") . "&nbsp;</td>";
            $strResult .= "  <td align=center>" . $rowTmp['position_new'] . "&nbsp;</td>";
            $strResult .= "  <td align=center>" . $rowTmp['grade_new'] . "&nbsp;</td>";
            $strResult .= "  <td align=center>" . pgDateFormat($rowTmp['position_new_date'], "d-M-y") . "&nbsp;</td>";
        } else {
            $strResult .= "  <td>&nbsp;</td>";
            $strResult .= "  <td>&nbsp;</td>";
            $strResult .= "  <td>&nbsp;</td>";
            $strResult .= "  <td>&nbsp;</td>";
            $strResult .= "  <td>&nbsp;</td>";
            $strResult .= "  <td>&nbsp;</td>";
        }
        // department mutation
        $strSQL = "SELECT * FROM hrd_employee_mutation_department WHERE id_mutation = '" . $rowDb['id'] . "' ";
        $resTmp = $db->execute($strSQL);
        if ($rowTmp = $db->fetchrow($resTmp)) {
            $strResult .= "  <td>" . $rowTmp['department_old'] . "&nbsp;</td>";
            $strResult .= "  <td>" . $rowTmp['department_new'] . "&nbsp;</td>";
            $strResult .= "  <td align=center>" . pgDateFormat($rowTmp['department_date'], "d-M-y") . "&nbsp;</td>";
        } else {
            $strResult .= "  <td>&nbsp;</td>";
            $strResult .= "  <td>&nbsp;</td>";
            $strResult .= "  <td>&nbsp;</td>";
        }
        $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>\n";
        $strResult .= "  <td align=center>" . $words[$ARRAY_REQUEST_STATUS[$rowDb['status']]] . "&nbsp;</td>\n";
        //$strResult .= "  <td align=center><a href=\"mutation_edit.php?dataID=" .$rowDb['id']. "\">" .$words['edit']. "</a>&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // showData
// fungsi untuk menampilkan data, tapi hanya perubahan status aja
function getDataStatus($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $ARRAY_REQUEST_STATUS;
    global $bolPrint;
    $intRows = 0;
    $strResult = "";
    // ambil dulu data employee, kumpulkan dalam array
    $i = 0;
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.gender, ";
    $strSQL .= "t3.status_new, t3.status_date_from, t3.status_date_thru ";
    $strSQL .= "FROM hrd_employee_mutation_status AS t3 ";
    $strSQL .= "LEFT JOIN hrd_employee_mutation AS t1 ON t3.id_mutation = t1.id ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.type=0 AND status = " . REQUEST_STATUS_CHECKED . "  $strKriteria ";
    //$strSQL .= "AND proposal_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
    $strSQL .= "ORDER BY $strOrder t1.proposal_date, t2.employee_id ";
    $resDb = $db->execute($strSQL);
    $strDateOld = "";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        ($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
        $strClass = "";//getCssClass($rowDb['status']);
        $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
        $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['proposal_date'], "d-M-Y") . "&nbsp;</td>\n";
        $strResult .= "  <td>" . $rowDb['employee_id'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $strGender . "&nbsp;</td>";
        $strResult .= "  <td>" . getWords($ARRAY_EMPLOYEE_STATUS[$rowDb['status_new']]) . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['status_date_from'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['status_date_thru'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // getDataStatus
// fungsi untuk menampilkan data, tapi hanya perubahan jabatan saja
function getDataPosition($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $ARRAY_REQUEST_STATUS;
    global $bolPrint;
    $intRows = 0;
    $strResult = "";
    // ambil dulu data employee, kumpulkan dalam array
    $i = 0;
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.gender, ";
    $strSQL .= "t3.position_old, t3.position_old_date, t3.grade_old, ";
    $strSQL .= "t3.position_new, t3.position_new_date, t3.grade_new ";
    $strSQL .= "FROM hrd_employee_mutation_position AS t3 ";
    $strSQL .= "LEFT JOIN hrd_employee_mutation AS t1 ON t3.id_mutation = t1.id ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.type=0 AND status = " . REQUEST_STATUS_CHECKED . "  $strKriteria ";
    //$strSQL .= "AND proposal_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
    $strSQL .= "ORDER BY $strOrder t1.proposal_date, t2.employee_id ";
    $resDb = $db->execute($strSQL);
    $strDateOld = "";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        ($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
        $strClass = "";//getCssClass($rowDb['status']);
        $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
        $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['proposal_date'], "d-M-Y") . "&nbsp;</td>\n";
        $strResult .= "  <td>" . $rowDb['employee_id'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $strGender . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['position_old'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['grade_old'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['position_old_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['position_new'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $rowDb['grade_new'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['position_new_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // getDataPosition
// fungsi untuk menampilkan data, tapi hanya perubahan department aja
function getDataDepartment($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $ARRAY_REQUEST_STATUS;
    global $bolPrint;
    $intRows = 0;
    $strResult = "";
    // ambil dulu data employee, kumpulkan dalam array
    $i = 0;
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.gender, ";
    $strSQL .= "t3.department_old, t3.department_new, t3.department_date ";
    $strSQL .= "FROM hrd_employee_mutation_department AS t3 ";
    $strSQL .= "LEFT JOIN hrd_employee_mutation AS t1 ON t3.id_mutation = t1.id ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.type=0 AND status = " . REQUEST_STATUS_CHECKED . "  $strKriteria ";
    //     $strSQL .= "AND proposal_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' ";
    $strSQL .= "ORDER BY $strOrder t1.proposal_date, t2.employee_id ";
    $resDb = $db->execute($strSQL);
    $strDateOld = "";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        ($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
        $strClass = "";// getCssClass($rowDb['status']);
        $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
        $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['proposal_date'], "d-M-Y") . "&nbsp;</td>\n";
        $strResult .= "  <td>" . $rowDb['employee_id'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . $strGender . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['department_old'] . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['department_new'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['department_date'], "d-M-y") . "&nbsp;</td>";
        $strResult .= "  <td>" . $rowDb['note'] . "&nbsp;</td>";
        $strResult .= "  <td align=center>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "&nbsp;</td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // getDataDepartment
// fungsi untuk menghapus data
function changeStatus($db, $intStatus)
{
    global $_REQUEST;
    if (!is_numeric($intStatus)) {
        return false;
    }
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $i++;
            // cari dulu ID
            $strSQL = "SELECT id_employee FROM hrd_employee_mutation WHERE id = '$strValue' ";
            $resTmp = $db->execute($strSQL);
            $strIDEmployee = ($rowTmp = $db->fetchrow($resTmp)) ? $rowTmp['id_employee'] : "";
            $strSQL = "UPDATE hrd_employee_mutation SET status = '$intStatus',  ";
            $strSQL .= "approval_date = now() WHERE id = '$strValue' ";
            $resExec = $db->execute($strSQL);
            // update status emploiyee
            updateEmployeeCareerData($db, $strIDEmployee);
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data", 0);
    }
} //changeStatus
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    // hapus data jika ada perintah
    if (isset($_REQUEST['btnApproved'])) {
        if ($bolCanEdit) {
            changeStatus($db, REQUEST_STATUS_APPROVED);
        }
    } else if (isset($_REQUEST['btnDenied'])) {
        if ($bolCanEdit) {
            changeStatus($db, 3);
        }
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    //getDefaultSalaryPeriode($strDefaultFrom,$strDefaultThru);
    //(isset($_REQUEST['dataDateFrom'])) ? $strDataDateFrom = $_REQUEST['dataDateFrom'] : $strDataDateFrom = $strDefaultFrom;
    //(isset($_REQUEST['dataDateThru'])) ? $strDataDateThru = $_REQUEST['dataDateThru'] : $strDataDateThru = $strDefaultThru;
    (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
    (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
    (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
    (isset($_REQUEST['dataOption'])) ? $strDataOption = $_REQUEST['dataOption'] : $strDataOption = "";
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    $strKriteria = "";
    if ($strDataDepartment != "") {
        $strKriteria .= "AND department_code = '$strDataDepartment' ";
    }
    if ($strDataSection != "") {
        $strKriteria .= "AND section_code = '$strDataSection' ";
    }
    if ($strDataEmployee != "") {
        $strKriteria .= "AND employee_id = '$strDataEmployee' ";
    }
    $strKriteria .= $strKriteriaCompany;
    if ($bolCanView) {
        //if (validStandardDate($strDataDateFrom) && validStandardDate($strDataDateThru)) {
        // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
        $strDataDetail = getData($db, $intTotalData, $strKriteria);
        //} else {
        //  $strDataDetail = "";
        //}
        // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
        if ($bolPrint) {
            // perintah printing, cek jenis yg diprint
            if (isset($_REQUEST['btnPrintStatus'])) {
                $strDisplayStatus = ""; // biar tampilkan
                $strDataDetail = getDataStatus($db, $intTotalData, $strKriteria);
            } else if (isset($_REQUEST['btnPrintPosition'])) {
                $strDisplayPosition = "";
                $strDataDetail = getDataPosition($db, $intTotalData, $strKriteria);
            } else if (isset($_REQUEST['btnPrintDepartment'])) {
                $strDisplayDepartment = "";
                $strDataDetail = getDataDepartment($db, $intTotalData, $strKriteria);
            } else {
                $strDisplayAll = "";
            }
        }
    } else {
        showError("view_denied");
    }
    $intDefaultWidthPx = 200;
    //$strInputDateFrom = "<input type=text name=dataDateFrom id=dataDateFrom size=15 maxlength=10 value=\"$strDataDateFrom\">";
    //$strInputDateThru = "<input type=text name=dataDateThru id=dataDateThru size=15 maxlength=10 value=\"$strDataDateThru\">";
    $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"$strDataEmployee\">";
    $strInputDepartment = getDepartmentList(
        $db,
        "dataDepartment",
        $strDataDepartment,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputSection = getSectionList(
        $db,
        "dataSection",
        $strDataSection,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
    //handle user company-access-right
    $strInputCompany = getCompanyList(
        $db,
        "dataCompany",
        $strDataCompany,
        $strEmptyOption2,
        $strKriteria2,
        "style=\"width:$intDefaultWidthPx\" "
    );
    $strInputOption = "";
    // informasi tanggal kehadiran
    /*
    if ($strDataDateFrom == $strDataDateThru) {
      $strInfo .= "<br>".strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
    } else {
      $strInfo .= "<br>".strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
      $strInfo .= " >> ".strtoupper(pgDateFormat($strDataDateThru, "d-M-Y"));
    }
    */
    // tampilkan button
    if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        $strButtons .= "&nbsp;<input type=submit name=btnApproved value=\"" . $words['approved'] . "\" onClick=\"return confirmChanges(false)\">";
        $strButtons .= "&nbsp;<input type=submit name=btnDenied value=\"" . $words['denied'] . "\" onClick=\"return confirmChanges(true)\">";
    }
    //$strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDataDateFrom\">";file:///home/yudi/public_html/artajasa/hr/mutation_approval.php
    //$strHidden .= "<input type=hidden name=dataDateThru value=\"$strDataDateThru\">";
    $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
    $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
    $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
    $strHidden .= "<input type=hidden name=dataOption value=\"$strDataOption\">";
}
($bolPrint) ? $strMainTemplate = getTemplate("mutation_list_print.html") : $strTemplateFile = getTemplate(
    "mutation_approval.html"
);
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords("mutation approval");
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>