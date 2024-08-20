<?php
// 数字化商城
namespace cores\library;

use think\exception\ValidateException;
use think\facade\Route;

/**
 * 工具类
 * Class helper
 * @package app\common\library
 */
class helper
{
    /**
     * 从object中选取属性
     * @param $source
     * @param array $columns
     * @return array
     */
    public static function pick($source, array $columns): array
    {
        $dataset = [];
        foreach ($source as $key => $item) {
            in_array($key, $columns) && $dataset[$key] = $item;
        }
        return $dataset;
    }

    /**
     * 获取数组中指定的列
     * @param $source
     * @param $column
     * @return array
     */
    public static function getArrayColumn($source, $column): array
    {
        $columnArr = [];
        foreach ($source as $item) {
            isset($item[$column]) && $columnArr[] = $item[$column];
        }
        return $columnArr;
    }

    /**
     * 获取数组中指定的列 [支持多列]
     * @param $source
     * @param $columns
     * @return array
     */
    public static function getArrayColumns($source, $columns): array
    {
        $columnArr = [];
        foreach ($source as $item) {
            $temp = [];
            foreach ($columns as $index) {
                $temp[$index] = $item[$index];
            }
            $columnArr[] = $temp;
        }
        return $columnArr;
    }

    /**
     * 随机获取指定数量的数组元素
     * @param array $source 数组源
     * @param int $num      指定数量
     * @return array
     */
    public static function getArrayRand(array $source, int $num): array
    {
        if (count($source) < $num) {
            return [];
        }
        $keys = array_rand($source, $num);
        if (!is_array($keys)) {
            return [$source[$keys]];
        }
        $data = [];
        foreach ($keys as $key) {
            $data[] = $source[$key];
        }
        return $data;
    }

    /**
     * 把二维数组或对象中某列设置为key返回
     * @param $source
     * @param $index
     * @return mixed
     */
    public static function arrayColumn2Key($source, $index)
    {
        $data = [];
        foreach ($source as $item) {
            $data[$item[$index]] = $item;
        }
        return $data;
    }

    /**
     * 二维数组去重
     * @param $source
     * @param $uniqueKey
     * @return array
     */
    public static function arrayUnique($source, $uniqueKey): array
    {
        $tmpKeys[] = [];
        foreach ($source as $key => &$item) {
            if (is_array($item) && isset($item[$uniqueKey])) {
                if (in_array($item[$uniqueKey], $tmpKeys)) {
                    unset($source[$key]);
                } else {
                    $tmpKeys[] = $item[$uniqueKey];
                }
            }
        }
        // 重置一下二维数组的索引
        return array_slice($source, 0, count($source), false);
    }

    /**
     * 格式化价格显示
     * @param mixed $number
     * @param bool $isMinimum 是否存在最小值
     * @param float $minimum
     * @return string
     */
    public static function number2($number, bool $isMinimum = false, float $minimum = 0.01): string
    {
        $isMinimum && $number = max($minimum, $number);
        return sprintf('%.2f', $number);
    }

    public static function getArrayItemByColumn($array, $column, $value)
    {
        foreach ($array as $item) {
            if ($item[$column] == $value) {
                return $item;
            }
        }
        return false;
    }

    /**
     * 获取二维数组中指定字段的和
     * @param $array
     * @param $column
     * @return float|int
     */
    public static function getArrayColumnSum($array, $column)
    {
        $sum = 0;
        foreach ($array as $item) {
            $sum = self::bcadd($sum, $item[$column]);
        }
        return $sum;
    }

    /**
     * 在二维数组中查找指定值
     * @param iterable $dataset 二维数组/或可变量对象
     * @param string $searchIdx 查找的索引
     * @param mixed $searchVal  查找的值
     * @return bool|mixed
     */
    public static function arraySearch(iterable $dataset, string $searchIdx, $searchVal)
    {
        foreach ($dataset as $item) {
            if ($item[$searchIdx] == $searchVal) {
                return $item;
            }
        }
        return false;
    }

    /**
     * 过滤二维数组
     * @param mixed|array $array 二维数组
     * @param string $searchIdx  查找的索引
     * @param mixed $searchVal   查找的值
     * @return iterable|array
     */
    public static function arrayFilterAsVal($array, string $searchIdx, $searchVal)
    {
        $data = [];
        foreach ($array as $key => $item) {
            if (isset($item[$searchIdx]) && $item[$searchIdx] == $searchVal) {
                $data[$key] = $item;
            }
        }
        return $data;
    }

    public static function setDataAttribute(&$source, $defaultData, $isArray = false)
    {
        if (!$isArray) $dataSource = [&$source]; else $dataSource = &$source;
        foreach ($dataSource as &$item) {
            foreach ($defaultData as $key => $value) {
                $item[$key] = $value;
            }
        }
        return $source;
    }

    public static function bcsub($leftOperand, $rightOperand, $scale = 2): string
    {
        return \bcsub($leftOperand, $rightOperand, $scale);
    }

    public static function bcadd($leftOperand, $rightOperand, $scale = 2): string
    {
        return \bcadd($leftOperand, $rightOperand, $scale);
    }

