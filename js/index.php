<html>
<head><title>Hai</title>
</head>
<body>
<?php
echo "<tt>";
echo "WELCOME    ----------- " . date("r") . " <br>";
echo "Your Agent ----------- " . $_SERVER['HTTP_USER_AGENT'] . " <br>";
echo "Your IP    ----------- " . $_SERVER['REMOTE_ADDR'] . " <br>";
echo "</tt>";
?>
</body>
</html>