<?php
session_start();
include_once("global.php");
include_once("global/common_function.php");
writeLog(ACTIVITY_LOGOUT);
session_unset();
session_destroy();
?>
<html>
<head>
    <title>Exit Application</title>
    <meta http-equiv="Content-Type" content="text/html; charset=us-ascii" />
    <link rel="stylesheet" href="css/login.css" type="text/css" />
    <script type="text/javascript">
        var timer = null;
        var interval = 10;
        window.onload = function () {
            document.getElementById("second").innerHTML = interval;
            timer = setInterval("countDown()", 1000);
        }

        function countDown() {
            var sec = document.getElementById("second");
            if (sec.innerHTML == '1') {
                document.getElementById("refreshInfo").innerHTML = "Please wait, redirecting to login page...";
                location.href = "index.php";
                clearInterval(timer);
            }
            else {
                sec.innerHTML = parseInt(sec.innerHTML) - 1;
            }
        }
    </script>
</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0" class="mainTable" bgcolor="#dddddd">
    <tr>
        <td>
            <table width="410"
                   border="0"
                   align="center"
                   cellpadding="2"
                   cellspacing="0"
                   style="background-color: #ffffff; border: 1px solid lightgray">
                <tr>
                    <td align="center" class="logo" style="padding:10px;">
                        <a href="index.php"
                           style="text-decoration: none;color: #999;font-size:24px;text-shadow: 1px 1px 3px rgba(150, 150, 150, 0.7);">
                            <span class="brand">Employee Self Service</span>
                        </a>
                    </td>
                </tr>
                <tr>
                    <td align="center" class="inputBox">
                        <BR />

                        <form action="index.php" name="LoginForm" method="post">
                            <table align='center' border="0" cellspacing="0" cellpadding="3">
                                <tr>
                                    <td align=center>You have been logged out from deVOSA</td>
                                </tr>
                                <tr>
                                    <td align=center><a href="index.php"><strong>Back to Login Page</strong></a></td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                </tr>
                            </table>
                        </form>
                        <br />
                    </td>
                </tr>
                <tr>
                    <td align="center" class="copyright" bgcolor="#666666"><br><?php echo COPYRIGHT; ?><br>&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
