<?php
include_once('../global/session.php');
include_once('global.php');
include_once("../includes/datagrid2/datagrid.php");
include_once("../includes/krumo/class.krumo.php");
$dataPrivilege = getDataPrivileges(
    "data_department_tree.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView) {
    die(accessDenied($_SERVER['HTTP_REFERER']));
}
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    getDataAJAX();
}
$bolPrint = (isset($_REQUEST['btnPrint']));
//---- INISIALISASI ----------------------------------------------------
// add new by ismail
$strMessages = "";
$strClass = "style=\"display:none;width:$strDateWidth%\" class=\"bgOK\" ";
$strDataDetail = "";
$intTotalData = 0;
$strWordsPrint = getWords("print");
$strWordsDepartmentData = getWords("organizational structure");
$strWordsDepartmentList = getWords("Department List");
$strWordsOrganizationTree = getWords("organization tree");
$strWordsInputData = getWords("input data");
$strWordsDepartmentList = getWords("chart / tree");
$strManagementCode = "";
$strDivisionCode = "";
$strDepartmentCode = "";
$strSubDepartmentCode = "";
$strSectionCode = "";
$strSubSectionCode = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $strOrder = "")
{
    global $words;
    $intRows = 0;
    $strResult = OrganizationalTreeButton();
    $strResult .= "<div class=\"dd nestable\" id=\"organizational-tree\">";
    $strResult .= "<ol class=\"dd-list\">\n";
    if (isset($_SESSION['sessionCompanyID'])) {
        $companyID = $_SESSION['sessionCompanyID'];
    }
    $strSQL = "SELECT company_code FROM hrd_company ";
    if (isset($companyID) && $companyID != -1) {
        $strSQL .= "WHERE id = $companyID ";
    }
    $resMan = $db->execute($strSQL);
    while ($rowMan = $db->fetchrow($resMan)) {
        $strMan = $rowMan['management_code'];
    }
    $strSQL = "SELECT * FROM hrd_management ";
    if (isset($companyID) && $companyID != -1) {
        $strSQL .= "WHERE management_code = '" . $strMan . "' $strKriteria ORDER BY $strOrder management_code ";
    } else {
        $strSQL .= $strKriteria . "ORDER BY $strOrder management_code ";
    }
    $resMan = $db->execute($strSQL);
    $strText = "";
    while ($rowMan = $db->fetchrow($resMan)) {
        $intRows++;
        $strText = $rowMan['management_code'] . "-" . $rowMan['management_name'];
        $link = printLink(
            $strText,
            $rowMan['management_code']
        );
        $strResult .= "<li class=\"dd-item\"><div class=\"dd-handle\"><a href=" . $link . ">" . $strText . "</a></div>";
        $strSQLCount = "SELECT COUNT(*) AS total_child FROM hrd_division ";
        $strSQLCount .= "WHERE management_code = '" . $rowMan['management_code'] . "'";
        $resCountDiv = $db->execute($strSQLCount);
        $rowCountDiv = $db->fetchrow($resCountDiv);
        if ((int)$rowCountDiv['total_child'] > 0) {
            $strResult .= "<ol class=\"dd-list\">\n";
            $intDiv = 0;
            $strSQL = "SELECT * FROM hrd_division ";
            $strSQL .= "WHERE management_code = '" . $rowMan['management_code'] . "' ORDER BY $strOrder division_code ";
            $resDiv = $db->execute($strSQL);
            while ($rowDiv = $db->fetchrow($resDiv)) {
                $intDiv++;
                $strText = $rowDiv['division_code'] . "-" . $rowDiv['division_name'];
                $link1 = printLink(
                    $strText,
                    $rowDiv['management_code'],
                    $rowDiv['division_code']
                );
                $strResult .= "<li class=\"dd-item\"><div class=\"dd-handle\"><a href=" . $link1 . ">" . $strText . "</a></div>";
                $strSQLCount = "SELECT COUNT(*) AS total_child FROM hrd_department ";
                $strSQLCount .= "WHERE division_code = '" . $rowDiv['division_code'] . "' ";
                $resCountDept = $db->execute($strSQLCount);
                $rowCountDept = $db->fetchrow($resCountDept);
                if ((int)$rowCountDept['total_child'] > 0) {
                    $strResult .= "<ol class=\"dd-list\">\n";
                    $intDept = 0;
                    $strSQL = "SELECT * FROM hrd_department ";
                    $strSQL .= "WHERE division_code = '" . $rowDiv['division_code'] . "' ";
                    $strSQL .= "ORDER BY $strOrder department_code ";
                    $resDb = $db->execute($strSQL);
                    while ($rowDb = $db->fetchrow($resDb)) {
                        $intDept++;
                        $strText = $rowDb['department_code'] . "-" . $rowDb['department_name'];
                        $link2 = printLink(
                            $strText,
                            $rowDb['management_code'],
                            $rowDb['division_code'],
                            $rowDb['department_code']
                        );
                        $strResult .= "  <li class=\"dd-item\"><div class=\"dd-handle\"><a href=" . $link2 . ">" . $strText . "</a></div>";
                        $strSQLCount = "SELECT COUNT(*) AS total_child FROM hrd_sub_department WHERE department_code = '" . $rowDb['department_code'] . "'";
                        $resCountSubDept = $db->execute($strSQLCount);
                        $rowCountSubDept = $db->fetchrow($resCountSubDept);
                        if ((int)$rowCountSubDept['total_child'] > 0) {
                            $strResult .= "<ol class=\"dd-list\">\n";
                            $intSubDept = 0;
                            $strSQL = "SELECT * FROM hrd_sub_department WHERE department_code = '" . $rowDb['department_code'] . "' ORDER BY sub_department_code ";
                            $resSubDept = $db->execute($strSQL);
                            while ($rowSubDept = $db->fetchrow($resSubDept)) {
                                $intSubDept++;
                                $strText = $rowSubDept['sub_department_code'] . "-" . $rowSubDept['sub_department_name'];
                                $link3 = printLink(
                                    $strText,
                                    $rowSubDept['management_code'],
                                    $rowSubDept['division_code'],
                                    $rowSubDept['department_code'],
                                    $rowSubDept['sub_department_code']
                                );
                                $strResult .= "  <li class=\"dd-item\"><div class=\"dd-handle\"><a href=" . $link3 . ">" . $strText . "</a></div>";
                                $strSQLCount = "SELECT COUNT(*) AS total_child FROM hrd_section WHERE department_code = '" . $rowDb['department_code'] . "' ";
                                $strSQLCount .= "AND sub_department_code ='" . $rowSubDept['sub_department_code'] . "'";
                                $resCountSection = $db->execute($strSQLCount);
                                $rowCountSection = $db->fetchrow($resCountSection);
                                if ((int)$rowCountSection['total_child'] > 0) {
                                    $strResult .= "<ol class=\"dd-list\">\n";
                                    $intSec = 0;
                                    $strSQL = "SELECT * FROM hrd_section WHERE department_code = '" . $rowDb['department_code'] . "' ";
                                    $strSQL .= "AND sub_department_code ='" . $rowSubDept['sub_department_code'] . "' ORDER BY section_code ";
                                    $resSec = $db->execute($strSQL);
                                    while ($rowSec = $db->fetchrow($resSec)) {
                                        $intSec++;
                                        $strText = $rowSec['section_code'] . "-" . $rowSec['section_name'];
                                        $link4 = printLink(
                                            $strText,
                                            $rowSec['management_code'],
                                            $rowSec['division_code'],
                                            $rowSec['department_code'],
                                            $rowSec['sub_department_code'],
                                            $rowSec['section_code']
                                        );
                                        $strResult .= "  <li class=\"dd-item\"><div class=\"dd-handle\"><a href=" . $link4 . ">" . $strText . "</a></div>";
                                        $strSQLCount = "SELECT COUNT(*) AS total_child FROM hrd_sub_section WHERE department_code = '" . $rowDb['department_code'] . "' ";
                                        $strSQLCount .= "AND sub_department_code = '" . $rowSubDept['sub_department_code'] . "' ";
                                        $strSQLCount .= "AND section_code = '" . $rowSec['section_code'] . "'";
                                        $resCountSubSection = $db->execute($strSQLCount);
                                        $rowCountSubSection = $db->fetchrow($resCountSubSection);
                                        if ((int)$rowCountSubSection['total_child'] > 0) {
                                            $strResult .= "<ol class=\"dd-list\">\n";
                                            $intSubSec = 0;
                                            $strSQL = "SELECT * FROM hrd_sub_section WHERE department_code = '" . $rowDb['department_code'] . "' ";
                                            $strSQL .= "AND sub_department_code = '" . $rowSubDept['sub_department_code'] . "' AND section_code = '" . $rowSec['section_code'] . "' ORDER BY sub_section_code ";
                                            $resSub = $db->execute($strSQL);
                                            while ($rowSub = $db->fetchrow($resSub)) {
                                                $intSubSec++;
                                                $strText = $rowSub['sub_section_code'] . "-" . $rowSub['sub_section_name'];
                                                $link5 = printLink(
                                                    $strText,
                                                    $rowSub['management_code'],
                                                    $rowSub['division_code'],
                                                    $rowSub['department_code'],
                                                    $rowSub['sub_department_code'],
                                                    $rowSub['section_code'],
                                                    $rowSub['sub_section_code']
                                                );
                                                $strResult .= "  <li class=\"dd-item\"><div class=\"dd-handle\"><a href=" . $link5 . ">" . $strText . "</a></div></li>";
                                            } // end cari subsection
                                            $strResult .= "</ol>\n";
                                        }
                                        $strResult .= "</li>\n";
                                    } // end cari data section
                                    $strResult .= "</ol>\n";
                                }
                                $strResult .= "</li>\n";
                            }
                            $strResult .= "</ol>\n";
                        }
                        $strResult .= "</li>\n";
                    }
                    $strResult .= "</ol>\n";
                }
                $strResult .= "</li>\n";
            }
            $strResult .= "</ol>\n";
        }
        $strResult .= "</li>\n";
    }
    $strResult .= "</ol>\n";
    $strResult .= "</div>\n";
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    return $strResult;
} // showData
function OrganizationalTreeButton()
{
    $organizationalTreeButton = "<menu id=\"nestable-menu\">";
    $organizationalTreeButton .= "<button id=\"expand-all\" type=\"button\" class=\"btn btn-primary btn-small\" data-action=\"expand-all\">Expand All</button>\n";
    $organizationalTreeButton .= "<button id=\"collapse-all\" type=\"button\" class=\"btn btn-danger\" data-action=\"collapse-all\">Collapse All</button>";
    $organizationalTreeButton .= "</menu>";
    return $organizationalTreeButton;
}
function getDataGrid($myDataGrid, $db)
{
    global $strManagementCode;
    global $strDivisionCode;
    global $strDepartmentCode;
    global $strSectionCode;
    global $strSubSectionCode;
    global $strDataCompany;
    global $strKriteriaCompany;
    $myDataGrid->disableFormTag();
    //$myDataGrid->addColumnCheckbox(new DataGrid_Column("chkID", "id", array('width' => '30'), array('align'=>'center', 'nowrap' => '')));
    $myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("No"), "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("employee ID"), "employee_id", ['width' => 100], ['align' => 'center'])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("name"), "employee_name", null, ['nowrap' => '']));
    // $myDataGrid->addColumn(new DataGrid_Column("Band", "grade_code", array('width' => 30), array('align' => 'center')));
    //$myDataGrid->addColumn(new DataGrid_Column("Position", "position_name", array('width' => 150), array('nowrap' => '')));
    //$myDataGrid->addSpecialButton("btnDelete","btnDelete","submit","Delete","onClick=\"javascript:return confirm('Delete this selected data?');\"","deleteData()");
    //$myDataGrid->addButton("btnAdd","btnAdd","submit","Add","","addData()");
    $myDataGrid->getRequest();
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $arrCriteria = [];
    $strManagementCode = getRequestValue("management_code");
    $strDivisionCode = getRequestValue("division_code");
    $strDepartmentCode = getRequestValue("department_code");
    $strSubDepartmentCode = getRequestValue("sub_department_code");
    $strSectionCode = getRequestValue("section_code");
    $strSubSectionCode = getRequestValue("sub_section_code");
    $strCaption = strtoupper(getWords("employee data")) . "<br>";
    if ($strManagementCode != "") {
        $strCaption .= " MAN: " . $strManagementCode;
        $arrCriteria[] = "management_code = '{$strManagementCode}'";
    }
    if ($strDivisionCode != "") {
        $strCaption .= " DIV: " . $strDivisionCode;
        $arrCriteria[] = "division_code = '{$strDivisionCode}'";
    }
    if ($strDepartmentCode != "") {
        $strCaption .= ", DEPT: " . $strDepartmentCode;
        $arrCriteria[] = "department_code = '{$strDepartmentCode}'";
    }
    if ($strSubDepartmentCode != "") {
        $strCaption .= ", SUBDEPT: " . $strSubDepartmentCode;
        $arrCriteria[] = "sub_department_code = '{$strSubDepartmentCode}'";
    }
    if ($strSectionCode != "") {
        $strCaption .= ", SECT: " . $strSectionCode;
        $arrCriteria[] = "section_code = '{$strSectionCode}'";
    }
    if ($strSubSectionCode != "") {
        $strCaption .= ", SUBSECT: " . $strSubSectionCode;
        $arrCriteria[] = "sub_section_code = '{$strSubSectionCode}'";
    }
    if ($strSubSectionCode != "") {
        $strCaption .= ", SUBSECT: " . $strSubSectionCode;
        $arrCriteria[] = "sub_section_code = '{$strSubSectionCode}'";
    }
    $strCriteria = implode(" AND ", $arrCriteria);
    if ($strCriteria == "") {
        $strCriteria = " 1 = 1";
    }
    $strSQLCOUNT = "
      SELECT COUNT(*) AS total
      FROM
      (
        SELECT e.*, p.position_code||' - '||p.position_name AS position_name
          FROM hrd_employee AS e LEFT JOIN hrd_position AS p
          ON e.position_code = p.position_code WHERE e.active = 1

      ) AS x
      WHERE " . $strCriteria . $strKriteriaCompany;
    $strSQL = "
      SELECT *
      FROM
      (
        SELECT e.*, p.position_code||' - '||p.position_name AS position_name
          FROM hrd_employee AS e LEFT JOIN hrd_position AS p
          ON e.position_code = p.position_code WHERE e.active = 1
      ) AS x
      WHERE  " . $strCriteria . $strKriteriaCompany;
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
    $dataset = $myDataGrid->getData($db, $strSQL);
    //bind Datagrid with array dataset
    $myDataGrid->bind($dataset);
    //you can put into TBS template by putting into variable
    //e.g. : $Datagrid = $myDataGrid->render();
    //and then put somewhere in you template HTML string :  [var.Datagrid;protect=no;htmlconv=no]
    return
        "<div style=\"font-size: 12px; font-weight: bold\">" . $strCaption . "</div>" .
        $myDataGrid->render();
}

