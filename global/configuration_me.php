<?php
//date_default_timezone_set('Asia/Jakarta');
//the host server address of database
define("DB_TYPE", "postgres");
define("DB_SERVER", "localhost");
//define("DB_SERVER","invosaserver");
//the name of database
define("DB_NAME", "DevosaVersionOnline");
//define("DB_NAME","DevosaVersi2_02_import_salary");
//the database's user password
define("DB_USER", "postgres");
//the database's user name
define("DB_PWD", "toor");
//the absolute directory path in local drive
//define("ABSOLUTE_PATH", $_SERVER['DOCUMENT_ROOT']);
//the URL
define("LIVE_SITE", "http://localhost/fuji/");
//application name
define("APPLICATION_NAME", "SMART-U - Human Resource Information System");
//copyright string/HTML
define("COPYRIGHT", "Copyright &copy; 2008 by PT Invosa Systems.<br>All rights reserved.");
//default ENGLISH
define("DEFAULT_LANGUAGE", "en");
//default error reporting
//error_reporting(0);
ini_set("memory_limit", "64M");
//write user login/logout activity to database
define("WRITE_USER_LOG", 0);
define("CONFIGURATION_LOADED", 1);
define("PG_DUMP_PATH", 'C:\Program Files\PostgreSQL\8.1\bin\pg_dump.exe');
?>
