<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
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
$strButtons = "";
$strInputImport = "";
$intTotalData = 0;
$strWordsEmployeeData = getWords("employee data");
$strMessages = "";
$strMsgClass = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
// fungsi untuk mengexport data
function exportData($db, $arrTableName, $strFileName)
{
    include("../global/class.excelExport.php");
    global $_REQUEST;
    foreach ($arrTableName as $strTableName => $strPK) {
        $strSQL = "SELECT * FROM \"$strTableName\"";
        $i = 0;
        $arrTempData = $db->fetchAll($strSQL);
        foreach ($arrTempData as $arrTempRow) {
            foreach ($arrTempRow as $strHeader => $arrContent) {
                if (!(is_numeric(substr($strHeader, 0, 1)))) {
                    $arrTableHeader[$strTableName][$i++] = ["text" => $strHeader, "width" => "", "type" => ""];
                }
            }
            break;
        }
        $i = 0;
        foreach ($arrTempData as $arrRow) {
            $j = 0;
            foreach ($arrRow as $strHeader => $strContent) {
                if (!(is_numeric($strHeader))) {
                    $arrTableContent[$strTableName][$i][$j++] = $strContent;
                }
            }
            $i++;
        }
    }
    writeLog(ACTIVITY_EXPORT, MODULE_PAYROLL, "$i data", 0);
    $objExl = new CxlsExport($strFileName . ".dat");
    $objExl->setData($arrTableHeader, $arrTableContent);
    $objExl->showExcel();
} //exportData
// fungsi untuk mengexport data
function importData($db, $flExcelImport)
{
    require_once('../global/excelReader/reader.php');
    // access the requested file
    if (is_uploaded_file($flExcelImport['tmp_name'])) {
        if (substr($flExcelImport['name'], strlen($flExcelImport['name']) - 3, 3) == "xls") {
            clearstatcache();
            if (!is_dir("temp")) {
                mkdir("temp", 0755);
            }
            $strNewFile = "temp/tempTable.xls";
            if (file_exists($strNewFile)) {
                unlink($strNewFile);
            }
            if (move_uploaded_file($flExcelImport['tmp_name'], $strNewFile)) {
                // prepare the variables
                $data = new Spreadsheet_Excel_Reader();
                // Set output Encoding.
                $data->setOutputEncoding('CP1251');
                $data->read($strNewFile);
                $intHeadRow = 3;
                $intIDCol = 2;
                $strSQL = "";
                $strSQL = "SELECT \"department_code\", \"department_name\" FROM \"hrd_department\"";
                $resDb = $db->execute($strSQL);
                while ($rowDb = $db->fetchrow($resDb)) {
                    $arrDepartment[$rowDb['department_name']] = $rowDb['department_code'];
                }
                $strSQL = "SELECT \"section_code\", \"section_name\" FROM \"hrd_section\"";
                $resDb = $db->execute($strSQL);
                while ($rowDb = $db->fetchrow($resDb)) {
                    $arrSection[$rowDb['section_name']] = $rowDb['section_code'];
                }
                $strSQL = "SELECT \"division_code\", \"division_name\" FROM \"hrd_division\"";
                $resDb = $db->execute($strSQL);
                while ($rowDb = $db->fetchrow($resDb)) {
                    $arrDivision[$rowDb['division_name']] = $rowDb['division_code'];
                }
                $strSQL = "SELECT \"position_code\", \"position_name\" FROM \"hrd_position\"";
                $resDb = $db->execute($strSQL);
                while ($rowDb = $db->fetchrow($resDb)) {
                    $arrPosition[$rowDb['position_name']] = $rowDb['position_code'];
                }
                $strSQL = "SELECT \"code\", \"name\" FROM \"hrd_functional\"";
                $resDb = $db->execute($strSQL);
                while ($rowDb = $db->fetchrow($resDb)) {
                    $arrFunction[$rowDb['name']] = $rowDb['code'];
                }
                // $strSQL = "SELECT \"code\", \"name\" FROM \"hrd_religion\"";
                // $resDb  = $db->execute($strSQL);
                // while ($rowDb = $db->fetchrow($resDb))
                // {
                // $arrReligion[$rowDb['name']] = $rowDb['code'];
                // }
                foreach ($data->sheets as $sheetIndex => $sheetArrays) {
                    $strTableName = "hrd_employee";
                    $arrCell = $sheetArrays['cells'];
                    $strSQL = "";
                    //data master employee
                    $strHeader = "\"employee_id\", \"employee_name\", \"gender\", \"birthday\", \"birthplace\", \"active\", \"join_date\", \"due_date\", \"permanent_date\", \"employee_status\", \"resign_date\", \"division_code\", \"department_code\", \"section_code\", \"position_code\", \"function\", \"family_status_code\", \"primary_address\"";
                    for ($intRow = 2; $intRow <= count($arrCell) + 1; $intRow++) {
                        if (!isset($arrCell[$intRow][$intIDCol]) || $arrCell[$intRow][$intIDCol] == "") {
                            break;
                        }
                        $strID = $arrCell[$intRow][$intIDCol];
                        if (isset($arrCell[$intRow][13]) && !isset($arrDivision[$arrCell[$intRow][13]])) {
                            $strSQL .= "INSERT INTO \"hrd_division\" (\"division_code\", \"division_name\") VALUES ('" . $arrCell[$intRow][13] . "', '" . $arrCell[$intRow][13] . "') ; ";
                            $arrDivision[$arrCell[$intRow][13]] = $arrCell[$intRow][13];
                        }
                        if (isset($arrCell[$intRow][14]) && !isset($arrDepartment[$arrCell[$intRow][14]])) {
                            $strSQL .= "INSERT INTO \"hrd_department\" (\"department_code\", \"department_name\", \"division_code\") VALUES ('" . $arrCell[$intRow][14] . "', '" . $arrCell[$intRow][14] . "', '" . $arrCell[$intRow][13] . "') ; ";
                            $arrDepartment[$arrCell[$intRow][14]] = $arrCell[$intRow][14];
                        }
                        if (isset($arrCell[$intRow][15]) && !isset($arrSection[$arrCell[$intRow][15]])) {
                            $strSQL .= "INSERT INTO \"hrd_section\" (\"section_code\", \"section_name\", \"department_code\", \"division_code\") VALUES ('" . $arrCell[$intRow][15] . "', '" . $arrCell[$intRow][15] . "' , '" . $arrCell[$intRow][14] . "', '" . $arrCell[$intRow][13] . "') ; ";
                            $arrSection[$arrCell[$intRow][15]] = $arrCell[$intRow][15];
                        }
                        if (isset($arrCell[$intRow][16]) && !isset($arrPosition[$arrCell[$intRow][16]])) {
                            if (strlen($arrCell[$intRow][16]) > 30) {
                                $temp = split(" ", $arrCell[$intRow][16]);
                                $temp2 = "";
                                foreach ($temp as $val) {
                                    $temp2 .= strtoupper(substr($val, 0, 1));
                                }
                            } else {
                                $temp2 = $arrCell[$intRow][16];
                            }
                            $strSQL .= "INSERT INTO \"hrd_position\" (\"position_code\", \"position_name\") VALUES ('" . $temp2 . "', '" . $arrCell[$intRow][16] . "') ; ";
                            $arrPosition[$arrCell[$intRow][16]] = $arrCell[$intRow][16];
                        }
                        if (isset($arrCell[$intRow][17]) && !isset($arrFunction[$arrCell[$intRow][17]])) {
                            $strSQL .= "INSERT INTO \"hrd_functional\" (\"code\", \"name\") VALUES ('" . $arrCell[$intRow][17] . "', '" . $arrCell[$intRow][17] . "') ; ";
                            $arrFunction[$arrCell[$intRow][17]] = $arrCell[$intRow][17];
                        }
                        $arrCell[$intRow][13] = (isset($arrCell[$intRow][13])) ? $arrDivision[$arrCell[$intRow][13]] : "";
                        $arrCell[$intRow][14] = (isset($arrCell[$intRow][14])) ? $arrDepartment[$arrCell[$intRow][14]] : "";
                        $arrCell[$intRow][15] = (isset($arrCell[$intRow][15])) ? $arrSection[$arrCell[$intRow][15]] : "";
                        $arrCell[$intRow][16] = (isset($arrCell[$intRow][16])) ? $arrPosition[$arrCell[$intRow][16]] : "";
                        $arrCell[$intRow][17] = (isset($arrCell[$intRow][17])) ? $arrFunction[$arrCell[$intRow][17]] : "";
                        for ($i = 2; $i < 20; $i++) {
                            //  if ($i == 7) echo $arrCell[$intRow][7]."|";
                            if (!isset($arrCell[$intRow][$i])) {
                                $arrCell[$intRow][$i] = "NULL";
                            }
                            if ($arrCell[$intRow][$i] == "" || strlen($arrCell[$intRow][$i]) == 0) {
                                $arrCell[$intRow][$i] = "NULL";
                            }
                        }
                        ksort($arrCell[$intRow]);
                        $arrCell[$intRow][4] = ($arrCell[$intRow][4] == 2) ? 0 : 1;
                        $arrCell[$intRow][7] = (strtolower($arrCell[$intRow][7]) == "active") ? 1 : 0;
                        $arrCell[$intRow][11] = ($arrCell[$intRow][11] == "P") ? 1 : 0;
                        //echo $arrCell[$intRow][4];
                        if ($arrCell[$intRow][5] != "NULL") {
                            $arrCell[$intRow][5] = excelSerialDateToString($arrCell[$intRow][5]);
                        }
                        if ($arrCell[$intRow][8] != "NULL") {
                            $arrCell[$intRow][8] = excelSerialDateToString($arrCell[$intRow][8]);
                        }
                        if ($arrCell[$intRow][9] != "NULL") {
                            $arrCell[$intRow][9] = excelSerialDateToString($arrCell[$intRow][9]);
                        }
                        if ($arrCell[$intRow][10] != "NULL") {
                            $arrCell[$intRow][10] = excelSerialDateToString($arrCell[$intRow][10]);
                        }
                        if ($arrCell[$intRow][12] != "NULL") {
                            $arrCell[$intRow][12] = excelSerialDateToString($arrCell[$intRow][12]);
                        }
                        $strContent = join("|, |", $arrCell[$intRow]);
                        $strContent = str_replace("'", "`", str_replace("|NULL|", "NULL", "|" . $strContent . "|"));
                        $strContent = str_replace("|", "'", $strContent);
                        $strSQL .= "DELETE FROM \"$strTableName\" WHERE \"employee_id\" = '$strID' ; ";
                        $strSQL .= "INSERT INTO \"$strTableName\" ($strHeader) VALUES($strContent) ; ";
                        $strSQL .= "UPDATE \"$strTableName\" SET created = now(), \"resign_date\" = null, \"due_date\" = null, \"permanent_date\" = null,";
                        $strSQL .= "modified_by = '" . $_SESSION['sessionUserID'] . "' WHERE \"employee_id\" = '$strID' ; ";
                    }
                    $resExec = $db->execute($strSQL);
                    /*
                                     $strSQL = "SELECT id, \"employee_id\", gender FROM \"hrd_employee\"";
                                     $resDb  = $db->execute($strSQL);
                                     while ($rowDb = $db->fetchrow($resDb))
                                     {
                                        $arrEmp[$rowDb['employee_id']] = $rowDb;
                                     }

                                     $arrFamily[22] = array("col" => 22, "relation" => "", "birthday" => 23);
                                     $arrFamily[24] = array("col" => 24, "relation" => "4", "birthday" => 25);
                                     $arrFamily[27] = array("col" => 27, "relation" => "4", "birthday" => 28);
                                     $arrFamily[30] = array("col" => 30, "relation" => "4", "birthday" => 31);
                                     $arrFamily[33] = array("col" => 33, "relation" => "1", "birthday" => "");
                                     $arrFamily[34] = array("col" => 34, "relation" => "0", "birthday" => "");
                                     $arrFamily[35] = array("col" => 35, "relation" => "6", "birthday" => "");
                                     $arrFamily[36] = array("col" => 36, "relation" => "6", "birthday" => "");

                                     $strSQL = "";
                                     for ($intRow = 11; $intRow <= count($arrCell)+1 ; $intRow++)
                                     {
                                        if (!isset($arrCell[$intRow][$intIDCol]) || $arrCell[$intRow][$intIDCol] == "") break;
                                        $strID = $arrEmp[$arrCell[$intRow][$intIDCol]]['id'];

                                        $intRelation = ($arrEmp[$arrCell[$intRow][$intIDCol]]['gender'] == 0) ? 3 : 2;
                                        $strSQL .= " DELETE FROM \"hrdEmployeeFamily\" WHERE \"idEmployee\" = '$strID'; ";

                                        foreach($arrFamily AS $strKey => $arrRow)
                                        {
                                           if (isset($arrCell[$intRow][$strKey]) && $arrCell[$intRow][$strKey] != "NULL")
                                           {
                                              $strName = str_replace("'", "`", $arrCell[$intRow][$strKey]);
                                              if ($strName == "NULL") echo $arrCell[$intRow][$strKey];

                                              if ($arrRow['relation'] == "") $arrRow['relation'] = $intRelation;

                                              $arrRow['birthday'] = ($arrRow['birthday'] != "" && isset($arrCell[$intRow][$arrRow['birthday']])) ?  $arrCell[$intRow][$arrRow['birthday']] : "NULL" ;

                                              if ($arrRow['birthday'] != "NULL") $arrRow['birthday'] = "'".excelSerialDateToString($arrRow['birthday'])."'";

                                              $strSQL .= " INSERT INTO \"hrdEmployeeFamily\" (\"idEmployee\", name, relation, birthday) VALUES('$strID', '".$strName."', '".$arrRow['relation']."', ".$arrRow['birthday']."); ";
                                           }
                                        }
                                     }
                                     $resExec = $db->execute($strSQL);
                                     return $resExec;*/
                }
            }
        } else {
            return false;
        }
    }
}//importData
function insertExim(&$strButton, &$strInputImport)
{
    $strButton .= " <input type=submit value=Export name=btnExport onClick=\"return promptExport();\">";
    $strInputImport .= "
      <td>
         <table align=\"left\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\">
           <tr>
               <td>&nbsp;</td>
           </tr>
            <tr valign=\"top\">
               <td width=50>&nbsp;File</td>
               <td>:</td>
               <td><input name=\"fileImport\" value=\"\" type=\"file\" id=\"filename2\" size=\"70\" ></td>
           </tr>
           <tr>
               <td>&nbsp;</td><td>&nbsp;</td>
               <td><input name=\"btnImport\" type=\"submit\" id=\"btnImport\" value=\"Import\"></td>
           </tr>
         </table>
       </td>
     ";
}//insertExim
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
    if (isset($_REQUEST['btnImport'])) {
        if ($bolCanEdit && $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
            if ($bolSave = importData($db, $_FILES["fileImport"])) {
                $strMsgClass = "class=bgOK";
                $strMessages = getWords("import succeed");
            } else {
                $strMsgClass = "class=bgError";
                $strMessages = getWords("import failed");
            }
        }
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    insertExim($strButtons, $strInputImport);
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>