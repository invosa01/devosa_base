<?php
//* to do: add input file, radio button, list
/*
   Dedy's class form Entry
   version 1.0
   PT. Invosa Systems
   All right reserved.
*/
require_once("form.config.php");

class clsForm
{

  var $CSSFileName;

  var $JSFileName;

  var $action = "";

  var $autocomplete = true;

  var $blankSpace;

  var $bolCanDelete;

  var $bolCanEdit;

  //if set true, the form Object will be validated with Javascript to check is it valid input or not
  //only the form Object with validate = true, will be validate

  var $bolCanView;

  var $caption = "INPUT DATA";

  var $colCount;

  var $dataset = [];

  var $editMode = false;

  var $enabled = true;

  var $fieldSets;

  var $formButton;

  var $formHidden;

  //var $objectType = array("text","password","select","checkbox", "file") ;
  /*var $dataTypes = array(
                    DATATYPE_UNDEFINED, 
                    DATATYPE_DATE, 
                    DATATYPE_NUMERIC, 
                    DATATYPE_STRING,
                    DATATYPE_EMAIL,
                    DATATYPE_INTEGER
                   );*/

  var $formName;

  var $formObject;

  var $groupingNumber;

  //set CSS file name

  var $hasFile = false;

  var $hasFormTag = true;

  var $height;

  //set caption of the form

  var $heightWindowHelp = "225";

  var $help = "No help provided in this form";

  //set help context here

  var $helpCaption = "Help";

  var $helpIcon;

  var $leftWindowHelp = "20";

  var $message = "";

  var $objects;

  var $readonly = false;

  var $resetData = false;

  var $showCaption = true;

  var $showCloseButton = false;

  var $showMinimizeButton = true;

  var $target = "";

  var $topWindowHelp = "157";

  var $validateEntryBeforeSubmit = true;

  var $width;

  var $widthWindowHelp = "250";

  // Class constructor
  //$colCount if set to 2 then the form view with 2 column

  function clsForm($formName = "form1", $colCount = 2, $formWidth = "100%", $formHeight = "100%", $path = "")
  {
    global $CLASSFORMPATH;
    global $FORM_CSS;
    if ($colCount <= 0) {
      die();
    }
    $this->width = $formWidth;
    $this->height = $formHeight;
    $this->formName = $formName;
    $this->colCount = $colCount;
    $this->groupingNumber = -1;
    $this->fieldSets = [];
    $this->formObject = [];
    $this->form = $this->formObject;
    $this->formHidden = [];
    $this->formButton = [];
    $this->blankSpace = "";
    //tambahkan 12 spasi, anda bisa mengubah dengan menyesuaikan tampilan HTML anda, supaya rapi saja
    $this->addBlankSpace(6);
    if ($path != "") {
      $CLASSFORMPATH = $path;
    }
    if (!isset($FORM_CSS)) {
      $FORM_CSS = "form";
    }
    $this->CSSFileName = ereg_replace('/+', '/', $CLASSFORMPATH . "/stylesheet/" . $FORM_CSS . ".css");
    $this->JSFileName = ereg_replace('/+', '/', $CLASSFORMPATH . "/scripts/form.js");
    $this->helpIcon = ereg_replace('/+', '/', $CLASSFORMPATH . "/images/help.gif");
    $this->setPermission();
  }

  //jika fungsi ini dipanggil maka datagrid tidak akan me-render tag <form....
  //pastikan jika anda memanggil fungsi ini, anda telah menyiapkan tag <form anda sendiri
  //kalo tidak semua fungsi sort, search, jump page, dll tidak berfungsi.

  function PrintJSInit()
  {
    global $validatorDataType;
    global $validatorErrorMessage;
    $newLine = "\n" . $this->blankSpace;
    //fungsi javascript ini adalah untuk mengecek validitas input dari form ketika form di submit ke server
    $strResult = $newLine . "<script language=\"javascript\">";
    $strResult .= $newLine . "  var my" . $this->formName . " = {";
    $strResult .= $newLine . "    submitCount : 0,";
    $strResult .= $newLine . "    doSubmit : function(confirmMessage) {";
    $strResult .= $newLine . "      var f = document." . $this->formName . ";";
    $strResult .= $newLine . "      if (this.submitCount != 0) return false;";
    if (!$this->readonly) {
      foreach ($this->objects as $key => $obj) {
        switch ($obj['type']) {
          case 'textarea':
          case 'password':
          case 'text':
          case 'select':
            if ($obj['validate'] == true) {
              $strResult .= $newLine . "      else if (!" . $validatorDataType[$obj['dataType']] . "(f." . $obj['name'] . ".value)) {";
              $strResult .= $newLine . "        alert('" . addslashes(
                      $validatorErrorMessage[$obj['dataType']] . $obj['caption']
                  ) . "!');";
              $strResult .= $newLine . "        f." . $obj['name'] . ".focus();";
              if ($obj['type'] == 'text' || $obj['type'] == 'password') {
                $strResult .= $newLine . "        if (f." . $obj['name'] . ".value != '') f." . $obj['name'] . ".select();";
              }
              $strResult .= $newLine . "      }";
            }
            break;
        }
      }
    }
    $strResult .= $newLine . "      else {";
    $strResult .= $newLine . "        if ((confirmMessage != '') && (confirmMessage!=null))";
    $strResult .= $newLine . "          if (!confirm(confirmMessage)) return false;";
    //TODO: maintain this submitCount, because submitCount will remain 0 if you submit back to server, but not refreshing the page (e.g:  to view PDF file (attachment))
    //I commented this for good
    //$strResult .= $newLine."      submitCount++;";
    $strResult .= $newLine . "        return true;";
    $strResult .= $newLine . "      }";
    $strResult .= $newLine . "      return false;";
    $strResult .= $newLine . "    },";
    $strResult .= $newLine . "    doMinimize : function(obj) {";
    $strResult .= $newLine . "      if (obj.className == 'formBoxMinimizeButton') {";
    $strResult .= $newLine . "        obj.className = 'formBoxMaximizeButton';";
    $strResult .= $newLine . "        document.getElementById(\"row_" . $this->formName . "\").style.display = \"none\"";
    $strResult .= $newLine . "      }";
    $strResult .= $newLine . "      else {";
    $strResult .= $newLine . "        obj.className = 'formBoxMinimizeButton';";
    $strResult .= $newLine . "        (document.all) ? document.getElementById(\"row_" . $this->formName . "\").style.display = 'block' : document.getElementById(\"row_" . $this->formName . "\").style.display = 'table-row';";
    $strResult .= $newLine . "      }";
    $strResult .= $newLine . "    },";
    $strResult .= $newLine . "    doClose : function() {";
    $strResult .= $newLine . "      document.getElementById(\"table_" . $this->formName . "\").style.display = \"none\"";
    $strResult .= $newLine . "    }";
    $strResult .= $newLine . "  }";
    $strResult .= $newLine . "</script>";
    return $strResult;
  }

  function addBlankSpace($spaces)
  {
    $this->blankSpace = str_pad("", $spaces);
  }

  function addButton(
      $name,
      $value,
      $hint,
      $enabled = true,
      $visible = true,
      $arrAttribute = [],
      $htmlBefore = "",
      $htmlAfter = "",
      $serverAction = "",
      $clientAction = ""
  ) {
    $arrData = [
        "enabled"      => $enabled,
        "visible"      => $visible,
        "type"         => "button",
        "name"         => $name,
        "hint"         => $hint,
        "value"        => $value,
        "clicked"      => false,
        "attribute"    => $this->serializeAttribute($arrAttribute),
        "htmlBefore"   => $htmlBefore,
        "htmlAfter"    => $htmlAfter,
        "clientAction" => $clientAction,
        "serverAction" => ""
    ];
    $this->formButton[] = &$arrData;
    $this->objects[$name] = &$arrData;
  }

