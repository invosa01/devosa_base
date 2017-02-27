<?php
include_once('../global/session.php');
include_once('global.php');
//include_once('../includes/datagrid/datagrid.php');
include_once('../classes/datagrid_modified.php');
include_once('../includes/form2/form2.php');
include_once('../global/common_data.php');
$dataPrivilege = getDataPrivileges("recruitment_edit.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
  die(getWords('view denied'));
}
$strWordsDataEntry = getWords("data entry");
$strWordsMRFList = getWords("list of mrf");
$strWordsNew = getWords("new");
$strWordsDenied = getWords("denied");
$strWordsChecked = getWords("checked");
$strWordsApproved = getWords("approved");
$strWordsFinished = getWords("finished");
$strWordsVerified = getWords("verified");
$strReportName = getWords("manpower requisition form");
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);//ambil semua info user]
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  $strReadonly = (scopeGeneralDataEntry(
      $arrData['dataEmployee'],
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  )) ? "readonly" : "";
}
$f = new clsForm("form1", 3, "100%", "100%");
$f->disableFormTag();
$f->caption = strtoupper($strWordsFILTERDATA);
$f->addHidden("dataID", $strDataID);
//$f->addFieldSet(getWords("search criteria"), 1);
$f->addSelect(getWords("year"), "year", getDataListYear(date("Y"), true), [], "string", false);
//$f->addSelect(getWords("company"), "id_company", getDataListCompany(null, true, null, $objUP->genFilterCompany(1)), array(), "string", false);
$f->addSelect(
    getWords("branch"),
    "dataBranch",
    getDataListBranch(getInitialValue("Branch"), true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false
);
$f->addSelect(
    getWords("level"),
    "dataPosition",
    getDataListPosition(getInitialValue("Position"), true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false
);
$f->addSelect(
    getWords("grade"),
    "dataGrade",
    getDataListSalaryGrade(getInitialValue("Grade"), true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false
);
$f->addSelect(
    getWords("status"),
    "dataEmployeeStatus",
    getDataListRequestStatus(
        getInitialValue("EmployeeStatus"),
        true,
        ["value" => "", "text" => "", "selected" => true]
    ),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false
);
$f->addSelect(
    getWords("company"),
    "id_company",
    getDataListCompany($strDataCompany, $bolCompanyEmptyOption, $arrCompanyEmptyData, $strKriteria2),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false
);
$f->addSelect(
    getWords("division"),
    "division_code",
    getDataListDivision($strDataDivision, true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['division'] == "")
);
$f->addSelect(
    getWords("department"),
    "department_code",
    getDataListDepartment($strDataDepartment, true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['department'] == "")
);
$f->addSelect(
    getWords("section"),
    "section_code",
    getDataListDepartment($strDataSection, true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['department'] == "")
);
$f->addSelect(
    getWords("sub section"),
    "sub_section_code",
    getDataListDepartment($strDataSubSection, true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['department'] == "")
);
$f->addSubmit("btnSearch", getWords("show data"), ["onClick" => "javascript:doSearch()"], true, true, "", "", "");
//$f->addSubmit("btnPrint", getWords("print"), array("onClick" => "javascript:printList()"), true, true, "", "", "");
$f->addSubmit("btnExportXLS", getWords("excel"), ["onClick" => "javascript:exportExcel()"], true, true, "", "", "");
$formInput = $f->render();
$bolPrint = false;
$bolExcel = false;
if (isset($_POST['btnPrint'])) {
  $bolPrint = true;
}
if (isset($_POST['btnExportXLS'])) {
  $bolExcel = true;
}

class cDataGrid2 extends cDataGridNew
{

  /*override this function*/
  function _printGridButtons()
  {
    global $bolCanEdit;
    $strResult = "";
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
      $colSpan = count($this->columnSet);
      if ($this->hasCheckbox && (count($this->dataset) > 0)) //have checkbox
      {
        $strResult .= "
              <!-- grid footer -->
              <tfoot>
              <tr>
                <td align=\"center\">" . $this->_printCheckboxAllBottom() . "</td>
                <td colspan=12>";
      } else //don't have checkbox
      {
        $strResult .= "
              <!-- grid footer -->
              <tfoot>
              <tr>
                <td colspan=13>";
      }
      $counter = 0;
      if (count($this->buttons) > 0) {
        foreach ($this->buttons as $button) {
          if ($button['special'] && (count($this->dataset) == 0)) {
            continue;
          }
          $counter++;
          if ($button['class'] == "") {
            $className = "";
          } else {
            $className = "class=\"" . $button['class'] . "\"";
          }
          $strResult .= "
                <input " . $className . " name=\"" . $button['name'] . "\" type=\"" . $button['type'] . "\" id=\"" . $button['id'] . "\" value=\"" . $button['value'] . "\" " . $button['clientAction'] . ">&nbsp;";
        }
      }
      if ($counter == 0) {
        return "";
      }
      $strResult .= "&nbsp;</td>
                <td nowrap=nowrap>";
      $strButtons = "";
      /*
      if ($_SESSION['sessionUserRole'] == ROLE_ADMIN || $_SESSION['sessionUserRole'] == ROLE_MANAGER || $_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_DIRECTOR)
      {
        $strButtons .= "<input type=submit name=btnRecommend value=\"" .getWords('recommend'). "\" onClick=\"return confirmStatusChanges(false)\">";
        $strButtons .= "&nbsp;<input type=submit name=btnSkip value=\"" .getWords('skip'). "\" onClick=\"return confirmStatusChanges(false)\">";
        $strButtons .= "&nbsp;<input type=submit name=btnCancel value=\"" .getWords('clear status'). "\" onClick=\"return confirmStatusChanges(false)\">";
      }
      */
      $strResult .= $strButtons . "&nbsp;</td>";
      if ($bolCanEdit) {
        $strResult .= "<td colSpan=2>&nbsp;</td>";
      }
      $strResult .= "
              </tr>
              </tfoot>
              <!-- end of grid footer -->";
    }
    return $strResult;
  }

  /*override this function*/

  function printOpeningRow($intRows, $rowDb)
  {
    $strResult = "";
    $strClass = getCssClass($rowDb['status_flag']);
    if ($strClass != "") {
      $strClass = "class=\"" . $strClass . "\"";
    }
    $strResult .= "
            <tr $strClass valign=\"top\">";
    return $strResult;
  }
}

$myDataGrid = new cDataGrid2("form1", "DataGrid1");
$myDataGrid->disableFormTag();
$myDataGrid->caption = $strReportName;//getWords("recruitment process");
//$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
//$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", array("rowspan" => 2, 'width'=>30), array('nowrap'=>'')));
if ($bolPrint || $bolExcel) {
  $myDataGrid->addColumn(
      new DataGrid_Column(
          strtoupper(getWords("mrf number")),
          "request_number",
          ["rowspan" => 2, 'width' => 150],
          ['nowrap' => 'nowrap'],
          true,
          true,
          "",
          "",
          "string",
          true,
          16
      )
  );
} else {
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "id", ["rowspan" => 2, 'width' => 30], ['align' => 'center', 'nowrap' => 'nowrap'])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          strtoupper(getWords("no.")),
          "request_number",
          ["rowspan" => 2, 'width' => 150],
          ['nowrap' => 'nowrap'],
          true,
          true,
          "",
          "",
          "string",
          true,
          16
      )
  );
}
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("date"),
        "recruitment_date",
        ["rowspan" => 2, "width" => 80],
        ["align" => "center"],
        true,
        true,
        "",
        "",
        "date",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("company"),
        "id_company",
        ["rowspan" => 2, "width" => 120],
        ["nowrap" => "nowrap"],
        true,
        false,
        "",
        "",
        "string",
        true,
        16
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("department"),
        "department_code",
        ["rowspan" => 2, "width" => 120],
        ["nowrap" => "nowrap"],
        true,
        true,
        "",
        "",
        "string",
        true,
        16
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("position profile"), "", ["colspan" => 5], [], false, false, "", "", "string", true, 12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("level"), "position_code", ["width" => 70], [], true, true, "", "", "string", true, 16)
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("employee status"),
        "employee_status",
        ["width" => 80],
        [],
        true,
        true,
        "",
        "",
        "string",
        true,
        16
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("required"),
        "number",
        ["width" => 50],
        ["align" => "center"],
        true,
        true,
        "",
        "",
        "integer",
        true,
        10
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("due date"),
        "due_date",
        ["width" => 90],
        ["align" => "center"],
        true,
        true,
        "",
        "",
        "date",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("job description"),
        "description",
        ["width" => 150],
        [],
        true,
        true,
        "",
        "",
        "string",
        true,
        40
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("qualification"), "", ["colspan" => 2], [], false, false, "", "", "string", true, 12)
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("gender"), "gender", ["width" => 70], [], true, true, "", "", "string", true, 16)
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("job qualification"),
        "qualification",
        ["width" => 150],
        [],
        true,
        true,
        "",
        "",
        "string",
        true,
        40
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("status"),
        "status",
        ["rowspan" => 2, "width" => 80],
        ["nowrap" => "nowrap"],
        true,
        true,
        "",
        "",
        "date",
        true,
        12
    )
);
// $myDataGrid->addColumn(new DataGrid_Column(getWords("#"), "number_ok", array("rowspan" => 2), array("nowrap" => "nowrap"), false, false, "", "", "string", true, 30));
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("candidate"),
        "candidate",
        ["rowspan" => 2],
        ["nowrap" => "nowrap"],
        false,
        false,
        "",
        "",
        "string",
        true,
        30
    )
);
if (!($bolPrint || $bolExcel)) {
  if ($bolCanEdit) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "",
            "",
            ["rowspan" => 2, 'width' => 45],
            ['align' => 'center', 'nowrap' => 'nowrap'],
            false,
            false,
            "",
            "printEditLink()",
            "string",
            false
        )
    );
  }
  $myDataGrid->addColumn(
      new DataGrid_Column(
          "",
          "",
          ["rowspan" => 2],
          ['align' => 'center', 'nowrap' => 'nowrap'],
          false,
          false,
          "",
          "printLink()",
          "string",
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          "",
          "",
          ["rowspan" => 2],
          ['align' => 'center', 'nowrap' => 'nowrap'],
          false,
          false,
          "",
          "printProcessLink()",
          "string",
          false
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          "",
          "",
          ["rowspan" => 2],
          ['align' => 'center', 'nowrap' => 'nowrap'],
          false,
          false,
          "",
          "printShowLink()",
          "string",
          false
      )
  );
}
generateRoleButtons2(
    $dataPrivilege['edit'],
    $dataPrivilege['delete'],
    $dataPrivilege['check'],
    $dataPrivilege['approve'],
    true,
    true,
    $myDataGrid
);
if ($bolCanDelete) {
  $myDataGrid->addSpecialButton(
      "btnDelete",
      "btnDelete",
      "submit",
      getWords("delete"),
      "onClick=\"javascript:return myClient.confirmDelete();\" ",
      "deleteData()"
  );
}
$myDataGrid->getRequest();
/*
if (isset($_POST['btnRecommend']))
  changeStatusCandidate(1);
else if (isset($_POST['btnSkip']))
  changeStatusCandidate(2);
else if (isset($_POST['btnCancel']))
  changeStatusCandidate(0);
*/
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strCriteria = "";
$tblRecruitmentNeed = new cModel("hrd_recruitment_need", "MRF");
if ($bolExcel) {
  $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
  $myDataGrid->strFileNameXLS = "mrf_list.xls";
  $myDataGrid->strTitle1 = $strReportName; //"List of Manpower Requisition Form";
  $myDataGrid->strTitle2 = "Printed Date: " . date("d/m/Y h:i:s");
} elseif ($bolPrint) {
  $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_PRINT_HTML;
  $myDataGrid->strTitle1 = $strReportName; //"List of Manpower Requisition Form";
  $myDataGrid->strTitle2 = "Printed Date: " . date("d/m/Y h:i:s");
}
$strCriteriaFlag = $myDataGrid->getCriteria();
$arrCriteria = [];
if ($f->getValue("year") != "" && $f->getValue(
        "year"
    ) != '0'
) //$arrCriteria[] = "( EXTRACT (year FROM recruitment_date) = '".$f->getValue("year")."' OR EXTRACT (year FROM due_date) = '".$f->getValue("year")."') ";
{
  $arrCriteria[] = "EXTRACT (year FROM recruitment_date) = '" . $f->getValue("year") . "' ";
}
if ($f->getValue("dataBranch") != "") {
  $arrCriteria[] = "branch_code = '" . $f->getValue("dataBranch") . "' ";
}
if ($f->getValue("dataPosition") != "") {
  $arrCriteria[] = "position_code = '" . $f->getValue("dataPosition") . "' ";
}
if ($f->getValue("dataGrade") != "") {
  $arrCriteria[] = "grade_code = '" . $f->getValue("dataGrade") . "' ";
}
if ($f->getValue("dataEmployeeStatus") != "") {
  $arrCriteria[] = "employee_status = '" . $f->getValue("dataEmployeeStatus") . "' ";
}
if ($f->getValue("id_company") != "") {
  $arrCriteria[] = "id_company = " . $f->getValue("id_company");
}
if ($f->getValue("department_code") != "") {
  $arrCriteria[] = "department_code = '" . $f->getValue("department_code") . "'";
}
if (count($arrCriteria) > 0) {
  $strCriteriaFlag .= " AND (" . implode(" AND ", $arrCriteria) . ")";
}
// tambahkan kriteria sesuai hak akses user
/*
  $strCriteriaFlag .= $objUP->genFilterCompany(0);
  if ($objUP->isUserEmployee())
  {
    $strCriteriaFlag .= "
      AND department_code IN (SELECT department_code FROM hrd_department WHERE 1=1 " .$objUP->genFilterDepartment(). ")
    ";
  }
  */
