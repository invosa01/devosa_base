<?php
/*
  Daftar fungsi-fungsi (super) global, untuk employee
    Author: Yudi K.
*/
function getIDEmployee($db, $code)
{
  $strResult = "";
  if ($code != "") {
    $strSQL = "SELECT id FROM hrd_employee WHERE employee_id = '$code' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strResult = $rowDb['id'];
    }
  }
  return $strResult;
} // getemployee_idByCode
function getEmployeeCode($db, $id)
{
  $strResult = "";
  if ($id != "") {
    $strSQL = "SELECT employee_id FROM hrd_employee WHERE id = $id ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strResult = $rowDb['employee_id'];
    }
  }
  return $strResult;
} // getEmployeeCode
function getEmployeeName($db, $id)
{
  $strResult = "";
  if ($id != "") {
    $strSQL = "SELECT employee_name FROM hrd_employee WHERE id = $id ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strResult = $rowDb['employee_name'];
    }
  }
  return $strResult;
} // getEmployeeCode
//Get EmployeeBranchCode
function getEmployeeBranchCode($db, $id)
{
  $strResult = "";
  if ($id != "") {
    $strSQL = "SELECT branch_code FROM hrd_employee WHERE id = $id ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strResult = $rowDb['branch_code'];
    }
  }
  return $strResult;
} // getEmployeeBranchCode

