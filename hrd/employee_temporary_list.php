<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_employee.php');
include_once('../classes/hrd/hrd_employee_temporary.php');
$dataPrivilege = getDataPrivileges(
    "employee_temporary_edit.php",
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
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintAll']) || isset($_REQUEST['btnExportXLS']) || isset($_REQUEST['btnExcelAll']));
$strWordsDataEntry = getWords("data entry");
$strWordsEmployeeTemporaryDataList = getWords("employee temporary data list");
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
$DataGrid = "";
//INISIALISASI------------------------------------------------------------------------------------------------------------------
//DAFTAR FUNGSI--------------------------------------------------------------------------------------------------------------
function getData($db)
{
  global $bolCanEdit, $bolPrint, $bolCanCheck, $bolCanDelete, $bolCanApprove;
  global $strPageTitle;
  global $f;
  global $datasetMaster;
  //global $arrTripCost;
  //global $arrTripCostType;
  global $myDataGrid;
  global $DataGrid;
  global $strKriteriaCompany;
  global $arrUserInfo;
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if ($arrData['dataEmployeeID'] != "") {
    $strKriteria .= "AND t1.employee_id = '" . $arrData['dataEmployeeID'] . "'";
  }
  if ($arrData['dataPosition'] != "") {
    $strKriteria .= "AND t1.position_code = '" . $arrData['dataPosition'] . "'";
  }
  if ($arrData['dataBranch'] != "") {
    $strKriteria .= "AND t1.branch_code = '" . $arrData['dataBranch'] . "'";
  }
  if ($arrData['dataGrade'] != "") {
    $strKriteria .= "AND t1.grade_code = '" . $arrData['dataGrade'] . "'";
  }
  if ($arrData['dataStatus'] != "") {
    $strKriteria .= "AND t1.employee_status = '" . $arrData['dataStatus'] . "'";
  }
  if ($arrData['dataActive'] != "") {
    $strKriteria .= "AND t1.active = '" . $arrData['dataActive'] . "'";
  }
  if ($arrData['dataRequestStatus'] != "") {
    $strKriteria .= "AND t0.status = '" . $arrData['dataRequestStatus'] . "'";
  }
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND t1.division_code = '" . $arrData['dataDivision'] . "'";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND t1.department_code = '" . $arrData['dataDepartment'] . "'";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND t1.section_code = '" . $arrData['dataSection'] . "'";
  }
  if ($arrData['dataSubSection'] != "") {
    $strKriteria .= "AND t1.sub_section_code = '" . $arrData['dataSubSection'] . "'";
  }
  //uddin: tambah kriteria jika employee maka yg muncul employee yg functional dia dan dibawahnya
  $strDataUserRole = $_SESSION['sessionUserRole'];
  if ($strDataUserRole == ROLE_EMPLOYEE or $strDataUserRole == ROLE_SUPERVISOR) {
    if ($arrUserInfo["functional_code"] != "") {
      //$strSQL="SELECT * FROM hrd_functional WHERE head_code='".$arrUserInfo["functional_code"]."'";
      $strSQL = "SELECT t1.*,t2.employee_id FROM hrd_functional as t1
                    LEFT JOIN  (select * from hrd_employee " . $strKriteriaDiv . ") as t2 ON t1.functional_code=t2.functional_code
                    WHERE t1.head_code='" . $arrUserInfo["functional_code"] . "'";
      $resDb = $db->execute($strSQL);
      //$strFunctionalcode="('".$arrUserInfo["functional_code"]."'"; // inisial masukkan kode functional diri sendiri
      $strFunctionalcode = "('DUMMYINVOSAFUNCT'"; // inisial masukkan kode functional diri sendiri
      while ($rowDb = $db->fetchrow($resDb)) {
        //$strFunctionalcode.=",'".$rowDb['functional_code']."'";
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
      //$strKriteria .= " AND t2.functional_code in ".$strFunctionalcode." ";
      $strKriteria .= " AND (t1.functional_code in " . $strFunctionalcode . " or t1.employee_id='" . $arrUserInfo["employee_id"] . "') ";
    }
  }
  // end tambah kriteria functional code
  //echo $strKriteria;
  if ($arrData['dataFull'] != "") {
    $isFullView = true;
  } else {
    $isFullView = false;
  }
  $strKriteriaCompany = str_replace("AND", "AND t1.", $strKriteriaCompany);
  $strKriteria .= $strKriteriaCompany;
  if ($db->connect()) {
    $myDataGrid->caption = getWords("list of updated employee data");
    // $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->setPageLimit("all");
    $myDataGrid->setCriteria($strKriteria);
    if (!isset($_REQUEST['btnExportXLS'])) {
      $myDataGrid->addColumnCheckbox(
          new DataGrid_Column("chkID", "id", ['rowspan' => 2, 'width' => '30'], ['align' => 'center', 'nowrap' => '']),
          true /*bolDisableSelfStatusChange*/
      );
    }
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['rowspan' => 2, 'width' => 30], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            strtoupper(getWords("empl.id")),
            "employee_id",
            ['rowspan' => 2, 'width' => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "hilite()",
            "string",
            true,
            12,
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("employee name"),
            "employee_name",
            ["rowspan" => 2],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "hilite()",
            "string",
            true,
            35
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            strtoupper("npwp"),
            "npwp",
            ["rowspan" => 2, "width" => 80],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "hilite()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("sex"),
            "gender",
            ["rowspan" => 2, "width" => 30],
            ["align" => "center"],
            true,
            true,
            "",
            "printGender()",
            "string",
            true,
            6
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("age"),
            "umur",
            ["rowspan" => 2, "width" => 30],
            ["align" => "right"],
            true,
            true,
            "",
            "hilite()",
            "integer",
            true,
            6
        )
    );
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("fam."), "family_status_code", array("rowspan" => 2, "width" => 30),  null, true, true, "", "", "string", true, 12));
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("liv."), "living_cost_code", array("rowspan" => 2, "width" => 30),  null, true, true, "", "", "string", true, 12));
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("med."), "medical_quota_status", array("rowspan" => 2, "width" => 30),  null, true, true, "", "", "string", true, 12));
    if ($isFullView) {
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("nick name"),
              "nickname",
              ["rowspan" => 2, "width" => 80],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addSpannedColumn(getWords("primary address"), 4);
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("address"),
              "primary_address",
              ["width" => 150],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              35
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("city"),
              "primary_city",
              ["width" => 80],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              15
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("zip"),
              "primary_zip",
              ["width" => 40],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              8
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("phone"),
              "primary_phone",
              ["width" => 70],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addSpannedColumn(getWords("emergency contact"), 4);
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("name"),
              "emergency_contact",
              ["width" => 150],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              35
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("relation"),
              "emergency_relation",
              ["width" => 80],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              15
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("address"),
              "emergency_address",
              ["width" => 120],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("phone"),
              "emergency_phone",
              ["width" => 70],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("birthplace"),
              "birthplace",
              ["rowspan" => 2, "width" => 120],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("birthday"),
              "birthday",
              ["rowspan" => 2, "width" => 80],
              null,
              true,
              true,
              "",
              "formatDateHilite()",
              "string",
              true,
              12
          )
      );
    }
    $myDataGrid->addSpannedColumn(getWords("work information"), 9);
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("employee status"),
            "employee_status",
            ["width" => 100],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "printEmployeeStatusHilite()",
            "string",
            true,
            15
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("man."),
            "management_name",
            ["width" => 50],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "hilite()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("div."),
            "division_name",
            ["width" => 50],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "hilite()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("dept."),
            "department_name",
            ["width" => 50],
            ["nowrap" => "nowrap"],
            false,
            true,
            "",
            "hilite()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("sect."),
            "section_name",
            ["width" => 50],
            ["nowrap" => "nowrap"],
            false,
            true,
            "",
            "hilite()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("sub."),
            "sub_section_name",
            ["width" => 50],
            ["nowrap" => "nowrap"],
            false,
            true,
            "",
            "hilite()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("branch"),
            "branch_name",
            ["width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("level"),
            "position_code",
            ["width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "hilite()",
            "string",
            true,
            12
        )
    );
    //$myDataGrid->addColumn(new DataGrid_Column(getWords("grade"), "grade_code", array("width" => 40),  array("align" => "center"), true, true, "", "", "string", true, 6));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("functional position"),
            "functional_code",
            ["width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "hilite()",
            "string",
            true,
            12
        )
    );
    if ($isFullView) {
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("weight"),
              "weight",
              ["rowspan" => 2, "width" => 40],
              null,
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              8
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("height"),
              "height",
              ["rowspan" => 2, "width" => 40],
              null,
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              8
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("blood"),
              "blood_type",
              ["rowspan" => 2, "width" => 30],
              null,
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              6
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("id card"),
              "id_card",
              ["rowspan" => 2, "width" => 80],
              null,
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addSpannedColumn(getWords("driving license"), 3);
      $myDataGrid->addColumn(
          new DataGrid_Column(
              "A",
              "driver_license_a",
              ["width" => 80],
              null,
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              "B",
              "driver_license_b",
              ["width" => 80],
              null,
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              "C",
              "driver_license_c",
              ["width" => 80],
              null,
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              12
          )
      );
      //$myDataGrid->addColumn(new DataGrid_Column(getWords("nationality"), "nationality", array("rowspan" => 2, "width" => 70), null, true, true, "", "hilite()", "string", true, 12));
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("passport no."),
              "passport",
              ["rowspan" => 2, "width" => 50],
              null,
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              8
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("religion"),
              "religion_code",
              ["rowspan" => 2, "width" => 70],
              null,
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("education"),
              "education_level_code",
              ["rowspan" => 2, "width" => 70],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("wedding date"),
              "wedding_date",
              ["rowspan" => 2, "width" => 80],
              null,
              true,
              true,
              "",
              "formatDateHilite()",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("spouse"),
              "inspouse",
              ["rowspan" => 2, "width" => 30],
              null,
              true,
              false,
              "",
              "printIsSpouse()",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("zakat"),
              "zakat",
              ["rowspan" => 2, "width" => 30],
              null,
              true,
              false,
              "",
              "printIsSpouse()",
              "string",
              true,
              12
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("jamsostek no."),
              "jamsostek_no",
              ["rowspan" => 2, "width" => 80],
              null,
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              15
          )
      );
      $myDataGrid->addSpannedColumn(getWords("primary bank account"), 3);
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("acc no."),
              "bank_account",
              ["width" => 80],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              15
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("acc name"),
              "bank_account_name",
              ["width" => 120],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("bank code"),
              "bank_code",
              ["width" => 80],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              15
          )
      );
      $myDataGrid->addSpannedColumn(getWords("secondary bank account"), 3);
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("acc no."),
              "bank2_account",
              ["width" => 80],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              15
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("acc name"),
              "bank2_account_name",
              ["width" => 120],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              30
          )
      );
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("bank code"),
              "bank2_code",
              ["width" => 80],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              15
          )
      );
    }
    if (!$isFullView) {
      $myDataGrid->addColumn(
          new DataGrid_Column(
              getWords("phone"),
              "primary_phone",
              ["rowspan" => 2, "width" => 70],
              ["nowrap" => "nowrap"],
              true,
              true,
              "",
              "hilite()",
              "string",
              true,
              12
          )
      );
    }
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("email"),
            "email",
            ["rowspan" => 2, "width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "hilite()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("note"),
            "note",
            ["rowspan" => 2, "width" => 70],
            ["nowrap" => "nowrap"],
            true,
            true,
            "",
            "hilite()",
            "string",
            true,
            12
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("status"),
            "status",
            ['rowspan' => 2, 'width' => '60'],
            "",
            false,
            false,
            "",
            "printRequestStatus()"
        )
    );
    if (!isset($_REQUEST['btnExportXLS'])) {
      if ($bolCanEdit) {
        $myDataGrid->addColumn(
            new DataGrid_Column(
                "",
                "",
                ['rowspan' => 2, 'width' => '60'],
                ['align' => 'center', 'nowrap' => ''],
                false,
                false,
                "",
                "printGlobalEditLink()",
                "",
                false /*show in excel*/
            )
        );
      }
      $myDataGrid->addColumn(
          new DataGrid_Column(
              "",
              "",
              ['rowspan' => 2, 'width' => '60'],
              ['align' => 'center', 'nowrap' => ''],
              false,
              false,
              "",
              "printView()",
              "",
              false /*show in excel*/
          )
      );
    }
    foreach ($arrData AS $key => $value) {
      $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
    }
    //tampilkan buttons sesuai dengan otoritas, common_function.php
    generateRoleButtons(
        $bolCanEdit,
        $bolCanDelete,
        $bolCanCheck,
        $bolCanApprove,
        $bolCanAcknowledge,
        true,
        $myDataGrid
    );
    $strWordExpoxcel = getWords("export excel");
    $myDataGrid->addButtonExportExcel($strWordExpoxcel, $strPageTitle . ".xls", $strPageTitle);
    $myDataGrid->getRequest();
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_employee_temporary AS t0
                       LEFT JOIN hrd_employee AS t1 ON t0.employee_id = t1.employee_id";
    $strSQL = "SELECT t0.*, (EXTRACT(YEAR FROM AGE(t0.birthday))) AS umur, t1.employee_name, t1.id as id_employee,
                       management_name, division_name, department_name, section_name, sub_section_name, branch_name FROM hrd_employee_temporary AS t0 
                       LEFT JOIN hrd_employee AS t1 ON t0.employee_id = t1.employee_id
                       LEFT JOIN hrd_management AS t2 ON t0.management_code = t2.management_code 
                       LEFT JOIN hrd_division AS t3 ON t0.division_code = t3.division_code 
                       LEFT JOIN hrd_department AS t4 ON t0.department_code = t4.department_code
                       LEFT JOIN hrd_section AS t5 ON t0.section_code = t5.section_code 
                       LEFT JOIN hrd_branch AS t6 ON t0.branch_code = t6.branch_code 
                       LEFT JOIN hrd_sub_section AS t7 ON t0.sub_section_code = t7.sub_section_code
                       WHERE 1=1 $strKriteria
                      ";
    // $strSQLMaster = "SELECT t0.*, (EXTRACT(YEAR FROM AGE(t0.birthday))) AS umur, t1.employee_name, t1.id as id_employee,
    // management_name, division_name, department_name, section_name, sub_section_name, branch_name FROM hrd_employee AS t0
    // LEFT JOIN hrd_employee AS t1 ON t0.employee_id = t1.employee_id
    // LEFT JOIN hrd_management AS t2 ON t0.management_code = t2.management_code
    // LEFT JOIN hrd_division AS t3 ON t0.division_code = t3.division_code
    // LEFT JOIN hrd_department AS t4 ON t0.department_code = t4.department_code
    // LEFT JOIN hrd_section AS t5 ON t0.section_code = t5.section_code
    // LEFT JOIN hrd_branch AS t6 ON t0.branch_code = t6.branch_code
    // LEFT JOIN hrd_sub_section AS t7 ON t0.sub_section_code = t7.sub_section_code
    // WHERE 1=1 $strKriteria
    // ";
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
    $dataset = $myDataGrid->getData($db, $strSQL);
    // $datasetMaster = $myDataGrid->getData($db, $strSQLMaster, "id");
    //bind Datagrid with array dataset and branchCode
    $myDataGrid->bind($dataset);
    $DataGrid = $myDataGrid->render();
  } else {
    $DataGrid = "";
  }
  return $DataGrid;
}