$myDataGrid->totalData = $tblRecruitmentNeed->findCount($strCriteriaFlag);
$dataset = $tblRecruitmentNeed->findAll(
    $strCriteriaFlag,
    "*",
    $myDataGrid->getSortBy(),
    $myDataGrid->getPageLimit(),
    $myDataGrid->getPageNumber()
);
$tblDepartment = new cModel("hrd_department", "department");
$arrDepartment = $tblDepartment->findAll(null, "department_code, department_name", null, null, null, "department_code");
$tblCompany = new cModel("hrd_company", "company");
$arrCompany = $tblCompany->findAll(null, "id, company_name", null, null, null, "id");
// cari dulu data status per proses
foreach ($dataset as &$row) {
  if (isset($arrDepartment[$row['department_code']])) {
    $row['department_code'] = $row['department_code'] . " - " . $arrDepartment[$row['department_code']]['department_name'];
  }
  if (isset($arrCompany[$row['id_company']])) {
    $row['id_company'] = $arrCompany[$row['id_company']]['company_name'];
  } else {
    $row['id_company'] = "";
  }
  if (isset($row['employee_status'])) //$row['employee_status'] = getWords($ARRAY_EMPLOYEE_STATUS[$row['employee_status']]);
  {
    $row['employee_status'] = ($row['employee_status'] == 1) ? getWords("permanent") : getWords("contract");
  } else {
    $row['employee_status'] = "";
  }
  if ($row['gender'] == 0) {
    $row['gender'] = "Female";
  } else {
    $row['gender'] = ($row['gender'] == FEMALE) ? getWords("female") : getWords("male");
  }
  $row['recruitment_date'] = pgDateFormat($row['recruitment_date'], "d-M-y");
  $row['due_date'] = ($row['due_date'] == "") ? "ASAP" : pgDateFormat($row['due_date'], "d-M-y");
  /*
  // ganti ambil dari FKR
  $strSQL  = "
    SELECT tf.*
    FROM hrd_fkr as tf
    WHERE tf.id_recruitment_need = ".$row['id']."
  ";
  */
  // ganti, ambil dari data candidate
  $strBreakLine = ($bolExcel) ? "\n\r" : "<br>\n";
  $strSQL = "
      SELECT tc.*, tf.join_date, tf.id as id_fkr
      FROM hrd_candidate as tc
      LEFT JOIN hrd_fkr AS tf ON tc.id = tf.id_candidate
      WHERE tc.id_recruitment_need = '" . $row['id'] . "'
    ";
  $arrData = $tblRecruitmentNeed->query($strSQL);
  $intCan = 0;
  $strCandidateList = "";
  foreach ($arrData as $data) {
    $intCan++;
    if ($strCandidateList != "") {
      $strCandidateList .= $strBreakLine;
    }
    if ($bolExcel || $bolPrint) {
      $strCandidateList .= $data['candidate_name'];
    } else {
      $strCandidateList .= "<a href=\"candidate_print.php?dataFull=true&dataID=" . $data['id'] . "\" target='_blank'>" . $data['candidate_name'] . "</a>";
    }
    if ($data['join_date'] != "") {
      $strCandidateList .= " " . getWords("start") . " : " . pgDateFormat($data['join_date'], "d-M-y");
    }
  }
  $row['candidate'] = $strCandidateList;
  $row['status_flag'] = $row['status'];
  if (isset($ARRAY_REQUEST_STATUS[$row['status']])) {
    $row['status'] = getWords($ARRAY_REQUEST_STATUS[$row['status']]);
  } else {
    $row['status'] = "";
  }
}
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$strConfirmDelete = getWords("are you sure to delete this selected data?");
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('recruitment data list');
$pageHeader = pageHeader($pageIcon, "Manpower Requisition List", $strPageDesc);
$pageSubMenu = recruitmentSubMenu($strWordsMRFList);
//$strTemplateFile = getTemplate();
//------------------------------------------------
$strTemplateFile = getTemplate("recruitment_list.html");
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
//$tbsPage->LoadTemplate($strTemplateFile) ;
$tbsPage->Show();
//Load Master Template
//$tbsPage->LoadTemplate("../templates/master2.html") ;
//$tbsPage->Show() ;
//--------------------------------------------------------------------------------
function printEditLink($params)
{
  extract($params);
  return "<a href=\"recruitment_edit.php?dataID=" . $record['id'] . "\">" . getWords('edit') . "</a>";
}

