<?php
if (!DEFINED('DB_SERVER') &&
    !DEFINED('DB_NAME') &&
    !DEFINED('DB_USER') &&
    !DEFINED('DB_PWD')
) {
  die("Direct access to this DBO file was prohibited. Please call connection manager instead.");
}

/*****************************************
 * CdbClass : kelas untuk mengakses basis data
 * Copyright (c) Invosa Systems, PT
 *
 * dbClass.php
 * Author:  Dedy Sukandar
 *
 * Ver   :  - 1.00, 2006-11-22
 ******************************************/
class CDboModel
{

  var $columns = [
      'primary_key' => ['name' => 'int IDENTITY (1, 1) NOT NULL'],
      'string'      => ['name' => 'varchar', 'limit' => '255'],
      'text'        => ['name' => 'text'],
      'integer'     => ['name' => 'int', 'formatter' => 'intval'],
      'float'       => ['name' => 'float', 'formatter' => 'floatval'],
      'datetime'    => ['name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'],
      'timestamp'   => ['name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'],
      'time'        => ['name' => 'datetime', 'format' => 'H:i:s', 'formatter' => 'date'],
      'date'        => ['name' => 'datetime', 'format' => 'Y-m-d', 'formatter' => 'date'],
      'binary'      => ['name' => 'image'],
      'boolean'     => ['name' => 'bit', 'formatter' => 'intval']
  ];

  var $dbCon = null;

  var $dbError;

  var $dbname = "";

  var $dbtype = "mssql";

  var $divStyle = "border:1px solid black; margin:4px; padding:4px; background-color:#FFDEAD;";

  var $host = "localhost";

  var $isConnected;

  var $isShowError = true;

  var $lastQuery;

  var $password = "";

  var $res;

  var $strConn = "";

  var $user = "";

  //fungsi untuk konek ke database postgre
  //parameternya adalah gabungan string dari nama host,port,dbname,user, dan password

  function affectedRows()
  {
    return mssql_rows_affected($this->dbCon);
  }

  function begin()
  {
    return $this->execute("BEGIN TRANSACTION");
  }

  //fungsi untuk mengambil daftar semua table dalam suatu database

  function close()
  {
    if ($this->isConnected) {
      $tutupKoneksi = mssql_close($this->dbCon);
    }
    $this->isConnected = false;
    return 0;
  }

  //fungsi untuk mengambil informasi kolom pada suatu table
  //output: array daftar columns

  /**
   * Converts database-layer column types to basic types
   *
   * @param string $real Real database-layer column type (i.e. "varchar(255)")
   *
   * @return string Abstract column type (i.e. "string")
   */
  function column($real)
  {
    if (is_array($real)) {
      $col = $real['name'];
      if (isset($real['limit'])) {
        $col .= '(' . $real['limit'] . ')';
      }
      return $col;
    }
    $col = str_replace(')', '', $real);
    $limit = null;
    @list($col, $limit) = explode('(', $col);
    if (in_array($col, ['date', 'time', 'datetime', 'timestamp'])) {
      return $col;
    }
    if ($col == 'bit') {
      return 'boolean';
    }
    if (strpos($col, 'int') !== false || $col == 'numeric') {
      return 'integer';
    }
    if (strpos($col, 'char') !== false) {
      return 'string';
    }
    if (strpos($col, 'text') !== false) {
      return 'text';
    }
    if (strpos($col, 'binary') !== false || $col == 'image') {
      return 'binary';
    }
    if (in_array($col, ['float', 'real', 'decimal'])) {
      return 'float';
    }
    return 'text';
  }

  ///fungsi untuk mengeksekusi perintah SQL
  ///parameter yang diperlukan adalah nilai string dari SQL tersebut

  function commit()
  {
    return $this->execute("COMMIT");
  }

  //mengambil data dari tiap record
  //parameter yang diperlukan adalah hasil dari query

