<?php
session_start();
include_once('global.php');
include_once('../global/excelReader/reader.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
    header("location:login.php?dataPage=data_department_import.php");
    exit();
}
$bolCanView = getUserPermission("data_department_import.php", $bolCanEdit, $bolCanDelete, $strError);
$strTemplateFile = getTemplate("data_department_import.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
//fungsi untuk memproses import data salary grade dari file excel
function importData($db, &$intTotal)
{
    global $HTTP_POST_FILES;
    global $_SESSION;
    global $messages;
    $strError = "";
    $strmodified_byID = $_SESSION['sessionUserID'];
    $intTotal = 0;
    if (is_uploaded_file($HTTP_POST_FILES["dataFile"]['tmp_name'])) {
        //-- baca file Excel
        $data = new Spreadsheet_Excel_Reader();
        $data->setOutputEncoding('CP1251');
        $data->read($HTTP_POST_FILES["dataFile"]['tmp_name']);
        $intCols = 20; // default ada segini
        $intRows = $data->sheets[0]['numRows']; // total baris
        //$i = 0;
        $ok = 0;
        $strDivision = "";
        $strDepartment = "";
        $strSection = "";
        $strSubSection = "";
        for ($i = 3; $i <= $intRows; $i++) {
            // handle satu persatu
            (isset($data->sheets[0]['cells'][$i][1])) ? $strDivCode = trim(
                $data->sheets[0]['cells'][$i][1]
            ) : $strDivCode = "";
            (isset($data->sheets[0]['cells'][$i][2])) ? $strDivName = trim(
                $data->sheets[0]['cells'][$i][2]
            ) : $strDivName = "";
            (isset($data->sheets[0]['cells'][$i][3])) ? $strDeptCode = trim(
                $data->sheets[0]['cells'][$i][3]
            ) : $strDeptCode = "";
            (isset($data->sheets[0]['cells'][$i][4])) ? $strDeptName = trim(
                $data->sheets[0]['cells'][$i][4]
            ) : $strDeptName = "";
            (isset($data->sheets[0]['cells'][$i][5])) ? $strSectCode = trim(
                $data->sheets[0]['cells'][$i][5]
            ) : $strSectCode = "";
            (isset($data->sheets[0]['cells'][$i][6])) ? $strSectName = trim(
                $data->sheets[0]['cells'][$i][6]
            ) : $strSectName = "";
            (isset($data->sheets[0]['cells'][$i][7])) ? $strSubCode = trim(
                $data->sheets[0]['cells'][$i][7]
            ) : $strSubCode = "";
            (isset($data->sheets[0]['cells'][$i][8])) ? $strSubName = trim(
                $data->sheets[0]['cells'][$i][8]
            ) : $strSubName = "";
            if ($strDivCode != "") {
                $strDivision = $strDivCode;
            }
            if ($strDeptCode != "") {
                $strDepartment = $strDeptCode;
            }
            if ($strSectCode != "") {
                $strSection = $strSectCode;
            }
            if ($strSubCode != "") {
                $strSubSection = $strSubCode;
            }
            // simpan data satu-satu, dari divisi sampai subsection
            // data divisi
            if ($strDivCode != "") {
                $strSQL = "INSERT INTO hrd_division (created,created_by,modified_by, ";
                $strSQL .= "division_code, division_name) ";
                $strSQL .= "VALUES(now(),'$strmodified_byID','$strmodified_byID', ";
                $strSQL .= "'$strDivCode','$strDivName') ";
                $resExec = $db->execute($strSQL);
            }
            // data department
            if ($strDeptCode != "") {
                $strSQL = "INSERT INTO hrd_department (created,created_by,modified_by, ";
                $strSQL .= "division_code, department_code, department_name) ";
                $strSQL .= "VALUES(now(),'$strmodified_byID','$strmodified_byID', ";
                $strSQL .= "'$strDivision','$strDeptCode', '$strDeptName') ";
                $resExec = $db->execute($strSQL);
            }
            // data section
            if ($strSectCode != "") {
                $strSQL = "INSERT INTO hrd_section (created,created_by,modified_by, ";
                $strSQL .= "division_code, department_code, section_code, section_name) ";
                $strSQL .= "VALUES(now(),'$strmodified_byID','$strmodified_byID', ";
                $strSQL .= "'$strDivision','$strDepartment', '$strSectCode', '$strSectName') ";
                $resExec = $db->execute($strSQL);
            }
            // data section
            if ($strSubCode != "") {
                $strSQL = "INSERT INTO hrd_sub_section (created,created_by,modified_by, ";
                $strSQL .= "division_code, department_code, section_code, ";
                $strSQL .= "sub_section_code, sub_section_name) ";
                $strSQL .= "VALUES(now(),'$strmodified_byID','$strmodified_byID', ";
                $strSQL .= "'$strDivision','$strDepartment','$strSection', ";
                $strSQL .= "'$strSubCode', '$strSubName') ";
                $resExec = $db->execute($strSQL);
            }
        }
        $strResult = $messages['data_saved'] . " " . $ok . "/" . $i;
        //$strResult .= " <br>".$strError;
        if ($intRows > 0) {
            writeLog(ACTIVITY_IMPORT, MODULE_PAYROLL, "$intRows data", 0);
        }
        return true;
    } else {
        return false;
    }
} //importData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    if ($bolCanEdit) {
        if (isset($_REQUEST['btnImport'])) {
            $intTotal = 0;
            if (importData($db, $intTotal)) {
                // sukses, langsung redirect ke halaman department
                header("location:data_department.php");
                exit();
            } else {
            }
        }
    }
}
$strInitAction .= "
  ";
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>