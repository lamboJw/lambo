<?php

namespace system\kernel;

class Router
{
    private static array $route_map = [];
    private static string $cur_namespace = 'default';
    private static array $cur_middleware = [];

    public function middleware(array $middleware)
    {
        self::$cur_middleware = $middleware;
        return $this;
    }

    public function route($path, $controller, $func)
    {
        $gen_route = (new Route($path, $controller, $func))->namespace(self::$cur_namespace)->middleware(self::$cur_middleware)->generate();
        self::$route_map[$gen_route['pattern']] = $gen_route;
        self::$cur_middleware = [];
    }

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