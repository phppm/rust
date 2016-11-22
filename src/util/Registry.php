<?php
namespace rust\util;

final class Registry {
    protected static $_entries;

    private function __clone() {

    }

    private function __construct() {
    }

    /**
     * @param $name
     */
    public static function del($name) {
        if (Registry::has($name)) {
            unset(self::$_entries[$name]);
            return TRUE;
        }
        //TODO:抛异常
        return FALSE;
    }

    /**
     * @param $name
     * @return null
     */
    public static function get($name) {
        if (!Registry::has($name)) {
            //TODO:抛异常
            return NULL;
        }
        return self::$_entries[$name];
    }

    /**
     * @param $name
     * @return bool
     */
    public static function has($name) {
        return isset(self::$_entries[$name]);
    }

    /**
     * @param $name
     * @param $value
     */
    public static function set($name, $value) {
        self::$_entries[$name] = $value;
    }
}