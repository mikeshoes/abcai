<?php

namespace cores\library\wechat;

use cores\exception\BaseException;

class WechatApi extends Base
{

    /**
     * @throws BaseException
     */
    public function sendTemplateMessage($templateId, $openId, $data, $options = []): bool
    {
        $accessToken = $this->getAccessToken();
        $url = vsprintf('https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=%s', [$accessToken]);
        $data = [
                'touser' => $openId,
                'template_id' => $templateId,
                'data' => $data,
            ] + $options;
        $result = $this->post($url, $data);
        $result = json_decode($result, true);
        if (!empty($result['errcode'])) {
            $this->error = $result['errmsg'];
            return false;
        }
        return true;
    }

}