<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/ga/apartment_request.php');
//================ END INCLUDE==========================================
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
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if ($arrData['dataIdItem'] != "") {
    $strKriteria .= "AND a.id_item = '" . $arrData['dataIdItem'] . "'";
  }
  if (validStandardDate($arrData['dataRequestDateFrom']) && validStandardDate($arrData['dataRequestDateThru'])) {
    $strKriteria .= "AND (a.request_date::date BETWEEN '" . $arrData['dataRequestDateFrom'] . "' AND '" . $arrData['dataRequestDateThru'] . "')  ";
  }
  if (validStandardDate($arrData['dataDateFromFrom']) && validStandardDate($arrData['dataDateFromThru'])) {
    $strKriteria .= "AND (a.date_from::date BETWEEN '" . $arrData['dataDateFromFrom'] . "' AND '" . $arrData['dataDateFromThru'] . "')  ";
  }
  if (validStandardDate($arrData['dataDateToFrom']) && validStandardDate($arrData['dataDateToThru'])) {
    $strKriteria .= "AND (a.date_to::date BETWEEN '" . $arrData['dataDateToFrom'] . "' AND '" . $arrData['dataDateToThru'] . "')  ";
  }
  if ($arrData['dataIdDriver'] != "") {
    $strKriteria .= "AND a.id_driver = '" . $arrData['dataIdDriver'] . "'";
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
        new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => '']),
        true
    );
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Req.No"), "vehicle_req_no", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("Item"), "item_name", ['width' => '100'], ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Driver"), "driver_name", ['width' => '100'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Request date"), "request_date", ['width' => '150'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("Date From"), "date_from", ['width' => '150'], ['nowrap' => ''])
    );
    $myDataGrid->addColumn(new DataGrid_Column(getWords("Date To"), "date_to", ['width' => '150'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("remark"), "remark", ['width' => '100'], ['nowrap' => '']));
    // Jika punya hal akses edit
    if (!isset($_POST['btnExportXLS']) && $bolCanEdit) {
      $myDataGrid->addColumn(
          new DataGrid_Column(
              "", "", ["width" => "60"], ['align' => 'center', 'nowrap' => ''], false, false, "",
              "printEditLink()", "", false /*show in excel*/
          )
      );
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
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM ga_vehicle_realization AS a
	  				 LEFT JOIN ga_vehicle_request as vr ON a.id_vehicle_request=vr.id
					 LEFT JOIN hrd_employee AS e ON vr.id_employee = e.id";
    $strSQL = "SELECT
	  				  i.item_name AS item_name,
					  e.employee_name AS employee_name,
					  d.driver_name AS driver_name,
					  e.id AS id_employee,
					  vr.vehicle_req_no,
					  a.* 
                      FROM ga_vehicle_realization AS a LEFT JOIN ga_item AS i ON a.id_item=i.id
                      LEFT JOIN ga_vehicle_request as vr ON a.id_vehicle_request=vr.id
                      LEFT JOIN ga_driver AS d ON a.id_driver=d.id
				      LEFT JOIN hrd_employee AS e ON vr.id_employee=e.id";
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
  return "<a href=\"vehicle_realization_edit.php?dataID=" . $record['id'] . "\">" . getWords('edit') . "</a>";
}

//******************************* END FUNGSI TOMBOL EDIT *******************************************
/*********************************BEGIN  fungsi untuk menghapus data ***************************/
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $dataItem = new cGaVehicleRequest();
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
  $f->addHidden("dataID", $strDataID);
  $f->addSelect(
      getWords("Vehicle request"),
      "dataIdVehicleRequest",
      getDataLisVehicleRequest(
          $arrData['dataIdVehicleRequest'],
          true,
          [
              "value"    => "",
              "text"     => "",
              "selected" => true
          ]
      ),
      ["size" => 10],
      "",
      false,
      true,
      true
  );
  $f->addSelect(
      getWords("Item"),
      "dataIdItem",
      getDataListItemCriteria(
          $db,
          $arrData['dataIdItem'],
          false,
          [
              "value"    => "",
              "text"     => "",
              "selected" => true
          ],
          "Vehicle"
      ),
      ["style" => "width:200", "size" => 10],
      "",
      false,
      true,
      true
  );
  $f->addSelect(
      getWords("Driver"),
      "dataIdDriver",
      getDataListDriver(
          $arrData['dataIdRoom'],
          false,
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
  $f->addInput(
      getWords("Request date From"),
      "dataRequestDateFrom",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      getWords("Request date to"),
      "dataRequestDateThru",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      "(Date From) From",
      "dataDateFromFrom",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput(
      "(Date From) Thru",
      "dataDateFromThru",
      "",
      ["style" => "width:$strDateWidth"],
      "date",
      false,
      true,
      true
  );
  $f->addInput("(Date To) From", "dataDateToFrom", "", ["style" => "width:$strDateWidth"], "date", false, true, true);
  $f->addInput("(Date To) Thru", "dataDateToThru", "", ["style" => "width:$strDateWidth"], "date", false, true, true);
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