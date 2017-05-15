<?php
# This is for ajax route controller.
function routeMap()
{
    return [
        'department'                     => 'getDepartmentData',
        'department-options'             => 'getRenderedDepartmentOptions',
        'destination'                    => 'getDestinationData',
        'destination-options'            => 'getRenderedDestinationOptions',
        'company'                        => 'getCompanyData',
        'company-options'                => 'getRenderedCompanyOptions',
        'division'                       => 'getDivisionData',
        'division-options'               => 'getRenderedDivisionOptions',
        'salaryCompanyCollector'         => 'getSalaryCompanyCollectorData',
        'salaryCompanyCollector-options' => 'getRenderedSalaryCompanyCollectorOptions',
        'quotaExtraOff'                  => 'getQuotaExtraOffData',
        'quotaExtraOff-options'          => 'getRenderedQuotaExtraOffOptions'
    ];
}

function route()
{
    # Define the default response.
    $response = '';
    # Fetch route map.
    $routeMap = routeMap();
    $route = null;
    if (array_key_exists('m', $_GET) === true) {
        $route = $_GET['m'];
    }
    if ($route !== null and array_key_exists($route, $routeMap) === true and is_callable($routeMap[$route]) === true) {
        # Lazy load.
        require_once __DIR__ . '/src/System/Standards.php';
        require_once __DIR__ . '/src/System/Sessions.php';
        require_once __DIR__ . '/src/System/Database.php';
        require_once __DIR__ . '/global/configuration.php';
        # Load new database instance handler.
        setActiveDbConnection('dbConnection', getPgConnection(DB_NAME, DB_USER, DB_PWD, DB_SERVER, DB_PORT));
        # Define the callback.
        $callback = $routeMap[$route];
        $response = [
            'result' => true,
            'data'   => $callback()
        ];
    }
    die(json_encode($response));
}

if (function_exists('getQuery') === false) {
    /**
     * Get processed query repository.
     *
     * @param string $strSql Sql query string parameter.
     * @param array  $wheres Where criteria data collection parameter.
     *
     * @return string
     */
    function getQuery($strSql, array $wheres = [])
    {
        if (count($wheres) > 0) {
            $strSql .= ' WHERE ' . implodeArray($wheres, ' AND ');
        }
        return $strSql;
    }
}
function getCompanyData()
{
    $managementCode = null;
    $wheres = [];
    if (array_key_exists('management_code', $_POST) === true) {
        $managementCode = $_POST['management_code'];
    }
    $strSQL = 'SELECT
                    mgt."id",
                    mgt.management_code,
                    mgt.management_name,
                    cpy."id",
                    cpy.company_code,
                    cpy.company_name
                FROM
                    "public".hrd_management AS mgt
                INNER JOIN "public".hrd_company AS cpy ON mgt.management_code = cpy.management_code';
    if ($managementCode !== null) {
        $wheres[] = 'mgt.management_code = ' . pgEscape($managementCode);
    }
    return pgFetchRows(getQuery($strSQL, $wheres));
}

function getRenderedCompanyOptions()
{
    $result = '<option value="">-</option>';
    $record = getCompanyData();
    foreach ($record as $row) {
        $result .= '<option value="' . $row['id'] . '">'
            . $row['company_code']
            . ' - '
            . $row['company_name']
            . '</option>';
    }
    return $result;
}

function getDivisionData()
{
    $companyCode = null;
    $wheres = [];
    if (array_key_exists('id', $_POST) === true) {
        $companyCode = $_POST['id'];
    }
    $strSQL = 'SELECT
                    cpy."id",
                    cpy.company_code,
                    cpy.company_name,
                    div."id",
                    div.division_code,
                    div.division_name
                FROM
                    "public".hrd_company AS cpy
                INNER JOIN "public".hrd_division AS div ON cpy.company_code = div.management_code';
    if ($companyCode !== null) {
        $wheres[] = 'cpy.id = ' . pgEscape($companyCode);
    }
    return pgFetchRows(getQuery($strSQL, $wheres));
}

function getRenderedDivisionOptions()
{
    $result = '<option value="">-</option>';
    $record = getDivisionData();
    foreach ($record as $row) {
        $result .= '<option value="' . $row['division_code'] . '">'
            . $row['division_code']
            . ' - '
            . $row['division_name']
            . '</option>';
    }
    return $result;
}

