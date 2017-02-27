<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('overtime_func.php');
$dataPrivilege = getDataPrivileges(
    "shift_schedule.php",
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
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strPaging = "";
$strMessages = "";
$strMsgClass = "";
$strButtons = "";
$strWordsMonth = getWords('month');
$strWordsBySection = getWords('by section');
$strWordsByGroup = getWords('by group');
$strWordsByEmployee = getWords('by employee');
$grouping = 0; //0= group, 1=section
$bolError = false;
//----------------------------------------------------------------------
//--- DAFTAR FUNGSI------------------------------------------------------
$bolIsGet = false; // apakah daftar cuti sudah pernah diambil atau belum, biar gak 2 kali
$arrShiftType = [];
// fungsi untuk mengambil daftar jenis cuti
// input: db object, nama objek, default nilai, atribut tambahan
function getShiftType($strName, $strDefault = '', $strExtra = "")
{
  return "<input type=\"text\" name=\"$strName\" id=\"$strName\" value=\"$strDefault\" size=5 onKeyUp=\"this.value = this.value.toUpperCase()\" $strExtra>";
}

$bolIsGet = false; // apakah daftar cuti sudah pernah diambil atau belum, biar gak 2 kali
$arrPattern = [];
// fungsi untuk mengambil daftar jenis cuti
// input: db object, nama objek, default nilai, atribut tambahan
function getPattern($db, $strName, $strDefault = '', $strExtra = "")
{
  global $bolIsGet;
  global $arrPattern;
  if (!$bolIsGet) {
    $strSQL = "SELECT \"pattern\" FROM \"hrd_shift_roster\"";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrPattern[$rowDb['pattern']] = $rowDb['pattern'];
    }
    $bolIsGet = true;
  }
  $strResult = "<select name=\"$strName\" id=\"$strName\" $strExtra >\n";
  $strResult .= "<option value=\"\"> </option>\n";
  foreach ($arrPattern AS $strCode => $strTmp) {
    $strSel = ($strDefault == $strCode) ? "selected" : "";
    $strResult .= "<option value=\"$strCode\" $strSel>$strCode</option>\n";
  }
  $strResult .= "</select>";
  return $strResult;
}

