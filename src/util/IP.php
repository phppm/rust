<?php
namespace rust\util;
/**
 * Class IP
 *
 * @package rust\util
 */
final class IP {
    /**
     * @param string $ip
     *
     * @return string
     */
    public static function toLong($ip = '') {
        return sprintf('%u', ip2long($ip));
    }

    /**
     * @param int $ip_long
     *
     * @return null|string
     */
    public static function toString($ip_long) {
        if (!$ip_long) {
            return NULL;
        }
        return long2ip($ip_long);
    }
}