  function addCheckBox(
      $caption,
      $name,
      $value,
      $hint,
      $readonly = false,
      $enabled = true,
      $validate = true,
      $visible = true,
      $createHidden = true,
      $dataType = "",
      $arrAttribute = [],
      $htmlBefore = "",
      $htmlAfter = "",
      $clientAction = ""
  ) {
    if ($value !== false && $value !== true) {
      //change $value to true and false, if passing by wrong data
      if ($value == 't') {
        $value = true;
      } else if ($value == 'f') {
        $value = false;
      } else if ($value == 'true') {
        $value = true;
      } else if ($value == 'false') {
        $value = false;
      } else if ($value == '1') {
        $value = true;
      } else if ($value == '0') {
        $value = false;
      } else {
        $value = false;
      }
    }
    $this->addFormObject(
        'checkbox',
        $caption,
        $name,
        $value,
        $value,
        $hint,
        $readonly,
        $enabled,
        $validate,
        $visible,
        true,
        "",
        $arrAttribute,
        $htmlBefore,
        $htmlAfter,
        $clientAction,
        ""
    );
    //return $checkbox;
  }

  function addFieldSet($name)
  {
    $this->groupingNumber++;
    $this->fieldSet[$this->groupingNumber] = ["name" => $name];
  }

  function addFormObject(
      $type,
      $caption,
      $name,
      $value,
      $default,
      $hint,
      $readonly = false,
      $enabled = true,
      $validate = true,
      $visible = true,
      $createHidden = false,
      $dataType = "string",
      $arrAttribute = [],
      $htmlBefore = "",
      $htmlAfter = "",
      $clientAction = "",
      $serverAction = "",
      $size = 0,
      $maxlength = 0,
      $targetFolder = "",
      $labelStyle = null
  ) {
    if ($type == 'select' || $type == 'radio') {
      if ($type == 'select') {
        $selectKey = 'selected';
      } else {
        $selectKey = 'checked';
      }
      $selectedValue = "";
      $selectedText = "";
      //special for select, it must be array data, if not then manipulate to array data
      if (!is_array($value)) {
        $temp = $value;
        $value = [];
        $value[] = ['id' => $temp, 'value' => $temp, 'text' => $temp, $selectKey => false];
      } else {
        foreach ($value as $key => &$val) {
          if (!is_array($val)) {
            $tempVal = ["id" => "", "value" => $key, "text" => $val, $selectKey => false];
            $val = $tempVal;
          } else {
            if (!isset($val['id'])) {
              $val['id'] = "";
            }
            if (!isset($val['value'])) {
              $val['value'] = "";
            }
            if (!isset($val['text'])) {
              $val['text'] = "";
            }
            if (!isset($val[$selectKey])) {
              $val[$selectKey] = false;
            } else if ($val[$selectKey]) {
              $selectedValue = $val['value'];
              $selectedText = $val['text'];
            }
          }
        }
      }
      $values = $value;
    } else if ($type == 'literal') {
      $selectedValue = $value;
      $selectedText = "";
      $values = "";
    } else {
      $selectedValue = $value;
      $selectedText = "";
      $values = "";
    }
    $dataForm = [
        "readonly"     => $readonly,
        "enabled"      => $enabled,
        "visible"      => $visible,
        "validate"     => $validate,
        "caption"      => $caption,
        "hint"         => $hint,
        "dataType"     => $dataType,
        "type"         => $type,
        "name"         => $name,
        "value"        => $selectedValue,
        "text"         => $selectedText,
        "values"       => $values,
        "attribute"    => $this->serializeAttribute($arrAttribute),
        "htmlBefore"   => $htmlBefore,
        "htmlAfter"    => $htmlAfter,
        "clientAction" => $clientAction,
        "serverAction" => $serverAction,
        "createHidden" => $createHidden,
        "size"         => $size,
        "maxlength"    => $maxlength,
        "labelStyle"   => $labelStyle,
        "targetFolder" => $targetFolder
    ];
    if ($this->groupingNumber < 0) {
      $this->groupingNumber = 0;
    }
    $this->formObject[$this->groupingNumber][] = &$dataForm;
    $this->objects[$name] = &$dataForm;
    if ($createHidden) {
      $this->formHidden[$name] = [
          "name"       => "hidden_" . $name,
          "value"      => $selectedValue,
          "enabled"    => true,
          "getRequest" => true
      ];
    }
  }

  function addHelp(
      $helpTitle = "Help",
      $helpContent = "No help provided in this form",
      $x = null,
      $y = null,
      $w = null,
      $h = null
  ) {
    if ($x !== null) {
      $this->leftWindowHelp = $x;
    }
    if ($y !== null) {
      $this->topWindowHelp = $y;
    }
    if ($w !== null) {
      $this->widthWindowHelp = $w;
    }
    if ($h !== null) {
      $this->heightWindowHelp = $h;
    }
    $this->helpCaption = $helpTitle;
    $this->help = $helpContent;
  }

  function addHidden($name, $value = "", $enabled = true, $getRequest = true)
  {
    $dataHidden = [
        "enabled"    => $enabled,
        "visible"    => 'false',
        "type"       => 'hidden',
        "name"       => $name,
        "value"      => $value,
        "getRequest" => $getRequest
    ];
    $this->formHidden[$name] = &$dataHidden;
    $this->objects[$name] = &$this->formHidden[$name];
    //return $dataHidden;
  }

  function addInputFile(
      $caption,
      $name,
      $value,
      $hint,
      $readonly = false/*when uploaded this will be set readonly*/,
      $enabled = true,
      $validate = true,
      $visible = true,
      $createHidden = true,
      $dataType = "string",
      $arrAttribute = [],
      $htmlBefore = "",
      $htmlAfter = "",
      $clientAction = "",
      $size = 0,
      $maxlength = 0,
      $targetFolder = "" /*untuk tipe data file  saja*/
  )
  {
    $this->hasFile = true;
    $this->addFormObject(
        'file',
        $caption,
        $name,
        $value,
        $value,
        $hint,
        false,
        $enabled,
        $validate,
        $visible,
        true
        /*must create hidden to save old file name*/,
        "string",
        $arrAttribute,
        $htmlBefore,
        $htmlAfter,
        $clientAction,
        "",
        $size,
        $maxlength,
        $targetFolder
    );
  }

  function addLabel(
      $caption,
      $name,
      $value,
      $hint = "",
      $visible = true,
      $createHidden = false,
      $dataType = "string",
      $arrAttribute = [],
      $htmlBefore = "",
      $htmlAfter = "",
      $clientAction = ""
  ) {
    $this->addFormObject(
        'label',
        $caption,
        $name,
        $value,
        $value,
        $hint,
        false,
        true,
        false,
        $visible,
        $createHidden,
        $dataType,
        $arrAttribute,
        $htmlBefore,
        $htmlAfter,
        $clientAction,
        ""
    );
  }

