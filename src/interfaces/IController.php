<?php
namespace rust\interfaces;
/**
 * Interface IController
 *
 * @package rust\interfaces
 */
interface IController {
    public function init();

    public function r($code, $msg = NULL, $data = NULL);
} // END interface IPKController
