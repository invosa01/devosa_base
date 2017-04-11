<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
//include_once('../classes/hrd/hrd_company.php');
include_once('../global/excelReader/excel_reader.php');
//include_once('overtime_func.php');
//include_once('activity.php');
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
//---- INISIALISASI ----------------------------------------------------
$strWordsEmployeeFile = getWords("employee file to import");
$strWordsDownloadTemplate = getWords("download templates format");
$strWordsImport = getWords("import");
$strWordsRESULT = getWords("result");
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strAttendanceDate = "";
$strInputDate = "";
$bolShowResult = false;
$strResultInfo = "";
$strWordsImportData = getWords("import data");
$strMessage = "Import Message Status";
$strDownloadFiles = "<a href='../hrd/employee_import.xls'>Download</a>"; // lokasi donwnload untuk file contoh
$strParameterName = 'fileImport'; // Nama parameter yang untuk input type file
$data = ""; //new Spreadsheet_Excel_Reader($lokasiFile);  // Class excel reader
$strMessage = "";
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    if (!$bolCanView) {
        echo "<script>alert(\"" . getWords("view_denied") . "\")</script>";
    }
    if (isset($_POST['btnImport']) && $bolCanEdit) {
        //Get Information Of Files and Parameter
        $lokasiFile = $_FILES[$strParameterName]['tmp_name'];
        $tipeFile = $_FILES[$strParameterName]['type'];
        $strMessage = importProcess($db);
    }
}
$strInputFile = "<input type='file' name='" . $strParameterName . "' id='" . $strParameterName . "' size='50'> ";
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = getWords('employee import');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
// Fungsi untuk proses insert data kedalam database
// Untuk melakukan Import Data silahkan siapkan perintah Array dan querynya didalam fungsi ini
function importProcess($db)
{
    global $data, $lokasiFile, $tipeFile, $intNumberOfField, $db;
    //global $data,$lokasiFile,$tipeFile,$intNumberOfField;
    $data = new Spreadsheet_Excel_Reader($lokasiFile);  // Class excel reader
    //d($data);
    // membaca jumlah baris dari data excel yang diupload
    $baris = $data->rowcount($sheet_index = 0);
    $kolom = $data->colcount($sheet_index = 0);
    // variabel awal counter untuk jumlah data yang sukses dan yang gagal diimport
    $dataPost = $_POST;
    //$dateFrom = $dataPost['dataDateFrom'];
    //$dateThru = $dataPost['dataDateThru'];
    // import data excel mulai baris ke-2 (karena baris pertama adalah nama kolom)
    $strSQL = "";
    $arrNewData = [];
    $arrUpdateData = [];
    $strEmployeeID ="";
    for ($i = 1; $i < $baris; $i++) {
        for ($j = 1; $j <= $kolom; $j++) {
            $key = $data->val(1, $j);
            $value = $data->val($i + 1, $j);
            // sementara proses data yang belum ada di database dimasukan ke $arrNewData
            // dan data yang sudah ada di database dimasukan ke $arrUpdateData
            if ($key == 'employee_id'){
                $strEmployeeID = $value;
            }
            $isExistEmployee2 = isExistEmployee($strEmployeeID);
            if (!$isExistEmployee2){
                $arrNewData[$i + 1][$key] = $value;
            } else{
                $arrUpdateData[$i + 1][$key] = $value;
            }

        }
    }
    //var_dump($strSQL);
    //var_dump($arrNewData);
    //var_dump($arrUpdateData);
    //exit();
    $strMessage = saveDataEmployee($arrNewData);
    //$strMessage = updateDataEmployee($arrUpdateData);
    return $strMessage;
}

