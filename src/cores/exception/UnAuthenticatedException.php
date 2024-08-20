<?php
//  数字化商城
declare (strict_types=1);

namespace cores\exception;

use think\Exception;

/**
 * 自定义异常类的基类
 * Class BaseException
 * @package cores\exception
 */
class UnAuthenticatedException extends BaseException
{

    /**
     * 构造函数，接收一个关联数组
     *
     */
    public function __construct(string $message = "请先登陆")
    {
        parent::__construct($message, 401, null);
    }
}

