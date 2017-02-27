<?php
/*
********************************************************
TinyButStrong - Template Engine for Pro and Beginners
------------------------
Version  : 3.2.0 PHP >= 4.0.6
Date     : 2006-11-26
Web site : www.tinybutstrong.com
Author   : skrol29@freesurf.fr
********************************************************
This library is free software.
You can redistribute and modify it even for commercial usage,
but you must accept and respect the LPGL License version 2.1.
*/
// Check PHP version
if (PHP_VERSION < '4.0.6') {
    echo '<br><b>TinyButStrong Error</b> (PHP Version Check) : Your PHP version is ' . PHP_VERSION . ' while TinyButStrong needs PHP version 4.0.6 or higher.';
}
if (@$need_special_tbs == "390") {
    include_once("tbs_class_php5_390.php");
} else if (PHP_VERSION < '5') {
    include_once("tbs_class_php4.php");
} else {
    include_once("tbs_class_php5.php");
}
?>