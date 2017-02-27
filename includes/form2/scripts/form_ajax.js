<!--

//constructor myDatagrid
function myDatagrid(phpScriptName, formName, dataGridId, totalNumberofData, specialButtonsID, useAJAX)
{
  this.phpScriptName = phpScriptName ;
  this.formName = formName;
  this.dataGridId = dataGridId;
  this.totalData = totalNumberofData;
  this.specialButtonsID = specialButtonsID;
  this.useAJAX = useAJAX;
  
  var objDataGrid = this;  
  if ($('searchKriteria'+dataGridId))
    Event.observe('searchKriteria'+dataGridId, 'keypress', 
      function(event)
      {
        var keyCode = event.keyCode;
        if ( keyCode == 0 ) // as it might on Moz
          keyCode = event.charCode;
        if ( keyCode == 0 ) // unlikely to get here
          keyCode = event.which;

        if (keyCode == 13)
        {
          //cancel enter event
          event.returnValue = false;
          if ( event.preventDefault ) event.preventDefault();
          objDataGrid.search();
        }
      }
    )
}

myDatagrid.prototype.loadGrid= function()
{
  var oDataGrid = document.getElementById(this.dataGridId);
	if(oDataGrid)
	{
    //initialisasi checkboxAll
		this.NumberTotal = this.totalData;

		this.checkboxAll = document.getElementById(this.dataGridId + "_chkAll");
    if (this.checkboxAll != null) this.checkboxAll.disabled = (this.NumberTotal==0);
    
		this.checkboxAllBottom  = document.getElementById(this.dataGridId + "_chkAllBottom");
    if (this.checkboxAllBottom != null) this.checkboxAllBottom.disabled = (this.NumberTotal==0);

    this.NumberChecked = 0;
    
    var f = eval("document." + this.formName);
    var myCounter = 0;
    //initialize item checkbox element and get total number  of checked item
    this.checkbox = new Array();
    for (var i=0; i < f.elements.length; i++)
    {
    	if ((f.elements[i].type=='checkbox') && (f.elements[i].id.indexOf(this.dataGridId)==0) && (f.elements[i].id != this.dataGridId+'_chkAll') && (f.elements[i].id != this.dataGridId+'_chkAllBottom'))
      {
        this.checkbox[myCounter] = f.elements[i];
        myCounter++;
        if (f.elements[i].checked) 
        {
          this.NumberChecked++;
          f.elements[i].parentNode.parentNode.className += 'Selected';
        }
      }
    }   
    

    //initialize special button (jika ada)
    if (this.specialButtonsID.length > 0)
    {
      this.specialButtons = new Array();
      for(i = 0; i < this.specialButtonsID.length; i++)
      {
     		this.specialButtons[i] = document.getElementById(this.specialButtonsID[i]);
        if (this.specialButtons[i] != null)
        {
          this.specialButtons[i].disabled = (this.NumberChecked == 0);
    		  if (this.NumberChecked == 0) this.specialButtons[i].className += 'Disabled';
        }
      }
    }
    else
      this.specialButtons = null;      
	}

  this.Datagrid = oDataGrid;

  this.pageSortBy = eval("document." + this.formName + ".pageSortBy" + this.dataGridId);
  this.pageNumber = eval("document." + this.formName + ".pageNumber" + this.dataGridId);
  this.pageJump   = eval("document." + this.formName + ".pageJump"   + this.dataGridId);
  this.pageLimit  = eval("document." + this.formName + ".pageLimit"  + this.dataGridId);
  
  var myDiv = document.getElementById('divIndicatorGrid');
  var progressText = document.getElementById('textGrid');
  if (myDiv != null)
  {
    myDiv.style.width = 160;
    myDiv.style.height = 30;
    myDiv.style.left = (document.body.clientWidth - 160)/2;
    myDiv.style.top = (document.body.clientHeight - 50)/2;
    myDiv.style.display = "none";
  }
  this.divIndicatorGrid = myDiv;
  this.progressText = progressText;
}

myDatagrid.prototype.itemListClicked = function(obj)
{
	var oTR = obj.parentNode.parentNode;
	if(obj.checked) 
	{
		oTR.className += 'Selected';
		this.NumberChecked++;
	}
	else 
	{
		oTR.className = oTR.className.replace("Selected","");//.substring(0,8);
		this.NumberChecked--;
	}
  if (this.checkboxAll != null)
    this.checkboxAll.checked = (this.NumberChecked == this.NumberTotal) ? true : false;
  if (this.checkboxAllBottom != null)
    this.checkboxAllBottom.checked = (this.NumberChecked == this.NumberTotal) ? true : false;

  this.refreshSpecialButton();
};

myDatagrid.prototype.refreshSpecialButton = function()
{
	if (this.specialButtons != null)
  {
    if (this.specialButtonsID.length > 0)
    {
      for(i = 0; i < this.specialButtonsID.length; i++)
      {
     		this.specialButtons[i].disabled = (this.NumberChecked == 0);//document.getElementById(this.specialButtonsID[i]);
        if (this.specialButtons[i].disabled)
        {
          if (this.specialButtons[i].className.indexOf('Disabled') < 0) 
            this.specialButtons[i].className += 'Disabled';
        }
        else
          this.specialButtons[i].className = this.specialButtons[i].className.replace('Disabled','');
      }
    }
  }
}

