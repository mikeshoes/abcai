<?php
// 数字化商城
declare (strict_types=1);

namespace cores;

use cores\traits\ErrorTrait;
use think\exception\FuncNotFoundException;
use think\exception\ValidateException;
use think\facade\Db;
use think\Model;
use think\Paginator;

/**
 * 基础service对象
 * @method static bool addAll(array $data) 批量添加数据
 * @method static Model getByUcode(string $ucode) 根据Ucode获取数据
 * @method static array list($where, array $fields = null, $order = null, int $page = 1, $limit = 15, array $with = []) 批量获取数据
 * @method static Paginator listByPage($where, array $fields = null, $order = null, int $limit = 15, array $with = []) 分页组件获取数据
 * @method static Model get($where, $with = []) 根据Id获取数据
 * @method static deleteAll(array $where) 删除记录
 * @method static bool deleteByUcode(string $ucode) 根据ucode删除数据
 */
class BaseService
{
    use ErrorTrait;

    /**
     * 模型注入
     * @var Request
     */
    protected Request $request;

    protected static ?string $modelClass;

    /**
     * 构造方法
     * BaseService constructor.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        // 执行子类的构造方法
        $this->initialize();
    }

    /**
     * 构造方法 (供继承的子类使用)
     */
    protected function initialize()
    {
    }

    /**
     * @param $data
     * @return false|mixed
     */
    public function save($data)
    {
        $model = new static::$modelClass();
        $result = $model->save($data);
        return $result ? $model : false;
    }

    /**
     * 数据库事务操作
     * @param callable $closure
     * @param bool $isTran
     * @return mixed
     */
    public function transaction(callable $closure, bool $isTran = true)
    {
        return $isTran ? Db::transaction($closure) : $closure();
    }

    public function __call($method, $arguments)
    {
        if (!is_null(static::$modelClass)) {
            return (new static::$modelClass)->$method(...$arguments);
        }

        throw new FuncNotFoundException("not found method [{$method}]");
    }

    public static function __callStatic($method, $arguments)
    {
        if (!is_null(static::$modelClass)) {
            if (method_exists(static::$modelClass, $method)) {
                return (new static::$modelClass)->$method(...$arguments);
            }

            return static::$modelClass::$method(...$arguments);
        }

        throw new FuncNotFoundException("not found method [{$method}]");
    }
}
