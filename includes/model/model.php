<?php
require_once("connection_manager.php");

class cModel
{

  var $DEBUGMODE = 0;

  var $REPOSTCHECKING = 1;

  var $db;

  var $name = null;

  var $strEntityName = "";

  var $strMessage = "";

  var $strTableName = null;

  var $tableInfo = null;

  //constructor

  function cModel($strTableName = null, $strEntityName = "")
  {
    if ($strTableName !== null) {
      $this->strTableName = $strTableName;
    }
    if ($strEntityName !== "") {
      $this->strEntityName = $strEntityName;
    }
    if (!isset($GLOBALS['dboDataSource'])) //$GLOBALS['dboDataSource'] = new CDboModel;
    {
      $GLOBALS['dboDataSource'] = new CdbClass;
    }
    $this->db = &$GLOBALS['dboDataSource'];
    if ($this->name === null) {
      $this->name = get_class($this);
    }
    if (!$this->db->connect()) {
      die("Connection to database failed");
    }
    $strConn = "host='" . $this->db->host . "' dbname=" . $this->db->dbname;
    if ($this->DEBUGMODE > 0) {
      $this->_debug("Connecting to: " . $strConn . " for table " . $this->strTableName);
    }
  }

  /**
   * Handles custom method calls, like findBy<field> for DB models,
   * and custom RPC calls for remote data sources
   *
   * @param unknown_type $method
   * @param array        $params
   *
   * @return unknown
   * @access protected
   */
  function __call($method, $params)
  {
    return $this->query($method, $params, $this);
  }

  /*parameter:
      $varCondition : can be (string) or (array)
      e.g : $varCondition = "id=3 AND name='dedy'" OR array("id => 3, "name" => 'dedy');
        return : integer
  */

  function _debug($message)
  {
    echo "<pre>" . $message . "</pre>";
  }

  /*parameter:
      $varCondition : can be (string) or (array)
      $varOrder : can be (string) or (array)
      $intLimit : must be (integer)
      $strKeyField : the name of field which will be use as option KEY
      $strValueField : the name of field which will be use as option VALUE
      e.g : $varCondition = "id=3" OR array("id => 3, "name" => 'dedy');
              $varOrder = "ORDER BY code" OR array("code" => "ASC", "name" => "DESC");
              $intLimit = (integer"ORDER BY code" OR array("code" => "ASC", "name" => "DESC");

        return : array
      */

  function _formatData(
      $arrData,
      $isStrict = true, /*true = replace to null if empty*/
      $isReplaceToNULL = true, /*format field*/
      $formatField = true,
      $stringPrefix = "'"
  ) {
    $this->getTableInfo();
    $arrResult = [];
    foreach ($arrData as $key => $val) {
      $isFound = false;
      foreach ($this->tableInfo as $col) {
        if ($col['name'] == $key) {
          $isFound = true;
          break;
        }
      }
      if (!$isFound) {
        if ($isStrict) {
          continue;
        }
      }
      if ($val === null) {
        $val = "null";
      } else {
        if (isset($this->tableInfo[$key])) {
          if (isset($this->db->columns[$this->tableInfo[$key]['type']]['formatter'])) {
            $formatter = $this->db->columns[$this->tableInfo[$key]['type']]['formatter'];
          } else {
            $formatter = null;
          }
          switch ($this->tableInfo[$key]['type']) {
            case "integer" :
              if ($formatter == null) {
                $formatter = "intval";
              }
            case "float" :
              if ($formatter == null) {
                $formatter = "floatval";
              }
              if ($val === "" || $val === null) {
                if ($this->tableInfo[$key]['null'] && $isReplaceToNULL) {
                  $val = "null";
                } else {
                  $val = 0;
                }
              } else {
                $val = $formatter($val);
              }
              break;
            case "date" :
            case "datetime" :
              if ($formatter == null) {
                $formatter = "date";
              }
              if ($val == "") {
                if ($this->tableInfo[$key]['null'] && $isReplaceToNULL) {
                  $val = "null";
                } else {
                  $val = $stringPrefix . $formatter(
                          $this->db->columns[$this->tableInfo[$key]['type']]['format']
                      ) . $stringPrefix;
                }
              } else {
                $val = $stringPrefix . $val . $stringPrefix;
              }
              break;
            case "string" :
              //print_r($val);
              $val = $stringPrefix . $this->db->escape_string($val) . $stringPrefix;
              break;
            default :
              if ($formatter !== null) {
                $val = $formatter($val);
              } else {
                $val = $stringPrefix . $val . $stringPrefix;
              }
          }
        } else {
          $val = $stringPrefix . $val . $stringPrefix;
        }
      }
      if ($formatField) //will format field according to DB, e.g: Postgres = "fieldname", in MySQL = `fieldname`, in MSSQL = [fieldname]
      {
        $arrResult[$this->db->formatFieldName($key)] = $val;
      } else {
        $arrResult[$key] = $val;
      }
    }
    return $arrResult;
  }

