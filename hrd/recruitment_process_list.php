<?php
include_once('../global/session.php');
include_once('global.php');
//include_once('../includes/datagrid/datagrid.php');
include_once('../classes/datagrid_modified.php');
include_once('../includes/form2/form2.php');
include_once('../global/common_data.php');
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  // cek apakah print out, dan bisa akses mrf -- khusus ka dept
  if (isset($_REQUEST['btnPrint'])) {
    $dataPriv = getDataPrivileges("recruitment_edit.php", $bolView, $bolEdit, $bolDel, $bolApp);
    if ($bolView) {
      $bolCanView = true;
    } else {
      die(getWords('view denied'));
    }
  } else {
    die(getWords('view denied'));
  }
}
$strWordsRecruitmentProcessList = getWords("recruitment process list");
$strWordsRecruitmentProcessReport = getWords("recruitment process report");
$strWordsLegend = getWords("legend");
$strWordsAccepted = getWords("accepted");
$strWordsConsidered = getWords("considered");

// kelas yang dimodifikasi
class clsFormMod extends clsForm
{

  //this function will get last $$_REQUEST and action that call before
  function _getRequest()
  {
    if (isset($this->requestProcessed)) {
      return false;
    }
    $this->requestProcessed = true;
    if (count($this->objects) > 0) {
      $arrObject = $this->objects;
      while (list($k, $v) = each($arrObject)) //foreach($this->objects as &$obj)
      {
        $obj = &$this->objects[$k];
        if ($obj['type'] == 'submit') {
          continue;
        }
        switch ($obj['type']) {
          case 'select':
            if (isset($_REQUEST[$obj['name']])) {
              $obj['value'] = $_REQUEST[$obj['name']];
              $arrValues = $obj['values'];
              while (list($key2, $value) = each($arrValues)) {
                $val = &$obj['values'][$key2];
                if ($val['value'] == $_REQUEST[$obj['name']]) {
                  $val['selected'] = true;
                  $obj['text'] = $val['text'];
                  break;
                } else {
                  $val['selected'] = false;
                }
              }
            }
            break;
          case 'radio':
            if (isset($_REQUEST[$obj['name']])) {
              $obj['value'] = $_REQUEST[$obj['name']];
              $arrValues = $obj['values'];
              while (list($key2, $value) = each($arrValues)) {
                $val = &$obj['values'][$key2];
                if ($val['value'] == $_REQUEST[$obj['name']]) {
                  $val['checked'] = true;
                  $obj['text'] = $val['text'];
                  break;
                } else {
                  $val['checked'] = false;
                }
              }
            }
            break;
          case 'checkbox':
            if (isset($_REQUEST[$obj['name']])) {
              $obj['value'] = true;
            } else {
              if (isset($_REQUEST["hidden_" . $obj['name']])) {
                $obj['value'] = false;
              }
            }
            break;
          case 'hidden' :
            if (isset($_REQUEST[$obj['name']])) {
              $obj['value'] = $_REQUEST[$obj['name']];
            }
            break;
          case 'file' :
            //jika ada post untuk input type=file, maka...
            if (isset($_FILES[$obj['name']])) {
              $this->_uploadFile($obj['name'], $obj['targetFolder']);
            }
            break;
          case 'labelautocomplete' :
          case 'label' :
          case 'literal' :
            // NO POST VALUE
            break;
          default:
            if (isset($_REQUEST[$obj['name']])) {
              $obj['value'] = $_REQUEST[$obj['name']];
            }
            break;
        };
      }
      //submit to server must be last priority of object to execute
      $arrObject = $this->objects;
      while (list($k, $v) = each($arrObject)) //foreach($this->objects as &$obj)
      {
        $obj = &$this->objects[$k];
        if ($obj['type'] == 'submit') {
          if (isset($_REQUEST[$obj['name']])) {
            //this button have been submit to server
            $obj['clicked'] = true;
            $funcName = $obj['serverAction'];
            if ($funcName != "") {
              $this->_formatter($funcName);
            }
          }
        }
      }
    }
    if (isset($_REQUEST['ajaxForm']) && $_REQUEST['ajaxForm'] == $this->formName) {
      $this->formAJAXsubmitted = true;
    } else {
      $this->formAJAXsubmitted = false;
    }
  }
}