    public static function bcmul($leftOperand, $rightOperand, $scale = 2): string
    {
        return \bcmul($leftOperand, $rightOperand, $scale);
    }

    public static function bcdiv($leftOperand, $rightOperand, int $scale = 2): ?string
    {
        return \bcdiv($leftOperand, $rightOperand, $scale);
    }

    /**
     * 浮点数比较
     * 若二个字符串一样大则返回 0；若左边的数字字符串 (left operand) 比右边 (right operand) 的大则返回 +1；若左边的数字字符串比右边的小则返回 -1
     * @param $leftOperand
     * @param $rightOperand
     * @param int $scale
     * @return int
     */
    public static function bccomp($leftOperand, $rightOperand, int $scale = 2): int
    {
        return \bccomp($leftOperand, $rightOperand, $scale);
    }

    /**
     * 比较两个数值是否相等
     * @param $leftOperand
     * @param $rightOperand
     * @param int $scale
     * @return bool
     */
    public static function bcequal($leftOperand, $rightOperand, int $scale = 2): bool
    {
        return self::bccomp($leftOperand, $rightOperand, $scale) === 0;
    }

    /**
     * 数组转为json
     * @param $data
     * @param int $options
     * @return false|string
     */
    public static function jsonEncode($data, int $options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($data, $options);
    }

    /**
     * json转义为数组
     * @param $json
     * @return array|mixed
     */
    public static function jsonDecode($json)
    {
        return json_decode($json, true);
    }

    /**
     * 将xml数据转换为array
     * @param string $xml
     * @return array|mixed
     */
    public static function xmlToArray(string $xml)
    {
        // 禁止引用外部xml实体
        if (PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader(true);
        }
        return self::jsonDecode(self::jsonEncode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)));
    }

    /**
     * URL生成
     * @param string $url
     * @param array $vars
     * @return string
     */
    public static function buildUrl(string $url, array $vars = []): string
    {
        // 生成完整的url (包含域名)
        $url = Route::buildUrl($url, $vars)->domain(true)->build();
        // 判断当前访问模式是否为兼容模式
        if (strpos(request()->url(), '.php?s=') && strpos($url, '.php/')) {
            $url = str_replace('.php/', '.php?s=/', $url);
        }
        return $url;
    }

    /**
     * 检查目录是否可写
     * @param $path
     * @return bool
     */
    public static function checkWriteable($path): bool
    {
        try {
            !is_dir($path) && mkdir($path, 0755);
            if (!is_dir($path))
                return false;
            $fileName = $path . '/_test_write.txt';
            if ($fp = fopen($fileName, 'w')) {
                return fclose($fp) && unlink($fileName);
            }
        } catch (\Exception $e) {
        }
        return false;
    }

    /**
     * 记录info日志
     * @param string $name
     * @param array $content
     */
    public static function logInfo(string $name, array $content)
    {
        $content['name'] = $name;
        log_record($content, 'info');
    }

    /**
     * 根据指定路径创建文件夹
     * @param string $dirPath
     * @return string
     */
    public static function mkdir(string $dirPath): string
    {
        !is_dir($dirPath) && mkdir($dirPath, 0755, true);
        return $dirPath;
    }

    /**
     * 将字符串转换为字节
     * @param string $from
     * @return int|null
     */
    public static function convertToBytes(string $from): ?int
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $number = substr($from, 0, -2);
        $suffix = strtoupper(substr($from, -2));
        // B or no suffix
        if (is_numeric(substr($suffix, 0, 1))) {
            return preg_replace('/[^\d]/', '', $from);
        }
        $exponent = array_flip($units)[$suffix] ?? null;
        if ($exponent === null) {
            return null;
        }
        return $number * (1024 ** $exponent);
    }

    /**
     * 设置默认的检索数据
     * @param array $query
     * @param array $default
     * @return array
     */
    public static function setQueryDefaultValue(array $query, array $default = []): array
    {
        $data = array_merge($default, $query);

        foreach ($query as $field => $value) {
            // 不存在默认值跳出循环
            if (!isset($default[$field])) continue;
            // 如果传参为空, 设置默认值
            if (empty($value) && $value !== '0' && $value !== 0) {
                $data[$field] = $default[$field];
            }
        }
        return $data;
    }

    /**
     * 科学计数格式转化为字符串
     * @param string $num
     * @param int $double
     * @return string
     */
    public static function scToStr(string $num, int $double = 6): string
    {
        if (false !== stripos($num, "e")) {
            $a = explode("e", strtolower($num));
            return bcmul($a[0], bcpow('10', $a[1], $double), $double);
        }
        return $num;
    }


    /**
     * 查找深度数组中的值
     *
     * @param array $array 要查找的数组
     * @param string $path 点号分隔的键路径
     * @return mixed 如果找到值则返回值，否则返回 null
     */
    public static function deepArraySearch(array $array, string $path, $default = '')
    {
        $keys = explode('.', $path);

        return array_reduce($keys, function ($carry, $key) use ($default) {
            // 如果之前的值为空或不是数组，返回 默认值
            $key = strtolower($key);

            if (!is_array($carry) || !array_key_exists($key, $carry)) {
                return $default;
            }
            // 返回当前键的值
            return $carry[$key];
        }, $array);
    }
}
