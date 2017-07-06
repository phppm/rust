<?php
/**
 * controller base class
 *
 * @author rustysun.cn@gmail.com
 */
namespace rust\web;

use rust\common\Config;
use rust\exception\view\ViewInstanceNotFoundException;
use rust\http\URL;
use rust\interfaces\IController;

/**
 * Class Controller
 *
 * @package rust\web
 */
abstract class Controller implements IController {
    private $_env=[];
    /**
     * @var View
     */
    private $_view;
    /**
     * @var Config
     */
    private $_config;
    /**
     * @var WebRequest
     */
    private $_request;
    /**
     * @var WebResponse
     */
    private $_response;

    /*
     * 阻止clone
     */
    private function __clone() {
    }

    /**
     * Controller constructor.
     *
     * @param WebRequest  $request
     * @param WebResponse $response
     * @param Config      $config
     */
    final public function __construct(WebRequest $request=null, WebResponse &$response=null,
        Config $config=null) {
        $this->_config=$config;
        $this->_request=$request;
        $this->_response=$response;
        $this->_view=null;
        //写入公共环境变量
        $this->env('http_request', $request);
    }

    /*
     * 初始化
     * @return bool
     */
    public function init() {
        $base_uri=$this->_config->get('route.base_uri');
        $this->_env['base_url']=$base_uri;
        return true;
    }

    /*
     * 设置或读取环境变量
     * @param $key
     * @param null $val
     * @return bool|mixed|null
     */
    final public function env($key, $val=null) {
        if (null !== $val) {
            $this->_env[$key]=$val;
        } elseif (is_array($key)) {
            if (!$key) {
                return false;
            }
            foreach ($key as $k=>$v) {
                $this->_env[$k]=$v;
            }
        } else {
            if (isset($this->_env[$key])) {
                return $this->_env[$key];
            }
            return null;
        }
        return true;
    }

    /*
     * 往视图写入数据
     * @param $name
     * @param null $value
     */
    final public function assign($name, $value=null) {
        if (!$this->_view) {
            throw new ViewInstanceNotFoundException;
        }
        return $this->_view->assign($name, $value);
    }

    /*
     * 显示视图
     * @param $tpl
     */
    final public function display($tpl) {
        $this->_response->write($this->render($tpl));
        $this->_response->send();
    }

    /**
     * 视图结束
     */
    final public function end() {
        if (!$this->_view) {
            throw new ViewInstanceNotFoundException;
        }
        $this->_view->end();
    }

    /*
     * 渲染视图
     * @param $tpl
     * @return mixed
     */
    final public function render($tpl) {
        if (!$this->_view) {
            throw new ViewInstanceNotFoundException;
        }
        //强制将环境变量 作为common数据 赋给模板
        $this->assign('common', $this->_env);
        return $this->_view->render($tpl);
    }

    /**
     * 获取配置实例
     *
     * @return Config
     */
    final public function getConfig() {
        return $this->_config;
    }

    /*
     * 获取request实例
     */
    final public function getRequest() {
        return $this->_request;
    }

    /*
     * 获取response实例
     */
    final public function getResponse() {
        return $this->_response;
    }

    /*
     * 获取view实例
     */
    final public function getView() {
        return $this->_view;
    }

    /**
     * @param View $view
     */
    final public function setView(View $view) {
        $this->_view=$view;
    }

    /**
     * 页面转向
     *
     * @param string $path   跳转路径
     * @param array  $params 路径参数
     */
    final public function redirect($path, $params=[]) {
        $url=URL::create($path, $params);
        $this->_response->redirect($url);
    }

    /**
     * 输出json
     *
     * @param     $result
     * @param int $options
     */
    final public function outputJson($result, int $options=JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) {
        header('Content-type: application/json');
        $serializePrecision=@ini_get('serialize_precision');
        if (($options & JSON_NUMERIC_CHECK) && $serializePrecision) {
            ini_set('serialize_precision', -1);
        }
        $json=json_encode($result, $options);
        if ($serializePrecision) {
            ini_set('serialize_precision', $serializePrecision);
        }
        die($json);
    }
}
