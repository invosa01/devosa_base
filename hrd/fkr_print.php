<?php
include_once('../global/session.php');
include_once('../global.php');
include_once('../global/common_function.php');
include_once('../includes/datagrid/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../global/date_function.php');
include_once('../global/common_data.php');
$strSet = getWords("strSet");
$emp = getWords("emp");
$salary1 = getWords("salary1");
$salary2 = getWords("salary2");
$salary3 = getWords("salary3");
$bolCanView = true;
$bolCanEdit = true;
$strWordsApprove = getWords("approve");
$strDataCandidateID = (isset($_REQUEST['dataCandidateID'])) ? $_REQUEST['dataCandidateID'] : "";
$strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
$arrData = null;
if ($strDataID != "" || $strDataCandidateID != "") {
  if ($strDataCandidateID != "") {
    $arrData = getDataCandidate($strDataCandidateID);
  } else {
    $arrData = getData($strDataID);
  }
}
if ($arrData == null) {
  header("location: candidate_search.php");
  exit();
}
$isView = getGetValue("view", 0);
$f = new clsForm("formInput", 2, "100%", "100%");
$f->bolRequiredEntryBeforeSubmit = false;
$f->showCaption = false;
$f->message = getGetValue('message');
$f->action = basename($_SERVER['PHP_SELF']);
//$f->addHelp(getWords("help for")." ".$dataPrivilege['menu_name'], getHelps("master user"), 8, 167, 400, 300);
$f->addHidden("URL_REFERER", $GLOBALS['URL_REFERER']);
$f->addHidden("dataID", $strDataID);
$f->addHidden("dataCandidateID", $strDataCandidateID);
$f->addHidden("id_candidate", $arrData['id_candidate']);
$f->addHidden("status", $arrData['status']);
$f->addFieldSet(getWords("employee information"), 2);
$f->addInput(
    getWords("name"),
    "employee_name",
    $arrData['employee_name'],
    ["size" => 50],
    "string",
    false,
    true,
    true,
    "",
    "",
    true,
    ["width" => 120]
);
$f->addInput(getWords("employee id"), "employee_id", $arrData['employee_id'], ["size" => 10], "string", false);
$f->addInput(getWords("position"), "position_code", $arrData['position_code'], ["size" => 30], "string", false);
$f->addSelect(
    getWords("company"),
    "id_company",
    getDataListCompany($arrData['id_company'], true),
    [],
    "string",
    false,
    true,
    true
);
$f->addSelect(
    getWords("unit"),
    "section_code",
    getDataListSection($arrData['section_code'], true),
    [],
    "string",
    false,
    true,
    true
);
$f->addSelect(
    getWords("department"),
    "department_code",
    getDataListDepartment($arrData['department_code'], true),
    [],
    "string",
    false,
    true,
    true
);
$f->addSelect(
    getWords("division"),
    "division_code",
    getDataListDivision($arrData['division_code'], true),
    [],
    "string",
    false,
    true,
    true
);
$f->addSelect(
    getWords("employee status"),
    "employee_status",
    getDataListEmployeeStatus($arrData['employee_status'], true),
    [],
    "string",
    false,
    true,
    true
);
$arrContractMonth = [3, 6, 12];
$arrResult = [];
foreach ($arrContractMonth as $month) {
  if ($month == $arrData['contract_month']) {
    $arrResult[] = ["value" => $month, "text" => $month . " " . getWords("months"), "checked" => true];
  } else {
    $arrResult[] = ["value" => $month, "text" => $month . " " . getWords("months"), "checked" => false];
  }
}
$f->addRadio(getWords("masa percobaan / kerja"), "contract_month", $arrResult, [], "string", false, true, true);
$f->addSelect(
    getWords("band"),
    "salary_grade_code",
    getDataListSalaryGrade($arrData['salary_grade_code'], true),
    [],
    "string",
    false
);
$f->addSelect(
    getWords("family status"),
    "family_status_code",
    getDataListFamilyStatus($arrData['family_status_code'], true, null, false),
    [],
    "string",
    false
);
$f->addInput(getWords("superior name"), "superior_name", $arrData['superior_name'], ["size" => 50], "string", false);
$f->addInput(getWords("join date"), "join_date", $arrData['join_date'], [], "date", false);
$f->addInput(
    getWords("salary adjustment date"),
    "adjustment_date",
    $arrData['adjustment_date'],
    [],
    "date",
    false,
    true,
    true,
    "",
    "<br>" . getWords("please fill if only there is negotiation on salary adjustment in the future")
);
$f->addFieldSet(getWords("bank information"), 1);
$f->addInput(
    getWords("bank account no."),
    "bank_account_no",
    $arrData['bank_account_no'],
    ["size" => 20],
    "string",
    false
);
$f->addInput(
    getWords("bank account name"),
    "bank_account_name",
    $arrData['bank_account_name'],
    ["size" => 20],
    "string",
    false
);
$f->addInput(getWords("branch name") . " (BCA) ", "bank", $arrData['bank'], ["size" => 20], "string", false);
if ($arrData['salary_grade_code'] == "" || isBandAccess($arrData['salary_grade_code'])) {
  $f->addFieldSet(getWords("salary information"), 1);
  $f->addLiteral(
      "",
      "dataDetailSalary",
      getDetailSalary($strDataID),
      false
  );//, array(), "integer", false, true, true, "", "year(s) old");
  $f->addFieldSet(getWords("note"), 1);
  $f->addTextArea(getWords("note"), "note", $arrData['note'], ["rows" => 6, "cols" => 120], "string", false);
}
//$f->addLiteral("", "", getSignature(), false);//, array(), "integer", false, true, true, "", "year(s) old");
$f->readOnlyForm();
//if ($strDataID !="")
//$f->addButton("btnPrint", "Print", array("onClick" => "javascript:location.href='candidate_print.php?dataID=$strDataID'"));
//$f->validateEntryBeforeSubmit=false;
$formInput = $f->render();
$strInitAction = "window.onload = window.print();";
$strDateTime = date("d-M-Y H:i:s");
$strSignatureStyle = "";
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords("form kesepakatan remunerasi") . " (FKR)";
$pageIcon = "../images/icons/blank.gif";
$strTemplateFile = "templates/fkr_edit.html";
//candidate user
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master_view.html");
$tbsPage->Show();
//--------------------------------------------------------------------------------
function getData($strDataID)
{
  $tblFKR = new cModel("hrd_fkr");
  if ($strDataID != "") {
    if ($rowDb = $tblFKR->findById($strDataID)) {
      return $rowDb;
    }
  }
  $arrData = $tblFKR->getEmptyRecord();
  return $arrData;
}

