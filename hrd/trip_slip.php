<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../global/form_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_salary_grade.php');
include_once('../classes/hrd/hrd_trip_type.php');
include_once('../classes/hrd/hrd_trip_cost_type.php');
include_once('../classes/hrd/hrd_trip_type_cost_setting.php');
include_once('../classes/hrd/hrd_trip_cost_platform.php');
include_once('../classes/hrd/hrd_trip.php');
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
$strWordsTripAllowanceQuota = getWords("trip allowance quota");
$strWordsDataEntry = getWords("data entry");
$strWordsBusinessTripList = getWords("business trip list");
$strWordsBusinessTripReport = getWords("business trip report");
$strWordsDispositionForm = getWords("disposition form");
$strSlipContent = "";
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", true, true, true);
$DataGrid = "";
//INISIALISASI------------------------------------------------------------------------------------------------------------------
//ambil semua jenis trip
$tblTripType = new cHrdTripType();
$arrTripType = $tblTripType->findAll("", "id, trip_type_code, trip_type_name", "", null, 1, "id");
//ambil semua jenis trip cost untuk setiap currency
$tblTripCostType = new cHrdTripCostType();
foreach ($ARRAY_CURRENCY as $strCurrencyNo => $strCurrencyCode) {
  $arrTripCostType[$strCurrencyCode] = $tblTripCostType->findAll(
      "currency = '$strCurrencyCode'",
      "id, trip_cost_type_name, currency",
      "trip_cost_type_name",
      null,
      1,
      "id"
  );
}
//ambil setting cost untuk trip sesuai dengan trip type yang dipilih
$tblTripTypeCostSetting = new cHrdTripTypeCostSetting();
foreach ($arrTripType AS $strTripID => $arrTripDetail) {
  $arrTripCostSetting = $tblTripTypeCostSetting->findAll(
      "id_trip_type = $strTripID",
      "id_trip_cost_type, include",
      "",
      null,
      1,
      "id_trip_cost_type"
  );
  foreach ($ARRAY_CURRENCY AS $strCurrencyNo => $strCurrencyCode) {
    $arrTripCost[$strTripID][$strCurrencyCode] = [];
    foreach ($arrTripCostType[$strCurrencyCode] AS $strCostID => $arrCostDetail) {
      if (isset($arrTripCostSetting[$strCostID]) && $arrTripCostSetting[$strCostID]['include'] == 't') {
        $arrTripCost[$strTripID][$strCurrencyCode][] = $strCostID;
      }
    }
  }
}
//DAFTAR FUNGSI--------------------------------------------------------------------------------------------------------------
function getData($db)
{
  global $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge;
  global $strPageTitle;
  global $f;
  global $arrTripCost;
  global $arrTripCostType;
  global $ARRAY_CURRENCY;
  global $myDataGrid;
  global $DataGrid;
  global $strKriteriaCompany;
  //global $arrUserInfo;
  $strDataTripTypeID = $f->getValue('dataTripType');
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if (validStandardDate($arrData['dataDateFrom']) && validStandardDate($arrData['dataDateThru'])) {
    $strKriteria .= "AND t1.created::date BETWEEN '" . $arrData['dataDateFrom'] . "' AND '" . $arrData['dataDateThru'] . "' ";
  }
  if ($strDataTripTypeID != "") {
    $strKriteria .= "AND id_trip_type = '" . $strDataTripTypeID . "' ";
  }
  if ($arrData['dataEmployeeID'] != "") {
    $strKriteria .= "AND employee_id = '" . $arrData['dataEmployeeID'] . "' ";
  }
  if ($arrData['dataPosition'] != "") {
    $strKriteria .= "AND position_code = '" . $arrData['dataPosition'] . "' ";
  }
  if ($arrData['dataBranch'] != "") {
    $strKriteria .= "AND t2.branch_code = '" . $arrData['dataBranch'] . "' ";
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
  if ($arrData['dataRequestStatus'] != "") {
    $strKriteria .= "AND t1.status = '" . $arrData['dataRequestStatus'] . "' ";
  }
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND t2.division_code = '" . $arrData['dataDivision'] . "' ";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND t2.department_code = '" . $arrData['dataDepartment'] . "' ";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND section_code = '" . $arrData['dataSection'] . "' ";
  }
  if ($arrData['dataSubSection'] != "") {
    $strKriteria .= "AND sub_section_code = '" . $arrData['dataSubSection'] . "' ";
  }
  if ($arrData['dataDestination'] != "") {
    $strKriteria .= "AND destination = '" . $arrData['dataDestination'] . "' ";
  }
  $strKriteria .= $strKriteriaCompany;
  if ($db->connect() && $strDataTripTypeID != "") {
    $myDataGrid->caption = getWords($strPageTitle);
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->pageSortBy = "created desc";
    $myDataGrid->setCriteria($strKriteria);
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID", "id", ["rowspan" => 2, 'width' => '30'], ['align' => 'center', 'nowrap' => '']),
        false /*bolDisableSelfStatusChange*/
    );
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ["rowspan" => 2, 'width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("form code"), "form_code", ["rowspan" => 2], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("created"), "created", ["rowspan" => 2], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date from"), "date_from", ["rowspan" => 2], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date thru"), "date_thru", ["rowspan" => 2], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("employee id"), "employee_id", ["rowspan" => 2], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("employee name"), "employee_name", ["rowspan" => 2], ['nowrap' => ''])
    );
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("division"), "division_name", array("rowspan" => 2), array('nowrap' => '')));
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("department"), "department_name", array("rowspan" => 2), array('nowrap' => '')));
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("location"), "branch", array("rowspan" => 2,'width' => '200'), array('nowrap' => '')));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("destination"), "destination", ["rowspan" => 2], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("purpose"), "purpose", ["rowspan" => 2], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("task detail"), "task", ["rowspan" => 2, 'width' => '250'], "")
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", ["rowspan" => 2], ""));
    // tampilkan cost dalam setiap currency jika ada
    foreach ($ARRAY_CURRENCY as $strCurrencyNo => $strCurrencyCode) {
      if (count($arrTripCost[$strDataTripTypeID][$strCurrencyCode]) > 0) {
        //$myDataGrid->addSpannedColumn(getWords("Trip Cost ").$strCurrencyCode, count($arrTripCost[$strDataTripTypeID][$strCurrencyCode]));
        if (count($arrTripCost[$strDataTripTypeID][$strCurrencyCode]) > 1) {
          $myDataGrid->addSpannedColumn(
              getWords("Trip Cost ") . $strCurrencyCode,
              count($arrTripCost[$strDataTripTypeID][$strCurrencyCode])
          );
        }
        foreach ($arrTripCost[$strDataTripTypeID][$strCurrencyCode] AS $strCostID) {
          //$myDataGrid->addColumn(new DataGrid_Column(getWords($arrTripCostType[$strCurrencyCode][$strCostID]['trip_cost_type_name'])." ".$strCurrencyCode, "trip_cost_".$strCostID, array('width' => '75'), array('align' => 'right'), false, false, "", "formatNumber()"));
          if (count($arrTripCost[$strDataTripTypeID][$strCurrencyCode]) > 1) {
            $myDataGrid->addColumn(
                new DataGrid_Column(
                    getWords(
                        $arrTripCostType[$strCurrencyCode][$strCostID]['trip_cost_type_name']
                    ) . " " . $strCurrencyCode,
                    "trip_cost_" . $strCostID,
                    ['width' => '75'],
                    ['align' => 'right'],
                    false,
                    false,
                    "",
                    "formatNumber()",
                    "numeric",
                    true,
                    15
                )
            );
          } else {
            $myDataGrid->addColumn(
                new DataGrid_Column(
                    getWords(
                        "Trip Cost " . $arrTripCostType[$strCurrencyCode][$strCostID]['trip_cost_type_name']
                    ) . " " . $strCurrencyCode,
                    "trip_cost_" . $strCostID,
                    ['width' => '75'],
                    ['align' => 'right'],
                    false,
                    false,
                    "",
                    "formatNumber()",
                    "numeric",
                    true,
                    15
                )
            );
          }
        }
        $myDataGrid->addColumn(
            new DataGrid_Column(
                getWords("total"),
                "total_cost_" . $strCurrencyCode,
                ["rowspan" => 2, 'width' => '75'],
                ['align' => 'right'],
                false,
                false,
                "",
                "formatNumber()"
            )
        );
      }
    }
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("status"), "status", ["rowspan" => 2], "", false, false, "", "printRequestStatus()"
        )
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
    if ($myDataGrid->sortName == "division_name") {
      $myDataGrid->sortName = "t2.division_code,department_name";
    }
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQL = "SELECT t1.*, employee_id, employee_name, division_name, department_name, t2.branch_code || ' - ' || branch_name as branch ,t2.division_code as division
                       FROM hrd_trip AS t1
                       LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id
                       LEFT JOIN hrd_branch  AS t3 ON t2.branch_code = t3.branch_code
                       LEFT JOIN hrd_division  AS t4 ON t2.division_code = t4.division_code
                       LEFT JOIN hrd_department  AS t5 ON t2.department_code = t5.department_code";
    $strSQLCOUNT = "SELECT count(*) FROM hrd_trip as t1
                       LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id";
    $tblTripDetail = new cHrdTripDetail();
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
    $dataset = $myDataGrid->getData($db, $strSQL);
    foreach ($dataset AS $strKey => $arrDetail) {
      $arrTripDetail = $tblTripDetail->findAll(
          "id_trip = " . $arrDetail['id'],
          "id_trip_cost_type, amount",
          "",
          null,
          1,
          "id_trip_cost_type"
      );
      foreach ($ARRAY_CURRENCY AS $strCurrencyNo => $strCurrencyCode) {
        $intTotal = 0;
        foreach ($arrTripCostType[$strCurrencyCode] AS $strCostID => $arrCostDetail) {
          $intAmount = (isset($arrTripDetail[$strCostID])) ? $arrTripDetail[$strCostID]['amount'] : 0;
          $intTotal += $intAmount;
          $dataset[$strKey]['trip_cost_' . $strCostID] = $intAmount;
        }
        $dataset[$strKey]['total_cost_' . $strCurrencyCode] = $intTotal;
      }
    }
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