// fungsi untuk menampilkan laporan roster
function getData($db, $strMonth, $strYear, $strKriteria)
{
  global $strCodeList;
  $strResult = "";
  $strMonth = (int)$strMonth;
  $strYear = (int)$strYear;
  // cari maksimum bulan, lagi malas bikin fungsinya
  $intMax = 31;
  if (in_array($strMonth, [4, 6, 9, 11])) {
    $intMax = 30;
  } else if ($strMonth == 2) {
    $intMax = (($strYear % 4) == 0) ? 29 : 28;
  }
  $strStartDate = "$strYear-$strMonth-01";
  $strEndDate = "$strYear-$strMonth-" . lastday($strMonth, $strYear);
  $strStartDate = pgDateFormat($strStartDate, "Y-m-d");
  $strEndDate = pgDateFormat($strEndDate, "Y-m-d");
  $intStartDay = getWDay($strStartDate);
  // header
  $arrDay = [];
  $intColumn = $intMax + 3;
  $strResultT = "";
  for ($tgl = 1; $tgl <= $intMax; $tgl++) {
    $intDay = ($intStartDay + $tgl) % 7;
    $strClass = ($intDay == 1) ? "bgHoliday" : "tableHeader";
    $strStyle = ($intDay == 1) ? " style = 'background:red;' " : "";
    $strResultT .= "<td width=15 align=center class='$strClass' $strStyle>$tgl</td>\n";
    $arrDay[] = $tgl;
  }
  $strResult = "
      <table cellspacing=0 class='gridTable'>
        <tr class=tableHeader>
          <th rowspan=2>&nbsp;No.</td>
          <th rowspan=2 nowrap>" . getwords('code') . ".</td>
          <th rowspan=2 nowrap>" . getwords('section name') . "</td>
          <th rowspan=2 style='border-left:none'>&nbsp;</td>
          <th colspan=$intMax align=center>Date</td>
        </tr>
        <tr class='tableHeader'>
          $strResultT
        </tr>
    ";
  // kumpulkan data jadwal shift
  $arrShift = []; // data shift karyawan
  $arrTotal = []; // menampung data total
  $strSQL = "
      SELECT \"id_group\", \"shift_code\", extract(day from \"shift_date\") as tgl 
      FROM \"hrd_shift_schedule_group\" WHERE EXTRACT(year from \"shift_date\") = '$strYear'
        AND EXTRACT(month from \"shift_date\") = '$strMonth'
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrShift[$rowDb['id_group']][$rowDb['tgl']] = $rowDb;
  }
  $arrShiftType = []; // data shift type
  $strSQL = "SELECT * FROM \"hrd_shift_type\" ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrList[] = " [<strong>" . $rowDb['code'] . "</strong>] " . $rowDb['note'] . " ";
    $arrShiftType[$rowDb['code']] = $rowDb;
  }
  $strCodeList .= join("&nbsp;&nbsp;&nbsp;", $arrList);
  // ambil data karyawan
  $strSQL = "SELECT t1.\"id\", t1.\"section_code\", t1.\"section_name\" FROM \"hrd_section\" AS t1 ";
  $strSQL .= "WHERE 1=1 $strKriteria ";
  $strSQL .= "ORDER BY t1.\"section_code\" ";
  $resDb = $db->execute($strSQL);
  $intRow = 1;
  while ($rowDb = $db->fetchrow($resDb)) {
    $strResult .= "
          <tr>
            <td>$intRow<input type=hidden name='dataSection$intRow' id='dataSection$intRow' value='" . $rowDb['id'] . "'></td>
            <td nowrap>" . $rowDb['section_code'] . "</td>
            <td nowrap>" . $rowDb['section_name'] . "</td>
            <td nowrap style='border-left:none'><input type='button' name='genShift$intRow' value='roster'  onClick='roast($intRow)'></td>
        ";
    for ($tgl = 1; $tgl <= $intMax; $tgl++) {
      $strShift = (isset($arrShift[$rowDb['id']][$tgl])) ? $arrShift[$rowDb['id']][$tgl]['shift_code'] : "";
      //$strFlag = (isset($arrShift[$rowDb['section_code']][$tgl]) && $arrShift[$rowDb['section_code']][$tgl]['replace_friday'] == 't') ? "t" : "f";
      $strExtra = " onChange = \"document.getElementById('dataSection$intRow').disabled = false;\" ";
      $strInfo = getShiftType("dataShift$intRow" . "_" . $tgl, $strShift, $strExtra);
      $strResult .= "<td >$strInfo</td>\n";
    }
    $strResult .= "  </tr>\n";
    $strRoster = "
            <table cellspacing=0 class=plain>
              <tr>
                <td>Pattern</td>
                <td>:</td>
                <td>" . getPattern($db, "dataPattern" . $intRow) . "</td>
                <td>
                  <input type=\"button\" name=\"apply$intRow\" onClick=\"apply($intRow)\" value=\"" . getWords(
            "apply"
        ) . "\">&nbsp;
                  <input type=\"button\" name=\"clear$intRow\" onClick=\"clearShift($intRow)\" value=\"" . getWords(
            "clear"
        ) . "\">
                </td>
              </tr>
              <tr>
                <td>Range</td>
                <td>:</td>
                <td>" . getComboFromArray($arrDay, "dataStart$intRow", 1, "", "", false) .
        "&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;&nbsp;" .
        getComboFromArray($arrDay, "dataEnd$intRow", $intMax, "", "", false) . "</td>
                <td><input type=\"button\" name=\"applyAll$intRow\" onClick=\"applyAll($intRow)\" value=\"" . getWords(
            "apply to all"
        ) . "\"></td>
              </tr>
            </table>
          ";
    $strResult .= "<tr id='row$intRow' style='display:none'><td>&nbsp;</td><td colspan=" . ($intMax + 3) . " style=\"background-color:#eaeaea\"> " . $strRoster . "</td></tr></div>";
    $intRow++;
  }
  // footer
  global $strDataSubsection;
  global $strDataSection;
  global $strDataDepartment;
  $intTemp = $intMax - 1;
  $strResult .= "
        <tr>
          <td colspan=4>&nbsp;</td>
          <td colspan = $intMax nowrap>
            <input type='submit' name='btnSave' id='btnSave' value='" . getWords("save") . "'>
            <input type=hidden name='totalData' id='totalData' value='$intRow'>
            <input type=hidden name='totalDays' id='totalDays' value='$intMax'>
            <input type=hidden name='dataSubsection' id='dataSubsection' value='$strDataSubsection'>
            <input type=hidden name='dataSection' id='dataSection' value='$strDataSection'>
            <input type=hidden name='dataDepartment' id='dataDepartment' value='$strDataDepartment'>
            <input type=hidden name='dataMonth' id='dataMonth' value='$strMonth'>
            <input type=hidden name='dataYear' id='dataYear' value='$strYear'>
            <input type=hidden name='btnShow' id='btnShow' value='show'>
            &nbsp;
          </td>
        
        </tr>
      </table>
    ";
  return $strResult;
}

