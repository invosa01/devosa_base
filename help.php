<?php
if ($_SESSION['sessionCurrentFolder'] == "") //on root application
{
    $relativeFolder = "./";
} else //on folder module application
{
    $relativeFolder = "../";
}
header("location:" . $relativeFolder . "help/help.pdf");
?>