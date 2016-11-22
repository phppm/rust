<?php
/**
 * URL处理
 * @author rustysun.cn@gmail.com
 */
namespace rust\http;

use rust\exception\RustException;

/**
 * Class URL
 *
 * @package rust\http
 */
class URL {
    protected static $domain;
    protected static $main_domain;

    /**
     * @param bool $method
     * @return null|string
     */
    protected static function protocol($method = FALSE) {
        if ($method === 'cli') {
            return NULL;
        }
        if (!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] === 'on') {
            return 'https';
        }
        return 'http';
    }

    /**
     * URL跳转
     * @param string $uri
     * @param string $status
     * @throws RustException
     */
    public static function redirect($uri = '', $status = '302') {
        $codes = [
            'refresh' => 'Refresh',
            '300'     => 'Multiple Choices',
            '301'     => 'Moved Permanently',
            '302'     => 'Found',
            '303'     => 'See Other',
            '304'     => 'Not Modified',
            '305'     => 'Use Proxy',
            '307'     => 'Temporary Redirect'
        ];
        $status = isset($codes[$status]) ? (string) $status : '302';

        if ($status === '300') {
            $uri = (array) $uri;
            $output = '<ul>';
            foreach ($uri as $link) {
                $output .= '<li><a href="' . $uri . '">' . $uri . '</a></li>';
            }
            $output .= '</ul>';
            $uri = $uri[0];
        } else {
            $output = '<p><a href="' . $uri . '">' . $uri . '</a></p>';
        }

        if (FALSE === strpos($uri, '://')) {
            $uri = URL::site($uri);
        }

        if ($status === 'refresh') {
            header('Refresh: 0; url=' . $uri);
        } else {
            header('HTTP/1.1 ' . $status . ' ' . $codes[$status]);
            header('Location: ' . $uri);
        }
        exit('<h1>' . $status . ' - ' . $codes[$status] . '</h1>' . $output);
    }

    /**
     * 生成站点URL
     * @param string $site
     * @return string
     * @throws RustException
     */
    public static function site($site = '') {
        $schema = self::protocol() . '://';
        if (!$schema || !self::$domain || !self::$domain) {
            throw new RustException(1002);
        }
        if (!$site) {
            return $schema . self::$main_domain . self::$domain;
        }
        return $schema . $site . self::$domain;
    }

    /**
     * 生成页面URL
     *
     * @param string $path
     * @param string $site
     * @param array $params
     *
     * @return string
     */
    public static function create($path, $site = '', $params = []) {
        $site_url = '';
        if ($site && is_string($site)) {
            $site_url = self::site($site);
        } else if ($site && is_array($site)) {
            $params = $site;
        }
        $url = $path;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        return $site_url . $url;
    }

    /**
     * @param $domain
     * @param string $main_domain
     */
    public static function setDomain($domain, $main_domain) {
        self::$domain = $domain;
        self::$main_domain = $main_domain;
    }
}