<?php
/*
   Dedy's class DataGrid
   version 1.0
   PT. Invosa Systems
   All right reserved.
 Last Update : 21 April 2008
*/
require_once("datagrid.config.php");
require_once("excel/Workbook.php");
require_once("excel/Worksheet.php");
DEFINE("DATAGRID_RENDER_NORMAL", 0);
DEFINE("DATAGRID_RENDER_EXCEL_HTML", 1);
DEFINE("DATAGRID_RENDER_EXCEL_BIFF", 2);
DEFINE("DATAGRID_RENDER_PRINT_HTML", 3);

class cDataGrid
{

  //set privileges of datagrid to delete, edit or view
  var $CSSFileName;

  var $CSSFileNameExcel = "";

  var $DATAGRID_RENDER_OUTPUT = null;

  var $JSFileName;

  var $autoScroll = false;

  var $blankSpace;

  var $buttonExcel = [];

  //these properties must be called from method setAJAXCallBackScript()
  //do not set directly

  var $buttonExcelHTML = [];

  var $buttonPrint = [];

  //-------------------------------------------------------------
  //autoscroll property,
  //if set true, then datagrid will have auto scroll box (if the width column > table width)

  var $buttonSearchName = "btnSearch";

  //autoscroll property,
  //if set true, then datagrid will have own form tag <form id=....
  //if set false, then datagrid must be placed inside other form tag from your own HTML,
  //call method disableFormTag to set this variable to false

  var $buttonSpecialName = "";

  var $buttons;

  //set $isShowPageNumbering = true, to show paging

  var $caption = "";

  //set $isShowPageLimit = true, to show view limit page size
  //(have relation with variable $isShowPageNumbering) 

  var $checkboxItemID = "";

  //set $isShowSort = true, to show sort by column

  var $checkboxes;

  var $columnSet = [];

  var $dataset = [];

  var $defaultPageLimit;

  //the datagrid object name

  var $defaultPagingSize = "10";

  //the datagrid width

  var $errorMessage = "";

  //the datagrid height

  var $fontSize = 9;

  //the grid dataset

  var $formNameId = "form1";

  //current page number

  var $gridClass = "dataGrid";

  var $gridName;

  //total number of page

  var $hasCheckbox = false;

  //current page limit
  //set "all" to make unlimited page size, otherwise set to positive integer e.g : 15

  var $hasFormTag = true;

  //current sort key

  var $hasGrandTotal = false;

  //current search key

  var $hasGroupBy = false;

  //current search criteria value

  var $height;

  //total numbers of data (use for paging)
  //must be set before calling method render

  var $imageList;

  //column set, consist of datagrid column object

var $imageName;

  //datagrid buttons collections

  var $intExcelRows = 0;

  var $isShowPageLimit;

  var $isShowPageNumbering;

  var $isShowSearch;

  //sort order ASC (Ascending) or DESC (Descending)

  var $isShowSort;

  //sort order key field

  var $jumpPage = 0;

  var $message = "";

  var $pageCount;

  var $pageLimit;

  //set CSS file name

  var $pageNumber = 1;

  var $pageSearchBy;

  //CSS class name
  //FIXED!!! don't change this value, unless you change datagrid.css too

  var $pageSearchCriteria;

  //------------------------------------------------------------------------------
  //FIXED!!!  dont change value below, unless you change JAVASCRIPT datagrid_.js too
  //------------------------------------------------------------------------------

  var $pageSortBy;

  var $repeaterFunction = "";

    var $resetNumbering = false;   //should be ASC or DESC

  var $scriptFileName = "";

  var $sheet = null;

  //repeater function

  var $sortName;

  var $sortOrder;

  var $strFileNameXLS = "";

  //export excel default font-size

  var $strGroupByField = "";

  var $strTitle1 = "";

  var $strTitle2 = "";

  var $strTitle3 = "";

  var $summaryData = [];

  var $totalData = "0";

  var $useAJAXTechnology = false;

  var $width;

  var $wkb = null;

  //---------------------------------------
  // Class constructor

  function cDataGrid(
      $formName = "form1",
      $parGridName = "DataGrid1",
      $gridWidth = "100%",
      $gridHeight = "100%",
      $showPageLimit = true, /*true = showing datagrid page limit control*/
      $showSearch = true, /*true = showing datagrid search feature*/
      $showSort = true, /*true = showing datagrid sort feature*/
      $showPageNumbering = true, /*true = showing datagrid page numbering*/
      $path = null /*force path to*/
  )
  {
    global $CLASSDATAGRIDPATH;
    global $DEFAULTPAGELIMIT;
    global $DEFAULTPAGINGSIZE;
    global $DATAGRID_CSS;
    global $globalLanguage;
    $this->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_NORMAL;
    $this->formNameId = $formName;
    $this->isShowPageNumbering = $showPageNumbering;
    $this->isShowSearch = $showSearch;
    $this->isShowPageLimit = $showPageLimit;
    $this->isShowSort = $showSort;
    $this->width = $gridWidth;
    $this->height = $gridHeight;
    $this->gridName = $parGridName;
    //don't change value below, because it is connected with Javascript function
    $this->buttonSearchName = "btnSearch" . $parGridName;
    $this->buttonSpecialName = "";
    $this->defaultPageLimit = $DEFAULTPAGELIMIT;
    $this->defaultPagingSize = $DEFAULTPAGINGSIZE;
    if ($path != null) {
      $CLASSDATAGRIDPATH = $path;
    }
    if ($globalLanguage != "en" && $globalLanguage != "id") {
      $dataLanguage = "en";
    } else {
      $dataLanguage = $globalLanguage;
    }
    $this->CSSFileName = preg_replace("/[<>]/", '/', $CLASSDATAGRIDPATH . "/css/" . $DATAGRID_CSS . ".css");
    $this->CSSFileNameExcel = preg_replace("/[<>]/", '/', $CLASSDATAGRIDPATH . "/css/" . $DATAGRID_CSS . ".excel.css");
    $this->JSFileName = preg_replace("/[<>]/", '/', $CLASSDATAGRIDPATH . "/scripts/datagrid_" . $dataLanguage . ".js");
    if (!is_file($this->CSSFileName)) {
      die("SKIN/CSS for datagrid was not found, please check your SETTING below in datagrid.config.php:<br />
- \$CLASSDATAGRIDPATH<br />
- \$DATAGRID_CSS");
    }
    if (!is_file($this->CSSFileNameExcel)) {
      $this->CSSFileNameExcel = $this->CSSFileName;
    }
    if (!is_file($this->JSFileName)) {
      die("Javascript for datagrid was not found, please check your SETTING below in datagrid.config.php:<br />
- \$CLASSDATAGRIDPATH");
    }
    $imagePath = preg_replace("/[<>]/", '/', $CLASSDATAGRIDPATH . "/css/" . $DATAGRID_CSS . "/");
    $this->imageList = [
        "sort_desc"   => $imagePath . "sortDesc.gif",
        "sort_asc"    => $imagePath . "sortAsc.gif",
        "indicator"   => $imagePath . "indicator.gif",
        "left_arrow"  => $imagePath . "2leftarrow.png",
        "right_arrow" => $imagePath . "2rightarrow.png"
    ];
    $this->buttons = [];
    $this->blankSpace = "";
  }

  function _createExcelFormat(
      $color = null,
      $bgcolor = null,
      $font_size = null,
      $is_bold = false,
      $border = null,
      $h_align = null,
      $v_align = null,
      $isWrap = false
  ) {
    // buat format excel untuk header row
    $format =& $this->wkb->add_format();
    if ($color != null) {
      $format->set_color('white');
    }
    if ($bgcolor != null) {
      $format->set_pattern();
      $format->set_fg_color($bgcolor);
    }
    if ($font_size == null) {
      $font_size = $this->fontSize;
    }
    $format->set_size($this->fontSize);
    if ($is_bold) {
      $format->set_bold(1);
    }
    if (intval($border) > 0) {
      $format->set_border($border);
    }
    if ($h_align != null) {
      $format->set_align($h_align);
    }
    if ($v_align != null) {
      $format->set_align($v_align);
    }
    if ($isWrap) {
      $format->set_text_wrap(1);
    }
    return $format;
  }

  //merubah setting default paging size, default 15
  //merubah setting page limit, pilihan "all",100,50,25,20,15, ...

  function _createInputHidden()
  {
    //only print if render is normal
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
      return "";
    }
    $strResult = "";
    if ($this->isShowPageNumbering) {
      $strResult .= "
    <input type=\"hidden\" id=\"" . "pageNumber" . $this->gridName . "\" name=\"" . "pageNumber" . $this->gridName . "\" value=\"" . $this->pageNumber . "\">
    <input type=\"hidden\" id=\"" . "pageJump" . $this->gridName . "\" name=\"" . "pageJump" . $this->gridName . "\" value=\"" . $this->jumpPage . "\">
    <input type=\"hidden\" id=\"" . "pageCount" . $this->gridName . "\" name=\"" . "pageCount" . $this->gridName . "\" value=\"" . $this->pageCount . "\">";
    }
    if ($this->isShowSort) {
      $strResult .= "
    <input type=\"hidden\" id=\"" . "pageSortBy" . $this->gridName . "\" name=\"" . "pageSortBy" . $this->gridName . "\" value=\"" . $this->pageSortBy . "\">";
    }
    return $strResult;
  }

  //merubah setting page limit, defaultnya 10

  function _disposeWorkBook()
  {
    $this->wkb->close();
    $this->wkb = null;
    $this->sheet = null;
    $this->intExcelRows = 0;
  }

  //jika fungsi ini dipanggil maka datagrid tidak akan me-render tag <form....
  //pastikan jika anda memanggil fungsi ini, anda telah menyiapkan tag <form anda sendiri
  //kalo tidak semua fungsi sort, search, jump page, dll tidak berfungsi.

  function _drawJavascript()
  {
    $strResult = "";
    if (!$GLOBALS['PROTOTYPE_LOADED']) {
      $GLOBALS['PROTOTYPE_LOADED'] = true;
      $strResult .= "
  <script type=\"text/javascript\" src=\"" . $GLOBALS['CLASSDATAGRIDPATH'] . "scripts/datagrid_prototype.js" . "\"></script>";
    }
    $strResult .= "
  <script type=\"text/javascript\" src=\"" . $GLOBALS['CLASSDATAGRIDPATH'] . "scripts/tablekit.js\"></script>
  <script type=\"text/javascript\" src=\"" . $this->JSFileName . "\"></script>
  <script type=\"text/javascript\">
    var specialButton_" . $this->gridName . " = new Array();";
    $counter = 0;
    foreach ($this->buttons as $button) {
      if ($button['special']) {
        $strResult .= "
    specialButton_" . $this->gridName . "[" . $counter . "] = '" . $button['id'] . "';";
        $counter++;
      }
    }
    $useAJAXTechnology = ($this->useAJAXTechnology) ? "true" : "false";
    $strResult .= "
    o" . $this->gridName . " = new myDatagrid('" . $this->scriptFileName . "', '" . $this->formNameId . "', '" . $this->gridName . "', " . count(
            $this->dataset
        ) . ", specialButton_" . $this->gridName . ", " . $useAJAXTechnology . ");
    o" . $this->gridName . ".loadGrid();

    if ($('" . $this->gridName . "').tBodies[0])
    {
      var aRows = $('" . $this->gridName . "').tBodies[0].rows;
      var nRows = aRows.length;
      var currClassName = '';
      for(var i=0;i<nRows;i++)
      {
        currClassName = aRows[i].className;
        //aRows[i].onmouseover = function() { this.className = this.className + 'Hover'; };
        //aRows[i].onmouseout  = function() { this.className = this.className.replace('Hover', ''); };
      }
    }";
    if (count($this->buttons) > 0) {
      foreach ($this->buttons as $button) {
        if ($button['special'] && (count($this->dataset) == 0)) {
          continue;
        }
        $strResult .= "$('" . $button['name'] . "').onmouseover = function() { this.className = this.className + 'Hover' };";
        $strResult .= "$('" . $button['name'] . "').onmouseout = function() { this.className = this.className.replace('Hover', '') };";
      }
    }
    $strResult .= "
  </script>";
    return $strResult;
  }

  //masukkan CSS file anda disini

  function _drawProgressBarIndicator()
  {
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
      return "";
    }
    global $DATAGRIDWORDS;
    return "
    <div id=\"divIndicatorGrid\" class=\"indicator\" style=\"display : none\">
      <img src=\"" . $this->imageList['indicator'] . "\" align=\"left\" width=\"16\" height=\"16\" border=\"0\">
      <span id=\"textGrid\">" . $DATAGRIDWORDS['please wait'] . "<br />" . $DATAGRIDWORDS['loading grid'] . "...</span>
    </div>";
  }

