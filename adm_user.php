<?php

class cAdmUser extends cModel
{

    var $strEntityName = "user";

    var $strTableName = "adm_user";

    function cAdmUser()
    {
        parent::cModel();
    }

    function authenticate($username, $pwd)
    {
        $strSQL = "
      SELECT u.*, g.group_role
        FROM " . $this->strTableName . " AS u
          INNER JOIN adm_group AS g 
            ON u.id_adm_group = g.id_adm_group
        WHERE u.login_name='$username' AND u.pwd = MD5('" . $pwd . "')";
        $arrData = $this->query($strSQL);
        if (count($arrData) > 0) {
            return $arrData[0];
        }
        return false;
    }
}

?>