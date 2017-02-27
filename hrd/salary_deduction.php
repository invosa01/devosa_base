<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../classes/hrd/hrd_basic_salary_set.php');
include_once('../classes/hrd/hrd_employee_deduction.php');
$dataPrivilege = getDataPrivileges(
    "salary_basic.php",
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
$strDataColumn = "";
$buttonDeduction = [];
$strHidden = "";
$intTotalData = 0;
$strPaging = "";
$strSpan1 = 1; // colspan untuk colum Deduction
$strSpan2 = 4; // colspan untk bagian paging
$arrColumns = [];
$strDeductionList = "";
$strWordsCompany = getWords("Company");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSubDepartment = getWords("sub department");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("sub section");
$strWordsPosition = getWords("level");
$strWordsGrade = getWords("grade");
$strWordsEmployeeID = getWords("employee id");
$strWordsEmployeeName = getWords("employee name");
$strWordsEmployeeStatus = getWords("status");
$strWordsBasicSalary = getWords("basic salary");
$strWordsAllowance = getWords("allowance");
$strWordsStaff = getWords("staff");
$strWordsActive = getWords("active");
$strWordsFamilyStatus = getWords("family status");
$strWordsGeneralSetting = getWords("general setting");
$strWordsSalarySet = getWords("salary set");
$strWordsEmployeeAllowance = getWords("employee allowance");
$strWordsEmployeeDeduction = getWords("employee deduction");
$strWordsBranch = getWords("outlet");
$strWordsDeduction = getWords("deduction");
$strMessage = $strDisable = $strReadonly = "";
$strMsgClass = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strKriteria = "", $intPage = 1, $bolLimit = true, $strOrder = "")
{
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $strPaging;
  global $intTotalData;
  global $intRowsLimit;
  global $_SESSION;
  global $arrColumns;
  global $strDisable;
  global $strIDCompany;
  global $strIDSalarySet;
  global $arrEmployeeLastDeduction;
  //$bolLimit = false;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $intInputWidth = 20;
  $intRowsLimit = getSetting("rows_per_page");
  if (!is_numeric($intRowsLimit)) {
    $intRowsLimit = 500;
  }
  $intmodified_byID = $_SESSION['sessionUserID'];
  $intTextWidth = 12;
  $strResult = "";
  // cari total data
  $intTotal = 0;
  $strSQL = "SELECT count(id) AS total FROM hrd_employee as t1 WHERE id_company = $strIDCompany ";
  $strSQL .= $strKriteria;
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    if (is_numeric($rowDb['total'])) {
      $intTotal = $rowDb['total'];
    }
  }
  $strPaging = getPaging($intPage, $intTotal, "javascript:goPage('[PAGE]')");
  if ($strPaging == "") {
    $strPaging = "1&nbsp;";
  }
  $intStart = (($intPage - 1) * $intRowsLimit);
  $intRows = 0;
  $strSQL = "SELECT t1.id,t1.employee_id, t1.employee_name, t1.gender, ";
  $strSQL .= "t1.employee_status, t1.position_code, t1.grade_code ";
  $strSQL .= "FROM hrd_employee AS t1 ";
  $strSQL .= "WHERE t1.id_company = $strIDCompany $strKriteria ORDER BY $strOrder employee_name ";
  if ($bolLimit) {
    $strSQL .= "LIMIT $intRowsLimit OFFSET $intStart ";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    ($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
    /*
          $strZakat = "";
           // ambil data yang ada gaji pokok dan tunjangan jabatan
          $strSQL = "SELECT zakat FROM hrd_employee_basic_salary WHERE id_employee = '" .$rowDb['id']. "' ";
          $resTmp = $db->execute($strSQL);
          if ($rowTmp = $db->fetchrow($resTmp)) {
            $strZakat = ($rowTmp['zakat'] == 't') ? "checked" : "";
          }
    */
    $strEmpStatus = ($rowDb['employee_status'] == "") ? "" : getWords(
        $ARRAY_EMPLOYEE_STATUS[$rowDb['employee_status']]
    );
    // ----- TAMPILKAN DATA ---------------------------------------
    $strResult .= "<tr valign=top id=detailData$intRows title=\"" . $rowDb['employee_id'] . "-" . $rowDb['employee_name'] . "\">\n";
    $strResult .= "  <td nowrap>" . ($intStart + $intRows) . "&nbsp;</td>";
    $strResult .= "  <td nowrap><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">" . $rowDb['employee_id'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $strEmpStatus . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['grade_code'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['position_code'] . "&nbsp;</td>";
    //$strResult .= "  <td align=center><input type=checkbox name=detailZakat$intRows value='t' $strZakat>&nbsp;</td>";
    // ambil dta potongan yang dimiliki employee, jika ada
    $i = 0;
    foreach ($arrColumns AS $strKode => $strAmount) {
      $i++;
      $fltAmount = 0;
      // cari dulu, apakah sudah ada di data atau belum
      $strSQL = "SELECT * FROM hrd_employee_deduction WHERE id_employee = '" . $rowDb['id'] . "' ";
      $strSQL .= "AND deduction_code = '$strKode' AND id_salary_set = $strIDSalarySet ";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $fltAmount = (float)$rowTmp['amount'];
      } else {
        $fltAmount = (isset($arrEmployeeLastDeduction[$rowDb['id']][$strKode])) ? $arrEmployeeLastDeduction[$rowDb['id']][$strKode] : (float)$strAmount;
        // simpan data
        $strSQL = "INSERT INTO hrd_employee_deduction (created, modified_by, created_by, ";
        $strSQL .= "id_employee, deduction_code, amount, id_salary_set) ";
        $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '" . $rowDb['id'] . "', ";
        $strSQL .= "'$strKode', '$fltAmount', $strIDSalarySet) ";
        $resExec = $db->execute($strSQL);
      }
      $strResult .= "<td align='center'><input type=\"text\" name=\"dataDeduction$i" . "_$intRows\" size=$intTextWidth maxlength=20 value=\"$fltAmount\" class=\"numeric form-control\" $strDisable></td>\n";
    }
    if ($i == 0) {
      $strResult .= "<td>&nbsp;</td>";
    }
    $strResult .= "</tr>\n";
  }
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  $intTotalData = $intTotal;
  return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strMsg)
{
  global $_REQUEST;
  global $_SESSION;
  global $strIDSalarySet;
  $bolOK = true;
  $strmodified_byID = $_SESSION['sessionUserID'];
  (isset($_REQUEST['totalData'])) ? $intTotalData = $_REQUEST['totalData'] : $intTotalData = 0;
  if (!is_numeric($intTotalData)) {
    $intTotalData = 0;
  };
  // ambil data daftar potongan
  $intColumn = (isset($_REQUEST['totalColumn'])) ? $_REQUEST['totalColumn'] : 0;
  for ($i = 1; $i <= $intColumn; $i++) {
    $arrColumn[$i] = (isset($_REQUEST['dataDeductionType' . $i])) ? $_REQUEST['dataDeductionType' . $i] : "";
  }
  for ($i = 1; $i <= $intTotalData; $i++) {
    (isset($_REQUEST['detailID' . $i])) ? $strDataID = $_REQUEST['detailID' . $i] : $strDataID = "";
    if ($strDataID != "") {
      // simpan data potongan lain-lain
      for ($j = 1; $j <= $intColumn; $j++) {
        $strKode = $arrColumn[$j];
        if ($strKode != "") {
          $fltDeduction = (isset($_REQUEST['dataDeduction' . $j . "_" . $i])) ? $_REQUEST['dataDeduction' . $j . "_" . $i] : 0;
          if (!is_numeric($fltDeduction)) {
            $fltDeduction = 0;
          }
          // cek apakah ada atau tidak
          $strSQL = "SELECT id FROM hrd_employee_deduction WHERE id_employee = '$strDataID' ";
          $strSQL .= "AND deduction_code = '$strKode' AND id_salary_set = $strIDSalarySet ";
          $resTmp = $db->execute($strSQL);
          if ($rowTmp = $db->fetchrow($resTmp)) {
            $strSQL = "UPDATE hrd_employee_deduction SET amount = '$fltDeduction' ";
            $strSQL .= "WHERE id = " . $rowTmp['id'];
            $resExec = $db->execute($strSQL);
          } else {
            $strSQL = "INSERT INTO hrd_employee_deduction (created, modified_by, created_by, ";
            $strSQL .= "id_employee, deduction_code, amount, id_salary_set) ";
            $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '$strDataID', ";
            $strSQL .= "'$strKode', '$fltDeduction', $strIDSalarySet) ";
            $resExec = $db->execute($strSQL);
          }
        }
      }
      /*
              // update status zakat
              $strZakat = (isset($_REQUEST['detailZakat'.$i])) ? "t" : "f";

              $strSQL = "UPDATE hrd_employee_basic_salary SET zakat = '$strZakat' ";
              $strSQL .= "WHERE id_employee = '$strDataID' ";
              $resExec = $db->execute($strSQL);
              */
    }
  }
  $strMsg = ($bolOK) ? getWords("data saved") : getWords("data not saved");
  $strMsg .= " @ " . date("r");
  return $bolOK;
}

