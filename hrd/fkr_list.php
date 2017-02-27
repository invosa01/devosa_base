<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../classes/datagrid_modified.php');
include_once('../includes/form2/form2.php');
include_once('cls_employee.php');
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
$db = new CdbClass;
$db->connect();
$arrUserList = getAllUserInfo($db);//ambil semua info user]
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
//if ($bolCanEdit)
//{
$f = new clsForm("form1", 3, "100%", "100%");
$f->disableFormTag();
$f->showCaption = false;
$f->addHidden("dataID", $strDataID);
//$f->addFieldSet(getWords("search criteria"), 3);
$f->addInput(
    getWords("employee name"),
    "employee_name",
    "",
    ["size" => 20, "maxlength" => 20],
    "string",
    true,
    true,
    true
);
$f->addInput(getWords("position"), "position_code", "", ["size" => 30], "string");
$arrFKRStatus = ["" => "", REQUEST_STATUS_NEW => "new", REQUEST_STATUS_APPROVED => "approved"];
$f->addSelect(getWords("status"), "status", $arrFKRStatus, [], "string", false);
//Nambah ruang kosong supaya form tidak memanjang unmark jika diperlukan
/*for ($i = 0;$i < 5;$i++){
  $f->addLabel("", "", "");
}*/
$f->addSubmit("btnSearch", getWords("search"), ["onClick" => "javascript:doSearch()"], true, true, "", "", "");
$f->addSubmit("btnPrint", getWords("print"), ["onClick" => "javascript:printList()"], true, true, "", "", "");
$f->addSubmit("btnExportXLS", getWords("excel"), ["onClick" => "javascript:exportExcel()"], true, true, "", "", "");
$formInput = $f->render();
// handle jika ada permintaan pembuatan data karyawan dari FKR
if (isset($_REQUEST['btnCreateEmployee']) && isset($_REQUEST['dataID'])) {
  $bolOK = generateEmployeeFromFKR($db);
  // if($bolOK)
  // echo "sadas";
}
//}
//else
//  $formInput = "";
$bolPrint = false;
$bolExcel = false;
if (isset($_POST['btnPrint'])) {
  $bolPrint = true;
}
if (isset($_POST['btnExportXLS'])) {
  $bolExcel = true;
}
$myDataGrid = new cDataGridNew("form1", "DataGrid1", "100%", "100%", !($bolPrint || $bolExcel), true, true);
$myDataGrid->disableFormTag();
$myDataGrid->caption = getWords("FKR");
//$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
//$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", array("rowspan" => 2, 'width'=>30), array('nowrap'=>'')));
if ($bolPrint || $bolExcel) {
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("name"),
          "employee_name",
          ["rowspan" => 2, 'width' => 150],
          ['nowrap' => 'nowrap'],
          true,
          true,
          "",
          "",
          "string",
          true,
          32
      )
  );
} else {
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "id", ["rowspan" => 2, 'width' => 30], ['align' => 'center', 'nowrap' => 'nowrap'])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("name"),
          "employee_name",
          ["rowspan" => 2, "width" => 150],
          ['nowrap' => 'nowrap'],
          true,
          true,
          "",
          "printViewLink()",
          "string",
          true,
          32
      )
  );
}
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("position"),
        "position_code",
        ["rowspan" => 2, "width" => 100],
        [],
        false,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("employee id"),
        "employee_id",
        ["rowspan" => 2, "width" => 100],
        [],
        false,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("family"),
        "family_status_code",
        ["rowspan" => 2, "width" => 50],
        [],
        false,
        false,
        "",
        "",
        "string",
        true,
        6
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("status"),
        "employee_status",
        ["rowspan" => 2, "width" => 50],
        [],
        false,
        false,
        "",
        "printEmployeeStatus1()",
        "string",
        true,
        6
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("join date"),
        "join_date",
        ["rowspan" => 2, "width" => 50],
        [],
        false,
        false,
        "",
        "",
        "string",
        true,
        6
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("band"),
        "salary_grade_code",
        ["rowspan" => 2, "width" => 50],
        [],
        false,
        false,
        "",
        "",
        "string",
        true,
        6
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("organization structure"),
        "",
        ["colspan" => 4],
        ['nowrap' => 'nowrap'],
        true,
        false,
        "",
        "",
        "string",
        true
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("company"),
        "id_company",
        ["width" => 100],
        ['nowrap' => 'nowrap'],
        false,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("division"),
        "division_name",
        ["width" => 100],
        ['nowrap' => 'nowrap'],
        true,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("department"),
        "department_name",
        ["width" => 100],
        ['nowrap' => 'nowrap'],
        true,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("unit"),
        "section_name",
        ["width" => 100],
        ['nowrap' => 'nowrap'],
        true,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("status"),
        "status",
        ["rowspan" => 2, "width" => 60],
        ['nowrap' => 'nowrap'],
        true,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("note"), "note", ["rowspan" => 2], [], true, true, "", "", "string", true, 12)
);
if (!($bolPrint || $bolExcel)) {
  if ($bolCanEdit) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "",
            "",
            ["rowspan" => 2, 'width' => 45],
            ['align' => 'center', 'nowrap' => 'nowrap'],
            false,
            false,
            "",
            "printEditLink()",
            "string",
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "",
            "",
            ["rowspan" => 2, 'width' => 45],
            ['align' => 'center', 'nowrap' => 'nowrap'],
            false,
            false,
            "",
            "printPrintLink()",
            "string",
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("employee"),
            "",
            ["rowspan" => 2, 'width' => 45],
            ['align' => 'center', 'nowrap' => 'nowrap'],
            false,
            false,
            "",
            "printEmployeeLink()",
            "string",
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "",
            "",
            ["rowspan" => 2],
            ['align' => 'center', 'nowrap' => 'nowrap'],
            false,
            false,
            "",
            "printShowLink()",
            "string",
            false
        )
    );
  }
}
if ($bolCanDelete) {
  $myDataGrid->addSpecialButton(
      "btnDelete",
      "btnDelete",
      "submit",
      getWords("delete"),
      "onClick=\"javascript:return myClient.confirmDelete();\"",
      "deleteData()"
  );
}
if ($bolCanApprove && $objUP->isManagerHR()) {
  $myDataGrid->addSpecialButton(
      "btnApprove",
      "btnApprove",
      "submit",
      getWords("approve"),
      "onClick=\"\"",
      "approveData()"
  );
  $myDataGrid->addSpecialButton(
      "btnUnApprove",
      "btnUnApprove",
      "submit",
      getWords("unapprove"),
      "onClick=\"\"",
      "unApproveData()"
  );
}
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$tblComp = new cModel("hrd_company", "Company");
$arrComp = $tblComp->findAll("", "id, company_name, company_code", "", null, 1, "id");
$tblDiv = new cModel("hrd_division", "Division");
$arrDiv = $tblDiv->findAll("", "id, division_code, division_name", "", null, 1, "division_code");
$tblDep = new cModel("hrd_department", "Department");
$arrDep = $tblDep->findAll("", "id, department_code, department_name", "", null, 1, "department_code");
$tblSec = new cModel("hrd_section", "Section");
$arrSec = $tblSec->findAll("", "id, section_code, section_name", "", null, 1, "section_code");
$tbl = new cModel("hrd_fkr", "FKR");
$arrCriteria = [];
if ($f->getValue('employee_name') != '') {
  $arrCriteria[] = "upper(employee_name) LIKE '%" . strtoupper($f->getValue('employee_name')) . "%'";
}
if ($f->getValue('status') != '') {
  $arrCriteria[] = "status =" . intval($f->getValue('status'));
}
if ($f->getValue('position_code') != '') {
  $arrCriteria[] = "upper(position_code) LIKE '%" . strtoupper($f->getValue('position_code')) . "%'";
}
$strCriteria = implode(" AND ", $arrCriteria);
if ($strCriteria != "") {
  $strCriteria = " AND " . $strCriteria;
}
// tambah kriteria berdasarkan band
/*
$strBandList = getBandAccessCriteria();
if ($strBandList == "") $strCriteria .= "AND salary_grade_code = '' ";
else if ($strBandList == "all") $strCriteria .= "";
else $strCriteria .= "AND salary_grade_code IN ($strBandList) ";
*/
// tambah kriteria untuk data karyawan
$strCriteria .= $objUP->genFilterCompany(0);
$strCriteria .= $objUP->genFilterDivision();
$strCriteriaFlag = $myDataGrid->getCriteria() . $strCriteria;
$myDataGrid->totalData = $tbl->findCount($strCriteriaFlag);
if ($bolExcel) {
  $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
  $myDataGrid->strFileNameXLS = "FKR_result.xls";
  $myDataGrid->strTitle1 = "List of Form Kesepakatan Remunerasi";
  $myDataGrid->strTitle2 = "Printed Date: " . date("d/m/Y h:i:s");
  $strPageLimit = null;
  $strPageNumber = null;
} elseif ($bolPrint) {
  $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_PRINT_HTML;
  $myDataGrid->strTitle1 = "List of Form Kesepakatan Remunerasi";
  $myDataGrid->strTitle2 = "Printed Date: " . date("d/m/Y h:i:s");
  $strPageLimit = null;
  $strPageNumber = null;
} else {
  $strPageLimit = $myDataGrid->getPageLimit();
  $strPageNumber = $myDataGrid->getPageNumber();
}
$dataset = $tbl->findAll(
    $strCriteriaFlag,
    null, //all field
    $myDataGrid->getSortBy(),
    $strPageLimit,
    $strPageNumber
);
foreach ($dataset as &$rowDb) {
  if ($rowDb['status'] == REQUEST_STATUS_APPROVED) {
    $rowDb['status'] = getWords("approved");
  } else if ($rowDb['status'] == REQUEST_STATUS_NEW) {
    $rowDb['status'] = getWords("new");
  } else {
    $rowDb['status'] = "";
  }
  $rowDb['note'] = nl2br($rowDb['note']);
  $rowDb['join_date'] = pgDateFormat($rowDb['join_date'], "d-M-y");
  $rowDb['division_name'] = $rowDb['division_code'];
  $rowDb['department_name'] = $rowDb['department_code'];
  $rowDb['section_name'] = $rowDb['section_code'];
  if (isset($arrComp[$rowDb['id_company']])) {
    $rowDb['id_company'] = $arrComp[$rowDb['id_company']]['company_name'];
  }
  if (isset($arrDiv[$rowDb['division_code']])) {
    $rowDb['division_name'] = $arrDiv[$rowDb['division_code']]['division_name'];
  }
  if (isset($arrDep[$rowDb['department_code']])) {
    $rowDb['department_name'] = $arrDep[$rowDb['department_code']]['department_name'];
  }
  if (isset($arrSec[$rowDb['section_code']])) {
    $rowDb['section_name'] = $arrSec[$rowDb['section_code']]['section_name'];
  }
}
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$strConfirmDelete = getWords("are you sure to delete this selected data?");
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('fkr management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = "./templates/fkr_list.html";
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
//$tbsPage->LoadTemplate($strMainTemplate) ;
$tbsPage->Show();
//--------------------------------------------------------------------------------
// fungsi untuk meng-handle jika input data adalah kosong
// jika kosong, diganti dengan NULL, jika tidak, apit dengan ''
function handleNull($str)
{
  if ($str === "") {
    return "NULL";
  } else {
    return "'$str'";
  }
}

// handle tanggal, jika kosong
function handleDate($str)
{
  if ($str == "") {
    return "NULL";
  } else {
    return "'$str'";
  }
}

// untuk menampilkan info yang mengubah data MRF
function printShowLink($params)
{
  extract($params);
  global $arrUserList;
  $strResult = "";
  // tambahkan info record info
  $strDiv = "<div id='detailRecord$counter' style=\"display:none\">\n";
  $strDiv .= "<strong>" . $record['employee_id'] . "-" . $record['employee_name'] . "</strong><br>\n";
  $strDiv .= getWords("last modified") . ": " . substr($record['modified'], 0, 19) . " ";
  $strDiv .= (isset($arrUserList[$record['modified_by']])) ? $arrUserList[$record['modified_by']]['name'] . "<br>" : "<br>";
  /*
      $strDiv .= getWords("verified"). ": ".substr($record['verified_time'], 0,19) ." ";
      $strDiv .= (isset($arrUserList[$record['verified_by']])) ? $arrUserList[$record['verified_by']]['name']."<br>" : "<br>";

      $strDiv .= getWords("checked"). ": ".substr($record['checked_time'], 0,19) ." ";
      $strDiv .= (isset($arrUserList[$record['checked_by']])) ? $arrUserList[$record['checked_by']]['name']."<br>" : "<br>";
  */
  $strDiv .= getWords("approved") . ": " . substr($record['approved1'], 0, 19) . " ";
  $strDiv .= (isset($arrUserList[$record['approved1_by']])) ? $arrUserList[$record['approved1_by']]['name'] . "<br>" : "<br>";
  /*
      $strDiv .= getWords("approved by director"). ": ".substr($record['dir_approval_time'], 0,19) ." ";
      $strDiv .= (isset($arrUserList[$record['dir_approval_by']])) ? $arrUserList[$record['dir_approval_by']]['name']."<br>" : "<br>";
      $strDiv .= getWords("denied"). ": ".substr($record['denied_time'], 0,19) ." ";
      $strDiv .= (isset($arrUserList[$record['denied_by']])) ? $arrUserList[$record['denied_by']]['name']."<br>" : "<br>";
  */
  $strDiv .= "</div>\n";
  $strResult .= $strDiv . "<a href=\"javascript:openViewWindowByContentId('Record Information', 'detailRecord$counter', 400, 150)\" title=\"" . getWords(
          "show record info"
      ) . "\">" . getWords("show") . "</a>";
  return $strResult;
}

// copy data keluarga, dari kandidat ke karyawan
function copyFamily($db, $strCandidateID, $strEmployeeID)
{
  if ($strCandidateID == "" || $strEmployeeID == "") {
    return false;
  }
  $bolOK = true;
  $strUpdate = "";
  $strSQL = "
      SELECT t1.*, t2.name as name_type, t2.is_married
      FROM hrd_candidate_family AS t1
      LEFT JOIN hrd_family AS t2 ON t1.id_family = t2.id
      WHERE id_candidate = '$strCandidateID';
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $strRel = strtoupper($row['name_type']);
    if (strstr($strRel, "AYAH")) {
      $strRelation = 0;
    } else if (strstr($strRel, "IBU")) {
      $strRelation = 1;
    } else if (strstr($strRel, "ISTRI") && $row['id_gender'] == FEMALE) {
      $strRelation = 2;
    } else if (strstr($strRel, "SUAMI") && $row['id_gender'] == MALE) {
      $strRelation = 3;
    } else if (strstr($strRel, "ANAK")) {
      $strRelation = 4;
    } else if (strstr($strRel, "SAUDARA")) {
      $strRelation = 5;
    } else {
      $strRelation = 6;
    }
    if ($row['name'] != "") {
      $strUpdate .= "
          INSERT INTO hrd_employee_family (
            id_employee, name, relation, birthday,
            education_code, \"position\", company, gender
          )
          VALUES (
            '$strEmployeeID', '" . addslashes(substr($row['name'], 0, 50)) . "',
            $strRelation, " . handleDate($row['dob']) . ",
            '" . addslashes(substr($row['education'], 0, 20)) . "',
            '" . addslashes(substr($row['position'], 0, 30)) . "',
            '" . addslashes(substr($row['company_name'], 0, 100)) . "',
            " . handleNull($row['id_gender']) . "
          );
        ";
    }
  }
  if ($strUpdate != "") {
    $resExec = $db->execute($strUpdate);
    if ($resExec == false) {
      $bolOK = false;
    }
  }
  return $bolOK;
}

