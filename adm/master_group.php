<?php
include_once('../global/session.php');
include_once('../global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/adm/adm_group.php');
include_once("../global/common_function.php");
$dataPrivilege = getDataPrivileges("master_group.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
    die(getWords("view denied"));
}
$db = new CdbClass;
if ($bolCanEdit) {
    $f = new clsForm("formInput", 1, "100%", "");
    $f->caption = getWords("input data group");
    //$f->addHelp(getWords("help for")." ".$dataPrivilege['menu_name'], getHelps("master group"), 8, 167, 400, 300);
    $f->addHidden("dataID");
    $f->addInput(getWords("group code"), "dataCode", "", ["size" => 30], "string", true, true, true);
    $f->addInput(getWords("group name"), "dataName", "", ["cols" => 48, "rows" => 2], "string", false, true, true);
    $f->addSelect(getWords("group level"), "dataGroupRole", $GLOBALS['ARRAY_GROUP_ROLE'], "");
    $f->addCheckBox(getWords("active"), "dataActive", false, [], false);
    // $f->addCheckBox(getWords("get email"), "dataGetEmail", false, array(), false);
    // $f->addCheckBox(getWords("eligible approve 2"), "dataEligibleApprove2", false, array(), false);
    $f->addSubmit(
        "btnSave",
        getWords("save"),
        ["onClick" => "return confirm('" . getWords('do you want to save this entry?') . "');"],
        true,
        true,
        "",
        "",
        "saveData()"
    );
    $f->addButton("btnAdd", getWords("add new user"), ["onClick" => "javascript:myClient.editData(0);"]);
    $formInput = $f->render();
} else {
    $formInput = "";
}
$myDataGrid = new cDataGrid("formData", "DataGrid1");
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
if ($bolCanDelete) {
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID", "id_adm_group", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
    );
}
$myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no."), "", ['width' => '30'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("group code"), "code", ['width' => '130'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("group name"), "name", ['width' => ''], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("group level"), "group_role", ['width' => '120'], ['nowrap' => ''], true, false)
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("status"),
        "active",
        ['width' => '100'],
        ['align' => 'center', 'nowrap' => ''],
        true,
        false
    )
);
// $myDataGrid->addColumn(new DataGrid_Column(getWords("get email"), "get_email", array('width' => '100'), array('align' => 'center', 'nowrap' => ''), true, false));
// $myDataGrid->addColumn(new DataGrid_Column(getWords("eligible approve 2"), "eligible_approve2", array('width' => '100'), array('align' => 'center', 'nowrap' => ''), true, false));
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("total users"),
        "totalmember",
        ['width' => '110'],
        ['align' => 'center', 'nowrap' => ''],
        false,
        false
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column("", "", null, ['align' => 'center', 'nowrap' => ''], false, false, "", "printViewMember()")
);
if ($bolCanEdit) {
    $myDataGrid->addColumn(
        new DataGrid_Column("", "", null, ['align' => 'center', 'nowrap' => ''], false, false, "", "printEditLink()")
    );
    $myDataGrid->addColumn(
        new DataGrid_Column("", "", null, ['align' => 'center', 'nowrap' => ''], false, false, "", "printPermission()")
    );
    // $myDataGrid->addColumn(new DataGrid_Column("", "", null, array('align' => 'center', 'nowrap' => ''), false, false, "","printEmailSetting()"));
}
$myDataGrid->addRepeaterFunction("drawHiddenRow()");
if ($bolCanDelete) //parameters id,name,tipe button(button, submit), clientEvent, serverEvent
{
    $myDataGrid->addSpecialButton(
        "btnDelete",
        "btnDelete",
        "submit",
        getWords("delete"),
        "onClick=\"javascript:return myClient.confirmDelete();\"",
        "deleteData()"
    );
}
//parameters id,name,tipe button(button, submit), clientEvent, serverEvent
//$myDataGrid->addButton("btnSave","btnSave","submit","Save","onClick=\"javascript:if (confirm('Save data?')) return true\"","saveData()");
//event listener very important
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM adm_group";
$strSQL = "SELECT g.id_adm_group, g.code, g.name, g.group_role, g.active,
                     CASE WHEN u.totalmember IS NULL THEN 0 ELSE u.totalmember END AS totalmember
                FROM adm_group AS g
                  LEFT JOIN
                    (SELECT id_adm_group, COUNT(id_adm_user) AS totalmember FROM
                      (
                        SELECT a.id_adm_group, a.id_adm_user
                        FROM adm_user AS a INNER JOIN adm_group AS b ON a.id_adm_group = b.id_adm_group
                                              ) AS x
                      GROUP BY id_adm_group) AS u ";
