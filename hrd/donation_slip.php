<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_salary_grade.php');
include_once('../classes/hrd/hrd_donation_type.php');
include_once('../classes/hrd/hrd_donation_platform.php');
include_once('../classes/hrd/hrd_donation.php');
include_once('../classes/hrd/hrd_trip_detail.php');
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
$strWordsDataEntry = getWords("data entry");
$strWordsDonationList = getWords("donation list");
$strWordsDonationReport = getWords("donation report");
$strWordsDispositionForm = getWords("disposition form");
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
$DataGrid = "";
//INISIALISASI------------------------------------------------------------------------------------------------------------------
//ambil semua jenis trip
$tblDonationType = new cHrdDonationType();
$dataDonationType = $tblDonationType->findAll("", "id, code, name", "", null, 1, "code");
//DAFTAR FUNGSI--------------------------------------------------------------------------------------------------------------
function getData($db)
{
  global $dataPrivilege;
  global $f;
  global $myDataGrid;
  global $DataGrid;
  global $strKriteriaCompany;
  //global $arrUserInfo;
  //$strDataTripTypeID = $f->getValue('dataTripType');
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if ($arrData['dataDonationType'] != "") {
    $strKriteria .= "AND donation_code = '" . $arrData['dataDonationType'] . "'";
  }
  if (validStandardDate($arrData['dataDateFrom']) && validStandardDate($arrData['dataDateThru'])) {
    $strKriteria .= "AND (t1.created::date BETWEEN '" . $arrData['dataDateFrom'] . "' AND '" . $arrData['dataDateThru'] . "')  ";
  }
  if ($arrData['dataEmployeeID'] != "") {
    $strKriteria .= "AND employee_id = '" . $arrData['dataEmployeeID'] . "'";
  }
  if ($arrData['dataPosition'] != "") {
    $strKriteria .= "AND position_code = '" . $arrData['dataPosition'] . "'";
  }
  if ($arrData['dataBranch'] != "") {
    $strKriteria .= "AND t2.branch_code = '" . $arrData['dataBranch'] . "'";
  }
  if ($arrData['dataGrade'] != "") {
    $strKriteria .= "AND t2.grade_code = '" . $arrData['dataGrade'] . "'";
  }
  if ($arrData['dataEmployeeStatus'] != "") {
    $strKriteria .= "AND employee_status = '" . $arrData['dataEmployeeStatus'] . "'";
  }
  if ($arrData['dataActive'] != "") {
    $strKriteria .= "AND active = '" . $arrData['dataActive'] . "'";
  }
  if ($arrData['dataRequestStatus'] != "") {
    $strKriteria .= "AND t1.status = '" . $arrData['dataRequestStatus'] . "'";
  }
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND t2.division_code = '" . $arrData['dataDivision'] . "'";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND t2.department_code = '" . $arrData['dataDepartment'] . "'";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND section_code = '" . $arrData['dataSection'] . "'";
  }
  if ($arrData['dataSubSection'] != "") {
    $strKriteria .= "AND sub_section_code = '" . $arrData['dataSubSection'] . "'";
  }
  $strKriteria .= $strKriteriaCompany;
  if ($db->connect()) {
    $myDataGrid->caption = getWords($dataPrivilege['menu_name']);
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->setCriteria($strKriteria);
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => '']),
        false /*bolDisableSelfStatusChange*/
    );
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("form code"), "form_code", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("created"), "created", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date from"), "event_date_from", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date thru"), "event_date_thru", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("employee id"), "employee_id", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("employee name"), "employee_name", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("donation type"), "name", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("name"), "relation_name", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("relation"), "relation_type", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", "", ""));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("amount"),
            "amount",
            ['nowrap' => ''],
            ["align" => "right"],
            false,
            false,
            "",
            "formatNumber()",
            "numeric",
            true,
            15
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("status"), "status", "", "", false, false, "", "printRequestStatus()")
    );
    foreach ($arrData AS $key => $value) {
      $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
    }
    $myDataGrid->addSpecialButton(
        "btnSlip",
        "btnSlip",
        "submit",
        getWords("get slip"),
        "onClick=\"document.formData.target = '_blank'\"",
        "getSlip()"
    );
    $myDataGrid->getRequest();
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_donation AS t1 LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id";
    $strSQL = "SELECT t1.*, employee_id, employee_name, division_name, department_name, t2.branch_code || ' - ' || branch_name as branch, t4.name, t2.grade_code, t2.department_code, t2.division_code
                       FROM hrd_donation AS t1
                       LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id
                       LEFT JOIN hrd_branch  AS t3 ON t2.branch_code = t3.branch_code
                       LEFT JOIN hrd_donation_type  AS t4 ON t1.donation_code= t4.code
                       LEFT JOIN hrd_division AS t5 ON t2.division_code = t5.division_code
                       LEFT JOIN hrd_department AS t6 ON t2.department_code = t6.department_code
                       WHERE 1=1 $strKriteria";
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
    $dataset = $myDataGrid->getData($db, $strSQL);
    //bind Datagrid with array dataset and branchCode
    $myDataGrid->bind($dataset);
    $DataGrid = $myDataGrid->render();
  } else {
    $DataGrid = "";
  }
  return $DataGrid;
}

