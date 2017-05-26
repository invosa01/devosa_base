<?php
//by Farhan (21 Agustus 2009)
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_news.php');
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
if ($bolCanEdit) {
    $f = new clsForm("formInput", 1, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    $f->addInput(getWords("event date"), "date_event", date($_SESSION['sessionDateSetting']['php_format']), ["style" => "width:80"], "date");
    $f->addSelect(
        getWords("company"),
        "id_company",
        getDataListCompany(getPostValue('id_company')),
        ["style" => "width:200"],
        "",
        false
    );
    $f->addTextArea(
        getWords("news"),
        "news",
        "",
        ["cols" => 97, "rows" => 2, "maxlength" => 255],
        "string",
        false,
        true,
        true
    );
    $f->addInput(getWords("place"), "place", "", [], "string", true, true, true);
    $f->addCheckBox(
        getWords("active"),
        "active",
        null,
        ["onChange" => "javascript:myClient.setActive(this.checked)"],
        null,
        false,
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
    $f->addButton("btnAdd", getWords("add new"), ["onClick" => "javascript:myClient.editData(0);"]);
    $formInput = $f->render();
} else {
    $formInput = "";
}
$myDataGrid = new cDataGrid("formData", "DataGrid1");
$myDataGrid->caption = strtoupper($strWordsLISTOF . " " . $dataPrivilege['menu_name']);
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("created"), "created", ['width' => '100'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("date"), "date_event", ['width' => '100'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("company"), "company_code", ['width' => '100'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("news"), "news", null, ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("place"), "place", null, ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("active"),
        "active",
        ["width" => 50],
        ['align' => 'center'],
        true,
        false,
        "",
        "printIsActive()"
    )
);
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
        "Delete",
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
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_news WHERE 1=1 $strKriteriaCompany";
$strSQL = "SELECT t0.*, company_code FROM hrd_news AS t0 LEFT JOIN hrd_company AS t1 ON t0.id_company = t1.id
                   WHERE 1=1 $strKriteriaCompany";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("manage news data");
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
      <input type=hidden name='detailCreated$counter' id='detailCreated$counter' value='" . $record['created'] . "' />
      <input type=hidden name='detailCompany$counter' id='detailCompany$counter' value='" . $record['company_code'] . "' />
      <input type=hidden name='detailEventDate$counter' id='detailEventDate$counter' value='" . $record['date_event'] . "' />
      <input type=hidden name='detailNews$counter' id='detailNews$counter' value='" . $record['news'] . "' />
	  <input type=hidden name='detailDuration$counter' id='detailDuration$counter' value='" . $record['duration'] . "' />
	  <input type=hidden name='detailPlace$counter' id='detailPlace$counter' value='" . $record['place'] . "' />
      <input type=hidden name='detailActive$counter' id='detailActive$counter' value='" . $record['active'] . "' />
      <a id=\"editdata-$counter\" class=\"edit-data\" href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

// fungsi untuk menyimpan data
function saveData()
{
    global $f;
    global $isNew;
    $strmodified_byID = $_SESSION['sessionUserID'];
    $dataHrdNews = new cHrdNews();
    $dateEvent = standardDateToSQLDateNew($f->getValue('date_event'), $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']);
    $data = [
        "date_event" => $dateEvent,
        "id_company" => $f->getValue('id_company'),
        "news"       => $f->getValue('news'),
        "place"      => $f->getValue('place'),
        "active"     => ($f->getValue('active')) ? 't' : 'f'
    ];
    // simpan data -----------------------
    $bolSuccess = false;
    if ($isNew) {
        // data baru
        $bolSuccess = $dataHrdNews->insert($data);
    } else {
        $bolSuccess = $dataHrdNews->update(/*pk*/
            "id='" . $f->getValue('dataID') . "'", /*data to update*/
            $data
        );
    }
    if ($bolSuccess) {
        if ($isNew) {
            $f->setValue('dataID', $dataHrdNews->getLastInsertId());
        } else {
            $f->setValue('dataID', $f->getValue('dataID'));
        }
    }
    $f->message = $dataHrdNews->strMessage;
} // saveData
//funngsi untuk menampilkan tanda ceklist bila statusnya true (farhan)
function printIsActive($params)
{
    extract($params);
    if ($value == 't') {
        return "&radic;";
    } else {
        return "-";
    }
}

// fungsi untuk menghapus data
function deleteData()
{
    global $myDataGrid;
    $arrKeys = [];
    foreach ($myDataGrid->checkboxes as $strValue) {
        $arrKeys['id'][] = $strValue;
    }
    $dataHrdNews = new cHrdNews();
    $dataHrdNews->deleteMultiple($arrKeys);
    $myDataGrid->message = $dataHrdNews->strMessage;
} //deleteData
?>