// fungsi untuk mengambil daftar potongan, selain potongan jabatan, simpan ke kolom array
function getDeductionData($db)
{
  global $strDisable;
  global $arrColumns;
  global $strDataColumn;
  global $strSpan1;
  global $strSpan2;
  global $strDeductionList;
  global $strDefaultWidthPx;
  global $buttonDeduction;
  $strDeductionList = "<select name=dataDeductionImport style=\"width:$strDefaultWidthPx\">\n";
  $strSQL = "SELECT * FROM hrd_deduction_type WHERE active = 't' ";
  $resDb = $db->execute($strSQL);
  $i = 0;
  while ($rowDb = $db->fetchrow($resDb)) {
    $i++;
    $strDataColumn .= "<th class=\"center width-200\" ><input type=hidden name=dataDeductionType$i value=\"" . $rowDb['code'] . "\">";
    $strDataColumn .= $rowDb['name'] . "</th>\n";
    $buttonDeduction[] = "<input $strDisable type=\"submit\" name=\"btnDefault" . $rowDb['code'] . "\" value=\"Get Default\"  class=\"btn btn-primary btn-sm btn-block\" onclick=\"return getDefault('" . $rowDb['code'] . "','" . $rowDb['name'] . "');\">";
    $arrColumns[$rowDb['code']] = $rowDb['amount'];
    $strDeductionList .= "<option value='" . $rowDb['code'] . "'>" . ($rowDb['code']) . "</option>\n";
  }
  $strSpan1 += $i;
  $strSpan2 += $i;
  // handle jika kosong
  if ($i == 0) {
    $strDataColumn .= "<th nowrap>&nbsp;</th>\n";
    $strSpan1++;
    $strSpan2++;
  }
  $strDeductionList .= "</select>\n";
  return true;
} // getDeductionData
// fungsi untuk mengisi default nilai potongan seluruh data karyawan
function setDefaultDeduction($db)
{
  global $strIDSalarySet;
  $strKode = (isset($_REQUEST['dataDefault2'])) ? $_REQUEST['dataDefault2'] : "";
  $fltAmount = (isset($_REQUEST['dataDefault3'])) ? $_REQUEST['dataDefault3'] : 0;
  $strUserID = $_SESSION['sessionUserID'];
  if ($strKode != "" && is_numeric($fltAmount)) {
    // hapus dulu semua
    $strSQL = "DELETE FROM hrd_employee_deduction WHERE deduction_code = '$strKode' AND id_salary_set = $strIDSalarySet";
    $resExec = $db->execute($strSQL);
    // isi nilainya
    //if ($fltAmount != 0)
    //{
    $strSQL = "
          INSERT INTO hrd_employee_deduction
            (id_salary_set, deduction_code, id_employee, amount, modified_by, created_by)
          SELECT $strIDSalarySet, '$strKode', id, '$fltAmount', '$strUserID', '$strUserID'
          FROM hrd_employee WHERE active = 1;
        ";
    $resExec = $db->execute($strSQL);
    //}
  }
}

