<?php
//the name of database server
if (!defined("DB_SERVER")) {
    die("Missing definition of DB_SERVER in file configuration.php");
}
//the name of database
if (!defined("DB_NAME")) {
    die("Missing definition of DB_NAME in file configuration.php");
}
//the database's user name
if (!defined("DB_PWD")) {
    die("Missing definition of DB_PWD in file configuration.php");
}
//the database's user password
if (!defined("DB_USER")) {
    die("Missing definition of DB_USER in file configuration.php");
}
if (!defined('DB_TYPE')) {
    die("Missing definition of DB_TYPE in file configuration.php");
}
if (!defined('DB_ENCODING')) {
    define('DB_ENCODING', '');
}
if (!DEFINED("LIKE")) {
    DEFINE("LIKE", "ILIKE");
}
if (file_exists(dirname(__FILE__) . "/dbo/dbo_" . DB_TYPE . '.php')) {
    require_once(dirname(__FILE__) . "/dbo/dbo_" . DB_TYPE . ".php");
} else {
    die(sprintf('Unable to load DataSource file dbo_%s.php', DB_TYPE));
}
?>
