<?php
namespace rust\web;

use rust\interfaces\IService;
use rust\base\Result;

/**
 * Class Service
 *
 * @package rust\web
 * @author rustysun.cn@gmail.com
 */
abstract class YarService implements IService {
    /**
     * call api
     * @param $path
     * @param array $parameter
     * @param null $callback
     * @return mixed
     */
    protected function call($path, $parameter = [], $callback = NULL) {
        return YarClient::call($path, $parameter, $callback);
    }

    /**
     * @param null $callback
     * @param null $error_callback
     */
    protected function loop($callback = NULL, $error_callback = NULL) {
        YarClient::loop($callback, $error_callback);
    }

    /**
     * return service result
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return Result
     */
    protected function result($code = 0, $msg = '', $data = []) {
        return new Result($code, $msg, $data);
    }
}