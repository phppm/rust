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
    const NAMED_METHOD_FORMAT = 'method-format';
    const NAMED_DEFAULT       = 'default';

    /**
     * 路由构造
     *
     * @param Config $route_config
     */
    public function __construct(Config $route_config) {
        $this->matchInfo = NULL;
        $this->routeInfo = [];
        $this->paras     = [];
        $this->config    = $route_config;
    }

    /**
     * @param WebRequest $request
     *
     * @return bool
     */
    public function route(& $request) {
        $result            = TRUE;
        $config            = $this->config;
        $isControllerFront = $config->get('controllerFront');
        $route_info        = [
            'package'    => $config->get('package'),
            'module'     => $config->get('module'),
            'controller' => $config->get('controller'),
            'action'     => $config->get('action'),
        ];
        $request_uri       = str_replace('\\', '/', $request->getUri()->getPath());
        $url               = trim($request_uri, '/');
        if (!empty($url)) {//默认路由
            $url_info = explode('/', $url);
            $count    = count($url_info);
            if (!$count) {
                return FALSE;
            }
            $index = 0;
            //是否有module
            $has_module = $config->get('module') && isset($url_info[$index]) && $url_info[$index];
            if ($has_module) {
                $route_info['module'] = $url_info[$index];
                $index++;
            }
            $route_info['controller'] = $url_info[$index];
            $index++;
            $action               = isset($url_info[$index]) ? $url_info[$index] : '';
            if($action){
                $route_info['action'] = $action;
            }
        }
        $version = NULL;
        if ($config->get('multi_version')) {
            $version = $request->getRequestVersion();
            $version = $version ? intval($version) : 1;
        }
        $route_info['controller_class'] = $this->_getController($route_info, $isControllerFront, $version);
        $request->setRouteInfo($route_info);
        return $result;
    }

    /**
     * @param array $routed
     * @param bool  $isControllerFront
     * @param null  $version
     *
     * @return string
     */
    private function _getController($routed, $isControllerFront = FALSE, $version = NULL) {
        $controller_name = isset($routed['package']) && $routed['package'] ? '\\' . $routed['package'] : '';
        if ($version) {
            $controller_name .= '\\v' . $version;
        }
        if ($isControllerFront) {
            $controller_name .= '\\controller';
        }
        $controller_name .= isset($routed['module']) && $routed['module'] ? '\\' . $routed['module'] : '';
        if (!$isControllerFront) {
            $controller_name .= '\\controller';
        }
        $controller_name .= '\\' . ucfirst($routed['controller']).'Controller';
        return $controller_name;
    }
}
