<?php
include_once('tbs_class.php');
$recset[] = ['title' => 'I will love you', 'rank' => 'A'];
$recset[] = ['title' => 'Tender thender', 'rank' => 'B'];
$recset[] = ['title' => 'I got you babe', 'rank' => 'C'];
$recset[] = ['title' => 'Only with you', 'rank' => 'D'];
$recset[] = ['title' => 'Love me tender', 'rank' => 'E'];
$recset[] = ['title' => 'Wait for me', 'rank' => 'F'];
$recset[] = ['title' => 'Happy pop', 'rank' => 'G'];
$recset[] = ['title' => 'Kiss me like that', 'rank' => 'H'];
$recset[] = ['title' => 'Love me so', 'rank' => 'I'];
$recset[] = ['title' => 'Us, you and I', 'rank' => 'J'];
$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_us_examples_prmserial.htm');
$TBS->MergeBlock('bx', $recset);
$TBS->MergeBlock('bz', $recset);
$TBS->Show();
?>