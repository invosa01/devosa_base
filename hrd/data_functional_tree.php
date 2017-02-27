<?php
include_once('../global/session.php');
include_once('global.php');
include_once("../includes/treeview/treeview.php");
include_once("../includes/datagrid2/datagrid.php");
$dataPrivilege = getDataPrivileges(
    "data_functional_tree.php",
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
$strWordsFunctionalData = getWords("organizational structure");
$strWordsFunctionaltList = getWords("Functional List");
$strWordsOrganizationTree = getWords("organization tree");
$strWordsInputData = getWords("input data");
$strWordsFunctionaltList = getWords("chart / tree");
$strManagementCode = "";
$strDivisionCode = "";
$strFunctionalCode = "";
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
    $strResult = "";
    $strResult .= "<ul>\n";
    $strSQL = "SELECT * FROM hrd_functional";
    $companyID = $_SESSION['sessionCompanyID'];
    if ($companyID == 3) {
        $strSQL .= "WHERE management_code NOT LIKE '" . getCompanyCode(
            ) . "%' $strKriteria ORDER BY $strOrder management_code ";
    } else {
        $strSQL .= $strKriteria . "ORDER BY $strOrder management_code ";
    }
    $resMan = $db->execute($strSQL);
    while ($rowMan = $db->fetchrow($resMan)) {
        $intRows++;
        $strResult .= "  <li>[" . $rowMan['management_code'] . "]&nbsp; " . $rowMan['management_name'];
        $strResult .= "<ul>\n";
        $intDiv = 0;
        $strSQL = "SELECT * FROM hrd_division ";
        $strSQL .= "WHERE management_code = '" . $rowMan['management_code'] . "' ORDER BY $strOrder division_code ";
        $resDiv = $db->execute($strSQL);
        while ($rowDiv = $db->fetchrow($resDiv)) {
            $intDiv++;
            $strResult .= "  <li>[" . $rowDiv['division_code'] . "]&nbsp; " . $rowDiv['division_name'];
            $strResult .= "<ul>\n";
            $intDept = 0;
            $strSQL = "SELECT * FROM hrd_department ";
            $strSQL .= "WHERE division_code = '" . $rowDiv['division_code'] . "' ";
            $strSQL .= "ORDER BY $strOrder department_code ";
            $resDb = $db->execute($strSQL);
            while ($rowDb = $db->fetchrow($resDb)) {
                $strResult .= "  <li>[" . $rowDb['department_code'] . "]&nbsp; " . $rowDb['department_name'];
                // cari data section
                $strResult .= "<ul>\n";
                $strSQL = "SELECT * FROM hrd_section WHERE department_code = '" . $rowDb['department_code'] . "' ORDER BY section_code ";
                $resSec = $db->execute($strSQL);
                while ($rowSec = $db->fetchrow($resSec)) {
                    $strResult .= "  <li>[" . $rowSec['section_code'] . "]&nbsp; " . $rowSec['section_name'];
                    $strResult .= "<ul>\n";
                    $strSQL = "SELECT * FROM hrd_sub_section WHERE department_code = '" . $rowDb['department_code'] . "' ";
                    $strSQL .= "AND section_code = '" . $rowSec['section_code'] . "' ORDER BY sub_section_code ";
                    $resSub = $db->execute($strSQL);
                    while ($rowSub = $db->fetchrow($resSub)) {
                        $strResult .= "  <li>[" . $rowSub['sub_section_code'] . "]&nbsp; " . $rowSub['sub_section_name'] . "</li>\n";
                    } // end cari subsection
                    $strResult .= "</ul>\n";
                    $strResult .= "</li>\n";
                } // end cari data section
                $strResult .= "</ul>\n";
                $strResult .= "</li>\n";
            }
            $strResult .= "</ul>\n";
            $strResult .= "</li>\n";
        }
        $strResult .= "</ul>\n";
        $strResult .= "</li>\n";
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    return $strResult;
} // showData
function getDataTree($db)
{
    global $myTreeview;
    $tblMan = new cModel("hrd_management", "management");
    $tblDiv = new cModel("hrd_division", "division");
    $tblDept = new cModel("hrd_department", "department");
    $tblSect = new cModel("hrd_section", "section");
    $tblSubSect = new cModel("hrd_sub_section", "sub section");
    $arrMan = $tblMan->findAll(
        "management_code LIKE '" . printCompanyCode($_SESSION['sessionIdCompany']) . "%'",
        null,
        "management_code"
    );
    //add 28-11-2012 by adnan
    $companyID = $_SESSION['sessionCompanyID'];
    if ($companyID == 3) {
        $arrMan = $tblMan->findAll("management_code NOT LIKE '" . getCompanyCode() . "%'", null, "management_code");
    }
    foreach ($arrMan as $rowMan) {
        //treeview root
        $management[$rowMan['management_code']] = $myTreeview->addNode(
            new TreeNode(
                $rowMan['management_code'] . '-' . $rowMan['management_name'],
                printLink(
                    $rowMan['management_code'] . '-' . $rowMan['management_name'],
                    $rowMan['management_code']
                ),
                true
            )
        );
        //find division
        $arrDiv = $tblDiv->findAllByManagementCode($rowMan['management_code'], null, "division_code");
        foreach ($arrDiv as $rowDiv) {
            //treeview root
            $division[$rowDiv['division_code']] = $myTreeview->addNode(
                new TreeNode(
                    $rowDiv['division_code'] . '-' . $rowDiv['division_name'],
                    printLink(
                        $rowDiv['division_code'] . '-' . $rowDiv['division_name'],
                        $rowDiv['management_code'],
                        $rowDiv['division_code']
                    ),
                    true
                ),
                $management[$rowDiv['management_code']]
            );
            //find department
            $arrDept = $tblDept->findAllByDivisionCode($rowDiv['division_code'], null, "department_code");
            foreach ($arrDept as $rowDb) {
                $dept[$rowDb['department_code']] = $myTreeview->addNode(
                    new TreeNode(
                        $rowDb['department_code'] . '-' . $rowDb['department_name'],
                        printLink(
                            $rowDb['department_code'] . '-' . $rowDb['department_name'],
                            $rowDb['management_code'],
                            $rowDb['division_code'],
                            $rowDb['department_code']
                        ),
                        true
                    ),
                    $division[$rowDb['division_code']]
                );
                // cari data section
                $arrSect = $tblSect->findAllByDepartmentCode($rowDb['department_code'], null, "section_code");
                foreach ($arrSect as $rowSec) {
                    $section[$rowSec['section_code']] = $myTreeview->addNode(
                        new TreeNode(
                            $rowSec['section_code'] . '-' . $rowSec['section_name'],
                            printLink(
                                $rowSec['section_code'] . '-' . $rowSec['section_name'],
                                $rowSec['management_code'],
                                $rowSec['division_code'],
                                $rowSec['department_code'],
                                $rowSec['section_code']
                            ),
                            true
                        ),
                        $dept[$rowSec['department_code']]
                    );
                    $arrSubSect = $tblSubSect->findAllByDepartmentCodeAndSectionCode(
                        $rowDb['department_code'],
                        $rowSec['section_code'],
                        null,
                        "sub_section_code"
                    );
                    foreach ($arrSubSect as $rowSub) {
                        $myTreeview->addNode(
                            new TreeNode(
                                $rowSub['sub_section_code'] . '-' . $rowSub['sub_section_name'],
                                printLink(
                                    $rowSub['sub_section_code'] . '-' . $rowSub['sub_section_name'],
                                    $rowSub['management_code'],
                                    $rowSub['division_code'],
                                    $rowSub['department_code'],
                                    $rowSub['section_code'],
                                    $rowSub['sub_section_code']
                                ),
                                true
                            ),
                            $section[$rowSub['section_code']]
                        );
                    }
                } // end cari data section
            }
        }
    }
    //writeLog(ACTIVITY_VIEW, MODULE_PAYROLL,"",0);
    return $myTreeview->render();
} // showData
function getDataTree2($db)
{
    global $myTreeview;
    $tblCompany = new cModel("hrd_company", "company");
    $tblFunctional = new cModel("hrd_functional", "functional");
    $arrCompany = $tblCompany->findAll("" . printCompanyCode($_SESSION['sessionIdCompany']) . "%'", null, "management_code");
    //add 28-11-2012 by adnan
    $companyID = $_SESSION['sessionCompanyID'];
    if ($companyID == 3) {
        $arrMan = $tblMan->findAll("management_code NOT LIKE '" . getCompanyCode() . "%'", null, "management_code");
    }
    foreach ($arrMan as $rowMan) {
        //treeview root
        $management[$rowMan['management_code']] = $myTreeview->addNode(
            new TreeNode(
                $rowMan['management_code'] . '-' . $rowMan['management_name'],
                printLink(
                    $rowMan['management_code'] . '-' . $rowMan['management_name'],
                    $rowMan['management_code']
                ),
                true
            )
        );
        //find division
        $arrDiv = $tblDiv->findAllByManagementCode($rowMan['management_code'], null, "division_code");
        foreach ($arrDiv as $rowDiv) {
            //treeview root
            $division[$rowDiv['division_code']] = $myTreeview->addNode(
                new TreeNode(
                    $rowDiv['division_code'] . '-' . $rowDiv['division_name'],
                    printLink(
                        $rowDiv['division_code'] . '-' . $rowDiv['division_name'],
                        $rowDiv['management_code'],
                        $rowDiv['division_code']
                    ),
                    true
                ),
                $management[$rowDiv['management_code']]
            );
            //find department
            $arrDept = $tblDept->findAllByDivisionCode($rowDiv['division_code'], null, "department_code");
            foreach ($arrDept as $rowDb) {
                $dept[$rowDb['department_code']] = $myTreeview->addNode(
                    new TreeNode(
                        $rowDb['department_code'] . '-' . $rowDb['department_name'],
                        printLink(
                            $rowDb['department_code'] . '-' . $rowDb['department_name'],
                            $rowDb['management_code'],
                            $rowDb['division_code'],
                            $rowDb['department_code']
                        ),
                        true
                    ),
                    $division[$rowDb['division_code']]
                );
                // cari data section
                $arrSect = $tblSect->findAllByDepartmentCode($rowDb['department_code'], null, "section_code");
                foreach ($arrSect as $rowSec) {
                    $section[$rowSec['section_code']] = $myTreeview->addNode(
                        new TreeNode(
                            $rowSec['section_code'] . '-' . $rowSec['section_name'],
                            printLink(
                                $rowSec['section_code'] . '-' . $rowSec['section_name'],
                                $rowSec['management_code'],
                                $rowSec['division_code'],
                                $rowSec['department_code'],
                                $rowSec['section_code']
                            ),
                            true
                        ),
                        $dept[$rowSec['department_code']]
                    );
                    $arrSubSect = $tblSubSect->findAllByDepartmentCodeAndSectionCode(
                        $rowDb['department_code'],
                        $rowSec['section_code'],
                        null,
                        "sub_section_code"
                    );
                    foreach ($arrSubSect as $rowSub) {
                        $myTreeview->addNode(
                            new TreeNode(
                                $rowSub['sub_section_code'] . '-' . $rowSub['sub_section_name'],
                                printLink(
                                    $rowSub['sub_section_code'] . '-' . $rowSub['sub_section_name'],
                                    $rowSub['management_code'],
                                    $rowSub['division_code'],
                                    $rowSub['department_code'],
                                    $rowSub['section_code'],
                                    $rowSub['sub_section_code']
                                ),
                                true
                            ),
                            $section[$rowSub['section_code']]
                        );
                    }
                } // end cari data section
            }
        }
    }
    //writeLog(ACTIVITY_VIEW, MODULE_PAYROLL,"",0);
    return $myTreeview->render();
} // showData
function getDataGrid($myDataGrid, $db)
{
    global $strManagementCode;
    global $strDivisionCode;
    global $strFunctionalCode;
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
    $strFunctionalCode = getRequestValue("department_code");
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
    if ($strFunctionalCode != "") {
        $strCaption .= ", DEPT: " . $strFunctionalCode;
        $arrCriteria[] = "department_code = '{$strFunctionalCode}'";
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

function printLink($name, $man = "", $div = "", $dept = "", $sect = "", $subsect = "")
{
    return "javascript:showDetail('$man','$div','$dept','$sect','$subsect')";
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
$strPageDesc = getWords('functional tree');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = organizationFunctionalChartSubmenu($strWordsFunctionaltList);
//------------------------------------------------
//Load Master Template
if ($bolPrint) {
    $strMainTemplate = getTemplate("data_functional_tree_print.html");
    $tbsPage->LoadTemplate($strMainTemplate);
    $tbsPage->Show();
} else {
    $myTreeview = new clsTreeView("treeView1", "../includes/treeview/", true);
    $dataTreeView = getDataTree2($db);
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