<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_evaluation_criteria_employee.php');
$dataPrivilege = getDataPrivileges("evaluation_list.php", $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
//---- INISIALISASI ----------------------------------------------------
$strWordsEvaluationCategory = getWords("evaluation category");
$strWordsGeneralKRA = getWords("general kra");
$strWordsEmployeeKRA = getWords("employee kra");
$strWordsEvaluationScoreSetting = getWords("evaluation score setting");
$intMainRow = 0;
$db = new CdbClass;
$db->connect();
$strDataID = getPostValue('dataID');
$strDataEmployee = getPostValue('dataEmployee');
$strDataYear = (getPostValue('dataYear') == "") ? date("Y") : getPostValue('dataYear');
$strHideHistory = (getPostValue('dataHideHistory') == "") ? "t" : getPostValue('dataHideHistory');;
$strDataIDCategory = getPostValue('dataIDCategory');
$arrEmployee = ["id" => "", "employee_name" => ""];
if ($strDataEmployee != "") {
  $arrEmployee = getEmployeeInfoByCode($db, $strDataEmployee, "id , employee_name ");
  $dataHrdEvaluationCriteriaEmployee = new cHrdEvaluationCriteriaEmployee();
  $arrTemp = $dataHrdEvaluationCriteriaEmployee->find(
      "active = 't' AND is_last_updated = 't' AND id_employee = '" . $arrEmployee['id'] . "' AND year = $strDataYear",
      "SUM (weight) AS total_weight"
  );
  $fltRemainingWeight = (100 - $arrTemp['total_weight'] > 0) ? 100 - $arrTemp['total_weight'] : 0;
}
$isNew = ($strDataID == "");
$fFilter = new clsForm("formFilter", 6, "100%", "");
$fFilter->caption = strtoupper(strtoupper("employee id"));
$fFilter->addInputAutoComplete(
    getWords("employee ID"),
    "dataEmployee",
    getDataEmployee($strDataEmployee),
    ["size" => 50],
    "string",
    false
);
$fFilter->addSelect(getWords("year"), "dataYear", getDataListYear($strDataYear), "", "", false);
$fFilter->addLiteral(
    "",
    "buttonShow",
    generateButton("btnShow", "Show", "", "onclick = \"document.formFilter.submit()\"")
);
$fFilter->hasButton = false;
$formFilter = $fFilter->render();
if ($bolCanEdit) {
  $f = new clsForm("formInput", 2, "100%", "");
  $f->caption = strtoupper("input data " . $strWordsEmployeeKRA);
  $f->addHidden("dataID", $strDataID);
  $f->addSelect(
      getWords("evaluation category"),
      "dataIDCategory",
      getDataListEvaluationCategory($strDataIDCategory, false, false),
      ["style" => "width:270"]
  );
  $f->addInput(
      getWords("employee"),
      "dataEmployeeTemp",
      $strDataEmployee,
      ["size" => 50],
      "integer",
      true,
      false,
      true
  );
  $f->addInput(getWords("weight"), "dataWeight", $fltRemainingWeight, ["size" => 5], "integer", true, true, true);
  $f->addInput(
      getWords("target date"),
      "dataTargetDate",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addTextArea(
      getWords("key result area"),
      "dataCriteriaTemp",
      "",
      ["cols" => 50, "rows" => 3],
      "string",
      true,
      true,
      true
  );
  $f->addTextArea(
      getWords("target achievement"),
      "dataTargetAchievement",
      "",
      ["cols" => 50, "rows" => 3],
      "string",
      false,
      true,
      true
  );
  $f->addCheckBox(getWords("active"), "dataActive", true);
  $f->addLiteral("", "dataScoreTable", getScoreTable($strDataID));
  $f->addTextArea(
      getWords("changes note"),
      "dataChangesNote",
      "New",
      ["cols" => 50, "rows" => 3],
      "string",
      true,
      true,
      true
  );
  $f->addLiteral("", "", "");
  $f->addLiteral("", "", "");
  $f->addLiteral("", "", "");
  $f->addLiteral("", "", "");
  $f->addHidden("dataEmployee", $strDataEmployee);
  $f->addHidden("dataEmployeeID", $arrEmployee['id']);
  $f->addHidden("dataYear", $strDataYear);
  $f->addHidden("dataCriteria", "");
  $f->addHidden("dataIDParent", "");
  $f->addHidden("dataEndorse", "");
  $f->addHidden("dataIsLastUpdated", "t");
  $f->addHidden("dataHideHistory", $strHideHistory);
  $f->addSubmit(
      "btnSave",
      getWords("save"),
      ["onClick" => "javascript:return myClient.confirmSave();"],
      true,
      true,
      "",
      "",
      "saveData()"
  );
  $f->addButton("btnAdd", getWords("add new"), ["onClick" => "document.formFilter.submit()"]);
  $formInput = $f->render();
  //javascript:myClient.editData(0);
} else {
  $formInput = "";
}
$myDataGrid = new cDataGrid("formData", "DataGrid", "100%", "100%", true, true, false);
$myDataGrid->caption = getWords(strtoupper($dataPrivilege['menu_name']));
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->setPageLimit("all");
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", "", ['width' => '15', 'valign' => 'top', 'align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("no"),
        "",
        "",
        ['width' => '15', 'valign' => 'top', 'align' => 'center', 'nowrap' => ''],
        false,
        false,
        "",
        "printNo()"
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("saved"), "created", "", ['valign' => 'top', 'align' => 'center', 'width' => '50',])
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("key result area"),
        "criteria",
        "",
        ['width' => '200', 'valign' => 'top', 'nowrap' => '']
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("weight"), "weight", "", ['valign' => 'top', 'align' => 'center', 'width' => '50',])
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("target date"),
        "target_date",
        "",
        ['valign' => 'top', 'align' => 'center', 'width' => '50',]
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("target achievement"),
        "target_achievement",
        "",
        ['valign' => 'top', 'align' => 'center', 'width' => '150',]
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("score"),
        "",
        "",
        ['align' => 'center', 'nowrap' => ''],
        false,
        false,
        "",
        "printScoreList()"
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("changes note"),
        "changes_note",
        "",
        ['nowrap' => '', 'valign' => 'top', 'align' => 'center', 'width' => '100']
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("active"),
        "",
        "",
        ['width' => '15', 'valign' => 'top', 'align' => 'center', 'nowrap' => ''],
        false,
        false,
        "",
        "printActiveSymbol()"
    )
);
if ($bolCanEdit) {
  $myDataGrid->addColumn(
      new DataGrid_Column(
          "",
          "",
          "",
          ['width' => '60', 'valign' => 'top', 'align' => 'center', 'nowrap' => ''],
          false,
          false,
          "",
          "printEditLink()"
      )
  );
}
if ($bolCanDelete) {
  $myDataGrid->addSpecialButton(
      "btnDelete",
      "btnDelete",
      "submit",
      getWords("delete"),
      "onClick=\"javascript:return myClient.confirmDelete();\"",
      "deleteData()"
  );
}
//$myDataGrid->addButtonExportExcel("Export Excel", "Employee KRA.xls", getWords("Employee KRA"));
//cek status show/hide history dan sesuaikan tampilan button (disable yang tidak perlu)
if ($strHideHistory == "t") {
  $strDisableShowButton = "";
  $strDisableHideButton = "disabled";
} else {
  $strDisableShowButton = "disabled";
  $strDisableHideButton = "";
}
$myDataGrid->strAdditionalHtml = generateHidden("dataEmployee", $strDataEmployee, "");
$myDataGrid->strAdditionalHtml .= generateHidden("dataHideHistory", $strHideHistory, "");
$myDataGrid->addButton(
    "btnShowHistory",
    "btnShowHistory",
    "submit",
    getWords("show history"),
    "onClick=\"javascript:return myClient.hideHistory('f');\"" . $strDisableShowButton,
    "showHistory()"
);
$myDataGrid->addButton(
    "btnHideHistory",
    "btnHideHistory",
    "submit",
    getWords("hide history"),
    "onClick=\"javascript:return myClient.hideHistory('t');\"" . $strDisableHideButton,
    "hideHistory()"
);
$myDataGrid->getRequest();
if ($myDataGrid->pageSortBy == "sequence_no") {
  $myDataGrid->pageSortBy = "";
}
//$strFilterIDCategory = getPostValue('filterIDCategory');
//$strFilterEmployee   = getPostValue('filterEmployee');
//$strFilterIDEmployee = ($strFilterEmployee != "") ? getIDEmployee($db, $strFilterEmployee)  : "-1";
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_evaluation_criteria_employee ";
$strSQL = "SELECT * FROM hrd_evaluation_criteria_employee ";
if ($arrEmployee['id'] != "" && $strDataYear != "") {
  $strSQL .= "WHERE id_employee = '" . $arrEmployee['id'] . "' AND year = $strDataYear ";
  $strSQLCOUNT .= "WHERE id_employee = '" . $arrEmployee['id'] . "' AND year = $strDataYear ";
} else {
  $strSQLCOUNT .= "WHERE 1=0 ";
  $strSQL .= "WHERE 1=0 ";
}
if ($strHideHistory == "t") {
  $strSQL .= "AND is_last_updated = 't' ";
  $strSQLCOUNT .= "AND is_last_updated = 't' ";
}
$strSQL .= "ORDER BY id_parent, created";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$strConfirmDelete = getWords("are you sure to delete this selected data? All the history of this KRA will be deleted");
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'] . " of " . $arrEmployee['employee_name'];
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
                   <td>" . generateInput(
          "dataScore3Note",
          "",
          "size=100",
          "onChange=\"javascript:myClient.setTargetAchievement(this.value);\""
      ) . "</td></tr><tr>
                   <td>" . generateInput("dataScore4", 2, "size=7") . "</td>
                   <td>" . generateInput("dataScore4Note", "", "size=100") . "</td></tr><tr>
                   <td>" . generateInput("dataScore5", 1, "size=7") . "</td>
                   <td>" . generateInput("dataScore5Note", "", "size=100") . "</td>
                   </tr>
                   </table>";
  return $strResult;
}

