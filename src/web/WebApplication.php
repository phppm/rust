<?php
/**
 * web application
 *
 * @author rustysun.cn@gmail.com
 */
namespace rust\web;
use rust\Application;
use rust\exception\ErrorCode;
use rust\exception\handler\Capture;
use rust\exception\handler\ExceptionHandler;
use rust\exception\HttpException;
use rust\exception\RustException;
use rust\util\Config;

/**
 * Class App
 *
 * @package rust\web
 */
final class WebApplication extends Application {
    /**
     * @var WebRequest
     */
    protected        $request;
    protected        $_run   = FALSE;
    protected static $_environ;
    protected        $_modules;
    protected        $status = 0;

    /**
     * @return $this
     */
    public function init() {
        parent::init();
        //实例化一个Request,用来获取请求
        $request = new WebRequest();
        $request->initRequestByServerEnv();
        $this->request     = $request;
        $capture_exception = new Capture();
        $capture_exception->pushHandler(new ExceptionHandler());
        $capture_exception->register();
        return $this;
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
        $response = new WebResponse();
        $request  = $this->request;
        try {
            //初始化
            $this->init();
            //TODO:路由开始前?
            //路由
            $route_config = $this->config->get('route');
            $router             = new Router(new Config($route_config));
            if (!$request->isRouted()) {
                $router->route($request);
            }
            $route_info = $request->getRouteInfo();
            if (!$route_info['controller']) {
                throw new HttpException(404);
            }
            $class_name = $route_info['controller_class'];
            $instance   = new $class_name($request, $response, $this->config);
            if ($instance instanceof Controller) {
                $instance->init();
            }
            $action = $route_info['action'];
            if (!method_exists($instance, $action)) {
                throw new RustException(ErrorCode::METHOD_NOT_FOUND);
            }
            call_user_func_array([$instance, $action], []);
        } catch (RustException $e) {
            $this->_run = FALSE;
            $code       = $e->getCode();
            if (404 == $code || ErrorCode::METHOD_NOT_FOUND == $code) {
                $this->status = 404;
            }
        }
        //TODO:路由结束后?
        //取出返回给客户端的数据
        if ($response->hasContent()) {
            $response->send();
            $response->clear();
        }
        if (!$this->_run && ($this->status == 404)) {
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
        }
        return $this->_run;
    }
}