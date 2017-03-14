<?php
/**
 * Created by PhpStorm.
 * User: rustysun
 * Date: 16/4/6
 * Time: 下午6:49
 */
namespace rust\exception;

final Class ErrorCode {
    const INVALID_PARAMETER = 1001;
    const NEED_PARAMETER = 1002;
    const METHOD_NOT_FOUND = 1003;
    const DBO_EXECUTE_FAILED = 2000;
    //---mvc
    //controller
    //model
    //view
    const NOT_FOUND_VIEW_INSTANCE = 3801;
    //缓存异常
    const CACHE_SERVER_NOT_FOUND = 6000;
    const CACHE_SERVER_CONNECT_FAILED = 6001;
}