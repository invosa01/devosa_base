<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/form_document.php');
include_once('../includes/form2/form2.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$strConfirmSave = getWords("do you want to save this entry?");
$strWordsDataEntry = getWords("data entry");
$strWordsMRFList = getWords("list of mrf");
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
  getDataAJAX();
}
$strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
$isNew = ($strDataID == "");
$tblMRF = new cModel("hrd_recruitment_need", getWords("manpower requisition form"));
$arrData = getData($strDataID);
$f = new clsForm("formInput", 2, "100%", "100%");
$f->bolRequiredEntryBeforeSubmit = false;
$f->caption = strtoupper(vsprintf(getWords("input data %s"), "MRF"));
$f->message = getGetValue('message');
$f->action = basename($_SERVER['PHP_SELF']);
//$f->addHelp(getWords("help for")." ".$dataPrivilege['menu_name'], getHelps("master user"), 8, 167, 400, 300);
$f->addHidden("dataID", $strDataID);
//$f->addTabPage(getWords("personal information"));
$emptyData = ["value" => "", "text" => ""];
$f->addFieldSet(getWords("form information"), 2);
$f->addInput(
    "MRF No.",
    "request_number",
    $arrData['request_number'],
    ["size" => 20],
    "string",
    true,
    true,
    true,
    "",
    "",
    true,
    ["width" => 120]
);
//bisa ubah tanggal
$f->addInput(getWords("request date"), "recruitment_date", $arrData['recruitment_date'], [], "date", true, true, true);
//$f->addInput(getWords("user name"), "user_name", $arrData['user_name'], array("size"=> 50), "", true, true, true, "", "", true, array("width" => 120));
$f->addFieldSet(getWords("position information"), 2);
//$f->addInput(getWords("position"), "position", $arrData['position'], array("size"=> 50), "", true, true, true, "", "", true, array("width" => 120));
//$f->addSelect(getWords("company"), "id_company", getDataListCompany($arrData['id_company'], true, $emptyData, $objUP->genFilterCompany(1)), array("style" =>"width:250"), "string", false, true, true);
$f->addSelect(
    getWords("branch"),
    "branch",
    getDataListBranch($arrData['branch_code'], true, $emptyData),
    ["style" => "width:$strDefaultWidthPx"],
    "string",
    false,
    true,
    true,
    "",
    "",
    true,
    ["width" => 120]
);
$f->addSelect(
    getWords("band / grade"),
    "grade_code",
    getDataListSalaryGrade($arrData['grade_code'], true, $emptyData),
    ["style" => "width:$strDefaultWidthPx"],
    "string",
    false,
    true,
    true,
    "",
    "",
    true,
    ["width" => 120]
);
$f->addSelect(
    getWords("level"),
    "position",
    getDataListPosition($arrData['position_code'], true, $emptyData),
    ["style" => "width:$strDefaultWidthPx"],
    "string",
    false,
    true,
    true,
    "",
    "",
    true,
    ["width" => 120]
);
$f->addSelect(
    getWords("status"),
    "employee_status",
    getDataListEmployeeStatus(
        $arrData['employee_status'],
        true,
        ["value" => "", "text" => "", "selected" => true]
    ),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false
);
$f->addSelect(
    getWords("sex"),
    "gender",
    getDataListGender($arrData['gender'], true),
    ["style" => "width:$strDefaultWidthPx"],
    "string",
    false,
    true,
    true
);
// per 11 Nov, gunakan lagi combo, biar standard
//$f->addSelect(getWords("department"), "department_code", getDataListDepartment($arrData['department_code'], true, $emptyData, $objUP->genFilterDepartment()), array("onChange" => "javascript:myClient.doChangeDepartment(this.value)", "style" =>"width:250"), "string", true, true, true);
//$f->addInput(getWords("department"), "department_code", $arrData['department_code'], array("size"=> 50), "", true, true, true, "", "", true, array("width" => 120));
$f->addSelect(
    getWords("company"),
    "id_company",
    getDataListCompany($arrData['id_company'], $bolCompanyEmptyOption, $arrCompanyEmptyData, $strKriteria2),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false
);
$f->addSelect(
    getWords("division"),
    "division_code",
    getDataListDivision($arrData['division_code'], true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['division'] == "")
);
$f->addSelect(
    getWords("department"),
    "department_code",
    getDataListDepartment($arrData['department_code'], true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['department'] == "")
);
$f->addSelect(
    getWords("section"),
    "section_code",
    getDataListSection($arrData['section_code'], true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['section'] == "")
);
$f->addSelect(
    getWords("sub section"),
    "sub_section_code",
    getDataListSubSection($arrData['sub_section_code'], true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['sub_section'] == "")
);
$f->addFieldSet(getWords("required information"), 2);
$f->addInput(
    getWords("number required"),
    "number",
    $arrData['number'],
    ["size" => 50],
    "integer",
    true,
    true,
    true,
    "",
    "",
    true,
    ["width" => 120]
);
$f->addInput(getWords("date required"), "due_date", $arrData['due_date'], [], "date", false, true, true);
$f->addHidden("actual_number", $arrData['actual_number']);
$f->addLabel(getWords("total number of employees"), "label_actual_number", $arrData['actual_number']);
//$f->addFieldSet(getWords("request information"), 2);
$f->addRadio(
    getWords("type of request"),
    "request_type",
    getDataCheckBoxMRFRequestType($arrData['request_type']),
    [],
    "string",
    false,
    true,
    true,
    "",
    "",
    true,
    ["width" => 120]
);
//$f->addSelect(getWords("employee status"), "employee_status", getDataListEmployeeStatus($arrData['employee_status'], true, $emptyData), array(), "string", true, true, true);
/*$arrStatus = array(
  0 => array("text" => getWords("contract"), "value" => "0"),
  1 => array("text" => getWords("permanent"), "value" => "1")
);
if ($arrData['employee_status'] == 0) $arrStatus[0]['selected'] = 'true';
else $arrStatus[1]['selected'] = 'true';*/
//$f->addSelect(getWords("employee status"), "employee_status", getDataListEmployeeStatus($arrData['employee_status'], true, $emptyData), array(), "string", true, true, true);
$f->addRadio(
    getWords("budget type"),
    "budget_type",
    getDataCheckBoxMRFBudgetType($arrData['budget_type']),
    [],
    "string",
    true,
    true,
    true
);
$f->addFieldSet(getWords("other information"), 1);
$f->addTextArea(
    getWords("reason"),
    "reason",
    $arrData['reason'],
    ["rows" => 3, "cols" => 48],
    "string",
    false,
    true,
    true
);
$f->addTextArea(
    getWords("job descriptions"),
    "description",
    $arrData['description'],
    ["rows" => 3, "cols" => 48],
    "string",
    false,
    true,
    true,
    "",
    "",
    true,
    ["width" => 120]
);
$f->addTextArea(
    getWords("job qualifications"),
    "qualification",
    $arrData['qualification'],
    ["rows" => 3, "cols" => 48],
    "string",
    false,
    true,
    true
);
$f->addFieldSet(getWords("remuneration information"), 2);
$f->addInput(
    getWords("monthly salary"),
    "monthly_salary",
    $arrData['monthly_salary'],
    [],
    "numeric",
    false,
    true,
    true
);
$f->addInput(getWords("allowance"), "allowance", $arrData['allowance'], ["size" => 50], "string", false, true, true);
$f->addFieldSet(getWords("document status"));
$f->addLabel(getWords("status"), "status", printStatus($arrData['status']));
if ($bolCanEdit) {
  if ($arrData['status'] < REQUEST_STATUS_APPROVED) {
    $f->addSubmit(
        "btnSave",
        getWords("save"),
        ["onClick" => "return myClient.confirmSave();"],
        true,
        true,
        "",
        "",
        "saveData()"
    );
  } else {
    $f->addSubmit(
        "btnSave",
        getWords("save"),
        ["disabled" => "disabled", "style" => "color:gray"],
        true,
        true,
        "",
        "",
        "saveData()"
    );
  }
}
// $f->addButton("btnBack", "Back", array("onClick" => "javascript:location.href='candidate_list.php'"));
if ($strDataID != "") {
  $f->addButton(
      "btnPrint",
      "Print",
      ["onClick" => "javascript:openWindowDialog('recruitment_print.php?dataID=" . $strDataID . "')"]
  );
}
$f->addButton("btnAddNew", getWords("add new"), ["onClick" => "location.href = 'recruitment_edit.php';"]);
//$f->validateEntryBeforeSubmit=false;
$formInput = $f->render();
$tbsPage = new clsTinyButStrong;
//write this variable in every page
if ($isNew) {
  $strPageTitle = getWords("add new candidate");
} else {
  $strPageTitle = getWords("edit candidate");
}
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$strPageTitle = getWords($dataPrivilege['menu_name']);
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
function getData($strDataID)
{
  global $tblMRF;
  if ($strDataID != "") {
    if ($rowDb = $tblMRF->findById($strDataID)) {
      return $rowDb;
    }
  }
  $arrData = $tblMRF->getEmptyRecord();
  $arrNumber = explode("|", getDocumentNumberMRF(date("Y-m-d")));
  $arrData["request_number_seq"] = intval($arrNumber[0]);
  $arrData["request_number"] = $arrNumber[1];
  $arrData['recruitment_date'] = date("Y-m-d");
  $arrData['number'] = 1; // default
  //if ($arrData['user_name'] == "") $arrData['user_name'] = $_SESSION['sessionUserName'];
  if ($_SESSION['sessionIdCompany'] != -1) {
    $arrData['id_company'] = $_SESSION['sessionIdCompany'];
  }
  return $arrData;
}

