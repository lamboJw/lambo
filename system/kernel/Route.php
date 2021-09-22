<?php


namespace system\kernel;


use Exception;
use system\helpers\DependencyInjection;

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
        if ($this->generated) return $this;
        $this->namespace = $namespace;
        return $this;
    }

    public function method($method)
    {
        if ($this->generated) return $this;
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
        if ($this->generated) return $this;
        $this->prefix = $prefix;
        return $this;
    }

    public function middleware(array $middleware)
    {
        if ($this->generated) return $this;
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
        if ($this->generated) return $this;
        $this->callback = $callback;
        return $this;
    }

    public function generate()
    {
        if ($this->generated) return $this;
        $this->class = 'app\\controllers' . ((empty($this->namespace) || $this->namespace == 'default') ? '' : "\\{$this->namespace}") . "\\{$this->controller}";
        $prefix = ((empty($this->namespace) || $this->namespace == 'default') ? '' : "/{$this->namespace}") . ($this->prefix ? "/{$this->prefix}" : '');
        $this->pattern = $prefix . $this->path;
        $this->generated = true;
    }

    public function runMiddleware()
    {
        if (!$this->generated) return;
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
     * @throws Exception
     */
    public function run()
    {
        if (!$this->generated) return;
        if (!empty($this->method) && !in_array(strtolower(server('request_method')), $this->method)) {
            throw new RouteException('不支持该HTTP方法');
        }
        if (is_callable($this->callback)) {
            $this->runCallback();
        } else {
            $this->runController();
        }
    }

    /**
     * 执行路由的回调函数
     */
    private function runCallback()
    {
        call_user_func_array($this->callback, DependencyInjection::getParams($this->callback, null, $this->getRouteParams()));
    }

    /**
     * 执行路由绑定的控制器方法
     * @throws Exception
     */
    private function runController()
    {
        if (!method_exists($instance = app()->singleton($this->class, $this->class), $this->function)) {
            throw new RouteException('当前类不存在该方法');
        }
        call_user_func_array([$instance, $this->function], DependencyInjection::getParams($this->class, $this->function, $this->getRouteParams()));
    }

    /**
     * 获取路由路径中指定的参数
     * @return array
     */
    private function getRouteParams(): array
    {
        $params = [];
        $request_uri = server('request_uri');
        $pattern = explode('/', $this->pattern);
        $request_uri = explode('/', $request_uri);
        foreach ($pattern as $key => $item) {
            if (preg_match('/^\{(.*)}$/', $item, $match) && $request_uri[$key] !== '') {
                $params[] = $request_uri[$key];
            }
        }
        return $params;
    }
}

class RouteException extends Exception
{

}