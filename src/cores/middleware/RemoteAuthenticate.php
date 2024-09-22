<?php

namespace cores\middleware;

use cores\exception\UnAuthenticatedException;
use cores\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class RemoteAuthenticate
{

    /**
     * @throws UnAuthenticatedException
     */
    public function handle(Request $request, \Closure $next)
    {
        // 获取远程用户中心的地址
        $remoteAuthUrl = config('app.remote_aut_address');

        if (empty($remoteAuthUrl)) {
            throw new UnAuthenticatedException('Authentication service not configured');
        }

        // 创建 Guzzle HTTP 客户端
        $client = new Client();
        try {
            $path = $request->baseUrl();
            // 转发当前请求到远程用户中心 添加proxy前缀
            $remoteAuthUrl = printf("%s/proxy/%s", $remoteAuthUrl, $path);
            $response = $client->request($request->method(), $remoteAuthUrl, [
                'headers' => $request->header(), // 转发原始请求的 headers
                'query' => $request->query(),   // 转发原始请求的 query 参数
                'json' => $request->input(),        // 转发原始请求的 body 参数 (json 数据)
                'timeout' => 5,                        // 设置请求超时
            ]);

            // 处理远程服务器返回的响应
            if ($response->getStatusCode() !== 200) {
                throw new UnAuthenticatedException('Authentication failed');
            }

            // 根据需求处理响应的结果
            $userData = json_decode($response->getBody()->getContents(), true);

            // 你可以根据远程服务的响应进一步处理，比如检查用户身份是否有效
            $request->setUserResolver(function () use ($userData) {
                return $userData;
            });

        } catch (GuzzleException $e) {
            // 捕获 Guzzle 请求异常，并处理错误情况
            throw new UnAuthenticatedException('Error communicating with authentication service: ' . $e->getMessage());
        }

        // 继续执行下一个中间件
        return $next($request);
    }
}