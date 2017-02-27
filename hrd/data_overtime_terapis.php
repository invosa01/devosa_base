<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_overtime_terapis.php');
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
$db = new CdbClass;
if ($db->connect()) {
    $strDataID = getPostValue('dataID');
    $isNew = ($strDataID == "");
    $strSet = "overtime_terapis";
    if ($bolCanEdit) {
        $f = new clsForm("formInput", 1, "100%", "");
        $f->caption = strtoupper(getWords("input data") . " " . getWords("overtime terapis"));
        $f->addHidden("dataID", $strDataID);
        $f->addInput(getWords("overtime code"), "dataCode", "", ["size" => 20], "string", true, true, true);
        $f->addInput(getWords("overtime type"), "dataType", "", ["size" => 50], "string", true, true, true);
        $f->addInput(getWords("amount"), "dataAmount", "", ["size" => 50], "numeric", true, true, true);
        $f->addSelect(
            getWords("company"),
            "dataCompanyId",
            getDataListCompany(null, false, null, $strKriteria2),
            [],
            "numeric",
            true,
            true,
            true
        );
        $f->addSubmit(
            "btnSave",
            getWords("save"),
            ["onClick" => "javascript:myClient.confirmSave();"],
            true,
            true,
            "",
            "",
            "saveData()"
        );
        $f->addButton("btnAdd", getWords("add new"), ["onClick" => "javascript:myClient.editData('0|functional|3');"]);
        $formInput = $f->render();
    } else {
        $formInput = "";
    }
    $myDataGrid = new cDataGrid("formData", "DataGrid1");
    // $myDataGrid->caption = getWords(strtoupper(vsprintf(getWords("list of"), getWords("functional"))));
    $myDataGrid->caption = strtoupper($strWordsLISTOF . " " . getWords($dataPrivilege['menu_name']));
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID", "ot_code", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
    );
    $myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no"), "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("code"), "ot_code", ['width' => '130'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("overtime type"), "ot_type", ['width' => ''], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("Amount"), "amount", ['width' => ''], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("Company"), "company", ['width' => ''], ['nowrap' => '']));
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
                "",
                false /*show in excel*/
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
    $myDataGrid->addButtonExportExcel(
        getWords("export excel"),
        $dataPrivilege['menu_name'] . ".xls",
        getWords($dataPrivilege['menu_name'])
    );
    $myDataGrid->getRequest();
    //--------------------------------
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_overtime_terapis ";
    $strSQL = "SELECT t1.*,t2.company_name as company FROM hrd_overtime_terapis as t1
LEFT JOIN hrd_company as t2 ON t2.id=t1.company_id";
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
    $dataset = $myDataGrid->getData($db, $strSQL);
    //bind Datagrid with array dataset
    $myDataGrid->bind($dataset);
    $DataGrid = $myDataGrid->render();
    $strConfirmSave = getWords("do you want to save this entry?");
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("data overtime terapis");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
function printEditLink($params)
{
    $strResult = "";
    extract($params);
    return "
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['ot_code'] . "' />
      <input type=hidden name='detailCode$counter' id='detailCode$counter' value='" . $record['ot_code'] . "' />
      <input type=hidden name='detailType$counter' id='detailType$counter' value='" . $record['ot_type'] . "' />
      <input type=hidden name='detailAmount$counter' id='detailAmount$counter' value='" . $record['amount'] . "' />
      <input type=hidden name='detailCompanyId$counter' id='detailCompanyId$counter' value='" . $record['company_id'] . "' />
      <a id=\"edit-$counter\" href=\"javascript:myClient.editData('$counter')\" class=\"edit-data\">" . getWords(
        'edit'
    ) . "</a>" . $strResult;
}

// fungsi untuk menyimpan data
function saveData()
{
    global $f;
    global $db;
    global $error;
    global $isNew;
    $strmodified_byID = $_SESSION['sessionUserID'];
    $dataHrdOvertimeTerapis = new cHrdOvertimeTerapis();
    $data = [
        "ot_code" => $f->getValue('dataCode'),
        "ot_type" => $f->getValue('dataType'),
        "amount" => $f->getValue('dataAmount'),
        "company_id" => $f->getValue('dataCompanyId')
    ];
    //var_dump($data);
    //exit();
    // simpan data -----------------------
    $bolSuccess = false;
    if ($isNew) {
        if (isDataExists($db, $ARRAY_ALLOWANCE_SET[$strSet]['table_name'], $strSet . "_code", $strDataCode)) {
            $f->message = $error['duplicate_code'] . " of $strSet -> $strDataCode";
        } else {
            // data baru
            $bolSuccess = ($dataHrdOvertimeTerapis->insert($data));
        }
    } else {
        //var_dump($data);
        //exit();
        $bolSuccess = ($dataHrdOvertimeTerapis->update(/*pk*/
            "ot_code='" . $f->getValue('dataID') . "'", /*data to update*/
            $data
        ));
    }
    if ($bolSuccess) {
        $f->setValue('dataID', $data['ot_code']);
    }
    $f->message = $dataHrdOvertimeTerapis->strMessage;
    // }
    $f->msgClass = ($bolSuccess) ? "bgOK" : "bgError";
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
    global $myDataGrid;
    $arrKeys = [];
    foreach ($myDataGrid->checkboxes as $strValue) {
        $arrKeys['ot_code'][] = $strValue;
    }
    $dataHrdOvertimeTerapis = new cHrdOvertimeTerapis();
    $dataHrdOvertimeTerapis->deleteMultiple($arrKeys);
    $myDataGrid->message = $dataHrdOvertimeTerapis->strMessage;
} //deleteData
?>
