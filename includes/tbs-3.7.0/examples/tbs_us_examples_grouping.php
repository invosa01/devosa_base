<?php
include_once('tbs_class.php');
$result[0] = ['country' => 'United States', 'city' => 'Washington', 'winner' => 'Bob', 'score' => 100];
$result[1] = ['country' => 'United States', 'city' => 'Washington', 'winner' => 'Julia', 'score' => 99];
$result[2] = ['country' => 'United States', 'city' => 'Washington', 'winner' => 'Mark', 'score' => 78];
$result[3] = ['country' => 'United States', 'city' => 'New York', 'winner' => 'Stanley', 'score' => 110];
$result[4] = ['country' => 'United States', 'city' => 'New York', 'winner' => 'Robert', 'score' => 109];
$result[5] = ['country' => 'France', 'city' => 'Paris', 'winner' => 'Pierre', 'score' => 250];
$result[6] = ['country' => 'France', 'city' => 'Paris', 'winner' => 'Jean', 'score' => 210];
$result[7] = ['country' => 'France', 'city' => 'Paris', 'winner' => 'Gaël', 'score' => 120];
$result[8] = ['country' => 'France', 'city' => 'Toulouse', 'winner' => 'Emmanuelle', 'score' => 260];
$result[9] = ['country' => 'France', 'city' => 'Toulouse', 'winner' => 'Louis', 'score' => 240];
$result[10] = ['country' => 'France', 'city' => 'Toulouse', 'winner' => 'Jaques', 'score' => 200];
$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_us_examples_grouping.htm');
$TBS->MergeBlock('blk_res', $result);
$TBS->Show();
?>