  function addLiteral($caption, $name, $literalHTML)
  {
    $this->addFormObject(
        'literal',
        $caption,
        $name,
        $literalHTML,
        "",
        "",
        false,
        true,
        false,
        true,
        false,
        DATATYPE_STRING,
        [],
        "",
        "",
        "",
        "",
        ""
    );
    //return $textarea;
  }
  //This function is to create label text object
  //choose $dataType between (date, numeric, string, decimal, integer)
  //Parameter $clientAction is clientAction/javascript
  //e.g. if you provide $clientAction = "onKeyDown='doTest()'" then you must have javascript function called doTest()
  //to Create Hidden field associate with this textbox then pass $createHidden = true;

  function addPassword(
      $caption,
      $name,
      $value,
      $hint,
      $readonly = false,
      $enabled = true,
      $validate = true,
      $visible = true,
      $createHidden = false,
      $dataType = "string",
      $arrAttribute = [],
      $htmlBefore = "",
      $htmlAfter = "",
      $clientAction = "",
      $size = 0,
      $maxlength = 0
  ) {
    $this->addFormObject(
        'password',
        $caption,
        $name,
        $value,
        $value,
        $hint,
        $readonly,
        $enabled,
        $validate,
        $visible,
        $createHidden,
        $dataType,
        $arrAttribute,
        $htmlBefore,
        $htmlAfter,
        $clientAction,
        "",
        $size,
        $maxlength
    );
  }

  function addRadio(
      $caption,
      $name,
      $value,
      $hint,
      $readonly = false,
      $enabled = true,
      $validate = true,
      $visible = true,
      $createHidden = false,
      $arrAttribute = [],
      $htmlBefore = "",
      $htmlAfter = "",
      $clientAction = "",
      $col = 0
  ) {
    $this->addFormObject(
        'radio',
        $caption,
        $name,
        $value,
        null,
        $hint,
        $readonly,
        $enabled,
        $validate,
        $visible,
        $createHidden,
        DATATYPE_STRING,
        $arrAttribute,
        $htmlBefore,
        $htmlAfter,
        $clientAction,
        "",
        $col
    );
  }

  //This function is to create input text object
  //choose $dataType between (date, numeric, string, decimal, integer)
  //Parameter $clientAction is clientAction/javascript
  //e.g. if you provide $clientAction = "onKeyDown='doTest()'" then you must have javascript function called doTest()
  //to Create Hidden field associate with this textbox then pass $createHidden = true;

  function addReset(
      $name,
      $value,
      $hint,
      $enabled = true,
      $visible = true,
      $arrAttribute = [],
      $htmlBefore = "",
      $htmlAfter = "",
      $serverAction = "",
      $clientAction = ""
  ) {
    $arrData = [
        "enabled"      => $enabled,
        "visible"      => $visible,
        "type"         => "reset",
        "name"         => $name,
        "hint"         => $hint,
        "value"        => $value,
        "clicked"      => false,
        "attribute"    => $this->serializeAttribute($arrAttribute),
        "htmlBefore"   => $htmlBefore,
        "htmlAfter"    => $htmlAfter,
        "clientAction" => $clientAction,
        "serverAction" => ""
    ];
    $this->formButton[] = &$arrData;
    $this->objects[$name] = &$arrData;
  }

  function addSelect(
      $caption,
      $name,
      $value,
      $hint,
      $readonly = false,
      $enabled = true,
      $validate = true,
      $visible = true,
      $createHidden = false,
      $arrAttribute = [],
      $htmlBefore = "",
      $htmlAfter = "",
      $clientAction = ""
  ) {
    $this->addFormObject(
        'select',
        $caption,
        $name,
        $value,
        null,
        $hint,
        $readonly,
        $enabled,
        $validate,
        $visible,
        $createHidden,
        DATATYPE_STRING,
        $arrAttribute,
        $htmlBefore,
        $htmlAfter,
        $clientAction,
        ""
    );
  }

  function addSubmit(
      $name,
      $value,
      $hint,
      $enabled = true,
      $visible = true,
      $arrAttribute = [],
      $htmlBefore = "",
      $htmlAfter = "",
      $serverAction = "",
      $clientAction = ""
  ) {
    $arrData = [
        "enabled"      => $enabled,
        "visible"      => $visible,
        "type"         => "submit",
        "name"         => $name,
        "value"        => $value,
        "clicked"      => false,
        "hint"         => $hint,
        "attribute"    => $this->serializeAttribute($arrAttribute),
        "htmlBefore"   => $htmlBefore,
        "htmlAfter"    => $htmlAfter,
        "clientAction" => $clientAction,
        "serverAction" => $serverAction
    ];
    $this->formButton[] = &$arrData;
    $this->objects[$name] = &$arrData;
  }

  function addTextArea(
      $caption,
      $name,
      $value,
      $hint,
      $readonly = false,
      $enabled = true,
      $validate = true,
      $visible = true,
      $createHidden = false,
      $dataType = "string",
      $arrAttribute = [],
      $htmlBefore = "",
      $htmlAfter = "",
      $clientAction = "",
      $cols = 0,
      $rows = 0
  ) {
    $this->addFormObject(
        'textarea',
        $caption,
        $name,
        $value,
        $value,
        $hint,
        $readonly,
        $enabled,
        $validate,
        $visible,
        $createHidden,
        $dataType,
        $arrAttribute,
        $htmlBefore,
        $htmlAfter,
        $clientAction,
        "",
        $cols,
        $rows
    );
    //return $textarea;
  }

  function addTextBox(
      $caption,
      $name,
      $value,
      $hint,
      $readonly = false,
      $enabled = true,
      $validate = true,
      $visible = true,
      $createHidden = false,
      $dataType = "string",
      $arrAttribute = [],
      $htmlBefore = "",
      $htmlAfter = "",
      $clientAction = "",
      $size = 0,
      $maxlength = 0,
      $labelWidth = null
  ) {
    $this->addFormObject(
        'text',
        $caption,
        $name,
        $value,
        $value,
        $hint,
        $readonly,
        $enabled,
        $validate,
        $visible,
        $createHidden,
        $dataType,
        $arrAttribute,
        $htmlBefore,
        $htmlAfter,
        $clientAction,
        "",
        $size,
        $maxlength,
        "",
        $labelWidth
    );
  }

  function disableForm()
  {
    $this->enabled = false;
    foreach ($this->objects as $value) {
      if ($value['type'] == "text" || $value['type'] == "password" || $value['type'] == "select" || $value['type'] == "textarea") {
        $value['enabled'] = false;
      }
    }
  }

  //This function is to create input text object
  //Parameter $value must be an 2 dimensional array
  //provide array with 3 keys "id","value","text"
  //e,g, : $value = array(array("id"=>"","value"=>1,"text"=>"january", "selected" => true), array("id"=>"","value"=>2,"text"=>"february"))
  //if any key is missing select will miss the key
  //Parameter $clientAction is clientAction/javascript
  //e.g. if you provide $clientAction = "onChange='doTest()'" then you must have javascript function called doTest()
  //to Create Hidden field associate with this textbox then pass $createHidden = true;

  function disableFormTag()
  {
    $this->hasFormTag = false;
  }

  function drawHidden($obj)
  {
    $strResult = "<input type=\"hidden\" name=\"" . $obj['name'] . "\" id=\"" . $obj['name'] . "\" value=\"" . $obj['value'] . "\"";
    if (!$obj['enabled']) {
      $strResult .= " disabled";
    }
    $strResult .= ">";
    return $strResult;
  }

