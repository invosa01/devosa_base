<?php
include_once('global.php');
include_once('calendar.php');
$db = new cDbClass;
$db->connect();
$isAJAX = (getGetValue("ajax", 0));
$strDataMonth = getGetValue("month");
$strDataWYear = getGetValue("year");
if (!$isAJAX) {
    echo "<div style='font-size: 14pt; text-align: center'>HRD TIME TABLE<br>" . strtoupper(
            getBulan($strDataMonth)
        ) . " - " . $strDataWYear . "</div><br><br>";
    echo <<<EOD
<html>
<head>
  <link href="../css/style.css" rel="stylesheet" type="text/css">
  <link href="../css/calendar.css" rel="stylesheet" type="text/css">
</head>
<body style="font-size:10px; font-family='Arial, Helvetica'">
EOD;
    echo getMonthlyCalendar($db, $strDataMonth, $strDataWYear, "50", true);
    echo "
  <script type='text/javascript'> window.print(); </script>
</body>
</html>";
} else {
    echo strtoupper(getBulan($strDataMonth)) . " - " . $strDataWYear;
    echo "|||";
    echo getMonthlyCalendar($db, $strDataMonth, $strDataWYear, "50", true);
}
?>
