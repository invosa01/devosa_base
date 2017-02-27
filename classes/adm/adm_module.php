<?php

class cAdmModule extends cModel
{

  var $strEntityName = "module";

  var $strTableName = "adm_module";

  function cAdmModule()
  {
    parent::cModel();
  }

  //MENGAMBIL MODULE DARI CURRENT USER DARI TABLE
  function findAllByIdAdmGroup($idGroup)
  {
    //get from database
    $arrModule = [];
    $strSQL = "
      SELECT a.id_adm_module, a.name 
        FROM adm_module AS a 
            INNER JOIN 
            (SELECT id_adm_module FROM adm_menu 
              WHERE id_adm_menu IN (SELECT id_adm_menu FROM adm_group_menu WHERE id_adm_group = " . $_SESSION['sessionIdGroup'] . ")
              GROUP BY id_adm_module) AS b
              ON a.id_adm_module = b.id_adm_module
        ORDER BY a.sequence_no";
    $res = $this->db->execute($strSQL);
    while ($rowDb = $this->db->fetchrow($res, "ASSOC")) {
      $arrModule[] = $rowDb;
    }
    return $arrModule;
  }

  function getMaxSequenceNo()
  {
    $strSQL = "SELECT MAX(sequence_no) AS sequence_no FROM " . $this->strTableName;
    $res = $this->db->execute($strSQL);
    if ($rowDb = $this->db->fetchrow($res)) {
      return $rowDb['sequence_no'];
    }
    return 0;
  }
}

?>