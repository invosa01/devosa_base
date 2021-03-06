<?php
DEFINED(
    'VALID_APPLICATION'
) or die("Sorry, direct access to page <span style=\"color:red\">" . $_SERVER['PHP_SELF'] . "</span> is prohibited!");
//include_once("includes/krumo/class.krumo.php");
//krumo($_SESSION);
include_once("includes/menu/clsMenu.php");
$menu = new clsMenu(
    $GLOBALS['globalRelativeFolder'] . "includes/menu/",
    "xp-extended",
    $GLOBALS['globalRelativeFolder'] . "images/icons/"
);
$menu->noscript = true; //don't print the javascript (otherwise error in IE)
if (!$GLOBALS['globalIsModuleLoaded']) {
  //jika daftar module belum ke load, maka load dahulu dari database
  //get Default Module, that is the first occurence module, order by sequence_no of table adm_module
  if ($GLOBALS['globalIdGroup'] != "") {
    $_SESSION['sessionModuleList'] = getDataModuleFromDatabase($GLOBALS['globalIdGroup']);
    if (count($_SESSION['sessionModuleList']) > 0) {
      if (isset($_SESSION['sessionDefaultModuleID']) && isset($_SESSION['sessionModuleList'][$_SESSION['sessionDefaultModuleID']])) {
        $_SESSION['sessionModuleID'] = $_SESSION['sessionModuleList'][$_SESSION['sessionDefaultModuleID']]['id_adm_module'];
        $_SESSION['sessionModuleName'] = $_SESSION['sessionModuleList'][$_SESSION['sessionDefaultModuleID']]['name'];
      } else {
        $_SESSION['sessionModuleID'] = $_SESSION['sessionModuleList'][1]['id_adm_module'];
        $_SESSION['sessionModuleName'] = $_SESSION['sessionModuleList'][1]['name'];
      }
    }
  }
}
$_SESSION['sessionDateSetting'] = getDateSettingFromDatabase();
$_SESSION['sessionDateFormat'] = str_replace($_SESSION['sessionDateSetting']['date_sparator'],"",$_SESSION['sessionDateSetting']['php_format']);
//$_SESSION['sessionPHPDateFormat'] = $_SESSION['sessionDateSetting']['php_format'];
//query to get menu
$menu->addMenuItem(
    new MenuItem(0, 0, null, getWords("program"), "#", "favorite.png", "Kembali ke halaman utama", true)
);
$menu->addMenuItem(
    new MenuItem(
        'home',
        1,
        0,
        getWords("Home"),
        $GLOBALS['globalRelativeFolder'] . "main.php",
        "home.png",
        "Back to Home"
    )
);
$menu->addMenuItem(
    new MenuItem(
        'pwd',
        1,
        0,
        getWords("Change Password"),
        $GLOBALS['globalRelativeFolder'] . "changepwd.php",
        "password.png",
        "Ubah Password"
    )
);
$menu->addMenuItem(
    new MenuItem(
        'lang',
        1,
        0,
        getWords("change language"),
        $GLOBALS['globalRelativeFolder'] . "changepwd.php",
        "lib.png",
        "Back to Home"
    )
);
$strGetQueryString = getQueryString();
$strLang = getGetValue('changeLanguageTo', $_SESSION['sessionLanguage']);
if ($strLang == "en") {
  $menu->addMenuItem(
      new MenuItem(
          'lang1',
          2,
          'lang',
          "English&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src='" . $GLOBALS['globalRelativeFolder'] . "images/ok.png' border=0 />",
          $_SERVER['PHP_SELF'] . "?changeLanguageTo=en",
          "english.png",
          "Back to Home"
      )
  );
  $menu->addMenuItem(
      new MenuItem(
          'lang2',
          2,
          'lang',
          "Indonesian",
          $_SERVER['PHP_SELF'] . "?changeLanguageTo=id" . $strGetQueryString,
          "indonesia.png",
          "Back to Home"
      )
  );
  $menu->addMenuItem(
      new MenuItem(
          'lang3',
          2,
          'lang',
          "Japan",
          $_SERVER['PHP_SELF'] . "?changeLanguageTo=jp" . $strGetQueryString,
          "japan.png",
          "Back to Home"
      )
  );
} else if ($strLang == "jp") {
  $menu->addMenuItem(
      new MenuItem(
          'lang1',
          2,
          'lang',
          getWords("English"),
          $_SERVER['PHP_SELF'] . "?changeLanguageTo=en" . $strGetQueryString,
          "english.png",
          "Back to Home"
      )
  );
  $menu->addMenuItem(
      new MenuItem(
          'lang2',
          2,
          'lang',
          getWords("Indonesian"),
          $_SERVER['PHP_SELF'] . "?changeLanguageTo=id" . $strGetQueryString,
          "indonesia.png",
          "Back to Home"
      )
  );
  $menu->addMenuItem(
      new MenuItem(
          'lang3',
          2,
          'lang',
          getWords(
              "Japan"
          ) . "&nbsp;&nbsp;&nbsp;<img src='" . $GLOBALS['globalRelativeFolder'] . "images/ok.png' border=0 />",
          $_SERVER['PHP_SELF'] . "?changeLanguageTo=jp",
          "japan.png",
          "Back to Home"
      )
  );
} else {
  $menu->addMenuItem(
      new MenuItem(
          'lang1',
          2,
          'lang',
          "English",
          $_SERVER['PHP_SELF'] . "?changeLanguageTo=en" . $strGetQueryString,
          "english.png",
          "Back to Home"
      )
  );
  $menu->addMenuItem(
      new MenuItem(
          'lang2',
          2,
          'lang',
          "Indonesia&nbsp;&nbsp;&nbsp;<img src='" . $GLOBALS['globalRelativeFolder'] . "images/ok.png' border=0 />",
          $_SERVER['PHP_SELF'] . "?changeLanguageTo=id",
          "indonesia.png",
          "Back to Home"
      )
  );
  $menu->addMenuItem(
      new MenuItem(
          'lang3',
          2,
          'lang',
          "Jepang",
          $_SERVER['PHP_SELF'] . "?changeLanguageTo=jp" . $strGetQueryString,
          "japan.png",
          "Back to Home"
      )
  );
}
$menu->addMenuItem(new MenuItem('sep1', 1, 0, "", "", "", ""));
//$menu->addMenuItem(new MenuItem('help', 1, 0, "Help", $GLOBALS['globalRelativeFolder']."help.php", "help.png", "Bantu saya!"));
//$menu->addMenuItem(new MenuItem('sep2', 1, 0, "", "", "", ""));
$menu->addMenuItem(
    new MenuItem(
        'exit',
        1,
        0,
        getWords("Exit"),
        "javascript:goMenu('" . $GLOBALS['globalRelativeFolder'] . "logout.php', '', '" . getWords(
            "Leave the system?"
        ) . "')",
        "exit.png",
        "Logout dan keluar dari aplikasi"
    )
);
if (!$GLOBALS['globalIsPrivilegesLoaded'])
  //jika data privileges user belum ke load, maka load dahulu dari database
  //get data privileges from database
{
  $_SESSION['sessionPrivileges'] = getDataPrivilegesFromDatabase($GLOBALS['globalIdGroup']);
}
if (is_array($_SESSION['sessionPrivileges'])) //pastikan bahwa data ini adalah array
{
  foreach ($_SESSION['sessionPrivileges'] as $rowDb) {
    if ($_SESSION['sessionModuleID'] == $rowDb['id_adm_module']) {
      $menu->addMenuItem(
          new MenuItem(
              $rowDb['id_adm_menu'],
              $rowDb['menu_level'],
              $rowDb['parent_id_adm_menu'],
              getWords($rowDb['menu_name']),
              $GLOBALS['globalRelativeFolder'] . $rowDb['folder'] . '/' . $rowDb['php_file'],
              $rowDb['icon_file'],
              $rowDb['note']
          )
      );
    }
  }
}
function getQueryString()
{
  $strResult = "";
  foreach ($_GET as $key => $value) {
    if ($key != "changeLanguageTo") {
      $strResult .= "&" . $key . "=" . $value;
    }
  }
  return $strResult;
}

