<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../classes/hrd/hrd_company.php');
include_once('../global/excelReader/excel_reader.php');
include_once('overtime_func.php');
include_once('activity.php');
include_once('../includes/krumo/class.krumo.php');
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
$strWordsAttendanceData = getWords("attendance data");
$strWordsEntryAttendance = getWords("entry attendance");
$strWordsImportAttendance = getWords("import attendance");
$strWordsAttendanceList = getWords("attendance list");
$strWordsAttendanceReport = getWords("attendance report");
$strWordsAttendanceFile = getWords("attendance file");
$strWordsAttendanceDate = getWords("attendance date");
$strWordsAttendanceFile = getWords("attendance file to import");
$strWordsDownloadTemplate = getWords("download templates format");
$strWordsCompany = getWords("company");
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
$strDownloadFiles = "<a href='../hrd/attendance_list.xls'>Download</a>"; // lokasi donwnload untuk file contoh
$strParameterName = 'fileImport'; // Nama parameter yang untuk input type file
$data = ""; //new Spreadsheet_Excel_Reader($lokasiFile);  // Class excel reader
$strMessage = "";
$strDataCompany = $_SESSION['sessionIdCompany'];
$strDataCompany = ($strDataCompany != -1) ? $strDataCompany : 23; // Set Default ke HO
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    if (!$bolCanView) {
        echo "<script>alert(\"" . getWords("view_denied") . "\")</script>";
    }
    $strDateFrom = (isset($_REQUEST['dataDateFrom'])) ? $_REQUEST['dataDateFrom'] : getNextDate(date("Y-m-d"), -7);
    $strDateThru = (isset($_REQUEST['dataDateThru'])) ? $_REQUEST['dataDateThru'] : date("Y-m-d");
    $intIDCompany = (isset($_REQUEST['dataCompany'])) ? $_REQUEST['dataCompany'] : -1;
    if (isset($_POST['btnImport']) && $bolCanEdit) {
        //Get Information Of Files and Parameter
        $lokasiFile = $_FILES[$strParameterName]['tmp_name'];
        $tipeFile = $_FILES[$strParameterName]['type'];
        //d($strDateThru);
        //Panggil fungsi importproses
        $strMessage = importProcess($db);
    }
}
$strInputFile = "<input type='file' name='" . $strParameterName . "' id='" . $strParameterName . "' size='50'> ";
$strInputDate = "<input type=text name='dataDateFrom' id='dataDateFrom' value='$strDateFrom' size=13>&nbsp;";
$strInputDate .= "<input type=button name='btnDateFrom' id='btnDateFrom' value='..'>&nbsp;" . getWords(
        "until"
    ) . "&nbsp;";
$strInputDate .= "<input type=text name='dataDateThru' id='dataDateThru' value='$strDateThru' size=13>&nbsp;";
$strInputDate .= "<input type=button name='btnDateThru' id='btnDateThru' value='..'>&nbsp;";
$strInputCompany = getCompanyList(
    $db,
    "dataCompany",
    $strDataCompany,
    $strEmptyOption2,
    $strKriteria2,
    "style=\"width:258\" "
);
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = getWords('attendance import');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = attendanceSubMenu($strWordsImportAttendance);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
// Fungsi untuk proses insert data kedalam database
// Untuk melakukan Import Data silahkan siapkan perintah Array dan querynya didalam fungsi ini
function importProcess($db)
{
    global $data, $lokasiFile, $tipeFile, $intNumberOfField, $strNormalStartTime, $strNormalFinishTime, $intIdCompany, $strDateFrom, $strDateThru, $db;
    //global $data,$lokasiFile,$tipeFile,$intNumberOfField;
    $data = new Spreadsheet_Excel_Reader($lokasiFile);  // Class excel reader
    //d($data);
    // membaca jumlah baris dari data excel yang diupload
    $baris = $data->rowcount($sheet_index = 0);
    // variabel awal counter untuk jumlah data yang sukses dan yang gagal diimport
    $dataPost = $_POST;
    //$dateFrom = $dataPost['dataDateFrom'];
    //$dateThru = $dataPost['dataDateThru'];
    // import data excel mulai baris ke-2 (karena baris pertama adalah nama kolom)
    for ($i = 2; $i <= $baris; $i++) {
        $strFingerID = $data->val($i, 1); // Finger ID
        $strNIK = $data->val($i, 2); // NIK atau Nama
        $strDate = timeStamp2SingleDate2($data->val($i, 3)); // Date
        $strIN = ($data->val($i, 4) != "") ? date("G:i", strtotime($data->val($i, 4))) : 'f'; // Time IN
        $strOUT = ($data->val($i, 5) != "") ? date("G:i", strtotime($data->val($i, 5))) : 'f'; // Time Out

        //------------------------------------
        //$strIdEmployee  = getIdEmployee2($strNIK); // Ambil informasi ID dengan Select dari NIK
        $strIdEmployee = getIdEmployeeByNIK(
            $strNIK,
            $intIdCompany
        ); // Ambil informasi ID dengan Select dari fingerid

        //$strIdEmployee = getIdEmployee2($strFingerID, $intIdCompany); // Ambil informasi ID dengan Select dari NIK (finger id =NIK)
        if (!is_null($strIdEmployee) AND !empty($strIdEmployee)) {
            $strEmployeeName = getEmployeeName($strIdEmployee);
            $arrData[] = [
                "idemp"  => $strIdEmployee,
                "finger" => $strFingerID,
                "nik"    => $strNIK,
                "name"   => $strEmployeeName,
                "date"   => $strDate,
                "in"     => $strIN,
                "out"    => $strOUT
            ];
        }
    }
    //krumo($arrData);
    //exit();
    $strMessage = saveDataAttendance($arrData, $intIdCompany, $strDate, $strDate);
    return $strMessage;
}

