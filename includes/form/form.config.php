<?php
/*
   Dedy's class form Entry CONFIGURATION
   version 0.95 BETA
   PT. Invosa Systems
   All right reserved.
*/
//For <Input type=file.....    (function addInputFile)
//1 : Old file will be deleted from disk, when new file uploaded
//0: Just keep old file if new file uploaded
if (!DEFINED("DELETE_OLD_FILE")) {
  DEFINE("DELETE_OLD_FILE", "0");
}
if (!DEFINED("DATATYPE_DATE")) {
  DEFINE("DATATYPE_DATE", "date");
}
if (!DEFINED("DATATYPE_NUMERIC")) {
  DEFINE("DATATYPE_NUMERIC", "numeric");
}
if (!DEFINED("DATATYPE_STRING")) {
  DEFINE("DATATYPE_STRING", "string");
}
if (!DEFINED("DATATYPE_EMAIL")) {
  DEFINE("DATATYPE_EMAIL", "email");
}
if (!DEFINED("DATATYPE_INTEGER")) {
  DEFINE("DATATYPE_INTEGER", "integer");
}
if (!DEFINED("DATATYPE_UNDEFINED")) {
  DEFINE("DATATYPE_UNDEFINED", "");
}
//path of datagrid class please end with / (slash)
$GLOBALS['CLASSFORMPATH'] = "../includes/form/";
if (isset($_SESSION['bahasa'])) {
  $GLOBALS['globalLanguage'] = $_SESSION['bahasa'];
} else //DEFAULT language is 'en' (English)
{
  $GLOBALS['globalLanguage'] = "en";
}
if ($GLOBALS['globalLanguage'] == "id") {
  $GLOBALS['FORMWORDS'] = [
      "view_denied"   => "Anda tidak mempunyai hak untuk melihat halaman ini.<br>Silahkan klik tombal back, atau <a href=\"javascript:history.back();\">Klik disini</a> untuk kembali.",
      "edit_denied"   => "Anda tidak mempunyai hak untuk mengubah/menambah data pada halaman ini.<br>Silahkan klik tombal back, atau <a href=\"javascript:history.back();\">Klik disini</a> untuk kembali.",
      "delete_denied" => "Anda tidak mempunyai hak untuk menghapus data pada halaman ini.<br>Silahkan klik tombal back, atau <a href=\"javascript:history.back();\">Klik disini</a> untuk kembali."
  ];
  //type your own validation error message here
  $GLOBALS['validatorErrorMessage'] = [
      DATATYPE_UNDEFINED => "",
      DATATYPE_DATE      => "Masukkan input tanggal yang valid dalam format (YYYY-MM-DD) untuk ",
      DATATYPE_NUMERIC   => "Masukkan input data numerik untuk ",
      DATATYPE_STRING    => "Masukkan input data untuk ",
      DATATYPE_EMAIL     => "Masukkan input data email address untuk ",
      DATATYPE_INTEGER   => "Masukkan input data integer untuk "
  ];
} else {
  $GLOBALS['FORMWORDS'] = [
      "view_denied"   => "You don't have rights to view this page.<br>Press back button or <a href=\"javascript:history.back();\">click here</a> to go back",
      "edit_denied"   => "You don't have rights to edit/add data in this page.<br>Press back button or <a href=\"javascript:history.back();\">click here</a> to go back",
      "delete_denied" => "You don't have rights to delete data in this page.<br>Press back button or <a href=\"javascript:history.back();\">click here</a> to go back"
  ];
  //type your own validation error message here
  $GLOBALS['validatorErrorMessage'] = [
      DATATYPE_UNDEFINED => "",
      DATATYPE_DATE      => "Please enter a valid date (YYYY-MM-DD) input for ",
      DATATYPE_NUMERIC   => "Please enter a valid numeric input for ",
      DATATYPE_STRING    => "Please enter a valid input for ",
      DATATYPE_EMAIL     => "Please enter a valid email address for ",
      DATATYPE_INTEGER   => "Please enter a valid integer number for "
  ];
}
//do not change this data type
$GLOBALS['validatorDataType'] = [
    DATATYPE_UNDEFINED => "",
    DATATYPE_DATE      => "validateDate",
    DATATYPE_NUMERIC   => "validateNumeric",
    DATATYPE_STRING    => "validateString",
    DATATYPE_EMAIL     => "validateEmail",
    DATATYPE_INTEGER   => "validateInteger"
];
//$FORM_CSS = "form";
$FORM_CSS = "kka";
?>