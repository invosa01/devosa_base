<?php
include_once('../global/session.php');
include_once('global.php');
include_once('form_object.php');
//include_once(getTemplate("words.inc"));
// periksa apakah sudah login atau belum, jika belum, harus login lagi
$dataPrivilege = getDataPrivileges(
    "employee_search.php",
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove,
    $bolCanCheck,
    $bolCanAcknowledge
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
//$strTemplateFile = getTemplate("employee_report_print.html");
$strMainTemplate = getTemplate("employee_report_print.html");
//---- INISIALISASI ----------------------------------------------------
$strWordsCompany = getWords("company");
$strWordsManagement = getWords("management");
$strWordsDivision = getWords("division");
$strWordsDepartment = getWords("department");
$strWordsSection = getWords("section");
$strWordsSubSection = getWords("sub section");
$strReportTitle = "Employee Report";
$strReportSubTitle = "";
$strDataDetail = "";
$strDataDate = "";
$strDivisionName = "";
$strDepartmentName = "";
$strSectionName = "";
$strSubSectionName = "";
$strManagementName = "";
$strStyle = "";
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strDataDate adalah tanggal yang diinginkan
// $strKriteria = query kriteria
// $intStart = record mulai, $bolLimit = dibatasi sesuai limit global
function getData($db, $strKriteria = "", $strDateFrom, $strDateThru)
{
  global $words;
  global $bolPrint;
  global $strDataSection;
  global $ARRAY_EMPLOYEE_STATUS;
  global $strReportTitle;
  global $strDataDivision;
  global $strDataDepartment;
  global $strDataSection;
  global $strDataSubSection;
  global $strKriteriaCompany;
  // echo $strDataCompany;
  // die();
  $intRows = 0;
  $strResult = "";
  $strDefaultWidth = "width=50";
  $strTableStart = "<table cellspacing=0 cellpadding=0 border=0 class=gridTable>";
  $strTableFinish = "</table><br><br>";
  // ----- RECAP INFO UNIDENTIFIED JOIN DATE
  $strSQL = "SELECT COUNT(id) AS total FROM hrd_employee WHERE 1=1 $strKriteria";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $intTotalEmployee = $rowDb['total'];
  }
  // echo $strSQL;
  /*
  $strSQL  = "SELECT COUNT(id) AS total FROM hrd_employee WHERE join_date IS NULL";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
     $strResult .= getWords("unidentified join date")." : ".$rowDb['total']. " out of ". $intTotalEmployee." employee<br><br>";
  }  */
  // $strSQL  = "SELECT COUNT(id) AS total FROM hrd_employee WHERE (division_code = '' OR division_code IS NULL) AND join_date <= '$strDateThru' AND (resign_date >= '$strDateFrom' OR resign_date IS NULL) $strKriteriaCompany";
  // $resDb = $db->execute($strSQL);
  // if ($rowDb = $db->fetchrow($resDb)) {
  // $strResult .= getWords("management")." : ".$rowDb['total']."<br><br>";
  // }
  // echo $strSQL;
  // die();
  // ----- RECAP INFO GENDER DAN STATUS KAWIN
  $arrEmp = [0 => 0, 1 => 0];
  $intTmpTotal = 0;
  $strHeader1 = "";
  $strData = "";
  $strSQL = "SELECT COUNT(id) AS total, \"gender\" FROM hrd_employee ";
  $strSQL .= "WHERE 1=1 $strKriteria ";
  $strSQL .= "GROUP BY \"gender\" ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrEmp[$rowDb['gender']] = $rowDb['total'];
    $intTmpTotal += $rowDb['total'];
  }
  // cari data per  dan tulis
  $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper(getWords("male")) . "&nbsp;</td>\n";
  $strData .= "  <td>" . $arrEmp[1] . "&nbsp;</td>\n";
  $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper(getWords("female")) . "&nbsp;</td>\n";
  $strData .= "  <td>" . $arrEmp[0] . "&nbsp;</td>\n";
  // bungkus
  $strHeader1 = " <tr class=tableHeader align=center>$strHeader1</tr>\n";
  $strData = " <tr align=center>$strData</tr>\n";
  //$strResult .= "<strong>".strtoupper(getWords("level"))."</strong><br>";
  $strResult .= $strTableStart;
  $strResult .= $strHeader1 . "\n" . $strData;
  $strResult .= $strTableFinish;
  // ---- RECAP DATA EMPLOYEE PER STATUS ----
  $arrEmp = [];
  $intTmpTotal = 0;
  $strSQL = "SELECT COUNT(id) AS total, employee_status FROM hrd_employee ";
  $strSQL .= "WHERE 1=1 $strKriteria ";
  $strSQL .= "GROUP BY employee_status ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrEmp[$rowDb['employee_status']] = $rowDb['total'];
    $intTmpTotal += $rowDb['total'];
  }
  // cari data per  dan tulis
  $strHeader1 = "";
  $strData = "";
  $intTmp = 0; // data yagn dipakai
  foreach ($ARRAY_EMPLOYEE_STATUS AS $intIndex => $strValue) {
    $intSection = 0;
    $strHeader1 .= "  <td align=center>" . strtoupper(getWords($strValue)) . "&nbsp;</td>\n";
    $intEmployee = (isset($arrEmp[$intIndex])) ? $arrEmp[$intIndex] : 0;
    $strData .= "  <td>" . $intEmployee . "&nbsp;</td>\n";
    $intTmp += $intEmployee;
  }
  // tambahkan info untuk data lain-lain (jika ada yang gak termasuk didalamnya)
  $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper(getWords("total")) . "&nbsp;</td>\n";
  $strData .= "  <td>" . ($intTmpTotal) . "&nbsp;</td>\n";
  // bungkus
  $strHeader1 = " <tr class=tableHeader align=center>$strHeader1</tr>\n";
  $strData = " <tr align=center>$strData</tr>\n";
  $strResult .= "<strong>" . strtoupper(getWords("employee status")) . "</strong><br>";
  $strResult .= $strTableStart;
  $strResult .= $strHeader1 . "\n" . $strData;
  $strResult .= $strTableFinish;
  // ---- RECAP DATA EMPLOYEE PER DEPARTEMEN ----
  /* ---- YANG LAMA, DIBUAT NAMA DEPT/SECT SEBAGAI HEADER
  $arrEmpDept = array();
  $intTmpTotal = 0;
  $strSQL  = "SELECT COUNT(id) AS total, department_code, section_code FROM hrd_employee ";
  $strSQL .= "WHERE flag = 0 AND active = 1 $strKriteria ";
  $strSQL .= "GROUP BY department_code, section_code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrEmpDept[$rowDb['department_code']][$rowDb['section_code']] = $rowDb['total'];
    $intTmpTotal += $rowDb['total'];
  }
  // cari data per department dan tulis
  $strHeader1 = "";
  $strHeader2 = "";
  $strData = "";
  $intDept = 0;
  $intTmp = 0; // data yagn dipakai
  $strSQL  = "SELECT * FROM hrd_department ORDER BY department_code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intDept++;
    $intSection = 0;
    $strSQL  = "SELECT * FROM hrd_section WHERE department_code = '" .$rowDb['department_code']."' ";
    $strSQL .= "ORDER BY section_code ";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      $intSection++;
      $strHeader2 .= "  <td align=center title=\"" .$rowTmp['section_name']."\" $strDefaultWidth>" .strtoupper($rowTmp['section_code'])."&nbsp;</td>\n";
      $intEmployee = (isset($arrEmpDept[$rowDb['department_code']][$rowTmp['section_code']])) ? $arrEmpDept[$rowDb['department_code']][$rowTmp['section_code']] : 0;
      $strData .= "  <td>" .$intEmployee."&nbsp;</td>\n";
      $intTmp += $intEmployee;
    }

    $strHeader1 .= "  <td align=center title=\"" .$rowDb['department_name']."\" colspan=$intSection>" .strtoupper($rowDb['department_code'])."&nbsp;</td>\n";
    if ($intSection == 0) {
      $strHeader2 .= "  <td align=center $strDefaultWidth>&nbsp;</td>\n";
      $intEmployee = (isset($arrEmpDept[$rowDb['department_code']][''])) ? $arrEmpDept[$rowDb['department_code']][''] : 0;
      $strData .= "  <td>" .$intEmployee."&nbsp;</td>\n";
      $intTmp += $intEmployee;
    }
  }
  // tambahkan info untuk data lain-lain (jika ada yang gak termasuk didalamnya)
  $strHeader1 .= "  <td align=center $strDefaultWidth>" .strtoupper(getWords("other"))."&nbsp;</td>\n";
  $strHeader2 .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td>" .($intTmpTotal - $intTmp)."&nbsp;</td>\n";
  $strHeader1 .= "  <td align=center $strDefaultWidth>" .strtoupper(getWords("total"))."&nbsp;</td>\n";
  $strHeader2 .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td>" .($intTmpTotal)."&nbsp;</td>\n";

  // bungkus
  $strHeader1 = " <tr class=tableHeader align=center>$strHeader1</tr>\n";
  $strHeader2 = " <tr class=tableHeader align=center>$strHeader2</tr>\n";
  $strData = " <tr align=center>$strData</tr>\n";

  $strResult .= "<strong>".strtoupper(getWords("department"))."</strong><br>";
  $strResult .= $strTableStart;
  $strResult .= $strHeader1."\n".$strHeader2. "\n".$strData;
  $strResult .= $strTableFinish;
  */
  //-- CARA BARU -- DEPT/SECT JADI BARIS
  $arrEmpDept = [];
  $intTmpTotal = 0;
  $strSQL = "SELECT COUNT(id) AS total, division_code, department_code, section_code , sub_section_code FROM hrd_employee ";
  $strSQL .= "WHERE 1=1 $strKriteria ";
  $strSQL .= "GROUP BY division_code, department_code, section_code, sub_section_code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrEmpDept[$rowDb['division_code']][$rowDb['department_code']][$rowDb['section_code']][$rowDb['sub_section_code']] = $rowDb['total'];
    $intTmpTotal += $rowDb['total'];
  }
  $strSQL = "SELECT COUNT(id) AS total FROM hrd_employee WHERE 1=1 $strKriteria";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $intOtherEmp = $rowDb['total'];
  }
  $strSQL = "SELECT COUNT(id) AS total FROM hrd_employee WHERE 1=1 $strKriteria";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $intTotalEmp = $rowDb['total'];
  }
  $arrDivEmp = [];
  $strSQL = "SELECT COUNT(id) AS total, division_code FROM hrd_employee WHERE 1=1 $strKriteria GROUP BY division_code";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDivEmp[$rowDb['division_code']] = $rowDb['total'];
  }
  $arrDeptEmp = [];
  $strSQL = "SELECT COUNT(id) AS total, division_code, department_code FROM hrd_employee WHERE 1=1 $strKriteria GROUP BY division_code, department_code";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrDeptEmp[$rowDb['division_code']][$rowDb['department_code']] = $rowDb['total'];
  }
  $arrSectEmp = [];
  $strSQL = "SELECT COUNT(id) AS total, division_code, department_code, section_code FROM hrd_employee WHERE 1=1 $strKriteria GROUP BY division_code, department_code, section_code";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrSectEmp[$rowDb['division_code']][$rowDb['department_code']][$rowDb['section_code']] = $rowDb['total'];
  }
  // cari data per department dan tulis
  $strHeader1 = "";
  $strHeader1 .= " <tr class='tableHeader' align=center>\n";
  $strHeader1 .= "  <td nowrap>" . strtoupper(getWords("division")) . "</td><td width=35px>&nbsp;</td>";
  $strHeader1 .= "  <td nowrap>" . strtoupper(getWords("department")) . "</td><td width=35px>&nbsp;</td>";
  $strHeader1 .= "  <td nowrap>" . strtoupper(getWords("section")) . "</td><td width=35px>&nbsp;</td>";
  $strHeader1 .= "  <td nowrap>" . strtoupper(getWords("sub section")) . "</td>";
  $strHeader1 .= "  <td nowrap>" . strtoupper(getWords("employee")) . "</td>";
  $strHeader1 .= " </tr>\n";
  $strHeader2 = "";
  $strData = "";
  $intDiv = 0;
  $intTmp = 0; // data yagn dipakai
  $strSQL = "SELECT * FROM hrd_division WHERE 1=1 ";//division_code LIKE '".getCompanyCode()."%' ";
  if ($strDataDivision != "") {
    $strSQL .= "AND division_code = '$strDataDivision' ";
  }
  $strSQL .= "ORDER BY division_code ";
  $resDiv = $db->execute($strSQL);
  while ($rowDiv = $db->fetchrow($resDiv)) {
    $intDiv++;
    $intDept = 0;
    $intDivSubSect = 0;
    $strTmpData4 = "";
    $strTmpData3 = "";
    $strTmpData2 = "";
    $strTmpData1 = "";
    $strSQL = "SELECT * FROM hrd_department WHERE division_code = '" . $rowDiv['division_code'] . "' ";
    if ($strDataDepartment != "") {
      $strSQL .= "AND department_code = '$strDataDepartment' ";
    }
    $strSQL .= "ORDER BY department_code ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $intDept++;
      $intSection = 0;
      $intDepSubSect = 0;
      $strTmpData3 = "";
      $strTmpData2 = "";
      $strTmpData1 = "";
      $strSQL = "SELECT * FROM hrd_section WHERE division_code = '" . $rowDiv['division_code'] . "' AND department_code = '" . $rowDb['department_code'] . "' ";
      if ($strDataSection != "") {
        $strSQL .= "AND section_code = '$strDataSection' ";
      }
      $strSQL .= "ORDER BY section_code ";
      $resTmp = $db->execute($strSQL);
      while ($rowTmp = $db->fetchrow($resTmp)) {
        $intSection++;
        $intSubSect = 0;
        $strTmpData1 = "";
        $strTmpData2 = "";
        $strSQL = "SELECT * FROM hrd_sub_section WHERE division_code = '" . $rowDiv['division_code'] . "' AND department_code = '" . $rowDb['department_code'] . "' AND section_code = '" . $rowTmp['section_code'] . "' ";
        if ($strDataSubSection != "") {
          $strSQL .= "AND sub_section_code = '$strDataSubSection' ";
        }
        $strSQL .= "ORDER BY sub_section_code ";
        $resTmp2 = $db->execute($strSQL);
        while ($rowTmp2 = $db->fetchrow($resTmp2)) {
          $intSubSect++;
          $intDepSubSect++;
          $intDivSubSect++;
          if ($intSubSect == 1) {
            $strTmpData1 .= "  <td>&nbsp;" . $rowTmp2['sub_section_code'] . " - " . $rowTmp2['sub_section_name'] . "&nbsp;</td>\n";
            $intEmployee = (isset($arrEmpDept[$rowDiv['division_code']][$rowDb['department_code']][$rowTmp['section_code']][$rowTmp2['sub_section_code']])) ? $arrEmpDept[$rowDiv['division_code']][$rowDb['department_code']][$rowTmp['section_code']][$rowTmp2['sub_section_code']] : 0;
            $strTmpData1 .= "  <td align=right>" . $intEmployee . "&nbsp;</td>\n";
            //$strTmpData1 .= "  <td>" .$intEmployee."&nbsp;</td>\n";
          } else {
            $strTmpData2 .= "<tr>\n";
            $strTmpData2 .= "  <td>&nbsp;" . $rowTmp2['sub_section_code'] . " - " . $rowTmp2['sub_section_name'] . "&nbsp;</td>\n";
            $intEmployee = (isset($arrEmpDept[$rowDiv['division_code']][$rowDb['department_code']][$rowTmp['section_code']][$rowTmp2['sub_section_code']])) ? $arrEmpDept[$rowDiv['division_code']][$rowDb['department_code']][$rowTmp['section_code']][$rowTmp2['sub_section_code']] : 0;
            $strTmpData2 .= "  <td align=right>" . $intEmployee . "&nbsp;</td>\n";
            //$strTmpData2 .= "  <td>" .$intEmployee."&nbsp;</td>\n";
            $strTmpData2 .= " </tr>\n";
          }
          $intTmp += $intEmployee;
        }
        $intSectEmp = (isset($arrSectEmp[$rowDiv['division_code']][$rowDb['department_code']][$rowTmp['section_code']])) ? $arrSectEmp[$rowDiv['division_code']][$rowDb['department_code']][$rowTmp['section_code']] : 0;
        if ($intSubSect == 0) {
          $intDepSubSect++;
          $intDivSubSect++;
          if ($intSection == 1) {
            $strTmpData1 .= "  <td>&nbsp;" . $rowTmp['section_code'] . " - " . $rowTmp['section_name'] . "&nbsp;</td><td align=right>$intSectEmp&nbsp;</td><td>&nbsp;</td>\n";
            $strTmpData1 .= "  <td align=right>&nbsp;</td>\n";
            //$strTmpData1 .= "  <td>" .$intEmployee."&nbsp;</td>\n";
          } else {
            $strTmpData2 .= "<tr>\n";
            $strTmpData2 .= "  <td>&nbsp;" . $rowTmp['section_code'] . " - " . $rowTmp['section_name'] . "&nbsp;</td><td align=right>$intSectEmp&nbsp;</td><td>&nbsp;</td>\n";
            $strTmpData2 .= "  <td align=right>&nbsp;</td>\n";
            //$strTmpData2 .= "  <td>" .$intEmployee."&nbsp;</td>\n";
            $strTmpData2 .= " </tr>\n";
          }
          $intTmp += $intEmployee;
          $strTmpData3 .= $strTmpData1;
          $strTmpData3 .= " </tr>\n";
          $strTmpData3 .= $strTmpData2;
        } else {
          if ($intSection != 1) {
            $strTmpData3 .= "<tr>";
          }
          $strTmpData3 .= "<td rowspan=$intSubSect>&nbsp;" . $rowTmp['section_code'] . " - " . $rowTmp['section_name'] . "</td><td rowspan=$intSubSect align=right>$intSectEmp&nbsp;</td>\n";
          $strTmpData3 .= $strTmpData1;
          $strTmpData3 .= " </tr>\n";
          $strTmpData3 .= $strTmpData2;
        }
      }
      $intDeptEmp = (isset($arrDeptEmp[$rowDiv['division_code']][$rowDb['department_code']])) ? $arrDeptEmp[$rowDiv['division_code']][$rowDb['department_code']] : 0;
      if ($intSection == 0) {
        $intDivSubSect++;
        if ($intDept == 1) {
          $strTmpData1 .= "  <td>&nbsp;" . $rowDb['department_code'] . " - " . $rowDb['department_name'] . "&nbsp;</td><td align=right>$intDeptEmp&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>\n";
          $strTmpData1 .= "  <td align=right>&nbsp;</td>\n";
          //$strTmpData1 .= "  <td>" .$intEmployee."&nbsp;</td>\n";
        } else {
          $strTmpData2 .= "<tr>\n";
          $strTmpData2 .= "  <td>&nbsp;" . $rowDb['department_code'] . " - " . $rowDb['department_name'] . "&nbsp;</td><td align=right>$intDeptEmp&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>\n";
          $strTmpData2 .= "  <td align=right>&nbsp;</td>\n";
          $strTmpData2 .= " </tr>\n";
        }
        $intTmp += $intEmployee;
        $strTmpData4 .= $strTmpData1;
        $strTmpData4 .= " </tr>\n";
        $strTmpData4 .= $strTmpData2;
      } else {
        if ($intDept != 1) {
          $strTmpData4 .= "<tr>";
        }
        $strTmpData4 .= "  <td rowspan=$intDepSubSect>&nbsp;" . $rowDb['department_code'] . " - " . $rowDb['department_name'] . "</td><td align=right rowspan=$intDepSubSect>$intDeptEmp&nbsp;</td>\n";
        $strTmpData4 .= $strTmpData3;
      }
    }
    $intDivEmp = (isset($arrDivEmp[$rowDiv['division_code']])) ? $arrDivEmp[$rowDiv['division_code']] : 0;
    if ($intDept == 0) {
      if ($intDiv == 1) {
        $strTmpData1 .= "  <td>&nbsp;" . $rowDiv['division_code'] . " - " . $rowDiv['division_name'] . "&nbsp;</td><td align=right>" . $intDivEmp . "&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>\n";
        $strTmpData1 .= "  <td align=right>&nbsp;</td>\n";
        //$strTmpData1 .= "  <td>" .$intEmployee."&nbsp;</td>\n";
      } else {
        $strTmpData2 .= "<tr>\n";
        $strTmpData2 .= "  <td>&nbsp;" . $rowDiv['division_code'] . " - " . $rowDiv['division_name'] . "&nbsp;</td><td align=right>" . $intDivEmp . "&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>\n";
        $strTmpData2 .= "  <td align=right>&nbsp;</td>\n";
        $strTmpData2 .= " </tr>\n";
      }
      $intTmp += $intEmployee;
      $strData .= $strTmpData1;
      $strData .= " </tr>\n";
      $strData .= $strTmpData2;
    } else {
      if ($intDiv != 1) {
        $strData .= "<tr>";
      }
      $strData .= "  <td rowspan=$intDivSubSect>&nbsp;" . $rowDiv['division_code'] . " - " . $rowDiv['division_name'] . "</td><td align=right rowspan=$intDivSubSect>" . $intDivEmp . "&nbsp;</td>\n";
      $strData .= $strTmpData4;
    }
  }
  // tambahkan info untuk data lain-lain (jika ada yang gak termasuk didalamnya)
  /*
  $strData .= " <tr>\n";
  $strData .= "  <td>&nbsp;" .strtoupper(getWords("other"))."&nbsp;</td>\n";
  $strData .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td align=right>" .($intOtherEmp)."&nbsp;</td>\n";
  $strData .= " </tr>\n";
  */
  $strData .= " <tr>\n";
  $strData .= "  <td>&nbsp;" . strtoupper(getWords("total")) . "&nbsp;</td>\n";
  $strData .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td align=center>&nbsp;</td>\n";
  $strData .= "  <td align=right>" . ($intTotalEmp) . "&nbsp;</td>\n";
  $strData .= " </tr>\n";
  //     $strHeader1 .= "  <td align=center $strDefaultWidth>" .strtoupper(getWords("total"))."&nbsp;</td>\n";
  //     $strHeader2 .= "  <td align=center>&nbsp;</td>\n";
  //     $strData .= "  <td>" .($intTmpTotal)."&nbsp;</td>\n";
  //
  //     // bungkus
  //     $strHeader1 = " <tr class=tableHeader align=center>$strHeader1</tr>\n";
  //     $strHeader2 = " <tr class=tableHeader align=center>$strHeader2</tr>\n";
  //$strData = " <tr align=center>$strData</tr>\n";
  $strResult .= "<strong>" . strtoupper(getWords("department")) . "</strong><br>";
  $strResult .= $strTableStart;
  $strResult .= $strHeader1 . "\n" . "\n" . $strData;
  $strResult .= $strTableFinish;
  // ---- RECAP DATA EMPLOYEE PER POSITION ----
  $arrEmp = [];
  $intTmpTotal = 0;
  $strSQL = "SELECT COUNT(id) AS total, position_code FROM hrd_employee ";
  $strSQL .= "WHERE 1=1 $strKriteria ";
  $strSQL .= "GROUP BY position_code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrEmp[$rowDb['position_code']] = $rowDb['total'];
    $intTmpTotal += $rowDb['total'];
  }
  // cari data per  dan tulis
  $strHeader1 = "";
  $strData = "";
  $intDept = 0;
  $intTmp = 0; // data yagn dipakai
  $strSQL = "SELECT * FROM hrd_position ORDER BY note ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intDept++;
    $strHeader1 .= "  <td align=center title=\"" . $rowDb['position_name'] . "\" $strDefaultWidth>" . strtoupper(
            $rowDb['position_code']
        ) . "&nbsp;</td>\n";
    $intEmployee = (isset($arrEmp[$rowDb['position_code']])) ? $arrEmp[$rowDb['position_code']] : 0;
    $strData .= "  <td>" . $intEmployee . "&nbsp;</td>\n";
    $intTmp += $intEmployee;
  }
  // tambahkan info untuk data lain-lain (jika ada yang gak termasuk didalamnya)
  $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper(getWords("other")) . "&nbsp;</td>\n";
  $strData .= "  <td align=center>" . ($intTmpTotal - $intTmp) . "&nbsp;</td>\n";
  $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper(getWords("total")) . "&nbsp;</td>\n";
  $strData .= "  <td>" . ($intTmpTotal) . "&nbsp;</td>\n";
  // bungkus
  $strHeader1 = " <tr class=tableHeader align=center>$strHeader1</tr>\n";
  $strData = " <tr align=center>$strData</tr>\n";
  $strResult .= "<strong>" . strtoupper(getWords("level")) . "</strong><br>";
  $strResult .= $strTableStart;
  $strResult .= $strHeader1 . "\n" . $strData;
  $strResult .= $strTableFinish;
  // ---- RECAP DATA EMPLOYEE PER FUNGSIONAL ----
  $arrEmp = [];
  $intTmpTotal = 0;
  $strSQL = "SELECT COUNT(id) AS total, functional_code FROM hrd_employee ";
  $strSQL .= "WHERE 1 = 1 $strKriteria ";
  $strSQL .= "GROUP BY functional_code  ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrEmp[$rowDb['functional_code']] = $rowDb['total'];
    $intTmpTotal += $rowDb['total'];
  }
  // cari data per  dan tulis
  $strHeader1 = "";
  $strData = "";
  $intDept = 0;
  $intTmp = 0; // data yagn dipakai
  $strSQL = "SELECT * FROM hrd_functional ORDER BY functional_code";
  $resDb = $db->execute($strSQL);
  /*
  while ($rowDb = $db->fetchrow($resDb)) {
    $intDept++;

    $strHeader1 .= "  <td align=center $strDefaultWidth>" .strtoupper($rowDb['functional_name'])."&nbsp;</td>\n";
    $intEmployee = (isset($arrEmp[$rowDb['functional_name']])) ? $arrEmp[$rowDb['functional_name']] : 0;
    $strData .= "  <td>" .$intEmployee."&nbsp;</td>\n";
    $intTmp += $intEmployee;
  }
  */
  while ($rowDb = $db->fetchrow($resDb)) {
    $intDept++;
    $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper($rowDb['functional_code']) . "&nbsp;</td>\n";
    $intEmployee = (isset($arrEmp[$rowDb['functional_code']])) ? $arrEmp[$rowDb['functional_code']] : 0;
    $strData .= "  <td>" . $intEmployee . "&nbsp;</td>\n";
    $intTmp += $intEmployee;
  }
  // tambahkan info untuk data lain-lain (jika ada yang gak termasuk didalamnya)
  $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper(getWords("other")) . "&nbsp;</td>\n";
  $strData .= "  <td align=center>" . ($intTmpTotal - $intTmp) . "&nbsp;</td>\n";
  $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper(getWords("total")) . "&nbsp;</td>\n";
  $strData .= "  <td>" . ($intTmpTotal) . "&nbsp;</td>\n";
  // bungkus
  $strHeader1 = " <tr class=tableHeader align=center>$strHeader1</tr>\n";
  $strData = " <tr align=center>$strData</tr>\n";
  $strResult .= "<strong>" . strtoupper(getWords("functional")) . "</strong><br>";
  $strResult .= $strTableStart;
  $strResult .= $strHeader1 . "\n" . $strData;
  $strResult .= $strTableFinish;
  // ---- RECAP DATA EMPLOYEE PER JOB GRADE ----
  $arrEmp = [];
  $intTmpTotal = 0;
  $strSQL = "SELECT COUNT(id) AS total, grade_code FROM hrd_employee ";
  $strSQL .= "WHERE 1 = 1 $strKriteria ";
  $strSQL .= "GROUP BY grade_code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrEmp[$rowDb['grade_code']] = $rowDb['total'];
    $intTmpTotal += $rowDb['total'];
  }
  $strResult .= "<strong>" . strtoupper(getWords("job grade")) . "</strong><br>";
  $strHeader1 = "";
  $strData = "";
  $intDept = 0;
  $x = 0;
  $intTmp = 0; // data yagn dipakai
  $strSQL = "SELECT * FROM hrd_salary_grade ORDER BY grade_code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intDept++;
    $x++;
    $strHeader1 .= "  <td align=center $strDefaultWidth>" . $rowDb['grade_code'] . "&nbsp;</td>\n";
    $intEmployee = (isset($arrEmp[$rowDb['grade_code']])) ? $arrEmp[$rowDb['grade_code']] : 0;
    $strData .= "  <td>" . $intEmployee . "&nbsp;</td>\n";
    $intTmp += $intEmployee;
    if ($x == 5) {
      $strHeader1 = " <tr class=tableHeader align=center>$strHeader1</tr>\n";
      $strData = " <tr align=center>$strData</tr>\n";
      $strResult .= $strTableStart;
      $strResult .= $strHeader1 . "\n" . $strData;
      $strResult .= $strTableFinish;
      $x = 0;
      $strHeader1 = "";
      $strData = "";
    }
  }
  // tambahkan info untuk data lain-lain (jika ada yang gak termasuk didalamnya)
  $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper(getWords("other")) . "&nbsp;</td>\n";
  $strData .= "  <td align=center>" . ($intTmpTotal - $intTmp) . "&nbsp;</td>\n";
  $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper(getWords("total")) . "&nbsp;</td>\n";
  $strData .= "  <td>" . ($intTmpTotal) . "&nbsp;</td>\n";
  // bungkus
  $strHeader1 = " <tr class=tableHeader align=center>$strHeader1</tr>\n";
  $strData = " <tr align=center>$strData</tr>\n";
  $strResult .= $strTableStart;
  $strResult .= $strHeader1 . "\n" . $strData;
  $strResult .= $strTableFinish;
  // ---- RECAP DATA EMPLOYEE PER FAMILY STATUS ----
  $arrEmp = [];
  $intTmpTotal = 0;
  $strSQL = "SELECT COUNT(id) AS total, family_status_code FROM hrd_employee ";
  $strSQL .= "WHERE 1 = 1 $strKriteria ";
  $strSQL .= "GROUP BY family_status_code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrEmp[$rowDb['family_status_code']] = $rowDb['total'];
    $intTmpTotal += $rowDb['total'];
  }
  // cari data per  dan tulis
  $strHeader1 = "";
  $strData = "";
  $intDept = 0;
  $intTmp = 0; // data yagn dipakai
  $strSQL = "SELECT * FROM hrd_family_status ORDER BY family_status_code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intDept++;
    $strHeader1 .= "  <td align=center $strDefaultWidth>" . $rowDb['family_status_code'] . "&nbsp;</td>\n";
    $intEmployee = (isset($arrEmp[$rowDb['family_status_code']])) ? $arrEmp[$rowDb['family_status_code']] : 0;
    $strData .= "  <td>" . $intEmployee . "&nbsp;</td>\n";
    $intTmp += $intEmployee;
  }
  // tambahkan info untuk data lain-lain (jika ada yang gak termasuk didalamnya)
  $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper(getWords("other")) . "&nbsp;</td>\n";
  $strData .= "  <td align=center>" . ($intTmpTotal - $intTmp) . "&nbsp;</td>\n";
  $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper(getWords("total")) . "&nbsp;</td>\n";
  $strData .= "  <td>" . ($intTmpTotal) . "&nbsp;</td>\n";
  // bungkus
  $strHeader1 = " <tr class=tableHeader align=center>$strHeader1</tr>\n";
  $strData = " <tr align=center>$strData</tr>\n";
  $strResult .= "<strong>" . strtoupper(getWords("family status")) . "</strong><br>";
  $strResult .= $strTableStart;
  $strResult .= $strHeader1 . "\n" . $strData;
  $strResult .= $strTableFinish;
  /*
      // ---- RECAP DATA EMPLOYEE PER religion ----
      $arrEmp = array();
      $intTmpTotal = 0;
      $strSQL  = "SELECT COUNT(id) AS total, religion_code FROM hrd_employee ";
      $strSQL .= "WHERE active = 1 $strKriteria ";
      $strSQL .= "GROUP BY religion_code ";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) {
        $arrEmp[$rowDb['religion_code']] = $rowDb['total'];
        $intTmpTotal += $rowDb['total'];
      }
      // cari data per  dan tulis
      $strHeader1 = "";
      $strData = "";
      $intDept = 0;
      $intTmp = 0; // data yagn dipakai
      $strSQL  = "SELECT * FROM hrd_religion ORDER BY code ";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) {
        $intDept++;

        $strHeader1 .= "  <td align=center title=\"" .$rowDb['name']."\" $strDefaultWidth>" .strtoupper($rowDb['name'])."&nbsp;</td>\n";
        $intEmployee = (isset($arrEmp[$rowDb['code']])) ? $arrEmp[$rowDb['code']] : 0;
        $strData .= "  <td>" .$intEmployee."&nbsp;</td>\n";
        $intTmp += $intEmployee;
      }
      // tambahkan info untuk data lain-lain (jika ada yang gak termasuk didalamnya)
      $strHeader1 .= "  <td align=center $strDefaultWidth>" .strtoupper(getWords("other"))."&nbsp;</td>\n";
      $strData .= "  <td align=center>" .($intTmpTotal - $intTmp)."&nbsp;</td>\n";
      $strHeader1 .= "  <td align=center $strDefaultWidth>" .strtoupper(getWords("total"))."&nbsp;</td>\n";
      $strData .= "  <td>" .($intTmpTotal)."&nbsp;</td>\n";

      // bungkus
      $strHeader1 = " <tr class=tableHeader align=center>$strHeader1</tr>\n";
      $strData = " <tr align=center>$strData</tr>\n";

      $strResult .= "<strong>".strtoupper(getWords("religion"))."</strong><br>";
      $strResult .= $strTableStart;
      $strResult .= $strHeader1."\n".$strData;
      $strResult .= $strTableFinish;
  */
  // ---- RECAP DATA EMPLOYEE PER EDUCATION LEVEL ----
  $arrEmp = [];
  $intTmpTotal = 0;
  $strSQL = "SELECT COUNT(id) AS total, education_level_code FROM hrd_employee ";
  $strSQL .= "WHERE 1 = 1 $strKriteria ";
  $strSQL .= "GROUP BY education_level_code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrEmp[$rowDb['education_level_code']] = $rowDb['total'];
    $intTmpTotal += $rowDb['total'];
  }
  // cari data per department dan tulis
  $strHeader1 = "";
  $strData = "";
  $intDept = 0;
  $intTmp = 0; // data yagn dipakai
  $strSQL = "SELECT * FROM hrd_education_level ORDER BY code ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $intDept++;
    $strHeader1 .= "  <td align=center $strDefaultWidth>" . $rowDb['code'] . "&nbsp;</td>\n";
    $intEmployee = (isset($arrEmp[$rowDb['code']])) ? $arrEmp[$rowDb['code']] : 0;
    $strData .= "  <td>" . $intEmployee . "&nbsp;</td>\n";
    $intTmp += $intEmployee;
  }
  // tambahkan info untuk data lain-lain (jika ada yang gak termasuk didalamnya)
  $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper(getWords("other")) . "&nbsp;</td>\n";
  $strData .= "  <td align=center>" . ($intTmpTotal - $intTmp) . "&nbsp;</td>\n";
  $strHeader1 .= "  <td align=center $strDefaultWidth>" . strtoupper(getWords("total")) . "&nbsp;</td>\n";
  $strData .= "  <td>" . ($intTmpTotal) . "&nbsp;</td>\n";
  // bungkus
  $strHeader1 = " <tr class=tableHeader align=center>$strHeader1</tr>\n";
  $strData = " <tr align=center>$strData</tr>\n";
  $strResult .= "<strong>" . strtoupper(getWords("education level")) . "</strong><br>";
  $strResult .= $strTableStart;
  $strResult .= $strHeader1 . "\n" . $strData;
  $strResult .= $strTableFinish;
  writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  return $strResult;
} // showData
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  // ------ AMBIL DATA KRITERIA -------------------------
  (isset($_REQUEST['dataDateFrom'])) ? $strDateFrom = $_REQUEST['dataDateFrom'] : $strDateFrom = "";
  (isset($_REQUEST['dataDateThru'])) ? $strDateThru = $_REQUEST['dataDateThru'] : $strDateThru = "";
  (isset($_REQUEST['dataManagement'])) ? $strDataManagement = $_REQUEST['dataManagement'] : $strDataManagement = "";
  (isset($_REQUEST['dataDivision'])) ? $strDataDivision = $_REQUEST['dataDivision'] : $strDataDivision = "";
  (isset($_REQUEST['dataDepartment'])) ? $strDataDepartment = $_REQUEST['dataDepartment'] : $strDataDepartment = "";
  (isset($_REQUEST['dataSection'])) ? $strDataSection = $_REQUEST['dataSection'] : $strDataSection = "";
  (isset($_REQUEST['dataSubSection'])) ? $strDataSubSection = $_REQUEST['dataSubSection'] : $strDataSubSection = "";
  scopeData(
      $strDataEmployee,
      $strDataSubSection,
      $strDataSection,
      $strDataDepartment,
      $strDataDivision,
      $_SESSION['sessionUserRole'],
      $arrUserInfo
  );
  // ------------ GENERATE KRITERIA QUERY,JIKA ADA -------------
  $strKriteria = "";
  $strCompanyCode = getCompanyCode();
  // $strSQL  = "SELECT company_name FROM hrd_company WHERE company_code = '$strCompanyCode' ";
  // $resDb = $db->execute($strSQL);
  // if ($rowDb = $db->fetchrow($resDb)) {
  // $strCompanyName = $rowDb['company_name'];
  // }
  if ($strDataManagement != "") {
    $strKriteria .= "AND management_code = '$strDataManagement' ";
    $strSQL = "SELECT management_name FROM hrd_management WHERE management_code = '$strDataManagement' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strManagementName = $rowDb['management_name'];
    }
  }
  // echo "tes ".$strDataManagement;
  if ($strDataDivision != "") {
    $strKriteria .= "AND division_code = '$strDataDivision' ";
    $strSQL = "SELECT division_name FROM hrd_division WHERE division_code = '$strDataDivision' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strDivisionName = $rowDb['division_name'];
    }
  }
  if ($strDataDepartment != "") {
    $strKriteria .= "AND department_code = '$strDataDepartment' ";
    $strSQL = "SELECT department_name FROM hrd_department WHERE department_code = '$strDataDepartment' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strDepartmentName = $rowDb['department_name'];
    }
  }
  if ($strDataSection != "") {
    $strKriteria .= "AND section_code = '$strDataSection' ";
    $strSQL = "SELECT section_name FROM hrd_section WHERE section_code = '$strDataSection' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strSectionName = $rowDb['section_name'];
    }
  }
  if ($strDataSubSection != "") {
    $strKriteria .= "AND sub_section_code = '$strDataSubSection' ";
    $strSQL = "SELECT sub_section_name FROM hrd_sub_section WHERE sub_section_code = '$strDataSubSection' ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $strSubSectionName = $rowDb['sub_section_name'];
    }
  }
  if (validStandardDate($strDateThru) && validStandardDate($strDateFrom)) {
    $arrDate = explode("/", $strDateThru);
    $strDateThru_ = $arrDate[2] . $arrDate[0] . $arrDate[1];
    $arrDate = explode("/", $strDateFrom);
    $strDateFrom_ = $arrDate[2] . $arrDate[0] . $arrDate[1];
    $strKriteria .= "AND management_code IS NOT NULL AND management_code <> '' AND join_date <= '$strDateThru_' AND (resign_date >= '$strDateFrom_' OR resign_date IS NULL)";
    $strReportSubTitle .= "Per " . pgDateFormat($strDateThru, "d F Y");
  }
  $strKriteria .= $strKriteriaCompany;
  $bolExcel = (isset($_REQUEST['btnExcel']));
  if ($bolCanView) {
    $strDataDetail = getData($db, $strKriteria, $strDateFrom, $strDateThru);
    if ($bolExcel) {
      // ambil data CSS-nya
      if (file_exists("bw.css")) {
        $strStyle = "bw.css";
      }
      $strPrintCss = "";
      $strPrintInit = "";
      headeringExcel("employeedata.xls");
    }
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
}
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>