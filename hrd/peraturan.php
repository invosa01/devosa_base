<?php
include_once('../global/session.php');
include_once('global.php');
$file_name = getGetValue('file');
$file_folder = getGetValue('d');
echo "
 <link href='..//css/invosa.css' rel='stylesheet' type='text/css'>
<title>Company Regulation</title>
<br>
<table width='100%' border='0' cellpadding='0' cellspacing='0'>
  <tr>
    <td colspan=2 >
      <form name='formNews' id='formNews' method='POST'>
        <table width='100%' border='0' cellpadding='0' cellspacing='0'>
          <tr>
            <td  align='left' class='pageHeaderTitle'>
              <table border='0' cellspacing='0' cellpadding='2'>
                <tr>
                  <td width='30'><img src='../images/icons/about.png' border='0' width='30'/></td>
                  <td nowrap class='pageHeaderTitleText'>Company Regulation</td><td><a href='main.php'>Back</a></td>
                </tr>
              </table>
            </td>
          </tr>
          <tr><td>&nbsp;</td></tr>
          <tr>
            <td>
					<div>
						<iframe src='http://docs.google.com/viewer?
						url=http://hr.patra-sk.com/hrd/regulation/{$file_folder}/{$file_name}&amp;embedded=true' width='100%' height='400px'/>
					</div>
			</td>
          </tr>
        </table>
      </form>
    </td>
  </tr>
</table>

";
?>