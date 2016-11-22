<?php
namespace util\design;
/**
 * Class Registry
 * @package util\design
 */
final class Registry {
    private static $resource = [];

    public static function get($key, $default = NULL) {
        if (!isset(self::$resource[$key])) {
            return $default;
        }
        return self::$resource[$key];
    }

    public static function set($key, $value) {
        self::$resource[$key] = $value;
    }

    public static function contain($key) {
        return isset(self::$resource[$key]);
    }
}