<?php
defined('SYSTEM_PATH') or exit('No direct script access allowed');

use system\kernel\Application;
use system\kernel\Router;


require_once SYS_KERNEL_PATH . "/common.php";

if (config('app', 'load_vendor', false)) {
    require_once 'vendor/autoload.php';
}

//创建redis连接池
if (config('app', 'enable_redis_pool')) {
    CO\run(function () {
        $redis_config_key = config('app', 'redis_config_key');
        $redis_config = config('redis', $redis_config_key);
        new \system\kernel\BaseRedis($redis_config);
    });
}

//创建mysql连接池
if (config('app', 'enable_mysql_pool')) {
    CO\run(function () {
        $mysql_config = config('database');
        foreach ($mysql_config as $db => $db_conf) {
            new \system\kernel\BaseModel($db, $db_conf);
        }
    });
}

// 自动加载指定library和helper
autoload_lib_and_helper();

// 设置协程配置
$coroutine_config = config('swoole', 'coroutine');
if (!empty($coroutine_config)) {
    Co::set($coroutine_config);
}

// 服务器启动
$server_type = server_type();
if ($server_type == CO_HTTP_SERVER) {
    //协程风格
    Co\run(function () {
        $http_config = config('swoole', 'http');

        $server = new Co\Http\Server($http_config['host'] ?? '127.0.0.1', $http_config['port'] ?? '10086', $http_config['ssl'] ?? false);
        $server_config = config('swoole', 'server', []);
        if (!empty($server_config)) {
            $server->set($server_config);
        }

        if (config('swoole', 'websocket')['open_websocket'] ?? false) {
            $server->handle('/websocket', function ($request, $ws) {    //websocket服务器
                call_user_func('websocket_server_callback', $request, $ws);
            });
        }

        $route_map = Router::load_routes();
        $server->handle('/', function ($request, $response) use ($route_map) {
            call_user_func('http_server_callback', $request, $response, $route_map);
        });
        echo "协程http服务器启动\n";
        $server->start();
    });
} elseif ($server_type == SWOOLE_HTTP_SERVER) {
    //异步风格
    $http_config = config('swoole', 'http');

    $server = new Swoole\Http\Server(
        $http_config['host'] ?? '127.0.0.1',
        $http_config['port'] ?? '10086',
        $http_config['server_mode'] ?? SWOOLE_BASE,
        $http_config['socket_type'] ?? SWOOLE_SOCK_TCP
    );
    $server_config = config('swoole', 'server', []);
    if (!empty($server_config)) {
        $server->set($server_config);
    }
    $route_map = Router::load_routes();
    $server->on('request', function ($request, $response) use ($route_map) {
        call_user_func('http_server_callback', $request, $response, $route_map);
    });
    echo "异步http服务器启动\n";
    $server->start();
} else {
    exit("错误的服务器类型\n");
}

//HTTP服务器处理请求函数
function http_server_callback(\Swoole\Http\Request $request, \Swoole\Http\Response $response, $route_map)
{
    //处理chrome请求favicon.ico
    if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
        $response->sendfile(STATIC_PATH . '/common/images/favicon.ico');
        return;
    }
    Application::getInstance($request, $response);
    try {
        if (array_key_exists($request->server['request_uri'], $route_map)) {
            $route = $route_map[$request->server['request_uri']];
            $middleware_result = true;
            foreach ($route['middleware'] as $middleware) {
                $mid_result = (new $middleware())->handle();
                if ($mid_result !== true) {
                    $middleware_result = false;
                    response()->end($mid_result);
                    break;
                }
            }
            if ($middleware_result) {
                if (config('app', 'std_output_to_page')) {
                    //输出标准输出到页面时的写法
                    response()->return(function () use ($route) {
                        $class = new $route['class']();
                        call_user_func([$class, $route['func']]);
                    });
	        } else {
                    //标准输出到控制台的写法
                    $class = new $route['class']();
                    call_user_func([$class, $route['func']]);
                    response()->end();
                }
	    }
        } else {
            response()->status(404);
            response()->end('<h1>Page Not Found</h1>');
	}
    } catch (Throwable $e) {
        debug('ERROR', '捕获错误：' . swoole_last_error() . '， 错误信息：' . $e->getMessage());
        response()->status(500);
        if (config('app', 'debug')) {
            $return = ['msg' => $e->getMessage(), 'request' => $request];
            response()->json($return);
        } else {
            response('服务器错误');
        }
        response()->end();
    }
    if (config('app', 'std_output_to_page')) {
        app()->ob_clean_all();    //使用标准输出到页面时，需要清除缓冲区
    }
    Application::destroy();
    return;
}

//websocket服务器处理请求函数
function websocket_server_callback($request, $ws)
{
    Application::getInstance($request, $ws);

    Application::destroy();
}
