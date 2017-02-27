<?php
include_once('tbs_class.php');
//Example of a Sql-Server connection
//$cnx_id = sqlite_open('mydatabase.dat') ;
$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_us_examples_datasqlite');
$TBS->MergeBlock('blk1', $cnx_id, 'SELECT * FROM t_tbs_exemples');
sqlite_close($cnx_id);
$TBS->Show();;
?>