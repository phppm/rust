<?php
namespace rust\web;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use rust\exception\HttpException;
use rust\stream\LazyOpenStream;
use rust\stream\StreamUtil;

/**
 * Class WebRequest
 *
 * @package rust\web
 */
class WebRequest implements RequestInterface {
    use WebMessageTrait;
    /** @var string */
    private $method;
    /** @var null|string */
    private $requestTarget;
    /** @var null|Uri */
    private $uri;
    //
    private $format;
    private $requestTime;
    private $requestTimeMS;
    private $remoteAddress;
    /**
     * @var null|RouteInfo $routedInfo
     */
    private $routedInfo;
    private $parameters;
    private $cookies;
    private $parsedBody;
    private $queryParameters;
    private $files;

    public function __construct() {
        $this->routedInfo=null;
    }

    /**
     * 根据服务器环境 变量 初始化
     */
    public function initRequestByServerEnv() {
        //init request time
        $this->requestTime=$_SERVER['REQUEST_TIME'] ?? null;
        $this->requestTimeMS=$_SERVER['REQUEST_TIME_FLOAT'] ?? null;
        $this->requestTimeMS=$this->requestTimeMS ? $this->requestTimeMS * 10000 : null;
        //init ip
        $this->remoteAddress=$_SERVER['REMOTE_ADDR'] ?? null;
        //----------
        $protocol=$_SERVER['SERVER_PROTOCOL'] ?? '';
        $version=$protocol ? str_replace('HTTP/', '', $protocol) : '1.1';
        $method=$_SERVER['REQUEST_METHOD'] ?? 'GET';
        $method=$method ? $method : 'GET';
        $uri=$this->initUriByServerEnv();
        //get headers
        $headers=function_exists('getallheaders') ? getallheaders() : [];
        if (!$headers) {
            $headers=[];
            foreach ($_SERVER as $name=>$value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))]=$value;
                }
            }
        }
        //body
        $body=new LazyOpenStream('php://input', 'r+');
        $this->createRequest($method, $uri, $headers, $body, $version);
    }

    /**
     * @param string $method
     * @param Uri $uri
     * @param array $headers
     * @param null $body
     * @param string $version
     */
    protected function createRequest($method, $uri, array $headers=[], $body=null, $version='1.1') {
        if (!($uri instanceof UriInterface)) {
            $uri=new Uri($uri);
        }
        $this->method=strtoupper($method);
        $this->uri=$uri;
        $this->setHeaders($headers);
        $this->protocol=$version;
        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }
        //初始化stream
        if ($body !== '' && $body !== null) {
            $this->stream=StreamUtil::streamFor($body);
        }
    }

    /**
     * @return Uri
     */
    protected function initUriByServerEnv() {
        $uri=new Uri('');
        $env_https=$_SERVER['HTTPS'] ?? 'off';
        if ($env_https) {
            $uri=$uri->withScheme($env_https == 'on' ? 'https' : 'http');
        }
        $env_host=getenv('HTTP_HOST');
        $env_host=$env_host ? $env_host : getenv('SERVER_NAME');
        if ($env_host) {
            $uri=$uri->withHost($env_host);
        }
        $env_port=getenv('SERVER_PORT');
        if ($env_port) {
            $uri=$uri->withPort($env_port);
        }
        $env_uri=getenv('REQUEST_URI');
        if ($env_uri) {
            $uri=$uri->withOriginalUri($env_uri);
        }
        $pathInfo=getenv('PATH_INFO');
        $path=$pathInfo ? $pathInfo : $env_uri;
        if ($path) {
            $path=current(explode('?', $path));
            $uri=$uri->withPath($path);
        }
        //TODO:remove默认格式
        $format='json';
        if ($path && false !== strpos('.', $format)) {
            $pathInfo=explode('.', $path);
            $format=$pathInfo && is_array($pathInfo) ? array_pop($pathInfo) : $format;
        }
        $this->format=strtolower($format);
        $env_query=getenv('QUERY_STRING');
        if ($env_query) {
            $uri=$uri->withQuery($env_query);
        }
        return $uri;
    }

    public function getCookies() {
        return $this->cookies;
    }

    /**
     * @param array $cookies
     *
     * @return WebRequest
     */
    public function withCookies(array $cookies) {
        $new=clone $this;
        $new->cookies=$cookies;
        return $new;
    }

    public function getFiles() {
        return $this->files;
    }

    public function withFiles($files) {
        $new=clone $this;
        $new->files=$files;
        return $new;
    }

    public function getParsedBody() {
        return $this->parsedBody;
    }

    public function withParsedBody($data) {
        $new=clone $this;
        if (!$data) {
            $data=$new->getBody()->getContents();
            $jsonDecoder=$data && is_string($data) ? json_decode($data, true) : $data;
            $data=$jsonDecoder ? $jsonDecoder : $data;
        }
        $new->parsedBody=$data;
        return $new;
    }

    public function getQueryParameters() {
        return $this->queryParameters;
    }

    public function withQueryParameters($data) {
        $new=clone $this;
        $new->queryParameters=$data;
        return $new;
    }

    public function getMethod() {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return WebRequest
     */
    public function withMethod($method) {
        $new=clone $this;
        $new->method=strtoupper($method);
        return $new;
    }

    public function getRemoteIP() {
        return $this->remoteAddress;
    }

    public function getRequestTarget() {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }
        $target=$this->uri->getPath();
        if ($target == '') {
            $target='/';
        }
        if ($this->uri->getQuery() != '') {
            $target.='?' . $this->uri->getQuery();
        }
        return $target;
    }

    /**
     * @param mixed $requestTarget
     *
     * @return WebRequest
     */
    public function withRequestTarget($requestTarget) {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
        }
        $new=clone $this;
        $new->requestTarget=$requestTarget;
        return $new;
    }

    public function getUri() {
        return $this->uri;
    }

    /**
     * @param UriInterface $uri
     * @param bool $preserveHost
     *
     * @return $this|WebRequest
     */
    public function withUri(UriInterface $uri, $preserveHost=false) {
        if ($uri === $this->uri) {
            return $this;
        }
        $new=clone $this;
        $new->uri=$uri;
        if (!$preserveHost) {
            $new->updateHostFromUri();
        }
        return $new;
    }

    /**
     * Gets the real remote IP address.
     *
     * @return null|string
     */
    public function getProxyIpAddress() {
        static $forwarded=[
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
        ];
        $flags=FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        foreach ($forwarded as $key) {
            if (!array_key_exists($key, $_SERVER)) {
                continue;
            }
            sscanf($_SERVER[$key], '%[^,]', $ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false) {
                return $ip;
            }
        }
        return null;
    }

    /**
     * @param string $name
     * @param null $initValue
     *
     * @return null|mixed
     * @throws HttpException
     */
    public function getParameter($name, $initValue=null) {
        if (!isset($this->parameters[$name])) {
            if (null !== $initValue) {
                return $initValue;
            }
            throw new HttpException(2001, 'not found parameter "' . $name . '"');
        }
        return $this->parameters[$name];
    }

    public function getParameters() {
        return $this->parameters;
    }

    public function getFormat() {
        $format=$this->format ? strtolower($this->format) : null;
        return $format;
    }

    /**
     * 获取 请求的版本号
     *
     * @param string $key
     *
     * @return null
     */
    public function getRequestVersion($key='ver') {
        $version=$this->getParameter($key);
        $version=$version ? $version : null;
        return $version;
    }

    /**
     * @param null $format
     *
     * @return int|string
     */
    public function getRequestTime($format=null) {
        $result=intval($this->requestTime);
        if (!empty($format)) {
            $result=date($format, $result);
        }
        return $result;
    }

    /**
     * @return mixed
     */
    public function getRequestTimeMS() {
        return $this->requestTimeMS;
    }

    /**
     * 获取路由信息
     *
     * @return null|RouteInfo
     */
    public function getRouteInfo():?RouteInfo {
        return $this->routedInfo;
    }

    /**
     * 是否已路由
     *
     * @return bool
     */
    public function isRouted() {
        return null!==$this->getRouteInfo() ? true : false;
    }

    /**
     * @param array $files
     *
     * @return array
     */
    public function normalizeFiles(array $files) {
        $normalized=[];
        foreach ($files as $key=>$value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key]=$value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key]=$this->createUploadedFileFromSpec($value);
            } elseif (is_array($value)) {
                $normalized[$key]=$this->normalizeFiles($value);
                continue;
            } else {
                throw new InvalidArgumentException('Invalid value in files specification');
            }
        }
        return $normalized;
    }

    /**
     * @param array $parameters
     */
    protected function setParameters(array $parameters) {
        $this->parameters=$parameters;
    }

    /**
     * @param RouteInfo $routeInfo
     */
    public function setRouteInfo(RouteInfo $routeInfo) {
        $this->routedInfo=$routeInfo;
    }

    /**
     * @param array $files
     *
     * @return array
     */
    private function normalizeNestedFileSpec(array $files=[]) {
        $normalizedFiles=[];
        foreach (array_keys($files['tmp_name']) as $key) {
            $spec=[
                'tmp_name'=>$files['tmp_name'][$key],
                'size'=>$files['size'][$key],
                'error'=>$files['error'][$key],
                'name'=>$files['name'][$key],
                'type'=>$files['type'][$key],
            ];
            $normalizedFiles[$key]=$this->createUploadedFileFromSpec($spec);
        }
        return $normalizedFiles;
    }

    /**
     * @param array $value
     *
     * @return array|UploadedFile
     */
    private function createUploadedFileFromSpec(array $value) {
        if (is_array($value['tmp_name'])) {
            return $this->normalizeNestedFileSpec($value);
        }
        return new UploadedFile($value['tmp_name'], (int)$value['size'], (int)$value['error'], $value['name'], $value['type']);
    }

    private function updateHostFromUri() {
        $host=$this->uri->getHost();
        if ($host == '') {
            return;
        }
        if (($port=$this->uri->getPort()) !== null) {
            $host.=':' . $port;
        }
        if (isset($this->headerNames['host'])) {
            $header=$this->headerNames['host'];
        } else {
            $header='Host';
            $this->headerNames['host']='Host';
        }
        // Ensure Host is the first header.
        // See: http://tools.ietf.org/html/rfc7230#section-5.4
        $this->headers=[$header=>[$host]] + $this->headers;
    }
}