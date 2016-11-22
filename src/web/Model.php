<?php
namespace rust\web;

use rust\dbo\DB;
use rust\util\Config;
use rust\util\Registry;
use rust\exception\storage\CacheServerException;
use rust\exception\ErrorCode;
use \Exception;
use \Redis;

/**
 * Class Model
 *
 * @package rust\web
 * @author  rustysun.cn@gmail.com
 */
abstract class Model {
    /**
     * @var \rust\dbo\DB
     */
    private static $_db   = NULL;
    protected      $table = '', $_autoPrefix = FALSE, $_tablePrefix = '';

    /**
     * Model constructor.
     */
    final public function __construct() {
        self::$_db = DB::getInstance();
    }

    /**
     * @return string
     */
    final public function getClassName() {
        return get_class($this);
    }

    /*
     * count records
     *
     * @param $condition string
     * @param $bindParams array
     *
     * @return total records num
     */
    final public function count($where, $bindParams = []) {
        $table = $this->getTableName();
        $cmd = self::$_db->createCommand()->select('COUNT(*)')->table($table)->where($where, $bindParams);
        $stmt = self::$_db->execute($cmd);
        $r = $stmt->fetch();
        return isset($r[0]) ? $r[0] : $r;
    }

    /*
     * delete records
     *
     * @param $condition string 条件
     * @param $bindParams array 监听参数数组
     *
     * @return TRUE|FALSE
     */
    protected function delete($condition, $bindParams = []) {
        if (!$condition) {
            return NULL;
        }
        $table = $this->getTableName();
        //生成sql
        $cmd = self::$_db->createCommand()->delete($table)->where($condition);
        //SQL预处理
        $st = self::$_db->execute($cmd, $bindParams);
        return $st->rowCount();
    }

    /*
     * insert record
     *
     * @param $data array
     * @param $sets array
     *
     * @return last insert id
     */
    protected function insert($data, $sets = []) {
        $table = $this->getTableName();
        $cmd = self::$_db->createCommand()->insert($table, $data, $sets);
        self::$_db->execute($cmd);
        return self::$_db->lastInsertId();
    }

    /*
     * 判断相关信息是否已存在
     *
     * @param $condition string 条件
     * @param $bindParams array 监听参数数组
     *
     * @return true or false(true:相关内容已存在，false:不存在)
     */
    protected function isExists($condition, $bindParams = []) {
        $r = NULL;
        $table = $this->getTableName();
        $cmd = self::$_db->createCommand()->select('*')->table($table)->where($condition, $bindParams);
        $st = self::$_db->execute($cmd);
        if (!$st) {
            return NULL;
        }
        $row = $st->fetchArray();
        $r = FALSE;
        if (!empty($row)) {
            $r = $row[0];
        }

        return $r;
    }

    /*
     * 设置自动增加前缀状态
     *
     * @param $status boolean 状态
     * @return 无
     */
    final function setAutoPrefix($status) {
        $this->_autoPrefix = $status;
    }

    final public function getLastSQL() {
        return self::$_db->getLastSQL();
    }

    /*
     * 求平均
     *
     * @param string $where 条件
     * @param $bindParams array 监听参数数组
     * @param $fields
     *
     * @return 结果数组
     */
    protected function getAvg($where, $bindParams = [], $fields) {
        if (!$where) {
            return NULL;
        }
        //生成sql
        $table = $this->getTableName();
        $cmd = self::$_db->createCommand()->select($fields)->table($table)->where($where, $bindParams);
        $rs = self::$_db->execute($cmd);
        return (array) $rs->fetch(\PDO::FETCH_ASSOC);
    }

    /*
     * 获取信息列表
     *
     * @param $options array 参数数组
     * @param $bindParams array 监听参数数组
     * @param $fields string 返回字段列表
     *
     * @return 数据结果集
     */
    protected function getList($options = [], $bindParams = [], $fields = '*') {
        if (!is_array($options)) {
            return [];
        }
        $table = $this->getTableName();
        $cmd = self::$_db->createCommand()->select($fields)->table($table);
        if (isset($options['where']) && $options['where']) {
            $cmd->where($options['where']);
        }
        $st = self::$_db->execute($cmd, $bindParams);
        return $st;
    }

    /*
     * 获取单个信息
     *
     * @param string $where 条件
     * @param $bindParams array 监听参数数组
     *
     * @return 结果数组
     */
    protected function getOne($where, $bindParams = []) {
        if (!$where) {
            return NULL;
        }
        //生成sql
        $table = $this->getTableName();
        $cmd = self::$_db->createCommand()->select('*')->table($table)->where($where, $bindParams)->orderBy('id DESC')->limit(1);
        $rs = self::$_db->execute($cmd);
        return $rs->fetchObject();
    }

    /*
     * 修改相关信息
     *
     * @param $data array 要更新的数据
     * @param $condition string 更新条件
     * @param $bindParams array 监听参数数组
     *
     * @return 执行结果
     */
    protected function update($data, $condition, $bindParams = []) {
        if (!is_array($data)) {
            return NULL;
        }
        $table = $this->getTableName();
        $cmd = self::$_db->createCommand();
        $cmd->update($table, $data)->where($condition);

        return self::$_db->execute($cmd, $bindParams)->rowCount();
    }

    /*
     * 获取表名
     *
     * @access 私有
     * @param $table string 表名
     * @return 附加前缀后的表名
     */
    protected function getTableName() {
        $table = $this->table;
        $prefix = $this->_autoPrefix ? $this->_tablePrefix : '';
        if ($prefix && strpos($table, $prefix) === FALSE) {
            $table = $prefix . $table;
        }
        return '`' . $table . '`';
    }
    /*
$app_config = $this->getConfig();
$redis_config = new Config($app_config->get('redis'));
    */

    /**
     * 获取redis实例
     *
     * @return Redis
     * @throws CacheServerException
     */
    protected function getCacheInstance() {
        static $redis;
        if (!$redis) {
            $redis = new Redis();
        }
        if (!$redis) {
            throw new CacheServerException(ErrorCode::CACHE_SERVER_NOT_FOUND);
        }
        try {
            $ping_result = $redis->ping();
        }
        catch (Exception $e) {
            $ping_result = NULL;
        }
        if (!$ping_result || '+PONG' !== $ping_result) {
            $app_config = Registry::get('app_config');
            if (!$app_config || !$app_config instanceof Config) {
                //TODO:抛出异常
            }
            $config = new Config($app_config->get('redis'));
            $connected = @$redis->connect($config->get('host'), $config->get('port'));
            if (!$connected) {
                throw new CacheServerException(ErrorCode::CACHE_SERVER_CONNECT_FAILED);
            }
        }
        return $redis;
    }
}