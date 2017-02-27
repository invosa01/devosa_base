<?php

class cHrdOrganizationDetail extends cModel
{

  var $strEntityName = "organization detail";

  var $strMessage = "";

  var $strTableName = "hrd_organization_detail";

  function cHrdOrganizationDetail()
  {
    parent::cModel();
  }

  /*
  function findById($intID)
  {
    $strSQL = "
      SELECT a.*, b.name AS organization_name, b.levelling 
        FROM ".$this->strTableName." AS a
            INNER JOIN
              hrd_organization AS b
              ON a.id_hrd_organization = b.id
        WHERE a.id = ".intval($intID);
    $res = $this->db->execute($strSQL);
    if ($rowDb = $this->db->fetchrow($res))
    {
      return $rowDb;
    }
    return false;
  }*/

  function findAllByIdHrdOrganization($intID)
  {
    $strSQL = "
      SELECT a.*, b.name AS organization_name, b.levelling
        FROM " . $this->strTableName . " AS a
            INNER JOIN
              hrd_organization AS b
              ON a.id_hrd_organization = b.id
        WHERE a.id_hrd_organization = " . intval($intID) . "
        ORDER BY a.ordering ";
    $res = $this->db->execute($strSQL);
    $arrResult = [];
    while ($rowDb = $this->db->fetchrow($res)) {
      $arrResult[] = $rowDb;
    }
    return $arrResult;
  }

  function open()
  {
    $strSQL = "
      SELECT a.*, b.name AS organization_name, b.levelling
        FROM " . $this->strTableName . " AS a
            INNER JOIN
              hrd_organization AS b
              ON a.id_hrd_organization = b.id
        ORDER BY b.levelling, a.id_hrd_organization, a.ordering ";
    $res = $this->db->execute($strSQL);
    $data = [];
    while ($rowDb = $this->db->fetchrow($res)) {
      $data[$rowDb['id']] = $rowDb;
    }
    return $data;
  }

  function recurseTree($intLevel)
  {
    $strSQL = "
      SELECT a.*, b.name AS organization_name, b.levelling 
        FROM " . $this->strTableName . " AS a
            INNER JOIN
              hrd_organization AS b
              ON a.id_hrd_organization = b.id
        ORDER BY b.levelling, a.id_hrd_organization, a.ordering ";
    $arrData = $this->query($strSQL);
    $arrResult = [];
    $this->recurseTreeDetail($arrData, "", $intLevel, $arrResult);
    return $arrResult;
  }

  function recurseTreeDetail($arrData, $intID = "", $intLevel, &$arrResult)
  {
    $next_levelling = $intLevel + 1;
    foreach ($arrData as $key => $value) {
      if ($value['levelling'] == $intLevel && ($value['id_hrd_organization_detail'] == intval(
                  $intID
              ) || $intID == "")
      ) {
        $arrResult[$value['id']] = $value;//array("value"=>$value['id'], "text"=>$value['name'], "selected" => false);
        $this->recurseTreeDetail($arrData, $value['id'], $next_levelling, $arrResult[$value['id']]['child']);
      }
    }
    return $arrResult;
  }
}

?>