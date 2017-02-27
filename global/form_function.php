<?php
/*
  Daftar fungsi-fungsi (super) global, yang terkait dengan proses2 formulir
  termasuk pengambilan nomor
    Author: Yudi K.
*/
include_once('../classes/hrd/hrd_division.php');
include_once('../classes/hrd/hrd_department.php');
include_once('../classes/hrd/hrd_section.php');
include_once('../classes/hrd/hrd_position.php');
include_once('../classes/hrd/hrd_functional.php');
$tblDivision = new cHrdDivision();
$arrDivision = $tblDivision->findAll("", "division_code, division_name", "", null, 1, "division_code");
$tblDepartment = new cHrdDepartment();
$arrDepartment = $tblDepartment->findAll("", "department_code, department_name", "", null, 1, "department_code");
$tblSection = new cHrdSection();
$arrSection = $tblSection->findAll("", "section_code, section_name", "", null, 1, "section_code");
$tblPosition = new cHrdPosition();
$arrPosition = $tblPosition->findAll("", "position_code, position_name", "", null, 1, "position_code");
$tblFunctional = new cHrdFunctional();
$arrFunctional = $tblFunctional->findAll("", "functional_code, functional_name", "", null, 1, "functional_code");
// fungsi untuk mengambil nomor surat berikut
function getFormCode($db, $strPrefix = "", $strSufix = "", $strTable)
{
    $strResult = "";
    $strSQL = "SELECT COUNT(id) as form_no FROM $strTable WHERE EXTRACT(MONTH FROM created::date) = EXTRACT(MONTH FROM now()::date)";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        $strResult = addPrevZero($rowDb['form_no'] + 1, 4);
    }
    return $strPrefix . $strResult . $strSufix;
}

// fungsi untuk menampilkan baris per baris dalam kolom dalam formulir disposisi / slip
function wrapRow(
    $strText1 = "",
    $strText2 = "",
    $strValue = "",
    $bolNumber = false,
    $bolComment = false,
    $bolNowrap = true
) {
    $strNowrap = ($bolNowrap) ? "nowrap" : "";
    $strColspan = ($bolComment) ? "colspan=3" : "";
    $strAttribute = ($bolNumber) ? "align=\"right\"" : "";
    $strResult = "  <tr><td " . $strColspan . "  $strNowrap valign='top'>$strText1&nbsp;</td>";
    $strResult .= (!$bolComment) ? "  <td  $strNowrap valign='top'>&nbsp;" . $strText2 . "</td>" : "";
    $strResult .= (!$bolComment) ? " <td  $strNowrap " . $strAttribute . ">&nbsp;" . $strValue . "</td></tr>" : "";
    return $strResult;
}

// fungsi untuk menampilkan baris per baris dalam kolom dalam formulir disposisi / slip (Tambah setting width)
function wrapRowSize(
    $strText1 = "",
    $strText2 = "",
    $strValue = "",
    $width = 1,
    $bolRight = false,
    $bolNumber = false,
    $bolComment = false
) {
    $strColspan = ($bolComment) ? "colspan=3" : "";
    $strAttribute = ($bolNumber) ? "align=\"right\"" : "";
    $strWidth = ($width != 1) ? "width = $width" : "";
    $strRight = ($bolRight) ? "align=\"right\"" : "";
    $strResult = "  <tr><td " . $strColspan . " nowrap. " . $strWidth . ">$strText1&nbsp;</td>";
    $strResult .= (!$bolComment) ? "  <td nowrap $strRight>&nbsp;" . $strText2 . "</td>" : "";
    $strResult .= (!$bolComment) ? " <td nowrap " . $strAttribute . ">&nbsp;" . $strValue . "</td></tr>" : "";
    return $strResult;
}

function formatNumeric($params)
{
    extract($params);
    return standardFormat($value);
}

function formatNumber($params)
{
    if (!is_numeric($params) && !is_string($params) && $params != "") {
        extract($params);
    } else {
        $value = $params;
    }
    return standardFormat($value);
}

function formatDate($params)
{
    if (!is_numeric($params) && !is_string($params) && $params != "") {
        extract($params);
    } else {
        $value = $params;
    }
    return pgDateFormat($value, "d-M-y");
}

function formatTime($params)
{
    if (!is_numeric($params) && !is_string($params) && $params != "") {
        extract($params);
    } else {
        $value = $params;
    }
    return minuteToTime($value, true);
}

function printActiveSymbol($params)
{
    global $myDataGrid;
    if (!is_string($params)) {
        extract($params);
    } else {
        $value = $params;
    }
    if ($myDataGrid->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
        return ($value == 't') ? "&radic;" : "";
    } else {
        return ($value == 't') ? "yes" : "";
    }
}

function printRequestStatus($params)
{
    global $ARRAY_REQUEST_STATUS;
    if (!is_string($params)) {
        extract($params);
    } else {
        $value = $params;
    }
    return (isset($ARRAY_REQUEST_STATUS[$value])) ? $ARRAY_REQUEST_STATUS[$value] : getWords("new");
}

