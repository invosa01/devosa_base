<?php
include_once('../global/session.php');
include_once('../global.php');
include_once('../includes/datagrid/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/adm/adm_menu.php');
include_once('../global/handledata.php');
include_once("../global/common_function.php");
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
    die(getWords('view denied'));
}
$strAction = getRequestValue("act");
$strMenuLevel = getRequestValue("level");
$strDataID = getRequestValue("dataID");
$strDataModule = getPostValue("dataModule");
if ($strDataModule == "") {
    $strDataModule = getDataFromCookie("dataModule");
} else {
    setDataToCookie("dataModule", $strDataModule);
}
$arrModule = getModuleList($strDataModule);
$db = new CdbClass;
if ($strAction == "desc") {
    goSortOrder($db, $strDataModule, false);
} else if ($strAction == "asc") {
    goSortOrder($db, $strDataModule, true);
}
if ($bolCanEdit) {
    $f = new clsForm("formInput", 3, "100%", "");
    $f->caption = strtoupper(vsprintf(getWords("input data %s"), getWords("menu")));
    //$f->addHelp(getWords("help for")." ".$dataPrivilege['menu_name'], getHelps("master menu"), 8, 167, 400, 300);
    $f->addHidden("dataID");
    $f->addHidden("dataIcon");
    $f->addSelect(
        getWords("module"),
        "dataModule",
        $arrModule,
        ["onChange" => "javascript:myClient.doRefreshMenu()"],
        "string",
        true,
        true,
        true
    );
    $f->addInput(getWords("menu name"), "dataName", "", null, "string", true, true, true);
    $f->addInput(getWords("page name"), "dataPageName", "", null, "string", true, true, true);
    $f->addInput(getWords("php file"), "dataPhpFile", "", null, "string", false, true, true);
    //mengambil data untuk selectbox dataParentID
    if (!isset($_POST['btnSave'])) {
        $arrParentMenu = getParentMenu($strDataModule);
    } else
        //jika ada action save maka biarkan kosong dahulu, karena nanti akan di ambil lagi setelah data di insert/update
        //lihat pada function saveData();
    {
        $arrParentMenu = [];
    }
    $f->addSelect(getWords("Parent Menu"), "dataParentID", $arrParentMenu, [], "string", false, true, true);
    if (isset($_POST['dataIcon'])) {
        $icon = $_POST['dataIcon'];
    } else {
        $icon = "";
    }
    $f->addFile(
        getWords("icon file"),
        "dataIconFile",
        $icon,
        [],
        "string",
        false,
        true,
        true,
        "",
        "<div id=\"divIcon\" style=\"display:none\"><img id=\"imgIcon\" border=0 /><br /><a href=\"javascript:myClient.modifyIcon()\">" . getWords(
            "edit"
        ) . "</a></div>",
        true,
        null,
        "../images/icons/"
    );
    $f->addTextArea(getWords("note"), "dataNote", "", ["cols" => 50, "rows" => 3], "string", false, true, true);
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
    $f->addButton("btnAdd", getWords("add new menu"), ["onClick" => "javascript:myClient.editData(0);"]);
    $formInput = $f->render();
} else {
    $formInput = "";
}
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
if ($bolCanDelete) {
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID", "id_adm_menu", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
    );
}
//$myDataGrid->addColumn(new DataGrid_Column("", "", array('width' => '30'), array('align'=>'center', 'nowrap' => ''), false, "printCheckBoxAll()", "printCheckBox()"));
$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("menu name"),
        "name",
        ['width' => ''],
        ['nowrap' => 'nowrap'],
        true,
        true,
        "",
        "printEditLink()"
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("icon file"), "image_icon", ['width' => '32'], ['align' => 'center'])
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("menu level"),
        "menu_level",
        ['width' => '80'],
        ['nowrap' => 'nowrap', 'align' => 'center'],
        true,
        false
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("order"),
        "sequence_no",
        ['width' => '40'],
        ['nowrap' => 'nowrap', 'align' => 'center'],
        true,
        false
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("published"), "visible", ['width' => '75'], ['align' => 'center'], false, false)
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("order"),
        "order",
        ['width' => '50'],
        ['align' => 'center', 'nowrap' => ''],
        false,
        false
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("page name"), "page_name", ['width' => '140'], ['nowrap' => 'nowrap'])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("php file"), "php_file", ['width' => '150'], ['nowrap' => 'nowrap'])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("parent menu"), "parent_menu_name", ['width' => ''], ['nowrap' => 'nowrap'])
);
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
    "Export Excel",
    $dataPrivilege['menu_name'] . ".xls",
    getWords($dataPrivilege['menu_name'])
);
$myDataGrid->getRequest();
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "
    SELECT COUNT(*) AS total 
      FROM 
        (SELECT m.*, p.name AS parent_menu_name 
          FROM adm_menu AS m LEFT JOIN adm_menu AS p
            ON m.parent_id_adm_menu = p.id_adm_menu
            WHERE m.id_adm_module = '$strDataModule' 
         ORDER BY m.menu_level, m.sequence_no) AS x 
      WHERE 1=1 ";
