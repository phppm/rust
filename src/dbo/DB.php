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
     * @var Command
     */
    protected $command;
    /**
     * @var [Connection]
     */
    protected static $connections;

    /**
     * Create DB Command
     *
     * @return Command
     */
    public function createCommand() {
        $this->command = new Command();
        return $this->command;
    }

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
        return $connection->getDBO($conn_key)->execute($sqlMap['sql']);
    }

    /**
     * 获取 最近执行的SQL
     *
     * @return string
     */
    final public function getLastSQL() {
        return $this->command->toString();
    }
}