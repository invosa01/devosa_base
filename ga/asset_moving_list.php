<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/asset_moving.php');
//================ END INCLUDE=====================================
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
//INISIALISASI---------------------------------------------------------------------------------------------------------------
$strWordsDataEntry = getWords("data entry");
$DataGrid = "";
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, true, false);
// *************************** BEGIN Fungsi ISI DATA GRID  ********************************************************************
function getData($db)
{
  global $dataPrivilege, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck;
  global $f;
  global $DataGrid;
  global $myDataGrid;
  global $strKriteriaCompany;
  //global $arrUserInfo
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if ($arrData['dataIdItem'] != "") {
    $strKriteria .= "AND m.id_item = '" . $arrData['dataIdItem'] . "'";
  }
  if (validStandardDate($arrData['dataMovingDateFrom']) && validStandardDate($arrData['dataMovingDateThru'])) {
    $strKriteria .= "AND (m.moving_date::date BETWEEN '" . $arrData['dataMovingDateFrom'] . "' AND '" . $arrData['dataMovingDateThru'] . "')  ";
  }
  if ($arrData['dataEmployeeFrom'] != "") {
    $strKriteria .= "AND e.employee_id = '" . $arrData['dataEmployeeFrom'] . "'";
  }
  if ($arrData['dataEmployee'] != "") {
    $strKriteria .= "AND e.employee_id = '" . $arrData['dataEmployee'] . "'";
  }
  if ($arrData['dataIdRoomFrom'] != "") {
    $strKriteria .= "AND m.id_room_from = '" . $arrData['dataIdRoomFrom'] . "'";
  }
  if ($arrData['dataIdRoomTo'] != "") {
    $strKriteria .= "AND m.id_room_to = '" . $arrData['dataIdRoomTo'] . "'";
  }
  if ($arrData['dataDepartmenyFrom'] != "") {
    $strKriteria .= "AND m.department_code_from = '" . $arrData['dataDepartmentFrom'] . "'";
  }
  if ($arrData['dataDepartmentTo'] != "") {
    $strKriteria .= "AND m.department_code_to = '" . $arrData['dataDepartmentTo'] . "'";
  }
  $strKriteria .= $strKriteriaCompany;
  if ($db->connect()) {
    $myDataGrid = new cDataGrid("formData", "DataGrid1");
    $myDataGrid->caption = getWords(
        strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
    );
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->setCriteria($strKriteria);
    $myDataGrid->addColumnCheckbox(
        new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
    );
    $myDataGrid->addColumnNumbering(
        new DataGrid_Column("No", "", ['width' => '30', 'rowspan' => '2'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("Item"), "item_name", ['width' => '90'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Emply. From"), "employee_name_from", ['width' => '70'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("room From"), "room_name_from", ['width' => '70'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Dept. From"), "department_code_from", ['width' => '50'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "Emply.To", "", ["width" => "60"], ['align' => 'center', 'nowrap' => ''], false, false,
            "", "changeNameEmployeeTo()", "", false /*show in excel*/
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("room to"), "room_name_to", ['width' => '80'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("dept. to"), "department_code_to", ['width' => '70'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("date"), "moving_date", ['width' => '70'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("remark"), "remark", ['width' => '200'], ['nowrap' => '']));
    if (!isset($_POST['btnExportXLS']) && $bolCanEdit) {
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
    //-----------------BEGIN Jika Punya Hak Akses Hapus-----------------------------//
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
    //---------------- END Jika Punya Hak Akses Hapus-------------------------//
    $myDataGrid->addButtonExportExcel(
        "Export Excel",
        $dataPrivilege['menu_name'] . ".xls",
        getWords($dataPrivilege['menu_name'])
    );
    $myDataGrid->getRequest();
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_asset_moving AS m LEFT JOIN hrd_employee AS e ON m.id_employee_from = e.id";
    $strSQL = "SELECT i.item_name AS item_name,
                   e.employee_name AS employee_name_from,
                    e.employee_id AS employee_id_from,
                   r.room_name AS room_name_from,
                   rt.room_name AS room_name_to,
				   m.* 
                   FROM ga_asset_moving AS m
				   LEFT JOIN ga_item AS i ON m.id_item=i.id
				   LEFT JOIN ga_room AS r ON m.id_room_from=r.id
				   LEFT JOIN ga_room AS rt ON m.id_room_to=rt.id
				   LEFT JOIN hrd_employee AS e ON m.id_employee_from=e.id
				   ";
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

//************** END FUNGSI ISI DATA GRID ****************************************************************************************
//*********************************** FUNGSI TOMBOL EDIT******************************************
function printEditLink($params)
{
  extract($params);
  return "<a href=\"asset_moving_edit.php?dataIDFrom=" . $record['employee_id_from'] . "&dataID=" . $record['id'] . "\">" . getWords(
      'edit'
  ) . "</a>";
}

//******************************* END FUNGSI TOMBOL EDIT *******************************************
/****************************** BEGIN Get Data nama penerima ***************************************
 * Fungsi ini dibuat karena bila dieksekusi dengan perintah sql untuk menampilkan employye to terjadi ambigu
 * id company kolom
 */
function changeNameEmployeeTo($params)
{
  extract($params);
  global $db;
  $result = [];
  if ($db->connect()) {
    $strSQL = "SELECT * FROM hrd_employee where id='" . $record['id_employee_to'] . "'";
    $res = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($res)) {
      $nameEmployee = $rowDb['employee_name'];
    }
  }
  return $nameEmployee;
}

//****************************** END Get Data Room ***********************************************//
/*********************************BEGIN  fungsi untuk menghapus data ***************************/
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $dataItem = new cGaAssetMoving();
  $dataItem->deleteMultiple($arrKeys);
  $myDataGrid->message = $dataItem->strMessage;
}

/*********************************END fungsi untuk menghapus data ****************************/
//============================================================ MAIN PROGRAM ==========================================================
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $arrUserList = getAllUserInfo($db);
  $strDataID = getPostValue('dataID');
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  $strReadonly = (scopeCBDataEntry($strDataEmployee, $_SESSION['sessionUserRole'], $arrUserInfo)) ? "readonly" : "";
  /// Form ==================================================================================
  $f = new clsForm("formFilter", 2, "100%", "");
  $f->caption = strtoupper($strWordsFILTERDATA);
  $f->addSelect(
      getWords("Item"),
      "dataIdItem",
      getDataListItem(
          $arrData['dataIdItem'],
          true,
          [
              "value"    => "",
              "text"     => "",
              "selected" => true
          ]
      ),
      ["style" => "width:200", "size" => 10],
      "",
      false,
      true,
      true
  );
  $f->addInputAutoComplete(
      getWords("employee ID From "),
      "dataEmployeeFrom",
      getDataEmployee($arrData['dataEmployee']),
      "style='width:250px' " . $strReadonly,
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployee", "");
  $f->addInputAutoComplete(
      getWords("employee ID To"),
      "dataEmployee",
      getDataEmployee($arrData['dataEmployee']),
      "style='width:250px' " . $strReadonly,
      "string",
      false
  );
  $f->addLabelAutoComplete("", "dataEmployee", "");
  $f->addSelect(
      getWords("Location Room From"),
      "dataIdRoomFrom",
      getDataListRoom(
          $arrData['dataIdRoomFrom'],
          true,
          [
              "value"    => "",
              "text"     => "",
              "selected" => true
          ]
      ),
      ["style" => "width:200"],
      "",
      false,
      true,
      true
  );
  $f->addSelect(
      getWords("Location Room To"),
      "dataIdRoomTo",
      getDataListRoom(
          $arrData['dataIdRoomTo'],
          true,
          [
              "value"    => "",
              "text"     => "",
              "selected" => true
          ]
      ),
      ["style" => "width:200"],
      "",
      false,
      true,
      true
  );
  // Jika St ReadOnly kosong-------------------------------------------------------------------------------------------------------------
  if ($strReadonly == "") {
    $f->addSelect(
        getWords("department From"),
        "dataDepartmentFrom",
        getDataListDepartment(
            $arrData['dataDepartementFrom'],
            true,
            [
                "value"    => "",
                "text"     => "",
                "selected" => true,
            ]
        ),
        ["style" => "width:200"],
        "",
        false,
        true,
        true
    );
  } else {
    $f->addSelect(
        getWords("department from"),
        "dataDepartmentFrom",
        getDataListDepartment(getInitialValue("Department", "", $strDataDepartment), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['department'] == "")
    );
  }
  //--------------------------------------------------------------------------------------------------------------------------------------
  // Jika St ReadOnly kosong-------------------------------------------------------------------------------------------------------------
  if ($strReadonly == "") {
    $f->addSelect(
        getWords("department To"),
        "dataDepartmentTo",
        getDataListDepartment(
            $arrData['dataDepartementTo'],
            true,
            [
                "value"    => "",
                "text"     => "",
                "selected" => true,
            ]
        ),
        ["style" => "width:200"],
        "",
        false,
        true,
        true
    );
  } else {
    $f->addSelect(
        getWords("department To"),
        "dataDepartmentTo",
        getDataListDepartment(getInitialValue("Department", "", $strDataDepartment), true),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false,
        ($ARRAY_DISABLE_GROUP['department'] == "")
    );
  }
  //--------------------------------------------------------------------------------------------------------------------------------------
  $f->addInput(
      getWords("Moving date From"),
      "dataMovingDateFrom",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      getWords("moving date Thru"),
      "dataMovingDateThru",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addSubmit("btnShow", getWords("show"), "", true, true, "", "", "");
  $f->addButton("btnAdd", getWords("Clear"), ["onClick" => "location.href='" . basename($_SERVER['PHP_SELF'] . "';")]);
  $formFilter = $f->render();
  getData($db);
  // END FORM====================================================================================
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>