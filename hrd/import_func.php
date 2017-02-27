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
//function processAttendance($db, $strDateFrom, $strDateTo, $bolSilent = true, $strIDEmployee = "")
function processAttendance($db, $strDateFrom, $strDateTo, $arrDataAttendance)
{
  //print_r($arrDataAttendance);
  include_once("overtime_func.php");
  include_once("activity.php");
  include_once("attendance_functions.php");
  include_once("..\global\employee_function.php");
  global $strResultInfo;
  // ambil dulu data barcode karyawan
  $arrBarcode = [];
  $strSQL = "SELECT id, barcode FROM hrd_employee ";
  //if ($strIDEmployee != "")
  //  $strSQL  .= "WHERE id = '$strIDEmployee'";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if ($rowDb['barcode'] != "") {
      $arrBarcode[$rowDb['barcode']] = $rowDb['id'];
    }
  }
  $strResult = "";
  print_r($arrBarcode);
  /*
  // mulai proses

  $db1 = odbc_connect("rsch", "", "");

 $strDateTo1 = getNextDate($strDateTo);
 if ($strIDEmployee != "")
  {
    $strFingerID = getEmployeeInfoByID($db, $strIDEmployee, "barcode") ;
    //$strFingerID = "000".$strEmployeeID['employee_id'];
  }

  $arrAttInfo = array();
  if ($strIDEmployee != "")
    $strSQL  .= "AND t2.Badgenumber = '$strFingerID'";

  $res = odbc_exec($db1, $strSQL);
  */
  //while ($row = odbc_fetch_array($res)) /// Foreach array data attendance
  foreach ($arrDataAttendance AS $index => $value) {
    echo "D";
    $strID = $value['finger_id'];  // finger_id
    $strKEY = 5;
    $strDate = $value['date']; // Ambil tanggal
    if (isset($arrBarcode[$strID])) {
      $strIO = 1;
      $strTime = $value['in']; // Time
      $arrAttInfo[$strDate][$arrBarcode[$strID]][] = [
          $strTime
          /*timestamp*/,
          $strIO
          /*in/out  1 = in, 0 = out*/,
          $strKEY
          /*in/out  3 = in Shift3, 4 = out shift 3*/
      ];
      $strIO = 0;
      $strTime = $value['out']; // Time
      $arrAttInfo[$strDate][$arrBarcode[$strID]][] = [
          $strTime
          /*timestamp*/,
          $strIO
          /*in/out  1 = in, 0 = out*/,
          $strKEY
          /*in/out  3 = in Shift3, 4 = out shift 3*/
      ];
      echo "r";
    }
    echo $strID . "|";;
  }
  //print_r($arrAttInfo);
  //die;
  $objAttendanceClass = new clsAttendanceClass($db);
  $strFirstdate = pgDateFormat($strDateFrom, "Y-m-d");
  $strCurrdate = pgDateFormat($strDateFrom, "Y-m-d");
  $strLastdate = pgDateFormat($strDateTo, "Y-m-d");
  while (dateCompare($strCurrdate, $strLastdate) <= 0) {
    if (isset($arrAttInfo[$strCurrdate])) {
      $objAttendanceClass->resetAttendance();
      $objAttendanceClass->setFilter(getNextDate($strCurrdate, -1), $strCurrdate);
      $objAttendanceClass->getAttendanceResource();
      $objToday = new clsAttendanceInfo($db);
      $objYesterday = new clsAttendanceInfo($db);
      $strCurrdate = pgDateFormat($strCurrdate, "Y-m-d");
      $strYesterday = getNextDate($strCurrdate, -1);
      foreach ($arrAttInfo[$strCurrdate] AS $strIDEmployee => $arrTimeIO) {
        $objToday->newInfo($strIDEmployee, $strCurrdate);
        $objYesterday->newInfo($strIDEmployee, $strYesterday);
        $objToday->initAttendanceInfo($objAttendanceClass);
        $objYesterday->initAttendanceInfo($objAttendanceClass);
        // ambil data kehadiran karyawan di hari tersebut, dipisahkan antara pagi dan siang
        $arrPrev = [];
        $intArray = 0;
        $strInYesterday = $strOutYesterday = $strInToday = $strOutToday = "";
        foreach ($arrTimeIO AS $x => $arrTime) {
          $intArray++;
          //index pertama
          if ($x == 0) {
            $intArray = $x;
            //jika punch out, cek attendance kemarin, jika belum ada attendance finish, catat
            if ($arrTime[1] == 0) {
              if ($arrTime[2] == 4) {
                $strOutYesterday = $arrTime[0];
              } else if ($objYesterday->strAttendanceFinish == "" && timeCompare($arrTime[0], "12:00") <= 0) {
                $strOutYesterday = $arrTime[0];
              } /* else if(!isset($arrTimeIO[1]))
                {
                  if (timeCompare($objYesterday->strAttendanceFinish, "12:00") >= 0  )
                    $strOutToday = $arrTime[0];
                } */
              else if (timeCompare($arrTime[0], "12:00") > 0) {
                $strOutToday = $arrTime[0];
              }
            } //jika punch in, catat sebagai jam masuk hari ini
            else {
              if ($arrTime[2] == 3) {
                if (timeCompare($arrTime[0], "03:00") > 0) {
                  $strInToday = $arrTime[0];
                } else {
                  $strInYesterday = $arrTime[0];
                }
              } else if ($arrTime[2] == 4) {
                $strOutYesterday = $arrTime[0];
              } else {
                $strInToday = $arrTime[0];
              }
            }
            $arrPrev = $arrTime;
          } //bukan index pertama
          else { //jika duplikasi dari punch sejenis sebelumnya, abaikan
            if (getIntervalHour(
                    $arrPrev[0],
                    $arrTime[0]
                ) < 60 && $arrPrev[1] == $arrTime[1] && $arrPrev[2] == $arrTime[2]
            ) //if ($arrPrev[1] == $arrTime[1])
            {
              //if (timeCompare($arrPrev[0], $arrTime[0]) <= 0 && $arrTime[1] == 1)
              //{
              $intArray--;
              $arrPrev = $arrTime;
              //}
              /*
              else if (timeCompare($arrPrev[0], $arrTime[0]) < 0 && $arrTime[1] == 0 && $arrTime[2] == 4)
                if($strInYesterday != "") $strOutYesterday = $arrTime[0];
              else if (timeCompare($arrPrev[0], $arrTime[0]) < 0 && $arrTime[1] == 0)
              {
                if($strInToday != "") $strOutToday = $arrTime[0];
              }*/
            } //jika bukan duplikasi
            else {
              //jika merupakan punch kedua hari bersangkutan
              if ($intArray == 1) {
                //jika punch out dan sebelumnya juga punch out dengan selisih interval lebih dari 5 jam, atau punch out dan sebelumnya punch in catat sebagai jam keluar hari ini
                if ($arrTime[1] == 0) {
                  if ($arrTime[2] == 3) {
                    if (timeCompare($arrTime[0], "03:00") > 0) {
                      $strInToday = $arrTime[0];
                    } else {
                      $strInYesterday = $arrTime[0];
                    }
                  } else if ($arrTime[2] == 4) {
                    $strOutYesterday = $arrTime[0];
                  } else if ($arrPrev[1] == 1) {
                    $strOutToday = $arrTime[0];
                  } else if (getIntervalHour($arrPrev[0], $arrTime[0]) > 300) {
                    $strOutToday = $arrTime[0];
                  }
                } else {
                  if ($arrTime[2] == 3) {
                    if (timeCompare($arrTime[0], "03:00") > 0) {
                      $strInToday = $arrTime[0];
                    } else {
                      $strInYesterday = $arrTime[0];
                    }
                  } else if ($arrTime[2] == 4) {
                    $strOutYesterday = $arrTime[0];
                  } else {
                    $strInToday = $arrTime[0];
                  }
                }
              } //jika merupakan punch kedua hari bersangkutan
              else if ($intArray >= 2) {
                //jika punch out, cek apa sebelumnya sudah pernah in, jika iya, catat sebagai jam keluar hari ini
                if ($arrTime[1] == 0) {
                  if ($arrTime[2] == 3) {
                    if (timeCompare($arrTime[0], "03:00") > 0) {
                      $strInToday = $arrTime[0];
                    } else {
                      $strInYesterday = $arrTime[0];
                    }
                  } else if ($arrTime[2] == 4) {
                    $strOutYesterday = $arrTime[0];
                  } else if ($strInToday != "") {
                    $strOutToday = $arrTime[0];
                  }
                } //jika punch in, catat sebagai jam masuk
                else {
                  if ($arrTime[2] == 3) {
                    if (timeCompare($arrTime[0], "03:00") > 0) {
                      $strInToday = $arrTime[0];
                    } else {
                      $strInYesterday = $arrTime[0];
                    }
                  } else if ($arrTime[2] == 4) {
                    $strOutYesterday = $arrTime[0];
                  } else {
                    $strInToday = $arrTime[0];
                  }
                }
              }
              $arrPrev = $arrTime;
            }
          }
        }
        if ($strInYesterday != "") // kemarin shift malam
        {
          $objYesterday->strAttendanceStart = $strInYesterday;
          $objYesterday->bolYesterday = true;
          $objYesterday->calculateDuration();
          $objYesterday->calculateLate();
          // handle overtime
          $objYesterday->calculateOvertime();
          $objYesterday->saveCurrentAttendance($objAttendanceClass);
        }
        if ($strOutYesterday != "") // kemarin shift malam
        {
          $objYesterday->strAttendanceFinish = $strOutYesterday;
          $objYesterday->bolYesterday = true;
          $objYesterday->calculateDuration();
          $objYesterday->calculateLate();
          // handle overtime
          $objYesterday->calculateOvertime();
          $objYesterday->saveCurrentAttendance($objAttendanceClass);
        }
        $objToday->strAttendanceStart = $strInToday;
        $objToday->strAttendanceFinish = $strOutToday;
        $objToday->bolYesterday = false;
        $objToday->calculateDuration();
        $objToday->calculateLate();
        // handle overtime
        $objToday->calculateOvertime();
        $objToday->saveCurrentAttendance($objAttendanceClass);
      }
    }
    // next date
    $strCurrdate = getNextDate($strCurrdate);
  }
  syncOvertimeApplication($db, $strFirstdate, $strLastdate);
  //if ($db1 != false) odbc_close($db1);
  unset($objToday);
  unset($objYesterday);
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
            $strSQLx = "SELECT * from hrd_shift_schedule_employee where id_employee = '$strID' and shift_date = ('$strTanggal')::date - 1::integer";
            $resDb = $db->execute($strSQLx);
            while ($rowDb = $db->fetchrow($resDb)) {
              $arrShift['start_time'] = $rowDb['start_time'];
              $arrShift['finish_time'] = $rowDb['finish_time'];
            }
            $varTemp = "";
            if (substr($arrShift['start_time'], 0, 5) == "24:00" && (int)substr(
                    $arrShift['start_time'],
                    0,
                    2
                ) > (int)substr($strStart, 0, 2) && (int)substr($strStart, 0, 2) < (int)substr($strNormalFinish, 0, 2)
            ) {
              $strNormalStart = "00:00";
              //$strTanggal = date("Y-m-d",  strtotime($strTanggal." -1 day"));
              $varTemp = "true";
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
                //$strSQL .= "status >= ".REQUEST_STATUS_APPROVED.", "; // langsung approve
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
              $strSQLx = "SELECT * from hrd_shift_schedule_employee where id_employee = '$strID' and shift_date = ('$strTanggal')::date - 1::integer";
              $resDb = $db->execute($strSQLx);
              while ($rowDb = $db->fetchrow($resDb)) {
                $arrShift['start_time'] = $rowDb['start_time'];
                $arrShift['finish_time'] = $rowDb['finish_time'];
              }
              $varTemp = "";
              if (substr($arrShift['start_time'], 0, 5) == "24:00" && (int)substr(
                      $arrShift['start_time'],
                      0,
                      2
                  ) > (int)substr($strStart, 0, 2) && (int)substr($strStart, 0, 2) < (int)substr($strNormalFinish, 0, 2)
              ) {
                $strNormalStart = "00:00";
                $strTanggal = date("Y-m-d", strtotime($strTanggal . " -1 day"));
                $varTemp = "true";
              }
              // insert baru
              // -- kayaknya revisi jangan ditulis dulu
              $strSQL = "INSERT INTO hrd_attendance (id_employee, attendance_date, ";
              $strSQL .= "normal_start, normal_finish, attendance_start,  ";
              $strSQL .= "attendance_finish, status, late_duration, ";
              $strSQL .= "early_duration, total_duration, ";
              $strSQL .= "l1, l2, l3, l4) ";
              //$strSQL .= "change_start, change_finish) "; //
              if ($varTemp == "true") {
                $strNormalStart = "'24:00'";
              }
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
  $strSQLx = "SELECT * from hrd_shift_schedule_employee where id_employee = '$strID' and shift_date = ('$strTanggal')::date - 1::integer";
  $resDb = $db->execute($strSQLx);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrShift['start_time'] = $rowDb['start_time'];
    $arrShift['finish_time'] = $rowDb['finish_time'];
  }
  $varTemp = "";
  if (substr($arrShift['start_time'], 0, 5) == "24:00" && (int)substr($arrShift['start_time'], 0, 2) > (int)substr(
          $strStart,
          0,
          2
      ) && (int)substr($strStart, 0, 2) < (int)substr($strNormalFinish, 0, 2)
  ) {
    $strNormalStart = "00:00";
    $strTanggal = date("Y-m-d", strtotime($strTanggal . " -1 day"));
    $varTemp = "true";
  }
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
  if ($varTemp == "true") {
    $strNormalStart = "'24:00'";
  }
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