<?php
session_start();
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
if (!isset($_SESSION['sessionUserID'])) {
  //header("location:login.php?dataPage=articleEdit.php");
  redirectPage("login.php", true);
  exit();
}
$bolCanView = true;
$strTemplateFile = getTemplate("letter.html", false);
$strAction = "recruitment_process_letter.php";
//---- INISIALISASI ----------------------------------------------------
$strCategory = "";
$strInputData = "";
$strData = "";
$intDefaultWidth = 30;
$intDefaultHeight = 3;
$strNow = date("Y-m-d");
$arrData = [
    "dataNo"      => "",
    "dataCode"    => "ST",
    "dataYear"    => date('Y'),
    "dataLetter"  => "151",
    "dataContent" => "",
    "dataID"      => "",
];
$strButtons = "";
$strIsUpdated = 0;
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db, $strDataID = "")
{
  global $words;
  global $_SESSION;
  global $arrData;
  if ($strDataID != "") {
    $strSQL = "SELECT * FROM hrd_recruitment_process_letter ";
    $strSQL .= "WHERE id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrData['dataNo'] = $rowDb['no'];
      $arrData['dataCode'] = $rowDb['code'];
      $arrData['dataLetter'] = $rowDb['letter_code'];
      $arrData['dataYear'] = $rowDb['year_code'];
      $arrData['dataContent'] = $rowDb['content'];
      $arrData['dataID'] = $rowDb['id'];
      $arrData['dataProcessID'] = $rowDb['idRecruitmentProcess'];
      $arrData['dataDetailID'] = $rowDb['idProcessDetail'];
    }
  }
  return true;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strDataID, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $arrData;
  global $strDataProcessID;
  global $strDataCandidateID;
  global $strDataDetailID;
  $strError = "";
  $bolOK = true;
  $strToday = date("Y-m-d");
  (isset($_REQUEST['dataNo'])) ? $strDataNo = $_REQUEST['dataNo'] : $strDataNo = "";
  (isset($_REQUEST['dataCode'])) ? $strDataCode = $_REQUEST['dataCode'] : $strDataCode = "";
  (isset($_REQUEST['dataYear'])) ? $strDataYear = $_REQUEST['dataYear'] : $strDataYear = "";
  (isset($_REQUEST['dataLetter'])) ? $strDataLetter = $_REQUEST['dataLetter'] : $strDataLetter = "";
  (isset($_REQUEST['dataContent'])) ? $strDataContent = $_REQUEST['dataContent'] : $strDataContent = "";
  // cek validasi -----------------------
  // simpan data -----------------------
  if ($bolOK) { // input OK, tinggal disimpan
    if ($strDataDetailID === "") {
      $strDataDetailID = "null";
    }
    // simpan data
    $strmodified_byID = $_SESSION['sessionUserID'];
    if ($strDataID == "") {
      // cek dulu, apakah data sudah yang terakhir
      $strTmp = getLastNo($db, $arrData['dataYear']);
      $intTmp = ($strTmp == "") ? 0 : (int)$strTmp;
      $intKode = (int)$strDataNo;
      if ($intKode <= $intTmp) { // sudah ada yang lebih besar atau sama, ganti
        $intKode = $intTmp + 1;
        $strDataNo = addPrevZero($intKode, 4);
      }
      // ambil ID-nya dulu
      $strDataID = $db->getNextID("hrdRecruitmentProcessLetter_id_seq");
      // data baru
      $strNow = date("Y-m-d");
      $strSQL = "INSERT INTO hrd_recruitment_process_letter (id, created, created_by,modified_by, ";
      $strSQL .= "id_recruitment_process, id_process_detail, no, code, ";
      $strSQL .= "year_code, letter_code, letter_date, \"content\") ";
      $strSQL .= "VALUES('$strDataID', now(), '$strmodified_byID','$strmodified_byID', ";
      $strSQL .= "$strDataProcessID, $strDataDetailID, '$strDataNo', '$strDataCode', ";
      $strSQL .= "'$strDataYear', '$strDataLetter', now(),'$strDataContent')  ";
      $resExec = $db->execute($strSQL);
      if ($resExec == false) {
        $bolOK = false;
        $strError = getWords("unknown_error") . " -> id= $strDataID ";
      }
    } else {
      $strSQL = "UPDATE hrd_recruitment_process_letter SET modified_by = '$strmodified_byID', ";
      $strSQL .= "code = '$strDataCode', letter_code = '$strDataLetter', ";
      $strSQL .= "year_code = '$strDataYear', ";
      $strSQL .= "no = '$strDataNo', content = '$strDataContent' ";
      $strSQL .= "WHERE id = '$strDataID' ";
      $resExec = $db->execute($strSQL);
    }
    global $strIsUpdated;
    $strIsUpdated = 1;
    if ($bolOK) {
      $strError = getWords('data_saved');
    }
  } else { // ---- data SALAH
    // gunakan data yang diisikan tadi
    $arrData['dataNo'] = $strDataNo;
    $arrData['dataContent'] = $strDataContent;
    $arrData['dataID'] = $strDataID;
  }
  return $bolOK;
} // saveData
// fungsi untuk ambil nomor terakhir (dari data)
function getLastNo($db, $strYear)
{
  $strResult = "";
  $strSQL = "SELECT MAX(no) AS nomor FROM hrd_recruitment_process_letter ";
  $strSQL .= "WHERE year_code = '$strYear' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strResult = $rowDb['nomor'] . "";
  }
  return $strResult;
}//getLastMedicalNumber
// --------- BAGIAN JAVASCRIPT -----------------------------------------
?>
  <script src="scripts/ylib.js"></script>
  <script language="javascript" type="text/javascript" src="../info/scripts/tiny_mce/tiny_mce_src.js"></script>

  <script language="JavaScript" type="text/javascript">
    // Notice: The simple theme does not use all options some of them are limited to the advanced theme
    tinyMCE.init({
      mode: "textareas",
      theme: "simple"
    });

    // fungsi untuk mencetak hasil (preview)
    function printPage() {
      no = "No : " + document.formInput.dataNo.value + "/";
      no += document.formInput.dataCode.value + "/";
      no += document.formInput.dataLetter.value + "/";
      no += document.formInput.dataYear.value + "<br>";

      wdw = window.open("", "", "statusbar=0;menubar=0");
      wdw.document.write("<html><head><title>Letter</title></head>");
      wdw.document.write("<link type='text/css' rel='stylesheet' href='bw.css'></head><body>");
      wdw.document.write(no);
      wdw.document.write(document.formInput.dataContent.value);
      wdw.document.write("</body></html>");
      wdw.print();
    }

    // fungsi untuk menutup current windows
    function closeWindow() {
      // refresh dulu datanya

      if (document.formInput.isChanged.value != 0) {
        bolOK = confirm("Data has changed. Do you want to close without saving?");
        if (!bolOK) return false;
      }
      if (document.formInput.isupdated.value != 0) {
        url = "http://" + window.opener.location.host + window.opener.location.pathname;
        url += "?dataID=" + document.formInput.dataProcessID.value;
        url += "&dataCandidateID=" + document.formInput.dataCandidateID.value;
        window.opener.location.href = url;
      }
      window.close();
    }

    function initPage_() {
//     document.formInput.dataNo.focus();
    } //initPage

    onload = initPage_();
  </script>

