<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/employee_function.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
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
include_once('form_object.php');
$bolPrint = (isset($_REQUEST['btnPrint']) || isset($_REQUEST['btnPrintAll']) || isset($_REQUEST['btnExcel']));
$strUserName = $_SESSION['sessionUserName'];
//---- INISIALISASI ----------------------------------------------------
$strWordsEmployeeQuotaList = getWords("employee quota list");
$strWordsInputMedicalClaim = getWords("input claim");
$strWordsMedicalClaimList = getWords("claim list");
$strWordsEmployeeMedicalReport = getWords("employee medical report");
$strWordsFormNo = getWords("form no.");
$strWordsLastno = getWords("last no");
$strWordsEmployeeID = getWords("employee id");
$strWordsFamilyList = getWords("family list");
$strWordsNo = getWords("no.");
$strWordsName = getWords("name");
$strWordsRelation = getWords("relation");
$strWordsType = getWords("type");
$strWordsCode = getWords("code");
$strWordsTreatmentDisease = getWords("treatment/disease");
$strWordsMedicine = getWords("medicine");
$strWordsTreatmentDateFrom = getWords("treatment date from");
$strWordsTreatmentDateThru = getWords("treatment date thru");
$strWordsClaimDate = getWords("claim date");
$strWordsCost = getWords("cost");
$strWordsApproved = getWords("approved");
$strWordsDelete = getWords("delete");
$strWordsMore = getWords("more");
$strWordsClearForm = getWords("clear form");
$strWordsCashRequest = getWords("cash request");
$strWordsCASHREQUESTFORM = getWords("cash request form");
$strWordsFormNo = getWords("form no.");
$strWordsRequestDate = getWords("request date");
$strWordsAccountNo = getWords("account no.");
$strWordsBudgetCode = getWords("budget code");
$strWordsAmount = getWords("amount");
$strWordsNote = getWords("note");
$strWordsSaveRequest = getWords("save request");
$strWordsCancelRequest = getWords("cancel request");
$strWordsListOfMedicalCode = getWords("list of medical code");
$strDataDetail = "";
$strButtons = "";
$intTotalData = 0;
$strInitCalendar = "";
$strMedicalTypeArray = ""; // array untuk javascript
$strMessages = "";
$strMsgClass = "";
$bolError = false;
$strNow = date("Y-m-d");
$arrData = [
    "dataNo"            => "",       // komponen kode
    //"dataCode" => "PBP", // default
    "dataYear"          => "",
    "dataMonth"         => "",
    "dataEmployee"      => "",
    "dataEmployeeName"  => "",
    "dataSection"       => "",
    "dataMethod"        => "",
    "dataAccount"       => "",
    "dataPayment"       => date("Y-m-d"),
    "dataPaymentStatus" => "0",
    "dataStatus"        => "0",
    "dataTotalCost"     => 0,
    "dataTotalApproved" => 0,
];
$strInputPaymentDate = "";
$strInputNo = "";
$strInputEmployee = "";
$strInputMethod = "";
$strInputAccount = "";
$strInputPaymentStatus = "";
$strInputStatus = "";
$strMedicalCodeList = "";
$strInputLastNo = "";
// data permohonan kas (FPK)
$strCashStyle = "display:none";
$strDisabledCash = "disabled";
$arrDataCash = [
    "dataCode"    => "FPK-HRD",
    "dataNo"      => "",
    "dataMonth"   => "",
    "dataYear"    => "",
    "dataAccount" => "",
    "dataBudget"  => "",
    "dataDate"    => $strNow,
    "dataNote"    => "",
    "dataAmount"  => "0",
    "dataID"      => "",
];
//----------------------------------------------------------------------
//--- DAFTAR FUNSI------------------------------------------------------
// fungsi untuk menampilkan data
// $db = kelas database, $intRows = jumlah baris (return)
// $strKriteria = query kriteria, $strOrder = query ORder by
function getData($db, &$intRows, $strDataID = "")
{
  global $words;
  global $ARRAY_FAMILY_RELATION;
  global $intDefaultWidth;
  global $strInitCalendar;
  global $strEmptyOption;
  global $arrData;
  global $bolIsEmployee;
  global $bolSave;
  global $bolError;
  global $_REQUEST;
  $strIDEmployee = (isset($arrData['dataEmployee'])) ? getIDEmployee($db, $arrData['dataEmployee']) : "";
  $strFamCriteria = ($strIDEmployee == "") ? $strIDEmployee : " AND id_employee = '$strIDEmployee' ";
  $intMaxShow = 4; // tambahan yang perlu dimunculkan
  $intAdd = 20; // maksimum tambahan
  //$intRows = 0;
  $intShown = 0;
  $strResult = "";
  $strSelect = "";
  $strNow = date("Y-m-d");
  $strReadonly = ($bolIsEmployee) ? "readonly" : "";
  if ($bolSave && $bolError) {
    $arrData['dataEmployee'] = (isset($arrData['dataEmployee'])) ? $arrData['dataEmployee'] : "";
    $arrData['dataEmployeeName'] = (isset($arrData['dataEmployeeName'])) ? $arrData['dataEmployeeName'] : "";
    $arrData['dataSection'] = (isset($_REQUEST['dataSection'])) ? $_REQUEST['dataSection'] : "";
    $arrData['dataNo'] = (isset($_REQUEST['dataNo'])) ? $_REQUEST['dataNo'] : "";
    //$arrData['dataCode'] = (isset($_REQUEST['dataCode'])) ? $_REQUEST['dataCode'] : "";
    $arrData['dataMonth'] = (isset($_REQUEST['dataMonth'])) ? $_REQUEST['dataMonth'] : "";
    $arrData['dataYear'] = (isset($_REQUEST['dataYear'])) ? $_REQUEST['dataYear'] : "";
    $arrData['dataMethod'] = (isset($_REQUEST['dataMethod'])) ? $_REQUEST['dataMethod'] : "";
    $arrData['dataAccount'] = (isset($_REQUEST['dataAccount'])) ? $_REQUEST['dataAccount'] : "";
    $arrData['dataPayment'] = (isset($_REQUEST['dataPayment'])) ? $_REQUEST['dataPayment'] : "";
    $arrData['dataPaymentStatus'] = (isset($_REQUEST['dataPaymentStatus'])) ? $_REQUEST['dataPaymentStatus'] : "";
    $arrData['dataStatus'] = (isset($_REQUEST['dataStatus'])) ? $_REQUEST['dataStatus'] : 0;
    //(isset($_REQUEST['maxDetail'])) ? $intRows = $_REQUEST['maxDetail'] : $intRows = 0;
    (isset($_REQUEST['numShow'])) ? $intShown = $_REQUEST['numShow'] : $intShown = 0;
    for ($i = 1; $i <= $intShown; $i++) {
      (isset($_REQUEST['detailID' . $i])) ? $strID = $_REQUEST['detailID' . $i] : $strID = "";
      if (isset($_REQUEST['detailName' . $i])) {
        $strName = $_REQUEST['detailName' . $i];
      } else {
        continue;
      }
      (isset($_REQUEST['detailRelation' . $i])) ? $strRelation = $_REQUEST['detailRelation' . $i] : $strRelation = "-1";
      (isset($_REQUEST['detailType' . $i])) ? $strType = $_REQUEST['detailType' . $i] : $strType = "0";
      /*if (isset($_REQUEST['detailRoom'.$i]))
      {
         $strCheckRoom = "checked";
         $strDisableRoom = "";
      }
      else
      {
         $strCheckRoom = "";
         $strDisableRoom = "disabled";
      }*/
      (isset($_REQUEST['detailMedicalCode' . $i])) ? $strMedicalCode = $_REQUEST['detailMedicalCode' . $i] : $strMedicalCode = "";
      (isset($_REQUEST['detailDisease' . $i])) ? $strDisease = $_REQUEST['detailDisease' . $i] : $strDisease = "";
      (isset($_REQUEST['detailMedicine' . $i])) ? $strMedicine = $_REQUEST['detailMedicine' . $i] : $strMedicine = "";
      (isset($_REQUEST['detailMedicalDate' . $i])) ? $strMedicalDate = $_REQUEST['detailMedicalDate' . $i] : $strMedicalDate = date(
          "Y-m-d"
      );
      (isset($_REQUEST['detailMedicalDateThru' . $i])) ? $strMedicalDateThru = $_REQUEST['detailMedicalDateThru' . $i] : $strMedicalDateThru = date(
          "Y-m-d"
      );
      (isset($_REQUEST['detailClaimDate' . $i])) ? $strClaimDate = $_REQUEST['detailClaimDate' . $i] : $strClaimDate = date(
          "Y-m-d"
      );
      (isset($_REQUEST['detailCost' . $i])) ? $strCost = $_REQUEST['detailCost' . $i] : $strCost = "0";
      (isset($_REQUEST['detailCostApproved' . $i])) ? $strCostApproved = $_REQUEST['detailCostApproved' . $i] : $strCostApproved = "0";
      $intType = ($strType == "") ? 0 : $strType;
      $strSelect = (substr($_REQUEST['detailName' . $i], -2) == -1) ? "selected" : "";
      $strResult .= "<tr valign=top  id=\"detailRows$i\">\n";
      $strResult .= "  <td align=right><input type=hidden name=detailID$i value=\"" . $strID . "\">$i&nbsp;</td>";
      $strResult .= "  <td nowrap>" . getFamilyList(
              $db,
              "detailName$i",
              $_REQUEST['detailName' . $i],
              "<option value=''> </option><option value=\"" . $arrData['dataEmployeeName'] . "|-1\" $strSelect>" . $arrData['dataEmployeeName'] . "</option>",
              $strFamCriteria
          ) . "&nbsp;<font color=\"red\">*</font></td>";
      $strResult .= "  <td  nowrap>" . getMedicalTreatmentTypeList(
              "detailType$i",
              true,
              $strType,
              "",
              "onChange=\"changeMedicalType($i);\" "
          ) . /*<input type=checkbox id=\"detailRoom$i\" name=\"detailRoom$i\" $strCheckRoom $strDisableRoom>"&nbsp;".getWords("room")."*/
          "&nbsp;<font color=\"red\">*</font></td >";
      $strResult .= "  <td nowrap>" . getMedicalTreatmentCodeList(
              $db,
              "detailMedicalCode$i",
              $strMedicalCode,
              "",
              "WHERE \"type\" = '$intType' ",
              "style=\"width:60px\""
          ) . "&nbsp;<font color=\"red\">*</font></td>";
      $strResult .= "  <td nowrap><input type=text size=30 maxlength=50 name=detailDisease$i value=\"" . $strDisease . "\"></td>";
      $strResult .= "  <td nowrap><input type=text size=30 maxlength=50 name=detailMedicine$i value=\"" . $strMedicine . "\"></td>";
      $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailMedicalDate$i id=\"detailMedicalDate$i\" value=\"" . $strMedicalDate . "\" class='date'>&nbsp;";
      $strResult .= "<input type=button id=\"btnMedicalDate$i\" value='..'></td>";
      $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailMedicalDateThru$i id=\"detailMedicalDateThru$i\" value=\"" . $strMedicalDateThru . "\" class='date'>&nbsp;";
      $strResult .= "<input type=button id=\"btnMedicalDateThru$i\" value='..'></td>";
      $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailClaimDate$i id=\"detailClaimDate$i\" value=\"" . $strClaimDate . "\" class='date'>&nbsp;";
      $strResult .= "<input type=button id=\"btnClaimDate$i\" value='..'></td>";
      $strResult .= "  <td nowrap><input type=text size=15 maxlength=20 name=detailCost$i value=\"" . (float)$strCost . "\" class=numeric onChange=\"onCostChanged($i);\" class='numeric'>&nbsp;<font color=\"red\">*</font></td>";
      $strResult .= "  <td nowrap><input type=text size=15 maxlength=20 name=detailCostApproved$i value=\"" . (float)$strCostApproved . "\" class=numeric onChange=\"getTotalCostApproved();\" $strReadonly class='numeric'></td>";
      $strAction = " onChange = \"chkDeleteChanged($i);\" ";
      $strResult .= "  <td align=center><input type=checkbox name='chkID$i' $strAction></td>\n";
      $strResult .= "</tr>\n";
      $strInitCalendar .= "Calendar.setup({ inputField:\"detailMedicalDate$i\", button:\"btnMedicalDate$i\" });\n";
      $strInitCalendar .= "Calendar.setup({ inputField:\"detailMedicalDateThru$i\", button:\"btnMedicalDateThru$i\" });\n";
      $strInitCalendar .= "Calendar.setup({ inputField:\"detailClaimDate$i\", button:\"btnClaimDate$i\" });\n";
    }
    $intRows = $i - 1;
  } else {
    if ($strDataID != "") {
      $strSQL = "SELECT t1.*, t2.\"id\" as id_e, t2.employee_id, t2.employee_name, ";
      $strSQL .= "t3.section_name FROM hrd_medical_claim_master AS t1 ";
      $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t2.id = t1.id_employee ";
      $strSQL .= "LEFT JOIN hrd_section AS t3 ON t3.section_code = t2.section_code ";
      $strSQL .= "WHERE t1.id = '$strDataID' ";
      $resM = $db->execute($strSQL);
      if ($rowM = $db->fetchrow($resM)) {
        $arrData['dataEmployee'] = $rowM['employee_id'];
        $arrData['dataEmployeeName'] = $rowM['employee_name'];
        $arrData['dataSection'] = $rowM['section_name'];
        $arrData['dataNo'] = $rowM['no'];
        //$arrData['dataCode'] = $rowM['code'];
        $arrData['dataMonth'] = $rowM['month_code'];
        $arrData['dataYear'] = $rowM['year_code'];
        $arrData['dataMethod'] = $rowM['method'];
        $arrData['dataAccount'] = $rowM['account'];
        $arrData['dataPayment'] = $rowM['payment_date'];
        $arrData['dataPaymentStatus'] = $rowM['payment_status'];
        $arrData['dataStatus'] = $rowM['status'];
        $strFamCriteria = ($rowM['id_e'] == "") ? $strIDEmployee : " AND id_employee = '" . $rowM['id_e'] . "' ";
        $strSQL = "SELECT * FROM hrd_medical_claim WHERE id_master  = '" . $rowM['id'] . "' ";
        $resDb = $db->execute($strSQL);
        while ($rowDb = $db->fetchrow($resDb)) {
          $intRows++;
          $intShown++;
          $intType = ($rowDb['type'] == "") ? 0 : $rowDb['type'];
          /*if ($rowDb['room'] == "t")
          {
             $strCheckRoom = "checked";
             $strDisableRoom = "";
          }
          else
          {
             $strCheckRoom = "";
             $strDisableRoom = "disabled" ;
          }*/
          if ($rowDb['relation'] == -1) {
            $strSelect = "selected";
          }
          $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
          $strResult .= "  <td align=right><input type=hidden name=detailID$intRows value=\"" . $rowDb['id'] . "\">$intRows&nbsp;</td>";
          $strResult .= "  <td nowrap>" . getFamilyList(
                  $db,
                  "detailName$intRows",
                  $rowDb['name'] . "|" . $rowDb['relation'],
                  "<option value=''> </option><option value=\"" . $arrData['dataEmployeeName'] . "|-1\" $strSelect>" . $arrData['dataEmployeeName'] . "</option>",
                  $strFamCriteria
              ) . "&nbsp;<font color=\"red\">*</font></td>";
          $strResult .= "  <td nowrap>" . getMedicalTreatmentTypeList(
                  "detailType$intRows",
                  true,
                  $rowDb['type'],
                  "",
                  "onChange=\"changeMedicalType($intRows);\" "
              ) . /*"<input type=checkbox id=\"detailRoom$intRows\" name=\"detailRoom$intRows\" $strCheckRoom $strDisableRoom>&nbsp;".getWords("room").*/
              "&nbsp;<font color=\"red\">*</font></td>";
          $strResult .= "  <td nowrap>" . getMedicalTreatmentCodeList(
                  $db,
                  "detailMedicalCode$intRows",
                  $rowDb['medical_code'],
                  "",
                  "WHERE type = '$intType' ",
                  "style=\"width:60px\""
              ) . "&nbsp;<font color=\"red\">*</font></td>";
          $strResult .= "  <td nowrap><input type=text size=30 maxlength=50 name=detailDisease$intRows value=\"" . $rowDb['disease'] . "\"></td>";
          $strResult .= "  <td nowrap><input type=text size=30 maxlength=50 name=detailMedicine$intRows value=\"" . $rowDb['medicine'] . "\"></td>";
          $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailMedicalDate$intRows id=\"detailMedicalDate$intRows\" value=\"" . $rowDb['medical_date'] . "\" class='date'>&nbsp;";
          $strResult .= "<input type=button id=\"btnMedicalDate$intRows\" value='..'></td>";
          $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailMedicalDateThru$intRows id=\"detailMedicalDateThru$intRows\" value=\"" . $rowDb['medical_date_thru'] . "\" class='date'>&nbsp;";
          $strResult .= "<input type=button id=\"btnMedicalDateThru$intRows\" value='..'></td>";
          $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailClaimDate$intRows id=\"detailClaimDate$intRows\" value=\"" . $rowDb['claim_date'] . "\" class='date'>&nbsp;";
          $strResult .= "<input type=button id=\"btnClaimDate$intRows\" value='..'></td>";
          $strResult .= "  <td nowrap align=right><input type=text size=15 maxlength=20 name=detailCost$intRows value=\"" . (float)$rowDb['cost'] . "\" class=numeric onChange=\"onCostChanged($intRows);\" class='numeric'>&nbsp;<font color=\"red\">*</font></td>";
          $strResult .= "  <td nowrap align=right><input type=text size=15 maxlength=20 name=detailCostApproved$intRows value=\"" . (float)$rowDb['approved_cost'] . "\" class=numeric onChange=\"getTotalCostApproved();\" $strReadonly class='numeric'></td>";
          $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
          $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' $strAction></td>\n";
          $strResult .= "</tr>\n";
          $strInitCalendar .= "Calendar.setup({ inputField:\"detailMedicalDate$intRows\", button:\"btnMedicalDate$intRows\" });\n";
          $strInitCalendar .= "Calendar.setup({ inputField:\"detailMedicalDateThru$intRows\", button:\"btnMedicalDateThru$intRows\" });\n";
          $strInitCalendar .= "Calendar.setup({ inputField:\"detailClaimDate$intRows\", button:\"btnClaimDate$intRows\" });\n";
        }
        writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
      }
    }
  }
  // tambahkan dengan data kosong
  //if ($intRows < $intAdd) $intAdd = 4;
  for ($i = $intShown; $i <= $intAdd; $i++) {
    $intRows++;
    if ($intRows <= $intMaxShow) {
      $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
      $intShown++;
      $strDisabled = "";
    } else {
      $strResult .= "<tr valign=top  id=\"detailRows$intRows\" style=\"display:none\">\n";
      $strDisabled = "disabled";
    }
    $strResult .= "  <td align=right>$intRows&nbsp;</td>";
    $strResult .= "  <td nowrap>" . getFamilyList(
            $db,
            "detailName$intRows",
            "",
            "<option value=''> </option><option value=\"" . $arrData['dataEmployeeName'] . "|-1\">" . $arrData['dataEmployeeName'] . "</option>",
            $strFamCriteria,
            $strDisabled
        ) . "&nbsp;<font color=\"red\">*</font></td>";
    $strResult .= "  <td nowrap>" . getMedicalTreatmentTypeList(
            "detailType$intRows",
            true,
            "",
            "",
            " onChange=\"changeMedicalType($intRows);\" $strDisabled"
        ) . /*"<input type=checkbox name=\"detailRoom$intRows\" id=\"detailRoom$intRows\" disabled \>&nbsp;".getWords("room").*/
        "&nbsp;<font color=\"red\">*</font></td>";
    $strResult .= "  <td nowrap>" . getMedicalTreatmentCodeList(
            $db,
            "detailMedicalCode$intRows",
            "",
            "",
            "WHERE type=0 ",
            "style=\"width:60px\" $strDisabled"
        ) . "&nbsp;<font color=\"red\">*</font></td>";
    $strResult .= "  <td nowrap><input type=text size=30 maxlength=50 name=detailDisease$intRows $strDisabled></td>";
    $strResult .= "  <td nowrap><input type=text size=30 maxlength=50 name=detailMedicine$intRows $strDisabled></td>";
    $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailMedicalDate$intRows id=\"detailMedicalDate$intRows\" value=$strNow $strDisabled class='date'>&nbsp;";
    $strResult .= "<input type=button id=\"btnMedicalDate$intRows\" value='..'></td>";
    $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailMedicalDateThru$intRows id=\"detailMedicalDateThru$intRows\" value=$strNow $strDisabled class='date'>&nbsp;";
    $strResult .= "<input type=button id=\"btnMedicalDateThru$intRows\" value='..'></td>";
    $strResult .= "  <td nowrap><input type=text size=15 maxlength=10 name=detailClaimDate$intRows id=\"detailClaimDate$intRows\" value=$strNow $strDisabled class='date'>&nbsp;";
    $strResult .= "<input type=button id=\"btnClaimDate$intRows\" value='..'></td>";
    $strResult .= "  <td nowrap align=right><input type=text size=15 maxlength=20 name=detailCost$intRows $strDisabled value=0 class=numeric onChange=\"onCostChanged($intRows);\" class='numeric'>&nbsp;<font color=\"red\">*</font></td>";
    $strResult .= "  <td nowrap align=right><input type=text size=15 maxlength=20 name=detailCostApproved$intRows $strDisabled value=0 class=numeric onChange=\"getTotalCostApproved();\" $strReadonly class='numeric'></td>";
    $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center><input type=checkbox name='chkID$intRows' $strAction></td>\n";
    $strResult .= "</tr>\n";
    $strInitCalendar .= "Calendar.setup({ inputField:\"detailMedicalDate$intRows\", button:\"btnMedicalDate$intRows\" });\n";
    $strInitCalendar .= "Calendar.setup({ inputField:\"detailClaimDate$intRows\", button:\"btnClaimDate$intRows\" });\n";
  }
  // tambahkan hidden data
  $strResult .= "<input type=hidden name=maxDetail value=$intRows>";
  $strResult .= "<input type=hidden name=numShow value=$intShown>";
  return $strResult;
} // showData
function getDataPrint($db, &$intRows, $strDataID = "")
{
  global $words;
  global $ARRAY_FAMILY_RELATION;
  global $arrData;
  $intMaxShow = 4; // tambahan yang perlu dimunculkan
  $strResult = "";
  $strNow = date("Y-m-d");
  if ($strDataID != "") {
    $strSQL = "SELECT t1.*, t2.employee_id, t2.employee_name, t4.department_name, ";
    $strSQL .= "t3.section_name FROM hrd_medical_claim_master AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t2.id = t1.id_employee ";
    $strSQL .= "LEFT JOIN hrd_section AS t3 ON t3.section_code = t2.section_code ";
    $strSQL .= "LEFT JOIN hrd_department AS t4 ON t4.department_code = t2.department_code ";
    $strSQL .= "WHERE t1.id = '$strDataID' ";
    $resM = $db->execute($strSQL);
    if ($rowM = $db->fetchrow($resM)) {
      $arrData['dataEmployee'] = $rowM['employee_id'];
      $arrData['dataEmployeeName'] = $rowM['employee_name'];
      $arrData['dataSection'] = ($rowM['section_name'] == "") ? $rowM['department_name'] : $rowM['section_name'];
      $arrData['dataNo'] = $rowM['no'];
      //$arrData['dataCode'] = $rowM['code'];
      $arrData['dataMonth'] = $rowM['month_code'];
      $arrData['dataYear'] = $rowM['year_code'];
      $arrData['dataMethod'] = $rowM['method'];
      $arrData['dataAccount'] = $rowM['account'];
      $arrData['dataPayment'] = $rowM['payment_date'];
      $arrData['dataPaymentStatus'] = $rowM['payment_status'];
      $arrData['dataStatus'] = $rowM['status'];
      $strSQL = "SELECT * FROM hrd_medical_claim WHERE id_master  = '" . $rowM['id'] . "' ";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) {
        $intRows++;
        $arrData['dataTotalCost'] += $rowDb['cost'];
        $arrData['dataTotalApproved'] += $rowDb['approved_cost'];
        if ($rowDb['claim_date'] != "") {
          $arrData['dataDate'] = $rowDb['claim_date'];
        }
        $strFamily = ($rowDb['relation'] < 0) ? "" : $words[$ARRAY_FAMILY_RELATION[$rowDb['relation']]];
        $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
        $strResult .= "  <td align=right>$intRows&nbsp;</td>";
        $strResult .= "  <td>&nbsp;" . $rowDb['name'] . "</td>";
        $strResult .= "  <td align=center>&nbsp;" . $strFamily . "</td>";
        $strResult .= "  <td align=center>&nbsp;" . $rowDb['medical_code'] . "</td>";
        $strResult .= "  <td>&nbsp;" . $rowDb['disease'] . "</td>";
        $strResult .= "  <td>&nbsp;" . $rowDb['medicine'] . "</td>";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['medical_date'], "d-M-y") . "&nbsp;";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['medical_date'], "d-M-y") . "&nbsp;";
        $strResult .= "  <td align=center>" . pgDateFormat($rowDb['claim_date'], "d-M-y") . "&nbsp;";
        $strResult .= "  <td align=right nowrap>" . standardFormat($rowDb['cost']) . "&nbsp;</td>";
        $strResult .= "  <td align=right nowrap>" . standardFormat($rowDb['approved_cost']) . "&nbsp;</td>";
        $strResult .= "</tr>\n";
      }
      // tambahakn baris kosong
      for ($i = $intRows; $i < $intMaxShow; $i++) {
        $intRows++;
        $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
        $strResult .= "  <td align=right>$intRows&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "  <td>&nbsp;</td>";
        $strResult .= "</tr>\n";
      }
      writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
    }
  }
  return $strResult;
} // showData
// fungsi untuk menyimpan data
function saveData($db, &$strError)
{
  global $_REQUEST;
  global $_SESSION;
  global $messages;
  global $error;
  global $strDataID;
  global $intFormNumberDigit;
  global $ARRAY_MEDICAL_TREATMENT_GROUP;
  $strUpdaterID = $_SESSION['sessionUserID'];
  $strError = "";
  $bolOK = true;
  // cari data master medical type untuk generate header tabel plafon
  $strSQL = "SELECT * FROM hrd_medical_type";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $arrMedGroup[$rowDb['type']][$rowDb['code']] = $rowDb['id'];
  }
  $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
  $strDataMonth = (isset($_REQUEST['dataMonth'])) ? trim($_REQUEST['dataMonth']) : "";
  $strDataYear = (isset($_REQUEST['dataYear'])) ? trim($_REQUEST['dataYear']) : "";
  $strDataEmployee = (isset($_REQUEST['dataEmployee'])) ? trim($_REQUEST['dataEmployee']) : "";
  $strDataMethod = (isset($_REQUEST['dataMethod'])) ? $_REQUEST['dataMethod'] : "0";
  $strDataAccount = (isset($_REQUEST['dataAccount'])) ? $_REQUEST['dataAccount'] : "";
  $strDataPaymentDate = (isset($_REQUEST['dataPaymentDate'])) ? $_REQUEST['dataPaymentDate'] : date("Y-m-d");
  $strDataStatus = (isset($_REQUEST['dataStatus'])) ? $_REQUEST['dataStatus'] : "0";
  $strDataPaymentStatus = (isset($_REQUEST['dataPaymentStatus'])) ? $_REQUEST['dataPaymentStatus'] : "0";
  $intMax = (isset($_REQUEST['maxDetail'])) ? $_REQUEST['maxDetail'] : 0;
  // VALIDASI DATA ----------------------------------------------------------
  //format tanggal
  if (!validStandardDate($strDataPaymentDate)) {
    $strDataPaymentDate = date("Y-m-d");
  }
  // data employee
  if ($strDataEmployee == "") {
    $bolOK = false;
    $strError = $error['empty_employee_id'];
  } else {
    $strIDEmployee = getIDEmployee($db, $strDataEmployee);
    if ($strIDEmployee == "") {
      $bolOK = false;
      $strError = $error['employee_data_not_found'];
    }
  }
  for ($i = 1; $i <= $intMax; $i++) {
    if (isset($_REQUEST['detailName' . $i])) {
      // format numeric untuk nilai klaim
      if (!is_numeric($_REQUEST['detailCost' . $i])) {
        $bolOK = false;
        $strError = $error['invalid_number'];
        break;
      } // data dengan nilai klaim tanpa nama pasien
      else if ($_REQUEST['detailCost' . $i] > 0 && $_REQUEST['detailName' . $i] == "") {
        $bolOK = false;
        $strError = $error['empty_name'];
        break;
      }
    }
  }
  //------------------------------------------------------------------------------------------------------------------------
  if ($bolOK && $strIDEmployee != "") {
    // cek dulu total request, apakah ada yang melebihi atau tidak
    $arrTotal = [];
    for ($i = 1; $i <= $intMax; $i++) {
      $strType = (isset($_REQUEST['detailType' . $i])) ? $_REQUEST['detailType' . $i] : "-1";
      //$bolRoom = (isset($_REQUEST['detailRoom'.$i])) ? "t" : "f";
      $strCode = (isset($_REQUEST['detailMedicalCode' . $i])) ? $_REQUEST['detailMedicalCode' . $i] : "0";
      $strCost = (isset($_REQUEST['detailCostApproved' . $i])) ? $_REQUEST['detailCostApproved' . $i] : "0";
      $strYear = (isset($_REQUEST['detailClaimDate' . $i])) ? $_REQUEST['detailClaimDate' . $i] : "";
      $strYear = substr($strYear, 0, 4);
      //echo $strDataID."|";
      $strEmployeeCompany = getEmployeeInfoByID($db, $strIDEmployee, "id_company");
      $strEmployeeCompany = $strEmployeeCompany["id_company"];
      $arrQuota[$strYear] = getEmployeeMedicalQuota(
          $db,
          $strIDEmployee,
          $strEmployeeCompany,
          $strYear,
          $strDataID
      ); // employeeFunc.php
      if ($strType == MEDICAL_TYPE_OUTPATIENT) {
        (isset($arrTotal[$strYear][MEDICAL_TYPE_OUTPATIENT])) ? $arrTotal[$strYear][MEDICAL_TYPE_OUTPATIENT] += $strCost : $arrTotal[$strYear][MEDICAL_TYPE_OUTPATIENT] = $strCost;
      } else {
        (isset($arrTotal[$strYear][$strType][$strCode])) ? $arrTotal[$strYear][$strType][$strCode] += $strCost : $arrTotal[$strYear][$strType][$strCode] = $strCost;
      }
    }
    // lakukan cek global
    foreach ($arrQuota AS $strYear => $arrQuotaYear) {
      foreach ($arrQuotaYear AS $strType => $arrQuotaDetail) {
        if ($strType == MEDICAL_TYPE_OUTPATIENT) {
          if (isset($arrTotal[$strYear][$strType]) && $arrTotal[$strYear][$strType] > $arrQuotaDetail) {
            $strError = getWords("medical_quota_not_enough");
            $strError .= getWords(
                ". remaining quota for " . $ARRAY_MEDICAL_TREATMENT_GROUP[$strType] . " treatment in $strYear =  " . standardFormat(
                    $arrQuotaDetail,
                    true,
                    0
                )
            );
            return false;
          }
          continue;
        }
        foreach ($arrQuotaDetail AS $strCode => $fltAmount) {
          if (isset($arrTotal[$strYear][$strType][$strCode]) && $arrTotal[$strYear][$strType][$strCode] > $fltAmount) {
            $strError = getWords("medical_quota_not_enough");
            $strError .= getWords(
                ". remaining quota for " . $ARRAY_MEDICAL_TREATMENT_GROUP[$strType] . " treatment in $strYear =  " . standardFormat(
                    $fltAmount,
                    true,
                    0
                )
            );
            return false;
          }
        }
      }
    }
    // -- end ceking
    if ($strDataID == "") { // buat baru
      // cek dulu, apakah data sudah yang terakhir
      $strTmp = getLastMedicalNumber($db, $strDataMonth, $strDataYear);
      $intTmp = ($strTmp == "") ? 0 : (int)$strTmp;
      $strSQL = "INSERT INTO hrd_medical_claim_master (created,  modified_by, created_by, id_employee, ";
      $strSQL .= "method, account, ";
      $strSQL .= "payment_date, payment_status, status) ";
      $strSQL .= "VALUES(now(), '$strUpdaterID', '$strUpdaterID', '$strIDEmployee', ";
      $strSQL .= "'$strDataMethod', '$strDataAccount', '$strDataPaymentDate', ";
      $strSQL .= "'$strDataPaymentStatus', '$strDataStatus') ";
      $resExec = $db->execute($strSQL);
      // cari IDnya
      $strSQL = "SELECT id FROM hrd_medical_claim_master WHERE ";
      $strSQL .= "id_employee = '$strIDEmployee' ORDER BY created DESC";
      $resDb = $db->execute($strSQL);
      if ($rowDb = $db->fetchrow($resDb)) {
        $strDataID = $rowDb['id'];
      }
    } else {
      $strSQL = "UPDATE hrd_medical_claim_master SET modified_by ='$strUpdaterID', ";
      $strSQL .= "id_employee = '$strIDEmployee', ";
      $strSQL .= "method = '$strDataMethod', account = '$strDataAccount', ";
      $strSQL .= "payment_date = '$strDataPaymentDate', payment_status = '$strDataPaymentStatus' ";
      $strSQL .= "WHERE id = '$strDataID' ";
      $resExec = $db->execute($strSQL);
    }
    if ($strDataID != "") { // simpan detail claim yang dilakukan
      (isset($_REQUEST['maxDetail'])) ? $intMax = $_REQUEST['maxDetail'] : $intMax = 0;
      for ($i = 1; $i <= $intMax; $i++) {
        (isset($_REQUEST['detailID' . $i])) ? $strID = $_REQUEST['detailID' . $i] : $strID = "";
        $strName = "";
        $strRelation = "";
        if (isset($_REQUEST['detailName' . $i])) {
          $arrTemp = explode("|", $_REQUEST['detailName' . $i]);
          if (count($arrTemp) >= 2) {
            $strName = $arrTemp[0];
            $strRelation = $arrTemp[1];
          }
        }
        (isset($_REQUEST['detailType' . $i])) ? $strType = $_REQUEST['detailType' . $i] : $strType = "0";
        (isset($_REQUEST['detailMedicalCode' . $i])) ? $strMedicalCode = $_REQUEST['detailMedicalCode' . $i] : $strMedicalCode = "";
        if ($strType == MEDICAL_TYPE_OUTPATIENT) {
          $idType = -1;
        } else {
          $idType = (isset($arrMedGroup[$strType][$strMedicalCode])) ? $arrMedGroup[$strType][$strMedicalCode] : "null";
        }
        (isset($_REQUEST['detailDisease' . $i])) ? $strDisease = $_REQUEST['detailDisease' . $i] : $strDisease = "";
        (isset($_REQUEST['detailMedicine' . $i])) ? $strMedicine = $_REQUEST['detailMedicine' . $i] : $strMedicine = "";
        (isset($_REQUEST['detailMedicalDate' . $i])) ? $strMedicalDate = $_REQUEST['detailMedicalDate' . $i] : $strMedicalDate = date(
            "Y-m-d"
        );
        (isset($_REQUEST['detailMedicalDateThru' . $i])) ? $strMedicalDateThru = $_REQUEST['detailMedicalDateThru' . $i] : $strMedicalDateThru = date(
            "Y-m-d"
        );
        (isset($_REQUEST['detailClaimDate' . $i])) ? $strClaimDate = $_REQUEST['detailClaimDate' . $i] : $strClaimDate = date(
            "Y-m-d"
        );
        (isset($_REQUEST['detailCost' . $i])) ? $strCost = $_REQUEST['detailCost' . $i] : $strCost = "0";
        (isset($_REQUEST['detailCostApproved' . $i])) ? $strCostApproved = $_REQUEST['detailCostApproved' . $i] : $strCostApproved = "0";
        if ($strID == "") {
          if ($strName != "") { // insert new data
            $strSQL = "INSERT INTO hrd_medical_claim (created, modified_by, created_by, ";
            $strSQL .= "id_master, name, relation, medical_code, id_medical_type, disease, medicine, type, "; /*room, */
            $strSQL .= "medical_date, medical_date_thru, claim_date, cost, approved_cost) ";
            $strSQL .= "VALUES(now(), '$strUpdaterID', '$strUpdaterID', '$strDataID', ";
            $strSQL .= "'$strName', '$strRelation', '$strMedicalCode', $idType, '$strDisease','$strMedicine', '$strType', "; //'$bolRoom',
            $strSQL .= "'$strMedicalDate', '$strMedicalDateThru', '$strClaimDate', '$strCost', '$strCostApproved') ";
            $resDb = $db->execute($strSQL);
            writeLog(ACTIVITY_ADD, MODULE_PAYROLL, "$strDataID", 0);
          }
        } else {
          if ($strName == "") {
            // delete data
            $strSQL = "DELETE FROM hrd_medical_claim WHERE id = '$strID' ";
            $resDb = $db->execute($strSQL);
            writeLog(ACTIVITY_DELETE, MODULE_PAYROLL, "$strDataID", 0);
          } else {
            // update data
            $strSQL = "UPDATE hrd_medical_claim SET modified_by ='$strUpdaterID', ";
            $strSQL .= "name = '$strName', relation = '$strRelation', type = '$strType', id_medical_type = $idType, ";
            $strSQL .= "medical_code = '$strMedicalCode', disease = '$strDisease', medicine = '$strMedicine', ";
            $strSQL .= "medical_date = '$strMedicalDate', medical_date_thru = '$strMedicalDateThru', claim_date = '$strClaimDate',  ";
            $strSQL .= "cost = '$strCost', approved_cost = '$strCostApproved' ";
            $strSQL .= "WHERE id = '$strID' ";
            $resDb = $db->execute($strSQL);
            writeLog(ACTIVITY_EDIT, MODULE_PAYROLL, "$strDataID", 0);
          }
        } // if
      } // for
    } //if
    $strError = $messages['data_saved'] . " >> " . date("d-M-Y H:i:s");
    return true;
  } else {
    return false;
  }
  return true;
} // saveData
// fungsi untuk ambil nomor terakhir (dari data)
function getLastMedicalNumber($db, $strMonth, $strYear)
{
  $strResult = "";
  $strSQL = "SELECT MAX(no) AS nomor FROM hrd_medical_claim_master ";
  $strSQL .= "WHERE year_code = '$strYear' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strResult = $rowDb['nomor'] . "";
  }
  return $strResult;
}//getLastMedicalNumber
// mengambil data pembayaran kas dari data lembur ini
// dataID adalah id dari permohonan lembur
/*  function getDataCash($db, $strDataID)
  {
    global $arrDataCash;

    if ($strDataID == "") {
      return 0;
    }

    $strSQL  = "SELECT * FROM hrd_cash_request WHERE source_id = '$strDataID' AND \"type\" = 1 "; // medis tipenya 1
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      $arrDataCash['dataID'] = $rowDb['id'];
      $arrDataCash['dataAmount'] = $rowDb['amount'];
      $arrDataCash['dataDate'] = $rowDb['request_date'];
      $arrDataCash['dataNo'] = $rowDb['no'];
      $arrDataCash['dataCode'] = $rowDb['code'];
      $arrDataCash['dataMonth'] = $rowDb['month_code'];
      $arrDataCash['dataYear'] = $rowDb['year_code'];
      $arrDataCash['dataAccount'] = $rowDb['account_no'];
      $arrDataCash['dataBudget'] = $rowDb['budget_code'];
      $arrDataCash['dataNote'] = $rowDb['note'];
    }

  }//getDataCash*/
