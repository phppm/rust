<?php
/**
 * Created by PhpStorm.
 * User: rustysun
 */
namespace rust\exception\view;

use rust\exception\ErrorCode;

/**
 * Class ViewInstanceNotFoundException
 *
 * @package PHPKit
 * @author  rustysun.cn@gmail.com
 */
class ViewInstanceNotFoundException extends ViewException {
    public function __construct() {
        parent::__construct(ErrorCode::NOT_FOUND_VIEW_INSTANCE,'not found view instance.') ;
    }
}