//  Simpan data attendance
function saveDataAttendance($arrData, $strInputCompany, $strDateFrom, $strDateThru)
{
    global $db;
    $intssucces = 0;
    $intGagal = 0;
    $strMessage = "";
    $i = 0;
    foreach ($arrData AS $key => $value) {
        $i++;
        $strSQL = "";
        $strIdEmployee = $value['idemp'];
        $strDate = $value['date'];
        $strIn = ($value['in'] == 'f') ? 'null' : "'" . $value['in'] . "'";
        $strOut = ($value['out'] == 'f') ? 'null' : "'" . $value['out'] . "'";
        $strMessage .= $i . ":" . $value['idemp'] . "|" . $value['nik'] . "|" . $value['name'] . "|" . $strDate . "|" . $value['in'] . "|" . $value['out'] . "<br/>";
        if ($strIdEmployee != "") {
            // Jika tidak ada simpan sebagai data baru
            $isExistAttendance1 = isExistAttendance($strIdEmployee, $strDate);
            $isExistAbsence2 = isExistAbsence($strIdEmployee, $strDate);

            if ($isExistAttendance1) {
                #Jika Ada Absensi dibiarkan saja tidak menimpa yang sudah ada
                if (($strIn == 'null' || $strIn == null) && ($strOut == 'null' || $strOut == null)) {
                    $strSQL = "DELETE FROM hrd_attendance WHERE attendance_date='$strDate' AND id_employee='$strIdEmployee';";
                } else {
                    $strSQL = "DELETE FROM hrd_absence_detail WHERE id_absence IN (SELECT id from hrd_absence WHERE id_employee = '" . $strIdEmployee . "' AND date_from = '" . $strDate . "' AND date_thru = '" . $strDate . "' AND note like '%alpha generated by system%') AND id_employee = '" . $strIdEmployee . "' AND absence_date = '" . $strDate . "';";
                    $res = $db->execute($strSQL);
                    $strSQL = "DELETE FROM hrd_absence WHERE id_employee = '" . $strIdEmployee . "' AND date_from = '" . $strDate . "' AND date_thru = '" . $strDate . "' AND note like '%alpha generated by system%'";
                    $res = $db->execute($strSQL);
                    $strSQL = "UPDATE hrd_attendance SET attendance_date='$strDate',attendance_start=$strIn,attendance_finish=$strOut WHERE id_employee='$strIdEmployee' AND attendance_date ='$strDate';";
                }
            } else {
                if (($strIn == 'null' || $strIn == null) && ($strOut == 'null' || $strOut == null)) {
                    //do nothing
                } else {
                    $strSQL = "DELETE FROM hrd_absence_detail WHERE id_absence IN (SELECT id from hrd_absence WHERE id_employee = '" . $strIdEmployee . "' AND date_from = '" . $strDate . "' AND date_thru = '" . $strDate . "' AND note like '%alpha generated by system%') AND id_employee = '" . $strIdEmployee . "' AND absence_date = '" . $strDate . "';";
                    $res = $db->execute($strSQL);
                    $strSQL = "DELETE FROM hrd_absence WHERE id_employee = '" . $strIdEmployee . "' AND date_from = '" . $strDate . "' AND date_thru = '" . $strDate . "' AND note like '%alpha generated by system%'";
                    $res = $db->execute($strSQL);
                    if ($isExistAbsence2 == false) { //Jika ada Absence yang sudah di approove gak boleh insert attendance
                        $strSQL = "INSERT INTO hrd_attendance (id_employee, attendance_date, attendance_start,  attendance_finish, note) Values ('" . $strIdEmployee . "','" . $strDate . "'," . $strIn . "," . $strOut . ", 'attendance generated by import');";
                    }

                    }
            }
            //$strMessage .= "<br>" . $strSQL;
            $res = $db->execute($strSQL);
            if ($res) {
                $intssucces++;
                $strMessage .= " <font color=\"green\"><< Success >></font> <br/>";
            } else {
                $intGagal++;
                $strMessage .= " <font color=\"red\"><< failed >></font> <br/>";
            }
            //syncOvertimeApplication($db, $strDate, $strDate, $strIdEmployee, "");
            //syncShiftAttendance($db, $strDate, $strDate, " AND id_employee = $strIdEmployee ");
            //setAutoAlpha($db, $strDate, $strDate);
        }
    }
    $strMessage = "<h3>Data sucess Saved =" . $intssucces . " </br> Data Failed= " . $intGagal . "</h3>" . $strMessage;
    return $strMessage;
}

