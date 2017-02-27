<?php
$host = '127.0.0.1';
$port = '5432';
$database = 'hrdev';
$user = 'patra';
$password = 'patra%6789';
$connectString = 'host=' . $host . ' port=' . $port . ' dbname=' . $database . ' user=' . $user . ' password=' . $password;
$link = pg_connect($connectString);
if (!$link) {
    die('Error: Could not connect: ' . pg_last_error());
}
?>
<html>
<head>
    <title>Form Overtime Slip</title>
</head>
<body>
<center>
    <h1> Overtime Slip</h1></center>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td colspan="3">
            <div style="margin-left:300px;">
                <table width="839" border=0" align="center" cellpadding="5" cellspacing="5">

                    <?php
                    $id = $_GET['dataID'];
                    $query = "SELECT t1.*,
    t2.employee_id,  t2.employee_name, t2.division_code, t2.department_code, t2.section_code, t2.sub_section_code, t2.grade_code, 
    t3.is_outdated,  t3.salary_month, t3.salary_year
    FROM hrd_overtime_application_employee AS t1 
    LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id     
    LEFT JOIN hrd_overtime_application AS t3 ON t1.id_application = t3.id 
    WHERE t1.status = '3' AND id_application=" . $id;
                    $result = pg_query($query);
                    $i = 0;
                    while ($row = pg_fetch_row($result)) {
                        ?>
                        <tr>
                            <td width=100 align="left" nowrap=''><strong>Create</strong></td>
                            <td width=5 nowrap=''>:</td>
                            <td width="400" nowrap=''><?php echo $row[24]; ?></td>
                            <td width=100 align="left" nowrap=''><strong>Overtime Date</strong></td>
                            <td width=5 nowrap=''>:</td>
                            <td width="400" nowrap=''><?php echo $row[17]; ?></td>
                        </tr>
                        <tr>
                            <td width=85 align="left" nowrap=''><strong>Employee</strong></td>
                            <td width=5 nowrap=''>:</td>
                            <td nowrap=''><?php echo $row[41] . "-" . $row[42]; ?></td>
                            <td width=85 align="left" nowrap=''><strong>Time</strong></td>
                            <td width=5 nowrap=''>:</td>
                            <td nowrap=''><?php echo $row[3] . "-" . $row[4]; ?></td>
                        </tr>

                        <tr>
                            <td width=85 align="left" nowrap=''><strong>Note</strong></td>
                            <td width=5 nowrap=''>:</td>
                            <td nowrap=''><?php echo $row[8]; ?></td>
                            <td width=85 align="left" nowrap=''><strong>Status</strong></td>
                            <td width=5 nowrap=''>:</td>
                            <td nowrap=''>Acknowledged</td>
                        </tr>


                        <?php
                        $i = $i + 1;
                    }
                    pg_free_result($result);
                    ?>


                </table>

                <p>&nbsp;</p>

                <p>&nbsp;</p>
                <table width="839" border="0" align="center" cellpadding="5" cellspacing="5">
                    <tr>
                        <td width="303"><strong>Checked By</strong></td>
                        <td width="133">&nbsp;</td>
                        <td width="403"><strong>Approved By</strong></td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td><?php
                            $query2 = "select x.*, (select a.employee_name from hrd_employee a, adm_user b WHERE a.employee_id=b.employee_id AND b.id_adm_user = to_number(x.checked_by, '999999')) as checked_name from (
SELECT 
    t2.employee_id,  t1.checked_by,t2.employee_name, t2.division_code, t2.department_code, t2.section_code, t2.sub_section_code, t2.grade_code, 
    t3.is_outdated,  t3.salary_month, t3.salary_year
    FROM hrd_overtime_application_employee AS t1 
    LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id     
    LEFT JOIN hrd_overtime_application AS t3 ON t1.id_application = t3.id
	WHERE t1.status = '3' AND id_application=" . $id . "
	) AS x ";
                            $result2 = pg_query($query2);
                            $row2 = pg_fetch_array($result2);
                            echo "<br><br><br><br>" . $row2[11];
                            pg_free_result($result2);
                            ?></td>
                        <td>&nbsp;</td>
                        <td><?php
                            $query1 = "select x.*, (select a.employee_name from hrd_employee a, adm_user b WHERE a.employee_id=b.employee_id AND b.id_adm_user = to_number(x.approved_by, '999999')) as approved_name from (
SELECT 
    t2.employee_id,  t1.approved_by,t2.employee_name, t2.division_code, t2.department_code, t2.section_code, t2.sub_section_code, t2.grade_code, 
    t3.is_outdated,  t3.salary_month, t3.salary_year
    FROM hrd_overtime_application_employee AS t1 
    LEFT JOIN hrd_employee AS t2 ON t1.id_employee = t2.id     
    LEFT JOIN hrd_overtime_application AS t3 ON t1.id_application = t3.id
	WHERE t1.status = '3' AND id_application=" . $id . "
	) AS x ";
                            $result1 = pg_query($query1);
                            $row1 = pg_fetch_array($result1);
                            echo "<br><br><br><br>" . $row1[11];
                            pg_free_result($result1);
                            ?></td>
                    </tr>
                </table>
                <p>&nbsp;</p>
</body>
</html>