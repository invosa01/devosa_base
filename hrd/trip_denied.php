<?php
session_start();
include_once('global.php');
include_once('formObject.php');
include_once('activity.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=tripList.php");
    exit();
}
$bolCanView = getUserPermission("tripList.php", $bolCanEdit, $bolCanDelete, $strError, true);
$strContentFile = "../hr/templates/tripDenied.html";
$strTemplateFile = getTemplate("mainTemplate.html");
$strPageTitle = '';
$strUserName = $_SESSION['sessionUserName'];
$strMainMenu = getMainMenu();
$strSubMenu = getSubMenu();
//---- INISIALISASI ----------------------------------------------------
$strWordsBusinessTripDataDenied = getWords("business trip data - denied");
$strWordsDataEntry = getWords("data entry");
$strWordsBusinessTripList = getWords("business trip list");
$strWordsBusinessTripPayment = getWords("business trip payment");
$strWordsLISTOFBUSINESSTRIP = getWords("list of business trip");
$strWordsREQUESTDATE = getWords("request date");
$strWordsFORMNO = getWords("form no.");
$strWordsEMPLID = getWords("empl.id");
$strWordsNAME = getWords("name");
$strWordsDEPT = getWords("dept.");
$strWordsSECT = getWords("sect.");
$strWordsDATEFROM = getWords("date from");
$strWordsDATETHRU = getWords("date thru");
$strWordsLOCATION = getWords("location");
$strWordsPURPOSE = getWords("purpose");
$strWordsTASK = getWords("task");
$strWordsALLOWANCE = getWords("allowance");
$strWordsSTATUS = getWords("status");
$strWordsPAYMENT = getWords("payment");
$strWordsSave = getWords("save");
$strWordsCancel = getWords("cancel");
$strDataDetail = "";
$strHidden = "";
$strButtonList = "";
$intTotalData = 0;
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
    $intRows = 0;
    $strResult = "";
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $strSQL = "SELECT t1.*, t2.id AS idemployee, t2.\"employeeID\", t2.\"employeeName\",  ";
            $strSQL .= "t2.\"positionCode\", t2.gender, t2.\"departmentCode\", t2.\"sectionCode\", ";
            $strSQL .= "t2.\"employeeStatus\", ";
            $strSQL .= "(t1.date_thru - t1.date_from) AS durasi ";
            $strSQL .= "FROM hrd_trip AS t1, \"hrdEmployee\" AS t2 ";
            $strSQL .= "WHERE t1.id_employee = t2.id AND t1.id = '$strValue' ";
            $resDb = $db->execute($strSQL);
            if ($rowDb = $db->fetchrow($resDb)) {
                $intRows++;
                ($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
                $strEmployeeInfo = $rowDb['employeeID'] . " - " . $rowDb['employeeName'];
                $strNomor = $rowDb['no'] . "/" . $rowDb['code'] . "/" . $rowDb['month_code'] . "/" . $rowDb['yearCode'];
                $bolDenied = false;
                if ($rowDb['status'] == 0) { //new
                    $strClass = "class=bgNewRevised";
                } else if ($rowDb['status'] == 4) {
                    $strClass = "class=bgDenied";
                    $bolDenied = true;
                } else {
                    $strClass = "";
                }
                $strTripPayment = ""; // cek status form pembayaran
                if ($rowDb['status'] >= REQUEST_STATUS_APPROVED) { // approve
                    // cari data payment
                    $strSQL = "SELECT id FROM \"hrdTripPayment\" WHERE \"idTrip\" = '" . $rowDb['id'] . "' ";
                    $resTmp = $db->execute($strSQL);
                    if ($rowTmp = $db->fetchrow($resTmp)) {
                        $strTripPayment = "&radic;";
                    } else {
                        $strTripPayment = "<input type=button value=\"" . $words['create'] . "\" name=btnPayment$intRows onClick=\"location.href='tripPaymentEdit.php?btnCreate=Create&dataTripID=" . $rowDb['id'] . "'\">";
                    }
                }
                // cari selisih hari
                $intDuration = $rowDb['durasi'] + 1;
                //$intDuration = totalWorkDay($db,$rowDb['date_from'],$rowDb['date_thru']);
                $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" $strClass>\n";
                $strResult .= "  <td align=center><input type=hidden name='chkID$intRows' value=\"" . $rowDb['id'] . "\">$intRows.&nbsp;</td>\n";
                $strResult .= "  <td>" . pgDateFormat($rowDb['proposal_date'], "d-M-y") . "&nbsp;</td>\n";
                $strResult .= "  <td nowrap>" . $strNomor . "&nbsp;</td>";
                $strResult .= "  <td>" . $rowDb['employeeID'] . "&nbsp;</td>";
                $strResult .= "  <td>" . $rowDb['employeeName'] . "&nbsp;</td>";
                $strResult .= "  <td>" . $rowDb['departmentCode'] . "&nbsp;</td>";
                $strResult .= "  <td>" . $rowDb['sectionCode'] . "&nbsp;</td>";
                $strResult .= "  <td>" . pgDateFormat($rowDb['date_from'], "d-M-y") . "&nbsp;</td>\n";
                $strResult .= "  <td>" . pgDateFormat($rowDb['date_thru'], "d-M-y") . "&nbsp;</td>\n";
                $strResult .= "  <td>" . $rowDb['location'] . "&nbsp;</td>";
                $strResult .= "  <td>" . $rowDb['purpose'] . "&nbsp;</td>";
                $strResult .= "  <td>" . nl2br($rowDb['task']) . "&nbsp;</td>";
                //$strResult .= "  <td align=right>" .$intDuration. "&nbsp;</td>";
                $strResult .= "  <td align=right>" . standardFormat($rowDb['allowance']) . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . $words[$ARRAY_REQUEST_STATUS[$rowDb['status']]] . "&nbsp;</td>";
                $strResult .= "  <td align=center>" . $strTripPayment . "&nbsp;</td>";
                $strResult .= "</tr>\n";
                // tambahkan info alasan penolakan
                $strResult .= "<tr valign=top>\n";
                $strResult .= "  <td >&nbsp;</td>\n";
                $strResult .= "  <td colspan=15><strong>" . $words['reason'] . "&nbsp; : <input type=text value=\"" . $rowDb['noteDenied'] . "\" name=detailNote$intRows class=string size=100 maxlength=240>&nbsp;</strong></td>\n";
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
            $strSQL = "UPDATE hrd_trip SET status = $intStatus, \"noteDenied\" = '$strNote', ";
            $strSQL .= "\"deniedBy\" = '$strmodified_by', \"deniedTime\" = now() ";
            $strSQL .= "WHERE id = '$id' ";
            //$strSQL .= "AND status < $intStatus "; // hanya yang lebih bawah yang bisa diubah statusnya
            $resExec = $db->execute($strSQL);
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data", 0);
    }
    header("location:tripList.php");
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
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    $strKriteria = "";
    if ($bolIsEmployee) {
        header("location:tripList.php");
        exit();
    }
    if ($bolCanView) {
        // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
        $strDataDetail = getData($db, $intTotalData);
    } else {
        header("location:tripList.php");
        exit();
    }
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
$tbsPage->LoadTemplate($strTemplateFile);
$tbsPage->Show();
?>