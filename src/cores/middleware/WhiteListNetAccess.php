<?php

namespace cores\middleware;

use cores\exception\DenyException;
use cores\Request;

class WhiteListNetAccess
{
    // 定义允许访问的白名单 IP 列表
    protected array $whitelist = [
        '192.168.0.0/16',  // IP 段
        '10.10.0.0/16',    // 单个IP
    ];

    public function handle(Request $request, \Closure $next, ...$whitelist)
    {
        $clientIp = $request->ip();
        // 检查 IP 是否在白名单中
        $whitelist = array_merge($this->whitelist, $whitelist);
        if (!$this->ipInWhitelist($clientIp, $whitelist)) {
            throw new DenyException();
        }
        return $next($request);
    }

    // CIDR 匹配函数，检查 IP 是否在给定的 CIDR 段中
    function cidrMatch($ip, $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
    }

    // 检查 IP 是否在白名单中
    function ipInWhitelist($ip, $whitelist): bool
    {
        foreach ($whitelist as $whitelistIp) {
            if (strpos($whitelistIp, '/') === false) {
                // 处理单个 IP
                if ($ip === $whitelistIp) {
                    return true;
                }
            } else {
                // 处理 IP 段
                if ($this->cidrMatch($ip, $whitelistIp)) {
                    return true;
                }
            }
        }
        return false;
    }
}