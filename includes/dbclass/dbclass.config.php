<?php
//the name of database server
if (!defined("DB_SERVER")) {
    define("DB_SERVER", "localhost");
}
//the name of database
if (!defined("DB_NAME")) {
    define("DB_NAME", "lega3w_devosa_wal_dev");
}
//the database's user name
if (!defined("DB_PWD")) {
    define("DB_PWD", "test01");
}
//the database's user password
if (!defined("DB_USER")) {
    define("DB_USER", "lega3w_wal");
}
//DEFINE('DB_TYPE', 'MYSQL');
if (!defined('DB_TYPE')) {
    define('DB_TYPE', 'postgres');
}
?>