// untuk menampilkan info yang mengubah data MRF
function printShowLink($params)
{
  extract($params);
  global $arrUserList;
  $strResult = "";
  // tambahkan info record info
  $strDiv = "<div id='detailRecord$counter' style=\"display:none\">\n";
  $strDiv .= "<strong>" . $record['position_code'] . "-" . $record['department_code'] . "</strong><br>\n";
  $strDiv .= getWords("last modified") . ": " . substr($record['created'], 0, 19) . " ";
  $strDiv .= (isset($arrUserList[$record['created_by']])) ? $arrUserList[$record['created_by']]['name'] . "<br>" : "<br>";
  $strDiv .= getWords("verified") . ": " . substr($record['verified_time'], 0, 19) . " ";
  $strDiv .= (isset($arrUserList[$record['verified_by']])) ? $arrUserList[$record['verified_by']]['name'] . "<br>" : "<br>";
  $strDiv .= getWords("checked") . ": " . substr($record['checked_time'], 0, 19) . " ";
  $strDiv .= (isset($arrUserList[$record['checked_by']])) ? $arrUserList[$record['checked_by']]['name'] . "<br>" : "<br>";
  $strDiv .= getWords("approved") . ": " . substr($record['approved_time'], 0, 19) . " ";
  $strDiv .= (isset($arrUserList[$record['approved_by']])) ? $arrUserList[$record['approved_by']]['name'] . "<br>" : "<br>";
  // $strDiv .= getWords("approved by director"). ": ".substr($record['dir_approval_time'], 0,19) ." ";
  //$strDiv .= (isset($arrUserList[$record['dir_approval_by']])) ? $arrUserList[$record['dir_approval_by']]['name']."<br>" : "<br>";
  $strDiv .= getWords("denied") . ": " . substr($record['denied_time'], 0, 19) . " ";
  $strDiv .= (isset($arrUserList[$record['denied_by']])) ? $arrUserList[$record['denied_by']]['name'] . "<br>" : "<br>";
  $strDiv .= "</div>\n";
  $strResult .= $strDiv . "<a href=\"javascript:openViewWindowByContentId('Record Information', 'detailRecord$counter', 400, 150)\" title=\"" . getWords(
          "show record info"
      ) . "\">" . getWords("show") . "</a>";
  return $strResult;
}

