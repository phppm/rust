<?php
namespace rust;
use rust\exception\handler\Capture;
use rust\exception\handler\ExceptionHandler;
use rust\util\Registry;

/**
 * Class Rust
 *
 * @package rust
 */
final Class Rust {
    /**
     * @var Registry
     */
    private static $config;
    /**
     * @var Application
     */
    private static $app;

    /**
     * @param null|ExceptionHandler $exceptionHandler
     */
    public static function init($exceptionHandlerName = NULL) {
        $capture_exception    = new Capture();
        $exceptionHandlerName = $exceptionHandlerName??'\\rust\exception\\handler\\ExceptionHandler';
        $exceptionHandler     = new $exceptionHandlerName();
        $capture_exception->pushHandler($exceptionHandler);
        $capture_exception->register();
    }

    /**
     * 构建应用实例
     *
     * @param string $name
     * @param string $base_path
     * @param        $config
     *
     * @return Application|null
     */
    public static function createApplication($name, $base_path = NULL, $config) {
        $instance = NULL;
        if (!$name || !$base_path) {
            return $instance;
        }
        static::$config = $config;
        $namespace      = '\\' . str_replace('.', '\\', $name);
        $instance       = new $namespace($name, $base_path, $config);
        if ($instance instanceof Application) {
            static::$app = $instance;
            return $instance;
        }
        return NULL;
    }

    public static function getApp() {
        return static::$app;
    }

    public static function getConfig() {
        return static::$config;
    }
}