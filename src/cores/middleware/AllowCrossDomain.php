<?php
//  数字化商城
declare (strict_types=1);

namespace cores\middleware;

use Closure;
use think\Config;
use think\Request;
use think\Response;

/**
 * 跨域请求支持
 * Class AllowCrossDomain
 * @package cores\middleware
 */
class AllowCrossDomain
{
    // cookie的所属域名
    protected $cookieDomain;

    /**
     * 构造方法
     * AllowCrossDomain constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->cookieDomain = $config->get('cookie.domain', '');
    }

    /**
     * 获取允许跨域的header参数 [自定义]
     * @return array
     */
    private function getCustomHeader(): array
    {
        return [
            'Access-Token',
            'saasId',
            'platform',
            'isLog',
        ];
    }

    /**
     * 获取允许跨域的header参数
     * @return array
     */
    private function getHeader(): array
    {
        $headers = array_merge([
            'Authorization', 'Content-Type', 'X-CSRF-TOKEN', 'X-Requested-With',
            'If-Match', 'If-Modified-Since', 'If-None-Match', 'If-Unmodified-Since',
        ], $this->getCustomHeader());

        return [
            // 允许所有域名访问
//            'Access-Control-Allow-Origin' => '*',
            // 允许cookie跨域访问
            'Access-Control-Allow-Credentials' => 'true',
            // 预检请求的有效期
            'Access-Control-Max-Age' => 1800,
            // 允许跨域的方法
            'Access-Control-Allow-Methods' => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
            // 跨域请求header头
            'Access-Control-Allow-Headers' => implode(',', $headers),
        ];
    }

    /**
     * 允许跨域请求
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param array|null $header
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?array $header = []): Response
    {
        $header = !empty($header) ? array_merge($this->getHeader(), $header) : $this->getHeader();
        if (!isset($header['Access-Control-Allow-Origin'])) {
            $origin = $request->header('origin');

            if ($origin && ('' == $this->cookieDomain || strpos($origin, $this->cookieDomain))) {
                $header['Access-Control-Allow-Origin'] = $origin;
            } else {
                $header['Access-Control-Allow-Origin'] = '*';
            }
        }
        return $next($request)->header($header);
    }
}
