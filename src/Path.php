<?php
namespace rust;

use rust\fso\Dir;

/**
 * Class Path
 *
 * @package rust
 */
class Path {
    const DEFAULT_CONFIG_PATH = 'resource/config/';
    const DEFAULT_TABLE_PATH = 'resource/config/share/table/';
    const DEFAULT_LOG_PATH = 'resource/logs/';
    const DEFAULT_CACHE_PATH = 'resource/cache/';
    const DEFAULT_MODEL_PATH = 'resource/model/';
    const DEFAULT_ROUTING_PATH = 'resource/routing';
    const DEFAULT_MIDDLEWARE_PATH = 'resource/middleware';
    const DEFAULT_APPLICATION_PATH = 'src/';
    private static $rootPath        = NULL;
    private static $configPath      = NULL;
    private static $logPath         = NULL;
    private static $cachePath       = NULL;
    private static $modelPath       = NULL;
    private static $tablePath       = NULL;
    private static $routingPath     = NULL;
    private static $middlewarePath  = NULL;
    private static $applicationPath = NULL;

    /**
     * @param string $rootPath
     */
    public static function init(string $rootPath) {
        static::setRootPath($rootPath);
        static::$configPath = static::$rootPath . static::DEFAULT_CONFIG_PATH;
        static::$logPath = static::$rootPath . static::DEFAULT_LOG_PATH;
        static::$modelPath = static::$rootPath . static::DEFAULT_MODEL_PATH;
        static::$cachePath = static::$rootPath . static::DEFAULT_CACHE_PATH;
        static::$tablePath = static::$rootPath . static::DEFAULT_TABLE_PATH;
        static::$routingPath = static::$rootPath . static::DEFAULT_ROUTING_PATH;
        static::$middlewarePath = static::$rootPath . static::DEFAULT_MIDDLEWARE_PATH;
        static::$applicationPath = static::$rootPath . static::DEFAULT_APPLICATION_PATH;
    }

    public static function getRootPath() {
        return static::$rootPath;
    }

    public static function getApplicationPath() {
        return static::$applicationPath;
    }

    public static function getConfigPath() {
        return static::$configPath;
    }

    public static function setConfigPath($configPath) {
        static::$configPath = $configPath;
    }

    public static function getLogPath() {
        return static::$logPath;
    }

    public static function getModelPath() {
        return static::$modelPath;
    }

    public static function getCachePath() {
        return static::$cachePath;
    }

    public static function getTablePath() {
        return static::$tablePath;
    }

    public static function getRoutingPath() {
        return static::$routingPath;
    }

    public static function getMiddlewarePath() {
        return static::$middlewarePath;
    }

    private static function setRootPath($rootPath) {
        static::$rootPath = Dir::formatPath($rootPath);
    }
}