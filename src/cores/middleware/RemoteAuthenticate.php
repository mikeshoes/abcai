<?php

namespace cores\middleware;

use cores\exception\DenyException;
use cores\exception\UnAuthenticatedException;
use cores\library\PropertyClass;
use cores\provider\UserProvider;
use cores\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class RemoteAuthenticate
{

    /**
     * @throws UnAuthenticatedException
     * @throws DenyException
     */
    public function handle(Request $request, \Closure $next)
    {
        // 获取远程用户中心的地址
        $remoteAuthUrl = config('app.remote_auth_address');

        if (empty($remoteAuthUrl)) {
            throw new DenyException();
        }

        // 创建 Guzzle HTTP 客户端
        $client = new Client(['base_uri' => $remoteAuthUrl]);
        try {
            $path = $request->baseUrl();
            $path = ltrim($path, '/');
            $headers = $request->header();
            unset($headers['content-length']); // 避免因上传文件接口的content-length过大 导致鉴权curl出错
            $response = $client->request($request->method(), $path, [
                'headers' => $headers, // 转发原始请求的 headers
                'query' => $request->get(), // 转发原始query
                'json' => $request->param(),        // 转发原始请求的 body 参数 (json 数据)
                'timeout' => 5,                        // 设置请求超时
            ]);

            // 处理远程服务器返回的响应
            if ($response->getStatusCode() !== 200) {
                throw new DenyException();
            }

            // 根据需求处理响应的结果
            $res = json_decode($response->getBody()->getContents(), true);
            if ($res['code'] !== 200) {
                if ($res['code'] === 401) {
                    throw new UnAuthenticatedException();
                }
                throw new DenyException($res['message']);
            }

            // 你可以根据远程服务的响应进一步处理，比如检查用户身份是否有效
            $request->setUserResolver(function () use ($res) {
                return new PropertyClass($res['data']);
            });

            $request->setTokenResolver(function () use ($res) {
                return $res['data']['token'];
            });

            $request->addHeader("saasid", $res['data']['saas_id']);

            app(UserProvider::class)->syncUser($request->user());

        } catch (GuzzleException $e) {
            // 捕获 Guzzle 请求异常，并处理错误情况
            throw new DenyException('Error communicating with authentication service: ' . $e->getMessage());
        }

        // 继续执行下一个中间件
        return $next($request);
    }
}