<?php
$strtemp = "";
//if (!DEFINED('CONFIGURATION_LOADED'))
include_once("global/configuration.php");
//session_start();
include_once('global/session.php');
include_once('global.php');
include_once("includes/menu/clsMenu.php");
// periksa apakah sudah login atau belum, jika belum, harus login lagi
//if (!isset($_SESSION['sessionUserID'])) {
//	header("location:login.php?dataPage=data_education.php");
//	exit();
//}
$bolCanView = true; //getUserPermission("data_education.php", $bolCanEdit, $bolCanDelete, $strError);
$strDisableApprove = ($_SESSION['sessionUserRole'] == ROLE_ADMIN) ? "" : "disabled";
$db = new CdbClass;
if ($db->connect()) {
	/*
        if (isset($_REQUEST['btnSave'])) {
            if ($bolCanEdit) {
                if (!saveData($db, $strError)) {
                    echo "<script>alert(\"$strError\")</script>";
                }
            }
        } else if (isset($_REQUEST['btnDelete'])) {
            if ($bolCanDelete) {
                deleteData($db);
            }
        } else if (isset($_REQUEST['btnApprove']) && $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
            approveData($db);
        }
        if ($bolCanView) {
            $strDataDetail = getData($db,$intTotalData);
        } else {
            showError("view_denied");
        }
        */
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!--
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
-->

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>deVosa - HR System</title>
	<link rel="stylesheet" type="text/css" href="dhx/dhtmlxLayout/codebase/dhtmlxlayout.css">
	<link rel="stylesheet" type="text/css" href="dhx/dhtmlxLayout/codebase/skins/dhtmlxlayout_dhx_blue.css">
	<link rel="stylesheet" type="text/css" href="dhx/dhtmlxLayout/codebase/skins/dhtmlxlayout_dhx_skyblue.css">
	<link rel="stylesheet" type="text/css" href="dhx/dhtmlxToolbar/skins/skyblue/dhtmlxtoolbar.css"></link>
	<script src="dhx/dhtmlxLayout/codebase/dhtmlxcommon.js"></script>
	<script src="dhx/dhtmlxLayout/codebase/dhtmlxlayout.js"></script>
	<link rel="STYLESHEET" type="text/css" href="dhx/dhtmlxTree/codebase/dhtmlxtree.css">
	<script src="dhx/dhtmlxTree/codebase/dhtmlxtree.js"></script>
	<link rel="stylesheet" type="text/css" href="dhx/dhtmlxTabbar/codebase/dhtmlxtabbar.css">
	<script src="dhx/dhtmlxTabbar/codebase/dhtmlxtabbar.js"></script>

	<script type="text/javascript" src="dhx/dhtmlxToolbar/codebase/dhtmlxtoolbar.js"></script>
	<script src="dhx/dhtmlxLayout/codebase/dhtmlxcontainer.js"></script>
	<script src="plugins/jquery/jquery-2.1.0.min.js"></script>

	<link href="css/invosa.css" rel="stylesheet" type="text/css">

	<style>
		html, body {
			width: 100%;
			height: 100%;
			margin: 0px;
			padding: 0px;
			overflow: hidden;
		}

		#my_logo {
			background: #fff url(images/patra_bg.png) repeat-x;
			font: bold 2em "Trebuchet MS", Helvetica, Sans-Serif;
			color: #fff;
		}

		#my_copy {
			font: normal .7em Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
			margin: 4px;
		}

		#subhead {
			font: bold .5em "Trebuchet MS", Helvetica, Sans-Serif;
			position: fixed;
			top: 4px;
			right: 10px;
		}

		#chxCompany {
			position: fixed;
			top: 25px;
			right: 50px;
			z-index: 1000;
			border: 2px solid green;
			padding: 5px;
			background-color: #e0ffff;
		}

		#chxCompany a {
			font-family: "Times New Roman", Times, serif;
			font-style: normal;
			text-decoration: none;
		}

		#chxCompany a:link {
			color: green;
		}

		#chxCompany a:visited {
			color: green;
		}

		#chxCompany a:hover {
			color: red;
		}

		#chxCompany a:active {
			color: yellow;
		}
	</style>
	</
	style
	>
	<
	script >
	var dhxLayout, dhxTree, dhxTabbar, dhxToolbar

	;
	function

	doOnLoad
	(
	)
	{
		dhxLayout = new dhtmlXLayoutObject(document . body, "1C", "dhx_skyblue")
	;
		dhxLayout . attachHeader("my_logo")
	;
		dhxLayout . attachFooter("my_copy")
	;
		dhxLayout . cells("a") . hideHeader()
	;

		dhxLayout . cells("a") . attachURL("hrd/main.php")
	;
	}
	function

	tonclick
	(
	id

	)
	{
	}
	;
	function

	tondblclick
	(
	id

	)
	{
		var idtab = (new Date()) . valueOf()
	;
		dhxTabbar . addTab(idtab, dhxTree . getItemText(id))
	;
		dhxTabbar . setContentHref(idtab, id)
	;
		dhxTabbar . setTabActive(idtab)
	;
	}
	;

	function

	HideContent
	(
	d

	)
	{
		document . getElementById(d) . style . display = "none"
	;
	}
	function

	ShowContent
	(
	d

	)
	{
		document . getElementById(d) . style . display = "block"
	;
	}
	function

	ReverseDisplay
	(
	d

	)
	{
	if
	(
	document.

	getElementById
	(
	d

	)
	.style.display

	=
	=
	"none"
	)
	{
		document . getElementById(d) . style . display = "block"
	;
	}
	else {
		document . getElementById(d) . style . display = "none";
	}

	}

	function

	reloadLayout
	(
	)
	{
		var c = [ ],
		els = document . forms . nms . elements,
		len = els . length,
		strT = ""
	;
		for (var i=0
	;
		i < len
	;
	i + +

	)
	{
	if

	(
	els[ i ].type

	=
	=
	=
	'checkbox'
	)
	{
	/
	/
	alert
	(
	els
	[
	i
	]
	.
	value
	)
	;
	/
	/
	If
	you
	want
	to
	add
	only
	checked
	checkboxes
	,
	you
	can
	use
	:
	if
	(
	els
	[
	i
	]
	.
	checked
	)
	strT
	=
	strT
	+
	els
	[
	i
	]
	.
	value
	+
	"_k_"
	;

	/
	/
	If
	you
	don
	't care, just use:
	/
	/
	c
	.
	push
	(
	els
	[
	i
	]
	.
	value
	)
	;
	}
	}
	;

	console.

	log
	(
	c

	)
	;
	/
	/
	Array of the checkboxes values
	dhxLayout.

	cells
	(
	"a"
	)
	.

	attachURL
	(
	"../mdtrack/map_nms2.php?t="
	+
	strT

	)
	;
	}
	<
	/
	script >
	<

	/
	head ><?php