  function _generateVariableSession()
  {
    //get Nama Cookies sesuai dengan nama file PHP yang memanggil
    $strPages = split("/", $_SERVER['PHP_SELF']);
    //buang karakter yang tidak valid sebagai nama variable
    $strPages = preg_replace("/[^a-z]/i", '', $strPages);
    //default jika terjadi kesalahan pengambilan adalah untitled
    $pageName = "untitled";
    if (count($strPages) > 0) {
      $pageNamePHP = $strPages[count($strPages) - 1];
      $pageNames = split("\.", $pageNamePHP);
      $pageName = $pageNames[0];
    }
    $namaVariable = 'session_' . $pageName;
    return $namaVariable;
  }

  /*parameter:
    $varCondition : can be (string) or (array)
    $varFields : can be (string) or (array) or for all selection(null) or ( "*")
    $varOrder : can be (string) or (array)
    e.g :  $varCondition = "id=3" OR array("id => 3, "name" => 'dedy');
            $varFields = array("id, "name");
            $varOrder = "ORDER BY code" OR array("code" => "ASC", "name" => "DESC");

      return : array
    */

  function _getDataSession()
  {
    $namaVariable = $this->_generateVariableSession();
    (isset($_SESSION[$namaVariable])) ? $currentSessionData = $_SESSION[$namaVariable] : $currentSessionData = null;
    return $currentSessionData;
  }

  /*parameter:
    $varCondition : can be (string) or (array)
    $varFields : can be (string) or (array) or for all selection(null) or ( "*")
    $varOrder : can be (string) or (array)
    e.g : $varCondition = "id=3" OR array("id => 3, "name" => 'dedy');
            $varFields = array("id, "name");
            $varOrder = "ORDER BY code" OR array("code" => "ASC", "name" => "DESC");

      return : single array = 1 record
    */

  function _isRepostData($passData)
  {
    $currentSessionData = $this->_getDataSession();
    if ($currentSessionData === null) {
      return false;
    } else {
      return ($currentSessionData == md5($passData));
    }
  }

  function _serializeCondition($varAttribute)
  {
    $strAttribute = "";
    if ($varAttribute != null) {
      if (is_array($varAttribute)) {
        $arrResult = [];
        if (isset($varAttribute['OR'])) {
          //OR CRITERIA
          $varAttribute['OR'] = $this->_formatData($varAttribute['OR'], false);
          foreach ($varAttribute['OR'] as $key => $value) {
            $arrResult[] = $key . " = " . $value;
          }
          $strAttribute = implode(" OR ", $arrResult);
        } else {
          $varAttribute = $this->_formatData($varAttribute, false);
          foreach ($varAttribute as $key => $value) {
            $arrResult[] = $key . " = " . $value;
          }
          $strAttribute = implode(" AND ", $arrResult);
        }
        $strAttribute = "WHERE " . $strAttribute;
      } else {
        $strAttribute = trim($varAttribute);
        $pos = strpos(strtoupper($strAttribute), "WHERE ");
        if ($pos !== false && $pos === 0) {
          $strAttribute = substr($strAttribute, 6);
        }
        $pos = strpos($strAttribute, "AND");
        if ($pos === 0) {
          $strAttribute = substr($strAttribute, 3);
        }
        if ($strAttribute != "") {
          $strAttribute = "WHERE " . $strAttribute;
        }
      }
    }
    return $strAttribute;
  }

