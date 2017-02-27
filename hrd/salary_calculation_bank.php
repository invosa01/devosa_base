<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('salary_func.php');
include_once('../includes/tbsclass/plugin_excel/tbs_plugin_excel.php');
// periksa apakah sudah login atau belum, jika belum, harus login lagi
$dataPrivilege = getDataPrivileges(
    "salary_calculation.php",
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
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strDataColumn = "";
$strHidden = "";
$strButtons = "";
$intTotalData = 0;
$strPaging = "";
$strDataID = "";
$strCalculationMenu = "";
$strDataDateFrom = "";
$strDataDateThru = "";
$strKriteria = " ";
$strWidth = "75px";
$strSpan1 = 0; // colspan untuk colum allowance
$strSpan2 = 7; // colspan untuk colum paging
//---- INISIALISASI ----------------------------------------------------
$strDataID = "";
$strPeriode = "";
$strKriteria = "";
$arrData = [];
$arrFields = [];
$arrEmp = [];
$arrEmpAllowance = [];
$arrEmpDeduction = [];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataID, &$intRows, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
{
    global $words;
    global $arrData;
    global $arrFields;
    global $arrEmp;
    global $strPeriode;
    global $strCompany;
    global $ARRAY_CURRENCY_CODE;
    global $smonth;
    global $syear;
    $strResult = "";
    if ($strDataID == "") {
        return "";
    } else {
        // cari info data
        $strSQL = "SELECT *, company_name FROM \"hrd_salary_master\" AS t1 ";
        $strSQL .= "LEFT JOIN hrd_company AS t2 ON t1.id_company = t2.id ";
        $strSQL .= "WHERE t1.id = '$strDataID' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strPeriode = strtoupper(date("M Y"));
            $strCompany = $rowDb['company_name'];
        } else {
            return "";
        }
    }
    $strSQL = "SELECT irregular, hide_blank,(EXTRACT(MONTH FROM salary_date)) as smonth ,(EXTRACT(YEAR FROM salary_date)) as syear FROM hrd_salary_master WHERE id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        $bolIrregular = ($rowDb['irregular'] == 't');
        $bolHideBlank = ($rowDb['hide_blank'] == 't');
        $smonth = $rowDb['smonth'];
        $syear = $rowDb['syear'];
    }
    $strSQL = "SELECT t1.total_gross, t1.total_gross_irregular, t1.employee_id, t2.employee_name, t2.bank_account,t2.division_code, t2.bank_account_name, t3.bank_name, 'IDR' as idr ";
    $strSQL .= "FROM hrd_salary_detail AS t1 LEFT JOIN hrd_employee AS t2 ON t1.\"id_employee\" = t2.id ";
    $strSQL .= "LEFT JOIN hrd_bank AS t3 ON t2.bank_code = t3.bank_code ";
    $strSQL .= "WHERE t1.id_salary_master = '$strDataID' $strKriteria ORDER BY t1.employee_id ";
    $resDb = $db->execute($strSQL);
    $i = 0;
    while ($rowDb = $db->fetchrow($resDb)) {
        if ($bolHideBlank) {
            if (!$bolIrregular && $rowDb['total_gross'] <= 0) {
                continue;
            } elseif ($bolIrregular && $rowDb['total_gross_irregular'] <= 0) {
                continue;
            }
        }
        if ($bolIrregular) {
            $rowDb['total_gross'] = $rowDb['total_gross_irregular'];
        }
        $rowDb['bank_account'] = strval($rowDb['bank_account']);
        $arrData[] = $rowDb;
    }
} // showData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$smonth = "XX";
$syear = "XXXX";
$db = new CdbClass;
if ($db->connect()) {
    (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
    if ($strDataID == "") {
        header("location:salary_calculation.php");
        exit();
    }
    $strCompanyCode = getSetting("company_code");
    $strCompanyAccount = getSetting("company_account");
    // ------ AMBIL DATA KRITERIA -------------------------
    (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
    (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
    (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
    (isset($_REQUEST['dataType'])) ? $strDataType = $_REQUEST['dataType'] : $strDataType = "0";
    (isset($_REQUEST['dataFilterValue'])) ? $strDataFilterValue = $_REQUEST['dataFilterValue'] : $strDataFilterValue = "0";
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    //$strKriteria = "";
    // print "<br>type data => ".$strDataType."<br>";
    if ($strDataEmployee != "") {
        $strKriteria .= "AND t1.\"employee_id\" = '$strDataEmployee' ";
    }
    if ($strDataSection != "") {
        $strKriteria .= "AND t1.\"section_code\" = '$strDataSection' ";
    }
    if ($strDataDepartment != "") {
        $strKriteria .= "AND t1.\"department_code\" = '$strDataDepartment' ";
    }
    if ($strDataType != "") {
        $strKriteria .= "AND t2.bank_code = '$strDataType' ";
    }
    if ($bolCanView) {
        $strDataDetail = getData($db, $strDataID, $intTotalData, $strKriteria);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
    if ($strDataType == "BCA") {
        // $strKriteria .= "AND t2.bank_code = 'BCA' ";
        header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="transfer_bca.txt"');
        $strTxtRecord = "";
        $totalsalary = 0;
        //var_dump($arrData);
        foreach ($arrData as $value) {
            $Rstrtotalsalary = number_format($value["total_gross"], 2); // buat koma 2
            $Rstrtotalsalary = str_replace(".", "", $Rstrtotalsalary); //hilangkan komanya
            $Rstrtotalsalary = str_replace(",", "", $Rstrtotalsalary); //hilangkan komanya
            $rNorek = str_pad($value["bank_account"], 10, "0", STR_PAD_LEFT); //10 digit 2-11 bank_account
            $rJmlTransfer = str_pad($Rstrtotalsalary, 15, "0", STR_PAD_LEFT);//15 digit 2 angka dibelakang koma 12-26
            $rNoPegawai = str_pad($value["employee_id"], 10, " ", STR_PAD_LEFT); //10 digit 27-36
            $rNamaPegawai = str_pad(substr($value["employee_name"], 0, 30), 30, " ", STR_PAD_LEFT);; // 30 digit 37-66
            $rDept = substr($value["division_code"], 0, 4); //4 digit 67-70
            $strTxtRecord .= "0" . $rNorek . $rJmlTransfer . $rNoPegawai . $rNamaPegawai . $rDept . "\r\n";
            $totalsalary += $value["total_gross"];
        }
        $strtotalsalary = number_format($totalsalary, 2); // buat koma 2
        $strtotalsalary = str_replace(".", "", $strtotalsalary); //hilangkan komanya
        $strtotalsalary = str_replace(",", "", $strtotalsalary); //hilangkan komanya
        $kodeKosong = "           "; // spasi 11 digit
        $kodecabang = "0255"; // 4 digit ke 12-15
        $kodePerusahaan = "00024"; //5 digit 16 - 20 kode perusahaan
        $inisialPerusahaan = "WANA"; // 4 digit inisial perusahaan 21-24
        $tgltransfer = "25"; //2 digit tgl transfer 25-26
        $kodeTransfer = "01"; // 2 digit selalu 01 27-28
        $norekDebet = "2553019494";//10 digit 29-38
        $bca = "0"; // 1 digit BCA=0 nonbca=1 39
        $libur = "1"; // transfer sebelum(0) atau sesudah hari libur(1)  40
        $ruang = "00"; //2 digit ruang lingkup transfer 41-42
        $jmlrecord = str_pad(count($arrData), 5, "0", STR_PAD_LEFT); //5 digit 43-47
        $totalsalary = str_pad(
            $strtotalsalary,
            17,
            "0",
            STR_PAD_LEFT
        );// 17 digit total jumlah yg ditransfer(2 digit dibelakan koma) 48-64
        $bulanproses = str_pad($smonth, 2, "0", STR_PAD_LEFT); //2 digit 65-66
        $tahunproses = str_pad($syear, 2, "0", STR_PAD_LEFT); //4 digit 67-70
        $strTxt = $kodeKosong . $kodecabang . $kodePerusahaan . $inisialPerusahaan . $tgltransfer . $kodeTransfer . $norekDebet;
        $strTxt .= $bca . $libur . $ruang . $jmlrecord . $totalsalary . $bulanproses . $tahunproses . "\r\n" . $strTxtRecord;
        echo $strTxt;
    } else {
        $tbsPage = new clsTinyButStrong;
        $tbsPage->PlugIn(TBS_INSTALL, TBS_EXCEL);
        $tbsPage->LoadTemplate(getTemplate("bank_transfer.xml"));
        $tbsPage->MergeBlock('data', $arrData);
        $tbsPage->PlugIn(TBS_EXCEL, TBS_EXCEL_FILENAME, 'upload.xls');
        $tbsPage->Show();
    }
}
?>
