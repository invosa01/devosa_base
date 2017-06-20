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
(isset($_REQUEST['axis']) && $_REQUEST['axis'] != "") ? $strAxis = $_REQUEST['axis'] : $strAxis = "dept";
$arrData = [];
$maxAxis = 10;
$untilAxis = date("Y"); // curr year
$startAxis = $untilAxis - $maxAxis;
//echo "Max:".$maxLeave;
//echo "interval:".$strDataInterval;
if ($strAxis == "period" AND $strPeriod == "monthly") {
  $selectMonthly = "selected";
  $selectPeriod = "selected";
  $axistTitle = $strPYear;
  ///getmaxLeave
  $strSQL = "select MAX(sum_leave) AS max FROM
  (select a.id_employee,extract(MONTH from b.salary_date),sum(a.leave_day) sum_leave
  from hrd_salary_detail a,hrd_salary_master b
  where a.id_salary_master=b.id AND extract(YEAR from b.salary_date)=$strPYear group by extract(MONTH from b.salary_date),a.id_employee) t0";
  $maxLeave = $db->execute($strSQL);
  $maxLeave = $db->fetchrow($maxLeave);
  $maxLeave = $maxLeave["max"];
  $minLeave = 0;
  // generate
  for ($iMonth = 1; $iMonth <= 12; $iMonth++) {
    //$striMonth=str_pad($iMonth, 2, "0", STR_PAD_LEFT);
    // note: ambil salary set yg tanggalnya paling akhir pada bulan itu
    $strSQL = "select COUNT(*) from hrd_employee AS t1
    LEFT JOIN (select * from hrd_salary_detail a,
    (select * from hrd_salary_master where extract(MONTH from salary_date)=" . $iMonth . " and extract(YEAR from salary_date)=" . $strPYear . "
      order by salary_date desc limit 1) b
    where a.id_salary_master=b.id and extract(YEAR from b.salary_date)=" . $strPYear . " and extract(MONTH from b.salary_date)=" . $iMonth . " )
    AS t2 ON t1.id = t2.id_employee
    WHERE ((resign_date <= '" . $strPYear . "1231' and resign_date >= '" . $strPYear . "0101') or resign_date is null)
    and join_date<='".$strPYear."1231' AND ".$strKriteria;
    for ($i = 0; $i < ($maxLeave + 1) / $strDataInterval; $i++) {
      $minRange = $i * $strDataInterval;
      $maxRange = $minRange + $strDataInterval - 1;
      if ($maxRange > $minLeave) {
        $strExecuteKriteria = " AND t2.leave_day BETWEEN $minRange AND $maxRange ";
        $strExecuteSQL = $strSQL . $strExecuteKriteria;
        //echo "<br/>".$strExecuteSQL."<br/>";
        $numOfEmployee = $db->execute($strExecuteSQL);
        $numOfEmployee = $db->fetchrow($numOfEmployee);
        $numOfEmployee = $numOfEmployee["count"];
        //  echo $strDataMale."<br/>";
        $arrData[$i][$iMonth] = $numOfEmployee;
        $arrData[$i]["label"] = number_format($minRange) . " - " . number_format($maxRange + 1);
      }
    }
  }
}
if ($strAxis == "period" AND $strPeriod == "yearly") {
  $axistTitle = "Last 10 years";
  $selectYearly = "selected";
  $selectPeriod = "selected";
  ///getmaxSalary
  $strSQL = "select MAX(sum_leave) AS max FROM
  (select a.id_employee,extract(YEAR from b.salary_date),sum(a.leave_day) sum_leave
  from hrd_salary_detail a,hrd_salary_master b
  where a.id_salary_master=b.id  group by extract(YEAR from b.salary_date),a.id_employee) t0";
  $maxLeave = $db->execute($strSQL);
  $maxLeave = $db->fetchrow($maxLeave);
  $maxLeave = $maxLeave["max"];
  $minLeave = 0;
  // generate
  for ($iYear = $startAxis; $iYear <= $untilAxis; $iYear++) {
    // note: ambil salary set yg tanggalnya paling akhir pada bulan itu
    $strSQL = "select COUNT(*) from hrd_employee AS t1
      LEFT JOIN (select * from hrd_salary_detail a,
                  (select * from hrd_salary_master where extract(YEAR from salary_date)=" . $iYear . " order by salary_date desc limit 1) b
                where a.id_salary_master=b.id and extract(YEAR from b.salary_date)=" . $iYear . " )
            AS t2 ON t1.id = t2.id_employee
      WHERE ((resign_date <= '".$iYear."1231' and resign_date >= '".$iYear."0101') or resign_date is null)
      and join_date<='" . $iYear . "1231' AND " . $strKriteria;
    for ($i = 0; $i < ($maxLeave + 1) / $strDataInterval; $i++) {
      $minRange = $i * $strDataInterval;
      $maxRange = $minRange + $strDataInterval - 1;
      if ($maxRange > $minLeave) {
        $strExecuteKriteria = " AND t2.leave_day BETWEEN $minRange AND $maxRange ";
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
}
if ($strAxis == "dept") {
  $selectMonthly = "selected";
  $selectDept = "selected";
  $axistTitle = $strPYear;
  $strSQL = "select department_code FROM hrd_department";
  $resSQL = $db->execute($strSQL);
  $arrCode = [];
  $numCode = 0;
  while ($rowDb = $db->fetchrow($resSQL)) {
    $arrCode[$numCode++] = $rowDb["department_code"];
  }
  $arrCode[$numCode++] = null;
  // generate
  for ($iMonth = 1; $iMonth <= 12; $iMonth++) {
    //$striMonth=str_pad($iMonth, 2, "0", STR_PAD_LEFT);
    // note: ambil salary set yg tanggalnya paling akhir pada bulan itu
    $strSQL = "select sum(t2.leave_day) sumleave from hrd_employee AS t1
    LEFT JOIN (select * from hrd_salary_detail a,
    (select * from hrd_salary_master where extract(MONTH from salary_date)=" . $iMonth . " and extract(YEAR from salary_date)=" . $strPYear . "
    order by salary_date desc limit 1) b
    where a.id_salary_master=b.id and extract(YEAR from b.salary_date)=" . $strPYear . " and extract(MONTH from b.salary_date)=" . $iMonth . " )
    AS t2 ON t1.id = t2.id_employee
    WHERE ((t1.resign_date <= '" . $strPYear . "1231' and t1.resign_date >= '" . $strPYear . "0101') or t1.resign_date is null)
    and t1.join_date<='".$strPYear."1231' AND ".$strKriteria;
    //echo $strSQL."<br/><br/>";
    for ($i = 0; $i < $numCode; $i++) {
      $strExecuteKriteria = " AND t1.department_code = '$arrCode[$i]' ";
      $strExecuteSQL = $strSQL . $strExecuteKriteria;
      //echo "<br/>".$strExecuteSQL."<br/>";
      $numOfEmployee = $db->execute($strExecuteSQL);
      $numOfEmployee = $db->fetchrow($numOfEmployee);
      $numOfEmployee = $numOfEmployee["sumleave"];
      if ($numOfEmployee == "") {
        $numOfEmployee = 0;
      }
      //  echo $strDataMale."<br/>";
      $arrData[$i][$iMonth] = $numOfEmployee;
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
  if ($strAxis == "period" AND $strPeriod == "yearly") {
    for ($iYear = $startAxis; $iYear <= $untilAxis; $iYear++) {
      if ($iYear != $startAxis) {
        $strData .= ",";
        $strDatastack .= ",";
      }
      $strData .= "{ label:\"" . $iYear . "\",y:" . $arrDataYear[$iYear] . "}";
    }
  }
  if ($strAxis == "period" AND $strPeriod == "monthly") {
    for ($iMonth = 1; $iMonth <= 12; $iMonth++) {
      if ($iMonth > 1) {
        $strData .= ",";
        $strDatastack .= ",";
      }
      $strData .= "{ label:\"" . $arrMonth[$iMonth] . "\",y:" . $arrDataYear[$iMonth] . "}";
    }
  }
  if ($strAxis == "dept") {
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
$strMoreForm .= '<tr><td>Series</td><td>:<select name="axis">';
$strMoreForm .= '<option value="period" ' . $selectPeriod . '>Leave Days</option>';
$strMoreForm .= '<option value="dept" ' . $selectDept . '>Department</option>';
$strMoreForm .= '</select></td></tr></table>';
$strChartNewJs = '<script type="text/javascript">
window.onload = function () {
  var chart = new CanvasJS.Chart("chartContainer", {
    theme:"theme3",animationEnabled: true,
    title:{
      text: "Employee Leave"
    },
    axisX:{
      title: "' . $axistTitle . '",
      interval: 1
    },
    axisY:{
      title: "days",
    },
    data: [  ' . $strData . ']
        });

  chart.render();

  var chartstack = new CanvasJS.Chart("chartContainerStack", {
          theme:"theme3",animationEnabled: true,
          title:{
            text: "Employee Leave (Stacked Chart)"
          },
          axisX:{
            interval: 1
          },
          axisY:{
            title: "days",
          },

      data: [  ' . $strDatastack . ']
        });

  chartstack.render();
}
    </script>
    <script type="text/javascript" src="../canvasjs/canvasjs.min.js"></script>';
?>
