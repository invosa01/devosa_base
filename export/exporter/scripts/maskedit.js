var keybYN = new keybEdit('yn','Valid values are \'Y\' or \'N\'.');
var keybNumeric = new keybEdit('01234567890','Integer input only.');
var keybTime = new keybEdit('01234567890:','Time input only.');
var keybAlpha = new keybEdit('abcdefghijklmnopqurstuvwxy ','Alpha input only.');
var keybAlphaNumeric = new keybEdit('abcdefghijklmnopqurstuvwxy01234567890 ','Alpha-numeric input only.');
var keybDecimal = new keybEdit('01234567890.','Decimal input only.');
var keybDate =  new keybEdit('01234567890/','Date input only');;
var keybYNNM = new keybEdit('yn');
var keybNumericNM = new keybEdit('01234567890');
var keybAlphaNM = new keybEdit('abcdefghijklmnopqurstuvwxy');
var keybAlphaNumericNM = new keybEdit('abcdefghijklmnopqurstuvwxy01234567890');
var keybDecimalNM = new keybEdit('01234567890.','Decimal input only');
var keybTimeNM = new keybEdit('01234567890:','Time input only');
var keybDateNM = new keybEdit('01234567890/');

var specialKeyCode = new Array(8,9,46,37,39,36,35,33,34,38,40,27,13,16,17,18,91,92,93);

function keybEdit(strValid, strMsg) {
  /*  Function:    keybEdit
    Creation Date:  October 11, 2001
    Programmer:    Edmond Woychowsky
  */

  //  Variables
  var reWork = new RegExp('[a-z]','gi');    //  Regular expression\

  //  Properties
  if(reWork.test(strValid))
    this.valid = strValid.toLowerCase() + strValid.toUpperCase();
  else
    this.valid = strValid;

  if((strMsg == null) || (typeof(strMsg) == 'undefined'))
    this.message = 'Invalid input!';
  else
    this.message = strMsg;

  //  Methods
  this.getValid = function () { return this.valid.toString(); }
  this.getMessage = function () { return this.message; }
}

 function editKeyBoard(e) {
  strWork = keybNumeric.getValid();
  strMsg = '';              // Error message
  blnValidChar = false;          // Valid character flag

  if (e == null) e=window.event;

  var keyCode = e.keyCode;
  if ( keyCode == 0 ) // as it might on Moz
    keyCode = e.charCode;
  if ( keyCode == 0 ) // unlikely to get here
    keyCode = e.which;


  if ( e.charCode != null && e.charCode == 0 ) {
    for(i=0;i < specialKeyCode.length;i++)
    if(keyCode== specialKeyCode[i]) {
      return;
    }
  }

  // Part 1: Validate input
  if(!blnValidChar)
    for(i=0;i < strWork.length;i++)
      if(keyCode== strWork.charCodeAt(i)) {
        blnValidChar = true;
        break;
      }

  // Part 2: Build error message
  if(!blnValidChar) {
    //alert('Error: Numeric input only.');

    if( document.all){
      e.keyCode = 0;
    }
    else{
      e.preventDefault();
    }
    //e.returnValue = false;    // Clear invalid character
    this.focus();            // Set focus
  }
}

 function editKeyBoardDecimal(e) {
  strWork = keybDecimalNM.getValid();
  strMsg = '';              // Error message
  blnValidChar = false;          // Valid character flag

  if (e == null) e=window.event;

  var keyCode = e.keyCode;
  if ( keyCode == 0 ) // as it might on Moz
    keyCode = e.charCode;
  if ( keyCode == 0 ) // unlikely to get here
    keyCode = e.which;


  if ( e.charCode != null && e.charCode == 0 ) {
    for(i=0;i < specialKeyCode.length;i++)
    if(keyCode== specialKeyCode[i]) {
      return;
    }
  }

  // Part 1: Validate input
  if(!blnValidChar)
    for(i=0;i < strWork.length;i++)
      if(keyCode== strWork.charCodeAt(i)) {
        blnValidChar = true;
        break;
      }

  // Part 2: Build error message
  if(!blnValidChar) {
    //alert('Error: Numeric input only.');

    if( document.all){
      e.keyCode = 0;
    }
    else{
      e.preventDefault();
    }
    //e.returnValue = false;    // Clear invalid character
    this.focus();            // Set focus
  }
}

function editKeyBoardTime(e) {
  strWork = keyTimeNM.getValid();
  strMsg = '';              // Error message
  blnValidChar = false;          // Valid character flag

  if (e == null) e=window.event;

  var keyCode = e.keyCode;
  if ( keyCode == 0 ) // as it might on Moz
    keyCode = e.charCode;
  if ( keyCode == 0 ) // unlikely to get here
    keyCode = e.which;


  if ( e.charCode != null && e.charCode == 0 ) {
    for(i=0;i < specialKeyCode.length;i++)
    if(keyCode== specialKeyCode[i]) {
      return;
    }
  }

  // Part 1: Validate input
  if(!blnValidChar)
    for(i=0;i < strWork.length;i++)
      if(keyCode== strWork.charCodeAt(i)) {
        blnValidChar = true;
        break;
      }

  // Part 2: Build error message
  if(!blnValidChar) {
    //alert('Error: Numeric input only.');

    if( document.all){
      e.keyCode = 0;
    }
    else{
      e.preventDefault();
    }
    //e.returnValue = false;    // Clear invalid character
    this.focus();            // Set focus
  }
}

function maskEdit(txtField, func) {
  txtField.onkeypress = func;
}