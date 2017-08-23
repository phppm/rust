<?php

namespace rust\dbo;
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
        $this->dbo = $dbo;
    }

    /**
     * @return int
     */
    public function getLastInsertId(): int {
        return $this->dbo->getLastInsertId();
    }

    /**
     * @return int
     */
    public function getAffectedRows(): int {
        return $this->dbo->getAffectedRows();
    }

    /**
     * @return Statement
     */
    public function getStatement(): Statement {
        return $this->dbo->getStatement();
    }

    public function free(){
        $this->dbo=null;
    }
}