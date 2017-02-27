<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
$dataPrivilege = getDataPrivileges(
    "recruitment_process_list.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
//---- INISIALISASI ----------------------------------------------------
$strReportName = getWords("recruitment report");
$strWordsRecruitmentProcessList = getWords("recruitment process list");
$strWordsRecruitmentProcessReport = getWords("recruitment process report");
$strWordsDateFrom = getWords("date from");
$strWordsDateTo = getWords("date thru");
$strWordsMRF = getWords("MRF No.");
$strWordsShowData = getWords("show");
$strWordsPrint = getWords("print");
$strWordsRECRUITMENTREPORT = strtoupper(getWords("recruitment process report"));
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strStyle = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $ARRAY_LEAVE_TYPE;
  global $strDataMRF;
  $intRows = 0;
  $strResult = "";
  // ambil tahunnya
  list($strYear1, $bln, $tgl) = explode("-", $strDataDateFrom);
  list($strYear2, $bln, $tgl) = explode("-", $strDataDateThru);
  // ambil dulu jenis absen
  $arrProcessType = [];
  $strSQL = "SELECT * FROM hrd_recruitment_process_type ORDER BY step ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrProcessType[$rowDb['name']] = $rowDb['note'];
  }
  $strResult .= "<table width=\"100%\" cellspacing=0 cellpadding=1 border=0 class='table table-striped table-hover table-bordered'>\n";
  // bikin header table
  $strDefaultWidth = "width=40";
  $intNumType = count($arrProcessType);
  $strResult .= " <thead>\n";
  $strResult .= " <tr>\n";
  $strResult .= "  <th>&nbsp;" . strtoupper(getWords("no")) . "</th>\n";
  $strResult .= "  <th>&nbsp;" . strtoupper(getWords("position")) . "</th>\n";
  $strResult .= "  <th>&nbsp;" . strtoupper(getWords("candidate")) . "</th>\n";
  $strResult .= "  <th $strDefaultWidth>&nbsp;" . strtoupper(getWords("invitation")) . "</th>\n";
  foreach ($arrProcessType AS $kode => $nama) {
    $strResult .= "  <th title=\"$nama\" >&nbsp;" . strtoupper($kode) . "</th>\n";
  }
  $strResult .= "  <th $strDefaultWidth>&nbsp;" . strtoupper(getWords("accepted")) . "</th>\n";
  $strResult .= "  <th $strDefaultWidth>&nbsp;" . strtoupper(getWords("denied")) . "</th>\n";
  $strResult .= "  <th $strDefaultWidth>&nbsp;" . strtoupper(getWords("considered")) . "</th>\n";
  $strResult .= " </tr>\n";
  $strResult .= " </thead>\n";
  // ambil dulu data jabatan/rekruitmen apa yagn diproses pada bulan itu
  $arrPos = [];
  // cari invitation
  /*
  $strSQL  = "
    SELECT t1.position, COUNT(t1.id) AS total
    FROM hrd_recruitment_process AS t1,
      hrd_recruitment_process_detail AS t2
    WHERE t1.id = t2.id_recruitment_process
      AND (t1.invitation_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' )
        -- OR t2.process_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru')
    GROUP BY t1.position
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrPos[$rowDb['position']] = $rowDb['total'];
  }
  */
  $strSQL = "
      SELECT DISTINCT \"position\" 
      FROM hrd_candidate 
      WHERE application_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru'
        OR id IN (
          SELECT id_candidate 
          FROM hrd_recruitment_process
          WHERE invitation_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' 
            OR id IN (
              SELECT DISTINCT id_recruitment_process
              FROM hrd_recruitment_process_detail
              WHERE process_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru'
            )
        )
      ORDER BY position
      ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrPos[$rowDb['position']] = 0;
  }
  // cari detail prosesnya
  $arrProses = [];
  $strSQL = "
      SELECT t1.position, t2.process_name, 
        COUNT(t2.process_date) AS total 
      FROM hrd_recruitment_process AS t1, hrd_recruitment_process_detail AS t2 
      WHERE t1.id = t2.id_recruitment_process 
        --AND (t1.invitation_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru' 
          AND (t2.process_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru') 
      GROUP BY t1.position, t2.process_name 
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrProses[$rowDb['position']][$rowDb['process_name']] = $rowDb['total'];
  }
  $i = 0;
  $tbl = new cModel("hrd_position");
  $arrPositionData = $tbl->findAll(null, null, null, null, null, "position_code");
  $strResult .= " <tbody>\n";
  foreach ($arrPos AS $strPos => $intTmp) {
    $i++;
    $intApprove = 0;
    $intDenied = 0;
    $intConsidered = 0;
    $intCandidate = 0;
    $intInvitation = 0;
    // cari data approve dan denied
    $strSQL = "
        SELECT SUM(CASE WHEN (result = 1) THEN 1 ELSE 0 END) AS accepted, 
          SUM(CASE WHEN (result = 3) THEN 1 ELSE 0 END) AS denied, 
          SUM(CASE WHEN (result = 4) THEN 1 ELSE 0 END) AS considered, 
          COUNT(invitation_date) AS invitation 
        FROM hrd_recruitment_process 
        WHERE invitation_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru'
          AND id_candidate IN (
            SELECT id FROM hrd_candidate WHERE \"position\" = '$strPos'
          )
            ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $intApprove = $rowTmp['accepted'];
      $intDenied = $rowTmp['denied'];
      $intConsidered = $rowTmp['considered'];
      $intInvitation = $rowTmp['invitation'];
    }
    // cari jumlah kandidate
    $strSQL = "
        SELECT COUNT(id) AS candidate 
        FROM hrd_candidate 
        WHERE application_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru'
          AND \"position\" = '$strPos'
        ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $intCandidate = $rowTmp['candidate'];
    }
    $strResult .= " <tr valign=top>\n";
    $strResult .= "  <td nowrap align=right>$i.&nbsp;</td>\n";
    if (isset($arrPositionData[$strPos])) {
      $strResult .= "  <td nowrap >&nbsp;$strPos" . " - " . $arrPositionData[$strPos]['position_name'] . "</td>\n";
    } else {
      $strResult .= "  <td nowrap >&nbsp;$strPos</td>\n";
    }
    $strResult .= "  <td nowrap align=center>&nbsp;$intCandidate</td>\n";
    $strResult .= "  <td nowrap align=center>&nbsp;$intInvitation</td>\n";
    foreach ($arrProcessType AS $kode => $nama) {
      $intTmp = (isset($arrProses[$strPos][$kode])) ? $arrProses[$strPos][$kode] : 0;
      if ($intTmp == 0) {
        $intTmp = "";
      }
      $strResult .= "  <td nowrap align=center>&nbsp;" . $intTmp . "</td>\n";
    }
    $strResult .= "  <td nowrap align=center>&nbsp;" . $intApprove . "</td>\n";
    $strResult .= "  <td nowrap align=center>&nbsp;" . $intDenied . "</td>\n";
    $strResult .= "  <td nowrap align=center>&nbsp;" . $intConsidered . "</td>\n";
    $strResult .= " </tr>\n";
  }
  $strResult .= " </tbody>\n";
  writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  $strResult .= "</table>\n";
  return $strResult;
} // showData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  // ------ AMBIL DATA KRITERIA -------------------------
  (isset($_REQUEST['dataDateFrom'])) ? $strDataDateFrom = $_REQUEST['dataDateFrom'] : $strDataDateFrom = date($_SESSION['sessionDateSetting']['php_format']);
  (isset($_REQUEST['dataDateThru'])) ? $strDataDateThru = $_REQUEST['dataDateThru'] : $strDataDateThru = date($_SESSION['sessionDateSetting']['php_format']);
  (isset($_REQUEST['dataMRF'])) ? $strDataMRF = $_REQUEST['dataMRF'] : $strDataMRF = "";
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if ($bolCanView) {
    $bolShow = (isset($_REQUEST['btnShow']) || isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
    if (validStandardDate($strDataDateFrom) && validStandardDate($strDataDateThru) && $bolShow) {
      // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
      $strDataDetail = getData($db, $strDataDateFrom, $strDataDateThru, $intTotalData, $strKriteria);
      if (isset($_REQUEST['btnExcel'])) {
        // ambil data CSS-nya
        if (file_exists("../css/excel.css")) {
          $strStyle = "../css/excel.css";
        }
        $strPrintCss = "";
        $strPrintInit = "";
        headeringExcel("recruitmentProcessReport.xls");
      } else {
        $strHidden .= "<input type=hidden name=btnShow value=show>";
      }
    } else {
      $strDataDetail = "";
    }
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  // generate data hidden input dan element form input
  $strReadonly = ($bolIsEmployee) ? "readonly" : "";
  $strDisabled = ($bolIsEmployee) ? "disabled" : "";
  $intDefaultWidthPx = 200;
  $strInputDateFrom = "<input type=text name=dataDateFrom id=dataDateFrom size=15 maxlength=10 value=\"$strDataDateFrom\" class=\"form-control datepicker\" data-date-format=".$_SESSION['sessionDateSetting']['html_format'].">";
  $strInputDateThru = "<input type=text name=dataDateThru id=dataDateThru size=15 maxlength=10 value=\"$strDataDateThru\" class=\"form-control datepicker\" data-date-format=".$_SESSION['sessionDateSetting']['html_format'].">";
  $strInputMRF = "<input type=text name=dataMRF id=dataMRF size=30 maxlength=60 value=\"$strDataMRF\">";
  // informasi tanggal kehadiran
  if ($strDataDateFrom == $strDataDateThru) {
    $strInfo .= strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
  } else {
    $strInfo .= strtoupper(pgDateFormat($strDataDateFrom, "d-M-Y"));
    $strInfo .= " &raquo; " . strtoupper(pgDateFormat($strDataDateThru, "d-M-Y"));
  }
  $strHidden .= "<input type=hidden name=dataDateFrom value=\"$strDataDateFrom\">";
  $strHidden .= "<input type=hidden name=dataDateThru value=\"$strDataDateThru\">";
  $strHidden .= "<input type=hidden name=dataMRF value=\"$strDataMRF\">";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = getWords('recruitment process report page');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = recruitmentProcessSubMenu($strWordsRecruitmentProcessReport);
if ($bolPrint) {
  $strMainTemplate = getTemplate("report_print.html");
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>