// proses import data
function importData($db)
{
  include_once('../global/excelReader/reader.php');
  global $_FILES;
  global $_REQUEST;
  global $_SESSION;
  global $strResult;
  global $messages;
  $strError = "";
  $strmodified_byID = $_SESSION['sessionUserID'];
  $intExcelDate2000 = 36526; // nilai integer dari tanggal 01-01-2000,
  // untuk konversi tanggal dari excel (integer)
  $intTotal = 0;
  $strTotalResultForm = 0;
  $strTotalResultDetail = 0;
  // cek tunjangan apa yang diimport
  $strDeductionType = (isset($_REQUEST['dataDeductionImport'])) ? $_REQUEST['dataDeductionImport'] : "";
  if (is_uploaded_file($_FILES["fileData"]['tmp_name']) && $strDeductionType != "") {
    $cTime = new CexecutionTime();
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
    $arrEmpUnknown = []; // mencatat daftar employee ID yang gak teradftar
    $arrForm = []; // mencata daftar form yang sudah ada, indexnya adalah form number
    // UPDATE DULU SISA CUTI YG ADA
    //$intRows = 15;
    for ($i = 1; $i <= $intRows; $i++) {
      // tampung di variabel, biar pendek codingnya :D
      $arrData = (isset($data->sheets[0]['cells'][$i])) ? $data->sheets[0]['cells'][$i] : [];
      // baca data satu persatu
      //$strNo = (isset($arrData[1])) ? trim($arrData[1]) : "";
      $stremployee_id = (isset($arrData[1])) ? trim($arrData[1]) : "";
      $strName = (isset($arrData[2])) ? addslashes(trim($arrData[2])) : "";
      $fltAmount = (isset($arrData[3])) ? (trim($arrData[3])) : "";
      // validasi dan handle data
      if ($stremployee_id != "" && is_numeric($fltAmount)) {
        if (isset($arrEmp[$stremployee_id])) {
          $strIDEmployee = $arrEmp[$stremployee_id];
          // simpan di data
          $strSQL = "SELECT id FROM hrd_employee_deduction WHERE id_employee = '$strIDEmployee' ";
          $strSQL .= "AND upper(deduction_code) = upper('$strDeductionType') ";
          $resTmp = $db->execute($strSQL);
          if ($rowTmp = $db->fetchrow($resTmp)) {
            $strSQL = "UPDATE hrd_employee_deduction SET amount = '$fltAmount' ";
            $strSQL .= "WHERE id_employee = '$strIDEmployee' AND deduction_code = '$strDeductionType' ";
            $resExec = $db->execute($strSQL);
          } else {
            $strSQL = "INSERT INTO hrd_employee_deduction (created, modified_by, created_by, ";
            $strSQL .= "id_employee, deduction_code, amount) ";
            $strSQL .= "VALUES(now(), '$strmodified_byID', '$strmodified_byID', '$strIDEmployee', ";
            $strSQL .= "'$strDeductionType', '$fltAmount') ";
            $resExec = $db->execute($strSQL);
          }
          $strTotalResultDetail++;
        } else {
          if (!in_array($stremployee_id, $arrEmpUnknown)) {
            $arrEmpUnknown[] = $stremployee_id;
          }
        }
      }
    }
    if ($ok > 0) {
      $strDur = $cTime->getDuration();
      writeLog(ACTIVITY_IMPORT, MODULE_PAYROLL, "$strTotalResultDetail data in $strDur ", 0);
    }
    // tampilkan employee ID yang error
    $strErrorID = implode(", ", $arrEmpUnknown);
    $strResultStyle = "";
  }
  //fclose($handle);
  // tampilkan alert
  /*
  showMessage("Finish ...");
  print_r($arrEmpUnknown);
  echo "<script language='Javascript'>alert('Process Done! $strTotalResultForm | $strTotalResultDetail data modified!')</script>";
  */
} //importData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$strInfo = "";
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo($db);
  $bolIsEmployee = ($_SESSION['sessionUserRole'] < ROLE_ADMIN);
  $strIDSalarySet = (isset($_SESSION['sessionFilterIDSalarySet'])) ? $_SESSION['sessionFilterIDSalarySet'] : "";
  if (isset($_REQUEST['dataIDSalarySet'])) {
    $strIDSalarySet = $_REQUEST['dataIDSalarySet'];
  }
  $_SESSION['sessionFilterIDSalarySet'] = $strIDSalarySet;
  // hapus data jika ada perintah
  if (isset($_REQUEST['btnSave'])) {
    if ($bolCanEdit) {
      saveData($db, $strError);
      $strError = "data saved";
      $bolError = false;
    } else {
      $bolError = true;
      $strError = getWords("Sorry, you don't have authotrity to modify any data on this page");
    }
    $strMessage = $strError;
    $strMsgClass = ($bolError) ? "class=bgError" : "class=bgOK";
  } else if (isset($_REQUEST['btnImport'])) {
    if ($bolCanEdit) {
      importData($db);
      $_REQUEST['btnShow'] = "Show";
    }
  } else if (isset($_REQUEST['dataDefault2']) && $_REQUEST['dataDefault2'] != "") {
    if ($bolCanEdit) {
      setDefaultDeduction($db);
      $_REQUEST['btnShow'] = "Show";
    }
  }
  // ------ AMBIL DATA KRITERIA -------------------------
  $strIDSalarySet = (isset($_SESSION['sessionFilterIDSalarySet'])) ? $_SESSION['sessionFilterIDSalarySet'] : "";
  $strDataEmployee = (isset($_SESSION['sessionFilterEmployee'])) ? $_SESSION['sessionFilterEmployee'] : "";
  $strDataPosition = (isset($_SESSION['sessionFilterPosition'])) ? $_SESSION['sessionFilterPosition'] : "";
  $strDataGrade = (isset($_SESSION['sessionFilterGrade   '])) ? $_SESSION['sessionFilterGrade'] : "";
  $strDataFamilyStatus = (isset($_SESSION['sessionFilterFamilyStatus'])) ? $_SESSION['sessionFilterFamilyStatus'] : "";
  $strDataBranch = (isset($_SESSION['sessionFilterBranch'])) ? $_SESSION['sessionFilterBranch'] : "";
  $strDataDivision = (isset($_SESSION['sessionFilterDivision'])) ? $_SESSION['sessionFilterDivision'] : "";
  $strDataDepartment = (isset($_SESSION['sessionFilterDepartment'])) ? $_SESSION['sessionFilterDepartment'] : "";
  $strDataSubDepartment = (isset($_SESSION['sessionFilterSubDepartment'])) ? $_SESSION['sessionFilterSubDepartment'] : "";
  $strDataSection = (isset($_SESSION['sessionFilterSection'])) ? $_SESSION['sessionFilterSection'] : "";
  $strDataSubSection = (isset($_SESSION['sessionFilterSubSection'])) ? $_SESSION['sessionFilterSubSection'] : "";
  $strDataStatus = (isset($_SESSION['sessionFilterEmployeeStatus'])) ? $_SESSION['sessionFilterEmployeeStatus'] : "";
  $strDataActive = (isset($_SESSION['sessionFilterActive'])) ? $_SESSION['sessionFilterActive'] : "";
  if (isset($_REQUEST['dataIDSalarySet'])) {
    $strIDSalarySet = $_REQUEST['dataIDSalarySet'];
  }
  if (isset($_REQUEST['dataEmployee'])) {
    $strDataEmployee = $_REQUEST['dataEmployee'];
  }
  if (isset($_REQUEST['dataPosition'])) {
    $strDataPosition = $_REQUEST['dataPosition'];
  }
  if (isset($_REQUEST['dataGrade'])) {
    $strDataGrade = $_REQUEST['dataGrade'];
  }
  if (isset($_REQUEST['dataFamilyStatus'])) {
    $strDataFamilyStatus = $_REQUEST['dataFamilyStatus'];
  }
  if (isset($_REQUEST['dataDivision'])) {
    $strDataDivision = $_REQUEST['dataDivision'];
  }
  if (isset($_REQUEST['dataDepartment'])) {
    $strDataDepartment = $_REQUEST['dataDepartment'];
  }
  if (isset($_REQUEST['dataSubDepartment'])) {
    $strDataSubDepartment = $_REQUEST['dataSubDepartment'];
  }
  if (isset($_REQUEST['dataBranch'])) {
    $strDataBranch = $_REQUEST['dataBranch'];
  }
  if (isset($_REQUEST['dataSection'])) {
    $strDataSection = $_REQUEST['dataSection'];
  }
  if (isset($_REQUEST['dataSubSection'])) {
    $strDataSubSection = $_REQUEST['dataSubSection'];
  }
  if (isset($_REQUEST['dataStatus'])) {
    $strDataStatus = $_REQUEST['dataStatus'];
  }
  if (isset($_REQUEST['dataActive'])) {
    $strDataActive = $_REQUEST['dataActive'];
  }
  // default selalu ambil yang aktif saja
  //if($strDataActive == "") $strDataActive = 1;
  // simpan dalam session
  $_SESSION['sessionFilterIDSalarySet'] = $strIDSalarySet;
  $_SESSION['sessionFilterEmployee'] = $strDataEmployee;
  $_SESSION['sessionFilterPosition'] = $strDataPosition;
  $_SESSION['sessionFilterGrade'] = $strDataGrade;
  $_SESSION['sessionFilterFamilyStatus'] = $strDataFamilyStatus;
  $_SESSION['sessionFilterDivision'] = $strDataDivision;
  $_SESSION['sessionFilterBranch'] = $strDataBranch;
  $_SESSION['sessionFilterDepartment'] = $strDataDepartment;
  $_SESSION['sessionFilterSubDepartment'] = $strDataSubDepartment;
  $_SESSION['sessionFilterSection'] = $strDataSection;
  $_SESSION['sessionFilterSubSection'] = $strDataSubSection;
  $_SESSION['sessionFilterEmployeeStatus'] = $strDataStatus;
  $_SESSION['sessionFilterActive'] = $strDataActive;
  $intCurrPage = (isset($_REQUEST['dataPage'])) ? $_REQUEST['dataPage'] : 1;
  if (!is_numeric($intCurrPage)) {
    $intCurrPage = 1;
  }
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataSubDepartment,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  $strReadonly = (scopeCBDataEntry($strDataEmployee, $_SESSION['sessionUserRole'], $arrUserInfo)) ? "readonly" : "";
  $strDisable = (scopeCBDataEntry($strDataEmployee, $_SESSION['sessionUserRole'], $arrUserInfo)) ? "disabled" : "";
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  if ($strDataEmployee != "") {
    $strKriteria .= "AND t1.employee_id = '$strDataEmployee' ";
  }
  if ($strDataSection != "") {
    $strKriteria .= "AND t1.section_code = '$strDataSection' ";
  }
  if ($strDataSubSection != "") {
    $strKriteria .= "AND t1.sub_section_code = '$strDataSubSection' ";
  }
  if ($strDataActive != "") {
    $strKriteria .= "AND t1.active = '$strDataActive' ";
  }
  if ($strDataDepartment != "") {
    $strKriteria .= "AND t1.department_code = '$strDataDepartment' ";
  }
  if ($strDataSubDepartment != "") {
    $strKriteria .= "AND t1.sub_department_code = '$strDataSubDepartment' ";
  }
  if ($strDataDivision != "") {
    $strKriteria .= "AND t1.division_code = '$strDataDivision' ";
  }
  if ($strDataPosition != "") {
    $strKriteria .= "AND t1.position_code = '$strDataPosition' ";
  }
  if ($strDataGrade != "") {
    $strKriteria .= "AND t1.grade_code = '$strDataGrade' ";
  }
  if ($strDataStatus != "") {
    $strKriteria .= "AND t1.employee_status = '$strDataStatus' ";
  }
  if ($strDataFamilyStatus != "") {
    $strKriteria .= "AND t1.family_status_code = '$strDataFamilyStatus' ";
  }
  if ($strDataBranch != "") {
    $strKriteria .= "AND t1.branch_code = '$strDataBranch' ";
  }
  $strKriteria .= $strKriteriaCompany;
  getDeductionData($db);
  $defaultButton = '';
  for ($i = 0; $i < count($buttonDeduction); $i++) {
    $defaultButton .= '<th class="center">' . $buttonDeduction[$i] . '</th>';
  }
  $bolShow = (isset($_REQUEST['btnShow']) || isset($_REQUEST['btnSave']) || isset($_REQUEST['dataDefault']));
  $tblBasicSalarySet = new cHrdBasicSalarySet();
  $arrBasicSalarySet = $tblBasicSalarySet->findAll(
      $strKriteriaCompany,
      "id, start_date, note, id_company, id_salary_set_source",
      "",
      null,
      1,
      "id"
  );
  foreach ($arrBasicSalarySet AS $keySet => $arrSet) {
    $arrSetSource[$keySet] = $arrSet['start_date'] . " - " . printCompanyName($arrSet['id_company']);
  }
  if (isset($arrSetSource[$strIDSalarySet])) {
    $strDataStartDate = $arrBasicSalarySet[$strIDSalarySet]['start_date'];
    $strDataNote = $arrBasicSalarySet[$strIDSalarySet]['note'];
    $strIDCompany = $arrBasicSalarySet[$strIDSalarySet]['id_company'];
    $strIDSalarySetSource = $arrBasicSalarySet[$strIDSalarySet]['id_salary_set_source'];
  } else {
    $strDataNote = $strDataStartDate = "";
    $bolShow = false;
  }
  $strInputStartDate = getComboFromArray(
      $arrSetSource,
      "dataIDSalarySet",
      $strIDSalarySet,
      "style=\"width:$strDefaultWidthPx\""
  );
  $tblEmployeeDeduction = new cHrdEmployeeDeduction;
  if (isset($strIDSalarySetSource) && $strIDSalarySetSource != "" && $tblEmployeeDeduction->findCount(
          "id_salary_set = $strIDSalarySetSource"
      ) > 0
  ) {
    $arrEmployeeDeduction = $tblEmployeeDeduction->findAll(
        "id_salary_set = $strIDSalarySetSource",
        "*",
        "",
        null,
        1,
        "id"
    );
    foreach ($arrEmployeeDeduction as $strID => $arrDetailDeduction) {
      $arrEmployeeLastDeduction[$arrDetailDeduction['id_employee']][$arrDetailDeduction['deduction_code']] = $arrDetailDeduction['amount'];
    }
  }
  if ($bolCanView) {
    if ($bolShow) // tampilkan hanya jika ada permintaan dan data tanggalnya tepat
    {
      $strDataDetail = getData($db, $intTotalData, $strKriteria, $intCurrPage);
    } else {
      $strDataDetail = "";
    }
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  // generate data hidden input dan element form input
  $strInputEmployee = "<input class=form-control type=text name=dataEmployee id=dataEmployee maxlength=30 value=\"" . $strDataEmployee . "\" style=\"width:$strDefaultWidthPx\" $strReadonly>";
  $strInputSection = getSectionList(
      $db,
      "dataSection",
      $strDataSection,
      $strEmptyOption,
      "",
      "style=\"width:$strDefaultWidthPx\" $strDisable"
  );
  $strInputSubSection = getSubSectionList(
      $db,
      "dataSubSection",
      $strDataSubSection,
      $strEmptyOption,
      "",
      "style=\"width:$strDefaultWidthPx\" $strDisable"
  );
  $strInputDepartment = getDepartmentList(
      $db,
      "dataDepartment",
      $strDataDepartment,
      $strEmptyOption,
      "",
      "style=\"width:$strDefaultWidthPx\" $strDisable"
  );
  $strInputSubDepartment = getSubDepartmentList(
      $db,
      "dataSubDepartment",
      $strDataSubDepartment,
      $strEmptyOption,
      "",
      "style=\"width:$strDefaultWidthPx\" $strDisable"
  );
  $strInputBranch = getBranchList(
      $db,
      "dataBranch",
      $strDataBranch,
      $strEmptyOption,
      "",
      "style=\"width:$strDefaultWidthPx\" "
  );
  $strInputDivision = getDivisionList(
      $db,
      "dataDivision",
      $strDataDivision,
      $strEmptyOption,
      "",
      "style=\"width:$strDefaultWidthPx\" $strDisable"
  );
  $strInputActive = getEmployeeActiveList("dataActive", $strDataActive, $strEmptyOption, $strDisable);
  //handle user company-access-right
  $strInputCompany = getCompanyList(
      $db,
      "dataCompany",
      $strDataCompany,
      $strEmptyOption2,
      $strKriteria2,
      "style=\"width:$strDefaultWidthPx\" $strDisable"
  );
  $strInputPosition = getPositionList(
      $db,
      "dataPosition",
      $strDataPosition,
      $strEmptyOption,
      $strCriteriaPosition,
      "style=\"width:$strDefaultWidthPx\" $strDisable"
  );
  $strInputGrade = getSalaryGradeList(
      $db,
      "dataGrade",
      $strDataGrade,
      $strEmptyOption,
      "",
      "style=\"width:$strDefaultWidthPx\" $strDisable"
  );
  $strInputEmployeeStatus = getEmployeeStatusList(
      "dataStatus",
      $strDataStatus,
      $strEmptyOption,
      "style=\"width:$strDefaultWidthPx\" $strDisable"
  );
  $strInputFamilyStatus = getFamilyStatusList(
      $db,
      "dataFamilyStatus",
      $strDataFamilyStatus,
      $strEmptyOption,
      "",
      "style=\"width:$strDefaultWidthPx\" $strDisable"
  );
  $strBtnSave = ($bolCanEdit) ? "<input class=\"btn btn-primary btn-small\" type=submit name=\"btnSave\" value=\"Save\">" : "";
  $strHidden .= "<input type=hidden name=dataIDSalarySet value=\"$strIDSalarySet\">";
  $strHidden .= "<input type=hidden name=dataPosition value=\"$strDataPosition\">";
  $strHidden .= "<input type=hidden name=dataGrade value=\"$strDataGrade\">";
  $strHidden .= "<input type=hidden name=dataFamilyStatus value=\"$strDataFamilyStatus\">";
  $strHidden .= "<input type=hidden name=dataSection value=\"$strDataSection\">";
  $strHidden .= "<input type=hidden name=dataSubSection value=\"$strDataSubSection\">";
  $strHidden .= "<input type=hidden name=dataDepartment value=\"$strDataDepartment\">";
  $strHidden .= "<input type=hidden name=dataSubDepartment value=\"$strDataSubDepartment\">";
  $strHidden .= "<input type=hidden name=dataDivision value=\"$strDataDivision\">";
  $strHidden .= "<input type=hidden name=dataBranch value=\"$strDataBranch\">";
  $strHidden .= "<input type=hidden name=\"dataStatus\" value=\"$strDataStatus\">";
  $strHidden .= "<input type=hidden name=dataActive value=\"$strDataActive\">";
  $strHidden .= "<input type=hidden name=dataEmployee value=\"$strDataEmployee\">";
  $strHidden .= "<input type=hidden name=dataCompany value=\"$strDataCompany\">";
  $strHidden .= "<input type=hidden name=dataPage value=\"$intCurrPage\">";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strPageDesc = getWords('employee salary deduction management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = salarySetSubmenu($strWordsEmployeeDeduction);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>