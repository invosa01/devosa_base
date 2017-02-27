<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_work_schedule.php');
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
    die(getWords('view denied'));
}
$db = new CdbClass;
if ($db->connect()) {
    $strDefaultStart = substr(getSetting("start_time"), 0, 5);
    $strDefaultFinish = substr(getSetting("finish_time"), 0, 5);
    $strDataID = getPostValue('dataID');
    $strDataLinkCode = getPostValue('dataLinkCode');
    $strDataTableName = getPostValue('dataTableName');
    $isNew = ($strDataID == "");
    if ($bolCanEdit) {
        $f = new clsForm("formInput", 2, "100%", "");
        $f->caption = strtoupper($strWordsINPUTDATA);
        $f->addHidden("dataID", $strDataID);
        $f->addHidden("dataLinkCode", $strDataLinkCode);
        $f->addHidden("dataTableName", $strDataTableName);
        $f->addSelect(
            getWords("division"),
            "dataDivision",
            getDataListDivision(null, true, ""),
            [
                "style"    => "width: 200px",
                "onChange" => "javascript:myClient.setTableName('Division', this.value)"
            ],
            "string",
            false,
            true,
            true
        );
        $f->addSelect(
            getWords("department"),
            "dataDepartment",
            getDataListDepartment(null, true, ""),
            [
                "style"    => "width: 200px",
                "onChange" => "javascript:myClient.setTableName('Department', this.value)"
            ],
            "string",
            false,
            true,
            true
        );
        $f->addSelect(
            getWords("unit"),
            "dataSection",
            getDataListSection(null, true, ""),
            [
                "style"    => "width: 200px",
                "onChange" => "javascript:myClient.setTableName('Section', this.value)"
            ],
            "string",
            false,
            true,
            true
        );
        $f->addSelect(
            getWords("section"),
            "dataSubSection",
            getDataListSubSection(),
            [
                "style"    => "width: 200px",
                "onChange" => "javascript:myClient.setTableName('SubSection', this.value)"
            ],
            "string",
            false,
            true,
            true
        );
        $f->addInputAutoComplete(
            getWords("employee ID"),
            "dataEmployee",
            getDataEmployee(),
            [
                "onChange" => "javascript:myClient.setTableName('Employee', this.value)",
                "style"    => "width:200"
            ],
            "string",
            false
        );
        $f->addLabelAutoComplete("", "dataEmployee", "");
        $f->addSelect(
            getWords("workday"),
            "dataWorkday",
            getDataListDayName(-1, true, -1),
            "",
            "integer",
            false,
            true,
            true
        );
        $f->addCheckBox(
            getWords("day off"),
            "dataDayOff",
            null,
            ["onChange" => "javascript:myClient.setDayOff(this.checked)"],
            null,
            false,
            true,
            true
        );
        $f->addInput(
            getWords("start time"),
            "dataStartTime",
            $strDefaultStart,
            ["size" => 10, "maxlength" => 5, "class" => "t input_mask mask_time"],
            "string",
            false,
            true,
            true
        );
        $f->addInput(
            getWords("finish time"),
            "dataFinishTime",
            $strDefaultFinish,
            ["size" => 10, "maxlength" => 5, "class" => "t input_mask mask_time"],
            "string",
            false,
            true,
            true
        );
        $f->addSubmit(
            "btnSave",
            getWords("save"),
            [
                "onClick" => "fixLinkCode(); return confirm('" . getWords(
                        'do you want to save this entry?'
                    ) . "');"
            ],
            true,
            true,
            "",
            "",
            "saveData()"
        );
        $f->addButton("btnAdd", getWords("add new"), ["onClick" => "javascript:myClient.editData(0);"]);
        $formInput = $f->render();
    } else {
        $formInput = "";
    }
    $myDataGrid = new cDataGrid("formData", "DataGrid1");
    $myDataGrid->caption = getWords("working schedule");
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    if (!isset($_REQUEST['btnExportXLS'])) {
        $myDataGrid->addColumnCheckbox(
            new DataGrid_Column(getWords("chkID"), "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
        );
    }
    $myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no."), "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("table name"), "table_name", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("link code"), "link_code", "", ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("link name"), "link_code", "", ['nowrap' => ''], true, true, "", "printLinkName()")
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("workday"), "workday", "", ['nowrap' => ''], true, true, "", "printWorkday()")
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("day off"), "day_off", "", ['align' => 'center'], true, true, "", "printDayOff()")
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
                "printEditLink()",
                "string",
                false
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
    $title = (str_replace(' ', '_', $dataPrivilege['menu_name']));
    $myDataGrid->addButtonExportExcel(getWords("export excel"), $title . ".xls", getWords($dataPrivilege['menu_name']));
    $myDataGrid->getRequest();
    //--------------------------------
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    if (is_numeric($strDataCompany)) {
        $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_work_schedule  WHERE
                        (table_name = 'Employee' AND link_code IN (SELECT employee_id FROM hrd_employee WHERE id_company = '$strDataCompany'))
                        OR
                        (table_name IN ('Division', 'Department', 'Section', 'SubSection','Unit')  AND link_code like '" . printCompanyCode(
                $strDataCompany
            ) . "%')";
        $strSQL = "SELECT * FROM hrd_work_schedule AS t1
                       WHERE 
                        (table_name = 'Employee' AND link_code IN (SELECT employee_id FROM hrd_employee WHERE id_company = '$strDataCompany'))
                        OR
                        (table_name IN ('Division', 'Department', 'Section', 'SubSection','Unit')  AND link_code like '" . printCompanyCode(
                $strDataCompany
            ) . "%')
                        ";
    } else {
        $strSQLCount = "SELECT COUNT(*) FROM hrd_work_schedule AS t1 ";
    }
    $strSQL = "SELECT * FROM hrd_work_schedule AS t1 ";
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
$strConfirmDelete = getWords("are you sure to delete this selected data?");
$strConfirmSave = getWords("do you want to save this entry?");
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
function printEditLink($params)
{
    extract($params);
    return "
    <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['id'] . "' />
     <input type=hidden name='detailTableName$counter' id='detailTableName$counter' value='" . $record['ori_table_name'] . "' />
     <input type=hidden name='detailLinkCode$counter' id='detailLinkCode$counter' value='" . $record['link_code'] . "' />
     <input type=hidden name='detailWorkday$counter' id='detailWorkday$counter' value='" . $record['workday'] . "' />
     <input type=hidden name='detailDayOff$counter' id='detailDayOff$counter' value='" . $record['day_off'] . "' />
     <input type=hidden name='detailStartTime$counter' id='detailStartTime$counter' value='" . $record['start_time'] . "' />
     <input type=hidden name='detailFinishTime$counter' id='detailFinishTime$counter' value='" . $record['finish_time'] . "' />
     <a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

function printDayOff($params)
{
    extract($params);
    if ($record['day_off'] == "t") {
        if (!isset($_REQUEST['btnExportXLS'])) {
            return "&radic;";
        } else {
            return "Yes";
        }
    } else {
        return "";
    }
}

function printWorkday($params)
{
    //global $ARRAY_DAY;
    $arrDay = [
        0 => getWords("sunday"),
        getWords("monday"),
        getWords("tuesday"),
        getWords("wednesday"),
        getWords("thursday"),
        getWords("friday"),
        getWords("saturday")
    ];
    extract($params);
    if ($record['workday'] == -1) {
        return "";
    } else {
        return $arrDay[$record['workday']];
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

// fungsi untuk menyimpan data
function saveData()
{
    global $f;
    global $isNew;
    $strDataID = $f->getValue('dataID');
    if ($f->getValue('dataTableName') != "") {
        $strmodified_byID = $_SESSION['sessionUserID'];
        if ($f->getValue('dataDayOff')) {
            $strDayOff = 't';
            $strStart = null;
            $strFinish = null;
        } else {
            $strDayOff = 'f';
            $strStart = $f->getValue('dataStartTime');
            $strFinish = $f->getValue('dataFinishTime');
        }
        $strWorkday = ($f->getValue('dataWorkday') == "") ? -1 : $f->getValue('dataWorkday');
        $dataHrdWorkSchedule = new cHrdWorkSchedule();
        $data = [
            "link_code"   => $f->getValue('dataLinkCode'),
            "table_name"  => $f->getValue('dataTableName'),
            "workday"     => $strWorkday,
            "day_off"     => $strDayOff,
            "start_time"  => $strStart,
            "finish_time" => $strFinish
        ];
        // simpan data -----------------------
        $bolSuccess = false;
        if ($isNew) {
            // data baru
            $bolSuccess = $dataHrdWorkSchedule->insert($data);
        } else {
            $bolSuccess = $dataHrdWorkSchedule->update(/*pk*/
                "id='" . $strDataID . "'", /*data to update*/
                $data
            );
        }
        $f->message = $dataHrdWorkSchedule->strMessage;
        $f->msgClass = "style=\"border-color:green; color:green\"";
        header("location:work_schedule.php");
    } else {
        $f->message = "Please fill either the division, department, section, or sub section";
    }
} // saveData
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