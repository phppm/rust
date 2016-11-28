<?php
namespace rust;
use rust\fso\Dir;

/**
 * Class Path
 *
 * @package rust
 */
class Path {
    const DEFAULT_CONFIG_PATH     = 'resource/config/';
    const DEFAULT_TABLE_PATH      = 'resource/config/share/table/';
    const DEFAULT_SQL_PATH        = 'resource/sql/';
    const DEFAULT_LOG_PATH        = 'resource/logs/';
    const DEFAULT_CACHE_PATH      = 'resource/cache/';
    const DEFAULT_MODEL_PATH      = 'resource/model/';
    const DEFAULT_ROUTING_PATH    = 'resource/routing';
    const DEFAULT_MIDDLEWARE_PATH = 'resource/middleware';
    private static $rootPath       = NULL;
    private static $configPath     = NULL;
    private static $sqlPath        = NULL;
    private static $logPath        = NULL;
    private static $cachePath      = NULL;
    private static $modelPath      = NULL;
    private static $tablePath      = NULL;
    private static $routingPath    = NULL;
    private static $middlewarePath = NULL;

    public static function init($rootPath) {
        self::setRootPath($rootPath);
        //
        self::$configPath     = self::$rootPath . self::DEFAULT_CONFIG_PATH;
        self::$sqlPath        = self::$rootPath . self::DEFAULT_SQL_PATH;
        self::$logPath        = self::$rootPath . self::DEFAULT_LOG_PATH;
        self::$modelPath      = self::$rootPath . self::DEFAULT_MODEL_PATH;
        self::$cachePath      = self::$rootPath . self::DEFAULT_CACHE_PATH;
        self::$tablePath      = self::$rootPath . self::DEFAULT_TABLE_PATH;
        self::$routingPath    = self::$rootPath . self::DEFAULT_ROUTING_PATH;
        self::$middlewarePath = self::$rootPath . self::DEFAULT_MIDDLEWARE_PATH;
    }

    public static function getRootPath() {
        return self::$rootPath;
    }

    public static function getConfigPath() {
        return self::$configPath;
    }

    public static function setConfigPath($configPath) {
        self::$configPath = $configPath;
    }

    public static function getSqlPath() {
        return self::$sqlPath;
    }

    public static function getLogPath() {
        return self::$logPath;
    }

    public static function getModelPath() {
        return self::$modelPath;
    }

    public static function getCachePath() {
        return self::$cachePath;
    }

    public static function getTablePath() {
        return self::$tablePath;
    }

    public static function getRoutingPath() {
        return self::$routingPath;
    }

    public static function getMiddlewarePath() {
        return self::$middlewarePath;
    }

    private static function setRootPath($rootPath) {
        self::$rootPath = Dir::formatPath($rootPath);
    }
}