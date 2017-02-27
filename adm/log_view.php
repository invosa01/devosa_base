<?php
include_once('../global/session.php');
include_once('../global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../classes/adm/adm_userlog.php');
include_once('../classes/adm/adm_group.php');
include_once("../global/common_function.php");
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(getWords("view denied"));
}
$db = new CdbClass;
$myDataGrid = new cDataGrid("formData", "DataGrid1");
$myDataGrid->caption = getWords(strtoupper(vsprintf(getWords("list of %s"), getWords("log table"))));
$myDataGrid->setPageLimit("50");
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id_adm_userlog", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("date / time"), "action_date", ["width" => 145], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("login"), "login_name", ["width" => 75], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("employee name"), "employee_name", ["width" => 75], ['nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("user group"),
        "id_adm_group",
        ["width" => 75],
        ['nowrap' => ''],
        "",
        "",
        "",
        "printUserGroup()"
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("activity"), "action_type", ["width" => 75], ['nowrap' => ''], true, false)
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("status"),
        "status",
        ["width" => 75],
        ['nowrap' => ''],
        "",
        "",
        "",
        "printRequestStatus()"
    )
);
//$myDataGrid->addColumn(new DataGrid_Column(getWords("page"), "php_file", array("width" => 120), array('nowrap' => '')));
$myDataGrid->addColumn(new DataGrid_Column(getWords("menu name"), "menu_name", ["width" => 150], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("ip address"), "ip_address", ["width" => 75], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "message"));
//if ($bolCanDelete)
//  $myDataGrid->addSpecialButton("btnDelete","btnDelete","submit", getWords("delete"), "onClick=\"javascript:if (!confirm('".getWords('are you sure to delete selected record(s)?')."')) return false\"","deleteData()");
$myDataGrid->addButtonExportExcel(
    getWords("export excel"),
    $dataPrivilege['menu_name'] . ".xls",
    getWords($dataPrivilege['menu_name'])
);
//event listener very important
$myDataGrid->pageSortBy = "action_date DESC";
$myDataGrid->getRequest();
//--------------------------------
//Set DataSource
$strSQLCOUNT = "
    SELECT COUNT(*) AS total FROM adm_userlog AS l";
$strSQL = "
    SELECT * FROM 
      (
        SELECT l.*, u.login_name, u.employee_id, u.name as employee_name, u.id_adm_group, m.name AS menu_name
          FROM adm_userlog AS l
          LEFT JOIN adm_user AS u ON l.id_adm_user = u.id_adm_user
          LEFT JOIN adm_menu AS m ON l.id_adm_menu = m.id_adm_menu
      ) AS x ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
while (list($key, $val) = each($dataset)) {
  $data = &$dataset[$key];
  $data['action_type'] = $ARRAY_ACTIVITY_TYPE[$data['action_type']];
}
$myDataGrid->bind($dataset);
$dataGrid = $myDataGrid->render(); //kalo pake TBS [var.DataGrid] harus ada
//----MAIN PROGRAM -----------------------------------------------------
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (!$dataPrivilege['icon_file']) {
  $dataPrivilege['icon_file'] = "blank.png";
}
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("database management");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
// fungsi untuk mennampilkan caption dari kode user group
function printUserGroup($params)
{
  extract($params);
  $tbl = new cAdmGroup();
  if ($value == "") {
    return "";
  }
  $arrGroup = $tbl->findAll("id_adm_group = " . $value . "", "id_adm_group, name", "", null, 1, "id_adm_group");
  if (isset($arrGroup[$value]['name'])) {
    return $arrGroup[$value]['name'];
  } else {
    return "";
  }
} //printUserGroup
// fungsi untuk menampilkan caption dari kode request status
function printRequestStatus($params)
{
  extract($params);
  global $ARRAY_REQUEST_STATUS;
  global $ARRAY_ACTIVITY_TYPE;
  if ($record['action_type'] != $ARRAY_ACTIVITY_TYPE[ACTIVITY_EDIT] || !isset($ARRAY_REQUEST_STATUS[$value])) {
    return "";
  } else {
    return $ARRAY_REQUEST_STATUS[$value];
  }
} //peintRequestStatus
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id_adm_userlog'][] = $strValue;
  }
  $dataUserLog = new cAdmUserLog();
  $dataUserLog->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataUserLog->strMessage;
} //deleteData
?>