// Generate menu

$menu = new clsMenu($GLOBALS['globalRelativeFolder']."includes/menu/", "xp-extended", $GLOBALS['globalRelativeFolder']."images/icons/");
$menu->noscript = true; //don't print the javascript (otherwise error in IE)

//$strtemp .= "generate menu<br/>";
if (!$GLOBALS['globalIsModuleLoaded'])
{

	//$strtemp .="masuk 1";
	//jika daftar module belum ke load, maka load dahulu dari database
	//get Default Module, that is the first occurence module, order by sequence_no of table adm_module
	if ($GLOBALS['globalIdGroup'] != "")
	{
		//$strtemp .="masuk 2";
		$_SESSION['sessionModuleList'] = getDataModuleFromDatabase($GLOBALS['globalIdGroup']);
		if (count($_SESSION['sessionModuleList']) > 0)
		{
			if (isset($_SESSION['sessionDefaultModuleID']) && isset($_SESSION['sessionModuleList'][$_SESSION['sessionDefaultModuleID']]))
			{
				$_SESSION['sessionModuleID'] = $_SESSION['sessionModuleList'][$_SESSION['sessionDefaultModuleID']]['id_adm_module'];
				$_SESSION['sessionModuleName'] = $_SESSION['sessionModuleList'][$_SESSION['sessionDefaultModuleID']]['name'];
			}
			else
			{
				$_SESSION['sessionModuleID'] = $_SESSION['sessionModuleList'][1]['id_adm_module'];
				$_SESSION['sessionModuleName'] = $_SESSION['sessionModuleList'][1]['name'];
			}
		}
	}
}
//query to get menu
$menu->addMenuItem(new MenuItem(0, 0, null, getWords("program"), "#", "favorite.png", "Kembali ke halaman utama", true));
$menu->addMenuItem(new MenuItem('home', 1, 0, getWords("Home"), $GLOBALS['globalRelativeFolder']."main.php", "home.png", "Back to Home"));
$menu->addMenuItem(new MenuItem('pwd', 1, 0, getWords("Change Password"), $GLOBALS['globalRelativeFolder']."changepwd.php", "password.png", "Ubah Password"));
$menu->addMenuItem(new MenuItem('lang', 1, 0, getWords("change language"), $GLOBALS['globalRelativeFolder']."changepwd.php", "lib.png", "Back to Home"));

