<?php
include_once("dbClass.php");
$db = new cDbClass;
if ($db->connect()) {
    $res = $db->execute("SELECT \"employeeID\", \"employeeName\" FROM \"hrdEmployee\"");
    while ($row = $db->fetchrow($res, "ASSOC")) {
        foreach ($row as $key => $val) {
            echo $val . " ";
        }
        echo "<br />";
    }
}
?>