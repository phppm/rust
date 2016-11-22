<?php
/**
 * Created by PhpStorm.
 * User: rustysun
 */
namespace rust\common;
use rust\exception\InvalidArgumentException;
use rust\fso\Dir;
use rust\interfaces\IConfig;

abstract class Config implements IConfig {
    /**
     * @param $path
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function loadFromFiles($path) {
        if (!is_dir($path)) {
            throw new InvalidArgumentException('Invalid path for ConfigLoader');
        }
        $path        = Dir::formatPath($path);
        $configFiles = Dir::glob($path, '*.php', Dir::SCAN_BFS);
        $configMap   = [];
        foreach ($configFiles as $configFile) {
            $loadedConfig = require $configFile;
            if (!is_array($loadedConfig)) {
                throw new InvalidArgumentException("syntax error find in config file: " . $configFile);
            }
            $keyString           = substr($configFile, strlen($path), -4);
            $pathKey             = str_replace("/", ".", $keyString);
            $configMap[$pathKey] = $loadedConfig;
        }
        return $configMap;
    }
}