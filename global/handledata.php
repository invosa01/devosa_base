<?php
//Untuk menghandle PENYIMPANAN DATA DAN MENCEGAH DUPLIKASI DATA/ RE-POST DATA
//---------------------------------------------------------------------------------------------------------------------------
//fungsi untuk menghandle/ mencegah re-posting/re-freshing page pada kasus inserting data
//kalo tidak dihandle bisa terjadi multiple insertion pada database
//data UNIK yang akan disimpan dicookie di MD5
//sehingga yang disimpan di cookie cuman 16 byte, meng-hemat space
//kemudian dibandingkan kembali, jika sama berarti re-post
//by Dedy Sukandar
//versi 1.0
//august 2006
DEFINED(
    'VALID_APPLICATION'
) or die("Sorry, direct access to page <span style=\"color:red\">" . $_SERVER['PHP_SELF'] . "</span> is prohibited!");
//fungsi untuk menggenerate nama variable cookie
//berdasar nama file pemanggil
function generateVariableCookie()
{
    //get Nama Cookies sesuai dengan nama file PHP yang memanggil
    $strPages = explode("/", $_SERVER['PHP_SELF']);
    //buang karakter yang tidak valid sebagai nama variable
    $strPages = preg_replace("/[^a-z]/i", '', $strPages);
    //default jika terjadi kesalahan pengambilan adalah untitled
    $pageName = "untitled";
    if (count($strPages) > 0) {
        $pageNamePHP = $strPages[count($strPages) - 1];
        $pageNames = explode("\.", $pageNamePHP);
        $pageName = $pageNames[0];
    }
    $namaVariable = 'cookie_' . $pageName;
    return $namaVariable;
}

//panggil fungsi ini setelah proses save di server
//pass value dengan data yang unik
function setDataCookie($currentCookieData)
{
    $currentCookieData = md5($currentCookieData);
    setcookie(generateVariableCookie(), $currentCookieData, time() + +3600000);
}

function getDataCookie()
{
    $namaVariable = generateVariableCookie();
    (isset($_COOKIE[$namaVariable])) ? $currentCookieData = $_COOKIE[$namaVariable] : $currentCookieData = null;
    return $currentCookieData;
}

//passData here to check
//apakah data yang sama dikirim ke server?
//compare data cookie (yang sebelumnya sudah disimpan pada fungsi setData)  dengan passData
//jika sama return true berarti data report, yang nantinya ngga usah disimpan
function isRepostData($passData)
{
    $cookieData = getDataCookie();
    if ($cookieData === null) {
        return false;
    } else {
        return ($cookieData == md5($passData));
    }
}

function executeSaveSQL($strSQL, $strPageName, &$strMessage, $closingDbOnExit = true)
{
    $db = new CDbClass();
    $strMessage = "";
    $isSaved = false;
    if (isRepostData($strSQL)) {
        //previous data already save because is still in the cookie with the precies same value
        $strMessage = vsprintf(getWords("data %s saved"), strtolower($strPageName));
        $isSaved = true;
    } else {
        if ($strSQL != "") {
            if ($db->connect()) {
                // cek validasi -----------------------
                if ($db->execute($strSQL)) {
                    $isSaved = ($db->affectedRows() != 0);
                    if ($isSaved) {
                        $strMessage = vsprintf("Data %s saved", strtolower($strPageName));
                        setDataCookie($strSQL);
                    } else {
                        $strMessage = vsprintf("Failed to save data %s", strtolower($strPageName));
                    }
                } else {
                    $strMessage = vsprintf(getWords("failed to save data %s"), strtolower($strPageName));
                }
                if ($closingDbOnExit) {
                    $db->close();
                }
            } else {
                $strMessage = getWords("connect failed");
                $isSaved = false;
            }
        }
    }
    return $isSaved;
}

function executeDeleteSQL($strSQL, $strPageName, &$strMessage, $closingDbOnExit = true)
{
    $db = new CDbClass();
    $isDeleted = false;
    $strMessage = "";
    if ($strSQL != '') {
        if ($db->connect()) {
            if ($db->execute($strSQL)) {
                $isDeleted = ($db->affectedRows() != 0);
                if ($isDeleted) {
                    $strMessage = vsprintf("Data %s deleted", strtolower($strPageName));
                } else {
                    $strMessage = vsprintf("Failed to delete data %s", strtolower($strPageName));
                }
            } else {
                $strMessage = vsprintf(getWords("failed to delete data %s"), strtolower($strPageName));
            }
            if ($closingDbOnExit) {
                $db->close();
            }
        }
    }
    //penting untuk menghilangkan cookie dari last action save karena sekarang last actionnya adalah delete.
    //sebenarnya bisa juga dilakukan pengecekan seperti saat save untuk menghindari multiple DELETION,
    //tetapi karena untuk DELETION tidak akan terjadi perulangan maka tidak perlu, cukup kosongkan cookie sebelumnya saja
    setDataCookie("");
    return $isDeleted;
}

function executeNormalSQL($strSQL, $closingDbOnExit = true)
{
    $db = new CDbClass();
    $result = false;
    if ($strSQL != '') {
        if ($db->connect()) {
            $result = $db->execute($strSQL);
            if ($closingDbOnExit) {
                $db->close();
            }
        }
    }
    //echo $strSQL;
    return $result;
}

?>