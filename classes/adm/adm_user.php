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
        WHERE u.login_name='$username' AND u.pwd = MD5('" . $pwd . "') AND u.active is true";
        $arrData = $this->query($strSQL);
        // uddin  20150829
        // Update metode cek user login
        // PENTING: User login harus dengan query menghasilkan 1 record dan bandingkan lagi hasil query dengan param session
        if ((count($arrData) == 1) and ($arrData[0]["login_name"] == $username)) {
            return $arrData[0];
        }
        return false;
    }
}

?>