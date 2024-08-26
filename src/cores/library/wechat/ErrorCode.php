<?php
// +----------------------------------------------------------------------
// | 二次开发商城 [ 致力于通过产品和服务，帮助商家高效化开拓市场 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2024 https://www.newsite.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
// +----------------------------------------------------------------------
// | Author: 新科技 <admin@newsite.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace cores\library\wechat;

/**
 * error code 说明.
 * <ul>
 *    <li>-41001: encodingAesKey 非法</li>
 *    <li>-41003: aes 解密失败</li>
 *    <li>-41004: 解密后得到的buffer非法</li>
 *    <li>-41005: base64加密失败</li>
 *    <li>-41016: base64解密失败</li>
 * </ul>
 */
class ErrorCode
{
    public static int $OK = 0;
    public static int $IllegalAesKey = -41001;
    public static int $IllegalIv = -41002;
    public static int $IllegalBuffer = -41003;
    public static int $DecodeBase64Error = -41004;
}

