<?php
//session_start();
include_once("../dbclass/dbClass.php");
include_once("form.php");
$db = new CDbClass;
//class datagrid declaration
//Parameter:
//   1. : Form Name and Form Id of this class FORM
//   2 : Number of Column that will become container for all objects(textbox, button, select, checkbox, etc)
//   3 : Form width, default is "100%", you can set with any integer value e.g : 700
//   4 : Form height, default is "100%", you can set with any integer value e.g : 500
//        but height will automatically adjust the height of the Form content
//   5 : if you want to force PATH of datagrid, use this parameter.
$f = new clsForm(
    "form1", /*1 column view*/
    1, "900", "500"
);
//title or caption of the Form
$f->caption = "DEMO";
//adding help to the form
//showing MINIMIZE button on the top-left corner
$f->showMinimizeButton = true;
//showing CLOSE button on the top-left corner
$f->showCloseButton = true;
//otomatis akan membuat folder photos jika belum ada
$uploadPath = "photos";
$f->addInputFile(
    "File",
    "dataFile", /*masukkan nama file lama*/
    "",
    "Hint: Upload file",
    false,
    true,
    true,
    true,
    false,
    "string",
    null,
    "",
    "",
    "",
    50,
    20,
    $uploadPath
);
$f->addSubmit(
    "btnUpload",
    "Upload",
    "Save this change",
    true,
    true,
    null,
    "",
    "",
    "afterUpload()",
    "onClick=\"return confirm('Do you want to upload this file?');\""
);
$f->getRequest();
$formInput = $f->render();
echo $formInput;
//end of main program
//-------------------------------
function afterUpload()
{
    global $db;
    global $f;
    if ($f->objects['dataFile']['readonly']) {
        $f->message = "Uploaded file: " . $f->getValue('dataFile');
    } else {
        $f->message = "Failed to upload";
    }
} // saveData
?>