  //fungsi ini digunakan jika anda ingin menambahkan sesuatu untuk direpeat setiap row (TR)
  //disisipkan dibawah dari row 1 dan seterusnya
  //misal anda ingin menambahkan TR dengan display none bisa anda lakukan disini juga

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

  //this function only draw colspan header

  function _formatFieldName($fieldName)
  {
    if (defined("DB_TYPE")) {
      if (DB_TYPE == "mssql") {
        $arrField = explode(",", $fieldName);
        $arrResult = [];
        foreach ($arrField as $field) {
          $field = trim($field);
          if (strpos($field, ".") === false) {
            $arrResult[] = "[" . str_replace(["[", "]"], "", $field) . "]";
          } else {
            $arrFields = explode(".", $field);
            if (count($arrFields) == 2) {
              $arrResult[] = $arrFields[0] . "." . "[" . str_replace(["[", "]"], "", $arrFields[1]) . "]";
            } else {
              $arrResult[] = $field;
            }
          }
        }
        return implode(",", $arrResult);
      } else if (DB_TYPE == "mysql") {
        $arrField = explode(",", $fieldName);
        $arrResult = [];
        foreach ($arrField as $field) {
          $field = trim($field);
          if (strpos($field, ".") === false) {
            $arrResult[] = "`" . str_replace("`", "", $field) . "`";
          } else {
            $arrFields = explode(".", $field);
            if (count($arrFields) == 2) {
              $arrResult[] = $arrFields[0] . "." . "`" . str_replace("`", "", $arrFields[1]) . "`";
            } else {
              $arrResult[] = $field;
            }
          }
        }
        return implode(",", $arrResult);
      }
    }
    //DEFAULT POSTGRES;
    $arrField = explode(",", $fieldName);
    $arrResult = [];
    foreach ($arrField as $field) {
      $field = trim($field);
      if (strpos($field, ".") === false) {
        $arrResult[] = "\"" . str_replace("\"", "", $field) . "\"";
      } else {
        $arrFields = explode(".", $field);
        if (count($arrFields) == 2) {
          $arrResult[] = $arrFields[0] . "." . "\"" . str_replace("\"", "", $arrFields[1]) . "\"";
        } else {
          $arrResult[] = $field;
        }
      }
    }
    return implode(",", $arrResult);
  }

  function _formatOrderBy($pageSortBy, $escapeChar = "\"")
  {
    $arrOrder = explode(",", $pageSortBy);
    $arrResult = [];
    foreach ($arrOrder as $strOrder) {
      $arrSingleOrder = explode(" ", trim($strOrder));
      if (strpos($arrSingleOrder[0], ".") === false) {
        $strResult = $escapeChar . str_replace($escapeChar, "", $strOrder) . $escapeChar;
      } else {
        //jika ada $escapeChar
        $arrSingleOrderFields = explode(".", $arrSingleOrder[0]);
        if (count($arrSingleOrderFields) == 2) {
          $strResult = $arrSingleOrderFields[0] . "." . $escapeChar . str_replace(
                  $escapeChar,
                  "",
                  $strOrder
              ) . $escapeChar;
        } else {
          $strResult = $arrSingleOrder[0];
        }
      }
      if (count($arrSingleOrder) > 1) {
        $strResult .= " " . $arrSingleOrder[1];
      }
      $arrResult[] = $strResult;
    }
    if (count($arrResult) > 0) {
      return implode(", ", $arrResult);
    } else {
      return "";
    }
  }

