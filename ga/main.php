<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_education_level.php');
include_once('../global/employee_function.php');
$strPageTitle = getWords("front of GA");
$strWordsAlert = getWords("alert");
$strWordsShortcut = getWords("shortcut");
$strWordsDueDateItem = getWords("Need to maintenance/extension");
$strWordsOpenConReq = getWords("New Consumable Request");
$strWordsOpenApartReq = getWords("New Apartment Request");
$strWordsOpenPurchReq = getWords("New Purchase Request");
$strWordsOutIn = getWords("Last Item IN/OUT");
$strWordsNews = getWords("news");
$bolCanView = true;
$bolCanEdit = true;
$bolCanDelete = true;
//---- INISIALISASI ----------------------------------------------------
$strDataDetail = "";
$strDetailNews = "";
$strDetailContract = "";
$strDetailAlert = "";
$strDetailBirthday = "";
$strNow = date("Y-m-d");
$strEmployeeID = "";
$strEmployeeName = "";
$strEmployeeStatus = "";
$strDepartment = "";
$strPosition = "";
$strLeave = "";
$strMedical = "";
$arrDataEmployee = [];
$strHidden = "";
$strDate = "";
$strNews = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNGSI FUNGSI------------------------------------------------------
//========================== FUNGSI CEK ITEM YANG MENDEKATI DUE DATE 25 HARI MENDEKATI  ========================================================
function getDataDueDateItem($db)
{
  global $words;
  global $_SESSION;
  global $strDataSection;
  global $bolIsEmployee;
  global $arrUserInfo;
  global $strKriteriaCompany;
  $strDataDivision = $strDataDepartment = $strDataSection = $strDataSubSection = "";
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  $intRows = 0;
  $strResult = "";
  $strSQL .= "SELECT i.*, ((i.item_due_date) - CURRENT_DATE) AS selisih,
    		   ic.category_name AS category_name,
    		   ic.maintenance_action AS maintenance_action,
	           CASE WHEN (i.item_due_date = CURRENT_DATE) THEN 1 ELSE 0 END AS sekarang
			   FROM ga_item AS i
			   LEFT JOIN ga_item_category AS ic ON i.id_category=ic.id
			   WHERE ((i.item_due_date >CURRENT_DATE) AND (i.item_due_date - interval '25 days' < CURRENT_DATE))
			   ORDER BY i.item_due_date ASC";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strID = $rowDb['id'];
    $strItemName = $rowDb['item_name'];
    $strDueDate = pgDateFormat($rowDb['item_due_date'], "d-M-y");
    $strUnit = $rowDb['item_unit'];
    $strStock = $rowDb['item_stock'];
    $strName = $rowDb['employee_name'];
    $strCategoryName = $rowDb['category_name'];
    $strMaintenanceAction = $rowDb['maintenance_action'];
    $strSelisih = $rowDb['selisih'];
    $strClass = ($rowDb['sekarang'] == 1) ? "class = 'bgLate'" : "";
    $strResult .= "<tr valign=top $strClass>\n";
    $strResult .= "  <td nowrap>$strItemName&nbsp;</td>";
    $strResult .= "  <td nowrap>$strCategoryName&nbsp;</td>";
    $strResult .= "  <td align=center>$strStock&nbsp;</td>";
    $strResult .= "  <td align=center>$strUnit&nbsp;</td>";
    $strResult .= "  <td align=center>$strDueDate&nbsp;</td>";
    $strResult .= "  <td align=center>$strMaintenanceAction&nbsp;</td>";
    $strResult .= "  <td>$strSelisih&nbsp;</td>";
    $strResult .= "  <td align=center nowrap><a href=\"asset_maintenance_edit.php?dataIDItem=$strID\">" . getWords(
            "Do"
        ) . " $strConfirm</a>&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  $intTotalData = $intRows;
  return $strResult;
}

