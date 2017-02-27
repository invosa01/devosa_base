function GetBrowserName()
{
	var browser="None";
	if(navigator.appName.indexOf("Netscape")>=0&&parseFloat(navigator.appVersion)>=4)
	{
		browser="NS4";
		version=4;
	}
	if(document.getElementById)
	{
		browser="NS6";
		if(navigator.userAgent.indexOf("6.01")!=-1||navigator.userAgent.indexOf("6.0")!=-1) version=6; else version=6.1;
	}
	if(document.all)
	{
		browser="IE";
		if(document.getElementById) version=5; else version=4;
	}
	if(navigator.userAgent.indexOf("Opera")!=-1)
	{
		browser="Opera";
		if(navigator.userAgent.indexOf("7.")!=-1) version=7; else version=6;
	}
	if(navigator.userAgent.indexOf("Safari")!=-1) isSafari=true; else isSafari=false;
	return browser;
};

function GetOSName()
{
	var os=navigator.userAgent;
	if(os.indexOf("Mac")!=-1) os="Mac";	else os="Win";
	return os;
};

function GetEventX(evt)
{
	if(browser=="IE")
	{
		if(document.body.parentNode)
		{
			if(document.body.parentNode.scrollLeft!=0)
				return(event.x+document.body.parentNode.scrollLeft);
		}
		return(event.x+document.body.scrollLeft);
	}
	if(browser=="NS6") return(evt.pageX);
	if(browser=="Opera") return(event.clientX);
};

function GetEventY(evt)
{
	if(browser=="IE")
	{
		if(document.body.parentNode)
		{
			if(document.body.parentNode.scrollTop!=0)
				return(event.y+document.body.parentNode.scrollTop);
		}
		return(event.y+document.body.scrollTop);
	}
	if(browser=="NS6") return(evt.pageY);
	if(browser=="Opera") return(event.clientY);
};

function IsExistObject(layerName)
{
	return(document.getElementById(layerName)!=null);
};

function IsVisible(element)
{
	if(IsExistObject(element))
	{
		if(document.getElementById(element).style.visibility=="visible") return true;
		else return false;
	}
	return false;
};

function DisplayElement(element,show){
  var vis;
  if(show){vis="visible";} else{vis="hidden";}
  if(browser=="IE"&&os=="Win")
    if(document.all[element]!=null)
       if(document.all[element].style.visibility!=vis)
       {
          var i;
          var obj=document.all[element].filters;
          if(obj)
          {
             for(i=0;obj[i];i++){ obj[i].apply();}
             document.all[element].style.visibility=vis;
             for(i=0;obj[i];i++){obj[i].play();}
          }
          else document.all[element].style.visibility=vis;
       }
  if(browser=="IE"&&os=="Mac")
     if(document.all[element]!=null)
        if(document.all[element].style.visibility!=vis) document.all[element].style.visibility=vis;
  if(browser=="NS6"||browser=="Opera")
     if(document.getElementById(element)!=null)
        if(document.getElementById(element).style.visibility!=vis) document.getElementById(element).style.visibility=vis;
};

function SetBackgroundColor(element,bgColor)
{
	if(browser=="Opera"&&version<7) return;
	if(IsExistObject(element)) document.getElementById(element).style.background=bgColor;
};

function SetForegroundColor(element,fgColor)
{
	if(browser=="Opera"&&version<7) return;
	if(IsExistObject(element)) document.getElementById(element).style.color=fgColor;
};

function SetPositionX(element,left)
{
	document.getElementById(element).style.left=left+'px';
};

function GetPositionX(element)
{
	if(browser=="Opera"&&version<7) return(document.getElementById(element).style.pixelLeft);
	else return(document.getElementById(element).offsetLeft);
};

function SetPositionY(element,top)
{
	document.getElementById(element).style.top=top+'px';
};

function GetPositionY(element)
{
	if(browser=="Opera"&&version<7) return(document.getElementById(element).style.pixelTop);
	else return(document.getElementById(element).offsetTop);
};

