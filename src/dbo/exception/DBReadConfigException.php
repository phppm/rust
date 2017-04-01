<?php

namespace rust\dbo\exception;

use rust\exception\ErrorCode;

/**
 * Class DBReadConfigException
 *
 * @package rust\dbo
 */
class DBReadConfigException extends DBOException {
    /**
     * DBReadConfigException constructor.
     *
     * @param string $msg
     */
    public function __construct($msg = '') {
        $msg = $msg ? $msg : 'not found database config';
        parent::__construct(ErrorCode::DBO_CONFIG_READ_FAILED, $msg, []);
    }
}