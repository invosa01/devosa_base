<?php
$folder = "regulation/";
$handle = opendir($folder);
# Making an array containing the files in the current directory:
while ($file = readdir($handle)) {
    if ($file != ".." && $file != ".") {
        //$key = filemtime($file);
        $files[] = $file;
    }
}
closedir($handle);
// sort files by mtime:
sort($files);
//print_r($files);
#echo the files
$i = 0;
$n = count($files) - 1;
foreach ($files as $file) {
    if ($i == $n) {
        echo "<a href=$folder$file target='read'>$file</a>" . "<br />";
    }
    $i++;
}
?>