var _replaceContext = false; // replace the system context menu? 
var _mouseOverContext = false; // is the mouse over the context menu? 
var _divContext = "";; // makes my life easier
var _gridName = "";
var _formName = "";
var isIE = (document.all);

function InitContext(formName, gridName) 
{ 
  _gridName = gridName;
  _formName = formName;
  _divContext = document.getElementById('divContext'+gridName);
  _divContext.onmouseover = function() { _mouseOverContext = true; }; 
  _divContext.onmouseout = function() { _mouseOverContext = false; }; 
  //$('aClose').onclick = CloseContext; 
  //$('aEnable').onclick = EnableContext; 
  document.body.onmousedown = ContextMouseDown; 
  document.body.oncontextmenu = ContextShow;

  var aHeaders = $(_gridName).tHead.rows;
  var nHeaders = aHeaders.length;  
  if (nHeaders > 1)
  {
    for(var i=nHeaders-1; i>=0; i--)
    {
      for(var j=0; j<aHeaders[i].cells.length; j++)
      {
        if (getAttributeHeader(aHeaders[i].cells[j], "colSpan") != null)
        {
          colSpan = getAttributeHeader(aHeaders[i].cells[j], "colSpan");
          if (colSpan == 1) colSpan = 0;
          setAttributeHeader(aHeaders[i].cells[j], "ori_colspan", colSpan);
        }
      }      
    }
  }   
} // call from the onMouseDown event, passing the event if standards compliant 

function ContextMouseDown(event) 
{
  if (_mouseOverContext) return; 
  // IE is evil and doesn't pass the event object 
  if (event == null) event = window.event; 
  // we assume we have a standards compliant browser, but check if we have IE 
  var target = event.target != null ? event.target : event.srcElement; 
  // only show the context menu if the right mouse button is pressed 
  // and a hyperlink has been clicked (the code can be made more selective) 
  if (event.button == 2)
  {
    if ((target.parentNode.parentNode.id == _gridName) || (target.parentNode.parentNode.parentNode.id == _gridName))
      _replaceContext = true; 
  }
  else if (!_mouseOverContext) _divContext.style.display = 'none'; 
}

function CloseContext() 
{ 
  _mouseOverContext = false; 
  _divContext.style.display = 'none'; 
} 
// call from the onContextMenu event, passing the event 
// if this function returns false, the browser's context menu will not show up 
function ContextShow(event) 
{ 
  if (_mouseOverContext) return; 
  // IE is evil and doesn't pass the event object 
  if (event == null) event = window.event; 
  // we assume we have a standards compliant browser, but check if we have IE 
  var target = event.target != null ? event.target : event.srcElement; 
  if (_replaceContext) 
  {
    //$('aContextNav').href = target.href; 
    //$('aAddWebmark').href = 'http://luke.breuer.com/webmark/?addurl=' + encodeURIComponent(target.href) + '&title=' + encodeURIComponent(target.innerHTML); 
    // document.body.scrollTop does not work in IE 
    var scrollTop = document.body.scrollTop ? document.body.scrollTop : document.documentElement.scrollTop; 
    var scrollLeft = document.body.scrollLeft ? document.body.scrollLeft : document.documentElement.scrollLeft; 
    // hide the menu first to avoid an "up-then-over" visual effect 
    _divContext.style.display = 'none'; 
    _divContext.style.left = event.clientX + scrollLeft + 'px'; 
    _divContext.style.top = event.clientY + scrollTop + 'px'; 
    _divContext.style.display = 'block'; 
    _replaceContext = false; 
    return false; 
  } 
} 

function EnableContext() 
{
  _noContext = false; 
  _mouseOverContext = false; 
  // this gets left enabled when "disable menus" is chosen 
  $('aEnable').style.display = 'none'; 
  return false; 
} 

function showHideObjectCell(obj, fieldName)
{
  if (!obj) return null;
  if (obj.style.display != "none")
  {
    obj.style.display = "none";
    return false;
  }
  else
  {
    if (isIE)
      obj.style.display = "block";
    else
      obj.style.display = "table-cell";
    return true;
  }

}

function getAttributeHeader(obj, attrName)
{
    return obj.getAttribute(attrName);
}

