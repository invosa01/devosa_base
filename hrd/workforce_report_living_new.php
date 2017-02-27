<?php
(isset($_REQUEST['dataInterval']) && $_REQUEST['dataInterval'] > 0) ? $strDataInterval = $_REQUEST['dataInterval'] : $strDataInterval = 10;
$arrData = [];
$maxAxis = 10;
$untilAxis = date("Y"); // curr year
$startAxis = $untilAxis - $maxAxis;
//get all position
$strSQL = "SELECT DISTINCT primary_city FROM hrd_employee";
$resSQL = $db->execute($strSQL);
$arrCode = [];
$numCode = 0;
while ($rowDb = $db->fetchrow($resSQL)) {
  $arrCode[$numCode++] = $rowDb["primary_city"];
}
// generate
for ($iYear = $startAxis; $iYear <= $untilAxis; $iYear++) {
  $strSQL = "select COUNT(*) from hrd_employee AS t1
    WHERE ((resign_date <= '" . $iYear . "1231' and resign_date >= '" . $iYear . "0101') or resign_date is null)
    and join_date<='" . $iYear . "1231' AND " . $strKriteria;
  for ($i = 0; $i < $numCode; $i++) {
    $strExecuteKriteria = " AND t1.primary_city = '$arrCode[$i]' ";
    $strExecuteSQL = $strSQL . $strExecuteKriteria;
    $numOfEmployee = $db->execute($strExecuteSQL);
    $numOfEmployee = $db->fetchrow($numOfEmployee);
    $numOfEmployee = $numOfEmployee["count"];
    //  echo $strDataMale."<br/>";
    $arrData[$i][$iYear] = $numOfEmployee;
    if ($arrCode[$i] == "") {
      $arrData[$i]["label"] = "No Area";
    } else {
      $arrData[$i]["label"] = $arrCode[$i];
    }
  }
}
//var_dump($arrData);
$strData = "";
$strDataStack = "";
$strData100 = "";
$i = 0;
foreach ($arrData as $key => $arrDataYear) {
  if ($i > 0) {
    $strData .= ",";
    $strDataStack .= ",";
    $strData100 .= ",";
  }
  $i++;
  $strData .= "{
    type: \"column\",
    toolTipContent:\"({name}) : {y}\",
    showInLegend: true,
    name: \"" . $arrDataYear["label"] . "\",
    dataPoints: [";
  $strDataStack .= "{
      type: \"stackedColumn\",
      toolTipContent:\"({name}) : {y}\",
      showInLegend: true,
      name: \"" . $arrDataYear["label"] . "\",
      dataPoints: [";
  $strData100 .= "{
        type: \"stackedColumn100\",
        toolTipContent:\"({name}) : {y} %\",
        showInLegend: true,
        name: \"" . $arrDataYear["label"] . "\",
        dataPoints: [";
  for ($iYear = $startAxis; $iYear <= $untilAxis; $iYear++) {
    if ($iYear != $startAxis) {
      $strData .= ",";
      $strDataStack .= ",";
      $strData100 .= ",";
    }
    $strData .= "{ label:\"" . $iYear . "\",y:" . $arrDataYear[$iYear] . "}";
    $strDataStack .= "{ label:\"" . $iYear . "\",y:" . $arrDataYear[$iYear] . "}";
    $sumData = 0;
    foreach ($arrData as $arrDataYearAdd) {
      $sumData += $arrDataYearAdd[$iYear];
    }
    $strData100 .= "{ label:\"" . $iYear . "\",y:" . number_format((($arrDataYear[$iYear] / $sumData) * 100), 2) . "}";
  }
  $strData .= "]}";
  $strDataStack .= "]}";
  $strData100 .= "]}";
}
$strChartNew = '
<div id="chartContainer" style="height: 300px; width: 800px;"></div>
<div id="chartContainerStack" style="height: 300px; width: 800px;"></div>
<div id="chartContainerStack100" style="height: 300px; width: 800px;"></div>';
$strChartNewJs = '<script type="text/javascript">
window.onload = function () {
  var chart = new CanvasJS.Chart("chartContainer", {
      theme:"theme3",animationEnabled: true,
      title:{text: "Employee Living Area" },
    data: [  ' . $strData . ']
        });
  chart.render();

  var chart = new CanvasJS.Chart("chartContainerStack", {
    theme:"theme3",animationEnabled: true,
    title:{text: "Employee Living Area (Stacked Chart)" },
    data: [  ' . $strDataStack . ']
  });
  chart.render();

  var chart = new CanvasJS.Chart("chartContainerStack100", {
    theme:"theme3",animationEnabled: true,
    title:{text: "Employee Living Area (Stacked Chart 100%)" },
    data: [  ' . $strData100 . ']
  });
  chart.render();
      }
    </script>
    <script type="text/javascript" src="../canvasjs/canvasjs.min.js"></script>';
?>
