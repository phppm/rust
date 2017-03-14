<?php
namespace rust\exception\view;

use Exception;

/**
 * Class ViewException
 *
 * @package rust\exception\view;
 * @author  rustysun.cn@gmail.com
 */
class ViewException extends Exception {
    public function __construct($code, $msg) {
        parent::__construct($msg, $code);
    }
}
