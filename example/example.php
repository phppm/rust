<?php
//开启错误报造
error_reporting(E_ALL);
ini_set('display_errors', FALSE);
date_default_timezone_set('PRC');
ini_set('set_time_limit', 0);//php执行时间没有限制
//自动载入
$root_path = dirname(__DIR__);
require_once($root_path . '/vendor/autoload.php');
use rust\loader\ClassLoader;
use rust\Rust;
use rust\util\YAConfig;
//loader
$loader = new ClassLoader();
$loader->register();
$loader->addPrefix('app\\', $root_path);
//config
$config = new YAConfig('example');
//application
$app = Rust::createApplication('web.WebApplication', dirname(__DIR__), $config);
$app->init();
if (!$app->run()) {
    exit(1);
}