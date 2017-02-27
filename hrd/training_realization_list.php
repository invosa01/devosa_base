<?php
include_once('../global/session.php');
include_once('global.php');
//include_once('../includes/datagrid/datagrid.php');
include_once('../classes/datagrid_modified.php');
include_once('../includes/form2/form2.php');
include_once('../global/common_data.php');
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
$strWordsInputTrainingRequest = getWords("input training request");
$strWordsTrainingRequestList = getWords("training request list");
$strWordsNew = getWords("new");
$strWordsDenied = getWords("denied");
$strWordsChecked = getWords("checked");
$strWordsApproved = getWords("approved");
$strWordsFinished = getWords("finished");
$strWordsVerified = getWords("verified");
$strReportName = getWords("training report");
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
$intPxWidth = "250px";
$db = new CdbClass;
if ($db->connect()) {
  $arrUserList = getAllUserInfo($db);//ambil semua info user]
}
$f = new clsForm("form1", 2, "100%", "");
$f->disableFormTag();
$f->showCaption = false;
$f->addHidden("dataID", $strDataID);
$f->addFieldSet(getWords("search criteria"), 2);
$f->addInput(
    getWords("training date") . " " . getWords("from"),
    "date_from",
    date("Y-m-d"),
    [],
    "date",
    false,
    true,
    true
);
$f->addInput(getWords("date to"), "date_thru", date("Y-m-d"), [], "date", false, true, true);
$f->addInput(getWords("employee id"), "employee_id", "", [], "string", false, true, true);
$f->addLabel(getWords(""), "employee_name", "", [], "string");
$f->addSelect(
    getWords("company"),
    "id_company",
    getDataListCompany(null, true, $emptyData),
    ["style" => "width:$intPxWidth"],
    "string",
    false,
    true,
    true
);
$f->addSelect(
    getWords("division"),
    "division_code",
    getDataListDivision(null, true, $emptyData, $objUP->genFilterDivision()),
    ["style" => "width:$intPxWidth"],
    "string",
    false,
    true,
    true
);
$f->addSelect(
    getWords("department"),
    "department_code",
    getDataListDepartment(null, true, $emptyData, $objUP->genFilterDepartment()),
    ["style" => "width:$intPxWidth"],
    "string",
    false,
    true,
    true
);
$f->addSelect(
    getWords("area"),
    "id_wilayah",
    getDataListWilayah(null, true, $emptyData),
    ["style" => "width:$intPxWidth"],
    "string",
    false,
    true,
    true
);
/*
  $arrType = array();
  $arrType[] = array("value" => "-1", "text" => "");
  $arrType[] = array("value" => "0", "text" => getWords("public"));
  $arrType[] = array("value" => "1", "text" => getWords("inhouse"));
  $arrType[] = array("value" => "2", "text" => getWords("internal"));
  $arrType[] = array("value" => "3", "text" => getWords("sharing session"));
  $f->addSelect(getWords("category"), "category", $arrType, array(), "", false, true, true);
  */
