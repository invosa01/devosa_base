<?php
/*
  KELAS UNTUK MELAKUKAN PERHITUNGAN BONUS
  Update:
    - 2009.02.01 (Yudi)
*/
include_once("../global/cls_date.php");

/*
  clsBonusCalculation : kelas untuk melakukan perhitungan bonus karyawan
*/

class clsBonusCalculation
{

  var $arrCompany; // kelas database

  var $arrDivision;     // data ID dari master bonus

  var $arrEmployee;   // tanggal perhitungan bonus

  var $data;// tanggal perhitungan pajak,jika ada

  var $fltShare;     // status perhitungan bonus

  var $intStatus;    // array daftar company, atau entitas yang mendapat hak bonus, karena gak semua

  var $strDataDate;   // array data karyawan

  var $strDataID;   // array data divisi

  var $strDataTaxDate;      // nilai share service

  /* inisialisasi, konstruktor
      jika sudah ada data id master, bisa langsung diset, agar langsung mengambil data
  */

  function clsBonusCalculation($db, $strID = "")
  {
    // inisialisasi
    $this->data = $db;
    $this->strDataID = $strID;
    $this->strDataDate = date("Y-m-d");
    $this->strDataTaxDate = "";
    $this->intStatus = 0;
    $this->fltShare = 0;
    // ambil data perhitungan gaji, jika ada
    $this->initData();
  }

  /* initData : fungsi untuk mengambil data perhitungan bonus, jika data sudah ada
      jika belum ada, lakukan inisialisasi, khususnya ambil daftar departement
  */

  function getEmployeeList()
  {
    $this->arrEmployee = [];
    if ($this->strDataID != "") {
      $strSQL = "
          SELECT t1.*, t2.employee_name
          FROM hrd_bonus_employee As t1
          LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id
          WHERE t1.id_bonus = '" . $this->strDataID . "'
        ";
      $res = $this->data->execute($strSQL);
      while ($row = $this->data->fetchrow($res)) {
        $this->arrEmployee[$row['id_employee']] = $row;
      }
    }
  }

  /* initCompany : inisialisasi daftar company yang berhak mendapat bonus (private)
      simpan di arrCompany
  */

  function initCompany()
  {
    $strSQL = "
        SELECT * FROM hrd_company WHERE bonus = 't'
      ";
    $res = $this->data->execute($strSQL);
    while ($row = $this->data->fetchrow($res)) {
      $this->arrCompany[$row['id']] = $row['company_name'];
    }
  }

  /* getEmployeeList : fungsi untuk mengambil daftar karyawan yang mendapat bonus
        simpan di arrEmployee. sengaja tidak otomatis waktu init, biar tidak berat di loading awal
  */

  function initData()
  {
    $this->arrDivision = [];
    $this->arrEmployee = [];
    $this->arrCompany = [];
    // ambil data master jika ada
    if ($this->strDataID != "") {
      $strSQL = "SELECT * FROM hrd_bonus WHERE id = '" . $this->strDataID . "' ";
      $res = $this->data->execute($strSQL);
      if ($row = $this->data->fetchrow($res)) {
        $this->strDataDate = $row['bonus_date'];
        $this->strDataTaxDate = $row['tax_date'];
        $this->intStatus = $row['status'];
      } else {
        $this->strDataID = "";
      } // anggap gak ada
    }
    // ambil data divisi
    if ($this->strDataID == "") // data baru
    {
      // ambil dari master data saja
      $strSQL = "
          SELECT *, 'f' as share_service, 0 as amount
          FROM hrd_division ORDER BY division_code
        ";
    } else {
      // ambil dari data perhitungan bonus
      $strSQL = "
          SELECT t1.*, t2.division_name
          FROM hrd_bonus_division AS t1
          LEFT JOIN hrd_division AS t2 ON t1.division_code = t2.division_code
          WHERE t1.id_bonus = '" . $this->strDataID . "'
        ";
    }
    $res = $this->data->execute($strSQL);
    while ($row = $this->data->fetchrow($res)) {
      $this->arrDivision[$row['division_code']] = $row;
    }
    $this->initCompany(); // inisialisasi daftar company yang berhak
  }

  /* initEmployeeList : fungsi untuk mengambil daftar karyawan yang berhak mendapat bonus (private)
        yang berhak adalah yang join di divisi tersebut selama >= 1 tahun
      input   : tanggal
      output  : array daftar karyawan, termasuk basic salary-nya
  */

