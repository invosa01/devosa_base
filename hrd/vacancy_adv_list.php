<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../classes/datagrid_modified.php');
include_once('../includes/form2/form2.php');
include_once('../global/common_data.php');
$dataPrivilege = getDataPrivileges(
    "vacancy_adv_edit.php",
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
$strWordsDataEntry = getWords("data entry");
$strWordsAdvertisementList = getWords("advertisement list");
$strWordsAdvertisementReport = getWords("advertisement report");
$strWordsDate = getWords("advertisement date");
$strWordsDueDate = getWords("due date");
$strWordsReference = getWords("media");
$strWordsPosition = getWords("position");
$strWordsClearForm = getWords("clear form");
$db = new CdbClass;
$db->connect();
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
//if ($bolCanEdit)
//{
$f = new clsForm("form1", 1, "100%", "");
$f->disableFormTag();
//$f->caption = strtoupper('List OF Job Advertisment');
$f->addHidden("dataID", $strDataID);
//$f->addFieldSet(getWords("search criteria"), 1);
$f->addInput(
    getWords("advertisement date") . " " . getWords("from"),
    "date_from",
    date($_SESSION['sessionDateSetting']['php_format']),
    [],
    "date",
    true,
    true,
    true
);
$f->addInput(getWords("date to"), "date_thru", date($_SESSION['sessionDateSetting']['php_format']), [], "date", true, true, true);
$f->addInput(getWords("media"), "reference", "", [], "string", false, true, true);
$f->addInput(getWords("position"), "position", "", [], "string", false, true, true);
$f->addSubmit("btnSearch", getWords("show data"), ["onClick" => "javascript:doSearch()"], true, true, "", "", "");
$f->addSubmit("btnPrint", getWords("print"), ["onClick" => "javascript:printList()"], true, true, "", "", "");
$f->addSubmit("btnExportXLS", getWords("excel"), ["onClick" => "javascript:exportExcel()"], true, true, "", "", "");
$formInput = $f->render();
//}
//else
//  $formInput = "";
$bolPrint = false;
$bolExcel = false;
if (isset($_POST['btnPrint'])) {
  $bolPrint = true;
}
if (isset($_POST['btnExportXLS'])) {
  $bolExcel = true;
}
$myDataGrid = new cDataGridNew("form1", "DataGrid1", "100%", "", true, false);
$myDataGrid->disableFormTag();
$myDataGrid->caption = strtoupper(getWords("job advertisement"));
//$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
//$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", array("rowspan" => 2, 'width'=>30), array('nowrap'=>'')));
if ($bolPrint || $bolExcel) {
} else {
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "id", ["rowspan" => 2, 'width' => 30], ['align' => 'center', 'nowrap' => 'nowrap'])
  );
}
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("advertisement date"),
        "advertisement_date",
        ['nowrap' => ''],
        ['nowrap' => 'nowrap'],
        true,
        false,
        "",
        "formatDate()",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("media"),
        "reference",
        [],
        ["nowrap" => "nowrap"],
        true,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("due date"),
        "due_date",
        ['nowrap' => ''],
        ['nowrap' => 'nowrap'],
        true,
        false,
        "",
        "formatDate()",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        (getWords('position')),
        "position_list",
        [],
        ["nowrap" => "nowrap", "valign" => "top"],
        false,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        ("# " . getWords('candidate')),
        "candidate_total",
        [],
        ["nowrap" => "nowrap", "align" => "center"],
        false,
        false,
        "",
        "",
        "numeric",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column((getWords('candidate')), "candidate_list", [], [], false, false, "", "", "string", true, 12)
);
if (!($bolPrint || $bolExcel)) {
  if ($bolCanEdit) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "",
            "",
            ["rowspan" => 2, 'width' => 45],
            ['align' => 'center', 'nowrap' => 'nowrap'],
            false,
            false,
            "",
            "printGlobalEditLink()",
            "string",
            false
        )
    );
  }
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
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strCriteria = "";
$strCriteriaC = ""; // khusus kriteria untuk kandidat
if ($f->getValue('date_from') != '') {
  $strCriteria .= " AND advertisement_date >= '" . standardDateToSQLDate($f->getValue('date_from')) . "' ";
  $strCriteriaC .= " AND job_reference_date >= '" . standardDateToSQLDate($f->getValue('date_from')) . "' ";
}
if ($f->getValue('date_thru') != '') {
  $strCriteria .= " AND advertisement_date <= '" . standardDateToSQLDate($f->getValue('date_thru')) . "' ";
  $strCriteriaC .= " AND job_reference_date <= '" . standardDateToSQLDate($f->getValue('date_thru')) . "' ";
}
if ($f->getValue('reference') != '') {
  $strCriteria .= " AND upper(reference) LIKE '%" . strtoupper($f->getValue('reference')) . "%'";
  $strCriteriaC .= " AND upper(reference) LIKE '%" . strtoupper($f->getValue('reference')) . "%'";
}
if ($f->getValue('position') != '') {
  $strCriteria .= "
      AND id IN (
        SELECT DISTINCT id_advertisement FROM hrd_job_advertisement_detail 
        WHERE upper(position_name) LIKE '%" . strtoupper($f->getValue('position')) . "%'
      )
    ";
}
if ($bolExcel) {
  $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
  $myDataGrid->strFileNameXLS = "advertisement_list.xls";
  $myDataGrid->strTitle1 = getWords("advertisement list");
  $myDataGrid->strTitle2 = getWords("printed date") . ": " . date("d/m/Y h:i:s");
} elseif ($bolPrint) {
  $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_PRINT_HTML;
  $myDataGrid->strTitle1 = getWords("advertisement list");
  $myDataGrid->strTitle2 = getWords("printed date") . ": " . date("d/m/Y h:i:s");
}
// ambil kemungkinan candidate yang melamar sesuai jenis jobpostingnya
$arrCandidate = [];
$strSQLE = "
    SELECT * FROM hrd_candidate 
    WHERE NOT (job_reference_date is null)
      $strCriteriaC 
  ";
