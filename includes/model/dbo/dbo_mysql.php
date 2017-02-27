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
      'primary_key' => ['name' => 'int(11) DEFAULT NULL auto_increment'],
      'string'      => ['name' => 'varchar', 'limit' => '255'],
      'text'        => ['name' => 'text'],
      'integer'     => ['name' => 'int', 'limit' => '11', 'formatter' => 'intval'],
      'float'       => ['name' => 'float', 'formatter' => 'floatval'],
      'datetime'    => ['name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'],
      'timestamp'   => ['name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'],
      'time'        => ['name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'],
      'date'        => ['name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'],
      'binary'      => ['name' => 'blob'],
      'boolean'     => ['name' => 'tinyint', 'limit' => '1', 'formatter' => 'intval']
  ];

  var $dbCon = null;

  var $dbError;

  var $dbname = "";

  var $dbtype = "mysql";

  var $divStyle = "border:1px solid black; margin:4px; padding:4px; background-color:#FFDEAD;";

  var $host = "localhost";

  var $isConnected;

  var $isShowError = true;

  var $lastQuery;

  var $password = "";

  var $res;

  var $strConn = "";

  var $user = "";

  //fungsi untuk konek ke database mySQL
  //parameternya adalah gabungan string dari nama host,port,dbname,user, dan password

  function affectedRows($res = null)
  {
    if ($res == null) {
      $res = $this->res;
    }
    if (!$res) {
      return false;
    }
    return mysql_affected_rows($this->dbCon);
  }

  function begin()
  {
    return $this->execute('START TRANSACTION');
  }

  //fungsi untuk mengambil daftar semua table dalam suatu database

  /**
   * Enter description here...
   *
   * @param unknown_type $schema
   *
   * @return unknown
   */
  function buildSchemaQuery($schema)
  {
    $search = [
        '{AUTOINCREMENT}',
        '{PRIMARY}',
        '{UNSIGNED}',
        '{FULLTEXT}',
        '{FULLTEXT_MYSQL}',
        '{BOOLEAN}',
        '{UTF_8}'
    ];
    $replace = [
        'int(11) not null auto_increment',
        'primary key',
        'unsigned',
        'FULLTEXT',
        'FULLTEXT',
        'enum (\'true\', \'false\') NOT NULL default \'true\'',
        '/*!40100 CHARACTER SET utf8 COLLATE utf8_unicode_ci */'
    ];
    $query = trim(str_replace($search, $replace, $schema));
    return $query;
  }

  //fungsi untuk mengambil informasi kolom pada suatu table
  //output: array daftar columns

  function close()
  {
    $tutupKoneksi = mysql_close($this->dbCon);
    $this->isConnected = false;
    return 0;
  }

  ///fungsi untuk mengeksekusi perintah SQL
  ///parameter yang diperlukan adalah nilai string dari SQL tersebut

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
    $limit = $this->length($real);
    @list($col, $vals) = explode('(', $col);
    if (in_array($col, ['date', 'time', 'datetime', 'timestamp'])) {
      return $col;
    }
    if ($col == 'tinyint' && $limit == 1) {
      return 'boolean';
    }
    if (strpos($col, 'int') !== false) {
      return 'integer';
    }
    if (strpos($col, 'char') !== false || $col == 'tinytext') {
      return 'string';
    }
    if (strpos($col, 'text') !== false) {
      return 'text';
    }
    if (strpos($col, 'blob') !== false) {
      return 'binary';
    }
    if (in_array($col, ['float', 'double', 'decimal'])) {
      return 'float';
    }
    if (strpos($col, 'enum') !== false) {
      return "enum($vals)";
    }
    if ($col == 'boolean') {
      return $col;
    }
    return 'text';
  }

  //mengambil data dari tiap record
  //parameter yang diperlukan adalah hasil dari query

  function commit()
  {
    return $this->execute('COMMIT');
  }

  //mengetahui jumlah record dari hasil query
  //parameter yan diperlukan adalah hasil dari query
  //hasil dari fungsi ini INTEGER

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
    if (is_callable('mysql_connect')) {
      $this->strConn = "host='" . $this->host . "' port=5432 dbname=" . $this->dbname . " user=" . $this->user . " password=" . $this->password;
      $this->dbCon = @mysql_connect($this->host, $this->user, $this->password);
      if (!mysql_select_db($this->dbname, $this->dbCon)) {
        $this->dbError = '<div style="' . $this->divStyle . '"><strong>Error: </strong>Cannot connect to MySQL server database';
        $this->dbError .= '<table border=0 cellspacing=0 cellpadding=0>';
        $this->dbError .= '<tr><td width=80>Host</td><td width=10>:</td><td>' . DB_SERVER . '</td>';
        $this->dbError .= '<tr><td>Database</td><td>:</td><td>' . DB_NAME . '</td>';
        $this->dbError .= '<tr><td>User</td><td>:</td><td>' . DB_USER . '</td>';
        $this->dbError .= '</table>';
        $this->dbError .= '</div>';
        echo $this->dbError;
      } else {
        if (defined('DB_ENCODING') && (DB_ENCODING != "")) {
          $this->setEncoding(DB_ENCODING);
        }
        $this->isConnected = true;
        return true;
      }
    } else {
      $this->dbError = '<div style="' . $this->divStyle . '"><strong>Fatal error:</strong> Call to undefined function mysql_connect()<br />Please activated MySQL extension in your php.ini file</div>';
      echo $this->dbError;
    }
    $this->isConnected = false;
    return false;
  }

  function describe($tableName)
  {
    $fields = false;
    $cols = $this->getRecordSet("DESCRIBE " . $tableName);
    foreach ($cols as $column) {
      $fields[$column['Field']] = [
          'name'    => $column['Field'],
          'type'    => $this->column($column['Type']),
          'null'    => ($column['Null'] == 'YES' ? true : false),
          'default' => $column['Default'],
          'length'  => $this->length($column['Type'])
      ];
    }
    return $fields;
  }

  function escape_string($String)
  {
    return mysql_escape_string($String);
  }

  function execute($stringSQL, $errMessage = "")
  {
    try {
      $this->lastQuery = $stringSQL;
      $this->res = @mysql_query($stringSQL, $this->dbCon);
    } catch (Exception $e) {
      if ($this->isShowError || $errMessage != "") {
        $this->setErrorMessage($errMessage);
        echo $this->dbError;
      }
      return false;
    }
    if ($this->res) {
      return $this->res;
    } else {
      if ($this->isShowError || $errMessage != "") {
        $this->setErrorMessage($errMessage);
        echo $this->dbError;
      }
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
    $strOffset = "";
    if ($page > 1 && $intLimit != null) {
      //jump to offset
      $intLimit = intval($intLimit);
      $strOffset = "OFFSET " . ((intval($page) - 1) * $intLimit) . "";
    }
    if ($intLimit != null && $intLimit != 0) {
      $intLimit = intval($intLimit);
      $strLimit = "LIMIT " . $intLimit;
    }
    $strSQL = "SELECT " . $strFields . " FROM " . $this->formatTableName(
            $strTableName
        ) . " " . $strCondition . " " . $strOrder . " " . $strLimit . " " . $strOffset;
    $res = $this->execute($strSQL);
    $arrResult = [];
    while ($rowDb = $this->fetchrow($res)) {
      if ($autoIndexField !== null) {
        $arrResult[$rowDb[$autoIndexField]] = $rowDb;
      } else {
        $arrResult[] = $rowDb;
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
        $result_type = MYSQL_ASSOC;
        break;
      case "NUM"   :
        $result_type = MYSQL_NUM;
        break;
      default      :
        $result_type = MYSQL_BOTH;
    }
    $resFetch = @mysql_fetch_array($res, $result_type);
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
    return "`" . $fieldName . "`";
  }

  function formatTableName($tableName)
  {
    return "`" . $tableName . "`";
  }

  function freeResult($res = null)
  {
    if ($res == null) {
      $res = $this->res;
    }
    if (!$res) {
      return false;
    }
    mysql_free_result($res);
  }

  /**
   * Generate a MySQL-native column schema string
   *
   * @param array $column An array structured like the following: array('name', 'type'[, options]),
   *                      where options can be 'default', 'length', or 'key'.
   *
   * @return string
   */
  function generateColumnSchema($column)
  {
    $name = $type = null;
    $column = array_merge(['null' => true], $column);
    list($name, $type) = $column;
    if (empty($name) || empty($type)) {
      $this->setErrorMessage('Column name or type not defined in schema');
      return null;
    }
    if (!isset($this->columns[$type])) {
      $this->setErrorMessage("Column type {$type} does not exist");
      return null;
    }
    $real = $this->columns[$type];
    $out = $this->name($name) . ' ' . $real['name'];
    if (isset($real['limit']) || isset($real['length']) || isset($column['limit']) || isset($column['length'])) {
      if (isset($column['length'])) {
        $length = $column['length'];
      } elseif (isset($column['limit'])) {
        $length = $column['limit'];
      } elseif (isset($real['length'])) {
        $length = $real['length'];
      } else {
        $length = $real['limit'];
      }
      $out .= '(' . $length . ')';
    }
    if (isset($column['key']) && $column['key'] == 'primary') {
      $out .= ' NOT NULL AUTO_INCREMENT';
    } elseif (isset($column['default'])) {
      $out .= ' DEFAULT ' . $this->value($column['default'], $type);
    } elseif (isset($column['null']) && $column['null'] == true) {
      $out .= ' DEFAULT NULL';
    } elseif (isset($column['default']) && isset($column['null']) && $column['null'] == false) {
      $out .= ' DEFAULT ' . $this->value($column['default'], $type) . ' NOT NULL';
    } elseif (isset($column['null']) && $column['null'] == false) {
      $out .= ' NOT NULL';
    }
    return $out;
  }

  /**
   * Generate a MySQL schema for the given Schema object
   *
   * @param object $schema An instance of a subclass of CakeSchema
   * @param string $table  Optional.  If specified only the table name given will be generated.
   *                       Otherwise, all tables defined in the schema are generated.
   *
   * @return string
   */
  function generateSchema($schema, $table = null)
  {
    $out = '';
    $arrTables = $this->tableList();
    foreach ($arrTables as $curTable) {
      if (empty($table) || $table == $curTable) {
        $out .= 'CREATE TABLE `' . $curTable . "` (\n";
        $colList = [];
        $primary = null;
        $columns = $this->describe($curTable);
        foreach ($columns as $col) {
          if (isset($col['key']) && $col['key'] == 'primary') {
            $primary = $col;
          }
          $colList[] = $this->generateColumnSchema($col);
        }
        if (empty($primary)) {
          $primary = ['id', 'integer', 'key' => 'primary'];
          array_unshift($colList, $this->generateColumnSchema($primary));
        }
        $colList[] = 'PRIMARY KEY (' . $this->name($primary[0]) . ')';
        $out .= "\t" . join(",\n\t", $colList) . "\n);\n\n";
      }
    }
    return $out;
  }

  function getEncoding()
  {
    return mysql_client_encoding($this->dbCon);
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
    $res = $this->execute('SELECT LAST_INSERT_ID() AS insertID');
    $id = $this->fetchrow($res);
    if ($id !== false && !empty($id) && isset($id['insertID'])) {
      return $id['insertID'];
    }
    return null;
  }

  /**
   * Gets the length of a database-native column description, or null if no length
   *
   * @param string $real Real database-layer column type (i.e. "varchar(255)")
   *
   * @return int An integer representing the length of the column
   */
  function length($real)
  {
    $col = str_replace([')', 'unsigned'], '', $real);
    $limit = null;
    if (strpos($col, '(') !== false) {
      list($col, $limit) = explode('(', $col);
    }
    if ($limit != null) {
      return intval($limit);
    }
    return null;
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
    if ($limit) {
      $rt = '';
      if (!strpos(strtolower($limit), 'limit') || strpos(strtolower($limit), 'limit') === 0) {
        $rt = ' LIMIT';
      }
      $rt .= ' ' . $limit;
      if ($offset) {
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
    $intJmlBrs = mysql_num_rows($res);
    return $intJmlBrs;
  }

  function rollback()
  {
    return $this->execute('ROLLBACK');
  }

  function setEncoding($enc)
  {
    return (($this->execute('SET NAMES ' . $enc)) != false);
  }

  function setErrorMessage($errMessage = "")
  {
    if ($errMessage == "") {
      $last_error = @mysql_error($this->dbCon);
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

  function tableList()
  {
    $sql = "SHOW TABLES FROM " . $this->dbname . ";";
    $result = $this->getRecordSet($sql);
    return $result;
  }
} // end of class db
?>
