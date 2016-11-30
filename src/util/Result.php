<?php
namespace rust\util;
class Result {
    public $code;
    public $msg;
    public $data;
    public $needRefreshToken = NULL;
    public $authorizedToken  = NULL;

    /**
     * Result constructor.
     *
     * @param int                          $code
     * @param null|string|array            $msg
     * @param null|string|int|array|object $data
     */
    public function __construct($code, $msg = NULL, $data = NULL) {
        $this->code = $code;
        $message    = $msg;
        if ((!$msg || is_array($msg)) && is_numeric($code) && $code) {
            $err_msg = $this->getErrorMsg($code);
            $message = is_array($msg) ? vsprintf($err_msg, $msg) : $err_msg;
        }
        //传入的结果非数组或者对象 则返回['result'=>xxx]对象
        $data       = !$data && !is_object($data) && !is_resource($data) ? NULL : $data;
        $this->msg  = $message;
        $this->data = $data;
        if (!$this->authorizedToken) {
            unset($this->authorizedToken);
        }
        if (!$this->needRefreshToken) {
            unset($this->needRefreshToken);
        }
    }

    /**
     * 获取错误消息
     *
     * @param $err_code
     *
     * @return int|null|Config|string
     */
    protected function getErrorMsg($err_code) {
        if (!$err_code || !is_numeric($err_code)) {
            return NULL;
        }
        $err_config = new Config('error_code', TRUE);
        return $err_config->get($err_code);
    }
}