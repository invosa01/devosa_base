<?php

/******************************************************************
 * CdbClass : kelas untuk mengakses basis data
 * Copyright (c) Invosa Systems, PT
 *
 * dbClass.php
 * Author:  Dedy Sukandar
 *
 * Ver   :  - 1.00, 2006-11-22
 *
 * 2015-09-01
 * uddin :
 * - tambah log to file dan log error yg muncul di layar di sembunyikan
 * - tambah beberapa method diambil dari dbo_postgres
 ***************************************************************/
class CdbClass
{

  var $dbCon;

  var $dbError;

  var $dbname = "";

  var $dbtype = "postgres";

  var $divStyle = "z-index: 999;position: fixed; right: 10px; top: 90px; border:1px solid black; margin:4px; padding:4px; background-color:#FFDEAD;";

  var $host = "localhost";

  var $isConnected;

  var $isShowError = true;

  var $lastQuery;

  //var $divStyle = "border:1px solid black; margin:4px; padding:4px; background-color:#FFDEAD;";

  var $password = "";

  var $res;

  var $strConn = "";

  var $user = "";

  //fungsi untuk konek ke database postgre
  //parameternya adalah gabungan string dari nama host,port,dbname,user, dan password

  function DB_Last_Insert_ID($table, $fieldname)
  {
    $tempres = $this->execute("SELECT currval('" . $table . "_" . $fieldname . "_seq') FROM " . $table);
    $Res = pg_fetch_result($tempres, 0, 0);
    $this->freeResult($tempres);
    return $Res;
  }

  function DB_Maintenance()
  {
    $this->setErrorMessage("Silahkan tunggu, database administrator sedang menjalankan proses optimasi database!");
    echo $this->dbError;
    $Result = $this->execute('VACUUM ANALYZE');
    $Result = $this->execute(
        "UPDATE config
          SET confvalue='" . Date('Y-m-d') . "'
          WHERE confname='DB_Maintenance_LastRun'"
    );
  }
  ///fungsi untuk mengeksekusi perintah SQL
  ///parameter yang diperlukan adalah nilai string dari SQL tersebut

  function DB_data_seek(&$ResultIndex, $Record)
  {
    pg_result_seek($ResultIndex, $Record);
  }

  //mengambil data dari tiap record
  //parameter yang diperlukan adalah hasil dari query

  function DB_error_msg()
  {
    return pg_last_error($this->dbCon);
  }

  //mengetahui jumlah record dari hasil query
  //parameter yan diperlukan adalah hasil dari query
  //hasil dari fungsi ini INTEGER

  function DB_error_no()
  {
    return DB_error_msg($this->dbCon) == "" ? 0 : -1;
  }

  function DB_escape_string($String)
  {
    return pg_escape_string($String);
  }

  function INTERVAL($val, $Inter)
  {
    return "\n(CAST( (" . $val . ") as text ) || ' " . $Inter . "')::interval\n";
  }

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

  function close()
  {
    $tutupKoneksi = pg_close($this->dbCon);
    $this->isConnected = false;
    return 0;
  }

  // fungsi untuk memgambil sequence ID yang berikutnya, berdasar anma sequence-nya

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
  } //getNextID

  function connect()
  {
    if (DEFINED('DB_SERVER')) {
      $this->host = DB_SERVER;
    }
    if (DEFINED('DB_NAME')) {
      $this->dbname = DB_NAME;
    }
    if (DEFINED('DB_USER')) {
      $this->user = DB_USER;
    }
    if (DEFINED('DB_PWD')) {
      $this->password = DB_PWD;
    }
    $this->lastQuery = '';
    if (is_callable('pg_connect')) {
      //$this->strConn = "host='".$this->host."' port=5432 dbname=".$this->dbname." user=".$this->user." password=".$this->password;
      if ($this->host == '') {
        // use unix domain socket
        $this->strConn = "dbname=" . $this->dbname . " user=" . $this->user . " password=" . $this->password;
      } else {
        $this->strConn = "host='" . $this->host . "' port=5432 dbname=" . $this->dbname . " user=" . $this->user . " password=" . $this->password;
      }
      $this->dbCon = @pg_connect($this->strConn);
      if (!$this->dbCon) {
        $this->dbError = '<div style="' . $this->divStyle . '"><strong>Error: </strong>Cannot connect to PostgreSQL server database</div>';
        //$this->dbError = '<div style="'.$this->divStyle.'"><strong>Error: </strong>Cannot connect to PostgreSQL server database:'.$this->host.' '.$this->dbname.' '.$this->user.' '.$this->password.' '.pg_last_error($this->dbCon ).'</div>';
        echo $this->dbError;
      } else {
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
  //
  // tambahan method dari dbo
  // uddin 20150901
  //fungsi untuk mengambil informasi kolom pada suatu table
  //output: array daftar columns

  function getLastID($offset = 0, $seq_suffix = 'seq')
  {
    $regs = [];
    preg_match("/insert\\s*into\\s*\"?(\\w*)\"?/i", $this->last_query, $regs);
    if (count($regs) > 1) {
      $table_name = $regs[1];
      $res = @pg_query($this->conn, "SELECT * FROM $table_name WHERE 1 != 1");
      $query_for_id = "SELECT CURRVAL('{$table_name}_" . @pg_field_name($res, $offset) . "_{$seq_suffix}'::regclass)";
      $result_for_id = @pg_query($this->conn, $query_for_id);
      $last_id = @pg_fetch_array($result_for_id, 0, PGSQL_NUM);
      return $last_id[0];
    }
    return null;
  }

function getNextID($strSeq = "")
  {
    $intResult = 0;
    if ($strSeq != "") {
      $strSQL = "SELECT nextval('\"$strSeq\"') AS id ";
      $res = $this->execute($strSQL);
      if ($row = $this->fetchrow($res)) {
        $intResult = $row['id'];
      }
    }
    return $intResult;
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
          //$this->dbError = '<div style="'.$this->divStyle.'"><b>Error: </b>' . $last_error . "</div>";
          $this->dbError = '<div style="' . $this->divStyle . '"><b>Error: </b>Contact Your administrator</div>';
          $this->writelogfile("Error:" . $last_error);
        } else {
          $this->dbError = "<div style='" . $this->divStyle . "'>";
          if (DEBUG_MODE == true) {
            $this->dbError .= '<b>Query:</b> ' . $this->lastQuery . '<br />' . implode('<br />', $_errors) . "<br>";
            $this->dbError .= '<b>Error Query:</b>' . implode('<br />', $_errors) . "<br>";
          }
          $this->dbError .= '<b> Error Query2:</b></div>';
          $this->writelogfile("Query:" . $this->lastQuery . " : " . implode('<br />', $_errors));
        }
      } else {
        $this->dbError = '';
      }
    } else {
      $this->dbError = '<div style="' . $this->divStyle . '"><b>Error: </b>' . $errMessage . '</div>';
      $this->writelogfile("Error:" . $errMessage);
    }
  }

  function writelogfile($errormsg)
  {
    //$file = $_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['REQUEST_URI']).'/invosa/base_devosa/log/devosa_dberror.log';
    $file = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/log/devosa_dberror.log';
    //echo $file;
    $content = date("Y-m-d, G:i:s") . "\n";
    $content .= $errormsg . "\n";
    //echo "tulis file:".$content;
    file_put_contents($file, $content, FILE_APPEND);
  }
} // end of class db
?>
