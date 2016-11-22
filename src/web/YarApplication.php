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
use rust\http\Request;
use rust\http\Response;
use rust\interfaces\IApplication;

final class YarApplication implements IApplication {
    protected static $_instance = [];
    /**
     * @var App
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

    protected $_run = FALSE;
    protected static $_environ;
    protected $_modules;

    /**
     * clone
     */
    private function __clone() {

    }

    /**
     * Application constructor.
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
     * @param string $config
     * @return App
     */
    public static function getInstance($config) {
        if (isset(self::$_instance[$config])) {
            return self::$_instance[$config];
        }
        self::$_instance[$config] = new App($config);
        self::$_app = self::$_instance;
        return self::$_instance[$config];
    }

    /**
     * @return $this
     */
    protected function init() {
        $capture_exception = new Capture();
        $capture_exception->pushHandler(new ExceptionHandler());
        $capture_exception->register(self::$_config);
        /*
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        $whoops->register();
        */
    }

    /**
     * Run
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
            if ($instance instanceof Controller) {
                $instance->init();
            }
            $action = $route_info['action'];
            //TODO:params
            call_user_func_array([$instance, $action], []);
        } catch (RustException $e) {
            $this->_run = FALSE;
        }
        //TODO:路由结束后?
        //取出返回给客户端的数据
        if ($response->hasContent()) {
            $response->send();
            $response->clear();
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