function printNo($params)
{
  global $intMainRow;
  extract($params);
  if ($record['is_last_updated'] == 't') {
    return ++$intMainRow;
  } else {
    return "(" . ($intMainRow + 1) . ")";
  }
}

function printActiveSymbol($params)
{
  extract($params);
  return ($record['active'] == 't') ? "&radic;" : "";
}

function printEditLink($params)
{
  global $strDataEmployee;
  extract($params);
  $strResult = "
    <table>
    <tr height=30><td>
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['id'] . "' />
      <input type=hidden name='detailIDParent$counter' id='detailIDParent$counter' value='" . $record['id_parent'] . "' />
      <input type=hidden name='detailIDCategory$counter' id='detailIDCategory$counter' value='" . $record['id_category'] . "' />
      <input type=hidden name='detailEmployee$counter' id='detailEmployee$counter' value='$strDataEmployee' />
      <input type=hidden name='detailCriteria$counter' id='detailCriteria$counter' value='" . $record['criteria'] . "' />
      <input type=hidden name='detailWeight$counter' id='detailWeight$counter' value='" . $record['weight'] . "' />
      <input type=hidden name='detailTargetDate$counter' id='detailTargetDate$counter' value='" . $record['target_date'] . "' />
      <input type=hidden name='detailTargetAchievement$counter' id='detailTargetAchievement$counter' value='" . $record['target_achievement'] . "' />
      <input type=hidden name='detailActive$counter' id='detailActive$counter' value='" . $record['active'] . "' />
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
      <input type=hidden name='detailChangesNote$counter' id='detailChangesNote$counter' value='" . $record['changes_note'] . "' />
      <input type=hidden name='detailIsLastUpdated$counter' id='detailIsLastUpdated$counter' value='" . $record['is_last_updated'] . "' />";
  if ($record['is_last_updated'] == 't') {
    $strResult .= "<a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>
                    </td></tr>
                    <tr><td>
                    <a href=\"javascript:myClient.endorseData($counter)\">" . getWords('endorse') . "</a>";
  }
  $strResult .= " </td></tr></table>";
  return $strResult;
}

