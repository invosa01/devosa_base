<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_absence_type.php');
include_once('../classes/hrd/hrd_absence.php');
include_once('../classes/hrd/hrd_absence_detail.php');
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
$strWordsEntryAbsence = getWords("absence entry");
$strWordsAbsenceList = getWords("absence list");
$strWordsEntryPartialAbsence = getWords("partial absence entry");
$strWordsPartialAbsenceList = getWords("partial absence list");
$strWordsAnnualLeave = getWords("annual leave");
$strWordsAbsenceSlip = getWords("absence slip");
$strConfirmSave = getWords("save ?");
$DataGrid = "";
$formFilter = "";
//tambahan
//INISIALISASI------------------------------------------------------------------------------------------------------------------
//ambil semua jenis Absence
$tblAbsenceType = new cHrdAbsenceType();
//$arrAbsenceType = $tblAbsenceType->findAll("", "id, absence_type_code, absence_type_name", "", null, 1, "id");
$arrAbsenceType = $tblAbsenceType->findAll("", "code, Note", "", null, 1, "id");
//exit();
//ambil semua jenis trip cost untuk setiap currency
// $tblTripCostType = new cHrdTripCostType();
//foreach ($ARRAY_CURRENCY as $strCurrencyNo => $strCurrencyCode)
//{
//$arrTripCostType[$strCurrencyCode] = $tblTripCostType->findAll("currency = '$strCurrencyCode'", "id, trip_cost_type_name, currency", //"trip_cost_type_name", null, 1, "id");
//DAFTAR FUNGSI--------------------------------------------------------------------------------------------------------------
function getData($db)
{
    global $dataPrivilege;
    global $bolCanEdit;
    global $bolCanDelete;
    global $bolCanCheck;
    global $bolCanApprove;
    global $f;
    global $myDataGrid;
    global $DataGrid;
    global $strKriteriaCompany;
    //global $arrUserInfo;
    $arrData = $f->getObjectValues();
    $strKriteria = "";
    // GENERATE CRITERIA
    if ($arrData['dataAbsence'] != "") {
        $strKriteria .= "AND absence_type_code = '" . $arrData['dataAbsence'] . "'";
    }
    if (validStandardDate($strDateFrom = $arrData['dataDateFrom']) && validStandardDate(
            $strDateThru = $arrData['dataDateThru']
        )
    ) {
        $strKriteria .= "AND ((date_from, date_thru) ";
        $strKriteria .= "    OVERLAPS (DATE '$strDateFrom', DATE '$strDateThru') ";
        $strKriteria .= "    OR (date_thru = DATE '$strDateFrom') ";
        $strKriteria .= "    OR (date_thru = DATE '$strDateThru')) ";
    }
    if ($arrData['dataEmployee'] != "") {
        $strKriteria .= "AND employee_id = '" . $arrData['dataEmployee'] . "'";
    }
    if ($arrData['dataPosition'] != "") {
        $strKriteria .= "AND position_code = '" . $arrData['dataPosition'] . "'";
    }
    if ($arrData['dataBranch'] != "") {
        $strKriteria .= "AND branch_code = '" . $arrData['dataBranch'] . "'";
    }
    if ($arrData['dataGrade'] != "") {
        $strKriteria .= "AND grade_code = '" . $arrData['dataGrade'] . "'";
    }
    if ($arrData['dataEmployeeStatus'] != "") {
        $strKriteria .= "AND employee_status = '" . $arrData['dataEmployeeStatus'] . "'";
    }
    if ($arrData['dataActive'] != "") {
        $strKriteria .= "AND active = '" . $arrData['dataActive'] . "'";
    }
    if ($arrData['dataDeductLeave'] != "" && $arrData['dataDeductLeave'] != "undefined") {
        $strKriteria .= "AND deduct_leave = '" . $arrData['dataDeductLeave'] . "'";
    }
    if ($arrData['dataRequestStatus'] != "") {
        $strKriteria .= "AND status = '" . $arrData['dataRequestStatus'] . "'";
    }
    if ($arrData['dataDivision'] != "") {
        $strKriteria .= "AND division_code = '" . $arrData['dataDivision'] . "'";
    }
    if ($arrData['dataDepartment'] != "") {
        $strKriteria .= "AND department_code = '" . $arrData['dataDepartment'] . "'";
    }
    if ($arrData['dataSection'] != "") {
        $strKriteria .= "AND section_code = '" . $arrData['dataSection'] . "'";
    }
    /* if ($arrData['dataSubSection']!= "") {
       $strKriteria .= "AND sub_section_code = '".$arrData['dataSubSection']."'";
     } */
    $strKriteria .= $strKriteriaCompany;
    if ($db->connect()) {
        $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
        $myDataGrid->caption = getWords(
            strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
        );
        $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
        $myDataGrid->setCriteria($strKriteria);
        $myDataGrid->pageSortBy = "date_from,employee_name";
        $myDataGrid->addColumnCheckbox(
            new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => '']),
            false /*bolDisableSelfStatusChange*/
        );
        $myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no."), "", ['width' => '30'], ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("created"), "created_date", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("date from"), "date_from", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("date thru"), "date_thru", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("employee id"), "employee_id", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("employee name"), "employee_name", "", ['nowrap' => '']));
        //$myDataGrid->addColumn(new DataGrid_Column(getWords("department"), "department_code",  "", array('nowrap' => ''), false, false, "","getDepartmentName()"));
        // $myDataGrid->addColumn(new DataGrid_Column(getWords("purpose"), "purpose", "", array('nowrap' => '')));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", "", ['nowrap' => '']));
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("is leave"),
                "deduct_leave",
                "",
                ['nowrap' => ''],
                false,
                false,
                "",
                "printActiveSymbol()"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("absence type"), "absence_type_code", "", ['nowrap' => ''])
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("duration"), "duration", "", ['nowrap' => ''], false, false, "", "")
        );
        //$myDataGrid->addColumn(new DataGrid_Column(getWords("remain"), "remain",  "", array('nowrap' => ''), false, false, "",""));
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("status"),
                "status",
                "",
                ['nowrap' => ''],
                false,
                false,
                "",
                "printRequestStatus()"
            )
        );
        //by kamal
        $myDataGrid->addSpecialButton(
            "btnSlip",
            "btnSlip",
            "submit",
            getWords("get slip"),
            "onClick=\"document.formData.target = '_blank'\"",
            "getSlip()"
        );
        // if ($dataPrivilege['edit'] == 't')
        // edit by LMD
        //  $myDataGrid->addColumn(new DataGrid_Column("", "", array("width" => "60"), array('align' => 'center', 'nowrap' => ''), false, false, //"","printEditLink()", "", false /*show in excel*/));
        //dari sini get slip by kamal
        foreach ($arrData AS $key => $value) {
            $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
        }
        //$myDataGrid->addSpecialButton("btnSlip", "btnSlip", "submit", getWords("get slip"), "onClick=\"document.formData.target = '_blank'\"", "getSlip()");
        //tampilkan buttons sesuai dengan otoritas, common_function.php
        //generateRoleButtons($bolCanEdit, $bolCanDelete, $bolCanCheck, $bolCanApprove, false, true, $myDataGrid);
        // $myDataGrid->addButtonExportExcel("Export Excel", $dataPrivilege['menu_name'].".xls", getWords($dataPrivilege['menu_name']));
        $myDataGrid->getRequest();
        if ($myDataGrid->sortName == "division_name") {
            $myDataGrid->sortName = "division_name,department_name";
        }
        $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_absence AS t1 LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id ";
        $strSQLCOUNT .= "LEFT JOIN hrd_absence_type AS t3 ON t1.absence_type_code = t3.code  ";
        $strSQL = "select * from (SELECT t1.*, t1.created::date as created_date, t3.deduct_leave, t3.leave_weight, t2.id AS id_employee, t2.employee_id, t2.employee_name, t2.id_company, t2.active, t2.employee_status, t2.grade_code, t2.branch_code, ";
        $strSQL .= "t2.position_code, t2.division_code, t2.department_code, t2.section_code, t2.sub_section_code ";
        $strSQL .= "FROM hrd_absence AS t1 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
        $strSQL .= "LEFT JOIN hrd_absence_type AS t3 ON t1.absence_type_code = t3.code) as t  ";
        $strSQL .= "WHERE 1=1 $strKriteria";
        //echo($strSQL);
        $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
        $dataset = $myDataGrid->getData($db, $strSQL);
        //bind Datagrid with array dataset and branchCode
        $myDataGrid->bind($dataset);
        $DataGrid = $myDataGrid->render();
    } else {
        $DataGrid = "";
    }
    return $DataGrid;
}

