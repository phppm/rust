<?php
namespace rust\exception;

use rust\exception\RustException;

/**
 * Class RuntimeException
 *
 * @package rust\exception
 */
class RuntimeException extends RustException {
    public function __construct($msg) {
        parent::__construct(9001, $msg);
    }

}
