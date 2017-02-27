<?php
/*
  Class untuk data karyawan, agar memudahkan proses terkait dengan karyawan (1)
  Author: Yudi (2008-12-18)
*/

// kelas karyawan
class clsEmployee
{

var $arrDetail;

  var $arrDetailException;       // id employee

    var $data; // NIK (employee_id)

var $strCode;

  var $strID; // data-data yang lebih detail, dengan index sama dengan nama column di database

    var $strName; // data-data yang lebih detail, yang bukan bagian dari tabel hrd_employee - untuk keperluan copyEmployee

  // konstruktor

  function clsEmployee($db)
  {
    // inisialisasi
    $this->data = $db;
    $this->strID = "";
    $this->strCode = "";
    $this->strName = "";
    $this->arrDetail = [];
    $this->arrDetailException = [];
  }

  // fungsi untuk mengambil data dari database berdasari NIK (employee id)
  // strFields berisi data-data apa saja yang dibutuhkan, secara default mengambil semua data
  // kriteria jika ada kriteria khusus selain NIK, misal yang aktif dsb -- dalam format AND xxxx
  // hasil disimpan di arrDetail

  function copyEmployee($strOldID, $strNewID, $strNewCode)
  {
    $bolOK = false;
    if ($strOldID == "" || $strNewID == "" || $strNewCode == "") {
      return false;
    }
    if (count($this->arrDetail) == 0) {
      $this->loadDataById($strOldID);
    }
    $strFields = "";
    foreach ($this->arrDetail AS $key => $val) {
      if ($key != "id" && $key != "employee_id" && !is_numeric($key) && !isset($this->arrDetailException[$key])) {
        $strFields .= ", \"$key\" ";
      }
    }
    $strSQL = "
        INSERT INTO hrd_employee ( id, employee_id $strFields
        )
        SELECT '$strNewID', '$strNewCode' $strFields
        FROM hrd_employee
        WHERE id = '$strOldID';
      ";
    $res = $this->data->execute($strSQL);
    if ($res != false) {
      $bolOK = true;
    }
    return $bolOK;
  }

  // fungsi untuk mengambil data dari database berdasari id employee
  // strFields berisi data-data apa saja yang dibutuhkan, secara default mengambil semua data
  // kriteria jika ada kriteria khusus selain NIK, misal yang aktif dsb -- dalam format AND xxxx
  // hasil disimpan di arrDetail

  function getInfo($strField)
  {
    if (isset($this->arrDetail[$strField])) {
      return $this->arrDetail[$strField];
    } else {
      return "";
    }
  }

  // fungsi untuk mengambil info dari data karyawan, dengan field tertentu. nama field disesuaikan dengan nama column di database
  // jika data tidak ditemukan, mungkin belum sempat di load, kirim string kosong

  function getOrganizationInfo($strID)
  {
    $arrResult = [];
    if ($strID == "") {
      return $arrResult;
    }
    $strSQL = "
        SELECT t_emp.*, t_com.company_name, t_wil.wilayah_name,
          t_div.division_name, t_dep.department_name,
          t_sec.section_name, t_sub.sub_section_name,
          t_pos.position_name
        FROM (
          SELECT 0 as tipe, '-1' AS id_organization, id AS id_employee, id_company, id_wilayah,
            division_code, department_code, section_code, sub_section_code,
            position_code, is_temporary, temporary_start_date, temporary_due_date
          FROM hrd_employee WHERE id = '$strID'
          UNION
          SELECT 1 as tipe, id AS id_organization, id_employee, id_company, id_wilayah,
            division_code, department_code, section_code, subsection_code AS sub_section_code,
            position_code, is_temporary, temporary_start_date, temporary_due_date
          FROM hrd_employee_department WHERE id_employee = '$strID'
        ) AS t_emp
        LEFT JOIN hrd_company AS t_com ON t_emp.id_company = t_com.id
        LEFT JOIN hrd_wilayah AS t_wil ON t_emp.id_wilayah = t_wil.id
        LEFT JOIN hrd_position AS t_pos ON t_emp.position_code = t_pos.position_code
        LEFT JOIN hrd_division AS t_div ON t_emp.division_code = t_div.division_code
        LEFT JOIN hrd_department AS t_dep ON (
          t_emp.division_code = t_dep.division_code AND
          t_emp.department_code = t_dep.department_code
        )
        LEFT JOIN hrd_section AS t_sec ON (
          t_emp.division_code = t_sec.division_code AND
          t_emp.department_code = t_sec.department_code AND
          t_emp.section_code = t_sec.section_code
        )
        LEFT JOIN hrd_sub_section AS t_sub ON (
          t_emp.division_code = t_sub.division_code AND
          t_emp.department_code = t_sub.department_code AND
          t_emp.section_code = t_sub.section_code AND
          t_emp.sub_section_code = t_sub.sub_section_code
        )
        ORDER BY t_emp.tipe
      ";
    $res = $this->data->execute($strSQL);
    while ($row = $this->data->fetchrow($res)) {
      foreach ($row AS $strIndex => $strValue) {
        $row[$strIndex] = trim($strValue);
      }
      $arrResult[] = $row;
    }
    return $arrResult;
  }

