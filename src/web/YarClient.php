<?php
namespace rust\web;

use \Yar_Concurrent_Client;

final class YarClient {
    private static $host = 'http://api.mogu.dev/';
    private static $synClients = [];

    /**
     * @param $urls
     * @param $return
     * @return bool
     */
    public static function batch($urls, &$return) {
        if (empty($urls)) {
            $return = [];
            return FALSE;
        }
        $cnt = count($urls);
        if ($cnt === 1) {
            $u = $urls[0][0];
            $p = $urls[0][1];
            $return[$u] = self::call($u, $p);
            return TRUE;
        }

        for ($i = 0; $i < $cnt; $i++) {
            $u = $urls[$i][0];
            $p = $urls[$i][1];
            self::call($u, $p, function ($data) use (&$return, $u) {
                $return[$u] = $data;
            });
        }
        self::loop();
    }

    /**
     * @param $path
     * @param array $parameter
     * @param null $callback
     * @return bool|mixed
     */
    public static function call($path, $parameter = [], $callback = NULL) {
        $pos = strrpos($path, ".");
        if (FALSE === $pos) {
            return FALSE;
        }
        $action = substr($path, $pos + 1);
        $path = substr($path, 0, $pos);

        if (NULL === $callback) {
            return self::synCall($path, $action, $parameter);
        }
        self::conCall($path, $action, $parameter, $callback);
    }

    /**
     * @param null $callback
     * @param null $error_callback
     */
    public static function loop($callback = NULL, $error_callback = NULL) {
        Yar_Concurrent_Client::loop($callback, $error_callback);
    }


    /**
     * @param $path
     * @param $action
     * @param $parameter
     * @return mixed
     */
    private static function synCall($path, $action, $parameter) {
        $client = self::getSynClient($path);
        $ret = call_user_func_array([$client, $action], $parameter);
        return $ret;
    }

    /**
     * @param $path
     * @param $action
     * @param $parameter
     * @param $callback
     */
    private static function conCall($path, $action, $parameter, $callback) {
        $url = self::$host . str_replace('.', '/', $path);
        $parameter = [
            'p' => $parameter,
        ];
        Yar_Concurrent_Client::call($url, $action, $parameter, $callback);
    }

    /**
     * @param $path
     * @return mixed|\Yar_Client
     */
    private static function getSynClient($path) {
        if (isset(self::$synClients[$path])) {
            return self::$synClients[$path];
        }
        $url = self::$host . str_replace('.', '/', $path);
        /*
        if (Config::get('debug')) {
            $url .= '?online_debug=true';
        }
        */
        $client = new \Yar_Client($url);
        self::$synClients[$path] = $client;
        return $client;
    }
}