$f->addSubmit("btnSearch", getWords("show data"), ["onClick" => "javascript:doSearch()"], true, true, "", "", "");
$f->addSubmit("btnPrint", getWords("print"), ["onClick" => "javascript:printList()"], true, true, "", "", "");
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
$myDataGrid->caption = $strReportName;
//$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
if ($bolPrint || $bolExcel) {
  $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ["rowspan" => 2, 'width' => 30], ['nowrap' => '']));
} else {
  // $myDataGrid->addColumnCheckbox(new DataGrid_Column("chkID", "id_participant", array('width' => 30), array('align'=>'center', 'nowrap' => 'nowrap')));
}
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("request date"),
        "request_date",
        ["nowrap" => "nowrap"],
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
        getWords("employee id"),
        "employee_id",
        ["nowrap" => "nowrap"],
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
        getWords("name"),
        "employee_name",
        ["nowrap" => "nowrap"],
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
        getWords("department"),
        "department_name",
        ["nowrap" => "nowrap"],
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
        strtoupper(getWords("no.")),
        "request_number",
        [],
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
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("requested by"),
        "employee_name",
        ["nowrap" => "nowrap"],
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
        getWords("training topic"),
        "training_topic",
        ["nowrap" => "nowrap"],
        ["nowrap" => "nowrap"],
        true,
        true,
        "",
        "printTopic()",
        "string",
        true,
        16
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("training type"),
        "training_type",
        ["nowrap" => "nowrap"],
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
        getWords("category"),
        "training_category",
        ["nowrap" => "nowrap"],
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
        getWords("expected result"),
        "result",
        ["nowrap" => "nowrap"],
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
        getWords("institution"),
        "name_vendor",
        ["nowrap" => "nowrap"],
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
        getWords("instructor"),
        "trainer",
        ["nowrap" => "nowrap"],
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
        getWords("place"),
        "address",
        ["nowrap" => "nowrap"],
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
        getWords("training cost"),
        "p_cost",
        ["nowrap" => "nowrap"],
        ["nowrap" => "nowrap", "align" => "right"],
        true,
        true,
        "",
        "printNumeric()",
        "integer",
        true,
        10
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("other cost"),
        "p_cost_other",
        ["nowrap" => "nowrap"],
        ["nowrap" => "nowrap", "align" => "right"],
        true,
        true,
        "",
        "printNumeric()",
        "integer",
        true,
        10
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("training date"),
        "date_details",
        ["nowrap" => "nowrap"],
        ["nowrap" => "nowrap"],
        false,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("training realization"),
        "realization",
        ["nowrap" => "nowrap"],
        ["nowrap" => "nowrap"],
        false,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