// copy data gaji karyawan
function copySalary($db, $strCandidateID, $strEmployeeID)
{
  if ($strCandidateID == "" || $strEmployeeID == "") {
    return false;
  }
  $bolOK = true;
  $strSQL1 = "select id from hrd_basic_salary_set order by id desc limit 1";
  $res1 = $db->execute($strSQL1);
  $row1 = $db->fetchrow($res1);
  $setID = $row1['id'];
  $strUpdate = "
      DELETE FROM hrd_employee_allowance WHERE id_employee = '$strEmployeeID';
    ";
  $strSQL = "
      SELECT t1.*, t2.code as allowance_code
      FROM hrd_fkr_detail AS t1
      INNER JOIN hrd_allowance_type AS t2 ON t1.id_allowance_type = t2.id
      WHERE t1.id_fkr IN
      ( SELECT id FROM hrd_fkr WHERE id_candidate = '$strCandidateID' )
      ;
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $fltAmount = ($row['amount_next'] == "" || $row['amount_next'] == 0) ? $row['amount_start'] : $row['amount_next'];
    $strCode = $row['allowance_code'];
    if ($strCode != "" && $fltAmount <> 0 && is_numeric($fltAmount)) {
      $strUpdate .= "
          INSERT INTO hrd_employee_allowance (
            id_employee, allowance_code, amount, created, id_salary_set
          )
          VALUES (
            '$strEmployeeID', '" . addslashes(substr($strCode, 0, 100)) . "',
            '$fltAmount', now(), '$setID'
          );
        ";
    }
    // pgp_sym_encrypt('$fltAmount', '".PGP_KEY."'), now()
    // echo $strUpdate;
  }
  // die();
  if ($strUpdate != "") {
    $resExec = $db->execute($strUpdate);
    if ($resExec == false) {
      $bolOK = false;
    }
  }
  return $bolOK;
}

