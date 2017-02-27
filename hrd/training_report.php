<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('list_employee.php');
include_once('list_training.php');
//include_once(getTemplate("words.inc"));
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
    die(getWords('view denied'));
}
$strWordsTrainingList = getWords("training list");
$strWordsEmployeeTraining = getWords("employee training");
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
if ($bolPrint) {
    $value = $_POST['ReportView'];
    if ($value == "TrainingList") {
        $strMainTemplate = getTemplate("training_list_print.html");
    } elseif ($value == "EmployeeTraining") {
        $strMainTemplate = getTemplate("training_list_employee_print.html");
    } elseif ($value == "") {
        $strMainTemplate = getTemplate("trainingNoSelectPrint.html");
    }
} else {
    $strTemplateFile = getTemplate("training_report.html");
}
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi buat nentuin apakah standard format atau gak
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $bolIsEmployee = isUserEmployee();
    if ($bolCanDelete && isset($_POST['btnDelete'])) {
        if ($_SESSION['sessionUserRole'] == 3 || $_SESSION['sessionUserRole'] == 4) {
            deleteData($db);
        }
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    (isset($_REQUEST['filterEmployeeID'])) ? $strFilterEmployeeID = trim(
        $_REQUEST['filterEmployeeID']
    ) : $strFilterEmployeeID = "";
    (isset($_REQUEST['filterDepartment'])) ? $strFilterDepartment = $_REQUEST['filterDepartment'] : $strFilterDepartment = "";
    (isset($_REQUEST['filterSection'])) ? $strFilterSection = $_REQUEST['filterSection'] : $strFilterSection = "";
    (isset($_REQUEST['filterYear'])) ? $strFilterYear = $_REQUEST['filterYear'] : $strFilterYear = date("Y");
    (isset($_REQUEST['dataPage'])) ? $intCurrPage = $_REQUEST['dataPage'] : $intCurrPage = 1;
    (isset($_REQUEST['dataSort'])) ? $strSortBy = $_REQUEST['dataSort'] : $strSortBy = "";
    $strInputSortBy = $strSortBy;
    if (!is_numeric($intCurrPage)) {
        $intCurrPage = 1;
    }
    if ($strSortBy != "") {
        $strSortBy = "\"$strSortBy\", ";
    }
    //$strBtnPrint = "<input type=button name='btnPrint' value=\"" .$words['print']. "\" onClick=\"printData($intCurrPage);\">";
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    $strKriteria = "";
    if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
        if ($arrUserInfo['isDeptHead']) {
            $strFilterDepartment = $arrUserInfo['department_code'];
        } else if ($arrUserInfo['isGroupHead']) {
            $strFilterSection = $arrUserInfo['section_code'];
        } else {
            $strFilterEmployeeID = $arrUserInfo['employee_id'];
        }
    }
    if (isset($_REQUEST['btnSearch']) || isset($_REQUEST['btnPrint'])) {
        $strInfoKriteria = "";
        if ($strFilterDepartment != "") {
            $strKriteria .= "AND t2.department_code = '$strFilterDepartment' ";
        }
        $strInfoKriteria = "";
        $value = $_POST['ReportView'];
        if ($strFilterEmployeeID != "") {
            if ($value == "TrainingList") {
                $strKriteria .= "";
            } else {
                $strKriteria .= "AND upper(t2.employee_id) = '" . strtoupper($strFilterEmployeeID) . "' ";
            }
        }
        if ($strFilterSection != "") {
            if ($value == "TrainingList") {
                $strKriteria .= "";
            } else {
                $strKriteria .= "AND t2.section_code = '$strFilterSection' ";
            }
        }
    } else { // jngan tampilkan data, kecuali jika yang login adalah meployee itu sendiri
        /*
        if ($arrUserInfo['employee_id'] == "") {
            $strKriteria .= " AND 1 = 2 "; // pasti salah
            $strBtnPrint = ""; // tidak perlu tampil
          } else {
            $strKriteria .= "AND employee_id = '". $arrUserInfo['employee_id']. "' ";
          }
        */
        $strKriteria .= " AND 1 = 2 "; // pasti salah
    }
    if ($bolCanView) {
        if (isset($_REQUEST['btnSearch']) || isset($_REQUEST['btnPrint'])) {
            $value = $_POST['ReportView'];
            if ($value == "TrainingList") {
                $strDataDetail = getData($db, $intTotalData, $strFilterYear, $strKriteria, $strSortBy);
            } elseif ($value == "EmployeeTraining") {
                $strDataDetail = getData1(
                    $db,
                    $intTotalData,
                    $strFilterYear,
                    $strKriteria,
                    $intCurrPage,
                    $bolLimit,
                    $strSortBy
                );
            }
        }
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
    // generate data hidden input dan element form input
    $intDefaultWidth = 30;
    $intDefaultWidthPx = 200;
    $intDefaultHeight = 3;
    $strDisabled = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) ? "disabled" : "";
    $strInputFilterEmployeeID = "<input type=text id=filterEmployeeID name=filterEmployeeID size=$intDefaultWidth value=\"$strFilterEmployeeID\" style=\"width:$intDefaultWidthPx\"width:$intDefaultWidthPx\" >";
    $strInputFilterDepartment = getDepartmentList(
        $db,
        "filterDepartment",
        $strFilterDepartment,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\" $strDisabled"
    );
    $strInputFilterSection = getSectionList(
        $db,
        "filterSection",
        $strFilterSection,
        $strEmptyOption,
        "",
        " style=\"width:$intDefaultWidthPx\" $strDisabled"
    );
    $strInputFilterYear = getYearList("filterYear", $strFilterYear, "");
    /*
    // tombol untuk check/approve
    if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
      $strButtons = "<input name=btnCheck value=\"" .$words['check']."\" onclick=\"return confirmCheck();\" type=\"submit\">";
    } else if ($_SESSION['sessionUserRole'] == ROLE_MANAGER) {
      $strButtons = "<input name=btnApprove value=\"" .$words['approve']."\" onclick=\"return confirmCheck();\" type=\"submit\">";
    }
    */
    $strHidden .= "<input type=hidden name=filterEmployeeID value=\"$strFilterEmployeeID\">";
    $strHidden .= "<input type=hidden name=filterDepartment value=\"$strFilterDepartment\">";
    $strHidden .= "<input type=hidden name=filterSection value=\"$strFilterSection\">";
    $strHidden .= "<input type=hidden name=filterYear value=\"$strFilterYear\">";
}
if (isset($_POST['btnSearch'])) {
    $value = $_POST['ReportView'];
    if ($value == "TrainingList") {
        if ($bolCanView) {
            $strDataDetail = getData($db, $intTotalData, $strFilterYear, $strKriteria, $strSortBy);
        } else {
            showError("view_denied");
        }
        $dataprint = $strDataDetail;
        $strTemplateFile = getTemplate("training_list.html");
    } elseif ($value == "EmployeeTraining") {
        if ($bolCanView) {
            if (isset($_REQUEST['btnPrint'])) {
                $bolLimit = false;
            }
            $strDataDetail = getData1(
                $db,
                $intTotalData,
                $strFilterYear,
                $strKriteria,
                $intCurrPage,
                $bolLimit,
                $strSortBy
            );
        } else {
            showError("view_denied");
        }
        $dataprint = $strDataDetail;
        $strTemplateFile = getTemplate("training_list_employee.html");
    }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
