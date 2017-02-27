<?php
/* fungsi untuk keperluan import data, misal import attendance atau apapun
  Author : Yudi K.
  04.2006
*/
// mengambil data kehadiran dari file sesuai konfigurasi, default dari file configurasi
// db -> kelas database
// bolSilent -> tidak menampilkan info hasil
function getAttendanceData($db, $bolSilent = true)
{
  $strResult = "";
  $strFileName = getSetting("attendance_file");
  if ($strFileName == "") {
    if (!$bolSilent) {
      $strResult = "No file setting!";
    }
  } else {
    processAttendance($db, $strFileName, $bolSilent);
  }
  return $strResult;
} // getAttendanceData
// fungsi untuk melakukan proses terhadap data kehadiran, yang dibaca dari nama file tertentu
function processAttendance($db, $strFileName, $strDateFrom, $strDateThru, $strDataCompany = '', $bolSilent = true)
{
  include_once("overtime_func.php");
  include_once("activity.php");
  include_once("attendance_functions.php");
  global $strResultInfo;
  // ambil dulu data barcode karyawan
  $arrBarcode = [];
  $strSQL = "SELECT id, barcode FROM hrd_employee WHERE id_company = $strDataCompany";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if ($rowDb['barcode'] != "") {
      $arrBarcode[$rowDb['barcode']] = $rowDb['id'];
    }
  }
  // mulai proses
  $strResult = "";
  if ($strFileName != "") {
    $handle = fopen("$strFileName", "r");
    if ($handle != false) {
      // baca semua data dari file, tampung dulu di array
      $arrData = [];;
      while (!feof($handle)) {
        $buffer = fgets($handle, 4096); // baca per baris
        $buffer = trim($buffer);
        // start parsing data -> tampung di array
        // employee id, date, time, remark
        $strEmployeeID = $strCurrDate = $strTimePunch = $strRemark = "";
        //get employee id
        preg_match("/\s[0-9]+\s/", $buffer, $match);
        if (isset($match[0]) && isset($arrBarcode[trim($match[0])])) {
          $strEmployeeID = $arrBarcode[trim($match[0])];
        } else {
          continue;
        }
        //get current date
        preg_match("/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/", $buffer, $match);
        $strCurrDate = isset($match[0]) ? trim($match[0]) : "";
        $strCurrDate = substr($strCurrDate, -4) . "-" . substr($strCurrDate, 3, 2) . "-" . substr($strCurrDate, 0, 2);
        //cek tanggal
        if ((!validStandardDate($strCurrDate)) || dateCompare($strCurrDate, $strDateFrom) < 0 || dateCompare(
                $strCurrDate,
                $strDateThru
            ) > 0
        ) {
          continue;
        }
        //get time
        preg_match("/[0-9]{2}:[0-9]{2}:[0-9]{2}/", $buffer, $match);
        $strTimePunch = isset($match[0]) ? substr(trim($match[0]), 0, 5) : "";
        //get remark (according to attendance file, it's either : MSK KERJA, MSK KERJA TELAT, PULANG, PULANG CEPAT, or SUDAH ABSEN)
        preg_match("/[\sA-Z]+$/", $buffer, $match);
        $strRemark = trim($match[0]);
        $arrAttInfo[$strCurrDate][$strEmployeeID][$strRemark] = $strTimePunch;
      }
    }
    fclose($handle);
  }
  $objAttendanceClass = new clsAttendanceClass($db);
  $objAttendanceClass->resetAttendance();
  $objAttendanceClass->setFilter($strDateFrom, $strDateThru);
  $objAttendanceClass->getAttendanceResource();
  $objToday = new clsAttendanceInfo($db);
  //$objYesterday = new clsAttendanceInfo($db);
  $strCurrdate = pgDateFormat($strDateFrom, "Y-m-d");
  $strLastdate = pgDateFormat($strDateThru, "Y-m-d");
  while (dateCompare($strCurrdate, $strLastdate) <= 0) {
    if (isset($arrAttInfo[$strCurrdate])) {
      $strCurrdate = pgDateFormat($strCurrdate, "Y-m-d");
      //$strYesterday = getNextDate($strCurrdate, -1);
      foreach ($arrAttInfo[$strCurrdate] AS $strIDEmployee => $arrTime) {
        $strAttendanceStart = $strAttendanceFinish = "";
        $objToday->newInfo($strIDEmployee, $strCurrdate);
        $objToday->initAttendanceInfo($objAttendanceClass);
        //$objYesterday->newInfo($strIDEmployee, $strYesterday);
        //$objYesterday->initAttendanceInfo($objAttendanceClass);
        // ambil data kehadiran karyawan di hari tersebut, dipisahkan antara pagi dan siang
        $arrAM = $arrPM = []; // untuk nampun jam masuk-pulang
        foreach ($arrTime AS $strRemark => $strTime) {
          if ($strRemark[0] == "M" && $strRemark[4] != "I") {
            $strAttendanceStart = $strTime;
          } else if ($strRemark[0] == "I") {
            $strAttendanceStart = getNextMinute($objToday->strNormalStart, 30);
          } else if ($strRemark[0] == "P") {
            $strAttendanceFinish = $strTime;
          }
        }
        /*
         if ($objYesterday->bolShiftNight && isset($arrAM['max'])) // kemarin shift malam
         {
           // handle kemarin juga, asumsi data-data sudah lengkap
           if ($objYesterday->strAttendanceStart != $arrAM['max'])
              $objYesterday->strAttendanceFinish = $arrAM['max'];
           $objYesterday->bolYesterday = true;
           $objYesterday->calculateDuration();
           $objYesterday->calculateLate();
           // handle overtime
           $objYesterday->calculateOvertime();
           $objYesterday->saveCurrentAttendance($objAttendanceClass);
         }

         //handle hari ini (cek dulu shift malam atau bukan)
         if ($objToday->bolShiftNight)
         {
           $objToday->bolYesterday = false;
           $objToday->strAttendanceStart = (isset($arrPM['min'])) ? $arrPM['min'] : "";
           $objToday->strAttendanceFinish = "";
           $objToday->calculateDuration();
           $objToday->calculateLate();
           // handle overtime
           $objToday->calculateOvertime();
           $objToday->saveCurrentAttendance($objAttendanceClass);
         }
         else
         {
         // handle hari ini saja
         if (isset($strAttendanceStart))
         {
         if ($strIDEmployee == '119') echo $strCurrdate."-obj|".$objToday->strAttendanceStart."|".$objToday->strAttendanceFinish."<BR>";
         if ($strIDEmployee == '119') echo $strCurrdate."-file|".$strAttendanceStart."|".$strAttendanceFinish."<BR>";
         if ($strIDEmployee == '119') echo $strCurrdate."-obj|".$objToday->strAttendanceStart."|".$objToday->strAttendanceFinish."<BR>";
*/
        $objToday->bolYesterday = ($objToday->strAttendanceFinish == "");
        if ($objToday->strAttendanceStart == "" || dateCompare(
                $strAttendanceStart,
                $objToday->strAttendanceStart
            ) < 0
        ) {
          $objToday->strAttendanceStart = $strAttendanceStart;
        }
        if ($objToday->strAttendanceFinish == "" || dateCompare(
                $strAttendanceFinish,
                $objToday->strAttendanceFinish
            ) < 0
        ) {
          $objToday->strAttendanceFinish = $strAttendanceFinish;
        }
        /*}
        else
        {
           $objToday->strAttendanceStart = $arrPM['min'];
           $objToday->strAttendanceFinish = (isset($arrPM['max2'])) ? $arrPM['max2'] : $arrPM['max'];
        }*/
        if ($objToday->strAttendanceStart == $objToday->strAttendanceFinish) {
          $objToday->strAttendanceFinish = "";
        }
        $objToday->calculateDuration();
        $objToday->calculateLate();
        // handle overtime
        $objToday->saveCurrentAttendance($objAttendanceClass, "imported");
        //}
      }
    }
    // next date
    $strCurrdate = getNextDate($strCurrdate);
  }
  //if ($db1 != false) odbc_close($db1);
  unset($objToday);
  unset($objYesterday);
  return $strResult;
}//processAttendance
// fungsi untuk melakukan proses terhadap data kehadiran, yang dibaca dari folder tertentu yang sudah di isi pada general setting
function processAttendanceAutomatic(
    $db,
    $strFilePath,
    $strFileType,
    $strDateFrom,
    $strDateThru,
    $strDataCompany = '',
    $bolSilent = true
) {
  $strResult = "";
  if ($strFilePath != "" && $strFileType != "" && validStandardDate($strDateFrom) && validStandardDate($strDateThru)) {
    $strFileDate = $strDateFrom;
    While (dateCompare(
            $strFileDate,
            getNextDate($strDateThru, 10)
        ) < 0) {//echo $strFilePath.str_replace("-", "", $strFileDate).".".$strFileType;
      if (file_exists($strFileName = ($strFilePath . str_replace("-", "", $strFileDate) . "." . $strFileType))) {
        echo $strFileName;
        //$strFileName = ($strFilePath.str_replace("-", "", $strFileDate).".".$strFileType);
        $strResult .= "<br>" . processAttendance(
                $db,
                $strFileName,
                getNextDate($strFileDate, -1),
                $strFileDate,
                $strDataCompany,
                false
            );
        if (dateCompare($strFileDate, $strDateThru) >= 0) {
          break;
        }
      }
      $strFileDate = getNextDate($strFileDate);
    }
  }
  return $strResult;
}//processAttendance
// dengan menggunakan jam 12 sebagai batas
function processAttendance12($db, $strFileName = "", $bolSilent = true)
{
  include_once("overtime_func.php");
  $strResult = "";
  if (($strDefaultStart = substr(getSetting("start_time"), 0, 5)) == "") {
    $strDefaultStart = "08:00";
  }
  if (($strDefaultFinish = substr(getSetting("finish_time"), 0, 5)) == "") {
    $strDefaultFinish = "17:00";
  }
  if ($strFileName != "") {
    $handle = fopen("$strFileName", "r");
    if ($handle != false) {
      $strMaxTanggal = pgDateFormat("Y-m-d");
      $strMinTanggal = "2000-01-01";
      // baca semua data dari file, tampung dulu di array
      $arrData = [];;
      while (!feof($handle)) {
        $buffer = fgets($handle, 4096); // baca per baris
        continue; //baris pertama di ignore (dbf file)
        $buffer = trim($buffer);
        // start parsing data -> tampung di array
        // [4:tahun][2:bulan][2:tanggal][2:jam][2:menit][7:NIK][1:flag][3:kode mesin]
        if (strlen($buffer) == 23) {
          $strThn = substr($buffer, 0, 4);
          $strBln = substr($buffer, 4, 2);
          $strTgl = substr($buffer, 6, 2);
          $strJam = substr($buffer, 8, 2);
          $strMnt = substr($buffer, 10, 2);
          $strNik = substr($buffer, 12, 7);
          $strFlg = substr($buffer, 19, 1);
          $strMsn = substr($buffer, 20, 3);
          $strTanggal = "$strThn-$strBln-$strTgl";
          $strWaktu = "$strJam:$strMnt";
          if ($strTanggal < $strMinTanggal) {
            $strMinTanggal = $strTanggal;
          }
          if ($strTanggal > $strMaxTanggal) {
            $strMaxTanggal = $strTanggal;
          }
          $intCode = ($strJam < 12) ? 1 : 2; // 1 = pagi, 2 = siang
          // simpan min
          if (isset($arrData[$strNik][$strTanggal][$intCode]['min'])) {
            if ($arrData[$strNik][$strTanggal][$intCode]['min'] > $strWaktu) {
              $arrData[$strNik][$strTanggal][$intCode]['min'] = $strWaktu;
            }
          } else {
            $arrData[$strNik][$strTanggal][$intCode]['min'] = $strWaktu;
          }
          // simpan max
          if (isset($arrData[$strNik][$strTanggal][$intCode]['max'])) {
            if ($arrData[$strNik][$strTanggal][$intCode]['max'] < $strWaktu) {
              $arrData[$strNik][$strTanggal][$intCode]['max'] = $strWaktu;
            }
          } else {
            $arrData[$strNik][$strTanggal][$intCode]['max'] = $strWaktu;
          }
        }
      }
      fclose($handle);
      // cari data karyawan, simpan dalam array
      $arrTmpEmp = [];
      $strSQL = "SELECT id, barcode FROM hrd_employee WHERE active = 1 AND flag=0 ";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) {
        if ($rowDb['barcode'] != "") {
          $arrEmp[$rowDb['barcode']] = $rowDb['id'];
          $arrTmpEmp[$rowDb['barcode']] = false;
        }
      }
      // baca data kehadiran yang sudah ada, simpan alam array juga
      $strSQL = "SELECT t1.*, t2.barcode FROM hrd_attendance AS t1 ";
      $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id ";
      $strSQL .= "WHERE attendance_date BETWEEN '$strMinTanggal' AND '$strMaxTanggal' ";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) {
        if ($rowDb['barcode'] != "") {
          $strTgl = pgDateFormat($rowDb['attendance_date'], "Y-m-d");
          $arrAtt[$rowDb['barcode']][$strTgl]['id_employee'] = $rowDb['id_employee'];
          $arrAtt[$rowDb['barcode']][$strTgl]['normal_start'] = substr($rowDb['normal_start'], 0, 5);
          $arrAtt[$rowDb['barcode']][$strTgl]['normal_finish'] = substr($rowDb['normal_finish'], 0, 5);
          $arrAtt[$rowDb['barcode']][$strTgl]['attendance_start'] = substr($rowDb['attendance_start'], 0, 5);
          $arrAtt[$rowDb['barcode']][$strTgl]['attendance_finish'] = substr($rowDb['attendance_finish'], 0, 5);
          $arrAtt[$rowDb['barcode']][$strTgl]['change_start'] = substr($rowDb['change_start'], 0, 5);
          $arrAtt[$rowDb['barcode']][$strTgl]['change_finish'] = substr($rowDb['change_finish'], 0, 5);
          $arrAtt[$rowDb['barcode']][$strTgl]['status'] = substr($rowDb['status'], 0, 5);
        }
      }
      // mulai proses datanya
      foreach ($arrData AS $strKode => $arrWaktu) {
        // cek apakah ada data employeenya
        if (isset($arrEmp[$strKode])) {
          $strID = $arrEmp[$strKode];
          foreach ($arrWaktu AS $strTanggal => $arrTmp) {
            // untuk sementara masih dianggap waktu normal, pagi masuk, malam pulang
            $strStart = "";
            $strFinish = "";
            $strNormalStart = (isset($arrAtt[$strKode][$strTanggal]['normal_start'])) ? $arrAtt[$strKode][$strTanggal]['normal_start'] : $strDefaultStart;
            $strNormalFinish = (isset($arrAtt[$strKode][$strTanggal]['normal_finish'])) ? $arrAtt[$strKode][$strTanggal]['normal_finish'] : $strDefaultFinish;
            $strTmp1 = (isset($arrAtt[$strKode][$strTanggal]['start'])) ? $arrAtt[$strKode][$strTanggal]['start'] : "";
            $strTmp2 = (isset($arrTmp[1]['min'])) ? $arrTmp[1]['min'] : "";
            if ($strTmp1 == "") {
              $strStart = $strTmp2;
            } else {
              $strStart = ($strTmp2 == "" || ($strTmp1 < $strTmp2)) ? $strTmp1 : $strTmp2;
            }
            // cari waktu pulang
            $strTmp1 = (isset($arrAtt[$strKode][$strTanggal]['finish'])) ? $arrAtt[$strKode][$strTanggal]['finish'] : "";
            $strTmp2 = (isset($arrTmp[2]['max'])) ? $arrTmp[2]['max'] : "";
            if ($strTmp1 == "") {
              $strFinish = $strTmp2;
            } else {
              $strFinish = ($strTmp2 == "" || ($strTmp1 > $strTmp2)) ? $strTmp1 : $strTmp2;
            }
            // hitung data lembur, telat atau pulang cepat
            $arrLembur = calculateOvertime($db, $strTanggal, $strNormalStart, $strNormalFinish, $strStart, $strFinish);
            // lakukan validasi
            $strStart = ($strStart == "") ? "NULL" : "'$strStart'";
            $strFinish = ($strFinish == "") ? "NULL" : "'$strFinish'";
            // simpan ke database
            if (isset($arrAtt[$strKode][$strTanggal])) {
              $strRevStart = $arrAtt[$strKode][$strTanggal]['change_start'];
              $strRevFinish = $arrAtt[$strKode][$strTanggal]['change_finish'];
              $strAttStart = $arrAtt[$strKode][$strTanggal]['attendance_start'];
              $strAttFinish = $arrAtt[$strKode][$strTanggal]['attendance_finish'];
              $strUpdate = "";
              // update jika revisi gak ada
              if ($strRevStart == "" && $strRevFinish == "") {// gak ada revisi
                $strUpdate .= "attendance_start = $strStart, attendance_finish = $strFinish, ";
              } else if ($strRevStart == "" && $strRevFinish != "") {// revisistart gak ada, update itu aja
                $strUpdate .= "attendance_start = $strStart, ";
              } else if ($strRevStart != "" && $strRevFinish == "") {// revisistart gak ada, update itu aja
                $strUpdate .= "attendance_finish = $strFinish, ";
              }
              // modifiedata yang ada
              if ($strUpdate != "") {
                $strSQL = "UPDATE hrd_attendance SET created = now(), ";
                //$strSQL .= "status = ".REQUEST_STATUS_APPROVED.", "; // langsung approve
                //$strSQL .= "attendance_start = $strStart, attendance_finish = $strFinish, ";
                //$strSQL .= "change_start = $strStart, change_finish = $strFinish, ";
                $strSQL .= $strUpdate;
                $strSQL .= "l1 = '" . $arrLembur['l1'] . "', ";
                $strSQL .= "l2 = '" . $arrLembur['l2'] . "', ";
                $strSQL .= "l3 = '" . $arrLembur['l3'] . "', ";
                $strSQL .= "l4 = '" . $arrLembur['l4'] . "', ";
                $strSQL .= "total_duration = '" . $arrLembur['total'] . "', ";
                $strSQL .= "late_duration = '" . $arrLembur['late'] . "', early_duration = '" . $arrLembur['early'] . "' ";
                $strSQL .= "WHERE id_employee = '$strID' AND attendance_date = '$strTanggal' ";
                $resExec = $db->execute($strSQL);
              }
            } else {
              // insert baru
              // -- kayaknya revisi jangan ditulis dulu
              $strSQL = "INSERT INTO hrd_attendance (id_employee, attendance_date, ";
              $strSQL .= "normal_start, normal_finish, attendance_start,  ";
              $strSQL .= "attendance_finish, status, late_duration, ";
              $strSQL .= "early_duration, total_duration, ";
              $strSQL .= "l1, l2, l3, l4) ";
              //$strSQL .= "change_start, change_finish) "; //
              $strSQL .= "VALUES('$strID', '$strTanggal', '$strNormalStart', ";
              $strSQL .= "'$strNormalFinish', $strStart, $strFinish, " . REQUEST_STATUS_APPROVED . ", "; // langusng approve
              $strSQL .= "'" . $arrLembur['late'] . "', '" . $arrLembur['early'] . "', ";
              $strSQL .= "'" . $arrLembur['total'] . "', ";
              $strSQL .= "'" . $arrLembur['l1'] . "', ";
              $strSQL .= "'" . $arrLembur['l2'] . "', ";
              $strSQL .= "'" . $arrLembur['l3'] . "', ";
              $strSQL .= "'" . $arrLembur['l4'] . "') ";
              //$strSQL .= "$strStart, $strFinish) ";
              $resExec = $db->execute($strSQL);
            }
          }
        }
      }
      if (!$bolSilent) {
        $strResult = "Done! $strMinTanggal - $strMaxTanggal";
      }
    } else {
      if (!$bolSilent) {
        $strResult = "ERROR File $strFileName";
      }
    }
  }
  return $strResult;
}//processAttendance
// fungsi untuk melakukan proses terhadap data kehadiran, yang dibaca dari nama array tertentu, SUDAH PASTI KEHADIRAN
// input: id_employee, tanggal, jam masuk, jam pulang, keterangan
function insertAttendanceData($db, $strIDEmployee, $strDate, $strStart, $strFinish, $strNote)
{
  include_once("overtime_func.php");
  include_once("activity.php");
  $strResult = "";
  if (($strDefaultStart = substr(getSetting("start_time"), 0, 5)) == "") {
    $strDefaultStart = "08:00";
  }
  if (($strDefaultFinish = substr(getSetting("finish_time"), 0, 5)) == "") {
    $strDefaultFinish = "17:00";
  }
  // mulai proses datanya
  $strID = $strIDEmployee;
  $strTanggal = pgDateFormat($strDate, "Y-m-d");
  $bolHoliday = isHoliday($strDate);
  $intHoliday = ($bolHoliday) ? 1 : 0;
  // untuk sementara masih dianggap waktu normal, pagi masuk, malam pulang
  $strNormalStart = $strDefaultStart;
  $strNormalFinish = $strDefaultFinish;
  // hitung data lembur, telat atau pulang cepat
  $arrLembur = calculateOvertime($db, $strTanggal, $strNormalStart, $strNormalFinish, $strStart, $strFinish);
  // lakukan validasi
  $strStart = ($strStart == "") ? "NULL" : "'$strStart'";
  $strFinish = ($strFinish == "") ? "NULL" : "'$strFinish'";
  // simpan ke database
  // hapus dulu yang lama
  $strSQL = "DELETE FROM hrd_attendance WHERE id_employee = '$strID' ";
  $strSQL .= "AND attendance_date = '$strTanggal' ";
  $resExec = $db->execute($strSQL);
  // insert baru
  // -- kayaknya revisi jangan ditulis dulu
  $strSQL = "INSERT INTO hrd_attendance (id_employee, attendance_date, ";
  $strSQL .= "normal_start, normal_finish, attendance_start,  ";
  $strSQL .= "attendance_finish, status, late_duration, ";
  $strSQL .= "early_duration, total_duration, ";
  $strSQL .= "l1, l2, l3, l4, \"note\", link_id, holiday) "; // linkID buat nandain, kalau ini hasil import yang lama
  //$strSQL .= "change_start, change_finish) "; //
  $strSQL .= "VALUES('$strID', '$strTanggal', '$strNormalStart', ";
  $strSQL .= "'$strNormalFinish', $strStart, $strFinish, " . REQUEST_STATUS_APPROVED . ", "; // langusng approve
  $strSQL .= "'" . $arrLembur['late'] . "', '" . $arrLembur['early'] . "', ";
  $strSQL .= "'" . $arrLembur['total'] . "', ";
  $strSQL .= "'" . $arrLembur['l1'] . "', ";
  $strSQL .= "'" . $arrLembur['l2'] . "', ";
  $strSQL .= "'" . $arrLembur['l3'] . "', ";
  $strSQL .= "'" . $arrLembur['l4'] . "', '$strNote', '-1', $intHoliday) ";
  //$strSQL .= "$strStart, $strFinish) ";
  $resExec = $db->execute($strSQL);
  deleteSystemGeneratedAbsence($db, $strID, $strTanggal);
  return $strResult;
}//processAttendance
?>