function printStatus($intStatus)
{
  global $ARRAY_CANDIDATE_STATUS;
  if (isset($ARRAY_CANDIDATE_STATUS[$intStatus])) {
    return $ARRAY_CANDIDATE_STATUS[$intStatus];
  } else {
    return "";
  }
}

function saveData()
{
  global $f;
  global $tblMRF;
  // simpan data -----------------------
  $data = $_POST;
  $tblMRF->begin();
  $isSuccess = false;
  if ($f->getValue('dataID') == "") {
    // data baru
    $arrNumber = explode("|", getDocumentNumberMRF($data['recruitment_date']));
    $data["request_number_seq"] = intval($arrNumber[0]);
    $data["request_number"] = $arrNumber[1];
    $data['status'] = 0;
    if ($isSuccess = $tblMRF->insert($data)) {
      $f->setValue('dataID', $tblMRF->getLastInsertId('id'));
    }
  } else {
    $isSuccess = $tblMRF->update(["id" => $f->getValue('dataID')], $data);
  }
  if ($isSuccess) {
    $f->message = $tblMRF->strMessage;
    $tblMRF->commit();
    header("location: recruitment_edit.php?dataID=" . $f->getValue('dataID') . "&message=" . $f->message);
    exit();
  } else {
    $f->errorMessage = getWords("failed to save data ") . " " . $tblMRF->strEntityName . " ";
    //$f->errorMessage .= getWords("on personal information");
    $tblMRF->rollback();
    return false;
  }
} // saveData
function getDocumentNumberMRF($dtTransDate)
{
  return implode(
      "|",
      getFormDocumentNumber(
          $dtTransDate,
          "hrd_recruitment_need",
          "recruitment_date",
          "request_number_seq",
          "formattingDocumentMRF"
      )
  ); // form_document.php
}

function getDataAJAX()
{
  $strAction = getGetValue("action");
  switch ($strAction) {
    case "changeDepartment" :
      $strDepartmentCode = getGetValue('department_code');
      $tbl = new cModel("hrd_employee");
      echo $tbl->findCount("department_code = '" . $strDepartmentCode . "'");
      break;
  }
  exit();
}

?>