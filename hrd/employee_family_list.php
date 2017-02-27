<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <title>...</title>
    <link href="artajasa.css" rel="stylesheet" type="text/css">
    <script>

    </script>
</head>
<body>
<table width=100% cellpadding=2 cellspacing=0 border=0 class=gridTable>

    <?php
    include_once('global.php');
    //include_once(getTemplate("words.inc"));
    $db = new CdbClass;
    if ($db->connect()) {
        $strData = "";
        if (isset($_REQUEST['dataEmployee']) && $_REQUEST['dataEmployee'] != "") {
            // cari info employee
            $strKode = $_REQUEST['dataEmployee'];
            $strSQL = "SELECT id, employee_name FROM hrd_employee WHERE employee_id = '$strKode' ";
            $resDb = $db->execute($strSQL);
            if ($rowDb = $db->fetchrow($resDb)) {
                $strID = $rowDb['id'];
                $strNama = $rowDb['employee_name'];
            }
            $strData .= "<tr valign=top><td colspan=5><h2>$strKode - $strNama</h2></td></tr>\n";
            // cari daftar keluarga
            $intRows = 0;
            $strSQL = "SELECT *,EXTRACT(year FROM AGE(birthday)) AS umur FROM hrd_employee_family ";
            $strSQL .= "WHERE id_employee = '$strID' ORDER BY relation, birthday DESC ";
            $resDb = $db->execute($strSQL);
            while ($rowDb = $db->fetchrow($resDb)) {
                $intRows++;
                $strGender = ($rowDb['gender'] == 1) ? "M" : "F";
                $strRelation = $words[$ARRAY_FAMILY_RELATION[$rowDb['relation']]];
                $strData .= " <tr valign=top>\n";
                $strData .= "  <td nowrap align=right>$intRows&nbsp;</td>\n";
                $strData .= "  <td nowrap>&nbsp;" . $rowDb['name'] . "</td>\n";
                $strData .= "  <td nowrap>&nbsp;" . $strGender . "</td>\n";
                $strData .= "  <td nowrap>&nbsp;" . $strRelation . "</td>\n";
                $strData .= "  <td nowrap>&nbsp;" . $rowDb['umur'] . "</td>\n";
                $strData .= " </tr>\n";
            }
            if ($intRows == 0) {
                $strData .= "<tr valign=top><td colspan=5><strong>&nbsp;No data</strong></td></tr>\n";
            }
        }
    }
    echo $strData;
    ?>
    <tr>
        <td align=center colspan=5><input type=button onClick="window.close()" value="Close"></td>
    </tr>
</table>
</body>