// copy data riwayat pengalaman kerja, dari kandidat ke karyawan
function copyWork($db, $strCandidateID, $strEmployeeID)
{
  if ($strCandidateID == "" || $strEmployeeID == "") {
    return false;
  }
  $bolOK = true;
  $strUpdate = "";
  $strSQL = "
      SELECT *
      FROM hrd_candidate_working_experience
      WHERE id_candidate = '$strCandidateID';
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $strPosition = ($row['position_end'] == "") ? $row['position_start'] : $row['position_end'];
    if ($row['company_name'] != "") {
      $strUpdate .= "
          INSERT INTO hrd_employee_work (
            id_employee, institution, \"location\", \"position\",
            day_from, month_from, year_from,
            day_thru, month_thru, year_thru, note
          )
          VALUES (
            '$strEmployeeID', '" . addslashes(substr($row['company_name'], 0, 100)) . "',
            '" . addslashes(substr($row['company_address'], 0, 50)) . "',
            '" . addslashes(substr($strPosition, 0, 50)) . "',
            '" . $row['start_day'] . "', '" . $row['start_month'] . "', '" . $row['start_year'] . "',
            '" . $row['end_day'] . "', '" . $row['end_month'] . "', '" . $row['end_year'] . "',
            '" . addslashes(substr($row['job_description'], 0, 250)) . "'
          );
        ";
    }
  }
  if ($strUpdate != "") {
    $resExec = $db->execute($strUpdate);
    if ($resExec == false) {
      $bolOK = false;
    }
  }
  return $bolOK;
}

