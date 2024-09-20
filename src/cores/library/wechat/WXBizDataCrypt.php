<?php
// 数字化商城
declare (strict_types=1);

namespace cores\library\wechat;

/**
 * 微信数据加解密
 * Class WXBizDataCrypt
 * @package cores\library\wechat
 */
class WXBizDataCrypt
{
    private $sessionKey;

    /**
     * 构造函数
     * @param string|null $sessionKey 用户在小程序登录后获取的会话密钥
     */
    public function __construct(?string $sessionKey = null)
    {
        $this->sessionKey = $sessionKey ?: substr(md5(php_uname()), 0, 24);
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param string $encryptedData 加密的用户数据
     * @param string $iv            与用户数据一同返回的初始向量
     * @param mixed $content        解密后的原文
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptData(string $encryptedData, string $iv, &$content): int
    {
        if (strlen($this->sessionKey) != 24) {
            return ErrorCode::$IllegalAesKey;
        }
        if (strlen($iv) != 24) {
            return ErrorCode::$IllegalIv;
        }
        $aesKey = base64_decode($this->sessionKey);
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, 'AES-128-CBC', $aesKey, 1, $aesIV);
        if (empty($result)) {
            return ErrorCode::$IllegalBuffer;
        }
        $resultArr = json_decode($result, true);
        if (empty($resultArr)) {
            return ErrorCode::$IllegalBuffer;
        }
        $content = $resultArr;
        return ErrorCode::$OK;
    }

    /**
     * 检验数据的真实性，并且获取加密后的密文.
     * @param string $plaintext     要加密的用户数据
     * @param string $iv            与用户数据一同返回的初始向量
     * @param string $encryptedData 加密后的数据
     * @return int 成功0，失败返回对应的错误码
     */
    public function encryptData(string $plaintext, string $iv, string &$encryptedData): int
    {
        if (strlen($this->sessionKey) != 24) {
            return ErrorCode::$IllegalAesKey;
        }
        if (strlen($iv) != 24) {
            return ErrorCode::$IllegalIv;
        }
        $aesKey = base64_decode($this->sessionKey);
        $aesIV = base64_decode($iv);
        $ciphertext = openssl_encrypt($plaintext, 'AES-128-CBC', $aesKey, 1, $aesIV);
        if ($ciphertext == NULL) {
            return ErrorCode::$IllegalBuffer;
        }
        $encryptedData = base64_encode($ciphertext);
        return ErrorCode::$OK;
    }

    /**
     * 生成一个伪随机字节串
     * @return string
     */
    public function createIv(): string
    {
        $ivlen = openssl_cipher_iv_length('AES-128-CBC');
        $iv = openssl_random_pseudo_bytes($ivlen);
        return base64_encode($iv);
    }
}