$strSQL = "
    SELECT * 
      FROM 
        (SELECT m.*, p.name AS parent_menu_name
          FROM adm_menu AS m LEFT JOIN adm_menu AS p
            ON m.parent_id_adm_menu = p.id_adm_menu
            WHERE m.id_adm_module = '$strDataModule' 
         ORDER BY m.menu_level, m.sequence_no) AS x 
      WHERE 1=1 ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$predataset = $myDataGrid->getData($db, $strSQL);
reorderMenu($predataset, "", 0, $dataset);
$counter = 0;
if (count($dataset) == 0) {
    $dataset = [];
}
foreach ($dataset as &$rowDb) {
    $counter++;
    if ($rowDb['icon_file'] != "") {
        $rowDb['image_icon'] = "<img src='" . $GLOBALS['globalRelativeFolder'] . "images/icons/" . $rowDb['icon_file'] . "' height=16 width=16 border=0 />&nbsp;";
    } else {
        $rowDb['image_icon'] = "";
    }
    $rowDb['order'] = "<a href=\"master_menu.php?dataID=" . $rowDb['id_adm_menu'] . "&act=asc&level=" . $rowDb['menu_level'] . "\"><img src=../images/asc.gif width=11 height=11 border=0></a><a href=\"master_menu.php?dataID=" . $rowDb['id_adm_menu'] . "&act=desc&level=" . $rowDb['menu_level'] . "\"><img src=../images/desc.gif width=11 height=11 border=0></a>";
    switch ($rowDb['menu_level']) {
        case 0 :
            $rowDb['menu_level'] = "<strong>" . getWords("main menu") . "</strong>";
            break;
        case 1 :
            $rowDb['menu_level'] = getWords("menu item");
            break;
        default :
            $rowDb['menu_level'] = "<em>" . getWords("submenu level-") . ($rowDb['menu_level'] - 1) . "</em>";
    }
    if ($rowDb['visible'] == 't') {
        $rowDb['visible'] = "<div id=\"visibleLink_" . $rowDb['id_adm_menu'] . "\"><a href=\"javascript:myClient.updateVisibleStatus(" . $rowDb['id_adm_menu'] . ")\"><img src=\"../images/publish.png\" border=\"0\" width=\"12\" height=\"12\" /></a></div>";
    } else {
        $rowDb['visible'] = "<div id=\"visibleLink_" . $rowDb['id_adm_menu'] . "\"><a href=\"javascript:myClient.updateVisibleStatus(" . $rowDb['id_adm_menu'] . ")\"><img src=\"../images/cross.png\" border=\"0\" width=\"12\" height=\"12\" /></a></div>";
    }
}
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$myDataGrid->caption = strtoupper(vsprintf(getWords("list of %s"), getWords("menu")));
$DataGrid = $myDataGrid->render();
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$strWordMenuList = strtoupper(vsprintf(getWords("list of %s"), getWords("menu")));
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("menu structure management");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
function printEditLink($params)
{
    extract($params);
    $strResult = "
      <input type=\"hidden\" name='detailID$counter' id='detailID$counter' value=\"" . $record['id_adm_menu'] . "\">
      <input type=\"hidden\" name='detailParentID$counter' id='detailParentID$counter' value=\"" . $record['parent_id_adm_menu'] . "\">
      <input type=\"hidden\" name='detailName$counter' id='detailName$counter' value=\"" . $record['name'] . "\">
      <input type=\"hidden\" name='detailPageName$counter' id='detailPageName$counter' value=\"" . $record['page_name'] . "\">
      <input type=\"hidden\" name='detailPhpFile$counter' id='detailPhpFile$counter' value=\"" . $record['php_file'] . "\">
      <input type=\"hidden\" name='detailNote$counter' id='detailNote$counter' value=\"" . $record['note'] . "\">";
    if ($record['icon_file'] != "") {
        $strResult .= "<input type=hidden name='detailIconFile$counter' id='detailIconFile$counter' value=\"" . $record['icon_file'] . "\">";
    }
    return $strResult . "
      <a href=\"javascript:myClient.editData($counter)\">" . $record['name'] . "</a>";
}

