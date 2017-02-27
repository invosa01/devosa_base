<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../includes/datagrid/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_work_schedule.php');
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
    die(getWords('view denied'));
}
$strWordsEntrySchedule = getWords("entry schedule");
$strWordsScheduleList = getWords("schedule list");
$db = new CdbClass;
if ($db->connect()) {
    $strDataID = getRequestValue('dataID');
    $isNew = ($strDataID == "");
    if ($strDataID != "") {
        $arrData = getDataByID($strDataID);
        $arrData['start_time'] = substr($arrData['start_time'], 0, 5);
        $arrData['finish_time'] = substr($arrData['finish_time'], 0, 5);
    } else {
        $arrData['start_time'] = substr(getSetting("start_time"), 0, 5);
        $arrData['finish_time'] = substr(getSetting("finish_time"), 0, 5);
        $arrData['data_id'] = getPostValue('dataID');
        $arrData['link_code'] = getPostValue('dataLinkCode');
        $arrData['table_name'] = getPostValue('dataTableName');
    }
    foreach ($ARRAY_SCHEDULE_TABLENAME as $strTableName => $arrTemp) {
        $$strTableName = ($arrData['table_name'] == $strTableName) ? $arrData['link_code'] : null;
    }
    $isNew = ($strDataID == "");
    if ($bolCanEdit) {
        $f = new clsForm("formInput", 2, "100%", "");
        $f->caption = strtoupper($strWordsINPUTDATA);
        $f->addHidden("dataID", $strDataID);
        $f->addHidden("dataLinkCode", $arrData['link_code']);
        $f->addHidden("dataTableName", $arrData['table_name']);
        $f->addSelect(
            getWords("division"),
            "dataDivision",
            getDataListDivision($Division, true, ""),
            [
                "style"    => "width: 200px",
                "onChange" => "javascript:myClient.setTableName('Division', this.value)"
            ],
            "string",
            false,
            true,
            true
        );
        $f->addSelect(
            getWords("department"),
            "dataDepartment",
            getDataListDepartment($Department, true, ""),
            [
                "style"    => "width: 200px",
                "onChange" => "javascript:myClient.setTableName('Department', this.value)"
            ],
            "string",
            false,
            true,
            true
        );
        $f->addSelect(
            getWords("section"),
            "dataSection",
            getDataListSection($Section, true, ""),
            [
                "style"    => "width: 200px",
                "onChange" => "javascript:myClient.setTableName('Section', this.value)"
            ],
            "string",
            false,
            true,
            true
        );
        $f->addSelect(
            getWords("sub section"),
            "dataSubSection",
            getDataListSubSection($SubSection, true, ""),
            [
                "style"    => "width: 200px",
                "onChange" => "javascript:myClient.setTableName('SubSection', this.value)"
            ],
            "string",
            false,
            true,
            true
        );
        $f->addInputAutoComplete(
            getWords("employee ID"),
            "dataEmployee",
            getDataEmployee($Employee),
            [
                "onChange" => "javascript:myClient.setTableName('Employee', this.value)",
                "style"    => "width:200"
            ],
            "string",
            false
        );
        $f->addLabelAutoComplete("", "dataEmployee", "");
        $f->addSelect(
            getWords("workday"),
            "dataWorkday",
            getDataListDayName(-1, true, -1),
            "",
            "integer",
            false,
            true,
            true
        );
        $f->addCheckBox(
            getWords("day off"),
            "dataDayOff",
            null,
            ["onChange" => "javascript:myClient.setDayOff(this.checked)"],
            null,
            false,
            true,
            true
        );
        $f->addInput(
            getWords("start time"),
            "dataStartTime",
            $arrData['start_time'],
            ["size" => 10, "maxlength" => 5, "class" => "t input_mask mask_time"],
            "string",
            false,
            true,
            true
        );
        $f->addInput(
            getWords("finish time"),
            "dataFinishTime",
            $arrData['finish_time'],
            ["size" => 10, "maxlength" => 5, "class" => "t input_mask mask_time"],
            "string",
            false,
            true,
            true
        );
        $f->addSubmit(
            "btnSave",
            getWords("save"),
            [
                "onClick" => "fixLinkCode(); return confirm('" . getWords(
                        'do you want to save this entry?'
                    ) . "');"
            ],
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
}
function getDataByID($strDataID)
{
    global $db;
    $tblWorkSchedule = new cHrdWorkSchedule();
    $arrResult = $tblWorkSchedule->findAll("id = $strDataID", "", "", null, 1);
    return $arrResult[0];
}

$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
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
    $strDataID = $f->getValue('dataID');
    if ($f->getValue('dataTableName') != "") {
        $strmodified_byID = $_SESSION['sessionUserID'];
        if ($f->getValue('dataDayOff')) {
            $strDayOff = 't';
            $strStart = null;
            $strFinish = null;
        } else {
            $strDayOff = 'f';
            $strStart = $f->getValue('dataStartTime');
            $strFinish = $f->getValue('dataFinishTime');
        }
        $strWorkday = ($f->getValue('dataWorkday') == "") ? -1 : $f->getValue('dataWorkday');
        $dataHrdWorkSchedule = new cHrdWorkSchedule();
        $data = [
            "link_code"   => $f->getValue('dataLinkCode'),
            "table_name"  => $f->getValue('dataTableName'),
            "workday"     => $strWorkday,
            "day_off"     => $strDayOff,
            "start_time"  => $strStart,
            "finish_time" => $strFinish
        ];
        // simpan data -----------------------
        $bolSuccess = false;
        if ($isNew) {
            // data baru
            $bolSuccess = $dataHrdWorkSchedule->insert($data);
        } else {
            $bolSuccess = $dataHrdWorkSchedule->update(/*pk*/
                "id='" . $strDataID . "'", /*data to update*/
                $data
            );
        }
        $f->message = $dataHrdWorkSchedule->strMessage;
        $f->msgClass = ($bolSuccess) ? "bgOK" : "bgError";
    } else {
        $f->message = "Please fill either the division, department, section, or sub section";
    }
    $f->msgClass = ($bolSuccess) ? "bgOK" : "bgError";
} // saveData
?>