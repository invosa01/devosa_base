<?php
include_once('../global/session.php');
include_once('../global.php');
include_once('../includes/date/date.php');
include_once('../includes/form2/form2.php');
include_once('../global/common_data.php');
include_once('../global/common_function.php');
/*
-----------------------------------------------------------------------------------
Note:
Datagrid dibawah ini di adopsi dari datagrid Smart-U
datagrid lama sengaja tidak di lakukan perubahan karena dikhawatirkan
ada modul-modul yang lain yang menggunakan datagrid tersebut sehingga  bisa terjadi
DisFunction Aplicattion), datagrid dibawah ini hanya dipakai untuk otomatisasi
report html dengan datagrid.
------------------------------------------------------------------------------------
*/
include_once('../includes/datagrid/datagridupdateforrecruitment.php');
//---------------END ---------------------------------------------------------------
// getDataPrivileges ada di '../global.php'
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
//$dataPrivilege = getDataPrivileges("report_recruitment_dept.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$arrData = [];
$arrData['reference'] = (isset($_POST['reference'])) ? $_POST['reference'] : "";
$arrData['position_code'] = (isset($_POST['position_code'])) ? $_POST['position_code'] : "";
$emptyData = ["value" => "", "text" => ""];
$DataGrid = "";
$strGridTitle = "";
$ViewRefGroup = "";
$f = new clsForm("formInput", 1, "100%", "");
$f->caption = getWords("Candidate By Job Reference");
//$f->addHelp(getWords("help for")." ".$dataPrivilege['menu_name'], getHelps("master user"), 8, 167, 400, 300);
$f->addInput(getWords("date from"), "data_date_from", date($_SESSION['sessionDateSetting']['php_format']), [], "date");
$f->addInput(getWords("date thru"), "data_date_thru", date($_SESSION['sessionDateSetting']['php_format']), [], "date");
$f->addSelect(
    getWords("reference"),
    "reference",
    getDataListJobReference($arrData['reference'], true, $emptyData),
    [],
    "string",
    true,
    true,
    true
);
$f->addInput(getWords("job posting date"), "data_job_date", "", [], "date", false);
$f->addSelect(
    getWords("position"),
    "position_code",
    getDataListPosition($arrData['position_code'], true, $emptyData),
    [],
    "string",
    false,
    true,
    true
);
$f->addSubmit(
    "btnPreview",
    getWords("show report"),
    ["onClick" => "document.formInput.target = '';"],
    true,
    true,
    "",
    "",
    "previewData"
);
$f->addSubmit(
    "btnPrint",
    getWords("print report"),
    ["onClick" => "javascript:printList()"],
    true,
    true,
    "",
    "",
    "previewData"
);
$f->addSubmit(
    "btnExcel",
    getWords("export excel"),
    ["onClick" => "document.formInput.target = '';"],
    true,
    true,
    "",
    "",
    "exportExcel"
);
$formInput = $f->render();
$tbsPage = new clsTinyButStrong;
//write this variable in every page
//$strPageTitle = getWords("candidate report by job reference");//$dataPrivilege['menu_name'];
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == '') {
  $dataPrivilege['icon_file'] = 'blank.png';
}
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('candidate report by reference page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = "templates/report_recruitment_dept.html";
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
// ambil jenis jabatan, sesuai permintaan recruitment
function getPositionDataList($default = null, $isHasEmpty = false, $emptyData = null)
{
  $tbl = new cModel("hrd_recruitment_need", getWords("recruitment need"));
  $arrResult = [];
  if ($isHasEmpty) {
    if ($emptyData == null) {
      $arrResult[] = ["value" => "", "text" => ""];
    } else {
      $arrResult[] = $emptyData;
    }
  }
  //$arrData = $tbl->generateList("status <> ".REQUEST_STATUS_DENIED, "position", null, "distinct position", "position", $isHasEmpty, $emptyData);
  $arrData = $tbl->findAll("status <> " . REQUEST_STATUS_DENIED, "distinct position", "position");
  foreach ($arrData AS $arrD) {
    $arrResult[] = ["text" => $arrD['position'], "value" => $arrD['position']];
  }
  if ($default != null || $default != "") {
    while (list($key, $val) = each($arrResult)) {
      $temp = &$arrData[$key];
      if ($val['value'] == $default) {
        $temp['selected'] = true;
      } else {
        $temp['selected'] = false;
      }
    }
  }
  return $arrResult;
}

// ambil jenis referensi lamaran kerja
function getDataListJobReference($default = null, $isHasEmpty = false, $emptyData = null)
{
  $tbl = new cModel("hrd_candidate_reference", getWords("reference"));
  $arrType = $tbl->findAll("", "type, name", "", null, 1, "type"); // jenis-jenis tipe
  $arrResult = [];
  if ($isHasEmpty) {
    if ($emptyData == null) {
      $arrResult[] = ["value" => "", "text" => ""];
    } else {
      $arrResult[] = $emptyData;
    }
  }
  //$arrData = $tbl->generateList("status <> ".REQUEST_STATUS_DENIED, "position", null, "distinct position", "position", $isHasEmpty, $emptyData);
  $tbl = new cModel("hrd_candidate", getWords("reference"));
  $arrData = $tbl->findAll("", "distinct reference, reference_type", "reference_type");
  foreach ($arrData AS $arrD) {
    $strTypeName = (isset($arrType[$arrD['reference_type']])) ? $arrType[$arrD['reference_type']]['name'] : "Other";
    $arrResult[] = [
        "value" => $arrD['reference_type'] . "##" . $arrD['reference'],
        "text"  => $strTypeName . " - " . $arrD['reference']
    ];
  }
  // tambahkan pilihan lain-lain
  if ($default != null || $default != "") {
    while (list($key, $val) = each($arrResult)) {
      $temp = &$arrData[$key];
      if ($val['value'] == $default) {
        $temp['selected'] = true;
      } else {
        $temp['selected'] = false;
      }
    }
  }
  return $arrResult;
}

// fungsi untuk menampilkan data
function previewData()
{
  global $f;
  global $DataGrid;
  global $strGridTitle;
  //global $ViewRefGroup;
  $ViewRefGroup = "";
  $db = new CdbClass;
  $db->connect();
  $bolPrint = false;
  if (isset($_POST['btnPrint'])) {
    $bolPrint = true;
  }
  $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, false);
  $myDataGrid->caption = strtoupper(getWords("Candidate By Job Reference"));
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  //$myDataGrid->groupBy("id_employee");
  //$myDataGrid->hasGrandTotal = true;
  if ($bolPrint) {
    $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_PRINT_HTML;
  }
  $myDataGrid->addColumnNumbering(
      new DataGrid_Column("No", "", ['width' => 30], ['nowrap' => ''], false, false, "", "", "numeric", true, 5, false)
  );//, strtoupper(getWords("rekapitulation")), true));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("date"),
          "application_date",
          ['width' => 60],
          ["align" => "center"],
          false,
          false,
          "",
          "",
          "string",
          true,
          32,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("candidate name"),
          "candidate_name",
          [],
          ['nowrap' => 'nowrap'],
          false,
          false,
          "",
          "",
          "string",
          true,
          12,
          false
      )
  );//, strtoupper(getWords("rekapitulation")), true));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("education"),
          "education",
          [],
          ['nowrap' => 'nowrap'],
          false,
          false,
          "",
          "",
          "string",
          true,
          16,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("position"),
          "position",
          [],
          ['nowrap' => 'nowrap'],
          false,
          false,
          "",
          "",
          "string",
          true,
          16,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("invitation"),
          "invitation_date",
          ['width' => 60],
          ['align' => 'center'],
          false,
          false,
          "",
          "",
          "string",
          true,
          10,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("work experience"),
          "experience",
          [],
          [],
          false,
          false,
          "",
          "",
          "string",
          true,
          32,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("note"), "note", [], [], false, false, "", "", "string", true, 32, false)
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("job posting date"),
          "job_reference_date",
          ['width' => 60],
          ["align" => "center"],
          false,
          false,
          "",
          "",
          "string",
          true,
          32,
          false
      )
  );
  //    $myDataGrid->addColumn(new DataGrid_Column(getWords("date required"), "due_date", array('width' => 90), array('align' => 'center'), false, false, "", "", "string", true, 10, false));
  //$myDataGrid->groupBy("Department");
  $myDataGrid->getRequest();
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  //get data attendance
  $strPosCriteria = (($strPos = $f->getValue('position_code')) == "") ? "" : "AND UPPER(\"position\") = '" . strtoupper(
          $strPos
      ) . "' ";
  $strRefCriteria = "";
  if (($strRef = $f->getValue('reference')) != "") {
    list($strRefType, $strRef) = explode("##", $strRef);
    $myDataGrid->caption .= " - " . $strRef;
    $strRefCriteria = "
        AND reference_type = '$strRefType' AND upper(reference) = '" . strtoupper($strRef) . "'
      ";
  }
  if (($strJobDate = $f->getValue("data_job_date")) != "") {
    $strRefCriteria .= " AND job_reference_date = '$strJobDate' ";
  }
  $strGridTitle = "
      <table width='100%' border=0 cellpadding=1 cellspacing=0 style='font-size: 10pt; font-weight: bold'>
        <tr>
          <td colspan=3 style='font-size: 12pt'>" . strtoupper(getWords($myDataGrid->caption)) . "</td>
        </tr>";
  if ($f->getValue("data_date_from") == $f->getValue("data_date_thru")) {
    $strGridTitle .= "
        <tr>
          <td>" . getWords("date") . "</td>
          <td width=10>:</td>
          <td>" . $f->getValue("data_date_from") . "</td>
        </tr>
      </table>";
  } else {
    $strGridTitle .= "
        <tr>
          <td width=80>" . getWords("period") . "</td>
          <td width=10>:</td>
          <td>" . $f->getValue("data_date_from") . "  " . getWords("to") . "  " . $f->getValue("data_date_thru") . "</td>
        </tr>
      </table>";
  }
  $arrExp = []; // data experience candidate
  $strSQL = "
      SELECT * FROM hrd_candidate_working_experience
      WHERE id_candidate IN
      (
        SELECT id FROM hrd_candidate 
        WHERE application_date BETWEEN '" . $f->getValue('data_date_from') . "' AND '" . $f->getValue(
          'data_date_thru'
      ) . "'
        $strPosCriteria $strRefCriteria
      )
      ORDER BY start_year, start_month, start_day
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    if ($row['company_name'] != "") {
      $arrExp[$row['id_candidate']][] = $row;
    }
  }
  $arrEdu = []; // data pendidikan terakhir
  $strSQL = "
      SELECT * FROM hrd_candidate_education
      WHERE id_candidate IN
      (
        SELECT id FROM hrd_candidate 
        WHERE application_date BETWEEN '" . $f->getValue('data_date_from') . "' AND '" . $f->getValue(
          'data_date_thru'
      ) . "'
        $strPosCriteria $strRefCriteria
      )
      ORDER BY year_from
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    if ($row['academic'] != "" && $row['major'] != "") {
      $arrEdu[$row['id_candidate']] = $row['major'];
    }
  }
  // data utama
  $strSQL = "
      SELECT tc.*, trp.invitation_date, trp.note, te.name AS education 
      FROM (
        SELECT * FROM hrd_candidate 
        WHERE application_date BETWEEN '" . $f->getValue('data_date_from') . "' AND '" . $f->getValue(
          'data_date_thru'
      ) . "'
        $strPosCriteria $strRefCriteria
      ) AS tc
      LEFT JOIN (
        SELECT * FROM hrd_recruitment_process
        WHERE invitation_date >= '" . $f->getValue('data_date_from') . "'
      ) AS trp ON tc.id = trp.id_candidate
      LEFT JOIN hrd_education_level AS te ON tc.education_level_code = te.code
      ORDER BY tc.application_date
    ";
  //$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
  $dataset = $myDataGrid->getData($db, $strSQL);
  foreach ($dataset as &$rowDb) {
    $rowDb['experience'] = "";
    if (isset($arrExp[$rowDb['id']])) {
      foreach ($arrExp[$rowDb['id']] AS $x => $row) {
        if ($rowDb['experience'] != "") {
          $rowDb['experience'] .= " <br /> \n";
        }
        $rowDb['experience'] .= "* " . $row['position_start'] . " (" . $row['company_name'] . ")";
      }
    }
    if (isset($arrEdu[$rowDb['id']])) {
      $rowDb['education'] = $arrEdu[$rowDb['id']];
    }
  }
  $myDataGrid->totalData = count($dataset);
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
}

