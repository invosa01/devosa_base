<?php
/*****************************************
 * Login Page
 * Copyright (c) Invosa Systems, PT
 *
 * login.php
 * Author:  Yudi K.
 *
 * Ver   :  1.0
 ******************************************/
session_start();
include("global.php");
//include(getTemplate("words.inc"));
$strTemplateFile = getTemplate("login.html");
$tAdm = "Administrator";
$tAdmP = "SMART-U";
//inisialisasi
$strPageTitle = 'Payroll System -- SMART-U';
$strLogin = "";
$strPassword = "";
$strLang = "";
$strError = "";
(isset($_REQUEST['dataPage'])) ? $strRedirectPage = $_REQUEST['dataPage'] : $strRedirectPage = "main.php";
$strHidden = "<input type=hidden name='dataPage' value='$strRedirectPage'>";
$bolIsLogin = false;
// cek apakah sudah login sebelumnya atau tidak
if (isset($_SESSION['sessionUser'])) {
    $bolIsLogin = ($_SESSION['sessionUser'] != "");
    header("Location:$strRedirectPage");
    exit;
}
if (isset($_REQUEST['btnLogin']) && !$bolIsLogin) {
    // ambil data-data yang dikirimkan --> post
    if (isset($_REQUEST['dataLogin'])) {
        $strLogin = $_REQUEST['dataLogin'];
    }
    if (isset($_REQUEST['dataPassword'])) {
        $strPassword = $_REQUEST['dataPassword'];
    }
    if (isset($_REQUEST['dataLanguage'])) {
        $strLang = $_REQUEST['dataLanguage'];
    }
    // database connect
    $db = new CdbClass;
    if ($db->connect()) {
        $strSQL = 'select * from "allUser"';
        $resDb = $db->execute($strSQL);
        // cek apakah ada data dalam tabel user
        if (($resDb = $db->execute($strSQL)) == false) {
            // baca tabel error, mungkin tidak ada tabel user, cek apakah memakai login administrator
            $strError = "<script>alert('Login failed! DB Error');</script>"; // gagal
        } else {
            // ada database "user" --> SQL tidak error
            if ($db->numrows($resDb) == 0) {
                // tidak ada data dalam tabel user, cek apakah memakai login administrator
                $strError = "<script>alert('Login failed! No User');</script>"; // gagal
            } else {
                // ada data di tabel user
                // cek data sesuai login yang diberikan
                $strSQL = "SELECT * FROM all_user ";
                $strSQL .= "WHERE login='$strLogin' ";
                $resDb = $db->execute($strSQL);
                if ($db->numrows($resDb) > 0) {
                    $rowDb = $db->fetchrow($resDb);
                    // user ditemukan dalam tabel -- ada user
                    if ($rowDb['passwd'] == md5($strPassword)) {
                        // sukses, password sesuai
                        $_SESSION['sessionUserName'] = $rowDb['name'];
                        $_SESSION['sessionUser'] = $strLogin;
                        $_SESSION['sessionLanguage'] = $strLang;
                        $_SESSION['sessionUserType'] = $rowDb['type'];
                        $_SESSION['sessionUserRole'] = $rowDb['role'];
                        $_SESSION['sessionUserModule'] = $rowDb['module'];
                        $_SESSION['sessionUserID'] = $rowDb['id'];
                        writeLog(ACTIVITY_LOGIN, MODULE_PAYROLL);
                        header("Location:$strRedirectPage");
                        exit;
                    } else {
                        // password tidak berhasil
                        $strError = "<script>alert('Login failed! Password Error');</script>";
                        writeLog(ACTIVITY_LOGIN, MODULE_PAYROLL, "Login failed, error password : $strLogin", 1);
                    }
                } else {
                    $strError = "<script>alert('Login failed! User Not Found');</script>"; // gagal
                    writeLog(ACTIVITY_LOGIN, MODULE_PAYROLL, "Login failed, user not found : $strLogin", 1);
                }
            }
        }
    } else {
        // konfigurasi salah
        $strError = "<script>alert('Login failed! Error Setting');</script>";
    }
}
$strInitAction = " top.document.location.href = './index.php'";
// jika tidak ada redirect
$tbsPage = new clsTinyButStrong;
echo $strError;
$tbsPage->LoadTemplate($strTemplateFile);
$tbsPage->Show();
?>
