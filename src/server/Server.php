<?php
/**
 * server application
 *
 * @author rustysun.cn@gmail.com
 */
namespace rust\web;

use rust\Rust;
use rust\util\Config;
use rust\util\Registry;
use rust\exception\handler\Capture;
use rust\exception\handler\ExceptionHandler;
use rust\exception\BaseException;

use rust\http\Request;
use rust\server\IServer;


final class Server {
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

    protected $status = 0;

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
     * get server application instance
     *
     * @param $config
     *
     * @return Server
     */
    public static function getInstance($config) {
        if (isset(self::$_instance[$config])) {
            return self::$_instance[$config];
        }
        self::$_instance[$config] = new Server($config);
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
     *
     * @param      $server
     * @param null $config
     *
     * @return bool
     */
    public function run($server, $config = NULL) {
        if (!$this->_run) {
            $this->_run = TRUE;
        }
        try {
            $srv = new $server;
            if (!$srv instanceof IServer) {
                return FALSE;
            }
            if ($config) {
                $this->_run = $srv->run($config);
            } else {
                $this->_run = $srv->run();
            }
        }
        catch (BaseException $e) {
            echo $e->toString();
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