// fungsi untuk verify, check, deny, atau approve
function changeStatus($db, $intStatus)
{
  global $_REQUEST;
  global $_SESSION;
  if (!is_numeric($intStatus)) {
    return false;
  }
  $strUpdate = "";
  $strSQL = "";
  $arrDataTemporaryID = [];
  $strModifiedByID = $_SESSION['sessionUserID'];
  $strUpdate = getStatusUpdateString($intStatus);
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 15) == 'DataGrid1_chkID') {
      $strSQLx = "SELECT status, t2.employee_name
                    FROM hrd_employee_temporary AS t1 
                    LEFT JOIN hrd_employee AS t2 ON t1.employee_id = t2.employee_id
                    WHERE t1.id = '$strValue' ";
      $resDb = $db->execute($strSQLx);
      if ($rowDb = $db->fetchrow($resDb)) {
        //the status should be increasing
        // if (isProcessable($rowDb['status'], $intStatus))
        if (($intStatus == -1) || (($rowDb['status'] < $intStatus) && ($rowDb['status'] != -1))) {
          $strSQL .= "UPDATE hrd_employee_temporary SET $strUpdate status = '$intStatus'  ";
          $strSQL .= "WHERE id = '$strValue'; ";
          writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, $rowDb['employee_name'] . " - " . $intStatus);
          $arrDataTemporaryID[] = $strValue;
        }
      }
    }
    $resExec = $db->execute($strSQL);
  }
  if ($intStatus >= REQUEST_STATUS_APPROVED && count($arrDataTemporaryID) != 0) {
    updateEmployeeData($db, $arrDataTemporaryID);
  }
} //changeStatus
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $tblEmployeeTemporary = new cHrdEmployeeTemporary();
  $tblEmployeeTemporary->deleteMultiple($arrKeys);
  $myDataGrid->message = $tblEmployeeTemporary->strMessage;
} //deleteData
function updateEmployeeData($db, $arrDataTemporaryID)
{
  $tblEmployee = new cHrdEmployee();
  $tblEmployeeTemporary = new cHrdEmployeeTemporary();
  foreach ($arrDataTemporaryID as $strDataTemporaryID) {
    $dataEmployeeTemporary = $tblEmployeeTemporary->find(["id" => $strDataTemporaryID]);
    $dataEmployeeTemporary = array_remove_key(
        $dataEmployeeTemporary,
        "id",
        "created",
        "created_by",
        "modified",
        "modified_by",
        "approved_time",
        "approved_by",
        "denied_time",
        "denied_by",
        "status"
    );
    $tblEmployee->update("employee_id = '" . $dataEmployeeTemporary['employee_id'] . "'", $dataEmployeeTemporary);
  }
}

