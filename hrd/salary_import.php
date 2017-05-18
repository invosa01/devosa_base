<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
//Tambahkan class reader Excel
include_once('../includes/excelReader/excel_reader.php');
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
//---- INISIALISASI VARIABEL ----------------------------------------------------
$strWordsAttendanceData = getWords("attendance data");
$strWordsImportAttendance = getWords("import salary");
$strWordsAttendanceFile = getWords("attendance file (Excel Format)");
$strWordsImport = getWords("import");
$strWordsRESULT = getWords("result");
$strWordsImportData = getWords("import data");
$strWordsSalarySet = getWords("salary set");
$strWordsSalaryTypeAllowance = getWords("salary type of allowance");
$strWordsSalaryTypeDeduction = getWords("salary type of deduction");
$strWordsSalaryFile = getWords("salary file to import");
$strWordsDownloadTemplate = getWords("download templates format");
$strWordsSubmit = getWords("submit");
$strDownloadFiles = "<a href='../hrd/template_salary.xls'>Download</a>"; // lokasi donwnload untuk file contoh
$strParameterName = 'fileImport'; // Nama parameter yang untuk input type file
$strParameterTypeSalary = 'salaryType'; // Nama parameter untuk input select salary type
$data = ""; //new Spreadsheet_Excel_Reader($lokasiFile);  // Class excel reader
$strMessage = "";
$closeAlert = '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
$db = new CdbClass;
//-----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strTemplateFile = getTemplate("salary_import.html");
if ($db->connect()) {
  if (!$bolCanView) {
    echo "<script>alert(\"" . getWords("view_denied") . "\")</script>";
  }
  $dtAwal = getdate();
  if (isset($_POST['btnImport']) && $bolCanEdit) {
    // Get Information Of Files and Parameter
    $lokasiFile = $_FILES[$strParameterName]['tmp_name'];
    $tipeFile = $_FILES[$strParameterName]['type'];
    //-------------------------
    // Panggiil fungsi importproses
    $strMessage = importProcess($db);
  }
}
/// FORM---------------------------------------------------------------------------------------------------
$strInputFile = "<input type='file' name='" . $strParameterName . "' id='" . $strParameterName . "' size='100'> ";
$strRemark = "<b>" . getWords("select one type of salary below") . "</b> ";
$strInputTypeAllowance = getListAllowance('salaryTypeAllowance');
$strInputTypeDeduction = getListDeduction('salaryTypeDeduction');
$strInputTypeSalarySet = getListSalarySet('salarySet');
/// END FORM -----------------------------------------------------------------------------------------------
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = 'Salary Import Page';
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
// -------END MAIN PROGRAM -----------------------------------------------
//*********************************************** FUNGSI-FUNGSI UTAMA ******************************************************
//*******************************************************************************************************************************
// Fungsi untuk proses insert data kedalam database
// Untuk melakukan Import Data silahkan siapkan perintah Array dan querynya didalam fungsi ini
function importProcess($db)
{
  global $data, $lokasiFile, $tipeFile, $intNumberOfField, $closeAlert;
  $getPost = $_POST;
  $strMessage = "";
  $salaryTypeAllowance = $getPost['salaryTypeAllowance'];
  $salaryTypeDeduction = $getPost['salaryTypeDeduction'];
  $salarySet = $getPost['salarySet'];
  $arrData = [];
  $data = new Spreadsheet_Excel_Reader($lokasiFile);  // Class excel reader
  // membaca jumlah baris dari data excel yang diupload
  $baris = $data->rowcount($sheet_index = 0);
  var_dump($baris);exit;
  // variabel awal counter untuk jumlah data yang sukses dan yang gagal diimport
  // Jika dipilh keduanya Maka tolak
  if (($salaryTypeAllowance != "") AND ($salaryTypeDeduction != "")) {
    $strMessage = "<div class='alert alert-danger'>$closeAlert<i class='fa fa-times-circle'></i><h5 class='no-margin'>Please Select Only One Salary Type (Allowance or Deduction)</h5></div>";
  } else if ($salaryTypeAllowance == "" AND $salaryTypeDeduction == "") {
    $strMessage = "<div class='alert alert-danger'>$closeAlert<i class='fa fa-times-circle'></i><h5 class='no-margin'>Please Select Only One Of Salary Type (Allowance or Deduction)</h5></div>";
  } else {
    // import data excel mulai baris ke-2 (karena baris pertama adalah nama kolom
    for ($i = 2; $i <= $baris; $i++) {
      $data1 = $data->val($i, 1); // NIK Karyawan
      $data2 = $data->val($i, 2); // Amount
      $arrData[] = ["nik" => $data1, "amount" => $data2];
    }
    if ($getPost['salaryTypeAllowance'] != "") {
      //Simpan data allowance
      $strMessage = saveDataSalaryAllowance(
          $arrData,
          $salaryTypeAllowance,
          "hrd_employee_allowance",
          "allowance_code",
          $salarySet
      );
    } elseif ($getPost['salaryTypeDeduction'] != "") {
      //Simpan data deduction
      $strMessage = saveDataSalaryAllowance(
          $arrData,
          $salaryTypeDeduction,
          "hrd_employee_deduction",
          "deduction_code",
          $salarySet
      );
    }
  }
  return $strMessage;
}