  function _formatter($par, $record, $counter, $fieldName)
  {
    // Define the Parameter list
    $paramList = [];
    //set reserved word untuk $record dan $counter
    //$record digunakan untuk mengakses record tiap row
    //$counter adalah auto nomor urut
    $paramList['record'] = $record;
    $paramList['counter'] = $counter;
    $paramList['value'] = (isset($record[$fieldName])) ? $record[$fieldName] : "";
    $paramList['field'] = $fieldName;
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
      $result = call_user_func($_formatter, $paramList);
    } else {
      $result = false;
    }
    return $result;
  }

  function _getCellFormat($col, $backgroundColor = "")
  {
    $strGeneratedFormatName = "";
    if (isset($col->attribs['align'])) {
      $strAlign = strtolower($col->attribs['align']);
      if ($strAlign != 'center' && $strAlign != 'left' && $strAlign != 'right') {
        $strAlign = '';
      }
      $strGeneratedFormatName .= "format" . $col->dataType . $strAlign . $backgroundColor;
    } else {
      $strAlign = '';
      $strGeneratedFormatName .= "format" . $col->dataType . $backgroundColor;
    }
    if (!isset($GLOBALS[$strGeneratedFormatName])) {
      $GLOBALS[$strGeneratedFormatName] =& $this->wkb->add_format();
      $GLOBALS[$strGeneratedFormatName]->set_size($this->fontSize);
      $GLOBALS[$strGeneratedFormatName]->set_border(1);
      if ($backgroundColor != '') {
        $GLOBALS[$strGeneratedFormatName]->set_pattern();
        $GLOBALS[$strGeneratedFormatName]->set_fg_color($backgroundColor);
      }
      if ($col->dataType == "numeric" || $col->dataType == "currency") {
        $GLOBALS[$strGeneratedFormatName]->set_align('right');
        $GLOBALS[$strGeneratedFormatName]->set_num_format(3);
      }
      if ($col->dataType == "integer") {
        $GLOBALS[$strGeneratedFormatName]->set_align('right');
      } else if ($col->dataType == "date") {
        $GLOBALS[$strGeneratedFormatName]->set_align('center');
        $GLOBALS[$strGeneratedFormatName]->set_num_format("dd MMM yyyy");
      }
      if ($strAlign != '') {
        $GLOBALS[$strGeneratedFormatName]->set_align($strAlign);
      }
    }
    return $GLOBALS[$strGeneratedFormatName];
  }

  function _getDefaultField()
  {
    $strResult = "";
    if (count($this->columnSet) > 0) {
      foreach ($this->columnSet as $col) {
        if ($col->fieldName != '' && $col->sortable) {
          $strResult = $col->fieldName;
          break;
        }
      }
    }
    return $strResult;
  }

  function _getRecordSet($db, $strSQL)
  {
    $res = $db->execute($strSQL);
    $arrResult = [];
    while ($rowDb = $db->fetchrow($res)) {
      $arrResult[] = $rowDb;
    }
    return $arrResult;
  }

  function _grandTotalAccumulation($lastGroupBy)
  {
    //accumulate sub total to grand total
    if ($this->hasGrandTotal) {
      foreach ($this->columnSet as $value) {
        if ($value->grouped && !$value->isTotalInformation) {
          $strValueSubTotal = $this->summaryData[$lastGroupBy][$value->groupField];
          if (!isset($this->summaryData['_GRANDTOTAL_'][$value->groupField])) {
            if (is_numeric($strValueSubTotal)) {
              $this->summaryData['_GRANDTOTAL_'][$value->groupField] = 0;
            } else {
              $this->summaryData['_GRANDTOTAL_'][$value->groupField] = "";
            }
          }
          if (is_numeric($strValueSubTotal)) {
            $this->summaryData['_GRANDTOTAL_'][$value->groupField] += $strValueSubTotal;
          }
        } else if ($value->isTotalInformation) {
          $this->summaryData['_GRANDTOTAL_'][$value->groupField] = strtoupper("grand total");
        }
      }
    }
  }

  function _initWorkBook()
  {
    $this->wkb = new workbook("-");
    //create custom color index 50
    $this->wkb->set_custom_color(50, 200, 200, 200);
    // Add a worksheet to the file, returning an object to add data to
    $this->sheet =& $this->wkb->add_worksheet('Data');
    // buat objek workbook dan worksheet
    $this->intExcelRows = 0;
  }

  function _printCSS()
  {
    $strResult = "";
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
      if ($this->height != "100%" && $this->height != "") {
        $strResult .= "
<style>
#" . $this->gridName . ">tbody
{
  overflow: auto;
  height: " . str_replace("px", "", $this->height) . "px;
  overflow-x: hidden;
}
</style>";
      }
      $CSSStyleFileName = preg_replace("/[<>]/", '/', $GLOBALS['CLASSDATAGRIDPATH'] . "/css/style.css");
      if ($this->CSSFileName !== "") {
        if (is_file($this->CSSFileName)) {
          $strResult .= "\n<link href=\"" . $this->CSSFileName . "\" rel=\"stylesheet\" type=\"text/css\">";
        }
      }
      $strResult .= "\n<link href=\"" . $CSSStyleFileName . "\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
    } else if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
      $strResult = "<html><head><style>" . file_get_contents($this->CSSFileNameExcel) . "</style></head><body>";
    }
    return $strResult;
  }

  function _printCheckbox($name, $data, $counter)
  {
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
      return "<input class=c type=\"checkbox\" name=\"" . $this->checkboxItemID . $counter . "\" id=\"" . $this->checkboxItemID . $counter . "\"  value=\"" . $data . "\" onClick=\"o" . $this->gridName . ".itemListClicked(this)\">";
    } else {
      return "";
    }
  }

  function _printCheckboxAll()
  {
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
      return "<input class=c type=\"checkbox\" name=\"" . $this->gridName . "_chkAll\" id=\"" . $this->gridName . "_chkAll\"  onClick=\"o" . $this->gridName . ".checkAllClicked(this)\">";
    } else {
      return "";
    }
  }

  function _printCheckboxAllBottom()
  {
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
      return "<input class=c type=\"checkbox\" name=\"" . $this->gridName . "_chkAllBottom\" id=\"" . $this->gridName . "_chkAllBottom\"  onClick=\"o" . $this->gridName . ".checkAllClicked(this)\">";
    } else {
      return "";
    }
  }

  function _printClosingTableContent()
  {
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
      return "";
    }
    return "
    </table>";
  }

  //draw/render html code

  function _printClosingTableDataGrid()
  {
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
      return "";
    }
    return "
          </table>
        </td>
      </tr>";
  }

  /*you can overrides this function to create your own TR class or style*/

  function _printDatagridCaptionExcel()
  {
    if ($this->strTitle1 != '') {
      $formatCaption1 =& $this->wkb->add_format();
      $formatCaption1->set_size($this->fontSize + 2);
      $formatCaption1->set_bold(1);
      $formatCaption1->set_align('vcenter');
      $this->sheet->write_string($this->intExcelRows++, 0, $this->strTitle1, $formatCaption1);
    }
    if ($this->strTitle2 != '') {
      $formatCaption2 =& $this->wkb->add_format();
      $formatCaption2->set_size($this->fontSize + 1);
      $formatCaption2->set_bold(1);
      $formatCaption2->set_align('vcenter');
      $this->sheet->write_string($this->intExcelRows++, 0, $this->strTitle2, $formatCaption2);
    }
    if ($this->strTitle3 != '') {
      $formatCaption3 =& $this->wkb->add_format();
      $formatCaption3->set_size($this->fontSize);
      $formatCaption3->set_bold(1);
      $formatCaption3->set_align('vcenter');
      $this->sheet->write_string($this->intExcelRows++, 0, $this->strTitle3, $formatCaption3);
    }
  }

  function _printDataset()
  {
    $strResult = "";
    $intRows = 0;
    if (is_numeric($this->pageLimit) && $this->pageNumber > 0) {
      $intRows += $this->pageLimit * ($this->pageNumber - 1);
    }
    $currGroupBy = "--UNDEFINED--";
    $startNumber = $intRows;
    $intNomor = $startNumber;
    $isFirst = true;
    foreach ($this->dataset as $rowDb) {
      $intRows++;
      //print TR
      if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
        //render opening TR
        $strResult .= $this->printOpeningRow($intRows, $rowDb);
      }
      //manage group by accumulation data
      if ($this->hasGroupBy) {
        $lastGroupBy = $currGroupBy;
        $currGroupBy = $rowDb[$this->strGroupByField];
        if (!$isFirst) {
          if ($lastGroupBy != $currGroupBy) {
            //accumulate sub total to grand total
            $this->_grandTotalAccumulation($lastGroupBy);
            //draw group by row
            $strResult .= $this->_renderGroupByRow($lastGroupBy);
            if ($this->resetNumbering) {
              $intNomor = $startNumber;
            }
          }
        } else {
          $isFirst = false;
        }
      }
      $this->intExcelRows++;
      $intNomor++;
      $counter = 0;
      foreach ($this->columnSet as $idx => $value) {
        //do not print data if it is spanned column and no fieldName and no item _formatter
        if (($value->fieldName === "" || $value->fieldName == null) &&
            ($value->intColSpan > 1) &&
            ($value->item_formatter === "" || $value->item_formatter == null)
        ) {
          continue;
        }
        if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML) {
          if (!$value->showInExcel) {
            continue;
          }
        }
        if ($value->item_formatter != "") {
          switch ($value->item_formatter) {
            case "showItemNumber" :
              //print auto numbering
              $strValue = $intNomor;
              $strValueSummary = 1;
              break;
            case "_printCheckbox" :
              $strValue = $this->_printCheckbox($value->columnName, $rowDb[$value->fieldName], $intRows);
              $strValueSummary = $strValue;
              break;
            default:
              if (isset($rowDb[$value->fieldName]) && is_numeric($rowDb[$value->fieldName])) {
                $strValue = $this->_formatter($value->item_formatter, $rowDb, $intRows, $value->fieldName);
                $strValueSummary = $rowDb[$value->fieldName];
              } else {
                $strValue = $this->_formatter($value->item_formatter, $rowDb, $intRows, $value->fieldName);
                $strValueSummary = $strValue;
              }
          }
        } else {
          if ($value->fieldName != "") {
            $strValue = $rowDb[$value->fieldName];
          } else {
            $strValue = "";
          }
          $strValueSummary = $strValue;
        }
        if ($this->hasGroupBy) {
          if ($value->grouped && !$value->isTotalInformation) {
            $this->_subTotalAccumulation($currGroupBy, $value->groupField, $strValueSummary);
          } else if ($value->isTotalInformation) {
            //masukkan string pada parameter group field sebagai string/word total
            $this->_subTotalAccumulation($currGroupBy, $value->groupField, $value->groupField);
          }
        }
        if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
          //print to HTML output
          $tdAttrib = $this->_serializeAttribute($value->attribs);
          if ($strValue === "" || $strValue === null) {
            $strValue = "&nbsp;";
          }
          $strResult .= "
              <td " . $tdAttrib . ">" . $strValue . "</td>";
        } else {
          //print to excel output
          $strGeneratedFormatName = $this->_getCellFormat($value);
          switch ($value->dataType) {
            case "numeric" :
            case "currency" :
            case "integer" :
              if ($value->item_formatter == "showItemNumber") {
                $this->sheet->write_number($this->intExcelRows, $counter, $strValue, $strGeneratedFormatName);
              } else {
                $this->sheet->write_number($this->intExcelRows, $counter, $strValueSummary, $strGeneratedFormatName);
              }
              break;
            case "date" :
              $strValue = $this->convertToExcelDate($strValue);
              if (is_numeric($strValue)) {
                $this->sheet->write_number($this->intExcelRows, $counter, $strValue, $strGeneratedFormatName);
              } else {
                $this->sheet->write_string($this->intExcelRows, $counter, $strValue, $strGeneratedFormatName);
              }
              break;
            case "string" :
              $this->sheet->write_string($this->intExcelRows, $counter, $strValue, $strGeneratedFormatName);
              break;
            default :
              $this->sheet->write($this->intExcelRows, $counter, $strValue, $strGeneratedFormatName);
          }
          $counter++;
        }
      }//end of foreach($this->columnSet as $idx => &$value)
      if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
        $strResult .= "
            </tr>";
      }
      //check is it have $repeaterFunction to draw?
      if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
        if ($this->repeaterFunction != "") //call to repeater function defined on client
        {
          $strResult .= $this->_formatter($this->repeaterFunction, $rowDb, $intRows, $value->fieldName);
        }
      }
    } //foreach ($this->dataset as &$rowDb)
    if ($this->hasGroupBy) {
      //if not yet rendered for the subtotal 
      if (!$isFirst) {
        $lastGroupBy = $currGroupBy;
        //accumulate sub total to grand total
        $this->_grandTotalAccumulation($lastGroupBy);
        $strResult .= $this->_renderGroupByRow($lastGroupBy);
        //render grand total
        $strResult .= $this->_renderGrandTotal();
      }
    }
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
      $strResult = "
            <!-- grid data -->
            <tbody>" . $strResult . "
            </tbody>
            <!-- end of grid data -->";
      return $strResult;
    } else {
      return "";
    }
  } // showData

  //accumulate all sub total to GRAND TOTAL

  function _printGridButtons()
  {
    $strResult = "";
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
      $colSpan = count($this->columnSet);
      if ($this->hasCheckbox && (count($this->dataset) > 0)) //have checkbox
      {
        $strResult .= "
            <!-- grid footer -->
            <tfoot>
            <tr>
              <td align=\"center\">" . $this->_printCheckboxAllBottom() . "</td>
              <td colspan=\"" . ($colSpan - 1) . "\">";
      } else //don't have checkbox
      {
        $strResult .= "
            <!-- grid footer -->
            <tfoot>
            <tr>
              <td colspan=\"" . $colSpan . "\">";
      }
      $counter = 0;
      if (count($this->buttons) > 0) {
        foreach ($this->buttons as $button) {
          if ($button['special'] && (count($this->dataset) == 0)) {
            continue;
          }
          $counter++;
          if ($button['class'] == "") {
            $className = "";
          } else {
            $className = "class=\"" . $button['class'] . "\"";
          }
          $strResult .= "
              <input " . $className . " name=\"" . $button['name'] . "\" type=\"" . $button['type'] . "\" id=\"" . $button['id'] . "\" value=\"" . $button['value'] . "\" " . $button['clientAction'] . ">&nbsp;";
        }
      }
      if ($counter == 0) {
        return "";
      }
      $strResult .= "&nbsp;</td>
            </tr>
            </tfoot>
            <!-- end of grid footer -->";
    }
    return $strResult;
  }

  //paramater:
  //1.  Group by name, 2. Group field, 3. value to be calculated

  function _printGridHeaderRow()
  {
    $strResult = "";
    $arrTempColumn = [];
    //set deep level, get maximum deep level, and set positioning of column
    $maxDeepLevel = $this->_setHeaderDeepLevel();
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
      // buat format excel untuk header row
      $this->formatTableHeader =& $this->_createExcelFormat(null, 50, null, true, 1, 'center', 'vcenter', 1);
    }
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
      $strResult .= "
        <!-- grid header -->
        <thead>";
    }
    for ($level = 1; $level <= $maxDeepLevel; $level++) {
      if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
        $strResult .= "
          <tr align=\"center\">";
      }
      foreach ($this->columnSet as $col) {
        //only print at the same level
        if ($col->intDeepLevel != $level) {
          continue;
        }
        $intRowPos = $this->intExcelRows + $level - 1;
        if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
          if (!$col->showInExcel) {
            continue;
          }
        }
        //if column has title _formatter then do this....
        if ($col->title_formatter != "") {
          switch ($col->title_formatter) {
            case "_printCheckboxAll" :
              $strValue = $this->_printCheckboxAll();
              break;
            default :
              $strValue = $this->_formatter($col->title_formatter, null, null, null);
          }
          //sorry, not sorting capability for header of type formatter
          $col->sortable = false;
          $col->columnName = $strValue;
          $col->isDatabaseField = false;
          $strResult .= $this->_printHeaderColumn($col, $intRowPos);
        } else {
          //just print the title/header
          //----------------------------------
          $strResult .= $this->_printHeaderColumn($col, $intRowPos);
        }
      }
      if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
        $strResult .= "
          </tr>";
      }
    }
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
      $strResult .= "
          </thead>
          <!-- end of grid header -->";
    }
    $this->intExcelRows += $maxDeepLevel - 1;
    return $strResult;
  }

  function _printGridItemRow()
  {
    $colSpan = count($this->columnSet);
    $strResult = "";
    if (count($this->dataset) == 0) {
      $strResult .= $this->_printNoItem();
    } else {
      $strResult .= $this->_printDataset();
    }
    return $strResult;
  }

  function _printHeaderColumn($col, $intRowPos)
  {
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
      $this->_printHeaderColumnExcel($col, $intRowPos);
      return "";
    }
    $strEvents = "";
    $strImage = "";
    if (($this->isShowSort) && ($col->fieldName != '') && ($col->sortable)) {
      if ($this->sortName == $col->fieldName) {
        $strImage = $this->imageName;
      }
    }
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
      if (($this->isShowSort) && ($col->fieldName != '') && ($col->sortable)) {
        $strEvents .= " onClick=\"javascript:o" . $this->gridName . ".goSort('" . $col->fieldName . "')\" title=\"Sort by " . str_replace(
                ["<br />", "<br>", "<br/>"],
                " ",
                $col->columnName
            ) . "\" ";
        $strEvents .= " onMouseOver=\"this.className='Hover'\" onMouseOut=\"this.className=''\" ";
        $strValue = "<div class='s'>" . $col->columnName . $strImage . "</div>";
      } else {
        $strValue = $col->columnName;
      }
    } else {
      $strValue = $col->columnName;
    }
    //serialize attribute
    $strResult = "";
    $tdAttrib = $this->_serializeAttribute($col->titleAttribs);
    if ($col->intColSpan > 1) {
      $strResult .= "
          <th class='spannedCol' " . $tdAttrib . ">" . nl2br($strValue) . "</th>";
    } else {
      if ($col->isDatabaseField && $col->fieldName != "") {
        $strResult .= "
          <th id='hdrGrid" . $col->fieldName . "' " . $tdAttrib . $strEvents . ">" . nl2br($strValue) . "</th>";
      } else {
        $strResult .= "
          <th " . $tdAttrib . $strEvents . ">" . nl2br($strValue) . "</th>";
      }
    }
    return $strResult;
  }

  function _printHeaderColumnExcel($col, $intRowPos)
  {
    if ($col->xlsColumnWidth > 0) {
      $this->sheet->set_column($col->intColumnPosition, $col->intColumnPosition, $col->xlsColumnWidth);
    }
    if ($col->showInExcel) {
      $this->sheet->write_string($intRowPos, $col->intColumnPosition, $col->columnName, $this->formatTableHeader);
    }
    //merged cell if any...
    if ($col->intRowSpan > 1 || $col->intColSpan > 1) {
      if ($col->intRowSpan == 0) {
        $col->intRowSpan = 1;
      }
      if ($col->intColSpan == 0) {
        $col->intColSpan = 1;
      }
      for ($i = 0; $i < $col->intRowSpan; $i++) {
        for ($j = 0; $j < $col->intColSpan; $j++) {
          if ($i != 0 || $j != 0) {
            $this->sheet->write_blank($intRowPos + $i, $col->intColumnPosition + $j, $this->formatTableHeader);
          }
        }
      }
      $this->sheet->merge_cells(
          $intRowPos,
          $col->intColumnPosition,
          ($intRowPos + $col->intRowSpan - 1),
          ($col->intColumnPosition + $col->intColSpan - 1)
      );
    }
  }

  function _printMessageRow()
  {
    //skip if the output is to EXCEL
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
      return "";
    }
    $strResult = "";
    if (trim($this->message) != "") {
      $strMessage = trim($this->message);
      $strClass = "class=\"dataGridOKMessage\"";
      $strResult .= "
    <tr>
      <td $strClass id=\"datagridMessage\">$strMessage</td>
    </tr>";
    }
    if (trim($this->errorMessage) != "") {
      $strClass = "class=\"dataGridErrorMessage\"";
      $strMessage = trim($this->errorMessage);
      $strResult .= "
    <tr>
      <td $strClass id=\"datagridMessage\">$strMessage</td>
    </tr>";
    }
    return $strResult;
  }

  function _printNoItem()
  {
    //if no data
    $strResult = "";
    $colSpan = count($this->columnSet);
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
      $strResult .= "
          <!-- grid data -->
          <tbody>
          <tr valign=\"top\">
            <td colspan=\"" . $colSpan . "\">" . $GLOBALS['DATAGRIDWORDS']['no data'] . "</td>
          </tr>
          </tbody>
          <!-- end of grid data -->";
    }
    return $strResult;
  }

  function _printOpeningTableContent()
  {
    //skip if output is to excel BIFF WRITER
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
      return "";
    }
    return "
    <table class=\"contentGrid\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"" . $this->width . "\">";
  }

  //input any pageSortBy string form
  //eg : string: "id DESC, name, dept DESC"

  function _printOpeningTableDataGrid()
  {
    //skip if output is to excel BIFF WRITER
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
      return "";
    }
    return "
      <tr>
        <td valign=\"top\">
          <table id=\"" . $this->gridName . "\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"" . $this->gridClass . " resizable\">";
  }

