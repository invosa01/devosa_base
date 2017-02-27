<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_training_category.php');
include_once('../classes/hrd/hrd_training_need_analysis_master.php');
include_once('../classes/hrd/hrd_training_need_analysis_detail_by_type.php');
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
$strWordsTNAEdit = getWords("tna edit");
$strWordsTNAList = getWords("tna list");
$db = new CdbClass;
$db->connect();
// ------ AMBIL DATA KRITERIA -------------------------
$strDataEmployeeID = trim(getSessionValue('sessiondataEmployeeID'));
$strDataID = trim(getPostValue('dataID'));
$strDataName = trim(getSessionValue('sessiondataName'));
$strDataDivision = getSessionValue('sessiondataDivision');
$strDataDepartment = getSessionValue('sessiondataDepartment');
$strDataSection = getSessionValue('sessiondataSection');
$strDataSubSection = getSessionValue('sessiondataSubSection');
$strDataGrade = getSessionValue('sessiondataGrade');
$strDataPosition = getSessionValue('sessiondataPosition');
if (isset($_REQUEST['dataEmployeeID'])) {
  $strDataEmployeeID = trim($_REQUEST['dataEmployeeID']);
}
if (isset($_REQUEST['dataName'])) {
  $strDataName = trim($_REQUEST['dataName']);
}
if (isset($_REQUEST['dataDivision'])) {
  $strDataDivision = $_REQUEST['dataDivision'];
}
if (isset($_REQUEST['dataDepartment'])) {
  $strDataDepartment = $_REQUEST['dataDepartment'];
}
if (isset($_REQUEST['dataSection'])) {
  $strDataSection = $_REQUEST['dataSection'];
}
if (isset($_REQUEST['dataSubSection'])) {
  $strDataSection = $_REQUEST['dataSubSection'];
}
if (isset($_REQUEST['dataGrade'])) {
  $strDataGrade = $_REQUEST['dataGrade'];
}
if (isset($_REQUEST['dataPosition'])) {
  $strDataPosition = trim($_REQUEST['dataPosition']);
}
$_SESSION['sessiondataEmployeeID'] = $strDataEmployeeID;
$_SESSION['sessiondataName'] = $strDataName;
$_SESSION['sessiondataDivision'] = $strDataDivision;
$_SESSION['sessiondataDepartment'] = $strDataDepartment;
$_SESSION['sessiondataSection'] = $strDataSection;
$_SESSION['sessiondataSubSection'] = $strDataSubSection;
$_SESSION['sessiondataGrade'] = $strDataGrade;
$_SESSION['sessiondataPosition'] = $strDataPosition;
$strDataDateFrom = (isset($_REQUEST['dataDateFrom'])) ? $_REQUEST['dataDateFrom'] : "";
$strDataDateThru = (isset($_REQUEST['dataDateThru'])) ? $_REQUEST['dataDateThru'] : "";
$tblTrainingCategory = new cHrdTrainingCategory();
$dataTrainingCategory = $tblTrainingCategory->findAll("", null, "", null, 1, "id");
$tblTNADetailByType = new cHrdTrainingNeedAnalysisDetailByType;
if ($bolCanView) {
  $f = new clsForm("formInput", 2, "100%", "");
  $f->caption = strtoupper("filter data " . $dataPrivilege['menu_name']);
  $f->addHidden("dataID", $strDataID);
  //$f->addSelect(getWords("year"), "dataYear",   getDataListYear($strDataYear, false /*has empty*/, null /*empty data*/, 10 /*limit*/, true /*asc*/), "", "", false);
  $f->addInput(
      getWords("date from"),
      "dataDateFrom",
      $strDataDateFrom,
      ["style" => "width:$strDateWidth"],
      "date",
      true,
      true,
      true
  );
  $f->addInput(
      getWords("date thru"),
      "dataDateThru",
      $strDataDateThru,
      ["style" => "width:$strDateWidth"],
      "date",
      true,
      true,
      true
  );
  $f->addSelect(
      getWords("division"),
      "dataDivision",
      getDataListDivision($strDataDivision, true),
      ["style" => "width:270"]
  );
  $f->addSelect(
      getWords("department"),
      "dataDepartment",
      getDataListDepartment($strDataDepartment, true),
      ["style" => "width:270"]
  );
  $f->addSelect(
      getWords("section"),
      "dataSection",
      getDataListSection($strDataSection, true),
      ["style" => "width:270"]
  );
  $f->addSelect(
      getWords("sub section"),
      "dataSubSection",
      getDataListSubSection($strDataSubSection, true),
      ["style" => "width:270"]
  );
  $f->addSelect(
      getWords("level"),
      "dataPosition",
      getDataListPosition($strDataPosition, true),
      ["style" => "width:270"]
  );
  $f->addSelect(getWords("grade"), "dataGrade", getDataListSalaryGrade($strDataGrade, true), ["style" => "width:270"]);
  $f->addInputAutoComplete(
      getWords("employee"),
      "dataEmployee",
      getDataEmployee($strDataEmployeeID),
      ["size" => 50],
      "string",
      false
  );
  $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "showData()");
  $formInput = $f->render();
} else {
  $formInput = "";
}
$myDataGrid = new cDataGrid("formData", "DataGrid");
$myDataGrid->caption = strtoupper(getWords($dataPrivilege['menu_name']));
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", "", ['width' => '15', 'valign' => 'top', 'align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(
    new DataGrid_Column("No", "", "", ['width' => '30', 'valign' => 'top', 'nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("created date"),
        "created_date",
        "",
        ['width' => '100', 'valign' => 'top', 'nowrap' => '']
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
        getWords("division"), "division_code", "", ['width' => '200', 'valign' => 'top', 'nowrap' => '']
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("department"),
        "department_code",
        "",
        ['width' => '200', 'valign' => 'top', 'nowrap' => '']
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("grade"), "grade_code", "", ['width' => '200', 'valign' => 'top', 'nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("job title"), "job_title", "", ['width' => '200', 'valign' => 'top', 'nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("job description"),
        "job_description",
        "",
        ['width' => '200', 'valign' => 'top', 'nowrap' => '']
    )
);
foreach ($dataTrainingCategory as $strIDCategory => $arrCategory) {
  $myDataGrid->addColumn(
      new DataGrid_Column(
          $arrCategory['training_category'],
          "category_" . $strIDCategory,
          "",
          ['align' => 'center', 'nowrap' => ''],
          false,
          false,
          "",
          ""
      )
  );
}
if ($bolCanDelete) {
  $myDataGrid->addSpecialButton(
      "btnDelete",
      "btnDelete",
      "submit",
      "Delete",
      "onClick=\"javascript:return myClient.confirmDelete();\"",
      "deleteData()"
  );
}
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_training_need_analysis_master ";
$strSQL = "SELECT created :: date as created_date, * FROM hrd_training_need_analysis_master ";
/* if ($strDataIDCategory != "")
 {
   $strSQL    .= " AND id_category = '$strDataIDCategory'";
   $strSQLCOUNT    .= " AND id_category = '$strDataIDCategory'";
 }*/
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
foreach ($dataset as $strKey => $arrDetail) {
  $dataTNADetailByType = $tblTNADetailByType->findAll(
      "id_master = '" . $arrDetail['id'] . "'",
      null,
      "",
      null,
      1,
      "id_category"
  );
  foreach ($dataTrainingCategory as $strIDCategory => $arrTypes) {
    if (isset($dataTNADetailByType[$strIDCategory])) {
      $arrTypes = $dataTNADetailByType[$strIDCategory];
      if (isset($dataset[$strKey]['category_' . $strIDCategory])) {
        $dataset[$strKey]['category_' . $strIDCategory] .= "<br>" . $arrTypes['type'];
      } else {
        $dataset[$strKey]['category_' . $strIDCategory] = $arrTypes['type'];
      }
    } else {
      $dataset[$strKey]['category_' . $strIDCategory] = "";
    }
  }
}
//print_r($dataset);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
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
  return "
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['id'] . "' />
      <input type=hidden name='detailIDCategory$counter' id='detailIDCategory$counter' value='" . $record['id_category'] . "' />
      <input type=hidden name='detailSubheader$counter' id='detailSubheader$counter' value='" . $record['subheader'] . "' />
      <input type=hidden name='detailCriteria$counter' id='detailCriteria$counter' value='" . $record['criteria'] . "' />
      <input type=hidden name='detailWeight$counter' id='detailWeight$counter' value='" . $record['weight'] . "' />
      <input type=hidden name='detailScore1$counter' id='detailScore1$counter' value='" . $record['score1'] . "' />
      <input type=hidden name='detailScore2$counter' id='detailScore2$counter' value='" . $record['score2'] . "' />
      <input type=hidden name='detailScore3$counter' id='detailScore3$counter' value='" . $record['score3'] . "' />
      <input type=hidden name='detailScore4$counter' id='detailScore4$counter' value='" . $record['score4'] . "' />
      <input type=hidden name='detailScore5$counter' id='detailScore5$counter' value='" . $record['score5'] . "' />
      <input type=hidden name='detailScore1Note$counter' id='detailScore1Note$counter' value='" . $record['score1_note'] . "' />
      <input type=hidden name='detailScore2Note$counter' id='detailScore2Note$counter' value='" . $record['score2_note'] . "' />
      <input type=hidden name='detailScore3Note$counter' id='detailScore3Note$counter' value='" . $record['score3_note'] . "' />
      <input type=hidden name='detailScore4Note$counter' id='detailScore4Note$counter' value='" . $record['score4_note'] . "' />
      <input type=hidden name='detailScore5Note$counter' id='detailScore5Note$counter' value='" . $record['score5_note'] . "' />
      <a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
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
  }
  $dataHrdEvaluationCriteria = new cHrdEvaluationCriteria();
  $dataHrdEvaluationCriteria->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdEvaluationCriteria->strMessage;
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