  // fungsi untuk menduplikat data karyawan, dengan ID yang berbeda dan NIK yang berbeda
  // input: ID lama, ID baru, NIK baru
  // output : sukes atau tidak

  function loadDataByCode($strCode, $strFields = "*", $strCriteria = "")
  {
    $this->arrDetail = []; // kosongkan dulu, reset
    if (trim($strFields) == "") {
      $strFields = "*";
    }
    if ($strFields == "*") {
      $strFields = "hrd_employee.* ";
    }
    $this->arrDetailException['education_level_name'] = "hrd_education_level";
    $strSQL = "
        SELECT $strFields,
          edu.name AS education_level_name
        FROM hrd_employee
        LEFT JOIN hrd_education_level AS edu ON hrd_employee.education_level_code = edu.code
        WHERE employee_id = '$strCode' $strCriteria
          AND active IN (0,1)
        ORDER BY active DESC
      ";
    $res = $this->data->execute($strSQL);
    if ($row = $this->data->fetchrow($res)) {
      // ambil satu saja
      $this->arrDetail = $row;
    }
  }

  /* getOrganizationInfo : fungsi untuk mengambil data struktur organisasi
        data company, wilayah, divisi, departemen, section, subsection, position,
     input  : id karyawan
     output : array - antisipasi jika ada lebih dari satu data
  */

  function loadDataById($strID, $strFields = "*", $strCriteria = "")
  {
    $this->arrDetail = []; // kosongkan dulu, reset
    if (trim($strFields) == "") {
      $strFields = "*";
    }
    if ($strFields == "*") {
      $strFields = "hrd_employee.* ";
    }
    $this->arrDetailException['education_level_name'] = "hrd_education_level";
    $strSQL = "
        SELECT $strFields ,
          edu.name AS education_level_name
        FROM hrd_employee
        LEFT JOIN hrd_education_level AS edu ON hrd_employee.education_level_code = edu.code
        WHERE id = '$strID' $strCriteria
      ";
    $res = $this->data->execute($strSQL);
    if ($row = $this->data->fetchrow($res)) {
      // ambil satu saja
      $this->arrDetail = $row;
    }
  }
} // end of class
/*
  clsEmployees : kelas untuk mengelola data multi employee
*/

class clsEmployees
{

var $arrCode;

  var $arrEmployee; // array daftar lengkap karyawan, diindex berdasarkan id

    var $data;     // daftar nik -> id, untuk memudahkan pencarian id karyawan berdasarkan NIK

  // konstruktor, inisialisasi

  function clsEmployees($db)
  {
    $this->data = $db;
    $this->arrEmployee = [];
    $this->arrCode = [];
  }

  /* loadData : fungsi untuk mengambil data lengkap karyawan
      hasil disimpan di arrEmployee
      input : strFields berisi data-data apa saja yang dibutuhkan, secara default mengambil semua data
        kriteria jika ada kriteria khusus selain NIK, misal yang aktif dsb -- dalam format AND xxxx
        arrCriteria adalah daftar id_employee dalam array ('212', '2322') dsb
  */

  function getIDByCode($strCode)
  {
    $strID = (isset($this->arrCode[$strCode])) ? $this->arrCode[$strCode] : "";
    return $strID;
  }

  /* getInfoByID : fungsi untuk mengambil info dari data karyawan, dengan field tertentu.  berdasarkan id dari karyawan
      input : id karyawan, nama field disesuaikan dengan nama column di database
      output : data sesuai field, jika data tidak ditemukan, mungkin belum sempat di load, kirim string kosong
  */

  function getInfoByCode($strCode, $strField)
  {
    $strID = (isset($this->arrCode[$strCode])) ? $this->arrCode[$strCode] : "";
    return $this->getInfoByID($strID, $strField);
  }

  /* getInfoByCode : fungsi untuk mengambil info dari data karyawan, dengan field tertentu.  berdasarkan NIK dari karyawan
      input : NIK karyawan, nama field disesuaikan dengan nama column di database
      output : data sesuai field, jika data tidak ditemukan, mungkin belum sempat di load, kirim string kosong
  */

  function getInfoByID($strID, $strField)
  {
    if (isset($this->arrEmployee[$strID][$strField])) {
      return $this->arrEmployee[$strID][$strField];
    } else {
      return "";
    }
  }

