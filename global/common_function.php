<?php
function generateSelect($name, $data, $default = "", $attribute = "", $event = "")
{
    $strResult = "<select class=\"form-control select2\" name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . ">\n";
    foreach ($data as $row) {
        if ($row['value'] == $default) {
            $strResult .= "  <option value=\"" . $row['value'] . "\" selected>" . $row['text'] . "</option>\n";
        } else {
            $strResult .= "  <option value=\"" . $row['value'] . "\">" . $row['text'] . "</option>\n";
        }
    }
    $strResult .= "</select>";
    return $strResult;
}

function generateOption($data, $default = "")
{
    $strResult = "";
    foreach ($data as $row) {
        if ($row['value'] == $default) {
            $strResult .= "  <option value=\"" . $row['value'] . "\" selected>" . $row['text'] . "</option>\n";
        } else {
            $strResult .= "  <option value=\"" . $row['value'] . "\">" . $row['text'] . "</option>\n";
        }
    }
    return $strResult;
}

function generateInput($name, $value, $attribute = "", $event = "", $class = "")
{
    $strResult = "<input class=\"form-control $class\" type=\"text\" name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . " value=\"" . $value . "\" />";
    return $strResult;
}

function generateNumber($name, $value, $attribute = "", $event = "")
{
    $strResult = "<input class=\"form-control\" type=\"number\" name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . " value=\"" . $value . "\" />";
    return $strResult;
}

function generateLabel($name, $value, $attribute = "", $event = "")
{
    $strResult = "<label name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . " value=\"" . $value . "\" />";
    return $strResult;
}

function generateButton($name, $value, $attribute = "", $event = "")
{
    $strResult = "<input type=\"button\" name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . " value=\"" . $value . "\" />";
    return $strResult;
}

function generateSubmit($name, $value, $attribute = "", $event = "", $btnClass = 'btn-primary')
{
    $strResult = "<input class=\"btn $btnClass\" type=\"submit\" name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . " value=\"" . $value . "\" />";
    return $strResult;
}

function generateRadio($name, $value, $attribute = "", $event = "")
{
    $strResult = "<label><input type=\"radio\" name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . " value=\"" . $value . "\" /></label>";
    return $strResult;
}

function generateCheckBox($name, $value, $attribute = '', $event = '', $label = '', $addedClass = '')
{
    if ($value == 't') {
        $strResult = "<div class=\"checkbox $addedClass\"><label class=\"checkbox-inline\" ><input type=\"checkbox\" name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . " value=\"t\" checked >" . $label . "</label></div>";
    } else {
        $strResult = "<div class=\"checkbox $addedClass\"><label class=\"checkbox-inline\" ><input type=\"checkbox\" name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . " value=\"f\" >" . $label . "</label></div>";
    }
    return $strResult;
}

function generateHidden($name, $value, $strAttribute = "")
{
    $strResult = "<input type=\"hidden\" name=\"" . $name . "\" id=\"" . $name . "\" value=\"" . $value . "\" " . $strAttribute . " />";
    return $strResult;
}

function generateTextArea($name, $value, $attribute = "", $event = "")
{
    $strResult = "<textarea class=\"form-control\"  name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . ">" . $value . "</textarea>";
    return $strResult;
}

function generateRoleButtons(
    $bolCanEdit,
    $bolCanDelete,
    $bolCanCheck,
    $bolCanApprove,
    $bolCanAcknowledge,
    $bolDatagridClass = false,
    &$objDatagrid = null
) {
    global $words;
    if ($bolDatagridClass) {
        include_once('../includes/datagrid2/datagrid.php');
        if ($bolCanDelete) {
            $objDatagrid->addSpecialButton(
                "btnDelete",
                "btnDelete",
                "submit",
                getWords('delete'),
                "onClick=\"javascript:return myClient.confirmDelete();\"",
                "deleteData()"
            );
        }
        if ($bolCanCheck) {
            $objDatagrid->addSpecialButton(
                "btnChecked",
                "btnChecked",
                "submit",
                getWords('checked'),
                "onClick=\"javascript:return myClient.confirmChangeStatus();\"",
                "callChangeStatus()"
            );
            //$objDatagrid->addSpecialButton("btnDenied", "btnDenied", "submit", $words['denied'], "onClick=\"javascript:return myClient.confirmChangeStatus();\"", "callChangeStatus()");
        }
        if ($bolCanApprove) {
            $objDatagrid->addSpecialButton(
                "btnApproved",
                "btnApproved",
                "submit",
                getWords('approved'),
                "onClick=\"javascript:return myClient.confirmChangeStatus();\"",
                "callChangeStatus()"
            );
            $objDatagrid->addSpecialButton(
                "btnDenied",
                "btnDenied",
                "submit",
                getWords('denied'),
                "onClick=\"javascript:return myClient.confirmChangeStatus();\"",
                "callChangeStatus()"
            );
        }
        if ($bolCanAcknowledge) {
            $objDatagrid->addSpecialButton(
                "btnAcknowledged",
                "btnAcknowledged",
                "submit",
                getWords('acknowledged'),
                "onClick=\"javascript:return myClient.confirmChangeStatus();\"",
                "callChangeStatus()"
            );
            //$objDatagrid->addSpecialButton("btnClose", "btnClose", "submit", $words['close'], "onClick=\"javascript:return myClient.confirmChangeStatus();\"", "callChangeStatus()");
        }
    } else {
        $strButtons = "";
        /* if ($bolCanEdit)
          {
            $strButtons .= "&nbsp;";
            $strButtons .= generateSubmit("btnVerified", $words['verified'], "", " onClick=\"return confirmStatusChanges(false)\"");
          }*/
        if ($bolCanDelete) {
            $strButtons .= "&nbsp;";
            $strButtons .= generateSubmit(
                "btnDelete",
                getWords('delete'),
                "",
                " onClick=\"return confirmDelete()\"",
                "btn-danger"
            );
        }
        if ($bolCanCheck) {
            $strButtons .= "&nbsp;";
            $strButtons .= generateSubmit(
                "btnChecked",
                getWords('check'),
                "",
                " onClick=\"return confirmStatusChanges(false)\"",
                "btn-warning"
            );
            //$strButtons .= "&nbsp;";
            //$strButtons .= generateSubmit("btnDenied", $words['denied'], "", " onClick=\"return confirmStatusChanges(false)\"");
        }
        if ($bolCanApprove) {
            $strButtons .= "&nbsp;";
            $strButtons .= generateSubmit(
                "btnApproved",
                getWords('approved'),
                "",
                " onClick=\"return confirmStatusChanges(false)\""
            );
            $strButtons .= "&nbsp;";
            $strButtons .= generateSubmit(
                "btnDenied",
                getWords('denied'),
                "",
                " onClick=\"return confirmStatusChanges(false)\"",
                "btn-danger"
            );
            $strButtons .= "&nbsp;";
        }
        if ($bolCanAcknowledge) {
            $strButtons .= "&nbsp;";
            $strButtons .= generateSubmit(
                "btnAcknowledged",
                getWords('acknowledged'),
                "",
                " onClick=\"return confirmStatusChanges(false)\""
            );
        }
        return $strButtons;
    }
}

function generateSelectMonth(
    $name,
    $default = "",
    $attribute = "",
    $event = "",
    $hasEmptyData = false,
    $emptyData = null,
    $className = null
) {
    $arrMonth = [
        "1" => "Jan",
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
    if (empty($className)) {
        $className = 'class="form-control select2 select-month"';
    } else {
        $className = '';
    }
    $strResult = "<select name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . " " . $className . ">\n";
    if ($default == "") {
        $default = date('m');
    }
    foreach ($arrMonth as $key => $val) {
        if ($key == $default) {
            $strResult .= "  <option value=\"" . $key . "\" selected>" . $val . "</option>\n";
        } else {
            $strResult .= "  <option value=\"" . $key . "\">" . $val . "</option>\n";
        }
    }
    $strResult .= "</select>";
    return $strResult;
}

function generateSelectYear(
    $name,
    $default = "",
    $attribute = "",
    $event = "",
    $hasEmptyData = false,
    $emptyData = null,
    $intLimit = 50,
    $isAscending = false,
    $className = null
) {
    if (empty($className)) {
        $className = 'class="form-control select2 select-year"';
    } else {
        $className = '';
    }
    $strResult = "<select name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . " " . $className . ">\n";
    if ($hasEmptyData && $emptyData !== null) {
        $strResult .= "  <option value=\"\">" . $emptyData . "</option>\n";
    }
    $intYear = intval(date("Y"));
    if ($isAscending) {
        $intYear -= $intLimit;
        for ($i = 0; $i < $intLimit; $i++) {
            if ($intYear == $default) {
                $strResult .= "  <option value=\"" . $intYear . "\" selected>" . $intYear . "</option>\n";
            } else {
                $strResult .= "  <option value=\"" . $intYear . "\">" . $intYear . "</option>\n";
            }
            $intYear++;
        }
    } else {
        for ($i = 0; $i < $intLimit; $i++) {
            if ($intYear == $default) {
                $strResult .= "  <option value=\"" . $intYear . "\" selected>" . $intYear . "</option>\n";
            } else {
                $strResult .= "  <option value=\"" . $intYear . "\">" . $intYear . "</option>\n";
            }
            $intYear--;
        }
    }
    $strResult .= "</select>";
    return $strResult;
}

function generateSpan($name, $value, $style = "")
{
    return "<span id=\"$name\" name=\"$name\" $style>" . $value . "</span>";
}

function generateDiv($name, $value, $style)
{
    return "<div id=\"$name\" name=\"$name\" " . $style . ">" . $value . "</div>";
}

function generateFile($name, $value = null, $attribute = "", $event = "")
{
    $strResult = "<input type=\"file\" name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . " />";
    return $strResult;
}

function getMonth($m = 0)
{
    return (($m == 0) ? date("F") : date("F", mktime(0, 0, 0, $m)));
}

/*
    Daftar fungsi-fungsi (super) global
      Author: Yudi K.
  */
// fungsi untuk meredirect ke suatu page
// $strDestPage = tujuan, $strSrcPage = asal, atau yang lama, $bolSave = simpan ke session
function redirectPage($strDestPage = "login.php", $strSrcPage = "", $bolSave = true)
{
    global $_SESSION;
    if ($strDestPage != "") {
        if ($bolSave) {
            if ($strSrcPage == "") {
                $strSrcPage = $_SERVER['PHP_SELF'];
            }
            $_SESSION['sessionLastPage'] = $strSrcPage;
        }
        header("location:$strDestPage");
    }
}

// fungsi untuk menentukan apakah yang login adalah employee biasa (staff, bukan admin sistem terkait)
// pokokny ayang bukan super sys admin, admin, manager
function isMe($strIDEmployee)
{
    global $arrUserInfo;
    $bolResult = ($arrUserInfo['id_employee'] == $strIDEmployee);
    return $bolResult;
}

// fungsi untuk menentukan apakah yang login adalah employee biasa (staff, bukan admin sistem terkait)
// pokokny ayang bukan super sys admin, admin, manager
function isUserEmployee()
{
    global $_SESSION;
    // employee adalah yang role = 2 (user), dan ada gak punya posisi (jabatan)
    $bolResult = ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE);
    return $bolResult;
}

function thisUserIs($strRole)
{
    global $_SESSION;
    // employee adalah yang role = 2 (user), dan ada gak punya posisi (jabatan)
    $bolResult = ($_SESSION['sessionUserRole'] == $strRole);
    return $bolResult;
}

