<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_work_schedule.php');
$dataPrivilege = getDataPrivileges(
    "work_schedule_edit.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView) {
    die(getWords('view denied'));
}
$strWordsEntrySchedule = getWords("entry schedule");
$strWordsScheduleList = getWords("schedule list");
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $arrUserList = getAllUserInfo($db);
    $strDataID = getPostValue('dataID');
    $isNew = ($strDataID == "");
    $strPageTitle = $dataPrivilege['menu_name'];
    $myDataGrid = new cDataGrid("formData", "DataGrid1");
    $strDefaultStart = substr(getSetting("start_time"), 0, 5);
    $strDefaultFinish = substr(getSetting("finish_time"), 0, 5);
    $strDataID = getPostValue('dataID');
    $strDataLinkCode = getPostValue('dataLinkCode');
    $strDataTableName = getPostValue('dataTableName');
    scopeData(
        $strDataEmployee,
        $strDataSubSection,
        $strDataSection,
        $strDataDepartment,
        $strDataDivision,
        $_SESSION['sessionUserRole'],
        $arrUserInfo
    );
    //generate form untuk select trip type
    //trip type harus dipilih dulu supaya jenis2 trip allowance dapat ditentukan
    $f = new clsForm("formFilter", 3, "100%", "");
    $f->caption = strtoupper("schedule list");
    $f->addInputAutoComplete(
        getWords("employee ID"),
        "dataEmployeeID",
        getDataEmployee($strDataEmployee),
        "style=width:$strDefaultWidthPx " . $strEmpReadonly,
        "string",
        false
    );
    $f->addLabelAutoComplete("", "dataEmployeeID", "");
    $f->addSelect(
        getWords("branch"),
        "dataBranch",
        getDataListBranch("", true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("level"),
        "dataPosition",
        getDataListPosition("", true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("grade"),
        "dataGrade",
        getDataListSalaryGrade("", true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("status"),
        "dataEmployeeStatus",
        getDataListEmployeeStatus("", true, ["value" => "", "text" => "", "selected" => true]),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("active"),
        "dataActive",
        getDataListEmployeeActive("", true, ["value" => "", "text" => "", "selected" => true]),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
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
        getDataListDivision($strDataDivision, true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['division'] == "")
    );
    $f->addSelect(
        getWords("department "),
        "dataDepartment",
        getDataListDepartment($strDataDepartment, true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['department'] == "")
    );
    $f->addSelect(
        getWords("section"),
        "dataSection",
        getDataListSection($strDataSection, true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['section'] == "")
    );
    $f->addSelect(
        getWords("sub section"),
        "dataSubSection",
        getDataListSubSection($strDataSubSection, true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['sub_section'] == "")
    );
    $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
    $formFilter = $f->render();
    getData($db);
}
function getData($db)
{
    global $dataPrivilege;
    global $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge;
    global $f;
    global $myDataGrid;
    global $DataGrid;
    global $strDataCompany;
    global $strKriteriaCompany;
    $arrData = $f->getObjectValues();
    $strKriteria = "";
    // GENERATE CRITERIA
    if ($arrData['dataEmployeeID'] != "") {
        $strKriteria .= "AND employee_id = '" . $arrData['dataEmployeeID'] . "' ";
    }
    if ($arrData['dataPosition'] != "") {
        $strKriteria .= "AND position_code = '" . $arrData['dataPosition'] . "' ";
    }
    if ($arrData['dataBranch'] != "") {
        $strKriteria .= "AND branch_code = '" . $arrData['dataBranch'] . "' ";
    }
    if ($arrData['dataGrade'] != "") {
        $strKriteria .= "AND grade_code = '" . $arrData['dataGrade'] . "' ";
    }
    if ($arrData['dataEmployeeStatus'] != "") {
        $strKriteria .= "AND employee_status = '" . $arrData['dataEmployeeStatus'] . "' ";
    }
    if ($arrData['dataActive'] != "") {
        $strKriteria .= "AND active = '" . $arrData['dataActive'] . "' ";
    }
    if ($arrData['dataDivision'] != "") {
        $strKriteria .= "AND division_code = '" . $arrData['dataDivision'] . "' ";
    }
    if ($arrData['dataDepartment'] != "") {
        $strKriteria .= "AND department_code = '" . $arrData['dataDepartment'] . "' ";
    }
    if ($arrData['dataSection'] != "") {
        $strKriteria .= "AND section_code = '" . $arrData['dataSection'] . "' ";
    }
    if ($arrData['dataSubSection'] != "") {
        $strKriteria .= "AND sub_section_code = '" . $arrData['dataSubSection'] . "' ";
    }
    $strKriteria .= $strKriteriaCompany;
    if ($db->connect()) {
        $myDataGrid->caption = getWords("schedule list");
        $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
        $myDataGrid->addColumnCheckbox(
            new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
        );
        $myDataGrid->addColumnNumbering(new DataGrid_Column("No.", "", ['width' => '30'], ['nowrap' => '']));
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("table name"), "table_name", ['width' => '100'], ['nowrap' => ''])
        );
        $myDataGrid->addColumn(new DataGrid_Column(getWords("link code"), "link_code", "", ['nowrap' => '']));
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("link name"),
                "link_code",
                "",
                ['nowrap' => ''],
                true,
                true,
                "",
                "printLinkName()"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("workday"), "workday", "", ['nowrap' => ''], true, true, "", "printWorkday()")
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("day off"),
                "day_off",
                "",
                ['align' => 'center'],
                true,
                true,
                "",
                "printDayOff()"
            )
        );
        $myDataGrid->addColumn(new DataGrid_Column(getWords("start time"), "start_time", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("finish time"), "finish_time", "", ['nowrap' => '']));
        if ($bolCanEdit) {
            $myDataGrid->addColumn(
                new DataGrid_Column(
                    "",
                    "",
                    ['width' => '60'],
                    ['align' => 'center', 'nowrap' => ''],
                    false,
                    false,
                    "",
                    "printGlobalEditLink()"
                )
            );
        }
        if ($bolCanDelete) {
            $myDataGrid->addSpecialButton(
                "btnDelete",
                "btnDelete",
                "submit",
                getWords("delete"),
                "onClick=\"javascript:return myClient.confirmDelete();\"",
                "deleteData()"
            );
        }
        $myDataGrid->getRequest();
        //--------------------------------
        //get Data and set to Datagrid's DataSource by set the data binding (bind method)
        if ($strKriteria != "") {
            $strKriteria .= " AND table_name = 'Employee'";
        }
        if (is_numeric($strDataCompany)) {
            $strSQLCOUNT = "SELECT COUNT(*) AS total FROM
                          (SELECT t1.* FROM 
                            (SELECT * FROM hrd_work_schedule WHERE 
                              (table_name = 'Employee' AND link_code IN (SELECT employee_id FROM hrd_employee WHERE id_company = '$strDataCompany'))
                              OR
                              (table_name IN ('Division', 'Department', 'Section', 'SubSection','Unit')  AND link_code like '" . printCompanyCode(
                    $strDataCompany
                ) . "%')
                             ) as t1 LEFT JOIN hrd_employee AS t2 ON t1.link_code = t2.employee_id 
                            WHERE 1=1 $strKriteria
                           )
                             ";
            $strSQL = "SELECT * FROM
                          (SELECT t1.* FROM 
                            (SELECT * FROM hrd_work_schedule WHERE 
                              (table_name = 'Employee' AND link_code IN (SELECT employee_id FROM hrd_employee WHERE id_company = '$strDataCompany'))
                              OR
                              (table_name IN ('Division', 'Department', 'Section', 'SubSection','Unit')  AND link_code like '" . printCompanyCode(
                    $strDataCompany
                ) . "%')
                             ) as t1 LEFT JOIN hrd_employee AS t2 ON t1.link_code = t2.employee_id 
                            WHERE 1=1 $strKriteria
                           )
                          ";
        } else {
            $strSQLCount = "SELECT COUNT(*) FROM hrd_work_schedule AS t1 LEFT JOIN hrd_employee AS t2 ON t1.link_code = t2.employee_id
                            WHERE 1=1 $strKriteria";
        }
        $strSQL = "SELECT t1.* FROM hrd_work_schedule AS t1 LEFT JOIN hrd_employee AS t2 ON t1.link_code = t2.employee_id
                            WHERE 1=1 $strKriteria";
        $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
        $dataset = $myDataGrid->getData($db, $strSQL);
        foreach ($dataset as &$row) {
            $row['ori_table_name'] = $row['table_name'];
            if ($row['table_name'] == 'Section') {
                $row['table_name'] = 'Unit';
            } else if ($row['table_name'] == 'SubSection') {
                $row['table_name'] = 'Section';
            }
        }
        //bind Datagrid with array dataset
        $myDataGrid->bind($dataset);
        $DataGrid = $myDataGrid->render();
    }
}

