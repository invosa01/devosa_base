<?php
include_once('../global/session.php');
include_once('../global.php');
include_once('../includes/datagrid2/datagrid.php');
include_once('../global/handledata.php');
include_once("../global/common_function.php");
$dataPrivilege = getDataPrivileges(
    basename($_SERVER['PHP_SELF']),
    $bolCanView,
    $bolCanEdit,
    $bolCanDelete,
    $bolCanApprove
);
if (!$bolCanView) {
  die(getWords("view denied"));
}
$db = new CDbClass;
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%", false, false, false, false);
$myDataGrid->caption = getWords("list of tables");
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column(getWords("table name"), "tablename", null, ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("record #"), "records", ['width' => '120'], ['align' => 'right']));
$myDataGrid->addColumn(new DataGrid_Column(getWords("sequence name"), "sequencename", null, ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("last sequence #"), "lastSeq", ['width' => '120'], ['align' => 'right'])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("actual sequence #"), "actualSeq", ['width' => '120'], ['align' => 'right'])
);
$myDataGrid->addColumn(
    new DataGrid_Column(getWords("duplicate #"), "duplicateSeq", ['width' => '120'], ['align' => 'right'])
);
if ($bolCanDelete) {
  $myDataGrid->addSpecialButton(
      "btnEmpty",
      "btnEmpty",
      "submit",
      getWords("empty record"),
      "onClick=\"javascript:if (confirm('Are you sure to emptying record of the selected table?')) return true\"",
      "emptyData()"
  );
}
if ($bolCanEdit) {
  $myDataGrid->addButton("btnFix", "btnFix", "submit", getWords("fix sequence #"), "", "fixSequence()", "btn-primary");
}
//event listener very important
$myDataGrid->getRequest();
//--------------------------------
//Set DataSource
$strSQL = "
    SELECT x.tablename, y.attname, y.sequencename
    FROM 
      (SELECT c1.relname AS tablename
        FROM pg_class AS c1
          INNER JOIN pg_namespace AS s1 ON s1.oid = c1.relnamespace
        WHERE c1.relkind = 'r' AND c1.relname !~ 'pg_' AND c1.relname !~ 'sql_') AS x
      LEFT JOIN      
    (
      SELECT t1.relname AS tablename, a.attname, t2.relname AS sequencename
        FROM pg_depend AS d INNER JOIN pg_class AS t1 ON t1.oid = d.refobjid
          INNER JOIN pg_class AS t2 ON t2.oid = d.objid
          INNER JOIN pg_namespace AS s1 ON s1.oid = t1.relnamespace
          INNER JOIN pg_namespace AS s2 ON s2.oid = t2.relnamespace
          INNER JOIN pg_attribute AS a ON a.attrelid = d.refobjid AND a.attnum = d.refobjsubid
        WHERE t1.relkind = 'r' AND t2.relkind = 'S' ) AS y
       ON x.tablename = y.tablename ";
