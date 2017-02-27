<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
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
$bolPrint = (isset($_REQUEST['btnPrint']));
$bolCanView = getUserPermission("medical_list.php", $bolCanEdit, $bolCanDelete, $strError);
$strUserName = $_SESSION['sessionUserName'];
$strMainMenu = getMainMenu();
$strSubMenu = getSubMenu();
//---- INISIALISASI ----------------------------------------------------
$strWordsMedicalDataDenied = getWords("medical data - denied");
$strWordsInputMedicalClaimList = getWords("input medical claim list");
$strWordsInputMedicalClaim = getWords("input medical claim");
$strWordsMedicalClaimList = getWords("medical claim list");
$strWordsEmployeeMedicalQuota = getWords("employee medical quota");
$strWordsLISTOFEMPLOYEEMEDICALCLAIM = getWords("list of employee medical claim");
$strWordsEMPLID = getWords("empl.id");
$strWordsNAME = getWords("name");
$strWordsGENDER = getWords("sex");
$strWordsPOSITION = getWords("level");
$strWordsDEPT = getWords("dept.");
$strWordsFORMNO = getWords("form no.");
$strWordsNAME = getWords("name");
$strWordsRELATION = getWords("relation");
$strWordsTYPE = getWords("type");
$strWordsCODE = getWords("code");
$strWordsTREATMENTDISEASE = getWords("treatment/disease");
$strWordsMEDDATE = getWords("med. date");
$strWordsCLAIMDATE = getWords("claim date");
$strWordsCOST = getWords("cost");
$strWordsAPVCOST = getWords("apv.cost");
$strWordsSTATUS = getWords("status");
$strWordsPAYMENTREQUEST = getWords("payment request");
$strWordsSave = getWords("save");
$strWordsCancel = getWords("cancel");
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strButtonList = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows)
{
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    global $ARRAY_REQUEST_STATUS;
    global $ARRAY_PAYMENT_METHOD;
    global $ARRAY_FAMILY_RELATION;
    global $ARRAY_TREATMENT_TYPE;
    $intRows = 0;
    $strResult = "";
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $i = 0;
            $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t2.position_code, ";
            $strSQL .= "t2.gender, t2.department_code, t2.section_code, t2.grade_code ";
            $strSQL .= "FROM hrd_medical_claim_master AS t1, hrd_employee AS t2 ";
            $strSQL .= "WHERE t1.id_employee = t2.id AND t1.id = '$strValue' AND status < '" . REQUEST_STATUS_APPROVED . "' ";
            $resDb = $db->execute($strSQL);
            if ($rowDb = $db->fetchrow($resDb)) {
                $intRows++;
                $intTotal = 0;
                $fltTotalCost = 0;
                $fltTotalCostApproved = 0;
                $strDetail = "";
                $strFormNo = $rowDb['code'] . "-" . $rowDb['no'] . "/" . $rowDb['month_code'] . "/" . $rowDb['year_code'];
                ($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
                $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
                $strPaymentStatus = ($rowDb['payment_status'] == 0) ? "x" : "&radic";
                $bolDenied = false;
                if ($rowDb['status'] == 0) { //new
                    $strClass = "class=bgNewRevised";
                } else if ($rowDb['status'] == 4) {
                    $strClass = "class=bgDenied";
                    $bolDenied = true;
                } else {
                    $strClass = "";
                }
                $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" $strClass>\n";
                $strResult .= "  <td align=center><input type=hidden name='chkID$intRows' value=\"" . $rowDb['id'] . "\">$intRows.&nbsp;</td>\n";
                $strResult .= "  <td nowrap>" . $rowDb['employee_id'] . "&nbsp;</td>";
                $strResult .= "  <td nowrap nowrap>" . $rowDb['employee_name'] . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . $strGender . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . $rowDb['position_code'] . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . $rowDb['department_code'] . "&nbsp;</td>";
                $strResult .= "  <td align=center nowrap>" . $strFormNo . "&nbsp;</td>";
                // cari data detail claim
                $strSQL = "SELECT * FROM hrd_medical_claim WHERE id_master = '" . $rowDb['id'] . "' ";
                $resTmp = $db->execute($strSQL);
                while ($rowTmp = $db->fetchrow($resTmp)) {
                    $intTotal++;
                    $strRelation = ($rowTmp['relation'] >= 0) ? $words[$ARRAY_FAMILY_RELATION[$rowTmp['relation']]] : "";
                    //$strRelation = $words[$ARRAY_FAMILY_RELATION[$rowTmp['relation']]];
                    if ($intTotal == 1) {
                        // tambahkan di baris sejajar dengan nama
                        $strResult .= "  <td nowrap>&nbsp;" . $rowTmp['name'] . "</td>\n";
                        $strResult .= "  <td align=center>&nbsp;" . $strRelation . "</td>\n";
                        $strResult .= "  <td>&nbsp;" . $words[$ARRAY_TREATMENT_TYPE[$rowTmp['type']]] . "</td>\n";
                        $strResult .= "  <td>&nbsp;" . $rowTmp['medical_code'] . "</td>\n";
                        $strResult .= "  <td>&nbsp;" . $rowTmp['disease'] . "</td>\n";
                        $strResult .= "  <td align=center>&nbsp;" . pgDateFormat(
                                $rowTmp['medical_date'],
                                "d-M-y"
                            ) . "</td>\n";
                        $strResult .= "  <td align=center>&nbsp;" . pgDateFormat(
                                $rowTmp['claim_date'],
                                "d-M-y"
                            ) . "</td>\n";
                        $strResult .= "  <td align=right>" . standardFormat($rowTmp['cost'], true) . "&nbsp;</td>\n";
                        $strResult .= "  <td align=right>" . standardFormat(
                                $rowTmp['approved_cost'],
                                true
                            ) . "&nbsp;</td>\n";
                    } else {
                        // tambahkan di bawah
                        $strDetail .= " <tr valign=top $strClass>";
                        $strDetail .= "  <td colspan=7>&nbsp;</td>\n";
                        $strDetail .= "  <td>&nbsp;" . $rowTmp['name'] . "</td>\n";
                        $strDetail .= "  <td align=center>&nbsp;" . $strRelation . "</td>\n";
                        $strDetail .= "  <td>&nbsp;" . $words[$ARRAY_TREATMENT_TYPE[$rowTmp['type']]] . "</td>\n";
                        $strDetail .= "  <td>&nbsp;" . $rowTmp['medical_code'] . "</td>\n";
                        $strDetail .= "  <td>&nbsp;" . $rowTmp['disease'] . "</td>\n";
                        $strDetail .= "  <td align=center>&nbsp;" . pgDateFormat(
                                $rowTmp['medical_date'],
                                "d-M-y"
                            ) . "</td>\n";
                        $strDetail .= "  <td align=center>&nbsp;" . pgDateFormat(
                                $rowTmp['claim_date'],
                                "d-M-y"
                            ) . "</td>\n";
                        $strDetail .= "  <td align=right>" . standardFormat($rowTmp['cost'], true) . "&nbsp;</td>\n";
                        $strDetail .= "  <td align=right>" . standardFormat(
                                $rowTmp['approved_cost'],
                                true
                            ) . "&nbsp;</td>\n";
                        $strDetail .= "  <td colspan=5>&nbsp;</td>\n";
                        $strDetail .= "</tr>";
                    }
                    $fltTotalCost += $rowTmp['cost'];
                    $fltTotalCostApproved += $rowTmp['approved_cost'];
                }
                if ($intTotal == 0) {
                    // kosongkan data
                    $strResult .= "  <td>&nbsp;</td>";
                    $strResult .= "  <td>&nbsp;</td>";
                    $strResult .= "  <td>&nbsp;</td>";
                    $strResult .= "  <td>&nbsp;</td>";
                    $strResult .= "  <td>&nbsp;</td>";
                    $strResult .= "  <td>&nbsp;</td>";
                    $strResult .= "  <td>&nbsp;</td>";
                    $strResult .= "  <td>&nbsp;</td>";
                    $strResult .= "  <td>&nbsp;</td>";
                    $strResult .= "  <td>&nbsp;</td>";
                }
                // cari status FPK
                $strSQL = "SELECT id FROM hrd_cash_request WHERE source_id = '" . $rowDb['id'] . "' AND \"type\" = 1 ";
                $resTmp = $db->execute($strSQL);
                if ($rowTmp = $db->fetchrow($resTmp)) {
                    $strPaymentStatus = "[&radic;]";
                } else {
                    $strPaymentStatus = "";
                }
                //$strResult .= "  <td>" .$words[$ARRAY_PAYMENT_METHOD[$rowDb['method']]]. "&nbsp;</td>";
                //$strResult .= "  <td>" .$rowDb['account']. "&nbsp;</td>";
                //$strResult .= "  <td>" .pgDateFormat($rowDb['payment_date'],"d-M-Y"). "&nbsp;</td>";
                //$strResult .= "  <td align=center>" .$strPaymentStatus. "&nbsp;</td>";
                $strResult .= "  <td align=center>" . $words[$ARRAY_REQUEST_STATUS[$rowDb['status']]] . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . $strPaymentStatus . "&nbsp;</td>";
                $strResult .= "</tr>\n";
                if ($intTotal > 0) {
                    $strResult .= $strDetail;
                    // tambahkan total di sini
                    $strResult .= " <tr valign=top $strClass>";
                    $strResult .= "  <td colspan=7>&nbsp;</td>\n";
                    $strResult .= "  <td colspan=7 align=right><strong>" . $words['total'] . "</strong>&nbsp;</td>\n";
                    $strResult .= "  <td align=right><strong>" . standardFormat(
                            $fltTotalCost,
                            true
                        ) . "</strong>&nbsp;</td>\n";
                    $strResult .= "  <td align=right><strong>" . standardFormat(
                            $fltTotalCostApproved,
                            true
                        ) . "</strong>&nbsp;</td>\n";
                    $strResult .= "  <td colspan=5>&nbsp;</td>\n";
                    $strResult .= "</tr>";
                }
                // tambahkan info alasan penolakan
                $strResult .= "<tr valign=top>\n";
                $strResult .= "  <td >&nbsp;</td>\n";
                $strResult .= "  <td colspan=17><strong>" . $words['reason'] . "&nbsp; : <input type=text size=100 maxlength=240 name=detailNote$intRows value=\"" . $rowDb['note_denied'] . "\" class=string>&nbsp;</strong></td>\n";
                $strResult .= "</tr>\n";
            }
        }
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
    return $strResult;
} // showData
// fungsi untuk mengubah status data
function changeStatusData($db)
{
    global $_REQUEST;
    global $_SESSION;
    $strmodified_by = $_SESSION['sessionUserID'];
    $intTotal = (isset($_REQUEST['totalData'])) ? $_REQUEST['totalData'] : 0;
    $intStatus = REQUEST_STATUS_DENIED;
    for ($i = 1; $i <= $intTotal; $i++) {
        if (isset($_REQUEST['chkID' . $i])) {
            $id = $_REQUEST['chkID' . $i];
            $strNote = (isset($_REQUEST['detailNote' . $i])) ? $_REQUEST['detailNote' . $i] : "";
            $strSQL = "UPDATE hrd_medical_claim_master SET status = $intStatus, note_denied = '$strNote', ";
            $strSQL .= "denied_by = '$strUpdater', denied_time = now() ";
            $strSQL .= "WHERE id = '$id' ";
            $resExec = $db->execute($strSQL);
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data", 0);
    }
    header("location:medical_list.php");
    exit();
} //changeStatusData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $bolIsEmployee = isUserEmployee();
    if ($bolCanEdit) {
        if (isset($_REQUEST['btnSave'])) {
            changeStatusData($db);
        }
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    $strKriteria = "";
    // jika login sebagai karyawan, cuma bisa lihat punya dia
    $strReadonly = "";
    if ($bolIsEmployee) {
        header("location:medical_list.php");
        exit();
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $intTotalData);
    } else {
        header("location:medical_list.php");
        exit();
    }
    // generate data hidden input dan element form input
    /*
    // informasi tanggal kehadiran
    if ($strDataDateFrom == $strDataDateThru) {
      $strInfo .= "<br>".strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
    } else {
      $strInfo .= "<br>".strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
      $strInfo .= " >> ".strtoupper(pgDateFormat($strDataDateThru, "d-M-Y"));
    }
    */
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>