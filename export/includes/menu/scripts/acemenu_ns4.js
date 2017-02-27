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
		if(navigator.userAgent.indexOf("6.01")!=-1||navigator.userAgent.indexOf("6.0")!=-1)
			version=6;
		else
			version=6.1;
	}
	if(document.all)
	{
		if(document.getElementById) version=5; else version=4;
		browser="IE";
	}
	if(navigator.userAgent.indexOf("Opera")!=-1) browser="Opera";
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
	return(evt.pageX);
};

function GetEventY(evt)
{
	return(evt.pageY);
};

function IsExistObject(layerName,parentName)
{
	if(arguments.length==2)
		return(document.layers[parentName].document.layers[layerName]!=undefined);
	else
		return(document.layers[layerName]!=undefined);
};

function IsVisible(element)
{
	if(document.layers[element]!=undefined)
		if(document.layers[element].visibility=="show") return true; else return false;
};

function DisplayElement(element,show,parent)
{
	if(arguments.length>=3)
	{
		if(document.layers[parent].layers[element]!=undefined)
			if(show) document.layers[parent].layers[element].visibility="show";
			else document.layers[parent].layers[element].visibility="hide";
	}
	else
	{
		if(document.layers[element]!=undefined)
			if(show) document.layers[element].visibility="show";
			else document.layers[element].visibility="hide";
	}
};

function SetBackgroundColor(element,bgColor,parent)
{
	if(arguments.length>=3)
	{
		if(bgColor=="transparent") document.layers[parent].document.layers[element].bgColor=null;
		else document.layers[parent].document.layers[element].bgColor=bgColor;
	}
	else
	{
		if(bgColor=="transparent") document.layers[element].bgColor=null;
		else document.layers[element].bgColor=bgColor;
	}
};

function SetForegroundColor(element,fgColor,parent){};

function SetPositionX(element,left,parent)
{
	if(arguments.length>=3) document.layers[parent].document.layers[element].left=left;
	else document.layers[element].left=left;
};

function GetPositionX(element,parent)
{
	if(arguments.length>=2) return(document.layers[parent].document.layers[element].pageX);
	else return(document.layers[element].pageX);
};

function SetPositionY(element,top,parent)
{
	if(arguments.length>=3) document.layers[parent].document.layers[element].top=top;
	else document.layers[element].top=top;
};

function GetPositionY(element,parent)
{
	if(arguments.length>=2) return(document.layers[parent].document.layers[element].pageY);
	else return(document.layers[element].pageY);
};

