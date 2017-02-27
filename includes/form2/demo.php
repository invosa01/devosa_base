<?php
//session_start();
include_once("../dbclass/dbClass.php");
include_once("form2.php");
(isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = ""; // get ID
$isNew = ($strDataID == "");
$db = new CDbClass;
$vendorData = getDataVendor();
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
    2, "900", "400"
);
//title or caption of the Form
$f->caption = "DATA VENDOR";
//adding help to the form
$f->addHelp("Help", nl2br("\nEnter information about vendor."), 0, 0, 300, 80);
//showing MINIMIZE button on the top-left corner
$f->showMinimizeButton = true;
$f->showMaximizeButton = true;
//showing CLOSE button on the top-left corner
$f->showCloseButton = true;
//add object <input type="hidden" name="dataID" id="dataID"...........
$f->addHidden("dataID");
$f->addHidden("isSaved");
$f->addTabPage("Page 1");
$f->addFieldSet("Vendor Information", 2);
$arrAutoComplete = [];
$arrAutoComplete[] = ["value" => 1, "text" => "Dedy"];
$arrAutoComplete[] = ["value" => 2, "text" => "Agus"];
$arrAutoComplete[] = ["value" => 3, "text" => "Linda"];
$arrAutoComplete[] = ["value" => 4, "text" => "Test"];
$arrAutoComplete[] = ["value" => 5, "text" => "AAAA"];
$f->addInputAutoComplete(
    "ID",
    "dataCodeAC",
    $arrAutoComplete,
    ["title" => "Vendor Code", "size" => 20],
    "string",
    true,
    true,
    true
);
$f->addLabelAutoComplete(
    "Name",
    "dataCodeAC", /*AC name*/
    ""
);
$f->addInput(
    "Vendor Number",
    "dataCode",
    $vendorData['code'],
    ["title" => "Vendor code", "size" => 50],
    "string",
    true,
    true,
    true
);
$f->addCheckBox("Company Name", "dataName2", $vendorData['companyName'], []);
$f->addRadio("Company Name", "dataName3", $vendorData['companyName'], []);
$f->addInput(
    "Company Name",
    "dataName",
    $vendorData['companyName'],
    ["title" => "Company Name", "size" => 50],
    "string",
    false,
    true,
    true
);
$f->addTextArea(
    "Office Address",
    "dataAddress",
    $vendorData['officeAddress'],
    ["title" => "Address", "cols" => 50, "rows" => 3],
    "string",
    false,
    true,
    true
);
$f->addInput(
    "Office Postcode",
    "dataZip",
    $vendorData['zipCode'],
    ["title" => "Office's Postcode", "size" => 5],
    "string",
    false,
    true,
    true
);
$f->addInput(
    "Office Phone",
    "dataPhoneNumber",
    $vendorData['phoneNumber'],
    ["title" => "Office's Phone", "size" => 5],
    "string",
    false,
    true,
    true
);
$f->addTextArea(
    "Factory Address",
    "dataFactoryAddress",
    $vendorData['factoryAddress'],
    ["title" => "Factory Address", "cols" => 50, "rows" => 3],
    "string",
    false,
    true,
    true
);
$f->addFieldSet("Additional Information", 1);
$f->addInput(
    "Established Date",
    "dataEstablishDate",
    $vendorData['establishDate'],
    ["title" => "Established Date"],
    "date",
    false,
    true,
    true
);
$f->addInput("NPWP", "dataNPWP", $vendorData['npwp'], ["title" => "Vendor NPWP"], "string", false, true, true);
$f->addSelect("Group", "dataGroup", [["value" => 1, "text" => "Dedy"]], ["title" => "Group of User"]);
$f->addFile("Data", "dataFile", "", ["title" => "Vendor NPWP"], "string", false, true, true);
$f->addTabPage("Page 2");
$f->addInput("Coba aja", "dataCobaAja", "", ["size" => 5], "string", false, true, true);
$f->addInput("Coba lagi", "dataCobaLagi", "", ["size" => 5], "string", false, true, true);
//this save button will hide after save <toggle>
$f->addSubmit(
    "btnSave",
    "Save",
    ["onClick" => "\"return confirm('Do you want to save this entry?');\"", "title" => "Save this change"],
    true,
    true,
    "",
    "",
    "saveData()"
);
$f->addBackButton("btnBack", "Back");
$formInput = $f->render();
echo "<html><body>";
echo $formInput;
echo "</body></html>";
//end of main program
//-------------------------------
//function to getData
function getDataVendor()
{
  global $db;
  global $isNew;
  global $strDataID;
  $vendorData = [];
  $success = false;
  if (!$isNew) {
    if ($db->connect()) {
      $strSQL = "SELECT * FROM \"procVendor\" WHERE id = $strDataID ";
      $resDb = $db->execute($strSQL);
      if ($db->numrows($resDb) > 0) {
        $rowDb = $db->fetchrow($resDb);
        $vendorData = $rowDb;
        $success = true;
      }
    }
  }
  if (!$success) {
    $vendorData['code'] = "";
    $vendorData['companyName'] = "";
    $vendorData['officeAddress'] = "";
    $vendorData['factoryAddress'] = "";
    $vendorData['businessLine'] = "";
    $vendorData['zipCode'] = "";
    $vendorData['factoryZipCode'] = "";
    $vendorData['npwp'] = "";
    $vendorData['certificate'] = "";
    $vendorData['notary'] = "";
    $vendorData['phoneNumber'] = "";
    $vendorData['factoryPhone'] = "";
    $vendorData['fax'] = "";
    $vendorData['email'] = "";
    $vendorData['web'] = "";
    $vendorData['establishDate'] = "";
  }
  return $vendorData;
}

