<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <title>Please wait ...</title>
    <link href="artajasa.css" rel="stylesheet" type="text/css">
    <script>
        function getData() {

            <?php
              // bikin script untuk copy content ke parent window
              if (isset($_REQUEST['objectName'])) {
                if (($strObj = $_REQUEST['objectName']) != "" && ($strView = $_REQUEST['objectView']) != "") {

                  echo "var dstView = opener.document.getElementById('$strView');\n";
                  echo "var dst = opener.document.getElementById('$strObj');\n";
                  //echo "var namaObj = '$strObj';";
                  echo "var src = document.getElementById('data');\n";
                  echo "dst.innerHTML = src.innerHTML;\n";
                  echo "(document.all) ? dstView.style.display = 'block' : dstView.style.display = 'table-row';\n";

                  //echo "opener.showData(namaObj, src);\n";
                }
              } else {
              }
            ?>

            close();
        }
    </script>
</head>
<body>
<?php
include_once('global.php');
//include_once(getTemplate("words.inc"));
echo "<strong>" . $messages['please_wait'] . "</strong>";
// ambil data
$strData = "<div id='data' style=\"display:none\"><table><tr>\n";
$strData .= "<td colspan=1 bgColor=#FFFFCC>&nbsp;</td>\n";
$strData .= "<td colspan=5 bgColor=#FFFFCC>";
$db = new CdbClass;
if ($db->connect()) {
    if (isset($_REQUEST['dataID']) && $_REQUEST['dataID'] != "") {
        $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name ";
        $strSQL .= "FROM hrd_shift_group_member AS t1 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
        $strSQL .= "WHERE t1.\"idGroup\" = " . $_REQUEST['dataID'];
        $resDb = $db->execute($strSQL);
        if ($db->numrows($resDb) > 0) {
            $strData .= "<table cellspacing=0 cellpadding=1 border=0 width=100%>\n";
            while ($rowDb = $db->fetchrow($resDb)) {
                $strData .= " <tr valign=top>\n";
                $strData .= "  <td nowrap>&nbsp;" . $rowDb['employee_id'] . "</td>\n";
                $strData .= "  <td>&nbsp;" . $rowDb['employee_name'] . "</td>\n";
                $strData .= " </tr>\n";
            }
            $strData .= "</table></div>\n";
        } else {
            $strData .= "&nbsp;";
        }
    }
}
$strData .= "<td colspan=1 bgColor=#FFFFCC>&nbsp;</td>\n";
$strData .= "</td></tr></table>\n";
echo $strData;
?>
<script>
    getData();
</script>
</body>