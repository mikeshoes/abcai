<?php
//  数字化商城
declare (strict_types=1);

namespace cores;

// 应用请求对象类
use cores\traits\UserRetrieval;

class Request extends \think\Request
{
    use UserRetrieval;

    // 全局过滤规则
    protected $filter = [];
}
