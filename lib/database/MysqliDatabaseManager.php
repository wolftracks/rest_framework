<?php
/*
 * By installing or using this file, you are confirming on behalf of the entity
 * subscribed to the SugarCRM Inc. product ("Company") that Company is bound by
 * the SugarCRM Inc. Master Subscription Agreement ("MSA"), which is viewable at:
 * http://www.sugarcrm.com/master-subscription-agreement
 *
 * If Company is not bound by the MSA, then by installing or using this file
 * you are agreeing unconditionally that Company will be bound by the MSA and
 * certifying that you have authority to bind Company accordingly.
 *
 * Copyright  2004-2013 SugarCRM Inc.  All rights reserved.
 */

class MysqliDatabaseManager extends DBManager
{
    public $dbConfig;
    protected $mysqli;
    protected $preparedStatements = array(false);

    /**
     * @param $dbConfig Database Configuration
     */
    function __construct($dbConfig = null)
    {
        if ($dbConfig) {
            $this->dbConfig = $dbConfig;
        } else {
            $this->dbConfig = SugarConfig::getDatabaseConfiguration();
        }
    }

    /**
     * @return bool
     * @throws DatabaseException
     */
    public function connect()
    {
        if (!$this->mysqli) {
            $dbConfig = $this->dbConfig;
            // $host = "p:" . $dbConfig['dbhost'];  // Persistent Connections are Disabled By Default
            $host = $dbConfig['dbhost'];
            $this->mysqli = new mysqli($host, $dbConfig['dbuser'], $dbConfig['dbpassword'], $dbConfig['dbname']);
            if ($this->mysqli->connect_errno) {
                $logMessage = "Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error;
                Log::fatal("MysqliDatabaseManager::connect(): " . $logMessage);
                $msg = "Database Connection Failure";
                throw new DatabaseException($msg, DatabaseException::ConnectionFailed);
            }
            // Log::info("MysqliDatabaseManager::connect(): Connected To Database:" . $dbConfig['dbname']);
        }
        return true;
    }

