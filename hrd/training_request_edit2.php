<?php
include_once('../global/session.php');
include_once('global.php');
include_once('../global/common_data.php');
include_once('../global/employee_function.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../includes/form2/form2.php');
include_once('../classes/hrd/hrd_training_category.php');
include_once('../classes/hrd/hrd_training_type.php');
include_once('../classes/hrd/hrd_training_request.php');
include_once('../classes/hrd/hrd_training_request_participant.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(accessDenied($_SERVER['HTTP_REFERER']));
}
$db = new CdbClass;
if ($db->connect()) {
  $strWordsTrainingRequestEdit = getWords("training request edit");
  $strWordsTrainingRequestList = getWords("training request list");
  $strWordsConductedTraining = getWords("conducted training");
  getUserEmployeeInfo();
  //INISIALISASI------------------------------------------------------------------------------------------------------------------
  //ambil semua training category
  $tblTrainingCategory = new cHrdTrainingCategory();
  $dataTrainingCategory = $tblTrainingCategory->findAll("", "id, training_category", "", null, 1, "id");
  $tblTrainingType = new cHrdTrainingType();
  //ambil semua training type berdasarkan id category dan id type
  // $tblTrainingCategoryType = new cHrdTrainingType();
  //$dataTrainingCategoryType = $tblTrainingCategoryType->findAll("", "id, id_category, code, name", "code", null, 1, "id_category");
  //ambil setting cost untuk trip sesuai dengan trip type yang dipilih
  //$tblTripTypeCostSetting = new cHrdTripTypeCostSetting();
  foreach ($dataTrainingCategory AS $strIDCategory => $arrTrainingCategory) {
    $dataTrainingType = $tblTrainingType->findAll(
        "id_category = '" . $strIDCategory . "'",
        "id, code, name",
        "",
        null,
        1,
        "id"
    );
    $dataTrainingCategoryType[$strIDCategory][] = $dataTrainingType;
  }
  //$tblTripCostPlatform = new cHrdTripCostPlatform();
  $strDataID = getRequestValue('dataID');
  $isNew = ($strDataID == "");
  if ($strDataID != "") {
    $arrData = getDataByID($strDataID);
    $arrTrainingType = $tblTrainingType->findAll(
        "category = '" . $arrData['dataTrainingCategory'] . "'",
        "",
        "",
        null,
        1,
        "id"
    );
  } else {
    $arrData = getDataByID("");
  }
  /*
  else
  {
    $arrData['dataEmployee']  = getPostValue('dataEmployee');
    $arrData['dataTrainingCategory']  = getPostValue('dataTrainingCategory');
    $arrData['dataTrainingTypeCode']  = getPostValue('dataTrainingTypeCode');
    $arrData['dataTrainingTypeName']  = getPostValue('dataTrainingTypeName');
    $arrData['dataDateFrom']  = (getPostValue('dataDateFrom') != "") ? getPostValue('dataDateFrom') : date("Y-m-d");
    $arrData['dataDateThru']  = (getPostValue('dataDateThru') != "") ? getPostValue('dataDateThru') : date("Y-m-d");
    $arrData['dataTopic']     = getPostValue('dataTopic');
    $arrData['dataPurpose']   = getPostValue('dataPurpose');
    $arrData['dataExpectedResult']   = getPostValue('dataExpectedResult');
    $arrData['dataInstitution']   = getPostValue('dataInstitution');
    $arrData['dataTrainer']   = getPostValue('dataTrainer');
    $arrData['dataLocation']   = getPostValue('dataLocation');
    $arrData['dataCost']   = getPostValue('dataCost');
    $arrData['dataNote']      = getPostValue('dataNote');
  }*/
  $strDataEmployee = (isset($arrData['employee_id'])) ? $arrData['employee_id'] : "";
  scopeGeneralDataEntry($strDataEmployee, $_SESSION['sessionUserRole'], $arrUserInfo);
  // ------------------------------------------------------------------------------------------------------------------------------
  if ($bolCanEdit) {
    $f = new clsForm("formInput", 2, "100%", "");
    $f->caption = strtoupper($strWordsINPUTDATA);
    $f->addHidden("dataID", $strDataID);
    $f->addFieldset("General");
    $f->addInputAutoComplete(
        getWords("employee ID"),
        "dataEmployee",
        getDataEmployee($strDataEmployee),
        "style='width:350px' " . $strEmpReadonly,
        "string",
        true
    );
    $f->addLabelAutoComplete("", "dataEmployee", "");
    $f->addLiteral("", "", "");
    $f->addSelect(
        getWords("training category"),
        "dataTrainingCategory",
        getDataListTrainingCategory($arrData['training_category']),
        [
            "onChange" => "switchTrainingType(this.value, '" . $arrData['id_training_type'] . "');",
            "style"    => "width:350px"
        ],
        "",
        true,
        true,
        true,
        "",
        "",
        true,
        null
    );
    foreach ($dataTrainingCategory as $strIDCategory => $arrCategoryDetail) {
      $bolEnable = (getPostValue("dataTempTrainingType_" . $strIDCategory) != "") ? true : false;
      $f->addSelect(
          "",
          "dataTempTrainingType_" . $strIDCategory,
          getDataListTrainingType(
              $arrData['id_training_type'],
              false,
              "",
              "id_category = '" . $strIDCategory . "'"
          ),
          ["class" => "classTraining", "onChange" => "document.formInput.submit()", "style" => "width:350px"],
          "",
          false,
          $bolEnable,
          true
      );
    }
    $f->addHidden("dataTrainingType", getPostValue("dataTempTrainingType_" . getPostValue("dataTrainingCategory")));
    $f->addInput(
        getWords("date from"),
        "dataDateFrom",
        $arrData['date_from'],
        ["style" => "width:$strDateWidth"],
        "date",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("date thru"),
        "dataDateThru",
        $arrData['date_thru'],
        ["style" => "width:$strDateWidth"],
        "date",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("topic"),
        "dataTrainingTopic",
        $arrData['topic'],
        ["size" => 79, "maxlength" => 255],
        "string",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("purpose"),
        "dataPurpose",
        $arrData['purpose'],
        ["size" => 79, "maxlength" => 255],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("expected result"),
        "dataExpectedResult",
        $arrData['expected_result'],
        ["size" => 79, "maxlength" => 255],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("place"),
        "dataPlace",
        $arrData['place'],
        ["size" => 79, "maxlength" => 255],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("institution"),
        "dataInstitution",
        $arrData['institution'],
        ["size" => 79, "maxlength" => 255],
        "string",
        false,
        true,
        true
    );
    $f->addInput(
        getWords("trainer/instructor"),
        "dataTrainer",
        $arrData['trainer'],
        ["size" => 79, "maxlength" => 255],
        "string",
        true,
        true,
        true
    );
    $f->addInput(
        getWords("cost"),
        "dataCost",
        $arrData['cost'],
        ["size" => 79, "maxlength" => 255],
        "integer",
        false,
        true,
        true
    );
    $f->addTextArea(
        getWords("note"),
        "dataNote",
        $arrData['note'],
        ["cols" => 76, "rows" => 2],
        "string",
        false,
        true,
        true
    );
    $f->addLiteral("", "", "");
    if (getPostValue("dataTrainingCategory") != "") {
      $strDataIDCategory = getPostValue("dataTrainingCategory");
      $strDataIDType = getPostValue("dataTempTrainingType_" . $strDataIDCategory);
      $f->addFieldset("Participant", "");
      //get employee who need the selected training
      $strSQL = "SELECT id_category, id_type, id_employee as id, employee_id , employee_name, '' as note FROM hrd_training_need_analysis_detail_by_type AS t1 LEFT JOIN hrd_training_need_analysis_master AS t2 ON t1.id_master = t2.id WHERE id_category = '$strDataIDCategory' AND id_type = '$strDataIDType'";
      $resDb = $db->execute($strSQL);
      while ($rowDb = $db->fetchrow($resDb)) {
        $arrParticipant[$rowDb['id']] = $rowDb;
      }
      $f->addLiteral("", "", getTrainingParticipant($arrParticipant));
      /*

              //tambah baris blank supaya semua field allowance terletak di kolom kanan
              if (count($arrTrainingType[$strDataTripTypeID]) > 9)
              {
                for($i = 9; $i <= count($arrTripCost[$strDataTripTypeID]); $i++)
                  $f->addLiteral("", "", "");
              }

              foreach($arrTripCost[$strDataTripTypeID] AS $strCostID)
              {

                $fltQuota = (isset($arrQuota[$strCostID])) ? $intDuration * $arrQuota[$strCostID]['amount'] : 0;

                $fltAmount = (isset($arrTripCostType[$strCostID]['amount'])) ? $arrTripCostType[$strCostID]['amount'] : $fltQuota;
                //generate button untuk alat bantu mengubah nilai allowance menjadi 0, 50%, 75%, dan nilai default.
                $strBtnHelper = "
                <input type=\"button\" name=\"btn".$strCostID."_0\" value=\"0%\"
                onClick=\"(document.formInput.dataTripCost_".$strCostID.".value = 0)\">&nbsp;
                <input type=\"button\" name=\"btn".$strCostID."_50\" value=\"50%\"
                onClick=\"\$(dataTripCost_".$strCostID.").value = (\$(dataTripCost_".$strCostID.").value /2);\">&nbsp;
                <input type=\"button\" name=\"btn".$strCostID."_75\" value=\"75%\"
                onClick=\"\$(dataTripCost_".$strCostID.").value = (\$(dataTripCost_".$strCostID.").value / 4 * 3);\">&nbsp;
                <input type=\"button\" name=\"btn".$strCostID."_Reset\" value=\"".getWords("reset")."\"
                onClick=\"\$(dataTripCost_".$strCostID.").value = (\$(dataTripCostDefault_".$strCostID.").value);\">&nbsp;";
                //generate field allowance untuk jenis trip yang dipilih
                $f->addInput(getWords($arrTripCostType[$strCostID]['trip_cost_type_name'])." (".$arrTripCostType[$strCostID]['currency'].")", "dataTripCost_".$strCostID, $fltAmount , array("size" => 30, "maxlength" => 12), "numeric", true, true, true, "", $strBtnHelper);
                $f->addHidden("dataTripCostDefault_".$strCostID, $fltQuota);

              }
              if (count($arrTripCost[$strDataTripTypeID]) < 10)
              {
                //tambah baris blank supaya semua field allowance terletak di kolom kanan
                for($i = 1; $i <= (10 - count($arrTripCost[$strDataTripTypeID])) ; $i++)
                  $f->addLiteral("", "", "");
              }
              $f->addButton("btnAdd", getWords("add new"), array("onClick" => "\"location.href=".basename($_SERVER['PHP_SELF']."\"")));
            }
            else
            {
              $f->addLiteral("", "buttonAllowance", generateSubmit("btnAllowance", getWords("set allowance"), "", ""));
              //tambah baris blank supaya semua field allowance terletak di kolom kanan
              for($i = 1; $i <= 11; $i++)
                $f->addLiteral("", "", "");
            }*/
    }
    $f->addSubmit(
        "btnSave",
        getWords("save"),
        ["onClick" => "javascript:myClient.confirmSave();"],
        true,
        true,
        "",
        "",
        "saveData()"
    );
    $formInput = $f->render();
  } else {
    $formInput = "";
  }
}
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = $dataPrivilege['menu_name'];
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate($strMainTemplate);
$tbsPage->Show();
//--------------------------------------------------------------------------------
function getDataByID($strDataID)
{
  global $db;
  $tblTrainingRequest = new cHrdTrainingRequest();
  if ($strDataID != "") {
    $dataTrainingRequest = $tblTrainingRequest->findAll("id = $strDataID", "", "", null, 1, "id");
    $arrTemp = getEmployeeInfoByID($db, $dataTrainingRequest[$strDataID]['id_employee'], "employee_id");
    $dataTrainingRequest[$strDataID]['employee_id'] = $arrTemp['employee_id'];
    foreach ($dataTrainingRequest[$strDataID] as $strKey => $strValue) {
      $arrResult[$strKey] = $strValue;
    }
  } else {
    $dataTrainingRequest = $tblTrainingRequest->find("", null, "");
    $dataTrainingRequest[$strDataID]['employee_id'] = "";
    foreach ($dataTrainingRequest as $strKey => $strValue) {
      $arrResult[$strKey] = "";
    }
  }
  return $arrResult;
}

