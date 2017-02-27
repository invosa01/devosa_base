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
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$strWordsInputTrainingRequest = getWords("input training request");
$strWordsTrainingRequestList = getWords("training request list");
$strReportName = getWords("training report");
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
}
scopeData(
    $strDataEmployee,
    $strDataSubSection,
    $strDataSection,
    $strDataDepartment,
    $strDataDivision,
    $_SESSION['sessionUserRole'],
    $arrUserInfo
);
$f = new clsForm("form1", 3, "100%", "");
$f->caption = strtoupper($strWordsFILTERDATA);
//$f->disableFormTag();
//$f->showCaption = false;
$f->addHidden("dataID", $strDataID);
$f->addInput(
    getWords("request date") . " " . getWords("from"),
    "date_from",
    date("Y-m-d"),
    [],
    "date",
    false,
    true,
    true
);
$f->addInput(getWords("date to"), "date_thru", date("Y-m-d"), [], "date", false, true, true);
$f->addInput(
    getWords("training date") . " " . getWords("from"),
    "training_date_from",
    "",
    [],
    "date",
    false,
    true,
    true
);
$f->addInput(getWords("date to"), "training_date_thru", "", [], "date", false, true, true);
$f->addLiteral("", "", "");
$f->addInput(getWords("training topic"), "training_topic", "", [], "string", false, true, true);
$arrType = [];
$arrType[] = ["value" => "-1", "text" => ""];
$arrType[] = ["value" => "0", "text" => getWords("public")];
$arrType[] = ["value" => "1", "text" => getWords("inhouse")];
$arrType[] = ["value" => "2", "text" => getWords("internal")];
$arrType[] = ["value" => "3", "text" => getWords("sharing session")];
$f->addSelect(getWords("category"), "category", $arrType, [], "", false, true, true);
$f->addSelect(
    getWords("request status"),
    "dataRequestStatus",
    getDataListRequestStatus(getInitialValue("RequestStatus"), true, $arrEmpty),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false
);
$f->addLiteral("", "", "");
$f->addLiteral("", "", "");
//ceknya pada employee
$f->addSelect(
    getWords("company"),
    "dataCompany",
    getDataListCompany($strDataCompany, $bolCompanyEmptyOption, $arrCompanyEmptyData, $strKriteria2),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false
);
$f->addSelect(
    getWords("division"),
    "dataDivision",
    getDataListDivision(getInitialValue("Division", "", $strDataDivision), true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['division'] == "")
);
$f->addSelect(
    getWords("department "),
    "dataDepartment",
    getDataListDepartment(getInitialValue("Department", "", $strDataDepartment), true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['department'] == "")
);
$f->addSelect(
    getWords("section"),
    "dataSection",
    getDataListSection(getInitialValue("Section", "", $strDataSection), true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['section'] == "")
);
$f->addSelect(
    getWords("sub section"),
    "dataSubSection",
    getDataListSubSection(getInitialValue("SubSection", "", $strDataSubSection), true),
    ["style" => "width:$strDefaultWidthPx"],
    "",
    false,
    ($ARRAY_DISABLE_GROUP['sub_section'] == "")
);
$f->addSubmit("btnSearch", getWords("show data"), ["onClick" => "javascript:doSearch()"], true, true, "", "", "");
$f->addSubmit("btnPrint", getWords("print"), ["onClick" => "javascript:printList()"], true, true, "", "", "");
$f->addSubmit("btnExportXLS", getWords("excel"), ["onClick" => "javascript:exportExcel()"], true, true, "", "", "");
$formFilter = $f->render();
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
  $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => 30], ['nowrap' => '']));
} else {
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "id", ['width' => 30], ['align' => 'center', 'nowrap' => 'nowrap'])
  );
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
        "topic",
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
        "name_instructor",
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
        getWords("participants"),
        "participants",
        ["nowrap" => "nowrap"],
        ["nowrap" => "nowrap"],
        false,
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
        getWords("paid by"),
        "paid_by",
        ["nowrap" => "nowrap"],
        ["nowrap" => "nowrap", "align" => "right"],
        true,
        true,
        "",
        "printPaidBy()",
        "integer",
        true,
        10
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("status"), "status", "", ['nowrap' => ''], false, false, "", "printRequestStatus()")
);
if ($bolCanEdit) {
  $myDataGrid->addColumn(
      new DataGrid_Column(
          "",
          "",
          ['width' => 45],
          ['align' => 'center', 'nowrap' => 'nowrap'],
          false,
          false,
          "",
          "printGlobalEditLink()",
          "string",
          false
      )
  );
}
/*
$myDataGrid->addColumn(new DataGrid_Column(getWords("sharing session"), "", array(), array('align' => 'center', 'nowrap' => 'nowrap'), false, false, "","sharingLink()", "string", false));
//$myDataGrid->addColumn(new DataGrid_Column("", "", array("rowspan" => 2), array('align' => 'center', 'nowrap' => 'nowrap'), false, false, "","printLink()", "string", false));
$myDataGrid->addColumn(new DataGrid_Column(getWords("evaluation"), "", array(), array('align' => 'center', 'nowrap' => 'nowrap'), false, false, "","printEvaluationLink()", "string", false));*/
$myDataGrid->addColumn(
    new DataGrid_Column(
        "",
        "",
        [],
        ['align' => 'center', 'nowrap' => 'nowrap'],
        false,
        false,
        "",
        "printShowLink()",
        "string",
        false
    )
);
// tambahkan tombol-tombol yang terkait
generateRoleButtons($bolCanEdit, $bolCanDelete, $bolCanCheck, $bolCanApprove, $bolCanAcknowledge, true, $myDataGrid);
$myDataGrid->getRequest();
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
if ($f->getValue("date_from") != "" && $f->getValue("date_thru") != '') {
  $strCriteria .= " AND request_date BETWEEN '" . $f->getValue("date_from") . "' AND '" . $f->getValue(
          "date_thru"
      ) . "' ";
}
if ($f->getValue("training_date_from") != "" && $f->getValue("training_date_thru") != '') {
  $strCriteria .= " AND id IN (SELECT DISTINCT id_request FROM hrd_training_request_detailtime
                        WHERE trainingdate BETWEEN '" . $f->getValue("training_date_from") . "' AND '" . $f->getValue(
          "training_date_thru"
      ) . "') ";
}
if ($f->getValue("dataDivision") != "") {
  $strCriteria .= "AND division_code = '" . $f->getValue("dataDivision") . "' ";
}
if ($f->getValue("dataDepartment") != "") {
  $strCriteria .= "AND department_code = '" . $f->getValue("dataDepartment") . "'";
}
if ($f->getValue("dataSection") != "") {
  $strCriteria .= "AND section_code = '" . $f->getValue("dataSection") . "'";
}
if ($f->getValue("dataSubSection") != "") {
  $strCriteria .= "AND sub_section_code = '" . $f->getValue("dataSubSection") . "'";
}
if ($f->getValue("training_topic") != "") {
  $strCriteria = "AND id_topic IN (SELECT id FROM hrd_training_topic
                          WHERE LOWER(topic) like '%" . strtolower($f->getValue("training_topic")) . "%') ";
}
if ($f->getValue("category") !== "" && $f->getValue("category") != "-1") {
  $strCriteria = "AND category = '" . $f->getValue("category") . "'";
}
if ($f->getValue("dataRequestStatus") != "") {
  $strCriteria .= "AND status = '" . $f->getValue("dataRequestStatus") . "'";
}
//$strCriteria .= $strKriteriaCompany;
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
$strSQL = "SELECT t1.*, t2.topic, t2.training_type, tdep.department_code,tdep.department_name, emp.employee_name, emp.employee_id, tin.name_vendor , t3.name_instructor
					 FROM ( SELECT * FROM hrd_training_request WHERE 1=1 " . $strCriteria . " ) AS t1
					 LEFT JOIN ( SELECT * FROM hrd_employee WHERE active IN (0,1) " . $strKriteriaCompany . " ) AS emp ON t1.id_employee = emp.id
					 LEFT JOIN ( SELECT * FROM hrd_training_plan ) AS t2 ON t1.id_plan = t2.id
					 LEFT JOIN ( SELECT * FROM hrd_department ) AS tdep ON t2.department_code = tdep.department_code
					 LEFT JOIN ( SELECT * FROM hrd_training_instructor ) AS t3 ON t2.id_instructor = t3.id 
					 LEFT JOIN ( SELECT * FROM hrd_training_vendor ) AS tin ON t2.id_training_vendor = tin.id 
					 WHERE 1=1 ";
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
  // ambil dari data partisipan
  $strSQL = "
      SELECT tc.*, te.employee_name, te.employee_id
      FROM hrd_training_request_participant as tc
      LEFT JOIN (
        SELECT * FROM hrd_employee WHERE active IN (0,1)
      ) AS te ON tc.id_employee = te.id
      WHERE tc.id_request = '" . $row['id'] . "'
    ";
  $arrParticipants = $tblData->query($strSQL);
  foreach ($arrParticipants as $index => $arrVal) {
    $arrTemp[$arrVal['id_request']][] = $arrVal;
  }
  $arrParticipants = $arrTemp;
  $intCan = 0;
  $strParticipants = "<table>";
  if (isset($arrParticipants[$row['id']])) {
    //$intParticipant = count($arrParticipants[$rowDb['id']]);
    foreach ($arrParticipants[$row['id']] AS $id => $rowTmp) {
      // print_r($rowTmp);
      //idEmployee
      $strTemporaryEmployeeName = $rowTmp['employee_name'];       //F_DOUBLEPARTICIPANT
      if (isset($tempDict["$strTemporaryEmployeeName"])) {
        continue;
      }   //F_DOUBLEPARTICIPANT
      $tempDict["$strTemporaryEmployeeName"] = true;         //F_DOUBLEPARTICIPANT
      $intParticipant++;
      if ($rowTmp['status'] == 1) {
        $strName = "<strike>" . $rowTmp['employee_name'] . "</strike>";
      } else {
        $strName = $rowTmp['employee_name'];
      }
      if ($intParticipant == 1) {
        $strParticipant1 .= "  <td nowrap title=\"" . $rowTmp['note'] . "\">" . $strName . "&nbsp;</td>\n";
        $strParticipant1 .= "  <td nowrap align=right>" . standardFormat(
                $rowTmp['evaluation'],
                true,
                2
            ) . "&nbsp;</td>\n";
        if ($bolExcel) {
          $strEvaluation = "";
        } else {
          $strEvaluation = ($row['status'] == REQUEST_STATUS_APPROVED && $rowTmp['status'] != 1) ? "<a href=\"training_evaluation_edit.php?dataID=" . $rowTmp['id'] . "\" title=\"" . getWords(
                  'view evaluation'
              ) . "\">" . $words['view'] . "</a>" : "";
        }
        $strParticipant1 .= "  <td nowrap align=center>$strEvaluation&nbsp;</td>\n";
      } else {
        $strParticipant2 .= " <tr valign=top class=$strClass>\n";
        $strParticipant2 .= "  <td nowrap title=\"" . $rowTmp['note'] . "\">" . $strName . "&nbsp;</td>\n";
        $strParticipant2 .= "  <td nowrap align=right>" . standardFormat(
                $rowTmp['evaluation'],
                true,
                2
            ) . "&nbsp;</td>\n";
        if ($bolExcel) {
          $strEvaluation = "";
        } else {
          $strEvaluation = ($row['status'] == REQUEST_STATUS_APPROVED && $rowTmp['status'] != 1) ? "<a href=\"training_evaluation_edit.php?dataID=" . $rowTmp['id'] . "\"  title=\"" . getWords(
                  'view evaluation'
              ) . "\">" . $words['view'] . "</a>" : "";
        }
        $strParticipant2 .= "  <td nowrap align=center>$strEvaluation&nbsp;</td>\n";
        $strParticipant2 .= "  </tr>\n";
      }
    }
  }
  if ($intParticipant == 0) { // gak ada partisipan
    $strParticipant1 .= "  <td nowrap>&nbsp;</td>\n";
    $strParticipant1 .= "  <td nowrap>&nbsp;</td>\n";
    $strParticipant1 .= "  <td nowrap>&nbsp;</td>\n";
    $strParticipant1 .= "  <td nowrap>&nbsp;</td>\n";
  } else {
    $strRowspan = " rowspan=$intParticipant ";
  }
  /*
  foreach($arrData as $data)
  {
    $intCan++;
    if ($strParticipants != "") $strParticipants .= "<BR>\n";
    $strParticipants .= "$intCan. [" .$data['employee_id']. "] ".$data['employee_name'];

    if ($data['status'] == 1)
      $strParticipants .= " <span style='color:red'>(" .getWords("cancel"). ")</span> ";
    $strEvaluation = ($rowDb['status'] == REQUEST_STATUS_APPROVED && $rowTmp['status'] != 1) ? "<a href=\"trainingEvaluationEdit.php?dataID=" .$rowTmp['participant']."\" title=\"" .getWords('view evaluation')."\">" .$words['view']."</a>" : "";

  }*/
  $strParticipants .= $strParticipant1 . $strParticipant2 . "</table>";
  $strParticipant1 = "";
  $strParticipant2 = "";
  $row['participants'] = $strParticipants;
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
  $row['status_flag'] = $row['status'];
}
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$strConfirmDelete = getWords("are you sure to delete this selected data?");
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
// menampilkan link edit evaluation
function printEvaluationLink($params)
{
  extract($params);
  return "<a href=\"training_evaluation_edit.php?dataRequestID=" . $record['id'] . "\">" . getWords(
      'evaluation'
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
  // $strResult .= $strDiv."<a href=\"javascript:openViewWindowByContentId('Record Information', 'detailRecord$counter', 400, 150)\" title=\"" .getWords("show record info")."\">" .getWords("show")."</a>";
  return $strResult;
}

//menampilkan link untuk create training session.
function sharingLink($params)
{
  extract($params);
  global $arrUserInfo;
  $bolEditable = true;
  if ($bolEditable && $record['category'] != '3') {
    return "<a href='training_request_edit.php?btnCreateSharing=Create&id_plan=" . $record['id'] . "'>" . getWords(
        'create'
    ) . "</a>";
  } else {
    return "";
  }
}

// untuk menampilkan link untuk print MRF
function printLink($params)
{
  extract($params);
  $strResult = "<a href=\"javascript:openWindowDialog('templates/training_request_print.html?dataID=" . $record['id'] . "');\">" . getWords(
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
// fungsi untuk verify, check, deny, atau approve
function changeStatus($db, $intStatus)
{
  global $_REQUEST;
  global $_SESSION;
  if (!is_numeric($intStatus)) {
    return false;
  }
  $strUpdate = "";
  $strSQL = "";
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strUpdate = getStatusUpdateString($intStatus);
  foreach ($_REQUEST as $strIndex => $strValue) {
    if (substr($strIndex, 0, 15) == 'DataGrid1_chkID') {
      $strSQLx = "SELECT status, id
                    FROM hrd_training_request 
                    WHERE id = '$strValue' ";
      $resDb = $db->execute($strSQLx);
      if ($rowDb = $db->fetchrow($resDb)) {
        //the status should be increasing
        if (isProcessable($rowDb['status'], $intStatus)) {
          $strSQL .= "UPDATE hrd_training_request SET $strUpdate status = '$intStatus'  ";
          $strSQL .= "WHERE id = '$strValue'; ";
          if ($intStatus == 2) {
            alertApproval($db, $strValue);
          }
          writeLog(
              ACTIVITY_EDIT,
              MODULE_OTHER,
              $rowDb['id'] . " - " . $rowDb['created'] . " - " . $rowDb['training_type'],
              $intStatus
          );
        }
      }
    }
    $resExec = $db->execute($strSQL);
  }
} //changeStatus
function sendApproveTrainingMail($recipientEmailAddr, $senderEmailAddr = null)
{
  $subject = "[Patra] Training Request Approved";
  $message = "This e-mail is sent to you because you are enrolled in a training request, and the request has just approved.\r\n
		E-mail ini dikirimkan kepada anda karena anda terdaftar dalam permohonan training, dan permohonan tersebut baru saja disetujui.";
  return sendMail($recipientEmailAddr, $subject, $message, $senderEmailAddr);
}

function alertApproval($db, $strValue)
{
  //if current action is approve - send email to all involved employee
  $strSQLMail = "SELECT * FROM hrd_training_request_participant AS t1, hrd_employee AS t2 WHERE t1.id_employee = t2.id AND t1.id_request = '$strValue'";
  $resDb = $db->execute($strSQLMail);
  while ($rowDB = $db->fetchrow($resDb)) {
    if (!empty($rowDB["email"])) {
      $successMail = sendApproveTrainingMail($rowDB["email"]);
      if (!$successMail) {
        $myDataGrid->errorMessage .= "ERROR SENDING MAIL TO " . $rowDB["email"];
        echo "ERROR SENDING MAIL TO " . $rowDB["email"];
      }
    }
  }
}

?>