  function _serializeFields($varAttribute)
  {
    $strAttribute = "";
    if ($varAttribute != null) {
      if (is_array($varAttribute)) {
        $arrResult = [];
        foreach ($varAttribute as $value) {
          $arrResult[] = $value;
        }
        $strAttribute = implode(", ", $arrResult);
      } else {
        $strAttribute = $varAttribute;
      }
    } else {
      $strAttribute = "*";
    }
    return $strAttribute;
  }

  function _serializeFieldsAndValue($varAttribute, $strConcat = ", ")
  {
    $strAttribute = "";
    if ($varAttribute != null) {
      if (is_array($varAttribute)) {
        $arrResult = [];
        $varAttribute = $this->_formatData($varAttribute);
        foreach ($varAttribute as $key => $value) {
          $arrResult[] = $key . " = " . $value;
        }
        $strAttribute = implode($strConcat, $arrResult);
      } else {
        $strAttribute = $varAttribute;
      }
    }
    return $strAttribute;
  }

  function _serializeOrderBy($varAttribute)
  {
    $strAttribute = "";
    if ($varAttribute != null) {
      if (is_array($varAttribute)) {
        $arrResult = [];
        foreach ($varAttribute as $key => $value) {
          $value = strtoupper(trim($value));
          if (($value != "ASC") || ($value != "DESC")) {
            $value = "";
          }
          $arrResult[] = " " . $key . " " . $value;
        }
        $strAttribute = implode(", ", $arrResult);
        $strAttribute = "ORDER BY " . $strAttribute;
      } else {
        $strAttribute = str_replace("ORDER BY", "", $varAttribute);
        if ($strAttribute != "") {
          $strAttribute = "ORDER BY " . $strAttribute;
        }
      }
    }
    return $strAttribute;
  }

  function _setDataSession($currentSessionData)
  {
    if (!session_id()) {
      session_start();
    }
    $namaVariable = $this->_generateVariableSession();
    if ($currentSessionData == null || $currentSessionData == "") {
      unset($_SESSION[$namaVariable]);
    } else {
      $currentSessionData = md5($currentSessionData);
      $_SESSION[$namaVariable] = $currentSessionData;
    }
  }

  function _underscore($camelCasedWord)
  {
    $replace = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
    return $replace;
  }

  //format data type values to be check with table column information

  function begin()
  {
    $this->db->begin();
    if ($this->DEBUGMODE > 0) {
      $this->_debug("BEGIN;");
    }
  }

  /*parameter:
    $varData : must be (array)
    e.g $varData : array("id" => 7, "name" => "dedy")
  */

  function commit()
  {
    $this->db->commit();
    if ($this->DEBUGMODE > 0) {
      $this->_debug("COMMIT;");
    }
  }

  /*parameter:
    $varKey = can be (array) or (string) condition
    $varData : can be (string) or (array)
    e.g $varKey : array("id" => 7, "name" => "dedy") equal to "WHERE id=7 AND name='dedy'";
          $varData : array("id" => 7, "name" => "dedy") equal to "id=7, name='dedy'";
  */

  function delete($varKey)
  {
    $strCriteria = $this->_serializeCondition($varKey);
    $strSQL = "DELETE FROM " . $this->db->formatTableName($this->strTableName) . " \n";
    $strSQL .= $strCriteria . ";";
    //execute save SQL query syntax, and get message to $f->message
    return $this->executeDeleteSQL($strSQL, $this->strEntityName, $this->strMessage);
  }

  /*parameter:
    $varKey = can be (array) or (string) condition
    e.g for array : array("id" => 7, "name" => "dedy") equal to "WHERE id=7 AND name='dedy'";
  */

