<?php
//  数字化商城
declare (strict_types=1);

namespace cores;

use cores\traits\AutoField;
use cores\traits\ErrorTrait;
use cores\traits\SoftDelete;
use Ramsey\Uuid\Uuid;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;
use think\facade\Log;
use think\helper\Str;
use think\model\Pivot;
use think\Paginator;

/**
 * 模型基类
 * Class BaseModel
 * @package cores\model
 */
abstract class BaseModel extends Pivot
{
    use ErrorTrait;
    use SoftDelete;
    use AutoField;

    // 当前访问的商城ID
    public static ?int $saasId = null;

    // 模型别名
    protected string $alias = '';

    // 定义全局的查询范围
    protected $globalScope = ['saasId'];

    // 是否允许全局查询saasId
    protected bool $isGlobalScopeSassId = true;

    // 默认删除字段值
    protected $defaultSoftDelete = 0;

    protected string $deleteField = 'is_del';

    protected array $autoFillField = ['saas_id', 'create_user_id', 'update_user_id', 'ucode'];

    protected $readonly = ['saas_id', 'create_user_id', 'create_time'];

    private array $customHidden = [
        'password',
        'update_user_id',
        'create_user_id',
        'update_time',
        'is_del',
    ];

    public function __construct(array $data = [])
    {
        // 当前模型名
        $name = str_replace('\\', '/', static::class);
        $this->name = app_name() . basename($name);
        parent::__construct($data);

        $this->addHidden($this->customHidden);
    }

    /**
     * 模型基类初始化
     */
    public static function init()
    {
        parent::init();
        self::saasId();
    }

    /**
     * 获取当前的租户
     * @return void
     */
    private static function saasId(): void
    {
        if (empty(self::$saasId)) {
            self::$saasId = \getSaasId();
        }
    }

    /**
     * 获取当前调用来源的应用名称
     * 例如：system, common
     * @return string
     */
    protected final static function getCalledModule(): string
    {
        if (preg_match('/app\\\(\w+)/', get_called_class(), $class)) {
            return $class[1];
        }
        return 'cores';
    }