  function connect()
  {
    //already connected
    if ($this->dbCon) {
      return true;
    }
    $this->host = DB_SERVER;
    $this->dbname = DB_NAME;
    $this->user = DB_USER;
    $this->password = DB_PWD;
    $this->lastQuery = '';
    $this->isConnected = false;
    if (is_callable('mssql_connect')) {
      $this->dbCon = @mssql_connect($this->host, $this->user, $this->password);
      if (mssql_select_db($this->dbname, $this->dbCon)) {
        $this->isConnected = true;
      } else {
        $this->dbError = '<div style="' . $this->divStyle . '"><strong>Error: </strong>Cannot connect to MS-SQL server database';
        $this->dbError .= '<table border=0 cellspacing=0 cellpadding=0>';
        $this->dbError .= '<tr><td width=80>Host</td><td width=10>:</td><td>' . DB_SERVER . '</td>';
        $this->dbError .= '<tr><td>Database</td><td>:</td><td>' . DB_NAME . '</td>';
        $this->dbError .= '<tr><td>User</td><td>:</td><td>' . DB_USER . '</td>';
        $this->dbError .= '</table>';
        $this->dbError .= '</div>';
        echo $this->dbError;
      }
    } else {
      $this->dbError = '<div style="' . $this->divStyle . '"><strong>Fatal error:</strong> Call to undefined function mssql_connect()<br />Please activated MS-SQL php extension</div>';
      echo $this->dbError;
    }
    return $this->isConnected;
  }

  //mengetahui jumlah record dari hasil query
  //parameter yan diperlukan adalah hasil dari query
  //hasil dari fungsi ini INTEGER

  function dataSeek($intOffset)
  {
    if (!$this->res) {
      return 0;
    }
    return mssql_data_seek($this->res, $intOffset);
  }

  function describe($tableName)
  {
    $fields = false;
    $cols = $this->getRecordSet(
        "
      SELECT COLUMN_NAME as Field,
             DATA_TYPE as Type,
             COL_LENGTH('" . $tableName . "', COLUMN_NAME) as Length,
             IS_NULLABLE As [Null],
             COLUMN_DEFAULT as [Default],
             COLUMNPROPERTY(OBJECT_ID('" . $tableName . "'), COLUMN_NAME, 'IsIdentity') as [Key],
             NUMERIC_SCALE as Size
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = '" . $tableName . "'"
    );
    foreach ($cols as $column) {
      $fields[] = [
          'name'    => $column['Field'],
          'type'    => $this->column($column['Type']),
          'null'    => (strtoupper($column['Null']) == 'YES'),
          'default' => $column['Default']
      ];
    }
    return $fields;
  }

  function escape_string($String)
  {
    return addslashes($String);
  }

  function execute($stringSQL, $errMessage = "")
  {
    try {
      $this->lastQuery = $stringSQL;
      $this->res = @mssql_query($stringSQL, $this->dbCon);
    } catch (Exception $e) {
      if ($this->isShowError || $errMessage != "") {
        $this->setErrorMessage($errMessage);
        echo $this->dbError;
      }
      $this->res = false;
      return false;
    }
    if ($this->res) {
      return $this->res;
    } else {
      if ($this->isShowError || $errMessage != "") {
        $this->setErrorMessage($errMessage);
        echo $this->dbError;
      }
      $this->res = false;
      return false;
    }
  }

  function fetchAll(
      $strTableName,
      $strCondition,
      $strFields,
      $strOrder,
      $intLimit,
      $page,
      $autoIndexField
      /*set with field name */,
      &$strSQL
  ) {
    $strLimit = "";
    if ($page <= 1 || $page == null) {
      if ($intLimit != null && $intLimit != 0) {
        $intLimit = intval($intLimit);
        $strLimit = "TOP " . $intLimit;
      }
    }
    $strSQL = "SELECT " . $strLimit . " " . $strFields . " FROM " . $this->formatTableName(
            $strTableName
        ) . " " . $strCondition . " " . $strOrder;
    $res = $this->execute($strSQL);
    //handle paging
    if ($page > 1 && $intLimit != null) {
      //jump to offset
      $offset = ($page - 1) * $limit;
      if ($this->numrows($res) > 0) {
        $db->dataSeek($offset);
      }
    }
    $arrResult = [];
    $counter = 0;
    while ($rowDb = $this->fetchrow($res)) {
      $counter++;
      if ($autoIndexField !== null) {
        $arrResult[$rowDb[$autoIndexField]] = $rowDb;
      } else {
        $arrResult[] = $rowDb;
      }
      if ($intLimit > 0 && $counter == $intLimit) //stop after limit reach
      {
        break;
      }
    }
    return $arrResult;
  }