function _printPaging($jumpPageType = 0, $intPage = 1, $intTotal = 1)
  {
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
      return "";
    }
    global $DATAGRIDWORDS;
    //if no paging exit this function
    if (!$this->isShowPageNumbering) {
      return "";
    }
    if ((is_numeric($this->pageLimit)) && ($this->pageLimit > 0) && $this->isShowPageLimit) {
      $intRowsLimit = $this->pageLimit;
      if ($intRowsLimit == 0) {
        $intRowsLimit = 1;
      }
      $strResult = "
          <table class=\"pagingGrid\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">
            <tr>
              <td nowrap width=\"32\"><strong>&nbsp;" . $DATAGRIDWORDS['page'] . "&nbsp;&nbsp;</strong></td>";
      // cari jumlah halaman
      $intTotalPage = ceil($intTotal / $intRowsLimit);
      //set start page numbering
      if ($intPage <= 0) {
        $intPage = 1;
      }
      if ($jumpPageType != '0')
        //click from jump page not from normal navigation
        // (jump 10 page default, customizable, call setPagingSize method to set other than 10 page jump)
      {
        $intPageStart = $intPage;
      } else if (($intPage % $this->defaultPagingSize) == 0) {
        $intPageStart = ((($intPage / $this->defaultPagingSize) - 1) * $this->defaultPagingSize) + 1;
      } // + ($intPage % $this->defaultPagingSize);
      else {
        $intPageStart = ((floor($intPage / $this->defaultPagingSize)) * $this->defaultPagingSize) + 1;
      } // + ($intPage % $this->defaultPagingSize);
      //$intPageStart = floor($intPageStart / $this->defaultPagingSize) * $this->defaultPagingSize + 1;
      if ($intPageStart > $this->defaultPagingSize) {
        //draw previous jump button
        $intPageStartPrev = $intPageStart - $this->defaultPagingSize;
        if ($intPageStartPrev < 0) {
          $intPageStartPrev = 1;
        }
        if (is_file($this->imageList['left_arrow'])) {
          $pagingPrev = "
                <td align=\"center\" width=30><a href=\"javascript:o" . $this->gridName . ".goPageStart('" . $intPageStartPrev . "')" . "\" title='" . vsprintf(
                  $DATAGRIDWORDS['jump to %d of previous pages'],
                  $this->defaultPagingSize
              ) . "'><img src='" . $this->imageList['left_arrow'] . "' border=0 width=16 height=16></a></td>";
        } else {
          $pagingPrev = "
                <td align=\"center\" width=30><a href=\"javascript:o" . $this->gridName . ".goPageStart('" . $intPageStartPrev . "')" . "\" title='" . vsprintf(
                  $DATAGRIDWORDS['jump to %d of previous pages'],
                  $this->defaultPagingSize
              ) . "'>&lt;&lt;</a></td>";
        }
      } else {
        $pagingPrev = "
                <td width=30 valign=middle>&nbsp;</td>";
      }
      //set finish page numbering
      $intPageFinish = $intTotalPage;//$intPageStart + $this->defaultPagingSize - 1;
      if (($intPageFinish - $intPageStart) > $this->defaultPagingSize) {
        //draw next jump button
        $intPageFinish = $intPageStart + $this->defaultPagingSize - 1;
        if (is_file($this->imageList['right_arrow'])) {
          $pagingNext = "
                <td align=\"center\" width=30>
                  <a href=\"javascript:o" . $this->gridName . ".goPageStart('" . ($intPageFinish + 1) . "')" . "\" title='" . vsprintf(
                  $DATAGRIDWORDS['jump to %d of next pages'],
                  $this->defaultPagingSize
              ) . "'><img src='" . $this->imageList['right_arrow'] . "' border=0 width=16 height=16></a>
                </td>";
        } else {
          $pagingNext = "
                <td align=\"center\" width=30><a href=\"javascript:o" . $this->gridName . ".goPageStart('" . ($intPageFinish + 1) . "')" . "\" title='" . vsprintf(
                  $DATAGRIDWORDS['jump to %d of next pages'],
                  $this->defaultPagingSize
              ) . "'>&gt;&gt;</a></td>";
        }
      } else {
        $pagingNext = "
                <td width=30 valign=middle>&nbsp;</td>";
      }
      if ($intPageStart < 1) {
        $intPageStart = 1;
      }
      //this one is to adjust paging number
      //misal; dari page 10 pada size 15 per page dengan total data 10 page,  akan otomatis menjadi page 8 pada size 20 per page
      if ($intPageFinish > $intTotalPage) {
        $intPageFinish = $intTotalPage;
      }
      if ($intPage > $intPageFinish) {
        $intPage = $intPageFinish;
      }
      // tambahkan link untuk prev dan first page
      $strLink = "javascript:o" . $this->gridName . ".goPage('[PAGE]')";
      $strResult .= $pagingPrev;
      for ($i = $intPageStart; $i <= $intPageFinish; $i++) {
        if ($i == $intPage) {
          $strResult .= "
              <td align=\"center\" width=\"20\" class=\"currentPage\">$i</td>";
        } else {
          $strResult .= "
              <td align=\"center\" width=\"20\"><a href=\"" . str_replace("[PAGE]", $i, $strLink) . "\">$i</a></td>";
        }
      }
      $strResult .= $pagingNext;
      $strResult .= "
              <td align=\"center\">[ " . $DATAGRIDWORDS['page'] . " " . number_format(
              $intPage,
              0,
              '',
              ','
          ) . " " . $DATAGRIDWORDS['of'] . " " . number_format(
              $intTotalPage,
              0,
              '',
              ','
          ) . " ]&nbsp;&nbsp;&nbsp;Total : " . number_format(
              $this->totalData,
              0,
              '',
              ','
          ) . " " . $DATAGRIDWORDS['records'] . "</td>";
      if ($intPage > 1) {
        $strResult .= "
              <td nowrap align=\"center\" width=\"20\">
                &nbsp;<a href=\"" . str_replace(
                "[PAGE]",
                "1",
                $strLink
            ) . "\" title=\"" . $DATAGRIDWORDS['go to'] . " " . $DATAGRIDWORDS['first page'] . "\">" . $DATAGRIDWORDS['first'] . "</a>&nbsp;
              </td>
              <td nowrap align=\"center\" width=\"20\">
                &nbsp;<a href=\"" . str_replace(
                "[PAGE]",
                ($intPage - 1),
                $strLink
            ) . "\" title=\"" . $DATAGRIDWORDS['go to'] . " " . $DATAGRIDWORDS['previous page'] . "\">" . $DATAGRIDWORDS['previous'] . "</a>&nbsp;";
      } else {
        $strResult .= "
              <td nowrap align=\"center\" width=\"20\" class=\"disabledNavigation\">
                &nbsp;" . $DATAGRIDWORDS['first'] . "&nbsp;
              </td>
              <td nowrap align=\"center\" width=\"20\" class=\"disabledNavigation\">
                &nbsp;" . $DATAGRIDWORDS['previous'] . "&nbsp;";
      }
      // tambahkan link next dan last page
      if ($intPage < $intTotalPage) {
        $strResult .= "
              </td>
              <td nowrap align=\"center\" width=\"20\">
                &nbsp;<a href=\"" . str_replace(
                "[PAGE]",
                ($intPage + 1),
                $strLink
            ) . "\" title=\"" . $DATAGRIDWORDS['go to'] . " " . $DATAGRIDWORDS['next page'] . "\">" . $DATAGRIDWORDS['next'] . "</a>&nbsp;
              </td>
              <td nowrap align=\"center\" width=\"20\">
                &nbsp;<a href=\"" . str_replace(
                "[PAGE]",
                $intTotalPage,
                $strLink
            ) . "\" title=\"" . $DATAGRIDWORDS['go to'] . " " . $DATAGRIDWORDS['last page'] . "\">" . $DATAGRIDWORDS['last'] . "</a>&nbsp;";
      } else {
        $strResult .= "
              </td>
              <td nowrap align=\"center\" width=\"20\" class=\"disabledNavigation\">
                &nbsp;" . $DATAGRIDWORDS['next'] . "&nbsp;
              </td>
              <td nowrap align=\"center\" width=\"20\" class=\"disabledNavigation\">
                &nbsp;" . $DATAGRIDWORDS['last'] . "&nbsp;";
      }
      $strResult .= "
              </td>
            </tr>
          </table>";
      if ($strResult == "") {
        $strResult = "          &nbsp;" . $DATAGRIDWORDS['page'] . " 1&nbsp;";
      }
      $strResult2 = "
      <tr>
        <td valign=\"top\" height=\"10\">";
      $strResult2 .= $strResult;
      $strResult2 .= "
        </td>
      </tr>";
      return $strResult2;
    } else {
      $strResult = "
      <tr>
        <td valign=\"top\" height=\"10\">
          <table class=\"pagingGrid\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">
            <tr>
              <td nowrap align=\"left\">
                &nbsp;Total : " . number_format($this->totalData, 0, '', ',') . " " . $DATAGRIDWORDS['records'] . "
              </td>
            </tr>
          </table>
        </td>
      </tr>";
      return $strResult;
    }
  }

  //this function will get last $_REQUEST and action that call before

  function _printSearchBarAndCaptionRow()
  {
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
      $this->_printDatagridCaptionExcel();
      return "";
    }
    $strResult = "";
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
      if ($this->isShowPageLimit) {
        $strResult .= "
              <td width=280>&nbsp;
                <input type=\"hidden\" id=\"" . "pageLimit" . $this->gridName . "\" name=\"" . "pageLimit" . $this->gridName . "\" value=\"" . $this->pageLimit . "\">";
        //render page limit
        $pageLink = "";
        //set default page limit to all if no pageLimit defined
        if ($this->pageLimit == "") {
          $this->pageLimit = $GLOBALS['ARRAY_PAGE_LIMIT'][0];
        }//"all";
        foreach ($GLOBALS['ARRAY_PAGE_LIMIT'] as $pageLimitValue) {
          if ($pageLink == "") {
            if ($this->pageLimit == $pageLimitValue) {
              $pageLink .= $GLOBALS['DATAGRIDWORDS']["view"] . ": $pageLimitValue";
            } else {
              $pageLink .= $GLOBALS['DATAGRIDWORDS']["view"] . ": <a href=\"javascript:o" . $this->gridName . ".setPageSize('" . $pageLimitValue . "')\">" . $pageLimitValue . "</a>";
            }
          } else if ($this->pageLimit == $pageLimitValue) {
            $pageLink .= " | $pageLimitValue";
          } else {
            $pageLink .= " | <a href=\"javascript:o" . $this->gridName . ".setPageSize('" . $pageLimitValue . "')\">" . $pageLimitValue . "</a>";
          }
        }
        $strResult .= $pageLink . "</td>";
      }
    }
    //print datagrid caption
    if (trim($this->caption) != "") {
      $strResult .= "
              <td class=\"dataGridTitle\" nowrap>" . $this->caption . "</td>";
    }
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
      if ($this->isShowSearch) {
        $strResult .= "
              <td width=300 align=right>
                <select id=\"" . "pageSearchBy" . $this->gridName . "\" name=\"" . "pageSearchBy" . $this->gridName . "\">
                  <option value=\"\">" . $GLOBALS['DATAGRIDWORDS']['any part of field'] . "</option>";
        foreach ($this->columnSet as $col) {
          if ($col->fieldName != '' && $col->searchable) {
            if ($this->pageSearchBy == $col->fieldName) {
              $strResult .= "
                  <option value=\"" . $col->fieldName . "\" selected>" . $col->columnName . "</option>";
            } else {
              $strResult .= "
                  <option value=\"" . $col->fieldName . "\">" . $col->columnName . "</option>";
            }
          }
        }
        $strResult .= "
                </select>
                <input class=\"textbox\" type=\"text\" size=\"20\" id=\"" . "pageSearchCriteria" . $this->gridName . "\" name=\"" . "pageSearchCriteria" . $this->gridName . "\" value=\"" . $this->pageSearchCriteria . "\" />
                <input title=\"Filter\" class=\"buttonSearch\" id=\"" . $this->buttonSearchName . "\" name=\"" . $this->buttonSearchName . "\" type=\"button\" value=\"\" onClick=\"javascript:o" . $this->gridName . ".search()\">
              </td>";
      }
    }
    if ($strResult != "") {
      $strResult = "
      <tr>
        <td height=\"10\" nowrap>
          <table class=\"pageLimitGrid\" width=\"100%\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\">
            <tr>" .
          $strResult . "
            </tr>
          </table>
        </td>
      </tr>";
    }
    return $strResult;
  }

  function _renderGrandTotal()
  {
    if (!$this->hasGrandTotal) {
      return "";
    }
    return $this->_renderGroupByRow("_GRANDTOTAL_");
  }

  function _renderGroupByRow($groupBy)
  {
    if (!$this->hasGroupBy) {
      return "";
    }
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
      $this->_renderGroupByRowToExcel($groupBy);
      return "";
    }
    if ($groupBy == "_GRANDTOTAL_") {
      $strResult = "<tr class=gt>";
    } else {
      $strResult = "<tr class=gs>";
    }
    $intSkip = 0;
    $intSpanned = 0;
    foreach ($this->columnSet as $idx => $value) {
      if ($intSkip > 0) {
        $intSkip--;
        if ($value->intColSpan <= 1) {
          $intSpanned++;
        }
        continue;
      }
      if ($value->grouped && !$value->isTotalInformation) {
        if ($intSpanned > 0) {
          $strResult = str_replace("[var.colspan]", "colspan=\"" . ($intSpanned) . "\"", $strResult);
        } else {
          $strResult = str_replace("[var.colspan]", "", $strResult);
        }
        $tdAttrib = $this->_serializeAttribute($value->attribs);
        $intSpanned = 0;
        if ($value->intColSpan > 1) {
          $intSkip = $value->intColSpan;
        } else {
          $intSkip = 0;
        }
        $strValue = $this->summaryData[$groupBy][$value->groupField];
        if (is_numeric($strValue) && $value->item_formatter != "") {
          switch ($value->item_formatter) {
            case "showItemNumber" :
              break;
            case "_printCheckbox" :
              break;
            default:
              $strValue = $this->_formatter(
                  $value->item_formatter,
                  $this->summaryData[$groupBy],
                  null,
                  $value->groupField
              );
              break;
          }
        }
        $strResult .= "<td $tdAttrib>" . $strValue . "</td>";
      } else {
        if ($value->isTotalInformation) {
          $strValue = $this->summaryData[$groupBy][$value->groupField];
          //$strValue = $value->groupField;
          if ($intSpanned > 0) {
            $strResult = str_replace("[var.colspan]", "colspan=\"" . ($intSpanned) . "\"", $strResult);
          } else {
            $strResult = str_replace("[var.colspan]", "", $strResult);
            $tdAttrib = "[var.colspan]";
            $strResult .= "<td $tdAttrib>" . $strValue . "</td>";
          }
        } else {
          if ($intSpanned == 0 && $value->intColSpan <= 1) {
            $tdAttrib = "[var.colspan]";
            $strResult .= "<td $tdAttrib>&nbsp;</td>";
          }
        }
        if ($value->intColSpan <= 1) {
          $intSpanned++;
        }
        //$strValue = $value->groupField;
      }
    }
    if ($intSpanned > 0) {
      if (strpos($strResult, "[var.colspan]") === false) {
        $strResult .= "<td colspan=$intSpanned>&nbsp;</td>";
      } else {
        $strResult = str_replace("[var.colspan]", "colspan=\"" . ($intSpanned) . "\"", $strResult);
      }
    } else {
      $strResult = str_replace("[var.colspan]", "", $strResult);
    }
    $strResult .= "</tr>";
    return $strResult;
  }

  //parameter: nama file script yang akan dipanggil ketika AJAX request ke server terjadi

  function _renderGroupByRowToExcel($groupBy)
  {
    if (!$this->hasGroupBy) {
      return;
    }
    $intSkip = 0;
    $intSpanned = 0;
    $intLastColumnPosition = 0;
    $counter = 0;
    $this->intExcelRows++;
    foreach ($this->columnSet as $idx => $value) {
      if ($intSkip > 0) {
        $intSkip--;
        if ($value->intColSpan <= 1) {
          $intSpanned++;
        }
        continue;
      }
      if ($value->grouped && !$value->isTotalInformation) {
        if ($intSpanned > 0) {
          // merge cell sebanyak intSpanned
          if ($intLastColumnPosition < $value->intColumnPosition) {
            $this->sheet->merge_cells(
                $this->intExcelRows,
                $intLastColumnPosition,
                $this->intExcelRows,
                ($value->intColumnPosition - 1)
            );
          }
        }
        $intLastColumnPosition = $value->intColumnPosition + 1;
        $intSpanned = 0;
        if ($value->intColSpan > 1) {
          $intSkip = $value->intColSpan;
        } else {
          $intSkip = 0;
        }
        $strValue = $this->summaryData[$groupBy][$value->groupField];
        //print cell
        $strGeneratedFormatName = $this->_getCellFormat($value, "silver");
        if ($value->dataType == "numeric" || $value->dataType == "currency") {
          $this->sheet->write_number(
              $this->intExcelRows,
              $value->intColumnPosition,
              $strValue,
              $strGeneratedFormatName
          );
        }
        if ($value->dataType == "integer") {
          $this->sheet->write_number(
              $this->intExcelRows,
              $value->intColumnPosition,
              $strValue,
              $strGeneratedFormatName
          );
        } else if ($value->dataType == "date") {
          $this->sheet->write_string(
              $this->intExcelRows,
              $value->intColumnPosition,
              $strValue,
              $strGeneratedFormatName
          );
        } else if ($value->dataType == "string") {
          $this->sheet->write_string(
              $this->intExcelRows,
              $value->intColumnPosition,
              $strValue,
              $strGeneratedFormatName
          );
        } else {
          $this->sheet->write($this->intExcelRows, $value->intColumnPosition, $strValue, $strGeneratedFormatName);
        }
        $counter++;
      } else {
        if ($value->isTotalInformation) {
          $strValue = $this->summaryData[$groupBy][$value->groupField];
          if ($intSpanned > 0) {
            //merge cell
            if ($intLastColumnPosition < $value->intColumnPosition) {
              $this->sheet->merge_cells(
                  $this->intExcelRows,
                  $intLastColumnPosition,
                  $this->intExcelRows,
                  ($value->intColumnPosition - 1)
              );
            }
          } else {
            //write cell
            $value->dataType = "string";
            $value->attribs['align'] = "left";
            $strGeneratedFormatName = $this->_getCellFormat($value, "silver");
            $this->sheet->write_string(
                $this->intExcelRows,
                $value->intColumnPosition,
                $strValue,
                $strGeneratedFormatName
            );
          }
          $intLastColumnPosition = $value->intColumnPosition;
        } else {
          //write blank cell
          $strGeneratedFormatName = $this->_getCellFormat($value, "silver");
          $this->sheet->write_blank($this->intExcelRows, $value->intColumnPosition, $strGeneratedFormatName);
        }
        if ($value->intColSpan <= 1) {
          $intSpanned++;
        }
      }
    }
    if ($intSpanned > 0) {
      //merge last found spanned cell
      if ($intLastColumnPosition < $value->intColumnPosition) {
        $this->sheet->merge_cells(
            $this->intExcelRows,
            $intLastColumnPosition,
            $this->intExcelRows,
            $value->intColumnPosition
        );
      }
    }
  }

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

  //fungsi2 tambahan 

  function _setHeaderDeepLevel()
  {
    //define all deep level...
    $maxDeepLevel = 1;
    $numOfCol = count($this->columnSet);
    $excelOutput = ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF);
    $colCounter = -1;
    foreach ($this->columnSet as $key => $col) {
      //do not process if this will be printed on excel and the column is hided
      if ($excelOutput && !$col->showInExcel) {
        continue;
      }
      if ($this->columnSet[$key]->intColumnPosition == null) {
        $colCounter++;
        $this->columnSet[$key]->intColumnPosition = $colCounter;
      }
      if ($col->intColSpan >= 1) {
        $intCurrentColSpan = $col->intColSpan;
        if ($col->intRowSpan <= 0) {
          $intCurrentRowSpan = 1;
        } else {
          $intCurrentRowSpan = $col->intRowSpan;
        }
        $deepLevel = $col->intDeepLevel + $intCurrentRowSpan;
        $intLastColumnPosition = $this->columnSet[$key]->intColumnPosition;
        if ($maxDeepLevel < $deepLevel) {
          $maxDeepLevel = $deepLevel;
        }
        $i = $key + 1;
        while (($i < $numOfCol) && ($intCurrentColSpan > 0)) {
          //if tested deep level below then current deep level then do this...
          if (($this->columnSet[$i]->intDeepLevel < $col->intDeepLevel) ||
              ($this->columnSet[$i]->intDeepLevel == 1)
          ) {
            $this->columnSet[$i]->intDeepLevel = $deepLevel;
            $this->columnSet[$i]->intColumnPosition = $intLastColumnPosition;
            if ($this->columnSet[$i]->intColSpan > 1) {
              $intLastColumnPosition += $this->columnSet[$i]->intColSpan;
            } else {
              $intLastColumnPosition++;
            }
            if ($this->columnSet[$i]->intColSpan > 0) {
              $intCurrentColSpan = $intCurrentColSpan - $this->columnSet[$i]->intColSpan;
              $i = $i + $this->columnSet[$i]->intColSpan;
            } else {
              $intCurrentColSpan--;
            }
          }
          $i++;
        }
        if ($col->intDeepLevel == 1) {
          $colCounter += ($col->intColSpan - 1);
        }
      }
    }
    return $maxDeepLevel;
  }

  function _setSortData()
  {
    $this->sortName = "";
    $this->sortOrder = "";
    if ($this->isShowSort) {
      //get current Sort Field and get the Sort Order
      if ($this->pageSortBy != '') {
        $strOrders = explode(" ", $this->pageSortBy, 2);
        if (count($strOrders) > 0) {
          if (count($strOrders) == 2) {
            $this->sortName = $strOrders[0];
            $this->sortOrder = " DESC";
            if (is_file($this->imageList['sort_desc'])) {
              $this->imageName = "<img src=\"" . $this->imageList['sort_desc'] . "\" border=\"0\" style=\"vertical-align:middle\" height=\"15\" width=\"15\">";
            } else {
              $this->imageName = "<span>&nbsp;&#9650;</span>";
            }
          } else {
            $this->sortName = $strOrders[0];
            if (is_file($this->imageList['sort_asc'])) {
              $this->imageName = "<img src=\"" . $this->imageList['sort_asc'] . "\" border=\"0\" style=\"vertical-align:middle\" height=\"15\" width=\"15\">";
            } else {
              $this->imageName = "<span>&nbsp;&#9660;</span>";
            }
          }
        }
      } else {
        $this->imageName = "";
      }
    }
  }

