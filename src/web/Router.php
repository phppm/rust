<?php
namespace rust\web;

use rust\exception\RustException;
use rust\util\Config;

/**
 * 路由器
 *
 * @package rust\web
 */
final class Router {
    /**
     * @var array 路由信息
     */
    private $_route;

    /**
     * 路由器构造
     *
     * @param Config $config
     */
    public function __construct($config) {
        $this->_route = new Route($config);
    }


    /**
     * 获取路由实例
     *
     * @return null|Route
     */
    public function getCurrentRoute() {
        return $this->_route;
    }

    /**
     * 路由
     * @param $request
     * @throws RustException
     */
    public function route(&$request) {
        if (!$this->_route->route($request)) {
            throw new RustException(10002);
        }
    }
}
