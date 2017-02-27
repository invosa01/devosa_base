<?php
/*
  Daftar variabel dan fungsi global
    Author: Yudi K.
*/
include("../global.php");
include_once("../global/common_variable.php");
include_once("../global/common_function.php");
include_once("../global/date_function.php");
include_once("../global/approval_function.php");
include_once("../global/form_function.php");
include_once("../global/words.php");
$strPrintCss = "../css/bw.css"; // file CSS untuk print
$strPrintInit = "window.print();";
$strCopyright = COPYRIGHT;
$strWordsINPUTDATA = getWords("input data");
$strWordsLISTOF = getWords("list of");
$strWordsFILTERDATA = getWords("filter data");
$strConfirmSave = $messages['confirm_save'];
$strConfirmApprove = $messages['confirm_approve'];
$strConfirmDelete = $messages['confirm_delete'];
$strConfirmChangeStatus = $messages['confirm_change_status'];
/*
  if (isset($_REQUEST['dataCompany'])) $intIdCompany = $_REQUEST['dataCompany'];
  else if (isset($_REQUEST['filterCompany'])) $intIdCompany = $_REQUEST['filterCompany'];
  else if (isset($_SESSION['sessionIdCompany'])) $intIdCompany = $_SESSION['sessionIdCompany'];
  else $intIdCompany = -1;
  $bolIdCompany = ($intIdCompany == -1 || $intIdCompany == "");
  if (!$bolIdCompany)
  { 
     $strKriteriaCompany = " AND id_company = '$intIdCompany' ";
     $strDataCompany     = $intIdCompany ;
     $strFilterCompany   = $intIdCompany ;
     if (isset($_SESSION['sessionIdCompany']) && $_SESSION['sessionIdCompany'] != -1 && $_SESSION['sessionIdCompany'] != "" )
     {
         $strKriteria2       = "WHERE id = ".$_SESSION['sessionIdCompany']." ";
         $strEmptyOption2    = "";
         $bolCompanyEmptyOption = false;
         $arrCompanyEmptyData = null;
     }
     else
     {
         $strKriteria2       = "";
         $strEmptyOption2    = $strEmptyOption;
         $bolCompanyEmptyOption = true;
         $arrCompanyEmptyData = array("value" => "", "text" => "", "selected" => true);
     }
  }
  else
  {
     $strKriteriaCompany = "";
     $strDataCompany     = "";
     $strFilterCompany   = "";
     $strKriteria2       = "";
     $strEmptyOption2    = $strEmptyOption;
     $bolCompanyEmptyOption = true;
     $arrCompanyEmptyData = array("value" => "", "text" => "", "selected" => true);
  }
*/
$intPageLimit = 10; // jumlah link page maksimal yang ditampilkan
$intRowsLimit = 50; // jumlah baris yang ditampilkan satu page
?>
