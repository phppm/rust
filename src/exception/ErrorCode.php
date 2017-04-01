<?php

namespace rust\exception;
/**
 * Class ErrorCode
 *
 * @package rust\exception
 */
final Class ErrorCode {
    //参数异常
    const INVALID_PARAMETER = 1001;
    const NEED_PARAMETER = 1002;
    //方法异常
    const METHOD_NOT_FOUND = 1101;
    //数据库异常
    const DBO_FAILED = 2000;
    const DBO_CONFIG_READ_FAILED = 2001;
    const DBO_CONNECT_FAILED = 2002;//数据库连接失败
    const DBO_SQL_EXECUTE_FAILED = 2003;//SQL执行异常
    //---mvc
    //controller
    //model
    //view
    const NOT_FOUND_VIEW_INSTANCE = 3801;
    //缓存异常
    const CACHE_SERVER_NOT_FOUND = 6000;
    const CACHE_SERVER_CONNECT_FAILED = 6001;
}