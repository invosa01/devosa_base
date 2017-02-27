<?php
if (!isset($_GET)) {
    $_GET =& $HTTP_GET_VARS;
}
show_source('tbs_us_examples_' . $_GET['script']);
exit;
?>