<?php
$globalRelativeFolder = $GLOBALS['globalRelativeFolder'];
DEFINED(
    'VALID_APPLICATION'
) or die("Sorry, direct access to page <span style=\"color:red\">" . $_SERVER['PHP_SELF'] . "</span> is prohibited!");
$link = [
    ["url" => "main.php", "name" => "home"],
    /*array("url" => "help.php", "name" => "help"),*/
    ["url" => $globalRelativeFolder . "changepwd.php", "name" => "change password"]
];
$strResult = "<div class=\"col-md-12\">
  <div class=\"btn-group\">";
foreach ($link as $val) {
  $strResult .= "
                <button class=\"btn btn-primary\"  onClick=\"goMenu('" . $val['url'] . "'," . $_SESSION['sessionModuleID'] . ")\">" . strtoupper(
          getWords($val['name'])
      ) . "</button>";
}
$strResult .= "
  <button class=\"btn btn-primary\"  onClick=\"goMenu('" . $globalRelativeFolder . "logout.php','','" . getWords(
        "exit application message"
    ) . "')\">" . strtoupper(getWords("logout")) . "</button>
              </div>
              </div>";
$strResult .= '<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
		<i class="fa fa-angle-double-up icon-only bigger-110"></i>
	</a>';
echo $strResult;
?>
