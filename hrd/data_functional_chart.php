<!DOCTYPE HTML>
<html>
<head>
    <link rel="stylesheet" href="../css/demo.css" />
    <link rel="stylesheet" href="../css/jquery.orgchart.css" />
    <script src="../js/jquery.min.js"></script>
    <script src="../js/jquery.orgchart.js"></script>
    <script>
        $(function () {
            $("#organisation").orgChart({container: $("#main")});
        });
    </script>

</head>
<body>


<?php
include_once('../global/session.php');
include_once('global.php');
$db = new CdbClass;
$db->connect();
$strSQL .= " SELECT * FROM hrd_functional ";
$strSQL .= " WHERE head_code = 'head'";//hapus bila sudah ada data sebenar nya
$result = $db->execute($strSQL);
while ($row = $db->fetchrow($result)) {
    $strFunc = $row['functional_code'];
    //$strName = $row['functional_name'];
}
//echo "<a href=data_functional_chart.php>FULL";
$strSQL2 .= " SELECT * FROM hrd_employee ";
$strSQL2 .= " WHERE functional_code = '" . $strFunc . "' ORDER BY employee_name ";
$result = $db->execute($strSQL2);
echo "<div id='left'>";
echo "<ul id='organisation'>";
echo "<li><a href=data_functional_chart.php?func=" . $strFunc . ">" . getFunctionalName($strFunc) . "</a>";
while ($row = $db->fetchrow($result)) {
    echo "<dl><dt>" . ucwords(strtolower($row['employee_name'])) . "</dt></dl>";
}
echo "<ul>" . funcChart($strFunc);
echo "</li></ul>";
echo "</div>";
echo "<div id ='content'>";
echo "<div id='main'></div></div>";
///////////////////////////////////////////////////////////////////////////////////////////////////
function funcChart($strFunc)
{
    global $db;
    if (hasMember($strFunc)) {
        echo "<ul>";
        $member = getMember($strFunc);
        foreach ($member as $keyMember => $valueMember) {
            $strSQL2 .= " SELECT * FROM hrd_employee ";
            $strSQL2 .= " WHERE functional_code = '" . $valueMember . "' ORDER BY employee_name ;";
            $result2 = $db->execute($strSQL2);
            echo "<li><a href=data_functional_chart.php?func=" . $valueMember . ">" . getFunctionalName(
                    $valueMember
                ) . "</a>";
            echo "<dl>";
            while ($row = $db->fetchrow($result2)) {
                echo "<dt>" . ucwords(strtolower($row['employee_name'])) . "</dt><br>";
            }
            echo "</dl>";
            $strFunc = $valueMember;
            funcChart($strFunc);
        }
        echo "</ul>";
    } else {
        echo "</li>";
    }
}

function hasMember($strFunc)
{
    global $db;
    $strCount .= " SELECT COUNT(*) as total FROM hrd_functional ";
    $strCount .= " WHERE head_code = '" . $strFunc . "' ";
    $result = $db->execute($strCount);
    if ($row = $db->fetchrow($result)) {
        $intCount = $row['total'];
    }
    if ($intCount > 0) {
        return true;
    } else {
        return false;
    }
}

function getMember($strFunc)
{
    global $db;
    $strSQL .= " SELECT * FROM hrd_functional ";
    $strSQL .= " WHERE head_code = '" . $strFunc . "' ";
    $result = $db->execute($strSQL);
    while ($row = $db->fetchrow($result)) {
        $arrMember[] = $row['functional_code'];
    }
    return $arrMember;
}

function getFunctionalName($strData)
{
    global $db;
    $strSQL .= " SELECT functional_name FROM hrd_functional ";
    $strSQL .= " WHERE functional_code = '" . $strData . "' ";
    $result = $db->execute($strSQL);
    while ($row = $db->fetchrow($result)) {
        $strResult = $row['functional_name'];
    }
    return $strResult;
}

?>

</body>
</html>
