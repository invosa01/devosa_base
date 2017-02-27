<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_company.php');
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
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
$strDefaultPath = getSetting("attendance_file_path");
$strDefaultType = getSetting("attendance_file_type");
if ($bolCanEdit) {
    $f = new clsForm("formInput", 3, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    $f->addInput(
        getWords("company code"),
        "dataCompanyCode",
        "",
        ["size" => 31, "maxlength" => 31],
        "string",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("company name"),
        "dataCompanyName",
        "",
        ["size" => 100, "maxlength" => 127],
        "string",
        true,
        true,
        true
    );
    /*$f->addInput(
        getWords("attendance file path"),
        "dataAttendanceFilePath",
        $strDefaultPath,
        ["size" => 100, "maxlength" => 255],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("attendance file type"),
        "dataAttendanceFileType",
        $strDefaultType,
        ["size" => 100, "maxlength" => 7],
        "string",
        false,
        true,
        true
    );*/
    $f->addInput(
        getWords("npwp"),
        "npwp",
        false,
        ["size" => 100, "maxlength" => 100],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("siup"),
        "siup",
        false,
        ["size" => 100, "maxlength" => 100],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("email"),
        "email",
        false,
        ["size" => 100, "maxlength" => 100],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("phone"),
        "phone_number",
        false,
        ["size" => 100, "maxlength" => 100],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("fax"),
        "fax_number",
        false,
        ["size" => 100, "maxlength" => 100],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("logo"),
        "logo",
        false,
        ["size" => 100, "maxlength" => 100],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("working days"),
        "work_days",
        false,
        ["size" => 100, "maxlength" => 100],
        "string",
        false,
        true,
        true
    );
    $f->addTextArea(getWords("address"), "address", "", ["cols" => 97, "rows" => 2], "string", false, true, true);
    $f->addTextArea(getWords("note"), "dataNote", "", ["cols" => 97, "rows" => 2], "string", false, true, true);
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
    $f->addButton("btnAdd", getWords("add new"), ["onClick" => "javascript:myClient.editData(0);"]);
    $formInput = $f->render();
} else {
    $formInput = "";
}
$myDataGrid = new cDataGrid("formData", "DataGrid1");
$myDataGrid->caption = strtoupper($strWordsLISTOF . " " . getWords($dataPrivilege['menu_name']));
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", "", ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no."), "", "", ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("company code"), "company_code", "", ['nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("company name"), "company_name", "", ['nowrap' => '']));
/*$myDataGrid->addColumn(
    new DataGrid_Column(getWords("attendance file path"), "attendance_file_path", [], ['nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("file type"), "attendance_file_type", [], ['nowrap' => '']));*/
$myDataGrid->addColumn(new DataGrid_Column("npwp", "npwp", "", ['align' => 'center', 'nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("siup", "siup", "", ['align' => 'center', 'nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("address", "address", "", ['align' => 'center', 'nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("email", "email", "", ['align' => 'center', 'nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("phone", "phone_number", "", ['align' => 'center', 'nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("fax", "fax_number", "", ['align' => 'center', 'nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("logo", "logo", "", ['align' => 'center', 'nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("work days", "work_days", "", ['align' => 'center', 'nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", null, ['nowrap' => '']));
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
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("Chart"),
        "",
        ["width" => 30],
        ['align' => 'center'],
        false,
        false,
        "",
        "printChartLink()"
    )
);
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
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_company $strKriteria2 ";
$strSQL = "SELECT * FROM hrd_company $strKriteria2 ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('company data management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
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
      <input type=hidden name='detailCompanyCode$counter' id='detailCompanyCode$counter' value='" . $record['company_code'] . "' />
      <input type=hidden name='detailCompanyName$counter' id='detailCompanyName$counter' value='" . $record['company_name'] . "' />
      <input type=hidden name='detailAttendanceFilePath$counter' id='detailAttendanceFilePath$counter' value='" . $record['attendance_file_path'] . "' />
      <input type=hidden name='detailAttendanceFileType$counter' id='detailAttendanceFileType$counter' value='" . $record['attendance_file_type'] . "' />
      <input type=hidden name='detailNote$counter' id='detailNote$counter' value='" . $record['note'] . "' />
      <input type=hidden name='detailNpwp$counter' id='detailNpwp$counter' value='" . $record['npwp'] . "' />
      <input type=hidden name='detailSiup$counter' id='detailSiup$counter' value='" . $record['siup'] . "' />
      <input type=hidden name='detailEmail$counter' id='detailEmail$counter' value='" . $record['email'] . "' />
      <input type=hidden name='detailPhone$counter' id='detailPhone$counter' value='" . $record['phone_number'] . "' />
      <input type=hidden name='detailFax$counter' id='detailFax$counter' value='" . $record['fax_number'] . "' />
      <input type=hidden name='detailLogo$counter' id='detailLogo$counter' value='" . $record['logo'] . "' />
      <input type=hidden name='detailWorkingDays$counter' id='detailWorkingDays$counter' value='" . $record['work_days'] . "' />
      <input type=hidden name='detailAddress$counter' id='detailAddress$counter' value='" . $record['address'] . "' />
      <a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

function printChartLink($params)
{
    extract($params);
    return '<a href="data_functional_chart_3.php?company_id=' . $record['id'] . '" target="_blank" class="btn btn-primary btn-small">View Chart</a>';
}

// fungsi untuk menyimpan data
function saveData()
{
    global $f;
    global $isNew;
    $strmodified_byID = $_SESSION['sessionUserID'];
    $dataHrdCompany = new cHrdCompany();
    /*$data = [
        "company_code"         => $f->getValue('dataCompanyCode'),
        "company_name"         => $f->getValue('dataCompanyName'),
        "attendance_file_path" => $f->getValue('dataAttendanceFilePath'),
        "attendance_file_type" => $f->getValue('dataAttendanceFileType'),
        "note"                 => $f->getValue('dataNote')
    ];*/
    $data = [
        "company_code" => $f->getValue('dataCompanyCode'),
        "company_name" => $f->getValue('dataCompanyName'),
        "npwp"         => $f->getValue('npwp'),
        "siup"         => $f->getValue('siup'),
        "email"        => $f->getValue('email'),
        "phone_number" => $f->getValue('phone_number'),
        "fax_number"   => $f->getValue('fax_number'),
        "logo"         => $f->getValue('logo'),
        "work_days"    => $f->getValue('work_days'),
        "address"      => $f->getValue('address'),
        "note"         => $f->getValue('dataNote')
    ];
    // simpan data -----------------------
    $bolSuccess = false;
    if ($isNew) {
        // data baru
        $bolSuccess = ($dataHrdCompany->insert($data));
    } else {
        $bolSuccess = ($dataHrdCompany->update(/*pk*/
            "id='" . $f->getValue('dataID') . "'", /*data to update*/
            $data
        ));
    }
    if ($bolSuccess) {
        $f->setValue('dataID', $f->getValue('dataID'));
    }
    $f->message = $dataHrdCompany->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
    global $myDataGrid;
    $arrKeys = [];
    foreach ($myDataGrid->checkboxes as $strValue) {
        $arrKeys['id'][] = $strValue;
    }
    $dataHrdCompany = new cHrdCompany();
    $dataHrdCompany->deleteMultiple($arrKeys);
    $myDataGrid->message = $dataHrdCompany->strMessage;
} //deleteData
?>