// fungsi untuk menentukan apakah yang login adalah employee biasa,
// tapi posisinya sebagai department_head
function isUserDeptHead($db)
{
    global $_SESSION;
    global $arrUserInfo;
    $bolResult = false;
    $strDeptHead = getSetting("department_head");
    $bolResult = (strtoupper($strDeptHead) == strtoupper($arrUserInfo['position_code']));
    return $bolResult;
} // isUserDeptHead
// fungsi untuk menentukan apakah yang login adalah employee biasa,
// tapi posisinya sebagai department_head
function isUserGroupHead($db)
{
    global $_SESSION;
    global $arrUserInfo;
    $bolResult = false;
    $strGroupHead = getSetting("group_head");
    $bolResult = (strtoupper($strGroupHead) == strtoupper($arrUserInfo['position_code']));
    return $bolResult;
} // bolUserGroupHead
// fungsi untuk menentukan apakah yang login adalah employee, tapi DEPT. HEAD (yang berhak approve punya employee)
function isUserManager()
{
    global $_SESSION;
    global $arrUserInfo;
    global $db;
    $bolResult = false;
    $strManagerCode = getSetting("department_head");
    if ($strManagerCode != "" && $arrUserInfo['idEmployee']) {
        // manager adalah user yang jabatannya sesuai
        $strSQL = "SELECT position_code FROM hrd_employee ";
        $strSQL .= "WHERE id = '" . $arrUserInfo['idEmployee'] . "' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $bolResult = (strtoupper($strManagerCode) == strtoupper($rowDb['position_code']));
        }
    }
    return $bolResult;
}// isUserManager
function getUserEmployeeInfo()
{
    global $db;
    global $arrUserInfo;
    global $ARRAY_DISABLE_GROUP;
    global $_SESSION;
    global $strEmpReadonly;
    global $strNonCbReadonly;
    if (isset($_SESSION['sessionUserID'])) {
        $strUserID = $_SESSION['sessionUserID'];
        if ($strUserID == "") {
            return 0;
        }
    } else {
        return 0;
    }
    $strSQL = "SELECT t1.id, t1.employee_id, t1.employee_name, t1.position_code, t1.employee_status, ";
    $strSQL .= "t1.functional_code,t1.division_code, t1.department_code, t1.section_code, ";
    $strSQL .= "t1.sub_section_code, t1.id_company, t1.grade_code, t3.weight ";
    $strSQL .= "FROM adm_user AS t2 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t1 ON TRIM(t1.employee_id) = TRIM(t2.employee_id)  ";
    $strSQL .= "LEFT JOIN hrd_salary_grade t3 ON t1.grade_code = t3.grade_code ";
    $strSQL .= "WHERE t2.id_adm_user = '$strUserID' ";
    //$strSQL .= "AND t1.active = 1 ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        $arrUserInfo['id_employee'] = $rowDb['id'];
        $arrUserInfo['employee_id'] = $rowDb['employee_id'];
        $arrUserInfo['employee_name'] = $rowDb['employee_name'];
        $arrUserInfo['employee_status'] = $rowDb['employee_status'];
        $arrUserInfo['division_code'] = $rowDb['division_code'];
        $arrUserInfo['department_code'] = $rowDb['department_code'];
        $arrUserInfo['section_code'] = $rowDb['section_code'];
        $arrUserInfo['sub_section_code'] = $rowDb['sub_section_code'];
        $arrUserInfo['position_code'] = $rowDb['position_code'];
        $arrUserInfo['functional_code'] = $rowDb['functional_code'];
        $arrUserInfo['id_company'] = $rowDb['id_company'];
        $arrUserInfo['grade_code'] = $rowDb['grade_code'];
        $arrUserInfo['grade_weight'] = is_null($rowDb['weight']) ? 0 : $rowDb['weight'];
        if ($rowDb['id_company'] != "") {
            $strSQL = "SELECT * FROM hrd_company WHERE id = " . $rowDb['id_company'];
            $resDb = $db->execute($strSQL);
            $arrUserInfo['company_code'] = ($rowDb = $db->fetchrow($resDb)) ? $rowDb['company_code'] : "";
        } else {
            $arrUserInfo['company_code'] = "";
        }
    }
    $arrUserInfo['display_print_all'] = true;
    $arrUserInfo['display_reset'] = true;
    if ($_SESSION['sessionUserRole'] == ROLE_EMPLOYEE) {
        $strEmpReadonly = "readonly";
        $ARRAY_DISABLE_GROUP['division'] = $ARRAY_DISABLE_GROUP['department'] = $ARRAY_DISABLE_GROUP['section'] =
        $ARRAY_DISABLE_GROUP['sub_section'] = "disabled";
        $arrUserInfo['display_print_all'] = false;
        $arrUserInfo['display_reset'] = false;
    } else if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
        if ($arrUserInfo['division_code'] != "") {
            $ARRAY_DISABLE_GROUP['division'] = "disabled";
        }
        if ($arrUserInfo['department_code'] != "") {
            $ARRAY_DISABLE_GROUP['department'] = "disabled";
        }
        if ($arrUserInfo['section_code'] != "") {
            $ARRAY_DISABLE_GROUP['section'] = "disabled";
        }
        if ($arrUserInfo['sub_section_code'] != "") {
            $ARRAY_DISABLE_GROUP['sub_section'] = "disabled";
        }
        $arrUserInfo['display_print_all'] = true;
        $arrUserInfo['display_reset'] = false;
    }
    if ($_SESSION['sessionUserRole'] != ROLE_SUPER) {
        $strNonCbReadonly = "readonly";
    }
} //getUserEmployeeInfo
// fungsi untuk mengambil informasi user
function getAllUserInfo($db)
{
    $arrResult = [];
    $strSQL = "SELECT * FROM adm_user ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrResult[$rowDb['id_adm_user']]['name'] = ($rowDb['name'] == "") ? $rowDb['login_name'] : $rowDb['name'];
        $arrResult[$rowDb['id_adm_user']]['login'] = $rowDb['login_name'];
        $arrResult[$rowDb['id_adm_user']]['car'] = $rowDb['id_adm_company'];
    }
    return $arrResult;
} //getAllUserInfo
// fungsi untuk menampilkan error, berupa alert, di javascript
function showError($str)
{
    global $error;
    $strMsg = "<script>alert(\"" . $error[$str] . "\")</script>";
    echo $strMsg;
}//showError
//fungsi untuk memeriksa apakah sebuah nilai adalah numeric
//input: sembarang
//output: benar jika input adalah numerik
function isNumeric($value)
{
    return is_float($value);
} //end of isNumeric
// fungsi untuk menulis data log file
// activity= tipe aktifitas yang dikerjakan, module=kode module, page=url, note=keterangan tambahan
function writeLog($intActivity = 0, $intModule = 0, $strNote = "", $intStatus = 0)
{
    global $dataPrivilege;
    /*
      global $bolSaveLog;

      $bolSaveLog = (getSetting("user_log") == 't');
      if (!$bolSaveLog)
      {
        return 0;
      }*/
    //echo $_SERVER['HTTP_USER_AGENT'];
    //$browser = get_browser(null, true);
    //var_dump($browser);
    //exit();
    $str_browser = $_SERVER['HTTP_USER_AGENT'];
    $strSession = session_id();
    $intUserID = (isset($_SESSION['sessionUserID'])) ? $_SESSION['sessionUserID'] : -1;
    $intUserType = (isset($_SESSION['sessionUserType'])) ? $_SESSION['sessionUserType'] : -1;
    $strUserLogin = (isset($_SESSION['sessionUser'])) ? substr($_SESSION['sessionUser'], 0, 30) : "";
    $arrPage = explode("/", $_SERVER['PHP_SELF']);
    $strPage = (count($arrPage) > 0) ? substr($arrPage[count($arrPage) - 1], 0, 50) : "";
    $strNote = substr($strNote, 0, 255);
    $strIP = $_SERVER['REMOTE_ADDR'];
    $strMenuID = $dataPrivilege['id_adm_menu'];
    $arrData = [
        "id_adm_user" => $intUserID,
        "action_type" => $intActivity,
        "id_adm_menu" => $strMenuID,
        "page"        => $strPage,
        "message"     => $strNote,
        "ip_address"  => $strIP,
        "php_file"    => $strPage,
        "status"      => $intStatus,
        "session_id"  => $strSession,
        "browser"     => $str_browser
    ];
    $tbl = new cModel("adm_userlog");
    $tbl->insert($arrData);
    return 0;
}

//fungsi untuk mengetahui apakah suatu data ada atau tidak
//criteria dalam format "AND ...."
function isDataExists($db, $table, $field, $data, $criteria = "")
{
    $bolResult = false;
    // dibuat incase-sensitif aja
    $data = strtolower($data);
    $strSQL = "SELECT \"$field\" FROM \"$table\" ";
    $strSQL .= "WHERE lower(\"$field\") = '$data' $criteria ";
    $resTmp = $db->execute($strSQL);
    if ($db->numrows($resTmp) > 0) {
        $bolResult = true;
    }
    return $bolResult;
} //isDataExists
//========================================================================================================================================
//Edit : 8 Agustus 2008
//fungsi untuk mengetahui apakah suatu data ada atau tidak(digunakan khusus untuk form Overtime Application untuk casting type Bigint)
//criteria dalam format "AND ...."
function isDataExists_($db, $table, $field, $data, $criteria = "")
{
    $bolResult = false;
    // dibuat incase-sensitif aja
    $data = strtolower($data);
    $strSQL = "SELECT \"$field\" FROM \"$table\" ";
    $strSQL .= "WHERE (\"$field\") = '$data' $criteria ";
    $resTmp = $db->execute($strSQL);
    if ($db->numrows($resTmp) > 0) {
        $bolResult = true;
    }
    return $bolResult;
} //isDataExists
//fungsi untuk mengambil data ID dari modified_by (pembuat data)
//input: class DB, nama tabel, nama field (biasanya ID), nilai dari field,
//output: id dari modified_by (user pembuat)
function getmodified_byID($strTable, $strColumn, $strValue)
{
    $tbl = new cModel($strTable);
    if ($row = $tbl->find("$strColumn = '$strValue'", "modified_by")) {
        return $row['modified_by'];
    }
    return "";
} // end of getmodified_byID
// fungsi untuk menghasilkan daftar page (pada page info)
// input: total record, record per page, current page, link target
// output: daftar halaman (lengkap dengan linknya)
function generatePages($intTotal, $intPerPage, $intCurrPage, $strLink)
{
    global $words;
    $strHasil = "";
    if ($intPerPage > 0) {
        if (($intTotal % $intPerPage) > 0) {
            $intTotalPage = $intTotal / $intPerPage + 1;
        } else {
            $intTotalPage = $intTotal / $intPerPage;
        }
        $intTotalPage = (int)$intTotalPage;
        if ($intTotalPage > 1) { // hanya diproses jika jumlah halaman > 1
            //prev link
            if ($intCurrPage == 1) {
                $strHasil .= "&laquo; ";
            } else {
                $strHasil .= "<a href=\"$strLink?page=" . ($intCurrPage - 1) . "\">&laquo;</a> ";
            }
            for ($i = 1; $i <= $intTotalPage; $i++) {
                if ($i == $intCurrPage) {
                    $strHasil .= "<b>$i</b> ";
                } else {
                    $strHasil .= "<a href=\"$strLink?page=$i\">$i</a> ";
                }
            }
            //next link
            if ($intTotalPage == $intCurrPage) {
                $strHasil .= "&raquo; ";
            } else {
                $strHasil .= "<a href=\"$strLink?page=" . ($intCurrPage + 1) . "\">&raquo;</a> ";
            }
            //all
            $strHasil .= " &nbsp;<a href=\"$strLink?all=1\">[all]</a> &nbsp; ";
        }
    }
    return $strHasil;
} //generatePages
//fungsi untuk mengambil data konfigurasi'
//data = database, code adalah kode konfigurasi
function getSetting($code, $bolGeneral = false)
{
    $tbl = ($bolGeneral) ? new cModel("all_setting_general") : new cModel("all_setting");
    if ($arrHasil = $tbl->findByCode($code)) {
        return $arrHasil['value'];
    } else {
        return false;
    }
}  //getSetting
//fungsi untuk menyimpan data konfigurasi'
//data = database, code adalah kode konfigurasi, $value = nilainya
function saveSetting($code, $value, $note = null, $module = null, $bolGeneral = false)
{
    $tbl = ($bolGeneral) ? new cModel("all_setting_general") : new cModel("all_setting_template");
    // cek dulu apakah ada settingnya
    $jml = $tbl->findCount("code = '$code'");
    $isSuccess = false;
    if ($note === null) {
        $note = "";
    }
    if ($module === null) {
        $module = (isset($GLOBALS['strModule'])) ? $GLOBALS['strModule'] : 0;
    }
    $data = [
        "code"   => $code,
        "value"  => $value,
        "note"   => $note,
        "module" => $module
    ];
    if ($jml > 0) {
        $isSuccess = $tbl->update(["code" => $code], $data);
    } else { // buat baru
        $isSuccess = $tbl->insert($data);
    }
    return $isSuccess;
}  //saveSetting
// fungsi untuk memformat angka ribuan, dengan format English
// standard memakai 2 decimal, jika 0, diabaikan
// Misal 11111111.11 jadi 11,111,111.11
// iqnoreZero => 0 diabaikan
// $intDec = jumlah angka dibelakang koma
function standardFormat($fltX = 0, $ignoreZero = false, $intDec = 0)
{
    if (!is_numeric($intDec)) {
        $intDec = 0;
    }
    if ($ignoreZero && $fltX == 0) {
        return 0;
    } else {
        if (is_numeric($fltX) == false) {
            $fltX = 0;
        }
        return number_format($fltX, $intDec, ',', '.');
    }
}

