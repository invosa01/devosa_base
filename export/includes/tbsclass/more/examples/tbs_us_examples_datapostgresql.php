<?php
include_once('tbs_class.php');
//Connection to the database
//The following file should connect to the database and set the variable $cnx_id.
if (!isset($_SERVER)) {
    $_SERVER =& $HTTP_SERVER_VARS;
} //PHP<4.1.0
require($_SERVER['DOCUMENT_ROOT'] . '/cnx_mysql.php');
//Example of a Sql-Server connection
//$cnx_id = pg_connect('host=localhost port=5432 dbname=books user=peter password=xxxx') ;
$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_us_examples_datapostgresql.htm');
$TBS->MergeBlock('blk1', $cnx_id, 'SELECT * FROM t_tbs_exemples');
mssql_close($cnx_id);
$TBS->Show();
?>