<?php
$need_special_tbs = "390";
//if ( !session_id() ) session_start();
date_default_timezone_set('Asia/Jakarta');
include_once('../global/session.php');
include_once('global.php');
// include_once('form_object.php');
include_once('../includes/tbsclass/plugins/tbs_plugin_opentbs.php');
//include_once('../includes/tbs_plugin_opentbs_1.9.4/demo/tbs_class.php'); // Load the TinyButStrong template engine
//include_once('../includes/tbs_plugin_opentbs_1.9.4/tbs_plugin_opentbs.php'); // Load the OpenTBS plugin
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
//if (!isset($_SESSION['sessionUserID'])) {
//  header("location:login.php?dataPage=salaryCalculation.php");
//  exit();
//}
// $bolCanView = getUserPermission("report_tax_yearly.php", $bolCanEdit, $bolCanDelete, $strError);
// if (!$bolCanView) {
//   die($strError);
// }
$bolCanView = true;
//---- INISIALISASI ----------------------------------------------------
$arrTax = $_POST['result'];
$arrTax = str_replace('$%^', '"', $arrTax);
$arrTax = unserialize($arrTax);
//    print_r($arrTax);
//die();
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
// $db = new CdbClass;
// if ($db->connect()) {
// ------ AMBIL DATA KRITERIA -------------------------
if ($bolCanView) {
    if ($arrTax) {
        $strEmployeeNPWP1 = substr($arrTax['npwp'], 0, 12);
        $strEmployeeNPWP2 = substr($arrTax['npwp'], 13, 3);
        $strEmployeeNPWP3 = substr($arrTax['npwp'], 17, 3);
        $strCompanyNPWP = getSetting("company_npwp");
        $strCompanyNPWP1 = substr($strCompanyNPWP, 0, 12);
        $strCompanyNPWP2 = substr($strCompanyNPWP, 13, 3);
        $strCompanyNPWP3 = substr($strCompanyNPWP, 17, 3);
        $strPemotongNPWP = getSetting("id_pemotong_npwp");
        $strPemotongNPWP1 = substr($strPemotongNPWP, 0, 12);
        $strPemotongNPWP2 = substr($strPemotongNPWP, 13, 3);
        $strPemotongNPWP3 = substr($strPemotongNPWP, 17, 3);
        $strCompanyName = strtoupper(getSetting("company_name"));
        $strNoSPT = getSetting("no_spt");
        $strEmployeeName = substr($arrTax['employeeName'], 0, 45);
        $strNoSPT1 = substr($strNoSPT, 0, 1);
        $strNoSPT2 = substr($strNoSPT, 2, 1);
        $strNoSPT3 = substr($strNoSPT, 4, 2);
        $strNoSPT4 = substr($strNoSPT, 7, 2);
        $strNoSPT5 = $_POST['row'];
        $strDateSigned = date('d');
        $strMonthSigned = date('m');
        $strYearSigned = date('Y');
        $strPemotongName = strtoupper(getSetting("id_pemotong_name"));
        $strPrimaryAddress = $arrTax['primaryAddress'];
        $strPrimaryAddress1 = substr($strPrimaryAddress, 0, 45);
        $strPrimaryAddress2 = $arrTax['primaryCity'];
        $strPrimaryAddress3 = $arrTax['primaryZip'];
        //          $x = "../../images/1721A1.jpg";
        //echo $strEmployeeName;
        //var_dump($arrTax);
        //die();
        $tbsPage = new clsTinyButStrong;
        $tbsPage->PlugIn(TBS_INSTALL, OPENTBS_PLUGIN);
        //           echo getTemplate("1721_A1.xml");
        $tbsPage->LoadTemplate(getTemplate("1721_A1.xlsx"));
        //          $tbsPage->PlugIn(OPENTBS_DEBUG_INFO [, $Exit])
        $output_file_name = 'pph-' . $strEmployeeName . '-' . $arrTax['year'] . '.xlsx';
        $tbsPage->Show(OPENTBS_DOWNLOAD, $output_file_name);
    } else {
        echo "<script>alert('No record found')</script>";
        header("location:report_tax_yearly.php");
        exit();
    }
} else {
    showError("view_denied");
}
// }
?>