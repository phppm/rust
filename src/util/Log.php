<?php
namespace rust\util;

use DateTime;
use Exception;
use rust\exception\RuntimeException;
use rust\Path;

defined('RUST_END_LINE') or define('RUST_END_LINE', "\r\n");

/**
 * Class Log
 *
 * @package rust\util
 */
final class Log {
    const EMERGENCY=0;
    const ALERT=1;
    const CRITICAL=2;
    const ERROR=3;
    const SQL=4;
    const WARNING=5;
    const NOTICE=6;
    const INFO=7;
    const DEBUG=8;
    const ALL=0xffffffff;
    const NONE=0x00000000;
    /**
     * @var Log
     */
    private static $instance;
    /**
     * Path to the log file
     *
     * @var string
     */
    private $logFilePath=null;
    /**
     * @var array 日志等级
     */
    private $levels=[
        self::EMERGENCY=>'emergency',
        self::ALERT    =>'alert',
        self::CRITICAL =>'critical',
        self::ERROR    =>'error',
        self::SQL      =>'sql',
        self::WARNING  =>'warning',
        self::NOTICE   =>'notice',
        self::INFO     =>'info',
        self::DEBUG    =>'debug',
    ];
    /**
     * Valid PHP date() format string for log timestamps
     *
     * @var string
     */
    private $dateFormat='Y-m-d G:i:s.u';
    /**
     * Octal notation for default permissions of the log file
     *
     * @var integer
     */
    private $defaultPermissions=0777;

    private function __clone() {
    }

    /**
     * Class constructor
     *
     * @throws RuntimeException
     */
    private function __construct() {
        $path=rtrim(Path::getLogPath(), '\\/');
        if (empty($path)) {
            throw new RuntimeException('The log could not be initialized. Check that log path have been set.');
        }
        if (!file_exists($path)) {
            mkdir($path, $this->defaultPermissions, true);
        }
        $this->logFilePath=$path;
    }

    /**
     * 单例
     *
     * @return Log
     */
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance=new Log;
        }
        return self::$instance;
    }

    /**
     * 写入日志
     *
     * @param string     $msg
     * @param int|string $type
     * @param array      $context
     * @param array      $paras
     */
    public static function write($msg, $type='info', $context=[], $paras=[]) {
        $instance=self::getInstance();
        $msg=is_string($msg) ? $msg : json_encode($msg, JSON_UNESCAPED_UNICODE);
        $paras=array_merge(['save_mode'=>1], $paras);
        $save_mode=$paras['save_mode'];
        if ($save_mode === 1) {
            try {
                $instance->writeToFile($type, $msg, $context);
            } catch (Exception $e) {
                //TODO:写入失败
            }
        }
        //TODO:使用seaslog
        //TODO:push to tcp or udp server
    }

    /**
     * Takes the given context and coverts it to a string.
     *
     * @param  array $context The Context
     *
     * @return string
     */
    protected function contextToString($context) {
        $export='';
        foreach ($context as $key=>$value) {
            $export.="{$key}: ";
            $export.=preg_replace([
                '/=>\s+([a-zA-Z])/im',
                '/array\(\s+\)/im',
                '/^  |\G  /m',
            ], [
                '=> $1',
                'array()',
                '    ',
            ], str_replace('array (', 'array(', var_export($value, true)));
            $export.=RUST_END_LINE;
        }
        return str_replace(['\\\\', '\\\''], ['\\', '\''], rtrim($export));
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param int|string $level
     * @param string     $message
     * @param array      $context
     *
     * @return bool|void
     * @throws RuntimeException
     */
    protected function writeToFile($level, $message, array $context=[]) {
        $name=$level;
        if (is_int($level)) {
            $name=isset($this->levels[$level]) ? $this->levels[$level] : '';
        }
        $name=empty($name) ? 'info' : $name;
        $logFilePath=$this->logFilePath . DIRECTORY_SEPARATOR . $name . '_' . date('Ymd') . '.log';
        if (file_exists($logFilePath) && !is_writable($this->logFilePath)) {
            throw new RuntimeException('can not write log file!' . $name);
        }
        if ($context && is_array($context)) {
            $message.=RUST_END_LINE . $this->contextToString($context);
        }
        $originalTime=microtime(true);
        $micro=sprintf("%06d", ($originalTime - floor($originalTime)) * 1000000);
        $date=new DateTime(date('Y-m-d H:i:s.' . $micro, $originalTime));
        $timestamp=$date->format($this->dateFormat);
        $message="{$timestamp} | {$message}";
        $result=null;
        try {
            //$result=error_log($message, 3, $logFilePath);
            $isCLI=preg_match("/cli/i", PHP_CLI) ? true : false;
            if (function_exists('swoole_async_write') && $isCLI) {
                $result=swoole_async_write($logFilePath, $message . "\t[swoole]\n");
            } else {
                $result=file_put_contents($logFilePath, $message . "\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            //$result=error_log($message);
        }
        if (!$result) {
            throw new RuntimeException('The file could not be written to. Check that appropriate permissions have been set.');
        }
        return 0;
    }
}