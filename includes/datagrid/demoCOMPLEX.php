<?php
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
//title or caption of the Form
$f->caption = "DATA MASTER EMPLOYEE";
//adding help to the form
$f->addHelp("Help", nl2br("\nPlease enter data employee."), 8, 28, 300, 80);
//showing MINIMIZE button on the top-left corner
$f->showMinimizeButton = true;
//showing CLOSE button on the top-left corner
$f->showCloseButton = true;
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
    "saveDasata()",
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
$f->getRequest();
//---begin of main program -------------------
$db = new CDbClass;
$myDataGrid = new cDataGrid("formData", "DataGrid1");
$myDataGrid->useAJAXTechnology = true;
$myDataGrid->addColumnCheckbox(
    new DataGrid_Column("chkID", "id", ['width' => '30'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumnNumbering(new DataGrid_Column("No", "", ['width' => '30'], ['nowrap' => '']));
$myDataGrid->addColumn(
    new DataGrid_Column("Employee ID", "employeeID", ['width' => '100'], ['align' => 'center', 'nowrap' => ''])
);
$myDataGrid->addColumn(new DataGrid_Column("Name", "employeeName", ['width' => '250'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("Address", "primaryAddress", null, ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("Sex", "gender", ['width' => '50'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("Department", "departmentCode", ['width' => '150'], ['nowrap' => '']));
$myDataGrid->addColumn(new DataGrid_Column("", "editLink", ['width' => '40'], ['nowrap' => ''], false));
$myDataGrid->addSpecialButton(
    "btnDelete",
    "btnDelete",
    "submit",
    "Delete",
    "onClick=\"javascript:return confirm('Delete this selected data?');\"",
    "deleteData()"
);
$myDataGrid->setPermission(/*view*/
    true, /*edit*/
    true, /*delete*/
    true
);
$myDataGrid->getRequest();
$myDataGrid->totalData = getTotalData($db);
$myDataGrid->bind(getData($db));
//------------------end of main program------------------
//---------------------------------------------------------
//function list
//---------------------------------------------------------
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

//menghitung total data
function getTotalData($db)
{
  $strSQLCOUNT = "SELECT COUNT(*) AS total FROM \"hrdEmployee\"";
  $totalData = 0;
  if ($db->connect()) {
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQLCOUNT = "SELECT COUNT(*) FROM \"hrdEmployee\" ";
    $strKriteria = getKriteriaString();
    if ($strKriteria != "") {
      $strSQLCOUNT .= "WHERE " . $strKriteria;
    }
    $resDb = $db->execute($strSQLCOUNT);
    if ($rowDb = $db->fetchrow($resDb)) {
      $totalData = $rowDb[0];
    } else {
      $totalData = 0;
    }
  }
  return $totalData;
}

function getKriteriaString()
{
  global $myDataGrid;
  $strResult = "";
  //handle search dari datagrid
  //property searchKriteria adalah: data kriteria yang dimasukkan pada saat tombol SEARCH di click
  //property pageSearchBy adalah: kriteria yang dipilih (dari drop-down list) pada saat tombol SEARCH di click,
  //      jika berisi data kosong berarti pencarian adalah untuk semua field
  if ($myDataGrid->isShowSearch) {
    if ($myDataGrid->searchKriteria != "") {
      if ($myDataGrid->pageSearchBy == "") {
        //any search field, maka looping untuk setial column dari datagrid yang ditampilkan
        foreach ($myDataGrid->columnSet as $col) {
          if ($col->fieldName != '' && $col->sortable) {
            if ($strResult == "") {
              $strResult = " lower(\"" . $col->fieldName . "\") LIKE '%" . strtolower(
                      $myDataGrid->searchKriteria
                  ) . "%'";
            } else {
              $strResult .= " OR lower(\"" . $col->fieldName . "\") LIKE '%" . strtolower(
                      $myDataGrid->searchKriteria
                  ) . "%'";
            }
          }
        }
        $strResult = "( " . $strResult . " )";
      } else {
        //specific search field
        $strResult = " lower(\"" . $myDataGrid->pageSearchBy . "\") LIKE '%" . strtolower(
                $myDataGrid->searchKriteria
            ) . "%'";
      }
    }
    return $strResult;
  } else {
    return "";
  }
}

function getData($db)
{
  global $myDataGrid;
  $arrData = [];
  if ($db->connect()) {
    //get Data and set to Datagrid's DataSource by set the data binding (bind method)
    $strSQL = "
        SELECT * FROM
          (SELECT e.*, d.\"departmentName\" FROM \"hrdEmployee\" AS e LEFT JOIN \"hrdDepartment\" AS d
            ON e.\"departmentCode\" = d.\"departmentCode\") AS a 
        ";
    $strKriteria = getKriteriaString();
    if ($strKriteria != "") {
      $strSQL .= "WHERE " . $strKriteria;
    }
    //handle sort
    if ($myDataGrid->isShowSort) {
      if ($myDataGrid->pageSortBy != "") {
        $strSQL .= " ORDER BY \"" . $myDataGrid->sortName . "\" " . $myDataGrid->sortOrder;
      }
    }
    //handle page limit
    if ($myDataGrid->isShowPageLimit) {
      if (is_numeric($myDataGrid->pageLimit) && $myDataGrid->pageLimit > 0) {
        $strSQL .= " LIMIT $myDataGrid->pageLimit OFFSET " . $myDataGrid->getOffsetStart();
      }
    }
    //get query
    $resDb = $db->execute($strSQL);
    //put result to array dataset
    $counter = 0;
    while ($rowDb = $db->fetchrow($resDb)) {
      //override content of $rowDb
      $counter++;
      $rowDb['employeeID'] = $rowDb['employeeID'] . "<input type='hidden' name='hDataEmployeeID$counter' id='hDataEmployeeID$counter' value='" . $rowDb['employeeID'] . "' />";
      $rowDb['employeeName'] = $rowDb['employeeName'] . "<input type='hidden' name='hDataEmployeeName$counter' id='hDataEmployeeName$counter' value='" . $rowDb['employeeName'] . "' />";
      $rowDb['primaryAddress'] = $rowDb['primaryAddress'] . "<input type='hidden' name='hDataAddress$counter' id='hDataAddress$counter' value='" . $rowDb['primaryAddress'] . "' />";
      $rowDb['department_code'] = $rowDb['department_code'] . " - " . $rowDb['department_name'] . "<input type='hidden' name='hDataDepartment$counter' id='hDataDepartment$counter' value='" . $rowDb['department_code'] . "' />";
      $rowDb['gender'] = ($rowDb['gender'] == 0) ? "Female" : "Male";
      $rowDb['editLink'] = "<a href=\"javascript:myClient.editData('$counter')\">Edit</a><input type='hidden' name='hDataID$counter' id='hDataID$counter' value='" . $rowDb['id'] . "' />";
      $arrData[] = $rowDb;
    }
  }
  return $arrData;
}

function deleteData()
{
  global $db;
  global $myDataGrid;
  if (!$myDataGrid->bolCanDelete) {
    echo "No delete privilege";
  } else {
    $strSQL = "";
    //$myDataGrid->checkboxes secara OTOMATIS berisi daftar checkbox yang diclick
    foreach ($myDataGrid->checkboxes as $strValue) {
      $strSQL .= "DELETE FROM \"hrdEmployee\" WHERE id = '$strValue';\n";
    }
    //set property message from datagrid class to print any message
    //e.g : $myDataGrid->message = "Hello world!";
    return executeDeleteSQL(
        $strSQL,
        "Employee", /*send message to datagrid */
        $myDataGrid->message
    );
  }
  return false;
} //deleteData
function addData()
{
  header("location:demoAdd.php");
}

?>
<html>
<head>
  <script type="text/javascript" src="../prototype/prototype.js"></script>
  <script type="text/javascript">
    //this function javascript below use prototype.js (if you use datagrid class you don't have to include this script//
    var myClient = {
      editData: function (idx) {
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
      }
    }
  </script>
</head>
<body>
<?php
echo $f->render();
echo "<br />";
echo $myDataGrid->render();
?>
</body>
</html>