<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/model/model.php');
$return_data = null;
if (isset($_GET['action']) && isset($_GET['term'])) {
    $db = new CdbClass;
    $action = $_GET['action'];
    if ($action == 'getemployee') {
        $strSearch = $_GET['term'];
        $return_data = getEmployeeData($db, $strSearch);
    }
}
print json_encode($return_data);
exit();
function getEmployeeData($db, $strSearch)
{
    $strKriteriaCompany = '';
    $strDataFunctional = '';
    $strDataDivision = '';
    $employeeList = null;
    if ($db->connect()) {
        //global $arrUserInfo;
        //getUserEmployeeInfo();
        //$arrUserInfo = getAllUserInfo($db);
        $arrUserInfo = [];
        if (isset($_SESSION['sessionUserID'])) {
            $strUserID = $_SESSION['sessionUserID'];
            if ($strUserID == "") {
                return 0;
            }
        } else {
            return 0;
        }
        $strSQL = "SELECT t1.id, t1.employee_id, t1.employee_name, t1.position_code, t1.employee_status, ";
        $strSQL .= "t1.functional_code,t1.division_code, t1.department_code, t1.section_code, t1.sub_section_code, t1.id_company ";
        $strSQL .= "FROM adm_user AS t2 LEFT JOIN hrd_employee AS t1 ON TRIM(t1.employee_id) = TRIM(t2.employee_id)  ";
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
            if ($rowDb['id_company'] != "") {
                $strSQL = "SELECT * FROM hrd_company WHERE id = " . $rowDb['id_company'];
                $resDb = $db->execute($strSQL);
                $arrUserInfo['company_code'] = ($rowDb = $db->fetchrow($resDb)) ? $rowDb['company_code'] : "";
            } else {
                $arrUserInfo['company_code'] = "";
            }
        }
        $strDataUserRole = $_SESSION['sessionUserRole'];
        if ($strDataUserRole == ROLE_SUPERVISOR) {
            $strDataEmployee = $arrUserInfo['employee_id'];
            if ($arrUserInfo['division_code'] != "") {
                $strDataDivision = $arrUserInfo['division_code'];
            }
            if ($arrUserInfo['department_code'] != "") {
                $strDataDepartment = $arrUserInfo['department_code'];
            }
            if ($arrUserInfo['section_code'] != "") {
                $strDataSection = $arrUserInfo['section_code'];
            }
            if ($arrUserInfo['sub_section_code'] != "") {
                $strDataSubSection = $arrUserInfo['sub_section_code'];
            }
            if ($arrUserInfo['functional_code'] != "") {
                $strDataFunctional = $arrUserInfo['functional_code'];
            }
        } else if ($strDataUserRole == ROLE_EMPLOYEE) {
            $strDataEmployee = $arrUserInfo['employee_id'];
            if ($arrUserInfo['division_code'] != "") {
                $strDataDivision = $arrUserInfo['division_code'];
            }
            if ($arrUserInfo['department_code'] != "") {
                $strDataDepartment = $arrUserInfo['department_code'];
            }
            if ($arrUserInfo['section_code'] != "") {
                $strDataSection = $arrUserInfo['section_code'];
            }
            if ($arrUserInfo['sub_section_code'] != "") {
                $strDataSubSection = $arrUserInfo['sub_section_code'];
            }
            if ($arrUserInfo['functional_code'] != "") {
                $strDataFunctional = $arrUserInfo['functional_code'];
            }
        }
        $strKriteria = "";
        /*
        if ($arrData['dataBranch']!= "") {
          $strKriteria .= "AND branch_code = '".$arrData['dataBranch']."' ";
        }
        */
        $strKriteriaDiv = " where active=1";
        if ($strDataDivision != "") {
            if ($strDataDivision != "C0100") { // khusus direksi tidak melihat divisi
                $strKriteria .= "AND division_code = '" . $strDataDivision . "' ";
                $strKriteriaDiv .= " and division_code= '" . $strDataDivision . "' ";
            }
        }
        /*
        if ($strDataDepartment!= "") {
          $strKriteria .= "AND department_code = '".$strDataDepartment."' ";
        }
        if ($strDataSection!= "") {
          $strKriteria .= "AND section_code = '".$strDataSection."' ";
        }
        if ($strDataSubSection!= "") {
          $strKriteria .= "AND sub_section_code = '".$strDataSubSection."' ";
        }
        */
        // uddin : get kriteria untuk functional dibawahnya
        if ($strDataFunctional != "") {
            //$strSQL="SELECT * FROM hrd_functional WHERE head_code='".$strDataFunctional."'";
            $strSQL = "SELECT t1.*,t2.employee_id FROM hrd_functional as t1
                    LEFT JOIN  (select * from hrd_employee " . $strKriteriaDiv . ") as t2 ON t1.functional_code=t2.functional_code
                    WHERE t1.head_code='" . $strDataFunctional . "'";
            $resDb = $db->execute($strSQL);
            //$strFunctionalcode="('".$strDataFunctional."'"; // inisial masukkan kode functional diri sendiri
            $strFunctionalcode = "('INISIALINVOSAFUNCCODE'"; // dihilangkan inisial karena bikin muncul yg selevel, ganti dummy code, jangan gunakan "INISIALFUNCCODE" sebagai functional code
            while ($rowDb = $db->fetchrow($resDb)) {
                //$strFunctionalcode.=",'".$rowDb['functional_code']."'";
                $tempRecursif = getfunctionalrecursif(
                    $db,
                    $rowDb['functional_code'],
                    $rowDb['employee_id'],
                    $strKriteriaDiv,
                    0
                );
                $strFunctionalcode .= ",'" . $rowDb['functional_code'] . "'" . $tempRecursif;
            }
            $strFunctionalcode .= ")";
            $strKriteria .= "AND (functional_code in " . $strFunctionalcode . " or employee_id='" . $strDataEmployee . "')";
        }
        //echo $strKriteria;
        $strSQL = "SELECT id, employee_id, employee_name FROM hrd_employee WHERE 1=1 $strKriteriaCompany $strKriteria
		and (lower(employee_id) LIKE '%" . strtolower($strSearch) . "%' OR lower(employee_name) LIKE '%" . strtolower(
                $strSearch
            ) . "%')
		AND active = 1";
        //		echo $strSQL;
        //var_dump($arrUserInfo);
        //echo "<br/><br/>".ROLE_SUPERVISOR.$strDataUserRole.$strSQL;
        $resDb = $db->execute($strSQL);
        $employeeList = [];
        while ($rowDb = $db->fetchrow($resDb)) {
            $employeeList[] = [
                'id'    => $rowDb['id'],
                'label' => $rowDb['employee_name'],
                'value' => $rowDb['employee_id']
            ];
        }
    }
    return $employeeList;
}

?>