// mod
$db = new CdbClass;
$strDataID = getPostValue('dataID');
$isNew = ($strDataID == "");
$f = new clsFormMod("form1", 3, "100%", "");
$f->disableFormTag();
$f->caption = strtoupper($strWordsFILTERDATA);
$f->addHidden("dataID", $strDataID);
//$f->addFieldSet(getWords("search criteria"), 1);
$f->addInput(
    getWords("invitation date") . " " . getWords("from"),
    "date_from",
    date($_SESSION['sessionDateSetting']['php_format']),
    [],
    "date",
    true,
    true,
    true
);
$f->addInput(getWords("date thru"), "date_thru", date($_SESSION['sessionDateSetting']['php_format']), [], "date", true, true, true);
$f->addInput(getWords("candidate name"), "candidate_name", "", [], "string", false, true, true);
$f->addInput(getWords("MRF No."), "mrf_no", "", [], "string", false, true, true);
$f->addSelect(getWords("position"), "position_code", getDataListPosition(null, true), [], "string", false);
$f->addSubmit("btnSearch", getWords("show data"), ["onClick" => "javascript:doSearch()"], true, true, "", "", "");
//$f->addSubmit("btnPrint", getWords("print"), array("onClick" => "javascript:printList()"), true, true, "", "", "");
$f->addSubmit("btnExportXLS", getWords("excel"), ["onClick" => "javascript:exportExcel()"], true, true, "", "", "");
$formInput = $f->render();
$strDataSummary = ""; // untuk tampilan summary berdasar status
$strDataNotComing = ""; // untuk tampilan summary  tidak datang
$bolPrint = false;
$bolExcel = false;
if (isset($_REQUEST['btnPrint'])) {
  $bolPrint = true;
}
if (isset($_REQUEST['btnExportXLS'])) {
  $bolExcel = true;
}

class cDataGrid2 extends cDataGridNew
{

  /*override this function*/
  function _printClosingTableContent()
  {
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
      return "";
    }
    $str = "
      </table>";
    global $bolPrint;
    if ($bolPrint) {
      global $strDataSummary;
      global $strDataNotComing;
      $str .= "
          <table cellspacing=0 cellpadding=1 border=0 class='table table-striped table-hover contentGrid'>
          	<tbody>
	            <tr>
	              <td width=200>$strDataSummary</td>
	              <td width=200>$strDataNotComing</td>
	            </tr>
	            </tbody>
          </table>
        ";
    }
    return $str;
  }

  function printOpeningRow($intRows, $rowDb)
  {
    $strResult = "";
    if ($rowDb['ori_result'] == 1) { // diterima
      $strClass = "class=bgAccepted";
    } else if ($rowDb['ori_result'] == 4) { // dipertimbangkan
      $strClass = "class=bgConsidered";
    } else {
      $strClass = "";
    }
    $strResult .= "
            <tr $strClass valign=\"top\">";
    return $strResult;
  }
}