//====================================== END DUE DATE INFO=============================================================================================
//========================== FUNGSI MELIHAT CONSUMABLE YANG BELUM DIPOSES ===============================================================================
function getDataNewConsumableReq($db)
{
  global $words;
  global $_SESSION;
  global $strDataSection;
  global $bolIsEmployee;
  global $arrUserInfo;
  global $strKriteriaCompany;
  $strDataDivision = $strDataDepartment = $strDataSection = $strDataSubSection = "";
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  $intRows = 0;
  $strResult = "";
  $strSQL .= "SELECT i.item_name AS item_name,
	  			e.employee_name AS employee_name,
				e.employee_id AS employee_id,
				CASE WHEN a.status=0 THEN 'New' END AS state,
				a.* 
                FROM ga_consumable_request as a LEFT JOIN ga_item AS i ON a.id_item=i.id
				LEFT JOIN hrd_employee AS e ON a.id_employee=e.id
				WHERE a.status=0 order by a.request_date DESC
				";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strID = $rowDb['id'];
    $strItemName = $rowDb['item_name'];
    $strDueDate = pgDateFormat($rowDb['request_date'], "d-M-y");
    $strDepartment = $rowDb['department_code'];
    $strEmployee = $rowDb['employee_name'];
    $strAmount = $rowDb['item_amount'];
    $strStatus = $rowDb['state'];
    $strResult .= "<tr valign=top $strClass>\n";
    $strResult .= "  <td nowrap>$strItemName&nbsp;</td>";
    $strResult .= "  <td nowrap>$strEmployee&nbsp;</td>";
    $strResult .= "  <td align=center>$strDepartment&nbsp;</td>";
    $strResult .= "  <td align=center>$strAmount&nbsp;</td>";
    $strResult .= "  <td align=center>$strDueDate&nbsp;</td>";
    $strResult .= "  <td align=center>$strStatus&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  $intTotalData = $intRows;
  return $strResult;
}

//====================================== END CONSUMABLE =============================================================================================
//========================== FUNGSI MELIHAT Apartment YANG BELUM DIPOSES ===============================================================================
function getDataNewApartmentReq($db)
{
  global $_SESSION;
  global $strDataSection;
  global $bolIsEmployee;
  global $arrUserInfo;
  global $strKriteriaCompany;
  $strDataDivision = $strDataDepartment = $strDataSection = $strDataSubSection = "";
  $intRows = 0;
  $strResult = "";
  $strSQL .= "SELECT r.room_name AS room_name,
	  			e.employee_name AS employee_name,
			    a.request_by AS id_employee,
			    CASE WHEN a.status=0 THEN 'New' END AS state,
				a.* 
                FROM ga_apartment_request AS a LEFT JOIN ga_room AS r ON a.id_room=r.id
				LEFT JOIN hrd_employee AS e ON a.request_by=e.id
				WHERE a.status=0 order by a.request_date DESC
				";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strID = $rowDb['id'];
    $strRoom = $rowDb['room_name'];
    $strReqDate = pgDateFormat($rowDb['request_date'], "d-M-y");
    $strFromDate = pgDateFormat($rowDb['date_from'], "d-M-y");
    $strToDate = pgDateFormat($rowDb['date_to'], "d-M-y");
    $strEmployee = $rowDb['employee_name'];
    $strStatus = $rowDb['state'];
    $strResult .= "<tr valign=top $strClass>\n";
    $strResult .= "  <td nowrap>$strRoom&nbsp;</td>";
    $strResult .= "  <td nowrap>$strEmployee&nbsp;</td>";
    $strResult .= "  <td align=center>$strReqDate&nbsp;</td>";
    $strResult .= "  <td align=center>$strFromDate&nbsp;</td>";
    $strResult .= "  <td align=center>$strToDate&nbsp;</td>";
    $strResult .= "  <td align=center>$strStatus&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  $intTotalData = $intRows;
  return $strResult;
}

//====================================== END Apartment =============================================================================================
//========================== FUNGSI MELIHAT Purchase YANG BELUM DIPROSES ===============================================================================
function getDataNewPurchaseReq($db)
{
  global $_SESSION;
  global $strDataSection;
  global $bolIsEmployee;
  global $arrUserInfo;
  global $strKriteriaCompany;
  $strDataDivision = $strDataDepartment = $strDataSection = $strDataSubSection = "";
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  $intRows = 0;
  $strResult = "";
  $strSQL .= "SELECT i.category_name,
	  			e.employee_name AS employee_name,
	  			CASE WHEN pr.status=0 THEN 'New' END AS state,
	  			pr.*
	  			FROM ga_purchase_request AS pr
	  			LEFT JOIN ga_item_category AS i ON pr.id_asset_category=i.id
	  			LEFT JOIN hrd_employee AS e ON pr.id_employee=e.id
				WHERE pr.status=0 order by pr.request_date DESC
				";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strID = $rowDb['id'];
    $strEmployee = $rowDb['employee_name'];
    $strReqDate = pgDateFormat($rowDb['request_date'], "d-M-y");
    $strStatus = $rowDb['state'];
    $strResult .= "<tr valign=top $strClass>\n";
    $strResult .= "  <td nowrap>$strEmployee&nbsp;</td>";
    $strResult .= "  <td nowrap>$strEmployee&nbsp;</td>";
    $strResult .= "  <td align=center>$strReqDate&nbsp;</td>";
    $strResult .= "  <td align=center>$strStatus&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  $intTotalData = $intRows;
  return $strResult;
}

