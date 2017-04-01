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
     * @param array $data
     */
    public function __construct($data = []) {
        parent::__construct(ErrorCode::DBO_CONFIG_READ_FAILED, 'not found database config', $data);
    }
}