// copy data riwayat training, dari kandidat ke karyawan
function copyTraining($db, $strCandidateID, $strEmployeeID)
{
  if ($strCandidateID == "" || $strEmployeeID == "") {
    return false;
  }
  $bolOK = true;
  $strUpdate = "";
  $strSQL = "
      SELECT *
      FROM hrd_candidate_course
      WHERE id_candidate = '$strCandidateID';
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    if ($row['course_type'] != "") {
      $strUpdate .= "
          INSERT INTO hrd_employee_training (
            id_employee, subject, institution, \"location\",
            year_from, year_thru, note
          )
          VALUES (
            '$strEmployeeID', '" . addslashes(substr($row['course_type'], 0, 50)) . "',
            '" . addslashes(substr($row['institution'], 0, 100)) . "',
            '" . addslashes(substr($row['place'], 0, 100)) . "',
            '" . $row['start_year'] . "',
            '" . $row['start_year'] . "',
            '" . addslashes(substr($row['funded_by'], 0, 250)) . "'
          );
        ";
    }
  }
  if ($strUpdate != "") {
    $resExec = $db->execute($strUpdate);
    if ($resExec == false) {
      $bolOK = false;
    }
  }
  return $bolOK;
}

// copy data riwayat training, dari kandidat ke karyawan
function copyEducation($db, $strCandidateID, $strEmployeeID)
{
  if ($strCandidateID == "" || $strEmployeeID == "") {
    return false;
  }
  $bolOK = true;
  $strUpdate = "";
  $strSQL = "
      SELECT *
      FROM hrd_candidate_education
      WHERE id_candidate = '$strCandidateID';
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    if ($row['academic'] != "" || $row['school'] != "") {
      $strUpdate .= "
          INSERT INTO hrd_employee_education (
            id_employee, education_level_code, institution, \"location\",
            faculty, year_from, year_thru
          )
          VALUES (
            '$strEmployeeID', '" . addslashes(substr($row['academic'], 0, 20)) . "',
            '" . addslashes(substr($row['school'], 0, 100)) . "',
            '" . addslashes(substr($row['place'], 0, 50)) . "',
            '" . addslashes(substr($row['major'], 0, 100)) . "',
            '" . $row['year_from'] . "',
            '" . $row['year_to'] . "'
          );
        ";
    }
  }
  if ($strUpdate != "") {
    $resExec = $db->execute($strUpdate);
    if ($resExec == false) {
      $bolOK = false;
    }
  }
  return $bolOK;
}