// fungsi untuk menyimpan data
function getSlip()
{
  global $ARRAY_CURRENCY;
  global $myDataGrid;
  global $db;
  global $arrTripType;
  global $strDataTripTypeID;
  global $strSlipContent;
  global $strSlipTopic;
  global $strSlipDate;
  global $strFormCode;
  global $strSlipContent1a, $strSlipContent1b, $strSlipContent2a, $strSlipContent2b, $strSlipContent2c, $strSlipContent3, $strSlipContent4a, $strSlipContent4b, $strSlipContent5a, $strSlipContent5b;
  $tblTrip = new cHrdTrip();
  $tblTripDetail = new cHrdTripDetail();
  $tblTripCostType = new cHrdTripCostType();
  echo "
            <html>
            <head>
            <title>Formulir Disposisi</title>
            <meta http-equiv='Content-Type' content='application/vnd.ms-word; charset=iso-8859-1'>
            <meta http-equiv='Content-Disposition' content='attachment; charset=iso-8859-1'>
            <link href='../css/invosa.css' rel='stylesheet' type='text/css'>
            </head>
            <body  marginheight=0 marginwidth=0 leftmargin=10 topmargin=0 >
         ";
  // inisialisasi
  $strThisPage = "<span>&nbsp;<br><br><br><br></span>";
  $strNewPage = "<span style=\"page-break-before:always;\"></span>";
  $strSlipTopic = $arrTripType[$strDataTripTypeID]['trip_type_name'];
  $strSlipDate = date("d M Y");
  $bolEven = true; // apakah genap (untuk 2 slip per halaman)
  //$bolEven = false;
  $i = 0;
  foreach ($myDataGrid->checkboxes as $strDataID) {
    $strSlipContent1a = $strSlipContent1b = $strSlipContent2a = $strSlipContent2b = $strSlipContent2c = $strSlipContent3 = $strSlipContent4a = $strSlipContent4b = $strSlipContent5a = $strSlipContent5b = "";
    //jalankan jika 1 halaman untuk 2 slip
    //$bolEven = !$bolEven;
    $i++;
    //assign header
    $dataTrip = $tblTrip->findAll("id = '$strDataID'", "id_employee, form_code", "", null, 1);
    $strFormCode = $dataTrip[0]['form_code'];
    $dataTrip1 = $tblTrip->findAll(
        "id = '$strDataID'",
        "date_from || ' until ' || date_thru AS trip_date",
        "",
        null,
        1
    );
    $dataTrip2 = $tblTrip->findAll("id = '$strDataID'", "destination", "", null, 1);
    $dataTrip3 = $tblTrip->findAll("id = '$strDataID'", "purpose, note", "", null, 1);
    //ambil data approval => ambil nama karyawan
    $dataApproval1 = $tblTrip->findAll(
        "id = '$strDataID'",
        "created_by, created, now()::timestamp without time zone as printed_time, '" . $_SESSION['sessionUserID'] . "' as printed_by",
        "",
        null,
        1
    );
    if (count($dataApproval1) > 0) {
      $dataApproval1[0]['created_by'] = ($dataApproval1[0]['created_by'] != "") ? getUserName(
              $db,
              $dataApproval1[0]['created_by']
          ) . " (" . substr($dataApproval1[0]['created'], 0, 16) . ")" : "";
      $dataApproval1[0]['printed_by'] = ($dataApproval1[0]['printed_by'] != "") ? getUserName(
              $db,
              $dataApproval1[0]['printed_by']
          ) . " (" . substr($dataApproval1[0]['printed_time'], 0, 16) . ")" : "";
      $dataApproval1[0] = array_remove_key($dataApproval1[0], "created", "printed_time");
    }
    //ambil data approval => ambil nama karyawan
    //$dataApproval2 = $tblTrip->findAll("id = '$strDataID'", "now()::timestamp without time zone as printed_time, '".$_SESSION['sessionUserID']."' as printed_by", "", null, 1);
    $dataApproval2 = $tblTrip->findAll(
        "id = '$strDataID'",
        "checked_by, checked_time, approved_by, approved_time",
        "",
        null,
        1
    );
    if (count($dataApproval2) > 0) {
      $dataApproval2[0]['approved_by'] = ($dataApproval2[0]['approved_by'] != "") ? getUserName(
              $db,
              $dataApproval2[0]['approved_by']
          ) . " (" . substr($dataApproval2[0]['approved_time'], 0, 16) . ")" : "";
      $dataApproval2[0]['checked_by'] = ($dataApproval2[0]['checked_by'] != "") ? getUserName(
              $db,
              $dataApproval2[0]['checked_by']
          ) . " (" . substr($dataApproval2[0]['checked_time'], 0, 16) . ")" : "";
      $dataApproval2[0] = array_remove_key($dataApproval2[0], "checked_time", "approved_time");
      //print_r($dataApproval[0]);
    }
    foreach ($ARRAY_CURRENCY AS $strCurrencyID => $strCurrencyCode) {
      $dataTripDetail[$strCurrencyCode] = $tblTripDetail->findAll(
          "id_trip = '$strDataID' AND currency = '$strCurrencyCode'",
          "trip_cost_type, amount, currency"
      );
    }
    // ambil ID employee
    $strIDEmployee = $dataTrip[0]['id_employee'];
    $dataEmployee1 = getEmployeeInfoByID(
        $db,
        $strIDEmployee,
        "employee_id ||' - '|| employee_name as employee, grade_code as grade"
    );
    $dataEmployee2 = getEmployeeInfoByID($db, $strIDEmployee, "t0.division_code as division");
    $dataEmployeeAccount1 = getEmployeeInfoByID(
        $db,
        $strIDEmployee,
        "bank2_account as bank_account, bank2_account_name as account_name"
    );
    $dataEmployeeAccount2 = getEmployeeInfoByID($db, $strIDEmployee, "bank_name as bank");
    foreach ($dataEmployee1 AS $strField => $strValue) {
      $strSlipContent1a .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "80", true);
    }
    foreach ($dataEmployee2 AS $strField => $strValue) {
      $strSlipContent1b .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "80", true);
    }
    foreach ($dataTrip1[0] AS $strField => $strValue) {
      $strSlipContent2a .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "80", true);
    }
    foreach ($dataTrip2[0] AS $strField => $strValue) {
      $strSlipContent2b .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "80", true);
    }
    foreach ($dataTrip3[0] AS $strField => $strValue) {
      $strSlipContent2c .= "<tr><td width=80 valign=top>" . ucWords(
              $strField
          ) . "</td><td valign=top>:</td><td colspan=4 height=30 valign=top>" . $strValue . "</td></tr>";
    }
    foreach ($dataTripDetail AS $strCurrencyCode => $arrCost) {
      if (count($arrCost) > 0) {
        $intTotal = 0;
        foreach ($arrCost AS $index => $arrValue) {
          $strSlipContent3 .= wrapRowSize(
              getWords($arrValue['trip_cost_type']),
              ": (" . $strCurrencyCode . ")",
              standardFormat($arrValue['amount']),
              "80",
              true,
              true
          );
          $intTotal += $arrValue['amount'];
        }
        $strSlipContent3 .= wrapRow(getWords("total"), ": (" . $strCurrencyCode . ")", standardFormat($intTotal), true);
      }
    }
    foreach ($dataEmployeeAccount1 AS $strField => $strValue) {
      $strSlipContent4a .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "85");
    }
    foreach ($dataEmployeeAccount2 AS $strField => $strValue) {
      $strSlipContent4b .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "85", true);
    }
    foreach ($dataApproval1[0] AS $strField => $strValue) {
      $strSlipContent5a .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "85");
    }
    foreach ($dataApproval2[0] AS $strField => $strValue) {
      $strSlipContent5b .= wrapRowSize(getWords(str_replace("_", " ", $strField)), ":", $strValue, "85");
    }
    //print_r ($strValue);
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
    //echo $strThisPage;
    $tbsPage = new clsTinyButStrong;
    $tbsPage->LoadTemplate("templates/trip_slip_template.html");
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
  $strDataTripTypeID = getPostValue('dataTripType');
  if ($strDataTripTypeID == "") {
    $arrID = $tblTripType->find("", "id", "id", null, 1, "id");
    $strDataTripTypeID = $arrID['id'];
  }
  $strPageTitle = $dataPrivilege['menu_name'];
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
  $f->caption = strtoupper("trip type");
  $f->addSelect(
      getWords("trip type"),
      "dataTripType",
      getDataListTripType($strDataTripTypeID),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
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
  $f->addSelect(
      getWords("destination"),
      "dataDestination",
      getDataListDestination("", true, ["value" => "", "text" => "", "selected" => true]),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
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
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
?>
