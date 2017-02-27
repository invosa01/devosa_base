<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid/datagrid.php');
include_once('../includes/date/date.php');
include_once('../includes/form2/form2.php');
include_once('../global/common_data.php');
// getDataPrivileges ada di '../global.php'
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
$db = new CdbClass;
$db->connect();
$strReportName = getWords("recruitment report by department");
$arrData = [];
if (isset($_POST[''])) {
  $arrData['division_code'] = ($_POST['division_code'] != "") ? $_POST['division_code'] : "";
} else {
  $arrData['division_code'] = "";
}
if (isset($_POST['department_code'])) {
  $arrData['department_code'] = ($_POST['department_code'] != "") ? $_POST['department_code'] : "";
} else {
  $arrData['department_code'] = "";
}
$emptyData = ["value" => "", "text" => ""];
$DataGrid = "";
$strGridTitle = "";
$ViewRefGroup = "";
$f = new clsForm("formInput", 1, "100%", "");
$f->caption = $strReportName;
$f->addInput(
    getWords("date from"),
    "dataDateFrom",
    getInitialValue("DateFrom", sqlToStandarDateNew(date("Y-m-") . "01", $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateFormat'])),
    ["style" => "width:$strDateWidth"],
    "date",
    true,
    true,
    true
);
$f->addInput(
    getWords("date thru"),
    "dataDateThru",
    getInitialValue("DateThru", date($_SESSION['sessionDateSetting']['php_format'])),
    ["style" => "width:$strDateWidth"],
    "date",
    true,
    true,
    true
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
    getWords("department "),
    "department_code",
    getDataListDepartment(getInitialValue("Department", "", $strDataDepartment), true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['department'] == "")
);
$f->addSelect(
    getWords("division"),
    "division_code",
    getDataListDivision(getInitialValue("Division", "", $strDataDivision), true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['division'] == "")
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
$f->addSubmit(
    "btnShow",
    getWords("show data"),
    ["onClick" => "document.formInput.target = ''; return validInput();"],
    true,
    true,
    "",
    "",
    "showData"
);
$f->addSubmit("btnPrint", getWords("print report"), ["onClick" => "printList()"], true, true, "", "", "showReport");
$f->addSubmit(
    "btnExcel",
    getWords("export excel"),
    ["onClick" => "document.formInput.target = ''; return validInput();"],
    true,
    true,
    "",
    "",
    "showReport"
);
//  if (!isset($_REQUEST['btnShow']))
$f->setFormTarget("");
$formInput = $f->render();
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == '') {
  $dataPrivilege['icon_file'] = 'blank.png';
}
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('recruitment report by department page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = "templates/report_recruitment_dept.html";
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
// tampilkan laporan
function showReport()
{
  global $f;
  global $DataGrid;
  global $strGridTitle;
  global $ViewRefGroup;
  global $strReportName;
  global $objUP;
  $ViewRefGroup = "";
  $strGridTitle = "";
  $bolExcel = false;
  if (isset($_POST['btnExcel'])) {
    $bolExcel = true;
  }
  $db = new CdbClass;
  $db->connect();
  if ($bolExcel) {
    headeringExcel("list_of_new_employee.xls");
  }
  /*
  if ($f->getValue("data_date_from") == $f->getValue("data_date_thru"))
    $strPeriod = $f->getValue("data_date_from");
  else
    $strPeriod = $f->getValue("data_date_from")."  " .getWords("to")."  ".$f->getValue("data_date_thru");
  */
  //$strPeriod = $f->getValue("dataYear");
  $strInfo = "";
  //get data from FKR
  $arrDiv = []; // daftar division
  $arrDep = []; // daftar departemen
  $arrInfo = [];
  $arrTotal = []; // menyimpan data total
  // Buat Header Untuk Report
  $strDateFrom = standardDateToSQLDateNew($f->getValue('dataDateFrom'), $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']);
return $date;
  $strDateThru = standardDateToSQLDateNew($f->getValue('dataDateThru'), $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']);
  $strNameCompany = $f->getValue('dataCompany');
  $strNameDepartment = getNameDepartment($f->getValue('department_code'));
  $strNameDivision = getNameDevision($f->getValue('division_code'));
  $strNameSection = getNameSection($f->getValue('dataSection'));
  $strPeriod .= "Department : $strNameDepartment <br>";
  $strPeriod .= "Division : $strNameDivision <br>";
  $strPeriod .= "Section : $strNameSection <br>";
  $strPeriod .= "Periode : $strDateFrom - $strDateThru <br>";
  // Filter kriteria
  $strKriteria = "";
  if ($f->getValue('department_code') != "") {
    $strKriteria .= " And department_code = '" . $f->getValue('department_code') . "' ";
  }
  if ($f->getValue('division_code') != "") {
    $strKriteria .= " And division_code = '" . $f->getValue('division_code') . "' ";
  }
  if ($f->getValue('dataCompany') != "") {
    $strKriteria .= " And id_company= '" . $f->getValue('dataCompany') . "' ";
  }
  if ($f->getValue('dataSection') != "") {
    $strKriteria .= " And section_code= '" . $f->getValue('dataSection') . "' ";
  }
  if (($f->getValue('dataDateFrom') != "") AND ($f->getValue('dataDateThru') != "")) {
    $strPeriode .= "AND (join_date::date BETWEEN '" . $f->getValue('dataDateFrom') . "' AND '" . $f->getValue(
            'dataDateThru'
        ) . "')  ";
  }
  $strKriteria .= $objUP->genFilterDivision() . $objUP->genFilterDepartment();
  $strSQL = "
      SELECT f.position_code, count(f.id) AS total, 
        EXTRACT(month FROM join_date) AS bulan,
        f.division_code, f.department_code,
        c.company_name, dv.division_name, dp.department_name 
      FROM (
        SELECT * FROM hrd_fkr
        WHERE 1=1 $strKriteria
      ) AS f
      LEFT JOIN hrd_company AS c ON f.id_company = c.id 
      LEFT JOIN hrd_division AS dv ON f.division_code = dv.division_code
      LEFT JOIN hrd_department AS dp ON (f.department_code = dp.department_code AND f.division_code = dp.division_code)
      WHERE 1=1 " . $strPeriode . "
      ";
  $strSQL .= "
      GROUP BY f.position_code, EXTRACT(month FROM f.join_date), 
        f.division_code, f.department_code,
        c.company_name, dv.division_name, dp.department_name 
      order by f.position_code 
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $arrDiv[$row['division_code']] = strtoupper($row['division_name']);
    $arrDep[$row['department_code']] = strtoupper($row['department_name']);
    $arrInfo[$row['division_code']][$row['department_code']][$row['position_code']][$row['bulan']] = $row['total'];
  }
  // mulai buat laporan
  //$strResult = $strGridTitle;
  $strMonthCols = "";
  for ($i = 1; $i <= 12; $i++) {
    $strMonthCols .= "<th class='tableHeader' nowrap>" . getBulanSingkat($i) . "</td>";
    $arrTotal[$i] = 0;
  }
  $arrTotal['total'] = 0;
  $strResult = "
        <br />
        <table width='100%' border=0 cellpadding=1 cellspacing=0 class='gridTable'>
          <tr align='center'>
            <th class='tableHeader' nowrap rowspan=2>" . getWords("no.") . "</th>
            <th class='tableHeader' nowrap rowspan=2>" . getWords("department") . "</th>
            <th class='tableHeader' nowrap rowspan=2>" . getWords("position") . "</th>
            <th class='tableHeader' nowrap rowspan=2>" . getWords("total") . "</th>
            <th class='tableHeader' nowrap colspan=12>" . getWords("fulfilment schedule") . "</th>
          </tr>
          <tr align='center'>
            $strMonthCols
          </tr>
      ";
  $intCols = 16;
  foreach ($arrInfo AS $strDiv => $arrD) {
    $strDivName = (isset($arrDiv[$strDiv])) ? $arrDiv[$strDiv] : $strDiv;
    $strResult .= "
          <tr>
            <td colspan='$intCols' style='font-weight:bold;color:white;background-color:darkgray'>$strDivName&nbsp;</td>
          </tr>
        ";
    $noDiv = 0;
    foreach ($arrD AS $strDep => $data) {
      $noDiv++;
      $strDepName = (isset($arrDep[$strDep])) ? $arrDep[$strDep] : $strDep;
      $no = 0;
      foreach ($data As $strPos => $row) {
        $no++;
        // detailnya dulu
        $intTotal = 0;
        $strResultDetail = "";
        for ($i = 1; $i <= 12; $i++) {
          $tmp = (isset($row[$i])) ? $row[$i] : "";
          $strResultDetail .= " <td align='center'>&nbsp;$tmp</td> ";
          if ($tmp != "") {
            $intTotal += $tmp;
            $arrTotal[$i] += $tmp;
          }
        }
        $arrTotal['total'] += $intTotal;
        // utama
        if ($no == 1) {
          $strResult .= "
                <tr>
                  <td align='center'>" . $noDiv . "&nbsp;</td>
                  <td nowrap>" . $strDepName . "&nbsp;</td>
              ";
        } else {
          $strResult .= "
                <tr>
                  <td align='center'>&nbsp;</td>
                  <td nowrap>&nbsp;</td>
              ";
        }
        $strResult .= "
                <td nowrap>" . $strPos . "&nbsp;</td>
                <td nowrap align='center'>" . $intTotal . "&nbsp;</td>
            ";
        $strResult .= $strResultDetail;
        $strResult .= "
              </tr>
            ";
      }
    }
  }
  // tampilkan total
  $strDetail = "";
  for ($i = 1; $i <= 12; $i++) {
    $strDetail .= " <td align='center'><b>&nbsp;" . $arrTotal[$i] . "</b></td> ";
  }
  $strResult .= "
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td align='center'><b>" . strtoupper(getWords("total")) . "</b>&nbsp;</td>
          <td align='center'><b>" . $arrTotal['total'] . "</b>&nbsp;</td>
          $strDetail
        </tr>
      ";
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

function showData()
{
  global $f;
  global $DataGrid;
  global $strGridTitle;
  global $strReportName;
  //global $ViewRefGroup;
  $ViewRefGroup = "";
  $isExport = false;
  // $bolExcel = false;
  // if (isset($_POST['btnExcel']))
  // $bolExcel = true;
  $db = new CdbClass;
  $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, false);
  $myDataGrid->caption = strtoupper($strReportName);
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  //$myDataGrid->groupBy("id_employee");
  $myDataGrid->hasGrandTotal = true;
  $myDataGrid->addColumnNumbering(
      new DataGrid_Column("No", "", ['width' => 30], ['nowrap' => ''], false, false, "", "", "numeric", true, 5, false)
  );//, strtoupper(getWords("rekapitulation")), true));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("department"),
          "department_name",
          ['width' => 150],
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
  //    $myDataGrid->addColumn(new DataGrid_Column(getWords("division"), "division_name", array('width' => 150), array('nowrap' => 'nowrap'), false, false, "", "printEmployeeStatus()", "string", true, 16, false));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("position"),
          "position_name",
          ["align" => "center"],
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
          (getWords("month")),
          "month",
          [],
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
          getWords("total"),
          "total",
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
  //    $myDataGrid->addColumn(new DataGrid_Column(getWords("date start"), "join_date", array('width' => 200), array("align" => "center"), false, false, "", "", "string", true, 32, false));
  //    $myDataGrid->addColumn(new DataGrid_Column(getWords("date received"), "received_date", array('width' => 90), array('align' => 'center'), false, false, "", "", "string", true, 10, false));
  //    $myDataGrid->addColumn(new DataGrid_Column(getWords("date required"), "due_date", array('width' => 90), array('align' => 'center'), false, false, "", "", "string", true, 10, false));
  if ($isExport) {
    $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
    $myDataGrid->strFileNameXLS = "report_recruitment_dept.xls";
    $myDataGrid->strTitle1 = strtoupper($strReportName);
    if ($f->getValue("dataDateFrom") == $f->getValue("dataDateThru")) {
      $myDataGrid->strTitle2 = getWords("date") . " : " . $f->getValue("dataDateFrom");
    } else {
      $myDataGrid->strTitle2 = getWords("periode") . " : " . $f->getValue("dataDateFrom") . " - " . $f->getValue(
              "dataDateThru"
          );
    }
  } else {
    $strGridTitle = "
        <table width='100%' border=0 cellpadding=1 cellspacing=0 style='font-size: 10pt; font-weight: bold'>
          <tr>
            <td colspan=3 style='font-size: 12pt'>" . strtoupper(
            getWords("list of employee by division and department")
        ) . "</td>
          </tr>";
    if ($f->getValue("dataDateFrom") == $f->getValue("dataDateThru")) {
      $strGridTitle .= "
          <tr>
            <td>" . getWords("date") . "</td>
            <td width=10>:</td>
            <td>" . $f->getValue("dataDateFrom") . "</td>
          </tr>
        </table>";
    } else {
      $strGridTitle .= "
          <tr>
            <td width=80>" . getWords("period") . "</td>
            <td width=10>:</td>
            <td>" . $f->getValue("dataDateFrom") . "  " . getWords("to") . "  " . $f->getValue("dataDateThru") . "</td>
          </tr>
        </table>";
    }
  }
  //$myDataGrid->groupBy("Department");
  $myDataGrid->getRequest();
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  //get data attendance
  //  saat ini sebenarnya cumup ambil data dari hrd_employee saja, karena yang di tampilkan adalah division_code dan department_code.
  $strSQL = "select  hd.department_name, hp.position_name, to_char(he.join_date, 'Month') as month, SUM(1) as total from
          hrd_employee as he
          left join hrd_department as hd on he.department_code=hd.department_code
          left join hrd_division as hdi on he.division_code=hdi.division_code
          left join hrd_position as hp on he.position_code=hp.position_code
         where he.join_date BETWEEN '" . standardDateToSQLDateNew($f->getValue('dataDateFrom'), $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']) . "' AND '" . standardDateToSQLDateNew($f->getValue('dataDateThru'), $_SESSION['sessionDateSetting']['date_sparator'], $_SESSION['sessionDateSetting']['pos_year'],$_SESSION['sessionDateSetting']['pos_month'], $_SESSION['sessionDateSetting']['pos_day']) . "' ";
  //  penambahan kondisi jika hendak menampilkan yg dari divisi ataupun department tertentu
  if ($f->getValue('division_code') != "") {
    $strSQL .= " And he.division_code='" . $f->getValue('division_code') . "' ";
  }
  if ($f->getValue('department_code') != "") {
    $strSQL .= " And he.department_code='" . $f->getValue('department_code') . "' ";
  }
  //
  $strSQL .= " group by hd.department_name,hp.position_name,to_char(he.join_date, 'Month') ";
  // sort berdasarkan divisi dulu, kemudian department, kemudian join_date
  //    $strSQL .= " order by he.division_code,he.department_code,he.join_date ";
  //$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
  $dataset = $myDataGrid->getData($db, $strSQL);
  $myDataGrid->totalData = count($dataset);
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
  //====================show group of reference===========================================================================================
  /*	$strSQL="select hc.reference,count(*) as total from hrd_employee as he
        left join  hrd_candidate as hc on hc.employee_id=he.employee_id
        group by hc.reference ";	*/
  /*	$strSQL ="select hc.reference,count(*) as total from hrd_employee as he
    left join  hrd_candidate as hc on hc.employee_id=he.employee_id	
    where hc.employee_id is not NULL AND he.join_date BETWEEN '".$f->getValue('data_date_from')."' AND '".$f->getValue('data_date_thru')."'	group by hc.reference ";		*/
  /*	$strSQL ="select hc.reference,count(*) as total from hrd_employee as he
    inner join  hrd_candidate as hc on hc.employee_id=he.employee_id	
    where he.join_date BETWEEN '".$f->getValue('data_date_from')."' AND '".$f->getValue('data_date_thru')
    ."'	group by hc.reference ";
    $db->connect();
    $res=$db->execute($strSQL);
    $ViewRefGroup="<br><table border=0><tr bgcolor=#000000><td><font color=#FFFFFF><b>".getWords("reference")
    ."</b></font></td><td><font color=#FFFFFF><b>Jumlah</b></font></td></tr>";
    $SubTotal=0;
    $color= array("#EEEEEE","#FFFFFF");
    $intC=0;
    while($dt=$db->fetchrow($res,"ASSOC"))
    {
      $ViewRefGroup .="<tr bgcolor=".$color[$intC%2]."><td>".$dt['reference']."&nbsp;</td><td align=right>".$dt['total']."</td></tr>";
      $SubTotal +=$dt['total'];
      $intC++;
    }
    $ViewRefGroup .="<tr bgcolor=#BBBBBB><td><b>Total&nbsp;</b></td><td align=right><b>$SubTotal</b></td></tr></table><br>";
  // end of group of reference ==============================================================================================================
  */
}

/// Nama Departmnet
function getNameDepartment($strcode)
{
  global $db;
  $strSQL = "SELECT department_name FROM hrd_department WHERE department_code = '$strcode'";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strName = $rowDb['department_name'];
  }
  return $strName;
}

