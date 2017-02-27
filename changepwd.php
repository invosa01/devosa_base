<?php
include_once('global/session.php');
include_once('global.php');
include_once('includes/form2/form2.php');
include_once('global/handledata.php');
//save link from HTTP Referrer into Cookie, used for back link
pushUrlReferrer();
$db = new CdbClass;
$strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
$CLASSFORMPATH = "./includes/form2/";
$f = new clsForm("formInput", 1, "100%", "");
$f->caption = getWords("change password");
//$f->addHelp(getWords("help for")." ".getWords("change password"), getHelps("change password"), 8, 167, 400, 300);
$f->addHidden("dataID", $strDataID);
$f->addPassword(
    getWords("old password"),
    "dataOldPassword",
    "",
    ["size" => 100, "maxlength" => 127],
    "string",
    true,
    true,
    true
);
$f->addPassword(
    getWords("new password"),
    "dataNewPassword1",
    "",
    ["size" => 100, "maxlength" => 127],
    "string",
    true,
    true,
    true
);
$f->addPassword(
    getWords("re-enter password"),
    "dataNewPassword2",
    "",
    ["size" => 100, "maxlength" => 127],
    "string",
    true,
    true,
    true
);
//$f->addSubmit("btnSave", getWords("save"),"Save this change", true,true,"","","", "saveData()","onClick=\"javascript:return validInput()\"");
//$f->addSubmit("btnCancel", getWords("cancel"),"Cancel", true,true,"","","", "goBack()","");
$f->addSubmit(
    "btnSave",
    getWords("save"),
    ["onClick" => "javascript:myClient.validInput();"],
    true,
    true,
    "",
    "",
    "saveData()"
);
$f->addButton("btnAdd", getWords("cancel"), []);
//$f->getRequest();
//$f->validateEntryBeforeSubmit=false;
$formInput = $f->render();
$tbsPage = new clsTinyButStrong;
$strJavascriptValidate = printJavascriptValidate();
//write this variable in every page
$strPageTitle = getWords("change password");
$pageIcon = "../images/icons/key.php";
$htmlContentFile = "templates/changepwd.html";
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("templates/master.html");
$tbsPage->Show();
//--------------------------------------------------------------------------------
// fungsi untuk menyimpan data
function saveData()
{
    global $db;
    global $f;
    $strmodified_byID = $_SESSION['sessionUserID'];
    $f->message = "";
    $strDataOldPassword = $f->value('dataOldPassword');
    $strDataNewPassword1 = $f->value('dataNewPassword1');
    $strDataNewPassword2 = $f->value('dataNewPassword2');
    if ($db->connect()) {
        //$db->execute("SELECT * FROM adm_user WHERE pwd  = md5('$strDataOldPassword') AND id_adm_user = '".$_SESSION['sessionUserID']."'");
        $strSQL = "SELECT * FROM adm_user WHERE pwd  = md5('$strDataOldPassword') AND id_adm_user = '" . $_SESSION['sessionUserID'] . "'";
        $resDb = $db->execute($strSQL);
        // echo $strSQL;
        // echo $db->numrows($resDb);
        // var_dump($resDb);
        // exit();
        if ($resDb) {
            if ($db->numrows($resDb) == 1) {
                $sessionUserId = "";
                while ($rowDb = $db->fetchrow($resDb)) {
                    $sessionUserId = $rowDb["id_adm_user"];
                }
                if ($sessionUserId == $_SESSION['sessionUserID']) {
                    $strSQL = "UPDATE adm_user ";
                    $strSQL .= "SET pwd  = '" . md5($strDataNewPassword1) . "' ";
                    $strSQL .= "WHERE id_adm_user = '" . $_SESSION['sessionUserID'] . "' ";
                } else {
                    $f->message = getWords("wrong old password");
                    return false;
                }
            } else {
                $f->message = getWords("wrong old password");
                return false;
            }
        } else {
            $f->message = getWords("Error update password");
            return false;
        }
    } else {
        $f->message = getWords("database connection error");
        return false;
    }
    $isSaved = executeSaveSQL($strSQL, getWords("password"), $f->message);
    if ($isSaved) {
        $f->setValue("dataOldPassword", "");
        $f->setValue("dataNewPassword1", "");
        $f->setValue("dataNewPassword2", "");
    }
    return $isSaved;
} // saveData
function goBack()
{
    $strRedirect = popUrlReferrer();
    if ($strRedirect == "") {
        $strRedirect = "main.php";
    }
    header("location:" . $strRedirect);
}

function printJavascriptValidate()
{
    return "
<script type=\"text/javascript\">
	<!--	
	//fungsi untuk memeriksa apakah input valid
	function validInput() {
		if (document.formInput.dataNewPassword1.value == \"\") 
    {
			alert('" . getWords("please type a new password") . "!');
			document.formInput.dataNewPassword1.focus();
			return false;
		}
		if (document.formInput.dataNewPassword1.value != document.formInput.dataNewPassword2.value) {
			alert(\"" . getWords("the new passwords and re-entered password are not the same") . "\");
			document.formInput.dataNewPassword1.focus();
			document.formInput.dataNewPassword1.select();
			return false;
		}
		return confirm('Do you want to save this entry?');
	}//validInput
	-->
</script>";
}

?>