  function deleteMultiple($arrKeys)
  {
    if (count($arrKeys) == 0) {
      return false;
    }
    $strCriteria = "";
    foreach ($arrKeys as $key => $val) {
      if (is_array($val)) {
        $strValues = "";
        foreach ($val as $data) {
          if ($strValues == "") {
            $strValues = "'" . $data . "'";
          } else {
            $strValues .= ", '" . $data . "'";
          }
        }
        $strValues = "(" . $strValues . ")";
        if ($strCriteria == "") {
          $strCriteria .= " " . $key . " IN " . $strValues;
        } else {
          $strCriteria .= " AND " . $key . " IN " . $strValues;
        }
      }
    }
    if ($strCriteria != "") {
      $strCriteria = " WHERE " . $strCriteria;
    }
    $strSQL = "
        DELETE FROM " . $this->db->formatTableName($this->strTableName) . " " . $strCriteria . ";";
    writeLog(ACTIVITY_DELETE, MODULE_EMPLOYEE, $strCriteria, 0);
    //execute save SQL query syntax, and get message to $f->message
    return $this->executeDeleteSQL($strSQL, $this->strEntityName, $this->strMessage);
  }

  /*$arrKey = array("pk" => array(......) ) */

  function execute($strSQL)
  {
    if ($this->DEBUGMODE > 0) {
      $this->_debug($strSQL);
    }
    return ($res = $this->db->execute($strSQL));
  }

  function executeDeleteSQL($strSQL, $strEntityName = null, &$strMessage, $closingDbOnExit = false)
  {
    global $messages;
    if ($strEntityName == null) {
      $strEntityName = $this->strEntityName;
    }
    $isDeleted = false;
    $strMessage = "";
    if ($strSQL != '') {
      if ($this->db->connect()) {
        if ($this->DEBUGMODE > 0) {
          $this->_debug($strSQL);
        }
        if ($res = $this->db->execute($strSQL)) {
          $isDeleted = ($this->db->affectedRows() != 0);
          if ($isDeleted) {
            $strMessage = $messages['data_deleted'];
          } else {
            $strMessage = $messages['process_failed'];
          }
        } else {
          if (function_exists('getWords')) {
            $strMessage = getWords("process failed");
          } else {
            $strMessage = $messages['process_failed'];
          }
        }
        if ($closingDbOnExit) {
          $this->db->close();
        }
      }
    }
    //penting untuk menghilangkan cookie dari last action save karena sekarang last actionnya adalah delete.
    //sebenarnya bisa juga dilakukan pengecekan seperti saat save untuk menghindari multiple DELETION,
    //tetapi karena untuk DELETION tidak akan terjadi perulangan maka tidak perlu, cukup kosongkan cookie sebelumnya saja
    $this->_setDataSession(null);
    return $isDeleted;
  }

  function executeNormalSQL($strSQL, $closingDbOnExit = false)
  {
    if ($strEntityName == null) {
      $strEntityName = $this->strEntityName;
    }
    $result = false;
    if ($strSQL != '') {
      if ($this->db->connect()) {
        if ($this->DEBUGMODE > 0) {
          $this->_debug($strSQL);
        }
        $result = $this->db->execute($strSQL);
        if ($closingDbOnExit) {
          $this->db->close();
        }
      }
    }
    //echo $strSQL;
    return $result;
  }

  function executeSaveSQL($strSQL, $strEntityName, &$strMessage, $closingDbOnExit = false)
  {
    global $messages;
    if ($strEntityName == null) {
      $strEntityName = $this->strEntityName;
    }
    $strMessage = "";
    $isSaved = false;
    if ($this->REPOSTCHECKING) {
      if ($this->_isRepostData($strSQL)) {
        //previous data already save because is still in the cookie with the precies same value
        if ($this->DEBUGMODE > 0) {
          $this->_debug($strSQL . "(CACHED)");
        }
        if (function_exists('getWords')) {
          $strMessage = $messages['data_saved'];
        } else {
          $strMessage = $messages['data_saved'];
        }
        $isSaved = true;
        return $isSaved;
      }
    }
    if ($strSQL != "") {
      if ($this->db->connect()) {
        // cek validasi -----------------------
        if ($this->DEBUGMODE > 0) {
          $this->_debug($strSQL);
        }
        if ($res = $this->db->execute($strSQL)) {
          $isSaved = ($this->db->affectedRows() != 0);
          if ($isSaved) {
            $strMessage = $messages['data_saved'];
            $this->_setDataSession($strSQL);
          } else {
            $strMessage = $messages['process_failed'];
          }
        } else {
          if (function_exists('getWords')) {
            $strMessage = getWords("process failed");
          } else {
            $strMessage = $messages['process_failed'];
          }
        }
        if ($closingDbOnExit) {
          $this->db->close();
        }
      } else {
        if (function_exists('getWords')) {
          $strMessage = getWords("connection failed");
        } else {
          $strMessage = $messages['connection_failed'];
        }
        $isSaved = false;
      }
    }
    return $isSaved;
  }