function printUserName($params)
{
    global $db;
    if (!is_string($params) && $params != "") {
        extract($params);
    } else {
        $value = $params;
    }
    return ($value == "") ? "" : getUserName($db, $value);
}

function printCompanyName($params)
{
    global $db;
    if (!is_string($params)) {
        extract($params);
    } else {
        $value = $params;
    }
    $strSQL = "SELECT company_name FROM hrd_company ";
    if ($value != "") {
        $strSQL .= "WHERE id = '$value'";
    }
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        return $rowDb['company_name'];
    } else {
        return "";
    }
}

function printCompanyCode($params)
{
    global $db;
    if (!is_string($params) && !is_numeric($params)) {
        extract($params);
    } else {
        $value = $params;
    }
    if ($value != "") {
        $strSQL = "SELECT company_code FROM hrd_company ";
        $strSQL .= "WHERE id = '$value'";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            return strtoupper($rowDb['company_code']);
        }
    } else {
        return "";
    }
}

function getDivisionName($params)
{
    global $arrDivision;
    if (!is_string($params)) {
        extract($params);
    } else {
        $value = $params;
    }
    $strCode = $value;
    return (isset($arrDivision[$strCode])) ? $arrDivision[$strCode]['division_name'] : $strCode;
}

function getDepartmentName($params)
{
    global $arrDepartment;
    if (!is_string($params)) {
        extract($params);
    } else {
        $value = $params;
    }
    $strCode = $value;
    return (isset($arrDepartment[$strCode])) ? $arrDepartment[$strCode]['department_name'] : $strCode;
}

function getSectionName($params)
{
    global $arrSection;
    if (!is_string($params)) {
        extract($params);
    } else {
        $value = $params;
    }
    $strCode = $value;
    return (isset($arrSection[$strCode])) ? $arrSection[$strCode]['section_name'] : $strCode;
}

function getPositionName($params)
{
    global $arrPosition;
    if (!is_string($params)) {
        extract($params);
    } else {
        $value = $params;
    }
    $strCode = $value;
    return (isset($arrPosition[$strCode])) ? $arrPosition[$strCode]['position_name'] : $strCode;
}

function getFunctionalNameChen($params)
{
    global $arrFunctional;
    if (!is_string($params)) {
        extract($params);
    } else {
        $value = $params;
    }
    $strCode = $value;
    return (isset($arrFunctional[$strCode])) ? $arrFunctional[$strCode]['functional_name'] : $strCode;
}

function getBranchName($params)
{
    global $arrBranch;
    if (!is_string($params)) {
        extract($params);
    } else {
        $value = $params;
    }
    $strCode = $value;
    return (isset($arrBranch[$strCode])) ? $arrBeanch[$strCode]['branch_name'] : $strCode;
}

function printEmployeeStatus($params)
{
    extract($params);
    global $ARRAY_EMPLOYEE_STATUS;
    return getWords($ARRAY_EMPLOYEE_STATUS[$value]);
}

function printSalaryCurrency($params)
{
    extract($params);
    global $ARRAY_CURRENCY;
    return ($value == "") ? "" : $ARRAY_CURRENCY[$value];
}

function printGlobalEditLink($params)
{
    extract($params);
    revertStatus($record['status']);
    if (!isset($record['status'])) {
        $record['status'] = REQUEST_STATUS_NEW;
    }
    if (!isset($record['baseFilename'])) {
        $baseFilename = getEditFile(basename($_SERVER['PHP_SELF']));
    } else {
        $baseFilename = $record['baseFilename'];
    }
    return ($record['status'] != REQUEST_STATUS_NEW && $record['status'] != REQUEST_STATUS_CHECKED) ? "<font color='lightgrey'><strike>edit</strike></font>" : "<a href=\"" . $baseFilename . "?dataID=" . $record['id'] . "\">" . getWords(
            'edit'
        ) . "</a>";
}

function printGlobalEditLinkOT($params)
{
    extract($params);
    if (!isset($record['status'])) {
        $record['status'] = REQUEST_STATUS_NEW;
    }
    return "<a href=\"" . getEditFile(basename($_SERVER['PHP_SELF'])) . "?dataID=" . $record['id'] . "\">" . getWords(
        'edit'
    ) . "</a>";
}

//Mengembalikan status dari kata2 menjadi angka
function revertStatus(&$statusChar)
{
    switch (strtoupper($statusChar)) {
        case "NEW":
            $statusChar = REQUEST_STATUS_NEW;
            break;
        case "CHECKED":
            $statusChar = REQUEST_STATUS_CHECKED;
            break;
        case "APPROVED":
            $statusChar = REQUEST_STATUS_APPROVED;
            break;
        case "DENIED":
            $statusChar = REQUEST_STATUS_DENIED;
            break;
        case "ACKNOWLEDGED":
            $statusChar = REQUEST_STATUS_ACKNOWLEDGED;
            break;
        default:
            break;
    }
}

?>
