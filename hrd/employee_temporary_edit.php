<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_employee.php');
include_once('../classes/hrd/hrd_employee_temporary.php');
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
  $strWordsDataEntry = getWords("data entry");
  $strWordsEmployeeTemporaryDataList = getWords("employee temporary data list");
  getUserEmployeeInfo();
  $strIDEmployee = $arrUserInfo['id_employee'];
  //INISIALISASI------------------------------------------------------------------------------------------------------------------
  $strDataID = getRequestValue('dataID');
  $isNew = ($strDataID == "");
  $intDefaultWidth = "250px";
  $arrData = getDataByID($strDataID);
  //echo "test<br/>";
  //var_dump($arrData);
  //echo $strDataID.$strIDEmployee;
  //exit();
  /*
   else
   {
     $arrData['dataEmployeeID']        = getPostValue('dataEmployeeID');
     $arrData['dataNickname']          = getPostValue('dataNickname');
     $arrData['dataPrimaryAddress']    = getPostValue('dataPrimaryAddress');
     $arrData['dataPrimaryPhone']      = getPostValue('dataPrimaryPhone');
     $arrData['dataPrimaryCity']       = getPostValue('dataPrimaryCity');
     $arrData['dataPrimaryZip']        = getPostValue('dataPrimaryZip');
     $arrData['dataIDCard']            = getPostValue('dataIDCard');
     $arrData['dataBirthplace']        = getPostValue('dataBirthplace');
     $arrData['dataBirthday']          = getPostValue('dataBirthday');
     $arrData['dataWeddingDate']       = getPostValue('dataWeddingDate');
     $arrData['dataGender']            = getPostValue('dataGender');
     $arrData['dataEmail']             = getPostValue('dataEmail');
     $arrData['dataWeight']            = getPostValue('dataWeight');
     $arrData['dataHeight']            = getPostValue('dataHeight');
     $arrData['dataBloodType']         = getPostValue('dataBloodType');
     $arrData['dataEmergencyContact']  = getPostValue('dataEmergencyContact');
     $arrData['dataEmergencyAddress']  = getPostValue('dataEmergencyAddress');
     $arrData['dataEmergencyPhone']    = getPostValue('dataEmergencyPhone');
     $arrData['dataEmergencyRelation'] = getPostValue('dataEmergencyRelation');
     $arrData['dataDriverLicenseA']    = getPostValue('dataDriverLicenseA');
     $arrData['dataDriverLicenseB']    = getPostValue('dataDriverLicenseB');
     $arrData['dataDriverLicenseC']    = getPostValue('dataDriverLicenseC');
     $arrData['dataNPWP']              = getPostValue('dataNPWP');
     $arrData['dataJamsostekNo']       = getPostValue('dataJamsostekNo');
     $arrData['dataPassport']          = getPostValue('dataPassport');
     $arrData['dataInspouse']          = getPostValue('dataInspouse');
     $arrData['dataBankAccount']       = getPostValue('dataBankAccount');
     $arrData['dataBankAccountName']   = getPostValue('dataBankAccountName');
     $arrData['dataBankAccountType']   = getPostValue('dataBankAccountType');
     $arrData['dataBankBranch']        = getPostValue('dataBankBranch');
     $arrData['dataBankCode']          = getPostValue('dataBankCode');
     $arrData['dataBank2Account']      = getPostValue('dataBank2Account');
     $arrData['dataBank2AccountName']  = getPostValue('dataBank2AccountName');
     $arrData['dataBank2AccountType']  = getPostValue('dataBank2AccountType');
     $arrData['dataBank2Branch']       = getPostValue('dataBank2Branch');
     $arrData['dataBank2Code']         = getPostValue('dataBank2Code');
     $arrData['dataNote']              = getPostValue('dataNote');
   }*/
  scopeGeneralDataEntry($strDataEmployee, $_SESSION['sessionUserRole'], $arrUserInfo);
  // ------------------------------------------------------------------------------------------------------------------------------
  if ($bolCanEdit) {
    $f = new clsForm("formInput", 2, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    //if ($isNew)
    //  $f->addInputAutoComplete(getWords("employee id"), "dataEmployeeID", getDataEmployee($strDataEmployee), "style='width:250px' ". $strEmpReadonly, "string", true);
    //else
    // $f->addInputAutoComplete(getWords("employee id"), "dataEmployeeID", getDataEmployee($arrData['dataEmployeeID']), "style='width:250px' ". $strEmpReadonly, "string", true);
    //$f->addLabelAutoComplete("", "dataEmployeeID", "");
    $f->addInput(
        getWords("employee id"),
        "dataEmployeeID",
        $arrData['dataEmployeeID'],
        "style='width:250px' " . $strEmpReadonly,
        "string",
        true,
        false,
        true
    );
    $f->addInput(
        getWords("nickname"),
        "dataNickname",
        $arrData['dataNickname'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 15],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("birthday"),
        "dataBirthday",
        $arrData['dataBirthday'],
        ["style" => "width:$intDefaultWidth"],
        "date",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("birthplace"),
        "dataBirthplace",
        $arrData['dataBirthplace'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 50],
        "string",
        false,
        true,
        true
    );
    $f->addSelect(
        getWords("sex"),
        "dataGender",
        getDataListGender($arrData['dataGender'], true, ["value" => "1", "text" => "", "selected" => false]),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false
    );
    $f->addTextArea(
        getWords("address"),
        "dataPrimaryAddress",
        $arrData['dataPrimaryAddress'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 255],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("city"),
        "dataPrimaryCity",
        $arrData['dataPrimaryCity'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 131],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("zip"),
        "dataPrimaryZip",
        $arrData['dataPrimaryZip'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 15],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("phone"),
        "dataPrimaryPhone",
        $arrData['dataPrimaryPhone'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 31],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("email"),
        "dataEmail",
        $arrData['dataEmail'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 63],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("emergency contact"),
        "dataEmergencyContact",
        $arrData['dataEmergencyContact'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 63],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("relation"),
        "dataEmergencyRelation",
        $arrData['dataEmergencyRelation'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 31],
        "string",
        false,
        true,
        true
    );
    $f->addTextArea(
        getWords("emergency address"),
        "dataEmergencyAddress",
        $arrData['dataEmergencyAddress'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 255],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("emergency phone"),
        "dataEmergencyPhone",
        $arrData['dataEmergencyPhone'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 31],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("weight") . " (kg)",
        "dataWeight",
        $arrData['dataWeight'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 7],
        "numeric",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("height") . " (cm)",
        "dataHeight",
        $arrData['dataHeight'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 7],
        "numeric",
        false,
        true,
        true
    );
    $f->addSelect(
        getWords("blood type"),
        "dataBloodType",
        getDataListBloodType(
            $arrData['dataBloodType'],
            true,
            ["value" => "", "text" => "", "selected" => false]
        ),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false,
        true
    );
    $f->addInput(
        getWords("K T P"),
        "dataIDCard",
        $arrData['dataIDCard'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 31],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("driver license a"),
        "dataDriverLicenseA",
        $arrData['dataDriverLicenseA'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 31],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("driver license b"),
        "dataDriverLicenseB",
        $arrData['dataDriverLicenseB'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 31],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("driver license c"),
        "dataDriverLicenseC",
        $arrData['dataDriverLicenseC'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 31],
        "string",
        false,
        true,
        true
    );
    //$f->addInput(getWords("nationality"), "dataNationality", $arrData['dataNationality'], array("style" => "width:$intDefaultWidth", "maxlength" => 31), "string", false, true, true);
    $f->addInput(
        getWords("passport"),
        "dataPassport",
        $arrData['dataPassport'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 31],
        "string",
        false,
        true,
        true
    );
    $f->addSelect(
        getWords("religion"),
        "dataReligion",
        getDataListReligion(
            $arrData['dataReligion'],
            true,
            ["value" => "", "text" => "", "selected" => false]
        ),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        true,
        true
    );
    $f->addSelect(
        getWords("education level"),
        "dataEducation",
        getDataListEducationLevel(
            $arrData['dataEducation'],
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("wedding date"),
        "dataWeddingDate",
        $arrData['dataWeddingDate'],
        ["style" => "width:$intDefaultWidth"],
        "date",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("transport"),
        "dataTransport",
        $arrData['dataTransport'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 31],
        "string",
        false,
        false,
        true
    );
    $f->addInput(
        getWords("transport fee"),
        "dataTransportFee",
        $arrData['dataTransportFee'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 31],
        "string",
        false,
        false,
        true
    );
    $f->addSelect(
        getWords("family status"),
        "dataFamily",
        getDataListFamilyStatus(
            $arrData['dataFamily'],
            true,
            ["value" => "", "text" => "", "selected" => false]
        ),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false,
        true
    );
    //$f->addSelect(getWords("family status (Tax)"), "dataFamilyTax", getDataListFamilyStatus($arrData['dataFamily'], true, array("value" => "", "text" => "", "selected" => false)),  array ("style" => "width:$intDefaultWidth"), "", false, false, true);
    //$f->addSelect(getWords("living cost status"), "dataLivingCost", getDataListLivingCost($arrData['dataLivingCost'], true, array("value" => "", "text" => "", "selected" => false)),  array ("style" => "width:$intDefaultWidth"), "", false, true, true);
    //$f->addSelect(getWords("medical quota status"), "dataMedicalQuota", getDataListFamilyStatus($arrData['dataMedicalQuota'], true, array("value" => "", "text" => "", "selected" => false)),  array ("style" => "width:$intDefaultWidth"), "", false, true, true);
    $f->addCheckBox(
        getWords("spouse"),
        "dataInspouse",
        $arrData['dataInspouse'],
        [],
        "string",
        false,
        false,
        true,
        "",
        "<br>&nbsp;<br>"
    );
    $f->addSelect(
        getWords("company"),
        "dataCompany",
        getDataListCompany(
            $arrData['dataCompany'],
            true,
            ["value" => "", "text" => "", "selected" => true],
            $strKriteria2
        ),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false,
        true
    );
    $f->addSelect(
        getWords("employee status"),
        "dataEmployeeStatus",
        getDataListEmployeeStatus(
            $arrData['dataEmployeeStatus'],
            true,
            ["value" => "", "text" => "", "selected" => ""]
        ),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false,
        true
    );
    $f->addSelect(
        getWords("subsection"),
        "dataSubsection",
        getDataListSubSection(
            $arrData['dataSubsection'],
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false,
        true
    );
    $f->addSelect(
        getWords("section"),
        "dataSection",
        getDataListSection($arrData['dataSection'], true, ["value" => "", "text" => "", "selected" => true]),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false,
        true
    );
    $f->addSelect(
        getWords("departement"),
        "dataDepartement",
        getDataListDepartment(
            $arrData['dataDepartement'],
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false,
        true
    );
    $f->addSelect(
        getWords("division"),
        "dataDivision",
        getDataListDivision(
            $arrData['dataDivision'],
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false,
        true
    );
    $f->addSelect(
        getWords("management"),
        "dataManagement",
        getDataListManagement(
            $arrData['dataManagement'],
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false,
        true
    );
    $f->addSelect(
        getWords("branch"),
        "dataBranch",
        getDataListBranch($arrData['dataBranch'], true, ["value" => "", "text" => "", "selected" => true]),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false,
        true
    );
    $f->addSelect(
        getWords("level"),
        "dataLevel",
        getDataListPosition($arrData['dataLevel'], true, ["value" => "", "text" => "", "selected" => true]),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false,
        true
    );
    $f->addSelect(
        getWords("functional position"),
        "dataFunctional",
        getDataListFunctionalPosition(
            $arrData['dataFunctional'],
            true,
            ["value" => "", "text" => "", "selected" => true]
        ),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false,
        true
    );
    $f->addInput(
        getWords("npwp"),
        "dataNPWP",
        $arrData['dataNPWP'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 63],
        "string",
        false,
        false,
        true
    );
    $f->addInput(
        getWords("jamsostek no"),
        "dataJamsostekNo",
        $arrData['dataJamsostekNo'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 50],
        "string",
        false,
        false,
        true
    );
    //$f->addCheckBox(getWords("zakat"), "dataZakat", $arrData['dataZakat'], array(), "string", false, true, true,"", "<br>&nbsp;<br>");
    $f->addSelect(
        getWords("bank code"),
        "dataBankCode",
        getDataListBank($arrData['dataBankCode'], true, ["value" => "", "text" => "", "selected" => true]),
        ["style" => "width:$intDefaultWidth"],
        "",
        false,
        false,
        true
    );
    $f->addInput(
        getWords("bank branch"),
        "dataBankBranch",
        $arrData['dataBankBranch'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 63],
        "string",
        false,
        false,
        true
    );
    $f->addInput(
        getWords("bank account type"),
        "dataBankAccountType",
        $arrData['dataBankAccountType'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 31],
        "string",
        false,
        false,
        true
    );
    $f->addInput(
        getWords("bank account"),
        "dataBankAccount",
        $arrData['dataBankAccount'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 59],
        "string",
        false,
        false,
        true
    );
    $f->addInput(
        getWords("bank account name"),
        "dataBankAccountName",
        $arrData['dataBankAccountName'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 127],
        "string",
        false,
        false,
        true
    );
    //$f->addSelect(getWords("2nd bank code"), "dataBank2Code", getDataListBank($arrData['dataBank2Code'], true, array("value" => "", "text" => "", "selected" => true)), array ("style" => "width:$intDefaultWidth"), "", false);
    //$f->addInput(getWords("2nd bank branch"), "dataBank2Branch", $arrData['dataBank2Branch'], array("style" => "width:$intDefaultWidth", "maxlength" => 63), "string", false, true, true);
    //$f->addInput(getWords("2nd bank account type"), "dataBank2AccountType", $arrData['dataBank2AccountType'], array("style" => "width:$intDefaultWidth", "maxlength" => 31), "string", false, false, true);
    //$f->addInput(getWords("2nd bank account"), "dataBank2Account", $arrData['dataBank2Account'], array("style" => "width:$intDefaultWidth", "maxlength" => 59), "string", false, false, true);
    //$f->addInput(getWords("2nd bank account name"), "dataBank2AccountName", $arrData['dataBank2AccountName'], array("style" => "width:$intDefaultWidth", "maxlength" => 127), "string", false, false, true);
    $f->addTextArea(
        getWords("note"),
        "dataNote",
        $arrData['dataNote'],
        ["style" => "width:$intDefaultWidth", "maxlength" => 255],
        "string",
        false,
        true,
        true
    );
    $strConfirmSave = getWords("save");
    $f->addSubmit(
        "btnSave",
        $strConfirmSave,
        ["onClick" => "javascript:myClient.confirmSave();"],
        true,
        true,
        "",
        "",
        "saveData()"
    );
    $f->addButton("btnClear", getWords("clear form"), ["onClick" => "addNew();"]);
    $formInput = $f->render();
  } else {
    $formInput = "";
  }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$strPageDesc = getWords('employee temporary entry form');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = employeeTempSubmenu($strWordsDataEntry);
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
function getDataByID($strDataID)
{
  global $db;
  global $strIDEmployee;
  //echo $strIDEmployee.$strDataID."<<";
  //exit();
  if ($strIDEmployee == "") {
    $dataEmployeeTemporary = [
        'employee_id'          => "",
        'nickname'             => "",
        'primary_address'      => "",
        'primary_phone'        => "",
        'primary_city'         => "",
        'primary_zip'          => "",
        'id_card'              => "",
        'employee_status'      => "",
        'birthplace'           => "",
        'birthday'             => "",
        'wedding_date'         => "",
        'gender'               => "",
        'email'                => "",
        'weight'               => "",
        'height'               => "",
        'blood_type'           => "",
        'emergency_contact'    => "",
        'emergency_address'    => "",
        'emergency_phone'      => "",
        'emergency_relation'   => "",
        'driver_license_a'     => "",
        'driver_license_b'     => "",
        'driver_license_c'     => "",
        'npwp'                 => "",
        'jamsostek_no'         => "",
        'passport'             => "",
        'inspouse'             => "",
        'bank_account'         => "",
        'bank_account_name'    => "",
        'bank_account_type'    => "",
        'bank_branch'          => "",
        'bank_code'            => "",
        'bank2_account'        => "",
        'bank2_account_name'   => "",
        'bank2_account_type'   => "",
        'bank2_branch'         => "",
        'bank2_code'           => "",
        'note'                 => "",
        'nationality'          => "",
        'religion_code'        => "",
        'education_level_code' => "",
        'transport_code'       => "",
        'transport_fee'        => "",
        'family_status_code'   => "",
        'living_cost_code'     => "",
        'medical_quota_status' => "",
        'id_company'           => "",
        'sub_section_code'     => "",
        'section_code'         => "",
        'department_code'      => "",
        'division_code'        => "",
        'management_code'      => "",
        'branch_code'          => "",
        'position_code'        => "",
        'functional_code'      => "",
        'zakat'                => ""
    ];
  } else if ($strDataID == "") {
    //     echo $strIDEmployee."<<";
    //exit();
    $tblEmployeeTemporary = new cHrdEmployee();
    $dataEmployeeTemporary = $tblEmployeeTemporary->find("id = $strIDEmployee");
  }
  if ($strDataID != "") {
    $tblEmployeeTemporary = new cHrdEmployeeTemporary();
    $dataEmployeeTemporary = $tblEmployeeTemporary->findAll("id = $strDataID", "", "", null, 1, "id");
    $dataEmployeeTemporary = $dataEmployeeTemporary[$strDataID];
  }
  $arrResult['dataEmployeeID'] = $dataEmployeeTemporary['employee_id'];
  $arrResult['dataNickname'] = $dataEmployeeTemporary['nickname'];
  $arrResult['dataPrimaryAddress'] = $dataEmployeeTemporary['primary_address'];
  $arrResult['dataPrimaryPhone'] = $dataEmployeeTemporary['primary_phone'];
  $arrResult['dataPrimaryCity'] = $dataEmployeeTemporary['primary_city'];
  $arrResult['dataPrimaryZip'] = $dataEmployeeTemporary['primary_zip'];
  $arrResult['dataIDCard'] = $dataEmployeeTemporary['id_card'];
  $arrDate = explode("-", $dataEmployeeTemporary['birthday']);
  if ($dataEmployeeTemporary['birthday'] != "") {
    $strtempdate = $arrDate[1] . "/" . $arrDate[2] . "/" . $arrDate[0];
  } else {
    $strtempdate = "";
  }
  $arrResult['dataBirthday'] = $strtempdate;
  $arrResult['dataBirthplace'] = $dataEmployeeTemporary['birthplace'];
  $arrDate = explode("-", $dataEmployeeTemporary['wedding_date']);
  if ($dataEmployeeTemporary['wedding_date'] != "") {
    $strtempdate = $arrDate[1] . "/" . $arrDate[2] . "/" . $arrDate[0];
  } else {
    $strtempdate = "";
  }
  $arrResult['dataWeddingDate'] = $strtempdate;
  $arrResult['dataGender'] = $dataEmployeeTemporary['gender'];
  $arrResult['dataEmail'] = $dataEmployeeTemporary['email'];
  $arrResult['dataWeight'] = $dataEmployeeTemporary['weight'];
  $arrResult['dataHeight'] = $dataEmployeeTemporary['height'];
  $arrResult['dataBloodType'] = $dataEmployeeTemporary['blood_type'];
  $arrResult['dataEmergencyContact'] = $dataEmployeeTemporary['emergency_contact'];
  $arrResult['dataEmergencyAddress'] = $dataEmployeeTemporary['emergency_address'];
  $arrResult['dataEmergencyPhone'] = $dataEmployeeTemporary['emergency_phone'];
  $arrResult['dataEmergencyRelation'] = $dataEmployeeTemporary['emergency_relation'];
  $arrResult['dataDriverLicenseA'] = $dataEmployeeTemporary['driver_license_a'];
  $arrResult['dataDriverLicenseB'] = $dataEmployeeTemporary['driver_license_b'];
  $arrResult['dataDriverLicenseC'] = $dataEmployeeTemporary['driver_license_c'];
  $arrResult['dataNPWP'] = $dataEmployeeTemporary['npwp'];
  //$arrResult['dataNationality']       = $dataEmployeeTemporary['nationality'];
  $arrResult['dataReligion'] = $dataEmployeeTemporary['religion_code'];
  $arrResult['dataEducation'] = $dataEmployeeTemporary['education_level_code'];
  $arrResult['dataTransport'] = $dataEmployeeTemporary['transport_code'];
  $arrResult['dataTransportFee'] = $dataEmployeeTemporary['transport_fee'];
  $arrResult['dataFamily'] = $dataEmployeeTemporary['tax_status_code'];
  //$arrResult['dataFamily']            = $dataEmployeeTemporary['family_status_code'];
  //$arrResult['dataFamilyTax']            = $dataEmployeeTemporary['tax_status_code'];
  //$arrResult['dataLivingCost']        = $dataEmployeeTemporary['living_cost_code'];
  //$arrResult['dataMedicalQuota']      = $dataEmployeeTemporary['medical_quota_status'];
  $arrResult['dataCompany'] = $dataEmployeeTemporary['id_company'];
  $arrResult['dataSubsection'] = $dataEmployeeTemporary['sub_section_code'];
  $arrResult['dataSection'] = $dataEmployeeTemporary['section_code'];
  $arrResult['dataDepartement'] = $dataEmployeeTemporary['department_code'];
  $arrResult['dataDivision'] = $dataEmployeeTemporary['division_code'];
  $arrResult['dataManagement'] = $dataEmployeeTemporary['management_code'];
  $arrResult['dataBranch'] = $dataEmployeeTemporary['branch_code'];
  $arrResult['dataLevel'] = $dataEmployeeTemporary['position_code'];
  $arrResult['dataFunctional'] = $dataEmployeeTemporary['functional_code'];
  //$arrResult['dataZakat']             = $dataEmployeeTemporary['zakat'];
  $arrResult['dataJamsostekNo'] = $dataEmployeeTemporary['jamsostek_no'];
  $arrResult['dataPassport'] = $dataEmployeeTemporary['passport'];
  $arrResult['dataInspouse'] = $dataEmployeeTemporary['inspouse'];
  $arrResult['dataBankAccount'] = $dataEmployeeTemporary['bank_account'];
  $arrResult['dataBankAccountName'] = $dataEmployeeTemporary['bank_account_name'];
  $arrResult['dataBankAccountType'] = $dataEmployeeTemporary['bank_account_type'];
  $arrResult['dataBankBranch'] = $dataEmployeeTemporary['bank_branch'];
  $arrResult['dataBankCode'] = $dataEmployeeTemporary['bank_code'];
  //$arrResult['dataBank2Account']      = $dataEmployeeTemporary['bank2_account'];
  //$arrResult['dataBank2AccountName']  = $dataEmployeeTemporary['bank2_account_name'];
  //$arrResult['dataBank2AccountType']  = $dataEmployeeTemporary['bank2_account_type'];
  //$arrResult['dataBank2Branch']       = $dataEmployeeTemporary['bank2_branch'];
  //$arrResult['dataBank2Code']         = $dataEmployeeTemporary['bank2_code'];
  $arrResult['dataNote'] = $dataEmployeeTemporary['note'];
  $arrResult['dataEmployeeStatus'] = $dataEmployeeTemporary['employee_status'];
  return $arrResult;
}

// fungsi untuk menyimpan data
function saveData()
{
  global $f;
  global $db;
  global $isNew;
  echo $f->getValue('dataInspouse');
  if ($db->connect()) {
    $strmodified_byID = $_SESSION['sessionUserID'];
    $tblEmployeeTemporary = new cHrdEmployeeTemporary();
    $arrDate = explode("/", $f->getValue('dataBirthday'));
    if ($f->getValue('dataBirthday') != "") {
      $strTempBday = $arrDate[2] . "-" . $arrDate[0] . "-" . $arrDate[1];
    } else {
      $strTempBday = "";
    }
    $arrDate = explode("/", $f->getValue('dataWeddingDate'));
    if ($f->getValue('dataWeddingDate') != "") {
      $strTempWedday = $arrDate[2] . "-" . $arrDate[0] . "-" . $arrDate[1];
    } else {
      $strTempWedday = "";
    }
    // echo $strTempWedday;
    // exit();
    $data = [
        "employee_id"          => $f->getValue('dataEmployeeID'),
        "nickname"             => $f->getValue('dataNickname'),
        "primary_address"      => $f->getValue('dataPrimaryAddress'),
        "primary_phone"        => $f->getValue('dataPrimaryPhone'),
        "primary_city"         => $f->getValue('dataPrimaryCity'),
        "primary_zip"          => $f->getValue('dataPrimaryZip'),
        "id_card"              => $f->getValue('dataIDCard'),
        "birthplace"           => $f->getValue('dataBirthplace'),
        "birthday"             => $strTempBday,
        "wedding_date"         => $strTempWedday,
        "gender"               => $f->getValue('dataGender'),
        "email"                => $f->getValue('dataEmail'),
        "weight"               => $f->getValue('dataWeight'),
        "height"               => $f->getValue('dataHeight'),
        "blood_type"           => $f->getValue('dataBloodType'),
        "emergency_contact"    => $f->getValue('dataEmergencyContact'),
        "emergency_address"    => $f->getValue('dataEmergencyAddress'),
        "emergency_phone"      => $f->getValue('dataEmergencyPhone'),
        "emergency_relation"   => $f->getValue('dataEmergencyRelation'),
        "driver_license_a"     => $f->getValue('dataDriverLicenseA'),
        "driver_license_b"     => $f->getValue('dataDriverLicenseB'),
        "driver_license_c"     => $f->getValue('dataDriverLicenseC'),
        "npwp"                 => $f->getValue('dataNPWP'),
        "jamsostek_no"         => $f->getValue('dataJamsostekNo'),
        "passport"             => $f->getValue('dataPassport'),
        "inspouse"             => ($f->getValue('dataInspouse') == 't') ? 't' : 'f',
        "bank_account"         => $f->getValue('dataBankAccount'),
        "bank_account_name"    => $f->getValue('dataBankAccountName'),
        "bank_account_type"    => $f->getValue('dataBankAccountType'),
        "bank_branch"          => $f->getValue('dataBankBranch'),
        "bank_code"            => $f->getValue('dataBankCode'),
        //"bank2_account" => $f->getValue('dataBank2Account'),
        // "bank2_account_name" => $f->getValue('dataBank2AccountName'),
        // "bank2_account_type" => $f->getValue('dataBank2AccountType'),
        // "bank2_branch" => $f->getValue('dataBank2Branch'),
        // "bank2_code" => $f->getValue('dataBank2Code'),
        // "family_status_code" => $f->getValue('dataFamily'),
        // "tax_status_code" => $f->getValue('dataFamilyTax'),
        "tax_status_code"      => $f->getValue('dataFamily'),
        "religion_code"        => $f->getValue('dataReligion'),
        "education_level_code" => $f->getValue('dataEducation'),
        "division_code"        => $f->getValue('dataDivision'),
        "department_code"      => $f->getValue('dataDepartement'),
        "section_code"         => $f->getValue('dataSection'),
        "sub_section_code"     => $f->getValue('dataSubsection'),
        "position_code"        => $f->getValue('dataLevel'),
        "transport_code"       => $f->getValue('dataTransport'),
        "functional_code"      => $f->getValue('dataFunctional'),
        //"zakat" => ($f->getValue('dataZakat') == 't') ? 't' : 'f',
        "id_company"           => $f->getValue('dataCompany'),
        //"living_cost_code" => $f->getValue('dataLivingCost'),
        //"medical_quota_status" => $f->getValue('dataMedicalQuota'),
        //"nationality" => $f->getValue('dataNationality'),
        "management_code"      => $f->getValue('dataManagement'),
        "branch_code"          => $f->getValue('dataBranch'),
        // "transport" => $f->getValue('dataTransport'),
        // "transport_fee" => $f->getValue('dataTransportFee'),
        "employee_status"      => $f->getValue('dataEmployeeStatus'),
        "note"                 => $f->getValue('dataNote')
    ];
    // simpan data trip type
    $bolSuccess = false;
    if ($isNew) {
      // data baru
      $bolSuccess = $tblEmployeeTemporary->insert($data);
    } else {
      $bolSuccess = $tblEmployeeTemporary->update("id='" . $f->getValue('dataID') . "'", $data);
    }
    if ($bolSuccess) {
      if ($isNew) {
        $f->setValue('dataID', $tblEmployeeTemporary->getLastInsertId());
      } else {
        $f->setValue('dataID', $f->getValue('dataID'));
      }
    }
  }
  $f->message = $tblEmployeeTemporary->strMessage;
} // saveData
?>