//$myDataGrid->addColumn(new DataGrid_Column(getWords("paid by"), "paid_by", array("nowrap" => "nowrap"), array("nowrap" => "nowrap", "align" => "right"), true, true, "", "printPaidBy()", "integer", true, 10));
// $myDataGrid->addColumn(new DataGrid_Column(getWords("status"), "status", array("width" => 80), array("nowrap" => "nowrap"), false, false, "", "", "date", true, 12));
if (!($bolPrint || $bolExcel)) {
  if ($bolCanEdit) {
    // $myDataGrid->addColumn(new DataGrid_Column("", "", array("rowspan" => 2, 'width' => 45), array('align' => 'center', 'nowrap' => 'nowrap'), false, false, "","printEditLink()", "string", false));
  }
  // $myDataGrid->addColumn(new DataGrid_Column("", "", array("rowspan" => 2), array('align' => 'center', 'nowrap' => 'nowrap'), false, false, "","printLink()", "string", false));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("realization"),
          "",
          ["rowspan" => 2],
          ['align' => 'center', 'nowrap' => 'nowrap'],
          false,
          false,
          "",
          "printRealizationLink()",
          "string",
          false
      )
  );
  // $myDataGrid->addColumn(new DataGrid_Column("", "", array("rowspan" => 2), array('align' => 'center', 'nowrap' => 'nowrap'), false, false, "","printShowLink()", "string", false));
}
// tambahkan tombol-tombol yang terkait
if ($bolCanApprove) {
  /*
  if ($objUP->isRoleSupervisor() && $objUP->isEmptyDepartment()) // verified, hanya KADIV
  {
    $myDataGrid->addSpecialButton("btnVerified",  "btnVerified",  "submit", getWords("verified"), "onClick=\"javascript:return confirmStatusChanges(false);\"", "changeStatusVerified()");
  }
  if ($objUP->isAdminHR() || $objUP->isManagerHR()) // checked
  {
    $myDataGrid->addSpecialButton("btnChecked",  "btnChecked",  "submit", getWords("checked"), "onClick=\"javascript:return confirmStatusChanges(false);\"", "changeStatusChecked()");
    $myDataGrid->addSpecialButton("btnCancel", "btnCancel", "submit", getWords("cancel"), "onClick=\"javascript:return confirmStatusChanges();\"", "changeStatusCancel()");
  }
  if ($objUP->isManagerHR() && $bolCanApprove) // approved
  {
    $myDataGrid->addSpecialButton("btnApproved", "btnApproved", "submit", getWords("approved"), "onClick=\"javascript:return confirmStatusChanges(false);\"", "changeStatusApproved()");
  }

  if ($objUP->isManagerHR() || $objUP->isDirector()) // denied atau unapprove
  {
    $myDataGrid->addSpecialButton("btnUnApprove", "btnUnApprove", "submit", getWords("unapprove"), "onClick=\"javascript:return confirmStatusChanges(true);\"", "changeStatusUnApprove()");
    $myDataGrid->addSpecialButton("btnDenied", "btnDenied", "submit", getWords("denied"), "onClick=\"javascript:return confirmStatusChanges(true);\"", "changeStatusDenied()");
  }
  */
}
if ($bolCanDelete) {
  //if ($objUP->isRoleSupervisor() || $objUP->isManagerHR() || $objUP->isDirector())
  // $myDataGrid->addSpecialButton("btnDelete","btnDelete","submit", getWords("delete"),"onClick=\"javascript:return myClient.confirmDelete();\" ","deleteData()");
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
$tblData = new cModel("hrd_training_request", "Training");
if ($bolExcel) {
  $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
  $myDataGrid->strFileNameXLS = "training.xls";
  $myDataGrid->strTitle1 = $strReportName; //"List of Manpower Requisition Form";
  $myDataGrid->strTitle2 = "Printed Date: " . date("d/m/Y h:i:s");
} elseif ($bolPrint) {
  $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_PRINT_HTML;
  $myDataGrid->strTitle1 = $strReportName; //"List of Manpower Requisition Form";
  $myDataGrid->strTitle2 = "Printed Date: " . date("d/m/Y h:i:s");
}
$strCriteriaFlag = $myDataGrid->getCriteria();
$arrCriteria = [];
$strCriteria = ""; // kriteria training
$strCriteriaEmployee = ""; // kriteria terkait karyawan
// if ($f->getValue("date_from") != "" && $f->getValue("date_thru") != '')
//   $strCriteria .= " AND request_date BETWEEN '" .$f->getValue("date_from"). "' AND '" .$f->getValue("date_thru"). "' ";
if ($f->getValue("date_from") != "" && $f->getValue("date_thru") != '') {
  $strCriteria .= " AND id IN (SELECT DISTINCT id_request FROM hrd_training_request_detailtime
                        WHERE trainingdate BETWEEN '" . $f->getValue("date_from") . "' AND '" . $f->getValue(
          "date_thru"
      ) . "') ";
}
if ($f->getValue("division_code") != "") {
  $strCriteriaEmployee .= "AND division_code = '" . $f->getValue("division_code") . "'";
}
if ($f->getValue("department_code") != "") {
  $strCriteriaEmployee .= "AND department_code = '" . $f->getValue("department_code") . "'";
}
if ($f->getValue("id_company") != "") {
  $strCriteriaEmployee .= "AND id_company = '" . $f->getValue("id_company") . "'";
}
if ($f->getValue("id_wilayah") != "") {
  $strCriteriaEmployee .= "AND id_wilayah = '" . $f->getValue("id_wilayah") . "'";
}
if ($f->getValue("employee_id") != "") {
  $strCriteriaEmployee .= "AND employee_id = '" . $f->getValue("employee_id") . "'";
}
/*
if ($f->getValue("training_topic") != "")
  $strCriteria = "AND id_topic IN (SELECT id FROM hrd_training_topic
                        WHERE LOWER(topic) like '%".strtolower($f->getValue("training_topic"))."%') ";
if ($f->getValue("category") !== "" && $f->getValue("category") != "-1")
  $strCriteria = "AND category = '".$f->getValue("category")."'";
*/
// tambahkan kriteria sesuai hak akses user
if ($objUP->isUserEmployee()) {
  $strCriteriaEmployee .= "
      AND department_code IN (SELECT department_code FROM hrd_department WHERE 1=1 " . $objUP->genFilterDepartment() . ")
    ";
}
/*
$myDataGrid->totalData = $tblData->findCount($strCriteriaFlag);
$dataset = $tblData->findAll($strCriteriaFlag,
                                 "*",
                                 $myDataGrid->getSortBy(),
                                 $myDataGrid->getPageLimit(),
                                 $myDataGrid->getPageNumber());
$tblDepartment = new cModel("hrd_department", "department");
$arrDepartment = $tblDepartment->findAll(null, "department_code, department_name", null, null, null, "department_code");
*/
$strSQL = "
    SELECT t1.*, t2.id_employee AS id_employee_participant,
      t2.status AS participant_status, t2.id AS id_participant,
      t2.cost as p_cost, t2.other_cost as p_cost_other,
      tdep.department_name, emp.employee_name, emp.employee_id,
      ttop.topic as training_topic, tin.name_vendor
    FROM (
      SELECT * FROM hrd_training_request WHERE 1=1 " . $strCriteria . "
    ) AS t1
    INNER JOIN (
      SELECT * FROM hrd_training_request_participant
      WHERE id_request IN (
        SELECT id FROM hrd_training_request WHERe 1=1 " . $strCriteria . "
      )
    ) AS t2 ON t2.id_request = t1.id
    INNER JOIN (
      SELECT * FROM hrd_employee WHERE active IN (0,1)
        $strCriteriaEmployee
     ) AS emp ON t2.id_employee = emp.id
    LEFT JOIN (
      SELECT * FROM hrd_department 
     ) AS tdep ON emp.department_code = tdep.department_code
    LEFT JOIN (
      SELECT * FROM hrd_training_topic
     ) AS ttop ON t1.id_topic = ttop.id 
    LEFT JOIN (
      SELECT * FROM hrd_training_vendor
     ) AS tin ON t1.id_institution = tin.id 
    WHERE 1=1 
    ";
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM ($strSQL) AS tbl ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
// cari dulu data status per proses
foreach ($dataset as &$row) {
  $row['request_date'] = pgDateFormat($row['request_date'], "d-M-y");
  if ($row['category'] == 1) {
    $row['training_category'] = getWords("inhouse");
  } else if ($row['category'] == 2) {
    $row['training_category'] = getWords("internal");
  } else if ($row['category'] == 3) {
    $row['training_category'] = getWords("sharing session");
  } else {
    $row['training_category'] = getWords("public");
  }
  $row['result'] = nl2br($row['result']);
  //$row['cost'] = standardFormat($row['cost']);
  // ambil dari data tanggal training
  $strSQL = "
      SELECT *
      FROM hrd_training_request_detailtime
      WHERE id_request = '" . $row['id'] . "'
      ORDER BY trainingdate
    ";
  $arrData = $tblData->query($strSQL);
  $intDate = 0;
  $strDate = "";
  foreach ($arrData as $data) {
    $intDate++;
    if ($strDate != "") {
      $strDate .= "<BR>\n";
    }
    $strDate .= $intDate . ". " . $data['trainingdate'] . " &nbsp; ";
    $strDate .= substr($data['timestart'], 0, 5) . " - " . substr($data['timefinish'], 0, 5);
  }
  $row['date_details'] = $strDate;
  // ambil dari data tanggal training
  $strDate = "";
  $strSQL = "
      SELECT *
      FROM hrd_training_realization
      WHERE id_request = '" . $row['id'] . "'
        AND id_employee = '" . $row['id_employee_participant'] . "'
      ORDER BY trainingdate
    ";
  $arrData = $tblData->query($strSQL);
  $intDate = 0;
  $strDate = "";
  foreach ($arrData as $data) {
    $intDate++;
    if ($strDate != "") {
      $strDate .= "<BR>\n";
    }
    $strDate .= $intDate . ". " . $data['trainingdate'] . " &nbsp; ";
    $strDate .= substr($data['timestart'], 0, 5) . " - " . substr($data['timefinish'], 0, 5);
  }
  $row['realization'] = $strDate;
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
$strTemplateFile = getTemplate();
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master2.html");
$tbsPage->Show();
//--------------------------------------------------------------------------------
// menampilkan link edit
function printEditLink($params)
{
  extract($params);
  return "<a href=\"training_request_edit.php?dataID=" . $record['id'] . "\">" . getWords('edit') . "</a>";
}

// menampilkan link edit evaluation
function printRealizationLink($params)
{
  extract($params);
  return "<a href=\"training_realization_edit.php?dataRequestID=" . $record['id'] . "&dataEmployeeID=" . $record['id_employee_participant'] . "\">" . getWords(
      'edit realization'
  ) . "</a>";
}

// menampilkan info training topic
function printTopic($params)
{
  global $bolPrint, $bolExcel;
  extract($params);
  if ($bolPrint || $bolExcel) {
    return $value;
  } else {
    return "
      <a href=\"javascript:showTopicInfo('" . $record['id_topic'] . "')\">$value</a>
    ";
  }
}

// menampilkan angka
function printNumeric($params)
{
  extract($params);
  return standardFormat($value);
}

// menampilkan info sumber biaya
function printPaidBy($params)
{
  extract($params);
  $strResult = ($value == 1) ? getWords("employee") : getWords("company");
  return $strResult;
}

// untuk menampilkan info yang mengubah data MRF
function printShowLink($params)
{
  extract($params);
  global $arrUserList;
  $strResult = "";
  // tambahkan info record info
  $strDiv = "<div id='detailRecord$counter' style=\"display:none\">\n";
  $strDiv .= getWords("last modified") . ": " . substr($record['created'], 0, 19) . " ";
  $strDiv .= (isset($arrUserList[$record['created_by']])) ? $arrUserList[$record['created_by']]['name'] . "<br>" : "<br>";
  $strDiv .= getWords("verified") . ": " . substr($record['verified_time'], 0, 19) . " ";
  $strDiv .= (isset($arrUserList[$record['verified_by']])) ? $arrUserList[$record['verified_by']]['name'] . "<br>" : "<br>";
  $strDiv .= getWords("checked") . ": " . substr($record['checked_time'], 0, 19) . " ";
  $strDiv .= (isset($arrUserList[$record['checked_by']])) ? $arrUserList[$record['checked_by']]['name'] . "<br>" : "<br>";
  $strDiv .= getWords("approved") . ": " . substr($record['approved_time'], 0, 19) . " ";
  $strDiv .= (isset($arrUserList[$record['approved_by']])) ? $arrUserList[$record['approved_by']]['name'] . "<br>" : "<br>";
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
  $strResult = "<a href=\"javascript:openWindowDialog('training_request_print.php?dataID=" . $record['id'] . "');\">" . getWords(
          "print"
      ) . "</a>";
  return $strResult;
}

// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $tbl = new cModel("hrd_training_request");
  $bolOK = true;
  foreach ($myDataGrid->checkboxes as $strValue) {
    $strSQL = "
        DELETE FROM hrd_training_request_participant WHERE id_request = '$strValue';
        DELETE FROM hrd_training_request_detailtime WHERE id_request = '$strValue';
        DELETE FROM hrd_training_request_trainer WHERE id_request = '$strValue';
        DELETE FROM hrd_training_request WHERE id = '$strValue';
      ";
    $bolOK = $tbl->query($strSQL);
  }
  $myDataGrid->message = getWords("data deleted");
  /*
    $arrKeys = array();
    foreach ($myDataGrid->checkboxes as $strValue)
      $arrKeys['id'][] = $strValue;

    if ($tbl->deleteMultiple($arrKeys))
      $myDataGrid->message = $tbl->strMessage;
    else
      $myDataGrid->errorMessage = $tbl->strMessage;  
        */
} //deleteData
function changeStatus($intStatus)
{
  global $myDataGrid;
  global $objUP;
  global $tblData;
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
  } else if ($intStatus == REQUEST_STATUS_CANCEL) {
    $strUpdate = "
        training_status = 2,
      ";
  } else {
    $strUpdate = "";
  }
  $i = 0;
  $tbl = new cModel("hrd_training_request");
  foreach ($myDataGrid->checkboxes as $strValue) {
    $i++;
    $strSQL = "UPDATE hrd_training_request SET $strUpdate status = '$intStatus'  ";
    $strSQL .= "WHERE id = '" . $strValue . "' ";
    if (!$objUP->isManagerHR()) {
      $strSQL .= "AND status <>  " . REQUEST_STATUS_APPROVED;
    } // yang udah apprve gak boleh diedit
    if ($objUP->isRoleSupervisor()) { // supervisor tidak boleh approve data yang dientry oleh dia sendiri
      $strSQL .= " AND created_by <> '" . $objUP->getUserID() . "' ";
    }
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

?>