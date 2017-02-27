var isBusy = false;

//parameters URL, params, method
function ajaxRequest(url, params, func, postMethod)
{
  if (isBusy) return false;
  isBusy = true;

  if ( postMethod === undefined ) postMethod = "get";
  else if (( postMethod != 'get') || ( postMethod != 'post' )) postMethod = "get";

  params += "&ajax=1";
  new Ajax.Request(url,
  { method: postMethod,
    parameters: params,
    onComplete: function(transport, json)
    {
      isBusy = false;
      //alert(transport.responseText  );
      //if ((transport.responseText || '') == '') return false;
      eval(func + "(transport.responseText)");      
    },
    onLoading: function()
    {
    },
    onFailure: function()
    {
      isBusy = false;
    }
  });
}