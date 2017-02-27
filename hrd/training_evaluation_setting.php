<?php
include_once('../global/session.php');
include_once('../global.php');
include_once('../includes/datagrid/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/adm/adm_menu.php');
include_once('../global/handledata.php');
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
$strWordsTrainingEvaluationQuestion = getWords("questions for training evaluation");
$strWordsTrainingEvaluationPoint = getWords("points for training evaluation");
$strAction = getRequestValue("act");
$strMenuLevel = getRequestValue("level");
$strDataID = getRequestValue("dataID");
$strDataType = getPostValue("question_type");
if ($strDataType == "") {
  $strDataType = getDataFromCookie("question_type");
} else {
  setDataToCookie("question_type", $strDataType);
}
$db = new CdbClass;
$arrModule = getTypeList($db, $strDataType);
if ($strAction == "desc") {
  goSortOrder($db, $strDataType, false);
} else if ($strAction == "asc") {
  goSortOrder($db, $strDataType, true);
}
if ($bolCanEdit) {
  $f = new clsForm("formInput", 1, "100%", "");
  $f->caption = strtoupper(vsprintf(getWords("input data %s"), getWords("menu")));
  $f->addHelp(getWords("help for") . " " . $dataPrivilege['menu_name'], getHelps("master menu"), 8, 167, 400, 300);
  $f->addHidden("dataID");
  $f->addHidden("dataIcon");
  $f->addSelect(
      getWords("question type"),
      "question_type",
      $arrModule,
      ["onChange" => "javascript:myClient.doRefreshMenu()"],
      "string",
      true,
      true,
      true
  );
  $f->addInput(getWords("question category"), "category", "", ["size" => 50], "string", false, true, true);
  $f->addInput(getWords("question"), "question", "", ["size" => 50], "string", true, true, true);
  $f->addSubmit(
      "btnSave",
      getWords("save"),
      ["onClick" => "return confirm('" . getWords('do you want to save this entry?') . "');"],
      true,
      true,
      "",
      "",
      "saveData()"
  );
  $f->addButton("btnAdd", getWords("add new"), ["onClick" => "javascript:myClient.editData(0);"]);
  $formInput = $f->render();
} else {
  $formInput = "";
}
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
if ($bolCanDelete) {
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
  );
}
$myDataGrid->addColumnNumbering(new DataGrid_Column("No.", "", ['width' => '30'], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("question type"),
        "question_type",
        ['width' => ''],
        ['nowrap' => 'nowrap'],
        false,
        false,
        "",
        ""
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("question category"),
        "category",
        ['width' => ''],
        ['nowrap' => 'nowrap'],
        false,
        false,
        "",
        ""
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("question"),
        "question",
        ['width' => '600'],
        ['nowrap' => 'nowrap'],
        false,
        false,
        "",
        ""
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("edit"),
        "",
        ['width' => '40'],
        ['nowrap' => 'nowrap', 'align' => 'center'],
        true,
        false,
        "",
        "printEditLink()"
    )
);
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
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
/*
$strSQLCOUNT  = "
  SELECT COUNT(*) AS total
    FROM
      (SELECT m.*, p.name AS parent_menu_name
        FROM adm_menu AS m LEFT JOIN adm_menu AS p
          ON m.parent_id_adm_menu = p.id_adm_menu
          WHERE m.id_adm_module = '$strDataType'
       ORDER BY m.menu_level, m.sequence_no) AS x
    WHERE 1=1 ";
    */
$strSQL = "
    SELECT * 
      FROM hrd_training_evaluation_question
      WHERE 1=1 
      ORDER BY category, question
  ";
$strSQLCOUNT = "SELECT COUNT(*) FROM ( $strSQL ) AS tmp WHERE 1=1 ";
$dataset = $myDataGrid->getData($db, $strSQL);
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$predataset = $myDataGrid->getData($db, $strSQL);
$counter = 0;
if (count($dataset) == 0) {
  $dataset = [];
}
/*
foreach ($dataset as &$rowDb)
{
  $counter++;

}
*/
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$myDataGrid->caption = strtoupper(vsprintf(getWords("list of %s"), getWords("question")));
$DataGrid = $myDataGrid->render();
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$strWordMenuList = strtoupper(vsprintf(getWords("list of %s"), getWords("question")));
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
function printEditLink($params)
{
  extract($params);
  $strResult = "
      <input type=\"hidden\" name='detailID$counter' id='detailID$counter' value=\"" . $record['id'] . "\">
      <input type=\"hidden\" name='detailCategory$counter' id='detailCategory$counter' value=\"" . $record['category'] . "\">
      <input type=\"hidden\" name='detailQuestion$counter' id='detailQuestion$counter' value=\"" . $record['question'] . "\">
      <input type=\"hidden\" name='detailQuestionType$counter' id='detailQuestionType$counter' value=\"" . $record['question_type'] . "\">
    ";
  return $strResult . "
      <a href=\"javascript:myClient.editData($counter)\">" . getWords("edit") . "</a>";
}

