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
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintStatus']) || isset($_REQUEST['btnPrintDepartment']) || isset($_REQUEST['btnPrintPosition']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strWordsDataEntry = getWords("data entry");
$strWordsDateFrom = getWords("date from");
$strWordsDateThru = getWords("date thru");
$strWordsOtType = getWords("overtime type");
$strWordsEmployeeID = getWords("employee ID");
$strWordsCompany = getWords("company");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSection = getWords("section");
$strWordsSubsection = getWords("sub section");
$strWordsStatus = getWords("status");
$strWordsListEmpLoan = getWords("list of employee overtime terapis");
$strWordsName = getWords("name");
$strWordsPosition = getWords("position");
$strWordsOvertimeDate = getWords("overtime date");
$strWordsType = getWords("type");
$strWordsAmount = getWords("amount");
$strWordsQty = getWords("qty");
$strWordsTotAmount = getWords("total amount");
$strWordsNote = getWords("note");
$strWordsExcel = getWords("excel");
$strWordsShowData = getWords("show data");
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $bolPrint;
    global $words;
    global $ARRAY_EMPLOYEE_STATUS;
    $dtToday = getdate();
    $intRows = 0;
    $strResult = "";
    // ambil dulu data employee, kumpulkan dalam array
    $arrEmployee = [];
    $intSalaryDate = getSetting("salary_date");
    if (!is_numeric($intSalaryDate)) {
        $intSalaryDate = 25;
    } // default
    $i = 0;
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name,t2.position_code,";
    $strSQL .= "t3.ot_type, t3.amount, (t3.amount * t1.qty) AS tot_amount ";
    $strSQL .= "FROM hrd_overtime_terapis_employee AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "LEFT JOIN hrd_overtime_terapis AS t3 ON t1.ot_code = t3.ot_code ";
    $strSQL .= "WHERE overtime_date <= '$strDataDateThru'
                AND overtime_date >= '$strDataDateFrom' ";
    $strSQL .= $strKriteria;
    $strSQL .= "ORDER BY $strOrder t2.employee_name, t1.overtime_date ;";
    $resDb = $db->execute($strSQL);
    $strDateOld = "";
    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= "<tr valign=top>\n";
        if ($bolPrint) {
            $strResult .= "  <td>&nbsp;</td>\n";
        } else {
            $strResult .= "  <td><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\"></label></td>\n";
        }
        $strResult .= "  <td>" . $rowDb['employee_id'] . "</td>";
        $strResult .= "  <td>" . $rowDb['employee_name'] . "</td>";
        $strResult .= "  <td>" . $rowDb['position_code'] . "</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['overtime_date'], "d-M-y") . "</td>";
        $strResult .= "  <td align=center>" . $rowDb['ot_code'] . "</td>";
        // $strResult .= "  <td align=right>" .standardFormat($rowDb['amount']). "</td>";
        $strResult .= "  <td align=right>" . number_format($rowDb['amount'], 2, '.', ',') . "</td>";
        $strResult .= "  <td align=right>" . $rowDb['qty'] . "</td>";
        $strResult .= "  <td align=right>" . number_format(
                $rowDb['tot_amount'],
                2,
                '.',
                ','
            ) . "</td>";//sanusi 2015-01-29 agar ketika export excel bisa di sum
        $strResult .= "  <td>" . $rowDb['note'] . "</td>";
        $strResult .= "  <td>" . $rowDb['status'] . "</td>";
        $strResult .= "  <td align=center><a href=\"overtime_terapis_edit.php?dataID=" . $rowDb['id'] . "\">" . $words['edit'] . "</a></td>";
        $strResult .= "</tr>\n";
    }
    if ($intRows > 0) {
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$intRows data", 0);
    }
    return $strResult;
} // showData
function printAct($a)
{
    if ($a == 1) {
        return "&radic;";
    } else {
        return "";
    }
}