//function printQuota($params)
// {
// global $arrTripCostType;
//extract($params);
//$strCostID = substr($field, 10);
//return  generateInput("detailQuota_".$record['grade_code']."_".$strCostID, $value);
//}
// fungsi untuk menyimpan data
function getSlip()
{
    global $ARRAY_CURRENCY;
    global $myDataGrid;
    global $db;
    global $arrAbsenceType;
    global $strDataAbsenceTypeID;
    global $strSlipContent;
    global $strSlipTopic;
    global $strSlipDate;
    global $strSlipContent1a, $stremployeeName, $strSlipContent1b, $strSlipContent1c, $strSlipContent2a, $strSlipContent2b, $strSlipContent1d, $strSlipContent2c, $strSlipContent3, $strSlipContent4a, $strSlipContent4b, $strSlipContent5a, $strSlipContent5b;
    $tblAbsence = new cHrdAbsence();
    $tblAbsenceDetail = new cHrdAbsenceDetail();
    echo "
            <html>
            <head>
            <title>Formulir Disposisi</title>
            <meta http-equiv='Content-Type' content='application/vnd.ms-word; charset=iso-8859-1'>
            <meta http-equiv='Content-Disposition' content='attachment; charset=iso-8859-1'>
            <link href='../css/invosa.css' rel='stylesheet' type='text/css'>
            </head>
            <body  marginheight=0 marginwidth=0 leftmargin=10  topmargin=0 >
         ";
    // inisialisasi
    $strThisPage = "<span>&nbsp;<br></span>";
    $strNewPage = "<span style=\"page-break-before:always;\"></span>";
    //$strSlipTopic = $arrAbsencetype[$strDataAbsencetype]['absence_type_code'];
    //$strSlipTopic = $arrAbsenceType[$strDataAbsenceTypeID]['absence_type_name']; 
    $strSlipDate = date("d M Y");
    $bolEven = true; // apakah genap (untuk 2 slip per halaman)
    //$bolEven = false; 
    $i = 0;
    foreach ($myDataGrid->checkboxes as $strDataID) {
        $strSlipContent1a = $strSlipContent1b = $strSlipContent1c = $strSlipContent2a = $strSlipContent2b = $strSlipContent2c = $strSlipContent1d = $strSlipContent3 = $strSlipContent4a = $strSlipContent4b = $strSlipContent5a = $strSlipContent5b = "";
        //jalankan jika 1 halaman untuk 2 slip
        //$bolEven = !$bolEven;
        $i++;
        //assign header
        $dataAbsence = $tblAbsence->findAll("id = '$strDataID'", "id_employee", "", null, 1);
        //$strFormCode = $dataTrip[0]['form_code'];
        $dataAbsence1 = $tblAbsence->findAll(
            "id = '$strDataID'",
            "date_from || ' until ' || date_thru AS absence_date",
            "",
            null,
            1
        );
        $dataAbsence3 = $tblAbsence->findAll("id = '$strDataID'", "absence_type_code As absence ", "", null, 1);
        $dataAbsence2 = $tblAbsence->findAll("id = '$strDataID'", "Note", "", null, 1);
        //ambil data approval => ambil nama karyawan
        $dataApproval1 = $tblAbsence->findAll(
            "id = '$strDataID'",
            "created_by, created, now()::timestamp without time zone as printed_time, '" . $_SESSION['sessionUserID'] . "' as printed_by",
            "",
            null,
            1
        );
        if (count($dataApproval1) > 0) {
            $dataApproval1[0]['created_by'] = ($dataApproval1[0]['created_by'] != "") ? getUserName(
                    $db,
                    $dataApproval1[0]['created_by']
                ) . " (" . substr($dataApproval1[0]['created'], 0, 16) . ")" : "";
            $dataApproval1[0]['printed_by'] = ($dataApproval1[0]['printed_by'] != "") ? getUserName(
                    $db,
                    $dataApproval1[0]['printed_by']
                ) . " (" . substr($dataApproval1[0]['printed_time'], 0, 16) . ")" : "";
            $dataApproval1[0] = array_remove_key($dataApproval1[0], "created", "printed_time");
        }
        //ambil data approval => ambil nama karyawan
        $dataApproval2 = $tblAbsence->findAll(
            "id = '$strDataID'",
            "now()::timestamp without time zone as printed_time, '" . $_SESSION['sessionUserID'] . "' as printed_by",
            "",
            null,
            1
        );
        $dataApproval2 = $tblAbsence->findAll(
            "id = '$strDataID'",
            "checked_by, checked_time, approved_by, approved_time",
            "",
            null,
            1
        );
        if (count($dataApproval2) > 0) {
            $dataApproval2[0]['checked_by'] = ($dataApproval2[0]['checked_by'] != "") ? getUserName(
                    $db,
                    $dataApproval2[0]['checked_by']
                ) . " (" . substr($dataApproval2[0]['checked_time'], 0, 16) . ")" : "";
            $dataApproval2[0]['approved_by'] = ($dataApproval2[0]['approved_by'] != "") ? getUserName(
                    $db,
                    $dataApproval2[0]['approved_by']
                ) . " (" . substr($dataApproval2[0]['approved_time'], 0, 16) . ")" : "";
            $dataApproval2[0] = array_remove_key($dataApproval2[0], "checked_time", "approved_time");
            //print_r($dataApproval[0]);
        }
        // ambil ID employee
        $strIDEmployee = $dataAbsence[0]['id_employee'];
        //$dataEmployee1 = getEmployeeInfoByID($db, $strIDEmployee, "employee_id	||' - '|| employee_name as employee, grade_code as grade, division_name as division");
        $dataEmployee1 = getEmployeeInformation($strDataID);
        $dataEmployee2 = getEmployeeInfoByID($db, $strIDEmployee, "division_name as division, branch_code as branch");
        // print_r($dataEmployee2);
        $dataEmployee3 = getEmployeeInfoByID($db, $strIDEmployee, "employee_name as employee");
        //adnan
        foreach ($dataEmployee1 AS $strField => $strValue) {
            $strSlipContent1a .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "80", true);
        }
        // 25/11/2012 by LMD
        /*foreach ($dataEmployee1 AS $strField)
        {
           $m = $strField;

        foreach ($m AS $strField => $strValue)
        {
           $strSlipContent1a .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "80", true);
        }

        }*/
        //$strSlipContent1a .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "80", true);
        // foreach ($dataEmployee2 AS $strField => $strValue)
        // $strSlipContent1b .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "80", true);
        //
        foreach ($dataEmployee2 AS $strField => $strValue) {
            $strSlipContent1b .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "80", true);
        }
        foreach ($dataEmployee3 AS $strField) {
            $m = $strField;
            foreach ($m AS $strField => $strValue) {
                $strSlipContent1c .= wrapRowSize(
                    getWords(str_replace("_", " ", $strField)),
                    ":",
                    $strValue,
                    "80",
                    true
                );
                //$strSlipkonten .= wrapRowSize(getWords(str_replace("_", " ", "")), " ", $strValue, "80", true);
            }
        }
        //foreach ($dataEmployee3 AS $strField => $strValue)
        //$strSlipContent1c .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "80", true);
        //print_r($dataAbsence1);
        foreach ($dataAbsence1[0] AS $strField => $strValue) {
            $strSlipContent2a .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "80", true);
        }
        foreach ($dataAbsence2[0] AS $strField => $strValue) {
            $strSlipContent2b .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "80", true);
        }
        foreach ($dataAbsence3[0] AS $strField => $strValue) {
            $strSlipContent2c .= "<tr><td width=80 valign=top>" . ucWords(
                    $strField
                ) . "</td><td valign=top>:</td><td colspan=4 height=30 valign=top>" . $strValue . "</td></tr>";
        }
        foreach ($dataApproval1[0] AS $strField => $strValue) {
            $strSlipContent5a .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "85");
        }
        foreach ($dataApproval2[0] AS $strField => $strValue) {
            $strSlipContent5b .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "85");
        }
        if ($bolEven) // genap
        {
            echo $strThisPage;
        } else if ($i == 2) {
            echo $strThisPage;
        } else // ganjil, page berikutnya
        {
            echo $strNewPage;
            $i = 2;
        }
        //  print_r($strSlipContent1a);
        //echo $strThisPage;
        $tbsPage = new clsTinyButStrong;
        $tbsPage->LoadTemplate("templates/absence_slip_templates.html");
        $tbsPage->Show(TBS_OUTPUT);
    }
    // tampilkan footer HTML
    echo "