function SetHeight(element,height)
{
	if(browser=="IE")
		document.all[element].style.height=height+'px';
	if(browser=="NS6"||browser=="Opera")
		document.getElementById(element).style.height=height+'px';
};

function GetHeight(element)
{
	if(browser=="Opera"&&version<7)
		return(document.getElementById(element).style.pixelHeight);
	else
		return(document.getElementById(element).offsetHeight);
};

function SetWidth(element,width)
{
	if(browser=="IE")
		document.all[element].style.width=width+'px';
	if(browser=="NS6"||browser=="Opera")
		document.getElementById(element).style.width=width+'px';
};

function GetWidth(element)
{
	if(browser=="Opera"&&version<7)
		return(document.getElementById(element).style.pixelWidth);
	else
		return(document.getElementById(element).offsetWidth);
};

function GetClientWidth()
{
	if(browser=="NS6"||browser=="Opera") return(window.innerWidth+window.pageXOffset-18);
	if(browser=="IE")
	{
		var obj=document.body.parentNode;
		if(isNaN(obj.clientWidth)||obj.clientWidth==0) obj=document.body;
		return(obj.scrollLeft+obj.clientWidth);
	}
};

function GetOffsetPositionX()
{
	if(browser=="NS6"||browser=="Opera") return(window.pageXOffset);
	if(browser=="IE")
	{
		var obj=document.body.parentNode;
		if(isNaN(obj.scrollLeft)||obj.scrollLeft==0) obj=document.body;
		return(obj.scrollLeft);
	}
};

function GetOffsetPositionY()
{
	if(browser=="NS6"||browser=="Opera") return(window.pageYOffset);
	if(browser=="IE")
	{
		var obj=document.body.parentNode;
		if(isNaN(obj.scrollTop)||obj.scrollTop==0) obj=document.body;
		return(obj.scrollTop);
	}
};

function GetClientHeight()
{
	if(browser=="NS6"||browser=="Opera") return(window.innerHeight+window.pageYOffset-18);
	if(browser=="IE")
	{
		var obj=document.body.parentNode;
		if(isNaN(obj.clientHeight)||obj.clientHeight==0) obj=document.body;
		return(obj.scrollTop+obj.clientHeight);
	}
};