/*function setAutoAlpha($db, $strDateFrom, $strDateThru)
{
    $strCurDate = $strDateFrom;
    while (dateCompare($strCurDate, $strDateThru) <= 0) {
        $strSQL = "SELECT id FROM hrd_employee WHERE active=1 AND join_date <= '$strCurDate' AND (resign_date is null or resign_date >= '$strCurDate') AND      (is_immune_auto_alpha = 0 OR is_immune_auto_alpha IS NULL) AND
                      id NOT IN (SELECT id_employee FROM hrd_attendance WHERE attendance_date = '$strCurDate')
                      AND id NOT IN (SELECT id_employee FROM hrd_absence_detail WHERE absence_date = '$strCurDate')";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $intLastID = 0;
            $strIDEmployee = $rowDb['id'];
            $bolHoliday = true;
            $bolHoliday = isHoliday($strCurDate);
            if ($bolHoliday == false) {
                $strSQL2 = "INSERT INTO hrd_absence (id_employee, date_from, date_thru, absence_type_code, note, status) VALUES
                             ($strIDEmployee, '$strCurDate', '$strCurDate', 'A', 'alpha generated by system', 2)";
                $resDb2 = $db->execute($strSQL2);
                $strSQL3 = "SELECT max(id) as last_id FROM hrd_absence WHERE id_employee = " . $rowDb['id'] . " AND note = 'alpha generated by system' ";
                $resDb3 = $db->execute($strSQL3);
                while ($rowDb3 = $db->fetchrow($resDb3)) {
                    $intLastID = $rowDb3['last_id'];
                }
                $strSQL4 = "INSERT INTO hrd_absence_detail (id_absence, id_employee, absence_date, absence_type) VALUES
                              ($intLastID, $strIDEmployee, '$strCurDate', 'A')";
                $resDb4 = $db->execute($strSQL4);
            }
        }
        $strCurDate = getNextDate($strCurDate);
    }
}*/
function  isExistAttendance($strIdEmployee, $attendance_date)
{
    global $db;
    $bolExist = true;
    $strSQL = "SELECT id FROM hrd_attendance WHERE id_employee='" . $strIdEmployee . "' AND attendance_date ='" . $attendance_date . "';";
    $res = $db->execute($strSQL);
    $arrData = $db->fetchrow($res);
    $strID = $arrData['id'];
    if ($strID == "") {
        $bolExist = false;
    }
    return $bolExist;
}
function  isExistAbsence($strIdEmployee, $absence_date)
{
    global $db;
    $bolExist = true;
    $strSQL = "SELECT t1.id FROM hrd_absence_detail AS t1 LEFT JOIN hrd_absence as t2 ON t1.id_absence = t2.id WHERE t2.status >=2 AND t1.id_employee='" . $strIdEmployee . "' AND t1.absence_date ='" . $absence_date . "';";
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

// End FUNGSI ----------------------------------
// Fungsi untuk mendapatkan informasi id pada sistem dengan parameter nomer NIK
function getIdEmployeeByNIK($strNIK, $intIdCompany)
{
    global $db;
    if ($db->connect()) {
        $strSQL = "SELECT id From hrd_employee WHERE employee_id= '" . $strNIK . "' AND id_company = $intIdCompany LIMIT 1 ";
        $res = $db->execute($strSQL);
        $arrData = $db->fetchrow($res);
        $strID = $arrData['id'];
    }
    return $strID;
}// Fungsi untuk mendapatkan informasi id pada sistem dengan parameter nomer NIK
function getIdEmployeeByFingerid($fingerId, $intIdCompany)
{
    global $db;
    if ($db->connect()) {
        $strSQL = "SELECT id From hrd_employee WHERE barcode= '" . $fingerId . "' AND id_company = $intIdCompany ";
        $res = $db->execute($strSQL);
        $arrData = $db->fetchrow($res);
        $strID = $arrData['id'];
    }
    return $strID;
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
    //$strResult = $arrDateExplode[2]."-".$arrDateExplode[0]."-".$arrDateExplode[1]; // dari m/d/yyyy menjadi yyyy-mm-dd
    $strResult = $arrDateExplode[2] . "-" . $arrDateExplode[0] . "-" . $arrDateExplode[1]; // dari d/m/yyyy menjadi yyyy-mm-dd
    return $strResult;
    //return date('Y-m-d', strtotime($date));
}

function getEmployeeName($intIDEmployee)
{
    global $db;
    if ($db->connect()) {
        $strSQL = "SELECT employee_name FROM hrd_employee WHERE id = $intIDEmployee ";
        $res = $db->execute($strSQL);
        $arrData = $db->fetchrow($res);
        $strEmployeeName = $arrData['employee_name'];
    }
    return $strEmployeeName;
}

?>