function getEmployeeEmail($db, $id)
{
  $strResult = null;
  if ($id != "") {
    $strSQL = "SELECT email FROM hrd_employee WHERE id = $id ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strResult = $rowDb['email'];
    }
  }
  return $strResult;
} // getEmployeeEmail
// fungsi untuk mengambil informasi employee, berdasarkan EmployeeID (NIP)
// $column = kolom apa aja yang ingin diambil
function getAllEmployeeInfoByCode($db, $column = "*", $criteria = "")
{
  $arrResult = [];
  if ($column == "") {
    $column = "*";
  };
  $strSQL = "SELECT employee_id,$column FROM hrd_employee $criteria  ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrResult[$rowDb['employee_id']] = $rowDb;
  }
  return $arrResult;
}//getAllEmployeeInfoByCode
// fungsi untuk mengambil informasi employee, berdasarkan EmployeeID (NIP)
// $column = kolom apa aja yang ingin diambil
function getEmployeeInfoByCode($db, $code, $column = "*")
{
  $arrResult = [];
  $code = trim($code);
  if ($code == "") {
    return $arrResult;
  }
  if ($column == "") {
    $column = "*";
  };
  $strSQL = "SELECT $column FROM hrd_employee LEFT JOIN hrd_position AS pos ON hrd_employee.position_code = pos.position_code WHERE employee_id = '$code' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $arrResult = $rowDb;
  }
  return $arrResult;
}//getEmployeeInfoByCode
// fungsi untuk mengambil informasi employee, berdasarkan id Employee
// $column = kolom apa aja yang ingin diambil
function getEmployeeInfoByID($db, $code, $column = "*")
{
  $arrResult = [];
  $code = trim($code);
  if ($code != "") {
    $code = "WHERE t0.id = '$code'";
  }
  if ($column == "") {
    $column = "*";
  };
  $strSQL = "SELECT $column FROM hrd_employee AS t0
                LEFT JOIN hrd_division as t1 ON t1.division_code = t0.division_code 
                LEFT JOIN hrd_department as t2 ON t2.department_code = t0.department_code 
                LEFT JOIN hrd_section as t3 ON t3.section_code = t0.section_code 
                LEFT JOIN hrd_sub_section as t4 ON t4.sub_section_code = t0.sub_section_code 
                LEFT JOIN hrd_company as t5 ON t5.id = t0.id_company 
                LEFT JOIN hrd_bank as t6 ON t6.bank_code = t0.bank2_code
                $code 
    ";
  $resDb = $db->execute($strSQL);
  if ($code != "") {
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrResult = $rowDb;
    }
  } else {
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrResult[$rowDb['id']] = $rowDb;
    }
  }
  return $arrResult;
}//getEmployeeInfoByID
// fungsi untuk mengambil user name dari user id
function getUserName($db, $code)
{
  $strReslt = "";
  if ($code != "") {
    $code = trim($code);
    $strSQL = "SELECT name FROM adm_user WHERE id_adm_user = '$code'";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strResult = $rowDb['name'];
    }
  } else {
    $strResult = "";
  }
  return $strResult;
}//getUserName
// fungsi untuk mengambil ID dari atasannya, bisa dept-head maupun group head
// pencarian bisa berdasar data id, atau data NIK (employeeID)
function getEmployeeManagerID($db, $strIDEmployee = "", $strEmployeeID = "")
{
  $intResult = -1;
  if ($strIDEmployee === "" && $strEmployeeID === "") {
    return $intResult;
  }
  // cari data karyawan
  $strSQL = "SELECT id, position_code, section_code, department_code ";
  $strSQL .= "FROM hrd_employee WHERE flag=0 ";
  if ($strIDEmployee !== "") {
    $strSQL .= "AND id = '$strIDEmployee' ";
  }
  if ($strEmployeeID !== "") {
    $strSQL .= "AND employee_id = '$strEmployeeID' ";
  }
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strDH = getSetting("department_head");
    $strGH = getSetting("group_head");
    if ($rowDb['position_code'] == $strDH) // dept head, dirinya sendiri
    {
      $intResult = $rowDb['id'];
    } else if ($rowDb['position_code'] == $strGH || $rowDb['section_code'] == "") {
      // cari dept.head sebagai manager
      $strSQL = "SELECT id FROM hrd_employee WHERE position_code = '$strDH' ";
      $strSQL .= "AND department_code = '" . $rowDb['department_code'] . "' ";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $intResult = $rowTmp['id'];
      }
    } else { // employee biasa, cari data group headnya
      // cari dept.head sebagai manager
      $strSQL = "SELECT id FROM hrd_employee WHERE position_code = '$strGH' ";
      $strSQL .= "AND section_code = '" . $rowDb['section_code'] . "' ";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $intResult = $rowTmp['id'];
      }
    }
  }
  return $intResult;
} //getEmployeeManagerID
// mengupdate data status kepegawaian dari karyawan, sesuai data yang ada di history employee
function updateEmployeeCareerData($db, $strDataID, $strIDEmployee)
{
  if ($strIDEmployee != "") {
    // cari dulu data employee, apakah ada atau tidak
    $strSQL = "SELECT * FROM hrd_employee WHERE id = '$strIDEmployee' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strStatus = $rowDb['employee_status'];
      $strJoinDate = $rowDb['join_date'];
      $strDueDate = $rowDb['due_date'];
      $strResignDate = $rowDb['resign_date'];
      $strPermanentDate = $rowDb['permanent_date'];
      $strResignDate = $rowDb['resign_date'];
      $strManagement = $rowDb['management_code'];
      $strDivision = $rowDb['division_code'];
      $strDepartment = $rowDb['department_code'];
      $strSection = $rowDb['section_code'];
      $strSubSection = $rowDb['sub_section_code'];
      $strGrade = $rowDb['salary_grade_code'];
      $strPosition = $rowDb['position_code'];
      $strActive = $rowDb['active'];
      // ambil data employee status
      $strSQL = "SELECT status_new, status_old, status_date_from, status_date_thru FROM hrd_employee_mutation_status AS t1
                    LEFT JOIN hrd_employee_mutation AS t2 ON t1.id_mutation = t2.id 
                    WHERE id_mutation = '$strDataID' AND status >= " . REQUEST_STATUS_APPROVED . " ";
      //$strSQL .= "ORDER BY t2.status_date_from DESC LIMIT 1";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $strStatus = $rowTmp['status_new'];
        if ($strStatus == 1) {
          $strPermanentDate = $rowTmp['status_date_from'];
          $strDue = "";
        } else if ($strStatus == 99) {
          $strActive = 0;
          $strStatus = $rowTmp['status_old'];
          $strResignDate = $rowTmp['status_date_from'];
        } else {
          //$strJoinDate = $rowTmp['status_date_from'];
          $strDueDate = $rowTmp['status_date_thru'];
          $strPermanentDate = "";
        }
      }
      // cek data resign
      /*
      $strSQL  = "SELECT t2.* FROM hrd_employee_mutation AS t1, hrd_employee_mutation_resign AS t2 ";
      $strSQL .= "WHERE t1.id = t2.id_mutation AND t1.id_employee = '$strIDEmployee' ";
      $strSQL .= "AND t1.status >= " .REQUEST_STATUS_APPROVED." ";
      $strSQL .= "ORDER BY t2.resign_date DESC LIMIT 1";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $strResignDate = $rowTmp['resign_date'];

      } else {
        // anggap belum resign
        $strResignDate = "";
      }*/
      // cek data jabatan
      $strSQL = "SELECT t1.* FROM hrd_employee_mutation_position AS t1
                    LEFT JOIN hrd_employee_mutation AS t2 ON t1.id_mutation = t2.id
                    WHERE id_mutation = '$strDataID' AND status >= " . REQUEST_STATUS_APPROVED . " ";
      //$strSQL .= "ORDER BY t2.position_new_date DESC LIMIT 1";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $strPosition = $rowTmp['position_new'];
        $strGrade = $rowTmp['grade_new'];
      }
      // cek data department
      $strSQL = "SELECT t1.* FROM hrd_employee_mutation_department AS t1
                    LEFT JOIN hrd_employee_mutation AS t2 ON t2.id = t1.id_mutation 
                    WHERE id_mutation = '$strDataID' AND status >= " . REQUEST_STATUS_APPROVED . " ";
      //$strSQL .= "ORDER BY t2.department_date DESC LIMIT 1";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $strManagement = $rowTmp['management_new'];
        $strDivision = $rowTmp['division_new'];
        $strDepartment = $rowTmp['department_new'];
        $strSection = $rowTmp['section_new'];
        $strSubSection = $rowTmp['sub_section_new'];
      }
      // falidasikan tanggal
      //$strJoinDate = ($strJoinDate == "") ? "NULL" : "'$strJoinDate'";
      $strDueDate = ($strDueDate == "") ? "NULL" : "'$strDueDate'";
      $strPermanentDate = ($strPermanentDate == "") ? "NULL" : "'$strPermanentDate'";
      $strResignDate = ($strResignDate == "") ? "NULL" : "'$strResignDate'";
      if (!is_numeric($strStatus)) {
        $strStatus = $rowDb['employee_status'];
      }
      // update data employee
      $strSQL = "UPDATE hrd_employee SET ";
      $strSQL .= "due_date = $strDueDate, active = $strActive, ";
      $strSQL .= "permanent_date = $strPermanentDate, resign_date = $strResignDate, ";
      $strSQL .= "employee_status = '$strStatus', position_code = '$strPosition', ";
      $strSQL .= "salary_grade_code = '$strGrade', department_code = '$strDepartment', ";
      $strSQL .= "management_code = '$strManagement', Division_code = '$strDivision', ";
      $strSQL .= "section_code = '$strSection', sub_section_code = '$strSubSection' ";
      $strSQL .= "WHERE id = '$strIDEmployee' ";
      $resExec = $db->execute($strSQL);
      // update data salary, jika ada
      // cari dulu data salary
      /*
      $fltSalary = 0;
      $strIDSalary = "";
      $strSQL  = "SELECT * FROM hrd_employee_basic_salary WHERE id_employee = '$strIDEmployee' ";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $fltSalary = $rowTmp['basic_salary'];
        $strIDSalary = $rowTmp['id'];
      }
      // cari dari mutasi, jika ada
      $strSQL  = "SELECT t2.* FROM hrd_employee_mutation AS t1, hrd_employee_mutation_salary AS t2 ";
      $strSQL .= "WHERE t1.id = t2.id_mutation AND t1.id_employee = '$strIDEmployee' ";
      $strSQL .= "AND t1.status >= " .REQUEST_STATUS_APPROVED." ";
      $strSQL .= "ORDER BY t2.salary_new_date DESC LIMIT 1";
      $resTmp = $db->execute($strSQL);
      if ($rowTmp = $db->fetchrow($resTmp)) {
        $fltSalary = $rowTmp['salaryNew'];

      }

      // update
      if ($strIDSalary == "") {
        $strSQL  = "INSERT INTO hrd_employee_basic_salary (id_employee, basic_salary) ";
        $strSQL .= "VALUES('$strIDEmployee', '$fltSalary') ";
        $resExec = $db->execute($strSQL);
      } else {
        $strSQL  = "UPDATE hrd_employee_basic_salary SET basic_salary = '$fltSalary' ";
        $strSQL .= "WHERE id_employee = '$strIDEmployee' ";
        $resExec = $db->execute($strSQL);
      }*/
    }
  }
  return true;
} // updateEmployeeCareerData
// fungsi untuk menyimpan data cuti tahunan karyawan, ke dalam leave history
// input berubah class db, ID employee, tahun
// joinDate (jika ada), lamanya kerja (jika ada) --> jika belum ada, dicari dulu
// status karyawan: untuk outsource, maksimal 12
// output, array
function saveEmployeeLeaveHistory($db, $strIDEmployee, $intYear = "", $strJoinDate = "", $strEmpStatus = 1)
{
  global $_SESSION;
  $intPCB = getSetting("pcb"); //periode cuti besar
  $intJCB = getSetting("jcb"); //jatah cuti besar
  $intJCN = getSetting("jcn"); //jatah cuti normal
  $arrResult = [
      "total"      => 0, // jatah
      "used"       => 0, // terpakai
      "holiday"    => 0, // hari libur cuti
      "remain"     => 0, // sisa cuti
      "startDate"  => "",
      "finishDate" => "",
  ];
  if ($strIDEmployee === "") {
    return $arrResult;
  }
  if ($intYear === "" || !is_numeric($intYear)) {
    $intYear = date("Y");
  }
  if ($strJoinDate == "") { // cari joinDate jika tidak disertakan
    $arrTmp = getEmployeeInfoByID(
        $db,
        $strIDEmployee,
        "join_date, employee_id, EXTRACT(year FROM AGE('$strDate', join_date)) AS kerja "
    );
    $strJoinDate = $arrTmp['join_date'];
  }
  // extrak data tanggalnya
  //$arrDate = extractDate($strDate);
  $arrJoinDate = extractDate($strJoinDate);
  // cari tanggal awal dan akhir
  $strStart = $intYear . "-" . $arrJoinDate['month'] . "-" . $arrJoinDate['day'];
  $strFinish = ($intYear + 1) . "-" . $arrJoinDate['month'] . "-" . $arrJoinDate['day'];
  $arrResult['startDate'] = $strStart;
  $arrResult['finishDate'] = $strFinish;
  $intDur = $intYear - $arrJoinDate['year'];
  $intQuota = (($intDur % $intPCB == 0) && $intDur > 0) ? $intJCB : $intJCN;
  // hitung jatah, berdasarkan selisih tahun
  //if ($intDur < 5) $intQuota = 12;
  //else if ($intDur < 10) $intQuota = 15;
  //else $intQuota = 20;
  //if ($strEmpStatus == 2 && $intQuota > 12) $intQuota = 12; // maks 12
  // cari cuti yang telah diambil
  $arrLeave = getEmployeeLeaveAnnualByYear($db, $intYear, "", $strIDEmployee);
  $intLeave = (isset($arrLeave[$strIDEmployee])) ? $arrLeave[$strIDEmployee] : 0;
  // cari hari libur yang dicatat sebagai cuti
  $intHoliday = 0;
  /*
  $strSQL  = "SELECT count(id) AS total FROM hrd_calendar WHERE status='t' AND leave='t' ";
  $strSQL .= "AND holiday BETWEEN '$strStart' AND '$strFinish' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    if ($rowDb['total'] != "") $intHoliday = $rowDb['total'];
  }
  */
  // SIMPAN DI LEAVE HISTORY
  $strModifiedByID = (isset($_SESSION['sessionUserID'])) ? $_SESSION['sessionUserID'] : "-1";
  $strSQL = "DELETE FROM hrd_leave_history WHERE id_employee = '$strIDEmployee' "; // hapus dulu
  $strSQL .= "AND \"year\" = '$intYear'; ";
  $strSQL .= "INSERT INTO hrd_leave_history (created, modified_by, created_by, ";
  $strSQL .= "id_employee, \"year\", start_date, finish_date, ";
  $strSQL .= "total, used, holiday) ";
  $strSQL .= "VALUES(now(), '$strModifiedByID', '$strModifiedByID', '$strIDEmployee', ";
  $strSQL .= "'$intYear', '$strStart', '$strFinish', '$intQuota', '$intLeave', '$intHoliday') ";
  $resExec = $db->execute($strSQL);
  $arrResult['total'] = $intQuota;
  $arrResult['used'] = $intLeave;
  $arrResult['holiday'] = $intHoliday;
  $arrResult['remain'] = $intQuota - $intLeave - $intHoliday;
  return $arrResult;
} // saveEmployeeLeaveHistory
// fungsi untuk megnhitung lembur yang terhitung sebagai cuti
// input: class db, tanggal awal dan tgl akhir (format: YYYY-MM-DD)
// output berupa integer, jumlah hari liburnya
function getLeaveHoliday($db, $strStartDate, $strFinishDate)
{
  $intResult = 0;
  if ($strStartDate == "" || $strFinishDate == "") {
    return 0;
  }
  $strSQL = "SELECT count(id) AS total FROM hrd_calendar WHERE status='t' AND leave='t' ";
  $strSQL .= "AND holiday BETWEEN '$strStartDate' AND '$strFinishDate' ";
  $resTmp = $db->execute($strSQL);
  if ($rowTmp = $db->fetchrow($resTmp)) {
    if ($rowTmp['total'] != "") {
      $intResult = $rowTmp['total'];
    }
  }
  return $intResult;
} //getLeaveHoliday
// fungsi untuk mengecek data jatah cuti tahunan karyawan, sesuai tanggal sekarang
// return berupa array
function getEmployeeLeaveQuota($db, $strIDEmployee, $strDate = "")
{
  $arrResult = [
      "currYear"    => "",
      "prevYear"    => "",
      "currQuota"   => 0, // jatah cuti tahun ini
      "prevQuota"   => 0,   // jatah cuti 1 tahun sebelumnya
      "prevRemain"  => 0,  // sisa cuti 1 tahun sebelumnya
      "currRemain"  => 0,  // sisa cuti
      "prevHoliday" => 0,  // liburan cuti 1 tahun sebelulmnya
      "currHoliday" => 0,  // liburan tahun ini
      "prevTaken"   => 0,  // yang diambil tahun lalu
      "currTaken"   => 0,  // yang diambil tahun ini
  ];
  if ($strIDEmployee == "") {
    return $arrResult;
  }
  if ($strDate == "") {
    $strDate = date("Y-m-d");
  } // default
  $dtNow = getdate();
  // cari data employee
  $arrEmp = getEmployeeInfoByID(
      $db,
      $strIDEmployee,
      "join_date, employee_status, employee_id, EXTRACT(year FROM AGE('$strDate', join_date)) AS kerja "
  );
  if (!isset($arrEmp['join_date'])) {
    return $arrResult;
  } // gak ketemu datanya
  if ($arrEmp['join_date'] == "") {
    return $arrResult;
  } // gak ada data
  if ($arrEmp['kerja'] == 0) {
    return $arrResult;
  } else {
    // sudah dapat jatah cuti
    $arrDate = extractDate($strDate);
    $arrJoinDate = extractDate($arrEmp['join_date']);
    if ((($arrJoinDate['month'] * 30) + $arrJoinDate['day']) > (($arrDate['month'] * 30) + $arrDate['day'])) {
      // belum lewat masa berlaku
      $intYear = $arrDate['year'] - 1;
      $intYearPrev = $arrDate['year'] - 2;
    } else {
      $intYear = $arrDate['year'];
      $intYearPrev = $arrDate['year'] - 1;
    }
    $arrResult['prevYear'] = $intYearPrev;
    $arrResult['currYear'] = $intYear;
    // cari data dari leave history
    $bolCurr = false;
    $bolPrev = false;
    $strSQL = "SELECT * FROM hrd_leave_history WHERE id_employee = '$strIDEmployee' ";
    $strSQL .= "AND (\"year\" = '$intYear' OR \"year\" = '$intYearPrev') ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      // cari hari libur cuti
      // cari hari libur yang dicatat sebagai cuti
      $intHoliday = getLeaveHoliday($db, $rowDb['start_date'], $rowDb['finish_date']);
      if ($rowDb['year'] == $intYear) {
        $arrResult['currQuota'] = $rowDb['total'];
        $arrResult['currTaken'] = $rowDb['used'];
        $arrResult['currHoliday'] = $intHoliday;
        $arrResult['currRemain'] = $rowDb['total'] - $intHoliday - $rowDb['used'];
        $bolCurr = true; // sudah ada data
      } else if ($rowDb['year'] == $intYearPrev) {
        $arrResult['prevQuota'] = $rowDb['total'];
        $arrResult['prevTaken'] = $rowDb['used'];
        $arrResult['prevHoliday'] = $intHoliday;
        $arrResult['prevRemain'] = $rowDb['total'] - $intHoliday - $rowDb['used'];
        $bolPrev = true;
      }
    }
    if (!$bolPrev) {
      // cari jatah bulan sebelumnya
      $arrLeave = saveEmployeeLeaveHistory(
          $db,
          $strIDEmployee,
          $intYearPrev,
          $arrEmp['join_date'],
          $arrEmp['employee_status']
      );
      $arrResult['prevQuota'] = $arrLeave['total'];
      $arrResult['prevTaken'] = $arrLeave['used'];
      $arrResult['prevHoliday'] = getLeaveHoliday($db, $arrLeave['startDate'], $arrLeave['finishDate']);
      $arrResult['prevRemain'] = $arrResult['prevQuota'] - $arrResult['prevTaken'] - $arrResult['prevHoliday'];
    }
    // jika OUTSOURCE, maka jatah kemarin tidak ada
    /*
    if ($arrEmp['employee_status'] == STATUS_OUTSOURCE)
    {
      $arrResult['prevQuota'] = 0;
      $arrResult['prevTaken'] = 0;
      $arrResult['prevHoliday'] = 0;
      $arrResult['prevRemain'] = 0;
    }
    else if ($arrEmp['kerja'] == 1) // jika baru kerja satu hari, jatah sebelumnya juga gak ada --- ANEH, khusus Artajasa kayaknya
    {
      $arrResult['prevQuota'] = 0;
      $arrResult['prevTaken'] = 0;
      $arrResult['prevHoliday'] = 0;
      $arrResult['prevRemain'] = 0;
    }*/
    if (!$bolCurr) {
      // cari jatah bulan ini
      $arrLeave = saveEmployeeLeaveHistory(
          $db,
          $strIDEmployee,
          $intYear,
          $arrEmp['join_date'],
          $arrEmp['employee_status']
      );
      $arrResult['currQuota'] = $arrLeave['total'];
      $arrResult['currTaken'] = $arrLeave['used'];
      $arrResult['currHoliday'] = getLeaveHoliday($db, $arrLeave['startDate'], $arrLeave['finishDate']);
      $arrResult['currRemain'] = $arrResult['currQuota'] - $arrResult['currTaken'] - $arrResult['currHoliday'];
    }
  }
  return $arrResult;
}//getEmployeeLeaveQuota
// fungsi untuk mengambil informasi cuti tahunan, berdasar tahun penerapan
// output berupa array
function getEmployeeLeaveAnnualByYear($db, $strYear, $strSection = "", $strIDEmployee = "")
{
  $arrResult = [];
  // langsung dipisah sesuai jenis absennya
  if ($strYear == "") {
    return $arrResult;
  }
  $strFrom = "$strYear-01-01";
  $strThru = "$strYear-12-31";
  $strSQL = "SELECT id_employee, leave_duration FROM hrd_absence AS t1 LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
                WHERE t1.status >= '" . REQUEST_STATUS_APPROVED . "' ";
  if ($strSection != "") {
    $strSQL .= "AND (t2.section_code = '$strSection') ";
  }
  if ($strIDEmployee != "") {
    $strSQL .= "AND t2.id = '$strIDEmployee' ";
  }
  $strSQL .= "AND \"leave_year\" = '$strYear' ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    // cari total waktu
    $intTotal = $rowDb['leave_duration'];
    if (isset($arrResult[$rowDb['id_employee']])) {
      $arrResult[$rowDb['id_employee']] += $intTotal;
    } else {
      $arrResult[$rowDb['id_employee']] = $intTotal;
    }
  }
  return $arrResult;
} // getEmployeeLeaveAnnualByYear
// fungsi untuk mengambil informasi sisa cuti, karyawan dengan ID tertentu
// input: id Employee
function getEmployeeLeaveRemain($db, $strIDEmployee)
{
  $intResult = 0;
  if ($strIDEmployee === "") {
    return $intResult;
  }
  $arrCuti = getEmployeeLeaveQuota($db, $strIDEmployee);
  $intLeaveQuotaPrev = $arrCuti['prevQuota'];
  $intLeaveQuotaCurr = $arrCuti['currQuota'];
  if ($intLeaveQuotaPrev == 0) {
    $intLeaveTakenPrev = 0; // anggap aja gak ada
    $intLeaveHolidayPrev = 0; //
  } else {
    $intLeaveTakenPrev = $arrCuti['prevTaken'];
    $intLeaveHolidayPrev = $arrCuti['prevHoliday'];
  }
  if ($intLeaveQuotaCurr == 0) {
    $intLeaveTakenCurr = 0; // anggap aja gak ada
    $intLeaveHolidayCurr = 0; //
  } else {
    $intLeaveTakenCurr = $arrCuti['currTaken'];
    $intLeaveHolidayCurr = $arrCuti['currHoliday'];
  }
  $intResult = $intLeaveQuotaPrev + $intLeaveQuotaCurr;
  $intResult -= ($intLeaveTakenCurr + $intLeaveTakenPrev);
  $intResult -= ($intLeaveHolidayCurr + $intLeaveHolidayPrev);
  return $intResult;
} // getEmployeeLeaveRemain
// fungsi untuk mengambil jatah pengobatan karyaawn, per hari ini
// input : idEmployee
// output: array (inpation, kacamata:lensa,frame,softlens)
function getEmployeeMedicalQuota($db, $strIDEmployee = "", $strEmployeeCompany = "", $strYear = "", $strClaimID = "")
{
  $arrResult = [];
  if ($strIDEmployee === "") {
    return $arrResult;
  }
  if ($strYear == "") {
    $strYear = date("Y");
  }
  // cari data master medical type untuk generate header tabel plafon
  $strSQL = "SELECT id, \"type\", code FROM hrd_medical_type";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrMedicalType[$rowDb['type']][$rowDb['id']] = $rowDb['code'];
  }
  // cari data plafon outpatient
  $arrPlatform = [];
  $strSQL = "SELECT amount + amount1 + amount2 AS amount FROM hrd_medical_quota_primary
                WHERE id_employee = '" . $strIDEmployee . "' AND quota_year = $strYear ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $fltQuota = $rowDb['amount'];
  }
  // cari data plafon lainnya
  $strSQL = "SELECT t1.amount, t2.\"type\", t2.code
                FROM hrd_medical_quota_secondary AS t1 LEFT JOIN hrd_medical_type AS t2 ON t1.id_medical_type = t2.id 
                WHERE id_employee = '" . $strIDEmployee . "' AND quota_year = $strYear ";
  $resDb = $db->execute($strSQL);
  //echo $strSQL;
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrQuota[$rowDb['type']][$rowDb['code']] = $rowDb['amount'];
  }
  // baca pemakaian obat semua karyawan dalam tahun yang bersangkutan
  // baca data claim  rawat jalan yang udah dilakukkan karyawan
  $arrMedicalClaim = [];
  $strSQL = "SELECT \"type\", medical_code as code, SUM(t1.approved_cost) AS total, t2.id_employee FROM hrd_medical_claim AS t1, ";
  $strSQL .= "hrd_medical_claim_master AS t2 WHERE t1.id_master = t2.id ";
  $strSQL .= "AND  t2.status <> '" . REQUEST_STATUS_DENIED . "' ";
  if ($strClaimID != "") {
    $strSQL .= "AND t1.id_master <> '$strClaimID' ";
  }
  if (isset($strIDEmployee) && $strIDEmployee != "") {
    $strSQL .= "AND t2.id_employee = " . $strIDEmployee . " ";
  }
  $strSQL .= "AND EXTRACT(year FROM claim_date) = '$strYear' ";
  $strSQL .= "GROUP BY \"type\", medical_code, t2.id_employee ";
  $resT = $db->execute($strSQL);
  while ($rowT = $db->fetchrow($resT)) {
    if ($rowT['type'] == MEDICAL_TYPE_OUTPATIENT) {
      $fltClaim = (isset($fltClaim)) ? $fltClaim += $rowT['total'] : $fltClaim = $rowT['total'];
    } else {
      $arrMedicalClaim[$rowT['type']][$rowT['code']] = (isset($arrMedicalClaim[$rowT['type']][$rowT['code']])) ? $arrMedicalClaim[$rowT['type']][$rowT['code']] += $rowT['total'] : $rowT['total'];
    }
  }
  if (!isset($fltClaim)) {
    $fltClaim = 0;
  }
  if (!isset($fltQuota)) {
    $fltQuota = 0;
  }
  $arrResult[MEDICAL_TYPE_OUTPATIENT] = $fltQuota - $fltClaim;
  foreach ($arrMedicalType AS $strType => $arrDetail) {
    foreach ($arrDetail AS $strIDMedicalType => $strCode) {
      if (!isset($arrMedicalClaim[$strType][$strCode])) {
        $arrMedicalClaim[$strType][$strCode] = 0;
      }
      $arrResult[$strType][$strCode] = $arrQuota[$strType][$strCode] - $arrMedicalClaim[$strType][$strCode];
    }
  }
  return $arrResult;
} //getEmployeeMedicalQuota
function getTimeDiff($db, $strIDEmployee)
{
  include_once("../classes/hrd/hrd_branch.php");
  $arrEmp = getEmployeeInfoByID($db, $strIDEmployee, "branch_code");
  $tblBranch = new cHrdBranch();
  $arrResult = $tblBranch->find(["branch_code" => $arrEmp['branch_code']], "local_time_difference", "", null, 1, "id");
  return (isset($arrResult['local_time_difference'])) ? $arrResult['local_time_difference'] : 0;
}

function getLateToleranceBranch($db, $strIDEmployee)
{
  include_once("../classes/hrd/hrd_branch.php");
  $arrEmp = getEmployeeInfoByID($db, $strIDEmployee, "branch_code");
  $tblBranch = new cHrdBranch();
  $arrResult = $tblBranch->find(["branch_code" => $arrEmp['branch_code']], "late_tolerance", "", null, 1, "id");
  return (isset($arrResult['late_tolerance'])) ? $arrResult['late_tolerance'] : 0;
}

?>
