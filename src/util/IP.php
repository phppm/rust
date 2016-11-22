<?php
namespace rust\util;

class IP {
    /**
     * @param string $ip
     *
     * @return string
     */
    public static function toLong($ip = '') {
        return sprintf('%u', ip2long($ip));
    }
}