// fungsi untuk menampilkan history, cukup assign filter utk datagrid
function showHistory()
{
}

// fungsi untuk menampilkan history, cukup assign filter utk datagrid
function hideHistory()
{
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $isNew;
  $arrScore = [];
  $db = new CdbClass;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $dataHrdEvaluationCriteriaEmployee = new cHrdEvaluationCriteriaEmployee();
  $strIDEmployee = $f->getValue('dataEmployeeID');
  global $fltRemainingWeight;
  $bolSave = true;
  //harus isi field karyawan
  if ($strIDEmployee == "") {
    $bolSave = false;
    $f->message = $dataHrdEvaluationCriteriaEmployee->strMessage;
  } //cek total persentase utk data baru
  else {
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
  }
  if ($bolSave) {
    for ($i = 1; $i <= 5; $i++) {
      $arrScore['dataScore' . $i] = getPostValue('dataScore' . $i);
      $arrScore['dataScore' . $i . 'Note'] = getPostValue('dataScore' . $i . 'Note');
    }
    $data = [
        "id_category" => $f->getValue('dataIDCategory'),
        "id_employee" => $strIDEmployee,
        "year" => $f->getValue('dataYear'),
        "criteria" => $f->getValue('dataCriteriaTemp'),
        "weight" => floatVal($f->getValue('dataWeight')),
        "target_date" => $f->getValue('dataTargetDate'),
        "target_achievement" => $f->getValue('dataTargetAchievement'),
        "changes_note" => $f->getValue('dataChangesNote'),
        "score1" => $arrScore['dataScore1'],
        "score2" => $arrScore['dataScore2'],
        "score3" => $arrScore['dataScore3'],
        "score4" => $arrScore['dataScore4'],
        "score5" => $arrScore['dataScore5'],
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
      $data['is_last_updated'] = "t";
      $bolSuccess = $dataHrdEvaluationCriteriaEmployee->insert($data);
    } else {
      if ($f->getValue('dataEndorse') == "true") {
        // data endorsement. Tambahkan record yang merupaka  update dari record utama
        // beri flag is_last_updated = true pada record ini , dan false pada record lain dengan id_parent yang sama
        $data['id_parent'] = $f->getValue('dataIDParent');
        $data['criteria'] = $f->getValue('dataCriteria');
        $bolSuccess = $dataHrdEvaluationCriteriaEmployee->insert($data);
        $strLastID = $dataHrdEvaluationCriteriaEmployee->getLastInsertId();
        $arrTemp = $dataHrdEvaluationCriteriaEmployee->find(["id" => $strLastID], "id_parent");
        $dataHrdEvaluationCriteriaEmployee->update(
            "id_parent='" . $arrTemp['id_parent'] . "'",
            ["is_last_updated" => "f"]
        );
        $dataHrdEvaluationCriteriaEmployee->update("id='" . $strLastID . "'", ["is_last_updated" => "t"]);
      } else {
        $bolSuccess = $dataHrdEvaluationCriteriaEmployee->update(/*pk*/
            "id='" . $f->getValue('dataID') . "'", /*data to update*/
            $data
        );
        $dataHrdEvaluationCriteriaEmployee->update(
            "id_parent='" . $f->getValue('dataIDParent') . "'",
            ["criteria" => $f->getValue('dataCriteriaTemp')]
        );
      }
    }
    $f->message = $dataHrdEvaluationCriteriaEmployee->strMessage;
  }
  if ($bolSuccess) {
    if (isset($data['id'])) {
      $f->setValue('dataID', $data['id']);
    } else {
      $f->setValue('dataID', "");
    }
    if ($isNew) {
      //berikan parent id sesuai dengan id pada data baru. Mempermudah sorting pada datagrid (by parent_id, created)
      $arrTemp = ["id_parent" => $dataHrdEvaluationCriteriaEmployee->getLastInsertId()];
      $dataHrdEvaluationCriteriaEmployee->update(/*pk*/
          "id='" . $dataHrdEvaluationCriteriaEmployee->getLastInsertId() . "'", /*data to update*/
          $arrTemp
      );
    }
  }
  $f->msgClass = ($bolSuccess) ? "bgOK" : "bgError";
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  $dataHrdEvaluationCriteriaEmployee = new cHrdEvaluationCriteriaEmployee();
  foreach ($myDataGrid->checkboxes as $strValue) {
    //hapus semua record dengan id parent yang sama
    $arrTemp = $dataHrdEvaluationCriteriaEmployee->find(["id" => $strValue], "id_parent");
    $arrTemp = $dataHrdEvaluationCriteriaEmployee->findAll(["id_parent" => $arrTemp['id_parent']], "id");
    foreach ($arrTemp as $strKey => $arrValue) {
      $arrKeys['id'][] = $arrValue['id'];
    }
  }
  $dataHrdEvaluationCriteriaEmployee->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdEvaluationCriteriaEmployee->strMessage;
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