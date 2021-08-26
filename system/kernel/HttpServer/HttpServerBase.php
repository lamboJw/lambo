<?php


namespace system\kernel\HttpServer;

use Swoole\Coroutine\System;
use system\kernel\Application;
use system\kernel\Router;
use system\kernel\WebsocketServer\WebsocketHandlerInterface;
use Throwable;

abstract class HttpServerBase
{
    protected $server;
    protected array $route_map;
    protected WebsocketHandlerInterface $ws_service;

    protected array $http_config = [
        'host' => '0.0.0.0',
        'port' => '10086',
        'ssl' => false,
        'socket_type' => SWOOLE_SOCK_TCP,
        'server_mode' => SWOOLE_BASE,
        'open_websocket' => true,
        'close_command' => 'close',
        'websocket_service' => \app\websocket\WebsocketService::class,
        'co_ws_broadcast' => true,
        'co_ws_pool_size' => 1024,
    ];

    protected array $server_config = [
        'worker_num' => 4,
        'reactor_num' => 4,
        'max_request' => 0,
        'max_connection' => 10000,
        'http_compression' => true,
        'http_compression_level' => 2,
        'dispatch_mode' => 3,
        'enable_static_handler' => true,
        'document_root' => ROOT_PATH,
        'static_handler_locations' => ['/' . STATIC_NAME],
        'upload_tmp_dir' => STATIC_PATH . '/uploads/',
        'open_http2_protocol' => false,
        'daemonize' => false,
        'log_file' => STATIC_PATH . '/logs/server.log',
        'log_rotation' => SWOOLE_LOG_ROTATION_DAILY,
        'log_date_format' => '%Y-%m-%d %H:%M:%S',
        'enable_coroutine' => true,
        'hook_flags' => SWOOLE_HOOK_ALL,
        'ssl_cert_file' => '',
        'ssl_key_file' => '',
        'ssl_protocols' => 0,
    ];

    protected array $websocket_config = [
        'websocket_subprotocol' => '',
        'open_websocket_close_frame' => false,
        'open_websocket_ping_frame' => false,
        'open_websocket_pong_frame' => false,
        'websocket_compression' => false,
    ];

    public function __construct()
    {
        $this->http_config = array_replace_recursive($this->http_config, config('swoole.http', []));
        $this->server_config = array_replace_recursive($this->server_config, config('swoole.server', []));
        $this->websocket_config = array_replace_recursive($this->websocket_config, config('swoole.websocket', []));
        $this->route_map = Router::load_routes();
        if (!empty($this->http_config['open_websocket'])) {
            $ws_service = $this->http_config['websocket_service'] ?? '';
            if (empty($ws_service)) {
                throw new \RuntimeException('请配置swoole.http.websocket_service项');
            }
            $this->ws_service = new $ws_service();
        }
    }

    abstract protected function onRequest();

    abstract protected function websocket();

    abstract protected function reload();

    public function start()
    {
        $this->server->start();
    }

    //HTTP服务器处理请求函数
    protected function http_server_callback(\Swoole\Http\Request $request, \Swoole\Http\Response $response, $route_map)
    {
        //处理chrome请求favicon.ico
        if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
            $response->sendfile(STATIC_PATH . '/common/images/favicon.ico');
            return;
        }
        app()->set_request($request);
        app()->set_response($response);
        app()->set_session();
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
    }

    /**
     * 自动平滑热更新代码（每5秒检测一次）
     * app路径下，除了helpers和libraries文件夹下文件外的所有文件，
     * 如有文件更新过，则会平滑重启当前Worker进程
     */
    protected function auto_reload()
    {
        go(function () {
            $file_list = [];
            while (true) {
                $files = get_included_files();
                foreach ($files as $file) {
                    $app_path = str_replace('/', '\/', APP_PATH);
                    $reg = "/^{$app_path}\/((?!helpers|libraries).*)$/";
                    if (preg_match($reg, $file, $match)) {
                        $time = @filemtime($file);
                        if (!isset($file_list[$file])) {
                            $file_list[$file] = $time;
                        } elseif ($time != $file_list[$file]) {
                            debug('debug', $file . '文件更新了，重启服务器:' . posix_getpid() . "\n");
                            $this->reload();
                            break 2;
                        }
                    }
                }
                System::sleep(5);
            }
        });
    }
}