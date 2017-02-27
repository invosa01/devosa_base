<?php
DEFINED(
    'VALID_APPLICATION'
) or die("Sorry, direct access to page <span style=\"color:red\">" . $_SERVER['PHP_SELF'] . "</span> is prohibited!");
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
  function goMenu(pageName, module, confirmation) {
    if (typeof confirmation != 'undefined')
      if (!confirm(confirmation)) {
        return;
      }

    if (typeof module != 'undefined')
      if (module != '')
        location.href = pageName + "?moduleID=" + module;
      else location.href = pageName;
    else location.href = pageName;
  }
</script>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class='logoTop'>
  <tr>
    <td width="375" height="65">&nbsp;</td>
    <td valign="top" class="logoTop2">
      <?php
      $strResult = "";
      foreach ($_SESSION['sessionModuleList'] as $module) {
        if ($_SESSION['sessionModuleID'] == $module['id_adm_module']) {
          $strModuleSelected = strtoupper(getWords($module['name']));
          $strResult .= "\n            <td class=\"moduleSelected\">" . strtoupper(getWords($module['name'])) . "</td>";
        } else if ($GLOBALS['globalLanguage'] == "id") {
          $strResult .= "\n            <td class=\"moduleNormal\" onMouseOver=\"this.className='moduleNormalHover'\" onMouseOut=\"this.className='moduleNormal'\" onClick=\"goMenu('" . $GLOBALS['globalRelativeFolder'] . "main.php','" . $module['id_adm_module'] . "')\">" . strtoupper(
                  getWords($module['name'])
              ) . "</td>";
        } else {
          $strResult .= "\n            <td class=\"moduleNormal\" onMouseOver=\"this.className='moduleNormalHover'\" onMouseOut=\"this.className='moduleNormal'\" onClick=\"goMenu('" . $GLOBALS['globalRelativeFolder'] . "main.php','" . $module['id_adm_module'] . "')\">" . strtoupper(
                  getWords($module['name'])
              ) . "</td>";
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
      echo $strResult;
      ?>
      <div style="vertical-align: bottom; float: right">
        <table border="0" cellpadding="0" cellspacing="0">
          <tr>
            <?php
            $link = [
                ["url" => "main.php", "name" => strtoupper(getWords("home"))],
                ["url" => "help.php", "name" => strtoupper(getWords("help"))],
                ["url" => "changepwd.php", "name" => strtoupper(getWords("change password"))]
            ];
            $strResult = "
              <td class=\"topMenuLeft\" nowrap ondblclick=\"alert('This application is used to manage time attendance and medical claim.\\nCopyright &copy; 2008 by PT Invosa Systems.\\n\\nSystem Info: \\n  - Database      : " . strtoupper(
                    DB_TYPE
                ) . " on " . DB_SERVER . "@" . DB_NAME . "\\n  - Current User : " . $_SESSION['sessionUserName'] . "')\">" . $_SESSION['sessionUserName'] . "</td>
              <td width=\"2\" bgcolor=\"white\"></td>";
            foreach ($link as $val) {
              $strResult .= "
              <td nowrap class=\"topMenu\" onMouseOver=\"this.className='topMenuHover'\" onMouseOut=\"this.className='topMenu'\" onClick=\"goMenu('" . $GLOBALS['globalRelativeFolder'] . $val['url'] . "')\">" . $val['name'] . "</td>";
            }
            $strResult .= "
              <td nowrap class=\"topMenuRight\" onMouseOver=\"this.className='topMenuRightHover'\" onMouseOut=\"this.className='topMenuRight'\" onClick=\"goMenu('" . $GLOBALS['globalRelativeFolder'] . "logout.php','','" . getWords(
                    "exit application message"
                ) . "')\">" . strtoupper(getWords('logout')) . "</td>";
            echo $strResult;
            ?>
          </tr>
        </table>
      </div>
    </td>
  </tr>

  <tr>
    <td colspan="2" style="height : 21px">
      <noscript><font style="color:white">Sorry, you must use javascript capability browser to run this ERP
                                          application.</font></noscript>
      <?php echo $menu->render(); ?>
    </td>
  </tr>
</table>
