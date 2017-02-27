<?php
include_once('../global/session.php');
include_once('../global.php');
include_once('../includes/form2/form2.php');
include_once("../global/common_function.php");
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView) {
    die(getWords("view denied"));
}
$f = new clsForm("formInput", 1, "100%", "");
$f->showMinimizeButton = false;
$f->showCaption = false;
$f->addHelp(getWords("help for") . " " . $dataPrivilege['menu_name'], getHelps("backup data"), 8, 167, 400, 300);
$f->addLiteral(
    "",
    "btnBackUp",
    "
    <p>Click the button below to start backup<br>
      After show the download dialog, please save to your disk.</p>
    <input type='submit' class='btn btn-primary btn-sm' name='btnBackup' value='Backup'>",
    false
);
//$f->addInputFile(getWords("backup file"), "dataBackUp", '', "Please enter the backup file to restore", false, true, true, true, false, "string", "","","","",0,0);
$f->message = "";
if (isset($_POST['btnBackup'])) {
    $db = new CDbClass;
    if (backupData($db)) {
        exit();
        $f->message = "Backup complete.";
    }
}
$formInput = $f->render();
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$strWordMenuList = strtoupper(vsprintf(getWords("list of %s"), getWords("menu")));
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("database backup");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
exit();
$strTemplateFile = "templates/backup_data.html";
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intTotalData = 0;
$strDefaultName = date("Ymd") . ".backup";
$strBackupButton = "";
$strRestoreButton = "";
$strMessage = "";
$strMsgClass = "";
//----------------------------------------------------------------------
function myEscapeShellCmd($str)
{
    global $data;
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        if ($str != null) {
            $str = str_replace('"', '""', $str);
        }
        return '"' . $str . '"';
    } else {
        return escapeshellcmd($str);
    }
}

function backupData($db, $compressed = false)
{
    // Prevent timeouts on large exports (non-safe mode only)
    if (!ini_get('safe_mode')) {
        set_time_limit(0);
    }
    $strBackupName = "backup_" . date("Ymd");
    if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') && isset($_SERVER['HTTPS'])) {
        header('Content-Type: text/plain');
    } else {
        header('Content-Type: application/download');
        if ($compressed) {
            header('Content-Disposition: attachment; filename=' . $strBackupName . '.sql.gz');
        } else {
            header('Content-Disposition: attachment; filename=' . $strBackupName . '.sql');
        }
    }
    putenv('PGPASSWORD=' . DB_PWD);
    putenv('PGUSER=' . DB_USER);
    putenv('PGHOST=' . DB_SERVER);
    putenv('PGPORT=' . DB_PORT);
    // Get the path og the pg_dump/pg_dumpall executable
    $exe = myEscapeShellCmd(PG_DUMP_PATH);
    // Build command for executing pg_dump.  '-i' means ignore version differences.
    $cmd = $exe . " -i";
    // MSIE cannot download gzip in SSL mode - it's just broken
    if (!(strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') && isset($_SERVER['HTTPS']))) {
        if ($compressed) {
            $cmd .= " -Z 9";
        }
    }
    $cmd .= ' -d ' . DB_NAME;
    putenv('PGDATABASE=' . DB_NAME);
    // Execute command and return the output to the screen
    passthru($cmd);
    return true;
} //backupData
// restoreData, dari file dengan nama tertentu
// format filenya adalah bzip
function restoreData($db, $strFileName, &$strMsg)
{
    if ($strFileName == "") {
        $strMsg = getWords("empty_file_name");
        return false;
    }
    if (!file_exists($strFileName)) {
        $strMsg = getWords("file_not_found");
        return false;
    }
    $dtAwal = getdate();
    // baca file bzip
    $bz = bzopen($strFileName, "r");
    $strData = '';
    while (!feof($bz)) {
        $strData .= bzread($bz, 4096);
    }
    bzclose($bz);
    // mulai restoreData
    /*
    $dbTmp = new CdbClass;
    if ($dbTmp->connect("localhost", "5432", "temp", "mahawan", "mahawan"))
    {
      @$dbTmp->execute($strData);
    }

    // perbaiki sequence ID
    $dbTmp->fixSeqID();
    $dbTmp->close();
    */
    @$db->execute($strData);
    $db->fixSeqID();
    /*
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=data.sql" );
    echo $strData;
    exit();
    */
    $dtAkhir = getdate();
    $selisih = $dtAkhir[0] - $dtAwal[0];
    $jam = floor($selisih / 3600);
    $mnt = floor(($selisih - ($jam * 3600)) / 60);
    $dtk = ($selisih % 60);
    $strMsg = "Finish in : $jam hour $mnt minutes $dtk seconds";
    return true;
} //restoreData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    if ($bolCanEdit && isset($_REQUEST['btnBackup'])) {
        backupData($db);
    } else if ($bolCanEdit && isset($_REQUEST['btnRestore'])) {
        if (isset($_FILES['fileData']['tmp_name'])) {
            $bolOK = restoreData($db, $_FILES['fileData']['tmp_name'], $strMessage);
            $strMsgClass = ($bolOK) ? "bgOK" : "bgError";
        }
    }
    //$strDataDetail = getDataTable($db,$intTotalData);
    if (!$bolCanView) {
        $strMessage = getWords("view_denied");
        $strMsgClass = "bgError";
    } else {
        $strBackupButton = "<input type=submit name='btnBackup' value=\"" . getWords("backup data") . "\">";
        $strRestoreButton = "<input type=submit name='btnRestore' value=\"" . getWords(
                "restore data"
            ) . "\" onClick=\"return confirmRestore();\">";
    }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (!$dataPrivilege['icon_file']) {
    $dataPrivilege['icon_file'] = 'blank.png';
}
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$htmlContentFile = $strTemplateFile;
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
?>