<?php
/**
 * Database Connection
 *
 * @author rustysun.cn@gmail.com
 */

namespace rust\dbo;

use rust\dbo\exception\DBOConnectionException;
use rust\dbo\exception\DBReadConfigException;
use rust\util\Config;

/**
 * Class Connection
 *
 * @package rust\dbo
 */
class Connection {
    const READ = 'read';
    const WRITE = 'write';
    const MASTER = 'mater';
    const SLAVE = 'slave';
    /**
     * @var Config
     */
    protected static $db_config;
    /**
     * @var array(new DBO) $dbo
     */
    protected $dbo = [];

    /**
     * get dbo instance
     *
     * @param string $name
     *
     * @return DBO
     * @throws DBOConnectionException
     */
    public function getDBO(string $name): DBO {
        $config = $this->getConnectConfig($name);
        $dsn = $config && isset($config['dsn']) ? $config['dsn'] : null;
        $user = $config && isset($config['user']) ? $config['user'] : null;
        $pass = $config && isset($config['pass']) ? $config['pass'] : null;
        $options = $config && isset($config['options']) ? $config['options'] : [];
        if (!$dsn || !$user) {
            throw new DBOConnectionException('database connection config read failed!');
        }
        $hash = md5($dsn . $user . $pass);
        $dbo = isset($this->dbo[$hash]) && $this->dbo[$hash] ? $this->dbo[$hash] : null;
        if (!$dbo) {
            $this->dbo[$hash] = new DBO($dsn, $user, $pass, $options);
        }
        return $this->dbo[$hash];
    }

    /**
     * Connection constructor.
     *
     * @param array $db_config
     */
    public function __construct($db_config) {
        static::$db_config = new Config($db_config);
    }

    /**
     * Create a DSN string from a configuration.
     *
     * @param $name
     *
     * @return array
     */
    protected function getConnectConfig($name = null) {
        //初始化
        $result = [
            'user' => static::$db_config->get('username'),
            'pass' => static::$db_config->get('password'),
        ];
        $driver = static::$db_config->get('driver');
        $database = static::$db_config->get('database');
        if (static::$db_config->get('read') || static::$db_config->get('slave') || static::$db_config->get('connections')) {
            $this->getMultiConnectionDSN($name, $driver, $database, $result);
        }
        return $result;
    }

    /**
     * connections or read/write or master/slave
     * TODO:轮询\加权轮询
     *
     * @param $name
     * @param $driver
     * @param $database
     * @param $result
     *
     * @throws DBReadConfigException
     */
    protected function getMultiConnectionDSN($name, $driver, $database, &$result) {
        if (!$name) {
            throw new DBReadConfigException('not found "name" parameter');
        }
        //db.connections
        $connections = self::$db_config->get('connections', true);
        if ($connections && !isset($connections[$name])) {
            $name = self::$db_config->get('default');
            if (!$name) {
                throw new DBReadConfigException('not found "default" parameter');
            }
        }
        if ($connections && isset($connections[$name]) && $connections[$name] && is_array($connections[$name])) {
            $config = $connections[$name];
            $driver = isset($config['driver']) ? $config['driver'] : $driver;
            $database = isset($config['database']) ? $config['database'] : $database;
            $host = isset($config['host']) ? $config['host'] : '';
            if (!$driver || !$database || !$host) {
                throw new DBReadConfigException('not found "driver","database" or "host" parameter.');
            }
            $result['dsn'] = vsprintf('%s:host=%s;dbname=%s', [$driver, $host, $database]);
            $result['user'] = $config['username'];
            $result['pass'] = $config['password'];
            return;
        }
        //db.read db.write db.master db.slave
        if ($config = self::$db_config->get($name, true)) {
            $host = $this->getConnectionHost($config);
            $driver = isset($config['driver']) ? $config['driver'] : $driver;
            $database = isset($config['database']) ? $config['database'] : $database;
            if (!$driver || !$database || !$host) {
                throw new DBReadConfigException('not found "driver","database" or "host" parameter!');
            }
            $result['dsn'] = vsprintf('%s:host=%s;dbname=%s', [
                $driver,
                $host,
                $database,
            ]);
        }
    }

    /**
     * @param Config $config
     *
     * @return mixed
     */
    protected function getSingleConnectionConfig(Config $config) {
        //unix socket
        $is_unix_socket = $config->get('unix_socket') ? true : false;
        if ($is_unix_socket) {
            return vsprintf('%s:unix_socket=%s;dbname=%s', [
                $config->get('driver'),
                $config->get('unix_socket'),
                $config->get('database'),
            ]);
        }
        return vsprintf('%s:host=%s;dbname=%s', [
            $config->get('driver'),
            $config->get('host'),
            $config->get('database'),
        ]);
    }

    /**
     * @param $conn_config
     *
     * @return null
     */
    protected function getConnectionHost($conn_config) {
        $host = null;
        if (isset($conn_config[0]) && $conn_config[0]) {
            $host_index = array_rand($conn_config);
            $host = $conn_config[$host_index];
        } else {
            if (isset($conn_config['host']) && is_array($conn_config['host'])) {
                $config_hosts = $conn_config['host'];
                $host_index = array_rand($config_hosts);
                $host = count($config_hosts) > 1 ? $config_hosts[$host_index] : $config_hosts[0];
            } else {
                if (isset($conn_config['host'])) {
                    $host = $conn_config['host'];
                }
            }
        }
        return $host;
    }

    /**
     * Set the modes for the connection.
     *
     * @param DBO         $dbo
     * @param  null|array $modes
     * @param bool        $is_strict
     *
     * @return void
     */
    protected function setModes(& $dbo, $modes, $is_strict = false) {
        $modes_config = $modes && is_array($modes) ? implode(',', $modes) : '';
        if ($modes_config) {
            $dbo->prepare(sprintf('set session sql_mode=\'%s\'', $modes))->execute();
            return;
        }
        if ($is_strict) {
            $dbo->prepare("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'")->execute();
            return;
        }
        $dbo->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
    }
}
