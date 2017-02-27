<?php
include_once('../global/session.php');
include_once("../global.php");
include_once("../global/common_data.php");
include_once("../global/common_function.php");
//include_once('adminFunc.php');
include_once('../global/handledata.php');
$dataPrivilege = getDataPrivileges("master_group.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$strDataID = getRequestValue("dataID");
if ($strDataID == "") {
  header("location:master_group.php");
}
$strMessage = "";
$strWordCancel = getWords("cancel");
$strWordCancelEntry = getWords("cancel entry?");
$db = new CdbClass;
if ($db->connect()) {
  if (isset($_POST['btnCancel'])) {
    header("location:master_group.php");
  }
  if (isset($_POST['btnSave'])) {
    saveData($strDataID);
  }
  $strDataPrivileges = getMenuPrivileges($db, $strDataID);
}
$tbsPage = new clsTinyButStrong;
$strWordListofUser = strtoupper(getWords("list of user"));
//write this variable in every page
$errMessage = "";
$strWordSave = getWords("save");
$strPageTitle = getWords("manage group permission");
$strPageDesc = getWords("manage group permission, check to add permission");
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
//--------------------------------------------------------------------------------
//recursively re-order the menu
function reorderMenu($arrMenu, $id_menu = "", $menu_level, &$arrResult)
{
  $next_menu_level = $menu_level + 1;
  foreach ($arrMenu as $key => $value) {
    if ($value['menu_level'] == $menu_level && $value['parent_id_adm_menu'] == $id_menu) {
      $arrResult[$key] = $value;
      reorderMenu($arrMenu, $value['id_adm_menu'], $next_menu_level, $arrResult);
    }
  }
  return $arrResult;
}

function getMenu($db, $strDataID)
{
  $strSQL = "SELECT a.parent_id_adm_menu, a.id_adm_menu, a.id_adm_module, a.name, a.sequence_no, a.page_name, ";
  $strSQL .= "a.menu_level, a.php_file, b.name AS modulename, g.view, g.edit, g.delete, g.check, g.approve, g.id_adm_group AS idgroup ";
  $strSQL .= "FROM adm_menu AS a INNER JOIN adm_module AS b ON a.id_adm_module = b.id_adm_module ";
  $strSQL .= "LEFT JOIN (SELECT * FROM adm_group_menu WHERE id_adm_group = '$strDataID' ) AS g ";
  $strSQL .= "ON g.id_adm_menu = a.id_adm_menu ";
  $strSQL .= "WHERE a.visible = 't' ";
  $strSQL .= "ORDER BY b.sequence_no, a.id_adm_module, a.sequence_no";
  $arrMenu = [];
  if ($db->connect()) {
    $res = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($res)) {
      $rowDb['viewchecked'] = ($rowDb['view'] == 't' && $rowDb['menu_level'] != 0) ? "checked" : "";
      $rowDb['editchecked'] = ($rowDb['edit'] == 't' && $rowDb['menu_level'] != 0) ? "checked" : "";
      $rowDb['deletechecked'] = ($rowDb['delete'] == 't' && $rowDb['menu_level'] != 0) ? "checked" : "";
      $rowDb['checkchecked'] = ($rowDb['check'] == 't' && $rowDb['menu_level'] != 0) ? "checked" : "";
      $rowDb['approvechecked'] = ($rowDb['approve'] == 't' && $rowDb['menu_level'] != 0) ? "checked" : "";
      if ($rowDb['php_file'] == null) {
        $rowDb['php_file'] = '';
      }
      $arrMenu[$rowDb['id_adm_menu']] = $rowDb;
    }
  }
  $arrResult = [];
  reorderMenu($arrMenu, "", 0, $arrResult);
  return $arrResult;
}

//mencari maximum menu level
function getMaxMenuLevel($db)
{
  $strSQL = "SELECT MAX(menu_level) AS maxlevel FROM adm_menu";
  if ($db->connect()) {
    $res = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($res)) {
      return intval($rowDb['maxlevel']);
    }
  }
  return 0;
}