//--END OF PROGRAM
?>
<script type="text/javascript">
  function goMenu(pageName, module, confirmation, newtab) {
    if (typeof confirmation != 'undefined')
      if (!confirm(confirmation)) {
        return;
      }

    if (typeof module != 'undefined' || module != '')
      if (module != '') {
        if (newtab == 'newtab')
          window.open(pageName + "?moduleID=" + module, '_blank');
        else
          location.href = pageName + "?moduleID=" + module;
      } else {
        if (newtab == 'newtab')
          window.open(pageName, '_blank');
        else
          location.href = pageName;
      }
    else {
      if (newtab == 'newtab')
        window.open(pageName, '_blank');
      else
        location.href = pageName;
    }
  }
</script>
<!-- HEAD NAV -->
<div class="navbar navbar-default navbar-static-top navbar-main" role="navigation">
  <div class="navbar-header">
    <a class="navbar-brand" href="main.php">
      &nbsp;<img src="<?php print $GLOBALS['globalRelativeFolder'] ?>images/logo_front_new_deluxe.png"
                 height="48"
                 style="padding-top:5px;"></a>
  </div>
  <ul id="main-module" class="nav navbar-nav navbar-right">
    <li class="visible-xs">
      <a href="#" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-top">
        <span class="sr-only">Toggle navigation</span>
        <i class="fa fa-bars"></i>
      </a>
    </li>
    <li class="dropdown">
      <a href="#" class="dropdown-toggle avatar pull-right" data-toggle="dropdown">
        <img src="[var.globalRelativeFolder]asset/img/users/default-user.png" alt="mike" class="img-avatar" />
        <span class="hidden-small"><?php print ucfirst($_SESSION['sessionUserName']); ?><b class="caret"></b></span>
      </a>

      <ul class="dropdown-menu pull-right">
        <?php
        $strResult2 = "";
        $strResult = "";
        foreach ($_SESSION['sessionModuleList'] as $module) {
          if ($_SESSION['sessionModuleID'] == $module['id_adm_module']) {
            $strModuleSelected = strtoupper(getWords($module['name']));
            $strResult .= "\n            <td class=\"moduleSelected\">" . strtoupper(
                    getWords($module['name'])
                ) . "</td>";
            $strResult2 .= "<li><a href=\"#\"><i class=\"fa fa-external-link-square\"></i>" . strtoupper(
                    getWords($module['name'])
                ) . "</a></li>";
          } else if ($GLOBALS['globalLanguage'] == "id") {
            $strResult .= "\n            <td class=\"moduleNormal\" onMouseOver=\"this.className='moduleNormalHover'\" onMouseOut=\"this.className='moduleNormal'\" onClick=\"goMenu('" . $GLOBALS['globalRelativeFolder'] . "main.php','" . $module['id_adm_module'] . "')\">" . strtoupper(
                    getWords($module['name'])
                ) . "</td>";
            $strResult2 .= "<li><a href=\"javascript:goMenu('" . $GLOBALS['globalRelativeFolder'] . "main.php','" . $module['id_adm_module'] . "')\"><i class=\"fa fa-external-link-square\"></i>" . strtoupper(
                    getWords($module['name'])
                ) . "</a></li>";
          } else {
            $strResult .= "\n            <td class=\"moduleNormal\" onMouseOver=\"this.className='moduleNormalHover'\" onMouseOut=\"this.className='moduleNormal'\" onClick=\"goMenu('" . $GLOBALS['globalRelativeFolder'] . "main.php','" . $module['id_adm_module'] . "')\">" . strtoupper(
                    getWords($module['name'])
                ) . "</td>";
            $strResult2 .= "<li><a href=\"javascript:goMenu('" . $GLOBALS['globalRelativeFolder'] . "main.php','" . $module['id_adm_module'] . "')\"><i class=\"fa fa-external-link-square\"></i>" . strtoupper(
                    getWords($module['name'])
                ) . "</a></li>";
          }
        }
        if ($strResult != "") {
          $strResult = "
              <div style='height: 46px'>
                <table border=\"0\" height=\"22\" cellspacing=\"0\" cellpadding=\"0\">
                  <tr>" . $strResult . "</tr>
                </table>
              </div>";
        } else {
          $strResult = "<div style='height: 46px'>&nbsp;</div>";
        }
        //  echo $strResult;
        $strResult2 .= "<li class=\"divider\"></li>";
        $link = [
            ["url" => "main.php", "name" => strtoupper(getWords("home")), "faicon" => "fa-home"],
            //array("url" => "help.php", "name" => strtoupper(getWords("help")),"faicon" => "fa-info-circle"),
            //array("url" => "winhelp", "name" => strtoupper(getWords("Online help")),"faicon" => "fa-info-circle"),
            ["url" => "changepwd.php", "name" => strtoupper(getWords("change password")), "faicon" => "fa-key"]
        ];
        $strResult = "
              <td class=\"topMenuLeft\" nowrap ondblclick=\"alert('This application is used to manage time attendance and medical claim.\\nCopyright &copy; 2008 by PT Invosa Systems.\\n\\nSystem Info: \\n  - Database      : " . strtoupper(
                DB_TYPE
            ) . " on " . DB_SERVER . "@" . DB_NAME . "\\n  - Current User : " . $_SESSION['sessionUserName'] . "')\">" . $_SESSION['sessionUserName'] . "</td>
              <td width=\"2\" bgcolor=\"white\"></td>";
        foreach ($link as $val) {
          if ($val['url'] == "winhelp") {
            //uddin
            // khusus help online capture php self dan digunakan untuk parameter help online
            $arrSelf = explode(".php", $_SERVER['PHP_SELF']);
            $arrSelf = explode("/", @$arrSelf[0]);
            $paramHelp = "help_" . @$arrSelf[count($arrSelf) - 1];
            $strResult .= "
              <td nowrap class=\"topMenu\" onMouseOver=\"this.className='topMenuHover'\" onMouseOut=\"this.className='topMenu'\" onClick=\"createWinHelp()\">" . $val['name'] . "</td>";
            $strResult2 .= "<li><a href=\"javascript:goMenu('http://devosa.info/?content=" . $paramHelp . "','','Help will open new tab or page','newtab')\"><i class=\"fa " . $val['faicon'] . "\"></i>" . $val['name'] . "</a></li>";
          } else {
            $strResult .= "
              <td nowrap class=\"topMenu\" onMouseOver=\"this.className='topMenuHover'\" onMouseOut=\"this.className='topMenu'\" onClick=\"goMenu('" . $GLOBALS['globalRelativeFolder'] . $val['url'] . "')\">" . $val['name'] . "</td>";
            $strResult2 .= "<li><a href=\"javascript:goMenu('" . $GLOBALS['globalRelativeFolder'] . $val['url'] . "','')\"><i class=\"fa " . $val['faicon'] . "\"></i>" . $val['name'] . "</a></li>";
          }
        }
        $strResult .= "
              <td nowrap class=\"topMenuRight\" onMouseOver=\"this.className='topMenuRightHover'\" onMouseOut=\"this.className='topMenuRight'\" onClick=\"goMenu('" . $GLOBALS['globalRelativeFolder'] . "logout.php','','" . getWords(
                "exit application message"
            ) . "')\">" . strtoupper(getWords('logout')) . "</td>";
        $strResult2 .= "<li class=\"divider\"></li>";
        $strResult2 .= "<li><a href=\"" . $GLOBALS['globalRelativeFolder'] . "logout.php\"><i class=\"fa fa-unlock\"></i>" . strtoupper(
                getWords('logout')
            ) . "</a></li>";
        //echo $strResult;
        echo $strResult2;
        ?>

      </ul>
      <noscript><font style="color:white">Sorry, you must use javascript capability browser to run this ERP
                                          application.</font></noscript>

    </li>
  </ul>
</div>
<!-- END: HEAD NAV -->
<div class="navbar navbar-default navbar-static-top navbar-top"
     role="navigation"
     style="box-shadow: 1px 4px 10px #ddd;">
  <?php echo $menu->render(); ?>
</div>
