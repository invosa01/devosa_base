<?php
include_once('tbs_class.php');
$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_us_examples_dyncol.htm');
// Retreiving user data
if (!isset($_GET)) {
	$_GET =& $HTTP_GET_VARS;
}
$nbr_row = (isset($_GET['nbr_row'])) ? intval($_GET['nbr_row']) : 10;
$nbr_col = (isset($_GET['nbr_col'])) ? intval($_GET['nbr_col']) : 10;
// List of column's names
$columns = [];
for ($col = 1; $col <= $nbr_col; $col++) {
	$columns[$col] = 'column_' . $col;
}
// Creating data
$data = [];
for ($row = 1; $row <= $nbr_row; $row++) {
	$record = [];
	for ($col = 1; $col <= $nbr_col; $col++) {
		$record[$columns[$col]] = $row * $col;
	}
	$data[$row] = $record;
}
// Expanding columns
$TBS->MergeBlock('c0,c1,c2', $columns);
// Merging rows
$TBS->MergeBlock('r', $data);
$TBS->Show();
?>