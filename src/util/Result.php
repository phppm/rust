<?php
namespace rust\util;
class Result {
    public $code;
    public $msg;
    public $data;
    public $isRefreshToken = FALSE;
    public $token          = NULL;

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
        $data = !$data && !is_numeric($data) ? NULL : $data;
        $json = NULL;
        if (is_array($data) || is_object($data)) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        if ($json) {
            $data = json_decode($json);
        }
        $this->msg  = $message;
        $this->data = $data;
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