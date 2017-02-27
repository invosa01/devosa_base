<?php
include_once('datagrid.php');
//include from other class DB library to get data from database,
//actually you can set datagrid with fixed array of any data
include_once('../dbclass/dbClass.php');
//handledata.php, very useful function
//use to prevent repost query with the same data from BUG of refreshing browser (F5 button)
//when user did update, or insert data into database
//E.g : user do insert data to post to server, then after finish, the user do refresh (F5), then database will contain 2 similar record if you table doesn't have primary key
// but with this handledata.php, the problem will be solve, with or without primary key in you database-table.
include_once('../handledata/handledata.php');
//---begin of main program -------------------
$db = new CDbClass;
//class datagrid declaration
//Parameter:
//   1. : Form Name and Form Id
//   2 : Datagrid object name
//   3 : DataGrid width, default is "100%", you can set with any integer value e.g : 700
//   4 : DataGrid height, default is "100%", you can set with any integer value e.g : 500
//        but height will automatically adjust the height of the datagrid content
//   5 : boolean parameter, if true datagrid will SHOW page limit on the top of the datagrid, DISABLED if false. Default value is true
//   6 : boolean parameter, if true datagrid will have SEARCH capability on the top-left corner of the datagrid, DISABLED if false.  Default value is true
//   7 : boolean parameter, if true datagrid will have SORT capability on each column header of the datagrid,  DISABLED if false.  Default value is true
//   8 : boolean parameter, if true datagrid will SHOW page numbering capability on bottom of the datagrid,  DISABLED if false.  Default value is true
//   9 : boolean parameter, if true datagrid will SHOW the column header of the datagrid,  DISABLED if false.  Default value is true
//        if you choose to disabled column header, you can later add you own header with
//        function drawHeader($headerReplacement)          ----> $headerReplacement = your own header start with <tr><th>...</th></tr>
//   10: if you want to force PATH of datagrid, use this parameter.
$myDataGrid = new cDataGrid("formData", "DataGrid1", "100%", "100%");
$myDataGrid->setCaption("Attendance List");
//to add checkbox column just call this function
//parameters: same with addColumn at below instruction
//addColumnCheckBox will generate automagically
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
//to add numbering column just call this function
//parameters: same with addColumn at below instruction
$myDataGrid->addColumnNumbering(
    new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => ''], false, false, "", "", "numeric", true, 4)
);
//-------------------------------------------------------------------------------------------------
//Note : addColumn
//parameters:
//   1 : Column Name
//	     the column name shows as title row
//   2 : Field Name
//	     the database field name
//   3 : Array of Attribute Title/Header
//	     e.g.	: array('width'=>130, 'class'=>'tableHeader')
//   4 : Array of Attribute Item
//	     e.g.	: array('width'=>130)
//   5: Sortable / Searchable
//   6 : Title formatter
//   7: Item formatter
//-------------------------------------------------------------------------------------------------
$myDataGrid->addColumn(
    new DataGrid_Column(
        "ID",
        "idEmployee",
        ['width' => '100'],
        ['align' => 'center', 'nowrap' => ''],
        true,
        true,
        "",
        "printHeaderID()",
        "string",
        true,
        20
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        "Date",
        "attendanceDate",
        ['width' => '250'],
        ['nowrap' => ''],
        false,
        false,
        "",
        "",
        "string",
        true,
        20
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column(
        "Start",
        "attendanceStart",
        ['width' => '250'],
        ['nowrap' => ''],
        false,
        false,
        "",
        "",
        "string",
        true,
        20
    )
);
$myDataGrid->addColumn(
    new DataGrid_Column("Finish", "attendanceFinish", null, ['nowrap' => ''], false, false, "", "", "string", true, 20)
);
//for function addSpecialButton and addButton
//parameters:
//   1 : button ID
//   2: button name
//   3: button type = "submit" or "button"
//        the difference between "submit" and "button" are:
//           - submit,  if clicked then client will be submitted to server, so you must provide parameter 6
//           - button, if clicked then it just called client side script (parameter 5), will not be submitted to server
//  4 : value of the button
//  5 : client event, e.g : "onClick=\"javascript: alert('button clicked')\""  or you can called other javascript function from your HTML source
//  6 : server event, provide with function name, e.g : "deleteData()"
//       then you must have function deleteData() in your PHP file.
//to add button that is connect with checkbox, you can call function addSpecialButton
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
//to add submit button call addButton with 3rd parameter = "submit"
$myDataGrid->addButton("btnAdd", "btnAdd", "submit", "Add", "", "addData()");
//to add button call addButton with 3rd parameter = "button", only for client button, you can combine with javascript at your own
$myDataGrid->addButton(
    "btnTest",
    "btnTest",
    "button",
    "Test",
    "onClick=\"javascript: location.href = 'main.php'\"",
    ""
);
$myDataGrid->addButtonExportExcel();
$myDataGrid->addButtonExportExcelHTML();
//if you page can provide permission to view, edit, or delete, then you must set this to control datagrid permission
$myDataGrid->setPermission(/*view*/
    true, /*delete*/
    true, /*edit*/
    true
);
//get all request, post, get from client to be checked with any datagrid event/action
$myDataGrid->getRequest();
//must set totalData,  if and only if you use paging's feature
$strSQLCOUNT = "SELECT COUNT(*) FROM \"hrdAttendance\" ";
$strSQL = "SELECT * FROM \"hrdAttendance\" ";
$myDataGrid->totalData = $myDataGrid->getTotalData($db, $strSQLCOUNT);
//bind Datagrid with array of Data
//in this example, I use query to database, but of course you can use FIXED array too
//Don't CALL this bind METHOD before set PROPERTY of totalData
//wrong of doing this will result of incorrect PAGING information
$myDataGrid->bind($myDataGrid->getData($db, $strSQL));
//All requirement finished, now you can RENDER the datagrid
//if you use template such as TBS (TinyButStrong) template then:
//1. in your HTML template, put on the desired position string like:  [var.Datagrid; protect=no; htmlconv=no]
//    important: protect=no and htmlconv=no is required (removing this will result an error in javascript function and datagrid view)
//2. in you PHP file don't forget to put instruction:
//        $Datagrid = $myDataGrid->render();
//in this demo, I just echo to browser
echo $myDataGrid->render();
//die();
//$myDataGrid->exportToExcel("resultData.xls");
//------------------end of main program------------------
//---------------------------------------------------------
//function list
//---------------------------------------------------------
//menghitung total data
function printHeaderID($params)
{
  extract($params);
  //$counter, $record, $value
  return $value;
}

function addData()
{
  global $myDataGrid;
  header("location:../form2/demo.php");
}

?>