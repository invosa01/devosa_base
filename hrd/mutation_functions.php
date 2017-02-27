<?php
/*
  DAFTAR FUNGSI DAN KONSTANTA UNTUK KEPERLUAN
  FORM P3K (PEMBERITAHUAN PERUBAHAN PENGEMBANGAN KARIR)
  BY: YUDI K (2008-12-17)
*/
// konstanta
define("MUT_PROBATION", "0"); // lulus/tidak masa percobaan
define("MUT_CONTRACT", "1"); // perpanjangan kontrak
define("MUT_ROTATION", "2"); // perubahan nama jabatan
define("MUT_PROMOTION", "3"); // perubahan jabatan
define("MUT_CONTRACT_NEW", "4"); // kontrak baru (setelah masa transisi)
define("MUT_PERMANENT", "5"); // menjadi karyawan tetap
define("MUT_DIVISION", "6"); // pindah divisi/dept/company
define("MUT_RESIGN", "7"); // mengundurkan diri
define("MUT_BIRTH", "8"); // kelahiran
define("MUT_MARRIED", "9"); // nikah
define("MUT_ADDRESS", "10"); // pindah alamat
define("MUT_DEATH", "11"); // meninggal
define("MUT_DIVORCE", "12"); // cerai
define("MUT_ACTING", "13"); // pejabat sementara
define("MUT_TRANSITION", "14"); // untuk menyatakan bahwa karyawan sedang menjalani masa transisi
define("MUT_BANK_ACCOUNT", "15"); // untuk perubahan data nomor rekening
define("MUT_ALLOWANCE", "16"); // untuk penambahan tunjangan, dimasukkan sebagai perubahan data
define("MUT_TOTAL", 17); // jumlah total kasus mutasi yang ditangani
// fungsi untuk menampilkan data (dalam bentuk pencarian data) - list
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getDataMutation($db, $strDataDateFrom, $strDataDateThru, &$intRows, $strKriteria = "", $strOrder = "")
{
  global $words;
  global $ARRAY_EMPLOYEE_STATUS;
  global $ARRAY_REQUEST_STATUS;
  global $bolPrint;
  $intRows = 0;
  $strResult = "";
  $bolViewTransition = $_SESSION['sessionIsTransition'];
  if ($strDataDateFrom == "" && $strDataDateThru == "") {
    // hanya ambil yang approval saja
    $strKriteria .= "AND status = '" . REQUEST_STATUS_CHECKED . "' ";
  } else {
    $strKriteria .= "AND proposal_date BETWEEN '$strDataDateFrom' AND '$strDataDateThru'  ";
  }
  if (!$bolViewTransition) {
    // tidak boleh melihat info transisi
    $strKriteria .= "
        AND id NOT IN (
          SELECT id_mutation FROM hrd_employee_mutation_status
          WHERE mutation_type = '" . MUT_TRANSITION . "'
            AND id_mutation IN (
              SELECT id FROM hrd_employee_mutation WHERE 1=1 $strKriteria
            )
        )
      ";
  }
  // ambil daftar nama company berdasarkan id
  $arrCompany = [];
  $strSQL = "
      SELECT id, company_name FROM hrd_company 
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrCompany[$rowDb['id']] = $rowDb['company_name'];
  }
  // ambil daftar nama wilayah berdasarkan id
  $arrWilayah = [];
  $strSQL = "
      SELECT id, wilayah_name FROM hrd_wilayah 
    ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrWilayah[$rowDb['id']] = $rowDb['wilayah_name'];
  }
  // ambil dulu data detail perubahan, kumpulkan dalam array
  $arrInfoDetail = []; // terkait perubahan data
  // sql untuk mencari ID mutation
  $strSQLSearch = "
      SELECT t1.id
      FROM (
        SELECT * FROM hrd_employee_mutation 
        WHERE \"type\"=0 $strKriteria 
      ) AS t1 
      LEFT JOIN hrd_employee AS t2 ON t1.employee_id = t2.employee_id 
             
    ";
  $arrDetailTable = [
      "hrd_employee_mutation_status",
      "hrd_employee_mutation_department",
      "hrd_employee_mutation_position",
      "hrd_employee_mutation_data"
  ];
  foreach ($arrDetailTable AS $x => $strTable) {
    // cari data perubahan status
    $strSQL = "
        SELECT * FROM \"$strTable\"
        WHERE id_mutation IN (
          $strSQLSearch
        )
        ORDER BY mutation_type
      ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrInfoDetail[$rowDb['id_mutation']][] = $rowDb;
    }
  }
  // ambil data mutasi
  $i = 0;
  $strSQL = "
      SELECT t1.*, t2.employee_name, t2.gender,
        t2.position_code, t2.grade_code, 
        t2.employee_status, t2.division_name,
        t2.department_name
      FROM (
        SELECT * FROM hrd_employee_mutation 
        WHERE \"type\"=0 $strKriteria 
      ) AS t1 
      LEFT JOIN (
        SELECT emp.id, emp.grade_code, emp.position_code, emp.employee_status,
          emp.employee_id, emp.employee_name, emp.gender, 
          div.division_name, dep.department_name
        FROM hrd_employee AS emp
        LEFT JOIN hrd_division AS div ON emp.division_code = div.division_code
        LEFT JOIN hrd_department AS dep ON emp.department_code = dep.department_code AND emp.division_code = dep.division_code
        
      ) AS t2 ON t1.id_employee = t2.id 
      ORDER BY $strOrder t1.proposal_date, t2.employee_id 
    "; // bingung
  $resDb = $db->execute($strSQL);
  $strDateOld = "";
  while ($rowDb = $db->fetchrow($resDb)) {
    $intRows++;
    //($rowDb['gender'] == 0) ? $strGender = $words['female'] : $strGender = $words['male'];
    $strClass = getCssClass($rowDb['status']);
    if ($rowDb['status'] >= REQUEST_STATUS_APPROVED) {
      $strClass = "";
    }
    $strEmployeeInfo = $rowDb['employee_id'] . " - " . $rowDb['employee_name'];
    $strChanges = ""; // informasi perubahan yang ada
    $strPersonal = ""; // ini khusus untuk perubahan data pribadi
    if (isset($arrInfoDetail[$rowDb['id']])) {
      foreach ($arrInfoDetail[$rowDb['id']] AS $x => $arrDb) {
        if ($arrDb['mutation_type'] == MUT_PROBATION) {
          if ($strChanges != "") {
            $strChanges .= "<br />\n";
          }
          $strChanges .= "Probation : " . (($arrDb['status_old'] == $arrDb['status_new']) ? (($arrDb['contract_month'] > 0) ? 'Perpanjangan ' . $arrDb['contract_month'] . ' bulan' : 'Tidak Lulus') : "Lulus");
        } else if ($arrDb['mutation_type'] == MUT_CONTRACT) {
          if ($strChanges != "") {
            $strChanges .= "<br />\n";
          }
          $strChanges .= "Perpanjangan Kontrak : " . ((isset($ARRAY_EMPLOYEE_STATUS[$arrDb['status_new']])) ? getWords(
                  $ARRAY_EMPLOYEE_STATUS[$arrDb['status_new']]
              ) : "") . " &nbsp;";
          if ($arrDb['contract_month'] > 0) {
            $strChanges .= " Periode : " . $arrDb['contract_month'] . " bulan &nbsp;";
          }
          $strChanges .= "Mulai : " . pgDateFormat($arrDb['status_date_from'], "d-M-Y");
        } else if ($arrDb['mutation_type'] == MUT_CONTRACT_NEW) {
          if ($strChanges != "") {
            $strChanges .= "<br />\n";
          }
          $strChanges .= "Kontrak baru  ";//. ((isset($ARRAY_EMPLOYEE_STATUS[$arrDb['status_new']])) ? getWords($ARRAY_EMPLOYEE_STATUS[$arrDb['status_new']]) : ""). " &nbsp;";
          if ($arrDb['contract_month'] > 0) {
            $strChanges .= " Periode : " . $arrDb['contract_month'] . " bulan &nbsp;";
          }
          $strChanges .= "Mulai : " . pgDateFormat($arrDb['status_date_from'], "d-M-Y") . " &nbsp;";
          $strNewNIK = ($arrDb['new_employee_id'] == "") ? "[<a class='bgError' href='mutation_edit.php?dataID=" . $rowDb['id'] . "'>" . getWords(
                  'edit new ID'
              ) . "</a>]" : $arrDb['new_employee_id'];
          $strChanges .= "<br>\n NIK : " . $arrDb['old_employee_id'] . " menjadi " . $strNewNIK;
        } else if ($arrDb['mutation_type'] == MUT_TRANSITION) {
          if ($strChanges != "") {
            $strChanges .= "<br />\n";
          }
          $strChanges .= "Transisi  Mulai : " . pgDateFormat($arrDb['status_date_from'], "d-M-Y");
          $strChanges .= "&nbsp; Sampai : " . pgDateFormat($arrDb['status_date_thru'], "d-M-Y");
        } else if ($arrDb['mutation_type'] == MUT_PERMANENT) {
          if ($strChanges != "") {
            $strChanges .= "<br />\n";
          }
          $strChanges .= "Menjadi Karyawan Tetap " . pgDateFormat($arrDb['status_date_from'], "d-M-Y");
        } else if ($arrDb['mutation_type'] == MUT_RESIGN) {
          if ($strChanges != "") {
            $strChanges .= "<br />\n";
          }
          $strChanges .= "Mengundurkan diri " . pgDateFormat($arrDb['status_date_from'], "d-M-Y");
        } else if ($arrDb['mutation_type'] == MUT_ROTATION) {
          if ($strChanges != "") {
            $strChanges .= "<br />\n";
          }
          $strChanges .= "Rotasi Mulai " . pgDateFormat($arrDb['position_new_date'], "d-M-Y");
          $strC = "";
          /*
          if ($arrDb['division_new'] != "") $strC .= " ".$arrDb['division_new']." (DIV) ";
          if ($arrDb['department_new'] != "") $strC .= " ".$arrDb['department_new']." (DEPT) ";
          if ($arrDb['section_new'] != "") $strC .= " ".$arrDb['section_new']." (SECT) ";
          */
          if ($arrDb['position_new'] != "") {
            $strC .= " " . $arrDb['position_new'] . "  ";
          }
          if ($strC != "") {
            $strChanges .= "<br />\n $strC ";
          }
        } else if ($arrDb['mutation_type'] == MUT_DIVISION) {
          $strChangesType = "";
          if ($rowDb['new_position_type'] == 1) {
            $strChangesType = " (Rangkap) ";
          } else if ($rowDb['new_position_type'] == 2) {
            $strChangesType = " (Berhenti) ";
          }
          if ($strChanges != "") {
            $strChanges .= "<br />\n";
          }
          $strChanges .= "Mutasi $strChangesType Mulai " . pgDateFormat($arrDb['department_date'], "d-M-Y");
          $strC = "";
          if ($arrDb['division_new'] != "") {
            $strC .= " " . $arrDb['division_new'] . " (DIV) ";
          }
          if ($arrDb['department_new'] != "") {
            $strC .= " " . $arrDb['department_new'] . " (DEPT) ";
          }
          if ($arrDb['section_new'] != "") {
            $strC .= " " . $arrDb['section_new'] . " (UNIT) ";
          }
          if ($arrDb['subsection_new'] != "") {
            $strC .= " " . $arrDb['subsection_new'] . " (SECT) ";
          }
          if ($strC != "") {
            $strChanges .= "<br />\n $strC ";
          }
          if ($arrDb['company_new'] != "") {
            $strChanges .= "<br />\n " . ((isset($arrCompany[$arrDb['company_new']])) ? $arrCompany[$arrDb['company_new']] : "");
          }
          if ($arrDb['wilayah_new'] != "") {
            $strChanges .= " - " . ((isset($arrWilayah[$arrDb['wilayah_new']])) ? $arrWilayah[$arrDb['wilayah_new']] : "");
          }
        } else if ($arrDb['mutation_type'] == MUT_PROMOTION) {
          $strChangesType = "Promosi Menjadi " . $arrDb['position_new'];
          if ($rowDb['new_position_type'] == 1) {
            $strChangesType = "Rangkap Jabatan " . $arrDb['position_new'];
          } else if ($rowDb['new_position_type'] == 2) {
            $strChangesType = "Berhenti Sebagai " . $arrDb['position_old'];
          }
          if ($strChanges != "") {
            $strChanges .= "<br />\n";
          }
          $strChanges .= $strChangesType; //"Promosi Menjadi " . $arrDb['position_new'];
          if ($arrDb['grade_new'] != "") {
            $strChanges .= ", Grade " . $arrDb['grade_new'] . ", ";
          }
          $strChanges .= " &nbsp; Mulai " . pgDateFormat($arrDb['position_new_date'], "d-M-Y");
        } else if ($arrDb['mutation_type'] == MUT_ACTING) {
          $strChangesType = "Pejabat Sementara Menjadi " . $arrDb['position_new'];
          if ($rowDb['new_position_type'] == 1) {
            $strChangesType = "Rangkap Jabatan Sementara " . $arrDb['position_new'];
          } else if ($rowDb['new_position_type'] == 2) {
            $strChangesType = "Berhenti Sebagai " . $arrDb['position_old'];
          }
          if ($strChanges != "") {
            $strChanges .= "<br />\n";
          }
          $strChanges .= $strChangesType; //"Pejabat Sementara " . $arrDb['position_new'];
          if ($arrDb['grade_new'] != "") {
            $strChanges .= ", Grade " . $arrDb['grade_new'] . ", ";
          }
          $strChanges .= " &nbsp; Mulai " . pgDateFormat($arrDb['position_new_date'], "d-M-Y");
          $strChanges .= " &nbsp; Sampai " . pgDateFormat($arrDb['position_duedate'], "d-M-Y");
        } else if ($arrDb['mutation_type'] == MUT_ADDRESS) {
          if ($strPersonal != "") {
            $strPersonal .= "<br />\n";
          }
          $strPersonal .= "* Ganti Alamat : " . nl2br($arrDb['note']);
          if ($arrDb['note2'] != "") {
            $strPersonal .= " " . $arrDb['note2'];
          }
        } else if ($arrDb['mutation_type'] == MUT_BIRTH) {
          if ($strPersonal != "") {
            $strPersonal .= "<br />\n";
          }
          $strPersonal .= "* Kelahiran Tanggal " . pgDateFormat($arrDb['event_date'], "d-M-Y");
          if ($arrDb['note'] != "") {
            $strPersonal .= " Nama " . $arrDb['note'];
          }
        } else if ($arrDb['mutation_type'] == MUT_MARRIED) {
          if ($strPersonal != "") {
            $strPersonal .= "<br />\n";
          }
          $strPersonal .= "* Menikah Tanggal " . pgDateFormat($arrDb['event_date'], "d-M-Y");
          if ($arrDb['note'] != "") {
            $strPersonal .= " Dengan " . $arrDb['note'];
          }
        } else if ($arrDb['mutation_type'] == MUT_DEATH) {
          if ($strPersonal != "") {
            $strPersonal .= "<br />\n";
          }
          $strPersonal .= "* Meninggal Tanggal " . pgDateFormat($arrDb['event_date'], "d-M-Y");
          if ($arrDb['note'] != "") {
            $strPersonal .= " Nama " . $arrDb['note'];
          }
        } else if ($arrDb['mutation_type'] == MUT_DIVORCE) {
          if ($strPersonal != "") {
            $strPersonal .= "<br />\n";
          }
          $strPersonal .= "* Cerai Tanggal " . pgDateFormat($arrDb['event_date'], "d-M-Y");
          //if ($arrDb['note'] != "") $strPersonal .= " Nama ".$arrDb['note'];
        } else if ($arrDb['mutation_type'] == MUT_BANK_ACCOUNT) {
          if ($strPersonal != "") {
            $strPersonal .= "<br />\n";
          }
          $strPersonal .= "* Perubahan Rekening BCA Tanggal " . pgDateFormat($arrDb['event_date'], "d-M-Y");
          if ($arrDb['note'] != "") {
            $strPersonal .= " No. " . $arrDb['note'];
          }
        } else if ($arrDb['mutation_type'] == MUT_ALLOWANCE) {
          if ($strPersonal != "") {
            $strPersonal .= "<br />\n";
          }
          if ($arrDb['note2'] == 1) {
            $strPersonal .= "* Perubahan Tunjangan " . $arrDb['note'];
          } else if ($arrDb['note2'] == 2) {
            $strPersonal .= "* Pencabutan Tunjangan " . $arrDb['note'];
          } else {
            $strPersonal .= "* Penambahan Tunjangan " . $arrDb['note'];
          }
          $strPersonal .= " Tanggal " . pgDateFormat($arrDb['event_date'], "d-M-Y");
        }
      }
    }
    // tambahkan info personal ke data perubahan
    if ($strPersonal != "") {
      if ($strChanges != "") {
        $strChanges .= "<br />\n";
      }
      $strChanges .= "Perubahan Data Karyawan <br />\n" . $strPersonal;
    }
    // tampilkan data
    $strResult .= "<tr valign=top title=\"$strEmployeeInfo\" class=$strClass>\n";
    if (!$bolPrint) {
      $strResult .= "  <td title='key=" . $rowDb['id'] . "'><input type=checkbox name='chkID$intRows' value=\"" . $rowDb['id'] . "\" title=\" key = " . $rowDb['id'] . "\"></td>\n";
    }
    $strResult .= "  <td align=center>" . pgDateFormat($rowDb['proposal_date'], "d-M-Y") . "&nbsp;</td>\n";
    $strResult .= "  <td title=\"" . $rowDb['id_employee'] . "\">" . $rowDb['employee_id'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['employee_name'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['grade_code'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['division_name'] . "&nbsp;</td>";
    $strResult .= "  <td>" . $rowDb['department_name'] . "&nbsp;</td>";
    $strResult .= "  <td nowrap>" . $strChanges . "&nbsp;</td>";
    $strResult .= "  <td>" . nl2br($rowDb['approval_note']) . "&nbsp;</td>";
    $strResult .= "  <td>" . nl2br($rowDb['note']) . "&nbsp;</td>";
    $strResult .= "  <td align=center>" . getWords($ARRAY_REQUEST_STATUS[$rowDb['status']]) . "&nbsp;</td>";
    if (!$bolPrint) {
      $strResult .= "  <td align=center><a href=\"mutation_edit.php?dataID=" . $rowDb['id'] . "\">" . $words['edit'] . "</a>&nbsp;</td>";
    }
    $strResult .= "</tr>\n";
  }
  if ($intRows > 0) {
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  return $strResult;
} // getData
?>