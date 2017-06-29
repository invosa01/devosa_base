<?php
// Author : Yudi K
// daftar fungsi-fungsi untuk mengenerate element=element form HTML
// umumnya untuk form yang tipenya combo/list dari data dinamis
// beberapa istilah yang dipakai dalam parameter fungsi:
// $db --> objek CDbClass, sebagai objek database, biar lebih efektif
// $varname --> nama element/data yang dibuat
// $default --> default value yang akan ditampilkan, jika ada
// $extra --> baris/option tambahan, jika ada
// $criteria --> kriteria/query pemilihan dari database
// $action -> action tambahan yang menyertai element tersebut, misal onClick, dsb
// $listonly --> apakah hanya menampilkan daftar option atau tidak
// Fungsi untuk generate data question type
function getQuestionTypeList($db, $id, $value)
{
    $strResult = null;
    $strResult .= "<select name=" . $id . ">\n";
    $bolSelect = false;
    $strResult .= "<option value=\"\" >--Choose--</option>\n";
    $strSQL2 = "SELECT * FROM \"hrd_training_question_type\" ORDER BY id";
    $resDb = $db->execute($strSQL2);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $value) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . getQuestionName(
                $db,
                $rowDb['id']
            ) . "</option>\n";
    }
    $strResult .= "</select>";
    return $strResult;
}

// Fungsi untuk mengambil question type name
function getQuestionName($db, $id)
{
    $strSQL = "SELECT * FROM \"hrd_training_question_type\" WHERE id = $id";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strTemp = $rowDb['question_type'];
    }
    return $strTemp;
}

// fungsi untuk mencari daftar training plan
// mengambil kriteria work behavior untuk evaluation
function getTrainingPlanList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_training_plan ";
    $strSQL .= $criteria;
    $strSQL .= " ORDER BY department_code, UPPER(topic), expected_date ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strInfo = $rowDb['topic'] . " [" . $rowDb['department_code'] . "] ";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $strInfo . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getTrainingPlanList