// fungsi untuk mengupdate family status,jika ada perubahan data terkait dengan status keluarga
// agar lebih cepat, status single (0) atau married (1) disebutkan terlebih dahulu
// juga gender disebutkan
function updateFamilyStatus($db, $strIDEmployee, $intGender = "", $intStatus = SINGLE)
{
  $bolOK = true;
  if ($strIDEmployee == "") {
    return $bolOK;
  }
  // ambil dulu jenis
  if ($intGender == "") {
    $intGender = MALE;
  } //anggap aja
  $intChildren = 0;
  $strFamilyStatus = "";
  if ($intGender == FEMALE || $intStatus == SINGLE) // wanita atau single, wanita dianggap single
  {
    $intStatus = SINGLE; //
  } else {
    $strSQL = "
        SELECT COUNT(id) AS total FROM hrd_employee_family
        WHERE id_employee = '$strIDEmployee'
          AND relation = '4' AND (status = '0' OR status is null)
      ";
    $res = $db->execute($strSQL);
    if ($row = $db->fetchrow($res)) {
      if (is_numeric($row['total'])) {
        $intChildren = $row['total'];
      }
      if ($intChildren > 3) {
        $intChildren = 3;
      }
    }
  }
  // cari data di jenis keluarga
  $strSQL = "
      SELECT * FROM hrd_family_status
      WHERE marital_status = '$intStatus'
        AND children = '$intChildren'
    ";
  $res = $db->execute($strSQL);
  if ($row = $db->fetchrow($res)) {
    $strFamilyStatus = $row['code'];
  }
  // update data
  $strSQL = "
      UPDATE hrd_employee SET family_status_code = '$strFamilyStatus'
      WHERE id = '$strIDEmployee';
    ";
  $resExec = $db->execute($strSQL);
  if ($resExec == false) {
    $bolOK = false;
  }
  return $bolOK;
}