// fungsi untuk menentukan apakah sebuah hari itu hari libur atau tidak
function isHoliday($strDate, $bolAllHoliday = true)
{
    global $db;
    $bolResult = false;
    if ($strDate == "") {
        return false;
    }
    // cari hari dan tanggalnya
    list($tahun, $bulan, $tanggal) = explode("-", $strDate);
    $tsTanggal = mktime(0, 0, 0, $bulan, $tanggal, $tahun);
    $dtTanggal = getdate($tsTanggal);
    // cari di calendar
    $tbl = new cModel("hrd_calendar");
    if ($rowDb = $tbl->findByHoliday($strDate, "id, status")) {
        $bolResult = ($rowDb['status'] == 't'); // bisa saja hari libur, atau hari libur tapi dianggap masuk (pengganti)
    } else if ($bolAllHoliday) {
        // tidak ada catatann hari libur
        if ($dtTanggal['wday'] == 0) { // hari minggu, libur
            $bolResult = true;
        } else if ($dtTanggal['wday'] == 6) { // hari sabtu
            if (getSetting("saturday") == "t") {
                $bolResult = true;
            }
        }
    }
    return $bolResult;
}//isHoliday
// fungsi untuk mmembuat paging dari data
function getPaging($intPage = 1, $intTotal = 1, $strLink = "", $nomargin = "no-margin")
{
    global $intPageLimit; // jumlah link page maksimal yang ditampilkan
    global $intRowsLimit; // jumlah baris yang ditampilkan satu page
    $strResult = "";
    // cari jumlah halaman
    $intTotalPage = ceil($intTotal / $intRowsLimit);
    //if (($intTotal % $intRowsLimit) == 0) {
    //  $intTotalPage++;
    //}
    // cari start page dan finish page yang akan ditambilkan
    if (($intPage % $intPageLimit) == 0) {
        $intPageStart = ((($intPage / $intPageLimit) - 1) * $intPageLimit) + 1; // + ($intPage % $intPageLimit);
    } else {
        $intPageStart = ((floor($intPage / $intPageLimit)) * $intPageLimit) + 1; // + ($intPage % $intPageLimit);
    }
    $intPageFinish = $intPageStart + $intPageLimit - 1;
    if ($intPageStart < 1) {
        $intPageStart = 1;
    };
    if ($intPageFinish > $intTotalPage) {
        $intPageFinish = $intTotalPage;
    };
    // tambahkan link untuk prev dan first page
    $strResult .= '<ul class="pagination ' . $nomargin . '">';
    if ($intPage > 1) {
        $strResult .= "<li><a href=\"" . str_replace("[PAGE]", "1", $strLink) . "\">&laquo;&laquo;</a></li>";
        $strResult .= "<li><a href=\"" . str_replace("[PAGE]", ($intPage - 1), $strLink) . "\">&laquo;</a></li>";
    }
    for ($i = $intPageStart; $i <= $intPageFinish; $i++) {
        if ($i == $intPage) {
            $strResult .= "<li class=\"active\"><a href=\"#\" onclick=\"this.preventDefault();\"><strong>$i</strong></a></li>";
        } else {
            $strResult .= "<li><a href=\"" . str_replace("[PAGE]", $i, $strLink) . "\">$i</a></li>";
        }
    }
    // tambahkan link next dan last page
    if ($intPage < $intTotalPage) {
        $strResult .= "<li><a href=\"" . str_replace("[PAGE]", ($intPage + 1), $strLink) . "\">&raquo;</a></li>";
        $strResult .= "<li><a href=\"" . str_replace("[PAGE]", $intTotalPage, $strLink) . "\">&raquo;&raquo;</a></li>";
    }
    $strResult .= '</ul>';
    return $strResult;
}//getPaging
// fungsi untuk menghitung selisih hari kerja
// Misal : tanggal 1 s.d. 2, berarti ada 2 hari
// Selisih hari adalah hari kerja, berarti selisih juga dikurangi dengan hari minggu dan libur, juga hari sabtu jika libur
// $db adalah kelas database, $strFrom dan $strThru dalam format standard date PG, "YYYY-MM-DD"
// output : total selisih hari
function totalWorkDay($db, $strFrom, $strThru)
{
    include_once("activity.php");
    global $arrWorkDay; // untuk menampung total workday, datefrom-datethru, agar menghemat pencarian, jika sudah pernah ada
    //if (isset($arrWorkDay["$strFrom.$strThru"])) return $arrWorkDay["$strFrom.$strThru"]; // langsung dibalikin
    if ($strFrom == "" || $strThru == "") {
        return 0;
    }
    $intResult = 0;
    $strFrom = pgDateFormat($strFrom, "Y-m-d");
    $strThru = pgDateFormat($strThru, "Y-m-d");
    if ($strFrom > $strThru) {
        $intResult = 0;
    } else if ($strFrom == $strThru) {
        $intResult = 1;
    } else {
        // ubah format dalam timestamp
        list($intYear, $intMonth, $intDay) = explode("-", $strFrom);
        $tsFrom = mktime(0, 0, 0, (int)$intMonth, (int)$intDay, (int)$intYear);
        $dtFrom = getdate($tsFrom);
        list($intYear, $intMonth, $intDay) = explode("-", $strThru);
        $tsThru = mktime(0, 0, 0, (int)$intMonth, (int)$intDay, (int)$intYear);
        $dtThru = getdate($tsThru);
        $intSelisih = round(($tsThru - $tsFrom) / 86400); // selisih dalam hari
        $intSelisihMinggu = floor($intSelisih / 7); // selisih dalam minggu
        $intResult = $intSelisih + 1; // karena hari awal dihitung juga
        $intModMinggu = ($intSelisih % 7);
        // dikurangi dengan hari minggu
        $intResult -= $intSelisihMinggu;
        if ($intModMinggu > 0) {
            if ($dtFrom['wday'] == 0 || $dtThru['wday'] == 0) {
                $intResult--;
            } else if ($dtFrom['wday'] > $dtThru['wday']) {
                $intResult--;
            }
        }
        // cek jika ada hari minggu yang ditngah-tengahnya
        /*
          if (($dtFrom['wday'] != 0) && ($dtThru['wday'] != 0) && ($dtFrom['wday'] > $dtThru['wday'])) {
            $intResult--;
          }
          */
        // dikurangi hari sabtu jika, sabtu libur
        $bolSaturday = (getSetting("saturday") == 't');
        if ($bolSaturday) {
            /*
              if ($intSelisihMinggu == 0) {
                // cari apakah hari minggu atau bukan
                if ($dtFrom['wday'] == 6 || $dtThru['wday'] == 6) {
                  $intResult--;
                } else if ($dtThru['wday'] == 0) {
                  $intResult--;
                }
              } else {
                $intResult -= $intSelisihMinggu;
                if ($dtFrom['wday'] == 6 || $dtThru['wday'] == 6) {
                  $intResult--;
                  echo " ada ";
                }
              }
              echo "-Sbt:$intResult x[$intSelisihMinggu] ";
              // cek jika ada hari sabtu yang ditngah-tengahnya
              if (($dtFrom['wday'] != 6) && ($dtThru['wday'] != 6) && ($dtFrom['wday'] > $dtThru['wday']) && ($dtFrom['wday'] != 0) && ($dtThru['wday'] != 0)) {
                $intResult--;
              }
              */
            $intResult -= $intSelisihMinggu;
            if ($intModMinggu > 0) {
                if ($dtFrom['wday'] == 6 || $dtThru['wday'] == 6) {
                    $intResult--;
                } else if ($dtFrom['wday'] > $dtThru['wday']) {
                    $intResult--;
                }
            }
        }
        // cari data hari libur
        // selain hari minggu
        $strSQL = "SElECT COUNT(id) AS total FROM hrd_calendar ";
        $strSQL .= "WHERE holiday BETWEEN '$strFrom' AND '$strThru' ";
        $strSQL .= "AND EXTRACT(dow FROM holiday) <> 0 ";
        if ($bolSaturday) {
            $strSQL .= "AND EXTRACT(dow FROM holiday) <> 6 ";
        }
        $strSQL .= "AND status = 't' "; // cari yang libur saja
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if (is_numeric($rowDb['total'])) {
                $intResult -= $rowDb['total'];
            }
        }
        // cari data pengganti libur
        $strSQL = "SElECT COUNT(id) AS total FROM hrd_calendar ";
        $strSQL .= "WHERE holiday BETWEEN '$strFrom' AND '$strThru' ";
        $strSQL .= "AND status = 'f' "; // cari yang libur saja
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if (is_numeric($rowDb['total'])) {
                $intResult += $rowDb['total'];
            }
        }
    }
    //echo $intResult;
    // simpan ke var global
    $arrWorkDay["$strFrom.$strThru"] = $intResult;
    $intResult = round($intResult);
    return round($intResult);
} //totalOffDay
function totalWorkDayEmployee($db, $strIDEmployee, $strFrom, $strThru)
{
    $intWorkday = $intTotal = getIntervalDate($strFrom, $strThru) + 1;
    $arrShift = getShiftSchedule($db, getNextDate($strFrom, -1), $strThru, $strIDEmployee);
    $strCurDate = $strFrom;
    while ($strCurDate <= $strThru) {
        if (isEmployeeHoliday($db, $strCurDate, $strIDEmployee)) {
            $intWorkday--;
        }
        $strCurDate = getNextDate($strCurDate);
    }
    return $intWorkday;
}

/*
function totalWorkDayEmployee($db, $strIDEmployee, $strFrom, $strThru)
  {
    include_once(dirname(dirname(__FILE__))."/includes/date/date.php");
    include_once("activity.php");


    if ($strFrom == "" || $strThru == "") return 0;

    $intResult = 0;
    $strFrom = pgDateFormat($strFrom, "Y-m-d");
    $strThru = pgDateFormat($strThru, "Y-m-d");

    if ($strFrom > $strThru)
    {
      swap($strFrom, $strThru);
    }

    // ubah format dalam timestamp
    list($intYear, $intMonth, $intDay) = explode("-",$strFrom);
    $dt1 = new clsDate(intval($intYear), intval($intMonth), intval($intDay));
    $tsFrom = mktime(10,0,0,(int)$intMonth,(int)$intDay, (int)$intYear);
    $dtFrom = getdate($tsFrom);
    list($intYear, $intMonth, $intDay) = explode("-",$strThru);
    $dt2 = new clsDate(intval($intYear), intval($intMonth), intval($intDay));
    $tsThru = mktime(10,0,0,(int)$intMonth,(int)$intDay, (int)$intYear);
    $dtThru = getdate($tsThru);


    //ambil workschedule berdasarkan jadwal per susunan organisasi
    $tblWorkingSchedule = new cModel("hrd_work_schedule");
    $arrScheduleRaw = $tblWorkingSchedule->findAll(null, null, "workday ASC");
    $arrSchedule = array();
    foreach($arrScheduleRaw as $rowDb)
    {
      if ($rowDb['workday'] == -1)
      {
        //all days
        for($i = 0; $i <= 6; $i++)
        {
          $arrSchedule[$rowDb['table_name']][$rowDb['link_code']][$i] = $rowDb['day_off'];
        }
      }
      else
        $arrSchedule[$rowDb['table_name']][$rowDb['link_code']][$rowDb['workday']] = $rowDb['day_off'];
    }

    if (getSetting("saturday") != 'f')
      $isSaturdayOff = 't';
    else
      $isSaturdayOff = 'f';
    $tblEmployee = new cModel("hrd_employee");
    if ($arrData = $tblEmployee->findById($strIDEmployee))
    {
      for($i = 0; $i <= 6; $i++)
      {
        $arrResult[$strIDEmployee][$i] = getScheduleDetail($i, $arrData, $arrSchedule);
        //jika tidak ada working schedule, maka baca dari general setting
        if ($arrResult[$strIDEmployee][$i] == false)
        {
          //sabtu
          $isDayOff = 'f';
          if ($i == 6)
            $isDayOff = $isSaturdayOff;
          //minggu
          else if ($i == 0) $isDayOff = 't';
          $arrResult[$strIDEmployee][$i] = array("day_off"     => $isDayOff);
        }
      }
    }
    print_r($arrResult);
    // cari data hari libur
    // selain hari minggu
    $arrHoliday = getListHoliday($strFrom, $strThru);
    $intResult = 0;
    while ($dt1->format("Y-m-d") <= $dt2->format("Y-m-d"))
    {
      $intDayWeek = $dt1->DayofWeek();
      if (isset($arrResult[$strIDEmployee][$intDayWeek]))
        if ($arrResult[$strIDEmployee][$intDayWeek]['day_off'] != 't')
        {
          if (!isset($arrHoliday[$dt1->format("Y-m-d")]))
            $intResult++;
        }

      $dt1->addDays(1);
    }

    return $intResult;
  } //totalWorkDayEmployee*/
