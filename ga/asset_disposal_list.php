<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/asset_assignment.php');
//================ END INCLUDE=====================================
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck
);
if (!$bolCanView) {
    die(accessDenied($_SERVER['HTTP_REFERER']));
}
//INISIALISASI---------------------------------------------------------------------------------------------------------------
$strWordsDataEntry = getWords("data entry");
$DataGrid = "";
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
// *************************** BEGIN Fungsi ISI DATA GRID  ********************************************************************
function getData($db)
{
    global $dataPrivilege, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck;
    global $f;
    global $DataGrid;
    global $myDataGrid;
    global $strKriteriaCompany;
    //global $arrUserInfo
    $arrData = $f->getObjectValues();
    $strKriteria = "";
    // GENERATE CRITERIA
    if ($arrData['dataIdItem'] != "") {
        $strKriteria .= "AND m.id_item = '" . $arrData['dataIdItem'] . "'";
    }
    if (validStandardDate($arrData['dataDisposalDateFrom']) && validStandardDate($arrData['dataDisposalDateThru'])) {
        $strKriteria .= "AND (m.disposal_date::date BETWEEN '" . $arrData['dataDisposalDateFrom'] . "' AND '" . $arrData['dataDisposalDateThru'] . "')  ";
    }
    if ($arrData['dataEmployeeID'] != "") {
        $strKriteria .= "AND m.employee_id = '" . $arrData['dataEmployeeID'] . "'";
    }
    $strKriteria .= $strKriteriaCompany;
    if ($db->connect()) {
        $myDataGrid = new cDataGrid("formData", "DataGrid1");
        $myDataGrid->caption = getWords(
            strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
        );
        $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
        $myDataGrid->setCriteria($strKriteria);
        $myDataGrid->addColumnCheckbox(
            new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => '']),
            true /*bolDisableSelfStatusChange*/
        );
        $myDataGrid->addColumnNumbering(
            new DataGrid_Column("No", "", ['width' => '30', 'rowspan' => '2'], ['nowrap' => ''])
        );
        $myDataGrid->addColumn(new DataGrid_Column(getWords("Item"), "item_name", ['width' => '90'], ['nowrap' => '']));
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("Employee "), "employee_name", ['width' => '100'], ['nowrap' => ''])
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("date"), "disposal_date", ['width' => '100'], ['nowrap' => ''])
        );
        $myDataGrid->addColumn(new DataGrid_Column(getWords("remark"), "remark", ['width' => '100'], ['nowrap' => '']));
        if (!isset($_POST['btnExportXLS']) && $bolCanEdit) {
            $myDataGrid->addColumn(
                new DataGrid_Column(
                    "",
                    "",
                    ["width" => "60"],
                    ['align' => 'center', 'nowrap' => ''],
                    false,
                    false,
                    "",
                    "printEditLink()",
                    "",
                    false /*show in excel*/
                )
            );
        }
        foreach ($arrData AS $key => $value) {
            $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
        }
        //-----------------BEGIN Jika Punya Hak Akses Hapus-----------------------------//
        if ($bolCanDelete) {
            $myDataGrid->addSpecialButton(
                "btnDelete",
                "btnDelete",
                "submit",
                "Delete",
                "onClick=\"javascript:return myClient.confirmDelete();\"",
                "deleteData()"
            );
        }
        //---------------- END Jika Punya Hak Akses Hapus-------------------------//
        $myDataGrid->addButtonExportExcel(
            "Export Excel",
            $dataPrivilege['menu_name'] . ".xls",
            getWords($dataPrivilege['menu_name'])
        );
        $myDataGrid->getRequest();
        //get Data and set to Datagrid's DataSource by set the data binding (bind method)
        $strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_asset_disposal AS m LEFT JOIN hrd_employee AS e ON m.id_employee = e.id";
        $strSQL = "SELECT i.item_name AS item_name,
                   e.employee_name AS employee_name,
                   e.employee_id AS employee_id ,
				   m.* 
                   FROM ga_asset_disposal AS m
				   LEFT JOIN ga_item AS i ON m.id_item=i.id
				   LEFT JOIN hrd_employee AS e ON m.id_employee=e.id ";
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

//************** END FUNGSI ISI DATA GRID ****************************************************************************************
//*********************************** FUNGSI TOMBOL EDIT******************************************
function printEditLink($params)
{
    extract($params);
    return "<a href=\"asset_disposal_edit.php?dataID=" . $record['id'] . "\">" . getWords('edit') . "</a>";
}

//******************************* END FUNGSI TOMBOL EDIT *******************************************
/*********************************BEGIN  fungsi untuk menghapus data ***************************/
function deleteData()
{
    global $myDataGrid;
    $arrKeys = [];
    foreach ($myDataGrid->checkboxes as $strValue) {
        $arrKeys['id'][] = $strValue;
    }
    $dataItem = new cGaAssetDisposal();
    $dataItem->deleteMultiple($arrKeys);
    $myDataGrid->message = $dataItem->strMessage;
}

/*********************************END fungsi untuk menghapus data ****************************/
//============================================================ MAIN PROGRAM ==========================================================
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $arrUserList = getAllUserInfo($db);
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
    $strReadonly = (scopeCBDataEntry($strDataEmployee, $_SESSION['sessionUserRole'], $arrUserInfo)) ? "readonly" : "";
    /// Form ==================================================================================
    $f = new clsForm("formFilter", 1, "100%", "");
    $f->caption = strtoupper($strWordsFILTERDATA);
    $f->addSelect(
        getWords("Item"),
        "dataIdItem",
        getDataListItem(
            $arrData['dataIdItem'],
            true,
            [
                "value" => "",
                "text" => "",
                "selected" => true
            ]
        ),
        ["style" => "width:200", "size" => 10],
        "",
        false,
        true,
        true
    );
    $f->addInputAutoComplete(
        getWords("employee ID"),
        "dataEmployee",
        getDataEmployee($arrData['dataEmployee']),
        "style='width:250px' " . $strReadonly,
        "string",
        false
    );
    $f->addLabelAutoComplete("", "dataEmployee", "");
    //$f->addSelect(getWords("Lacation Room"), "dataIdRoom",$arrData['dataIdRoom'], getDataRooms());
    $f->addInput(
        getWords("disposal date From"),
        "dataDisposalDateFrom",
        "",
        ["style" => "width:$strDateWidth"],
        "date",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("disposal date Thru"),
        "dataDisposalDateThru",
        "",
        ["style" => "width:$strDateWidth"],
        "date",
        false,
        true,
        true
    );
    $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
    $formFilter = $f->render();
    getData($db);
    // END FORM====================================================================================
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>