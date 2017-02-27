<?php
session_start();
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  header("location:login.php?dataPage=trip_payment_list.php");
  exit();
}
$bolCanView = getUserPermission("trip_payment_list.php", $bolCanEdit, $bolCanDelete, $strError, true);
$strTemplateFile = getTemplate("trip_payment_list.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$strButtonList = "";
$intTotalData = 0;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $ARRAY_REQUEST_STATUS;
  global $ARRAY_PAYMENT_METHOD;
  $intRows = 0;
  $strResult = "";
  // ambil dulu data employee, kumpulkan dalam array
  $arrEmployee = [];
  $i = 0;
  $strSQL = "SELECT t1.*, t2.id AS idemployee, t2.employee_id, t2.employee_name,  ";
  $strSQL .= "t2.position_code, t2.gender, t2.department_code, t2.section_code, ";
  $strSQL .= "t2.employee_status, t3.location, t3.allowance, t3.id AS \"tripID\" ";
  $strSQL .= "FROM hrd_trip_payment AS t1 LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
  $strSQL .= "LEFT JOIN hrd_trip AS t3 ON t1.id_trip = t3.id ";
  $strSQL .= "WHERE t1.request_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' $strKriteria ";
  $strSQL .= "ORDER BY $strOrder t1.request_date, t2.employee_name ";
  $resDb = $db->execute($strSQL);
  $strDateOld = "";
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    ($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
    $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
    $strNomor = $rowDb['no'] . "/" . $rowDb['code'] . "/" . $rowDb['month_code'] . "/" . $rowDb['year_code'];
    switch ($rowDb['status']) {
      case 0 :
        $strClass = "class=bgNewData";
        break;
      case 1 :
        $strClass = "class=bgVerifiedData";
        break;
      case 2 :
        $strClass = "class=bgCheckedData";
        break;
      case 4 :
        $strClass = "class=bgDenied";
        $bolDenied = true;
        break;
      default :
        $strClass = "";
        break;
    }
    $fltTotalCost = $rowDb['totalAmount'];
    /*
    $fltTotalCost = 0
    // cari total biaya
    $strSQL  = "SELECT SUM(amount) AS total1 ";
    $strSQL .= "FROM hrd_trip_payment_other WHERE id_trip_payment = '" .$rowDb['id']. "' ";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      $fltTotalCost = $rowTmp['total1'];
    }
    */
    $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" $strClass>\n";
    $strResult .= "  <td><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></td>\n";
    $strResult .= "  <td>" . pgDateFormat($rowDb['request_date'], "d-M-y") . "&nbsp;</td>\n";
    $strResult .= "  <td nowrap>" . $strNomor . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['employee_id'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['department_code'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['section_code'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['location'] . "&nbsp;</td>";
    $strResult .= "  <td align=right>" . standardFormat($fltTotalCost) . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $words[$ARRAY_PAYMENT_METHOD[$rowDb['method']]] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . pgDateFormat($rowDb['paymentDate'], "d-M-y") . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $words[$ARRAY_REQUEST_STATUS[$rowDb['status']]] . "&nbsp;</td>";
    $strResult .= "  <td align=center><a href=\"trip_payment_edit.php?dataTripID=" . $rowDb['tripID'] . "\">" . $words['edit'] . "</a>&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  return $strResult;
} // showData
// fungsi untuk menghapus data
function deleteData($db)
{
  global $_REQUEST;
  $i = 0;
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 5) == 'chkID') {
      $i++;
      $strSQL = "DELETE FROM hrd_trip_payment_other WHERE id_trip_payment = '$strValue'; ";
      $strSQL = "DELETE FROM hrd_trip_payment WHERE id = '$strValue' ";
      $resExec = $db->execute($strSQL);
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data", 0);
  }
} //deleteData
// fungsi untuk mengubah status data
function changeStatusData($db, $intStatus)
{
  global $_REQUEST;
  $i = 0;
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 5) == 'chkID') {
      $i++;
      $strSQL = "UPDATE hrd_trip_payment SET status = $intStatus WHERE id = '$strValue' ";
      $strSQL .= "AND status < $intStatus "; // hanya yang lebih bawah yang bisa diubah statusnya
      $resExec = $db->execute($strSQL);
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "change status ($intStatus) $i data", 0);
  }
} //changeStatusData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  // hapus data jika ada perintah
  if (isset($_POST['btnDelete'])) {
    if ($bolCanDelete) {
      deleteData($db);
    }
  }
  if ($bolCanEdit) {
    if (isset($_POST['btnDenied'])) {
      changeStatusData($db, 4);
    } else if (isset($_POST['btnApproved'])) {
      changeStatusData($db, 3);
    } else if (isset($_POST['btnVerified'])) {
      changeStatusData($db, 1);
    } else if (isset($_POST['btnChecked'])) {
      changeStatusData($db, 2);
    }
  }
  $strDate = "";
  // ------ AMBIL DATA KRITERIA -------------------------
  //getDefaultSalaryPeriode($strDefaultFrom,$strDefaultThru, $strDate);
  $dtNow = getdate();
  $tsThru = $dtNow[0];
  $tsFrom = $tsThru - (86400);
  $strDefaultFrom = date("Y-m-d", $tsFrom);
  $strDefaultThru = date("Y-m-d", $tsThru);
  (isset($_REQUEST['dataDateFrom'])) ? $strDataDateFrom = $_REQUEST['dataDateFrom'] : $strDataDateFrom = $strDefaultFrom;
  (isset($_REQUEST['dataDateThru'])) ? $strDataDateThru = $_REQUEST['dataDateThru'] : $strDataDateThru = $strDefaultThru;
  (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
  (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  $strReadonly = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) ? "readonly" : "";
  $strDisabled = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) ? "disabled" : "";
  if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    if ($arrUserInfo['isDeptHead']) {
      if ($strDataDepartment == "") {
        $strDataDepartment = $arrUserInfo['department_code'];
      }
    } else if ($arrUserInfo['isGroupHead']) {
      if ($strDataSection == "") {
        $strDataSection = $arrUserInfo['section_code'];
      }
    } else {
      $strDataEmployee = $arrUserInfo['employee_id'];
      $strReadonly = "readonly";
      $strDisabled = "disabled";
    }
  }
  if ($strDataDepartment != "") {
    $strKriteria .= "AND department_code = '$strDataDepartment' ";
  }
  if ($strDataSection != "") {
    $strKriteria .= "AND section_code = '$strDataSection' ";
  }
  if ($strDataEmployee != "") {
    $strKriteria .= "AND employee_id = '$strDataEmployee' ";
  }
  // tambahkan button sesuai peran
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
    $strButtonList .= "&nbsp;<input type=submit name=btnChecked value=\"" . $words['checked'] . "\" onClick=\"return confirmStatusChanges()\">";
    $strButtonList .= "&nbsp;<input type=submit name=btnDenied value=\"" . $words['denied'] . "\" onClick=\"return confirmStatusChanges()\">";
  } else if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    $strButtonList .= "&nbsp;<input type=submit name=btnChecked value=\"" . $words['checked'] . "\" onClick=\"return confirmStatusChanges()\">";
    $strButtonList .= "&nbsp;<input type=submit name=btnApproved value=\"" . $words['approved'] . "\" onClick=\"return confirmStatusChanges()\">";
    $strButtonList .= "&nbsp;<input type=submit name=btnDenied value=\"" . $words['denied'] . "\" onClick=\"return confirmStatusChanges()\">";
  } else if ($arrUserInfo['isDeptHead'] || $arrUserInfo['isGroupHead']) {
    $strButtonList .= "&nbsp;<input type=submit name=btnVerified value=\"" . $words['verified'] . "\" onClick=\"return confirmStatusChanges()\">";
    $strButtonList .= "&nbsp;<input type=submit name=btnDenied value=\"" . $words['denied'] . "\" onClick=\"return confirmStatusChanges()\">";
  }
  if ($bolCanView) {
    if (validStandardDate($strDataDateFrom) && validStandardDate($strDataDateThru)) {
      // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
      $strDataDetail = getData($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
    } else {
      $strDataDetail = "";
    }
  } else {
    showError("view_denied");
  }
  // generate data hidden input dan element form input
  $intDefaultWidthPx = 200;
  $strTmpKriteria = "WHERE 1=1 ";
  $strInputDateFrom = "<input type=text name=dataDateFrom id=dataDateFrom size=15 maxlength=10 value=\"$strDataDateFrom\">";
  $strInputDateThru = "<input type=text name=dataDateThru id=dataDateThru size=15 maxlength=10 value=\"$strDataDateThru\">";
  $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"$strDataEmployee\" $strReadonly>";
  if ($arrUserInfo['isDeptHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strTmpKriteria .= "AND department_code = '" . $arrUserInfo['department_code'] . "' ";
    $strDisabled = "";
  }
  $strInputDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $strDataDepartment,
      $strEmptyOption,
      $strTmpKriteria,
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  if ($arrUserInfo['isGroupHead'] && $_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
    $strTmpKriteria .= "AND section_code = '" . $arrUserInfo['section_code'] . "' ";
    $strDisabled = "";
  }
  $strInputSection = getSectionList(
      $db,
      "dataSection",
      $strDataSection,
      $strEmptyOption,
      $strTmpKriteria,
      "style=\"width:$intDefaultWidthPx\" $strDisabled"
  );
  // informasi tanggal kehadiran
  if ($strDataDateFrom == $strDataDateThru) {
    $strInfo .= "<br>" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
  } else {
    $strInfo .= "<br>" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
    $strInfo .= " >> " . strtoupper(pgDateFormat($strDataDateThru, "d-M-Y"));
  }
  $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDataDateFrom\">";
  $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDataDateThru\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
  $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
}
$strInitAction .= " document.formInput.dataDateFrom.focus();
    Calendar.setup({ inputField:\"dataDateFrom\", button:\"btnDateFrom\" });
    Calendar.setup({ inputField:\"dataDateThru\", button:\"btnDateThru\" });
    init();
    onCodeBlur();
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>