<?php
include_once('tbs_class.php');
include_once('tbs_plugin_cache.php'); // Plug-in Cache System
$TBS = new clsTinyButStrong;
// Call the Cache System which is deciding wheter to continue and store the result into a cache file, or to display a cached page.
$TBS->PlugIn(TBS_CACHE, 'testcache', 10);
$TBS->LoadTemplate('tbs_us_examples_cache.htm');
$TBS->Show();
?>