myDatagrid.prototype.checkAllClicked = function(obj)
{
	var bChecked = obj.checked;
  var otherID = obj.id;
  if (obj.id.indexOf("chkAllBottom") >= 0)
  {
    otherID = otherID.replace("chkAllBottom", "chkAll");
    document.getElementById(otherID).checked = bChecked;
  }
  else
  {
    otherID = otherID.replace("chkAll", "chkAllBottom");
    document.getElementById(otherID).checked = bChecked;
  }
	var aRows = this.Datagrid.tBodies[0].rows;
	var nRows = aRows.length-2;
  for (var i=0; i<this.checkbox.length; i++)
  {
    this.checkbox[i].checked=bChecked;
  }
	if (bChecked)
  {
		for(var i=0;i<nRows;i++)
      if (aRows[i+1].className != '' || aRows[i+1].className != null)
        aRows[i+1].className = aRows[i+1].className.substring(0,8) + 'Selected';
	}
  else
	{
    for(var i=0;i<nRows;i++)
      if (aRows[i+1].className != '' || aRows[i+1].className != null)
        aRows[i+1].className = aRows[i+1].className.substring(0,8);
  }

  this.NumberChecked = (bChecked) ? nRows : 0;

  this.refreshSpecialButton();
};

myDatagrid.prototype.showIndicator = function (txt) 
{
  this.progressText.innerHTML = txt;
  (document.all) ? this.divIndicatorGrid.style.display = "block" : this.divIndicatorGrid.style.display = "table-cell" ;
}

myDatagrid.prototype.goSort = function(kriteria) 
{
	if (kriteria != "") 
  {
		if (this.pageSortBy.value!='')
		{
			if (this.pageSortBy.value.substring(0,kriteria.length)==kriteria)
				if (this.pageSortBy.value.length == kriteria.length)
					this.pageSortBy.value = kriteria + ' DESC';
				else
					this.pageSortBy.value= kriteria;
			else
				this.pageSortBy.value = kriteria;
		}
		else
			this.pageSortBy.value = kriteria;

    this.showIndicator('Tunggu<br>Mengurutkan data...');
    
    this.submitToServer();
		//document.forms[this.formName].submit();
	}
}


myDatagrid.prototype.goPage = function (no) 
{
	if (no != "") 
  {
	  this.pageNumber.value = no;
	  this.pageJump.value = '0';
    this.showIndicator('Tunggu<br>Lompat ke halaman '+no+'...');

    this.submitToServer();
		//document.forms[this.formName].submit();
	}
}//goPage

myDatagrid.prototype.goPageStart = function (no) 
{
	if (no != "") 
  {
	  this.pageNumber.value = no;
	  this.pageJump.value = no;
    this.showIndicator('Tunggu<br>Lompat ke halaman '+no+'...');
		
    this.submitToServer();
    //document.forms[this.formName].submit();
	}
}//goPageStart

myDatagrid.prototype.setPageSize = function (pageSize) 
{
	if (pageSize != "") 
  {
	  this.pageLimit.value = pageSize;
    if (isNaN(pageSize))
      this.showIndicator('Tunggu<br>Ubah ke '+pageSize+' record...');
    else
      this.showIndicator('Tunggu<br>Ubah '+pageSize+' record / halaman...');

    this.submitToServer();
	}
}//setPageSize


myDatagrid.prototype.search = function() 
{
  this.showIndicator('Tunggu<br>Menyaring data...');

  var searchBy = eval("document." + this.formName + ".searchBy" + this.dataGridId + ".value");
  var searchKriteria = eval("document." + this.formName + ".searchKriteria" + this.dataGridId + ".value");
  
  this.submitToServer();
  return false;
}//search


myDatagrid.prototype.submitToServer = function()
{
  if (this.useAJAX)
  {
    var dataGridId = this.dataGridId;
    var queryString = 'ajax=1';
    queryString += '&pageNumber'+this.dataGridId+'='+$('pageNumber' +this.dataGridId).value;
    queryString += '&pageJump'+this.dataGridId+'='+$('pageJump' +this.dataGridId).value;
    queryString += '&pageCount'+this.dataGridId+'='+$('pageCount' +this.dataGridId).value;
    queryString += '&pageSortBy'+this.dataGridId+'='+$('pageSortBy' +this.dataGridId).value;
    queryString += '&pageLimit'+this.dataGridId+'='+$('pageLimit' +this.dataGridId).value;
    queryString += '&searchBy'+this.dataGridId+'='+$('searchBy' +this.dataGridId).value;
    queryString += '&searchKriteria'+this.dataGridId+'='+$('searchKriteria' +this.dataGridId).value;
    
    if (this.phpScriptName.indexOf("?") > 0)
    {
      queryString += "&" + this.phpScriptName.substring(this.phpScriptName.indexOf("?") + 1);
      this.phpScriptName = this.phpScriptName.substring(0, this.phpScriptName.indexOf("?"));
    }

    new Ajax.Request(this.phpScriptName,
    { method:'get',
      parameters: queryString,
      onComplete: function(transport, json)
      {
        //alert(transport.responseText);
        if ((transport.responseText || '') == '') return false;
        
        var responseData = transport.responseText;
        var headerContent =  '<!-- START OF DATAGRID CONTENT: ' + dataGridId + ' FOR AJAX -->';
        var idx = transport.responseText.indexOf(headerContent);

        if (idx >= 0) idx += headerContent.length; 

        responseData = responseData.substring(idx);
        idx = responseData.indexOf('<!-- END OF DATAGRID CONTENT: ' + dataGridId + ' FOR AJAX -->');
        if (idx >= 0) responseData = responseData.substring(0, idx);
                
        //alert(responseData);
        $('div'+dataGridId).update(responseData);        
      },
      onFailure: function()
      {
        $('message'+dataGridId).innerHTML = "Fail to request data from server via AJAX...";
      }
    });
  }
  else 
  {
    document.forms[this.formName].submit();
  }
}
-->