function printLink($name, $man = "", $div = "", $dept = "", $subdept = "", $sect = "", $subsect = "")
{
    return "javascript:showDetail('$man','$div','$dept','$subdept','$sect','$subsect')";
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    if ($bolCanView) {
        $strDataDetail = getData($db, $intTotalData);
    } else {
        showError("view_denied");
    }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('departement management tree');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = organizationChartSubmenu($strWordsDepartmentList);
//------------------------------------------------
//Load Master Template
if ($bolPrint) {
    $strMainTemplate = getTemplate("data_department_tree_print.html");
    $tbsPage->LoadTemplate($strMainTemplate);
    $tbsPage->Show();
} else {
    //$myTreeview = new clsTreeView("treeView1", "../includes/treeview/", true);
    //$dataTreeView = getDataTree($db);
    $dataTreeView = getData($db);
    $myDataGrid = new cDataGrid("formData", "DataGrid1");
    $dataGrid = getDataGrid($myDataGrid, $db);
    if ($strMessages != "") {
        $strClass = "style=\"width:$strDateWidth%\" class=\"bgOK\" ";
    }
    $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
    $tbsPage->LoadTemplate($strMainTemplate);
    $tbsPage->Show();
}
function getDataAJAX()
{
    $db = new cDbClass;
    $myDataGrid = new cDataGrid("formData", "DataGrid1");
    echo getDataGrid($myDataGrid, $db);
    exit();
}

?>