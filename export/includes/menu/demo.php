<?php
include_once("clsMenu.php");
$dataMain = [
    "Home"        => "javascript:alert(\"home's clicked\")",
    "Transaction" => [
        "Transaction 1" => "demo1.php",
        "Transaction 2" => "demo2.php",
        "Transaction 3" => [
            "Demo4" => "demo4.php",
            "Demo5" => "demo5.php"
        ]
    ],
    "Report"      => [
        "Report1" => "report1.php",
        "Report2" => "report2.php",
        "-"       => "",
        "Report3" => "report3.php"
    ]
];
(isset($_POST['skin'])) ? $skin = $_POST['skin'] : $skin = $GLOBALS['MENUSKIN'];
$menu = new clsMenu($dataMain, $skin);
echo $menu->render();
echo "current skin: " . $skin;
?>
<form method=post>
  <select name=skin>
    <option value="aqua">aqua</option>
    <option value="beos">beos</option>
    <option value="hmenu-dark">hmenu-dark</option>
    <option value="longhorn">longhorn</option>
    <option value="rain">rain</option>
    <option value="sample">sample</option>
    <option value="shaped">shaped</option>
    <option value="system">system</option>
    <option value="win2k">win2k</option>
    <option value="win98">win98</option>
    <option value="xp">xp</option>
    <option value="xp-apps">xp-apps</option>
    <option value="xp-apps2">xp-apps2</option>
    <option value="xp-extended">xp-extended</option>
    <option value="yellow">yellow</option>
    <option value="yp">yp</option>
  </select>
  <input type=submit value="Change Skin" />
</form>