function printView($params)
{
  global $datasetMaster;
  extract($params);
  return "<a target=\"_blank\" href=\"employee_temporary_view.php?dataID=" . $record['id'] . "&empID=" . $record['employee_id'] . "\">View</a>";
}

function hilite($params)
{
  global $datasetMaster;
  extract($params);
  return ($datasetMaster[$record['id_employee']] [$params['field']] != $value) ? "$value" : $value;
}

function printGender($params)
{
  global $datasetMaster;
  extract($params);
  $tempVal = ($value == 0) ? "F" : "M";
  return ($datasetMaster[$record['id_employee']] [$params['field']] != $value) ? "$tempVal" : $tempVal;
}

function printEmployeeStatusHilite($params)
{
  global $datasetMaster;
  extract($params);
  global $ARRAY_EMPLOYEE_STATUS;
  $tempVal = getWords($ARRAY_EMPLOYEE_STATUS[$value]);
  return ($datasetMaster[$record['id_employee']] [$params['field']] != $value) ? "$tempVal" : $tempVal;
}

function printIsSpouse($params)
{
  global $datasetMaster;
  extract($params);
  $tempVal = ($value == 't') ? "*" : "";
  return ($datasetMaster[$record['id_employee']] [$params['field']] != $value) ? "$tempVal" : $tempVal;
}

