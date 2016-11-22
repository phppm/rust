<?php
namespace rust\interfaces;
/**
 * Interface IApplication
 *
 * @package rust\interfaces
 */
interface IApplication {
    /**
     * IApplication constructor.
     *
     * @param string  $name
     * @param string  $base_path
     * @param IConfig $config
     */
    public function __construct($name, $base_path, IConfig $config);

    public function init();

    public function run();
}