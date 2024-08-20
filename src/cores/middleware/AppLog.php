<?php
//  数字化商城
declare (strict_types=1);

namespace cores\middleware;

use cores\Request;
use think\facade\Log;
use think\Response;

/**
 * 中间件：应用日志
 */
class AppLog
{
    // 访问日志
    private static string $beginLog = '';

    /**
     * 前置中间件
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        // 记录访问日志
        if (env('begin_log')) {
            $log = $this->getVisitor($request);
            $log .= "\r\n" . '[ header ] ' . print_r($request->header(), true);
            $log .= "" . '[ param ] ' . print_r($request->param(), true);
            $log .= '--------------------------------------------------------------------------------------------';
            static::$beginLog = $log;
            Log::record(static::$beginLog, 'begin');
        }

        return $next($request);
    }

    /**
     * 记录访问日志
     * @param Response $response
     */
    public function end(Response $response)
    {
        // todo 设计日志存储
    }
}