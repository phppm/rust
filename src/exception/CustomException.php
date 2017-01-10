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
     * @param int                   $main_code
     * @param null|int|string|array $sub_error_code
     * @param null|array            $data
     */
    public function __construct($code, $sub_error_code = NULL, $data = NULL) {
        parent::__construct($code, $sub_error_code, $data);
    }
}