// fungsi untuk mencari daftar training plan
// mengambil kriteria work behavior untuk evaluation
function getRecruitmentPlanList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_recruitment_plan ";
    $strSQL .= $criteria;
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>[" . $rowDb['department_code'] . "] " . $rowDb['position'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getRecruitmentPlanList
// mengambil kriteria work behavior untuk evaluation
function getWorkBehaviorList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_evaluation_criteria ";
    $strSQL .= "WHERE type=2 $criteria ORDER BY criteria ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['criteria'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['criteria'] . "\" $strSelect>" . $rowDb['criteria'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getWorkBehaviorList
// mengambil kriteria work behavior untuk evaluation
function getCompetencyList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_evaluation_criteria ";
    $strSQL .= "WHERE type=1 $criteria ORDER BY \"criteria\" ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['criteria'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['criteria'] . "\" $strSelect>" . $rowDb['criteria'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getCompetencyList
// mengenerate combo list untuk daftar manager, isinny adalah ID
// untuk saat inin, yang termasuk manager adalah yang punya position
function getManagerList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    $strManKode = getSetting("department_head"); // ambil kode dept head
    $strGroupKode = getSetting("group_head"); // ambil kode group head
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT id, employee_id, employee_name FROM hrd_employee ";
    $strSQL .= "WHERE active=1 AND flag=0 ";
    if ($strManKode == "" && $strManKode == "") {
        $strSQL .= "AND 1=2 ";
    } else {
        $strSQL .= "AND (1=2 ";
        if ($strManKode != "") {
            $strSQL .= "OR position_code = '$strManKode' ";
        }
        if ($strGroupKode != "") {
            $strSQL .= "OR position_code = '$strGroupKode' ";
        }
        $strSQL .= ") ";
    }
    //$strSQL .= ($strManKode != "") ? "AND position_code = '$strManKode' " : " AND 1=2 ";
    $strSQL .= "$criteria ORDER BY employee_id ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $rowDb['employee_id'] . " - " . $rowDb['employee_name'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getManagerList
// mengenerate combo list untuk daftar appraiser, isinny adalah ID
function getAppraiserList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT t1.id, t2.employee_id, t2.employee_name FROM hrd_appraiser AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "$criteria ORDER BY t2.employee_id ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $rowDb['employee_id'] . " - " . $rowDb['employee_name'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getAppraiserList
// mengenerate combo list untuk daftar jenis shift
function getMedicalTreatmentCodeList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $i = 0;
    $strSQL = "SELECT * FROM hrd_medical_type $criteria ORDER BY type, code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $i++;
        ($rowDb['code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option id=\"" . $rowDb['type'] . "_$i\" value=\"" . $rowDb['code'] . "\" $strSelect>" . $rowDb['code'];
        if ($rowDb['note'] != "") {
            $strResult .= "   - " . $rowDb['note'];
        }
        $strResult .= " </option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getMedicalTypeList
// mengenerate combo list untuk daftar jenis shift
function getShiftTypeList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_shift_type $criteria ORDER BY code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['code'] . "\" $strSelect>" . $rowDb['code'];
        if ($rowDb['note'] != "") {
            $strResult .= "   - " . $rowDb['note'];
        }
        $strResult .= " </option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getShiftTypeList
// mengenerate combo list untuk daftar jenis shift
function getWarningTypeList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_warning_type $criteria ORDER BY code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['code'] . "\" $strSelect>" . $rowDb['code'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getWarningTypeList
function getRewardTypeList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_reward_type $criteria ORDER BY code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['code'] . "\" $strSelect>" . $rowDb['code'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getRewardTypeList
// mengenerate combo list untuk daftar jenis tunjangan tidak tetap
function getAllowanceTypeList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "";
    $strHidden = "";
    $bolFound = false;
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_allowance_type $criteria ORDER BY name";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        if ($rowDb['name'] == $default) {
            $strSelect = "selected";
            $bolFound = true;
        } else {
            $strSelect = "";
        }
        $strResult .= "<option value=\"" . $rowDb['name'] . "\" $strSelect>" . $rowDb['name'] . "</option>\n";
    }
    if (!$bolFound && $default != "") {
        // tambahkan default
        $strResult .= "<option value=\"$default\" $strSelect>" . $default . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getAllowanceTypeList
// mengenerate combo list untuk daftar jenis potongan tidak tetap
function getDeductionTypeList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "";
    $strHidden = "";
    $bolFound = false;
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_deduction_type $criteria ORDER BY name";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        if ($rowDb['name'] == $default) {
            $strSelect = "selected";
            $bolFound = true;
        } else {
            $strSelect = "";
        }
        $strResult .= "<option value=\"" . $rowDb['name'] . "\" $strSelect>" . $rowDb['name'] . "</option>\n";
    }
    if (!$bolFound && $default != "") {
        // tambahkan default
        $strResult .= "<option value=\"$default\" $strSelect>" . $default . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getDeductionTypeList
// mengenerate combo list untuk daftar jenis shift group
function getShiftGroupList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_shift_group $criteria ORDER BY group_name ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $rowDb['group_name'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getShiftGroupList
// fungsi untuk generate data jenis absen, valuenya = code
function getAbsenceTypeList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_absence_type WHERE 1=1 $criteria ORDER BY code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['code'] . "\" $strSelect>" . $rowDb['code'] . " - " . $rowDb['note'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getAbsenceTypeList
// fungsi untuk generate data jenis absen khusus, valuenya = code, di keterangan ada jumlah hari
function getSpecialAbsenceTypeList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_leave_type  $criteria ORDER BY code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['code'] . "\" $strSelect title='" . $rowDb['duration'] . "'>";
        $strResult .= $rowDb['code'] . " - " . $rowDb['duration'] . " day(s)</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getAbsenceTypeList
// fungsi untuk generate data religion, valuenya = code
function getReligionList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_religion WHERE 1=1 $criteria ORDER BY code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['code'] . "\" $strSelect>" . $rowDb['code'] . " - " . $rowDb['name'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getReligionList
// fungsi untuk generate data level pendidikan, valuenya = code
function getEducationList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_education_level  $criteria ORDER BY code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['code'] . "\" $strSelect>" . $rowDb['code'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getEducationList
// fungsi untuk generate data level pendidikan, valuenya = code
function getTransportList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_transportation  $criteria ORDER BY code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['code'] . "\" $strSelect>" . $rowDb['code'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getTransportList
// fungsi untuk generate data management, valuenya = code
function getManagementList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    global $strDataCompany;
    $strResult = "";
    $criteria .= ($strDataCompany == "") ? "" : "AND management_code LIKE '%" . printCompanyCode(
            $strDataCompany
        ) . "%'";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_management WHERE 1=1 $criteria ORDER BY management_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['management_code'];
        $strName = $rowDb['management_name'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode - $strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getManagementList
// fungsi untuk generate data division, valuenya = code
function getDivisionList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    global $strDataCompany;
    $strResult = "";
    $criteria .= ($strDataCompany == "") ? "" : "AND management_code LIKE '%" . printCompanyCode(
            $strDataCompany
        ) . "%'";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_division WHERE 1=1 $criteria ORDER BY division_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['division_code'];
        $strName = $rowDb['division_name'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode - $strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getDivisionList
// fungsi untuk generate data company, valuenya = id
// fungsi untuk generate data department, valuenya = code
function getDepartmentListIdris(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select name=\"$varname\" id=\"$varname\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_department  $criteria ORDER BY department_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['department_code'];
        $strName = $rowDb['department_name'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>" . $strCode . " - " . $strName . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getDepartmentList
// fungsi untuk generate data department, valuenya = code
function getDepartmentList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    global $strDataCompany;
    $strResult = "";
    $criteria .= ($strDataCompany == "") ? "" : "AND management_code LIKE '%" . printCompanyCode(
            $strDataCompany
        ) . "%'";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
        $strResult .= "<option value=''></option>";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_department WHERE 1=1 $criteria ORDER BY department_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['department_code'];
        $strName = $rowDb['department_name'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode  - $strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getDepartmentList
function getCompanyList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_company $criteria ORDER BY id ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['company_code'];
        $strName = $rowDb['company_name'];
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $strCode . " - " . $strName . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getDepartmentList
// fungsi untuk generate data sub department, valuenya = code
function getSubDepartmentList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    global $strDataCompany;
    $strResult = "";
    $criteria .= ($strDataCompany == "") ? "" : "AND management_code LIKE '%" . printCompanyCode(
            $strDataCompany
        ) . "%'";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_sub_department WHERE 1=1 $criteria ORDER BY department_code, sub_department_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['sub_department_code'];
        $strName = $rowDb['sub_department_name'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode  - $strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getSubDepartmentList
// fungsi untuk generate data section, valuenya = code
function getSectionList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    global $strDataCompany;
    $strResult = "";
    $criteria .= ($strDataCompany == "") ? "" : "AND management_code LIKE '%" . printCompanyCode(
            $strDataCompany
        ) . "%'";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_section WHERE 1=1 $criteria ORDER BY sub_department_code, section_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['section_code'];
        $strName = $rowDb['section_name'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode  - $strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getSectionList
// fungsi untuk generate data subsection, valuenya = code
function getSubSectionList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    global $strDataCompany;
    $strResult = "";
    $criteria .= ($strDataCompany == "") ? "" : "AND management_code LIKE '%" . printCompanyCode(
            $strDataCompany
        ) . "%'";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_sub_section WHERE 1=1 $criteria ORDER BY section_code, sub_section_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['sub_section_code'];
        $strName = $rowDb['sub_section_name'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode  - $strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getSubSectionList
// fungsi untuk generate data group, valuenya = code
function getGroupList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_group  $criteria ORDER BY group_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['group_code'];
        $strName = $rowDb['group_name'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        ($strName == "") ? $strName = "" : $strName = " - " . $strName;
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode  - $strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getGroupList
// fungsi untuk generate data fungsional, valuenya = code
function getFunctionalPositionList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_functional $criteria ORDER BY functional_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['functional_code'];
        $strName = $rowDb['functional_name'];
        $strSelect = (trim($strCode) == trim($default)) ? "selected" : "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode - $strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getFunctionalPPositionList
// fungsi untuk generate data position, valuenya = code
function getPositionList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select name=\"$varname\" id=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_position $criteria ORDER BY position_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['position_code'];
        $strName = $rowDb['position_name'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode - $strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getPositionList
function getCategoryList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false,
    $strDomain = "",
    $strType = ""
) {
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    if (EMPTY($strDomain) && EMPTY($strType)) {
        $strSQL = "SELECT competency FROM hrd_training_type GROUP BY competency ORDER BY competency ";
    } else if (EMPTY($strType)) {
        $strSQL = "SELECT competency FROM hrd_training_type WHERE training_type = '$strType' GROUP BY competency ORDER BY competency ";
    } else if (EMPTY($strDomain)) {
        $strSQL = "SELECT competency FROM hrd_training_type WHERE competency = '$strDomain' GROUP BY competency ORDER BY competency ";
    } else {
        $strSQL = "SELECT competency FROM hrd_training_type WHERE domain = '$strDomain' AND training_type = $strType GROUP BY competency ORDER BY competency ";
    }
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCompetency = $rowDb['competency'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCompetency\" $strSelect>$strCompetency </option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}

function getDomainArray($db)
{
    $strSQL = "SELECT domain FROM hrd_training_type GROUP BY domain ORDER BY domain ";
    $resDb = $db->execute($strSQL);
    $length;
    $strDomain = [];
    for ($i = 0; $rowDb = $db->fetchrow($resDb); $i++) {
        $strDomain[$i] = $rowDb['domain'];
        $length = $i;
    }
    return $strDomain;
}

function getDomainTypeArray($db)
{
    $strSQL = "SELECT domain FROM hrd_training_type GROUP BY domain ORDER BY domain ";
    $resDb = $db->execute($strSQL);
    $length;
    $strDomain = [];
    $strDomainType = [];
    for ($i = 0; $rowDb = $db->fetchrow($resDb); $i++) {
        $strDomain[$i] = $rowDb['domain'];
        $length = $i;
    }
    for ($i = 0; $i <= $length; $i++) {
        $strSQL = "SELECT training_type FROM hrd_training_type WHERE domain = '$strDomain[$i]' ORDER BY training_type";
        $resDb = $db->execute($strSQL);
        for ($j = 0; $rowDb = $db->fetchrow($resDb); $j++) {
            $strDomainType[$i][$j] = $rowDb['training_type'];
        }
    }
    return $strDomainType;
}

function getDomainCategoryArray($db)
{
    $strSQL = "SELECT competency FROM hrd_training_type GROUP BY domain, competency ORDER BY domain ";
    $resDb = $db->execute($strSQL);
    $length;
    $strDomainCategory = [];
    for ($i = 0; $rowDb = $db->fetchrow($resDb); $i++) {
        $strDomainCategory[$i] = $rowDb['competency'];
        $length = $i;
    }
    return $strDomainCategory;
}

function getCategoryArray($db)
{
    $strSQL = "SELECT competency FROM hrd_training_type GROUP BY competency ORDER BY competency ";
    $resDb = $db->execute($strSQL);
    $length;
    $strCategory = [];
    for ($i = 0; $rowDb = $db->fetchrow($resDb); $i++) {
        $strCategory[$i] = $rowDb['competency'];
        $length = $i;
    }
    return $strCategory;
}

function getCategoryTypeArray($db)
{
    $strSQL = "SELECT competency FROM hrd_training_type GROUP BY competency ORDER BY competency ";
    $resDb = $db->execute($strSQL);
    $length;
    $strCategory = [];
    $strCategoryType = [];
    for ($i = 0; $rowDb = $db->fetchrow($resDb); $i++) {
        $strCategory[$i] = $rowDb['competency'];
        $length = $i;
    }
    for ($i = 0; $i <= $length; $i++) {
        $strSQL = "SELECT training_type FROM hrd_training_type WHERE competency = '$strCategory[$i]' ORDER BY training_type";
        $resDb = $db->execute($strSQL);
        for ($j = 0; $rowDb = $db->fetchrow($resDb); $j++) {
            $strCategoryType[$i][$j] = $rowDb['training_type'];
        }
    }
    return $strCategoryType;
}

function getCategoryDomainArray($db)
{
    $strSQL = "SELECT competency FROM hrd_training_type GROUP BY competency ORDER BY competency ";
    $resDb = $db->execute($strSQL);
    $length;
    $strCategory = [];
    $strCategoryDomain = [];
    for ($i = 0; $rowDb = $db->fetchrow($resDb); $i++) {
        $strCategory[$i] = $rowDb['competency'];
        $length = $i;
    }
    for ($i = 0; $i <= $length; $i++) {
        $strSQL = "SELECT domain FROM hrd_training_type WHERE competency = '$strCategory[$i]' GROUP BY domain ORDER BY domain ";
        $resDb = $db->execute($strSQL);
        for ($j = 0; $rowDb = $db->fetchrow($resDb); $j++) {
            $strCategoryDomain[$i][$j] = $rowDb['domain'];
        }
    }
    return $strCategoryDomain;
}

function getCategoryDomainTypeArray($db)
{
    $strSQL = "SELECT competency FROM hrd_training_type GROUP BY competency ORDER BY competency ";
    $resDb = $db->execute($strSQL);
    $length;
    $strCategory = [];
    $strCategoryDomain = [];
    $strCategoryDomainType = [];
    for ($i = 0; $rowDb = $db->fetchrow($resDb); $i++) {
        $strCategory[$i] = $rowDb['competency'];
        $length = $i;
    }
    for ($i = 0; $i <= $length; $i++) {
        $strSQL = "SELECT domain FROM hrd_training_type WHERE competency = '$strCategory[$i]' GROUP BY domain ORDER BY domain ";
        $resDb = $db->execute($strSQL);
        for ($j = 0; $rowDb = $db->fetchrow($resDb); $j++) {
            $strCategoryDomain[$i][$j] = $rowDb['domain'];
        }
    }
    for ($i = 0; $i <= $length; $i++) {
        for ($j = 0; $j <= count($strCategoryDomain[$i]); $j++) {
            $cat = $strCategory[$i];
            $dom = $strCategoryDomain[$i][$j];
            $strSQL = "SELECT training_type FROM hrd_training_type WHERE competency = '$cat' AND domain = '$dom' ORDER BY training_type ";
            $resDb = $db->execute($strSQL);
            for ($k = 0; $rowDb = $db->fetchrow($resDb); $k++) {
                $strCategoryDomainType[$i][$j][$k] = $rowDb['training_type'];
            }
        }
    }
    return $strCategoryDomainType;
}

function getTypeArray($db)
{
    $strSQL = "SELECT training_type FROM hrd_training_type GROUP BY training_type ORDER BY training_type ";
    $resDb = $db->execute($strSQL);
    $length;
    $strType = [];
    for ($i = 0; $rowDb = $db->fetchrow($resDb); $i++) {
        $strType[$i] = $rowDb['training_type'];
        $length = $i;
    }
    return $strType;
}

function getTypeDomainArray($db)
{
    $strSQL = "SELECT training_type, domain FROM hrd_training_type GROUP BY training_type, domain ORDER BY training_type ";
    $resDb = $db->execute($strSQL);
    $length;
    $strTypeDomain = [];
    for ($i = 0; $rowDb = $db->fetchrow($resDb); $i++) {
        $strTypeDomain[$i] = $rowDb['domain'];
        $length = $i;
    }
    return $strTypeDomain;
}

function getTypeCategoryArray($db)
{
    $strSQL = "SELECT training_type, competency FROM hrd_training_type GROUP BY training_type, competency ORDER BY training_type ";
    $resDb = $db->execute($strSQL);
    $length;
    $strTypeCategory = [];
    for ($i = 0; $rowDb = $db->fetchrow($resDb); $i++) {
        $strTypeCategory[$i] = $rowDb['competency'];
        $length = $i;
    }
    return $strTypeCategory;
}

// fungsi untuk generate data position, valuenya = code
function getRecruitmentPositionList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $bolSelect = false;
    $strSQL = "SELECT * FROM hrd_recruitment_need  ";
    $strSQL .= "WHERE status <> " . REQUEST_STATUS_DENIED . " ";
    $strSQL .= "$criteria ORDER BY position ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['position'];
        $strInfo = $strCode . " [" . $rowDb['department_code'] . "] ";
        if ($strCode == $default) {
            $strSelect = "selected";
            $bolSelect = true;
        } else {
            $strSelect = "";
        }
        $strResult .= "<option value=\"$strCode\" $strSelect>$strInfo</option>\n";
    }
    if ($default != "" && !$bolSelect) {
        $strResult .= "<option value=\"$default\" selected>$default</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getRecruitmentPositionList
// fungsi untuk generate data status keluarga, valuenya = code
function getFamilyStatusList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_family_status  $criteria ORDER BY family_status_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['family_status_code'];
        $strNote = $rowDb['note'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode - $strNote</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getFamilyStatus
// fungsi untuk generate data status keluarga, valuenya = code
function getLivingCostList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_minimum_living_cost  $criteria ORDER BY code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['code'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        if ($rowDb['note'] != "") {
            $strResult .= "   - " . $rowDb['note'];
        }
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode ";
        if ($rowDb['note'] != "") {
            $strResult .= "   - " . $rowDb['note'];
        }
        $strResult .= " </option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getLivingCost
// fungsi untuk generate data salary grade, valuenya = code
function getSalaryGradeList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_salary_grade  $criteria ORDER BY grade_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['grade_code'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode ";
        if ($rowDb['note'] != "") {
            $strResult .= "   - " . $rowDb['note'];
        }
        $strResult .= " </option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getSalaryGradeList
// fungsi untuk generate data kategory berita, valuenya = code
function getNewsCategoryList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM all_news_category  $criteria ORDER BY category ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['category'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getNewsCategoryList
// fungsi untuk generate data kategory artikel, valuenya = code
function getArticleCategoryList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM all_article_category  $criteria ORDER BY category ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['category'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getArticleCategoryList
// fungsi untuk generate data jenis perawatan
function getMedicalTreatmentTypeList($varname, $bolIncludeAll = false, $default = 0, $extra = "", $action = "")
{
    global $ARRAY_MEDICAL_TREATMENT_GROUP;
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_MEDICAL_TREATMENT_GROUP);
    for ($i = 0; $i < $intTotal; $i++) {
        if ($i == 0 && !$bolIncludeAll) {
            continue;
        }
        ($default == $i && $default != "") ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . getWords($ARRAY_MEDICAL_TREATMENT_GROUP[$i]) . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getTreatmentTypeList
// fungsi untuk generate data jenis hasil rekrutment
function getRecruitmentResultList($varname, $default = 0, $extra = "", $action = "")
{
    global $ARRAY_RECRUITMENT_RESULT;
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_RECRUITMENT_RESULT);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i && $default != "") ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . getWords($ARRAY_RECRUITMENT_RESULT[$i]) . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//
// fungsi untuk generate data jenis Status recruitment need
function getRecruitmentNeedStatusList($varname, $default = 0, $extra = "", $action = "")
{
    global $ARRAY_REQUEST_STATUS;
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_REQUEST_STATUS);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i && $default != "") ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . $words[$ARRAY_REQUEST_STATUS[$i]] . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getRecruitmentNeedStatusList
// fungsi untuk generate data jenis Status overtime application
function getOvertimeApplicationStatusList($varname, $default = 0, $extra = "", $action = "")
{
    global $ARRAY_REQUEST_STATUS;
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_REQUEST_STATUS);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i && $default != "") ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . $words[$ARRAY_REQUEST_STATUS[$i]] . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getOvertimeApplicationStatusList
// fungsi untuk generate data jenis pinjaman
function getLoanTypeList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_loan_type  $criteria ORDER BY type ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['type'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['type'] . "\" $strSelect>" . $rowDb['type'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getLoanTypeList
function getLoanTypeList2($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT id, type FROM hrd_loan_type  $criteria ORDER BY weight ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $rowDb['type'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getLoanTypeList
// fungsi untuk generate data tujuan pinjaman
function getLoanPurposeList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_loan_purpose  $criteria ORDER BY purpose ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['purpose'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['purpose'] . "\" $strSelect>" . $rowDb['purpose'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}//
// fungsi untuk generate data jenis product untuk pinjaman
function getLoanProductTypeList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_loan_product  $criteria ORDER BY product_type ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['productType'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['product_type'] . "\" $strSelect>" . $rowDb['product_type'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getLoanProductTypeList
// fungsi untuk generate data gender 0=female, 1=male
function getGenderList($varname, $default = "", $extra = "", $action = "")
{
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $strSelect1 = ($default == "0") ? "selected" : "";
    $strSelect2 = ($default == "1") ? "selected" : "";
    $strResult .= "<option value=\"\" selected> </option>\n";
    $strResult .= "<option value=\"0\" $strSelect1>" . $words['female'] . "</option>\n";
    $strResult .= "<option value=\"1\" $strSelect2>" . $words['male'] . "</option>\n";
    $strResult .= "</select>\n";
    return $strResult;
}//getGenderList
// fungsi untuk generate data gender 0=single, 1=married
function getMaritalStatus($varname, $default = "", $extra = "", $action = "")
{
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $strSelect1 = ($default == "0") ? "selected" : "";
    $strSelect2 = ($default == "1") ? "selected" : "";
    $strResult .= "<option value=\"\" selected> </option>\n";
    $strResult .= "<option value=\"0\" $strSelect1>" . $words['single'] . "</option>\n";
    $strResult .= "<option value=\"1\" $strSelect2>" . $words['married'] . "</option>\n";
    $strResult .= "</select>\n";
    return $strResult;
}//getMaritalStatus
// fungsi untuk generate data jenis cuti
function getLeaveTypeList($varname, $default = 0, $extra = "", $action = "")
{
    global $ARRAY_LEAVE_TYPE;
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_LEAVE_TYPE);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . $words[$ARRAY_LEAVE_TYPE[$i]] . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getLeaveTypeList
// fungsi untuk generate data employee status
function getEmployeeStatusList($varname, $default = "", $extra = "", $action = "", $extra2 = "")
{
    global $ARRAY_EMPLOYEE_STATUS;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_EMPLOYEE_STATUS);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i && $default != "") ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . getWords($ARRAY_EMPLOYEE_STATUS[$i]) . "</option>\n";
    }
    $strResult .= $extra2;
    $strResult .= "</select>\n";
    return $strResult;
}//getEmployeeStatusList
// fungsi untuk generate data status keikutsertaan
function getTrainingParticipationList($varname, $default = "", $extra = "", $action = "")
{
    global $ARRAY_TRAINING_PARTICIPATION;
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_TRAINING_PARTICIPATION);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i && $default != "") ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . $words[$ARRAY_CANDIDATE_STATUS[$i]] . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getTrainingPartictipationList
// fungsi untuk membuat combo dari list berupa array, yang nilainya numeric
// teks yang tampil diabil dari words, sesuai isi array
function getComboFromArray($array, $varname, $default = "", $extra = "", $action = "", $indexed = true)
{
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($array);
    foreach ($array as $key => $value) {
        $strCode = ($indexed) ? $key : $value;
        ($default == $strCode && $default != "") ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$strCode $strSelect>" . $value . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
} // getComboFromArray
// fungsi untuk generate data candidate status
function getCandidateStatusList($varname, $default = "", $extra = "", $action = "")
{
    global $ARRAY_CANDIDATE_STATUS;
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_CANDIDATE_STATUS);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i && $default != "") ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . $words[$ARRAY_CANDIDATE_STATUS[$i]] . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getCandidateStatusList
// fungsi untuk generate data keaktivan status 0=non aktif, 1=aktif
function getEmployeeActiveList($varname, $default = "", $extra = "", $action = "")
{
    global $words;
    $strResult = "";
    $strResult .= "<select name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    if ($default == '0') {
        $strResult .= "<option value=0 selected>" . $words['not active'] . "</option>\n";
        $strResult .= "<option value=1>" . $words['active'] . "</option>\n";
    } else if ($default == '1') {
        $strResult .= "<option value=0>" . $words['not active'] . "</option>\n";
        $strResult .= "<option value=1 selected>" . $words['active'] . "</option>\n";
    } else {
        $strResult .= "<option value=0>" . $words['not active'] . "</option>\n";
        $strResult .= "<option value=1>" . $words['active'] . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getGenderList
function getMajorList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_major  $criteria ORDER BY code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['code'] . "\" $strSelect>" . $rowDb['code'] . " - " . $rowDb['name'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}

// fungsi untuk generate data hubungan kekeluargaan
function getFamilyRelationList($varname, $default = "", $extra = "", $action = "")
{
    global $ARRAY_FAMILY_RELATION;
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_FAMILY_RELATION);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i && $default != "") ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . $words[$ARRAY_FAMILY_RELATION[$i]] . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getFamilyRelationList
// fungsi untuk generate data hubungan kekeluargaan
function getPaymentMethodList($varname, $default = 0, $extra = "", $action = "")
{
    global $ARRAY_PAYMENT_METHOD;
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_PAYMENT_METHOD);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . getWords($ARRAY_PAYMENT_METHOD[$i]) . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getPaymentMethodList
// fungsi untuk generate data hubungan kekeluargaan
function getMaritalList($varname, $default = 0, $extra = "", $action = "")
{
    global $ARR_DATA_MARITAL_STATUS;
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARR_DATA_MARITAL_STATUS);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . getWords($ARR_DATA_MARITAL_STATUS[$i]) . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getMaritalList
// fungsi untuk generate data hubungan kekeluargaan
function getAccessRightList($varname, $default = 0, $extra = "", $action = "")
{
    global $ARRAY_ACCESS_RIGHT;
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_ACCESS_RIGHT);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . $words[$ARRAY_ACCESS_RIGHT[$i]] . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getFamilyRelationList
function getFamilyList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "")
{
    global $ARRAY_FAMILY_RELATION;
    if ($criteria == "") {
        $criteria = " AND 1=0 ";
    }
    $strResult = "";
    $strResult .= "<select name=\"$varname\"style=\"width:150\" $action>\n";
    $strResult .= $extra;
    $strSQL = "SELECT *, EXTRACT(year FROM AGE(birthday)) AS umur FROM \"hrd_employee_family\" ";
    $strSQL .= "WHERE relation IN (2,3,4) $criteria ORDER BY relation, birthday DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($default == ($rowDb['name'] . "|" . $rowDb['relation']) && $default != "") ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['name'] . "|" . $rowDb['relation'] . "\" $strSelect>" . $rowDb['name'] . " - " . getWords(
                $ARRAY_FAMILY_RELATION[$rowDb['relation']]
            );
        $strResult .= "</option>\n";
    }
    $strResult .= "</select>\n";
    //echo $criteria;
    //echo  $strResult;
    return $strResult;
}//getFamilyList
// fungsi untuk generate data hubungan kekeluargaan
function getHolidayTypeList($varname, $default = 0, $extra = "", $action = "")
{
    global $ARRAY_HOLIDAY_TYPE;
    global $words;
    $strResult = "";
    $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    $strResult .= $extra;
    $intTotal = count($ARRAY_HOLIDAY_TYPE);
    for ($i = 0; $i < $intTotal; $i++) {
        ($default == $i) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=$i $strSelect>" . $words[$ARRAY_HOLIDAY_TYPE[$i]] . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}//getHolidayType
// fungsi untuk generate tanggal (tanggal saja)
function getDayList($varname, $default = "", $extra = "", $action = "")
{
    $strResult = "";
    $strResult .= "<select name=$varname class=\"form-control select2\" $action style=\"width: 70px;float: left;margin-right: 10px;\"> \n";
    $strResult .= $extra;
    for ($i = 1; $i <= 31; $i++) {
        ($default == $i) ? $strSelected = "selected" : $strSelected = "";
        $strResult .= "<option value=$i $strSelected>$i</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}// getDayList
// fungsi untuk generate bulan
function getMonthList($varname, $default = "", $extra = "", $action = "")
{
    $strResult = "";
    $strResult .= "<select name=$varname id=$varname class=\"form-control select2\" $action style=\"width: 80px;float: left;margin-right: 10px;\"> \n";//
    $strResult .= $extra;
    for ($i = 1; $i <= 12; $i++) {
        ($default == $i) ? $strSelected = "selected" : $strSelected = "";
        $strResult .= "<option id=$i value=$i $strSelected>" . getBulanSingkat($i) . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}// getMonthList
// fungsi untuk generate tanggal (tanggal saja)
function getYearList($varname, $default = "", $extra = "", $action = "")
{
    $strResult = "";
    $intBefore = 50;
    $intAfter = 50;
    $dtNow = getdate();
    $intYear = $dtNow['year'];
    $bolSelect = false;
    $strResult .= "<select name=$varname class=\"form-control select2\" $action style=\"width: 95px;float: left;\"> \n";
    $strResult .= $extra;
    for ($i = ($intYear - $intBefore); $i < $intYear; $i++) {
        if ($default == $i) {
            $strSelected = "selected";
            $bolSelect = true;
        } else {
            $strSelected = "";
        }
        $strResult .= "<option value=$i $strSelected>$i</option>\n";
    }
    for ($i = $intYear; $i <= ($intYear + $intAfter); $i++) {
        if ($default == $i) {
            $strSelected = "selected";
            $bolSelect = true;
        } else {
            $strSelected = "";
        }
        $strResult .= "<option value=$i $strSelected>$i</option>\n";
    }
    if (!$bolSelect) {
        $strResult .= "<option value=$default selected>$default</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}// getYearList
// fungsi untuk generate pilihan daftar point untuk appraisal
function getAppraisalValueList($varname, $default = "", $extra = "", $action = "")
{
    $strResult = "";
    $intStart = 0;
    $intFinish = 5;
    $strResult .= "<select name=$varname $action class=\"form-control select2\"> \n";
    $strResult .= $extra;
    for ($i = $intStart; $i <= $intFinish; $i++) {
        ($default == $i && $default != "") ? $strSelected = "selected" : $strSelected = "";
        $strResult .= "<option value=$i $strSelected>$i</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}// getAppraisalValueList
// mengenerate combo list untuk daftar jenis ot reason
function getReasonTypeList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_overtime_reason $criteria ORDER BY code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $rowDb['code'] . " - " . $rowDb['reason'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getShiftTypeList
// fungsi untuk mencari daftar start date untuk salary basic range history
function getBasicSalaryRangeStartDate($db, $varname, $default = "", $extra = "", $criteria = "", $action = "")
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT DISTINCT start_date FROM hrd_basic_salary_range ";
    $strSQL .= "ORDER BY start_date DESC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['start_date'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['start_date'] . "\" $strSelect>" . $rowDb['start_date'] . "</option>\n";
    }
    $strResult .= "</select>\n";
    return $strResult;
}// getBasicSalaryRangeStartDate
function getCharityTypeList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_charity_type $criteria ORDER BY \"name\" ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['code'] . "\" $strSelect>" . $rowDb['name'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getCharityTypeList
// mengenerate combo list untuk daftar jenis ot reason
function getTrainingTypeList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    // $strSQL  = "SELECT t1.id, t1.training_type, t2.training_category FROM hrd_training_type AS t1 LEFT JOIN hrd_training_category AS t2 ON t1.id_category = t2.id ORDER BY t1.id_category";
    $strSQL = "SELECT * FROM hrd_training_type $criteria ORDER BY \"training_type\" ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $rowDb['training_category'] . " - " . $rowDb['training_type'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getShiftTypeList
// fungsi untuk generate data branch, valuenya = code
function getBranchList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_branch $criteria ORDER BY branch_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['branch_code'];
        $strName = $rowDb['branch_name'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode - $strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getBranchList
// fungsi untuk generate data position, valuenya = code
function getBankList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
        // $strResult .= "<option value=\"BCA TXT\">BCA TXT</option>";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_bank $criteria ORDER BY bank_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['bank_code'];
        $strName = $rowDb['bank_name'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode - $strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getBankList
// from datanet : copy by idris
// mengenerate combo list untuk daftar topik training
function getTrainingTopicList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select name=\"$varname\" id=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    // flag = nomor urut
    $strSQL = "SELECT * FROM hrd_training_plan $criteria ORDER BY topic ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $rowDb['topic'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getTrainingTopicList
//by irfan
// mengenerate combo list untuk institution  training
function getTrainingInstitutionList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select name=\"$varname\" id=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    // flag = nomor urut
    $strSQL = "SELECT * FROM hrd_training_vendor $criteria ORDER BY name_vendor ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $rowDb['name_vendor'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}//getTrainingInstitutionList
// mengenerate combo list untuk instructor  training
function getTrainingInstructorList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select name=\"$varname\" id=\"$varname\" class=\"form-control select2\" $action>\n";
        $strResult .= "<option value=''></option>";
    }
    $strResult .= $extra;
    // flag = nomor urut
    $strSQL = "SELECT * FROM hrd_training_instructor $criteria ORDER BY name_instructor";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $rowDb['name_instructor'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}

function getSalaryDateList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_salary_master
	$criteria ORDER BY id DESC";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strName = $rowDb['salary_date'];
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $strName . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getDepartmentList
//coba membuat city
function getCityList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_city $criteria ORDER BY id ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['city_code'];
        $strName = $rowDb['city_name'];
        ($rowDb['city_name'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['city_name'] . "\" $strSelect>" . $strCode . " - " . $strName . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}

// by irfan
// fungsi untuk generate data instructor training, valuenya = code
function getInstructorList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select name=\"$varname\" id=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    // flag = nomor urut
    $strSQL = "SELECT * FROM hrd_training_vendor $criteria ORDER BY name_vendor ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $rowDb['name_vendor'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}

// fungsi untuk generate data competency training, valuenya = code
function getCompetencyTrainingList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    global $strDataCompany;
    $strResult = "";
    //$criteria .= ($strDataCompany == "") ? "" : "AND management_code LIKE '%". printCompanyCode($strDataCompany)."%'";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
        $strResult .= "<option value=''></option>";
    }
    $strResult .= $extra;
    $strSQL = "SELECT competency FROM hrd_training_type WHERE 1=1 $criteria GROUP BY competency ORDER by competency asc";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['competency'];
        $strName = $rowDb['competency'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getCompetencyTrainingList
// fungsi untuk generate data domain training, valuenya = code
function getDomainTrainingList(
    $db,
    $varname,
    $default = "",
    $extra = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    global $strDataCompany;
    $strResult = "";
    //$criteria .= ($strDataCompany == "") ? "" : "AND management_code LIKE '%". printCompanyCode($strDataCompany)."%'";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
        $strResult .= "<option value=''></option>";
    }
    $strResult .= $extra;
    $strSQL = "SELECT domain FROM hrd_training_type WHERE 1=1 $criteria GROUP BY domain ORDER by domain asc";
    $resDb = $db->execute($strSQL);
    // $strCode = $rowDb['domain'];
    // $strName = $rowDb['domain'];
    $rowDb = $db->fetchrow($resDb);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['domain'];
        $strName = $rowDb['domain'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getDomainTrainingList
// fungsi untuk generate data type training, valuenya = code
function getTypeTrainingList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    global $strDataCompany;
    $strResult = "";
    //$criteria .= ($strDataCompany == "") ? "" : "AND management_code LIKE '%". printCompanyCode($strDataCompany)."%'";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
        $strResult .= "<option value=''></option>";
    }
    $strResult .= $extra;
    $strSQL = "SELECT training_type FROM hrd_training_type WHERE 1=1 $criteria GROUP BY training_type ORDER by training_type asc";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['training_type'];
        $strName = $rowDb['training_type'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strName</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getTypeTrainingList
function getDataTrainingPlan($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if ($default != "") {
        $strSQL = "SELECT * FROM hrd_training_plan ";
        $strSQL .= "WHERE id = '$default' ";
        $resDb = $db->execute($strSQL);
        $rowDb = $db->fetchrow($resDb);
        $strResult = "<input class=\"form-control\" type=text name=\"$varname\" $action value='" . $rowDb['topic'] . " [" . $rowDb['department_code'] . "] " . "'>";
        // $strResult = $rowDb['topic']. " [" .$rowDb['department_code']."] ";
    }
    return $strResult;
}

// fungsi untuk generate data department, valuenya = code
function getDataDepartment($db, $varname, $default = "", $type = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "<input type=text $action>";
    if ($default != "") {
        $strSQL = "SELECT * FROM hrd_department WHERE department_code='$default' $criteria";
        $resDb = $db->execute($strSQL);
        $rowDb = $db->fetchrow($resDb);
        $strResult = "<input class=\"form-control\" type=$type name=\"$varname\" $action value='" . $rowDb['department_code'] . " [" . $rowDb['department_name'] . "] " . "'>";
    }
    return $strResult;
}

// fungsi untuk generate data division, valuenya = code
function getDataDivision($db, $varname, $default = "", $type = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "<input type=text $action>";
    if ($default != "") {
        $strSQL = "SELECT * FROM hrd_division WHERE division_code='$default' $criteria ";
        $resDb = $db->execute($strSQL);
        $rowDb = $db->fetchrow($resDb);
        $strResult = "<input class=\"form-control\" type=$type name=\"$varname\" $action value='" . $rowDb['division_code'] . " [" . $rowDb['division_name'] . "] " . "'>";
    }
    return $strResult;
}

function getDataSection($db, $varname, $default = "", $type = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "<input type=text $action>";
    if ($default != "") {
        $strSQL = "SELECT * FROM hrd_section WHERE section_code='$default' $criteria ";
        $resDb = $db->execute($strSQL);
        $rowDb = $db->fetchrow($resDb);
        $strResult = "<input class=\"form-control\" type=$type name=\"$varname\" $action value='" . $rowDb['section_code'] . " [" . $rowDb['section_name'] . "] " . "'>";
    }
    return $strResult;
}

function getDataSubSection($db, $varname, $default = "", $type = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "<input type=text $action>";
    if ($default != "") {
        $strSQL = "SELECT * FROM hrd_sub_section WHERE sub_section_code='$default' $criteria ";
        $resDb = $db->execute($strSQL);
        $rowDb = $db->fetchrow($resDb);
        $strResult = "<input class=\"form-control\" type=$type name=\"$varname\" $action value='" . $rowDb['sub_section_code'] . " [" . $rowDb['sub_section_name'] . "] " . "'>";
    }
    return $strResult;
}

function getDataTrainingInstitution(
    $db,
    $varname,
    $default = "",
    $type = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "<input type=text $action>";
    if ($default != "") {
        $strSQL = "SELECT * FROM hrd_training_vendor WHERE id='$default' $criteria ";
        $resDb = $db->execute($strSQL);
        $rowDb = $db->fetchrow($resDb);
        $strResult = "<input class=\"form-control\" type=$type name=\"$varname\" $action value='" . $rowDb['name_vendor'] . "'>";
    }
    return $strResult;
}

function getDataTrainingInstructor(
    $db,
    $varname,
    $default = "",
    $type = "",
    $criteria = "",
    $action = "",
    $listonly = false
) {
    $strResult = "<input type=text $action>";
    if ($default != "") {
        $strSQL = "SELECT * FROM hrd_training_instructor WHERE id='$default' $criteria ";
        $resDb = $db->execute($strSQL);
        $rowDb = $db->fetchrow($resDb);
        $strResult = "<input class=\"form-control\" type=$type name=\"$varname\" $action value='" . $rowDb['name_instructor'] . "'>";
    }
    return $strResult;
}

function getDataCostCenter($db, $varname, $default = "", $type = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    $strHidden = "";
    if (!$listonly) {
        $strResult .= "<select class=\"form-control select2\" name=\"$varname\" id=\"$varname\" $action>\n";
    }
    $strResult .= $extra;
    // flag = nomor urut
    $strSQL = "SELECT * FROM hrd_cost_center $criteria ";
    $resDb = $db->execute($strSQL);
    $strResult .= "<option value=\"\" $strSelect></option>\n";
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['id'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['id'] . "\" $strSelect>" . $rowDb['name'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}

// fungsi untuk generate data jenis absen, valuenya = code
function getRatingValueList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_evaluation_rating WHERE 1=1 $criteria ORDER BY code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['code'] . "\" $strSelect>" . $rowDb['code'] . " - " . $rowDb['name'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getAbsenceTypeList
//--------------------------------------------------------------------------------------------------------------
// 2016-05-24 | Sanusi : fungsi untuk generate overime terapis code
function getOtTerapisList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_overtime_terapis  $criteria ORDER BY ot_type ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        ($rowDb['ot_code'] == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"" . $rowDb['ot_code'] . "\" $strSelect>" . $rowDb['ot_type'] . "</option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}// getLoanTypeList

function getDateSettingList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM date_setting  $criteria ORDER BY id ASC ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['id'];
        $strFormat = $rowDb['date_format'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strFormat ";
        if ($rowDb['note'] != "") {
            $strResult .= "   - " . $rowDb['note'];
        }
        $strResult .= " </option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}

/**
 * Function to create a list of leave level quota.
 *
 * @param            $db
 * @param            $varname
 * @param string     $default
 * @param string     $extra
 * @param string     $criteria
 * @param string     $action
 * @param bool|false $listonly
 *
 * @return string
 */
function getLeaveLevelList($db, $varname, $default = "", $extra = "", $criteria = "", $action = "", $listonly = false)
{
    $strResult = "";
    if (!$listonly) {
        $strResult .= "<select id=\"$varname\" name=\"$varname\" class=\"form-control select2\" $action>\n";
    }
    $strResult .= $extra;
    $strSQL = "SELECT * FROM hrd_leave_level_quota $criteria ORDER BY id ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $strCode = $rowDb['level_code'];
        ($strCode == $default) ? $strSelect = "selected" : $strSelect = "";
        $strResult .= "<option value=\"$strCode\" $strSelect>$strCode ";
        if ($rowDb['max_quota'] != "") {
            $strResult .= "   - " .$rowDb['max_quota']. " days";
        }
        $strResult .= " </option>\n";
    }
    if (!$listonly) {
        $strResult .= "</select>\n";
    }
    return $strResult;
}

function getCriteria($strCode){
    global $arrUserInfo;
    $criteria = '';
    $strDataDivision = $arrUserInfo['division_code'];
    $strDataDepartment = $arrUserInfo['department_code'];
    $strDataSection = $arrUserInfo['section_code'];
    $strDataSubSection = $arrUserInfo['sub_section_code'];
    switch ($strCode){
        case 'division_code':
            if ($strDataDivision != ''){
                $criteria = "AND division_code = '$strDataDivision' ";
            }
            break;
        case 'department_code':
            if ($strDataDepartment !=''){
                $criteria = "AND department_code = '$strDataDepartment'";
            }
            break;
        case 'section_code':
            if ($strDataSection != ''){
                $criteria = "AND section_code = '$strDataSection'";
            }
            break;
        case 'sub_section_code':
            if ($strDataSubSection != ''){
                $criteria = "AND sub_section_code = '$strDataSubSection'";
            }
            break;
    }
    return $criteria;
}
?>
