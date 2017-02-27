<?php
/*
   Dedy's class form Entry
   version 2.0
   PT. Invosa Systems
   All right reserved.
*/
require_once("form2.config.php");

class clsForm
{

  var $CSSFileName;

  var $CSSPath;

  var $JSFileName;

  var $JSPath;

  var $action = "";

  //if set true, the form Object will be validated with Javascript to check is it valid input or not
  //only the form Object with validate = true, will be validate

  var $autocomplete = true;

  var $blankIcon;

  var $bolRequiredEntryBeforeSubmit = true;

  var $colCount;

  var $confirmMessage = '';

  var $defaultSelectedIndex = -1;

  var $editMode = false;

  var $enabled = true;

  var $fieldSets;

  var $formAJAXsubmitted = false;

  var $formAutoComplete;

  var $formButton;

  //set CSS file name

  var $formHidden;

  var $formName;

  var $formObject;

  var $groupingNumber;

  var $hasAutoComplete = false;

  //set caption of the form

  var $hasButton = true;

  var $hasFile = false;

  //set help context here

  var $hasFormTag = true;

  var $hasRequiredControl = false;

  var $hasTabPage = false;

  var $height;

  var $heightWindowHelp = "225";

  var $help = "No help provided in this form";

  var $helpCaption = "Help";

  var $helpIcon;

  var $leftWindowHelp = "20";

  var $message = "";

  var $msgClass = "bgOK";

  var $objects;

  var $readonly = false;

  var $resetData = false;

  var $scriptFileName = "";

  var $showCaption = true;

  var $showCloseButton = false;

  var $showHelpButton = false;

  var $showMinimizeButton = true;

  var $tabPageNumber;

  var $target = "";

  var $title = "INPUT DATA";

  var $topWindowHelp = "157";