<?php
// ---------- END JAVASCRIPT -------------------------------------------
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  (isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = "";
  (isset($_REQUEST['dataProcessID'])) ? $strDataProcessID = $_REQUEST['dataProcessID'] : $strDataProcessID = "";
  (isset($_REQUEST['dataCandidateID'])) ? $strDataCandidateID = $_REQUEST['dataCandidateID'] : $strDataCandidateID = "";
  (isset($_REQUEST['dataDetailID'])) ? $strDataDetailID = $_REQUEST['dataDetailID'] : $strDataDetailID = "";
  $arrData['dataID'] = $strDataID;
  $arrData['dataProcessID'] = $strDataProcessID;
  $arrData['dataDetailID'] = $strDataDetailID;
  // beri default dulu
  if ($strDataDetailID === "") {
    $arrData['dataCode'] = "ST";
  } else {
    $arrData['dataCode'] = "PPP";
  }
  // cari dulu default no -- autonumber
  $strInputLastNo = getLastNo($db, $arrData['dataYear']);
  $intLastNo = ($strInputLastNo == "") ? 0 : (int)$strInputLastNo;
  $arrData['dataNo'] = addPrevZero($intLastNo + 1, 4);
  if ($bolCanEdit) {
    if (isset($_POST['btnSave'])) {
      $bolOK = saveData($db, $strDataID, $strError);
      if ($strError != "") {
        echo "<script>alert(\"$strError\")</script>"; // tampilkan pesan tersimpan
      }
      //$strDataID = ""; // biar tidak mengambil dta, melainkan pakai data baru atau data yang dikirim (jika error)
    }
  }
  //----- TAMPILKAN DATA ---------
  getData($db, $strDataID);
  $strDataProcessID = $arrData['dataProcessID'];
  $strDataDetailID = $arrData['dataDetailID'];
  //$strData = getFormInput($db,$strDataID, $arrData);
  $strDataNo = "<input type=text name='dataNo' id='dataNo' value=\"" . $arrData['dataNo'] . "\" size=5 maxlength=10>" . "/";
  $strDataNo .= "<input type=text name='dataCode' id='dataCode' value=\"" . $arrData['dataCode'] . "\" size=5 maxlength=10>" . "/";
  $strDataNo .= "<input type=text name='dataLetter' id='dataLetter' value=\"" . $arrData['dataLetter'] . "\" size=5 maxlength=10>" . "/";
  $strDataNo .= "<input type=text name='dataYear' id='dataYear' value=\"" . $arrData['dataYear'] . "\" size=4 maxlength=4>";
  $strDataContentFile = getTemplate("letter0.html", false);
  if ($strDataID == "") { // ambil dari template
    $strContentType = 0;
    $strNo = $arrData['dataNo'] . "/" . $arrData['dataCode'] . "/" . $arrData['dataLetter'] . "/" . $arrData['dataYear'];
    $strDate = pgDateFormat(date("Y-m-d"), "d M Y");
    if ($strDataDetailID != "") {
      $strDataContentFile = getTemplate("letter1.html", false);
    }
    // cari data kandidat, jika bukan dari proses tertentu
    //if ($arrData['dataDetailID'] == "") {
    $strSQL = "SELECT t2.candidate_name, t2.address, t2.city, t2.zip FROM hrd_recruitment_process AS t1 ";
    $strSQL .= "LEFT JOIN hrd_candidate AS t2 ON t1.id_candidate = t2.id ";
    $strSQL .= "WHERE t1.id = '$strDataProcessID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strdataName = $rowDb['candidate_name'];
      $strDataAddress = $rowDb['address'];
      if ($rowDb['city'] != "") {
        $strDataAddress .= "<br>" . $rowDb['city'];
      }
      if ($rowDb['zip'] != "") {
        $strDataAddress .= " " . $rowDb['zip'];
      }
    }
    //}
  } else { // ambil dari data
    $strContentType = 1;
    $strDataContent = $arrData['dataContent'];
  }
  $strButtons .= "<input type=submit name='btnSave' id='btnSave' value=\"" . getWords("save") . "\">&nbsp;";
  $strButtons .= "<input type=button name='btnPrint' id='btnPrint' value=\"" . getWords(
          "print"
      ) . "\" onClick=\"printPage();\">&nbsp;";
  $strButtons .= "<input type=reset name='btnReset' value=\"" . getWords('reset') . "\">&nbsp";
  $strButtons .= "<input type=button name='btnClose' id='btnClose' value=\"" . getWords(
          "close"
      ) . "\" onClick=\"closeWindow();\">&nbsp;";
}
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strTemplateFile);
$tbsPage->Show();
?>