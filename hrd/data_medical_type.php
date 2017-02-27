<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_medical_type.php');
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
$strWordsTreatmentTypeSetting = getWords("treatment type setting");
$strWordsQuotaSetting = getWords("quota setting");
$strWordsExtendedQuota = getWords("extended quota");
$db = new CdbClass;
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
if ($bolCanEdit) {
  $f = new clsForm("formInput", 1, "100%", "");
  $f->caption = strtoupper($strWordsINPUTDATA);
  $f->addHidden("dataID", $strDataID);
  $f->addSelect(
      getWords("treatment type"),
      "dataType",
      getDataListMedicalTreatmentType("", false, null),
      ["style" => "width:270"]
  );
  $f->addInput(
      getWords("treatment code"),
      "dataCode",
      "",
      ["size" => 50, "maxlength" => 30],
      "string",
      true,
      true,
      true
  );
  //$f->addCheckBox(getWords("permanent only"), "dataPermanentOnly", true );
  //$f->addCheckBox(getWords("prorate"), "dataProrate", true);
  $f->addCheckBox(getWords("permanent only"), "dataPermanentOnly", true, false, true, "", "<br>&nbsp;<br>");
  $f->addCheckBox(getWords("prorate"), "dataProrate", true, false, true, "", "<br>&nbsp;<br>");
  $f->addTextArea(getWords("note"), "dataNote", "", ["cols" => 50, "rows" => 2], "string", false, true, true);
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
  $f->addButton("btnAdd", getWords("add new"), ["onClick" => "javascript:myClient.editData(0);"]);
  $formInput = $f->render();
} else {
  $formInput = "";
}
$myDataGrid = new cDataGrid("formData", "DataGrid1");
$myDataGrid->caption = strtoupper($strWordsLISTOF . " " . $dataPrivilege['menu_name']);
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
if (!isset($_REQUEST['btnExportXLS'])) {
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
  );
}
$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("treatment type"),
        "type",
        ['width' => '150'],
        ['nowrap' => ''],
        true,
        true,
        "",
        "printTreatmentTypeName()"
    )
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("treatment code"), "code", ['width' => '150'], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("permanent only"),
        "permanent_only",
        "",
        ['width' => '15', 'valign' => 'top', 'align' => 'center', 'nowrap' => ''],
        false,
        false,
        "",
        "printActiveSymbol()"
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("prorate"),
        "prorate",
        "",
        ['width' => '15', 'valign' => 'top', 'align' => 'center', 'nowrap' => ''],
        false,
        false,
        "",
        "printActiveSymbol()"
    )
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", ""));
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
      "Delete",
      "onClick=\"javascript:return myClient.confirmDelete();\"",
      "deleteData()"
  );
}
$myDataGrid->addButtonExportExcel(
    getWords("export excel"),
    $dataPrivilege['menu_name'] . ".xls",
    getWords($dataPrivilege['menu_name'])
);
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_medical_type";
$strSQL = "SELECT * FROM hrd_medical_type ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("medical type data management");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = medicalTypeSubmenu($strWordsTreatmentTypeSetting);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
function printTreatmentTypeName($params)
{
  global $ARRAY_MEDICAL_TREATMENT_GROUP;
  extract($params);
  return (isset($ARRAY_MEDICAL_TREATMENT_GROUP[$value])) ? $value . " - " . $ARRAY_MEDICAL_TREATMENT_GROUP[$value] : "UNKNOWN";
}

function printEditLink($params)
{
  extract($params);
  return "
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['id'] . "' />
      <input type=hidden name='detailType$counter' id='detailType$counter' value='" . $record['type'] . "' />
      <input type=hidden name='detailCode$counter' id='detailCode$counter' value='" . $record['code'] . "' />
      <input type=hidden name='detailPermanentOnly$counter' id='detailPermanentOnly$counter' value='" . $record['permanent_only'] . "' />
      <input type=hidden name='detailProrate$counter' id='detailProrate$counter' value='" . $record['prorate'] . "' />
      <input type=hidden name='detailNote$counter' id='detailNote$counter' value='" . $record['note'] . "' />
      <a href=\"javascript:myClient.editData($counter)\">" . getWords('edit') . "</a>";
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $db;
  global $error;
  $isNew = ($f->getValue('dataID') == "");
  $strModifiedByID = $_SESSION['sessionUserID'];
  // cek validasi -----------------------
  $strKriteria = ($isNew) ? "" : "AND id <> '" . $f->getValue('dataID') . "' ";
  if (isDataExists(
      $db,
      "hrd_medical_type",
      "code",
      $f->getValue('dataCode'),
      "AND \"type\" = " . $f->getValue('dataType') . $strKriteria
  )) {
    $f->message = $error['duplicate_code'] . "  -> " . $f->getValue('dataCode');
    $f->msgClass = "bgError";
    return false;
  }
  $dataMedicalType = new cHrdMedicalType();
  $data = [
      "type" => $f->getValue('dataType'),
      "code" => $f->getValue('dataCode'),
      "permanent_only" => ($f->getValue('dataPermanentOnly') == "") ? "f" : "t",
      "prorate" => ($f->getValue('dataProrate') == "") ? "f" : "t",
      "note" => $f->getValue('dataNote')
  ];
  // simpan data -----------------------
  $bolSuccess = false;
  if ($isNew) {
    // data baru
    $bolSuccess = $dataMedicalType->insert($data);
  } else {
    $bolSuccess = $dataMedicalType->update(/*pk*/
        "id='" . $f->getValue('dataID') . "'", /*data to update*/
        $data
    );
  }
  if ($bolSuccess) {
    if ($isNew) {
      $f->setValue('dataID', $dataMedicalType->getLastInsertId());
    } else {
      $f->setValue('dataID', $f->getValue('dataID'));
    }
  }
  $f->message = $dataMedicalType->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $dataMedicalType = new cHrdMedicalType();
  $dataMedicalType->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataMedicalType->strMessage;
} //deleteData
?>