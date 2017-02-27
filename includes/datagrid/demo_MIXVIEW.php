<?php
//DEMO MIXVIEW WITH AJAX 
error_reporting(0);
//here I will draw form class
include_once("../form/form.php");
include_once("datagrid.php");
include_once('../dbclass/dbClass.php');
include_once('../handledata/handledata.php');
$db = new CDbClass;
//class datagrid declaration
//Parameter:
//   1. : Form Name and Form Id of this class FORM
//   2 : Number of Column that will become container for all objects(textbox, button, select, checkbox, etc)
//   3 : Form width, default is "100%", you can set with any integer value e.g : 700
//   4 : Form height, default is "100%", you can set with any integer value e.g : 500
//        but height will automatically adjust the height of the Form content
//   5 : if you want to force PATH of datagrid, use this parameter.
$f = new clsForm(
    "form1", /*2 column view*/
    2, "100%", "100%", "../form/"
);
//$f->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
//title or caption of the Form
$f->caption = "DATA MASTER EMPLOYEE";
//adding help to the form
$f->addHelp("Help", nl2br("\nPlease enter data employee."), 8, 28, 300, 80);
//showing MINIMIZE button on the top-left corner
$f->showMinimizeButton = true;
//showing CLOSE button on the top-left corner
//$f->showCloseButton = true;
//add object <input type="hidden" name="dataID" id="dataID"...........
$f->addHidden("dataID");
//add FieldSet object to Form
//after FieldSet added, all object that generated after this FieldSet will be put inside FieldSet until end of object or FOUND another FieldSet
$f->addFieldSet("Employee Information");
//add TextBox <input type="text" ......
//Parameters:
//   1 : String: TextBox Caption
//   2: String: TextBox Name
//   3: String: TextBox Value (only use for initialize only), will be ignore if the data was postback from server, unless the method resetBeforeRender is called
//    method resetBeforeRender will reset all object data to empty value
//   4: String: Hint when the mouse cursor over the object
//   5: Boolean: readonly control if true, normal otherwise, default false
//   6: Boolean: enabling control, default true, false will disabling control
//   7: Boolean: validating control at client browser according to parameter 10(dataType), default true, false will not check to validate control
//   8: Boolean: visibility control, default true, false will hide the control
//   9: Boolean: creating hidden object to replicate object value, default false
//       E.g : you want to save textbox value into hidden to be manipulate in you own Javascript coding the check with last data
//  10:String: datatype of the object, check in form.config.php for the list of datatype
//          DATATYPE_UNDEFINED
//          DATATYPE_DATE
//          DATATYPE_NUMERIC
//          DATATYPE_STRING
//          DATATYPE_EMAIL
//          DATATYPE_INTEGER
//        "
//  11:Array: array of attribute object
//     E.g : array("class" => "inputBox", "style" => "width:200px")
//  12:String: HTML before will be write before the object, for example you can add "&nbsp;"
//  13:String: HTML after will be write aflter the object,
//  14:String: client action, e.g : "onClick=\"javascript:alert('Hello World!')\""
//  15:Integer: number of character of the object (size)
//  16:Integer: maximum length of input character of the object  (maxlength)
//  17:Target/destination folder, used only if you using addFile method
$f->addTextBox(
    "Employee ID",
    "dataEmployeeID",
    "",
    "Employee ID",
    false,
    true,
    true,
    true,
    false,
    "string",
    null,
    "",
    "",
    "",
    50,
    20
);
$f->addTextBox(
    "Name",
    "dataName",
    "",
    "Employee Name",
    false,
    true,
    true,
    true,
    false,
    "string",
    null,
    "",
    "",
    "",
    50,
    50
);
$f->addTextArea(
    "Address",
    "dataAddress",
    "",
    "Primary Address",
    false,
    true,
    true,
    true,
    false,
    "string",
    null,
    "",
    "",
    "",
    46,
    2
);
$f->addFieldSet("Position Information");
$f->addSelect("Department", "dataDepartment", getMasterDepartment($db), "Employee's Deparment");
//this save button will hide after save <toggle>
$f->addSubmit(
    "btnSave",
    "Save",
    "Save this change",
    true,
    true,
    null,
    "",
    "",
    "saveData()",
    "onClick=\"return confirm('Do you want to save this entry?');\""
);
//function addButton($name, $value, $hint, $enabled=true, $visible = true, $arrAttribute = array(), $htmlBefore="", $htmlAfter="", $serverAction="", $clientAction="")
$f->addButton(
    "btnAdd",
    "Add New",
    "Add New Data",
    true,
    true,
    null,
    "",
    "",
    "",
    "onClick='javascript:myClient.editData(0)'"
);
//---begin of main program -------------------
$db = new CDbClass;
$myDataGrid = new cDataGrid("formData", "DataGrid1");
//masukkan sesuai nama script yang akan dipanggil jika anda ingin menggunakan fasilitas AJAX request saja
$myDataGrid->setAJAXCallBackScript(basename($_SERVER['PHP_SELF']));
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column(
        "chkID",
        "id",
        ['rowspan' => 2, 'width' => '30'],
        ['align' => 'center', 'nowrap' => ''],
        false,
        false,
        "",
        "",
        "string",
        false
    )
);
$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['rowspan' => 2, 'width' => '30'], ['nowrap' => '']));
//if you remember all setting parameter just set in one-row instruction like this...
$myDataGrid->addColumn(
    new DataGrid_Column(
        "Employee ID",
        "employeeID",
        ['rowspan' => 2, 'width' => '100'],
        ['align' => 'center', 'nowrap' => ''],
        true,
        true,
        "",
        "",
        "string",
        true,
        12
    )
);
//add spanned column 3
$myDataGrid->addColumn(new DataGrid_Column("Employee Information", "", ['colspan' => '3']));
//if you not remember enough, you can also modified the Datagrid_Column first,  before call addColumn, like this...
$employeeNameField = new DataGrid_Column("Name", "employeeName", ['width' => '250'], ['nowrap' => '']);
$employeeNameField->xlsColumnWidth = 32;
$myDataGrid->addColumn(
    $employeeNameField
);//new DataGrid_Column("Name", "employeeName", array('width' => '250'), array('nowrap' => ''), true, true, null, null, "string", true, 32));
$employeeAddress = new DataGrid_Column("Name", "primaryAddress", null, ['nowrap' => '']);
$employeeAddress->xlsColumnWidth = 64;
$myDataGrid->addColumn($employeeAddress);
$myDataGrid->addColumn(
    new DataGrid_Column(
        "Department",
        "departmentCode",
        ['width' => '150'],
        ['nowrap' => ''],
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
        "",
        "",
        ['rowspan' => 2, 'width' => '40'],
        ['align' => 'center'],
        false,
        false,
        "",
        "printEditLink()",
        "string",
        false
    )
);
$myDataGrid->addSpecialButton(
    "btnDelete",
    "btnDelete",
    "submit",
    "Delete",
    "onClick=\"javascript:return confirm('Delete this selected data?');\"",
    "deleteData()"
);
$myDataGrid->addButtonExportExcel("Export to Real Excel (Faster - using BIFF writer)");
$myDataGrid->addButtonExportExcelHTML("Export to HTML Excel (Excel 2003 only)");
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
//All requirement finished, now you can RENDER the datagrid
//if you use template such as TBS (TinyButStrong) template then:
//1. in your HTML template, put on the desired position string like:  [var.Datagrid; protect=no; htmlconv=no]
//    important: protect=no and htmlconv=no is required (removing this will result an error in javascript function and datagrid view)
//2. in you PHP file don't forget to put instruction:
//        $Datagrid = $myDataGrid->render();
//------------------end of main program------------------
//---------------------------------------------------------
//function list
//---------------------------------------------------------
function printEditLink($params)
{
  extract($params);
  return "
      <input type=hidden name='hDataID$counter' id='hDataID$counter' value='" . $record['id'] . "' />
      <input type=hidden name='hDataEmployeeID$counter' id='hDataEmployeeID$counter' value='" . $record['employeeID'] . "' />
      <input type=hidden name='hDataEmployeeName$counter' id='hDataEmployeeName$counter' value='" . $record['employeeName'] . "' />
      <input type='hidden' name='hDataAddress$counter' id='hDataAddress$counter' value='" . $record['primaryAddress'] . "' />
      <input type='hidden' name='hDataDepartment$counter' id='hDataDepartment$counter' value='" . $record['department_code'] . "' />
      <a href=\"javascript:myClient.editData($counter)\">Edit</a>";
}

