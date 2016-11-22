<?php
namespace rust\util\design;
/**
 * Class AbstractFactory
 * @package rust\util\design
 */
trait AbstractFactory {
    abstract public function createInstance();
}