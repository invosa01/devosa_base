<?php

/*****************************************
 * CdbClass : kelas untuk mengakses basis data
 * Copyright (c) Invosa Systems, PT
 *
 * dbClass.php
 * Author:  Dedy Sukandar
 *
 * Ver   :  - 1.00, 2006-11-22
 ******************************************/
class CdbClass
{

    var $dbCon;

    var $dbError;

    var $dbname = "";

    var $dbtype = "MSSQL";

    var $divStyle = "border:1px solid black; margin:4px; padding:4px; background-color:#FFDEAD;";

    var $host = "localhost";

    var $lastQuery;

    var $password = "";

    var $user = "";

    //fungsi untuk konek ke database postgre
    //parameternya adalah gabungan string dari nama host,port,dbname,user, dan password

    function close()
    {
        $tutupKoneksi = mssql_close($dbCon);
        return 0;
    }

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
        if (is_callable('mssql_connect')) {
            $this->dbCon = @mssql_connect($this->host, $this->user, $this->password);
            if (!$this->dbCon) {
                $this->dbError = '<div style="' . $this->divStyle . '"><strong>Error: </strong>Cannot connect to SQL server database</div>';
                echo $this->dbError;
            } else {
                if (@mssql_select_db($this->dbname, $this->dbCon)) {
                    return true;
                } else {
                    $this->setErrorMessage();
                    echo $this->dbError;
                }
            }
        } else {
            $this->dbError = '<div style="' . $this->divStyle . '"><strong>Fatal error:</strong> Call to undefined function mssql_connect()<br />Please activated MS-SQL php extension</div>';
            echo $this->dbError;
        }
        return false;
    }

    ///fungsi untuk mengeksekusi perintah SQL
    ///parameter yang diperlukan adalah nilai string dari SQL tersebut

    function execute($stringSQL)
    {
        try {
            $this->lastQuery = $stringSQL;
            $temp = @mssql_query($stringSQL, $this->dbCon);
        } catch (Exception $e) {
            $this->setErrorMessage();
            echo $this->dbError;
            return false;
        }
        if ($temp) {
            return $temp;
        } else {
            $this->setErrorMessage();
            echo $this->dbError;
            return false;
        }
    }

    //mengambil data dari tiap record
    //parameter yang diperlukan adalah hasil dari query
    function fetchrow($res, $result_type = "BOTH")
    {
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
        $resFetch = @mssql_fetch_array($res, $result_type);
        if (!$resFetch) {
            $this->setErrorMessage();
            echo $this->dbError;
            return false;
        } else {
            return $resFetch;
        }
    }

    //mengetahui jumlah record dari hasil query
    //parameter yan diperlukan adalah hasil dari query
    //hasil dari fungsi ini INTEGER
    function numrows($res)
    {
        $intJmlBrs = mssql_num_rows($res);
        return $intJmlBrs;
    }

    function setErrorMessage()
    {
        $last_error = @mssql_error($this->dbCon);
        if ($last_error != "") {
            if ($this->lastQuery == '') {
                $this->dbError = '<div style="' . $this->divStyle . '"><b>Error: </b>' . $last_error . "</div>";
            } else {
                $this->dbError = '<div style="' . $this->divStyle . '"><b>Query:</b> ' . $this->lastQuery . '<br />' . $last_error . "</div>";
            }
        } else {
            $this->dbError = '';
        }
    }
} // end of class db
?>
