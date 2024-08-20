<?php

namespace cores\exception;

class DenyException extends BaseException
{
    /**
     * 构造函数，接收一个关联数组
     *
     */
    public function __construct(string $message = "无权访问")
    {
        parent::__construct($message, 403, null);
    }
}