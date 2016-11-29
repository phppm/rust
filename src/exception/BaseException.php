<?php
namespace rust\exception;
class BaseException extends \Exception {
    protected $data;

    /**
     * BaseException constructor.
     *
     * @param int    $code
     * @param string $message
     * @param null   $data
     */
    public function __construct($code, $message = '', $data = NULL) {
        parent::__construct($message, $code);
        $this->data = $data;
    }

    /**
     * 获取异常数据
     *
     * @return null
     */
    final public function getData() {
        return $this->data;
    }

    /**
     * @param string $resultClass
     *
     * @return mixed
     */
    final public function toResult($resultClass = '\\rust\\util\\Result') {
        $result = new $resultClass($this->getCode(), $this->getMessage(), $this->getData());
        return $result;
    }

    /**
     * @return string
     */
    final public function toString() {
        $result = $this->toResult();
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}