browser=GetBrowserName();
os=GetOSName();
ace_state=new InitializeMenu();
function InitializeMenu()
{
	this.ver='3.6.0';
	this.id='52084';
	this.menuActive=false;
	this.submenuArray=new Array(50);
	this.mainmenuArray=new Array(5);
	this.activemenuArray=new Array(10);
	this.timeoutidArray=new Array(50);
	this.imgNormalArray=new Array(50);
	this.imgHoverArray=new Array(50);
	this.imac=0;
	this.mmcp=0;
	this.smcp=0;
	this.mmac=0;
	this.smac=0;
	this.amac=0;
	this.tiac=0;
	this.onclick=false;
	this.hideidArray=null;
	this.mtopoffset=0;
	this.mleftoffset=0;
	this.ACECreateMenu=false;
	this.ACELoadMenu=false;
	this.offsetLeft=1;
	this.offsetTop=1;
	this.menuReady=false;
	this.menuStatus=1;
	this.nbgcolor='#000000';
	this.hbgcolor='#000000';
	this.nftcolor='#FFFFFF';
	this.hftcolor='#FFFFFF';
	this.position='bottom';
	this.closedelay=500;
	this.fontFamily='Arial';
	this.fontStyle='normal';
	this.fontSize='10pt';
	this.fontWeight='normal';
	this.hspacing=10;
	this.columns=1;
	this.bgimage=null;
	this.arrowimage=null;
	this.textalign='left';
	this.filters='';
	this.ffade='';
	this.fopacity='';
	this.fshadow='';
	this.fblinds='';
	this.fdissolve='';
	this.htmlBefore='';
	this.htmlAfter='';
	this.divider='&nbsp;';
	this.hbdrsize='0';
	this.hbdrcolor='#FFFFFF';
	this.cellpadding=1;
	this.cellspacing=0;
	this.layerpadding=3;
	this.target='_self';
	this.valign='middle';
	this.menuborder='';
	this.itemborder='';
	this.offset=0;
	this.nowrap='nowrap';
	this.width='';
	this.IsRelative=
		function(str)
		{
			if(str==null||str=='null'||str=='') return false;
			if(str.search(/^\/+/i)!=-1)return false;
			if(str.search(/^http:/i)!=-1)return false;
			if(str.search(/^https:/i)!=-1)return false;
			if(str.search(/^ftp:/i)!=-1)return false;
			if(str.search(/^javascript:/i)!=-1)return false;
			if(str.search(/^mailto:/i)!=-1)return false;
			if(str.search(/^file:/i)!=-1)return false;
			if(str.search(/^telnet:/i)!=-1)return false;
			return true;
		};
	this.ProcUrl=
		function(str)
		{
			if(ace_state.IsRelative(str)) return ace_path+str;
			else return str;
		};
	this.StaticMenu=
		function()
		{
			for(var i=0;i<ace_state.mmac;i++)
			{
				var menu=ace_state.mainmenuArray[i];
				if(IsExistObject(menu.name))
					if(menu.isStatic)
						switch(menu.staticPos)
						{
							case 1:SetPositionY(menu.name,GetOffsetPositionY()+menu.staticY);SetPositionX(menu.name,GetClientWidth()+menu.staticX);break;
							case 2:SetPositionY(menu.name,GetClientHeight()+menu.staticY);SetPositionX(menu.name,GetOffsetPositionX()+menu.staticX);break;
							case 3:SetPositionY(menu.name,GetClientHeight()+menu.staticY);SetPositionX(menu.name,GetClientWidth()+menu.staticX);break;
							case 0:default:SetPositionY(menu.name,GetOffsetPositionY()+menu.staticY);SetPositionX(menu.name,GetOffsetPositionX()+menu.staticX);break;
						}
			}
		};
};