function SetHeight(element,height,parent)
{
	if(arguments.length>=3) document.layers[parent].document.layers[element].clip.height=height;
	else{document.layers[element].clip.height=height;
};

function GetHeight(element,parent)
{
	if(arguments.length>=2) return(document.layers[parent].document.layers[element].clip.height);
	else return(document.layers[element].clip.height);
};

function SetWidth(element,width,parent)
{
	if(arguments.length>=3) document.layers[parent].document.layers[element].clip.width=width;
	else document.layers[element].clip.width=width;
};

function GetWidth(element,parent)
{
	if(arguments.length>=2) return(document.layers[parent].document.layers[element].clip.width);
	else return(document.layers[element].clip.width);
};

function GetClientWidth()
{
	return(window.innerWidth+window.pageXOffset);
};

function GetOffsetPositionX()
{
	return(window.pageXOffset);
};

function GetOffsetPositionY()
{
	return(window.pageYOffset);
};

function GetClientHeight()
{
	return(window.innerHeight+window.pageYOffset);
};

browser=GetBrowserName();
os=GetOSName();
ace_state=new InitializeMenu();
function InitializeMenu()
{
	this.ver='3.5.0';
	this.id='52084';
	this.menuActive=false;
	this.submenuArray=new Array(50);
	this.mainmenuArray=new Array(5);
	this.activemenuArray=new Array(10);
	this.timeoutidArray=new Array(50);
	this.imgNormalArray=new Array(50);
	this.imgHoverArray=new Array(50);
	this.imac=0;this.mmcp=0;
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
	this.menuoffset=1;
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
	this.hspacing=1;
	this.columns=1;
	this.bgimage=null;
	this.arrowimage=null;
	this.textalign='left';
	this.filters='';
	this.ffade='';
	this.fopacity='';
	this.fshadow='';
	this.htmlBefore='&nbsp;&nbsp;';
	this.htmlAfter='&nbsp;&nbsp;';
	this.divider='&nbsp;';
	this.cellpadding=1;
	this.cellspacing=0;
	this.layerpadding=0;
	this.target='_self';
	this.valign='middle';
	this.menuborder='';
	this.itemborder='';
	this.offset=0;
	this.nowrap='nowrap';
	this.IsRelative=
		function(str)
		{
			if(str==null) return false;
			if(str=='null') return false;
			if(str=='') return false;
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
			if(ace_state.IsRelative(str))return ace_path+str;
			else return str; 
		};
	this.StaticMenu=
		function()
		{
			for(var i=0;i<ace_state.mmac;i++)
			{
				var menu=ace_state.mainmenuArray[i];
				if(menu.isStatic)
					switch(menu.staticPos)
					{
						case 1:
							SetPositionY(menu.name,GetOffsetPositionY()+menu.staticY);
							SetPositionX(menu.name,GetClientWidth()+menu.staticX);
							break;
						case 2:
							SetPositionY(menu.name,GetClientHeight()+menu.staticY);
							SetPositionX(menu.name,GetOffsetPositionX()+menu.staticX);
							break;
						case 3:
							SetPositionY(menu.name,GetClientHeight()+menu.staticY);
							SetPositionX(menu.name,GetClientWidth()+menu.staticX);
							break;
						case 0: default:
							SetPositionY(menu.name,GetOffsetPositionY()+menu.staticY);
							SetPositionX(menu.name,GetOffsetPositionX()+menu.staticX);
							break;
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
	this.filters=ace_state.filters;
	this.ffade=ace_state.ffade;
	this.fopacity=ace_state.fopacity;
	this.fshadow=ace_state.fshadow;
	this.item='';
	this.width='';
	this.style='';
	this.textDecoration='none';
	this.__setStyle=
		function(element)
		{
			document.ids[element].fontFamily=this.fontFamily;
			document.ids[element].fontSize=this.fontSize;
			document.ids[element].fontStyle=this.fontStyle;
			document.ids[element].fontWeight=this.fontWeight;
			document.ids[element].textDecoration=this.textDecoration;
		};
	this.BuildMenu=
		function()
		{
			if(this.isMain) this.DisplayMenu();
			if(!this.isMain||this.isStatic)
			{
				document.writeln('<layer id="'+this.name+'" ');
				if(this.nbgcolor!='transparent') document.writeln('bgColor="'+this.nbgcolor+'" ');
				if(this.width!='') document.writeln('width="'+this.width+'" ');
				if(this.isStatic) document.writeln('left="'+this.staticX+'" top="'+this.staticY+'" ');
				document.writeln('visibility="hide" z-index="30" onmouseout="ace_state.menuActive=false;" onmouseover="ace_state.menuActive=true;"><layer visibility="hide"></layer>');
			}
			document.writeln('<table ');
			if(this.nbgcolor!='transparent') document.writeln('bgcolor="'+this.nbgcolor+'" ');
			if(this.bgimage!=null&&this.bgimage!='null') document.writeln('background="'+ace_state.ProcUrl(this.bgimage)+'" ');
				else document.writeln('background="" ');
			if(this.width!=''&&this.isMain) document.writeln('width="'+this.width+'" ');
			document.writeln('border="0" cellpadding="'+this.cellpadding+'" cellspacing="'+this.cellspacing+'">');
			if(ace_state.menuStatus==0) document.writeln(this.item);
			document.writeln('</table>');
			if(!this.isMain||this.isStatic) document.writeln('</layer>');
		};
	this.AddItem=
		function()
		{
			this.AddMenuItem(true,arguments[0],arguments[1],arguments[2],arguments[3],arguments[4]);
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
		function()
		{
			this.AddMenuItem(false,arguments[1],arguments[2],arguments[0],arguments[3],arguments[4],arguments[5]);
		};
	this.AddMenuItem=
		function()
		{
			var text,addr,target,tip,imgNormal,imgHover,bSubmenu;
			var mname=this.name+'itm'+this.count;
			if(arguments[0])
			{
				text=arguments[1];
				addr=ace_state.ProcUrl(arguments[2]);
				target=arguments[3];
				bSubmenu=arguments[4];
				tip=arguments[5];
				imgNormal=null;
				imgHover=null;
			}
			else
			{
				imgNormal=ace_state.ProcUrl(arguments[1]);
				imgHover=ace_state.ProcUrl(arguments[2]);
				addr=ace_state.ProcUrl(arguments[3]);
				target=arguments[4];
				bSubmenu=arguments[5];
				tip=arguments[6];
			}
			if(this.tdc==1) this.item+='<tr>';
			else
			{
				if(this.hspacing!=0) this.item+='<td width="'+this.hspacing+'"><font color="'+this.nftcolor+'" id="'+this.name+'d'+this.count+'">'+this.divider+'</font></td>';
				this.__setStyle(this.name+'d'+this.count);
			}
			if(this.bgimage!=null&&this.bgimage!='null') this.nbgcolor='transparent';
			if(target==null||target=='') target=this.target;
			text='<font color="'+this.nftcolor+'">'+this.htmlBefore+text+this.htmlAfter+'</font>';
			this.item+='<td nowrap valign="'+this.valign+'">';
			this.item+='<ilayer id="'+mname+'clip" z-index="28" visibility="hide">';
			if(addr!=null&&addr!='null')
			{
				this.item+='<a href="'+addr+'" id="'+this.name+'ai'+this.count+'">';
				if(imgNormal!=null) this.item+='<img src="'+imgNormal+'" border="0"></font></a>';
					else this.item+=text+'</a>';
			}
			else this.item+=text;
			this.item+='</ilayer>';
			this.item+='<layer id="'+mname+'" z-index="29" width="500" ';
			if(this.isMain) this.item+='visibility="hide" ';
			if(this.nbgcolor!='transparent') this.item+='bgColor="'+this.nbgcolor+'" ';
			var arglist0='\''+this.name+'\', \''+this.count+'\', \''+this.hbgcolor+'\', \''+this.hftcolor+'\', \''+ace_state.imac+'\'';
			var arglist1='\''+this.name+'\', \''+this.count+'\', \''+this.nbgcolor+'\', \''+this.nftcolor+'\', \''+ace_state.imac+'\'';
			if(addr!=null&&addr!='null')
			{
				this.item+='onmouseover="ACEMenuMouseHover('+arglist0+')" ';
				this.item+='onmouseout="ACEMenuClose('+arglist1+')">';
				if(imgNormal!=null)
				{
					ace_state.imgHoverArray[ace_state.imac]=new Image();
					ace_state.imgHoverArray[ace_state.imac].src=imgHover;
					ace_state.imgNormalArray[ace_state.imac]=new Image();
					ace_state.imgNormalArray[ace_state.imac++].src=imgNormal;
				}
				this.item+='<a href="'+addr+'" title="'+tip+'" id="'+this.name+'a'+this.count+'"';
				if(target!='') this.item+=' target="'+target+'"';
				if(addr=='') this.item+=' onclick="ACEMenuOpen('+arglist0+');return false;"';
				if(imgNormal!=null) this.item+='><img name="'+this.name+'img'+this.count+'" src="'+imgNormal+'" border="0" alt="'+tip+'"></a>';
					else this.item+='>'+text+'</a>';
				this.__setStyle(this.name+'ai'+this.count);
				this.__setStyle(this.name+'a'+this.count);
			}
			else
			{
				this.item+='onmouseout="ACEMenuClose('+arglist1+')">';
				this.item+='<font id="'+this.name+'a'+this.count+'">'+text+'</font>';
				this.__setStyle(this.name+'a'+this.count);
			}
			this.item+='</layer></td>';
			this.itemArray[this.iac++]=mname;
			if(this.tdc==this.columns) this.item+='</tr>';this.tdc=1;
				else this.tdc++;
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

function ACEMenuOpen(name,count,bgcolor,ftcolor,img)
{
	if(!ace_state.ACELoadMenu) return;
	var left;
	var top;
	var offset=0;
	var nm1=name+'i'+count;
	var nm2=name+'itm'+count;
	var isMain=false;
	if(nm1.indexOf('i')==nm1.lastIndexOf('i')) isMain=true;
	for(var i=0;i<ace_state.mmac;i++)
	{
		var menu=ace_state.mainmenuArray[i];
		if(menu.name==name) isMain=!menu.isStatic;
	}
	ACECloseMenus();
	if(!isMain)
	{
		var tmp_amac=ace_state.amac;
		for(var i=0;i<tmp_amac;i++)
			if(ace_state.activemenuArray[i]!=name)
				if(nm1.indexOf(ace_state.activemenuArray[i])==-1)
				{
					DisplayElement(ace_state.activemenuArray[i],false);
					ace_state.amac--;
				}
	}
	if(IsExistObject(nm1))
	{
		var orientation='bottom';
		for(var i=0;i<ace_state.smac;i++)
		{
			if(ace_state.submenuArray[i].name==nm1)
			{
				orientation=ace_state.submenuArray[i].position;
				offset=ace_state.submenuArray[i].offset;
				break;
			}
		}
		ace_state.activemenuArray[ace_state.amac++]=nm1;
		if(isMain)
		{
			if(orientation=='right')
			{
				left=GetPositionX(nm2)+GetWidth(nm2)-ace_state.menuoffset;
				top=GetPositionY(nm2);
			}
			if(orientation=='left')
			{
				left=GetPositionX(nm2)-GetWidth(nm1)+ace_state.menuoffset;
				top=GetPositionY(nm2);
			}
			if(orientation=='bottom')
			{
				left=GetPositionX(nm2);
				top=GetPositionY(nm2)+GetHeight(nm2)-ace_state.menuoffset;
			}
			if(orientation=='top')
			{
				left=GetPositionX(nm2);
				top=GetPositionY(nm2)-GetHeight(nm1)+ace_state.menuoffset;
			}
		}
		else
		{
			if(orientation=='right')
			{
				left=GetPositionX(nm2+'clip',name)+GetWidth(nm2,name)-ace_state.menuoffset;
				top=GetPositionY(nm2,name);
			}
			if(orientation=='left')
			{
				left=GetPositionX(nm2+'clip',name)-GetWidth(nm1)+ace_state.menuoffset;
				top=GetPositionY(nm2,name);
			}
			if(orientation=='bottom')
			{
				left=GetPositionX(nm2+'clip',name);
				top=GetPositionY(nm2,name)+GetHeight(nm2,name)-ace_state.menuoffset;
			}
			if(orientation=='top')
			{
				left=GetPositionX(nm2+'clip',name);
				top=GetPositionY(nm2,name)-GetHeight(nm1)+ace_state.menuoffset;
			}
		}
		ace_state.menuActive=true;
		left+=offset;
		SetPositionY(nm1,top);
		SetPositionX(nm1,left);
	}
	if(IsExistObject(nm1))
	{
		if(left<GetOffsetPositionX()) SetPositionX(nm1,GetOffsetPositionX());
		if(left+GetWidth(nm1)>GetClientWidth()) SetPositionX(nm1,GetClientWidth()-GetWidth(nm1));
		if(top<GetOffsetPositionY()) SetPositionY(nm1,GetOffsetPositionY());
		if(top+GetHeight(nm1)>GetClientHeight()) SetPositionY(nm1,GetClientHeight()-GetHeight(nm1));
	}
	DisplayElement(nm1,true);
};

function ACEMenuMouseHover(name,count,bgcolor,ftcolor,img)
{
	for(var i=0;i<ace_state.tiac;i++)
		clearTimeout(ace_state.timeoutidArray[i]);
	ace_state.tiac=0;
	var nm1=name+'i'+count;
	var isMain=false;
	if(nm1.indexOf('i')==nm1.lastIndexOf('i')) isMain=true;
	for(var i=0;i<ace_state.mmac;i++)
	{
		var menu=ace_state.mainmenuArray[i];
		if(menu.name==name) isMain=!menu.isStatic;
	}
	var nm2=name+'itm'+count;
	var s=false;
	if(arguments.length==5)
		if(isMain)
		{
			SetBackgroundColor(nm2,bgcolor);
			SetForegroundColor(name+'a'+count,ftcolor);
			s=document.layers[nm2].document.images[name+'img'+count];
		}
		else
		{
			SetBackgroundColor(nm2,bgcolor,name);
			SetForegroundColor(name+'a'+count,ftcolor,name);
			s=document.layers[name].document.layers[nm2].document.images[name+'img'+count];
		}
	if(s) s.src=ace_state.imgHoverArray[img].src;
	if(!ace_state.onclick)
	{
		ACEMenuOpen(name,count,bgcolor,ftcolor,img);
		if(ace_state.hideidArray!=null)
			for(var i=0;i<ace_state.hideidArray.length;i++)
				DisplayElement(ace_state.hideidArray[i],false);
	}
};

function ACEMenuClose(name,count,bgcolor,ftcolor,img)
{
	var nm1=name+'i'+count;
	var isMain=false;
	if(nm1.indexOf('i')==nm1.lastIndexOf('i')) isMain=true;
	for(var i=0;i<ace_state.mmac;i++)
	{
		var menu=ace_state.mainmenuArray[i];
		if(menu.name==name) isMain=!menu.isStatic;
	}
	if(arguments.length!=3)
		var s;
		if(isMain)
		{
			SetBackgroundColor(name+'itm'+count,bgcolor);
			SetForegroundColor(name+'a'+count,ftcolor);
			ace_state.menuActive=false;
			s=document.layers[name+'itm'+count].document.images[name+'img'+count];
		}
		else
		{
			SetBackgroundColor(name+'itm'+count,bgcolor,name);
			SetForegroundColor(name+'a'+count,ftcolor,name);
			s=document.layers[name].document.layers[name+'itm'+count].document.images[name+'img'+count];
		}
		if(s) s.src=ace_state.imgNormalArray[img].src;
	if(arguments.length==3) ace_state.menuActive=false;
	ace_state.timeoutidArray[ace_state.tiac++]=setTimeout("ACECloseMenus()",ace_state.closedelay);
};

function ACECloseMenus()
{
	if(!ace_state.menuActive)
	{
		for(var i=0;i<ace_state.amac;i++)
			DisplayElement(ace_state.activemenuArray[i],false);
		ace_state.amac=0;
		if(ace_state.hideidArray!=null)
			for(var i=0;i<ace_state.hideidArray.length;i++)
				DisplayElement(ace_state.hideidArray[i],true);
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

function ACECreateMenu()
{
	for(var i=ace_state.smcp;i<ace_state.smac;i++)
		ace_state.submenuArray[i].BuildMenu();
	ace_state.smcp=ace_state.smac;
	ace_state.ACECreateMenu=true;
};

function ACELoadMenu()
{
	if(!ace_state.ACECreateMenu) return;
	for(var i=0;i<ace_state.mmac;i++)
	{
		var menu=ace_state.mainmenuArray[i];
		var width=0;
		var height=0;
		var tdwidtharray=new Array(menu.columns);
		var tdc=0;
		for(var j=0;j<menu.columns;j++) tdwidtharray[j]=0;
		if(menu.isStatic)
		{
			for(var j=0;j<menu.iac;j++)
			{
				if(IsExistObject(menu.itemArray[j]+'clip',menu.name))
					if(tdwidtharray[tdc]<GetWidth(menu.itemArray[j]+'clip',menu.name))
						tdwidtharray[tdc]=GetWidth(menu.itemArray[j]+'clip',menu.name);
				tdc++;
				if(tdc==menu.columns) tdc=0;
			}
			tdc=0;
			for(var j=0;j<menu.iac;j++)
			{
				if(IsExistObject(menu.itemArray[j],menu.name))
				{
					SetWidth(menu.itemArray[j],tdwidtharray[tdc],menu.name);
					SetPositionX(menu.itemArray[j],GetPositionX(menu.itemArray[j]+'clip',menu.name)-GetPositionX(menu.name),menu.name);
					SetPositionY(menu.itemArray[j],GetPositionY(menu.itemArray[j]+'clip',menu.name)-GetPositionY(menu.name),menu.name);
					DisplayElement(menu.itemArray[j],true,menu.name);
				}
				tdc++;
				if(tdc==menu.columns) tdc=0;
			}
		}
		else
		{
			for(var j=0;j<menu.iac;j++)
			{
				if(IsExistObject(menu.itemArray[j]+'clip'))
					if(tdwidtharray[tdc]<GetWidth(menu.itemArray[j]+'clip'))
						tdwidtharray[tdc]=GetWidth(menu.itemArray[j]+'clip');
				tdc++;
				if(tdc==menu.columns) tdc=0;
			}
			tdc=0;
			for(var j=0;j<menu.iac;j++)
			{
				if(IsExistObject(menu.itemArray[j]))
				{
					SetWidth(menu.itemArray[j],tdwidtharray[tdc]);
					SetPositionX(menu.itemArray[j],GetPositionX(menu.itemArray[j]+'clip'));
					SetPositionY(menu.itemArray[j],GetPositionY(menu.itemArray[j]+'clip'));
					DisplayElement(menu.itemArray[j],true);
				}
				tdc++;
				if(tdc==menu.columns) tdc=0;
			}
		}
	}
	for(var i=0;i<ace_state.smac;i++)
	{
		var menu=ace_state.submenuArray[i];
		var width=0;
		var height=0;
		var tdwidtharray=new Array(menu.columns);
		var tdc=0;
		for(var j=0;j<menu.columns;j++) tdwidtharray[j]=0;
		for(var j=0;j<menu.iac;j++)
		{
			if(tdwidtharray[tdc]<GetWidth(menu.itemArray[j]+'clip',menu.name))
				tdwidtharray[tdc]=GetWidth(menu.itemArray[j]+'clip',menu.name);
			tdc++;
			if(tdc==menu.columns) tdc=0; 
		}
		tdc=0;
		for(var j=0;j<menu.iac;j++)
		{
			SetWidth(menu.itemArray[j],tdwidtharray[tdc],menu.name);
			SetPositionX(menu.itemArray[j],GetPositionX(menu.itemArray[j]+'clip',menu.name)-8,menu.name);
			SetPositionY(menu.itemArray[j],GetPositionY(menu.itemArray[j]+'clip',menu.name)-GetPositionY(menu.name),menu.name);
			tdc++;
			if(tdc==menu.columns) tdc=0;
		}
		var t=menu.cellpadding+menu.layerpadding;
		width=tdwidtharray[0]+t*2;
		for(var j=1;j<menu.columns;j++) width+=tdwidtharray[j]+menu.hspacing+t*4;
		SetWidth(menu.name,width);
	}
	ace_state.ACELoadMenu=true;
	for(var i=0;i<ace_state.mmac;i++)
	{
		var menu=ace_state.mainmenuArray[i];
		if(menu.isStatic)
		{
			setInterval('ace_state.StaticMenu()',50);
			break;
		}
	}
};

window.onresize=
	function()
	{
		if(browser=='NS4'&&origWidth==window.innerWidth&&origHeight==window.innerHeight) return;
		origWidth=window.innerWidth;
		origHeight=window.innerHeight;
	};

origWidth=window.innerWidth;
origHeight=window.innerHeight;
