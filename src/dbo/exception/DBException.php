<?php

namespace rust\dbo\exception;

use rust\exception\ErrorCode;

class DBException extends DBOException {
    public function __construct(string $message = '') {
        parent::__construct(ErrorCode::DBO_FAILED, $message);
    }
}