function ACEMenu(menuName)
{
  this.itemArray=new Array(30);
  this.iac=0;
  this.count=1;
  if(menuName=='0')
  {
    ace_state.mainmenuArray[ace_state.mmac++]=this;
    this.name='ace'+ace_state.mmac;
    this.isMain=true;
    this.staticX=0;
    this.staticY=0;
  }
  else
  {
    ace_state.submenuArray[ace_state.smac++]=this;
    this.name='ace'+ace_state.mmac+'i'+menuName;
    this.isMain=false;
    this.staticX=-500;
    this.staticY=-500;
   } 
   this.isStatic=false;
   this.isAbsolute=false;
   this.staticPos='0';
   this.htmlBefore=ace_state.htmlBefore;
   this.htmlAfter=ace_state.htmlAfter;
   this.divider=ace_state.divider;
   this.imgHover=new Array(30);
   this.imgNormal=new Array(30);
   this.cellpadding=ace_state.cellpadding;
   this.cellspacing=ace_state.cellspacing;
   this.layerpadding=ace_state.layerpadding;
   this.target=ace_state.target;
   this.valign=ace_state.valign;
   this.menuborder=ace_state.menuborder;
   this.itemborder=ace_state.itemborder;
   this.offset=ace_state.offset;
   this.nowrap=ace_state.nowrap;
   this.fontFamily=ace_state.fontFamily;
   this.fontStyle=ace_state.fontStyle;
   this.fontSize=ace_state.fontSize;
   this.fontWeight=ace_state.fontWeight;
   this.nbgcolor=ace_state.nbgcolor;
   this.hbgcolor=ace_state.hbgcolor;
   this.nftcolor=ace_state.nftcolor;
   this.hftcolor=ace_state.hftcolor;
   this.position=ace_state.position;
   this.hspacing=ace_state.hspacing;
   this.bgimage=ace_state.bgimage;
   this.arrowimage=ace_state.arrowimage;
   this.textalign=ace_state.textalign;
   this.offsetLeft=ace_state.offsetLeft;
   this.offsetTop=ace_state.offsetTop;
   this.hbdrsize=ace_state.hbdrsize;
   this.hbdrcolor=ace_state.hbdrcolor;
   this.filters=ace_state.filters;
   this.ffade=ace_state.ffade;
   this.fopacity=ace_state.fopacity;
   this.fshadow=ace_state.fshadow;
   this.fblinds=ace_state.fblinds;
   this.fdissolve=ace_state.fdissolve;
   this.item='';
   this.width=ace_state.width;
   this.style='';
   this.BuildMenu=
      function()
      { 
         if(browser=="IE")
         {
            this.filters+='filter:';
            if(this.ffade!='') { this.filters+='blendTrans(Duration='+this.ffade+') ';}
            if(this.fopacity!=''){this.filters+='progid:DXImageTransform.Microsoft.Alpha(opacity='+this.fopacity+') ';}
            if(this.fshadow!=''){
               this.filters+='progid:DXImageTransform.Microsoft.Shadow(color='+this.fshadow+',direction=135,strength=2) ';
            }
            if(this.fblinds!=''){
               this.filters+='progid:DXImageTransform.Microsoft.Blinds(direction='+this.fblinds+', bands=1) ';
            }
            if(this.fdissolve!=''){
               this.filters+='progid:DXImageTransform.Microsoft.RandomDissolve(duration="'+this.fdissolve+'") ';
            }
         }
         if(this.isMain) {this.DisplayMenu();}
         if(!this.isMain||this.isStatic||this.isAbsolute)
         {
            document.writeln('<div id="'+this.name+'" align="left" style="position:absolute;background-color:'+this.nbgcolor+';z-index:30;visibility:hidden;left:'+this.staticX+'px;top:'+this.staticY+'px;'+this.filters+'" onmouseout="ace_state.menuActive=false;" onmouseover="ace_state.menuActive=true;">');
         }
         document.writeln('<table id="'+this.name+'table" ');
         if(this.nbgcolor!='transparent')
         {
            document.writeln('bgcolor="'+this.nbgcolor+'" ');
         }
         if(this.bgimage!=null&&this.bgimage!='null')
         {
            document.writeln('background="'+ace_state.ProcUrl(this.bgimage)+'" ');
         }
         if(this.width!='')
         {
            document.writeln('width="'+this.width+'px" ');
         }
         document.writeln('border="0px" cellpadding="'+this.cellpadding+'px" cellspacing="'+this.cellspacing+'px" onmouseout="ace_state.menuActive=false;" onmouseover="ace_state.menuActive=true;" style="border:'+this.menuborder+'">');
         if(ace_state.menuStatus==0)
         {
            document.writeln(this.item);
         } 
         document.writeln('</table>');
         if(!this.isMain||this.isStatic||this.isAbsolute) {
            document.writeln('</div>');
         }
      };
   this.AddItem=
      function(){
          this.AddMenuItem(true,arguments[0],arguments[1],arguments[2],arguments[3],arguments[4],arguments[5],arguments[6]);
      };
      
   this.AddSeparator=
      function(){
          if(arguments[0])
          {
            var imgSeparator=arguments[0];
            this.item+='<tr><td height="1px" '+this.nowrap+' valign="'+this.valign+'" style="'+this.style+';border:'+this.itemborder+'">';
            this.item+='<image src="'+imgSeparator+'" alt="" height="3" width="100%">';
            this.item+='</td></tr>';
          }
      };
      
   this.AddRollover=
      function(){
          this.AddMenuItem(false,arguments[1],arguments[2],arguments[0],arguments[3],arguments[4],arguments[5],arguments[6],arguments[7]);
      };
   this.AddMenuItem=
      function(){
          var text,addr,target,tip,imgNormal,imgHover,bSubmenu;var hbdr,borderNone='';
          var mname=this.name+'itm'+this.count;
          var offsetTop,offsetLeft;
          offsetTop=ace_state.offsetTop;
          offsetLeft=ace_state.offsetLeft;
          if(arguments[0])
          {
             text=arguments[1];
             addr=ace_state.ProcUrl(arguments[2]);
             target=arguments[3]; 
             bSubmenu=arguments[4];
             tip=arguments[5];
             if(arguments[6]!=undefined)
             {
                offsetLeft=arguments[6];
             }
             if(arguments[7]!=undefined) offsetTop=arguments[7];
             imgNormal=null;
             imgHover=null;
          }
          else{
             imgNormal=ace_state.ProcUrl(arguments[1]);
             imgHover=ace_state.ProcUrl(arguments[2]);
             addr=ace_state.ProcUrl(arguments[3]);
             target=arguments[4];
             bSubmenu=arguments[5];
             tip=arguments[6];
             if(arguments[7]!=undefined)offsetLeft=arguments[7];
             if(arguments[8]!=undefined)offsetTop=arguments[8];
          }
          if(tip==null){tip='';}
          if(bSubmenu==null){bSubmenu=false;}
          this.style='font-family:'+this.fontFamily+';font-style:'+this.fontStyle+';font-size:'+this.fontSize+';font-weight:'+this.fontWeight+';text-decoration:'+this.textDecoration;
          if(this.cellspacing==0)
          {
             if(this.tdc!=1){borderNone+='border-left: none; ';}
             if(this.count!=this.tdc){borderNone+='border-top: none; ';}
          }
          if(this.tdc==1){this.item+='<tr>';}
            else{
                if(this.hspacing!=0){
                   this.item+='<td width="'+this.hspacing+'px" style="border:'+this.itemborder+'; '+borderNone+'"><font color="'+this.nftcolor+'" style="'+this.style+'">'+this.divider+'</font></td>';
                }
            }
          if(this.bgimage!=null&&this.bgimage!='null'){this.nbgcolor='transparent';}
          if(target==null||target==''){target=this.target;}
          text=this.htmlBefore+text+this.htmlAfter;
          hbdr=this.hbdrsize+'px solid '+this.hbdrcolor;
          var arglist0='\''+this.name+'\',\''+this.count+'\', \''+this.hbgcolor+'\',\''+this.hftcolor+'\',\''+ace_state.imac+'\',\''+hbdr+'\','+offsetLeft+','+offsetTop+'';
          hbdr=this.hbdrsize+'px solid '+this.nbgcolor;
          var arglist1='\''+this.name+'\',\''+this.count+'\', \''+this.nbgcolor+'\',\''+this.nftcolor+'\',\''+ace_state.imac+'\',\''+hbdr+'\'';
          var arglist2='\''+this.name+'\',\''+this.count+'\','+offsetLeft+','+offsetTop+'';
          if(addr!=null&&addr!='null')
          {
              var s='';
              if((addr!='') && (!bSubmenu))
              {
                 s=addr.toLowerCase();
                 if(s.indexOf("javascript:")==0){s=addr.substring(11,addr.length);}
                   else
                   {
                       if(s.indexOf("'")==-1&&s.indexOf('"')==-1)
                       {
                          if(target=='_self'){s='location.href = \''+addr+'\'';}
                            else{s='window.open(\''+addr+'\',\''+target+'\')';}
                       }
                       else{s='';}
                   }
              }
              this.item+='<td onclick="ACEMenuOpen('+arglist2+');'+s+'" onmouseover="ACEMenuMouseHover('+arglist0+')"';
              this.item+=' onmouseout="ACEMenuClose('+arglist1+');" '+this.nowrap+' valign="'+this.valign+'" style="border:'+this.itemborder+'; '+borderNone+'">';
              this.item+='<div id="'+mname+'clip" style="position:absolute;width:1px;height:1px" align="'+this.textalign+'"></div>';
              if(this.arrowimage!=null&&bSubmenu) this.item+='<div><table id="'+mname+'" style="padding:'+this.layerpadding+'px; border:'+hbdr+'" align="'+this.textalign+'" cellpadding="0px" cellspacing="0px" border="0px" width="100%"><tr><td '+this.nowrap+'>';
	              else this.item+='<div id="'+mname+'" onmouseover="return;" style="padding:'+this.layerpadding+'px; border:'+hbdr+'" align="'+this.textalign+'">';

			  if (bSubmenu)
        {
          hrefAddress = "";
        }
        else
        {
          hrefAddress = 'href="'+addr+'"';
        }
        if ((bSubmenu)||(target!=this.target)) this.item+='<a '+hrefAddress+' title="'+tip+'" style="cursor: default; text-decoration:none" onmouseover="return;" onclick="return false;"';
				else this.item+='<a '+hrefAddress+' title="'+tip+'" style="text-decoration:none" onmouseover="return;" onclick="ACEMenuCloseDirect('+arglist1+');return false;"';

              if(addr=='') this.item+=' onmouseover="self.status=\'\';return true;"';
              if(target!='') this.item+=' target="'+target+'"';
              if(imgNormal!=null){
                 this.item+='><img id="'+this.name+'mi_img'+this.count+'" src="'+imgNormal+'" alt="'+tip+'" border="0"></a>';
                 ace_state.imgHoverArray[ace_state.imac]=new Image();
                 ace_state.imgHoverArray[ace_state.imac].src=imgHover;
                 ace_state.imgNormalArray[ace_state.imac]=new Image();
                 ace_state.imgNormalArray[ace_state.imac++].src=imgNormal;
              }
              else this.item+='><font id="'+this.name+'a'+this.count+'" style="'+this.style+';color:'+this.nftcolor+';">'+text+'</font></a>';
              if(this.arrowimage!=null&&bSubmenu)
              {
                 if(browser=="Opera") this.item+='</td><td nowrap width="1%">';
	                 else this.item+='</td><td nowrap width="1px">';
                 this.item+='<font style="'+this.style+';color:'+this.nftcolor+';"><img border="0" src="'+ace_state.ProcUrl(this.arrowimage)+'"></font></td></tr></table></div>';
              }
          }
          else 
          {
              this.item+='<td height="1px" '+this.nowrap+' valign="'+this.valign+'" onmouseout="ACEMenuClose('+arglist1+');" style="'+this.style+';border:'+this.itemborder+'">';
              this.item+='<div id="'+mname+'" style="padding:'+this.layerpadding+'px; color:'+this.nftcolor+'; border:'+hbdr+'">';
              this.item+=text;
          }
          this.item+='</div></td>';
          this.itemArray[this.iac++]=mname;
          if(this.tdc==this.columns) { this.item+='</tr>';this.tdc=1;}
            else{this.tdc++;}
          this.count++;
      };
      
   this.DisplayMenu=
      function()
      {
        ace_state.menuStatus=0;	
        ace_state.menuReady=true;
      };
   this.columns=ace_state.columns;
   this.tdc=1;
};

