<?php
//  数字化商城
declare (strict_types=1);

namespace cores\exception;

use Throwable;

/**
 * 自定义异常类的基类
 * Class BaseException
 * @package cores\exception
 */
class BaseException extends \Exception
{
    // 输出的数据
    public $data = [];

    /**
     * 构造函数，接收一个关联数组
     *
     */
    public function __construct(string $message = '服务器开小差了', int $code = null, array $data = null, Throwable $previous = null)
    {
        $code = $code ?? config('status.error');
        parent::__construct($message, $code, $previous);
        $this->data = $data ?? [];
    }
}