function _showHttpHeader()
  {
    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=" . $this->strFileNameXLS);
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
    header("Pragma: public");
  }

  function _subTotalAccumulation($currGroupBy, $groupField, $strValueSummary)
  {
    if (!isset($this->summaryData[$currGroupBy][$groupField])) {
      $this->summaryData[$currGroupBy][$groupField] = $strValueSummary;
    } else {
      if (is_numeric($strValueSummary)) {
        $this->summaryData[$currGroupBy][$groupField] += $strValueSummary;
      } else {
        $this->summaryData[$currGroupBy][$groupField] = $strValueSummary;
      }
    }
  }

  function _writeContextMenu()
  {
    $jml = 0;
    foreach ($this->columnSet as $val) {
      if ($val->isDatabaseField && $val->fieldName != "" && $val->intColSpan <= 0) {
        $jml++;
      }
    }
    $intColumnContext = ceil($jml / 10);
    $strResult = "
<div class='contextMenu' id=\"divContext" . $this->gridName . "\">
  <div class=l style=\"text-decoration: underline; \" >Show/Hide Columns</div>
  <div class=clr></div>
  <div class=l>
    <ul>";
    $counter = 0;
    $colCounter = 0;
    foreach ($this->columnSet as $val) {
      if ($val->isDatabaseField && $val->fieldName != "") {
        $strResult .= "
      <li><input class=c type=\"checkbox\" name=\"dataHeader" . $val->fieldName . "\" id=\"dataHeader" . $val->fieldName . "\" checked onClick=\"javascript:showHideField($colCounter, '" . $val->fieldName . "')\" />" . $val->columnName . "</li>";
        if ($val->intColSpan <= 0) {
          $counter++;
        }
        if ($counter % 10 == 0 && $jml != $counter) {
          $strResult .= "
    </ul>
  </div>
  <div class=l>
    <ul>";
        }
      }
      if ($val->intColSpan <= 0) {
        $colCounter++;
      }
    }
    $strResult .= "
    </ul>
  </div>
  <div class=clr></div>
  <div class=l><input type=button onClick=\"javascript:CloseContext()\" value=\"Close\" /></div>
</div>";
    return $strResult;
  }

  function _writeContextMenuJS()
  {
    $strResult = "
  <script type=\"text/javascript\" src=\"" . $GLOBALS['CLASSDATAGRIDPATH'] . "scripts/datagrid_context.js" . "\"></script>";
    $strResult .= "
  <script type=\"text/javascript\">InitContext('" . $this->formNameId . "', '" . $this->gridName . "');</script>";
    return $strResult;
  }

  function addButton($id, $name, $type, $value, $clientAction = "", $serverAction = "", $class = "")
  {
    $this->buttons[] = [
        "special"      => false,
        "id"           => $id,
        "name"         => $name,
        "type"         => $type,
        "value"        => $value,
        "class"        => "button",
        "clientAction" => $clientAction,
        "serverAction" => $serverAction
    ];
  }

  function addButtonExportExcel(
      $id = "btnExportXLS",
      $value = "Export to Excel",
      $strFileName = "exportData.xls",
      $strTitle1 = "",
      $strTitle2 = "",
      $strTitle3 = ""
  ) {
    $this->buttonExcel[] = $id;
    $this->buttons[] = [
        "special"      => false,
        "id"           => $id,
        "name"         => $id,
        "type"         => "submit",
        "value"        => $value,
        "class"        => "buttonExcel",
        "clientAction" => "",
        "serverAction" => ""
    ];
    $this->strFileNameXLS = $strFileName;
    $this->strTitle1 = $strTitle1;
    $this->strTitle2 = $strTitle2;
    $this->strTitle3 = $strTitle3;
  }

  function addButtonExportExcelHTML(
      $id = "btnExportXLSHTML",
      $value = "Export to Excel",
      $strFileName = "exportData.xls",
      $strTitle1 = "",
      $strTitle2 = "",
      $strTitle3 = ""
  ) {
    $this->buttonExcelHTML[] = $id;
    $this->buttons[] = [
        "special"      => false,
        "id"           => $id,
        "name"         => $id,
        "type"         => "submit",
        "value"        => $value,
        "class"        => "buttonExcel",
        "clientAction" => "",
        "serverAction" => ""
    ];
    $this->strFileNameXLS = $strFileName;
    $this->strTitle1 = $strTitle1;
    $this->strTitle2 = $strTitle2;
    $this->strTitle3 = $strTitle3;
  }

  //Membuat hidden2 yang diperlukan untuk paging dan sorting

  function addButtonPrintHTML($id = "btnPrintHTML", $value = "Print", $strTitle1 = "", $strTitle2 = "", $strTitle3 = "")
  {
    $this->buttonPrint[] = $id;
    $this->buttons[] = [
        "special"      => false,
        "id"           => $id,
        "name"         => $id,
        "type"         => "submit",
        "value"        => $value,
        "class"        => "buttonPrint",
        "clientAction" => "target=\"_blank\"",
        "serverAction" => ""
    ];
    $this->strTitle1 = $strTitle1;
    $this->strTitle2 = $strTitle2;
    $this->strTitle3 = $strTitle3;
  }

  //Fungsi untuk mencetak search bar

  function addColumn($column)
  {
    if (is_a($column, 'datagrid_column')) {
      $this->columnSet = array_merge($this->columnSet, [$column]);
    } else {
      die("Wrong parameter on addColumn function");
    }
    return true;
  }

  function addColumnCheckbox($column)
  {
    if ($this->hasCheckbox) {
      die("Cannot add 2 column checkbox on 1 datagrid");
    }
    if (is_a($column, 'datagrid_column')) {
      $this->checkboxItemID = $this->gridName . "_" . $column->columnName;
      $column->title_formatter = "_printCheckboxAll";
      $column->item_formatter = "_printCheckbox";
      $column->sortable = false;
      $column->searchable = false;
      $column->isDatabaseField = false;
      $this->columnSet = array_merge($this->columnSet, [$column]);
      $this->hasCheckbox = true;
    } else {
      die("Wrong parameter on addColumnCheckbox function");
    }
    return true;
  }

  function addColumnNumbering($column)
  {
    if (is_a($column, 'datagrid_column')) {
      $column->item_formatter = "showItemNumber";
      $column->fieldName = "";
      $column->sortable = false;
      $column->searchable = false;
      $column->dataType = "integer";
      $column->isDatabaseField = false;
      if (!isset($column->attribs['align'])) {
        $column->attribs['align'] = 'center';
      }
      if ($column->grouped) {
        $this->resetNumbering = true;
      } else {
        $this->resetNumbering = false;
      }
      $column->grouped = false;
      if ($column->xlsColumnWidth == 0) {
        $column->xlsColumnWidth = 4;
      }
      $this->columnSet = array_merge($this->columnSet, [$column]);
    } else {
      die("Wrong parameter on addColumnNumbering function");
    }
    return true;
  }

  function addRepeaterFunction($fungsiRowGenerator)
  {
    //$fungsiRowGenerator = function yang ada di file PHP pemanggil datagrid ini
    //contoh fungsi berikut dapat ditambahkan di file PHP
    //function drawHiddenRow($params) //$params harus ada
    //{
    //  extract($params);
    //  $strResult = "<tr valign=top style=\"display:none;background-color:#eeeeee\" id=\"detail$counter\">\n";
    //  $strResult .= "  <td colspan=2 align=\"center\"><strong>Members List</strong></td>\n";
    //  $strResult .= "  <td colspan=5><div id=\"detailData$counter\"></div></td>\n";
    //  $strResult .= "</tr>\n";
    //  return $strResult;
    //}
    //perhatikan $counter adalah reserved word => menghasilkan counter secara urut row by row
    //jika anda ingin mengakses record gunakan reserved word $record
    $this->repeaterFunction = $fungsiRowGenerator;
  }

  function addSpannedColumn($strHeader, $intColSpan)
  {
    $column = new DataGrid_Column(
        $strHeader, "",
        ["colspan" => $intColSpan], null,
        false, false, null, null,
        "string", true, 0, false, ""
    );
    $this->columnSet = array_merge($this->columnSet, [$column]);
    return true;
  }

  function addSpecialButton($id, $name, $type, $value, $clientAction = "", $serverAction = "")
  {
    $this->buttons[] = [
        "special"      => true,
        "id"           => $id,
        "name"         => $name,
        "type"         => $type,
        "value"        => $value,
        "class"        => "buttonSpecial",
        "clientAction" => $clientAction,
        "serverAction" => $serverAction
    ];
  }

  //fungsi untuk mencetak header dari datagrid
  //anda dapat meng-overload fungsi ini untuk mencetak header grid sesuai keinginan anda
  //hubungi Dedy Sukandar untuk mengetahui cara meng-overload fungsi dalam class, atau cari di google deh....

  function bind($ar)
  {
    if (is_array($ar)) {
      $this->dataset = $ar;
      return true;
    } else {
      return false;
    }
  }

  function convertToExcelDate($strDate)
  {
    if ($intTimeStamp = strtotime($strDate)) {
      $intTimeStamp = ceil($intTimeStamp / 86400) + 25569;
      return $intTimeStamp;
    } else {
      return $strDate;
    }
  }

  function disableFormTag()
  {
    $this->hasFormTag = false;
  }

  function getCriteria()
  {
    $strResult = "";
    //handle search dari datagrid
    //property pageSearchCriteria adalah: data kriteria yang dimasukkan pada saat tombol SEARCH di click
    //property pageSearchBy adalah: kriteria yang dipilih (dari drop-down list) pada saat tombol SEARCH di click,
    //      jika berisi data kosong berarti pencarian adalah untuk semua field
    if ($this->isShowSearch) {
      if ($this->pageSearchCriteria != "") {
        if ($this->pageSearchBy == "") {
          //any search field, maka looping untuk setial column dari datagrid yang ditampilkan
          foreach ($this->columnSet as $col) {
            if ($col->fieldName != '' && $col->searchable) {
              if ($strResult == "") {
                $strResult = " lower(" . $this->_formatFieldName($col->fieldName) . ") LIKE '%" . strtolower(
                        $this->pageSearchCriteria
                    ) . "%'";
              } else {
                $strResult .= " OR lower(" . $this->_formatFieldName($col->fieldName) . ") LIKE '%" . strtolower(
                        $this->pageSearchCriteria
                    ) . "%'";
              }
            }
          }
          $strResult = "( " . $strResult . " )";
        } else {
          //specific search field
          $strResult = " lower(" . $this->_formatFieldName($this->pageSearchBy) . ") LIKE '%" . strtolower(
                  $this->pageSearchCriteria
              ) . "%'";
        }
      }
      return $strResult;
    } else {
      return "";
    }
  }

  function getData($db, &$strSQL)
  {
    $arrData = [];
    if ($db->connect()) {
      //get Data and set to Datagrid's DataSource by set the data binding (bind method)
      $strKriteria = $this->getCriteria();
      if ($strKriteria != "") {
        if (strpos(strtolower($strSQL), "where ")) {
          $strSQL .= " AND " . $strKriteria;
        } else {
          $strSQL .= " WHERE " . $strKriteria;
        }
      }
      //handle sort
      $strSort = $this->getSortBy();
      if ($strSort != "") {
        $strSQL .= " ORDER BY " . $strSort;
      }
      //handle page limit
      //since MSSQL do not have OFFSET (except for MSSQL 2005), we use dataSeek function to make offset and limit
      if (defined("DB_TYPE")) {
        if (DB_TYPE == "mssql") {
          //handle page limit
          $res = $db->execute($strSQL);
          $pageLimit = 0;
          if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
            if ($this->isShowPageLimit) {
              if (is_numeric($this->pageLimit) && $this->pageLimit > 0) {
                $pageLimit = $this->pageLimit;
                //jump to offset
                // if ($db->numrows($res) > 0)
                //   $db->dataSeek($this->getOffsetStart());
              }
            }
          }
          $counter = 0;
          while ($rowDb = $db->fetchrow($res, "ASSOC")) {
            $counter++;
            $arrData[] = $rowDb;
            if ($pageLimit > 0 && $counter == $pageLimit) //stop after limit reach
            {
              break;
            }
          }
          return $arrData;
          //end of getData()
        }
      }
      //else if not MSSQL then...
      if ($this->isShowPageLimit && $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
        if (is_numeric($this->pageLimit) && $this->pageLimit > 0) {
          $strSQL .= " LIMIT $this->pageLimit OFFSET " . $this->getOffsetStart();
        }
      }
      $arrData = $db->getRecordSet($strSQL);
    }
    //echo $strSQL;
    return $arrData;
  }

  function getOffsetStart()
  {
    if ((is_numeric($this->pageLimit)) && ($this->pageLimit > 0)) {
      $intTotalPage = ceil($this->totalData / $this->pageLimit);
    } else {
      $intTotalPage = 1;
    }
    if ($this->pageNumber > $intTotalPage) {
      $this->pageNumber = $intTotalPage;
    }
    $offsetStart = (($this->pageNumber - 1) * $this->pageLimit);
    if ($offsetStart < 0) {
      $offsetStart = 0;
    }
    return $offsetStart;
  }

  function getPageJump()
  {
    return (isset($_REQUEST["pageJump" . $this->gridName])) ? $_REQUEST["pageJump" . $this->gridName] : 0;
  }

  function getPageLimit()
  {
    $pageLimit = (isset($_REQUEST["pageLimit" . $this->gridName])) ? $_REQUEST["pageLimit" . $this->gridName] : $this->defaultPageLimit;
    if (!is_numeric($pageLimit)) {
      $pageLimit = (isset($GLOBALS['ARRAY_PAGE_LIMIT'][0])) ? $GLOBALS['ARRAY_PAGE_LIMIT'][0] : "all";
    }
    return $pageLimit;
  }

  function getPageNumber()
  {
    return (isset($_REQUEST["pageNumber" . $this->gridName])) ? $_REQUEST["pageNumber" . $this->gridName] : 1;
  }

  function getPageSearchBy($defaultSearch = "")
  {
    return (isset($_REQUEST["pageSearchBy" . $this->gridName])) ? $_REQUEST["pageSearchBy" . $this->gridName] : $defaultSearch;
  }

  function getPageSearchCriteria()
  {
    return (isset($_REQUEST["pageSearchCriteria" . $this->gridName])) ? $_REQUEST["pageSearchCriteria" . $this->gridName] : "";
  }

  //serialize array into 2-pair value  

  function getPageSortBy()
  {
    if (isset($_REQUEST["pageSortBy" . $this->gridName])) {
      $pageSortBy = $_REQUEST["pageSortBy" . $this->gridName];
    } else //if no sort before then get default
      if (count($this->columnSet) > 0) {
        if ($this->pageSortBy == "" && $this->isShowSort) {
          $pageSortBy = $this->_getDefaultField();
        } else {
          $pageSortBy = $this->pageSortBy;
        }
      }
    return $pageSortBy;
  }

  function getRequest()
  {
    $this->pageNumber = $this->getPageNumber();
    $this->pageJump = $this->getPageJump();
    $this->pageLimit = $this->getPageLimit();
    $this->pageSearchBy = $this->getPageSearchBy();
    $this->pageSearchCriteria = $this->getPageSearchCriteria();
    $this->pageSortBy = $this->getPageSortBy();
    $this->_setSortData();
    $lengthText = strlen($this->checkboxItemID);
    $this->checkboxes = [];
    foreach ($_POST as $strIndex => $strValue) {
      if (substr($strIndex, 0, $lengthText) == $this->checkboxItemID) {
        $this->checkboxes[$strIndex] = $strValue;
      }
    }
    if (count($this->buttons) > 0) {
      foreach ($this->buttons as $button) {
        if (isset($_POST[$button['name']]) && $button['type'] == 'submit') {
          if (in_array($button['name'], $this->buttonExcel)) {
            $this->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_BIFF;
          } else if (in_array($button['name'], $this->buttonExcelHTML)) {
            $this->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_EXCEL_HTML;
          } else if (in_array($button['name'], $this->buttonPrint)) {
            $this->DATAGRID_RENDER_OUTPUT = DATAGRID_RENDER_PRINT_HTML;
          }
          $funcName = str_replace("(", "", $button['serverAction']);
          $funcName = str_replace(")", "", $funcName);
          if (is_callable($funcName)) {
            call_user_func($funcName);
          } else {
            $this->message = "
            Button \"" . $button['value'] . "\" is supposed to call function \"" . $funcName . "()\" when it submitted to server,
            <br />
            but the PROGRAMMER/SOFTWARE DEVELOPER forgets to add that function.
            <br />
            Contact SOFTWARE VENDOR if this error happened
            <br />
            PROGRAMMER Hint: You have to add function \"" . $funcName . "()\" in your coding.";
          }
        }
      }
    }
  }

  //set header deep level for colspan and rowspan feature
  //purpose: to set deep level, get maximum deep level, and set positioning of column
  //return value: maximum deep level

  function getSortBy()
  {
    //handle sort
    if ($this->isShowSort) {
      if ($this->pageSortBy != "") {
        return $this->_formatFieldName($this->sortName) . " " . $this->sortOrder;
      }
    } else if ($this->pageSortBy != "") {
      return $this->_formatFieldName($this->pageSortBy) . " ";
    }
    return "";
  }

  function getTotalData($db, &$strSQLCOUNT)
  {
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
      return 0;
    }
    $totalData = 0;
    if ($db->connect()) {
      //get Data and set to Datagrid's DataSource by set the data binding (bind method)
      $strKriteria = $this->getCriteria();
      if ($strKriteria != "") {
        if (strpos(strtolower($strSQLCOUNT), "where ")) {
          $strSQLCOUNT .= " AND " . $strKriteria;
        } else {
          $strSQLCOUNT .= " WHERE " . $strKriteria;
        }
      }
      $res = $db->execute($strSQLCOUNT);
      if ($rowDb = $db->fetchrow($res, "BOTH")) {
        $totalData = $rowDb[0];
      } else {
        $totalData = 0;
      }
    }
    return $totalData;
  }

  function groupBy($strGroup)
  {
    $this->strGroupByField = $strGroup;
    $this->hasGroupBy = true;
  }

  function printOpeningRow($intRows, $rowDb)
  {
    $strResult = "";
    if (($intRows % 2) == 0) {
      $strResult .= "
          <tr class=a valign=\"top\">";
    } else {
      $strResult .= "
          <tr valign=\"top\">";
    }
    return $strResult;
  }

  function render($DATAGRID_RENDER_OUTPUT = null)
  {
    //force to render the output to yours.
    if ($DATAGRID_RENDER_OUTPUT !== null) {
      $this->DATAGRID_RENDER_OUTPUT = $DATAGRID_RENDER_OUTPUT;
    }
    //print header to download excel, only if the render output is to excel
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
      $this->_showHttpHeader();
      //if render to BIFF WRITER the create workbook class
      if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
        $this->_initWorkBook();
      }
    }
    $strResult = "";
    //print CSS (stylesheet)
    $strResult .= $this->_printCSS();
    //render all datagrid input hidden
    $strResult .= $this->_createInputHidden();
    //print opening table contentGrid
    $strResult .= $this->_printOpeningTableContent();
    //print any datagrid message if exist
    $strResult .= $this->_printMessageRow();
    //render search bar
    //print page, captionm and searchbar on contentGrid
    $strResult .= $this->_printSearchBarAndCaptionRow();
    //print opening table of datagrid with id =   $thid->gridName
    $strResult .= $this->_printOpeningTableDataGrid();
    //print datagrid header row
    $strResult .= $this->_printGridHeaderRow();
    //print datagrid item row
    $strResult .= $this->_printGridItemRow();
    //print datagrid buttons
    $strResult .= $this->_printGridButtons();
    //print closing table datagrid
    $strResult .= $this->_printClosingTableDataGrid();
    //print paging on contentGrid
    $strResult .= $this->_printPaging($this->jumpPage, $this->pageNumber, $this->totalData);
    //print closing table contentGrid
    $strResult .= $this->_printClosingTableContent();
    //print DIV loading/process indicator
    $strResult .= $this->_drawProgressBarIndicator();
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_NORMAL) {
      if ($this->hasFormTag) {
        $strResult = "
  <form id=\"" . $this->formNameId . "\" name=\"" . $this->formNameId . "\" method=\"post\">" . $strResult . "
  </form>";
      }
      if ($this->autoScroll) {
        $strResult = "<div style='overflow:auto'>" . $strResult . "</div>";
      }
      $strResult .= $this->_drawJavascript();
      //print datagrid REMARKs
      //PLEASE DO NOT EDIT THIS LINE BELOW
      //WARNING: DO NOT CHANGE any of this LINE BELOW because it used in AJAX request datagrid
      $strResult = "
<!-- START OF DATAGRID: " . $this->gridName . ", generated by Datagrid Class, by Dedy Sukandar -->
<div class=\"divDataGrid\" id=\"div" . $this->gridName . "\">" .
          "<!-- START OF DATAGRID CONTENT: " . $this->gridName . " FOR AJAX -->" .
          $strResult;
      $strResult .= $this->_writeContextMenu();
      $strResult .= $this->_writeContextMenuJS();
      $strResult .= "
</div>
<!-- END OF DATAGRID CONTENT: " . $this->gridName . " FOR AJAX -->
<!-- END OF DATAGRID: " . $this->gridName . ", generated by Datagrid Class, by Dedy Sukandar -->";
    }
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_HTML || $this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_PRINT_HTML) {
      echo $strResult . "</body></html>";
      exit();
    }
    if ($this->DATAGRID_RENDER_OUTPUT == DATAGRID_RENDER_EXCEL_BIFF) {
      $this->_disposeWorkBook();
      exit();
    }
    return $strResult;
  }

    function setAJAXCallBackScript($scriptFileName)
  {
    $this->scriptFileName = $scriptFileName;
    $this->useAJAXTechnology = true;
  }//_printPaging

  // fungsi untuk mengirim header xls ke browser

  function setCSSFile($fileName)
  {
    if (is_file($fileName)) {
      $this->CSSFileName = $fileName;
    } else {
      $this->CSSFileName = "";
    }
  }//_showHttpHeader

  function setCaption($caption)
  {
    $this->caption = $caption;
  }

  function setPageLimit($limit)
  {
    $this->defaultPageLimit = $limit;
  }

  //input : dd MMM yyyy
  //output : numeric

  function setPagingSize($size)
  {
    $this->defaultPagingSize = $size;
  }
}