//====================================== END Apartment =============================================================================================
//========================== FUNGSI MELIHAT data yang distock out atau in ===============================================================================
function getDataStockOutIn($db)
{
  global $words;
  global $_SESSION;
  global $strDataSection;
  global $bolIsEmployee;
  global $arrUserInfo;
  global $strKriteriaCompany;
  $strDataDivision = $strDataDepartment = $strDataSection = $strDataSubSection = "";
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  $intRows = 0;
  $strResult = "";
  $strSQL .= "SELECT * FROM
				(
					SELECT 	so.id AS id_s,so.id_item AS id_item,
						so.item_amount AS item_amount,
						'OUT' AS status
					FROM ga_consumable_stock_out as so
					UNION
					SELECT si.id AS id_s,si.id_item AS id_item,
						si.item_amount AS item_amount,
						'IN' AS status
					FROM ga_consumable_stock_in as si
				) AS ut
				LEFT JOIN ga_item AS i ON ut.id_item=i.id
                order by id_s DESC LIMIT 20
			";
  $resDb = $db->execute($strSQL);
  $intRows = 0;
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strID = $rowDb['id'];
    $strItem = $rowDb['item_name'];
    $strStockNow = $rowDb['item_stock'];
    $strStockAmount = $rowDb['item_amount'];
    $strStatus = $rowDb['status'];
    $strResult .= "<tr valign=top $strClass>\n";
    $strResult .= "  <td nowrap>$intRows&nbsp;</td>";
    $strResult .= "  <td nowrap>$strItem&nbsp;</td>";
    $strResult .= "  <td nowrap>$strStockNow&nbsp;</td>";
    $strResult .= "  <td align=center>$strStockAmount&nbsp;</td>";
    $strResult .= "  <td align=center>$strStatus&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  $intTotalData = $intRows;
  return $strResult;
}

//====================================== END  MELIHAT data yang distock out atau in ==========================================================
/*
SELECT * FROM
(
SELECT 	so.id_item AS id_item,
 so.item_amount AS item_amount,
 'OUT' AS status
FROM ga_consumable_stock_out as so

UNION

SELECT 	si.id_item AS id_item,
 si.item_amount AS item_amount,
 'IN' AS status
FROM ga_consumable_stock_in as si

) AS report_stock




*/
//fungsi untuk mengambil data news (by Farhan)
function getDataNews($db)
{
  global $words;
  global $_SESSION;
  global $strKriteriaCompany;
  $intRows = 0;
  $strResult = "<table width=\"100%\" class=\"gridTable\">";
  $strSQL = "SELECT t0.*, company_code FROM hrd_news AS t0 LEFT JOIN hrd_company AS t1 ON t0.id_company = t1.id
                   WHERE 1=1 $strKriteriaCompany ORDER BY date_event";
  $resDb = $db->execute($strSQL);
  $strResult .= "<tr valign=top height=20>\n";
  $strResult .= "  <th nowrap align=center width=\"20px\">" . getWords("no.") . "&nbsp;</td>";
  $strResult .= "  <th align=center>" . getWords("created") . "&nbsp;</td>";
  $strResult .= "  <th align=center>" . getWords("event date") . "&nbsp;</td>";
  $strResult .= "  <th width=\"50px\" align=center>" . getWords("company") . "&nbsp;</td>";
  $strResult .= "  <th align=center width=\"75%\">" . getWords("news") . "&nbsp;</td>";
  $strResult .= "</tr>\n";
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    $strResult .= "<tr valign=top height=20>\n";
    $strResult .= "  <td nowrap align=center>$intRows&nbsp;</td>";
    $strResult .= "  <td align=center>" . $rowDb['created'] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $rowDb['date_event'] . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . $rowDb['company_code'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['news'] . "&nbsp;</td>";
    $strResult .= "</tr>\n";
  }
  $strResult .= "</table>";
  $intTotalData = $intRows;
  return $strResult;
} // getDataNews
// fungsi mengambil data informasi karyawan, jika ada user
function getEmployeeInfo($db)
{
  global $arrUserInfo;
  global $arrDataEmployee;
  if ($arrUserInfo['employee_id'] != "") {
    $arrDataEmployee['employee_id'] = $arrUserInfo['employee_id'];
    $arrDataEmployee['employee_name'] = $arrUserInfo['employee_name'];
    $strSQL = "SELECT employee_status FROM hrd_employee ";
    $strSQL .= "WHERE employee_id = '" . $arrUserInfo['employee_id'] . "' ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $arrDataEmployee['employee_status'] = $rowTmp['employee_status'];
    }
  }
}

