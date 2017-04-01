<?php
/**
 * DB - database manager
 *
 * @author rustysun.cn@gmail.com
 */

namespace rust\dbo;

use rust\common\Config;
use rust\dbo\exception\DBException;
use rust\dbo\exception\DBOException;
use rust\dbo\exception\DBReadConfigException;
use rust\Rust;
use rust\util\Log;

/**
 * DB
 */
class DB {
    /**
     * @var array $connections
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
    public static function exec($sid, $data, array $options = []) {
        $sqlMap = null;
        try {
            $sqlMap = SqlMap::getInstance()->getSql($sid, $data, $options);
            $conn_key = Table::getInstance()->getDatabase($sqlMap['table']);
            $dboResult = static::getConnection($conn_key)->getDBO($conn_key)->execute($sqlMap);
            $formatter = new ResultFormatter($dboResult, $sqlMap['result_type']);
            return $formatter->format();
        } catch (DBOException $e) {
            $msg = $e->getMessage() or '数据库执行出错了';
            Log::write($sqlMap, 'dbo_error');
            throw new DBException($msg);
        }
    }

    /**
     * @param string $sid
     * @param array  $data
     * @param array  $options
     *
     * @return Statement
     */
    public static function execQuery(string $sid, array $data, $options = []): Statement {
        $result = static::exec($sid, $data, $options);
        if (null === $result || !$result instanceof Statement) {
        }
        return $result;
    }

    /**
     * @param string $sid
     * @param array  $data
     * @param array  $options
     *
     * @return int
     */
    public static function execUpdate(string $sid, array $data, array $options = []): int {
        $result = static::exec($sid, $data, $options);
        if (null === $result || !is_int($result)) {
        }
        return $result;
    }

    /**
     * 获取数据库连接
     *
     * @param string $conn_key
     *
     * @return Connection
     * @throws DBReadConfigException
     */
    private static function getConnection(string $conn_key): Connection {
        $connection = static::$connections[$conn_key]??null;
        if (!$connection instanceof Connection) {
            $app_config = Rust::getConfig();
            if (!$app_config || !$app_config instanceof Config) {
                throw new DBReadConfigException();
            }
            $db_config = $app_config->get('db');
            $connection = new Connection($db_config);
        }
        static::$connections[$conn_key] = $connection;
        return $connection;
    }
}