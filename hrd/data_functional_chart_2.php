<?php
$hNode = 68;
//$hNode=50;
?><!DOCTYPE HTML>
<html>
<head>
    <link rel="stylesheet" href="../css/demo.css" />
    <link rel="stylesheet" href="../css/jquery.orgchart.css" />
    <script src="../js/jquery.min.js"></script>
    <script src="../js/jquery.orgchart.js"></script>
    <!-- <script type="text/javascript" src="../js/fsapi.js" onerror="alert('Error: failed to load ' + this.src)">
    </script>
    <script>
        // Set this to *false* to avoid addon auto-installation if missed.
        FireShotAPI.AutoInstall = true;
    </script> -->
    <script>
        $(function () {
            $("#organisation").orgChart({container: $("#main")});
        });
    </script>
    <style>
        div.orgChart div.node.one {
            background-color: #ffffff;
            height: <?php echo $hNode; ?>px;
        }

        div.orgChart div.node.moreone {
            background-color: #ffffff;
            min-height: <?php echo $hNode; ?>px;
        }

        div.orgChart tr.lines td.lvl {
            width: 1px;
            height: 0px;
        }

        div.orgChart tr.lines td.top {
            height: 17px;
        }

        <?php $hNode+=20+20; ?>
        /* 68 + 20 + 20 = 108*/
        .lvl1 {
            height: <?php echo ($hNode*1); ?>px;
        }

        .lvl2 {
            height: <?php echo ($hNode*2); ?>px;
        }

        .lvl3 {
            height: <?php echo ($hNode*3); ?>px;
        }

        .lvl4 {
            height: <?php echo ($hNode*4); ?>px;
        }

        .lvl5 {
            height: <?php echo ($hNode*5); ?>px;
        }

        .lvl6 {
            height: <?php echo ($hNode*6); ?>px;
        }

        .lvl7 {
            height: <?php echo ($hNode*7); ?>px;
        }

        .lvl8 {
            height: <?php echo ($hNode*8); ?>px;
        }

        .lvl9 {
            height: <?php echo ($hNode*9); ?>px;
        }

        .lvl10 {
            height: <?php echo ($hNode*10); ?>px;
        }

        .lvl11 {
            height: <?php echo ($hNode*11); ?>0px;
        }
    </style>
</head>
<body>
<!-- <body onLoad="FireShotAPI.checkAvailability()"> -->

<!-- <input type="button" class='btn-primary' onClick="FireShotAPI.savePage(true)" value="Save"> -->