//end of class DataGrid
class DataGrid_Column
{

  var $attribs;

  var $columnName;

var $dataType;

  var $fieldName;

var $groupField = "";

var $grouped = false;

  var $intColSpan = 0;

  var $intColumnPosition = null;

    var $intDeepLevel = 1; /* string, numeric, date, currency, link*/

  var $intRowSpan = 0; /* default true*/

  var $isDatabaseField = true; /* default 0*/

  var $item_formatter; /* default false*/

  var $searchable; /* default ""*/
  //will be set from titleAttribs

var $showInExcel;

  var $sortable;

  //reserved variable
  //will be re-calculate again when you use rowspan/colspan attribute

  var $titleAttribs;

  //will be set for EXCEL merge cell
  //1st column position is 0

  var $title_formatter;

var $xlsColumnWidth;

  /**
   * Constructor
   *
   * Creates default table style settings
   *
   * @param   string  $columnName         The name of the column to be printed
   * @param   string  $fieldName          The name of the field for the column
   *                                      to be mapped to
   * @param   string  $orderBy            The field to order the data by
   * @param   string  $titleAttribs       The HTML attributes for the header (TR-TH tag)
   * @param   string  $attribs            The HTML attributes for the item/data (TR-TD tag)
   * @param   boolean $sortable           Whether or not the column is sortable
   * @param   boolean $searchable         Whether or not the column is searchable
   * @param   string  $title_formatter    A defined function to call upon the header/title  print
   *                                      rendering to allow for special
   *                                      formatting ON TITLE HEADER.  This allows for
   *                                      call-back function to print out a
   *                                      link or a form element, or whatever
   *                                      you can possibly think of.
   * @param   string  $item_formatter     A defined function to call upon
   *                                      rendering to allow for special
   *                                      formatting ON ITEM.  This allows for
   *                                      call-back function to print out a
   *                                      link or a form element, or whatever
   *                                      you can possibly think of.
   * @param   string  $dataType           the type of data e.g: string, numeric, date, currency, link (url, email, etc)
   * @param   boolean $showInExcel        this column will be shown in excel if this set to true
   * @param   double  $xlsColumnWidth     the column width of excel (be careful this is not same with width in HTML)
   *
   * @access  public
   */
  function DataGrid_Column(
      $columnName,
      $fieldName = null,
      $titleAttribs = [],
      $attribs = [],
      $sortable = true,
      $searchable = true,
      $title_formatter = null,
      $item_formatter = null,
      $dataType = "string",
      $showInExcel = true,
      $xlsColumnWidth = 0,
      $grouped = false,
      $groupField = "",
      $isTotalInformation = false
  ) {
    $this->columnName = $columnName;
    if ($fieldName == null) {
      $this->fieldName = "";
    } else {
      $this->fieldName = $fieldName;
    }
    if ($titleAttribs == null) {
      $this->titleAttribs = [];
    } else {
      if (isset($titleAttribs['rowspan'])) {
        $this->intRowSpan = intval($titleAttribs['rowspan']);
      }
      if (isset($titleAttribs['colspan'])) {
        $this->isDatabaseField = false;
        $this->intColSpan = intval($titleAttribs['colspan']);
      }
      $this->titleAttribs = $titleAttribs;
    }
    if ($attribs == null) {
      $this->attribs = [];
    } else {
      $this->attribs = $attribs;
    }
    $this->sortable = $sortable;
    $this->searchable = $searchable;
    if ($title_formatter == null) {
      $this->title_formatter = "";
    } else {
      $this->title_formatter = $title_formatter;
    }
    if ($item_formatter == null) {
      $this->item_formatter = "";
    } else {
      $this->item_formatter = $item_formatter;
    }
    $this->dataType = $dataType;
    $this->showInExcel = $showInExcel;
    $this->xlsColumnWidth = $xlsColumnWidth;
    $this->grouped = $grouped;
    $this->groupField = $groupField;
    $this->isTotalInformation = $isTotalInformation;
  }
} // end of class DataGrid_Column
?>
