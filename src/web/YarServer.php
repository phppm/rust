<?php
/**
 * web application
 *
 * @author rustysun.cn@gmail.com
 */
namespace rust\web;

use rust\Rust;
use rust\util\Config;
use rust\util\Registry;
use rust\exception\handler\Capture;
use rust\exception\handler\ExceptionHandler;
use rust\exception\RustException;

final class YarServer {
    protected static $_instance = [];
    /**
     * @var Server
     */
    protected static $_app = NULL;
    /**
     * @var Config;
     */
    protected static $_config;

    /**
     * @var Request
     */
    protected $request;

    protected        $_run = FALSE;
    protected static $_environ;
    protected        $_modules;

    /**
     * clone
     */
    private function __clone() {

    }

    /**
     * Application constructor.
     *
     * @param String $config
     */
    private function __construct($config) {
        $configInfo = explode('.', strtolower($config));
        self::$_environ = $configInfo && is_array($configInfo) && isset($configInfo[0]) ? $configInfo[0] : Rust::ENV_PRODUCTION;
        Registry::set(Rust::APP_CONFIG, new Config($config));
        self::$_config = Registry::get(Rust::APP_CONFIG);
        $this->init();
    }

    /**
     * get application instance
     *
     * @param string $config
     *
     * @return Server
     */
    public static function getInstance($config) {
        if (isset(self::$_instance[$config])) {
            return self::$_instance[$config];
        }
        self::$_instance[$config] = new YarServer($config);
        self::$_app = self::$_instance;
        return self::$_instance[$config];
    }

    /**
     * @return $this
     */
    protected function init() {
        //实例化一个Request,用来获取请求
        $uri_config = self::$_config->get('uri');
        $this->request = new Request($uri_config);
        $capture_exception = new Capture();
        $capture_exception->pushHandler(new ExceptionHandler());
        $capture_exception->register(self::$_config);
    }

    /**
     * Run
     *
     * @return bool
     */
    public function run() {
        if (!$this->_run) {
            $this->_run = TRUE;
        }
        //实例化一个Response，用来返回的数据
        $response = new Response();
        $request = $this->request;
        try {
            //初始化
            //$this->init();
            //TODO:路由开始前?
            //路由
            $router_config_name = self::$_config->get('router');
            $router_config = new Config($router_config_name);
            $router = new Router($router_config);
            if (!$request->isRouted()) {
                $router->route($request);
            }
            $route_info = $request->getRouted();
            $class_name = $route_info['controller_class'];
            $instance = new $class_name($request, $response, self::$_config);
            $service = new \Yar_Server($instance);
            $service->handle();
        }
        catch (RustException $e) {
            $this->_run = FALSE;
        }

        return $this->_run;
    }

    /**
     * get manage config instance
     *
     * @return Config
     */
    public static function getConfig() {
        return self::$_config;
    }

    /**
     * @return mixed|string
     */
    public static function getEnv() {
        return self::$_environ;
    }
}