function printGridHeader($maxMenuLevel)
{
  $strResult = "
          <tr>
            <th width=\"140\" rowspan=\"2\">Module Name</th>
            <th width=\"170\" rowspan=\"2\">Main Menu</th>";
  for ($i = 1; $i <= $maxMenuLevel; $i++) {
    $strResult .= "
            <th width=\"160\" rowspan=\"2\">SubMenu Level-" . $i . "</th>";
  }
  $strResult .= "
            <th rowspan=\"2\">PHP file</th>
            <th colspan=\"5\">Privileges</th>
          </tr>
          <tr>
            <th width=\"40\">view</th>
            <th width=\"40\">edit</td>
            <th width=\"40\">delete</td>
            <th width=\"40\">check</td>
            <th width=\"40\">approve</td>
          </tr>";
  return $strResult;
}

function getMenuPrivileges($db, $strDataID)
{
  // cetak all module
  $strResult = "";
  // Cetak Module
  $lastModule = "/undefined/";
  $arrMenu = getMenu($db, $strDataID);
  $maxMenuLevel = getMaxMenuLevel($db);
  refreshPrivileges($arrMenu, $maxMenuLevel);
  $strResult = printGridHeader($maxMenuLevel);
  foreach ($arrMenu as $rowDb) {
    if ($lastModule != $rowDb['modulename']) {
      $strRows = "";
      $strResult .= "<tr style=\"background-color : #999999\">";
      $strResult .= "  <td style=\"color:white\" colspan=\"" . ($maxMenuLevel + 3) . "\"> <strong>" . $rowDb['modulename'] . "&nbsp;</strong></td>";
      $strResult .= "  <td align=\"center\"><div class=\"checkbox\"><label><input class=\"checkbox-inline\" name=\"chkIDview_" . $rowDb['id_adm_module'] . "\" id=\"chkIDview_" . $rowDb['id_adm_module'] . "\" type=\"checkbox\" value='noSave' onClick=\"checkClick(this);\" [chkIDViewModule_" . $rowDb['id_adm_module'] . "]></label></div></td>";
      $strResult .= "  <td align=\"center\"><div class=\"checkbox\"><label><input class=\"checkbox-inline\" name=\"chkIDedit_" . $rowDb['id_adm_module'] . "\" id=\"chkIDedit_" . $rowDb['id_adm_module'] . "\" type=\"checkbox\" value='noSave' onClick=\"checkClick(this);\" [chkIDEditModule_" . $rowDb['id_adm_module'] . "]></label></div></td>";
      $strResult .= "  <td align=\"center\"><div class=\"checkbox\"><label><input class=\"checkbox-inline\" name=\"chkIDdelete_" . $rowDb['id_adm_module'] . "\" id=\"chkIDdelete_" . $rowDb['id_adm_module'] . "\" type=\"checkbox\" value='noSave' onClick=\"checkClick(this);\" [chkIDDeleteModule_" . $rowDb['id_adm_module'] . "]></label></div></td>";
      $strResult .= "  <td align=\"center\"><div class=\"checkbox\"><label><input class=\"checkbox-inline\" name=\"chkIDcheck_" . $rowDb['id_adm_module'] . "\" id=\"chkIDcheck_" . $rowDb['id_adm_module'] . "\" type=\"checkbox\" value='noSave' onClick=\"checkClick(this);\" [chkIDApproveModule_" . $rowDb['id_adm_module'] . "]></label></div></td>";
      $strResult .= "  <td align=\"center\"><div class=\"checkbox\"><label><input class=\"checkbox-inline\" name=\"chkIDapprove_" . $rowDb['id_adm_module'] . "\" id=\"chkIDapprove_" . $rowDb['id_adm_module'] . "\" type=\"checkbox\" value='noSave' onClick=\"checkClick(this);\" [chkIDApproveModule_" . $rowDb['id_adm_module'] . "]></label></div></td>";
      $strResult .= "</tr>";
      $lastModule = $rowDb['modulename'];
    }
    if ($rowDb['menu_level'] == 0) {
      //jika main menu/top menu
      $strRows = "<tr id=\"menu_" . $rowDb['id_adm_module'] . "_" . $rowDb['id_adm_menu'] . "\" style=\"background-color : #cccccc\">";
      $chkIDview = "chkIDview_" . $rowDb['id_adm_module'] . "_" . $rowDb['id_adm_menu'];
      $chkIDedit = "chkIDedit_" . $rowDb['id_adm_module'] . "_" . $rowDb['id_adm_menu'];
      $chkIDdelete = "chkIDdelete_" . $rowDb['id_adm_module'] . "_" . $rowDb['id_adm_menu'];
      $chkIDcheck = "chkIDcheck_" . $rowDb['id_adm_module'] . "_" . $rowDb['id_adm_menu'];
      $chkIDapprove = "chkIDapprove_" . $rowDb['id_adm_module'] . "_" . $rowDb['id_adm_menu'];
      //save last chkID
      $lastChkIDview = $chkIDview;
      $lastChkIDedit = $chkIDedit;
      $lastChkIDdelete = $chkIDdelete;
      $lastChkIDcheck = $chkIDcheck;
      $lastChkIDapprove = $chkIDapprove;
    } else {
      $strRows = "<tr id=\"menu_" . $rowDb['id_adm_module'] . "_" . $rowDb['id_adm_menu'] . "\" >";
      $chkIDview = $lastChkIDview . "_" . $rowDb['id_adm_menu'];
      $chkIDedit = $lastChkIDedit . "_" . $rowDb['id_adm_menu'];
      $chkIDdelete = $lastChkIDdelete . "_" . $rowDb['id_adm_menu'];
      $chkIDcheck = $lastChkIDcheck . "_" . $rowDb['id_adm_menu'];
      $chkIDapprove = $lastChkIDapprove . "_" . $rowDb['id_adm_menu'];
    }
    for ($level = 0; $level < $rowDb['menu_level']; $level++) {
      $strRows .= "  <td>&nbsp;</td>";
    }
    $strRows .= "  <td>&nbsp;&nbsp;|--------------------------</td>\n";
    $strRows .= "  <td nowrap>" . $rowDb['name'] . "&nbsp;</td>";
    $colspan = $maxMenuLevel - $rowDb['menu_level'] + 1;
    if ($colspan > 1) {
      $strRows .= "  <td colspan=\"" . ($colspan - 1) . "\">&nbsp;</td>";
      $strRows .= "  <td>" . $rowDb['php_file'] . "&nbsp;</td>";
    } elseif ($colspan == 1) {
      $strRows .= "  <td nowrap>" . $rowDb['php_file'] . "&nbsp;</td>";
    }
    $strRows .= "  <td align=\"center\"><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" " . $rowDb['viewchecked'] . " name=\"" . $chkIDview . "\" id=\"" . $chkIDview . "\" type=\"checkbox\" onClick=\"checkClick(this);\"></label></div></td>";
    $strRows .= "  <td align=\"center\"><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" " . $rowDb['editchecked'] . " name=\"" . $chkIDedit . "\" id=\"" . $chkIDedit . "\" type=\"checkbox\" onClick=\"checkClick(this);\"></label></div></td>";
    $strRows .= "  <td align=\"center\"><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" " . $rowDb['deletechecked'] . " name=\"" . $chkIDdelete . "\" id=\"" . $chkIDdelete . "\" type=\"checkbox\" onClick=\"checkClick(this);\"></label></div></td>";
    $strRows .= "  <td align=\"center\"><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" " . $rowDb['checkchecked'] . " name=\"" . $chkIDcheck . "\" id=\"" . $chkIDcheck . "\" type=\"checkbox\" onClick=\"checkClick(this);\"></label></div></td>";
    $strRows .= "  <td align=\"center\"><div class=\"checkbox no-margin\"><label><input class=\"checkbox-inline\" " . $rowDb['approvechecked'] . " name=\"" . $chkIDapprove . "\" id=\"" . $chkIDapprove . "\" type=\"checkbox\" onClick=\"checkClick(this);\"></label></div></td>";
    $strRows .= "</tr>";
    $strResult .= $strRows;
  }// end print of Module.
  $lastModule = '/undefined/';
  $result['viewchecked'] = '';
  $result['editchecked'] = '';
  $result['deletechecked'] = '';
  $result['checkchecked'] = '';
  $result['approvechecked'] = '';
  foreach ($arrMenu as $rowDb) {
    if ($lastModule != $rowDb['id_adm_module']) {
      if ($lastModule != '/undefined/') {
        $strResult = str_replace("[chkIDViewModule_" . $lastModule . "]", $result['viewchecked'], $strResult);
        $strResult = str_replace("[chkIDEditModule_" . $lastModule . "]", $result['editchecked'], $strResult);
        $strResult = str_replace("[chkIDDeleteModule_" . $lastModule . "]", $result['deletechecked'], $strResult);
        $strResult = str_replace("[chkIDCheckModule_" . $lastModule . "]", $result['checkchecked'], $strResult);
        $strResult = str_replace("[chkIDApproveModule_" . $lastModule . "]", $result['approvechecked'], $strResult);
        $result['viewchecked'] = '';
        $result['editchecked'] = '';
        $result['deletechecked'] = '';
        $result['checkchecked'] = '';
        $result['approvechecked'] = '';
      }
      $lastModule = $rowDb['id_adm_module'];
    }
    if ($rowDb['view'] == 't') {
      $result['viewchecked'] = 'checked';
    }
    if ($rowDb['edit'] == 't') {
      $result['editchecked'] = 'checked';
    }
    if ($rowDb['delete'] == 't') {
      $result['deletechecked'] = 'checked';
    }
    if ($rowDb['check'] == 't') {
      $result['checkchecked'] = 'checked';
    }
    if ($rowDb['approve'] == 't') {
      $result['approvechecked'] = 'checked';
    }
  }
  if ($lastModule != '/undefined/') {
    $strResult = str_replace("[chkIDViewModule_" . $lastModule . "]", $result['viewchecked'], $strResult);
    $strResult = str_replace("[chkIDEditModule_" . $lastModule . "]", $result['editchecked'], $strResult);
    $strResult = str_replace("[chkIDDeleteModule_" . $lastModule . "]", $result['deletechecked'], $strResult);
    $strResult = str_replace("[chkIDCheckModule_" . $lastModule . "]", $result['checkchecked'], $strResult);
    $strResult = str_replace("[chkIDApproveModule_" . $lastModule . "]", $result['approvechecked'], $strResult);
  }
  $strResult = "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\" class=\"gridTable\">" . $strResult . "</table>";
  return $strResult;
}

