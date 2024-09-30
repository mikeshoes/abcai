<?php

namespace cores\library\wechat;

use cores\exception\BaseException;

class WechatUser extends Base
{
    /**
     * code 换取 session_key
     * 这是一个 HTTPS 接口，开发者服务器使用登录凭证 code 获取 session_key 和 openid。
     * @throws BaseException
     */
    public function jscode2session(string $code)
    {
        $url = 'https://api.weixin.qq.com/sns/jscode2session';
        $result = json_decode($this->get($url, [
            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'grant_type' => 'authorization_code',
            'js_code' => $code
        ]), true);
        if (isset($result['errcode'])) {
            $this->error = $result['errmsg'];
            return false;
        }
        return $result;
    }

}