//------ END FUNGSI
//******************** End Main Function ***************************************************************************************
// Funsi untuk cek apakah data yang di upload kosong dan sesuai dengan format yang benar
function validateFile($parameterName)
{
  // Get Information Of Files
  $lokasiFile = $_FILES[$parameterName]['tmp_name'];
  $tipeFile = $_FILES[$parameterName]['type'];
  $bolValidate = false;
  // jika kosong file ksoong
  if ($lokasiFile == "") {
    $bolValidate = false;
  } elseif ($tipeFile != "xls") {
    $bolValidate = false;
  } else {
    $bolValidate = true;
  }
  return $bolValidate;
}

// End FUNGSI ----------------------------------
// Fungsi membuat list allowance;
function getListAllowance($strParameter)
{
  global $db;
  $str = "";
  if ($db->connect()) {
    $strSQL = "SELECT code, name FROM hrd_allowance_type where active = true order by name;";
    $resDb = $db->execute($strSQL);
    $str .= "<select class='form-control select2' name='" . $strParameter . "'>";
    $str .= "<option value=''></option>";
    while ($r = $db->fetchrow($resDb)) {
      $str .= "<option value='" . $r['code'] . "'>" . $r['name'] . "</option>";
    }
    $str .= "</select>";
  }
  return $str;
}

// Fungsi membuat list Jenis Deduction
function getListDeduction($strParameter)
{
  global $db;
  $str = "";
  if ($db->connect()) {
    $strSQL = "SELECT code, name FROM hrd_deduction_type where active = true order by name;";
    $resDb = $db->execute($strSQL);
    $str .= "<select class='form-control select2' name='" . $strParameter . "'>";
    $str .= "<option value=''></option>";
    while ($r = $db->fetchrow($resDb)) {
      $str .= "<option value='" . $r['code'] . "'>" . $r['name'] . "</option>";
    }
    $str .= "</select>";
  }
  return $str;
}

// Fungsi untuk mendapatkan informasi id pada sistem dengan parameter nomer NIK
function getIdEmployee($strNIK)
{
  global $db;
  if ($db->connect()) {
    $strSQL = "SELECT id From hrd_employee WHERE employee_id= '" . $strNIK . "' ";
    $res = $db->execute($strSQL);
    $arrData = $db->fetchrow($res);
    $strID = $arrData['id'];
  }
  return $strID;
}

//List Hrd Basic Salary set
function getListSalarySet($strParameter)
{
  global $db;
  $str = "";
  if ($db->connect()) {
    $strSQL = "SELECT t1.id,t1.id_company, t1.start_date ,t2.company_name FROM hrd_basic_salary_set AS t1
                 LEFT JOIN hrd_company AS t2 ON t1.id_company=t2.id ";
    $resDb = $db->execute($strSQL);
    $str .= "<select class='form-control select2' name='" . $strParameter . "'>";
    while ($r = $db->fetchrow($resDb)) {
      $str .= "<option value='" . $r['id'] . "'>" . $r['start_date'] . "/" . $r['company_name'] . "</option>";
    }
    $str .= "</select>";
  }
  return $str;
}

//  Simpan data allowancc
function saveDataSalaryAllowance($arrData, $strSalaryType, $strNameTabel, $strCodeSalary, $strSalarySET)
{
  global $db;
  global $closeAlert;
  $intssucces = 0;
  $intGagal = 0;
  $strMessage = "";
  foreach ($arrData AS $key => $value) {
    $strIdEmployee = getIdEmployee($value['nik']);
    $strAmount = $value['amount'];
    if ($strIdEmployee != "") {
      $isExistSalary = isExistsalary($strIdEmployee, $strNameTabel, $strSalaryType, $strCodeSalary, $strSalarySET);
      if ($isExistSalary) {
        //Jika ada  update nilai amountnya
        $strSQL = "UPDATE $strNameTabel SET amount='$strAmount' WHERE id_employee='$strIdEmployee' AND $strCodeSalary ='$strSalaryType' AND id_salary_set='$strSalarySET';";
      } else {
        // Jika tidak ada simpan sebagai data baru
        $strSQL = "INSERT INTO $strNameTabel (id_employee,$strCodeSalary,amount,id_salary_set) Values ('" . $strIdEmployee . "','" . $strSalaryType . "','" . $strAmount . "','" . $strSalarySET . "');";
      }
      $res = $db->execute($strSQL);
      if ($res) {
        $intssucces++;
      } else {
        $intGagal++;
      }
    }
  }
  $strMessage .= "<div class='alert alert-success'>$closeAlert<i class='fa fa-check-circle'></i><h5 class='no-margin'>Data sucess Saved = " . $intssucces . " Data Failed = " . $intGagal . "</h5></div>";
  return $strMessage;
}

/// Simpan Salary Basic  allowance
function saveDataSalaryBasic()
{
}

function isExistsalary($strIdEmployee, $strNameTabel, $strSalaryType, $strCodeSalary, $strSalarySET)
{
  global $db;
  $bolExisit = true;
  $strSQL = "SELECT id  FROM $strNameTabel WHERE id_employee='" . $strIdEmployee . "' AND $strCodeSalary ='" . $strSalaryType . "' AND id_salary_set='" . $strSalarySET . "'";
  $res = $db->execute($strSQL);
  $arrData = $db->fetchrow($res);
  $strID = $arrData['id'];
  if ($strID == "") {
    $bolExisit = false;
  }
  return $bolExisit;
}

?>