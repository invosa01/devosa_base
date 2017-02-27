<?php
include_once('../global/session.php');
//include_once('../global.php');
include_once('global.php');
include_once('../includes/date/date.php');
include_once('../includes/form2/form2.php');
include_once('../global/common_data.php');
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
// getDataPrivileges ada di '../global.php'
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
$strReportName = getWords("list of new employee");
$arrData = [];
if (isset($_POST['id_company'])) {
  $arrData['id_company'] = ($_POST['id_company'] != "") ? $_POST['id_company'] : "";
} else {
  $arrData['id_company'] = "";
}
$emptyData = ["value" => "", "text" => ""];
$DataGrid = "";
$strGridTitle = "";
$ViewRefGroup = "";
$f = new clsForm("formInput", 1, "100%", "");
$f->caption = getWords("new employee");
//$f->addHelp(getWords("help for")." ".$dataPrivilege['menu_name'], getHelps("master user"), 8, 167, 400, 300);
$f->addInput(getWords("date from"), "data_date_from", date($_SESSION['sessionDateSetting']['php_format']), [], "date");
$f->addInput(getWords("date thru"), "data_date_thru", date($_SESSION['sessionDateSetting']['php_format']), [], "date");
// getDataListCompany()  ada di  file ../global/common_data.php
//function addSelect($title, $name, $value, $arrAttribute = array(), $dataType="string", $bolRequired = true, $bolEnabled = true, $bolVisible = true, $htmlBefore="", $htmlAfter="", $renderLabel = true, $arrLabelAttribute = null)
$f->addSelect(
    getWords("company"),
    "id_company",
    getDataListCompany($arrData['id_company'], true, $emptyData),
    [],
    "string",
    false,
    true,
    true
);
$f->addSelect(
    getWords("division"),
    "dataDivision",
    getDataListDivision(null, true, $emptyData, $objUP->genFilterDivision()),
    [],
    "string",
    false,
    true,
    true
);
$f->addSelect(
    getWords("department"),
    "dataDepartment",
    getDataListDepartment(null, true, $emptyData, $objUP->genFilterDepartment()),
    [],
    "string",
    false,
    true,
    true
);
$f->addSubmit(
    "btnShow",
    getWords("show data"),
    ["onClick" => "document.formInput.target = ''; return validInput();"],
    true,
    true,
    "",
    "",
    "showData()"
);
$f->addSubmit("btnPrint", getWords("print report"), ["onClick" => "printList()"], true, true, "", "", "showReport");
$f->addSubmit(
    "btnExcel",
    getWords("export excel"),
    ["onClick" => "document.formInput.target = '';return validInput();"],
    true,
    true,
    "",
    "",
    "showData"
);
// $f->setFormTarget("_blank");
$formInput = $f->render();
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == '') {
  $dataPrivilege['icon_file'] = 'blank.png';
}
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('new employee report page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = "templates/report_recruitment_total_karyawan_masuk.html";
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
// tampilkan data
function showData()
{
  global $f;
  global $DataGrid;
  global $strGridTitle;
  global $ViewRefGroup;
  global $strReportName;
  global $objUP;
  $ViewRefGroup = "";
  $bolExcel = false;
  if (isset($_POST['btnExcel'])) {
    $bolExcel = true;
  }
  $db = new CdbClass;
  $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, false);
  $myDataGrid->caption = strtoupper($strReportName);
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  //$myDataGrid->groupBy("id_employee");
  //$myDataGrid->hasGrandTotal = true;
  $myDataGrid->addColumnNumbering(
      new DataGrid_Column("No", "", ['width' => 30], ['nowrap' => ''], false, false, "", "", "numeric", true, 5, false)
  );//, strtoupper(getWords("rekapitulation")), true));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("employe name"),
          "employee_name",
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
          getWords("employee status"),
          "employee_status",
          ['width' => 100],
          ['nowrap' => 'nowrap'],
          false,
          false,
          "",
          "printEmployeeStatus()",
          "string",
          true,
          15,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("position"),
          "position_code",
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
          getWords("band"),
          "salary_grade_code",
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
          strtoupper(getWords("PT")),
          "code",
          ['width' => 50],
          ["align" => "center"],
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
          getWords("division"),
          "division_name",
          [],
          ["nowrap" => "nowrap"],
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
          getWords("department"),
          "department_name",
          [],
          ["nowrap" => "nowrap"],
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
          getWords("join date"),
          "join_date",
          ['width' => 100],
          ['align' => 'center'],
          false,
          false,
          "",
          "",
          "string",
          true,
          12,
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("reference"),
          "reference",
          ['width' => 100],
          [],
          false,
          false,
          "",
          "",
          "string",
          true,
          12,
          false
      )
  );
  // ambil kriteria divisi atau department
  $strKriteria = "AND status = '" . REQUEST_STATUS_APPROVED . "' ";
  if ($f->getValue('id_company') != "") {
    $strKriteria .= " AND id_company = '" . $f->getValue('id_company') . "' ";
  }
  if ($f->getValue('dataDivision') != "") {
    $strKriteria .= " AND division_code = '" . $f->getValue('dataDivision') . "' ";
  }
  if ($f->getValue('dataDepartment') != "") {
    $strKriteria .= " AND department_code = '" . $f->getValue('dataDepartment') . "' ";
  }
  $strKriteria .= $objUP->genFilterDivision() . $objUP->genFilterDepartment();
  if ($bolExcel) {
    $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
    $myDataGrid->strFileNameXLS = "report_karyawan_baru_masuk.xls";
    $myDataGrid->strTitle1 = strtoupper($strReportName);
    if ($f->getValue("data_date_from") == $f->getValue("data_date_thru")) {
      $myDataGrid->strTitle2 = getWords("date") . " : " . $f->getValue("data_date_from");
    } else {
      $myDataGrid->strTitle2 = getWords("periode") . " : " . $f->getValue("data_date_from") . " - " . $f->getValue(
              "data_date_thru"
          );
    }
  } else
    // {
    // $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_PRINT_HTML;
    // $strGridTitle = "
    // <table width='100%' border=0 cellpadding=1 cellspacing=0 style='font-size: 10pt; font-weight: bold'>
    // <tr>
    // <td colspan=3 style='font-size: 12pt'>".strtoupper($strReportName)."</td>
    // </tr>";
    // if ($f->getValue("data_date_from") == $f->getValue("data_date_thru"))
    // {
    // $strGridTitle .= "
    // <tr>
    // <td>".getWords("date")."</td>
    // <td width=10>:</td>
    // <td>".$f->getValue("data_date_from")."</td>
    // </tr>
    // </table>";
    // }
    // else
    // {
    // $strGridTitle .= "
    // <tr>
    // <td width=80>".getWords("periode")."</td>
    // <td width=10>:</td>
    // <td>".$f->getValue("data_date_from")."  to  ".$f->getValue("data_date_thru")."</td>
    // </tr>
    // </table>";
    // }
    // }
    //$myDataGrid->groupBy("Department");
  {
    $myDataGrid->getRequest();
  }
  // mengacu ke FKR
  $strSQL = "
      select 
        he.employee_name, he.employee_status, he.position_code,
        hc.company_code, hc.company_name, hd.department_name, he.join_date, 
        hca.reference, he.salary_grade_code, hdv.division_name
      from (
        SELECT * FROM hrd_fkr
        where join_date BETWEEN '" . $f->getValue('data_date_from') . "' AND '" . $f->getValue('data_date_thru') . "'
          $strKriteria
      ) as he
      left join hrd_department as hd on (he.division_code = hd.division_code AND he.department_code = hd.department_code)
      left join hrd_division as hdv on he.division_code=hdv.division_code
      left join hrd_company as hc on he.id_company=hc.id
      left join hrd_candidate as hca on hca.id=he.id_candidate
      order by hdv.division_name, hd.department_name, he.join_date, he.employee_name 
    ";
  //$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
  $dataset = $myDataGrid->getData($db, $strSQL);
  $myDataGrid->totalData = count($dataset);
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
  //====================show group of reference===========================================================================================
  $strSQL = "
      select hc.reference,count(*) as total 
      from (
        SELECT * FROM hrd_fkr
        where join_date BETWEEN '" . $f->getValue('data_date_from') . "' AND '" . $f->getValue('data_date_thru') . "'
          $strKriteria        
      ) as he
      left join  hrd_candidate as hc on hc.employee_id=he.employee_id
      group by hc.reference 
    ";
  $db->connect();
  $res = $db->execute($strSQL);
  $ViewRefGroup = "
      <br>
        <table border=0 cellpadding=5>
          <tr bgcolor=#000000>
            <td><font color=#FFFFFF><b>" . getWords("reference") . "</b></font></td>
            <td><font color=#FFFFFF><b>Jumlah</b></font></td>
          </tr>
    ";
  $SubTotal = 0;
  $color = ["#EEEEEE", "#FFFFFF"];
  $intC = 0;
  while ($dt = $db->fetchrow($res, "ASSOC")) {
    $ViewRefGroup .= "
        <tr bgcolor=" . $color[$intC % 2] . ">
          <td>" . $dt['reference'] . "&nbsp;</td>
          <td align=right>" . $dt['total'] . "</td>
        </tr>
      ";
    $SubTotal += $dt['total'];
    $intC++;
  }
  $ViewRefGroup .= "
        <tr bgcolor=#BBBBBB>
          <td><b>Total&nbsp;</b></td>
          <td align=right><b>$SubTotal</b></td>
        </tr>
      </table>
      <br>
    ";
  // end of group of reference ==============================================================================================================
}

