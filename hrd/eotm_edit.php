<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_eotm.php');
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
  getUserEmployeeInfo();
  //INISIALISASI------------------------------------------------------------------------------------------------------------------
  $strWordsDataEntry = getWords("data entry");
  $strWordsEOTMList = getWords("eotm list");
  $strDataID = getRequestValue('dataID');
  $isNew = ($strDataID == "");
  if ($strDataID != "") {
    $arrData = getDataByID($strDataID);
  } else {
    $arrData['dataCreated'] = getInitialValue(date("Y-m-d"));
    $arrData['dataMonth'] = getPostValue('dataMonth');
    $arrData['dataYear'] = getPostValue('dataYear');
    $arrData['dataCompany'] = getPostValue('dataCompany');
    $arrData['dataEmployee'] = getPostValue('dataEmployee');
    $arrData['dataNote'] = getPostValue('dataNote');
  }
  // ------------------------------------------------------------------------------------------------------------------------------
  if ($bolCanEdit) {
    $f = new clsForm("formInput", 1, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    $f->addSelect(
        getWords("release month"),
        "dataMonth",
        getDataListMonth($arrData['dataMonth']),
        "style='width:250px'",
        "",
        true
    );
    $f->addSelect(
        getWords("release year"),
        "dataYear",
        getDataListYear($arrData['dataYear']),
        "style='width:250px'",
        "",
        true
    );
    $f->addSelect(
        getWords("company"),
        "dataCompany",
        getDataListCompany(
            $arrData['dataCompany'],
            $bolCompanyEmptyOption,
            $arrCompanyEmptyData,
            $strKriteria2
        ),
        ["style" => "width:$strDefaultWidthPx"],
        "",
        false
    );
    $f->addInputAutoComplete(
        getWords("employee ID"),
        "dataEmployee",
        getDataEmployee($arrData['dataEmployee']),
        "style='width:250px' ",
        "string",
        true
    );
    $f->addLabelAutoComplete("", "dataEmployee", "");
    $f->addTextArea(
        getWords("note"),
        "dataNote",
        $arrData['dataNote'],
        ["cols" => 76, "rows" => 3],
        "string",
        false,
        true,
        true
    );
    $f->addsubmit("btnSave", getWords("save"), "", true, true, "", "", "saveData()");
    $f->addButton(
        "btnAdd",
        getWords("add new"),
        ["onClick" => "location.href='" . basename($_SERVER['PHP_SELF']) . "'"]
    );
    $formInput = $f->render();
  } else {
    $formInput = "";
  }
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
function getDataByID($strDataID)
{
  global $db;
  $tblEOTM = new cHrdEotm();
  $dataEOTM = $tblEOTM->findAll("id = $strDataID", '*', "id", null, 1, "id");
  $dataEOTM = $dataEOTM[$strDataID];
  $strDataEmployee = getEmployeeCode($db, $dataEOTM['id_employee']);
  $arrResult['dataEmployee'] = $strDataEmployee;
  $arrResult['dataCreated'] = $dataEOTM['created'];
  $arrResult['dataMonth'] = $dataEOTM['release_month'];
  $arrResult['dataYear'] = $dataEOTM['release_year'];
  $arrResult['dataCompany'] = $dataEOTM['id_company'];
  $arrResult['dataNote'] = $dataEOTM['note'];
  return $arrResult;
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $db;
  global $isNew;
  if ($db->connect()) {
    $strRelationType = "";
    $strmodified_byID = $_SESSION['sessionUserID'];
    $strIDEmployee = getIDEmployee($db, $f->getValue('dataEmployee'));
    $tblEOTM = new cHrdEotm();
    $data = [
        "release_month" => $f->getValue('dataMonth'),
        "release_year"  => $f->getValue('dataYear'),
        "id_company"    => $f->getValue('dataCompany'),
        "id_employee"   => ($strIDEmployee),
        "note"          => $f->getValue('dataNote')
    ];
    // simpan data donation
    $bolSuccess = false;
    if ($isNew) {
      // data baru
      $bolSuccess = $tblEOTM->insert($data);
      //print_r($data);
    } else {
      $bolSuccess = $tblEOTM->update("id='" . $f->getValue('dataID') . "'", $data);
    }
    if ($bolSuccess) {
      if ($isNew) {
        $f->setValue('dataID', $tblEOTM->getLastInsertId());
      } else {
        $f->setValue('dataID', $f->getValue('dataID'));
      }
    }
  } else {
    $f->message = "no connection";
    $f->msgClass = "bgError";
  }
  $f->message = $tblEOTM->strMessage;
} // saveData
?>