<?php


namespace system\kernel;


class Route
{
    /**
     * 是否已完成创建，未完成时不能调用run和runMiddleware函数
     * @var bool
     */
    private bool $generated = false;
    private string $namespace = '';
    private string $controller = '';
    private string $path = '';
    private string $prefix = '';
    public string $pattern = '';
    public string $class = '';
    public string $function = '';
    public array $method = [];
    public array $middleware = [];
    public $callback = null;

    public function __construct(string $path, string $controller, string $function)
    {
        $this->path = $path;
        $this->controller = $controller;
        $this->function = $function;
    }

    public function namespace(string $namespace)
    {
        if($this->generated) return $this;
        $this->namespace = $namespace;
        return $this;
    }

    public function method($method)
    {
        if($this->generated) return $this;
        if (!empty($method)) {
            if (is_array($method)) {
                $this->method = $method;
            } else {
                $this->method[] = $method;
            }
        }
        return $this;
    }

    public function prefix(string $prefix)
    {
        if($this->generated) return $this;
        $this->prefix = $prefix;
        return $this;
    }

    public function middleware(array $middleware)
    {
        if($this->generated) return $this;
        foreach ($middleware as $item) {
            $md_class = config("middleware.{$item}", null);
            if ($md_class === null) {
                continue;
            }
            $this->middleware[] = $md_class;
        }
        return $this;
    }

    public function callback(callable $callback)
    {
        if($this->generated) return $this;
        $this->callback = $callback;
        return $this;
    }

    public function generate()
    {
        if($this->generated) return $this;
        $this->class = '\\app\\controllers' . ((empty($this->namespace) || $this->namespace == 'default') ? '' : "\\{$this->namespace}") . '\\' . $this->controller;
        $prefix = ((empty($this->namespace) || $this->namespace == 'default') ? '' : "/{$this->namespace}") . ($this->prefix ? "/{$this->prefix}" : '');
        $this->pattern = $prefix . $this->path;
        $this->generated = true;
    }

    public function runMiddleware()
    {
        if(!$this->generated) return;
        foreach ($this->middleware as $middleware) {
            if (!method_exists($mid_obj = new $middleware(), 'handle')) {
                continue;
            }
            $mid_result = $mid_obj->handle();
            if ($mid_result !== true) {
                response()->end($mid_result);
            }
        }
    }

    /**
     * 执行路由
     * @throws \ReflectionException
     */
    public function run()
    {
        if(!$this->generated) return;
        $method = request()->server('request_method');
        if (!empty($this->method) && !in_array(strtolower($method), $this->method)) {
            throw new \Exception('不支持该HTTP方法');
        }
        if (is_callable($this->callback)) {
            self::runCallback();
        } else {
            self::runController();
        }
    }

    /**
     * 执行路由的回调函数
     * @throws \ReflectionException
     */
    private function runCallback()
    {
        $params = self::getInjectParams();
        call_user_func_array($this->callback, $params);
    }

    /**
     * 执行路由绑定的控制器方法
     * @throws \Exception
     */
    private function runController()
    {
        if (!method_exists($instance = app()->singleton($this->class, $this->class), $this->function)) {
            throw new \Exception('当前类不存在该方法');
        }
        $params = self::getInjectParams();
        call_user_func_array([$instance, $this->function], $params);
    }

    /**
     * 获取路由路径中指定的参数
     * @return array
     */
    private function getRouteParams()
    {
        $params = [];
        $request_uri = request()->server('request_uri');
        $pattern = explode('/', $this->pattern);
        $request_uri = explode('/', $request_uri);
        foreach ($pattern as $key => $item) {
            if (preg_match('/^\{(.*)}$/', $item, $match) && $request_uri[$key] !== '') {
                $params[$match[1]] = $request_uri[$key];
            }
        }
        return $params;
    }

    /**
     * 获取依赖注入后的参数数组
     * @return array
     * @throws \ReflectionException
     */
    private function getInjectParams()
    {
        $params = self::getRouteParams();
        if (is_callable($this->callback)) {
            $ref_method = new \ReflectionFunction($this->callback);
        } else {
            $ref_method = new \ReflectionMethod($this->class, $this->function);
        }
        $func_params = $ref_method->getParameters();
        foreach ($func_params as $key => $func_param) {
            $class = $func_param->getClass();
            if ($class && !array_key_exists($func_param->name, $params)) {
                $instance = app()->singleton($class->name, $class->name);
                $params[$func_param->name] = $instance;
            }
        }
        return self::sortParams($func_params, $params);
    }

    /**
     * 根据控制器方法的参数顺序获取参数列表
     * @param array $func_params 控制器方法的参数
     * @param array $origin_params 所有已获取的参数
     * @return array
     */
    private function sortParams(array $func_params, array $origin_params)
    {
        $params = [];
        foreach ($func_params as $key => $func_param) {
            if (array_key_exists($func_param->name, $origin_params)) {
                $params[$key] = $origin_params[$func_param->name];
            } else {
                $params[$key] = null;
            }
        }
        return $params;
    }
}