$myDataGrid = new cDataGrid2("form1", "DataGrid1", "100%", "100%", true, false);
$myDataGrid->disableFormTag();
$myDataGrid->caption = strtoupper(getWords("recruitment process"));
//$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
if ($bolPrint || $bolExcel) {
  $myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ["rowspan" => 2, 'width' => 30], ['nowrap' => '']));
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("name"),
          "candidate_name",
          ["rowspan" => 2, 'width' => 150],
          ['nowrap' => 'nowrap'],
          true,
          true,
          "",
          "",
          "string",
          true,
          32
      )
  );
} else {
  $myDataGrid->addColumnCheckbox(
      new DataGrid_Column("chkID", "id", ["rowspan" => 2, 'width' => 30], ['align' => 'center', 'nowrap' => 'nowrap'])
  );
  $myDataGrid->addColumn(
      new DataGrid_Column(
          getWords("candidate"),
          "candidate_name",
          ["rowspan" => 2, 'width' => 150],
          ['nowrap' => 'nowrap'],
          true,
          true,
          "",
          "printViewLink()",
          "string",
          true,
          32
      )
  );
}
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("position"),
        "position",
        ["rowspan" => 2],
        ["nowrap" => "nowrap"],
        true,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("invitation date"),
        "invitation_date",
        ["rowspan" => 2, "width" => 80],
        ['nowrap' => 'nowrap'],
        true,
        false,
        "",
        "formatDate()",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("invitation method"),
        "invitation_method",
        ["rowspan" => 2],
        ["nowrap" => "nowrap"],
        false,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column((getWords("process")), "", ["colspan" => 5], [], false, false, "", "", "string", true)
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        (getWords('subject')),
        "process_subject",
        [],
        ["nowrap" => "nowrap", "valign" => "top"],
        false,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        (getWords('process')),
        "process_schedule",
        [],
        ['nowrap' => 'nowrap', "valign" => "top"],
        false,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        (('PIC')),
        "process_pic",
        [],
        ['nowrap' => 'nowrap', "valign" => "top"],
        true,
        true,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        (getWords('status')),
        "process_status",
        ["width" => 100],
        ['nowrap' => 'nowrap', "valign" => "top"],
        true,
        true,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        (getWords('note')),
        "process_note",
        [],
        ['nowrap' => 'nowrap', "valign" => "top"],
        true,
        true,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("result"),
        "result",
        ["rowspan" => 2, "width" => 75],
        ["nowrap" => "nowrap"],
        false,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        getWords("note"),
        "note",
        ["rowspan" => 2, "width" => 150],
        ["nowrap" => "nowrap"],
        false,
        false,
        "",
        "",
        "string",
        true,
        12
    )
);
if (!($bolPrint || $bolExcel)) {
  if ($bolCanEdit) {
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "",
            "",
            ["rowspan" => 2, 'width' => 45],
            ['align' => 'center', 'nowrap' => 'nowrap'],
            false,
            false,
            "",
            "printEditLink()",
            "string",
            false
        )
    );
    $myDataGrid->addColumn(
        new DataGrid_Column(
            "",
            "",
            ["rowspan" => 2, 'width' => 60],
            ['align' => 'center', 'nowrap' => 'nowrap'],
            false,
            false,
            "",
            "printFKRLink()",
            "string",
            false
        )
    );
  }
}
if ($bolCanDelete) {
  $myDataGrid->addSpecialButton(
      "btnDelete",
      "btnDelete",
      "submit",
      "Delete",
      "onClick=\"javascript:return myClient.confirmDelete();\"",
      "deleteData()"
  );
}
$myDataGrid->getRequest();
//--------------------------------
//get Data and set to Datagrid's DataSource by set the data binding (bind method)
$strCriteria = "";
$strCriteriaC = ""; // khusus kriteria untuk kandidat
$strCriteriaR = ""; // khusus kriteria untuk kandidat
if ($f->getValue('date_from') != '') {
  $strCriteria .= " AND invitation_date >= '" . $f->getValue('date_from') . "' ";
}
if ($f->getValue('date_thru') != '') {
  $strCriteria .= " AND invitation_date <= '" . $f->getValue('date_thru') . "' ";
}
if ($f->getValue('position_code') != '') {
  $strCriteria .= " AND upper(position) LIKE '%" . strtoupper($f->getValue('position')) . "%'";
}
if ($f->getValue('candidate_name') != '') {
  $strCriteriaC .= " AND upper(candidate_name) LIKE '%" . strtoupper($f->getValue('candidate_name')) . "%'";
}
if ($f->getValue('mrf_no') != '') {
  $strCriteriaR .= "
    AND id_recruitment_need IN (
      SELECT id FROM hrd_recruitment_need 
      WHERE upper(request_number) LIKE '%" . strtoupper($f->getValue('mrf_no')) . "%'
    )
  ";
}
if ($bolExcel) {
  $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
  $myDataGrid->strFileNameXLS = "recruitment_process_list.xls";
  $myDataGrid->strTitle1 = "Recruitment Process List";
  $myDataGrid->strTitle2 = "Printed Date: " . date("d/m/Y h:i:s");
} elseif ($bolPrint) {
  $myDataGrid->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_PRINT_HTML;
  $myDataGrid->strTitle1 = "Recruitment Process List";
  $myDataGrid->strTitle2 = "Printed Date: " . date("d/m/Y h:i:s");
}
$strSQLCOUNT = "
    SELECT COUNT(*) AS total
    FROM 
    (SELECT t1.*, t2.candidate_name 
      FROM (
        SELECT * FROM hrd_recruitment_process WHERE 1=1 " . $strCriteria . "
      ) AS t1 
      INNER JOIN (
        SELECT * FROM hrd_candidate WHERE 1=1 $strCriteriaC $strCriteriaR
       ) AS t2 ON t1.id_candidate = t2.id
      WHERE 1=1 $strCriteriaC
    ) AS x ";