//output: array of date
function getListWorkDayEmployee($db, $strIDEmployee, $strFrom, $strThru)
{
    include_once(dirname(dirname(__FILE__)) . "/includes/date/date.php");
    if ($strFrom == "" || $strThru == "") {
        return 0;
    }
    $intResult = 0;
    $strFrom = pgDateFormat($strFrom, "Y-m-d");
    $strThru = pgDateFormat($strThru, "Y-m-d");
    $arrResultDate = [];
    if ($strFrom > $strThru) {
        swap($strFrom, $strThru);
    }
    // ubah format dalam timestamp
    list($intYear, $intMonth, $intDay) = explode("-", $strFrom);
    $dt1 = new clsDate(intval($intYear), intval($intMonth), intval($intDay));
    $tsFrom = mktime(0, 0, 0, (int)$intMonth, (int)$intDay, (int)$intYear);
    $dtFrom = getdate($tsFrom);
    list($intYear, $intMonth, $intDay) = explode("-", $strThru);
    $dt2 = new clsDate(intval($intYear), intval($intMonth), intval($intDay));
    $tsThru = mktime(0, 0, 0, (int)$intMonth, (int)$intDay, (int)$intYear);
    $dtThru = getdate($tsThru);
    //ambil workschedule berdasarkan jadwal per susunan organisasi
    $tblWorkingSchedule = new cModel("hrd_work_schedule");
    $arrScheduleRaw = $tblWorkingSchedule->findAll(null, null, "workday ASC");
    $arrSchedule = [];
    foreach ($arrScheduleRaw as $rowDb) {
        if ($rowDb['workday'] == -1) {
            //all days
            for ($i = 0; $i <= 6; $i++) {
                $arrSchedule[$rowDb['table_name']][$rowDb['link_code']][$i] = $rowDb['day_off'];
            }
        } else {
            $arrSchedule[$rowDb['table_name']][$rowDb['link_code']][$rowDb['workday']] = $rowDb['day_off'];
        }
    }
    //sabtu
    if (getSetting("saturday") != 'f') {
        $isSaturdayOff = 't';
    } else {
        $isSaturdayOff = 'f';
    }
    $tblEmployee = new cModel("hrd_employee");
    if ($arrData = $tblEmployee->findById($strIDEmployee)) {
        for ($i = 0; $i <= 6; $i++) {
            $arrResult[$strIDEmployee][$i] = getScheduleDetail($i, $arrData, $arrSchedule);
            //jika tidak ada working schedule, maka baca dari general setting
            if ($arrResult[$strIDEmployee][$i] == false) {
                $isDayOff = 'f';
                if ($i == 6) {
                    $isDayOff = $isSaturdayOff;
                } //minggu
                else if ($i == 0) {
                    $isDayOff = 't';
                }
                $arrResult[$strIDEmployee][$i] = ["day_off" => $isDayOff];
            }
        }
    }
    // cari data hari libur
    // selain hari minggu
    $arrHoliday = getListHoliday($strFrom, $strThru);
    $intResult = 0;
    while ($dt1->format("Y-m-d") <= $dt2->format("Y-m-d")) {
        $intDayWeek = $dt1->DayofWeek();
        if (isset($arrResult[$strIDEmployee][$intDayWeek])) {
            if ($arrResult[$strIDEmployee][$intDayWeek]['day_off'] != 't') {
                if (!isset($arrHoliday[$dt1->format("Y-m-d")])) {
                    $arrResultDate[$dt1->format("Y-m-d")] = 1;
                }
            }
        }
        $dt1->addDays(1);
    }
    return $arrResultDate;
} //getListWorkDay
function getListHoliday($strFrom, $strThru)
{
    $tbl = new cModel("hrd_calendar");
    $arrHoliday = $tbl->findAll(
        "(holiday BETWEEN '$strFrom' AND '$strThru') AND status = 't' ",
        null,
        null,
        null,
        null,
        "holiday"
    ); // cari yang libur saja
    return $arrHoliday;
}

// fungsi untuk membulatkan nilai uang, dengan pembulatan tertentu
// tipe = jennis pembulatan, 1 (ke atas), 0=tergantung, -1 (ke bawah)
function roundMoney($fltAmount, $fltRoundFactor = 100, $type = 1)
{
    $fltResult = 0;
    if (is_numeric($fltAmount) && is_numeric($fltRoundFactor) && ($fltRoundFactor != 0)) {
        if (($fltAmount % $fltRoundFactor) == 0) { // gak perlu dibulatkan
            $fltResult = round($fltAmount);
        } else {
            $fltTmp = floor($fltAmount / $fltRoundFactor);
            $fltFloor = ($fltTmp * $fltRoundFactor);
            if ($type == 1) { // bulatkan ke atas
                $fltResult = $fltFloor + $fltRoundFactor;
            } else if ($type == -1) { // bulatkan ke bawah
                $fltResult = $fltFloor;
            } else {
                // lihat komposisi
                $fltSelisih = $fltAmount - $fltFloor;
                $fltResult = ($fltSelisih < ($fltRoundFactor / 2)) ? $fltFloor : ($fltFloor + $fltRoundFactor);
            }
        }
    }
    return $fltResult;
}// roundMoney
// fungsi untuk mengubah angka ke angka romawi
function getRomans($intNumber)
{
    // array menampung angka
    $arrRomans = [0, "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"]; // sampai 12 dulu
    // cari aja di array, jika gak ada, kembalikan nilai semula
    $strResult = (isset($arrRomans[$intNumber])) ? $arrRomans[$intNumber] : $intNumber;
    return $strResult;
}//getRomans
// fungsi untuk nambahin karakter 0 didepan angka
// intLen = panjang string yang diinginkan
function addPrevZero($strTeks, $intLen)
{
    $strResult = "";
    $intDiff = ($intLen - strlen($strTeks)); // cari selisih
    for ($i = 1; $i <= $intDiff; $i++) {
        $strResult .= "0";
    }
    return $strResult . $strTeks;
}// addPrevZero
// fungsi untuk mengambil data, siapa sih atasan dalam suatu department
// berdasar department dan jabatannya
function getDepartmentLeaderID($db, $strDepartmentCode = "")
{
    $strResult = "";
    $strManCode = getSetting("department_head");
    if ($strManCode != "" && $strDepartmentCode != "") {
        // ambil jabatan dari employee
        $strSQL = "SELECT id FROM hrd_employee WHERE active = 1 AND flag = 0 ";
        $strSQL .= "AND department_code = '$strDepartmentCode' ";
        $strSQL .= "AND position_code = '$strManCode' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            $strResult = $rowDb['id'];
        }
    }
    return $strResult;
} // getUserManager
// fungsi untuk menampilkan header file excel
function headeringExcel($strFileName)
{
    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=$strFileName");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
    header("Pragma: public");
}

// Fungsi terbilang dari : http://daunsalam.net/artikel/terbilang.htm
function terbilang($bilangan)
{
    $kalimat = "";
    $angka = [
        '0',
        '0',
        '0',
        '0',
        '0',
        '0',
        '0',
        '0',
        '0',
        '0',
        '0',
        '0',
        '0',
        '0',
        '0',
        '0'
    ];
    $kata = [
        '',
        'satu',
        'dua',
        'tiga',
        'empat',
        'lima',
        'enam',
        'tujuh',
        'delapan',
        'sembilan'
    ];
    $tingkat = ['', 'ribu', 'juta', 'milyar', 'triliun'];
    $bolMinus = ($bilangan < 0);
    if ($bolMinus) {
        $bilangan = abs($bilangan);
    }
    $panjang_bilangan = strlen($bilangan);
    /* pengujian panjang bilangan */
    if (!is_numeric($bilangan)) {
        return "";
    } else if ($panjang_bilangan > 15) {
        $kalimat = "to large ";
        return $kalimat;
    }
    /* mengambil angka-angka yang ada dalam bilangan,
         dimasukkan ke dalam array */
    for ($i = 1; $i <= $panjang_bilangan; $i++) {
        $angka[$i] = substr($bilangan, -($i), 1);
    }
    $i = 1;
    $j = 0;
    /* mulai proses iterasi terhadap array angka */
    while ($i <= $panjang_bilangan) {
        $subkalimat = "";
        $kata1 = "";
        $kata2 = "";
        $kata3 = "";
        /* untuk ratusan */
        if ($angka[$i + 2] != "0") {
            if ($angka[$i + 2] == "1") {
                $kata1 = "seratus";
            } else {
                $kata1 = $kata[$angka[$i + 2]] . " ratus";
            }
        }
        /* untuk puluhan atau belasan */
        if ($angka[$i + 1] != "0") {
            if ($angka[$i + 1] == "1") {
                if ($angka[$i] == "0") {
                    $kata2 = "sepuluh";
                } elseif ($angka[$i] == "1") {
                    $kata2 = "sebelas";
                } else {
                    $kata2 = $kata[$angka[$i]] . " belas";
                }
            } else {
                $kata2 = $kata[$angka[$i + 1]] . " puluh";
            }
        }
        /* untuk satuan */
        if ($angka[$i] != "0") {
            if ($angka[$i + 1] != "1") {
                $kata3 = $kata[$angka[$i]];
            }
        }
        /* pengujian angka apakah tidak nol semua,
             lalu ditambahkan tingkat */
        if (($angka[$i] != "0") OR ($angka[$i + 1] != "0") OR
            ($angka[$i + 2] != "0")
        ) {
            $subkalimat = "$kata1 $kata2 $kata3 " . $tingkat[$j] . " ";
        }
        /* gabungkan variabe sub kalimat (untuk satu blok 3 angka)
             ke variabel kalimat */
        $kalimat = $subkalimat . $kalimat;
        $i = $i + 3;
        $j = $j + 1;
    }
    /* mengganti satu ribu jadi seribu jika diperlukan */
    if (($angka[5] == "0") AND ($angka[6] == "0")) {
        $kalimat = str_replace("satu ribu", "seribu", $kalimat);
    }
    if ($bolMinus) {
        $kalimat = "minus " . $kalimat;
    }
    return trim($kalimat);
}

// fungsi untuk mengambil kelas CSS dari suatu status dari permintaan / Request
function getCssClass($intStatus = 0)
{
    $strClass = "";
    switch ($intStatus) {
        case 0 :
            $strClass = "bgNewData";
            break;
        case 1 :
            $strClass = "bgCheckedData";
            break;
        case 2 :
            $strClass = "bgApprovedData";
            break;
        case 4 :
            $strClass = "bgDenied";
            break;
        default :
            $strClass = "";
            break;
    }
    return $strClass;
}//getCssClass
// fungsi untuk mengambil kelas CSS dari suatu status dari permintaan / Request
function getStatusRemovalAsset($intStatus = 0)
{
    $strStatus = "";
    switch ($intStatus) {
        case 0 :
            $strStatus = "New";
            break;
        case 1 :
            $strStatus = "Approved";
            break;
        case 2 :
            $strStatus = "being Checked";
            break;
        case 3 :
            $strStatus = "Rejected";
            break;
    }
    return $strStatus;
}//getCssClass
function totalDay($db, $strFrom, $strThru)
{
    global $arrDay; // untuk menampung totalday, datefrom-datethru, agar menghemat pencarian, jika sudah pernah ada
    if (isset($arrDay["$strFrom.$strThru"])) {
        return $arrDay["$strFrom.$strThru"];
    } // langsung dibalikin
    if ($strFrom == "" || $strThru == "") {
        return 0;
    }
    $intResult = 0;
    $strFrom = pgDateFormat($strFrom, "Y-m-d");
    $strThru = pgDateFormat($strThru, "Y-m-d");
    if ($strFrom > $strThru) {
        $intResult = 0;
    } else if ($strFrom == $strThru) {
        $intResult = 1;
    } else {
        // ubah format dalam timestamp
        list($intYear, $intMonth, $intDay) = explode("-", $strFrom);
        $tsFrom = mktime(0, 0, 0, (int)$intMonth, (int)$intDay, (int)$intYear);
        $dtFrom = getdate($tsFrom);
        list($intYear, $intMonth, $intDay) = explode("-", $strThru);
        $tsThru = mktime(0, 0, 0, (int)$intMonth, (int)$intDay, (int)$intYear);
        $dtThru = getdate($tsThru);
        $intSelisih = ($tsThru - $tsFrom) / 86400; // selisih dalam hari
        $intSelisihMinggu = floor($intSelisih / 7); // selisih dalam minggu
        $intResult = $intSelisih + 1; // karena hari awal dihitung juga
        $intModMinggu = ($intSelisih % 7);
        // cari data pengganti libur
        $strSQL = "SElECT COUNT(id) AS total FROM hrd_calendar ";
        $strSQL .= "WHERE holiday BETWEEN '$strFrom' AND '$strThru' ";
        $strSQL .= "AND status = 'f' "; // cari yang libur saja
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if (is_numeric($rowDb['total'])) {
                $intResult += $rowDb['total'];
            }
        }
    }
    return $intResult;
} //etotalDay
//fungsi cek apakah isi field tertentu sudah ada di suatu tabel
//return 1 jika sudah ada, 0 jika tidak ditemukan datanya
//DW
function cek_field($db, $field, $table, $isi, $kriteria = "")
{
    //inisialisasi
    $bol = 0;
    //cek ada kriteria atau tidak
    if ($kriteria == "") {
        $kriteria = " 1=1 ";
    }
    //cek di database
    $strSQL = "SELECT " . $field . " FROM " . $table . " WHERE " . $field . " = '" . $isi . "' AND $kriteria ";
    $res = $db->execute($strSQL);
    if ($row = $db->fetchrow($res)) {
        $bol = 1;
    }
    return $bol;
}

function isEmployeeHoliday($db, $strDate, $strIDEmployee, $arrShift = null)
{
    if (!isset($arrShift)) {
        $arrShift = getShiftSchedule($db, $strDate, $strDate, $strIDEmployee);
    }
    // 1. cek dari shift schedule
    if (isset($arrShift[$strDate][$strIDEmployee])) {
        $arrTemp = $arrShift[$strDate][$strIDEmployee];
        $strShiftType = $arrTemp['shift_code'];
        if ($arrTemp['shift_off'] != "t") {
            // jika prioritas libur general lebih tinggi dari jadwal shift
            // return isHoliday($strDate);
            // jika prioritas jadwal shift lebih tinggi dari pada libur general
            return false;
        } else {
            return true;
        }
    } // 2. cek dari work schedule
    else {
        $arrWorkSchedule = getWorkSchedule($db, $strDate, $strIDEmployee);
        if (isset($arrWorkSchedule[$strIDEmployee])) {
            if ($arrWorkSchedule[$strIDEmployee]['day_off'] != "t") {
                // cek hari libur
                return isHoliday($strDate);
                // karena prioritas libur general lebih tinggi dari setting work schedule employee
                // jika prioritas setting work schedule employee lebih tinggi dari pada libur general, langsung panggil nilai false
            } else {
                return true;
            }
        } else {
            return isHoliday($strDate);
        }
    }
}