//  Simpan data attendance
function saveDataEmployee($arrNewData)
{
    global $db;
    $intssucces = 0;
    $intGagal = 0;
    $strMessage = "";
    $note1 ="";
    $note2 ="";
    //$strEmployeeID = "";
    foreach ($arrNewData as $column) {
        $colvals = "";
        foreach ($column as $key => $value) {
            $cols[] = $key;
            $value2 = ltrim($value," ");
            $value2 = rtrim($value2," ");
            $value2 = "'" . $value2 . "'";
            $value2 = ($value == "") ? "NULL" : $value2;
            if($key =='employee_id'){
                $note1 = $key." : ".$value." | ";
            }elseif ($key == 'employee_name'){
                $note2 = $key." : ".$value;
            }
            /*if ($key == 'wedding_date') $value2 = ($value == "" )? "NULL" : "'" . $value . "'";
            if ($key == 'birthday') $value2 = ($value == "" )? "NULL" : "'" . $value . "'";
            if ($key == 'join_date') $value2 = ($value == "" )? "NULL" : "'" . $value . "'";
            if ($key == 'due_date') $value2 = ($value == "" )? "NULL" : "'" . $value . "'";
            if ($key == 'resign_date') $value2 = ($value == "" )? "NULL" : "'" . $value . "'";
            if ($key == 'permanent_date') $value2 = ($value == "" )? "NULL" : "'" . $value . "'";
            if ($key == 'kitas_valid_until') $value2 = ($value == "" )? "NULL" : "'" . $value . "'";
            echo "--".$key."-".$value."--";*/
            $colvals .= $value2;
            $colvals .= ",";
        }
        $colnames = "\"" . implode("\",\"", $cols) . "\"";
        $colvals = substr($colvals, 0, -1);

        $strSQL = "INSERT INTO hrd_employee ($colnames) VALUES ($colvals);";
        $res = $db->execute($strSQL);

        if ($res) {
            $intssucces++;
            $strMessage .= " <font color=\"green\"><< Success >> For ".$note1.$note2."</font> <br/>";
        } else {
            $intGagal++;
            $strMessage .= " <font color=\"red\"><< failed >> For ".$note1.$note2."</font> <br/>";
        }
        //echo $strSQL;
        unset($cols, $vals);
    }
    $strMessage = "<h3>Data sucess Saved =" . $intssucces . " </br> Data Failed to Save = " . $intGagal . "</h3>" . $strMessage;
    return $strMessage;
}

function updateDataEmployee($arrUpdateData){
    global $db;
    $intssucces = 0;
    $intGagal = 0;
    $strMessage = "";
    $query ="";
    $strSQL ="";
    //Sementara data yang sudah ada tidak di insert
    foreach ($arrUpdateData as $column) {
        //$colvals = "";
        foreach ($column as $key => $value) {
            if ($key !='employee_id'){
                $column2 = "\"".$key."\"";
                $value2 = ltrim($value," ");
                $value2 = rtrim($value2," ");
                $value2 = "'" . $value2 . "'";
                $value2 = ($value == "") ? "NULL" : $value2;
                $query .= $column2."=".$value2;
                $query .= ", ";
            }else{
                $strEmployeeID = $value;
            }

        }
        $query = substr($query, 0, -2);
        $strSQL = "UPDATE hrd_employee SET ".$query." WHERE employee_id = '".$strEmployeeID."'; ";
        //var_dump($strSQL);
        //exit();
        unset($cols, $vals);
    }
    $strMessage = "<h3>Data sucess Updated =" . $intssucces . " </br> Data Failed to Update = " . $intGagal . "</h3>" . $strMessage;
    return $strMessage;
}

function  isExistEmployee($strEmployeeID)
{
    global $db;
    $bolExist = true;
    $strSQL = "SELECT id FROM hrd_employee WHERE employee_id='" . $strEmployeeID . "';";
    $res = $db->execute($strSQL);
    $arrData = $db->fetchrow($res);
    $strID = $arrData['id'];
    if ($strID == "") {
        $bolExist = false;
    }
    return $bolExist;
}

// Funsi untuk cek apakah data yang di upload kosong dan sesuai dengan format yang benar
function validateFile($parameterName)
{
    // Get Information Of Files
    $lokasiFile = $_FILES[$parameterName]['tmp_name'];
    $tipeFile = $_FILES[$parameterName]['type'];
    $bolValidate = false;
    // jika kosong file ksoong
    if ($lokasiFile == "") {
        $bolValidate = false;
    } elseif ($tipeFile != "xls") {
        $bolValidate = false;
    } else {
        $bolValidate = true;
    }
    return $bolValidate;
}

//Fungsi memishakan tanggal bila formatny menggunakan satu digit ex: 12/31/2021 (d/m/yyyy) jadikan ke format (yyyy-mm-dd)
function timeStamp2SingleDate2($date)
{
    //Pecah file berdasarkan garis miring
    $arrDateExplode = explode("/", $date);
    // Jika bulan dan tanggal satu digit maka tambhkan NOL
    if ((strlen($arrDateExplode[0])) == 1) {
        $arrDateExplode[0] = '0' . $arrDateExplode[0];
    }
    if ((strlen($arrDateExplode[1])) == 1) {
        $arrDateExplode[1] = '0' . $arrDateExplode[1];
    }
    $strResult = $arrDateExplode[2] . "-" . $arrDateExplode[0] . "-" . $arrDateExplode[1]; // dari m/d/yyyy menjadi yyyy-mm-dd
    //$strResult = $arrDateExplode[2]."-".$arrDateExplode[1]."-".$arrDateExplode[0]; // dari d/m/yyyy menjadi yyyy-mm-dd
    return $strResult;
    //return date('Y-m-d', strtotime($date));
}

?>
