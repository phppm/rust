<?php

namespace rust\dbo\exception;

use rust\exception\ErrorCode;

/**
 * Class DBOConnectionException
 *
 * @package rust\dbo
 */
class DBOConnectionException extends DBOException {
    /**
     * DBOConnectionException constructor.
     *
     * @param string $msg
     * @param array  $data
     */
    public function __construct(string $msg = 'cant connect database server', array $data = []) {
        parent::__construct(ErrorCode::DBO_CONNECT_FAILED, $msg, $data);
    }
}