<?php
session_start();
include_once("global.php");
include_once("global/common_function.php");
include_once("classes/adm/adm_user.php");
//inisialisasi
$strPageTitle = "Login Page - Secure Area";
$strCopyright = COPYRIGHT;
$dataUserName = getPostValue('dataUserName');
$dataPassword = getPostValue('dataPassword');
$strDataPage = getRequestValue('dataPage');
$strInputPage = "<input name=\"dataPage\" type=\"hidden\" value=\"$strDataPage\">";
$strWordsUsername = getWords("username");
$strWordsPassword = getWords("password");
$strErrorAlert = "";
if (isset($_POST['btnLogin'])) {
  //jika sudah login atau session sudah ada, maka hapus semua session untuk membebaskan memory
  session_unset();
  if (IsAuthenticated($dataUserName, $dataPassword, $strErrorAlert)) {
    $_SESSION['sessionLanguage'] = DEFAULT_LANGUAGE;//$dataLanguage;
    if ($strDataPage != "") {
      redirectPage($strDataPage);
    } else {
      header("location:hrd/main.php");
    }
    exit();
  } //else if (isAuthenticatedAssetDataBank($dataUserName,$dataPassword, $strErrorAlert)) /*do nothing */;
  else {
    $strErrorAlert = "alert('" . $strErrorAlert . "');";
  }
}
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate("templates/index.html");
$tbsPage->Show();
//daftar fungsi
function IsAuthenticated($username, $pwd, & $errorMessage)
{
  $errorMessage = '';
  $tblUser = new cAdmUser();
  if ($rowDb = $tblUser->authenticate($username, $pwd)) {
    $_SESSION['sessionUserID'] = $rowDb['id_adm_user'];
    $_SESSION['sessionUserName'] = $rowDb['name'];
    $_SESSION['sessionUser'] = $username;
    $_SESSION['sessionLanguage'] = DEFAULT_LANGUAGE;
    $_SESSION['sessionEmployeeID'] = $rowDb['employee_id'];
    $_SESSION['sessionIdGroup'] = $rowDb['id_adm_group'];
    $_SESSION['sessionPermissionGroup'] = $rowDb['permission_group'];
    $_SESSION['sessionIdCompany'] = $rowDb['id_adm_company'];
    $_SESSION['sessionEmployeeID'] = $rowDb['employee_id'];
    $_SESSION['sessionUserRole'] = $rowDb['group_role'];
    $_SESSION['sessionGroupRole'] = $rowDb['group_role'];
    $_SESSION['sessionBasePath'] = dirname($_SERVER['SCRIPT_NAME']);
    $_SESSION['sessionDefaultModuleID'] = $rowDb['id_adm_module'];
    $arrModule = getDataModule($_SESSION['sessionIdGroup']);
    //print_r($arrModule);
    if (!isset($arrModule[$_SESSION['sessionDefaultModuleID']])) {
      foreach ($arrModule as $module) {
        $_SESSION['sessionDefaultModuleID'] = $module;
        break;
      }
    }
    writeLog(ACTIVITY_LOGIN);
    return true;
  } else {
    $errorMessage = "Invalid user name or password!";
  } // gagal
  writeLog(ACTIVITY_LOGIN, -1, $errorMessage . "/" . $username . "/" . $pwd);
  return false;
}

function getDataModule($intIdGroup)
{
  $tblModule = new cModel("adm_module", "module");
  $arrData = $tblModule->query(
      "
        SELECT m.id_adm_module 
          FROM adm_group_menu AS g 
                INNER JOIN 
               adm_menu AS m
                ON g.id_adm_menu = m.id_adm_menu
          WHERE g.id_adm_group = $intIdGroup
          GROUP BY m.id_adm_module
          ORDER BY m.id_adm_module"
  );
  $arrModule = [];
  foreach ($arrData as $rowDb) {
    $arrModule[$rowDb['id_adm_module']] = $rowDb['id_adm_module'];
  }
  return $arrModule;
}

?>