function ACEMenuOpen()
{
	if(!ace_state.ACELoadMenu) return;
	var name,count;
	var offsetTop,offsetLeft;
	var left;
	var top;
	var offset=0;
	var nm1;
	var nm2;
	var isMain=false;
	name=arguments[0];
	count=arguments[1];
	offsetLeft=arguments[2];
	offsetTop=arguments[3];
	nm1=name+'i'+count;
	nm2=name+'itm'+count;
	if(nm1.indexOf('i')==nm1.lastIndexOf('i')) isMain=true;
	var tmp_amac=ace_state.amac;
	for(var i=0;i<tmp_amac;i++)
		if(ace_state.activemenuArray[i]!=name)
			if(nm1.indexOf(ace_state.activemenuArray[i])==-1)
			{
				DisplayElement(ace_state.activemenuArray[i],false);
				ace_state.amac--;
			}
	if(IsExistObject(nm1))
	{
		ace_state.activemenuArray[ace_state.amac++]=nm1;
		var orientation='bottom';
		for(var i=0;i<ace_state.smac;i++)
			if(ace_state.submenuArray[i].name==nm1)
			{
				orientation=ace_state.submenuArray[i].position;
				offset=ace_state.submenuArray[i].offset;
				break;
			}
		for(var i=0;i<ace_state.mmac;i++)
		{
			var menu=ace_state.mainmenuArray[i];
			if(menu.name==name) isMain=!(menu.isStatic||menu.isAbsolute);
		}
		if(isMain)
		{
			left=ace_state.mleftoffset+offsetLeft;
			if(orientation=='top'||orientation=='bottom') left+=GetPositionX(nm2+'clip');
			if(orientation=='left') left+=GetPositionX(nm2+'clip')-GetWidth(nm1);
			if(orientation=='right') left+=GetPositionX(nm2+'clip')+GetWidth(nm2);
			top=GetPositionY(nm2+'clip')+ace_state.mtopoffset+offsetTop;
			if(orientation=='top')
			{
				top-=GetHeight(nm1);
				if(browser=="Opera") top+=2;
			}
			if(orientation=='bottom')
			{
				top+=GetHeight(nm2);
				if(browser=="Opera") top+=2;
			}
		}
		else
		{
			left=ace_state.mleftoffset+offsetLeft;
			if(orientation=='top'||orientation=='bottom') left+=GetPositionX(name)+GetPositionX(nm2+'clip');
			if(orientation=='left') left+=GetPositionX(name)+GetPositionX(nm2+'clip')-GetWidth(nm1);
			if(orientation=='right') left+=GetPositionX(name)+GetPositionX(nm2+'clip')+GetWidth(nm2);
			top=GetPositionY(name)+GetPositionY(nm2+'clip')+ace_state.mtopoffset+offsetTop;
			if(browser=="NS6"&&version!=6.1) top=GetPositionY(nm2+'clip');
			if(orientation=='bottom') top+=GetHeight(nm2);
			if(orientation=='top') top-=GetHeight(nm1);
		}
		left+=offset;
	}
	ace_state.menuActive=true;
	if(IsExistObject(nm1))
	{
		if(!isSafari) SetPositionX(nm1,-800);
		if(left<GetOffsetPositionX()) left=GetOffsetPositionX();
		if(left+GetWidth(nm1)>GetClientWidth()) left=GetClientWidth()-GetWidth(nm1);
		if(top<GetOffsetPositionY()) top=GetOffsetPositionY();
		if(top+GetHeight(nm1)>GetClientHeight()) top=GetClientHeight()-GetHeight(nm1);
		SetPositionY(nm1,top);
		SetPositionX(nm1,left);
		DisplayElement(nm1,true);
		setTimeout('SetZIndex("'+nm1+'", 30)',1);
	}
};