$strGetQueryString = getQueryString();
$strLang = getGetValue('changeLanguageTo', $_SESSION['sessionLanguage']);
if ($strLang == "en")
{
	$menu->addMenuItem(new MenuItem('lang1', 2, 'lang', "English&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src='".$GLOBALS['globalRelativeFolder']."images/ok.png' border=0 />", $_SERVER['PHP_SELF']."?changeLanguageTo=en", "english.png", "Back to Home"));
	$menu->addMenuItem(new MenuItem('lang2', 2, 'lang', "Indonesian", $_SERVER['PHP_SELF']."?changeLanguageTo=id".$strGetQueryString, "indonesia.png", "Back to Home"));
	$menu->addMenuItem(new MenuItem('lang3', 2, 'lang', "Japan", $_SERVER['PHP_SELF']."?changeLanguageTo=jp".$strGetQueryString, "japan.png", "Back to Home"));
}
else if($strLang == "jp")
{
	$menu->addMenuItem(new MenuItem('lang1', 2, 'lang', getWords("English"), $_SERVER['PHP_SELF']."?changeLanguageTo=en".$strGetQueryString, "english.png", "Back to Home"));
	$menu->addMenuItem(new MenuItem('lang2', 2, 'lang', getWords("Indonesian"), $_SERVER['PHP_SELF']."?changeLanguageTo=id".$strGetQueryString, "indonesia.png", "Back to Home"));
	$menu->addMenuItem(new MenuItem('lang3', 2, 'lang', getWords("Japan")."&nbsp;&nbsp;&nbsp;<img src='".$GLOBALS['globalRelativeFolder']."images/ok.png' border=0 />", $_SERVER['PHP_SELF']."?changeLanguageTo=jp", "japan.png", "Back to Home"));
}
else
{
	$menu->addMenuItem(new MenuItem('lang1', 2, 'lang', "English", $_SERVER['PHP_SELF']."?changeLanguageTo=en".$strGetQueryString, "english.png", "Back to Home"));
	$menu->addMenuItem(new MenuItem('lang2', 2, 'lang', "Indonesia&nbsp;&nbsp;&nbsp;<img src='".$GLOBALS['globalRelativeFolder']."images/ok.png' border=0 />", $_SERVER['PHP_SELF']."?changeLanguageTo=id", "indonesia.png", "Back to Home"));
	$menu->addMenuItem(new MenuItem('lang3', 2, 'lang', "Jepang", $_SERVER['PHP_SELF']."?changeLanguageTo=jp".$strGetQueryString, "japan.png", "Back to Home"));
}
$menu->addMenuItem(new MenuItem('sep1', 1, 0, "", "", "", ""));
//$menu->addMenuItem(new MenuItem('help', 1, 0, "Help", $GLOBALS['globalRelativeFolder']."help.php", "help.png", "Bantu saya!"));
//$menu->addMenuItem(new MenuItem('sep2', 1, 0, "", "", "", ""));
$menu->addMenuItem(new MenuItem('exit', 1, 0, getWords("Exit"), "javascript:goMenu('".$GLOBALS['globalRelativeFolder']."logout.php', '', '".getWords("Leave the system?")."')", "exit.png", "Logout dan keluar dari aplikasi"));