  function drawObject($obj)
  {
    $strResult = "";
    switch ($obj['type']) {
      case 'select' :
        if ($obj['visible']) {
          if ($this->readonly) {
            $strResult = $obj['htmlBefore'] . "<span id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\">" . $obj['text'] . "</span>" . $obj['htmlAfter'];
          } else {
            $strResult = $obj['htmlBefore'] . "<select id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" " . $obj['clientAction'];
            if ($obj['attribute'] != '') {
              $strResult .= " " . $obj['attribute'];
            }
            if (!$obj['enabled']) {
              $strResult .= " disabled";
            }
            if ($obj['hint'] != "") {
              $strResult .= " title=\"" . $obj['hint'] . "\"";
            }
            $strResult .= ">";
            $isSelected = false;
            foreach ($obj['values'] as $key => $opsi) {
              $strResult .= "<option";
              if (isset($opsi['id']) && $opsi['id'] != '') {
                $strResult .= " id=" . $opsi['id'];
              }
              $strResult .= " value='" . str_replace("'", "\'", $opsi['value']) . "'";
              if (!$isSelected && $opsi['selected'] && !$this->resetData) {
                $strResult .= " selected";
                $isSelected = true;
              }
              $strResult .= ">" . $opsi['text'] . "</option>";
            }
            $strResult .= "</select>" . $obj['htmlAfter'];
          }
        }
        break;
      case 'radio' :
        if ($obj['visible']) {
          if ($this->readonly) {
            $strResult = $obj['htmlBefore'] . "<span id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\">" . $obj['text'] . "</span>" . $obj['htmlAfter'];
          } else {
            $strPreResult = "<input type=\"radio\" id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" " . $obj['clientAction'];
            if ($obj['attribute'] != '') {
              $strPreResult .= " " . $obj['attribute'];
            }
            if (!$obj['enabled']) {
              $strPreResult .= " disabled";
            }
            if ($obj['hint'] != "") {
              $strPreResult .= " title=\"" . $obj['hint'] . "\"";
            }
            //$strResult .= ">";
            $isChecked = false;
            $strResult = "";
            foreach ($obj['values'] as $key => $opsi) {
              $strResult .= $strPreResult . " value='" . str_replace("'", "\'", $opsi['value']) . "'";
              if (!$isChecked && $opsi['checked'] && !$this->resetData) {
                $strResult .= " checked";
                $isChecked = true;
              }
              $strResult .= ">" . $opsi['text'];
              //untuk mode penampilan, jika tidak 0 maka vertical, default horizontal
              if ($obj['size'] != 0) {
                $strResult .= "<br />\n";
              } else {
                $strResult .= "&nbsp;\n";
              }
            }
            $strResult = $obj['htmlBefore'] . $strResult . $obj['htmlAfter'];
          }
        }
        break;
      case 'reset' :
      case 'submit' :
      case 'button' :
        if ($obj['visible']) {
          $strResult = $obj['htmlBefore'] . "<input type=\"" . $obj['type'] . "\" id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" value=\"" . $obj['value'] . "\" " . $obj['clientAction'];
          if ($obj['attribute'] != '') {
            $strResult .= " " . $obj['attribute'];
          }
          if (!$obj['enabled']) {
            $strResult .= " disabled";
          }
          if ($obj['hint'] != "") {
            $strResult .= " title=\"" . $obj['hint'] . "\"";
          }
          $strResult .= ">" . $obj['htmlAfter'];
        }
        break;
      case 'label':
        if ($obj['visible']) {
          if ($this->readonly) {
            $strResult = $obj['htmlBefore'] . "<span id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\">" . nl2br(
                    str_replace(" ", "&nbsp;", $obj['value'])
                ) . "&nbsp;</span>" . $obj['htmlAfter'];
          } else {
            $strResult = $obj['htmlBefore'] . "<span id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" " . $obj['clientAction'];
            if ($obj['attribute'] != '') {
              $strResult .= " " . $obj['attribute'];
            }
            $strResult .= ">" . nl2br($obj['value']) . "</span>" . $obj['htmlAfter'];
            $strResult .= "<input type=\"hidden\" name=\"" . $obj['name'] . "\" value=\"" . $obj['value'] . "\"";
            if ($obj['hint'] != "") {
              $strResult .= " title=\"" . $obj['hint'] . "\"";
            }
            if (!$obj['enabled']) {
              $strResult .= " disabled";
            }
            $strResult .= ">";
          }
        }
        break;
      case 'textarea':
        if ($obj['visible']) {
          if ($this->readonly) {
            $strResult = $obj['htmlBefore'] . "<span id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\">" . nl2br(
                    str_replace(" ", "&nbsp;", $obj['value'])
                ) . "&nbsp;</span>" . $obj['htmlAfter'];
          } else {
            $strResult = $obj['htmlBefore'] . "<textarea id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" " . $obj['clientAction'];
            //replace size to cols, and maxlength to rows in textarea type
            if ($obj['size'] != 0) {
              $strResult .= " cols=\"" . $obj['size'] . "\"";
            }
            if ($obj['maxlength'] != 0) {
              $strResult .= " rows=\"" . $obj['maxlength'] . "\"";
            }
            if ($obj['attribute'] != '') {
              $strResult .= " " . $obj['attribute'];
            }
            if ($obj['readonly']) {
              $strResult .= " readonly";
            }
            if ($obj['hint'] != "") {
              $strResult .= " title=\"" . $obj['hint'] . "\"";
            }
            if (!$obj['enabled']) {
              $strResult .= " disabled";
            }
            $strResult .= ">";
            if (!$this->resetData) {
              $strResult .= $obj['value'];
            }
            $strResult .= "</textarea>" . $obj['htmlAfter'];
          }
        }
        break;
      case 'file' :
      case 'text' :
      case 'password' :
        if ($obj['visible']) {
          if ($this->readonly) {
            $strResult = $obj['htmlBefore'] . "<span id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\">" . nl2br(
                    str_replace(" ", "&nbsp;", $obj['value'])
                ) . "&nbsp;</span>" . $obj['htmlAfter'];
          } else {
            $strResult = $obj['htmlBefore'] . "<input type=\"" . $obj['type'] . "\" id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" " . $obj['clientAction'];
            if (!$this->resetData) {
              $strResult .= " value=\"" . $obj['value'] . "\"";
            }
            if ($obj['dataType'] == "date") {
              $strResult .= " size=\"10\" maxlength=\"10\"";
            } else {
              if ($obj['maxlength'] != 0) {
                $strResult .= " maxlength=\"" . $obj['maxlength'] . "\"";
              }
              if ($obj['size'] != 0) {
                $strResult .= " size=\"" . $obj['size'] . "\"";
              }
            }
            if ($obj['attribute'] != '') {
              $strResult .= " " . $obj['attribute'];
            }
            if ($obj['readonly']) {
              $strResult .= " readonly";
            }
            if ($obj['hint'] != "") {
              $strResult .= " title=\"" . $obj['hint'] . "\"";
            }
            if (!$obj['enabled']) {
              $strResult .= " disabled";
            }
            $strResult .= ">";
            if ($obj['dataType'] == "date") {
              $strResult .= "&nbsp;<input type=\"button\" name=\"btn" . $obj['name'] . "\" id=\"btn" . $obj['name'] . "\" class=\"buttonCalendar\">";
            }
            $strResult .= $obj['htmlAfter'];
          }
        }
        break;
      case 'checkbox' :
        if ($obj['visible']) {
          if ($this->readonly) {
            if ($obj['value']) {
              $strResult = $obj['htmlBefore'] . "<input type=\"checkbox\" id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" checked disabled>" . $obj['htmlAfter'];
            } else {
              $strResult = $obj['htmlBefore'] . "<input type=\"checkbox\" id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" disabled>" . $obj['htmlAfter'];
            }
          } else {
            $strResult = $obj['htmlBefore'] . "<input type=\"" . $obj['type'] . "\" id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\"";
            if ($obj['value'] && !$this->resetData) {
              $strResult .= " checked";
            }
            if ($obj['clientAction'] != '') {
              $strResult .= " " . $obj['clientAction'];
            }
            if ($obj['attribute'] != '') {
              $strResult .= " " . $obj['attribute'];
            }
            if ($obj['readonly']) {
              $strResult .= " readonly";
            }
            if ($obj['hint'] != "") {
              $strResult .= " title=\"" . $obj['hint'] . "\"";
            }
            if (!$obj['enabled']) {
              $strResult .= " disabled";
            }
            $strResult .= ">";
            $strResult .= $obj['htmlAfter'];
          }
        }
        break;
      case 'hidden' :
        $strResult = "<input type=\"hidden\" name=\"" . $obj['name'] . "\" id=\"" . $obj['name'] . "\" value=\"" . $obj['value'] . "\"";
        if (!$obj['enabled']) {
          $strResult .= " disabled";
        }
        $strResult .= ">";
        break;
      default:
        $strResult = $obj['value'];
    }
    //draw hidden object
    /*if (isset($obj['createHidden']))
      if ($obj['createHidden'] == true)
      {
        $strResult .= "<input type=\"hidden\" name=\"".$this->formHidden[$obj['name']]['name']."\" id=\"".$this->formHidden[$obj['name']]['name']."\" value=\"".$this->formHidden[$obj['name']]['value']."\"";
        if (!$obj['enabled']) $strResult .= " disabled";
        $strResult .= ">";
      }*/
    return $strResult;
  }

