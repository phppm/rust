<?php
namespace rust\dbo;

use PDO;
use PDOException;
use PDOStatement;
use rust\dbo\exception\SQLExecuteException;

/**
 * DBO extends PDO
 */
class DBO extends PDO {
    private $_lastInsertId;
    private $_affectedRows;
    /**
     * @var Statement $_statement
     */
    private $_statement=null;
    /**
     * @var string $dsn
     */
    private $dsn;
    /**
     * @var string $username
     */
    private $username;
    /**
     * @var string $password
     */
    private $password;
    /**
     * @var array $options
     */
    private $options;

    /**
     * DBO constructor.
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     */
    public function __construct($dsn, $username, $password, $options=[]) {
        $this->dsn=$dsn;
        $this->username=$username;
        $this->password=$password;
        $options=$options && is_array($options) ? $options : [];
        $options+=[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            //PDO::ATTR_PERSISTENT        =>true,
            PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES utf8',
        ];
        $this->options=$options;
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
            try {
                $this->_affectedRows=$this->exec($sqlMap['sql'] . ';');
            } catch(PDOException $e) {
                if ($e->getCode() === 2006 || false !== strpos($e->getMessage(), 'server has gone away')) {
                    $this->close();
                }
            }
            if ('insert' === $sqlMap['sql_type']) {
                $this->_lastInsertId=$this->lastInsertId();
            }
        } else {
            $exec_result=null;
            $stmt=null;
            try {
                $sql=$sqlMap['sql'];
                $this->_statement=$this->prepare($sql . ';');
                $exec_result=$this->_statement->execute();
                $className=$sqlMap['result_model'] ?? 'stdClass';
                $this->_statement->setFetchMode(PDO::FETCH_CLASS, $className);
            } catch(PDOException $e) {
                if ($e->getCode() === 2006 && false !== strpos($e->getMessage(), 'server has gone away')) {
                    $this->close();
                }
            }
            if (!$exec_result) {
                $err_info=$this->_statement->errorInfo();
                $msg=is_array($err_info) ? implode('|', $err_info) : '';
                $data=[
                    'driver_code'=>isset($err_info[1]) ? $err_info[1] : null,
                    'sql'=>$sqlMap,
                    'sql_state_code'=>isset($err_info[0]) ? $err_info[0] : null,
                ];
                throw new SQLExecuteException($msg, $data);
            }
        }
        return new SQLExecuteResult($this);
    }

    /**
     * @return bool
     */
    public function ping() : bool {
        $result=false;
        try {
            if (null !== $this->getAttribute(PDO::ATTR_SERVER_INFO)) {
                $result=true;
            }
        } catch(PDOException $e) {
            if (false === strpos($e->getMessage(), 'server has gone away')) {
                $result=true;
            }
        }
        return $result;
    }

    public function getLastInsertId() : int {
        return $this->_lastInsertId;
    }

    public function getAffectedRows() : ?int {
        return $this->_affectedRows;
    }

    public function getStatement() : PDOStatement {
        return $this->_statement;
    }

    public function close() : void {
        if (null !== $this->_statement) {
            $this->_statement=null;
        }
    }
}
