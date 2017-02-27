<?php
// fungsi untuk mengambil informasi alert
function getAlert($db)
{
    global $arrUserInfo, $bolIsEmployee;
    global $_SESSION;
    global $strKriteriaCompany;
    scopeData(
        $strDataEmployee,
        $strDataSubSection,
        $strDataSection,
        $strDataDepartment,
        $strDataDivision,
        $_SESSION['sessionUserRole'],
        $arrUserInfo
    );
    $strClass = "bgNewRevised";
    $strResult = "<table width=100 border=0 cellspacing=0 cellpadding=1>\n";
    /*
    * start alert Placement
    * by yuda prabu
    * email :yuda.pc@gmail.com
    */
    if ($_SESSION['sessionUserRole'] == ROLE_SUPER || $_SESSION['sessionUserRole'] == ADMIN) {
        // cek yang statusnya akan di checked
        $strLink = "javascript:goAlert('mutation_list.php'," . REQUEST_STATUS_NEW . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_employee_mutation WHERE status = " . REQUEST_STATUS_NEW . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Placement Request Need Checked : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_SUPER) {
        // cek yang statusnya akan di Approve
        $strLink = "javascript:goAlert('mutation_list.php'," . REQUEST_STATUS_CHECKED . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_employee_mutation WHERE status = " . REQUEST_STATUS_CHECKED . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Placement Request Need Approve : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_SUPER) {
        // cek yang statusnya akan di AKnowledge
        $strLink = "javascript:goAlert('mutation_list.php'," . REQUEST_STATUS_APPROVED . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_employee_mutation WHERE status = " . REQUEST_STATUS_APPROVED . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Placement Request Need AKnowledge : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    $strLink = "javascript:goAlert('mutation_list.php'," . REQUEST_STATUS_ACKNOWLEDGED . ")";
    $strSQL = "SELECT count(id) AS total FROM hrd_employee_mutation WHERE 1=1 ";
    if ($bolIsEmployee) {
        $strSQL .= " AND id_employee=" . $arrUserInfo['id_employee'] . " ";
    }
    $strSQL .= "AND status=3";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
        if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
            $strResult .= " <tr valign=top class=$strClass>\n";
            $strResult .= "  <td align=left nowrap>&nbsp;Placement List Acknowledged : </td>\n";
            $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
            $strResult .= " </tr>\n";
        }
    }
    /*
    * end alert Placement
    * by yuda prabu
    * email :yuda.pc@gmail.com
    */
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /*
    * start alert Overtime
    * by yuda prabu
    * email :yuda.pc@gmail.com
    */
    //if ($_SESSION['sessionUserRole'] == ROLE_SUPER || $_SESSION['sessionUserRole'] == ADMIN)
    if ($_SESSION['sessionIdGroup'] == 12 || $_SESSION['sessionIdGroup'] == 28 || $_SESSION['sessionIdGroup'] == 29 || $_SESSION['sessionIdGroup'] == 31 || $_SESSION['sessionIdGroup'] == 35 || $_SESSION['sessionIdGroup'] == 38 || $_SESSION['sessionIdGroup'] == 30) {
        // cek yang statusnya akan di checked
        $strLink = "javascript:goAlert('overtime_application_list.php'," . REQUEST_STATUS_NEW . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_overtime_application_employee WHERE 1=1 ";
        if ($bolIsEmployee) {
            $strSQL .= " AND id_employee=" . $arrUserInfo['id_employee'] . " ";
        }
        $strSQL .= "AND status = " . REQUEST_STATUS_NEW . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Overtime Request Need Checked : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    //if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_SUPER)
    if ($_SESSION['sessionIdGroup'] == 12 || $_SESSION['sessionIdGroup'] == 28 || $_SESSION['sessionIdGroup'] == 29 || $_SESSION['sessionIdGroup'] == 31 || $_SESSION['sessionIdGroup'] == 35 || $_SESSION['sessionIdGroup'] == 38 || $_SESSION['sessionIdGroup'] == 30) {
        // cek yang statusnya akan di Approve
        $strLink = "javascript:goAlert('overtime_application_list.php'," . REQUEST_STATUS_CHECKED . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_overtime_application_employee WHERE 1=1 ";
        if ($bolIsEmployee) {
            $strSQL .= " AND id_employee=" . $arrUserInfo['id_employee'] . " ";
        }
        $strSQL .= "AND status = " . REQUEST_STATUS_CHECKED . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Overtime Request Need Approve : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    //if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_SUPER)
    if ($_SESSION['sessionIdGroup'] == 12 || $_SESSION['sessionIdGroup'] == 28 || $_SESSION['sessionIdGroup'] == 29 || $_SESSION['sessionIdGroup'] == 31 || $_SESSION['sessionIdGroup'] == 35 || $_SESSION['sessionIdGroup'] == 38 || $_SESSION['sessionIdGroup'] == 30) {
        // cek yang statusnya akan di AKnowledge
        $strLink = "javascript:goAlert('overtime_application_list.php'," . REQUEST_STATUS_APPROVED . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_overtime_application_employee WHERE 1=1 ";
        if ($bolIsEmployee) {
            $strSQL .= " AND id_employee=" . $arrUserInfo['id_employee'] . " ";
        }
        $strSQL .= "AND status = " . REQUEST_STATUS_APPROVED . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Overtime Request Need AKnowledge : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    if ($_SESSION['sessionIdGroup'] == 12 || $_SESSION['sessionIdGroup'] == 28 || $_SESSION['sessionIdGroup'] == 29 || $_SESSION['sessionIdGroup'] == 31 || $_SESSION['sessionIdGroup'] == 35 || $_SESSION['sessionIdGroup'] == 38 || $_SESSION['sessionIdGroup'] == 30) {
        $strLink = "javascript:goAlert('overtime_application_list.php'," . REQUEST_STATUS_ACKNOWLEDGED . ")";
        $strSQL = "SELECT count(id) AS total FROM hrd_overtime_application_employee WHERE 1=1 ";
        if ($bolIsEmployee) {
            $strSQL .= " AND id_employee=" . $arrUserInfo['id_employee'] . " ";
        }
        $strSQL .= "AND status=3";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Overtime List Acknowledged : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    /*
    * end alert Overtime
    * by yuda prabu
    * email :yuda.pc@gmail.com
    */
    // cek apakah ada perubahan data PEGAWAI ---
    $strLink = "javascript:goAlert('employee_search.php')";
    if ($_SESSION['sessionUserRole'] == ROLE_ADMIN || $_SESSION['sessionUserRole'] == ROLE_SUPER) { //. cek apakah ada yang flagnya 1/3
        $strLink = "javascript:goAlert('employee_temporary_list.php',1)";
        $strSQL = "SELECT count(id) AS total FROM hrd_employee_temporary WHERE status = " . REQUEST_STATUS_NEW;
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Modified employee data : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) { //. cek apakah ada yang flagnya 1
        $strLink = "javascript:goAlert('employee_search.php',2)";
        $strSQL = "SELECT count(id) AS total FROM hrd_employee WHERE flag=2 ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Employee data need approval : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    //------ END of Employee
    $strLink = "javascript:goAlert('absence_list.php')";
    //if ($_SESSION['sessionUserRole'] >= ROLE_ADMIN) 
    /*
    * op-admin&lab-admin (31,29) Lintas(12) + op-member&labmember(28,35)
    */
    if ($_SESSION['sessionIdGroup'] == 12 || $_SESSION['sessionIdGroup'] == 31 || $_SESSION['sessionIdGroup'] == 29 || $_SESSION['sessionIdGroup'] == 28 || $_SESSION['sessionIdGroup'] == 35) {
        // cek yang statusnya udah baru
        $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_ACKNOWLEDGED . ")";
        $strSQL = "SELECT COUNT(t0.id) AS total FROM hrd_absence as t0 LEFT JOIN hrd_employee as t1 ON t0.id_employee = t1.id
                  WHERE status = " . REQUEST_STATUS_ACKNOWLEDGED . " " . $strKriteriaCompany;
        if ($strDataDivision != "") {
            $strSQL .= "AND division_code = '$strDataDivision' ";
        }
        if ($strDataDepartment != "") {
            $strSQL .= "AND department_code = '$strDataDepartment' ";
        }
        if ($strDataSection != "") {
            $strSQL .= "AND section_code = '$strDataSection' ";
        }
        if ($strDataSubSection != "") {
            $strSQL .= "AND sub_section_code = '$strDataSubSection' ";
        }
        if ($bolIsEmployee) {
            $strSQL .= " AND id_employee=" . $arrUserInfo['id_employee'] . " ";
        }
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;New Absence Request : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    //if ($_SESSION['sessionUserRole'] == ROLE_SUPER) 
    /*
    * op-manager & lab manager 1 &lab manager 2(30,36,38) :+ lintas (12)
    */
    if ($_SESSION['sessionIdGroup'] == 12 || $_SESSION['sessionIdGroup'] == 30 || $_SESSION['sessionIdGroup'] == 36 || $_SESSION['sessionIdGroup'] == 38) {
        // cek yang statusnya udah checked
        $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_CHECKED . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_absence WHERE status = " . REQUEST_STATUS_CHECKED . " ";
        if ($bolIsEmployee) {
            $strSQL .= " AND id_employee=" . $arrUserInfo['id_employee'] . " ";
        }
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Absence Request Need Approval : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    // ---- END of Absence
    // ---- cek informasi data LEAVE
    $strLink = "javascript:goAlert('absence_list.php')";
    if ($_SESSION['sessionUserRole'] == ROLE_SUPER) {
        // cek yang statusnya udah checked
        $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_CHECKED . ", 1)";
        $strSQL = "SELECT COUNT(*) AS total FROM hrd_absence AS t1 LEFT JOIN hrd_employee  AS t2 ON t1.id_employee = t2.id
					LEFT JOIN hrd_absence_type AS t3 ON t1.absence_type_code = t3.code 
				WHERE t1.status=1 ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Checked Leave Request : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    } elseif ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
        // cari request baru yang ada di bawah departmentnnya
        // cek yang statusnya udah new
        $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_NEW . ", 1)";
        $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_absence_detail  AS t1 ";
        $strSQL .= "LEFT JOIN hrd_absence_type AS t4 ON t1.absence_type = t4.code ";
        $strSQL .= "LEFT JOIN hrd_absence AS t3 ON t1.id_absence = t3.id  ";
        $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
        $strSQL .= "WHERE t3.status = " . REQUEST_STATUS_NEW . "  AND t4.is_leave = TRUE ";
        $strSQL .= "AND (t2.department_code = '" . $arrUserInfo['department_code'] . "' ";
        $strSQL .= "OR t2.division_code = '" . $arrUserInfo['division_code'] . "' ";
        $strSQL .= "OR t2.section_code = '" . $arrUserInfo['section_code'] . "' ) ";
        $strSQL .= "AND t1.id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;New Leave Request  : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    // ---- END of Leave
    if ($_SESSION['sessionIdGroup'] == 30) {   //if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        // cek yang statusnya udah baru
        $strLink = "javascript:goAlert('overtime_application_list.php',0)";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_overtime_application_employee WHERE status = 0 ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;New Overtime Actual Report : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    $strLink = "javascript:goAlert('trip_list.php')";
    if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        $strLink = "javascript:goAlert('trip_list.php'," . REQUEST_STATUS_NEW . ")";
        $strSQL = "SELECT count(a.id) as total FROM hrd_trip a inner join hrd_employee b on a.id_employee = b.id where a.status = " . REQUEST_STATUS_NEW . " ";
        $strSQL .= $strKriteriaCompany;
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;New Business Trip Request : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
        // cek yang statusnya udah verified
        $strLink = "javascript:goAlert('trip_list.php'," . REQUEST_STATUS_CHECKED . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_trip WHERE status = " . REQUEST_STATUS_CHECKED . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Verified Business Trip Request : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    //if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
    if ($_SESSION['sessionIdGroup'] == 30 || $_SESSION['sessionIdGroup'] == 36 || $_SESSION['sessionIdGroup'] == 38 || $_SESSION['sessionIdGroup'] == 12) //op-manager & lab manager 1 &lab manager 2(30,36,38)
    {
        // cek yang statusnya udah checked
        $strLink = "javascript:goAlert('trip_list.php'," . REQUEST_STATUS_CHECKED . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_trip WHERE status = " . REQUEST_STATUS_CHECKED . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Business Trip Request Need Approval : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    } else //if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
        if ($_SESSION['sessionIdGroup'] == 31 || $_SESSION['sessionIdGroup'] == 29 || $_SESSION['sessionIdGroup'] == 12) //untuk op-admin&lab-admin (31,29) dan lintas
        {
            $strLink = "javascript:goAlert('trip_list.php'," . REQUEST_STATUS_APPROVE . ")";
            // cari request baru yang ada di bawah departmentnnya
            // cek yang statusnya udah new
            $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_trip AS t1 ";
            $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
            $strSQL .= "WHERE t1.status = " . REQUEST_STATUS_APPROVE . " ";
            $strSQL .= "AND t2.division_code = '" . $arrUserInfo['division_code'] . "' ";
            $strSQL .= "AND t2.department_code = '" . $arrUserInfo['department_code'] . "' ";
            $strSQL .= "AND t2.section_code = '" . $arrUserInfo['section_code'] . "' ";
            $strSQL .= "AND t2.sub_section_code = '" . $arrUserInfo['sub_section_code'] . "' ";
            $strSQL .= "AND t1.id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
            $resDb = $db->execute($strSQL);
            if ($rowDb = $db->fetchrow($resDb)) {
                if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                    $strResult .= " <tr valign=top class=$strClass>\n";
                    $strResult .= "  <td align=left nowrap>&nbsp;New Business Trip Request Need Anknowladge : </td>\n";
                    $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                    $strResult .= " </tr>\n";
                }
            }
        }
    // --- end of Perjalanan Dinas
    // -- cek Permintaan Karyawan baru
    $strLink = "javascript:goAlert('recruitment_list.php')";
    if ($_SESSION['sessionUserRole'] >= ROLE_ADMIN) {
        // cek yang statusnya udah baru
        $strLink = "javascript:goAlert('recruitment_list.php'," . REQUEST_STATUS_NEW . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_recruitment_need WHERE status = " . REQUEST_STATUS_NEW . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;New Recruitment Need Request : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\"><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
        // cek yang statusnya udah verified
        $strLink = "javascript:goAlert('recruitment_list.php'," . REQUEST_STATUS_CHECKED . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_recruitment_need WHERE status = " . REQUEST_STATUS_CHECKED . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Verified Recruitment Need Request : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        // cek yang statusnya udah checked
        $strLink = "javascript:goAlert('recruitment_list.php'," . REQUEST_STATUS_CHECKED . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_recruitment_need WHERE status = " . REQUEST_STATUS_CHECKED . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Recruitment Need Request Need Approval : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    // cek TRAINING PLAN yang belum dibuat request-nya
    if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        $strLink = "javascript:goAlert('training_plan_list.php'," . REQUEST_STATUS_NEW . ")";
        $strSQL = "SELECT COUNT(id1) AS total FROM  ";
        $strSQL .= "(SELECT id AS id1 FROM hrd_training_plan ";
        $strSQL .= "WHERE  ((expected_date > CURRENT_DATE AND (expected_date - interval '1 months') < CURRENT_DATE) OR (expected_date < CURRENT_DATE)) ";
        if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
            $strSQL .= "AND department_code = '" . $arrUserInfo['department_code'] . "' ";
        }
        $strSQL .= "EXCEPT ";
        $strSQL .= "SELECT DISTINCT id_plan AS id1 FROM hrd_training_request ";
        $strSQL .= "WHERE EXTRACT(year FROM request_date) = '" . date("Y") . "' ";
        if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
            $strSQL .= "AND department_code = '" . $arrUserInfo['department_code'] . "' ";
        }
        $strSQL .= ") AS tbl ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Unrequested Training Plan : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    // --- Permintaan Training ---
    $strLink = "javascript:goAlert('training_request_list.php')";
    if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        // cek yang statusnya udah baru
        $strLink = "javascript:goAlert('training_request_list.php'," . REQUEST_STATUS_NEW . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_training_request WHERE status = " . REQUEST_STATUS_NEW . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;New Training Need Request : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
        // cek yang statusnya udah verified
        $strLink = "javascript:goAlert('training_request_list.php'," . REQUEST_STATUS_CHECKED . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_training_request WHERE status = " . REQUEST_STATUS_CHECKED . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Verified Training Request : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    if ($_SESSION['sessionUserRole'] == ROLE_ADMIN) {
        // cek yang statusnya udah checked
        $strLink = "javascript:goAlert('training_request_list.php'," . REQUEST_STATUS_CHECKED . ")";
        $strSQL = "SELECT COUNT(id) AS total FROM hrd_training_request WHERE status = " . REQUEST_STATUS_CHECKED . " ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;Training Request Need Approval : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    } else if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
        // cari request baru yang ada di bawah departmentnnya
        // cek yang statusnya udah new
        $strLink = "javascript:goAlert('training_request_list.php'," . REQUEST_STATUS_NEW . ")";
        $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_training_request AS t1 ";
        $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
        $strSQL .= "WHERE t1.status = " . REQUEST_STATUS_NEW . " ";
        $strSQL .= "AND t2.department_code = '" . $arrUserInfo['department_code'] . "' ";
        $strSQL .= "AND t2.section_code = '" . $arrUserInfo['section_code'] . "' ";
        $strSQL .= "AND t1.id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
        $resDb = $db->execute($strSQL);
        if ($rowDb = $db->fetchrow($resDb)) {
            if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
                $strResult .= " <tr valign=top class=$strClass>\n";
                $strResult .= "  <td align=left nowrap>&nbsp;New Training Request (Need Approval) : </td>\n";
                $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
                $strResult .= " </tr>\n";
            }
        }
    }
    //added by adnan
    //untuk menampilkan ptjb
    /* $strLink = "javascript:goAlert('trip_list.php',3 )";
    $strSQL   = "SELECT count(id) AS total FROM hrd_trip WHERE 1=1 ";
    if ($bolIsEmployee) $strSQL   .= " AND id_employee=".$arrUserInfo['id_employee']." ";
    $strSQL   .= "AND status=3";

    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {

      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Trip List Acknowledged : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    } */
    //absence by employee id
    /*  $strLink = "javascript:goAlert('absence_list.php',3 )";
     $strSQL   = "SELECT count(id) AS total FROM hrd_absence WHERE 1=1 ";
     if ($bolIsEmployee) $strSQL   .= " AND id_employee=".$arrUserInfo['id_employee']." ";
     $strSQL   .= "AND status=3";

     $resDb = $db->execute($strSQL);
     if ($rowDb = $db->fetchrow($resDb)) {

       if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
         $strResult .= " <tr valign=top class=$strClass>\n";
         $strResult .= "  <td align=left nowrap>&nbsp;Absence Acknowledged : </td>\n";
         $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" .$rowDb['total']. "&nbsp; data</a></strong></td>\n";
         $strResult .= " </tr>\n";
       }
     } */
    //
    ///////////by yuda ///////////// alert for employee absence  ///////////
    // for cuti
    /*
    echo $_SESSION['sessionEmployeeID'];
    echo "<br>";
    echo $arrUserInfo['id_employee'];
    */
    $strResult .= "</table>\n";
    return $strResult;
}

?>