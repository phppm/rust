<?php
namespace rust\dbo\exception;

use rust\exception\ErrorCode;

/**
 * Class SQLExecuteException
 *
 * @package rust\dbo
 */
class SQLExecuteException extends DBOException {
    /**
     * SQLExecuteException constructor.
     *
     * @param string $msg
     * @param array  $data
     */
    public function __construct(string $msg = 'sql execute failed', array $data = []) {
        parent::__construct(ErrorCode::DBO_SQL_EXECUTE_FAILED, $msg, $data);
    }
}