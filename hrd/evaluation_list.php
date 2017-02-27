<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_evaluation_master.php');
include_once('../classes/hrd/hrd_evaluation_detail.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
//---- INISIALISASI ----------------------------------------------------
$strWordsEvaluationEntry = getWords("evaluation entry");
$strWordsEvaluationList = getWords("evaluation list");
$strWordsEvaluationApproval = getWords("evaluation approval");
//DAFTAR FUNGSI--------------------------------------------------------------------------------------------------------------
function getData($db)
{
  global $dataPrivilege;
  global $f;
  global $DataGrid;
  global $strKriteriaCompany;
  //global $arrUserInfo;
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if (validStandardDate($arrData['dataDateFrom']) && validStandardDate($arrData['dataDateThru'])) {
    $strKriteria .= "AND (t1.created::date BETWEEN '" . $arrData['dataDateFrom'] . "' AND '" . $arrData['dataDateThru'] . "')  ";
  }
  if ($arrData['dataEmployee'] != "") {
    $strKriteria .= "AND t2.employee_id = '" . $arrData['dataEmployee'] . "'";
  }
  if ($arrData['dataEvaluator'] != "") {
    $strKriteria .= "AND t3.employee_id = '" . $arrData['dataEvaluator'] . "'";
  }
  if ($arrData['dataRequestStatus'] != "") {
    $strKriteria .= "AND status = '" . $arrData['dataRequestStatus'] . "'";
  }
  if ($arrData['dataBranch'] != "") {
    $strKriteria .= "AND t2.branch_code = '" . $arrData['dataBranch'] . "'";
  }
  if ($arrData['dataPosition'] != "") {
    $strKriteria .= "AND t2.position_code = '" . $arrData['dataPosition'] . "'";
  }
  if ($arrData['dataGrade'] != "") {
    $strKriteria .= "AND t2.grade_code = '" . $arrData['dataGrade'] . "'";
  }
  if ($arrData['dataStatus'] != "") {
    $strKriteria .= "AND t2.employee_status = '" . $arrData['dataStatus'] . "'";
  }
  if ($arrData['dataActive'] != "") {
    $strKriteria .= "AND t2.active = '" . $arrData['dataActive'] . "'";
  }
  if ($arrData['dataDivision'] != "") {
    $strKriteria .= "AND t2.division_code = '" . $arrData['dataDivision'] . "'";
  }
  if ($arrData['dataDepartment'] != "") {
    $strKriteria .= "AND t2.department_code = '" . $arrData['dataDepartment'] . "'";
  }
  if ($arrData['dataSection'] != "") {
    $strKriteria .= "AND t2.section_code = '" . $arrData['dataSection'] . "'";
  }
  if ($arrData['dataSubSection'] != "") {
    $strKriteria .= "AND t2.sub_section_code = '" . $arrData['dataSubSection'] . "'";
  }
  $strKriteria .= $strKriteriaCompany;
  if ($db->connect()) {
    $myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
    $myDataGrid->caption = getWords(
        strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
    );
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->setCriteria($strKriteria);
    //$myDataGrid->addColumnCheckbox(new DataGrid_Column("chkID", "id", array('width' => '30'), array('align'=>'center', 'nowrap' => '')), true /*bolDisableSelfStatusChange*/);
    $myDataGrid->addColumnNumbering(
        new DataGrid_Column("No", "", "", ['width' => '30', 'valign' => 'top', 'nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("evaluation date"),
            "evaluation_date",
            "",
            ['width' => '100', 'valign' => 'top', 'nowrap' => '']
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("evaluation period"),
            "evaluation_period",
            "",
            ['width' => '150', 'valign' => 'top', 'nowrap' => '']
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("employee id"),
            "employee_id",
            "",
            ['width' => '100', 'valign' => 'top', 'nowrap' => '']
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("employee name"),
            "employee_name",
            "",
            ['width' => '200', 'valign' => 'top', 'nowrap' => '']
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("division"),
            "division_name",
            "",
            ['width' => '200', 'valign' => 'top', 'nowrap' => '']
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("department"),
            "department_name",
            "",
            ['width' => '200', 'valign' => 'top', 'nowrap' => '']
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("evaluator"),
            "evaluator_name",
            "",
            ['width' => '100', 'valign' => 'top', 'nowrap' => '']
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("total score"),
            "total_score",
            "",
            ['align' => 'center', 'nowrap' => ''],
            false,
            false,
            "",
            ""
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("total result"),
            "total_result",
            "",
            ['align' => 'center', 'nowrap' => ''],
            false,
            false,
            "",
            ""
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("status"), "status", "", "", false, false, "", "printRequestStatus()")
    );
    if ($dataPrivilege['view'] == 't' && !isset($_REQUEST['btnExportXLS'])) {
      $myDataGrid->addColumn(
          new DataGrid_Column(
              "",
              "",
              ["width" => "60"],
              ['align' => 'center', 'nowrap' => ''],
              false,
              false,
              "",
              "printEditLink()",
              "",
              false /*show in excel*/
          )
      );
    }
    foreach ($arrData AS $key => $value) {
      $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
    }
    //tampilkan buttons sesuai dengan otoritas, common_function.php
    //generateRoleButtons($dataPrivilege['edit'], $dataPrivilege['delete'], $dataPrivilege['check'], $dataPrivilege['approve'], true, true, $myDataGrid);
    $myDataGrid->addButtonExportExcel(
        "Export Excel",
        $dataPrivilege['menu_name'] . ".xls",
        getWords($dataPrivilege['menu_name'])
    );
    $myDataGrid->getRequest();
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_evaluation_master as t1
                       LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id 
                       LEFT JOIN (select id, employee_id from hrd_employee) as t3 ON t1.id_evaluator = t3.id WHERE 1=1 ";
    $strSQL = "SELECT t1.*, t1.evaluation_period_from || ' to ' || t1.evaluation_period_thru as evaluation_period, t2.employee_id,
                       t2.employee_name, t2a.division_name, t2b.department_name, t3.employee_name as evaluator_name FROM hrd_evaluation_master AS t1 
                       LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id 
                       LEFT JOIN hrd_division AS t2a ON t2.division_code = t2a.division_code 
                       LEFT JOIN hrd_department AS t2b ON t2.department_code = t2b.department_code 
                       LEFT JOIN (select id, employee_id, employee_name from hrd_employee) as t3 ON t1.id_evaluator = t3.id WHERE 1=1 ";
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
    $dataset = $myDataGrid->getData($db, $strSQL);
    //bind Datagrid with array dataset and branchCode
    $myDataGrid->bind($dataset);
    $DataGrid = $myDataGrid->render();
  } else {
    $DataGrid = "";
  }
  return $DataGrid;
}

$db = new CdbClass;
$db->connect();
// ------ AMBIL DATA KRITERIA -------------------------
$strDataID = trim(getPostValue('dataID'));
getUserEmployeeInfo();
$arrUserList = getAllUserInfo($db);
scopeData(
    $strDataEmployee,
    $strDataSubSection,
    $strDataSection,
    $strDataDepartment,
    $strDataDivision,
    $_SESSION['sessionUserRole'],
    $arrUserInfo
);
$dataHrdEvaluationMaster = new cHrdEvaluationMaster();
if ($bolCanView) {
  $f = new clsForm("formInput", 3, "100%", "");
  $f->caption = strtoupper("filter data " . $dataPrivilege['menu_name']);
  $f->addHidden("dataID", $strDataID);
  $f->addInput(
      getWords("date from"),
      "dataDateFrom",
      getInitialValue("DateFrom", date("Y-") . "01-01"),
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      getWords("date thru"),
      "dataDateThru",
      getInitialValue("DateThru", date("Y-m-d")),
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInputAutoComplete(
      getWords("employee"),
      "dataEmployee",
      getDataEmployee(getInitialValue("Employee", null, $strDataEmployee)),
      ["style" => "width:$strDateWidth", $strEmpReadonly => $strEmpReadonly],
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployee", "");
  $f->addInputAutoComplete(
      getWords("evaluator"),
      "dataEvaluator",
      getDataEmployee(getInitialValue("Evaluator")),
      ["style" => "width:$strDateWidth"],
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEvaluator", "");
  $f->addSelect(
      getWords("request status"),
      "dataRequestStatus",
      getDataListRequestStatus(
          getInitialValue("RequestStatus"),
          true,
          ["value" => "", "text" => "", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
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
      "dataStatus",
      getDataListEmployeeStatus(
          getInitialValue("EmployeeStatus"),
          true,
          ["value" => "", "text" => "", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("active"),
      "dataActive",
      getDataListEmployeeActive(
          getInitialValue("Active"),
          true,
          ["value" => "", "text" => "", "selected" => true]
      ),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addLiteral("", "", "");
  $f->addLiteral("", "", "");
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
  $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
  $formInput = $f->render();
} else {
  $formInput = "";
}
getData($db);
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
function getScoreTable($strDataID)
{
  $strResult = "";
  $strResult .= "<table border=1 cellpadding=1 cellspacing=0>
                   <tr>
                   <th>" . getWords("score") . "</th><th>" . getWords("note") . "</th></tr><tr>
                   <td>" . generateInput("dataScore1", 5, "size=7") . "</td>
                   <td>" . generateInput("dataScore1Note", "", "size=100") . "</td></tr><tr>
                   <td>" . generateInput("dataScore2", 4, "size=7") . "</td>
                   <td>" . generateInput("dataScore2Note", "", "size=100") . "</td></tr><tr>
                   <td>" . generateInput("dataScore3", 3, "size=7") . "</td>
                   <td>" . generateInput("dataScore3Note", "", "size=100") . "</td></tr><tr>
                   <td>" . generateInput("dataScore4", 2, "size=7") . "</td>
                   <td>" . generateInput("dataScore4Note", "", "size=100") . "</td></tr><tr>
                   <td>" . generateInput("dataScore5", 1, "size=7") . "</td>
                   <td>" . generateInput("dataScore5Note", "", "size=100") . "</td>
                   </tr>
                   </table>";
  return $strResult;
}

function printEditLink($params)
{
  extract($params);
  //return "<a href=\"evaluation_edit.php?dataID=".$record['id']."\">" .getWords('view'). "</a>";
  return "<a href='javascript:showEvaluationEdit(" . $record['id'] . ");'>" . getWords('view') . "</a>";
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $isNew;
  global $fltRemainingWeight;
  $arrScore = [];
  $db = new CdbClass;
  $bolSave = true;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $dataHrdEvaluationCriteria = new cHrdEvaluationCriteria();
  //$strIDEmployee = ($f->getValue('dataEmployee') == "") ? -1 : getIDEmployee($db, $f->getValue('dataEmployee'));
  if ($isNew) {
    if ($fltRemainingWeight < floatVal($f->getValue('dataWeight'))) {
      $bolSave = false;
      $f->message = "Exceeded weight percentage, the remaining weight quota is " . $fltRemainingWeight;
    }
  } else {
    $arrTemp = $dataHrdEvaluationCriteriaEmployee->find("id = '" . $f->getValue('dataID') . "'", "weight");
    if (($fltRemainingWeight + $arrTemp['weight']) < floatVal($f->getValue('dataWeight'))) {
      $bolSave = false;
      $f->message = "Exceeded weight percentage, the remaining weight quota is " . ($fltRemainingWeight + $arrTemp['weight']);
    }
  }
  if ($bolSave) {
    for ($i = 1; $i <= 5; $i++) {
      $arrScore['dataScore' . $i] = getPostValue('dataScore' . $i);
      $arrScore['dataScore' . $i . 'Note'] = getPostValue('dataScore' . $i . 'Note');
    }
    $data = [
        "id_category" => $f->getValue('dataIDCategory'),
        "year"        => $f->getValue('dataYear'),
        "criteria"    => $f->getValue('dataCriteria'),
        "subheader"   => $f->getValue('dataSubheader'),
        "weight"      => floatVal($f->getValue('dataWeight')),
        "score1"      => $arrScore['dataScore1'],
        "score2"      => $arrScore['dataScore2'],
        "score3"      => $arrScore['dataScore3'],
        "score4"      => $arrScore['dataScore4'],
        "score5"      => $arrScore['dataScore5'],
        "score1_note" => $arrScore['dataScore1Note'],
        "score2_note" => $arrScore['dataScore2Note'],
        "score3_note" => $arrScore['dataScore3Note'],
        "score4_note" => $arrScore['dataScore4Note'],
        "score5_note" => $arrScore['dataScore5Note'],
    ];
    // simpan data -----------------------
    $bolSuccess = false;
    if ($isNew) {
      // data baru
      $bolSuccess = $dataHrdEvaluationCriteria->insert($data);
      if ($bolSuccess) {
        $f->setValue('dataID', $dataHrdEvaluationCriteria->getLastInsertId());
      }
    } else {
      $bolSuccess = $dataHrdEvaluationCriteria->update(/*pk*/
          "id='" . $f->getValue('dataID') . "'", /*data to update*/
          $data
      );
    }
  }
  $f->message = $dataHrdEvaluationCriteria->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
    $arrKeys2['id_evaluation'][] = $strValue;
  }
  $tblHrdEvaluationMaster = new cHrdEvaluationMaster();
  $tblHrdEvaluationDetail = new cHrdEvaluationDetail();
  $tblHrdEvaluationMaster->deleteMultiple($arrKeys);
  $tblHrdEvaluationDetail->deleteMultiple($arrKeys2);
  $myDataGrid->message = $tblHrdEvaluationDetail->strMessage;
} //deleteData
function printScoreList($params)
{
  extract($params);
  global $bolPrint;
  if ($bolPrint) {
    return stripslashes($value);
  } else {
    return "<table class=\"gridTable\" width=\"100%\">
               <tr><td width=25>" . $record['score1'] . "</td><td>&nbsp;" . $record['score1_note'] . "</td></tr>
               <tr><td>" . $record['score2'] . "</td><td>&nbsp;" . $record['score2_note'] . "</td></tr>
               <tr><td>" . $record['score3'] . "</td><td>&nbsp;" . $record['score3_note'] . "</td></tr>
               <tr><td>" . $record['score4'] . "</td><td>&nbsp;" . $record['score4_note'] . "</td></tr>
               <tr><td>" . $record['score5'] . "</td><td>&nbsp;" . $record['score5_note'] . "</td></tr>
               </table>";
  }
}

?>