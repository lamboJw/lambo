<?php

use system\kernel\Application;
use system\kernel\Router;

defined('SYSTEM_PATH') or exit('No direct script access allowed');

require_once SYS_KERNEL_PATH . "/common.php";

if (config('app', 'load_vendor')) {
    require_once 'vendor/autoload.php';
}

$coroutine_config = config('swoole', 'coroutine');
if (!empty($coroutine_config)) {
    Co::set($coroutine_config);
}
$server_type = config('app','server_type');
if($server_type == CO_HTTP_SERVER){
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
                Application::getInstance($request, $ws);
                app()->set_websocket_response($ws);

                Application::destroy();
            });
        }

        $server->handle('/favicon.ico', function ($request, $response) {
            $response->sendfile(STATIC_PATH . '/common/images/favicon.ico');
        });

        foreach (Router::load_routes() as $route) {
            $server->handle($route['pattern'], function ($request, $response) use ($route) {
                Application::getInstance($request, $response);
                try {
                    $middleware_result = true;
                    foreach ($route['middleware'] as $middleware) {
                        $mid_result = (new $middleware())->handle();
                        if ($mid_result !== true) {
                            $middleware_result = false;
                            $response->end($mid_result);
                            break;
                        }
                    }
                    if ($middleware_result) {
                        if(config('app','std_output_to_page')){
                            //输出标准输出到页面时的写法
                            response()->return(function () use ($route) {
                                $class = new $route['class']();
                                call_user_func([$class, $route['func']]);
                            });
                        }else{
                            //标准输出到控制台的写法
                            $class = new $route['class']();
                            call_user_func([$class, $route['func']]);
                            $response->end();
                        }
                    }
                } catch (Throwable $e) {
                    debug('ERROR', '捕获错误：' . swoole_last_error() . '， 错误信息：' . $e->getMessage());
                    response()->status(500);
                    if (config('app', 'debug')) {
                        $return = ['msg' => $e->getMessage(), 'request' => $request, 'response' => $response];
                        response()->json($return);
                    } else {
                        response('服务器错误');
                    }
                    $response->end();
                }
                if(config('app','std_output_to_page')) {
                    app()->ob_clean_all();    //使用标准输出到页面时，需要清除缓冲区
                }
                Application::destroy();
            });
        }
        echo "协程http服务器启动\n";
        $server->start();
    });
}elseif($server_type == SWOOLE_HTTP_SERVER){
    //异步风格
    $http_config = config('swoole', 'http');

    $server = new Swoole\Http\Server($http_config['host'] ?? '127.0.0.1', $http_config['port'] ?? '10086', $http_config['server_mode'] ?? SWOOLE_BASE, $http_config['socket_type'] ?? SWOOLE_SOCK_TCP);
    $server_config = config('swoole', 'server', []);
    if (!empty($server_config)) {
        $server->set($server_config);
    }
    $route_map = Router::load_routes();
    $server->on('request', function ($request, $response) use ($route_map) {
        //处理chrome请求favicon.ico
        if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
            $response->sendfile(STATIC_PATH . '/common/images/favicon.ico');
            return;
        }
        Application::getInstance($request, $response);
        try {
            if(array_key_exists($request->server['request_uri'], $route_map)){
                $route = $route_map[$request->server['request_uri']];
                $middleware_result = true;
                foreach ($route['middleware'] as $middleware) {
                    $mid_result = (new $middleware())->handle();
                    if ($mid_result !== true) {
                        $middleware_result = false;
                        $response->end($mid_result);
                        break;
                    }
                }
                if ($middleware_result) {
                    if(config('app','std_output_to_page')){
                        //输出标准输出到页面时的写法
                        response()->return(function () use ($route) {
                            $class = new $route['class']();
                            call_user_func([$class, $route['func']]);
                        });
                    }else{
                        //标准输出到控制台的写法
                        $class = new $route['class']();
                        call_user_func([$class, $route['func']]);
                    }
                }
            }
        } catch (Throwable $e) {
            debug('ERROR', '捕获错误：' . swoole_last_error() . '， 错误信息：' . $e->getMessage());
            response()->status(500);
            if (config('app', 'debug')) {
                $return = ['msg' => $e->getMessage(), 'request' => $request, 'response' => $response];
                response()->json($return);
            } else {
                response('服务器错误');
            }
        }
    });
    echo "异步http服务器启动\n";
    $server->start();
}else{
    exit("错误的服务器类型\n");
}