$strSQL = "
    SELECT t1.*, t2.candidate_name 
      FROM (
        SELECT * FROM hrd_recruitment_process WHERE 1=1 " . $strCriteria . "
      ) AS t1 
      INNER JOIN (
        SELECT * FROM hrd_candidate WHERE 1=1 $strCriteriaC $strCriteriaR
       ) AS t2 ON t1.id_candidate = t2.id 
      WHERE 1=1 $strCriteriaC
            ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
$dataset = $myDataGrid->getData($db, $strSQL);
$tblFKR = new cModel("hrd_fkr");
$arrDataFKR = $tblFKR->findAll(null, "id, id_candidate", null, null, null, "id_candidate");
// cari dulu data status per proses
$arrSummary = []; // summary, per status
$arrNotComing = []; // summary khusus yang tidak datang, berdasar alasan tidak datang
//$strBreakChar = ($bolExcel) ? " \r\n " : " &nbsp;<br>\n "; // karakter untuk ganti baris
$strBreakChar = ($bolExcel) ? " \n " : " &nbsp;<br>\n "; // karakter untuk ganti baris
$tblRecruitmentProcessDetail = new cModel("hrd_recruitment_process_detail");
foreach ($dataset as &$rowDb) {
  $arrDetail = $tblRecruitmentProcessDetail->findAllByIdRecruitmentProcess(
      $rowDb['id'],
      null,
      "schedule_date",
      null,
      null,
      "id"
  );
  $strPName = $strPSched = $strPStat = $strPNote = $strPIC = "";
  foreach ($arrDetail AS $strID => $arrD) {
    if ($strPName != "") {
      $strPName .= $strBreakChar;
    }
    if ($strPSched != "") {
      $strPSched .= $strBreakChar;
    }
    if ($strPStat != "") {
      $strPStat .= $strBreakChar;
    }
    if ($strPNote != "") {
      $strPNote .= $strBreakChar;
    }
    if ($strPIC != "") {
      $strPIC .= $strBreakChar;
    }
    $strPName .= ($arrD['subject'] == "") ? $arrD['process_name'] : $arrD['subject'];
    $strPSched .= pgDateFormat($arrD['process_date'], "d-M-y");
    $strPStat .= getWords($ARRAY_RECRUITMENT_RESULT[$arrD['result']]) . " ";
    $strPNote .= $arrD['note'];
    $strPIC .= $arrD['pic'];
  }
  $rowDb['process_subject'] = $strPName;
  $rowDb['process_schedule'] = $strPSched;
  $rowDb['process_status'] = $strPStat;
  $rowDb['process_note'] = $strPNote;
  $rowDb['process_pic'] = $strPIC;
  $rowDb['ori_result'] = $rowDb['result'];
  $rowDb['result'] = getWords($ARRAY_RECRUITMENT_RESULT[$rowDb['result']]);
  // untuk tampilan summary berdasar status
  if (isset($arrSummary[$rowDb['ori_result']])) {
    $arrSummary[$rowDb['ori_result']]++;
  } else {
    $arrSummary[$rowDb['ori_result']] = 1;
  }
  if ($rowDb['ori_result'] == RECRUITMENT_NOT_COMING) {
    $strTmpNote = str_replace("  ", " ", trim(strtolower($rowDb['note'])));
    if (isset($arrNotComing[$strTmpNote])) {
      $arrNotComing[$strTmpNote]++;
    } else {
      $arrNotComing[$strTmpNote] = 1;
    }
  }
}
// tampilkan summary
if (count($arrSummary) > 0) {
  $strDataSummary .= "
      <table cellspacing=0 cellpadding=1 border=0 class='table table-striped table-hover dataGrid'>
      	<thead>
      		<tr>
        		<th colspan=2 class=center><b>" . getWords("summary") . " : </b></th>
        	</tr>
        </thead>	
        <tbody>
    ";
  foreach ($arrSummary AS $intStatus => $intTotal) {
    $strDataSummary .= "
        <tr> 
          <td>" . getWords($ARRAY_RECRUITMENT_RESULT[$intStatus]) . "&nbsp;</td>
          <td>&nbsp;" . $intTotal . "&nbsp;</td>
        </tr>
      ";
  }
  $strDataSummary .= "
    	</tbody>
      </table>
    ";
  if (count($arrNotComing) > 0) // common_variable.php
  {
    $strDataNotComing .= "
        <table cellspacing=0 cellpadding=1 border=0 class='dataGrid table table-striped table-hover'>
        <tbody>
        	<tr>
          	<td><b>" . getWords("not coming") . "</b></td>
          	<td><b>" . $arrSummary[RECRUITMENT_NOT_COMING] . "</b></td>
          </tr>
      ";
    foreach ($arrNotComing AS $strText => $intTotal) {
      $strDataNotComing .= "
          <tr> 
            <td>" . ucwords($strText) . "&nbsp;</td>
            <td>&nbsp;" . $intTotal . "&nbsp;</td>
          </tr>
        ";
    }
    $strDataNotComing .= "
      	</tbody>
        </table>
      ";
  }
}
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render();
$strConfirmDelete = getWords("are you sure to delete this selected data?");
$strConfirmSave = getWords("do you want to save this entry?");
$tbsPage = new clsTinyButStrong;
//write this variable in every page
$strPageTitle = getWords($dataPrivilege['menu_name']);
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords('recruitment process form');
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$pageSubMenu = recruitmentProcessSubMenu($strWordsRecruitmentProcessList);
$strTemplateFile = getTemplate("recruitment_process_list.html");
//------------------------------------------------
//Load Master Template
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
//--------------------------------------------------------------------------------
function printViewLink($params)
{
  extract($params);
  return "<a href=\"recruitment_process_edit.php?dataID=" . $record['id'] . "&dataCandidateID=" . $record['id_candidate'] . "\">" . $record['candidate_name'] . "</a>";
}