// fungsi untuk menyimpan data kas dari lembur ini
/*
function saveDataCash($db)
{
  global $_SESSION;
  global $_REQUEST;
  global $intFormNumberDigit;
  $strUpdaterID = $_SESSION['sessionUserID'];

  $bolResult = true;

  $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
  if ($strDataID == "") return false;
  // cari data lembur dulu
  $strSQL  = "SELECT * FROM hrd_medical_claim_master WHERE id = '$strDataID' ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    $strDataEmployee = $rowDb['id_employee'];
  } else {
    return false;
  }

  $strDataNo = (isset($_REQUEST['dataCashNo'])) ? $_REQUEST['dataCashNo'] : "";
  $strDataCode = (isset($_REQUEST['dataCashCode'])) ? $_REQUEST['dataCashCode'] : "";
  $strDataMonth = (isset($_REQUEST['dataCashMonth'])) ? $_REQUEST['dataCashMonth'] : "";
  $strDataYear = (isset($_REQUEST['dataCashYear'])) ? $_REQUEST['dataCashYear'] : "";
  $strDataAccount = (isset($_REQUEST['dataAccount'])) ? $_REQUEST['dataAccount'] : "";
  $strDataBudget = (isset($_REQUEST['dataBudget'])) ? $_REQUEST['dataBudget'] : "";
  $strDataNote = (isset($_REQUEST['dataCashNote'])) ? $_REQUEST['dataCashNote'] : "";
  $strDataAmount = (isset($_REQUEST['dataAmount'])) ? $_REQUEST['dataAmount'] : 0;
  $strDataDate = (isset($_REQUEST['dataCashDate'])) ? $_REQUEST['dataCashDate'] : date("Y-m-d");

  // validasi
  if (!validStandardDate($strDataDate)) $strDataDate = date("Y-m-d");
  if (!is_numeric($strDataAmount)) $strDataAmount = 0;

  $strSQL  = "SELECT * FROM hrd_cash_request WHERE source_id = '$strDataID' AND \"type\" = 1 "; // medis tipenya 1
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    //update
    $strSQL  = "UPDATE hrd_cash_request SET modified_by ='$strUpdaterID', ";
    $strSQL .= "request_date = '$strDataDate', amount = '$strDataAmount', ";
    $strSQL .= "account_no = '$strDataAccount', budget_code = '$strDataBudget', ";
    $strSQL .= "note = '$strDataNote' ";
    $strSQL .= "WHERE source_id = '$strDataID' AND \"type\" = 1 ";
    $resExec = $db->execute($strSQL);
  } else {

    // cek ulang nomornya
    $strTmp = getLastFormNumber($db, "hrd_cash_request", "no", $strDataMonth, $strDataYear);
    $intTmp = ($strTmp == "") ? 0 : (int)$strTmp;
    $intKode = (int)$strDataNo;
    if ($intKode <= $intTmp) { // sudah ada yang lebih besar atau sama, ganti
      $intKode = $intTmp + 1;
      $strDataNo = addPrevZero($intKode,$intFormNumberDigit);
    }

    // insert
    $strSQL  = "INSERT INTO hrd_cash_request (created,  modified_by, created_by, no, code, ";
    $strSQL .= "month_code, year_code, account_no, budget_code, amount, note, ";
    $strSQL .= "request_date, \"type\", source_id, status) ";
    $strSQL .= "VALUES(now(), '$strUpdaterID', '$strUpdaterID', '$strDataNo', '$strDataCode', ";
    $strSQL .= "'$strDataMonth', '$strDataYear', '$strDataAccount', '$strDataBudget', ";
    $strSQL .= "'$strDataAmount', '$strDataNote', '$strDataDate', 1, '$strDataID', 3) "; // langsung approve :D
    $resExec = $db->execute($strSQL);
  }

  return $bolResult;
}

// fungsi untuk menghapus atau membatalkan pengajuan kas
function deleteDataCash($db, $strDataID)
{
  if ($strDataID != "") {
    $strSQL  = "DELETE FROM hrd_cash_request WHERE type = 1 AND source_id = '$strDataID' ";
    $resExec = $db->execute($strSQL);
  }
}//delete DataCash*/
// mengambil daftar tipe medis
function getMedicalType($db)
{
  global $strMedicalTypeArray;
  $strResult = "";
  $arrType = [];
  $strResult .= "<table border=0 width=100%>\n";
  $strResult .= " <tr valign=top>\n";
  // ambil dulu total data
  $intTotal = 0;
  $strSQL = "SELECT COUNT(id) AS total FROM hrd_medical_type ";
  $resDb = $db->execute($strSQL);
  if ($rowDb = $db->fetchrow($resDb)) {
    if (is_numeric($rowDb['total'])) {
      $intTotal = $rowDb['total'];
    }
  }
  // akan dibagi dalam 4 kolom, jadi cek jatah masing-masing kolom
  $intBaris = ceil($intTotal / 4);
  $i = 0;
  $strResult .= " <td>\n";
  $strSQL = "SELECT * FROM hrd_medical_type ORDER BY type, code  ";
  $resDb = $db->execute($strSQL);
  while ($rowDb = $db->fetchrow($resDb)) {
    $i++;
    $strResult .= $rowDb['code'] . " . " . $rowDb['note'] . "<br>\n";
    if ($i > $intBaris) {
      $strResult .= "</td>\n<td>";
      $i = 0;
    }
    $arrType[$rowDb['type']][] = [$rowDb['code'], $rowDb['note']];
  }
  $intLength = count($arrType) + 1;
  $strResult .= " </td>\n";
  $strResult .= " </tr>\n";
  $strResult .= "</table>\n";
  // generate array medical type untuk di javascript
  $strMedicalTypeArray = "var arrType = new Array($intLength);\n";
  for ($i = 1; $i < $intLength; $i++) {
    $strTypeList = "";
    foreach ($arrType[$i] AS $idx => $arrTmp) {
      $strTypeList .= "['$arrTmp[0]','$arrTmp[1]'],";
    }
    $strMedicalTypeArray .= "arrType[$i] = [$strTypeList];\n";
  }
  return $strResult;
}//getMedicalType
//----------------------------------------------------------------------
//----MAIN PROGRAM -----------------------------------------------------
$db = new CdbClass;
if ($db->connect()) {
  getUserEmployeeInfo();
  $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
  if (isset($_REQUEST['dataEmployee'])) {
    $arrData['dataEmployee'] = $_REQUEST['dataEmployee'];
  }
  scopeCBDataEntry($arrData['dataEmployee'], $_SESSION['sessionUserRole'], $arrUserInfo);
  if (isset($arrData['dataEmployee']) && $arrData['dataEmployee'] != "") {
    $arrTemp = getEmployeeInfoByCode($db, $arrData['dataEmployee'], "employee_name");
    $arrData['dataEmployeeName'] = $arrTemp['employee_name'];
  }
  // baca dulu default buat nomor baru
  $dtNow = getdate(); // default berdasar hari ini
  $arrData['dataMonth'] = getRomans($dtNow['mon']);
  $arrData['dataYear'] = $dtNow['year'];
  $strInputLastNo = getLastMedicalNumber($db, $arrData['dataMonth'], $arrData['dataYear']);
  $intLastNo = ($strInputLastNo == "") ? 0 : (int)$strInputLastNo;
  $arrData['dataNo'] = "";//addPrevZero($intLastNo + 1,$intFormNumberDigit);
  if ($bolCanEdit) {
    if (isset($_POST['btnSave'])) {
      $bolSave = true;
      if (!saveData($db, $strError)) {
        $bolError = true;
      }
      if ($strError != "") {
        $strMessages = $strError;
        $strMsgClass = ($bolError) ? "class=bgError" : "class=bgOK";
      }
    }
  }
  if ($bolCanView) {
    if (isset($arrData['dataEmployee'])) {
      $strDataDetail = ($bolPrint) ? getDataPrint($db, $intTotalData, $strDataID) : getData(
          $db,
          $intTotalData,
          $strDataID
      );
    }
    //$strInputLastNo = getLastMedicalNumber($db);
  } else {
    showError("view_denied");
    $strDataDetail = "";
  }
  if ($bolPrint) {
    $strNomor = "";//$arrData['dataNo'] ."/". $arrData['dataCode'] ."/". $arrData['dataMonth'] ."/". $arrData['dataYear'];
    $strDataNo = $strNomor;
    $strDataEmployee = "" . $arrData['dataEmployee'];
    $strDataEmployeeName = "" . $arrData['dataEmployeeName'];
    $strDataSection = "" . $arrData['dataSection'];
    $strDataDate = pgDateFormat($arrData['dataDate'], "d M Y");
    $strTotalCost = standardFormat($arrData['dataTotalCost']);
    $strTotalApproved = standardFormat($arrData['dataTotalApproved']);
    $strDataTerbilang = Terbilang($arrData['dataTotalApproved']);
  } else {
    /*$strInputNo  = "<input type=text name=dataCode id=dataCode size=3 maxlength=10 value=\"" .$arrData['dataCode']. "\" style=\"width:40\" readonly>";
    $strInputNo .= "-<input type=text name=dataNo id=dataNo size=$intFormNumberDigit maxlength=10 value=\"" .$arrData['dataNo']. "\" style=\"width:50\" $strReadonly>";
    $strInputNo .= "/<input type=text name=dataMonth id=dataMonth size=5 maxlength=5 value=\"" .$arrData['dataMonth']. "\" style=\"width:50\" readonly>";
    $strInputNo .= "/<input type=text name=dataYear id=dataYear size=4 maxlength=4 value=\"" .$arrData['dataYear']. "\" style=\"width:40\" readonly>";*/
    //$strInputPaymentDate = "<input type=text size=15 maxlength=10 name=dataPaymentDate id=dataPaymentDate value=\"" .$arrData['dataPayment']. "\" $strReadonly class='date-empty'>";
    $strInputEmployee = "<input type=text name=dataEmployee id=dataEmployee size=15 maxlength=30 value=\"" . $arrData['dataEmployee'] . "\" style=\"width:$strDefaultWidthPx\" $strNonCbReadonly class='string'>";
    $strInputAccount = "<input type=text name=dataAccount id=dataAccount size=30 maxlength=30 value=\"" . $arrData['dataAccount'] . "\" style=\"width:$strDefaultWidthPx\">";
    $strInputMethod = getPaymentMethodList(
        "dataMethod",
        $arrData['dataMethod'],
        "",
        "style=\"width:$strDefaultWidthPx\""
    );
    // status pembayaran
    if ($bolIsEmployee) {
      $strInputPaymentStatus = ($arrData['dataPaymentStatus'] == 1) ? $words['paid'] : $words['unpaid'];
      $strInputStatus = $words[$ARRAY_REQUEST_STATUS[$arrData['dataStatus']]];
    } else {
      $strInputPaymentStatus = "<select name=dataStatus style=\"width:$strDefaultWidthPx\">";
      if ($arrData['dataPaymentStatus'] == 0) {
        $strInputPaymentStatus .= "  <option value=0 selected>" . $words['unpaid'] . "</option>";
        $strInputPaymentStatus .= "  <option value=1>" . $words['paid'] . "</option>";
      } else {
        $strInputPaymentStatus .= "  <option value=0>" . $words['unpaid'] . "</option>";
        $strInputPaymentStatus .= "  <option value=1 selected>" . $words['paid'] . "</option>";
      }
      $strInputPaymentStatus .= "</select>\n";
      $strInputStatus = $words[$ARRAY_REQUEST_STATUS[$arrData['dataStatus']]];
    }
    // tampilan data permohonan kas
    $strReadonly = "readonly";
    if ($strDataID != "") { // ada data
      if ($_SESSION['sessionUserRole'] == ROLE_ADMIN || $_SESSION['sessionUserRole'] == ROLE_SUPERVISOR) { // admin atau manager
        // cek apakah adapermintaan simpan data
        if (isset($_REQUEST['btnSaveCash']) && $bolCanEdit) {
          $bolResult = saveDataCash($db);
        } else if (isset($_REQUEST['btnCancelCash']) && $bolCanEdit) {
          deleteDataCash($db, $strDataID);
        }
        // cek status, apakah mungkin untuk membuka permohonan kas, jika ya, ambil data
        if ($arrData['dataStatus'] == 3) {
          $strDisabledCash = "";
          // ambil data
          //getDataCash($db, $strDataID);
          if ($arrDataCash['dataID'] != "") {
            $strCashStyle = "";
          }
          $strReadonly = "";
        }
      }
    }
    $arrDataCash['dataMonth'] = getRomans($dtNow['mon']);
    $arrDataCash['dataYear'] = $dtNow['year'];
    $strInputLastNo = "1";//getLastFormNumber($db, "hrd_cash_request", "no", $arrDataCash['dataMonth'], $arrDataCash['dataYear']);
    $intLastNo = ($strInputLastNo == "") ? 0 : (int)$strInputLastNo;
    //$arrDataCash['dataNo'] = addPrevZero($intLastNo + 1,$intFormNumberDigit);
    //$strCashNo  = "<input type=text name=dataCashNo size=$intFormNumberDigit maxlength=10 value=\"" .$arrDataCash['dataNo']. "\" style=\"width:50\" readonly>";
    $strCashNo = "/<input type=text name=dataCashCode size=3 maxlength=10 value=\"" . $arrDataCash['dataCode'] . "\" style=\"width:60\" readonly>";
    $strCashNo .= "/<input type=text name=dataCashMonth size=5 maxlength=5 value=\"" . $arrDataCash['dataMonth'] . "\" style=\"width:20\" readonly>";
    $strCashNo .= "/<input type=text name=dataCashYear size=4 maxlength=4 value=\"" . $arrDataCash['dataYear'] . "\" style=\"width:40\" readonly>";
    $strCashNote = "<textarea name=dataCashNote cols=30 rows=3 wrap='virtual' style=\"width:$strDefaultWidthPx\" $strReadonly>" . $arrDataCash['dataNote'] . "</textarea>";
    $strCashAccount = "<input type=text name=dataAccount size=30 maxlength=50 value=\"" . $arrDataCash['dataAccount'] . "\" style=\"width:$strDefaultWidthPx\"  $strReadonly>";
    $strCashBudget = "<input type=text name=dataBudget size=30 maxlength=50 value=\"" . $arrDataCash['dataBudget'] . "\" style=\"width:$strDefaultWidthPx\"  $strReadonly>";
    $strCashAmount = "<input type=text name=dataAmount size=30 maxlength=20 value=\"" . $arrDataCash['dataAmount'] . "\" style=\"width:$strDefaultWidthPx\"  class=numeric $strReadonly>";
    $strCashDate = "<input type=text size=15 maxlength=10 name=dataCashDate id=dataCashDate value=\"" . $arrDataCash['dataDate'] . "\">";
  }
  $strMedicalCodeList = getMedicalType($db);
  // tampilkan button
  $strDisablePrint = ($strDataID != "") ? "" : "disabled";
  $strButtons = "";
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
if (trim($dataPrivilege['icon_file']) == "") {
  $pageIcon = "../images/icons/blank.gif";
} else {
  $pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
}
if ($bolPrint) {
  $strMainTemplate = getTemplate(str_replace(".php", "_print.html", basename($_SERVER['PHP_SELF'])));
} else {
  $strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
}
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
?>