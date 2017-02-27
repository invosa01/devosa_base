<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
//include_once('../classes/hrd/hrd_exchange_rate.php');
$ARRAY_MONTH = ["1" => "Jan", "2" => "Feb"];
$ARRAY_YEAR = ["2015" => "2015", "2014" => "2014"];
$ARRAY_CURRENCY = ["1" => "USD", "2" => "YEN"];
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
  if ($bolCanEdit) {
    $f = new clsForm("formInput", 1, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    $f->addSelect(getWords("currency"), "currency_code", getDataListCurrency2(), [], "string", true, true, true);
    $f->addSelect(getWords("month"), "month", getDataListMonth2(), [], "numeric", true, true, true);
    $f->addSelect(getWords("year"), "year", getDataListYear2(), [], "numeric", true, true, true);
    $f->addInput(
        getWords("exchange rate to ") . "IDR (Indonesian Rupiah)",
        "value",
        "",
        ["size" => 10, "maxlength" => 10],
        "numeric",
        false,
        true,
        true,
        "",
        ""
    );
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
  $myDataGrid->caption = strtoupper($strWordsLISTOF . " " . getWords($dataPrivilege['menu_name']));
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
  );
  $myDataGrid->addColumnNumbering(new DataGrid_Column(getWords("no."), "", ['width' => '30'], ['nowrap' => '']));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("currency"),
          "currency_code",
          null,
          ['align' => 'center'],
          true,
          true,
          "",
          "printCurrency()"
      )
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("year"), "year", null, ['align' => 'center'], true, true, "", "printYear()")
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("month"), "month", null, ['align' => 'center'], true, true, "", "printMonth()")
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("exchange rate (to indonesian rupiah)"), "value", null, ['nowrap' => ''])
  );
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
  $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_exchange_rate ";
  $strSQL = "SELECT * FROM hrd_exchange_rate ";
  $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
  $dataset = $myDataGrid->getData($db, $strSQL);
  $myDataGrid->pageSortBy = "year,month";
  //bind Datagrid with array dataset
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
  $strConfirmSave = getWords("do you want to save this entry?");
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('exchange rate data management');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
//--------------------------------------------------------------------------------
function printEditLink($params)
{
  extract($params);
  return "
      <input type=hidden name='detailID$counter' id='detailID$counter' value='" . $record['id'] . "' />
      <input type=hidden name='detailMonth$counter' id='detailMonth$counter' value='" . $record['month'] . "' />
      <input type=hidden name='detailYear$counter' id='detailYear$counter' value='" . $record['year'] . "' />
      <input type=hidden name='detailValue$counter' id='detailValue$counter' value='" . $record['value'] . "' />
      <input type=hidden name='detailCurrency$counter' id='detailCurrency$counter' value='" . $record['currency_code'] . "' />

      <a href=\"javascript:myClient.editData('$counter');\">" . getWords('edit') . "</a>";
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $db;
  global $error;
  global $isNew;
  $dataHrdExchangeRate = new cHrdExchangeRate();
  $data = [
      "currency_code" => $f->getValue('currency_code'),
      "value"         => $f->getValue('value'),
      "month"         => $f->getValue('month'),
      "year"          => $f->getValue('year')
  ];
  // simpan data -----------------------
  $bolSuccess = false;
  // data baru
  if ($isNew) {
    $bolSuccess = $dataHrdExchangeRate->insert($data);
  } else {
    $bolSuccess = $dataHrdExchangeRate->update("id='" . $f->getValue('dataID') . "'", $data);
  }
  if ($bolSuccess) {
    $f->setValue('dataID', $data['id']);
    $f->message = $dataHrdExchangeRate->strMessage;
  }
  $f->msgClass = ($bolSuccess) ? "bgOK" : "bgError";
} // saveData
// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $dataHrdExchangeRate = new cHrdExchangeRate();
  $dataHrdExchangeRate->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataHrdExchangeRate->strMessage;
} //deleteData
// fungsi untuk menampilkan tahun
function getDataListYear2($default = null, $isHasEmpty = false, $emptyData = null)
{
  global $ARRAY_YEAR;
  $arrData = [];
  if ($isHasEmpty) {
    $arrData[] = $emptyData;
  }
  foreach ($ARRAY_YEAR as $key => $value) {
    if ($key == $default) {
      $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
    } else {
      $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
    }
  }
  return $arrData;
}

// fungsi untuk menampilkan tahun
function getDataListCurrency2($default = null, $isHasEmpty = false, $emptyData = null)
{
  global $ARRAY_CURRENCY;
  $arrData = [];
  if ($isHasEmpty) {
    $arrData[] = $emptyData;
  }
  foreach ($ARRAY_CURRENCY as $key => $value) {
    if ($key == $default) {
      $arrData[] = ["value" => $key, "text" => strtoupper(getWords($value)), "selected" => true];
    } else {
      $arrData[] = ["value" => $key, "text" => strtoupper(getWords($value)), "selected" => false];
    }
  }
  return $arrData;
}

// fungsi untuk menampilkan bulan
function getDataListMonth2($default = null, $isHasEmpty = false, $emptyData = null)
{
  global $ARRAY_MONTH;
  $arrData = [];
  if ($isHasEmpty) {
    $arrData[] = $emptyData;
  }
  foreach ($ARRAY_MONTH as $key => $value) {
    if ($key == $default) {
      $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
    } else {
      $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
    }
  }
  return $arrData;
}

// print month
function printMonth($params)
{
  global $ARRAY_MONTH;
  extract($params);
  if ($record['month'] == "") {
    return "";
  } else {
    return getWords($ARRAY_MONTH[$record['month']]);
  }
}  // print month
// print year
function printYear($params)
{
  global $ARRAY_YEAR;
  extract($params);
  if ($record['year'] == "") {
    return "";
  } else {
    return getWords($ARRAY_YEAR[$record['year']]);
  }
}  // print year
// print currency
function printCurrency($params)
{
  global $ARRAY_CURRENCY;
  extract($params);
  if ($record['currency_code'] == "") {
    return "";
  } else {
    return strtoupper(getWords($ARRAY_CURRENCY[$record['currency_code']]));
  }
}  // print currency
?>