  function fetchAll($strSQL)
  {
    if ($this->DEBUGMODE > 0) {
      $this->_debug($strSQL);
    }
    if ($res = $this->db->execute($strSQL)) {
      $out = [];
      while ($item = $this->db->fetchrow($res)) {
        $out[] = $item;
      }
      return $out;
    } else {
      return false;
    }
  }

  //panggil fungsi ini setelah proses save di server
  //pass value dengan data yang unik

  /**
   * Returns contents of a field in a query matching given conditions.
   *
   * @param string $name Name of field to get
   * @param array  $varCondition SQL conditions (defaults to NULL)
   * @param string $varOrder SQL ORDER BY fragment
   *
   * @return field contents
   * @access public
   */
  function field($name, $varCondition = null, $varOrder = null)
  {
    $strCondition = $this->_serializeCondition($varCondition);
    $strOrder = $this->_serializeOrderBy($varOrder);
    if ($data = $this->find($strCondition, $name, $strOrder)) {
      return $data[$name];
    } else {
      return null;
    }
  }

  function find($varCondition = "", $varField = null, $varOrder = "")
  {
    $arrResult = $this->findAll($varCondition, $varField, $varOrder, 1);
    if ($arrResult != null && count($arrResult) == 1) {
      return $arrResult[0];
    } else {
      return null;
    }
  }


  //passData here to check
  //apakah data yang sama dikirim ke server?
  //compare data cookie (yang sebelumnya sudah disimpan pada fungsi setData)  dengan passData
  //jika sama return true berarti data report, yang nantinya ngga usah disimpan

  function findAll(
      $varCondition = "",
      $varField = null,
      $varOrder = "",
      $intLimit = null,
      $page = 1,
      $autoIndexField = null /*set with field name */
  )
  {
    $strCondition = $this->_serializeCondition($varCondition);
    $strOrder = $this->_serializeOrderBy($varOrder);
    $strFields = $this->_serializeFields($varField);
    $arrResult = $this->db->fetchAll(
        $this->strTableName,
        $strCondition,
        $strFields,
        $strOrder,
        $intLimit,
        $page,
        $autoIndexField,
        $strSQL
    );
    if ($this->DEBUGMODE > 0) {
      $this->_debug($strSQL);
    }
    return $arrResult;
  }

  /*parameter:
    $varAttribute : can be (string) or (array)
    e.g : $varAttribute = "id=3" OR array("id => 3, "name" => 'dedy');
    */

  function findCount($varCondition = null)
  {
    $strCondition = $this->_serializeCondition($varCondition);
    $strSQL = "SELECT COUNT(*) AS total FROM " . $this->db->formatTableName($this->strTableName) . " " . $strCondition;
    if ($this->DEBUGMODE > 0) {
      $this->_debug($strSQL);
    }
    $res = $this->db->execute($strSQL);
    if ($rowDb = $this->db->fetchrow($res)) {
      return intval($rowDb['total']);
    }
    return 0;
  }

  /*parameter:
    $varAttribute : can be (string) or (array)
    e.g : $varAttribute = "id, name DESC" OR "ORDER BY id, name DESC" OR array("id => "ASC", "name" => "DESC");
    */

