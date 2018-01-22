<?php
namespace rust\dbo;

use PDOStatement;

/**
 * Class SQLExecuteResult
 *
 * @package rust\dbo
 */
class SQLExecuteResult {
    /**
     * @var DBO $dbo
     */
    private $dbo;

    /**
     * DBExecResult constructor.
     *
     * @param DBO $dbo
     */
    public function __construct(DBO &$dbo) {
        $this->dbo=$dbo;
    }

    /**
     * @return int
     */
    public function getLastInsertId() : int {
        return $this->dbo->getLastInsertId();
    }

    /**
     * @return int
     */
    public function getAffectedRows() : int {
        $rows=$this->dbo->getAffectedRows();
        return (!$rows ? 0 : $rows);
    }

    /**
     * @return PDOStatement
     */
    public function getStatement() : PDOStatement {
        return $this->dbo->getStatement();
    }

    public function free() {
        $this->dbo=null;
    }
}