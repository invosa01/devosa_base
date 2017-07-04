<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/employee_function.php');
include_once('form_object.php');
include_once('salary_func.php');
include_once('../includes/model/model.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../classes/hrd/hrd_basic_salary_set.php');
include_once("../includes/krumo/class.krumo.php");
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
$strDisplay = ($bolCanEdit) ? "table-row" : "none";
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strWordsCompany = getWords("company");
//$strWordsCurrency        = getWords("currency");
$strWordsSalaryDate = getWords("salary calculation date");
$strWordsAttendanceDate = getWords("period for activity");
$strWordsPeriodForTHR = getWords("period for THR");
$strWordsSalarySet = getWords("salary set");
$strWordsIrregular = getWords("irregular");
$strWordsHideIfBlank = getWords("hide if blank");
$strWordsStartCalculation = getWords("start calculation");
//$strWordsTaxRate        = getWords("tax rate");
$strWordsNote = getWords("note");
$strHidden = "";
$strButtons = "";
$intTotalData = 0;
//krumo($_SESSION);
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// menyimpan perintah perhitungan gaji
// output : ID perhitungan gaji, jika sukses, jika gagal, return ""
function saveData($db, &$strError)
{
    global $_REQUEST;
    global $error;
    global $_SESSION;
    $strDateSparator = $_SESSION['sessionDateSetting']['date_sparator'];
    $strDatePosYear = $_SESSION['sessionDateSetting']['pos_year'];
    $strDatePosMonth = $_SESSION['sessionDateSetting']['pos_month'];
    $strDatePosDay = $_SESSION['sessionDateSetting']['pos_day'];
    $strDatePHPFormat = $_SESSION['sessionDateSetting']['php_format'];
    $strDataTHRDateFrom = (isset($_REQUEST['dataTHRDateFrom'])) ? standardDateToSQLDateNew(
        $_REQUEST['dataTHRDateFrom'],
        $strDateSparator,
        $strDatePosYear,
        $strDatePosMonth,
        $strDatePosDay
    ) : "";
    $strDataTHRDateThru = (isset($_REQUEST['dataTHRDateThru'])) ? standardDateToSQLDateNew(
        $_REQUEST['dataTHRDateThru'],
        $strDateSparator,
        $strDatePosYear,
        $strDatePosMonth,
        $strDatePosDay
    ) : "";
    $strDataDateFrom = (isset($_REQUEST['dataDateFrom'])) ? standardDateToSQLDateNew(
        $_REQUEST['dataDateFrom'],
        $strDateSparator,
        $strDatePosYear,
        $strDatePosMonth,
        $strDatePosDay
    ) : date($strDatePHPFormat);
    $strDataDateThru = (isset($_REQUEST['dataDateThru'])) ? standardDateToSQLDateNew(
        $_REQUEST['dataDateThru'],
        $strDateSparator,
        $strDatePosYear,
        $strDatePosMonth,
        $strDatePosDay
    ) : date($strDatePHPFormat);
    $strDataDate = (isset($_REQUEST['dataDate'])) ? standardDateToSQLDateNew(
        $_REQUEST['dataDate'],
        $strDateSparator,
        $strDatePosYear,
        $strDatePosMonth,
        $strDatePosDay
    ) : date($strDatePHPFormat);
    //$strDataCurrency    = (isset($_REQUEST['dataCurrency']))    ? $_REQUEST['dataCurrency']     : "";
    $strDataCompany = (isset($_REQUEST['dataCompany'])) ? $_REQUEST['dataCompany'] : "";
    $bolIrregular = (isset($_REQUEST['dataIrregular'])) ? true : false;
    $bolHideBlank = (isset($_REQUEST['dataHideBlank'])) ? true : false;
    $intFlag =  0;
    $strDataNote = (isset($_REQUEST['dataNote'])) ? $_REQUEST['dataNote'] : "";
    //$strDataTaxRate     = (isset($_REQUEST['dataTaxRate']))     ? $_REQUEST['dataTaxRate']      : "";
    $strDataIDSalarySet = (isset($_REQUEST['dataIDSalarySet'])) ? $_REQUEST['dataIDSalarySet'] : "";
    if (!validStandardDate($strDataTHRDateFrom) && $strDataTHRDateFrom != "") {
        $strError = $error['invalid_date'] . " " . $strDataTHRDateFrom;
        return 0;
    } else if (!validStandardDate($strDataTHRDateThru) && $strDataTHRDateFrom != "") {
        $strError = $error['invalid_date'] . " " . $strDataTHRDateThru;
        return 0;
    } else if (!validStandardDate($strDataDateFrom)) {
        $strError = $error['invalid_date'] . " " . $strDataDateFrom;
        return 0;
    } else if (!validStandardDate($strDataDateThru)) {
        $strError = $error['invalid_date'] . " " . $strDataDateThru;
        return 0;
    } else if (!validStandardDate($strDataDate)) {
        $strError = $error['invalid_date'] . " " . $strDataDate;
        return 0;
    } /*else if (!is_numeric($strDataTaxRate)) {
      $strError = $error['invalid_number']." ".$strDataDate;
      return 0;
    }*/
    if ($strDataCompany == "") {
        $strError = "please choose one company to start salary calculation";
        return 0;
    }
    /*if ($strDataCurrency == "")
    {
      $strError = "please choose one currency to start salary calculation";
      return 0;
    }*/
    /*
        if (!$bolIrregular)
        {
          // cek apakah untuk tanggal ini sudah pernah ada
          $strSQL  = "SELECT id FROM hrd_salary_master WHERE ";
          $strSQL .= "date_from = '$strDataDateFrom' AND date_thru = '$strDataDateThru' AND id_company = '$strDataCompany' ";
          $resDb = $db->execute($strSQL);
          if ($rowDb = $db->fetchrow($resDb)) {
            // error , sudah pernah ada
            $strError = "Duplicate!";
            return 0;
          }
        }
        */
    $intID = "";
    include_once("cls_salary_calculation.php");
    $objSalary = new clsSalaryCalculation(
        $db,
        "",
        $bolIrregular,
        $strDataDate,
        ["id_company" => $strDataCompany],
        $strDataDateFrom,
        $strDataDateThru
    );
    $objSalary->setSalaryDate(
        $strDataDate,
        $strDataDateFrom,
        $strDataDateThru,
        $strDataTHRDateFrom,
        $strDataTHRDateThru,
        $strDataCompany,
        $strDataIDSalarySet,
        $bolHideBlank,
        $strDataNote,
        $intFlag
    );
    $objSalary->saveData();
    $intID = $objSalary->strDataID;
    unset($objSalary);
    return $intID;
}// saveData
// fungsi untuk menghapus data
function deleteData()
{
    global $db;
    global $myDataGrid;
    $arrKeys = [];
    $db->execute("begin");
    $isSuccess = false;
    $counter = 0;
    foreach ($myDataGrid->checkboxes as $strValue) {
        $counter++;
        $strSQL = "";
        $strSQL .= "
        DELETE FROM hrd_salary_master_allowance WHERE id_salary_master = '$strValue';
        DELETE FROM hrd_salary_master_deduction WHERE id_salary_master = '$strValue';
        DELETE FROM hrd_salary_deduction WHERE id_salary_master = '$strValue';
        DELETE FROM hrd_salary_allowance WHERE id_salary_master = '$strValue';
        DELETE FROM hrd_salary_detail WHERE id_salary_master = '$strValue';
        DELETE FROM hrd_salary_master WHERE id = '$strValue';
        DELETE FROM hrd_leave_allowance WHERE  id_salary_master = '$strValue';
      ";
        $isSuccess = $db->execute($strSQL);
        if (!$isSuccess) {
            break;
        }
    }
    if ($isSuccess) {
        $db->execute("commit");
        $myDataGrid->message = $counter . " record(s) " . getWords("salary data deleted!");
    } else {
        $db->execute("rollback");
        $myDataGrid->errorMessage = getWords("failed to delete salary data!");
    }
} //deleteData
// fungsi untuk verify, check, deny, atau approve
function changeStatus($db, $intStatus)
{
    global $_REQUEST;
    global $_SESSION;
    //global $ARRAY_CURRENCY;
    if (!is_numeric($intStatus)) {
        return false;
    }
    $strUpdate = "";
    $strSQL = "";
    $strmodified_byID = $_SESSION['sessionUserID'];
    $strUpdate = getStatusUpdateString($intStatus);
    foreach ($_REQUEST as $strIndex => $strValue) {
        if (substr($strIndex, 0, 15) == 'DataGrid1_chkID') {
            $strSQLx = "SELECT status, salary_date, id_company
                    FROM hrd_salary_master WHERE id = '$strValue' ";
            $resDb = $db->execute($strSQLx);
            if ($rowDb = $db->fetchrow($resDb)) {
                //the status should be increasing
                //if (isProcessable($rowDb['status'], $intStatus))
                if (($intStatus == -1) || (($rowDb['status'] < $intStatus) && ($rowDb['status'] != -1))) {
                    $strSQL .= "UPDATE hrd_salary_master SET $strUpdate status = '$intStatus'  ";
                    $strSQL .= "WHERE id = '$strValue'; ";
                    writeLog(
                        ACTIVITY_EDIT,
                        MODULE_PAYROLL,
                        getCompanyCode($rowDb['id_company']) . " - " . $rowDb['salary_date'],
                        $intStatus
                    );
                }
            }
        }
        $resExec = $db->execute($strSQL);
    }
} //changeStatus
//----------------------------------------------------------------------
//class inheritance from cDataGrid
class cDataGrid2 extends cDataGrid
{

