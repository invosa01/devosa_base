<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../classes/datagrid_modified.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_employee_mutation.php');
include_once('../classes/hrd/hrd_employee_mutation_resign.php');
include_once("../includes/krumo/class.krumo.php");

class cDataGrid2 extends cDataGridNew
{

    /*override this function*/
    function _printGridButtons()
    {
        global $bolCanEdit;
        $strResult = "";
        if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
            $colSpan = count($this->columnSet);
            if ($this->hasCheckbox && (count($this->dataset) > 0)) //have checkbox
            {
                $strResult .= "
              <!-- grid footer -->
              <tfoot>
              <tr>
                <td align=\"center\">" . $this->_printCheckboxAllBottom() . "</td>
                <td colspan=12>";
            } else //don't have checkbox
            {
                $strResult .= "
              <!-- grid footer -->
              <tfoot>
              <tr>
                <td colspan=13>";
            }
            $counter = 0;
            if (count($this->buttons) > 0) {
                foreach ($this->buttons as $button) {
                    if ($button['special'] && (count($this->dataset) == 0)) {
                        continue;
                    }
                    $counter++;
                    if ($button['class'] == "") {
                        $className = "";
                    } else {
                        $className = "class=\"" . $button['class'] . "\"";
                    }
                    $strResult .= "
                <input " . $className . " name=\"" . $button['name'] . "\" type=\"" . $button['type'] . "\" id=\"" . $button['id'] . "\" value=\"" . $button['value'] . "\" " . $button['clientAction'] . ">&nbsp;";
                }
            }
            if ($counter == 0) {
                return "";
            }
            $strResult .= "&nbsp;</td>
                <td nowrap=nowrap>";
            $strButtons = "";
            /*
            if ($_SESSION['sessionUserRole'] == ROLE_ADMIN || $_SESSION['sessionUserRole'] == ROLE_MANAGER || $_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_DIRECTOR)
            {
              $strButtons .= "<input type=submit name=btnRecommend value=\"" .getWords('recommend'). "\" onClick=\"return confirmStatusChanges(false)\">";
              $strButtons .= "&nbsp;<input type=submit name=btnSkip value=\"" .getWords('skip'). "\" onClick=\"return confirmStatusChanges(false)\">";
              $strButtons .= "&nbsp;<input type=submit name=btnCancel value=\"" .getWords('clear status'). "\" onClick=\"return confirmStatusChanges(false)\">";
            }
            */
            $strResult .= $strButtons . "&nbsp;</td>";
            if ($bolCanEdit) {
                $strResult .= "<td colSpan=2>&nbsp;</td>";
            }
            $strResult .= "
              </tr>
              </tfoot>
              <!-- end of grid footer -->";
        }
        return $strResult;
    }

    /*override this function*/

    function printOpeningRow($intRows, $rowDb)
    {
        $strResult = "";
        $strClass = getCssClass($rowDb['status']);
        if ($strClass != "") {
            $strClass = "class=\"" . $strClass . "\"";
        }
        $strResult .= "
            <tr $strClass valign=\"top\">";
        return $strResult;
    }
}