function scopeData(
    &$strDataEmployee,
    &$strDataSubSection,
    &$strDataSection,
    &$strDataSubDepartment,
    &$strDataDepartment,
    &$strDataDivision,
    $strDataUserRole,
    $arrUserInfo
) {
    $bolScoped = true;
    if ($strDataUserRole == ROLE_SUPERVISOR) {
        if ($arrUserInfo['division_code'] != "") {
            $strDataDivision = $arrUserInfo['division_code'];
        }
        if ($arrUserInfo['department_code'] != "") {
            $strDataDepartment = $arrUserInfo['department_code'];
        }
        if ($arrUserInfo['sub_department_code'] != "") {
            $strDataSubDepartment = $arrUserInfo['sub_department_code'];
        }
        if ($arrUserInfo['section_code'] != "") {
            $strDataSection = $arrUserInfo['section_code'];
        }
        if ($arrUserInfo['sub_section_code'] != "") {
            $strDataSubSection = $arrUserInfo['sub_section_code'];
        }
    } else if ($strDataUserRole == ROLE_EMPLOYEE) {
        $strDataEmployee = $arrUserInfo['employee_id'];
        if ($arrUserInfo['division_code'] != "") {
            $strDataDivision = $arrUserInfo['division_code'];
        }
        if ($arrUserInfo['department_code'] != "") {
            $strDataDepartment = $arrUserInfo['department_code'];
        }
        if ($arrUserInfo['sub_department_code'] != "") {
            $strDataSubDepartment = $arrUserInfo['sub_department_code'];
        }
        if ($arrUserInfo['section_code'] != "") {
            $strDataSection = $arrUserInfo['section_code'];
        }
        if ($arrUserInfo['sub_section_code'] != "") {
            $strDataSubSection = $arrUserInfo['sub_section_code'];
        }
    } else {
        $bolScoped = false;
    }
    return $bolScoped;
}

//fungsi untuk membatasi data entry untuk page general (diluar salary, compensation, dan benefit)
//hanya role admin dan super yang dapat melakukan entry data untuk seluruh karyawan, selebihnya hanya entri data sendiri
//kecuali supervisor, bisa mengedit data bawahannya
/*function scopeGeneralDataEntry(&$strDataEmployee, $strDataUserRole, $arrUserInfo, $bolIsNew = false)
  {
    $bolScoped = true;
    if ($strDataUserRole == ROLE_EMPLOYEE || ($strDataUserRole == ROLE_SUPERVISOR && $bolIsNew))
      $strDataEmployee = $arrUserInfo['employee_id'];
    else if ($strDataUserRole == ROLE_ADMIN || $strDataUserRole == ROLE_SUPER)
    {
      if ($strDataEmployee == "") $strDataEmployee = $arrUserInfo['employee_id'];
      $bolScoped = false;
    }
    return $bolScoped;
  }  //fungsi untuk membatasi data entry untuk page general (diluar salary, compensation, dan benefit)
  */
//edit by Ajeng
function scopeGeneralDataEntry(&$strDataEmployee, $strDataUserRole, $arrUserInfo, $bolIsNew = false)
{
    $bolScoped = true;
    if ($strDataUserRole == ROLE_EMPLOYEE)// || ($strDataUserRole == ROLE_SUPERVISOR && $bolIsNew))
    {
        $strDataEmployee = $arrUserInfo['employee_id'];
    } else if ($strDataUserRole == ROLE_ADMIN || $strDataUserRole == ROLE_SUPER || $strDataUserRole == ROLE_SUPERVISOR) {
        if ($strDataEmployee == "") {
            $strDataEmployee = $arrUserInfo['employee_id'];
        }
        $bolScoped = false;
    }
    return $bolScoped;
}

//hanya role admin dan super yang dapat melakukan entry data untuk seluruh karyawan, selebihnya hanya entri data sendiri
function scopeCBDataEntry(&$strDataEmployee, $strDataUserRole, $arrUserInfo)
{
    $bolScoped = true;
    if ($strDataUserRole < ROLE_SUPERVISOR) {
        $strDataEmployee = $arrUserInfo['employee_id'];
    } else {
        $bolScoped = false;
    }
    return $bolScoped;
}

function getRequestStatusClass($intStatus)
{
    switch ($intStatus) {
        case 0 :
            return "class=bgNewData";
        case 1 :
            return "class=bgCheckedData";
        case 2 :
            return "class=bgApprovedData";
        case 4 :
            return "class=bgDenied";
        default :
            return "";
    }
}

function updateNote(
    $db,
    $strTable,
    $strDataID,
    $strModifier,
    $strNote1,
    $strNote2,
    $strDataStatus,
    $intActivity,
    $intModule = MODULE_OTHER
) {
    $strSQL = "UPDATE $strTable ";
    $strSQL .= "SET modified_by = '$strModifier', ";
    $strSQL .= "note = '$strNote2' ";
    $strSQL .= "WHERE id = '$strDataID' ";
    $resExec = $db->execute($strSQL);
    writeLog($intActivity, $intModule, $strNote1 . $strNote2, $strDataStatus);
}

function accessDenied($strReferer)
{
    return "<div align=center style=\"font-family:verdana;color:red\"><br><br><br>" . getWords(
        'access denied'
    ) . " | <a href=\"" . $strReferer . "\">Back to previous page</a></div>";
}

// remove by key:
function array_remove_key()
{
    $args = func_get_args();
    return array_diff_key($args[0], array_flip(array_slice($args, 1)));
}

// remove by value:
function array_remove_value()
{
    $args = func_get_args();
    return array_diff($args[0], array_slice($args, 1));
}

function getCompanyCode()
{
    global $arrUserInfo;
    return $arrUserInfo['company_code'];
}

// fungsi untuk get post atau session dan set session
// $strPostSufix = nama object (kata setelah 'data', misalnya untuk dataEmployee => 'Employee', untuk dataDivision => 'Division'),
// $defaultValue = nilai default jika tidak ada nilai yang di-post atau tidak ada nilai pada session,
// $priorityValue = nilai yang ditentukan dan digunakan walaupun ada nilai yang di-post atau ada nilai session (misalnya restriksi),
// $strSessionSufix = nama variable session (perlakuannya sama dengan strPostSufix, jika nilainya null, maka disamakan dengan strPostSufix,
// $bolSetSession = set nilai session setelah mendapatkan nilai yang akan digunakan,
// $strPostPrefix = kata depan pada object misalnya "data",
// $strSessionPrefix = kata depan pada var session misalnya "sessiondata"
function getInitialValue(
    $strPostSufix,
    $defaultValue = "",
    $priorityValue = "",
    $strSessionSufix = null,
    $bolSetSession = true,
    $strPostPrefix = "data",
    $strSessionPrefix = "sessiondata",
    $bolAlertCall = false
) {
    global $_SESSION;
    global $_POST;
    if ($strSessionSufix == null) {
        $strSessionSufix = $strPostSufix;
    }
    if ($bolAlertCall) {
        $value = $priorityValue;
    } else {
        //set nilai: urutan prioritas: priority, post, session, default
        if ($priorityValue != "") {
            $value = $priorityValue;
        } else {
            if (isset($_POST[$strPostPrefix . $strPostSufix])) {
                $value = $_REQUEST[$strPostPrefix . $strPostSufix];
            } else {
                $value = (isset($_SESSION[$strSessionPrefix . $strSessionSufix])) ? $_SESSION[$strSessionPrefix . $strSessionSufix] : $defaultValue;
            }
        }
    }
    //echo "<br>".$strPostSufix."|".$_SESSION[$strSessionPrefix.$strSessionSufix]."|".$value;
    if ($bolSetSession) {
        $_SESSION[$strSessionPrefix . $strSessionSufix] = $value;
    }
    return $value;
}

//fungsi yang memanggil getInitialValue dengan menyelipkan nilai true pada $bolAlertCall
//menyiapkan data initial untuk form yang diload dari link notifikasi home page
//parameter bolAlertCall diset true (fix)
//default untuk bolSetSession diubah menjadi false
function getInitialValueAlert(
    $strPostSufix,
    $defaultValue = "",
    $priorityValue = "",
    $strSessionSufix = null,
    $bolSetSession = false,
    $strPostPrefix = "data",
    $strSessionPrefix = "sessiondata"
) {
    getInitialValue(
        $strPostSufix,
        $defaultValue,
        $priorityValue,
        $strSessionSufix,
        $bolSetSession,
        $strPostPrefix,
        $strSessionPrefix,
        true
    );
}

//belum lengkap
function buidCriteria($arrData, $strKriteriaCompany, $strPrefix = "data")
{
    foreach ($arrData as $strKey => $strValue) {
        if ($strValue != "") {
            $strKriteria .= "AND " . _underscore($strKey, $strPrefix) . " = '" . $strValue . "'";
        }
    }
}

function _underscore($strKey, $strPrefix)
{
    strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', preg_replace('/$strPrefix/i', '', $strKey)));
}

function generateSelectDay(
    $name,
    $default = "",
    $attribute = "",
    $event = "",
    $hasEmptyData = false,
    $emptyData = null,
    $className = null
) {
    if (empty($className)) {
        $className = 'class="form-control select2 select-day"';
    } else {
        $className = '';
    }
    $strResult = "<select name=\"" . $name . "\" id=\"" . $name . "\" " . $attribute . " " . $event . " " . $className . ">\n";
    if ($hasEmptyData && $emptyData !== null) {
        $strResult .= "  <option value=\"\">" . $emptyData . "</option>\n";
    }
    for ($i = 1; $i <= 31; $i++) {
        if ($i == $default) {
            $strResult .= "  <option value=\"" . $i . "\" selected>" . $i . "</option>\n";
        } else {
            $strResult .= "  <option value=\"" . $i . "\">" . $i . "</option>\n";
        }
    }
    $strResult .= "</select>";
    return $strResult;
}

/*
  ======================================================================================================================
  ----------------------------------------------------------------------------------------------------------------------
  DESK   : Fungsi-Fungsi dibawah  ini adlaah fungsi yang ditambahkaan untuk keperluar penambahan recruitment modul
  ----------------------------------------------------------------------------------------------------------------------
  ======================================================================================================================
  */
//======= fungsi untuk menambahkan 0 di depan angka sampai karakter berukuran tertentu ===============================
function leadingZero($num, $leading)
{
    return sprintf("%0" . $leading . "d", $num);
}

//============== END ================================================================================================
//====================== fungsi untuk mengecek apakah user berhak mengakses data band tertentu ======================
function isBandAccess($strBand)
{
    if (!$_SESSION['sessionIsSpecifyBand']) {
        return true;
    }
    if (in_array($strBand, $_SESSION['sessionBandList'])) {
        return true;
    } else {
        return false;
    }
}

//============================END=====================================================================================
//============= fungsi untuk mengirim daftar band yang boleh diakses oleh user,=======================================
// dalam string (untuk QUERY IN (xxxx))
function getBandAccessCriteria()
{
    $strResult = "";
    if (isset ($_SESSION['sessionBandList'])) {
        foreach ($_SESSION['sessionBandList'] AS $strBand) {
            if ($strResult != "") {
                $strResult .= ", ";
            }
            $strResult .= "'$strBand'";
        }
    } else {
        $strResult = "all"; // artinya semua berhak
    }
    return $strResult;
}

//======================= END ==========================================================================================
function callChangeStatus()
{
    global $_REQUEST;
    global $db;
    global $intStatus;
    if (isset($_REQUEST['btnChecked'])) {
        $intStatus = REQUEST_STATUS_CHECKED;
    } else if (isset($_REQUEST['btnApproved'])) {
        $intStatus = REQUEST_STATUS_APPROVED;
    } else if (isset($_REQUEST['btnDenied'])) {
        $intStatus = REQUEST_STATUS_DENIED;
    } else if (isset($_REQUEST['btnAcknowledged'])) {
        $intStatus = REQUEST_STATUS_ACKNOWLEDGED;
    } else if (isset($_REQUEST['btnClosed'])) {
        $intStatus = REQUEST_STATUS_CLOSED;
    }
    changeStatus($db, $intStatus);
}

function getStatusUpdateString($intStatus)
{
    if ($intStatus == REQUEST_STATUS_CHECKED) {
        $strUpdate = "checked_by = '" . $_SESSION['sessionUserID'] . "', checked_time = now(), ";
    } else if ($intStatus == REQUEST_STATUS_APPROVED) {
        $strUpdate = "approved_by = '" . $_SESSION['sessionUserID'] . "', approved_time = now(), ";
    } else if ($intStatus == REQUEST_STATUS_DENIED) {
        $strUpdate = "denied_by = '" . $_SESSION['sessionUserID'] . "', denied_time = now(), ";
    } else if ($intStatus == REQUEST_STATUS_ACKNOWLEDGED) {
        $strUpdate = "acknowledged_by = '" . $_SESSION['sessionUserID'] . "', acknowledged_time = now(), ";
    } else if ($intStatus == REQUEST_STATUS_CLOSED) {
        $strUpdate = "closed_by = '" . $_SESSION['sessionUserID'] . "', closed_time = now(), ";
    }
    return $strUpdate;
}

function getEditFile($strFileName)
{
    return str_replace("list", "edit", $strFileName);
}