function printEditLink($params)
{
  extract($params);
  return "<a href=\"recruitment_process_edit.php?dataID=" . $record['id'] . "&dataCandidateID=" . $record['id_candidate'] . "\">" . getWords(
      'edit process'
  ) . "</a>";
}

function printFKRLink($params)
{
  extract($params);
  global $arrDataFKR;
  if (isset($arrDataFKR[$record['id_candidate']])) {
    return "<a href=\"javascript:openViewWindow('View FKR', 'fkr_edit.php?view=1&dataID=" . $arrDataFKR[$record['id_candidate']]['id'] . "', 700, 650)\">" . getWords(
        'view'
    ) . " FKR</a>";
  } else if (strtolower($record['result']) == 'accepted') {
    return "<a href=\"fkr_edit.php?dataCandidateID=" . $record['id_candidate'] . "\">" . getWords(
        'create'
    ) . " FKR</a>";
  } else {
    return "";
  }
}

function formatDate__($params)
{
  extract($params);
  return pgDateFormat($value, "d-M-y");
}

// fungsi untuk menghapus data
function deleteData()
{
  global $myDataGrid;
  $arrKeys = [];
  foreach ($myDataGrid->checkboxes as $strValue) {
    $arrKeys['id'][] = $strValue;
  }
  $tbl = new cModel("hrd_recruitment_process");
  if ($tbl->deleteMultiple($arrKeys)) {
    $myDataGrid->message = $tbl->strMessage;
  } else {
    $myDataGrid->errorMessage = $tbl->strMessage;
  }
} //deleteData
?>