function getMasterDepartment($db, $default = "")
{
  $arrData = [];
  $arrData[] = [
      "value" => "",
      "text" => "<-- choose here -->",
      "selected" => true
  ];
  if ($db->connect()) {
    $strSQL = "SELECT \"departmentCode\", \"departmentName\" FROM \"hrdDepartment\"";
    $resDb = $db->execute($strSQL);
    while ($rowDb = $db->fetchrow($resDb)) {
      $arrData[] = [
          "value" => $rowDb['department_code'],
          "text"  => $rowDb['department_code'] . " - " . $rowDb['department_name']
      ];
    }
  }
  return $arrData;
}

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
function saveData()
{
  global $f;
  $strDataID = $f->getValue('dataID');
  $isNew = ($strDataID != '') ? false : true;
  $strSQL = "";
  //if new then insert
  if ($isNew) {
    //adjust this QUERY WITH YOUR insert QUERY
    //---------------------------------------------------------------
    //$strSQL .= "INSERT INTO \"yourTable\" ...... ";
  } else {
    //adjust this QUERY WITH YOUR UPDATE QUERY
    //---------------------------------------------------------------
    //$strSQL  = "UPDATE \"yourTable\" SET ....
  }
  //EXECUTE SQL
  //executeSaveSQL in handledata.php, very useful to protect saving data from browser re-post/refresh
  //return true if succesfully execute Query, false otherwise
  $isSaved = executeSaveSQL(
      $strSQL, /*entity name*/
      "yourTable", /*returned message*/
      $f->message
  );
  $isSaved = true;
  //I just want to show you very good example below
  //After saving data if will show to user with READONLY data that was entried.
  //but if you don't like it just remove them
  if ($isSaved) {
    $f->message = "Data Saved";
  }
  return $isSaved;
} // saveData
?>
<?php
if (!$myDataGrid->renderToExcelHTML && !$myDataGrid->renderToExcel)
{ ?>
<html>
<head>
  <script type="text/javascript">
    //this function javascript below use prototype.js (if you use datagrid class you don't have to include this script//
    var myClient = {
      editData: function (idx) {
        $('formMessage').style.visibility = 'hidden';
        if (idx == 0) {
          //reset data
          $('dataEmployeeID').value = '';
          $('dataName').value = '';
          $('dataDepartment').value = '';
          $('dataAddress').value = '';
          $('dataID').value = '';
        }
        else {
          $('dataEmployeeID').value = $('hDataEmployeeID' + idx).value;
          $('dataName').value = $('hDataEmployeeName' + idx).value;
          $('dataDepartment').value = $('hDataDepartment' + idx).value;
          $('dataAddress').value = $('hDataAddress' + idx).value;
          $('dataID').value = $('hDataID' + idx).value;
        }
        $('dataEmployeeID').focus();
        $('dataEmployeeID').select();
      }
    }
  </script>
</head>
<body>
<?php
echo $f->render();
echo "<br />";
}
echo $myDataGrid->render();
?>
</body>
</html>
