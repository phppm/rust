<?php
namespace rust\util;
use rust\common\Config;
use Yaconf;

/**
 * Class Config 配置
 *
 * @package rust\util
 */
final class YAConfig extends Config {
    /**
     * @var string
     */
    private $prefix;

    /**
     * 防止clone
     */
    private function __clone() {
    }

    /**
     * YAConfig constructor.
     *
     * @param null|string $prefix
     */
    public function __construct($prefix = NULL) {
        $this->prefix = $prefix;
    }

    /**
     * 获取配置项设定值
     *
     * @param string $name
     * @param null   $default
     *
     * @return mixed|null
     */
    public function get($name, $default = NULL) {
        if (!$name) {
            //TODO
            return NULL;
        }
        $config_key = ($this->prefix ? $this->prefix . '.' : '') . $name;
        if (!Yaconf::has($config_key)) {
            //TODO
            return NULL;
        }
        return Yaconf::get($config_key, $default);
    }

    /**
     * set config
     *
     * @param $name
     * @param $content
     *
     * @return bool
     */
    //public function set($name, $content) {
    //    if (!$name || !$content) {
    //        return FALSE;
    //    }
    //    $prefix     = $this->prefix ? $this->prefix . '.' : '';
    //    $configFile = Path::getConfigPath() . '/' . $prefix . '.ini';
    //    if (!file_exists($configFile)) {
    //        //TODO:
    //        return FALSE;
    //    }
    //    //TODO:read a ini to array
    //    $iniData = parse_ini_file($configFile);
    //    print_r($iniData);
    //    die;
    //    //TODO:update ini config
    //    //TODO:save to file
    //    return TRUE;
    //}
    //
    //private function saveToFile(array $array, $path, $has_sections) {
    //    $content = '';
    //    if (!$handle = fopen($path, 'w')) {
    //        return FALSE;
    //    }
    //    $this->writeToFile($content, $array, $has_sections);
    //    if (!fwrite($handle, $content)) {
    //        return FALSE;
    //    }
    //    fclose($handle);
    //    return TRUE;
    //}
    //
    ///**
    // * @param $content
    // * @param $assoc_arr
    // * @param $has_sections
    // */
    //private function writeToFile(&$content, $assoc_arr, $has_sections) {
    //    foreach ($assoc_arr as $key => $val) {
    //        if (is_array($val)) {
    //            if ($has_sections) {
    //                $content .= "[$key]\n";
    //                $this->writeToFile($content, $val, FALSE);
    //            } else {
    //                foreach ($val as $iKey => $iVal) {
    //                    if (is_int($iKey)) {
    //                        $content .= $key . "[] = $iVal\n";
    //                    } else {
    //                        $content .= $key . "[$iKey] = $iVal\n";
    //                    }
    //                }
    //            }
    //        } else {
    //            $content .= "$key = $val\n";
    //        }
    //    }
    //}
}