<?php
namespace rust\web;

/**
 * Class RouteInfo
 *
 * @package rust\web
 */
final class RouteInfo {
    /**
     * @var string $package
     */
    private $package;
    /**
     * @var string $module
     */
    private $module;
    /**
     * @var string $controller
     */
    private $controller;
    /**
     * @var string $controllerClass
     */
    private $controllerClass;
    /**
     * @var string $action
     */
    private $action;

    /**
     * 路由信息
     *
     * @param string $package
     * @param string $module
     * @param string $controller
     * @param string $action
     */
    public function __construct(string $package, string $module, string $controller, string $action) {
        $this->setPackage($package);
        $this->setModule($module);
        $this->setController($controller);
        $this->setAction($action);
    }

    /**
     * @return string
     */
    public function getPackage() : string {
        return $this->package;
    }

    /**
     * @param string $package
     */
    public function setPackage(string $package) : void {
        $this->package=$package;
    }

    /**
     * @return string
     */
    public function getModule() : string {
        return $this->module;
    }

    /**
     * @param string $module
     */
    public function setModule(string $module) : void {
        $this->module=$module;
    }

    /**
     * @return string
     */
    public function getController() : string {
        return $this->controller;
    }

    /**
     * @param string $controller
     */
    public function setController(string $controller) : void {
        $this->controller=$controller;
    }

    /**
     * @return string
     */
    public function getAction() : string {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction(string $action) : void {
        $this->action=$action;
    }

    /**
     * @return string
     */
    public function getControllerClass() : string {
        return $this->controllerClass;
    }

    /**
     * @param string $controllerClass
     */
    public function setControllerClass(string $controllerClass) : void {
        $this->controllerClass=$controllerClass;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isController(string $name) : bool {
        return $this->controller === $name ? true : false;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isAction(string $name) : bool {
        return $this->action === $name ? true : false;
    }
}
