<?php
namespace rust\util\design;
use rust\event\Event;

/**
 * Class Context
 * @package rust\util\design
 */
class Context {
    private $map   = [];
    private $event = NULL;

    public function __construct() {
        $this->map = [];
        $this->event = new Event();
    }

    public function get($key, $default = NULL, $class = NULL) {
        if (!isset($this->map[$key])) {
            return $default;
        }
        if (NULL === $class) {
            return $this->map[$key];
        }
        if ($this->map[$key] instanceof $class
            || is_subclass_of($this->map[$key], $class)
        ) {
            return $this->map[$key];
        }
        return $default;
    }

    public function set($key, $value) {
        $this->map[$key] = $value;
    }

    public function getEvent() {
        return $this->event;
    }

    public function getEventChain() {
        return $this->event->getEventChain();
    }
}