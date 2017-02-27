<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
include_once('activity.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
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
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintAll']) || isset($_REQUEST['btnExcel']));
$bolFull = (isset($_REQUEST['filterFull']));
$DataGrid = "";
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", true, true, true);
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strPaging = "";
$strHidden = "";
$strBtnPrint = "";
$intTotalData = 0;
$strWordsByTopic = getWords("by topic");
$strWordsByEmployee = getWords("by employee");
$bolLimit = true; // default, tampilan dibatasi (paging)
$strButtons = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI-----------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria, $strOrder = query ORder by
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getData($db)
{
  global $dataPrivilege, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge;
  global $f;
  global $DataGrid;
  global $myDataGrid;
  global $strKriteriaCompany;
  $arrData = $f->getObjectValues();
  $strKriteria = "";
  // GENERATE CRITERIA
  if ($arrData['dataTrainingType'] != "") {
    $strKriteria .= "AND t2.training_type = '" . $arrData['dataTrainingType'] . "'";
  }
  if ($arrData['dataInstitution'] != "") {
    $strKriteria .= "AND t2.id_training_vendor = '" . $arrData['dataInstitution'] . "'";
  }
  if (validStandardDate($arrData['dataDateFrom']) && validStandardDate($arrData['dataDateThru'])) {
    $strKriteria .= "AND (t1.training_date BETWEEN '" . $arrData['dataDateFrom'] . "' AND '" . $arrData['dataDateThru'] . "')  ";
  }
  // cari dulu partisipannya
  $arrParticipant = [];
  $strSQL = "SELECT t3.id_request, t3.cost, t3.other_cost, t4.employee_name FROM hrd_training_request_participant AS t3 ";
  $strSQL .= "LEFT JOIN hrd_training_request AS t1 ON t1.id = t3.id_request ";
  $strSQL .= "LEFT JOIN hrd_training_plan AS t2 ON t1.id_plan = t2.id ";
  $strSQL .= "LEFT JOIN hrd_employee AS t4 ON t3.id_employee = t4.id ";
  $strSQL .= "WHERE t1.status=" . REQUEST_STATUS_APPROVED . " $strKriteria ";
  // echo $strSQL;
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if (isset($arrParticipant[$rowDb['id_request']])) {
      $arrParticipant[$rowDb['id_request']][] = $rowDb['employee_name'];
      $arrCost[$rowDb['id_request']] += $rowDb['cost'] + $rowDb['other_cost'];
    } else {
      $arrParticipant[$rowDb['id_request']][0] = $rowDb['employee_name'];
      $arrCost[$rowDb['id_request']] = $rowDb['cost'] + $rowDb['other_cost'];
    }
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
    $myDataGrid->caption = getWords(
        strtoupper(vsprintf(getWords("list of %s"), getWords($dataPrivilege['menu_name'])))
    );
    $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
    $myDataGrid->setCriteria($strKriteria);
    $myDataGrid->hasGrandTotal = true;
    $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("id"), "id", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("training date"), "training_date", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("type"), "training_type", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("topic"), "topic", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("expected result"), "result", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("institution"), "name_vendor", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("trainer"), "name_instructor", "", ['nowrap' => '']));
    $myDataGrid->addColumn(new DataGrid_Column(getWords("participant"), "participant", "", ['nowrap' => '']));
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("total"),
            "total_participant",
            ['nowrap' => ''],
            ["align" => "right"],
            false,
            true,
            "",
            "",
            "numeric",
            true,
            15
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("total cost"),
            "total_cost",
            ['nowrap' => ''],
            ["align" => "right"],
            false,
            true,
            "",
            "formatNumber()",
            "numeric",
            true,
            15
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("duration") . " " . getWords("days"),
            "total_duration",
            ['nowrap' => ''],
            ["align" => "right"],
            false,
            true,
            "",
            "",
            "numeric",
            true,
            15
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords("duration") . " " . getWords("minutes"),
            "total_hour",
            ['nowrap' => ''],
            ["align" => "right"],
            false,
            true,
            "",
            "",
            "numeric",
            true,
            15
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(getWords("status"), "status", "", "", false, false, "", "printRequestStatus()")
    );
    foreach ($arrData AS $key => $value) {
      $myDataGrid->strAdditionalHtml .= generateHidden($key, $value, "");
    }
    //tampilkan buttons sesuai dengan otoritas, common_function.php
    $myDataGrid->addButtonExportExcel(
        "Export Excel",
        $dataPrivilege['menu_name'] . ".xls",
        getWords($dataPrivilege['menu_name'])
    );
    $myDataGrid->getRequest();
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQL = "
        SELECT t1.*, t3.name_vendor, '' as participant, 0 as total_participant, 0 as total_cost ,t2.topic,t2.training_type,t4.name_instructor
        FROM hrd_training_request AS t1 
		LEFT JOIN hrd_training_plan AS t2 ON t1.id_plan = t2.id
        LEFT JOIN hrd_training_vendor AS t3 ON t2.id_training_vendor = t3.id
		LEFT JOIN hrd_training_instructor AS t4 ON t2.id_instructor = t4.id 
        LEFT JOIN hrd_employee AS t5 ON t2.id_creator = t5.id
        WHERE t1.status=" . REQUEST_STATUS_APPROVED . " ";
    $strSQLCOUNT = "SELECT COUNT(*) AS total FROM hrd_training_request AS t1 LEFT JOIN hrd_training_plan AS t2 ON t1.id_plan = t2.id LEFT JOIN hrd_employee AS t3 ON t2.id_creator = t3.id WHERE 1=1";
    $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
    $dataset = $myDataGrid->getData($db, $strSQL);
    foreach ($dataset AS $strKey => $arrDetail) {
      $strParticipant = "";
      $intParticipant = 0;
      if (isset($arrParticipant[$arrDetail['id']])) {
        $intParticipant = count($arrParticipant[$arrDetail['id']]);
        foreach ($arrParticipant[$arrDetail['id']] AS $id => $strName) {
          if ($strParticipant != "") {
            $strParticipant .= "<br>";
          }
          $strParticipant .= $strName;
        }
      }
      $dataset[$strKey]['participant'] = $strParticipant;
      $dataset[$strKey]['total_participant'] = $intParticipant;
      $dataset[$strKey]['total_cost'] = $arrCost[$arrDetail['id']];
    }
    //bind Datagrid with array dataset and branchCode
    $myDataGrid->bind($dataset);
    $DataGrid = $myDataGrid->render();
  } else {
    $DataGrid = "";
  }
  return $DataGrid;
}

//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
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
  $f = new clsForm("formFilter", 2, "100%", "");
  $f->caption = strtoupper($strWordsFILTERDATA);
  $f->addInput(
      getWords("date from"),
      "dataDateFrom",
      getInitialValue("DateFrom", date("Y-m-") . "01"),
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
  $f->addSelect(
      getWords("training type"),
      "dataTrainingType",
      getDataListTrainingType("", true, $arrEmpty),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addSelect(
      getWords("institution"),
      "dataInstitution",
      getDataListTrainingVendor("", true, $arrEmpty),
      ["style" => "width:$strDefaultWidthPx"],
      "",
      false
  );
  $f->addLiteral("", "", "");
  // $f->addSelect(getWords("company"), "dataCompany", getDataListCompany($strDataCompany, $bolCompanyEmptyOption, $arrCompanyEmptyData, $strKriteria2), array("style" => "width:$strDefaultWidthPx"), "", false);
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
  $formFilter = $f->render();
  getData($db);
}
$tbsPage = new clsTinyButStrong;
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
if ($bolPrint) {
  $strMainTemplate = getTemplate(str_replace(".php", "_print.html", basename($_SERVER['PHP_SELF'])));
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>
