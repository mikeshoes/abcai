<?php

namespace cores\library\wechat;

use cores\exception\BaseException;
use cores\traits\ErrorTrait;
use think\facade\Cache;
use think\facade\Log;


// +----------------------------------------------------------------------
// | 二次开发商城 [ 致力于通过产品和服务，帮助商家高效化开拓市场 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2024 https://www.newsite.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
// +----------------------------------------------------------------------
// | Author: 新科技 <admin@newsite.com>
// +----------------------------------------------------------------------

class Base
{
    /**
     * 微信api基类
     * Class wechat
     * @package app\library
     */
    use ErrorTrait;

    protected string $appId;
    protected string $appSecret;

    /**
     * 构造函数
     * WxBase constructor.
     * @param $appId
     * @param $appSecret
     */
    public function __construct($appId = null, $appSecret = null)
    {
        $this->setConfig($appId, $appSecret);
    }

    protected function setConfig($appId = null, $appSecret = null)
    {
        !empty($appId) && $this->appId = $appId;
        !empty($appSecret) && $this->appSecret = $appSecret;
    }

    /**
     * 获取access_token
     * @return mixed
     * @throws BaseException
     */
    protected function getAccessToken()
    {
        $cacheKey = sprintf('access_token:%s', $this->appId);
        if (!Cache::get($cacheKey)) {
            // 请求API获取 access_token
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appId}&secret={$this->appSecret}";
            $result = $this->get($url);
            $response = $this->jsonDecode($result);
            if (array_key_exists('errcode', $response)) {
                throw new BaseException("access_token获取失败，错误信息：{$result}");
            }
            // 记录日志
            Log::record([
                'name' => '获取access_token',
                'url' => $url,
                'appId' => $this->appId,
                'result' => $result
            ]);
            // 写入缓存
            Cache::set($cacheKey, $response['access_token'], 6000);
        }
        return Cache::get($cacheKey);
    }

    /**
     * 模拟GET请求 HTTPS的页面
     * @param string $url 请求地址
     * @param array $data
     * @return string $result
     * @throws BaseException
     */
    protected function get(string $url, array $data = []): string
    {
        // 处理query参数
        if (!empty($data)) {
            $url = $url . '?' . http_build_query($data);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        if ($result === false) {
            throw new BaseException(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    /**
     * 模拟POST请求
     * @param string $url    请求地址
     * @param mixed $data    请求数据
     * @param false $useCert 是否引入微信支付证书
     * @param array $sslCert 证书路径
     * @return mixed|bool|string
     * @throws \cores\exception\BaseException
     */
    protected function post(string $url, $data = [], bool $useCert = false, array $sslCert = [])
    {
        $header = ['Content-type: application/json;'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        if ($useCert) {
            // 设置证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $sslCert['certPem']);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $sslCert['keyPem']);
        }
        $result = curl_exec($ch);
        if ($result === false) {
            throw new BaseException(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    /**
     * 模拟POST请求 [第二种方式, 用于兼容微信api]
     * @param $url
     * @param array $data
     * @return mixed
     * @throws \cores\exception\BaseException
     */
    protected function post2($url, array $data = [])
    {
        $header = ['Content-Type: application/x-www-form-urlencoded'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        if ($result === false) {
            throw new BaseException(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    /**
     * 数组转json
     * @param $data
     * @return string
     */
    protected function jsonEncode($data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * json转数组
     * @param $json
     * @return mixed
     */
    protected function jsonDecode($json)
    {
        return json_decode($json);
    }
}