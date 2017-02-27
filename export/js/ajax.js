
var isBusy = false;

// fungsi untuk membuat http request object
function createRequestObject()
  {
    var ro;
    var browser = navigator.appName;

    if(browser == "Microsoft Internet Explorer")
    {
      try {
        ro = new ActiveXObject("Msxml2.XMLHTTP");
      }
      catch (othermicrosoft) {
        try {
        ro = new ActiveXObject("Microsoft.XMLHTTP");
        }
        catch (failed) {
        ro = false;
        }
      }
    }
    else
    // on every other browser, we can directly create a new XMLHttpRequest object
    {
      try {
        ro = new XMLHttpRequest();
      }
      catch (failed) {
        ro = false;
      }
    }
    if (!ro)
      alert("Error initializing XMLHttpRequest!");
    else return ro;
  } // createRequestObject


// fungsi untuk melakukan pemanggilan data
// ajak adalah objek xmlHTTprequest, method = post/get, url yg dituju, data=data yg dikirim
// func = fungsi yg dipanggil untuk proses data
function getDataAjax(ajax, method, url, data, func) {
  if (!isBusy)
  {
    isBusy = true;
    ajax.open(method, url,true);
    ajax.onreadystatechange = function () {
      showDataAjax(ajax, func);
    }
    ajax.send(data);
    isBusy = false;
  }

} //getData

// fungsi menampilkan data yang diterima
  function showDataAjax(ajax, func)
  {
    if(ajax.readyState == 4) {
      var response = ajax.responseText;
      if (response.charAt(0)==0)
      {
        response = ajax.responseText.substring(2);
      }
      if ((typeof func != "undefined") && func != null ) {
        eval(func + "(response)");
      }

    }
  }
  
  function getDataAjaxWithParam(ajax, method, url, data, func, params) 
  {
    if (!isBusy)
    {
      isBusy = true;
      ajax.open(method, url,true);
      ajax.onreadystatechange = function () {
        showDataAjaxWithParam(ajax, func, params);
      }
      ajax.send(data);
      isBusy = false;
    }
  } //getData

// fungsi menampilkan data yang diterima
  function showDataAjaxWithParam(ajax, func, params){
    if(ajax.readyState == 4) {
      var response = ajax.responseText;
      if (response.charAt(0)==0)
      {
        response = ajax.responseText.substring(2);
      }
      if ((typeof func != "undefined") && func != null ) {
        eval(func + "(response, params)");
      }

    }
  }