// tampilkan dalam bentuk report, dalam grup per department
// sepertinya tidak dipakai
function showReport()
{
  global $f;
  global $DataGrid;
  global $strGridTitle;
  global $ViewRefGroup;
  global $strReportName;
  // echo $f->getValue("report_type");
  // die();
  $ViewRefGroup = "";
  $strGridTitle = "";
  // $isExport = false;
  // if ($f->getValue("report_type") == 2) $isExport = true;
  $db = new CdbClass;
  $db->connect();
  if ($isExport) {
    headeringExcel("list_of_new_employee.xls");
  }
  /*
  else
  {
    $strGridTitle = "
      <table width='100%' border=0 cellpadding=1 cellspacing=0 style='font-size: 10pt; font-weight: bold'>
        <tr>
          <td colspan=3 style='font-size: 12pt'>".strtoupper($strReportName)."</td>
        </tr>";
    if ($f->getValue("data_date_from") == $f->getValue("data_date_thru"))
    {
      $strGridTitle .= "
        <tr>
          <td>".getWords("date")."</td>
          <td width=10>:</td>
          <td>".$f->getValue("data_date_from")."</td>
        </tr>
      </table>";
    }
    else
    {
      $strGridTitle .= "
        <tr>
          <td width=80>".getWords("periode")."</td>
          <td width=10>:</td>
          <td>".$f->getValue("data_date_from")."  to  ".$f->getValue("data_date_thru")."</td>
        </tr>
      </table>";
    }
  }
  */
  if ($f->getValue("data_date_from") == $f->getValue("data_date_thru")) {
    $strPeriod = $f->getValue("data_date_from");
  } else {
    $strPeriod = $f->getValue("data_date_from") . "  " . getWords("to") . "  " . $f->getValue("data_date_thru");
  }
  $strInfo = "";
  //get data from FKR
  $arrDiv = []; // daftar division
  $arrDep = []; // daftar departemen
  $arrInfo = [];
  $strSQL = "
      SELECT f.*, c.company_name, dv.division_name, dp.department_name 
      FROM hrd_fkr AS f
      LEFT JOIN hrd_company AS c ON f.id_company = c.id 
      LEFT JOIN hrd_division AS dv ON f.division_code = dv.division_code
      LEFT JOIN hrd_department AS dp ON (f.department_code = dp.department_code AND f.division_code = dp.division_code)
      WHERE f.join_date BETWEEN '" . $f->getValue("data_date_from") . "' AND '" . $f->getValue("data_date_thru") . "'
      ";
  if ($f->getValue('id_company') != "") {
    $strSQL .= " And f.id_company='" . $f->getValue('id_company') . "' ";
  }
  $strSQL .= " order by f.join_date ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $arrDiv[$row['division_code']] = strtoupper($row['division_name']);
    $arrDep[$row['department_code']] = strtoupper($row['department_name']);
    $arrInfo[$row['division_code']][$row['department_code']][] = $row;
  }
  // mulai buat laporan
  //$strResult = $strGridTitle;
  $strResult = "
        <br />
        <table width='100%' border=0 cellpadding=1 cellspacing=0 class='gridTable'>
          <tr align='center'>
            <th class='tableHeader' nowrap>" . getWords("no.") . "</th>
            <th class='tableHeader' nowrap>" . getWords("name") . "</th>
            <th class='tableHeader' nowrap>" . getWords("position") . "</th>
            <th class='tableHeader' nowrap>" . getWords("band") . "</th>
            <th class='tableHeader' nowrap>" . getWords("company") . "</th>
            <th class='tableHeader' nowrap>" . getWords("department") . "</th>
            <th class='tableHeader' nowrap>" . getWords("join date") . "</th>
          </tr>
      ";
  $intCols = 7;
  foreach ($arrInfo AS $strDiv => $arrD) {
    $strDivName = (isset($arrDiv[$strDiv])) ? $arrDiv[$strDiv] : $strDiv;
    $strResult .= "
          <tr>
            <td colspan='$intCols' style='font-weight:bold;color:white;background-color:darkgray'>$strDivName&nbsp;</td>
          </tr>
        ";
    foreach ($arrD AS $strDep => $data) {
      $strDepName = (isset($arrDep[$strDep])) ? $arrDep[$strDep] : $strDep;
      $strResult .= "
            <tr>
              <td colspan='$intCols' style='font-weight:bold;'>$strDepName&nbsp;</td>
            </tr>
          ";
      $no = 0;
      foreach ($data As $i => $row) {
        $no++;
        $strResult .= "
              <tr>
                <td align='center'>" . $no . "&nbsp;</td>
                <td nowrap>" . $row['employee_name'] . "&nbsp;</td>
                <td nowrap>" . $row['position_code'] . "&nbsp;</td>
                <td nowrap>" . $row['salary_grade_code'] . "&nbsp;</td>
                <td nowrap>" . $row['company_name'] . "&nbsp;</td>
                <td nowrap>" . $row['department_code'] . "&nbsp;</td>
                <td nowrap align='center'>" . pgDateFormat($row['join_date'], "d-M-Y") . "&nbsp;</td>
              </tr>
            ";
      }
      $strResult .= "
            <tr>
              <td colspan='$intCols' >&nbsp;</td>
            </tr>
          ";
    }
  }
  $strResult .= "
        </table>
      ";
  $GLOBALS['strPeriod'] = $strPeriod;
  $GLOBALS['strInfo'] = $strInfo;
  $GLOBALS['strData'] = $strResult;
  $GLOBALS['strPageTitle'] = $strReportName;
  $tbsPage = new clsTinyButStrong;
  $tbsPage->LoadTemplate("../templates/master_print.html");
  $tbsPage->Show();
}

function comment_printEmployeeStatus($params)
{
  extract($params);
  global $ARRAY_EMPLOYEE_STATUS;
  // sementara yang tampil hanya kontrak atau permanen
  if ($value == "") {
    return "";
  } else if ($value == STATUS_PERMANENT) {
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