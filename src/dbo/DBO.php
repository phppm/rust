<?php

namespace rust\dbo;

use PDO;
use PDOException;
use rust\dbo\exception\SQLExecuteException;

/**
 * DBO extends PDO
 */
class DBO extends PDO {
    private $_lastInsertId;
    private $_affectedRows;
    private $_statement = null;

    /**
     * DBO constructor.
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array  $options
     */
    public function __construct($dsn, $username, $password, $options = []) {
        $options = $options && is_array($options) ? $options : [];
        $options += [
            PDO::ATTR_STATEMENT_CLASS    => [
                '\\rust\\dbo\\Statement',
                [$this],
            ],
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        ];
        parent::__construct($dsn, $username, $password, $options);
    }

    /**
     * Prepare & execute query with params
     *
     * @param  array $sqlMap
     *
     * @return SQLExecuteResult
     * @throws SQLExecuteException
     */
    public function execute($sqlMap) {
        if ('w' === $sqlMap['rw']) {
            try{
                $this->_affectedRows = $this->exec($sqlMap['sql']);
            } catch (PDOException $e) {
                $err_info = $this->errorInfo();
                $msg = array_pop($err_info);
                $data = [
                    'driver_code'    => isset($err_info[1]) ? $err_info[1] : null,
                    'sql'            => $sqlMap,
                    'sql_state_code' => isset($err_info[0]) ? $err_info[0] : null,
                ];
                throw new SQLExecuteException($msg, $data);
            }
            if ('insert' === $sqlMap['sql_type']) {
                $this->_lastInsertId = $this->lastInsertId();
            }
        } else {
            $exec_result = null;
            $stmt = null;
            try {
                $sql = $sqlMap['sql'];
                $this->_statement = $this->prepare($sql);
                $exec_result = $this->_statement->execute();
                if (isset($sqlMap['result_model']) && $sqlMap['result_model']) {
                    $this->_statement->setFetchMode(PDO::FETCH_CLASS, $sqlMap['result_model']);
                } else {
                    $this->_statement->setFetchMode(PDO::FETCH_CLASS, 'stdClass');
                }
                //TODO:写入SQL日志
            } catch (PDOException $e) {
                //TODO:写入SQL异常
            }
            if (!$exec_result) {
                $err_info = $this->_statement->errorInfo();
                $msg = array_pop($err_info);
                $data = [
                    'driver_code'    => isset($err_info[1]) ? $err_info[1] : null,
                    'sql'            => $sqlMap,
                    'sql_state_code' => isset($err_info[0]) ? $err_info[0] : null,
                ];
                throw new SQLExecuteException($msg, $data);
            }
        }
        return new SQLExecuteResult($this);
    }

    public function getLastInsertId(): int {
        return $this->_lastInsertId;
    }

    public function getAffectedRows(): int {
        return $this->_affectedRows;
    }

    public function getStatement(): Statement {
        return $this->_statement;
    }
}
