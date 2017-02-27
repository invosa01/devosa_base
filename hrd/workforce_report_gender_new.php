<?php
// Query data
$arrFemaleData = [];
$arrMaleData = [];
$maxAxis = 10;
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
$strChartNew = '
<div id="chartContainer" style="height: 300px; width: 800px;"></div>
<div id="chartContainerStack" style="height: 300px; width: 800px;"></div>
<div id="chartContainerStack100" style="height: 300px; width: 800px;"></div>';
$strChartNewJs = '<script type="text/javascript">
window.onload = function () {
  var chart = new CanvasJS.Chart("chartContainer", {
    theme:"theme3",animationEnabled: true,
    title:{
      text: "Employee Gender"
    },

    data: [  //array of dataSeries
      { //dataSeries - first quarter
        /*** Change type "column" to "bar", "area", "line" or "pie"***/
        type: "column",
        toolTipContent:"({name}) : {y}",
        showInLegend: true,
        name: "Male",
        dataPoints: [' . $strMaleData . '
          ]
        },
        { //dataSeries - second quarter

          type: "column",
          showInLegend: true,
          toolTipContent:"({name}) : {y}",
          name: "Famale",
          dataPoints: [' . $strFemaleData . '
            ]
          }
          ]
        });
        chart.render();
        var chartstack = new CanvasJS.Chart("chartContainerStack", {
          theme:"theme3",animationEnabled: true,
          title:{
            text: "Employee Gender (stack chart)"
          },

          data: [  //array of dataSeries
            { //dataSeries - first quarter
              /*** Change type "column" to "bar", "area", "line" or "pie"***/
              type: "stackedColumn",
              toolTipContent:"({name}) : {y}",
              showInLegend: true,
              name: "Male",
              dataPoints: [' . $strMaleData . '
              ]
            },
            { //dataSeries - second quarter

              type: "stackedColumn",
              showInLegend: true,
              toolTipContent:"({name}) : {y}",
              name: "Famale",
              dataPoints: [' . $strFemaleData . '
              ]
            }
            ]
          });
          chartstack.render();
          var chartstack100 = new CanvasJS.Chart("chartContainerStack100", {
            theme:"theme3",animationEnabled: true,
            title:{
              text: "Employee Gender (stack chart 100%)"
            },

            data: [  //array of dataSeries
              { //dataSeries - first quarter
                /*** Change type "column" to "bar", "area", "line" or "pie"***/
                type: "stackedColumn100",
                toolTipContent:"({name}) : {y}%",
                showInLegend: true,
                name: "Male",
                dataPoints: [' . $strMaleData100 . '
                ]
              },
              { //dataSeries - second quarter

                type: "stackedColumn100",
                showInLegend: true,
                toolTipContent:"({name}) : {y}%",
                name: "Famale",
                dataPoints: [' . $strFemaleData100 . '
                ]
              }
              ]
            });
            chartstack100.render();
      }
    </script>
    <script type="text/javascript" src="../canvasjs/canvasjs.min.js"></script>';
?>