function getDataCandidate($strDataCandidateID)
{
  $tblFKR = new cModel("hrd_candidate", getWords("candidate"));
  if ($strDataCandidateID != "") {
    if ($arrData = $tblFKR->findById($strDataCandidateID)) {
      $arrData['id_candidate'] = $arrData['id'];
      $arrData['employee_name'] = $arrData['candidate_name'];
      $arrData['position_code'] = $arrData['position'];
      $arrData['section_code'] = '';
      $arrData['department_code'] = '';
      $arrData['division_code'] = '';
      $arrData['contract_month'] = 0;
      $arrData['employee_status'] = null;
      $arrData['employee_id'] = '';
      $arrData['salary_grade_code'] = '';
      $arrData['family_status_code'] = '';
      $arrData['superior_name'] = '';
      $arrData['join_date'] = '';
      $arrData['adjustment_date'] = '';
      $arrData['bank_account_no'] = '';
      $arrData['bank_account_name'] = '';
      $arrData['bank'] = '';
      $arrData['note'] = '';
      $arrData['status'] = 0;
      $arrData['id_company'] = '';
      return $arrData;
    }
  }
  return null;
}

function getDetailSalary($strDataID, $gradeCode = "")
{
  global $isView;
  $jumlahStart = 0;
  $jumlahNext = 0;
  $tblFKRDetail = new cModel("hrd_fkr_detail");
  $strResult = "
      <table class=\"dataGrid\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <th width=30>NO</th>
          <th width=250>" . strtoupper(getWords("salary") . " & " . getWords("allowances")) . "</th>
          <th nowrap>" . strtoupper(getWords("start amount")) . "</th>
          <th nowrap>" . strtoupper(getWords("adjustment amount")) . "</th>
        </tr>
        <tbody>";
  $counter = 0;
  $tblAllowanceType = new cModel("hrd_allowance_type");
  $arrAllowance = $tblAllowanceType->findAll(null, null, "id", null, null, "id");
  $tbl = new cModel;
  $strSQL = "SELECT grade_allowance1 FROM hrd_salary_grade ";
  $strSQL .= "WHERE grade_code = '$gradeCode' ";
  $resDb = $tbl->query($strSQL);
  foreach ($resDb as $loop) {
    $mount = $loop['grade_allowance1'];
  }
  // print_r($resDb);
  $arrAllowance[0]['name'] = "Basic Salary";
  $arrAllowance[0]['amount'] = $mount;
  // print_r($arrAllowance);
  if ($strDataID != "") {
    $arrResult = $tblFKRDetail->findAllByIdFkr($strDataID, null, "id", null, null, "id_allowance_type");
  }
  foreach ($arrAllowance as $idAllowance => $allowance) {
    $counter++;
    if (!isset($arrResult[$idAllowance])) {
      $row = [
          "id" => "",
          "amount_start" => $allowance['amount'],
          "amount_next" => $allowance['amount']
      ];
    } else {
      $row = $arrResult[$idAllowance];
    }
    if ($isView) {
      $strResult .= "
        <tr>
          <td align=center>" . $counter . ".</td>
          <td nowrap>" . $allowance['name'] . "</td>
          <td align=right>" . number_format($row['amount_start']) . "</td>
          <td align=right>" . number_format($row['amount_next']) . "</td>
        </tr>";
    } else {
      $strResult .= "
        <tr>
          <td align=center>" . $counter . ".</td>
          <td nowrap>" .
          generateHidden("id" . $counter, $row['id']) .
          generateHidden("deleted" . $counter, 0) .
          generateHidden("id_allowance_type" . $counter, $idAllowance) .
          ucwords(strtolower($allowance['name'])) . "
          </td>
          <td nowrap>" . generateInput(
              "amount_start" . $counter,
              $row['amount_start'],
              "class=\"numberformat numeric\" style='width:100%'"
          ) . "</td>
          <td nowrap>" . generateInput(
              "amount_next" . $counter,
              $row['amount_next'],
              "class=\"numberformat numeric\" style='width:100%'"
          ) . "</td>
        </tr>";
    }
    $jumlahStart += $row['amount_start'];
    $jumlahNext += $row['amount_next'];
  }
  //$jumlah = $jumlahStart + $jumlahNext ;
  // tambahkan total
  $strResult .= "
        <tr >
           <td nowrap colspan=2 bgcolor= \"#c8c5c6\" align=center>Total</td>
          <td nowrap bgcolor=\"#c8c5c6\">" . generateInput(
          "amount_start_total",
          $jumlahStart,
          "class=\"numberformat numeric\" style='width:100%'"
      ) . "</td>
          <td nowrap bgcolor=\"#c8c5c6\">" . generateInput(
          "amount_next_total",
          $jumlahNext,
          "class=\"numberformat numeric\" style='width:100%'"
      ) . "</td>
        </tr>";
  $strResult .= "
        </tbody>
      </table>" .
      generateHidden("hNumShowDetail", $counter);
  return $strResult;
}

?>