$dataset = $myDataGrid->getData($db, $strSQL);
$myDataGrid->totalData = count($dataset);
$counter = 0;
foreach ($dataset as &$rowDb) {
  $counter++;
  $rowDb['id'] = $counter;
  $intTotalRecord = 0;
  $intActualMaxID = 0;
  $strDuplicateID = "";
  // cari total record
  if ($rowDb['attname'] == '') {
    $strSQL = "SELECT COUNT(*) AS total FROM \"" . $rowDb['tablename'] . "\" ";
  } else {
    $strSQL = "SELECT COUNT(*) AS total, MAX(\"" . $rowDb['attname'] . "\") AS maxid FROM \"" . $rowDb['tablename'] . "\" ";
  }
  $resTmp = $db->execute($strSQL);
  if ($rowTmp = $db->fetchrow($resTmp)) {
    if (is_numeric($rowTmp['total'])) {
      $intTotalRecord = $rowTmp['total'];
    } else {
      $intTotalRecord = 0;
    }
    if ($rowDb['attname'] == '') {
      $intActualMaxID = '';
    } else {
      if (is_numeric($rowTmp['maxid'])) {
        $intActualMaxID = $rowTmp['maxid'];
      } else {
        $intActualMaxID = '0';
      }
    }
  }
  // cari apakah ada ID yang double
  if ($rowDb['attname'] != '') {
    $strSQL = "
        SELECT \"" . $rowDb['attname'] . "\" AS id,
                COUNT(*) AS total
          FROM \"" . $rowDb['tablename'] . "\"
          GROUP BY \"" . $rowDb['attname'] . "\"
          HAVING COUNT(*) > 1 ";
    $resTmp = $db->execute($strSQL);
    while ($rowTmp = $db->fetchrow($resTmp)) {
      if ($strDuplicateID != "") {
        $strDuplicateID .= ", ";
      }
      $strDuplicateID .= $rowTmp['id'] . "[" . $rowTmp['total'] . "]";
    }
  }
  // cari apakah ada tabel sequense ID
  $strSeqTable = "";
  $intLastID = 0;
  $strSeqTable = $rowDb['sequencename'];
  if ($strSeqTable != '') {
    // cari data last value
    $strSQL = "SELECT last_value FROM \"$strSeqTable\" ";
    $resTmp = $db->execute($strSQL);
    if ($rowTmp = $db->fetchrow($resTmp)) {
      $intLastID = $rowTmp['last_value'];
    }
  }
  //$strClass = ($intTotalRecord == 0) ? "class = 'bgLate' " : "";
  //$strClass1 = ($intActualMaxID > $intLastID) ? "class = 'bgLate' " : "";
  $rowDb['tablename'] = "
          <input type=hidden name='tableName$counter' value=\"" . $rowDb['tablename'] . "\">
          <input type=hidden name='attName$counter' value=\"" . $rowDb['attname'] . "\">
          <input type=hidden name='seqName$counter' value=\"" . $rowDb['sequencename'] . "\">" . $rowDb['tablename'];
  $rowDb['records'] = $intTotalRecord;
  $rowDb['lastSeq'] = $intLastID;
  $rowDb['actualSeq'] = $intActualMaxID;
  $rowDb['duplicateSeq'] = $strDuplicateID;
}
//bind Datagrid with array dataset
$myDataGrid->bind($dataset);
$DataGrid = $myDataGrid->render(); //kalo pake TBS [var.DataGrid] harus ada
//----MAIN PROGRAM -----------------------------------------------------
$strPageTitle = getWords($dataPrivilege['menu_name']);
if (!$dataPrivilege['icon_file']) {
  $dataPrivilege['icon_file'] = "blank.png";
}
$pageIcon = "../images/icons/" . $dataPrivilege['icon_file'];
$strPageDesc = getWords("database management");
$pageHeader = pageHeader($pageIcon, $strPageTitle, $strPageDesc);
$strTemplateFile = getTemplate(str_replace(".php", ".html", basename($_SERVER['PHP_SELF'])));
//------------------------------------------------
//Load Master Template
$tbsPage = new clsTinyButStrong;
$tbsPage->LoadTemplate("../templates/master.html");
$tbsPage->Show();
//end of main Program
//----------------------------------------------------------------------
// fungsi untuk menghapus data
function emptyData()
{
  global $myDataGrid;
  $strSQL = "";
  foreach ($myDataGrid->checkboxes as $strValue) {
    $strSQL .= "DELETE FROM \"" . $_POST['tableName' . $strValue] . "\"; ";
  }
  return executeDeleteSQL($strSQL, getWords("records of selected table"), $myDataGrid->message);
} //emptyData
// fungsi untuk memperbaiki nilai sequense ID pada table sequense, sesuai dengan data terakhir pada tabel
function fixSequence()
{
  global $myDataGrid;
  global $db;
  if (!$db->connect()) {
    return false;
  }
  $strSQLUpdate = "";
  foreach ($myDataGrid->checkboxes as $intIndex) {
    $strTable = $_POST['tableName' . $intIndex];
    $attName = $_POST['attName' . $intIndex];
    $seqName = $_POST['seqName' . $intIndex];
    if ($attName == "") {
      continue;
    }
    $strSQL = "SELECT MAX(\"" . $attName . "\") AS maxid FROM \"$strTable\" ";
    $resDb = $db->execute($strSQL);
    if ($rowDb = $db->fetchrow($resDb)) {
      if ($rowDb['maxid'] == "") {
        $strSQLUpdate .= "SELECT pg_catalog.setval('\"" . $seqName . "\"', 1, false);";
      } else {
        $strSQLUpdate .= "SELECT pg_catalog.setval('\"" . $seqName . "\"', " . intval($rowDb['maxid']) . ");";
      }
    }
  }
  //echo $strSQLUpdate;
  if ($strSQLUpdate != "") {
    if ($resExec = $db->execute($strSQLUpdate)) {
      $myDataGrid->message = "Data Sequence Number of the Selected Table Fixed.";
      return true;
    } else {
      $myDataGrid->message = "Failed to Fix Sequence Number of the Selected Table!";
    }
  }
  return false;
} //deleteData
?>