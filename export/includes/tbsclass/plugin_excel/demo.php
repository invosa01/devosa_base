<?php
if (count($_GET) == 0) {
    header('Location: demo_main.htm');
    exit;
}
include('tbs_class.php'); // TinyButStrong Template Engine (TBS)
include('tbs_plugin_excel.php'); // Excel plug-in for TBS 
include('demo_data.php'); // Data stored in arrays
$TBS = new clsTinyButStrong;
// Install the Excel plug-in (must be before LoadTemplate)
$TBS->PlugIn(TBS_INSTALL, TBS_EXCEL);
// Load the Excel template
$TBS->LoadTemplate('demo_template.xls');
// Merge Example 1 (in sheet #1)
$TBS->MergeBlock('book', $books);
// Merge Example 2 (in sheet #2)
$TBS->MergeBlock('tsk1,tsk2', $tasks);
$TBS->MergeBlock('emp', $employees);
// Options
//$TBS->PlugIn(TBS_EXCEL,TBS_EXCEL_INLINE);
$TBS->PlugIn(TBS_EXCEL, TBS_EXCEL_FILENAME, 'result.xls');
// Final merge and download file
$TBS->Show();
?>