<?php
namespace rust\interfaces;
/**
 * Interface IConfig
 *
 * @package rust\interfaces
 */
interface IConfig {
    /**
     * @param $name
     *
     * @return mixed
     */
    public function get($name);
}