//if (!$GLOBALS['globalIsPrivilegesLoaded'])
//jika data privileges user belum ke load, maka load dahulu dari database
//get data privileges from database
$_SESSION['sessionPrivileges'] = getDataPrivilegesFromDatabase($GLOBALS['globalIdGroup']);
$arrPriv = getDataPrivilegesFromDatabase($GLOBALS['globalIdGroup']);
//var_dump($arrPriv);
//if (is_array($_SESSION['sessionPrivileges']))
//pastikan bahwa data ini adalah array
//{
	//foreach ($_SESSION['sessionPrivileges'] as $rowDb)
	foreach ($arrPriv as $rowDb){
	if ($_SESSION['sessionModuleID'] == $rowDb['id_adm_module'])
		{$menu->addMenuItem(new MenuItem($rowDb['id_adm_menu'], $rowDb['menu_level'], $rowDb['parent_id_adm_menu'], getWords($rowDb['menu_name']), $GLOBALS['globalRelativeFolder'].$rowDb['folder'].'/'.$rowDb['php_file'], $rowDb['icon_file'], $rowDb['note']));}
	//$strtemp .=$rowDb['menu_name']."<br/>";
	}
//}


function getQueryString()
{
	$strResult = "";
	foreach($_GET as $key => $value)
	{if ($key != "changeLanguageTo")
	{$strResult .= "&".$key."=".$value;}}
	return $strResult;
}


//--END OF PROGRAM

