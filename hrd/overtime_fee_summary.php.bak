<?php
  include_once('../global/session.php');
  include_once('global.php');
  include_once('../global/common_data.php');
  include_once('../includes/datagrid2/datagrid.php');
  include_once('../includes/form2/form2.php');
  include_once("cls_overtime.php");
  include_once('../classes/hrd/hrd_overtime_fee_master.php');
  include_once('../classes/hrd/hrd_overtime_fee_detail.php');

  
  $dataPrivilege = getDataPrivileges(basename($_SERVER['PHP_SELF']), $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge);
  if (!$bolCanView) die(accessDenied($_SERVER['HTTP_REFERER']));

  $strWordsOvetimeFeeSummary  = getWords("overtime fee summary");
  $strWordsOvetimeFeeDetail   = getWords("overtime fee detail");
 
  $db = new CdbClass;

  $strDataID = getPostValue('dataIDMaster');
  $isNew = ($strDataID == "");

  if ($bolCanEdit)
  {
    $f = new clsForm("formInput", 1, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);

    $f->addHidden("dataIDMaster", $strDataID);
    $f->addSelect(getWords("company"), "dataCompany", getDataListCompany($strDataCompany, $bolCompanyEmptyOption, $arrCompanyEmptyData, $strKriteria2), "", "", false);
    $f->addInput(getWords("date from"), "dataDateFrom", (substr(getNextDateNextMonth(date("Y-m-d"), -1),0,8)."01"), array("style" => "width:$strDateWidth"), "date", true, true, true);
    $f->addInput(getWords("date thru"), "dataDateThru", (date("Y-").addPrevZero((date("m") - 1), 2)."-".lastday(date("m")-1, date("Y"))), array("style" => "width:$strDateWidth"), "date", true, true, true);
    $f->addSelect(getWords("salary month (for outdated OT)"), "dataSalaryMonth", getDataListMonth(date("m")), array(), "numeric", true, true, true);  
    $f->addSelect(getWords("salary year (for outdated OT)"), "dataSalaryYear", getDataListYear(date("Y")), array(), "numeric", true, true, true);  
    $f->addTextArea(getWords("note"), "dataNote", "", array("cols"=>76, "rows"=>3), "string", false, true, true);
    $f->addSubmit("btnSave", getWords("save"), array("onClick" => "javascript:myClient.confirmSave();"), true, true, "", "", "saveData()");
    $f->addButton("btnAdd", getWords("add new"), array("onClick" => "javascript:myClient.editData(0);"));
    
    $formInput = $f->render();
  }
  else
    $formInput = "";
  
  $myDataGrid = new cDataGrid("formData","DataGrid1");
  $myDataGrid->caption = strtoupper($strWordsLISTOF . " " . $dataPrivilege['menu_name']);
  $myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
  
  $myDataGrid->addColumnCheckbox(new DataGrid_Column("chkID", "id", array('width' => '30'), array('align'=>'center', 'nowrap' => '')));
  $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", array('width'=>'30'), array('nowrap'=>'')));
  $myDataGrid->addColumn(new DataGrid_Column(getWords("company"), "id_company", array('width' => '150'),array('nowrap' => ''), true, true, "", "printCompanyCode()", "", true));
  $myDataGrid->addColumn(new DataGrid_Column(getWords("date from"), "date_from", array('width' => '150'),array('nowrap' => '')));
  $myDataGrid->addColumn(new DataGrid_Column(getWords("date thru"), "date_thru", array('width' => '150'),array('nowrap' => '')));
  $myDataGrid->addColumn(new DataGrid_Column(getWords("salary month"), "salary_month", array('width' => '150'),array('nowrap' => '')));
  $myDataGrid->addColumn(new DataGrid_Column(getWords("salary year"), "salary_year", array('width' => '150'),array('nowrap' => '')));
  $myDataGrid->addColumn(new DataGrid_Column(getWords("note"), "note", ""));
  $myDataGrid->addColumn(new DataGrid_Column("", "id", array('width' => '75'), array('align' => 'center', 'nowrap' => ''), false, false, "","printDetailLink()"));
  if ($bolCanEdit)
    $myDataGrid->addColumn(new DataGrid_Column("", "", array('width' => '60'), array('align' => 'center', 'nowrap' => ''), false, false, "","printEditLink()", "", false /*show in excel*/));
  if ($bolCanDelete)
    $myDataGrid->addSpecialButton("btnDelete","btnDelete","submit","Delete","onClick=\"javascript:return myClient.confirmDelete();\"","deleteData()");

  $myDataGrid->addButtonExportExcel("Export Excel", $dataPrivilege['menu_name'].".xls", getWords($dataPrivilege['menu_name']));
  $myDataGrid->strAdditionalHtml = generateHidden("dataIDMaster", "", "");

  $myDataGrid->getRequest();
  //--------------------------------
  //get Data and set to Datagrid's DataSource by set the data binding (bind method)
  $strSQLCOUNT  = "SELECT COUNT(*) AS total FROM hrd_overtime_fee_master ";
  $strSQL       = "SELECT * FROM hrd_overtime_fee_master ";

  $myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
  $dataset = $myDataGrid->getData($db, $strSQL);

  //bind Datagrid with array dataset
  $myDataGrid->bind($dataset);
  $DataGrid = $myDataGrid->render();
  

  $strConfirmSave = getWords("do you want to save this entry?");
  
  
  $tbsPage = new clsTinyButStrong ;
  
  //write this variable in every page
  $strPageTitle = $dataPrivilege['menu_name'];
  $pageIcon = "../images/icons/".$dataPrivilege['icon_file'];
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));  
  //------------------------------------------------
  //Load Master Template
  $tbsPage->LoadTemplate($strMainTemplate) ;
  $tbsPage->Show() ;
