<?php
namespace rust\util\design;
/**
 * Class Singleton
 * @package rust\util\design
 */
trait Singleton {
    /**
     * @var static
     */
    private static $_instance = NULL;

    private function __clone() {
    }

    private function __construct() {
    }

    final private static function singleton() {
        if (NULL === static::$_instance) {
            static::$_instance = new static();
        }
        return static::$_instance;
    }

    /**
     * @return static
     */
    final public static function getInstance() {
        return static::singleton();
    }
}