function printQuota($params)
{
  global $arrTripCostType;
  extract($params);
  $strCostID = substr($field, 10);
  return generateInput("detailQuota_" . $record['grade_code'] . "_" . $strCostID, $value);
}

function printEditLink($params)
{
  extract($params);
  return "
      <a href=\"trip_edit.php?dataID=" . $record['id'] . "\">" . getWords('edit') . "</a>";
}

// fungsi untuk menyimpan data
function getSlip()
{
  global $ARRAY_CURRENCY;
  global $myDataGrid;
  global $db;
  global $strDataID;
  global $dataDonationType;
  global $strSlipContent;
  global $strSlipTopic;
  global $strSlipDate;
  global $strFormCode;
  global $strSlipContent1;
  global $strSlipContent2;
  global $strSlipContent2;
  global $strSlipContent3;
  global $strSlipContent4;
  $tblDonation = new cHrdDonation();
  echo "
            <html>
            <head>
            <title>Formulir Disposisi</title>
            <meta http-equiv='Content-Type' content='application/vnd.ms-word; charset=iso-8859-1'>
            <meta http-equiv='Content-Disposition' content='attachment; charset=iso-8859-1'>
            <link href='../css/invosa.css' rel='stylesheet' type='text/css'>
            </head>
            <body  marginheight=0 marginwidth=0 leftmargin=30 topmargin=0 >
         ";
  // inisialisasi
  $strThisPage = "<p><p><p><p><p><p><span>&nbsp;</span>";
  $strNewPage = "<span style=\"page-break-before:always;\"></span>";
  $strSlipDate = date("d M Y");
  $bolEven = true; // apakah genap (untuk 2 slip per halaman)
  //$bolEven = false;
  $i = 0;
  foreach ($myDataGrid->checkboxes as $strDataID) {
    $strSlipContent1 = $strSlipContent2 = $strSlipContent3 = $strSlipContent4 = "";
    /* jalankan jika 1 halaman untuk 2 slip
    $bolEven = !$bolEven;
    */
    $i++;
    //assign header
    $dataDonation = $tblDonation->findAll(
        "id = '$strDataID'",
        "id_employee, form_code, event_date_from || ' to ' || event_date_thru AS event_date, donation_code, relation_name || ' - ' || relation_type AS relation, note, amount",
        "",
        null,
        1
    );
    $strSlipTopic = $dataDonationType[$dataDonation[0]['donation_code']]['name'];
    //ambil data approval => ambil nama karyawan
    $dataApproval = $tblDonation->findAll(
        "id = '$strDataID'",
        "checked_by, checked_time, approved_by, approved_time, now()::timestamp without time zone as printed_time, '" . $_SESSION['sessionUserID'] . "' as printed_by",
        "",
        null,
        1
    );
    if (count($dataApproval) > 0) {
      $dataApproval[0]['checked_by'] = ($dataApproval[0]['checked_by'] != "") ? getUserName(
              $db,
              $dataApproval[0]['checked_by']
          ) . " (" . $dataApproval[0]['checked_time'] . ")" : "";
      $dataApproval[0]['approved_by'] = ($dataApproval[0]['approved_by'] != "") ? getUserName(
              $db,
              $dataApproval[0]['approved_by']
          ) . " (" . $dataApproval[0]['approved_time'] . ")" : "";
      $dataApproval[0]['printed_by'] = ($dataApproval[0]['printed_by'] != "") ? getUserName(
              $db,
              $dataApproval[0]['printed_by']
          ) . " (" . $dataApproval[0]['printed_time'] . ")" : "";
      $dataApproval[0] = array_remove_key($dataApproval[0], "checked_time", "approved_time", "printed_time");
      //print_r($dataApproval[0]);
    }
    // ambil ID employee
    $strIDEmployee = $dataDonation[0]['id_employee'];
    $strFormCode = $dataDonation[0]['form_code'];
    $dataEmployee = getEmployeeInfoByID(
        $db,
        $strIDEmployee,
        "employee_id || ' - ' || employee_name AS employee_data, division_name as division, company_code, grade_code as grade, branch_code"
    );
    $dataEmployeeAccount = getEmployeeInfoByID(
        $db,
        $strIDEmployee,
        "bank2_account as bank_account, bank2_account_name as account_name, bank_name as bank"
    );
    foreach ($dataEmployee AS $strField => $strValue) {
      $strSlipContent1 .= wrapRow(getWords(str_replace("_", " ", $strField)), ":", $strValue);
    }
    foreach ($dataDonation[0] AS $strField => $strValue) {
      if ($strField != "id_employee" && $strField != "form_code") {
        if ($strField == "note") {
          $strSlipContent2 .= wrapRow(getWords(str_replace("_", " ", $strField)), ":", $strValue, false, false, false);
        } else if ($strField == "amount") {
          $strSlipContent2 .= wrapRow(
              getWords(str_replace("_", " ", $strField)),
              ":",
              standardFormat($strValue),
              false,
              false,
              false
          );
        } else {
          $strSlipContent2 .= wrapRow(getWords(str_replace("_", " ", $strField)), ":", $strValue);
        }
      }
    }
    foreach ($dataEmployeeAccount AS $strField => $strValue) {
      $strSlipContent3 .= wrapRow(getWords(str_replace("_", " ", $strField)), ":", $strValue);
    }
    foreach ($dataApproval[0] AS $strField => $strValue) {
      $strSlipContent4 .= wrapRow(getWords(str_replace("_", " ", $strField)), ":", $strValue);
    }
    if ($bolEven) // genap
    {
      echo $strThisPage;
    } else if ($i == 2) {
      echo $strThisPage;
    } else // ganjil, page berikutnya
    {
      echo $strNewPage;
      $i = 2;
    }
    $tbsPage = new clsTinyButStrong;
    $tbsPage->LoadTemplate("templates/donation_slip_template.html");
    $tbsPage->Show(TBS_OUTPUT);
  }
  // tampilkan footer HTML
  echo "

</body>
</html>

    ";
  unset($objEmp);
  exit();
}//getSlip
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  $strDataID = getPostValue('dataID');
  $isNew = ($strDataID == "");
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  $strEmpReadonly = (scopeCBDataEntry(&$strDataEmployee, $_SESSION['sessionUserRole'], $arrUserInfo)) ? "readonly" : "";
  //generate form untuk select trip type
  //trip type harus dipilih dulu supaya jenis2 trip allowance dapat ditentukan
  $f = new clsForm("formFilter", 3, "100%", "");
  $f->caption = strtoupper($strWordsFILTERDATA);
  $f->addInput(
      getWords("date from"),
      "dataDateFrom",
      date("Y-m") . "-01",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      getWords("date thru"),
      "dataDateThru",
      date("Y-m-d"),
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addSelect(
      getWords("donation type"),
      "dataDonationType",
      getDataListDonationType("", true, ["value" => "", "text" => "", "selected" => true]),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addInputAutoComplete(
      getWords("employee ID"),
      "dataEmployeeID",
      getDataEmployee($strDataEmployee),
      "style=width:$strDefaultWidthPx " . $strEmpReadonly,
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployeeID", "");
  $f->addSelect(
      getWords("request status"),
      "dataRequestStatus",
      getDataListRequestStatus("", true, ["value" => "", "text" => "", "selected" => true]),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("branch"),
      "dataBranch",
      getDataListBranch("", true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("level"),
      "dataPosition",
      getDataListPosition("", true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("grade"),
      "dataGrade",
      getDataListSalaryGrade("", true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("status"),
      "dataEmployeeStatus",
      getDataListEmployeeStatus("", true, ["value" => "", "text" => "", "selected" => true]),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("active"),
      "dataActive",
      getDataListEmployeeActive("", true, ["value" => "", "text" => "", "selected" => true]),
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
      getDataListDivision($strDataDivision, true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['division'] == "")
  );
  $f->addSelect(
      getWords("department "),
      "dataDepartment",
      getDataListDepartment($strDataDepartment, true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['department'] == "")
  );
  $f->addSelect(
      getWords("section"),
      "dataSection",
      getDataListSection($strDataSection, true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['section'] == "")
  );
  $f->addSelect(
      getWords("sub section"),
      "dataSubSection",
      getDataListSubSection($strDataSubSection, true),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false,
      ($ARRAY_DISABLE_GROUP['sub_section'] == "")
  );
  $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
  $formFilter = $f->render();
  getData($db);
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
?>