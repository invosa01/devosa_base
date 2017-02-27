<?php
include_once(dirname(__FILE__) . '/../includes/model/model.php');
function getDataListGroup($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("adm_group", getWords("group"));
    $arrData = $tbl->generateList(
        "active = 1",
        "id_adm_group",
        null,
        "id_adm_group",
        ["code", "name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListReligion($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_religion", getWords("religion"));
    $arrData = $tbl->generateList(null, null, null, "code", "name", $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListMajor($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_major", getWords("major"));
    $arrData = $tbl->generateList(null, null, null, "code", "name", $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListEducation($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_education_level", getWords("education"));
    $arrData = $tbl->generateList(null, null, null, "code", "name", $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

/*
function getDataListPosition($default = null, $isHasEmpty = false, $emptyData = null)
{
  $tbl = new cModel("hrd_position", getWords("position_aplied"));
  $arrData = $tbl->generateList(null, null, null, "position_code",$isHasEmpty, $emptyData);
  if ($default != null || $default != "")
    while(list($key, $val) = each($arrData))
    {
      $temp = &$arrData[$key];
      if ($val['value'] == $default)
        $temp['selected'] = true;
      else
        $temp['selected'] = false;
    }
  return $arrData;
}*/
function getDataListEducationLevel($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_education_level", getWords("education level"));
    $arrData = $tbl->generateList(null, null, null, "code", "name", $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListFamilyStatus($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_family_status", getWords("family status"));
    $arrData = $tbl->generateList(null, null, null, "family_status_code", "note", $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListLivingCost($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_minimum_living_cost", getWords("minimum living cost"));
    $arrData = $tbl->generateList(null, null, null, "code", "note", $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListOrganizationDetailByIdOrganization(
    $idOrganization,
    $default = null,
    $isHasEmpty = false,
    $emptyData = null
) {
    $tbl = new cModel("hrd_organization_detail", getWords("organization"));
    $arrData = $tbl->generateList(/*WHERE */
        "id_hrd_organization = " . intval($idOrganization),
        null,
        null,
        "id",
        ["code", "name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

/*parameter $idParentOrganizationDetail : can be array or single string*/
function getDataListOrganizationDetailByIdParent(
    $idParentOrganizationDetail,
    $default = null,
    $isHasEmpty = false,
    $emptyData = null
) {
    $tbl = new cModel("hrd_organization_detail", getWords("organization"));
    if (is_array($idParentOrganizationDetail)) {
        if (count($idParentOrganizationDetail) > 0) {
            $arrData = $tbl->generateList(/*WHERE */
                "id_hrd_organization_detail IN (" . implode(", ", $idParentOrganizationDetail) . ")",
                null,
                null,
                "id",
                ["code", "name"],
                $isHasEmpty,
                $emptyData
            );
        } else {
            $arrData = [];
        }
    } else {
        $arrData = $tbl->generateList(/*WHERE */
            "id_hrd_organization_detail = " . intval($idParentOrganizationDetail),
            null,
            null,
            "id",
            ["code", "name"],
            $isHasEmpty,
            $emptyData
        );
    }
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListShiftType($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_shift_type", getWords("shift type"));
    $arrData = $tbl->generateList(null, null, null, "code", "code", $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListModule($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("adm_module", getWords("module"));
    $arrData = $tbl->generateList(
        "visible = 1",
        "id_adm_module",
        null,
        "id_adm_module",
        "name",
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListMonth($default = null, $isHasEmpty = false, $emptyData = null)
{
    $arrMonth = [
        1 => "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec"
    ];
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    foreach ($arrMonth as $key => $val) {
        if ($default == $key) {
            $arrData[] = ["value" => $key, "text" => $val, "selected" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => $val, "selected" => false];
        }
    }
    return $arrData;
}

function getDataListYear($default = null, $isHasEmpty = false, $emptyData = null, $limit = 10, $isAsc = false)
{
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    $currentYear = intval(date("Y"));
    if ($isAsc) {
        $currentYear -= ($limit / 2);
    } else {
        $currentYear += ($limit / 2);
    }
    for ($i = 1 - $limit; $i <= 0; $i++) {
        if ($currentYear == $default) {
            $arrData[] = ["value" => $currentYear, "text" => $currentYear, "selected" => true];
        } else {
            $arrData[] = ["value" => $currentYear, "text" => $currentYear, "selected" => false];
        }
        if ($isAsc) {
            $currentYear++;
        } else {
            $currentYear--;
        }
    }
    for ($i = 1; $i <= $limit; $i++) {
        if ($currentYear == $default) {
            $arrData[] = ["value" => $currentYear, "text" => $currentYear, "selected" => true];
        } else {
            $arrData[] = ["value" => $currentYear, "text" => $currentYear, "selected" => false];
        }
        if ($isAsc) {
            $currentYear++;
        } else {
            $currentYear--;
        }
    }
    return $arrData;
}

function getDataListShiftGroup($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_shift_group", getWords("shift group"));
    $arrData = $tbl->generateList(null, "name", null, "id", "name", $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListHolidayType($default = null, $isHasEmpty = false, $emptyData = null)
{
    global $ARRAY_HOLIDAY_TYPE;
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    foreach ($ARRAY_HOLIDAY_TYPE as $key => $val) {
        if ($default == $key) {
            $arrData[] = ["value" => $key, "text" => getWords($ARRAY_HOLIDAY_TYPE[$key]), "selected" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => getWords($ARRAY_HOLIDAY_TYPE[$key]), "selected" => false];
        }
    }
    return $arrData;
}//getHolidayType
// fungsi untuk mencari info apakah libur nasional atau tidak untuk tanggal tertentu
// input: db, tanggal : YYYY-MM-DD (dianggap sudah valid)
//TODO: tambahkan validasi untuk mengecek parameter
function isCompanyHoliday($strDate)
{
    //find day of week
    $arrDate = explode("-", $strDate);
    $intTimeStamp = mktime(10, 0, 0, intval($arrDate[1]), intval($arrDate[2]), intval($arrDate[0]));
    $dow = intval(date("w", $intTimeStamp)); //$dow = 0, sunday, 6 = saturday
    if (!isset($GLOBALS['isSundayHoliday'])) {
        $GLOBALS['isSundayHoliday'] = isSundayHoliday();
    }
    if (!isset($GLOBALS['isSaturdayHoliday'])) {
        $GLOBALS['isSaturdayHoliday'] = isSaturdayHoliday();
    }
    if ($dow == 0 && $GLOBALS['isSundayHoliday']) {
        return true;
    }
    if ($dow == 6 && $GLOBALS['isSaturdayHoliday']) {
        return true;
    }
    $tblCalendar = new cModel("hrd_calendar", "calendar");
    if ($tblCalendar->findCount("CONVERT(varchar(10), holiday, 120) = '$strDate' AND status = 1") > 0) {
        return true;
    }
    return false;
}

function getDefaultStartTime()
{
    return getSetting("start_time");
}

function getDefaultFinishTime()
{
    return getSetting("finish_time");
}

function isSaturdayHoliday()
{
    return getSetting("saturday") == 't';
}

function isSundayHoliday()
{
    return getSetting("sunday") == 't';
}

function getDataListAbsenceType($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_absence_type", getWords("absence type"));
    $arrData = $tbl->generateList(null, "code", null, "code", ["code", "note"], $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataHolidayByRange($strFrom, $strThru)
{
    //cek holiday
    $arrHoliday = [];
    $tblCalendar = new cModel("hrd_calendar");
    $strCriteria = "CONVERT(varchar(10), holiday, 120) >= '$strFrom' AND CONVERT(varchar(10), holiday, 120) <= '$strThru' ";
    $strCriteria .= " AND status=1";
    $arrHoliday = $tblCalendar->findAll(
        $strCriteria,
        "CONVERT(VARCHAR(10), holiday, 120) AS holiday_date",
        null,
        null,
        null,
        "holiday_date"
    );
    return $arrHoliday;
}

function getDataListGender($default = null, $isHasEmpty = false, $emptyData = null)
{
    global $ARRAY_GENDER;
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    foreach ($ARRAY_GENDER as $key => $value) {
        if ($key == $default) {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
        }
    }
    return $arrData;
}

function getDataListBloodType($default = null, $isHasEmpty = false, $emptyData = null)
{
    global $ARRAY_BLOOD_TYPE;
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    foreach ($ARRAY_BLOOD_TYPE as $key => $value) {
        if ($key == $default) {
            $arrData[] = ["value" => $key, "text" => $value, "selected" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => $value, "selected" => false];
        }
    }
    return $arrData;
}

function getDataListMaritalStatus($default = null, $isHasEmpty = false, $emptyData = null)
{
    global $ARRAY_MARITAL_STATUS;
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    foreach ($ARRAY_MARITAL_STATUS as $key => $value) {
        if ($key == $default) {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
        }
    }
    echo var_dump();
    return $arrData;
}

function getDataListEmployeeStatus($default = null, $isHasEmpty = false, $emptyData = null)
{
    global $ARRAY_EMPLOYEE_STATUS;
    $arrData = [];
    if ($default != null) {
        $emptyData['selected'] = false;
    }
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    foreach ($ARRAY_EMPLOYEE_STATUS as $key => $value) {
        if ($key == $default && !($emptyData['selected'])) {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
        }
    }
    return $arrData;
}

function getDataListPartialAbsenceType($default = null, $isHasEmpty = false, $emptyData = null)
{
    global $ARRAY_PARTIAL_ABSENCE_TYPE;
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    foreach ($ARRAY_PARTIAL_ABSENCE_TYPE as $key => $value) {
        if ($key == $default) {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
        }
    }
    return $arrData;
}

function getDataListEmployeeActive($default = null, $isHasEmpty = false, $emptyData = null)
{
    global $ARRAY_EMPLOYEE_ACTIVE;
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    foreach ($ARRAY_EMPLOYEE_ACTIVE as $key => $value) {
        if ($key === $default) {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
        }
    }
    return $arrData;
}

function getDataListRequestStatus($default = null, $isHasEmpty = false, $emptyData = null)
{
    global $ARRAY_REQUEST_STATUS;
    $arrData = [];
    if ($default != null) {
        $emptyData['selected'] = false;
    }
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    foreach ($ARRAY_REQUEST_STATUS as $key => $value) {
        if ($key == $default && !($emptyData['selected'])) {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
        }
    }
    return $arrData;
}

// added for OTMA function [bm]
function getDataListCompany($default = null, $isHasEmpty = false, $emptyData = null, $criteria = null)
{
    $tbl = new cModel("hrd_company", getWords("Company"));
    $arrData = $tbl->generateList($criteria, "id", null, "id", "company_name", $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
    /*
    global $ARRAY_COMPANY;
    $arrData = array();
    if ($isHasEmpty) $arrData[] = $emptyData;
    foreach($ARRAY_COMPANY as $key => $value)
      if ($key == $default)
        $arrData[] = array("value" => $key, "text" => $value, "selected" => true);
      else
        $arrData[] = array("value" => $key, "text" => $value, "selected" => false);
    return $arrData;*/
}

function getDataListManagement($default = null, $isHasEmpty = false, $emptyData = null, $criteria = "")
{
    global $strDataCompany;
    $criteria .= "AND management_code LIKE '%" . printCompanyCode($strDataCompany) . "%'";
    $tbl = new cModel("hrd_management", getWords("Management"));
    $arrData = $tbl->generateList(
        "1=1 " . $criteria,
        "id",
        null,
        "management_code",
        ["management_code", "management_name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}//getDataListManagement
function getDataListDivision($default = null, $isHasEmpty = false, $emptyData = null, $criteria = "")
{
    global $strDataCompany;
    $criteria .= (($strDataCompany == "") ? "" : "AND management_code LIKE '%" . printCompanyCode(
            $strDataCompany
        ) . "%'");
    $tbl = new cModel("hrd_division", getWords("division"));
    $arrData = $tbl->generateList(
        "1=1 " . $criteria,
        "id",
        null,
        "division_code",
        ["division_code", "division_name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}//getDataListDivision
function getDataListDepartment($default = null, $isHasEmpty = false, $emptyData = null, $criteria = "")
{
    global $strDataCompany;
    $criteria .= (($strDataCompany == "") ? "" : "AND management_code LIKE '%" . printCompanyCode(
            $strDataCompany
        ) . "%'");
    $tbl = new cModel("hrd_department", getWords("Departemen"));
    $arrData = $tbl->generateList(
        "1=1 " . $criteria,
        "id",
        null,
        "department_code",
        ["department_code", "department_name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}//getDataListDepartment
function getDataListSubDepartment($default = null, $isHasEmpty = false, $emptyData = null, $criteria = "")
{
    global $strDataCompany;
    $criteria .= (($strDataCompany == "") ? "" : "AND management_code LIKE '%" . printCompanyCode(
            $strDataCompany
        ) . "%'");
    $tbl = new cModel("hrd_sub_department", getWords("Sub Departemen"));
    $arrData = $tbl->generateList(
        "1=1 " . $criteria,
        "id",
        null,
        "sub_department_code",
        ["sub_department_code", "sub_department_name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}//getDataListSubDepartment
function getDataListSection($default = null, $isHasEmpty = false, $emptyData = null, $criteria = "")
{
    global $strDataCompany;
    $criteria .= (($strDataCompany == "") ? "" : "AND management_code LIKE '%" . printCompanyCode(
            $strDataCompany
        ) . "%'");
    $tbl = new cModel("hrd_section", getWords("section"));
    $arrData = $tbl->generateList(
        "1=1 " . $criteria,
        "id",
        null,
        "section_code",
        ["section_code", "section_name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}//getDataListSection
function getDataListSubSection($default = null, $isHasEmpty = false, $emptyData = null, $criteria = "")
{
    global $strDataCompany;
    $criteria .= (($strDataCompany == "") ? "" : "AND management_code LIKE '%" . printCompanyCode(
            $strDataCompany
        ) . "%'");
    $tbl = new cModel("hrd_sub_section", getWords("subsection"));
    $arrData = $tbl->generateList(
        "1=1 " . $criteria,
        "id",
        null,
        "sub_section_code",
        ["sub_section_code", "sub_section_name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}//getDataListSubSection
function getDataListPosition($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_position", getWords("position"));
    $arrData = $tbl->generateList(
        null,
        "position_code",
        null,
        "position_code",
        ["position_code", "position_name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListFunctionalPosition($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_functional", getWords("functional position"));
    $arrData = $tbl->generateList(
        null,
        "functional_code",
        null,
        "functional_code",
        ["functional_code", "functional_name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListSalaryGrade($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_salary_grade", getWords("salary grade"));
    $arrData = $tbl->generateList(null, "grade_code", null, "grade_code", ["grade_code"], $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataEmployee($default = null, $isHasEmpty = false, $emptyData = null)
{
    global $strKriteriaCompany;
    $tbl = new cModel("hrd_employee", getWords("employee"));
    $arrData = $tbl->generateList(
        "active = 1 " . $strKriteriaCompany,
        "employee_id",
        null,
        "employee_id",
        ["employee_name"]
    );
    if (!empty($default)) {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if (trim($val['value']) == trim($default)) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getEvaluationSubheader($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_evaluation_criteria", getWords("evaluation criteria"));
    $arrData = $tbl->generateList(null, "subheader", null, "subheader", ["subheader"], true, true);
    return $arrData;
}

function getDataListEvaluationCategory($default = null, $isHasEmpty = false, $emptyData = null, $criteria = "")
{
    $tbl = new cModel("hrd_evaluation_category", getWords("evaluation_category"));
    $arrData = $tbl->generateList($criteria, "id", null, "id", ["category"], $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListTrainingCategory($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_training_category", getWords("training category"));
    $arrData = $tbl->generateList(null, "id", null, "id", ["training_category"], $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListTrainingCategoryType($default = null, $isHasEmpty = false, $emptyData = null)
{
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = ["value" => $emptyData, "text" => ""];
    }
    $db = new CdbClass;
    if ($db->connect()) {
        $strSQL = "SELECT t1.id, t1.code, t1.name, t2.id AS id_category, t2.training_category FROM hrd_training_type AS t1 LEFT JOIN hrd_training_category AS t2
                ON t1.id_category = t2.id";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $arrData[] = [
                "value" => $rowDb['id'] . "|" . $rowDb['id_category'],
                "text"  => $rowDb['training_category'] . " - " . $rowDb['code'] . "(" . $rowDb['name'] . ")"
            ];
        }
        if ($default != null || $default != "") {
            while (list($key, $val) = each($arrData)) {
                $temp = &$arrData[$key];
                if ($val['value'] == $default) {
                    $temp['selected'] = true;
                } else {
                    $temp['selected'] = false;
                }
            }
        }
    }
    return $arrData;
}

function getDataListBranch($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_branch", getWords("branch"));
    $arrData = $tbl->generateList(
        null,
        "branch_code",
        null,
        "branch_code",
        ["branch_code", "branch_name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListBank($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_bank", getWords("bank"));
    $arrData = $tbl->generateList(
        null,
        "bank_code",
        null,
        "bank_code",
        ["bank_code", "bank_name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListTripType($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_trip_type", getWords("trip type"));
    $arrData = $tbl->generateList(
        null,
        "id",
        null,
        "id",
        ["trip_type_code", "trip_type_name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListDestination($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_destination", getWords("trip type"));
    $arrData = $tbl->generateList(
        null,
        "destination_name",
        null,
        "destination_name",
        ["destination_code", "destination_name"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListCurrency($default = null, $isHasEmpty = false, $emptyData = null)
{
    global $ARRAY_CURRENCY;
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    foreach ($ARRAY_CURRENCY as $key => $value) {
        if ($key == $default) {
            $arrData[] = ["value" => $value, "text" => $value, "selected" => true];
        } else {
            $arrData[] = ["value" => $value, "text" => $value, "selected" => false];
        }
    }
    return $arrData;
}

function getDataListActive($default = null, $isHasEmpty = false, $emptyData = null)
{
    $arrActive = ['' => '', 't' => 'active', 'f' => 'not active'];
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    foreach ($arrActive as $key => $value) {
        if ($key === $default) {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
        }
    }
    return $arrData;
}

function getDataList($data, $indexed = true, $default = null, $isHasEmpty = false, $emptyData = null)
{
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    if ($indexed) {
        foreach ($data as $key => $value) {
            if ($key == $default) {
                $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
            } else {
                $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
            }
        }
    } else {
        foreach ($data as $value) {
            if ($value == $default) {
                $arrData[] = ["value" => $value, "text" => getWords($value), "selected" => true];
            } else {
                $arrData[] = ["value" => $value, "text" => getWords($value), "selected" => false];
            }
        }
    }
    if ($default == null && $isHasEmpty) {
        $arrData[1]['selected'] = true;
    }
    if ($default == null && !$isHasEmpty) {
        $arrData[0]['selected'] = true;
    }
    return $arrData;
}

function getDataListTrainingType($default = null, $isHasEmpty = false, $emptyData = null, $criteria = "")
{
    $tbl = new cModel("hrd_training_type", getWords("training type"));
    $arrData = $tbl->generateList(
        $criteria,
        "training_type",
        null,
        "training_type",
        ["training_type", "note"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListTrainingVendor($default = null, $isHasEmpty = false, $emptyData = null, $criteria = "")
{
    $tbl = new cModel("hrd_training_vendor", getWords("training vendor"));
    $arrData = $tbl->generateList($criteria, "id", null, "id", ["id", "name_vendor"], $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListEmployeeFamily($default = null, $isHasEmpty = false, $emptyData = null, $criteria = "")
{
    global $ARRAY_FAMILY_RELATION;
    $tbl = new cModel("hrd_employee_family", getWords("family member"));
    $arrData = $tbl->generateList($criteria, "name", null, "name", ["name", "relation"], $isHasEmpty, $emptyData);
    foreach ($arrData as $index => $arrDetail) {
        if ($arrDetail['value'] != "") {
            list($strTemp1, $strTemp2) = explode(" - ", $arrDetail['text']);
            $arrData[$index]['text'] = $strTemp1 . " - " . $ARRAY_FAMILY_RELATION[$strTemp2];
        }
    }
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListMedicalTreatmentType(
    $default = null,
    $isHasEmpty = false,
    $emptyData = null,
    $bolIncludeOutpatient = false
) {
    global $ARRAY_MEDICAL_TREATMENT_GROUP;
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    foreach ($ARRAY_MEDICAL_TREATMENT_GROUP as $key => $value) {
        if ($bolIncludeOutpatient || $value != "outpatient") {
            if ($key === $default) {
                $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
            } else {
                $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
            }
        }
    }
    return $arrData;
}

function getDataListDonationType($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_donation_type", getWords("donation type"));
    $arrData = $tbl->generateList(null, "name", null, "code", ["code", "name"], $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataListDayName($default = null, $isHasEmpty = false, $emptyData = null)
{
    $arrDay = [0 => "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = [$emptyData => ""];
    }
    foreach ($arrDay as $key => $val) {
        if ($default == $key) {
            $arrData[] = ["value" => $key, "text" => $val, "selected" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => $val, "selected" => false];
        }
    }
    return $arrData;
}

function getDataLivingCost($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_minimum_living_cost", getWords("living cost"));
    $arrData = $tbl->generateList(null, null, null, "code", "note", $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataCheckBoxMRFRequestType($default = null, $isHasEmpty = false, $emptyData = null)
{
    $arrData = [];
    if ($isHasEmpty) {
        if ($emptyData == null) {
            $emptyData = ["value" => "", "text" => ""];
        }
        $arrData[] = $emptyData;
    }
    $ARRAY_MRF_REQUEST_TYPE = [0 => "additional", "subtitution"];
    foreach ($ARRAY_MRF_REQUEST_TYPE as $key => $value) {
        if ($key == $default) {
            $arrData[] = ["value" => $key, "text" => getWords($value), "checked" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => getWords($value), "checked" => false];
        }
    }
    return $arrData;
}//
function getDataCheckBoxMRFBudgetType($default = null, $isHasEmpty = false, $emptyData = null)
{
    $arrData = [];
    if ($isHasEmpty) {
        if ($emptyData == null) {
            $emptyData = ["value" => "", "text" => ""];
        }
        $arrData[] = $emptyData;
    }
    $ARRAY_MRF_BUDGET_TYPE = [0 => "according to budget", "over budget"];
    foreach ($ARRAY_MRF_BUDGET_TYPE as $key => $value) {
        if ($key == $default) {
            $arrData[] = ["value" => $key, "text" => getWords($value), "checked" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => getWords($value), "checked" => false];
        }
    }
    return $arrData;
}//
// fungsi untuk buat array data MRF, index adalah ID,
// input : bolAll = jika false hanya menampilkan yang masih aktif (belum terpenuhi)
//         includeID = tampilkan juga data dengan id tertentu
// value adalah : Jabatan | Departemen | Nomor MRF
function getDataListMRF($default = null, $isHasEmpty = false, $emptyData = null, $bolAll = false, $includeID = "")
{
    $tbl = new cModel("hrd_recruitment_need", getWords("recruitment need"));
    if ($isHasEmpty) {
        if ($emptyData == null) {
            $emptyData = ["value" => "", "text" => ""];
        }
    }
    $strActive = ($bolAll) ? "" : " AND number_ok < \"number\" ";
    $strInclude = ($includeID == "") ? "" : "OR id = '$includeID' ";
    //$strKriteria = "(status <> ".REQUEST_STATUS_DENIED. " $strActive) $strInclude " ;
    $strKriteria = "(status >= " . REQUEST_STATUS_APPROVED . " $strActive) $strInclude "; // hanya ambil yang sudah approve saja
    $arrData = $tbl->generateList(
        $strKriteria,
        "position_code",
        null,
        "id",
        ["request_number", "position_code", "department_code"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

function getDataCheckBoxGender($default = null, $isHasEmpty = false, $emptyData = null)
{
    global $ARRAY_GENDER;
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    foreach ($ARRAY_GENDER as $key => $value) {
        if ($key == $default) {
            $arrData[] = ["value" => $key, "text" => getWords($value), "checked" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => getWords($value), "checked" => false];
        }
    }
    return $arrData;
}

function getDataListCandidateLanguage($default = null, $isHasEmpty = false, $emptyData = null)
{
    $arrData = [];
    if ($isHasEmpty) {
        if ($emptyData == null) {
            $emptyData = ["value" => "", "text" => ""];
        }
        $arrData[] = $emptyData;
    }
    $arrLanguageList = ["English", "Japanese", "Mandarin", "French"];
    foreach ($arrLanguageList as $value) {
        if ($value == $default) {
            $arrData[] = ["value" => $value, "text" => $value, "selected" => true];
        } else {
            $arrData[] = ["value" => $value, "text" => $value, "selected" => false];
        }
    }
    return $arrData;
}//
function getDataCheckBoxCandidateLanguageSkill($default = null)
{
    $arrData = [];
    $arrLanguageList = [1, 2, 3, 4, 5];
    foreach ($arrLanguageList as $value) {
        if ($value == $default) {
            $arrData[] = ["value" => $value, "text" => $value, "checked" => true];
        } else {
            $arrData[] = ["value" => $value, "text" => $value, "checked" => false];
        }
    }
    return $arrData;
}//
function getDataSelectCandidateLanguageSkill($default = null)
{
    $arrData = getDataCheckBoxCandidateLanguageSkill();
    foreach ($arrData as $value) {
        unset($value["checked"]);
    }
    $arrData[0]["selected"] = true;
    return $arrData;
}//
/*
====================================================================================================================
--------------------------------------------------------------------------------------------------------------------
DESK 		: Fungsi- fungsi dibawah ini digunakan untuk migrasi modul recruitment
              dari aplikasi smart-U ke DeVosa.
Update	: 2011/12/13
--------------------------------------------------------------------------------------------------------------------
====================================================================================================================
*/
//====================== Begin GET ROLE=============================================================================
function getDataGroupByGroupRole($idGroupRole)
{
    $tbl = new cModel("adm_group");
    $arrData = $tbl->findByGroupRole($idGroupRole, null, "id_adm_group");
    return $arrData;
}

function getDataGroupRoleCandidate()
{
    return getDataGroupByGroupRole(ROLE_CANDIDATE);
}

function getDataGroupRoleSupervisor()
{
    return getDataGroupByGroupRole(ROLE_SUPERVISOR);
}

function getDataGroupRoleEmployee()
{
    return getDataGroupByGroupRole(ROLE_EMPLOYEE);
}

//========================= END ROLE =============================================================================
//============BEGIN object global, untuk mengatur hak akses =======================================================
include_once("cls_permission.php");
$objUP = new clsUserPermission(); //Cek class ini di cls_permision.php
$strGlobalEmployeeFilter = $objUP->genFilterEmployee(); // untuk keperluan melakuan filter terhadap setiap data karyawan
//=============== End ==============================================================================================
/*================= BEGIN code candidate next =====================================================================
 mengambil kode user untuk data kandidat
 strDate : jika diisi, maka jadi acuan tahun, jika kosong dianggap tahun sekarang */
function getDataNextCandidateCode($strDate = "")
{
    include_once("common_function.php");
    $tbl = new cModel("hrd_candidate");
    if ($strDate == "") {
        $strDate = date("Y-m-d");
    }
    $strSQL = "
      SELECT max(UPPER(candidate_code)) AS candidate_code FROM hrd_candidate
      WHERE (NOT candidate_code IS NULL) AND candidate_code <> ''
      AND EXTRACT(year FROM application_date) = EXTRACT(year FROM date '$strDate')
    ";
    $arrData = $tbl->query($strSQL);
    if (count($arrData) > 0) {
        if ($arrData[0]['candidate_code'] != "") {
            //increment 1
            //format candidate code adalah CIGYYNNNN
            $number = intval(substr($arrData[0]['candidate_code'], 5));
            $number += 1;
            $number = leadingZero($number, 4);
            $newCode = substr($arrData[0]['candidate_code'], 0, 5) . $number;
            return $newCode;
        }
    }
    //not found/error or no candidate code in the table...then....
    $number = 1;
    $number = leadingZero($number, 4);
    $newCode = "CIG" . date("y") . $number;
    return $newCode;
}

//============================ END CANDICATE CODE ===========================================================
//==========================BEGIN GET DATA MARITAL STATUS===================================================
function getDataListMaritalStatusCandidate($default = null, $isHasEmpty = false, $emptyData = null)
{
    global $ARR_DATA_MARITAL_STATUS_CANDIDATE;
    $arrData = [];
    if ($isHasEmpty) {
        $arrData[] = $emptyData;
    }
    $arrData[0]["selected"] = false;
    foreach ($ARR_DATA_MARITAL_STATUS_CANDIDATE as $key => $value) {
        if ($key == $default) {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => true];
        } else {
            $arrData[] = ["value" => $key, "text" => getWords($value), "selected" => false];
        }
    }
    return $arrData;
}

//=================== END DATA MARITAL STATUS ===============================================================
/// ========================== GET DATA RECRUITMENT STEP=========================================================
function getDataListRecruitmentProcessTypeStep($default = null, $isHasEmpty = false, $emptyData = null)
{
    //$strSQL  = "SELECT MAX(step) AS maks FROM hrd_recruitment_process_type ";
    $tbl = new cModel("hrd_recruitment_process_type", getWords("recruitment process type"));
    $arrQuery = $tbl->query("SELECT MAX(step) AS step FROM " . $tbl->strTableName . ";");
    $intStep = 1;
    if (count($arrQuery) > 0) {
        $intStep = $arrQuery[0]['step'] + 1;
    }
    $arrData = [];
    if ($isHasEmpty) {
        if ($emptyData == null) {
            $emptyData = ["value" => "", "text" => ""];
        }
        $arrData[] = $emptyData;
    }
    for ($i = 1; $i <= $intStep; $i++) {
        if ($default != null || $default != "") {
            if ($default == $i) {
                $arrData[] = ["value" => $i, "text" => $i, "selected" => true];
            } else {
                $arrData[] = ["value" => $i, "text" => $i, "selected" => false];
            }
        } else {
            $arrData[] = ["value" => $i, "text" => $i, "selected" => false];
        }
    }
    return $arrData;
}

//============================ END DATA RECRUITMENT  ===============================================================
// ============================ BEGIN GET DATA LIST CANDIDATE =====================================================
function getDataListCandidateReference($type, $default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_candidate_reference", getWords("reference"));
    if ($isHasEmpty) {
        if ($emptyData == null) {
            $emptyData = ["value" => "", "text" => ""];
        }
    }
    $arrData = $tbl->generateList("type=$type", "reference", null, "reference", "reference", $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

//=========================== END CANDIDATR REFERE==================================================================
//================BEGIN Mengambil daftar data wilayah ==============================================================
function getDataListWilayah($default = null, $isHasEmpty = false, $emptyData = null, $kriteria = null)
{
    $tbl = new cModel("hrd_wilayah", getWords("Wilayah"));
    $arrData = $tbl->generateList($kriteria, "wilayah_name", null, "id", "wilayah_name", $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

//=========================== END ======================================================================================
//============== GET DATA VENDOR======================================================================
function getDataListReference($default = null, $isHasEmpty = false, $emptyData = null, $criteria = "")
{
    global $strDataCompany;
    $tbl = new cModel("hrd_candidate_reference", getWords("candidate reference"));
    $arrData = $tbl->generateList(
        "1=1 " . $criteria,
        "id",
        null,
        "reference",
        ["name", "reference"],
        $isHasEmpty,
        $emptyData
    );
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

//============================================================================================================
/// Untuk mendapatkan infromasi level pendidikan karyawan =====================================================
function getDataListAcademic($default = null, $isHasEmpty = false, $emptyData = null)
{
    $arrData = [];
    if ($isHasEmpty) {
        if ($emptyData == null) {
            $emptyData = ["value" => "", "text" => ""];
        }
        $arrData[] = $emptyData;
    }
    $arrAcademicList = [
        "SD",
        "SMP",
        "SMA",
        "D1, D2, D3, Akademi",
        "S1, Sarjana",
        "S2, Pasca Sarjana",
        "S3, PHD",
        "OTHER"
    ];
    foreach ($arrAcademicList as $value) {
        if ($value == $default) {
            $arrData[] = ["value" => $value, "text" => $value, "selected" => true];
        } else {
            $arrData[] = ["value" => $value, "text" => $value, "selected" => false];
        }
    }
    return $arrData;
}

function getEmployeNameByID($db, $empID)
{
    $rowDb = null;
    if ($db->connect()) {
        $strSQL = "SELECT id, employee_id, employee_name FROM hrd_employee WHERE employee_id = '" . $empID . "'";
        $resDb = $db->execute($strSQL);
        $rowDb = $db->fetchrow($resDb);
    }
    return $rowDb;
}

//
//=================================================== END ================================================
function getDataListRatingCode($default = null, $isHasEmpty = false, $emptyData = null)
{
    $tbl = new cModel("hrd_evaluation_rating", getWords("rating"));
    $arrData = $tbl->generateList(null, "code", null, "code", ["code", "note"], $isHasEmpty, $emptyData);
    if ($default != null || $default != "") {
        while (list($key, $val) = each($arrData)) {
            $temp = &$arrData[$key];
            if ($val['value'] == $default) {
                $temp['selected'] = true;
            } else {
                $temp['selected'] = false;
            }
        }
    }
    return $arrData;
}

/* Function to Get Latest Salary Set */
function getLatestSalarySet($db = null, $idCompany = null, $totalRecord = 1)
{
    $latestSalarySet = null;
    if (!empty($db) && $db->connect()) {
        $strSQL = "SELECT id,start_date,created,created_by,note,id_company,
  		id_salary_set_source FROM hrd_basic_salary_set ";
        if (!empty($idCompany)) {
            $strSQL .= "WHERE id_company = $idCompany ";
        }
        $strSQL .= "ORDER BY id DESC LIMIT " . $totalRecord;
        $resDb = $db->execute($strSQL);
        $latestSalarySet = [];
        while ($rowDb = $db->fetchrow($resDb)) {
            $latestSalarySet[] = $rowDb;
        }
    }
    return $latestSalarySet;
}

/* End Function to Get Latest Salary Set */
/* Function to Get Latest Employee Allowance Based On Latest Salary Set */
function getEmployeeAllowanceBySalarySet($db = null, $strAllowanceCode = null, $idEmployee = null)
{
    $employeeAllowance = null;
    if (!empty($db) && $db->connect() && !empty($strAllowanceCode)) {
        $latestSalarySet = getLatestSalarySet($db);
        $idSalarySet = $latestSalarySet[0]['id'];
        $strSQL = "SELECT id_employee, amount FROM hrd_employee_allowance WHERE
  		id_salary_set = $idSalarySet AND allowance_code = '$strAllowanceCode' ";
        if (!empty($idEmployee)) {
            $strSQL = "AND id_employee = $idEmployee";
        }
        $resDb = $db->execute($strSQL);
        $employeeAllowance = [];
        while ($rowDb = $db->fetchrow($resDb)) {
            $employeeAllowance[$rowDb['id_employee']] = $rowDb['amount'];
        }
    }
    return $employeeAllowance;
}

/* End Function to Get Latest Employee Allowance Based On Latest Salary Set */
function getBaseBpjsByRange($db = null, $basicSalary = null, $companyId = null)
{
    $baseBpjs = 0;
    if (!empty($db) && $db->connect() && !empty($basicSalary) && !empty($companyId)) {
        $strSQL = "SELECT base FROM hrd_base_bpjs_kesehatan WHERE min >= '$basicSalary' AND max < '$basicSalary' AND company_id='$companyId' LIMIT 1";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $baseBpjs = $rowDb['base'];
        }
    }
    return $baseBpjs;
}

function getBaseUmkByBranch($db = null, $strBranchCode = null, $companyId = null)
{
    $baseUmk = 0;
    if (!empty($db) && $db->connect() && !empty($strBranchCode) && !empty($companyId)) {
        $strSQL = "SELECT umk FROM hrd_branch WHERE branch_code = '$strBranchCode' AND company_id='$companyId' LIMIT 1";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $baseUmk = $rowDb['umk'];
        }
    }//var_dump($strSQL);
    return $baseUmk;
}

function getTotalAbsenceByCode(
    $db = null,
    $idEmployee = null,
    $strAbsenceCode = null,
    $dateFrom = null,
    $dateThru = null
) {
    $totalAbsence = 0;
    if (!empty($db) && $db->connect() && !empty($strAbsenceCode) && !empty($idEmployee)) {
        $strSQL = "SELECT count(t1.id) AS total_absence  FROM hrd_absence_detail as t1 LEFT JOIN hrd_absence AS t2 ON t2.id = t1.id_absence WHERE absence_date BETWEEN '$dateFrom' AND '$dateThru' AND status =2 AND t1.id_employee = $idEmployee AND t1.absence_type ='$strAbsenceCode';";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $totalAbsence = $rowDb['total_absence'];
        }
    }
    return $totalAbsence;
}

function syncMRFCandidate($db = null, $limitMRF = 0)
{
    $mrfUpdated = null;
    if (!empty($db) && $db->connect()) {
        $strSQL = "SELECT id, request_number,number_ok FROM hrd_recruitment_need ORDER BY id DESC ";
        if ($limitMRF != 0) {
            $strSQL .= "LIMIT $limitMRF";
        }
        $resDb = $db->execute($strSQL);
        $mrfUpdated = [];
        while ($rowDb = $db->fetchrow($resDb)) {
            $numberOkOld = $rowDb['number_ok'];
            $strSQLTotCandidate = "SELECT COUNT(*) AS tot_candidate FROM hrd_fkr WHERE ";
            $strSQLTotCandidate .= "id_recruitment_need=" . $rowDb['id'] . " AND status >= 2";
            $resDbTot = $db->execute($strSQLTotCandidate);
            $rowDbTot = $db->fetchrow($resDbTot);
            $numberOk = isset($rowDbTot['tot_candidate']) && !empty($rowDbTot['tot_candidate']) ? $rowDbTot['tot_candidate'] : 0;
            if ($numberOk != $numberOkOld) {
                $strUpdateMRF = "UPDATE hrd_recruitment_need SET number_ok='$numberOk' WHERE id=" . $rowDb['id'];
                $db->execute($strUpdateMRF);
                $mrfUpdated[] = $rowDb;
            }
        }
    }
    return $mrfUpdated;
}

function getMasterSalaryByYearGlobal($db = null, $intYear = null, $intCompany = null, $isManagerial = 'FALSE')
{
    $arraySalaryMasterId = [];
    if (!empty($db) && !empty($intYear) && $db->connect()) {
        $strSQL = "SELECT id FROM hrd_salary_master WHERE ";
        $strSQL .= "EXTRACT (YEAR FROM salary_date) = $intYear AND status >= " . REQUEST_STATUS_APPROVED_2 . " ";
        if (!empty($intCompany)) {
            $strSQL .= "AND id_company = $intCompany ";
        }
        $strSQL .= "AND is_overtime_only IS FALSE AND is_managerial IS $isManagerial ";
        $strSQL .= "ORDER BY salary_date";
        $res = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $arraySalaryMasterId[] = $rowDb['id'];
        }
    }
    return $arraySalaryMasterId;
}

function getFKRSalaryList($db = null, $idEmployee = null, $idCandidate = null)
{
    if (!empty($db) && (!empty($idEmployee) || !empty($idCandidate)) && $db->connect()) {
        $strSQL = "SELECT t1.*, t2.code AS allowance_code, t2.name AS allowance_name
      FROM hrd_fkr_detail AS t1 INNER JOIN hrd_allowance_type AS t2 ON t1.id_allowance_type = t2.id
      WHERE t1.id_fkr IN ";
        if (!empty($idCandidate)) {
            $strSQL .= "( SELECT id FROM hrd_fkr WHERE id_candidate = '$idCandidate' )";
        } else if (!empty($idEmployee)) {
            $strSQL .= "( SELECT id FROM hrd_fkr WHERE id_employee = '$idEmployee' )";
        }
        $resDb = $db->execute($strSQL);
        $arrayAllowance = [];
        while ($rowDb = $db->fetchrow($resDb)) {
            $allowanceData = [];
            $fltAmount = ($rowDb['amount_next'] == "" || $rowDb['amount_next'] == 0) ? $rowDb['amount_start'] : $rowDb['amount_next'];
            $strCode = $rowDb['allowance_code'];
            $strName = $rowDb['allowance_name'];
            if ($fltAmount > 0) {
                $allowanceData['code'] = $strCode;
                $allowanceData['name'] = $strName;
                $allowanceData['amount'] = $fltAmount;
            }
            if (count($allowanceData)) {
                $arrayAllowance[] = $allowanceData;
            }
        }
    }
    return $arrayAllowance;
}

function getActiveAllowanceType($db = null)
{
    global $db;
    $activeTemplate = $_SESSION['currentActiveTemplate'];
    $tblAllowanceType = new cModel("hrd_allowance_type");
    if (!empty($activeTemplate)) {
        $arrAllowance = $tblAllowanceType->findAll(
            "active = true AND template_name='$activeTemplate'",
            null,
            "seq",
            null,
            null,
            "id"
        );
    } else {
        $activeTemplate = getSalarySettingActiveTemplate($db);
        if (!empty($activeTemplate)) {
            $arrAllowance = $tblAllowanceType->findAll(
                "active = true AND template_name='$activeTemplate'",
                null,
                "seq",
                null,
                null,
                "id"
            );
        } else {
            $arrAllowance = $tblAllowanceType->findAll("active = true", null, "seq", null, null, "id");
        }
    }
    return $arrAllowance;
}

function getSalarySettingActiveTemplate($db = null)
{
    $strTemplateName = null;
    if (!empty($db) && $db->connect()) {
        $strtempSQL = "SELECT value FROM all_setting where code='template_name'";
        $resDb = $db->execute($strtempSQL);
        $rowDb = $db->fetchrow($resDb);
        $strTemplateName = $rowDb['value'];
    }
    return $strTemplateName;
}

function getSalarySetList($indexKey = 'id', $sortDesc = true, $idCompany = null)
{
    $tblBasicSalarySet = new cModel("hrd_basic_salary_set");
    $strCompany = "";
    if (!empty($idCompany)) {
        $strCompany = "id_company = '$idCompany'";
    }
    $arrBasicSalarySet = $tblBasicSalarySet->findAll(
        $strCompany,
        "id, start_date, note, id_company",
        "start_date DESC",
        null,
        1,
        $indexKey
    );
    foreach ($arrBasicSalarySet AS $keySet => $arrSet) {
        $companyData = getCompanyName($arrSet['id_company']);
        $arrSetSource[$keySet] = $arrSet['start_date'] . " - " . $companyData[0]['company_name'];
    }
    if ($sortDesc) {
        krsort($arrSetSource);
    } else {
        ksort($arrSetSource);
    }
    return $arrSetSource;
}

function getCompanyName($companyID = null)
{
    if (!empty($companyID)) {
        $tblCompany = new cModel("hrd_company");
        $arrCompany = $tblCompany->findAll("id = '$companyID'", "id, company_name", "", null, 1);
    }
    return $arrCompany;
}

function createLoanType($indexKey = 'id', $sortKey = 'id', $strCriteria = "")
{
    $tblLoanType = new cModel("hrd_loan_type", "Loan Type");
    $arrLoanType = $tblLoanType->findAll($strCriteria, "id, type, note", $sortKey, null, 1, $indexKey);
    return $arrLoanType;
}

?>