function isProcessable($intOldStatus, $intNewStatus)
{
    // Apakah statusnya naik?
    $bolProcess1 = ($intOldStatus < $intNewStatus && $intOldStatus != REQUEST_STATUS_DENIED) || ($intOldStatus != REQUEST_STATUS_APPROVED && $intNewStatus == REQUEST_STATUS_DENIED);
    // Apakah statusnya tidak loncat?
    $bolProcess2 = ($intNewStatus - $intOldStatus >= 1 || $intNewStatus == REQUEST_STATUS_DENIED);
    if ($bolProcess1 && $bolProcess2) {
        return true;
    } else {
        showNotification();
        return false;
    }
}

function showNotification()
{
    echo "<script>
         alert(\"Status change is missmatched, only change the data status in following order: New-Check-Approved-Acknowledged\");
         </script>";
}

// ============= FUNGSI UNTUK MENGIRIM E-MAIL =======================
function sendMail($to, $subject, $message, $from = null)
{
    $semi_rand = md5(time());
    $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
    $MIME_Headers = "";
    if (!empty($from)) {
        $MIME_Headers .= "From: $from\r\n";
    }
    $MIME_Headers .= "To: $to\r\n" .
        "MIME-Version: 1.0\r\n" .
        "Content-Type: multipart/mixed;\r\n" .
        " boundary=\"{$mime_boundary}\"";
    $MIME_Message = "This is a multi-part message in MIME format.\n\n" .
        "--{$mime_boundary}\n" .
        "Content-Type: text/plain; charset=\"iso-8859-1\"\n" .
        "Content-Transfer-Encoding: QUOTED-PRINTABLE\n\n" .
        $message . "\n\n";
    $MIME_Message .= "--{$mime_boundary}--\n";
    return mail($to, $subject, $MIME_Message, $MIME_Headers);
}

/*
    countAdditionalAbsence
    returns an array: --> Key = id_employee, Value = floor((late_num + early_num)/2)
  */
function countAdditionalAbsence($db, $strDateFrom = "now()", $strDateThru = "now()", $tolerance = 10)
{
    $arrAdditionalAbsence = [];
    $strSQL = "
      SELECT t1.id, t1.employee_id, COUNT(t2.late_duration) AS late_num FROM hrd_employee AS t1
      LEFT JOIN hrd_attendance AS t2 ON t1.id = t2.id_employee
      WHERE
        (attendance_date BETWEEN '$strDateFrom' AND '$strDateThru')
        AND late_duration > $tolerance
      GROUP BY t1.id, t2.id_employee, t1.employee_id
      ORDER BY t1.id
      ";
    $resDb = $db->execute($strSQL);
    //key = id_employee, value = # of late
    while ($rowDb = $db->fetchrow($resDb)) {
        $arrAdditionalAbsence[$rowDb['id']] = $rowDb['late_num'];
    }
    $strSQL = "
      SELECT t1.id, t1.employee_id, COUNT(t2.early_duration) AS early_num FROM hrd_employee AS t1
      LEFT JOIN hrd_attendance AS t2 ON t1.id = t2.id_employee
      WHERE
        (attendance_date BETWEEN '$strDateFrom' AND '$strDateThru')
        AND early_duration > $tolerance
      GROUP BY t1.id, t2.id_employee, t1.employee_id
      ORDER BY t1.id
      ";
    $resDb = $db->execute($strSQL);
    //key = id_employee, value = # of late
    while ($rowDb = $db->fetchrow($resDb)) {
        if (isset($arrAdditionalAbsence[$rowDb['id']])) {
            $arrAdditionalAbsence[$rowDb['id']] += $rowDb['early_num'];
        } else {
            $arrAdditionalAbsence[$rowDb['id']] = $rowDb['early_num'];
        }
    }
    foreach ($arrAdditionalAbsence as &$tempAddAbs) {
        $tempAddAbs = floor($tempAddAbs / 2);
    }
    /*
        SELECT t1.id, t1.employee_id, COUNT(t2.late_duration) AS late_num FROM hrd_employee AS t1
        LEFT JOIN hrd_attendance AS t2 ON t1.id = t2.id_employee
        WHERE
          (attendance_date BETWEEN $strDateFrom AND $strDateThru)
          AND late_duration > $tolerance
        GROUP BY t1.id, t2.id_employee, t1.employee_id
        ORDER BY t1.id

        SELECT t1.id, t1.employee_id, COUNT(t2.early_duration) AS early_num FROM hrd_employee AS t1
        LEFT JOIN hrd_attendance AS t2 ON t1.id = t2.id_employee
        WHERE
          (attendance_date BETWEEN $strDateFrom AND $strDateThru)
          AND early_duration > $tolerance
        GROUP BY t1.id, t2.id_employee, t1.employee_id
        ORDER BY t1.id
      */
    return $arrAdditionalAbsence;
}

// ============= END ================================================
function _debug_array($arr)
{
    echo "<pre>";
    print_r($arr);
    echo "</pre>";
}

/* == Perbaikan Tampilan Devosa Header creator */
function createAccordion($variables = null)
{
    $accordion = '';
    if (!empty($variables)) {
        if (!isset($variables['id'])) {
            $variables['id'] = 'accordion';
        }
        $accordion = '<div class="panel-group" id="' . $variables['id'] . '">';
        if (isset($variables['element']) && count($variables['element'])) {
            for ($i = 0; $i < count($variables['element']); $i++) {
                $element = $variables['element'][$i];
                $accordion .= '
          <div class="panel panel-primary">
              <div class="panel-heading" data-toggle="collapse" data-parent="#' . $variables['id'] . '" data-target="#collapse-' . $variables['id'] . '-' . ($i + 1) . '">
                  <h4 class="panel-title">
                      ' . $element['title'] . '
                  </h4>
              </div>
              <div id="collapse-' . $variables['id'] . '-' . ($i + 1) . '" class="panel-collapse collapse">
                  <div class="panel-body">
                      ' . $element['description'] . '
                  </div>
              </div>
          </div>';
            }
        }
        $accordion .= '</div>';
    }
    return $accordion;
}

function createHorizontalList($variables = null)
{
    if (!empty($variables)) {
        if (isset($variables['element']) && count($variables['element'])) {
            $list = '<dl class="dl-horizontal">';
            for ($i = 0; $i < count($variables['element']); $i++) {
                $element = $variables['element'][$i];
                $list .= '<dt>' . $element['title'] . '</dt>';
                $list .= '<dd>' . $element['description'] . '</dd>';
            }
            $list .= '</dl>';
        }
    }
    return $list;
}

function pageHeader($icon, $title, $desc)
{
    $pageHeader = '<div class="header">
      <div class="col-md-12">
        <h3 class="header-title"><img src="' . $icon . '" border="0" width="30" />&nbsp;&nbsp;' . $title . '</h3>
        <p class="header-info">' . $desc . '</p>
      </div>
    </div>';
    return $pageHeader;
}

function pageSubMenu($submenu)
{
    $subMenuView = '<ul class="nav nav-tabs">';
    for ($i = 0; $i < count($submenu); $i++) {
        $submenuData = $submenu[$i];
        if ($submenuData['active']) {
            $subMenuView .= '<li class="active"><a href="#"><strong>' . $submenuData['title'] . '</strong></a></li>';
        } else {
            $subMenuView .= '<li><a href="' . $submenuData['link'] . '">' . $submenuData['title'] . '</a></li>';
        }
    }
    $subMenuView .= '</ul>';
    return $subMenuView;
}

function employeeDataSubmenu($activePage)
{
    $strWordsSearchEmployee = getWords("search employee");
    $strWordsSimpleResume = getWords("simple resume");
    $strWordsReport = getWords("report");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsSearchEmployee) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'employee_search.php';
    }
    $submenuData['title'] = $strWordsSearchEmployee;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsSimpleResume) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'employee_resume_all.php';
    }
    $submenuData['title'] = $strWordsSimpleResume;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsReport) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'employee_report.php';
    }
    $submenuData['title'] = $strWordsReport;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function employeeEditSubmenu($activePage)
{
    $strWordsPrimaryInformation = getWords("primary information ");
    $strWordsFamilyData = getWords("family data");
    $strWordsEducationData = getWords("education data");
    $strWordsTrainingData = getWords("training data");
    $strWordsWorkExperiences = getWords("work experiences");
    $strWordsResume = getWords("resume");
    $strWordsStatistik = getWords("statistik");
    $strWordsDokumen = getWords("dokumen");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsPrimaryInformation) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = "javascript:goUrl('employee_edit')";
    }
    $submenuData['title'] = $strWordsPrimaryInformation;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsFamilyData) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = "javascript:goUrl('employee_edit_family')";
    }
    $submenuData['title'] = $strWordsFamilyData;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsEducationData) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = "javascript:goUrl('employee_edit_education')";
    }
    $submenuData['title'] = $strWordsEducationData;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsTrainingData) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = "javascript:goUrl('employee_edit_training')";
    }
    $submenuData['title'] = $strWordsTrainingData;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsWorkExperiences) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = "javascript:goUrl('employee_edit_work')";
    }
    $submenuData['title'] = $strWordsWorkExperiences;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsResume) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = "javascript:goUrl('employee_resume')";
    }
    $submenuData['title'] = $strWordsResume;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsStatistik) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = "javascript:goUrl('employee_statistic')";
    }
    $submenuData['title'] = $strWordsStatistik;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsDokumen) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = "javascript:goUrl('employee_document')";
    }
    $submenuData['title'] = $strWordsDokumen;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function employeeMutationSubmenu($activePage)
{
    $strWordsProposalEntry = getWords("proposal entry");
    $strWordsProposalList = getWords("proposal list");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsProposalEntry) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'mutation_edit.php';
    }
    $submenuData['title'] = $strWordsProposalEntry;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsProposalList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'mutation_list.php';
    }
    $submenuData['title'] = $strWordsProposalList;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function employeeResignSubmenu($activePage)
{
    $strWordsResignProposalEntry = getWords("severance employee entry");
    $strWordsResignProposalList = getWords("severance employee list");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsResignProposalEntry) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'resign_edit.php';
    }
    $submenuData['title'] = $strWordsResignProposalEntry;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsResignProposalList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'resign_list.php';
    }
    $submenuData['title'] = $strWordsResignProposalList;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function dataShiftTypeSubmenu($activePage)
{
    $strWordsScheduleType = getWords("schedule type");
    $strWordsWorkSchedule = getWords("work schedule");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsScheduleType) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'data_shift_type.php';
    }
    $submenuData['title'] = $strWordsScheduleType;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsWorkSchedule) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'shift_schedule.php';
    }
    $submenuData['title'] = $strWordsWorkSchedule;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function organizationChartSubmenu($activePage)
{
    $strWordsInputData = getWords("input data");
    $strWordsDepartment = getWords("chart / tree");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsInputData) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'data_department.php';
    }
    $submenuData['title'] = $strWordsInputData;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsDepartment) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'data_department_tree.php';
    }
    $submenuData['title'] = $strWordsDepartment;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function organizationFunctionalChartSubmenu($activePage)
{
    $strWordsInputData = getWords("input data");
    $strWordsDepartment = getWords("chart / tree");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsDepartment) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'data_functional_tree.php';
    }
    $submenuData['title'] = $strWordsDepartment;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function medicalTypeSubmenu($activePage)
{
    $strWordsTreatmentTypeSetting = getWords("treatment type setting");
    $strWordsQuotaSetting = getWords("quota setting");
    $strWordsExtendedQuota = getWords("extended quota");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsTreatmentTypeSetting) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'data_medical_type.php';
    }
    $submenuData['title'] = $strWordsTreatmentTypeSetting;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsQuotaSetting) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'data_medical_platform.php';
    }
    $submenuData['title'] = $strWordsQuotaSetting;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsExtendedQuota) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'medical_additional_quota.php';
    }
    $submenuData['title'] = $strWordsExtendedQuota;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function medicalQuotaSubmenu($activePage)
{
    $strWordsMedicalData = getWords("medical data");
    $strWordsEmployeeQuotaList = getWords("employee quota list");
    $strWordsInputMedicalClaim = getWords("input claim");
    $strWordsMedicalClaimList = getWords("claim list");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsMedicalData) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'medical_quota.php';
    }
    $submenuData['title'] = $strWordsMedicalData;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsEmployeeQuotaList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'medical_list.php';
    }
    $submenuData['title'] = $strWordsEmployeeQuotaList;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsInputMedicalClaim) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'medical_edit.php';
    }
    $submenuData['title'] = $strWordsInputMedicalClaim;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsMedicalClaimList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'medical_report.php';
    }
    $submenuData['title'] = $strWordsMedicalClaimList;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function workreportSubmenu($activePage)
{
    $strWordsStaticticalReport = getWords("statistical analisys");
    $strWordsActivityReport = getWords("activity report");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsStaticticalReport) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'workforce_report_new.php';
    }
    $submenuData['title'] = $strWordsStaticticalReport;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsActivityReport) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'workhabit_report_new.php';
    }
    $submenuData['title'] = $strWordsActivityReport;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function salarySetSubmenu($activePage)
{
    $strWordsGeneralSetting = getWords("general setting");
    $strWordsSalarySet = getWords("salary set");
    $strWordsEmployeeAllowance = getWords("employee allowance");
    $strWordsEmployeeDeduction = getWords("employee deduction");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsGeneralSetting) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'salary_basic.php';
    }
    $submenuData['title'] = $strWordsGeneralSetting;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsSalarySet) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'salary_set.php';
    }
    $submenuData['title'] = $strWordsSalarySet;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsEmployeeAllowance) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'salary_basic.php';
    }
    $submenuData['title'] = $strWordsEmployeeAllowance;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsEmployeeDeduction) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'salary_deduction.php';
    }
    $submenuData['title'] = $strWordsEmployeeDeduction;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function attendanceSubMenu($activePage)
{
    $idgrup = $_SESSION['sessionUserRole'];
    $strWordsEntryAttendance = getWords("entry attendance");
    $strWordsImportAttendance = getWords("import attendance");
    $strWordsAttendanceList = getWords("attendance list");
    $strWordsAttendanceReport = getWords("attendance report");
    $submenu = [];
    if ($idgrup == ROLE_ADMIN or $idgrup == ROLE_SUPER) {
        $submenuData = [];
        if ($activePage == $strWordsImportAttendance) {
            $submenuData['active'] = true;
        } else {
            $submenuData['active'] = false;
            $submenuData['link'] = 'attendance_import.php';
        }
        $submenuData['title'] = $strWordsImportAttendance;
        $submenu[] = $submenuData;
        $submenuData = [];
        if ($activePage == $strWordsEntryAttendance) {
            $submenuData['active'] = true;
        } else {
            $submenuData['active'] = false;
            $submenuData['link'] = 'attendance_edit_by_employee.php';
        }
        $submenuData['title'] = $strWordsEntryAttendance;
        $submenu[] = $submenuData;
    }
    $submenuData = [];
    if ($activePage == $strWordsAttendanceList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'attendance_list.php';
    }
    $submenuData['title'] = $strWordsAttendanceList;
    $submenu[] = $submenuData;
    if ($idgrup == ROLE_ADMIN or $idgrup == ROLE_SUPER) {
        $submenuData = [];
        if ($activePage == $strWordsAttendanceReport) {
            $submenuData['active'] = true;
        } else {
            $submenuData['active'] = false;
            $submenuData['link'] = 'attendance_report.php';
        }
        $submenuData['title'] = $strWordsAttendanceReport;
        $submenu[] = $submenuData;
    }
    return pageSubMenu($submenu);
}

