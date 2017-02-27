<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/employee_function.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_employee.php');
include_once('../classes/hrd/hrd_medical_quota_primary.php');
include_once('../classes/hrd/hrd_medical_quota_secondary.php');
$dataPrivilege = getDataPrivileges(
    "medical_quota.php",
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
$db = new CdbClass;
$strWordsMedicalData = getWords("medical data");
$strWordsEmployeeQuotaList = getWords("employee quota list");
$strWordsInputMedicalClaim = getWords("input claim");
$strWordsMedicalClaimList = getWords("claim list");
$strWordsEmployeeMedicalReport = getWords("employee medical report");
//----MAIN PROGRAM -----------------------------------------------------
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  $strYear = (getPostValue('dataYear') == "") ? date("Y") : getPostValue('dataYear');
  $strDataCompany = (getPostValue('dataCompany') == "") ? 1 : getPostValue('dataCompany');
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  $strReadonly = (scopeCBDataEntry($strDataEmployee, $_SESSION['sessionUserRole'], $arrUserInfo)) ? "readonly" : "";
  $f = new clsForm("formFilter", 3, "1024px", "");
  $f->caption = strtoupper("filter data");
  $f->addSelect(
      getWords("year"),
      "dataYear",
      getDataListYear($strYear),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addInputAutoComplete(
      getWords("employee ID"),
      "dataEmployeeID",
      getDataEmployee($strDataEmployee),
      "style=width:$strDefaultWidthPx " . $strReadonly,
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployeeID", "");
  $f->addLiteral("", "", "");
  $f->addLiteral("", "", "");
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
      getWords("department"),
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
  getData($db, $strDataCompany, $strYear);
}
function getData($db, $strDataCompany, $strYear)
{
  global $dataPrivilege, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge;
  global $f;
  global $myDataGrid;
  global $DataGrid;
  global $strKriteriaCompany;
  global $ARRAY_MEDICAL_TREATMENT_GROUP;
  // cari data master medical type untuk generate header tabel plafon
  $strSQL = "SELECT * FROM hrd_medical_type";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrMedGroup[$rowDb['type']][$rowDb['id']] = $rowDb;
  }
  $formFilter = $f->render();
  $strKriteria = "";
  $strFormatter = (isset($_POST['btnExportXLS'])) ? "" : "formatNumber()";
  // GENERATE CRITERIA
  $arrData = $f->getObjectValues();
  if ($arrData['dataEmployeeID'] != "") {
    $strKriteria .= "AND employee_id = '" . $arrData['dataEmployeeID'] . "' ";
  }
  if ($arrData['dataPosition'] != "") {
    $strKriteria .= "AND position_code = '" . $arrData['dataPosition'] . "' ";
  }
  if ($arrData['dataBranch'] != "") {
    $strKriteria .= "AND branch_code = '" . $arrData['dataBranch'] . "' ";
  }
  if ($arrData['dataGrade'] != "") {
    $strKriteria .= "AND t0.grade_code = '" . $arrData['dataGrade'] . "' ";
  }
  if ($arrData['dataEmployeeStatus'] != "") {
    $strKriteria .= "AND t0.employee_status = '" . $arrData['dataEmployeeStatus'] . "' ";
  }
  if ($arrData['dataActive'] != "") {
    $strKriteria .= "AND t0.active = '" . $arrData['dataActive'] . "' ";
  }
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND division_code = '" . $arrData['dataDivision'] . "' ";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND department_code = '" . $arrData['dataDepartment'] . "' ";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND section_code = '" . $arrData['dataSection'] . "' ";
  }
  if ($arrData['dataSubSection'] != "") {
    $strKriteria .= "AND sub_section_code = '" . $arrData['dataSubSection'] . "' ";
  }
  $strKriteria .= $strKriteriaCompany;
  $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, false, false);
  $myDataGrid->caption = getWords(strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name']))));
  $myDataGrid->pageSortBy = "employee_name";
  //$myDataGrid->setCriteria($strKriteria);
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  $myDataGrid->setPageLimit("all");
  if (!isset($_REQUEST['btnExportXLS'])) {
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column(
            "chkID",
            "id_employee",
            ["rowspan" => 2, 'width' => 30],
            ['align' => 'center', 'nowrap' => 'nowrap']
        )
    );
  }
  $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['rowspan' => '2', 'width' => '30'], ['nowrap' => '']));
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("employee id"), "employee_id", ['nowrap' => '', 'rowspan' => '2',], ['nowrap' => ''])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("employee name"),
          "employee_name",
          ['nowrap' => '', 'rowspan' => '2', 'style' => '250'],
          ['nowrap' => '']
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("grade"), "grade_code", ['rowspan' => '2', 'width' => '25'], ['nowrap' => ''])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("family status"),
          "family_status",
          ['rowspan' => '2', 'width' => '25'],
          ['nowrap' => '']
      )
  );
  //$myDataGrid->addColumn(new DataGrid_Column(getWords("department"), "department_code", array('rowspan' => '2', 'width' => '75'), array('align' => 'center'), true, true, "", "getDepartmentName()", "", true));
  $myDataGrid->addSpannedColumn(getWords("outpatient"), 6);
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("main quota"),
          "amount",
          [],
          ['align' => 'center'],
          true,
          true,
          "",
          ($strFormatter),
          "",
          true
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("add. quota"),
          "amount1",
          [],
          ['align' => 'center'],
          true,
          true,
          "",
          ($strFormatter),
          "",
          true
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("add. quota"),
          "amount2",
          [],
          ['align' => 'center'],
          true,
          true,
          "",
          ($strFormatter),
          "",
          true
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("total quota"),
          "total_quota",
          [],
          ['align' => 'center'],
          true,
          true,
          "",
          ($strFormatter),
          "",
          true
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("claimed"),
          "main_claimed",
          [],
          ['align' => 'center'],
          true,
          true,
          "",
          ($strFormatter),
          "",
          true
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("remaining"),
          "main_remaining",
          [],
          ['align' => 'center'],
          true,
          true,
          "",
          ($strFormatter),
          "",
          true
      )
  );
  foreach ($arrMedGroup AS $idMedGroup => $arrMedType) {
    foreach ($arrMedType AS $idMedType => $arrMedDetail) {
      $myDataGrid->addSpannedColumn(
          getWords($ARRAY_MEDICAL_TREATMENT_GROUP[$idMedGroup]) . "-" . getWords($arrMedDetail['code']),
          3
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("quota"),
              "quota_" . $idMedType,
              [],
              ['align' => 'center'],
              true,
              true,
              "",
              ($strFormatter),
              "",
              true
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("claimed"),
              "claimed_" . $idMedType,
              [],
              ['align' => 'center'],
              true,
              true,
              "",
              ($strFormatter),
              "",
              true
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("remaining"),
              "remaining_" . $idMedType,
              [],
              ['align' => 'center'],
              true,
              true,
              "",
              ($strFormatter),
              "",
              true
          )
      );
    }
  }
  foreach ($arrData AS $key => $value) {
    $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
  }
  if ($bolCanEdit) {
    $myDataGrid->addSpecialButton(
        "btnSave",
        "btnSave",
        "submit",
        getWords("calculate"),
        "onClick=\"javascript:return myClient.confirmSave();\"",
        "saveData()"
    );
  }
  $myDataGrid->addButtonExportExcel(
      getWords("export excel"),
      $dataPrivilege['menu_name'] . ".xls",
      getWords($dataPrivilege['menu_name'])
  );
  $myDataGrid->getRequest();
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  $strSQL = "SELECT t0.id as id_employee, t0.employee_id, t0.id_company, t0.employee_name, t0.gender,
                t1.inspouse, t1.grade_code, t1.employee_status, t1.family_status, 
                t1.amount, t1.amount1, t1.amount2, t1.amount + t1.amount1 + t1.amount2 AS total_quota, t1.note,
                SUM(t4.approved_cost) as main_claimed
                FROM hrd_employee AS t0 
                LEFT JOIN (SELECT * FROM hrd_medical_quota_primary WHERE quota_year = $strYear) AS t1 ON t0.id = t1.id_employee
                LEFT JOIN 
                (SELECT id_employee, id_medical_type, approved_cost, claim_date, status 
                FROM hrd_medical_claim AS t2 
                LEFT JOIN hrd_medical_claim_master AS t3 ON t2.id_master = t3.id
                WHERE status <> " . REQUEST_STATUS_NEW . " AND status <> " . REQUEST_STATUS_DENIED . "
                AND EXTRACT(year FROM claim_date) = '$strYear' AND id_medical_type = -1)
                AS t4 ON t4.id_employee = t0.id  
                WHERE id_company = $strDataCompany  $strKriteria 
                GROUP BY  t0.id , t0.employee_id, t0.id_company, t0.employee_name, t0.gender, 
                t1.inspouse, t1.grade_code, t1.employee_status, t1.family_status, 
                t1.amount, t1.amount1, t1.amount2, total_quota, t1.note
                ";
  $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_employee AS t0
                LEFT JOIN (SELECT * FROM hrd_medical_quota_primary WHERE quota_year = $strYear) AS t1 ON t1.id = t1.id_employee
                WHERE id_company = $strDataCompany  $strKriteria 
                ";
  $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
  $dataset = $myDataGrid->getData($db, $strSQL);
  $strSQL = "SELECT t0.id AS id_employee, t1.id_medical_type, t1.amount, SUM(t4.approved_cost) as claimed
                FROM hrd_employee AS t0
                LEFT JOIN 
                (SELECT id_employee, id_medical_type, amount FROM hrd_medical_quota_secondary WHERE quota_year = $strYear) AS t1 ON t0.id = t1.id_employee
                LEFT JOIN 
                (SELECT id_employee, id_medical_type, approved_cost, claim_date, status 
                FROM hrd_medical_claim AS t2 
                LEFT JOIN hrd_medical_claim_master AS t3 ON t2.id_master = t3.id
                WHERE status <> " . REQUEST_STATUS_NEW . " AND status <> " . REQUEST_STATUS_DENIED . " AND EXTRACT(year FROM claim_date) = '$strYear' )
                AS t4 ON t4.id_employee = t0.id AND t4.id_medical_type = t1.id_medical_type 
                WHERE id_company = $strDataCompany  $strKriteria 
                GROUP BY t0.id, t1.id_medical_type, t1.amount";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrQuota2[$rowDb['id_employee']][$rowDb['id_medical_type']] = [
        "amount" => $rowDb['amount'],
        "claimed" => $rowDb['claimed']
    ];
  }
  foreach ($dataset AS $index => $rowDb) {
    $dataset[$index]['main_remaining'] = $rowDb['total_quota'] - $rowDb['main_claimed'];
    foreach ($arrQuota2[$rowDb['id_employee']] AS $idMedType => $arrDetail) {
      $dataset[$index]['quota_' . $idMedType] = $arrDetail['amount'];
      $dataset[$index]['claimed_' . $idMedType] = $arrDetail['claimed'];
      $dataset[$index]['remaining_' . $idMedType] = $arrDetail['amount'] - $arrDetail['claimed'];
    }
  }
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
}