function ACEMenuMouseHover()
{
	var name,count;
	var bgcolor,ftcolor;
	var img;
	var border;
	name=arguments[0];
	count=arguments[1];
	bgcolor=arguments[2];
	ftcolor=arguments[3];
	img=arguments[4];
	border=arguments[5];
	for(var i=0;i<ace_state.tiac;i++) clearTimeout(ace_state.timeoutidArray[i]);
	ace_state.tiac=0;
	SetBackgroundColor(name+'itm'+count,bgcolor);
	SetForegroundColor(name+'a'+count,ftcolor);
	SetBorderStyle(name+'itm'+count,border);
	if(document.images[name+'mi_img'+count]) document.images[name+'mi_img'+count].src=ace_state.imgHoverArray[img].src;
	if(!ace_state.onclick)
	{
		ACEMenuOpen(name,count,arguments[6],arguments[7]);
		if(ace_state.hideidArray!=null)
			for(var i=0;i<ace_state.hideidArray.length;i++)
				DisplayElement(ace_state.hideidArray[i],false);
	}
};

function ACEMenuClose()
{
	var name,count;
	var bgcolor,ftcolor;
	var img;
	var border;
	name=arguments[0];
	count=arguments[1];
	bgcolor=arguments[2];
	ftcolor=arguments[3];
	img=arguments[4];
	border=arguments[5];
	if(bgcolor!=null&&ftcolor!=null)
	{
		SetBackgroundColor(name+'itm'+count,bgcolor);
		SetForegroundColor(name+'a'+count,ftcolor);
		SetBorderStyle(name+'itm'+count,border);
	}
	if(document.images[name+'mi_img'+count]) document.images[name+'mi_img'+count].src=ace_state.imgNormalArray[img].src;
	ace_state.menuActive=false;
	ace_state.timeoutidArray[ace_state.tiac++]=setTimeout("ACECloseMenus()",ace_state.closedelay);
};