$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
function printDayOff($params)
{
    extract($params);
    if ($record['day_off'] == "t") {
        return "&radic;";
    } else {
        return "";
    }
}

function printWorkday($params)
{
    global $ARRAY_DAY;
    extract($params);
    if ($record['workday'] == -1) {
        return "";
    } else {
        return $ARRAY_DAY[$record['workday']];
    }
}

function printLinkName($params)
{
    global $db;
    extract($params);
    if ($record['table_name'] == 'Employee') {
        $strSQL = "SELECT employee_name FROM hrd_employee WHERE employee_id = '" . $record['link_code'] . "'";
        $resExec = $db->execute($strSQL);
        if ($rowTmp = $db->fetchrow($resExec)) {
            $strLinkName = $rowTmp['employee_name'];
        } else {
            $strLinkName = "";
        }
    } else if ($record['table_name'] == 'Division') {
        $strSQL = "SELECT division_name FROM hrd_division WHERE division_code = '" . $record['link_code'] . "'";
        $resExec = $db->execute($strSQL);
        if ($rowTmp = $db->fetchrow($resExec)) {
            $strLinkName = $rowTmp['division_name'];
        } else {
            $strLinkName = "";
        }
    } else if ($record['table_name'] == 'Department') {
        $strSQL = "SELECT department_name FROM hrd_department WHERE department_code = '" . $record['link_code'] . "'";
        $resExec = $db->execute($strSQL);
        if ($rowTmp = $db->fetchrow($resExec)) {
            $strLinkName = $rowTmp['department_name'];
        } else {
            $strLinkName = "";
        }
    } else if ($record['table_name'] == 'Unit') {
        $strSQL = "SELECT section_name FROM hrd_section WHERE section_code = '" . $record['link_code'] . "'";
        $resExec = $db->execute($strSQL);
        if ($rowTmp = $db->fetchrow($resExec)) {
            $strLinkName = $rowTmp['section_name'];
        } else {
            $strLinkName = "";
        }
    } else if ($record['table_name'] == 'Section') {
        $strSQL = "SELECT sub_section_name FROM hrd_sub_section WHERE sub_section_code = '" . $record['link_code'] . "'";
        $resExec = $db->execute($strSQL);
        if ($rowTmp = $db->fetchrow($resExec)) {
            $strLinkName = $rowTmp['sub_section_name'];
        } else {
            $strLinkName = "";
        }
    }
    return $strLinkName;
}

// fungsi untuk menghapus data
function deleteData()
{
    global $myDataGrid;
    $arrKeys = [];
    foreach ($myDataGrid->checkboxes as $strValue) {
        $arrKeys['id'][] = $strValue;
    }
    $dataHrdWorkSchedule = new cHrdWorkSchedule();
    $dataHrdWorkSchedule->deleteMultiple($arrKeys);
    $myDataGrid->message = $dataHrdWorkSchedule->strMessage;
} //deleteData
?>