// untuk menampilkan link untuk print MRF
function printLink($params)
{
  extract($params);
  $strResult = "<a href=\"javascript:openWindowDialog('recruitment_print.php?dataID=" . $record['id'] . "');\">" . getWords(
          "print"
      ) . "</a>";
  return $strResult;
}

// untuk menampilkan link untuk print daftar proses yang sudah dilakukan
function printProcessLink($params)
{
  extract($params);
  $strResult = "<a href=\"recruitment_process_list.php?mrf_no=" . $record['request_number'] . "&date_from=" . $record['recruitment_date'] . "\" target='_blank'>" . getWords(
          "recruitment process"
      ) . "</a>";
  return $strResult;
}

// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $tbl = new cModel("hrd_recruitment_need");
  if ($tbl->deleteMultiple($arrKeys)) {
    $myDataGrid->message = $tbl->strMessage;
  } else {
    $myDataGrid->errorMessage = $tbl->strMessage;
  }
} //deleteData
// function callChangeStatus() {
//   global $_REQUEST;
//   global $db;
//   if (isset($_REQUEST['btnVerified'])) $intStatus = REQUEST_STATUS_VERIFIED;
//   else if (isset($_REQUEST['btnChecked'])) $intStatus = REQUEST_STATUS_CHECKED;
//   else if (isset($_REQUEST['btnApproved'])) $intStatus = REQUEST_STATUS_APPROVED;
//   else if (isset($_REQUEST['btnDenied'])) $intStatus = REQUEST_STATUS_DENIED;
//   else if (isset($_REQUEST['btnPaid'])) $intStatus = REQUEST_STATUS_PAID;
//   changeStatus($db, $intStatus);
// }
function changeStatus($db, $intStatus)
{
  global $myDataGrid;
  if (!is_numeric($intStatus)) {
    return false;
  }
  // tambahan info
  if ($intStatus == REQUEST_STATUS_VERIFIED) {
    $strUpdate = "verified_by = '" . $_SESSION['sessionUserID'] . "', verified_time = now(), ";
  } else if ($intStatus == REQUEST_STATUS_CHECKED) {
    $strUpdate = "checked_by = '" . $_SESSION['sessionUserID'] . "', checked_time = now(), ";
  } else if ($intStatus == REQUEST_STATUS_APPROVED) {
    $strUpdate = "approved_by = '" . $_SESSION['sessionUserID'] . "', approved_time = now(), ";
  } else if ($intStatus == REQUEST_STATUS_DENIED) {
    $strUpdate = "denied_by = '" . $_SESSION['sessionUserID'] . "', denied_time = now(), ";
  } else if ($intStatus == REQUEST_STATUS_NEW) // unapprove
  {
    $strUpdate = "
        verified_by = null, verified_time = null,
        checked_by = null, checked_time = null,
        approved_by = null, approved_time = null,
        denied_by = null, denied_time = null,
      ";
  } else {
    $strUpdate = "";
  }
  $i = 0;
  $tbl = new cModel("hrd_recruitment_need");
  foreach ($myDataGrid->checkboxes as $strValue) {
    $i++;
    $strSQL = "UPDATE hrd_recruitment_need SET $strUpdate status = '$intStatus'  ";
    $strSQL .= "WHERE id = '" . $strValue . "' ";
    $tbl->execute($strSQL);
  }
  if ($i > 0) {
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data", 0);
  }
} //changeStatus
function changeStatusVerified()
{
  changeStatus(REQUEST_STATUS_VERIFIED);
}

