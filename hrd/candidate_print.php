<?php
include_once('../global/session.php');
include_once('../global.php');
include_once('../global/common_function.php');
include_once('../includes/datagrid/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../global/date_function.php');
include_once('../global/common_data.php');
$dataPrivilege = getDataPrivileges(
    "candidate_edit.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$isCandidate = ($_SESSION['sessionGroupRole'] == ROLE_CANDIDATE);
$strPrintDate = date("d F Y");
$strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
$isNew = ($strDataID == "");
$checked = "X";
$tblCandidate = new cModel("hrd_candidate", getWords("candidate"));
$arrData = getData($strDataID);
// handle data yang isinya NULL, karena di TBS akan menjadi error
foreach ($arrData AS $key => $val) {
  if ($val == "") {
    $arrData[$key] = "";
  }
}
if ($arrData['gender'] == FEMALE) {
  $genderM = "";
  $genderF = $checked;
} else {
  $genderF = "";
  $genderM = $checked;
}
$arrAddress = explode("\n", $arrData['current_address']);
foreach ($arrAddress as $i => $address) {
  $arrData['current_address' . ($i + 1)] = $address;
}
$arrAddress = explode("\n", $arrData['permanent_address']);
foreach ($arrAddress as $i => $address) {
  $arrData['permanent_address' . ($i + 1)] = $address;
}
extract($arrData);
$candidate_name = strtoupper($candidate_name);
$driver_license_bc = $driver_license_b;
$arrDate = explode("-", $birthdate);
if (count($arrDate) == 3) {
  $birthdate_day = $arrDate[2];
  $birthdate_month = $arrDate[1];
  $birthdate_year = $arrDate[0];
} else {
  $birthdate_day = "";
  $birthdate_month = "";
  $birthdate_year = "";
}
//lihat common_variable
//$ARR_DATA_MARITAL_STATUS_CANDIDATE = array(0 => "Lajang", "Menikah", "Janda/Duda", "Cerai");
$varMaritalStatus = "marital_status" . $marital_status;
$$varMaritalStatus = $checked;
$pregnant_statusY = "";
$pregnant_statusN = "";
if ($pregnant_status == 1) {
  $pregnant_statusY = $checked;
} else if ($pregnant_status == 2) {
  $pregnant_statusN = $checked;
}
if ($pregnant_month != "" && $pregnant_month > 0) {
  $pregnant_month .= " bulan";
}
$merried_planY = "";
$merried_planN = "";
if ($merried_plan == 1) {
  $merried_planY = $checked;
} else if ($merried_plan == 0) {
  $merried_planN = $checked;
}
$readingStatus = "readingStatus" . $reading_status;
$$readingStatus = $checked;
$arrReadingTopic = explode("\n", $reading_topic);
foreach ($arrReadingTopic as $i => $topic) {
  $var = "reading_topic" . ($i + 1);
  $$var = $topic;
}
//house ownership
$varHO = "house_ownership" . intval($house_ownership);
$$varHO = $checked;
//car_ownership
$varCO = "car_ownership" . intval($car_ownership);
$$varCO = $checked;
//print susunan keluarga
$tblCandidateFamily = new cModel("hrd_candidate_family", getWords("candidate family"));
$strDataDetailFamily = getFamily($id);
$intSelisihRowCounter = 0;
$tblCandidateEducation = new cModel("hrd_candidate_education", getWords("candidate formal education"));
$strDataDetailEducation = getEducation($id);
$tblCandidateCourse = new cModel("hrd_candidate_course", getWords("candidate informal education"));
$strDataDetailCourse = getCourseTraining($id);
$tblCandidateLanguage = new cModel("hrd_candidate_language", getWords("candidate language"));
$strDataDetailLanguage = getLanguageSkill($id);
$tblCandidateSocialActivities = new cModel("hrd_candidate_social_activities", getWords("candidate social activities"));
$strDataDetailSocialActivities = getSocialActivities($id);
$strDataDetailHobbies = getHobbies();
$strDataDetailJobDescription = getJobDescription();
$tblCandidateWorkingExperience = new cModel(
    "hrd_candidate_working_experience", getWords("candidate working experience")
);
$strDataDetailWorkingExperience = getWorkingExperience($strDataID);
$tblCandidateReferencePerson = new cModel("hrd_candidate_reference_person", getWords("candidate reference"));
$strDataDetailReference = getReference($id);
$tblCandidateEmergency = new cModel("hrd_candidate_emergency", getWords("emergency contact"));
$strDataDetailEmergency = getEmergency($id);
$tblCandidateQuestion = new cModel("hrd_candidate_question", getWords("question"));
$strDataDetailQuestion = getQuestion($id);
$strPhoto = getPhoto($id);
$tbsPage = new clsTinyButStrong;
$strPageTitle = getWords("print candidate");
//$strTemplateFile = getTemplate();
//candidate user
$tbsPage->LoadTemplate('templates/candidate_print.html');
//$tbsPage->Show(TBS_NOTHING) ;
$tbsPage->Show();
//require TBS class
/*require('../includes/dompdf/dompdf_config.inc.php');

$tempHTML = &$tbsPage->Source;

//$tempHTML = '<html><head><STYLE>table { page-break-after: always }</STYLE></head><body>' . $tempHTML . '</body></html>';
if ( get_magic_quotes_gpc() ) $tempHTML = stripslashes($tempHTML);
//echo "$tempHTML";
//exit(0);
$old_limit = ini_set("memory_limit", "128M");
$dompdf = new DOMPDF();
$dompdf->load_html($tempHTML);
$dompdf->set_paper("a4", "portrait");
$dompdf->render();
$dompdf->stream("candidate_print.pdf");
*/
//--------------------------------------------------------------------------------
// untuk menampilkan foto candidate
function getPhoto($strDataID)
{
  global $arrData;
  $strW = "width='150px'";
  $strResult = "";
  //tampilkan foto
  if ($arrData['file_photo'] == "") {
    $strResult .= "<img src='../images/dummy.gif'>";
  } else {
    if (file_exists("photos/" . $arrData['file_photo'])) {
      //$strDataPhoto = "<img src='photos/" .$arrData['dataPhoto']. "'>";
      $strResult .= "<img src='photos/" . $arrData['file_photo'] . "' " . $strW . ">";
    } else {
      $strResult .= "<img src='../images/dummy.gif'>";
    }
  }
  //$strResult .= "<br
  return $strResult;
}

function getData($strDataID)
{
  global $isCandidate;
  global $tblCandidate;
  $tblRel = new cModel("hrd_religion", "religion");
  if ($strDataID != "") {
    if ($rowDb = $tblCandidate->findById($strDataID)) {
      if ($rowDb['religion_code'] != "") {
        $arrTmp = $tblRel->find(["code" => $rowDb['religion_code']], "name");
        if (isset($arrTmp['name'])) {
          $rowDb['religion_code'] = $arrTmp['name'];
        }
      }
      return $rowDb;
    }
  }
  $arrData = $tblCandidate->getEmptyRecord();
  if ($isCandidate) {
    //if candidate user, then get candidate name from session user name
    $arrData['candidate_name'] = $_SESSION['sessionUserName'];
  }
  return $arrData;
}

function getFamily($strDataID)
{
  global $tblCandidateFamily;
  $strResult = "
<table class=family border=0 cellpadding=0 cellspacing=0>
 <tr>
  <th rowspan=2>HUBUNGAN KELUARGA</th>
  <th rowspan=2>NAMA</th>
  <th rowspan=2>L/P</th>
  <th rowspan=2>TEMPAT dan TANGGAL LAHIR</th>
  <th rowspan=2>PENDIDIKAN TERAKHIR</th>
  <th colspan=2>PEKERJAAN TERAKHIR</th>
 </tr>
 <tr>
  <th>JABATAN</th>
  <th>PERUSAHAAN</th>
 </tr>";
  $tblFamily = new cModel("hrd_family", getWords("family"));
  $arrMasterFamily = $tblFamily->findAll(
      null,
      null, /*ORDER BY*/
      "is_married, id"
  );
  $arrMainFamily = [];
  $arrOwnFamily = [];
  foreach ($arrMasterFamily as $family) {
    if ($family['is_married'] == 'f') {
      $arrMainFamily[$family['id']] = $family;
    } else {
      $arrOwnFamily[$family['id']] = $family;
    }
  }
  $arrResult = [];
  if ($strDataID != "") {
    $arrTemp = $tblCandidateFamily->findAllByIdCandidate($strDataID);
    foreach ($arrTemp as $val) {
      $arrResult[$val['id_family']] = $val;
    }
  }
  $counter = 0;
  $strCalendar = "";
  foreach ($arrMainFamily as $mainFamily) {
    $counter++;
    if (isset($arrResult[$mainFamily['id']])) {
      $row = $arrResult[$mainFamily['id']];
      if ($row['id_gender'] == FEMALE) {
        $row['id_gender'] = "P";
      } else if ($row['id_gender'] == MALE) {
        $row['id_gender'] = "L";
      } else {
        $row['id_gender'] = "";
      }
      $arrBirth = [];
      if ($row['birthplace']) {
        $arrBirth[] = $row['birthplace'];
      }
      if ($row['dob']) {
        $arrBirth[] = $row['dob'];
      }
      $strBirth = implode(", ", $arrBirth);
      $strResult .= "
 <tr height=18 style='height:14.1pt'>
  <td>" . $mainFamily['name'] . "</td>
  <td>" . $row['name'] . "&nbsp;</td>
  <td align=center>&nbsp;" . $row['id_gender'] . "&nbsp;</td>
  <td>" . $strBirth . "&nbsp;</td>
  <td>" . $row['education'] . "&nbsp;</td>
  <td>" . $row['position'] . "&nbsp;</td>
  <td>" . $row['company_name'] . "&nbsp;</td>
 </tr>";
    } else {
      //jika tidak ada data family information, maka..., skip
      /*
      $strResult .= "
<tr height=18 style='height:14.1pt'>
<td>".$mainFamily['name']."</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>";
*/
    }
  }
  $strResult .= "
 <tr>
  <td colspan=7 style='border-left-style: none; border-right-style: none' class=xl1>&nbsp;</td>
 </tr>";
  foreach ($arrOwnFamily as $ownFamily) {
    $counter++;
    if (isset($arrResult[$ownFamily['id']])) {
      $row = $arrResult[$ownFamily['id']];
      if ($row['id_gender'] == FEMALE) {
        $row['id_gender'] = "P";
      } else if ($row['id_gender'] == MALE) {
        $row['id_gender'] = "L";
      } else {
        $row['id_gender'] = "";
      }
      $arrBirth = [];
      if ($row['birthplace']) {
        $arrBirth[] = $row['birthplace'];
      }
      if ($row['dob']) {
        $arrBirth[] = $row['dob'];
      }
      $strBirth = implode(", ", $arrBirth);
      $strResult .= "
 <tr height=18 style='height:14.1pt'>
  <td>" . $ownFamily['name'] . "</td>
  <td>" . $row['name'] . "&nbsp;</td>
  <td align=center>&nbsp;" . $row['id_gender'] . "&nbsp;</td>
  <td>" . $strBirth . "&nbsp;</td>
  <td>" . $row['education'] . "&nbsp;</td>
  <td>" . $row['position'] . "&nbsp;</td>
  <td>" . $row['company_name'] . "&nbsp;</td>
 </tr>";
    } else {
      //jika tidak ada data family information, maka... skil
      /*
      $strResult .= "
<tr height=18 style='height:14.1pt'>
<td>".$ownFamily['name']."</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>";
*/
    }
  }
  $strResult .= "</table>";
  return $strResult;
}

function getLanguageSkill($strDataID)
{
  global $tblCandidateLanguage;
  global $intSelisihRowCounter;
  $strResult = "
<table class=language border=0 cellpadding=0 cellspacing=0>
 <tr>
  <th style='text-align: left'>MACAM BAHASA</th>
  <th colspan=5>MENDENGAR</th>
  <th colspan=5>MEMBACA</th>
  <th colspan=5>BERBICARA</th>
  <th colspan=5>MENULIS</th>
 </tr>";
  $arrLanguage = getDataListCandidateLanguage(null, false);
  $counter = 0;
  if ($strDataID != "") {
    $arrResult = $tblCandidateLanguage->findAllByIdCandidate($strDataID, "", "id");
    $arrTempLanguage = [];
    foreach ($arrLanguage as $row) {
      $arrTempLanguage[] = $row['value'];
    }
    if (is_array($arrResult) && count($arrResult) > 0) {
      $arrDefault = [1 => "1", "2", "3", "4", "5"];
      foreach ($arrResult as $row) {
        $counter++;
        $arrInfo = [ // menyimpan info text untuk keterangan
                     "L" => $arrDefault,
                     "R" => $arrDefault,
                     "S" => $arrDefault,
                     "W" => $arrDefault
        ];
        for ($i = 1; $i <= 5; $i++) {
          //initialize css listening
          $strClassL = "strClassL" . $i;
          if ($i == 5) {
            $$strClassL = "class=right";
          } else {
            $$strClassL = "";
          }
          $strClassR = "strClassR" . $i;
          if ($i == 5) {
            $$strClassR = "class=right";
          } else {
            $$strClassR = "";
          }
          $strClassS = "strClassS" . $i;
          if ($i == 5) {
            $$strClassS = "class=right";
          } else {
            $$strClassS = "";
          }
          $strClassW = "strClassW" . $i;
          if ($i == 5) {
            $$strClassW = "class=right";
          } else {
            $$strClassW = "";
          }
        }
        if ($row['listening_skill'] != "") {
          $strClass = "strClassL" . $row['listening_skill'];
          if ($row['listening_skill'] == 5) {
            $$strClass = "class='circle right'";
          } else {
            $$strClass = "class=circle";
          }
          if (isset($arrInfo['L'][$row['listening_skill']])) {
            $arrInfo['L'][$row['listening_skill']] = "(" . $row['listening_skill'] . ")";
          }
        }
        if ($row['reading_skill'] != "") {
          $strClass = "strClassR" . $row['reading_skill'];
          if ($row['reading_skill'] == 5) {
            $$strClass = "class='circle right'";
          } else {
            $$strClass = "class=circle";
          }
          if (isset($arrInfo['R'][$row['reading_skill']])) {
            $arrInfo['R'][$row['reading_skill']] = "(" . $row['reading_skill'] . ")";
          }
        }
        if ($row['speaking_skill'] != "") {
          $strClass = "strClassS" . $row['speaking_skill'];
          if ($row['speaking_skill'] == 5) {
            $$strClass = "class='circle right'";
          } else {
            $$strClass = "class=circle";
          }
          if (isset($arrInfo['S'][$row['speaking_skill']])) {
            $arrInfo['S'][$row['speaking_skill']] = "(" . $row['speaking_skill'] . ")";
          }
        }
        if ($row['writing_skill'] != "") {
          $strClass = "strClassW" . $row['writing_skill'];
          if ($row['writing_skill'] == 5) {
            $$strClass = "class='circle right'";
          } else {
            $$strClass = "class=circle";
          }
          if (isset($arrInfo['W'][$row['writing_skill']])) {
            $arrInfo['W'][$row['writing_skill']] = "(" . $row['writing_skill'] . ")";
          }
        }
        $strResult .= "
 <tr>
  <td class=left>" . $row['language_name'] . "&nbsp;</td>
  <td $strClassL1>" . $arrInfo['L'][1] . "</td>
  <td $strClassL2>" . $arrInfo['L'][2] . "</td>
  <td $strClassL3>" . $arrInfo['L'][3] . "</td>
  <td $strClassL4>" . $arrInfo['L'][4] . "</td>
  <td $strClassL5>" . $arrInfo['L'][5] . "</td>
  <td $strClassR1>" . $arrInfo['R'][1] . "</td>
  <td $strClassR2>" . $arrInfo['R'][2] . "</td>
  <td $strClassR3>" . $arrInfo['R'][3] . "</td>
  <td $strClassR4>" . $arrInfo['R'][4] . "</td>
  <td $strClassR5>" . $arrInfo['R'][5] . "</td>
  <td $strClassS1>" . $arrInfo['S'][1] . "</td>
  <td $strClassS2>" . $arrInfo['S'][2] . "</td>
  <td $strClassS3>" . $arrInfo['S'][3] . "</td>
  <td $strClassS4>" . $arrInfo['S'][4] . "</td>
  <td $strClassS5>" . $arrInfo['S'][5] . "</td>
  <td $strClassW1>" . $arrInfo['W'][1] . "</td>
  <td $strClassW2>" . $arrInfo['W'][2] . "</td>
  <td $strClassW3>" . $arrInfo['W'][3] . "</td>
  <td $strClassW4>" . $arrInfo['W'][4] . "</td>
  <td $strClassW5>" . $arrInfo['W'][5] . "</td>
 </tr>";
      }
    } else {
      return "";
    }
  }
  $counter = $counter - $intSelisihRowCounter;
  $intSelisihRowCounter = 5 - $counter;
  if ($intSelisihRowCounter > 0) {
    $intSelisihRowCounter = 0;
  }
  $strResult .= "</table>";
  return $strResult;
}

function getReference($strDataID)
{
  global $tblCandidateReferencePerson;
  global $arrData;
  $strResult = "
<table class=referensi border=0 cellpadding=0 cellspacing=0>
 <tr>
  <th>NAMA</th>
  <th width=160>ALAMAT</th>
  <th width=100>TELEPHONE</th>
  <th width=110>PEKERJAAN</th>
  <th width=100>HUBUNGAN</th>
 </tr>";
  $counter = 0;
  if ($strDataID != "") {
    $arrResult = $tblCandidateReferencePerson->findAllByIdCandidate($strDataID);
    if (is_array($arrResult)) {
      foreach ($arrResult as $row) {
        $counter++;
        $strResult .= "
 <tr>
  <td>" . $row['name'] . "&nbsp;</td>
  <td>" . $row['address'] . "&nbsp;</td>
  <td>" . $row['phone'] . "&nbsp;</td>
  <td>" . $row['job'] . "&nbsp;</td>
  <td>" . $row['relation'] . "&nbsp;</td>
 </tr>";
        if ($counter == 4) {
          break;
        }
      }
    }
  }
  for ($i = $counter; $i < 4; $i++) {
    $strResult .= "
 <tr>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
 </tr>";
  }
  $strResult .= "</table>";
  return $strResult;
}

function getQuestion($strDataID)
{
  global $tblCandidateQuestion;
  if ($strDataID != "") {
    $arrData = $tblCandidateQuestion->findAllByIdCandidate($strDataID, null, null, null, null, "id_question");
  }
  $arrQuestion = [
      1 => "Apakah anda pernah melamar digroup ini sebelumnya ?\nBilamana dan sebagai apa ?",
      2 => "Selain disini diperusahaan mana lagi anda melamar waktu ini ? Sebagai apa ?",
      3 => "Apakah anda terikat kontrak dengan perusahaan tempat kerja anda saat ini ?",
      4 => "Apakah anda mempunya pekerjaan sambilan atau part time ?",
      5 => "Perusahaan membutuhkan 3 surat referensi kerja sesuai dengan prosedur. Apakah anda\nberkeberatan bila kami meminta referensi pada perusahaan tempat anda bekerja ?",
      6 => "Apakah anda mempunyai teman/sanak keluarga yang bekerja digroup perusahaan ini ?",
      7 => "Apakah anda pernah menderita sakit keras/kronis/kecelakaan berat/operasi ?",
      8 => "Apakah anda pernah menjalani pemeriksaan psikologis/psikotes ?\nBilamana, dimana dan untuk tujuan apa ?",
      9 => "Apakah anda pernah berurusan dengan polisi karena tindak kejahatan ?",
      10 => "Bila diterima bersediakan anda ditugaskan keluar kota ?",
      11 => "Bila diterima bersediakan anda ditempatkan di luar kota ?",
      12 => "Macam pekerjaan/jabatan apakah yang sesuai dengan cita-cita anda ?",
      13 => "Macam pekerjaan apakah yang anda tidak sukai ?",
      14 => "Pada prinsipnya semua pekerjaan harus diselesaikan sampai dengan tuntas.\nApakah anda bersedia menyelesaikan pekerjaan sampai dengan selesai setelah jam kerja,\n masuk Sabtu, Minggu dan atau hari Libut ?",
      15 => "Apakah anda bersedia untuk menandatangani kontrak kerja ?",
      16 => "Berapa besarkah penghasilan nett anda per bulan ?\nFasilitas apakah yang anda peroleh saat ini ?",
      17 => "Bila diterima, berapa besar nett anda per bulan ?\nBila diterima, fasilitas apakah yang anda inginkan ?",
      18 => "Bila diterima, kapankan anda dapat mulai bekerja ?",
  ];
  $strResult = "";
  $checked = "&radic;";
  for ($i = 1; $i <= 2; $i++) {
    $checkedY = "&nbsp;";
    $checkedN = "&nbsp;";
    if (!isset($arrData[$i]['is_yes'])) {
      $arrData[$i]['is_yes'] = '';
    }
    if ($arrData[$i]['is_yes'] == 't') {
      $checkedY = $checked;
    } else if ($arrData[$i]['is_yes'] == 'f') {
      $checkedN = $checked;
    }
    if (!isset($arrData[$i]['answer1'])) {
      $arrData[$i]['answer1'] = "";
    }
    $arrData[$i]['answer1'] = nl2br($arrData[$i]['answer1']);
    $strResult .= "
 <tr height=16 style='height:12.0pt'>
  <td height=16 class=xl391 style='height:12.0pt'>" . $i . ".</td>
  <td class=xl311 colspan=19>" . nl2br($arrQuestion[$i]) . "</td>
  <td class=xl321>&nbsp;</td>
  <td class=xl321 colspan=2 align=center>" . $checkedY . "</td>
  <td class=xl321 colspan=2 align=center>" . $checkedN . "</td>
  <td colspan=8 class=xl261>" . $arrData[$i]['answer1'] . "</td>
  <td class=xl321>&nbsp;</td>
 </tr>
 <tr height=18>
  <td class=xl391>&nbsp;</td>
  <td class=xl311 colspan=19>&nbsp;</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td colspan=8 class=xl261></td>
  <td class=xl32>&nbsp;</td>
 </tr>";
  }
  $i = 3;
  $checkedY = "&nbsp;";
  $checkedN = "&nbsp;";
  if (!isset($arrData[$i]['is_yes'])) {
    $arrData[$i]['is_yes'] = '';
  }
  if ($arrData[$i]['is_yes'] == 't') {
    $checkedY = $checked;
  } else if ($arrData[$i]['is_yes'] == 'f') {
    $checkedN = $checked;
  }
  if (!isset($arrData[$i]['answer1'])) {
    $arrData[$i]['answer1'] = "";
  }
  $strResult .= "
 <tr height=16 style='height:12.0pt' valign=top>
  <td height=16 class=xl391 style='height:12.0pt'>" . $i . ".</td>
  <td class=xl311 colspan=19>" . nl2br($arrQuestion[$i]) . "</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2 align=center>" . $checkedY . "</td>
  <td class=xl321 colspan=2 align=center>" . $checkedN . "</td>
  <td colspan=8 class=xl261>Jika Ya, sampai dengan<br>" . $arrData[$i]['answer1'] . "</td>
  <td class=xl32>&nbsp;</td>
 </tr>
 <tr height=18>
  <td class=xl391>&nbsp;</td>
  <td class=xl311 colspan=19>&nbsp;</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td colspan=8 class=xl261></td>
  <td class=xl32>&nbsp;</td>
 </tr>";
  $i = 4;
  $checkedY = "&nbsp;";
  $checkedN = "&nbsp;";
  if (!isset($arrData[$i]['is_yes'])) {
    $arrData[$i]['is_yes'] = '';
  }
  if ($arrData[$i]['is_yes'] == 't') {
    $checkedY = $checked;
  } else if ($arrData[$i]['is_yes'] == 'f') {
    $checkedN = $checked;
  }
  if (!isset($arrData[$i]['answer1'])) {
    $arrData[$i]['answer1'] = "";
  }
  if (!isset($arrData[$i]['answer2'])) {
    $arrData[$i]['answer2'] = "";
  }
  $strResult .= "
 <tr height=16 style='height:12.0pt' valign=top>
  <td height=16 class=xl391 style='height:12.0pt'>" . $i . ".</td>
  <td class=xl311 colspan=19>" . nl2br($arrQuestion[$i]) . "</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2 align=center>" . $checkedY . "</td>
  <td class=xl321 colspan=2 align=center>" . $checkedN . "</td>
  <td colspan=8 class=xl261>Jika Ya, di Perusahaan " . $arrData[$i]['answer1'] . "<br>Sebagai " . $arrData[$i]['answer2'] . "</td>
  <td class=xl32>&nbsp;</td>
 </tr>
 <tr height=18>
  <td class=xl391>&nbsp;</td>
  <td class=xl311 colspan=19>&nbsp;</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td colspan=8 class=xl261></td>
  <td class=xl32>&nbsp;</td>
 </tr>";
  $i = 5;
  $checkedY = "&nbsp;";
  $checkedN = "&nbsp;";
  if (!isset($arrData[$i]['is_yes'])) {
    $arrData[$i]['is_yes'] = '';
  }
  if ($arrData[$i]['is_yes'] == 't') {
    $checkedY = $checked;
  } else if ($arrData[$i]['is_yes'] == 'f') {
    $checkedN = $checked;
  }
  if (!isset($arrData[$i]['answer1'])) {
    $arrData[$i]['answer1'] = "";
  }
  $arrData[$i]['answer1'] = nl2br($arrData[$i]['answer1']);
  $strResult .= "
 <tr height=16 style='height:12.0pt' valign=top>
  <td height=16 class=xl391 style='height:12.0pt'>" . $i . ".</td>
  <td class=xl311 colspan=19>" . nl2br($arrQuestion[$i]) . "</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2 align=center>" . $checkedY . "</td>
  <td class=xl321 colspan=2 align=center>" . $checkedN . "</td>
  <td colspan=8 class=xl261>" . $arrData[$i]['answer1'] . "</td>
  <td class=xl32>&nbsp;</td>
 </tr>
 <tr height=18>
  <td class=xl391>&nbsp;</td>
  <td class=xl311 colspan=19>&nbsp;</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td colspan=8 class=xl261></td>
  <td class=xl32>&nbsp;</td>
 </tr>";
  $i = 6;
  $checkedY = "&nbsp;";
  $checkedN = "&nbsp;";
  if (!isset($arrData[$i]['is_yes'])) {
    $arrData[$i]['is_yes'] = '';
  }
  if ($arrData[$i]['is_yes'] == 't') {
    $checkedY = $checked;
  } else if ($arrData[$i]['is_yes'] == 'f') {
    $checkedN = $checked;
  }
  if (!isset($arrData[$i]['answer1'])) {
    $arrData[$i]['answer1'] = "";
  }
  if (!isset($arrData[$i]['answer2'])) {
    $arrData[$i]['answer2'] = "";
  }
  $strResult .= "
 <tr height=16 style='height:12.0pt' valign=top>
  <td height=16 class=xl391 style='height:12.0pt'>" . $i . ".</td>
  <td class=xl311 colspan=19>" . nl2br($arrQuestion[$i]) . "</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2 align=center>" . $checkedY . "</td>
  <td class=xl321 colspan=2 align=center>" . $checkedN . "</td>
  <td colspan=8 class=xl261>Jika Ya, mohon disebutkan<br>Nama : " . $arrData[$i]['answer1'] . "<br>Posisi : " . $arrData[$i]['answer2'] . "</td>
  <td class=xl32>&nbsp;</td>
 </tr>
 <tr height=18>
  <td class=xl391>&nbsp;</td>
  <td class=xl311 colspan=19>&nbsp;</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td colspan=8 class=xl261></td>
  <td class=xl32>&nbsp;</td>
 </tr>";
  for ($i = 7; $i <= 8; $i++) {
    $checkedY = "&nbsp;";
    $checkedN = "&nbsp;";
    if (!isset($arrData[$i]['is_yes'])) {
      $arrData[$i]['is_yes'] = '';
    }
    if ($arrData[$i]['is_yes'] == 't') {
      $checkedY = $checked;
    } else if ($arrData[$i]['is_yes'] == 'f') {
      $checkedN = $checked;
    }
    if (!isset($arrData[$i]['answer1'])) {
      $arrData[$i]['answer1'] = "";
    }
    if (!isset($arrData[$i]['answer2'])) {
      $arrData[$i]['answer2'] = "";
    }
    $strResult .= "
 <tr height=16 style='height:12.0pt' valign=top>
  <td height=16 class=xl391 style='height:12.0pt'>" . $i . ".</td>
  <td class=xl311 colspan=19>" . nl2br($arrQuestion[$i]) . "</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2 align=center>" . $checkedY . "</td>
  <td class=xl321 colspan=2 align=center>" . $checkedN . "</td>
  <td colspan=8 class=xl261>Jika Ya, mohon disebutkan<br>
    <div style='float: left'>1. " . $arrData[$i]['answer1'] . "<br>2. " . $arrData[$i]['answer3'] . "</div>
    <div style='float: left'>&nbsp;Thn : " . $arrData[$i]['answer2'] . "<br>&nbsp;Thn : " . $arrData[$i]['answer4'] . "</div>
  </td>
  <td class=xl32>&nbsp;</td>
 </tr>
 <tr height=18>
  <td class=xl391>&nbsp;</td>
  <td class=xl311 colspan=19>&nbsp;</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td colspan=8 class=xl261></td>
  <td class=xl32>&nbsp;</td>
 </tr>";
  }
  for ($i = 9; $i <= 15; $i++) {
    $checkedY = "&nbsp;";
    $checkedN = "&nbsp;";
    if (!isset($arrData[$i]['is_yes'])) {
      $arrData[$i]['is_yes'] = '';
    }
    if ($arrData[$i]['is_yes'] == 't') {
      $checkedY = $checked;
    } else if ($arrData[$i]['is_yes'] == 'f') {
      $checkedN = $checked;
    }
    if (!isset($arrData[$i]['answer1'])) {
      $arrData[$i]['answer1'] = "";
    }
    $arrData[$i]['answer1'] = nl2br($arrData[$i]['answer1']);
    $strResult .= "
 <tr height=16 style='height:12.0pt' valign=top>
  <td height=16 class=xl391 style='height:12.0pt'>" . $i . ".</td>
  <td class=xl311 colspan=19>" . nl2br($arrQuestion[$i]) . "</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2 align=center>" . $checkedY . "</td>
  <td class=xl321 colspan=2 align=center>" . $checkedN . "</td>
  <td colspan=8 class=xl261>" . $arrData[$i]['answer1'] . "</td>
  <td class=xl32>&nbsp;</td>
 </tr>
 <tr height=18>
  <td class=xl391>&nbsp;</td>
  <td class=xl311 colspan=19>&nbsp;</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td colspan=8 class=xl261></td>
  <td class=xl32>&nbsp;</td>
 </tr>";
  }
  for ($i = 16; $i <= 17; $i++) {
    $checkedY = "&nbsp;";
    $checkedN = "&nbsp;";
    if (!isset($arrData[$i]['is_yes'])) {
      $arrData[$i]['is_yes'] = '';
    }
    if ($arrData[$i]['is_yes'] == 't') {
      $checkedY = $checked;
    } else if ($arrData[$i]['is_yes'] == 'f') {
      $checkedN = $checked;
    }
    if (!isset($arrData[$i]['answer1'])) {
      $arrData[$i]['answer1'] = "";
    }
    if (is_numeric($arrData[$i]['answer1'])) {
      $arrData[$i]['answer1'] = number_format($arrData[$i]['answer1']);
    }
    if (!isset($arrData[$i]['answer2'])) {
      $arrData[$i]['answer2'] = "";
    }
    $strResult .= "
 <tr height=16 style='height:12.0pt' valign=top>
  <td height=16 class=xl391 style='height:12.0pt'>" . $i . ".</td>
  <td class=xl311 colspan=19>" . nl2br($arrQuestion[$i]) . "</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2 align=center>" . $checkedY . "</td>
  <td class=xl321 colspan=2 align=center>" . $checkedN . "</td>
  <td colspan=8 class=xl261>Penghasilan nett : Rp. " . $arrData[$i]['answer1'] . "<br>Fasilitas : " . $arrData[$i]['answer2'] . "</td>
  <td class=xl32>&nbsp;</td>
 </tr>
 <tr height=18>
  <td class=xl391>&nbsp;</td>
  <td class=xl311 colspan=19>&nbsp;</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td colspan=8 class=xl261></td>
  <td class=xl32>&nbsp;</td>
 </tr>";
  }
  $i = 18;
  $checkedY = "&nbsp;";
  $checkedN = "&nbsp;";
  if (!isset($arrData[$i]['is_yes'])) {
    $arrData[$i]['is_yes'] = '';
  }
  if ($arrData[$i]['is_yes'] == 't') {
    $checkedY = $checked;
  } else if ($arrData[$i]['is_yes'] == 'f') {
    $checkedN = $checked;
  }
  if (!isset($arrData[$i]['answer1'])) {
    $arrData[$i]['answer1'] = "";
  }
  $strResult .= "
 <tr height=16 style='height:12.0pt' valign=top>
  <td height=16 class=xl391 style='height:12.0pt'>" . $i . ".</td>
  <td class=xl311 colspan=19>" . nl2br($arrQuestion[$i]) . "</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2 align=center>" . $checkedY . "</td>
  <td class=xl321 colspan=2 align=center>" . $checkedN . "</td>
  <td colspan=8 class=xl261>Tgl. " . $arrData[$i]['answer1'] . "</td>
  <td class=xl32>&nbsp;</td>
 </tr>
 <tr height=18>
  <td class=xl391>&nbsp;</td>
  <td class=xl311 colspan=19>&nbsp;</td>
  <td class=xl32>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td class=xl321 colspan=2>&nbsp;</td>
  <td colspan=8 class=xl261></td>
  <td class=xl32>&nbsp;</td>
 </tr>";
  return $strResult;
}

function getSocialActivities($strDataID)
{
  global $tblCandidateSocialActivities;
  global $intSelisihRowCounter;
  $strResult = "
<table class=social border=0 cellpadding=0 cellspacing=0>
 <tr>
  <th>ORGANISASI</th>
  <th>MACAM KEGIATAN</th>
  <th>JABATAN</th>
  <th width=60>TAHUN</th>
 </tr>";
  $counter = 0;
  if ($strDataID != "") {
    $arrResult = $tblCandidateSocialActivities->findAllByIdCandidate($strDataID);
    if (is_array($arrResult)) {
      foreach ($arrResult as $row) {
        $counter++;
        $strResult .= "
 <tr>
  <td>" . $row['organization'] . "&nbsp;</td>
  <td>" . $row['type_organization'] . "&nbsp;</td>
  <td>" . $row['last_position'] . "&nbsp;</td>
  <td align=center>" . $row['year_from'] . "&nbsp;</td>
 </tr>";
      }
    }
  }
  $counter = $counter - $intSelisihRowCounter;
  $intSelisihRowCounter = 5 - $counter;
  if ($intSelisihRowCounter > 0) {
    $intSelisihRowCounter = 0;
  }
  if ($counter < 5) {
    for ($i = $counter; $i < 5; $i++) {
      $strResult .= "
 <tr>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
 </tr>";
    }
  }
  $strResult .= "</table>";
  return $strResult;
}

function getHobbies()
{
  global $arrData;
  global $intSelisihRowCounter;
  $strResult = "
 <tr height=18 style='height:13.5pt'>
  <td height=18 colspan=34 class=xl26 style='height:13.5pt'></td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 colspan=33 class=xl41 colspan=11 style='height:13.5pt'>HOBBY DAN KEGIATAN DI WAKTU LUANG :</td>
  <td class=xl30>&nbsp;</td>
 </tr>";
  $arrHobbies = explode("\n", $arrData['hobbies']);
  $counter = 0;
  foreach ($arrHobbies as $hobby) {
    $counter++;
    $hobby = substr($hobby, 0, 130);
    $strResult .= "
   <tr height=18 style='height:13.5pt'>
    <td height=18 colspan=33 class=xl25 style='height:13.5pt'>&nbsp;" . $hobby . "</td>
    <td class=xl30>&nbsp;</td>
   </tr>";
  }
  $counter = $counter - $intSelisihRowCounter;
  $intSelisihRowCounter = 5 - $counter;
  if ($intSelisihRowCounter > 0) {
    $intSelisihRowCounter = 0;
  }
  if ($counter < 5) {
    for ($i = $counter; $i < 5; $i++) {
      $strResult .= "
   <tr height=18 style='height:13.5pt'>
    <td height=18 colspan=33 class=xl25 style='height:13.5pt'>&nbsp;</td>
    <td class=xl30>&nbsp;</td>
   </tr>";
    }
  }
  return $strResult;
}

function getJobDescription()
{
  global $arrData;
  $strResult = "";
  $arrJobDesc = explode("\n", $arrData['job_description']);
  $counter = 0;
  foreach ($arrJobDesc as $job) {
    $counter++;
    $job = substr($job, 0, 130);
    $strResult .= "
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl25 colspan=33 style='height:13.5pt'>&nbsp;" . $job . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>";
  }
  if ($counter < 21) {
    for ($i = $counter; $i < 21; $i++) {
      $strResult .= "
   <tr height=18 style='height:13.5pt'>
    <td height=18 colspan=33 class=xl25 style='height:13.5pt'>&nbsp;</td>
    <td class=xl30>&nbsp;</td>
   </tr>";
    }
  }
  return $strResult;
}

function getEmergency($strDataID)
{
  global $tblCandidateEmergency;
  global $arrData;
  $strResult = "
<table class=referensi border=0 cellpadding=0 cellspacing=0>
 <tr>
  <th>NAMA</th>
  <th width=160>ALAMAT</th>
  <th width=100>TELEPHONE</th>
  <th width=110>PEKERJAAN</th>
  <th width=100>HUBUNGAN</th>
 </tr>";
  $counter = 0;
  if ($strDataID != "") {
    $arrResult = $tblCandidateEmergency->findAllByIdCandidate($strDataID);
    if (is_array($arrResult)) {
      foreach ($arrResult as $row) {
        $counter++;
        $strResult .= "
 <tr>
  <td>" . $row['name'] . "&nbsp;</td>
  <td>" . $row['address'] . "&nbsp;</td>
  <td>" . $row['phone'] . "&nbsp;</td>
  <td>" . $row['job'] . "&nbsp;</td>
  <td>" . $row['relation'] . "&nbsp;</td>
 </tr>";
        if ($counter == 4) {
          break;
        }
      }
    }
  }
  for ($i = $counter; $i < 4; $i++) {
    $strResult .= "
 <tr>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
 </tr>";
  }
  $strResult .= "</table>";
  return $strResult;
}

function getEducation($strDataID)
{
  global $tblCandidateEducation;
  global $intSelisihRowCounter;
  $tblEdu = new cModel("hrd_education_level", "education");
  //print header
  $strResult = "
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl40 colspan=12 style='height:13.5pt'>RIWAYAT PENDIDIKAN FORMAL / INFORMAL</td>
  <td colspan=22 class=xl40></td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td colspan=6 rowspan=2 height=36 class=xl43 style='border-right:.5pt solid black; border-bottom:.5pt solid black;height:27.0pt'>TINGKAT</td>
  <td colspan=8 rowspan=2 class=xl43 style='border-right:.5pt solid black; border-bottom:.5pt solid black'>NAMA SEKOLAH</td>
  <td colspan=5 rowspan=2 class=xl43 style='border-right:.5pt solid black; border-bottom:.5pt solid black'>TEMPAT / KOTA</td>
  <td colspan=7 rowspan=2 class=xl43 style='border-right:.5pt solid black; border-bottom:.5pt solid black'>JURUSAN</td>
  <td colspan=4 class=xl49 style='border-right:.5pt solid black;'>TAHUN</td>
  <td colspan=4 rowspan=2 class=xl43 style='border-right:.5pt solid black; border-bottom:.5pt solid black'>LULUS / TIDAK</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td colspan=2 height=18 class=xl49 style='border-right:.5pt solid black; height:13.5pt;border-left:none'>DARI</td>
  <td colspan=2 class=xl49 style='border-right:.5pt solid black;'>SAMPAI</td>
 </tr>
    ";
  $counter = 0;
  $emptyValue = ["value" => "", "text" => ""];
  $arrAcademicList = getDataListAcademic(null, true);
  if ($strDataID != "") {
    $arrResult = $tblCandidateEducation->findAllByIdCandidate($strDataID, null, "id");
    if (is_array($arrResult) && count($arrResult) > 0) {
      foreach ($arrResult as $row) {
        $counter++;
        if ($row['is_passed'] == 't') {
          $row['is_passed'] = 'LULUS';
        }
        if ($row['is_passed'] == 'f') {
          $row['is_passed'] = 'TIDAK LULUS';
        }
        if ($row['academic'] != "") {
          $arrTmp = $tblEdu->find(["code" => $row['academic']], "name");
          if (isset($arrTmp['name'])) {
            $row['academic'] = $arrTmp['name'];
          }
        }
        $strResult .= "
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl25 colspan=5 style='height:13.5pt;'>&nbsp;" . $row['academic'] . "</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 colspan=7>&nbsp;" . $row['school'] . "</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 colspan=4>&nbsp;" . $row['place'] . "</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 colspan=6>&nbsp;" . $row['major'] . "</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 align=right>" . $row['year_from'] . "&nbsp;</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 align=right>" . $row['year_to'] . "&nbsp;</td>
  <td class=xl30 >&nbsp;</td>
  <td class=xl29 colspan=3 >&nbsp;" . $row['is_passed'] . "</td>
  <td class=xl30 >&nbsp;</td>
 </tr>";
      }
    } else {
      return "";
    }
  }
  $intSelisihRowCounter = 8 - $counter;
  if ($intSelisihRowCounter > 0) {
    $intSelisihRowCounter = 0;
  }
  /*
  if ($counter < 8)
  {
    for($i = $counter; $i < 8; $i++)
    {
        $strResult .= "
<tr height=18 style='height:13.5pt'>
<td height=18 class=xl25 colspan=5 style='height:13.5pt;'>&nbsp;</td>
<td class=xl30>&nbsp;</td>
<td class=xl29 colspan=7>&nbsp;</td>
<td class=xl30>&nbsp;</td>
<td class=xl29 colspan=4>&nbsp;</td>
<td class=xl30>&nbsp;</td>
<td class=xl29 colspan=6>&nbsp;</td>
<td class=xl30>&nbsp;</td>
<td class=xl29 align=right>&nbsp;</td>
<td class=xl30>&nbsp;</td>
<td class=xl29 align=right>&nbsp;</td>
<td class=xl30 >&nbsp;</td>
<td class=xl29 colspan=3 >&nbsp;</td>
<td class=xl30 >&nbsp;</td>
</tr>";
    }
  }
  */
  return $strResult;
}

function getCourseTraining($strDataID)
{
  global $tblCandidateCourse;
  global $intSelisihRowCounter;
  $strResult = "
 <tr height=18 style='height:13.5pt'>
  <td height=18 colspan=34 class=xl26 style='height:13.5pt'></td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl40 colspan=6 style='height:13.5pt'>KURSUS / TRAINING</td>
  <td colspan=28 class=xl40></td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td colspan=6 height=18 class=xl52 style='border-right:.5pt solid black; height:13.5pt'>BIDANG / JENIS</td>
  <td colspan=8 class=xl52 style='border-right:.5pt solid black;'>PENYELENGGARA</td>
  <td colspan=5 class=xl52 style='border-right:.5pt solid black;'>TEMPAT / KOTA</td>
  <td colspan=7 class=xl52 style='border-right:.5pt solid black;'>LAMA KURSUS</td>
  <td colspan=3 class=xl52 style='border-right:.5pt solid black;'>TAHUN</td>
  <td colspan=5 class=xl52 style='border-right:.5pt solid black;'>DIBIAYAI OLEH</td>
 </tr>";
  $counter = 0;
  $emptyValue = ["value" => "", "text" => ""];
  if ($strDataID != "") {
    $arrResult = $tblCandidateCourse->findAllByIdCandidate($strDataID);
    if (is_array($arrResult) && count($arrResult) > 0) {
      foreach ($arrResult as $row) {
        $counter++;
        $strResult .= "
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl25 colspan=5 style='height:13.5pt'>&nbsp;" . $row['course_type'] . "</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 colspan=7>&nbsp;" . $row['institution'] . "</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 colspan=4>&nbsp;" . $row['place'] . "</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 colspan=6>&nbsp;" . $row['duration'] . "</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 colspan=2 align=right>&nbsp;" . $row['start_year'] . "</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 colspan=4>&nbsp;" . $row['funded_by'] . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>";
      }
    } else {
      return "";
    }
  }
  $counter = $counter - $intSelisihRowCounter;
  $intSelisihRowCounter = 5 - $counter;
  if ($intSelisihRowCounter > 0) {
    $intSelisihRowCounter = 0;
  }
  if ($counter < 5) {
    for ($i = $counter; $i < 5; $i++) {
      $strResult .= "
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl25 colspan=5 style='height:13.5pt'>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 colspan=7>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 colspan=4>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 colspan=6>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 colspan=2 align=right>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
  <td class=xl29 colspan=4>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>";
    }
  }
  return $strResult;
}

function getWorkingExperience($strDataID)
{
  global $tblCandidateWorkingExperience;
  $strResult = "";
  $counter = 0;
  if ($strDataID != "") {
    $arrResult = $tblCandidateWorkingExperience->findAllByIdCandidate($strDataID, null, "id");
    if (is_array($arrResult)) {
      foreach ($arrResult as $row) {
        $counter++;
        $tabIndex = $counter * 18 + 1;
        if ($row['reference_position'] != "") {
          $row['reference_position'] = "( " . $row['reference_position'] . " )";
        }
        $strResult .= "
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl42 style='height:13.5pt'>" . chr(ord('A') + $counter - 1) . ".</td>
  <td class=xl25 colspan=9>NAMA PERUSAHAAN</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=10>&nbsp;" . $row['company_name'] . "</td>
  <td class=xl29>&nbsp;</td>
  <td class=xl29 colspan=3>PHONE :</td>
  <td class=xl29 colspan=8>&nbsp;" . $row['company_phone'] . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>ALAMAT</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=22>&nbsp;" . $row['company_address'] . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>JUMLAH KERYAWAN</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=22>&nbsp;" . $row['number_of_employees'] . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>JABATAN PADA SAAT BEKERJA</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=22>&nbsp;" . $row['position_start'] . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>DIREKTUR</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=22>&nbsp;" . $row['director_name'] . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>NAMA ATASAN</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=22>&nbsp;" . $row['superior_name'] . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>MULAI BEKERJA</td>
  <td class=xl29 colspan=2>TGL :</td>
  <td class=xl29 colspan=3>&nbsp;" . $row['start_day'] . "</td>
  <td class=xl29 colspan=2>BLN :</td>
  <td class=xl29 colspan=4>&nbsp;" . $row['start_month'] . "</td>
  <td class=xl29 colspan=2>THN :</td>
  <td class=xl29 colspan=10>&nbsp;" . $row['start_year'] . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>BERHENTI BEKERJA</td>
  <td class=xl29 colspan=2>TGL :</td>
  <td class=xl29 colspan=3>&nbsp;" . $row['end_day'] . "</td>
  <td class=xl29 colspan=2>BLN :</td>
  <td class=xl29 colspan=4>&nbsp;" . $row['end_month'] . "</td>
  <td class=xl29 colspan=2>THN :</td>
  <td class=xl29 colspan=10>&nbsp;" . $row['end_year'] . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>PENGHASILAN NETT PER BULAN</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=22>&nbsp;Rp. " . number_format($row['last_salary']) . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>NAMA REFERENSI ( JABATAN )</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=10>&nbsp;" . $row['reference_name'] . " " . $row['reference_position'] . "</td>
  <td class=xl29>&nbsp;</td>
  <td class=xl29 colspan=3>PHONE :</td>
  <td class=xl29 colspan=8>" . $row['reference_phone'] . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>ALASAN BERHENTI :</td>
  <td class=xl29>1</td>
  <td class=xl29 colspan=22>&nbsp;" . $row['reason_for_leaving1'] . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>&nbsp;</td>
  <td class=xl29>2</td>
  <td class=xl29 colspan=22>&nbsp;" . $row['reason_for_leaving2'] . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl33 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>&nbsp;</td>
  <td class=xl29>3</td>
  <td class=xl29 colspan=22>&nbsp;" . $row['reason_for_leaving3'] . "</td>
  <td class=xl30>&nbsp;</td>
 </tr>
  <td class='xl1' colspan=34>&nbsp;</td>
 </tr>";
        //3 only
        //if ($counter == 3) break;
      }
    }
  }
  if ($counter < 3) {
    for ($i = $counter; $i < 3; $i++) {
      $strResult .= "
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl42 style='height:13.5pt'>" . chr(ord('A') + $i) . ".</td>
  <td class=xl25 colspan=9>NAMA PERUSAHAAN</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=10>&nbsp;</td>
  <td class=xl29>&nbsp;</td>
  <td class=xl29 colspan=3>PHONE :</td>
  <td class=xl29 colspan=8>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>ALAMAT</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=22>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>JUMLAH KERYAWAN</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=22>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>JABATAN PADA SAAT BEKERJA</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=22>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>DIREKTUR</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=22>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>NAMA ATASAN</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=22>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>MULAI BEKERJA</td>
  <td class=xl29 colspan=2>TGL :</td>
  <td class=xl29 colspan=3>&nbsp;</td>
  <td class=xl29 colspan=2>BLN :</td>
  <td class=xl29 colspan=4>&nbsp;</td>
  <td class=xl29 colspan=2>THN :</td>
  <td class=xl29 colspan=10>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>BERHENTI BEKERJA</td>
  <td class=xl29 colspan=2>TGL :</td>
  <td class=xl29 colspan=3>&nbsp;</td>
  <td class=xl29 colspan=2>BLN :</td>
  <td class=xl29 colspan=4>&nbsp;</td>
  <td class=xl29 colspan=2>THN :</td>
  <td class=xl29 colspan=10>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>PENGHASILAN NETT PER BULAN</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=22>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>NAMA REFERENSI ( JABATAN )</td>
  <td class=xl29>:</td>
  <td class=xl29 colspan=10>&nbsp;</td>
  <td class=xl29>&nbsp;</td>
  <td class=xl29 colspan=3>PHONE :</td>
  <td class=xl29 colspan=8>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>ALASAN BERHENTI :</td>
  <td class=xl29>1</td>
  <td class=xl29 colspan=22>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl31 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>&nbsp;</td>
  <td class=xl29>2</td>
  <td class=xl29 colspan=22>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 <tr height=18 style='height:13.5pt'>
  <td height=18 class=xl33 style='height:13.5pt'>&nbsp;</td>
  <td class=xl25 colspan=9>&nbsp;</td>
  <td class=xl29>3</td>
  <td class=xl29 colspan=22>&nbsp;</td>
  <td class=xl30>&nbsp;</td>
 </tr>
 </tr>
  <td class='xl1' colspan=34>&nbsp;</td>
 </tr>";
    }
  }
  return $strResult;
}

?>