function ACEMenuCloseDirect()
{
	var name,count;
	var bgcolor,ftcolor;
	var img;
	var border;
	name=arguments[0];
	count=arguments[1];
	bgcolor=arguments[2];
	ftcolor=arguments[3];
	img=arguments[4];
	border=arguments[5];
	if(bgcolor!=null&&ftcolor!=null)
	{
		SetBackgroundColor(name+'itm'+count,bgcolor);
		SetForegroundColor(name+'a'+count,ftcolor);
		SetBorderStyle(name+'itm'+count,border);
	}
	if(document.images[name+'mi_img'+count]) document.images[name+'mi_img'+count].src=ace_state.imgNormalArray[img].src;
	ace_state.menuActive=false;
	for(var i=0;i<ace_state.amac;i++) DisplayElement(ace_state.activemenuArray[i],false);
	ace_state.amac=0;
	if(ace_state.hideidArray!=null)
		for(var i=0;i<ace_state.hideidArray.length;i++) DisplayElement(ace_state.hideidArray[i],true);
	//window.status='Tunggu, sedang proses...';
	//document.body.style.cursor='wait';
};

function ACECloseMenus()
{
	if(!ace_state.menuActive)
	{
		for(var i=0;i<ace_state.amac;i++) DisplayElement(ace_state.activemenuArray[i],false);
		ace_state.amac=0;
		if(ace_state.hideidArray!=null)
			for(var i=0;i<ace_state.hideidArray.length;i++) DisplayElement(ace_state.hideidArray[i],true);
	}
};

