<?php
/*
   Dedy's class DataGrid CONFIGURATION
   version 1.0
   PT. Invosa Systems
   All right reserved.
*/
//path of datagrid class please end with / (slash)  relative to application that call this library
//for example if you put this datagrid library in directory includes/datagrid/
//while your application is in root directory then you should set this to $CLASSDATAGRIDPATH = "includes/datagrid/";
global $DATAGRID_CSS, $ARRAY_PAGE_LIMIT, $DATAGRIDWORDS;
$GLOBALS['CLASSDATAGRIDPATH'] = "../includes/datagrid2/";
//default page limit
$DEFAULTPAGELIMIT = "15";
//default paging size
$DEFAULTPAGINGSIZE = "10";
//set your SESSION LANGUAGE HERE, the value must be in 'en', and 'id' only, other language not yet supported
//if you do not use SESSION LANGUAGE JUST IGNORE it
if (isset($_SESSION['sessionLanguage'])) {
    $globalLanguage = $_SESSION['sessionLanguage'];
} else //DEFAULT language is 'en' English
{
    $globalLanguage = "en";
}
if ($globalLanguage == "en") {
    $ARRAY_PAGE_LIMIT = ["all", "100", "50", "25", "20", "15"];
    $DATAGRIDWORDS = [
        "any part of field"            => "Any part of Field",
        "view"                         => "view",
        "please wait"                  => "Please wait",
        "loading grid"                 => "Loading grid",
        "view_denied"                  => "You don't have rights to view this page.<br>Press back button or <a href=\"javascript:history.back();\">click here</a> to go back",
        "edit_denied"                  => "You don't have rights to edit/add data in this page.<br>Press back button or <a href=\"javascript:history.back();\">click here</a> to go back",
        "delete_denied"                => "You don't have rights to delete data in this page.<br>Press back button or <a href=\"javascript:history.back();\">click here</a> to go back",
        "no data"                      => "There are no data",
        "page"                         => "Page",
        "records"                      => "record(s)",
        "of"                           => "of",
        "first"                        => "First",
        "previous"                     => "Prev",
        "next"                         => "Next",
        "last"                         => "Last",
        "jump to %d of next pages"     => "Jump to %d of next pages",
        "jump to %d of previous pages" => "Jump to %d of previous pages",
        "go to"                        => "Go to",
        "next page"                    => "next page",
        "previous page"                => "previous page",
        "first page"                   => "first page",
        "last page"                    => "last page",
    ];
} else if ($globalLanguage == "jp") {
    $ARRAY_PAGE_LIMIT = ["全て", "100", "50", "25", "20", "15"];
    $DATAGRIDWORDS = [
        "any part of field"            => "全てのフィールド",
        "view"                         => "表示",
        "please wait"                  => "お待ちください",
        "loading grid"                 => "ローディンググリッド",
        "view_denied"                  => "You don't have rights to view this page.<br>Press back button or <a href=\"javascript:history.back();\">click here</a> to go back",
        "edit_denied"                  => "You don't have rights to edit/add data in this page.<br>Press back button or <a href=\"javascript:history.back();\">click here</a> to go back",
        "delete_denied"                => "You don't have rights to delete data in this page.<br>Press back button or <a href=\"javascript:history.back();\">click here</a> to go back",
        "no data"                      => "There are no data",
        "page"                         => "頁",
        "records"                      => "記録",
        "of"                           => "の",
        "first"                        => "最初",
        "previous"                     => "前",
        "next"                         => "次",
        "last"                         => "最終",
        "jump to %d of next pages"     => "Jump to %d of next pages",
        "jump to %d of previous pages" => "Jump to %d of previous pages",
        "go to"                        => "に行く",
        "next page"                    => "次のページ",
        "previous page"                => "前のページ",
        "first page"                   => "先頭ページ",
        "last page"                    => "最終ページ",
    ];
} else {
    $ARRAY_PAGE_LIMIT = ["semua", "100", "50", "25", "20", "15"];
    $DATAGRIDWORDS = [
        "any part of field"            => "Semua Field",
        "view"                         => "tampilkan",
        "please wait"                  => "Tunggu",
        "loading grid"                 => "Loading grid",
        "view_denied"                  => "Anda tidak mempunyai hak untuk melihat halaman ini.<br>Silahkan klik tombal back, atau <a href=\"javascript:history.back();\">Klik disini</a> untuk kembali.",
        "edit_denied"                  => "Anda tidak mempunyai hak untuk mengubah/menambah data pada halaman ini.<br>Silahkan klik tombal back, atau <a href=\"javascript:history.back();\">Klik disini</a> untuk kembali.",
        "delete_denied"                => "Anda tidak mempunyai hak untuk menghapus data pada halaman ini.<br>Silahkan klik tombal back, atau <a href=\"javascript:history.back();\">Klik disini</a> untuk kembali.",
        "no data"                      => "Tidak ada data",
        "page"                         => "Halaman",
        "records"                      => "record",
        "of"                           => "dari",
        "first"                        => "First",
        "previous"                     => "Prev",
        "next"                         => "Next",
        "last"                         => "Last",
        "jump to %d of next pages"     => "Lompat sebanyak %d ke halaman berikutnya",
        "jump to %d of previous pages" => "Lompat sebanyak %d ke halaman sebelumnya",
        "go to"                        => "Pergi ke",
        "next page"                    => "halaman berikutnya",
        "previous page"                => "halaman sebelumnya",
        "first page"                   => "halaman awal",
        "last page"                    => "halaman terakhir",
    ];
}
//default SKIN/CSS
$DATAGRID_CSS = "invosa1";
//set this to false, unless you already include prototype.js script in your PHP/HTML
//this variable to check is the prototype.js already loaded or not
//if ONCE loaded (true) then it will not be included again
$GLOBALS['PROTOTYPE_LOADED'] = false;
?>