function refreshPrivileges(&$arrMenu, $maxMenuLevel)
{
  for ($i = $maxMenuLevel; $i >= 0; $i--) {
    $result['view'] = 'f';
    $result['edit'] = 'f';
    $result['delete'] = 'f';
    $result['check'] = 'f';
    $result['approve'] = 'f';
    $lastParent = "/undefined/";
    foreach ($arrMenu as $rowDb) {
      if ($rowDb['menu_level'] == $i) {
        if ($lastParent != $rowDb['parent_id_adm_menu']) {
          if ($lastParent != "/undefined/") {
            $arrMenu[$lastParent]['view'] = $result['view'];
            $arrMenu[$lastParent]['edit'] = $result['edit'];
            $arrMenu[$lastParent]['delete'] = $result['delete'];
            $arrMenu[$lastParent]['check'] = $result['check'];
            $arrMenu[$lastParent]['approve'] = $result['approve'];
            $result['view'] = 'f';
            $result['edit'] = 'f';
            $result['delete'] = 'f';
            $result['check'] = 'f';
            $result['approve'] = 'f';
          }
          $lastParent = $rowDb['parent_id_adm_menu'];
        }
        if ($rowDb['view'] == 't') {
          $result['view'] = 't';
        }
        if ($rowDb['edit'] == 't') {
          $result['edit'] = 't';
        }
        if ($rowDb['delete'] == 't') {
          $result['delete'] = 't';
        }
        if ($rowDb['check'] == 't') {
          $result['check'] = 't';
        }
        if ($rowDb['approve'] == 't') {
          $result['approve'] = 't';
        }
      }
    }
    if ($lastParent != "/undefined/" && $lastParent != "") {
      $arrMenu[$lastParent]['view'] = $result['view'];
      $arrMenu[$lastParent]['edit'] = $result['edit'];
      $arrMenu[$lastParent]['delete'] = $result['delete'];
      $arrMenu[$lastParent]['check'] = $result['check'];
      $arrMenu[$lastParent]['approve'] = $result['approve'];
    }
  }
  //print_r($arrMenu);
  foreach ($arrMenu as &$rowDb) {
    $rowDb['viewchecked'] = ($rowDb['view'] == 't') ? "checked" : "";
    $rowDb['editchecked'] = ($rowDb['edit'] == 't') ? "checked" : "";
    $rowDb['deletechecked'] = ($rowDb['delete'] == 't') ? "checked" : "";
    $rowDb['checkchecked'] = ($rowDb['check'] == 't') ? "checked" : "";
    $rowDb['approvechecked'] = ($rowDb['approve'] == 't') ? "checked" : "";
  }
}