// fungsi untuk mengambil informasi alert
function getAlert($db)
{
  global $arrUserInfo;
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
  // ---- cek informasi data ABSENCE -------
  $strLink = "javascript:goAlert('absence_list.php')";
  if ($_SESSION['sessionUserRole'] >= ROLE_ADMIN) {
    // cek yang statusnya udah baru
    $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(t0.id) AS total FROM hrd_absence as t0 LEFT JOIN hrd_employee as t1 ON t0.id_employee = t1.id
                  WHERE status = " . REQUEST_STATUS_NEW . " " . $strKriteriaCompany;
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
  if ($_SESSION['sessionUserRole'] == ROLE_SUPER) {
    // cek yang statusnya udah checked
    $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_CHECKED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_absence WHERE status = " . REQUEST_STATUS_CHECKED . " ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;Absence Request Need Approval : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  } elseif ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) {
    // cari request baru yang ada di bawah departmentnnya
    // cek yang statusnya udah new
    $strLink = "javascript:goAlert('absence_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_absence AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
    $strSQL .= "WHERE t1.status = " . REQUEST_STATUS_NEW . " ";
    $strSQL .= "AND (t2.department_code = '" . $arrUserInfo['department_code'] . "' ";
    $strSQL .= "OR t2.division_code = '" . $arrUserInfo['division_code'] . "' ";
    $strSQL .= "OR t2.section_code = '" . $arrUserInfo['section_code'] . "' ) ";
    $strSQL .= "AND t1.id_employee <> '" . $arrUserInfo['id_employee'] . "' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Absence Request (Need Approval) : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  // ---- END of Absence
  // ---- cek informasi data LEAVE
  $strLink = "javascript:goAlert('leave_list.php')";
  if ($_SESSION['sessionUserRole'] >= ROLE_ADMIN) {
    // cek yang statusnya udah baru
    $strLink = "javascript:goAlert('leave_list.php'," . REQUEST_STATUS_NEW . ")";
    $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_absence_detail  AS t1 ";
    $strSQL .= "LEFT JOIN hrd_absence_type AS t2 ON t1.absence_type = t2.code ";
    $strSQL .= "LEFT JOIN hrd_absence AS t3 ON t1.id_absence = t3.id WHERE status = " . REQUEST_STATUS_NEW . " AND t2.is_leave = TRUE ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['total'] != "" && $rowDb['total'] > 0) {
        $strResult .= " <tr valign=top class=$strClass>\n";
        $strResult .= "  <td align=left nowrap>&nbsp;New Leave Request : </td>\n";
        $strResult .= "  <td align=right nowrap><strong><a href=\"$strLink\">" . $rowDb['total'] . "&nbsp; data</a></strong></td>\n";
        $strResult .= " </tr>\n";
      }
    }
  }
  if ($_SESSION['sessionUserRole'] == ROLE_SUPER) {
    // cek yang statusnya udah checked
    $strLink = "javascript:goAlert('leave_list.php'," . REQUEST_STATUS_CHECKED . ")";
    $strSQL = "SELECT COUNT(t1.id) AS total FROM hrd_absence_detail  AS t1 ";
    $strSQL .= "LEFT JOIN hrd_absence_type AS t2 ON t1.absence_type = t2.code ";
    $strSQL .= "LEFT JOIN hrd_absence AS t3 ON t1.id_absence = t3.id WHERE status = " . REQUEST_STATUS_CHECKED . " AND t2.is_leave = TRUE ";
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
    $strLink = "javascript:goAlert('leave_list.php'," . REQUEST_STATUS_NEW . ")";
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
  // -- cek Permintaan Karyawan baru
  $strLink = "javascript:goAlert('recruitment_list.php')";
  if ($_SESSION['sessionUserRole'] == ROLE_SUPERVISOR || $_SESSION['sessionUserRole'] == ROLE_ADMIN) {
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
    $strLink = "javascript:goAlert('recruitment_list.php'," . REQUEST_STATUS_VERIFIED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_recruitment_need WHERE status = " . REQUEST_STATUS_VERIFIED . " ";
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
  // --- end of Permintaan Karyawan
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
    $strLink = "javascript:goAlert('training_request_list.php'," . REQUEST_STATUS_VERIFIED . ")";
    $strSQL = "SELECT COUNT(id) AS total FROM hrd_training_request WHERE status = " . REQUEST_STATUS_VERIFIED . " ";
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
  // --  end of Permintaan Training
  $strResult .= "</table>\n";
  return $strResult;
}

//**************************************************************************************************************************************************
//----MAIN PROGRAM -----------------------------------------------------
//--- Ambil Data Yang Dikirim ----
$dtNow = getdate();
(isset($_REQUEST['dataWYear'])) ? $strDataWYear = $_REQUEST['dataWYear'] : $strDataWYear = $dtNow['year'];
(isset($_REQUEST['dataMonth'])) ? $strDataMonth = $_REQUEST['dataMonth'] : $strDataMonth = $dtNow['mon'];
(isset($_REQUEST['dataYear'])) ? $strDataYear = $_REQUEST['dataYear'] : $strDataYear = $dtNow['year'];
if (!is_numeric($strDataYear)) {
  $strDataYear = $dtNow['year'];
}
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $bolIsEmployee = isUserEmployee();
  if ($bolCanView) {
    $strDepartment = $arrUserInfo['department_code'] . " - " . $arrUserInfo['section_code'];
    $strPosition = $arrUserInfo['position_code'] . "";
    $strDetailAlert = "";
    if ($_SESSION['sessionUserRole'] != ROLE_EMPLOYEE) {
      $strDetailAlert .= getAlert($db);
      //$strDetailAlert .= "<br>". getAlertActivity($db);
    }
    $strDetailOutIn = getDataStockOutIn($db);
    $strDetailNews = getDataNews($db);
    $strDetailContract = getDataDueDateItem($db);
    $strDetailNewConsReq = getDataNewConsumableReq($db);
    $strDetailNewApartReq = getDataNewApartmentReq($db);
    $strDetailNewPurchReq = getDataNewPurchaseReq($db);
    getEmployeeInfo($db);
    if (isset($arrDataEmployee['employee_id'])) {
      $strEmployeeID = $arrDataEmployee['employee_id'];
      $strEmployeeName = $arrDataEmployee['employee_name'];
      $strEmployeeStatus = getWords($ARRAY_EMPLOYEE_STATUS[$arrDataEmployee['employee_status']]);
    }
    //writeLog(ACTIVITY_VIEW, MODULE_PAYROLL,"",0);
  } else {
    showError("view_denied");
  }
}
$strDisplayShortcut = "display:block"; //($_SESSION['sessionUserRole'] >= ROLE_ADMIN) ? "display:block" : "display:none";
$strDisplayBirthday = ($_SESSION['sessionUserRole'] >= ROLE_SUPERVISOR) ? "display:block" : "display:none";
$strDisplayAlert = ($_SESSION['sessionUserRole'] >= ROLE_SUPERVISOR) ? "display:block" : "display:none";
$strDisplayContract = ($_SESSION['sessionUserRole'] >= ROLE_SUPERVISOR) ? "display:block" : "display:none";
$strHidden .= "<input type=\"hidden\" name=\"dataMonth\" value=\"$strDataMonth\">";
$strHidden .= "<input type=\"hidden\" name=\"dataWYear\" value=\"$strDataWYear\">";
$tbsPage = new clsTinyButStrong;
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>