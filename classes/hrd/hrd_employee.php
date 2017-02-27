<?php

class cHrdEmployee extends cModel
{

    var $strEntityName = "employee";

    var $strTableName = "hrd_employee";

    function cHrdEmployee()
    {
        parent::cModel();
    }
    /*function findAllByIdHrdOrganizationDetail($intID)
    {
      $strSQL = "
        SELECT e.*, o.id_hrd_organization_detail
          FROM ".$this->strTableName." AS e
            INNER JOIN
              hrd_employee_organization_detail AS o
                ON e.id = o.id_hrd_employee
          WHERE o.id_hrd_organization_detail=".intval($intID)." ORDER BY e.id ";

      $res = $this->db->execute($strSQL);
      $data = array();
      while ($rowDb = $this->db->fetchrow($res))
      {
        $data[$rowDb['id']] = $rowDb;
      }
      return $data;
    }  */
}

?>