function dataAbsenceSubmenu($activePage)
{
    $strWordsEntryAbsence = getWords("entry absence");
    $strWordsAbsenceList = getWords("absence list");
    $strWordsEntryPartialAbsence = getWords("entry partial absence");
    $strWordsPartialAbsenceList = getWords("partial absence list");
    $strWordsAnnualLeave = getWords("annual leave");
    $strWordsAbsenceSlip = getWords("absence slip");
    $strWordsAbsenceApproval = getWords("absence approval");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsEntryAbsence) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'absence_edit.php';
    }
    $submenuData['title'] = $strWordsEntryAbsence;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsAbsenceList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'absence_list.php';
    }
    $submenuData['title'] = $strWordsAbsenceList;
    /*

      $submenu[] = $submenuData;
      $submenuData = array();
      if ($activePage == $strWordsEntryPartialAbsence){
        $submenuData['active'] = true;
      }else{
        $submenuData['active'] = false;
        $submenuData['link'] = 'absence_partial_edit.php';
      }
      $submenuData['title'] = $strWordsEntryPartialAbsence;

      $submenu[] = $submenuData;
      $submenuData = array();
      if ($activePage == $strWordsPartialAbsenceList){
        $submenuData['active'] = true;
      }else{
        $submenuData['active'] = false;
        $submenuData['link'] = 'absence_partial_list.php';
      }
      $submenuData['title'] = $strWordsPartialAbsenceList;
  */
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsAnnualLeave) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'leave_annual.php';
    }
    $submenuData['title'] = $strWordsAnnualLeave;
    $submenu[] = $submenuData;
    /*
      $submenuData = array();
      if ($activePage == $strWordsAbsenceSlip){
        $submenuData['active'] = true;
      }else{
        $submenuData['active'] = false;
        $submenuData['link'] = 'absence_slip.php';
      }
      $submenuData['title'] = $strWordsAbsenceSlip;
      $submenu[] = $submenuData;
      */
    return pageSubMenu($submenu);
}

function overtimeSubmenu($activePage)
{
    $strWordsDataEntry = getWords("data entry");
    $strWordsOvertimeList = getWords("overtime list");
    //$strWordsHolidayOTApproval   = getWords("holiday OT approval");
    //$strWordsWorkdayOTApproval   = getWords("workday OT approval");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsDataEntry) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'overtime_application_edit.php';
    }
    $submenuData['title'] = $strWordsDataEntry;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsOvertimeList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'overtime_application_list.php';
    }
    $submenuData['title'] = $strWordsOvertimeList;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function recruitmentSubMenu($activePage)
{
    $strWordsDataEntry = getWords("data entry");
    $strWordsMRFList = getWords("list of mrf");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsDataEntry) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'recruitment_edit.php';
    }
    $submenuData['title'] = $strWordsDataEntry;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsMRFList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'recruitment_list.php';
    }
    $submenuData['title'] = $strWordsMRFList;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function recruitmentProcessSubMenu($activePage)
{
    $strWordsRecruitmentProcessList = getWords("recruitment process list");
    $strWordsRecruitmentProcessReport = getWords("recruitment process report");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsRecruitmentProcessList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'recruitment_process_list.php';
    }
    $submenuData['title'] = $strWordsRecruitmentProcessList;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsRecruitmentProcessReport) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'recruitment_process_report.php';
    }
    $submenuData['title'] = $strWordsRecruitmentProcessReport;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function loanSubMenu($activePage)
{
    $strWordsDataEntry = getWords("data entry");
    $strWordsLoanList = getWords("loan list");
    $strWordsLoanType = getWords("loan type");
    $strWordsLoanPurpose = getWords("loan purpose");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsDataEntry) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'loan_edit.php';
    }
    $submenuData['title'] = $strWordsDataEntry;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsLoanList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'loan_list.php';
    }
    $submenuData['title'] = $strWordsLoanList;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsLoanType) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'loan_type.php';
    }
    $submenuData['title'] = $strWordsLoanType;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsLoanPurpose) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'loan_purpose.php';
    }
    $submenuData['title'] = $strWordsLoanPurpose;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function salaryReportSubMenu($activePage)
{
    $strWordsSalaryReport = getWords("salary report");
    $strWordsSalaryReportCostCenter = getWords("salary report cost center");
    $strWordsSalaryReportCumulative = getWords("salary report cumulative");
    $strWordsTaxReport = getWords("tax report");
    $strWordsBPJSKT = getWords("BPJS ketenagakerjaan report");
    $strWordsBPJSKS = getWords("BPJS kesehatan report");
    $strWordsOvertimeReport = getWords("overtime report");
    $strWordsESPTReport = getWords("E-SPT report");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsSalaryReport) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'salary_report.php';
    }
    $submenuData['title'] = $strWordsSalaryReport;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsSalaryReportCostCenter) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'salary_report_cost_center.php';
    }
    $submenuData['title'] = $strWordsSalaryReportCostCenter;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsSalaryReportCumulative) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'salary_report_cumulative.php';
    }
    $submenuData['title'] = $strWordsSalaryReportCumulative;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsTaxReport) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'salary_report_tax.php';
    }
    $submenuData['title'] = $strWordsTaxReport;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsBPJSKT) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'salary_report_jamsostek.php';
    }
    $submenuData['title'] = $strWordsBPJSKT;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsBPJSKS) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'salary_report_bpjs.php';
    }
    $submenuData['title'] = $strWordsBPJSKS;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsOvertimeReport) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'salary_report_overtime.php';
    }
    $submenuData['title'] = $strWordsOvertimeReport;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsESPTReport) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'salary_report_espt.php';
    }
    $submenuData['title'] = $strWordsESPTReport;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function warningSubMenu($activePage)
{
    $strWordsWarningEntry = getWords("warning entry");
    $strWordsWarningList = getWords("warning list");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsWarningEntry) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'warning_edit.php';
    }
    $submenuData['title'] = $strWordsWarningEntry;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsWarningList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'warning_list.php';
    }
    $submenuData['title'] = $strWordsWarningList;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function trainingPlanSubmenu($activePage)
{
    $strWordsInputTrainingPlan = getWords("input training template");
    $strWordsTrainingPlanList = getWords("training template list");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsInputTrainingPlan) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'training_plan_edit.php';
    }
    $submenuData['title'] = $strWordsInputTrainingPlan;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsTrainingPlanList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'training_plan_list.php';
    }
    $submenuData['title'] = $strWordsTrainingPlanList;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function rewardSubMenu($activePage)
{
    $strWordsRewardEntry = getWords('reward data entry');
    $strWordsRewardList = getWords('reward list');
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsRewardEntry) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'reward_edit.php';
    }
    $submenuData['title'] = $strWordsRewardEntry;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsRewardList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'reward_list.php';
    }
    $submenuData['title'] = $strWordsRewardList;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function trainingConfigSubMenu($activePage)
{
    $strWordsTrainingType = getWords('training type data');
    $strWordsTrainingInstitution = getWords('training institution data');
    $strWordsTrainingInstructor = getWords('training instructor data');
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsTrainingType) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'data_training_type.php';
    }
    $submenuData['title'] = $strWordsTrainingType;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsTrainingInstitution) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'data_training_vendor.php';
    }
    $submenuData['title'] = $strWordsTrainingInstitution;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsTrainingInstructor) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'data_training_instructor.php';
    }
    $submenuData['title'] = $strWordsTrainingInstructor;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

//uddin 20160106
// untuk ESS WAL
function getfunctionalrecursif($db, $functionalCode, $cekempId, $Empkriteria = '', $n = 0)
{
    if ($n > 1000) {
        return "";
    } else {
        $n++;
        $ReturnFunctionalcode = "";
        if ($cekempId == "") {
            $strSQL2 = "SELECT t1.*,t2.employee_id FROM hrd_functional as t1
                      LEFT JOIN  (select * from hrd_employee " . $Empkriteria . ") as t2 ON t1.functional_code=t2.functional_code
                      WHERE t1.head_code='" . $functionalCode . "'";
            $resDb2 = $db->execute($strSQL2);
            while ($rowDb2 = $db->fetchrow($resDb2)) {
                //echo $rowDb2['functional_code']."recursif $n <br/>";
                $tempRecursif = getfunctionalrecursif(
                    $db,
                    $rowDb2['functional_code'],
                    $rowDb2['employee_id'],
                    $Empkriteria,
                    $n
                );
                $ReturnFunctionalcode .= ",'" . $rowDb2['functional_code'] . "'" . $tempRecursif;
            }
        }
        return $ReturnFunctionalcode;
    }
}

//fungsi untuk wal
function getDateFormat($strDate)
{
    $splitDate = explode('-', $strDate);
    $strDate = $splitDate[1] . '/' . $splitDate[2] . '/' . $splitDate[0];
    return $strDate;
}

function getHeadEmployeeData($db = null, $strIDEmployee = null)
{
    if (!empty($db) && !empty($strIDEmployee) && $db->connect()) {
        $funcCode = getEmployeeFunctionalCode($db, $strIDEmployee);
        $headData = null;
        $loopCount = 0;
        while (empty($headData) && $loopCount < 50) {
            $strSQL = "SELECT head_code FROM hrd_functional WHERE functional_code='$funcCode'";
            $resDb = $db->execute($strSQL);
            while ($rowDb = $db->fetchrow($resDb)) {
                $headCode = $rowDb['head_code'];
                if (!empty($headCode)) {
                    $strSQL2 = "SELECT id, employee_id, employee_name, email FROM hrd_employee WHERE functional_code='$headCode' and active = 1";
                    $resDb2 = $db->execute($strSQL2);
                    while ($rowDb2 = $db->fetchrow($resDb2)) {
                        $headData = $rowDb2;
                    }
                }
                if (empty($headData)) {
                    $funcCode = $headCode;
                }
            }
            $loopCount++;
        }
    }
    return $headData;
}

function getEmployeeFunctionalCode($db = null, $strIDEmployee = null)
{
    $employeeFunctionCode = null;
    if (!empty($db) && !empty($strIDEmployee) && $db->connect()) {
        $strSQL = "SELECT functional_code FROM hrd_employee WHERE id=$strIDEmployee";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $employeeFunctionCode = $rowDb['functional_code'];
        }
    }
    return $employeeFunctionCode;
}

/* added email function */
function getSubjectEmail($status, $module, $id)
{
    global $employeeID;
    global $type;
    global $module_permission;
    $employeeID = $id;
    $type = $status;
    $module_permission = $module;
    $strSubject = "";
    if ($status == 1) {
        $strSubject = "[$module ID: $id]New Checked $module Entry";
    } else if ($status == 6) {
        $strSubject = "[$module ID: $id]New Approved 2 $module Entry";
    } else if ($status == 2) {
        $strSubject = "[$module ID: $id]New Approved 1 $module Entry";
    } else if ($status == -1) {
        $strSubject = "[$module ID: $id]New Denied $module Entry";
    } else { //use in entry form
        $strSubject = "[$module ID: $id]New $module Entry Created";
    }
    return $strSubject;
}

