<?php
namespace rust\exception;
/**
 * Class CustomException
 *
 * @package rust\exception
 */
class CustomException extends RustException {
    /**
     * BaseException constructor.
     *
     * @param int      $code
     * @param null|int $sub_error_code
     */
    public function __construct($code, $sub_error_code = NULL) {
        parent::__construct($code, $sub_error_code);
    }
}
