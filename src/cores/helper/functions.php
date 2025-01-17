<?php


if (!function_exists('getCurrentUser')) {
    function getCurrentUser()
    {
        return app('request')->user();
    }
}

if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId()
    {
        $user = getCurrentUser();
        if ($user) {
            return $user->id;
        }
        return 0;
    }
}


if (!function_exists('encrypt')) {
    function encrypt($data): string
    {
        $key = "$$3456789001235467891234567890KK"; // 32 字节 (256 位) 密钥
        // 使用 openssl_encrypt 函数进行加密
        $ciphertext = openssl_encrypt($data, 'AES-256-ECB', $key, OPENSSL_RAW_DATA);

        return base64_encode($ciphertext);
    }
}

if (!function_exists('decrypt')) {
    function decrypt($data): string
    {
        $key = "$$3456789001235467891234567890KK"; // 32 字节 (256 位) 密钥
        $ciphertext = base64_decode($data);
        // 使用 openssl_encrypt 函数进行加密
        return openssl_decrypt($ciphertext, 'AES-256-ECB', $key, OPENSSL_RAW_DATA);
    }
}
