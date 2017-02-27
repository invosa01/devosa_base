<?php
include_once('../global/session.php');
include_once('../global.php');
$dataPrivilege = getDataPrivileges("master_group.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
    die("");
}
(isset($_GET['dataID'])) ? $varID = $_GET['dataID'] : $varID = "";
if ($varID == "") {
    exit();
}
$strData = "";
$db = new CdbClass();
if ($db->connect()) {
    $strSQL = "SELECT a.*, b.code, b.name as group_name, c.id as id_company , c.company_name  as company_name
          FROM adm_user AS a INNER JOIN adm_group AS b ON a.id_adm_group = b.id_adm_group 
          INNER JOIN ((SELECT id, company_name FROM hrd_company) UNION (select -1 as id, 'ALL' as company_name)) AS c on a.id_adm_company = c.id ";
    $strSQL .= "WHERE a.id_adm_group = " . $varID . " ";
    $strSQL .= "ORDER BY id_company";
    $db->execute($strSQL);
    if ($db->numrows() > 0) {
        $strData .= "<table class=\"dataGrid\" cellspacing=0 cellpadding=1 border=0 width=\"100%\">\n";
        $strData .= "  <tr valign=top>\n";
        $strData .= "    <th nowrap width=\"20\">No.</th>\n";
        $strData .= "    <th nowrap width=\"120\">Login Name</th>\n";
        $strData .= "    <th nowrap width=\"120\">Employee ID</th>\n";
        $strData .= "    <th nowrap>Name</th>\n";
        $strData .= "    <th nowrap>Company</th>\n";
        $strData .= "    <th nowrap>Status</th>\n";
        $strData .= "  </tr>";
        $i = 1;
        while ($rowDb = $db->fetchrow()) {
            $strData .= "  <tr valign=top>\n";
            $strData .= "    <td nowrap width=\"20\">&nbsp;" . ($i++) . "</td>\n";
            $strData .= "    <td nowrap width=\"120\">&nbsp;" . $rowDb['login_name'] . "</td>\n";
            $strData .= "    <td nowrap width=\"120\">&nbsp;" . $rowDb['employee_id'] . "</td>\n";
            $strData .= "    <td nowrap>&nbsp;" . $rowDb['name'] . "</td>\n";
            $strData .= "    <td nowrap>&nbsp;" . $rowDb['company_name'] . "</td>\n";
            if ($rowDb['active'] == 't') {
                $strData .= "    <td width=\"89\" nowrap align=\"center\">&nbsp;<font class=\"active\">" . getWords(
                        "active"
                    ) . "</font></td>\n";
            } else {
                $strData .= "    <td width=\"89\" nowrap align=\"center\">&nbsp;<font class=\"inactive\">" . getWords(
                        "inactive"
                    ) . "</font></td>\n";
            }
            $strData .= "  </tr>\n";
        }
        $strData .= "</table>\n";
    } else {
        $strData .= "&nbsp;";
    }
} else {
    $strData = "";
}
echo $strData;
?>