// fungsi untuk menampilkan data
function exportExcel()
{
  global $f;
  global $DataGrid;
  global $strGridTitle;
  //global $ViewRefGroup;
  $ViewRefGroup = "";
  $db = new CdbClass;
  $db->connect();
  $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, false);
  $myDataGrid->caption = strtoupper(getWords("Candidate By Job Reference"));
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  //$myDataGrid->groupBy("id_employee");
  //$myDataGrid->hasGrandTotal = true;
  $myDataGrid->addColumnNumbering(
      new DataGrid_Column("No", "", ['width' => 30], ['nowrap' => ''], false, false, "", "", "numeric", true, 5, false)
  );//, strtoupper(getWords("rekapitulation")), true));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("date"),
          "application_date",
          ['width' => 60],
          ["align" => "center"],
          false,
          false,
          "",
          "",
          "string",
          true,
          32,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("candidate name"),
          "candidate_name",
          [],
          ['nowrap' => 'nowrap'],
          false,
          false,
          "",
          "",
          "string",
          true,
          12,
          false
      )
  );//, strtoupper(getWords("rekapitulation")), true));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("education"),
          "education",
          [],
          ['nowrap' => 'nowrap'],
          false,
          false,
          "",
          "",
          "string",
          true,
          16,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("position"),
          "position",
          [],
          ['nowrap' => 'nowrap'],
          false,
          false,
          "",
          "",
          "string",
          true,
          16,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("invitation"),
          "invitation_date",
          ['width' => 60],
          ['align' => 'center'],
          false,
          false,
          "",
          "",
          "string",
          true,
          10,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("work experience"),
          "experience",
          [],
          [],
          false,
          false,
          "",
          "",
          "string",
          true,
          32,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("note"), "note", [], [], false, false, "", "", "string", true, 32, false)
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("job posting date"),
          "job_reference_date",
          ['width' => 60],
          ["align" => "center"],
          false,
          false,
          "",
          "",
          "string",
          true,
          32,
          false
      )
  );
  //    $myDataGrid->addColumn(new DataGrid_Column(getWords("date required"), "due_date", array('width' => 90), array('align' => 'center'), false, false, "", "", "string", true, 10, false));
  //$myDataGrid->groupBy("Department");
  $myDataGrid->getRequest();
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  //get data attendance
  $strPosCriteria = (($strPos = $f->getValue(
          'position_code'
      )) == "") ? "" : "AND UPPER(\"position_code\") = '" . strtoupper($strPos) . "' ";
  $strRefCriteria = "";
  if (($strRef = $f->getValue('reference')) != "") {
    list($strRefType, $strRef) = explode("##", $strRef);
    $myDataGrid->caption .= " - " . $strRef;
    $strRefCriteria = "
        AND reference_type = '$strRefType' AND upper(reference) = '" . strtoupper($strRef) . "'
      ";
  }
  if (($strJobDate = standardDateToSQLDateNew($f->getValue("data_job_date"), $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day'])) != "") {
    $strRefCriteria .= " AND job_reference_date = '$strJobDate' ";
  }
  $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
  $myDataGrid->strFileNameXLS = "report_recruitment_reference.xls";
  $myDataGrid->strTitle1 = strtoupper(getWords($myDataGrid->caption));
  if ($f->getValue("data_date_from") == $f->getValue("data_date_thru")) {
    $myDataGrid->strTitle2 = getWords("date") . " : " . $f->getValue("data_date_from");
  } else {
    $myDataGrid->strTitle2 = getWords("period") . " : " . $f->getValue("data_date_from") . " - " . $f->getValue(
            "data_date_thru"
        );
  }
  $arrExp = []; // data experience candidate
  $strSQL = "
      SELECT * FROM hrd_candidate_working_experience
      WHERE id_candidate IN
      (
        SELECT id FROM hrd_candidate 
        WHERE application_date BETWEEN '" . standardDateToSQLDateNew($f->getValue('data_date_from'), $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']) . "' AND '" . standardDateToSQLDateNew($f->getValue('data_date_thru'), $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']) . "'
        $strPosCriteria $strRefCriteria
      )
      ORDER BY start_year, start_month, start_day
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    if ($row['company_name'] != "") {
      $arrExp[$row['id_candidate']][] = $row;
    }
  }
  $arrEdu = []; // data pendidikan terakhir
  $strSQL = "
      SELECT * FROM hrd_candidate_education
      WHERE id_candidate IN
      (
        SELECT id FROM hrd_candidate 
        WHERE application_date BETWEEN '" . standardDateToSQLDateNew($f->getValue('data_date_from'), $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']) . "' AND '" . standardDateToSQLDateNew($f->getValue('data_date_thru'), $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']) . "'
        $strPosCriteria $strRefCriteria
      )
      ORDER BY year_from
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    if ($row['academic'] != "" && $row['major'] != "") {
      $arrEdu[$row['id_candidate']] = $row['major'];
    }
  }
  // data utama
  $strSQL = "
      SELECT tc.*, trp.invitation_date, trp.note, te.name AS education 
      FROM (
        SELECT * FROM hrd_candidate 
        WHERE application_date BETWEEN '" . standardDateToSQLDateNew($f->getValue('data_date_from'), $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']) . "' AND '" . standardDateToSQLDateNew($f->getValue('data_date_thru'), $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']) . "'
        $strPosCriteria $strRefCriteria
      ) AS tc
      LEFT JOIN (
        SELECT * FROM hrd_recruitment_process
        WHERE invitation_date >= '" . standardDateToSQLDateNew($f->getValue('data_date_from'), $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']) . "'
      ) AS trp ON tc.id = trp.id_candidate
      LEFT JOIN hrd_education_level AS te ON tc.education_level_code = te.code
      ORDER BY tc.application_date
    ";
  //$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
  $dataset = $myDataGrid->getData($db, $strSQL);
  foreach ($dataset as &$rowDb) {
    $rowDb['experience'] = "";
    if (isset($arrExp[$rowDb['id']])) {
      foreach ($arrExp[$rowDb['id']] AS $x => $row) {
        if ($rowDb['experience'] != "") {
          $rowDb['experience'] .= " <br /> \n";
        }
        $rowDb['experience'] .= "* " . $row['position_start'] . " (" . $row['company_name'] . ")";
      }
    }
    if (isset($arrEdu[$rowDb['id']])) {
      $rowDb['education'] = $arrEdu[$rowDb['id']];
    }
  }
  $myDataGrid->totalData = count($dataset);
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
}

function printEmployeeStatus($params)
{
  extract($params);
  global $ARRAY_EMPLOYEE_STATUS;
  if (isset($ARRAY_EMPLOYEE_STATUS[$value])) {
    return getWords($ARRAY_EMPLOYEE_STATUS[$value]);
  } else {
    return "";
  }
}

function printFormatDouble($params)
{
  extract($params);
  if ($value != '') {
    return number_format($value, 2);
  } else {
    return "";
  }
}

?>