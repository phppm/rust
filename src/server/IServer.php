<?php
/**
 * Created by PhpStorm.
 * User: rustysun
 * Date: 16/7/13
 * Time: 上午7:00
 */
namespace rust\server;

interface IServer {
    public function run($config = NULL);
}