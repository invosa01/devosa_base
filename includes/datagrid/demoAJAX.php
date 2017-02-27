<html>
<head>
  NO COMMENT FOR THIS DEMO, OPEN demo.php TO SEE FULL COMMENT EXAMPLE
  <br />
  THIS AJAX DATAGRID DEMO IS EXACTLY SAME WITH NORMAL DATAGRID FROM demo.php
  <br />
  THE DIFERRENCE IS THIS DATAGRID USE AJAX TECHNOLOGY
  <br />
  <br />
  JUST SET the property useAJAXTechnology to true, default is false
  <br />
  e.g : $myDataGrid->useAJAXTechnology = true; //after new class datagrid named myDataGrid created

<?php
include_once('datagrid.php');
include_once('../dbclass/dbClass.php');
include_once('../handledata/handledata.php');
//---begin of main program -------------------
$db = new CDbClass;
$myDataGrid = new cDataGrid("formData", "DataGrid1");
//masukkan sesuai nama script yang akan dipanggil jika AJAX request ke server
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column(
        "chkID",
        "id",
        ['width' => '30'],
        ['align' => 'center', 'nowrap' => ''],
        false,
        false,
        "",
        "",
        "string",
        false
    )
);
$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column("Employee ID", "employeeID", ['width' => '100'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column("Name", "employeeName", ['width' => '250'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("Address", "primaryAddress", null, ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("Sex", "gender", ['width' => '50'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("Department", "departmentCode", ['width' => '150'], ['nowrap' => '']));
$myDataGrid->addSpecialButton(
    "btnDelete",
    "btnDelete",
    "submit",
    "Delete",
    "onClick=\"javascript:return confirm('Delete this selected data?');\"",
    "deleteData()"
);
$myDataGrid->addSpecialButton(
    "btnApproved",
    "btnApproved",
    "submit",
    "Approved",
    "onClick=\"javascript:return confirm('Do you want to approve this selected data?');\"",
    "approvedData()"
);
$myDataGrid->addSpecialButton(
    "btnDenied",
    "btnDenied",
    "submit",
    "Denied",
    "onClick=\"javascript:return confirm('Do you want to deny this selected data?');\"",
    "denyData()"
);
$myDataGrid->addButton("btnAdd", "btnAdd", "submit", "Add", "", "addData()");
$myDataGrid->addButton("btnTest", "btnTest", "button", "Test");
$myDataGrid->addButtonExportExcel();
$myDataGrid->addButtonExportExcelHTML();
$myDataGrid->setPermission(/*view*/
    true, /*edit*/
    true, /*delete*/
    true
);
$myDataGrid->getRequest();
$strSQLCOUNT = "SELECT COUNT(*) AS total FROM \"hrdEmployee\"";
$strSQL = "
    SELECT * FROM
      (SELECT e.*, d.\"departmentName\" FROM \"hrdEmployee\" AS e LEFT JOIN \"hrdDepartment\" AS d
        ON e.\"departmentCode\" = d.\"departmentCode\") AS a 
    ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
//bind Datagrid with array of Data
//in this example, I use query to database, but of course you can use FIXED array too
//Don't CALL this bind METHOD before set PROPERTY of totalData
//wrong of doing this will result of incorrect PAGING information
$myDataGrid->bind($myDataGrid->getData($db, $strSQL));
echo $myDataGrid->render();
//------------------end of main program------------------
//---------------------------------------------------------
//function list
//---------------------------------------------------------
function deleteData()
{
  global $myDataGrid;
  //check delete privilege
  if (!$myDataGrid->bolCanDelete) {
    echo "No delete privilege";
  } else {
    $strSQL = "";
    //$myDataGrid->checkboxes secara OTOMATIS berisi daftar checkbox yang diclick
    foreach ($myDataGrid->checkboxes as $strValue)
      //adjust with your DELETE QUERY something like query below as example
      //PostgreSQL table to improve performance use begin...commit trans, you can use ; to deliminating several QUERY
      //and then execute query in one step
      //$strSQL  .= "DELETE FROM \"hrdEmployee\" WHERE id = '$strValue' ; \n";
      //executeSaveSQL in handledata.php, very useful connected with saving data cookie (to protect refresh)
      //return true if succesfully execute Query, false otherwise
    {
      return executeDeleteSQL(
          $strSQL, /*entity name*/
          "Employee", /*returned message*/
          $myDataGrid->message
      );
    }
  }
  return false;
} //deleteData
function addData()
{
  header("location:demoAdd.php");
}

?>