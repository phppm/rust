<?php
namespace rust\swoole\http;
use rust\util\Log;
use swoole_async_dns_lookup;
use swoole_http_client as SwooleHttpClient;

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
            $cli     = new SwooleHttpClient($ip, 443, TRUE);
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

    /**
     * @param string $url
     * @param array  $data
     * @param array  $header
     *
     * @return bool
     */
    public static function doCurlRequest($url, $data, $header = NULL) {
        $postUrl = $url;
        $ch      = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $body = curl_exec($ch);//运行curl
        curl_close($ch);
        Log::write($body, 'http_request');
        return TRUE;
    }
}