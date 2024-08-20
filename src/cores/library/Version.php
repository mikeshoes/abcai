<?php
//  数字化商城
declare (strict_types=1);

namespace cores\library;

use cores\exception\BaseException;

/**
 * 版本号工具类
 * Class Files
 * @package cores\library
 */
class Version
{
    /**
     * 获取当前系统版本号
     * @return string
     * @throws BaseException
     */
    public static function getVersion(): string
    {
        static $version = [];
        if (empty($version)) {
            // 读取version.json文件
            $filePath = root_path() . '/composer.json';
            !file_exists($filePath) && throwError('composer.json not found');
            // 解析json数据
            $version = json_decode(file_get_contents($filePath));
            !is_array($version) && throwError('version cannot be decoded');
        }
        return $version['version'];
    }

    /**
     * 获取下一个版本号
     * @param string $currentVersion   当前的版本号
     * @param array $versionCollection 版本号列表
     * @return false|string
     * @throws BaseException
     */
    public static function nextVersion(string $currentVersion, array $versionCollection)
    {
        $vers1 = self::versionToInteger($currentVersion);
        $dataset = [];
        foreach ($versionCollection as $value) {
            $vers2 = self::versionToInteger($value);
            $vers2 > $vers1 && $dataset[] = $vers2;
        }
        if (empty($dataset)) {
            return false;
        }
        return self::integerToVersion(min($dataset));
    }

    /**
     * 将版本转为数字
     * @param string $version
     * @return int
     * @throws BaseException
     */
    public static function versionToInteger(string $version): int
    {
        if (!self::check($version)) {
            throwError('version Validate Error');
        }
        list($major, $minor, $sub) = explode('.', $version);
        return intval($major * 10000 + $minor * 100 + $sub);
    }

    /**
     * 将数字转为版本
     * @param int $versionCode 版本的数字表示
     * @return string
     * @throws BaseException
     */
    public static function integerToVersion(int $versionCode): string
    {
        if (!is_numeric($versionCode) || $versionCode >= 100000) {
            throwError('version code Validate Error');
        }
        $version = array();
        $version[0] = (int)($versionCode / 10000);
        $version[1] = (int)($versionCode % 10000 / 100);
        $version[2] = $versionCode % 100;
        return implode('.', $version);
    }

    /**
     * 检查版本格式是否正确
     * @param string $version 版本
     * @return bool
     */
    public static function check(string $version): bool
    {
        return (bool)preg_match('/^[0-9]{1,3}\.[0-9]{1,2}\.[0-9]{1,2}$/', $version);
    }

    /**
     * 比较两个版本的值
     * @param string $version1 版本1
     * @param string $version2 版本2
     * @return int -1:版本1小于版本2, 0:相等, 1:版本1大于版本2
     * @throws BaseException
     */
    public static function compare(string $version1, string $version2): int
    {
        if (!self::check($version1) || !self::check($version2)) {
            throwError('version1 or version2 Validate Error');
        }
        return version_compare($version1, $version2);
    }
}