  /* getIDByCode : fungsi untuk mengambil id karyawan, berdasarkan NIK dari karyawan
      input : NIK karyawan
      output : id karyawan, jika data tidak ditemukan, mungkin belum sempat di load, kirim string kosong
  */

  function getProbationEmployee($strDueDate = "", $strCriteria = "")
  {
    $arrResult = [];
    if ($strDueDate != "") {
      $strCriteria .= " AND (probation_finish_date > '$strDueDate' AND (probation_finish_date - interval '1 mon') < '$strDueDate') ";
    }
    $strDate = ($strDueDate == "") ? "CURRENT_DATE" : "'$strDueDate'";
    $strActiveCriteria = " AND ((active = 1) OR (active = 0 AND resign_date > CURRENT_DATE) )";
    $strSQL = "
          SELECT *, ((probation_finish_date) - $strDate) AS selisih,
            CASE WHEN (probation_finish_date = $strDate) THEN 1 ELSE 0 END AS sekarang
          FROM hrd_employee
          WHERE employee_status = " . STATUS_PERMANENT . " AND is_probation = 't'
            AND 1=1 AND flag = 0 $strActiveCriteria $strCriteria
          ORDER BY probation_finish_date DESC, employee_name
      ";
    $res = $this->data->execute($strSQL);
    while ($row = $this->data->fetchrow($res)) {
      $arrResult[$row['id']] = $row;
    }
    return $arrResult;
  }

  /* getTransitionEmployee : fungsi untuk mengambil data karyawan yang sedang menjalani masa transisi
      input  : tanggal (awal dan akhir) (jika kosong berarti hari ini), kriteria karyawan (dalam sintak query: AND xxx )
      output : array, dengan index adalah ID karyawan, datanya adalah NIK, tanggal awal dan tanggal akhir transisi
  */

  function getTransitionEmployee($strDateFrom = "", $strDateTo = "", $strCriteria = "")
  {
    include_once("mutation_functions.php");
    if ($strDateFrom == "") {
      $strDateFrom = date("Y-m-d");
    }
    if ($strDateTo == "") {
      $strDateTo = date("Y-m-d");
    }
    $arrResult = [];
    $strActiveCriteria = " AND ((active = 1) OR (active = 0 AND resign_date > CURRENT_DATE) )";
    $strSQL = "
        SELECT t1.status_date_from, t1.status_date_thru, 
          t2.id_employee, t3.employee_id, t3.employee_name
        FROM (
          SELECT * FROM hrd_employee_mutation_status
          WHERE mutation_type = '" . MUT_TRANSITION . "'
          AND (
            '$strDateFrom' BETWEEN status_date_from AND status_date_thru
            OR 
            '$strDateTo' BETWEEN status_date_from AND status_date_thru
          )
        ) AS t1 
        INNER JOIN (
          SELECT * FROM hrd_employee_mutation
          WHERE status >= '" . REQUEST_STATUS_APPROVED . "'
        ) AS t2 ON t1.id_mutation = t2.id
        INNER JOIN (
          SELECT * FROM hrd_employee
          WHERE 1=1 $strActiveCriteria $strCriteria
        ) AS t3 ON t2.id_employee = t3.id
        ORDER BY t3.employee_name
      ";
    $res = $this->data->execute($strSQL);
    while ($row = $this->data->fetchrow($res)) {
      $arrResult[$row['id_employee']] = $row;
    }
    return $arrResult;
  }

  /* getProbationEmployee : fungsi untuk mengambil data karyawan yang akan habis waktu percobaannya, limit 1 bulan sebelumnya
      input  : tanggal batas (jika kosong berarti semua), kriteria karyawan (dalam sintak query: AND xxx )
      output : array, dengan index adalah ID karyawan, datanya adalah NIK, tanggal awal dan tanggal akhir transisi
  */

  function loadData($strFields = "*", $strCriteria = "", $arrCriteria = [])
  {
    $this->arrEmployee = []; // kosongkan dulu, reset
    if (trim($strFields) == "") {
      $strFields = "*";
    }
    if (isset($arrCriteria) && count($arrCriteria) > 0) {
      $strTmp = "";
      foreach ($arrCriteria AS $x => $strEmp) {
        $strTmp .= ($strTmp == "") ? "'$strEmp'" : ", '$strEmp'";
      }
      if ($strTmp != "") {
        $strCriteria .= " AND id IN ($strTmp) ";
      }
    }
    $strSQL = "
        SELECT $strFields FROM hrd_employee
        WHERE 1=1 $strCriteria
      ";
    $res = $this->data->execute($strSQL);
    while ($row = $this->data->fetchrow($res)) {
      // ambil satu saja
      $this->arrEmployee[$row['id']] = $row;
      $this->arrCode[$row['employee_id']] = $row['id'];
    }
  }
}

?>