    /*you can inherit this function to created your own TR class or style*/
    function printOpeningRow($intRows, $rowDb)
    {
        $strResult = "";
        $strClass = "";//getCSSClassName($rowDb['status'], false);
        if (($intRows % 2) == 0) {
            $strResult .= "
            <tr $strClass valign=\"top\">";
        } else {
            $strResult .= "
            <tr $strClass valign=\"top\">";
        }
        return $strResult;
    }
}

// fungsi getData dengan datagrid
function getDataGrid($db, $strCriteria, $bolLimit = true, $isFullView = false)
{
    global $bolPrint;
    global $dataPrivilege, $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge;
    global $intTotalData;
    global $myDataGrid;
    $tblSalary = new cModel("hrd_salary_master", getWords("salary calculation"));
    $DEFAULTPAGELIMIT = getSetting("rows_per_page");
    if (!is_numeric($DEFAULTPAGELIMIT)) {
        $DEFAULTPAGELIMIT = 50;
    }
    if ($bolPrint) {
        $myDataGrid = new cDataGrid2("formData", "DataGrid1", "", "", false, false, false, false);
    } else {
        $myDataGrid = new cDataGrid2("formData", "DataGrid1", "100%", "", $bolLimit, false, true);
        $myDataGrid->caption = getWords("list of salary calculation");
    }
    $myDataGrid->disableFormTag();
    //$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->pageSortBy = "id DESC";
    $myDataGrid->setCriteria($strCriteria);
    //end of class initialization
    if (!isset($_REQUEST['btnExportXLS'])) {
        $myDataGrid->addColumnCheckbox(
            new DataGrid_Column("chkID", "id", ['width' => 15], ['align' => 'center', 'nowrap' => ''])
        );
    }
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => 30], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("company"),
            "id_company",
            ["width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "printCompanyName()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(new DataGrid_Column("id", "id", ['width' => 15], ['align' => 'center', 'nowrap' => '']));
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("currency"), "salary_currency", array("width" => 70),  array("nowrap" => "nowrap"), true, true, "", "printSalaryCurrency()", "string", true, 12));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("salary date"),
            "salary_date",
            ["width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "formatDate()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("date from"),
            "date_from",
            ["width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "formatDate()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("date thru"),
            "date_thru",
            ["width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "formatDate()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("irregular income"),
            "irregular",
            ["width" => 50],
            ["align" => "center", "nowrap" => "nowrap"],
            true,
            true,
            "",
            "printActiveSymbol()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("hide if blank"),
            "hide_blank",
            ["width" => 50],
            ["align" => "center", "nowrap" => "nowrap"],
            true,
            true,
            "",
            "printActiveSymbol()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("note"), "note", [], ["nowrap" => "nowrap"], true, true, "", "", "string", true)
    );
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("tax rate"), "tax_rate", array(),  array("nowrap" => "nowrap"), true, true, "", "", "string", true));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("created"),
            "created",
            ["width" => 50],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "formatDate()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("approved"),
            "approved_time",
            ["width" => 50],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "formatDate()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("approved by"),
            "approved_by",
            ["width" => 50],
            ["align" => "center", "nowrap" => "nowrap"],
            true,
            true,
            "",
            "printUserName()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("status"), "status", ['width' => '60'], "", true, true, "", "printRequestStatus()")
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("show"),
            "",
            ["width" => 30],
            ["align" => "center"],
            true,
            true,
            "",
            "printShowLink()",
            "string",
            false,
            12
        )
    );
    generateRoleButtons($bolCanEdit, $bolCanDelete, $bolCanCheck, $bolCanApprove, true, true, $myDataGrid);
    //$myDataGrid->addButton("btnPrint", "btnPrint", "submit", getWords("print"), "onClick=\"document.formData.target = '_blank';\"");
    $myDataGrid->addButtonExportExcel(getWords("export excel"), "salarylist.xls", getWords("list of salary"));
    $myDataGrid->getRequest();
    //--------------------------------
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strOrderBy = $myDataGrid->getSortBy();
    if ($bolLimit) {
        $strPageLimit = $myDataGrid->getPageLimit();
        $intPageNumber = $myDataGrid->getPageNumber();
    } else {
        $strPageLimit = null;
        $intPageNumber = null;
    }
    $myDataGrid->totalData = $tblSalary->findCount($strCriteria);
    $dataset = $tblSalary->findAll(
        $strCriteria,
        "id, id_company, salary_date, date_from, date_thru, date_from_thr, date_thru_thr, irregular, hide_blank, note, created :: date, approved_time :: date, approved_by,status",
        $strOrderBy,
        $strPageLimit,
        $intPageNumber
    );
    $intTotalData = count($dataset);
    $myDataGrid->bind($dataset);
    return $myDataGrid->render();
}