//--------------------------------------------------------------------------------

  function printEditLink($params)
  {
    extract($params);
    return "
      <input type=hidden name='detailID$counter' id='detailID$counter' value='".$record['id']."' />
      <input type=hidden name='detailIDCompany$counter' id='detailIDCompany$counter' value='".$record['id_company']."' />
      <input type=hidden name='detailDateFrom$counter' id='detailDateFrom$counter' value='".$record['date_from']."' />
      <input type=hidden name='detailDateThru$counter' id='detailDateThru$counter' value='".$record['date_thru']."' />
      <input type=hidden name='detailSalaryMonth$counter' id='detailSalaryMonth$counter' value='".$record['salary_month']."' />
      <input type=hidden name='detailSalaryYear$counter' id='detailSalaryYear$counter' value='".$record['salary_year']."' />
      <input type=hidden name='detailNote$counter' id='detailNote$counter' value='".$record['note']."' />
      <a href=\"javascript:myClient.editData($counter)\">" .getWords('edit'). "</a>";
  }

  function printDetailLink($params)
  {
    extract($params);
    global $bolPrint;
    if ($bolPrint)
      return "";
    else
      return generateButton("btnDetail$counter", getWords("open"), "", "onclick =\"document.formData.dataIDMaster.value = '".$value."'; document.formData.action = 'overtime_fee_detail.php'; document.formData.submit();\"");
  }

  // fungsi untuk menyimpan data
  function saveData() 
  {
    global $f;
    global $isNew;

    $strmodified_byID = $_SESSION['sessionUserID'];
    
    $tblOvertimeFeeMaster = new cHrdOvertimeFeeMaster();
    $tblOvertimeFeeDetail = new cHrdOvertimeFeeDetail();
    $data = array("id_company" => $f->getValue('dataCompany'),
                  "date_from" => $f->getValue('dataDateFrom'),
                  "date_thru" => $f->getValue('dataDateThru'),
                  "salary_month" => $f->getValue('dataSalaryMonth'),
                  "salary_year" => $f->getValue('dataSalaryYear'),
                  "note" => $f->getValue('dataNote'));

    // simpan data -----------------------
    $bolSuccess = false;
    if ($isNew)
    {
      // data baru
      if ($tblOvertimeFeeMaster->findCount(array("id_company" => $f->getValue('dataCompany'), "date_from" => $f->getValue('dataDateFrom'), "date_thru" => $f->getValue('dataDateThru'), "salary_month" => $f->getValue('dataSalaryMonth'), "salary_year" => $f->getValue('dataSalaryYear'))) > 0)
      {
        $bolSuccess = false;
        $f->message = getWords("duplicate data");
      }
      else
      {
        $bolSuccess = $tblOvertimeFeeMaster->insert($data);
        generateOvertimeFeeDetail($tblOvertimeFeeMaster->getLastInsertId(),  $f->getValue('dataCompany'), $f->getValue('dataDateFrom'), $f->getValue('dataDateThru'), $f->getValue('dataSalaryMonth'), $f->getValue('dataSalaryYear'));
        $f->message = $tblOvertimeFeeMaster->strMessage;
      }
    } 
    else 
    {
      $bolSuccess = $tblOvertimeFeeMaster->update(/*pk*/"id='".$f->getValue('dataIDMaster')."'", /*data to update*/ $data);
      $f->message = $tblOvertimeFeeMaster->strMessage;
    }
    if ($bolSuccess)
    {
      if ($isNew)
        $f->setValue('dataIDMaster', $tblOvertimeFeeMaster->getLastInsertId());
      else
        $f->setValue('dataIDMaster', $f->getValue('dataIDMaster'));
      redirectPage("overtime_fee_detail.php?dataIDMaster=".$f->getValue('dataIDMaster'));
    }

    $f->msgClass = ($bolSuccess) ? "bgOK" : "bgError";

  } // saveData

  function generateOvertimeFeeDetail($strIDMaster, $strDataCompany,  $strDateFrom, $strDateThru, $strSalaryMonth, $strSalaryYear) 
  {
    global $db;
    $fltBreakfastAllowance = getSetting("ot_breakfast_allowance");
    $strSQL = "DELETE FROM hrd_overtime_fee_detail WHERE id_master = $strIDMaster";
    $resDb = $db->execute($strSQL);
    $strSaveSQL = "";

    if ($db->connect() && $strIDMaster != "")
    {

      // Overtime Breakfast Allowance
      // only for employee with full OT right
      // granted if overtime_start is at least one hour earlier than normal_start
      $strSQL  = "
        SELECT 
          t1.id_employee, 
          SUM(CASE WHEN normal_start - overtime_start_early >= interval '1 hours' THEN $fltBreakfastAllowance ELSE 0 END) AS ot_breakfast
        FROM 
        (
          SELECT ta.* FROM hrd_attendance AS ta
          LEFT JOIN (hrd_overtime_application_employee AS toe left join hrd_overtime_application as toa ON toa.id = toe.id_application)
          ON ta.attendance_date = toe.overtime_date AND ta.id_employee = toe.id_employee
          LEFT JOIN (hrd_absence_detail AS tad LEFT JOIN hrd_absence_type AS tat ON tad.absence_type =  tat.code) 
          ON ta.attendance_date = tad.absence_date AND ta.id_employee = tad.id_employee
          WHERE 
          (
            ((attendance_date BETWEEN '" .$strDateFrom."' AND '" .$strDateThru."') AND toa.is_outdated = 'f')
            OR
            (toa.is_outdated = 't' AND salary_month = $strSalaryMonth AND salary_year = $strSalaryYear )
          )
          AND (toe.status >= ".REQUEST_STATUS_APPROVED.") 
          AND (attendance_start is not null OR attendance_finish is not null) 
          AND (is_absence <> 't' OR leave_weight BETWEEN 0 AND 1) 
        UNION
          SELECT ta2.* FROM hrd_attendance AS ta2
          LEFT JOIN (hrd_absence_detail AS tad2 LEFT JOIN hrd_absence_type AS tat2 ON tad2.absence_type =  tat2.code) 
          ON ta2.attendance_date = tad2.absence_date AND ta2.id_employee = tad2.id_employee
          WHERE 
          (attendance_date BETWEEN '" .$strDateFrom."' AND '" .$strDateThru."')
          AND attendance_date NOT IN 
            (SELECT overtime_date FROM hrd_overtime_application_employee AS toe2 WHERE toe2.id_employee = ta2.id_employee)
          AND (attendance_start is not null OR attendance_finish is not null) 
          AND (is_absence <> 't' OR leave_weight BETWEEN 0 AND 1) 
          AND (auto_overtime = 't') 
        ) AS t1 
        LEFT JOIN (hrd_employee AS te LEFT JOIN hrd_position as tp ON te.position_code = tp.position_code)
        ON t1.id_employee = te.id 
        WHERE tp.get_ot = 1 AND te.id_company = '$strDataCompany' 
        GROUP BY t1.id_employee";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) 
      {
        $arrOTBreakfast[$rowDb['id_employee']] = $rowDb['ot_breakfast'];
      }


      // Overtime Meal Allowance
      /*
        Grade 1-8 
        -	pada hk  sampai lebih dari jam 20:00 mendapat Rp. 10,000 
        -	pada hk pulang jam 24:00 mendapat tambahan Rp. 10,000
        -	Pada HL, minimal  4 jam bekerja mendapat Rp. 20,000
        Grade 9-16 
        -	pada HK diatas jam 20:00 mendapat Rp. 20,000
        -	Pada HL, 3 – 6 jam bekerja mendapat Rp. 40,000
        -	Pada HL, diatas 6 jam bekerja mendapat Rp. 80,000
      */
      $arrOTMeal = array();
      $strSQL  = "
        SELECT t1.id_employee, 
          SUM(CASE WHEN holiday = 0 AND (overtime_finish > '20:00' OR overtime_finish BETWEEN '00:00' AND '06:00')  THEN 1 ELSE 0 END) AS ot_hk_gt20, 

          SUM(CASE WHEN holiday = 0 AND overtime_finish BETWEEN '00:00' AND '06:00' AND overtime_finish <> '00:00' THEN 1 ELSE 0 END) AS ot_hk_gt24, 

          SUM(CASE WHEN holiday = 1 AND (overtime_finish - overtime_start > interval '4 hours' OR (overtime_finish - overtime_start < interval '0 hours' AND overtime_finish - overtime_start + interval '24 hours' > interval '4 hours')) THEN 1 ELSE 0 END) AS ot_hl_gt4h, 
          
          SUM(CASE WHEN holiday = 1 AND (overtime_finish - overtime_start >= interval '3 hours' AND overtime_finish - overtime_start <= interval '6 hours' OR (overtime_finish - overtime_start < interval '0 hours' AND overtime_finish - overtime_start + interval '24 hours' >= interval '3 hours' AND overtime_finish - overtime_start <= interval '6 hours')) THEN 1 ELSE 0 END) AS ot_hl_gt3h, 

          SUM(CASE WHEN holiday = 1 AND (overtime_finish - overtime_start > interval '6 hours' OR (overtime_finish - overtime_start < interval '0 hours' AND overtime_finish - overtime_start + interval '24 hours' > interval '6 hours')) THEN 1 ELSE 0 END) AS ot_hl_gt6h

        FROM 
        (
          SELECT ta.* FROM hrd_attendance AS ta LEFT JOIN
          (hrd_overtime_application_employee AS toe left join hrd_overtime_application as toa ON toa.id = toe.id_application)
          ON ta.attendance_date = toe.overtime_date AND ta.id_employee = toe.id_employee
          LEFT JOIN (hrd_absence_detail AS tad LEFT JOIN hrd_absence_type AS tat ON tad.absence_type =  tat.code) 
          ON ta.attendance_date = tad.absence_date AND ta.id_employee = tad.id_employee
          WHERE 
          (
            ((attendance_date BETWEEN '" .$strDateFrom."' AND '" .$strDateThru."') AND toa.is_outdated = 'f')
            OR
            (toa.is_outdated = 't' AND salary_month = $strSalaryMonth AND salary_year = $strSalaryYear )
          )
          AND (toe.status >= ".REQUEST_STATUS_APPROVED.") 
          AND (attendance_start is not null OR attendance_finish is not null) 
          AND (is_absence <> 't' OR leave_weight BETWEEN 0 AND 1) 
        UNION
          SELECT ta2.* FROM hrd_attendance AS ta2
          LEFT JOIN (hrd_absence_detail AS tad2 LEFT JOIN hrd_absence_type AS tat2 ON tad2.absence_type =  tat2.code) 
          ON ta2.attendance_date = tad2.absence_date AND ta2.id_employee = tad2.id_employee
          WHERE 
          (attendance_date BETWEEN '" .$strDateFrom."' AND '" .$strDateThru."')
          AND attendance_date NOT IN 
            (SELECT overtime_date FROM hrd_overtime_application_employee AS toe2 WHERE toe2.id_employee = ta2.id_employee)
          AND (attendance_start is not null OR attendance_finish is not null) 
          AND (is_absence <> 't' OR leave_weight BETWEEN 0 AND 1) 
          AND (auto_overtime = 't') 
        ) AS t1 LEFT JOIN hrd_employee AS te ON t1.id_employee = te.id 
        WHERE te.id_company = '$strDataCompany' 
        GROUP BY t1.id_employee";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) 
      {
        $arrOTMeal[$rowDb['id_employee']] = $rowDb;
      }

      // Overtime Transport Allowance
      // baca dari spl, hanya untuk yang pulang pukul 21:00 or later
      $strSQL  = "
        SELECT 
          id_employee,
          SUM(toe.transport_fee) AS transport_allowance 
        FROM hrd_overtime_application_employee as toe
        LEFT JOIN hrd_overtime_application as toa ON toe.id_application = toa.id
        LEFT JOIN hrd_employee as te ON toe.id_employee = te.id
        WHERE  
        (
          ((toe.overtime_date BETWEEN '" .$strDateFrom."' AND '" .$strDateThru."') AND toa.is_outdated = 'f')
          OR
          (toa.is_outdated = 't' AND salary_month = $strSalaryMonth AND salary_year = $strSalaryYear)
        )
        AND
          (finish_actual > '21:00' OR finish_actual BETWEEN '00:00' AND '06:00')
        AND
          id_company = '$strDataCompany'
        GROUP BY toe.id_employee
        ";

      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) 
      {
        $arrOTTransport[$rowDb['id_employee']] = (is_numeric($rowDb['transport_allowance'])) ? $rowDb['transport_allowance'] : 0;
      }

      // Overtime Transport Allowance for Auto OT employee
      // baca dari attendance, hanya untuk yang pulang pukul 21:00 or later, auto ot, dan tidak ada spl nya

      $strSQL  = "
        SELECT 
          ta.id_employee,
          SUM(te.transport_fee) AS transport_allowance 
          FROM hrd_attendance AS ta LEFT JOIN hrd_employee AS te ON ta.id_employee = te.id 
          LEFT JOIN (hrd_absence_detail AS tad LEFT JOIN hrd_absence_type AS tat ON tad.absence_type =  tat.code) 
          ON ta.attendance_date = tad.absence_date AND ta.id_employee = tad.id_employee
          WHERE 
            (attendance_date BETWEEN '" .$strDateFrom."' AND '" .$strDateThru."')
          AND attendance_date NOT IN 
            (SELECT overtime_date FROM hrd_overtime_application_employee AS toe WHERE toe.id_employee = ta.id_employee)
          AND 
            (attendance_start is not null OR attendance_finish is not null) 
          AND (is_absence <> 't' OR leave_weight BETWEEN 0 AND 1) 
          AND
            (auto_overtime = 't') 
          AND
            (overtime_finish > '21:00' OR overtime_finish BETWEEN '00:00' AND '06:00')
          AND
            id_company = '$strDataCompany'
          GROUP BY ta.id_employee
        ";

      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) 
      {
        $arrOTTransport[$rowDb['id_employee']] = (isset($arrOTTransport[$rowDb['id_employee']])) ? ($arrOTTransport[$rowDb['id_employee']] + $rowDb['transport_allowance']) : $rowDb['transport_allowance'];
      }


      $strSQL = "SELECT id, grade_code FROM hrd_employee WHERE id_company = '$strDataCompany'";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) 
      {
        $arrGrade[$rowDb['id']] = $rowDb['grade_code'];
      }





      foreach($arrOTMeal as $strIDEmployee => $arrMealDetail)
      {
        $fltMeal = $fltBreakfast = $fltTransport = 0;
        /*
        Meal Allowance for Grade 1-8
        -	pada hk  sampai lebih dari jam 20:00 mendapat Rp. 10,000 
        -	pada hk pulang jam 24:00 mendapat tambahan Rp. 10,000
        -	Pada HL, minimal  4 jam bekerja mendapat Rp. 20,000
        */
        if ($arrGrade[$strIDEmployee] < 9)
        {
          $fltMeal += ($arrMealDetail['ot_hk_gt20'] * 10000);
          $fltMeal += ($arrMealDetail['ot_hk_gt24'] * 10000);
          $fltMeal += ($arrMealDetail['ot_hl_gt4h'] * 20000);
        }
        /*
        Meal Allowance for Grade >= 9
        -	pada HK diatas jam 20:00 mendapat Rp. 20,000
        -	Pada HL, 3 – 6 jam bekerja mendapat Rp. 40,000
        -	Pada HL, diatas 6 jam bekerja mendapat Rp. 80,000
        */
        else
        {
          $fltMeal += ($arrMealDetail['ot_hk_gt20'] * 20000);
          $fltMeal += ($arrMealDetail['ot_hl_gt3h'] * 40000);
          $fltMeal += ($arrMealDetail['ot_hl_gt6h'] * 80000);
        }
        /*
        Transport Allowance 
        sesuai dengan yang di approve di SPL, hanya berlaku bagi yang pulang diata pukul 21:00
        */
        if (isset($arrOTTransport[$strIDEmployee])) $fltTransport = $arrOTTransport[$strIDEmployee];

        /*
        Breakfast Allowance 
        Untuk karyawan yang berhak Full OT dan datang minimal 1 jam seelum normal start
        */
        if (isset($arrOTBreakfast[$strIDEmployee])) $fltBreakfast = $arrOTBreakfast[$strIDEmployee];

        $strSaveSQL .= "
          INSERT INTO hrd_overtime_fee_detail (id_master, id_employee, breakfast_allowance, meal_allowance, transport_allowance) 
          VALUES ($strIDMaster, $strIDEmployee, $fltBreakfast, $fltMeal, $fltTransport); ";
      }
    $resSaveDb = $db->execute($strSaveSQL);
    }
  }

  // fungsi untuk menghapus data
  function deleteData() 
  {
    global $myDataGrid;
  
    $arrKeys = array();
    foreach ($myDataGrid->checkboxes as $strValue)
    {
      $arrKeys['id'][] = $strValue;
      $arrKeys2['id_master'][] = $strValue;
    }
    $dataBasicSalarySet = new cHrdOvertimeFeeMaster();    
    $dataBasicSalarySet->deleteMultiple($arrKeys);
    $dataEmployeeBasicSalary = new cHrdOvertimeFeeDetail();    
    $dataEmployeeBasicSalary->deleteMultiple($arrKeys2);
    
    $myDataGrid->message = $dataBasicSalarySet->strMessage;
  } //deleteData

?>