<?php
namespace rust\dbo;
use rust\interfaces\IResultType;

/**
 * Created by PhpStorm.
 * User: rustysun
 */
class ResultFormatter {
    private $dbResult;
    private $resultType;

    /**
     * ResultFormatterInterface constructor.
     *
     * @param DBOResult $result
     * @param int       $resultType
     */
    public function __construct(DBOResult $result, $resultType = IResultType::RAW) {
        $this->dbResult   = $result;
        $this->resultType = $resultType;
    }

    /**
     * @return int|null|Statement
     */
    public function format() {
        switch ($this->resultType) {
        case IResultType::INSERT :
            $result = $this->insert();
            break;
        case IResultType::UPDATE :
            $result = $this->update();
            break;
        case IResultType::DELETE :
            $result = $this->delete();
            break;
        case IResultType::BATCH :
            $result = $this->batch();
            break;
        case IResultType::ROW :
            $result = $this->row();
            break;
        case IResultType::RAW :
            $result = $this->raw();
            break;
        case IResultType::SELECT :
            $result = $this->select();
            break;
        case IResultType::COUNT :
            $result = $this->count();
            break;
        case IResultType::LAST_INSERT_ID :
            $result = $this->lastInsertId();
            break;
        case IResultType::AFFECTED_ROWS :
            $result = $this->affectedRows();
            break;
        default :
            $result = $this->raw();
            break;
        }
        return $result;
    }

    private function select() {
        return $this->dbResult->getStatement();
    }

    private function count() {
        $rows = $this->dbResult->getStatement()->fetchArray();
        yield !isset($rows[0]['count_sql_rows']) ? 0 : (int)$rows[0]['count_sql_rows'];
    }

    private function insert() {
        return $this->dbResult->getStatement();
    }

    private function lastInsertId() {
        return $this->dbResult->getLastInsertId();
    }

    private function update() {
        return $this->dbResult->getStatement();
    }

    private function delete() {
        return $this->dbResult->getStatement();
    }

    private function affectedRows() {
        return $this->dbResult->getAffectedRows();
    }

    private function batch() {
        return $this->dbResult->getStatement();
    }

    private function row() {
        $rows = $this->dbResult->getStatement()->fetchArray();
        return isset($rows[0]) && [] != $rows[0] ? $rows[0] : NULL;
    }

    private function raw() {
        return $this->dbResult->getStatement();
    }
}