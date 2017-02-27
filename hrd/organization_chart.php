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
        function onNodeClicked($node) {
            var value = $node.clone().children("ul,li,img").remove().end().text();
            if ($node.data("actor")) {
                value += " (" + $node.data("actor") + ")";
            }
            $("<li>" + value + "</li>").prependTo($("#clicks"));
        }
    </script>
    <style>
        div.orgChart div.node {
            cursor: pointer;
        }
    </style>
</head>
<body>


<?php
include_once('../global/session.php');
include_once('global.php');
session_start();
$db = new CdbClass;
$db->connect();
$strKriteria = "";
$strOrder = "";
$strResult = "";
$intRows = 0;
$intDiv = 0;
$intDept = 0;
$intSect = 0;
$intSubSect = 0;
// MANAGEMENT
$strSQL = "SELECT * FROM hrd_management ";
$strSQL .= "WHERE 1=1 $strKriteria ORDER BY $strOrder management_code ";
$resMan = $db->execute($strSQL);
$strResult .= "<div id='left'>";
$strResult .= "<ul id='organisation'>";
// $strResult .= "<li>".WAL."<ul>";//nanti ganti wal
while ($rowMan = $db->fetchrow($resMan)) {
    $intRows++;
    $strResult .= "  <li><input type=hidden name=detailManagementCode$intRows value=\"" . $rowMan['management_code'] . "\" disabled>" . "<a href=organization_chart.php>" . $rowMan['management_name'] . "&nbsp;</a>\n";
    //MANAGEMENT HEAD
    $strSQL = "SELECT * FROM hrd_employee ";
    $strSQL .= "WHERE division_code = '' AND department_code = '' AND section_code = '' AND sub_section_code = '' AND management_code = '" . $rowMan['management_code'] . "'";
    $strSQL .= "ORDER BY employee_name";
    // die($strSQL);
    $resHead = $db->execute($strSQL);
    while ($manHead = $db->fetchrow($resHead)) {
        $strResult .= "<dl><dt>" . ucwords(strtolower($manHead['employee_name'])) . "</dt></dl>";
    }
    $strResult .= "<ul>";
    //DIVISION
    $strSQL = "SELECT * FROM hrd_division ";
    $strSQL .= "WHERE management_code = '" . $rowMan['management_code'] . "' ";
    if (isset($_REQUEST['div'])) {
        $strSQL .= "AND division_code = '" . $_REQUEST['div'] . "' ";
    }
    $strSQL .= "ORDER BY $strOrder management_code";
    $resDiv = $db->execute($strSQL);
    while ($rowDiv = $db->fetchrow($resDiv)) {
        $intDiv++;
        if ($intDiv > 1) { //baris baru
            $intRows++;
        }
        $strResult .= "<li><input type=hidden name=detailDivisionCode$intRows value=\"" . $rowDiv['division_code'] . "\" disabled>" . "<a href=organization_chart.php?div=" . $rowDiv['division_code'] . ">" . $rowDiv['division_name'] . "</a><br>";
        //            $strResult .= "<a href=organization_chart.php?div=".$rowDiv['division_code'].">".getWords('Click')."</a>";
        //DIVISION HEAD
        $strSQL = "SELECT * FROM hrd_employee ";
        $strSQL .= "WHERE department_code = '' AND section_code = '' AND sub_section_code = '' AND division_code = '" . $rowDiv['division_code'] . "'";
        $strSQL .= "ORDER BY $strOrder employee_name";
        $resHead = $db->execute($strSQL);
        while ($divHead = $db->fetchrow($resHead)) {
            $strResult .= "<dl><dt>" . ucwords(strtolower($divHead['employee_name'])) . "</dt></dl>";
        }
        $strResult .= "<ul>";
        //DEPARTMENT
        $strSQL = "SELECT * FROM hrd_department ";
        $strSQL .= "WHERE division_code = '" . $rowDiv['division_code'] . "' AND management_code = '" . $rowMan['management_code'] . "' ";
        $strSQL .= "ORDER BY $strOrder management_code ";
        $resDept = $db->execute($strSQL);
        while ($rowDept = $db->fetchrow($resDept)) {
            //$arrDept[$intDept] = $rowDept['department_code'];
            $intDept++;
            if ($intDept > 1) { //baris baru
                $intRows++;
            }
            $strResult .= "  <li><input type=hidden name=detailCode$intRows value=\"" . $rowDept['department_code'] . "\" disabled>" . $rowDept['department_name'] . "<br>";
            //DEPARTMENT HEAD
            $strSQL = "SELECT * FROM hrd_employee ";
            $strSQL .= "WHERE section_code = '' AND sub_section_code = '' AND  department_code = '" . $rowDept['department_code'] . "'";//tambah management_code di filter
            $strSQL .= "ORDER BY $strOrder employee_name";
            $resHead = $db->execute($strSQL);
            while ($deptHead = $db->fetchrow($resHead)) {
                $strResult .= "<dl><dt>" . ucwords($deptHead['employee_name']) . "</dt></dl>";
            }
            $strResult .= "<ul>";
            //SECTION
            $strSQL = "SELECT * FROM hrd_section WHERE department_code = '" . $rowDept['department_code'] . "' AND division_code = '" . $rowDiv['division_code'] . "' AND management_code = '" . $rowMan['management_code'] . "' ORDER BY $strOrder management_code ";
            $resSect = $db->execute($strSQL);
            while ($rowSect = $db->fetchrow($resSect)) {
                //$arrSect[$intSect] = $rowSect['section_code'];
                $intSect++;
                if ($intSect > 1) { //baris baru
                    $intRows++;
                }
                $strResult .= "  <li><input type=hidden name=detailSectionCode$intRows value=\"" . $rowSect['section_code'] . "\" disabled>" . $rowSect['section_name'] . "";
                //SECTION HEAD
                $strSQL = "SELECT * FROM hrd_employee ";
                $strSQL .= "WHERE sub_section_code = '' AND  section_code = '" . $rowSect['section_code'] . "'";//tambah management_code dan division_code di filter
                $strSQL .= "ORDER BY $strOrder employee_name";
                $resHead = $db->execute($strSQL);
                while ($secHead = $db->fetchrow($resHead)) {
                    $strResult .= "<dl><dt>" . $secHead['employee_name'] . "</dt></dl>";
                }
                $strResult .= "<ul>";
                //SUBSECTION
                $strSQL = "SELECT * FROM hrd_sub_section WHERE department_code = '" . $rowSect['department_code'] . "' AND division_code = '" . $rowSect['division_code'] . "' AND management_code = '" . $rowMan['management_code'] . "' ";
                $strSQL .= "AND section_code = '" . $rowSect['section_code'] . "' ORDER BY sub_section_code ";
                $resSub = $db->execute($strSQL);
                while ($rowSub = $db->fetchrow($resSub)) {
                    //$arrSubSect[$intSubSect] = $rowSub['sub_section_code'];
                    $intSubSect++;
                    if ($intSubSection > 1) { //baris baru
                        $intRows++;
                    }
                    $strResult .= "  <li><input type=hidden name=detailSubSectionCode$intRows value=\"" . $rowSub['sub_section_code'] . "\" disabled>" . $rowSub['sub_section_name'] . "&nbsp;</li>\n";
                    /*//SUBSECTION MEMBER
                    $strSQL = "SELECT * FROM hrd_employee WHERE management_code != '' AND division_code != '' AND department_code != '' AND section_code != '' AND sub_section_code != ''";

                    $resHead = $db->execute($strSQL);
                    while ($subsecHead = $db->fetchrow($resHead)) {
                        $strResult .= "<dl><dt>".$subsecHead['employee_name']."</dt></dl>";
                    }*/
                }
                $strResult .= "</ul></li>";
            }
            $strResult .= "</ul></li>";
        }
        $strResult .= "</ul></li>";
    }
    $strResult .= "</ul></li>";
}
$strResult .= "</ul></li></ul></div>";
$strResult .= "<div id ='content'>";
$strResult .= "<div id='main'></div>
                    <div class='text'>
                    <ul id='clicks'>
                    </ul>
                    </div>
                    </div>";
echo $strResult;
?>

</body>
</html>
