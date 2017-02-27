// common library from yahoo :D
// --------------------------------------

function ylib_Browser()
{
	d=document;
	this.agt=navigator.userAgent.toLowerCase();
	this.major = parseInt(navigator.appVersion);
	this.dom=(d.getElementById)?1:0;
	this.ns=(d.layers);
	this.ns4up=(this.ns && this.major >=4);
	this.ns6=(this.dom&&navigator.appName=="Netscape");
	this.op=(window.opera? 1:0);
	this.ie=(d.all);
	this.ie4=(d.all&&!this.dom)?1:0;
	this.ie4up=(this.ie && this.major >= 4);
	this.ie5=(d.all&&this.dom);
	this.win=((this.agt.indexOf("win")!=-1) || (this.agt.indexOf("16bit")!=-1));
	this.mac=(this.agt.indexOf("mac")!=-1);
};

var oBw = new ylib_Browser();

function ylib_getObj(id,d)
{
	var i,x;  if(!d) d=document; 
	if(!(x=d[id])&&d.all) x=d.all[id]; 
	for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][id];
	for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=ylib_getObj(id,d.layers[i].document);
	if(!x && document.getElementById) x=document.getElementById(id); 
	return x;
};

function ylib_addEvt(o,e,f,c){ 
  if(o.addEventListener)o.addEventListener(e,f,c);
  else if(o.attachEvent)o.attachEvent("on"+e,f);
  else eval("o.on"+e+"="+f) 
};

var YLIB_SHIFT_KEYCODE = 16;
var YLIB_CTRL_KEYCODE = 17;
var YLIB_ALT_KEYCODE = 18;
var YLIB_SHIFT = "shift";
var YLIB_CTRL = "ctrl";
var YLIB_ALT = "alt";

ylib_keyevt.count=0;

function ylib_keyevt(elm)
{
	this.id = "keyevt"+ylib_keyevt.count++;
	eval(this.id + "=this");
	this.keys = new Array();
	this.shift=0;
	this.ctrl=0;
	this.alt=0;
	this.addKey = ylib_addKey;
	this.keyevent = ylib_keyevent;
	this.checkModKeys = ylib_checkModKeys;
};

function ylib_addKey(cdom,cns4,a,m)
{
	if(oBw.ie||oBw.dom) this.keys[cdom] = [a,m];
	else this.keys[cns4] = [a,m];
};

var YLIB_COUNT=0;

function ylib_keyevent(evt)
{
	if(oBw.ie||oBw.op) evt=event;
	var k = (oBw.ie||oBw.op||oBw.ns6)? evt.keyCode:evt.which;
	this.checkModKeys(evt,k);
	if(this.keys[k]==null) return false;
	var m = this.keys[k][1];
	if((this.shift && (m.indexOf(YLIB_SHIFT) != -1) || !this.shift && (m.indexOf(YLIB_SHIFT) == -1)) && (this.ctrl && (m.indexOf(YLIB_CTRL) != -1) || !this.ctrl && (m.indexOf(YLIB_CTRL) == -1)) && (this.alt && (m.indexOf("alt") != -1) || !this.alt && (m.indexOf("alt") == -1)))
	{
		var a = this.keys[k][0];
		a = eval(a); 
		if(typeof a == "function") a();
	}
};

function ylib_checkModKeys(e,k)
{
	if(oBw.dom)
	{ 
		this.shift = e.shiftKey;
		this.ctrl = e.ctrlKey;
		this.alt = e.altKey;
	}
	else
	{
		// for opera
		this.shift = (k==YLIB_SHIFT_KEYCODE) ? 1:0;
		this.ctrl = (k==YLIB_CTRL_KEYCODE) ? 1:0;
		this.alt = (k==YLIB_ALT_KEYCODE) ? 1:0;
	}
};

var oKey = new ylib_keyevt();