function ACEDisplayMenu()
{
	for(var i=ace_state.mmcp;i<ace_state.mmac;i++)
	{
		ace_state.mainmenuArray[i].BuildMenu();
		DisplayElement(ace_state.mainmenuArray[i].name,true);
	}
	ace_state.mmcp=ace_state.mmac;
};

function ACECreateMenu(){
  for(var i=ace_state.smcp;i<ace_state.smac;i++){
    ace_state.submenuArray[i].BuildMenu();
  }
  ace_state.smcp=ace_state.smac;
  ace_state.ACECreateMenu=true;
};

function ACELoadMenu(){
  if(!ace_state.ACECreateMenu){
    return;
  }
  if(os=="Mac"&&browser=="IE"){
    ace_state.mtopoffset=parseInt(document.body.topMargin);
    ace_state.mleftoffset=parseInt(document.body.leftMargin);
  }
  ace_state.ACELoadMenu=true;
  for(var i=0;i<ace_state.mmac;i++){
    var menu=ace_state.mainmenuArray[i];
    if(menu.isStatic){
       setInterval('ace_state.StaticMenu()',50);
       break;
    }
  }
};

function SetBorderStyle(element,border)
{
	if(browser=="Opera"&&version<7) return;
	if(IsExistObject(element)) document.getElementById(element).style.border=border;
};

function SetZIndex(element,zIndex)
{
	if(IsExistObject(element))
	{
		document.getElementById(element).style.zIndex=zIndex+1;
		document.getElementById(element).style.zIndex=zIndex;
	}
};
