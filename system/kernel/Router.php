<?php

namespace system\kernel;

class Router
{
    // 所有路由
    private static array $route_map = [];

    // 当前使用的路由空间
    private static string $cur_namespace = 'default';

    // 当前使用的中间件数组
    private static array $cur_middleware = [];

    /**
     * 为路由设置中间件，与route()方法配合使用
     * @param array $middleware
     * @return $this
     */
    public function middleware(array $middleware)
    {
        self::$cur_middleware = $middleware;
        return $this;
    }

    /**
     * 定义路由
     * @param string $path 路由地址
     * @param string $controller 控制器名
     * @param string $func 方法名
     */
    public function route(string $path, string $controller, string $func)
    {
        $gen_route = (new Route($path, $controller, $func))->namespace(self::$cur_namespace)->middleware(self::$cur_middleware)->generate();
        self::$route_map[$gen_route['pattern']] = $gen_route;
        self::$cur_middleware = [];
    }

    /**
     * 定义路由组
     * @param array $middleware 中间件数组
     * @param array $routes 路由数组
     */
    public function group(array $middleware, array $routes)
    {
        self::$cur_middleware = $middleware;
        foreach ($routes as $route) {
            if (!$route instanceof Route) {
                continue;
            }
            $gen_route = $route->namespace(self::$cur_namespace)->middleware($middleware)->generate();
            self::$route_map[$gen_route['pattern']] = $gen_route;
        }
        self::$cur_middleware = [];
    }

    /**
     * 根据路由文件加载路由
     * @return array
     */
    public static function load_routes()
    {
        $routes = get_dir_files(ROUTE_PATH);
        foreach ($routes as $route_file) {
            self::$cur_namespace = str_replace('.php', '', basename($route_file));
            require_once $route_file;
        }
        return self::$route_map;
    }
}