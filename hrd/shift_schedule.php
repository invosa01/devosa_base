<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('overtime_func.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(getWords("view denied"));
}
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnExcel']));
$strUserName = $_SESSION['sessionUserName'];
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strHidden = "";
$intTotalData = 0;
$strPaging = "";
$strMessages = "";
$strMsgClass = "";
$strButtons = "";
$strWordsBySection = getwords('by section');
$strWordsByGroup = getwords('by group');
$strWordsByEmployee = getwords('by employee');
$strWordsShiftGroup = getwords('shift group');
$strWordsShiftSchedule = getwords('shift schedule');
$strWordsScheduleType = getWords('schedule type');
$strWordsWorkSchedule = getWords('work schedule');
$strWordsMonth = getwords('month');
$strWordsEmployeeID = getwords('employee id');
$strWordsDivision = getwords('division');
$strWordsDepartment = getwords('department');
$strWordsSection = getwords('section');
$strWordsSubSection = getwords('subsection');
$strWordsActive = getwords('active');
$strWordsShow = getwords('show data');
$bolError = false;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
$bolIsGet = false; // apakah daftar cuti sudah pernah diambil atau belum, biar gak 2 kali
$arrShiftType = [];
// fungsi untuk mengambil daftar jenis cuti
// input: db object, nama objek, default nilai, atribut tambahan
function getShiftType($strName, $strDefault = '', $strExtra = "")
{
  return "<input class=\"form-control-sized\" type=\"text\" name=\"$strName\" id=\"$strName\" value=\"$strDefault\" size=3  onKeyUp=\"this.value = this.value.toUpperCase()\" $strExtra>";
}

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
  $strResult = "<select class=\"form-control select2\" name=\"$strName\" id=\"$strName\" $strExtra style=\"width: 350px;\">\n";
  $strResult .= "<option value=\"\"> </option>\n";
  if (count($arrPattern) > 0) {
    foreach ($arrPattern AS $strCode => $strTmp) {
      $strSel = ($strDefault == $strCode) ? "selected" : "";
      $strResult .= "<option value=\"$strCode\" $strSel>$strCode</option>\n";
    }
  }
  $strResult .= "</select>";
  return $strResult;
}

