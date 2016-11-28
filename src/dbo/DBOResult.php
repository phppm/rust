<?php
/**
 * Created by PhpStorm.
 * User: rustysun
 */
namespace rust\dbo;
/**
 * Class DBOResult
 *
 * @package rust\dbo
 */
class DBOResult {
    /**
     * @var DBO
     */
    private $dbo;

    /**
     * FutureResult constructor.
     *
     * @param DriverInterface $driver
     */
    public function __construct(DBO &$dbo) {
        $this->dbo = $dbo;
    }

    /**
     * @return int
     */
    public function getLastInsertId() {
        return $this->dbo->getLastInsertId();
    }

    /**
     * @return int
     */
    public function getAffectedRows() {
        return $this->dbo->getAffectedRows();
    }

    /**
     * @return Statement
     */
    public function getStatement() {
        return $this->dbo->getStatement();
    }
}