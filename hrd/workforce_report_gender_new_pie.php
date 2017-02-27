<?php
// Query data
$arrFemaleData = [];
$arrMaleData = [];
$maxAxis = 5;
$untilAxis = date("Y"); // curr year
$startAxis = $untilAxis - $maxAxis;
// generate data male
for ($iYear = $startAxis; $iYear <= $untilAxis; $iYear++) {
  $strSQLMale = "select COUNT(*) from hrd_employee AS t1
    WHERE ((resign_date <= '" . $iYear . "1231' and resign_date >= '" . $iYear . "0101') or resign_date is null)
    and join_date<='" . $iYear . "1231' AND gender=1 AND " . $strKriteria;
  //echo $strSQLMale;
  //hitung male
  $strDataMale = $db->execute($strSQLMale);
  $strDataMale = $db->fetchrow($strDataMale);
  $strDataMale = $strDataMale["count"];
  //  echo $strDataMale."<br/>";
  $arrMaleData[$iYear] = $strDataMale;
}
//var_dump($arrMaleData);
// generate date Female
for ($iYear = $startAxis; $iYear <= $untilAxis; $iYear++) {
  $strSQLFemale = "select COUNT(*) from hrd_employee AS t1
    WHERE ((resign_date <= '" . $iYear . "1231' and resign_date >= '" . $iYear . "0101') or resign_date is null)
    and join_date<='" . $iYear . "1231' AND gender=0 AND " . $strKriteria;
  //hitung female
  $strDataFemale = $db->execute($strSQLFemale);
  $strDataFemale = $db->fetchrow($strDataFemale);
  $strDataFemale = $strDataFemale["count"];
  $arrFemaleData[$iYear] = $strDataFemale;
}
// end query
$strMaleData = "";
$strFemaleData = "";
$strMaleData100 = "";
$strFemaleData100 = "";
for ($iYear = $startAxis; $iYear <= $untilAxis; $iYear++) {
  if ($iYear != $startAxis) {
    $strMaleData .= ",";
    $strFemaleData .= ",";
    $strMaleData100 .= ",";
    $strFemaleData100 .= ",";
  }
  $allData = $arrFemaleData[$iYear] + $arrMaleData[$iYear];
  $strMaleData .= "{ label:\"" . $iYear . "\",y:" . $arrMaleData[$iYear] . "}";
  $strFemaleData .= "{ label:\"" . $iYear . "\",y:" . $arrFemaleData[$iYear] . "}";
  $strMaleData100 .= "{ label:\"" . $iYear . "\",y:" . number_format(
          (($arrMaleData[$iYear] / $allData) * 100),
          2
      ) . "}";
  $strFemaleData100 .= "{ label:\"" . $iYear . "\",y:" . number_format(
          (($arrFemaleData[$iYear] / $allData) * 100),
          2
      ) . "}";
}
$strChartNew = '';
for ($iYear = $startAxis; $iYear <= $untilAxis; $iYear++) {
  $strChartNew = '<div class="col-md-6" align="center"><div id="chartContainer' . $iYear . '" style="height: 300px; width: 300px;"></div></div>' . $strChartNew;
}
$strChartNewJs = '';
for ($iYear = $startAxis; $iYear <= $untilAxis; $iYear++) {
  $strChartNewJs .= 'var chart' . $iYear . ' = new CanvasJS.Chart("chartContainer' . $iYear . '", {
    theme:"theme3",animationEnabled: true,
    title:{ text: "Employee Gender ' . $iYear . '" },
    data: [
        {        
          type: "pie",
          indexLabelFontFamily: "Garamond",       
          indexLabelFontSize: 20,
          indexLabelFontWeight: "bold",
          startAngle:0,
          indexLabelFontColor: "MistyRose",       
          indexLabelLineColor: "darkgrey", 
          indexLabelPlacement: "inside", 
          toolTipContent: "{name}: {y} employees",
          showInLegend: true,
          indexLabel: "#percent%", 
          dataPoints: [
            {  y: ' . $arrMaleData[$iYear] . ', name: "Male", legendMarkerType: "triangle"},
            {  y: ' . $arrFemaleData[$iYear] . ', name: "Female", legendMarkerType: "square"}
          ]
        }
        ]
        });
        chart' . $iYear . '.render();';
}
$strChartNewJs = '<script type="text/javascript">
window.onload = function () {
  ' . $strChartNewJs . '
      }
    </script>
    <script type="text/javascript" src="../canvasjs/canvasjs.min.js"></script>';
?>
