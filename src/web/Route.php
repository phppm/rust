<?php
namespace rust\web;

use rust\interfaces\IRoute;
use rust\util\Config;

/**
 * Class Route
 *
 * @package rust\web
 */
class Route implements IRoute {
    protected $routeInfo;
    protected $matchInfo;
    protected $paras;
    protected $config;
    const NAMED_METHOD_FORMAT='method-format';
    const NAMED_DEFAULT='default';

    /**
     * 路由构造
     *
     * @param Config $route_config
     */
    public function __construct(Config $route_config) {
        $this->matchInfo=null;
        $this->routeInfo=[];
        $this->paras=[];
        $this->config=$route_config;
    }

    /**
     * @param WebRequest $request
     *
     * @return bool
     */
    public function route(& $request) {
        $result=true;
        $config=$this->config;
        $isControllerFront=$config->get('controllerFront') ? true : false;
        $routeInfo=new RouteInfo($config->get('package'), $config->get('module'), $config->get('controller'), $config->get('action'));
        $request_uri=str_replace('\\', '/', $request->getUri()->getPath());
        $baseUri = $config->get('base_uri');
        if($baseUri){
            $request_uri = ltrim($request_uri,$baseUri);
        }
        $url=trim($request_uri, '/');
        if (!empty($url)) {//默认路由
            $url_info=explode('/', $url);
            $count=count($url_info);
            if (!$count) {
                return false;
            }
            $index=0;
            //是否有module
            $has_module=$config->get('module') && isset($url_info[$index]) && $url_info[$index];
            if ($has_module) {
                $routeInfo->setModule($url_info[$index]);
                $index++;
            }
            $routeInfo->setController($url_info[$index]);
            $index++;
            $action=isset($url_info[$index]) ? $url_info[$index] : '';
            if ($action) {
                $routeInfo->setAction($action);
            }
        }
        $version=null;
        if ($config->get('multi_version')) {
            $version=$request->getRequestVersion();
            $version=$version ? intval($version) : 1;
        }
        $this->setController($routeInfo, $isControllerFront, $version);
        $request->setRouteInfo($routeInfo);
        return $result;
    }

    /**
     * @param RouteInfo $routeInfo
     * @param bool $isControllerFront
     * @param null|string $version
     */
    private function setController(RouteInfo &$routeInfo, bool $isControllerFront=false, ?string $version=null) : void {
        $package=$routeInfo->getPackage();
        $module=$routeInfo->getModule();
        $controller=$package ? '\\' . $package : '';
        if ($version) {
            $controller.='\\v' . $version;
        }
        if ($isControllerFront) {
            $controller.='\\controller';
        }
        $controller.=$module ? '\\' . $module : '';
        if (!$isControllerFront) {
            $controller.='\\controller';
        }
        $controller.='\\' . ucfirst($routeInfo->getController()) . 'Controller';
        $routeInfo->setControllerClass($controller);
    }
}