function saveData()
{
  global $db;
  global $isNew;
  global $strIdMenu;
  global $strDataID;
  global $intMaxDetail;
  global $f;
  $strSQL = "";
  if ($db->connect()) {
    //if new then insert
    if ($isNew) {
      //adjust this QUERY WITH YOUR insert QUERY
      //---------------------------------------------------------------
      /*$strSQL  = "SELECT nextval('\"procVendor_id_seq\"');";
      $strSQL .= "INSERT INTO \"procVendor\" (id,modified_by,created, remarks, code,\"companyName\",\"officeAddress\", \"zipCode\", ";
      $strSQL .= "\"factoryAddress\", \"factoryZipCode\", \"phoneNumber\", \"factoryPhone\", \"fax\", ";
      $strSQL .= "npwp, certificate, notary, \"establishDate\", \"businessLine\", email, web) ";
      $strSQL .= "VALUES(currval('\"procVendor_id_seq\"') , '" .$_SESSION['sessionUserID']. "',now(), '', '".pg_escape_string($f->value('dataCode'))."', ";
      $strSQL .= "'".pg_escape_string($f->value('dataName'))."','".pg_escape_string($f->value('dataAddress'))."', ";
      $strSQL .= "'".pg_escape_string($f->value('dataZip'))."', '".pg_escape_string($f->value('dataFactoryAddress'))."',";
      $strSQL .= "'".pg_escape_string($f->value('dataFactoryZip'))."','".$f->value('dataPhoneNumber')."',";
      $strSQL .= "'".pg_escape_string($f->value('dataFactoryPhone'))."','".$f->value('dataFax')."',";
      $strSQL .= "'".pg_escape_string($f->value('dataNPWP'))."', '".pg_escape_string($f->value('dataCertificate'))."', ";
      $strSQL .= "'".pg_escape_string($f->value('dataNotary'))."', '".pg_escape_string($f->value('dataEstablishDate'))."', ";
      $strSQL .= "'".pg_escape_string($f->value('dataBusiness'))."', '".pg_escape_string($f->value('dataEmail'))."',";
      $strSQL .= "'".pg_escape_string($f->value('dataWebsite'))."');";

      $strVendorID = "currval('\"procVendor_id_seq\"')";*/
    } else {
      //adjust this QUERY WITH YOUR UPDATE QUERY
      //---------------------------------------------------------------
      /*
      $strSQL  = "UPDATE \"procVendor\" SET created = now(), modified_by = '" .$_SESSION['sessionUserID']. "',  ";
      $strSQL .= "code = '".pg_escape_string($f->value('dataCode'))."', \"companyName\" = '".pg_escape_string($f->value('dataName'))."',";
      $strSQL .= "\"officeAddress\" = '".pg_escape_string($f->value('dataAddress'))."', \"zipCode\" = '".pg_escape_string($f->value('dataZip'))."', ";
      $strSQL .= "\"factoryAddress\" = '".pg_escape_string($f->value('dataFactoryAddress'))."', \"factoryZipCode\" = '".pg_escape_string($f->value('dataFactoryZip'))."', ";
      $strSQL .= "\"phoneNumber\" = '".$f->value('dataPhoneNumber')."', \"factoryPhone\" = '".pg_escape_string($f->value('dataFactoryPhone'))."', ";
      $strSQL .= "fax = '".$f->value('dataFax')."', npwp = '".pg_escape_string($f->value('dataNPWP'))."', ";
      $strSQL .= "certificate = '".pg_escape_string($f->value('dataCertificate'))."', notary = '".pg_escape_string($f->value('dataNotary'))."',";
      $strSQL .= "\"establishDate\" = '".pg_escape_string($f->value('dataEstablishDate'))."', \"businessLine\" = '".pg_escape_string($f->value('dataBusiness'))."',";
      $strSQL .= "email = '".pg_escape_string($f->value('dataEmail'))."', web = '".pg_escape_string($f->value('dataWebsite'))."' ";
      $strSQL .= "WHERE id = $strDataID ;";

      $strVendorID = $strDataID;*/
    }
    //EXECUTE SQL
    /*$isSaved = false;
    if ($strSQL!="")
      if ($db->execute($strSQL))
        $isSaved = true; */
    $isSaved = true; //for example only
    if ($isSaved) {
      $f->readOnlyForm();
      //update hidden save to 1
      $f->objects['isSaved']['value'] = "1";
      //expert only: manipulation/change button
      $f->objects['btnSave']['visible'] = false;
      $f->objects['cboParentMenu']['htmlAfter'] = "";
      $f->objects['dataVisible']['htmlAfter'] = "";
      //$f->objects['btnCancel']['clientAction'] = "onClick='javascript:doBack();'";
      $f->objects['btnCancel']['value'] = "Finish";
      $f->message = "Data Vendor Saved";
      return true;
    } else {
      $f->message = "Failed to save data vendor";
      return false;
    }
  }
} // saveData
?>