function setAttributeHeader(obj, attrName, value)
{
    obj.setAttribute(attrName,  value);
}

function hideHeaderCheck(fieldName)
{
  var aHeaders = $(_gridName).tHead.rows;
  var nHeaders = aHeaders.length;  
  var counter;
  var pointer = null;  
  //ini untuk header rowSpan dan colSpan header
  if (nHeaders > 1)
  {
    for(var i=nHeaders-1; i>=0; i--)
    {
      counter = 0;      
      for(var j=0; j<aHeaders[i].cells.length; j++)
      {
        if (pointer == null)
        {
          if (aHeaders[i].cells[j].style.display != "none")
            counter++;

          if (aHeaders[i].cells[j].id == 'hdrGrid'+fieldName)
          {
            if (getAttributeHeader(aHeaders[i].cells[j], "rowSpan") != null)
            {
              if (getAttributeHeader(aHeaders[i].cells[j], "rowSpan") == nHeaders)
              {
                return false;
              }
            }
            pointer = counter;
            break;
          }
        }
        else
        {
          if (getAttributeHeader(aHeaders[i].cells[j], "ori_colspan") > 0)
          {
            colSpan = getAttributeHeader(aHeaders[i].cells[j], "colSpan");
            //colSpanOri = getAttributeHeader(aHeaders[i].cells[j], "ori_colspan");
            if (colSpan >= pointer)
            {
              colSpan--;
              //alert(colSpan);
              if (colSpan == 0)
              {
                //alert('a');
                aHeaders[i].cells[j].style.display = "none";
              }
              else
              {
                setAttributeHeader( aHeaders[i].cells[j], "colSpan", colSpan);
              }
              return true;
            }
            else
            {
              pointer -= colSpan;
            }
          }
        }
      }
    }
  }
  return true;
}

function showHeaderCheck(fieldName)
{
  var aHeaders = $(_gridName).tHead.rows;
  var nHeaders = aHeaders.length;  
  var counter;
  var pointer = null;
  //ini untuk header rowSpan dan colSpan header
  if (nHeaders > 1)
  {
    for(var i=nHeaders-1; i>=0; i--)
    {
      counter = 0;
      for(var j=0; j<aHeaders[i].cells.length; j++)
      {
        if (pointer == null)
        {
          if (aHeaders[i].cells[j].style.display == "none")
            counter++;
          
          if (aHeaders[i].cells[j].id == 'hdrGrid'+fieldName)
          {
            if (getAttributeHeader(aHeaders[i].cells[j], "rowSpan") != null)
            {
              if (getAttributeHeader(aHeaders[i].cells[j], "rowSpan") == nHeaders)
              {
                return false;
              }
            }
            pointer = counter;
            break;
          }
        }
        else
        {
          if (getAttributeHeader(aHeaders[i].cells[j], "ori_colspan") > 0)
          {
            colSpan = getAttributeHeader(aHeaders[i].cells[j], "colSpan");
            colSpanOri = getAttributeHeader(aHeaders[i].cells[j], "ori_colspan");
            if ((colSpan == 1) && (aHeaders[i].cells[j].style.display == "none"))
            {
              colSpan = 0;
            }
            if (((colSpanOri - colSpan) > 0) && ((colSpanOri - colSpan) >= pointer))
            {
              if (colSpan == 0)
              {
                if (isIE)
                  aHeaders[i].cells[j].style.display = "block";
                else
                  aHeaders[i].cells[j].style.display = "table-cell";
              }
              colSpan++;
              setAttributeHeader( aHeaders[i].cells[j], "colSpan", colSpan);
              
              return true;
            }
            else
            {
              pointer -= colSpan;
            }
          }
        }
      }
    }
  }
  return true;
}

function showHideField(colNumber, fieldName)
{
	var aRows = $(_gridName).tBodies[0].rows;
  var nRows = aRows.length;
  
  isShow = showHideObjectCell($('hdrGrid'+fieldName));
  if (isShow)
    showHeaderCheck(fieldName);
  else if (isShow != null)
    hideHeaderCheck(fieldName);
  for(var i=0;i<nRows;i++)
  {
    showHideObjectCell(aRows[i].cells[colNumber]);
  }
}