  function generateList(
      $varCondition = null,
      $varOrder = "",
      $intLimit = null,
      $strKeyField,
      $strValueField,
      $isAddEmpty = false,
      $emptyData = null,
      $strDistinct = ""
  ) {
    $strCondition = $this->_serializeCondition($varCondition);
    $strOrder = $this->_serializeOrderBy($varOrder);
    $intLimit = intval($intLimit);
    if ($intLimit == null || $intLimit == 0) {
      $strLimit = "";
    } else {
      $strLimit = "LIMIT " . $intLimit;
    }
    if (is_array($strValueField)) {
      $strValueField2 = implode(", ", $strValueField);
    } else {
      $strValueField2 = $strValueField;
    }
    $strSQL = "SELECT " . $strDistinct . " " . $strKeyField . ", " . $strValueField2 . " FROM " . $this->strTableName . " " . $strCondition . " " . $strOrder . " " . $strLimit;
    if ($this->DEBUGMODE > 0) {
      $this->_debug($strSQL);
    }
    $res = $this->db->execute($strSQL);
    $arrResult = [];
    if ($isAddEmpty) {
      if (is_array($emptyData)) {
        if (isset($emptyData['value'])) {
          $val = $emptyData['value'];
        } else {
          $val = "";
        }
        if (isset($emptyData['text'])) {
          $text = $emptyData['text'];
        } else {
          $text = "";
        }
        $arrResult[] = ["value" => $val, "text" => $text];
      } else {
        $arrResult[] = ["value" => "", "text" => ""];
      }
    }
    while ($rowDb = $this->db->fetchrow($res)) {
      if (is_array($strValueField)) {
        $text = [];
        foreach ($strValueField as $val) {
          $text[] = $rowDb[$val];
        }
        $strText = implode(" - ", $text);
        $arrResult[] = ["value" => $rowDb[$strKeyField], "text" => $strText];
      } else {
        $arrResult[] = ["value" => $rowDb[$strKeyField], "text" => $rowDb[$strValueField]];
      }
    }
    return $arrResult;
  }

  /*parameter:
    $varAttribute : can be (array)  or (string)
    e.g : $varAttribute = null, "*", "id, name, code", or array("id", "name");
    */

  function getEmptyRecord()
  {
    $this->getTableInfo();
    foreach ($this->tableInfo as $key => $val) //read column and make empty
    {
      $arrData[$key] = "";
    }
    return $this->_formatData($arrData, true, false, false, "" /*remove string*/);
  }

  /*parameter:
    $varAttribute : (array)
    e.g : $varAttribute = array("id => "3", "name" => "dedy");
    */

  function getLastInsertId($field = 'id')
  {
    $intResult = $this->db->lastInsertId($this->strTableName, $field);
    if ($this->DEBUGMODE > 0) {
      $this->_debug("Last {" . $this->strTableName . "} inserted ID {" . $field . "} : " . $intResult);
    }
    return $intResult;
  }

  function getTableInfo()
  {
    if (!$this->tableInfo) {
      $this->tableInfo = $this->db->describe($this->strTableName);
    }
    return $this->tableInfo;
  }

  function insert($arrData)
  {
    if (!is_array($arrData)) {
      die("insertting to table " . $this->strTableName . " must use array data type");
    }
    if (isset($this->db->columns['datetime']['formatter']) && isset($this->db->columns['datetime']['format'])) {
      $formatter = $this->db->columns['datetime']['formatter'];
      $arrData['created'] = $formatter($this->db->columns['datetime']['format']);
    }
    $arrData['created_by'] = (isset($_SESSION['sessionUserID'])) ? $_SESSION['sessionUserID'] : "";
    $arrData = $this->_formatData($arrData);
    $strColumns = implode(", ", array_keys($arrData));
    $strValues = implode(", ", array_values($arrData));
    $strSQL = "INSERT INTO " . $this->db->formatTableName($this->strTableName) . " \n";
    $strSQL .= "( " . $strColumns . " )\n";
    $strSQL .= "VALUES \n";
    $strSQL .= "( " . $strValues . " ) ;";
    //execute save SQL query syntax, and get message to $f->message
    return $this->executeSaveSQL($strSQL, $this->strEntityName, $this->strMessage);
  }