$strSQL .= "ON g.id_adm_group = u.id_adm_group";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
$counter = 0;
foreach ($dataset as &$rowDb) {
    $counter++;
    $rowDb['group_role_ori'] = $rowDb['group_role'];
    $rowDb['group_role'] = $GLOBALS['ARRAY_GROUP_ROLE'][$rowDb['group_role']];
    if ($rowDb['active'] == 't') {
        $rowDb['active'] = "<input type=\"hidden\" name=\"detailActive$counter\" id=\"detailActive$counter\" value=\"t\" disabled>" . getWords(
                "active"
            );
    } else {
        $rowDb['active'] = "<input type=\"hidden\" name=\"detailActive$counter\" id=\"detailActive$counter\" value=\"f\" disabled>" . getWords(
                "inactive"
            );
    }
    //    if ($rowDb['eligible_approve2']=='t')
    //      $rowDb['eligible_approve2'] = "<input type=\"hidden\" name=\"detailEligibleApprove2$counter\" id=\"detailEligibleApprove2$counter\" value=\"t\" disabled>".getWords("eligible");
    //    else
    //      $rowDb['eligible_approve2'] = "<input type=\"hidden\" name=\"detailEligibleApprove2$counter\" id=\"detailEligibleApprove2$counter\" value=\"f\" disabled>".getWords("not eligible");
    //    if ($rowDb['get_email']=='t')
    //      $rowDb['get_email'] = "<input type=\"hidden\" name=\"detailGetEmail$counter\" id=\"detailGetEmail$counter\" value=\"t\" disabled>".getWords("eligible");
    //    else
    //      $rowDb['get_email'] = "<input type=\"hidden\" name=\"detailGetEmail$counter\" id=\"detailGetEmail$counter\" value=\"f\" disabled>".getWords("not eligible");
}
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$tbsPage = new clsTinyButStrong;
$strWordsViewMembers = getWords("view members");
$strWordsHideMembers = getWords("hide members");
//write this variable in every page
//$globalRelativeFolder = "../";
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("master group management");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
function printViewMember($params)
{
    extract($params);
    return "<a href=\"javascript:myClient.showDetail(" . $record['id_adm_group'] . ", $counter, true);\" id=\"hrefViewMember" . $counter . "\">" . "<span id=\"spanViewMember" . $counter . "\">" . getWords(
        'view members'
    ) . "</span></a>";
}

function printEditLink($params)
{
    extract($params);
    return "
      <input type=\"hidden\" name=\"detailID$counter\" id=\"detailID$counter\" value=\"" . $record['id_adm_group'] . "\">
      <input type=\"hidden\" name=\"detailCode$counter\" id=\"detailCode$counter\" value=\"" . $record['code'] . "\">
      <input type=\"hidden\" name=\"detailName$counter\" id=\"detailName$counter\" value=\"" . $record['name'] . "\">
      <input type=\"hidden\" name=\"detailGroupLevel$counter\" id=\"detailGroupLevel$counter\" value=\"" . $record['group_role_ori'] . "\">
      <a id=\"editdata-$counter\" class=\"edit-data\" href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

function printPermission($params)
{
    extract($params);
    return "<a href=\"master_group_permission.php?dataID=" . $record['id_adm_group'] . "\">" . getWords(
        'permission'
    ) . "</a>";
}

function printEmailSetting($params)
{
    extract($params);
    return "<a href=\"master_group_email_setting.php?dataID=" . $record['id_adm_group'] . "\">" . getWords(
        'email setting'
    ) . "</a>";
}

function drawHiddenRow($params) //$params harus ada
{
    global $bolCanDelete;
    global $bolCanEdit;
    extract($params);
    $strResult = "<tr valign=top style=\"display:none;background-color:#eeeeee\" id=\"detail$counter\">\n";
    if ($bolCanDelete) {
        $strResult .= "  <td colspan=3 align=\"center\"><strong>Members List</strong></td>\n";
    } else {
        $strResult .= "  <td colspan=2 align=\"center\"><strong>Members List</strong></td>\n";
    }
    if ($bolCanEdit) {
        $strResult .= "  <td colspan=7><div id=\"detailData$counter\"></div></td>\n";
    } else {
        $strResult .= "  <td colspan=5><div id=\"detailData$counter\"></div></td>\n";
    }
    $strResult .= "</div>\n";
    return $strResult;
}

// fungsi untuk menyimpan data
function saveData()
{
    global $f;
    $strDataActive = ($f->getValue('dataActive')) ? 't' : 'f';
    //$strDataEligibleApprove2 = ($f->getValue('dataEligibleApprove2')) ? 't' : 'f';
    //$strDataGetEmail = ($f->getValue('dataGetEmail')) ? 't' : 'f';
    $dataGroup = new cAdmGroup();
    // simpan data -----------------------
    $data = [
        "code"       => $f->getValue('dataCode'),
        "name"       => $f->getValue('dataName'),
        "group_role" => $f->getValue('dataGroupRole'),
        "active"     => $strDataActive,
        // "eligible_approve2" => $strDataEligibleApprove2,
        // "get_email" => $strDataGetEmail
    ];
    if ($f->getValue('dataID') == "") {
        // data baru
        $dataGroup->insert($data);
    } else {
        $dataGroup->update(["id_adm_group" => $f->getValue('dataID')], $data);
    }
    $f->message = $dataGroup->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
    global $myDataGrid;
    $arrKeys = [];
    foreach ($myDataGrid->checkboxes as $strValue) {
        $arrKeys['id_adm_group'][] = $strValue;
    }
    $dataGroup = new cAdmGroup();
    $dataGroup->deleteMultiple($arrKeys);
    $myDataGrid->message = $dataGroup->strMessage;
} //deleteData
?>
