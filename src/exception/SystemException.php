<?php
namespace rust\exception;
/**
 * Class SystemException
 *
 * @package rust\exception
 * @author  rustysun.cn@gmail.com
 */
class SystemException extends RustException {
    public function __construct($msg) {
        $code = 9999;
        parent::__construct($code, $msg);
    }
}