function getTrainingParticipant($arrParticipant)
{
  global $words;
  global $strTargetElements;
  global $_REQUEST;
  global $strDataID;
  $intMaxShow = 4; // tambahan yang perlu dimunculkan
  $intAdd = 10; // maksimum tambahan
  $intRows = 0;
  $intShown = 0;
  $strResult = "";
  $arrStatus = ["ok", "cancel"];
  $strResult .= " <table border=0 class=gridTable cellpadding=0 cellspacing=0 width=100%>\n";
  $strResult .= "  <tr valign=top class=tableHeader>\n";
  $strResult .= "  <td nowrap>&nbsp;</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('no') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('employee id') . "</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('name') . "</td>\n";
  //$strResult .= "  <td nowrap>&nbsp;" .getWords('status')."</td>\n";
  $strResult .= "  <td nowrap>&nbsp;" . getWords('note') . "</td>\n";
  $strResult .= "  </tr>\n";
  //$arrParticipant = array();
  if ($strDataID != "") {
    $strSQL = "SELECT t1.id, t1.note, t1.status, t2.employee_id, t2.employee_name FROM hrd_training_request_participant AS t1 ";
    $strSQL .= "LEFT JOIN hrd_employee AS t2 ON t2.id = t1.id_employee ";
    $strSQL .= "WHERE t1.id_request = '$strDataID' ";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrParticipant[$rowDb['id']] = $rowDb;
    }
    writeLog(ACTIVITY_VIEW, MODULE_PAYROLL, "", 0);
  }
  foreach ($arrParticipant AS $id => $rowDb) {
    $intRows++;
    $intShown++;
    $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
    $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center><input type=checkbox name='chkParticipant_$intRows' value=\"" . $rowDb['id'] . "\" $strAction checked></td>\n";
    $strResult .= "  <td align=right nowrap><input type=hidden name=dataParticipantID$intRows value=\"" . $rowDb['id'] . "\">$intRows&nbsp;</td>";
    $strAction = "onFocus = \"AC_kode = 'detailEmployeeID$intRows';AC_nama='detailName$intRows';\" ";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailEmployeeID$intRows id=detailEmployeeID$intRows value=\"" . $rowDb['employee_id'] . "\" $strAction></td>";
    $strResult .= "  <td nowrap>&nbsp;<strong id=\"detailName$intRows\">" . $rowDb['employee_name'] . "</strong></td>";
    //$strResult .= "  <td>" .getComboFromArray($arrStatus, "detailStatus$intRows", $rowDb['status'])."</td>";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=dataParticipantNote_$intRows id=dataParticipantNote_$intRows value=\"" . $rowDb['note'] . "\"></td>";
    $strResult .= "</tr>\n";
    $strTargetElements .= ",\"detailEmployeeID$intRows\"";
  }
  // tambahkan dengan data kosong
  for ($i = 1; $i <= $intAdd; $i++) {
    $intRows++;
    if ($intRows <= $intMaxShow) {
      $strResult .= "<tr valign=top  id=\"detailRows$intRows\">\n";
      $intShown++;
      $strDisabled = "";
    } else {
      $strResult .= "<tr valign=top  id=\"detailRows$intRows\" style=\"display:none\">\n";
      $strDisabled = "disabled";
    }
    $strAction = " onChange = \"chkDeleteChanged($intRows);\" ";
    $strResult .= "  <td align=center><input type=checkbox name='chkParticipant$intRows' $strAction></td>\n";
    $strResult .= "  <td align=right nowrap>$intRows&nbsp;</td>";
    $strAction = "onFocus = \"AC_kode = 'detailEmployeeID$intRows';AC_nama='detailName$intRows';\" ";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=detailEmployeeID$intRows id=detailEmployeeID$intRows $strDisabled $strAction></td>";
    $strResult .= "  <td nowrap>&nbsp;<strong id=\"detailName$intRows\"></strong></td>";
    //$strResult .= "  <td>" .getComboFromArray($arrStatus, "detailStatus$intRows")."</td>";
    $strResult .= "  <td><input type=text size=20 maxlength=50 name=dataParticipantNote_$intRows id=dataParticipantNote_$intRows></td>";
    $strResult .= "</tr>\n";
    $strTargetElements .= ",\"detailEmployeeID$intRows\"";
  }
  $strResult .= " <tr valign=top><td>&nbsp;</td>\n";
  $strResult .= "  <td colspan=3>&nbsp;<a href=\"javascript:showMoreInput();\">" . $words['more'] . "</a></td></tr>\n";
  $strResult .= "  </table>\n";
  // tambahkan hidden data
  $strResult .= "<input type=hidden name=maxDetail value=$intRows>";
  $strResult .= "<input type=hidden name=numShow value=$intShown>";
  return $strResult;
} // getTrainingParticipant
// fungsi untuk menyimpan data
function saveData()
{
  global $db;
  global $f;
  global $_POST;
  global $isNew;
  global $strDataID;
  //global $arrCategoryScore;
  // simpan data master
  if ($db->connect()) {
    $strmodified_byID = $_SESSION['sessionUserID'];
    $tblTrainingRequest = new cHrdTrainingRequest();
    $data = [
        "id_employee" => getPostValue('dataIDEmployee'),
        "date_from" => getPostValue('dataTopic'),
        "date_thru" => getPostValue('dataTopic'),
        //"id_training_category" => getPostValue('dataIDTrainingCategory'),
        "training_category" => getPostValue('dataTrainingCategory'),
        "training_type" => getPostValue('dataTrainingType'),
        //"id_type" => getPostValue('dataIDTrainingType'),
        //"training_type_code" => getPostValue('dataTrainingTypeCode'),
        //"training_type_name" => getPostValue('dataTrainingTypeName'),
        "topic" => getPostValue('dataTopic'),
        "purpose" => getPostValue('dataPurpose'),
        "expected_result" => getPostValue('dataExpectedResult'),
        "institution" => getPostValue('dataInstitution'),
        "trainer" => getPostValue('dataTrainer'),
        "place" => getPostValue('dataPlace'),
        "cost" => getPostValue('dataCost'),
        "job_description" => getPostValue('dataJobDescription')
    ];
    $bolSuccess = false;
    if ($isNew) {
      // data baru
      $bolSuccess = $tblTrainingRequest->insert($data);
    } else {
      $bolSuccess = $tblTrainingRequest->update("id='" . getPostValue('dataID') . "'", $data);
    }
    if ($bolSuccess) {
      if ($isNew) {
        $strDataID = $tblTrainingRequest->getLastInsertId();
      } else {
        $strDataID = getPostValue('dataID');
      }
    }
    // simpan detail
    // hapus data lama, insert data baru
    $tblTrainingRequestParticipant = new cHrdTrainingRequestParticipant();
    $tblTrainingRequestParticipant->delete("id_request = " . $strDataID);
    foreach ($_POST as $strName => $strValue) {
      if (substr($strName, 0, 15) == "chkParticipant_") {
        $strIndex = substr($strName, 16);
        $data2 = [];
        $data2['id_request'] = $strDataID;
        $data2['id_employee'] = $strValue;
        $data2['note'] = getPostValue("dataParticipantNote_" . $strIndex);
        $tblTrainingRequestParticipant->insert($data2);
      }
    }
  }
  $f->message = $tblTrainingRequest->strMessage;
} // saveData
?>