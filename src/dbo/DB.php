<?php
/**
 * DB - database manager
 *
 * @author rustysun.cn@gmail.com
 */
namespace rust\dbo;

use rust\common\Config as AbstractConfig;
use rust\dbo\exception\DBException;
use rust\dbo\exception\DBOException;
use rust\dbo\exception\DBReadConfigException;
use rust\Rust;
use rust\util\Config;
use rust\util\Log;

/**
 * DB
 */
class DB {
    /**
     * @var Config $dbConfig
     */
    private static $dbConfig=null;
    /**
     * @var Connection[] $connections
     */
    private static $connections;

    /**
     * @param       $sid
     * @param       $data
     * @param array $options
     *
     * @return int|null|Statement
     * @throws DBException
     */
    public static function exec($sid, $data, array $options=[]) {
        $sqlMap=null;
        $errMsg=null;
        try {
            $sqlMap=SqlMap::getInstance()->getSql($sid, $data, $options);
            $conn_key=Table::getInstance()->getDatabase($sqlMap['table']);
            $dboResult=self::getConnection($conn_key)->getDBO($conn_key)->execute($sqlMap);
            if(!$sqlMap['result_type']){
                return null;
            }
            $formatter=new ResultFormatter($dboResult, $sqlMap['result_type']);
            return $formatter->format();
        } catch(DBOException $e) {
            $errMsg=$e->getCode() . "|" . $e->getMessage() . "\t[dbo]";
        } catch(\PDOException $e) {
            $errMsg=$e->getCode() . "|" . ($e->getMessage()) . "\t[pdo]";
            if ($e->getCode() === 2006) {
                return self::exec($sid, $data, $options);
            }
        } catch(\Exception $e) {
            $errMsg=$e->getCode() . "|" . $e->getMessage() . "\t[exn]";
        }
        if ($errMsg) {
            Log::write($sid . "\t" . $errMsg . print_r($sqlMap, true), 'dbo_error');
        }
        return null;
    }

    /**
     * @param string $sid
     * @param array  $data
     * @param array  $options
     *
     * @return Statement
     * @throws DBException
     */
    public static function execQuery(string $sid, array $data, $options=[]) : Statement {
        $result=static::exec($sid, $data, $options);
        if (null === $result || !$result instanceof Statement) {
            throw new DBException('数据库执行出错了');
        }
        return $result;
    }

    /**
     * @param string $sid
     * @param array  $data
     * @param array  $options
     *
     * @return int
     * @throws DBException
     */
    public static function execUpdate(string $sid, array $data, array $options=[]) : int {
        $result=static::exec($sid, $data, $options);
        if (null === $result || !is_int($result)) {
            throw new DBException('数据库执行出错了');
        }
        return $result;
    }

    /**
     * @return array
     */
    public static function getConnections() : array {
        return static::$connections;
    }

    /**
     * @param Config $dbConfig
     * @param array  $connKeys
     */
    public static function init(Config $dbConfig, array $connKeys=['default_write']) {
        static::$dbConfig=$dbConfig;
        foreach ($connKeys as $connKey) {
            static::getConnection($connKey)->getDBO($connKey);
        }
    }

    /**
     * 获取数据库连接
     *
     * @param string $conn_key
     *
     * @return Connection
     * @throws DBReadConfigException
     */
    private static function getConnection(string $conn_key) : Connection {
        $connection=static::$connections[$conn_key] ?? null;
        if (!$connection instanceof Connection) {
            if (!static::$dbConfig) {
                $app_config=Rust::getConfig();
                if (!$app_config || !$app_config instanceof AbstractConfig) {
                    throw new DBReadConfigException('app config read failed!' . "\t" . '[db]');
                }
                $dbConfig=$app_config->get('db');
                if (!$dbConfig || !is_array($dbConfig)) {
                    throw new DBReadConfigException('db config read failed!' . "\t" . '[db]');
                }
                static::$dbConfig=new Config($dbConfig);
            }
            $connection=new Connection(static::$dbConfig);
        }
        static::$connections[$conn_key]=$connection;
        return $connection;
    }
}