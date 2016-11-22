<?php
namespace rust\util\design;
/**
 * Class Instance
 * @package rust\util\design
 */
trait Instance {
    public static function newInstance() {
        return new static();
    }
}
