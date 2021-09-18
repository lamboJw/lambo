<?php

namespace system\kernel;
/**
 * Class Router
 * @package system\kernel
 * @method static void get(string $path, $controller, string $func = null)
 * @method static void post(string $path, $controller, string $func = null)
 * @method static void put(string $path, $controller, string $func = null)
 * @method static void delete(string $path, $controller, string $func = null)
 * @method static void patch(string $path, $controller, string $func = null)
 * @method static void any(string $path, $controller, string $func = null)
 * @method static void match(array $method, string $path, $controller, string $func = null)
 */
class Router
{
    // 所有路由
    private static array $route_map = [];

    // 当前使用的路由空间
    private static string $cur_namespace = 'default';

    // 当前使用的中间件数组
    private static array $cur_middleware = [];

    // 当前使用的前缀
    private static string $cur_prefix = '';

    // 当前路由组使用的中间件数组
    private static array $cur_group_middleware = [];

    // 当前路由组使用的前缀
    private static string $cur_group_prefix = '';

    /**
     * 为路由设置中间件
     * @param array $middleware
     * @return Router
     */
    public static function middleware(array $middleware): Router
    {
        self::$cur_middleware = $middleware;
        return new self;
    }

    /**
     * 设置路由前缀
     * @param string $prefix
     * @return Router
     */
    public static function prefix(string $prefix): Router
    {
        self::$cur_prefix = $prefix;
        return new self;
    }

    /**
     * 定义路由组
     * @param array|callable $options 配置，如果没有配置，可以直接传回调函数
     * @param callable $callback
     */
    public static function group($options, $callback = null)
    {
        if ($callback === null && is_callable($options)) {
            $callback = $options;
            $options = [];
        }
        if ($callback === null) {
            return;
        }
        if (!empty($options['middleware'])) {
            if (is_array($options['middleware'])) {
                self::$cur_group_middleware = $options['middleware'];
            } else {
                self::$cur_group_middleware = [$options['middleware']];
            }
        }
        if (!empty($options['prefix'])) {
            self::$cur_group_prefix = $options['prefix'];
        }
        $callback();
        self::$cur_group_middleware = [];
        self::$cur_group_prefix = '';
    }

    public static function __callStatic($name, $arguments)
    {
        if (in_array($name, ['get', 'post', 'put', 'delete', 'patch', 'any', 'match'])) {
            if ($name === 'match') {
                $method = array_shift($arguments);
            }
            $path = $arguments[0];
            if (is_callable($arguments[1]) && empty($arguments[2])) {
                $callback = $arguments[1];
                $controller = '';
                $func = '';
            } else {
                $callback = null;
                $controller = $arguments[1];
                $func = $arguments[2];
            }
            $route = (new Route($path, $controller, $func));
            if (in_array($name, ['get', 'post', 'put', 'delete', 'patch'])) {
                $route->method($name);
            } elseif ($name === 'match') {
                foreach ($method as &$item) {
                    $item = strtolower($item);
                }
                unset($item);
                $route->method($method);
            }
            if (is_callable($callback)) {
                $route->callback($callback);
            }
            $prefix = self::$cur_group_prefix ?: (self::$cur_prefix ?: '');
            $middleware = self::$cur_group_middleware ?: (self::$cur_middleware ?: []);
            $route->namespace(self::$cur_namespace)->prefix($prefix)->middleware($middleware)->generate();
            self::addRouteMap($route);
            self::$cur_prefix = '';
            self::$cur_middleware = [];
        }
    }

    private static function addRouteMap(Route $route)
    {
        $pattern = $route->pattern;
        $pattern = explode('/', ltrim($pattern, "/"));
        $max_key = count($pattern) - 1;
        $pid = null;
        foreach ($pattern as $key => $item) {
            self::$route_map[$key][] = ['pattern' => $item, 'route' => $key == $max_key ? $route : null, 'child' => null];
            end(self::$route_map[$key]);
            $cid = key(self::$route_map[$key]);
            if ($pid !== null) {
                self::$route_map[$key - 1][$pid]['child'] = $cid;
            }
            $pid = $cid;
        }
    }

    /**
     * 根据路由文件加载路由
     * @return void
     */
    public static function loadRoutes()
    {
        $routes = get_dir_files(ROUTE_PATH);
        foreach ($routes as $route_file) {
            self::$cur_namespace = str_replace('.php', '', basename($route_file));
            require_once $route_file;
        }
    }

    /**
     * @return Route|bool
     */
    public static function matchRoute()
    {
        $request_uri = request()->server('request_uri');
        $request_uri = explode('/', ltrim($request_uri, "/"));
        $children[0] = array_keys(self::$route_map[0]);
        $max_key = count($request_uri) - 1;
        $spare_route = null;
        $http_method = strtolower(request()->server('request_method'));
        foreach ($request_uri as $key => $item) {
            if (!isset($children[$key])) break;  //没有任何匹配项
            foreach ($children[$key] as $child) {
                $pattern = self::$route_map[$key][$child];
                if ($pattern['pattern'] == $item || preg_match('/^\{(.*)}$/', $pattern['pattern'])) {     //有匹配到的匹配项
                    if ($key == $max_key && !empty($pattern['route'])) {  //路由地址完全匹配
                        if (empty($pattern['route']->method) || in_array($http_method, $pattern['route']->method)) {    //该路由的http方法允许当前请求，则直接返回
                            return $pattern['route'];
                        } else {        //http方法不允许，作为备用
                            $spare_route = $pattern['route'];
                        }
                    } elseif ($key != $max_key && $pattern['child'] !== null) {     //未完全匹配，记录下一次的匹配项
                        $children[$key + 1][] = $pattern['child'];
                    }
                }
            }
        }
        if (!empty($spare_route)) {
            return $spare_route;
        }
        return false;
    }
}