$dataPrivilege = getDataPrivileges(
    "resign_edit.php",
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
$strWordsNew = getWords("new");
$strWordsDenied = getWords("denied");
$strWordsChecked = getWords("checked");
$strWordsApproved = getWords("approved");
$strWordsFinished = getWords("finished");
$strWordsVerified = getWords("verified");
$DataGrid = "";
$formFilter = "";
//DAFTAR FUNGSI--------------------------------------------------------------------------------------------------------------
function getData($db)
{
    global $dataPrivilege;
    global $f;
    global $myDataGrid;
    global $DataGrid;
    global $strKriteriaCompany;
    //global $arrUserInfo;
    $arrData = $f->getObjectValues();
    $strKriteria = "";
    // GENERATE CRITERIA
    if (validStandardDate($strDateFrom = $arrData['dataDateFrom']) && validStandardDate(
            $strDateThru = $arrData['dataDateThru']
        )
    ) {
        $strKriteria .= "AND (t3.resign_date BETWEEN '$strDateFrom' AND '$strDateThru')";
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
    if ($arrData['dataRequestStatus'] != "") {
        $strKriteria .= "AND status = '" . $arrData['dataRequestStatus'] . "'";
    }
    if ($arrData['dataDivision'] != "") {
        $strKriteria .= "AND t2.division_code = '" . $arrData['dataDivision'] . "'";
    }
    if ($arrData['dataDepartment'] != "") {
        $strKriteria .= "AND department_code = '" . $arrData['dataDepartment'] . "'";
    }
    if ($arrData['dataSection'] != "") {
        $strKriteria .= "AND section_code = '" . $arrData['dataSection'] . "'";
    }
    if ($arrData['dataSubSection'] != "") {
        $strKriteria .= "AND sub_section_code = '" . $arrData['dataSubSection'] . "'";
    }
    $strKriteria .= $strKriteriaCompany;
    if ($db->connect()) {
        $myDataGrid = new cDataGrid2("formData", "DataGrid1", "100%", "100%", false, true, false);
        $myDataGrid->caption = getWords(
            strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
        );
        $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
        $myDataGrid->setCriteria($strKriteria);
        //$myDataGrid->setSortOrder("t1.proposal_date, t2.employee_id ");
        if (!isset($_REQUEST['btnExportXLS'])) {
            $myDataGrid->addColumnCheckbox(
                new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => '']),
                true /* bolDisableSelfStatusChange */
            );
        }
        $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("created"), "proposal_date", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("resign date"), "resign_date", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("employee id"), "employee_id", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("employee name"), "employee_name", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("join date"), "join_date", "", ['nowrap' => '']));
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("department"),
                "department_code",
                "",
                ['nowrap' => ''],
                false,
                false,
                "",
                "getDepartmentName()"
            )
        );
        $myDataGrid->addColumn(new DataGrid_Column(getWords("leave remain"), "leave_remain", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("meal"), "meal", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("conjuncture"), "conjuncture", "", ['nowrap' => '']));
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("leave allowance"), "leave_allowance1", "", ['nowrap' => ''])
        );
        $myDataGrid->addColumn(new DataGrid_Column(getWords("pesangon"), "pesangon", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("other right"), "other_right", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("loan"), "loan", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("other loan"), "other_loan", "", ['nowrap' => '']));
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("other obligation"), "other_obligation", "", ['nowrap' => ''])
        );
        $myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", "", ['nowrap' => '']));
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
        if ($dataPrivilege['edit'] == 't') //$myDataGrid->addColumn(new DataGrid_Column("", "", array("width" => "60"), array('align' => 'center', 'nowrap' => ''), false, false, "", "printGlobalEditLink(" . array("record" => $params) . ")", "", false /* show in excel */));
        {
            $myDataGrid->addColumn(
                new DataGrid_Column(
                    "",
                    "",
                    ["width" => "60"],
                    ['align' => 'center', 'nowrap' => ''],
                    false,
                    false,
                    "",
                    "printGlobalEditLink()",
                    "",
                    false /*show in excel*/
                )
            );
        }
        foreach ($arrData AS $key => $value) {
            $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
        }
        //tampilkan buttons sesuai dengan otoritas, common_function.php
        generateRoleButtons(
            $dataPrivilege['edit'],
            $dataPrivilege['delete'],
            $dataPrivilege['check'],
            $dataPrivilege['approve'],
            true,
            true,
            $myDataGrid
        );
        $myDataGrid->addButtonExportExcel(
            "Export Excel",
            $dataPrivilege['menu_name'] . ".xls",
            getWords($dataPrivilege['menu_name'])
        );
        $myDataGrid->getRequest();
        $strSQLCOUNT = "
        SELECT COUNT(*) AS total FROM hrd_employee_mutation_resign AS t3 
        LEFT JOIN hrd_employee_mutation AS t1 ON t3.id_mutation = t1.id
        LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id";
        $strSQL = "SELECT t3.*, t2.employee_id, t2.employee_name, t2.gender, t2.join_date,  ";
        $strSQL .= "t1.\"note\", t1.\"id_employee\", t1.proposal_date, t1.\"status\", t1.id as idm ";
        $strSQL .= "FROM hrd_employee_mutation_resign AS t3 ";
        $strSQL .= "LEFT JOIN hrd_employee_mutation AS t1 ON t3.id_mutation = t1.id ";
        $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
        $strSQL .= "WHERE 1=1 $strKriteria ";
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

