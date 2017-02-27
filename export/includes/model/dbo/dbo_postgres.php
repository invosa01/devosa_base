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
      'primary_key' => ['name' => 'serial NOT NULL'],
      'string'      => ['name' => 'varchar', 'limit' => '255'],
      'text'        => ['name' => 'text'],
      'integer'     => ['name' => 'integer', 'formatter' => 'intval'],
      'float'       => ['name' => 'float', 'formatter' => 'floatval'],
      'datetime'    => ['name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'],
      'timestamp'   => ['name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'],
      'time'        => ['name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'],
      'date'        => ['name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'],
      'binary'      => ['name' => 'bytea'],
      'boolean'     => ['name' => 'boolean'],
      'number'      => ['name' => 'numeric'],
      'inet'        => ['name' => 'inet']
  ];

  var $dbCon = null;

  var $dbError;

  var $dbname = "";

  var $dbtype = "postgres";

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

  function affectedRows($res = null)
  {
    if ($res == null) {
      $res = $this->res;
    }
    if (!$res) {
      return false;
    }
    return pg_affected_rows($res);
  }

  function begin()
  {
    return $this->execute("BEGIN;");
  }

  //fungsi untuk mengambil daftar semua table dalam suatu database

  function close()
  {
    $tutupKoneksi = pg_close($this->dbCon);
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
    if (in_array($col, ['date', 'time'])) {
      return $col;
    }
    if (strpos($col, 'timestamp') !== false) {
      return 'datetime';
    }
    if ($col == 'inet') {
      return ('inet');
    }
    if ($col == 'boolean') {
      return 'boolean';
    }
    if (strpos($col, 'int') !== false && $col != 'interval') {
      return 'integer';
    }
    if (strpos($col, 'char') !== false) {
      return 'string';
    }
    if (strpos($col, 'text') !== false) {
      return 'text';
    }
    if (strpos($col, 'bytea') !== false) {
      return 'binary';
    }
    if (in_array($col, ['float', 'float4', 'float8', 'double', 'double precision', 'decimal', 'real', 'numeric'])) {
      return 'float';
    }
    return 'text';
  }

  ///fungsi untuk mengeksekusi perintah SQL
  ///parameter yang diperlukan adalah nilai string dari SQL tersebut

  function commit()
  {
    return $this->execute("COMMIT;");
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
    if (is_callable('pg_connect')) {
      $this->strConn = "host='" . $this->host . "' port=5432 dbname=" . $this->dbname . " user=" . $this->user . " password=" . $this->password;
      $this->dbCon = @pg_connect($this->strConn);
      if (!$this->dbCon) {
        $this->dbError = '<div style="' . $this->divStyle . '"><strong>Error: </strong>Cannot connect to PostgreSQL server database';
        $this->dbError .= '<table border=0 cellspacing=0 cellpadding=0>';
        $this->dbError .= '<tr><td width=80>Host</td><td width=10>:</td><td>' . DB_SERVER . '</td>';
        $this->dbError .= '<tr><td>Database</td><td>:</td><td>' . DB_NAME . '</td>';
        $this->dbError .= '<tr><td>User</td><td>:</td><td>' . DB_USER . '</td>';
        $this->dbError .= '</table>';
        $this->dbError .= '</div>';
        echo $this->dbError;
      } else {
        if (DB_ENCODING != "") {
          $this->setEncoding(DB_ENCODING);
        }
        $this->isConnected = true;
        return true;
      }
    } else {
      $this->dbError = '<div style="' . $this->divStyle . '"><strong>Fatal error:</strong> Call to undefined function pg_connect()<br />Please activated PostgreSQL php extension</div>';
      echo $this->dbError;
    }
    $this->isConnected = false;
    return false;
  }

  //mengetahui jumlah record dari hasil query
  //parameter yan diperlukan adalah hasil dari query
  //hasil dari fungsi ini INTEGER

  function describe($tableName)
  {
    $fields = false;
    $cols = $this->getRecordSet(
        "SELECT DISTINCT column_name AS name, data_type AS type, is_nullable AS null, column_default AS default, ordinal_position AS position, character_maximum_length AS char_length, character_octet_length AS oct_length FROM information_schema.columns WHERE table_name = '" . $tableName . "' ORDER BY position"
    );
    foreach ($cols as $c) {
      if (strpos($c['default'], 'nextval(') === 0) {
        $c['default'] = null;
      }
      if (!empty($c['char_length'])) {
        $length = intval($c['char_length']);
      } elseif (!empty($c['oct_length'])) {
        $length = intval($c['oct_length']);
      } else {
        $length = $this->length($c['type']);
      }
      $fields[$c['name']] = [
          'name'     => $c['name'],
          'type'     => $this->column($c['type']),
          'null'     => ($c['null'] == 'NO' ? false : true),
          'default'  => $c['default'],
          'position' => $c['position'],
          'length'   => $length
      ];
    }
    return $fields;
  }

  function escape_string($String)
  {
    return pg_escape_string($String);
  }

  function execute($stringSQL, $errMessage = "")
  {
    try {
      $this->lastQuery = $stringSQL;
      //$this->res = pg_exec($this->dbCon, $stringSQL);
      $this->res = @pg_query($this->dbCon, $stringSQL);
    } catch (Exception $e) {
      if ($this->isShowError || $errMessage != "") {
        $this->setErrorMessage($errMessage);
        echo $this->dbError;
      }
      //echo "$stringSQL<br>";
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
        $result_type = PGSQL_ASSOC;
        break;
      case "NUM"   :
        $result_type = PGSQL_NUM;
        break;
      default      :
        $result_type = PGSQL_BOTH;
    }
    $resFetch = @pg_fetch_array($res, null, $result_type);
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
    return '"' . $fieldName . '"';
  }

  function formatTableName($tableName)
  {
    return '"' . $tableName . '"';
  }

  function freeResult($res = null)
  {
    if ($res == null) {
      $res = $this->res;
    }
    if (!$res) {
      return false;
    }
    pg_free_result($res);
  }

  function getEncoding()
  {
    return pg_client_encoding($this->dbCon);
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
    $seq = "{$tableName}_{$sequenceField}_seq";
    $res = $this->execute("SELECT last_value AS max FROM \"{$seq}\"");
    if ($data = $this->fetchrow($res)) {
      return $data['max'];
    } else {
      return null;
    }
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
    $intJmlBrs = pg_num_rows($res);
    //$intJmlBrs = mysql_numrows($res);
    return $intJmlBrs;
  }

  function rollback()
  {
    return $this->execute("ROLLBACK;");
  }

  function setEncoding($enc)
  {
    return pg_set_client_encoding($this->dbCon, $enc) == 0;
  }

  function setErrorMessage($errMessage = "")
  {
    if ($errMessage == "") {
      $connection_status = @pg_connection_status($this->dbCon);
      $last_error = @pg_last_error($this->dbCon);
      $result_error = @pg_result_error($this->dbCon);
      $last_notice = @pg_last_notice($this->dbCon);
      $_errors = [];
      if ($connection_status != '') {
        $_errors[] = $connection_status;
      }
      if ($last_error != '') {
        $_errors[] = $last_error;
      }
      if ($result_error != '') {
        $_errors[] = $result_error;
      }
      if ($last_notice != '') {
        $_errors[] = $last_notice;
      }
      if (count($_errors) > 0) {
        if ($this->lastQuery == '') {
          $this->dbError = '<div style="' . $this->divStyle . '"><b>Error: </b>' . $last_error . "</div>";
        } else {
          $this->dbError = '<div style="' . $this->divStyle . '"><b>Query:</b> ' . $this->lastQuery . '<br />' . implode(
                  '<br />',
                  $_errors
              ) . "</div>";
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
    $sql = "SELECT table_name as name FROM INFORMATION_SCHEMA.tables WHERE table_schema = '{$schema}';";
    $result = $this->getRecordSet($sql);
    return $result;
  }
} // end of class db
?>