// fungsi untuk menyimpan data
function saveData()
{
  global $myDataGrid;
  global $error;
  global $strKriteriaCompany;
  $strError = "";
  $strKriteria = "";
  $bolSuccess = true;
  $strModifiedByID = $_SESSION['sessionUserID'];
  $tblMedicalQuotaPrimary = new cHrdMedicalQuotaPrimary();
  $tblMedicalQuotaSecondary = new cHrdMedicalQuotaSecondary();
  $arrData = $_POST;
  $strYear = $arrData['dataYear'];
  $strDataCompany = $arrData['dataCompany'];
  if ($arrData['dataEmployeeID'] != "") {
    $strKriteria .= "AND employee_id = '" . $arrData['dataEmployeeID'] . "' ";
  }
  if ($arrData['dataPosition'] != "") {
    $strKriteria .= "AND t1.position_code = '" . $arrData['dataPosition'] . "' ";
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
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND division_code = '" . $arrData['dataDivision'] . "' ";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND department_code = '" . $arrData['dataDepartment'] . "' ";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND section_code = '" . $arrData['dataSection'] . "' ";
  }
  if ($arrData['dataSubSection'] != "") {
    $strKriteria .= "AND sub_section_code = '" . $arrData['dataSubSection'] . "' ";
  }
  $strKriteria .= $strKriteriaCompany;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrEmployee[] = $strValue;
  }
  $db = new CdbClass;
  if ($db->connect()) {
    // cari data plafon outpatient
    $arrPlatform = [];
    $strSQL = "SELECT * FROM hrd_medical_platform_primary";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrPlatform[$rowDb['family_status_code']][$rowDb['gender']][$rowDb['inspouse']] = $rowDb['amount'];
    }
    // cari data plafon outpatient tambahan
    $arrAddPlatform = [];
    $strSQL = "SELECT id_employee, amount1, amount2, note FROM hrd_medical_quota_primary WHERE quota_year = $strYear;";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrAddPlatform[$rowDb['id_employee']] = $rowDb;
    }
    // cari data master medical type untuk generate header tabel plafon
    $strSQL = "SELECT * FROM hrd_medical_type";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrMedType[$rowDb['id']] = $rowDb;
    }
    // cari data plafon lainnya
    $strSQL = "SELECT * FROM hrd_medical_platform_secondary";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrPlatformSecondary[$rowDb['id_medical_type']][$rowDb['grade_code']] = $rowDb['amount'];
    }
    $strSQL = "SELECT t1.id as id_employee, employee_id, employee_name, gender, inspouse,
                  medical_quota_status, t1.grade_code, employee_status, join_date, resign_date, due_date, 
                  EXTRACT(year FROM join_date) AS join_year, EXTRACT(year FROM due_date) AS due_year, EXTRACT(year FROM resign_date) AS resign_year, 
                  CASE WHEN (position_group = '" . POSITION_EXECUTIVE . "') THEN 10000000 ELSE (CASE WHEN (basic_salary < 1500000) THEN 1500000 ELSE (CASE WHEN (basic_salary > 9000000) THEN 9000000 ELSE basic_salary END) END) END AS basic_salary
                  FROM 
                  hrd_employee AS t1 LEFT JOIN 
                  (SELECT id_employee, basic_salary FROM hrd_employee_basic_salary WHERE id_salary_set = (SELECT id FROM hrd_basic_salary_set WHERE id_company = $strDataCompany AND EXTRACT(year FROM start_date) = '$strYear' ORDER BY start_date DESC LIMIT 1)) AS t2 ON t1.id = t2.id_employee 
                  LEFT JOIN hrd_position AS t3 ON t1.position_code = t3.position_code
                  WHERE 1=1 $strKriteria";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrParam[$rowDb['id_employee']] = $rowDb;
    }
    foreach ($arrEmployee AS $strIDEmployee) {
      $rowDb = $arrParam[$strIDEmployee];
      $strMedicalQuotaStatus = $rowDb['medical_quota_status'];
      $strGender = $rowDb['gender'];
      $strInspouse = $rowDb['inspouse'];
      $strGrade = $rowDb['grade_code'];
      $strEmployeeStatus = $rowDb['employee_status'];
      $fltBasicSalary = $rowDb['basic_salary'];
      $varMin = 0;
      //Sesuaikan dengan tahun berjalan, jika karyawan baru prorata
      if ($rowDb['resign_year'] < $strYear && validStandardDate($rowDb['resign_date'])) {
        $varMin = 12;
      } else {
        if ($rowDb['join_year'] == $strYear) {
          //$varMin += $arrJoin['month'] - 1;
          //$varMin += ($arrJoin['day'] - 1)/lastDay($arrJoin['month'], $arrJoin['year']);
          $strStartDate = $rowDb['join_date'];
        } else if ($rowDb['join_year'] < $strYear) {
          $strStartDate = date("Y") . "-01-01";
        }
        if (isset($rowDb['due_year']) && $rowDb['due_year'] != "" && $rowDb['due_year'] == $strYear && $rowDb['employee_status'] != 1) {
          //$varMin += 12 - $arrDue['month'];
          //$varMin += (lastday($arrDue['month'], $arrDue['year']) - $arrDue['day'])/lastday($arrDue['month'], $arrDue['year']);
          $strEndDate = $rowDb['due_date'];
        } else if (isset($rowDb['resign_year']) && $rowDb['resign_year'] != "" && $rowDb['resign_year'] == $strYear && $rowDb['employee_status'] == 1) {
          //$varMin += 12 - $arrDue['month'];
          //$varMin += (lastday($arrDue['month'], $arrDue['year']) - $arrDue['day'])/lastday($arrDue['month'], $arrDue['year']);
          $strEndDate = $rowDb['resign_date'];
        } else {
          $strEndDate = date("Y") . "-12-31";
        }
      }
      //$varMin = (12 - $varMin)/12;
      $varMin = (getIntervalDate($strStartDate, $strEndDate) + 1) / (getIntervalDate(
                  date("Y") . "-01-01",
                  date("Y") . "-12-31"
              ) + 1);
      $intPlatform = (isset($arrPlatform[$strMedicalQuotaStatus][$strGender][$strInspouse])) ? $arrPlatform[$strMedicalQuotaStatus][$strGender][$strInspouse] : 0;
      $data = [];
      $data['id_employee'] = $strIDEmployee;
      $data['family_status'] = $strMedicalQuotaStatus;
      $data['grade_code'] = $strGrade;
      $data['inspouse'] = $strInspouse;
      $data['employee_status'] = $strEmployeeStatus;
      $data['basic_salary'] = $fltBasicSalary;
      $data['quota_year'] = $strYear;
      $data['amount'] = $varMin * $intPlatform / 100 * $fltBasicSalary;
      $data['amount1'] = (isset($arrAddPlatform[$strIDEmployee]['amount1'])) ? $arrAddPlatform[$strIDEmployee]['amount1'] : 0;
      $data['amount2'] = (isset($arrAddPlatform[$strIDEmployee]['amount2'])) ? $arrAddPlatform[$strIDEmployee]['amount2'] : 0;
      $data['note'] = (isset($arrAddPlatform[$strIDEmployee]['note'])) ? $arrAddPlatform[$strIDEmployee]['note'] : "";
      $tblMedicalQuotaPrimary->delete(["id_employee" => $strIDEmployee, "quota_year" => $strYear]);
      $tblMedicalQuotaPrimary->insert($data);
      foreach ($arrMedType AS $idType => $arrDetail) {
        //cek permanent_only, apakah hanya untuk karyawan tetap
        if ($arrDetail['permanent_only'] != "t" || $strEmployeeStatus == STATUS_PERMANENT) {
          $intPlatform = (isset($arrPlatformSecondary[$idType][$strGrade])) ? $arrPlatformSecondary[$idType][$strGrade] : 0;
        } else {
          $intPlatform = 0;
        }
        //cek apakah perlu di prorata
        if ($arrDetail['prorate'] == "t") {
          $intPlatform *= $varMin;
        }
        $data = [];
        $data['id_employee'] = $strIDEmployee;
        $data['id_medical_type'] = $idType;
        $data['quota_year'] = $strYear;
        $data['amount'] = $intPlatform;
        $tblMedicalQuotaSecondary->delete(
            ["id_employee" => $strIDEmployee, "id_medical_type" => $idType, "quota_year" => $strYear]
        );
        $tblMedicalQuotaSecondary->insert($data);
      }
    }
  }
  if ($bolSuccess) {
    $myDataGrid->message = $tblMedicalQuotaPrimary->strMessage;
  } else {
    $myDataGrid->errorMessage = $strError;
  }
} // saveData
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