// fungsi untuk verify, check, deny, atau approve
function changeStatus($db, $intStatus)
{
    global $_REQUEST;
    global $_SESSION;
    if (!is_numeric($intStatus)) {
        return false;
    }
    $strUpdate = "";
    $strSQL = "";
    $strmodified_byID = $_SESSION['sessionUserID'];
    $strUpdate = getStatusUpdateString($intStatus);
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 15) == 'DataGrid1_chkID') {
            $strSQLx = "SELECT id_mutation FROM hrd_employee_mutation_resign
                    WHERE id = '$strValue' ";
            $resDb = $db->execute($strSQLx);
            if ($rowDb = $db->fetchrow($resDb)) {
                $strSQLx = "SELECT status, employee_name, t1.proposal_date, t1.id
                    FROM hrd_employee_mutation_resign AS t3 
                    LEFT JOIN hrd_employee_mutation AS t1 ON t3.id_mutation = t1.id
                    LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
                    WHERE t1.id = '" . $rowDb['id_mutation'] . "'";
                $resDb = $db->execute($strSQLx);
                if ($rowDb = $db->fetchrow($resDb)) {
                    //the status should be increasing
                    //if (isProcessable($rowDb['status'], $intStatus))
                    if (($intStatus == -1) || (($rowDb['status'] < $intStatus) && ($rowDb['status'] != -1))) {
                        $strSQL .= "UPDATE hrd_employee_mutation SET $strUpdate status = '$intStatus'  ";
                        $strSQL .= "WHERE id = '" . $rowDb['id'] . "'; ";
                        writeLog(
                            ACTIVITY_EDIT,
                            MODULE_EMPLOYEE,
                            $rowDb['employee_name'] . " - " . $rowDb['proposal_date'] . " - " . $rowDb['resign_date'],
                            $intStatus
                        );
                    }
                }
            }
        }
        $resExec = $db->execute($strSQL);
    }
} //changeStatus
// fungsi untuk menghapus data
function deleteData()
{
    global $myDataGrid;
    $arrKeys = [];
    foreach ($myDataGrid->checkboxes as $strValue) {
        $arrKeys['id'][] = $strValue;
        $arrKeys2['id_absence'][] = $strValue;
    }
    $tblResign = new cHrdEmployeeMutationResign();
    $tblResign->deleteMultiple($arrKeys);
    writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, $arrKeys['id']);
    $myDataGrid->message = $tblResign->strMessage;
}

//deleteData
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $arrUserList = getAllUserInfo($db);
    if (isset($_POST['btnShowAlert']) && $_POST['btnShowAlert'] == 1) {
        $dtFrom = getNextYear(date("Y-m-d"), -1);
        $reqStatus = 0;
        $_SESSION["sessiondataEmployee"] = "";
        $_SESSION["sessiondataPosition"] = "";
        $_SESSION["sessiondataSalaryGrade"] = "";
        $_SESSION["sessiondataEmployeeStatus"] = "";
        $_REQUEST["sessiondataEmployeeStatus"] = "";
        echo $_SESSION["sessiondataEmployeeStatus"];
    } else {
        $dtFrom = date("Y-m-") . "01";
        $reqStatus = null;
    }
    $strDataID = getPostValue('dataID');
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
        getInitialValue("DateFrom", $dtFrom, $dtFrom),
        ["style" => "width:$strDateWidth"],
        "date",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("date thru"),
        "dataDateThru",
        getInitialValue("DateThru", date("Y-m-d")),
        ["style" => "width:$strDateWidth"],
        "date",
        false,
        true,
        true
    );
    $f->addInputAutoComplete(
        getWords("employee"),
        "dataEmployee",
        getDataEmployee(getInitialValue("Employee", null, $strDataEmployee)),
        "style=width:$strDefaultWidthPx " . $strEmpReadonly,
        "string",
        false
    );
    $f->addLabelAutoComplete("", "dataEmployee", "");
    $f->addSelect(
        getWords("request status"),
        "dataRequestStatus",
        getDataListRequestStatus(
            getInitialValue("RequestStatus", $reqStatus, $reqStatus),
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("branch"),
        "dataBranch",
        getDataListBranch(getInitialValue("Branch"), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("level"),
        "dataPosition",
        getDataListPosition(getInitialValue("Position"), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("grade"),
        "dataGrade",
        getDataListSalaryGrade(getInitialValue("Grade"), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("status"),
        "dataEmployeeStatus",
        getDataListEmployeeStatus(
            getInitialValue("EmployeeStatus"),
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
            getInitialValue("Active"),
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
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
        getDataListDivision(getInitialValue("Division", "", $strDataDivision), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['division'] == "")
    );
    $f->addSelect(
        getWords("department "),
        "dataDepartment",
        getDataListDepartment(getInitialValue("Department", "", $strDataDepartment), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['department'] == "")
    );
    $f->addSelect(
        getWords("section"),
        "dataSection",
        getDataListSection(getInitialValue("Section", "", $strDataSection), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['section'] == "")
    );
    $f->addSelect(
        getWords("sub section"),
        "dataSubSection",
        getDataListSubSection(getInitialValue("SubSection", "", $strDataSubSection), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['sub_section'] == "")
    );
    $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
    $formFilter = $f->render();
    getData($db);
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('employee resign request list');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strWordsResignProposalList = getWords("severance employee list");
$pageSubMenu = employeeResignSubmenu($strWordsResignProposalList);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
?>