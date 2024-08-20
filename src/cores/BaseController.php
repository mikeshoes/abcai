<?php
//  数字化商城
declare (strict_types=1);

namespace cores;

use think\App;
use think\exception\ValidateException;
use think\response\Json;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var Request
     */
    protected $request;

    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * BaseController constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        // 自动验证
        $this->autoValidateCheck();

    }

    private function autoValidateCheck()
    {
        $controller = $this->request->controller();
        $validate = str_replace('Controller', 'Validate', $controller);
        if (class_exists($validateClass = $this->app->parseClass('validate', $validate))) {
            $action = $this->request->action(true);
            $validateEntity = new $validateClass();
            if (method_exists($validateEntity, 'hasScene') && $validateEntity->hasScene($action)) {
                if ($data = $this->validateData()) {
                    $this->validate($data, $validate . '.' . $action);
                }
            }
        }
    }

    public function user()
    {
        return $this->request->user();
    }

    private function validateData(): array
    {
        return array_merge($this->postForm() ?? [], $this->postData() ?? []);
    }

    /**
     * 验证数据
     * @access protected
     * @param array $data            数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array $message         提示信息
     * @param bool $batch            是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @param int|null $status
     * @param string $message
     * @param array $data
     * @return Json
     */
    protected final function renderJson(int $code = null, string $message = '', array $data = []): Json
    {
        return json(compact('code', 'message', 'data'));
    }

    /**
     * 返回操作成功json
     * @param array|string $data
     * @param string $message
     * @return Json
     */
    protected final function renderSuccess($data = [], string $message = 'success'): Json
    {
        if (is_string($data)) {
            $message = $data;
            $data = [];
        }
        return $this->renderJson(config('status.success'), $message, $data);
    }

    /**
     * 返回操作失败json
     * @param string $message
     * @param array $data
     * @return Json
     */
    protected final function renderError(string $message = 'error', array $data = []): Json
    {
        return $this->renderJson(config('status.error'), $message, $data);
    }

    /**
     * 获取post数据 (数组)
     * @param null $key
     * @param bool $filter
     * @return mixed
     */
    protected final function postData($key = null, bool $filter = false)
    {
        return $this->request->post(empty($key) ? '' : "{$key}/a", null, $filter ? '' : null);
    }

    /**
     * 获取post数据 (数组)
     * @param string|null $key
     * @param bool $filter
     * @return mixed
     */
    protected final function postForm(?string $key = 'form', bool $filter = true)
    {
        return $this->postData(empty($key) ? 'form' : $key, $filter);
    }
}