//--
// fungsi untuk menyimpan data yang dikirim
// $db = kelas database, $strError, pesan kesalahan atau pemberitahuan sukses
function saveData($db, &$strError)
{
  include_once('activity.php');
  global $words;
  global $messages;
  global $_SESSION;
  global $_REQUEST;
  $strError = "";
  $intTotalData = (isset($_REQUEST['totalData'])) ? $_REQUEST['totalData'] : 0;
  $intMonth = (isset($_REQUEST['dataMonth'])) ? $_REQUEST['dataMonth'] : 0;
  $intYear = (isset($_REQUEST['dataYear'])) ? $_REQUEST['dataYear'] : 0;
  $intTotalDays = (isset($_REQUEST['totalDays'])) ? $_REQUEST['totalDays'] : 0;
  $arrShiftList = []; // untuk menyimpan info tentang shift
  if ($intTotalData > 0) {
    $strSQL = "SELECT * FROM \"hrd_shift_type\" ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrShiftList[$rowDb['code']] = $rowDb;
    }
  }
  for ($intX = 1; $intX <= $intTotalData; $intX++) {
    $strGroupId = (isset($_REQUEST['dataSection' . $intX])) ? $_REQUEST['dataSection' . $intX] : "";
    if ($strGroupId != "") {
      // hapus dulu data yang lama
      $strSQL = "
          DELETE FROM \"hrd_shift_schedule_group\"
          WHERE \"id_group\" = '$strGroupId'
            AND (extract(year FROM \"shift_date\") = '$intYear')
            AND (extract(month FROM \"shift_date\") = '$intMonth')
        ";
      $resExec = $db->execute($strSQL);
      $strSQL = "
          DELETE FROM \"hrd_shift_schedule_employee\"
          WHERE (extract(year FROM \"shift_date\") = '$intYear')
            AND (extract(month FROM \"shift_date\") = '$intMonth')  AND \"id_employee\"  IN (";
      $strSQL .= " SELECT \"id\" FROM \"hrd_employee\" WHERE \"section_code\" = '" . $strGroupId . "') ";
      $resExec = $db->execute($strSQL);
      // loop per tanggal
      $strSQL = "";
      for ($intTgl = 1; $intTgl <= $intTotalDays; $intTgl++) {
        $strCode = (isset($_REQUEST['dataShift' . $intX . '_' . $intTgl])) ? $_REQUEST['dataShift' . $intX . '_' . $intTgl] : "";
        if ($strCode != "" && isset($arrShiftList[$strCode])) {
          $strStart = $arrShiftList[$strCode]['start_time'];
          $strFinish = $arrShiftList[$strCode]['finish_time'];
          $strDate = "$intYear-$intMonth-$intTgl";
          $strSQL = "INSERT INTO \"hrd_shift_schedule_group\" (modified_by, created_by, ";
          $strSQL .= " \"id_group\", \"shift_code\", \"shift_date\", \"start_time\", \"finish_time\") ";
          $strSQL .= "VALUES('" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "', ";
          $strSQL .= "'" . $strGroupId . "', '" . $strCode . "', '" . $strDate . "', '" . $strStart . "', '" . $strFinish . "') ";
          if ($strSQL != "") {
            $resExec = $db->execute($strSQL);
          }
          // cari daftar employee untuk group yang bersangkutan
          $strSQL = "SELECT \"id\" FROM \"hrd_employee\" WHERE \"section_code\" = '$strGroupId' ";
          $resDb = $db->execute($strSQL);
          while ($rowDb = $db->fetchrow($resDb)) {
            // insert data untuk setiap employee yg merupakan member section
            $strSQL = "INSERT INTO \"hrd_shift_schedule_employee\" (modified_by, created_by, \"id_employee\", \"shift_code\", ";
            $strSQL .= "\"shift_date\", \"start_time\", \"finish_time\") ";
            $strSQL .= "VALUES ('" . $_SESSION['sessionUserID'] . "', '" . $_SESSION['sessionUserID'] . "','";
            $strSQL .= $rowDb['id'] . "', '$strCode', '$strDate', '$strStart', '$strFinish')";
            $resExec = $db->execute($strSQL);
            // update data kehadiran, jika ada'
            $strSQL = "UPDATE \"hrd_attendance\" SET \"shift_type\" = '" . $arrShiftList[$strCode]['id'] . "' ";
            $strSQL .= "WHERE \"id_employee\" = '" . $rowDb['id'] . "' ";
            $strSQL .= "AND \"attendance_date\" = '$strDate' ";
            $resExec = $db->execute($strSQL);
          }
          writeLog(ACTIVITY_ADD, MODULE_PAYROLL, $strDate . "-" . $strGroupId, 0);
        }
      }
    }
  }
  $strDateFrom = "$intYear-$intMonth-1";
  $strDateThru = "$intYear-$intMonth-" . lastday($intMonth, $intYear);
  syncShiftAttendance($db, $strDateFrom, $strDateThru);
  $strError = $messages['data_saved'] . " >> " . date("r");
  return true;
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
//$intDefaultStart = "07:30";
//$intDefaultFinish = "16:30";
$db = new CdbClass;
if ($db->connect()) {
  // ------ AMBIL DATA KRITERIA -------------------------
  $strDataMonth = (isset($_REQUEST['dataMonth'])) ? $_REQUEST['dataMonth'] : (int)date("m");
  $strDataYear = (isset($_REQUEST['dataYear'])) ? $_REQUEST['dataYear'] : date("Y");
  //(isset($_REQUEST['dataDivision'])) ? $strDataDivision = $_REQUEST['dataDivision'] : $strDataDivision = "";
  //(isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
  //(isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
  (isset($_REQUEST['dataGroup'])) ? $strDataGroup = $_REQUEST['dataGroup'] : $strDataGroup = "";
  (isset($_REQUEST['dataView'])) ? $strDataView = $_REQUEST['dataView'] : $strDataView = "";
  (isset($_REQUEST['dataPage'])) ? $intCurrPage = $_REQUEST['dataPage'] : $intCurrPage = 1;
  if (!is_numeric($intCurrPage)) {
    $intCurrPage = 1;
  }
  //cek groupingnya berdasarkan sectionnya atau group yg dibuat
  //utk kbi bisa digunakan kedua nya, dibuatkan 2 page yang terpisah, section dan group
  /*-----------------------------------------------------------------------------------
  $strSQL = "SELECT value FROM all_setting WHERE code = 'grouping'";
  $res = $db->execute($strSQL);
  if ($row = $db->fetchrow($res))
  {
    if ($row['value'] == 1) //group shiftnya berdasar section nya
    {
      $grouping = 1;
    }
  }
  -----------------------------------------------------------------------------------*/
  if ($grouping == 0) //jika shift groupnya berdasarkan group yang ada
  {
    $strWordsGroup = getwords('group');
    $strInputGroup = getShiftGroupList($db, "dataGroup", "", $strEmptyOption, "", "");
  } else //jika shift groupnya berdasarkan sectionnya
  {
    $strWordsGroup = getwords('section');
    $strInputGroup = getSectionList($db, "dataGroup", $strDataGroup, $strEmptyOption, "", "");
  }
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      $bolError = !saveData($db, $strError);
      if ($strError != "") {
        $strMessages = $strError;
        $strMsgClass = ($bolError) ? "class=bgError" : "class=bgOK";
      }
    }
  }
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if ($strDataGroup != "") {
    if ($grouping == 0) {
      $strKriteria .= "AND t1.id = '$strDataGroup' ";
    } else {
      $strKriteria .= "AND section_code = '$strDataGroup' ";
    }
  }
  if ($bolCanView && ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN)) {
    if (isset($_REQUEST['btnShow'])) {
      // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
      $strDataDetail = getData($db, $strDataMonth, $strDataYear, $strKriteria);
      $strHidden .= "<input type=hidden name=btnShow value=show>";
    } else {
      $strDataDetail = "";
    }
  } else {
    $strDataDetail = "";
    showError("view_denied");
  }
  //--- TAMPILKAN INPUT DATA -------------------------
  // generate data hidden input dan element form input
  $intDefaultWidthPx = 200;
  $strInputMonth = getMonthList("dataMonth", $strDataMonth);
  $strInputMonth .= getYearList("dataYear", $strDataYear);
  // informasi
  $strInfo .= "<br>" . getBulan($strDataMonth) . " - $strDataYear";
  $strHidden .= "<input type=hidden name=dataMonth value=\"$strDataMonth\">";
  $strHidden .= "<input type=hidden name=dataYear value=\"$strDataYear\">";
  $strHidden .= "<input type=hidden name=dataGroup value=\"$strDataGroup\">";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>