// fungsi untuk membuat data karyawan dari data FKR
function generateEmployeeFromFKR($db)
{
  global $arrHouseOwnership; // common_variable.php
  $bolOK = true;
  $strFKR = getRequestValue("dataID");
  $strCandidate = ""; // id dari candidate
  if ($strFKR == "") {
    return false;
  }
  $arrFKR = [];
  $strSQL = "
      SELECT * FROM hrd_fkr WHERE id = '$strFKR'
    ";
  $res = $db->execute($strSQL);
  if ($row = $db->fetchrow($res)) {
    $arrFKR = $row;
    $strCandidate = $row['id_candidate'];
    if ($row['id_employee'] != "") {
      return false;
    } //anggap sudah ada, tidak perlu dibuat lagi
  }
  if ($strCandidate == "") {
    return false;
  } // kandidat tidak ada, abaikan
  $arrCandidate = [];
  $strSQL = "
      SELECT * FROM hrd_candidate WHERE id = '$strCandidate'
    ";
  $res = $db->execute($strSQL);
  if ($row = $db->fetchrow($res)) {
    $arrCandidate = $row;
  } else {
    return false;
  }
  // start proses generate data karyawan
  $db->execute("begin");
  $strEmployeeID = $db->getNextID("hrd_employee_id_seq");
  $strGender = ($arrCandidate['gender'] == "") ? 0 : $arrCandidate['gender'];
  $strMaritalStatus = ($arrCandidate['marital_status'] == "1") ? MARRIED : SINGLE; // di kandidat agak aneh, gak standard
  $strPhone = ($arrCandidate['phone'] != "" && $arrCandidate['hp'] != "") ? $arrCandidate['phone'] . ", " . $arrCandidate['hp'] : $arrCandidate['phone'] . $arrCandidate['hp'];
  $strJoinDate = $strDueDate = $strJoinDate2 = $strDueDate2 = "";
  $strJoinDateLeave = $arrFKR['join_date']; // join date untuk leave
  $strPermanent = $strProbationEnd = $strPermanentAssign = "";
  $strProbation = 'f';
  if ($arrFKR['employee_status'] == 0) {
    $strJoinDate = $arrFKR['join_date'];
    $strPeriod = ($arrFKR['contract_month'] == "") ? 0 : $arrFKR['contract_month'];
    $strDueDate = getNextDateNextMonth($strJoinDate, $strPeriod);// date_functions.php
    $strDueDate = getNextDate($strDueDate, -1);
    $arrFKR['employee_status'] = STATUS_CONTRACT;
  } else //if ($arrFKR['employee_status'] == 1)
  {
    $strPermanent = $arrFKR['join_date'];
    $strPermanentAssign = $arrFKR['join_date'];
    $strPeriod = ($arrFKR['contract_month'] == "") ? 0 : $arrFKR['contract_month'];
    if (is_numeric($strPeriod) && $strPeriod > 0) //percobaan
    {
      $strProbation = 't';
      $strProbationEnd = getNextDateNextMonth($strPermanent, $strPeriod);// date_functions.php
      $strProbationEnd = getNextDate($strProbationEnd, -1);
      $strPermanentAssign = "";
    }
    $arrFKR['employee_status'] = STATUS_PERMANENT;
  }
  if (isset($arrHouseOwnership[$arrCandidate['house_ownership']])) {
    $strHouse = $arrHouseOwnership[$arrCandidate['house_ownership']]['text'];
  } else {
    $strHouse = $arrCandidate['house_ownership_other'];
  }
  $transport = 0;
  $strSQL = "
      SELECT t1.*, t2.code as allowance_code
      FROM hrd_fkr_detail AS t1
      INNER JOIN hrd_allowance_type AS t2 ON t1.id_allowance_type = t2.id
      WHERE t1.id_fkr IN
      ( SELECT id FROM hrd_fkr WHERE id_candidate = '$strCandidate' )
      ;
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $fltAmount = ($row['amount_next'] == "" || $row['amount_next'] == 0) ? $row['amount_start'] : $row['amount_next'];
    if ($row['allowance_code'] == "tunjangan_transport") {
      $transport = $fltAmount;
    }
  }
  // $transport;
  // die();
  $strSQL = "
      INSERT INTO hrd_employee (
        id, employee_id, employee_name, gender,
        primary_address, primary_phone, primary_city,
        primary_zip, emergency_address, emergency_phone,
        birthplace, birthday, nationality,
        driver_license_a, driver_license_b, driver_license_c,
        id_card, email, passport, bank_branch,
        bank_account, bank_account_name,
        weight, height, religion_code, employee_status,
        id_company, division_code, department_code,
        section_code, sub_section_code,
        position_code, grade_code,
        join_date, due_date, permanent_date,
        house_status, marital_status,
        photo, family_status_code, active, created,
        education_level_code,transport,transport_fee,branch_code,nickname
      )
      VALUES (
        '$strEmployeeID',
        '" . addslashes($arrFKR['employee_id']) . "',
        '" . addslashes($arrFKR['employee_name']) . "',
        '" . $strGender . "',
        '" . addslashes($arrCandidate['current_address']) . "',
        '" . addslashes($strPhone) . "',
        '" . addslashes($arrCandidate['current_address_city']) . "',
        '" . addslashes($arrCandidate['current_address_zip']) . "',
        '" . addslashes($arrCandidate['permanent_address']) . "',
        '" . addslashes($arrCandidate['permanent_address_phone']) . "',
        '" . addslashes($arrCandidate['birthplace']) . "',
         " . handleDate($arrCandidate['birthdate']) . ",
        '" . addslashes($arrCandidate['nationality']) . "',
        '" . addslashes($arrCandidate['driver_license_a']) . "',
        '" . addslashes($arrCandidate['driver_license_b']) . "',
        '" . addslashes($arrCandidate['driver_license_c']) . "',
        '" . addslashes($arrCandidate['id_card']) . "',
        '" . addslashes($arrCandidate['email']) . "',
        '" . addslashes($arrCandidate['passport']) . "',
        '" . addslashes($arrFKR['bank']) . "',
        '" . addslashes($arrFKR['bank_account_no']) . "',
        '" . addslashes($arrFKR['bank_account_name']) . "',
         " . handleNull($arrCandidate['weight']) . ",
         " . handleNull($arrCandidate['height']) . ",
         " . handleNull($arrCandidate['religion_code']) . ",
         " . handleNull($arrFKR['employee_status']) . ",
         " . handleNull($arrFKR['id_company']) . ",
        '" . addslashes($arrFKR['division_code']) . "',
        '" . addslashes($arrFKR['department_code']) . "',
        '" . addslashes($arrFKR['section_code']) . "',
        '" . addslashes($arrFKR['sub_section_code']) . "',
        '" . addslashes($arrFKR['position_code']) . "',
        '" . addslashes($arrFKR['salary_grade_code']) . "',
         " . handleDate($strJoinDate) . ",
         " . handleDate($strDueDate) . ",
         " . handleDate($strPermanent) . ",
        '" . addslashes($strHouse) . "',
         " . handleNull($strMaritalStatus) . ",
         " . handleNull($arrCandidate['file_photo']) . ",
        '" . addslashes($arrFKR['family_status_code']) . "',
         '1', now(),
         " . handleNull($arrCandidate['education_level_code']) . ",
		 " . handleNull($arrCandidate['transport']) . ",
		 $transport,
		  '" . addslashes($arrFKR['branch_code']) . "',
		   '" . addslashes($arrCandidate['nickname']) . "'
      );
    ";
  $resExec = $db->execute($strSQL);
  if ($resExec == false) {
    $bolOK = false;
  }
  if ($bolOK) {
    // update juga data fkr, agar ada link ke id karyawan
    $strSQL = "
        UPDATE hrd_fkr SET id_employee = '$strEmployeeID'
        WHERE id = '$strFKR';
      ";
    $resExec = $db->execute($strSQL);
    if ($resExec == false) {
      $bolOK = false;
    }
  }
  if ($bolOK) {
    // update juga data keluarga
    $bolOK = copyFamily($db, $strCandidate, $strEmployeeID);
  }
  if ($bolOK) {
    // update juga data pendidikan
    $bolOK = copyEducation($db, $strCandidate, $strEmployeeID);
  }
  if ($bolOK) {
    // update juga data training
    $bolOK = copyTraining($db, $strCandidate, $strEmployeeID);
  }
  if ($bolOK) {
    // update juga data pengalaman kerja
    $bolOK = copyWork($db, $strCandidate, $strEmployeeID);
  }
  if ($bolOK) {
    // update juga data pengalaman kerja
    $bolOK = copySalary($db, $strCandidate, $strEmployeeID);
  }
  if ($bolOK) {
    // update juga status keluarga (pajak)
    // $bolOK = updateFamilyStatus($db, $strEmployeeID, $strGender, $strMaritalStatus);
  }
  if ($bolOK) {
    $db->execute("commit");
    $myDataGrid->message = getWords("Data Employee Succes  be created!");
    header("location:employee_edit.php?dataID=" . $strEmployeeID);
    exit();
  } else {
    $db->execute("rollback");
  }
  return $bolOK;
}