$res = $db->execute($strSQLE);
while ($row = $db->fetchrow($res)) {
  $strPos = trim(strtoupper($row['position']));
  $arrCandidate[$row['reference_type']][strtoupper(
      $row['reference']
  )][$row['job_reference_date']][$strPos][] = $row['candidate_name'];
}
$strSQLMaster = "
      SELECT *
      FROM hrd_job_advertisement
      WHERE 1=1 $strCriteria
  ";
$arrPosition = [];
// cari dulu detil jabatan
$strSQL = "
    SELECT * FROM hrd_job_advertisement_detail
    WHERE id_advertisement IN (
      SELECT id
      FROM hrd_job_advertisement
      WHERE 1=1 $strCriteria
    )
  ";
$res = $db->execute($strSQL);
while ($row = $db->fetchrow($res)) {
  $arrPosition[$row['id_advertisement']][] = $row['position_name'];
}
$strSQLCOUNT = "
    SELECT COUNT(*) AS total
    FROM 
    (
      $strSQLMaster
    ) AS x ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQLMaster);
foreach ($dataset as &$rowDb) {
  $arrPos = (isset($arrPosition[$rowDb['id']])) ? $arrPosition[$rowDb['id']] : [];
  $strPos = "";
  $str = ""; // daftar kandidat
  $strRef = strtoupper($rowDb['reference']);
  $rowDb['candidate_total'] = 0;
  foreach ($arrPos AS $i => $strTmp) {
    if ($strPos != "") {
      $strPos .= "&nbsp;<br>\n ";
    }
    $strPos .= $strTmp;
    $strTmpPos = trim(strtoupper($strTmp));
    if (isset($arrCandidate[$rowDb['ref_type']][$strRef][$rowDb['advertisement_date']][$strTmpPos])) {
      $arr = $arrCandidate[$rowDb['ref_type']][$strRef][$rowDb['advertisement_date']][$strTmpPos];
      $rowDb['candidate_total'] = count($arr);
      foreach ($arr AS $x => $strName) {
        $str .= ($str != "") ? ", " . $strName : $strName;
      }
    }
  }
  $rowDb['position_list'] = $strPos;
  $rowDb['candidate_list'] = $str;
}
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$strConfirmDelete = getWords("are you sure to delete this selected data?");
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = vacancyAdvSubmenu($strWordsAdvertisementList);
$strTemplateFile = "templates/vacancy_adv_list.html";
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
//$tbsPage->LoadTemplate("../templates/master2.html");
$tbsPage->Show();
//--------------------------------------------------------------------------------
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $tbl = new cModel("hrd_job_advertisement");
  if ($tbl->deleteMultiple($arrKeys)) {
    $myDataGrid->message = $tbl->strMessage;
  } else {
    $myDataGrid->errorMessage = $tbl->strMessage;
  }
} //deleteData
?>