function getBodyEmail($status, $module, $string, $strModifyBy = null)
{
    global $db;
    $strBody = "";
    $strModifyBy = empty($strModifyBy) ? "INVOSA" : $strModifyBy;
    if ($status == 1) {
        $strBody = "This $module entry has been checked by $strModifyBy on " . date("Y-m-d") . "\n\n";
        $strBody .= "$string";
    } else if ($status == 2) {
        $strBody = "This $module entry has been approved 1 by $strModifyBy on " . date("Y-m-d") . "\n\n";
        $strBody .= "$string";
    } else if ($status == 6) {
        $strBody = "This $module entry has been approved 2 by $strModifyBy on " . date("Y-m-d") . "\n\n";
        $strBody .= "$string";
    } else if ($status == -1) {
        $strBody = "This $module entry has been denied by $strModifyBy on " . date("Y-m-d") . "\n\n";
        $strBody .= "$string";
    } else {
        $strBody = "This $module entry has created by $strModifyBy on " . date("Y-m-d") . "\n\n";
        $strBody .= "$string";
    }
    return $strBody;
}

/* End added email function */
function createHeadCodeArray($db = null, $strFunctionalCode = null)
{
    $headData = null;
    if (!empty($db) && $db->connect()) {
        $strSQL = "SELECT functional_code, head_code FROM hrd_functional ";
        if (!empty($strFunctionalCode)) {
            $strSQL .= "WHERE functional_code = '$strFunctionalCode'";
        }
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $headData[$rowDb['functional_code']] = $rowDb['head_code'];
        }
    }
    return $headData;
}

function employeeTempSubmenu($activePage)
{
    $strWordsDataEntry = getWords("data entry");
    $strWordsEmployeeTemporaryDataList = getWords("employee temporary data list");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsDataEntry) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'employee_temporary_edit.php';
    }
    $submenuData['title'] = $strWordsDataEntry;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsEmployeeTemporaryDataList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'employee_temporary_list.php';
    }
    $submenuData['title'] = $strWordsEmployeeTemporaryDataList;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function dataRatingSubmenu($activePage)
{
    $strWordsDataEntry = getWords("entry rating");
    $strWordsEmployeeTemporaryDataList = getWords("rating list");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsDataEntry) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'employee_rating_edit.php';
    }
    $submenuData['title'] = $strWordsDataEntry;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsEmployeeTemporaryDataList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'employee_rating_list.php';
    }
    $submenuData['title'] = $strWordsEmployeeTemporaryDataList;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function evaluationCatSubmenu($activePage)
{
    $strWordsEvaluationCategory = getWords("evaluation category");
    //$strWordsGeneralKPIForManagerial = getWords("general kpi for managerial");
    //$strWordsGeneralKPIForStaff = getWords("general kpi for staff");
    $strWordsEmployeeKPI = getWords("employee individual kpi");
    $strWordsEvaluationFeedbackSetup = getWords("evaluation feedback setup");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsEvaluationCategory) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'evaluation_category.php';
    }
    $submenuData['title'] = $strWordsEvaluationCategory;
    $submenu[] = $submenuData;
    /*$submenuData = [];
    if ($activePage == $strWordsGeneralKPIForManagerial) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'evaluation_criteria_managerial.php';
    }
    $submenuData['title'] = $strWordsGeneralKPIForManagerial;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsGeneralKPIForStaff) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'evaluation_criteria_staff.php';
    }
    $submenuData['title'] = $strWordsGeneralKPIForStaff;
    $submenu[] = $submenuData;*/
    $submenuData = [];
    if ($activePage == $strWordsEmployeeKPI) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'evaluation_criteria_employee.php';
    }
    $submenuData['title'] = $strWordsEmployeeKPI;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsEvaluationFeedbackSetup) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'evaluation_feedback_setup.php';
    }
    $submenuData['title'] = $strWordsEvaluationFeedbackSetup;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

function evaluationSubmenu($activePage)
{
    $strWordsEvaluationEntry = getWords("evaluation entry");
    //$strWordsEvaluationList = getWords("evaluation list");
    $strWordsEvaluationApproval = getWords("evaluation approval");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsEvaluationEntry) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'evaluation_edit.php';
    }
    $submenuData['title'] = $strWordsEvaluationEntry;
    $submenu[] = $submenuData;
    /*$submenuData = [];
    if ($activePage == $strWordsEvaluationList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'evaluation_list.php';
    }
    $submenuData['title'] = $strWordsEvaluationList;
    $submenu[] = $submenuData;*/
    $submenuData = [];
    if ($activePage == $strWordsEvaluationApproval) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'evaluation_approval.php';
    }
    $submenuData['title'] = $strWordsEvaluationApproval;
    $submenu[] = $submenuData;
    return pageSubMenu($submenu);
}

// uddin: fungsi khusus untuk wal untuk cek apakah atasan dan bawahan cocok
function isUserAtasanDanBawahan($db, $idAtasan, $idBawahan)
{
    $strDataUserRole = $_SESSION['sessionUserRole'];
    if ($strDataUserRole > ROLE_ADMIN) {
        return true;
    }
    $bolResult = false;
    $strSQL = "SELECT emp.id,emp.employee_id,emp.functional_code,emp.employee_name,f1.head_code,f1.functional_name as bawahan_funct,f1.employee_name as bawahan
      FROM hrd_employee as emp 
      LEFT JOIN (
            SELECT t0.*,t1.employee_name from hrd_functional t0,hrd_employee t1 
            WHERE t0.functional_code=t1.functional_code AND t1.id=$idBawahan
                ) AS f1 ON f1.head_code=emp.functional_code
      WHERE emp.employee_id= '$idAtasan'";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        if ($rowDb['functional_code'] != "" AND $rowDb['functional_code'] == $rowDb['head_code']) {
            $bolResult = true;
        }
    }
    // echo $rowDb['functional_code']."--".$rowDb['head_code'];
    // echo $idAtasan."KKK".$idBawahan;
    // exit();
    return $bolResult;
}// isUserAtasanDanBawahan
function getInitialValueDateRange($db, $table, $datefield, $order, $kriteria, $default)
{
    $strReturn = $default;
    $strSQL2 = "select $datefield from $table where $kriteria order by $datefield $order limit 1";
    $resDb2 = $db->execute($strSQL2);
    while ($rowDb2 = $db->fetchrow($resDb2)) {
        $strReturn = $rowDb2[$datefield];
    }
    $arrDate = explode("-", $strReturn);
    $strReturn = $arrDate[1] . "/" . $arrDate[2] . "/" . $arrDate[0];
    return $strReturn;
}

function check_plain($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function createDateRangeArray($strDateFrom, $strDateTo)
{
    $aryRange = [];
    $iDateFrom = mktime(1, 0, 0, substr($strDateFrom, 5, 2), substr($strDateFrom, 8, 2), substr($strDateFrom, 0, 4));
    $iDateTo = mktime(1, 0, 0, substr($strDateTo, 5, 2), substr($strDateTo, 8, 2), substr($strDateTo, 0, 4));
    if ($iDateTo >= $iDateFrom) {
        array_push($aryRange, date('Y-m-d', $iDateFrom)); // first entry
        while ($iDateFrom < $iDateTo) {
            $iDateFrom += 86400; // add 24 hours
            array_push($aryRange, date('Y-m-d', $iDateFrom));
        }
    }
    return $aryRange;
}

// 2016-05-24 | otTerapisSubMenu
function otTerapisSubMenu($activePage)
{
    $strWordsDataEntry = getWords("data entry");
    $strWordsOtTerapisList = getWords("overtime terapis list");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsDataEntry) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'overtime_terapis_edit.php';
    }
    $submenuData['title'] = $strWordsDataEntry;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsOtTerapisList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'overtime_terapis_list.php';
    }
    $submenuData['title'] = $strWordsOtTerapisList;
    $submenu[] = $submenuData;
    $submenuData = [];
    return pageSubMenu($submenu);
}

function vacancyAdvSubmenu($activePage)
{
    $strWordsDataEntry = getWords("data entry");
    $strWordsAdvertisementList = getWords("advertisement list");
    $submenu = [];
    $submenuData = [];
    if ($activePage == $strWordsDataEntry) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'vacancy_adv_edit.php';
    }
    $submenuData['title'] = $strWordsDataEntry;
    $submenu[] = $submenuData;
    $submenuData = [];
    if ($activePage == $strWordsAdvertisementList) {
        $submenuData['active'] = true;
    } else {
        $submenuData['active'] = false;
        $submenuData['link'] = 'vacancy_adv_list.php';
    }
    $submenuData['title'] = $strWordsAdvertisementList;
    $submenu[] = $submenuData;
    $submenuData = [];
    return pageSubMenu($submenu);
}

function newArrayAllowance($arrayMA = null)
{
    $newArrayAllowance = [];
    if (!empty($arrayMA)) {
        //Create Benefit With Tax Allowance
        $benefitTaxAllowance = [];
        $benefitNonTaxAllowance = [];
        $defaultAllowance = [];
        $otherAllowance = [];
        $firstViewAllowance = [];
        $managerialAllowance = [];
        if (count($arrayMA)) {
            foreach ($arrayMA as $strCode => $maData) {
                $splitCode = explode('_', $maData['allowance_code']);
                $isManagerial = false;
                if (strtolower($splitCode[count($splitCode) - 1]) == 'managerial') {
                    $isManagerial = true;
                }
                if ($isManagerial) {
                    $managerialAllowance[] = $maData;
                } else {
                    if ($maData['benefit'] == 't' && $maData['tax'] == 't') {
                        $benefitTaxAllowance[] = $maData;
                        //$firstViewAllowance[] = $maData;
                    } else if ($maData['benefit'] == 't' && $maData['tax'] == 'f') {
                        $benefitNonTaxAllowance[] = $maData;
                    } else if ($maData['benefit'] == 'f' && $maData['is_default'] == 't') {
                        $defaultAllowance[] = $maData;
                        $firstViewAllowance[] = $maData;
                    } else if ($maData['benefit'] == 'f' && $maData['is_default'] == 'f') {
                        $otherAllowance[] = $maData;
                        $firstViewAllowance[] = $maData;
                    }
                }
            }
        }
        $newArrayAllowance['benefit_tax'] = $benefitTaxAllowance;
        $newArrayAllowance['benefit_non_tax'] = $benefitNonTaxAllowance;
        $newArrayAllowance['default_allowance'] = $defaultAllowance;
        $newArrayAllowance['other_allowance'] = $otherAllowance;
        $newArrayAllowance['first_view_allowance'] = $firstViewAllowance;
        $newArrayAllowance['managerial_allowance'] = $managerialAllowance;
    }
    return $newArrayAllowance;
}

function newArrayDeduction($arrayMD = null)
{
    $newArrayDeduction = [];
    if (!empty($arrayMD)) {
        //Create Benefit With Tax Allowance
        $defaultDeduction = [];
        $otherDeduction = [];
        $managerialDeduction = [];
        if (count($arrayMD)) {
            foreach ($arrayMD as $strCode => $mdData) {
                $splitCode = explode('_', $mdData['deduction_code']);
                $isManagerial = false;
                if (strtolower($splitCode[count($splitCode) - 1]) == 'managerial') {
                    $isManagerial = true;
                }
                if ($isManagerial) {
                    $managerialDeduction[] = $mdData;
                } else {
                    if ($mdData['is_default'] == 't') {
                        $defaultDeduction[] = $mdData;
                    } else if ($mdData['is_default'] == 'f') {
                        $otherDeduction[] = $mdData;
                    }
                }
            }
        }
        $newArrayDeduction['default_deduction'] = $defaultDeduction;
        $newArrayDeduction['other_deduction'] = $otherDeduction;
        $newArrayDeduction['managerial_deduction'] = $managerialDeduction;
    }
    return $newArrayDeduction;
}

function getAllDownLine($db = null, $functionalCode = null)
{
    global $arrayFunctional;
    if (!empty($db) && !empty($functionalCode) && $db->connect()) {
        $strSQL = "SELECT functional_code FROM hrd_functional WHERE head_code='$functionalCode'";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            if (!empty($rowDb['functional_code'])) {
                $arrayFunctional[] = $rowDb['functional_code'];
                getAllDownLine($db, $rowDb['functional_code']);
            }
        }
    }
    return $arrayFunctional;
}

function arrCompanyInfo($db = null, $idCompany)
{
    $arrCompanyInfo = null;
    if (!empty($db) && !empty($idCompany) && $db->connect()) {
        $strSQL = "SELECT * FROM hrd_company WHERE id=$idCompany";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
            $arrCompanyInfo['company_code'] = $rowDb['company_code'];
            $arrCompanyInfo['company_name'] = $rowDb['company_name'];
            $arrCompanyInfo['address'] = $rowDb['address'];
            $arrCompanyInfo['city'] = $rowDb['city'];
            $arrCompanyInfo['logo'] = $rowDb['logo'];
            $arrCompanyInfo['npwp'] = $rowDb['siup'];
            $arrCompanyInfo['phone'] = $rowDb['phone_number'];
            $arrCompanyInfo['fax'] = $rowDb['fax_number'];
            $arrCompanyInfo['email'] = $rowDb['email'];
        }
    }
    return $arrCompanyInfo;
}

?>