// buat tampilkan untuk link pembuatan data karyawan dari data FKR
function printEmployeeLink($params)
{
  extract($params);
  if ($record['status'] != REQUEST_STATUS_APPROVED && $record['status'] != getWords("approved")) {
    return "";
  }
  if ($record['id_employee'] == "") {
    if ($record['employee_id'] == "") // belum diisi niknya
    {
      $str = "<a href=\"javascript:alert('Employee ID Does not Exists')\">" . getWords('create employee') . "</a>";
    } else {
      $str = "<a onclick=\"return confirm('Do you want to create Employee based on this FKR?');\" href='fkr_list.php?btnCreateEmployee=true&dataID=" . $record['id'] . "'>" . getWords(
              'create employee'
          ) . "</a>";
    }
  } else {
    $str = "<a href='employee_edit.php?dataID=" . $record['id_employee'] . "'>" . getWords("view") . "</a>";
  }
  return $str;
}

function printViewLink($params)
{
  extract($params);
  return "<a href='fkr_edit.php?dataID=" . $record['id'] . "'>" . $value . "</a>";
}

function printEditLink($params)
{
  extract($params);
  $bolOK = false;
  if ($record['salary_grade_code'] == "") {
    $bolOK = true;
  } else {
    $bolOK = (isBandAccess($record['salary_grade_code']));
  }
  $str = ($bolOK) ? "<a href='fkr_edit.php?dataID=" . $record['id'] . "'>" . getWords('edit') . "</a>" : "";
  return $str;
}