function saveData($strDataID)
{
  // upadate untuk checkbox View Permission
  global $errMessage;
  global $strMessage;
  global $strWordCancel;
  global $strWordCancelEntry;
  $lastIndex = "undefined";
  $strSQL = "DELETE FROM adm_group_menu WHERE id_adm_group = '$strDataID';";
  foreach ($_REQUEST as $strIndex2 => $strValue2) {
    if (substr($strIndex2, 0, 9) == 'chkIDview' || substr($strIndex2, 0, 9) == 'chkIDedit' || substr(
            $strIndex2,
            0,
            11
        ) == 'chkIDdelete'
    ) {
      if ($strValue2 != 'noSave') {
        //ambil index setelah tanda _
        if (substr($strIndex2, 0, 11) == 'chkIDdelete') {
          $idx = substr($strIndex2, 12);
        } else {
          $idx = substr($strIndex2, 10);
        }
        if ($lastIndex == $idx) {
          continue;
        } else {
          $lastIndex = $idx;
        }
        (isset($_REQUEST['chkIDview_' . $idx])) ? $viewValue = 't' : $viewValue = 'f';
        (isset($_REQUEST['chkIDedit_' . $idx])) ? $editValue = 't' : $editValue = 'f';
        (isset($_REQUEST['chkIDdelete_' . $idx])) ? $deleteValue = 't' : $deleteValue = 'f';
        (isset($_REQUEST['chkIDcheck_' . $idx])) ? $checkValue = 't' : $checkValue = 'f';
        (isset($_REQUEST['chkIDapprove_' . $idx])) ? $approveValue = 't' : $approveValue = 'f';
        $isUpdate = false;
        $arrID = explode("_", $idx);
        if (count($arrID) > 0) {
          $idx2 = $arrID[count($arrID) - 1];
          //insert
          $strSQL .= "INSERT INTO adm_group_menu (id_adm_group, id_adm_menu, \"view\", \"edit\", \"delete\", \"check\", \"approve\") ";
          $strSQL .= "VALUES('$strDataID', '$idx2', '$viewValue', '$editValue', '$deleteValue', '$checkValue', '$approveValue');";
        }
      }
    }
  }
  //no need to save again, just return
  $isSaved = executeSaveSQL($strSQL, getWords("group permission"), $strMessage);
  $errMessage = "bgError";
  if ($isSaved) {
    $strWordCancel = getWords("back");
    $strWordCancelEntry = getWords("go back confirmation");
    $errMessage = "bgOK";
  }
  return $isSaved;
} //end of saving data
?>