  function drawObjectCaption($obj)
  {
    if ($obj['type'] != 'hidden') {
      if ($obj['caption'] != "") {
        return "<label for=\"" . $obj['name'] . "\">" . $obj['caption'] . "</label>";
      }
    }
    return "";
  }

  function formatter($par)
  {
    $paramList = [];
    //set reserved word untuk $objects, $readonly dan $enabled
    $paramList['message'] = $this->message;
    $paramList['objects'] = $this->objects;
    $paramList['readonly'] = $this->readonly;
    $paramList['enabled'] = $this->enabled;
    // Define the Parameter list
    // Determine callback and additional parameters
    // Determine callback and additional parameters
    if ($size = strpos($par, '(')) {
      // Retrieve the name of the function to call
      $formatter = substr($par, 0, $size);
      if (strstr($formatter, '->')) {
        $formatter = explode('->', $formatter);
      } elseif (strstr($formatter, '::')) {
        $formatter = explode('::', $formatter);
      }
      // Build the list of parameters
      $length = strlen($par) - $size - 2;
      $parameters = substr($par, $size + 1, $length);
      $parameters = ($parameters === '') ? [] : split(',', $parameters);
      // Process the parameters
      foreach ($parameters as $param) {
        if ($param != '') {
          $param = str_replace('$', '', $param);
          if (strpos($param, '=') != false) {
            $vars = split('=', $param);
            $paramList[trim($vars[0])] = trim($vars[1]);
          } else {
            $paramList[$param] = $$param;
          }
        }
      }
      //end of foreach
    } else {
      $formatter = $par;
    }
    // Call the formatter
    if (is_callable($formatter)) {
      call_user_func($formatter, $paramList);
    } else {
      $this->message = "
      function " . $formatter . " does not exist in the server-side code,
      <br />
      please contact SOFTWARE VENDOR to fixed this problem";
      return false;
    }
    return true;
  }

  function getDefaultField()
  {
    $strResult = "";
    if (count($this->columnSet) > 0) {
      foreach ($this->columnSet as $col) {
        if ($col->fieldName != '') {
          $strResult = $col->fieldName;
          break;
        }
      }
    }
    return $strResult;
  }
  //This function is to create input submit object
  //Parameter $serverAction is a must
  //e.g. if you provide $serverAction = "saveDate()" then you must have PHP function called saveData()