function formatDateHilite($params)
{
  global $datasetMaster;
  if (!is_numeric($params) && !is_string($params) && $params != "") {
    extract($params);
    $tempVal = $value;
  } else {
    $tempVal = $params;
  }
  $tempVal = pgDateFormat($tempVal, "d-M-y");
  return ($datasetMaster[$record['id_employee']] [$params['field']] != $value) ? "$tempVal" : $tempVal;
}

//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
$datasetMaster = [];
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
  $strPageTitle = getWords($dataPrivilege['menu_name']);
  //generate form untuk select trip type
  //trip type harus dipilih dulu supaya jenis2 trip allowance dapat ditentukan
  $f = new clsForm("formFilter", 3, "1024px", "");
  $f->caption = strtoupper(getWords("employee temporary data"));
  $autoCompleteValue = "";//getInitialValue("Employee", null, $strDataEmployee);
  $employeeName = '';
  if (!empty($autoCompleteValue)) {
    //$autoCompleteValue = getInitialValue("Employee", null, $strDataEmployee);
    $employeeData = getEmployeNameByID($db, $autoCompleteValue);
    $employeeName = $employeeData['employee_name'];
  }
  $f->addInputAutoComplete(
      getWords("employee"),
      "dataEmployeeID",
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
  $f->addLabelAutoComplete("", "dataEmployeeID", $employeeName);
  //$f->addInputAutoComplete(getWords("employee ID"), "dataEmployeeID", getDataEmployee($strDataEmployee), "style=width:$strDefaultWidthPx ".$strEmpReadonly, "string", false);
  //$f->addLabelAutoComplete("", "dataEmployeeID", "");
  $f->addLiteral("", "", "");
  $f->addSelect(
      getWords("request status"),
      "dataRequestStatus",
      getDataListRequestStatus("", true, ["value" => "", "text" => "", "selected" => true]),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addCheckBox(getWords("full view"), "dataFull", false, null, "string", false, true, true, "", "");
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
  getData($db);
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$strPageDesc = getWords('employee temporary list');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = employeeTempSubmenu($strWordsEmployeeTemporaryDataList);
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
?>