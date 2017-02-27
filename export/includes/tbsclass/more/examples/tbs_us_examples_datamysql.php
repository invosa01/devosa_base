<?php
include_once('tbs_class.php');
//Connexion to the database
if (!isset($_SERVER)) {
    $_SERVER =& $HTTP_SERVER_VARS;
} //PHP<4.1.0
require($_SERVER['DOCUMENT_ROOT'] . '/cnx_mysql.php');
//The file cnx_mysql.php contains the following lines :
//  $cnx_id = mysql_connect('localhost','user','password') ;
//  mysql_select_db('dbname',$cnx_id) ;
$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_us_examples_datamysql.htm');
$TBS->MergeBlock('blk1', $cnx_id, 'SELECT * FROM t_tbs_exemples');
mysql_close($cnx_id);
$TBS->Show();
?>