<?php
namespace rust\util;

/**
 * Class Random
 * @package rust\util
 */
final class Random {
    /**
     * 获取指定长度的随机字符串
     * @param int $length 指定的长度
     * @return null|string
     */
    protected static function getRandChars($length) {
        $str = NULL;
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, 61)];
        }
        return $str;
    }

    /**
     * 生成指定长度的随机字符串
     * @param int $length 要生成的字符串长度
     * @param bool $numeric 是否数字(即生成随机数字串)
     * @return string
     */
    public static function chars($length, $numeric = FALSE) {
        $seed = base_convert(hash('ripemd256', self::getRandChars(11) . $length), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
        $hash = '';
        if (!$numeric) {
            $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
            $length--;
        }
        $max = strlen($seed) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $seed{mt_rand(0, $max)};
        }
        return $hash;
    }

    /**
     * 生成指定长度的随机数字
     * @param int $len
     * @return int|null
     */
    public static function number($len) {
        if (!$len || !is_numeric($len)) {
            return NULL;
        }
        $num = getmypid();
        if (!$num) {
            $num = 1;
        }
        mt_srand((double) microtime() * 1000000 * $num);
        $min = pow(10, $len - 1);
        $max = pow(10, $len) - 1;
        return mt_rand($min, $max);
    }
}