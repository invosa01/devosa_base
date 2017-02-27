<?php
//session_start();
include_once("../dbclass/dbClass.php");
include_once("form.php");
(isset($_REQUEST['dataID'])) ? $strDataID = $_REQUEST['dataID'] : $strDataID = ""; // get ID
//GET LAST URL
if (isset($_REQUEST['URL_REFERER'])) {
  $URL_REFERER = $_REQUEST['URL_REFERER'];
} else if (isset($_SERVER['HTTP_REFERER'])) {
  $URL_REFERER = $_SERVER['HTTP_REFERER'];
} else {
  $URL_REFERER = $_SERVER['PHP_SELF'];
}
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
    2, "900", "500"
);
//title or caption of the Form
$f->caption = "DATA VENDOR";
//adding help to the form
$f->addHelp("Help", nl2br("\nEnter information about vendor."), 0, 0, 300, 80);
//showing MINIMIZE button on the top-left corner
$f->showMinimizeButton = true;
//showing CLOSE button on the top-left corner
$f->showCloseButton = true;
//add object <input type="hidden" name="dataID" id="dataID"...........
$f->addHidden("dataID");
$f->addHidden("isSaved");
$f->addHidden("URL_REFERER", $URL_REFERER);
//add FieldSet object to Form
//after FieldSet added, all object that generated after this FieldSet will be put inside FieldSet until end of object or FOUND another FieldSet
$f->addFieldSet("Vendor Information");
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
    "Vendor Number",
    "dataCode",
    $vendorData['code'],
    "Vendor code",
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
    "Company Name",
    "dataName",
    $vendorData['companyName'],
    "Company name",
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
    "Office Address",
    "dataAddress",
    $vendorData['officeAddress'],
    "Office Address",
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
$f->addTextBox(
    "Office Postcode",
    "dataZip",
    $vendorData['zipCode'],
    "Office Postcode",
    false,
    true,
    false,
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
    "Office Phone",
    "dataPhoneNumber",
    $vendorData['phoneNumber'],
    "Office Phone",
    false,
    true,
    false,
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
$f->addTextArea(
    "Factory Address",
    "dataFactoryAddress",
    $vendorData['factoryAddress'],
    "Factory Address",
    false,
    true,
    false,
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
$f->addTextBox(
    "Factory Postcode",
    "dataFactoryZip",
    $vendorData['factoryZipCode'],
    "Factory Postcode",
    false,
    true,
    false,
    true,
    false,
    "string",
    null,
    "",
    "",
    "",
    50,
    10
);
$f->addTextBox(
    "Factory Phone",
    "dataFactoryPhone",
    $vendorData['factoryPhone'],
    "Factory Phone",
    false,
    true,
    false,
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
    "Fax Number",
    "dataFax",
    $vendorData['fax'],
    "Fax Number",
    false,
    true,
    false,
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
    "Email",
    "dataEmail",
    $vendorData['email'],
    "Email",
    false,
    true,
    false,
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
$f->addTextBox(
    "Website",
    "dataWebsite",
    $vendorData['web'],
    "Vendor Website",
    false,
    true,
    false,
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
$f->addFieldSet("Additional Information");
$f->addTextBox(
    "Established Date",
    "dataEstablishDate",
    $vendorData['establishDate'],
    "Established Date",
    false,
    true,
    false,
    true,
    false,
    "date"
);
$f->addTextBox(
    "NPWP",
    "dataNPWP",
    $vendorData['npwp'],
    "Vendor NPWP",
    false,
    true,
    false,
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
$f->addTextBox(
    "Legal Certificate No.",
    "dataCertificate",
    $vendorData['certificate'],
    "Legal Certificate No.",
    false,
    true,
    false,
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
$f->addTextBox(
    "Notary",
    "dataNotary",
    $vendorData['notary'],
    "Notary",
    false,
    true,
    false,
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
    "Business Line",
    "dataBusiness",
    $vendorData['businessLine'],
    "Business Line",
    false,
    true,
    false,
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
$f->addSelect("Group", "dataGroup", [["value" => 1, "text" => "Dedy"]], "", "Group of User");
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
    "btnCancel",
    "Cancel",
    "Cancel and back to previous page",
    true,
    true,
    null,
    "",
    "",
    "",
    "onClick='javascript:location.href=\"" . $f->getValue('URL_REFERER') . "\"'"
);
$f->getRequest();
$formInput = $f->render();
echo $formInput;
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
      $strSQL .= "INSERT INTO \"procVendor\" (id,updater,currdate, remarks, code,\"companyName\",\"officeAddress\", \"zipCode\", ";
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
      $strSQL  = "UPDATE \"procVendor\" SET currdate = now(), updater = '" .$_SESSION['sessionUserID']. "',  ";
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