    /**
     * @param $index
     * @return bool
     */
    public function closeStatement($index)
    {
        if ($index >= 0 && $index < count($this->preparedStatements)) {
            $stmt = $this->preparedStatements[$index];
            if ($stmt !== false) {
                $stmt->close();
                $this->preparedStatements[$index] = false;
            }
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function close()
    {
        for ($i = 0; $i < count($this->preparedStatements); $i++) {
            if ($this->preparedStatements[$i] !== false) {
                $stmt = $this->preparedStatements[$i];
                $stmt->close();
            }
        }
        $this->preparedStatements = array();
        if ($this->mysqli) {
            $this->mysqli->close();
            $this->mysqli = null;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function freeQueryResult(&$result)
    {
        if ($result) {
            $result->free();
            $result = null;
        }
        return true;
    }

    /**
     * Parse and Execute SQL Query
     * @param $sql
     * @return $result or bool depending on query
     */
    public function query($sql)
    {
        $result = $this->mysqli->query($sql);
        if ($result && $this->getErrno() === 0) {
            return $result;
        }
        if ($this->getErrno() !== 0) {
            $msg = "Database - query failed: " . $sql;
            Log::error($msg . ": " . $this->getLastError());
            throw new DatabaseException($msg, DatabaseException::QueryFailed);
        }
        return false;
    }

    /**
     * Fetch Row from Result Set
     * @param $result Result Set returned by Query()
     * @return $row
     */
    public function fetchAssoc($result)
    {
        return $this->fetchArray($result);
    }

    /**
     * Fetch Row from Result Set
     * @param $result Result Set returned by Query()
     * @return $row
     */
    public function fetchArray($result, $arrayType=MYSQLI_ASSOC)
    {
        $row = $result->fetch_array($arrayType);
        return $row;
    }

    /**
     * Lock One or More Table
     * @return mixed  resource
     */
    public function lockTables($tableLocks)
    {
        $sql = "LOCK TABLES ";
        $first = true;
        foreach ($tableLocks AS $table => $lockType) {
            if ($first) {
                $first = false;
            } else {
                $sql .= ", ";
            }
            $sql .= "$table $lockType";
        }
        return $this->mysqli->query($sql);
    }

    /**
     * Unlock All Tables
     * @return mixed  resource
     */
    public function unlockTables()
    {
        return $this->mysqli->query("UNLOCK TABLES");
    }

    /**
     * @param $sql
     * @return int
     * @throws DatabaseException
     */
    public function prepare($sql)
    {
        $stmt = $this->mysqli->prepare($sql);
        if ($stmt) {
            $this->preparedStatements[] = $stmt;
            return count($this->preparedStatements) - 1;
        }
        $msg = "Database - prepare failed";
        Log::error($msg . ": " . $this->getLastError());
        throw new DatabaseException($msg, DatabaseException::PrepareFailed);
    }

    /**
     * @param $index
     * @return bool
     */
    public function getStatement($index)
    {
        if ($index >= 0 && $index < count($this->preparedStatements)) {
            return $this->preparedStatements[$index]; // can be false - if already closed
        }
        return false;
    }

    /**
     * @param int $statementIndex
     * @return bool
     * @throws DatabaseException
     */
    public function execute($statementIndex)
    {
        $stmt = $this->getStatement($statementIndex);
        if ($stmt === false) {
            $msg = "Database - execute - invalid StatementIndex: $statementIndex";
            Log::error($msg);
            throw new DatabaseException($msg, DatabaseException::StatementInvalid);
        }
        $res_exec = $stmt->execute();
        if ($res_exec) {
            $res_store = $stmt->store_result();
            if (!$res_store) {
                $msg = "Database - execute - unable to store_result";
                Log::error($msg . ": " . $this->getLastError());
                throw new DatabaseException($msg, DatabaseException::ExecuteFailed);
            }
        }
        if ($this->getErrno() !== 0) {
            //$msg = "Database - execute failed";
            //$logmsg = $msg . ": " . $this->getLastError();
            //Log::error($logmsg);
            return false;
        }
        return !!$res_exec;
    }

    /**
     * @param $columns
     * @return string
     */
    public function getSelectSet(&$columns)
    {
        $select_array = array();
        foreach ($columns AS $col) {
            $select_array[] = "`" . $col . "`";
        }
        $select_set = implode(',', $select_array);
        return $select_set;
    }

    /**
     * @param int $statementIndex
     * @param string $template
     * @param array $values
     * @return bool
     * @throws DatabaseException
     */
    public function bindParameters($statementIndex, $template, array $values)
    {
        $stmt = $this->getStatement($statementIndex);
        if ($stmt === false) {
            $msg = "Database - bindParameters failed - invalid StatementIndex: $statementIndex";
            Log::error($msg);
            throw new DatabaseException($msg, DatabaseException::StatementInvalid);
        }
        $params = array(); // Parameter array passed to 'bind_result()'
        $params[] =& $template;
        for ($i = 0; $i < count($values); $i++) {
            // Assign the fetched value to the variable '$result[$name]'
            $params[] =& $values[$i];
        }
        // print_r($params);
        $res = call_user_func_array(array($stmt, "bind_param"), $params);
        if (!$res) {
            $msg = "Database - bindParameters failed - bind_param";
            Log::error($msg . ": " . $this->getLastError());
            throw new DatabaseException($msg, DatabaseException::BindParametersFailed);
        }
        return true;
    }

    /**
     * @param int $statementIndex
     * @param array $columns
     * @return array database row
     * @throws DatabaseException
     */
    public function fetchResult($statementIndex, &$columns)
    {
        $stmt = $this->getStatement($statementIndex);
        if ($stmt === false) {
            $msg = "Database - fetchResult failed - invalid StatementIndex: $statementIndex";
            Log::error($msg);
            throw new DatabaseException($msg, DatabaseException::StatementInvalid);
        }
        $result = array(); // Array that accepts the data.
        $params = array(); // Parameter array passed to 'bind_result()'
        foreach ($columns as $col_name) {
            // Assign the fetched value to the variable '$result[$name]'
            $params[] =& $result[$col_name];
        }
        $res = call_user_func_array(array($stmt, "bind_result"), $params);
        if ($res && ($stmt->fetch())) {
            return $result;
        }
        return false;
    }

    /**
     * Returns the next value for an auto increment
     *
     * @param  int $statementIndex
     * @return int affected rows
     */
    public function affectedRows($statementIndex)
    {
        $stmt = $this->getStatement($statementIndex);
        if ($stmt === false) {
            $msg = "Database - affectedRows failed - invalid StatementIndex: $statementIndex";
            Log::error($msg);
        }
        return $stmt->affected_rows;
    }

    /**
     * Returns the next value for an auto increment
     *
     * @param  string $table tablename
     * @param  string $field_name
     * @return string
     */
    public function getAutoIncrement($table, $field_name='')
    {
        $result = $this->query("SHOW TABLE STATUS LIKE '$table'");
        $row = $this->fetchAssoc($result);
        $this->freeQueryResult($result);

        if (!empty($row['Auto_increment'])) {
            return $row['Auto_increment'];
        }
        return "";
    }


    /**
     * Get Last Error (Error Number and Error Message)
     * @return string  last errno:error
     */
    public function getLastError()
    {
        return(sprintf("[%d] %s",$this->mysqli->errno,$this->mysqli->error));
    }

    /**
     * Get SQL Error Number from last SQL Execute/Query
     * @return int errno
     */
    public function getErrno()
    {
        return $this->mysqli->errno;
    }
}
