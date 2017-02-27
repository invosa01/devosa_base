<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/form2/form2.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../classes/hrd/hrd_absence_partial.php');
include_once('../classes/hrd/hrd_absence_detail.php');
include_once('overtime_func.php');
include_once('activity.php');
include_once('form_object.php');
include_once('attendance_functions.php');
//include_once("../includes/krumo/class.krumo.php");
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
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
$bolSync = isset($_REQUEST['btnSync']);
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExportXLS']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strWordsATTENDANCEDATA = getWords("attendance data");
$strWordsEntryAttendance = getWords("entry attendance");
$strWordsImportAttendance = getWords("import attendance");
$strWordsAttendanceList = getWords("attendance list");
$strWordsAttendanceReport = getWords("attendance report");
$strWordsDateFrom = getWords("date from");
$strWordsDateThru = getWords("date thru");
$strWordsEmployeeID = getWords("employee id");
$strWordsCompany = getWords("company");
$strWordsShow = getWords("show");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("subsection");
$strWordsEmployeeStatus = getWords("employee status");
$strWordsActive = getWords("active");
$strWordsOutdated = getWords("outdated");
$strWordsSalary = getWords("salary");
$strWordsActive = getWords("active");
$DataGrid = "";
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strButtons = "";
$strButtonsTop = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
function getData($db, $bolSync = false)
{
    //global $words;
    global $dataPrivilege;
    global $f;
    global $myDataGrid;
    global $DataGrid;
    global $strKriteriaCompany;
    global $arrUserInfo;
    //var_dump($arrUserInfo);
    if ($db->connect()) {
        $arrData = $f->getObjectValues();
        $strKriteria = "";
        $strKriteriaDiv = "";
        //$splitDateFrom = explode('/', $arrData['dataDateFrom']);
        //$strDateFrom = $splitDateFrom[2] . '-' . $splitDateFrom[0] . '-' . $splitDateFrom[1];
        //$splitDateThru = explode('/', $arrData['dataDateThru']);
        //$strDateThru = $splitDateThru[2] . '-' . $splitDateThru[0] . '-' . $splitDateThru[1];
        $strDateFrom = standardDateToSQLDateNew(
            $arrData['dataDateFrom'],
            $_SESSION['sessionDateSetting']['date_sparator'],
            $_SESSION['sessionDateSetting']['pos_year'],
            $_SESSION['sessionDateSetting']['pos_month'],
            $_SESSION['sessionDateSetting']['pos_day']
        );
        $strDateThru = standardDateToSQLDateNew(
            $arrData['dataDateThru'],
            $_SESSION['sessionDateSetting']['date_sparator'],
            $_SESSION['sessionDateSetting']['pos_year'],
            $_SESSION['sessionDateSetting']['pos_month'],
            $_SESSION['sessionDateSetting']['pos_day']
        );
        $strIDEmployee = getIDEmployee($db, $arrData['dataEmployee']);
        // GENERATE CRITERIA
        if ($arrData['dataEmployee'] != "") {
            $strKriteria .= "AND employee_id = '" . $arrData['dataEmployee'] . "' ";
        }
        if ($arrData['dataPosition'] != "") {
            $strKriteria .= "AND t1.position_code = '" . $arrData['dataPosition'] . "' ";
            /*
            ini hanya work-around
            akan di-refer 2x oleh 2 SQL:
            ->
            SELECT .... FROM hrd_employee AS t0 LEFT JOIN hrd_position AS t1 .... //error di query ini
            ->
            SELECT .... FROM hrd_employee AS t1 ....
            ->
            karena position_code ada di baik hrd_employee & hrd_position, maka di work-around dengan menambah t1.
            */
        }
        if ($arrData['dataBranch'] != "") {
            $strKriteria .= "AND branch_code = '" . $arrData['dataBranch'] . "' ";
        }
        if ($arrData['dataGrade'] != "") {
            $strKriteria .= "AND grade_code = '" . $arrData['dataGrade'] . "' ";
        }
        if ($arrData['dataEmployeeStatus'] != "") {
            $strKriteria .= "AND employee_status = '" . $arrData['dataEmployeeStatus'] . "' ";
        }
        if ($arrData['dataActive'] != "") {
            $strKriteria .= "AND active = '" . $arrData['dataActive'] . "' ";
        }
        /*if ($arrData['dataRequestStatus']!= "") {
          $strKriteria .= "AND status = '".$arrData['dataRequestStatus']."' ";
        }*/
        if ($arrData['dataDivision'] != "") {
            $strKriteria .= "AND division_code = '" . $arrData['dataDivision'] . "' ";
            $strKriteriaDiv = " where division_code= '" . $arrData['dataDivision'] . "' ";
        }
        /*
        if ($arrData['dataDepartment']!= "") {
          $strKriteria .= "AND department_code = '".$arrData['dataDepartment']."' ";
        }
        if ($arrData['dataSection']!= "") {
          $strKriteria .= "AND section_code = '".$arrData['dataSection']."' ";
        }
        if ($arrData['dataSubSection']!= "") {
          $strKriteria .= "AND sub_section_code = '".$arrData['dataSubSection']."' ";
        }
        */
        //uddin: tambah kriteria jika employee maka yg muncul employee yg functional dia dan dibawahnya
        $strDataUserRole = $_SESSION['sessionUserRole'];
        if ($strDataUserRole == ROLE_EMPLOYEE or $strDataUserRole == ROLE_SUPERVISOR) {
            if ($arrUserInfo["functional_code"] != "") {
                $strSQL = "SELECT t1.*,t2.employee_id FROM hrd_functional as t1
                    LEFT JOIN  (select * from hrd_employee " . $strKriteriaDiv . ") as t2 ON t1.functional_code=t2.functional_code
                    WHERE t1.head_code='" . $arrUserInfo["functional_code"] . "'";
                $resDb = $db->execute($strSQL);
                $strFunctionalcode = "('" . $arrUserInfo["functional_code"] . "'"; // inisial masukkan kode functional diri sendiri
                while ($rowDb = $db->fetchrow($resDb)) {
                    //lakukan pengecekan jika tidak employee di posisi tersebut maka cari employee di level bawahnya
                    // agar employee yg atasan langsungnya masih vacant bisa di approval
                    $tempRecursif = getfunctionalrecursif(
                        $db,
                        $rowDb['functional_code'],
                        $rowDb['employee_id'],
                        $strKriteriaDiv,
                        0
                    );
                    $strFunctionalcode .= ",'" . $rowDb['functional_code'] . "'" . $tempRecursif;
                }
                $strFunctionalcode .= ")";
                $strKriteria .= "AND functional_code in " . $strFunctionalcode . " ";
            }
        }
        // end tambah kriteria functional code
        //die($strFunctionalcode);
        $strKriteria .= $strKriteriaCompany;
        $strKriteriaBackup = $strKriteria;
        if ($bolSync) {
            syncShiftAttendance($db, $strDateFrom, $strDateThru, $strKriteria);
            syncOvertimeApplication($db, $strDateFrom, $strDateThru, $strIDEmployee, $strKriteria);
            syncLateEarly($db, $strDateFrom, $strDateThru, "", $strKriteria);
        }
        //get approved late or early
        $tblAbsencePartial = new cHrdAbsencePartial();
        $strCriteria = "partial_absence_date BETWEEN '$strDateFrom' AND '$strDateThru' AND status >= " . REQUEST_STATUS_APPROVED . " ";
        if ($arrData['dataEmployee'] != "") {
            $strCriteria .= "AND id_employee = '" . getIDEmployee($db, $arrData['dataEmployee']) . "' ";
        }
        $dataAbsencePartial = $tblAbsencePartial->findAll($strCriteria, "", "", null, 1, "id");
        foreach ($dataAbsencePartial as $strID => $detailAbsencePartial) {
            $arrAbsencePartial[$detailAbsencePartial['partial_absence_date']][$detailAbsencePartial['id_employee']][$detailAbsencePartial['partial_absence_type']] = $detailAbsencePartial;
        }
        //get absence which cancels late/early
        $strSQL = "SELECT t1.*, cancel_partial_absence, status FROM hrd_absence_detail AS t1 ";
        $strSQL .= "LEFT JOIN hrd_absence_type AS t2 ON t1.absence_type = t2.code ";
        $strSQL .= "LEFT JOIN hrd_absence AS t3 ON t1.id_absence = t3.id ";
        $strSQL .= "WHERE absence_date BETWEEN '$strDateFrom' AND '$strDateThru' AND cancel_partial_absence = TRUE AND status >= " . REQUEST_STATUS_APPROVED . " ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $arrCancelLate[$rowDb['absence_date']][$rowDb['id_employee']] = true;
        }
        $intRows = 0;
        $strResult = "";
        $dataset = [];
        $objAttendanceClass = new clsAttendanceClass($db);
        $objAttendanceClass->resetAttendance();
        $objAttendanceClass->setFilter($strDateFrom, $strDateThru, $strIDEmployee, $strKriteria);
        $objAttendanceClass->getAttendanceResource();
        $objToday = new clsAttendanceInfo($db);
        $intLate = 0;
        $intEarly = 0;
        $intTotalLate = 0;
        $intTotalEarly = 0;
        $intApprovedLate = 0;
        $intApprovedEarly = 0;
        $intTotalApprovedLate = 0;
        $intTotalApprovedEarly = 0;
        $intTotalOT = 0;
        $intTotalCalculatedOT = 0;
        $strCurrDate = $strDateFrom;
        while (dateCompare($strCurrDate, $strDateThru) <= 0) {
            $arrAttendance = (isset($objAttendanceClass->arrAttendance[$strCurrDate])) ? $objAttendanceClass->arrAttendance[$strCurrDate] : [];
            foreach ($objAttendanceClass->arrEmployee as $strIDEmployee => $arrEmployee) {
                $objToday->newInfo($strIDEmployee, $strCurrDate);
                $objToday->initAttendanceInfo($objAttendanceClass);
                if (isset($arrCancelLate[$strCurrDate][$strIDEmployee])) {
                    $intLate = "";
                    $intEarly = "";
                    $intApprovedLate = "";
                    $intApprovedEarly = "";
                } else {
                    if (isset($arrAbsencePartial[$strCurrDate][$strIDEmployee])) {
                        if (isset($arrAbsencePartial[$strCurrDate][$strIDEmployee][PARTIAL_ABSENCE_LATE]) && is_numeric(
                                $arrAbsencePartial[$strCurrDate][$strIDEmployee][PARTIAL_ABSENCE_LATE]['approved_duration']
                            )
                        ) {
                            $intApprovedLate = $arrAbsencePartial[$strCurrDate][$strIDEmployee][PARTIAL_ABSENCE_LATE]['approved_duration'];
                            $intLate = $objToday->intLate - $intApprovedLate;
                            //$intLate = ($intLate < 0) ? 0 : "";
                            $intLate = ($intLate <= 0) ? "" : $intLate;
                        } else {
                            $intLate = ($objToday->intLate == 0) ? "" : $objToday->intLate;
                            $intApprovedLate = "";
                        }
                        if (isset($arrAbsencePartial[$strCurrDate][$strIDEmployee][PARTIAL_ABSENCE_EARLY]) && is_numeric(
                                $arrAbsencePartial[$strCurrDate][$strIDEmployee][PARTIAL_ABSENCE_EARLY]['approved_duration']
                            )
                        ) {
                            $intApprovedEarly = $arrAbsencePartial[$strCurrDate][$strIDEmployee][PARTIAL_ABSENCE_EARLY]['approved_duration'];
                            $intEarly = $objToday->intEarly - $intApprovedEarly;
                            //$intEarly = ($intEarly < 0) ? 0 : "";
                        } else {
                            $intEarly = ($objToday->intEarly == 0) ? "" : $objToday->intEarly;
                            $intApprovedEarly = "";
                        }
                    } else {
                        $intLate = ($objToday->intLate <= 0) ? "" : $objToday->intLate;
                        $intEarly = ($objToday->intEarly == 0) ? "" : $objToday->intEarly;
                        $intApprovedLate = "";
                        $intApprovedEarly = "";
                    }
                }
                /*$arrDate = explode("-", $strCurrDate);
                $strCurrDate_ = $arrDate[1] . "/" . $arrDate[2] . "/" . $arrDate[0];*/
                $strCurrDate_ = $strCurrDate;
                $dataset[] = [
                    "attendance_date_"      => $strCurrDate_,
                    "attendance_date"       => $strCurrDate,
                    "id_employee"           => $strIDEmployee,
                    "employee_id"           => $arrEmployee['employee_id'],
                    "employee_name"         => $arrEmployee['employee_name'],
                    "division_code"         => $arrEmployee['division_code'],
                    "division_name"         => $arrEmployee['division_name'],
                    "department_code"       => $arrEmployee['department_code'],
                    "department_name"       => $arrEmployee['department_name'],
                    "section_code"          => $arrEmployee['section_code'],
                    "section_name"          => $arrEmployee['section_name'],
                    "absence_code"          => $objToday->strAbsenceCode,
                    "shift_code"            => $objToday->strShiftCode,
                    "attendance_start"      => $objToday->strAttendanceStart,
                    "attendance_finish"     => $objToday->strAttendanceFinish,
                    "normal_start"          => $objToday->strNormalStart,
                    "normal_finish"         => $objToday->strNormalFinish,
                    "late"                  => $intLate,
                    "early"                 => $intEarly,
                    "approved_late"         => $intApprovedLate,
                    "approved_early"        => $intApprovedEarly,
                    "overtime_start_early"  => $objToday->strOvertimeStartEarly,
                    "overtime_finish_early" => $objToday->strOvertimeFinishEarly,
                    "overtime_start"        => $objToday->strOvertimeStart,
                    "overtime_finish"       => $objToday->strOvertimeFinish,
                    "normal_finish"         => $objToday->strNormalFinish,
                    "ot"                    => $objToday->fltTotalOT,
                    "calculated_ot"         => $objToday->totOTCalculated,
                    "data_source"           => $objToday->strDataSource
                ];
                $intTotalLate += ((is_numeric($intLate)) ? $intLate : 0);
                $intTotalEarly += ((is_numeric($intEarly)) ? $intEarly : 0);
                $intTotalApprovedLate += ((is_numeric($intApprovedLate)) ? $intApprovedLate : 0);
                $intTotalApprovedEarly += ((is_numeric($intApprovedEarly)) ? $intApprovedEarly : 0);
                $intTotalOT += ((is_numeric($objToday->fltTotalOT)) ? $objToday->fltTotalOT : 0);
                $intTotalCalculatedOT += ((is_numeric($objToday->totOTCalculated)) ? $objToday->totOTCalculated : 0);
            }
            $strCurrDate = getNextDate($strCurrDate);
        }
        // tambahkan baris kosong dan total countable minute
        if (count($dataset) != 0) {
            $tempDataset = [];
            foreach ($dataset[0] as $key => $value) {
                $tempDataset[$key] = "";
            }
            $dataset[] = $tempDataset;
            foreach ($dataset[0] as $key => $value) {
                switch ($key) {
                    case "employee_name" :
                        $tempValue = strtoupper(getWords("total"));
                        break;
                    case "late" :
                        $tempValue = $intTotalLate;
                        break;
                    case "early" :
                        $tempValue = $intTotalEarly;
                        break;
                    case "approved_late" :
                        $tempValue = $intTotalApprovedLate;
                        break;
                    case "approved_early" :
                        $tempValue = $intTotalApprovedEarly;
                        break;
                    case "ot" :
                        $tempValue = $intTotalOT;
                        break;
                    case "calculated_ot" :
                        $tempValue = $intTotalCalculatedOT;
                        break;
                    default:
                        $tempValue = "";
                }
                $tempDataset[$key] = $tempValue;
            }
            $dataset[] = $tempDataset;
        }
        $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, false, false);
        $myDataGrid->caption = getWords(
            strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
        );
        $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
        $myDataGrid->setCriteria($strKriteria);
        //$myDataGrid->addColumnCheckbox(new DataGrid_Column("chkID", "id", array('width' => '30'), array('align'=>'center', 'nowrap' => '')), true /*bolDisableSelfStatusChange*/);
        $myDataGrid->pageSortBy = "division_name";
        $myDataGrid->addColumnNumbering(
            new DataGrid_Column("", "", ['rowspan' => '2', 'width' => '30'], ['nowrap' => ''])
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("date"), "attendance_date_", ['rowspan' => '2'], ['nowrap' => ''])
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("day"),
                "attendance_date",
                ['rowspan' => '2'],
                ['nowrap' => ''],
                true,
                false,
                "",
                "printWDay()"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("id"), "employee_id", ['rowspan' => '2'], ['nowrap' => ''])
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("employee name"), "employee_name", ['rowspan' => '2'], ['nowrap' => ''])
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("division"), "division_name", ['rowspan' => '2'], ['nowrap' => ''])
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("department"), "department_name", ['rowspan' => '2'], ['nowrap' => ''])
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("section"), "section_name", ['rowspan' => '2'], ['nowrap' => ''])
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("absence"),
                "absence_code",
                ['rowspan' => '2'],
                ['style' => 'color:red;font-size:11;font-weight:bold', 'nowrap' => '']
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("shift"), "shift_code", ['rowspan' => '2'], ['nowrap' => ''])
        );
        $myDataGrid->addSpannedColumn(getWords("attendance"), 2);
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("start"),
                "attendance_start",
                "",
                ["nowrap" => "nowrap"],
                true,
                true,
                "",
                "",
                "string",
                true,
                10,
                true
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("finish"),
                "attendance_finish",
                "",
                ["nowrap" => "nowrap"],
                true,
                true,
                "",
                "",
                "string",
                true,
                10,
                true
            )
        );
        $myDataGrid->addSpannedColumn(getWords("normal"), 2);
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("start"),
                "normal_start",
                "",
                ['nowrap' => ''],
                true,
                true,
                "",
                "",
                "string",
                true,
                10,
                true
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("finish"),
                "normal_finish",
                "",
                ['nowrap' => ''],
                true,
                true,
                "",
                "",
                "string",
                true,
                10,
                true
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("late"),
                "late",
                ['rowspan' => '2'],
                ['style' => 'color:red;font-size:11;font-weight:bold', 'nowrap' => ''],
                true,
                true,
                "",
                "formatTime()"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("app. late"),
                "approved_late",
                ['rowspan' => '2', 'width' => '30'],
                ['nowrap' => ''],
                true,
                false,
                "",
                "formatTime()"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("early"),
                "early",
                ['rowspan' => '2'],
                ['style' => 'color:red;font-size:11;font-weight:bold', 'nowrap' => ''],
                true,
                true,
                "",
                "formatTime()"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("app. early"),
                "approved_early",
                ['rowspan' => '2', 'width' => '30'],
                ['nowrap' => ''],
                true,
                false,
                "",
                "formatTime()"
            )
        );
        $myDataGrid->addSpannedColumn(getWords("early overtime"), 2);
        $myDataGrid->addColumn(new DataGrid_Column(getWords("start"), "overtime_start_early", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("finish"), "overtime_finish_early", "", ['nowrap' => '']));
        $myDataGrid->addSpannedColumn(getWords("afternoon overtime"), 2);
        $myDataGrid->addColumn(new DataGrid_Column(getWords("start"), "overtime_start", "", ['nowrap' => '']));
        $myDataGrid->addColumn(new DataGrid_Column(getWords("finish"), "overtime_finish", "", ['nowrap' => '']));
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("total ot"),
                "ot",
                ['rowspan' => '2'],
                ['nowrap' => ''],
                true,
                false,
                "",
                "formatTime()"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("calculated ot"),
                "calculated_ot",
                ['rowspan' => '2'],
                ['nowrap' => ''],
                true,
                false,
                "",
                "formatTime()"
            )
        );
        $myDataGrid->addColumn(
            new DataGrid_Column(getWords("source"), "data_source", ['rowspan' => '2'], ['nowrap' => ''])
        );
        //$myDataGrid->addButtonExportExcel("Export Excel", $dataPrivilege['menu_name'].".xls", getWords($dataPrivilege['menu_name']));
        $myDataGrid->addButtonExportExcel(
            getWords("export excel"),
            str_replace(" ", "_", $dataPrivilege['menu_name'] . ".xls"),
            getWords($dataPrivilege['menu_name'])
        );
        //$myDataGrid->setPageLimit("all");
        $myDataGrid->getRequest();
        $myDataGrid->hasGrandTotal = true;
        $intTotalData = $myDataGrid->totalData = count($dataset);
        // PROCESS TO FILTER THE dataset based on the AJAX VALUE.
        // Special case for attendance list since the dataset is generated not by DataGrid class
        $datasetFiltered = filterBasedDataGrid($dataset);
        $intTotalData = $myDataGrid->totalData = count($datasetFiltered);
        //bind Datagrid with array dataset
        $myDataGrid->bind($datasetFiltered);
        return $myDataGrid->render();
    }
} // getData
function filterBasedDataGrid($dataset)
{
    $newDataSet = [];
    $criteria = "";
    $searchBy = "";
    $notNull = false;
    $found = false;
    if (isset($_REQUEST['datagridajax'])) {
        if (isset($_REQUEST['pageSearchCriteriaDataGrid1']) && $_REQUEST['pageSearchCriteriaDataGrid1'] != "") {
            if (isset($_REQUEST['pageSearchByDataGrid1'])) {
                $found = true;
                $criteria = $_REQUEST['pageSearchCriteriaDataGrid1'];
                $searchBy = $_REQUEST['pageSearchByDataGrid1'];
                if (strtolower($criteria) === "not empty") {
                    $notNull = true;
                }
            }
        }
    }
    if (isset($_REQUEST['pageSortByDataGrid1'])) {
        $found = true;
        $criteria = $_REQUEST['pageSearchCriteriaDataGrid1'];
        $searchBy = $_REQUEST['pageSearchByDataGrid1'];
        $sortBy = $_REQUEST['pageSortByDataGrid1'];
        if (strtolower($criteria) === "not empty") {
            $notNull = true;
        }
    }
    if (!$found) {
        return $dataset;
    }
    foreach ($dataset as $idx => $data) {
        foreach ($data as $key => $value) {
            if ($key == $searchBy) {
                if ($notNull) {
                    if (strlen($value) >= 1) {
                        $newDataSet[] = $data;
                    }
                } else if (strpos(strtolower($value), strtolower($criteria)) !== false) {
                    $newDataSet[] = $data;
                }
            }
        }
    }
    // start
    // by uddin, 20141119
    // purpose: modified sort grid by click on header grid
    if ($sortBy != "") {
        $arrSort = explode(" ", $sortBy);
        $sortBy = $arrSort[0];
        if (count($newDataSet) == 0) {
            $newDataSet = $dataset;
        }
        $sortArray = [];
        foreach ($newDataSet as $person) {
            foreach ($person as $key => $value) {
                if (!isset($sortArray[$key])) {
                    $sortArray[$key] = [];
                }
                $sortArray[$key][] = $value;
            }
        }
        $orderby = $sortBy; //change this to whatever key you want from the array
        if (@$arrSort[1] == "DESC") {
            array_multisort($sortArray[$orderby], SORT_DESC, $newDataSet);
        } else {
            array_multisort($sortArray[$orderby], SORT_ASC, $newDataSet);
        }
    }
    //end
    return $newDataSet;
}

