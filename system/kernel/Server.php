<?php
/**
 * 服务器
 */

namespace system\kernel;

use Swoole\Process\Pool;
use Swoole\Process;
use system\kernel\HttpServer\CoHttpServer;
use system\kernel\HttpServer\SwooleHttpServer;
use Throwable;

class Server
{
    /**
     * 根据服务器类型启动http服务器
     */
    public function run_http_server()
    {
        switch (config('app.server_type')) {
            case CO_HTTP_SERVER:
            {
                $this->co_http_server();
                break;
            }
            case SWOOLE_HTTP_SERVER:
            {
                $this->swoole_http_server();
                break;
            }
            default:
                exit("错误的服务器类型\n");
        }
    }

    /**
     * 协程风格
     */
    private function co_http_server()
    {
        if (config('swoole.server.daemonize', false)) {
            Process::daemon();
        }
        $workerNum = config('swoole.server.worker_num', 1);
        $pool = new Pool($workerNum, 0, null, true);
        $pool->on('WorkerStart', function ($pool, $workerId) {
            $server = new CoHttpServer($pool);
            Process::signal(SIGTERM, function () use ($server) {
                $server->shutdown();
            });
            $server->onRequest($this->http_server_callback());
            if (config('swoole.websocket.open_websocket', false)) {
                $server->wsOnMessage($this->websocket_server_callback());
            }
            $server->start();
        });
        echo "协程http服务器启动\n";
        $pool->start();
    }

    /**
     * 异步风格
     */
    private function swoole_http_server()
    {
        $server = new SwooleHttpServer();
        $server->onRequest($this->http_server_callback());
        echo "异步http服务器启动\n";
        $server->start();
    }

    //HTTP服务器处理请求函数
    function http_server_callback(): callable
    {
        return function (\Swoole\Http\Request $request, \Swoole\Http\Response $response, $route_map) {
            //处理chrome请求favicon.ico
            if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
                $response->sendfile(STATIC_PATH . '/common/images/favicon.ico');
                return;
            }
            Application::getInstance($request, $response);
            try {
                if (array_key_exists($request->server['request_uri'], $route_map)) {
                    $route = $route_map[$request->server['request_uri']];
                    foreach ($route['middleware'] as $middleware) {
                        $mid_result = (new $middleware())->handle();
                        if ($mid_result !== true) {
                            response()->end($mid_result);
                        }
                    }
                    if (config('app.std_output_to_page')) {
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
                } else {
                    response()->status(404);
                    response()->end('<h1>Page Not Found</h1>');
                }
            } catch (Throwable $e) {
                if ($e instanceof \Swoole\ExitException && $e->getStatus() == SWOOLE_RESPONSE_EXIT) {
                    //如果是响应退出，且开启了标准输出到页面，因为有一个ob_start未闭合，所以要执行一次
                    if (config('app.std_output_to_page')) {
                        app()->ob_get_clean();
                    }
                } else {
                    debug('ERROR', '捕获错误：' . swoole_last_error() . '， 错误信息：' . $e->getMessage());
                    $response->status(500);
                    if (config('app.debug')) {
                        $response->end(json_encode(['msg' => $e->getMessage(), 'request' => $request]));
                    } else {
                        $response->end('<h1>500 服务器错误</h1>');
                    }
                }
            }
            if (config('app.std_output_to_page')) {
                app()->ob_clean_all();    //使用标准输出到页面时，需要清除缓冲区
            }
            Application::destroy();
            return;
        };
    }

    //websocket服务器处理请求函数
    function websocket_server_callback(): callable
    {
        return function ($request, $ws) {
            Application::getInstance($request, $ws);

            Application::destroy();
        };
    }
}