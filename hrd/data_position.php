<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_position.php');
include_once('../classes/hrd/hrd_employee.php');
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
$db = new CdbClass;
if ($db->connect()) {
  $strDataID = getPostValue('dataID');
  $isNew = ($strDataID == "");
  $strSet = "position";
  $strDataApprover = (isset($_REQUEST['approver_id'])) ? $_REQUEST['approver_id'] : $strDataApprover = "";
  if ($bolCanEdit) {
    $f = new clsForm("formInput", 3, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    //    $dataOrganizationStructure = $dataHrdOrganization->generateList(null, "levelling", null, "id", "name");
    $f->addHidden("dataID", $strDataID);
    $f->addInput(
        getWords("level code"),
        "position_code",
        "",
        ["size" => 25, "maxlength" => 15],
        "string",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("level name"),
        "position_name",
        "",
        ["size" => 100, "maxlength" => 63],
        "string",
        true,
        true,
        true
    );
    $f->addSelect(
        getWords("level group"),
        "position_group",
        getDataListPositionGroup(),
        [],
        "numeric",
        true,
        true,
        true
    );
    $f->addInput(getWords("level"), "level_val", "0", ["size" => 30, "maxlength" => 10], "numeric", false, true, true);
    $f->addSelect(getWords("get overtime"), "get_ot", getDataListGetOvertime(), [], "numeric", true, true, true);
    $f->addCheckBox(
        getWords("get auto overtime"),
        "get_auto_ot",
        false,
        [],
        "string",
        false,
        true,
        true,
        "",
        ""
    );
    $f->addInput(
        getWords("overtime limit(rupiah)"),
        "ot_limit",
        "0",
        ["size" => 30, "maxlength" => 10],
        "numeric",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("overtime meal/transport fee"),
        "ot_meal_fee",
        "0",
        ["size" => 30, "maxlength" => 10],
        "numeric",
        false,
        true,
        true
    );
    // $f->addInput(getWords("minimum overtime duration for meal/transport fee(hour)"), "ot_meal_min_duration", "0",   array("size" => 30, "maxlength" => 10), "numeric", false, true, true);
    for ($i = 1; $i <= MAX_ALLOWANCE_SET; $i++) {
      $f->addInput(
          getSetting($strSet . $i . "_allowance_name"),
          $strSet . $i,
          "0",
          ["size" => 30, "maxlength" => 10],
          "numeric",
          false,
          true,
          true
      );
    }
    $f->addTextArea(getWords("note"), "note", "", ["cols" => 97, "rows" => 2], "string", false, true, true);
    // $f->addInputAutoComplete(getWords("approver ID"), "approver_id", getDataEmployee($strDataApprover), "", "string", false, true, true,"","",true,null,"../global/hrd_ajax_source.php?action=getemployee");
    // $f->addLabelAutoComplete("", "approver_id", "");
    $f->addSubmit(
        "btnSave",
        getWords("save"),
        ["onClick" => "javascript:myClient.confirmSave();"],
        true,
        true,
        "",
        "",
        "saveData()"
    );
    $f->addButton("btnAdd", getWords("add new"), ["onClick" => "javascript:myClient.editData('0|position|3');"]);
    $formInput = $f->render();
  } else {
    $formInput = "";
  }
  $myDataGrid = new cDataGrid("formData", "DataGrid1");
  $myDataGrid->caption = strtoupper($strWordsLISTOF . " " . getWords($dataPrivilege['menu_name']));
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "position_code", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
  );
  $myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no."), "", ['width' => '30'], ['nowrap' => '']));
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("level code"), "position_code", ['width' => '130'], ['nowrap' => ''])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("level name"), "position_name", ['width' => '200'], ['nowrap' => ''])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("level group"),
          "position_group",
          ["width" => 100],
          ['align' => 'center'],
          true,
          true,
          "",
          "printPositionGroup()"
      )
  );
  $myDataGrid->addColumn(new DataGrid_Column(getWords("level"), "level", ['width' => '200'], ['nowrap' => '']));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("get overtime"),
          "get_ot",
          ["width" => 30],
          ['align' => 'center'],
          true,
          true,
          "",
          "printGetOvertime()"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("get auto overtime"),
          "get_auto_ot",
          ["width" => 70],
          ['align' => 'center'],
          true,
          false,
          "",
          "printAutoOT()"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("overtime limit (rupiah)"), "ot_limit", ['width' => '200'], ['nowrap' => ''])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("overtime meal/transport fee"), "ot_meal_fee", ['width' => '200'], ['nowrap' => ''])
  );
  // $myDataGrid->addColumn(new DataGrid_Column(getWords("minimum overtime duration for meal/transport fee"), "ot_meal_min_duration", array('width' => '200'), array('nowrap' => '')));
  for ($i = 1; $i <= MAX_ALLOWANCE_SET; $i++) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getSetting($strSet . $i . "_allowance_name"),
            $ARRAY_ALLOWANCE_SET[$strSet]['field_name'] . $i,
            ['width' => '80'],
            ['align' => 'right'],
            true,
            true,
            "",
            "formatNumber()"
        )
    );
  }
  // $myDataGrid->addColumn(new DataGrid_Column(getWords("approver ID"), "approver_id", array('width' => '200'), array('nowrap' => '')));
  $myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", null, ['nowrap' => '']));
  if ($bolCanEdit) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "",
            "",
            ['width' => '60'],
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
  //tampilkan buttons sesuai dengan otoritas, common_function.php
  generateRoleButtons($bolCanEdit, $bolCanDelete, $bolCanCheck, $bolCanApprove, $bolCanAcknowledge, false, $myDataGrid);
  $myDataGrid->addButtonExportExcel(
      getWords("export excel"),
      $dataPrivilege['menu_name'] . ".xls",
      getWords($dataPrivilege['menu_name'])
  );
  $myDataGrid->getRequest();
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_position ";
  $strSQL = "SELECT * FROM hrd_position ";
  $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
  $dataset = $myDataGrid->getData($db, $strSQL);
  //bind Datagrid with array dataset
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
  $strConfirmSave = getWords("do you want to save this entry?");
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('position data management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
//--------------------------------------------------------------------------------
function printEditLink($params)
{
    global $ARRAY_ALLOWANCE_SET, $strSet;
    $strResult = "";
    extract($params);
    for ($i = 1; $i <= MAX_ALLOWANCE_SET; $i++)
        $strResult .= "<input type=hidden name='detailAllowance".$i."_$counter' id='detailAllowance".$i."_$counter' value='".$record[$ARRAY_ALLOWANCE_SET[$strSet]['field_name'].$i]."' />";
    return "
      <input type=hidden name='detailID$counter' id='detailID$counter' value='".$record['position_code']."' />
      <input type=hidden name='detailCode$counter' id='detailCode$counter' value='".$record['position_code']."' />
      <input type=hidden name='detailName$counter' id='detailName$counter' value='".$record['position_name']."' />
      <input type=hidden name='detailNote$counter' id='detailNote$counter' value='".$record['note']."' />
      <input type=hidden name='detailPositionGroup$counter' id='detailPositionGroup$counter' value='".$record['position_group']."' />
      <input type=hidden name='detailGetOvertime$counter' id='detailGetOvertime$counter' value='".$record['get_ot']."' />
      <input type=hidden name='detailGetAutoOvertime$counter' id='detailGetAutoOvertime$counter' value='".$record['get_auto_ot']."' />
      <input type=hidden name='detailOvertimeLimit$counter' id='detailOvertimeLimit$counter' value='" . $record['ot_limit'] . "' />
      <input type=hidden name='detailOvertimeMealFee$counter' id='detailOvertimeMealFee$counter' value='" . $record['ot_meal_fee'] . "' />
      <input type=hidden name='detailApproverID$counter' id='detailApproverID$counter' value='".$record['approver_id']."' />
      <input type=hidden name='detailLevel$counter' id='detailLevel$counter' value='" . $record['level'] . "' />
      <a id=\"editdata-$counter\" class=\"edit-data\" href=\"javascript:myClient.editData('$counter"."|$strSet|".MAX_ALLOWANCE_SET."')\">" .getWords('edit'). "</a>".$strResult;
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $db;
  global $error;
  global $isNew;
  global $strSet;
  global $ARRAY_ALLOWANCE_SET;
  $strDataCode = $f->getValue('position_code');
  $dataHrdPosition = new cHrdPosition();
  $dataHrdEmployee = new cHrdEmployee();
  $data = [
      "position_code"  => $strDataCode,
      "position_name"  => $f->getValue('position_name'),
      "position_group" => ($f->getValue('position_group') == '') ? null : intval($f->getValue('position_group')),
      "note"           => $f->getValue('note'),
      "level"          => $f->getValue('level_val'),
      "get_ot"         => $f->getValue('get_ot'),
      "ot_limit"       => $f->getValue('ot_limit'),
      "ot_meal_fee"    => $f->getValue('ot_meal_fee'),
      // "ot_meal_min_duration" => $f->getValue('ot_meal_min_duration'),
      "get_auto_ot"    => (($f->getValue('get_auto_ot')) ? 't' : 'f')
  ];
  for ($i = 1; $i <= MAX_ALLOWANCE_SET; $i++) {
    $data[$ARRAY_ALLOWANCE_SET[$strSet]['field_name'] . $i] = $f->getValue($strSet . $i);
  }
  $data2 = ["position_code" => $f->getValue('position_code')];
  // simpan data -----------------------
  $bolSuccess = false;
  // data baru
  if ($isNew) {
    if (isDataExists($db, $ARRAY_ALLOWANCE_SET[$strSet]['table_name'], $strSet . "_code", $strDataCode)) {
      $f->message = $error['duplicate_code'] . " of $strSet -> $strDataCode";
    }
    $bolSuccess = $dataHrdPosition->insert($data);
  } else {
    $bolSuccess = $dataHrdPosition->update(/*pk*/
        "position_code='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
    $dataHrdEmployee->update(/*pk*/
        "position_code='" . $f->getValue('dataID') . "'", /*data to update*/
        $data2
    );
  }
  if ($bolSuccess) {
    $f->setValue('dataID', $data['position_code']);
    $f->message = $dataHrdPosition->strMessage;
  }
  $f->msgClass = ($bolSuccess) ? "bgOK" : "bgError";
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['position_code'][] = $strValue;
  }
  $dataHrdPosition = new cHrdPosition();
  $dataHrdPosition->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdPosition->strMessage;
} //deleteData
// fungsi untuk menampilkan group posisi
function getDataListPositionGroup($default = null, $isHasEmpty = false, $emptyData = null)
{
  global $ARRAY_POSITION_GROUP;
  $arrData = [];
  if ($isHasEmpty) {
    $arrData[] = $emptyData;
  }
  foreach ($ARRAY_POSITION_GROUP as $key => $value) {
    if ($key == $default) {
      $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
    } else {
      $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
    }
  }
  return $arrData;
}

function getDataListGetOvertime($default = 1, $isHasEmpty = false, $emptyData = false)
{
  global $ARRAY_GET_OT;
  $arrData = [];
  if ($isHasEmpty) {
    $arrData[] = $emptyData;
  }
  foreach ($ARRAY_GET_OT as $key => $value) {
    if ($key == $default) {
      $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
    } else {
      $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
    }
  }
  return $arrData;
}

function printAutoOT($params)
{
  extract($params);
  if ($value == 't') {
    return "V";
  } else {
    return "-";
  }
}

// print Position Group
function printPositionGroup($params)
{
  global $ARRAY_POSITION_GROUP;
  extract($params);
  if ($record['position_group'] == "") {
    return "";
  } else {
    return getWords($ARRAY_POSITION_GROUP[$record['position_group']]);
  }
}  // print Position Group
/*  function printFormatBasicSalary($params)
  {
    extract($params);
    return number_format($record['basic_salary']);
  }
  function printFormatSeniorityAllowance($params)
  {
    extract($params);
    return number_format($record['seniority_allowance']);
  }
  function printFormatHousingAllowance($params)
  {
    extract($params);
    return number_format($record['housing_allowance']);
  }
    function printIsLeader($params)
  {
    extract($params);
    if ($value == SQL_TRUE)
      return "*";
    else
      return "";
  }*/
function printGetOvertime($params)
{
  global $ARRAY_GET_OT;
  extract($params);
  if ($record['get_ot'] == "") {
    return "";
  } else {
    return getWords($ARRAY_GET_OT[$record['get_ot']]);
  }
}

?>