// fungsi untuk menyimpan data
// fungsi untuk menyimpan data
function saveData()
{
  global $db;
  global $f;
  global $strDataID;
  global $strDataType;
  // cek validasi -----------------------
  if ($f->getValue('question') == "") {
    $f->message = getWords('empty_code');
    return false;
  }
  //    $intWeight = (float)$f->getValue("weight");
  // simpan data -----------------------
  if ($strDataID == "") {
    // data baru
    $strSQL = "
        INSERT INTO hrd_training_evaluation_question
            (category, question_type, question) 
          VALUES('" . $f->getValue('category') . "', '" . $f->getValue('question_type') . "', '" . $f->getValue(
            'question'
        ) . "')";
  } else {
    $strSQL = "
        UPDATE hrd_training_evaluation_question 
          SET category = '" . $f->getValue('category') . "',
              question = '" . $f->getValue('question') . "',
              question_type = '" . $f->getValue('question_type') . "'
        WHERE id = '$strDataID'; 
       ";
  }
  if (executeSaveSQL($strSQL, getWords("menu"), $f->message)) {
    $bolOK = true;
  } else {
    $bolOK = false;
  }
  return $bolOK;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $f;
  global $myDataGrid;
  $strSQL = "";
  foreach ($myDataGrid->checkboxes as $strValue) {
    $strSQL .= "DELETE FROM hrd_training_evaluation_question WHERE id = '$strValue';";
  }
  $bolOK = (executeDeleteSQL($strSQL, getWords("question"), $myDataGrid->message));
  return false;
} //deleteData
// fungsi untuk generate daftar jenis-jenis yang ada
function getTypeList($db, &$default)
{
  $tbl = new cModel();
  $strSQL = "SELECT id,question_type FROM hrd_training_question_type ";
  $resDb = $tbl->query($strSQL);
  foreach ($resDb as $list) {
    $arrModule[$list['id']] = $list['question_type'];
  }
  //	  $arrModule = array();
  //	  while($rowDb = $db->fetchrow($resDb)){
  //	  $arrModule[$rowDb['id']] = $rowDb['question_type'];
  //	  }
  //	  while($a=$db->fetchrow($resDb)){
  //	  echo "dsa";
  //	  }
  //	  print_r($arrModule);
  //    $arrModule = array(
  //      0 => getWords("evaluation for trainer"),
  //      1 => getWords("evaluation for training"),
  //      2 => getWords("post training evaluation")
  //    );
  $arrResult = [];
  $isFirst = true;
  foreach ($arrModule AS $idx => $val) {
    if ($default == "" || $default == "0") {
      if ($isFirst) {
        $isFirst = false;
        $default = $idx;
        $arrResult[] = ["value" => $idx, "text" => $val, "selected" => true];
      } else {
        $arrResult[] = ["value" => $idx, "text" => $val, "selected" => false];
      }
    } else if ($default == $idx) {
      $arrResult[] = ["value" => $idx, "text" => $val, "selected" => true];
    } else {
      $arrResult[] = ["value" => $idx, "text" => $val, "selected" => false];
    }
  }
  return $arrResult;
}//getTypeList
//  function getTypeList(&$default) {
//    $arrResult = array();
//
//    $strSQL = "SELECT id,question_type FROM hrd_training_evaluation_question";
//    $resDb = $db->execute($strSQL);
//    if ($rowDb = $db->fetchrow($resDb)) {
//      $arrResult[] = array("value"=>$rowDb['id'], "text"=>$rowDb['question_type'], "selected" => false);
//    }
//    return $arrResult;
//  }//getEmployeeInfoByCode
?>