?> < body onload

	=
	"doOnLoad();"
	>
	<
	div id

	=
	"my_logo"
	style

	=
	"height: 90px;"
	width

	=
	"100%"
	>
	<
	table >
	< tr >
	< td >
	< img src

	=
	"images/logo_front.gif"
	height

	=
	"50"
	>
	<
	/
	td >
	< td >
	< table class

	=
	"master"
	border

	=
	0
	cellpadding

	=
	0
	cellspacing

	=
	0
	>
	<
	tr class

	=
	"logoTop"
	>
	<
	td style

	=
	"height:95px"
	valign

	=
	"top"
	>
	<
	script type

	=
	"text/javascript"
	>
	function

	goMenu
	(
	pageName, module, confirmation

	)
	{
	if

	(
	typeof confirmation

	!=
	'undefined'
	)
	if

	(
	!
	confirm
	(
	confirmation

	)
	)
	{
		return
	;
	}

	if

	(
	typeof module

	!=
	'undefined'
	)
	if

	(
	module

	!=
	''
	)
	location.href

	=
	pageName +

	"?moduleID="
	+
	module

	;
	else location.href

	=
	pageName

	;
	else location.href

	=
	pageName

	;
	}
	<
	/
	script >
	< table width

	=
	"100%"
	border

	=
	"0"
	cellspacing

	=
	"0"
	cellpadding

	=
	"0"
	class

	=
	'logoTop'
	>
	<
	tr >
	< td width

	=
	"375"
	height

	=
	"65"
	>
	&
	nbsp

	;
	<
	/
	td >
	< td valign

	=
	"top"
	class

	=
	"logoTop2"
	>
	<?php
    $strResult = "";
    foreach ($_SESSION['sessionModuleList'] as $module)
    {
        if ($_SESSION['sessionModuleID'] == $module['id_adm_module'])
        {
            $strModuleSelected = strtoupper(getWords($module['name']));
            $strResult .= "\n            <td class=\"moduleSelected\">".strtoupper(getWords($module['name']))."</td>";
        }
        else
        if ($GLOBALS['globalLanguage'] == "id")
        {$strResult .= "\n            <td class=\"moduleNormal\" onMouseOver=\"this.className='moduleNormalHover'\" onMouseOut=\"this.className='moduleNormal'\" onClick=\"goMenu('".$GLOBALS['globalRelativeFolder']."main.php','".$module['id_adm_module']."')\">".strtoupper(getWords($module['name']))."</td>";}
        else
        {$strResult .= "\n            <td class=\"moduleNormal\" onMouseOver=\"this.className='moduleNormalHover'\" onMouseOut=\"this.className='moduleNormal'\" onClick=\"goMenu('".$GLOBALS['globalRelativeFolder']."main.php','".$module['id_adm_module']."')\">".strtoupper(getWords($module['name']))."</td>";}
    }
    if ($strResult != "")
    {$strResult = "
    <div style='height: 46px'>
    <table border=\"0\" height=\"22\" cellspacing=\"0\" cellpadding=\"0\">
    <tr>".$strResult."</tr>
    </table>
    </div>";}
    else
    {$strResult = "<div style='height: 46px'>&nbsp;</div>";}
    echo $strResult;
    ?>
	<
	div style

	=
	"vertical-align: bottom; float: right"
	>
	<
	table border

	=
	"0"
	cellpadding

	=
	"0"
	cellspacing

	=
	"0"
	>
	<
	tr > <?php
															$link = [
															["url" => "main.php", "name" => strtoupper(getWords("home"))],
															["url" => "help.php", "name" => strtoupper(getWords("help"))],
															["url" => "changepwd.php", "name" => strtoupper(getWords("change password"))]
															];
															$strResult = "
															<td class=\"topMenuLeft\" nowrap ondblclick=\"alert('This application is used to manage time attendance and medical claim.\\nCopyright &copy; 2008 by PT Invosa Systems.\\n\\nSystem Info: \\n  - Database      : " .strtoupper(DB_TYPE)." on ".DB_SERVER."@".DB_NAME."\\n  - Current User : " .$_SESSION['sessionUserName']."')\">".$_SESSION['sessionUserName']."</td>
															<td width=\"2\" bgcolor=\"white\"></td>";
															foreach($link as $val)
															{$strResult .= "
															<td nowrap class=\"topMenu\" onMouseOver=\"this.className='topMenuHover'\" onMouseOut=\"this.className='topMenu'\" onClick=\"goMenu('".$GLOBALS['globalRelativeFolder'].$val['url']."')\">".$val['name']."</td>";}

															$strResult .= "
															<td nowrap class=\"topMenuRight\" onMouseOver=\"this.className='topMenuRightHover'\" onMouseOut=\"this.className='topMenuRight'\" onClick=\"goMenu('".$GLOBALS['globalRelativeFolder']."logout.php','','".getWords("exit application message")."')\">".strtoupper(getWords('logout'))."</td>";
															echo $strResult;
															?> <

	/
	tr >
	<

	/
	table >
	<

	/
	div >
	<

	/
	td >
	<

	/
	tr >
	< tr >
	< td colspan

	=
	"2"
	style

	=
	"height : 21px"
	>
	<
	noscript > < font style

	=
	"color:white"
	>
	Sorry, you must use javascript capability browser to run this ERP application.<

	/
	font > <

	/
	noscript > <?php echo $menu->render(); ?> <

	/
	td >
	<

	/
	tr >
	<

	/
	table ><?php echo $strtemp; ?> <

	/
	td >
	<

	/
	tr >
	<

	/
	table >
	<

	/
	td >
	<

	/
	tr >
	<

	/
	table >
	<

	/
	div >
	< div id

	=
	"my_copy"
	style

	=
	"height: 25px;"
	>
	PT Invosa Systems, copyright & copy

	;
	2015
	<
	/
	div >
	<

	/
	div >
	< script src

	=
	"hrd/scripts/invosa.js"
	>
	<
	/
	script >
	< script type

	=
	"text/javascript"
	src

	=
	"js/prototype.js"
	>
	<
	/
	script >
	< script type

	=
	"text/javascript"
	src

	=
	"includes/menu/js/PieNG.js"
	>
	<
	/
	script >
	< script type

	=
	"text/javascript"
	>
	DynarchMenu.

	preloadImages
	(
	)
	;
	var mainmenu

	=
	DynarchMenu.

	setup
	(
	'page-menu'
	,
	{
		shadows: [ -1, 0, 5, 5 ],
		scrolling: true,
		electric: false
	}
	)
	;
	if

	(
	typeof initPage_

	=
	=
	'function'
	)
	initPage_
	(
	)
	;
	<
	/
	script >
	<

	/
	body >
	<

	/
	html >