  function getRequest()
  {
    if (count($this->objects) > 0)
      foreach ($this->objects as $key => &$obj)
        switch ($obj['type']) {
          case 'select':
            if (isset($_POST[$obj['name']])) {
              $obj['value'] = $_POST[$obj['name']];
              foreach ($obj['values'] as &$val) {
                if ($val['value'] == $_POST[$obj['name']]) {
                  $val['selected'] = true;
                  $obj['text'] = $val['text'];
                  break;
                } else {
                  $val['selected'] = false;
                }
              }
            } else if (isset($_POST["hidden_" . $obj['name']])) {
              $obj['value'] = $_POST["hidden_" . $obj['name']];
              foreach ($obj['values'] as &$val) {
                if ($val['value'] == $_POST["hidden_" . $obj['name']]) {
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
            if (isset($_POST[$obj['name']])) {
              $obj['value'] = $_POST[$obj['name']];
              foreach ($obj['values'] as &$val) {
                if ($val['value'] == $_POST[$obj['name']]) {
                  $val['checked'] = true;
                  $obj['text'] = $val['text'];
                  break;
                } else {
                  $val['checked'] = false;
                }
              }
            } else if (isset($_POST["hidden_" . $obj['name']])) {
              $obj['value'] = $_POST["hidden_" . $obj['name']];
              foreach ($obj['values'] as &$val) {
                if ($val['value'] == $_POST["hidden_" . $obj['name']]) {
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
            if (isset($_POST[$obj['name']])) {
              $obj['value'] = true;
            } else if (isset($_POST["hidden_" . $obj['name']])) {
              $obj['value'] = false;
            }
            break;
          case 'hidden' :
            if ($obj['getRequest']) {
              if (isset($_POST[$obj['name']])) {
                $obj['value'] = $_POST[$obj['name']];
              }
            }
            break;
          case 'file' :
            //jika ada post untuk input type=file, maka...
            if (isset($_FILES[$obj['name']])) {
              $this->uploadFile($obj['name'], $obj['targetFolder']);
            }
            break;
          case 'submit' :
            if (isset($_POST[$obj['name']])) {
              //this button have been submit to server
              $obj['clicked'] = true;
              $funcName = $obj['serverAction'];
              if ($funcName != "") {
                $this->formatter($funcName);
              }
            }
            break;
          case 'label' :
            break;
          default:
            if (isset($_POST[$obj['name']])) {
              $obj['value'] = $_POST[$obj['name']];
            } else if (isset($_POST["hidden_" . $obj['name']])) {
              $obj['value'] = $_POST["hidden_" . $obj['name']];
            }
            break;
        }
  }

  //This function is to create input text object
  //Parameter $clientAction is clientAction/javascript (is a must)
  //e.g. if you provide $clientAction = "onClick='doHelloWorld()'" then you must have javascript function called doHelloWorld()

  function getValue($name)
  {
    if (isset($this->objects[$name])) {
      if ($this->objects[$name]['type'] == "button" || $this->objects[$name]['type'] == "submit") {
        return $this->objects[$name]['clicked'];
      } else {
        return $this->objects[$name]['value'];
      }
    }
  }

  function printFormTag()
  {
    $newLine = "\n" . $this->blankSpace;
    $strResult = "";
    if ($this->hasFormTag) {
      //----------render tag form
      if ($this->target != "") {
        $strTarget = " target=\"" . $this->target . "\"";
      } else {
        $strTarget = "";
      }
      if ($this->action != "") {
        $strAction = " action=\"" . $this->action . "\"";
      } else {
        $strAction = "";
      }
      if ($this->hasFile) {
        $encType = " enctype=\"multipart/form-data\"";
      } else {
        $encType = "";
      }
      $strResult .= $newLine . "      <form name=\"" . $this->formName . "\" id=\"" . $this->formName . "\" method=\"post\"" . $strTarget . $strAction . $encType;
      if ($this->validateEntryBeforeSubmit) {
        $strResult .= " onsubmit=\"return my" . $this->formName . ".doSubmit();\"";
      }
      if (!$this->autocomplete) {
        $strResult .= " autocomplete=\"off\"";
      }
      $strResult .= ">";
      //----------end of render tag form
    }
    //----------render input  hidden (yang ditambahkan lewat fungsi addHidden) persis dibawah tag form
    foreach ($this->formHidden as $value) {
      $strResult .= $newLine . "        " . $this->drawHidden($value);
    }
    //----------end of render tag input hidden
    return $strResult;
  }

  //this method if called, then all form Object will be replaced with SPAN object
  //useful to make view only form, e.g. : after save data

  function printJSOnload()
  {
    $newLine = "\n" . $this->blankSpace;
    $strResult = "";
    if (count($this->formObject) > 0 && !$this->readonly) {
      $strResult = $newLine . "<script type=\"text/javascript\">";
      $strResult .= $newLine . "  var f = document." . $this->formName . ";";
      foreach ($this->objects as $value) {
        if ($value['type'] == 'select' || $value['type'] == 'text' || $value['type'] == 'password' || $value['type'] == 'textarea') {
          if ($value['visible'] && !$value['readonly'] && $value['enabled'] && $value['type'] != "label" && $value['type'] != "literal") {
            //----------render javascript to setfocus on the first object, focus only on visible, enabled, and not readonly, and not label
            $strResult .= $newLine . "  if (f." . $value['name'] . " != null)";
            $strResult .= $newLine . "  {";
            $strResult .= $newLine . "    f." . $value['name'] . ".focus();";
            if ($value['type'] == 'text' || $value['type'] == 'password' || $value['type'] == 'textarea') {
              $strResult .= $newLine . "    if (f." . $value['name'] . ".value.length > 0)";
              $strResult .= $newLine . "      f." . $value['name'] . ".select();";
            }
            $strResult .= $newLine . "  }";
            //----------end render javascript to setfocus
            break;
          }
        }
      }
      foreach ($this->formObject as $arrObject) {
        foreach ($arrObject as $value) {
          if ($value['dataType'] == 'date' && $value['type'] == 'text') //----------render calendar if any
          {
            $strResult .= $newLine . "  Calendar.setup({ inputField:\"" . $value['name'] . "\", button:\"btn" . $value['name'] . "\" });";
          }
        }
      }
      //----------end of render calendar
      foreach ($this->formObject as $arrObject) {
        foreach ($arrObject as $obj)
          switch ($obj['type']) {
            case 'select':
              $isSelected = false;
              //print_r($obj['values']);
              foreach ($obj['values'] as $valSelect) {
                if ($valSelect['selected']) {
                  $isSelected = true;
                  break;
                }
              }
              if (!$isSelected) {
                $strResult .= $newLine . "  f." . $obj['name'] . ".selectedIndex = -1;";
                $strResult .= $newLine . "  f." . $obj['name'] . ".value = \"\";";
              }
              break;
          }
      }
      $strResult .= $newLine . "</script>";
    }
    return $strResult;
  }

  function printObjectGroup($groupNumber)
  {
    $strResult = "";
    $newLine = "\n" . $this->blankSpace;
    $strResult .= $newLine . "        <table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\">";
    $strResult .= $newLine . "          <tr valign=\"top\">";
    //----------hitung jumlah form objects
    $jmlBaris = ceil(count($this->formObject[$groupNumber]) / $this->colCount);
    //----------hitung percentage dari kolom untuk tampilan multi kolom
    $percentWidth = round(100 / $this->colCount, 1);
    //render object2 ke html berdasarkan jumlah kolom
    //----------object yang tidak visible lompati saja jangan di-render.
    for ($i = 0; $i < $this->colCount; $i++) {
      $strResult .= $newLine . "            <td width=\"" . $percentWidth . "%\" style=\"padding-left:5px; padding-right:5px\">";
      $strResult .= $newLine . "              <table border=\"0\" cellpadding=\"1\" cellspacing=\"0\">";
      for ($j = $i * $jmlBaris; ($j < ($i + 1) * $jmlBaris && $j < count($this->formObject[$groupNumber])); $j++) {
        $strCaption = $this->drawObjectCaption($this->formObject[$groupNumber][$j]);
        $strResult .= $newLine . "                <tr valign=\"top\">";
        if (count($this->formObject[$groupNumber][$j]['labelStyle']) > 0) {
          $strResult .= $newLine . "                  <td " . $this->serializeAttribute(
                  $this->formObject[$groupNumber][$j]['labelStyle']
              ) . ">" . $strCaption . "&nbsp;</td>";
        } else {
          $strResult .= $newLine . "                  <td nowrap>" . $strCaption . "&nbsp;</td>";
        }
        if ($strCaption == "") {
          $strResult .= $newLine . "                  <td width=\"10\">&nbsp;</td>";
        } else {
          $strResult .= $newLine . "                  <td width=\"10\">:</td>";
        }
        $strResult .= $newLine . "                  <td>" . $this->drawObject(
                $this->formObject[$groupNumber][$j]
            ) . "</td>";
        $strResult .= $newLine . "                </tr>";
      }
      $strResult .= $newLine . "              </table>";
      $strResult .= $newLine . "            </td>";
    }
    $strResult .= $newLine . "          </tr>";
    $strResult .= $newLine . "        </table>";
    if (isset($this->fieldSet[$groupNumber])) {
      $strResult = $newLine . "        <fieldset><legend>" . $this->fieldSet[$groupNumber]['name'] . "</legend>" . $strResult . "</fieldset>";
    }
    return $strResult;
  }

  function printObjects()
  {
    $newLine = "\n" . $this->blankSpace;
    $strResult = "";
    for ($i = 0; $i <= $this->groupingNumber; $i++) {
      $strResult .= $this->printObjectGroup($i);
    }
    $strResult .= $newLine . "        <table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\">";
    $strResult .= $newLine . "          <tr valign=\"top\">";
    $strResult .= $newLine . "            <td height=\"30\" valign=\"middle\">";
    //----------render all button object
    foreach ($this->formButton as $value) {
      $strResult .= $this->drawObject($value) . "&nbsp;";
    }
    //----------end of render button
    $strResult .= $newLine . "            </td>";
    $strResult .= $newLine . "          </tr>";
    $strResult .= $newLine . "        </table>";
    return $strResult;
  }

  function printWindowCaption()
  {
    $newLine = "\n" . $this->blankSpace;
    $strResult = "";
    if ($this->showCaption) {
      $strResult .= $newLine . "  <tr valign=\"top\" height=\"21\">";
      $strResult .= $newLine . "    <td width=\"22\" class=\"formBoxTopLeft\" onClick=\"javascript:openHelp()\">&nbsp;</td>";
      $strResult .= $newLine . "    <td class=\"formBoxTitle\" valign=\"top\">";
      $strResult .= $newLine . "      <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
      $strResult .= $newLine . "        <tr>";
      $strResult .= $newLine . "          <td class=\"formBoxCaption\" valign=\"top\">";
      $strResult .= $newLine . "            " . $this->caption;
      $strResult .= $newLine . "          </td>";
      if ($this->showMinimizeButton) {
        $strResult .= $newLine . "          <td class=\"formBoxMinimizeButton\" onClick=\"javascript:my" . $this->formName . ".doMinimize(this)\">";
        $strResult .= $newLine . "            &nbsp;";
        $strResult .= $newLine . "          </td>";
      }
      if ($this->showCloseButton) {
        $strResult .= $newLine . "          <td class=\"formBoxCloseButton\" onClick=\"javascript:my" . $this->formName . ".doClose(this)\">";
        $strResult .= $newLine . "            &nbsp;";
        $strResult .= $newLine . "          </td>";
      }
      $strResult .= $newLine . "        </tr>";
      $strResult .= $newLine . "      </table>";
      $strResult .= $newLine . "    </td>";
      $strResult .= $newLine . "  </tr>";
    }
    return $strResult;
  }

  function readOnlyForm()
  {
    $this->readonly = true;
  }

  function render()
  {
    $strResult = "";
    $newLine = "\n" . $this->blankSpace;
    //----------print CSS filename if exist
    if ($this->CSSFileName != "") {
      $strResult .= $newLine . "<link href=\"" . $this->CSSFileName . "\" rel=\"stylesheet\" type=\"text/css\">";
    }
    //----------end of print CSS
    //print Javascript
    $strResult .= $newLine . "<script type=\"text/javascript\" src=\"" . $this->JSFileName . "\"></script>";
    $strResult .= $this->printJSInit();
    //end of print Javascript
    $tableWidth = ($this->width == "") ? "" : "width=\"" . $this->width . "\" ";
    $tableHeight = ($this->height == "" || $this->showMinimizeButton) ? "" : "height=\"" . $this->height . "\" ";
    $strResult .= $newLine . "<table id=\"table_" . $this->formName . "\" class=\"formBox\" " . $tableWidth . $tableHeight . "border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
    $strResult .= $this->printWindowCaption();
    $strResult .= $newLine . "  <tr valign=\"top\" id=\"row_" . $this->formName . "\">";
    $strResult .= $newLine . "    <td class=\"formBoxLeft\">&nbsp;</td>";
    $strResult .= $newLine . "    <td class=\"formBoxContent\" valign=\"top\">";
    //print form tag, if $hasFormTag set to true
    $strResult .= $this->printFormTag();
    //print message if any
    if ($this->message != '') {
      $strResult .= $newLine . "<div id=\"formMessage\">" . $this->message . "</div>";
    } else // don't show message, but still print the reserved DIV
    {
      $strResult .= $newLine . "<div id=\"formMessage\" style=\"visibility:hidden\">&nbsp;</div>";
    }
    //mencetak object object textbox, select, textarea, checkbox, dsb
    $strResult .= $this->printObjects();
    //print closing form tag, if $hasFormTag set to true
    if ($this->hasFormTag) {
      $strResult .= $newLine . "      </form>";
    }
    $strResult .= $newLine . "    <td>";
    $strResult .= $newLine . "  </tr>";
    $strResult .= $newLine . "</table>";
    if ($this->showCaption) {
      $strResult .= $this->writeWindowHelp();
    }
    //for focusing default control and make select/combo to select index -1 not index 0
    $strResult .= $this->PrintJSOnload();
    //write information
    $strResult = $this->blankSpace . "<!-- START OF FORM ENTRY, generated by FormEntry Class, by Dedy Sukandar -->" . $strResult .
        $newLine . "<!-- END OF FORMENTRY, generated by FormEntry Class, by Dedy Sukandar -->";
    return $strResult;
  }

  function renderContent()
  {
    //this function will render content only
    //useful in PrintMode Page
    //removing border and top title/caption
    //--------------------------------------------
    $strResult = "";
    $newLine = "\n" . $this->blankSpace;
    $strResult .= $this->blankSpace . "<!-- START OF FORM ENTRY, generated by FormEntry Class, by Dedy Sukandar -->";
    //----------render CSS filename if exist
    if ($this->CSSFileName != "") {
      $strResult .= $newLine . "<link href=\"" . $this->CSSFileName . "\" rel=\"stylesheet\" type=\"text/css\">";
    }
    //----------end of render CSS
    $tableWidth = ($this->width == "") ? "" : "width=\"" . $this->width . "\" ";
    $tableHeight = ($this->height == "") ? "" : "height=\"" . $this->height . "\" ";
    $strResult .= $newLine . "<table id=\"table_" . $this->formName . "\" " . $tableWidth . $tableHeight . "border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
    $strResult .= $newLine . "  <tr valign=\"top\" id=\"row_" . $this->formName . "\">";
    $strResult .= $newLine . "    <td valign=\"top\">";
    if ($this->hasFormTag) {
      //----------render tag form
      $strResult .= $newLine . "      <form name=\"" . $this->formName . "\" id=\"" . $this->formName . "\" method=\"post\" action=\"" . $_SERVER['PHP_SELF'] . "\"";
      if (!$this->autocomplete) {
        $strResult .= " autocomplete=\"off\"";
      }
      $strResult .= ">";
      //----------end of render tag form
    }
    //----------render input  hidden (yang ditambahkan lewat fungsi addHidden) persis dibawah tag form
    foreach ($this->formHidden as $value) {
      $strResult .= $newLine . "        " . $this->drawHidden($value);
    }
    //----------end of render tag input hidden
    $strResult .= $newLine . "        <table border=\"0\" cellpadding=\"1\" cellspacing=\"0\" width=\"100%\">";
    $strResult .= $newLine . "          <tr valign=\"top\">";
    //----------hitung jumlah form objects
    $jmlBaris = ceil(count($this->formObject) / $this->colCount);
    //----------hitung percentage dari kolom untuk tampilan multi kolom
    $percentWidth = round(100 / $this->colCount, 1);
    //render object2 ke html berdasarkan jumlah kolom
    //----------object yang tidak visible lompati saja jangan di-render.
    for ($i = 0; $i < $this->colCount; $i++) {
      $strResult .= $newLine . "            <td width=\"" . $percentWidth . "%\" style=\"padding-left:5px; padding-right:5px\">";
      $strResult .= $newLine . "              <table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\">";
      for ($j = $i * $jmlBaris; ($j < ($i + 1) * $jmlBaris && $j < count($this->formObject)); $j++) {
        $strResult .= $newLine . "                <tr valign=\"top\">";
        $strResult .= $newLine . "                  <td nowrap>" . $this->drawObjectCaption(
                $this->formObject[$j]
            ) . "</td>";
        $strResult .= $newLine . "                  <td width=\"10\">:</td>";
        $strResult .= $newLine . "                  <td>" . $this->drawObject($this->formObject[$j]) . "</td>";
        $strResult .= $newLine . "                </tr>";
      }
      $strResult .= $newLine . "              </table>";
      $strResult .= $newLine . "            </td>";
    }
    $strResult .= $newLine . "          </tr>";
    $strResult .= $newLine . "          <tr>";
    if ($this->colCount > 1) {
      $strResult .= $newLine . "            <td height=\"30\" valign=\"middle\" colspan=\"" . $this->colCount . "\">";
    } else {
      $strResult .= $newLine . "            <td height=\"30\" valign=\"middle\">";
    }
    //----------render all button object
    foreach ($this->formButton as $value) {
      $strResult .= $this->drawObject($value) . "&nbsp;";
    }
    //----------end of render button
    $strResult .= "</td>";
    $strResult .= $newLine . "          </tr>";
    $strResult .= $newLine . "        </table>";
    //render closing form tag
    if ($this->hasFormTag) {
      $strResult .= $newLine . "      </form>";
    }
    $strResult .= $newLine . "    <td>";
    $strResult .= $newLine . "  </tr>";
    $strResult .= $newLine . "</table>";
    $strResult .= $this->PrintJSOnload();
    $strResult .= $newLine . "<!-- END OF FORMENTRY, generated by FormEntry Class, by Dedy Sukandar -->";
    return $strResult;
  }

  function resetBeforeRender()
  {
    $this->resetData = true;
  }

  function serializeAttribute($arrAttribute)
  {
    $strAttribute = "";
    if ($arrAttribute != null && is_array($arrAttribute)) {
      foreach ($arrAttribute as $attribKey => $attribValue) {
        if ($attribValue == "") {
          $strAttribute .= $attribKey . " ";
        } else {
          $strAttribute .= $attribKey . "=" . $attribValue . " ";
        }
      }
    }
    return $strAttribute;
  }

  function setCSSfile($filename)
  {
    if (file_exists($filename)) {
      $this->CSSFileName = $filename;
    } else {
      $this->CSSFileName = "";
    }
  }

  function setFormAction($action)
  {
    $this->action = $action;
  }

  //use for AJAX, under development, not yet tested
  //to render CONTENT ONLY because AJAX doesn't need the other

  function setFormTarget($target)
  {
    $this->target = $target;
  }

  //this function will get last $_POST and action that call before

  function setJSfile($filename)
  {
    if (file_exists($filename)) {
      $this->JSFileName = $filename;
    } else {
      $this->JSFileName = "form.js";
    }
  }

  function setPermission($bolCanView = false, $bolCanDelete = false, $bolCanEdit = false)
  {
    $this->bolCanView = $bolCanView;
    $this->bolCanDelete = $bolCanDelete;
    $this->bolCanEdit = $bolCanEdit;
  }

  function setValue($name, $val)
  {
    $this->objects[$name]['value'] = $val;
  }

  function uploadFile($objID, $targetFolder)
  {
    $tmpName = $_FILES[$objID]['tmp_name'];
    $fileName = basename($_FILES[$objID]['name']);
    if (is_uploaded_file($tmpName)) {
      if ($targetFolder != "" && $targetFolder != "./" && $targetFolder != ".") {
        if (!is_dir($targetFolder)) {
          mkdir($targetFolder, 0755);
        }
      } else {
        $targetFolder = "./";
      }
      $targetFolder = ereg_replace('/+', '/', $targetFolder . "/");
      $strFullFileName = $targetFolder . $fileName;
      if (file_exists($strFullFileName)) {
        //delete first if exist
        unlink($strFullFileName);
      }
      if (move_uploaded_file($tmpName, $strFullFileName)) {
        if (DEFINED('DELETE_OLD_FILE') && DELETE_OLD_FILE == 1) {
          //remove old file if different name
          $oldFile = $this->formHidden[$objID]['value'];
          $oldFile = $targetFolder . $oldFile;
          if (file_exists($oldFile) && $this->formHidden[$objID]['value'] != '') {
            @unlink($oldFile);
          }
        }
        $this->formHidden[$objID]['value'] = $fileName;
        $this->objects[$objID]['value'] = $fileName;
        $this->objects[$objID]['readonly'] = true;
        return true;
      }
    }
    return false;
  }

  function value($name)
  {
    return $this->getValue($name);
  }

  function writeWindowHelp()
  {
    global $CLASSFORMPATH;
    $newLine = "\n" . $this->blankSpace;
    $strResult = $newLine . "<div id=\"windowHelp_" . $this->formName . "\" class=\"window\" style=\"left:" . $this->leftWindowHelp . "px;top:" . $this->topWindowHelp . "px;width:" . $this->widthWindowHelp . "px\">";
    $strResult .= $newLine . "  <div class=\"titleBar\">";
    $strResult .= $newLine . "    <table class=\"tableTitleBar\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
    $strResult .= $newLine . "      <tr>";
    $strResult .= $newLine . "        <td class=\"titleBarLeft\" width=6>&nbsp;</td>";
    if ($this->helpIcon != "") {
      $strResult .= $newLine . "        <td class=\"titleBarIcon\" width=\"16\"><img src=\"" . $this->helpIcon . "\" border=\"0\" width=\"16\" height=\"16\"></td>";
    }
    $strResult .= $newLine . "        <td class=\"titleBarText\" width=\"100%\">" . $this->helpCaption . "</td>";
    $strResult .= $newLine . "        <td class=\"titleBarButtonsRow\" width=\"52\" nowrap>";
    $imagePath = ereg_replace('/+', '/', $CLASSFORMPATH . "/images/");
    $minOff = $imagePath . "form_minimizeoff.gif";
    $minOn = $imagePath . "form_minimizeon.gif";
    $maxOff = $imagePath . "form_maximizeoff.gif";
    $maxOn = $imagePath . "form_maximizeon.gif";
    $closeOff = $imagePath . "form_closeoff.gif";
    $closeOn = $imagePath . "form_closeon.gif";
    $strResult .= $newLine . "          <img class=\"topButton\" src=\"" . $minOff . "\" border=\"0\" width=\"16\" height=\"16\" onclick=\"this.parentWindow.minimize();return false;\" onMouseOver=\"this.src='" . $minOn . "'\" onMouseOut=\"this.src='" . $minOff . "'\"><img class=\"topButton\" src=\"" . $maxOff . "\" border=\"0\" width=\"16\" height=\"16\" onclick=\"this.parentWindow.restore();return false;\" onMouseOver=\"this.src='" . $maxOn . "'\" onMouseOut=\"this.src='" . $maxOff . "'\"><img src=\"" . $closeOff . "\" border=\"0\" width=\"16\" height=\"16\" onclick=\"this.parentWindow.close();return false;\" onMouseOver=\"this.src='" . $closeOn . "'\" onMouseOut=\"this.src='" . $closeOff . "'\">";
    $strResult .= $newLine . "        </td>";
    $strResult .= $newLine . "        <td class=\"titleBarRight\" width=6>&nbsp;</td>";
    $strResult .= $newLine . "      </tr>";
    $strResult .= $newLine . "    </table>";
    $strResult .= $newLine . "  </div>";
    $strResult .= $newLine . "  <div class=\"clientArea\" style=\"height:" . (int)($this->heightWindowHelp - 25) . "px;\">";
    $strResult .= $newLine . "    " . $this->help;
    $strResult .= $newLine . "  </div>";
    $strResult .= $newLine . "</div>";
    $strResult .= $newLine . "<script language=\"javascript\">";
    $strResult .= $newLine . "  winInit();";
    $strResult .= $newLine . "  function openHelp(val)";
    $strResult .= $newLine . "  {";
    $strResult .= $newLine . "    if (winList['windowHelp_" . $this->formName . "'])";
    $strResult .= $newLine . "    {";
    $strResult .= $newLine . "      var myWin = winList['windowHelp_" . $this->formName . "'];";
    $strResult .= $newLine . "      if (myWin.isOpen) myWin.close(); else myWin.open();";
    $strResult .= $newLine . "    }";
    $strResult .= $newLine . "  }";
    $strResult .= $newLine . "</script>";
    return $strResult;
  }
}

?>