  function fetchrow($res = null, $result_type = "ASSOC")
  {
    if ($res == null) {
      $res = $this->res;
    }
    if (!$res) {
      return false;
    }
    switch ($result_type) {
      case "ASSOC" :
        $result_type = MSSQL_ASSOC;
        break;
      case "NUM"   :
        $result_type = MSSQL_NUM;
        break;
      default      :
        $result_type = MSSQL_BOTH;
    }
    $resFetch = @mssql_fetch_array($this->res, $result_type);
    //$resFetch = mysql_fetch_array($res);
    if (!$resFetch) {
      $this->setErrorMessage();
      echo $this->dbError;
      return false;
    } else {
      return $resFetch;
    }
  }

  function formatFieldName($fieldName)
  {
    return '[' . $fieldName . ']';
  }

  function formatTableName($tableName)
  {
    $tableName = str_replace(["[", "]"], "", $tableName);
    $arrName = explode(".", $tableName);
    $tableName = "";
    foreach ($arrName as $name) {
      if ($tableName == "") {
        $tableName = "[" . $name . "]";
      } else {
        $tableName .= ".[" . $name . "]";
      }
    }
    return $tableName;
  }

  function freeResult($res = null)
  {
    if ($res == null) {
      $res = $this->res;
    }
    if (!$res) {
      return false;
    }
    mssql_free_result($res);
  }

  function getRecordSet($strSQL, $result_type = "BOTH")
  {
    if (!$this->isConnected) {
      if (!$this->connect()) {
        return null;
      }
    }
    $this->res = $this->execute($strSQL);
    $arrResult = [];
    while ($rowDb = $this->fetchrow($this->res, $result_type)) {
      $arrResult[] = $rowDb;
    }
    return $arrResult;
  }

  function lastInsertId($tableName, $sequenceField = 'id')
  {
    $res = $this->execute("SELECT SCOPE_IDENTITY() AS insertID");
    if ($data = $this->fetchrow($res)) {
      return $data['insertID'];
    } else {
      return null;
    }
  }

  /**
   * Returns a limit statement in the correct format for the particular database.
   *
   * @param int $limit  Limit of results returned
   * @param int $offset Offset from which to start results
   *
   * @return string SQL limit/offset statement
   */
  function limit($limit, $offset = null)
  {
    /*SQL Server 2005 */
    if ($limit) {
      $rt = '';
      if (!strpos(strtolower($limit), 'top') || strpos(strtolower($limit), 'top') === 0) {
        $rt = ' TOP';
      }
      $rt .= ' ' . $limit;
      if (is_int($offset) && $offset > 0) {
        $rt .= ' OFFSET ' . $offset;
      }
      return $rt;
    }
    return null;
  }

  function numrows($res = null)
  {
    if ($res == null) {
      $res = $this->res;
    }
    if (!$res) {
      return false;
    }
    $intJmlBrs = mssql_num_rows($res);
    //$intJmlBrs = mysql_numrows($res);
    return $intJmlBrs;
  }

  function rollback()
  {
    return $this->execute("ROLLBACK");
  }

  function setErrorMessage($errMessage = "")
  {
    if ($errMessage == "") {
      $last_error = mssql_get_last_message();//($this->dbCon);
      if ($last_error != "") {
        if ($this->lastQuery == '') {
          $this->dbError = '<div style="' . $this->divStyle . '"><b>Error: </b>' . $last_error . "</div>";
        } else {
          $this->dbError = '<div style="' . $this->divStyle . '"><b>Query:</b> ' . $this->lastQuery . '<br />' . $last_error . "</div>";
        }
      } else {
        $this->dbError = '';
      }
    } else {
      $this->dbError = '<div style="' . $this->divStyle . '"><b>Error: </b>' . $errMessage . "</div>";
    }
  }

  function tableList($schema = "public")
  {
    $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES";
    $result = $this->getRecordSet($sql);
    return $result;
  }
} // end of class db
?>
