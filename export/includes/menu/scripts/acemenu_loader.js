var isDOM = (document.getElementById) ? true : false;
var isNS4 = (document.layers) ? true : false;

if (isDOM)
{
	var ace_obj = document.getElementById("acemenu_script");
	if (ace_obj != null)
	{
		ace_path = ace_obj.src.substr(0, ace_obj.src.lastIndexOf('/')+1);
	}
}

var ace_filename = '';
if (isDOM)
	ace_filename = ace_path + 'acemenu_dom.js';

if (isNS4)
	ace_filename = ace_path + 'acemenu_ns4.js';

document.write('<script src="'+ ace_filename +'" type="text/javascript"><\/script>');
