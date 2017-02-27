<?php
include_once('tbs_class.php');
$country = ['France', 'England', 'Spain', 'Italy', 'Germany'];
$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_us_examples_blocks.htm');
$TBS->MergeBlock('blk1', $country);
$TBS->MergeBlock('blk2', $country);
$TBS->MergeBlock('blk3', $country);
$TBS->MergeBlock('blk4', $country);
$TBS->MergeBlock('blk5', $country);
$TBS->Show();
?>