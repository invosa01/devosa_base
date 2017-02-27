<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/date/date.php');
include_once('../includes/form2/form2.php');
include_once('../global/common_data.php');
include_once('../global/cls_date.php');
/*
-----------------------------------------------------------------------------------
Note:
Datagrid dibawah ini di adopsi dari datagrid Smart-U
datagrid lama sengaja tidak di lakukan perubahan karena dikahawatirkan
ada modul-modul yang lain yang menggunakan datagrid lama sehingga  bisa terjadi
DisFunction Aplicattion)
------------------------------------------------------------------------------------
*/
include_once('../includes/datagrid/datagridupdateforrecruitment.php');
//---------------END ---------------------------------------------------------------
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
$objDt = new clsCommonDate();
$DataGrid = "";
$strGridTitle = "";
$emptyData = ["value" => "", "text" => ""];
$f = new clsForm("formInput", 1, "100%", "");
$f->caption = getWords("report criteria");
//$f->addHelp(getWords("help for")." ".$dataPrivilege['menu_name'], getHelps("master user"), 8, 167, 400, 300);
$f->addInput(getWords("date from"), "data_date_from", date($_SESSION['sessionDateSetting']['php_format']), [], "date");
$f->addInput(getWords("date thru"), "data_date_thru", date($_SESSION['sessionDateSetting']['php_format']), [], "date");
$arrReportType = [];
$arrReportType[] = ["value" => "1", "text" => getWords("detail"), "checked" => true];
$arrReportType[] = ["value" => "2", "text" => getWords("summary"), "checked" => false];
$f->addSelect(
    getWords("company"),
    "id_company",
    getDataListCompany("", true, $emptyData),
    [],
    "string",
    false,
    true,
    true
);
$f->addSelect(
    getWords("division"),
    "division_code",
    getDataListDivision("", true, $emptyData, $objUP->genFilterDivision()),
    [],
    "string",
    false,
    true,
    true
);
$f->addSelect(
    getWords("department"),
    "department_code",
    getDataListDepartment("", true, $emptyData, $objUP->genFilterDivision() . $objUP->genFilterDepartment()),
    [],
    "string",
    false,
    true,
    true
);
$arrStatus = [];
$arrStatus[] = ["value" => "0", "text" => getWords("all"), "selected" => true];
$arrStatus[] = ["value" => "1", "text" => getWords("outstanding"), "checked" => false];
$arrStatus[] = ["value" => "2", "text" => getWords("done"), "checked" => false];
$f->addSelect(getWords("mrf status"), "dataStatus", $arrStatus, [], "string", false, true, true);
$f->addRadio(getWords("report type"), "report_type", $arrReportType, [], "string");
$f->addHidden("company_name", ""); // sekedar nyimpan nama company
$f->addSubmit(
    "btnPreview",
    getWords("show report"),
    ["onClick" => "document.formInput.target = ''; return validInput();"],
    true,
    true,
    "",
    "",
    "previewData"
);
$f->addSubmit("btnPrint", getWords("print report"), ["onClick" => "printList()"], true, true, "", "", "previewData");
$f->addSubmit(
    "btnExcel",
    getWords("export excel"),
    ["onClick" => "document.formInput.target = '';return validInput();"],
    true,
    true,
    "",
    "",
    "exportExcel"
);
$formInput = $f->render();
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == '') {
  $dataPrivilege['icon_file'] = 'blank.png';
}
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('report mrf page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = "templates/report_mrf.html";
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
// fungsi untuk mengambil data kandidate dari FKR, berdasar nomor MRF
// input tanggal awal
// output : array daftar kandidat + join date, dengan index adalah nomor MRF (asumsi yang diterima cuma 1)
function getCandidateFKR($strDateFrom)
{
  $kriteria = ($strDateFrom == "") ? "" : "AND join_date >= '$strDateFrom' ";
  $arrResult = [];
  $tbl = new cModel("hrd_fkr", "fkr");
  // $arrResult = $tbl->findAll($kriteria, "*", "", null, 1, "id_recruitment_need");
  $strSQL = "SELECT * FROM hrd_fkr where 1=1 $kriteria";
  $rowDb = $tbl->query($strSQL);
  $result = [];
  foreach ($rowDb as $list) {
    if (!isset($result[$list['id_recruitment_need']])) {
      $result[$list['id_recruitment_need']] = [];
    }
    $result[$list['id_recruitment_need']][] = $list;
  }
  return $result;
}

function previewData()
{
  global $f;
  global $DataGrid;
  global $strGridTitle;
  global $objDt;
  global $objUP;
  $db = new CdbClass;
  $db->connect();
  $isSummary = false;
  if ($f->getValue("report_type") == 2) {
    $isSummary = true;
  }
  $bolPrint = false;
  if (isset($_REQUEST['btnPrint'])) {
    $bolPrint = true;
  }
  $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, false);
  $myDataGrid->caption = strtoupper(getWords("report mrf"));
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  $strDivision = $f->getValue("division_code");
  $strDepartment = $f->getValue("department_code");
  $strPeriodText = getWords("date");
  $strPeriod = $f->getValue("data_date_from");
  if ($f->getValue("data_date_from") != $f->getValue("data_date_thru")) {
    $strPeriod .= " " . getWords("to") . " " . $f->getValue("data_date_thru");
    $strPeriodText = getWords("period");
  }
  $strGridTitle = "
      <table width='100%' border=0 cellpadding=1 cellspacing=0 style='font-size: 10pt; font-weight: bold'>
        <tr>
          <td colspan=3 style='font-size: 12pt'>" . strtoupper(getWords("report mrf")) . "</td>
        </tr>
        <tr>
          <td width=80>" . $strPeriodText . "</td>
          <td width=10>:</td>
          <td>" . $strPeriod . "</td>
        </tr>
      </table>
    ";
  $strDiv = $objUP->genFilterDivision();
  $strComp = ($f->getValue("id_company") == "") ? "" : "AND id_company = '" . $f->getValue(
          "id_company"
      ) . "' "; // kriteria company
  $strComp .= $objUP->genFilterDepartment();
  if ($strDivision != "") {
    $strComp .= "
      AND department_code IN (
        select department_code from hrd_department 
        where division_code = '$strDivision'
      )
    ";
  }
  if ($strDiv != "") {
    $strComp .= "
      AND department_code IN (
        select department_code from hrd_department 
        where 1=1 $strDiv
      )
    ";
  }
  if ($strDepartment != "") {
    $strComp .= "
      AND department_code = '$strDepartment'
    ";
  }
  // sebelumnya, update dulu jumlah yang sudah dipenuhi, berdasar jumlah FKR
  $strSQL = "
      UPDATE hrd_recruitment_need SET number_ok = 0
      WHERE approved_time BETWEEN '" . toSQLDate($f->getValue('data_date_from')) . "' AND '" . toSQLDate($f->getValue('data_date_thru')) . "'
        AND status = '" . REQUEST_STATUS_APPROVED . "' ;
      UPDATE hrd_recruitment_need SET number_ok = fkr.total
      FROM (
        SELECT COUNT(id) AS total, id_recruitment_need FROM hrd_fkr
        WHERE join_date >= '" . toSQLDate($f->getValue('data_date_from')) . "'
          AND status = '" . REQUEST_STATUS_APPROVED . "'
        GROUP BY id_recruitment_need
      ) AS fkr 
      WHERE fkr.id_recruitment_need = hrd_recruitment_need.id
        AND hrd_recruitment_need.approved_time BETWEEN '" . toSQLDate($f->getValue('data_date_from')) . "' AND '" . toSQLDate($f->getValue('data_date_thru')) . "'
        AND hrd_recruitment_need.status = '" . REQUEST_STATUS_APPROVED . "' ;
    ";
  $resExec = $db->execute($strSQL);
  $strCriteriaStatus = ""; // kriteria untuk status penyelesaian MRF
  if ($f->getValue("dataStatus") == 1) // outstanding
  {
    $strCriteriaStatus = "AND (number_ok = 0 OR number_ok is null) ";
  } else if ($f->getValue("dataStatus") == 2) // done
  {
    $strCriteriaStatus = "AND number_ok > 0 ";
  }
  if ($bolPrint) {
    $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_PRINT_HTML;
    echo "<script>window.print();</script>";
  }
  $arrCandidate = getCandidateFKR(toSQLDate($f->getValue('data_date_from'))); // ambil data kandidat berdasar fkr
  if ($isSummary) {
    $myDataGrid->addColumnNumbering(
        new DataGrid_Column(
            "No.",
            "",
            ['width' => 30],
            ['nowrap' => ''],
            false,
            false,
            "",
            "",
            "numeric",
            true,
            5,
            false
        )
    );//, strtoupper(getWords("rekapitulation")), true));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("departement"),
            "department_name",
            ['nowrap' => ''],
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
            getWords("position"),
            "position",
            ['width' => 150],
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
            getWords("total needed"),
            "total_number",
            ['width' => 70],
            ["align" => "right"],
            false,
            false,
            "",
            "",
            "string",
            true,
            8,
            false
        )
    );
    $strSQL = "
        SELECT t_rec.department_code, t_rec.position, t_dep.department_name,
          SUM(t_rec.number) AS total_number 
        FROM (
          SELECT * FROM hrd_recruitment_need
          WHERE approved_time BETWEEN '" . toSQLDate($f->getValue('data_date_from')) . "' AND '" . toSQLDate($f->getValue('data_date_thru')) . "'
            AND status = '" . REQUEST_STATUS_APPROVED . "'
            $strComp
            $strCriteriaStatus
        ) AS t_rec
        LEFT JOIN hrd_company AS t_com ON t_rec.id_company = t_com.id
        LEFT JOIN hrd_department AS t_dep ON t_rec.department_code = t_dep.department_code
        GROUP BY t_rec.department_code, t_rec.position, t_dep.department_name
        ORDER BY t_rec.department_code, t_rec.position
      ";
  } else {
    $myDataGrid->addColumnNumbering(
        new DataGrid_Column(
            "No", "", ['width' => 30], ['nowrap' => ''], false, false, "", "", "numeric", true, 5, false
        )
    );//, strtoupper(getWords("rekapitulation")), true));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "MRF",
            "request_number",
            ['nowrap' => ''],
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
            getWords("departement"),
            "department_name",
            ['nowrap' => ''],
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
            getWords("company"),
            "company_name",
            ['width' => 100],
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
            getWords("position"),
            "position_name",
            ['width' => 150],
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
            getWords("user"),
            "user_name",
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
            getWords("total needed"),
            "number",
            ['width' => 70],
            ["align" => "right"],
            false,
            false,
            "",
            "",
            "string",
            true,
            8,
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("qualification"),
            "qualification",
            ['width' => 200],
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
        new DataGrid_Column(
            getWords("job description"),
            "description",
            ['width' => 200],
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
        new DataGrid_Column(
            getWords("date received"),
            "approved_time",
            ['width' => 90],
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
            getWords("date required"),
            "due_date",
            ['width' => 90],
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
            getWords("created"),
            "created",
            ['width' => 120],
            ['align' => 'center'],
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
            getWords("candidate"),
            "candidate",
            [],
            ['align' => 'center', 'nowrap' => 'nowrap'],
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
    // $myDataGrid->addColumn(new DataGrid_Column(getWords("join date"), "join_date", array(), array('align' => 'center', 'nowrap' => 'nowrap'), false, false, "", "", "string", true, 16, false));
    $myDataGrid->getRequest();
    $strSQL = "
        SELECT t_rec.*, t_com.company_name , t_dep.department_name, t_pos.position_name
        FROM (
          SELECT * FROM hrd_recruitment_need
          WHERE approved_time BETWEEN '" . toSQLDate($f->getValue('data_date_from')) . "' AND '" . toSQLDate($f->getValue('data_date_thru')) . "'
            AND status = '" . REQUEST_STATUS_APPROVED . "'
            $strComp
            $strCriteriaStatus
        ) AS t_rec
        LEFT JOIN hrd_company AS t_com ON t_rec.id_company = t_com.id
        LEFT JOIN hrd_department AS t_dep ON t_rec.department_code = t_dep.department_code
		LEFT JOIN hrd_position AS t_pos ON t_rec.position_code = t_pos.position_code
      ";
  }
  $dataset = $myDataGrid->getData($db, $strSQL);
  foreach ($dataset as &$row) {
    $row['received_date'] = "";
    $row['candidate'] = ""; // kandidat yang diterima
    $row['join_date'] = ""; // tanggal bergabung
    if (!$isSummary && isset($arrCandidate[$row['id']])) {
      $strCandidateList = "";
      foreach ($arrCandidate[$row['id']] as $list) {
        $strCandidateList .= $list['employee_name'] . ", " . getWords("start") . " : " . $objDt->getDateFormat(
                $list['join_date'],
                "d M Y"
            ) . "<br>";
      }
      $row['candidate'] = $strCandidateList;
    }
    if (isset($row['approved_time'])) {
      $row['approved_time'] = $objDt->getDateFormat(substr($row['approved_time'], 0, 10), "d M Y");
    }
    if (isset($row['due_date'])) {
      $row['due_date'] = $objDt->getDateFormat($row['due_date'], "d M Y");
    }
    if (isset ($row['created'])) {
      $row['created'] = substr($row['created'], 0, 19);
    }
  }
  $myDataGrid->totalData = count($dataset);
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
}

function exportExcel()
{
  global $f;
  global $DataGrid;
  global $strGridTitle;
  global $objDt;
  $isSummary = false;
  if ($f->getValue("report_type") == 2) {
    $isSummary = true;
  }
  $db = new CdbClass;
  $db->connect();
  $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, false);
  $myDataGrid->caption = strtoupper(getWords("report mrf"));
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  $strDivision = $f->getValue("division_code");
  $strDepartment = $f->getValue("department_code");
  $strComp = ($f->getValue("id_company") == "") ? "" : "AND t_rec.id_company = '" . $f->getValue(
          "id_company"
      ) . "' "; // kriteria company
  if ($strDivision != "") {
    $strComp .= "
      AND t_rec.department_code IN (select department_code from hrd_department where division_code = '$strDivision')
    ";
  }
  if ($strDepartment != "") {
    $strComp .= "
      AND t_rec.department_code = '$strDepartment'
    ";
  }
  $arrCandidate = getCandidateFKR(toSQLDate($f->getValue('data_date_from'))); // ambil data kandidat berdasar fkr
  if ($isSummary) {
    $myDataGrid->addColumnNumbering(
        new DataGrid_Column(
            "No.",
            "",
            ['width' => 30],
            ['nowrap' => ''],
            false,
            false,
            "",
            "",
            "numeric",
            true,
            5,
            false
        )
    );//, strtoupper(getWords("rekapitulation")), true));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("departement"),
            "department_code",
            ['width' => 100],
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
            getWords("position"),
            "position",
            ['width' => 150],
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
            getWords("total needed"),
            "total_number",
            ['width' => 70],
            ["align" => "right"],
            false,
            false,
            "",
            "",
            "string",
            true,
            8,
            false
        )
    );
    $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
    $myDataGrid->strFileNameXLS = "report_mrf.xls";
    $myDataGrid->strTitle1 = strtoupper(getWords("report mrf"));
    if ($f->getValue("data_date_from") == $f->getValue("data_date_thru")) {
      $myDataGrid->strTitle2 = getWords("date") . " : " . $f->getValue("data_date_from");
    } else {
      $myDataGrid->strTitle2 = getWords("periode") . " : " . $f->getValue("data_date_from") . " - " . $f->getValue(
              "data_date_thru"
          );
    }
    if ($f->getValue("company_name") != "") {
      $myDataGrid->strTitle3 = getWords("company") . " : " . $f->getValue("company_name");
    }
    $myDataGrid->getRequest();
    $strSQL = "
        SELECT t_rec.department_code, t_rec.position, t_dep.department_name,
          SUM(t_rec.number) AS total_number 
        FROM hrd_recruitment_need AS t_rec
        LEFT JOIN hrd_company AS t_com ON t_rec.id_company = t_com.id
        LEFT JOIN hrd_department AS t_dep ON t_rec.department_code = t_dep.department_code
        WHERE t_rec.recruitment_date BETWEEN '" . toSQLDate($f->getValue('data_date_from')) . "' AND '" . toSQLDate($f->getValue('data_date_thru')) . "'
          AND t_rec.status = '" . REQUEST_STATUS_APPROVED . "'
          $strComp
        GROUP BY t_rec.department_code, t_rec.position, t_dep.department_name
        ORDER BY t_rec.department_code, t_rec.position
      ";
  } else {
    $myDataGrid->addColumnNumbering(
        new DataGrid_Column(
            "No", "", ['width' => 30], ['nowrap' => ''], false, false, "", "", "numeric", true, 5, false
        )
    );//, strtoupper(getWords("rekapitulation")), true));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "MRF",
            "request_number",
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
            getWords("departement"),
            "department_code",
            ['width' => 100],
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
            getWords("company"),
            "company_name",
            ['width' => 100],
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
            getWords("position"),
            "position",
            ['width' => 150],
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
            getWords("date required"),
            "due_date",
            ['width' => 90],
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
            getWords("total needed"),
            "number",
            ['width' => 70],
            ["align" => "right"],
            false,
            false,
            "",
            "",
            "string",
            true,
            8,
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("qualification"),
            "qualification",
            ['width' => 200],
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
        new DataGrid_Column(
            getWords("job description"),
            "description",
            ['width' => 200],
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
        new DataGrid_Column(
            getWords("date received"),
            "approved_time",
            ['width' => 90],
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
            getWords("date required"),
            "due_date",
            ['width' => 90],
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
            getWords("created"),
            "created",
            ['width' => 120],
            ['align' => 'center'],
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
            getWords("candidate"),
            "candidate",
            [],
            ['align' => 'center', 'nowrap' => 'nowrap'],
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
            getWords("join date"),
            "join_date",
            [],
            ['align' => 'center', 'nowrap' => 'nowrap'],
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
    $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
    $myDataGrid->strFileNameXLS = "report_mrf.xls";
    $myDataGrid->strTitle1 = strtoupper(getWords("report mrf"));
    if ($f->getValue("data_date_from") == $f->getValue("data_date_thru")) {
      $myDataGrid->strTitle2 = getWords("date") . " : " . $f->getValue("data_date_from");
    } else {
      $myDataGrid->strTitle2 = getWords("periode") . " : " . $f->getValue("data_date_from") . " - " . $f->getValue(
              "data_date_thru"
          );
    }
    if ($f->getValue("company_name") != "") {
      $myDataGrid->strTitle3 = getWords("company") . " : " . $f->getValue("company_name");
    }
    $myDataGrid->getRequest();
    $strSQL = "
        SELECT t_rec.*, t_com.company_name, t_dep.department_name
        FROM hrd_recruitment_need AS t_rec
        LEFT JOIN hrd_company AS t_com ON t_rec.id_company = t_com.id
        LEFT JOIN hrd_department AS t_dep ON t_rec.department_code = t_dep.department_code
        WHERE t_rec.recruitment_date BETWEEN '" . toSQLDate($f->getValue('data_date_from')) . "' AND '" . toSQLDate($f->getValue('data_date_thru')) . "'
          AND t_rec.status = '" . REQUEST_STATUS_APPROVED . "'
          $strComp
      ";
  }
  $dataset = $myDataGrid->getData($db, $strSQL);
  foreach ($dataset as &$row) {
    $row['received_date'] = "";
    $row['candidate'] = ""; // kandidat yang diterima
    $row['join_date'] = ""; // tanggal bergabung
    if (!$isSummary && isset($arrCandidate[$row['id']])) {
      $row['candidate'] = $arrCandidate[$row['id']]['employee_name'];
      $row['join_date'] = $objDt->getDateFormat($arrCandidate[$row['id']]['join_date'], "d M Y");
    }
    if (isset($row['approved_time'])) {
      $row['approved_time'] = $objDt->getDateFormat(substr($row['approved_time'], 0, 10), "d M Y");
    }
    if (isset($row['due_date'])) {
      $row['due_date'] = $objDt->getDateFormat($row['due_date'], "d M Y");
    }
    if (isset ($row['created'])) {
      $row['created'] = substr($row['created'], 0, 19);
    }
  }
  $myDataGrid->totalData = count($dataset);
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
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
function toSQLDate($varDate)
{
$date = standardDateToSQLDateNew($varDate, $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']);
return $date;
}
?>