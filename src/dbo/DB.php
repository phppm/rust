<?php
/**
 * DB - database manager
 *
 * @author rustysun.cn@gmail.com
 */
namespace rust\dbo;
use rust\common\Config;
use rust\exception\RustException;
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
     * @var array[Config]
     */
    private static $_instances = [];
    /**
     * @var Connection
     */
    protected static $connection;

    /**
     * DB constructor.
     *
     * @param $db_config
     */
    private function __construct($db_config) {
        static::$connection = Connection::getInstance($db_config);
    }

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
        print_r($sqlMap);
        $database = Table::getInstance()->getDatabase($sqlMap['table']);
        var_dump($database);
        die;
        //获取 执行类型(Read or Write)
        $cmd = NULL;
        $exec_type = Connection::WRITE;
        $matches   = [];
        preg_match('/^\s*([^\s]+)\s+/', $sqlMap['sql'], $matches);
        if ($matches && isset($matches[1])) {
            $cmd = strtoupper($matches[1]);
        }
        if ($cmd === 'SELECT') {
            $exec_type = Connection::READ;
        }
        return static::$connection->getDBO($exec_type)->execute($sql, $exec_params);
    }

    /**
     * Execute sql or command
     *
     * @param string|Command $obj
     * @param array          $bind_params
     * @param null           $exec_type
     *
     * @return Statement
     * @throws RustException
     */
    public function execute($obj, $bind_params = [], $exec_type = NULL) {
        if (!$obj) {
            throw new RustException(1005);
        }
        //获取要执行SQL
        $sql         = $obj;
        $exec_params = [];
        if ($obj instanceof Command) {
            $sql         = $obj->toString();
            $exec_params = $obj->getBindParams();
            foreach ($bind_params as $key => $value) {
                if (is_string($key)) {
                    $exec_params[$key] = $value;
                } else {
                    array_push($exec_params, $value);
                }
            }
        }
        //获取 执行类型(Read or Write)
        $cmd = NULL;
        if (!$exec_type) {
            $exec_type = Connection::WRITE;
            $matches   = [];
            preg_match('/^\s*([^\s]+)\s+/', $sql, $matches);
            if ($matches && isset($matches[1])) {
                $cmd = strtoupper($matches[1]);
            }
            if ($cmd === 'SELECT') {
                $exec_type = Connection::READ;
            }
        }
        return static::$connection->getDBO($exec_type)->execute($sql, $exec_params);
    }

    /**
     * @return DB
     */
    public static function getInstance() {
        $app_config = Rust::getConfig();
        if (!$app_config || !$app_config instanceof Config) {
            //TODO:抛出异常
        }
        $db_config        = $app_config->get('db');
        self::$_instances = new DB($db_config);
        return self::$_instances[$db_config['default']];
    }

    /**
     * the last insert id
     *
     * @return string
     */
    public function lastInsertId() {
        return static::$connection->getDBO(Connection::WRITE)->lastInsertId();
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