// fungsi untuk menghapus data
function deleteData($db)
{
    global $_REQUEST;
    $i = 0;
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 5) == 'chkID') {
            $strSQL = "DELETE FROM hrd_loan WHERE id = '$strValue' ";
            $resExec = $db->execute($strSQL);
            $i++;
        }
    }
    if ($i > 0) {
        writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$i data ", 0);
    }
} //deleteData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$intDefaultStart = "07:30";
$intDefaultFinish = "16:30";
$db = new CdbClass;
if ($db->connect()) {
    GetUserEmployeeInfo($db);
    // hapus data jika ada perintah
    if (isset($_REQUEST['btnDelete'])) {
        if ($bolCanDelete) {
            deleteData($db);
        }
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    (isset($_REQUEST['dataDateFrom'])) ? $strDataDateFrom = $_REQUEST['dataDateFrom'] : $strDataDateFrom = date(
        "Y-m-d"
    );
    (isset($_REQUEST['dataDateThru'])) ? $strDataDateThru = $_REQUEST['dataDateThru'] : $strDataDateThru = date(
        "Y-m-d"
    );
    (isset($_REQUEST['dataDivision'])) ? $strDataDivision = $_REQUEST['dataDivision'] : $strDataDivision = "";
    (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
    (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
    (isset($_REQUEST['dataSubsection'])) ? $strDataSubsection = $_REQUEST['dataSubsection'] : $strDataSubsection = "";
    (isset($_REQUEST['dataActive'])) ? $strDataActive = $_REQUEST['dataActive'] : $strDataActive = 1;
    (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
    (isset($_REQUEST['dataOtTerapisType'])) ? $strDataOtTerapisType = $_REQUEST['dataOtTerapisType'] : $strDataOtTerapisType = "";
    scopeCBDataEntry($strDataEmployee, $_SESSION['sessionUserRole'], $arrUserInfo);
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    $strKriteria = "";
    if ($strDataDivision != "") {
        $strKriteria .= "AND t2.division_code = '$strDataDivision' ";
    }
    if ($strDataLoanType != "") {
        $strKriteria .= "AND \"type\" = '$strDataLoanType' ";
    }
    if ($strDataDepartment != "") {
        $strKriteria .= "AND t2.department_code = '$strDataDepartment' ";
    }
    if ($strDataSection != "") {
        $strKriteria .= "AND t2.section_code = '$strDataSection' ";
    }
    if ($strDataSubsection != "") {
        $strKriteria .= "AND t2.sub_section_code = '$strDataSubsection' ";
    }
    /* if ($strDataActive != "") {
         $strKriteria .= "AND t2.active = '$strDataActive' ";
     }*/
    if ($strDataEmployee != "") {
        $strKriteria .= "AND t2.employee_id = '$strDataEmployee' ";
    }
    $strKriteria .= $strKriteriaCompany;
    if ($bolCanView) {
        if (validStandardDate($strDataDateFrom) && validStandardDate($strDataDateThru)) {
            // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
            $strDataDetail = getData($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
        } else {
            $strDataDetail = "";
        }
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
    if ($bolCanView) {
        //if (isset($_REQUEST['btnExcel'])) $bolLimit = false;
        //$strDataDetail = getData($db,$intTotalData, $strKriteria, $intCurrPage, $bolLimit);
        if (isset($_REQUEST['btnExcel'])) {
            $strDataDetail = getData($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
            // ambil data CSS-nya
            if (file_exists("bw.css")) {
                $strStyle = "bw.css";
            }
            $strPrintCss = "";
            $strPrintInit = "";
            headeringExcel("loan.xls");
        }
    }
    // generate data hidden input dan element form input
    $intDefaultWidthPx = 200;
    $strInputDateFrom = "<input class=\"form-control datepicker\" data-date-format=\"yyyy-mm-dd\" type=text name=dataDateFrom id=dataDateFrom size=15 maxlength=10 value=\"$strDataDateFrom\">";
    $strInputDateThru = "<input class=\"form-control datepicker\" data-date-format=\"yyyy-mm-dd\"  type=text name=dataDateThru id=dataDateThru size=15 maxlength=10 value=\"$strDataDateThru\">";
    $strInputEmployee = "<input class=\"form-control\" type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"$strDataEmployee\" $strNonCbReadonly>";
    $strInputDivision = getDivisionList(
        $db,
        "dataDivision",
        $strDataDivision,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputDepartment = getDepartmentList(
        $db,
        "dataDepartment",
        $strDataDepartment,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputSection = getSectionList(
        $db,
        "dataSection",
        $strDataSection,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputSubsection = getSubSectionList(
        $db,
        "dataSubsection",
        $strDataSubsection,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
    $strInputOtType = getOtTerapisList(
        $db,
        "dataOtTerapisType",
        $strDataOtTerapisType,
        $strEmptyOption,
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
    //handle user company-access-right
    $strInputCompany = getCompanyList(
        $db,
        "dataCompany",
        $strDataCompany,
        $strEmptyOption2,
        $strKriteria2,
        "style=\"width:$intDefaultWidthPx\""
    );
    // informasi tanggal kehadiran
    if ($strDataDateFrom == $strDataDateThru) {
        $strInfo .= "&nbsp;&nbsp;" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
    } else {
        $strInfo .= "&nbsp;&nbsp;" . strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
        $strInfo .= " >> " . strtoupper(pgDateFormat($strDataDateThru, "d-M-Y"));
    }
    $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDataDateFrom\">";
    $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDataDateThru\">";
    $strHidden .= "<input type=hidden name=dataDivision value=\"$strDataDivision\">";
    $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
    $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
    $strHidden .= "<input type=hidden name=dataSubsection value=\"$strDataSubsection\">";
    $strHidden .= "<input type=hidden name=dataActive value=\"$strDataActive\">";
    $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
    $strHidden .= "<input type=hidden name=dataLoanType value=\"$strDataLoanType\">";
}
$strButtonList = generateRoleButtons($bolCanEdit, $bolCanDelete, $bolCanCheck, $bolCanApprove, true);
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('loan management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = otTerapisSubMenu($strWordsOtTerapisList);
if ($bolPrint) {
    $strMainTemplate = getTemplate(str_replace(".php", "_print.html", basename($_SERVER['PHP_SELF'])));
} else {
    $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}    //------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
