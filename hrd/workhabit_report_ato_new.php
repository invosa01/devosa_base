<?php
$selectYearly = "";
$selectMonthly = "";
$selectPeriod = "";
$selectDept = "";
$arrMonth = [1  => "Jan",
             2  => "Feb",
             3  => "Mar",
             4  => "Apr",
             5  => "May",
             6  => "Jun",
             7  => "Jul",
             8  => "Aug",
             9  => "Sep",
             10 => "Oct",
             11 => "Nov",
             12 => "Dec"
];
(isset($_REQUEST['dataInterval']) && $_REQUEST['dataInterval'] > 0) ? $strDataInterval = $_REQUEST['dataInterval'] : $strDataInterval = 5;
(isset($_REQUEST['pyear']) && $_REQUEST['pyear'] > 0) ? $strPYear = $_REQUEST['pyear'] : $strPYear = date("Y");
(isset($_REQUEST['period']) && $_REQUEST['period'] != "") ? $strPeriod = $_REQUEST['period'] : $strPeriod = "yearly";
(isset($_REQUEST['axis']) && $_REQUEST['axis'] != "") ? $strAxis = $_REQUEST['axis'] : $strAxis = "monthly";
$arrData = [];
$maxAxis = 10;
$untilAxis = date("Y"); // curr year
$startAxis = $untilAxis - $maxAxis;
//get all position
$strSQL = "SELECT department_code FROM hrd_department ";
$resSQL = $db->execute($strSQL);
$arrCode = [];
$numCode = 0;
while ($rowDb = $db->fetchrow($resSQL)) {
  $arrCode[$numCode++] = $rowDb["department_code"];
}
$arrCode[$numCode++] = null;
if ($strPeriod == "monthly") {
  $selectMonthly = "selected";
  $selectPeriod = "selected";
  $axistTitle = $strPYear;
  $axisYTitle = "# of Employees";
  // generate
  for ($iMonth = 1; $iMonth <= 12; $iMonth++) {
    $striMonth = str_pad($iMonth, 2, "0", STR_PAD_LEFT);
    if ($iMonth == 12) {
      $striMonthNext = "01";
    } else {
      $striMonthNext = str_pad(($iMonth + 1), 2, "0", STR_PAD_LEFT);
    }
    for ($i = 0; $i < $numCode; $i++) {
      $strExecuteSQL = "select count(t1.id)  AS \"jumlah\" FROM hrd_employee AS t1
      Where
      $strKriteria AND t1.department_code = '$arrCode[$i]' AND t1.resign_date < '" . $strPYear . $striMonthNext . "01' and t1.resign_date >= '" . $strPYear . $striMonth . "01'";
      //  LEFT JOIN hrd_absence_type AS t2 ON t1.absence_type_code = t2.code
      $numOfEmployee = $db->execute($strExecuteSQL);
      $numOfEmployee = $db->fetchrow($numOfEmployee);
      $numOfEmployee = $numOfEmployee["jumlah"];
      //if ($arrCode[$i]=="")
      //  echo $strExecuteSQL."<br/>";
      if ($numOfEmployee == "") {
        $numOfEmployee = "0";
      }
      $arrData[$i][$iMonth] = $numOfEmployee;
      if ($arrCode[$i] == "") {
        $arrData[$i]["label"] = "No Dept";
      } else {
        $arrData[$i]["label"] = $arrCode[$i];
      }
    }
  }
}
if ($strPeriod == "yearly") {
  $axistTitle = "Last 10 years";
  $selectYearly = "selected";
  $selectPeriod = "selected";
  $axisYTitle = "# of Employees";
  // generate
  for ($iYear = $startAxis; $iYear <= $untilAxis; $iYear++) {
    for ($i = 0; $i < $numCode; $i++) {
      $strExecuteSQL = "select count(t1.id)  AS \"jumlah\" FROM hrd_employee AS t1 where
        $strKriteria AND t1.department_code = '$arrCode[$i]' AND t1.resign_date <= '" . $iYear . "1231' and t1.resign_date >= '" . $iYear . "0101'";
      //  LEFT JOIN hrd_absence_type AS t2 ON t1.absence_type_code = t2.code
      $numOfEmployee = $db->execute($strExecuteSQL);
      $numOfEmployee = $db->fetchrow($numOfEmployee);
      $numOfEmployee = $numOfEmployee["jumlah"];
      // echo $strExecuteSQL."<br/>";
      if ($numOfEmployee == "") {
        $numOfEmployee = 0;
      }
      $arrData[$i][$iYear] = $numOfEmployee;
      if ($arrCode[$i] == "") {
        $arrData[$i]["label"] = "No Dept";
      } else {
        $arrData[$i]["label"] = $arrCode[$i];
      }
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
  if ($strPeriod == "yearly") {
    for ($iYear = $startAxis; $iYear <= $untilAxis; $iYear++) {
      if ($iYear != $startAxis) {
        $strData .= ",";
        $strDatastack .= ",";
      }
      $strData .= "{ label:\"" . $iYear . "\",y:" . $arrDataYear[$iYear] . "}";
      $strDatastack .= "{ label:\"" . $iYear . "\",y:" . $arrDataYear[$iYear] . "}";
    }
  }
  if ($strPeriod == "monthly") {
    for ($iMonth = 1; $iMonth <= 12; $iMonth++) {
      if ($iMonth > 1) {
        $strData .= ",";
        $strDatastack .= ",";
      }
      $strData .= "{ label:\"" . $arrMonth[$iMonth] . "\",y:" . $arrDataYear[$iMonth] . "}";
      $strDatastack .= "{ label:\"" . $arrMonth[$iMonth] . "\",y:" . $arrDataYear[$iMonth] . "}";
    }
  }
  $strData .= "]}";
  $strDatastack .= "]}";
  $strData100 .= "]}";
}
$strChartNew = '<div id="chartContainer" style="height: 300px; width: 800px;"></div>';
$strChartNew .= '<div id="chartContainerStack" style="height: 300px; width: 800px;"></div>';
$strMoreForm .= '<table><tr><td>Year</td><td>:<select name="pyear">';
for ($iYear = $untilAxis; $iYear >= $startAxis; $iYear--) {
  if ($strPYear == $iYear) {
    $strMoreForm .= '<option value="' . $iYear . '" selected>' . $iYear . '</option>';
  } else {
    $strMoreForm .= '<option value="' . $iYear . '">' . $iYear . '</option>';
  }
}
$strMoreForm .= '</select></td></tr>';
$strMoreForm .= '<tr><td>Axis X Period</td><td>:<select name="period">';
$strMoreForm .= '<option value="yearly" ' . $selectYearly . ' >Yearly</option>';
$strMoreForm .= '<option value="monthly"' . $selectMonthly . '>Monthly</option>';
$strMoreForm .= '</select></td></tr>';
$strMoreForm .= '</table>';
$strChartNewJs = '<script type="text/javascript">
window.onload = function () {
  var chart = new CanvasJS.Chart("chartContainer", {
    theme:"theme3",animationEnabled: true,
    title:{
      text: "Employee Turn Over"
    },
    axisX:{
      title: "' . $axistTitle . '",
      interval: 1
    },
    axisY:{
      title: "' . $axisYTitle . '",
    },
    data: [  ' . $strData . ']
        });

  chart.render();

  var chartstack = new CanvasJS.Chart("chartContainerStack", {
          theme:"theme3",animationEnabled: true,
          title:{
            text: "Employee Turn Over (Stacked Chart)"
          },
          axisX:{
            interval: 1
          },
          axisY:{
            title: "' . $axisYTitle . '",
          },

      data: [  ' . $strDatastack . ']
        });

  chartstack.render();
}
    </script>
    <script type="text/javascript" src="../canvasjs/canvasjs.min.js"></script>';
?>