function printShowLink($params)
{
    extract($params);
    //2011-04-21:agnes;
    return generateHidden("dataID_" . $record['id'], $record['id'], "") . generateButton(
        "btnReferer1" . $record['id'],
        getWords("show"),
        "class='btn btn-primary btn-xs'" . getWords(
            "show"
        ),
        "onclick=\"document.formReferer1.dataID.value = '" . $record['id'] . "';document.formReferer1.submit()\""
    );
    #return "<a href='salary_calculation_result.php?dataID=" .$record['id']. "'>" .getWords("show"). "</a>";$value
}

//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
    if (isset($_REQUEST['btnStart'])) {
        if ($bolCanEdit) {
            $strError = "";
            $intID = saveData($db, $strError);
            if ($intID > 0) { // error
                // langsung redirect
                redirectPage("salary_calculation_result.php?dataID=$intID");
                //echo "<script>postToURL('salary_calculation_result.php', {'dataID':'$intID'})</script>";
                exit();
            } else {
                if ($strError != "") {
                    echo "<script>alert('$strError');</script>";
                }
            }
        }
    }
    // ------ AMBIL DATA KRITERIA -------------------------
    // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
    //$strKriteria = " (flag IS NULL OR flag = 0 )";
    $strKriteria = $strKriteriaCompany;
    $bolLimit = false;
    $bolPrint = false;
    if ($bolCanView) {
        //$strDataDetail = getData($db, $intTotalData, $strKriteria);
        $strDataDetail = getDataGrid($db, $strKriteria);
    } else {
        showError("view_denied");
        $strDataDetail = "";
    }
    // generate data hidden input dan element form input
    $intDefaultWidthPx = 200;
    //getDefaultSalaryPeriode($strDefaultFrom,$strDefaultThru);
    $strDateSparator = $_SESSION['sessionDateSetting']['date_sparator'];
    $strDatePosYear = $_SESSION['sessionDateSetting']['pos_year'];
    $strDatePosMonth = $_SESSION['sessionDateSetting']['pos_month'];
    $strDatePosDay = $_SESSION['sessionDateSetting']['pos_day'];
    $strDatePHPFormat = $_SESSION['sessionDateSetting']['php_format'];
    $strDateFormat = $_SESSION['sessionDateFormat'];
    $strDefaultDate = date($strDatePHPFormat);
    $strDefaultDate2 = date("Y-m-d");
    $strTempDate = date("Y-m-d");
    $strTempDate = getNextDateNextMonth($strTempDate, -1); //yyyy-mm-dd
    $arrDt = explode("-", $strTempDate);
    $strDefaultFromDefault = $arrDt[0] . "-" . $arrDt[1] . "-01";
    $strDefaultThruDefault  = $arrDt[0] . "-" . $arrDt[1] . "-" . lastday($arrDt[1], $arrDt[0]);
    $strDefaultFrom = sqlToStandarDateNew($strDefaultFromDefault, $strDateSparator, $strDateFormat);
    $strDefaultThru = sqlToStandarDateNew($strDefaultThruDefault, $strDateSparator, $strDateFormat);

    if (!validStandardDate($strDefaultDate2)) {
        $strDefaultDate = $strDefaultThru;
    }
    /*
    $dtNow = getdate();
    $strDefaultThru = $dtNow['year']."-".$dtNow['mon']."-15";
    if ($dtNow['mon'] == 1) {
      $strDefaultFrom = ($dtNow['year'] - 1)."-12-16";
    } else {
      $strDefaultFrom = $dtNow['year']."-".($dtNow['mon']-1)."-16";
    }
    */
    $strInputDateFrom = "<input class=\"form-control datepicker\" type=text name=\"dataDateFrom\" id=\"dataDateFrom\" maxlength=10 value=\"$strDefaultFrom\" data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\">";
    $strInputDateThru = "<input class=\"form-control datepicker\" type=text name=dataDateThru id=dataDateThru maxlength=10 value=\"$strDefaultThru\" data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\">";
    $strInputTHRDateFrom = "<input class=\"form-control datepicker\" type=text name=dataTHRDateFrom id=dataTHRDateFrom maxlength=10 value=\"\" data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\">";
    $strInputTHRDateThru = "<input class=\"form-control datepicker\" type=text name=dataTHRDateThru id=dataTHRDateThru maxlength=10 value=\"\" data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\">";
    $strInputDate = "<input class=\"form-control datepicker\" type=text name=dataDate id=dataDate maxlength=10 value=\"$strDefaultDate\" data-date-format=\"" . $_SESSION['sessionDateSetting']['html_format'] . "\">";
    //$strInputCurrency     = getComboFromArray($ARRAY_CURRENCY, "dataCurrency", "", $strEmptyOption, " style=\"width:$intDefaultWidthPx\"");
    $strInputCompany = getCompanyList(
        $db,
        "dataCompany",
        $strDataCompany,
        $strEmptyOption2,
        $strKriteria2,
        "style=\"width:$intDefaultWidthPx\" "
    );
    $strInputIrregular = generateCheckBox(
            "dataIrregular",
            false,
            "",
            "",
            '<strong>' . $strWordsIrregular
        ) . '</strong>';
    $strInputHideBlank = generateCheckBox(
        "dataHideBlank",
        false,
        "",
        "",
        '<strong>' . $strWordsHideIfBlank . '</strong>'
    );
    //$strInputFlag = generateHidden("dataFlag",0,"");
    //$strInputTaxRate      = generateInput("dataTaxRate", 1);
    $strInputNote = generateTextArea(
        "dataNote",
        getWords("(note)"),
        "rows=1, cols=45 style=\"color:gray\"",
        "onFocus=\"this.value=''\" onBlur=\"if(this.value == '') this.value='" . getWords(
            "(note)"
        ) . "'\""
    );
    $tblBasicSalarySet = new cHrdBasicSalarySet();
    $arrBasicSalarySet = $tblBasicSalarySet->findAll(
        $strKriteriaCompany,
        "id, start_date, id_company",
        "start_date desc",
        null,
        1,
        "id"
    );
    foreach ($arrBasicSalarySet AS $keySet => $arrSet) {
        $arrSetSource[$keySet] = $arrSet['start_date'] . " - " . printCompanyName($arrSet['id_company']);
    }
    $strInputSalarySet = getComboFromArray(
        $arrSetSource,
        "dataIDSalarySet",
        "",
        "",
        "style=\"width:$intDefaultWidthPx\""
    );
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("employee salary calculation");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
