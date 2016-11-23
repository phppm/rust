<?php
namespace rust;
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

    private static $app;

    /**
     * 构建应用实例
     *
     * @param string $name
     * @param string $base_path
     *
     * @return Application|null
     */
    public static function createApplication($name, $base_path = NULL, $config) {
        $instance = NULL;
        if (!$name || !$base_path) {
            return $instance;
        }
        static::$config = $config;
        $namespace      = '\\\\' . str_replace('.', '\\\\', $name);
        $instance       = new $namespace($name, $base_path);
        if ($instance instanceof Application) {
            return $instance;
        }
        return NULL;
    }

    public static function getConfig() {
        return static::$config;
    }
}