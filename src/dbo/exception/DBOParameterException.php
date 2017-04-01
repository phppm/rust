<?php

namespace rust\dbo\exception;

use rust\exception\ErrorCode;

/**
 * Class DBOParameterException
 *
 * @package rust\dbo
 */
class DBOParameterException extends DBOException {
    /**
     * DBOConfigParameterException constructor.
     *
     * @param string $msg
     * @param array  $data
     */
    public function __construct(string $msg = '数据库连接参数获取失败', array $data = []) {
        parent::__construct(ErrorCode::DBO_CONNECT_FAILED, $msg, $data);
    }
}