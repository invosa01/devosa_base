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
function processAttendance($db, $strDateFrom, $strDateTo, $bolSilent = true, $strIDEmployee = "")
{
  include_once("overtime_func.php");
  include_once("activity.php");
  include_once("attendance_functions.php");
  include_once("..\global\employee_function.php");
  global $strResultInfo;
  // ambil dulu data barcode karyawan
  $arrBarcode = [];
  $strSQL = "SELECT id, barcode FROM hrd_employee ";
  $strDateFrom = date('Y-m-d', strtotime($strDateFrom, "-1 day"));
  $strDateTo = date('Y-m-d', strtotime($strDateTo, "+1 day"));
  if ($strIDEmployee != "") {
    $strSQL .= "WHERE id = '$strIDEmployee'";
  }
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    if ($rowDb['barcode'] != "") {
      $arrBarcode[$rowDb['barcode']] = $rowDb['id'];
    }
  }
  // mulai proses
  $strResult = "";
  $db1 = odbc_connect("absen", "", "");
  $strDateTo1 = getNextDate($strDateTo);
  if ($strIDEmployee != "") {
    $strFingerID = getEmployeeInfoByID($db, $strIDEmployee, "barcode");
    //$strFingerID = "000".$strEmployeeID['employee_id'];
  }
  $arrAttInfo = [];
  $strSQL = "SELECT Name AS USERID, CHECKTIME, CHECKTYPE FROM CHECKINOUT LEFT JOIN USERINFO ON USERINFO.USERID=CHECKINOUT.USERID WHERE CHECKTIME BETWEEN #$strDateFrom# AND #$strDateTo1# ORDER BY Name, CHECKTIME";
  // if ($strIDEmployee != "")
  // $strSQL  .= "AND USERID = '$strFingerID'";
  //$strSQL  .= " ORDER BY USERID, CHECKTIME";
  //echo $strSQL;
  $res = odbc_exec($db1, $strSQL);
  if (!$res) {
    echo "query errorrrr";
  }
  while ($row = odbc_fetch_array($res)) {
    $strID = $row['USERID'];
    $strIO = ($row['CHECKTYPE'] == "I") ? 1 : 0;
    $strKEY = 5;
    $arrTmp = explode(" ", $row['CHECKTIME']);
    if (count($arrTmp) > 1) {
      $strDate = $arrTmp[0];
      $strTime = $arrTmp[1];
      if (isset($arrBarcode[$strID])) {
        $arrAttInfo[$strDate][$arrBarcode[$strID]][] = [
            $strTime
            /*timestamp*/,
            $strIO
            /*in/out  1 = in, 0 = out*/,
            $strKEY
            /*in/out  3 = in Shift3, 4 = out shift 3*/
        ];
      }
    }
  }
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
              if ($objYesterday->strAttendanceFinish == "" && timeCompare($arrTime[0], "10:00") <= 0) {
                $strOutYesterday = $arrTime[0];
              } else {
                $strOutToday = $arrTime[0];
              }
            } //jika punch in, catat sebagai jam masuk hari ini
            else {
              if ($strInToday != "") {
                $strInToday = $strInToday;
              } else {
                $strInToday = $arrTime[0];
              }
            }
            $arrPrev = $arrTime;
          } //bukan index pertama
          else {
            if ($intArray == 1) {
              if ($arrTime[1] == 0) {
                if ($objYesterday->strAttendanceFinish == "" && timeCompare($arrTime[0], "10:00") <= 0) {
                  $strOutYesterday = $arrTime[0];
                } else {
                  $strOutToday = $arrTime[0];
                }
              } else {
                if ($strInToday != "") {
                  $strInToday = $strInToday;
                } else {
                  $strInToday = $arrTime[0];
                }
              }
            } //jika merupakan punch kedua hari bersangkutan
            else if ($intArray >= 2) {
              //jika punch out, cek apa sebelumnya sudah pernah in, jika iya, catat sebagai jam keluar hari ini
              if ($arrTime[1] == 0) {
                if ($objYesterday->strAttendanceFinish == "" && timeCompare($arrTime[0], "10:00") <= 0) {
                  $strOutYesterday = $arrTime[0];
                } else {
                  $strOutToday = $arrTime[0];
                }
              }
              // if($arrTime[1] == 0)
              // {
              //     if($strInToday != "") $strOutToday = $arrTime[0];
              // }
              //jika punch in, catat sebagai jam masuk
              else {
                if ($strInToday != "") {
                  $strInToday = $strInToday;
                } else {
                  $strInToday = $arrTime[0];
                }
              }
            }
            $arrPrev = $arrTime;
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
  setAutoAlpha($db, $strFirstdate, $strLastdate);
  if ($db1 != false) {
    odbc_close($db1);
  }
  unset($objToday);
  unset($objYesterday);
  return $strResult;
}//processAttendance
?>