function getDepartmentData()
{
    $divisionCode = null;
    $wheres = [];
    if (array_key_exists('division_code', $_POST) === true) {
        $divisionCode = $_POST['division_code'];
    }
    $strSQL = 'SELECT
                    div.division_code,
                    div.division_name,
                    dpr."id",
                    dpr.department_code,
                    dpr.department_name,
                    dpr.division_code
                FROM
                    "public".hrd_department AS dpr
                INNER JOIN "public".hrd_division AS div ON dpr.division_code = div.division_code';
    if ($divisionCode !== null) {
        $wheres[] = 'div.division_code = ' . pgEscape($divisionCode);
    }
    return pgFetchRows(getQuery($strSQL, $wheres));
}

function getRenderedDepartmentOptions()
{
    $result = '<option value="">-</option>';
    $record = getDepartmentData();
    foreach ($record as $row) {
        $result .= '<option value="' . $row['department_code'] . '">'
            . $row['department_code']
            . ' - '
            . $row['department_name']
            . '</option>';
    }
    return $result;
}

function getDestinationData()
{
    $tripTypeCode = null;
    $wheres = [];
    if (array_key_exists('id', $_POST) === true) {
        $tripTypeCode = $_POST['id'];
    }
    $strSQL = 'SELECT
                    trt."id",
                    trt.trip_type_code,
                    trt.trip_type_name,
                    dst.destination_code,
                    dst.destination_name,
                    dst.trip_type_code
                FROM
                    "public".hrd_destination AS dst
                INNER JOIN "public".hrd_trip_type AS trt ON dst.trip_type_code = trt.trip_type_code';
    if ($tripTypeCode !== null) {
        $wheres[] = 'trt."id" = ' . pgEscape($tripTypeCode);
    }
    return pgFetchRows(getQuery($strSQL, $wheres));
}

function getRenderedDestinationOptions()
{
    $result = '<option value="">-</option>';
    $record = getDestinationData();
    foreach ($record as $row) {
        $result .= '<option value="' . $row['destination_name'] . '">'
            . $row['destination_code']
            . ' - '
            . $row['destination_name']
            . '</option>';
    }
    return $result;
}

function getSalaryCompanyCollectorData()
{
    $strCompanyCollector = null;
    $wheres = [];
    if (array_key_exists('id_company', $_POST) === true) {
        $strCompanyCollector = $_POST['id_company'];
    }
    $strSQL = 'SELECT t1.salary_date, t1.id, t2.company_name FROM hrd_salary_master AS t1
               LEFT JOIN hrd_company AS t2 ON t1.id_company = t2.id ';
    $wheres[] = 't1.status >= 2';
    if ($strCompanyCollector !== null) {
        $wheres[] = 't1.id_company = ' . pgEscape($strCompanyCollector);
    }
    return pgFetchRows(getQuery($strSQL, $wheres));
}

function getRenderedSalaryCompanyCollectorOptions()
{
    $result = '<option value="">-</option>';
    $record = getSalaryCompanyCollectorData();
    foreach ($record as $row) {
        $result .= '<option value="' . $row['id'] . '">'
            . 'Payroll'
            . ' - '
            . $row['company_name']
            . ' - '
            . date('F Y', strtotime($row['salary_date']))
            . ' (' . $row['salary_date'] . ') '
            . '</option>';
    }
    return $result;
}

function getQuotaExtraOffData()
{
    $employeeId = null;
    $wheres = [];
    if (array_key_exists('id', $_POST) === true) {
        $employeeId = $_POST['id'];
    }
    $strSQL = 'SELECT
                    emp."id",
                    qeo."id" AS qeo_id,
                    qeo.employee_id,
                    qeo.date_extra_off,
                    qeo.date_expaired,
                    qeo.note
                FROM
                    "public".hrd_employee AS emp
                INNER JOIN "public".hrd_quota_extra_off AS qeo ON qeo.employee_id = emp."id"
';
    if ($employeeId !== null) {
        $wheres[] = 'qeo.employee_id = ' . pgEscape($employeeId);
    }
    return pgFetchRows(getQuery($strSQL, $wheres));
}

function getRenderedQuotaExtraOffOptions()
{
    $result = '<option value="">-</option>';
    $record = getQuotaExtraOffData();
    foreach ($record as $row) {
        $result .= '<option value="' . $row['qeo_id'] . '">'
            . $row['date_extra_off']
            . ' - '
            . $row['note']
            . ' - '
            . $row['date_expaired']
            . '</option>';
    }
    return $result;
}

# Run the ajax router.
route();