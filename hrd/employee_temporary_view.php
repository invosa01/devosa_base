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
    "employee_temporary_edit.php",
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
$strTableCompare = "";
$db = new CdbClass;
if ($db->connect()) {
  $strWordsDataEntry = getWords("data entry");
  $strWordsEmployeeTemporaryDataList = getWords("employee temporary data list");
  getUserEmployeeInfo();
  $strIDEmployee = $arrUserInfo['employee_id'];
  //INISIALISASI------------------------------------------------------------------------------------------------------------------
  $strDataID = getRequestValue('dataID');
  // echo "admin".$_SESSION['sessionUserRole'].ROLE_ADMIN;
  if ($_SESSION['sessionUserRole'] >= ROLE_ADMIN) {
    $strIDEmployee = getRequestValue('empID');
  }
  $isNew = ($strDataID == "");
  $intDefaultWidth = "250px";
  $arrData = getDataByID($strDataID);
  //currentdata
  $tblEmployee = new cHrdEmployee();
  $dataEmployee = $tblEmployee->find("employee_id = '$strIDEmployee'");
  //echo "test<br/>";
  //var_dump($arrData);
  //echo $strDataID."-".$strIDEmployee;
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
    $strTableCompare = "Employee Name: <b>" . $dataEmployee['employee_name'] . "</b><table class=\"table\">";
    $strTableCompare .= "<tr>";
    $strTableCompare .= "<th width=\"200\">Field</th>";
    $strTableCompare .= "<th>Current Employee Data</th>";
    $strTableCompare .= "<th>New Temporary Data</th>";
    $strTableCompare .= "<th>Note</th>";
    $strTableCompare .= "</tr>";
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
    $strTableCompare .= strCompareRow("Nickname", $dataEmployee['nickname'], $arrData['dataNickname']);
    $strTableCompare .= strCompareRow(
        getWords("employee status"),
        $ARRAY_EMPLOYEE_STATUS[$dataEmployee['employee_status']],
        $ARRAY_EMPLOYEE_STATUS[$arrData['dataEmployeeStatus']]
    );
    $arrDate = explode("-", $dataEmployee['birthday']);
    $strtempdate = $arrDate[1] . "/" . $arrDate[2] . "/" . $arrDate[0];
    $strTableCompare .= strCompareRow(getWords("birthday"), $strtempdate, $arrData['dataBirthday']);
    $strTableCompare .= strCompareRow(
        getWords("address"),
        $dataEmployee['primary_address'],
        $arrData['dataPrimaryAddress']
    );
    $strTableCompare .= strCompareRow(getWords("city"), $dataEmployee['primary_city'], $arrData['dataPrimaryCity']);
    $strTableCompare .= strCompareRow(getWords("zip"), $dataEmployee['primary_zip'], $arrData['dataPrimaryZip']);
    $strTableCompare .= strCompareRow(getWords("phone"), $dataEmployee['primary_phone'], $arrData['dataPrimaryPhone']);
    $strTableCompare .= strCompareRow(getWords("email"), $dataEmployee['email'], $arrData['dataEmail']);
    $strTableCompare .= strCompareRow(
        getWords("emergency contact"),
        $dataEmployee['emergency_contact'],
        $arrData['dataEmergencyContact']
    );
    $strTableCompare .= strCompareRow(
        getWords("relation"),
        $dataEmployee['emergency_relation'],
        $arrData['dataEmergencyRelation']
    );
    $strTableCompare .= strCompareRow(
        getWords("emergency address"),
        $dataEmployee['emergency_address'],
        $arrData['dataEmergencyAddress']
    );
    $strTableCompare .= strCompareRow(
        getWords("emergency phone"),
        $dataEmployee['emergency_phone'],
        $arrData['dataEmergencyPhone']
    );
    $strTableCompare .= strCompareRow(getWords("weight"), $dataEmployee['weight'], $arrData['dataWeight']);
    $strTableCompare .= strCompareRow(getWords("height"), $dataEmployee['height'], $arrData['dataHeight']);
    $strTableCompare .= strCompareRow(getWords("blood type"), $dataEmployee['blood_type'], $arrData['dataBloodType']);
    $strTableCompare .= strCompareRow(getWords("K T P"), $dataEmployee['id_card'], $arrData['dataIDCard']);
    $strTableCompare .= strCompareRow(
        getWords("driver license a"),
        $dataEmployee['driver_license_a'],
        $arrData['dataDriverLicenseA']
    );
    $strTableCompare .= strCompareRow(
        getWords("driver license b"),
        $dataEmployee['driver_license_b'],
        $arrData['dataDriverLicenseB']
    );
    $strTableCompare .= strCompareRow(
        getWords("driver license c"),
        $dataEmployee['driver_license_c'],
        $arrData['dataDriverLicenseC']
    );
    $strTableCompare .= strCompareRow(getWords("passport"), $dataEmployee['passport'], $arrData['dataPassport']);
    $strTableCompare .= strCompareRow(
        getWords("education level"),
        $dataEmployee['education_level_code'],
        $arrData['dataEducation']
    );
    $arrDate = explode("-", $dataEmployee['wedding_date']);
    $strtempdate = $arrDate[1] . "/" . $arrDate[2] . "/" . $arrDate[0];
    $strTableCompare .= strCompareRow(getWords("wedding date"), $strtempdate, $arrData['dataWeddingDate']);
    //$strTableCompare.=strCompareRow(getWords("employee status"),$dataEmployee['family_status_code'],$arrData['dataFamily']);
    $strTableCompare .= strCompareRow(getWords("note"), $dataEmployee['note'], $arrData['dataNote']);
    //$f->addInput(getWords("birthday"), "dataBirthday", $arrData['dataBirthday'], array("style" => "width:$intDefaultWidth"), "date", false, true, true);
    //$f->addInput(getWords("birthplace"), "dataBirthplace", $arrData['dataBirthplace'], array("style" => "width:$intDefaultWidth","maxlength" => 50), "string", false, true, true);
    //$f->addSelect(getWords("sex"), "dataGender", getDataListGender($arrData['dataGender'], true, array("value" => "1", "text" => "", "selected" => false)),  array ("style" => "width:$intDefaultWidth"), "", false,false);
    //$f->addTextArea(getWords("address"), "dataPrimaryAddress", $arrData['dataPrimaryAddress'], array("style" => "width:$intDefaultWidth", "maxlength" => 255), "string", false, true, true);
    //$f->addInput(getWords("city"), "dataPrimaryCity", $arrData['dataPrimaryCity'], array("style" => "width:$intDefaultWidth", "maxlength" => 131), "string", false, true, true);
    //$f->addInput(getWords("zip"), "dataPrimaryZip", $arrData['dataPrimaryZip'], array("style" => "width:$intDefaultWidth", "maxlength" => 15), "string", false, true, true);
    //$f->addInput(getWords("phone"), "dataPrimaryPhone", $arrData['dataPrimaryPhone'], array("style" => "width:$intDefaultWidth", "maxlength" => 31), "string", false, true, true);
    //$f->addInput(getWords("email"), "dataEmail", $arrData['dataEmail'], array("style" => "width:$intDefaultWidth","maxlength" => 63), "string", false, true, true);
    //$f->addInput(getWords("emergency contact"), "dataEmergencyContact", $arrData['dataEmergencyContact'], array("style" => "width:$intDefaultWidth", "maxlength" => 63), "string", false, true, true);
    //$f->addInput(getWords("relation"), "dataEmergencyRelation", $arrData['dataEmergencyRelation'], array("style" => "width:$intDefaultWidth", "maxlength" => 31), "string", false, true, true);
    //$f->addTextArea(getWords("emergency address"), "dataEmergencyAddress", $arrData['dataEmergencyAddress'], array("style" => "width:$intDefaultWidth", "maxlength" => 255), "string", false, true, true);
    //$f->addInput(getWords("emergency phone"), "dataEmergencyPhone", $arrData['dataEmergencyPhone'], array("style" => "width:$intDefaultWidth", "maxlength" => 31), "string", false, true, true);
    //$f->addInput(getWords("weight")." (kg)", "dataWeight", $arrData['dataWeight'], array("style" => "width:$intDefaultWidth","maxlength" => 7), "numeric", false, true, true);
    //$f->addInput(getWords("height")." (cm)", "dataHeight", $arrData['dataHeight'], array("style" => "width:$intDefaultWidth","maxlength" => 7), "numeric", false, true, true);
    //$f->addSelect(getWords("blood type"), "dataBloodType", getDataListBloodType($arrData['dataBloodType'],true, array( "value" => "", "text" => "", "selected" => false)),  array ("style" => "width:$intDefaultWidth"), "", false, false, true);
    //$f->addInput(getWords("K T P"), "dataIDCard", $arrData['dataIDCard'], array("style" => "width:$intDefaultWidth", "maxlength" => 31), "string", false, true, true);
    //$f->addInput(getWords("driver license a"), "dataDriverLicenseA", $arrData['dataDriverLicenseA'], array("style" => "width:$intDefaultWidth", "maxlength" => 31), "string", false, true, true);
    //$f->addInput(getWords("driver license b"), "dataDriverLicenseB", $arrData['dataDriverLicenseB'], array("style" => "width:$intDefaultWidth", "maxlength" => 31), "string", false, true, true);
    //$f->addInput(getWords("driver license c"), "dataDriverLicenseC", $arrData['dataDriverLicenseC'], array("style" => "width:$intDefaultWidth", "maxlength" => 31), "string", false, true, true);
    //$f->addInput(getWords("nationality"), "dataNationality", $arrData['dataNationality'], array("style" => "width:$intDefaultWidth", "maxlength" => 31), "string", false, true, true);
    //$f->addInput(getWords("passport"), "dataPassport", $arrData['dataPassport'], array("style" => "width:$intDefaultWidth", "maxlength" => 31), "string", false, true, true);
    //$f->addSelect(getWords("religion"), "dataReligion", getDataListReligion($arrData['dataReligion'], true, array("value" => "", "text" => "", "selected" => false)),  array ("style" => "width:$intDefaultWidth"), "", false, true, true);
    //$f->addSelect(getWords("education level"), "dataEducation", getDataListEducationLevel($arrData['dataEducation'], true, array("value" => "", "text" => "", "selected" => true)),  array ("style" => "width:$intDefaultWidth"), "", false, true, true);
    //$f->addInput(getWords("wedding date"), "dataWeddingDate", $arrData['dataWeddingDate'], array("style" => "width:$intDefaultWidth"), "date", false, true, true);
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
        true,
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
    $strTableCompare .= "</table>";
  } else {
    $formInput = "";
  }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$strPageDesc = getWords('employee temporary view');
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
  $strtempdate = $arrDate[1] . "/" . $arrDate[2] . "/" . $arrDate[0];
  $arrResult['dataBirthday'] = $strtempdate;
  $arrResult['dataBirthplace'] = $dataEmployeeTemporary['birthplace'];
  $arrDate = explode("-", $dataEmployeeTemporary['wedding_date']);
  $strtempdate = $arrDate[1] . "/" . $arrDate[2] . "/" . $arrDate[0];
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
  //$arrResult['dataFamily']            = $dataEmployeeTemporary['family_status_code'];
  //$arrResult['dataFamilyTax']            = $dataEmployeeTemporary['tax_status_code'];
  //$arrResult['dataLivingCost']        = $dataEmployeeTemporary['living_cost_code'];
  //$arrResult['dataMedicalQuota']      = $dataEmployeeTemporary['medical_quota_status'];
  $arrResult['dataFamily'] = $dataEmployeeTemporary['tax_status_code'];
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

function strCompareRow($strField, $currentdata, $newdata)
{
  if ($currentdata == $newdata) {
    return "<tr><td>" . $strField . "</td><td>" . $currentdata . "</td><td>" . $newdata . "</td><td>-</td></tr>";
  } else {
    return "<tr><td>" . $strField . "</td><td>" . $currentdata . "</td><td>" . $newdata . "</td><td>Changed</td></tr>";
  }
}

?>