/// Nama Devisi
function getNameDevision($strCode)
{
  global $db;
  $strSQL = "SELECT division_name FROM hrd_division WHERE division_code ='$strCode'";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strName = $rowDb['division_name'];
  }
  return $strName;
}

// Section
function getNameSection($strCode)
{
  global $db;
  $strSQL = "SELECT section_name FROM hrd_section WHERE section_code='$strCode'";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strName = $rowDb['section_name'];
  }
  return $strName;
}

/*  $strSQL  = "SELECT division_name FROM hrd_division WHERE division_code = '$strDataDivision' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strDivisionName = $rowDb['division_name'];
    }
    $strKriteria .= "AND division_code = '$strDataDivision' ";
  }

  if ($strDataSection != "") {
    $strSQL  = "SELECT section_name FROM hrd_section WHERE section_code = '$strDataSection' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strSectionName = $rowDb['section_name'];
    }
    $strKriteria .= "AND section_code = '$strDataSection' ";
*/
function getDataYear()
{
  $currYear = intval(date("Y"));
  $arrResult = [];
  for ($i = $currYear + 2; $i > $currYear - 10; $i--) {
    if ($i == $currYear) {
      $arrResult[] = ["value" => $i, "text" => $i, "selected" => true];
    } else {
      $arrResult[] = ["value" => $i, "text" => $i, "selected" => false];
    }
  }
  return $arrResult;
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