function printWDay($params)
{
    global $bolPrint;
    extract($params);
    $strDay = getNamaHariSingkat(getWDay($value));
    return (($strDay == "Sat" || $strDay == "Sun") && !$bolPrint) ? "<strong><font color=red size=-1>$strDay</font></strong>" : $strDay;
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$intDefaultStart = "08:00";
$intDefaultFinish = "17:00";
$db = new CdbClass;
if ($db->connect()) {
    getUserEmployeeInfo();
    $arrUserList = getAllUserInfo($db);
    $strDataID = getPostValue('dataID');
    scopeData(
        $strDataEmployee,
        $strDataSubSection,
        $strDataSection,
        $strDataDepartment,
        $strDataDivision,
        $_SESSION['sessionUserRole'],
        $arrUserInfo
    );
    //generate form untuk select trip type
    //trip type harus dipilih dulu supaya jenis2 trip allowance dapat ditentukan
    $f = new clsForm("formFilter", 3, "100%", "");
    $f->caption = strtoupper($strWordsFILTERDATA);
    $f->addInput(
        getWords("date from"),
        "dataDateFrom",
        ($strDateFrom = getInitialValue("DateFrom", date($_SESSION['sessionDateSetting']['php_format']))),
        ["style" => "width:$strDateWidth"],
        "date",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("date thru"),
        "dataDateThru",
        ($strDateThru = getInitialValue("DateThru", date($_SESSION['sessionDateSetting']['php_format']))),
        ["style" => "width:$strDateWidth"],
        "date",
        false,
        true,
        true
    );
    $autoCompleteValue = getInitialValue("Employee", null, $strDataEmployee);
    $employeeName = '';
    if (!empty($autoCompleteValue)) {
        $employeeData = getEmployeNameByID($db, $autoCompleteValue);
        $employeeName = $employeeData['employee_name'];
    }
    $f->addInputAutoComplete(
        getWords("employee"),
        "dataEmployee",
        getDataEmployee(getInitialValue("Employee", null, $strDataEmployee)),
        "style=width:$strDefaultWidthPx " . $strEmpReadonly,
        "string",
        false,
        true,
        true,
        "",
        "",
        true,
        null,
        "../global/hrd_ajax_source.php?action=getemployee",
        $autoCompleteValue
    );
    $f->addLabelAutoComplete("", "dataEmployee", $employeeName);
    //$f->addSelect(getWords("request status"), "dataRequestStatus", getDataListRequestStatus(getInitialValue("RequestStatus"), true, array("value" => "", "text" => "", "selected" => true)), array("style" => "width:$strDefaultWidthPx"), "", false, false);
    $f->addLiteral("", "", "");
    $f->addSelect(
        getWords("branch"),
        "dataBranch",
        getDataListBranch(getInitialValue("Branch"), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("level"),
        "dataPosition",
        getDataListPosition(getInitialValue("Position"), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("grade"),
        "dataGrade",
        getDataListSalaryGrade(getInitialValue("Grade"), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("status"),
        "dataEmployeeStatus",
        getDataListEmployeeStatus(
            getInitialValue("EmployeeStatus"),
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("active"),
        "dataActive",
        getDataListEmployeeActive(
            getInitialValue("Active"),
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addLiteral("", "", "");
    $f->addSelect(
        getWords("company"),
        "dataCompany",
        getDataListCompany($strDataCompany, $bolCompanyEmptyOption, $arrCompanyEmptyData, $strKriteria2),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addSelect(
        getWords("division"),
        "dataDivision",
        getDataListDivision(getInitialValue("Division", "", $strDataDivision), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['division'] == "")
    );
    $f->addSelect(
        getWords("department "),
        "dataDepartment",
        getDataListDepartment(getInitialValue("Department", "", $strDataDepartment), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['department'] == "")
    );
    $f->addSelect(
        getWords("section"),
        "dataSection",
        getDataListSection(getInitialValue("Section", "", $strDataSection), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['section'] == "")
    );
    $f->addSelect(
        getWords("sub section"),
        "dataSubSection",
        getDataListSubSection(getInitialValue("SubSection", "", $strDataSubSection), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['sub_section'] == "")
    );
    $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
    if ($bolCanApprove) {
        $f->addSubmit("btnSync", getWords("sync"), "", true, true, "", "", "");
    }
    $formFilter = $f->render();
    //$splitDateFrom = explode('/', $strDateFrom);
    //$strDateFrom = $splitDateFrom[2] . '-' . $splitDateFrom[0] . '-' . $splitDateFrom[1];
    //$splitDateThru = explode('/', $strDateThru);
    //$strDateThru = $splitDateThru[2] . '-' . $splitDateThru[0] . '-' . $splitDateThru[1];
    $strDateFrom = standardDateToSQLDateNew(
        $strDateFrom,
        $_SESSION['sessionDateSetting']['date_sparator'],
        $_SESSION['sessionDateSetting']['pos_year'],
        $_SESSION['sessionDateSetting']['pos_month'],
        $_SESSION['sessionDateSetting']['pos_day']
    );
    $strDateThru = standardDateToSQLDateNew(
        $strDateThru,
        $_SESSION['sessionDateSetting']['date_sparator'],
        $_SESSION['sessionDateSetting']['pos_year'],
        $_SESSION['sessionDateSetting']['pos_month'],
        $_SESSION['sessionDateSetting']['pos_day']
    );
    if (validStandardDate($strDateFrom) && validStandardDate(
            $strDateThru
        ) && (isset($_REQUEST['btnShow']) || isset($_REQUEST['btnExportXLS']) || $bolSync)
    ) {
        // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
        $myDataGrid = new cDataGrid("formData", "DataGrid", "100%", "100%", false, false, false);
        $myDataGrid->caption = $dataPrivilege['menu_name'];
        $DataGrid = getData($db, $bolSync);
        $strHidden .= "<input type=hidden name=btnShow value=show>";
    } else if (isset($_REQUEST['datagridajax']) && $_REQUEST['datagridajax'] == 1) {
        $myDataGrid = new cDataGrid("formData", "DataGrid", "100%", "100%", false, false, false);
        $myDataGrid->caption = $dataPrivilege['menu_name'];
        //	  $myDataGrid->pageSortBy = "employee_id";
        $DataGrid = getData($db, true);
    }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
    $pageIcon = "../images/icons/blank.gif";
} else {
    $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = getWords('employee attendance list');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = attendanceSubMenu($strWordsAttendanceList);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
