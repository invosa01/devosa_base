<?php
(isset($_REQUEST['dataInterval']) && $_REQUEST['dataInterval'] > 0) ? $strDataInterval = $_REQUEST['dataInterval'] : $strDataInterval = 2000;
$arrData = [];

//adam 12-06-2017 query
$getField = $_GET['orderby'];
if($getField=="")$getField="Division";
if(!empty($getField)){
	$strOption = "SELECT * FROM hrd_".$getField."";
}elseif($getField='management'){
	$strOption = "SELECT * FROM hrd_company";
}
else{
	$strOption = "SELECT * FROM hrd_division";	
}
$excOption = $db->execute($strOption);
$x = -1;
while ($rowDb = $db->fetchrow($excOption, "ASSOC")) {
	$x++;
	$data[] = $rowDb;
	
	if($getField=='department'){
			$strSQL = "SELECT sum(total_net) FROM hrd_salary_detail where department_code = '".$data[$x]['department_code']."' and total_net != 0";
		if(!empty($_GET['datefrom'])){
			$strSQL .= " and created >= '".$_GET['datefrom']."'";
		}
		if(!empty($_GET['dateend'])){
			$strSQL .= " and created <= '".$_GET['dateend']."'";
		}
	}elseif($getField=='division'){
			$strSQL = "SELECT sum(total_net) FROM hrd_salary_detail where division_code = '".$data[$x]['division_code']."' and total_net != 0";
		if(!empty($_GET['datefrom'])){
			$strSQL .= " and created >= '".$_GET['datefrom']."'";
		}
		if(!empty($_GET['dateend'])){
			$strSQL .= " and created <= '".$_GET['dateend']."'";
		}
	}elseif($getField=='management'){
			$strSQL = "SELECT sum(total_net)
					FROM hrd_salary_detail INNER JOIN hrd_employee
					on hrd_employee.employee_id = hrd_salary_detail.employee_id
					where management_code = '".$data[$x]['management_code']."' and total_net != 0";
		if(!empty($_GET['datefrom'])){
			$strSQL .= " and created >= '".$_GET['datefrom']."'";
		}
		if(!empty($_GET['dateend'])){
			$strSQL .= " and created <= '".$_GET['dateend']."'";
		}
	}else{
		$strSQL = "SELECT sum(total_net) FROM hrd_salary_detail where division_code = '".$data[$x]['division_code']."' and total_net != 0";
	}
	
	$numSalary = $db->execute($strSQL);
	$Salarysum = $db->fetchrow($numSalary);
	
	$data[$x]['salarytotal'] = ceil($Salarysum['sum']);
}

// echo "<pre>";
// print_r($data);die();

$strData = "";
$strDatastack = "";
$strData100 = "";
$i = 0;

foreach ($data as $key => $arrPie) {
  $i++;
  if($getField=='division'){
  	$strData .= "{ y: ".$arrPie['salarytotal'].", legendText:'".$arrPie['division_code']."', indexLabel: '".$arrPie['division_name']."' },";
  }elseif($getField=='department'){
  	$strData .= "{ y: ".$arrPie['salarytotal'].", legendText:'".$arrPie['division_code']."', indexLabel: '".$arrPie['division_code']."' },";
  }elseif($getField=='management'){
  	$strData .= "{ y: ".$arrPie['salarytotal'].", legendText:'".$arrPie['management_code']."', indexLabel: '".$arrPie['management_code']."' },";
  }else{
  	$strData .= "{ y: ".$arrPie['salarytotal'].", legendText:'".$arrPie['division_code']."', indexLabel: '".$arrPie['division_code']."' },";
  } 
}

// echo "<pre>";
// print_r($data);die();

//adam 13-06-2017

$strChartNew = '
<div id="chartContainer" style="height: 450px; width: 100%;"></div>
<div style="float: left"><i>*per million</i></div>
';
$strChartNewJs = '<script type="text/javascript">
	window.onload = function () {
		var chart = new CanvasJS.Chart("chartContainer",
		{
			title:{
				text: "Salary Chart By '.ucfirst($getField).'"
			},
	                animationEnabled: true,
			legend:{
				verticalAlign: "bottom",
				horizontalAlign: "center"
			},
			data: [
			{        
				indexLabelFontSize: 20,
				indexLabelFontFamily: "Monospace",       
				indexLabelFontColor: "darkgrey", 
				indexLabelLineColor: "darkgrey",        
				indexLabelPlacement: "outside",
				type: "pie",       
				showInLegend: true,
				toolTipContent: "{y} - <strong>#percent%</strong>",
				dataPoints: [
					' . $strData . '
				]
			}
			]
		});
		chart.render();
	}
	</script>
    <script type="text/javascript" src="../canvasjs/canvasjs.min.js"></script>';
$strDataInterval = $strDataInterval / 1000; // interval dalam ribuan, dikembalikan untuk tampilan
?>
