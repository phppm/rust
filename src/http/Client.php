<?php
namespace rust\http;
use rust\util\Log;
use \swoole_async_dns_lookup;
use \swoole_http_client as SwooleHttpClient;

class Client {
    /**
     * @param array      $request
     * @param null|array $setting
     * @param null       $callback
     * @param null|array $params
     */
    public static function doAsyncRequest($request, $setting = NULL, $callback = NULL, $params = NULL) {
        swoole_async_dns_lookup($request['domain'], function ($host, $ip) use ($request, $callback, $setting, $params) {
            if (!$ip) {
                Log::write("NOT FOUND IP", 'http_request');
                return;
            }
            if (!$params) {
                $params = [];
            }
            if (!is_array($params)) {
                $params = [$params];
            }
            $headers = isset($request['headers']) ? $request['headers'] : [];
            $cli = new SwooleHttpClient($ip, 443, TRUE);
            if ($setting) {
                $cli->set($setting);
            }
            $cli->setHeaders(array_merge([
                'Host' => $host,
            ], $headers));
            if (isset($request['method']) && 'get' === $request['method']) {
                $cli->get($request['path'], function ($cli) use ($callback, $request, $params) {
                    array_unshift($params, $cli->body);
                    if (!call_user_func_array($callback, $params)) {
                        Log::write([
                            'error' => $cli->body,
                            'data'  => $request['data'],
                            'url'   => $request['domain'] . $request['path'],
                        ], 'http_error');
                        return;
                    }
                    Log::write($cli->body, 'http_request');
                });
                return;
            }
            //post请求
            $cli->post($request['path'], $request['data'], function ($cli) use ($callback, $request, $params) {
                if (is_callable($callback)) {
                    array_unshift($params, $cli->body);
                    if (!call_user_func_array($callback, $params)) {
                        Log::write([
                            'error' => $cli->body,
                            'data'  => $request['data'],
                            'url'   => $request['domain'] . $request['path'],
                        ], 'http_error');
                        return;
                    }
                }
                Log::write($cli->body, 'http_request');
            });
        });
    }
}