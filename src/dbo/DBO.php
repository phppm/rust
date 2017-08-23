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
    private $_statement=null;
    private $connected =false;
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
     * @param array  $options
     */
    public function __construct($dsn, $username, $password, $options=[]) {
        $this->dsn=$dsn;
        $this->username=$username;
        $this->password=$password;
        $options=$options && is_array($options) ? $options : [];
        $options+=[
            PDO::ATTR_STATEMENT_CLASS   =>[
                '\\rust\\dbo\\Statement',
                [$this],
            ],
            PDO::ATTR_ERRMODE           =>PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES utf8',
        ];
        $this->options=$options;
        if (false === $this->connected) {
            $this->connect();
        }
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
                $this->_affectedRows=$this->exec($sqlMap['sql']);
            } catch(PDOException $e) {
                if ($e->getCode() !== 'HY000' ||
                    false === strpos($e->getMessage(), 'server has gone away')) {
                    $err_info=$this->errorInfo();
                    $msg=array_pop($err_info);
                    $data=[
                        'driver_code'   =>isset($err_info[1]) ? $err_info[1] : null,
                        'sql'           =>$sqlMap,
                        'sql_state_code'=>isset($err_info[0]) ? $err_info[0] : null,
                    ];
                    throw new SQLExecuteException($msg, $data);
                }
                $this->close();
                if ($this->connect()) {
                    $this->execute($sqlMap);
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
                $this->_statement=$this->prepare($sql);
                $exec_result=$this->_statement->execute();
                if (isset($sqlMap['result_model']) && $sqlMap['result_model']) {
                    $this->_statement->setFetchMode(PDO::FETCH_CLASS, $sqlMap['result_model']);
                } else {
                    $this->_statement->setFetchMode(PDO::FETCH_CLASS, 'stdClass');
                }
            } catch(PDOException $e) {
                if ($e->getCode() === 'HY000' &&
                    false !== strpos($e->getMessage(), 'server has gone away')) {
                    $this->close();
                    if ($this->connect()) {
                        $this->execute($sqlMap);
                    }
                }
            }
            if (!$exec_result) {
                $err_info=$this->_statement->errorInfo();
                $msg=array_pop($err_info);
                $data=[
                    'driver_code'   =>isset($err_info[1]) ? $err_info[1] : null,
                    'sql'           =>$sqlMap,
                    'sql_state_code'=>isset($err_info[0]) ? $err_info[0] : null,
                ];
                throw new SQLExecuteException($msg, $data);
            }
        }
        return new SQLExecuteResult($this);
    }

    public function getLastInsertId() : int {
        return $this->_lastInsertId;
    }

    public function getAffectedRows() : int {
        return $this->_affectedRows;
    }

    public function getStatement() : Statement {
        return $this->_statement;
    }

    public function close() : void {
        if (null !== $this->_statement) {
            $this->_statement=null;
        }
    }

    /**
     * @return bool
     */
    private function connect() : bool {
        $dsn=$this->dsn;
        $username=$this->username;
        $password=$this->password;
        $options=$this->options;
        parent::__construct($dsn, $username, $password, $options);
        $this->connected=true;
        return true;
    }
}
