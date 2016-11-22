<?php
namespace rust\web;
use rust\interfaces\IService;
use rust\util\Result;

/**
 * Class Service
 *
 * @package rust\web
 * @author rustysun.cn@gmail.com
 */
abstract class Service implements IService {
    /**
     * return service result
     * @param int|Result $code
     * @param string     $msg
     * @param array      $data
     * @return Result
     */
    protected function result($code = 0, $msg = '', $data = NULL) {
        if ($code instanceof Result) {
            return $code;
        }
        return new Result($code, $msg, $data);
    }
}