// fungsi untuk menyimpan data
function saveData()
{
    global $db;
    global $f;
    global $strDataID;
    global $strDataModule;
    // cek validasi -----------------------
    if ($f->value('dataName') == "") {
        $f->message = getWords('empty_code');
        return false;
    }
    //cari dahulu sequence no dan menu_level dari parent menunya, jika ada
    $sequence_no = 0;
    $menu_level = 0;
    if ($f->value('dataParentID') == '0') {
        $strDataParentID = 'null';
    } else {
        $strDataParentID = "'" . $f->value('dataParentID') . "'";
    }
    if ($db->connect()) {
        if ($f->getValue('dataParentID') == "0") {
            $strSQL = "
          SELECT MAX(sequence_no) AS sequence_no, -1 AS menu_level
            FROM adm_menu 
            WHERE id_adm_module = '$strDataModule'";
        } else {
            $strSQL = "
          SELECT MAX(c.sequence_no) AS sequence_no, MAX(m.menu_level) AS menu_level 
            FROM adm_menu AS m LEFT JOIN adm_menu AS c
              ON m.id_adm_menu = c.parent_id_adm_menu
            WHERE m.id_adm_module = '$strDataModule' AND m.id_adm_menu = $strDataParentID";
        }
        $res = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($res)) {
            $sequence_no = intval($rowDb['sequence_no']) + 1;
            $menu_level = intval($rowDb['menu_level']) + 1;
        } else {
            return false;
        }
    }
    // simpan data -----------------------
    if ($strDataID == "") {
        // data baru
        $strSQL = "
        INSERT INTO \"adm_menu\" 
            (name,id_adm_module, sequence_no, page_name, php_file, icon_file, 
             parent_id_adm_menu, menu_level, note) 
          VALUES('" . $f->value('dataName') . "','" . $f->value('dataModule') . "', '$sequence_no',
                 '" . $f->getValue('dataPageName') . "','" . $f->getValue('dataPhpFile') . "',
                 '" . $f->getValue('dataIconFile') . "', $strDataParentID,
                 '$menu_level', '" . $f->getValue('dataNote') . "')";
    } else {
        $strSQL = "
          SELECT parent_id_adm_menu
            FROM adm_menu 
            WHERE id_adm_menu = '$strDataID'";
        $res = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($res)) {
            $old_parent_menu = intval($rowDb['parent_id_adm_menu']);
        }
        $strSQL = "
        UPDATE adm_menu 
          SET name = '" . $f->value('dataName') . "',
              page_name = '" . $f->value('dataPageName') . "',
              php_file = '" . $f->value('dataPhpFile') . "',
              icon_file = '" . $f->value('dataIconFile') . "',
              parent_id_adm_menu = $strDataParentID, 
              note = '" . $f->getValue('dataNote') . "',
              menu_level = '$menu_level' ";
        //update sequence only if you change the parent menu(renumeration the menu)
        if ($old_parent_menu != $f->value('dataParentID')) {
            $strSQL .= ", sequence_no = '$sequence_no' ";
        }
        $strSQL .= "WHERE id_adm_menu = '$strDataID'; ";
    }
    if (executeSaveSQL($strSQL, getWords("menu"), $f->message)) {
        //data Parent Menu harus direfresh setelah di insert donk
        $f->objects['dataParentID']['values'] = getParentMenu($strDataModule, $f->getValue('dataParentID'));
        return true;
    }
    return false;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
    global $f;
    global $myDataGrid;
    $strSQL = "";
    foreach ($myDataGrid->checkboxes as $strValue) {
        $strSQL .= "DELETE FROM adm_menu WHERE parent_id_adm_menu = '$strValue';";
        $strSQL .= "DELETE FROM adm_menu WHERE id_adm_menu = '$strValue';";
    }
    if (executeDeleteSQL($strSQL, getWords("menu"), $myDataGrid->message)) {
        //hapus dari memory objectform select dataParentID
        foreach ($f->objects['dataParentID']['values'] as $key => $value) {
            if ($value['value'] == $strValue) {
                unset($f->objects['dataParentID']['values'][$key]);
            }
        }
        return true;
    }
    return false;
} //deleteData
// fungsi untuk generate daftar modul2 yang ada
function getModuleList(&$default)
{
    $arrModule = [];
    $db = new CdbClass;
    if ($db->connect()) {
        $strSQL = "SELECT id_adm_module, name FROM adm_module order by sequence_no, id_adm_module";
        $res = $db->execute($strSQL);
        $isFirst = true;
        while ($rowDb = $db->fetchrow($res)) {
            if ($default == "" || $default == "0") {
                if ($isFirst) {
                    $isFirst = false;
                    $default = $rowDb['id_adm_module'];
                    setcookie("dataModuleText", strtoupper($rowDb['name']), time() + 3600);  /* expire in 1 hour*/
                    setcookie(
                        "formDataModule",
                        strtoupper($rowDb['id_adm_module']),
                        time() + 3600
                    );  /* expire in 1 hour*/
                    $arrModule[] = ["value" => $rowDb['id_adm_module'], "text" => $rowDb['name'], "selected" => true];
                } else {
                    $arrModule[] = ["value" => $rowDb['id_adm_module'], "text" => $rowDb['name'], "selected" => false];
                }
            } else if ($default == $rowDb['id_adm_module']) {
                setcookie("dataModuleText", strtoupper($rowDb['name']), time() + 3600);  /* expire in 1 hour*/
                setcookie("formDataModule", strtoupper($rowDb['id_adm_module']), time() + 3600);  /* expire in 1 hour*/
                $arrModule[] = ["value" => $rowDb['id_adm_module'], "text" => $rowDb['name'], "selected" => true];
            } else {
                $arrModule[] = ["value" => $rowDb['id_adm_module'], "text" => $rowDb['name'], "selected" => false];
            }
        }
    }
    return $arrModule;
}//getModuleList
//recursively re-order the menu
function reorderMenu($arrMenu, $id_menu = "", $menu_level, &$arrResult)
{
    $next_menu_level = $menu_level + 1;
    foreach ($arrMenu as $key => $value) {
        if ($value['menu_level'] == $menu_level && $value['parent_id_adm_menu'] == $id_menu) {
            $arrResult[] = $value;
            reorderMenu($arrMenu, $value['id_adm_menu'], $next_menu_level, $arrResult);
        }
    }
    return $arrResult;
}

