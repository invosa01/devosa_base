<?php
(isset($_REQUEST['dataInterval']) && $_REQUEST['dataInterval'] > 0) ? $strDataInterval = $_REQUEST['dataInterval'] : $strDataInterval = 2000;
$arrData = [];
$maxAxis = 10;
$untilAxis = date("Y"); // curr year
$startAxis = $untilAxis - $maxAxis;
$strDataInterval *= 5000; // interval dalam ribuan
///getmaxSalary
$strSQL = "SELECT MAX(total_net) AS max FROM hrd_salary_detail";
$maxSalary = $db->execute($strSQL);
$maxSalary = $db->fetchrow($maxSalary);
$maxSalary = $maxSalary["max"];
$minSalary = 1000000;
//echo "Max:".$maxAge;
//echo "interval:".$strDataInterval;
// generate
for ($iYear = $startAxis; $iYear <= $untilAxis; $iYear++) {
  // note: ambil salary set yg tanggalnya paling akhir pada bulan itu
  $strSQL = "select COUNT(*) from hrd_employee AS t1
    LEFT JOIN (select * from hrd_salary_detail a,
                (select * from hrd_salary_master where extract(YEAR from salary_date)=" . $iYear . " order by salary_date desc limit 1) b
              where a.id_salary_master=b.id and extract(YEAR from b.salary_date)=" . $iYear . " )
          AS t2 ON t1.id = t2.id_employee
    WHERE ((resign_date <= '" . $iYear . "1231' and resign_date >= '" . $iYear . "0101') or resign_date is null)
    and join_date<='" . $iYear . "1231' AND " . $strKriteria;
  for ($i = 0; $i < ($maxSalary + 1) / $strDataInterval; $i++) {
    $minRange = $i * $strDataInterval;
    $maxRange = $minRange + $strDataInterval - 1;
    if ($maxRange > $minSalary) {
      $strExecuteKriteria = " AND t2.total_net BETWEEN $minRange AND $maxRange ";
      $strExecuteSQL = $strSQL . $strExecuteKriteria;
      //echo "<br/>".$strExecuteSQL."<br/>";
      $numOfEmployee = $db->execute($strExecuteSQL);
      $numOfEmployee = $db->fetchrow($numOfEmployee);
      $numOfEmployee = $numOfEmployee["count"];
      //  echo $strDataMale."<br/>";
      $arrData[$i][$iYear] = $numOfEmployee;
      $arrData[$i]["label"] = number_format($minRange) . " - " . number_format($maxRange + 1);
    }
  }
}
//var_dump($arrData);
$strData = "";
$strDatastack = "";
$strData100 = "";
$i = 0;
foreach ($arrData as $key => $arrDataYear) {
  if ($i > 0) {
    $strData .= ",";
    $strDatastack .= ",";
    $strData100 .= ",";
  }
  $i++;
  $strData .= "{
    type: \"column\",
    toolTipContent:\"({name}) : {y}\",
    showInLegend: true,
    name: \"" . $arrDataYear["label"] . "\",
    dataPoints: [";
  $strDatastack .= "{
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
      $strDatastack .= ",";
      $strData100 .= ",";
    }
    $strData .= "{ label:\"" . $iYear . "\",y:" . $arrDataYear[$iYear] . "}";
    $strDatastack .= "{ label:\"" . $iYear . "\",y:" . $arrDataYear[$iYear] . "}";
    $sumData = 0;
    foreach ($arrData as $arrDataYearAdd) {
      $sumData += $arrDataYearAdd[$iYear];
    }
    $strData100 .= "{ label:\"" . $iYear . "\",y:" . number_format((($arrDataYear[$iYear] / $sumData) * 100), 2) . "}";
  }
  $strData .= "]}";
  $strDatastack .= "]}";
  $strData100 .= "]}";
}
$strChartNew = '
<div id="chartContainer" style="height: 400px; width: 800px;"></div>
<div id="chartContainerStack" style="height: 400px; width: 800px;"></div>
<div id="chartContainerStack100" style="height: 400px; width: 800px;"></div>';
$strChartNewJs = '<script type="text/javascript">
window.onload = function () {
  var chart = new CanvasJS.Chart("chartContainer", {
    theme:"theme3",animationEnabled: true,
    title:{
      text: "Employee Salary"
    },

    data: [  ' . $strData . ']
        });

        chart.render();
        var chartstack = new CanvasJS.Chart("chartContainerStack", {
          theme:"theme3",animationEnabled: true,
          title:{
            text: "Employee Salary (Stacked chart)"
          },

          data: [  ' . $strDatastack . ']
        });

        chartstack.render();
        var chart100 = new CanvasJS.Chart("chartContainerStack100", {
          theme:"theme3",animationEnabled: true,
          title:{
            text: "Employee Salary (Stacked chart 100%)"
          },

          data: [  ' . $strData100 . ']
        });

        chart100.render();
      }
    </script>
    <script type="text/javascript" src="../canvasjs/canvasjs.min.js"></script>';
$strDataInterval = $strDataInterval / 1000; // interval dalam ribuan, dikembalikan untuk tampilan
?>
