<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_training_template.php');
include_once('../classes/hrd/hrd_training_category.php');
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
$strWordsTrainingCategory = getWords("training category");
$strWordsTrainingType = getWords("training type");
$strWordsTrainingTemplate = getWords("training template");
$strWordsTrainingNeedCriteria = getWords("training need criteria");
$db = new CdbClass;
if ($db->connect()) {
  if (getRequestValue("deleteID") != "") {
    if ($bolCanDelete) {
      deleteData(getRequestValue("deleteID"));
      redirectPage("training_template.php");
    } else {
      $myDataGrid->message = "Sorry, you don't have authority to delete data from this page";
    }
  }
  $strDataID = getPostValue('dataID');
  $dataHrdTrainingTemplate = new cHrdTrainingTemplate();
  $dataHrdTrainingCategory = new cHrdTrainingCategory();
  $isNew = ($strDataID == "");
  if ($bolCanEdit) {
    $f = new clsForm("formInput", 1, "100%", "");
    $f->caption = strtoupper("input data " . $dataPrivilege['menu_name']);
    $f->addHidden("dataID", $strDataID);
    $f->addSelect(getWords("grade"), "dataGradeCode", getDataListSalaryGrade(), ["style" => "width:270"]);
    $f->addSelect(
        getWords("training type"),
        "dataIdType",
        getDataListTrainingCategoryType("", false),
        ["style" => "width:270"]
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
    $f->addButton(
        "btnAdd",
        getWords("add new"),
        ["onClick" => "javascript:myClient.addData();"],
        true,
        true,
        "",
        "",
        ""
    );
    $formInput = $f->render();
  } else {
    $formInput = "";
  }
  $myDataGrid = new cDataGrid("formData", "DataGrid");
  $arrCategory = $dataHrdTrainingCategory->findAll("", "id, training_category", "", null, 1, "id");
  $myDataGrid->caption = strtoupper(getWords($dataPrivilege['menu_name']));
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  $myDataGrid->setPageLimit("all");
  //$myDataGrid->addColumnCheckbox(new DataGrid_Column("chkID", "id", "", array('width' => '15', 'valign' => 'top', 'align'=>'center', 'nowrap' => '')));
  $myDataGrid->addColumnNumbering(
      new DataGrid_Column("No", "", "", ['width' => '30', 'valign' => 'top', 'nowrap' => ''])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(getWords("grade"), "grade_code", "", ['width' => '50', 'valign' => 'top', 'nowrap' => ''])
  );
  foreach ($arrCategory AS $strIdCategory => $arrDetail) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords($arrDetail['training_category']),
            "category_" . $strIdCategory,
            "",
            ['valign' => 'top', 'nowrap' => ''],
            false,
            false,
            "",
            "",
            "",
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            getWords($arrDetail['training_category']),
            "category_" . $strIdCategory . "_excel",
            ["style" => "display:none"],
            ["style" => "display:none"],
            false,
            false,
            "",
            "",
            "",
            true,
            15
        )
    );
  }
  $myDataGrid->addButtonExportExcel("Export Excel", "Training Template.xls", getWords("Training Template"));
  $myDataGrid->getRequest();
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  $strSQL = "SELECT t1.id, t2.code, t2.name, ";
  foreach ($arrCategory AS $strIdCategory => $arrDetail) {
    $strSQL .= "CASE WHEN t1.id_category = " . $strIdCategory . " THEN t2.code || ' - ' ||  t2.name ELSE NULL END AS category_" . $strIdCategory . ", ";
  }
  $strSQL .= "grade_code FROM hrd_training_template AS t1 LEFT JOIN hrd_training_type AS t2 ON t1.id_type = t2.id";
  $myDataGrid->totalData = $dataHrdTrainingTemplate->findCount();
  $dataset2 = $myDataGrid->getData($db, $strSQL);
  $dataset = [];
  foreach ($dataset2 as $strKey => $arrDetail) {
    if (!isset($dataset[$arrDetail['grade_code']])) {
      $dataset[$arrDetail['grade_code']]['grade_code'] = $arrDetail['grade_code'];
    }
    foreach ($arrDetail as $strField => $strValue) {
      if (substr($strField, 0, 9) == "category_") {
        $strDeleteLink = ($strValue == "") ? "" : "<a href=\"training_template.php?deleteID=" . $arrDetail['id'] . "\">[x]</a> ";
        if (isset($dataset[$arrDetail['grade_code']][$strField]) && $dataset[$arrDetail['grade_code']][$strField] != "") {
          if ($strValue != "") {
            $dataset[$arrDetail['grade_code']][$strField] .= "<br>" . $strDeleteLink . $strValue;
            $dataset[$arrDetail['grade_code']][$strField . "_excel"] .= ", " . $strValue;
          }
        } else {
          $dataset[$arrDetail['grade_code']][$strField] = $strDeleteLink . $strValue;
          $dataset[$arrDetail['grade_code']][$strField . "_excel"] = $strValue;
        }
      }
    }
  }
  //bind Datagrid with array dataset
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
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
//--------------------------------------------------------------------------------
// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $isNew;
  $db = new CdbClass;
  $bolSave = true;
  $strmodified_byID = $_SESSION['sessionUserID'];
  $dataHrdTrainingTemplate = new cHrdTrainingTemplate();
  if ($bolSave) {
    list($strDataIdType, $strDataIdCategory) = explode("|", $f->getValue('dataIdType'));
    $data = [
        "grade_code" => $f->getValue('dataGradeCode'),
        "id_category" => $strDataIdCategory,
        "id_type" => $strDataIdType,
    ];
    // simpan data -----------------------
    $bolSuccess = false;
    if ($isNew) {
      // data baru
      $bolSuccess = $dataHrdTrainingTemplate->insert($data);
      if ($bolSuccess) {
        $f->setValue('dataID', $dataHrdTrainingTemplate->getLastInsertId());
      }
    } else {
      $bolSuccess = $dataHrdTrainingTemplate->update(/*pk*/
          "id='" . $f->getValue('dataID') . "'", /*data to update*/
          $data
      );
    }
  }
  $f->message = $dataHrdTrainingTemplate->strMessage;
} // saveData
// fungsi untuk menghapus data
function deleteData($strValue)
{
  global $myDataGrid;
  $dataHrdTrainingTemplate = new cHrdTrainingTemplate();
  $dataHrdTrainingTemplate->delete("id = '$strValue'");
} //deleteData
?>