  function initEmployeeList($strDate)
  {
    $arrResult = [];
    if ($strDate == "") {
      return $arrResult;
    }
    $arrMut = []; // menyimpan info mutasi karyawan
    // ambil data perpindahan karyawan ke departemen tertentu, yang lebih kurang dari satu tahun
    $strSQL = "
        SELECT t1.*, t2.id_employee
        FROM (
          SELECT * FROM hrd_employee_mutation_department
          WHERE EXTRACT(year FROM AGE('$strDate', department_date)) < 1
            AND (division_new != '') AND NOT (division_new IS NULL)
            AND (division_new != division_old)
        ) AS t1
        INNER JOIN (
          SELECT * FROM hrd_employee_mutation
          WHERE status = '2' -- approved
        ) AS t2 ON t1.id_mutation = t2.id
        INNER JOIN (
          SELECT id, employee_name, employee_id, join_date, division_code, id_company
          FROM hrd_employee 
          WHERE active = 1
            AND EXTRACT(year FROM AGE('$strDate', join_date)) >= 1
        ) AS t3 ON t2.id_employee = t3.id
        
      ";
    $res = $this->data->execute($strSQL);
    while ($row = $this->data->fetchrow($res)) {
      if (isset($arrMut[$row['id_employee']])) { // bandingkan dulu tanggalnya, ambil yang paling lama
        if ($row['department_date'] > $arrMut[$row['id_employee']]['department_date']) // update
        {
          $arrMut[$row['id_employee']] = $row;
        }
      } else {
        $arrMut[$row['id_employee']] = $row;
      }
    }
    // untuk sementara, ambil sesuai join date dulu
    $strSQL = "
        SELECT t1.id, t1.employee_id, t1.employee_name, t1.join_date, 
          t1.division_code, t1.self_service , t2.basic_salary, t1.id_company
        FROM hrd_employee AS t1
        LEFT JOIN hrd_employee_basic_salary AS t2 ON t1.id = t2.id_employee
        WHERE t1.active = 1 
          AND EXTRACT(year FROM AGE('$strDate', t1.join_date)) >= 1
      ";
    $res = $this->data->execute($strSQL);
    while ($row = $this->data->fetchrow($res)) {
      if (!isset($arrMut[$row['id']])) {
        $arrResult[$row['id']] = $row;
      }
    }
    return $arrResult;
  }

  /* setDivisionValue : fungsi untuk memberi nilai bonus pada data divisi
      data disimpan di arrDivision
      input : kode divisi, jumlah, share service (boolean)
  */

