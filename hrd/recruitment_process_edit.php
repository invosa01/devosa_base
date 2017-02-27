<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
//include_once('../includes/krumo/class.krumo.php');
$arrInvitationMethod = [
    "Telp"           => "Telp",
    "SMS"            => "SMS",
    "Email/Internet" => "Email/Internet",
    "Letter"         => "Letter",
    "Other"          => "Other"
];
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
$strTemplateFile = getTemplate("recruitment_process_edit.html");
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intDefaultWidth = 30;
$intDefaultWidthPx = 120;
$intDefaultWidthPx2 = 500;
$intDefaultHeight = 5;
$strInputFiles = "";
$strMessages = "";
$strMsgClass = "";
$strCalendarSetup = "";
$strNow = date("Y-m-d");
$arrData = []; // inisialisasi
$arrData['id_candidate'] = "";
$arrData['id_recruitment_need'] = "";
$arrData['mrf_number'] = "";
$arrData['candidate_name'] = "";
$arrData['applyDate'] = "";
$arrData['position'] = "";
$arrData['id'] = "";
$arrData['result'] = 0;
$arrData['salary'] = 0;
$arrData['point'] = 0;
$arrData['note'] = "";
$arrData['start_date'] = "";
$arrData['invitation_date'] = $strNow;
$arrData['invitation_method'] = "";
$strInputLetter = "";
$strWordConfirmDelete = getWords("confirm_delete");
$arrInterviewType = []; // mencatat jenis-jenis proses rekrutmen yang merupakan interview
$arrTestDriverType = []; // mencatat jenis-jenis test drive
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk mengambil data jenis proses, output dalam array
function getRecruitmentProcessData($db)
{
  global $strInterviewType;
  global $arrInterviewType;
  global $arrType;
  global $arrTestDriverType;
  $arrResult = [];
  $strSQL = "
      SELECT * FROM hrd_recruitment_process_type ORDER BY step
    ";
  $res = $db->execute($strSQL);
  while ($row = $db->fetchrow($res)) {
    $arrResult[$row['name']] = $row['name'];
    if ($row['letter_type'] == 1) {
      $arrInterviewType[$row['name']] = $row['name'];
      $arrType[$row['name']] = $row['letter_type'];
    } else if ($row['letter_type'] == 2) // test driver
    {
      $arrTestDriverType[$row['name']] = $row['name'];
      $arrType[$row['name']] = $row['letter_type'];
    }
  }
  return $arrResult;
}

// fungsi untuk ambil jenis proses rekrutmen, dalam bentuk combo
function getRecruitmentProcessList($strName, $strDefault = "", $strAction = "")
{
  global $arrProcessType;
  $strResult = "<select name='$strName' id='$strName' class=\"form-control select2\" $strAction>";
  $strResult .= "<option value=''> </option>";
  foreach ($arrProcessType AS $strType => $strInfo) {
    $strResult .= "<option value='" . $strType . "' " . (($strDefault == $strType) ? "selected" : "") . ">$strInfo</option>";
  }
  $strResult .= "</select>";
  return $strResult;
}

