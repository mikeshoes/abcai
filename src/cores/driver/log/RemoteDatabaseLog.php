<?php

namespace cores\driver\log;

use cores\driver\LogDatabaseSave;
use cores\exception\DenyException;
use cores\library\PropertyClass;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use think\contract\LogHandlerInterface;
use think\exception\ValidateException;

class RemoteDatabaseLog implements LogHandlerInterface
{
    public function save(array $log): bool
    {
        $message = [];
        foreach ($log as $type => $val) {
            foreach ($val as $msg) {
                if (is_string($msg)) {
                    $msg = ['msg' => $msg];
                } elseif (!is_array($msg)) {
                    $msg = ['msg' => var_export($msg, true)];
                }

                $message[] = array_merge($msg, ['type' => $type]);
            }
        }

        $remoteAuthUrl = config('app.remote_log_api');
        if (empty($remoteAuthUrl)) {
            return false;
        }
        // 创建 Guzzle HTTP 客户端
        $client = new Client();
        try {
            // 转发当前请求到远程用户中心 添加proxy前缀
            $response = $client->request('post', $remoteAuthUrl, [
                'headers' => app('request')->header(), // 转发原始请求的 headers
                'json' => $message,        // 转发原始请求的 body 参数 (json 数据)
                'timeout' => 5,                    // 设置请求超时
            ]);

            // 处理远程服务器返回的响应
            if ($response->getStatusCode() !== 200) {
                return false;
            }

            // 根据需求处理响应的结果
            $res = json_decode($response->getBody()->getContents(), true);
            if ($res['code'] !== 200) {
                return false;
            }
        } catch (GuzzleException $e) {
        }
        return true;
    }
}