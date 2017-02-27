<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
// Data Privilage followed from parent (employee_edit.php)
$dataPrivilege = getDataPrivileges(
    basename("employee_edit.php"),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView && $_POST['dataID'] == "") {
  die(getWords('view denied'));
}
$bolPrint = (isset($_REQUEST['btnPrint']));
if ($bolPrint) {
  header('Pragma: no-cache');
  header('Content-Type: application/vnd.ms-word');
  header('Content-Disposition: download; filename="employee_resume.doc"');
}
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$intDefaultWidth = 30;
$intDefaultWidthPx = 210;
$intDefaultHeight = 3;
$strInputFiles = "";
$strWordsEmployeeData = getWords("employee data");
$strWordsPrimaryInformation = getWords("primary information ");
$strWordsFacilities = getWords("facilities ");
$strWordsFamilyData = getWords("family data");
$strWordsEducationData = getWords("education data");
$strWordsTrainingData = getWords("training data");
$strWordsWorkExperiences = getWords("work experiences");
$strWordsResume = getWords("resume");
$strWordsINPUTDATA = getWords("input data");
$strWordsEmployeeID = getWords("employee ID");
$strWordsEmployeeName = getWords("employee name");
$strWordsFingerID = getWords("finger id");
$strWordsLetterCode = getWords("letter code");
$strWordsNickname = getWords("nick name");
$strWordsGender = getWords("sex");
$strWordsSalaryPaymentType = getWords("salary payment type");
$strWordsAddress = getWords("address");
$strWordsCityZip = getWords("city / zip");
$strWordsPhone = getWords("phone");
$strWordsEmail = getWords("email");
$strWordsPrivatePhone = getWords("private phone");
$strWordsPrivateEmail = getWords("private email");
$strWordsEmergencyContact = getWords("emergency contact");
$strWordsRelation = getWords("relation");
$strWordsEmergencyAddress = getWords("emergency address");
$strWordsEmergencyPhone = getWords("emergency phone");
$strWordsBirthplace = getWords("birth place");
$strWordsBirthday = getWords("birthday");
$strWordsWeight = getWords("weight");
$strWordsHeight = getWords("height");
$strWordsBloodType = getWords("blood type");
$strWordsIDCard = getWords("ID card");
$strWordsDriverLicenseA = getWords("driving license A ");
$strWordsDriverLicenseB = getWords("driving license B");
$strWordsDriverLicenseC = getWords("driving license C");
$strWordsNationality = getWords("nationality");
$strWordsPassport = getWords("passport");
$strWordsPhoto = getWords("photo");
$strWordsReligion = getWords("religion");
$strWordsEducationLevel = getWords("education level");
$strWordsFamilyStatus = getWords("family status");
$strWordsLivingCostStatus = getWords("living cost status");
$strWordsMedicalQuotaStatus = getWords("medical quota status");
$strWordsInspouse = getWords("spouse");
//$strWordsMaritalStatus = getWords ("marital status");
$strWordsWeddingDate = getWords("wedding date");
$strWordsTransport = getWords("transport");
$strWordsTransportFee = getWords("transport fee");
$strWordsEmployeeStatus = getWords("employee status");
$strWordsSubsection = getWords("subsection");
$strWordsSection = getWords("section");
$strWordsCompany = getWords("company");
$strWordsDepartment = getWords("department");
$strWordsManagement = getWords("management");
$strWordsDivision = getWords("division");
$strWordsLevel = getWords("level");
$strWordsBranch = getWords("branch");
$strWordsFunctionalPosition = getWords("functional position");
$strWordsJobGrade = getWords("job grade");
$strWordsJoinDate = getWords("join date");
$strWordsFinishDate = getWords("finish date");
$strWordsPermanentDate = getWords("permanent date");
$strWordsStatus = getWords("status");
$strWordsNote = getWords("note");
$strWordsActive = getWords("active");
$strWordsResignDate = getWords("resign date");
$strWordsBankCode = getWords("bank code");
$strWordsBankBranch = getWords("bank branch");
$strWordsBankAccountType = getWords("bank account type");
$strWordsBankAccount = getWords("bank account");
$strWordsBankAccountName = getWords("bank account name");
$strWordsBank2Code = getWords("2nd bank code");
$strWordsBank2Branch = getWords("2nd bank branch");
$strWordsBank2AccountType = getWords("2nd bank account type");
$strWordsBank2Account = getWords("2nd bank account");
$strWordsBank2AccountName = getWords("2nd bank account name");
$strWordsNPWP = strtoupper("npwp");
$strWordsJamsostekNo = getWords("jamsostek no");
$strWordsZakat = getWords("zakat");
$strWordsMembership = getWords("membership");
$arrData = [];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $strDataID = ID data, jika ingin ditampilkan
// $arrInputData = array untuk menampung data
function getData($db, &$arrData, $strDataID = "")
{
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $_SESSION;
  global $arrUserInfo;
  global $bolIsEmployee;
  $bolNewData = true;
  if ($strDataID == "") {
    //
  } else if ($bolIsEmployee && !isMe($strDataID)) {
    //
  } else {
    $strSQL = "SELECT t1.*, t2.division_name, t3.department_name, t4.section_name, t5.sub_section_name FROM hrd_employee AS t1 ";
    $strSQL .= "LEFT JOIN hrd_division AS t2 ON t1.division_code = t2.division_code ";
    $strSQL .= "LEFT JOIN hrd_department AS t3 ON t1.department_code = t3.department_code ";
    $strSQL .= "LEFT JOIN hrd_section AS t4 ON t1.section_code = t4.section_code ";
    $strSQL .= "LEFT JOIN hrd_sub_section AS t5 ON t1.sub_section_code = t5.sub_section_code ";
    $strSQL .= "WHERE t1.id = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $bolNewData = false;
      $arrData['dataName'] = $rowDb['employee_name'];
      $arrData['dataFingerID'] = $rowDb['barcode'];
      $arrData['dataLetterCode'] = $rowDb['letter_code'];
      $arrData['dataNickname'] = $rowDb['nickname'];
      $arrData['dataAddress'] = $rowDb['primary_address'];
      $arrData['dataCity'] = $rowDb['primary_city'];
      $arrData['dataZip'] = $rowDb['primary_zip'];
      $arrData['dataPhone'] = $rowDb['primary_phone'];
      $arrData['dataPrivatePhone'] = $rowDb['private_phone'];
      $arrData['dataNote'] = $rowDb['note'];
      $arrData['dataWeight'] = $rowDb['weight'];
      $arrData['dataHeight'] = $rowDb['height'];
      $arrData['dataLivingCost'] = $rowDb['living_cost_code'];
      $arrData['dataMedicalQuotaStatus'] = $rowDb['medical_quota_status'];
      $arrData['dataInspouse'] = $rowDb['inspouse'];
      $arrData['dataCompany'] = $rowDb['id_company'];
      $arrData['dataManagement'] = $rowDb['management_code'];
      $arrData['dataBranch'] = $rowDb['branch_code'];
      $arrData['dataEmail'] = $rowDb['email'];
      $arrData['dataPrivateEmail'] = $rowDb['private_email'];
      $arrData['dataBlood'] = $rowDb['blood_type'];
      $arrData['dataFunctionalPosition'] = $rowDb['functional_code'];
      $arrData['dataJamsostek'] = $rowDb['jamsostek_no'];
      $arrData['dataIDCard'] = $rowDb['id_card'];
      $arrData['dataLicenseA'] = $rowDb['driver_license_a'];
      $arrData['dataLicenseB'] = $rowDb['driver_license_b'];
      $arrData['dataLicenseC'] = $rowDb['driver_license_c'];
      $arrData['dataEmergencyContact'] = $rowDb['emergency_contact'];
      $arrData['dataEmergencyRelation'] = $rowDb['emergency_relation'];
      $arrData['dataEmergencyAddress'] = $rowDb['emergency_address'];
      $arrData['dataEmergencyPhone'] = $rowDb['emergency_phone'];
      $arrData['dataBirthplace'] = $rowDb['birthplace'];
      $arrData['dataBirthday'] = $rowDb['birthday'];
      $arrData['dataNationality'] = $rowDb['nationality'];
      $arrData['dataPassport'] = $rowDb['passport'];
      $arrData['dataGender'] = ($rowDb['gender'] == 1) ? $words['male'] : $words['female'];
      $arrData['dataEmployeeID'] = $rowDb['employee_id'];
      $arrData['dataJamsostekNo'] = $rowDb['jamsostek_no'];
      $arrData['dataBankCode'] = $rowDb['bank_code'];
      $arrData['dataBankAccountType'] = $rowDb['bank_account_type'];
      $arrData['dataBankBranch'] = $rowDb['bank_branch'];
      $arrData['dataBankAccount'] = $rowDb['bank_account'];
      $arrData['dataBankAccountName'] = $rowDb['bank_account_name'];
      $arrData['dataBank2Code'] = $rowDb['bank2_code'];
      $arrData['dataBank2AccountType'] = $rowDb['bank2_account_type'];
      $arrData['dataBank2Branch'] = $rowDb['bank2_branch'];
      $arrData['dataBank2Account'] = $rowDb['bank2_account'];
      $arrData['dataBank2AccountName'] = $rowDb['bank2_account_name'];
      $arrData['dataZakat'] = $rowDb['zakat'];
      $arrData['dataNPWP'] = $rowDb['npwp'];
      $arrData['dataPhoto'] = $rowDb['photo'];
      $arrData['dataActive'] = $rowDb['active'];
      $arrData['dataReligion'] = $rowDb['religion_code'];
      $arrData['dataEducation'] = $rowDb['education_level_code'];
      $arrData['dataFamilyStatus'] = $rowDb['family_status_code'];
      //$arrData['dataMaritalStatus'] = $rowDb['marital_status'];
      $arrData['dataWeddingdate'] = $rowDb['wedding_date'] . "";
      $arrData['dataEmployeeStatus'] = $rowDb['employee_status'];
      $arrData['dataDivision'] = $rowDb['division_code'] . " - " . $rowDb['division_name'];
      $arrData['dataDivisionCode'] = $rowDb['division_code'];
      $arrData['dataDepartment'] = $rowDb['department_code'] . " - " . $rowDb['department_name'];
      $arrData['dataDepartmentCode'] = "" . $rowDb['department_code'];
      $arrData['dataSection'] = $rowDb['section_code'] . " - " . $rowDb['section_name'];
      $arrData['dataSectionCode'] = "" . $rowDb['section_code'];
      $arrData['dataSubSection'] = $rowDb['sub_section_code'] . " - " . $rowDb['sub_section_name'];
      $arrData['dataSubSectionCode'] = "" . $rowDb['sub_section_code'];
      $arrData['dataFunction'] = $rowDb['functional_code'];
      $arrData['dataPosition'] = $rowDb['position_code'];
      $arrData['dataJoindate'] = $rowDb['join_date'];
      $arrData['dataDuedate'] = $rowDb['due_date'];
      $arrData['dataTransport'] = $rowDb['transport'];
      $arrData['dataTransportFee'] = $rowDb['transport_fee'];
      $arrData['dataPermanentdate'] = $rowDb['permanent_date'];
      $arrData['dataResigndate'] = $rowDb['resign_date'];
      $arrData['dataNote'] = $rowDb['note'];
      //$arrData['dataSalaryPeriod'] = $rowDb['salary_period'];
      $arrData['dataSalaryPaymentType'] = $rowDb['salary_payment_type'];
      //if ($_SESSION['sessionUserRole'] == 2) {
      $arrData['dataSalaryGrade'] = $rowDb['grade_code'];
      //} else {
      //  $arrData['dataSalaryGrade'] = "";
      //}
      writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "$strDataID", 0);
    }
  }
  if ($bolNewData) {
    $arrData['dataEmployeeID'] = "";
    $arrData['dataFingerID'] = "";
    $arrData['dataLetterCode'] = "";
    $arrData['dataNickname'] = "";
    $arrData['dataName'] = "";
    $arrData['dataAddress'] = "";
    $arrData['dataCity'] = "";
    $arrData['dataZip'] = "";
    $arrData['dataPhone'] = "";
    $arrData['dataPrivatePhone'] = "";
    $arrData['dataEmergencyContact'] = "";
    $arrData['dataEmergencyRelation'] = "";
    $arrData['dataEmergencyAddress'] = "";
    $arrData['dataEmergencyPhone'] = "";
    $arrData['dataEmail'] = "";
    $arrData['dataPrivateEmail'] = "";
    $arrData['dataBirthplace'] = "";
    $arrData['dataBirthday'] = date("Y-m-d");
    $arrData['dataNationality'] = "";
    $arrData['dataPassport'] = "";
    $arrData['dataGender'] = '0';
    $arrData['dataJamsostek'] = "";
    $arrData['dataBankCode'] = "";
    $arrData['dataBankBranch'] = "";
    $arrData['dataBankAccount'] = "";
    $arrData['dataBankAccountType'] = "";
    $arrData['dataBankAccountName'] = "";
    $arrData['dataBank2Code'] = "";
    $arrData['dataBank2Branch'] = "";
    $arrData['dataBank2Account'] = "";
    $arrData['dataBank2AccountType'] = "";
    $arrData['dataBank2AccountName'] = "";
    $arrData['dataNPWP'] = "";
    $arrData['dataPhoto'] = "";
    $arrData['dataNote'] = "";
    $arrData['dataWeight'] = "0";
    $arrData['dataHeight'] = "0";
    $arrData['dataBlood'] = "";
    $arrData['dataIDCard'] = "";
    $arrData['dataLicenseA'] = "";
    $arrData['dataLicenseB'] = "";
    $arrData['dataLicenseC'] = "";
    $arrData['dataActive'] = "1";
    $arrData['dataReligion'] = "";
    $arrData['dataLivingCost'] = "";
    $arrData['dataMedicalQuotaStatus'] = "";
    $arrData['dataInspouse'] = "";
    $arrData['dataCompany'] = "";
    $arrData['dataManagement'] = "";
    $arrData['dataBranch'] = "";
    $arrData['dataFunctionalPosition'] = "";
    $arrData['dataJamsostekNo'] = "";
    $arrData['dataZakat'] = "";
    $arrData['dataTransport'] = "";
    $arrData['dataTransportFee'] = "0";
    $arrData['dataEducation'] = "";
    $arrData['dataFamilyStatus'] = "";
    //$arrData['dataMaritalStatus'] = "";
    $arrData['dataWeddingdate'] = "";
    $arrData['dataEmployeeStatus'] = "";
    $arrData['dataDivision'] = "";
    $arrData['dataDepartment'] = "";
    $arrData['dataSection'] = "";
    $arrData['dataSubSection'] = "";
    $arrData['dataFunction'] = "";
    $arrData['dataPosition'] = "";
    $arrData['dataSalaryGrade'] = "";
    $arrData['dataJoindate'] = date('Y-m-d');
    $arrData['dataDuedate'] = date('Y-m-d');
    $arrData['dataPermanentdate'] = date('Y-m-d');
    $arrData['dataResigndate'] = date('Y-m-d');
  }
  return true;
} // showData
// fungsi untuk mengambil daftar alamat lainnya , trmasuk pilihan
function getMoreAddress($db, $strDataID = "")
{
  global $words;
  global $intDefaultWidth;
  global $intDefaultWidthPx;
  global $intDefaultHeight;
  $strResult = "";
  $intAdd = 5;
  $intTotal = 5;
  $intCurr = 0;
  // cari data address tambahan, jika ada
  if ($strDataID != "") {
    $strSQL = "SELECT * FROM hrd_employee_address ";
    $strSQL .= "WHERE id_employee = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $intCurr++;
      $strResult .= "<tr valign=top id='detailAddress$intCurr'>\n";
      $strResult .= "  <td>&nbsp;</td>\n";
      $strResult .= "  <td>&nbsp;</td>\n";
      $strResult .= "  <td>" . nl2br($rowDb['address']) . "</td>";
      $strResult .= "</tr>\n";
      $strResult .= "<tr valign=top id='detailCity$intCurr'>\n";
      $strResult .= "  <td>&nbsp;</td>\n";
      $strResult .= "  <td>&nbsp;</td>\n";
      $strResult .= "  <td>" . $rowDb['city'] . " / " . $rowDb['zip'] . "</td>";
      $strResult .= "</tr>\n";
    }
  }
  return $strResult;
} //getMoreAddress
// fungsi untuk mengambil daftar phone lainnya , trmasuk pilihan
function getMorePhone($db, $strDataID = "")
{
  global $words;
  global $intDefaultWidth;
  global $intDefaultWidthPx;
  global $intDefaultHeight;
  $strResult = "";
  $intAdd = 5;
  $intTotal = 5;
  $intCurr = 0;
  // cari data phone tambahan, jika ada
  if ($strDataID != "") {
    $strSQL = "SELECT * FROM hrd_employee_phone ";
    $strSQL .= "WHERE id_employee = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $intCurr++;
      $strResult .= "<tr valign=top id='detailPhone$intCurr'>\n";
      $strResult .= "  <td>&nbsp;</td>\n";
      $strResult .= "  <td>&nbsp;</td>\n";
      $strResult .= "  <td>" . $rowDb['phone'] . "&nbsp;</td>";
      $strResult .= "</tr>\n";
    }
  }
  return $strResult;
} //getMorePhone
// fungsi untuk mengambil daftar emergency contact lainnya , trmasuk pilihan
/*
function getMoreContact($db,$strDataID = "") {
  global $words;
  global $intDefaultWidth;
  global $intDefaultWidthPx;
  global $intDefaultHeight;

  $strResult = "";
  $intAdd = 3;
  $intTotal = 5;

  $intCurr = 0;
  // cari data phone tambahan, jika ada
  if ($strDataID != "") {
    $strSQL  = "SELECT * FROM hrd_employee_contact ";
    $strSQL .= "WHERE id_employee = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $intCurr++;
      $strResult .= "<tr valign=top id='detailEmergencyContact$intCurr'>\n";
      $strResult .= "  <td nowrap>&nbsp;<strong>" .$words['emergency contact']. " ".($intCurr + 1). "</strong></td>\n";
      $strResult .= "  <td>:&nbsp;</td>\n";
      $strResult .= "  <td>" .$rowDb['contact']. "&nbsp;</td>";
      $strResult .= "</tr>\n";
      $strResult .= "<tr valign=top id='detailEmergencyRelation$intCurr'>\n";
      $strResult .= "  <td nowrap>&nbsp;<strong>" .$words['relation']. " ".($intCurr + 1). "</strong></td>\n";
      $strResult .= "  <td>:&nbsp;</td>\n";
      $strResult .= "  <td>" .$rowDb['relation']. "&nbsp;</td>";
      $strResult .= "</tr>\n";
      $strResult .= "<tr valign=top id='detailEmergencyAddress$intCurr'>\n";
      $strResult .= "  <td nowrap>&nbsp;<strong>" .$words['emergency address']. " ".($intCurr + 1). "</strong></td>\n";
      $strResult .= "  <td>:&nbsp;</td>\n";
      $strResult .= "  <td>" .$rowDb['address']. "&nbsp;</td>";
      $strResult .= "</tr>\n";
      $strResult .= "<tr valign=top id='detailEmergencyPhone$intCurr'>\n";
      $strResult .= "  <td nowrap>&nbsp;<strong>" .$words['emergency phone']. " ".($intCurr + 1). "</strong></td>\n";
      $strResult .= "  <td>:&nbsp;</td>\n";
      $strResult .= "  <td>" .$rowDb['phone']. "&nbsp;</td>";
      $strResult .= "</tr>\n";
    }
  }

  return $strResult;
} //getMorePhone
*/
// fungsiuntuk mengambil daftar anggota keluarga berdasar id karyawan
function getEmployeeFamily($db, $strDataID = "")
{
  global $ARRAY_FAMILY_RELATION;
  $strResult = "";
  if ($strDataID === "") {
    return "";
  }
  // cari daftar keluarga
  $intRows = 0;
  $strSQL = "SELECT *,EXTRACT(year FROM AGE(birthday)) AS umur FROM hrd_employee_family ";
  $strSQL .= "WHERE id_employee = '$strDataID' ORDER BY relation, birthday DESC ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strGender = ($rowDb['gender'] == 1) ? "M" : "F";
    $strRelation = getWords($ARRAY_FAMILY_RELATION[$rowDb['relation']]);
    $strResult .= " <tr valign=top>\n";
    $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['name'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $strGender . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['umur'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . pgDateFormat($rowDb['birthday'], "d-M-y") . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $strRelation . "</td>\n";
    $strResult .= " </tr>\n";
  }
  if ($intRows == 0) {
    $strResult .= "<tr align=center><td colspan=6>--</td></tr>\n";
  }
  return $strResult;
}//getEmployeeFamily
// fungsiuntuk mengambil daftar pendidikan
function getEmployeeEducation($db, $strDataID = "")
{
  global $ARRAY_FAMILY_RELATION;
  $strResult = "";
  if ($strDataID === "") {
    return "";
  }
  // cari daftar pendidikan
  $intRows = 0;
  $strSQL = "SELECT * FROM hrd_employee_education ";
  $strSQL .= "WHERE id_employee = '$strDataID' ORDER BY year_from, month_from, day_from ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strPeriode1 = "";
    if ($rowDb['day_from'] != "") {
      $strPeriode1 .= $rowDb['day_from'];
    }
    if ($rowDb['month_from'] != "") {
      $strPeriode1 .= getBulanSingkat($rowDb['month_from']);
    }
    if ($rowDb['year_from'] != "") {
      $strPeriode1 .= $rowDb['year_from'];
    }
    $strPeriode2 = "";
    if ($rowDb['day_thru'] != "") {
      $strPeriode2 .= $rowDb['day_thru'];
    }
    if ($rowDb['month_thru'] != "") {
      $strPeriode2 .= getBulanSingkat($rowDb['month_thru']);
    }
    if ($rowDb['year_thru'] != "") {
      $strPeriode2 .= $rowDb['year_thru'];
    }
    if ($strPeriode1 == "" || $strPeriode2 == "") {
      $strPeriode = $strPeriode1 . $strPeriode2;
    } else {
      $strPeriode = $strPeriode1 . " - " . $strPeriode2;
    }
    $strResult .= " <tr valign=top>\n";
    $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['education_level_code'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['institution'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['faculty'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['location'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['registration_no'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['certificate_no'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $strPeriode . "</td>\n";
    $strResult .= "  <td>&nbsp;" . $rowDb['note'] . "</td>\n";
    $strResult .= " </tr>\n";
  }
  if ($intRows == 0) {
    $strResult .= "<tr align=center><td colspan=7>--</td></tr>\n";
  }
  return $strResult;
}//getEmployeeEducation
// fungsiuntuk mengambil daftar training
function getEmployeeTraining($db, $strDataID = "")
{
  global $bolPrint;
  $strResult = "";
  if ($strDataID === "") {
    return "";
  }
  // cari daftar training
  $intRows = 0;
  $strSQL = "SELECT * FROM hrd_employee_training ";
  $strSQL .= "WHERE id_employee = '$strDataID' ORDER BY year_from, month_from, day_from ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strPeriode1 = "";
    if ($rowDb['day_from'] != "") {
      $strPeriode1 .= $rowDb['day_from'];
    }
    if ($rowDb['month_from'] != "") {
      $strPeriode1 .= " " . getBulanSingkat($rowDb['month_from']);
    }
    if ($rowDb['year_from'] != "") {
      $strPeriode1 .= " " . $rowDb['year_from'];
    }
    $strPeriode2 = "";
    if ($rowDb['day_thru'] != "") {
      $strPeriode2 .= $rowDb['day_thru'];
    }
    if ($rowDb['month_thru'] != "") {
      $strPeriode2 .= " " . getBulanSingkat($rowDb['month_thru']);
    }
    if ($rowDb['year_thru'] != "") {
      $strPeriode2 .= " " . $rowDb['year_thru'];
    }
    if ($strPeriode1 == "" || $strPeriode2 == "") {
      $strPeriode = $strPeriode1 . $strPeriode2;
    } else {
      $strPeriode = $strPeriode1 . " - " . $strPeriode2;
    }
    $strResult .= " <tr valign=top>\n";
    if ($bolPrint) {
      $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>\n";
      $strResult .= "  <td nowrap>&nbsp;" . $rowDb['subject'] . "</td>\n";
      $strResult .= "  <td nowrap>&nbsp;" . $rowDb['institution'] . "</td>\n";
      $strResult .= "  <td nowrap>&nbsp;" . $rowDb['trainer'] . "</td>\n";
      $strResult .= "  <td nowrap>&nbsp;" . $strPeriode . "</td>\n";
      $strResult .= "  <td nowrap align=right>" . standardFormat($rowDb['cost'], true, 0) . "&nbsp;</td>\n";
      $strResult .= "  <td>&nbsp;" . $rowDb['note'] . "</td>\n";
    } else {
      $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>\n";
      $strResult .= "  <td nowrap>&nbsp;" . $rowDb['subject'] . "</td>\n";
      $strResult .= "  <td nowrap>&nbsp;" . $rowDb['institution'] . "</td>\n";
      $strResult .= "  <td nowrap>&nbsp;" . $rowDb['location'] . "</td>\n";
      $strResult .= "  <td nowrap>&nbsp;" . $rowDb['trainer'] . "</td>\n";
      $strResult .= "  <td nowrap>&nbsp;" . $strPeriode . "</td>\n";
      $strResult .= "  <td nowrap align=right>" . standardFormat($rowDb['cost'], true, 0) . "&nbsp;</td>\n";
      $strResult .= "  <td>&nbsp;" . $rowDb['note'] . "</td>\n";
    }
    $strResult .= " </tr>\n";
  }
  if ($intRows == 0) {
    $strResult .= "<tr align=center><td colspan=8>--</td></tr>\n";
  }
  return $strResult;
}//getEmployeeTraining
// fungsiuntuk mengambil daftar pengalaman kerja
function getEmployeeWork($db, $strDataID = "")
{
  $strResult = "";
  if ($strDataID === "") {
    return "";
  }
  // cari daftar pengalaman kerja
  $intRows = 0;
  $strSQL = "SELECT * FROM hrd_employee_work ";
  $strSQL .= "WHERE id_employee = '$strDataID' ORDER BY year_from, month_from, day_from ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strPeriode1 = "";
    if ($rowDb['day_from'] != "") {
      $strPeriode1 .= $rowDb['day_from'];
    }
    if ($rowDb['month_from'] != "") {
      $strPeriode1 .= " " . getBulanSingkat($rowDb['month_from']);
    }
    if ($rowDb['year_from'] != "") {
      $strPeriode1 .= " " . $rowDb['year_from'];
    }
    $strPeriode2 = "";
    if ($rowDb['day_thru'] != "") {
      $strPeriode2 .= $rowDb['day_thru'];
    }
    if ($rowDb['month_thru'] != "") {
      $strPeriode2 .= " " . getBulanSingkat($rowDb['month_thru']);
    }
    if ($rowDb['year_thru'] != "") {
      $strPeriode2 .= " " . $rowDb['year_thru'];
    }
    if ($strPeriode1 == "" || $strPeriode2 == "") {
      $strPeriode = $strPeriode1 . $strPeriode2;
    } else {
      $strPeriode = $strPeriode1 . " - " . $strPeriode2;
    }
    $strResult .= " <tr valign=top>\n";
    $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['institution'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['location'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['position'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $strPeriode . "</td>\n";
    $strResult .= "  <td>&nbsp;" . $rowDb['note'] . "</td>\n";
    $strResult .= " </tr>\n";
  }
  if ($intRows == 0) {
    $strResult .= "<tr align=center><td colspan=6>--</td></tr>\n";
  }
  return $strResult;
}//getEmployeeWork
// [B.M] - Fungsi2 tambahan untuk format Print
// fungsi untuk mengambil tax Marital Status
function getTaxMaritalStatus($db, $strDataID = "")
{
  // un-Implemented
  $strResult = "----";
  return $strResult;
}//getTaxMaritalStatus
// fungsi untuk mengambil tipe pembayaran gaji
function getWageType($db, $strDataID = "")
{
  // un-Implemented
  $strResult = "----";
  return $strResult;
}//getWageType
// fungsi untuk menggambil cara pembayaran gaji
// fungsi untuk mengambil nama jenis tabungan
function getAccountName($db, $strDataID = "")
{
  // un-Implemented
  $strResult = "----";
  return $strResult;
}//getAccountName
// fungsi untuk mengambil data Surat Peringatan
function getEmployeeWarning($db, $strDataID = "")
{
  $strResult = "";
  if ($strDataID === "") {
    return "";
  }
  // cari daftar surat peringatan
  $intRows = 0;
  $strSQL = "SELECT * FROM hrd_employee_warning ";
  $strSQL .= "WHERE id_employee = '$strDataID' ORDER BY warning_date";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strResult .= " <tr valign=top>\n";
    $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . pgDateFormat($rowDb['warning_date'], "d-M-y") . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['warning_code'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['duration'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['due_date'] . "</td>\n";
    $strResult .= "  <td>&nbsp;" . $rowDb['note'] . "</td>\n";
    $strResult .= " </tr>\n";
  }
  if ($intRows == 0) {
    $strResult .= "<tr align=center><td colspan=6>--</td></tr>\n";
  }
  return $strResult;
}//getEmployeeWarning
// fungsi untuk mengambil data klaim karyawan
function getEmployeeClaim($db, $strDataID = "")
{
  // some un-implemented
  $strResult = "";
  if ($strDataID === "") {
    return "";
  }
  // cari daftar claim karyawan
  $intRows = 0;
  $strSQL = "select * from hrd_medical_claim c JOIN hrd_medical_claim_master m ON c.id_master = m.id  ";
  $strSQL .= "WHERE id_employee = '$strDataID' ORDER BY claim_date";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strResult .= " <tr valign=top>\n";
    $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['name'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['relation'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['disease'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . pgDateFormat($rowDb['claim_date'], "d-M-y") . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['cost'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['type'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . "No-Note" . "</td>\n";
    //      $strResult .= "  <td nowrap>&nbsp;" .$rowDb['note']. "</td>\n";
    $strResult .= "  <td>&nbsp;" . $rowDb['note'] . "</td>\n";
    $strResult .= " </tr>\n";
  }
  if ($intRows == 0) {
    $strResult .= "<tr align=center><td colspan=8>--</td></tr>\n";
  }
  return $strResult;
}//getEmployeeClaim
function getEmployeeMutationStatus($db, $strDataID = "")
{
  global $ARRAY_EMPLOYEE_STATUS;
  $strResult = "";
  if ($strDataID === "") {
    return "";
  }
  // cari status mutation status
  $intRows = 0;
  $strSQL = "SELECT * FROM hrd_employee_mutation_status as t0 LEFT JOIN hrd_employee_mutation as t1 on t1.id = t0.id_mutation ";
  $strSQL .= "WHERE t1.id_employee = '$strDataID' AND t1.status = 6";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strResult .= " <tr valign=top>\n";
    $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . strtoupper($ARRAY_EMPLOYEE_STATUS[$rowDb['status_new']]) . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . strtoupper($ARRAY_EMPLOYEE_STATUS[$rowDb['status_new']]) . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . pgDateFormat($rowDb['status_date_from'], "d-M-y") . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . pgDateFormat($rowDb['status_date_thru'], "d-M-y") . "</td>\n";
    $strResult .= " </tr>\n";
  }
  if ($intRows == 0) {
    $strResult .= "<tr align=center><td colspan=5>--</td></tr>\n";
  }
  return $strResult;
}//getEmployeeMutationStatus
/*function getEmployeeMutationPosition($db, $strDataID = "") {
    global $arrPosition;
    $strResult = "";
    if ($strDataID === "") return "";

    // cari status mutation promotion
    $intRows = 0;

    $strSQL  = "SELECT * FROM hrd_employee_mutation_position as t0 LEFT JOIN hrd_employee_mutation as t1 on t1.id = t0.id_mutation ";
    $strSQL .= "WHERE t1.id_employee = '$strDataID' AND t1.status = 6";
    $resDb = $db->execute($strSQL);

    while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $strResult .= " <tr valign=top>\n";
        $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>\n";
        $strResult .= "  <td nowrap>&nbsp;".$rowDb['grade_old']."</td>\n";
        $strResult .= "  <td nowrap>&nbsp;".$arrPosition[$rowDb['position_old']]."</td>\n";
        $strResult .= "  <td nowrap>&nbsp;".$rowDb['functional_old']."</td>\n";
        $strResult .= "  <td nowrap>&nbsp;".$rowDb['grade_new']."</td>\n";
        $strResult .= "  <td nowrap>&nbsp;".$arrPosition[$rowDb['position_new']]."</td>\n";
        $strResult .= "  <td nowrap>&nbsp;".$rowDb['functional_new']."</td>\n";
        $strResult .= "  <td nowrap>&nbsp;" .pgDateFormat($rowDb['position_new_date'], "d-M-y"). "</td>\n";
        $strResult .= " </tr>\n";
    }

    if ($intRows == 0) {
      $strResult .= "<tr align=center><td colspan=8>--</td></tr>\n";

    }

    return $strResult;
  }//getEmployeeMutationPosition*/
function getEmployeeMutationDepartment($db, $strDataID = "")
{
  global $arrCompanyName;
  $strResult = "";
  if ($strDataID === "") {
    return "";
  }
  // cari status mutation department
  $intRows = 0;
  $strSQL = "SELECT * FROM hrd_employee_mutation_department as t0 LEFT JOIN hrd_employee_mutation as t1 on t1.id = t0.id_mutation ";
  $strSQL .= "WHERE t1.id_employee = '$strDataID' AND t1.status = 6";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strResult .= " <tr valign=top>\n";
    $strResult .= "  <td nowrap align=right>$intRows&nbsp;</td>\n";
    //        $strResult .= "  <td nowrap>&nbsp;".$arrCompanyName[$rowDb['company_old']]."</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['management_old'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['division_old'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['department_old'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['section_old'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['sub_section_old'] . "</td>\n";
    //        $strResult .= "  <td nowrap>&nbsp;".$arrCompanyName[$rowDb['company_new']]."</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['management_new'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['division_new'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['department_new'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['section_new'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . $rowDb['sub_section_new'] . "</td>\n";
    $strResult .= "  <td nowrap>&nbsp;" . pgDateFormat($rowDb['department_new_date'], "d-M-y") . "</td>\n";
    $strResult .= " </tr>\n";
  }
  if ($intRows == 0) {
    $strResult .= "<tr align=center><td colspan=14>--</td></tr>\n";
  }
  return $strResult;
}//getEmployeeMutationDepartment
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  (isset($_POST['dataID'])) ? $strDataID = $_POST['dataID'] : $strDataID = "";
  if ($bolCanEdit) {
    if (isset($_REQUEST['btnSave'])) {
      saveData($db, $strDataID, $strError);
      if ($strError != "") {
        echo "<script>alert(\"$strError\")</script>";
      }
    }
  }
  if ($strDataID != "") {
    getData($db, $arrData, $strDataID);
  }
  /*
  else
  {
      //showError("view_denied");
    $strDataDetail = "";
    redirectPage("employee_search.php");

  }*/
  //----- TAMPILKAN DATA ---------
  if ($bolIsEmployee && !isMe($strDataID)) {
    redirectPage("employee_search.php");
  }
  if (thisUserIs(ROLE_SUPERVISOR)) {
    if ($arrUserInfo['sub_section_code'] != "" && $arrUserInfo['sub_section_code'] != $arrData['dataSubSectionCode']) {
      redirectPage("employee_search.php");
    } else if ($arrUserInfo['section_code'] != "" && $arrUserInfo['section_code'] != $arrData['dataSectionCode']) {
      redirectPage("employee_search.php");
    } else if ($arrUserInfo['department_code'] != "" && $arrUserInfo['department_code'] != $arrData['dataDepartmentCode']) {
      redirectPage("employee_search.php");
    } else if ($arrUserInfo['division_code'] != "" && $arrUserInfo['division_code'] != $arrData['dataDivisionCode']) {
      redirectPage("employee_search.php");
    }
  }
  $strDataPhoto = "";
  $strInputEmployeeID = $arrData['dataEmployeeID'];
  $strInputEmployeeID .= ($bolPrint) ? "" : "<input type=hidden name='dataEmployeeID'  value=" . $arrData['dataEmployeeID'] . ">";
  $strInputFingerID = $arrData['dataFingerID'] . "";
  $strInputLetterCode = $arrData['dataLetterCode'] . "";
  $strInputNickname = $arrData['dataNickname'] . "";
  $strInputName = $arrData['dataName'] . "";
  $strInputCity = $arrData['dataCity'] . "";
  $strInputZip = $arrData['dataZip'] . "";
  $strInputPhone = $arrData['dataPhone'] . "";
  $strInputPrivatePhone = $arrData['dataPrivatePhone'] . "";
  $strInputEmail = $arrData['dataEmail'] . "";
  $strInputPrivateEmail = $arrData['dataPrivateEmail'] . "";
  $strInputEmergencyContact = $arrData['dataEmergencyContact'] . "";
  $strInputEmergencyRelation = $arrData['dataEmergencyRelation'] . "";
  $strInputEmergencyPhone = $arrData['dataEmergencyPhone'] . "";
  $strInputBirthplace = $arrData['dataBirthplace'] . "";
  $strInputWeight = $arrData['dataWeight'] . "";
  $strInputHeight = $arrData['dataHeight'] . "";
  $strInputBlood = $arrData['dataBlood'] . "";
  $strInputLivingCost = $arrData['dataLivingCost'] . "";
  $strInputMedicalQuotaStatus = $arrData['dataMedicalQuotaStatus'] . "";
  $strInputCompany = $arrData['dataCompany'] . "";
  $strInputBranch = $arrData['dataBranch'] . "";
  $strInputManagement = $arrData['dataManagement'] . "";
  $strInputFunctionalPosition = $arrData['dataFunctionalPosition'] . "";
  $strInputJamsostekNo = $arrData['dataJamsostekNo'] . "";
  $strInputIsZakat = $arrData['dataZakat'] . "";
  $strInputIDCard = $arrData['dataIDCard'] . "";
  $strInputLicenseA = $arrData['dataLicenseA'] . "";
  $strInputLicenseB = $arrData['dataLicenseB'] . "";
  $strInputLicenseC = $arrData['dataLicenseC'] . "";
  $strInputNationality = $arrData['dataNationality'] . "";
  $strInputPassport = $arrData['dataPassport'] . "";
  $strInputJamsostek = $arrData['dataJamsostekNo'] . "";
  $strInputInspouse = $arrData['dataInspouse'] . "";
  $strInputTransport = $arrData['dataTransport'] . "";
  $strInputTransportFee = $arrData['dataTransportFee'] . "";
  $strInputNPWP = $arrData['dataNPWP'] . "";
  $strInputBankCode = $arrData['dataBankCode'] . "";
  $strInputBankAccount = $arrData['dataBankAccount'] . "";
  $strInputBankBranch = $arrData['dataBankBranch'] . "";
  $strInputBankAccountType = $arrData['dataBankAccountType'] . "";
  $strInputBankAccountName = $arrData['dataBankAccountName'] . "";
  $strInputBank2Code = $arrData['dataBank2Code'] . "";
  $strInputBank2Account = $arrData['dataBank2Account'] . "";
  $strInputBank2Branch = $arrData['dataBank2Branch'] . "";
  $strInputBank2AccountType = $arrData['dataBank2AccountType'] . "";
  $strInputBank2AccountName = $arrData['dataBank2AccountName'] . "";
  $strInputBirthday = pgDateFormat($arrData['dataBirthday'] . "", "d-M-Y");
  $strInputJoinDate = pgDateFormat($arrData['dataJoindate'] . "", "d-M-Y");
  $strInputDueDate = pgDateFormat($arrData['dataDuedate'] . "", "d-M-Y");
  $strInputPermanentDate = pgDateFormat($arrData['dataPermanentdate'] . "", "d-M-Y");
  $strInputResignDate = pgDateFormat($arrData['dataResigndate'] . "", "d-M-Y");
  $strInputWeddingDate = pgDateFormat($arrData['dataWeddingdate'] . "", "d-M-Y");
  $strInputAddress = nl2br($arrData['dataAddress'] . "");
  $strInputEmergencyAddress = nl2br($arrData['dataEmergencyAddress'] . "");
  $strInputNote = nl2br($arrData['dataNote'] . "");
  $strInputEmployeeStatus = $arrData['dataEmployeeStatus'];
  $strInputGender = $arrData['dataGender'] . "";
  $strInputEmployeeStatus = getWords($ARRAY_EMPLOYEE_STATUS[$strInputEmployeeStatus]);
  $strInputReligion = $arrData['dataReligion'] . "";
  $strInputEducation = $arrData['dataEducation'] . "";
  //$strInputMaritalStatus    = ($arrData['dataMaritalStatus'] =="") ? "" : getWords($ARR_DATA_MARITAL_STATUS[$arrData['dataMaritalStatus']]);
  $strInputFamilyStatus = $arrData['dataFamilyStatus'] . "";
  $strInputDivision = $arrData['dataDivision'] . "";
  $strInputDepartment = $arrData['dataDepartment'] . "";
  $strInputSection = $arrData['dataSection'] . "";
  $strInputSubSection = $arrData['dataSubSection'] . "";
  $strInputFunction = $arrData['dataFunction'] . "";
  $strInputPosition = $arrData['dataPosition'] . "";
  $strInputActive = $arrData['dataActive'] . "";
  $strInputSalaryGrade = $arrData['dataSalaryGrade'] . "";
  /*if ($_SESSION['sessionUserRole'] == ROLE_SUPER || $arrData['dataEmployeeID'] == $arrUserInfo['employee_id'])
    $strInputSalaryGrade = $arrData['dataSalaryGrade']."";
  else
    $strInputSalaryGrade = "&nbsp;";*/
  $arrCompanyName = [];
  $strSQL = "SELECT id, company_name FROM hrd_company ";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrCompanyName[$rowTmp['id']] = $rowTmp['company_name'];
  }
  $arrReligion = [];
  $strSQL = "SELECT code, name FROM hrd_religion ";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrReligion[$rowTmp['code']] = $rowTmp['name'];
  }
  $arrSubSection = [];
  $strSQL = "SELECT sub_section_code, sub_section_name FROM hrd_sub_section ";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrSubSection[$rowTmp['sub_section_code']] = $rowTmp['sub_section_name'];
  }
  $arrSection = [];
  $strSQL = "SELECT section_code, section_name FROM hrd_section ";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrSection[$rowTmp['section_code']] = $rowTmp['section_name'];
  }
  $arrDepartment = [];
  $strSQL = "SELECT department_code, department_name FROM hrd_department";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDepartment[$rowTmp['department_code']] = $rowTmp['department_name'];
  }
  $arrDivision = [];
  $strSQL = "SELECT division_code, division_name FROM hrd_division";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrDivision[$rowTmp['division_code']] = $rowTmp['division_name'];
  }
  $arrManagement = [];
  $strSQL = "SELECT management_code, management_name FROM hrd_management ";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrManagement[$rowTmp['management_code']] = $rowTmp['management_name'];
  }
  $arrBranch = [];
  $strSQL = "SELECT branch_code, branch_name FROM hrd_branch";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrBranch[$rowTmp['branch_code']] = $rowTmp['branch_code'] . " - " . $rowTmp['branch_name'];
  }
  $arrPosition = [];
  $strSQL = "SELECT position_code, position_name FROM hrd_position";
  $resTmp = $db->execute($strSQL);
  while ($rowTmp = $db->fetchrow($resTmp)) {
    $arrPosition[$rowTmp['position_code']] = $rowTmp['position_name'];
  }
  $strInputActive = ($strInputActive == 1) ? "Yes" : "No";
  $strInputInspouse = ($strInputInspouse == 't') ? "Yes" : "No";
  $strInputIsZakat = ($strInputIsZakat == 't') ? "Yes" : "No";
  $strInputSubSection = (isset($arrSubSection[$strInputSubSection])) ? $arrSubSection[$strInputSubSection] : $strInputSubSection;
  $strInputSection = (isset($arrSection[$strInputSection])) ? $arrSection[$strInputSection] : $strInputSection;
  $strInputDepartment = (isset($arrDepartment[$strInputDepartment])) ? $arrDepartment[$strInputDepartment] : $strInputDepartment;
  $strInputDivision = (isset($arrDivision[$strInputDivision])) ? $arrDivision[$strInputDivision] : $strInputDivision;
  $strInputManagement = (isset($arrManagement[$strInputManagement])) ? $arrManagement[$strInputManagement] : $strInputManagement;
  $strInputBranch = ($strInputBranch == "") ? "" : $arrBranch[$strInputBranch];
  $strInputPosition = ($strInputPosition == "") ? "" : $arrPosition[$strInputPosition];
  $strInputCompany = $arrCompanyName[$strInputCompany];
  $strMoreAddress = "";//getMoreAddress($db, $strDataID);
  $strMorePhone = "";//getMorePhone($db,$strDataID);
  $strMoreContact = "";//getMoreContact($db,$strDataID);
  $strDataFamily = getEmployeeFamily($db, $strDataID);
  $strDataEducation = getEmployeeEducation($db, $strDataID);
  $strDataTraining = getEmployeeTraining($db, $strDataID);
  $strDataWork = getEmployeeWork($db, $strDataID);
  $today = getdate();
  $strToday = $today['weekday'] . ", " . $today['mday'] . " " . $today['month'] . " " . $today['year'];
  //$strInputTaxMaritalStatus = getTaxMaritalStatus($db, $arrData['dataEmployeeID']);
  //$strInputCurrency = $ARRAY_CURRENCY[$arrData['dataCurrency']];
  //$strInputSalaryPeriod = $ARRAY_SALARY_PERIOD [$arrData['dataSalaryPeriod']];
  //getWageType($db, $arrData['dataEmployeeID']);
  $strInputPaymentType = $ARRAY_PAYMENT_METHOD[$arrData['dataSalaryPaymentType']];
  $strInputAccountType = $arrData['dataBankAccountType'];
  $strInputAccountType = (isset($strInputAccountType)) ? $strInputAccountType : "";
  $strDataWarning = getEmployeeWarning($db, $strDataID);
  $strDataClaim = getEmployeeClaim($db, $strDataID);
  $strDataMutationStatus = getEmployeeMutationStatus($db, $strDataID);
  //    $strDataMutationPosition = getEmployeeMutationPosition($db, $strDataID);
  $strDataMutationDepartment = getEmployeeMutationDepartment($db, $strDataID);
  //tampilkan foto
  if ($arrData['dataPhoto'] == "") {
    $strDataPhoto = "<img src='photos/dummy.gif'>";
  } else {
    if (file_exists("photos/" . $arrData['dataPhoto'])) {
      //$strDataPhoto = "<img src='photos/" .$arrData['dataPhoto']. "'>";
      $strDataPhoto = "<img src=\"employee_photo.php?dataID=$strDataID\">";
    } else {
      $strDataPhoto = "<img src='photos/dummy.gif'>";
    }
  }
}
$strCompanyName = getSetting("company_name");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords("employee data");
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strWordsResume = getWords("resume");
$pageSubMenu = employeeEditSubmenu($strWordsResume);
if ($bolPrint) {
  $strTemplateFile = getTemplate("employee_resume_print.html");
  $tbsPage->LoadTemplate($strTemplateFile);
} else {
  $strTemplateFile = getTemplate("employee_resume.html");
  $tbsPage->LoadTemplate($strMainTemplate);
}
//$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->Show();
?>