//recursively re-order the menu
function restructureMenu($arrMenu, $id_menu = "", $menu_level, &$arrResult)
{
    $next_menu_level = $menu_level + 1;
    foreach ($arrMenu as $key => $value) {
        if ($value['menu_level'] == $menu_level && $value['parent_id_adm_menu'] == $id_menu) {
            $dashes = "";
            for ($i = 0; $i < $value['menu_level']; $i++) {
                if ($i == 0) {
                    $dashes = "&#9492;&#9472;&nbsp;";
                } else {
                    $dashes = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $dashes;
                }
            }
            if ($menu_level == 0) {
                $arrResult[] = ["value" => $value['id_adm_menu'], "text" => $value['name'], "selected" => false];
            } else {
                $arrResult[] = [
                    "value" => $value['id_adm_menu'],
                    "text" => $dashes . $value['name'],
                    "selected" => false
                ];
            }
            restructureMenu($arrMenu, $value['id_adm_menu'], $next_menu_level, $arrResult);
        }
    }
    return $arrResult;
}

function getParentMenu($strDataModule, $default = "")
{
    $arrResult = [];
    if ($default == "") {
        $arrResult[] = ["value" => "0", "text" => "[Main Menu - No Parent]", "selected" => true];
    } else {
        $arrResult[] = ["value" => "0", "text" => "[Main Menu - No Parent]", "selected" => false];
    }
    $db = new CdbClass;
    if ($db->connect()) {
        $strSQL = "SELECT * FROM adm_menu AS m WHERE id_adm_module = '$strDataModule' ORDER BY menu_level, sequence_no";
        $isFirst = true;
        $arrMenu = [];
        $res = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($res)) {
            $arrMenu[] = $rowDb;
        }
        restructureMenu($arrMenu, "", 0, $arrResult);
        foreach ($arrResult as &$data) {
            if ($data['value'] == $default) {
                $data['selected'] = true;
                break;
            }
        }
        //print_r($arrResult);
    }
    return $arrResult;
}//getParentMenu
// fungsi untuk menaikan field
function goSortOrder($db, $idModule, $modeAsc)
{
    global $strDataID;
    global $bolCanEdit;
    global $myDataGrid;
    if (!$bolCanEdit) {
        $myDataGrid->message = getWords('edit_denied');
        return false;
    }
    if ($db->connect()) {
        $strSQL = "
        SELECT * FROM adm_menu 
          WHERE id_adm_module = '$idModule' AND id_adm_menu = '$strDataID'";
        $res = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($res)) {
            if ($modeAsc) {
                $strSQL = "
            SELECT * FROM adm_menu 
              WHERE id_adm_module = '$idModule' AND sequence_no < '" . $rowDb['sequence_no'] . "'
              ORDER BY sequence_no DESC LIMIT 1";
            } else {
                $strSQL = "
            SELECT * FROM adm_menu 
              WHERE id_adm_module = '$idModule' AND sequence_no > '" . $rowDb['sequence_no'] . "'
              ORDER BY sequence_no ASC LIMIT 1";
            }
            $res = $db->execute($strSQL);
            if ($rowDb2 = $db->fetchrow($res)) {
                $strSQL = "
            UPDATE adm_menu SET sequence_no = '" . $rowDb2['sequence_no'] . "'
              WHERE id_adm_menu = '" . $rowDb['id_adm_menu'] . "';";
                $strSQL .= "
            UPDATE adm_menu SET sequence_no = '" . $rowDb['sequence_no'] . "'
              WHERE id_adm_menu = '" . $rowDb2['id_adm_menu'] . "';";
                $res = $db->execute($strSQL);
                setDataCookie("");
            }
        }
    }
}

?>