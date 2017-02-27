<?php
error_reporting(0);
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_family_status.php');
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
  $strSet = "family_status";
  if ($bolCanEdit) {
    $f = new clsForm("formInput", 2, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    if ($isNew) {
      $f->addInput(getWords("code"), "dataCode", "", ["size" => 30, "maxlength" => 31], "string", true, true, true);
    } else {
      $f->addInput(
          getWords("code"),
          "dataCode",
          "",
          ["size" => 30, "maxlength" => 31, "readOnly" => true],
          "string",
          true,
          true,
          true
      );
    }
    //$f->addSelect(getWords("marital status"), "dataMaritalStatus", getDataListMaritalStatus(), array(), "string", true, true, true);
    $f->addInput(
        getWords("number of children"),
        "dataChildren",
        0,
        ["size" => 3, "maxlength" => 3],
        "integer",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("tax reduction"),
        "dataTaxReduction",
        0,
        ["size" => 10, "maxlength" => 10],
        "numeric",
        true,
        true,
        true
    );
    for ($i = 1; $i <= MAX_ALLOWANCE_SET; $i++) {
      $f->addInput(
          getSetting($strSet . $i . "_allowance_name"),
          $strSet . $i,
          "0",
          ["size" => 30, "maxlength" => 10],
          "numeric",
          true,
          true,
          true
      );
    }
    $f->addTextArea(
        getWords("note"),
        "dataNote",
        "",
        ["cols" => 48, "rows" => 2, "maxlength" => 127],
        "string",
        false,
        true,
        true
    );
    $f->addSubmit("btnSave", getWords("save"), "", true, true, "", "", "saveData()");
    $f->addButton("btnAdd", getWords("add new"), ["onClick" => "javascript:myClient.editData('0|family_status|3');"]);
    $formInput = $f->render();
  } else {
    $formInput = "";
  }
  $myDataGrid = new cDataGrid("formData", "DataGrid1");
  $myDataGrid->caption = strtoupper($strWordsLISTOF . " " . getWords($dataPrivilege['menu_name']));
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "family_status_code", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
  );
  $myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no."), "", ['width' => '30'], ['nowrap' => '']));
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("code"), "family_status_code", ['width' => '70'], ['nowrap' => ''])
  );
  //$myDataGrid->addColumn(new DataGrid_Column(getWords("marital status"), "marital_status", array('width' => '100'), array('align' => 'center'), true, false, "", "printMaritalStatus()"));
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("children"), "children", ['width' => '80'], ['align' => 'center'])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("tax reduction"),
          "tax_reduction",
          ['width' => '100'],
          ['align' => 'right'],
          true,
          true,
          "",
          "formatNumber()"
      )
  );
  for ($i = 1; $i <= MAX_ALLOWANCE_SET; $i++) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getSetting($strSet . $i . "_allowance_name"),
            $ARRAY_ALLOWANCE_SET[$strSet]['field_name'] . $i,
            ['width' => '100'],
            ['align' => 'right'],
            true,
            true,
            "",
            "formatNumber()"
        )
    );
  }
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
  $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_family_status ";
  $strSQL = "SELECT * FROM hrd_family_status ";
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
$strPageDesc = getWords("family status management");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
function printEditLink($params)
{
  global $ARRAY_ALLOWANCE_SET, $strSet;
  $strResult = "";
  extract($params);
  for ($i = 1; $i <= MAX_ALLOWANCE_SET; $i++) {
    $strResult .= "<input type=hidden name='detailAllowance" . $i . "_$counter' id='detailAllowance" . $i . "_$counter' value='" . $record[$ARRAY_ALLOWANCE_SET[$strSet]['field_name'] . $i] . "' />";
  }
  return "
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['family_status_code'] . "' />
      <input type=hidden name='detailCode$counter' id='detailCode$counter' value='" . $record['family_status_code'] . "' />
      <input type=hidden name='detailTaxReduction$counter' id='detailTaxReduction$counter' value='" . $record['tax_reduction'] . "' />
      <input type=hidden name='detailChildren$counter' id='detailChildren$counter' value='" . $record['children'] . "' />
      <input type=hidden name='detailNote$counter' id='detailNote$counter' value='" . $record['note'] . "' />
      <a href=\"javascript:myClient.editData('$counter" . "|$strSet|" . MAX_ALLOWANCE_SET . "')\">" . getWords(
      'edit'
  ) . "</a>" . $strResult;
}

function printMaritalStatus($params)
{
  global $ARR_DATA_MARITAL_STATUS;
  extract($params);
  if ($record['marital_status'] == "") {
    return "";
  } else {
    return getWords($ARR_DATA_MARITAL_STATUS[$record['marital_status']]);
  }
}

function saveData()
{
  global $f;
  global $db;
  global $error;
  global $isNew;
  global $strSet;
  global $ARRAY_ALLOWANCE_SET;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $strDataCode = $f->getValue('dataCode');
  $dataHrdFamilyStatus = new cHrdFamilyStatus();
  $data = [
      "family_status_code" => $strDataCode,
      "children"           => intval($f->getValue('dataChildren')),
      "tax_reduction"      => floatval($f->getValue('dataTaxReduction')),
      "note"               => pg_escape_string($f->getValue('dataNote'))
  ];
  for ($i = 1; $i <= MAX_ALLOWANCE_SET; $i++) {
    $data[$ARRAY_ALLOWANCE_SET[$strSet]['field_name'] . $i] = $f->getValue($strSet . $i);
  }
  // simpan data -----------------------
  $bolSuccess = false;
  if ($isNew) {
    // data baru
    if (isDataExists($db, $ARRAY_ALLOWANCE_SET[$strSet]['table_name'], $strSet . "_code", $strDataCode)) {
      $f->message = $error['duplicate_code'] . " of $strSet -> $strDataCode";
    }
    $bolSuccess = $dataHrdFamilyStatus->insert($data);
  } else {
    $bolSuccess = $dataHrdFamilyStatus->update(/*pk*/
        "family_status_code='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  if ($bolSuccess) {
    $f->setValue('dataID', $data['family_status_code']);
    $f->message = $dataHrdFamilyStatus->strMessage;
  }
  $f->msgClass = ($bolSuccess) ? "bgOK" : "bgError";
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['family_status_code'][] = $strValue;
  }
  $dataHrdFamilyStatus = new cHrdFamilyStatus();
  $dataHrdFamilyStatus->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdFamilyStatus->strMessage;
} //deleteData
?>