<?php
include_once('../global/session.php');
include_once('global.php');
$db = new CdbClass;
$db->connect();
$pFunc = @$_GET["func"];
$pLevel = @$_GET["lvl"];
$firstLevel = 5; // isi dengan level functional yg deiset sebagai head (president directur)
//$strSQL .= " SELECT * FROM hrd_functional WHERE functional_code='ACC'";
//$strSQL .= " WHERE head_code is null OR head_code != 'unset'; ";//hapus bila sudah ada data sebenar nya
$strSQL .= " SELECT * FROM hrd_functional ";
$strSQL .= " WHERE functional_code = '170'";//hapus bila sudah ada data sebenar nya
$result = $db->execute($strSQL);
while ($row = $db->fetchrow($result)) {
    $strFunc = $row['functional_code'];
    //$strName = $row['functional_name'];
}
//echo "<a href=data_functional_chart.php>FULL";
$strSQL2 .= " SELECT * FROM hrd_employee ";
$strSQL2 .= " WHERE functional_code = '" . $strFunc . "' and active = 1 ORDER BY employee_name ";
$result = $db->execute($strSQL2);
// //TAMBAHAN UNTUK KOMITE AUDIT, COMMISSIONER, COMMISSIONER INDEPENDENT, PRESIDENT COMMISSIONER
// $strSQL3 .= " SELECT tf.*, te.employee_name as employee FROM hrd_functional as tf ";
// $strSQL3 .= " LEFT JOIN hrd_employee as te ON tf.functional_code = te.functional_code ";
// $strSQL3 .= " WHERE tf.head_code = '' ";
// $strSQL3 .= " ORDER BY functional_code; ";
//
// $res = $db->execute($strSQL3);
// echo "<table style='border:1px solid black'>";
// while($row = $db->fetchrow($res)) {
//     //echo "<dl><dt><svg x='10' width='350' height='30'><rect x='15' width='300' height='30' rx='20' ry='20' style='fill:white;stroke:gray;stroke-width:2px' /> <text x='20' y='20' text-anchor='start'>".ucwords(strtolower($row['functional_name'])).": ".ucwords(strtolower($row['employee']))."</text></svg></dt></dl>";
//     echo "<tr style='border:1px solid black'>";
//     echo "<td style='border:1px solid black'>".ucwords(strtolower($row['functional_name']))."</td>";
//     echo "<td style='border:1px solid black'>".ucwords(strtolower($row['employee']))."</td>";
//     echo "</tr>";
// }
// echo "</table>";
// //END TAMBAHAN
echo "<div id='left'>";
echo "<ul id='organisation'>";
echo "<li><a href=\"?func=" . $strFunc . "\">" . getFunctionalName($strFunc) . "</a>";
while ($row = $db->fetchrow($result)) {
    echo "<dl><dt>" . ucwords(strtolower($row['employee_name'])) . "</dt></dl>";
}
//echo "<ul><li>".funcChart($strFunc,1,1);
echo funcChart($strFunc, 1, 1);
echo "</li></ul>";
echo "</div>";
echo "<div id ='content'>";
echo "<div id='main'></div></div>";
///////////////////////////////////////////////////////////////////////////////////////////////////
function funcChart($strFunc, $n, $orgLvl)
{
    global $db;
    global $pLevel;
    global $pFunc;
    global $firstLevel;
    if (hasMember($strFunc)) {
        echo "<ul>";
        // $member = getMember($strFunc,$n);
        // foreach($member as $keyMember=>$valueMember) {
        $strSQL = " SELECT t1.*,t2.level,t2.position_code FROM hrd_functional as t1";
        $strSQL .= " LEFT JOIN hrd_position as t2 on t1.position_code=t2.position_code";
        $strSQL .= " WHERE t1.head_code = '" . $strFunc . "' ";
        if ($n == $pLevel and $pLevel == 1 and $pFunc != "") {
            $strSQL .= " AND t1.functional_code = '" . $pFunc . "' ";
        }
        //echo $strSQL;
        $result = $db->execute($strSQL);
        while ($row = $db->fetchrow($result)) {
            $strSQL2 .= " SELECT * FROM hrd_employee ";
            //$strSQL2 .= " WHERE functional_code = '".$valueMember."' ORDER BY employee_name ;";
            $strSQL2 .= " WHERE functional_code = '" . $row["functional_code"] . "' AND active = 1 ORDER BY employee_name ;";
            $result2 = $db->execute($strSQL2);
            $numEmp = $db->numrows($result2);
            //$selisihLevel=($row["level"]-$firstLevel)-$n;
            $selisihLevel = ($row["level"] - $firstLevel) - $orgLvl;
            $strLevel = "";
            if ($selisihLevel > 0) {
                if ($numEmp > 1) {
                    $strLevel = " class=\"lvl" . $selisihLevel . " moreone\"";
                } else {
                    $strLevel = " class=\"lvl" . $selisihLevel . " one\"";
                }
                $orgLvlNext = $orgLvl + $selisihLevel;
            } else {
                if ($numEmp > 1) {
                    $strLevel = " class=\"moreone\"";
                } else {
                    $strLevel = " class=\"one\"";
                }
                $orgLvlNext = $orgLvl;
            }
            //echo "<li><a href=data_functional_chart.php?lvl=".$n."&func=".$valueMember.">".getFunctionalName($valueMember)."(".$n.")</a>";
            //echo "<li ".$strLevel."><a href=\"?lvl=".$n."&func=".$row["functional_code"]."\">".$row["functional_name"]."(".$n.")(".$row["level"]."/".$selisihLevel.")</a>";
            //echo "<li ".$strLevel."><a href=\"?lvl=".$n."&func=".$row["functional_code"]."\">".$row["functional_name"].":".$selisihLevel.":".$orgLvl."(".$row["position_code"].")</a>";
            echo "<li " . $strLevel . "><a href=\"?lvl=" . $n . "&func=" . $row["functional_code"] . "\">" . $row["functional_name"] . "</a>";
            echo "<dl>";
            $i = 0;
            while ($row2 = $db->fetchrow($result2)) {
                echo "<dt>" . ucwords(strtolower($row2['employee_name'])) . "</dt><br>";
                $i++;
            }
            if ($i == 0) {
                echo "(<i>Vacant</i>)";
            }
            echo "</dl>";
            $strFunc = $row["functional_code"];
            funcChart($strFunc, $n + 1, $orgLvlNext + 1);
            echo "</li>";
        }
        echo "</ul>";
    }
    //else {
    //    echo "</li>";
    //}
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
    //echo $strCount;
    //echo $intCount;
    if ($intCount > 0) {
        return true;
    } else {
        return false;
    }
}

function getMember($strFunc, $n = "")
{
    global $db;
    global $pLevel;
    global $pFunc;
    $strSQL = " SELECT t1.*,t2.level FROM hrd_functional as t1";
    $strSQL .= " LEFT JOIN hrd_position as t2 on t1.position_code=t2.position_code";
    $strSQL .= " WHERE t1.head_code = '" . $strFunc . "' ";
    if ($n == $pLevel and $pLevel == 1 and $pFunc != "") {
        $strSQL .= " AND t1.functional_code = '" . $pFunc . "' ";
    }
    $result = $db->execute($strSQL);
    while ($row = $db->fetchrow($result)) {
        $arrMember[] = $row['functional_code'];
    }
    return $arrMember;
}

function getFunctionalName($strFunc)
{
    global $db;
    $strSQL .= " SELECT functional_name FROM hrd_functional ";
    $strSQL .= " WHERE functional_code = '" . $strFunc . "' ";
    $result = $db->execute($strSQL);
    while ($row = $db->fetchrow($result)) {
        $arrName = $row['functional_name'];
    }
    return $arrName;
}

?>

</body>
</html>