// fungsi untuk menampilkan laporan roster
function getData($db, $strMonth, $strYear, $strKriteria)
{
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
  $strStartDate = pgDateFormat($strStartDate, "Y-m-d");
  $intStartDay = getWDay($strStartDate);
  // header
  $arrDay = [];
  $intColumn = $intMax + 4;
  $strResultT = "";
  for ($tgl = 1; $tgl <= $intMax; $tgl++) {
    $intDay = ($intStartDay + $tgl) % 7;
    $strClass = ($intDay == 1) ? "bgHoliday" : "tableHeader";
    $strStyle = ($intDay == 1 || $intDay == 0) ? " style = 'background:red;' " : "";
    if ($strStyle == '') {
      $strStyle .= 'style="width: 40px;"';
    } else {
      $strStyle .= "'width: 40px;'";
    }
    $strResultT .= "<th class=center class='$strClass' $strStyle>$tgl</th>\n";
    $arrDay[] = $tgl;
  }
  $strTableNo = getwords('no.');
  $strTableDate = getwords('date');
  $strTableName = getwords('name');
  $strTableEmployeeID = getwords('employee id');
  $strResult = "
      <table cellspacing=0 class='table table-striped table-hover table-bordered gridTable'>
      	<thead>
	        <tr class='tableHeader'>
	          <th class='tableHeader' rowspan=2>" . $strTableNo . "</th>
	          <th class='tableHeader' rowspan=2 nowrap>$strTableEmployeeID</th>
	          <th class='tableHeader' colspan=2 rowspan=2 nowrap style='width: 250px;'><div style=\"width: 250px;\">$strTableName</div></th>
	          <th class='tableHeader center' colspan=$intMax>$strTableDate</th>
	        </tr>
	        <tr class='tableHeader'>
	          $strResultT
	        </tr>
        </thead>
    ";
  $strHead = $strResult;
  // kumpulkan data jadwal shift
  $arrShift = []; // data shift karyawan
  $arrTotal = []; // menampung data total
  $strSQL = "
      SELECT \"id_employee\", \"shift_code\", extract(day from \"shift_date\") as tgl  FROM \"hrd_shift_schedule_employee\" WHERE EXTRACT(year from \"shift_date\") = '$strYear'
      AND EXTRACT(month from \"shift_date\") = '$strMonth'
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrShift[$rowDb['id_employee']][$rowDb['tgl']] = $rowDb;
  }
  // ambil data karyawan
  $arrNoList = [];
  $arrDivList = [];
  $arrDeptList = [];
  $arrSectList = [];
  $arrEmployee = [];
  $strSQL = "SELECT t1.id, t1.\"employee_name\", t1.\"employee_id\", ";
  $strSQL .= "t4.\"division_code\", t4.\"division_name\", ";
  $strSQL .= "t2.\"department_code\", t2.\"department_name\", ";
  $strSQL .= "t3.\"section_code\", t3.\"section_name\" FROM \"hrd_employee\" AS t1 ";
  $strSQL .= "LEFT JOIN \"hrd_division\" AS t4 ON t1.\"division_code\" = t4.\"division_code\" ";
  $strSQL .= "LEFT JOIN \"hrd_department\" AS t2 ON t1.\"department_code\" = t2.\"department_code\" ";
  $strSQL .= "LEFT JOIN \"hrd_section\" AS t3 ON t1.\"section_code\" = t3.\"section_code\" ";
  $strSQL .= "WHERE 1 = 1 $strKriteria ";
  $strSQL .= "ORDER BY t1.\"division_code\", t1.\"department_code\", t1.\"section_code\", t1.\"employee_name\" ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if ($rowDb['section_code'] != "") {
      $arrDivList[$rowDb['division_code']]['name'] = $rowDb['division_name'];
      $arrDeptList[$rowDb['division_code']][$rowDb['department_code']]['name'] = $rowDb['department_name'];
      $arrSectList[$rowDb['division_code']][$rowDb['department_code']][$rowDb['section_code']]['name'] = $rowDb['section_name'];
      $arrSectList[$rowDb['division_code']][$rowDb['department_code']][$rowDb['section_code']]['employee'][] = $rowDb;
    } else if ($rowDb['department_code'] != "") {
      $arrDivList[$rowDb['division_code']]['name'] = $rowDb['division_name'];
      $arrDeptList[$rowDb['division_code']][$rowDb['department_code']]['name'] = $rowDb['department_name'];
      $arrDeptList[$rowDb['division_code']][$rowDb['department_code']]['employee'][] = $rowDb;
    } else if ($rowDb['division_code'] != "") {
      $arrDivList[$rowDb['division_code']]['name'] = $rowDb['division_name'];
      $arrDivList[$rowDb['division_code']]['employee'][] = $rowDb;
    } else {
      $arrNoList[] = $rowDb;
    }
  }
  $intRow = 0;
  foreach ($arrNoList AS $rowDb) //untuk karyawan yang tidak mempunyai division code
  {
    $intRow++;
    if ($intRow % 10 == 0) {
      $strResult .= $strHead;
    }
    $strResult .= "
          <tr>
            <td>$intRow<input type=hidden name='dataEmployee$intRow' id='dataEmployee$intRow' value='" . $rowDb['id'] . "' ></td>
            <td nowrap>" . $rowDb['employee_id'] . "</td>
            <td style=\"width: 250px;\" nowrap>" . $rowDb['employee_name'] . "</td>
            <td nowrap width=50 style='border-left:none'><input class='btn btn-sm btn-primary' type='button' name='genShift$intRow' value='Roster'  onClick='roast($intRow)'></td>
        ";
    for ($tgl = 1; $tgl <= $intMax; $tgl++) {
      $strShift = (isset($arrShift[$rowDb['id']][$tgl])) ? $arrShift[$rowDb['id']][$tgl]['shift_code'] : "";
      $strExtra = " onchange = \"document.getElementById('dataEmployee$intRow').disabled = false;\" ";
      $strInfo = getShiftType("dataShift$intRow" . "_" . $tgl, $strShift, $strExtra);
      $strResult .= "<td>$strInfo</td>\n";
    }
    $strResult .= "  </tr>\n";
    $strRoster = "
          <table cellspacing=0 class=plain>
            <tr>
              <td>Pattern</td>
              <td>:</td>
              <td>" . getPattern($db, "dataPattern" . $intRow) . "</td>
              <td class=\"left\">
                <input class=\"btn btn-sm btn-primary\" type=\"button\" name=\"apply$intRow\" onClick=\"apply($intRow)\" value=\"" . getWords(
            "apply"
        ) . "\">&nbsp;
                <input class=\"btn btn-sm btn-danger\" type=\"button\" name=\"clear$intRow\" onClick=\"clearShift($intRow)\" value=\"" . getWords(
            "clear"
        ) . "\">&nbsp;
              </td>
            </tr>
            <tr>
              <td>Range</td>
              <td>:</td>
              <td>" . getComboFromArray(
            $arrDay,
            "dataStart$intRow",
            0,
            "",
            "",
            false,
            ['form-control', 'width-50', 'pull-left']
        )
        . "<div class=\"pull-left width-25 center padding-top-7\">-</div>"
        . getComboFromArray(
            $arrDay,
            "dataEnd$intRow",
            $intMax - 1,
            "",
            "",
            false,
            ['form-control', 'width-50', 'pull-left']
        ) . "</td>
              <td class=\"left\"><input class=\"btn btn-sm btn-primary\" type=\"button\" name=\"applyAll$intRow\" onClick=\"applyAll($intRow)\" value=\"" . getWords(
            "apply to all"
        ) . "\"></td>
            </tr>
          </table>
        ";
    $strResult .= "<tr id='row$intRow' style='display:none'><td>&nbsp;</td><td colspan=" . ($intMax + 3) . " style=\"background-color:#eaeaea\"> " . $strRoster . "</td></tr></div>";
  }
  //batas1
  foreach ($arrDivList AS $strDivCode => $arrTmpDiv) // per division
  {
    $strResult .= "
        <tr>
          <td colspan='$intColumn' nowrap class='bgNewRevised left'>
          <strong>$strDivCode - " . $arrTmpDiv['name'] . "</strong>&nbsp;
          </td>
        </tr>
      ";
    // tampilkan data employee di divisi tersebut
    $arrDivEmpl = (isset($arrTmpDiv['employee'])) ? $arrTmpDiv['employee'] : [];
    foreach ($arrDivEmpl AS $intTmp => $rowDb) {
      $intRow++;
      if ($intRow % 10 == 0) {
        $strResult .= $strHead;
      }
      $strResult .= "
          <tr>
            <td>$intRow<input type=hidden name='dataEmployee$intRow' id='dataEmployee$intRow' value='" . $rowDb['id'] . "' ></td>
            <td nowrap>" . $rowDb['employee_id'] . "</td>
            <td style=\"width: 250px;\" nowrap>" . $rowDb['employee_name'] . "</td>
            <td nowrap width=50 style='border-left:none'><input class='btn btn-sm btn-primary' type='button' name='genShift$intRow' value='Roster'  onClick='roast($intRow)'></td>
        ";
      for ($tgl = 1; $tgl <= $intMax; $tgl++) {
        $strShift = (isset($arrShift[$rowDb['id']][$tgl])) ? $arrShift[$rowDb['id']][$tgl]['shift_code'] : "";
        $strExtra = " onchange = \"document.getElementById('dataEmployee$intRow').disabled = false;\" title=" . $rowDb['employee_name'];
        $strInfo = getShiftType("dataShift$intRow" . "_" . $tgl, $strShift, $strExtra);
        $strResult .= "<td>$strInfo</td>\n";
      }
      $strResult .= "  </tr>\n";
      $strRoster = "
          <table cellspacing=0 class=plain>
            <tr>
              <td>Pattern</td>
              <td>:</td>
              <td>" . getPattern($db, "dataPattern" . $intRow) . "</td>
              <td class=\"left\">
                <input class=\"btn btn-sm btn-primary\" type=\"button\" name=\"apply$intRow\" onClick=\"apply($intRow)\" value=\"" . getWords(
              "apply"
          ) . "\">&nbsp;
                <input class=\"btn btn-sm btn-danger\" type=\"button\" name=\"clear$intRow\" onClick=\"clearShift($intRow)\" value=\"" . getWords(
              "clear"
          ) . "\">&nbsp;
              </td>
            </tr>
            <tr>
              <td>Range</td>
              <td>:</td>
              <td>" . getComboFromArray(
              $arrDay,
              "dataStart$intRow",
              0,
              "",
              "",
              false,
              ['form-control', 'width-50', 'pull-left']
          )
          . "<div class=\"pull-left width-25 center padding-top-7\">-</div>"
          . getComboFromArray(
              $arrDay,
              "dataEnd$intRow",
              $intMax - 1,
              "",
              "",
              false,
              ['form-control', 'width-50', 'pull-left']
          ) . "</td>
              <td class=\"left\"><input class=\"btn btn-sm btn-primary\" type=\"button\" name=\"applyAll$intRow\" onClick=\"applyAll($intRow)\" value=\"" . getWords(
              "apply to all"
          ) . "\"></td>
            </tr>
          </table>
        ";
      $strResult .= "<tr id='row$intRow' style='display:none'><td>&nbsp;</td><td colspan=" . ($intMax + 3) . " style=\"background-color:#eaeaea\"> " . $strRoster . "</td></tr></div>";
    }
    //tampilkan data department di dalam nya
    if (isset($arrDeptList[$strDivCode])) {
      foreach ($arrDeptList[$strDivCode] AS $strDeptCode => $arrTmpDept) {
        $strResult .= "
            <tr>
              <td colspan='$intColumn' nowrap class='bgNewRevised left'>
              <strong><em>$strDeptCode - " . $arrTmpDept['name'] . "</em></strong>&nbsp;
              </td>
            </tr>
          ";
        // tampilkan data employee di department tersebut
        $arrDeptEmpl = (isset($arrTmpDept['employee'])) ? $arrTmpDept['employee'] : [];
        foreach ($arrDeptEmpl AS $intTmp => $rowDb) {
          $intRow++;
          if ($intRow % 10 == 0) {
            $strResult .= $strHead;
          }
          $strResult .= "
              <tr>
                <td>$intRow<input type=hidden name='dataEmployee$intRow' id='dataEmployee$intRow' value='" . $rowDb['id'] . "'></td>
                <td nowrap>" . $rowDb['employee_id'] . "</td>
                <td style=\"width: 250px;\" nowrap>" . $rowDb['employee_name'] . "</td>
                <td nowrap style='border-left:none'><input class='btn btn-sm btn-primary' type='button' name='genShift$intRow' value='Roster'  onClick='roast($intRow)'></td>
            ";
          for ($tgl = 1; $tgl <= $intMax; $tgl++) {
            $strShift = (isset($arrShift[$rowDb['id']][$tgl])) ? $arrShift[$rowDb['id']][$tgl]['shift_code'] : "";
            //$strFlag = (isset($arrShift[$rowDb['id']][$tgl])) ? $arrShift[$rowDb['id']][$tgl]['replace_friday'] : "";
            $strExtra = " onchange = \"document.getElementById('dataEmployee$intRow').disabled = false;\"  title=" . $rowDb['employee_name'];
            $strInfo = getShiftType("dataShift$intRow" . "_" . $tgl, $strShift, $strExtra);
            $strResult .= "<td>$strInfo</td>\n";
          }
          $strResult .= "  </tr>\n";
          $strRoster = "
                <table cellspacing=0 class=plain>
                  <tr>
                    <td>Pattern</td>
                    <td>:</td>
                    <td>" . getPattern($db, "dataPattern" . $intRow) . "</td>
                    <td class=\"left\">
                      <input class=\"btn btn-sm btn-primary\" type=\"button\" name=\"apply$intRow\" onClick=\"apply($intRow)\" value=\"" . getWords(
                  "apply"
              ) . "\"> &nbsp;
                      <input class=\"btn btn-sm btn-danger\" type=\"button\" name=\"clear$intRow\" onClick=\"clearShift($intRow)\" value=\"" . getWords(
                  "clear"
              ) . "\">
                    </td>
                  </tr>
                  <tr>
                    <td>Range</td>
                    <td>:</td>
                    <td>" . getComboFromArray(
                  $arrDay,
                  "dataStart$intRow",
                  0,
                  "",
                  "",
                  false,
                  ['form-control', 'width-50', 'pull-left']
              ) .
              "<div class=\"pull-left width-25 center padding-top-7\">-</div>" .
              getComboFromArray(
                  $arrDay,
                  "dataEnd$intRow",
                  $intMax - 1,
                  "",
                  "",
                  false,
                  ['form-control', 'width-50', 'pull-left']
              ) . "</td>
                    <td class=\"left\"><input class=\"btn btn-sm btn-primary\" type=\"button\" name=\"applyAll$intRow\" onClick=\"applyAll($intRow)\" value=\"" . getWords(
                  "apply to all"
              ) . "\")</td>
                  </tr>
                </table>
              ";
          $strResult .= "<tr id='row$intRow' style='display:none'><td>&nbsp;</td><td colspan=" . ($intMax + 3) . " style=\"background-color:#eaeaea\"> " . $strRoster . "</td></tr></div>";
        }
        //tampilkan data section di dalamnya
        if (isset($arrSectList[$strDivCode][$strDeptCode])) {
          foreach ($arrSectList[$strDivCode][$strDeptCode] AS $strSectCode => $arrTmpSect) {
            //if ($strDeptCode == 'MRL') print_r($arrTmpSect);
            $strResult .= "
                <tr>
                  <td colspan='$intColumn' nowrap class='bgNewRevised left'>
                  <strong><em>$strSectCode - " . $arrTmpSect['name'] . "</em></strong>&nbsp;
                  </td>
                </tr>
              ";
            // tampilkan data employee di section tersebut
            $arrSectEmpl = (isset($arrTmpSect['employee'])) ? $arrTmpSect['employee'] : [];
            foreach ($arrSectEmpl AS $intTmp => $rowDb) {
              $intRow++;
              if ($intRow % 10 == 0) {
                $strResult .= $strHead;
              }
              $strResult .= "
                  <tr>
                    <td>$intRow<input type=hidden name='dataEmployee$intRow' id='dataEmployee$intRow' value='" . $rowDb['id'] . "'></td>
                    <td nowrap>" . $rowDb['employee_id'] . "</td>
                    <td style=\"width: 250px;\" nowrap>" . $rowDb['employee_name'] . "</td>
                    <td nowrap style='border-left:none'><input class='btn btn-sm btn-primary' type='button' name='genShift$intRow' value='Roster'  onClick='roast($intRow)'></td>
                ";
              for ($tgl = 1; $tgl <= $intMax; $tgl++) {
                $strShift = (isset($arrShift[$rowDb['id']][$tgl])) ? $arrShift[$rowDb['id']][$tgl]['shift_code'] : "";
                //$strFlag = (isset($arrShift[$rowDb['id']][$tgl])) ? $arrShift[$rowDb['id']][$tgl]['replace_friday'] : "";
                $strExtra = " onchange = \"document.getElementById('dataEmployee$intRow').disabled = false;\"  title=" . $rowDb['employee_name'];
                $strInfo = getShiftType("dataShift$intRow" . "_" . $tgl, $strShift, $strExtra);
                $strResult .= "<td>$strInfo</td>\n";
              }
              $strResult .= "  </tr>\n";
              $strRoster = "
                    <table cellspacing=0 class=plain>
                      <tr>
                        <td>Pattern</td>
                        <td>:</td>
                        <td>" . getPattern($db, "dataPattern" . $intRow) . "</td>
                        <td class=\"left\">
                          <input class=\"btn btn-sm btn-primary\" type=\"button\" name=\"apply$intRow\" onClick=\"apply($intRow)\" value=\"" . getWords(
                      "apply"
                  ) . "\"> &nbsp;
                          <input class=\"btn btn-sm btn-danger\" type=\"button\" name=\"clear$intRow\" onClick=\"clearShift($intRow)\" value=\"" . getWords(
                      "clear"
                  ) . "\">
                        </td>
                      </tr>
                      <tr>
                        <td>Range</td>
                        <td>:</td>
                        <td>" . getComboFromArray(
                      $arrDay,
                      "dataStart$intRow",
                      0,
                      "",
                      "",
                      false,
                      ['form-control', 'width-50', 'pull-left']
                  ) .
                  "<div class=\"pull-left width-25 center padding-top-7\">-</div>" .
                  getComboFromArray(
                      $arrDay,
                      "dataEnd$intRow",
                      $intMax - 1,
                      "",
                      "",
                      false,
                      ['form-control', 'width-50', 'pull-left']
                  ) . "</td>
                        <td class=\"left\"><input class=\"btn btn-sm btn-primary\" type=\"button\" name=\"applyAll$intRow\" onClick=\"applyAll($intRow)\" value=\"" . getWords(
                      "apply to all"
                  ) . "\")</td>
                      </tr>
                    </table>
                  ";
              $strResult .= "<tr id='row$intRow' style='display:none'><td>&nbsp;</td><td colspan=" . ($intMax + 3) . " style=\"background-color:#eaeaea\"> " . $strRoster . "</td></tr></div>";
            }
          }
        }
      }
    }
  }
  // footer
  global $strDataEmployee, $strDataDivision, $strDataDepartment, $strDataSection, $strDataSubSection/*, $strDataGroup*/
         ;
  $strResult .= "
        <tr>
           <td colspan=\"" . ($intMax + 4) . "\" nowrap class=\"left form-actions\">
           	<input class='btn btn-sm btn-primary' type='submit' name='btnSave'      id='btnSave'        value='" . getWords(
          "save"
      ) . "'>
            <input type=hidden name='totalData'      id='totalData'      value='$intRow'>
            <input type=hidden name='totalDays'      id='totalDays'      value='$intMax'>
            <input type=hidden name='dataEmployee'   id='dataEmployee'   value='$strDataEmployee'>
            <input type=hidden name='dataSubSection' id='dataSubSection' value='$strDataSubSection'>
            <input type=hidden name='dataSection'    id='dataSection'    value='$strDataSection'>
            <input type=hidden name='dataDepartment' id='dataDepartment' value='$strDataDepartment'>
            <input type=hidden name='dataDivision'   id='dataDivision'   value='$strDataDivision'>
            <input type=hidden name='dataMonth'      id='dataMonth'      value='$strMonth'>
            <input type=hidden name='dataYear'       id='dataYear'       value='$strYear'>
            <input type=hidden name='btnShow'        id='btnShow'        value='show'>
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
function saveData($db, &$strError, $strKriteria)
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
    $strIDEmployee = (isset($_REQUEST['dataEmployee' . $intX])) ? $_REQUEST['dataEmployee' . $intX] : "";
    if ($strIDEmployee != "") {
      // hapus dulu data yang lama
      $strSQL = "
          DELETE FROM \"hrd_shift_schedule_employee\"
          WHERE \"id_employee\" = '$strIDEmployee'
            AND (extract(year FROM \"shift_date\") = '$intYear')
            AND (extract(month FROM \"shift_date\") = '$intMonth'); 
        ";
      //$resExec = $db->execute($strSQL);
      // loop per tanggal
      for ($intTgl = 1; $intTgl <= $intTotalDays; $intTgl++) {
        $strCode = (isset($_REQUEST['dataShift' . $intX . '_' . $intTgl])) ? $_REQUEST['dataShift' . $intX . '_' . $intTgl] : "";
        if ($strCode != "" && isset($arrShiftList[$strCode])) {
          $strStart = $arrShiftList[$strCode]['start_time'];
          $strFinish = $arrShiftList[$strCode]['finish_time'];
          $strDate = "$intYear-$intMonth-$intTgl";
          $strSQL .= "
              INSERT INTO \"hrd_shift_schedule_employee\"
              (\"id_employee\", \"shift_code\", \"shift_date\", \"start_time\", \"finish_time\", flag)
              VALUES ('$strIDEmployee', '$strCode', '$strDate', '$strStart', '$strFinish', 0);
            ";
        }
      }
      if ($strSQL != "") {
        $resExec = $db->execute($strSQL);
      }
      if ($strSQL != "") {
        $resExec = $db->execute($strSQL);
      }
    }
  }
  // PR -- CEK keterkaitan dengan data kehadiran, absen dan gaji (jika ada)
  $strDateFrom = "$intYear-$intMonth-1";
  $strDateThru = "$intYear-$intMonth-" . lastday($intMonth, $intYear);
  syncShiftAttendance($db, $strDateFrom, $strDateThru, $strKriteria);
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
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  $strDataActive = getSessionValue('sessiondataActive');
  if (isset($_REQUEST['dataActive'])) {
    $strDataActive = $_REQUEST['dataActive'];
  }
  if ($strDataActive == "") {
    $strDataActive = 1;
  }
  $_SESSION['sessiondataActive'] = $strDataActive;
  // ------ AMBIL DATA KRITERIA -------------------------
  $strDataMonth = (isset($_REQUEST['dataMonth'])) ? $_REQUEST['dataMonth'] : (int)date("m");
  $strDataYear = (isset($_REQUEST['dataYear'])) ? $_REQUEST['dataYear'] : date("Y");
  (isset($_REQUEST['dataDivision'])) ? $strDataDivision = $_REQUEST['dataDivision'] : $strDataDivision = "";
  (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
  (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
  (isset($_REQUEST['dataSubSection'])) ? $strDataSubSection = $_REQUEST['dataSubSection'] : $strDataSubSection = "";
  //(isset($_REQUEST['dataGroup']))      ? $strDataGroup      = $_REQUEST['dataGroup']      : $strDataGroup = "";
  (isset($_REQUEST['dataEmployee'])) ? $strDataEmployee = $_REQUEST['dataEmployee'] : $strDataEmployee = "";
  (isset($_REQUEST['dataView'])) ? $strDataView = $_REQUEST['dataView'] : $strDataView = "";
  (isset($_REQUEST['dataPage'])) ? $intCurrPage = $_REQUEST['dataPage'] : $intCurrPage = 1;
  if (!is_numeric($intCurrPage)) {
    $intCurrPage = 1;
  }
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if ($strDataDivision != "") {
    $strKriteria .= "AND t1.\"division_code\" = '$strDataDivision' ";
  }
  if ($strDataDepartment != "") {
    $strKriteria .= "AND t1.\"department_code\" = '$strDataDepartment' ";
  }
  if ($strDataSection != "") {
    $strKriteria .= "AND t1.\"section_code\" = '$strDataSection' ";
  }
  if ($strDataSubSection != "") {
    $strKriteria .= "AND t1.\"sub_section_code\" = '$strDataSubSection' ";
  }
  /*if ($strDataGroup != "") {
    $strKriteria .= "AND t1.\"group_code\" = '$strDataGroup' ";
  }*/
  if ($strDataEmployee != "") {
    $strKriteria .= "AND t1.\"employee_id\" = '$strDataEmployee' ";
  }
  if ($strDataActive != "") {
    $strKriteria .= "AND t1.\"active\" = '$strDataActive' ";
  }
  $strKriteria .= $strKriteriaCompany;
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      $bolError = !saveData($db, $strError, $strKriteria);
      if ($strError != "") {
        $strMessages = $strError;
        $strMsgClass = ($bolError) ? "class=bgError" : "class=bgOK";
      }
    }
  }
  if ($bolCanView) {
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
  //var_dump($arrUserInfo);exit;
  $intDefaultWidthPx = 200;
  $strInputMonth = getMonthList("dataMonth", $strDataMonth);
  $strInputMonth .= getYearList("dataYear", $strDataYear);
  //handle user company-access-right
  $strInputCompany = getCompanyList(
      $db,
      "dataCompany",
      $strDataCompany,
      $strEmptyOption2,
      $strKriteria2,
      "style=\"width:$intDefaultWidthPx\" "
  );
  $strInputDivision = getDivisionList(
      $db,
      "dataDivision",
      $strDataDivision,
      $strEmptyOption2,
      (SET_FILTERING === true and (integer)$arrUserInfo['id_adm_group'] !== HRD_ADMIN_ID) ? getCriteria('division_code') : '',
      "style=\"width:$intDefaultWidthPx\""
  );
  $strInputDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $strDataDepartment,
      $strEmptyOption2,
      (SET_FILTERING === true and (integer)$arrUserInfo['id_adm_group'] !== HRD_ADMIN_ID) ? getCriteria('department_code') : '',
      "style=\"width:$intDefaultWidthPx\"" . $ARRAY_DISABLE_GROUP['department']
  );
  $strInputSection = getSectionList(
      $db,
      "dataSection",
      $strDataSection,
      $strEmptyOption2,
      (SET_FILTERING === true and (integer)$arrUserInfo['id_adm_group'] !== HRD_ADMIN_ID) ? getCriteria('section_code') : '',
      "style=\"width:$intDefaultWidthPx\"" . $ARRAY_DISABLE_GROUP['section']
  );
  $strInputSubsection = getSubSectionList(
      $db,
      "dataSubSection",
      $strDataSubSection,
      $strEmptyOption2,
      (SET_FILTERING === true and (integer)$arrUserInfo['id_adm_group'] !== HRD_ADMIN_ID) ? getCriteria('sub_section_code') : '',
      "style=\"width:$intDefaultWidthPx\"" . $ARRAY_DISABLE_GROUP['sub_section']
  );
  //$strInputGroup = getGroupList($db,"dataGroup",$strDataGroup, $strEmptyOption,""," style=\"width:$intDefaultWidthPx\"");
  $strInputEmployee = "<input class=form-control type=text name=dataEmployee id=dataEmployee maxlength=30 value=\"$strDataEmployee\" style=\"width:$intDefaultWidthPx\">";
  $strInputActive = getEmployeeActiveList(
      "dataActive",
      $strDataActive,
      $strEmptyOption,
      " style=\"width:$intDefaultWidthPx\" "
  );
  // informasi
  $strInfo .= "&nbsp;&nbsp;" . getBulan($strDataMonth) . " - $strDataYear";
  $strHidden .= "<input type=hidden name=dataMonth value=\"$strDataMonth\">";
  $strHidden .= "<input type=hidden name=dataYear value=\"$strDataYear\">";
  $strHidden .= "<input type=hidden name=dataDivision value=\"$strDataDivision\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
  $strHidden .= "<input type=hidden name=dataSubSection value=\"$strDataSubSection\">";
  $strHidden .= "<input type=hidden name=dataActive value=\"$strDataActive\">";
  $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strCompany = getWords("Company");
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('work schedule management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = dataShiftTypeSubmenu($strWordsWorkSchedule);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
if (isset($_REQUEST['btnPrint'])) {
  $strInfo = getBulan($strDataMonth) . " - $strDataYear";
  $strSQL = "SELECT code, note FROM hrd_shift_type ORDER BY code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrShiftType[] = ["code" => $rowDb['code'], "name" => $rowDb['note']];
  }
  $tbsPage->PlugIn(TBS_INSTALL, TBS_EXCEL);
  $tbsPage->LoadTemplate($strTemplateFile);
  $tbsPage->MergeBlock('emp', $arrData);
  $tbsPage->MergeBlock('shift', $arrShiftType);
  $tbsPage->PlugIn(TBS_EXCEL, TBS_EXCEL_FILENAME, 'shift_schedule.xls');
} else {
  $tbsPage->LoadTemplate($strMainTemplate);
}
$tbsPage->Show();
?>