    /**
     * 查找单条记录
     * @param mixed $data 查询条件
     * @param array $with 关联查询
     * @return array|static|null
     */
    public static function get($data, array $with = [])
    {
        try {
            $query = (new static)->with($with);
            return is_array($data) ? $query->where($data)->find() : $query->find((int)$data);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 获取当前表名称 (不含前缀)
     * @return string
     */
    public static final function getTableName(): string
    {
        $model = new static;
        return Str::snake($model->name);
    }

    /**
     * 定义全局的查询范围
     * @param Query $query
     */
    public function scopeSaasId(Query $query)
    {
        if (!$this->isGlobalScopeSassId) return;
        $saasId = self::$saasId;
        $saasId > 0 && $query->where($query->getTable() . '.saas_id', $saasId);
    }

    /**
     * 批量更新多条数据
     * @param iterable $dataSet [0 => ['data'=>[], 'where'=>[]]]
     * @return array|false
     */
    public function updateAll(iterable $dataSet)
    {
        if (empty($dataSet)) {
            return false;
        }
        return $this->transaction(function () use ($dataSet) {
            $result = [];
            foreach ($dataSet as $key => $item) {
                $result[$key] = self::updateBase($item['data'], $item['where']);
            }
            return $result;
        });
    }

    /**
     * 批量新增数据
     * @param iterable $dataSet [0 => ['id'=>10001, 'name'=>'wang']]
     * @return \think\Collection | bool
     * @throws \Exception
     */
    public function addAll(iterable $dataSet)
    {
        if (empty($dataSet)) {
            return false;
        }
        return $this->saveAll($dataSet);
    }

    /**
     * 删除记录
     * @param array $where
     *           方式1: ['goods_id' => $goodsId]
     *           方式2: [
     *           ['store_user_id', '=', $storeUserId],
     *           ['role_id', 'in', $deleteRoleIds]
     *           ]
     * @return bool|int 这里实际返回的是数量int
     */
    public static function deleteAll(array $where, $force = false)
    {
        return static::destroy($where, $force);
    }

    /**
     * 字段值增长
     * @param array|int|bool $where
     * @param string $field
     * @param float $step
     * @return mixed
     */
    protected function setInc($where, string $field, float $step = 1)
    {
        if (is_numeric($where)) {
            $where = [$this->getPk() => (int)$where];
        }
        return $this->where($where)->inc($field, $step)->update();
    }

    /**
     * 字段值消减
     * @param array|int|bool $where
     * @param string $field
     * @param float $step
     * @return mixed
     */
    protected function setDec($where, string $field, float $step = 1)
    {
        if (is_numeric($where)) {
            $where = [$this->getPk() => (int)$where];
        }
        return $this->where($where)->dec($field, $step)->update();
    }

    /**
     * 新增hidden属性
     * @param array $hidden
     * @return $this
     */
    protected function addHidden(array $hidden): BaseModel
    {
        $this->hidden = array_merge($this->hidden, $hidden);
        return $this;
    }

    /**
     * 生成字段列表(字段加上$alias别名)
     * @param string $alias        别名
     * @param array $withoutFields 排除的字段
     * @return array
     */
    protected function getAliasFields(string $alias, array $withoutFields = []): array
    {
        $fields = array_diff($this->getTableFields(), $withoutFields);
        foreach ($fields as &$field) {
            $field = "$alias.$field";
        }
        return $fields;
    }

    /**
     * 更新数据[批量]
     * @param array $data       更新的数据内容
     * @param array|int $where  更新条件
     * @param array $allowField 允许的字段
     * @return bool
     */
    public static function updateBase(array $data, $where, array $allowField = []): bool
    {
        $model = new static;
        if (!empty($allowField)) {
            $model->allowField($allowField);
        }
        if (is_numeric($where)) {
            $where = [$model->getPk() => $where];
        }
        return $model->mySetUpdateWhere($where)->exists(true)->save($data);
    }

    /**
     * 设置模型的更新条件
     * @access protected
     * @param mixed $where 更新条件
     * @return static
     */
    public function mySetUpdateWhere($where): BaseModel
    {
        $this->setUpdateWhere($where);
        return $this;
    }

    /**
     * 根据ucode删除
     * @param string $ucode
     * @return bool
     */
    public function deleteByUcode(string $ucode): bool
    {
        return static::destroy(['ucode' => $ucode]);
    }

    /***
     * 分页组件获取列表数据
     * @param mixed $where       无条件则写1
     * @param array|null $fields 无需查数据，只查总数，为null
     * @param null $order
     * @param int $limit
     * @param array $with
     * @return Paginator
     * @throws DataNotFoundException|ModelNotFoundException|DbException
     */
    public function listByPage($where, array $fields = null, $order = null, int $limit = 15, array $with = []): \think\Paginator
    {
        try {
            $query = $this->where($where);
            return $query->when($order, fn($q) => $q->order($order))
                ->when($with, fn($q) => $q->with($with))
                ->field($fields)
                ->paginate($limit);
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            Log::record("列表查询出错{$e->getMessage()}");
            throw $e;
        }
    }


    /***
     * 不使用分页组件获取列表数据
     * @param mixed $where       无条件则为1或true
     * @param array|null $fields 无需查数据，只查总数，为null
     * @param null $order
     * @param int $page
     * @param int $limit
     * @param array $with
     * @return array
     * @throws DataNotFoundException|ModelNotFoundException|DbException
     */
    public function list($where, array $fields = null, $order = null, int $page = 1, int $limit = 15, array $with = []): array
    {
        try {
            $query = $this->where($where);
            $total = $query->count();
            if ($fields) {
                $list = $query->when($order, fn($q) => $q->order($order))
                    ->when($with, fn($q) => $q->with($with))
                    ->when($limit, fn($q) => $q->page($page, $limit))
                    ->field($fields)
                    ->select();
            }
            return [$total, $list ?? null];
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            Log::record("列表查询出错{$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * 获取隐藏的属性
     * @param array $hidden 合并的隐藏属性
     * @return array
     */
    public static function getHidden(array $hidden = []): array
    {
        return array_merge((new static)->hidden, $hidden);
    }

    public function autoSaasIdAttr(): ?int
    {
        return self::$saasId ?? 0;
    }

    public function autoUcodeAttr(): ?string
    {
        return str_replace('-', '', Uuid::uuid4()->toString());
    }

    private function getCurrentUser()
    {
        return app(Request::class)->user();
    }

    public function autoCreateUserIdAttr()
    {
        if ($user = $this->getCurrentUser()) {
            return $user->id;
        }
        return 0;
    }

    public function autoUpdateUserIdAttr()
    {
        if ($user = $this->getCurrentUser()) {
            return $user->id;
        }
        return 0;
    }
}