// fungsi untuk buat baris untuk bagian detail proses recruitment
function getRecruitmentProcessDetail($intRows, $arrInfo, $strDataID)
{
  global $strDataDetail;
  global $strCalendarSetup;
  global $arrProcessType;
  global $strInterviewType;
  global $arrInterviewType;
  global $arrType;
  global $arrTestDriverType;
  $strStyle = ($intRows == 1 || count($arrInfo) > 0) ? "" : "style='display:none' ";
  $strDefaultSubject = ($intRows == 1 && count($arrInfo) == 0 && count(
          $arrTmp = array_values($arrProcessType)
      ) > 0) ? $arrTmp[0] : "";
  $strLetterButton = "";
  if (count($arrInfo) == 0) {
    $strProcess = "";
    $strSubject = "";
    $strSchedule = "";
    $strPlace = "";
    $strDate = "";
    $strPIC = "";
    $strResult = "";
    $strLetters = "";
    $strNote = "";
    $strProcessID = "";
  } else {
    $strProcess = $arrInfo['process_name'];
    $strSubject = $arrInfo['subject'];
    $strSchedule = $arrInfo['schedule_date'];
    $strPlace = $arrInfo['place'];
    $strDate = $arrInfo['process_date'];
    $strPIC = $arrInfo['pic'];
    $strResult = $arrInfo['result'];
    $strLetters = "";
    $strNote = $arrInfo['note'];
    $strProcessID = $arrInfo['id'];
    if (isset($arrType[$strProcess])) {
      $type = $arrType[$strProcess];
    }
    //if ($strProcess == $strInterviewType)
    if (isset($arrInterviewType[$strProcess])) {
      // $strLetterButton = "
      // <br /><input type='button' name='btnLetter$intRows' id='btnLetter$intRows' value='" .getWords("print interview form")."' onClick=\"printInterview('" .$strProcessID."','" .$type."')\" >";
      // $strLetterButton .= "<input type='button' name='btnLetter$intRows' id='btnLetter$intRows' value='" .getWords("entry interview result")."' onClick= entryinterview('" .$strProcessID."','" .$strDataID."','" .$type."') >";
    } else if (isset($arrTestDriverType[$strProcess])) {
      // $strLetterButton = "
      // <br /><input type='button' name='btnLetter$intRows' id='btnLetter$intRows' value='" .getWords("print test driver form")."' onClick=\"printInterview('" .$strProcessID."','" .$type."')\" >";
      // $strLetterButton .= "<input type='button' name='btnLetter$intRows' id='btnLetter$intRows' value='" .getWords("entry test driver result")."' onClick= entryinterview('" .$strProcessID."','" .$strDataID."','" .$type."') >";
    }
  }
  $strDataDetail .= "
      	<fieldset id=\"detailProcess$intRows\" $strStyle>
      		<legend><b>Process #$intRows</b></legend>
      		<div class=\"col-md-4\">
						<div class=\"form-group\">
							<label class=\"col-sm-4 control-label\" for=\"dataType\">" . getWords('process') . "</label>
							<input type=hidden name=detailID$intRows value=\"$strProcessID\">
							<div class=\"col-sm-8\">
								" . getRecruitmentProcessList(
          "detailProcessName$intRows",
          $strProcess,
          "onChange=\"document.getElementById('detailSubject$intRows').value = this.value;\" "
      ) . "
							</div>
						</div>
						<div class=\"form-group\">
							<label class=\"col-sm-4 control-label\" for=\"dataType\">" . getWords('schedule date') . "</label>
							<div class=\"col-sm-8\">
								<input class=form-control type=text name=detailSchedule$intRows id=detailSchedule$intRows size=15 maxlength=10 value=\"$strSchedule\">
							</div>
						</div>
						<div class=\"form-group\">
							<label class=\"col-sm-4 control-label\" for=\"dataType\">" . getWords('process date') . "</label>
							<div class=\"col-sm-8\">
								<input class=form-control type=text name=detailDate$intRows id=detailDate$intRows size=15 maxlength=10 value=\"$strDate\">
							</div>
						</div>
						<div class=\"form-group\">
							<label class=\"col-sm-4 control-label\" for=\"dataType\">" . getWords('result') . "</label>
							<div class=\"col-sm-8\">
								" . getRecruitmentResultList(
          "detailResult$intRows",
          $strResult,
          "",
          " onChange=\"document.getElementByID('dataResult').value = this.value;\""
      ) . "
							</div>
						</div>
						<div class=\"form-group\">
							<label class=\"col-sm-4 control-label\" for=\"dataType\">" . getWords('subject') . "</label>
							<input type=hidden name=detailID$intRows value=\"$strProcessID\">
							<div class=\"col-sm-8\">
								<input class=form-control type=text size=50 maxlength=100 name='detailSubject$intRows' id='detailSubject$intRows' value=\"$strSubject\">
							</div>
						</div>
					</div>
					<div class=\"col-md-4\">
						<div class=\"form-group\">
							<label class=\"col-sm-4 control-label\" for=\"dataType\">" . getWords('place') . "</label>
							<div class=\"col-sm-8\">
								<input class=form-control type=text size=30 maxlength=50 name=detailPlace$intRows value=\"$strPlace\">
							</div>
						</div>
						<div class=\"form-group\">
							<label class=\"col-sm-4 control-label\" for=\"dataType\">" . getWords('PIC') . "</label>
							<div class=\"col-sm-8\">
								<input class=form-control type=text size=30 maxlength=50 name=detailPIC$intRows value=\"$strPIC\">
							</div>
						</div>
						<div class=\"form-group\">
							<label class=\"col-sm-4 control-label\" for=\"dataType\">" . getWords('note') . "</label>
							<div class=\"col-sm-8\">
								<textarea class=form-control id='detailNote$intRows' name='detailNote$intRows' rows='3'>$strNote</textarea>
							</div>
						</div>
					</div>
          </fieldset>
    ";
  // tambahkan di handle calendar setup
  $strCalendarSetup .= "Calendar.setup({ inputField:\"detailDate$intRows\", button:\"btnDate$intRows\" });";
  $strCalendarSetup .= "Calendar.setup({ inputField:\"detailSchedule$intRows\", button:\"btnSchedule$intRows\" });";
}

// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db, &$arrData, $strDataID = "", $strDataCandidateID = "")
{
  global $words;
  global $strDataDetail;
  global $strCalendarSetup;
  global $intDefaultWidthPx;
  global $intDefaultWidthPx2;
  global $intDefaultWidth;
  global $intDefaultHeight;
  global $arrProcessType;
  $bolNewData = true;
  $arrLetter = getDataLetters($db, $strDataID); // ambil daftar surat
  $arrProcessType = getRecruitmentProcessData($db);
  $arrDetail = [];
  if ($strDataID != "") {
    $strSQL = "
        SELECT t1.*, t2.candidate_name,  t2.application_date,
          t2.position as pos, t3.request_number
        FROM hrd_recruitment_process AS t1
        LEFT JOIN hrd_candidate AS t2 ON t1.id_candidate = t2.id
        LEFT JOIN hrd_recruitment_need AS t3 ON t2.id_recruitment_need = t3.id
        WHERE t1.id = '$strDataID'
      ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $bolNewData = false;
      $arrData['id_candidate'] = $rowDb['id_candidate'];
      $arrData['invitation_date'] = $rowDb['invitation_date'];
      $arrData['invitation_method'] = $rowDb['invitation_method'];
      $arrData['candidate_name'] = $rowDb['candidate_name'];
      $arrData['applyDate'] = $rowDb['application_date'];
      $arrData['position'] = $rowDb['pos'];
      $arrData['note'] = $rowDb['note'];
      $arrData['result'] = $rowDb['result'];
      $arrData['salary'] = $rowDb['salary'];
      $arrData['point'] = $rowDb['point'];
      $arrData['start_date'] = $rowDb['start_date'];
      $arrData['id_recruitment_need'] = $rowDb['id_recruitment_need'];
      $arrData['mrf_number'] = $rowDb['request_number'];
      writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$strDataID -> " . $rowDb['candidate_name'], 0);
    }
    // ambil dta detailnya, simpan dalam aarray
    $i = 0;
    $strSQL = "
        SELECT * FROM hrd_recruitment_process_detail
        WHERE id_recruitment_process = '$strDataID'
        ORDER BY schedule_date
      ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $i++;
      //$arrDetail[$rowDb['process_name']] = $rowDb;
      $arrDetail[$i] = $rowDb;
    }
  } else if ($strDataCandidateID != "") { // belum ada proses, ambil dta kandidat aja
    $strSQL = "
        SELECT t1.*, t3.request_number
        FROM hrd_candidate AS t1
        LEFT JOIN hrd_recruitment_need AS t3 ON t1.id_recruitment_need = t3.id
        WHERE t1.id = '$strDataCandidateID'
      ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      //$bolNewData = false;
      $arrData['id_candidate'] = $rowDb['id'];
      $arrData['candidate_name'] = $rowDb['candidate_name'];
      $arrData['applyDate'] = $rowDb['application_date'];
      $arrData['position'] = $rowDb['position'];
      $arrData['id_recruitment_need'] = $rowDb['id_recruitment_need'];
      $arrData['mrf_number'] = $rowDb['request_number'];
      /*
              $tblRecruitment = new cModel("hrd_recruitment_need");
              if ($arrTemp = $tblRecruitment->find("status<>".REQUEST_STATUS_FINISHED."
                                                    AND position='".$rowDb['position']."'
                                                    AND recruitment_date <= '".$rowDb['application_date']."'",
                                                  "id",
                                                  "recruitment_date DESC"))
              {
                $arrData['id_recruitment_need'] = $arrTemp['id'];
              }
              else if ($arrTemp = $tblRecruitment->find("position='".$rowDb['position']."'
                                                    AND recruitment_date <= '".$rowDb['application_date']."'",
                                                  "id",
                                                  "recruitment_date DESC"))
              {
                $arrData['id_recruitment_need'] = $arrTemp['id'];
              }
      */
      writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "-> " . $rowDb['candidate_name'], 0);
    }
  }
  // tampilkan pilihan proses recruitment
  $intCount = count($arrDetail) + 15;
  for ($i = 1; $i <= $intCount; $i++) {
    getRecruitmentProcessDetail($i, ((isset($arrDetail[$i])) ? $arrDetail[$i] : []), $strDataID);
  }
  // footer
  $intShow = (count($arrDetail) > 0) ? count($arrDetail) : 1;
  $strDataDetail .= "
    <div class=\"col-md-12\">
      <input class=\"btn btn-xs btn-danger\" type='button' name='btnAdd' id='btnAdd' value=\"" . getWords(
          'add more process'
      ) . "\" onClick='showMore()'>
      <input type=hidden id='dataTotalProcess' name='dataTotalProcess' value='$intCount'>
      <input type=hidden id='dataShowProcess' name='dataShowProcess' value='$intShow'>
    </div>";
  /*
  // ambil dta proses recruitment
  $intRows = 0;
  $strSQL  = "SELECT * FROM hrd_recruitment_process_type ";
  $strSQL .= "ORDER BY step ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb))
  {
    if (($strProcess = $rowDb['name']) != "")
    {
      $intRows++;
      $strProcessID = (isset($arrDetail[$strProcess]['id'])) ? $arrDetail[$strProcess]['id'] : "";
      $strDate = (isset($arrDetail[$strProcess]['process_date'])) ? $arrDetail[$strProcess]['process_date'] : "";
      $strSchedule = (isset($arrDetail[$strProcess]['schedule_date'])) ? $arrDetail[$strProcess]['schedule_date'] : "";
      $strPlace = (isset($arrDetail[$strProcess]['place'])) ? $arrDetail[$strProcess]['place'] : "";
      $strPIC = (isset($arrDetail[$strProcess]['pic'])) ? $arrDetail[$strProcess]['pic'] : "";
      $strResult = (isset($arrDetail[$strProcess]['result'])) ? $arrDetail[$strProcess]['result'] : 0;
      $strNote = (isset($arrDetail[$strProcess]['note'])) ? $arrDetail[$strProcess]['note'] : "";
      // buat data inputnya
      $strDate  = "<input type=text name=detailDate$intRows id=detailDate$intRows size=15 maxlength=10 value=\"$strDate\">";
      $strDate .= " <input type=button name=btnDate$intRows id=btnDate$intRows value='..'>";
      $strSchedule  = "<input type=text name=detailSchedule$intRows id=detailSchedule$intRows size=15 maxlength=10 value=\"$strSchedule\">";
      $strSchedule .= " <input type=button name=btnSchedule$intRows id=btnSchedule$intRows value='..'>";
      $strPlace = "<input type=text size=30 maxlength=50 name=detailPlace$intRows style=\"width:120px\" value=\"$strPlace\">";
      $strPIC = "<input type=text size=30 maxlength=50 name=detailPIC$intRows style=\"width:120px\" value=\"$strPIC\">";
      $strResult = getRecruitmentResultList("detailResult$intRows",$strResult,""," style=\"width:120px\"");
      $strNote = "<textarea name=detailNote$intRows style=\"width:300px\" rows=4>$strNote</textarea>";

      // ambil data surat-surat tertentu
      $strLetters = "";
      if (isset($arrLetter[$strProcessID]) && $strProcessID != "") {
        foreach ($arrLetter[$strProcessID] AS $x => $arrDb) {
          $strNo  = $arrDb['no'] ."/". $arrDb['code'] ."/". $arrDb['letter_code'] ."/". $arrDb['year_code'];
          if ($strLetters != "") $strLetters .= "<br>&nbsp;";
          $strLetters .= "<a href=\"javascript:goOpenLetter(" .$arrDb['id'].")\"";
          $strLetters .= " title=\"" .$strNo. " -- ".pgDateFormat($arrDb['letterDate'], "d M y")."\">$strNo</a>";
          $strLetters .= "&nbsp;<a href=\"recruitment_process_edit.php?btnDelete=Delete&dataCandidateID=$strDataCandidateID&dataID=$strDataID&dataLetterID=" .$arrDb['id']."\"";
          $strLetters .= " title=\"" .getWords("delete")."\" onClick=\"return confirmDelete();\">[X]</a>";
        }
      }
      if ($strLetters != "") $strLetters .= "<br>&nbsp;";
      $strLetters .= "[<a href=\"javascript:goCreateLetter($strProcessID)\">" .getWords("create letter")."</a>]";

      $strDataDetail .= " <tr valign=top><td colspan=7>&nbsp;</td></tr>\n";

      $strDataDetail .= " <tr valign=top><td colspan=7 class='inputBoxBottomLine'>&nbsp;<strong>" .strtoupper($strProcess)."</strong>";
      $strDataDetail .= "<input type=hidden name=detailID$intRows value=\"$strProcessID\">";
      $strDataDetail .= "<input type=hidden name=detailProcessName$intRows value=\"$strProcess\"></td></tr>\n"; //header process
      $strDataDetail .= "  <tr valign=top>\n";
      $strDataDetail .= "   <td>&nbsp;<strong>" .$words['schedule date']. "</strong></td>";
      $strDataDetail .= "   <td>&nbsp;:</td>";
      $strDataDetail .= "   <td>&nbsp;" .$strSchedule."</td>";
      $strDataDetail .= "   <td>&nbsp;</td>";
      $strDataDetail .= "   <td>&nbsp;<strong>" .$words['place']. "</strong></td>";
      $strDataDetail .= "   <td>&nbsp;:</td>";
      $strDataDetail .= "   <td>&nbsp;" .$strPlace."</td>";
      $strDataDetail .= "  </tr>\n";

      //$strDataDetail .= " <tr valign=top><td align=left>\n";
      $strDataDetail .= "  <tr valign=top>\n";
      $strDataDetail .= "   <td>&nbsp;<strong>" .$words['process date']. "</strong></td>";
      $strDataDetail .= "   <td>&nbsp;:</td>";
      $strDataDetail .= "   <td>&nbsp;" .$strDate."</td>";
      $strDataDetail .= "   <td>&nbsp;</td>";
      $strDataDetail .= "   <td>&nbsp;<strong>" .$words['PIC']. "</strong></td>";
      $strDataDetail .= "   <td>&nbsp;:</td>";
      $strDataDetail .= "   <td>&nbsp;" .$strPIC."</td>";
      $strDataDetail .= "  </tr>\n";

      //$strDataDetail .= " <tr valign=top><td align=left>\n";
      $strDataDetail .= "  <tr valign=top>\n";
      $strDataDetail .= "   <td nowrap>&nbsp;<strong>" .$words['result']. "</strong></td>";
      $strDataDetail .= "   <td>&nbsp;:</td>";
      $strDataDetail .= "   <td>&nbsp;" .$strResult."</td>";
      $strDataDetail .= "   <td>&nbsp;</td>";
      $strDataDetail .= "   <td>&nbsp;<strong>" .getWords('letter'). "</strong></td>";
      $strDataDetail .= "   <td>&nbsp;:</td>";
      $strDataDetail .= "   <td>&nbsp;" .$strLetters."</td>";
      $strDataDetail .= "  </tr>\n";

      $strDataDetail .= "  <tr valign=top>\n";
      $strDataDetail .= "   <td nowrap>&nbsp;<strong>" .$words['note']. "</strong></td>";
      $strDataDetail .= "   <td>&nbsp;:</td>";
      $strDataDetail .= "   <td colspan=5>&nbsp;" .$strNote."</td>";
      $strDataDetail .= "  </tr>\n";

      // tambahkan di handle calendar setup
      $strCalendarSetup .= "Calendar.setup({ inputField:\"detailDate$intRows\", button:\"btnDate$intRows\" });";
      $strCalendarSetup .= "Calendar.setup({ inputField:\"detailSchedule$intRows\", button:\"btnSchedule$intRows\" });";
    }
  }

  $strDataDetail .= " <tr valign=top><td colspan=7>&nbsp;<input type=hidden name=dataTotalProcess value='$intRows'></td></tr>\n";
*/
  // tampilkan daftar surat umum, yang pernah dibuat
  global $strInputLetter;
  if (isset($arrLetter['x'])) {
    foreach ($arrLetter['x'] AS $x => $arrDb) {
      $strNo = $arrDb['no'] . "/" . $arrDb['code'] . "/" . $arrDb['letter_code'] . "/" . $arrDb['year_code'];
      if ($strInputLetter != "") {
        $strInputLetter .= "<br>";
      }
      $strInputLetter .= "<a href=\"javascript:goOpenLetter(" . $arrDb['id'] . ")\"";
      $strInputLetter .= " title=\"" . $strNo . " -- " . pgDateFormat($arrDb['letterDate'], "d M y") . "\">$strNo</a>";
      $strInputLetter .= "&nbsp;<a href=\"recruitment_process_edit.php?btnDelete=Delete&dataCandidateID=$strDataCandidateID&dataID=$strDataID&dataLetterID=" . $arrDb['id'] . "\"";
      $strInputLetter .= " title=\"" . getWords("delete") . "\" onClick=\"return confirmDelete();\">[X]</a>";
    }
  }
  if ($strInputLetter != "") {
    $strInputLetter .= "<br>";
  }
  $strInputLetter .= "[<a href=\"javascript:goCreateLetter()\">" . getWords("create letter") . "</a>]";
  return true;
} // showData
// fungsi untuk mengambil data surat yang pernah dibuat
// hasil disimpan alam array
function getDataLetters($db, $strDataID)
{
  $arrResult = [];
  if ($strDataID == "") {
    return $arrResult;
  }
  $strSQL = "SELECT id, id_process_detail, id_recruitment_process, type,  ";
  $strSQL .= "year_code, code, no, letter_code, letter_date ";
  $strSQL .= "FROM hrd_recruitment_process_letter WHERE id_recruitment_process = '$strDataID' ";
  $strSQL .= "ORDER BY letter_date, id ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if ($rowDb['idProcessDetail'] != "") // punya detail proses
    {
      if (isset($arrResult[$rowDb['idProcessDetail']])) {
        $arrResult[$rowDb['idProcessDetail']][] = $rowDb;
      } else {
        $arrResult[$rowDb['idProcessDetail']][0] = $rowDb;
      }
    } else {
      if (isset($arrResult['x'])) {
        $arrResult['x'][] = $rowDb;
      } else {
        $arrResult['x'][0] = $rowDb;
      }
    }
  }
  return $arrResult;
}// getDataLetters
// fungsi untuk menyimpan data
function saveData($db, &$strDataID, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $error;
  global $messages;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strError = "";
  $strToday = date("Y-m-d");
  $strDataCandidateID = getPostValue('dataCandidateID');
  $strDataPosition = getPostValue('dataPosition');
  $strDataSalary = getPostValue('dataSalary', 0);
  $strDataResult = getPostValue('dataResult', 0);
  $strDataNote = getPostValue('dataNote');
  $strDataPoint = getPostValue('dataPoint', 0);
  $strDataStartDate = getPostValue('dataStartDate');
  $strDataInvitationDate = getPostValue('dataInvitationDate');
  $strDataInvitationMethod = getPostValue('dataInvitationMethod');
  // cek validasi tanggal
  $strDataInvitationDate = (validStandardDate($strDataInvitationDate)) ? $strDataInvitationDate : null;
  $strDataStartDate = (validStandardDate($strDataStartDate)) ? $strDataStartDate : null;
  $intIDRecruitmentNeed = getPostValue('id_recruitment_need');
  // cek validasi -----------------------
  if ($strDataInvitationDate == null) {
    $strError = $error['invalid_date'];
    return false;
  } else {
    /*
    ($strDataID == "") ? $strKriteria = "" : $strKriteria = "AND id <> '$strDataID' ";
    if (isDataExists("hrdCandidate","candidate_code",$strDataCode,$strKriteria)) {
      $strError = $error['duplicate_code']. "  -> $strDataCode";
      return false;
    }
    */
  }
  // simpan data -----------------------
  $tbl = new cModel("hrd_recruitment_process");
  if ($strDataID == "") {
    $data = [
        "id_candidate"        => $strDataCandidateID,
        "position"            => $strDataPosition,
        "invitation_date"     => $strDataInvitationDate,
        "invitation_method"   => $strDataInvitationMethod,
        "start_date"          => $strDataStartDate,
        "salary"              => $strDataSalary,
        "point"               => $strDataPoint,
        "result"              => $strDataResult,
        "note"                => $strDataNote,
        "id_recruitment_need" => $intIDRecruitmentNeed
    ];
    // data baru
    echo $strDataID;
    if ($tbl->insert($data)) {
      $strSQL = "SELECT id from hrd_recruitment_process order by id desc limit 1";
      $resDb = $db->execute($strSQL);
      $rowDb = $db->fetchrow($resDb);
      $strDataID = $rowDb['id'];
      writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataID ", 0);
    }
  } else {
    $data = [
        "id_candidate"        => $strDataCandidateID,
        "position"            => $strDataPosition,
        "invitation_date"     => $strDataInvitationDate,
        "invitation_method"   => $strDataInvitationMethod,
        "start_date"          => $strDataStartDate,
        "salary"              => $strDataSalary,
        "point"               => $strDataPoint,
        "result"              => $strDataResult,
        "note"                => $strDataNote,
        "id_recruitment_need" => $intIDRecruitmentNeed
    ];
    if ($tbl->update(["id" => $strDataID], $data)) {
      writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataID ", 0);
    }
  }
  // simpan data detail proses yang ada
  if ($strDataID != "") {
    $intTotal = getPostValue('dataTotalProcess', 0);
    $tblDetail = new cModel("hrd_recruitment_process_detail");
    for ($i = 1; $i <= $intTotal; $i++) {
      $strDate = getPostValue('detailDate' . $i);
      $strDate = (validStandardDate($strDate)) ? $strDate : null;
      $strSchedule = getPostValue('detailSchedule' . $i);
      $strSchedule = (validStandardDate($strSchedule)) ? $strSchedule : null;
      $data = [
          "id_recruitment_process" => $strDataID,
          "process_name"           => getPostValue('detailProcessName' . $i),
          "schedule_date"          => $strSchedule,
          "process_date"           => $strDate,
          "place"                  => getPostValue('detailPlace' . $i),
          "pic"                    => getPostValue('detailPIC' . $i),
          "result"                 => getPostValue('detailResult' . $i, 0),
          "subject"                => getPostValue('detailSubject' . $i, 0),
          "note"                   => getPostValue('detailNote' . $i)
      ];
      $strID = getPostValue('detailID' . $i);
      if ($strID == "") { // baru
        if ($data['process_name'] != "") {
          $tblDetail->insert($data);
        }
      } else {
        if ($data['process_name'] == "") {
          $tblDetail->delete(["id" => $strID], $data);
        } else {
          $tblDetail->update(["id" => $strID], $data);
        }
      }
    }
  } // end simpan detail process
  $strError = $messages['data_saved'];
  return true;
} // saveData
// fugnsi untuk memghapus delete
function deleteData($db)
{
  global $_REQUEST;
  $strID = (isset($_REQUEST['dataLetterID'])) ? $_REQUEST['dataLetterID'] : "";
  if ($strID != "") {
    $strSQL = "DELETE FROM hrd_recruitment_process_letter WHERE id = '$strID' ";
    $resExec = $db->execute($strSQL);
  }
} //deleteData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
  $strDataCandidateID = (isset($_REQUEST['dataCandidateID'])) ? $_REQUEST['dataCandidateID'] : "";
  // jika gak ada candidate ID, exit
  if ($strDataCandidateID == "") {
    header("location:candidate_search.php");
    exit();
  }
  if (isset($_POST['btnSave'])) {
    if ($bolCanEdit) {
      $bolOK = saveData($db, $strDataID, $strError);
      if ($strError != "") {
        //echo "<script>alert(\"$strError\")/script>";
        $strMessages = $strError;
        $strMsgClass = ($bolOK) ? "class=bgOK" : "class=bgError";
      }
    }
  } else if (isset($_REQUEST['btnDelete'])) {
    if ($bolCanDelete) {
      deleteData($db);
    }
  }
  if ($bolCanView) {
    getData($db, $arrData, $strDataID, $strDataCandidateID);
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  //----- TAMPILKAN DATA ---------
  $strDataCandidateName = "" . $arrData['candidate_name'];
  $strDataApplicationDate = pgDateFormat($arrData['applyDate'], "d M Y");
  $strDataPosition = "<input type=hidden name=dataPosition value=\"" . $arrData['position'] . "\">" . $arrData['position'];
  $strInputInvitationDate = "<input type=text name=dataInvitationDate id=dataInvitationDate size=15 maxlength=10 value=\"" . $arrData['invitation_date'] . "\" class=\"form-control datepicker\" data-date-format=\"yyyy-mm-dd\">";
  //$strInputInvitationDate .= " <input type=button name=btnInvitationDate id=btnInvitationDate value='..'>";
  //$strInputInvitationMethod = "<input type=text name=dataInvitationMethod size=$intDefaultWidth maxlength=50 value=\"" .$arrData['invitation_method']. "\" style=\"width:$intDefaultWidthPx\">";
  $strInputInvitationMethod = getComboFromArray(
      $arrInvitationMethod,
      "dataInvitationMethod",
      $arrData['invitation_method']
  );
  $strInputSalary = "<input type=text name=dataSalary size=$intDefaultWidth maxlength=30 value=\"" . $arrData['salary'] . "\" style=\"width:$intDefaultWidthPx\" class=numeric>";
  //$strInputPoint = "<input type=text name=dataPoint size=$intDefaultWidth maxlength=20 value=\"" .$arrData['point']. "\" style=\"width:$intDefaultWidthPx\" class=numeric>";
  $strInputNote = generateTextArea("dataNote", $arrData['note'], "cols=80 rows=3");
  $strInputStartDate = "<input type=text name=dataStartDate id=dataStartDate size=15 maxlength=10 value=\"" . $arrData['start_date'] . "\" >";
  $strInputStartDate .= " <input type=button name=btnStartDate id=btnStartDate value='..'>";
  $strInputResult = getRecruitmentResultList(
      "dataResult",
      $arrData['result'],
      "",
      " style=\"width:$intDefaultWidthPx\" onChange=\"onResultChange()\" id=\"dataResult\" "
  );
  //$strMRFNumber = getRecruitmentNeedRequestNumber($db, "id_recruitment_need", $arrData['id_recruitment_need'], "AND position='".$arrData['position']."'", " style=\"width:$intDefaultWidthPx\" ");
  $strMRFNumber = "<input type=hidden name='id_recruitment_need' id='id_recruitment_need' value=\"" . $arrData['id_recruitment_need'] . "\">" . $arrData['mrf_number'];
}
$strInitAction = " document.formInput.dataInvitationDate.focus();
    Calendar.setup({ inputField:\"dataInvitationDate\", button:\"btninvitation_date\" });
    Calendar.setup({ inputField:\"dataStartDate\", button:\"btnStartDate\" });
    [var.strCalendarSetup]
    onResultChange();
  ";
$strPageTitle = getWords("recruitment process");
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('recruitment process edit form');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = recruitmentProcessSubMenu($strWordsRecruitmentProcessList);
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
