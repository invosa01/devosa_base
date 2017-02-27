var displayed="<a href='javascript:window.scrollTo(0,0)'><div class='backtotop'>.</div></a>";
document.write('<div id="backtotop" style="position:absolute;top:-300;z-index:100;overflow:hidden">'+displayed+'</div>');

// === DO NOT EDIT ANYTHING BELOW THIS LINE!!! === //

var object_scroll_width;
var object_scroll_height;
var ns4=document.layers
var ie4=document.all
var ns6=document.getElementById&&!document.all
var bottomLimit = 34;

var scrollWidth  = getScrollerWidth();
var offsetWidth  = 85;
var offsetHeight = 19;

function getScrollerWidth() 
{
  var scr = null;
  var inn = null;
  var wNoScroll = 0;
  var wScroll = 0;

  // Outer scrolling div
  scr = document.createElement('div');
  scr.style.position = 'absolute';
  scr.style.top = '-1000px';
  scr.style.left = '-1000px';
  scr.style.width = '100px';
  scr.style.height = '50px';
  // Start with no scrollbar
  scr.style.overflow = 'hidden';

  // Inner content div
  inn = document.createElement('div');
  inn.style.width = '100%';
  inn.style.height = '200px';

  // Put the inner div in the scrolling div
  scr.appendChild(inn);
  // Append the scrolling div to the doc
  document.body.appendChild(scr);

  // Width of the inner div sans scrollbar
  wNoScroll = inn.offsetWidth;
  hNoScroll = inn.offsetHeight;
  // Add the scrollbar
  scr.style.overflow = 'auto';
  // Width of the inner div width scrollbar
  wScroll = inn.offsetWidth;
  hScroll = inn.offsetHeight;

  // Remove the scrolling div from the doc
  document.body.removeChild(document.body.lastChild);

  // Pixel width of the scroller
  return (wNoScroll - wScroll);
}


function staticit()
{ 
  if (document.all) crossbacktotop=document.all.backtotop;
  else crossbacktotop=document.getElementById("backtotop");

  //object_scroll_width  = (document.all)? document.body.clientWidth-crossbacktotop.offsetWidth-scrollWidth: window.innerWidth-crossbacktotop.offsetWidth-scrollWidth;
  //object_scroll_height = (document.all)? document.body.clientHeight-crossbacktotop.offsetHeight : window.innerHeight-crossbacktotop.offsetHeight;
  object_scroll_width  = (document.all)? document.body.clientWidth-offsetWidth-scrollWidth: window.innerWidth-offsetWidth-scrollWidth;
  object_scroll_height = (document.all)? document.body.clientHeight-offsetHeight : window.innerHeight-offsetHeight;
  var w2=ns6? pageXOffset + object_scroll_width : document.body.scrollLeft + object_scroll_width;
  var h2=ns6? pageYOffset + object_scroll_height : document.body.scrollTop + object_scroll_height;

  if (h2 != object_scroll_height)
  {
    //alert(w2 +"-"+object_scroll_width+"-"+offsetWidth+"-"+scrollWidth);
    if (h2 < document.body.scrollHeight  - bottomLimit)
      crossbacktotop.style.top=h2;
    else
      crossbacktotop.style.top=document.body.scrollHeight - bottomLimit;
    
    crossbacktotop.style.left=w2;
    crossbacktotop.style.visibility = "";
    //crossbacktotop.style.display = "block";
  }
  else
  {
    crossbacktotop.style.visibility = "hidden";
    crossbacktotop.style.top=h2;
    crossbacktotop.style.left=w2;
    //crossbacktotop.style.display = "none";
  }
}

window.onscroll=staticit;
window.onresize=staticit;
