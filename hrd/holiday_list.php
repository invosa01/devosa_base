<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_calendar.php');
$dataPrivilege = getDataPrivileges(
    "working_calendar.php",
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
$strWordsEventList = getWords("event list");
$strWordsWorkingCalendar = getWords("working calendar");
$db = new CdbClass;
$dtNow = getdate();
(isset($_REQUEST['dataYear'])) ? $strDataYear = $_REQUEST['dataYear'] : $strDataYear = $dtNow['year'];
if (!is_numeric($strDataYear)) {
  $strDataYear = $dtNow['year'];
}
$myDataGrid = new cDataGrid("formData", "DataGrid1");
$myDataGrid->caption = strtoupper($strWordsLISTOF . " " . getWords("event"));
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->setPageLimit("all");
$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("event"), "holiday", ['width' => '150'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", ""));
//In the common_variable files, the variable name for the array of event category is ARRAY_HOLIDAY_TYPE
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("category"),
        "category",
        ['width' => '60'],
        ['align' => 'center', 'nowrap' => ''],
        true,
        true,
        "",
        "printEventCategory()"
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("holiday"),
        "status",
        ['width' => '60'],
        ['align' => 'center', 'nowrap' => ''],
        true,
        false,
        "",
        "printIsHoliday()"
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("leave"),
        "leave",
        ['width' => '60'],
        ['align' => 'center', 'nowrap' => ''],
        true,
        false,
        "",
        "printIsLeave()"
    )
);
$myDataGrid->addButtonExportExcel(
    "Export Excel",
    "event_list_" . $strDataYear . ".xls",
    getWords("event list") . " " . $strDataYear
);
$myDataGrid->getRequest();
$myDataGrid->pageSortBy = "holiday";
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_calendar WHERE EXTRACT(year FROM holiday) = '$strDataYear' ";
$strSQL = "SELECT * FROM hrd_calendar WHERE EXTRACT(year FROM holiday) = '$strDataYear' ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords("event");
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
function printEventCategory($params)
{
  global $ARRAY_HOLIDAY_TYPE;
  extract($params);
  return $value . "-" . getWords($ARRAY_HOLIDAY_TYPE[$value]);
}

function printIsHoliday($params)
{
  extract($params);
  return ($value == 't') ? "*" : "";
}

function printIsLeave($params)
{
  extract($params);
  return ($value == 't') ? "*" : "";
}

?>