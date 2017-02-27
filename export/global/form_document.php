<?php
//need to include global.php before call this file
//function formatLeadingZero in global.php required
DEFINE("DOCUMENT_RESET_MONTHLY", 1);
DEFINE("DOCUMENT_RESET_YEARLY", 2);
DEFINE("DOCUMENT_RESET_NEVER", 3);
$resetType = DOCUMENT_RESET_MONTHLY;
function formatLeadingZero($num, $leading)
{
  return sprintf("%0" . $leading . "d", $num);
}

//general document form generator
//parameter : $dtTransDate = format YYYY-MM-DD
//TODO: CHECKING INPUT
//Output : array (number, formatted_doc_number);
function getFormDocumentNumber(
    $dtTransDate,
    $tblName,
    $docDateName,
    $columnNumberName,
    $formatterFunction,
    $forceResetType = null
) {
  global $resetType;
  if ($forceResetType == null) {
    $forceResetType = $resetType;
  }
  $strResult1 = "";
  $strResult2 = "";
  if ($dtTransDate != "") {
    list($intYear, $intMonth, $intDay) = explode("-", $dtTransDate);
    $db = new CDbClass;
    if ($db->connect()) {
      switch ($forceResetType) {
        CASE DOCUMENT_RESET_MONTHLY :
          $strSQL = "
              SELECT MAX(" . $columnNumberName . ") AS " . $columnNumberName . "
                FROM " . $tblName . "
                WHERE DATE_PART('year', " . $docDateName . ") = $intYear AND
                      DATE_PART('month', " . $docDateName . ") = $intMonth ";
          break;
        CASE DOCUMENT_RESET_YEARLY :
          $strSQL = "
              SELECT MAX(" . $columnNumberName . ") AS " . $columnNumberName . "
                FROM " . $tblName . "
                WHERE DATE_PART('year', " . $docDateName . ") = $intYear ";
          break;
        CASE DOCUMENT_RESET_NEVER :
          $strSQL = "
              SELECT MAX(" . $columnNumberName . ") AS " . $columnNumberName . "
                FROM " . $tblName . "";
          break;
      }
      $res = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($res)) {
        $strResult1 = intval($rowDb[$columnNumberName]) + 1;
        $strResult2 = $formatterFunction($intYear, $intMonth, $strResult1);
      }
    }
  }
  $arrResult = [$strResult1, $strResult2];
  return $arrResult;
}

function formattingDocumentMRF($year, $month, $no)
{
  if (isset($_SESSION['sessionCompanyData']['code'])) {
    return "MRF/" . $_SESSION['sessionCompanyData']['code'] . "/$year/" . formatLeadingZero(
        $month,
        2
    ) . "/" . formatLeadingZero($no, 3);
  } else {
    return "MRF/$year/" . formatLeadingZero($month, 2) . "/" . formatLeadingZero($no, 3);
  }
}

?>