  function saveData()
  {
    $bolOK = true;
    // proses save data
    $this->data->execute("begin");
    $strUserID = $_SESSION['sessionUserID'];
    // save data master dulu
    $strTaxDate = ($this->strDataTaxDate == "") ? "NULL" : "'" . $this->strDataTaxDate . "'";
    if ($this->strDataID == "") // insert new
    {
      $this->strDataID = $this->data->getNextID("hrd_salary_master_id_seq");
      $strSQL = "
          INSERT INTO hrd_bonus (
            id, created_time, created_by, bonus_date, tax_date, status
          )
          VALUES (
            '" . $this->strDataID . "', now(), '$strUserID', '" . $this->strDataDate . "',
            $strTaxDate, 0
          );
        ";
    } else {
      $strSQL = "
          UPDATE hrd_bonus
          SET bonus_date = '" . $this->strDataDate . "', tax_date = $strTaxDate
          WHERE id = '" . $this->strDataID . "';
        ";
    }
    $resExec = $this->data->execute($strSQL);
    if ($resExec == false) {
      $bolOK = false;
    }
    // save data master divisi
    $intShare = 0;
    $fltTotalShare = 0;
    $fltShare = 0;
    if ($bolOK) {
      // hapus dulu
      $strSQL = "
          DELETE FROM hrd_bonus_division WHERE id_bonus = '" . $this->strDataID . "';
        ";
      $resExec = $this->data->execute($strSQL);
      if ($resExec == false) {
        $bolOK = false;
      }
      $strSQL = "";
      foreach ($this->arrDivision AS $strCode => $arrD) {
        $strSQL .= "
            INSERT INTO hrd_bonus_division (
              id_bonus, division_code, share_service, amount, modified_by
            )
            VALUES (
              '" . $this->strDataID . "', '" . $arrD['division_code'] . "',
              '" . $arrD['share_service'] . "', '" . $arrD['amount'] . "',
              '$strUserID'
            );
          ";
        if ($arrD['share_service'] == 't') {
          $intShare++;
          $fltTotalShare += $arrD['amount'];
        }
      }
      if ($strSQL != "") {
        $resExec = $this->data->execute($strSQL);
        if ($resExec == false) {
          $bolOK = false;
        }
      }
    }
    // hitung nilai share service
    if ($intShare > 0 && $fltTotalShare <> 0) {
      $fltShare = ($fltTotalShare / $intShare);
    }
    $this->fltShare = $fltShare;
    // save data data karyawan yang berhak mendapat bonus
    if ($bolOK) {
      $strSQL = "
          DELETE FROM hrd_bonus_employee WHERE id_bonus = '" . $this->strDataID . "';
        ";
      $resExec = $this->data->execute($strSQL);
      if ($resExec == false) {
        $bolOK = false;
      }
      $arrEmployee = $this->initEmployeeList($this->strDataDate);
      $strSQL = "";
      foreach ($arrEmployee AS $strID => $arrInfo) {
        $fltAmount = 0;
        $bolShare = 'f';
        if ($arrInfo['self_service'] == '1') // share service
        {
          $bolShare = 't';
          $fltAmount = ($arrInfo['basic_salary'] * ($fltShare / 100));
        } else if (isset($this->arrDivision[$arrInfo['division_code']]['amount']) && isset($this->arrCompany[$arrInfo['id_company']])) {
          $fltAmount = ($arrInfo['basic_salary'] * ($this->arrDivision[$arrInfo['division_code']]['amount'] / 100));
        }
        if ($fltAmount <> 0) {
          $strSQL .= "
              INSERT INTO hrd_bonus_employee (
                id_bonus, id_employee, division_code, share_service,
                basic_salary, amount
              )
              VALUES (
                '" . $this->strDataID . "', '$strID', '" . $arrInfo['division_code'] . "',
                '$bolShare', '" . $arrInfo['basic_salary'] . "', '$fltAmount'
              );
            ";
        }
      } // foreach
      if ($strSQL != "") {
        $resExec = $this->data->execute($strSQL);
        if ($resExec == false) {
          $bolOK = false;
        }
      }
    }
    if ($bolOK) {
      $this->data->execute("commit");
    } else {
      $this->data->execute("rollback");
    }
    return $bolOK;
  }

  /* saveData : fungsi untuk menyimpan data bonus, baik data master maupun detail
      tahap: simpan data master, simpan data kinerja departement, simpan data karyawan yang mendapat bonus
      output : sukses / tidak
  */

  function setApproved()
  {
    if ($this->strDataID != "") {
      $intStatus = SALARY_CALCULATION_APPROVED;
      $strSQL = "
          UPDATE hrd_bonus SET status = '" . $intStatus . "'
          WHERE id = '" . $this->strDataID . "';
        ";
      $resExec = $this->data->execute($strSQL);
      if ($resExec != false) {
        $this->intStatus = $intStatus;
      }
    }
  }

  /* setFinish : fungsi untuk menyatakan bahwa perhitungan bonus sudah dianggap finish/closed
       mengubah status perhitungan bonus yang sekarang menjadi finish
  */

  function setDivisionValue($strCode, $fltAmount, $bolShare = false)
  {
    if (isset ($this->arrDivision[$strCode])) {
      $this->arrDivision[$strCode]['amount'] = (is_numeric($fltAmount)) ? $fltAmount : 0;
      $this->arrDivision[$strCode]['share_service'] = ($bolShare) ? "t" : "f";
    }
  }

  /* setApproved : fungsi untuk  mengubah status perhitungan bonus yang sekarang menjadi telah disetujui
  */

  function setFinish()
  {
    if ($this->strDataID != "") {
      $intStatus = SALARY_CALCULATION_FINISH;
      $strSQL = "
          UPDATE hrd_bonus SET status = '" . $intStatus . "'
          WHERE id = '" . $this->strDataID . "';
        ";
      $resExec = $this->data->execute($strSQL);
      if ($resExec != false) {
        $this->intStatus = $intStatus;
      }
    }
  }
}

?>