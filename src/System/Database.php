<?php
/**
 * Code written is strictly used within this program.
 * Any other use of this code is in violation of copy rights.
 *
 * @package   -
 * @author    Bambang Adrian Sitompul <bambang.adrian@gmail.com>
 * @copyright 2016 Developer
 * @license   - No License
 * @version   GIT: $Id$
 * @link      -
 */
defined('STANDARD_FUNCTION_LOADED') === true or die('STANDARD FUNCTION NOT LOADED YET');
/**
 * A Flag sign if standard postgresql database driver library has been loaded or not.
 *
 * @constant boolean STANDARD_PGSQL_DRIVER_LIBRARY
 */
define('STANDARD_PGSQL_DRIVER_LIBRARY', true);
if (function_exists('getPgConnection') === false) {
    /**
     * Get postgreSQL database connection.
     *
     * @param string  $dbName Database name parameter.
     * @param string  $dbUser Database user parameter.
     * @param string  $dbPwd  Database user-password parameter.
     * @param string  $dbHost Database server host parameter.
     * @param integer $dbPort Database server port parameter.
     *
     * @throws \BadFunctionCallException If pgsql extension not enabled yet on php setting.
     * @return resource|false
     */
    function getPgConnection($dbName, $dbUser, $dbPwd, $dbHost = '', $dbPort = 5432)
    {
        if (is_callable('pg_connect') === true) {
            $connectionString = '';
            if (getValue($dbHost) !== null) {
                $connectionString = 'host=' . $dbHost . ' ';
            }
            $connectionString .= 'port=' . $dbPort . ' dbname=' . $dbName . ' user=' . $dbUser . ' password=' . $dbPwd;
            $connection = pg_connect($connectionString) or die('Database connection failed');
        } else {
            throw new \BadFunctionCallException('Cannot call function pg_connect(), please enable the pgsql extension');
        }
        return $connection;
    }
}
if (function_exists('pgIsValidConnectionResource') === false) {
    /**
     * Check if given connection resource is a valid pgsql connection resource.
     *
     * @param resource $connectionResource Database connection resource parameter.
     *
     * @return boolean
     */
    function pgIsValidConnectionResource($connectionResource)
    {
        return (is_resource($connectionResource) === true and get_resource_type($connectionResource) === 'pgsql link');
    }
}
if (function_exists('setActiveDbConnection') === false) {
    /**
     * Set active database connection handler.
     *
     * @param string   $connectionName     Database connection session name parameter.
     * @param resource $connectionResource PostgreSQL database connection resource parameter.
     *
     * @throws \InvalidArgumentException If invalid database connection resource given.
     * @return void
     */
    function setActiveDbConnection($connectionName, $connectionResource)
    {
        if (pgIsValidConnectionResource($connectionResource) === false) {
            throw new \InvalidArgumentException(
                'Invalid database connection resource ' . (string)$connectionResource
            );
        }
        setSessionValue('activeDbConnection', $connectionName);
        setSessionValue($connectionName, $connectionResource);
    }
}
if (function_exists('setActiveDbConnectionName') === false) {
    /**
     * Activate existing database connection handler using given connection name.
     *
     * @param string $connectionName Database connection session name parameter.
     *
     * @throws \InvalidArgumentException If invalid database connection name given.
     * @return void
     */
    function setActiveDbConnectionName($connectionName)
    {
        if (pgIsValidConnectionResource(getSessionValue($connectionName, null)) === false) {
            throw new \InvalidArgumentException('Invalid database connection resource name: ' . $connectionName);
        }
        setSessionValue('activeDbConnection', $connectionName);
    }
}
if (function_exists('getCurrentDbConnection') === false) {
    /**
     * Get current database connection handler.
     *
     * @throws \LogicException If failed to get correct database connection resource.
     * @return resource
     */
    function getCurrentDbConnection()
    {
        $connectionResource = getSessionValue(getSessionValue('activeDbConnection'));
        if (pgIsValidConnectionResource($connectionResource) === false) {
            throw new \LogicException('Cannot get correct database connection resource');
        }
        return $connectionResource;
    }
}
if (function_exists('pgInfo') === false) {
    /**
     * Get postgreSQL database connection and execution information.
     *
     * @param string $infoKey Information key index parameter.
     *
     * @return string|array
     */
    function pgInfo($infoKey = '')
    {
        $dbConnection = getCurrentDbConnection();
        $dbState = [
            'connectionStatus' => pg_connection_status($dbConnection),
            'lastError'        => pg_last_error($dbConnection),
            'resultError'      => pg_result_error($dbConnection),
            'lastNotice'       => pg_last_notice($dbConnection)
        ];
        return getMappedValue(
            array_key_exists($infoKey, $dbState) === true,
            $dbState[$infoKey],
            $dbState
        );
    }
}
if (function_exists('pgExecuteQuery') === false) {
    /**
     * Execute query using postgreSQL database handler.
     *
     * @param string $query  Query string parameter.
     * @param array  $params Parameter collection data that will be substituted into query.
     *
     * @return resource|false
     */
    function pgExecuteQuery($query, array $params = [])
    {
        $queryStatementName = uniqid('query', true);
        $result = false;
        if (pg_prepare(getCurrentDbConnection(), $queryStatementName, $query) !== false) {
            $result = pg_execute(getCurrentDbConnection(), $queryStatementName, $params);
        }
        return $result;
    }
}
if (function_exists('pgFetchRow') === false) {
    /**
     * Fetching single row using postgreSQL database handler.
     *
     * @param string $query Query string parameter.
     *
     * @return array
     */
    function pgFetchRow($query)
    {
        $data = [];
        $result = pgExecuteQuery($query);
        if ($result !== false) {
            $data = pg_fetch_assoc($result);
            pg_free_result($result);
        }
        return $data;
    }
}
if (function_exists('pgFetchRows') === false) {
    /**
     * Fetching row data collection using postgreSQL database handler.
     *
     * @param string $query Query string parameter.
     *
     * @return array
     */
    function pgFetchRows($query)
    {
        $data = [];
        $result = pgExecuteQuery($query);
        if ($result !== false) {
            while (true) {
                $row = pg_fetch_assoc($result);
                if ($row === false) {
                    break;
                }
                $data[] = $row;
            }
            pg_free_result($result);
        }
        return $data;
    }
}
if (function_exists('pgEscape') === false) {
    /**
     * Escaping string value before passed into query.
     *
     * @param string  $value    Value that want to be escaped.
     * @param boolean $autoTrim Trim whitespace from passed value option parameter.
     *
     * @return string
     */
    function pgEscape($value, $autoTrim = true)
    {
        if ($autoTrim === true) {
            $value = trim($value);
        }
        return '\'' . $value . '\'';
    }
}
if (function_exists('pgCheckPassedDataArray') === false) {
    /**
     * Checking passed associative data array constraint with table property.
     *
     * @param string $tableName            Table name parameter.
     * @param array  $passedAssocDataArray Associative data array that will be checked.
     *
     * @return array|boolean
     */
    function pgCheckPassedDataArray($tableName, array $passedAssocDataArray)
    {
        return pg_convert(getCurrentDbConnection(), $tableName, $passedAssocDataArray);
    }
}
if (function_exists('pgGetObjectName') === false) {
    /**
     * Get postgreSQL element object name.
     *
     * @param string $objectName Object name parameter.
     *
     * @return string
     */
    function pgGetObjectName($objectName)
    {
        if ((boolean)preg_match('/^[a-z_][a-z0-9_]+$/', $objectName) === false) {
            $objectName = '"' . (string)$objectName . '"';
        }
        return $objectName;
    }
}
if (function_exists('pgGetFullTableName') === false) {
    /**
     * Get full table name with specific schema name.
     *
     * @param string $tableName  Table name parameter.
     * @param string $schemaName Schema name parameter.
     *
     * @return string
     */
    function pgGetFullTableName($tableName, $schemaName = 'public')
    {
        return pgGetObjectName($schemaName) . '.' . pgGetObjectName($tableName);
    }
}
if (function_exists('pgGetFullFieldName') === false) {
    /**
     * Get full field name with given table name and schema name.
     *
     * @param string $fieldName  Field name parameter.
     * @param string $tableName  Table name parameter.
     * @param string $schemaName Schema name parameter.
     *
     * @return string
     */
    function pgGetFullFieldName($fieldName, $tableName, $schemaName = 'public')
    {
        return pgGetFullTableName($tableName, $schemaName) . '.' . pgGetObjectName($fieldName);
    }
}
if (function_exists('pgGetPrimaryFieldInfo') === false) {
    /**
     * Get primary field name information data.
     *
     * @param string $tableName  Table name parameter.
     * @param string $schemaName Schema name parameter.
     *
     * @return array
     */
    function pgGetPrimaryFieldInfo($tableName, $schemaName = 'public')
    {
        $relationIdName = pgGetFullTableName($tableName, $schemaName);
        $sql = 'SELECT quote_ident(a.attname) "fieldName", format_type(a.atttypid, a.atttypmod) "dataType"
                FROM   pg_index i
                JOIN   pg_attribute a ON a.attrelid = i.indrelid
                                     AND a.attnum = ANY(i.indkey)
                WHERE  i.indrelid = ' . pgEscape($relationIdName) . '::regclass
                AND    i.indisprimary;';
        return pgFetchRow($sql);
    }
}
if (function_exists('pgGetSerialSequenceName') === false) {
    /**
     * Get serial sequence name of given table.
     *
     * @param string $tableName  Table name parameter.
     * @param string $fieldName  Field name parameter.
     * @param string $schemaName Schema name parameter.
     *
     * @return string
     */
    function pgGetSerialSequenceName($tableName, $fieldName = '', $schemaName = 'public')
    {
        $serialName = '';
        try {
            $fieldName = getValue($fieldName);
            if ($fieldName === null) {
                $primaryInfo = getValue(pgGetPrimaryFieldInfo($tableName, $schemaName), null);
                $fieldName = getMappedValue($primaryInfo !== null, $primaryInfo['fieldName']);
            }
            if ($fieldName !== null) {
                $query = 'SELECT pg_get_serial_sequence(' .
                    pgEscape(pgGetObjectName($schemaName) . '.' . pgGetObjectName($tableName)) . ', ' .
                    pgEscape($fieldName) . ') serial';
                $result = pgFetchRow($query);
                $serialName = $result['serial'];
            }
        } catch (\Exception $ex) {
            unset($ex);
        }
        return $serialName;
    }
}
if (function_exists('pgGetLastInsertId') === false) {
    /**
     * Get last insert id of given table name, field name, and schema name.
     *
     * @param string $tableName  Table name parameter.
     * @param string $fieldName  Field name parameter.
     * @param string $schemaName Schema name parameter.
     *
     * @return integer
     */
    function pgGetLastInsertId($tableName, $fieldName = '', $schemaName = 'public')
    {
        # Define the started insert id.
        $lastInsertId = 1;
        $sequenceName = pgGetSerialSequenceName($tableName, $fieldName, $schemaName);
        try {
            $query = 'SELECT currval(' . pgEscape($sequenceName) . '::regclass) "currentValue"';
            $result = pgFetchRow($query);
            if (array_key_exists('currentValue', $result) === true) {
                $lastInsertId = (integer)$result['currentValue'];
            }
        } catch (\Exception $ex) {
            unset($ex);
        }
        return $lastInsertId;
    }
}
if (function_exists('pgGetNextInsertId') === false) {
    /**
     * Get next insert id of given table name, field name, and schema name.
     *
     * @param string $tableName  Table name parameter.
     * @param string $fieldName  Field name parameter.
     * @param string $schemaName Schema name parameter.
     *
     * @return integer
     */
    function pgGetNextInsertId($tableName, $fieldName = '', $schemaName = 'public')
    {
        # Define the started insert id.
        $nextInsertId = 1;
        $sequenceName = pgGetSerialSequenceName($tableName, $fieldName, $schemaName);
        try {
            $query = 'SELECT nextval(' . pgEscape($sequenceName) . '::regclass) "currentValue"';
            $result = pgFetchRow($query);
            if (array_key_exists('currentValue', $result) === true) {
                $nextInsertId = (integer)$result['currentValue'];
            }
        } catch (\Exception $ex) {
            unset($ex);
        }
        return $nextInsertId;
    }
}
if (function_exists('pgGetFieldsInfoOnTable') === false) {
    /**
     * Get all fields information data from selected table on given schema.
     *
     * @param string $tableName  Table name parameter.
     * @param string $schemaName Schema name parameter.
     *
     * @return array
     */
    function pgGetFieldsInfoOnTable($tableName, $schemaName = 'public')
    {
        $query = 'SELECT  *
                  FROM    information_schema.columns
                  WHERE   table_schema = ' . pgEscape($schemaName) . ' AND
                          table_name   = ' . pgEscape($tableName);
        return pgFetchRows($query);
    }
}
if (function_exists('pgInsert') === false) {
    /**
     * Insert row record into table using postgreSQL database handler.
     *
     * @param string $tableName      Table name parameter.
     * @param array  $assocDataArray Associate array data that will be inserted into table.
     * @param string $schemaName     Schema name parameter.
     *
     * @return boolean
     */
    function pgInsert($tableName, array $assocDataArray, $schemaName = 'public')
    {
        return (boolean)pg_insert(
            getCurrentDbConnection(),
            pgGetFullTableName($tableName, $schemaName),
            $assocDataArray
        );
    }
}
if (function_exists('pgUpdate') === false) {
    /**
     * Update record on a table using postgreSQL database handler.
     *
     * @param string $tableName      Table name parameter.
     * @param array  $assocDataArray Associative array data that will be updated into table.
     * @param array  $whereArr       Filtering data array parameter.
     * @param string $schemaName     Schema name parameter.
     *
     * @return boolean
     */
    function pgUpdate($tableName, array $assocDataArray, array $whereArr = [], $schemaName = 'public')
    {
        return (boolean)pg_update(
            getCurrentDbConnection(),
            pgGetFullTableName($tableName, $schemaName),
            $assocDataArray,
            $whereArr
        );
    }
}
if (function_exists('pgDelete') === false) {
    /**
     * Delete record on a table using postgreSQL database handler.
     *
     * @param string $tableName  Table name parameter.
     * @param array  $whereArr   Data record selection using this filter parameter.
     * @param string $schemaName Schema name parameter.
     *
     * @return boolean
     */
    function pgDelete($tableName, array $whereArr, $schemaName = 'public')
    {
        return (boolean)pg_delete(getCurrentDbConnection(), pgGetFullTableName($tableName, $schemaName), $whereArr);
    }
}
if (function_exists('pgSelect') === false) {
    /**
     * Select record from table using given field name, where filter data, and schema name.
     *
     * @param string $tableName  Table name parameter.
     * @param array  $fields     Field name collection data parameter.
     * @param array  $wheres     Where filter data parameter.
     * @param array  $options    Selection option configuration parameter.
     * @param string $schemaName Schema name parameter.
     *
     * @return array
     */
    function pgSelect($tableName, array $fields = [], array $wheres = [], array $options = [], $schemaName = 'public')
    {
        # Initialize all local variables.
        $strFieldQuery = '*';
        $strWhere = '';
        $defaultOptions = ['order' => [], 'group' => [], 'single' => false];
        # Processing the options parameter.
        $options = array_merge($defaultOptions, $options);
        $data = [];
        # Trimming all the field name that located on field names collection data.
        $fields = array_map(
            function ($fieldName) {
                return trim($fieldName);
            },
            $fields
        );
        # Built-up the field query.
        $fieldsInfo = pgGetFieldsInfoOnTable($tableName, $schemaName);
        $existingFieldNames = array_column($fieldsInfo, 'column_name');
        # Check if there is a select all field (*) in field names collection data.
        if (in_array('*', $fields, true) === false and count($fields) > 0) {
            # Setting up field alias.
            $fieldAlias = array_filter(
                $fields,
                function ($key) {
                    return is_numeric($key) === false;
                },
                ARRAY_FILTER_USE_KEY
            );
            $strFieldQuery = implodeArray(
                array_map(
                    function ($fieldName) use ($fieldAlias, $tableName, $schemaName, $existingFieldNames) {
                        $validFieldName = $fieldName;
                        if (in_array($fieldName, $existingFieldNames, true) === true) {
                            $validFieldName = pgGetObjectName($fieldName);
                        }
                        if (array_key_exists($fieldName, $fieldAlias) === true) {
                            $validFieldName .= ' AS ' . pgGetObjectName($fieldAlias[$fieldName]);
                        }
                        return $validFieldName;
                    },
                    $fields
                ),
                ', '
            );
            # Filtering and built-up the where query.
            $arrWhere = [];
            foreach ($wheres as $fieldName => $where) {
                if (in_array($fieldName, $existingFieldNames, true) === true) {
                    $fieldName = pgGetObjectName($fieldName);
                }
                $arrWhere[] = $fieldName . ' ' . $where;
            }
            $strWhere = getMappedValue(count($arrWhere) > 0, ' WHERE ' . implodeArray($arrWhere, ' '), '');
        }
        $single = (boolean)$options['single'];
        # Validate group data.
        $groups = array_map(
            function ($groupItem) use ($existingFieldNames) {
                if (in_array($groupItem, $existingFieldNames, true) === true) {
                    $groupItem = pgGetObjectName($groupItem);
                }
                return $groupItem;
            },
            (array)$options['group']
        );
        # Validate order data.
        $orders = array_map(
            function ($orderItem) use ($existingFieldNames) {
                if (in_array($orderItem, $existingFieldNames, true) === true) {
                    $orderItem = pgGetObjectName($orderItem);
                }
                return $orderItem;
            },
            (array)$options['order']
        );
        $strGroupBy = getMappedValue(getValue($groups) !== null, ' GROUP BY ' . implodeArray($groups) . ' ', '');
        $strOrderBy = getMappedValue(getValue($orders) !== null, ' ORDER BY ' . implodeArray($orders) . ' ', '');
        $query = 'SELECT ' . $strFieldQuery . ' FROM ' . pgGetFullTableName($tableName, $schemaName) .
            $strWhere . $strGroupBy . $strOrderBy;
        try {
            if ($single === true) {
                $data = pgFetchRow($query);
            } else {
                $data = pgFetchRows($query);
            }
        } catch (\Exception $ex) {
            unset($ex);
        }
        return $data;
    }
}
if (function_exists('pgIsDataExists') === false) {
    /**
     * Check if data is exists or not on given table.
     *
     * @param string  $tableName     Table name parameter.
     * @param string  $fieldName     Field name parameter.
     * @param string  $checkedValue  Checked value parameter.
     * @param string  $whereCriteria Additional filtering criteria string formed parameter.
     * @param boolean $caseSensitive Case sensitive option parameter.
     *
     * @return boolean
     */
    function pgIsDataExists($tableName, $fieldName, $checkedValue, $whereCriteria = '', $caseSensitive = false)
    {
        $mainCriteria = '"' . $fieldName . '" = ' . pgEscape($checkedValue);
        if ($caseSensitive === true) {
            $mainCriteria = ' lower("' . $fieldName . '") = ' . pgEscape(strtolower($checkedValue));
        }
        $query = 'SELECT "' . $fieldName . '" FROM "' . $tableName .
            '" WHERE ' . $mainCriteria . ' ' . $whereCriteria;
        return count(pgFetchRows($query)) > 0;
    }
}
if (function_exists('pgActiveTransactionId') === false) {
    /**
     * Get transaction id that handle by postgreSQL database.
     *
     * @return string
     */
    function pgActiveTransactionId()
    {
        return getSessionValue('dbActiveTransactionId', '');
    }
}
if (function_exists('pgIsTransactionStarted') === false) {
    /**
     * Check if database transaction is started or not.
     *
     * @return boolean
     */
    function pgIsTransactionStarted()
    {
        return (boolean)getSessionValue('dbTransactionStarted', false);
    }
}
if (function_exists('pgStartTransaction') === false) {
    /**
     * Start database transaction (only support 1 asynchronous transaction).
     *
     * @param boolean $withPreparedTransaction Begin transaction with prepared transaction.
     *
     * @throws \LogicException If failed to end all the started database transaction.
     * @return boolean
     */
    function pgStartTransaction($withPreparedTransaction = false)
    {
        $dbHandler = getCurrentDbConnection();
        if (pgIsTransactionStarted() === false) {
            if ($withPreparedTransaction === true) {
                # Start new prepared transaction.
                $activeTransactionId = uniqid('trans', false);
                $result = pg_query($dbHandler, 'BEGIN') !== false and
                pg_query($dbHandler, 'PREPARE TRANSACTION ' . pgEscape($activeTransactionId)) !== false;
                if ($result === true) {
                    setSessionValue('dbActiveTransactionId', $activeTransactionId);
                }
            } else {
                $result = pg_query($dbHandler, 'BEGIN');
            }
            if ($result !== false) {
                setSessionValue('dbTransactionStarted', true);
            } else {
                throw new \LogicException('Failed to start new database transaction');
            }
        }
        return pgIsTransactionStarted();
    }
}
if (function_exists('pgEndTransaction') === false) {
    /**
     * End database transaction with given end statement of transaction.
     *
     * @param string $endStatement End type statement parameter.
     *
     * @throws \InvalidArgumentException If invalid end type transaction given.
     * @throws \LogicException If failed to end the started transaction.
     * @return boolean
     */
    function pgEndTransaction($endStatement)
    {
        $endStatement = strtoupper($endStatement);
        if (in_array($endStatement, ['COMMIT', 'ROLLBACK'], true) === false) {
            throw new \InvalidArgumentException('Invalid end type transaction given');
        } elseif (pgIsTransactionStarted() === true) {
            $dbHandler = getCurrentDbConnection();
            $activeTransactionId = pgActiveTransactionId();
            if ($activeTransactionId !== '') {
                $result = pg_query($dbHandler, $endStatement . ' PREPARED ' . pgEscape($activeTransactionId));
                if ($result !== false) {
                    unsetSession('dbActiveTransactionId');
                }
            } else {
                $result = pg_query($dbHandler, $endStatement);
            }
            if ($result !== false) {
                setSessionValue('dbTransactionStarted', false);
            } else {
                throw new \LogicException('Failed to end the started transaction');
            }
        }
        return pgIsTransactionStarted() === false;
    }
}
if (function_exists('pgCommitTransaction') === false) {
    /**
     * Commit database transaction.
     *
     * @throws \InvalidArgumentException If invalid end type transaction given.
     * @return boolean
     */
    function pgCommitTransaction()
    {
        return pgEndTransaction('COMMIT');
    }
}
if (function_exists('pgRollbackTransaction') === false) {
    /**
     * Rollback database transaction.
     *
     * @throws \InvalidArgumentException If invalid end type transaction given.
     * @return boolean
     */
    function pgRollbackTransaction()
    {
        return pgEndTransaction('ROLLBACK');
    }
}
if (function_exists('pgIsBooleanType') === false) {
    /**
     * Check if value is a boolean type on postgreSQL database.
     *
     * @param mixed $value Value parameter that will be checked.
     *
     * @return boolean
     */
    function pgIsBooleanType($value)
    {
        if (is_string($value) === true) {
            $value = strtolower($value);
        }
        $booleanFieldRange = ['f', 'n', '0', 0, false, 't', 'y', '1', 1, true];
        return in_array($value, $booleanFieldRange, true) === true;
    }
}
if (function_exists('pgCastToBoolean') === false) {
    /**
     * Casting the value into boolean that constraint by the boolean type on postgreSQL database.
     *
     * @param mixed $value Value parameter that will be casted.
     *
     * @return boolean
     */
    function pgCastToBoolean($value)
    {
        if (is_string($value) === true) {
            $value = strtolower($value);
        }
        $booleanFieldRange = [['f', 'n', '0', 0, false], ['t', 'y', '1', 1, true]];
        foreach ($booleanFieldRange as $index => $range) {
            if (in_array($value, $range, true) === true) {
                $value = $index;
                break;
            }
        }
        return (boolean)$value;
    }
}
