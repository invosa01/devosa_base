<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/excelReader/reader.php');
include_once('../global/employeeFunc.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
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
$strUserName = $_SESSION['sessionUserName'];
$strMainMenu = getMainMenu();
$strSubMenu = getSubMenu();
//---- INISIALISASI ----------------------------------------------------
$strWordsImportMedicalData = getWords("import medical data");
$strWordsExcelFile = getWords("excel file");
$strWordsImport = getWords("import");
$strWordsFORMATDATAINPUT = getWords("format data input");
$strWordsEMPLID = getWords("empl.id");
$strWordsEMPLNAME = getWords("empl.name");
$strWordsRELATIONSTATUS = getWords("relation/status");
$strWordsKODETINDAKAN = getWords("kode tindakan");
$strWordsTREATMENTTYPE = getWords("treatment type");
$strWordsTREATMANTDATE = getWords("treatment date");
$strWordsCOST = getWords("cost");
$strWordsAPPROVEDCOST = getWords("approved cost");
$strWordsFORMNO = getWords("form no.");
$strWordsTYPE = getWords("type");
$strWordsCLAIMDATE = getWords("claim date");
$strWordsID = getWords("id");
$strWordsRESULTFORM = getWords("result #form");
$strWordsRESULTDETAIL = getWords("result #detail");
$strWordsUNKNOWNID = getWords("unknown id");
$strDataDetail = "";
$strHidden = "";
$strResult = "";
$intTotalData = 0;
$strTotalResultForm = ""; // jumlah form yang ditemukan
$strTotalResultDetail = ""; // jumlah detail data yang diproses
$strErrorID = ""; // daftar ID employee yang gak ketemu
$strResultStyle = "style = \"display:none\" ";
//----------------------------------------------------------------------
//--- DAFTAR FUNGSI ------------------------------------------------------
// menampilkan pesan ke HTML
function showMessage($strMsg)
{
  echo $strMsg . "<br>";
}

// proses import data -- main-main aja
function importData($db, &$intTotal)
{
  global $_FILES;
  global $_REQUEST;
  global $_SESSION;
  global $strResult;
  global $messages;
  global $strTotalResultDetail;
  global $strTotalResultForm;
  global $strErrorID;
  global $strResultStyle;
  $strError = "";
  $strUpdaterID = $_SESSION['sessionUserID'];
  $strFormCode = "PBP";
  $intExcelDate2000 = 36526; // nilai integer dari tanggal 01-01-2000,
  // untuk konversi tanggal dari excel (integer)
  $intTotal = 0;
  $strTotalResultForm = 0;
  $strTotalResultDetail = 0;
  if (is_uploaded_file($_FILES["fileData"]['tmp_name'])) {
    //-- baca file Excel
    $data = new Spreadsheet_Excel_Reader();
    $data->setOutputEncoding('CP1251');
    $data->read($_FILES["fileData"]['tmp_name']);
    //--- MULAI BACA DATA DARI FILE EXCEL -------
    $intCols = 56; // default ada segini
    $intRows = $data->sheets[0]['numRows']; // total baris
    $ok = 0;
    $arrEmp = []; // menampung daftar karyawan yagn sudah ada
    if ($intRows > 1) {
      // baca dulu daftar karyawan
      $strSQL = "SELECT id, employee_id FROM hrd_employee WHERE flag = 0 ";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) {
        $arrEmp[$rowDb['employee_id']] = $rowDb['id'];
      }
    }
    // array untuk daftar status keluarga
    $arrStatus = [
        "anak"      => [4, 1], // kode status, gender
        "anaka"     => [4, 1],
        "istri"     => [2, 0],
        "iatri"     => [2, 0],
        "karyawan"  => [-1, 1],
        "karyawati" => [-1, 0],
        "kryawan"   => [-1, 1],
        "pegawai"   => [-1, 1],
        "pgw"       => [-1, 0],
        "suami"     => [3, 1],
        ""          => [-1, 0]
    ];
    $arrEmpUnknown = []; // mencatat daftar employee ID yang gak teradftar
    $arrForm = []; // mencata daftar form yang sudah ada, indexnya adalah form number
    for ($i = 2; $i <= $intRows; $i++) {
      // tampung di variabel, biar pendek codingnya :D
      $arrData = (isset($data->sheets[0]['cells'][$i])) ? $data->sheets[0]['cells'][$i] : [];
      // baca data satu persatu
      $strEmployeeID = (isset($arrData[1])) ? trim($arrData[1]) : "";
      $strName = (isset($arrData[2])) ? addslashes(trim($arrData[2])) : "";
      $strStatus = (isset($arrData[3])) ? strtolower(trim($arrData[3])) : "";
      $strCode = (isset($arrData[4])) ? strtolower(trim($arrData[4])) : "";
      $strTreatment = (isset($arrData[5])) ? addslashes(trim($arrData[5])) : "";
      $strTreatmentDate = (isset($arrData[6])) ? trim($arrData[6]) : 0;
      $strCost = (isset($arrData[7])) ? trim($arrData[7]) : 0;
      $strApprovedCost = (isset($arrData[8])) ? trim($arrData[8]) : 0;
      if (!is_numeric($strApprovedCost)) {
        $strApprovedCost = 0;
      }
      if (!is_numeric($strCost)) {
        $strCost = 0;
      }
      $strFormNo = (isset($arrData[9])) ? trim($arrData[9]) : "";
      $strType = (isset($arrData[10])) ? strtolower(trim($arrData[10])) : "";
      $strDate = (isset($arrData[11])) ? trim($arrData[11]) : 0;
      $strID = (isset($arrData[12])) ? trim($arrData[12]) : "";
      if (isset($arrStatus[$strStatus])) {
        $intRelation = $arrStatus[$strStatus][0];
        $intGender = $arrStatus[$strStatus][1];
      } else {
        $intRelation = -1;
        $intGender = 1;
      }
      // cek jenis perawatan
      if (($tmp = strpos($strType, "jalan")) !== false) {
        $intType = 0;
      } // rawat jalan
      else if (($tmp = strpos($strType, "inap")) !== false) {
        $intType = 2;
      } else if (($tmp = strpos($strType, "frame")) !== false) {
        $intType = 1;
      } else if (($tmp = strpos($strType, "glasses")) !== false) {
        $intType = 1;
      } else if (($tmp = strpos($strType, "lens")) !== false) {
        $intType = 1;
      } else {
        $intType = 0;
      } //d efault rawat jalan
      // cari tanggal perawatan
      $strTreatmentDate = convertExcelDateToSQL($strTreatmentDate);
      // cari tanggal nota, dari ID-nya saja, 10 karakter di awal
      $strClaimDate = substr($strID, 0, 8);
      $strClaimDate = substr($strClaimDate, 4, 4) . "-" . substr($strClaimDate, 2, 2) . "-" . substr(
              $strClaimDate,
              0,
              2
          );
      // validasi dan handle data
      if ($strEmployeeID != "") {
        if (isset($arrEmp[$strEmployeeID])) {
          $strIDEmployee = $arrEmp[$strEmployeeID];
          if (isset($arrForm[$strFormNo])) { // sudah ada
            // nothing
          } else {
            // belum, simpan datanya dulu
            $arrForm[$strFormNo]['id'] = "";
            $arrForm[$strFormNo]['no'] = $strFormNo;
            $arrForm[$strFormNo]['id_employee'] = $strIDEmployee;
            $arrForm[$strFormNo]['date'] = $strClaimDate;
            $arrForm[$strFormNo]['id_employee'] = $strIDEmployee;
            // split nomor form dulu
            $arrNo = split("/", $strFormNo);
            $intTmp = count($arrNo);
            if ($intTmp > 1) {
              $arrNo[3] = (isset($arrNo[2])) ? $arrNo[2] : substr($strClaimDate, 0, 4); // tahun
              $arrNo[2] = (isset($arrNo[1])) ? $arrNo[1] : ""; // bulan
              $intTmp = strpos($arrNo[0], $strFormCode);
              if ($intTmp === false) {
                $arrNo[1] = $arrNo[0];
                $arrNo[0] = "";
              } else {
                $arrNo[1] = substr($arrNo[0], strlen($strFormCode), strlen($arrNo[0]) - strlen($strFormCode));
                $arrNo[0] = $strFormCode;
              }
            } else {
              $arrNo[0] = ""; // PBP
              $arrNo[1] = $strFormNo; // Nomor
              $arrNo[2] = ""; // bulan
              $arrNo[3] = ""; // tahun
            }
            // simpan data master claim dulu
            $strSQL = "INSERT INTO hrd_medical_claim_master (created,  modified_by, created_by, id_employee, ";
            $strSQL .= "code, no, month_code, year_code, status, payment_date) ";
            $strSQL .= "VALUES(now(), '$strUpdaterID', '$strUpdaterID', '$strIDEmployee', ";
            $strSQL .= "'$arrNo[0]', '$arrNo[1]', '$arrNo[2]', '$arrNo[3]', " . REQUEST_STATUS_APPROVED . ", '$strClaimDate') ";
            $resExec = $db->execute($strSQL);
            // cari IDnya
            $strSQL = "SELECT id FROM hrd_medical_claim_master WHERE code = '$arrNo[0]' ";
            $strSQL .= "AND no = '$arrNo[1]' AND month_code = '$arrNo[2]' AND year_code = '$arrNo[3]' ";
            $strSQL .= "AND id_employee = '$strIDEmployee' AND status >= '" . REQUEST_STATUS_APPROVED . "' ";
            $resTmp = $db->execute($strSQL);
            if ($rowTmp = $db->fetchrow($resTmp)) {
              $arrForm[$strFormNo]['id'] = $rowTmp['id'];
            }
            $strTotalResultForm++;
          }
          // simpan data detailnya
          $strSQL = "INSERT INTO hrd_medical_claim (created, modified_by, created_by, ";
          $strSQL .= "id_master, name, relation, medical_code, disease, type, ";
          $strSQL .= "medical_date, claim_date, cost, approved_cost) ";
          $strSQL .= "VALUES(now(), '$strUpdaterID', '$strUpdaterID', '" . $arrForm[$strFormNo]['id'] . "', ";
          $strSQL .= "'$strName', '$intRelation', '$strCode', '$strTreatment', '$intType', ";
          $strSQL .= "$strTreatmentDate, '$strClaimDate', '$strCost', '$strApprovedCost') ";
          $resExec = $db->execute($strSQL);
          $strTotalResultDetail++;
        } else {
          if (!in_array($strEmployeeID, $arrEmpUnknown)) {
            $arrEmpUnknown[] = $strEmployeeID;
          }
        }
      }
    }
    if ($ok > 0) {
      writeLog(ACTIVITY_IMPORT, MODULE_PAYROLL, "$strTotalResultDetail data", 0);
    }
    //$strResult = $messages['data_saved'] ." ". $ok. "/".$i;
    //$strResult .= " <br>".$strError;
    // tampilkan employee ID yang error
    $strErrorID = implode(", ", $arrEmpUnknown);
    $strResultStyle = "";
  }
  //fclose($handle);
  // tampilkan alert
  /*
  showMessage("Finish ...");
  print_r($arrEmpUnknown);
  echo "<script language='Javascript'>alert('Process Done! $strTotalResultForm | $strTotalResultDetail data updated!')</script>";
  */
} //importData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  // ------ AMBIL DATA KRITERIA -------------------------
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if (isset($_POST['btnImport'])) {
    importData($db, &$intTotalData);
  }
}
$strResult = "";
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>