function printPrintLink($params)
{
  extract($params);
  return "<a href=\"javascript:openWindowDialog('fkr_print.php?dataID=" . $record['id'] . "')\">" . getWords(
      'print'
  ) . "</a>";
}

// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $tbl = new cModel("hrd_fkr");
  if ($tbl->deleteMultiple($arrKeys)) {
    $myDataGrid->message = $tbl->strMessage;
  } else {
    $myDataGrid->errorMessage = $tbl->strMessage;
  }
} //deleteData
// handle cara menampilkan jenis status karyawan
function printEmployeeStatus1($params)
{
  extract($params);
  global $ARRAY_EMPLOYEE_STATUS;
  if ($value == STATUS_PERMANENT) {
    return getWords("permanent");
  } else {
    return getWords("contract");
  }
  /*
  if (isset($ARRAY_EMPLOYEE_STATUS[$value]))
    return getWords($ARRAY_EMPLOYEE_STATUS[$value]);
  else
    return "";
    */
}

// handle approve/unapprove
function approveData()
{
  global $myDataGrid;
  global $objUP;
  if (!$objUP->isManagerHR()) {
    return false;
  }
  $i = 0;
  $tbl = new cModel("hrd_fkr");
  foreach ($myDataGrid->checkboxes as $strValue) {
    $i++;
    $strSQL = "
        UPDATE hrd_fkr
        SET status = '" . REQUEST_STATUS_APPROVED . "', approved1 = now(),
          approved1_by = '" . $_SESSION['sessionUserID'] . "'
        WHERE id = '" . $strValue . "';
        UPDATE hrd_recruitment_need SET number_ok = number_ok + 1 
        WHERE id IN (
          SELECT id_recruitment_need FROM hrd_fkr WHERE id = '$strValue'
        ) AND NOT (number_ok is null);
        UPDATE hrd_recruitment_need SET number_ok = 1 
        WHERE id IN (
          SELECT id_recruitment_need FROM hrd_fkr WHERE id = '$strValue'
        ) AND number_ok is null;
      ";
    $tbl->execute($strSQL);
  }
}

// handle approve/unapprove
function unApproveData()
{
  global $myDataGrid;
  global $objUP;
  if (!$objUP->isManagerHR()) {
    return false;
  }
  $i = 0;
  $tbl = new cModel("hrd_fkr");
  foreach ($myDataGrid->checkboxes as $strValue) {
    $i++;
    $strSQL = "
        UPDATE hrd_fkr
        SET status = '" . REQUEST_STATUS_NEW . "', approved1 = null,
          approved1_by = null
        WHERE id = '" . $strValue . "' AND status <> '" . REQUEST_STATUS_NEW . "';
        
        UPDATE hrd_recruitment_need SET number_ok = 0
        WHERE id IN (
          SELECT id_recruitment_need FROM hrd_fkr WHERE id = '$strValue'
        );
        UPDATE hrd_recruitment_need SET number_ok = fkr.total
        FROM (
          SELECT COUNT(id) AS total, id_recruitment_need FROM hrd_fkr
          WHERE id_recruitment_need IN (
            SELECT id_recruitment_need FROM hrd_fkr WHERE id = '$strValue'
            AND status = '" . REQUEST_STATUS_APPROVED . "'
          )
          GROUP BY id_recruitment_need
        ) AS fkr 
        WHERE fkr.id_recruitment_need = hrd_recruitment_need.id
          AND hrd_recruitment_need.id IN (
          SELECT id_recruitment_need FROM hrd_fkr WHERE id = '$strValue'
        );
          
      ";
    $tbl->execute($strSQL);
  }
}

?>