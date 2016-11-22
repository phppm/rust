<?php
namespace rust\http;

use rust\util\XSSClean;

class Cookie {
    /**
     * @var array[Config]
     */
    private static $_instances = [];
    /**
     * @var \rust\util\Config
     */
    protected $config;

    protected $prefix = '';

    private function __clone() {
    }

    /**
     * This is a static helper class, no instance can be created.
     */
    final private function __construct($config) {
        $this->config = $config;
    }

    /**
     * @param \rust\util\Config $config cookie配置信息
     */
    public static function getInstance($config) {
        $hash = $config->getHashKey();
        if (isset(self::$_instances[$hash])) {
            return self::$_instances[$hash];
        }
        self::$_instances[$hash] = new Cookie($config);
        return self::$_instances[$hash];
    }

    /**
     * @param $name
     * @param null $value
     * @param null $expire
     * @param array $params
     * @return bool
     */
    public function set($name, $value = NULL, $expire = NULL, $params = []) {
        if (headers_sent()) {
            return FALSE;
        }
        $config = array_merge($this->getConfig(), $params);
        if ($expire !== 0) {
            //TODO:是什么结果?
            $expire += time();
        }
        if (strlen($config['salt']) && $value) {
            //TODO:?
            $value = $this->getSalt($name, $value, $config['salt']) . '~' . $value;
        }
        $path = isset($config['path']) ? $config['path'] : '/';
        $domain = isset($config['domain']) ? $config['domain'] : NULL;
        $secure = isset($config['secure']) ? $config['secure'] : FALSE;
        $http_only = isset($config['http_only']) ? $config['http_only'] : TRUE;
        return setcookie($name, $value, $expire, $path, $domain, $secure, $http_only);
    }

    /**
     * Fetch a cookie value from a signed cookie or an array of cookies if no cookie name is
     * specified.
     *
     * @param   string $name Cookie name
     * @param   mixed $default Default value
     * @param   boolean $xss_clean Use XSS cleaning on the value
     * @return  mixed Cookie value or array of cookies
     */
    public function get($name = NULL, $default = NULL, $xss_clean = FALSE) {
        if ($name === NULL) {
            $cookies = [];
            foreach ($_COOKIE AS $key => $value) {
                $cookies[$key] = $this->get($key, $default, $xss_clean);
            }
            return $cookies;
        }
        if (!isset($_COOKIE[$name])) {
            return $default;
        }
        $cookie = $_COOKIE[$name];
        $config = $this->getConfig();
        $salt = $config['salt'];
        if (!$salt) {
            return $cookie;
        }
        $split = strlen($this->getSalt($name, NULL, $salt));
        if (isset($cookie[$split]) && $cookie[$split] === '~') {
            list ($hash, $value) = explode('~', $cookie, 2);
            if ($this->getSalt($name, $value, $salt) === $hash) {
                if ($xss_clean === TRUE && $config['xss_filtering'] === FALSE) {
                    return XSSClean::doClean($value);
                }
                return $value;
            }
            $this->del($name);
        }
        return $default;
    }

    /**
     * 删除Cookie
     *
     * @param   string $name Cookie名称
     * @param   string $path URL path
     * @param   string $domain URL domain
     * @return  boolean
     */
    public function del($name, $path = NULL, $domain = NULL) {
        unset($_COOKIE[$name]);
        return $this->set($name);
    }

    /**
     * Generates a salt string for a cookie based on the name and value.
     *
     * @param    string $name Name of cookie
     * @param    string $value Value of cookie
     * @param null $salt
     * @return    string   SHA1 hash
     */
    protected function getSalt($name, $value, $salt = NULL) {
        $result = $salt ? hash_hmac('ripemd160', $name . $value, $salt) : sha1($name . $value . $name);
        return $result;
    }

    /**
     * 获取cookie配置信息
     * @return array
     */
    protected function getConfig() {
        $config = $this->config;
        $result = [
            'expire'    => $config->get('expire'),
            'domain'    => $config->get('domain'),
            'path'      => $config->get('path'),
            'secure'    => $config->get('secure'),
            'http_only' => $config->get('http_only'),
            'salt'      => $config->get('salt'),
        ];
        return $result;
    }
}