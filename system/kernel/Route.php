<?php


namespace system\kernel;


class Route
{
    private string $namespace = '';
    private string $controller = '';
    private string $function = '';
    private string $path = '';
    private array $middleware = [];

    public function __construct($path, $controller, $function)
    {
        $this->path = $path;
        $this->controller = $controller;
        $this->function = $function;
    }

    public function namespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function middleware(array $middleware)
    {
        foreach ($middleware as $item) {
            $md_class = config('middleware', $item, null);
            if ($md_class === null) {
                continue;
            }
            $this->middleware[] = $md_class;
        }
        return $this;
    }

    public function generate()
    {
        $class = '\\app\\controllers' . ((empty($this->namespace) || $this->namespace == 'default') ? '' : "\\{$this->namespace}") . '\\' . $this->controller;
        $prefix = (empty($this->namespace) || $this->namespace == 'default') ? '' : "/{$this->namespace}";
        return ['pattern' => $prefix.$this->path, 'class' => $class, 'func' => $this->function, 'middleware' => $this->middleware];
    }
}