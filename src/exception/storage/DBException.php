<?php
namespace rust\exception\storage;

use rust\exception\BaseException;
use rust\exception\ErrorCode;

class DBException extends BaseException {
    public function __construct($message = '', $data = NULL) {
        parent::__construct(ErrorCode::DBO_EXECUTE_FAILED, $message, $data);
    }
}