  function query()
  {
    $args = func_get_args();
    $fields = null;
    $order = null;
    $limit = null;
    $page = null;
    $auto_indexing = null;
    $page = null;
    $recursive = null;
    if (count($args) == 1) {
      //single query syntax
      return $this->fetchAll($args[0]);
    } else if (count($args) > 1 && (strpos(strtolower($args[0]), 'findby') === 0 || strpos(
                strtolower($args[0]),
                'findallby'
            ) === 0)
    ) {
      $params = $args[1];
      if (strpos(strtolower($args[0]), 'findby') === 0) {
        $all = false;
        $field = $this->_underscore(preg_replace('/findBy/i', '', $args[0]));
      } else {
        $all = true;
        $field = $this->_underscore(preg_replace('/findAllBy/i', '', $args[0]));
      }
      $or = (strpos($field, '_or_') !== false);
      if ($or) {
        $field = explode('_or_', $field);
      } else {
        $field = explode('_and_', $field);
      }
      $off = count($field) - 1;
      if (isset($params[1 + $off])) {
        $fields = $params[1 + $off];
      }
      if (isset($params[2 + $off])) {
        $order = $params[2 + $off];
      }
      if (!array_key_exists(0, $params)) {
        return false;
      }
      $c = 0;
      $query = [];
      foreach ($field as $f) {
        if (!is_array($params[$c]) && !empty($params[$c]) && $params[$c] !== true && $params[$c] !== false) {
          $query[/*$args[2]->name . '.' . */
          $f] = /*'= ' . */
              $params[$c];
        } else {
          $query[/*$args[2]->name . '.' . */
          $f] = $params[$c];
        }
        $c++;
      }
      if ($or) {
        $query = ['OR' => $query];
      }
      if ($all) {
        if (isset($params[3 + $off])) {
          $limit = $params[3 + $off];
        }
        if (isset($params[4 + $off])) {
          $page = $params[4 + $off];
        }
        if (isset($params[5 + $off])) {
          $auto_indexing = $params[5 + $off];
        }
        return $args[2]->findAll($query, $fields, $order, $limit, $page, $auto_indexing /*, $page, $recursive*/);
      } else {
        /*if (isset($params[3 + $off])) {
            $recursive = $params[3 + $off];
        }*/
        return $args[2]->find($query, $fields, $order /*, $recursive*/);
      }
    } else {
      /*if (isset($args[1]) && $args[1] === true)
{
          return $this->fetchAll($args[0], true);
      }*/
      return $this->fetchAll($args[0] /*, false*/);
    }
  }

  function rollback()
  {
    $this->db->rollback();
    if ($this->DEBUGMODE > 0) {
      $this->_debug("ROLLBACK;");
    }
  }

  function update($varKey, $varData)
  {
    $strCriteria = $this->_serializeCondition($varKey);
    if (isset($this->db->columns['datetime']['formatter']) && isset($this->db->columns['datetime']['format'])) {
      $formatter = $this->db->columns['datetime']['formatter'];
      $strModified = $formatter($this->db->columns['datetime']['format']);
    }
    if (is_array($varData)) {
      $varData['modified'] = $strModified;
      $varData['modified_by'] = (isset($_SESSION['sessionUserID'])) ? $_SESSION['sessionUserID'] : "";
    } else {
      $varData .= ", modified='" . $strModified . "'";
      if (isset($_SESSION['sessionUserID'])) {
        $varData .= ", modified_by = " . intval($_SESSION['sessionUserID']);
      }
    }
    $strFieldsAndValue = $this->_serializeFieldsAndValue($varData, ", ");
    $strSQL = "UPDATE " . $this->db->formatTableName($this->strTableName) . " \n";
    $strSQL .= "SET " . $strFieldsAndValue . " \n";
    $strSQL .= $strCriteria . ";";
    //execute save SQL query syntax, and get message to $f->message
    return $this->executeSaveSQL($strSQL, $this->strEntityName, $this->strMessage);
  }
}

?>