</body>
</html>

    ";
    unset($objEmp);
    exit();
}//getSlip
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $arrUserList = getAllUserInfo($db);
    $_getInitialValue = (isset($_POST['btnShowAlert']) && $_POST['btnShowAlert'] == 1) ? "getInitialValueAlert" : "getInitialValue";
    $strDataID = getPostValue('dataID');
    $strDeductLeave = getPostValue('dataDeductLeave');
    scopeData(
        $strDataEmployee,
        $strDataSubSection,
        $strDataSection,
        $strDataDepartment,
        $strDataDivision,
        $_SESSION['sessionUserRole'],
        $arrUserInfo
    );
    $f = new clsForm("formFilter", 3, "100%", "");
    $f->caption = strtoupper($strWordsFILTERDATA);
    $f->addInput(
        getWords("date from"),
        "dataDateFrom",
        $_getInitialValue("DateFrom", date("Y-m-") . "01"),
        ["style" => "width:$strDateWidth"],
        "date",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("date thru"),
        "dataDateThru",
        $_getInitialValue("DateThru", date("Y-m-d")),
        ["style" => "width:$strDateWidth"],
        "date",
        false,
        true,
        true
    );
    $f->addInputAutoComplete(
        getWords("employee"),
        "dataEmployee",
        getDataEmployee($_getInitialValue("Employee", null, $strDataEmployee)),
        "style=width:$strDefaultWidthPx " . $strEmpReadonly,
        "string",
        false,
        true,
        true,
        "",
        "",
        true,
        null,
        "../global/hrd_ajax_source.php?action=getemployee"
    );
    $f->addLabelAutoComplete("", "dataEmployee", "");
    $f->addSelect(
        getWords("absence type"),
        "dataAbsenceType",
        getDataListabsenceType("", true, ["value" => "", "text" => "", "selected" => true]),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("deduct leave"),
        "dataDeductLeave",
        getDataListEmployeeActive($strDeductLeave, true, ["value" => "", "text" => "", "selected" => true]),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("request status"),
        "dataRequestStatus",
        getDataListRequestStatus(
            $_getInitialValue("RequestStatus"),
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addLiteral("", "", "");
    $f->addSelect(
        getWords("branch"),
        "dataBranch",
        getDataListBranch($_getInitialValue("Branch"), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("level"),
        "dataPosition",
        getDataListPosition($_getInitialValue("Position"), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("grade"),
        "dataGrade",
        getDataListSalaryGrade($_getInitialValue("Grade"), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("status"),
        "dataEmployeeStatus",
        getDataListEmployeeStatus(
            $_getInitialValue("EmployeeStatus", "", ""),
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("active"),
        "dataActive",
        getDataListEmployeeActive(
            $_getInitialValue("Active"),
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addLiteral("", "", "");
    $f->addSelect(
        getWords("company"),
        "dataCompany",
        getDataListCompany($strDataCompany, $bolCompanyEmptyOption, $arrCompanyEmptyData, $strKriteria2),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("division"),
        "dataDivision",
        getDataListDivision($_getInitialValue("Division", "", $strDataDivision), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['division'] == "")
    );
    $f->addSelect(
        getWords("department "),
        "dataDepartment",
        getDataListDepartment($_getInitialValue("Department", "", $strDataDepartment), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['department'] == "")
    );
    $f->addSelect(
        getWords("section"),
        "dataSection",
        getDataListSection($_getInitialValue("Section", "", $strDataSection), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['section'] == "")
    );
    $f->addSelect(
        getWords("sub section"),
        "dataSubSection",
        getDataListSubSection($_getInitialValue("SubSection", "", $strDataSubSection), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['sub_section'] == "")
    );
    $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
    if ($bolCanApprove) {
        $f->addSubmit("btnSync", getWords("sync"), "", true, true, "", "", "");
    }
    $formFilter = $f->render();
    getData($db);
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('absence slip management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = dataAbsenceSubmenu($strWordsAbsenceSlip);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
//pemanggilan employee yang Cuti
function getEmployeeInformation($strIDAbsence)
{
    global $db;
    if ($db->connect()) {
        $strSQL = "select t2.employee_id || ' - ' || t2.employee_name as employee, t2.grade_code as grade
		FROM hrd_employee AS t2, hrd_absence_detail t3 where t2.id = t3.id_employee and  t3.id_absence = '" . $strIDAbsence . "'";
        $res = $db->execute($strSQL);
        if ($row = $db->fetchrow($res)) {
            $arrData['employee'] = $row['employee'];
            $arrData['grade'] = $row['grade'];
        }
    }
    return $arrData;
}

?>