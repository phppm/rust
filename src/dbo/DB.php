<?php
/**
 * DB - database manager
 *
 * @author rustysun.cn@gmail.com
 */
namespace rust\dbo;
use rust\common\Config;
use rust\Rust;

/**
 * DB
 */
class DB {
    /**
     * @var [Connection]
     */
    protected static $connections;

    /**
     * @param       $sid
     * @param       $data
     * @param array $options
     *
     * @return int|null|Statement|string
     */
    public static function exec($sid, $data, $options = []) {
        $sqlMap   = SqlMap::getInstance()->getSql($sid, $data, $options);
        $conn_key = Table::getInstance()->getDatabase($sqlMap['table']);
        if (!isset(static::$connections[$conn_key])) {
            $app_config = Rust::getConfig();
            if (!$app_config || !$app_config instanceof Config) {
                //TODO:抛出异常
                return NULL;
            }
            $db_config                      = $app_config->get('db');
            static::$connections[$conn_key] = new Connection($db_config);
        }
        $connection = static::$connections[$conn_key];
        if (!$connection instanceof Connection) {
            //TODO:抛出异常
            return NULL;
        }
        $dboResult = $connection->getDBO($conn_key)->execute($sqlMap);
        $formatter = new ResultFormatter($dboResult, $sqlMap['result_type']);
        return $formatter->format();
    }
}