function changeStatusChecked()
{
  changeStatus(REQUEST_STATUS_CHECKED);
}

function changeStatusApproved()
{
  changeStatus(REQUEST_STATUS_APPROVED);
}

// khusus approval oleh direksi
function changeStatusApprovedDirector()
{
  global $myDataGrid;
  global $objUP;
  if (!$objUP->isDirector()) {
    return false;
  }
  $i = 0;
  $tbl = new cModel("hrd_recruitment_need");
  foreach ($myDataGrid->checkboxes as $strValue) {
    $i++;
    $strSQL = "
        UPDATE hrd_recruitment_need
        SET dir_approval_by = '" . $_SESSION['sessionUserID'] . "', dir_approval_time = now()
        WHERE id = '" . $strValue . "'
      ";
    $tbl->execute($strSQL);
  }
  if ($i > 0) {
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data", 0);
  }
}

function changeStatusDenied()
{
  changeStatus(REQUEST_STATUS_DENIED);
}

function changeStatusUnApprove()
{
  changeStatus(REQUEST_STATUS_NEW);
}

function changeStatusFinished()
{
  changeStatus(REQUEST_STATUS_FINISHED);
}

function changeStatusCancel()
{
  changeStatus(REQUEST_STATUS_CANCEL);
}

function changeStatusCandidate($intStatus)
{
  if (!is_numeric($intStatus)) {
    return false;
  }
  $i = 0;
  $tbl = new cModel("hrd_candidate");
  foreach ($_POST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 12) == 'chkCandidate') {
      $i++;
      $strSQL = "UPDATE hrd_candidate SET status = '$intStatus'  ";
      $strSQL .= "WHERE id = '$strValue' "; // yang udah apprve gak boleh diedit
      $tbl->execute($strSQL);
    }
  }
  if ($i > 0) {
    writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$i data", 0);
  }
} //changeStatusCandidate
function generateRoleButtons2(
    $bolCanEdit,
    $bolCanDelete,
    $bolCanCheck,
    $bolCanApprove,
    $bolCanAcknowledge,
    $bolDatagridClass = false,
    &$objDatagrid = null
) {
  global $words;
  if ($bolDatagridClass) {
    include_once('../includes/datagrid2/datagrid.php');
    if ($bolCanDelete != 'f') {
      $objDatagrid->addSpecialButton(
          "btnDelete",
          "btnDelete",
          "submit",
          $words['delete'],
          "onClick=\"javascript:return myClient.confirmDelete();\"",
          "deleteData()"
      );
    }
    if ($bolCanCheck != 'f') {
      $objDatagrid->addSpecialButton(
          "btnChecked",
          "btnChecked",
          "submit",
          getWords('checked'),
          "onClick=\"javascript:return myClient.confirmChangeStatus();\"",
          "callChangeStatus()"
      );
      //$objDatagrid->addSpecialButton("btnDenied", "btnDenied", "submit", $words['denied'], "onClick=\"javascript:return myClient.confirmChangeStatus();\"", "callChangeStatus()");
    }
    if ($bolCanApprove != 'f') {
      $objDatagrid->addSpecialButton(
          "btnApproved",
          "btnApproved",
          "submit",
          $words['approved'],
          "onClick=\"javascript:return myClient.confirmChangeStatus();\"",
          "callChangeStatus()"
      );
      $objDatagrid->addSpecialButton(
          "btnDenied",
          "btnDenied",
          "submit",
          $words['denied'],
          "onClick=\"javascript:return myClient.confirmChangeStatus();\"",
          "callChangeStatus()"
      );
    }
    if ($bolCanAcknowledge != 'f') {
      $objDatagrid->addSpecialButton(
          "btnAcknowledged",
          "btnAcknowledged",
          "submit",
          $words['acknowledged'],
          "onClick=\"javascript:return myClient.confirmChangeStatus();\"",
          "callChangeStatus()"
      );
      //$objDatagrid->addSpecialButton("btnClose", "btnClose", "submit", $words['close'], "onClick=\"javascript:return myClient.confirmChangeStatus();\"", "callChangeStatus()");
    }
  } else {
    $strButtons = "";
    /* if ($bolCanEdit)
     {
       $strButtons .= "&nbsp;";
       $strButtons .= generateSubmit("btnVerified", $words['verified'], "", " onClick=\"return confirmStatusChanges(false)\"");
     }*/
    if ($bolCanDelete) {
      $strButtons .= "&nbsp;";
      $strButtons .= generateSubmit("btnDelete", $words['delete'], "", " onClick=\"return confirmDelete()\"");
    }
    if ($bolCanCheck) {
      $strButtons .= "&nbsp;";
      $strButtons .= generateSubmit(
          "btnChecked",
          $words['check'],
          "",
          " onClick=\"return confirmStatusChanges(false)\""
      );
      //$strButtons .= "&nbsp;";
      //$strButtons .= generateSubmit("btnDenied", $words['denied'], "", " onClick=\"return confirmStatusChanges(false)\"");
    }
    if ($bolCanApprove) {
      $strButtons .= "&nbsp;";
      $strButtons .= generateSubmit(
          "btnApproved",
          $words['approved'],
          "",
          " onClick=\"return confirmStatusChanges(false)\""
      );
      $strButtons .= "&nbsp;";
      $strButtons .= generateSubmit(
          "btnDenied",
          $words['denied'],
          "",
          " onClick=\"return confirmStatusChanges(false)\""
      );
      $strButtons .= "&nbsp;";
    }
    if ($bolCanAcknowledge) {
      $strButtons .= "&nbsp;";
      $strButtons .= generateSubmit(
          "btnAcknowledged",
          $words['acknowledged'],
          "",
          " onClick=\"return confirmStatusChanges(false)\""
      );
    }
    return $strButtons;
  }
}

?>
