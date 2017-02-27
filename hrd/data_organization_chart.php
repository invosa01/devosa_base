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
//$strResult .= "<li>".WAL."<ul>";//nanti ganti wal
while ($rowMan = $db->fetchrow($resMan)) {
    $intRows++;
    $strResult .= "  <li><input type=hidden name=detailManagementCode$intRows value=\"" . $rowMan['management_code'] . "\" disabled>" . "<a href=data_organization_chart.php>" . $rowMan['management_name'] . "&nbsp;</a>\n";
    //MANAGEMENT HEAD
    $strSQL = "SELECT * FROM hrd_employee as t0 LEFT JOIN hrd_position as t1 ON t0.position_code = t1.position_code ";
    $strSQL .= "WHERE active = '1' AND position_group=0 AND division_code = '' AND department_code = '' AND section_code = '' AND sub_section_code = '' AND management_code = '" . $rowMan['management_code'] . "'";
    $strSQL .= "ORDER BY $strOrder employee_name";
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
        $strResult .= "<li><input type=hidden name=detailDivisionCode$intRows value=\"" . $rowDiv['division_code'] . "\" disabled>" . "<a href=data_organization_chart.php?div=" . $rowDiv['division_code'] . ">" . $rowDiv['division_name'] . "</a><br>";
        //            $strResult .= "<a href=organization_chart.php?div=".$rowDiv['division_code'].">".getWords('Click')."</a>";
        //DIVISION HEAD
        $strSQL = "SELECT * FROM hrd_employee ";
        $strSQL .= "WHERE active = 1 AND department_code = '' AND section_code = '' AND sub_section_code = '' AND division_code = '" . $rowDiv['division_code'] . "'";
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
            $intDept++;
            if ($intDept > 1) { //baris baru
                $intRows++;
            }
            $strResult .= "  <li><input type=hidden name=detailCode$intRows value=\"" . $rowDept['department_code'] . "\" disabled>" . $rowDept['department_name'] . "<br>";
            //DEPARTMENT HEAD
            $strSQL = "SELECT * FROM hrd_employee ";
            $strSQL .= "WHERE  active = 1 AND section_code = '' AND sub_section_code = '' AND  department_code = '" . $rowDept['department_code'] . "'";//tambah management_code di filter
            $strSQL .= "ORDER BY $strOrder employee_name";
            $resHead = $db->execute($strSQL);
            while ($deptHead = $db->fetchrow($resHead)) {
                $strResult .= "<dl><dt>" . ucwords(strtolower($deptHead['employee_name'])) . "</dt></dl>";
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
                $strSQL .= "WHERE  active = 1 AND sub_section_code = '' AND  section_code = '" . $rowSect['section_code'] . "'";//tambah management_code dan division_code di filter
                $strSQL .= "ORDER BY $strOrder employee_name";
                $resHead = $db->execute($strSQL);
                while ($secHead = $db->fetchrow($resHead)) {
                    $strResult .= "<dl><dt>" . ucwords(strtolower($secHead['employee_name'])) . "</dt></dl>";
                }
                $strResult .= "<ul>";
                //SUBSECTION
                $strSQL = "SELECT * FROM hrd_sub_section WHERE department_code = '" . $rowSect['department_code'] . "' AND division_code = '" . $rowDiv['division_code'] . "' AND management_code = '" . $rowMan['management_code'] . "' ";
                $strSQL .= "AND section_code = '" . $rowSect['section_code'] . "' ORDER BY $strOrder sub_section_code ";
                $resSub = $db->execute($strSQL);
                while ($rowSub = $db->fetchrow($resSub)) {
                    $intSubSect++;
                    if ($intSubSection > 1) { //baris baru
                        $intRows++;
                    }
                    $strResult .= "  <li><input type=hidden name=detailSubSectionCode$intRows value=\"" . $rowSub['sub_section_code'] . "\" disabled>" . $rowSub['sub_section_name'] . "&nbsp;\n";
                    //SUBSECTION MEMBER
                    $strSQL = "SELECT * FROM hrd_employee WHERE active = 1 AND management_code = '" . $rowMan['management_code'] . "' AND division_code = '" . $rowDiv['division_code'] . "' AND department_code = '" . $rowDept['department_code'] . "' AND section_code = '" . $rowSect['section_code'] . "' AND sub_section_code = '" . $rowSub['sub_section_code'] . "'";
                    //$strSQL = "SELECT * FROM hrd_employee WHERE section_code = 'AD1' ";
                    $resHead = $db->execute($strSQL);
                    while ($subsecHead = $db->fetchrow($resHead)) {
                        //die($subsecHead['employee_name']);
                        $strResult .= "<dl><dt>" . ucwords(strtolower($subsecHead['employee_name'])) . "</dt></dl>";
                    }
                    $strResult .= "</li>";
                }
                $strResult .= "</ul></li>";
            }
            $strResult .= "</ul></li>";
        }
        $strResult .= "</ul></li>";
    }
    $strResult .= "</ul></li>";
}
$strResult .= "</ul></div>";
$strResult .= "<div id ='content'>";
$strResult .= "<div id='main'></div>
                    </div>";
echo $strResult;
?>

</body>
</html>