  var $useAJAXTechnology = false;

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
    $this->tabPageNumber = -1;
    $this->fieldSets = [];
    $this->formObject = [];
    $this->formAutoComplete = [];
    $this->form = $this->formObject;
    $this->formHidden = [];
    $this->formButton = [];
    if ($path != "") {
      $CLASSFORMPATH = $path;
    }
    if (!isset($FORM_CSS)) {
      $FORM_CSS = "form";
    }
    $this->JSPath = ereg_replace('/+', '/', $CLASSFORMPATH . "/scripts/");
    $this->CSSPath = ereg_replace('/+', '/', $CLASSFORMPATH . "/css/");
    $this->CSSFileName = $this->CSSPath . $FORM_CSS . ".css";
    $this->JSFileName = $this->JSPath . "form.js";
    $this->helpIcon = ereg_replace('/+', '/', $CLASSFORMPATH . "/css/" . $FORM_CSS . "/help.gif");
    $this->blankIcon = ereg_replace('/+', '/', $CLASSFORMPATH . "/css/" . $FORM_CSS . "/blank.gif");
  }

  //jika fungsi ini dipanggil maka datagrid tidak akan me-render tag <form....
  //pastikan jika anda memanggil fungsi ini, anda telah menyiapkan tag <form anda sendiri
  //kalo tidak semua fungsi sort, search, jump page, dll tidak berfungsi.

  function _addCommonButton(
      $buttonType,
      $name,
      $value,
      $arrAttribute,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $serverAction = ""
  ) {
    $arrData = [
        "type"         => $buttonType,
        "enabled"      => $bolEnabled,
        "visible"      => $bolVisible,
        "name"         => $name,
        "value"        => $value,
        "clicked"      => false,
        "attribute"    => $this->_serializeAttribute($arrAttribute),
        "htmlBefore"   => $htmlBefore,
        "htmlAfter"    => $htmlAfter,
        "required"     => false,
        "serverAction" => $serverAction
    ];
    $this->formButton[] = &$arrData;
    $this->objects[$name] = &$arrData;
  }

  function _drawHidden($obj)
  {
    $strResult = "<input type=\"hidden\" name=\"" . $obj['name'] . "\" id=\"" . $obj['name'] . "\" value=\"" . $obj['value'] . "\"";
    if (!$obj['enabled']) {
      $strResult .= " disabled";
    }
    $strResult .= ">";
    return $strResult;
  }

  function _escapeString($str)
  {
    return str_replace("'", "\'", $str);
  }

  function _formatAttribute($key, $value)
  {
    $attribute = '';
    $attributeFormat = '%s="%s"';
    $minimizedAttributes = [
        'compact',
        'checked',
        'declare',
        'readonly',
        'disabled',
        'selected',
        'defer',
        'ismap',
        'nohref',
        'noshade',
        'nowrap',
        'multiple',
        'noresize'
    ];
    if (in_array($key, $minimizedAttributes)) {
      if ($value === 1 || $value === true || $value === 'true' || $value == $key) {
        $attribute = sprintf($attributeFormat, $key, $key);
      }
    } else {
      $attribute = sprintf($attributeFormat, $key, $value);
    }
    return $attribute;
  }

  function _formatter($par)
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
      $_formatter = substr($par, 0, $size);
      if (strstr($_formatter, '->')) {
        $_formatter = explode('->', $_formatter);
      } elseif (strstr($_formatter, '::')) {
        $_formatter = explode('::', $_formatter);
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
      $_formatter = $par;
    }
    // Call the _formatter
    if (is_callable($_formatter)) {
      call_user_func($_formatter, $paramList);
    } else {
      $this->message = "
      function " . $_formatter . " does not exist in the server-side code,
      <br />
      please contact SOFTWARE VENDOR to fixed this problem";
      return false;
    }
    return true;
  }

  function _genSelectHour($name, $default = 0)
  {
    $strResult = "";
    $strResult .= "<select name='$name' id='$name'>";
    for ($i = 0; $i < 24; $i++) {
      if ($i < 10) {
        $value = "0" . $i;
      } else {
        $value = $i;
      }
      if ($default == $i) {
        $strResult .= "<option value='$value' selected>" . $value . "</option>";
      } else {
        $strResult .= "<option value='$value'>" . $value . "</option>";
      }
    }
    $strResult .= "</select>";
    return $strResult;
  }

  function _genSelectMinSec($name, $default = 0)
  {
    $strResult = "";
    $strResult .= "<select name='$name' id='$name'>";
    for ($i = 0; $i < 60; $i++) {
      if ($i < 10) {
        $value = "0" . $i;
      } else {
        $value = $i;
      }
      if ($default == $i) {
        $strResult .= "<option value='$value' selected>" . $value . "</option>";
      } else {
        $strResult .= "<option value='$value'>" . $value . "</option>";
      }
    }
    $strResult .= "</select>";
    return $strResult;
  }

  function _generateAutoCompleteCanonData($arrData, &$strAllCanons, &$strNickTokens, &$selectedValue, &$selectedText)
  {
    $strAllCanons = "";
    $strNickTokens = "";
    $i = 0;
    $selectedValue = "";
    $selectedText = "";
    foreach ($arrData as $key => $value) {
      $strAllCanons .= "\"" . htmlspecialchars($value["value"], ENT_COMPAT) . "\", ";
      $strAllCanons .= "\"" . htmlspecialchars($value["text"], ENT_COMPAT) . "\", ";
      $strNickTokens .= "[\"" . htmlspecialchars($value["value"], ENT_COMPAT) . "," . ($i * 2) . "\"], ";
      if (isset($value['selected']) && $value['selected'] === true) {
        $selectedValue = $value['value'];
        $selectedText = $value['text'];
      }
      $i++;
    }
    $strAllCanons = "[" . $strAllCanons . "]";
    $strNickTokens = "[" . $strNickTokens . "]";
  }

  function _getLastURL()
  {
    if (isset($_POST['hidden_URL_REFERER'])) {
      $URL_REFERER = $_POST['hidden_URL_REFERER'];
    } else if (isset($_SERVER['HTTP_REFERER'])) {
      $URL_REFERER = $_SERVER['HTTP_REFERER'];
    } else {
      $URL_REFERER = $_SERVER['PHP_SELF'];
    }
    return $URL_REFERER;
  }

  //This function is to create input type=text
  //Parameters:
  //  - $title : string label / title
  //  - $name : string input name & id
  //  - $value : string input value
  //  - $arrAttribute : array html attribute of input e.g: style, class, etc
  //  - $dataType : see available datatype in form2.config.php
  //  - $bolRequired : boolean, true is required, false otherwise
  //  - $bolEnabled : boolean, true if enable, false otherwise
  //  - $bolVisible : boolean, true if visible, false otherwise

  function _getRequest()
  {
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
            if (isset($_POST[$obj['name']])) {
              $obj['value'] = $_POST[$obj['name']];
              $arrValues = $obj['values'];
              while (list($key2, $value) = each($arrValues)) {
                $val = &$obj['values'][$key2];
                if ($val['value'] == $_POST[$obj['name']]) {
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
              $arrValues = $obj['values'];
              while (list($key2, $value) = each($arrValues)) {
                $val = &$obj['values'][$key2];
                if ($val['value'] == $_POST[$obj['name']]) {
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
            } else {
              if (isset($_POST["hidden_" . $obj['name']])) {
                $obj['value'] = false;
              }
            }
            break;
          case 'hidden' :
            if (isset($_POST[$obj['name']])) {
              $obj['value'] = $_POST[$obj['name']];
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
            if (isset($_POST[$obj['name']])) {
              $obj['value'] = $_POST[$obj['name']];
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
          if (isset($_POST[$obj['name']])) {
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
    if (isset($_GET['ajaxForm']) && $_GET['ajaxForm'] == $this->formName) {
      $this->formAJAXsubmitted = true;
    } else {
      $this->formAJAXsubmitted = false;
    }
  }

  //This function is to create span/label
  //Parameters:
  //  - $title : string label / title
  //  - $name : string input name & id
  //  - $value : string input value
  //  - $arrAttribute : array html attribute of input e.g: style, class, etc
  //  - $dataType : see available datatype in form2.config.php
  //  - $bolRequired : boolean, true is required, false otherwise
  //  - $bolVisible : boolean, true if visible, false otherwise

  function _printFormTag()
  {
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
      $strResult .= "
        <form name=\"" . $this->formName . "\" id=\"" . $this->formName . "\" method=\"post\"" . $strTarget . $strAction . $encType;
      if ($this->bolRequiredEntryBeforeSubmit) {
        $strResult .= " onsubmit=\"return my" . $this->formName . ".doSubmit('" . $this->confirmMessage . "');\"";
      }
      if (!$this->autocomplete) {
        $strResult .= " autocomplete=\"off\"";
      }
      $strResult .= ">";
      //----------end of render tag form
    }
    //----------render input  hidden (yang ditambahkan lewat fungsi addHidden) persis dibawah tag form
    foreach ($this->formHidden as $value) {
      $strResult .= "\n          " . $this->_drawHidden($value);
    }
    //----------end of render tag input hidden
    return $strResult;
  }

  function _printHidden($name, $value)
  {
    $strResult = "<input type=\"hidden\" name=\"" . $name . "\" id=\"" . $name . "\" value=\"" . $value . "\" />";
    return $strResult;
  }

  function _printJSInit()
  {
    global $validatorDataType;
    global $validatorErrorMessage;
    $newLine = "\n  ";
    //fungsi javascript ini adalah untuk mengecek validitas input dari form ketika form di submit ke server
    $strResult = $newLine . "<script type=\"text/javascript\">";
    $strResult .= $newLine . "  var my" . $this->formName . " = {";
    $strResult .= $newLine . "    lastSubmittedButton : null,";
    if ($this->useAJAXTechnology) {
      $strResult .= $newLine . "    useAJAXsubmit : true,";
    } else {
      $strResult .= $newLine . "    useAJAXsubmit : false,";
    }
    $strResult .= $newLine . "    submitCount : 0,";
    $strResult .= $newLine . "    doSubmit : function(confirmMessage) {";
    $strResult .= $newLine . "      var f = document." . $this->formName . ";";
    $strResult .= $newLine . "      if (this.submitCount != 0) return false;";
    $strResultTime = "";
    if (!$this->readonly) {
      for ($i = 0; $i <= $this->tabPageNumber; $i++) {
        foreach ($this->formObject[$i] as $objects) {
          foreach ($objects as $key => $obj) {
            switch ($obj['type']) {
              case 'autocomplete':
              case 'textarea':
              case 'password':
              case 'text':
              case 'select':
                if ($obj['required'] == true) {
                  if (isset($validatorDataType[$obj['dataType']])) {
                    $strResult .= $newLine . "      else if (!" . $validatorDataType[$obj['dataType']] . "(f." . $obj['name'] . ".value)) {";
                    $strResult .= $newLine . "        alert('" . addslashes(
                            $validatorErrorMessage[$obj['dataType']] . $obj['title']
                        ) . "!');";
                    $strResult .= $newLine . "        f." . $obj['name'] . ".focus();";
                    if ($obj['type'] == 'text' || $obj['type'] == 'password') {
                      $strResult .= $newLine . "        if (f." . $obj['name'] . ".value != '') f." . $obj['name'] . ".select();";
                    }
                    $strResult .= $newLine . "      }";
                  } else if ($obj['dataType'] == "time") {
                    $strResult .= $newLine . "      else if (" . "f." . $obj['name'] . "_hour.value == '') {";
                    $strResult .= $newLine . "        alert('Please select hour!');";
                    $strResult .= $newLine . "        f." . $obj['name'] . "_hour.focus();";
                    $strResult .= $newLine . "      }";
                    $strResult .= $newLine . "      else if (" . "f." . $obj['name'] . "_minute.value == '') {";
                    $strResult .= $newLine . "        alert('Please select minute!');";
                    $strResult .= $newLine . "        f." . $obj['name'] . "_minute.focus();";
                    $strResult .= $newLine . "      }";
                    $strResult .= $newLine . "      else if (" . "f." . $obj['name'] . "_second.value == '') {";
                    $strResult .= $newLine . "        alert('Please select second!');";
                    $strResult .= $newLine . "        f." . $obj['name'] . "_second.focus();";
                    $strResult .= $newLine . "      }";
                  }
                }
                if ($obj['dataType'] == "time") {
                  $strResultTime .= $newLine . "      f." . $obj['name'] . ".value = f." . $obj['name'] . "_hour.value + ':' + f." . $obj['name'] . "_minute.value + ':' + f." . $obj['name'] . "_second.value ;";
                }
                break;
            }
          }
        }
      }
    }
    $strResult .= $newLine . "      else {";
    $strResult .= $newLine . "        if ((confirmMessage != '') && (confirmMessage!=null))";
    $strResult .= $newLine . "          if (!confirm(confirmMessage)) return false;";
    $strResult .= $strResultTime;
    //TODO: maintain this submitCount, because submitCount will remain 0 if you submit back to server, but not refreshing the page (e.g:  to view PDF file (attachment))
    //I commented this for good
    //$strResult .= $newLine."      submitCount++;";
    if ($this->useAJAXTechnology) {
      $strResult .= $newLine . "        return this.submitToServer();";
      //$strResult .= $newLine."        false;";
    } else {
      $strResult .= $newLine . "        return true;";
    }
    $strResult .= $newLine . "      }";
    $strResult .= $newLine . "      return false;";
    $strResult .= $newLine . "    },";
    if ($this->useAJAXTechnology) {
      $strResult .= $this->_printSubmitToServerJSAJAX();
    }
    $strResult .= $newLine . "    doMinimize : function(obj) {";
    $strResult .= $newLine . "      if ((obj.className == 'minimize') || (obj.className == 'minimizeHover')) {";
    $strResult .= $newLine . "        obj.className = 'maximizeHover';";
    $strResult .= $newLine . "        document.getElementById(\"row_" . $this->formName . "\").style.display = \"none\"";
    $strResult .= $newLine . "      }";
    $strResult .= $newLine . "      else {";
    $strResult .= $newLine . "        obj.className = 'minimizeHover';";
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

  function _printJSOnload()
  {
    $newLine = "\n  ";
    $strResult = $newLine . "<script type=\"text/javascript\">";
    $strResult .= $newLine . "  document.getElementById('table_" . $this->formName . "').style.display = '';";
    if ($this->hasAutoComplete) {
      $counter = 0;
      foreach ($this->formAutoComplete as $key => $value) {
        $strResult .= $newLine . "  //initialize auto-complete variable";
        $strResult .= $newLine . "  AC_nickTokens[$counter] = " . $value['nick'] . ";";
        $strResult .= $newLine . "  AC_allCanons[$counter] = " . $value['canon'] . ";";
        $strResult .= $newLine . "  AC_targetElements[$counter] = ['" . $key . "'];";
        $strResult .= $newLine . "  AC_descriptionElements[$counter] = ['" . $value['label'] . "'];";
        $counter++;
      }
    }
    if (count($this->formObject) > 0 && !$this->readonly) {
      $strResult .= $newLine . "  var f = document." . $this->formName . ";";
      $isFirst = true;
      foreach ($this->objects as $value) {
        if (!isset($value['type'])) {
          continue;
        }
        if ($value['type'] == 'select' || $value['type'] == 'text' || $value['type'] == 'password' || $value['type'] == 'textarea' || $value['type'] == 'autocomplete') {
          if ($value['dataType'] == 'numeric') {
            $strResult .= $newLine . "  maskEdit($('" . $value['name'] . "'), editKeyBoardNumeric);";
          } else if ($value['dataType'] == 'integer') {
            $strResult .= $newLine . "  maskEdit($('" . $value['name'] . "'), editKeyBoardInteger);";
          }
          if ($isFirst) {
            if ($value['visible'] && !$value['readonly'] && $value['enabled']) {
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
              $isFirst = false;//break;
            }
          }
        }
      }
      foreach ($this->formObject as $arrPagesObject) {
        foreach ($arrPagesObject as $arrObject) {
          foreach ($arrObject as $value) {
            if ($value['dataType'] == 'date' && $value['type'] == 'text') {
              //----------render calendar if any
              $strCalendarUpdate = "";
              if ($value['calendarUpdate'] != null) {
                $strCalendarUpdate = ", onUpdate:" . $value['calendarUpdate'];
              }
              $strResult .= $newLine . "  Calendar.setup({ inputField:\"" . $value['name'] . "\", button:\"btn" . $value['name'] . "\", ifFormat:\"" . $GLOBALS['FORM_DATE_FORMAT'] . "\", daFormat:\"" . $GLOBALS['FORM_DATE_FORMAT'] . "\" $strCalendarUpdate});";
              //----------end of render calendar
            }
          }
        }
      }
      foreach ($this->formObject as $arrPageObject) {
        foreach ($arrPageObject as $arrObject) {
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
                  $strResult .= $newLine . "  f." . $obj['name'] . ".selectedIndex = " . $this->defaultSelectedIndex . ";";
                  $strResult .= $newLine . "  f." . $obj['name'] . ".value = \"\";";
                }
                break;
            }
        }
      }
    }
    if ($this->useAJAXTechnology) {
      $strResult .= $newLine . "  my" . $this->formName . ".setEventClickSubmitButton();";
    }
    if ($this->hasAutoComplete) {
      $strResult .= $newLine . "  initAutoComplete();";
    }
    $strResult .= $newLine . "</script>";
    return $strResult;
  }

  function _printObject($obj)
  {
    $strResult = "";
    switch ($obj['type']) {
      case 'select' :
        if ($obj['visible']) {
          if ($this->readonly) {
            $selText = "";
            $selVal = "";
            //print_r($obj['values'] );
            foreach ($obj['values'] as $opsi) {
              if ($opsi['selected'] && !$this->resetData) {
                $selText = $opsi['text'];
                $selVal = $opsi['value'];
                break;
              }
            }
            $strResult = $this->_printSpan("span_" . $obj['name'], $selText, null, $obj['htmlBefore'], "");
            $strResult .= $this->_printHidden($obj['name'], $selVal);
          } else {
            $strResult = $obj['htmlBefore'] . "<select id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" " . $obj['attribute'];
            if (!$obj['enabled']) {
              $strResult .= " disabled";
            }
            $strResult .= ">";
            $isSelected = false;
            foreach ($obj['values'] as $key => $opsi) {
              $strResult .= "<option";
              if (isset($opsi['id']) && $opsi['id'] != '') {
                $strResult .= " id=" . $opsi['id'];
              } else {
                $strResult .= " id=" . $opsi['value'];
              }
              $strResult .= " value='" . $this->_escapeString($opsi['value']) . "'";
              if (!$isSelected && $opsi['selected'] && !$this->resetData) {
                $strResult .= " selected";
                $isSelected = true;
              }
              $strResult .= ">" . $opsi['text'] . "</option>";
            }
            $strResult .= "</select>";
          }
        }
        break;
      case 'radio' :
        if ($obj['visible']) {
          if ($this->readonly) {
            $strResult = $this->_printSpan("span_" . $obj['name'], $obj['text'], null, $obj['htmlBefore'], "");
            $strResult .= $this->_printHidden($obj['name'], $obj['value']);
          } else {
            $strPreResult = "<input class=r type=\"radio\" id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" " . $obj['attribute'];
            if (!$obj['enabled']) {
              $strPreResult .= " disabled";
            }
            $isChecked = false;
            $strResult = "";
            foreach ($obj['values'] as $key => $opsi) {
              $strResult .= $strPreResult . " value='" . $this->_escapeString($opsi['value']) . "'";
              if (!$isChecked && $opsi['checked'] && !$this->resetData) {
                $strResult .= " checked";
                $isChecked = true;
              }
              $strResult .= ">" . $opsi['text'];
              //untuk mode penampilan, jika tidak 0 maka vertical, default horizontal
              if ($obj['verticalLayout']) {
                $strResult .= "<br />\n";
              } else {
                $strResult .= "&nbsp;\n";
              }
            }
          }
        }
        break;
      case 'reset' :
      case 'submit' :
      case 'button' :
        if ($obj['visible']) {
          $strResult = $obj['htmlBefore'] . "<input type=\"" . $obj['type'] . "\" class=button id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" value=\"" . $obj['value'] . "\" " . $obj['attribute'];
          if (!$obj['enabled']) {
            $strResult .= " disabled";
          } else {
            $strResult .= " onMouseOver=\"this.className = 'buttonHover'\" ";
            $strResult .= " onMouseOut=\"this.className = 'button'\" ";
          }
          $strResult .= ">";
        }
        break;
      case 'label':
      case 'labelautocomplete':
        if ($obj['visible']) {
          if ($this->readonly) {
            $strResult = $this->_printSpan($obj['name'], $obj['value'], null, $obj['htmlBefore'], "");
          } else {
            $strResult = $obj['htmlBefore'] . "<span id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" ";
            if ($obj['attribute'] != '') {
              $strResult .= " " . $obj['attribute'];
            } else {
              $strResult .= " ";
            }// style='padding-top:2px; padding-bottom:2px' " ;
            $strResult .= ">" . nl2br($obj['value']) . "&nbsp;</span>";//.$obj['htmlAfter'];
            $strResult .= "<input type=\"hidden\" name=\"" . $obj['name'] . "\" value=\"" . $obj['value'] . "\"";
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
            $strResult = $this->_printSpan("span_" . $obj['name'], $obj['value'], null, $obj['htmlBefore'], "");
            $strResult .= $this->_printHidden($obj['name'], $obj['value']);
          } else {
            $strResult = $obj['htmlBefore'] . "<textarea class=t id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" ";
            //replace size to cols, and maxlength to rows in textarea type
            if ($obj['attribute'] != '') {
              $strResult .= " " . $obj['attribute'];
            }
            if (!$obj['enabled']) {
              $strResult .= " disabled";
            }
            $strResult .= ">";
            if (!$this->resetData) {
              $strResult .= $obj['value'];
            }
            $strResult .= "</textarea>";
          }
        }
        break;
      case 'file' :
      case 'text' :
      case 'password' :
      case 'autocomplete' :
        if ($obj['visible']) {
          if ($this->readonly) {
            $strResult = $this->_printSpan("span_" . $obj['name'], $obj['value'], null, $obj['htmlBefore'], "");
            $strResult .= $this->_printHidden($obj['name'], $obj['value']);
          } else {
            if ($obj['dataType'] == "time") {
              //draw object time
              $arrData = explode(":", $obj['value']);
              if (!isset($arrData[0])) {
                $arrData[0] = "";
              } else {
                $arrData[0] = intval($arrData[0]);
              }
              if (!isset($arrData[1])) {
                $arrData[1] = "";
              } else {
                $arrData[1] = intval($arrData[1]);
              }
              if (!isset($arrData[2])) {
                $arrData[2] = "";
              } else {
                $arrData[2] = intval($arrData[2]);
              }
              $strResult .= $this->_genSelectHour($obj['name'] . "_hour", $arrData[0]);
              $strResult .= $this->_genSelectMinSec($obj['name'] . "_minute", $arrData[1]);
              $strResult .= $this->_genSelectMinSec($obj['name'] . "_second", $arrData[2]);
              $strResult .= "<input type=hidden name='" . $obj['name'] . "' id='" . $obj['name'] . "' value='" . $obj['value'] . "' >";
            } else {
              if ($obj['type'] == 'autocomplete') {
                $strResult = $obj['htmlBefore'] . "<input class=t type=\"text\" id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" ";
                if (isset($this->formAutoComplete[$obj['name']]['label'])) {
                  $labelName = $this->formAutoComplete[$obj['name']]['label'];
                  foreach ($obj['values'] as $arrVal) {
                    if ($arrVal['value'] == $obj['value']) {
                      $this->objects[$labelName]['value'] = $arrVal['text'];
                      break;
                    }
                  }
                }
              } else {
                $strResult = $obj['htmlBefore'] . "<input class=t type=\"" . $obj['type'] . "\" id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" ";
              }
              if (!$this->resetData) {
                $strResult .= " value=\"" . $obj['value'] . "\"";
              }
              if ($obj['dataType'] == "date") {
                $strResult .= " size=\"10\" maxlength=\"10\"";
              } else if ($obj['dataType'] == "time") {
                $strResult .= " size=\"10\" maxlength=\"5\"";
              }
              if ($obj['attribute'] != '') {
                $strResult .= " " . $obj['attribute'];
              }
              if (!$obj['enabled']) {
                $strResult .= " disabled";
              }
              $strResult .= ">";
              if ($obj['dataType'] == "date") {
                $strResult .= "&nbsp;<input type=\"button\" name=\"btn" . $obj['name'] . "\" id=\"btn" . $obj['name'] . "\" class=\"buttonCalendar\">";
              }
            }
          }
        }
        break;
      case 'checkbox' :
        if ($obj['visible']) {
          if ($this->readonly) {
            if ($obj['value']) {
              $strResult = $obj['htmlBefore'] . "<input class=c type=\"checkbox\" id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" checked disabled>";
            }//.$obj['htmlAfter'];
            else {
              $strResult = $obj['htmlBefore'] . "<input class=c type=\"checkbox\" id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\" disabled>";
            }//.$obj['htmlAfter'];
          } else {
            $strResult = $obj['htmlBefore'] . "<input class=c type=\"" . $obj['type'] . "\" id=\"" . $obj['name'] . "\" name=\"" . $obj['name'] . "\"";
            if ($obj['value'] && !$this->resetData) {
              $strResult .= " checked";
            }
            if ($obj['attribute'] != '') {
              $strResult .= " " . $obj['attribute'];
            }
            if (!$obj['enabled']) {
              $strResult .= " disabled";
            }
            $strResult .= ">";
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
    if ($obj['required']) {
      $strResult .= $this->_printSpan("", "&nbsp;*", ["class" => "required"], "&nbsp;" . $obj['htmlAfter'], "");
    } else {
      $strResult .= "&nbsp;" . $obj['htmlAfter'];
    }
    return $strResult;
  }

  function _printObjectCaption($obj)
  {
    if ($obj['renderLabel']) {
      return "<label for=\"" . $obj['name'] . "\">" . $obj['title'] . "</label>";
    } else {
      return "";
    }
  }

  function _printObjectGroup($tabPageNumber, $groupNumber)
  {
    if (isset($this->fieldSets[$tabPageNumber][$groupNumber])) {
      $colCount = $this->fieldSets[$tabPageNumber][$groupNumber]['columns'];
    } else if ($this->hasTabPage) {
      $colCount = $this->tabPages[$tabPageNumber]['columns'];
    } else {
      $colCount = $this->colCount;
    }
    $strResult = "";
    $strResult .= "
          <table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\">
            <tr valign=\"top\">";
    //----------hitung jumlah form objects
    $jmlBaris = ceil(count($this->formObject[$tabPageNumber][$groupNumber]) / $colCount);
    //----------hitung percentage dari kolom untuk tampilan multi kolom
    $percentWidth = round(100 / $colCount, 1);
    //render object2 ke html berdasarkan jumlah kolom
    //----------object yang tidak visible lompati saja jangan di-render.
    for ($i = 0; $i < $colCount; $i++) {
      $strResult .= "
              <td width=\"" . $percentWidth . "%\" style=\"padding-left:5px; padding-right:5px\">
                <table border=\"0\" cellpadding=\"1\" cellspacing=\"0\">";
      for (
          $j = $i * $jmlBaris;
      ($j < ($i + 1) * $jmlBaris && $j < count($this->formObject[$tabPageNumber][$groupNumber])); $j++
      ) {
        $strResult .= "
                  <tr valign=\"top\">";
        if (!($this->formObject[$tabPageNumber][$groupNumber][$j]['renderLabel'])) {
          $strResult .= "
                    <td colspan=3>" . $this->_printObject($this->formObject[$tabPageNumber][$groupNumber][$j]) . "</td>
                  </tr>";
        } else {
          if (count($this->formObject[$tabPageNumber][$groupNumber][$j]['labelAttribute']) > 0) {
            $strResult .= "
                    <td " . $this->_serializeAttribute(
                    $this->formObject[$tabPageNumber][$groupNumber][$j]['labelAttribute']
                ) . ">" . $this->_printObjectCaption(
                    $this->formObject[$tabPageNumber][$groupNumber][$j]
                ) . "&nbsp;</td>";
          } else {
            $strResult .= "
                    <td nowrap>" . $this->_printObjectCaption(
                    $this->formObject[$tabPageNumber][$groupNumber][$j]
                ) . "</td>";
          }
          if ($this->formObject[$tabPageNumber][$groupNumber][$j]['title'] != "") {
            $strResult .= "
                    <td width=\"10\" >:</td>";
          } else {
            $strResult .= "
                    <td></td>";
          }
          $strResult .= "
                    <td>" . $this->_printObject($this->formObject[$tabPageNumber][$groupNumber][$j]) . "</td>
                  </tr>";
        }
      }
      $strResult .= "
                </table>
              </td>";
    }
    $strResult .= "
            </tr>
          </table>";
    if (isset($this->fieldSets[$tabPageNumber][$groupNumber])) {
      $strResult = "
          <fieldset><legend>" . $this->fieldSets[$tabPageNumber][$groupNumber]['name'] . "</legend>" . $strResult . "
          </fieldset>";
    }
    return $strResult;
  }

  function _printObjectList()
  {
    $strResult = "";
    if ($this->hasTabPage) {
      $strResult = "<div style='visibility: hidden' class=\"tab-pane\" id=\"" . $this->formName . "_PN\">";
    }
    for ($i = 0; $i <= $this->tabPageNumber; $i++) {
      $intGroups = 0;
      if (isset($this->fieldSets[$i])) {
        $intGroups = count($this->fieldSets[$i]) - 1;
      }
      if ($intGroups < 0) {
        $intGroups = 0;
      }
      //draw tab page if any
      if ($this->hasTabPage) {
        $strResult .= "
        <div class=\"tab-page\" id=\"" . $this->formName . "_TP" . ($i + 1) . "\">
          <h2 class=\"tab\">" . $this->tabPages[$i]['name'] . "</h2>";
      }
      for ($j = 0; $j <= $intGroups; $j++) {
        $strResult .= $this->_printObjectGroup($i, $j);
      }
      //close div tab if any
      if ($this->hasTabPage) {
        $strResult .= "</div>";
      }
    }
    $strResult .= "
          <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
    if ($this->hasButton) {
      $strResult .= "
              <tr valign=\"top\">
                <td height=\"30\" valign=\"middle\">";
      //----------render all button object
      foreach ($this->formButton as $value) {
        if ($value['visible']) {
          $strResult .= $this->_printObject($value) . "&nbsp;";
        }
      }
      //----------end of render button
      $strResult .= "
                </td>
              </tr>";
    }
    if ($this->hasRequiredControl) {
      $strResult .= "
            <tr><td valign='bottom'><span class='requiredSmall'>* " . $GLOBALS['FORMWORDS']['required field'] . "</span></td></tr>";
    }
    $strResult .= "
          </table>";
    if ($this->hasTabPage) {
      $strResult .= "</div>";
    }
    return $strResult;
  }

  function _printSpan($name, $text, $arrAttribute, $htmlBefore, $htmlAfter)
  {
    $att = $this->_serializeAttribute($arrAttribute);
    if ($name == "") {
      return $htmlBefore . "<span $att>" . nl2br($text) . "</span>" . $htmlAfter;
    } else {
      return $htmlBefore . "<span id=\"" . $name . "\" name=\"" . $name . "\" $att>" . nl2br(
          $text
      ) . "</span>" . $htmlAfter;
    }
  }

  function _printSubmitToServerJSAJAX()
  {
    $strResult = "
      setEventClickSubmitButton : function()
      {";
    if ($this->useAJAXTechnology) {
      foreach ($this->formButton as $value) {
        if ($value['type'] == 'submit') {
          $strResult .= "
          $('" . $value['name'] . "').observe('click', function(event)
            {
              var elt = Event.element(event);
              my" . $this->formName . ".lastSubmittedButton = elt.id;
              //alert(elt.id);
            });";
        }
      }
    }
    $strResult .= "
      },
      submitToServer : function()
      {
        if (this.useAJAXsubmit)
        {
          if (this.submitCount != 0) return false;
          this.submitCount++;

          var formId = '" . $this->formName . "';
          var phpFile = '" . $this->scriptFileName . "';
          var lastSubmitted = this.lastSubmittedButton;
          var queryString = '';
          //alert(lastSubmitted);

          var arrControl = $(formId).getElements();
          arrControl.each(function(item)
          {
            if (item.type != 'submit' || item.id == lastSubmitted)
              queryString += $(item.id).serialize() + '&';
          });

          queryString += 'ajaxForm=" . $this->formName . "';

          if (phpFile.indexOf('?') > 0)
          {
            queryString += '&' + phpFile.substring(phpFile.indexOf('?') + 1);
            phpFile = phpFile.substring(0, phpFile.indexOf('?'));
          }
          var obj = this;

          new Ajax.Request('" . basename($this->scriptFileName) . "',
            { method:'post',
              parameters: queryString,
              onSuccess: function(transport, json)
              {
                obj.submitCount = 0;
                //alert(transport.responseText);
                if ((transport.responseText || '') == '') return false;
                var responseData = transport.responseText;
                var headerContent =  '<!-- START OF FORM CONTENT: ' + formId + ' FOR AJAX -->';
                var idx = transport.responseText.indexOf(headerContent);
                if (idx >= 0) idx += headerContent.length;

                responseData = responseData.substring(idx);

                idx = responseData.indexOf('<!-- END OF FORM CONTENT: ' + formId + ' FOR AJAX -->');
                if (idx >= 0) responseData = responseData.substring(0, idx);

                //alert(responseData);
                $('content_" . $this->formName . "').update(responseData);
                obj.setEventClickSubmitButton();
              },
              onLoading: function()
              {
                obj.submitCount++;
                $('" . $this->formName . "').disabled();
                //$('content_" . $this->formName . "').update('Submitting to server...');
              },
              onFailure: function()
              {
                obj.submitCount = 0;
                $('content_" . $this->formName . "').update('Fail to request data from server via AJAX...');
              }
            });
        }
        //must be set false to prevent submitted browser
        return false;
      }, ";
    return $strResult;
  }

  function _printWindowCaption()
  {
    $strResult = "";
    if ($this->showCaption) {
      $strResult .= "
    <tr valign=\"top\">
      <td class=\"formBoxTopLeft\"></td>
      <td class=\"formBoxTitle\" valign=\"top\">";
      $strResult .= "
        <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
          <tr>";
      if ($this->showHelpButton) {
        $strResult .= "<td width=16 align=center valign=middle><img src=\"" . $this->helpIcon . "\" width=16 border=0 onClick=\"javascript:openHelp()\" /></td>";
      }
      $strResult .= "
            <td class=\"formBoxCaption\" valign=\"top\">" . $this->caption . "</td>";
      if ($this->showMinimizeButton) {
        $strResult .= "
            <td class=\"minimize\" onClick=\"javascript:my" . $this->formName . ".doMinimize(this)\" onMouseOver=\"this.className=this.className+'Hover'\" onMouseOut=\"this.className=this.className.replace('Hover', '')\">&nbsp;</td>";
      }
      if ($this->showCloseButton) {
        $strResult .= "
            <td class=\"close\" onClick=\"javascript:my" . $this->formName . ".doClose(this)\" onMouseOver=\"this.className='formBoxCloseButtonHover'\" onMouseOut=\"this.className='formBoxCloseButton'\">&nbsp;</td>";
      }
      $strResult .= "
          </tr>
        </table>
      </td>
      <td class=\"formBoxTopRight\"></td>
    </tr>";
    }
    return $strResult;
  }

  function _runTabPage()
  {
    $this->currentTabPageIndex = 0;
    return "
      <script type=\"text/javascript\">
        myTabPage_" . $this->formName . " = new WebFXTabPane( document.getElementById( \"" . $this->formName . "_PN\" ), false );
        //<![CDATA[
        setupAllTabs();
        //]]>

        document.getElementById('" . $this->formName . "_PN').style.visibility = '';
      </script>";
  }

  //This function is to create input submit object
  //Parameter $serverAction is a must
  //e.g. if you provide $serverAction = "saveDate()" then you must have PHP function called saveData()

  function _serializeAttribute($arrAttribute)
  {
    $strAttribute = "";
    if ($arrAttribute != null) {
      if (is_array($arrAttribute)) {
        $arrResult = [];
        foreach ($arrAttribute as $key => $value) {
          $arrResult[] = $this->_formatAttribute($key, $value);
        }
        $strAttribute = implode(' ', $arrResult);
      } else {
        $strAttribute = $arrAttribute;
      }
    }
    return $strAttribute;
  }

  //This function is to create input text object
  //Parameter $clientAction is clientAction/javascript (is a must)
  //e.g. if you provide $clientAction = "onClick='doHelloWorld()'" then you must have javascript function called doHelloWorld()

  function _setTabPageInit()
  {
    $strResult = "";
    if ($this->height != "auto" || $this->height != "100%") {
      $strResult .= "
<style>
.dynamic-tab-pane-control .tab-page
{
  height:	" . intval($this->height) . "px;
}
</style>
";
    }
    return $strResult . "
      <link type=\"text/css\" rel=\"stylesheet\" href=\"" . $this->CSSPath . "tab.css\" />
      <script type=\"text/javascript\" src=\"" . $this->JSPath . "tabpane.js\"></script>";
  }

  function _uploadFile($objID, $targetFolder)
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
        $this->objects[$objID]['uploaded'] = true;
        return true;
      }
    }
    return false;
  }

  function _writeWindowHelp()
  {
    global $CLASSFORMPATH;
    global $FORM_CSS;
    $strResult = "
  <div id=\"windowHelp_" . $this->formName . "\" class=\"window\" style=\"left:" . $this->leftWindowHelp . "px;top:" . $this->topWindowHelp . "px;width:" . $this->widthWindowHelp . "px\">
    <div class=\"titleBar\">
      <table class=\"tableTitleBar\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <td height=21 class=\"titleBarLeft\" width=6>&nbsp;</td>";
    if ($this->helpIcon != "") {
      $strResult .= "
          <td class=\"titleBarIcon\" width=\"16\"><img src=\"" . $this->helpIcon . "\" border=\"0\" width=\"16\" height=\"16\" /></td>";
    }
    $strResult .= "
          <td class=\"titleBarText\" width=\"6\">" . $this->helpCaption . "</td>";
    $imagePath = ereg_replace('/+', '/', $CLASSFORMPATH . "/css/" . $FORM_CSS . "/");
    $minOff = $imagePath . "form_minimizeoff.gif";
    $minOn = $imagePath . "form_minimizeon.gif";
    $maxOff = $imagePath . "form_maximizeoff.gif";
    $maxOn = $imagePath . "form_maximizeon.gif";
    $closeOff = $imagePath . "form_closeoff.gif";
    $closeOn = $imagePath . "form_closeon.gif";
    $strResult .= "
          <td class=\"titleBarButtonsRow\" width=\"52\" nowrap><img class=\"topButton\" src=\"" . $minOff . "\" border=\"0\" width=\"16\" height=\"16\" onclick=\"this.parentWindow.minimize();return false;\" onMouseOver=\"this.src='" . $minOn . "'\" onMouseOut=\"this.src='" . $minOff . "'\"><img class=\"topButton\" src=\"" . $maxOff . "\" border=\"0\" width=\"16\" height=\"16\" onclick=\"this.parentWindow.restore();return false;\" onMouseOver=\"this.src='" . $maxOn . "'\" onMouseOut=\"this.src='" . $maxOff . "'\"><img src=\"" . $closeOff . "\" border=\"0\" width=\"16\" height=\"16\" onclick=\"this.parentWindow.close();return false;\" onMouseOver=\"this.src='" . $closeOn . "'\" onMouseOut=\"this.src='" . $closeOff . "'\"></td>
          <td class=\"titleBarRight\" width=6>&nbsp;</td>
        </tr>
      </table>
    </div>";
    if (is_numeric($this->heightWindowHelp)) {
      $strHeight = (int)($this->heightWindowHelp - 25) . "px;";
    } else {
      $strHeight = "auto";
    }
    $strResult .= "
    <div class=\"clientArea\" style=\"height:$strHeight;\">" .
        $this->help . "
    </div>
  </div>
  <script type=\"text/javascript\">
    winInit();
    function openHelp(val)
    {
      if (winList['windowHelp_" . $this->formName . "'])
      {
        var myWin = winList['windowHelp_" . $this->formName . "'];
        if (myWin.isOpen) myWin.close(); else myWin.open();
      }
    }
  </script>";
    return $strResult;
  }

  //for backward compatibility, use getValue or setValue instead

  function addBackButton(
      $name,
      $value,
      $arrAttribute = null,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $serverAction = null
  ) {
    $URL_REFERER = $this->_getLastURL();
    $arrAttribute['onClick'] = "javascript:location.href='" . $URL_REFERER . "';";
    $this->_addCommonButton(
        "button",
        $name,
        $value,
        $arrAttribute,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        ""
    );
  }

  //get the value of object

  function addButton(
      $name,
      $value,
      $arrAttribute,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $serverAction = null
  ) {
    $this->_addCommonButton(
        "button",
        $name,
        $value,
        $arrAttribute,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        ""
    );
  }

  //set the value of object

  function addCheckBox(
      $title,
      $name,
      $value,
      $arrAttribute = [],
      $dataType = "string",
      $bolRequired = true,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $renderLabel = true,
      $arrLabelAttribute = null
  ) {
    if ($bolRequired) {
      $this->hasRequiredControl = true;
    }
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
        $title,
        $name,
        $value,
        $arrAttribute,
        $dataType,
        $bolRequired,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        $renderLabel,
        $arrLabelAttribute,
        null
    );
  }

  //get the values of object (for object SELECT only)
  //input object name

  function addFieldSet($name, $intNumberOfColumns = null)
  {
    $this->groupingNumber++;
    //if NO number of columns specified then use form's one
    if ($intNumberOfColumns == null) {
      $intNumberOfColumns = $this->colCount;
    }
    if ($this->tabPageNumber < 0) {
      $this->tabPageNumber = 0;
    }
    $this->fieldSets[$this->tabPageNumber][$this->groupingNumber] = ["name" => $name, "columns" => $intNumberOfColumns];
  }

  //set the values of object (for object SELECT only)
  //input object name, and ARRAY data values

  function addFile(
      $title,
      $name,
      $value,
      $arrAttribute,
      $dataType = "string",
      $bolRequired = true,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $renderLabel = true,
      $arrLabelAttribute = null,
      $targetFolder = "" /*untuk tipe data file  saja*/
  )
  {
    $this->hasFile = true;
    $this->addFormObject(
        'file',
        $title,
        $name,
        $value,
        $arrAttribute,
        $dataType,
        $bolRequired,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        $renderLabel,
        $arrLabelAttribute,
        null,
        $targetFolder
    );
  }

  //if this function called, then all POST data value will not be RENDER again

  function addFormObject(
      $type,
      $title,
      $name,
      $value,
      $arrAttribute,
      $dataType,
      $bolRequired,
      $bolEnabled,
      $bolVisible,
      $htmlBefore,
      $htmlAfter,
      $renderLabel = true,
      $arrLabelAttribute,
      $serverAction = "",
      $targetFolder = "",
      $bolVertical = null,
      $calendarUpdate = null
  ) {
    $strAllCanons = "";
    $strNickTokens = "";
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
        $arrValue = $value;
        while (list($key, $val) = each($arrValue)) {
          $data = &$value[$key];
          if (!is_array($data)) {
            $tempVal = ["id" => $key, "value" => $key, "text" => $data, $selectKey => false];
            $data = $tempVal;
          } else {
            if (!isset($data['id'])) {
              $data['id'] = "";
            }
            if (!isset($data['value'])) {
              $data['value'] = "";
            }
            if (!isset($data['text'])) {
              $data['text'] = "";
            }
            if (!isset($data[$selectKey])) {
              $data[$selectKey] = false;
            } else if ($data[$selectKey]) {
              $selectedValue = $data['value'];
              $selectedText = $data['text'];
            }
          }
        }
      }
      $values = $value;
    } else if ($type == 'literal') {
      $selectedValue = $value;
      $selectedText = "";
      $values = "";
    } else if ($type == 'autocomplete') {
      $this->_generateAutoCompleteCanonData($value, $strAllCanons, $strNickTokens, $selectedValue, $selectedText);
      $this->formAutoComplete[$name] = ["label" => "", "canon" => $strAllCanons, "nick" => $strNickTokens];
      $values = $value;
    } else if ($type == "labelautocomplete") {
      $oldname = $name;
      $name = "label_" . $name;
      $this->formAutoComplete[$oldname]['label'] = $name;
      $selectedValue = $value;
      $selectedText = "";
      $values = "";
    } else {
      $selectedValue = $value;
      $selectedText = "";
      $values = "";
    }
    $readonly = false;
    if (is_array($arrAttribute)) {
      foreach ($arrAttribute as $key => $val) {
        if (strtolower($key) == "readonly") {
          if (strtolower($val) == "readonly" || ($val)) {
            $readonly = true;
          }
        }
      }
    }
    $dataForm = [
        "type"           => $type,
        "enabled"        => $bolEnabled,
        "visible"        => $bolVisible,
        "required"       => $bolRequired,
        "readonly"       => $readonly,
        "title"          => $title,
        "dataType"       => $dataType,
        "name"           => $name,
        "id"             => $name,
        "value"          => $selectedValue,
        "text"           => $selectedText,
        "values"         => $values,
        "attribute"      => $this->_serializeAttribute($arrAttribute),
        "htmlBefore"     => $htmlBefore,
        "htmlAfter"      => $htmlAfter,
        "serverAction"   => $serverAction,
        "labelAttribute" => $arrLabelAttribute,
        "targetFolder"   => $targetFolder,
        "uploaded"       => false,
        "verticalLayout" => $bolVertical,
        "renderLabel"    => $renderLabel,
        "calendarUpdate" => $calendarUpdate
    ];
    if ($this->groupingNumber < 0) {
      $this->groupingNumber = 0;
    }
    if ($this->tabPageNumber < 0) {
      $this->tabPageNumber = 0;
    }
    $this->formObject[$this->tabPageNumber][$this->groupingNumber][] = &$dataForm;
    $this->objects[$name] = &$dataForm;
    if ($type != 'literal') {
      $this->formHidden[$name] = [
          "name"        => "hidden_" . $name,
          "value"       => $selectedValue,
          "enabled"     => true,
          "required"    => false,
          "renderLabel" => false
      ];
    }
  }

  //this method if called, then all form Object will be replaced with SPAN object
  //useful to make view only form, e.g. : after save data

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
    $this->showHelpButton = true;
    $this->helpCaption = $helpTitle;
    $this->help = $helpContent;
  }

  function addHidden($name, $value = "")
  {
    $dataHidden = [
        "type"        => "hidden",
        "name"        => $name,
        "value"       => $value,
        "enabled"     => true,
        "required"    => false,
        "renderLabel" => false
    ];
    $this->formHidden[$name] = &$dataHidden;
    $this->objects[$name] = &$this->formHidden[$name];
  }

  function addInput(
      $title,
      $name,
      $value,
      $arrAttribute,
      $dataType = "string",
      $bolRequired = true,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $renderLabel = true,
      $arrLabelAttribute = null,
      $calendarUpdate = null
  ) {
    if ($bolRequired) {
      $this->hasRequiredControl = true;
    }
    $this->addFormObject(
        'text',
        $title,
        $name,
        $value,
        $arrAttribute,
        $dataType,
        $bolRequired,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        $renderLabel,
        $arrLabelAttribute,
        null,
        null,
        null,
        $calendarUpdate
    );
  }

  //GET LAST URL

  function addInputAutoComplete(
      $title,
      $name,
      $value,
      $arrAttribute = [],
      $dataType = "string",
      $bolRequired = true,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $renderLabel = true,
      $arrLabelAttribute = null
  ) {
    if ($bolRequired) {
      $this->hasRequiredControl = true;
    }
    if (!is_array($value)) {
      $arrExamples = [];
      $arrExamples[] = ["value" => 1, "text" => "Dedy"];
      $arrExamples[] = ["value" => 2, "text" => "Agus"];
      $arrExamples[] = ["value" => 3, "text" => "Linda"];
      echo "To add autocomplete textbox, you must provide value with data type ARRAY with 2 key-field<br>";
      echo "e.g data: <pre>";
      print_r($arrExamples);
      echo "</pre>";
      die();
    }
    $this->hasAutoComplete = true;
    $this->addFormObject(
        'autocomplete',
        $title,
        $name,
        $value,
        $arrAttribute,
        $dataType,
        $bolRequired,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        $renderLabel,
        $arrLabelAttribute
    );
  }

  function addLabel(
      $title,
      $name,
      $value,
      $arrAttribute = [],
      $dataType = "string",
      $bolRequired = false,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $renderLabel = true,
      $arrLabelAttribute = null
  ) {
    $this->addFormObject(
        'label',
        $title,
        $name,
        $value,
        $arrAttribute,
        $dataType,
        false,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        $renderLabel,
        $arrLabelAttribute
    );
  }

  function addLabelAutoComplete(
      $title,
      $name,
      $value,
      $arrAttribute = [],
      $dataType = "string",
      $bolRequired = false,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $renderLabel = true,
      $arrLabelAttribute = null
  ) {
    $this->addFormObject(
        'labelautocomplete',
        $title,
        $name,
        $value,
        $arrAttribute,
        $dataType,
        false,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        $renderLabel,
        $arrLabelAttribute
    );
  }

  function addLiteral($title, $name, $literalValue, $renderLabel = true, $arrLabelAttribute = null)
  {
    $this->addFormObject(
        'literal',
        $title,
        $name,
        $literalValue,
        null,
        "",
        false,
        true,
        true,
        "",
        "",
        $renderLabel,
        $arrLabelAttribute,
        null
    );
  }

  function addPassword(
      $title,
      $name,
      $value,
      $arrAttribute,
      $dataType = "string",
      $bolRequired = true,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $renderLabel = true,
      $arrLabelAttribute = null
  ) {
    if ($bolRequired) {
      $this->hasRequiredControl = true;
    }
    $this->addFormObject(
        'password',
        $title,
        $name,
        $value,
        $arrAttribute,
        $dataType,
        $bolRequired,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        $renderLabel,
        $arrLabelAttribute,
        null
    );
  }

  function addRadio(
      $title,
      $name,
      $value,
      $arrAttribute,
      $dataType = null,
      $bolRequired = true,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $renderLabel = true,
      $arrLabelAttribute = null,
      $bolVertical = true
  ) {
    if ($bolRequired) {
      $this->hasRequiredControl = true;
    }
    $this->addFormObject(
        'radio',
        $title,
        $name,
        $value,
        $arrAttribute,
        "string",
        $bolRequired,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        $renderLabel,
        $arrLabelAttribute,
        null,
        $bolVertical
    );
  }

  function addReset(
      $name,
      $value,
      $arrAttribute,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $serverAction = null
  ) {
    $this->_addCommonButton(
        "reset",
        $name,
        $value,
        $arrAttribute,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        ""
    );
  }

  function addSelect(
      $title,
      $name,
      $value,
      $arrAttribute = [],
      $dataType = "string",
      $bolRequired = true,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $renderLabel = true,
      $arrLabelAttribute = null
  ) {
    if ($bolRequired) {
      $this->hasRequiredControl = true;
    }
    $this->addFormObject(
        'select',
        $title,
        $name,
        $value,
        $arrAttribute,
        $dataType,
        $bolRequired,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        $renderLabel,
        $arrLabelAttribute,
        null
    );
  }

  function addSubmit(
      $name,
      $value,
      $arrAttribute,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $serverAction = ""
  ) {
    $this->_addCommonButton(
        "submit",
        $name,
        $value,
        $arrAttribute,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        $serverAction
    );
  }

  function addTabPage($tabName, $intNumberOfColumns = null)
  {
    $this->hasTabPage = true;
    $this->groupingNumber = -1;
    $this->tabPageNumber++;
    if ($intNumberOfColumns == null) {
      $intNumberOfColumns = $this->colCount;
    }
    $this->tabPages[$this->tabPageNumber] = [
        "name"    => $tabName,
        "id"      => $this->tabPageNumber,
        "columns" => $intNumberOfColumns
    ];
  }

  function addTextArea(
      $title,
      $name,
      $value,
      $arrAttribute,
      $dataType = "string",
      $bolRequired = true,
      $bolEnabled = true,
      $bolVisible = true,
      $htmlBefore = "",
      $htmlAfter = "",
      $renderLabel = true,
      $arrLabelAttribute = null
  ) {
    if ($bolRequired) {
      $this->hasRequiredControl = true;
    }
    $this->addFormObject(
        'textarea',
        $title,
        $name,
        $value,
        $arrAttribute,
        $dataType,
        $bolRequired,
        $bolEnabled,
        $bolVisible,
        $htmlBefore,
        $htmlAfter,
        $renderLabel,
        $arrLabelAttribute,
        null
    );
  }

  function disableForm()
  {
    $this->enabled = false;
    $arrObjects = $this->objects;
    while (list($key, $val) = each($arrObjects)) {
      $value = &$this->objects[$key];
      if ($value['type'] == "text" || $value['type'] == "password" || $value['type'] == "select" || $value['type'] == "textarea" ||
          $value['type'] == "radio" || $value['type'] == "checkbox" || $value['type'] == "hidden"
      ) {
        $value['enabled'] = false;
      }
    }
  }

  function disableFormTag()
  {
    $this->hasFormTag = false;
  }

  //this function will get last $_POST and action that call before

  function getObjectValues()
  {
    foreach ($this->formObject[0][0] AS $strObjID => $arrObjDetail) {
      if (substr($arrObjDetail['name'], 0, 4) == "data") {
        $arrResult[$arrObjDetail['name']] = $arrObjDetail['value'];
      }
    }
    return $arrResult;
  }

  function getValue($name)
  {
    if (isset($this->objects[$name])) {
      if ($this->objects[$name]['type'] == "button" || $this->objects[$name]['type'] == "submit") {
        return $this->objects[$name]['clicked'];
      } else {
        return $this->objects[$name]['value'];
      }
    } else {
      die ("Trying to get value of non object, please check object name '" . $name . "'!");
    }
  }

  function getValues($name)
  {
    if (isset($this->objects[$name])) {
      if ($this->objects[$name]['type'] == "select") {
        return $this->objects[$name]['values'];
      } else {
        die ("Trying to get values of non SELECT object!");
      }
    } else {
      die ("Trying to get values of non object, please check object name '" . $name . "'!");
    }
  }

  function readOnlyForm()
  {
    $this->readonly = true;
  }

  function render()
  {
    $this->_getRequest();
    //save URL Referer
    $this->addHidden("hidden_URL_REFERER", $this->_getLastURL());
    //print_r($this->objects);
    $strResult = "";
    //----------print CSS filename if exist
    if ($this->CSSFileName != "") {
      $strResult .= "
  <link href=\"" . $this->CSSFileName . "\" rel=\"stylesheet\" type=\"text/css\">";
    }
    if ($this->hasAutoComplete) {
      $strResult .= "
  <link href=\"" . ereg_replace(
              '/+',
              '/',
              $GLOBALS['CLASSFORMPATH'] . "/css/ac.css"
          ) . "\" rel=\"stylesheet\" type=\"text/css\">";
    }
    //----------end of print CSS
    //print Javascript
    if (!$GLOBALS['PROTOTYPE_LOADED']) {
      $GLOBALS['PROTOTYPE_LOADED'] = true;
      $strResult .= "
  <script type=\"text/javascript\" src=\"" . $GLOBALS['CLASSFORMPATH'] . "scripts/form_prototype.js" . "\"></script>";
    }
    $strResult .= "
  <script type=\"text/javascript\" src=\"" . $this->JSFileName . "\"></script>";
    if ($this->hasAutoComplete) {
      $GLOBALS['AUTOCOMPLETE_LOADED'] = true;
      $strResult .= "
  <script type=\"text/javascript\" src=\"" . $GLOBALS['CLASSFORMPATH'] . "scripts/form_ac.js" . "\"></script>";
    }
    $strResult .= $this->_printJSInit();
    //end of print Javascript
    $tableWidth = ($this->width == "") ? "" : "width=\"" . $this->width . "\" ";
    $tableHeight = ($this->height == "" || $this->showMinimizeButton) ? "" : "height=\"" . $this->height . "\" ";
    $strResult .= "
  <table id=\"table_" . $this->formName . "\" style='display: none' class=\"formBox\" " . $tableWidth . $tableHeight . "border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
    $strResult .= $this->_printWindowCaption();
    $strResult .= "
    <tr valign=\"top\" id=\"row_" . $this->formName . "\">
      <td class=\"formBoxLeft\"></td>
      <td id=\"content_" . $this->formName . "\" class=\"formBoxContent\" valign=\"top\">
      <!-- START OF FORM CONTENT: " . $this->formName . " FOR AJAX -->";
    //print form tag, if $hasFormTag set to true
    $strResult .= $this->_printFormTag();
    //print message if any
    if ($this->message != '') {
      $strResult .= "
          <div id=\"formMessage\" name=\"formMessage\" class=\"" . $this->msgClass . "\">" . $this->message . "</div>";
    } else // don't show message, but still print the reserved DIV
    {
      $strResult .= "
          <div id=\"formMessage\" name=\"formMessage\" style=\"visibility:hidden\" >&nbsp;</div>";
    }
    //mencetak object object textbox, select, textarea, checkbox, dsb
    $strResult .= $this->_printObjectList();
    //print closing form tag, if $hasFormTag set to true
    if ($this->hasFormTag) {
      $strResult .= "
        </form>";
    }
    $strResult .= "
      <!-- END OF FORM CONTENT: " . $this->formName . " FOR AJAX -->
      </td>
      <td class=\"formBoxRight\"></td>
    </tr>
  </table>";
    if ($this->showCaption && $this->showHelpButton) {
      $strResult .= $this->_writeWindowHelp();
    }
    //print closing form tag, if $hasFormTag set to true
    if ($this->hasTabPage) {
      $strResult .= $this->_setTabPageInit();
      $strResult .= $this->_runTabPage();
    }
    //for focusing default control and make select/combo to select index -1 not index 0
    $strResult .= $this->_printJSOnload();
    //write information
    $strResult = "
<!-- START OF FORM ENTRY, generated by FormEntry Class, by Dedy Sukandar -->" . $strResult . "
<!-- END OF FORMENTRY, generated by FormEntry Class, by Dedy Sukandar -->";
    //jika dari AJAX maka langsung di echo dan exit
    if ($this->formAJAXsubmitted) {
      die($strResult);
    } else {
      return $strResult;
    }
  }

  function resetBeforeRender()
  {
    $this->resetData = true;
  }

  function setAJAXCallBackScript($scriptFileName)
  {
    $this->action = $scriptFileName;
    $this->scriptFileName = $scriptFileName;
    $this->useAJAXTechnology = true;
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

  function setFormTarget($target)
  {
    $this->target = $target;
  }

  //parameter: nama file script yang akan dipanggil ketika AJAX request ke server terjadi

  function setJSfile($filename)
  {
    if (file_exists($filename)) {
      $this->JSFileName = $filename;
    } else {
      $this->JSFileName = "form.js";
    }
  }

  function setValue($name, $val)
  {
    if (isset($this->objects[$name])) {
      $this->objects[$name]['value'] = $val;
    } else {
      die ("Trying to set value of non object, please check object name '" . $name . "'!");
    }
  }

  function setValues($name, $arrValue)
  {
    if (isset($this->objects[$name])) {
      if ($this->objects[$name]['type'] == "select") {
        $this->objects[$name]['values'] = $arrValue;
      } else {
        die ("Trying to set values of non SELECT object!");
      }
    } else {
      die ("Trying to set values of non object, please check object name '" . $name . "'!");
    }
  }

  function value($name)
  {
    return $this->getValue($name);
  }
}

?>
